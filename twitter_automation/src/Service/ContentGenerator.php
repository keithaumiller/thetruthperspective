<?php

namespace Drupal\twitter_automation\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;

/**
 * Content generator service for creating Twitter posts.
 * 
 * Generates engaging content from analytics data and news insights.
 */
class ContentGenerator {

  /**
   * The metrics data service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface
   */
  protected $metricsDataService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor.
   */
  public function __construct(
    MetricsDataServiceInterface $metrics_data_service,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->metricsDataService = $metrics_data_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('twitter_automation');
  }

  /**
   * Generate analytics summary content for Twitter.
   *
   * @return string
   *   Generated tweet content.
   */
  public function generateAnalyticsSummary(): string {
    try {
      // Get recent metrics data
      $daily_articles = $this->metricsDataService->getDailyArticlesBySource();
      $total_articles = 0;
      $sources_count = 0;
      
      if (!empty($daily_articles)) {
        $recent_day = array_slice($daily_articles, -1, 1, true);
        if (!empty($recent_day)) {
          $day_data = reset($recent_day);
          $date = key($recent_day);
          $sources_count = count($day_data['sources'] ?? []);
          foreach ($day_data['sources'] as $source_data) {
            $total_articles += $source_data['total'] ?? 0;
          }
        }
      }

      $templates = [
        "📊 Latest AI analysis: {$total_articles} articles from {$sources_count} sources processed. Our algorithms detected patterns in narrative construction, bias indicators, and motivational drivers across the media landscape.",
        
        "🔍 Today's insights: Analyzed {$total_articles} news articles revealing trends in how stories are framed and motivated. See the data transparency in action at thetruthperspective.org",
        
        "🤖 AI-powered media analysis: {$total_articles} articles processed from {$sources_count} major sources. Tracking narrative patterns, bias detection, and thematic evolution in real-time.",
        
        "📈 Data update: Our system processed {$total_articles} articles today, identifying motivation patterns and narrative structures across {$sources_count} news sources. Transparency through technology.",
      ];

      $content = $templates[array_rand($templates)];
      
      // Add URL and hashtags
      $content .= "\n\n🔗 thetruthperspective.org\n\n#AIAnalysis #MediaBias #NewsAnalysis #DataTransparency";
      
      return $content;

    } catch (\Exception $e) {
      $this->logger->error('Failed to generate analytics summary: @message', ['@message' => $e->getMessage()]);
      return $this->getFallbackContent();
    }
  }

  /**
   * Generate trending topics content for Twitter.
   *
   * @return string
   *   Generated tweet content.
   */
  public function generateTrendingTopics(): string {
    try {
      // Get top taxonomy terms from recent articles
      $node_storage = $this->entityTypeManager->getStorage('node');
      $query = $node_storage->getQuery()
        ->condition('type', 'article')
        ->condition('status', 1)
        ->condition('created', strtotime('-7 days'), '>')
        ->sort('created', 'DESC')
        ->range(0, 50)
        ->accessCheck(FALSE);
        
      $nids = $query->execute();
      
      if (empty($nids)) {
        return $this->getFallbackContent();
      }

      $nodes = $node_storage->loadMultiple($nids);
      $term_counts = [];
      
      foreach ($nodes as $node) {
        if ($node->hasField('field_tags') && !$node->get('field_tags')->isEmpty()) {
          foreach ($node->get('field_tags')->referencedEntities() as $term) {
            $term_name = $term->getName();
            if (strlen($term_name) <= 25) { // Keep it short for Twitter
              $term_counts[$term_name] = ($term_counts[$term_name] ?? 0) + 1;
            }
          }
        }
      }

      arsort($term_counts);
      $top_terms = array_slice(array_keys($term_counts), 0, 3);

      if (empty($top_terms)) {
        return $this->getFallbackContent();
      }

      $trending_list = implode(', ', $top_terms);
      
      $templates = [
        "🔥 Trending in our AI analysis: {$trending_list}. These themes are dominating news narratives this week. What patterns do you see?",
        
        "📱 This week's hot topics: {$trending_list}. Our algorithms detected these as key narrative drivers across media sources.",
        
        "⚡ Emerging patterns: {$trending_list} are trending themes in our news analysis. See how different sources frame these topics:",
      ];

      $content = $templates[array_rand($templates)];
      $content .= "\n\n🔗 thetruthperspective.org\n\n#TrendingTopics #NewsAnalysis #AI #MediaPatterns";
      
      return $content;

    } catch (\Exception $e) {
      $this->logger->error('Failed to generate trending topics: @message', ['@message' => $e->getMessage()]);
      return $this->getFallbackContent();
    }
  }

  /**
   * Generate insight about AI bias detection.
   *
   * @return string
   *   Generated tweet content.
   */
  public function generateBiasInsight(): string {
    $insights = [
      "🤖 AI Bias Alert: Our analysis shows how LLMs can misinterpret satirical content as legitimate news. The Onion gets similar 'credibility scores' as CNN - highlighting the dangers of algorithmic information ranking.",
      
      "⚠️ Black Box Problem: AI systems that rank and rate news operate without transparency. Those who control the algorithms effectively control how information is perceived. We're exposing these limitations.",
      
      "🔍 Transparency Experiment: We deliberately show how AI misjudges news sources to demonstrate the hidden biases in automated content evaluation. Question the algorithms, not just the content.",
      
      "📊 AI Reality Check: Our system assigns similar bias scores to satire and journalism, proving that AI-mediated information control is more dangerous than we think. Technology isn't neutral.",
    ];

    $content = $insights[array_rand($insights)];
    $content .= "\n\n🔗 thetruthperspective.org\n\n#AIBias #MediaLiteracy #TechEthics #InformationControl";
    
    return $content;
  }

  /**
   * Get fallback content when data generation fails.
   *
   * @return string
   *   Fallback tweet content.
   */
  protected function getFallbackContent(): string {
    $fallbacks = [
      "🔍 The Truth Perspective: Using AI to analyze news narratives while exposing the limitations and biases of AI-driven information systems. Transparency through technology.",
      
      "📊 Our AI analyzes thousands of news articles to reveal patterns in narrative construction and bias - while simultaneously demonstrating how AI can misinterpret content.",
      
      "🤖 Question everything: including the AI systems that increasingly mediate our information consumption. See our live analysis of media patterns and AI limitations.",
    ];

    $content = $fallbacks[array_rand($fallbacks)];
    $content .= "\n\n🔗 thetruthperspective.org\n\n#AI #MediaAnalysis #Transparency #TechEthics";
    
    return $content;
  }

  /**
   * Generate content based on specified type.
   *
   * @param string $type
   *   The type of content to generate.
   *
   * @return string
   *   Generated tweet content.
   */
  public function generateContent(string $type): string {
    switch ($type) {
      case 'analytics_summary':
        return $this->generateAnalyticsSummary();
        
      case 'trending_topics':
        return $this->generateTrendingTopics();
        
      case 'bias_insight':
        return $this->generateBiasInsight();
        
      default:
        return $this->getFallbackContent();
    }
  }

}
