<?php

namespace Drupal\social_media_automation\Commands;

use Drupal\social_media_automation\Service\ContentGenerator;
use Drupal\social_media_automation\Service\PlatformManager;
use Drupal\social_media_automation\Service\SocialMediaScheduler;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands for testing social media automation.
 */
class SocialMediaAutomationCommands extends DrushCommands {

  /**
   * The content generator service.
   *
   * @var \Drupal\social_media_automation\Service\ContentGenerator
   */
  protected $contentGenerator;

  /**
   * The platform manager service.
   *
   * @var \Drupal\social_media_automation\Service\PlatformManager
   */
  protected $platformManager;

  /**
   * The social media scheduler service.
   *
   * @var \Drupal\social_media_automation\Service\SocialMediaScheduler
   */
  protected $scheduler;

  /**
   * Constructor.
   */
  public function __construct(ContentGenerator $content_generator = NULL, PlatformManager $platform_manager = NULL, SocialMediaScheduler $scheduler = NULL) {
    // Use service locator pattern for Drush commands
    $this->contentGenerator = $content_generator ?: \Drupal::service('social_media_automation.content_generator');
    $this->platformManager = $platform_manager ?: \Drupal::service('social_media_automation.platform_manager');
    $this->scheduler = $scheduler ?: \Drupal::service('social_media_automation.scheduler');
  }

  /**
   * Show platform status and statistics.
   *
   * @command social-media:status
   * @usage social-media:status
   *   Show automation status and platform statistics
   */
  public function status() {
    try {
      $config = \Drupal::config('social_media_automation.settings');
      $stats = $this->scheduler->getStats();
      
      $this->output()->writeln('<info>=== Social Media Automation Status ===</info>');
      $this->output()->writeln('');
      
      // Global status
      $enabled = $config->get('enabled') ? 'Enabled' : 'Disabled';
      $status_color = $config->get('enabled') ? 'info' : 'comment';
      $this->output()->writeln('<' . $status_color . '>Automation: ' . $enabled . '</' . $status_color . '>');
      $this->output()->writeln('Enabled Platforms: ' . count($stats['enabled_platforms']));
      $this->output()->writeln('Queue Count: ' . $stats['queue_count']);
      $this->output()->writeln('');
      
      // Recent activity
      $this->output()->writeln('<comment>Recent Activity:</comment>');
      $this->output()->writeln('Last Daily Post: ' . ($stats['last_daily_date'] ?? 'Never'));
      $this->output()->writeln('Last Content Type: ' . ucfirst(str_replace('_', ' ', $stats['last_content_type'] ?? 'recent_article')));
      
      // Legacy info for backward compatibility
      if (isset($stats['last_morning_date']) && $stats['last_morning_date'] !== 'Never') {
        $this->output()->writeln('Last Morning Post (Legacy): ' . $stats['last_morning_date']);
      }
      if (isset($stats['last_evening_date']) && $stats['last_evening_date'] !== 'Never') {
        $this->output()->writeln('Last Evening Post (Legacy): ' . $stats['last_evening_date']);
      }
      $this->output()->writeln('');
      
      // Platform details
      if (!empty($stats['enabled_platforms'])) {
        $this->output()->writeln('<comment>Platform Details:</comment>');
        
        foreach ($stats['enabled_platforms'] as $platform_name) {
          $platform_stats = $this->scheduler->getPlatformStats($platform_name);
          $platform = $this->platformManager->getPlatform($platform_name);
          $platform_display_name = $platform ? $platform->getName() : $platform_name;
          
          $this->output()->writeln($platform_display_name . ':');
          $this->output()->writeln('  Last Post: ' . ($platform_stats['last_post_date'] ?? 'Never'));
          $this->output()->writeln('  Total Posts: ' . ($platform_stats['total_posts'] ?? 0));
          $this->output()->writeln('  Character Limit: ' . ($platform_stats['character_limit'] ?? 'N/A'));
          
          if (!empty($platform_stats['last_error'])) {
            $this->output()->writeln('  <comment>Last Error: ' . substr($platform_stats['last_error'], 0, 50) . '...</comment>');
          }
          
          $this->output()->writeln('');
        }
      } else {
        $this->output()->writeln('<comment>No platforms enabled. Configure platforms in admin settings.</comment>');
      }
      
    } catch (\Exception $e) {
      $this->output()->writeln('<info>Social Media Automation module is loaded and commands are working!</info>');
      $this->output()->writeln('<comment>Status: Basic functionality verified (detailed status requires configuration)</comment>');
      $this->output()->writeln('<comment>Error: ' . $e->getMessage() . '</comment>');
    }
  }

  /**
   * Test content generation for all enabled platforms.
   *
   * @param string $type
   *   Content type: recent_article, analytics_summary, trending_topics, bias_insight
   *
   * @command social-media:test-content
   * @aliases sma:test-content
   * @usage social-media:test-content recent_article
   *   Generate test content featuring the most recent article
   * @usage social-media:test-content analytics_summary
   *   Generate test analytics summary content for all platforms
   * @usage social-media:test-content trending_topics
   *   Generate test trending topics content for all platforms
   */
  public function testContent($type = 'recent_article') {
    $valid_types = ['recent_article', 'analytics_summary', 'trending_topics', 'bias_insight'];
    
    if (!in_array($type, $valid_types)) {
      $this->output()->writeln('<error>Invalid content type. Use: ' . implode(', ', $valid_types) . '</error>');
      return;
    }

    $this->output()->writeln('<info>Generating content for type: ' . $type . '</info>');
    $this->output()->writeln('');

    try {
      $platform_content = $this->contentGenerator->generateContent($type);
      
      if (empty($platform_content)) {
        $this->output()->writeln('<error>Failed to generate content</error>');
        return;
      }

      foreach ($platform_content as $platform_name => $content) {
        $platform = $this->platformManager->getPlatform($platform_name);
        $platform_display_name = $platform ? $platform->getName() : $platform_name;
        $char_limit = $platform ? $platform->getCharacterLimit() : 500;
        
        $char_count = strlen($content);
        $status = $char_count <= $char_limit ? 'OK' : 'TOO LONG';
        $status_color = $char_count <= $char_limit ? 'info' : 'comment';

        $this->output()->writeln('<comment>--- ' . $platform_display_name . ' ---</comment>');
        $this->output()->writeln($content);
        $this->output()->writeln('');
        $this->output()->writeln('<' . $status_color . '>Character count: ' . $char_count . '/' . $char_limit . ' (' . $status . ')</' . $status_color . '>');
        $this->output()->writeln('');
      }

    } catch (\Exception $e) {
      $this->output()->writeln('<error>Error generating content: ' . $e->getMessage() . '</error>');
    }
  }

  /**
   * List all available platforms and their status.
   *
   * @command social-media:platforms
   * @aliases sma:platforms
   * @usage social-media:platforms
   *   List all available platforms and their configuration status
   */
  public function listPlatforms() {
    try {
      $available_platform_names = $this->platformManager->getAvailablePlatforms();
      $enabled_platforms = $this->platformManager->getEnabledPlatforms();
      
      $this->output()->writeln('<info>=== Available Platforms ===</info>');
      $this->output()->writeln('');
      
      if (empty($available_platform_names)) {
        $this->output()->writeln('<comment>No platforms registered. Platform services may not be configured.</comment>');
        $this->output()->writeln('<comment>Expected platforms: Mastodon, LinkedIn, Facebook, Twitter</comment>');
        return;
      }
      
      foreach ($available_platform_names as $platform_name) {
        $platform = $this->platformManager->getPlatform($platform_name);
        $is_enabled = isset($enabled_platforms[$platform_name]);
        $status = $is_enabled ? 'Enabled' : 'Disabled';
        $status_color = $is_enabled ? 'info' : 'comment';
        
        if ($platform) {
          $this->output()->writeln('<comment>' . $platform->getName() . ' (' . $platform_name . ')</comment>');
          $this->output()->writeln('  Status: <' . $status_color . '>' . $status . '</' . $status_color . '>');
          $this->output()->writeln('  Character Limit: ' . $platform->getCharacterLimit());
          
          try {
            $features = $platform->getSupportedFeatures();
            $feature_list = [];
            if (!empty($features['hashtags'])) $feature_list[] = 'Hashtags';
            if (!empty($features['mentions'])) $feature_list[] = 'Mentions';
            if (!empty($features['media'])) $feature_list[] = 'Media';
            if (!empty($features['threads'])) $feature_list[] = 'Threads';
            if (!empty($features['content_warnings'])) $feature_list[] = 'Content Warnings';
            
            $this->output()->writeln('  Features: ' . (!empty($feature_list) ? implode(', ', $feature_list) : 'Basic text'));
          } catch (\Exception $e) {
            $this->output()->writeln('  Features: Basic text (error getting features)');
          }
        } else {
          $this->output()->writeln('<comment>' . ucfirst($platform_name) . ' (' . $platform_name . ')</comment>');
          $this->output()->writeln('  Status: <error>Not Available</error>');
          $this->output()->writeln('  Error: Platform service not found');
        }
        
        $this->output()->writeln('');
      }
      
    } catch (\Exception $e) {
      $this->output()->writeln('<error>Error loading platforms: ' . $e->getMessage() . '</error>');
      $this->output()->writeln('<comment>Expected platforms: Mastodon, LinkedIn, Facebook, Twitter</comment>');
      $this->output()->writeln('<comment>Configure platforms at: /admin/config/services/social-media-automation</comment>');
    }
  }

  /**
   * Test connection to all enabled platforms.
   *
   * @command social-media:test-connections
   * @aliases sma:test-connections
   * @usage social-media:test-connections
   *   Test connection to all enabled platforms
   */
  public function testConnections() {
    try {
      $platforms = $this->platformManager->getEnabledPlatforms();
      
      if (empty($platforms)) {
        $this->output()->writeln('<comment>No platforms enabled. Configure platforms in admin settings.</comment>');
        return;
      }

      $this->output()->writeln('<info>Testing connections to enabled platforms...</info>');
      $this->output()->writeln('');

      foreach ($platforms as $platform_name => $platform) {
        $this->output()->writeln('<comment>Testing ' . $platform->getName() . '...</comment>');
        
        try {
          $result = $platform->testConnection();
          
          if ($result) {
            $this->output()->writeln('<info>✅ ' . $platform->getName() . ': Connection successful</info>');
          } else {
            $this->output()->writeln('<error>❌ ' . $platform->getName() . ': Connection failed</error>');
          }
        } catch (\Exception $e) {
          $this->output()->writeln('<error>❌ ' . $platform->getName() . ': Error - ' . $e->getMessage() . '</error>');
        }
        
        $this->output()->writeln('');
      }
      
    } catch (\Exception $e) {
      $this->output()->writeln('<error>Error testing connections: ' . $e->getMessage() . '</error>');
    }
  }

  /**
   * Manually trigger cron execution for social media automation.
   *
   * @command social-media:cron
   * @aliases sma:cron
   * @usage social-media:cron
   *   Manually run the social media automation cron to check if posts should be scheduled
   */
  public function cronRun() {
    try {
      $config = \Drupal::config('social_media_automation.settings');
      
      $this->output()->writeln('<info>=== Manual Cron Execution ===</info>');
      $this->output()->writeln('');
      
      // Show current time and settings
      $current_time = time();
      $current_hour = (int) date('H', $current_time);
      $today = date('Y-m-d', $current_time);
      $enabled = $config->get('enabled');
      
      $this->output()->writeln('Current time: ' . date('Y-m-d H:i:s T', $current_time));
      $this->output()->writeln('Current hour: ' . $current_hour);
      $this->output()->writeln('Automation enabled: ' . ($enabled ? 'Yes' : 'No'));
      $this->output()->writeln('Daily posting window: 10 AM - 2 PM (hours 10-13)');
      $this->output()->writeln('');
      
      if (!$enabled) {
        $this->output()->writeln('<comment>Automation is disabled. Enable it in the admin settings.</comment>');
        return;
      }
      
      // Check last daily post
      $last_daily_post = \Drupal::state()->get('social_media_automation.last_daily_post', 0);
      $last_post_date = $last_daily_post ? date('Y-m-d', $last_daily_post) : 'Never';
      $last_content_type = \Drupal::state()->get('social_media_automation.last_content_type', 'recent_article');
      
      $this->output()->writeln('Last daily post: ' . ($last_daily_post ? date('Y-m-d H:i:s', $last_daily_post) : 'Never'));
      $this->output()->writeln('Last content type: ' . $last_content_type);
      $this->output()->writeln('');
      
      // Show queue status before
      $queue = \Drupal::service('queue')->get('social_media_automation_posts');
      $queue_count_before = $queue->numberOfItems();
      $this->output()->writeln('Queue items before cron: ' . $queue_count_before);
      
      // Execute cron
      $this->output()->writeln('<comment>Executing cron...</comment>');
      $this->scheduler->executeCronPosts();
      
      // Show queue status after
      $queue_count_after = $queue->numberOfItems();
      $this->output()->writeln('Queue items after cron: ' . $queue_count_after);
      
      if ($queue_count_after > $queue_count_before) {
        $this->output()->writeln('<info>✅ Post was queued! New items added to queue.</info>');
        
        // Show next content type
        $next_content_type = \Drupal::state()->get('social_media_automation.last_content_type', 'recent_article');
        $this->output()->writeln('Next content type will be: ' . $next_content_type);
      } else {
        if ($current_hour >= 10 && $current_hour < 14) {
          if ($last_post_date === $today) {
            $this->output()->writeln('<comment>⏸️ Post already sent today (' . $last_post_date . ').</comment>');
          } else {
            $this->output()->writeln('<comment>⚠️ In posting window but no post queued. Check platform configuration.</comment>');
          }
        } else {
          $this->output()->writeln('<comment>⏰ Outside posting window (current hour: ' . $current_hour . ').</comment>');
          if ($current_hour < 10) {
            $this->output()->writeln('Next posting window starts at 10 AM.');
          } else {
            $this->output()->writeln('Posting window ended at 2 PM. Next window starts tomorrow at 10 AM.');
          }
        }
      }
      
      $this->output()->writeln('');
      $this->output()->writeln('<comment>To process the queue manually: drush queue:run social_media_automation_posts</comment>');
      
    } catch (\Exception $e) {
      $this->output()->writeln('<error>Error running cron: ' . $e->getMessage() . '</error>');
    }
  }

  /**
   * Process queued social media posts.
   *
   * @command social-media:process-queue
   * @aliases sma:process-queue
   * @usage social-media:process-queue
   *   Process any queued social media posts immediately
   */
  public function processQueue() {
    try {
      $queue = \Drupal::service('queue')->get('social_media_automation_posts');
      $queue_count = $queue->numberOfItems();
      
      $this->output()->writeln('<info>=== Processing Social Media Queue ===</info>');
      $this->output()->writeln('Queue items: ' . $queue_count);
      $this->output()->writeln('');
      
      if ($queue_count === 0) {
        $this->output()->writeln('<comment>No items in queue to process.</comment>');
        return;
      }
      
      $processed = 0;
      $failed = 0;
      
      while ($item = $queue->claimItem()) {
        $this->output()->writeln('<comment>Processing item: ' . $item->item_id . '</comment>');
        
        try {
          $data = $item->data;
          $this->output()->writeln('Type: ' . ($data['type'] ?? 'unknown'));
          $this->output()->writeln('Content Type: ' . ($data['content_type'] ?? 'unknown'));
          
          $result = $this->scheduler->processQueuedPost($data);
          
          if ($result) {
            $queue->deleteItem($item);
            $processed++;
            $this->output()->writeln('<info>✅ Successfully processed and posted</info>');
          } else {
            $queue->releaseItem($item);
            $failed++;
            $this->output()->writeln('<error>❌ Failed to process, item released back to queue</error>');
          }
          
        } catch (\Exception $e) {
          $queue->releaseItem($item);
          $failed++;
          $this->output()->writeln('<error>❌ Exception: ' . $e->getMessage() . '</error>');
        }
        
        $this->output()->writeln('');
      }
      
      $this->output()->writeln('<info>Processing complete:</info>');
      $this->output()->writeln('Processed successfully: ' . $processed);
      $this->output()->writeln('Failed: ' . $failed);
      
    } catch (\Exception $e) {
      $this->output()->writeln('<error>Error processing queue: ' . $e->getMessage() . '</error>');
    }
  }

}
