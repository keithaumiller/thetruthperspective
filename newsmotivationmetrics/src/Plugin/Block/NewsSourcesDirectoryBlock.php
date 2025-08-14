<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'News Sources Directory' block.
 *
 * @Block(
 *   id = "news_sources_directory_block",
 *   admin_label = @Translation("News Sources Directory"),
 *   category = @Translation("News Motivation Metrics")
 * )
 */
class NewsSourcesDirectoryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new NewsSourcesDirectoryBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $sources = $this->getNewsSourcesWithCounts();

    // Debug logging
    \Drupal::logger('newsmotivationmetrics')->info('News Sources Directory Build: @count sources, empty check: @empty', [
      '@count' => count($sources),
      '@empty' => empty($sources) ? 'TRUE' : 'FALSE',
    ]);

    if (empty($sources)) {
      return [
        '#markup' => '<div class="news-sources-directory"><p>No news sources found.</p></div>',
        '#cache' => [
          'tags' => ['taxonomy_term_list:tags', 'node_list:article'],
          'max-age' => 3600, // Cache for 1 hour
        ],
      ];
    }

    $total_sources = count($sources);
    $total_articles = array_sum(array_column($sources, 'article_count'));
    
    // Debug the template variables
    \Drupal::logger('newsmotivationmetrics')->info('News Sources Directory Template Variables: sources=@sources_count, total_sources=@total_sources, total_articles=@total_articles', [
      '@sources_count' => count($sources),
      '@total_sources' => $total_sources,
      '@total_articles' => $total_articles,
    ]);

    $build = [
      '#theme' => 'news_sources_directory',
      '#sources' => $sources,
      '#total_sources' => $total_sources,
      '#total_articles' => $total_articles,
      '#attached' => [
        'library' => ['newsmotivationmetrics/news-sources-directory'],
      ],
      '#cache' => [
        'tags' => ['taxonomy_term_list:tags', 'node_list:article'],
        'max-age' => 3600, // Cache for 1 hour
      ],
    ];
    
    return $build;
  }

  /**
   * Get news sources with article counts from field_news_source field.
   *
   * @return array
   *   Array of news source data.
   */
  protected function getNewsSourcesWithCounts() {
    try {
      // Query the field_news_source field directly to get actual news sources
      $query = $this->database->select('node__field_news_source', 'nfs');
      $query->join('node_field_data', 'n', 'nfs.entity_id = n.nid AND n.type = :type AND n.status = 1', [':type' => 'article']);
      $query->fields('nfs', ['field_news_source_value']);
      $query->addExpression('COUNT(DISTINCT n.nid)', 'article_count');
      $query->condition('nfs.field_news_source_value', '', '<>');
      $query->isNotNull('nfs.field_news_source_value');
      
      // Exclude placeholder/invalid sources
      $query->condition('nfs.field_news_source_value', 'Source Unavailable', '<>');
      $query->condition('nfs.field_news_source_value', 'linkedin.com', '<>');
      $query->condition('nfs.field_news_source_value', 'Newspapers.com', '<>');
      
      $query->groupBy('nfs.field_news_source_value');
      $query->having('COUNT(DISTINCT n.nid) > 0'); // Only sources with articles
      $query->orderBy('article_count', 'DESC');
      $query->orderBy('field_news_source_value', 'ASC');
      
      $results = $query->execute()->fetchAll();
      
      // Debug logging
      \Drupal::logger('newsmotivationmetrics')->info('News Sources Directory Query Results: @count results found', [
        '@count' => count($results),
      ]);
      
      $sources = [];
      foreach ($results as $result) {
        $source_name = $result->field_news_source_value;
        
        // Debug logging for each source name
        \Drupal::logger('newsmotivationmetrics')->info('Checking taxonomy term for source: "@source_name"', [
          '@source_name' => $source_name,
        ]);
        
        // Find corresponding taxonomy term for this news source
        $term_query = $this->database->select('taxonomy_term_field_data', 't');
        $term_query->fields('t', ['tid']);
        $term_query->condition('t.vid', 'tags');
        $term_query->condition('t.status', 1);
        $term_query->condition('t.name', $source_name);
        $term_result = $term_query->execute()->fetchField();
        
        // Debug logging for taxonomy term lookup result
        \Drupal::logger('newsmotivationmetrics')->info('Taxonomy term lookup for "@source_name": @result', [
          '@source_name' => $source_name,
          '@result' => $term_result ? "Found TID {$term_result}" : 'Not found',
        ]);
        
        if ($term_result) {
          // Create link to taxonomy term page
          $term_url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $term_result]);
          $term_link = Link::fromTextAndUrl($source_name, $term_url);
          
          $sources[] = [
            'tid' => $term_result,
            'name' => $source_name,
            'article_count' => (int) $result->article_count,
            'url' => $term_url->toString(),
            'link' => $term_link,
          ];
        } else {
          // No taxonomy term exists yet, just show as text for now
          $sources[] = [
            'tid' => null,
            'name' => $source_name,
            'article_count' => (int) $result->article_count,
            'url' => null,
            'link' => null,
          ];
        }
      }
      
      // Debug logging
      \Drupal::logger('newsmotivationmetrics')->info('News Sources Directory Final Sources: @count sources processed', [
        '@count' => count($sources),
      ]);
      
      return $sources;
      
    } catch (\Exception $e) {
      \Drupal::logger('newsmotivationmetrics')->error('Error fetching news sources for directory: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['taxonomy_term_list:tags', 'node_list:article']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['languages:language_content']);
  }

}
