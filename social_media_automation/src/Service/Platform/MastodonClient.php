<?php

namespace Drupal\social_media_automation\Service\Platform;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\social_media_automation\Traits\ConfigurableLoggingTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Mastodon platform client for social media automation.
 * 
 * Handles authentication and communication with Mastodon API.
 */
class MastodonClient implements PlatformInterface {

  use ConfigurableLoggingTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactor  /**
   * Build content generation prompt for social media post.
   *
   * @param string $article_title
   *   The article title.
   * @param string $article_url
   *   The article URL.
   * @param string $motivation_analysis
   *   The motivation analysis data.
   *
   * @return string
   *   The prompt for AI.
   */
  protected function buildContentPrompt($article_title, $article_url, $motivation_analysis): string { The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    ClientInterface $http_client
  ) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('social_media_automation');
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'Mastodon';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName(): string {
    return 'mastodon';
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigured(): bool {
    $config = $this->configFactory->get('social_media_automation.settings');
    $credentials = $this->getRequiredCredentials();
    
    $this->logDebug('Checking Mastodon configuration...');
    
    foreach ($credentials as $credential) {
      $value = $config->get("mastodon.{$credential}");
      if (empty($value)) {
        $this->logDebug('Missing credential: mastodon.@credential', ['@credential' => $credential]);
        return FALSE;
      } else {
        $this->logDebug('Credential present: mastodon.@credential (@length chars)', [
          '@credential' => $credential,
          '@length' => strlen($value),
        ]);
      }
    }
    
    $this->logDebug('All Mastodon credentials present');
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function testConnection(): bool {
    $this->logInfo('=== Starting Mastodon connection test ===');
    
    // Step 1: Check if configured
    if (!$this->isConfigured()) {
      $config = $this->configFactory->get('social_media_automation.settings');
      $server_url = $config->get('mastodon.server_url');
      $access_token = $config->get('mastodon.access_token');
      
      $this->logError('Mastodon not configured. Missing credentials:');
      $this->logError('- server_url: @server_url', ['@server_url' => $server_url ? 'Present' : 'MISSING']);
      $this->logError('- access_token: @access_token', ['@access_token' => $access_token ? 'Present (' . strlen($access_token) . ' chars)' : 'MISSING']);
      
      return FALSE;
    }

    $config = $this->configFactory->get('social_media_automation.settings');
    $server_url = $config->get('mastodon.server_url');
    $access_token = $config->get('mastodon.access_token');

    $this->logInfo('Step 1: Configuration check passed');
    $this->logInfo('- Server URL: @server', ['@server' => $server_url]);
    $this->logInfo('- Access Token: @token_length chars, ends with @token_end', [
      '@token_length' => strlen($access_token),
      '@token_end' => substr($access_token, -8),
    ]);

    // Step 2: Validate URL format
    if (!filter_var($server_url, FILTER_VALIDATE_URL)) {
      $this->logger->error('Step 2 FAILED: Invalid server URL format: @url', ['@url' => $server_url]);
      return FALSE;
    }
    $this->logger->info('Step 2: URL format validation passed');

    // Step 3: Check if URL is accessible
    $test_url = rtrim($server_url, '/') . '/api/v1/accounts/verify_credentials';
    $this->logger->info('Step 3: Testing API endpoint: @url', ['@url' => $test_url]);

    try {
      $this->logger->info('Step 4: Making HTTP request...');
      
      $response = $this->httpClient->get($test_url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
          'Accept' => 'application/json',
          'User-Agent' => 'TruthPerspective/1.0 (+https://thetruthperspective.org)',
        ],
        'timeout' => 10,
        'allow_redirects' => true,
      ]);

      $status = $response->getStatusCode();
      $this->logger->info('Step 5: HTTP response received');
      $this->logger->info('- Status Code: @status', ['@status' => $status]);
      $this->logger->info('- Content Type: @type', ['@type' => $response->getHeaderLine('Content-Type')]);
      
      if ($status === 200) {
        // Try to decode the response to verify it's valid JSON
        try {
          $body = $response->getBody()->getContents();
          $data = json_decode($body, true);
          
          if (json_last_error() === JSON_ERROR_NONE && isset($data['id'])) {
            $this->logger->info('Step 6: SUCCESS! Valid account data received');
            $this->logger->info('- Account ID: @id', ['@id' => $data['id']]);
            $this->logger->info('- Username: @username', ['@username' => $data['username'] ?? 'Unknown']);
            $this->logger->info('- Display Name: @name', ['@name' => $data['display_name'] ?? 'Unknown']);
            return TRUE;
          } else {
            $this->logger->error('Step 6 FAILED: Invalid JSON response or missing account ID');
            $this->logger->error('- JSON Error: @error', ['@error' => json_last_error_msg()]);
            $this->logger->error('- Response body (first 200 chars): @body', ['@body' => substr($body, 0, 200)]);
            return FALSE;
          }
        } catch (\Exception $e) {
          $this->logger->error('Step 6 FAILED: Error parsing response: @message', ['@message' => $e->getMessage()]);
          return FALSE;
        }
      } else {
        $this->logger->error('Step 5 FAILED: HTTP error status @status', ['@status' => $status]);
        
        // Try to get error details from response
        try {
          $body = $response->getBody()->getContents();
          $error_data = json_decode($body, true);
          if (json_last_error() === JSON_ERROR_NONE && isset($error_data['error'])) {
            $this->logger->error('- Mastodon error: @error', ['@error' => $error_data['error']]);
          } else {
            $this->logger->error('- Response body: @body', ['@body' => substr($body, 0, 500)]);
          }
        } catch (\Exception $e) {
          $this->logger->error('- Could not parse error response: @message', ['@message' => $e->getMessage()]);
        }
        
        return FALSE;
      }

    } catch (RequestException $e) {
      $this->logger->error('Step 4 FAILED: HTTP request exception');
      $this->logger->error('- Exception type: @type', ['@type' => get_class($e)]);
      $this->logger->error('- Message: @message', ['@message' => $e->getMessage()]);
      
      if ($e->hasResponse()) {
        $response = $e->getResponse();
        $this->logger->error('- Response status: @status', ['@status' => $response->getStatusCode()]);
        $this->logger->error('- Response headers: @headers', ['@headers' => json_encode($response->getHeaders())]);
        
        try {
          $body = $response->getBody()->getContents();
          $this->logger->error('- Response body: @body', ['@body' => substr($body, 0, 500)]);
        } catch (\Exception $body_error) {
          $this->logger->error('- Could not read response body: @error', ['@error' => $body_error->getMessage()]);
        }
      } else {
        $this->logger->error('- No response received (connection/DNS issue?)');
      }
      
      return FALSE;
      
    } catch (\Exception $e) {
      $this->logger->error('Step 4 FAILED: Unexpected exception');
      $this->logger->error('- Exception type: @type', ['@type' => get_class($e)]);
      $this->logger->error('- Message: @message', ['@message' => $e->getMessage()]);
      $this->logger->error('- File: @file:@line', ['@file' => $e->getFile(), '@line' => $e->getLine()]);
      
      return FALSE;
    } finally {
      $this->logger->info('=== Mastodon connection test completed ===');
    }
  }

  /**
   * Post to Mastodon using AI-generated social media content.
   * 
   * @return bool
   *   TRUE if the post was successful, FALSE otherwise.
   */
  public function testPost(): bool {
    $this->logger->info('=== Starting Mastodon post with AI-generated content ===');
    
    // Step 1: Test connection first
    if (!$this->testConnection()) {
      $this->logger->error('Connection test failed, cannot proceed with post');
      return FALSE;
    }
    
    // Step 2: Generate AI social media content
    $this->logger->info('Step 2: Attempting to generate AI content...');
    $test_message = $this->generateTestContent();
    
    $this->logger->info('AI content generation result: @result', [
      '@result' => empty($test_message) ? 'EMPTY/FAILED' : 'SUCCESS (' . strlen($test_message) . ' chars)'
    ]);
    
    if (empty($test_message)) {
      // Fallback to basic message if AI generation fails
      // Keep it very short to handle low character limits
      $test_message = "🤖 Latest analysis from Truth Perspective #MediaAnalysis";
      $this->logger->warning('AI content generation failed, using fallback message');
    } else {
      $this->logger->info('Using AI-generated content for post');
    }

    // Ensure message fits within character limit
    $char_limit = $this->getInstanceCharacterLimit();
    if (strlen($test_message) > $char_limit) {
      $test_message = substr($test_message, 0, $char_limit - 10) . '... #Test';
      $this->logger->warning('Truncated test message to fit character limit of @limit chars', [
        '@limit' => $char_limit
      ]);
    }
    
    $this->logger->info('Step 2: Posting message (length: @length chars)', [
      '@length' => strlen($test_message)
    ]);
    
    // Step 3: Post the message
    $result = $this->postContent($test_message, [
      'visibility' => 'public'
    ]);
    
    if ($result === FALSE) {
      $this->logger->error('Post failed');
      return FALSE;
    }
    
    // Step 4: Success
    $this->logger->info('✅ Post successful!');
    if (isset($result['url'])) {
      $this->logger->info('Post URL: @url', ['@url' => $result['url']]);
    }
    if (isset($result['id'])) {
      $this->logger->info('Post ID: @id', ['@id' => $result['id']]);
    }
    
    $this->logger->info('=== Mastodon post completed successfully ===');
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCharacterLimit(): int {
    // Standard Mastodon limit, but some instances have much lower limits
    // This returns the theoretical maximum, actual validation happens in postContent()
    return 500;
  }

  /**
   * Get the actual character limit for this Mastodon instance.
   * 
   * @return int The actual character limit, or fallback to conservative value.
   */
  protected function getInstanceCharacterLimit(): int {
    static $cached_limit = null;
    
    if ($cached_limit !== null) {
      return $cached_limit;
    }

    try {
      $config = $this->configFactory->get('social_media_automation.settings');
      $server_url = $config->get('mastodon.server_url');
      
      if (empty($server_url)) {
        $cached_limit = 50; // Conservative fallback
        return $cached_limit;
      }

      // Query instance info
      $response = $this->httpClient->get($server_url . '/api/v1/instance', [
        'timeout' => 10,
        'http_errors' => false,
      ]);

      if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getBody()->getContents(), true);
        if (isset($data['configuration']['statuses']['max_characters'])) {
          $cached_limit = (int) $data['configuration']['statuses']['max_characters'];
          $this->logger->info('Detected Mastodon instance character limit: @limit', ['@limit' => $cached_limit]);
          return $cached_limit;
        }
      }
    } catch (\Exception $e) {
      $this->logger->warning('Could not detect instance character limit: @message', ['@message' => $e->getMessage()]);
    }

    // Fallback to very conservative limit if we can't detect
    $cached_limit = 50;
    $this->logger->info('Using conservative character limit fallback: @limit', ['@limit' => $cached_limit]);
    return $cached_limit;
  }

  /**
   * {@inheritdoc}
   */
  public function postContent(string $content, array $options = []): array|false {
    if (!$this->isConfigured()) {
      $this->logger->error('Mastodon not configured for posting');
      return FALSE;
    }

    $config = $this->configFactory->get('social_media_automation.settings');
    $server_url = $config->get('mastodon.server_url');
    $access_token = $config->get('mastodon.access_token');

    // Prepare post data
    $post_data = [
      'status' => $content,
    ];

    // Add optional parameters
    if (!empty($options['visibility'])) {
      $post_data['visibility'] = $options['visibility']; // public, unlisted, private, direct
    } else {
      $post_data['visibility'] = 'public';
    }

    if (!empty($options['reply_to'])) {
      $post_data['in_reply_to_id'] = $options['reply_to'];
    }

    if (!empty($options['sensitive'])) {
      $post_data['sensitive'] = $options['sensitive'];
      if (!empty($options['spoiler_text'])) {
        $post_data['spoiler_text'] = $options['spoiler_text'];
      }
    }

    try {
      $response = $this->httpClient->post($server_url . '/api/v1/statuses', [
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
          'Content-Type' => 'application/json',
        ],
        'json' => $post_data,
        'timeout' => 30,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      if (isset($data['id'])) {
        $this->logger->info('Successfully posted to Mastodon: @post_id', ['@post_id' => $data['id']]);
        return $data;
      } else {
        $this->logger->error('Mastodon API returned unexpected response: @response', ['@response' => print_r($data, TRUE)]);
        return FALSE;
      }

    } catch (RequestException $e) {
      $error_response = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response body';
      
      // Check for character limit error
      if (strpos($error_response, 'character limit') !== false || 
          strpos($error_response, 'Validation failed') !== false) {
        $this->logger->error('Mastodon post failed due to character limit. Content length: @length chars. Error: @message', [
          '@length' => strlen($content),
          '@message' => $e->getMessage()
        ]);
      } else {
        $this->logger->error('Failed to post to Mastodon: @message. Response: @response', [
          '@message' => $e->getMessage(),
          '@response' => $error_response
        ]);
      }
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatContent(string $content, array $context = []): string {
    // Mastodon supports markdown-like formatting and hashtags
    $formatted = $content;
    
    // Ensure content fits within character limit using instance-specific limit
    $limit = $this->getInstanceCharacterLimit();
    if (strlen($formatted) > $limit) {
      $formatted = substr($formatted, 0, $limit - 3) . '...';
    }
    
    // Add context-specific hashtags
    if (!empty($context['hashtags'])) {
      $hashtags = is_array($context['hashtags']) ? $context['hashtags'] : [$context['hashtags']];
      $hashtag_string = ' ' . implode(' ', array_map(function($tag) {
        return '#' . preg_replace('/[^a-zA-Z0-9]/', '', $tag);
      }, $hashtags));
      
      // Add hashtags if they fit
      if (strlen($formatted . $hashtag_string) <= $limit) {
        $formatted .= $hashtag_string;
      }
    }
    
    return $formatted;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationUrl(): string {
    $config = $this->configFactory->get('social_media_automation.settings');
    $server_url = $config->get('mastodon.server_url', 'https://mastodon.social');
    
    return $server_url . '/settings/applications';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredCredentials(): array {
    return [
      'server_url',
      'access_token',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateCredentials(array $credentials): array {
    $errors = [];
    
    if (empty($credentials['server_url'])) {
      $errors[] = 'Server URL is required';
    } elseif (!filter_var($credentials['server_url'], FILTER_VALIDATE_URL)) {
      $errors[] = 'Server URL must be a valid URL';
    }
    
    if (empty($credentials['client_id'])) {
      $errors[] = 'Client ID is required';
    }
    
    if (empty($credentials['client_secret'])) {
      $errors[] = 'Client Secret is required';
    }
    
    if (empty($credentials['access_token'])) {
      $errors[] = 'Access Token is required';
    }
    
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedFeatures(): array {
    return [
      'hashtags' => TRUE,
      'mentions' => TRUE,
      'media' => TRUE,
      'visibility_levels' => TRUE,
      'content_warnings' => TRUE,
      'replies' => TRUE,
      'threads' => TRUE,
      'polls' => TRUE,
    ];
  }

  /**
   * Generate AI-powered social media content for posting.
   *
   * @return string
   *   The generated social media content, or empty string if generation fails.
   */
  protected function generateTestContent(): string {
    $this->logger->info('Starting AI test content generation...');
    
    try {
      // Get most recent article
      $this->logger->info('Querying for most recent article...');
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'article')
        ->condition('status', 1)
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->accessCheck(FALSE);
      
      $nids = $query->execute();
      
      if (empty($nids)) {
        $this->logger->warning('No published articles found for AI test content generation');
        return '';
      }
      
      $nid = reset($nids);
      $this->logger->info('Found article @nid for content generation', ['@nid' => $nid]);
      
      $article = \Drupal\node\Entity\Node::load($nid);
      
      if (!$article) {
        $this->logger->warning('Could not load article @nid for test content', ['@nid' => $nid]);
        return '';
      }
      
      $this->logger->info('Article loaded successfully: @title', ['@title' => $article->getTitle()]);
      
      // Extract motivation analysis
      $motivation_analysis = '';
      if ($article->hasField('field_motivation_analysis') && !$article->get('field_motivation_analysis')->isEmpty()) {
        $motivation_analysis = $article->get('field_motivation_analysis')->value;
        $this->logger->info('Found motivation analysis field: @length characters', [
          '@length' => strlen($motivation_analysis)
        ]);
      } else {
        $this->logger->warning('No motivation analysis field or field is empty for article @nid', ['@nid' => $nid]);
        
        // Debug: Check what fields are actually available
        $field_definitions = $article->getFieldDefinitions();
        $available_fields = array_keys($field_definitions);
        $this->logger->info('Available fields on article: @fields', [
          '@fields' => implode(', ', $available_fields)
        ]);
        
        return '';
      }
      
      if (empty($motivation_analysis)) {
        $this->logger->warning('Motivation analysis field exists but is empty for article @nid', ['@nid' => $nid]);
        return '';
      }
      
      // Get article details
      $article_title = $article->getTitle();
      $article_url = $article->toUrl('canonical', ['absolute' => TRUE])->toString();
      
      $this->logger->info('Article details extracted: title=@title, url=@url', [
        '@title' => $article_title,
        '@url' => $article_url
      ]);
      
      // Check if AI service is available
      $container = \Drupal::getContainer();
      if (!$container->has('news_extractor.ai_processing')) {
        $this->logger->warning('AI processing service not available for test content');
        return '';
      }
      
      $ai_service = $container->get('news_extractor.ai_processing');
      $this->logger->info('AI processing service loaded successfully');
      
      // Build prompt for social media generation
      $prompt = $this->buildContentPrompt($article_title, $article_url, $motivation_analysis);
      $this->logger->info('Built prompt for AI: @length characters', ['@length' => strlen($prompt)]);
      
      // Call AI service
      $this->logger->info('Calling AI service for content generation...');
      $social_media_post = $ai_service->generateAnalysis($prompt, $article_title);
      
      if (empty($social_media_post)) {
        $this->logger->warning('AI service returned empty response for test content');
        return '';
      }
      
      $this->logger->info('AI service returned response: @length characters', [
        '@length' => strlen($social_media_post)
      ]);
      
      // Parse AI response if it's JSON
      if (is_string($social_media_post) && (strpos($social_media_post, '{') === 0)) {
        $this->logger->info('Parsing JSON response from AI...');
        $parsed = json_decode($social_media_post, TRUE);
        if (json_last_error() === JSON_ERROR_NONE && isset($parsed['content'])) {
          $social_media_post = $parsed['content'];
          $this->logger->info('Extracted content from JSON response');
        } else {
          $this->logger->warning('Failed to parse AI JSON response or missing content field');
        }
      }
      
      $this->logger->info('Successfully generated AI test content: @length characters', [
        '@length' => strlen($social_media_post)
      ]);
      
      // Log first 100 chars for debugging
      $this->logger->info('Generated content preview: @preview...', [
        '@preview' => substr($social_media_post, 0, 100)
      ]);
      
      return $social_media_post;
      
    } catch (\Exception $e) {
      $this->logger->error('AI test content generation failed with exception: @error', [
        '@error' => $e->getMessage()
      ]);
      $this->logger->error('Exception trace: @trace', [
        '@trace' => $e->getTraceAsString()
      ]);
      return '';
    }
  }

  /**
   * Build content generation prompt for social media post.
   *
   * @param string $article_title
   *   The article title.
   * @param string $article_url
   *   The article URL.
   * @param string $motivation_analysis
   *   The motivation analysis data.
   *
   * @return string
   *   The prompt for AI.
   */
  protected function buildContentPrompt($article_title, $article_url, $motivation_analysis): string {
    $prompt = "As a social scientist, create a compelling social media post for Mastodon based on the motivation analysis below. This is for The Truth Perspective platform.\n\n";
    
    $prompt .= "ARTICLE TITLE: {$article_title}\n\n";
    $prompt .= "ARTICLE URL: {$article_url}\n\n";
    $prompt .= "MOTIVATION ANALYSIS DATA:\n{$motivation_analysis}\n\n";
    
    $instance_limit = $this->getInstanceCharacterLimit();
    $prompt .= "REQUIREMENTS:\n";
    $prompt .= "- Write from a social scientist's analytical perspective\n";
    $prompt .= "- Keep under {$instance_limit} characters total (Mastodon instance limit)\n";
    $prompt .= "- Highlight the most compelling motivational insights from the analysis\n";
    $prompt .= "- Include 2-4 relevant hashtags (e.g., #MotivationAnalysis #SocialScience #MediaAnalysis)\n";
    $prompt .= "- Include the article URL\n";
    $prompt .= "- Focus on what drives the key players and what this reveals about societal patterns\n\n";
    
    $prompt .= "TONE: Professional yet accessible, insightful, thought-provoking\n\n";
    
    $prompt .= "Please respond with ONLY the social media post text, ready to publish. Do not include any additional commentary, explanation, or JSON formatting.";
    
    return $prompt;
  }

  /**
   * {@inheritdoc}
   */
  public function getRateLimits(): array {
    return [
      'posts_per_hour' => 300,  // Most Mastodon instances
      'posts_per_day' => 1000,
      'burst_limit' => 5,       // Posts per minute
    ];
  }

}
