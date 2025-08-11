<?php

namespace Drupal\newsmotivationmetrics\Service\Interface;

/**
 * Interface for dashboard builder services.
 * 
 * Defines contract for building dashboard render arrays with proper
 * Drupal theming and responsive design.
 */
interface DashboardBuilderServiceInterface {

  /**
   * Build the main public metrics dashboard.
   *
   * @return array
   *   Complete Drupal render array for the dashboard.
   */
  public function buildPublicDashboard(): array;

  /**
   * Build the admin version of the metrics dashboard.
   *
   * @return array
   *   Drupal render array for admin dashboard with additional controls.
   */
  public function buildAdminDashboard(): array;

  /**
   * Build metrics overview section.
   *
   * @param array $metrics_data
   *   Comprehensive metrics data.
   * 
   * @return array
   *   Render array for metrics overview section.
   */
  public function buildMetricsOverview(array $metrics_data): array;

  /**
   * Build methodology explanation section.
   *
   * @return array
   *   Render array for methodology documentation.
   */
  public function buildMethodologySection(): array;

}
