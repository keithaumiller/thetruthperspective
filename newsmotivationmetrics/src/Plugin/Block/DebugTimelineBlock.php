<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a simple 'Debug Timeline Block' for testing.
 *
 * @Block(
 *   id = "debug_timeline_chart",
 *   admin_label = @Translation("Debug Timeline Chart"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class DebugTimelineBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'debug_message' => 'Debug block is working!',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['debug_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Debug Message'),
      '#default_value' => $config['debug_message'],
      '#description' => $this->t('Message to display for debugging.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['debug_message'] = $form_state->getValue('debug_message');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    
    $build = [
      '#markup' => '<div style="padding: 20px; background: #f0f8ff; border: 2px solid #0066cc; border-radius: 5px;">' . 
                   '<h3>Debug Timeline Block</h3>' .
                   '<p>' . $this->t('@message', ['@message' => $config['debug_message']]) . '</p>' .
                   '<p><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</p>' .
                   '<p><strong>Services Status:</strong></p>' .
                   '<ul>' . $this->getServicesStatus() . '</ul>' .
                   '</div>',
      '#cache' => ['max-age' => 0], // No caching for debugging
    ];

    return $build;
  }

  /**
   * Get status of required services.
   */
  private function getServicesStatus(): string {
    $status = '';
    
    try {
      $chart_service = \Drupal::service('newsmotivationmetrics.chart_data_service');
      $status .= '<li>✅ Chart Data Service: Available (' . get_class($chart_service) . ')</li>';
      
      // Test the service method
      $data = $chart_service->getTimelineChartData(['limit' => 5, 'days_back' => 7]);
      $status .= '<li>✅ Chart Data Method: Working (returned ' . count($data['timeline_data'] ?? []) . ' datasets)</li>';
      
    } catch (\Exception $e) {
      $status .= '<li>❌ Chart Data Service Error: ' . $e->getMessage() . '</li>';
    }

    try {
      $dashboard_service = \Drupal::service('newsmotivationmetrics.dashboard_builder');
      $status .= '<li>✅ Dashboard Builder Service: Available</li>';
    } catch (\Exception $e) {
      $status .= '<li>❌ Dashboard Builder Service Error: ' . $e->getMessage() . '</li>';
    }

    try {
      $block_manager = \Drupal::service('plugin.manager.block');
      $definitions = $block_manager->getDefinitions();
      $timeline_blocks = array_filter($definitions, function($def) {
        return strpos($def['id'], 'timeline') !== false;
      });
      $status .= '<li>✅ Block Manager: Found ' . count($timeline_blocks) . ' timeline blocks</li>';
    } catch (\Exception $e) {
      $status .= '<li>❌ Block Manager Error: ' . $e->getMessage() . '</li>';
    }

    return $status;
  }

}
