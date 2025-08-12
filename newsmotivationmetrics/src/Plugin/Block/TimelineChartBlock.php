<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\newsmotivationmetrics\Service\Interface\TimelineChartServiceInterface;

/**
 * Provides a 'Timeline Chart' Block.
 *
 * @Block(
 *   id = "timeline_chart_block",
 *   admin_label = @Translation("Timeline Chart"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class TimelineChartBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The timeline chart service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\TimelineChartServiceInterface
   */
  protected $timelineChartService;

  /**
   * Constructs a new TimelineChartBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\newsmotivationmetrics\Service\Interface\TimelineChartServiceInterface $timeline_chart_service
   *   The timeline chart service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TimelineChartServiceInterface $timeline_chart_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->timelineChartService = $timeline_chart_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('newsmotivationmetrics.timeline_chart')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->timelineChartService->buildTimelineChart([
      'canvas_id' => 'taxonomy-timeline-chart',
      'title' => '📈 Topic Trends Over Time',
      'show_controls' => TRUE,
      'show_legend' => TRUE,
      'show_title' => TRUE,
      'chart_height' => 400,
      'days_back' => 30,
      'term_limit' => 10,
      'container_classes' => ['taxonomy-timeline-section'],
      'library' => 'newsmotivationmetrics/chart-js',
      'js_behavior' => 'taxonomyTimelineChart',
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
