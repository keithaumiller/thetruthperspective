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
   * Twitter API base URL.
   */
  const API_BASE_URL = 'https://api.twitter.com/2/';

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
   * Post a tweet to Twitter.
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
    $bearer_token = $config->get('bearer_token');
    
    if (empty($bearer_token)) {
      $this->logger->error('Twitter Bearer Token not configured');
      return FALSE;
    }

    if (strlen($text) > 280) {
      $this->logger->warning('Tweet text too long: @length characters', ['@length' => strlen($text)]);
      $text = substr($text, 0, 277) . '...';
    }

    $payload = [
      'text' => $text,
    ];

    // Add optional parameters
    if (!empty($options['reply_to'])) {
      $payload['reply'] = ['in_reply_to_tweet_id' => $options['reply_to']];
    }

    if (!empty($options['media_ids'])) {
      $payload['media'] = ['media_ids' => $options['media_ids']];
    }

    try {
      $response = $this->httpClient->post(self::API_BASE_URL . 'tweets', [
        'headers' => [
          'Authorization' => 'Bearer ' . $bearer_token,
          'Content-Type' => 'application/json',
        ],
        'json' => $payload,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      if (isset($data['data']['id'])) {
        $this->logger->info('Successfully posted tweet: @tweet_id', ['@tweet_id' => $data['data']['id']]);
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

}
