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
  $api_url = 'https://api.diffbot.com/v3/article';
  $request_url = $api_url . '?' . http_build_query([
    'token' => $api_token,
    'url' => $url,
    'naturalLanguage' => 'summary',
  ]);

  try {
    $response = \Drupal::httpClient()->get($request_url, [
      'timeout' => 30,
      'headers' => ['Accept' => 'application/json'],
    ]);
    $data = json_decode($response->getBody()->getContents(), TRUE);

    if (isset($data['objects'][0]['text'])) {
      // --- Update article with extracted content ---
      _news_extractor_update_article($entity, $data['objects'][0]);

      // --- Generate Motivation Analysis ---
      $ai_summary = _news_extractor_generate_ai_summary($data['objects'][0]['text'], $entity->getTitle());
      if ($ai_summary && $entity->hasField('field_motivation_analysis')) {
        
        // Store the RAW AI response in new dedicated field
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
        
        // Store individual assessment fields
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
        
        // Create simple tags for browsing (entities + motivations + metrics)
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

          // Create taxonomy terms
          $tag_ids = [];
          foreach (array_unique($simple_tags) as $tag) {
            if (!empty(trim($tag))) {
              $tid = _news_extractor_get_or_create_tag($tag);
              if ($tid) $tag_ids[] = $tid;
            }
          }

          if (!empty($tag_ids)) {
            $entity->set('field_tags', $tag_ids);
          }
        }

        // Format the analysis for human-readable display
        $motivation_analysis = news_extractor_format_json_analysis($structured_data);
        $entity->set('field_motivation_analysis', [
          'value' => $motivation_analysis,
          'format' => 'basic_html',
        ]);

        $entity->save();
        
        // Enhanced logging to track what's stored where
        \Drupal::logger('news_extractor')->info('AI data stored for @title: Raw response (@raw_len chars), Structured data (@struct_items items), Formatted analysis (@format_len chars)', [
          '@title' => $entity->getTitle(),
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
 * Check if URL is likely a news article (not podcast, video, ad, etc.).
 */
function _news_extractor_is_article_url($url) {
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

  $skip_patterns = [
    '/audio/', '/video/', '/podcast/', '/gallery/', '/interactive/', '/live-news/', '/live-tv/',
    '/newsletters/', '/sponsored/', '/advertisement/', '/ads/', '/promo/', '/newsletter/',
    '/weather/', '/specials/', '/cnn-underscored/', '/coupons/', '/profiles/',
  ];

  foreach ($skip_patterns as $pattern) {
    if (strpos($url, $pattern) !== FALSE) {
      return FALSE;
    }
  }

  $ad_keywords = ['sponsored', 'advertisement', 'promo', 'ad-', '-ad', 'coupon'];
  $url_lower = strtolower($url);

  foreach ($ad_keywords as $keyword) {
    if (strpos($url_lower, $keyword) !== FALSE) {
      return FALSE;
    }
  }

  $article_patterns = ['/politics/', '/world/', '/us/', '/national/', '/international/', '/breaking/', '/news/'];
  foreach ($article_patterns as $pattern) {
    if (strpos($url, $pattern) !== FALSE) {
      return TRUE;
    }
  }

  if (preg_match('/\/(index\.html?|story\.html?)$/i', $url) || preg_match('/\/\d{4}\/\d{2}\/\d{2}\//', $url)) {
    return TRUE;
  }

  return TRUE;
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
 * Format JSON structured data for human-readable display - Enhanced with assessments.
 */
function news_extractor_format_json_analysis($structured_data) {
  if (empty($structured_data)) {
    return '<p>No analysis data available.</p>';
  }

  $html = '';

  // Entities section
  if (!empty($structured_data['entities']) && is_array($structured_data['entities'])) {
    $html .= '<p><strong>Entities mentioned:</strong><br>';
    foreach ($structured_data['entities'] as $entity) {
      if (isset($entity['name']) && isset($entity['motivations'])) {
        $name = $entity['name'];
        $motivations = is_array($entity['motivations']) ? implode(', ', $entity['motivations']) : $entity['motivations'];
        $html .= "- {$name}: {$motivations}<br>";
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

  // Key metric section
  if (!empty($structured_data['metrics']) && is_array($structured_data['metrics'])) {
    $metric = $structured_data['metrics'][0]; // Take first metric
    $html .= "<p><strong>Key metric:</strong> {$metric}</p>";
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

