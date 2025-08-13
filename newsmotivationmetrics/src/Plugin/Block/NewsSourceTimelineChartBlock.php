<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\newsmotivationmetrics\Service\Interface\NewsSourceTimelineChartServiceInterface;

/**
 * Provides a 'News Source Timeline Chart' Block.
 *
 * @Block(
 *   id = "news_source_timeline_chart_block",
 *   admin_label = @Translation("News Source Timeline Chart"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class NewsSourceTimelineChartBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The news source timeline chart service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\NewsSourceTimelineChartServiceInterface
   */
  protected $newsSourceTimelineChartService;

  /**
   * Constructs a new NewsSourceTimelineChartBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\newsmotivationmetrics\Service\Interface\NewsSourceTimelineChartServiceInterface $news_source_timeline_chart_service
   *   The news source timeline chart service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    NewsSourceTimelineChartServiceInterface $news_source_timeline_chart_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->newsSourceTimelineChartService = $news_source_timeline_chart_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('newsmotivationmetrics.news_source_timeline_chart_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->newsSourceTimelineChartService->buildNewsSourceTimelineChart([
      'canvas_id' => 'news-source-timeline-chart',
      'title' => '📊 News Source Quality Trends Over Time',
      'show_controls' => TRUE,
      'show_legend' => TRUE,
      'show_title' => TRUE,
      'chart_height' => 400,
      'days_back' => 30,
      'source_limit' => 3,
      'container_classes' => ['news-source-timeline-section'],
      'library' => 'newsmotivationmetrics/news-source-timeline',
      'js_behavior' => 'newsSourceTimelineChart',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['newsmotivationmetrics:source_timeline', 'newsmotivationmetrics:chart_data'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Cache for 5 minutes since this is real-time data
    return 300;
  }

}
