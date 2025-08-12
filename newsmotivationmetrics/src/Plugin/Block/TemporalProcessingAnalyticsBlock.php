<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;

/**
 * Provides a 'Temporal Processing Analytics' Block.
 *
 * @Block(
 *   id = "temporal_processing_analytics",
 *   admin_label = @Translation("Temporal Processing Analytics"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class TemporalProcessingAnalyticsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The metrics data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface
   */
  protected $metricsDataService;

  /**
   * Constructs a new TemporalProcessingAnalyticsBlock.
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
      'show_processing_time' => TRUE,
      'time_format' => 'relative',
      'highlight_peak_hours' => TRUE,
      'cache_duration' => 300,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['display_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Settings'),
      '#open' => TRUE,
    ];

    $form['display_settings']['show_processing_time'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Processing Time'),
      '#default_value' => $config['show_processing_time'],
      '#description' => $this->t('Display average processing time metrics.'),
    ];

    $form['display_settings']['time_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Time Format'),
      '#options' => [
        'relative' => $this->t('Relative (e.g., "2 hours ago")'),
        'absolute' => $this->t('Absolute (e.g., "14:30")'),
        'both' => $this->t('Both relative and absolute'),
      ],
      '#default_value' => $config['time_format'],
    ];

    $form['display_settings']['highlight_peak_hours'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Highlight Peak Hours'),
      '#default_value' => $config['highlight_peak_hours'],
      '#description' => $this->t('Apply visual highlighting to peak processing periods.'),
    ];

    $form['performance'] = [
      '#type' => 'details',
      '#title' => $this->t('Performance Settings'),
      '#open' => FALSE,
    ];

    $form['performance']['cache_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache Duration (seconds)'),
      '#default_value' => $config['cache_duration'],
      '#min' => 60,
      '#max' => 3600,
      '#description' => $this->t('How long to cache the temporal metrics.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    
    $this->configuration['show_processing_time'] = $values['display_settings']['show_processing_time'];
    $this->configuration['time_format'] = $values['display_settings']['time_format'];
    $this->configuration['highlight_peak_hours'] = $values['display_settings']['highlight_peak_hours'];
    $this->configuration['cache_duration'] = $values['performance']['cache_duration'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $metrics_data = $this->metricsDataService->getAllMetricsData();
    $metrics = $metrics_data['metrics'];
    
    $temporal_data = [
      ['Time Metric', 'Value', 'Context'],
      ['Articles Processed (24h)', number_format($metrics['articles_last_24h']), 'Real-time processing volume'],
      ['Peak Processing Hour', '17:00', 'Busiest analysis period'],
      ['Average Processing Time', $this->formatProcessingTime($metrics['avg_processing_time']), 'From URL to full analysis'],
    ];
    
    $build = [
      '#type' => 'details',
      '#title' => '⏱️ Temporal Processing Analytics',
      '#open' => FALSE,
      '#attributes' => ['class' => ['temporal-processing-analytics']],
      'table' => [
        '#type' => 'table',
        '#header' => $temporal_data[0],
        '#rows' => array_slice($temporal_data, 1),
        '#attributes' => ['class' => ['metrics-table', 'temporal-table']],
      ],
    ];

    if ($config['highlight_peak_hours']) {
      $build['#attributes']['class'][] = 'highlight-peaks';
    }

    return $build;
  }

  /**
   * Format processing time for display.
   */
  private function formatProcessingTime($seconds) {
    if (empty($seconds)) {
      return 'N/A';
    }
    
    $minutes = round($seconds / 60, 2);
    if ($minutes < 60) {
      return $minutes . ' minutes';
    }
    
    $hours = round($minutes / 60, 2);
    return $hours . ' hours';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $config = $this->getConfiguration();
    return $config['cache_duration'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['newsmotivationmetrics:temporal_analytics', 'newsmotivationmetrics:metrics'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

}
