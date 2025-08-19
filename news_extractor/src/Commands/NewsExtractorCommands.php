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
    $processing_or_group = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_json_scraped_article_data', NULL, 'IS NULL')
      ->condition('field_json_scraped_article_data', '', '=')
      ->condition('field_json_scraped_article_data', 'Scraped data unavailable.', '=');
    
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_original_url.uri', '', '<>')
      ->condition($processing_or_group)
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
        $scrape_or_group = \Drupal::entityQuery('node')->orConditionGroup()
          ->condition('field_json_scraped_article_data', NULL, 'IS NULL')
          ->condition('field_json_scraped_article_data', '', '=')
          ->condition('field_json_scraped_article_data', 'Scraped data unavailable.', '=');
        $query->condition('field_original_url.uri', '', '<>')
          ->condition($scrape_or_group);
        break;
        
      case 'analyze_only':
        // Articles with good JSON data but no AI analysis
        $ai_or_group = \Drupal::entityQuery('node')->orConditionGroup()
          ->condition('field_ai_raw_response', NULL, 'IS NULL')
          ->condition('field_ai_raw_response', '', '=');
        $query->condition('field_json_scraped_article_data', '', '<>')
          ->condition('field_json_scraped_article_data', 'Scraped data unavailable.', '<>')
          ->condition($ai_or_group);
        break;
        
      case 'reprocess':
        // Articles with failed scraping data
        $query->condition('field_json_scraped_article_data', 'Scraped data unavailable.', '=');
        break;
        
      case 'full':
      default:
        // Articles that need any kind of processing
        $full_or_group = \Drupal::entityQuery('node')->orConditionGroup()
          ->condition('field_json_scraped_article_data', NULL, 'IS NULL')
          ->condition('field_json_scraped_article_data', '', '=')
          ->condition('field_json_scraped_article_data', 'Scraped data unavailable.', '=')
          ->condition('field_ai_raw_response', NULL, 'IS NULL')
          ->condition('field_ai_raw_response', '', '=');
        $query->condition('field_original_url.uri', '', '<>')
          ->condition($full_or_group);
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
   * Clean up articles that are not suitable for processing.
   * 
   * @command news-extractor:cleanup
   * @aliases ne:cleanup
   * @option limit Maximum number of articles to check (default: 100)
   * @option dry-run Show what would be deleted without actually deleting
   * @usage news-extractor:cleanup
   *   Clean up unwanted articles (videos, PDFs, social media, etc.)
   * @usage news-extractor:cleanup --dry-run
   *   See what articles would be deleted without deleting them
   */
  public function cleanupArticles(array $options = ['limit' => 100, 'dry-run' => false]) {
    $limit = $options['limit'];
    $dry_run = $options['dry-run'];
    
    $this->output()->writeln("🧹 " . ($dry_run ? "Checking" : "Cleaning up") . " unwanted articles...");
    
    // Find all articles
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->accessCheck(FALSE)
      ->range(0, $limit)
      ->sort('created', 'DESC');
    
    $nids = $query->execute();
    
    if (empty($nids)) {
      $this->output()->writeln("✅ No articles found to check.");
      return;
    }
    
    $this->output()->writeln("Checking " . count($nids) . " articles...");
    
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
    $deleted = 0;
    $checked = 0;
    
    foreach ($nodes as $node) {
      $checked++;
      $should_delete = $this->shouldDeleteArticle($node);
      
      if ($should_delete) {
        $this->output()->writeln("📄 " . $node->getTitle() . " (ID: " . $node->id() . ")");
        $this->output()->writeln("  🗑️ Reason: " . $should_delete);
        
        if ($dry_run) {
          $this->output()->writeln("  📋 Would delete (dry-run mode)");
        } else {
          $node->delete();
          $this->output()->writeln("  ✅ Deleted");
        }
        
        $deleted++;
      }
    }
    
    $this->output()->writeln("");
    $this->output()->writeln("🎉 Cleanup completed!");
    $this->output()->writeln("  📊 Articles checked: {$checked}");
    $this->output()->writeln("  🗑️ Articles " . ($dry_run ? "would be " : "") . "deleted: {$deleted}");
    
    if ($dry_run && $deleted > 0) {
      $this->output()->writeln("");
      $this->output()->writeln("💡 Run without --dry-run to actually delete these articles");
    }
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
   * Reprocess articles to ensure taxonomy terms are created for news sources.
   *
   * @param int $limit
   *   Number of articles to reprocess. Defaults to 20.
   *
   * @command news-extractor:ensure-source-taxonomy
   * @aliases ne:est
   * @option limit Number of articles to reprocess
   * @usage news-extractor:ensure-source-taxonomy
   *   Reprocess 20 articles to ensure news source taxonomy terms exist
   * @usage news-extractor:ensure-source-taxonomy --limit=50
   *   Reprocess 50 articles to ensure news source taxonomy terms exist
   */
  public function ensureSourceTaxonomy($limit = 20, array $options = ['limit' => 20]) {
    $limit = $options['limit'] ?? $limit;
    
    $this->output()->writeln("🔄 Reprocessing {$limit} articles to ensure news source taxonomy terms...");
    
    // Find articles that have news sources but might need taxonomy term creation
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('field_news_source', '', '<>')
      ->condition('field_news_source', 'Source Unavailable', '<>')
      ->exists('field_news_source')
      ->range(0, $limit)
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    
    if (empty($nids)) {
      $this->output()->writeln("❌ No articles with news sources found.");
      return;
    }
    
    $this->output()->writeln("Found " . count($nids) . " articles with news sources to reprocess...");
    
    /** @var \Drupal\news_extractor\Service\NewsExtractionService $extraction_service */
    $extraction_service = \Drupal::service('news_extractor.extraction');
    
    $processed_count = 0;
    $success_count = 0;
    
    foreach ($nids as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
      if (!$node) {
        continue;
      }
      
      $news_source = $node->get('field_news_source')->value;
      $this->output()->writeln("Processing: {$node->getTitle()} (Source: {$news_source})");
      
      try {
        // Reprocess the article to trigger taxonomy creation
        $success = $extraction_service->reprocessArticle($node);
        
        if ($success) {
          $success_count++;
          $this->output()->writeln("  ✅ Successfully reprocessed");
        } else {
          $this->output()->writeln("  ❌ Failed to reprocess");
        }
      }
      catch (\Exception $e) {
        $this->output()->writeln("  ❌ Error: " . $e->getMessage());
      }
      
      $processed_count++;
    }
    
    $this->output()->writeln("");
    $this->output()->writeln("✅ Completed! Processed {$processed_count} articles, {$success_count} successful.");
    $this->output()->writeln("Check the logs for detailed taxonomy term creation information.");
  }

  /**
   * Create missing taxonomy terms for existing news sources.
   *
   * @command news-extractor:create-missing-taxonomy
   * @aliases ne:cmt
   * @usage news-extractor:create-missing-taxonomy
   *   Create taxonomy terms for news sources that don't have them
   */
  public function createMissingTaxonomy() {
    $this->output()->writeln("🏷️ Creating missing taxonomy terms for news sources...");
    
    /** @var \Drupal\news_extractor\Service\DataProcessingService $data_service */
    $data_service = \Drupal::service('news_extractor.data_processing');
    
    // Get all unique news sources from articles
    $query = \Drupal::database()->select('node__field_news_source', 'ns')
      ->fields('ns', ['field_news_source_value'])
      ->condition('ns.field_news_source_value', '', '<>')
      ->condition('ns.field_news_source_value', 'Source Unavailable', '<>')
      ->distinct();
    
    $news_sources = $query->execute()->fetchCol();
    
    if (empty($news_sources)) {
      $this->output()->writeln("❌ No news sources found in articles.");
      return;
    }
    
    $this->output()->writeln("Found " . count($news_sources) . " unique news sources in articles.");
    
    $created_count = 0;
    $existing_count = 0;
    
    foreach ($news_sources as $news_source) {
      $this->output()->writeln("Processing: {$news_source}");
      
      // Check if taxonomy term already exists
      $existing_terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties([
          'vid' => 'news_sources',
          'name' => $news_source,
        ]);
      
      if (!empty($existing_terms)) {
        $existing_count++;
        $this->output()->writeln("  ✅ Taxonomy term already exists");
      } else {
        // Create new taxonomy term
        try {
          $term = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->create([
              'vid' => 'news_sources',
              'name' => $news_source,
            ]);
          $term->save();
          
          $created_count++;
          $this->output()->writeln("  🆕 Created new taxonomy term (TID: {$term->id()})");
          
          \Drupal::logger('news_extractor')->info('Created missing taxonomy term for news source: @source (TID: @tid)', [
            '@source' => $news_source,
            '@tid' => $term->id(),
          ]);
          
        } catch (\Exception $e) {
          $this->output()->writeln("  ❌ Error creating taxonomy term: " . $e->getMessage());
          \Drupal::logger('news_extractor')->error('Failed to create taxonomy term for @source: @error', [
            '@source' => $news_source,
            '@error' => $e->getMessage(),
          ]);
        }
      }
    }
    
    $this->output()->writeln("");
    $this->output()->writeln("🎉 Completed! Created {$created_count} new taxonomy terms, {$existing_count} already existed.");
    
    if ($created_count > 0) {
      $this->output()->writeln("💡 Run 'drush ne:est' to reprocess articles and link them to the new taxonomy terms.");
    }
  }

  /**
   * Test post-processor on a specific node.
   *
   * @param string $nid
   *   The node ID to test.
   *
   * @command news-extractor:test-postprocessor
   * @aliases ne:test-pp
   * @usage drush ne:test-pp 3016
   *   Test post-processor on node 3016.
   */
  public function testPostProcessor($nid) {
    $this->output()->writeln("🔍 Testing post-processor on node {$nid}...");
    
    // Load the node
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    
    if (!$node) {
      $this->output()->writeln("❌ Node {$nid} not found.");
      return;
    }
    
    $this->output()->writeln("📄 Node: " . $node->getTitle());
    $this->output()->writeln("📊 Current Status: " . ($node->isPublished() ? 'PUBLISHED' : 'UNPUBLISHED'));
    
    // Check motivation analysis field
    if ($node->hasField('field_motivation_analysis')) {
      $motivation_analysis = $node->get('field_motivation_analysis')->value;
      $format = $node->get('field_motivation_analysis')->format;
      
      $this->output()->writeln("🧠 Motivation Analysis Content:");
      $this->output()->writeln("   Value: '{$motivation_analysis}'");
      $this->output()->writeln("   Format: {$format}");
      $this->output()->writeln("   Length: " . strlen($motivation_analysis));
      
      // Test the conditions
      $contains_pending = strpos($motivation_analysis, 'Analysis is Pending') !== false;
      $contains_html_pending = strpos($motivation_analysis, '<p>Analysis is Pending.</p>') !== false;
      $contains_no_data = strpos($motivation_analysis, 'No analysis data available') !== false;
      $contains_html_no_data = strpos($motivation_analysis, '<p>No analysis data available.</p>') !== false;
      
      $this->output()->writeln("🔍 Condition Tests:");
      $this->output()->writeln("   Contains 'Analysis is Pending': " . ($contains_pending ? 'YES' : 'NO'));
      $this->output()->writeln("   Contains '<p>Analysis is Pending.</p>': " . ($contains_html_pending ? 'YES' : 'NO'));
      $this->output()->writeln("   Contains 'No analysis data available': " . ($contains_no_data ? 'YES' : 'NO'));
      $this->output()->writeln("   Contains '<p>No analysis data available.</p>': " . ($contains_html_no_data ? 'YES' : 'NO'));
      
      $should_unpublish = !empty($motivation_analysis) && 
          ($contains_pending || $contains_html_pending || $contains_no_data || $contains_html_no_data);
      
      $this->output()->writeln("⚖️ Should be unpublished: " . ($should_unpublish ? 'YES' : 'NO'));
      
      // Test the actual post-processor
      $this->output()->writeln("");
      $this->output()->writeln("🔧 Running post-processor...");
      
      $data_processing_service = \Drupal::service('news_extractor.data_processing');
      $original_status = $node->isPublished();
      
      // Use reflection to access the protected method
      $reflection = new \ReflectionClass($data_processing_service);
      $method = $reflection->getMethod('postProcessPublishingStatus');
      $method->setAccessible(true);
      $method->invoke($data_processing_service, $node);
      
      $new_status = $node->isPublished();
      $status_changed = $original_status !== $new_status;
      
      $this->output()->writeln("📊 Status after post-processor: " . ($new_status ? 'PUBLISHED' : 'UNPUBLISHED'));
      $this->output()->writeln("🔄 Status changed: " . ($status_changed ? 'YES' : 'NO'));
      
      if ($status_changed) {
        $this->output()->writeln("💾 Saving node...");
        $node->save();
        $this->output()->writeln("✅ Node saved!");
        
        // Show updated analysis field
        $updated_analysis = $node->get('field_motivation_analysis')->value;
        $this->output()->writeln("🔄 Updated analysis field: '{$updated_analysis}'");
      }
      
    } else {
      $this->output()->writeln("❌ Node does not have field_motivation_analysis field.");
    }
  }

  /**
   * Comprehensive cron maintenance including assessment field checks.
   *
   * @param array $options
   *   Command options.
   *
   * @command news-extractor:cron-maintenance
   * @aliases ne:cron
   * @option limit Number of articles to process per run (default: 30)
   * @option check-assessments Include assessment field validation (default: true)
   * @option max-age Maximum age in days for articles to check (default: 14)
   * @usage news-extractor:cron-maintenance
   *   Run comprehensive maintenance including assessment checks
   * @usage news-extractor:cron-maintenance --limit=50 --max-age=7
   *   Check 50 articles from last 7 days
   * @usage news-extractor:cron-maintenance --check-assessments=false
   *   Skip assessment field validation
   */
  public function cronMaintenance(array $options = ['limit' => 30, 'check-assessments' => TRUE, 'max-age' => 14]) {
    $limit = $options['limit'];
    $check_assessments = $options['check-assessments'];
    $max_age = $options['max-age'];
    
    $this->output()->writeln("🔧 [CRON] Starting comprehensive news extractor maintenance...");
    $this->output()->writeln("📅 Processing articles from last {$max_age} days");
    $this->output()->writeln("📊 Limit: {$limit} articles per run");
    $this->output()->writeln("🎯 Assessment checks: " . ($check_assessments ? 'ENABLED' : 'DISABLED'));
    $this->output()->writeln("");
    
    $cutoff_timestamp = time() - ($max_age * 24 * 60 * 60);
    $total_processed = 0;
    $total_updated = 0;
    $total_failed = 0;
    
    // 1. Check for articles needing basic processing (scraping + AI)
    $this->output()->writeln("1️⃣ [CRON] Checking for articles needing basic processing...");
    
    $basic_or_group = \Drupal::entityQuery('node')->orConditionGroup()
      ->condition('field_json_scraped_article_data', NULL, 'IS NULL')
      ->condition('field_json_scraped_article_data', '', '=')
      ->condition('field_json_scraped_article_data', 'Scraped data unavailable.', '=')
      ->condition('field_ai_raw_response', NULL, 'IS NULL')
      ->condition('field_ai_raw_response', '', '=');
    
    $basic_query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('field_original_url.uri', '', '<>')
      ->condition('created', $cutoff_timestamp, '>')
      ->condition($basic_or_group)
      ->accessCheck(FALSE)
      ->range(0, min($limit, 10)) // Limit basic processing to 10 per run
      ->sort('created', 'DESC');
    
    $basic_nids = $basic_query->execute();
    
    if (!empty($basic_nids)) {
      $this->output()->writeln("   📋 Found " . count($basic_nids) . " articles needing basic processing");
      $total_processed += $this->processBasicArticles($basic_nids, $total_updated, $total_failed);
    } else {
      $this->output()->writeln("   ✅ No articles need basic processing");
    }
    
    // 2. Check for assessment fields (if enabled)
    if ($check_assessments) {
      $remaining_limit = $limit - count($basic_nids);
      if ($remaining_limit > 0) {
        $this->output()->writeln("");
        $this->output()->writeln("2️⃣ [CRON] Checking for missing assessment fields...");
        
        $assess_processed = $this->processAssessmentFields($remaining_limit, $max_age);
        $total_processed += $assess_processed;
      }
    }
    
    // 3. Summary
    $this->output()->writeln("");
    $this->output()->writeln("🎉 [CRON] Maintenance complete!");
    $this->output()->writeln("📊 Total articles processed: {$total_processed}");
    
    \Drupal::logger('news_extractor')->info('[CRON] Maintenance completed: @processed articles processed', [
      '@processed' => $total_processed,
    ]);
  }

  /**
   * Process articles needing basic scraping/AI analysis.
   */
  private function processBasicArticles($nids, &$updated, &$failed) {
    /** @var \Drupal\news_extractor\Service\NewsExtractionService $extraction_service */
    $extraction_service = \Drupal::service('news_extractor.extraction');
    
    $processed = 0;
    foreach ($nids as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
      if (!$node) continue;
      
      $processed++;
      $this->output()->writeln("   [BASIC] Processing: " . $node->getTitle() . " (ID: {$nid})");
      
      try {
        $url = $node->get('field_original_url')->uri;
        $result = $extraction_service->processArticle($node, $url);
        
        if ($result) {
          $updated++;
          $this->output()->writeln("     ✅ Success");
        } else {
          $failed++;
          $this->output()->writeln("     ❌ Failed");
        }
      } catch (\Exception $e) {
        $failed++;
        $this->output()->writeln("     ❌ Error: " . $e->getMessage());
      }
      
      sleep(2); // Rate limiting
    }
    
    return $processed;
  }

  /**
   * Process articles missing assessment fields.
   */
  private function processAssessmentFields($limit, $max_age) {
    $cutoff_timestamp = time() - ($max_age * 24 * 60 * 60);
    
    // Create OR condition for missing assessment fields
    $missing_any = \Drupal::entityQuery('node')->orConditionGroup();
    
    $fields = [
      'field_authoritarianism_score',
      'field_credibility_score', 
      'field_bias_rating',
      'field_article_sentiment_score'
    ];
    
    foreach ($fields as $field) {
      $field_missing = \Drupal::entityQuery('node')->orConditionGroup()
        ->condition($field, NULL, 'IS NULL')
        ->condition($field, '', '=');
      $missing_any->condition($field_missing);
    }
    
    $assess_query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('created', $cutoff_timestamp, '>')
      ->condition($missing_any)
      ->accessCheck(FALSE)
      ->range(0, $limit)
      ->sort('created', 'DESC');
    
    $assess_nids = $assess_query->execute();
    
    if (empty($assess_nids)) {
      $this->output()->writeln("   ✅ No articles missing assessment fields");
      return 0;
    }
    
    $this->output()->writeln("   📋 Found " . count($assess_nids) . " articles missing assessment fields");
    
    /** @var \Drupal\news_extractor\Service\NewsExtractionService $extraction_service */
    $extraction_service = \Drupal::service('news_extractor.extraction');
    
    $processed = 0;
    foreach ($assess_nids as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
      if (!$node) continue;
      
      $processed++;
      $this->output()->writeln("   [ASSESS] Reprocessing: " . $node->getTitle() . " (ID: {$nid})");
      
      try {
        $url = $node->get('field_original_url')->uri;
        $result = $extraction_service->processArticle($node, $url);
        
        if ($result) {
          $this->output()->writeln("     ✅ Assessment fields updated");
        } else {
          $this->output()->writeln("     ❌ Failed to update");
        }
      } catch (\Exception $e) {
        $this->output()->writeln("     ❌ Error: " . $e->getMessage());
      }
      
      sleep(3); // Longer delay for assessment reprocessing
    }
    
    return $processed;
  }

}
