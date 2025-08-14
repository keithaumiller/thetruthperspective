<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'About The Truth Perspective Analytics' Block.
 *
 * @Block(
 *   id = "about_truth_perspective_analytics",
 *   admin_label = @Translation("About The Truth Perspective Analytics"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class AboutTruthPerspectiveAnalyticsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new AboutTruthPerspectiveAnalyticsBlock.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'show_mission' => TRUE,
      'show_methodology' => TRUE,
      'show_data_sources' => TRUE,
      'show_privacy_info' => TRUE,
      'expanded_sections' => FALSE,
      'cache_duration' => 3600,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['content_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Content Settings'),
      '#open' => TRUE,
    ];

    $form['content_settings']['show_mission'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Mission Section'),
      '#default_value' => $config['show_mission'],
      '#description' => $this->t('Display the mission statement section.'),
    ];

    $form['content_settings']['show_methodology'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Methodology Section'),
      '#default_value' => $config['show_methodology'],
      '#description' => $this->t('Display the analysis methodology section.'),
    ];

    $form['content_settings']['show_data_sources'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Data Sources Section'),
      '#default_value' => $config['show_data_sources'],
      '#description' => $this->t('Display the data sources and processing section.'),
    ];

    $form['content_settings']['show_privacy_info'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Privacy & Transparency Section'),
      '#default_value' => $config['show_privacy_info'],
      '#description' => $this->t('Display the privacy and transparency information.'),
    ];

    $form['display_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Settings'),
      '#open' => FALSE,
    ];

    $form['display_settings']['expanded_sections'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expand Sections by Default'),
      '#default_value' => $config['expanded_sections'],
      '#description' => $this->t('Show all sections expanded when the page loads.'),
    ];

    $form['performance'] = [
      '#type' => 'details',
      '#title' => $this->t('Performance Settings'),
      '#open' => FALSE,
    ];

    $form['performance']['cache_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache Duration (seconds)'),
      '#default_value' => $config['cache_duration'],
      '#min' => 300,
      '#max' => 86400,
      '#description' => $this->t('How long to cache this content (longer is better for static content).'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    
    $this->configuration['show_mission'] = $values['content_settings']['show_mission'];
    $this->configuration['show_methodology'] = $values['content_settings']['show_methodology'];
    $this->configuration['show_data_sources'] = $values['content_settings']['show_data_sources'];
    $this->configuration['show_privacy_info'] = $values['content_settings']['show_privacy_info'];
    $this->configuration['expanded_sections'] = $values['display_settings']['expanded_sections'];
    $this->configuration['cache_duration'] = $values['performance']['cache_duration'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $expanded = $config['expanded_sections'];
    
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['about-truth-perspective-analytics']],
    ];

    // Main title
    $build['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => 'ℹ️ About The Truth Perspective Analytics',
      '#attributes' => ['class' => ['about-title']],
    ];

    // Mission section
    if ($config['show_mission']) {
      $build['mission'] = [
        '#type' => 'details',
        '#title' => '🎯 Our Mission & Methodology Transparency',
        '#open' => $expanded,
        '#attributes' => ['class' => ['mission-section']],
        'intro' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => 'The Truth Perspective leverages advanced AI technology to analyze news content across multiple media sources, providing transparency into narrative patterns, motivational drivers, and thematic trends in modern journalism.',
          '#attributes' => ['class' => ['mission-text']],
        ],
        'limitations' => [
          '#type' => 'details',
          '#title' => '⚠️ Critical Limitations & Bias Awareness',
          '#open' => $expanded,
          'content' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'This platform demonstrates both the capabilities and inherent dangers of using Large Language Models (LLMs) for automatic ranking and rating systems. Our analysis reveals significant inconsistencies - for example, satirical content from The Onion may receive similar "credibility scores" as traditional news from CNN, highlighting how AI systems can misinterpret context, satire, and journalistic intent.',
            '#attributes' => ['class' => ['limitations-text']],
          ],
        ],
        'black_box' => [
          '#type' => 'details',
          '#title' => '🔍 The "Black Box" Problem',
          '#open' => $expanded,
          'content' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'These AI-driven assessments operate as opaque "black boxes" where the reasoning behind scores and classifications remains largely hidden. This creates a fundamental power imbalance: those who control the LLMs - major tech corporations and AI companies - effectively control how information is ranked, rated, and perceived by the public.',
            '#attributes' => ['class' => ['black-box-text']],
          ],
        ],
        'transparency' => [
          '#type' => 'details',
          '#title' => '📊 Transparency Through Example',
          '#open' => $expanded,
          'content' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'Rather than hiding these limitations, we expose them. Our statistics comparing The Onion\'s AI-generated "bias scores" against CNN\'s demonstrate how algorithmic assessment can flatten the crucial distinction between satire and journalism, revealing the dangerous potential for AI-mediated information control.',
            '#attributes' => ['class' => ['transparency-text']],
          ],
        ],
        'scientific_value' => [
          '#type' => 'details',
          '#title' => '🔬 Scientific Value & Predictive Potential',
          '#open' => $expanded,
          'content' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'Despite these limitations, the true scientific value of this analysis lies in its potential for prediction and actionable insights. While individual article ratings may be flawed, aggregate patterns in narrative trends, source behavior, and thematic evolution may still provide valuable predictive indicators for understanding media dynamics, public discourse shifts, and information ecosystem changes over time.',
            '#attributes' => ['class' => ['scientific-value-text']],
          ],
        ],
        'purpose' => [
          '#type' => 'details',
          '#title' => '💡 Our True Purpose',
          '#open' => $expanded,
          'content' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'This platform serves as both an analytical tool and a warning: automated content ranking systems, no matter how sophisticated, embed the biases and limitations of their creators while concentrating unprecedented power over information interpretation in the hands of those who control the technology. Yet through transparent methodology and aggregate analysis, meaningful insights about information patterns may still emerge.',
            '#attributes' => ['class' => ['purpose-text']],
          ],
        ],
      ];
    }

    // Methodology section
    if ($config['show_methodology']) {
      $build['methodology'] = [
        '#type' => 'details',
        '#title' => '🔬 Analysis Methodology',
        '#open' => $expanded,
        '#attributes' => ['class' => ['methodology-section']],
        'ai_analysis' => [
          '#type' => 'details',
          '#title' => '🤖 AI-Powered Content Analysis',
          '#open' => $expanded,
          'content' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'Using Claude AI models, we evaluate article content for underlying motivations, bias indicators, and narrative frameworks. Each article undergoes comprehensive linguistic and semantic analysis.',
            '#attributes' => ['class' => ['methodology-text']],
          ],
        ],
        'entity_recognition' => [
          '#type' => 'details',
          '#title' => '🔍 Entity Recognition & Classification',
          '#open' => $expanded,
          'content' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'Automated identification of key people, organizations, locations, and concepts enables cross-reference analysis and theme tracking across multiple sources and timeframes.',
            '#attributes' => ['class' => ['methodology-text']],
          ],
        ],
        'statistical_aggregation' => [
          '#type' => 'details',
          '#title' => '📊 Statistical Aggregation',
          '#open' => $expanded,
          'content' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'Real-time metrics aggregate processing success rates, content coverage, and analytical depth to provide transparency into our system\'s capabilities and reliability.',
            '#attributes' => ['class' => ['methodology-text']],
          ],
        ],
      ];
    }

    // Data sources section
    if ($config['show_data_sources']) {
      $build['data_sources'] = [
        '#type' => 'details',
        '#title' => '📈 Data Sources & Processing',
        '#open' => $expanded,
        '#attributes' => ['class' => ['data-sources-section']],
        'list' => [
          '#theme' => 'item_list',
          '#items' => [
            'Content Extraction: Diffbot API processes raw HTML into clean, structured article data',
            'AI Analysis: Claude language models analyze motivation, sentiment, and thematic elements',
            'Taxonomy Generation: Automated tag creation based on content analysis and entity recognition',
            'Cross-Source Correlation: Pattern recognition across multiple media outlets and publication timeframes',
          ],
          '#attributes' => ['class' => ['data-sources-list']],
        ],
      ];
    }

    // Privacy section
    if ($config['show_privacy_info']) {
      $build['privacy'] = [
        '#type' => 'details',
        '#title' => '🔒 Privacy & Transparency',
        '#open' => $expanded,
        '#attributes' => ['class' => ['privacy-section']],
        'intro' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => 'All metrics represent aggregated statistics from publicly available news content. We do not track individual users, collect personal data, or store private information. Our analysis focuses exclusively on published media content and provides transparency into automated content evaluation processes.',
          '#attributes' => ['class' => ['privacy-text']],
        ],
        'details' => [
          '#theme' => 'item_list',
          '#items' => [
            '<strong>Update Frequency:</strong> Metrics refresh in real-time as new articles are processed. Analysis typically completes within minutes of publication.',
            '<strong>Data Retention:</strong> Historical analysis data enables trend tracking and longitudinal narrative studies.',
          ],
          '#attributes' => ['class' => ['privacy-details']],
        ],
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $config = $this->getConfiguration();
    return $config['cache_duration'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['newsmotivationmetrics:about_info'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

}
