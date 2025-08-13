<?php

namespace Drupal\newsmotivationmetrics\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;

/**
 * Service for retrieving news metrics and analytics data.
 * 
 * Handles all database queries for metrics calculation, with proper
 * error handling and performance optimization for large datasets.
 */
class MetricsDataService implements MetricsDataServiceInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    Connection $database,
    LoggerChannelFactoryInterface $logger_factory,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->database = $database;
    $this->loggerFactory = $logger_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getArticleMetrics(): array {
    $metrics = [];
    
    try {
      // Total articles
      $query = $this->database->select('node_field_data', 'n');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $metrics['total_articles'] = $query->countQuery()->execute()->fetchField();
      
      // Articles with AI analysis
      $query = $this->database->select('node_field_data', 'n');
      $query->leftJoin('node__field_ai_raw_response', 'ai', 'n.nid = ai.entity_id');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('ai.field_ai_raw_response_value', '', '<>');
      $metrics['articles_with_ai'] = $query->countQuery()->execute()->fetchField();
      
      // Articles with JSON data
      $query = $this->database->select('node_field_data', 'n');
      $query->leftJoin('node__field_json_scraped_article_data', 'json', 'n.nid = json.entity_id');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('json.field_json_scraped_article_data_value', '', '<>');
      $metrics['articles_with_json'] = $query->countQuery()->execute()->fetchField();
      
      // Articles with tags
      $query = $this->database->select('node_field_data', 'n');
      $query->leftJoin('node__field_tags', 'tags', 'n.nid = tags.entity_id');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->isNotNull('tags.field_tags_target_id');
      $metrics['articles_with_tags'] = $query->countQuery()->execute()->fetchField();
      
      // Articles with motivation analysis
      $query = $this->database->select('node_field_data', 'n');
      $query->leftJoin('node__field_motivation_analysis', 'mot', 'n.nid = mot.entity_id');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('mot.field_motivation_analysis_value', '', '<>');
      $metrics['articles_with_motivation'] = $query->countQuery()->execute()->fetchField();
      
      // Articles with external images
      $query = $this->database->select('node_field_data', 'n');
      $query->leftJoin('node__field_external_image_url', 'img', 'n.nid = img.entity_id');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('img.field_external_image_url_uri', '', '<>');
      $metrics['articles_with_images'] = $query->countQuery()->execute()->fetchField();
      
      // Total unique tags
      $query = $this->database->select('taxonomy_term_field_data', 't');
      $query->condition('t.vid', 'tags');
      $metrics['total_tags'] = $query->countQuery()->execute()->fetchField();
      
      // Articles created in last 7 days
      $query = $this->database->select('node_field_data', 'n');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('n.created', strtotime('-7 days'), '>=');
      $metrics['articles_last_7_days'] = $query->countQuery()->execute()->fetchField();
      
      // Articles created in last 30 days
      $query = $this->database->select('node_field_data', 'n');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('n.created', strtotime('-30 days'), '>=');
      $metrics['articles_last_30_days'] = $query->countQuery()->execute()->fetchField();
      
    } catch (\Exception $e) {
      $this->loggerFactory->get('newsmotivationmetrics')->error('Error fetching article metrics: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      // Return safe defaults
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
    }
    
    return $metrics;
  }

  /**
   * {@inheritdoc}
   */
  public function getMotivationInsights(): array {
    $insights = [];
    
    try {
      // Average motivation analysis length
      $query = $this->database->select('node__field_motivation_analysis', 'mot');
      $query->addExpression('AVG(LENGTH(mot.field_motivation_analysis_value))', 'avg_length');
      $query->condition('mot.field_motivation_analysis_value', '', '<>');
      $avg_length = $query->execute()->fetchField();
      $insights['avg_motivation_length'] = round($avg_length);
      
      // Average AI response length
      $query = $this->database->select('node__field_ai_raw_response', 'ai');
      $query->addExpression('AVG(LENGTH(ai.field_ai_raw_response_value))', 'avg_length');
      $query->condition('ai.field_ai_raw_response_value', '', '<>');
      $avg_ai_length = $query->execute()->fetchField();
      $insights['avg_ai_response_length'] = round($avg_ai_length);
      
      // Average tags per article
      $query = $this->database->select('node_field_data', 'n');
      $query->leftJoin('node__field_tags', 'tags', 'n.nid = tags.entity_id');
      $query->addExpression('COUNT(tags.field_tags_target_id) / COUNT(DISTINCT n.nid)', 'avg_tags');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $avg_tags = $query->execute()->fetchField();
      $insights['avg_tags_per_article'] = round($avg_tags, 1);
      
    } catch (\Exception $e) {
      $this->loggerFactory->get('newsmotivationmetrics')->error('Error fetching motivation insights: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      $insights = [
        'avg_motivation_length' => 0,
        'avg_ai_response_length' => 0,
        'avg_tags_per_article' => 0,
      ];
    }
    
    return $insights;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemporalMetrics(): array {
    $metrics = [];
    
    try {
      // Articles processed in last 24 hours
      $query = $this->database->select('node_field_data', 'n');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('n.created', strtotime('-24 hours'), '>=');
      $metrics['articles_last_24_hours'] = $query->countQuery()->execute()->fetchField();
      
      // Peak processing hour
      $query = $this->database->select('node_field_data', 'n');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('n.created', strtotime('-30 days'), '>=');
      $query->addExpression('HOUR(FROM_UNIXTIME(created))', 'hour');
      $query->addExpression('COUNT(*)', 'count');
      $query->groupBy('hour');
      $query->orderBy('count', 'DESC');
      $query->range(0, 1);
      $result = $query->execute()->fetchAssoc();
      $metrics['peak_processing_hour'] = $result ? $result['hour'] . ':00' : 'Unknown';
      
      // Average processing time
      $query = $this->database->select('node_field_data', 'n');
      $query->leftJoin('node__field_ai_raw_response', 'ai', 'n.nid = ai.entity_id');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('n.created', strtotime('-7 days'), '>=');
      $query->condition('ai.field_ai_raw_response_value', '', '<>');
      $query->addExpression('AVG((n.changed - n.created) / 60)', 'avg_processing_minutes');
      $result = $query->execute()->fetchField();
      $metrics['avg_processing_time'] = $result ? (float) $result : 0;
      
    } catch (\Exception $e) {
      $this->loggerFactory->get('newsmotivationmetrics')->error('Error fetching temporal metrics: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      $metrics = [
        'articles_last_24_hours' => 0,
        'peak_processing_hour' => 'Unknown',
        'avg_processing_time' => 0,
      ];
    }
    
    return $metrics;
  }

  /**
   * {@inheritdoc}
   */
  public function getSentimentMetrics(): array {
    $metrics = [
      'positive_sentiment_percentage' => 0,
      'negative_sentiment_percentage' => 0,
      'neutral_sentiment_percentage' => 0,
    ];
    
    try {
      // Get total articles with AI analysis
      $query = $this->database->select('node_field_data', 'n');
      $query->leftJoin('node__field_ai_raw_response', 'ai', 'n.nid = ai.entity_id');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('ai.field_ai_raw_response_value', '', '<>');
      $total_with_ai = $query->countQuery()->execute()->fetchField();
      
      if ($total_with_ai > 0) {
        // Count positive sentiment
        $query = $this->database->select('node_field_data', 'n');
        $query->leftJoin('node__field_ai_raw_response', 'ai', 'n.nid = ai.entity_id');
        $query->condition('n.type', 'article');
        $query->condition('n.status', 1);
        $query->condition('ai.field_ai_raw_response_value', '%positive%', 'LIKE');
        $positive_count = $query->countQuery()->execute()->fetchField();
        
        // Count negative sentiment
        $query = $this->database->select('node_field_data', 'n');
        $query->leftJoin('node__field_ai_raw_response', 'ai', 'n.nid = ai.entity_id');
        $query->condition('n.type', 'article');
        $query->condition('n.status', 1);
        $query->condition('ai.field_ai_raw_response_value', '%negative%', 'LIKE');
        $negative_count = $query->countQuery()->execute()->fetchField();
        
        // Calculate percentages
        $metrics['positive_sentiment_percentage'] = round(($positive_count / $total_with_ai) * 100, 2);
        $metrics['negative_sentiment_percentage'] = round(($negative_count / $total_with_ai) * 100, 2);
        $metrics['neutral_sentiment_percentage'] = 100 - $metrics['positive_sentiment_percentage'] - $metrics['negative_sentiment_percentage'];
      }
      
    } catch (\Exception $e) {
      $this->loggerFactory->get('newsmotivationmetrics')->error('Error fetching sentiment metrics: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
    
    return $metrics;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityMetrics(): array {
    $metrics = [
      'unique_people_identified' => 0,
      'unique_organizations_identified' => 0,
      'unique_locations_identified' => 0,
    ];
    
    try {
      $query = $this->database->select('node_field_data', 'n');
      $query->leftJoin('node__field_ai_raw_response', 'ai', 'n.nid = ai.entity_id');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('ai.field_ai_raw_response_value', '', '<>');
      $total_with_ai = $query->countQuery()->execute()->fetchField();
      
      if ($total_with_ai > 0) {
        // Count people mentions
        $query = $this->database->select('node__field_ai_raw_response', 'ai');
        $query->leftJoin('node_field_data', 'n', 'ai.entity_id = n.nid');
        $query->condition('n.type', 'article');
        $query->condition('n.status', 1);
        $query->condition($this->database->condition('OR')
          ->condition('ai.field_ai_raw_response_value', '%person%', 'LIKE')
          ->condition('ai.field_ai_raw_response_value', '%people%', 'LIKE')
          ->condition('ai.field_ai_raw_response_value', '%individual%', 'LIKE')
          ->condition('ai.field_ai_raw_response_value', '%politician%', 'LIKE')
          ->condition('ai.field_ai_raw_response_value', '%official%', 'LIKE')
        );
        $people_mentions = $query->countQuery()->execute()->fetchField();
        
        // Count organization mentions
        $query = $this->database->select('node__field_ai_raw_response', 'ai');
        $query->leftJoin('node_field_data', 'n', 'ai.entity_id = n.nid');
        $query->condition('n.type', 'article');
        $query->condition('n.status', 1);
        $query->condition($this->database->condition('OR')
          ->condition('ai.field_ai_raw_response_value', '%organization%', 'LIKE')
          ->condition('ai.field_ai_raw_response_value', '%company%', 'LIKE')
          ->condition('ai.field_ai_raw_response_value', '%corporation%', 'LIKE')
          ->condition('ai.field_ai_raw_response_value', '%agency%', 'LIKE')
          ->condition('ai.field_ai_raw_response_value', '%institution%', 'LIKE')
        );
        $org_mentions = $query->countQuery()->execute()->fetchField();
        
        // Count location mentions
        $query = $this->database->select('node__field_ai_raw_response', 'ai');
        $query->leftJoin('node_field_data', 'n', 'ai.entity_id = n.nid');
        $query->condition('n.type', 'article');
        $query->condition('n.status', 1);
        $query->condition($this->database->condition('OR')
          ->condition('ai.field_ai_raw_response_value', '%location%', 'LIKE')
          ->condition('ai.field_ai_raw_response_value', '%country%', 'LIKE')
          ->condition('ai.field_ai_raw_response_value', '%state%', 'LIKE')
          ->condition('ai.field_ai_raw_response_value', '%city%', 'LIKE')
          ->condition('ai.field_ai_raw_response_value', '%region%', 'LIKE')
        );
        $location_mentions = $query->countQuery()->execute()->fetchField();
        
        // Estimate unique entities
        $metrics['unique_people_identified'] = round($people_mentions * 1.8);
        $metrics['unique_organizations_identified'] = round($org_mentions * 1.5);
        $metrics['unique_locations_identified'] = round($location_mentions * 1.3);
      }
      
    } catch (\Exception $e) {
      $this->loggerFactory->get('newsmotivationmetrics')->error('Error fetching entity metrics: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
    
    return $metrics;
  }

  /**
   * {@inheritdoc}
   */
  public function getNewsSourceMetrics(): array {
    $sources = [];
    
    try {
      $query = $this->database->select('node_field_data', 'n');
      $query->leftJoin('node__field_news_source', 'ns', 'n.nid = ns.entity_id');
      $query->fields('ns', ['field_news_source_value']);
      $query->addExpression('COUNT(n.nid)', 'article_count');
      $query->condition('n.type', 'article');
      $query->condition('n.status', 1);
      $query->condition('ns.field_news_source_value', '', '<>');
      $query->groupBy('ns.field_news_source_value');
      $query->orderBy('article_count', 'DESC');
      $query->range(0, 20);
      
      $results = $query->execute()->fetchAll();
      
      foreach ($results as $result) {
        $sources[] = [
          'source' => $result->field_news_source_value,
          'article_count' => (int) $result->article_count,
        ];
      }
      
    } catch (\Exception $e) {
      $this->loggerFactory->get('newsmotivationmetrics')->error('Error fetching news source metrics: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
    
    return $sources;
  }

  /**
   * {@inheritdoc}
   */
  public function getTagMetrics(): array {
    $tags = [];
    
    try {
      $query = $this->database->select('taxonomy_term_field_data', 't');
      $query->leftJoin('node__field_tags', 'nft', 't.tid = nft.field_tags_target_id');
      $query->leftJoin('node_field_data', 'n', 'nft.entity_id = n.nid AND n.type = :type AND n.status = 1', [':type' => 'article']);
      $query->fields('t', ['tid', 'name', 'vid']);
      $query->addExpression('COUNT(DISTINCT n.nid)', 'article_count');
      $query->condition('t.vid', 'tags');
      $query->groupBy('t.tid');
      $query->groupBy('t.name');
      $query->groupBy('t.vid');
      $query->orderBy('article_count', 'DESC');
      
      $results = $query->execute()->fetchAll();
      
      foreach ($results as $result) {
        $tags[] = [
          'tid' => $result->tid,
          'name' => $result->name,
          'article_count' => (int) $result->article_count,
        ];
      }
      
    } catch (\Exception $e) {
      $this->loggerFactory->get('newsmotivationmetrics')->error('Error fetching tag metrics: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
    
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllMetricsData(): array {
    try {
      return [
        'metrics' => $this->getArticleMetrics(),
        'insights' => $this->getMotivationInsights(),
        'temporal_metrics' => $this->getTemporalMetrics(),
        'sentiment_metrics' => $this->getSentimentMetrics(),
        'entity_metrics' => $this->getEntityMetrics(),
        'news_source_metrics' => $this->getNewsSourceMetrics(),
        'tag_metrics' => $this->getTagMetrics(),
      ];
    } catch (\Exception $e) {
      $this->loggerFactory->get('newsmotivationmetrics')->error('Failed to load all metrics data: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      // Return safe fallback structure
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
        'news_source_metrics' => [],
        'tag_metrics' => [],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNewsSourceTimelineData(array $options = []): array {
    try {
      // Set default options
      $options = $options + [
        'limit' => 10,
        'days_back' => 30,
        'source_ids' => [],
      ];

      $database = $this->database;
      $connection = $database;
      
      // Calculate date range
      $end_date = new \DateTime();
      $start_date = clone $end_date;
      $start_date->sub(new \DateInterval('P' . $options['days_back'] . 'D'));
      
      // Start with node_field_data like the working getNewsSourceMetrics method
      $query = $connection->select('node_field_data', 'n');
      $query->fields('n', ['nid', 'created', 'title']);
      
      // Join with news source field
      $query->leftJoin('node__field_news_source', 'ns', 'ns.entity_id = n.nid');
      $query->addField('ns', 'field_news_source_value', 'news_source');
      
      // Join with bias field
      $query->leftJoin('node__field_bias_rating', 'bias', 'bias.entity_id = n.nid');
      $query->addField('bias', 'field_bias_rating_value', 'bias_rating');
      
      // Join with credibility field
      $query->leftJoin('node__field_credibility_score', 'cred', 'cred.entity_id = n.nid');
      $query->addField('cred', 'field_credibility_score_value', 'credibility_score');
      
      // Join with sentiment field (using correct field name)
      $query->leftJoin('node__field_article_sentiment_score', 'sent', 'sent.entity_id = n.nid');
      $query->addField('sent', 'field_article_sentiment_score_value', 'sentiment_score');
      
      $query->condition('n.type', 'article')
        ->condition('n.status', 1)
        ->condition('n.created', $start_date->getTimestamp(), '>=')
        ->condition('n.created', $end_date->getTimestamp(), '<=')
        ->condition('ns.field_news_source_value', '', '<>') // Only articles with news source
        ->condition('ns.field_news_source_value', NULL, 'IS NOT NULL')
        ->condition('ns.field_news_source_value', 'Source Unavailable', '<>'); // Exclude unavailable sources
      
      // Filter by specific sources if provided
      if (!empty($options['source_ids'])) {
        $query->condition('ns.field_news_source_value', $options['source_ids'], 'IN');
      }
      
      $query->orderBy('n.created', 'DESC');
      
      $results = $query->execute()->fetchAll();
      
      // Debug: Log the number of results found
      $this->loggerFactory->get('newsmotivationmetrics')->info('News source timeline query found @count results', [
        '@count' => count($results),
      ]);
      
      // Process timeline data by source
      $timeline_data = [];
      $source_data = [];
      
      foreach ($results as $row) {
        $source_name = $row->news_source;
        $date = date('Y-m-d', $row->created);
        
        if (!isset($source_data[$source_name])) {
          $source_data[$source_name] = [];
        }
        
        if (!isset($source_data[$source_name][$date])) {
          $source_data[$source_name][$date] = [
            'bias_total' => 0,
            'credibility_total' => 0,
            'sentiment_total' => 0,
            'count' => 0,
          ];
        }
        
        $source_data[$source_name][$date]['bias_total'] += (float) ($row->bias_rating ?? 0);
        $source_data[$source_name][$date]['credibility_total'] += (float) ($row->credibility_score ?? 0);
        $source_data[$source_name][$date]['sentiment_total'] += (float) ($row->sentiment_score ?? 0);
        $source_data[$source_name][$date]['count']++;
      }
      
      // Generate date range for timeline
      $current_date = clone $start_date;
      $date_range = [];
      while ($current_date <= $end_date) {
        $date_range[] = $current_date->format('Y-m-d');
        $current_date->add(new \DateInterval('P1D'));
      }
      
      // Format timeline data for Chart.js
      foreach ($source_data as $source_name => $dates) {
        // Create bias rating timeline
        $bias_data = [];
        foreach ($date_range as $date) {
          if (isset($dates[$date]) && $dates[$date]['count'] > 0) {
            $bias_data[] = [
              'date' => $date,
              'value' => round($dates[$date]['bias_total'] / $dates[$date]['count'], 2),
            ];
          } else {
            $bias_data[] = [
              'date' => $date,
              'value' => 0,
            ];
          }
        }
        
        // Create credibility score timeline
        $credibility_data = [];
        foreach ($date_range as $date) {
          if (isset($dates[$date]) && $dates[$date]['count'] > 0) {
            $credibility_data[] = [
              'date' => $date,
              'value' => round($dates[$date]['credibility_total'] / $dates[$date]['count'], 2),
            ];
          } else {
            $credibility_data[] = [
              'date' => $date,
              'value' => 0,
            ];
          }
        }
        
        // Create sentiment score timeline
        $sentiment_data = [];
        foreach ($date_range as $date) {
          if (isset($dates[$date]) && $dates[$date]['count'] > 0) {
            $sentiment_data[] = [
              'date' => $date,
              'value' => round($dates[$date]['sentiment_total'] / $dates[$date]['count'], 2),
            ];
          } else {
            $sentiment_data[] = [
              'date' => $date,
              'value' => 0,
            ];
          }
        }
        
        // Add source datasets (3 lines per source: bias, credibility, sentiment)
        $timeline_data[] = [
          'source_name' => $source_name . ' - Bias Rating',
          'source_id' => $source_name,
          'metric_type' => 'bias',
          'data' => $bias_data,
        ];
        
        $timeline_data[] = [
          'source_name' => $source_name . ' - Credibility Score',
          'source_id' => $source_name,
          'metric_type' => 'credibility',
          'data' => $credibility_data,
        ];
        
        $timeline_data[] = [
          'source_name' => $source_name . ' - Sentiment Score',
          'source_id' => $source_name,
          'metric_type' => 'sentiment',
          'data' => $sentiment_data,
        ];
      }
      
      // Limit to requested number of sources (multiply by 3 for 3 metrics per source)
      $max_datasets = $options['limit'] * 3;
      if (count($timeline_data) > $max_datasets) {
        $timeline_data = array_slice($timeline_data, 0, $max_datasets);
      }
      
      return $timeline_data;
      
    } catch (\Exception $e) {
      $this->loggerFactory->get('newsmotivationmetrics')->error('Failed to load news source timeline data: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTopNewsSources(int $limit = 10): array {
    try {
      $database = $this->database;
      $connection = $database;
      
      // Fix MySQL strict mode compliance - only select what we group by
      $query = $connection->select('node_field_data', 'n');
      
      // Join with news source field
      $query->leftJoin('node__field_news_source', 'ns', 'ns.entity_id = n.nid');
      $query->addField('ns', 'field_news_source_value', 'news_source');
      
      // Add aggregation for count
      $query->addExpression('COUNT(n.nid)', 'article_count');
      
      $query->condition('n.type', 'article')
        ->condition('n.status', 1)
        ->condition('ns.field_news_source_value', '', '<>')
        ->condition('ns.field_news_source_value', NULL, 'IS NOT NULL')
        ->condition('ns.field_news_source_value', 'Source Unavailable', '<>')
        ->groupBy('ns.field_news_source_value')
        ->orderBy('article_count', 'DESC')
        ->range(0, $limit);
      
      $results = $query->execute()->fetchAll();
      
      $sources = [];
      foreach ($results as $row) {
        $sources[] = [
          'source_id' => $row->news_source,
          'source_name' => $row->news_source,
          'article_count' => (int) $row->article_count,
        ];
      }
      
      return $sources;
      
    } catch (\Exception $e) {
      $this->loggerFactory->get('newsmotivationmetrics')->error('Failed to load top news sources: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      return [];
    }
  }

}
