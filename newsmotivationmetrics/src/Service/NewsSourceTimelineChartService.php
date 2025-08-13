<?php

namespace Drupal\newsmotivationmetrics\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface;
use Drupal\newsmotivationmetrics\Service\Interface\NewsSourceTimelineChartServiceInterface;

/**
 * Service for building news source timeline chart render arrays.
 * 
 * Centralizes news source timeline chart construction logic to eliminate duplication
 * between dashboard pages and block implementations. This service specifically handles
 * timeline charts that display credibility, bias, and sentiment trends by news source.
 */
class NewsSourceTimelineChartService implements NewsSourceTimelineChartServiceInterface {

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
  public function buildNewsSourceTimelineChart(array $options = []): array {
    // Set default options
    $options = $options + [
      'canvas_id' => 'news-source-timeline-chart',
      'title' => 'News Source Quality Trends Over Time',
      'show_controls' => TRUE,
      'show_legend' => TRUE,
      'show_title' => TRUE,
      'chart_height' => 400,
      'days_back' => 30,
      'source_limit' => 3, // Fewer sources since each has 3 metrics
      'container_classes' => ['news-source-timeline-section'],
      'library' => 'newsmotivationmetrics/news-source-timeline',
      'js_behavior' => 'newsSourceTimelineChart',
    ];

    // Get chart data
    $chart_data = $this->chartDataService->getNewsSourceTimelineChartData([
      'limit' => $options['source_limit'],
      'days_back' => $options['days_back'],
    ]);

    // Get extended sources for selector (top 20 sources)
    $extended_sources = $this->chartDataService->getNewsSourceTimelineChartData([
      'limit' => 50, // Increase to show more sources in selector
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

    // Status and description
    $build['status'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-status-container']],
    ];

    $build['status']['message'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '📊 Loading news source quality trends...',
      '#attributes' => [
        'class' => ['chart-status-message'],
        'id' => $options['canvas_id'] === 'news-source-timeline-chart' ? 'chart-status' : 'chart-status-' . substr($options['canvas_id'], -8),
      ],
    ];

    $build['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => 'This chart shows credibility scores, bias ratings, and sentiment trends for the top news sources (by article count) over the past ' . $options['days_back'] . ' days. Colors: CNN=Blue, Fox=Red, third source=Green.',
      '#attributes' => ['class' => ['chart-description']],
    ];

    // Chart controls (if enabled)
    if ($options['show_controls']) {
      $build['controls'] = $this->buildChartControls($extended_sources, $options['canvas_id']);
    }

    // Chart canvas
    $build['chart_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-container']],
    ];

    $build['chart_container']['canvas'] = [
      '#type' => 'html_tag',
      '#tag' => 'canvas',
      '#attributes' => [
        'id' => $options['canvas_id'],
        'class' => ['news-source-timeline-chart'],
        'style' => 'max-width: 100%; height: ' . $options['chart_height'] . 'px; max-height: 500px;',
        'aria-label' => 'News Source Quality Trends - Interactive Timeline Chart',
      ],
    ];

    // Chart reset button
    $build['chart_container']['reset_button'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => '🔄 Reset to Top ' . $options['source_limit'],
      '#attributes' => [
        'id' => $options['canvas_id'] === 'news-source-timeline-chart' ? 'reset-chart' : 'reset-chart-' . substr($options['canvas_id'], -8),
        'class' => ['btn', 'btn-sm', 'btn-secondary', 'chart-reset-button'],
        'style' => 'margin-top: 10px; margin-right: 5px;',
        'title' => 'Reset chart to show top ' . $options['source_limit'] . ' news sources',
      ],
    ];

    // Chart clear button
    $build['chart_container']['clear_button'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => '🧹 Clear All',
      '#attributes' => [
        'id' => $options['canvas_id'] === 'news-source-timeline-chart' ? 'clear-chart' : 'clear-chart-' . substr($options['canvas_id'], -8),
        'class' => ['btn', 'btn-sm', 'btn-outline-secondary', 'chart-clear-button'],
        'style' => 'margin-top: 10px;',
        'title' => 'Clear all selected sources',
      ],
    ];

    // Attach JavaScript settings and libraries with isolated namespace
    $build['#attached']['drupalSettings']['newsmotivationmetrics_sources'] = $this->buildJavaScriptSettings($chart_data, $options, $extended_sources);
    $build['#attached']['library'] = [$options['library']];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildNewsSourceTimelineBlock(array $config = []): array {
    // Convert block config to chart options
    $options = [
      'canvas_id' => 'news-source-timeline-chart-' . substr(md5(serialize($config)), 0, 8),
      'title' => $config['chart_title'] ?? 'News Source Quality Trends',
      'show_controls' => $config['show_controls'] ?? TRUE,
      'show_legend' => $config['show_legend'] ?? TRUE,
      'show_title' => TRUE,
      'chart_height' => $config['chart_height'] ?? 400,
      'days_back' => $config['days_back'] ?? 30,
      'source_limit' => $config['source_limit'] ?? 5,
      'container_classes' => ['news-source-timeline-block'],
      'library' => 'newsmotivationmetrics/news-source-timeline',
      'js_behavior' => 'newsSourceTimelineChart',
    ];

    return $this->buildNewsSourceTimelineChart($options);
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
        'for' => $canvas_id === 'news-source-timeline-chart' ? 'source-selector' : 'source-selector-' . substr($canvas_id, -8),
        'class' => ['control-label'],
      ],
    ];

    // Build source options from extended sources (not just top 3)
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
        'id' => $canvas_id === 'news-source-timeline-chart' ? 'source-selector' : 'source-selector-' . substr($canvas_id, -8),
        'class' => ['form-select', 'source-selector'],
        'data-chart-id' => $canvas_id,
        'multiple' => 'multiple', // Force native multi-select attribute
        'size' => min(count($source_options), 8), // Ensure visible height for multiselect
      ],
    ];

    $controls['controls_container']['help_text'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '💡 Each source shows 3 trend lines: Credibility Score, Bias Rating, and Sentiment Score. Colors: CNN=Blue, Fox=Red, others assigned Red/Blue/Green by position. Use Ctrl+Click to select multiple sources.',
      '#attributes' => ['class' => ['help-text', 'text-muted', 'small']],
    ];

    return $controls;
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
        '#value' => '📊 No news source data available for the selected time period.',
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
    if ($options['js_behavior'] === 'newsSourceTimelineBlocks') {
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
