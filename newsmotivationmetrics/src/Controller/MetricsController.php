<?php

namespace Drupal\newsmotivationmetrics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\newsmotivationmetrics\Service\Interface\DashboardBuilderServiceInterface;
use Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for News Motivation Metrics pages.
 * 
 * Provides public analytics dashboard and admin interfaces for The Truth
 * Perspective news analysis system. Uses dependency injection and services
 * following Drupal 11 best practices.
 */
class MetricsController extends ControllerBase {

  /**
   * The dashboard builder service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\DashboardBuilderServiceInterface
   */
  protected $dashboardBuilder;

  /**
   * The chart data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface
   */
  protected $chartDataService;

  /**
   * The metrics data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface
   */
  protected $metricsDataService;

  /**
   * Constructor.
   *
   * @param \Drupal\newsmotivationmetrics\Service\Interface\DashboardBuilderServiceInterface $dashboard_builder
   *   The dashboard builder service.
   * @param \Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface $chart_data_service
   *   The chart data service.
   * @param \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface $metrics_data_service
   *   The metrics data service.
   */
  public function __construct(
    DashboardBuilderServiceInterface $dashboard_builder,
    ChartDataServiceInterface $chart_data_service,
    MetricsDataServiceInterface $metrics_data_service
  ) {
    $this->dashboardBuilder = $dashboard_builder;
    $this->chartDataService = $chart_data_service;
    $this->metricsDataService = $metrics_data_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('newsmotivationmetrics.dashboard_builder'),
      $container->get('newsmotivationmetrics.chart_data_service'),
      $container->get('newsmotivationmetrics.metrics_data_service')
    );
  }

  /**
   * Display the metrics dashboard.
   */
  public function dashboard() {
    $block_manager = $this->blockManager;
    
    // Create header block
    $header_block = $block_manager->createInstance('metrics_header', []);
    $header_content = $header_block->build();
    
    // Create content analysis overview block
    $content_analysis_block = $block_manager->createInstance('content_analysis_overview', []);
    $content_analysis_content = $content_analysis_block->build();
    
    // Create temporal processing analytics block
    $temporal_block = $block_manager->createInstance('temporal_processing_analytics', []);
    $temporal_content = $temporal_block->build();
    
    // Create sentiment distribution analysis block
    $sentiment_block = $block_manager->createInstance('sentiment_distribution_analysis', []);
    $sentiment_content = $sentiment_block->build();
    
    // Create timeline chart block (using existing working implementation)
    $timeline_block = $block_manager->createInstance('taxonomy_timeline_chart', []);
    $timeline_content = $timeline_block->build();
    
    // Create entity recognition metrics block
    $entity_block = $block_manager->createInstance('entity_recognition_metrics', []);
    $entity_content = $entity_block->build();
    
    // Create recent activity metrics block
    $activity_block = $block_manager->createInstance('recent_activity_metrics', []);
    $activity_content = $activity_block->build();
    
    // Create analysis quality metrics block
    $quality_block = $block_manager->createInstance('analysis_quality_metrics', []);
    $quality_content = $quality_block->build();
    
    // Create about truth perspective analytics block
    $about_block = $block_manager->createInstance('about_truth_perspective_analytics', []);
    $about_content = $about_block->build();
    
    // Return all blocks in organized layout
    return [
      '#theme' => 'container',
      '#attributes' => ['class' => ['metrics-dashboard', 'fullwidth-content']],
      'header' => $header_content,
      'content_analysis' => $content_analysis_content,
      'temporal_analytics' => $temporal_content,
      'sentiment_analysis' => $sentiment_content,
      'timeline_chart' => $timeline_content,
      'entity_metrics' => $entity_content,
      'activity_metrics' => $activity_content,
      'quality_metrics' => $quality_content,
      'about_info' => $about_content,
      '#attached' => [
        'library' => [
          'newsmotivationmetrics/chart-behavior',
          'newsmotivationmetrics/chart-styles',
        ],
      ],
    ];
  }  /**
   * Display the admin version of the dashboard.
   * 
   * @return array
   *   Drupal render array for admin dashboard.
   */
  public function adminDashboard() {
    return $this->dashboardBuilder->buildAdminDashboard();
  }
  
  /**
   * Display details for a specific taxonomy term.
   * 
   * @param int $tid
   *   Taxonomy term ID.
   * 
   * @return array
   *   Drupal render array for tag details page.
   * 
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   When taxonomy term is not found.
   */
  public function tagDetails($tid) {
    $term = Term::load($tid);
    
    if (!$term) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    
    $build = [];
    
    $build['header'] = [
      '#markup' => '<h1>Tag Details: ' . $term->getName() . '</h1>',
    ];
    
    $build['back'] = [
      '#markup' => '<p>' . Link::createFromRoute('← Back to Metrics Dashboard', 'newsmotivationmetrics.metrics_dashboard')->toString() . '</p>',
    ];
    
    return $build;
  }

}