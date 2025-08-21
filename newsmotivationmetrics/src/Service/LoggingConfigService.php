<?php

namespace Drupal\newsmotivationmetrics\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for managing logging levels across The Truth Perspective platform.
 * 
 * Provides centralized control over logging verbosity to reduce log noise
 * and improve system performance in production environments.
 */
class LoggingConfigService {

  /**
   * Log level constants.
   */
  const LOG_LEVEL_NONE = 0;
  const LOG_LEVEL_ERROR = 1;
  const LOG_LEVEL_WARNING = 2;
  const LOG_LEVEL_INFO = 3;
  const LOG_LEVEL_DEBUG = 4;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Get the current logging level.
   *
   * @param string $module
   *   The module name (optional, for module-specific overrides).
   *
   * @return int
   *   The logging level constant.
   */
  public function getLogLevel(string $module = 'default'): int {
    $config = $this->configFactory->get('thetruthperspective.logging');
    
    // Check for module-specific override first
    $module_level = $config->get("modules.{$module}.level");
    if ($module_level !== NULL) {
      return (int) $module_level;
    }
    
    // Fall back to global level
    return (int) $config->get('global.level') ?? self::LOG_LEVEL_ERROR;
  }

  /**
   * Check if a log level should be recorded.
   *
   * @param int $message_level
   *   The level of the message being logged.
   * @param string $module
   *   The module name.
   *
   * @return bool
   *   TRUE if the message should be logged.
   */
  public function shouldLog(int $message_level, string $module = 'default'): bool {
    $configured_level = $this->getLogLevel($module);
    return $message_level <= $configured_level;
  }

  /**
   * Conditional logger that respects log level configuration.
   *
   * @param string $channel
   *   The logger channel name.
   * @param int $level
   *   The log level (use class constants).
   * @param string $message
   *   The log message.
   * @param array $context
   *   The log context.
   */
  public function log(string $channel, int $level, string $message, array $context = []): void {
    if (!$this->shouldLog($level, $channel)) {
      return;
    }

    $logger = $this->loggerFactory->get($channel);
    
    switch ($level) {
      case self::LOG_LEVEL_ERROR:
        $logger->error($message, $context);
        break;
      case self::LOG_LEVEL_WARNING:
        $logger->warning($message, $context);
        break;
      case self::LOG_LEVEL_INFO:
        $logger->info($message, $context);
        break;
      case self::LOG_LEVEL_DEBUG:
        $logger->debug($message, $context);
        break;
    }
  }

  /**
   * Convenience method for error logging.
   */
  public function error(string $channel, string $message, array $context = []): void {
    $this->log($channel, self::LOG_LEVEL_ERROR, $message, $context);
  }

  /**
   * Convenience method for warning logging.
   */
  public function warning(string $channel, string $message, array $context = []): void {
    $this->log($channel, self::LOG_LEVEL_WARNING, $message, $context);
  }

  /**
   * Convenience method for info logging.
   */
  public function info(string $channel, string $message, array $context = []): void {
    $this->log($channel, self::LOG_LEVEL_INFO, $message, $context);
  }

  /**
   * Convenience method for debug logging.
   */
  public function debug(string $channel, string $message, array $context = []): void {
    $this->log($channel, self::LOG_LEVEL_DEBUG, $message, $context);
  }

  /**
   * Get human-readable log level names.
   *
   * @return array
   *   Array of level constants to human names.
   */
  public function getLogLevelNames(): array {
    return [
      self::LOG_LEVEL_NONE => 'None (Disabled)',
      self::LOG_LEVEL_ERROR => 'Error Only',
      self::LOG_LEVEL_WARNING => 'Warning & Error',
      self::LOG_LEVEL_INFO => 'Info, Warning & Error',
      self::LOG_LEVEL_DEBUG => 'All (Debug, Info, Warning & Error)',
    ];
  }

  /**
   * Update logging configuration.
   *
   * @param int $global_level
   *   The global logging level.
   * @param array $module_overrides
   *   Module-specific logging overrides.
   */
  public function updateConfig(int $global_level, array $module_overrides = []): void {
    $config = $this->configFactory->getEditable('thetruthperspective.logging');
    
    $config->set('global.level', $global_level);
    
    if (!empty($module_overrides)) {
      foreach ($module_overrides as $module => $level) {
        $config->set("modules.{$module}.level", $level);
      }
    }
    
    $config->save();
  }

}
