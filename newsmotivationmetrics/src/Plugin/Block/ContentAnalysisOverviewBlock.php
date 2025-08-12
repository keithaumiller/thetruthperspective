<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;

/**
 * Provides a 'Content Analysis Overview' Block.
 *
 * @Block(
 *   id = "content_analysis_overview",
 *   admin_label = @Translation("Content Analysis Overview"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class ContentAnalysisOverviewBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The metrics data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface
   */
  protected $metricsDataService;

  /**
   * Constructs a new ContentAnalysisOverviewBlock.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface $metrics_data_service
   *   The metrics data service.
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
      'show_coverage_percentages' => TRUE,
      'highlight_high_coverage' => TRUE,
      'auto_refresh' => TRUE,
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

    $form['display_settings']['show_coverage_percentages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Coverage Percentages'),
      '#default_value' => $config['show_coverage_percentages'],
      '#description' => $this->t('Display percentage coverage for each metric.'),
    ];

    $form['display_settings']['highlight_high_coverage'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Highlight High Coverage'),
      '#default_value' => $config['highlight_high_coverage'],
      '#description' => $this->t('Apply visual highlighting to metrics with high coverage rates.'),
    ];

    $form['performance'] = [
      '#type' => 'details',
      '#title' => $this->t('Performance Settings'),
      '#open' => FALSE,
    ];

    $form['performance']['auto_refresh'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Refresh Data'),
      '#default_value' => $config['auto_refresh'],
      '#description' => $this->t('Automatically refresh metrics data.'),
    ];

    $form['performance']['cache_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache Duration (seconds)'),
      '#default_value' => $config['cache_duration'],
      '#min' => 60,
      '#max' => 3600,
      '#description' => $this->t('How long to cache the metrics data.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    
    $this->configuration['show_coverage_percentages'] = $values['display_settings']['show_coverage_percentages'];
    $this->configuration['highlight_high_coverage'] = $values['display_settings']['highlight_high_coverage'];
    $this->configuration['auto_refresh'] = $values['performance']['auto_refresh'];
    $this->configuration['cache_duration'] = $values['performance']['cache_duration'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $metrics_data = $this->metricsDataService->getAllMetricsData();
    $metrics = $metrics_data['metrics'];
    
    $overview_data = [
      ['Metric', 'Count', 'Coverage'],
      ['Total Articles Analyzed', number_format($metrics['total_articles']), '100%'],
      ['AI Analysis Complete', number_format($metrics['articles_with_ai']), round(($metrics['articles_with_ai'] / max($metrics['total_articles'], 1)) * 100, 1) . '%'],
      ['Content Extraction Success', number_format($metrics['articles_with_json']), round(($metrics['articles_with_json'] / max($metrics['total_articles'], 1)) * 100, 1) . '%'],
      ['Taxonomy Classification', number_format($metrics['articles_with_tags']), round(($metrics['articles_with_tags'] / max($metrics['total_articles'], 1)) * 100, 1) . '%'],
      ['Motivation Analysis', number_format($metrics['articles_with_motivation']), round(($metrics['articles_with_motivation'] / max($metrics['total_articles'], 1)) * 100, 1) . '%'],
      ['Media Assets Captured', number_format($metrics['articles_with_images']), round(($metrics['articles_with_images'] / max($metrics['total_articles'], 1)) * 100, 1) . '%'],
    ];
    
    $build = [
      '#type' => 'details',
      '#title' => '📊 Content Analysis Overview',
      '#open' => FALSE,
      '#attributes' => ['class' => ['content-analysis-overview']],
      'table' => [
        '#type' => 'table',
        '#header' => $overview_data[0],
        '#rows' => array_slice($overview_data, 1),
        '#attributes' => ['class' => ['metrics-table']],
      ],
    ];

    // Add CSS classes based on configuration
    if ($config['highlight_high_coverage']) {
      $build['#attributes']['class'][] = 'highlight-coverage';
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
    return ['newsmotivationmetrics:content_analysis', 'newsmotivationmetrics:metrics'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

}
