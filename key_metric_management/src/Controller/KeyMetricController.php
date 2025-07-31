<?php

namespace Drupal\key_metric_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\key_metric_management\Service\MetricAnalyzer;
use Drupal\key_metric_management\Service\TaxonomyAnalyzer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for key metric management pages.
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
   * Dashboard page showing all key metrics.
   */
  public function dashboard(): array {
    $stats = $this->metricAnalyzer->getMetricStats();
    $all_metrics = $this->metricAnalyzer->getAllMetrics();
    
    // Filter to only allowed federal performance metrics
    $allowed_metrics = $this->getAllowedMetrics();
    $filtered_metrics = array_filter($all_metrics, function($metric) use ($allowed_metrics) {
      return in_array($metric, $allowed_metrics);
    }, ARRAY_FILTER_USE_KEY);

    // Prepare metrics with their URLs and taxonomy links
    $metrics_data = [];
    foreach ($filtered_metrics as $metric => $count) {
      $metrics_data[] = [
        'name' => $metric,
        'count' => $count,
        'url' => Url::fromRoute('key_metric_management.metric_detail', ['metric' => $metric])->toString(),
        'taxonomy_url' => $this->getMetricTaxonomyUrl($metric),
        'category' => $this->getMetricCategory($metric),
      ];
    }

    return [
      '#theme' => 'key_metric_stats',
      '#stats' => $stats,
      '#metrics' => $metrics_data,
      '#cache' => [
        'tags' => ['key_metric_management:dashboard'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Detail page for a specific metric.
   */
  public function metricDetail(string $metric): array {
    // Properly decode the metric name from URL
    $metric = $this->decodeMetricFromUrl($metric);
    
    // Check if this is an allowed metric
    if (!$this->isAllowedMetric($metric)) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Metric not found');
    }

    $count = $this->metricAnalyzer->getMetricCount($metric);
    $category = $this->getMetricCategory($metric);
    $taxonomy_url = $this->getMetricTaxonomyUrl($metric);
    $back_url = Url::fromRoute('key_metric_management.dashboard')->toString();

    return [
      '#theme' => 'key_metric_detail',
      '#metric' => $metric,
      '#count' => $count,
      '#category' => $category,
      '#taxonomy_url' => $taxonomy_url,
      '#back_url' => $back_url,
      '#cache' => [
        'tags' => ['key_metric_management:metric:' . $metric],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Title callback for metric detail pages.
   */
  public function metricDetailTitle(string $metric): string {
    $metric = $this->decodeMetricFromUrl($metric);
    return $this->isAllowedMetric($metric) ? $metric : 'Metric Not Found';
  }

  /**
   * Taxonomy metrics overview page.
   */
  public function taxonomyMetrics(): array {
    $taxonomy_terms = $this->taxonomyAnalyzer->getMetricTaxonomyTerms();

    return [
      '#theme' => 'key_metric_taxonomy',
      '#terms' => $taxonomy_terms,
      '#cache' => [
        'tags' => ['taxonomy_term_list'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Decode metric name from URL parameter.
   * 
   * Handles URL encoding where spaces become + or %20.
   */
  private function decodeMetricFromUrl(string $metric): string {
    // First decode URL encoding
    $decoded = urldecode($metric);
    
    // Replace + signs with spaces (common URL encoding for spaces)
    $decoded = str_replace('+', ' ', $decoded);
    
    // Trim any extra whitespace
    return trim($decoded);
  }

  /**
   * Check if a metric is in the allowed list.
   */
  private function isAllowedMetric(string $metric): bool {
    return in_array($metric, $this->getAllowedMetrics());
  }

  /**
   * Get taxonomy URL for a metric by finding matching taxonomy term.
   */
  private function getMetricTaxonomyUrl(string $metric): ?string {
    // Search across all vocabularies for the term
    $term = $this->taxonomyAnalyzer->getTermByName($metric);
    
    if ($term) {
      return Url::fromRoute('entity.taxonomy_term.canonical', [
        'taxonomy_term' => $term->id()
      ])->toString();
    }

    return null;
  }

  /**
   * Get the category for a metric.
   */
  private function getMetricCategory(string $metric): string {
    $categories = [
      'Presidential Approval Rating' => 'Political Performance',
      'Congressional Approval Rating' => 'Political Performance',
      'Trust in Government Index' => 'Political Performance',
      'Government Effectiveness Score' => 'Government Efficiency',
      'Transparency Index' => 'Government Efficiency',
      'Bills Passed Per Session' => 'Legislative Performance',
      'Bipartisan Legislation Rate' => 'Legislative Performance',
      'Committee Productivity' => 'Legislative Performance',
      'Filibuster Usage Rate' => 'Legislative Performance',
      'Executive Order Frequency' => 'Executive Performance',
      'Voter Turnout Rate' => 'Democratic Participation',
      'Voter Registration Rate' => 'Democratic Participation',
      'Electoral Competitiveness' => 'Democratic Participation',
      'Campaign Finance Totals' => 'Democratic Participation',
      'Lobbying Expenditures' => 'Democratic Participation',
      'Federal Judicial Confirmation Rate' => 'Judicial Performance',
      'Government Shutdown Days' => 'Government Efficiency',
      'Federal Employee Satisfaction' => 'Government Efficiency',
      'Congressional Ethics Violations' => 'Political Performance',
      'Polarization Index' => 'Political Performance',
      'GDP Growth Rate' => 'Economic Performance',
      'Unemployment Rate' => 'Economic Performance',
      'Inflation Rate (CPI)' => 'Economic Performance',
      'Federal Funds Rate' => 'Economic Performance',
      'Labor Force Participation Rate' => 'Economic Performance',
      'National Debt' => 'Fiscal Performance',
      'Federal Budget Deficit/Surplus' => 'Fiscal Performance',
      'Debt-to-GDP Ratio' => 'Fiscal Performance',
      'Tax Revenue Growth' => 'Fiscal Performance',
      'Government Spending Growth' => 'Fiscal Performance',
      'Trade Balance' => 'Trade & International',
      'Current Account Balance' => 'Trade & International',
      'Dollar Index (DXY)' => 'Trade & International',
      'Manufacturing Output' => 'Economic Performance',
      'Productivity Growth' => 'Economic Performance',
      'Median Household Income' => 'Social & Economic Welfare',
      'Poverty Rate' => 'Social & Economic Welfare',
      'Income Inequality (Gini Coefficient)' => 'Social & Economic Welfare',
      'Consumer Confidence Index' => 'Economic Performance',
      'Housing Price Index' => 'Economic Performance',
    ];

    return $categories[$metric] ?? 'General';
  }

  /**
   * Get the list of allowed federal performance metrics.
   */
  public function getAllowedMetrics(): array {
    return [
      'Presidential Approval Rating',
      'Congressional Approval Rating',
      'Trust in Government Index',
      'Government Effectiveness Score',
      'Transparency Index',
      'Bills Passed Per Session',
      'Bipartisan Legislation Rate',
      'Committee Productivity',
      'Filibuster Usage Rate',
      'Executive Order Frequency',
      'Voter Turnout Rate',
      'Voter Registration Rate',
      'Electoral Competitiveness',
      'Campaign Finance Totals',
      'Lobbying Expenditures',
      'Federal Judicial Confirmation Rate',
      'Government Shutdown Days',
      'Federal Employee Satisfaction',
      'Congressional Ethics Violations',
      'Polarization Index',
      'GDP Growth Rate',
      'Unemployment Rate',
      'Inflation Rate (CPI)',
      'Federal Funds Rate',
      'Labor Force Participation Rate',
      'National Debt',
      'Federal Budget Deficit/Surplus',
      'Debt-to-GDP Ratio',
      'Tax Revenue Growth',
      'Government Spending Growth',
      'Trade Balance',
      'Current Account Balance',
      'Dollar Index (DXY)',
      'Manufacturing Output',
      'Productivity Growth',
      'Median Household Income',
      'Poverty Rate',
      'Income Inequality (Gini Coefficient)',
      'Consumer Confidence Index',
      'Housing Price Index',
    ];
  }

}
