<?php

namespace Drupal\newsmotivationmetrics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface;
use Drupal\newsmotivationmetrics\Service\Interface\TimelineServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Chart debug controller for The Truth Perspective analytics development.
 * 
 * Provides comprehensive debugging tools for Chart.js integration,
 * data validation, and timeline visualization testing using services.
 */
class ChartDebugController extends ControllerBase {

  /**
   * The chart data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface
   */
  protected $chartDataService;

  /**
   * The timeline service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\TimelineServiceInterface
   */
  protected $timelineService;

  /**
   * Constructor.
   *
   * @param \Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface $chart_data_service
   *   The chart data service.
   * @param \Drupal\newsmotivationmetrics\Service\Interface\TimelineServiceInterface $timeline_service
   *   The timeline service.
   */
  public function __construct(
    ChartDataServiceInterface $chart_data_service,
    TimelineServiceInterface $timeline_service
  ) {
    $this->chartDataService = $chart_data_service;
    $this->timelineService = $timeline_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('newsmotivationmetrics.chart_data_service'),
      $container->get('newsmotivationmetrics.timeline_service')
    );
  }

  /**
   * Display the chart debug interface for The Truth Perspective analytics.
   * 
   * @return array
   *   Render array for the debug page with comprehensive chart testing tools.
   */
  public function debugPage() {
    $chart_data = $this->chartDataService->getTimelineChartData(['limit' => 10, 'days_back' => 30]);
    
    // Add debug information about publication dates
    $publication_date_stats = $this->getPublicationDateStats();
    
    $build = [
      '#theme' => 'chart_debug',
      '#timeline_data' => $chart_data['timeline_data'],
      '#top_terms' => $chart_data['top_terms'],
      '#debug_info' => $chart_data['debug_info'],
      '#publication_date_stats' => $publication_date_stats,
    ];
    
    // Attach libraries and pass data
    $build['#attached']['library'][] = 'newsmotivationmetrics/chart-debug-console';
    $build['#attached']['drupalSettings']['newsmotivationmetrics'] = [
      'timelineData' => $chart_data['timeline_data'],
      'topTerms' => $chart_data['top_terms'],
      'debugMode' => TRUE,
      'debugInfo' => array_merge($chart_data['debug_info'], $publication_date_stats),
      'publicationDateStats' => $publication_date_stats,
    ];
    
    return $build;
  }
  
  /**
   * Get statistics about publication date field usage.
   * 
   * @return array
   *   Publication date statistics.
   */
  protected function getPublicationDateStats(): array {
    try {
      $database = \Drupal::database();
      
      // Total articles
      $query = $database->select('node_field_data', 'n');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $total_articles = $query->countQuery()->execute()->fetchField();
      
      // Articles with publication dates
      $query = $database->select('node_field_data', 'n');
      $query->leftJoin('node__field_publication_date', 'pd', 'n.nid = pd.entity_id');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('pd.field_publication_date_value', NULL, 'IS NOT NULL');
      $with_pub_dates = $query->countQuery()->execute()->fetchField();
      
      // Articles with tags
      $query = $database->select('node_field_data', 'n');
      $query->leftJoin('node__field_tags', 'nt', 'n.nid = nt.entity_id');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('nt.field_tags_target_id', NULL, 'IS NOT NULL');
      $with_tags = $query->countQuery()->execute()->fetchField();
      
      // Articles with both publication dates and tags
      $query = $database->select('node_field_data', 'n');
      $query->leftJoin('node__field_publication_date', 'pd', 'n.nid = pd.entity_id');
      $query->leftJoin('node__field_tags', 'nt', 'n.nid = nt.entity_id');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('pd.field_publication_date_value', NULL, 'IS NOT NULL');
      $query->condition('nt.field_tags_target_id', NULL, 'IS NOT NULL');
      $with_both = $query->countQuery()->execute()->fetchField();
      
      return [
        'total_articles' => (int) $total_articles,
        'with_publication_dates' => (int) $with_pub_dates,
        'with_tags' => (int) $with_tags,
        'with_both_dates_and_tags' => (int) $with_both,
        'publication_date_coverage' => $total_articles > 0 ? round(($with_pub_dates / $total_articles) * 100, 1) : 0,
        'tag_coverage' => $total_articles > 0 ? round(($with_tags / $total_articles) * 100, 1) : 0,
        'complete_coverage' => $total_articles > 0 ? round(($with_both / $total_articles) * 100, 1) : 0,
      ];
      
    } catch (\Exception $e) {
      \Drupal::logger('newsmotivationmetrics')->error('Publication date stats error: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      return [
        'total_articles' => 0,
        'with_publication_dates' => 0,
        'with_tags' => 0,
        'with_both_dates_and_tags' => 0,
        'publication_date_coverage' => 0,
        'tag_coverage' => 0,
        'complete_coverage' => 0,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * AJAX endpoint for refreshing debug data.
   * 
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with updated debug data.
   */
  public function refreshData() {
    $chart_data = $this->chartDataService->getTimelineChartData(['limit' => 20]);
    
    return new JsonResponse([
      'status' => 'success',
      'data' => $chart_data,
      'timestamp' => date('Y-m-d H:i:s'),
    ]);
  }

  /**
   * AJAX endpoint for testing Chart.js libraries.
   * 
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with library test results.
   */
  public function testLibraries() {
    return new JsonResponse([
      'status' => 'success',
      'message' => 'Library test endpoint available',
      'chart_js_available' => TRUE,
      'timestamp' => date('Y-m-d H:i:s'),
    ]);
  }

}
