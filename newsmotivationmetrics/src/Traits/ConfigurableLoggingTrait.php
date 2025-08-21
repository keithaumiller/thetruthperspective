<?php

namespace Drupal\newsmotivationmetrics\Traits;

use Drupal\newsmotivationmetrics\Service\LoggingConfigService;

/**
 * Trait for implementing controlled logging across The Truth Perspective.
 * 
 * This trait provides standard logging methods that respect the platform's
 * logging configuration to reduce log noise in production environments.
 */
trait ConfigurableLoggingTrait {

  /**
   * The logging config service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\LoggingConfigService
   */
  protected $loggingConfigService;

  /**
   * Get the logging config service.
   *
   * @return \Drupal\newsmotivationmetrics\Service\LoggingConfigService
   */
  protected function getLoggingConfig() {
    if (!$this->loggingConfigService) {
      $this->loggingConfigService = \Drupal::service('newsmotivationmetrics.logging_config');
    }
    return $this->loggingConfigService;
  }

  /**
   * Log an error message.
   *
   * @param string $channel
   *   The logger channel.
   * @param string $message
   *   The message to log.
   * @param array $context
   *   The context array.
   */
  protected function logError(string $channel, string $message, array $context = []): void {
    $this->getLoggingConfig()->error($channel, $message, $context);
  }

  /**
   * Log a warning message.
   *
   * @param string $channel
   *   The logger channel.
   * @param string $message
   *   The message to log.
   * @param array $context
   *   The context array.
   */
  protected function logWarning(string $channel, string $message, array $context = []): void {
    $this->getLoggingConfig()->warning($channel, $message, $context);
  }

  /**
   * Log an info message.
   *
   * @param string $channel
   *   The logger channel.
   * @param string $message
   *   The message to log.
   * @param array $context
   *   The context array.
   */
  protected function logInfo(string $channel, string $message, array $context = []): void {
    $this->getLoggingConfig()->info($channel, $message, $context);
  }

  /**
   * Log a debug message.
   *
   * @param string $channel
   *   The logger channel.
   * @param string $message
   *   The message to log.
   * @param array $context
   *   The context array.
   */
  protected function logDebug(string $channel, string $message, array $context = []): void {
    $this->getLoggingConfig()->debug($channel, $message, $context);
  }

  /**
   * Check if a logging level should be recorded.
   *
   * @param int $level
   *   The log level to check.
   * @param string $channel
   *   The logger channel.
   *
   * @return bool
   *   TRUE if logging should occur.
   */
  protected function shouldLog(int $level, string $channel): bool {
    return $this->getLoggingConfig()->shouldLog($level, $channel);
  }

}
