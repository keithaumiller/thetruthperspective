<?php
require_once __DIR__ . '/news_extractor.scraper.php';

/**
 * @file
 * Legacy functions for news_extractor module.
 * Contains helper functions preserved for backward compatibility.
 */

use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityInterface;

/**
 * LEGACY: Implements hook_feeds_process_alter().
 * This function has been moved to the main module file.
 * Keeping here for reference only - DO NOT USE.
 */
function _news_extractor_legacy_feeds_process_alter(&$feed, $item, $entity_interface) {
  // Debug: Log module version on every feed import
  \Drupal::logger('news_extractor')->info('news_extractor module version: 1.0.0 - feeds_process_alter triggered');

  // Only process Article content type
  if ($entity_interface->getTarget()->bundle() !== 'article') {
    return;
  }

  $title_to_check = $item['title'] ?? '';
  $link_to_check = $item['link'] ?? '';

  // Skip items with blocked strings
  if (_news_extractor_has_blocked_content($title_to_check, $link_to_check)) {
    $feed->skipItem($item);
    return;
  }

  // Skip items with invalid titles
  if (_news_extractor_has_invalid_title($title_to_check)) {
    $feed->skipItem($item);
    return;
  }

  // Skip non-article URLs
  if (!empty($item['link']) && !_news_extractor_scraper_is_article_url($item['link'])) {
    \Drupal::logger('news_extractor')->info('Skipping non-article URL: @url', [
      '@url' => $item['link'],
    ]);
    $feed->skipItem($item);
    return;
  }

  // --- NEWS SOURCE POPULATION - STAGE 1: FEED DATA ---
  $news_source = _news_extractor_extract_news_source_from_feed($item);
  if (!empty($news_source) && $entity_interface->getTarget()->hasField('field_news_source')) {
    $entity_interface->getTarget()->set('field_news_source', $news_source);
    \Drupal::logger('news_extractor')->info('Set news source from feed data: @source for article: @title', [
      '@source' => $news_source,
      '@title' => $title_to_check,
    ]);
  }

  // --- Image linking logic ---
  // Check for image URL in feed item
  $image_url = $item['image'] ?? ($item['media:content'] ?? '');

  if (!empty($image_url) && filter_var($image_url, FILTER_VALIDATE_URL)) {
    // Store the image URL in the node's link field
    if ($entity_interface->getTarget()->hasField('field_external_image_url')) {
      $entity_interface->getTarget()->set('field_external_image_url', [
        'uri' => $image_url,
        'title' => $item['title'] ?? '',
      ]);
    }
  }
  // --- End image linking logic ---
}

/**
 * LEGACY: Implements hook_ENTITY_TYPE_insert().
 * This function has been moved to the main module file.
 * Keeping here for reference only - DO NOT USE.
 */
function _news_extractor_legacy_node_insert(EntityInterface $entity) {
  if ($entity->bundle() !== 'article') {
    return;
  }

  if (!$entity->hasField('field_original_url') || $entity->get('field_original_url')->isEmpty()) {
    return;
  }

  $original_url = $entity->get('field_original_url')->uri;

  // Delete non-article content
  if (!_news_extractor_scraper_is_article_url($original_url)) {
    \Drupal::logger('news_extractor')->info('Deleting non-article content: @title (@url)', [
      '@title' => $entity->getTitle(),
      '@url' => $original_url,
    ]);
    $entity->delete();
    return;
  }

  // Extract content if body is empty or too short
  $body = $entity->get('body')->value;
  if (empty($body) || strlen($body) < 500) {
    
    // --- NEWS SOURCE POPULATION - ENHANCED MULTI-STAGE APPROACH ---
    if ($entity->hasField('field_news_source') && $entity->get('field_news_source')->isEmpty()) {
      $news_source = '';
      
      // Stage 1: Try JSON data first (most reliable)
      if ($entity->hasField('field_json_scraped_article_data') && !$entity->get('field_json_scraped_article_data')->isEmpty()) {
        $news_source = _news_extractor_extract_news_source_from_json_data($entity);
        if (!empty($news_source)) {
          \Drupal::logger('news_extractor')->info('Set news source from JSON data: @source for article: @title', [
            '@source' => $news_source,
            '@title' => $entity->getTitle(),
          ]);
        }
      }
      
      // Stage 2: Fallback to URL extraction
      if (empty($news_source)) {
        $news_source = _news_extractor_extract_news_source_from_url($original_url);
        if (!empty($news_source)) {
          \Drupal::logger('news_extractor')->info('Set news source from URL fallback: @source for article: @title', [
            '@source' => $news_source,
            '@title' => $entity->getTitle(),
          ]);
        }
      }
      
      // Set the news source if found
      if (!empty($news_source)) {
        $entity->set('field_news_source', $news_source);
      }
    }

    _news_extractor_extract_content($entity, $original_url);

    // --- NEWS SOURCE POPULATION - STAGE 3: POST-DIFFBOT ENHANCEMENT ---
    // After Diffbot extraction, try JSON data again if it wasn't available before
    if ($entity->hasField('field_news_source') && $entity->get('field_news_source')->isEmpty()) {
      $news_source = _news_extractor_extract_news_source_from_json_data($entity);
      if (!empty($news_source)) {
        $entity->set('field_news_source', $news_source);
        \Drupal::logger('news_extractor')->info('Set news source from post-Diffbot JSON data: @source', [
          '@source' => $news_source,
        ]);
      }
    }

    // --- Tagging logic ---
    // Get the Motivation Analysis from the entity
    $motivation_analysis = $entity->get('field_motivation_analysis')->value ?? '';
    if (!empty($motivation_analysis)) {
      $tags = _news_extractor_extract_tags_from_summary($motivation_analysis);
      
      // Create simple tags: entities + motivations + metrics
      $all_tags = array_merge($tags['entities'], $tags['motivations'], $tags['metrics']);
      
      $tag_ids = [];
      foreach ($all_tags as $tag) {
        $tid = _news_extractor_get_or_create_tag($tag);
        if ($tid) $tag_ids[] = $tid;
      }
      
      if (!empty($tag_ids)) {
        $entity->set('field_tags', $tag_ids);
        $entity->save();
      }
    }
    // --- End tagging logic ---
    
    // Post-process to fetch and set external image URL - NOW USES SCRAPER
    _news_extractor_scraper_postprocess_article_image($entity);
  }
}

/**
 * LEGACY: Implements hook_ENTITY_TYPE_update().
 * This function has been moved to the main module file.
 * Keeping here for reference only - DO NOT USE.
 */
function _news_extractor_legacy_node_update(EntityInterface $entity) {
  if ($entity->bundle() !== 'article') {
    return;
  }

  if (!$entity->hasField('field_original_url') || $entity->get('field_original_url')->isEmpty()) {
    return;
  }

  $original_url = $entity->get('field_original_url')->uri;
  $original_entity = $entity->original;

  // Only extract if URL has changed
  if (!$original_entity->get('field_original_url')->isEmpty()) {
    $old_url = $original_entity->get('field_original_url')->uri;
    if ($original_url != $old_url) {
      _news_extractor_extract_content($entity, $original_url);
      
      // Update news source when URL changes
      if ($entity->hasField('field_news_source')) {
        $news_source = _news_extractor_extract_news_source_from_url($original_url);
        if (!empty($news_source)) {
          $entity->set('field_news_source', $news_source);
          \Drupal::logger('news_extractor')->info('Updated news source for URL change: @source', [
            '@source' => $news_source,
          ]);
        }
      }
    }
  }
}

/**
 * LEGACY: Implements hook_cron().
 * This function has been moved to the main module file.
 * Keeping here for reference only - DO NOT USE.
 */
function _news_extractor_legacy_cron() {
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'article')
    ->condition('field_original_url.uri', '', '<>')
    ->condition('body.value', '', '=')
    ->range(0, 10)
    ->accessCheck(FALSE);

  $nids = $query->execute();

  if (!empty($nids)) {
    $nodes = Node::loadMultiple($nids);
    foreach ($nodes as $node) {
      $original_url = $node->get('field_original_url')->uri;
      _news_extractor_extract_content($node, $original_url);
    }
    \Drupal::logger('news_extractor')->info('Processed @count articles during cron run', [
      '@count' => count($nids),
    ]);
  }

  // --- NEWS SOURCE POPULATION - STAGE 4: CRON MAINTENANCE ---
  _news_extractor_fix_missing_news_sources();
}

/**
 * Generate AI summary using AWS Bedrock Claude.
 */
function _news_extractor_generate_ai_summary($article_text, $article_title) {
  try {
    $sdk = new \Aws\Sdk([
      'region' => 'us-west-2',
      'version' => 'latest',
    ]);
    $bedrock = $sdk->createBedrockRuntime();

    $prompt = _news_extractor_build_ai_prompt($article_title, $article_text);

    $response = $bedrock->invokeModel([
      'modelId' => 'anthropic.claude-3-5-sonnet-20240620-v1:0',
      'body' => json_encode([
        'anthropic_version' => 'bedrock-2023-05-31',
        'max_tokens' => 1000,
        'messages' => [
          [
            'role' => 'user',
            'content' => $prompt
          ]
        ]
      ])
    ]);

    $result = json_decode($response['body']->getContents(), true);

    if (isset($result['content'][0]['text'])) {
      return $result['content'][0]['text'];
    }
    return null;

  } catch (\Exception $e) {
    \Drupal::logger('news_extractor')->error('Error generating AI summary: @message', [
      '@message' => $e->getMessage(),
    ]);
    return null;
  }
}

/**
 * Get or create a taxonomy term for article tags.
 */
function _news_extractor_get_or_create_tag($tag_name) {
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $terms = $term_storage->loadByProperties([
    'name' => $tag_name,
    'vid' => 'tags',
  ]);

  if (!empty($terms)) {
    $term = reset($terms);
    return $term->id();
  }

  try {
    $term = $term_storage->create([
      'name' => $tag_name,
      'vid' => 'tags',
    ]);
    $term->save();
    return $term->id();
  } catch (\Exception $e) {
    \Drupal::logger('news_extractor')->error('Error creating tag @tag: @message', [
      '@tag' => $tag_name,
      '@message' => $e->getMessage(),
    ]);
    return NULL;
  }
}

/**
 * Get bias rating and credibility score for a news source.
 */
function _news_extractor_get_source_bias($source) {
  $source_ratings = [
    'CNN Politics' => ['bias' => 'lean_left', 'credibility' => 75],
    'CNN' => ['bias' => 'lean_left', 'credibility' => 75],
    'CNN.com - RSS Channel - Politics' => ['bias' => 'lean_left', 'credibility' => 75],
    'NPR' => ['bias' => 'lean_left', 'credibility' => 85],
    'Reuters' => ['bias' => 'center', 'credibility' => 90],
    'Associated Press' => ['bias' => 'center', 'credibility' => 88],
    'BBC News' => ['bias' => 'center', 'credibility' => 82],
    'Wall Street Journal' => ['bias' => 'lean_right', 'credibility' => 80],
    'Fox News' => ['bias' => 'right', 'credibility' => 65],
    'MSNBC' => ['bias' => 'left', 'credibility' => 70],
    'New York Times' => ['bias' => 'lean_left', 'credibility' => 78],
    'Washington Post' => ['bias' => 'lean_left', 'credibility' => 77],
    'Politico' => ['bias' => 'lean_left', 'credibility' => 72],
    'The Hill' => ['bias' => 'center', 'credibility' => 70],
  ];

  return $source_ratings[$source] ?? ['bias' => 'center', 'credibility' => 50];
}

/**
 * Helper function to check for blocked content in title and link.
 */
function _news_extractor_has_blocked_content($title, $link) {
  $blocked_strings = [
    'comparecards.com',
    'fool.com',
    'lendingtree.com',
  ];

  foreach ($blocked_strings as $str) {
    if (stripos($title, $str) !== FALSE) {
      \Drupal::logger('news_extractor')->info('Skipping blocked string in title: @title', [
        '@title' => $title,
      ]);
      return TRUE;
    }
    
    if (stripos($link, $str) !== FALSE) {
      \Drupal::logger('news_extractor')->info('Skipping blocked string in link: @url', [
        '@url' => $link,
      ]);
      return TRUE;
    }
  }

  return FALSE;
}

/**
 * Helper function to check for invalid titles.
 */
function _news_extractor_has_invalid_title($title) {
  // Skip items with missing or empty titles
  if (empty($title) || trim($title) == '') {
    \Drupal::logger('news_extractor')->info('Skipping item with missing or empty title');
    return TRUE;
  }

  // Skip items with very short titles
  if (strlen(trim($title)) < 10) {
    \Drupal::logger('news_extractor')->info('Skipping item with very short title: @title', [
      '@title' => $title,
    ]);
    return TRUE;
  }

  return FALSE;
}

/**
 * Returns the motivational category mapping based on thetruthperspective.org framework.
 * Key: Lowercase motivation string, Value: Motivational category.
 */
function _news_extractor_get_motivation_map() {
  static $motivation_map = [
    // Self-Determination Theory
    'joy' => 'Intrinsic Motivation',
    'happiness' => 'Intrinsic Motivation',
    'amusement' => 'Intrinsic Motivation',
    'bliss' => 'Intrinsic Motivation',
    'delight' => 'Intrinsic Motivation',
    'ecstasy' => 'Intrinsic Motivation',
    'enthusiasm' => 'Intrinsic Motivation',
    'excitement' => 'Intrinsic Motivation',
    'inspiration' => 'Intrinsic Motivation',
    'love' => 'Intrinsic Motivation',
    'wonder' => 'Intrinsic Motivation',
    'curiosity' => 'Intrinsic Motivation',
    'interest' => 'Intrinsic Motivation',
    'beauty appreciation' => 'Intrinsic Motivation',
    'elation' => 'External Regulation: Reward-seeking',
    'euphoria' => 'External Regulation: Reward-seeking',
    'triumph' => 'External Regulation: Reward-seeking',
    'anticipation' => 'External Regulation: Reward-seeking',
    'fear' => 'External Regulation: Punishment-avoidance',
    'anxiety' => 'External Regulation: Punishment-avoidance',
    'dread' => 'External Regulation: Punishment-avoidance',
    'panic' => 'External Regulation: Punishment-avoidance',
    'terror' => 'External Regulation: Punishment-avoidance',
    'worry' => 'External Regulation: Punishment-avoidance',
    'stress' => 'External Regulation: Deadline-driven',
    'tension' => 'External Regulation: Deadline-driven',
    'urgency' => 'External Regulation: Deadline-driven',
    'guilt' => 'Introjected Regulation: Guilt-based',
    'regret' => 'Introjected Regulation: Guilt-based',
    'remorse' => 'Introjected Regulation: Guilt-based',
    'pride' => 'Introjected Regulation: Ego-driven',
    'contempt' => 'Introjected Regulation: Ego-driven',
    'superiority' => 'Introjected Regulation: Ego-driven',
    'obligation' => 'Introjected Regulation: Should-based',
    'duty-bound feelings' => 'Introjected Regulation: Should-based',
    'hope' => 'Identified Regulation',
    'determination' => 'Identified Regulation',
    'resolve' => 'Identified Regulation',
    'serenity' => 'Integrated Regulation',
    'peace' => 'Integrated Regulation',
    'contentment' => 'Integrated Regulation',
    'authenticity' => 'Integrated Regulation',
    'boredom' => 'Amotivation',
    'apathy' => 'Amotivation',
    'dejection' => 'Amotivation',
    'depression' => 'Amotivation',
    'despair' => 'Amotivation',
    'melancholy' => 'Amotivation',
    'misery' => 'Amotivation',
    // Institutional Theory
    'obedience' => 'Compliance Motivation',
    'submission' => 'Compliance Motivation',
    'paranoia' => 'Deterrent Motivation',
    'wariness' => 'Deterrent Motivation',
    'greed' => 'Incentive Motivation',
    'ambition' => 'Incentive Motivation',
    'righteousness' => 'Moral Obligation',
    'moral outrage' => 'Moral Obligation',
    'indignation' => 'Moral Obligation',
    'elevation' => 'Moral Obligation',
    'embarrassment' => 'Social Expectation',
    'humiliation' => 'Social Expectation',
    'shame' => 'Social Expectation',
    'social anxiety' => 'Social Expectation',
    'professional pride' => 'Professional Duty',
    'duty' => 'Professional Duty',
    'gratitude' => 'Reciprocity Obligation',
    'indebtedness' => 'Reciprocity Obligation',
    'envy' => 'Mimetic Motivation',
    'jealousy' => 'Mimetic Motivation',
    'competitive spirit' => 'Mimetic Motivation',
    'dignity' => 'Identity-based Motivation',
    'honor' => 'Identity-based Motivation',
    'self-respect' => 'Identity-based Motivation',
    // Social Cognitive Theory
    'confidence' => 'Efficacy Expectations',
    'assurance' => 'Efficacy Expectations',
    'empowerment' => 'Efficacy Expectations',
    'capability' => 'Efficacy Expectations',
    'materialism' => 'Outcome Expectations',
    'acquisitiveness' => 'Outcome Expectations',
    'admiration-seeking' => 'Outcome Expectations',
    'approval-seeking' => 'Outcome Expectations',
    'self-satisfaction' => 'Outcome Expectations',
    'self-approval' => 'Outcome Expectations',
    'team spirit' => 'Collective Efficacy',
    'solidarity' => 'Collective Efficacy',
    'unity' => 'Collective Efficacy',
    'showing off' => 'Performance Goals',
    'demonstration pride' => 'Performance Goals',
    'performance anxiety' => 'Performance Goals',
    'fear of failure' => 'Performance Goals',
    'fascination' => 'Learning Goals',
    'intellectual excitement' => 'Learning Goals',
    'self-awareness' => 'Self-Monitoring',
    'mindfulness' => 'Self-Monitoring',
    'self-criticism' => 'Self-Evaluation',
    'self-assessment' => 'Self-Evaluation',
    'self-congratulation' => 'Self-Reaction',
    'self-appreciation' => 'Self-Reaction',
    'self-loathing' => 'Self-Reaction',
    'self-disappointment' => 'Self-Reaction',
    'modeling desire' => 'Observational Learning',
    'imitation urge' => 'Observational Learning',
    'encouragement' => 'Social Persuasion',
    'motivation from others' => 'Social Persuasion',
    'inspiration from others' => 'Vicarious Motivation',
    'aspirational feelings' => 'Vicarious Motivation',
    'cautionary feelings' => 'Vicarious Motivation',
    'warning emotions' => 'Vicarious Motivation',
    'exhilaration' => 'Emotional Arousal',
    'energized' => 'Emotional Arousal',
    'pumped up' => 'Emotional Arousal',
    'agitation' => 'Emotional Arousal',
    'restlessness' => 'Emotional Arousal',
    'irritation' => 'Emotional Arousal',
    'moodiness' => 'Mood-dependent Motivation',
    'emotional volatility' => 'Mood-dependent Motivation',
    'whimsical feelings' => 'Mood-dependent Motivation',
    'anticipated pleasure' => 'Anticipated Affect',
    'excitement about future' => 'Anticipated Affect',
    'preemptive guilt' => 'Anticipated Affect',
    'future-focused anxiety' => 'Anticipated Affect',
    'nostalgia' => 'Complex/Mixed Emotions',
    'bittersweet' => 'Complex/Mixed Emotions',
    'ambivalence' => 'Complex/Mixed Emotions',
    'poignancy' => 'Complex/Mixed Emotions',
  ];
  return $motivation_map;
}

/**
 * Motivational category mapping based on thetruthperspective.org framework.
 */
function _news_extractor_get_motivation_category($motivation) {
  $motivation_map = _news_extractor_get_motivation_map();
  $key = strtolower(trim($motivation));
  return $motivation_map[$key] ?? 'Other';
}

/**
 * Helper function to build AI prompt for social scientist perspective - Enhanced JSON output.
 */
function _news_extractor_build_ai_prompt($article_title, $article_text) {
  $allowed_motivations = "Ambition, Competitive spirit, Righteousness, Moral outrage, Loyalty, Pride, Determination, Fear, Greed, Power, Control, Revenge, Justice, Self-preservation, Recognition, Legacy, Influence, Security, Freedom, Unity, Professional pride, Duty, Curiosity, Enthusiasm, Wariness, Anxiety, Self-respect, Obligation, Indignation";

  return "As a social scientist, analyze this article comprehensively for both content analysis and media assessment.\n\n" .
         "Instructions:\n" .
         "1. Identify each entity (person, organization, institution) mentioned in the article\n" .
         "2. For each entity, select their top 2-3 motivations from the allowed list\n" .
         "3. Choose the most relevant US performance metric this article impacts\n" .
         "4. Provide analysis of how this affects that metric\n" .
         "5. Assess the article's credibility, bias, and sentiment\n\n" .
         "Use ONLY motivations from this list: $allowed_motivations\n\n" .
         "CREDIBILITY SCORING (0-100):\n" .
         "- 0-20: Intentional deceit, false information, propaganda\n" .
         "- 21-40: Highly questionable sources, unverified claims\n" .
         "- 41-60: Mixed reliability, some factual issues\n" .
         "- 61-80: Generally reliable with minor issues\n" .
         "- 81-100: Highly credible, well-sourced, factual\n\n" .
         "BIAS RATING (0-100):\n" .
         "- 0-20: Extreme Left\n" .
         "- 21-40: Lean Left\n" .
         "- 41-60: Center\n" .
         "- 61-80: Lean Right\n" .
         "- 81-100: Extreme Right\n\n" .
         "SENTIMENT SCORING (0-100):\n" .
         "- 0-20: Very negative, doom, crisis\n" .
         "- 21-40: Negative, critical, pessimistic\n" .
         "- 41-60: Neutral, balanced reporting\n" .
         "- 61-80: Positive, optimistic, hopeful\n" .
         "- 81-100: Very positive, celebratory, triumphant\n\n" .
         "Return your response as valid JSON in this exact format:\n\n" .
         "{\n" .
         "  \"entities\": [\n" .
         "    {\n" .
         "      \"name\": \"Entity Name\",\n" .
         "      \"motivations\": [\"Motivation1\", \"Motivation2\", \"Motivation3\"]\n" .
         "    }\n" .
         "  ],\n" .
         "  \"key_metric\": \"Specific Metric Name\",\n" .
         "  \"analysis\": \"As a social scientist, I analyze that [your detailed analysis].\",\n" .
         "  \"credibility_score\": 75,\n" .
         "  \"bias_rating\": 45,\n" .
         "  \"bias_analysis\": \"Two-line explanation of why this bias rating was selected based on language, framing, and source presentation.\",\n" .
         "  \"sentiment_score\": 35\n" .
         "}\n\n" .
         "IMPORTANT: Return ONLY the JSON object, no other text or formatting.\n\n" .
         "Article Title: " . $article_title . "\n\n" .
         "Article Text: " . $article_text;
}

/**
 * Extract entities, motivations, and metrics from structured JSON data.
 */
function _news_extractor_extract_tags_from_summary($structured_data) {
  $entities = [];
  $motivations = [];
  $metrics = [];

  // If we get JSON string, parse it first
  if (is_string($structured_data)) {
    $structured_data = json_decode($structured_data, true);
  }

  if (is_array($structured_data)) {
    // Extract entities
    if (isset($structured_data['entities']) && is_array($structured_data['entities'])) {
      foreach ($structured_data['entities'] as $entity_data) {
        if (isset($entity_data['name'])) {
          $entities[] = $entity_data['name'];
        }
        if (isset($entity_data['motivations']) && is_array($entity_data['motivations'])) {
          $motivations = array_merge($motivations, $entity_data['motivations']);
        }
      }
    }
    
    // Extract direct motivations array
    if (isset($structured_data['motivations']) && is_array($structured_data['motivations'])) {
      $motivations = array_merge($motivations, $structured_data['motivations']);
    }
    
    // Extract metrics
    if (isset($structured_data['metrics']) && is_array($structured_data['metrics'])) {
      $metrics = array_merge($metrics, $structured_data['metrics']);
    }
  }

  return [
    'entities' => array_unique($entities),
    'motivations' => array_unique($motivations),
    'metrics' => array_unique($metrics),
  ];
}

/**
 * Extract structured motivation data from AI JSON response - Enhanced with media assessment.
 */
function _news_extractor_extract_structured_data($ai_response) {
  $data = [
    'entities' => [],
    'motivations' => [],
    'metrics' => [],
    'credibility_score' => null,
    'bias_rating' => null,
    'bias_analysis' => '',
    'sentiment_score' => null
  ];

  try {
    // Clean the response - remove any non-JSON content
    $json_start = strpos($ai_response, '{');
    $json_end = strrpos($ai_response, '}');
    
    if ($json_start !== false && $json_end !== false) {
      $json_content = substr($ai_response, $json_start, $json_end - $json_start + 1);
      $parsed = json_decode($json_content, true);
      
      if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
        // Extract entities and motivations
        if (isset($parsed['entities']) && is_array($parsed['entities'])) {
          foreach ($parsed['entities'] as $entity_data) {
            if (isset($entity_data['name']) && isset($entity_data['motivations'])) {
              $data['entities'][] = [
                'name' => $entity_data['name'],
                'motivations' => $entity_data['motivations']
              ];
              
              // Collect unique motivations
              foreach ($entity_data['motivations'] as $motivation) {
                if (!empty($motivation) && !in_array($motivation, $data['motivations'])) {
                  $data['motivations'][] = $motivation;
                }
              }
            }
          }
        }
        
        // Extract key metric
        if (isset($parsed['key_metric']) && !empty($parsed['key_metric'])) {
          $data['metrics'][] = $parsed['key_metric'];
        }
        
        // Store the analysis text for human display
        if (isset($parsed['analysis'])) {
          $data['analysis'] = $parsed['analysis'];
        }
        
        // Extract new assessment fields
        if (isset($parsed['credibility_score']) && is_numeric($parsed['credibility_score'])) {
          $data['credibility_score'] = (int) $parsed['credibility_score'];
        }
        
        if (isset($parsed['bias_rating']) && is_numeric($parsed['bias_rating'])) {
          $data['bias_rating'] = (int) $parsed['bias_rating'];
        }
        
        if (isset($parsed['bias_analysis']) && !empty($parsed['bias_analysis'])) {
          $data['bias_analysis'] = $parsed['bias_analysis'];
        }
        
        if (isset($parsed['sentiment_score']) && is_numeric($parsed['sentiment_score'])) {
          $data['sentiment_score'] = (int) $parsed['sentiment_score'];
        }
        
      } else {
        \Drupal::logger('news_extractor')->error('JSON parsing failed: @error', ['@error' => json_last_error_msg()]);
      }
    } else {
      \Drupal::logger('news_extractor')->error('No valid JSON found in AI response');
    }
  } catch (\Exception $e) {
    \Drupal::logger('news_extractor')->error('Error parsing AI JSON response: @error', ['@error' => $e->getMessage()]);
  }

  return $data;
}

/**
 * Helper to get the Diffbot API token from config.
 *
 * @return string
 *   The Diffbot API token, or an empty string if not set.
 */
function news_extractor_get_diffbot_token() {
  return \Drupal::config('news_extractor.settings')->get('diffbot_token') ?: '';
}

/**
 * STAGE 1: Extract news source from feed item data.
 */
function _news_extractor_extract_news_source_from_feed($item) {
  $news_source = '';
  
  // Priority 1: Check for explicit source field in feed
  if (!empty($item['source'])) {
    $news_source = $item['source'];
  }
  // Priority 2: Check for dc:source in Dublin Core metadata
  elseif (!empty($item['dc:source'])) {
    $news_source = $item['dc:source'];
  }
  // Priority 3: Check feed title/description (often contains source)
  elseif (!empty($item['feed_title'])) {
    $news_source = $item['feed_title'];
  }
  // Priority 4: Check description for source patterns (Source: CNN, Via: Reuters, etc.)
  elseif (!empty($item['description']) && preg_match('/(?:Source|Via|From):\s*(.+?)(?:\n|$|\|)/i', $item['description'], $matches)) {
    $news_source = trim($matches[1]);
  }
  // Priority 5: Extract from link URL as last resort
  elseif (!empty($item['link'])) {
    $news_source = _news_extractor_extract_news_source_from_url($item['link']);
  }
  
  // Clean and standardize the news source
  if (!empty($news_source)) {
    $news_source = _news_extractor_clean_news_source($news_source);
  }
  
  return !empty($news_source) ? $news_source : NULL;
}

/**
 * STAGE 2: Extract news source from URL (fallback method).
 */
function _news_extractor_extract_news_source_from_url($url) {
  if (empty($url)) {
    return NULL;
  }
  
  $parsed_url = parse_url($url);
  if (!isset($parsed_url['host'])) {
    return NULL;
  }
  
  $host = strtolower($parsed_url['host']);
  
  // Remove 'www.' prefix
  $host = preg_replace('/^www\./', '', $host);
  
  // Map common domains to clean source names
  $domain_map = [
    'cnn.com' => 'CNN',
    'politics.cnn.com' => 'CNN Politics',
    'foxnews.com' => 'Fox News',
    'reuters.com' => 'Reuters',
    'ap.org' => 'Associated Press',
    'apnews.com' => 'Associated Press',
    'npr.org' => 'NPR',
    'bbc.com' => 'BBC News',
    'bbc.co.uk' => 'BBC News',
    'wsj.com' => 'Wall Street Journal',
    'nytimes.com' => 'New York Times',
    'washingtonpost.com' => 'Washington Post',
    'politico.com' => 'Politico',
    'thehill.com' => 'The Hill',
    'msnbc.com' => 'MSNBC',
    'nbcnews.com' => 'NBC News',
    'abcnews.go.com' => 'ABC News',
    'cbsnews.com' => 'CBS News',
    'usatoday.com' => 'USA Today',
    'bloomberg.com' => 'Bloomberg',
    'theguardian.com' => 'The Guardian',
    'time.com' => 'Time',
    'newsweek.com' => 'Newsweek',
    'huffpost.com' => 'HuffPost',
    'slate.com' => 'Slate',
    'vox.com' => 'Vox',
    'axios.com' => 'Axios',
    'breitbart.com' => 'Breitbart',
    'dailywire.com' => 'The Daily Wire',
    'nypost.com' => 'New York Post',
    'dailymail.co.uk' => 'Daily Mail',
    'independent.co.uk' => 'The Independent',
    'ft.com' => 'Financial Times',
  ];
  
  if (isset($domain_map[$host])) {
    return $domain_map[$host];
  }
  
  // For unknown domains, convert to title case
  $parts = explode('.', $host);
  if (count($parts) >= 2) {
    $domain_name = $parts[0];
    return ucwords(str_replace(['-', '_'], ' ', $domain_name));
  }
  
  return ucwords(str_replace(['-', '_', '.'], ' ', $host));
}

/**
 * STAGE 3: Extract news source from field_site_name or field_json_scraped_article_data (hybrid approach).
 */
function _news_extractor_extract_news_source_from_json_data(EntityInterface $entity) {
  // PRIORITY 1: Try field_site_name first (most efficient) - only if field exists
  if ($entity->hasField('field_site_name') && !$entity->get('field_site_name')->isEmpty()) {
    $site_name = trim($entity->get('field_site_name')->value);
    
    if (!empty($site_name)) {
      // Clean and standardize the site name
      $cleaned_source = _news_extractor_clean_news_source($site_name);
      
      \Drupal::logger('news_extractor')->info('Extracted news source from field_site_name: @source (original: @original) for article @id', [
        '@source' => $cleaned_source,
        '@original' => $site_name,
        '@id' => $entity->id(),
      ]);
      
      return $cleaned_source;
    }
  }
  
  // PRIORITY 2: Fallback to JSON parsing from field_json_scraped_article_data
  if (!$entity->hasField('field_json_scraped_article_data') || $entity->get('field_json_scraped_article_data')->isEmpty()) {
    return NULL;
  }
  
  $json_data = $entity->get('field_json_scraped_article_data')->value;
  
  // Check for known status indicators
  if (trim($json_data) === "Scraped data unavailable.") {
    \Drupal::logger('news_extractor')->info('Article @id has unavailable scraped data status - setting default source', [
      '@id' => $entity->id(),
    ]);
    return "Source Unavailable";
  }
  
  try {
    $parsed_data = json_decode($json_data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
      \Drupal::logger('news_extractor')->error('Invalid JSON in field_json_scraped_article_data for article @id: @error', [
        '@id' => $entity->id(),
        '@error' => json_last_error_msg(),
      ]);
      return NULL;
    }
    
    // Extract siteName from Diffbot objects array
    if (isset($parsed_data['objects']) && is_array($parsed_data['objects'])) {
      foreach ($parsed_data['objects'] as $object) {
        if (isset($object['siteName']) && !empty($object['siteName'])) {
          $site_name = trim($object['siteName']);
          
          // Clean and standardize the site name
          $cleaned_source = _news_extractor_clean_news_source($site_name);
          
          \Drupal::logger('news_extractor')->info('Extracted news source from JSON fallback: @source (original: @original) for article @id', [
            '@source' => $cleaned_source,
            '@original' => $site_name,
            '@id' => $entity->id(),
          ]);
          
          return $cleaned_source;
        }
      }
    }
    
    \Drupal::logger('news_extractor')->warning('No siteName found in JSON data fallback for article @id', [
      '@id' => $entity->id(),
    ]);
    
  } catch (\Exception $e) {
    \Drupal::logger('news_extractor')->error('Error parsing JSON data fallback for article @id: @message', [
      '@id' => $entity->id(),
      '@message' => $e->getMessage(),
    ]);
  }
  
  return NULL;
}

/**
 * Clean and standardize news source names.
 */
function _news_extractor_clean_news_source($source) {
  if (empty($source)) {
    return '';
  }
  
  // Trim whitespace
  $source = trim($source);
  
  // Remove common suffixes and prefixes
  $patterns_to_remove = [
    '/\s*-\s*RSS.*$/i',
    '/\s*RSS.*$/i',
    '/\s*-\s*Politics.*$/i',
    '/\s*Breaking News.*$/i',
    '/\s*Latest News.*$/i',
    '/\s*News Feed.*$/i',
    '/\s*\|.*$/i',
    '/\s*::.*$/i',
    '/\s*\(.*\).*$/i',
    '/^RSS:\s*/i',
    '/^Feed:\s*/i',
  ];
  
  foreach ($patterns_to_remove as $pattern) {
    $source = preg_replace($pattern, '', $source);
    $source = trim($source);
  }
  
  // Remove trailing dashes and other punctuation
  $source = preg_replace('/[\s\-\|:]+$/', '', $source);
  $source = trim($source);
  
  // Common source name standardizations
  $standardizations = [
    '/^CNN Politics$/i' => 'CNN',
    '/^CNN\.com.*$/i' => 'CNN',
    '/^CNN International.*$/i' => 'CNN',
    '/^Fox News Politics.*$/i' => 'Fox News',
    '/^FOX News.*$/i' => 'Fox News',
    '/^The New York Times.*$/i' => 'New York Times',
    '/^The Washington Post.*$/i' => 'Washington Post',
    '/^The Wall Street Journal.*$/i' => 'Wall Street Journal',
    '/^The Guardian.*$/i' => 'The Guardian',
    '/^BBC News.*$/i' => 'BBC News',
    '/^Reuters.*$/i' => 'Reuters',
    '/^Associated Press.*$/i' => 'Associated Press',
    '/^AP News.*$/i' => 'Associated Press',
    '/^NBC News.*$/i' => 'NBC News',
    '/^ABC News.*$/i' => 'ABC News',
    '/^CBS News.*$/i' => 'CBS News',
  ];
  
  foreach ($standardizations as $pattern => $standard) {
    if (preg_match($pattern, $source)) {
      return $standard;
    }
  }
  
  return trim($source);
}

/**
 * Populate news source from existing JSON scraped data for articles (focuses on JSON data).
 */
function _news_extractor_populate_news_source_from_json_data($batch_size = 50) {
  // Find articles with JSON data but missing news source
  $or_group = \Drupal::entityQuery('node')->orConditionGroup()
    ->condition('field_news_source', NULL, 'IS NULL')
    ->condition('field_news_source', '', '=');
  
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'article')
    ->condition('field_json_scraped_article_data', '', '<>')
    ->condition($or_group)
    ->range(0, $batch_size)
    ->accessCheck(FALSE);

  $nids = $query->execute();

  if (empty($nids)) {
    return 0;
  }

  $nodes = Node::loadMultiple($nids);
  $updated_count = 0;
  
  foreach ($nodes as $node) {
    $news_source = _news_extractor_extract_news_source_from_json_data($node);
    
    if (!empty($news_source)) {
      $node->set('field_news_source', $news_source);
      $node->save();
      $updated_count++;
      
      \Drupal::logger('news_extractor')->info('Populated news source via JSON processing for article @id (@title): @source', [
        '@id' => $node->id(),
        '@title' => $node->getTitle(),
        '@source' => $news_source,
      ]);
    }
  }
  
  return $updated_count;
}

/**
 * ENHANCED STAGE 4: Fix missing news sources using multiple methods.
 */
function _news_extractor_fix_missing_news_sources() {
  $total_updated = 0;
  
  // Priority 1: Use JSON scraped data if available
  $json_updated = _news_extractor_populate_news_source_from_json_data(25);
  $total_updated += $json_updated;
  
  if ($json_updated > 0) {
    \Drupal::logger('news_extractor')->info('Populated @count news sources from JSON data during cron run', [
      '@count' => $json_updated,
    ]);
  }
  
  // Priority 2: Use URL extraction for remaining articles
  $or_group2 = \Drupal::entityQuery('node')->orConditionGroup()
    ->condition('field_news_source', NULL, 'IS NULL')
    ->condition('field_news_source', '', '=');
  
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'article')
    ->condition('field_original_url.uri', '', '<>')
    ->condition($or_group2)
    ->range(0, 25)
    ->accessCheck(FALSE);

  $nids = $query->execute();

  if (!empty($nids)) {
    $nodes = Node::loadMultiple($nids);
    $url_updated = 0;
    
    foreach ($nodes as $node) {
      $original_url = $node->get('field_original_url')->uri;
      $news_source = _news_extractor_extract_news_source_from_url($original_url);
      
      if (!empty($news_source)) {
        $node->set('field_news_source', $news_source);
        $node->save();
        $url_updated++;
        $total_updated++;
        
        \Drupal::logger('news_extractor')->info('Fixed missing news source for article @id (@title): @source', [
          '@id' => $node->id(),
          '@title' => $node->getTitle(),
          '@source' => $news_source,
        ]);
      }
    }
    
    if ($url_updated > 0) {
      \Drupal::logger('news_extractor')->info('Fixed @count missing news sources from URL during cron run', [
        '@count' => $url_updated,
      ]);
    }
  }
  
  if ($total_updated > 0) {
    \Drupal::logger('news_extractor')->info('Total @count news sources updated during cron run', [
      '@count' => $total_updated,
    ]);
  }
}

/**
 * Drush command helper functions for bulk processing.
 */
function _news_extractor_populate_all_news_sources_from_json() {
  $batch_size = 100;
  $total_updated = 0;
  
  \Drupal::logger('news_extractor')->info('Starting bulk population of news sources from JSON data');
  
  do {
    $updated_count = _news_extractor_populate_news_source_from_json_data($batch_size);
    $total_updated += $updated_count;
    
    if ($updated_count > 0) {
      \Drupal::logger('news_extractor')->info('Processed batch: @updated updated out of @batch articles', [
        '@updated' => $updated_count,
        '@batch' => $batch_size,
      ]);
      sleep(1);
    }
    
  } while ($updated_count > 0);
  
  \Drupal::logger('news_extractor')->info('Completed bulk population: @total articles updated', [
    '@total' => $total_updated,
  ]);
  
  return $total_updated;
}
