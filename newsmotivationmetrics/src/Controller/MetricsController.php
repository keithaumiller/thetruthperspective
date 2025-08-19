<?php

namespace Drupal\newsmotivationmetrics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\newsmotivationmetrics\Service\Interface\DashboardBuilderServiceInterface;
use Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;
use Drupal\newsmotivationmetrics\Service\BiasTimelineChartService;
use Drupal\newsmotivationmetrics\Service\CredibilityTimelineChartService;
use Drupal\newsmotivationmetrics\Service\SentimentTimelineChartService;
use Drupal\newsmotivationmetrics\Service\AuthoritarianismTimelineChartService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for News Motivation Metrics pages.
 * 
 * Provides public analytics dashboard and admin interfaces for The Truth
 * Perspective news analysis system. Uses dependency injection and services
 * following Drupal 11 best practices.
 */
class MetricsController extends ControllerBase {

  /**
   * The dashboard builder service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\DashboardBuilderServiceInterface
   */
  protected $dashboardBuilder;

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
   * The bias timeline chart service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\BiasTimelineChartService
   */
  protected $biasTimelineChartService;

  /**
   * The credibility timeline chart service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\CredibilityTimelineChartService
   */
  protected $credibilityTimelineChartService;

  /**
   * The sentiment timeline chart service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\SentimentTimelineChartService
   */
  protected $sentimentTimelineChartService;

  /**
   * The authoritarianism timeline chart service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\AuthoritarianismTimelineChartService
   */
  protected $authoritarianismTimelineChartService;

  /**
   * Constructor.
   *
   * @param \Drupal\newsmotivationmetrics\Service\Interface\DashboardBuilderServiceInterface $dashboard_builder
   *   The dashboard builder service.
   * @param \Drupal\newsmotivationmetrics\Service\Interface\ChartDataServiceInterface $chart_data_service
   *   The chart data service.
   * @param \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface $metrics_data_service
   *   The metrics data service.
   * @param \Drupal\newsmotivationmetrics\Service\BiasTimelineChartService $bias_timeline_chart_service
   *   The bias timeline chart service.
   * @param \Drupal\newsmotivationmetrics\Service\CredibilityTimelineChartService $credibility_timeline_chart_service
   *   The credibility timeline chart service.
   * @param \Drupal\newsmotivationmetrics\Service\SentimentTimelineChartService $sentiment_timeline_chart_service
   *   The sentiment timeline chart service.
   * @param \Drupal\newsmotivationmetrics\Service\AuthoritarianismTimelineChartService $authoritarianism_timeline_chart_service
   *   The authoritarianism timeline chart service.
   */
  public function __construct(
    DashboardBuilderServiceInterface $dashboard_builder,
    ChartDataServiceInterface $chart_data_service,
    MetricsDataServiceInterface $metrics_data_service,
    BiasTimelineChartService $bias_timeline_chart_service,
    CredibilityTimelineChartService $credibility_timeline_chart_service,
    SentimentTimelineChartService $sentiment_timeline_chart_service,
    AuthoritarianismTimelineChartService $authoritarianism_timeline_chart_service
  ) {
    $this->dashboardBuilder = $dashboard_builder;
    $this->chartDataService = $chart_data_service;
    $this->metricsDataService = $metrics_data_service;
    $this->biasTimelineChartService = $bias_timeline_chart_service;
    $this->credibilityTimelineChartService = $credibility_timeline_chart_service;
    $this->sentimentTimelineChartService = $sentiment_timeline_chart_service;
    $this->authoritarianismTimelineChartService = $authoritarianism_timeline_chart_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('newsmotivationmetrics.dashboard_builder'),
      $container->get('newsmotivationmetrics.chart_data_service'),
      $container->get('newsmotivationmetrics.metrics_data_service'),
      $container->get('newsmotivationmetrics.bias_timeline_chart_service'),
      $container->get('newsmotivationmetrics.credibility_timeline_chart_service'),
      $container->get('newsmotivationmetrics.sentiment_timeline_chart_service'),
      $container->get('newsmotivationmetrics.authoritarianism_timeline_chart_service')
    );
  }

  /**
   * Display the metrics dashboard.
   * 
   * DEPRECATED: This method is no longer used. Dashboard functionality 
   * has been moved to individual blocks placed in the hero region of 
   * the front page. Keeping for reference during transition period.
   */
  /*
  public function dashboard() {
    $block_manager = $this->blockManager;
    
    // Create header block
    $header_block = $block_manager->createInstance('metrics_header', []);
    $header_content = $header_block->build();
    
    // Create content analysis overview block
    $content_analysis_block = $block_manager->createInstance('content_analysis_overview', []);
    $content_analysis_content = $content_analysis_block->build();
    
    // Create temporal processing analytics block
    $temporal_block = $block_manager->createInstance('temporal_processing_analytics', []);
    $temporal_content = $temporal_block->build();
    
    // Create sentiment distribution analysis block
    $sentiment_block = $block_manager->createInstance('sentiment_distribution_analysis', []);
    $sentiment_content = $sentiment_block->build();
    
    // Create timeline chart block (using existing working implementation)
    $timeline_block = $block_manager->createInstance('news_motivation_timeline_chart', []);
    $timeline_content = $timeline_block->build();
    
    // Create entity recognition metrics block
    $entity_block = $block_manager->createInstance('entity_recognition_metrics', []);
    $entity_content = $entity_block->build();
    
    // Create recent activity metrics block
    $activity_block = $block_manager->createInstance('recent_activity_metrics', []);
    $activity_content = $activity_block->build();
    
    // Create analysis quality metrics block
    $quality_block = $block_manager->createInstance('analysis_quality_metrics', []);
    $quality_content = $quality_block->build();
    
    // Create about truth perspective analytics block
    $about_block = $block_manager->createInstance('about_truth_perspective_analytics', []);
    $about_content = $about_block->build();
    
    // Return all blocks in organized layout
    return [
      '#theme' => 'container',
      '#attributes' => ['class' => ['metrics-dashboard', 'fullwidth-content']],
      'header' => $header_content,
      'content_analysis' => $content_analysis_content,
      'temporal_analytics' => $temporal_content,
      'sentiment_analysis' => $sentiment_content,
      'timeline_chart' => $timeline_content,
      'entity_metrics' => $entity_content,
      'activity_metrics' => $activity_content,
      'quality_metrics' => $quality_content,
      'about_info' => $about_content,
      '#attached' => [
        'library' => [
          'newsmotivationmetrics/chart-behavior',
          'newsmotivationmetrics/chart-styles',
        ],
      ],
    ];
  }
  */

  /**
   * Display the admin version of the dashboard.
   * 
   * @return array
   *   Drupal render array for admin dashboard.
   */
  public function adminDashboard() {
    return $this->dashboardBuilder->buildAdminDashboard();
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
      '#markup' => '<p>' . Link::createFromRoute('← Back to Front Page', '<front>')->toString() . '</p>',
    ];
    
    return $build;
  }

  /**
   * Display the bias timeline chart page.
   * 
   * @return array
   *   Drupal render array for bias timeline chart page.
   */
  public function biasChart() {
    return $this->biasTimelineChartService->buildBiasTimelineChart([
      'canvas_id' => 'bias-timeline-chart',
      'title' => 'News Source Bias Trends Over Time',
      'show_controls' => TRUE,
      'show_legend' => TRUE,
      'show_title' => TRUE,
      'chart_height' => 400,
      'days_back' => 90,
      'source_limit' => 5,
      'container_classes' => ['timeline-chart-container'],
      'library' => 'newsmotivationmetrics/bias-timeline',
      'js_behavior' => 'biasTimelineChart',
    ]);
  }

  /**
   * Display the credibility timeline chart page.
   * 
   * @return array
   *   Drupal render array for credibility timeline chart page.
   */
  public function credibilityChart() {
    return $this->credibilityTimelineChartService->buildCredibilityTimelineChart([
      'canvas_id' => 'credibility-timeline-chart',
      'title' => 'News Source Credibility Trends Over Time',
      'show_controls' => TRUE,
      'show_legend' => TRUE,
      'show_title' => TRUE,
      'chart_height' => 400,
      'days_back' => 90,
      'source_limit' => 5,
      'container_classes' => ['timeline-chart-container'],
      'library' => 'newsmotivationmetrics/credibility-timeline',
      'js_behavior' => 'credibilityTimelineChart',
    ]);
  }

  /**
   * Display the sentiment timeline chart page.
   * 
   * @return array
   *   Drupal render array for sentiment timeline chart page.
   */
  public function sentimentChart() {
    return $this->sentimentTimelineChartService->buildSentimentTimelineChart([
      'canvas_id' => 'sentiment-timeline-chart',
      'title' => 'News Source Sentiment Trends Over Time',
      'show_controls' => TRUE,
      'show_legend' => TRUE,
      'show_title' => TRUE,
      'chart_height' => 400,
      'days_back' => 90,
      'source_limit' => 5,
      'container_classes' => ['timeline-chart-container'],
      'library' => 'newsmotivationmetrics/sentiment-timeline',
      'js_behavior' => 'sentimentTimelineChart',
    ]);
  }

  /**
   * Display the authoritarianism timeline chart page.
   * 
   * @return array
   *   Drupal render array for authoritarianism timeline chart page.
   */
  public function authoritarianismChart() {
    return $this->authoritarianismTimelineChartService->buildAuthoritarianismTimelineChart([
      'canvas_id' => 'authoritarianism-timeline-chart',
      'title' => 'News Source Authoritarianism Trends Over Time',
      'show_controls' => TRUE,
      'show_legend' => TRUE,
      'show_title' => TRUE,
      'chart_height' => 400,
      'days_back' => 90,
      'source_limit' => 5,
      'container_classes' => ['timeline-chart-container'],
      'library' => 'newsmotivationmetrics/authoritarianism-timeline',
      'js_behavior' => 'authoritarianismTimelineChart',
    ]);
  }

}