<?php

namespace Drupal\newsmotivationmetrics\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\newsmotivationmetrics\Service\Interface\TimelineServiceInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;

/**
 * Service for retrieving timeline and temporal data.
 * 
 * Optimized for Chart.js consumption with proper date handling
 * and efficient database queries for large datasets.
 */
class TimelineService implements TimelineServiceInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The metrics data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface
   */
  protected $metricsDataService;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface $metrics_data_service
   *   The metrics data service.
   */
  public function __construct(
    Connection $database,
    LoggerChannelFactoryInterface $logger_factory,
    MetricsDataServiceInterface $metrics_data_service
  ) {
    $this->database = $database;
    $this->loggerFactory = $logger_factory;
    $this->metricsDataService = $metrics_data_service;
  }

  /**
   * {@inheritdoc}
   */
  public function getTaxonomyTimelineData(int $limit = 10, int $days_back = 90): array {
    $timeline_data = [];
    
    try {
      $top_terms = $this->getTopTaxonomyTerms($limit);
      
      foreach ($top_terms as $term) {
        $term_data = [
          'term_id' => $term['tid'],
          'term_name' => $term['name'],
          'data' => [],
        ];
        
        for ($i = $days_back; $i >= 0; $i--) {
          $date = date('Y-m-d', strtotime("-{$i} days"));
          $count = $this->getArticleCountForDateRange($date, $date, [$term['tid']]);
          
          $term_data['data'][] = [
            'date' => $date,
            'count' => (int) $count,
          ];
        }
        
        $timeline_data[] = $term_data;
      }
      
    } catch (\Exception $e) {
      $this->loggerFactory->get('newsmotivationmetrics')->error('Timeline data error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
    
    return $timeline_data;
  }

  /**
   * {@inheritdoc}
   */
  public function getTopTaxonomyTerms(int $limit = 10): array {
    $terms = [];
    
    try {
      $query = $this->database->select('node__field_tags', 'nt');
      $query->leftJoin('taxonomy_term_field_data', 't', 'nt.field_tags_target_id = t.tid');
      $query->leftJoin('node_field_data', 'n', 'nt.entity_id = n.nid');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('t.status', 1);
      $query->addField('t', 'tid');
      $query->addField('t', 'name');
      $query->addExpression('COUNT(*)', 'usage_count');
      $query->groupBy('t.tid');
      $query->groupBy('t.name');
      $query->orderBy('usage_count', 'DESC');
      $query->range(0, $limit);
      
      $results = $query->execute()->fetchAll();
      
      foreach ($results as $result) {
        $terms[] = [
          'tid' => $result->tid,
          'name' => $result->name,
          'usage_count' => $result->usage_count,
        ];
      }
      
    } catch (\Exception $e) {
      $this->loggerFactory->get('newsmotivationmetrics')->error('Top terms error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
    
    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  public function getArticleCountForDateRange(string $start_date, string $end_date, array $term_ids = []): int {
    try {
      // Convert input dates to proper format for comparison
      $start_date_only = date('Y-m-d', strtotime($start_date));
      $end_date_only = date('Y-m-d', strtotime($end_date));
      $start_timestamp = strtotime($start_date . ' 00:00:00');
      $end_timestamp = strtotime($end_date . ' 23:59:59');
      
      if (empty($term_ids)) {
        // Count all articles in date range using publication date field
        $query = $this->database->select('node_field_data', 'n');
        $query->leftJoin('node__field_publication_date', 'pd', 'n.nid = pd.entity_id');
        $query->condition('n.type', 'article');
        $query->condition('n.status', 1);
        
        // Use publication date if available, fallback to created date
        $or_group = $query->orConditionGroup();
        
        // Primary: publication date matches range (handle both date and datetime formats)
        $pub_date_group = $query->andConditionGroup();
        $pub_date_group->condition('pd.field_publication_date_value', NULL, 'IS NOT NULL');
        // For date-only fields, use LIKE for date comparison
        $pub_date_group->condition('pd.field_publication_date_value', $start_date_only . '%', '>=');
        $pub_date_group->condition('pd.field_publication_date_value', $end_date_only . '%', '<=');
        $or_group->condition($pub_date_group);
        
        // Fallback: no publication date but created date matches
        $fallback_group = $query->andConditionGroup();
        $fallback_group->condition('pd.field_publication_date_value', NULL, 'IS NULL');
        $fallback_group->condition('n.created', $start_timestamp, '>=');
        $fallback_group->condition('n.created', $end_timestamp, '<=');
        $or_group->condition($fallback_group);
        
        $query->condition($or_group);
        
        return $query->countQuery()->execute()->fetchField();
      } else {
        // Count articles with specific taxonomy terms using publication date
        $query = $this->database->select('node__field_tags', 'nt');
        $query->leftJoin('node_field_data', 'n', 'nt.entity_id = n.nid');
        $query->leftJoin('node__field_publication_date', 'pd', 'n.nid = pd.entity_id');
        $query->condition('nt.field_tags_target_id', $term_ids, 'IN');
        $query->condition('n.type', 'article');
        $query->condition('n.status', 1);
        
        // Use publication date if available, fallback to created date
        $or_group = $query->orConditionGroup();
        
        // Primary: publication date matches range (handle both date and datetime formats)
        $pub_date_group = $query->andConditionGroup();
        $pub_date_group->condition('pd.field_publication_date_value', NULL, 'IS NOT NULL');
        // Use DATE() function to extract date part for comparison
        $pub_date_group->where("DATE(pd.field_publication_date_value) >= :start_date", [':start_date' => $start_date_only]);
        $pub_date_group->where("DATE(pd.field_publication_date_value) <= :end_date", [':end_date' => $end_date_only]);
        $or_group->condition($pub_date_group);
        
        // Fallback: no publication date but created date matches
        $fallback_group = $query->andConditionGroup();
        $fallback_group->condition('pd.field_publication_date_value', NULL, 'IS NULL');
        $fallback_group->condition('n.created', $start_timestamp, '>=');
        $fallback_group->condition('n.created', $end_timestamp, '<=');
        $or_group->condition($fallback_group);
        
        $query->condition($or_group);
        
        return $query->countQuery()->execute()->fetchField();
      }
      
    } catch (\Exception $e) {
      $this->loggerFactory->get('newsmotivationmetrics')->error('Date range count error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return 0;
    }
  }

}
