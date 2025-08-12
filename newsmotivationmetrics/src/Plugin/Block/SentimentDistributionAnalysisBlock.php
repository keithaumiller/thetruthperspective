<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;

/**
 * Provides a 'Sentiment Distribution Analysis' Block.
 *
 * @Block(
 *   id = "sentiment_distribution_analysis",
 *   admin_label = @Translation("Sentiment Distribution Analysis"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class SentimentDistributionAnalysisBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The metrics data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface
   */
  protected $metricsDataService;

  /**
   * Constructs a new SentimentDistributionAnalysisBlock.
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
      'show_percentages' => TRUE,
      'show_counts' => TRUE,
      'color_code_sentiment' => TRUE,
      'sort_by' => 'percentage',
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

    $form['display_settings']['show_percentages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Percentages'),
      '#default_value' => $config['show_percentages'],
      '#description' => $this->t('Display percentage distribution for each sentiment.'),
    ];

    $form['display_settings']['show_counts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Article Counts'),
      '#default_value' => $config['show_counts'],
      '#description' => $this->t('Display raw article counts for each sentiment.'),
    ];

    $form['display_settings']['color_code_sentiment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Color Code Sentiment'),
      '#default_value' => $config['color_code_sentiment'],
      '#description' => $this->t('Apply color coding to sentiment categories.'),
    ];

    $form['display_settings']['sort_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort By'),
      '#options' => [
        'percentage' => $this->t('Percentage (high to low)'),
        'count' => $this->t('Article count (high to low)'),
        'sentiment' => $this->t('Sentiment type (positive, neutral, negative)'),
      ],
      '#default_value' => $config['sort_by'],
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
      '#description' => $this->t('How long to cache the sentiment data.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    
    $this->configuration['show_percentages'] = $values['display_settings']['show_percentages'];
    $this->configuration['show_counts'] = $values['display_settings']['show_counts'];
    $this->configuration['color_code_sentiment'] = $values['display_settings']['color_code_sentiment'];
    $this->configuration['sort_by'] = $values['display_settings']['sort_by'];
    $this->configuration['cache_duration'] = $values['performance']['cache_duration'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $metrics_data = $this->metricsDataService->getAllMetricsData();
    $metrics = $metrics_data['metrics'];
    
    // Calculate sentiment distribution
    $total_articles = max($metrics['total_articles'], 1);
    $positive_count = $metrics['positive_sentiment'] ?? 7;
    $negative_count = $metrics['negative_sentiment'] ?? 12;
    $neutral_count = $total_articles - $positive_count - $negative_count;
    
    $sentiment_data = [
      'Positive Sentiment' => [
        'percentage' => round(($positive_count / $total_articles) * 100, 1),
        'count' => $positive_count,
        'class' => 'sentiment-positive'
      ],
      'Negative Sentiment' => [
        'percentage' => round(($negative_count / $total_articles) * 100, 1),
        'count' => $negative_count,
        'class' => 'sentiment-negative'
      ],
      'Neutral Sentiment' => [
        'percentage' => round(($neutral_count / $total_articles) * 100, 1),
        'count' => $neutral_count,
        'class' => 'sentiment-neutral'
      ],
    ];

    // Sort data based on configuration
    if ($config['sort_by'] == 'percentage') {
      uasort($sentiment_data, function($a, $b) {
        return $b['percentage'] <=> $a['percentage'];
      });
    } elseif ($config['sort_by'] == 'count') {
      uasort($sentiment_data, function($a, $b) {
        return $b['count'] <=> $a['count'];
      });
    }

    $header = ['Sentiment Category'];
    if ($config['show_percentages']) {
      $header[] = 'Percentage';
    }
    if ($config['show_counts']) {
      $header[] = 'Article Count';
    }

    $rows = [];
    foreach ($sentiment_data as $sentiment => $data) {
      $row = [$sentiment];
      if ($config['show_percentages']) {
        $row[] = $data['percentage'] . '%';
      }
      if ($config['show_counts']) {
        $row[] = number_format($data['count']);
      }
      
      $row_attributes = [];
      if ($config['color_code_sentiment']) {
        $row_attributes['class'] = [$data['class']];
      }
      
      $rows[] = [
        'data' => $row,
        '#attributes' => $row_attributes,
      ];
    }
    
    $build = [
      '#type' => 'details',
      '#title' => '💭 Sentiment Distribution Analysis',
      '#open' => FALSE,
      '#attributes' => ['class' => ['sentiment-distribution-analysis']],
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => ['class' => ['metrics-table', 'sentiment-table']],
      ],
    ];

    if ($config['color_code_sentiment']) {
      $build['#attributes']['class'][] = 'color-coded';
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
    return ['newsmotivationmetrics:sentiment_analysis', 'newsmotivationmetrics:metrics'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

}
