<?php

namespace Drupal\social_media_automation\Service\Platform;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Twitter platform client for social media automation.
 * 
 * Handles OAuth 1.0a authentication and communication with Twitter API v1.1.
 * Preserves all working functionality from twitter_automation module.
 */
class TwitterClient implements PlatformInterface {

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
   * Twitter API v1.1 base URL.
   */
  const API_BASE_URL = 'https://api.twitter.com/1.1/';

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
    return 'Twitter';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName(): string {
    return 'twitter';
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigured(): bool {
    $config = $this->configFactory->get('social_media_automation.settings');
    $credentials = $this->getRequiredCredentials();
    
    foreach ($credentials as $credential) {
      if (empty($config->get("twitter.{$credential}"))) {
        return FALSE;
      }
    }
    
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function testConnection(): bool {
    if (!$this->isConfigured()) {
      return FALSE;
    }

    $config = $this->configFactory->get('social_media_automation.settings');
    $api_key = $config->get('twitter.api_key');
    $api_secret = $config->get('twitter.api_secret');
    $access_token = $config->get('twitter.access_token');
    $access_secret = $config->get('twitter.access_secret');

    // Use OAuth 1.0a to test account/verify_credentials endpoint
    $url = self::API_BASE_URL . 'account/verify_credentials.json';

    try {
      // Generate OAuth signature for GET request
      $oauth_params = [
        'oauth_consumer_key' => $api_key,
        'oauth_nonce' => $this->generateNonce(),
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_timestamp' => time(),
        'oauth_token' => $access_token,
        'oauth_version' => '1.0',
      ];

      // Create base string for signature (GET request, no additional params)
      ksort($oauth_params);
      $param_pairs = [];
      foreach ($oauth_params as $key => $value) {
        $param_pairs[] = rawurlencode($key) . '=' . rawurlencode($value);
      }
      $param_string = implode('&', $param_pairs);
      $base_string = 'GET&' . rawurlencode($url) . '&' . rawurlencode($param_string);

      // Create signing key and signature
      $signing_key = rawurlencode($api_secret) . '&' . rawurlencode($access_secret);
      $signature = base64_encode(hash_hmac('sha1', $base_string, $signing_key, true));
      $oauth_params['oauth_signature'] = $signature;

      // Build authorization header
      $auth_parts = [];
      foreach ($oauth_params as $key => $value) {
        $auth_parts[] = $key . '="' . rawurlencode($value) . '"';
      }
      $auth_header = 'OAuth ' . implode(', ', $auth_parts);

      $response = $this->httpClient->get($url, [
        'headers' => [
          'Authorization' => $auth_header,
        ],
        'timeout' => 10,
      ]);

      return $response->getStatusCode() === 200;

    } catch (RequestException $e) {
      $this->logger->error('Twitter connection test failed: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCharacterLimit(): int {
    return 280;
  }

  /**
   * {@inheritdoc}
   */
  public function postContent(string $content, array $options = []): array|false {
    if (!$this->isConfigured()) {
      $this->logger->error('Twitter not configured for posting');
      return FALSE;
    }

    $config = $this->configFactory->get('social_media_automation.settings');
    $api_key = $config->get('twitter.api_key');
    $api_secret = $config->get('twitter.api_secret');
    $access_token = $config->get('twitter.access_token');
    $access_secret = $config->get('twitter.access_secret');

    if (strlen($content) > 280) {
      $this->logger->warning('Tweet text too long: @length characters', ['@length' => strlen($content)]);
      $content = substr($content, 0, 277) . '...';
    }

    // Use Twitter API v1.1 statuses/update endpoint
    $url = self::API_BASE_URL . 'statuses/update.json';
    $params = ['status' => $content];

    // Add optional parameters
    if (!empty($options['reply_to'])) {
      $params['in_reply_to_status_id'] = $options['reply_to'];
    }

    try {
      // Generate OAuth signature
      $oauth_params = [
        'oauth_consumer_key' => $api_key,
        'oauth_nonce' => $this->generateNonce(),
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_timestamp' => time(),
        'oauth_token' => $access_token,
        'oauth_version' => '1.0',
      ];

      // Create base string for signature
      $base_params = array_merge($oauth_params, $params);
      ksort($base_params);
      
      // Manually build query string to ensure proper encoding
      $param_pairs = [];
      foreach ($base_params as $key => $value) {
        $param_pairs[] = rawurlencode($key) . '=' . rawurlencode($value);
      }
      $param_string = implode('&', $param_pairs);
      
      $base_string = 'POST&' . rawurlencode($url) . '&' . rawurlencode($param_string);

      // Create signing key and signature
      $signing_key = rawurlencode($api_secret) . '&' . rawurlencode($access_secret);
      $signature = base64_encode(hash_hmac('sha1', $base_string, $signing_key, true));
      $oauth_params['oauth_signature'] = $signature;

      // Build authorization header
      $auth_parts = [];
      foreach ($oauth_params as $key => $value) {
        $auth_parts[] = $key . '="' . rawurlencode($value) . '"';
      }
      $auth_header = 'OAuth ' . implode(', ', $auth_parts);

      // Make the request
      $response = $this->httpClient->post($url, [
        'headers' => [
          'Authorization' => $auth_header,
          'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'form_params' => $params,
        'timeout' => 30,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      if (isset($data['id_str'])) {
        $this->logger->info('Successfully posted to Twitter: @tweet_id', ['@tweet_id' => $data['id_str']]);
        return $data;
      } else {
        $this->logger->error('Twitter API returned unexpected response: @response', ['@response' => print_r($data, TRUE)]);
        return FALSE;
      }

    } catch (RequestException $e) {
      $error_response = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response body';
      $this->logger->error('Failed to post to Twitter: @message. Full response: @response', [
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
    // Twitter-specific formatting
    $formatted = $content;
    
    // Ensure content fits within character limit
    $limit = $this->getCharacterLimit();
    if (strlen($formatted) > $limit) {
      $formatted = substr($formatted, 0, $limit - 3) . '...';
    }
    
    // Add context-specific hashtags if they fit
    if (!empty($context['hashtags'])) {
      $hashtags = is_array($context['hashtags']) ? $context['hashtags'] : [$context['hashtags']];
      $hashtag_string = ' ' . implode(' ', array_map(function($tag) {
        return '#' . preg_replace('/[^a-zA-Z0-9_]/', '', $tag);
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
    return 'https://developer.twitter.com/en/portal/dashboard';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredCredentials(): array {
    return [
      'api_key',
      'api_secret',
      'access_token',
      'access_secret',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateCredentials(array $credentials): array {
    $errors = [];
    
    if (empty($credentials['api_key'])) {
      $errors[] = 'API Key is required';
    }
    
    if (empty($credentials['api_secret'])) {
      $errors[] = 'API Secret is required';
    }
    
    if (empty($credentials['access_token'])) {
      $errors[] = 'Access Token is required';
    }
    
    if (empty($credentials['access_secret'])) {
      $errors[] = 'Access Token Secret is required';
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
      'replies' => TRUE,
      'threads' => TRUE,
      'retweets' => TRUE,
      'quotes' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRateLimits(): array {
    return [
      'posts_per_hour' => 300,    // Twitter API v1.1 limit
      'posts_per_day' => 2400,
      'burst_limit' => 5,         // Posts per minute
      'note' => 'Requires paid API access ($100/month minimum)',
    ];
  }

  /**
   * Generate a random nonce for OAuth requests.
   *
   * @return string
   *   A random 32-character string.
   */
  private function generateNonce(): string {
    return bin2hex(random_bytes(16));
  }

}
