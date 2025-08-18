<?php

namespace Drupal\social_media_automation\Service\Platform;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Mastodon platform client for social media automation.
 * 
 * Handles authentication and communication with Mastodon API.
 */
class MastodonClient implements PlatformInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger service.
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
    
    $this->logger->debug('Checking Mastodon configuration...');
    
    foreach ($credentials as $credential) {
      $value = $config->get("mastodon.{$credential}");
      if (empty($value)) {
        $this->logger->debug('Missing credential: mastodon.@credential', ['@credential' => $credential]);
        return FALSE;
      } else {
        $this->logger->debug('Credential present: mastodon.@credential (@length chars)', [
          '@credential' => $credential,
          '@length' => strlen($value),
        ]);
      }
    }
    
    $this->logger->debug('All Mastodon credentials present');
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function testConnection(): bool {
    $this->logger->info('=== Starting Mastodon connection test ===');
    
    // Step 1: Check if configured
    if (!$this->isConfigured()) {
      $config = $this->configFactory->get('social_media_automation.settings');
      $server_url = $config->get('mastodon.server_url');
      $access_token = $config->get('mastodon.access_token');
      
      $this->logger->error('Mastodon not configured. Missing credentials:');
      $this->logger->error('- server_url: @server_url', ['@server_url' => $server_url ? 'Present' : 'MISSING']);
      $this->logger->error('- access_token: @access_token', ['@access_token' => $access_token ? 'Present (' . strlen($access_token) . ' chars)' : 'MISSING']);
      
      return FALSE;
    }

    $config = $this->configFactory->get('social_media_automation.settings');
    $server_url = $config->get('mastodon.server_url');
    $access_token = $config->get('mastodon.access_token');

    $this->logger->info('Step 1: Configuration check passed');
    $this->logger->info('- Server URL: @server', ['@server' => $server_url]);
    $this->logger->info('- Access Token: @token_length chars, ends with @token_end', [
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
   * Test Mastodon by posting a "Hello World" message.
   * 
   * @return bool
   *   TRUE if the test post was successful, FALSE otherwise.
   */
  public function testPost(): bool {
    $this->logger->info('=== Starting Mastodon test post ===');
    
    // Step 1: Test connection first
    if (!$this->testConnection()) {
      $this->logger->error('Connection test failed, cannot proceed with test post');
      return FALSE;
    }
    
    // Step 2: Create test message
    $timestamp = date('Y-m-d H:i:s T');
    $test_message = "🤖 Hello World from The Truth Perspective!\n\nThis is a test post to verify Mastodon integration is working.\n\nTimestamp: {$timestamp}\n\n#TestPost #TheTruthPerspective";
    
    $this->logger->info('Step 2: Posting test message (length: @length chars)', [
      '@length' => strlen($test_message)
    ]);
    
    // Step 3: Post the message
    $result = $this->postContent($test_message, [
      'visibility' => 'public'
    ]);
    
    if ($result === FALSE) {
      $this->logger->error('Test post failed');
      return FALSE;
    }
    
    // Step 4: Success
    $this->logger->info('✅ Test post successful!');
    if (isset($result['url'])) {
      $this->logger->info('Post URL: @url', ['@url' => $result['url']]);
    }
    if (isset($result['id'])) {
      $this->logger->info('Post ID: @id', ['@id' => $result['id']]);
    }
    
    $this->logger->info('=== Mastodon test post completed successfully ===');
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCharacterLimit(): int {
    return 500; // Standard Mastodon limit, some instances allow more
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
      $this->logger->error('Failed to post to Mastodon: @message. Response: @response', [
        '@message' => $e->getMessage(),
        '@response' => $error_response
      ]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatContent(string $content, array $context = []): string {
    // Mastodon supports markdown-like formatting and hashtags
    $formatted = $content;
    
    // Ensure content fits within character limit
    $limit = $this->getCharacterLimit();
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
