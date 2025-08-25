<?php

namespace Drupal\news_extractor\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Service for managing daily article processing limits per news source.
 */
class DailyLimitService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new DailyLimitService object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Check if processing is allowed for a news source today.
   *
   * @param string $news_source
   *   The news source name.
   * @param string $date
   *   Optional date in Y-m-d format. Defaults to today.
   *
   * @return bool
   *   TRUE if processing is allowed, FALSE if limit reached.
   */
  public function isProcessingAllowed(string $news_source, string $date = NULL): bool {
    $config = $this->configFactory->get('news_extractor.settings');
    
    // Check if daily limits are enabled
    if (!$config->get('daily_limit_enabled')) {
      return TRUE;
    }

    if (!$date) {
      $date = date('Y-m-d');
    }

    $current_count = $this->getDailyCount($news_source, $date);
    $daily_limit = $this->getDailyLimit($news_source);

    $allowed = $current_count < $daily_limit;
    
    if (!$allowed) {
      $this->loggerFactory->get('news_extractor')->info('Daily limit reached for source @source on @date: @count/@limit articles', [
        '@source' => $news_source,
        '@date' => $date,
        '@count' => $current_count,
        '@limit' => $daily_limit,
      ]);
    }

    return $allowed;
  }

  /**
   * Increment the daily count for a news source.
   *
   * @param string $news_source
   *   The news source name.
   * @param string $date
   *   Optional date in Y-m-d format. Defaults to today.
   *
   * @return int
   *   The new count after incrementing.
   */
  public function incrementDailyCount(string $news_source, string $date = NULL): int {
    if (!$date) {
      $date = date('Y-m-d');
    }

    $current_time = time();
    $daily_limit = $this->getDailyLimit($news_source);

    // Use UPSERT to handle concurrent access safely
    $this->database->upsert('news_extractor_daily_limits')
      ->key(['news_source', 'processing_date'])
      ->fields([
        'news_source' => $news_source,
        'processing_date' => $date,
        'article_count' => 1,
        'daily_limit' => $daily_limit,
        'created' => $current_time,
        'updated' => $current_time,
      ])
      ->expression('article_count', 'article_count + 1')
      ->expression('updated', ':time', [':time' => $current_time])
      ->execute();

    $new_count = $this->getDailyCount($news_source, $date);

    $this->loggerFactory->get('news_extractor')->info('Incremented daily count for source @source on @date: @count/@limit articles', [
      '@source' => $news_source,
      '@date' => $date,
      '@count' => $new_count,
      '@limit' => $daily_limit,
    ]);

    return $new_count;
  }

  /**
   * Get the current daily count for a news source.
   *
   * @param string $news_source
   *   The news source name.
   * @param string $date
   *   Optional date in Y-m-d format. Defaults to today.
   *
   * @return int
   *   The current daily count.
   */
  public function getDailyCount(string $news_source, string $date = NULL): int {
    if (!$date) {
      $date = date('Y-m-d');
    }

    $query = $this->database->select('news_extractor_daily_limits', 'dl')
      ->fields('dl', ['article_count'])
      ->condition('news_source', $news_source)
      ->condition('processing_date', $date);

    $result = $query->execute()->fetchField();
    
    return $result ? (int) $result : 0;
  }

  /**
   * Get the daily limit for a news source.
   *
   * @param string $news_source
   *   The news source name.
   *
   * @return int
   *   The daily limit for this source.
   */
  public function getDailyLimit(string $news_source): int {
    // First check if there's a source-specific limit in the database
    $query = $this->database->select('news_extractor_daily_limits', 'dl')
      ->fields('dl', ['daily_limit'])
      ->condition('news_source', $news_source)
      ->range(0, 1);

    $result = $query->execute()->fetchField();
    
    if ($result) {
      return (int) $result;
    }

    // Fall back to default configuration
    $config = $this->configFactory->get('news_extractor.settings');
    return (int) $config->get('default_daily_limit') ?: 5;
  }

  /**
   * Set a custom daily limit for a specific news source.
   *
   * @param string $news_source
   *   The news source name.
   * @param int $limit
   *   The new daily limit.
   */
  public function setDailyLimit(string $news_source, int $limit): void {
    $current_time = time();
    $date = date('Y-m-d');

    $this->database->upsert('news_extractor_daily_limits')
      ->key(['news_source', 'processing_date'])
      ->fields([
        'news_source' => $news_source,
        'processing_date' => $date,
        'article_count' => 0,
        'daily_limit' => $limit,
        'created' => $current_time,
        'updated' => $current_time,
      ])
      ->expression('daily_limit', ':limit', [':limit' => $limit])
      ->expression('updated', ':time', [':time' => $current_time])
      ->execute();

    $this->loggerFactory->get('news_extractor')->info('Set daily limit for source @source to @limit articles', [
      '@source' => $news_source,
      '@limit' => $limit,
    ]);
  }

  /**
   * Get all daily counts for today.
   *
   * @param string $date
   *   Optional date in Y-m-d format. Defaults to today.
   *
   * @return array
   *   Array of daily counts keyed by news source.
   */
  public function getAllDailyCounts(string $date = NULL): array {
    if (!$date) {
      $date = date('Y-m-d');
    }

    $query = $this->database->select('news_extractor_daily_limits', 'dl')
      ->fields('dl', ['news_source', 'article_count', 'daily_limit'])
      ->condition('processing_date', $date)
      ->orderBy('article_count', 'DESC');

    $results = $query->execute()->fetchAllAssoc('news_source');
    
    $counts = [];
    foreach ($results as $source => $row) {
      $counts[$source] = [
        'count' => (int) $row->article_count,
        'limit' => (int) $row->daily_limit,
        'remaining' => max(0, (int) $row->daily_limit - (int) $row->article_count),
        'at_limit' => (int) $row->article_count >= (int) $row->daily_limit,
      ];
    }

    return $counts;
  }

  /**
   * Reset all daily counts (typically called at midnight).
   *
   * @param string $date
   *   Optional date in Y-m-d format. Defaults to yesterday.
   */
  public function resetDailyCounts(string $date = NULL): void {
    if (!$date) {
      // Default to yesterday to clean up old records
      $date = date('Y-m-d', strtotime('-1 day'));
    }

    // Clean up old records older than 7 days to prevent table bloat
    $cleanup_date = date('Y-m-d', strtotime('-7 days'));
    
    $deleted = $this->database->delete('news_extractor_daily_limits')
      ->condition('processing_date', $cleanup_date, '<')
      ->execute();

    if ($deleted > 0) {
      $this->loggerFactory->get('news_extractor')->info('Cleaned up @count old daily limit records (older than @date)', [
        '@count' => $deleted,
        '@date' => $cleanup_date,
      ]);
    }

    $this->loggerFactory->get('news_extractor')->info('Daily limit tracking reset completed for date @date', [
      '@date' => $date,
    ]);
  }

  /**
   * Get processing statistics for the last N days.
   *
   * @param int $days
   *   Number of days to include in statistics.
   *
   * @return array
   *   Array of daily statistics with counts per source.
   */
  public function getProcessingStatistics(int $days = 7): array {
    $start_date = date('Y-m-d', strtotime("-{$days} days"));
    
    $query = $this->database->select('news_extractor_daily_limits', 'dl')
      ->fields('dl', ['processing_date', 'news_source', 'article_count', 'daily_limit'])
      ->condition('processing_date', $start_date, '>=')
      ->orderBy('processing_date', 'DESC')
      ->orderBy('article_count', 'DESC');

    $results = $query->execute()->fetchAll();
    
    $statistics = [];
    foreach ($results as $row) {
      $date = $row->processing_date;
      if (!isset($statistics[$date])) {
        $statistics[$date] = [
          'date' => $date,
          'sources' => [],
          'total_processed' => 0,
          'sources_at_limit' => 0,
        ];
      }

      $at_limit = $row->article_count >= $row->daily_limit;
      
      $statistics[$date]['sources'][$row->news_source] = [
        'count' => (int) $row->article_count,
        'limit' => (int) $row->daily_limit,
        'at_limit' => $at_limit,
      ];
      
      $statistics[$date]['total_processed'] += (int) $row->article_count;
      if ($at_limit) {
        $statistics[$date]['sources_at_limit']++;
      }
    }

    return $statistics;
  }

  /**
   * Check if the daily limit system is enabled.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  public function isEnabled(): bool {
    $config = $this->configFactory->get('news_extractor.settings');
    return (bool) $config->get('daily_limit_enabled');
  }

}
