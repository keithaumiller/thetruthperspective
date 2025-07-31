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
   * Get taxonomy term by name.
   *
   * @param string $name
   *   The term name to search for.
   * @param string $vocabulary
   *   The vocabulary machine name (defaults to 'tags').
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The taxonomy term or null if not found.
   */
  public function getTermByName(string $name, string $vocabulary = 'tags'): ?object {
    try {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      
      $terms = $term_storage->loadByProperties([
        'name' => $name,
        'vid' => $vocabulary,
      ]);
      
      if (!empty($terms)) {
        return reset($terms);
      }
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('key_metric_management')
        ->error('Error loading term by name "@name": @error', [
          '@name' => $name,
          '@error' => $e->getMessage()
        ]);
    }
    
    return NULL;
  }

  /**
   * Get metric taxonomy terms.
   */
  public function getMetricTaxonomyTerms(string $vocabulary = 'tags'): array {
    $cache_key = 'key_metric_management:taxonomy_terms:' . $vocabulary;
    $cache = $this->cache->get($cache_key);

    if ($cache && !empty($cache->data)) {
      return $cache->data;
    }

    $metric_terms = [];

    try {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $node_storage = $this->entityTypeManager->getStorage('node');
      
      // Get all terms from the specified vocabulary
      $terms = $term_storage->loadByProperties(['vid' => $vocabulary]);
      
      foreach ($terms as $term) {
        $term_name = $term->getName();
        
        // Query for articles tagged with this term
        $query = $node_storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('type', 'article')
          ->condition('status', 1)
          ->condition('field_tags', $term->id());
        
        $count = $query->count()->execute();
        
        if ($count > 0) {
          $metric_terms[] = [
            'tid' => $term->id(),
            'name' => $term_name,
            'count' => $count,
            'url' => Url::fromRoute('entity.taxonomy_term.canonical', [
              'taxonomy_term' => $term->id()
            ])->toString(),
            'term' => $term,
          ];
        }
      }

      // Sort by count (highest first)
      usort($metric_terms, fn($a, $b) => $b['count'] <=> $a['count']);

      // Cache results
      $this->cache->set($cache_key, $metric_terms, time() + self::CACHE_TTL, ['taxonomy_term_list']);

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('key_metric_management')
        ->error('Error loading taxonomy terms: @error', ['@error' => $e->getMessage()]);
    }

    return $metric_terms;
  }

  /**
   * Get terms that match federal performance metrics.
   *
   * @param array $allowed_metrics
   *   Array of allowed metric names.
   *
   * @return array
   *   Array of terms that match allowed metrics.
   */
  public function getMetricTermsByAllowedList(array $allowed_metrics): array {
    $all_terms = $this->getMetricTaxonomyTerms();
    
    return array_filter($all_terms, function($term) use ($allowed_metrics) {
      return in_array($term['name'], $allowed_metrics);
    });
  }

  /**
   * Get articles tagged with a specific metric term.
   *
   * @param string $metric_name
   *   The metric name.
   * @param int $limit
   *   Number of articles to return.
   * @param int $offset
   *   Offset for pagination.
   *
   * @return array
   *   Array of node entities.
   */
  public function getArticlesByMetricTerm(string $metric_name, int $limit = 20, int $offset = 0): array {
    $term = $this->getTermByName($metric_name);
    
    if (!$term) {
      return [];
    }
    
    try {
      $node_storage = $this->entityTypeManager->getStorage('node');
      
      $query = $node_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'article')
        ->condition('status', 1)
        ->condition('field_tags', $term->id())
        ->sort('created', 'DESC')
        ->range($offset, $limit);
      
      $nids = $query->execute();
      
      if (empty($nids)) {
        return [];
      }
      
      return $node_storage->loadMultiple($nids);
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('key_metric_management')
        ->error('Error loading articles by metric term "@metric": @error', [
          '@metric' => $metric_name,
          '@error' => $e->getMessage()
        ]);
      
      return [];
    }
  }

  /**
   * Clear taxonomy-related caches.
   */
  public function clearCache(): void {
    $cache_tags = ['taxonomy_term_list'];
    $this->cache->invalidateTags($cache_tags);
    
    // Also clear specific cache keys
    $vocabularies = ['tags', 'news_extractor'];
    foreach ($vocabularies as $vocabulary) {
      $cache_key = 'key_metric_management:taxonomy_terms:' . $vocabulary;
      $this->cache->delete($cache_key);
    }
  }

}