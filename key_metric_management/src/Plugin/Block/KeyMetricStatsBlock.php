<?php

namespace Drupal\key_metric_management\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\key_metric_management\Service\MetricAnalyzer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Key Metric Stats' Block.
 *
 * @Block(
 *   id = "key_metric_stats_block",
 *   admin_label = @Translation("Key Metric Statistics"),
 *   category = @Translation("Key Metrics")
 * )
 */
class KeyMetricStatsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The metric analyzer service.
   */
  protected MetricAnalyzer $metricAnalyzer;

  /**
   * Constructs a new KeyMetricStatsBlock instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MetricAnalyzer $metric_analyzer
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->metricAnalyzer = $metric_analyzer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('key_metric_management.metric_analyzer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $stats = [];
    
    try {
      $stats = $this->metricAnalyzer->getMetricStats();
    }
    catch (\Exception $e) {
      // Log error but continue with empty stats
      \Drupal::logger('key_metric_management')->error('Error loading stats for block: @error', ['@error' => $e->getMessage()]);
    }

    $build = [
      '#theme' => 'key_metric_stats_block',
      '#stats' => $stats,
      '#dashboard_url' => Url::fromRoute('key_metric_management.dashboard')->toString(),
      '#attached' => [
        'library' => ['key_metric_management/block-styling'],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return 3600; // 1 hour cache
  }

}