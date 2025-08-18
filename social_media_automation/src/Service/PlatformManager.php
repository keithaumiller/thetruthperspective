<?php

namespace Drupal\social_media_automation\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\social_media_automation\Service\Platform\PlatformInterface;

/**
 * Platform manager service for social media automation.
 * 
 * Manages registration and access to different social media platform clients.
 */
class PlatformManager {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Registered platform clients.
   *
   * @var array
   */
  protected $platforms = [];

  /**
   * Platform client instances.
   *
   * @var array
   */
  protected $instances = [];

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('social_media_automation');
    $this->registerDefaultPlatforms();
  }

  /**
   * Register default platform clients.
   */
  protected function registerDefaultPlatforms() {
    $this->registerPlatform('mastodon', 'Drupal\social_media_automation\Service\Platform\MastodonClient');
    $this->registerPlatform('linkedin', 'Drupal\social_media_automation\Service\Platform\LinkedInClient');
    $this->registerPlatform('facebook', 'Drupal\social_media_automation\Service\Platform\FacebookClient');
    $this->registerPlatform('twitter', 'Drupal\social_media_automation\Service\Platform\TwitterClient');
  }

  /**
   * Register a platform client.
   *
   * @param string $machine_name
   *   The platform machine name.
   * @param string $class_name
   *   The platform client class name.
   */
  public function registerPlatform(string $machine_name, string $class_name) {
    $this->platforms[$machine_name] = $class_name;
  }

  /**
   * Get a platform client instance.
   *
   * @param string $machine_name
   *   The platform machine name.
   *
   * @return \Drupal\social_media_automation\Service\Platform\PlatformInterface|null
   *   The platform client or NULL if not found.
   */
  public function getPlatform(string $machine_name): ?PlatformInterface {
    if (!isset($this->platforms[$machine_name])) {
      return NULL;
    }

    if (!isset($this->instances[$machine_name])) {
      // Use the service container to get properly configured platform instances
      $service_id = "social_media_automation.platform.{$machine_name}";
      
      try {
        if (\Drupal::hasService($service_id)) {
          $this->instances[$machine_name] = \Drupal::service($service_id);
        } else {
          $this->logger->warning('Platform service not found: @service_id', ['@service_id' => $service_id]);
          return NULL;
        }
      } catch (\Exception $e) {
        $this->logger->error('Error getting platform service @service_id: @error', [
          '@service_id' => $service_id,
          '@error' => $e->getMessage()
        ]);
        return NULL;
      }
    }

    return $this->instances[$machine_name];
  }

  /**
   * Get all registered platforms.
   *
   * @return array
   *   Array of platform machine names.
   */
  public function getAvailablePlatforms(): array {
    return array_keys($this->platforms);
  }

  /**
   * Get all registered platform instances.
   *
   * @return array
   *   Array of platform instances keyed by machine name.
   */
  public function getAllPlatforms(): array {
    $all_platforms = [];

    foreach ($this->getAvailablePlatforms() as $platform_name) {
      $platform = $this->getPlatform($platform_name);
      if ($platform) {
        $all_platforms[$platform_name] = $platform;
      }
    }

    return $all_platforms;
  }

  /**
   * Get all enabled platforms.
   *
   * @return array
   *   Array of enabled platform instances.
   */
  public function getEnabledPlatforms(): array {
    $config = $this->configFactory->get('social_media_automation.settings');
    $enabled_platforms = [];

    foreach ($this->getAvailablePlatforms() as $platform_name) {
      if ($config->get("{$platform_name}.enabled")) {
        $platform = $this->getPlatform($platform_name);
        if ($platform && $platform->isConfigured()) {
          $enabled_platforms[$platform_name] = $platform;
        }
      }
    }

    return $enabled_platforms;
  }

  /**
   * Get platform status for all platforms.
   *
   * @return array
   *   Array of platform status information.
   */
  public function getPlatformStatus(): array {
    $status = [];
    $config = $this->configFactory->get('social_media_automation.settings');

    foreach ($this->getAvailablePlatforms() as $platform_name) {
      $platform = $this->getPlatform($platform_name);
      if ($platform) {
        $status[$platform_name] = [
          'name' => $platform->getName(),
          'enabled' => $config->get("{$platform_name}.enabled", FALSE),
          'configured' => $platform->isConfigured(),
          'connected' => $platform->isConfigured() ? $platform->testConnection() : FALSE,
          'character_limit' => $platform->getCharacterLimit(),
          'features' => $platform->getSupportedFeatures(),
        ];
      }
    }

    return $status;
  }

  /**
   * Test all platform connections.
   *
   * @return array
   *   Array of connection test results.
   */
  public function testAllConnections(): array {
    $results = [];

    foreach ($this->getEnabledPlatforms() as $platform_name => $platform) {
      $results[$platform_name] = [
        'name' => $platform->getName(),
        'success' => $platform->testConnection(),
      ];
    }

    return $results;
  }

  /**
   * Post content to all enabled platforms.
   *
   * @param array $content
   *   Array of platform-specific content.
   * @param array $options
   *   Optional posting parameters.
   *
   * @return array
   *   Array of posting results by platform.
   */
  public function postToAllPlatforms(array $content, array $options = []): array {
    $results = [];

    foreach ($this->getEnabledPlatforms() as $platform_name => $platform) {
      if (isset($content[$platform_name])) {
        $result = $platform->postContent($content[$platform_name], $options);
        $results[$platform_name] = [
          'success' => $result !== FALSE,
          'data' => $result,
        ];

        if ($result !== FALSE) {
          $this->logger->info('Successfully posted to @platform', ['@platform' => $platform->getName()]);
        } else {
          $this->logger->error('Failed to post to @platform', ['@platform' => $platform->getName()]);
        }
      }
    }

    return $results;
  }

}
