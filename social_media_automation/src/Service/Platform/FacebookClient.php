<?php

namespace Drupal\social_media_automation\Service\Platform;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Facebook platform client for social media automation.
 * 
 * Handles authentication and communication with Facebook API.
 */
class FacebookClient implements PlatformInterface {

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
    return 'Facebook';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName(): string {
    return 'facebook';
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigured(): bool {
    $config = $this->configFactory->get('social_media_automation.settings');
    $credentials = $this->getRequiredCredentials();
    
    foreach ($credentials as $credential) {
      if (empty($config->get("facebook.{$credential}"))) {
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

    try {
      // Facebook API connection test - get page info
      $config = $this->configFactory->get('social_media_automation.settings');
      $access_token = $config->get('facebook.access_token');
      $page_id = $config->get('facebook.page_id');

      $response = $this->httpClient->request('GET', "https://graph.facebook.com/v18.0/{$page_id}", [
        'query' => [
          'access_token' => $access_token,
          'fields' => 'id,name,access_token'
        ],
        'timeout' => 10,
      ]);

      return $response->getStatusCode() === 200;

    } catch (RequestException $e) {
      $this->logger->error('Facebook connection test failed: @error', ['@error' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCharacterLimit(): int {
    return 63206; // Facebook post character limit
  }

  /**
   * {@inheritdoc}
   */
  public function postContent(string $content, array $options = []): array|false {
    if (!$this->isConfigured()) {
      return ['success' => FALSE, 'error' => 'Facebook not configured'];
    }

    try {
      $config = $this->configFactory->get('social_media_automation.settings');
      $access_token = $config->get('facebook.access_token');
      $page_id = $config->get('facebook.page_id');

      // Format content for Facebook API
      $formatted_content = $this->formatContent($content, $options);

      $post_data = [
        'message' => $formatted_content,
        'access_token' => $access_token
      ];

      $response = $this->httpClient->request('POST', "https://graph.facebook.com/v18.0/{$page_id}/feed", [
        'form_params' => $post_data,
        'timeout' => 30,
      ]);

      if ($response->getStatusCode() === 200) {
        $this->logger->info('Successfully posted to Facebook');
        return ['success' => TRUE, 'response' => json_decode($response->getBody(), TRUE)];
      }

      return ['success' => FALSE, 'error' => 'Unexpected response code: ' . $response->getStatusCode()];

    } catch (RequestException $e) {
      $error = 'Facebook posting failed: ' . $e->getMessage();
      $this->logger->error($error);
      return ['success' => FALSE, 'error' => $error];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatContent(string $content, array $context = []): string {
    // Facebook has generous character limits
    // Focus on engagement and platform-appropriate formatting
    
    $formatted = $content;
    
    // Add hashtags if provided
    if (!empty($context['hashtags'])) {
      $hashtags = array_map(function($tag) {
        return '#' . str_replace(' ', '', ucwords($tag));
      }, $context['hashtags']);
      $formatted .= "\n\n" . implode(' ', $hashtags);
    }
    
    return $formatted;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationUrl(): string {
    $config = $this->configFactory->get('social_media_automation.settings');
    $app_id = $config->get('facebook.app_id');
    $redirect_uri = $config->get('facebook.redirect_uri') ?: 'https://thetruthperspective.org/admin/config/services/social-media-automation/facebook/callback';
    
    $params = [
      'client_id' => $app_id,
      'redirect_uri' => $redirect_uri,
      'scope' => 'pages_manage_posts,pages_read_engagement',
      'response_type' => 'code',
    ];
    
    return 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredCredentials(): array {
    return [
      'app_id',
      'app_secret',
      'access_token',
      'page_id'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedFeatures(): array {
    return [
      'hashtags' => TRUE,
      'mentions' => TRUE,
      'media' => TRUE,
      'threads' => FALSE,
      'content_warnings' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateCredentials(array $credentials): array {
    $errors = [];
    
    if (empty($credentials['app_id'])) {
      $errors[] = 'App ID is required';
    }
    
    if (empty($credentials['app_secret'])) {
      $errors[] = 'App Secret is required';
    }
    
    if (empty($credentials['access_token'])) {
      $errors[] = 'Access Token is required';
    }
    
    if (empty($credentials['page_id'])) {
      $errors[] = 'Page ID is required';
    }
    
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function getRateLimits(): array {
    return [
      'posts_per_hour' => 25,   // Facebook Graph API limits
      'posts_per_day' => 200,
      'burst_limit' => 1,       // Posts per minute
    ];
  }

}
