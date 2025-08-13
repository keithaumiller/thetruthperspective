<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\newsmotivationmetrics\Service\Interface\NewsMotivationTimelineChartServiceInterface;

/**
 * Provides a 'News Motivation Timeline Chart (Simple)' Block.
 *
 * @Block(
 *   id = "news_motivation_timeline_chart_block",
 *   admin_label = @Translation("News Motivation Timeline Chart (Simple)"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class NewsMotivationTimelineChartBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The news motivation timeline chart service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\NewsMotivationTimelineChartServiceInterface
   */
  protected $newsMotivationTimelineChartService;

  /**
   * Constructs a new NewsMotivationTimelineChartBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\newsmotivationmetrics\Service\Interface\NewsMotivationTimelineChartServiceInterface $news_motivation_timeline_chart_service
   *   The news motivation timeline chart service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    NewsMotivationTimelineChartServiceInterface $news_motivation_timeline_chart_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->newsMotivationTimelineChartService = $news_motivation_timeline_chart_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('newsmotivationmetrics.news_motivation_timeline_chart_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->newsMotivationTimelineChartService->buildNewsMotivationTimelineChart([
      'canvas_id' => 'news-motivation-timeline-chart',
      'title' => '📈 News Motivation Trends Over Time',
      'show_controls' => TRUE,
      'show_legend' => TRUE,
      'show_title' => TRUE,
      'chart_height' => 400,
      'days_back' => 30,
      'term_limit' => 10,
      'container_classes' => ['news-motivation-timeline-section'],
      'library' => 'newsmotivationmetrics/news-motivation-timeline',
      'js_behavior' => 'newsMotivationTimelineChart',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['newsmotivationmetrics:timeline', 'newsmotivationmetrics:chart_data'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Cache for 5 minutes since this is real-time data
    return 300;
  }

}
