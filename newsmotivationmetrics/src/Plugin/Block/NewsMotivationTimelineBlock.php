<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\newsmotivationmetrics\Service\Interface\NewsMotivationTimelineChartServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'News Motivation Timeline Chart (Configurable)' Block.
 *
 * @Block(
 *   id = "news_motivation_timeline_chart",
 *   admin_label = @Translation("News Motivation Timeline Chart (Configurable)"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class NewsMotivationTimelineBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The news motivation timeline chart service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\NewsMotivationTimelineChartServiceInterface
   */
  protected $newsMotivationTimelineChartService;

  /**
   * Constructs a new NewsMotivationTimelineBlock.
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
  public function defaultConfiguration() {
    return [
      'chart_title' => $this->t('Topic Trends Over Time'),
      'days_back' => 30,
      'term_limit' => 10,
      'chart_height' => 400,
      'show_controls' => TRUE,
      'show_legend' => TRUE,
      'auto_refresh' => FALSE,
      'refresh_interval' => 300, // 5 minutes
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

    $form['chart_settings']['chart_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Chart Title'),
      '#default_value' => $config['chart_title'],
      '#description' => $this->t('The title displayed above the chart.'),
      '#required' => TRUE,
    ];

    $form['chart_settings']['chart_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Chart Height (pixels)'),
      '#default_value' => $config['chart_height'],
      '#min' => 200,
      '#max' => 800,
      '#description' => $this->t('Height of the chart in pixels.'),
    ];

    $form['data_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Data Settings'),
      '#open' => TRUE,
    ];

    $form['data_settings']['days_back'] = [
      '#type' => 'number',
      '#title' => $this->t('Days to Show'),
      '#default_value' => $config['days_back'],
      '#min' => 7,
      '#max' => 365,
      '#description' => $this->t('Number of days of data to display in the timeline.'),
    ];

    $form['data_settings']['term_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Terms'),
      '#default_value' => $config['term_limit'],
      '#min' => 5,
      '#max' => 50,
      '#description' => $this->t('Maximum number of taxonomy terms to include in the chart.'),
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
      '#description' => $this->t('Display controls for users to modify the chart (term selector, reset buttons).'),
    ];

    $form['display_settings']['show_legend'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Legend'),
      '#default_value' => $config['show_legend'],
      '#description' => $this->t('Display the chart legend showing term names and colors.'),
    ];

    $form['performance_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Performance Settings'),
      '#open' => FALSE,
    ];

    $form['performance_settings']['auto_refresh'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Refresh'),
      '#default_value' => $config['auto_refresh'],
      '#description' => $this->t('Automatically refresh chart data at regular intervals.'),
    ];

    $form['performance_settings']['refresh_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Refresh Interval (seconds)'),
      '#default_value' => $config['refresh_interval'],
      '#min' => 60,
      '#max' => 3600,
      '#description' => $this->t('How often to refresh the chart data (in seconds). Only applies if auto refresh is enabled.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[performance_settings][auto_refresh]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    
    $values = $form_state->getValues();
    
    // Save chart settings
    $this->configuration['chart_title'] = $values['chart_settings']['chart_title'];
    $this->configuration['chart_height'] = $values['chart_settings']['chart_height'];
    
    // Save data settings
    $this->configuration['days_back'] = $values['data_settings']['days_back'];
    $this->configuration['term_limit'] = $values['data_settings']['term_limit'];
    
    // Save display settings
    $this->configuration['show_controls'] = $values['display_settings']['show_controls'];
    $this->configuration['show_legend'] = $values['display_settings']['show_legend'];
    
    // Save performance settings
    $this->configuration['auto_refresh'] = $values['performance_settings']['auto_refresh'];
    $this->configuration['refresh_interval'] = $values['performance_settings']['refresh_interval'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    
    // Use the shared timeline chart service
    return $this->newsMotivationTimelineChartService->buildNewsMotivationTimelineBlock($config);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $config = $this->getConfiguration();
    return $config['auto_refresh'] ? $config['refresh_interval'] : 1800;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['taxonomy_term_list', 'node_list', 'newsmotivationmetrics_data'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

}
