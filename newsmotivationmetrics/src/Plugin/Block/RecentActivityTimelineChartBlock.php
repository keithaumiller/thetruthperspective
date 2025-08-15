<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\newsmotivationmetrics\Service\Interface\ActivityTimelineChartServiceInterface;

/**
 * Provides a 'Recent Activity Timeline Chart' Block.
 *
 * @Block(
 *   id = "recent_activity_timeline_chart",
 *   admin_label = @Translation("Recent Activity Timeline Chart"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class RecentActivityTimelineChartBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The activity timeline chart service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\ActivityTimelineChartServiceInterface
   */
  protected $activityTimelineChartService;

  /**
   * Constructs a new RecentActivityTimelineChartBlock.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ActivityTimelineChartServiceInterface $activity_timeline_chart_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->activityTimelineChartService = $activity_timeline_chart_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('newsmotivationmetrics.activity_timeline_chart_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'days_to_show' => 90,
      'show_unpublished' => TRUE,
      'top_sources_limit' => 6,
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

    $form['chart_settings']['days_to_show'] = [
      '#type' => 'number',
      '#title' => $this->t('Days to Show'),
      '#default_value' => $config['days_to_show'],
      '#min' => 7,
      '#max' => 120,
    ];

    $form['chart_settings']['show_unpublished'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Unpublished Articles'),
      '#default_value' => $config['show_unpublished'],
      '#description' => $this->t('Include articles still being processed in the chart.'),
    ];

    $form['chart_settings']['top_sources_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Sources to Show'),
      '#default_value' => $config['top_sources_limit'],
      '#min' => 3,
      '#max' => 15,
      '#description' => $this->t('Limit chart to top N sources by article count.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    
    $this->configuration['days_to_show'] = $values['chart_settings']['days_to_show'];
    $this->configuration['show_unpublished'] = $values['chart_settings']['show_unpublished'];
    $this->configuration['top_sources_limit'] = $values['chart_settings']['top_sources_limit'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    
    return $this->activityTimelineChartService->buildActivityTimelineChart([
      'canvas_id' => 'recent-activity-timeline-chart-' . substr(md5(microtime()), 0, 8),
      'title' => 'Recent Activity Timeline',
      'show_controls' => TRUE,
      'show_legend' => TRUE,
      'show_title' => TRUE,
      'chart_height' => 400,
      'days_back' => $config['days_to_show'],
      'source_limit' => $config['top_sources_limit'],
      'show_unpublished' => $config['show_unpublished'],
      'container_classes' => ['timeline-chart-container', 'activity-timeline', 'recent-activity-timeline-chart-block'],
      'library' => 'newsmotivationmetrics/activity-timeline-chart',
      'js_behavior' => 'activityTimelineChart',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 300; // Cache for 5 minutes
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['newsmotivationmetrics:activity_timeline', 'newsmotivationmetrics:metrics'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

}
