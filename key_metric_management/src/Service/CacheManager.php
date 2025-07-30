<?php
namespace Drupal\key_metric_management\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;

/**
 * Service for analyzing taxonomy relationships with metrics.
 */
class TaxonomyAnalyzer {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The cache backend.
   */
  protected CacheBackendInterface $cache;

  /**
   * The logger factory.
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * The metric analyzer service.
   */
  protected MetricAnalyzer $metricAnalyzer;

  /**
   * Cache TTL (1 hour).
   */
  const CACHE_TTL = 3600;

  /**
   * Constructs a TaxonomyAnalyzer object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CacheBackendInterface $cache,
    LoggerChannelFactoryInterface $logger_factory,
    MetricAnalyzer $metric_analyzer
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
    $this->loggerFactory = $logger_factory;
    $this->metricAnalyzer = $metric_analyzer;
  }

  /**
   * Get metric taxonomy terms.
   */
  public function getMetricTaxonomyTerms(string $vocabulary = 'news_extractor'): array {
    $cache_key = 'key_metric_management:taxonomy_terms:' . $vocabulary;
    $cache = $this->cache->get($cache_key);

    if ($cache && !empty($cache->data)) {
      return $cache->data;
    }

    $metric_terms = [];

    try {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $terms = $term_storage->loadTree($vocabulary);
      $json_metrics = $this->metricAnalyzer->getAllMetrics();

      foreach ($terms as $term) {
        $term_entity = $term_storage->load($term->tid);
        if (!$term_entity) {
          continue;
        }

        $term_name = $term_entity->getName();

        if (isset($json_metrics[$term_name])) {
          $metric_terms[] = [
            'tid' => $term->tid,
            'name' => $term_name,
            'count' => $json_metrics[$term_name],
            'url' => Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $term->tid])->toString(),
          ];
        }
      }

      // Sort by count (highest first)
      usort($metric_terms, fn($a, $b) => $b['count'] - $a['count']);

      // Cache results
      $this->cache->set($cache_key, $metric_terms, time() + self::CACHE_TTL, ['taxonomy_term_list']);

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('key_metric_management')
        ->error('Error loading taxonomy terms: @error', ['@error' => $e->getMessage()]);
    }

    return $metric_terms;
  }

}

/**
 * Service for managing module caches.
 */
class CacheManager {

  /**
   * The cache backend.
   */
  protected CacheBackendInterface $cache;

  /**
   * Cache key patterns.
   */
  const CACHE_PATTERNS = [
    'key_metric_management:all_metrics',
    'key_metric_management:metric_count:*',
    'key_metric_management:taxonomy_terms:*',
  ];

  /**
   * Constructs a CacheManager object.
   */
  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
  }

  /**
   * Invalidate all metric-related caches.
   */
  public function invalidateMetricCaches(): void {
    // Clear specific cache keys
    $this->cache->delete('key_metric_management:all_metrics');

    // Clear cache tags
    $this->cache->invalidateMultiple([
      'key_metric_management:metric_count',
      'key_metric_management:taxonomy_terms',
    ]);
  }

  /**
   * Clear all module caches.
   */
  public function clearAllCaches(): void {
    $this->invalidateMetricCaches();
  }

}
