<?php

namespace Drupal\twitter_automation\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\twitter_automation\Service\TwitterScheduler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker for processing Twitter posts.
 *
 * @QueueWorker(
 *   id = "twitter_automation_posts",
 *   title = @Translation("Twitter Automation Posts"),
 *   cron = {"time" = 60}
 * )
 */
class TwitterPostsQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The Twitter scheduler service.
   *
   * @var \Drupal\twitter_automation\Service\TwitterScheduler
   */
  protected $twitterScheduler;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TwitterScheduler $twitter_scheduler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->twitterScheduler = $twitter_scheduler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('twitter_automation.scheduler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->twitterScheduler->processQueuedPost($data);
  }

}
