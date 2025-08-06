<?php
// filepath: /workspaces/thetruthperspective/newsmotivationmetrics/src/Controller/MetricsController.php

namespace Drupal\newsmotivationmetrics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

/**
 * Controller for News Motivation Metrics pages.
 */
class MetricsController extends ControllerBase {

  /**
   * Display the public metrics dashboard.
   */
  public function dashboard() {
    $build = [];
    
    // Page header with professional styling
    $build['header'] = [
      '#markup' => '<div class="news-metrics-header">
        <h1>The Truth Perspective Analytics</h1>
        <p>Real-time insights into news analysis, AI-powered content evaluation, and narrative tracking across media sources.</p>
        <div class="metrics-subtitle">
          <span class="badge">Live Data</span>
          <span class="updated">Updated: ' . date('F j, Y g:i A') . '</span>
        </div>
      </div>',
    ];
    
    // Get metrics data with error handling
    try {
      $metrics = newsmotivationmetrics_get_article_metrics();
      $insights = newsmotivationmetrics_get_motivation_insights();
    } catch (\Exception $e) {
      \Drupal::logger('newsmotivationmetrics')->error('Failed to load metrics data: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      // Fallback data
      $metrics = [
        'total_articles' => 0,
        'articles_with_ai' => 0,
        'articles_with_json' => 0,
        'articles_with_tags' => 0,
        'articles_with_motivation' => 0,
        'articles_with_images' => 0,
        'total_tags' => 0,
        'articles_last_7_days' => 0,
        'articles_last_30_days' => 0,
      ];
      $insights = [
        'avg_motivation_length' => 0,
        'avg_ai_response_length' => 0,
        'avg_tags_per_article' => 0,
      ];
    }
    
    // Content Analysis Overview
    $build['overview'] = [
      '#type' => 'details',
      '#title' => '📊 Content Analysis Overview',
      '#open' => TRUE,
      '#attributes' => ['class' => ['metrics-overview']],
    ];
    
    $overview_data = [
      ['Metric', 'Count', 'Coverage'],
      ['Total Articles Analyzed', number_format($metrics['total_articles']), '100%'],
      ['AI Analysis Complete', number_format($metrics['articles_with_ai']), round(($metrics['articles_with_ai'] / max($metrics['total_articles'], 1)) * 100, 1) . '%'],
      ['Content Extraction Success', number_format($metrics['articles_with_json']), round(($metrics['articles_with_json'] / max($metrics['total_articles'], 1)) * 100, 1) . '%'],
      ['Taxonomy Classification', number_format($metrics['articles_with_tags']), round(($metrics['articles_with_tags'] / max($metrics['total_articles'], 1)) * 100, 1) . '%'],
      ['Motivation Analysis', number_format($metrics['articles_with_motivation']), round(($metrics['articles_with_motivation'] / max($metrics['total_articles'], 1)) * 100, 1) . '%'],
      ['Media Assets Captured', number_format($metrics['articles_with_images']), round(($metrics['articles_with_images'] / max($metrics['total_articles'], 1)) * 100, 1) . '%'],
    ];
    
    $build['overview']['table'] = [
      '#type' => 'table',
      '#header' => $overview_data[0],
      '#rows' => array_slice($overview_data, 1),
      '#attributes' => ['class' => ['metrics-table']],
    ];
    
    // Recent Activity Section
    $build['activity'] = [
      '#type' => 'details',
      '#title' => '⚡ Recent Activity',
      '#open' => TRUE,
    ];
    
    $activity_data = [
      ['Period', 'Articles', 'Daily Average'],
      ['Last 7 Days', number_format($metrics['articles_last_7_days']), round($metrics['articles_last_7_days'] / 7, 1)],
      ['Last 30 Days', number_format($metrics['articles_last_30_days']), round($metrics['articles_last_30_days'] / 30, 1)],
      ['Total Classification Tags', number_format($metrics['total_tags']), ''],
    ];
    
    $build['activity']['table'] = [
      '#type' => 'table',
      '#header' => $activity_data[0],
      '#rows' => array_slice($activity_data, 1),
      '#attributes' => ['class' => ['activity-table']],
    ];
    
    // Analysis Quality Metrics
    $build['insights'] = [
      '#type' => 'details',
      '#title' => '🔍 Analysis Quality Metrics',
      '#open' => TRUE,
    ];
    
    $insights_data = [
      ['Quality Indicator', 'Average Value'],
      ['AI Response Depth', number_format($insights['avg_ai_response_length']) . ' characters'],
      ['Motivation Analysis Detail', number_format($insights['avg_motivation_length']) . ' characters'],
      ['Classification Density', $insights['avg_tags_per_article'] . ' tags per article'],
    ];
    
    $build['insights']['table'] = [
      '#type' => 'table',
      '#header' => $insights_data[0],
      '#rows' => array_slice($insights_data, 1),
      '#attributes' => ['class' => ['insights-table']],
    ];
    
    // Enhanced CSS for professional presentation
    $build['#attached']['html_head'][] = [
      [
        '#tag' => 'style',
        '#value' => '
          .news-metrics-header { 
            margin-bottom: 30px; 
            padding: 30px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px; 
            color: white;
            text-align: center;
          }
          .news-metrics-header h1 { 
            margin: 0 0 15px 0; 
            font-size: 2.5em;
            font-weight: 300;
          }
          .news-metrics-header p { 
            margin: 0 0 20px 0; 
            font-size: 1.2em;
            opacity: 0.9;
          }
          .metrics-subtitle {
            display: flex;
            justify-content: center;
            gap: 20px;
            align-items: center;
          }
          .badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
          }
          .updated {
            font-size: 0.9em;
            opacity: 0.8;
          }
          .metrics-overview, .activity, .insights { 
            margin-bottom: 25px; 
          }
          .metrics-table, .activity-table, .insights-table { 
            width: 100%; 
            margin-top: 15px;
          }
          .metrics-table th, .activity-table th, .insights-table th { 
            background-color: #f8f9fa; 
            font-weight: 600;
            padding: 12px;
          }
          .metrics-table td, .activity-table td, .insights-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #dee2e6;
          }
        ',
      ],
      'news-metrics-public-styles',
    ];
    
    return $build;
  }
  
  /**
   * Display the admin version of the dashboard.
   */
  public function adminDashboard() {
    $build = $this->dashboard();
    
    // Add admin-specific modifications
    $build['header']['#markup'] = str_replace(
      'The Truth Perspective Analytics',
      'News Motivation Metrics Dashboard (Admin)',
      $build['header']['#markup']
    );
    
    return $build;
  }
  
  /**
   * Display details for a specific tag.
   */
  public function tagDetails($tid) {
    $term = Term::load($tid);
    
    if (!$term) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    
    $build = [];
    
    $build['header'] = [
      '#markup' => '<h1>Tag Details: ' . $term->getName() . '</h1>',
    ];
    
    $build['back'] = [
      '#markup' => '<p>' . Link::createFromRoute('← Back to Metrics Dashboard', 'newsmotivationmetrics.metrics_dashboard')->toString() . '</p>',
    ];
    
    return $build;
  }

}