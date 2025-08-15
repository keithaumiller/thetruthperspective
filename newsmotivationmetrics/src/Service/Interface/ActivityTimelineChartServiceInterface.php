<?php

namespace Drupal\newsmotivationmetrics\Service\Interface;

/**
 * Interface for activity timeline chart service.
 * 
 * Provides consistent interface for building activity timeline charts
 * across dashboard pages and block implementations.
 */
interface ActivityTimelineChartServiceInterface {

  /**
   * Build an activity timeline chart render array.
   *
   * @param array $options
   *   Chart configuration options including:
   *   - canvas_id: Unique canvas element ID
   *   - title: Chart title text
   *   - show_controls: Whether to show chart controls
   *   - show_legend: Whether to show chart legend
   *   - show_title: Whether to show chart title
   *   - chart_height: Chart height in pixels
   *   - days_back: Number of days of data to show
   *   - source_limit: Maximum number of sources to display
   *   - container_classes: Array of CSS classes for container
   *   - library: Drupal library to attach
   *   - js_behavior: JavaScript behavior name
   *
   * @return array
   *   Complete render array for activity timeline chart.
   */
  public function buildActivityTimelineChart(array $options = []): array;

  /**
   * Build an activity timeline chart for block usage.
   *
   * @param array $config
   *   Block configuration array.
   *
   * @return array
   *   Render array optimized for block display.
   */
  public function buildActivityTimelineBlock(array $config = []): array;

}
