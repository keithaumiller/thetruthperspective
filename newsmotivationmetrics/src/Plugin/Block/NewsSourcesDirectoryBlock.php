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

    if (empty($sources)) {
      return [
        '#markup' => '<div class="news-sources-directory"><p>No news sources found.</p></div>',
        '#cache' => [
          'tags' => ['taxonomy_term_list:tags', 'node_list:article'],
          'max-age' => 3600, // Cache for 1 hour
        ],
      ];
    }

    $build = [
      '#theme' => 'news_sources_directory',
      '#sources' => $sources,
      '#total_sources' => count($sources),
      '#total_articles' => array_sum(array_column($sources, 'article_count')),
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
   * Get news sources with article counts from taxonomy terms.
   *
   * @return array
   *   Array of news source data.
   */
  protected function getNewsSourcesWithCounts() {
    try {
      // Get news source taxonomy terms (they should be in the 'tags' vocabulary now)
      // We'll identify them by looking for terms that match known news source patterns
      $query = $this->database->select('taxonomy_term_field_data', 't');
      $query->leftJoin('node__field_tags', 'nft', 't.tid = nft.field_tags_target_id');
      $query->leftJoin('node_field_data', 'n', 'nft.entity_id = n.nid AND n.type = :type AND n.status = 1', [':type' => 'article']);
      $query->fields('t', ['tid', 'name']);
      $query->addExpression('COUNT(DISTINCT n.nid)', 'article_count');
      $query->condition('t.vid', 'tags');
      $query->condition('t.status', 1);
      
      // Filter for news source patterns
      $source_patterns = [
        'CNN%',
        'Fox News%',
        'Reuters%',
        'BBC%',
        'Associated Press%',
        'NPR%',
        'New York Times%',
        'Washington Post%',
        'Wall Street Journal%',
        'The Guardian%',
        'Politico%',
        'NBC News%',
        'ABC News%',
        'CBS News%',
        'MSNBC%',
        'Bloomberg%',
        'USA Today%',
        'AP News%',
      ];
      
      $or_condition = $query->orConditionGroup();
      foreach ($source_patterns as $pattern) {
        $or_condition->condition('t.name', $pattern, 'LIKE');
      }
      $query->condition($or_condition);
      
      $query->groupBy('t.tid');
      $query->groupBy('t.name');
      $query->having('COUNT(DISTINCT n.nid) > 0'); // Only sources with articles
      $query->orderBy('article_count', 'DESC');
      $query->orderBy('t.name', 'ASC');
      
      $results = $query->execute()->fetchAll();
      
      $sources = [];
      foreach ($results as $result) {
        $term_url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $result->tid]);
        $term_link = Link::fromTextAndUrl($result->name, $term_url);
        
        $sources[] = [
          'tid' => $result->tid,
          'name' => $result->name,
          'article_count' => (int) $result->article_count,
          'url' => $term_url->toString(),
          'link' => $term_link,
        ];
      }
      
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
