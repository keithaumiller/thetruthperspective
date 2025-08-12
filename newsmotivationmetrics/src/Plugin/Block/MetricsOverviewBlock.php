<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;

/**
 * Provides a 'Metrics Overview' Block.
 *
 * @Block(
 *   id = "metrics_overview_block",
 *   admin_label = @Translation("Metrics Overview Tables"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class MetricsOverviewBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The metrics data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface
   */
  protected $metricsDataService;

  /**
   * Constructs a new MetricsOverviewBlock instance.
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
  public function build() {
    $metrics_data = $this->metricsDataService->getAllMetricsData();
    
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['metrics-overview-sections']],
      'content_analysis' => $this->buildContentAnalysisSection($metrics_data),
      'temporal' => $this->buildTemporalSection($metrics_data),
      'sentiment' => $this->buildSentimentSection($metrics_data),
      'entities' => $this->buildEntitiesSection($metrics_data),
      'activity' => $this->buildActivitySection($metrics_data),
      'insights' => $this->buildInsightsSection($metrics_data),
      '#cache' => [
        'max-age' => 300, // Cache for 5 minutes
        'tags' => ['newsmotivationmetrics:metrics', 'newsmotivationmetrics:overview'],
      ],
    ];
  }

  /**
   * Build Content Analysis Overview section.
   */
  protected function buildContentAnalysisSection($metrics_data): array {
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
    
    return [
      '#type' => 'details',
      '#title' => '📊 Content Analysis Overview',
      '#open' => TRUE,
      '#attributes' => ['class' => ['metrics-overview']],
      'table' => [
        '#type' => 'table',
        '#header' => $overview_data[0],
        '#rows' => array_slice($overview_data, 1),
        '#attributes' => ['class' => ['metrics-table']],
      ],
    ];
  }

  /**
   * Build other metric sections with similar patterns.
   */
  protected function buildTemporalSection($metrics_data): array {
    $temporal_metrics = $metrics_data['temporal_metrics'];
    
    $temporal_data = [
      ['Time Metric', 'Value', 'Context'],
      ['Articles Processed (24h)', number_format($temporal_metrics['articles_last_24_hours']), 'Real-time processing volume'],
      ['Peak Processing Hour', $temporal_metrics['peak_processing_hour'], 'Busiest analysis period'],
      ['Average Processing Time', round($temporal_metrics['avg_processing_time'], 2) . ' minutes', 'From URL to full analysis'],
    ];
    
    return [
      '#type' => 'details',
      '#title' => '⏱️ Temporal Processing Analytics',
      '#open' => TRUE,
      'table' => [
        '#type' => 'table',
        '#header' => $temporal_data[0],
        '#rows' => array_slice($temporal_data, 1),
        '#attributes' => ['class' => ['temporal-table']],
      ],
    ];
  }

  /**
   * Build sentiment section.
   */
  protected function buildSentimentSection($metrics_data): array {
    $metrics = $metrics_data['metrics'];
    $sentiment_metrics = $metrics_data['sentiment_metrics'];
    
    $sentiment_data = [
      ['Sentiment Category', 'Percentage', 'Article Count'],
      ['Positive Sentiment', round($sentiment_metrics['positive_sentiment_percentage'], 1) . '%', number_format($metrics['total_articles'] * ($sentiment_metrics['positive_sentiment_percentage'] / 100))],
      ['Negative Sentiment', round($sentiment_metrics['negative_sentiment_percentage'], 1) . '%', number_format($metrics['total_articles'] * ($sentiment_metrics['negative_sentiment_percentage'] / 100))],
      ['Neutral Sentiment', round($sentiment_metrics['neutral_sentiment_percentage'], 1) . '%', number_format($metrics['total_articles'] * ($sentiment_metrics['neutral_sentiment_percentage'] / 100))],
    ];
    
    return [
      '#type' => 'details',
      '#title' => '💭 Sentiment Distribution Analysis',
      '#open' => TRUE,
      'table' => [
        '#type' => 'table',
        '#header' => $sentiment_data[0],
        '#rows' => array_slice($sentiment_data, 1),
        '#attributes' => ['class' => ['sentiment-table']],
      ],
    ];
  }

  /**
   * Build entities section.
   */
  protected function buildEntitiesSection($metrics_data): array {
    $entity_metrics = $metrics_data['entity_metrics'];
    
    $entity_data = [
      ['Entity Type', 'Unique Count'],
      ['People Identified', number_format($entity_metrics['unique_people_identified'])],
      ['Organizations Tracked', number_format($entity_metrics['unique_organizations_identified'])],
      ['Locations Mapped', number_format($entity_metrics['unique_locations_identified'])],
    ];
    
    return [
      '#type' => 'details',
      '#title' => '🏷️ Entity Recognition Metrics',
      '#open' => TRUE,
      'table' => [
        '#type' => 'table',
        '#header' => $entity_data[0],
        '#rows' => array_slice($entity_data, 1),
        '#attributes' => ['class' => ['entities-table']],
      ],
    ];
  }

  /**
   * Build activity section.
   */
  protected function buildActivitySection($metrics_data): array {
    $metrics = $metrics_data['metrics'];
    
    $activity_data = [
      ['Period', 'Articles', 'Daily Average'],
      ['Last 7 Days', number_format($metrics['articles_last_7_days']), round($metrics['articles_last_7_days'] / 7, 1)],
      ['Last 30 Days', number_format($metrics['articles_last_30_days']), round($metrics['articles_last_30_days'] / 30, 1)],
      ['Total Classification Tags', number_format($metrics['total_tags']), ''],
    ];
    
    return [
      '#type' => 'details',
      '#title' => '⚡ Recent Activity',
      '#open' => TRUE,
      'table' => [
        '#type' => 'table',
        '#header' => $activity_data[0],
        '#rows' => array_slice($activity_data, 1),
        '#attributes' => ['class' => ['activity-table']],
      ],
    ];
  }

  /**
   * Build insights section.
   */
  protected function buildInsightsSection($metrics_data): array {
    $insights = $metrics_data['insights'];
    
    $insights_data = [
      ['Quality Indicator', 'Average Value'],
      ['AI Response Depth', number_format($insights['avg_ai_response_length']) . ' characters'],
      ['Motivation Analysis Detail', number_format($insights['avg_motivation_length']) . ' characters'],
      ['Classification Density', $insights['avg_tags_per_article'] . ' tags per article'],
    ];
    
    return [
      '#type' => 'details',
      '#title' => '🔍 Analysis Quality Metrics',
      '#open' => TRUE,
      'table' => [
        '#type' => 'table',
        '#header' => $insights_data[0],
        '#rows' => array_slice($insights_data, 1),
        '#attributes' => ['class' => ['insights-table']],
      ],
    ];
  }

}
