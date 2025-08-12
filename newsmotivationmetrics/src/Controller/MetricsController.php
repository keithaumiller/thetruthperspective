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
   * Display the public metrics dashboard.
   * 
   * Block-based approach using existing working timeline chart block.
   * 
   * @return array
   *   Drupal render array for the dashboard page.
   */
  public function dashboard() {
    $block_manager = \Drupal::service('plugin.manager.block');
    
    $build = [];
    
    // Create and render each metrics block
    $header_block = $block_manager->createInstance('metrics_header_block');
    $build['header'] = $header_block->build();
    
    // Use the existing working timeline chart block
    $timeline_block = $block_manager->createInstance('taxonomy_timeline_chart');
    $build['timeline'] = $timeline_block->build();
    
    $overview_block = $block_manager->createInstance('metrics_overview_block');
    $build['overview'] = $overview_block->build();
    
    $methodology_block = $block_manager->createInstance('methodology_block');
    $build['methodology'] = $methodology_block->build();
    
    // Add wrapper for styling
    $build['#type'] = 'container';
    $build['#attributes'] = ['class' => ['metrics-dashboard-content']];
    
    return $build;
  }

  /**
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