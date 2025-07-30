<?php

namespace Drupal\key_metric_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\key_metric_management\Service\MetricAnalyzer;
use Drupal\key_metric_management\Service\TaxonomyAnalyzer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Key Metric Management pages.
 */
class KeyMetricController extends ControllerBase {

  /**
   * The metric analyzer service.
   */
  protected MetricAnalyzer $metricAnalyzer;

  /**
   * The taxonomy analyzer service.
   */
  protected TaxonomyAnalyzer $taxonomyAnalyzer;

  /**
   * Constructs a KeyMetricController object.
   */
  public function __construct(MetricAnalyzer $metric_analyzer, TaxonomyAnalyzer $taxonomy_analyzer) {
    $this->metricAnalyzer = $metric_analyzer;
    $this->taxonomyAnalyzer = $taxonomy_analyzer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('key_metric_management.metric_analyzer'),
      $container->get('key_metric_management.taxonomy_analyzer')
    );
  }

  /**
   * Dashboard showing all key metrics.
   */
  public function dashboard(): array {
    // ✅ FIXED: Use injected services only
    $metrics = $this->metricAnalyzer->getAllMetrics();
    $stats = $this->metricAnalyzer->getMetricStats();

    $build = [];

    // ✅ FIXED: Proper theme usage with CSS library
    $build['stats'] = [
      '#theme' => 'key_metric_stats',
      '#stats' => $stats,
    ];

    // Metrics table
    $build['table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Metric Name'), $this->t('Article Count'), $this->t('Actions')],
      '#rows' => [],
      '#empty' => $this->t('No metrics found.'),
    ];

    foreach ($metrics as $metric => $count) {
      $build['table']['#rows'][] = [
        $metric,
        $count,
        [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('View Details'),
            '#url' => Url::fromRoute('key_metric_management.metric_detail', ['metric' => urlencode($metric)]),
            '#attributes' => ['class' => ['button', 'button--small']],
          ],
        ],
      ];
    }

    // ✅ FIXED: Attach CSS library to entire page
    $build['#attached']['library'][] = 'key_metric_management/global-styling';

    return $build;
  }

  /**
   * Simple metric detail page showing just the count.
   */
  public function metricDetail(string $metric): array {
    $metric = urldecode($metric);
    // ✅ FIXED: Use injected service only
    $count = $this->metricAnalyzer->getMetricCount($metric);

    $build = [
      '#theme' => 'key_metric_detail',
      '#metric' => $metric,
      '#count' => $count,
      '#back_url' => Url::fromRoute('key_metric_management.dashboard'),
      // ✅ FIXED: Attach CSS library
      '#attached' => [
        'library' => ['key_metric_management/global-styling'],
      ],
    ];

    return $build;
  }

  /**
   * Title callback for metric detail pages.
   */
  public function metricDetailTitle(string $metric): string {
    return $this->t('Key Metric: @metric', ['@metric' => $metric]);
  }

  /**
   * Page showing key metric taxonomy terms.
   */
  public function taxonomyMetrics(): array {
    // ✅ FIXED: Use injected service only
    $metric_terms = $this->taxonomyAnalyzer->getMetricTaxonomyTerms();
    $total_terms = count($metric_terms);

    $build = [];

    $build['header'] = [
      '#markup' => '<h1>' . $this->t('Key Metric Taxonomy Terms') . '</h1>' .
        '<p>' . $this->t('Showing @count taxonomy terms that are identified as key metrics in articles.', ['@count' => $total_terms]) . '</p>' .
        '<p><a href="' . Url::fromRoute('key_metric_management.dashboard')->toString() . '">&larr; ' . $this->t('Back to Main Dashboard') . '</a></p><hr>',
    ];

    if (empty($metric_terms)) {
      $build['empty'] = [
        '#markup' => '<p>' . $this->t('No key metric taxonomy terms found.') . '</p>',
      ];
      // ✅ FIXED: Attach CSS even for empty state
      $build['#attached']['library'][] = 'key_metric_management/global-styling';
      return $build;
    }

    // Metrics table
    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Rank'),
        $this->t('Metric Name'),
        $this->t('Article Count'),
        $this->t('Taxonomy Page'),
        $this->t('Metric Analysis'),
      ],
      '#rows' => [],
    ];

    $rank = 1;
    foreach ($metric_terms as $term) {
      $build['table']['#rows'][] = [
        $rank++,
        $term['name'],
        $term['count'],
        [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('View Taxonomy'),
            '#url' => Url::fromUri('internal:' . $term['url']),
            '#attributes' => ['class' => ['button', 'button--small'], 'target' => '_blank'],
          ],
        ],
        [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('Metric Details'),
            '#url' => Url::fromRoute('key_metric_management.metric_detail', ['metric' => urlencode($term['name'])]),
            '#attributes' => ['class' => ['button', 'button--small', 'button--primary']],
          ],
        ],
      ];
    }

    // ✅ FIXED: Attach CSS library
    $build['#attached']['library'][] = 'key_metric_management/global-styling';

    return $build;
  }

}
