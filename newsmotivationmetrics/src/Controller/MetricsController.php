<?php

namespace Drupal\newsmotivationmetrics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

/**
 * Controller for News Motivation Metrics pages.
 * 
 * Provides public analytics dashboard and admin interfaces for The Truth
 * Perspective news analysis system. Handles chart data generation, metrics
 * aggregation, and responsive dashboard presentation.
 */
class MetricsController extends ControllerBase {

  /**
   * Display the public metrics dashboard.
   * 
   * Main analytics interface showing taxonomy trends, processing metrics,
   * sentiment analysis, and entity recognition statistics.
   * 
   * @return array
   *   Drupal render array for the dashboard page.
   */
  public function dashboard() {
    $build = [];
    
    // Get chart data for timeline visualization
    $timeline_data = $this->getTaxonomyTimelineData();
    $top_terms = $this->getTopTaxonomyTerms(20);
    
    // Page header with live data indicator
    $build['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['news-metrics-header']],
    ];
    
    $build['header']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => 'The Truth Perspective Analytics',
    ];
    
    $build['header']['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => 'Real-time insights into news analysis, AI-powered content evaluation, and narrative tracking across media sources.',
    ];
    
    $build['header']['subtitle'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['metrics-subtitle']],
    ];
    
    $build['header']['subtitle']['badge'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => 'Live Data',
      '#attributes' => ['class' => ['badge']],
    ];
    
    $build['header']['subtitle']['updated'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => 'Updated: ' . date('F j, Y g:i A'),
      '#attributes' => ['class' => ['updated']],
    ];
    
    // Taxonomy Timeline Chart Section
    $build['taxonomy_timeline'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['taxonomy-timeline-section']],
    ];
    
    $build['taxonomy_timeline']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => '📈 Topic Trends Over Time',
      '#attributes' => ['class' => ['chart-section-title']],
    ];
    
    // Chart controls using proper form elements
    $build['taxonomy_timeline']['controls'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-controls']],
    ];
    
    $build['taxonomy_timeline']['controls']['selector_group'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['control-group']],
    ];
    
    $build['taxonomy_timeline']['controls']['selector_group']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => 'Add/Remove Terms:',
      '#attributes' => ['for' => 'term-selector'],
    ];
    
    $build['taxonomy_timeline']['controls']['selector_group']['selector'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $this->buildTermOptionsArray($top_terms),
      '#default_value' => array_slice(array_column($top_terms, 'tid'), 0, 10),
      '#attributes' => [
        'class' => ['term-selector'],
        'id' => 'term-selector',
        'size' => 8,
      ],
    ];
    
    $build['taxonomy_timeline']['controls']['buttons'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['control-buttons']],
    ];
    
    $build['taxonomy_timeline']['controls']['buttons']['reset'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => 'Reset to Top 10',
      '#attributes' => [
        'id' => 'reset-chart',
        'class' => ['btn', 'btn-secondary'],
        'type' => 'button',
      ],
    ];
    
    $build['taxonomy_timeline']['controls']['buttons']['clear'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => 'Clear All',
      '#attributes' => [
        'id' => 'clear-chart',
        'class' => ['btn', 'btn-outline'],
        'type' => 'button',
      ],
    ];
    
    $build['taxonomy_timeline']['controls']['info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-info']],
    ];
    
    $build['taxonomy_timeline']['controls']['info']['text'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => '📊 Showing frequency of topic mentions over the last 90 days',
      '#attributes' => ['class' => ['info-text']],
    ];
    
    // Chart container with canvas element
    $build['taxonomy_timeline']['chart_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-container']],
    ];
    
    $build['taxonomy_timeline']['chart_wrapper']['canvas'] = [
      '#type' => 'html_tag',
      '#tag' => 'canvas',
      '#value' => '',
      '#attributes' => [
        'id' => 'taxonomy-timeline-chart',
        'width' => '800',
        'height' => '400',
      ],
    ];
    
    // Debug information for troubleshooting
    $build['taxonomy_timeline']['chart_wrapper']['debug'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'chart-debug-info'],
    ];
    
    $build['taxonomy_timeline']['chart_wrapper']['debug']['status'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => 'Initializing chart...',
      '#attributes' => ['id' => 'chart-status'],
    ];
    
    $build['taxonomy_timeline']['chart_wrapper']['debug']['data_status'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => 'Data status: Loading...',
      '#attributes' => ['id' => 'chart-data-status'],
    ];
    
    // Methodology explanation section
    $build['explanation'] = [
      '#type' => 'details',
      '#title' => 'ℹ️ About The Truth Perspective Analytics',
      '#open' => FALSE,
      '#attributes' => ['class' => ['methodology-explanation']],
    ];
    
    $build['explanation']['content'] = [
      '#markup' => $this->getMethodologyContent(),
    ];
    
    // Get metrics data with error handling
    $metrics_data = $this->getMetricsData();
    
    // Build dashboard sections
    $this->buildOverviewSection($build, $metrics_data);
    $this->buildTemporalSection($build, $metrics_data);
    $this->buildSentimentSection($build, $metrics_data);
    $this->buildEntitiesSection($build, $metrics_data);
    $this->buildActivitySection($build, $metrics_data);
    $this->buildInsightsSection($build, $metrics_data);
    
    // Attach Chart.js library and pass data to JavaScript
    $build['#attached']['library'][] = 'newsmotivationmetrics/chart-js';
    $build['#attached']['drupalSettings']['newsmotivationmetrics'] = [
      'timelineData' => $timeline_data,
      'topTerms' => $top_terms,
      'debugInfo' => [
        'dataPoints' => count($timeline_data),
        'termCount' => count($top_terms),
        'timestamp' => time(),
      ],
    ];
    
    return $build;
  }
  
  /**
   * Get methodology content for explanation section.
   * 
   * @return string
   *   HTML content describing analysis methodology.
   */
  private function getMethodologyContent() {
    return '
      <div class="explanation-content">
        <h3>🎯 Our Mission</h3>
        <p>The Truth Perspective leverages advanced AI technology to analyze news content across multiple media sources, providing transparency into narrative patterns, motivational drivers, and thematic trends in modern journalism.</p>
        
        <h3>🔬 Analysis Methodology</h3>
        <div class="methodology-grid">
          <div class="method-card">
            <h4>🤖 AI-Powered Content Analysis</h4>
            <p>Using Claude AI models, we evaluate article content for underlying motivations, bias indicators, and narrative frameworks. Each article undergoes comprehensive linguistic and semantic analysis.</p>
          </div>
          <div class="method-card">
            <h4>🔍 Entity Recognition & Classification</h4>
            <p>Automated identification of key people, organizations, locations, and concepts enables cross-reference analysis and theme tracking across multiple sources and timeframes.</p>
          </div>
          <div class="method-card">
            <h4>📊 Statistical Aggregation</h4>
            <p>Real-time metrics aggregate processing success rates, content coverage, and analytical depth to provide transparency into our system\'s capabilities and reliability.</p>
          </div>
        </div>
        
        <h3>📈 Data Sources & Processing</h3>
        <ul class="data-sources">
          <li><strong>Content Extraction:</strong> Diffbot API processes raw HTML into clean, structured article data</li>
          <li><strong>AI Analysis:</strong> Claude language models analyze motivation, sentiment, and thematic elements</li>
          <li><strong>Taxonomy Generation:</strong> Automated tag creation based on content analysis and entity recognition</li>
          <li><strong>Cross-Source Correlation:</strong> Pattern recognition across multiple media outlets and publication timeframes</li>
        </ul>
        
        <h3>🔒 Privacy & Transparency</h3>
        <p>All metrics represent <strong>aggregated statistics</strong> from publicly available news content. We do not track individual users, collect personal data, or store private information. Our analysis focuses exclusively on published media content and provides transparency into automated content evaluation processes.</p>
        
        <div class="update-info">
          <p><strong>Update Frequency:</strong> Metrics refresh in real-time as new articles are processed. Analysis typically completes within minutes of publication.</p>
          <p><strong>Data Retention:</strong> Historical analysis data enables trend tracking and longitudinal narrative studies.</p>
        </div>
      </div>
    ';
  }
  
  /**
   * Get all metrics data with error handling.
   * 
   * @return array
   *   Associative array containing all metrics data.
   */
  private function getMetricsData() {
    try {
      return [
        'metrics' => \newsmotivationmetrics_get_article_metrics(),
        'insights' => \newsmotivationmetrics_get_motivation_insights(),
        'temporal_metrics' => \newsmotivationmetrics_get_temporal_metrics(),
        'sentiment_metrics' => \newsmotivationmetrics_get_sentiment_metrics(),
        'entity_metrics' => \newsmotivationmetrics_get_entity_metrics(),
      ];
    } catch (\Exception $e) {
      \Drupal::logger('newsmotivationmetrics')->error('Failed to load metrics data: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      // Return fallback data structure
      return [
        'metrics' => [
          'total_articles' => 0,
          'articles_with_ai' => 0,
          'articles_with_json' => 0,
          'articles_with_tags' => 0,
          'articles_with_motivation' => 0,
          'articles_with_images' => 0,
          'total_tags' => 0,
          'articles_last_7_days' => 0,
          'articles_last_30_days' => 0,
        ],
        'insights' => [
          'avg_motivation_length' => 0,
          'avg_ai_response_length' => 0,
          'avg_tags_per_article' => 0,
        ],
        'temporal_metrics' => [
          'peak_processing_hour' => 'Unknown',
          'avg_processing_time' => 0,
          'articles_last_24_hours' => 0,
        ],
        'sentiment_metrics' => [
          'positive_sentiment_percentage' => 0,
          'negative_sentiment_percentage' => 0,
          'neutral_sentiment_percentage' => 0,
        ],
        'entity_metrics' => [
          'unique_people_identified' => 0,
          'unique_organizations_identified' => 0,
          'unique_locations_identified' => 0,
        ],
      ];
    }
  }
  
  /**
   * Build Content Analysis Overview section.
   */
  private function buildOverviewSection(&$build, $metrics_data) {
    $metrics = $metrics_data['metrics'];
    
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
  }
  
  /**
   * Build Temporal Processing Analytics section.
   */
  private function buildTemporalSection(&$build, $metrics_data) {
    $metrics = $metrics_data['metrics'];
    $temporal_metrics = $metrics_data['temporal_metrics'];
    
    $build['temporal'] = [
      '#type' => 'details',
      '#title' => '⏱️ Temporal Processing Analytics',
      '#open' => TRUE,
    ];
    
    $temporal_data = [
      ['Time Metric', 'Value', 'Context'],
      ['Articles Processed (24h)', number_format($temporal_metrics['articles_last_24_hours']), 'Real-time processing volume'],
      ['Peak Processing Hour', $temporal_metrics['peak_processing_hour'], 'Busiest analysis period'],
      ['Average Processing Time', round($temporal_metrics['avg_processing_time'], 2) . ' minutes', 'From URL to full analysis'],
      ['Weekly Processing Trend', $this->calculateWeeklyTrend($metrics), 'Growth/decline indicator'],
    ];
    
    $build['temporal']['table'] = [
      '#type' => 'table',
      '#header' => $temporal_data[0],
      '#rows' => array_slice($temporal_data, 1),
      '#attributes' => ['class' => ['temporal-table']],
    ];
  }
  
  /**
   * Build Sentiment Distribution Analysis section.
   */
  private function buildSentimentSection(&$build, $metrics_data) {
    $metrics = $metrics_data['metrics'];
    $sentiment_metrics = $metrics_data['sentiment_metrics'];
    
    $build['sentiment'] = [
      '#type' => 'details',
      '#title' => '💭 Sentiment Distribution Analysis',
      '#open' => TRUE,
    ];
    
    $sentiment_data = [
      ['Sentiment Category', 'Percentage', 'Article Count'],
      ['Positive Sentiment', round($sentiment_metrics['positive_sentiment_percentage'], 1) . '%', number_format($metrics['total_articles'] * ($sentiment_metrics['positive_sentiment_percentage'] / 100))],
      ['Negative Sentiment', round($sentiment_metrics['negative_sentiment_percentage'], 1) . '%', number_format($metrics['total_articles'] * ($sentiment_metrics['negative_sentiment_percentage'] / 100))],
      ['Neutral Sentiment', round($sentiment_metrics['neutral_sentiment_percentage'], 1) . '%', number_format($metrics['total_articles'] * ($sentiment_metrics['neutral_sentiment_percentage'] / 100))],
      ['Sentiment Analysis Coverage', round((($sentiment_metrics['positive_sentiment_percentage'] + $sentiment_metrics['negative_sentiment_percentage'] + $sentiment_metrics['neutral_sentiment_percentage']) > 0 ? 100 : 0), 1) . '%', 'AI sentiment detection rate'],
    ];
    
    $build['sentiment']['table'] = [
      '#type' => 'table',
      '#header' => $sentiment_data[0],
      '#rows' => array_slice($sentiment_data, 1),
      '#attributes' => ['class' => ['sentiment-table']],
    ];
  }
  
  /**
   * Build Entity Recognition Metrics section.
   */
  private function buildEntitiesSection(&$build, $metrics_data) {
    $metrics = $metrics_data['metrics'];
    $entity_metrics = $metrics_data['entity_metrics'];
    
    $build['entities'] = [
      '#type' => 'details',
      '#title' => '🏷️ Entity Recognition Metrics',
      '#open' => TRUE,
    ];
    
    $entity_data = [
      ['Entity Type', 'Unique Count', 'Recognition Rate'],
      ['People Identified', number_format($entity_metrics['unique_people_identified']), $this->calculateEntityRate($entity_metrics['unique_people_identified'], $metrics['total_articles']) . '%'],
      ['Organizations Tracked', number_format($entity_metrics['unique_organizations_identified']), $this->calculateEntityRate($entity_metrics['unique_organizations_identified'], $metrics['total_articles']) . '%'],
      ['Locations Mapped', number_format($entity_metrics['unique_locations_identified']), $this->calculateEntityRate($entity_metrics['unique_locations_identified'], $metrics['total_articles']) . '%'],
      ['Total Named Entities', number_format($entity_metrics['unique_people_identified'] + $entity_metrics['unique_organizations_identified'] + $entity_metrics['unique_locations_identified']), 'Cross-reference database'],
    ];
    
    $build['entities']['table'] = [
      '#type' => 'table',
      '#header' => $entity_data[0],
      '#rows' => array_slice($entity_data, 1),
      '#attributes' => ['class' => ['entities-table']],
    ];
  }
  
  /**
   * Build Recent Activity section.
   */
  private function buildActivitySection(&$build, $metrics_data) {
    $metrics = $metrics_data['metrics'];
    
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
  }
  
  /**
   * Build Analysis Quality Metrics section.
   */
  private function buildInsightsSection(&$build, $metrics_data) {
    $insights = $metrics_data['insights'];
    
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
  }
  
  /**
   * Get taxonomy timeline data for charting.
   * 
   * Retrieves daily article counts for top taxonomy terms over the past 90 days.
   * Optimized for Chart.js consumption with proper data structure.
   * 
   * @return array
   *   Timeline data array with term information and daily counts.
   */
  private function getTaxonomyTimelineData() {
    $database = \Drupal::database();
    $timeline_data = [];
    
    try {
      $top_terms = $this->getTopTaxonomyTerms(10);
      $days_back = 90;
      
      foreach ($top_terms as $term) {
        $term_data = [
          'term_id' => $term['tid'],
          'term_name' => $term['name'],
          'data' => [],
        ];
        
        for ($i = $days_back; $i >= 0; $i--) {
          $date = date('Y-m-d', strtotime("-{$i} days"));
          $start_timestamp = strtotime($date . ' 00:00:00');
          $end_timestamp = strtotime($date . ' 23:59:59');
          
          $query = $database->select('node__field_tags', 'nt');
          $query->leftJoin('node_field_data', 'n', 'nt.entity_id = n.nid');
          $query->condition('nt.field_tags_target_id', $term['tid']);
          $query->condition('n.type', 'article');
          $query->condition('n.status', 1);
          $query->condition('n.created', $start_timestamp, '>=');
          $query->condition('n.created', $end_timestamp, '<=');
          $count = $query->countQuery()->execute()->fetchField();
          
          $term_data['data'][] = [
            'date' => $date,
            'count' => (int) $count,
          ];
        }
        
        $timeline_data[] = $term_data;
      }
      
    } catch (\Exception $e) {
      \Drupal::logger('newsmotivationmetrics')->error('Timeline data error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
    
    return $timeline_data;
  }
  
  /**
   * Get top taxonomy terms by usage count.
   * 
   * @param int $limit
   *   Maximum number of terms to return.
   * 
   * @return array
   *   Array of term data with usage statistics.
   */
  private function getTopTaxonomyTerms($limit = 10) {
    $database = \Drupal::database();
    $terms = [];
    
    try {
      $query = $database->select('node__field_tags', 'nt');
      $query->leftJoin('taxonomy_term_field_data', 't', 'nt.field_tags_target_id = t.tid');
      $query->leftJoin('node_field_data', 'n', 'nt.entity_id = n.nid');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('t.status', 1);
      $query->addField('t', 'tid');
      $query->addField('t', 'name');
      $query->addExpression('COUNT(*)', 'usage_count');
      $query->groupBy('t.tid');
      $query->groupBy('t.name');
      $query->orderBy('usage_count', 'DESC');
      $query->range(0, $limit);
      
      $results = $query->execute()->fetchAll();
      
      foreach ($results as $result) {
        $terms[] = [
          'tid' => $result->tid,
          'name' => $result->name,
          'usage_count' => $result->usage_count,
        ];
      }
      
    } catch (\Exception $e) {
      \Drupal::logger('newsmotivationmetrics')->error('Top terms error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
    
    return $terms;
  }
  
  /**
   * Build options array for term selector.
   * 
   * @param array $terms
   *   Array of term data.
   * 
   * @return array
   *   Options array for Drupal select element.
   */
  private function buildTermOptionsArray($terms) {
    $options = [];
    foreach ($terms as $term) {
      $options[$term['tid']] = $term['name'] . ' (' . $term['usage_count'] . ' articles)';
    }
    return $options;
  }
  
  /**
   * Calculate weekly processing trend indicator.
   * 
   * @param array $metrics
   *   Article metrics data.
   * 
   * @return string
   *   Formatted trend indicator with emoji and percentage.
   */
  private function calculateWeeklyTrend($metrics) {
    $current_week = $metrics['articles_last_7_days'];
    $previous_week = max($metrics['articles_last_30_days'] - $current_week, 0) / 3;
    
    if ($previous_week > 0) {
      $trend = (($current_week - $previous_week) / $previous_week) * 100;
      if ($trend > 5) {
        return '📈 +' . round($trend, 1) . '% growth';
      } elseif ($trend < -5) {
        return '📉 ' . round($trend, 1) . '% decline';
      } else {
        return '➡️ Stable (~' . round($trend, 1) . '%)';
      }
    }
    return '📊 Insufficient data';
  }
  
  /**
   * Calculate entity recognition rate percentage.
   * 
   * @param int $entity_count
   *   Number of unique entities identified.
   * @param int $total_articles
   *   Total number of articles processed.
   * 
   * @return float
   *   Recognition rate as percentage.
   */
  private function calculateEntityRate($entity_count, $total_articles) {
    if ($total_articles > 0) {
      return round(($entity_count / $total_articles) * 100, 1);
    }
    return 0;
  }

  /**
   * Display the admin version of the dashboard.
   * 
   * @return array
   *   Drupal render array for admin dashboard.
   */
  public function adminDashboard() {
    $build = $this->dashboard();
    
    // Modify header for admin interface
    $build['header']['title']['#value'] = 'News Motivation Metrics Dashboard (Admin)';
    
    return $build;
  }
  
  /**
   * Display details for a specific taxonomy term.
   * 
   * @param int $tid
   *   Taxonomy term ID.
   * 
   * @return array
   *   Drupal render array for tag details page.
   * 
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   When taxonomy term is not found.
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