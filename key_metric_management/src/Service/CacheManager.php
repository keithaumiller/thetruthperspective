<?php
# Create CacheManager service
cat > key_metric_management/src/Service/CacheManager.php << 'EOF'
<?php

namespace Drupal\key_metric_management\Service;

use Drupal\Core\Cache\CacheBackendInterface;

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
EOF