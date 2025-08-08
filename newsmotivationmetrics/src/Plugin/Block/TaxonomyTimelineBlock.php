<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Taxonomy Timeline Chart' Block.
 *
 * @Block(
 *   id = "taxonomy_timeline_chart",
 *   admin_label = @Translation("Taxonomy Timeline Chart"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class TaxonomyTimelineBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The chart data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface
   */
  protected $chartDataService;

  /**
   * Constructs a new TaxonomyTimelineBlock.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface $chart_data_service
   *   The chart data service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ChartDataServiceInterface $chart_data_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->chartDataService = $chart_data_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('newsmotivationmetrics.chart_data_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'chart_title' => $this->t('Topic Trends Over Time'),
      'days_back' => 30,
      'term_limit' => 10,
      'chart_height' => 400,
      'show_controls' => TRUE,
      'show_legend' => TRUE,
      'auto_refresh' => FALSE,
      'refresh_interval' => 300, // 5 minutes
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

    $form['chart_settings']['chart_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Chart Title'),
      '#default_value' => $config['chart_title'],
      '#description' => $this->t('The title displayed above the chart.'),
      '#required' => TRUE,
    ];

    $form['chart_settings']['chart_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Chart Height (pixels)'),
      '#default_value' => $config['chart_height'],
      '#min' => 200,
      '#max' => 800,
      '#description' => $this->t('Height of the chart in pixels.'),
    ];

    $form['data_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Data Settings'),
      '#open' => TRUE,
    ];

    $form['data_settings']['days_back'] = [
      '#type' => 'number',
      '#title' => $this->t('Days to Show'),
      '#default_value' => $config['days_back'],
      '#min' => 7,
      '#max' => 365,
      '#description' => $this->t('Number of days of data to display in the timeline.'),
    ];

    $form['data_settings']['term_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Terms'),
      '#default_value' => $config['term_limit'],
      '#min' => 5,
      '#max' => 50,
      '#description' => $this->t('Maximum number of taxonomy terms to include in the chart.'),
    ];

    $form['display_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Options'),
      '#open' => FALSE,
    ];

    $form['display_settings']['show_controls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Chart Controls'),
      '#default_value' => $config['show_controls'],
      '#description' => $this->t('Display controls for users to modify the chart (term selector, reset buttons).'),
    ];

    $form['display_settings']['show_legend'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Legend'),
      '#default_value' => $config['show_legend'],
      '#description' => $this->t('Display the chart legend showing term names and colors.'),
    ];

    $form['performance_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Performance Settings'),
      '#open' => FALSE,
    ];

    $form['performance_settings']['auto_refresh'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Refresh'),
      '#default_value' => $config['auto_refresh'],
      '#description' => $this->t('Automatically refresh chart data at regular intervals.'),
    ];

    $form['performance_settings']['refresh_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Refresh Interval (seconds)'),
      '#default_value' => $config['refresh_interval'],
      '#min' => 60,
      '#max' => 3600,
      '#description' => $this->t('How often to refresh the chart data (in seconds). Only applies if auto refresh is enabled.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[performance_settings][auto_refresh]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    
    $values = $form_state->getValues();
    
    // Save chart settings
    $this->configuration['chart_title'] = $values['chart_settings']['chart_title'];
    $this->configuration['chart_height'] = $values['chart_settings']['chart_height'];
    
    // Save data settings
    $this->configuration['days_back'] = $values['data_settings']['days_back'];
    $this->configuration['term_limit'] = $values['data_settings']['term_limit'];
    
    // Save display settings
    $this->configuration['show_controls'] = $values['display_settings']['show_controls'];
    $this->configuration['show_legend'] = $values['display_settings']['show_legend'];
    
    // Save performance settings
    $this->configuration['auto_refresh'] = $values['performance_settings']['auto_refresh'];
    $this->configuration['refresh_interval'] = $values['performance_settings']['refresh_interval'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    
    // Get chart data based on configuration
    $chart_data = $this->chartDataService->getTimelineChartData([
      'limit' => $config['term_limit'],
      'days_back' => $config['days_back'],
    ]);

    if (empty($chart_data['timeline_data'])) {
      return [
        '#markup' => '<div class="taxonomy-timeline-block-empty">' . $this->t('No timeline data available.') . '</div>',
        '#cache' => [
          'max-age' => 300, // Cache for 5 minutes
          'tags' => ['taxonomy_term_list', 'node_list'],
        ],
      ];
    }

    $block_id = 'taxonomy-timeline-block-' . $this->getPluginId();
    $canvas_id = 'taxonomy-timeline-chart-' . substr(md5($block_id), 0, 8);

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['taxonomy-timeline-block', 'taxonomy-timeline-section'],
        'id' => $block_id,
      ],
    ];

    // Chart title
    if (!empty($config['chart_title'])) {
      $build['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $config['chart_title'],
        '#attributes' => ['class' => ['chart-block-title']],
      ];
    }

    // Chart controls (if enabled)
    if ($config['show_controls']) {
      $build['controls'] = $this->buildChartControls($chart_data['top_terms'], $canvas_id);
    }

    // Chart container
    $build['chart_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-container', 'chart-block-container']],
    ];

    // Canvas element
    $build['chart_wrapper']['canvas'] = [
      '#type' => 'html_tag',
      '#tag' => 'canvas',
      '#attributes' => [
        'id' => $canvas_id,
        'width' => 800,
        'height' => min($config['chart_height'], 500), // Cap height at 500px
        'style' => 'max-width: 100%; height: auto; max-height: 500px;',
        'aria-label' => $config['chart_title'] . ' - Interactive Timeline Chart',
        'data-chart-config' => json_encode([
          'showLegend' => $config['show_legend'],
          'autoRefresh' => $config['auto_refresh'],
          'refreshInterval' => $config['refresh_interval'],
        ]),
      ],
    ];

    // Status and debug info
    $build['chart_wrapper']['status'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => 'Initializing chart...',
      '#attributes' => [
        'id' => 'chart-status-' . substr(md5($block_id), 0, 8),
        'class' => ['chart-status'],
      ],
    ];

    // Attach libraries and settings
    $build['#attached']['library'][] = 'newsmotivationmetrics/chart-blocks';
    $build['#attached']['drupalSettings']['newsmotivationmetrics']['blocks'][$canvas_id] = [
      'timelineData' => $chart_data['timeline_data'],
      'topTerms' => $chart_data['top_terms'],
      'debugInfo' => $chart_data['debug_info'],
      'config' => $config,
      'canvasId' => $canvas_id,
    ];

    // Cache settings
    $build['#cache'] = [
      'max-age' => $config['auto_refresh'] ? $config['refresh_interval'] : 1800, // 30 minutes default
      'tags' => ['taxonomy_term_list', 'node_list', 'newsmotivationmetrics_data'],
      'contexts' => ['user.permissions'],
    ];

    return $build;
  }

  /**
   * Build chart controls for the block.
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
      '#attributes' => ['class' => ['chart-controls', 'chart-block-controls']],
    ];

    $controls['selector_group'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['control-group']],
    ];

    $controls['selector_group']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => $this->t('Select Terms:'),
      '#attributes' => ['for' => 'term-selector-' . substr(md5($canvas_id), 0, 8)],
    ];

    $controls['selector_group']['selector'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $this->chartDataService->buildTermOptionsArray($top_terms),
      '#default_value' => array_slice(array_column($top_terms, 'tid'), 0, 10),
      '#attributes' => [
        'class' => ['term-selector'],
        'id' => 'term-selector-' . substr(md5($canvas_id), 0, 8),
        'size' => 6,
        'data-canvas-id' => $canvas_id,
      ],
    ];

    $controls['buttons'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['control-buttons']],
    ];

    $controls['buttons']['reset'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->t('Reset'),
      '#attributes' => [
        'id' => 'reset-chart-' . substr(md5($canvas_id), 0, 8),
        'class' => ['btn', 'btn-secondary', 'chart-reset-btn'],
        'type' => 'button',
        'data-canvas-id' => $canvas_id,
      ],
    ];

    $controls['buttons']['clear'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->t('Clear'),
      '#attributes' => [
        'id' => 'clear-chart-' . substr(md5($canvas_id), 0, 8),
        'class' => ['btn', 'btn-outline', 'chart-clear-btn'],
        'type' => 'button',
        'data-canvas-id' => $canvas_id,
      ],
    ];

    return $controls;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $config = $this->getConfiguration();
    return $config['auto_refresh'] ? $config['refresh_interval'] : 1800;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['taxonomy_term_list', 'node_list', 'newsmotivationmetrics_data'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

}
