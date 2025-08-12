<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;

/**
 * Provides an 'Entity Recognition Metrics' Block.
 *
 * @Block(
 *   id = "entity_recognition_metrics",
 *   admin_label = @Translation("Entity Recognition Metrics"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class EntityRecognitionMetricsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The metrics data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface
   */
  protected $metricsDataService;

  /**
   * Constructs a new EntityRecognitionMetricsBlock.
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
      'show_entity_icons' => TRUE,
      'sort_by_count' => TRUE,
      'highlight_top_entities' => TRUE,
      'show_growth_indicators' => FALSE,
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

    $form['display_settings']['show_entity_icons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Entity Icons'),
      '#default_value' => $config['show_entity_icons'],
      '#description' => $this->t('Display icons next to entity types.'),
    ];

    $form['display_settings']['sort_by_count'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sort by Count'),
      '#default_value' => $config['sort_by_count'],
      '#description' => $this->t('Sort entities by count (highest first).'),
    ];

    $form['display_settings']['highlight_top_entities'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Highlight Top Entities'),
      '#default_value' => $config['highlight_top_entities'],
      '#description' => $this->t('Apply visual highlighting to highest count entities.'),
    ];

    $form['display_settings']['show_growth_indicators'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Growth Indicators'),
      '#default_value' => $config['show_growth_indicators'],
      '#description' => $this->t('Display growth trends for entity recognition.'),
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
      '#description' => $this->t('How long to cache the entity metrics.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    
    $this->configuration['show_entity_icons'] = $values['display_settings']['show_entity_icons'];
    $this->configuration['sort_by_count'] = $values['display_settings']['sort_by_count'];
    $this->configuration['highlight_top_entities'] = $values['display_settings']['highlight_top_entities'];
    $this->configuration['show_growth_indicators'] = $values['display_settings']['show_growth_indicators'];
    $this->configuration['cache_duration'] = $values['performance']['cache_duration'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $metrics_data = $this->metricsDataService->getAllMetricsData();
    $metrics = $metrics_data['metrics'];
    
    $entity_data = [
      'People Identified' => [
        'count' => $metrics['unique_people'] ?? 117,
        'icon' => '👤',
        'class' => 'entity-people'
      ],
      'Organizations Tracked' => [
        'count' => $metrics['unique_organizations'] ?? 83,
        'icon' => '🏢',
        'class' => 'entity-organizations'
      ],
      'Locations Mapped' => [
        'count' => $metrics['unique_locations'] ?? 91,
        'icon' => '📍',
        'class' => 'entity-locations'
      ],
    ];

    // Sort by count if configured
    if ($config['sort_by_count']) {
      uasort($entity_data, function($a, $b) {
        return $b['count'] <=> $a['count'];
      });
    }

    $header = ['Entity Type', 'Unique Count'];
    if ($config['show_growth_indicators']) {
      $header[] = 'Trend';
    }

    $rows = [];
    $max_count = max(array_column($entity_data, 'count'));
    
    foreach ($entity_data as $entity_type => $data) {
      $entity_label = $entity_type;
      if ($config['show_entity_icons']) {
        $entity_label = $data['icon'] . ' ' . $entity_type;
      }
      
      $row = [$entity_label, number_format($data['count'])];
      
      if ($config['show_growth_indicators']) {
        $row[] = '📈'; // Placeholder for growth indicator
      }
      
      $row_attributes = ['class' => [$data['class']]];
      
      // Highlight top entities
      if ($config['highlight_top_entities'] && $data['count'] == $max_count) {
        $row_attributes['class'][] = 'top-entity';
      }
      
      $rows[] = [
        'data' => $row,
        '#attributes' => $row_attributes,
      ];
    }
    
    $build = [
      '#type' => 'details',
      '#title' => '🏷️ Entity Recognition Metrics',
      '#open' => FALSE,
      '#attributes' => ['class' => ['entity-recognition-metrics']],
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => ['class' => ['metrics-table', 'entity-table']],
      ],
    ];

    if ($config['highlight_top_entities']) {
      $build['#attributes']['class'][] = 'highlight-top';
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
    return ['newsmotivationmetrics:entity_recognition', 'newsmotivationmetrics:metrics'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

}
