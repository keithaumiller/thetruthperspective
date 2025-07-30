<?php
# Create MetricAnalyzer service
cat > key_metric_management/src/Service/MetricAnalyzer.php << 'EOF'
<?php

namespace Drupal\key_metric_management\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\Entity\Node;

/**
 * Service for analyzing key metrics from articles.
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
   * The logger factory.
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * Batch size for processing nodes.
   */
  const BATCH_SIZE = 100;

  /**
   * Cache TTL (1 hour).
   */
  const CACHE_TTL = 3600;

  /**
   * Constructs a MetricAnalyzer object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    CacheBackendInterface $cache,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->cache = $cache;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Get all unique key metrics from article nodes.
   */
  public function getAllMetrics(bool $use_cache = TRUE): array {
    $cache_key = 'key_metric_management:all_metrics';

    if ($use_cache) {
      $cache = $this->cache->get($cache_key);
      if ($cache && !empty($cache->data)) {
        return $cache->data;
      }
    }

    $metrics = [];

    try {
      $nids = $this->getArticleNodeIds();
      $batches = array_chunk($nids, self::BATCH_SIZE);

      foreach ($batches as $batch) {
        $nodes = Node::loadMultiple($batch);

        foreach ($nodes as $node) {
          $node_metrics = $this->extractMetricsFromNode($node);
          foreach ($node_metrics as $metric) {
            $metric = trim($metric);
            if (!empty($metric)) {
              $metrics[$metric] = ($metrics[$metric] ?? 0) + 1;
            }
          }
        }

        // Free memory
        unset($nodes);
      }

      // Sort by frequency (most common first)
      arsort($metrics);

      // Cache results
      if ($use_cache) {
        $this->cache->set($cache_key, $metrics, time() + self::CACHE_TTL, ['node_list']);
      }

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('key_metric_management')
        ->error('Error loading metrics: @error', ['@error' => $e->getMessage()]);
    }

    return $metrics;
  }

  /**
   * Get count of articles for a specific metric.
   */
  public function getMetricCount(string $metric_name): int {
    $cache_key = 'key_metric_management:metric_count:' . md5($metric_name);
    $cache = $this->cache->get($cache_key);

    if ($cache && isset($cache->data)) {
      return $cache->data;
    }

    $count = 0;

    try {
      // Use database query for performance
      $query = $this->database->select('node__field_motivation_data', 'md')
        ->fields('md', ['entity_id'])
        ->condition('md.field_motivation_data_value', '%"' . $this->database->escapeLike($metric_name) . '"%', 'LIKE')
        ->distinct();

      $nids = $query->execute()->fetchCol();

      if (!empty($nids)) {
        $nodes = Node::loadMultiple($nids);

        foreach ($nodes as $node) {
          $node_metrics = $this->extractMetricsFromNode($node);
          if (in_array($metric_name, $node_metrics, TRUE)) {
            $count++;
          }
        }
      }

      // Cache for 30 minutes
      $this->cache->set($cache_key, $count, time() + 1800, ['node_list']);

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('key_metric_management')
        ->error('Error counting metric @metric: @error', [
          '@metric' => $metric_name,
          '@error' => $e->getMessage(),
        ]);
    }

    return $count;
  }

  /**
   * Get articles for a specific metric.
   */
  public function getArticlesByMetric(string $metric_name, int $limit = 50, int $offset = 0): array {
    $articles = [];

    try {
      // Use database query for performance
      $query = $this->database->select('node__field_motivation_data', 'md')
        ->fields('md', ['entity_id'])
        ->condition('md.field_motivation_data_value', '%"' . $this->database->escapeLike($metric_name) . '"%', 'LIKE')
        ->distinct();

      $nids = $query->execute()->fetchCol();

      if (!empty($nids)) {
        $nodes = Node::loadMultiple($nids);

        foreach ($nodes as $node) {
          $node_metrics = $this->extractMetricsFromNode($node);
          if (in_array($metric_name, $node_metrics, TRUE)) {
            $articles[] = [
              'nid' => $node->id(),
              'title' => $node->getTitle(),
              'created' => $node->getCreatedTime(),
            ];
          }
        }
      }

      // Sort by creation date (newest first)
      usort($articles, fn($a, $b) => $b['created'] - $a['created']);

      // Apply pagination
      $articles = array_slice($articles, $offset, $limit);

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('key_metric_management')
        ->error('Error getting articles for metric @metric: @error', [
          '@metric' => $metric_name,
          '@error' => $e->getMessage(),
        ]);
    }

    return $articles;
  }

  /**
   * Generate metric statistics summary.
   */
  public function getMetricStats(): array {
    $all_metrics = $this->getAllMetrics();
    $total_articles = array_sum($all_metrics);

    return [
      'total_metrics' => count($all_metrics),
      'total_articles_with_metrics' => $total_articles,
      'top_metrics' => array_slice($all_metrics, 0, 10, TRUE),
      'most_common_metric' => !empty($all_metrics) ? array_keys($all_metrics)[0] : 'None',
    ];
  }

  /**
   * Get article node IDs.
   */
  private function getArticleNodeIds(): array {
    $query = $this->database->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('n.type', 'article')
      ->condition('n.status', 1);

    return $query->execute()->fetchCol();
  }

  /**
   * Extract metrics from a node.
   */
  private function extractMetricsFromNode($node): array {
    if (!$node->hasField('field_motivation_data') || $node->get('field_motivation_data')->isEmpty()) {
      return [];
    }

    $json_data = $node->get('field_motivation_data')->value;
    $data = json_decode($json_data, TRUE);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['metrics']) || !is_array($data['metrics'])) {
      return [];
    }

    return array_filter($data['metrics'], fn($metric) => !empty(trim($metric)));
  }

}
EOF