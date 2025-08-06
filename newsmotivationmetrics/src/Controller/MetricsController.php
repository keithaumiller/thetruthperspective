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
    
    // Page header
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
    
    // Key metrics overview
    $metrics = newsmotivationmetrics_get_article_metrics();
    $insights = newsmotivationmetrics_get_motivation_insights();
    
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
    
    // Activity metrics
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
    
    // Analysis quality insights
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
    
    // Top narrative themes
    $tags = newsmotivationmetrics_get_tag_metrics();
    $top_tags = array_slice($tags, 0, 20); // Top 20 tags
    
    $build['tags'] = [
      '#type' => 'details',
      '#title' => '🎯 Dominant Narrative Themes',
      '#open' => TRUE,
    ];
    
    $tag_rows = [];
    foreach ($top_tags as $tag) {
      $tag_link = Link::createFromRoute(
        $tag['name'],
        'newsmotivationmetrics.tag_details',
        ['tid' => $tag['tid']]
      );
      
      $percentage = round(($tag['article_count'] / max($metrics['total_articles'], 1)) * 100, 1);
      $bar_width = min($percentage * 3, 100); // Visual bar representation
      
      $tag_rows[] = [
        $tag_link,
        number_format($tag['article_count']),
        $percentage . '%',
        [
          'data' => '<div class="progress-bar-container"><div class="progress-bar" style="width: ' . $bar_width . 'px;"></div></div>',
          'data' => [
            '#markup' => '<div class="progress-bar-container"><div class="progress-bar" style="width: ' . $bar_width . 'px;"></div></div>',
          ],
        ],
      ];
    }
    
    $build['tags']['table'] = [
      '#type' => 'table',
      '#header' => ['Theme/Topic', 'Articles', 'Prevalence', 'Distribution'],
      '#rows' => $tag_rows,
      '#attributes' => ['class' => ['tags-table']],
    ];
    
    // News sources analysis (public version)
    $sources = newsmotivationmetrics_get_news_source_metrics();
    
    if (!empty($sources)) {
      $build['sources'] = [
        '#type' => 'details',
        '#title' => '📰 Media Source Distribution',
        '#open' => FALSE,
      ];
      
      $source_rows = [];
      foreach (array_slice($sources, 0, 15) as $source) { // Top 15 for public view
        $source_rows[] = [
          $source['source'],
          number_format($source['article_count']),
          round(($source['article_count'] / max($metrics['total_articles'], 1)) * 100, 1) . '%',
        ];
      }
      
      $build['sources']['table'] = [
        '#type' => 'table',
        '#header' => ['Media Source', 'Articles', 'Share'],
        '#rows' => $source_rows,
        '#attributes' => ['class' => ['sources-table']],
      ];
    }
    
    // About section
    $build['about'] = [
      '#type' => 'details',
      '#title' => 'ℹ️ About This Analysis',
      '#open' => FALSE,
    ];
    
    $build['about']['content'] = [
      '#markup' => '
        <div class="about-content">
          <h3>Methodology</h3>
          <p>This dashboard presents real-time analytics from The Truth Perspective news analysis system, which uses:</p>
          <ul>
            <li><strong>AI-Powered Content Analysis:</strong> Advanced language models evaluate article content for narrative patterns, motivations, and thematic elements</li>
            <li><strong>Automated Entity Recognition:</strong> Machine learning identifies key people, organizations, and concepts</li>
            <li><strong>Motivation Mapping:</strong> Algorithmic assessment of underlying motivations and perspectives in news content</li>
            <li><strong>Cross-Source Correlation:</strong> Pattern recognition across multiple media sources and timeframes</li>
          </ul>
          
          <h3>Data Sources</h3>
          <p>Content is aggregated from ' . count($sources) . '+ media sources, processed through Diffbot content extraction, and analyzed using Claude AI models.</p>
          
          <h3>Update Frequency</h3>
          <p>Metrics are updated in real-time as new content is processed. Analysis typically completes within minutes of article publication.</p>
        </div>
      ',
    ];
    
    // Enhanced CSS for public presentation
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
          .metrics-overview, .activity, .insights, .tags, .sources, .about { 
            margin-bottom: 25px; 
          }
          .metrics-table, .activity-table, .insights-table, .tags-table, .sources-table { 
            width: 100%; 
            margin-top: 15px;
          }
          .metrics-table th, .activity-table th, .insights-table th, .tags-table th, .sources-table th { 
            background-color: #f8f9fa; 
            font-weight: 600;
            padding: 12px;
          }
          .metrics-table td, .activity-table td, .insights-table td, .tags-table td, .sources-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #dee2e6;
          }
          .progress-bar-container {
            width: 100px;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
          }
          .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            border-radius: 4px;
            transition: width 0.3s ease;
          }
          .about-content {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
          }
          .about-content h3 {
            color: #495057;
            margin-top: 20px;
            margin-bottom: 10px;
          }
          .about-content ul {
            margin: 10px 0;
            padding-left: 25px;
          }
          .about-content li {
            margin-bottom: 8px;
          }
        ',
      ],
      'news-metrics-public-styles',
    ];
    
    return $build;
  }
  
  /**
   * Display the admin version of the dashboard (more detailed).
   */
  public function adminDashboard() {
    // This can be the same as the current dashboard or enhanced with admin-only features
    $build = $this->dashboard();
    
    // Add admin-specific enhancements
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
      '#markup' => '<div class="tag-details-header">
        <h1>📊 Theme Analysis: ' . $term->getName() . '</h1>
        <p>Detailed breakdown of articles and patterns for this narrative theme.</p>
      </div>',
    ];
    
    // Get articles with this tag
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('field_tags', $tid)
      ->sort('created', 'DESC')
      ->range(0, 100);
    
    $nids = $query->execute();
    $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
    
    $build['summary'] = [
      '#markup' => '<div class="tag-summary">
        <h2>Summary Statistics</h2>
        <p><strong>Total articles with this theme:</strong> ' . count($nodes) . '</p>
        <p><strong>Analysis period:</strong> ' . 
        (count($nodes) > 0 ? 
          date('M j, Y', min(array_map(function($n) { return $n->getCreatedTime(); }, $nodes))) . 
          ' to ' . 
          date('M j, Y', max(array_map(function($n) { return $n->getCreatedTime(); }, $nodes)))
          : 'No articles found'
        ) . '</p>
      </div>',
    ];
    
    if (!empty($nodes)) {
      $rows = [];
      foreach ($nodes as $node) {
        $pub_date = $node->get('field_publication_date')->value;
        $formatted_date = $pub_date ? date('M j, Y', strtotime($pub_date)) : date('M j, Y', $node->getCreatedTime());
        
        $rows[] = [
          Link::fromTextAndUrl($node->getTitle(), $node->toUrl()),
          $formatted_date,
          $node->get('field_news_source')->value ?: 'Unknown',
          [
            'data' => Link::fromTextAndUrl('View Analysis', $node->toUrl()),
            'class' => ['action-link'],
          ],
        ];
      }
      
      $build['articles'] = [
        '#type' => 'table',
        '#header' => ['Article Title', 'Published', 'Source', 'Action'],
        '#rows' => $rows,
        '#caption' => 'Articles featuring this theme (most recent first)',
        '#attributes' => ['class' => ['tag-articles-table']],
      ];
    } else {
      $build['no_articles'] = [
        '#markup' => '<p class="no-articles">No articles found with this theme.</p>',
      ];
    }
    
    $build['back'] = [
      '#markup' => '<div class="back-link">' . 
        Link::createFromRoute('← Back to Analytics Dashboard', 'newsmotivationmetrics.metrics_dashboard')->toString() . 
        '</div>',
    ];
    
    // CSS for tag details page
    $build['#attached']['html_head'][] = [
      [
        '#tag' => 'style',
        '#value' => '
          .tag-details-header {
            margin-bottom: 30px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 5px solid #007bff;
          }
          .tag-summary {
            margin-bottom: 25px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
          }
          .tag-articles-table {
            margin-top: 20px;
          }
          .action-link a {
            background: #007bff;
            color: white;
            padding: 5px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
          }
          .action-link a:hover {
            background: #0056b3;
          }
          .back-link {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
          }
          .no-articles {
            padding: 40px;
            text-align: center;
            color: #6c757d;
            font-style: italic;
          }
        ',
      ],
      'tag-details-styles',
    ];
    
    return $build;
  }
}