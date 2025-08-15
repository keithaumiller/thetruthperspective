<?php

namespace Drupal\newsmotivationmetrics\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;
use Drupal\newsmotivationmetrics\Service\Interface\ActivityTimelineChartServiceInterface;

/**
 * Service for building activity timeline chart render arrays.
 * 
 * Centralizes activity timeline chart construction logic to eliminate duplication
 * between dashboard pages and block implementations. This service specifically handles
 * timeline charts that display article publication activity trends by news source.
 */
class ActivityTimelineChartService implements ActivityTimelineChartServiceInterface {

  use StringTranslationTrait;

  /**
   * The metrics data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface
   */
  protected $metricsDataService;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface $metrics_data_service
   *   The metrics data service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    MetricsDataServiceInterface $metrics_data_service,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->metricsDataService = $metrics_data_service;
    $this->logger = $logger_factory->get('newsmotivationmetrics');
  }

  /**
   * {@inheritdoc}
   */
  public function buildActivityTimelineChart(array $options = []): array {
    // Set default options
    $options = $options + [
      'canvas_id' => 'activity-timeline-chart',
      'title' => 'Recent Activity Timeline',
      'show_controls' => TRUE,
      'show_legend' => TRUE,
      'show_title' => TRUE,
      'chart_height' => 400,
      'days_back' => 90,
      'source_limit' => 6,
      'show_unpublished' => TRUE,
      'container_classes' => ['timeline-chart-container', 'activity-timeline'],
      'library' => 'newsmotivationmetrics/activity-timeline-chart',
      'js_behavior' => 'activityTimelineChart',
    ];

    // Get chart data
    $daily_articles = $this->metricsDataService->getDailyArticlesBySource();
    
    if (empty($daily_articles)) {
      return $this->buildEmptyChart($options);
    }

    // Prepare chart data
    $chart_data = $this->prepareChartData($daily_articles, $options);
    
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
          '#value' => '📈 ' . $options['title'] . ' (Last ' . $options['days_back'] . ' Days)',
          '#attributes' => ['class' => ['chart-section-title']],
        ],
        'description' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => 'This chart shows article publication activity for the top news sources over the past ' . $options['days_back'] . ' days. ' . ($options['show_unpublished'] ? 'Solid lines = Published articles, Dashed lines = Articles being processed.' : 'Only published articles are shown.'),
          '#attributes' => ['class' => ['chart-description']],
        ],
      ];
    }

    // Chart controls (if enabled)
    if ($options['show_controls']) {
      $build['controls'] = $this->buildChartControls($chart_data, $options['canvas_id']);
    }

    // Chart container and canvas
    $build['chart_container'] = $this->buildChartContainer($options, $chart_data);

    // Chart actions (reset/clear buttons)
    $build['actions'] = $this->buildChartActions($options['canvas_id'], $options['source_limit']);

    // Attach JavaScript libraries and settings
    $build['#attached']['library'][] = $options['library'];
    $build['#attached']['drupalSettings']['newsmotivationmetrics_activity'][$options['canvas_id']] = $chart_data;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildActivityTimelineBlock(array $config = []): array {
    // Convert block config to chart options
    $options = [
      'canvas_id' => 'activity-timeline-chart-' . substr(md5(serialize($config)), 0, 8),
      'title' => $config['chart_title'] ?? 'Recent Activity Timeline',
      'show_controls' => $config['show_controls'] ?? TRUE,
      'show_legend' => $config['show_legend'] ?? TRUE,
      'show_title' => !empty($config['chart_title']),
      'chart_height' => min($config['chart_height'] ?? 400, 500),
      'days_back' => $config['days_back'] ?? 90,
      'source_limit' => $config['source_limit'] ?? 6,
      'show_unpublished' => $config['show_unpublished'] ?? TRUE,
      'container_classes' => ['timeline-chart-container', 'activity-timeline', 'activity-timeline-block'],
      'library' => 'newsmotivationmetrics/activity-timeline-chart',
      'js_behavior' => 'activityTimelineChart',
      'auto_refresh' => $config['auto_refresh'] ?? FALSE,
      'refresh_interval' => $config['refresh_interval'] ?? 300,
    ];

    return $this->buildActivityTimelineChart($options);
  }

  /**
   * Build chart controls section.
   *
   * @param array $chart_data
   *   Chart data including source options.
   * @param string $canvas_id
   *   The canvas element ID.
   *
   * @return array
   *   Render array for chart controls.
   */
  protected function buildChartControls(array $chart_data, string $canvas_id): array {
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
      '#value' => t('Select News Sources (showing top ' . count($chart_data['source_options']) . ' by article count):'),
      '#attributes' => [
        'for' => 'activity-source-selector-' . substr($canvas_id, -8),
        'class' => ['control-label'],
      ],
    ];

    $controls['controls_container']['selector_group']['selector'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter Sources'),
      '#title_display' => 'invisible',
      '#multiple' => TRUE,
      '#size' => min(count($chart_data['source_options']), 8),
      '#options' => $chart_data['source_options'],
      '#attributes' => [
        'id' => 'activity-source-selector-' . substr($canvas_id, -8),
        'class' => ['form-select', 'source-selector'],
        'data-chart-target' => $canvas_id,
        'multiple' => 'multiple',
        'size' => min(count($chart_data['source_options']), 8),
      ],
      '#default_value' => array_keys($chart_data['source_options']),
    ];

    $controls['controls_container']['help_text'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '💡 Each source shows article publication activity over time. Solid lines show published articles, dashed lines show articles currently being processed. Use Ctrl+Click to select multiple sources.',
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
          'class' => ['activity-timeline-chart'],
          'width' => 800,
          'height' => $options['chart_height'],
          'data-chart-type' => 'activity-timeline',
          'aria-label' => 'Activity Timeline Chart',
          'style' => 'max-width: 100%; height: auto; max-height: 500px;',
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
                     '<p>' . t('No recent activity data available for chart display.') . '</p>' .
                     '</div>',
      ],
      '#cache' => [
        'max-age' => 300,
        'tags' => ['newsmotivationmetrics:activity_timeline', 'newsmotivationmetrics:metrics'],
      ],
    ];
  }

  /**
   * Prepare chart data for JavaScript consumption.
   */
  private function prepareChartData(array $daily_articles, array $config): array {
    $timeline_data = [];
    $all_sources = [];
    $date_labels = [];
    
    // Sort by date ascending for timeline
    uksort($daily_articles, function($a, $b) {
      return strtotime($a) - strtotime($b);
    });
    
    // Limit to configured days
    $daily_articles = array_slice($daily_articles, -$config['days_back'], $config['days_back'], true);
    
    // Collect all sources and dates
    foreach ($daily_articles as $date => $day_data) {
      $date_labels[] = $date;
      foreach ($day_data['sources'] as $source => $counts) {
        if (!in_array($source, $all_sources)) {
          $all_sources[] = $source;
        }
      }
    }
    
    // Sort sources by total article count
    $source_totals = [];
    foreach ($all_sources as $source) {
      $total = 0;
      foreach ($daily_articles as $day_data) {
        if (isset($day_data['sources'][$source])) {
          $total += $day_data['sources'][$source]['total'];
        }
      }
      $source_totals[$source] = $total;
    }
    arsort($source_totals);
    
    // Limit to top sources
    $top_sources = array_slice(array_keys($source_totals), 0, $config['source_limit']);
    
    // Create datasets for each source
    $datasets = [];
    $colors = [
      '#DC2626', '#2563EB', '#16A34A', '#CA8A04', '#7C3AED', '#DC2626',
      '#0891B2', '#BE185D', '#9A3412', '#4338CA', '#059669', '#D97706'
    ];
    
    foreach ($top_sources as $index => $source) {
      $published_data = [];
      $unpublished_data = [];
      
      foreach ($date_labels as $date) {
        $day_data = $daily_articles[$date] ?? ['sources' => []];
        $source_data = $day_data['sources'][$source] ?? ['published' => 0, 'unpublished' => 0];
        
        $published_data[] = [
          'x' => $date,
          'y' => $source_data['published']
        ];
        
        if ($config['show_unpublished']) {
          $unpublished_data[] = [
            'x' => $date,
            'y' => $source_data['unpublished']
          ];
        }
      }
      
      $base_color = $colors[$index % count($colors)];
      
      // Published articles dataset
      $datasets[] = [
        'label' => $source . ' (Published)',
        'data' => $published_data,
        'borderColor' => $base_color,
        'backgroundColor' => $base_color . '20',
        'borderWidth' => 2,
        'fill' => false,
        'tension' => 0.1,
        'pointRadius' => 3,
        'pointHoverRadius' => 5,
      ];
      
      // Unpublished articles dataset (if enabled)
      if ($config['show_unpublished'] && !empty(array_filter($unpublished_data, fn($point) => $point['y'] > 0))) {
        $datasets[] = [
          'label' => $source . ' (Processing)',
          'data' => $unpublished_data,
          'borderColor' => $base_color,
          'backgroundColor' => $base_color . '10',
          'borderWidth' => 1,
          'borderDash' => [5, 5],
          'fill' => false,
          'tension' => 0.1,
          'pointRadius' => 2,
          'pointHoverRadius' => 4,
        ];
      }
    }
    
    // Create source selector options
    $source_options = [];
    foreach ($top_sources as $source) {
      $source_options[$source] = $source . ' (' . $source_totals[$source] . ' articles)';
    }
    
    return [
      'timelineData' => $datasets,
      'labels' => $date_labels,
      'source_options' => $source_options,
      'topSources' => $top_sources,
      'config' => $config,
      'debugInfo' => [
        'dataPoints' => count($date_labels),
        'sourceCount' => count($top_sources),
        'timestamp' => time(),
        'date' => date('Y-m-d H:i:s'),
        'show_unpublished' => $config['show_unpublished'],
      ],
    ];
  }

}
