<?php

namespace Drupal\newsmotivationmetrics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Chart debug controller for The Truth Perspective analytics development.
 * 
 * Provides comprehensive debugging tools for Chart.js integration,
 * data validation, and timeline visualization testing.
 */
class ChartDebugController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a ChartDebugController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Display the chart debug interface for The Truth Perspective analytics.
   * 
   * @return array
   *   Render array for the debug page with comprehensive chart testing tools.
   */
  public function debugPage() {
    // Get timeline data for charts
    $timeline_data = $this->getTimelineData();
    $top_terms = $this->getTopTerms();
    
    // Debug information
    $debug_info = [
      'dataPoints' => count($timeline_data),
      'termCount' => count($top_terms),
      'timestamp' => time(),
    ];

    // Prepare JavaScript settings for Chart.js integration
    $js_settings = [
      'timelineData' => $timeline_data,
      'topTerms' => $top_terms,
      'debugMode' => TRUE,
      'debugInfo' => $debug_info,
    ];

    return [
      '#theme' => 'chart_debug',
      '#timelineData' => $timeline_data,
      '#topTerms' => $top_terms,
      '#debugInfo' => $debug_info,
      '#attached' => [
        'library' => [
          'newsmotivationmetrics/chart-debug',
        ],
        'drupalSettings' => [
          'newsmotivationmetrics' => $js_settings,
        ],
      ],
      '#cache' => [
        'max-age' => 300, // 5 minutes cache for debug data
        'contexts' => ['url'],
      ],
    ];
  }

  /**
   * Get timeline data for chart visualization.
   * 
   * @return array
   *   Array of timeline data with term information and daily counts.
   */
  protected function getTimelineData() {
    try {
      // Get date range (last 90 days)
      $end_date = date('Y-m-d');
      $start_date = date('Y-m-d', strtotime('-90 days'));

      // Query for timeline data with proper term information
      $query = $this->database->select('taxonomy_term_field_data', 't');
      $query->fields('t', ['tid', 'name']);
      $query->condition('t.vid', 'motivation_detection');
      $query->orderBy('t.name');
      
      $terms = $query->execute()->fetchAll();
      
      $timeline_data = [];
      
      foreach ($terms as $term) {
        // Get daily counts for this term
        $count_query = $this->database->select('node__field_ai_detected_motivations', 'm');
        $count_query->addExpression('DATE(FROM_UNIXTIME(n.created))', 'date');
        $count_query->addExpression('COUNT(*)', 'count');
        $count_query->join('node_field_data', 'n', 'm.entity_id = n.nid');
        $count_query->condition('m.field_ai_detected_motivations_target_id', $term->tid);
        $count_query->condition('n.status', 1);
        $count_query->condition('n.type', 'article');
        $count_query->where('DATE(FROM_UNIXTIME(n.created)) BETWEEN :start_date AND :end_date', [
          ':start_date' => $start_date,
          ':end_date' => $end_date,
        ]);
        $count_query->groupBy('DATE(FROM_UNIXTIME(n.created))');
        $count_query->orderBy('date');

        $counts_result = $count_query->execute()->fetchAllKeyed();
        
        // Generate complete date range with zero-filled gaps
        $data_points = [];
        $current_date = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        while ($current_date <= $end_timestamp) {
          $date_string = date('Y-m-d', $current_date);
          $count = isset($counts_result[$date_string]) ? (int)$counts_result[$date_string] : 0;
          
          $data_points[] = [
            'date' => $date_string,
            'count' => $count,
          ];
          
          $current_date = strtotime('+1 day', $current_date);
        }
        
        $timeline_data[] = [
          'term_id' => $term->tid,
          'term_name' => $term->name,
          'data' => $data_points,
        ];
      }

      return $timeline_data;

    } catch (\Exception $e) {
      \Drupal::logger('newsmotivationmetrics')->error('Error getting timeline data for debug: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Get top terms with usage counts for debug display.
   * 
   * @return array
   *   Array of top terms with usage statistics.
   */
  protected function getTopTerms() {
    try {
      // Get terms with usage counts from AI detected motivations
      $query = $this->database->select('taxonomy_term_field_data', 't');
      $query->fields('t', ['tid', 'name']);
      $query->addExpression('COUNT(m.field_ai_detected_motivations_target_id)', 'usage_count');
      $query->leftJoin('node__field_ai_detected_motivations', 'm', 't.tid = m.field_ai_detected_motivations_target_id');
      $query->leftJoin('node_field_data', 'n', 'm.entity_id = n.nid AND n.status = 1 AND n.type = :article_type', [':article_type' => 'article']);
      $query->condition('t.vid', 'motivation_detection');
      $query->groupBy('t.tid');
      $query->groupBy('t.name');
      $query->orderBy('usage_count', 'DESC');
      $query->orderBy('t.name');
      $query->range(0, 20); // Top 20 terms
      
      $results = $query->execute()->fetchAll();
      
      $top_terms = [];
      foreach ($results as $result) {
        $top_terms[] = [
          'tid' => $result->tid,
          'name' => $result->name,
          'usage_count' => $result->usage_count,
        ];
      }

      return $top_terms;

    } catch (\Exception $e) {
      \Drupal::logger('newsmotivationmetrics')->error('Error getting top terms for debug: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * AJAX endpoint for refreshing debug data.
   * 
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with updated debug data.
   */
  public function refreshData() {
    $timeline_data = $this->getTimelineData();
    $top_terms = $this->getTopTerms();
    
    $debug_info = [
      'dataPoints' => count($timeline_data),
      'termCount' => count($top_terms),
      'timestamp' => time(),
    ];

    return new JsonResponse([
      'timelineData' => $timeline_data,
      'topTerms' => $top_terms,
      'debugInfo' => $debug_info,
      'status' => 'success',
    ]);
  }

  /**
   * Test Chart.js library loading and functionality.
   * 
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with library test results.
   */
  public function testLibraries() {
    // This endpoint can be used for server-side library verification
    // Currently focuses on data availability testing
    
    $timeline_data = $this->getTimelineData();
    $top_terms = $this->getTopTerms();
    
    $test_results = [
      'dataAvailable' => !empty($timeline_data),
      'termsAvailable' => !empty($top_terms),
      'dataPointCount' => count($timeline_data),
      'termCount' => count($top_terms),
      'timestamp' => time(),
    ];
    
    // Check for data quality
    $terms_with_data = 0;
    foreach ($timeline_data as $term_data) {
      if (!empty($term_data['data'])) {
        foreach ($term_data['data'] as $point) {
          if ($point['count'] > 0) {
            $terms_with_data++;
            break;
          }
        }
      }
    }
    
    $test_results['termsWithData'] = $terms_with_data;
    $test_results['dataQuality'] = $terms_with_data > 0 ? 'good' : 'no_data';
    
    return new JsonResponse($test_results);
  }

}