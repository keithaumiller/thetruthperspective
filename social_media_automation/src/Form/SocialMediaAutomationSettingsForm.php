<?php

namespace Drupal\social_media_automation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_media_automation\Service\PlatformManager;
use Drupal\social_media_automation\Service\SocialMediaScheduler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for social media automation settings.
 */
class SocialMediaAutomationSettingsForm extends ConfigFormBase {

  /**
   * The platform manager.
   *
   * @var \Drupal\social_media_automation\Service\PlatformManager
   */
  protected $platformManager;

  /**
   * The social media scheduler.
   *
   * @var \Drupal\social_media_automation\Service\SocialMediaScheduler
   */
  protected $socialMediaScheduler;

  /**
   * Constructor.
   */
  public function __construct(PlatformManager $platform_manager, SocialMediaScheduler $social_media_scheduler) {
    $this->platformManager = $platform_manager;
    $this->socialMediaScheduler = $social_media_scheduler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('social_media_automation.platform_manager'),
      $container->get('social_media_automation.scheduler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_media_automation.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_media_automation_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_media_automation.settings');

    $form['overview'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--info"><strong>Multi-Platform Social Media Automation</strong><br/>Configure credentials for each platform you want to use. The system will automatically post to all enabled platforms twice daily (morning and evening).</div>',
    ];

    // Global automation settings
    $form['automation'] = [
      '#type' => 'details',
      '#title' => $this->t('Global Automation Settings'),
      '#open' => TRUE,
    ];

    $form['automation']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Automated Posting'),
      '#default_value' => $config->get('enabled'),
      '#description' => $this->t('Enable automatic posting twice daily (morning and evening) to all configured platforms.'),
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

    // Platform configurations
    $platforms = $this->platformManager->getAllPlatforms();
    
    foreach ($platforms as $platform_name => $platform) {
      $form[$platform_name] = [
        '#type' => 'details',
        '#title' => $platform->getName() . ' Configuration',
        '#open' => FALSE,
      ];

      $form[$platform_name]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable @platform', ['@platform' => $platform->getName()]),
        '#default_value' => $config->get($platform_name . '.enabled'),
        '#description' => $this->t('Enable posting to @platform.', ['@platform' => $platform->getName()]),
      ];

      // Add platform-specific credential fields
      $this->addPlatformCredentialFields($form[$platform_name], $platform, $config, $platform_name);

      // Add test connection button
      $form[$platform_name]['test_connection'] = [
        '#type' => 'button',
        '#value' => $this->t('Test @platform Connection', ['@platform' => $platform->getName()]),
        '#ajax' => [
          'callback' => '::testPlatformConnectionCallback',
          'wrapper' => $platform_name . '-connection-status',
        ],
        '#attributes' => ['data-platform' => $platform_name],
      ];

      $form[$platform_name]['connection_status'] = [
        '#type' => 'markup',
        '#markup' => '<div id="' . $platform_name . '-connection-status"></div>',
      ];
    }

    // Statistics section
    $stats = $this->socialMediaScheduler->getStats();
    
    $form['statistics'] = [
      '#type' => 'details',
      '#title' => $this->t('Posting Statistics'),
      '#open' => FALSE,
    ];

    $form['statistics']['global_stats'] = [
      '#type' => 'table',
      '#header' => [$this->t('Metric'), $this->t('Value')],
      '#rows' => [
        ['Enabled Platforms', implode(', ', $stats['enabled_platforms']) ?: 'None'],
        ['Last Morning Post', $stats['last_morning_date']],
        ['Last Evening Post', $stats['last_evening_date']],
        ['Last Evening Type', ucfirst(str_replace('_', ' ', $stats['last_evening_type']))],
        ['Queued Posts', $stats['queue_count']],
      ],
    ];

    // Platform-specific statistics
    if (!empty($stats['enabled_platforms'])) {
      $form['statistics']['platform_stats'] = [
        '#type' => 'details',
        '#title' => $this->t('Platform-Specific Statistics'),
        '#open' => FALSE,
      ];

      foreach ($platforms as $platform_name => $platform) {
        if ($config->get($platform_name . '.enabled')) {
          $platform_stats = $this->socialMediaScheduler->getPlatformStats($platform_name);
          
          $form['statistics']['platform_stats'][$platform_name] = [
            '#type' => 'table',
            '#header' => [$platform->getName(), $this->t('Value')],
            '#rows' => [
              ['Last Post', $platform_stats['last_post_date'] ?? 'Never'],
              ['Total Posts', $platform_stats['total_posts'] ?? 0],
              ['Character Limit', $platform_stats['character_limit'] ?? 'N/A'],
              ['Last Error', $platform_stats['last_error'] ? substr($platform_stats['last_error'], 0, 100) . '...' : 'None'],
            ],
          ];
        }
      }
    }

    // Testing section
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

    $form['testing']['send_test_posts'] = [
      '#type' => 'button',
      '#value' => $this->t('Send Test Posts to All Platforms'),
      '#ajax' => [
        'callback' => '::sendTestPostsCallback',
        'wrapper' => 'test-posts-status',
      ],
    ];

    // Individual platform testing
    foreach ($platforms as $platform_name => $platform) {
      if ($config->get($platform_name . '.enabled')) {
        $form['testing']['send_test_' . $platform_name] = [
          '#type' => 'button',
          '#value' => $this->t('Test @platform Only', ['@platform' => $platform->getName()]),
          '#ajax' => [
            'callback' => '::sendTestPostToPlatformCallback',
            'wrapper' => 'test-posts-status',
          ],
          '#attributes' => ['data-platform' => $platform_name],
        ];
      }
    }

    $form['testing']['test_posts_status'] = [
      '#type' => 'markup',
      '#markup' => '<div id="test-posts-status"></div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Add platform-specific credential fields.
   */
  protected function addPlatformCredentialFields(array &$form_section, $platform, $config, $platform_name) {
    
    switch ($platform_name) {
      case 'mastodon':
        $form_section['server_url'] = [
          '#type' => 'url',
          '#title' => $this->t('Mastodon Server URL'),
          '#default_value' => $config->get($platform_name . '.server_url'),
          '#description' => $this->t('The URL of your Mastodon server (e.g., https://mastodon.social)'),
          '#placeholder' => 'https://mastodon.social',
        ];

        $form_section['access_token'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Access Token'),
          '#default_value' => $config->get($platform_name . '.access_token'),
          '#description' => $this->t('Generate this in your Mastodon account under Preferences → Development → New Application'),
          '#attributes' => ['autocomplete' => 'off'],
        ];
        break;

      case 'linkedin':
        $form_section['client_id'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Client ID'),
          '#default_value' => $config->get($platform_name . '.client_id'),
          '#description' => $this->t('From your LinkedIn app in the LinkedIn Developer Portal'),
          '#attributes' => ['autocomplete' => 'off'],
        ];

        $form_section['client_secret'] = [
          '#type' => 'password',
          '#title' => $this->t('Client Secret'),
          '#default_value' => $config->get($platform_name . '.client_secret'),
          '#description' => $this->t('Client Secret from your LinkedIn app'),
          '#attributes' => ['autocomplete' => 'off'],
        ];

        $form_section['access_token'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Access Token'),
          '#default_value' => $config->get($platform_name . '.access_token'),
          '#description' => $this->t('OAuth 2.0 access token for posting'),
          '#attributes' => ['autocomplete' => 'off'],
        ];
        break;

      case 'facebook':
        $form_section['page_id'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Page ID'),
          '#default_value' => $config->get($platform_name . '.page_id'),
          '#description' => $this->t('The ID of the Facebook page to post to'),
        ];

        $form_section['access_token'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Page Access Token'),
          '#default_value' => $config->get($platform_name . '.access_token'),
          '#description' => $this->t('Page access token from Facebook Developer Portal'),
          '#attributes' => ['autocomplete' => 'off'],
        ];
        break;

      case 'twitter':
        $form_section['description'] = [
          '#type' => 'markup',
          '#markup' => '<div class="messages messages--warning"><strong>Note:</strong> Twitter requires a paid API plan ($100/month) for posting tweets. This is included for completeness but may not be practical for most users.</div>',
        ];

        $form_section['api_key'] = [
          '#type' => 'textfield',
          '#title' => $this->t('API Key'),
          '#default_value' => $config->get($platform_name . '.api_key'),
          '#description' => $this->t('Consumer Key from your Twitter app'),
          '#attributes' => ['autocomplete' => 'off'],
        ];

        $form_section['api_secret'] = [
          '#type' => 'password',
          '#title' => $this->t('API Key Secret'),
          '#default_value' => $config->get($platform_name . '.api_secret'),
          '#description' => $this->t('Consumer Secret from your Twitter app'),
          '#attributes' => ['autocomplete' => 'off'],
        ];

        $form_section['access_token'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Access Token'),
          '#default_value' => $config->get($platform_name . '.access_token'),
          '#description' => $this->t('Access Token for posting'),
          '#attributes' => ['autocomplete' => 'off'],
        ];

        $form_section['access_secret'] = [
          '#type' => 'password',
          '#title' => $this->t('Access Token Secret'),
          '#default_value' => $config->get($platform_name . '.access_secret'),
          '#description' => $this->t('Access Token Secret for posting'),
          '#attributes' => ['autocomplete' => 'off'],
        ];
        break;
    }
  }

  /**
   * AJAX callback for testing platform connection.
   */
  public function testPlatformConnectionCallback(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $platform_name = $triggering_element['#attributes']['data-platform'];
    
    $platform = $this->platformManager->getPlatform($platform_name);
    
    if (!$platform) {
      $message = '<div class="messages messages--error">Platform not found: ' . $platform_name . '</div>';
    } else {
      try {
        $result = $platform->testConnection();
        
        if ($result) {
          $message = '<div class="messages messages--status">✅ <strong>SUCCESS!</strong> Connected to ' . $platform->getName() . '</div>';
        } else {
          $message = '<div class="messages messages--error">❌ <strong>Failed</strong> to connect to ' . $platform->getName() . '. Check your credentials.</div>';
        }
      } catch (\Exception $e) {
        $message = '<div class="messages messages--error">❌ <strong>Connection Error:</strong> ' . $e->getMessage() . '</div>';
      }
    }

    $form[$platform_name]['connection_status']['#markup'] = '<div id="' . $platform_name . '-connection-status">' . $message . '</div>';
    
    return $form[$platform_name]['connection_status'];
  }

  /**
   * AJAX callback for sending test posts to all platforms.
   */
  public function sendTestPostsCallback(array &$form, FormStateInterface $form_state) {
    $content_type = $form_state->getValue('test_content_type');
    
    $results = $this->socialMediaScheduler->sendTestPost($content_type);
    
    if (empty($results)) {
      $message = '<div class="messages messages--error">No platforms enabled or no content generated.</div>';
    } else {
      $success_count = 0;
      $total_count = count($results);
      $details = [];
      
      foreach ($results as $platform_name => $result) {
        $platform = $this->platformManager->getPlatform($platform_name);
        $platform_display_name = $platform ? $platform->getName() : $platform_name;
        
        if ($result['success']) {
          $success_count++;
          $details[] = '✅ ' . $platform_display_name . ': Success';
        } else {
          $error = $result['error'] ?? 'Unknown error';
          $details[] = '❌ ' . $platform_display_name . ': ' . $error;
        }
      }
      
      $status_class = $success_count > 0 ? 'messages--status' : 'messages--error';
      $message = '<div class="messages ' . $status_class . '">';
      $message .= '<strong>Test Results:</strong> ' . $success_count . '/' . $total_count . ' platforms succeeded<br>';
      $message .= implode('<br>', $details);
      $message .= '</div>';
    }

    $form['testing']['test_posts_status']['#markup'] = '<div id="test-posts-status">' . $message . '</div>';
    
    return $form['testing']['test_posts_status'];
  }

  /**
   * AJAX callback for sending test post to specific platform.
   */
  public function sendTestPostToPlatformCallback(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $platform_name = $triggering_element['#attributes']['data-platform'];
    $content_type = $form_state->getValue('test_content_type');
    
    $result = $this->socialMediaScheduler->sendTestPostToPlatform($platform_name, $content_type);
    
    $platform = $this->platformManager->getPlatform($platform_name);
    $platform_display_name = $platform ? $platform->getName() : $platform_name;
    
    if ($result) {
      $message = '<div class="messages messages--status">✅ Test post sent successfully to ' . $platform_display_name . '!</div>';
    } else {
      $message = '<div class="messages messages--error">❌ Failed to send test post to ' . $platform_display_name . '. Check the logs for details.</div>';
    }

    $form['testing']['test_posts_status']['#markup'] = '<div id="test-posts-status">' . $message . '</div>';
    
    return $form['testing']['test_posts_status'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('social_media_automation.settings');
    
    // Save global settings
    $config->set('enabled', $values['enabled'] ?? FALSE);
    $config->set('morning_time', $values['morning_time'] ?? '8 AM - 12 PM');
    $config->set('evening_time', $values['evening_time'] ?? '6 PM - 10 PM');
    
    // Save platform-specific settings
    $platforms = $this->platformManager->getAllPlatforms();
    
    foreach ($platforms as $platform_name => $platform) {
      $config->set($platform_name . '.enabled', $values[$platform_name]['enabled'] ?? FALSE);
      
      // Save platform-specific credentials
      switch ($platform_name) {
        case 'mastodon':
          $config->set($platform_name . '.server_url', $values[$platform_name]['server_url'] ?? '');
          $config->set($platform_name . '.access_token', $values[$platform_name]['access_token'] ?? '');
          break;

        case 'linkedin':
          $config->set($platform_name . '.client_id', $values[$platform_name]['client_id'] ?? '');
          $config->set($platform_name . '.client_secret', $values[$platform_name]['client_secret'] ?? '');
          $config->set($platform_name . '.access_token', $values[$platform_name]['access_token'] ?? '');
          break;

        case 'facebook':
          $config->set($platform_name . '.page_id', $values[$platform_name]['page_id'] ?? '');
          $config->set($platform_name . '.access_token', $values[$platform_name]['access_token'] ?? '');
          break;

        case 'twitter':
          $config->set($platform_name . '.api_key', $values[$platform_name]['api_key'] ?? '');
          $config->set($platform_name . '.api_secret', $values[$platform_name]['api_secret'] ?? '');
          $config->set($platform_name . '.access_token', $values[$platform_name]['access_token'] ?? '');
          $config->set($platform_name . '.access_secret', $values[$platform_name]['access_secret'] ?? '');
          break;
      }
    }
    
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
