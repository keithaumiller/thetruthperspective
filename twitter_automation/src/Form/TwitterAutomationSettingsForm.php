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
    $bearer_token = $form_state->getValue('bearer_token');
    
    if (empty($bearer_token)) {
      $message = '<div class="messages messages--error">' . $this->t('Please enter a Bearer Token first.') . '</div>';
    } else {
      // Temporarily set the token for testing
      $config = \Drupal::service('config.factory')->getEditable('twitter_automation.settings');
      $original_token = $config->get('bearer_token');
      $config->set('bearer_token', $bearer_token)->save();
      
      $result = $this->twitterApiClient->testConnection();
      
      // Restore original token if test failed
      if (!$result) {
        $config->set('bearer_token', $original_token)->save();
        $message = '<div class="messages messages--error">' . $this->t('Connection failed. Please check your Bearer Token.') . '</div>';
      } else {
        $message = '<div class="messages messages--status">' . $this->t('Connection successful!') . '</div>';
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
