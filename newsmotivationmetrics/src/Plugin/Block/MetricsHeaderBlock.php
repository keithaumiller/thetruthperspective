<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\newsmotivationmetrics\Service\Interface\DashboardBuilderServiceInterface;

/**
 * Provides a 'Metrics Header' Block.
 *
 * @Block(
 *   id = "metrics_header_block",
 *   admin_label = @Translation("Metrics Dashboard Header"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class MetricsHeaderBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The dashboard builder service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\DashboardBuilderServiceInterface
   */
  protected $dashboardBuilder;

  /**
   * Constructs a new MetricsHeaderBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\newsmotivationmetrics\Service\Interface\DashboardBuilderServiceInterface $dashboard_builder
   *   The dashboard builder service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    DashboardBuilderServiceInterface $dashboard_builder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dashboardBuilder = $dashboard_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('newsmotivationmetrics.dashboard_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'dashboard_title' => 'The Truth Perspective Analytics',
      'dashboard_description' => 'Real-time insights into news analysis, AI-powered content evaluation, and narrative tracking across media sources.',
      'show_live_badge' => TRUE,
      'show_timestamp' => TRUE,
      'custom_badge_text' => 'Live Data',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['dashboard_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dashboard Title'),
      '#description' => $this->t('The main title displayed at the top of the metrics dashboard.'),
      '#default_value' => $config['dashboard_title'],
      '#required' => TRUE,
      '#size' => 60,
    ];

    $form['dashboard_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Dashboard Description'),
      '#description' => $this->t('A brief description of what the dashboard provides.'),
      '#default_value' => $config['dashboard_description'],
      '#rows' => 3,
      '#required' => TRUE,
    ];

    $form['display_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Display Options'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['display_options']['show_live_badge'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Live Data Badge'),
      '#description' => $this->t('Display a "Live Data" badge to indicate real-time information.'),
      '#default_value' => $config['show_live_badge'],
    ];

    $form['display_options']['custom_badge_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Badge Text'),
      '#description' => $this->t('Custom text for the data badge.'),
      '#default_value' => $config['custom_badge_text'],
      '#size' => 30,
      '#states' => [
        'visible' => [
          ':input[name="settings[display_options][show_live_badge]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['display_options']['show_timestamp'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Last Updated Timestamp'),
      '#description' => $this->t('Display when the dashboard was last updated.'),
      '#default_value' => $config['show_timestamp'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    
    $this->configuration['dashboard_title'] = $values['dashboard_title'];
    $this->configuration['dashboard_description'] = $values['dashboard_description'];
    $this->configuration['show_live_badge'] = $values['display_options']['show_live_badge'];
    $this->configuration['custom_badge_text'] = $values['display_options']['custom_badge_text'];
    $this->configuration['show_timestamp'] = $values['display_options']['show_timestamp'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['news-metrics-header']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h1',
        '#value' => $config['dashboard_title'],
      ],
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $config['dashboard_description'],
      ],
      '#cache' => [
        'max-age' => 300, // Cache for 5 minutes
        'tags' => ['newsmotivationmetrics:dashboard'],
      ],
    ];

    // Add subtitle section if badges or timestamp are enabled
    if ($config['show_live_badge'] || $config['show_timestamp']) {
      $build['subtitle'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['metrics-subtitle']],
      ];

      if ($config['show_live_badge']) {
        $build['subtitle']['badge'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $config['custom_badge_text'],
          '#attributes' => ['class' => ['badge']],
        ];
      }

      if ($config['show_timestamp']) {
        $build['subtitle']['updated'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => 'Updated: ' . date('F j, Y g:i A'),
          '#attributes' => ['class' => ['updated']],
        ];
      }
    }

    return $build;
  }

}
