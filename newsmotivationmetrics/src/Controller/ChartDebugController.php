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
    
    $build = [];
    
    // Page header
    $build['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['debug-header']],
    ];
    
    $build['header']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => '📊 Chart Debug Console',
    ];
    
    $build['header']['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => 'Testing Chart.js integration for The Truth Perspective Analytics with service-based architecture',
    ];
    
    // Debug information section
    $build['debug_info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['debug-info']],
    ];
    
    $build['debug_info']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Debug Information:',
    ];
    
    $build['debug_info']['status'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => 'Initializing services...',
      '#attributes' => ['id' => 'debug-status'],
    ];
    
    $debug_info = $chart_data['debug_info'];
    
    $build['debug_info']['stats'] = [
      '#type' => 'markup',
      '#markup' => '<div>Data Points: ' . $debug_info['dataPoints'] . '</div>
                    <div>Terms Available: ' . $debug_info['termCount'] . '</div>
                    <div>Timestamp: ' . $debug_info['date'] . '</div>
                    <div>Memory Usage: ' . round($debug_info['memory_usage'] / 1024 / 1024, 2) . ' MB</div>
                    <div>Chart.js Version: <span id="chartjs-version">Loading...</span></div>
                    <div>Date Adapter: <span id="date-adapter-status">Loading...</span></div>',
    ];
    
    // Chart controls section
    $build['controls'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-controls']],
    ];
    
    $build['controls']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Chart Controls',
    ];
    
    $build['controls']['selector_group'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['control-group']],
    ];
    
    $build['controls']['selector_group']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => 'Select Terms to Display:',
      '#attributes' => ['for' => 'term-selector'],
    ];
    
    $build['controls']['selector_group']['selector'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $this->chartDataService->buildTermOptionsArray($chart_data['top_terms']),
      '#attributes' => [
        'id' => 'term-selector',
        'size' => 8,
      ],
    ];
    
    $build['controls']['buttons'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['control-buttons']],
    ];
    
    $build['controls']['buttons']['update'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => 'Update Chart',
      '#attributes' => [
        'id' => 'update-chart',
        'class' => ['btn', 'btn-primary'],
        'type' => 'button',
      ],
    ];
    
    $build['controls']['buttons']['reset'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => 'Reset to Top 5',
      '#attributes' => [
        'id' => 'reset-chart',
        'class' => ['btn', 'btn-secondary'],
        'type' => 'button',
      ],
    ];
    
    $build['controls']['buttons']['clear'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => 'Clear All',
      '#attributes' => [
        'id' => 'clear-chart',
        'class' => ['btn', 'btn-outline'],
        'type' => 'button',
      ],
    ];
    
    $build['controls']['buttons']['test_simple'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => 'Test Simple Chart',
      '#attributes' => [
        'id' => 'test-simple',
        'class' => ['btn', 'btn-outline'],
        'type' => 'button',
      ],
    ];
    
    $build['controls']['buttons']['test_date'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => 'Test Date Chart',
      '#attributes' => [
        'id' => 'test-date',
        'class' => ['btn', 'btn-outline'],
        'type' => 'button',
      ],
    ];
    
    // Chart status
    $build['status'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => 'Chart Status: Waiting for initialization...',
      '#attributes' => [
        'id' => 'chart-status',
        'class' => ['chart-status', 'info'],
      ],
    ];
    
    // Chart container
    $build['chart_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-container']],
    ];
    
    $build['chart_container']['canvas'] = [
      '#type' => 'markup',
      '#markup' => '<canvas id="taxonomy-timeline-chart"></canvas>',
    ];
    
    // Raw data preview
    $build['data_preview'] = [
      '#type' => 'details',
      '#title' => 'Raw Data Preview',
      '#attributes' => ['class' => ['debug-info']],
    ];
    
    $build['data_preview']['content'] = [
      '#type' => 'html_tag',
      '#tag' => 'pre',
      '#value' => htmlspecialchars(json_encode($chart_data['timeline_data'], JSON_PRETTY_PRINT)),
      '#attributes' => ['id' => 'data-preview'],
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
      'timelineData' => $chart_data['timeline_data'],
      'topTerms' => $chart_data['top_terms'],
      'debugInfo' => $chart_data['debug_info'],
      'status' => 'success',
    ]);
  }

  /**
   * Test Chart.js library loading and functionality.
   * 
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with library test results.
   */
  public function testLibraries() {
    $chart_data = $this->chartDataService->getTimelineChartData(['limit' => 20]);
    
    $test_results = [
      'dataAvailable' => !empty($chart_data['timeline_data']),
      'termsAvailable' => !empty($chart_data['top_terms']),
      'dataPointCount' => count($chart_data['timeline_data']),
      'termCount' => count($chart_data['top_terms']),
      'timestamp' => time(),
    ];
    
    // Check for data quality
    $terms_with_data = 0;
    foreach ($chart_data['timeline_data'] as $term_data) {
      if (!empty($term_data['data'])) {
        foreach ($term_data['data'] as $point) {
          if ($point['count'] > 0) {
            $terms_with_data++;
            break;
          }
        }
      }
    }
    
    $test_results['termsWithData'] = $terms_with_data;
    $test_results['dataQuality'] = $terms_with_data > 0 ? 'good' : 'no_data';
    
    return new JsonResponse($test_results);
  }

}