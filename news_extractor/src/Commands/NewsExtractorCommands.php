<?php

namespace Drupal\news_extractor\Commands;

use Drush\Commands\DrushCommands;
use Drupal\node\Entity\Node;

/**
 * News Extractor Drush commands.
 */
class NewsExtractorCommands extends DrushCommands {

  /**
   * Populate news source field for existing articles from JSON data.
   *
   * @param int $batch_size
   *   Number of articles to process per batch. Defaults to 100.
   * @param array $options
   *   Additional options.
   *
   * @command news-extractor:populate-sources
   * @aliases ne:pop-sources
   * @option batch_size Number of articles to process per batch
   * @option all Process all articles regardless of batch size
   * @usage news-extractor:populate-sources
   *   Populate news sources from JSON data in batches of 100
   * @usage news-extractor:populate-sources 50
   *   Populate news sources from JSON data in batches of 50
   * @usage news-extractor:populate-sources --all
   *   Process all articles in one run
   */
  public function populateNewsSources($batch_size = 100, array $options = ['all' => FALSE]) {
    
    if ($options['all']) {
      $this->output()->writeln('Starting bulk population of ALL articles...');
      $total_updated = _news_extractor_populate_all_news_sources_from_json();
      $this->output()->writeln("✅ Completed! Updated {$total_updated} articles total.");
      return;
    }

    $this->output()->writeln("Processing articles in batches of {$batch_size}...");
    
    // Debug: Check what fields exist and what data is available
    $this->output()->writeln("🔍 <info>Debugging available data...</info>");
    
    // Check total articles
    $all_articles = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $this->output()->writeln("📰 Total articles: {$all_articles}");
    
    // Check articles with JSON data (any content)
    $with_json = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_json_scraped_article_data', '', '<>')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $this->output()->writeln("📄 Articles with JSON data: {$with_json}");
    
    // Check articles with empty news source (using proper NULL check)
    $or_group = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_news_source', NULL, 'IS NULL')
      ->condition('field_news_source', '', '=');
    
    $empty_source = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition($or_group)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $this->output()->writeln("❌ Articles with empty news source: {$empty_source}");
    
    // Check articles with both JSON and empty source (using proper NULL check)
    $or_group2 = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_news_source', NULL, 'IS NULL')
      ->condition('field_news_source', '', '=');
    
    $json_and_empty = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_json_scraped_article_data', '', '<>')
      ->condition($or_group2)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $this->output()->writeln("🎯 Articles with JSON but no source: {$json_and_empty}");
    
    // Get total count for JSON data processing (using proper NULL check)
    $or_group3 = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_news_source', NULL, 'IS NULL')
      ->condition('field_news_source', '', '=');
    
    $total_query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_json_scraped_article_data', '', '<>')
      ->condition($or_group3)
      ->accessCheck(FALSE);
    
    $total_count = $total_query->count()->execute();
    
    if ($total_count == 0) {
      $this->output()->writeln('No articles found that need news source population.');
      return;
    }
    
    $this->output()->writeln("Found {$total_count} articles with JSON data but missing news source.");
    
    $total_updated = 0;
    $batch_num = 1;
    
    do {
      $this->output()->writeln("Processing batch {$batch_num}...");
      $updated_count = _news_extractor_populate_news_source_from_json_data($batch_size);
      $total_updated += $updated_count;
      
      if ($updated_count > 0) {
        $this->output()->writeln("  ✅ Updated {$updated_count} articles in batch {$batch_num}");
        $batch_num++;
        
        // Brief pause between batches
        sleep(1);
      } else {
        $this->output()->writeln("  ⚪ No more articles to process.");
      }
      
    } while ($updated_count > 0);
    
    $this->output()->writeln("🎉 Completed! Updated {$total_updated} articles total.");
  }

  /**
   * Populate news sources from URLs for articles without JSON data.
   *
   * @param int $batch_size
   *   Number of articles to process per batch. Defaults to 50.
   *
   * @command news-extractor:populate-sources-url
   * @aliases ne:pop-url
   * @option batch_size Number of articles to process per batch
   * @usage news-extractor:populate-sources-url
   *   Populate news sources from URLs in batches of 50
   * @usage news-extractor:populate-sources-url 25
   *   Populate news sources from URLs in batches of 25
   */
  public function populateNewsSourcesFromUrl($batch_size = 50) {
    $this->output()->writeln("Processing articles from URLs in batches of {$batch_size}...");
    
    // Get total count first
    $or_group_url = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_news_source', NULL, 'IS NULL')
      ->condition('field_news_source', '', '=');
    
    $total_query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_original_url.uri', '', '<>')
      ->condition($or_group_url)
      ->accessCheck(FALSE);
    $total_count = $total_query->count()->execute();
    
    if ($total_count == 0) {
      $this->output()->writeln('No articles found that need news source population from URLs.');
      return;
    }
    
    $this->output()->writeln("Found {$total_count} articles with URLs but missing news source.");
    
    $total_updated = 0;
    $batch_num = 1;
    
    do {
      $this->output()->writeln("Processing batch {$batch_num}...");
      
      // Get articles for this batch
      $or_group_batch = \Drupal::entityQuery('node')->orConditionGroup()
        ->condition('field_news_source', NULL, 'IS NULL')
        ->condition('field_news_source', '', '=');
      
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'article')
        ->condition('field_original_url.uri', '', '<>')
        ->condition($or_group_batch)
        ->range(0, $batch_size)
        ->accessCheck(FALSE);

      $nids = $query->execute();
      
      if (empty($nids)) {
        $this->output()->writeln("  ⚪ No more articles to process.");
        break;
      }

      $nodes = Node::loadMultiple($nids);
      $batch_updated = 0;
      
      foreach ($nodes as $node) {
        $original_url = $node->get('field_original_url')->uri;
        $news_source = _news_extractor_extract_news_source_from_url($original_url);
        
        if (!empty($news_source)) {
          $node->set('field_news_source', $news_source);
          $node->save();
          $batch_updated++;
          $total_updated++;
          
          $this->output()->writeln("    → {$node->getTitle()}: {$news_source}");
        }
      }
      
      if ($batch_updated > 0) {
        $this->output()->writeln("  ✅ Updated {$batch_updated} articles in batch {$batch_num}");
        $batch_num++;
        sleep(1);
      }
      
    } while (!empty($nids));
    
    $this->output()->writeln("🎉 Completed! Updated {$total_updated} articles total.");
  }

  /**
   * Show statistics about news source field population.
   *
   * @command news-extractor:source-stats
   * @aliases ne:stats
   * @usage news-extractor:source-stats
   *   Display statistics about news source field population
   */
  public function showSourceStats() {
    $this->output()->writeln('📊 News Source Field Statistics:');
    $this->output()->writeln('');
    
    // First, let's debug what fields actually exist
    $this->output()->writeln("🔍 <info>Debugging field existence...</info>");
    
    // Get a sample article to check field structure
    $sample_query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->range(0, 1)
      ->accessCheck(FALSE);
    $sample_ids = $sample_query->execute();
    
    if (!empty($sample_ids)) {
      $sample_id = reset($sample_ids);
      $sample_node = \Drupal::entityTypeManager()->getStorage('node')->load($sample_id);
      
      $this->output()->writeln("📋 <info>Sample article ID: {$sample_id}</info>");
      
      // Check which fields exist
      $important_fields = [
        'field_news_source',
        'field_json_scraped_article_data', 
        'field_url',
        'field_site_name'
      ];
      
      foreach ($important_fields as $field_name) {
        if ($sample_node->hasField($field_name)) {
          $value = $sample_node->get($field_name)->value;
          $status = !empty($value) ? "✅ HAS DATA" : "⚪ EMPTY";
          $this->output()->writeln("   {$field_name}: {$status}");
          if (!empty($value) && strlen($value) > 100) {
            $this->output()->writeln("      Preview: " . substr($value, 0, 100) . "...");
          } elseif (!empty($value)) {
            $this->output()->writeln("      Value: {$value}");
          }
        } else {
          $this->output()->writeln("   {$field_name}: ❌ FIELD NOT FOUND");
        }
      }
      
      // Also check all fields that start with 'field_'
      $this->output()->writeln("📝 <info>All available fields:</info>");
      $field_definitions = $sample_node->getFieldDefinitions();
      foreach ($field_definitions as $field_name => $definition) {
        if (strpos($field_name, 'field_') === 0) {
          $value = $sample_node->get($field_name)->value;
          $status = !empty($value) ? "✅" : "⚪";
          $this->output()->writeln("   {$field_name}: {$status}");
        }
      }
    }
    
    $this->output()->writeln("");
    
    // Total articles
    $total_articles = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Articles with news source
    $with_source = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_news_source', NULL, 'IS NOT NULL')
      ->condition('field_news_source', '', '!=')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Articles without news source
    $without_source = $total_articles - $with_source;
    
    // Articles with JSON data but no source (using proper NULL check)
    $or_group = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_news_source', NULL, 'IS NULL')
      ->condition('field_news_source', '', '=');
    
    $json_no_source = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_json_scraped_article_data', '', '<>')
      ->condition($or_group)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Articles with URL but no source (for URL-only fallback)
    $or_group2 = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_news_source', NULL, 'IS NULL')
      ->condition('field_news_source', '', '=');
    
    $url_only_no_source = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_original_url.uri', '', '<>')
      ->condition($or_group2)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    $percentage = $total_articles > 0 ? round(($with_source / $total_articles) * 100, 1) : 0;
    
    $this->output()->writeln("📰 Total Articles: {$total_articles}");
    $this->output()->writeln("✅ With News Source: {$with_source} ({$percentage}%)");
    $this->output()->writeln("❌ Missing News Source: {$without_source}");
    $this->output()->writeln("");
    $this->output()->writeln("🔧 Processing Opportunities:");
    $this->output()->writeln("📄 Articles with JSON data (ready for processing): {$json_no_source}");
    $this->output()->writeln("🔗 Articles with URLs only (fallback method): {$url_only_no_source}");
    $this->output()->writeln("");
    
    if ($json_no_source > 0) {
      $this->output()->writeln("💡 Run: drush ne:pop-sources");
      $this->output()->writeln("   To populate from JSON data (most reliable)");
    }
    
    if ($url_only_no_source > 0) {
      $this->output()->writeln("💡 Run: drush ne:pop-url");
      $this->output()->writeln("   To populate from URLs (fallback method)");
    }
  }

  /**
   * Test news source extraction for a specific URL.
   *
   * @param string $url
   *   The URL to test extraction for.
   *
   * @command news-extractor:test-extraction
   * @aliases ne:test
   * @usage news-extractor:test-extraction https://cnn.com/politics/article
   *   Test news source extraction for a specific URL
   */
  public function testExtraction($url) {
    $this->output()->writeln("🧪 Testing news source extraction for: {$url}");
    $this->output()->writeln('');
    
    $news_source = _news_extractor_extract_news_source_from_url($url);
    
    if (!empty($news_source)) {
      $this->output()->writeln("✅ Extracted source: {$news_source}");
    } else {
      $this->output()->writeln("❌ No source could be extracted from this URL");
    }
    
    // Show URL parsing details
    $parsed_url = parse_url($url);
    if (isset($parsed_url['host'])) {
      $host = strtolower($parsed_url['host']);
      $clean_host = preg_replace('/^www\./', '', $host);
      $this->output()->writeln("🔍 Parsed host: {$clean_host}");
    }
  }

  /**
   * Clean up articles with invalid JSON and attempt URL fallback.
   *
   * @command news-extractor:fix-invalid-json
   * @aliases ne:fix-json
   * @usage drush ne:fix-json
   *   Attempt to fix articles with invalid JSON by using URL extraction
   */
  public function fixInvalidJson() {
    $this->output()->writeln("🔧 Fixing articles with invalid JSON data...");
    
    // Find articles with JSON data but still missing news source (likely invalid JSON)
    $or_group = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_news_source', NULL, 'IS NULL')
      ->condition('field_news_source', '', '=');
    
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_json_scraped_article_data', '', '<>')
      ->condition($or_group)
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    
    if (empty($nids)) {
      $this->output()->writeln("✅ No articles with invalid JSON found.");
      return;
    }
    
    $this->output()->writeln("Found " . count($nids) . " articles with JSON data but no source (likely invalid JSON)");
    
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
    $fixed_count = 0;
    
    foreach ($nodes as $node) {
      // Try URL extraction as fallback
      if ($node->hasField('field_original_url') && !$node->get('field_original_url')->isEmpty()) {
        $url = $node->get('field_original_url')->uri;
        $extracted_source = _news_extractor_extract_news_source_from_url($url);
        
        if (!empty($extracted_source)) {
          $node->set('field_news_source', $extracted_source);
          $node->save();
          $fixed_count++;
          
          $this->output()->writeln("  ✅ Fixed article {$node->id()} using URL: {$extracted_source}");
          
          \Drupal::logger('news_extractor')->info('Fixed invalid JSON article @id using URL extraction: @source', [
            '@id' => $node->id(),
            '@source' => $extracted_source,
          ]);
        } else {
          $this->output()->writeln("  ❌ Could not extract source for article {$node->id()}");
        }
      } else {
        $this->output()->writeln("  ❌ No URL available for article {$node->id()}");
      }
    }
    
    $this->output()->writeln("🎉 Fixed {$fixed_count} articles using URL extraction fallback.");
  }

  /**
   * Clean up news source field data to standardize CNN variants.
   *
   * @param int $batch_size
   *   Number of articles to process per batch. Defaults to 100.
   * @param array $options
   *   Additional options.
   *
   * @command news-extractor:clean-sources
   * @aliases ne:clean
   * @option batch_size Number of articles to process per batch
   * @option dry-run Show what would be changed without making changes
   * @usage news-extractor:clean-sources
   *   Clean up news sources in batches of 100
   * @usage news-extractor:clean-sources --dry-run
   *   Show what would be changed without making changes
   * @usage news-extractor:clean-sources 50
   *   Clean up news sources in batches of 50
   */
  public function cleanNewsSources($batch_size = 100, array $options = ['dry-run' => FALSE]) {
    
    $dry_run = $options['dry-run'];
    $action = $dry_run ? 'Analyzing' : 'Cleaning up';
    
    $this->output()->writeln("🧹 {$action} news source field data...");
    
    if ($dry_run) {
      $this->output()->writeln("🔍 <info>DRY RUN MODE - No changes will be made</info>");
    }
    
    // Get data processing service
    /** @var \Drupal\news_extractor\Service\DataProcessingService $data_service */
    $data_service = \Drupal::service('news_extractor.data_processing');
    
    // Find articles with news sources that need cleaning
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_news_source', '', '<>')
      ->accessCheck(FALSE);
    
    $total_count = $query->count()->execute();
    
    if ($total_count == 0) {
      $this->output()->writeln('No articles found with news sources.');
      return;
    }
    
    $this->output()->writeln("Found {$total_count} articles with news sources.");
    
    // Find CNN variants specifically
    $cnn_patterns = [
      'CNN - Politics',
      'CNN - Money', 
      'CNN Money',
      'CNN Politics',
      'CNN Business',
      'CNN Health',
      'CNN Travel',
      'CNN Style',
      'CNN Sport',
      'CNN Entertainment'
    ];
    
    $cnn_counts = [];
    $total_cnn_variants = 0;
    
    foreach ($cnn_patterns as $pattern) {
      $count = \Drupal::entityQuery('node')
        ->condition('type', 'article')
        ->condition('field_news_source', $pattern)
        ->accessCheck(FALSE)
        ->count()
        ->execute();
      
      if ($count > 0) {
        $cnn_counts[$pattern] = $count;
        $total_cnn_variants += $count;
      }
    }
    
    // Also check for other CNN variants with LIKE
    $other_cnn_query = \Drupal::database()->select('node__field_news_source', 'ns')
      ->fields('ns', ['field_news_source_value'])
      ->condition('ns.field_news_source_value', 'CNN%', 'LIKE')
      ->condition('ns.field_news_source_value', 'CNN', '<>')
      ->distinct();
    
    $other_cnn_variants = $other_cnn_query->execute()->fetchCol();
    
    $this->output()->writeln("");
    $this->output()->writeln("📊 <info>CNN Variants Analysis:</info>");
    
    if (!empty($cnn_counts)) {
      foreach ($cnn_counts as $variant => $count) {
        $this->output()->writeln("  {$variant}: {$count} articles");
      }
    }
    
    if (!empty($other_cnn_variants)) {
      $this->output()->writeln("  <info>Other CNN variants found:</info>");
      foreach ($other_cnn_variants as $variant) {
        $count = \Drupal::entityQuery('node')
          ->condition('type', 'article')
          ->condition('field_news_source', $variant)
          ->accessCheck(FALSE)
          ->count()
          ->execute();
        $this->output()->writeln("    {$variant}: {$count} articles");
        $total_cnn_variants += $count;
      }
    }
    
    $this->output()->writeln("");
    $this->output()->writeln("🎯 <info>Total CNN variants to standardize: {$total_cnn_variants}</info>");
    
    if ($dry_run) {
      $this->output()->writeln("");
      $this->output()->writeln("💡 Run without --dry-run to make these changes:");
      $this->output()->writeln("   drush ne:clean");
      return;
    }
    
    if ($total_cnn_variants == 0) {
      $this->output()->writeln("✅ No CNN variants found that need cleaning.");
      return;
    }
    
    // Process articles in batches
    $total_updated = 0;
    $batch_num = 1;
    
    // Get all articles with CNN variants
    $cnn_query = \Drupal::database()->select('node__field_news_source', 'ns')
      ->fields('ns', ['entity_id'])
      ->condition('ns.field_news_source_value', 'CNN%', 'LIKE')
      ->condition('ns.field_news_source_value', 'CNN', '<>');
    
    $cnn_nids = $cnn_query->execute()->fetchCol();
    
    $batches = array_chunk($cnn_nids, $batch_size);
    
    foreach ($batches as $batch_nids) {
      $count = count($batch_nids);
      $this->output()->writeln("Processing batch {$batch_num} ({$count} articles)...");
      
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($batch_nids);
      $batch_updated = 0;
      
      foreach ($nodes as $node) {
        $current_source = $node->get('field_news_source')->value;
        
        // Use the data processing service to clean the source
        $reflection = new \ReflectionClass($data_service);
        $method = $reflection->getMethod('cleanNewsSource');
        $method->setAccessible(true);
        $cleaned_source = $method->invoke($data_service, $current_source);
        
        if ($cleaned_source !== $current_source) {
          $node->set('field_news_source', $cleaned_source);
          $node->save();
          $batch_updated++;
          $total_updated++;
          
          $this->output()->writeln("  ✅ {$current_source} → {$cleaned_source} (Article {$node->id()})");
        }
      }
      
      if ($batch_updated > 0) {
        $this->output()->writeln("  📝 Updated {$batch_updated} articles in batch {$batch_num}");
        $batch_num++;
        sleep(1); // Brief pause between batches
      }
    }
    
    $this->output()->writeln("");
    $this->output()->writeln("🎉 Completed! Updated {$total_updated} articles total.");
    
    // Show final counts
    $final_cnn_count = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_news_source', 'CNN')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    $this->output()->writeln("📊 Final result: {$final_cnn_count} articles now have 'CNN' as their source.");
  }

  /**
   * Show processing summary and recommendations.
   *
   * @command news-extractor:summary
   * @aliases ne:summary
   * @usage drush ne:summary
   *   Show processing status and next steps
   */
  public function showSummary() {
    $this->output()->writeln("📋 News Extractor Processing Summary");
    $this->output()->writeln("====================================");
    
    // Get total articles
    $total_articles = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Articles with news source
    $with_source = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_news_source', '', '<>')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Articles with JSON data
    $with_json = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_json_scraped_article_data', '', '<>')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Articles missing source but have JSON (potentially invalid JSON)
    $or_group = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_news_source', NULL, 'IS NULL')
      ->condition('field_news_source', '', '=');
    
    $json_no_source = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_json_scraped_article_data', '', '<>')
      ->condition($or_group)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Articles with URLs but no source
    $or_group2 = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_news_source', NULL, 'IS NULL')
      ->condition('field_news_source', '', '=');
    
    $url_no_source = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_original_url.uri', '', '<>')
      ->condition($or_group2)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    $missing_source = $total_articles - $with_source;
    $percentage = $total_articles > 0 ? round(($with_source / $total_articles) * 100, 1) : 0;
    
    $this->output()->writeln("");
    $this->output()->writeln("📊 Overall Statistics:");
    $this->output()->writeln("  📰 Total Articles: {$total_articles}");
    $this->output()->writeln("  ✅ With News Source: {$with_source} ({$percentage}%)");
    $this->output()->writeln("  ❌ Missing News Source: {$missing_source}");
    $this->output()->writeln("  📄 With JSON Data: {$with_json}");
    $this->output()->writeln("");
    
    $this->output()->writeln("🔧 Processing Status:");
    $this->output()->writeln("  🎯 JSON but no source: {$json_no_source} (likely invalid JSON)");
    $this->output()->writeln("  🔗 URL but no source: {$url_no_source} (fallback available)");
    $this->output()->writeln("");
    
    if ($json_no_source > 0) {
      $this->output()->writeln("💡 Recommendations:");
      $this->output()->writeln("  🔧 Run: drush ne:fix-json");
      $this->output()->writeln("     To fix articles with invalid JSON using URL extraction");
      $this->output()->writeln("");
    }
    
    if ($url_no_source > 0 && $json_no_source == 0) {
      $this->output()->writeln("💡 Recommendations:");
      $this->output()->writeln("  🔗 Run: drush ne:pop-url");
      $this->output()->writeln("     To extract sources from URLs");
      $this->output()->writeln("");
    }
    
    if ($percentage >= 95) {
      $this->output()->writeln("🎉 Excellent! {$percentage}% of articles have news sources.");
    } elseif ($percentage >= 90) {
      $this->output()->writeln("👍 Good progress! {$percentage}% of articles have news sources.");
    } else {
      $this->output()->writeln("🚧 More processing needed. {$percentage}% of articles have news sources.");
    }
  }

  /**
   * Process recent articles through the complete pipeline.
   *
   * @command news-extractor:process
   * @aliases ne:process
   * @usage news-extractor:process
   *   Process recent articles that need scraping or AI analysis
   */
  public function processArticles() {
    $this->output()->writeln("🚀 Processing recent articles...");
    
    /** @var \Drupal\news_extractor\Service\NewsExtractionService $extraction_service */
    $extraction_service = \Drupal::service('news_extractor.extraction');
    
    // Find articles that need processing (have URL but no JSON data)
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_original_url.uri', '', '<>')
      ->group()
        ->condition('field_json_scraped_article_data', NULL, 'IS NULL')
        ->condition('field_json_scraped_article_data', '', '=')
        ->condition('field_json_scraped_article_data', 'Scraped data unavailable.', '=')
      ->groupOperator('OR')
      ->range(0, 10)
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    
    if (empty($nids)) {
      $this->output()->writeln("✅ No recent articles need processing.");
      return;
    }
    
    $this->output()->writeln("Found " . count($nids) . " articles to process...");
    
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
    $processed = 0;
    
    foreach ($nodes as $node) {
      $this->output()->writeln("Processing: " . $node->getTitle());
      
      try {
        if (!$node->hasField('field_original_url') || $node->get('field_original_url')->isEmpty()) {
          throw new \Exception("No URL available for processing");
        }
        $url = $node->get('field_original_url')->uri;
        $extraction_service->processArticle($node, $url);
        $processed++;
        $this->output()->writeln("  ✅ Completed");
      } catch (\Exception $e) {
        $this->output()->writeln("  ❌ Failed: " . $e->getMessage());
      }
      
      // Brief pause between articles
      sleep(2);
    }
    
    $this->output()->writeln("🎉 Processed {$processed} articles successfully.");
  }

  /**
   * Bulk process articles with various options.
   *
   * @param array $options
   *   Processing options.
   *
   * @command news-extractor:bulk-process
   * @aliases ne:bulk
   * @option limit Number of articles to process (default: 50)
   * @option type Processing type: full, scrape_only, analyze_only, reprocess
   * @option node-id Process specific node ID
   * @usage news-extractor:bulk-process --limit=50 --type=full
   *   Full processing pipeline for 50 articles
   * @usage news-extractor:bulk-process --type=scrape_only
   *   Only Diffbot scraping, no AI analysis
   * @usage news-extractor:bulk-process --type=analyze_only
   *   Only AI analysis for already scraped articles
   * @usage news-extractor:bulk-process --type=reprocess --node-id=2941
   *   Reprocess specific article with failed scraping
   */
  public function bulkProcess(array $options = ['limit' => 50, 'type' => 'full', 'node-id' => NULL]) {
    $limit = $options['limit'];
    $type = $options['type'];
    $node_id = $options['node-id'];
    
    /** @var \Drupal\news_extractor\Service\NewsExtractionService $extraction_service */
    $extraction_service = \Drupal::service('news_extractor.extraction');
    
    $this->output()->writeln("🔧 Bulk processing articles (type: {$type})...");
    
    // Handle specific node processing
    if ($node_id) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($node_id);
      if (!$node) {
        $this->output()->writeln("❌ Node {$node_id} not found.");
        return;
      }
      
      $this->output()->writeln("Processing specific node: {$node_id} - " . $node->getTitle());
      
      try {
        switch ($type) {
          case 'scrape_only':
            $this->reprocessFailedScraping($node);
            break;
          case 'analyze_only':
            $extraction_service->analyzeArticleOnly($node);
            break;
          case 'reprocess':
          case 'full':
          default:
            if (!$node->hasField('field_original_url') || $node->get('field_original_url')->isEmpty()) {
              throw new \Exception("No URL available for processing");
            }
            $url = $node->get('field_original_url')->uri;
            $extraction_service->processArticle($node, $url);
            break;
        }
        $this->output()->writeln("✅ Successfully processed node {$node_id}");
      } catch (\Exception $e) {
        $this->output()->writeln("❌ Failed to process node {$node_id}: " . $e->getMessage());
      }
      return;
    }
    
    // Bulk processing based on type
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->accessCheck(FALSE)
      ->range(0, $limit)
      ->sort('created', 'DESC');
    
    switch ($type) {
      case 'scrape_only':
        // Articles with URLs but no/failed JSON data
        $query->condition('field_original_url.uri', '', '<>')
          ->group()
            ->condition('field_json_scraped_article_data', NULL, 'IS NULL')
            ->condition('field_json_scraped_article_data', '', '=')
            ->condition('field_json_scraped_article_data', 'Scraped data unavailable.', '=')
          ->groupOperator('OR');
        break;
        
      case 'analyze_only':
        // Articles with good JSON data but no AI analysis
        $query->condition('field_json_scraped_article_data', '', '<>')
          ->condition('field_json_scraped_article_data', 'Scraped data unavailable.', '<>')
          ->group()
            ->condition('field_ai_raw_response', NULL, 'IS NULL')
            ->condition('field_ai_raw_response', '', '=')
          ->groupOperator('OR');
        break;
        
      case 'reprocess':
        // Articles with failed scraping data
        $query->condition('field_json_scraped_article_data', 'Scraped data unavailable.', '=');
        break;
        
      case 'full':
      default:
        // Articles that need any kind of processing
        $query->condition('field_original_url.uri', '', '<>')
          ->group()
            ->condition('field_json_scraped_article_data', NULL, 'IS NULL')
            ->condition('field_json_scraped_article_data', '', '=')
            ->condition('field_json_scraped_article_data', 'Scraped data unavailable.', '=')
            ->condition('field_ai_raw_response', NULL, 'IS NULL')
            ->condition('field_ai_raw_response', '', '=')
          ->groupOperator('OR');
        break;
    }
    
    $nids = $query->execute();
    
    if (empty($nids)) {
      $this->output()->writeln("✅ No articles found for processing type: {$type}");
      return;
    }
    
    $this->output()->writeln("Found " . count($nids) . " articles for {$type} processing...");
    
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
    $processed = 0;
    $failed = 0;
    
    foreach ($nodes as $node) {
      $this->output()->writeln("Processing: " . $node->getTitle() . " (ID: " . $node->id() . ")");
      
      try {
        switch ($type) {
          case 'scrape_only':
            $this->reprocessFailedScraping($node);
            break;
          case 'analyze_only':
            $extraction_service->analyzeArticleOnly($node);
            break;
          case 'reprocess':
          case 'full':
          default:
            if (!$node->hasField('field_original_url') || $node->get('field_original_url')->isEmpty()) {
              throw new \Exception("No URL available for processing");
            }
            $url = $node->get('field_original_url')->uri;
            $extraction_service->processArticle($node, $url);
            break;
        }
        $processed++;
        $this->output()->writeln("  ✅ Completed");
      } catch (\Exception $e) {
        $failed++;
        $this->output()->writeln("  ❌ Failed: " . $e->getMessage());
        \Drupal::logger('news_extractor')->error('Bulk processing failed for node @nid: @error', [
          '@nid' => $node->id(),
          '@error' => $e->getMessage(),
        ]);
      }
      
      // Brief pause between articles to avoid rate limits
      sleep(3);
    }
    
    $this->output()->writeln("");
    $this->output()->writeln("🎉 Bulk processing completed!");
    $this->output()->writeln("  ✅ Successfully processed: {$processed}");
    $this->output()->writeln("  ❌ Failed: {$failed}");
  }

  /**
   * Check processing status of articles.
   *
   * @param array $options
   *   Status options.
   *
   * @command news-extractor:status
   * @aliases ne:status
   * @option limit Number of recent articles to check (default: 10)
   * @usage news-extractor:status --limit=20
   *   Check status of 20 most recent articles
   */
  public function checkStatus(array $options = ['limit' => 10]) {
    $limit = $options['limit'];
    
    $this->output()->writeln("📊 Article Processing Status (Last {$limit} articles)");
    $this->output()->writeln("=============================================");
    
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->range(0, $limit)
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    
    if (empty($nids)) {
      $this->output()->writeln("No articles found.");
      return;
    }
    
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
    
    foreach ($nodes as $node) {
      $this->output()->writeln("");
      $this->output()->writeln("📰 Article: " . $node->getTitle());
      $this->output()->writeln("   ID: " . $node->id());
      $this->output()->writeln("   Created: " . date('Y-m-d H:i:s', $node->getCreatedTime()));
      
      // Check URL
      $url = $node->hasField('field_original_url') && !$node->get('field_original_url')->isEmpty() 
        ? $node->get('field_original_url')->uri : 'None';
      $this->output()->writeln("   URL: " . $url);
      
      // Check news source
      $source = $node->hasField('field_news_source') && !$node->get('field_news_source')->isEmpty()
        ? $node->get('field_news_source')->value : 'None';
      $this->output()->writeln("   Source: " . $source);
      
      // Check JSON data status
      $json_status = "❌ None";
      if ($node->hasField('field_json_scraped_article_data') && !$node->get('field_json_scraped_article_data')->isEmpty()) {
        $json_value = $node->get('field_json_scraped_article_data')->value;
        if ($json_value === 'Scraped data unavailable.') {
          $json_status = "⚠️  Failed";
        } else {
          $json_status = "✅ Available";
        }
      }
      $this->output()->writeln("   Scraped Data: " . $json_status);
      
      // Check AI analysis status
      $ai_status = "❌ None";
      if ($node->hasField('field_ai_raw_response') && !$node->get('field_ai_raw_response')->isEmpty()) {
        $ai_status = "✅ Available";
      }
      $this->output()->writeln("   AI Analysis: " . $ai_status);
      
      // Overall status
      $overall = "🔴 Needs Processing";
      if ($json_status === "✅ Available" && $ai_status === "✅ Available") {
        $overall = "🟢 Complete";
      } elseif ($json_status === "✅ Available") {
        $overall = "🟡 Needs AI Analysis";
      } elseif ($json_status === "⚠️  Failed") {
        $overall = "🔴 Failed Scraping";
      }
      $this->output()->writeln("   Status: " . $overall);
    }
  }

  /**
   * Helper method to reprocess failed scraping for a specific node.
   */
  private function reprocessFailedScraping($node) {
    /** @var \Drupal\news_extractor\Service\NewsExtractionService $extraction_service */
    $extraction_service = \Drupal::service('news_extractor.extraction');
    
    if (!$node->hasField('field_original_url') || $node->get('field_original_url')->isEmpty()) {
      throw new \Exception("No URL available for scraping");
    }
    
    $url = $node->get('field_original_url')->uri;
    $this->output()->writeln("  🔄 Re-scraping URL: " . $url);
    
    // Re-run Diffbot scraping using the extraction service
    $extraction_service->scrapeArticleOnly($node, $url);
    
    $this->output()->writeln("  ✅ Scraping completed");
  }

  /**
   * Re-evaluate publishing status for unpublished articles with complete processing.
   *
   * @param array $options
   *   Command options.
   *
   * @command news-extractor:republish
   * @aliases ne:republish
   * @option limit Number of articles to check (default: 50)
   * @option dry-run Show what would be republished without making changes
   * @usage news-extractor:republish
   *   Re-evaluate and potentially republish unpublished articles
   * @usage news-extractor:republish --dry-run
   *   Show which articles would be republished without making changes
   */
  public function reevaluatePublishing(array $options = ['limit' => 50, 'dry-run' => FALSE]) {
    $limit = $options['limit'];
    $dry_run = $options['dry-run'];
    
    $action = $dry_run ? 'Analyzing' : 'Re-evaluating';
    $this->output()->writeln("🔍 {$action} unpublished articles for potential republishing...");
    
    if ($dry_run) {
      $this->output()->writeln("🔍 <info>DRY RUN MODE - No changes will be made</info>");
    }

    /** @var \Drupal\news_extractor\Service\DataProcessingService $data_service */
    $data_service = \Drupal::service('news_extractor.data_processing');

    // Find unpublished articles
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 0) // Only unpublished articles
      ->range(0, $limit)
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);

    $nids = $query->execute();

    if (empty($nids)) {
      $this->output()->writeln("✅ No unpublished articles found.");
      return;
    }

    $this->output()->writeln("Found " . count($nids) . " unpublished articles to evaluate...");

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
    $republished = 0;
    $still_unpublished = 0;

    foreach ($nodes as $node) {
      $title = $node->getTitle();
      $nid = $node->id();
      
      // Use reflection to access the protected method for dry run analysis
      $reflection = new \ReflectionClass($data_service);
      $method = $reflection->getMethod('shouldArticleBePublished');
      $method->setAccessible(true);
      $should_publish = $method->invoke($data_service, $node);

      if ($should_publish) {
        if ($dry_run) {
          $this->output()->writeln("  📰 Would republish: {$title} (ID: {$nid})");
          $republished++;
        } else {
          $success = $data_service->reevaluatePublishingStatus($node);
          if ($success) {
            $this->output()->writeln("  ✅ Republished: {$title} (ID: {$nid})");
            $republished++;
          } else {
            $this->output()->writeln("  ❌ Failed to republish: {$title} (ID: {$nid})");
            $still_unpublished++;
          }
        }
      } else {
        $still_unpublished++;
        if ($dry_run) {
          $this->output()->writeln("  ⚪ Stays unpublished: {$title} (ID: {$nid}) - processing incomplete");
        }
      }
    }

    $this->output()->writeln("");
    if ($dry_run) {
      $this->output()->writeln("📊 Analysis Results:");
      $this->output()->writeln("  📰 Would republish: {$republished} articles");
      $this->output()->writeln("  ⚪ Would stay unpublished: {$still_unpublished} articles");
      $this->output()->writeln("");
      $this->output()->writeln("💡 Run without --dry-run to make these changes:");
      $this->output()->writeln("   drush ne:republish");
    } else {
      $this->output()->writeln("🎉 Re-evaluation completed!");
      $this->output()->writeln("  ✅ Republished: {$republished} articles");
      $this->output()->writeln("  ⚪ Still unpublished: {$still_unpublished} articles");
    }
  }

}
