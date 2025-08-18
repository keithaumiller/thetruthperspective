<?php

namespace Drupal\social_media_automation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor.
   */
  public function __construct(PlatformManager $platform_manager, SocialMediaScheduler $social_media_scheduler, LoggerChannelFactoryInterface $logger_factory) {
    $this->platformManager = $platform_manager;
    $this->socialMediaScheduler = $social_media_scheduler;
    $this->logger = $logger_factory->get('social_media_automation');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('social_media_automation.platform_manager'),
      $container->get('social_media_automation.scheduler'),
      $container->get('logger.factory')
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
      $form['platforms'] = $form['platforms'] ?? [
        '#type' => 'details',
        '#title' => $this->t('Platform Configurations'),
        '#open' => TRUE,
      ];
      
      $form['platforms'][$platform_name] = [
        '#type' => 'details',
        '#title' => $platform->getName() . ' Configuration',
        '#open' => $platform_name === 'mastodon', // Open Mastodon by default
      ];

      $form['platforms'][$platform_name]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable @platform', ['@platform' => $platform->getName()]),
        '#default_value' => $config->get($platform_name . '.enabled'),
        '#description' => $this->t('Enable posting to @platform.', ['@platform' => $platform->getName()]),
        '#id' => 'edit-' . $platform_name . '-enabled',
      ];

      // Add platform-specific credential fields
      $this->addPlatformCredentialFields($form['platforms'][$platform_name], $platform, $config, $platform_name);

      // Add test connection button
      $form['platforms'][$platform_name]['test_connection'] = [
        '#type' => 'button',
        '#value' => $this->t('Test @platform Connection', ['@platform' => $platform->getName()]),
        '#ajax' => [
          'callback' => '::testPlatformConnectionCallback',
          'wrapper' => $platform_name . '-connection-status',
        ],
        '#attributes' => ['data-platform' => $platform_name],
      ];

      $form['platforms'][$platform_name]['connection_status'] = [
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

    // Add debug test form section
    $form['debug_test'] = [
      '#type' => 'details',
      '#title' => $this->t('🔧 Debug Test Form'),
      '#open' => TRUE,
      '#description' => $this->t('<strong>Instructions:</strong> These fields are pre-filled with test values. Click "Save configuration" and then check the logs with:<br><code>sudo -u www-data drush watchdog:show --count=20 --type=social_media_automation | grep "DEBUG TEST"</code><br><br>This will help identify if the form submission is working properly.'),
    ];

    $form['debug_test']['test_server_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Test Server URL'),
      '#default_value' => 'https://mastodon.social',
      '#description' => $this->t('Pre-filled test URL - should appear in form submission logs'),
    ];

    $form['debug_test']['test_access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test Access Token'),
      '#default_value' => 'test_token_12345_should_appear_in_logs',
      '#description' => $this->t('Pre-filled test token - should appear in form submission logs'),
    ];

    $form['debug_test']['test_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test Checkbox'),
      '#default_value' => TRUE,
      '#description' => $this->t('Pre-checked checkbox - should show as 1 in logs'),
    ];

    $form['debug_test']['test_nested'] = [
      '#type' => 'details',
      '#title' => $this->t('Test Nested Fields'),
      '#open' => TRUE,
    ];

    $form['debug_test']['test_nested']['nested_field1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nested Field 1'),
      '#default_value' => 'nested_value_1',
      '#description' => $this->t('Should appear as test_nested[nested_field1] in logs'),
    ];

    $form['debug_test']['test_nested']['nested_field2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nested Field 2'),  
      '#default_value' => 'nested_value_2',
      '#description' => $this->t('Should appear as test_nested[nested_field2] in logs'),
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
          '#id' => 'edit-mastodon-server-url',
        ];

        $form_section['access_token'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Access Token'),
          '#default_value' => $config->get($platform_name . '.access_token'),
          '#description' => $this->t('Generate this in your Mastodon account under Preferences → Development → New Application'),
          '#attributes' => ['autocomplete' => 'off'],
          '#id' => 'edit-mastodon-access-token',
        ];
        break;

      case 'linkedin':
        $form_section['client_id'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Client ID'),
          '#default_value' => $config->get($platform_name . '.client_id'),
          '#description' => $this->t('From your LinkedIn app in the LinkedIn Developer Portal'),
          '#attributes' => ['autocomplete' => 'off'],
          '#id' => 'edit-linkedin-client-id',
        ];

        $form_section['client_secret'] = [
          '#type' => 'password',
          '#title' => $this->t('Client Secret'),
          '#default_value' => $config->get($platform_name . '.client_secret'),
          '#description' => $this->t('Client Secret from your LinkedIn app'),
          '#attributes' => ['autocomplete' => 'off'],
          '#id' => 'edit-linkedin-client-secret',
        ];

        $form_section['access_token'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Access Token'),
          '#default_value' => $config->get($platform_name . '.access_token'),
          '#description' => $this->t('OAuth 2.0 access token for posting'),
          '#attributes' => ['autocomplete' => 'off'],
          '#id' => 'edit-linkedin-access-token',
        ];
        break;

      case 'facebook':
        $form_section['page_id'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Page ID'),
          '#default_value' => $config->get($platform_name . '.page_id'),
          '#description' => $this->t('The ID of the Facebook page to post to'),
          '#id' => 'edit-facebook-page-id',
        ];

        $form_section['access_token'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Page Access Token'),
          '#default_value' => $config->get($platform_name . '.access_token'),
          '#description' => $this->t('Page access token from Facebook Developer Portal'),
          '#attributes' => ['autocomplete' => 'off'],
          '#id' => 'edit-facebook-access-token',
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
          '#id' => 'edit-twitter-api-key',
        ];

        $form_section['api_secret'] = [
          '#type' => 'password',
          '#title' => $this->t('API Key Secret'),
          '#default_value' => $config->get($platform_name . '.api_secret'),
          '#description' => $this->t('Consumer Secret from your Twitter app'),
          '#attributes' => ['autocomplete' => 'off'],
          '#id' => 'edit-twitter-api-secret',
        ];

        $form_section['access_token'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Access Token'),
          '#default_value' => $config->get($platform_name . '.access_token'),
          '#description' => $this->t('Access Token for posting'),
          '#attributes' => ['autocomplete' => 'off'],
          '#id' => 'edit-twitter-access-token',
        ];

        $form_section['access_secret'] = [
          '#type' => 'password',
          '#title' => $this->t('Access Token Secret'),
          '#default_value' => $config->get($platform_name . '.access_secret'),
          '#description' => $this->t('Access Token Secret for posting'),
          '#attributes' => ['autocomplete' => 'off'],
          '#id' => 'edit-twitter-access-secret',
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
    
    // Debug: Log all form values to help troubleshoot
    $this->logger->info('=== Form submission debug ===');
    $this->logger->info('All form values: @values', ['@values' => print_r($values, TRUE)]);
    
    // Special debug test logging
    if (isset($values['test_server_url'])) {
      $this->logger->info('🔧 DEBUG TEST - test_server_url: @url', ['@url' => $values['test_server_url']]);
    }
    if (isset($values['test_access_token'])) {
      $this->logger->info('🔧 DEBUG TEST - test_access_token: @token', ['@token' => $values['test_access_token']]);
    }
    if (isset($values['test_checkbox'])) {
      $this->logger->info('🔧 DEBUG TEST - test_checkbox: @checkbox', ['@checkbox' => $values['test_checkbox']]);
    }
    if (isset($values['test_nested'])) {
      $this->logger->info('🔧 DEBUG TEST - test_nested: @nested', ['@nested' => print_r($values['test_nested'], TRUE)]);
    }
    
      // Check for server_url and access_token at top level
      $this->logger->info('🔧 TOP LEVEL - server_url: "@url"', ['@url' => $values['server_url'] ?? 'NOT_SET']);
      $this->logger->info('🔧 TOP LEVEL - access_token: "@token"', ['@token' => $values['access_token'] ?? 'NOT_SET']);
      
      // Check for nested mastodon fields
      if (isset($values['mastodon'])) {
        $this->logger->info('🔧 NESTED MASTODON FOUND: @mastodon', ['@mastodon' => print_r($values['mastodon'], TRUE)]);
      } else {
        $this->logger->warning('🔧 NO NESTED MASTODON STRUCTURE FOUND');
      }
      
      // Check each platform structure (new platforms[platform] structure)
      foreach (['mastodon', 'linkedin', 'facebook', 'twitter'] as $platform) {
        if (isset($values['platforms'][$platform])) {
          $this->logger->info('🔧 PLATFORM STRUCTURE - platforms[@platform]: @data', [
            '@platform' => $platform,
            '@data' => print_r($values['platforms'][$platform], TRUE)
          ]);
        } else {
          $this->logger->warning('🔧 MISSING PLATFORM STRUCTURE: platforms[@platform]', ['@platform' => $platform]);
        }
        
        // Also check old structure for comparison
        if (isset($values[$platform])) {
          $this->logger->info('🔧 OLD PLATFORM STRUCTURE - @platform: @data', [
            '@platform' => $platform,
            '@data' => print_r($values[$platform], TRUE)
          ]);
        }
      }    // Save global settings
    $config->set('enabled', $values['enabled'] ?? FALSE);
    $config->set('morning_time', $values['morning_time'] ?? '8 AM - 12 PM');
    $config->set('evening_time', $values['evening_time'] ?? '6 PM - 10 PM');
    
    $this->logger->info('Global settings saved:');
    $this->logger->info('- enabled: @enabled', ['@enabled' => $values['enabled'] ?? 'FALSE']);
    
    // Save platform-specific settings
    $platforms = $this->platformManager->getAllPlatforms();
    
    foreach ($platforms as $platform_name => $platform) {
      $this->logger->info('Processing platform: @platform', ['@platform' => $platform_name]);
      
      // Check for new nested structure (platforms[platform_name])
      if (isset($values['platforms'][$platform_name]) && is_array($values['platforms'][$platform_name])) {
        $platform_values = $values['platforms'][$platform_name];
        $this->logger->info('Found NEW nested platform @platform values: @values', [
          '@platform' => $platform_name,
          '@values' => print_r($platform_values, TRUE),
        ]);
      }
      // Check if platform data exists in old nested form values (platform_name directly)
      elseif (isset($values[$platform_name]) && is_array($values[$platform_name])) {
        $platform_values = $values[$platform_name];
        $this->logger->info('Found OLD nested platform @platform values: @values', [
          '@platform' => $platform_name,
          '@values' => print_r($platform_values, TRUE),
        ]);
      } 
      // Handle flattened form structure (fallback)
      else {
        $this->logger->info('Using flattened form structure for @platform', ['@platform' => $platform_name]);
        
        // For Mastodon, map the top-level fields
        if ($platform_name === 'mastodon') {
          $platform_values = [
            'enabled' => $values['mastodon']['enabled'] ?? FALSE,  // Look for nested first
            'server_url' => $values['server_url'] ?? '',
            'access_token' => $values['access_token'] ?? '',
          ];
          
          // If nested structure exists, use it instead
          if (isset($values['mastodon']) && is_array($values['mastodon'])) {
            $platform_values = $values['mastodon'];
          }
        } else {
          // For other platforms, skip if no nested structure
          $this->logger->warning('No form values found for platform: @platform', ['@platform' => $platform_name]);
          continue;
        }
      }
      
      if (!empty($platform_values)) {
        $this->logger->info('Platform @platform values: @values', [
          '@platform' => $platform_name,
          '@values' => print_r($platform_values, TRUE),
        ]);
        
        $config->set($platform_name . '.enabled', $platform_values['enabled'] ?? FALSE);
        $this->logger->info('Set @platform.enabled = @value', [
          '@platform' => $platform_name,
          '@value' => $platform_values['enabled'] ?? 'FALSE',
        ]);
        
        // Save platform-specific credentials
        switch ($platform_name) {
          case 'mastodon':
            $config->set($platform_name . '.server_url', $platform_values['server_url'] ?? '');
            $config->set($platform_name . '.access_token', $platform_values['access_token'] ?? '');
            $this->logger->info('Saved Mastodon credentials:');
            $this->logger->info('- server_url: @url', ['@url' => $platform_values['server_url'] ?? 'EMPTY']);
            $this->logger->info('- access_token: @token_length chars', ['@token_length' => strlen($platform_values['access_token'] ?? '')]);
            break;

          case 'linkedin':
            $config->set($platform_name . '.client_id', $platform_values['client_id'] ?? '');
            $config->set($platform_name . '.client_secret', $platform_values['client_secret'] ?? '');
            $config->set($platform_name . '.access_token', $platform_values['access_token'] ?? '');
            break;

          case 'facebook':
            $config->set($platform_name . '.page_id', $platform_values['page_id'] ?? '');
            $config->set($platform_name . '.access_token', $platform_values['access_token'] ?? '');
            break;

          case 'twitter':
            $config->set($platform_name . '.api_key', $platform_values['api_key'] ?? '');
            $config->set($platform_name . '.api_secret', $platform_values['api_secret'] ?? '');
            $config->set($platform_name . '.access_token', $platform_values['access_token'] ?? '');
            $config->set($platform_name . '.access_secret', $platform_values['access_secret'] ?? '');
            break;
        }
      }
    }
    
    // Save the configuration
    $config->save();
    $this->logger->info('Configuration saved successfully');
    
    // Verify the saved configuration
    $saved_config = $this->configFactory->get('social_media_automation.settings');
    $this->logger->info('Verification - Mastodon server_url after save: @url', [
      '@url' => $saved_config->get('mastodon.server_url') ?? 'NULL',
    ]);
    $this->logger->info('Verification - Mastodon access_token length after save: @length', [
      '@length' => strlen($saved_config->get('mastodon.access_token') ?? ''),
    ]);

    parent::submitForm($form, $form_state);
  }

}
