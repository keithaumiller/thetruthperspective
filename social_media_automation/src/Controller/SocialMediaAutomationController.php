<?php

namespace Drupal\social_media_automation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_media_automation\Service\SocialMediaScheduler;
use Drupal\social_media_automation\Service\ContentGenerator;
use Drupal\social_media_automation\Service\PlatformManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for social media automation admin pages.
 */
class SocialMediaAutomationController extends ControllerBase {

  /**
   * The social media scheduler service.
   *
   * @var \Drupal\social_media_automation\Service\SocialMediaScheduler
   */
  protected $socialMediaScheduler;

  /**
   * The content generator service.
   *
   * @var \Drupal\social_media_automation\Service\ContentGenerator
   */
  protected $contentGenerator;

  /**
   * The platform manager service.
   *
   * @var \Drupal\social_media_automation\Service\PlatformManager
   */
  protected $platformManager;

  /**
   * Constructor.
   */
  public function __construct(SocialMediaScheduler $social_media_scheduler, ContentGenerator $content_generator, PlatformManager $platform_manager) {
    $this->socialMediaScheduler = $social_media_scheduler;
    $this->contentGenerator = $content_generator;
    $this->platformManager = $platform_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('social_media_automation.scheduler'),
      $container->get('social_media_automation.content_generator'),
      $container->get('social_media_automation.platform_manager')
    );
  }

  /**
   * Social media automation dashboard page.
   */
  public function dashboard() {
    $config = $this->config('social_media_automation.settings');
    $stats = $this->socialMediaScheduler->getStats();

    $build = [];

    // Status overview
    $build['status'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Automation Status'),
    ];

    $status = $config->get('enabled') ? $this->t('Enabled') : $this->t('Disabled');
    $status_class = $config->get('enabled') ? 'color: green;' : 'color: red;';

    $enabled_platforms = $stats['enabled_platforms'];
    $platforms_text = !empty($enabled_platforms) ? implode(', ', $enabled_platforms) : $this->t('None configured');

    $build['status']['current_status'] = [
      '#markup' => '
        <p><strong>' . $this->t('Status:') . '</strong> <span style="' . $status_class . '">' . $status . '</span></p>
        <p><strong>' . $this->t('Enabled Platforms:') . '</strong> ' . $platforms_text . '</p>
        <p><strong>' . $this->t('Total Platforms:') . '</strong> ' . count($enabled_platforms) . '</p>
      ',
    ];

    // Platform status overview
    if (!empty($enabled_platforms)) {
      $build['platforms'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Platform Status'),
      ];

      $platform_rows = [];
      $platforms = $this->platformManager->getEnabledPlatforms();
      
      foreach ($platforms as $platform_name => $platform) {
        $platform_stats = $this->socialMediaScheduler->getPlatformStats($platform_name);
        $char_limit = $platform_stats['character_limit'] ?? 'N/A';
        $last_post = $platform_stats['last_post_date'] ?? 'Never';
        $total_posts = $platform_stats['total_posts'] ?? 0;
        $last_error = $platform_stats['last_error'] ? '⚠️ Error' : '✅ OK';
        
        $platform_rows[] = [
          $platform->getName(),
          $char_limit,
          $last_post,
          $total_posts,
          $last_error,
        ];
      }

      if (!empty($platform_rows)) {
        $build['platforms']['table'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Platform'),
            $this->t('Char Limit'),
            $this->t('Last Post'),
            $this->t('Total Posts'),
            $this->t('Status'),
          ],
          '#rows' => $platform_rows,
        ];
      }
    }

    // Global statistics
    $build['statistics'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Recent Activity'),
    ];

    $build['statistics']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Metric'),
        $this->t('Value'),
        $this->t('Details'),
      ],
      '#rows' => [
        [
          $this->t('Last Morning Post'),
          $stats['last_morning_date'],
          $this->t('Daily analytics summary'),
        ],
        [
          $this->t('Last Evening Post'),
          $stats['last_evening_date'],
          $this->t('Type: @type', ['@type' => ucfirst(str_replace('_', ' ', $stats['last_evening_type']))]),
        ],
        [
          $this->t('Queued Posts'),
          $stats['queue_count'],
          $this->t('Posts waiting to be sent'),
        ],
      ],
    ];

    // Content preview for each platform
    $build['preview'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Content Preview'),
    ];

    $content_types = [
      'analytics_summary' => $this->t('Analytics Summary'),
      'trending_topics' => $this->t('Trending Topics'),
      'bias_insight' => $this->t('Bias Insight'),
    ];

    foreach ($content_types as $type => $label) {
      $build['preview'][$type] = [
        '#type' => 'details',
        '#title' => $label,
        '#open' => FALSE,
      ];

      try {
        $platform_content = $this->contentGenerator->generateContent($type);
        
        if (empty($platform_content)) {
          $build['preview'][$type]['error'] = [
            '#markup' => '<div style="color: red;">No content generated - check platform configuration</div>',
          ];
          continue;
        }

        foreach ($platform_content as $platform_name => $content) {
          $platform = $this->platformManager->getPlatform($platform_name);
          $platform_display_name = $platform ? $platform->getName() : $platform_name;
          $char_limit = $platform ? $platform->getCharacterLimit() : 500;
          
          $char_count = strlen($content);
          $char_status = $char_count <= $char_limit ? 'color: green;' : 'color: red;';
          
          $build['preview'][$type][$platform_name] = [
            '#type' => 'details',
            '#title' => $platform_display_name . ' (' . $char_count . '/' . $char_limit . ' characters)',
            '#open' => FALSE,
          ];

          $build['preview'][$type][$platform_name]['content'] = [
            '#markup' => '<div style="border: 1px solid #ccc; padding: 10px; background: #f9f9f9; white-space: pre-wrap;">' . 
                        htmlspecialchars($content) . 
                        '</div><p style="' . $char_status . '"><small>Character count: ' . $char_count . '/' . $char_limit . '</small></p>',
          ];
        }
        
      } catch (\Exception $e) {
        $build['preview'][$type]['error'] = [
          '#markup' => '<div style="color: red;">Error generating content: ' . htmlspecialchars($e->getMessage()) . '</div>',
        ];
      }
    }

    // Schedule information
    $build['schedule'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Posting Schedule'),
    ];

    $build['schedule']['info'] = [
      '#markup' => '
        <p><strong>' . $this->t('Morning Posts:') . '</strong> 8:00 AM - 12:00 PM (Analytics Summary)</p>
        <p><strong>' . $this->t('Evening Posts:') . '</strong> 6:00 PM - 10:00 PM (Alternating: Trending Topics / Bias Insights)</p>
        <p><em>' . $this->t('Posts are automatically queued during these time windows and sent to all enabled platforms once per day.') . '</em></p>
        <p><strong>' . $this->t('Content Adaptation:') . '</strong> Each platform receives optimized content tailored to its character limits and features.</p>
      ',
    ];

    // Platform-specific features summary
    if (!empty($enabled_platforms)) {
      $build['features'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Platform Features'),
      ];

      $feature_rows = [];
      $platforms = $this->platformManager->getEnabledPlatforms();
      
      foreach ($platforms as $platform_name => $platform) {
        $features = $platform->getSupportedFeatures();
        $feature_list = [];
        
        if (!empty($features['hashtags'])) $feature_list[] = 'Hashtags';
        if (!empty($features['mentions'])) $feature_list[] = 'Mentions';
        if (!empty($features['media'])) $feature_list[] = 'Media';
        if (!empty($features['threads'])) $feature_list[] = 'Threads';
        if (!empty($features['content_warnings'])) $feature_list[] = 'Content Warnings';
        
        $feature_rows[] = [
          $platform->getName(),
          $platform->getCharacterLimit(),
          !empty($feature_list) ? implode(', ', $feature_list) : 'Basic text',
        ];
      }

      if (!empty($feature_rows)) {
        $build['features']['table'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Platform'),
            $this->t('Character Limit'),
            $this->t('Supported Features'),
          ],
          '#rows' => $feature_rows,
        ];
      }
    }

    // Quick actions
    $build['actions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Quick Actions'),
    ];

    $build['actions']['links'] = [
      '#theme' => 'links',
      '#links' => [
        'settings' => [
          'title' => $this->t('Configure Settings'),
          'url' => \Drupal\Core\Url::fromRoute('social_media_automation.settings'),
          'attributes' => ['class' => ['button']],
        ],
        'logs' => [
          'title' => $this->t('View Logs'),
          'url' => \Drupal\Core\Url::fromRoute('dblog.overview', [], ['query' => ['type' => ['social_media_automation']]]),
          'attributes' => ['class' => ['button']],
        ],
      ],
    ];

    // Migration notice if Twitter automation still exists
    if (\Drupal::moduleHandler()->moduleExists('twitter_automation')) {
      $build['migration_notice'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Migration Notice'),
      ];

      $build['migration_notice']['message'] = [
        '#markup' => '<div class="messages messages--warning"><strong>Migration Available:</strong> The old Twitter automation module is still installed. Once you\'ve verified that this unified system works correctly, you can safely uninstall the twitter_automation module to avoid conflicts.</div>',
      ];
    }

    return $build;
  }

}
