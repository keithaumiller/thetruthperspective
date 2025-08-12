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
    
    // Check articles with empty news source
    $empty_source = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_news_source', '', '=')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $this->output()->writeln("❌ Articles with empty news source: {$empty_source}");
    
    // Check articles with both JSON and empty source
    $json_and_empty = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_json_scraped_article_data', '', '<>')
      ->condition('field_news_source', '', '=')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $this->output()->writeln("🎯 Articles with JSON but no source: {$json_and_empty}");
    
    // Get total count for JSON data processing
    $total_query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_json_scraped_article_data', '', '<>')
      ->condition('field_news_source', '', '=')
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
    $total_query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_original_url.uri', '', '<>')
      ->condition('field_news_source', '', '=')
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
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'article')
        ->condition('field_original_url.uri', '', '<>')
        ->condition('field_news_source', '', '=')
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
      ->condition('field_news_source', '', '<>')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Articles without news source
    $without_source = $total_articles - $with_source;
    
    // Articles with JSON data but no source
    $json_no_source = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_json_scraped_article_data', '', '<>')
      ->condition('field_news_source', '', '=')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Articles with URL but no source (for URL-only fallback)
    $url_only_no_source = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_original_url.uri', '', '<>')
      ->condition('field_news_source', '', '=')
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

}
