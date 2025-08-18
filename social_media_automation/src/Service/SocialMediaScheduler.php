<?php

namespace Drupal\social_media_automation\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;

/**
 * Social media scheduler service for managing automated posting across platforms.
 * 
 * Handles scheduling and execution of automated posts to multiple social media platforms.
 */
class SocialMediaScheduler {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The platform manager.
   *
   * @var \Drupal\social_media_automation\Service\PlatformManager
   */
  protected $platformManager;

  /**
   * The content generator.
   *
   * @var \Drupal\social_media_automation\Service\ContentGenerator
   */
  protected $contentGenerator;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    PlatformManager $platform_manager,
    ContentGenerator $content_generator,
    QueueFactory $queue_factory,
    StateInterface $state,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->configFactory = $config_factory;
    $this->platformManager = $platform_manager;
    $this->contentGenerator = $content_generator;
    $this->queueFactory = $queue_factory;
    $this->state = $state;
    $this->logger = $logger_factory->get('social_media_automation');
  }

  /**
   * Execute scheduled social media posts.
   * 
   * Called by cron to check if posts should be sent.
   */
  public function executeCronPosts(): void {
    $config = $this->configFactory->get('social_media_automation.settings');
    
    if (!$config->get('enabled')) {
      return;
    }

    $last_morning_post = $this->state->get('social_media_automation.last_morning_post', 0);
    $last_evening_post = $this->state->get('social_media_automation.last_evening_post', 0);
    
    $current_time = time();
    $current_hour = (int) date('H', $current_time);
    $today = date('Y-m-d', $current_time);
    
    // Morning post (8 AM - 12 PM)
    if ($current_hour >= 8 && $current_hour < 12) {
      $last_morning_date = date('Y-m-d', $last_morning_post);
      if ($last_morning_date !== $today) {
        $this->queueMorningPost();
      }
    }
    
    // Evening post (6 PM - 10 PM)
    if ($current_hour >= 18 && $current_hour < 22) {
      $last_evening_date = date('Y-m-d', $last_evening_post);
      if ($last_evening_date !== $today) {
        $this->queueEveningPost();
      }
    }
  }

  /**
   * Queue morning post.
   */
  protected function queueMorningPost(): void {
    $queue = $this->queueFactory->get('social_media_automation_posts');
    
    $item = [
      'type' => 'morning',
      'content_type' => 'analytics_summary',
      'timestamp' => time(),
    ];
    
    $queue->createItem($item);
    $this->logger->info('Morning social media post queued');
  }

  /**
   * Queue evening post.
   */
  protected function queueEveningPost(): void {
    $queue = $this->queueFactory->get('social_media_automation_posts');
    
    // Alternate between different content types for evening posts
    $evening_types = ['trending_topics', 'bias_insight'];
    $last_evening_type = $this->state->get('social_media_automation.last_evening_type', 'trending_topics');
    
    $next_type = ($last_evening_type === 'trending_topics') ? 'bias_insight' : 'trending_topics';
    
    $item = [
      'type' => 'evening',
      'content_type' => $next_type,
      'timestamp' => time(),
    ];
    
    $queue->createItem($item);
    $this->state->set('social_media_automation.last_evening_type', $next_type);
    $this->logger->info('Evening social media post queued with type: @type', ['@type' => $next_type]);
  }

  /**
   * Process a queued social media post.
   *
   * @param array $data
   *   The queue item data.
   *
   * @return bool
   *   TRUE if successful, FALSE otherwise.
   */
  public function processQueuedPost(array $data): bool {
    try {
      $config = $this->configFactory->get('social_media_automation.settings');
      
      if (!$config->get('enabled')) {
        $this->logger->info('Social media automation disabled, skipping post');
        return TRUE;
      }

      // Generate content for all enabled platforms
      $platform_content = $this->contentGenerator->generateContent($data['content_type']);
      
      if (empty($platform_content)) {
        $this->logger->error('Failed to generate content for type: @type', ['@type' => $data['content_type']]);
        return FALSE;
      }

      $success_count = 0;
      $total_platforms = count($platform_content);
      
      // Post to each enabled platform
      foreach ($platform_content as $platform_name => $content) {
        $platform = $this->platformManager->getPlatform($platform_name);
        
        if (!$platform) {
          $this->logger->error('Platform not found: @platform', ['@platform' => $platform_name]);
          continue;
        }

        try {
          $result = $platform->postContent($content);
          
          if ($result) {
            $success_count++;
            $this->logger->info('Successfully posted to @platform: @content', [
              '@platform' => $platform->getName(),
              '@content' => substr($content, 0, 100) . '...',
            ]);
          } else {
            $this->logger->error('Failed to post to @platform', ['@platform' => $platform->getName()]);
          }
          
        } catch (\Exception $e) {
          $this->logger->error('Exception posting to @platform: @message', [
            '@platform' => $platform->getName(),
            '@message' => $e->getMessage(),
          ]);
        }
      }
      
      // Consider it successful if at least one platform succeeded
      $overall_success = $success_count > 0;
      
      if ($overall_success) {
        // Update tracking state
        if ($data['type'] === 'morning') {
          $this->state->set('social_media_automation.last_morning_post', time());
        } elseif ($data['type'] === 'evening') {
          $this->state->set('social_media_automation.last_evening_post', time());
        }
        
        $this->logger->info('Completed @type post to @success/@total platforms', [
          '@type' => $data['type'],
          '@success' => $success_count,
          '@total' => $total_platforms,
        ]);
      }
      
      return $overall_success;

    } catch (\Exception $e) {
      $this->logger->error('Exception processing social media post: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Manually send a test post to all enabled platforms.
   *
   * @param string $content_type
   *   The type of content to generate.
   *
   * @return array
   *   Array of results keyed by platform name.
   */
  public function sendTestPost(string $content_type = 'analytics_summary'): array {
    $results = [];
    
    try {
      // Generate content for all enabled platforms
      $platform_content = $this->contentGenerator->generateContent($content_type);
      
      if (empty($platform_content)) {
        $this->logger->error('Failed to generate test content');
        return [];
      }

      // Post to each enabled platform
      foreach ($platform_content as $platform_name => $content) {
        $platform = $this->platformManager->getPlatform($platform_name);
        
        if (!$platform) {
          $results[$platform_name] = [
            'success' => FALSE,
            'error' => 'Platform not found',
          ];
          continue;
        }

        try {
          // Add test prefix
          $test_content = "🧪 TEST: " . $content;
          
          // Use platform's formatContent to ensure proper length handling
          $test_content = $platform->formatContent($test_content);
          
          $result = $platform->postContent($test_content);
          
          $results[$platform_name] = [
            'success' => $result,
            'content' => substr($test_content, 0, 100) . '...',
          ];
          
          if ($result) {
            $this->logger->info('Successfully posted test to @platform', ['@platform' => $platform->getName()]);
          } else {
            $this->logger->error('Failed to post test to @platform', ['@platform' => $platform->getName()]);
          }
          
        } catch (\Exception $e) {
          $results[$platform_name] = [
            'success' => FALSE,
            'error' => $e->getMessage(),
          ];
          
          $this->logger->error('Exception sending test to @platform: @message', [
            '@platform' => $platform->getName(),
            '@message' => $e->getMessage(),
          ]);
        }
      }

    } catch (\Exception $e) {
      $this->logger->error('Exception sending test posts: @message', ['@message' => $e->getMessage()]);
    }
    
    return $results;
  }

  /**
   * Send test post to specific platform.
   *
   * @param string $platform_name
   *   The machine name of the platform.
   * @param string $content_type
   *   The type of content to generate.
   *
   * @return bool
   *   TRUE if successful, FALSE otherwise.
   */
  public function sendTestPostToPlatform(string $platform_name, string $content_type = 'analytics_summary'): bool {
    try {
      $platform = $this->platformManager->getPlatform($platform_name);
      
      if (!$platform) {
        $this->logger->error('Platform not found: @platform', ['@platform' => $platform_name]);
        return FALSE;
      }

      // Generate content for all platforms (to ensure proper adaptation)
      $platform_content = $this->contentGenerator->generateContent($content_type);
      
      if (empty($platform_content[$platform_name])) {
        $this->logger->error('No content generated for platform: @platform', ['@platform' => $platform_name]);
        return FALSE;
      }

      $content = $platform_content[$platform_name];
      
      // Add test prefix
      $test_content = "🧪 TEST: " . $content;
      
      // Use platform's formatContent to ensure proper length handling
      $test_content = $platform->formatContent($test_content);
      
      $result = $platform->postContent($test_content);
      
      if ($result) {
        $this->logger->info('Successfully posted test to @platform', ['@platform' => $platform->getName()]);
      } else {
        $this->logger->error('Failed to post test to @platform', ['@platform' => $platform->getName()]);
      }
      
      return $result;

    } catch (\Exception $e) {
      $this->logger->error('Exception sending test to @platform: @message', [
        '@platform' => $platform_name,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Get statistics about automated posting.
   *
   * @return array
   *   Array of statistics.
   */
  public function getStats(): array {
    $last_morning_post = $this->state->get('social_media_automation.last_morning_post', 0);
    $last_evening_post = $this->state->get('social_media_automation.last_evening_post', 0);
    $last_evening_type = $this->state->get('social_media_automation.last_evening_type', 'trending_topics');
    
    $queue = $this->queueFactory->get('social_media_automation_posts');
    $queue_count = $queue->numberOfItems();
    
    // Get enabled platforms
    $enabled_platforms = $this->platformManager->getEnabledPlatforms();
    $platform_names = array_map(function($platform) {
      return $platform->getName();
    }, $enabled_platforms);
    
    return [
      'enabled_platforms' => $platform_names,
      'enabled_platform_count' => count($enabled_platforms),
      'last_morning_post' => $last_morning_post,
      'last_evening_post' => $last_evening_post,
      'last_evening_type' => $last_evening_type,
      'queue_count' => $queue_count,
      'last_morning_date' => $last_morning_post ? date('Y-m-d H:i:s', $last_morning_post) : 'Never',
      'last_evening_date' => $last_evening_post ? date('Y-m-d H:i:s', $last_evening_post) : 'Never',
    ];
  }

  /**
   * Get statistics for a specific platform.
   *
   * @param string $platform_name
   *   The machine name of the platform.
   *
   * @return array
   *   Array of platform-specific statistics.
   */
  public function getPlatformStats(string $platform_name): array {
    $platform = $this->platformManager->getPlatform($platform_name);
    
    if (!$platform) {
      return [];
    }

    $last_post_key = "social_media_automation.{$platform_name}.last_post";
    $post_count_key = "social_media_automation.{$platform_name}.post_count";
    $last_error_key = "social_media_automation.{$platform_name}.last_error";
    
    $last_post = $this->state->get($last_post_key, 0);
    $post_count = $this->state->get($post_count_key, 0);
    $last_error = $this->state->get($last_error_key, '');
    
    return [
      'platform_name' => $platform->getName(),
      'platform_machine_name' => $platform_name,
      'enabled' => $platform->isEnabled(),
      'last_post' => $last_post,
      'last_post_date' => $last_post ? date('Y-m-d H:i:s', $last_post) : 'Never',
      'total_posts' => $post_count,
      'last_error' => $last_error,
      'character_limit' => $platform->getCharacterLimit(),
      'supported_features' => $platform->getSupportedFeatures(),
    ];
  }

  /**
   * Update platform statistics after a post.
   *
   * @param string $platform_name
   *   The machine name of the platform.
   * @param bool $success
   *   Whether the post was successful.
   * @param string $error
   *   Error message if post failed.
   */
  public function updatePlatformStats(string $platform_name, bool $success, string $error = ''): void {
    $last_post_key = "social_media_automation.{$platform_name}.last_post";
    $post_count_key = "social_media_automation.{$platform_name}.post_count";
    $last_error_key = "social_media_automation.{$platform_name}.last_error";
    
    if ($success) {
      $this->state->set($last_post_key, time());
      $current_count = $this->state->get($post_count_key, 0);
      $this->state->set($post_count_key, $current_count + 1);
      $this->state->set($last_error_key, ''); // Clear any previous error
    } else {
      $this->state->set($last_error_key, $error);
    }
  }

}
