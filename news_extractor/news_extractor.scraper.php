<?php
require_once __DIR__ . '/news_extractor.module';

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
        // Format the Motivation Analysis for readability
        $motivation_analysis = news_extractor_format_motivation_analysis($ai_summary);
        $entity->set('field_motivation_analysis', [
          'value' => $motivation_analysis,
          'format' => 'basic_html',
        ]);

        // Extract and store structured data
        if ($entity->hasField('field_motivation_data')) {
          $structured_data = _news_extractor_extract_structured_data($motivation_analysis);
          $entity->set('field_motivation_data', json_encode($structured_data));
        }

        // Create simple tags for browsing
        $simple_tags = [];
        foreach ($structured_data['entities'] as $entity_data) {
          $simple_tags[] = $entity_data['name']; // Entity tag
          foreach ($entity_data['motivations'] as $motivation) {
            $simple_tags[] = $motivation; // Motivation tag
          }
        }
        $simple_tags = array_merge($simple_tags, $structured_data['metrics']);

        // Create taxonomy terms
        $tag_ids = [];
        foreach (array_unique($simple_tags) as $tag) {
          $tid = _news_extractor_get_or_create_tag($tag);
          if ($tid) $tag_ids[] = $tid;
        }

        if (!empty($tag_ids)) {
          $entity->set('field_tags', $tag_ids);
        }

        $entity->save();
        \Drupal::logger('news_extractor')->info('Generated Motivation Analysis for: @title', [
          '@title' => $entity->getTitle(),
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
 * Format the motivation analysis text with proper line breaks.
 */
function news_extractor_format_motivation_analysis($text) {
  // Add a line break before and after "Entities mentioned:" and its list
  $text = preg_replace('/(Entities mentioned:)(.*?)(Motivations:)/s', "\n$1$2\n\n$3", $text);

  // Add a line break after "Motivations:" and its list
  $text = preg_replace('/(Motivations:)(.*?)(Key metric:)/s', "\n$1$2\n\n$3", $text);

  // Add a couple lines after "Key metric:" line
  $text = preg_replace('/(Key metric:.*?)(\n|$)/', "\n$1\n\n\n", $text);

  return $text;
}

/**
 * Extract structured motivation data from AI summary.
 */
function _news_extractor_extract_structured_data($motivation_analysis) {
  $data = [
    'entities' => [],
    'motivations' => [],
    'metrics' => []
  ];

  // Extract entities and their motivations
  if (preg_match_all('/- (.+?):\s*(.+?)(?=\n-|\nMotivations:|\nKey metric:|$)/s', $motivation_analysis, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
      $entity = trim($match[1]);
      $motivations_text = trim($match[2]);
      $motivations = array_map('trim', explode(',', $motivations_text));
      
      $data['entities'][] = [
        'name' => $entity,
        'motivations' => $motivations
      ];
      
      // Also collect unique motivations
      foreach ($motivations as $motivation) {
        if (!empty($motivation) && !in_array($motivation, $data['motivations'])) {
          $data['motivations'][] = $motivation;
        }
      }
    }
  }

  // Extract general motivations section (if different format)
  if (preg_match('/Motivations:(.*?)(?:Key metric:|$)/is', $motivation_analysis, $matches)) {
    preg_match_all('/- (.+)/', $matches[1], $motivation_matches);
    foreach ($motivation_matches[1] as $motivation) {
      $motivation = trim($motivation);
      if (!empty($motivation) && !in_array($motivation, $data['motivations'])) {
        $data['motivations'][] = $motivation;
      }
    }
  }

  // Extract key metric
  if (preg_match('/Key metric:\s*(.+)/i', $motivation_analysis, $matches)) {
    $data['metrics'][] = trim($matches[1]);
  }

  return $data;
}

/**
 * Format structured motivation data for display.
 */
function news_extractor_format_structured_display($json_data) {
  $data = json_decode($json_data, TRUE);
  if (!$data) return '';

  $output = '';

  // Entities section
  if (!empty($data['entities'])) {
    $output .= "\nEntities mentioned:\n";
    foreach ($data['entities'] as $entity) {
      $motivations = implode(', ', $entity['motivations']);
      $output .= "- {$entity['name']}: {$motivations}\n";
    }
  }

  // General motivations (if any not tied to entities)
  if (!empty($data['motivations'])) {
    $output .= "\nMotivations:\n";
    foreach ($data['motivations'] as $motivation) {
      $output .= "- {$motivation}\n";
    }
  }

  // Key metrics
  if (!empty($data['metrics'])) {
    $output .= "\nKey metric: " . implode(', ', $data['metrics']) . "\n\n";
  }

  return $output;
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

