<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
  public function build() {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['news-metrics-header']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h1',
        '#value' => 'The Truth Perspective Analytics',
      ],
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => 'Real-time insights into news analysis, AI-powered content evaluation, and narrative tracking across media sources.',
      ],
      'subtitle' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['metrics-subtitle']],
        'badge' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => 'Live Data',
          '#attributes' => ['class' => ['badge']],
        ],
        'updated' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => 'Updated: ' . date('F j, Y g:i A'),
          '#attributes' => ['class' => ['updated']],
        ],
      ],
      '#cache' => [
        'max-age' => 300, // Cache for 5 minutes
        'tags' => ['newsmotivationmetrics:dashboard'],
      ],
    ];
  }

}
