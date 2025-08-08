<?php

namespace Drupal\newsmotivationmetrics\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface;
use Drupal\newsmotivationmetrics\Service\Interface\TimelineServiceInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;

/**
 * Service for preparing chart data structures.
 * 
 * Optimizes data formatting for Chart.js consumption with proper
 * error handling and responsive design considerations.
 */
class ChartDataService implements ChartDataServiceInterface {

  /**
   * The timeline service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\TimelineServiceInterface
   */
  protected $timelineService;

  /**
   * The metrics data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface
   */
  protected $metricsDataService;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\newsmotivationmetrics\Service\Interface\TimelineServiceInterface $timeline_service
   *   The timeline service.
   * @param \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface $metrics_data_service
   *   The metrics data service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    TimelineServiceInterface $timeline_service,
    MetricsDataServiceInterface $metrics_data_service,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->timelineService = $timeline_service;
    $this->metricsDataService = $metrics_data_service;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimelineChartData(array $options = []): array {
    // Set defaults
    $defaults = [
      'limit' => 10,
      'days_back' => 90,
      'term_ids' => [],
    ];
    $options = array_merge($defaults, $options);
    
    try {
      $timeline_data = $this->timelineService->getTaxonomyTimelineData(
        $options['limit'],
        $options['days_back']
      );
      
      $top_terms = $this->timelineService->getTopTaxonomyTerms($options['limit'] * 2);
      
      return [
        'timeline_data' => $timeline_data,
        'top_terms' => $top_terms,
        'debug_info' => $this->getChartDebugInfo(),
      ];
      
    } catch (\Exception $e) {
      $this->loggerFactory->get('newsmotivationmetrics')->error('Chart data error: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      return [
        'timeline_data' => [],
        'top_terms' => [],
        'debug_info' => $this->getChartDebugInfo(),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildTermOptionsArray(array $terms): array {
    $options = [];
    foreach ($terms as $term) {
      $usage_count = isset($term['usage_count']) ? $term['usage_count'] : 
        (isset($term['article_count']) ? $term['article_count'] : 0);
      $options[$term['tid']] = $term['name'] . ' (' . $usage_count . ' articles)';
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getChartDebugInfo(): array {
    try {
      $timeline_data = $this->timelineService->getTaxonomyTimelineData(10, 90);
      $top_terms = $this->timelineService->getTopTaxonomyTerms(20);
      
      return [
        'dataPoints' => count($timeline_data),
        'termCount' => count($top_terms),
        'timestamp' => time(),
        'date' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'memory_usage' => memory_get_usage(true),
        'peak_memory' => memory_get_peak_usage(true),
      ];
      
    } catch (\Exception $e) {
      $this->loggerFactory->get('newsmotivationmetrics')->error('Debug info error: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      return [
        'dataPoints' => 0,
        'termCount' => 0,
        'timestamp' => time(),
        'date' => date('Y-m-d H:i:s'),
        'error' => $e->getMessage(),
      ];
    }
  }

}
