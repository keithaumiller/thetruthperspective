<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;

/**
 * Provides an 'Analysis Quality Metrics' Block.
 *
 * @Block(
 *   id = "analysis_quality_metrics",
 *   admin_label = @Translation("Analysis Quality Metrics"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class AnalysisQualityMetricsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The metrics data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface
   */
  protected $metricsDataService;

  /**
   * Constructs a new AnalysisQualityMetricsBlock.
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
      'show_quality_indicators' => TRUE,
      'highlight_high_quality' => TRUE,
      'show_character_counts' => TRUE,
      'quality_threshold' => 2000,
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

    $form['display_settings']['show_quality_indicators'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Quality Indicators'),
      '#default_value' => $config['show_quality_indicators'],
      '#description' => $this->t('Display quality indicators for analysis depth.'),
    ];

    $form['display_settings']['highlight_high_quality'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Highlight High Quality'),
      '#default_value' => $config['highlight_high_quality'],
      '#description' => $this->t('Apply visual highlighting to high-quality analysis metrics.'),
    ];

    $form['display_settings']['show_character_counts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Character Counts'),
      '#default_value' => $config['show_character_counts'],
      '#description' => $this->t('Display character counts for AI responses.'),
    ];

    $form['display_settings']['quality_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('High Quality Threshold (characters)'),
      '#default_value' => $config['quality_threshold'],
      '#min' => 500,
      '#description' => $this->t('Character count threshold for highlighting high-quality analysis.'),
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
      '#description' => $this->t('How long to cache the quality metrics.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    
    $this->configuration['show_quality_indicators'] = $values['display_settings']['show_quality_indicators'];
    $this->configuration['highlight_high_quality'] = $values['display_settings']['highlight_high_quality'];
    $this->configuration['show_character_counts'] = $values['display_settings']['show_character_counts'];
    $this->configuration['quality_threshold'] = $values['display_settings']['quality_threshold'];
    $this->configuration['cache_duration'] = $values['performance']['cache_duration'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $metrics_data = $this->metricsDataService->getAllMetricsData();
    $metrics = $metrics_data['metrics'];
    
    $quality_data = [
      'AI Response Depth' => [
        'value' => '2,080 characters',
        'raw_value' => 2080,
        'class' => 'ai-response-depth'
      ],
      'Motivation Analysis Detail' => [
        'value' => '2,813 characters',
        'raw_value' => 2813,
        'class' => 'motivation-analysis-detail'
      ],
      'Classification Density' => [
        'value' => '18.3 tags per article',
        'raw_value' => 18.3,
        'class' => 'classification-density'
      ],
    ];

    $header = ['Quality Indicator', 'Average Value'];
    if ($config['show_quality_indicators']) {
      $header[] = 'Quality';
    }

    $rows = [];
    foreach ($quality_data as $indicator => $data) {
      $row = [$indicator, $data['value']];
      
      if ($config['show_quality_indicators']) {
        if (str_contains($data['value'], 'characters')) {
          $quality = $data['raw_value'] >= $config['quality_threshold'] ? '✅ High' : '⚠️ Standard';
        } else {
          $quality = '📊 Good';
        }
        $row[] = $quality;
      }
      
      $row_attributes = ['class' => [$data['class']]];
      
      // Highlight high quality
      if ($config['highlight_high_quality']) {
        if (str_contains($data['value'], 'characters') && $data['raw_value'] >= $config['quality_threshold']) {
          $row_attributes['class'][] = 'high-quality';
        } elseif (str_contains($data['value'], 'tags') && $data['raw_value'] >= 15) {
          $row_attributes['class'][] = 'high-quality';
        }
      }
      
      $rows[] = [
        'data' => $row,
        '#attributes' => $row_attributes,
      ];
    }
    
    $build = [
      '#type' => 'details',
      '#title' => '🔍 Analysis Quality Metrics',
      '#open' => FALSE,
      '#attributes' => ['class' => ['analysis-quality-metrics']],
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => ['class' => ['metrics-table', 'quality-table']],
      ],
    ];

    if ($config['highlight_high_quality']) {
      $build['#attributes']['class'][] = 'highlight-quality';
    }

    return $build;
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
    return ['newsmotivationmetrics:analysis_quality', 'newsmotivationmetrics:metrics'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

}
