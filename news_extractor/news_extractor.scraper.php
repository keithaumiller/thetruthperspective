<?php

use Drupal\Core\Entity\EntityInterface;

/**
 * Extract full article content using Diffbot API.
 */
function _news_extractor_extract_content(EntityInterface $entity, $url) {
  $api_token = '8488710a556cedc9ff2ad6547bbbecaf';
  $api_url = 'https://api.diffbot.com/v3/article';
  $request_url = $api_url . '?' . http_build_query([
    'token' => $api_token,
    'url' => $url,
    'paging' => 'false',
  ]);

  try {
    $response = \Drupal::httpClient()->get($request_url, [
      'timeout' => 30,
      'headers' => ['Accept' => 'application/json'],
    ]);
    $data = json_decode($response->getBody()->getContents(), TRUE);

    if (isset($data['objects'][0])) {
      _news_extractor_update_article($entity, $data['objects'][0]);
      \Drupal::logger('news_extractor')->info('Successfully extracted content for article: @title', [
        '@title' => $entity->getTitle(),
      ]);
    } else {
      \Drupal::logger('news_extractor')->warning('No article data returned from Diffbot for URL: @url', [
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