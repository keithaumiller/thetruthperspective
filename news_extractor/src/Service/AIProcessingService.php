<?php

namespace Drupal\news_extractor\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Aws\Sdk;

/**
 * Service for handling AI processing via AWS Bedrock Claude.
 * 
 * This service is responsible for:
 * - Building prompts for AI analysis
 * - Making AWS Bedrock API calls to Claude
 * - Handling AI response processing
 * - Managing AI-specific error handling
 */
class AIProcessingService {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * AIProcessingService constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Get the logger for this service.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected function logger() {
    return $this->loggerFactory->get('news_extractor_ai');
  }

  /**
   * Generate AI summary using AWS Bedrock Claude.
   *
   * @param string $article_text
   *   The article content to analyze.
   * @param string $article_title
   *   The article title.
   *
   * @return string|null
   *   The AI response or NULL on failure.
   */
  public function generateAnalysis($article_text, $article_title) {
    try {
      $sdk = new Sdk([
        'region' => 'us-west-2',
        'version' => 'latest',
      ]);
      $bedrock = $sdk->createBedrockRuntime();

      $prompt = $this->buildAnalysisPrompt($article_title, $article_text);

      $this->logger()->info('Generating AI analysis for article: @title', [
        '@title' => $article_title,
      ]);

      $response = $bedrock->invokeModel([
        'modelId' => 'anthropic.claude-3-5-sonnet-20240620-v1:0',
        'body' => json_encode([
          'anthropic_version' => 'bedrock-2023-05-31',
          'max_tokens' => 1000,
          'messages' => [
            [
              'role' => 'user',
              'content' => $prompt,
            ],
          ],
        ]),
      ]);

      $result = json_decode($response['body']->getContents(), TRUE);

      if (isset($result['content'][0]['text'])) {
        $ai_response = $result['content'][0]['text'];
        $this->logger()->info('Successfully generated AI analysis (@chars chars) for: @title', [
          '@chars' => strlen($ai_response),
          '@title' => $article_title,
        ]);
        return $ai_response;
      }

      $this->logger()->warning('No content returned from Claude for: @title', [
        '@title' => $article_title,
      ]);
      return NULL;

    }
    catch (\Exception $e) {
      $this->logger()->error('Error generating AI analysis for @title: @message', [
        '@title' => $article_title,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Build comprehensive analysis prompt for Claude.
   *
   * @param string $article_title
   *   The article title.
   * @param string $article_text
   *   The article content.
   *
   * @return string
   *   The formatted prompt.
   */
  protected function buildAnalysisPrompt($article_title, $article_text) {
    $allowed_motivations = $this->getAllowedMotivations();

    return "As a social scientist, analyze this article comprehensively for both content analysis and media assessment.\n\n" .
           "Instructions:\n" .
           "1. Identify each entity (person, organization, institution) mentioned in the article\n" .
           "2. For each entity, select their top 2-3 motivations from the allowed list\n" .
           "3. Choose the most relevant US performance metric this article impacts\n" .
           "4. Provide analysis of how this affects that metric\n" .
           "5. Assess the article's credibility, bias, sentiment, and authoritarianism risk\n\n" .
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
           "AUTHORITARIANISM RISK SCORING (0-100):\n" .
           "- 0-20: Strongly promotes democratic values, transparency, accountability\n" .
           "- 21-40: Generally democratic with minor authoritarian elements\n" .
           "- 41-60: Mixed democratic/authoritarian signals or neutral\n" .
           "- 61-80: Shows authoritarian tendencies, power consolidation themes\n" .
           "- 81-100: Promotes totalitarian ideas, suppression of dissent, elimination of checks/balances\n\n" .
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
           "  \"sentiment_score\": 35,\n" .
           "  \"authoritarianism_score\": 25\n" .
           "}\n\n" .
           "IMPORTANT: Return ONLY the JSON object, no other text or formatting.\n\n" .
           "Article Title: " . $article_title . "\n\n" .
           "Article Text: " . $article_text;
  }

  /**
   * Get the allowed motivations list.
   *
   * @return string
   *   Comma-separated list of allowed motivations.
   */
  protected function getAllowedMotivations() {
    return "Ambition, Competitive spirit, Righteousness, Moral outrage, Loyalty, Pride, Determination, Fear, Greed, Power, Control, Revenge, Justice, Self-preservation, Recognition, Legacy, Influence, Security, Freedom, Unity, Professional pride, Duty, Curiosity, Enthusiasm, Wariness, Anxiety, Self-respect, Obligation, Indignation";
  }

  /**
   * Parse AI response to extract structured data.
   *
   * @param string $ai_response
   *   The raw AI response.
   *
   * @return array
   *   Structured data array.
   */
  public function parseResponse($ai_response) {
    $data = [
      'entities' => [],
      'motivations' => [],
      'metrics' => [],
      'credibility_score' => NULL,
      'bias_rating' => NULL,
      'bias_analysis' => '',
      'sentiment_score' => NULL,
      'authoritarianism_score' => NULL,
      'analysis' => '',
    ];

    try {
      // Clean the response - remove any non-JSON content
      $json_start = strpos($ai_response, '{');
      $json_end = strrpos($ai_response, '}');

      if ($json_start !== FALSE && $json_end !== FALSE) {
        $json_content = substr($ai_response, $json_start, $json_end - $json_start + 1);
        $parsed = json_decode($json_content, TRUE);

        if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
          // Extract entities and motivations
          if (isset($parsed['entities']) && is_array($parsed['entities'])) {
            foreach ($parsed['entities'] as $entity_data) {
              if (isset($entity_data['name']) && isset($entity_data['motivations'])) {
                $data['entities'][] = [
                  'name' => $entity_data['name'],
                  'motivations' => $entity_data['motivations'],
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

          // Extract assessment fields
          if (isset($parsed['credibility_score']) && is_numeric($parsed['credibility_score'])) {
            $data['credibility_score'] = (int) $parsed['credibility_score'];
          }

          if (isset($parsed['bias_rating']) && is_numeric($parsed['bias_rating'])) {
            $data['bias_rating'] = (int) $parsed['bias_rating'];
          }

          if (isset($parsed['bias_analysis']) && !empty($parsed['bias_analysis'])) {
            // Limit bias analysis to 999 characters to prevent database field overflow
            $bias_analysis = $parsed['bias_analysis'];
            if (strlen($bias_analysis) > 999) {
              $bias_analysis = substr($bias_analysis, 0, 996) . '...';
              $this->logger()->warning('Bias analysis truncated from @original to 999 characters', [
                '@original' => strlen($parsed['bias_analysis']),
              ]);
            }
            $data['bias_analysis'] = $bias_analysis;
          }

          if (isset($parsed['sentiment_score']) && is_numeric($parsed['sentiment_score'])) {
            $data['sentiment_score'] = (int) $parsed['sentiment_score'];
          }

          if (isset($parsed['authoritarianism_score']) && is_numeric($parsed['authoritarianism_score'])) {
            $data['authoritarianism_score'] = (int) $parsed['authoritarianism_score'];
          }

          $this->logger()->info('Successfully parsed AI response with @entities entities and @motivations motivations', [
            '@entities' => count($data['entities']),
            '@motivations' => count($data['motivations']),
          ]);
        }
        else {
          $this->logger()->error('JSON parsing failed: @error', ['@error' => json_last_error_msg()]);
        }
      }
      else {
        $this->logger()->error('No valid JSON found in AI response');
      }
    }
    catch (\Exception $e) {
      $this->logger()->error('Error parsing AI JSON response: @error', ['@error' => $e->getMessage()]);
    }

    return $data;
  }

  /**
   * Validate AI response structure.
   *
   * @param array $structured_data
   *   The parsed AI response data.
   *
   * @return bool
   *   TRUE if the response has valid structure.
   */
  public function validateResponse(array $structured_data) {
    // Check for required fields
    if (empty($structured_data['entities']) || !is_array($structured_data['entities'])) {
      $this->logger()->warning('AI response missing or invalid entities array');
      return FALSE;
    }

    // Check for at least one entity with name and motivations
    $valid_entities = 0;
    foreach ($structured_data['entities'] as $entity) {
      if (isset($entity['name']) && isset($entity['motivations']) && is_array($entity['motivations'])) {
        $valid_entities++;
      }
    }

    if ($valid_entities === 0) {
      $this->logger()->warning('AI response contains no valid entities with name and motivations');
      return FALSE;
    }

    return TRUE;
  }

}
