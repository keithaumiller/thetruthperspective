<?php

namespace Drupal\twitter_automation\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;

/**
 * Twitter scheduler service for managing automated posting.
 * 
 * Handles scheduling and execution of automated Twitter posts.
 */
class TwitterScheduler {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Twitter API client.
   *
   * @var \Drupal\twitter_automation\Service\TwitterApiClient
   */
  protected $twitterApiClient;

  /**
   * The content generator.
   *
   * @var \Drupal\twitter_automation\Service\ContentGenerator
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
    TwitterApiClient $twitter_api_client,
    ContentGenerator $content_generator,
    QueueFactory $queue_factory,
    StateInterface $state,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->configFactory = $config_factory;
    $this->twitterApiClient = $twitter_api_client;
    $this->contentGenerator = $content_generator;
    $this->queueFactory = $queue_factory;
    $this->state = $state;
    $this->logger = $logger_factory->get('twitter_automation');
  }

  /**
   * Execute scheduled Twitter posts.
   * 
   * Called by cron to check if posts should be sent.
   */
  public function executeCronPosts(): void {
    $config = $this->configFactory->get('twitter_automation.settings');
    
    if (!$config->get('enabled')) {
      return;
    }

    $last_morning_post = $this->state->get('twitter_automation.last_morning_post', 0);
    $last_evening_post = $this->state->get('twitter_automation.last_evening_post', 0);
    
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
    $queue = $this->queueFactory->get('twitter_automation_posts');
    
    $item = [
      'type' => 'morning',
      'content_type' => 'analytics_summary',
      'timestamp' => time(),
    ];
    
    $queue->createItem($item);
    $this->logger->info('Morning Twitter post queued');
  }

  /**
   * Queue evening post.
   */
  protected function queueEveningPost(): void {
    $queue = $this->queueFactory->get('twitter_automation_posts');
    
    // Alternate between different content types for evening posts
    $evening_types = ['trending_topics', 'bias_insight'];
    $last_evening_type = $this->state->get('twitter_automation.last_evening_type', 'trending_topics');
    
    $next_type = ($last_evening_type === 'trending_topics') ? 'bias_insight' : 'trending_topics';
    
    $item = [
      'type' => 'evening',
      'content_type' => $next_type,
      'timestamp' => time(),
    ];
    
    $queue->createItem($item);
    $this->state->set('twitter_automation.last_evening_type', $next_type);
    $this->logger->info('Evening Twitter post queued with type: @type', ['@type' => $next_type]);
  }

  /**
   * Process a queued Twitter post.
   *
   * @param array $data
   *   The queue item data.
   *
   * @return bool
   *   TRUE if successful, FALSE otherwise.
   */
  public function processQueuedPost(array $data): bool {
    try {
      $config = $this->configFactory->get('twitter_automation.settings');
      
      if (!$config->get('enabled')) {
        $this->logger->info('Twitter automation disabled, skipping post');
        return TRUE;
      }

      // Generate content
      $content = $this->contentGenerator->generateContent($data['content_type']);
      
      if (empty($content)) {
        $this->logger->error('Failed to generate content for type: @type', ['@type' => $data['content_type']]);
        return FALSE;
      }

      // Check content length (Twitter limit is 280 characters)
      if (strlen($content) > 280) {
        $this->logger->warning('Generated content exceeds Twitter limit: @length characters', ['@length' => strlen($content)]);
        // Truncate if needed
        $content = substr($content, 0, 277) . '...';
      }

      // Post to Twitter
      $result = $this->twitterApiClient->postTweet($content);
      
      if ($result) {
        // Update tracking state
        if ($data['type'] === 'morning') {
          $this->state->set('twitter_automation.last_morning_post', time());
        } elseif ($data['type'] === 'evening') {
          $this->state->set('twitter_automation.last_evening_post', time());
        }
        
        $this->logger->info('Successfully posted @type tweet: @content', [
          '@type' => $data['type'],
          '@content' => substr($content, 0, 100) . '...',
        ]);
        
        return TRUE;
      } else {
        $this->logger->error('Failed to post @type tweet', ['@type' => $data['type']]);
        return FALSE;
      }

    } catch (\Exception $e) {
      $this->logger->error('Exception processing Twitter post: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Manually send a test tweet.
   *
   * @param string $content_type
   *   The type of content to generate.
   *
   * @return bool
   *   TRUE if successful, FALSE otherwise.
   */
  public function sendTestTweet(string $content_type = 'analytics_summary'): bool {
    try {
      $content = $this->contentGenerator->generateContent($content_type);
      
      if (empty($content)) {
        $this->logger->error('Failed to generate test content');
        return FALSE;
      }

      // Add test prefix
      $content = "🧪 TEST: " . $content;
      
      if (strlen($content) > 280) {
        $content = substr($content, 0, 277) . '...';
      }

      $result = $this->twitterApiClient->postTweet($content);
      
      if ($result) {
        $this->logger->info('Successfully posted test tweet');
        return TRUE;
      } else {
        $this->logger->error('Failed to post test tweet');
        return FALSE;
      }

    } catch (\Exception $e) {
      $this->logger->error('Exception sending test tweet: @message', ['@message' => $e->getMessage()]);
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
    $last_morning_post = $this->state->get('twitter_automation.last_morning_post', 0);
    $last_evening_post = $this->state->get('twitter_automation.last_evening_post', 0);
    $last_evening_type = $this->state->get('twitter_automation.last_evening_type', 'trending_topics');
    
    $queue = $this->queueFactory->get('twitter_automation_posts');
    $queue_count = $queue->numberOfItems();
    
    return [
      'last_morning_post' => $last_morning_post,
      'last_evening_post' => $last_evening_post,
      'last_evening_type' => $last_evening_type,
      'queue_count' => $queue_count,
      'last_morning_date' => $last_morning_post ? date('Y-m-d H:i:s', $last_morning_post) : 'Never',
      'last_evening_date' => $last_evening_post ? date('Y-m-d H:i:s', $last_evening_post) : 'Never',
    ];
  }

}
