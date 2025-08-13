<?php

namespace Drupal\news_extractor\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Main orchestrator service for news extraction and analysis.
 * 
 * This service coordinates the three core services:
 * - ScrapingService (Sensors): Data gathering from external sources
 * - AIProcessingService (Processors): Data transformation and analysis  
 * - DataProcessingService (Levers): Actions taken based on processed data
 */
class NewsExtractionService {

  /**
   * The scraping service.
   *
   * @var \Drupal\news_extractor\Service\ScrapingService
   */
  protected $scrapingService;

  /**
   * The AI processing service.
   *
   * @var \Drupal\news_extractor\Service\AIProcessingService
   */
  protected $aiProcessingService;

  /**
   * The data processing service.
   *
   * @var \Drupal\news_extractor\Service\DataProcessingService
   */
  protected $dataProcessingService;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * NewsExtractionService constructor.
   *
   * @param \Drupal\news_extractor\Service\ScrapingService $scraping_service
   *   The scraping service.
   * @param \Drupal\news_extractor\Service\AIProcessingService $ai_processing_service
   *   The AI processing service.
   * @param \Drupal\news_extractor\Service\DataProcessingService $data_processing_service
   *   The data processing service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ScrapingService $scraping_service,
    AIProcessingService $ai_processing_service,
    DataProcessingService $data_processing_service,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->scrapingService = $scraping_service;
    $this->aiProcessingService = $ai_processing_service;
    $this->dataProcessingService = $data_processing_service;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Get the logger for this service.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected function logger() {
    return $this->loggerFactory->get('news_extractor_orchestrator');
  }

  /**
   * Complete extraction and analysis pipeline for an article entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The article entity to process.
   * @param string $url
   *   The article URL.
   *
   * @return bool
   *   TRUE if processing was successful.
   */
  public function processArticle(EntityInterface $entity, $url) {
    $this->logger()->info('Starting complete article processing for: @title (@url)', [
      '@title' => $entity->getTitle(),
      '@url' => $url,
    ]);

    try {
      // SENSOR PHASE: Extract content from external source
      $diffbot_response = $this->scrapingService->extractContent($url);
      
      if (!$diffbot_response || empty($diffbot_response['objects'][0]['text'])) {
        $this->logger()->warning('No content extracted from Diffbot for: @url', ['@url' => $url]);
        return FALSE;
      }

      // Store complete scraped data
      $this->scrapingService->storeScrapedData($entity, $diffbot_response);

      // Update basic content fields
      $this->scrapingService->updateBasicFields($entity, $diffbot_response);

      // PROCESSOR PHASE: AI analysis of content
      $article_data = $diffbot_response['objects'][0];
      $ai_response = $this->aiProcessingService->generateAnalysis(
        $article_data['text'], 
        $entity->getTitle()
      );

      if (!$ai_response) {
        $this->logger()->warning('No AI analysis generated for: @title', [
          '@title' => $entity->getTitle(),
        ]);
        return FALSE;
      }

      // Parse AI response into structured data
      $structured_data = $this->aiProcessingService->parseResponse($ai_response);

      // Validate AI response structure
      if (!$this->aiProcessingService->validateResponse($structured_data)) {
        $this->logger()->warning('Invalid AI response structure for: @title', [
          '@title' => $entity->getTitle(),
        ]);
        return FALSE;
      }

      // LEVER PHASE: Process and store analysis data
      $success = $this->dataProcessingService->processAnalysisData(
        $entity, 
        $structured_data, 
        $ai_response
      );

      if ($success) {
        $this->logger()->info('Successfully completed processing for: @title', [
          '@title' => $entity->getTitle(),
        ]);
        return TRUE;
      }
      else {
        $this->logger()->error('Failed to process analysis data for: @title', [
          '@title' => $entity->getTitle(),
        ]);
        return FALSE;
      }

    }
    catch (\Exception $e) {
      $this->logger()->error('Error in article processing pipeline for @title: @error', [
        '@title' => $entity->getTitle(),
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Reprocess article from stored data without making new API calls.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The article entity to reprocess.
   *
   * @return bool
   *   TRUE if reprocessing was successful.
   */
  public function reprocessArticle(EntityInterface $entity) {
    $this->logger()->info('Reprocessing article from stored data: @title', [
      '@title' => $entity->getTitle(),
    ]);

    // Try to reprocess from stored AI response first
    if ($this->dataProcessingService->reprocessFromRawResponse($entity)) {
      $this->logger()->info('Successfully reprocessed from raw AI response: @title', [
        '@title' => $entity->getTitle(),
      ]);
      return TRUE;
    }

    // Fallback: check if we have stored scraped data for re-analysis
    $stored_data = $this->scrapingService->getStoredScrapedData($entity);
    if ($stored_data && !empty($stored_data['objects'][0]['text'])) {
      $article_data = $stored_data['objects'][0];
      
      // Generate new AI analysis
      $ai_response = $this->aiProcessingService->generateAnalysis(
        $article_data['text'],
        $entity->getTitle()
      );

      if ($ai_response) {
        $structured_data = $this->aiProcessingService->parseResponse($ai_response);
        
        if ($this->aiProcessingService->validateResponse($structured_data)) {
          return $this->dataProcessingService->processAnalysisData(
            $entity,
            $structured_data,
            $ai_response
          );
        }
      }
    }

    $this->logger()->warning('Unable to reprocess article - no stored data available: @title', [
      '@title' => $entity->getTitle(),
    ]);
    
    return FALSE;
  }

  /**
   * Process only the scraping phase (sensor) for an article.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The article entity.
   * @param string $url
   *   The URL to scrape.
   *
   * @return bool
   *   TRUE if scraping was successful.
   */
  public function scrapeArticleOnly(EntityInterface $entity, $url) {
    $this->logger()->info('Scraping content only for: @title (@url)', [
      '@title' => $entity->getTitle(),
      '@url' => $url,
    ]);

    $diffbot_response = $this->scrapingService->extractContent($url);
    
    if ($diffbot_response) {
      $this->scrapingService->storeScrapedData($entity, $diffbot_response);
      $this->scrapingService->updateBasicFields($entity, $diffbot_response);
      $entity->save();
      
      $this->logger()->info('Successfully scraped content for: @title', [
        '@title' => $entity->getTitle(),
      ]);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Process only the AI analysis phase (processor) for an article.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The article entity.
   *
   * @return bool
   *   TRUE if analysis was successful.
   */
  public function analyzeArticleOnly(EntityInterface $entity) {
    $this->logger()->info('Analyzing content only for: @title', [
      '@title' => $entity->getTitle(),
    ]);

    // Get article text from body field or stored data
    $article_text = '';
    
    if ($entity->hasField('body') && !$entity->get('body')->isEmpty()) {
      $article_text = $entity->get('body')->value;
    }
    else {
      // Try to get from stored scraped data
      $stored_data = $this->scrapingService->getStoredScrapedData($entity);
      if ($stored_data && !empty($stored_data['objects'][0]['text'])) {
        $article_text = $stored_data['objects'][0]['text'];
      }
    }

    if (empty($article_text)) {
      $this->logger()->warning('No article text found for analysis: @title', [
        '@title' => $entity->getTitle(),
      ]);
      return FALSE;
    }

    $ai_response = $this->aiProcessingService->generateAnalysis(
      $article_text,
      $entity->getTitle()
    );

    if ($ai_response) {
      $structured_data = $this->aiProcessingService->parseResponse($ai_response);
      
      if ($this->aiProcessingService->validateResponse($structured_data)) {
        return $this->dataProcessingService->processAnalysisData(
          $entity,
          $structured_data,
          $ai_response
        );
      }
    }

    return FALSE;
  }

  /**
   * Get processing statistics for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return array
   *   Array of processing status information.
   */
  public function getProcessingStatus(EntityInterface $entity) {
    $status = [
      'has_scraped_data' => FALSE,
      'has_ai_response' => FALSE,
      'has_structured_data' => FALSE,
      'has_taxonomy_tags' => FALSE,
      'entity_title' => $entity->getTitle(),
      'entity_id' => $entity->id(),
    ];

    // Check for scraped data
    if ($entity->hasField('field_json_scraped_article_data') && 
        !$entity->get('field_json_scraped_article_data')->isEmpty()) {
      $status['has_scraped_data'] = TRUE;
    }

    // Check for AI response
    if ($entity->hasField('field_ai_raw_response') && 
        !$entity->get('field_ai_raw_response')->isEmpty()) {
      $status['has_ai_response'] = TRUE;
    }

    // Check for structured data
    if ($entity->hasField('field_motivation_data') && 
        !$entity->get('field_motivation_data')->isEmpty()) {
      $status['has_structured_data'] = TRUE;
    }

    // Check for taxonomy tags
    if ($entity->hasField('field_tags') && 
        !$entity->get('field_tags')->isEmpty()) {
      $status['has_taxonomy_tags'] = TRUE;
      $status['tag_count'] = count($entity->get('field_tags')->getValue());
    }

    return $status;
  }

}
