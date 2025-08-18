<?php

namespace Drupal\twitter_automation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\twitter_automation\Service\TwitterApiClient;
use Drupal\twitter_automation\Service\TwitterScheduler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Twitter automation settings.
 */
class TwitterAutomationSettingsForm extends ConfigFormBase {

  /**
   * The Twitter API client.
   *
   * @var \Drupal\twitter_automation\Service\TwitterApiClient
   */
  protected $twitterApiClient;

  /**
   * The Twitter scheduler.
   *
   * @var \Drupal\twitter_automation\Service\TwitterScheduler
   */
  protected $twitterScheduler;

  /**
   * Constructor.
   */
  public function __construct(TwitterApiClient $twitter_api_client, TwitterScheduler $twitter_scheduler) {
    $this->twitterApiClient = $twitter_api_client;
    $this->twitterScheduler = $twitter_scheduler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('twitter_automation.api_client'),
      $container->get('twitter_automation.scheduler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['twitter_automation.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twitter_automation_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('twitter_automation.settings');

    $form['twitter_api'] = [
      '#type' => 'details',
      '#title' => $this->t('Twitter API Configuration'),
      '#open' => TRUE,
    ];

    $form['twitter_api']['bearer_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bearer Token (Optional)'),
      '#default_value' => $config->get('bearer_token'),
      '#description' => $this->t('Twitter API v2 Bearer Token. <strong>For testing only - posting requires OAuth credentials below.</strong>'),
      '#required' => FALSE,
      '#attributes' => ['autocomplete' => 'off'],
    ];

    $form['twitter_api']['oauth_credentials'] = [
      '#type' => 'details',
      '#title' => $this->t('OAuth 1.1a Credentials (Required for Posting)'),
      '#open' => TRUE,
      '#description' => $this->t('These credentials are required for posting tweets. Get them from your Twitter Developer app.'),
    ];

    $form['twitter_api']['oauth_credentials']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key (Consumer Key)'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('Also called Consumer Key from your Twitter app.'),
      '#required' => FALSE,
      '#attributes' => ['autocomplete' => 'off'],
    ];

    $form['twitter_api']['oauth_credentials']['api_secret'] = [
      '#type' => 'password',
      '#title' => $this->t('API Secret (Consumer Secret)'),
      '#default_value' => $config->get('api_secret'),
      '#description' => $this->t('Also called Consumer Secret from your Twitter app.'),
      '#attributes' => ['autocomplete' => 'off'],
    ];

    $form['twitter_api']['oauth_credentials']['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#default_value' => $config->get('access_token'),
      '#description' => $this->t('OAuth 1.0a Access Token from your Twitter app.'),
      '#required' => FALSE,
      '#attributes' => ['autocomplete' => 'off'],
    ];

    $form['twitter_api']['oauth_credentials']['access_secret'] = [
      '#type' => 'password',
      '#title' => $this->t('Access Token Secret'),
      '#default_value' => $config->get('access_secret'),
      '#description' => $this->t('OAuth 1.0a Access Token Secret from your Twitter app.'),
      '#attributes' => ['autocomplete' => 'off'],
    ];

    $form['twitter_api']['test_connection'] = [
      '#type' => 'button',
      '#value' => $this->t('Test Connection'),
      '#ajax' => [
        'callback' => '::testConnectionCallback',
        'wrapper' => 'connection-status',
      ],
    ];

    $form['twitter_api']['connection_status'] = [
      '#type' => 'markup',
      '#markup' => '<div id="connection-status"></div>',
    ];

    $form['automation'] = [
      '#type' => 'details',
      '#title' => $this->t('Automation Settings'),
      '#open' => TRUE,
    ];

    $form['automation']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Automated Posting'),
      '#default_value' => $config->get('enabled'),
      '#description' => $this->t('Enable automatic posting twice daily (morning and evening).'),
    ];

    $form['automation']['morning_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Morning Post Time Range'),
      '#default_value' => $config->get('morning_time') ?: '8 AM - 12 PM',
      '#description' => $this->t('Time range for morning posts (display only - actual range is 8 AM - 12 PM).'),
      '#disabled' => TRUE,
    ];

    $form['automation']['evening_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Evening Post Time Range'),
      '#default_value' => $config->get('evening_time') ?: '6 PM - 10 PM',
      '#description' => $this->t('Time range for evening posts (display only - actual range is 6 PM - 10 PM).'),
      '#disabled' => TRUE,
    ];

    // Statistics section
    $stats = $this->twitterScheduler->getStats();
    
    $form['statistics'] = [
      '#type' => 'details',
      '#title' => $this->t('Posting Statistics'),
      '#open' => FALSE,
    ];

    $form['statistics']['stats_table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Metric'), $this->t('Value')],
      '#rows' => [
        ['Last Morning Post', $stats['last_morning_date']],
        ['Last Evening Post', $stats['last_evening_date']],
        ['Last Evening Type', ucfirst(str_replace('_', ' ', $stats['last_evening_type']))],
        ['Queued Posts', $stats['queue_count']],
      ],
    ];

    $form['testing'] = [
      '#type' => 'details',
      '#title' => $this->t('Testing'),
      '#open' => FALSE,
    ];

    $form['testing']['test_content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Test Content Type'),
      '#options' => [
        'analytics_summary' => $this->t('Analytics Summary'),
        'trending_topics' => $this->t('Trending Topics'),
        'bias_insight' => $this->t('Bias Insight'),
      ],
      '#default_value' => 'analytics_summary',
    ];

    $form['testing']['send_test_tweet'] = [
      '#type' => 'button',
      '#value' => $this->t('Send Test Tweet'),
      '#ajax' => [
        'callback' => '::sendTestTweetCallback',
        'wrapper' => 'test-tweet-status',
      ],
    ];

    $form['testing']['test_tweet_status'] = [
      '#type' => 'markup',
      '#markup' => '<div id="test-tweet-status"></div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * AJAX callback for testing Twitter connection.
   */
  public function testConnectionCallback(array &$form, FormStateInterface $form_state) {
    // Get values from form (either newly entered or saved)
    $api_key = $form_state->getValue('api_key') ?: $this->config('twitter_automation.settings')->get('api_key');
    $api_secret = $form_state->getValue('api_secret') ?: $this->config('twitter_automation.settings')->get('api_secret');
    $access_token = $form_state->getValue('access_token') ?: $this->config('twitter_automation.settings')->get('access_token');
    $access_secret = $form_state->getValue('access_secret') ?: $this->config('twitter_automation.settings')->get('access_secret');
    
    if (empty($api_key) || empty($api_secret) || empty($access_token) || empty($access_secret)) {
      $message = '<div class="messages messages--error">Please enter all OAuth credentials first.</div>';
    } else {
      // Temporarily save credentials for testing
      $config = \Drupal::service('config.factory')->getEditable('twitter_automation.settings');
      $config->set('api_key', $api_key)
        ->set('api_secret', $api_secret)
        ->set('access_token', $access_token)
        ->set('access_secret', $access_secret)
        ->save();
      
      // Detailed OAuth debugging
      $debug_info = [];
      $debug_info[] = '<strong>Credential Analysis:</strong>';
      $debug_info[] = 'API Key: ' . substr($api_key, 0, 10) . '... (length: ' . strlen($api_key) . ')';
      $debug_info[] = 'API Secret: ' . substr($api_secret, 0, 10) . '... (length: ' . strlen($api_secret) . ')';
      $debug_info[] = 'Access Token: ' . substr($access_token, 0, 15) . '... (length: ' . strlen($access_token) . ')';
      $debug_info[] = 'Access Secret: ' . substr($access_secret, 0, 10) . '... (length: ' . strlen($access_secret) . ')';
      $debug_info[] = '';
      
      // Check token format
      $token_format = strpos($access_token, '-') !== false ? 'CORRECT (has dash)' : 'INCORRECT (missing dash)';
      $debug_info[] = 'Access Token Format: ' . $token_format;
      $debug_info[] = '';
      
      try {
        $client = \Drupal::httpClient();
        $url = 'https://api.twitter.com/1.1/account/verify_credentials.json';
        $method = 'GET';
        $timestamp = (string) time();
        $nonce = bin2hex(random_bytes(16));
        
        $debug_info[] = '<strong>OAuth Generation:</strong>';
        $debug_info[] = 'Timestamp: ' . $timestamp;
        $debug_info[] = 'Nonce: ' . $nonce;
        $debug_info[] = 'Method: ' . $method;
        $debug_info[] = 'URL: ' . $url;
        $debug_info[] = '';
        
        // Build OAuth parameters
        $oauth_params = [
          'oauth_consumer_key' => $api_key,
          'oauth_nonce' => $nonce,
          'oauth_signature_method' => 'HMAC-SHA1',
          'oauth_timestamp' => $timestamp,
          'oauth_token' => $access_token,
          'oauth_version' => '1.0',
        ];
        
        ksort($oauth_params);
        $param_string = http_build_query($oauth_params);
        $debug_info[] = '<strong>Parameters:</strong>';
        $debug_info[] = 'Sorted params: ' . $param_string;
        $debug_info[] = '';
        
        // Create base string
        $base_string = $method . '&' . rawurlencode($url) . '&' . rawurlencode($param_string);
        $debug_info[] = '<strong>Base String:</strong>';
        $debug_info[] = substr($base_string, 0, 200) . '...';
        $debug_info[] = '';
        
        // Create signing key
        $signing_key = rawurlencode($api_secret) . '&' . rawurlencode($access_secret);
        $debug_info[] = '<strong>Signing Key:</strong>';
        $debug_info[] = substr($signing_key, 0, 30) . '... (length: ' . strlen($signing_key) . ')';
        $debug_info[] = '';
        
        // Generate signature
        $signature = base64_encode(hash_hmac('sha1', $base_string, $signing_key, true));
        $oauth_params['oauth_signature'] = $signature;
        $debug_info[] = '<strong>Signature:</strong>';
        $debug_info[] = $signature;
        $debug_info[] = '';
        
        // Build authorization header
        $auth_parts = [];
        foreach ($oauth_params as $key => $value) {
          $auth_parts[] = $key . '="' . rawurlencode($value) . '"';
        }
        $auth_header = 'OAuth ' . implode(', ', $auth_parts);
        $debug_info[] = '<strong>Authorization Header:</strong>';
        $debug_info[] = substr($auth_header, 0, 200) . '...';
        $debug_info[] = '';
        
        // Make the request
        $debug_info[] = '<strong>Making Request...</strong>';
        $response = $client->get($url, [
          'headers' => [
            'Authorization' => $auth_header,
          ],
        ]);
        
        if ($response->getStatusCode() === 200) {
          $data = json_decode($response->getBody(), true);
          $username = $data['screen_name'] ?? 'Unknown';
          $message = '<div class="messages messages--status">✅ <strong>SUCCESS!</strong> Connected as @' . $username . '</div>';
        } else {
          $debug_info[] = 'HTTP Status: ' . $response->getStatusCode();
          $debug_info[] = 'Response: ' . $response->getBody();
          $message = '<div class="messages messages--error">❌ <strong>Failed:</strong> HTTP ' . $response->getStatusCode() . '<br><br><details><summary>Debug Info</summary><pre>' . implode("\n", $debug_info) . '</pre></details></div>';
        }
        
      } catch (\Exception $e) {
        $debug_info[] = '<strong>Exception:</strong>';
        $debug_info[] = $e->getMessage();
        $debug_info[] = '';
        
        // Check for specific error patterns
        if (strpos($e->getMessage(), '401') !== false) {
          $debug_info[] = '<strong>401 Unauthorized Analysis:</strong>';
          $debug_info[] = '• Check if app has Read/Write permissions';
          $debug_info[] = '• Verify access token was generated AFTER setting permissions';
          $debug_info[] = '• Confirm credentials are correctly mapped';
          $debug_info[] = '• Look for extra spaces or characters in credentials';
        }
        
        $message = '<div class="messages messages--error">❌ <strong>Connection Failed</strong><br><br><details><summary>Debug Info</summary><pre>' . implode("\n", $debug_info) . '</pre></details></div>';
      }
    }

    $form['twitter_api']['connection_status']['#markup'] = '<div id="connection-status">' . $message . '</div>';
    
    return $form['twitter_api']['connection_status'];
  }

  /**
   * AJAX callback for sending test tweet.
   */
  public function sendTestTweetCallback(array &$form, FormStateInterface $form_state) {
    $content_type = $form_state->getValue('test_content_type');
    
    $result = $this->twitterScheduler->sendTestTweet($content_type);
    
    if ($result) {
      $message = '<div class="messages messages--status">' . $this->t('Test tweet sent successfully!') . '</div>';
    } else {
      $message = '<div class="messages messages--error">' . $this->t('Failed to send test tweet. Check the logs for details.') . '</div>';
    }

    $form['testing']['test_tweet_status']['#markup'] = '<div id="test-tweet-status">' . $message . '</div>';
    
    return $form['testing']['test_tweet_status'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('twitter_automation.settings')
      ->set('bearer_token', $form_state->getValue('bearer_token'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('api_secret', $form_state->getValue('api_secret'))
      ->set('access_token', $form_state->getValue('access_token'))
      ->set('access_secret', $form_state->getValue('access_secret'))
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('morning_time', $form_state->getValue('morning_time'))
      ->set('evening_time', $form_state->getValue('evening_time'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
