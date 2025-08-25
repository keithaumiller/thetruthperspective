<?php

namespace Drupal\news_extractor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\news_extractor\Service\DailyLimitService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for daily limit administration pages.
 */
class DailyLimitController extends ControllerBase {

  /**
   * The daily limit service.
   *
   * @var \Drupal\news_extractor\Service\DailyLimitService
   */
  protected $dailyLimitService;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new DailyLimitController object.
   *
   * @param \Drupal\news_extractor\Service\DailyLimitService $daily_limit_service
   *   The daily limit service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(DailyLimitService $daily_limit_service, ConfigFactoryInterface $config_factory) {
    $this->dailyLimitService = $daily_limit_service;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('news_extractor.daily_limit'),
      $container->get('config.factory')
    );
  }

  /**
   * Daily limits dashboard page.
   *
   * @return array
   *   Render array for the dashboard.
   */
  public function dashboard() {
    $config = $this->configFactory->get('news_extractor.settings');
    $is_enabled = $config->get('daily_limit_enabled');
    $default_limit = $config->get('default_daily_limit') ?: 5;

    $build = [];

    // System status section
    $build['status'] = [
      '#type' => 'details',
      '#title' => $this->t('Daily Limit System Status'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['daily-limits-status']],
    ];

    $status_icon = $is_enabled ? '✅' : '❌';
    $status_text = $is_enabled ? $this->t('ENABLED') : $this->t('DISABLED');
    $status_class = $is_enabled ? 'status-enabled' : 'status-disabled';

    $build['status']['system_status'] = [
      '#markup' => '<div class="system-status ' . $status_class . '">' .
        '<h3>' . $status_icon . ' Daily Processing Limits: ' . $status_text . '</h3>' .
        ($is_enabled ? '<p>Default limit per source: <strong>' . $default_limit . ' articles per day</strong></p>' : 
                      '<p class="warning">All news sources can process unlimited articles.</p>') .
        '</div>',
    ];

    if (!$is_enabled) {
      $build['status']['disabled_notice'] = [
        '#markup' => '<div class="messages messages--warning">' .
          $this->t('Daily limits are currently disabled. Enable them in the ') .
          '<a href="/admin/config/content/news-extractor">News Extractor configuration</a>.' .
          '</div>',
      ];
      return $build;
    }

    // Today's statistics
    $today_counts = $this->dailyLimitService->getAllDailyCounts();
    
    $build['today'] = [
      '#type' => 'details',
      '#title' => $this->t('Today\'s Processing Status (@date)', ['@date' => date('Y-m-d')]),
      '#open' => TRUE,
      '#attributes' => ['class' => ['todays-status']],
    ];

    if (empty($today_counts)) {
      $build['today']['no_data'] = [
        '#markup' => '<p>' . $this->t('No articles processed today yet.') . '</p>',
      ];
    } else {
      // Summary stats
      $total_processed = 0;
      $sources_at_limit = 0;
      foreach ($today_counts as $data) {
        $total_processed += $data['count'];
        if ($data['at_limit']) {
          $sources_at_limit++;
        }
      }

      $build['today']['summary'] = [
        '#markup' => '<div class="summary-stats">' .
          '<p><strong>Summary:</strong> ' . count($today_counts) . ' sources tracked, ' .
          $sources_at_limit . ' at their daily limit, ' . $total_processed . ' total articles processed</p>' .
          '</div>',
      ];

      // Source details table
      $header = [
        $this->t('News Source'),
        $this->t('Articles Processed'),
        $this->t('Daily Limit'),
        $this->t('Remaining'),
        $this->t('Status'),
      ];

      $rows = [];
      foreach ($today_counts as $source => $data) {
        $status_icon = $data['at_limit'] ? '🔴' : ($data['count'] >= $data['limit'] * 0.8 ? '🟡' : '🟢');
        $status_text = $data['at_limit'] ? $this->t('AT LIMIT') : $this->t('Active');
        $status_class = $data['at_limit'] ? 'at-limit' : 'active';

        $rows[] = [
          'data' => [
            $source,
            $data['count'],
            $data['limit'],
            $data['remaining'],
            ['data' => $status_icon . ' ' . $status_text, 'class' => [$status_class]],
          ],
          'class' => [$status_class],
        ];
      }

      $build['today']['table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => ['class' => ['daily-limits-table']],
      ];
    }

    // Recent statistics
    $recent_stats = $this->dailyLimitService->getProcessingStatistics(7);
    
    $build['recent'] = [
      '#type' => 'details',
      '#title' => $this->t('Last 7 Days Statistics'),
      '#open' => count($today_counts) === 0, // Open if no activity today
      '#attributes' => ['class' => ['recent-stats']],
    ];

    if (empty($recent_stats)) {
      $build['recent']['no_data'] = [
        '#markup' => '<p>' . $this->t('No processing data available for the last 7 days.') . '</p>',
      ];
    } else {
      $recent_rows = [];
      foreach ($recent_stats as $date => $day_data) {
        $sources_list = [];
        foreach ($day_data['sources'] as $source => $source_data) {
          $status_icon = $source_data['at_limit'] ? '🔴' : '🟢';
          $sources_list[] = $status_icon . ' ' . $source . ': ' . $source_data['count'] . '/' . $source_data['limit'];
        }

        $recent_rows[] = [
          $date,
          $day_data['total_processed'],
          count($day_data['sources']),
          $day_data['sources_at_limit'],
          implode('<br>', $sources_list),
        ];
      }

      $recent_header = [
        $this->t('Date'),
        $this->t('Total Processed'),
        $this->t('Sources Tracked'),
        $this->t('Sources at Limit'),
        $this->t('Source Details'),
      ];

      $build['recent']['table'] = [
        '#type' => 'table',
        '#header' => $recent_header,
        '#rows' => $recent_rows,
        '#attributes' => ['class' => ['recent-stats-table']],
      ];
    }

    // Add some basic CSS
    $build['#attached']['html_head'][] = [
      [
        '#tag' => 'style',
        '#value' => '
          .daily-limits-status .system-status { padding: 15px; border-radius: 5px; margin-bottom: 15px; }
          .daily-limits-status .status-enabled { background-color: #d4edda; border: 1px solid #c3e6cb; }
          .daily-limits-status .status-disabled { background-color: #f8d7da; border: 1px solid #f5c6cb; }
          .daily-limits-table .at-limit { background-color: #f8d7da; }
          .daily-limits-table .active { background-color: #d4edda; }
          .summary-stats { padding: 10px; background-color: #e9ecef; border-radius: 3px; margin-bottom: 15px; }
          .recent-stats-table { font-size: 0.9em; }
          .recent-stats-table td:last-child { font-size: 0.8em; line-height: 1.3; }
        ',
      ],
      'daily-limits-styles'
    ];

    return $build;
  }

}
