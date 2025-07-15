<?php

use Drupal\Core\Entity\EntityInterface;
use GuzzleHttp\Client;
use Drupal\node\Entity\Node;

/**
 * Extract full article content using Diffbot API.
 */
function _news_extractor_extract_content(EntityInterface $entity, $url) {
  $api_token = '8488710a556cedc9ff2ad6547bbbecaf';
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

    // Store the full response for debugging
    _news_extractor_store_debug_diffbot_response($data);

    if (isset($data['objects'][0]['text'])) {
      $entity->set('body', [
        'value' => $data['objects'][0]['text'],
        'format' => 'basic_html',
      ]);
      $entity->save();
      \Drupal::logger('news_extractor')->info('Updated article body from Diffbot for: @title', [
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
 * Scrape headlines from a section page using Diffbot Analyze API.
 *
 * @param string $url The section URL (e.g., https://www.cnn.com/politics)
 * @return array Array of headlines with title, summary, and link.
 */
function _news_extractor_scrape_headlines($url) {
  $api_token = '8488710a556cedc9ff2ad6547bbbecaf';
  $client = new Client();

  try {
    $response = $client->request('GET', 'https://api.diffbot.com/v3/analyze', [
      'query' => [
        'token' => $api_token,
        'url' => $url,
      ],
      'headers' => [
        'accept' => 'application/json',
      ],
      'timeout' => 30,
    ]);
    $data = json_decode($response->getBody(), TRUE);

    $headlines = [];
    if (!empty($data['objects'][0]['items'])) {
      foreach ($data['objects'][0]['items'] as $item) {
        $headlines[] = [
          'title' => $item['title'] ?? $item['container_list-headlines__text'] ?? '',
          'summary' => $item['summary'] ?? '',
          'link' => $item['link'] ?? $item['container__link--type-article'] ?? '',
        ];
      }
    }
    return $headlines;

  } catch (\Exception $e) {
    \Drupal::logger('news_extractor')->error('Error scraping headlines: @message', [
      '@message' => $e->getMessage(),
    ]);
    return [];
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
 * Store the full Diffbot response in a new article node for debugging.
 *
 * @param array $diffbot_response The full response array from Diffbot.
 */
function _news_extractor_store_debug_diffbot_response(array $diffbot_response) {
  // Encode the response as pretty JSON for readability.
  $body = json_encode($diffbot_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

  // Commented out: Create a new article node for debugging.
  /*
  $node = Node::create([
    'type' => 'article',
    'title' => 'debugdiffbot',
    'body' => [
      'value' => $body,
      'format' => 'basic_html',
    ],
  ]);
  $node->save();

  \Drupal::logger('news_extractor')->info('Stored debugdiffbot article with full Diffbot response.');
  */
}

/**
 * Loop through all article nodes and update their body from Diffbot,
 * but only process articles that do not have a body set.
 */
function news_extractor_update_articles_missing_body_from_diffbot() {
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'article')
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

