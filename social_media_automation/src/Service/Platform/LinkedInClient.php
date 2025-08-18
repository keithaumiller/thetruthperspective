<?php

namespace Drupal\social_media_automation\Service\Platform;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * LinkedIn platform client for social media automation.
 * 
 * Handles authentication and communication with LinkedIn API.
 */
class LinkedInClient implements PlatformInterface {

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
    return 'LinkedIn';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName(): string {
    return 'linkedin';
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigured(): bool {
    $config = $this->configFactory->get('social_media_automation.settings');
    $credentials = $this->getRequiredCredentials();
    
    foreach ($credentials as $credential) {
      if (empty($config->get("linkedin.{$credential}"))) {
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
      // LinkedIn API connection test - get profile info
      $config = $this->configFactory->get('social_media_automation.settings');
      $access_token = $config->get('linkedin.access_token');

      $response = $this->httpClient->request('GET', 'https://api.linkedin.com/v2/people/~', [
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
          'X-Restli-Protocol-Version' => '2.0.0',
        ],
        'timeout' => 10,
      ]);

      return $response->getStatusCode() === 200;

    } catch (RequestException $e) {
      $this->logger->error('LinkedIn connection test failed: @error', ['@error' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCharacterLimit(): int {
    return 3000; // LinkedIn personal posts limit
  }

  /**
   * {@inheritdoc}
   */
  public function postContent(string $content, array $options = []): array|false {
    if (!$this->isConfigured()) {
      return ['success' => FALSE, 'error' => 'LinkedIn not configured'];
    }

    try {
      $config = $this->configFactory->get('social_media_automation.settings');
      $access_token = $config->get('linkedin.access_token');

      // Format content for LinkedIn API
      $formatted_content = $this->formatContent($content, $options);

      $post_data = [
        'author' => 'urn:li:person:' . $config->get('linkedin.person_id'),
        'lifecycleState' => 'PUBLISHED',
        'specificContent' => [
          'com.linkedin.ugc.ShareContent' => [
            'shareCommentary' => [
              'text' => $formatted_content
            ],
            'shareMediaCategory' => 'NONE'
          ]
        ],
        'visibility' => [
          'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
        ]
      ];

      $response = $this->httpClient->request('POST', 'https://api.linkedin.com/v2/ugcPosts', [
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
          'Content-Type' => 'application/json',
          'X-Restli-Protocol-Version' => '2.0.0',
        ],
        'json' => $post_data,
        'timeout' => 30,
      ]);

      if ($response->getStatusCode() === 201) {
        $this->logger->info('Successfully posted to LinkedIn');
        return ['success' => TRUE, 'response' => json_decode($response->getBody(), TRUE)];
      }

      return ['success' => FALSE, 'error' => 'Unexpected response code: ' . $response->getStatusCode()];

    } catch (RequestException $e) {
      $error = 'LinkedIn posting failed: ' . $e->getMessage();
      $this->logger->error($error);
      return ['success' => FALSE, 'error' => $error];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatContent(string $content, array $context = []): string {
    // LinkedIn doesn't have strict character limits like Twitter
    // Focus on professional tone and platform-appropriate formatting
    
    $formatted = $content;
    
    // Ensure professional hashtags
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
    $client_id = $config->get('linkedin.client_id');
    $redirect_uri = $config->get('linkedin.redirect_uri') ?: 'https://thetruthperspective.org/admin/config/services/social-media-automation/linkedin/callback';
    
    $params = [
      'response_type' => 'code',
      'client_id' => $client_id,
      'redirect_uri' => $redirect_uri,
      'scope' => 'r_liteprofile w_member_social',
    ];
    
    return 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query($params);
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredCredentials(): array {
    return [
      'client_id',
      'client_secret', 
      'access_token',
      'person_id'
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
    
    if (empty($credentials['client_id'])) {
      $errors[] = 'Client ID is required';
    }
    
    if (empty($credentials['client_secret'])) {
      $errors[] = 'Client Secret is required';
    }
    
    if (empty($credentials['access_token'])) {
      $errors[] = 'Access Token is required';
    }
    
    if (empty($credentials['person_id'])) {
      $errors[] = 'Person ID is required';
    }
    
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function getRateLimits(): array {
    return [
      'posts_per_hour' => 25,   // LinkedIn API limits
      'posts_per_day' => 100,
      'burst_limit' => 2,       // Posts per minute
    ];
  }

}
