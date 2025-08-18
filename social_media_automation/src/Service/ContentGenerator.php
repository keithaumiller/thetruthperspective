<?php

namespace Drupal\social_media_automation\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\newsmotivationmetrics\Service\Interface\MetricsDataServiceInterface;

/**
 * Content generator service for creating social media posts.
 * 
 * Generates engaging content from analytics data and news insights,
 * adapting it for different social media platforms.
 */
class ContentGenerator {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The platform manager.
   *
   * @var \Drupal\social_media_automation\Service\PlatformManager
   */
  protected $platformManager;

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
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    PlatformManager $platform_manager,
    MetricsDataServiceInterface $metrics_data_service = NULL,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('social_media_automation');
    $this->platformManager = $platform_manager;
    $this->metricsDataService = $metrics_data_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Generate analytics summary content.
   *
   * @return string
   *   Generated base content.
   */
  public function generateAnalyticsSummary(): string {
    try {
      // Check if metrics service is available
      if (!$this->metricsDataService) {
        $this->logger->warning('Metrics data service not available, using fallback content');
        return $this->getFallbackContent();
      }
      
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

      return $templates[array_rand($templates)];

    } catch (\Exception $e) {
      $this->logger->error('Failed to generate analytics summary: @message', ['@message' => $e->getMessage()]);
      return $this->getFallbackContent();
    }
  }

  /**
   * Generate trending topics content.
   *
   * @return string
   *   Generated base content.
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
            if (strlen($term_name) <= 25) { // Keep it reasonable for all platforms
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
        
        "⚡ Emerging patterns: {$trending_list} are trending themes in our news analysis. See how different sources frame these topics.",
      ];

      return $templates[array_rand($templates)];

    } catch (\Exception $e) {
      $this->logger->error('Failed to generate trending topics: @message', ['@message' => $e->getMessage()]);
      return $this->getFallbackContent();
    }
  }

  /**
   * Generate content featuring a random recent article.
   *
   * @return string
   *   Generated base content.
   */
  public function generateRecentArticle(): string {
    try {
      // Get random article from last 48 hours
      $node_storage = $this->entityTypeManager->getStorage('node');
      
      // Calculate 48 hours ago timestamp
      $forty_eight_hours_ago = time() - (48 * 60 * 60);
      
      $query = $node_storage->getQuery()
        ->condition('type', 'article')
        ->condition('status', 1)
        ->condition('created', $forty_eight_hours_ago, '>')
        ->accessCheck(FALSE);
        
      $nids = $query->execute();
      
      if (empty($nids)) {
        // Fallback to any recent article if none in last 48 hours
        $this->logger->info('No articles found in last 48 hours, falling back to most recent');
        $query = $node_storage->getQuery()
          ->condition('type', 'article')
          ->condition('status', 1)
          ->sort('created', 'DESC')
          ->range(0, 10)
          ->accessCheck(FALSE);
        $nids = $query->execute();
        
        if (empty($nids)) {
          return $this->getFallbackContent();
        }
      }
      
      // Pick a random article from the results
      $nids_array = array_values($nids);
      $random_nid = $nids_array[array_rand($nids_array)];
      
      $this->logger->info('Selected random article @nid from @count articles in last 48 hours', [
        '@nid' => $random_nid,
        '@count' => count($nids)
      ]);
      
      $article = $node_storage->load($random_nid);
      
      if (!$article) {
        return $this->getFallbackContent();
      }

      $title = $article->getTitle();
      $article_url = $article->toUrl('canonical', ['absolute' => TRUE])->toString();
      
      // Fix URL if it shows as default
      if (strpos($article_url, 'http://default') !== false) {
        $article_url = str_replace('http://default', 'https://thetruthperspective.org', $article_url);
      }
      
      // Get motivation analysis if available
      $motivation_insight = '';
      if ($article->hasField('field_motivation_analysis') && !$article->get('field_motivation_analysis')->isEmpty()) {
        $motivation_data = $article->get('field_motivation_analysis')->value;
        
        // Clean up HTML tags from motivation data
        $motivation_data = strip_tags($motivation_data);
        $motivation_data = html_entity_decode($motivation_data, ENT_QUOTES | ENT_HTML5);
        
        // Extract a key insight from the motivation analysis
        if (preg_match('/motivation[s]?[^.]*([^.]{50,150})/i', $motivation_data, $matches)) {
          $motivation_insight = trim($matches[1]);
        } elseif (preg_match('/([^.]{50,150}\.)/i', $motivation_data, $matches)) {
          // Fallback: get any substantial sentence
          $motivation_insight = trim($matches[1]);
        }
        
        // Further cleanup of the insight
        $motivation_insight = preg_replace('/\s+/', ' ', $motivation_insight); // normalize whitespace
        $motivation_insight = trim($motivation_insight);
      }

      $templates = [
        "🔍 Random Deep Dive: \"{$title}\" - Our AI reveals the underlying motivational patterns in this story. {$motivation_insight}",
        
        "📊 Featured Analysis: Just examined \"{$title}\" for narrative structure and bias indicators. {$motivation_insight}",
        
        "🤖 AI Spotlight: Our latest examination of \"{$title}\" exposes interesting patterns in how this story is constructed. {$motivation_insight}",
        
        "⚡ Story Breakdown: \"{$title}\" - See how AI deconstructs the motivational framework of contemporary news narratives.",
        
        "🎯 Random Pick: From our recent analysis of \"{$title}\" - discover the hidden motivational drivers shaping this narrative. {$motivation_insight}",
      ];

      $selected_template = $templates[array_rand($templates)];
      
      // Add URL if not already present
      if (strpos($selected_template, 'thetruthperspective.org') === false) {
        $selected_template .= "\n\n🔗 " . $article_url;
      }

      return $selected_template;

    } catch (\Exception $e) {
      $this->logger->error('Failed to generate random recent article content: @message', ['@message' => $e->getMessage()]);
      return $this->getFallbackContent();
    }
  }

  /**
   * Generate insight about AI bias detection.
   *
   * @return string
   *   Generated base content.
   */
  public function generateBiasInsight(): string {
    $insights = [
      "🤖 AI Bias Alert: Our analysis shows how LLMs can misinterpret satirical content as legitimate news. The Onion gets similar 'credibility scores' as CNN - highlighting the dangers of algorithmic information ranking.",
      
      "⚠️ Black Box Problem: AI systems that rank and rate news operate without transparency. Those who control the algorithms effectively control how information is perceived. We're exposing these limitations.",
      
      "🔍 Transparency Experiment: We deliberately show how AI misjudges news sources to demonstrate the hidden biases in automated content evaluation. Question the algorithms, not just the content.",
      
      "📊 AI Reality Check: Our system assigns similar bias scores to satire and journalism, proving that AI-mediated information control is more dangerous than we think. Technology isn't neutral.",
    ];

    return $insights[array_rand($insights)];
  }

  /**
   * Get fallback content when data generation fails.
   *
   * @return string
   *   Fallback content.
   */
  protected function getFallbackContent(): string {
    $fallbacks = [
      "🔍 The Truth Perspective: Using AI to analyze news narratives while exposing the limitations and biases of AI-driven information systems. Transparency through technology.",
      
      "📊 Our AI analyzes thousands of news articles to reveal patterns in narrative construction and bias - while simultaneously demonstrating how AI can misinterpret content.",
      
      "🤖 Question everything: including the AI systems that increasingly mediate our information consumption. See our live analysis of media patterns and AI limitations.",
    ];

    return $fallbacks[array_rand($fallbacks)];
  }

  /**
   * Generate content adapted for all enabled platforms.
   *
   * @param string $type
   *   The type of content to generate.
   * @param array $context
   *   Additional context for content generation.
   *
   * @return array
   *   Array of platform-specific content, keyed by platform machine name.
   */
  public function generateContent(string $type, array $context = []): array {
    // Generate base content
    $base_content = $this->generateBaseContent($type);
    
    if (empty($base_content)) {
      $this->logger->error('Failed to generate base content for type: @type', ['@type' => $type]);
      return [];
    }

    // Get enabled platforms
    $enabled_platforms = $this->platformManager->getEnabledPlatforms();
    
    if (empty($enabled_platforms)) {
      $this->logger->warning('No platforms enabled for posting');
      return [];
    }

    // Adapt content for each platform
    $platform_content = [];
    foreach ($enabled_platforms as $platform_name => $platform) {
      $adapted_content = $this->adaptContentForPlatform($base_content, $platform, $type, $context);
      $platform_content[$platform_name] = $adapted_content;
      
      $this->logger->info('Generated content for @platform: @content', [
        '@platform' => $platform->getName(),
        '@content' => substr($adapted_content, 0, 100) . '...'
      ]);
    }

    return $platform_content;
  }

  /**
   * Generate base content based on type.
   *
   * @param string $type
   *   The content type.
   *
   * @return string
   *   Base content string.
   */
  protected function generateBaseContent(string $type): string {
    switch ($type) {
      case 'recent_article':
        return $this->generateRecentArticle();
        
      case 'analytics_summary':
        return $this->generateAnalyticsSummary();
        
      case 'trending_topics':
        return $this->generateTrendingTopics();
        
      case 'bias_insight':
        return $this->generateBiasInsight();
        
      default:
        $this->logger->warning('Unknown content type: @type', ['@type' => $type]);
        return $this->getFallbackContent();
    }
  }

  /**
   * Adapt content for a specific platform.
   *
   * @param string $base_content
   *   The base content to adapt.
   * @param \Drupal\social_media_automation\Service\Platform\PlatformInterface $platform
   *   The target platform.
   * @param string $content_type
   *   The type of content being adapted.
   * @param array $context
   *   Additional context for adaptation.
   *
   * @return string
   *   Platform-adapted content.
   */
  protected function adaptContentForPlatform($base_content, $platform, string $content_type, array $context = []): string {
    // Start with base content
    $adapted_content = $base_content;
    
    // Add platform-specific elements
    $adapted_content = $this->addPlatformSpecificElements($adapted_content, $platform, $content_type);
    
    // Add URL and hashtags based on platform capabilities
    $adapted_content = $this->addUrlAndHashtags($adapted_content, $platform, $content_type);
    
    // Let the platform do final formatting
    $adapted_content = $platform->formatContent($adapted_content, $context);
    
    return $adapted_content;
  }

  /**
   * Add platform-specific elements to content.
   *
   * @param string $content
   *   The content to modify.
   * @param \Drupal\social_media_automation\Service\Platform\PlatformInterface $platform
   *   The target platform.
   * @param string $content_type
   *   The content type.
   *
   * @return string
   *   Modified content.
   */
  protected function addPlatformSpecificElements(string $content, $platform, string $content_type): string {
    $platform_name = $platform->getMachineName();
    $features = $platform->getSupportedFeatures();
    
    // Platform-specific adjustments
    switch ($platform_name) {
      case 'mastodon':
        // Mastodon supports longer content and content warnings
        if ($content_type === 'bias_insight') {
          // Could add content warning for bias discussion
          $content = "AI/Algorithm Discussion\n\n" . $content;
        }
        break;
        
      case 'linkedin':
        // LinkedIn prefers professional tone and longer content
        $content = $this->makeContentMoreProfessional($content);
        break;
        
      case 'facebook':
        // Facebook allows longer content and more casual tone
        $content = $this->makeContentMoreEngaging($content);
        break;
        
      case 'twitter':
        // Twitter has strict character limits
        $content = $this->makeContentConcise($content, 280);
        break;
    }
    
    return $content;
  }

  /**
   * Add URL and hashtags to content based on platform.
   *
   * @param string $content
   *   The content to modify.
   * @param \Drupal\social_media_automation\Service\Platform\PlatformInterface $platform
   *   The target platform.
   * @param string $content_type
   *   The content type.
   *
   * @return string
   *   Content with URL and hashtags.
   */
  protected function addUrlAndHashtags(string $content, $platform, string $content_type): string {
    $features = $platform->getSupportedFeatures();
    $char_limit = $platform->getCharacterLimit();
    
    // Add URL
    $url = "\n\n🔗 thetruthperspective.org";
    
    // Add hashtags based on content type and platform features
    $hashtags = [];
    switch ($content_type) {
      case 'recent_article':
        $hashtags = ['#AIAnalysis', '#NewsBreakdown', '#MediaInsights', '#TheTruthPerspective'];
        break;
        
      case 'analytics_summary':
        $hashtags = ['#AIAnalysis', '#MediaBias', '#NewsAnalysis', '#DataTransparency'];
        break;
        
      case 'trending_topics':
        $hashtags = ['#TrendingTopics', '#NewsAnalysis', '#AI', '#MediaPatterns'];
        break;
        
      case 'bias_insight':
        $hashtags = ['#AIBias', '#MediaLiteracy', '#TechEthics', '#InformationControl'];
        break;
    }
    
    if (!empty($features['hashtags']) && !empty($hashtags)) {
      $hashtag_string = "\n\n" . implode(' ', $hashtags);
      
      // Check if content + URL + hashtags fit within character limit
      $total_length = strlen($content . $url . $hashtag_string);
      if ($total_length <= $char_limit) {
        $content .= $url . $hashtag_string;
      } else {
        // Try with fewer hashtags
        $reduced_hashtags = array_slice($hashtags, 0, 2);
        $hashtag_string = "\n\n" . implode(' ', $reduced_hashtags);
        $total_length = strlen($content . $url . $hashtag_string);
        
        if ($total_length <= $char_limit) {
          $content .= $url . $hashtag_string;
        } else {
          // Just add URL if hashtags don't fit
          if (strlen($content . $url) <= $char_limit) {
            $content .= $url;
          }
        }
      }
    } else {
      // No hashtag support, just add URL if it fits
      if (strlen($content . $url) <= $char_limit) {
        $content .= $url;
      }
    }
    
    return $content;
  }

  /**
   * Make content more professional for LinkedIn.
   *
   * @param string $content
   *   The content to modify.
   *
   * @return string
   *   More professional content.
   */
  protected function makeContentMoreProfessional(string $content): string {
    // Replace casual elements with professional ones
    $content = str_replace('🔥 Trending', 'Key insights', $content);
    $content = str_replace('📱 This week\'s hot topics', 'This week\'s analysis reveals', $content);
    $content = str_replace('What patterns do you see?', 'These patterns reveal important insights about information architecture in modern media.', $content);
    
    return $content;
  }

  /**
   * Make content more engaging for Facebook.
   *
   * @param string $content
   *   The content to modify.
   *
   * @return string
   *   More engaging content.
   */
  protected function makeContentMoreEngaging(string $content): string {
    // Add call-to-action elements
    if (strpos($content, '?') === false) {
      $content .= "\n\nWhat do you think about AI's role in media analysis?";
    }
    
    return $content;
  }

  /**
   * Make content more concise for character-limited platforms.
   *
   * @param string $content
   *   The content to modify.
   * @param int $limit
   *   Character limit.
   *
   * @return string
   *   More concise content.
   */
  protected function makeContentConcise(string $content, int $limit): string {
    if (strlen($content) <= $limit) {
      return $content;
    }
    
    // Remove extra explanatory text
    $content = str_replace('Our algorithms detected these as key narrative drivers across media sources.', 'Key narrative drivers detected.', $content);
    $content = str_replace('See how different sources frame these topics.', '', $content);
    $content = str_replace('revealing trends in how stories are framed and motivated', 'revealing framing trends', $content);
    
    // If still too long, truncate with ellipsis
    if (strlen($content) > $limit) {
      $content = substr($content, 0, $limit - 3) . '...';
    }
    
    return $content;
  }

}
