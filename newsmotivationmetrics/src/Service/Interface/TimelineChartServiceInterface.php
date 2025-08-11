<?php

namespace Drupal\newsmotivationmetrics\Service\Interface;

/**
 * Interface for timeline chart service.
 * 
 * Provides shared timeline chart construction capabilities for
 * both dashboard pages and configurable blocks.
 */
interface TimelineChartServiceInterface {

  /**
   * Build a complete timeline chart render array.
   *
   * @param array $options
   *   Chart configuration options including:
   *   - canvas_id: Unique canvas element ID
   *   - title: Chart title text
   *   - show_controls: Whether to show interactive controls
   *   - show_legend: Whether to show chart legend
   *   - show_title: Whether to display the title
   *   - chart_height: Chart height in pixels (max 500)
   *   - days_back: Number of days to include in timeline
   *   - term_limit: Maximum number of terms to display
   *   - container_classes: CSS classes for main container
   *   - library: Drupal library to attach
   *   - js_behavior: JavaScript behavior name
   *
   * @return array
   *   Complete render array with chart, controls, and settings.
   */
  public function buildTimelineChart(array $options = []): array;

  /**
   * Build timeline chart specifically for block implementation.
   *
   * @param array $config
   *   Block configuration array with:
   *   - chart_title: Block title
   *   - show_controls: Enable/disable controls
   *   - show_legend: Enable/disable legend
   *   - chart_height: Chart height
   *   - days_back: Timeline period
   *   - term_limit: Number of terms
   *   - auto_refresh: Enable auto-refresh
   *   - refresh_interval: Refresh frequency in seconds
   *
   * @return array
   *   Block-optimized chart render array.
   */
  public function buildTimelineBlock(array $config = []): array;

}
