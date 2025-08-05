<?php

use Drupal\Core\Entity\EntityInterface;
use GuzzleHttp\Client;
use Drupal\node\Entity\Node;

/**
 * Extract full article content using Diffbot API and generate AI summary.
 */
function _news_extractor_extract_content(EntityInterface $entity, $url) {
  $api_token = news_extractor_get_diffbot_token();
  if (empty($api_token)) {
    \Drupal::logger('news_extractor')->error('Diffbot token not set in configuration.');
    return;
  }
  
  // Check if URL is valid for article processing
  if (!_news_extractor_scraper_is_article_url($url)) {
    \Drupal::logger('news_extractor')->info('Skipping non-article URL: @url', ['@url' => $url]);
    return;
  }
  
  try {
    // USE THE CENTRALIZED FUNCTION INSTEAD OF INLINE API CALL
    $diffbot_response = _news_extractor_scraper_get_diffbot_data($url, $api_token);
    
    if ($diffbot_response && isset($diffbot_response['objects'][0]['text'])) {
      // STEP 1: Store complete Diffbot JSON response
      if ($entity->hasField('field_json_scraped_article_data')) {
        $entity->set('field_json_scraped_article_data', json_encode($diffbot_response, JSON_PRETTY_PRINT));
        \Drupal::logger('news_extractor')->info('Stored complete Diffbot JSON data (@size chars) for: @title', [
          '@size' => strlen(json_encode($diffbot_response)),
          '@title' => $entity->getTitle(),
        ]);
      }
      
      // STEP 2: Update basic article fields
      _news_extractor_scraper_update_basic_fields($entity, $diffbot_response);

      // STEP 3: Generate AI analysis
      $article_data = $diffbot_response['objects'][0];
      $ai_summary = _news_extractor_generate_ai_summary($article_data['text'], $entity->getTitle());
      
      if ($ai_summary && $entity->hasField('field_motivation_analysis')) {
        
        // Store the RAW AI response in dedicated field
        if ($entity->hasField('field_ai_raw_response')) {
          $entity->set('field_ai_raw_response', $ai_summary);
        }
        
        // Keep existing AI Summary field for backward compatibility
        if ($entity->hasField('field_ai_summary')) {
          $entity->set('field_ai_summary', $ai_summary);
        }
        
        // Extract and store structured data (for JSON field)
        $structured_data = _news_extractor_extract_structured_data($ai_summary);
        
        if ($entity->hasField('field_motivation_data')) {
          $entity->set('field_motivation_data', json_encode($structured_data));
        }
        
        // Store individual assessment fields FIRST
        if (isset($structured_data['credibility_score']) && $entity->hasField('field_credibility_score')) {
          $entity->set('field_credibility_score', (string) $structured_data['credibility_score']);
        }
        
        if (isset($structured_data['bias_rating']) && $entity->hasField('field_bias_rating')) {
          $entity->set('field_bias_rating', (string) $structured_data['bias_rating']);
        }
        
        if (isset($structured_data['bias_analysis']) && $entity->hasField('field_bias_analysis')) {
          $entity->set('field_bias_analysis', $structured_data['bias_analysis']);
        }
        
        if (isset($structured_data['sentiment_score']) && $entity->hasField('field_article_sentiment_score')) {
          $entity->set('field_article_sentiment_score', (string) $structured_data['sentiment_score']);
        }

        // Create simple tags for browsing (entities + motivations + metrics) BEFORE formatting
        if (!empty($structured_data)) {
          $simple_tags = [];
          
          // Extract entity names
          if (isset($structured_data['entities']) && is_array($structured_data['entities'])) {
            foreach ($structured_data['entities'] as $entity_data) {
              if (isset($entity_data['name'])) {
                $simple_tags[] = $entity_data['name']; // Entity tag
              }
              if (isset($entity_data['motivations']) && is_array($entity_data['motivations'])) {
                foreach ($entity_data['motivations'] as $motivation) {
                  $simple_tags[] = $motivation; // Motivation tag
                }
              }
            }
          }
          
          // Add metrics
          if (isset($structured_data['metrics']) && is_array($structured_data['metrics'])) {
            $simple_tags = array_merge($simple_tags, $structured_data['metrics']);
          }

          // Create taxonomy terms FIRST (so they exist when we create links)
          $tag_ids = [];
          foreach (array_unique($simple_tags) as $tag) {
            if (!empty(trim($tag))) {
              $tid = _news_extractor_get_or_create_tag($tag);
              if ($tid) {
                $tag_ids[] = $tid;
              }
            }
          }

          if (!empty($tag_ids)) {
            $entity->set('field_tags', $tag_ids);
          }
        }

        // Save entity FIRST to ensure taxonomy terms are created
        $entity->save();
        
        // NOW format the analysis with taxonomy links (AFTER tags are created and saved)
        $motivation_analysis = news_extractor_format_json_analysis($structured_data);
        $entity->set('field_motivation_analysis', [
          'value' => $motivation_analysis,
          'format' => 'basic_html',
        ]);

        // Save again with the linked motivation analysis
        $entity->save();
        
        // Enhanced logging to track what's stored where
        \Drupal::logger('news_extractor')->info('Complete processing for @title: Diffbot JSON (@json_size chars), AI raw (@raw_len chars), Structured data (@struct_items entities), Formatted analysis (@format_len chars)', [
          '@title' => $entity->getTitle(),
          '@json_size' => strlen(json_encode($diffbot_response)),
          '@raw_len' => strlen($ai_summary),
          '@struct_items' => count($structured_data['entities'] ?? []),
          '@format_len' => strlen($motivation_analysis),
        ]);
      }

      \Drupal::logger('news_extractor')->info('Successfully processed article: @title', [
        '@title' => $entity->getTitle(),
      ]);
    } else {
      \Drupal::logger('news_extractor')->warning('No article text returned from Diffbot for URL: @url', [
        '@url' => $url,
      ]);
    }
  } catch (\Exception $e) {
    \Drupal::logger('news_extractor')->error('Error extracting content from @url: @message', [
      '@url' => $url,
      '@message' => $e->getMessage(),
    ]);
  }
}

/**
 * Update article entity with extracted data from Diffbot.
 */
function _news_extractor_update_article(EntityInterface $entity, array $article_data) {
  $updated = FALSE;

  // Update body with full article text
  if (!empty($article_data['text'])) {
    $entity->set('body', [
      'value' => $article_data['text'],
      'format' => 'basic_html',
    ]);
    $updated = TRUE;
  }

  // Update title if available
  if (!empty($article_data['title'])) {
    $entity->setTitle($article_data['title']);
    $updated = TRUE;
  }

  // Truncate field_original_url_title for DB safety
  if ($entity->hasField('field_original_url_title')) {
    $title_value = $entity->getTitle();
    $title_value = substr($title_value, 0, 255);
    $entity->set('field_original_url_title', $title_value);
  }

  if ($updated) {
    $entity->save();
  }
}

/**
 * Update basic content fields from complete Diffbot response.
 * 
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The article node entity.
 * @param array $diffbot_response
 *   Complete Diffbot API response.
 */
function _news_extractor_scraper_update_basic_fields($entity, $diffbot_response) {
  $article_data = $diffbot_response['objects'][0] ?? null;
  if (!$article_data) {
    \Drupal::logger('news_extractor')->warning('No article object found in Diffbot response for: @title', [
      '@title' => $entity->getTitle(),
    ]);
    return;
  }

  $updated = FALSE;

  // Update body with full article text
  if (!empty($article_data['text'])) {
    $entity->set('body', [
      'value' => $article_data['text'],
      'format' => 'basic_html',
    ]);
    $updated = TRUE;
    \Drupal::logger('news_extractor')->info('Updated body content (@chars chars) for: @title', [
      '@chars' => strlen($article_data['text']),
      '@title' => $entity->getTitle(),
    ]);
  }

  // Update title if empty
  if (empty($entity->getTitle()) && !empty($article_data['title'])) {
    $entity->setTitle($article_data['title']);
    $updated = TRUE;
    \Drupal::logger('news_extractor')->info('Updated title to: @title', [
      '@title' => $article_data['title'],
    ]);
  }

  // Update publication date
  _news_extractor_scraper_update_publication_date($entity, $article_data);

  // Update external image
  _news_extractor_scraper_update_external_image($entity, $article_data);
  
  // Store additional metadata
  _news_extractor_scraper_store_metadata($entity, $article_data);

  // Truncate field_original_url_title for DB safety
  if ($entity->hasField('field_original_url_title')) {
    $title_value = $entity->getTitle();
    $title_value = substr($title_value, 0, 255);
    $entity->set('field_original_url_title', $title_value);
  }

  if ($updated) {
    $entity->save();
  }
}

/**
 * Update publication date from Diffbot data with enhanced date handling and fallback.
 * 
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The article node entity.
 * @param array $article_data
 *   Article data from Diffbot.
 * @param bool $use_creation_fallback
 *   Whether to use node creation date as fallback.
 * 
 * @return bool
 *   TRUE if date was updated, FALSE otherwise.
 */
function _news_extractor_scraper_update_publication_date($entity, $article_data, $use_creation_fallback = TRUE) {
  if (!$entity->hasField('field_publication_date')) {
    \Drupal::logger('news_extractor')->info('Node @nid does not have field_publication_date field', [
      '@nid' => $entity->id(),
    ]);
    return FALSE;
  }

  // Skip if publication date is already set
  if (!$entity->get('field_publication_date')->isEmpty()) {
    \Drupal::logger('news_extractor')->info('Publication date already set for node @nid, skipping', [
      '@nid' => $entity->id(),
    ]);
    return FALSE;
  }

  $date_value = null;
  $source = null;
  
  // Try multiple date fields from Diffbot in order of preference
  if (!empty($article_data['date'])) {
    $date_value = $article_data['date'];
    $source = 'diffbot_date';
  } elseif (!empty($article_data['estimatedDate'])) {
    $date_value = $article_data['estimatedDate'];
    $source = 'diffbot_estimatedDate';
  } elseif (!empty($article_data['publishedAt'])) {
    $date_value = $article_data['publishedAt'];
    $source = 'diffbot_publishedAt';
  } elseif (!empty($article_data['created'])) {
    $date_value = $article_data['created'];
    $source = 'diffbot_created';
  } elseif ($use_creation_fallback) {
    // FALLBACK: Use node creation date
    $date_value = $entity->getCreatedTime();
    $source = 'node_creation_date';
  }
  
  if ($date_value) {
    try {
      // Handle different date formats
      $date_obj = null;
      
      // Try parsing as timestamp first
      if (is_numeric($date_value)) {
        $date_obj = new \DateTime();
        $date_obj->setTimestamp($date_value);
      } else {
        // Try parsing as date string
        $date_obj = new \DateTime($date_value);
      }
      
      if ($date_obj) {
        $formatted_date = $date_obj->format('Y-m-d');
        
        $entity->set('field_publication_date', $formatted_date);
        
        \Drupal::logger('news_extractor')->info('Updated publication date @date (from @source) for @title', [
          '@date' => $formatted_date,
          '@source' => $source,
          '@title' => $entity->getTitle(),
        ]);
        
        return TRUE;
      }
    } catch (\Exception $e) {
      \Drupal::logger('news_extractor')->warning('Failed to parse publication date @date (from @source) for @title: @error', [
        '@date' => $date_value,
        '@source' => $source,
        '@title' => $entity->getTitle(),
        '@error' => $e->getMessage(),
      ]);
    }
  } else {
    \Drupal::logger('news_extractor')->info('No publication date found in Diffbot data and no fallback for @title', [
      '@title' => $entity->getTitle(),
    ]);
  }
  
  return FALSE;
}

/**
 * Update external image URL from Diffbot data.
 * 
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The article node entity.
 * @param array $article_data
 *   Article data from Diffbot.
 * 
 * @return bool
 *   TRUE if image was updated, FALSE otherwise.
 */
function _news_extractor_scraper_update_external_image($entity, $article_data) {
  if (!$entity->hasField('field_external_image_url')) {
    return FALSE;
  }

  // Skip if image is already set
  if (!$entity->get('field_external_image_url')->isEmpty()) {
    return FALSE;
  }

  // Look for images in Diffbot response
  if (!empty($article_data['images']) && is_array($article_data['images'])) {
    $image = $article_data['images'][0]; // Use first image
    if (!empty($image['url'])) {
      $entity->set('field_external_image_url', [
        'uri' => $image['url'],
        'title' => $entity->getTitle(),
      ]);
      
      \Drupal::logger('news_extractor')->info('Set external image URL for @title: @url', [
        '@title' => $entity->getTitle(),
        '@url' => $image['url'],
      ]);
      
      return TRUE;
    }
  }
  
  return FALSE;
}

/**
 * Store additional metadata from Diffbot response.
 * 
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The article node entity.
 * @param array $article_data
 *   Article data from Diffbot.
 */
function _news_extractor_scraper_store_metadata($entity, $article_data) {
  // Store author information if available
  if (!empty($article_data['author']) && $entity->hasField('field_author')) {
    $entity->set('field_author', $article_data['author']);
  }
  
  // Store site name if available
  if (!empty($article_data['siteName']) && $entity->hasField('field_site_name')) {
    $entity->set('field_site_name', $article_data['siteName']);
  }
  
  // Store breadcrumb if available
  if (!empty($article_data['breadcrumb']) && $entity->hasField('field_breadcrumb')) {
    $breadcrumb_text = is_array($article_data['breadcrumb']) 
      ? implode(' > ', $article_data['breadcrumb']) 
      : $article_data['breadcrumb'];
    $entity->set('field_breadcrumb', $breadcrumb_text);
  }
  
  // Store word count if available
  if (!empty($article_data['wordCount']) && $entity->hasField('field_word_count')) {
    $entity->set('field_word_count', (string) $article_data['wordCount']);
  }
  
  // Store language if available
  if (!empty($article_data['naturalLanguage']) && $entity->hasField('field_article_language')) {
    $entity->set('field_article_language', $article_data['naturalLanguage']);
  }
  
  \Drupal::logger('news_extractor')->info('Stored additional metadata for: @title', [
    '@title' => $entity->getTitle(),
  ]);
}

/**
 * Get stored Diffbot data from article node.
 * 
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The article node entity.
 * 
 * @return array|null
 *   Decoded JSON data or NULL if not available.
 */
function _news_extractor_scraper_get_stored_diffbot_data($entity) {
  if (!$entity->hasField('field_json_scraped_article_data') || 
      $entity->get('field_json_scraped_article_data')->isEmpty()) {
    return null;
  }
  
  $json_data = $entity->get('field_json_scraped_article_data')->value;
  $decoded_data = json_decode($json_data, true);
  
  if (json_last_error() !== JSON_ERROR_NONE) {
    \Drupal::logger('news_extractor')->error('Failed to decode stored JSON data for node @nid: @error', [
      '@nid' => $entity->id(),
      '@error' => json_last_error_msg(),
    ]);
    return null;
  }
  
  return $decoded_data;
}

/**
 * Check if URL is likely a news article (not podcast, video, ad, etc.).
 * Updated function name to match scraper convention.
 */
function _news_extractor_scraper_is_article_url($url) {
  $blocked_domains = [
    'comparecards.com',
    'fool.com',
    'lendingtree.com',
  ];

  foreach ($blocked_domains as $domain) {
    if (strpos($url, $domain) !== FALSE) {
      return FALSE;
    }
  }

  // Enhanced patterns for better filtering
  $skip_patterns = [
    '/\/ads?\//i' => 'advertisements',
    '/\/advertisement/i' => 'advertisement pages',
    '/\/sponsored/i' => 'sponsored content',
    '/\/podcast/i' => 'podcast pages',
    '/\/video/i' => 'video content',
    '/\/gallery/i' => 'image galleries',
    '/financial.*markets/i' => 'financial markets',
    '/stock.*price/i' => 'stock prices',
    '/\.pdf$/i' => 'PDF files',
    '/\/audio\//i' => 'audio content',
    '/\/interactive\//i' => 'interactive content',
    '/\/live-news\//i' => 'live news feeds',
    '/\/live-tv\//i' => 'live TV content',
    '/\/newsletters?\//i' => 'newsletter content',
    '/\/weather\//i' => 'weather content',
    '/\/specials\//i' => 'special content',
    '/\/coupons?\//i' => 'coupon content',
    '/\/profiles?\//i' => 'profile pages',
  ];

  foreach ($skip_patterns as $pattern => $description) {
    if (preg_match($pattern, $url)) {
      \Drupal::logger('news_extractor')->info('Skipping @description: @url', [
        '@description' => $description,
        '@url' => $url,
      ]);
      return FALSE;
    }
  }

  // Article patterns that indicate valid news content
  $article_patterns = ['/politics/', '/world/', '/us/', '/national/', '/international/', '/breaking/', '/news/'];
  foreach ($article_patterns as $pattern) {
    if (strpos($url, $pattern) !== FALSE) {
      return TRUE;
    }
  }

  // Check for common article URL patterns
  if (preg_match('/\/(index\.html?|story\.html?)$/i', $url) || preg_match('/\/\d{4}\/\d{2}\/\d{2}\//', $url)) {
    return TRUE;
  }

  return TRUE; // Default to processing if no exclusion patterns match
}

/**
 * Loop through all article nodes and update their body from Diffbot,
 * but only process articles that do not have a body set.
 */
function news_extractor_update_articles_missing_body_from_diffbot() {
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'article')
    ->accessCheck(FALSE)  // Add this line
    ->execute();

  foreach ($nids as $nid) {
    $node = Node::load($nid);
    // Only process if body is empty or not set
    if (
      $node &&
      $node->hasField('field_original_url') &&
      !$node->get('field_original_url')->isEmpty() &&
      (
        !$node->hasField('body') ||
        $node->get('body')->isEmpty() ||
        empty($node->get('body')->value)
      )
    ) {
      $original_url = $node->get('field_original_url')->uri;
      _news_extractor_extract_content($node, $original_url);
    }
  }
}

/**
 * Format the motivation analysis text - now handles basic HTML input.
 */
function news_extractor_format_motivation_analysis($text) {
  // The text is already in HTML format from AI, just clean it up
  
  // Ensure proper spacing between paragraphs
  $text = preg_replace('/<\/p>\s*<p>/', '</p><p>', $text);
  
  // Add extra spacing after key metric paragraph
  $text = preg_replace('/(<p><strong>Key metric:<\/strong>[^<]*<\/p>)\s*(<p>As a social scientist)/i', '$1<p></p>$2', $text);
  
  // Clean up any excessive spacing
  $text = preg_replace('/<p><\/p>\s*<p><\/p>/', '<p></p>', $text);
  
  // Trim whitespace
  $text = trim($text);

  return $text;
}

/**
 * Update the formatting for motivation analysis field on all existing article nodes.
 */
function news_extractor_update_motivation_analysis_formatting() {
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'article')
    ->accessCheck(FALSE)  // Add this line
    ->execute();

  $processed = 0;
  $updated = 0;

  foreach ($nids as $nid) {
    $node = Node::load($nid);
    
    if ($node && $node->hasField('field_motivation_analysis') && !$node->get('field_motivation_analysis')->isEmpty()) {
      $original_analysis = $node->get('field_motivation_analysis')->value;
      
      // Apply formatting
      $formatted_analysis = news_extractor_format_motivation_analysis($original_analysis);
      
      // Only update if the content actually changed
      if ($formatted_analysis !== $original_analysis) {
        $node->set('field_motivation_analysis', [
          'value' => $formatted_analysis,
          'format' => $node->get('field_motivation_analysis')->format,
        ]);
        $node->save();
        $updated++;
        
        \Drupal::logger('news_extractor')->info('Updated motivation analysis formatting for: @title (nid: @nid)', [
          '@title' => $node->getTitle(),
          '@nid' => $nid,
        ]);
      }
      
      $processed++;
    }
  }

  \Drupal::logger('news_extractor')->info('Motivation analysis formatting update complete. Processed: @processed, Updated: @updated', [
    '@processed' => $processed,
    '@updated' => $updated,
  ]);

  return [
    'processed' => $processed,
    'updated' => $updated,
  ];
}

/**
 * Debug function to see what's happening with formatting.
 */
function news_extractor_debug_motivation_analysis_formatting() {
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'article')
    ->accessCheck(FALSE)
    ->range(0, 3) // Just check first 3 nodes for debugging
    ->execute();

  foreach ($nids as $nid) {
    $node = Node::load($nid);
    
    if ($node && $node->hasField('field_motivation_analysis') && !$node->get('field_motivation_analysis')->isEmpty()) {
      $original_analysis = $node->get('field_motivation_analysis')->value;
      $formatted_analysis = news_extractor_format_motivation_analysis($original_analysis);
      
      echo "\n=== NODE $nid: " . $node->getTitle() . " ===\n";
      echo "ORIGINAL LENGTH: " . strlen($original_analysis) . "\n";
      echo "FORMATTED LENGTH: " . strlen($formatted_analysis) . "\n";
      echo "CONTENT CHANGED: " . ($formatted_analysis !== $original_analysis ? 'YES' : 'NO') . "\n";
      echo "ORIGINAL (first 300 chars): " . substr($original_analysis, 0, 300) . "...\n";
      echo "FORMATTED (first 300 chars): " . substr($formatted_analysis, 0, 300) . "...\n";
      echo "\n";
      
      if ($formatted_analysis !== $original_analysis) {
        echo "DIFFERENCE DETECTED - This node would be updated.\n";
      }
    }
  }
}

/**
 * Debug function to check the newest nodes for formatting issues.
 */
function news_extractor_debug_newest_motivation_analysis() {
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'article')
    ->accessCheck(FALSE)
    ->sort('created', 'DESC') // Get newest first
    ->range(0, 5) // Check last 5 nodes
    ->execute();

  foreach ($nids as $nid) {
    $node = Node::load($nid);
    
    if ($node && $node->hasField('field_motivation_analysis') && !$node->get('field_motivation_analysis')->isEmpty()) {
      $analysis = $node->get('field_motivation_analysis')->value;
      
      echo "\n=== NEWEST NODE $nid: " . $node->getTitle() . " ===\n";
      echo "CREATED: " . date('Y-m-d H:i:s', $node->getCreatedTime()) . "\n";
      echo "CONTENT LENGTH: " . strlen($analysis) . "\n";
      
      // Show more content to see the structure
      echo "FULL CONTENT:\n" . $analysis . "\n";
      echo "=" . str_repeat("=", 80) . "\n";
    }
  }
}

/**
 * Simple test function to verify the update is working.
 */
function news_extractor_test_update() {
  echo "Starting update test...\n";
  
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'article')
    ->accessCheck(FALSE)
    ->range(0, 5) // Just test 5 nodes
    ->execute();

  echo "Found " . count($nids) . " article nodes\n";

  $processed = 0;
  $updated = 0;

  foreach ($nids as $nid) {
    $node = Node::load($nid);
    echo "Checking node $nid: " . $node->getTitle() . "\n";
    
    if ($node && $node->hasField('field_motivation_analysis') && !$node->get('field_motivation_analysis')->isEmpty()) {
      $original_analysis = $node->get('field_motivation_analysis')->value;
      $formatted_analysis = news_extractor_format_motivation_analysis($original_analysis);
      
      echo "  - Has motivation analysis: " . strlen($original_analysis) . " chars\n";
      echo "  - Content changed: " . ($formatted_analysis !== $original_analysis ? 'YES' : 'NO') . "\n";
      
      // Show first part of content to see what we're working with
      echo "  - Content preview: " . substr(str_replace("\n", "\\n", $original_analysis), 0, 150) . "...\n";
      
      if ($formatted_analysis !== $original_analysis) {
        echo "  - WOULD UPDATE NODE $nid\n";
        $updated++;
      }
      
      $processed++;
    } else {
      echo "  - No motivation analysis field or empty\n";
    }
  }

  echo "Test complete. Processed: $processed, Would update: $updated\n";
}

/**
 * Format JSON structured data for human-readable display with taxonomy links.
 */
function news_extractor_format_json_analysis($structured_data) {
  if (empty($structured_data)) {
    return '<p>No analysis data available.</p>';
  }

  $html = '';

  // Entities section with taxonomy links
  if (!empty($structured_data['entities']) && is_array($structured_data['entities'])) {
    $html .= '<p><strong>Entities mentioned:</strong><br>';
    foreach ($structured_data['entities'] as $entity) {
      if (isset($entity['name']) && isset($entity['motivations'])) {
        $entity_name = $entity['name'];
        $motivations = is_array($entity['motivations']) ? $entity['motivations'] : [$entity['motivations']];
        
        // Create link for entity name
        $entity_link = _news_extractor_create_taxonomy_link($entity_name);
        
        // Create links for motivations
        $motivation_links = [];
        foreach ($motivations as $motivation) {
          if (!empty(trim($motivation))) {
            $motivation_links[] = _news_extractor_create_taxonomy_link($motivation);
          }
        }
        
        $motivation_string = implode(', ', $motivation_links);
        $html .= "- {$entity_link}: {$motivation_string}<br>";
      }
    }
    $html .= '</p>';
  }

  // Assessment scores section
  $html .= '<p><strong>Article Assessment:</strong><br>';
  
  if (isset($structured_data['credibility_score'])) {
    $html .= "Credibility Score: {$structured_data['credibility_score']}/100<br>";
  }
  
  if (isset($structured_data['bias_rating'])) {
    $bias_label = '';
    $bias_score = $structured_data['bias_rating'];
    if ($bias_score <= 20) $bias_label = 'Extreme Left';
    elseif ($bias_score <= 40) $bias_label = 'Lean Left';
    elseif ($bias_score <= 60) $bias_label = 'Center';
    elseif ($bias_score <= 80) $bias_label = 'Lean Right';
    else $bias_label = 'Extreme Right';
    
    $html .= "Bias Rating: {$bias_score}/100 ({$bias_label})<br>";
  }
  
  if (isset($structured_data['sentiment_score'])) {
    $html .= "Sentiment Score: {$structured_data['sentiment_score']}/100<br>";
  }
  
  $html .= '</p>';

  // Bias analysis
  if (!empty($structured_data['bias_analysis'])) {
    $html .= '<p><strong>Bias Analysis:</strong><br>' . $structured_data['bias_analysis'] . '</p>';
  }

  // Key metric section with taxonomy link
  if (!empty($structured_data['metrics']) && is_array($structured_data['metrics'])) {
    $metric = $structured_data['metrics'][0]; // Take first metric
    $metric_link = _news_extractor_create_taxonomy_link($metric);
    $html .= "<p><strong>Key metric:</strong> {$metric_link}</p>";
  }

  // Analysis section
  if (!empty($structured_data['analysis'])) {
    $html .= '<p></p>'; // Spacing
    $html .= '<p>' . $structured_data['analysis'] . '</p>';
  }

  // Fallback if no structured content
  if (empty($html)) {
    $html = '<p>Analysis data processed but no structured content available.</p>';
  }

  return $html;
}

/**
 * Create a taxonomy link for a given term name.
 */
function _news_extractor_create_taxonomy_link($term_name) {
  if (empty(trim($term_name))) {
    return $term_name;
  }
  
  // Clean the term name
  $clean_name = trim($term_name);
  
  // Try to find existing taxonomy term
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $terms = $term_storage->loadByProperties([
    'name' => $clean_name,
    'vid' => 'tags', // Assuming 'tags' vocabulary
  ]);
  
  if (!empty($terms)) {
    // Term exists, create link
    $term = reset($terms);
    $term_id = $term->id();
    $term_url = \Drupal\Core\Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $term_id]);
    $link = \Drupal\Core\Link::fromTextAndUrl($clean_name, $term_url)->toString();
    return $link;
  } else {
    // Term doesn't exist, return plain text
    return $clean_name;
  }
}

// Add this function to reprocess from stored ai_raw_response:

/**
 * Reprocess a single node's fields from stored ai_raw_response data.
 */
function news_extractor_reprocess_node_from_raw_response($nid) {
  $node = Node::load($nid);
  
  if (!$node || $node->bundle() !== 'article') {
    return FALSE;
  }
  
  // Get stored raw AI response
  if (!$node->hasField('field_ai_raw_response') || $node->get('field_ai_raw_response')->isEmpty()) {
    \Drupal::logger('news_extractor')->warning('No raw AI response found for node @nid', ['@nid' => $nid]);
    return FALSE;
  }
  
  $ai_raw_response = $node->get('field_ai_raw_response')->value;
  
  // Extract structured data from raw response
  $structured_data = _news_extractor_extract_structured_data($ai_raw_response);
  
  // Update all assessment fields from structured data
  if (isset($structured_data['credibility_score']) && $node->hasField('field_credibility_score')) {
    $node->set('field_credibility_score', (string) $structured_data['credibility_score']);
  }
  
  if (isset($structured_data['bias_rating']) && $node->hasField('field_bias_rating')) {
    $node->set('field_bias_rating', (string) $structured_data['bias_rating']);
  }
  
  if (isset($structured_data['bias_analysis']) && $node->hasField('field_bias_analysis')) {
    $node->set('field_bias_analysis', $structured_data['bias_analysis']);
  }
  
  if (isset($structured_data['sentiment_score']) && $node->hasField('field_article_sentiment_score')) {
    $node->set('field_article_sentiment_score', (string) $structured_data['sentiment_score']);
  }
  
  // Update motivation data field
  if ($node->hasField('field_motivation_data')) {
    $node->set('field_motivation_data', json_encode($structured_data));
  }
  
  // Regenerate tags FIRST (before creating links)
  if (!empty($structured_data)) {
    $simple_tags = [];
    
    if (isset($structured_data['entities']) && is_array($structured_data['entities'])) {
      foreach ($structured_data['entities'] as $entity_data) {
        if (isset($entity_data['name'])) {
          $simple_tags[] = $entity_data['name'];
        }
        if (isset($entity_data['motivations']) && is_array($entity_data['motivations'])) {
          foreach ($entity_data['motivations'] as $motivation) {
            $simple_tags[] = $motivation;
          }
        }
      }
    }
    
    if (isset($structured_data['metrics']) && is_array($structured_data['metrics'])) {
      $simple_tags = array_merge($simple_tags, $structured_data['metrics']);
    }
    
    $tag_ids = [];
    foreach (array_unique($simple_tags) as $tag) {
      if (!empty(trim($tag))) {
        $tid = _news_extractor_get_or_create_tag($tag);
        if ($tid) {
          $tag_ids[] = $tid;
        }
      }
    }
    
    if (!empty($tag_ids)) {
      $node->set('field_tags', $tag_ids);
    }
  }
  
  // Save node FIRST to ensure taxonomy terms exist
  $node->save();
  
  // NOW regenerate formatted analysis with taxonomy links
  $motivation_analysis = news_extractor_format_json_analysis($structured_data);
  $node->set('field_motivation_analysis', [
    'value' => $motivation_analysis,
    'format' => 'basic_html',
  ]);
  
  // Save again with linked analysis
  $node->save();
  
  \Drupal::logger('news_extractor')->info('Reprocessed node @nid from raw AI response with taxonomy links', ['@nid' => $nid]);
  return TRUE;
}

// Add this function for bulk reprocessing:

/**
 * Reprocess all nodes that have raw AI responses but may need field updates.
 */
function news_extractor_bulk_reprocess_from_raw_responses($limit = 50) {
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'article')
    ->condition('field_ai_raw_response.value', '', '<>')
    ->accessCheck(FALSE)
    ->range(0, $limit)
    ->execute();
    
  $processed = 0;
  $updated = 0;
  
  foreach ($nids as $nid) {
    if (news_extractor_reprocess_node_from_raw_response($nid)) {
      $updated++;
    }
    $processed++;
  }
  
  \Drupal::logger('news_extractor')->info('Bulk reprocessing complete: @processed processed, @updated updated', [
    '@processed' => $processed,
    '@updated' => $updated,
  ]);
  
  return ['processed' => $processed, 'updated' => $updated];
}

/**
 * Post-process article node to fetch and set external image URL from stored Diffbot data.
 * Uses stored JSON data instead of making new API call.
 * 
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The article node entity.
 * 
 * @return bool
 *   TRUE if image was processed, FALSE otherwise.
 */
function _news_extractor_scraper_postprocess_article_image($entity) {
  // Only process article nodes with an original URL
  if ($entity->bundle() !== 'article' || !$entity->hasField('field_original_url')) {
    return FALSE;
  }

  $original_url = $entity->get('field_original_url')->uri ?? '';
  if (empty($original_url)) {
    return FALSE;
  }

  // Check if image is already set
  if ($entity->hasField('field_external_image_url') && !$entity->get('field_external_image_url')->isEmpty()) {
    \Drupal::logger('news_extractor')->info('Image URL already set for node @nid, skipping image processing', [
      '@nid' => $entity->id(),
    ]);
    return FALSE;
  }

  // Try to get image from stored JSON data first
  $stored_data = _news_extractor_scraper_get_stored_diffbot_data($entity);
  if ($stored_data && isset($stored_data['objects'][0]['images'])) {
    $article_data = $stored_data['objects'][0];
    if (_news_extractor_scraper_update_external_image($entity, $article_data)) {
      $entity->save();
      \Drupal::logger('news_extractor')->info('Set external image URL from stored data for node @nid', [
        '@nid' => $entity->id(),
      ]);
      return TRUE;
    }
  }

  // Fallback: Make fresh Diffbot API call if no stored data or no images
  \Drupal::logger('news_extractor')->info('No stored image data found, making fresh Diffbot API call for node @nid', [
    '@nid' => $entity->id(),
  ]);

  $diffbot_token = \Drupal::config('news_extractor.settings')->get('diffbot_token');
  if (empty($diffbot_token)) {
    \Drupal::logger('news_extractor')->error('Diffbot token not set in configuration for image processing.');
    return FALSE;
  }

  try {
    // Make rate-limited API call
    $diffbot_response = _news_extractor_scraper_get_diffbot_data($original_url, $diffbot_token);
    
    if ($diffbot_response && isset($diffbot_response['objects'][0]['images'])) {
      $article_data = $diffbot_response['objects'][0];
      
      // Store the complete response if not already stored
      if ($entity->hasField('field_json_scraped_article_data') && $entity->get('field_json_scraped_article_data')->isEmpty()) {
        $entity->set('field_json_scraped_article_data', json_encode($diffbot_response, JSON_PRETTY_PRINT));
        \Drupal::logger('news_extractor')->info('Stored Diffbot JSON data during image processing for node @nid', [
          '@nid' => $entity->id(),
        ]);
      }
      
      // Update image
      if (_news_extractor_scraper_update_external_image($entity, $article_data)) {
        $entity->save();
        \Drupal::logger('news_extractor')->info('Set external image URL from fresh API call for node @nid', [
          '@nid' => $entity->id(),
        ]);
        return TRUE;
      }
    }
  } catch (\Exception $e) {
    \Drupal::logger('news_extractor')->error('Error fetching Diffbot image for node @nid: @msg', [
      '@nid' => $entity->id(),
      '@msg' => $e->getMessage(),
    ]);
  }

  return FALSE;
}

/**
 * Bulk process article images using stored or fresh Diffbot data.
 * 
 * @param int $limit
 *   Maximum number of articles to process.
 * 
 * @return array
 *   Processing statistics.
 */
function news_extractor_bulk_process_article_images($limit = 20) {
  $stats = [
    'processed' => 0,
    'updated_images' => 0,
    'used_stored_data' => 0,
    'made_api_calls' => 0,
    'failed' => 0,
    'skipped' => 0,
  ];

  echo "=== BULK PROCESSING ARTICLE IMAGES ===\n";

  // Find articles missing images but with original URLs
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'article')
    ->condition('field_original_url.uri', '', '<>')
    ->accessCheck(FALSE)
    ->range(0, $limit);

  // Only process articles without images
  $or_group = $query->orConditionGroup();
  $or_group->condition('field_external_image_url.uri', '', '=');
  $or_group->condition('field_external_image_url.uri', NULL, 'IS NULL');
  $query->condition($or_group);

  $nids = $query->execute();

  if (empty($nids)) {
    echo "No articles found needing image processing\n";
    return $stats;
  }

  echo "Found " . count($nids) . " articles for image processing\n\n";

  foreach ($nids as $nid) {
    try {
      $node = \Drupal\node\Entity\Node::load($nid);
      if (!$node) {
        $stats['skipped']++;
        continue;
      }

      echo "Processing Node $nid: " . $node->getTitle() . "\n";

      // Check if stored data has images
      $stored_data = _news_extractor_scraper_get_stored_diffbot_data($node);
      $used_stored = FALSE;
      
      if ($stored_data && isset($stored_data['objects'][0]['images'])) {
        $article_data = $stored_data['objects'][0];
        if (_news_extractor_scraper_update_external_image($node, $article_data)) {
          $node->save();
          $stats['updated_images']++;
          $stats['used_stored_data']++;
          $used_stored = TRUE;
          echo "  ✓ Updated image from stored data\n";
        }
      }

      if (!$used_stored) {
        // Make fresh API call with rate limiting
        $original_url = $node->get('field_original_url')->uri;
        $diffbot_token = \Drupal::config('news_extractor.settings')->get('diffbot_token');
        
        if (!empty($diffbot_token)) {
          echo "  → Making fresh Diffbot API call (rate limited)...\n";
          $diffbot_response = _news_extractor_scraper_get_diffbot_data($original_url, $diffbot_token);
          
          if ($diffbot_response && isset($diffbot_response['objects'][0]['images'])) {
            $article_data = $diffbot_response['objects'][0];
            
            // Store JSON data if not already stored
            if ($node->hasField('field_json_scraped_article_data') && $node->get('field_json_scraped_article_data')->isEmpty()) {
              $node->set('field_json_scraped_article_data', json_encode($diffbot_response, JSON_PRETTY_PRINT));
            }
            
            if (_news_extractor_scraper_update_external_image($node, $article_data)) {
              $node->save();
              $stats['updated_images']++;
              echo "  ✓ Updated image from fresh API call\n";
            }
            
            $stats['made_api_calls']++;
            
            // Rate limiting - wait 13 seconds after API call
            if (count($nids) > 1) {
              echo "  → Waiting 13 seconds for rate limiting...\n";
              sleep(13);
            }
          } else {
            echo "  ✗ No images found in Diffbot response\n";
          }
        } else {
          echo "  ✗ No Diffbot token configured\n";
          $stats['failed']++;
        }
      }

      $stats['processed']++;

    } catch (\Exception $e) {
      $stats['failed']++;
      echo "  ✗ Error processing node $nid: " . $e->getMessage() . "\n";
    }
  }

  // Display results
  echo "\n=== IMAGE PROCESSING COMPLETE ===\n";
  foreach ($stats as $key => $value) {
    echo "  {$key}: {$value}\n";
  }

  return $stats;
}

/**
 * Debug function to display stored Diffbot data.
 * 
 * @param int $nid
 *   Node ID to debug.
 */
function news_extractor_debug_stored_diffbot_data($nid) {
  $node = \Drupal\node\Entity\Node::load($nid);
  if (!$node) {
    echo "Node $nid not found.\n";
    return;
  }
  
  echo "=== STORED DIFFBOT DATA FOR NODE $nid ===\n";
  echo "Title: " . $node->getTitle() . "\n";
  
  $stored_data = _news_extractor_scraper_get_stored_diffbot_data($node);
  if ($stored_data) {
    echo "JSON Data Size: " . strlen(json_encode($stored_data)) . " chars\n";
    echo "Response Keys: " . implode(', ', array_keys($stored_data)) . "\n";
    
    if (isset($stored_data['objects'][0])) {
      $article = $stored_data['objects'][0];
      echo "Article Object Keys: " . implode(', ', array_keys($article)) . "\n";
      echo "Text Length: " . strlen($article['text'] ?? '') . " chars\n";
      echo "Author: " . ($article['author'] ?? 'Not available') . "\n";
      echo "Site Name: " . ($article['siteName'] ?? 'Not available') . "\n";
      echo "Word Count: " . ($article['wordCount'] ?? 'Not available') . "\n";
      echo "Publication Date: " . ($article['date'] ?? 'Not available') . "\n";
      echo "Images Count: " . count($article['images'] ?? []) . "\n";
      
      if (!empty($article['images'])) {
        echo "First Image URL: " . ($article['images'][0]['url'] ?? 'N/A') . "\n";
      }
    }
  } else {
    echo "No stored Diffbot data found.\n";
  }
  
  echo "=== END DEBUG DATA ===\n";
}

// Add this function after the existing functions:

/**
 * Bulk populate publication dates from stored Diffbot JSON data with creation date fallback.
 * 
 * @param int $limit
 *   Maximum number of articles to process.
 * @param bool $use_creation_fallback
 *   Whether to use node creation date as fallback.
 * 
 * @return array
 *   Processing statistics.
 */
function news_extractor_bulk_populate_publication_dates($limit = 50, $use_creation_fallback = TRUE) {
  $stats = [
    'processed' => 0,
    'updated_dates' => 0,
    'from_diffbot_data' => 0,
    'from_creation_date' => 0,
    'had_stored_data' => 0,
    'already_had_dates' => 0,
    'no_date_found' => 0,
    'errors' => 0,
  ];

  echo "=== BULK POPULATING PUBLICATION DATES ===\n";
  echo "Creation date fallback: " . ($use_creation_fallback ? 'ENABLED' : 'DISABLED') . "\n\n";

  // Find articles missing publication dates but with stored JSON data
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'article')
    ->accessCheck(FALSE)
    ->range(0, $limit);

  // Only process articles without publication dates
  $or_group = $query->orConditionGroup();
  $or_group->condition('field_publication_date.value', '', '=');
  $or_group->condition('field_publication_date.value', NULL, 'IS NULL');
  $query->condition($or_group);

  $nids = $query->execute();

  if (empty($nids)) {
    echo "No articles found needing publication date population\n";
    return $stats;
  }

  echo "Found " . count($nids) . " articles for publication date processing\n\n";

  foreach ($nids as $nid) {
    try {
      $node = \Drupal\node\Entity\Node::load($nid);
      if (!$node) {
        continue;
      }

      echo "Processing Node $nid: " . substr($node->getTitle(), 0, 60) . "...\n";

      // Check if already has publication date
      if ($node->hasField('field_publication_date') && !$node->get('field_publication_date')->isEmpty()) {
        echo "  → Already has publication date, skipping\n";
        $stats['already_had_dates']++;
        $stats['processed']++;
        continue;
      }

      $date_source = null;
      $updated = FALSE;

      // Try to get date from stored Diffbot data first
      $stored_data = _news_extractor_scraper_get_stored_diffbot_data($node);
      if ($stored_data && isset($stored_data['objects'][0])) {
        $stats['had_stored_data']++;
        $article_data = $stored_data['objects'][0];
        
        // Check what date fields are available
        $diffbot_date_fields = ['date', 'estimatedDate', 'publishedAt', 'created'];
        $found_diffbot_date = FALSE;
        
        foreach ($diffbot_date_fields as $field) {
          if (!empty($article_data[$field])) {
            $found_diffbot_date = TRUE;
            $date_source = "diffbot_$field";
            break;
          }
        }
        
        // Try to update publication date
        if (_news_extractor_scraper_update_publication_date($node, $article_data, $use_creation_fallback)) {
          $node->save();
          $stats['updated_dates']++;
          $updated = TRUE;
          
          if ($found_diffbot_date) {
            $stats['from_diffbot_data']++;
            echo "  ✓ Updated publication date from Diffbot data ($date_source)\n";
          } else {
            $stats['from_creation_date']++;
            echo "  ✓ Updated publication date from node creation date (fallback)\n";
          }
        } else {
          $stats['no_date_found']++;
          echo "  ✗ No valid date found in stored data or fallback\n";
        }
      } else {
        // No stored data, try creation date fallback if enabled
        if ($use_creation_fallback) {
          $creation_timestamp = $node->getCreatedTime();
          $creation_date = date('Y-m-d', $creation_timestamp);
          
          $node->set('field_publication_date', $creation_date);
          $node->save();
          
          $stats['updated_dates']++;
          $stats['from_creation_date']++;
          $updated = TRUE;
          echo "  ✓ Updated publication date from creation date (no stored data)\n";
        } else {
          echo "  ✗ No stored Diffbot data and fallback disabled\n";
        }
      }

      if (!$updated) {
        $stats['no_date_found']++;
      }

      $stats['processed']++;

    } catch (\Exception $e) {
      $stats['errors']++;
      echo "  ✗ Error processing node $nid: " . $e->getMessage() . "\n";
    }
  }

  // Display results
  echo "\n=== PUBLICATION DATE PROCESSING COMPLETE ===\n";
  foreach ($stats as $key => $value) {
    echo "  {$key}: {$value}\n";
  }

  // Show breakdown
  echo "\nDate Source Breakdown:\n";
  echo "  From Diffbot data: {$stats['from_diffbot_data']}\n";
  echo "  From creation date fallback: {$stats['from_creation_date']}\n";
  echo "  Success rate: " . round(($stats['updated_dates'] / max($stats['processed'], 1)) * 100, 1) . "%\n";

  return $stats;
}

/**
 * Debug publication dates in stored Diffbot data.
 * 
 * @param int $limit
 *   Number of nodes to check.
 */
function news_extractor_debug_publication_dates($limit = 10) {
  echo "=== DEBUGGING PUBLICATION DATES IN STORED DATA ===\n\n";

  $query = \Drupal::entityQuery('node')
    ->condition('type', 'article')
    ->condition('field_json_scraped_article_data.value', '', '<>')
    ->accessCheck(FALSE)
    ->sort('created', 'DESC')
    ->range(0, $limit);

  $nids = $query->execute();

  foreach ($nids as $nid) {
    $node = \Drupal\node\Entity\Node::load($nid);
    if (!$node) continue;

    echo "Node $nid: " . substr($node->getTitle(), 0, 50) . "...\n";
    
    // Check current publication date
    $current_date = '';
    if ($node->hasField('field_publication_date') && !$node->get('field_publication_date')->isEmpty()) {
      $current_date = $node->get('field_publication_date')->value;
      echo "  Current pub date: $current_date\n";
    } else {
      echo "  Current pub date: NOT SET\n";
    }

    // Check stored Diffbot data
    $stored_data = _news_extractor_scraper_get_stored_diffbot_data($node);
    if ($stored_data && isset($stored_data['objects'][0])) {
      $article = $stored_data['objects'][0];
      
      echo "  Available date fields:\n";
      $date_fields = ['date', 'estimatedDate', 'publishedAt', 'created'];
      foreach ($date_fields as $field) {
        if (!empty($article[$field])) {
          echo "    $field: " . $article[$field] . "\n";
        }
      }
      
      // FIXED LINE - Added parentheses around $article
      if (empty(array_filter($date_fields, function($field) use ($article) {
        return !empty($article[$field]);
      }))) {
        echo "    No date fields found in stored data\n";
      }
    } else {
      echo "  No stored Diffbot data found\n";
    }
    
    echo "\n";
  }
}

/**
 * Test publication date parsing with a specific node.
 * 
 * @param int $nid
 *   Node ID to test.
 */
function news_extractor_test_publication_date_parsing($nid) {
  $node = \Drupal\node\Entity\Node::load($nid);
  if (!$node) {
    echo "Node $nid not found.\n";
    return;
  }

  echo "=== TESTING PUBLICATION DATE PARSING FOR NODE $nid ===\n";
  echo "Title: " . $node->getTitle() . "\n\n";
  $stored_data = _news_extractor_scraper_get_stored_diffbot_data($node);
  if (!$stored_data || !isset($stored_data['objects'][0])) {
    echo "No stored Diffbot data found.\n";
    return;
  }

  $article_data = $stored_data['objects'][0];
  
  echo "Testing date field parsing:\n";
  
  $date_fields = ['date', 'estimatedDate', 'publishedAt', 'created'];
  foreach ($date_fields as $field) {
    if (!empty($article_data[$field])) {
      $date_value = $article_data[$field];
      echo "\n$field: $date_value\n";
      
      try {
        $date_obj = null;
        
        if (is_numeric($date_value)) {
          $date_obj = new \DateTime();
          $date_obj->setTimestamp($date_value);
          echo "  → Parsed as timestamp: " . $date_obj->format('Y-m-d H:i:s') . "\n";
        } else {
          $date_obj = new \DateTime($date_value);
          echo "  → Parsed as date string: " . $date_obj->format('Y-m-d H:i:s') . "\n";
        }
        
        if ($date_obj) {
          $formatted_date = $date_obj->format('Y-m-d');
          echo "  → Formatted date: $formatted_date\n";
        }
      } catch (\Exception $e) {
        echo "  ✗ Error parsing date: " . $e->getMessage() . "\n";
      }
    }
  }
}