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
   * Cache TTL (1 hour).
   */
  const CACHE_TTL = 3600;

  /**
   * Constructs a TaxonomyAnalyzer object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CacheBackendInterface $cache,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
    $this->loggerFactory = $logger_factory;
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
    $cache_key = 'key_metric_management:term_by_name:' . md5($name . ($vocabulary ?? 'tags'));
    
    if ($cached = $this->cache->get($cache_key)) {
      return $cached->data;
    }

    try {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      
      // Search for exact match first
      $terms = $term_storage->loadByProperties([
        'name' => $name,
        'vid' => $vocabulary,
      ]);
      
      if (!empty($terms)) {
        $term = reset($terms);
        $this->cache->set($cache_key, $term, time() + self::CACHE_TTL);
        return $term;
      }

      // If no exact match and we're searching in tags, try partial match
      if ($vocabulary === 'tags') {
        $query = $term_storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('vid', 'tags')
          ->condition('name', $name, 'CONTAINS');
        
        $tids = $query->execute();
        
        if (!empty($tids)) {
          $terms = $term_storage->loadMultiple($tids);
          $term = reset($terms);
          $this->cache->set($cache_key, $term, time() + self::CACHE_TTL);
          return $term;
        }
      }

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('key_metric_management')
        ->error('Error loading term by name "@name": @error', [
          '@name' => $name,
          '@error' => $e->getMessage()
        ]);
    }
    
    $this->cache->set($cache_key, null, time() + self::CACHE_TTL);
    return null;
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
      $this->cache->set($cache_key, $metric_terms, time() + self::CACHE_TTL);

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('key_metric_management')
        ->error('Error loading taxonomy terms: @error', ['@error' => $e->getMessage()]);
    }

    return $metric_terms;
  }

  /**
   * Clear taxonomy-related caches.
   */
  public function clearCache(): void {
    // Clear specific cache keys instead of using invalidateTags
    $cache_keys = [
      'key_metric_management:taxonomy_terms:tags',
    ];
    
    foreach ($cache_keys as $cache_key) {
      $this->cache->delete($cache_key);
    }
    
    // Also clear any term lookup caches by deleting all keys with our prefix
    // Note: This is a simplified approach since we can't use cache tags
    $this->cache->deleteAll();
  }

}