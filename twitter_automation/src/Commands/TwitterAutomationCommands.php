<?php

namespace Drupal\twitter_automation\Commands;

use Drupal\twitter_automation\Service\ContentGenerator;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for testing Twitter automation.
 */
class TwitterAutomationCommands extends DrushCommands {

  /**
   * The content generator service.
   *
   * @var \Drupal\twitter_automation\Service\ContentGenerator
   */
  protected $contentGenerator;

  /**
   * Constructor.
   */
  public function __construct(ContentGenerator $content_generator) {
    $this->contentGenerator = $content_generator;
  }

  /**
   * Test content generation for Twitter automation.
   *
   * @param string $type
   *   Content type: analytics_summary, trending_topics, bias_insight
   *
   * @command twitter:test-content
   * @usage twitter:test-content analytics_summary
   *   Generate test analytics summary content
   * @usage twitter:test-content trending_topics
   *   Generate test trending topics content
   */
  public function testContent($type = 'analytics_summary') {
    $valid_types = ['analytics_summary', 'trending_topics', 'bias_insight'];
    
    if (!in_array($type, $valid_types)) {
      $this->output()->writeln('<error>Invalid content type. Use: ' . implode(', ', $valid_types) . '</error>');
      return;
    }

    $this->output()->writeln('<info>Generating content for type: ' . $type . '</info>');
    $this->output()->writeln('');

    try {
      $content = $this->contentGenerator->generateContent($type);
      
      if (empty($content)) {
        $this->output()->writeln('<error>Failed to generate content</error>');
        return;
      }

      $char_count = strlen($content);
      $status = $char_count <= 280 ? 'OK' : 'TOO LONG';
      $status_color = $char_count <= 280 ? 'info' : 'comment';

      $this->output()->writeln('<comment>--- Generated Content ---</comment>');
      $this->output()->writeln($content);
      $this->output()->writeln('');
      $this->output()->writeln('<' . $status_color . '>Character count: ' . $char_count . '/280 (' . $status . ')</' . $status_color . '>');

      if ($char_count > 280) {
        $this->output()->writeln('<comment>--- Truncated Version ---</comment>');
        $truncated = substr($content, 0, 277) . '...';
        $this->output()->writeln($truncated);
      }

    } catch (\Exception $e) {
      $this->output()->writeln('<error>Error generating content: ' . $e->getMessage() . '</error>');
    }
  }

  /**
   * Test all content types.
   *
   * @command twitter:test-all
   * @usage twitter:test-all
   *   Generate test content for all types
   */
  public function testAllContent() {
    $types = ['analytics_summary', 'trending_topics', 'bias_insight'];
    
    foreach ($types as $type) {
      $this->output()->writeln('<info>=== Testing: ' . ucfirst(str_replace('_', ' ', $type)) . ' ===</info>');
      $this->testContent($type);
      $this->output()->writeln('');
    }
  }

}
