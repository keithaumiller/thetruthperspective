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
    
    foreach ($credentials as $credential) {
      if (empty($config->get("mastodon.{$credential}"))) {
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
    $server_url = $config->get('mastodon.server_url');
    $access_token = $config->get('mastodon.access_token');

    try {
      $response = $this->httpClient->get($server_url . '/api/v1/accounts/verify_credentials', [
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
        ],
        'timeout' => 10,
      ]);

      return $response->getStatusCode() === 200;

    } catch (RequestException $e) {
      $this->logger->error('Mastodon connection test failed: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
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
      'client_id',
      'client_secret', 
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
