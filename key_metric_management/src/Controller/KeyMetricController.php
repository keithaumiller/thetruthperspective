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
   * List of allowed federal performance metrics.
   *
   * @var array
   */
  protected array $allowedMetrics = [
    // Governance & Leadership
    'Presidential Approval Rating',
    'Congressional Approval Rating', 
    'Trust in Government Index',
    'Government Effectiveness Score',
    'Transparency Index',
    
    // Legislative Performance
    'Bills Passed Per Session',
    'Bipartisan Legislation Rate',
    'Committee Productivity',
    'Filibuster Usage Rate',
    'Executive Order Frequency',
    
    // Democratic Participation
    'Voter Turnout Rate',
    'Voter Registration Rate',
    'Electoral Competitiveness',
    'Campaign Finance Totals',
    'Lobbying Expenditures',
    
    // Institutional Metrics
    'Federal Judicial Confirmation Rate',
    'Government Shutdown Days',
    'Federal Employee Satisfaction',
    'Congressional Ethics Violations',
    'Polarization Index',
    
    // Economic Performance
    'GDP Growth Rate',
    'Unemployment Rate',
    'Inflation Rate (CPI)',
    'Federal Funds Rate',
    'Labor Force Participation Rate',
    
    // Fiscal Health
    'National Debt',
    'Federal Budget Deficit/Surplus',
    'Debt-to-GDP Ratio',
    'Tax Revenue Growth',
    'Government Spending Growth',
    
    // Trade & Competitiveness
    'Trade Balance',
    'Current Account Balance',
    'Dollar Index (DXY)',
    'Manufacturing Output',
    'Productivity Growth',
    
    // Living Standards
    'Median Household Income',
    'Poverty Rate',
    'Income Inequality (Gini Coefficient)',
    'Consumer Confidence Index',
    'Housing Price Index'
  ];

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
   * Check if a metric is in the allowed list.
   */
  protected function isAllowedMetric(string $metric): bool {
    return in_array($metric, $this->allowedMetrics);
  }

  /**
   * Get taxonomy URL for a metric if it exists.
   */
  protected function getMetricTaxonomyUrl(string $metric): ?string {
    if (!$this->isAllowedMetric($metric)) {
      return NULL;
    }
    
    $term = $this->taxonomyAnalyzer->getTermByName($metric);
    if ($term) {
      return Url::fromRoute('entity.taxonomy_term.canonical', [
        'taxonomy_term' => $term->id()
      ])->toString();
    }
    
    return NULL;
  }

  /**
   * Dashboard showing all key metrics.
   */
  public function dashboard(): array {
    $metrics = $this->metricAnalyzer->getAllMetrics();
    $stats = $this->metricAnalyzer->getMetricStats();

    // Filter metrics to only show allowed ones
    $filtered_metrics = array_filter($metrics, function($metric) {
      return $this->isAllowedMetric($metric);
    }, ARRAY_FILTER_USE_KEY);

    $build = [];

    // Stats overview
    $build['stats'] = [
      '#theme' => 'key_metric_stats',
      '#stats' => $stats,
    ];

    // Add info about allowed metrics
    $build['info'] = [
      '#markup' => '<div class="metric-info">' .
        '<h2>' . $this->t('Federal Performance Metrics Dashboard') . '</h2>' .
        '<p>' . $this->t('Tracking @total of @allowed federal performance metrics identified in news articles.', [
          '@total' => count($filtered_metrics),
          '@allowed' => count($this->allowedMetrics)
        ]) . '</p>' .
        '</div>',
    ];

    // Metrics table with taxonomy links
    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Rank'),
        $this->t('Metric Name'), 
        $this->t('Category'),
        $this->t('Article Count'), 
        $this->t('Taxonomy Page'),
        $this->t('Metric Analysis')
      ],
      '#rows' => [],
      '#empty' => $this->t('No allowed metrics found in articles.'),
    ];

    // Sort metrics by count (descending)
    arsort($filtered_metrics);
    
    $rank = 1;
    foreach ($filtered_metrics as $metric => $count) {
      $category = $this->getMetricCategory($metric);
      $taxonomy_url = $this->getMetricTaxonomyUrl($metric);
      
      $taxonomy_link = $taxonomy_url ? [
        'data' => [
          '#type' => 'link',
          '#title' => $this->t('Browse Articles'),
          '#url' => Url::fromUri('internal:' . $taxonomy_url),
          '#attributes' => [
            'class' => ['button', 'button--small'],
            'title' => $this->t('View all articles tagged with @metric', ['@metric' => $metric]),
            'target' => '_blank'
          ],
        ],
      ] : $this->t('No taxonomy term');

      $build['table']['#rows'][] = [
        $rank++,
        [
          'data' => [
            '#markup' => '<strong>' . $metric . '</strong>',
          ],
        ],
        $category,
        $count,
        $taxonomy_link,
        [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('View Details'),
            '#url' => Url::fromRoute('key_metric_management.metric_detail', ['metric' => urlencode($metric)]),
            '#attributes' => ['class' => ['button', 'button--small', 'button--primary']],
          ],
        ],
      ];
    }

    // Attach CSS library
    $build['#attached']['library'][] = 'key_metric_management/global-styling';

    return $build;
  }

  /**
   * Get the category for a metric.
   */
  protected function getMetricCategory(string $metric): string {
    $categories = [
      'Governance & Leadership' => [
        'Presidential Approval Rating', 'Congressional Approval Rating', 
        'Trust in Government Index', 'Government Effectiveness Score', 'Transparency Index'
      ],
      'Legislative Performance' => [
        'Bills Passed Per Session', 'Bipartisan Legislation Rate', 'Committee Productivity',
        'Filibuster Usage Rate', 'Executive Order Frequency'
      ],
      'Democratic Participation' => [
        'Voter Turnout Rate', 'Voter Registration Rate', 'Electoral Competitiveness',
        'Campaign Finance Totals', 'Lobbying Expenditures'
      ],
      'Institutional Metrics' => [
        'Federal Judicial Confirmation Rate', 'Government Shutdown Days', 
        'Federal Employee Satisfaction', 'Congressional Ethics Violations', 'Polarization Index'
      ],
      'Economic Performance' => [
        'GDP Growth Rate', 'Unemployment Rate', 'Inflation Rate (CPI)',
        'Federal Funds Rate', 'Labor Force Participation Rate'
      ],
      'Fiscal Health' => [
        'National Debt', 'Federal Budget Deficit/Surplus', 'Debt-to-GDP Ratio',
        'Tax Revenue Growth', 'Government Spending Growth'
      ],
      'Trade & Competitiveness' => [
        'Trade Balance', 'Current Account Balance', 'Dollar Index (DXY)',
        'Manufacturing Output', 'Productivity Growth'
      ],
      'Living Standards' => [
        'Median Household Income', 'Poverty Rate', 'Income Inequality (Gini Coefficient)',
        'Consumer Confidence Index', 'Housing Price Index'
      ]
    ];

    foreach ($categories as $category => $metrics) {
      if (in_array($metric, $metrics)) {
        return $category;
      }
    }

    return $this->t('Other');
  }

  /**
   * Simple metric detail page showing just the count.
   */
  public function metricDetail(string $metric): array {
    $metric = urldecode($metric);
    
    // Check if metric is allowed
    if (!$this->isAllowedMetric($metric)) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(
        $this->t('Metric "@metric" is not a recognized federal performance metric.', ['@metric' => $metric])
      );
    }
    
    $count = $this->metricAnalyzer->getMetricCount($metric);
    $category = $this->getMetricCategory($metric);
    $taxonomy_url = $this->getMetricTaxonomyUrl($metric);

    $build = [
      '#theme' => 'key_metric_detail',
      '#metric' => $metric,
      '#count' => $count,
      '#category' => $category,
      '#taxonomy_url' => $taxonomy_url,
      '#back_url' => Url::fromRoute('key_metric_management.dashboard')->toString(),
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
    $metric = urldecode($metric);
    return $this->t('Key Metric: @metric', ['@metric' => $metric]);
  }

  /**
   * Page showing key metric taxonomy terms.
   */
  public function taxonomyMetrics(): array {
    $metric_terms = $this->taxonomyAnalyzer->getMetricTaxonomyTerms();
    
    // Filter to only show allowed metrics
    $filtered_terms = array_filter($metric_terms, function($term) {
      return $this->isAllowedMetric($term['name']);
    });
    
    $total_terms = count($filtered_terms);

    $build = [];

    $build['header'] = [
      '#markup' => '<h1>' . $this->t('Federal Performance Metrics with Taxonomy Terms') . '</h1>' .
        '<p>' . $this->t('Showing @count recognized federal performance metrics that have corresponding taxonomy terms.', ['@count' => $total_terms]) . '</p>' .
        '<p><a href="' . Url::fromRoute('key_metric_management.dashboard')->toString() . '">&larr; ' . $this->t('Back to Main Dashboard') . '</a></p><hr>',
    ];

    if (empty($filtered_terms)) {
      $build['empty'] = [
        '#markup' => '<p>' . $this->t('No recognized federal performance metric taxonomy terms found.') . '</p>',
      ];
      $build['#attached']['library'][] = 'key_metric_management/global-styling';
      return $build;
    }

    // Sort by count descending
    usort($filtered_terms, function($a, $b) {
      return $b['count'] <=> $a['count'];
    });

    // Metrics table
    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Rank'),
        $this->t('Metric Name'),
        $this->t('Category'),
        $this->t('Article Count'),
        $this->t('Taxonomy Page'),
        $this->t('Metric Analysis'),
      ],
      '#rows' => [],
    ];

    $rank = 1;
    foreach ($filtered_terms as $term) {
      $category = $this->getMetricCategory($term['name']);
      
      $build['table']['#rows'][] = [
        $rank++,
        [
          'data' => [
            '#markup' => '<strong>' . $term['name'] . '</strong>',
          ],
        ],
        $category,
        $term['count'],
        [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('Browse Articles'),
            '#url' => Url::fromUri('internal:' . $term['url']),
            '#attributes' => [
              'class' => ['button', 'button--small'], 
              'target' => '_blank',
              'title' => $this->t('View all articles tagged with @metric', ['@metric' => $term['name']])
            ],
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

    $build['#attached']['library'][] = 'key_metric_management/global-styling';

    return $build;
  }

  /**
   * Get list of all allowed metrics (for API or other uses).
   */
  public function getAllowedMetrics(): array {
    return $this->allowedMetrics;
  }

}
