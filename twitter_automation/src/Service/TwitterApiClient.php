<?php

namespace Drupal\twitter_automation\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Twitter API client service for posting content.
 * 
 * Handles authentication and communication with Twitter API v2.
 */
class TwitterApiClient {

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
    $this->logger = $logger_factory->get('twitter_automation');
    $this->httpClient = $http_client;
  }

  /**
   * Post a tweet to Twitter using OAuth 1.0a.
   *
   * @param string $text
   *   The tweet text content.
   * @param array $options
   *   Optional parameters like media, reply settings, etc.
   *
   * @return array|false
   *   Response data from Twitter API or FALSE on failure.
   */
  public function postTweet(string $text, array $options = []) {
    $config = $this->configFactory->get('twitter_automation.settings');
    $api_key = $config->get('api_key');
    $api_secret = $config->get('api_secret');
    $access_token = $config->get('access_token');
    $access_secret = $config->get('access_secret');
    
    if (empty($api_key) || empty($api_secret) || empty($access_token) || empty($access_secret)) {
      $this->logger->error('Twitter OAuth credentials not configured');
      return FALSE;
    }

    if (strlen($text) > 280) {
      $this->logger->warning('Tweet text too long: @length characters', ['@length' => strlen($text)]);
      $text = substr($text, 0, 277) . '...';
    }

    // Use Twitter API v1.1 statuses/update endpoint
    $url = self::API_BASE_URL . 'statuses/update.json';
    $params = ['status' => $text];

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

      // Debug OAuth signature generation
      $this->logger->info('OAuth Debug for POST - URL: @url, Base String: @base, Signature: @sig', [
        '@url' => $url,
        '@base' => substr($base_string, 0, 200) . '...',
        '@sig' => $signature
      ]);

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
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      if (isset($data['id_str'])) {
        $this->logger->info('Successfully posted tweet: @tweet_id', ['@tweet_id' => $data['id_str']]);
        return $data;
      } else {
        $this->logger->error('Twitter API returned unexpected response: @response', ['@response' => print_r($data, TRUE)]);
        return FALSE;
      }

    } catch (RequestException $e) {
      $this->logger->error('Failed to post tweet: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Test the Twitter API connection.
   *
   * @return bool
   *   TRUE if connection is successful, FALSE otherwise.
   */
  public function testConnection(): bool {
    $config = $this->configFactory->get('twitter_automation.settings');
    $bearer_token = $config->get('bearer_token');
    
    if (empty($bearer_token)) {
      $this->logger->warning('No Bearer Token configured - Twitter API connection cannot be tested');
      return FALSE;
    }

    try {
      $response = $this->httpClient->get(self::API_BASE_URL . 'users/me', [
        'headers' => [
          'Authorization' => 'Bearer ' . $bearer_token,
        ],
      ]);

      return $response->getStatusCode() === 200;

    } catch (RequestException $e) {
      $this->logger->error('Twitter API connection test failed: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Get user information from Twitter API.
   *
   * @return array|false
   *   User data or FALSE on failure.
   */
  public function getUserInfo() {
    $config = $this->configFactory->get('twitter_automation.settings');
    $bearer_token = $config->get('bearer_token');
    
    if (empty($bearer_token)) {
      return FALSE;
    }

    try {
      $response = $this->httpClient->get(self::API_BASE_URL . 'users/me', [
        'headers' => [
          'Authorization' => 'Bearer ' . $bearer_token,
        ],
      ]);

      return json_decode($response->getBody()->getContents(), TRUE);

    } catch (RequestException $e) {
      $this->logger->error('Failed to get user info: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Generate a random nonce for OAuth requests.
   *
   * @return string
   *   A random 32-character string.
   */
  private function generateNonce() {
    return bin2hex(random_bytes(16));
  }

}
