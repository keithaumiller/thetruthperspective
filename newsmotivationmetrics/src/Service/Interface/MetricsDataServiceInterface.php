<?php

namespace Drupal\newsmotivationmetrics\Service\Interface;

/**
 * Interface for metrics data services.
 * 
 * Defines contract for retrieving various metrics and analytics data
 * from the news analysis system.
 */
interface MetricsDataServiceInterface {

  /**
   * Get general article metrics.
   *
   * @return array
   *   Array containing total articles, processing coverage, and success rates.
   */
  public function getArticleMetrics(): array;

  /**
   * Get motivation analysis insights.
   *
   * @return array
   *   Array containing motivation analysis quality metrics.
   */
  public function getMotivationInsights(): array;

  /**
   * Get temporal processing analytics.
   *
   * @return array
   *   Array containing time-based processing metrics.
   */
  public function getTemporalMetrics(): array;

  /**
   * Get sentiment distribution metrics.
   *
   * @return array
   *   Array containing sentiment analysis percentages.
   */
  public function getSentimentMetrics(): array;

  /**
   * Get entity recognition metrics.
   *
   * @return array
   *   Array containing entity identification statistics.
   */
  public function getEntityMetrics(): array;

  /**
   * Get news source metrics.
   *
   * @return array
   *   Array of news source data with article counts.
   */
  public function getNewsSourceMetrics(): array;

  /**
   * Get taxonomy tag metrics.
   *
   * @return array
   *   Array of tag data with usage statistics.
   */
  public function getTagMetrics(): array;

  /**
   * Get comprehensive metrics data.
   *
   * @return array
   *   Consolidated array of all metrics data with error handling.
   */
  public function getAllMetricsData(): array;

}
