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
    $chart_data = $this->chartDataService->getTimelineChartData(['limit' => 20]);
    
    $build = [
      '#theme' => 'chart_debug',
      '#timeline_data' => $chart_data['timeline_data'],
      '#top_terms' => $chart_data['top_terms'],
      '#debug_info' => $chart_data['debug_info'],
    ];
    
    // Attach libraries and pass data
    $build['#attached']['library'][] = 'newsmotivationmetrics/chart-debug-console';
    $build['#attached']['drupalSettings']['newsmotivationmetrics'] = [
      'timelineData' => $chart_data['timeline_data'],
      'topTerms' => $chart_data['top_terms'],
      'debugMode' => TRUE,
      'debugInfo' => $chart_data['debug_info'],
    ];
    
    return $build;
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
