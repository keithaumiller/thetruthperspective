<?php

namespace Drupal\key_metric_management\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Service for analyzing key metrics from news articles.
 */
class MetricAnalyzer {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The database connection.
   */
  protected Connection $database;

  /**
   * The cache backend.
   */
  protected CacheBackendInterface $cache;

  /**
   * Constructs a MetricAnalyzer object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, CacheBackendInterface $cache) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->cache = $cache;
  }

  /**
   * Get all metrics with their counts.
   */
  public function getAllMetrics(): array {
    $cache_key = 'key_metric_management:all_metrics';
    
    if ($cached = $this->cache->get($cache_key)) {
      return $cached->data;
    }

    $metrics = [];
    
    try {
      // Query articles for metrics from motivation_data field
      $query = $this->database->select('node__field_motivation_data', 'fmd');
      $query->addField('fmd', 'field_motivation_data_value');
      $query->join('node_field_data', 'nfd', 'fmd.entity_id = nfd.nid');
      $query->condition('nfd.type', 'article');
      $query->condition('nfd.status', 1);
      
      $result = $query->execute();

      foreach ($result as $row) {
        if (!empty($row->field_motivation_data_value)) {
          $data = json_decode($row->field_motivation_data_value, TRUE);
          if (isset($data['metrics']) && is_array($data['metrics'])) {
            foreach ($data['metrics'] as $metric) {
              if (!empty($metric)) {
                $metrics[$metric] = isset($metrics[$metric]) ? $metrics[$metric] + 1 : 1;
              }
            }
          }
        }
      }
    }
    catch (\Exception $e) {
      // Return empty array on database error
      return [];
    }

    $this->cache->set($cache_key, $metrics, time() + 3600);
    return $metrics;
  }

  /**
   * Get count for a specific metric.
   */
  public function getMetricCount(string $metric): int {
    $all_metrics = $this->getAllMetrics();
    return $all_metrics[$metric] ?? 0;
  }

  /**
   * Get metric statistics.
   */
  public function getMetricStats(): array {
    $metrics = $this->getAllMetrics();
    
    return [
      'total_metrics' => count($metrics),
      'total_articles' => array_sum($metrics),
      'top_metrics' => array_slice($metrics, 0, 10, TRUE),
      'most_common_metric' => !empty($metrics) ? array_keys($metrics)[0] : 'None',
    ];
  }

}
