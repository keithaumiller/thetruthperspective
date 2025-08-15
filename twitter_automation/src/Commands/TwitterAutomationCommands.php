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
   * Test OAuth connection to Twitter.
   *
   * @command twitter:test-oauth
   * @usage twitter:test-oauth
   *   Test OAuth connection with saved credentials
   */
  public function testOAuth() {
    $config = \Drupal::config('twitter_automation.settings');
    $api_key = $config->get('api_key');
    $api_secret = $config->get('api_secret');
    $access_token = $config->get('access_token');
    $access_secret = $config->get('access_secret');

    if (empty($api_key) || empty($api_secret) || empty($access_token) || empty($access_secret)) {
      $this->output()->writeln('<error>Missing OAuth credentials. Please configure in admin settings.</error>');
      return;
    }

    $this->output()->writeln('<info>Testing OAuth credentials...</info>');
    $this->output()->writeln('API Key: ' . substr($api_key, 0, 10) . '...');
    $this->output()->writeln('Access Token: ' . substr($access_token, 0, 10) . '...');

    try {
      $client = \Drupal::httpClient();
      $url = 'https://api.twitter.com/1.1/account/verify_credentials.json';
      
      // Simple OAuth test
      $oauth_params = [
        'oauth_consumer_key' => $api_key,
        'oauth_token' => $access_token,
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_timestamp' => (string) time(),
        'oauth_nonce' => bin2hex(random_bytes(16)),
        'oauth_version' => '1.0',
      ];
      
      // Sort parameters
      ksort($oauth_params);
      
      // Create base string
      $base_string = 'GET&' . rawurlencode($url) . '&' . rawurlencode(http_build_query($oauth_params));
      $this->output()->writeln('<comment>Base string: ' . substr($base_string, 0, 100) . '...</comment>');
      
      // Create signing key
      $signing_key = rawurlencode($api_secret) . '&' . rawurlencode($access_secret);
      
      // Generate signature
      $oauth_params['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_string, $signing_key, true));
      
      // Build authorization header
      $auth_header_parts = [];
      foreach ($oauth_params as $key => $value) {
        $auth_header_parts[] = $key . '="' . rawurlencode($value) . '"';
      }
      $auth_header = 'OAuth ' . implode(', ', $auth_header_parts);
      
      $this->output()->writeln('<comment>Authorization header: ' . substr($auth_header, 0, 100) . '...</comment>');
      
      $response = $client->get($url, [
        'headers' => [
          'Authorization' => $auth_header,
        ],
      ]);
      
      if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getBody(), true);
        $this->output()->writeln('<info>✅ Success! Connected as @' . $data['screen_name'] . '</info>');
        $this->output()->writeln('User ID: ' . $data['id']);
        $this->output()->writeln('Name: ' . $data['name']);
      } else {
        $this->output()->writeln('<error>❌ Failed: HTTP ' . $response->getStatusCode() . '</error>');
      }
      
    } catch (\Exception $e) {
      $this->output()->writeln('<error>❌ Error: ' . $e->getMessage() . '</error>');
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
