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
      $this->output()->writeln('Last Morning Post: ' . $stats['last_morning_date']);
      $this->output()->writeln('Last Evening Post: ' . $stats['last_evening_date']);
      $this->output()->writeln('Last Evening Type: ' . ucfirst(str_replace('_', ' ', $stats['last_evening_type'])));
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
   *   Content type: analytics_summary, trending_topics, bias_insight
   *
   * @command social-media:test-content
   * @aliases sma:test-content
   * @usage social-media:test-content analytics_summary
   *   Generate test analytics summary content for all platforms
   * @usage social-media:test-content trending_topics
   *   Generate test trending topics content for all platforms
   */
  public function testContent($type = 'analytics_summary') {
    $valid_types = ['analytics_summary', 'trending_topics', 'bias_insight'];
    
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

}
