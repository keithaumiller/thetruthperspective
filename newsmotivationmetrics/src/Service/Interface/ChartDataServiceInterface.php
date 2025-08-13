<?php

namespace Drupal\newsmotivationmetrics\Service\Interface;

/**
 * Interface for chart data services.
 * 
 * Defines contract for preparing data structures optimized for Chart.js rendering.
 */
interface ChartDataServiceInterface {

  /**
   * Get chart-ready timeline data.
   *
   * @param array $options
   *   Chart configuration options including:
   *   - limit: Number of terms to include
   *   - days_back: Number of days to look back
   *   - term_ids: Specific terms to include
   * 
   * @return array
   *   Chart.js compatible data structure.
   */
  public function getTimelineChartData(array $options = []): array;

  /**
   * Get term selector options for UI controls.
   *
   * @param array $terms
   *   Array of term data.
   * 
   * @return array
   *   Options array for Drupal select elements.
   */
  public function buildTermOptionsArray(array $terms): array;

  /**
   * Get debug information for chart troubleshooting.
   *
   * @return array
   *   Debug data including data point counts and timestamps.
   */
  public function getChartDebugInfo(): array;

  /**
   * Get chart-ready news source timeline data.
   *
   * @param array $options
   *   Chart configuration options including:
   *   - limit: Number of news sources to include
   *   - days_back: Number of days to look back
   *   - source_ids: Specific sources to include
   * 
   * @return array
   *   Chart.js compatible data structure with credibility, bias, and sentiment.
   */
  public function getNewsSourceTimelineChartData(array $options = []): array;

  /**
   * Get news source selector options for UI controls.
   *
   * @param array $sources
   *   Array of news source data.
   * 
   * @return array
   *   Options array for Drupal select elements.
   */
  public function buildNewsSourceOptionsArray(array $sources): array;

}
