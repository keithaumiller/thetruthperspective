<?php

namespace Drupal\newsmotivationmetrics\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface;
use Drupal\newsmotivationmetrics\Service\Interface\NewsMotivationTimelineChartServiceInterface;

/**
 * Service for building news motivation timeline chart render arrays.
 * 
 * Centralizes news motivation timeline chart construction logic to eliminate duplication
 * between dashboard pages and block implementations. This service specifically handles
 * timeline charts that display motivation analysis trends over time for news articles.
 */
class NewsMotivationTimelineChartService implements NewsMotivationTimelineChartServiceInterface {

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
  public function buildNewsMotivationTimelineChart(array $options = []): array {
    // Set default options
    $options = $options + [
      'canvas_id' => 'news-motivation-timeline-chart',
      'title' => 'Motivation Trends Over Time',
      'show_controls' => TRUE,
      'show_legend' => TRUE,
      'show_title' => TRUE,
      'chart_height' => 400,
      'days_back' => 30,
      'term_limit' => 10,
      'container_classes' => ['timeline-chart-container', 'motivation-timeline'],
      'library' => 'newsmotivationmetrics/news-motivation-timeline',
      'js_behavior' => 'newsMotivationTimelineChart',
    ];

    // Get chart data
    $chart_data = $this->chartDataService->getTimelineChartData([
      'limit' => $options['term_limit'],
      'days_back' => $options['days_back'],
    ]);

    // Get extended terms for selector (top 50 terms)
    $extended_terms = $this->chartDataService->getTimelineChartData([
      'limit' => 50,
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
      $build['header'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['chart-header']],
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => '🎯 ' . $options['title'] . ' (Last ' . $options['days_back'] . ' Days)',
          '#attributes' => ['class' => ['chart-section-title']],
        ],
        'description' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => 'This chart displays the frequency trends of motivation-related terms and entities detected in news articles over the past ' . $options['days_back'] . ' days. Each line represents how often a particular motivation or key entity appears in analyzed content.',
          '#attributes' => ['class' => ['chart-description']],
        ],
      ];
    }

    // Chart controls (if enabled)
    if ($options['show_controls']) {
      $build['controls'] = $this->buildChartControls(
        $extended_terms['top_terms'], // Use extended terms for selector
        $options['canvas_id']
      );
    }

    // Chart container and canvas
    $build['chart_container'] = $this->buildChartContainer($options, $chart_data);

    // Action buttons removed for simplified interface

    // Attach JavaScript libraries and settings
    $build['#attached']['library'][] = $options['library'];
    $build['#attached']['drupalSettings']['newsmotivationmetrics'] = $this->buildJavaScriptSettings(
      $chart_data, 
      $options,
      $extended_terms // Pass extended terms for background loading
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildNewsMotivationTimelineBlock(array $config = []): array {
    // Convert block config to chart options
    $options = [
      'canvas_id' => 'news-motivation-timeline-chart-' . substr(md5(serialize($config)), 0, 8),
      'title' => $config['chart_title'] ?? 'Motivation Trends Over Time',
      'show_controls' => $config['show_controls'] ?? TRUE,
      'show_legend' => $config['show_legend'] ?? TRUE,
      'show_title' => !empty($config['chart_title']),
      'chart_height' => min($config['chart_height'] ?? 400, 500),
      'days_back' => $config['days_back'] ?? 30,
      'term_limit' => $config['term_limit'] ?? 10,
      'container_classes' => ['timeline-chart-container', 'motivation-timeline'],
      'library' => 'newsmotivationmetrics/news-motivation-timeline',
      'js_behavior' => 'newsMotivationTimelineChart',
      'auto_refresh' => $config['auto_refresh'] ?? FALSE,
      'refresh_interval' => $config['refresh_interval'] ?? 300,
    ];

    return $this->buildNewsMotivationTimelineChart($options);
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
      '#value' => t('Select Terms (max 10):'),
      '#attributes' => ['for' => $canvas_id === 'news-motivation-timeline-chart' ? 'term-selector' : 'term-selector-' . $this->getUniqueId($canvas_id)],
    ];

    $controls['controls_container']['selector_group']['selector'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $this->chartDataService->buildTermOptionsArray($top_terms),
      '#default_value' => array_slice(array_column($top_terms, 'tid'), 0, 10),
      '#attributes' => [
        'class' => ['term-selector'],
        'id' => $canvas_id === 'news-motivation-timeline-chart' ? 'term-selector' : 'term-selector-' . $this->getUniqueId($canvas_id),
        'size' => 12, // Increased size to show more options
        'data-canvas-id' => $canvas_id,
        'data-max-selections' => 10,
        'multiple' => 'multiple', // Force native multi-select attribute
      ],
    ];

    $controls['controls_container']['info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-info']],
    ];

    $controls['controls_container']['info']['text'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => t('📊 Select up to 10 terms to display. Top 10 terms shown by default.'),
      '#attributes' => ['class' => ['info-text']],
    ];

    $controls['controls_container']['info']['loading'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => t('Loading additional terms in background...'),
      '#attributes' => [
        'class' => ['loading-text'],
        'id' => 'loading-status-' . $this->getUniqueId($canvas_id),
        'style' => 'display: none; color: #666; font-style: italic;',
      ],
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

    return $container;
  }

  /**
   * Build JavaScript settings for chart initialization.
   *
   * @param array $chart_data
   *   Chart data from service.
   * @param array $options
   *   Chart options.
   * @param array $extended_terms
   *   Extended term data for background loading.
   *
   * @return array
   *   JavaScript settings array.
   */
  protected function buildJavaScriptSettings(array $chart_data, array $options, array $extended_terms = null): array {
    $settings = [
      'timelineData' => $chart_data['timeline_data'],
      'topTerms' => $chart_data['top_terms'],
      'debugInfo' => $chart_data['debug_info'],
    ];

    // Add extended terms for background loading if available
    if ($extended_terms && !empty($extended_terms['timeline_data'])) {
      $settings['extendedTerms'] = [
        'timelineData' => $extended_terms['timeline_data'],
        'topTerms' => $extended_terms['top_terms'],
      ];
    }

    // For block-based charts, organize by canvas ID
    if ($options['js_behavior'] === 'newsMotivationTimelineBlocks') {
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
