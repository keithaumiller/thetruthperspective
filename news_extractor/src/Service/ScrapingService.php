<?php

namespace Drupal\news_extractor\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Service for handling web scraping and content extraction via Diffbot API.
 * 
 * This service is responsible for:
 * - Making rate-limited Diffbot API calls
 * - Extracting article content and metadata
 * - Validating and filtering URLs
 * - Storing raw scraped data
 */
class ScrapingService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * ScrapingService constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    StateInterface $state
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->state = $state;
  }

  /**
   * Get the logger for this service.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected function logger() {
    return $this->loggerFactory->get('news_extractor_scraping');
  }

  /**
   * Get the Diffbot API token from configuration.
   *
   * @return string
   *   The API token.
   */
  protected function getDiffbotToken() {
    return $this->configFactory->get('news_extractor.settings')->get('diffbot_token') ?: '';
  }

  /**
   * Extract article content from URL using Diffbot API.
   *
   * @param string $url
   *   The article URL to scrape.
   *
   * @return array|null
   *   Complete Diffbot API response or NULL on failure.
   */
  public function extractContent($url) {
    $api_token = $this->getDiffbotToken();
    if (empty($api_token)) {
      $this->logger()->error('Diffbot token not configured.');
      return NULL;
    }

    // Validate URL before processing
    if (!$this->isValidArticleUrl($url)) {
      $this->logger()->info('Skipping invalid or blocked URL: @url', ['@url' => $url]);
      return NULL;
    }

    return $this->makeDiffbotApiCall($url, $api_token);
  }

  /**
   * Make rate-limited Diffbot API call.
   *
   * @param string $url
   *   The URL to extract.
   * @param string $api_token
   *   The Diffbot API token.
   *
   * @return array|null
   *   The API response or NULL on failure.
   */
  protected function makeDiffbotApiCall($url, $api_token) {
    // RATE LIMITING: Diffbot allows 0.08 calls/second = 1 call every 12.5 seconds
    $rate_limit_key = 'news_extractor_diffbot_last_call';
    $min_interval = 13; // 13 seconds between calls for safety

    // Check last call time
    $last_call = $this->state->get($rate_limit_key, 0);
    $current_time = time();
    $time_since_last = $current_time - $last_call;

    if ($time_since_last < $min_interval) {
      $sleep_time = $min_interval - $time_since_last;
      $this->logger()->info('Rate limiting: Waiting @seconds seconds before Diffbot API call', [
        '@seconds' => $sleep_time,
      ]);
      sleep($sleep_time);
    }

    try {
      $api_url = 'https://api.diffbot.com/v3/article';
      $request_url = $api_url . '?' . http_build_query([
        'token' => $api_token,
        'url' => $url,
        'naturalLanguage' => 'summary',
      ]);

      $this->logger()->info('Making Diffbot API call to: @url', ['@url' => $url]);

      $response = $this->httpClient->get($request_url, [
        'timeout' => 30,
        'headers' => ['Accept' => 'application/json'],
      ]);

      // Record successful call time
      $this->state->set($rate_limit_key, time());

      $data = json_decode($response->getBody()->getContents(), TRUE);

      if (!empty($data['objects'][0])) {
        $this->logger()->info('Successfully extracted content from Diffbot: @chars chars', [
          '@chars' => strlen($data['objects'][0]['text'] ?? ''),
        ]);
        return $data;
      }
      else {
        $this->logger()->warning('Diffbot returned no article data for: @url', ['@url' => $url]);
      }
    }
    catch (\Exception $e) {
      $error_message = $e->getMessage();

      // Handle rate limiting errors
      if (strpos($error_message, '429') !== FALSE || strpos($error_message, 'Too Many Requests') !== FALSE) {
        $this->logger()->error('Diffbot rate limit exceeded. Waiting 60 seconds before retry. Error: @error', [
          '@error' => $error_message,
        ]);
        // Set longer wait time for rate limit errors
        $this->state->set($rate_limit_key, time() + 60);
      }
      else {
        $this->logger()->error('Error calling Diffbot API: @error', ['@error' => $error_message]);
      }
    }

    return NULL;
  }

  /**
   * Check if URL is valid for article processing.
   *
   * @param string $url
   *   The URL to validate.
   *
   * @return bool
   *   TRUE if URL should be processed, FALSE otherwise.
   */
  public function isValidArticleUrl($url) {
    // Check blocked domains
    $blocked_domains = [
      'comparecards.com',
      'fool.com',
      'lendingtree.com',
    ];

    foreach ($blocked_domains as $domain) {
      if (strpos($url, $domain) !== FALSE) {
        return FALSE;
      }
    }

    // Enhanced patterns for better filtering
    $skip_patterns = [
      '/\/ads?\//i' => 'advertisements',
      '/\/advertisement/i' => 'advertisement pages',
      '/\/sponsored/i' => 'sponsored content',
      '/\/podcast/i' => 'podcast pages',
      '/\/video/i' => 'video content',
      '/\/gallery/i' => 'image galleries',
      '/financial.*markets/i' => 'financial markets',
      '/stock.*price/i' => 'stock prices',
      '/\.pdf$/i' => 'PDF files',
      '/\/audio\//i' => 'audio content',
      '/\/interactive\//i' => 'interactive content',
      '/\/live-news\//i' => 'live news feeds',
      '/\/live-tv\//i' => 'live TV content',
      '/\/newsletters?\//i' => 'newsletter content',
      '/\/weather\//i' => 'weather content',
      '/\/specials\//i' => 'special content',
      '/\/coupons?\//i' => 'coupon content',
      '/\/profiles?\//i' => 'profile pages',
    ];

    foreach ($skip_patterns as $pattern => $description) {
      if (preg_match($pattern, $url)) {
        $this->logger()->info('Skipping @description: @url', [
          '@description' => $description,
          '@url' => $url,
        ]);
        return FALSE;
      }
    }

    // Article patterns that indicate valid news content
    $article_patterns = ['/politics/', '/world/', '/us/', '/national/', '/international/', '/breaking/', '/news/'];
    foreach ($article_patterns as $pattern) {
      if (strpos($url, $pattern) !== FALSE) {
        return TRUE;
      }
    }

    // Check for common article URL patterns
    if (preg_match('/\/(index\.html?|story\.html?)$/i', $url) || preg_match('/\/\d{4}\/\d{2}\/\d{2}\//', $url)) {
      return TRUE;
    }

    return TRUE; // Default to processing if no exclusion patterns match
  }

  /**
   * Store complete Diffbot response in entity field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param array $diffbot_response
   *   The complete Diffbot response.
   */
  public function storeScrapedData(EntityInterface $entity, array $diffbot_response) {
    if ($entity->hasField('field_json_scraped_article_data')) {
      $entity->set('field_json_scraped_article_data', json_encode($diffbot_response, JSON_PRETTY_PRINT));
      $this->logger()->info('Stored complete Diffbot JSON data (@size chars) for: @title', [
        '@size' => strlen(json_encode($diffbot_response)),
        '@title' => $entity->getTitle(),
      ]);
    }
  }

  /**
   * Get stored Diffbot data from entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to read from.
   *
   * @return array|null
   *   Decoded JSON data or NULL if not available.
   */
  public function getStoredScrapedData(EntityInterface $entity) {
    if (!$entity->hasField('field_json_scraped_article_data') ||
        $entity->get('field_json_scraped_article_data')->isEmpty()) {
      return NULL;
    }

    $json_data = $entity->get('field_json_scraped_article_data')->value;
    $decoded_data = json_decode($json_data, TRUE);

    if (json_last_error() !== JSON_ERROR_NONE) {
      $this->logger()->error('Failed to decode stored JSON data for entity @id: @error', [
        '@id' => $entity->id(),
        '@error' => json_last_error_msg(),
      ]);
      return NULL;
    }

    return $decoded_data;
  }

  /**
   * Update basic article fields from Diffbot response.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param array $diffbot_response
   *   The Diffbot response.
   *
   * @return bool
   *   TRUE if any fields were updated.
   */
  public function updateBasicFields(EntityInterface $entity, array $diffbot_response) {
    $article_data = $diffbot_response['objects'][0] ?? NULL;
    if (!$article_data) {
      $this->logger()->warning('No article object found in Diffbot response for: @title', [
        '@title' => $entity->getTitle(),
      ]);
      return FALSE;
    }

    $updated = FALSE;

    // Update body with full article text
    if (!empty($article_data['text'])) {
      $entity->set('body', [
        'value' => $article_data['text'],
        'format' => 'basic_html',
      ]);
      $updated = TRUE;
      $this->logger()->info('Updated body content (@chars chars) for: @title', [
        '@chars' => strlen($article_data['text']),
        '@title' => $entity->getTitle(),
      ]);
    }

    // Update title if empty
    if (empty($entity->getTitle()) && !empty($article_data['title'])) {
      $entity->setTitle($article_data['title']);
      $updated = TRUE;
      $this->logger()->info('Updated title to: @title', [
        '@title' => $article_data['title'],
      ]);
    }

    // Update additional metadata
    $this->updateMetadataFields($entity, $article_data);

    return $updated;
  }

  /**
   * Update metadata fields from article data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param array $article_data
   *   The article data from Diffbot.
   */
  protected function updateMetadataFields(EntityInterface $entity, array $article_data) {
    // Store author information
    if (!empty($article_data['author']) && $entity->hasField('field_author')) {
      $entity->set('field_author', $article_data['author']);
    }

    // Store site name
    if (!empty($article_data['siteName']) && $entity->hasField('field_site_name')) {
      $entity->set('field_site_name', $article_data['siteName']);
    }

    // PRIMARY NEWS SOURCE EXTRACTION - This is the main place where news source gets set
    if ($entity->hasField('field_news_source')) {
      $current_source = $entity->get('field_news_source')->value;
      $new_source = NULL;
      
      // Extract from Diffbot siteName (Priority 1)
      if (!empty($article_data['siteName'])) {
        $new_source = $this->cleanNewsSourceName($article_data['siteName']);
        $this->logger()->info('Extracted news source from Diffbot siteName: @source', [
          '@source' => $new_source,
        ]);
      }
      // Fallback to URL extraction (Priority 2)
      elseif ($entity->hasField('field_original_url') && !$entity->get('field_original_url')->isEmpty()) {
        $url = $entity->get('field_original_url')->uri;
        $new_source = $this->extractSourceFromUrl($url);
        $this->logger()->info('Extracted news source from URL: @source from @url', [
          '@source' => $new_source,
          '@url' => $url,
        ]);
      }
      
      // Set or update the news source
      if (!empty($new_source)) {
        // Always update if current source is empty, "Source Unavailable", or different
        if (empty($current_source) || 
            $current_source === 'Source Unavailable' || 
            $current_source !== $new_source) {
          
          $entity->set('field_news_source', $new_source);
          $this->logger()->info('Updated news source for @title: @old → @new', [
            '@title' => $entity->getTitle(),
            '@old' => $current_source ?: 'EMPTY',
            '@new' => $new_source,
          ]);
        }
      } else {
        // Only set to "Source Unavailable" if field is completely empty
        if (empty($current_source)) {
          $entity->set('field_news_source', 'Source Unavailable');
          $this->logger()->warning('Could not extract news source for @title, set to unavailable', [
            '@title' => $entity->getTitle(),
          ]);
        }
      }
    }

    // Store breadcrumb
    if (!empty($article_data['breadcrumb']) && $entity->hasField('field_breadcrumb')) {
      $breadcrumb_text = is_array($article_data['breadcrumb'])
        ? implode(' > ', $article_data['breadcrumb'])
        : $article_data['breadcrumb'];
      $entity->set('field_breadcrumb', $breadcrumb_text);
    }

    // Store word count
    if (!empty($article_data['wordCount']) && $entity->hasField('field_word_count')) {
      $entity->set('field_word_count', (string) $article_data['wordCount']);
    }

    // Store language
    if (!empty($article_data['naturalLanguage']) && $entity->hasField('field_article_language')) {
      $entity->set('field_article_language', $article_data['naturalLanguage']);
    }

    // Update external image
    $this->updateExternalImage($entity, $article_data);

    // Update publication date
    $this->updatePublicationDate($entity, $article_data);

    $this->logger()->info('Updated metadata fields for: @title', [
      '@title' => $entity->getTitle(),
    ]);
  }

  /**
   * Post-processor to handle articles with unavailable scraped data.
   * 
   * Unpublishes articles where the JSON scraped data is "Scraped data unavailable"
   * to prevent incomplete articles from appearing in the public site.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check and potentially unpublish.
   */
  protected function postProcessScrapedDataStatus(EntityInterface $entity) {
    // Check if entity has the scraped data field
    if (!$entity->hasField('field_json_scraped_article_data')) {
      return;
    }

    // Get the scraped data value
    $scraped_data = $entity->get('field_json_scraped_article_data')->value;
    
    // Check for "Scraped data unavailable" message
    if (trim($scraped_data) === "Scraped data unavailable" || 
        trim($scraped_data) === "Scraped data unavailable.") {
      
      // Unpublish the article
      if ($entity->isPublished()) {
        $entity->setUnpublished();
        
        $this->logger()->warning('Unpublished article with unavailable scraped data: @title (ID: @id)', [
          '@title' => $entity->getTitle(),
          '@id' => $entity->id(),
        ]);
        
        // Set news source to indicate the issue
        if ($entity->hasField('field_news_source')) {
          $entity->set('field_news_source', 'Source Unavailable');
        }
      }
    }
  }

  /**
   * Post-process motivation analysis to unpublish nodes with pending analysis.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check and potentially unpublish.
   */
  protected function postProcessMotivationAnalysis(EntityInterface $entity) {
    // Check if entity has the motivation analysis field
    if (!$entity->hasField('field_motivation_analysis')) {
      return;
    }

    $motivation_analysis = $entity->get('field_motivation_analysis')->value;
    
    // Unpublish if analysis contains pending indicators
    if (!empty($motivation_analysis) && 
        (strpos($motivation_analysis, 'Analysis is Pending') !== false ||
         strpos($motivation_analysis, 'No analysis data available') !== false)) {
      
      // Unpublish the article
      if ($entity->isPublished()) {
        $entity->setUnpublished();
        
        $this->logger()->warning('Unpublished article with pending motivation analysis: @title (ID: @id)', [
          '@title' => $entity->getTitle(),
          '@id' => $entity->id(),
        ]);
        
        // Update the analysis field to reflect the action taken
        if (strpos($motivation_analysis, 'Analysis is Pending') !== false) {
          $entity->set('field_motivation_analysis', [
            'value' => str_replace('Analysis is Pending', 'Unpublished - Analysis Pending', $motivation_analysis),
            'format' => $entity->get('field_motivation_analysis')->format ?: 'basic_html',
          ]);
        } else {
          $entity->set('field_motivation_analysis', [
            'value' => '<p>Unpublished - No analysis data available.</p>',
            'format' => $entity->get('field_motivation_analysis')->format ?: 'basic_html',
          ]);
        }
      }
    }
  }

  /**
   * Update external image URL from article data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param array $article_data
   *   The article data.
   */
  protected function updateExternalImage(EntityInterface $entity, array $article_data) {
    if (!$entity->hasField('field_external_image_url') ||
        !$entity->get('field_external_image_url')->isEmpty()) {
      return;
    }

    // Look for images in Diffbot response
    if (!empty($article_data['images']) && is_array($article_data['images'])) {
      $image = $article_data['images'][0]; // Use first image
      if (!empty($image['url'])) {
        $entity->set('field_external_image_url', [
          'uri' => $image['url'],
          'title' => $entity->getTitle(),
        ]);

        $this->logger()->info('Set external image URL for @title: @url', [
          '@title' => $entity->getTitle(),
          '@url' => $image['url'],
        ]);
      }
    }
  }

  /**
   * Update publication date from article data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param array $article_data
   *   The article data.
   */
  protected function updatePublicationDate(EntityInterface $entity, array $article_data) {
    if (!$entity->hasField('field_publication_date') ||
        !$entity->get('field_publication_date')->isEmpty()) {
      return;
    }

    $date_value = NULL;
    $source = NULL;

    // Try date fields in order of preference: estimatedDate first, then date
    if (!empty($article_data['estimatedDate'])) {
      $date_value = $article_data['estimatedDate'];
      $source = 'diffbot_estimatedDate';
    }
    elseif (!empty($article_data['date'])) {
      $date_value = $article_data['date'];
      $source = 'diffbot_date';
    }
    elseif (!empty($article_data['publishedAt'])) {
      $date_value = $article_data['publishedAt'];
      $source = 'diffbot_publishedAt';
    }
    elseif (!empty($article_data['created'])) {
      $date_value = $article_data['created'];
      $source = 'diffbot_created';
    }

    if ($date_value) {
      try {
        $date_obj = NULL;

        // Handle different date formats
        if (is_numeric($date_value)) {
          // Timestamp format
          $date_obj = new \DateTime();
          $date_obj->setTimestamp($date_value);
        }
        else {
          // String format
          $date_obj = new \DateTime($date_value);
        }

        if ($date_obj) {
          // Always use full datetime format for datetime fields
          $formatted_date = $date_obj->format('Y-m-d\TH:i:s');

          $entity->set('field_publication_date', $formatted_date);

          $this->logger()->info('Updated publication date @date (from @source) for @title', [
            '@date' => $formatted_date,
            '@source' => $source,
            '@title' => $entity->getTitle(),
          ]);
        }
      }
      catch (\Exception $e) {
        $this->logger()->warning('Failed to parse publication date @date (from @source) for @title: @error', [
          '@date' => $date_value,
          '@source' => $source,
          '@title' => $entity->getTitle(),
          '@error' => $e->getMessage(),
        ]);
      }
    }
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
  protected function cleanNewsSourceName($source) {
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
      'theonion.com' => 'TheOnion',
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
