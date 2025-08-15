<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;

/**
 * Provides a 'Recent Activity Timeline Chart' Block.
 *
 * @Block(
 *   id = "recent_activity_timeline_chart",
 *   admin_label = @Translation("Recent Activity Timeline Chart"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class RecentActivityTimelineChartBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The metrics data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface
   */
  protected $metricsDataService;

  /**
   * Constructs a new RecentActivityTimelineChartBlock.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MetricsDataServiceInterface $metrics_data_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->metricsDataService = $metrics_data_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('newsmotivationmetrics.metrics_data_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'days_to_show' => 30,
      'show_unpublished' => TRUE,
      'top_sources_limit' => 6,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['chart_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Chart Settings'),
      '#open' => TRUE,
    ];

    $form['chart_settings']['days_to_show'] = [
      '#type' => 'number',
      '#title' => $this->t('Days to Show'),
      '#default_value' => $config['days_to_show'],
      '#min' => 7,
      '#max' => 60,
    ];

    $form['chart_settings']['show_unpublished'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Unpublished Articles'),
      '#default_value' => $config['show_unpublished'],
      '#description' => $this->t('Include articles still being processed in the chart.'),
    ];

    $form['chart_settings']['top_sources_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Sources to Show'),
      '#default_value' => $config['top_sources_limit'],
      '#min' => 3,
      '#max' => 15,
      '#description' => $this->t('Limit chart to top N sources by article count.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    
    $this->configuration['days_to_show'] = $values['chart_settings']['days_to_show'];
    $this->configuration['show_unpublished'] = $values['chart_settings']['show_unpublished'];
    $this->configuration['top_sources_limit'] = $values['chart_settings']['top_sources_limit'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    
    // Get the daily article data
    $daily_articles = $this->metricsDataService->getDailyArticlesBySource();
    
    if (empty($daily_articles)) {
      return [
        '#type' => 'container',
        '#attributes' => ['class' => ['recent-activity-chart-empty']],
        'message' => [
          '#markup' => '<p>No recent activity data available for chart display.</p>',
        ],
      ];
    }

    // Prepare chart data
    $chart_data = $this->prepareChartData($daily_articles, $config);
    
    // Generate unique chart ID
    $chart_id = 'recent-activity-timeline-chart-' . substr(md5(microtime()), 0, 8);
    
    $build = [
      '#theme' => 'recent_activity_timeline_chart',
      '#chart_data' => $chart_data,
      '#chart_id' => $chart_id,
      '#config' => $config,
      '#content' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['recent-activity-timeline-chart-container']],
      ],
    ];

    // Chart title and controls
    $build['#content']['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-header']],
      'title' => [
        '#markup' => '<h3>📈 Recent Activity Timeline (Last ' . $config['days_to_show'] . ' Days)</h3>',
      ],
      'controls' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['chart-controls']],
        'source_selector' => [
          '#type' => 'select',
          '#title' => $this->t('Filter Sources'),
          '#options' => $chart_data['source_options'],
          '#multiple' => TRUE,
          '#attributes' => [
            'id' => 'activity-source-selector',
            'class' => ['source-selector'],
            'data-chart-target' => $chart_id,
          ],
          '#default_value' => array_keys($chart_data['source_options']),
        ],
      ],
    ];

    // Chart canvas
    $build['#content']['chart'] = [
      '#type' => 'html_tag',
      '#tag' => 'canvas',
      '#attributes' => [
        'id' => $chart_id,
        'class' => ['recent-activity-timeline-chart'],
        'width' => 800,
        'height' => 400,
        'data-chart-type' => 'activity-timeline',
        'aria-label' => 'Recent Activity Timeline Chart',
      ],
    ];

    // Add Chart.js data to drupalSettings
    $build['#attached']['drupalSettings']['newsmotivationmetrics_activity'][$chart_id] = $chart_data;
    
    // Attach JavaScript and CSS
    $build['#attached']['library'][] = 'newsmotivationmetrics/chart-style';
    $build['#attached']['library'][] = 'newsmotivationmetrics/activity-timeline-chart';

    return $build;
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
    $daily_articles = array_slice($daily_articles, -$config['days_to_show'], $config['days_to_show'], true);
    
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
    $top_sources = array_slice(array_keys($source_totals), 0, $config['top_sources_limit']);
    
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

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 300; // Cache for 5 minutes
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['newsmotivationmetrics:activity_timeline', 'newsmotivationmetrics:metrics'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

}
