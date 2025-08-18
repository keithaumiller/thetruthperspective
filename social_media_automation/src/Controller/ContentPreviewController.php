<?php

namespace Drupal\social_media_automation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\news_extractor\Service\AIProcessingService;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for generating social media content previews using AI.
 */
class ContentPreviewController extends ControllerBase {

  /**
   * The AI processing service.
   *
   * @var \Drupal\news_extractor\Service\AIProcessingService
   */
  protected $aiProcessingService;

  /**
   * Constructs a ContentPreviewController object.
   *
   * @param \Drupal\news_extractor\Service\AIProcessingService $ai_processing_service
   *   The AI processing service.
   */
  public function __construct(AIProcessingService $ai_processing_service) {
    $this->aiProcessingService = $ai_processing_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('news_extractor.ai_processing')
    );
  }

  /**
   * Generate a social media post preview using AI.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response with the generated content.
   */
  public function generatePreview(Request $request) {
    $response = new AjaxResponse();

    try {
      // Get the most recently published article
      $recent_article = $this->getMostRecentArticle();
      
      if (!$recent_article) {
        $response->addCommand(new MessageCommand(
          $this->t('No recent articles found to generate content from.'),
          '#social-media-preview-container',
          ['type' => 'warning']
        ));
        return $response;
      }

      // Get the AI summary from the article
      $ai_summary = $this->getAiSummaryFromArticle($recent_article);
      
      if (empty($ai_summary)) {
        $response->addCommand(new MessageCommand(
          $this->t('No AI analysis found for the most recent article.'),
          '#social-media-preview-container',
          ['type' => 'warning']
        ));
        return $response;
      }

      // Generate social media post using AI
      $social_media_post = $this->generateSocialMediaPost($recent_article, $ai_summary);

      if ($social_media_post) {
        // Create the preview HTML
        $preview_html = $this->buildPreviewHtml($recent_article, $social_media_post);
        
        $response->addCommand(new ReplaceCommand(
          '#social-media-preview-container',
          $preview_html
        ));
      } else {
        $response->addCommand(new MessageCommand(
          $this->t('Failed to generate social media content. Please try again.'),
          '#social-media-preview-container',
          ['type' => 'error']
        ));
      }

    } catch (\Exception $e) {
      \Drupal::logger('social_media_automation')->error('Content preview generation failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      $response->addCommand(new MessageCommand(
        $this->t('An error occurred while generating content. Please try again.'),
        '#social-media-preview-container',
        ['type' => 'error']
      ));
    }

    return $response;
  }

  /**
   * Get the most recently published article.
   *
   * @return \Drupal\node\Entity\Node|null
   *   The most recent article node, or NULL if none found.
   */
  protected function getMostRecentArticle() {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 1) // Published only
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->accessCheck(FALSE);

    $nids = $query->execute();
    
    if (empty($nids)) {
      return NULL;
    }

    $nid = reset($nids);
    return Node::load($nid);
  }

  /**
   * Extract AI summary from article.
   *
   * @param \Drupal\node\Entity\Node $article
   *   The article node.
   *
   * @return string|null
   *   The AI summary text, or NULL if not found.
   */
  protected function getAiSummaryFromArticle(Node $article) {
    // Check for AI response field
    if ($article->hasField('field_ai_response') && !$article->get('field_ai_response')->isEmpty()) {
      $ai_response = $article->get('field_ai_response')->value;
      
      // Parse JSON response to extract summary
      $ai_data = json_decode($ai_response, TRUE);
      if (isset($ai_data['summary'])) {
        return $ai_data['summary'];
      }
      
      // If summary not found, use the full response
      return $ai_response;
    }

    // Fallback to article body if no AI summary
    if ($article->hasField('body') && !$article->get('body')->isEmpty()) {
      $body = $article->get('body')->value;
      // Return first 500 characters as a summary
      return substr(strip_tags($body), 0, 500) . '...';
    }

    return NULL;
  }

  /**
   * Generate social media post content using AI.
   *
   * @param \Drupal\node\Entity\Node $article
   *   The article node.
   * @param string $ai_summary
   *   The AI-generated summary.
   *
   * @return string|null
   *   The generated social media post, or NULL on failure.
   */
  protected function generateSocialMediaPost(Node $article, $ai_summary) {
    $article_title = $article->getTitle();
    $article_url = $article->toUrl('canonical', ['absolute' => TRUE])->toString();
    
    // Build prompt for social media content generation
    $prompt = $this->buildSocialMediaPrompt($article_title, $ai_summary, $article_url);
    
    try {
      // Use the existing AI processing service to generate content
      $ai_response = $this->aiProcessingService->generateAnalysis($prompt);
      
      if ($ai_response && isset($ai_response['social_media_post'])) {
        return $ai_response['social_media_post'];
      }
      
      // If structured response not available, use the raw response
      return $ai_response;
      
    } catch (\Exception $e) {
      \Drupal::logger('social_media_automation')->error('AI social media generation failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Build prompt for social media content generation.
   *
   * @param string $title
   *   Article title.
   * @param string $summary
   *   Article summary.
   * @param string $url
   *   Article URL.
   *
   * @return string
   *   The formatted prompt.
   */
  protected function buildSocialMediaPrompt($title, $summary, $url) {
    return "Generate a compelling social media post for Mastodon based on this news article analysis. 

ARTICLE TITLE: {$title}

ARTICLE ANALYSIS: {$summary}

ARTICLE URL: {$url}

REQUIREMENTS:
- Keep under 500 characters (Mastodon limit)
- Include relevant hashtags (2-4 maximum)
- Be engaging and informative
- Include the article URL
- Maintain journalistic credibility
- Focus on key insights from the analysis

Please respond with ONLY the social media post text, ready to publish. Do not include any additional commentary or explanation.";
  }

  /**
   * Build the preview HTML for display.
   *
   * @param \Drupal\node\Entity\Node $article
   *   The source article.
   * @param string $social_media_post
   *   The generated social media content.
   *
   * @return string
   *   The HTML for the preview.
   */
  protected function buildPreviewHtml(Node $article, $social_media_post) {
    $article_title = $article->getTitle();
    $article_url = $article->toUrl('canonical', ['absolute' => TRUE])->toString();
    $created_date = \Drupal::service('date.formatter')->format($article->getCreatedTime(), 'medium');
    
    $html = '<div id="social-media-preview-container" class="social-media-preview">';
    $html .= '<h3>' . $this->t('Generated Social Media Post Preview') . '</h3>';
    
    $html .= '<div class="preview-content">';
    $html .= '<div class="post-preview">';
    $html .= '<h4>' . $this->t('Post Content:') . '</h4>';
    $html .= '<div class="post-text">' . nl2br(htmlspecialchars($social_media_post)) . '</div>';
    $html .= '<div class="character-count">' . $this->t('Character count: @count', ['@count' => strlen($social_media_post)]) . '</div>';
    $html .= '</div>';
    
    $html .= '<div class="source-article">';
    $html .= '<h4>' . $this->t('Source Article:') . '</h4>';
    $html .= '<div class="article-info">';
    $html .= '<strong>' . htmlspecialchars($article_title) . '</strong><br>';
    $html .= '<small>' . $this->t('Published: @date', ['@date' => $created_date]) . '</small><br>';
    $html .= '<a href="' . $article_url . '" target="_blank">' . $article_url . '</a>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="preview-actions">';
    $html .= '<button type="button" class="button button--primary" onclick="generateNewPreview()">' . $this->t('Generate New Version') . '</button>';
    $html .= '<button type="button" class="button" onclick="clearPreview()">' . $this->t('Clear Preview') . '</button>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
  }

}
