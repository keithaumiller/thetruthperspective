<?php

namespace Drupal\newsmotivationmetrics\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Methodology' Block.
 *
 * @Block(
 *   id = "methodology_block",
 *   admin_label = @Translation("Methodology & About"),
 *   category = @Translation("News Motivation Metrics"),
 * )
 */
class MethodologyBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'details',
      '#title' => 'ℹ️ About The Truth Perspective Analytics',
      '#open' => FALSE,
      '#attributes' => ['class' => ['methodology-explanation']],
      'content' => [
        '#markup' => $this->getMethodologyContent(),
      ],
      '#cache' => [
        'max-age' => 3600, // Cache for 1 hour - this content doesn't change often
        'tags' => ['newsmotivationmetrics:methodology'],
      ],
    ];
  }

  /**
   * Get methodology content for explanation section.
   */
  protected function getMethodologyContent(): string {
    return '
      <div class="explanation-content">
        <h3>🎯 Our Mission</h3>
        <p>The Truth Perspective leverages advanced AI technology to analyze news content across multiple media sources, providing transparency into narrative patterns, motivational drivers, and thematic trends in modern journalism.</p>
        
        <h3>🔬 Analysis Methodology</h3>
        <div class="methodology-grid">
          <div class="method-card">
            <h4>🤖 AI-Powered Content Analysis</h4>
            <p>Using Claude AI models, we evaluate article content for underlying motivations, bias indicators, and narrative frameworks. Each article undergoes comprehensive linguistic and semantic analysis.</p>
          </div>
          <div class="method-card">
            <h4>🔍 Entity Recognition & Classification</h4>
            <p>Automated identification of key people, organizations, locations, and concepts enables cross-reference analysis and theme tracking across multiple sources and timeframes.</p>
          </div>
          <div class="method-card">
            <h4>📊 Statistical Aggregation</h4>
            <p>Real-time metrics aggregate processing success rates, content coverage, and analytical depth to provide transparency into our system\'s capabilities and reliability.</p>
          </div>
        </div>
        
        <h3>📈 Data Sources & Processing</h3>
        <ul class="data-sources">
          <li><strong>Content Extraction:</strong> Diffbot API processes raw HTML into clean, structured article data</li>
          <li><strong>AI Analysis:</strong> Claude language models analyze motivation, sentiment, and thematic elements</li>
          <li><strong>Taxonomy Generation:</strong> Automated tag creation based on content analysis and entity recognition</li>
          <li><strong>Cross-Source Correlation:</strong> Pattern recognition across multiple media outlets and publication timeframes</li>
        </ul>
        
        <h3>🔒 Privacy & Transparency</h3>
        <p>All metrics represent <strong>aggregated statistics</strong> from publicly available news content. We do not track individual users, collect personal data, or store private information. Our analysis focuses exclusively on published media content and provides transparency into automated content evaluation processes.</p>
        
        <div class="update-info">
          <p><strong>Update Frequency:</strong> Metrics refresh in real-time as new articles are processed. Analysis typically completes within minutes of publication.</p>
          <p><strong>Data Retention:</strong> Historical analysis data enables trend tracking and longitudinal narrative studies.</p>
        </div>
      </div>
    ';
  }

}
