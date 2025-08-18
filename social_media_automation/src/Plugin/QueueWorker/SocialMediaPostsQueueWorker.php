<?php

namespace Drupal\social_media_automation\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\social_media_automation\Service\SocialMediaScheduler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker for processing social media posts.
 *
 * @QueueWorker(
 *   id = "social_media_automation_posts",
 *   title = @Translation("Social Media Automation Posts"),
 *   cron = {"time" = 60}
 * )
 */
class SocialMediaPostsQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The social media scheduler service.
   *
   * @var \Drupal\social_media_automation\Service\SocialMediaScheduler
   */
  protected $socialMediaScheduler;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SocialMediaScheduler $social_media_scheduler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->socialMediaScheduler = $social_media_scheduler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('social_media_automation.scheduler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->socialMediaScheduler->processQueuedPost($data);
  }

}
