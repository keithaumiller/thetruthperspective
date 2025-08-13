<?php

namespace Drupal\newsmotivationmetrics\Service\Interface;

/**
 * Interface for news source timeline chart service.
 * 
 * Provides shared news source timeline chart construction capabilities for
 * both dashboard pages and configurable blocks. This interface specifically
 * handles timeline charts that display credibility, bias, and sentiment
 * trends by news source over time for The Truth Perspective platform.
 */
interface NewsSourceTimelineChartServiceInterface {

  /**
   * Build a complete news source timeline chart render array.
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
   *   - source_limit: Maximum number of news sources to display
   *   - container_classes: CSS classes for main container
   *   - library: Drupal library to attach
   *   - js_behavior: JavaScript behavior name
   *
   * @return array
   *   Complete render array with chart, controls, and settings.
   */
  public function buildNewsSourceTimelineChart(array $options = []): array;

  /**
   * Build news source timeline chart specifically for block implementation.
   *
   * @param array $config
   *   Block configuration array with:
   *   - chart_title: Block title
   *   - show_controls: Enable/disable controls
   *   - show_legend: Enable/disable legend
   *   - chart_height: Chart height
   *   - days_back: Timeline period
   *   - source_limit: Number of sources
   *   - auto_refresh: Enable auto-refresh
   *   - refresh_interval: Refresh frequency in seconds
   *
   * @return array
   *   Block-optimized chart render array.
   */
  public function buildNewsSourceTimelineBlock(array $config = []): array;

}
