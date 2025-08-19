<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\newsmotivationmetrics\Service\CredibilityTimelineChartService;

/**
 * Provides a 'Credibility Timeline Chart' Block.
 *
 * @Block(
 *   id = "credibility_timeline_chart",
 *   admin_label = @Translation("Credibility Timeline Chart"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class CredibilityTimelineChartBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The credibility timeline chart service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\CredibilityTimelineChartService
   */
  protected $credibilityTimelineChartService;

  /**
   * Constructs a new CredibilityTimelineChartBlock.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CredibilityTimelineChartService $credibility_timeline_chart_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->credibilityTimelineChartService = $credibility_timeline_chart_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('newsmotivationmetrics.credibility_timeline_chart_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'days_back' => 90,
      'source_limit' => 5,
      'chart_height' => 400,
      'show_controls' => TRUE,
      'show_legend' => TRUE,
      'show_title' => TRUE,
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

    $form['chart_settings']['days_back'] = [
      '#type' => 'number',
      '#title' => $this->t('Days to Show'),
      '#default_value' => $config['days_back'],
      '#min' => 7,
      '#max' => 365,
      '#description' => $this->t('Number of days of credibility data to display.'),
    ];

    $form['chart_settings']['source_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Sources to Show'),
      '#default_value' => $config['source_limit'],
      '#min' => 3,
      '#max' => 15,
      '#description' => $this->t('Limit chart to top N sources by article count.'),
    ];

    $form['chart_settings']['chart_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Chart Height (pixels)'),
      '#default_value' => $config['chart_height'],
      '#min' => 200,
      '#max' => 800,
      '#description' => $this->t('Height of the chart in pixels.'),
    ];

    $form['display_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Options'),
      '#open' => FALSE,
    ];

    $form['display_settings']['show_controls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Chart Controls'),
      '#default_value' => $config['show_controls'],
      '#description' => $this->t('Display controls for users to modify the chart.'),
    ];

    $form['display_settings']['show_legend'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Legend'),
      '#default_value' => $config['show_legend'],
      '#description' => $this->t('Display the chart legend showing source names and colors.'),
    ];

    $form['display_settings']['show_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Title'),
      '#default_value' => $config['show_title'],
      '#description' => $this->t('Display the chart title.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    
    $this->configuration['days_back'] = $values['chart_settings']['days_back'];
    $this->configuration['source_limit'] = $values['chart_settings']['source_limit'];
    $this->configuration['chart_height'] = $values['chart_settings']['chart_height'];
    $this->configuration['show_controls'] = $values['display_settings']['show_controls'];
    $this->configuration['show_legend'] = $values['display_settings']['show_legend'];
    $this->configuration['show_title'] = $values['display_settings']['show_title'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    
    return $this->credibilityTimelineChartService->buildCredibilityTimelineChart([
      'canvas_id' => 'credibility-timeline-chart-block-' . substr(md5(serialize($config)), 0, 8),
      'title' => 'News Source Credibility Trends Over Time',
      'show_controls' => $config['show_controls'],
      'show_legend' => $config['show_legend'],
      'show_title' => $config['show_title'],
      'chart_height' => $config['chart_height'],
      'days_back' => $config['days_back'],
      'source_limit' => $config['source_limit'],
      'container_classes' => ['timeline-chart-container', 'credibility-timeline', 'credibility-timeline-chart-block'],
      'library' => 'newsmotivationmetrics/credibility-timeline',
      'js_behavior' => 'credibilityTimelineChart',
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
    return ['newsmotivationmetrics:credibility_timeline', 'newsmotivationmetrics:metrics'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

}
