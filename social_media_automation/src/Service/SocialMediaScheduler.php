<?php

namespace Drupal\social_media_automation\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Drupal\social_media_automation\Traits\ConfigurableLoggingTrait;

/**
 * Social media scheduler service for managing automated posting across platforms.
 * 
 * Handles scheduling and execution of automated posts to multiple social media platforms.
 */
class SocialMediaScheduler {

  use ConfigurableLoggingTrait;

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

    $current_time = time();
    $current_hour = (int) date('H', $current_time);
    $today = date('Y-m-d', $current_time);
    
    // Three daily posts: Morning (8-10 AM), Afternoon (12-2 PM), Evening (6-8 PM)
    $posting_windows = [
      'morning' => ['start' => 8, 'end' => 10, 'state_key' => 'last_morning_post'],
      'afternoon' => ['start' => 12, 'end' => 14, 'state_key' => 'last_afternoon_post'],
      'evening' => ['start' => 18, 'end' => 20, 'state_key' => 'last_evening_post'],
    ];
    
    foreach ($posting_windows as $window_name => $window) {
      if ($current_hour >= $window['start'] && $current_hour < $window['end']) {
        $last_post = $this->state->get('social_media_automation.' . $window['state_key'], 0);
        $last_post_date = date('Y-m-d', $last_post);
        
        if ($last_post_date !== $today) {
          $this->queueScheduledPost($window_name);
          $this->state->set('social_media_automation.' . $window['state_key'], $current_time);
        }
      }
    }
  }

  /**
   * Queue scheduled post for specific time window.
   */
  protected function queueScheduledPost(string $window_name): void {
    $queue = $this->queueFactory->get('social_media_automation_posts');
    
    // Different content types for each time window to ensure variety
    $content_by_window = [
      'morning' => ['recent_article', 'analytics_summary'],
      'afternoon' => ['trending_topics', 'bias_insight'],
      'evening' => ['recent_article', 'analytics_summary'],
    ];
    
    $available_types = $content_by_window[$window_name] ?? ['recent_article'];
    $last_content_type = $this->state->get('social_media_automation.last_content_type_' . $window_name, '');
    
    // Rotate between available content types for this window
    $current_index = array_search($last_content_type, $available_types);
    $next_index = ($current_index === FALSE) ? 0 : ($current_index + 1) % count($available_types);
    $next_type = $available_types[$next_index];
    
    $item = [
      'type' => 'scheduled',
      'window' => $window_name,
      'content_type' => $next_type,
      'timestamp' => time(),
    ];
    
    $queue->createItem($item);
    $this->state->set('social_media_automation.last_content_type_' . $window_name, $next_type);
    $this->logInfo('Scheduled @window social media post queued with type: @type', [
      '@window' => $window_name,
      '@type' => $next_type
    ]);
  }

  /**
   * Queue daily post (legacy method - now redirects to morning post).
   */
  protected function queueDailyPost(): void {
    $this->queueScheduledPost('morning');
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
        $this->logInfo('Social media automation disabled, skipping post');
        return TRUE;
      }

      // Generate content for all enabled platforms
      $platform_content = $this->contentGenerator->generateContent($data['content_type']);
      
      if (empty($platform_content)) {
        $this->logError('Failed to generate content for type: @type', ['@type' => $data['content_type']]);
        return FALSE;
      }

      $success_count = 0;
      $total_platforms = count($platform_content);
      
      // Post to each enabled platform
      foreach ($platform_content as $platform_name => $content) {
        $platform = $this->platformManager->getPlatform($platform_name);
        
        if (!$platform) {
          $this->logError('Platform not found: @platform', ['@platform' => $platform_name]);
          continue;
        }

        try {
          $result = $platform->postContent($content);
          
          if ($result) {
            $success_count++;
            $this->logInfo('Successfully posted to @platform: @content', [
              '@platform' => $platform->getName(),
              '@content' => substr($content, 0, 100) . '...',
            ]);
          } else {
            $this->logError('Failed to post to @platform', ['@platform' => $platform->getName()]);
          }
          
        } catch (\Exception $e) {
          $this->logError('Exception posting to @platform: @message', [
            '@platform' => $platform->getName(),
            '@message' => $e->getMessage(),
          ]);
        }
      }
      
      // Consider it successful if at least one platform succeeded
      $overall_success = $success_count > 0;
      
      if ($overall_success) {
        // Update tracking state
        if ($data['type'] === 'daily' || $data['type'] === 'forced_daily') {
          $this->state->set('social_media_automation.last_daily_post', time());
        } elseif ($data['type'] === 'morning') {
          $this->state->set('social_media_automation.last_morning_post', time());
        } elseif ($data['type'] === 'evening') {
          $this->state->set('social_media_automation.last_evening_post', time());
        }
        
        $type_display = $data['type'] === 'forced_daily' ? 'forced daily' : $data['type'];
        $this->logInfo('Completed @type post to @success/@total platforms', [
          '@type' => $type_display,
          '@success' => $success_count,
          '@total' => $total_platforms,
        ]);
      }
      
      return $overall_success;

    } catch (\Exception $e) {
      $this->logError('Exception processing social media post: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Manually send a post to all enabled platforms.
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
        $this->logError('Failed to generate content');
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
          // Use platform's formatContent to ensure proper length handling
          $formatted_content = $platform->formatContent($content);
          
          $result = $platform->postContent($formatted_content);
          
          $results[$platform_name] = [
            'success' => $result,
            'content' => substr($formatted_content, 0, 100) . '...',
          ];
          
          if ($result) {
            $this->logInfo('Successfully posted to @platform', ['@platform' => $platform->getName()]);
          } else {
            $this->logError('Failed to post to @platform', ['@platform' => $platform->getName()]);
          }
          
        } catch (\Exception $e) {
          $results[$platform_name] = [
            'success' => FALSE,
            'error' => $e->getMessage(),
          ];
          
          $this->logError('Exception sending to @platform: @message', [
            '@platform' => $platform->getName(),
            '@message' => $e->getMessage(),
          ]);
        }
      }

    } catch (\Exception $e) {
      $this->logError('Exception sending posts: @message', ['@message' => $e->getMessage()]);
    }
    
    return $results;
  }

  /**
   * Send post to specific platform.
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
        $this->logError('Platform not found: @platform', ['@platform' => $platform_name]);
        return FALSE;
      }

      // Generate content for all platforms (to ensure proper adaptation)
      $platform_content = $this->contentGenerator->generateContent($content_type);
      
      if (empty($platform_content[$platform_name])) {
        $this->logError('No content generated for platform: @platform', ['@platform' => $platform_name]);
        return FALSE;
      }

      $content = $platform_content[$platform_name];
      
      // Use platform's formatContent to ensure proper length handling
      $formatted_content = $platform->formatContent($content);
      
      $result = $platform->postContent($formatted_content);
      
      if ($result !== FALSE) {
        $this->logInfo('Successfully posted to @platform', ['@platform' => $platform->getName()]);
        return TRUE;
      } else {
        $this->logError('Failed to post to @platform', ['@platform' => $platform->getName()]);
        return FALSE;
      }

    } catch (\Exception $e) {
      $this->logError('Exception sending to @platform: @message', [
        '@platform' => $platform_name,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Manually force a daily post (bypassing time window check).
   *
   * @param string $content_type
   *   Optional content type to use. If not provided, uses normal rotation.
   *
   * @return bool
   *   TRUE if post was queued and processed successfully, FALSE otherwise.
   */
  public function forcePost(string $content_type = NULL): bool {
    $config = $this->configFactory->get('social_media_automation.settings');
    
    if (!$config->get('enabled')) {
      $this->logWarning('Cannot force post: automation is disabled');
      return FALSE;
    }

    $this->logInfo('=== FORCING DAILY POST (Manual Override) ===');
    
    // Determine content type
    if (!$content_type) {
      // Use normal rotation
      $content_types = ['recent_article', 'analytics_summary', 'trending_topics', 'bias_insight'];
      $last_content_type = $this->state->get('social_media_automation.last_content_type', 'recent_article');
      
      // Find next content type in rotation
      $current_index = array_search($last_content_type, $content_types);
      $next_index = ($current_index + 1) % count($content_types);
      $content_type = $content_types[$next_index];
    }
    
    $this->logInfo('Force posting with content type: @type', ['@type' => $content_type]);
    
    // Queue the post
    $queue = $this->queueFactory->get('social_media_automation_posts');
    
    $item = [
      'type' => 'forced_daily',
      'content_type' => $content_type,
      'timestamp' => time(),
      'forced' => TRUE,
    ];
    
    $queue->createItem($item);
    $this->state->set('social_media_automation.last_content_type', $content_type);
    $this->logInfo('Forced post queued with type: @type', ['@type' => $content_type]);
    
    // Immediately process the queue
    $processed = FALSE;
    while ($queued_item = $queue->claimItem()) {
      if ($queued_item->data['forced'] ?? FALSE) {
        $this->logInfo('Processing forced post item: @id', ['@id' => $queued_item->item_id]);
        
        try {
          $result = $this->processQueuedPost($queued_item->data);
          
          if ($result) {
            $queue->deleteItem($queued_item);
            $processed = TRUE;
            $this->logInfo('✅ Forced post processed successfully');
            break;
          } else {
            $queue->releaseItem($queued_item);
            $this->logError('❌ Failed to process forced post');
            break;
          }
          
        } catch (\Exception $e) {
          $queue->releaseItem($queued_item);
          $this->logError('❌ Exception processing forced post: @message', ['@message' => $e->getMessage()]);
          break;
        }
      } else {
        // Not our forced item, release it back
        $queue->releaseItem($queued_item);
      }
    }
    
    if ($processed) {
      $this->logInfo('=== FORCED POST COMPLETED SUCCESSFULLY ===');
    } else {
      $this->logError('=== FORCED POST FAILED ===');
    }
    
    return $processed;
  }

  /**
   * Get statistics about automated posting.
   *
   * @return array
   *   Array of statistics.
   */
  public function getStats(): array {
    $last_daily_post = $this->state->get('social_media_automation.last_daily_post', 0);
    $last_content_type = $this->state->get('social_media_automation.last_content_type', 'recent_article');
    
    // Keep legacy stats for backward compatibility
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
      'last_daily_post' => $last_daily_post,
      'last_content_type' => $last_content_type,
      'queue_count' => $queue_count,
      'last_daily_date' => $last_daily_post ? date('Y-m-d H:i:s', $last_daily_post) : 'Never',
      // Legacy stats for backward compatibility
      'last_morning_post' => $last_morning_post,
      'last_evening_post' => $last_evening_post,
      'last_evening_type' => $last_evening_type,
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
    
    // Check if platform is enabled via configuration
    $config = $this->configFactory->get('social_media_automation.settings');
    $enabled_platforms = $config->get('enabled_platforms') ?: [];
    $is_enabled = in_array($platform_name, $enabled_platforms);
    
    return [
      'platform_name' => $platform->getName(),
      'platform_machine_name' => $platform_name,
      'enabled' => $is_enabled,
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
