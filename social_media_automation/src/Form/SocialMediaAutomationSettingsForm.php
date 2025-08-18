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

    // DISABLED FOR TESTING - Global automation settings
    /*
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
    */

    // Platform configurations - ONLY MASTODON FOR NOW
    $platforms = $this->platformManager->getAllPlatforms();
    
    foreach ($platforms as $platform_name => $platform) {
      // Skip all platforms except Mastodon for initial testing
      if ($platform_name !== 'mastodon') {
        continue;
      }
      
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

      // Add test connection button (test post for Mastodon)
      $button_text = ($platform_name === 'mastodon') 
        ? $this->t('Test @platform (Send Hello World Post)', ['@platform' => $platform->getName()])
        : $this->t('Test @platform Connection', ['@platform' => $platform->getName()]);
        
      $form['platforms'][$platform_name]['test_connection'] = [
        '#type' => 'button',
        '#value' => $button_text,
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

    // DISABLED FOR TESTING - Statistics section
    /*
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
    */

    // Testing section - MASTODON ONLY
    $form['testing'] = [
      '#type' => 'details',
      '#title' => $this->t('Testing - Mastodon Only'),
      '#open' => TRUE,
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

    // Content Preview Section
    $form['content_preview'] = [
      '#type' => 'details',
      '#title' => $this->t('🎯 AI Content Preview'),
      '#open' => TRUE,
      '#description' => $this->t('Generate social media post content using AI analysis of the most recent article. This will NOT post content - only preview what would be posted.'),
    ];

    $form['content_preview']['generate_preview'] = [
      '#type' => 'button',
      '#value' => $this->t('🤖 Generate Social Media Post Preview'),
      '#ajax' => [
        'callback' => '::generateContentPreviewCallback',
        'wrapper' => 'social-media-preview-container',
        'effect' => 'fade',
      ],
      '#attributes' => [
        'class' => ['button--primary'],
      ],
    ];

    $form['content_preview']['preview_container'] = [
      '#type' => 'markup',
      '#markup' => '<div id="social-media-preview-container"><p><em>Click "Generate Social Media Post Preview" to create AI-powered content based on your most recent article.</em></p></div>',
    ];

    // Add JavaScript for preview interactions
    $form['#attached']['library'][] = 'social_media_automation/content-preview';

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
          '#name' => 'platforms[linkedin][client_id]',
        ];

        $form_section['client_secret'] = [
          '#type' => 'password',
          '#title' => $this->t('Client Secret'),
          '#default_value' => $config->get($platform_name . '.client_secret'),
          '#description' => $this->t('Client Secret from your LinkedIn app'),
          '#attributes' => ['autocomplete' => 'off'],
          '#id' => 'edit-linkedin-client-secret',
          '#name' => 'platforms[linkedin][client_secret]',
        ];

        $form_section['access_token'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Access Token'),
          '#default_value' => $config->get($platform_name . '.access_token'),
          '#description' => $this->t('OAuth 2.0 access token for posting'),
          '#attributes' => ['autocomplete' => 'off'],
          '#id' => 'edit-linkedin-access-token',
          '#name' => 'platforms[linkedin][access_token]',
        ];
        break;

      case 'facebook':
        $form_section['page_id'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Page ID'),
          '#default_value' => $config->get($platform_name . '.page_id'),
          '#description' => $this->t('The ID of the Facebook page to post to'),
          '#id' => 'edit-facebook-page-id',
          '#name' => 'platforms[facebook][page_id]',
        ];

        $form_section['access_token'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Page Access Token'),
          '#default_value' => $config->get($platform_name . '.access_token'),
          '#description' => $this->t('Page access token from Facebook Developer Portal'),
          '#attributes' => ['autocomplete' => 'off'],
          '#id' => 'edit-facebook-access-token',
          '#name' => 'platforms[facebook][access_token]',
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
          '#name' => 'platforms[twitter][api_key]',
        ];

        $form_section['api_secret'] = [
          '#type' => 'password',
          '#title' => $this->t('API Key Secret'),
          '#default_value' => $config->get($platform_name . '.api_secret'),
          '#description' => $this->t('Consumer Secret from your Twitter app'),
          '#attributes' => ['autocomplete' => 'off'],
          '#id' => 'edit-twitter-api-secret',
          '#name' => 'platforms[twitter][api_secret]',
        ];

        $form_section['access_token'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Access Token'),
          '#default_value' => $config->get($platform_name . '.access_token'),
          '#description' => $this->t('Access Token for posting'),
          '#attributes' => ['autocomplete' => 'off'],
          '#id' => 'edit-twitter-access-token',
          '#name' => 'platforms[twitter][access_token]',
        ];

        $form_section['access_secret'] = [
          '#type' => 'password',
          '#title' => $this->t('Access Token Secret'),
          '#default_value' => $config->get($platform_name . '.access_secret'),
          '#description' => $this->t('Access Token Secret for posting'),
          '#attributes' => ['autocomplete' => 'off'],
          '#id' => 'edit-twitter-access-secret',
          '#name' => 'platforms[twitter][access_secret]',
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
        // For Mastodon, do a test post; for others, just test connection
        if ($platform_name === 'mastodon' && method_exists($platform, 'testPost')) {
          $this->logger->info('Testing Mastodon with test post...');
          $result = $platform->testPost();
          
          if ($result) {
            $message = '<div class="messages messages--status">✅ <strong>SUCCESS!</strong> Test post sent to ' . $platform->getName() . '! Check your Mastodon account.</div>';
          } else {
            $message = '<div class="messages messages--error">❌ <strong>Failed</strong> to post to ' . $platform->getName() . '. Check your credentials and logs.</div>';
          }
        } else {
          // Regular connection test for other platforms
          $result = $platform->testConnection();
          
          if ($result) {
            $message = '<div class="messages messages--status">✅ <strong>SUCCESS!</strong> Connected to ' . $platform->getName() . '</div>';
          } else {
            $message = '<div class="messages messages--error">❌ <strong>Failed</strong> to connect to ' . $platform->getName() . '. Check your credentials.</div>';
          }
        }
      } catch (\Exception $e) {
        $message = '<div class="messages messages--error">❌ <strong>Connection Error:</strong> ' . $e->getMessage() . '</div>';
        $this->logger->error('Test platform callback error: @error', ['@error' => $e->getMessage()]);
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
   * AJAX callback for generating content preview.
   */
  public function generateContentPreviewCallback(array &$form, FormStateInterface $form_state) {
    try {
      // Get most recent article
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'article')
        ->condition('status', 1)
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->accessCheck(FALSE);
      
      $nids = $query->execute();
      
      if (empty($nids)) {
        $form['content_preview']['preview_container']['#markup'] = '<div id="social-media-preview-container" class="messages messages--warning">No published articles found to generate content from.</div>';
        return $form['content_preview']['preview_container'];
      }
      
      $nid = reset($nids);
      $article = \Drupal\node\Entity\Node::load($nid);
      
      if (!$article) {
        $form['content_preview']['preview_container']['#markup'] = '<div id="social-media-preview-container" class="messages messages--error">Could not load the most recent article.</div>';
        return $form['content_preview']['preview_container'];
      }
      
      // Get AI summary
      $ai_summary = '';
      if ($article->hasField('field_ai_response') && !$article->get('field_ai_response')->isEmpty()) {
        $ai_response = $article->get('field_ai_response')->value;
        $ai_data = json_decode($ai_response, TRUE);
        if (is_array($ai_data) && isset($ai_data['summary'])) {
          $ai_summary = $ai_data['summary'];
        } elseif (is_string($ai_response) && !empty($ai_response)) {
          $ai_summary = $ai_response;
        }
      }
      
      // Fallback to body if no AI summary
      if (empty($ai_summary)) {
        if ($article->hasField('body') && !$article->get('body')->isEmpty()) {
          $body = $article->get('body')->value;
          $ai_summary = substr(strip_tags($body), 0, 500) . '...';
        }
      }
      
      if (empty($ai_summary)) {
        $form['content_preview']['preview_container']['#markup'] = '<div id="social-media-preview-container" class="messages messages--warning">No content available to generate social media post from.</div>';
        return $form['content_preview']['preview_container'];
      }
      
      // Check if AI service is available
      $container = \Drupal::getContainer();
      if (!$container->has('news_extractor.ai_processing')) {
        $form['content_preview']['preview_container']['#markup'] = '<div id="social-media-preview-container" class="messages messages--error">AI processing service not available. Please check your configuration.</div>';
        return $form['content_preview']['preview_container'];
      }
      
      $ai_service = $container->get('news_extractor.ai_processing');
      
      // Generate social media content
      $article_title = $article->getTitle();
      $article_url = $article->toUrl('canonical', ['absolute' => TRUE])->toString();
      
      $prompt = "Generate a compelling social media post for Mastodon based on this news article analysis. 

ARTICLE TITLE: {$article_title}

ARTICLE ANALYSIS: {$ai_summary}

ARTICLE URL: {$article_url}

REQUIREMENTS:
- Keep under 500 characters (Mastodon limit)
- Include relevant hashtags (2-4 maximum)
- Be engaging and informative
- Include the article URL
- Maintain journalistic credibility
- Focus on key insights from the analysis

Please respond with ONLY the social media post text, ready to publish. Do not include any additional commentary or explanation.";

      $social_media_post = $ai_service->generateAnalysis($prompt);
      
      if (empty($social_media_post)) {
        $form['content_preview']['preview_container']['#markup'] = '<div id="social-media-preview-container" class="messages messages--error">Failed to generate social media content. AI service may be unavailable.</div>';
        return $form['content_preview']['preview_container'];
      }
      
      // If AI service returns an array, extract the content
      if (is_array($social_media_post)) {
        if (isset($social_media_post['content'])) {
          $social_media_post = $social_media_post['content'];
        } elseif (isset($social_media_post['text'])) {
          $social_media_post = $social_media_post['text'];
        } else {
          $social_media_post = json_encode($social_media_post);
        }
      }
      
      // Build preview HTML
      $created_date = \Drupal::service('date.formatter')->format($article->getCreatedTime(), 'medium');
      
      $html = '<div id="social-media-preview-container" class="social-media-preview">';
      $html .= '<h3>' . $this->t('Generated Social Media Post Preview') . '</h3>';
      
      $html .= '<div class="preview-content">';
      $html .= '<div class="post-preview">';
      $html .= '<h4>' . $this->t('Post Content:') . '</h4>';
      $html .= '<div class="post-text">' . nl2br(htmlspecialchars($social_media_post)) . '</div>';
      $html .= '<div class="character-count">' . $this->t('Character count: @count', ['@count' => strlen($social_media_post)]) . '</div>';
      $html .= '</div>';
      
      $html .= '<div class="source-article">';
      $html .= '<h4>' . $this->t('Source Article:') . '</h4>';
      $html .= '<div class="article-info">';
      $html .= '<strong>' . htmlspecialchars($article_title) . '</strong><br>';
      $html .= '<small>' . $this->t('Published: @date', ['@date' => $created_date]) . '</small><br>';
      $html .= '<a href="' . $article_url . '" target="_blank">' . $article_url . '</a>';
      $html .= '</div>';
      $html .= '</div>';
      $html .= '</div>';
      
      $html .= '<div class="preview-actions">';
      $html .= '<button type="button" class="button button--primary" onclick="generateNewPreview()">' . $this->t('Generate New Version') . '</button>';
      $html .= '<button type="button" class="button" onclick="clearPreview()">' . $this->t('Clear Preview') . '</button>';
      $html .= '</div>';
      
      $html .= '</div>';
      
      $form['content_preview']['preview_container']['#markup'] = $html;
      
    } catch (\Exception $e) {
      \Drupal::logger('social_media_automation')->error('Preview generation failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      $form['content_preview']['preview_container']['#markup'] = '<div id="social-media-preview-container" class="messages messages--error">An unexpected error occurred: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }

    return $form['content_preview']['preview_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('social_media_automation.settings');
    
    // COMPREHENSIVE DEBUG LOGGING
    $this->logger->info('=== COMPLETE FORM SUBMISSION DEBUG ===');
    $this->logger->info('🔍 RAW FORM VALUES (JSON): @values', ['@values' => json_encode($values, JSON_PRETTY_PRINT)]);
    $this->logger->info('🔍 RAW FORM VALUES (PRINT_R): @values', ['@values' => print_r($values, TRUE)]);
    
    // Check each possible structure location for Mastodon data
    $this->logger->info('🔍 STRUCTURE CHECK: Checking all possible locations for Mastodon data...');
    
    // 1. Check top-level fields
    $this->logger->info('� TOP LEVEL server_url: "@value"', ['@value' => $values['server_url'] ?? 'NOT_FOUND']);
    $this->logger->info('� TOP LEVEL access_token: "@value"', ['@value' => $values['access_token'] ?? 'NOT_FOUND']);
    $this->logger->info('� TOP LEVEL enabled: "@value"', ['@value' => $values['enabled'] ?? 'NOT_FOUND']);
    
    // 2. Check platforms[mastodon] structure
    if (isset($values['platforms']['mastodon'])) {
      $this->logger->info('✅ FOUND platforms[mastodon]: @data', ['@data' => json_encode($values['platforms']['mastodon'], JSON_PRETTY_PRINT)]);
    } else {
      $this->logger->warning('❌ NOT FOUND: platforms[mastodon]');
    }
    
    // 3. Check mastodon direct structure
    if (isset($values['mastodon'])) {
      $this->logger->info('✅ FOUND direct mastodon: @data', ['@data' => json_encode($values['mastodon'], JSON_PRETTY_PRINT)]);
    } else {
      $this->logger->warning('❌ NOT FOUND: direct mastodon');
    }
    
    // 4. List ALL top-level keys for debugging
    $this->logger->info('🗂️ ALL TOP-LEVEL FORM KEYS: @keys', ['@keys' => implode(', ', array_keys($values))]);
    
    // 5. Check for any key containing "mastodon"
    foreach ($values as $key => $value) {
      if (strpos($key, 'mastodon') !== false) {
        $this->logger->info('� FOUND KEY WITH "mastodon": @key = @value', [
          '@key' => $key,
          '@value' => is_array($value) ? json_encode($value) : $value
        ]);
      }
    }
    
    // Special debug test logging
    if (isset($values['test_server_url'])) {
      $this->logger->info('🔧 DEBUG TEST - test_server_url: @url', ['@url' => $values['test_server_url']]);
    }
    if (isset($values['test_access_token'])) {
      $this->logger->info('🔧 DEBUG TEST - test_access_token: @token', ['@token' => $values['test_access_token']]);
    }    // Save global settings
    $config->set('enabled', $values['enabled'] ?? FALSE);
    $config->set('morning_time', $values['morning_time'] ?? '8 AM - 12 PM');
    $config->set('evening_time', $values['evening_time'] ?? '6 PM - 10 PM');
    
    $this->logger->info('Global settings saved:');
    $this->logger->info('- enabled: @enabled', ['@enabled' => $values['enabled'] ?? 'FALSE']);
    
    // Save platform-specific settings - MASTODON ONLY FOR TESTING
    $platforms = $this->platformManager->getAllPlatforms();
    
    foreach ($platforms as $platform_name => $platform) {
      // Skip all platforms except Mastodon for initial testing
      if ($platform_name !== 'mastodon') {
        continue;
      }
      
      $this->logger->info('Processing platform: @platform', ['@platform' => $platform_name]);
      
      // Check for new nested structure (platforms[platform_name])
      if (isset($values['platforms'][$platform_name]) && is_array($values['platforms'][$platform_name])) {
        $platform_values = $values['platforms'][$platform_name];
        $this->logger->info('✅ SUCCESS! Found nested platform @platform values: @values', [
          '@platform' => $platform_name,
          '@values' => print_r($platform_values, TRUE),
        ]);
        
        // Save the platform settings
        $config->set($platform_name . '.enabled', $platform_values['enabled'] ?? FALSE);
        
        // Save credential fields
        $required_credentials = $platform->getRequiredCredentials();
        foreach ($required_credentials as $credential => $info) {
          if (isset($platform_values[$credential])) {
            $config->set($platform_name . '.' . $credential, $platform_values[$credential]);
            $this->logger->info('Saved @platform.@credential', [
              '@platform' => $platform_name,
              '@credential' => $credential,
            ]);
          }
        }
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
            'enabled' => $values['enabled'] ?? FALSE,  // Check top-level enabled field
            'server_url' => $values['server_url'] ?? '',
            'access_token' => $values['access_token'] ?? '',
          ];
          
          // Also check if there's a platforms[mastodon] structure
          if (isset($values['platforms']['mastodon'])) {
            $platform_values = array_merge($platform_values, $values['platforms']['mastodon']);
          }
          
          // Debug the enabled field specifically
          $this->logger->info('🔧 ENABLED DEBUG - values[enabled]: "@enabled"', ['@enabled' => $values['enabled'] ?? 'NOT_SET']);
          $this->logger->info('🔧 ENABLED DEBUG - Final platform_values[enabled]: "@enabled"', ['@enabled' => $platform_values['enabled'] ?? 'NOT_SET']);
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
            // Clean up server URL - remove www. if present and ensure it's valid
            $server_url = $platform_values['server_url'] ?? '';
            if (!empty($server_url)) {
              // Remove www. prefix if present
              $server_url = preg_replace('/^https?:\/\/www\./', 'https://', $server_url);
              // Ensure https://
              if (!preg_match('/^https?:\/\//', $server_url)) {
                $server_url = 'https://' . $server_url;
              }
              $this->logger->info('🔧 URL CLEANUP - Original: "@original", Cleaned: "@cleaned"', [
                '@original' => $platform_values['server_url'] ?? '',
                '@cleaned' => $server_url
              ]);
            }
            
            $config->set($platform_name . '.server_url', $server_url);
            $config->set($platform_name . '.access_token', $platform_values['access_token'] ?? '');
            $this->logger->info('Saved Mastodon credentials:');
            $this->logger->info('- server_url: @url', ['@url' => $server_url]);
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
    $this->logger->info('✅ Configuration saved successfully');
    
    // COMPREHENSIVE DATABASE VERIFICATION
    $this->logger->info('=== DATABASE VERIFICATION ===');
    $saved_config = $this->configFactory->get('social_media_automation.settings');
    
    // Verify all Mastodon settings
    $this->logger->info('🔍 DB CHECK - mastodon.enabled: "@value"', [
      '@value' => $saved_config->get('mastodon.enabled') ? 'TRUE' : 'FALSE',
    ]);
    $this->logger->info('🔍 DB CHECK - mastodon.server_url: "@url"', [
      '@url' => $saved_config->get('mastodon.server_url') ?? 'NULL',
    ]);
    $this->logger->info('🔍 DB CHECK - mastodon.access_token: @length chars', [
      '@length' => strlen($saved_config->get('mastodon.access_token') ?? ''),
    ]);
    
    // Get raw config data to see complete structure
    $all_data = $saved_config->getRawData();
    $this->logger->info('🔍 DB CHECK - ALL CONFIG DATA: @data', [
      '@data' => json_encode($all_data, JSON_PRETTY_PRINT)
    ]);
    
    // Check if the config file exists and has the expected structure
    $config_name = 'social_media_automation.settings';
    $this->logger->info('🔍 DB CHECK - Config name: @name', ['@name' => $config_name]);
    
    // Verify what we just tried to save vs what's actually in DB
    if (isset($platform_values)) {
      $this->logger->info('📊 COMPARISON - Attempted to save: @attempted', [
        '@attempted' => json_encode($platform_values, JSON_PRETTY_PRINT)
      ]);
      $this->logger->info('📊 COMPARISON - Actually in DB: enabled=@enabled, server_url=@url, token_length=@length', [
        '@enabled' => $saved_config->get('mastodon.enabled') ? 'TRUE' : 'FALSE',
        '@url' => $saved_config->get('mastodon.server_url') ?? 'NULL',
        '@length' => strlen($saved_config->get('mastodon.access_token') ?? '')
      ]);
    }

    parent::submitForm($form, $form_state);
  }

}
