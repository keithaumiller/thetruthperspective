<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;

/**
 * Provides a 'Recent Activity Metrics' Block.
 *
 * @Block(
 *   id = "recent_activity_metrics",
 *   admin_label = @Translation("Recent Activity Metrics"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class RecentActivityMetricsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The metrics data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface
   */
  protected $metricsDataService;

  /**
   * Constructs a new RecentActivityMetricsBlock.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MetricsDataServiceInterface $metrics_data_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->metricsDataService = $metrics_data_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('newsmotivationmetrics.metrics_data_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'days_to_show' => 15,
      'cache_duration' => 300,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['display_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Settings'),
      '#open' => TRUE,
    ];

    $form['display_settings']['days_to_show'] = [
      '#type' => 'number',
      '#title' => $this->t('Days to Show'),
      '#default_value' => $config['days_to_show'],
      '#min' => 5,
      '#max' => 30,
      '#description' => $this->t('Number of recent days to display in the activity tables.'),
    ];

    $form['performance'] = [
      '#type' => 'details',
      '#title' => $this->t('Performance Settings'),
      '#open' => FALSE,
    ];

    $form['performance']['cache_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache Duration (seconds)'),
      '#default_value' => $config['cache_duration'],
      '#min' => 60,
      '#max' => 3600,
      '#description' => $this->t('How long to cache the activity metrics.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    
    $this->configuration['days_to_show'] = $values['display_settings']['days_to_show'];
    $this->configuration['cache_duration'] = $values['performance']['cache_duration'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    
    // Get daily data
    $daily_articles = $this->metricsDataService->getDailyArticlesBySource();
    $daily_tags = $this->metricsDataService->getDailyTagCounts();
    
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['recent-activity-metrics']],
    ];
    
    // Article counts by source section
    $build['articles_section'] = [
      '#type' => 'details',
      '#title' => '⚡ Daily Article Activity (Last 30 Days)',
      '#open' => TRUE,
      '#attributes' => ['class' => ['daily-articles-section']],
    ];
    
    if (!empty($daily_articles)) {
      $article_rows = [];
      foreach ($daily_articles as $date_data) {
        $date = $date_data['date'];
        $total_pub = $date_data['total_published'];
        $total_unpub = $date_data['total_unpublished'];
        
        // Create source breakdown
        $source_details = [];
        foreach ($date_data['sources'] as $source => $counts) {
          $source_details[] = "{$source}: {$counts['published']}pub/{$counts['unpublished']}proc";
        }
        $source_breakdown = implode(', ', $source_details);
        
        $article_rows[] = [
          'data' => [
            $date,
            $total_pub,
            $total_unpub,
            $total_pub + $total_unpub,
            $source_breakdown,
          ],
          '#attributes' => [
            'class' => $total_pub > 20 ? ['high-activity'] : ($total_pub > 10 ? ['medium-activity'] : ['normal-activity'])
          ],
        ];
      }
      
      $build['articles_section']['table'] = [
        '#type' => 'table',
        '#header' => [
          'Date',
          'Published',
          'Processing',
          'Total',
          'By Source (pub/proc)',
        ],
        '#rows' => array_slice($article_rows, 0, $config['days_to_show']), // Show configured number of days
        '#attributes' => ['class' => ['metrics-table', 'daily-articles-table']],
      ];
    } else {
      $build['articles_section']['empty'] = [
        '#markup' => '<p>No article data available for the last 30 days.</p>',
      ];
    }
    
    // Classification tags section
    $build['tags_section'] = [
      '#type' => 'details',
      '#title' => '🏷️ Daily Classification Tags',
      '#open' => FALSE,
      '#attributes' => ['class' => ['daily-tags-section']],
    ];
    
    if (!empty($daily_tags)) {
      $tag_rows = [];
      $total_tags_30_days = 0;
      
      foreach ($daily_tags as $date_data) {
        $date = $date_data['date'];
        $tag_count = $date_data['tag_count'];
        $article_count = $date_data['article_count'];
        $tags_per_article = $date_data['tags_per_article'];
        
        $total_tags_30_days += $tag_count;
        
        $tag_rows[] = [
          'data' => [
            $date,
            $tag_count,
            $article_count,
            $tags_per_article,
          ],
          '#attributes' => [
            'class' => $tag_count > 50 ? ['high-activity'] : ($tag_count > 25 ? ['medium-activity'] : ['normal-activity'])
          ],
        ];
      }
      
      // Add summary row
      $avg_tags_per_day = count($daily_tags) > 0 ? round($total_tags_30_days / count($daily_tags), 1) : 0;
      $tag_rows[] = [
        'data' => [
          '<strong>30-Day Average</strong>',
          "<strong>{$avg_tags_per_day}</strong>",
          '',
          '',
        ],
        '#attributes' => ['class' => ['summary-row']],
      ];
      
      $build['tags_section']['table'] = [
        '#type' => 'table',
        '#header' => [
          'Date',
          'New Tags',
          'Articles',
          'Tags/Article',
        ],
        '#rows' => array_slice($tag_rows, 0, 11), // Show last 10 days + summary
        '#attributes' => ['class' => ['metrics-table', 'daily-tags-table']],
      ];
    } else {
      $build['tags_section']['empty'] = [
        '#markup' => '<p>No tag data available for the last 30 days.</p>',
      ];
    }
    
    // Add CSS for styling
    $build['#attached']['library'][] = 'newsmotivationmetrics/chart-style';
    
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $config = $this->getConfiguration();
    return $config['cache_duration'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['newsmotivationmetrics:recent_activity', 'newsmotivationmetrics:metrics'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

}
