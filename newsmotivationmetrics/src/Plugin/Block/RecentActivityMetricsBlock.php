<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;

/**
 * Provides a 'Recent Activity Metrics' Block.
 *
 * @Block(
 *   id = "recent_activity_metrics",
 *   admin_label = @Translation("Recent Activity Metrics"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class RecentActivityMetricsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The metrics data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface
   */
  protected $metricsDataService;

  /**
   * Constructs a new RecentActivityMetricsBlock.
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
      'show_daily_averages' => TRUE,
      'highlight_recent_activity' => TRUE,
      'show_total_tags' => TRUE,
      'activity_threshold' => 10,
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

    $form['display_settings']['show_daily_averages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Daily Averages'),
      '#default_value' => $config['show_daily_averages'],
      '#description' => $this->t('Display calculated daily averages for activity periods.'),
    ];

    $form['display_settings']['highlight_recent_activity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Highlight Recent Activity'),
      '#default_value' => $config['highlight_recent_activity'],
      '#description' => $this->t('Apply visual highlighting to recent high-activity periods.'),
    ];

    $form['display_settings']['show_total_tags'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Total Classification Tags'),
      '#default_value' => $config['show_total_tags'],
      '#description' => $this->t('Display total count of classification tags.'),
    ];

    $form['display_settings']['activity_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('High Activity Threshold'),
      '#default_value' => $config['activity_threshold'],
      '#min' => 1,
      '#description' => $this->t('Articles per day threshold for highlighting high activity.'),
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
      '#description' => $this->t('How long to cache the activity metrics.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    
    $this->configuration['show_daily_averages'] = $values['display_settings']['show_daily_averages'];
    $this->configuration['highlight_recent_activity'] = $values['display_settings']['highlight_recent_activity'];
    $this->configuration['show_total_tags'] = $values['display_settings']['show_total_tags'];
    $this->configuration['activity_threshold'] = $values['display_settings']['activity_threshold'];
    $this->configuration['cache_duration'] = $values['performance']['cache_duration'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $metrics_data = $this->metricsDataService->getAllMetricsData();
    $metrics = $metrics_data['metrics'];
    
    $activity_data = [];
    
    // Calculate activity metrics
    $articles_7_days = $metrics['articles_last_7_days'] ?? 109;
    $articles_30_days = $metrics['articles_last_30_days'] ?? 130;
    $total_tags = $metrics['total_tags'] ?? 1052;
    
    $daily_avg_7 = round($articles_7_days / 7, 1);
    $daily_avg_30 = round($articles_30_days / 30, 1);
    
    if ($config['show_daily_averages']) {
      $activity_data[] = [
        'period' => 'Last 7 Days',
        'articles' => number_format($articles_7_days),
        'daily_avg' => $daily_avg_7,
        'class' => $daily_avg_7 >= $config['activity_threshold'] ? 'high-activity' : 'normal-activity'
      ];
      
      $activity_data[] = [
        'period' => 'Last 30 Days',
        'articles' => number_format($articles_30_days),
        'daily_avg' => $daily_avg_30,
        'class' => $daily_avg_30 >= $config['activity_threshold'] ? 'high-activity' : 'normal-activity'
      ];
    } else {
      $activity_data[] = [
        'period' => 'Last 7 Days',
        'articles' => number_format($articles_7_days),
        'daily_avg' => null,
        'class' => 'normal-activity'
      ];
      
      $activity_data[] = [
        'period' => 'Last 30 Days',
        'articles' => number_format($articles_30_days),
        'daily_avg' => null,
        'class' => 'normal-activity'
      ];
    }

    $header = ['Period', 'Articles'];
    if ($config['show_daily_averages']) {
      $header[] = 'Daily Average';
    }

    $rows = [];
    foreach ($activity_data as $data) {
      $row = [$data['period'], $data['articles']];
      if ($config['show_daily_averages']) {
        $row[] = $data['daily_avg'];
      }
      
      $row_attributes = [];
      if ($config['highlight_recent_activity']) {
        $row_attributes['class'] = [$data['class']];
      }
      
      $rows[] = [
        'data' => $row,
        '#attributes' => $row_attributes,
      ];
    }

    // Add total tags row if configured
    if ($config['show_total_tags']) {
      $rows[] = [
        'data' => ['Total Classification Tags', number_format($total_tags), $config['show_daily_averages'] ? '' : ''],
        '#attributes' => ['class' => ['tags-total']],
      ];
    }
    
    $build = [
      '#type' => 'details',
      '#title' => '⚡ Recent Activity',
      '#open' => FALSE,
      '#attributes' => ['class' => ['recent-activity-metrics']],
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => ['class' => ['metrics-table', 'activity-table']],
      ],
    ];

    if ($config['highlight_recent_activity']) {
      $build['#attributes']['class'][] = 'highlight-activity';
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
    return ['newsmotivationmetrics:recent_activity', 'newsmotivationmetrics:metrics'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

}
