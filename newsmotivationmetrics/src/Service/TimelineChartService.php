<?php

namespace Drupal\newsmotivationmetrics\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface;
use Drupal\newsmotivationmetrics\Service\Interface\TimelineChartServiceInterface;

/**
 * Service for building timeline chart render arrays.
 * 
 * Centralizes timeline chart construction logic to eliminate duplication
 * between dashboard pages and block implementations.
 */
class TimelineChartService implements TimelineChartServiceInterface {

  /**
   * The chart data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface
   */
  protected $chartDataService;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface $chart_data_service
   *   The chart data service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ChartDataServiceInterface $chart_data_service,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->chartDataService = $chart_data_service;
    $this->logger = $logger_factory->get('newsmotivationmetrics');
  }

  /**
   * {@inheritdoc}
   */
  public function buildTimelineChart(array $options = []): array {
    // Set default options
    $options = $options + [
      'canvas_id' => 'taxonomy-timeline-chart',
      'title' => 'Topic Trends Over Time',
      'show_controls' => TRUE,
      'show_legend' => TRUE,
      'show_title' => TRUE,
      'chart_height' => 400,
      'days_back' => 30,
      'term_limit' => 10,
      'container_classes' => ['taxonomy-timeline-section'],
      'library' => 'newsmotivationmetrics/chart-js',
      'js_behavior' => 'taxonomyTimelineChart',
    ];

    // Get chart data
    $chart_data = $this->chartDataService->getTimelineChartData([
      'limit' => $options['term_limit'],
      'days_back' => $options['days_back'],
    ]);

    if (empty($chart_data['timeline_data'])) {
      return $this->buildEmptyChart($options);
    }

    // Build the complete chart render array
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => $options['container_classes'],
        'id' => $options['canvas_id'] . '-container',
      ],
    ];

    // Chart title (if enabled)
    if ($options['show_title'] && !empty($options['title'])) {
      $build['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $options['title'],
        '#attributes' => ['class' => ['chart-section-title']],
      ];
    }

    // Chart controls (if enabled)
    if ($options['show_controls']) {
      $build['controls'] = $this->buildChartControls(
        $chart_data['top_terms'], 
        $options['canvas_id']
      );
    }

    // Chart container and canvas
    $build['chart_wrapper'] = $this->buildChartContainer($options, $chart_data);

    // Attach JavaScript libraries and settings
    $build['#attached']['library'][] = $options['library'];
    $build['#attached']['drupalSettings']['newsmotivationmetrics'] = $this->buildJavaScriptSettings(
      $chart_data, 
      $options
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTimelineBlock(array $config = []): array {
    // Convert block config to chart options
    $options = [
      'canvas_id' => 'taxonomy-timeline-chart-' . substr(md5(serialize($config)), 0, 8),
      'title' => $config['chart_title'] ?? 'Topic Trends Over Time',
      'show_controls' => $config['show_controls'] ?? TRUE,
      'show_legend' => $config['show_legend'] ?? TRUE,
      'show_title' => !empty($config['chart_title']),
      'chart_height' => min($config['chart_height'] ?? 400, 500),
      'days_back' => $config['days_back'] ?? 30,
      'term_limit' => $config['term_limit'] ?? 10,
      'container_classes' => ['taxonomy-timeline-block', 'taxonomy-timeline-section'],
      'library' => 'newsmotivationmetrics/chart-blocks',
      'js_behavior' => 'taxonomyTimelineBlocks',
      'auto_refresh' => $config['auto_refresh'] ?? FALSE,
      'refresh_interval' => $config['refresh_interval'] ?? 300,
    ];

    return $this->buildTimelineChart($options);
  }

  /**
   * Build chart controls section.
   *
   * @param array $top_terms
   *   Array of top terms data.
   * @param string $canvas_id
   *   The canvas element ID.
   *
   * @return array
   *   Render array for chart controls.
   */
  protected function buildChartControls(array $top_terms, string $canvas_id): array {
    $controls = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-controls']],
    ];

    $controls['selector_group'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['control-group']],
    ];

    $controls['selector_group']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => t('Add/Remove Terms:'),
      '#attributes' => ['for' => $canvas_id === 'taxonomy-timeline-chart' ? 'term-selector' : 'term-selector-' . $this->getUniqueId($canvas_id)],
    ];

    $controls['selector_group']['selector'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $this->chartDataService->buildTermOptionsArray($top_terms),
      '#default_value' => array_slice(array_column($top_terms, 'tid'), 0, 10),
      '#attributes' => [
        'class' => ['term-selector'],
        'id' => $canvas_id === 'taxonomy-timeline-chart' ? 'term-selector' : 'term-selector-' . $this->getUniqueId($canvas_id),
        'size' => 8,
        'data-canvas-id' => $canvas_id,
      ],
    ];

    $controls['buttons'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['control-buttons']],
    ];

    $unique_id = $this->getUniqueId($canvas_id);

    $controls['buttons']['reset'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => t('Reset to Top 10'),
      '#attributes' => [
        'id' => $canvas_id === 'taxonomy-timeline-chart' ? 'reset-chart' : 'reset-chart-' . $unique_id,
        'class' => ['btn', 'btn-secondary', 'chart-reset-btn'],
        'type' => 'button',
        'data-canvas-id' => $canvas_id,
      ],
    ];

    $controls['buttons']['clear'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => t('Clear All'),
      '#attributes' => [
        'id' => $canvas_id === 'taxonomy-timeline-chart' ? 'clear-chart' : 'clear-chart-' . $unique_id,
        'class' => ['btn', 'btn-outline', 'chart-clear-btn'],
        'type' => 'button',
        'data-canvas-id' => $canvas_id,
      ],
    ];

    $controls['info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-info']],
    ];

    $controls['info']['text'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => t('📊 Showing frequency of topic mentions over the last @days days', [
        '@days' => 30, // This could be dynamic based on options
      ]),
      '#attributes' => ['class' => ['info-text']],
    ];

    return $controls;
  }

  /**
   * Build chart container with canvas element.
   *
   * @param array $options
   *   Chart options array.
   * @param array $chart_data
   *   Chart data array.
   *
   * @return array
   *   Render array for chart container.
   */
  protected function buildChartContainer(array $options, array $chart_data): array {
    $container = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-container']],
    ];

    // Canvas element
    $container['canvas'] = [
      '#type' => 'html_tag',
      '#tag' => 'canvas',
      '#attributes' => [
        'id' => $options['canvas_id'],
        'width' => 800,
        'height' => min($options['chart_height'], 500),
        'style' => 'max-width: 100%; height: auto; max-height: 500px;',
        'aria-label' => $options['title'] . ' - Interactive Timeline Chart',
      ],
    ];

    // Status/debug container
    $container['debug'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'chart-debug-info-' . $this->getUniqueId($options['canvas_id'])],
    ];

    $container['debug']['status'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => 'Chart ready',
      '#attributes' => [
        'id' => $options['canvas_id'] === 'taxonomy-timeline-chart' ? 'chart-status' : 'chart-status-' . $this->getUniqueId($options['canvas_id']),
        'class' => ['chart-status', 'info'],
      ],
    ];

    return $container;
  }

  /**
   * Build JavaScript settings for chart initialization.
   *
   * @param array $chart_data
   *   Chart data from service.
   * @param array $options
   *   Chart options.
   *
   * @return array
   *   JavaScript settings array.
   */
  protected function buildJavaScriptSettings(array $chart_data, array $options): array {
    $settings = [
      'timelineData' => $chart_data['timeline_data'],
      'topTerms' => $chart_data['top_terms'],
      'debugInfo' => $chart_data['debug_info'],
    ];

    // For block-based charts, organize by canvas ID
    if ($options['js_behavior'] === 'taxonomyTimelineBlocks') {
      return [
        'blocks' => [
          $options['canvas_id'] => $settings + [
            'config' => [
              'showLegend' => $options['show_legend'],
              'autoRefresh' => $options['auto_refresh'] ?? FALSE,
              'refreshInterval' => $options['refresh_interval'] ?? 300,
              'term_limit' => $options['term_limit'],
            ],
            'canvasId' => $options['canvas_id'],
          ],
        ],
      ];
    }

    // For standard dashboard charts
    return $settings;
  }

  /**
   * Build empty chart render array when no data is available.
   *
   * @param array $options
   *   Chart options array.
   *
   * @return array
   *   Render array for empty state.
   */
  protected function buildEmptyChart(array $options): array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => $options['container_classes'] + ['chart-empty']],
      'message' => [
        '#markup' => '<div class="chart-no-data">' . 
                     '<h3>' . $options['title'] . '</h3>' .
                     '<p>' . t('No timeline data available for the selected period.') . '</p>' .
                     '</div>',
      ],
      '#cache' => [
        'max-age' => 300,
        'tags' => ['taxonomy_term_list', 'node_list'],
      ],
    ];
  }

  /**
   * Generate a unique ID suffix from canvas ID.
   *
   * @param string $canvas_id
   *   The canvas ID.
   *
   * @return string
   *   Unique identifier.
   */
  protected function getUniqueId(string $canvas_id): string {
    return substr(md5($canvas_id), 0, 8);
  }

}
