<?php

namespace Drupal\social_media_automation\Commands;

use Drupal\social_media_automation\Service\ContentGenerator;
use Drupal\social_media_automation\Service\PlatformManager;
use Drupal\social_media_automation\Service\SocialMediaScheduler;
use Drush\Commands\DrushCommands;

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
  public function __construct(ContentGenerator $content_generator, PlatformManager $platform_manager, SocialMediaScheduler $scheduler) {
    $this->contentGenerator = $content_generator;
    $this->platformManager = $platform_manager;
    $this->scheduler = $scheduler;
  }

  /**
   * Test content generation for all enabled platforms.
   *
   * @param string $type
   *   Content type: analytics_summary, trending_topics, bias_insight
   *
   * @command social-media:test-content
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
   * Test connection to all enabled platforms.
   *
   * @command social-media:test-connections
   * @usage social-media:test-connections
   *   Test connection to all enabled platforms
   */
  public function testConnections() {
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
  }

  /**
   * Test connection to specific platform.
   *
   * @param string $platform_name
   *   Platform machine name: mastodon, linkedin, facebook, twitter
   *
   * @command social-media:test-platform
   * @usage social-media:test-platform mastodon
   *   Test connection to Mastodon
   * @usage social-media:test-platform twitter
   *   Test connection to Twitter
   */
  public function testPlatform($platform_name) {
    $platform = $this->platformManager->getPlatform($platform_name);
    
    if (!$platform) {
      $this->output()->writeln('<error>Platform not found: ' . $platform_name . '</error>');
      $this->output()->writeln('<comment>Available platforms: ' . implode(', ', array_keys($this->platformManager->getAllPlatforms())) . '</comment>');
      return;
    }

    $this->output()->writeln('<info>Testing connection to ' . $platform->getName() . '...</info>');

    try {
      $result = $platform->testConnection();
      
      if ($result) {
        $this->output()->writeln('<info>✅ Success! Connected to ' . $platform->getName() . '</info>');
      } else {
        $this->output()->writeln('<error>❌ Failed to connect to ' . $platform->getName() . '</error>');
      }
    } catch (\Exception $e) {
      $this->output()->writeln('<error>❌ Error: ' . $e->getMessage() . '</error>');
    }
  }

  /**
   * Send test post to all enabled platforms.
   *
   * @param string $type
   *   Content type: analytics_summary, trending_topics, bias_insight
   *
   * @command social-media:test-post
   * @usage social-media:test-post analytics_summary
   *   Send test analytics summary to all platforms
   * @usage social-media:test-post trending_topics
   *   Send test trending topics to all platforms
   */
  public function testPost($type = 'analytics_summary') {
    $valid_types = ['analytics_summary', 'trending_topics', 'bias_insight'];
    
    if (!in_array($type, $valid_types)) {
      $this->output()->writeln('<error>Invalid content type. Use: ' . implode(', ', $valid_types) . '</error>');
      return;
    }

    $this->output()->writeln('<info>Sending test post (' . $type . ') to all enabled platforms...</info>');
    $this->output()->writeln('');

    $results = $this->scheduler->sendTestPost($type);
    
    if (empty($results)) {
      $this->output()->writeln('<error>No platforms enabled or no content generated.</error>');
      return;
    }

    $success_count = 0;
    foreach ($results as $platform_name => $result) {
      $platform = $this->platformManager->getPlatform($platform_name);
      $platform_display_name = $platform ? $platform->getName() : $platform_name;
      
      if ($result['success']) {
        $success_count++;
        $this->output()->writeln('<info>✅ ' . $platform_display_name . ': Success</info>');
      } else {
        $error = $result['error'] ?? 'Unknown error';
        $this->output()->writeln('<error>❌ ' . $platform_display_name . ': ' . $error . '</error>');
      }
    }
    
    $this->output()->writeln('');
    $this->output()->writeln('<comment>Results: ' . $success_count . '/' . count($results) . ' platforms succeeded</comment>');
  }

  /**
   * Send test post to specific platform.
   *
   * @param string $platform_name
   *   Platform machine name: mastodon, linkedin, facebook, twitter
   * @param string $type
   *   Content type: analytics_summary, trending_topics, bias_insight
   *
   * @command social-media:test-platform-post
   * @usage social-media:test-platform-post mastodon analytics_summary
   *   Send test analytics summary to Mastodon only
   * @usage social-media:test-platform-post twitter trending_topics
   *   Send test trending topics to Twitter only
   */
  public function testPlatformPost($platform_name, $type = 'analytics_summary') {
    $valid_types = ['analytics_summary', 'trending_topics', 'bias_insight'];
    
    if (!in_array($type, $valid_types)) {
      $this->output()->writeln('<error>Invalid content type. Use: ' . implode(', ', $valid_types) . '</error>');
      return;
    }

    $platform = $this->platformManager->getPlatform($platform_name);
    
    if (!$platform) {
      $this->output()->writeln('<error>Platform not found: ' . $platform_name . '</error>');
      $this->output()->writeln('<comment>Available platforms: ' . implode(', ', array_keys($this->platformManager->getAllPlatforms())) . '</comment>');
      return;
    }

    $this->output()->writeln('<info>Sending test post (' . $type . ') to ' . $platform->getName() . '...</info>');

    $result = $this->scheduler->sendTestPostToPlatform($platform_name, $type);
    
    if ($result) {
      $this->output()->writeln('<info>✅ Success! Test post sent to ' . $platform->getName() . '</info>');
    } else {
      $this->output()->writeln('<error>❌ Failed to send test post to ' . $platform->getName() . '</error>');
    }
  }

  /**
   * Show platform status and statistics.
   *
   * @command social-media:status
   * @usage social-media:status
   *   Show automation status and platform statistics
   */
  public function status() {
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
  }

  /**
   * Test all content types for all platforms.
   *
   * @command social-media:test-all
   * @usage social-media:test-all
   *   Generate test content for all types and all platforms
   */
  public function testAllContent() {
    $types = ['analytics_summary', 'trending_topics', 'bias_insight'];
    
    foreach ($types as $type) {
      $this->output()->writeln('<info>=== Testing: ' . ucfirst(str_replace('_', ' ', $type)) . ' ===</info>');
      $this->testContent($type);
      $this->output()->writeln('');
    }
  }

  /**
   * List all available platforms and their status.
   *
   * @command social-media:platforms
   * @usage social-media:platforms
   *   List all available platforms and their configuration status
   */
  public function listPlatforms() {
    $all_platforms = $this->platformManager->getAllPlatforms();
    $enabled_platforms = $this->platformManager->getEnabledPlatforms();
    
    $this->output()->writeln('<info>=== Available Platforms ===</info>');
    $this->output()->writeln('');
    
    foreach ($all_platforms as $platform_name => $platform) {
      $is_enabled = isset($enabled_platforms[$platform_name]);
      $status = $is_enabled ? 'Enabled' : 'Disabled';
      $status_color = $is_enabled ? 'info' : 'comment';
      
      $this->output()->writeln('<comment>' . $platform->getName() . ' (' . $platform_name . ')</comment>');
      $this->output()->writeln('  Status: <' . $status_color . '>' . $status . '</' . $status_color . '>');
      $this->output()->writeln('  Character Limit: ' . $platform->getCharacterLimit());
      
      $features = $platform->getSupportedFeatures();
      $feature_list = [];
      if (!empty($features['hashtags'])) $feature_list[] = 'Hashtags';
      if (!empty($features['mentions'])) $feature_list[] = 'Mentions';
      if (!empty($features['media'])) $feature_list[] = 'Media';
      if (!empty($features['threads'])) $feature_list[] = 'Threads';
      if (!empty($features['content_warnings'])) $feature_list[] = 'Content Warnings';
      
      $this->output()->writeln('  Features: ' . (!empty($feature_list) ? implode(', ', $feature_list) : 'Basic text'));
      $this->output()->writeln('');
    }
  }

}
