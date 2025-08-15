<?php

namespace Drupal\twitter_automation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\twitter_automation\Service\TwitterScheduler;
use Drupal\twitter_automation\Service\ContentGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Twitter automation admin pages.
 */
class TwitterAutomationController extends ControllerBase {

  /**
   * The Twitter scheduler service.
   *
   * @var \Drupal\twitter_automation\Service\TwitterScheduler
   */
  protected $twitterScheduler;

  /**
   * The content generator service.
   *
   * @var \Drupal\twitter_automation\Service\ContentGenerator
   */
  protected $contentGenerator;

  /**
   * Constructor.
   */
  public function __construct(TwitterScheduler $twitter_scheduler, ContentGenerator $content_generator) {
    $this->twitterScheduler = $twitter_scheduler;
    $this->contentGenerator = $content_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('twitter_automation.scheduler'),
      $container->get('twitter_automation.content_generator')
    );
  }

  /**
   * Twitter automation dashboard page.
   */
  public function dashboard() {
    $config = $this->config('twitter_automation.settings');
    $stats = $this->twitterScheduler->getStats();

    $build = [];

    // Status overview
    $build['status'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Automation Status'),
    ];

    $status = $config->get('enabled') ? $this->t('Enabled') : $this->t('Disabled');
    $status_class = $config->get('enabled') ? 'color: green;' : 'color: red;';

    $build['status']['current_status'] = [
      '#markup' => '<p><strong>' . $this->t('Status:') . '</strong> <span style="' . $status_class . '">' . $status . '</span></p>',
    ];

    // Statistics table
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

    // Content preview
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
      try {
        $content = $this->contentGenerator->generateContent($type);
        $char_count = strlen($content);
        $char_status = $char_count <= 280 ? 'color: green;' : 'color: red;';
        
        $build['preview'][$type] = [
          '#type' => 'details',
          '#title' => $label . ' (' . $char_count . ' characters)',
          '#open' => FALSE,
        ];

        $build['preview'][$type]['content'] = [
          '#markup' => '<div style="border: 1px solid #ccc; padding: 10px; background: #f9f9f9; white-space: pre-wrap;">' . 
                      htmlspecialchars($content) . 
                      '</div><p style="' . $char_status . '"><small>Character count: ' . $char_count . '/280</small></p>',
        ];
      } catch (\Exception $e) {
        $build['preview'][$type] = [
          '#type' => 'details',
          '#title' => $label . ' (Error)',
          '#open' => FALSE,
        ];

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
        <p><em>' . $this->t('Posts are automatically queued during these time windows and sent once per day.') . '</em></p>
      ',
    ];

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
          'url' => \Drupal\Core\Url::fromRoute('twitter_automation.settings'),
          'attributes' => ['class' => ['button']],
        ],
        'logs' => [
          'title' => $this->t('View Logs'),
          'url' => \Drupal\Core\Url::fromRoute('dblog.overview', [], ['query' => ['type' => ['twitter_automation']]]),
          'attributes' => ['class' => ['button']],
        ],
      ],
    ];

    return $build;
  }

}
