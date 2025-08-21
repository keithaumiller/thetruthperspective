<?php

namespace Drupal\newsmotivationmetrics\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface;

/**
 * Service for building authoritarianism timeline chart render arrays.
 * 
 * Specialized service for authoritarianism score trends that filters news source data
 * to show only authoritarianism metrics over time. Follows the same design pattern as
 * the combined NewsSourceTimelineChartService.
 */
class AuthoritarianismTimelineChartService {

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
   * Build authoritarianism timeline chart render array.
   *
   * @param array $options
   *   Chart configuration options.
   *
   * @return array
   *   Render array for authoritarianism timeline chart.
   */
  public function buildAuthoritarianismTimelineChart(array $options = []): array {
    // Set default options
    $options = $options + [
      'limit' => 10,
      'days_back' => 90,
      'source_ids' => [],
      'source_limit' => 5,
    ];

    // Get chart data from the shared service
    $chart_data = $this->chartDataService->getNewsSourceTimelineChartData([
      'limit' => $options['source_limit'],
      'days_back' => $options['days_back'],
    ]);

    // Get extended sources for selector (top 50 sources)
    $extended_sources = $this->chartDataService->getNewsSourceTimelineChartData([
      'limit' => 50,
      'days_back' => $options['days_back'],
    ]);

    if (empty($chart_data['timeline_data'])) {
      return $this->buildEmptyChart($options);
    }

    // Filter data to only include authoritarianism metrics
    $chart_data['timeline_data'] = $this->filterAuthoritarianismData($chart_data['timeline_data']);
    if (!empty($extended_sources['timeline_data'])) {
      $extended_sources['timeline_data'] = $this->filterAuthoritarianismData($extended_sources['timeline_data']);
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
      $build['header'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['chart-header']],
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $options['title'],
          '#attributes' => ['class' => ['chart-section-title']],
        ],
        'description' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => 'This chart shows authoritarianism scores for news sources over the past ' . $options['days_back'] . ' days. Authoritarianism scale: 0 = Democratic/liberal, 50 = Mixed, 100 = Authoritarian/autocratic.',
          '#attributes' => ['class' => ['chart-description']],
        ],
      ];
    }

    // Chart controls (if enabled)
    if ($options['show_controls']) {
      $build['controls'] = $this->buildChartControls($extended_sources, $options['canvas_id']);
    }

    // Chart container and canvas
    $build['chart_container'] = $this->buildChartContainer($options, $chart_data);

    // Chart actions (reset/clear buttons) - REMOVED for cleaner interface
    // $build['actions'] = $this->buildChartActions($options['canvas_id'], $options['source_limit']);

    // Attach JavaScript settings and libraries with isolated namespace
    $build['#attached']['drupalSettings']['newsmotivationmetrics_authoritarianism'] = $this->buildJavaScriptSettings($chart_data, $options, $extended_sources);
    $build['#attached']['library'] = [$options['library']];

    return $build;
  }

  /**
   * Build authoritarianism timeline block for use in Drupal blocks.
   *
   * @param array $config
   *   Block configuration.
   *
   * @return array
   *   Render array for authoritarianism timeline block.
   */
  public function buildAuthoritarianismTimelineBlock(array $config = []): array {
    // Convert block config to chart options
    $options = [
      'canvas_id' => 'authoritarianism-timeline-chart-' . substr(md5(serialize($config)), 0, 8),
      'title' => $config['chart_title'] ?? 'News Source Authoritarianism Trends',
      'show_controls' => $config['show_controls'] ?? TRUE,
      'show_legend' => $config['show_legend'] ?? TRUE,
      'show_title' => TRUE,
      'chart_height' => $config['chart_height'] ?? 400,
      'days_back' => $config['days_back'] ?? 90,
      'source_limit' => $config['source_limit'] ?? 5,
      'container_classes' => ['authoritarianism-timeline-block'],
      'library' => 'newsmotivationmetrics/authoritarianism-timeline',
      'js_behavior' => 'authoritarianismTimelineChart',
    ];

    return $this->buildAuthoritarianismTimelineChart($options);
  }

  /**
   * Filter timeline data to only include authoritarianism metrics.
   *
   * @param array $timeline_data
   *   Full timeline data from service.
   *
   * @return array
   *   Filtered timeline data containing only authoritarianism metrics.
   */
  protected function filterAuthoritarianismData(array $timeline_data): array {
    return array_filter($timeline_data, function($item) {
      return isset($item['metric_type']) && $item['metric_type'] === 'authoritarianism';
    });
  }

  /**
   * Build chart controls section.
   *
   * @param array $extended_sources_data
   *   Array of extended sources data including top_sources.
   * @param string $canvas_id
   *   The canvas element ID.
   *
   * @return array
   *   Render array for chart controls.
   */
  protected function buildChartControls(array $extended_sources_data, string $canvas_id): array {
    $controls = [
      '#type' => 'details',
      '#title' => t('📊 Chart Controls'),
      '#open' => FALSE,
      '#attributes' => ['class' => ['chart-controls-section']],
    ];

    $controls['controls_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-controls']],
    ];

    $controls['controls_container']['selector_group'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['control-group']],
    ];

    $controls['controls_container']['selector_group']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => t('Select News Sources (showing top 50 by article count):'),
      '#attributes' => [
        'for' => $canvas_id === 'authoritarianism-timeline-chart' ? 'source-selector' : 'source-selector-' . substr($canvas_id, -8),
        'class' => ['control-label'],
      ],
    ];

    // Build source options from extended sources
    $all_sources = !empty($extended_sources_data['top_sources']) ? $extended_sources_data['top_sources'] : [];
    $source_options = $this->chartDataService->buildNewsSourceOptionsArray($all_sources);

    $controls['controls_container']['selector_group']['selector'] = [
      '#type' => 'select',
      '#title' => t('News Sources'),
      '#title_display' => 'invisible',
      '#multiple' => TRUE,
      '#size' => min(count($source_options), 8),
      '#options' => $source_options,
      '#attributes' => [
        'id' => $canvas_id === 'authoritarianism-timeline-chart' ? 'source-selector' : 'source-selector-' . substr($canvas_id, -8),
        'class' => ['form-select', 'source-selector'],
        'data-chart-id' => $canvas_id,
        'multiple' => 'multiple',
        'size' => min(count($source_options), 8),
      ],
    ];

    $controls['controls_container']['help_text'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '💡 <strong>Authoritarianism Score Scale:</strong> 0 = Democratic/liberal governance, 50 = Mixed approach, 100 = Authoritarian/autocratic governance. Use Ctrl+Click to select multiple sources.',
      '#attributes' => ['class' => ['help-text', 'text-muted', 'small']],
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
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-container']],
      'canvas' => [
        '#type' => 'html_tag',
        '#tag' => 'canvas',
        '#attributes' => [
          'id' => $options['canvas_id'],
          'class' => ['authoritarianism-timeline-chart'],
          'style' => 'max-width: 100%; height: ' . $options['chart_height'] . 'px; max-height: 500px;',
          'aria-label' => 'News Source Authoritarianism Trends - Interactive Timeline Chart',
        ],
      ],
    ];
  }

  /**
   * Build chart action buttons.
   *
   * @param string $canvas_id
   *   The canvas element ID.
   * @param int $source_limit
   *   The default number of sources to show.
   *
   * @return array
   *   Render array for chart actions.
   */
  protected function buildChartActions(string $canvas_id, int $source_limit): array {
    $unique_id = substr(md5($canvas_id), 0, 8);

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-actions']],
      'reset' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => t('🔄 Reset to Top ' . $source_limit),
        '#attributes' => [
          'id' => 'reset-chart-' . $unique_id,
          'class' => ['btn', 'btn-secondary', 'chart-reset-btn'],
          'type' => 'button',
          'data-canvas-id' => $canvas_id,
        ],
      ],
      'clear' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => t('🧹 Clear All'),
        '#attributes' => [
          'id' => 'clear-chart-' . $unique_id,
          'class' => ['btn', 'btn-outline', 'chart-clear-btn'],
          'type' => 'button',
          'data-canvas-id' => $canvas_id,
        ],
      ],
    ];
  }

  /**
   * Build empty chart when no data is available.
   *
   * @param array $options
   *   Chart options.
   *
   * @return array
   *   Empty chart render array.
   */
  protected function buildEmptyChart(array $options): array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => $options['container_classes']],
      'message' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => '📊 No authoritarianism data available for the selected time period.',
        '#attributes' => ['class' => ['alert', 'alert-info']],
      ],
    ];
  }

  /**
   * Build JavaScript settings for chart initialization.
   *
   * @param array $chart_data
   *   Chart data from service.
   * @param array $options
   *   Chart options.
   * @param array $extended_sources
   *   Extended sources data.
   *
   * @return array
   *   JavaScript settings array.
   */
  protected function buildJavaScriptSettings(array $chart_data, array $options, array $extended_sources = null): array {
    $settings = [
      'timelineData' => $chart_data['timeline_data'],
      'topSources' => $chart_data['top_sources'],
      'debugInfo' => $chart_data['debug_info'],
    ];

    // Add extended sources for background loading if available
    if ($extended_sources && !empty($extended_sources['timeline_data'])) {
      $settings['extendedSources'] = [
        'timelineData' => $extended_sources['timeline_data'],
        'topSources' => $extended_sources['top_sources'],
      ];
    }

    // For block-based charts, organize by canvas ID
    if ($options['js_behavior'] === 'authoritarianismTimelineBlocks') {
      return [
        'blocks' => [
          $options['canvas_id'] => $settings + [
            'config' => [
              'showLegend' => $options['show_legend'],
              'autoRefresh' => $options['auto_refresh'] ?? FALSE,
              'refreshInterval' => $options['refresh_interval'] ?? 300,
              'source_limit' => $options['source_limit'],
            ],
            'canvasId' => $options['canvas_id'],
          ],
        ],
      ];
    }

    // Standard chart settings
    return $settings;
  }

}
