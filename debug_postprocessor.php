<?php

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once '/var/www/html/drupal/autoload.php';
$kernel = new DrupalKernel('prod', $autoloader);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);

// Load article 3016 to test post-processor
$node = \Drupal::entityTypeManager()->getStorage('node')->load(3016);

if ($node) {
  echo "=== DEBUGGING ARTICLE 3016 ===\n";
  echo "Title: " . $node->getTitle() . "\n";
  echo "Published Status: " . ($node->isPublished() ? 'PUBLISHED' : 'UNPUBLISHED') . "\n";
  
  if ($node->hasField('field_motivation_analysis')) {
    $motivation_analysis = $node->get('field_motivation_analysis')->value;
    echo "Raw motivation analysis value: '" . $motivation_analysis . "'\n";
    echo "Length: " . strlen($motivation_analysis) . "\n";
    echo "Contains 'Analysis is Pending': " . (strpos($motivation_analysis, 'Analysis is Pending') !== false ? 'YES' : 'NO') . "\n";
    
    // Test the exact condition from post-processor
    $should_unpublish = !empty($motivation_analysis) && 
        (strpos($motivation_analysis, 'Analysis is Pending') !== false ||
         strpos($motivation_analysis, 'No analysis data available') !== false);
    
    echo "Should be unpublished: " . ($should_unpublish ? 'YES' : 'NO') . "\n";
    
    // Test running the post-processor manually
    echo "\n=== TESTING POST-PROCESSOR ===\n";
    $data_processing_service = \Drupal::service('news_extractor.data_processing');
    
    $original_status = $node->isPublished();
    echo "Original status: " . ($original_status ? 'PUBLISHED' : 'UNPUBLISHED') . "\n";
    
    // Use reflection to access the protected method
    $reflection = new \ReflectionClass($data_processing_service);
    $method = $reflection->getMethod('postProcessMotivationAnalysis');
    $method->setAccessible(true);
    $method->invoke($data_processing_service, $node);
    
    $new_status = $node->isPublished();
    echo "Status after post-processor: " . ($new_status ? 'PUBLISHED' : 'UNPUBLISHED') . "\n";
    echo "Status changed: " . ($original_status !== $new_status ? 'YES' : 'NO') . "\n";
    
    if ($original_status !== $new_status) {
      echo "Saving node...\n";
      $node->save();
      echo "Node saved!\n";
    }
    
  } else {
    echo "Node does not have field_motivation_analysis field\n";
  }
  
} else {
  echo "Could not load article 3016\n";
}

$kernel->terminate($request, $response);
