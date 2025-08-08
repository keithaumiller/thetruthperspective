<?php

namespace Drupal\newsmotivationmetrics\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\newsmotivationmetrics\Service\Interface\DashboardBuilderServiceInterface;
use Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;

/**
 * Service for building dashboard render arrays.
 * 
 * Centralizes dashboard construction logic with proper Drupal theming,
 * responsive design, and accessibility considerations.
 */
class DashboardBuilderService implements DashboardBuilderServiceInterface {

  /**
   * The chart data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface
   */
  protected $chartDataService;

  /**
   * The metrics data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface
   */
  protected $metricsDataService;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface $chart_data_service
   *   The chart data service.
   * @param \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface $metrics_data_service
   *   The metrics data service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   */
  public function __construct(
    ChartDataServiceInterface $chart_data_service,
    MetricsDataServiceInterface $metrics_data_service,
    RendererInterface $renderer,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->chartDataService = $chart_data_service;
    $this->metricsDataService = $metrics_data_service;
    $this->renderer = $renderer;
    $this->logger = $logger_factory->get('newsmotivationmetrics');
  }

  /**
   * {@inheritdoc}
   */
  public function buildPublicDashboard(): array {
    $chart_data = $this->chartDataService->getTimelineChartData(['limit' => 10, 'days_back' => 30]);
    $metrics_data = $this->metricsDataService->getAllMetricsData();
    
    $build = [];
    
    // Page header
    $build['header'] = $this->buildDashboardHeader();
    
    // Timeline chart section
    $build['timeline'] = $this->buildTimelineSection(
      $chart_data['timeline_data'],
      $chart_data['top_terms']
    );
    
    // Metrics overview
    $build['overview'] = $this->buildMetricsOverview($metrics_data);
    
    // Methodology section
    $build['methodology'] = $this->buildMethodologySection();
    
    // Attach libraries and JavaScript settings
    $build['#attached']['library'][] = 'newsmotivationmetrics/chart-js';
    $build['#attached']['drupalSettings']['newsmotivationmetrics'] = [
      'timelineData' => $chart_data['timeline_data'],
      'topTerms' => $chart_data['top_terms'],
      'debugInfo' => $chart_data['debug_info'],
    ];
    
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildAdminDashboard(): array {
    $build = $this->buildPublicDashboard();
    
    // Modify header for admin interface
    $build['header']['title']['#value'] = 'News Motivation Metrics Dashboard (Admin)';
    
    // Add admin-specific sections if needed
    $build['admin_controls'] = [
      '#type' => 'details',
      '#title' => '🔧 Admin Controls',
      '#open' => FALSE,
      '#attributes' => ['class' => ['admin-controls']],
    ];
    
    $build['admin_controls']['refresh'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => 'Admin dashboard with extended controls and detailed analytics.',
    ];
    
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTimelineSection(array $timeline_data, array $top_terms): array {
    $section = [
      '#type' => 'container',
      '#attributes' => ['class' => ['taxonomy-timeline-section']],
    ];
    
    $section['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => '📈 Topic Trends Over Time',
      '#attributes' => ['class' => ['chart-section-title']],
    ];
    
    // Chart controls
    $section['controls'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-controls']],
    ];
    
    $section['controls']['selector_group'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['control-group']],
    ];
    
    $section['controls']['selector_group']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => 'Add/Remove Terms:',
      '#attributes' => ['for' => 'term-selector'],
    ];
    
    $section['controls']['selector_group']['selector'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $this->chartDataService->buildTermOptionsArray($top_terms),
      '#default_value' => array_slice(array_column($top_terms, 'tid'), 0, 10),
      '#attributes' => [
        'class' => ['term-selector'],
        'id' => 'term-selector',
        'size' => 8,
      ],
    ];
    
    $section['controls']['buttons'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['control-buttons']],
    ];
    
    $section['controls']['buttons']['reset'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => 'Reset to Top 10',
      '#attributes' => [
        'id' => 'reset-chart',
        'class' => ['btn', 'btn-secondary'],
        'type' => 'button',
      ],
    ];
    
    $section['controls']['buttons']['clear'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => 'Clear All',
      '#attributes' => [
        'id' => 'clear-chart',
        'class' => ['btn', 'btn-outline'],
        'type' => 'button',
      ],
    ];
    
    $section['controls']['info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-info']],
    ];
    
    $section['controls']['info']['text'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => '📊 Showing frequency of topic mentions over the last 30 days',
      '#attributes' => ['class' => ['info-text']],
    ];
    
    // Chart container
    $section['chart_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-container']],
    ];
    
    $section['chart_wrapper']['canvas'] = [
      '#type' => 'html_tag',
      '#tag' => 'canvas',
      '#attributes' => [
        'id' => 'taxonomy-timeline-chart',
        'width' => 800,
        'height' => 400,
        'style' => 'max-width: 100%; height: auto;',
        'aria-label' => 'Taxonomy Timeline Chart',
      ],
    ];
    
    $section['chart_wrapper']['debug'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'chart-debug-info'],
    ];
    
    $section['chart_wrapper']['debug']['status'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => 'Initializing chart...',
      '#attributes' => ['id' => 'chart-status'],
    ];
    
    return $section;
  }

  /**
   * {@inheritdoc}
   */
  public function buildMetricsOverview(array $metrics_data): array {
    $overview = [
      '#type' => 'container',
      '#attributes' => ['class' => ['metrics-overview-sections']],
    ];
    
    // Build individual metric sections
    $overview['content_analysis'] = $this->buildContentAnalysisSection($metrics_data);
    $overview['temporal'] = $this->buildTemporalSection($metrics_data);
    $overview['sentiment'] = $this->buildSentimentSection($metrics_data);
    $overview['entities'] = $this->buildEntitiesSection($metrics_data);
    $overview['activity'] = $this->buildActivitySection($metrics_data);
    $overview['insights'] = $this->buildInsightsSection($metrics_data);
    
    return $overview;
  }

  /**
   * {@inheritdoc}
   */
  public function buildMethodologySection(): array {
    return [
      '#type' => 'details',
      '#title' => 'ℹ️ About The Truth Perspective Analytics',
      '#open' => FALSE,
      '#attributes' => ['class' => ['methodology-explanation']],
      'content' => [
        '#markup' => $this->getMethodologyContent(),
      ],
    ];
  }

  /**
   * Build dashboard header section.
   *
   * @return array
   *   Render array for header.
   */
  protected function buildDashboardHeader(): array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['news-metrics-header']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h1',
        '#value' => 'The Truth Perspective Analytics',
      ],
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => 'Real-time insights into news analysis, AI-powered content evaluation, and narrative tracking across media sources.',
      ],
      'subtitle' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['metrics-subtitle']],
        'badge' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => 'Live Data',
          '#attributes' => ['class' => ['badge']],
        ],
        'updated' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => 'Updated: ' . date('F j, Y g:i A'),
          '#attributes' => ['class' => ['updated']],
        ],
      ],
    ];
  }

  /**
   * Build Content Analysis Overview section.
   */
  protected function buildContentAnalysisSection($metrics_data): array {
    $metrics = $metrics_data['metrics'];
    
    $overview_data = [
      ['Metric', 'Count', 'Coverage'],
      ['Total Articles Analyzed', number_format($metrics['total_articles']), '100%'],
      ['AI Analysis Complete', number_format($metrics['articles_with_ai']), round(($metrics['articles_with_ai'] / max($metrics['total_articles'], 1)) * 100, 1) . '%'],
      ['Content Extraction Success', number_format($metrics['articles_with_json']), round(($metrics['articles_with_json'] / max($metrics['total_articles'], 1)) * 100, 1) . '%'],
      ['Taxonomy Classification', number_format($metrics['articles_with_tags']), round(($metrics['articles_with_tags'] / max($metrics['total_articles'], 1)) * 100, 1) . '%'],
      ['Motivation Analysis', number_format($metrics['articles_with_motivation']), round(($metrics['articles_with_motivation'] / max($metrics['total_articles'], 1)) * 100, 1) . '%'],
      ['Media Assets Captured', number_format($metrics['articles_with_images']), round(($metrics['articles_with_images'] / max($metrics['total_articles'], 1)) * 100, 1) . '%'],
    ];
    
    return [
      '#type' => 'details',
      '#title' => '📊 Content Analysis Overview',
      '#open' => TRUE,
      '#attributes' => ['class' => ['metrics-overview']],
      'table' => [
        '#type' => 'table',
        '#header' => $overview_data[0],
        '#rows' => array_slice($overview_data, 1),
        '#attributes' => ['class' => ['metrics-table']],
      ],
    ];
  }

  /**
   * Build other metric sections with similar patterns.
   */
  protected function buildTemporalSection($metrics_data): array {
    $temporal_metrics = $metrics_data['temporal_metrics'];
    
    $temporal_data = [
      ['Time Metric', 'Value', 'Context'],
      ['Articles Processed (24h)', number_format($temporal_metrics['articles_last_24_hours']), 'Real-time processing volume'],
      ['Peak Processing Hour', $temporal_metrics['peak_processing_hour'], 'Busiest analysis period'],
      ['Average Processing Time', round($temporal_metrics['avg_processing_time'], 2) . ' minutes', 'From URL to full analysis'],
    ];
    
    return [
      '#type' => 'details',
      '#title' => '⏱️ Temporal Processing Analytics',
      '#open' => TRUE,
      'table' => [
        '#type' => 'table',
        '#header' => $temporal_data[0],
        '#rows' => array_slice($temporal_data, 1),
        '#attributes' => ['class' => ['temporal-table']],
      ],
    ];
  }

  /**
   * Build sentiment section.
   */
  protected function buildSentimentSection($metrics_data): array {
    $metrics = $metrics_data['metrics'];
    $sentiment_metrics = $metrics_data['sentiment_metrics'];
    
    $sentiment_data = [
      ['Sentiment Category', 'Percentage', 'Article Count'],
      ['Positive Sentiment', round($sentiment_metrics['positive_sentiment_percentage'], 1) . '%', number_format($metrics['total_articles'] * ($sentiment_metrics['positive_sentiment_percentage'] / 100))],
      ['Negative Sentiment', round($sentiment_metrics['negative_sentiment_percentage'], 1) . '%', number_format($metrics['total_articles'] * ($sentiment_metrics['negative_sentiment_percentage'] / 100))],
      ['Neutral Sentiment', round($sentiment_metrics['neutral_sentiment_percentage'], 1) . '%', number_format($metrics['total_articles'] * ($sentiment_metrics['neutral_sentiment_percentage'] / 100))],
    ];
    
    return [
      '#type' => 'details',
      '#title' => '💭 Sentiment Distribution Analysis',
      '#open' => TRUE,
      'table' => [
        '#type' => 'table',
        '#header' => $sentiment_data[0],
        '#rows' => array_slice($sentiment_data, 1),
        '#attributes' => ['class' => ['sentiment-table']],
      ],
    ];
  }

  /**
   * Build entities section.
   */
  protected function buildEntitiesSection($metrics_data): array {
    $entity_metrics = $metrics_data['entity_metrics'];
    
    $entity_data = [
      ['Entity Type', 'Unique Count'],
      ['People Identified', number_format($entity_metrics['unique_people_identified'])],
      ['Organizations Tracked', number_format($entity_metrics['unique_organizations_identified'])],
      ['Locations Mapped', number_format($entity_metrics['unique_locations_identified'])],
    ];
    
    return [
      '#type' => 'details',
      '#title' => '🏷️ Entity Recognition Metrics',
      '#open' => TRUE,
      'table' => [
        '#type' => 'table',
        '#header' => $entity_data[0],
        '#rows' => array_slice($entity_data, 1),
        '#attributes' => ['class' => ['entities-table']],
      ],
    ];
  }

  /**
   * Build activity section.
   */
  protected function buildActivitySection($metrics_data): array {
    $metrics = $metrics_data['metrics'];
    
    $activity_data = [
      ['Period', 'Articles', 'Daily Average'],
      ['Last 7 Days', number_format($metrics['articles_last_7_days']), round($metrics['articles_last_7_days'] / 7, 1)],
      ['Last 30 Days', number_format($metrics['articles_last_30_days']), round($metrics['articles_last_30_days'] / 30, 1)],
      ['Total Classification Tags', number_format($metrics['total_tags']), ''],
    ];
    
    return [
      '#type' => 'details',
      '#title' => '⚡ Recent Activity',
      '#open' => TRUE,
      'table' => [
        '#type' => 'table',
        '#header' => $activity_data[0],
        '#rows' => array_slice($activity_data, 1),
        '#attributes' => ['class' => ['activity-table']],
      ],
    ];
  }

  /**
   * Build insights section.
   */
  protected function buildInsightsSection($metrics_data): array {
    $insights = $metrics_data['insights'];
    
    $insights_data = [
      ['Quality Indicator', 'Average Value'],
      ['AI Response Depth', number_format($insights['avg_ai_response_length']) . ' characters'],
      ['Motivation Analysis Detail', number_format($insights['avg_motivation_length']) . ' characters'],
      ['Classification Density', $insights['avg_tags_per_article'] . ' tags per article'],
    ];
    
    return [
      '#type' => 'details',
      '#title' => '🔍 Analysis Quality Metrics',
      '#open' => TRUE,
      'table' => [
        '#type' => 'table',
        '#header' => $insights_data[0],
        '#rows' => array_slice($insights_data, 1),
        '#attributes' => ['class' => ['insights-table']],
      ],
    ];
  }

  /**
   * Get methodology content for explanation section.
   */
  protected function getMethodologyContent(): string {
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

}
