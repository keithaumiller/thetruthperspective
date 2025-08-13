<?php

namespace Drupal\news_extractor\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Service for handling data processing and field updates.
 * 
 * This service is responsible for:
 * - Processing structured AI response data
 * - Creating and managing taxonomy terms
 * - Updating entity fields with processed data
 * - Formatting analysis for display
 * - Managing data relationships
 */
class DataProcessingService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * DataProcessingService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Get the logger for this service.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected function logger() {
    return $this->loggerFactory->get('news_extractor_data');
  }

  /**
   * Process structured AI data and update entity fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param array $structured_data
   *   The structured AI response data.
   * @param string $raw_response
   *   The raw AI response for storage.
   *
   * @return bool
   *   TRUE if entity was updated successfully.
   */
  public function processAnalysisData(EntityInterface $entity, array $structured_data, $raw_response) {
    try {
      // Store raw AI response
      $this->storeRawResponse($entity, $raw_response);

      // Store structured motivation data
      $this->storeStructuredData($entity, $structured_data);

      // Update individual assessment fields
      $this->updateAssessmentFields($entity, $structured_data);

      // Create taxonomy terms and update tags FIRST
      $this->updateTaxonomyTags($entity, $structured_data);

      // Save entity to ensure taxonomy terms exist
      $entity->save();

      // NOW format analysis with taxonomy links
      $this->updateFormattedAnalysis($entity, $structured_data);

      // Final save with formatted analysis
      $entity->save();

      $this->logger()->info('Successfully processed analysis data for: @title', [
        '@title' => $entity->getTitle(),
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger()->error('Error processing analysis data for @title: @error', [
        '@title' => $entity->getTitle(),
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Store raw AI response in entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param string $raw_response
   *   The raw AI response.
   */
  protected function storeRawResponse(EntityInterface $entity, $raw_response) {
    // Store raw AI response for debugging and reprocessing
    if ($entity->hasField('field_ai_raw_response')) {
      $entity->set('field_ai_raw_response', $raw_response);
    }

    // Keep backward compatibility with existing field
    if ($entity->hasField('field_ai_summary')) {
      $entity->set('field_ai_summary', $raw_response);
    }
  }

  /**
   * Store structured data as JSON.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param array $structured_data
   *   The structured data.
   */
  protected function storeStructuredData(EntityInterface $entity, array $structured_data) {
    if ($entity->hasField('field_motivation_data')) {
      $entity->set('field_motivation_data', json_encode($structured_data));
    }
  }

  /**
   * Update individual assessment fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param array $structured_data
   *   The structured data.
   */
  protected function updateAssessmentFields(EntityInterface $entity, array $structured_data) {
    // Credibility score
    if (isset($structured_data['credibility_score']) && $entity->hasField('field_credibility_score')) {
      $entity->set('field_credibility_score', (string) $structured_data['credibility_score']);
    }

    // Bias rating
    if (isset($structured_data['bias_rating']) && $entity->hasField('field_bias_rating')) {
      $entity->set('field_bias_rating', (string) $structured_data['bias_rating']);
    }

    // Bias analysis
    if (isset($structured_data['bias_analysis']) && $entity->hasField('field_bias_analysis')) {
      $entity->set('field_bias_analysis', $structured_data['bias_analysis']);
    }

    // Sentiment score
    if (isset($structured_data['sentiment_score']) && $entity->hasField('field_article_sentiment_score')) {
      $entity->set('field_article_sentiment_score', (string) $structured_data['sentiment_score']);
    }
  }

  /**
   * Update taxonomy tags from structured data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param array $structured_data
   *   The structured data.
   */
  protected function updateTaxonomyTags(EntityInterface $entity, array $structured_data) {
    if (!$entity->hasField('field_tags')) {
      return;
    }

    $tags = $this->extractTagsFromData($structured_data);
    $tag_ids = [];

    foreach ($tags as $tag) {
      if (!empty(trim($tag))) {
        $tid = $this->getOrCreateTag($tag);
        if ($tid) {
          $tag_ids[] = $tid;
        }
      }
    }

    if (!empty($tag_ids)) {
      $entity->set('field_tags', $tag_ids);
      $this->logger()->info('Created @count taxonomy tags for: @title', [
        '@count' => count($tag_ids),
        '@title' => $entity->getTitle(),
      ]);
    }
  }

  /**
   * Extract all tags from structured data.
   *
   * @param array $structured_data
   *   The structured data.
   *
   * @return array
   *   Array of tag names.
   */
  protected function extractTagsFromData(array $structured_data) {
    $tags = [];

    // Extract entity names
    if (isset($structured_data['entities']) && is_array($structured_data['entities'])) {
      foreach ($structured_data['entities'] as $entity_data) {
        if (isset($entity_data['name'])) {
          $tags[] = $entity_data['name'];
        }
        if (isset($entity_data['motivations']) && is_array($entity_data['motivations'])) {
          foreach ($entity_data['motivations'] as $motivation) {
            $tags[] = $motivation;
          }
        }
      }
    }

    // Add metrics
    if (isset($structured_data['metrics']) && is_array($structured_data['metrics'])) {
      $tags = array_merge($tags, $structured_data['metrics']);
    }

    return array_unique($tags);
  }

  /**
   * Get or create a taxonomy term for tags.
   *
   * @param string $tag_name
   *   The tag name.
   *
   * @return int|null
   *   The term ID or NULL on failure.
   */
  protected function getOrCreateTag($tag_name) {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $term_storage->loadByProperties([
      'name' => $tag_name,
      'vid' => 'tags',
    ]);

    if (!empty($terms)) {
      $term = reset($terms);
      return $term->id();
    }

    try {
      $term = $term_storage->create([
        'name' => $tag_name,
        'vid' => 'tags',
      ]);
      $term->save();
      return $term->id();
    }
    catch (\Exception $e) {
      $this->logger()->error('Error creating tag @tag: @message', [
        '@tag' => $tag_name,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Update formatted analysis with taxonomy links.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param array $structured_data
   *   The structured data.
   */
  protected function updateFormattedAnalysis(EntityInterface $entity, array $structured_data) {
    if (!$entity->hasField('field_motivation_analysis')) {
      return;
    }

    $formatted_analysis = $this->formatAnalysisWithLinks($structured_data);
    $entity->set('field_motivation_analysis', [
      'value' => $formatted_analysis,
      'format' => 'basic_html',
    ]);
  }

  /**
   * Format structured data for human-readable display with taxonomy links.
   *
   * @param array $structured_data
   *   The structured data.
   *
   * @return string
   *   Formatted HTML content.
   */
  protected function formatAnalysisWithLinks(array $structured_data) {
    if (empty($structured_data)) {
      return '<p>No analysis data available.</p>';
    }

    $html = '';

    // Entities section with taxonomy links
    if (!empty($structured_data['entities']) && is_array($structured_data['entities'])) {
      $html .= '<p><strong>Entities mentioned:</strong><br>';
      foreach ($structured_data['entities'] as $entity) {
        if (isset($entity['name']) && isset($entity['motivations'])) {
          $entity_name = $entity['name'];
          $motivations = is_array($entity['motivations']) ? $entity['motivations'] : [$entity['motivations']];

          // Create link for entity name
          $entity_link = $this->createTaxonomyLink($entity_name);

          // Create links for motivations
          $motivation_links = [];
          foreach ($motivations as $motivation) {
            if (!empty(trim($motivation))) {
              $motivation_links[] = $this->createTaxonomyLink($motivation);
            }
          }

          $motivation_string = implode(', ', $motivation_links);
          $html .= "- {$entity_link}: {$motivation_string}<br>";
        }
      }
      $html .= '</p>';
    }

    // Assessment scores section
    $html .= '<p><strong>Article Assessment:</strong><br>';

    if (isset($structured_data['credibility_score'])) {
      $html .= "Credibility Score: {$structured_data['credibility_score']}/100<br>";
    }

    if (isset($structured_data['bias_rating'])) {
      $bias_label = $this->getBiasLabel($structured_data['bias_rating']);
      $html .= "Bias Rating: {$structured_data['bias_rating']}/100 ({$bias_label})<br>";
    }

    if (isset($structured_data['sentiment_score'])) {
      $html .= "Sentiment Score: {$structured_data['sentiment_score']}/100<br>";
    }

    $html .= '</p>';

    // Bias analysis
    if (!empty($structured_data['bias_analysis'])) {
      $html .= '<p><strong>Bias Analysis:</strong><br>' . $structured_data['bias_analysis'] . '</p>';
    }

    // Key metric section with taxonomy link
    if (!empty($structured_data['metrics']) && is_array($structured_data['metrics'])) {
      $metric = $structured_data['metrics'][0]; // Take first metric
      $metric_link = $this->createTaxonomyLink($metric);
      $html .= "<p><strong>Key metric:</strong> {$metric_link}</p>";
    }

    // Analysis section
    if (!empty($structured_data['analysis'])) {
      $html .= '<p></p>'; // Spacing
      $html .= '<p>' . $structured_data['analysis'] . '</p>';
    }

    // Fallback if no structured content
    if (empty($html)) {
      $html = '<p>Analysis data processed but no structured content available.</p>';
    }

    return $html;
  }

  /**
   * Create a taxonomy link for a given term name.
   *
   * @param string $term_name
   *   The term name.
   *
   * @return string
   *   The linked term or plain text if term doesn't exist.
   */
  protected function createTaxonomyLink($term_name) {
    if (empty(trim($term_name))) {
      return $term_name;
    }

    $clean_name = trim($term_name);

    // Try to find existing taxonomy term
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $term_storage->loadByProperties([
      'name' => $clean_name,
      'vid' => 'tags',
    ]);

    if (!empty($terms)) {
      // Term exists, create link
      $term = reset($terms);
      $term_id = $term->id();
      $term_url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $term_id]);
      $link = Link::fromTextAndUrl($clean_name, $term_url)->toString();
      return $link;
    }
    else {
      // Term doesn't exist, return plain text
      return $clean_name;
    }
  }

  /**
   * Get bias label from numeric rating.
   *
   * @param int $bias_score
   *   The bias score.
   *
   * @return string
   *   The bias label.
   */
  protected function getBiasLabel($bias_score) {
    if ($bias_score <= 20) {
      return 'Extreme Left';
    }
    elseif ($bias_score <= 40) {
      return 'Lean Left';
    }
    elseif ($bias_score <= 60) {
      return 'Center';
    }
    elseif ($bias_score <= 80) {
      return 'Lean Right';
    }
    else {
      return 'Extreme Right';
    }
  }

  /**
   * Reprocess entity from stored raw AI response.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to reprocess.
   *
   * @return bool
   *   TRUE if successfully reprocessed.
   */
  public function reprocessFromRawResponse(EntityInterface $entity) {
    if (!$entity->hasField('field_ai_raw_response') || $entity->get('field_ai_raw_response')->isEmpty()) {
      $this->logger()->warning('No raw AI response found for entity @id', ['@id' => $entity->id()]);
      return FALSE;
    }

    $raw_response = $entity->get('field_ai_raw_response')->value;

    // We need the AI service to parse the response
    $ai_service = \Drupal::service('news_extractor.ai_processing');
    $structured_data = $ai_service->parseResponse($raw_response);

    return $this->processAnalysisData($entity, $structured_data, $raw_response);
  }

  /**
   * Extract news source from various entity fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to extract source from.
   *
   * @return string|null
   *   The news source or NULL if not found.
   */
  public function extractNewsSource(EntityInterface $entity) {
    // Try field_site_name first (most efficient)
    if ($entity->hasField('field_site_name') && !$entity->get('field_site_name')->isEmpty()) {
      $site_name = trim($entity->get('field_site_name')->value);
      if (!empty($site_name)) {
        return $this->cleanNewsSource($site_name);
      }
    }

    // Fallback to JSON data
    if ($entity->hasField('field_json_scraped_article_data') && !$entity->get('field_json_scraped_article_data')->isEmpty()) {
      $json_data = $entity->get('field_json_scraped_article_data')->value;

      if (trim($json_data) === "Scraped data unavailable.") {
        return "Source Unavailable";
      }

      try {
        $parsed_data = json_decode($json_data, TRUE);
        if (json_last_error() === JSON_ERROR_NONE && isset($parsed_data['objects'][0]['siteName'])) {
          $site_name = trim($parsed_data['objects'][0]['siteName']);
          return $this->cleanNewsSource($site_name);
        }
      }
      catch (\Exception $e) {
        $this->logger()->error('Error parsing JSON data for news source extraction: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // Final fallback to URL
    if ($entity->hasField('field_original_url') && !$entity->get('field_original_url')->isEmpty()) {
      $url = $entity->get('field_original_url')->uri;
      return $this->extractSourceFromUrl($url);
    }

    return NULL;
  }

  /**
   * Clean and standardize news source name.
   *
   * @param string $source
   *   The raw source name.
   *
   * @return string
   *   The cleaned source name.
   */
  protected function cleanNewsSource($source) {
    $source = trim($source);

    // Common source name mappings
    $source_map = [
      'CNN.com' => 'CNN',
      'CNN Politics' => 'CNN Politics',
      'Fox News' => 'Fox News',
      'Reuters.com' => 'Reuters',
      'AP News' => 'Associated Press',
      'NPR.org' => 'NPR',
      'BBC News' => 'BBC News',
      'WSJ.com' => 'Wall Street Journal',
      'The New York Times' => 'New York Times',
      'The Washington Post' => 'Washington Post',
      'POLITICO' => 'Politico',
      'The Hill' => 'The Hill',
    ];

    return $source_map[$source] ?? $source;
  }

  /**
   * Extract source from URL as fallback.
   *
   * @param string $url
   *   The URL to extract from.
   *
   * @return string|null
   *   The extracted source or NULL.
   */
  protected function extractSourceFromUrl($url) {
    $parsed_url = parse_url($url);
    if (!isset($parsed_url['host'])) {
      return NULL;
    }

    $host = strtolower($parsed_url['host']);
    $host = preg_replace('/^www\./', '', $host);

    $domain_map = [
      'cnn.com' => 'CNN',
      'foxnews.com' => 'Fox News',
      'reuters.com' => 'Reuters',
      'ap.org' => 'Associated Press',
      'npr.org' => 'NPR',
      'bbc.com' => 'BBC News',
      'wsj.com' => 'Wall Street Journal',
      'nytimes.com' => 'New York Times',
      'washingtonpost.com' => 'Washington Post',
      'politico.com' => 'Politico',
      'thehill.com' => 'The Hill',
    ];

    if (isset($domain_map[$host])) {
      return $domain_map[$host];
    }

    // For unknown domains, convert to title case
    $parts = explode('.', $host);
    if (count($parts) >= 2) {
      $domain_name = $parts[0];
      return ucwords(str_replace(['-', '_'], ' ', $domain_name));
    }

    return ucwords(str_replace(['-', '_', '.'], ' ', $host));
  }

}
