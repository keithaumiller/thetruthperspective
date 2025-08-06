<?php
// filepath: /workspaces/thetruthperspective/newsmotivationmetrics/src/Controller/ChartTestController.php

namespace Drupal\newsmotivationmetrics\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Minimal Chart.js test controller for The Truth Perspective.
 */
class ChartTestController extends ControllerBase {

  /**
   * Simple Chart.js test page - Hello World only.
   */
  public function testPage() {
    // Only attach the minimal chart-test library, not chart-debug
    return [
      '#theme' => 'chart_test',
      '#attached' => [
        'library' => ['newsmotivationmetrics/chart-test'],
        // Explicitly prevent other chart libraries from loading
        'drupalSettings' => [
          'chartTestOnly' => TRUE,
        ],
      ],
    ];
  }

}