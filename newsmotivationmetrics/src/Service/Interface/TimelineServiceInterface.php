<?php

namespace Drupal\newsmotivationmetrics\Service\Interface;

/**
 * Interface for timeline data services.
 * 
 * Defines contract for retrieving time-based data for charts and analytics.
 */
interface TimelineServiceInterface {

  /**
   * Get taxonomy timeline data for charting.
   *
   * @param int $limit
   *   Maximum number of terms to include.
   * @param int $days_back
   *   Number of days to look back for data.
   * 
   * @return array
   *   Timeline data array optimized for Chart.js consumption.
   */
  public function getTaxonomyTimelineData(int $limit = 10, int $days_back = 90): array;

  /**
   * Get top taxonomy terms by usage count.
   *
   * @param int $limit
   *   Maximum number of terms to return.
   * 
   * @return array
   *   Array of term data with usage statistics.
   */
  public function getTopTaxonomyTerms(int $limit = 10): array;

  /**
   * Get article count for specific date range.
   *
   * @param string $start_date
   *   Start date in Y-m-d format.
   * @param string $end_date
   *   End date in Y-m-d format.
   * @param array $term_ids
   *   Optional array of term IDs to filter by.
   * 
   * @return int
   *   Number of articles in the specified range.
   */
  public function getArticleCountForDateRange(string $start_date, string $end_date, array $term_ids = []): int;

}
