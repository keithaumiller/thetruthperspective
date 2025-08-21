<?php

namespace Drupal\newsmotivationmetrics\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Drush commands for managing The Truth Perspective logging levels.
 */
class LoggingCommands extends DrushCommands {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Show current logging configuration.
   *
   * @command thetruthperspective:logging:status
   * @aliases ttplog-status
   * @usage thetruthperspective:logging:status
   *   Show current logging levels for all modules.
   */
  public function loggingStatus() {
    $config = $this->configFactory->get('thetruthperspective.logging');
    
    $levels = [
      1 => 'ERROR ONLY',
      2 => 'WARNING & ERROR',
      3 => 'INFO, WARNING & ERROR',
      4 => 'ALL (INCLUDING DEBUG)',
    ];
    
    $global_level = $config->get('global.level') ?? 1;
    
    $this->output()->writeln('');
    $this->output()->writeln('<info>=== The Truth Perspective Logging Status ===</info>');
    $this->output()->writeln('');
    $this->output()->writeln("<info>Global Level:</info> {$levels[$global_level]} ($global_level)");
    $this->output()->writeln('');
    $this->output()->writeln('<info>Module Overrides:</info>');
    
    $modules = [
      'news_extractor',
      'newsmotivationmetrics',
      'social_media_automation',
      'ai_conversation',
      'job_application_automation',
      'twitter_automation',
    ];
    
    foreach ($modules as $module) {
      $module_level = $config->get("modules.{$module}.level");
      if ($module_level !== NULL) {
        $level_name = $levels[$module_level] ?? 'UNKNOWN';
        $this->output()->writeln("  - <comment>{$module}:</comment> {$level_name} ($module_level)");
      } else {
        $this->output()->writeln("  - <comment>{$module}:</comment> Using Global ({$levels[$global_level]})");
      }
    }
    
    $this->output()->writeln('');
    $this->output()->writeln('<info>Quick Commands:</info>');
    $this->output()->writeln('  drush ttplog-error     (Set to ERROR only - PRODUCTION)');
    $this->output()->writeln('  drush ttplog-info      (Set to INFO level - DEBUGGING)');
    $this->output()->writeln('  drush ttplog-debug     (Set to DEBUG level - DEVELOPMENT)');
    $this->output()->writeln('');
  }

  /**
   * Set logging to ERROR level only (production setting).
   *
   * @command thetruthperspective:logging:error-only
   * @aliases ttplog-error
   * @usage thetruthperspective:logging:error-only
   *   Set all modules to ERROR level only for production.
   */
  public function setErrorOnly() {
    $config = $this->configFactory->getEditable('thetruthperspective.logging');
    
    $config->set('global.level', 1);
    
    // Clear all module overrides to use global setting
    $modules = [
      'news_extractor',
      'newsmotivationmetrics', 
      'social_media_automation',
      'ai_conversation',
      'job_application_automation',
      'twitter_automation',
    ];
    
    foreach ($modules as $module) {
      $config->clear("modules.{$module}.level");
    }
    
    $config->save();
    
    $this->output()->writeln('<info>✅ Logging set to ERROR ONLY for all modules (production setting)</info>');
    
    // Log this change
    \Drupal::logger('newsmotivationmetrics')->error('Logging configuration changed to ERROR ONLY via Drush command');
  }

  /**
   * Set logging to INFO level (debugging setting).
   *
   * @command thetruthperspective:logging:info
   * @aliases ttplog-info
   * @usage thetruthperspective:logging:info
   *   Set all modules to INFO level for debugging.
   */
  public function setInfoLevel() {
    $config = $this->configFactory->getEditable('thetruthperspective.logging');
    
    $config->set('global.level', 3);
    
    // Clear all module overrides to use global setting
    $modules = [
      'news_extractor',
      'newsmotivationmetrics', 
      'social_media_automation',
      'ai_conversation',
      'job_application_automation',
      'twitter_automation',
    ];
    
    foreach ($modules as $module) {
      $config->clear("modules.{$module}.level");
    }
    
    $config->save();
    
    $this->output()->writeln('<info>✅ Logging set to INFO level for all modules (debugging setting)</info>');
    
    // Log this change
    \Drupal::logger('newsmotivationmetrics')->info('Logging configuration changed to INFO level via Drush command');
  }

  /**
   * Set logging to DEBUG level (development setting).
   *
   * @command thetruthperspective:logging:debug
   * @aliases ttplog-debug
   * @usage thetruthperspective:logging:debug
   *   Set all modules to DEBUG level for development.
   */
  public function setDebugLevel() {
    $config = $this->configFactory->getEditable('thetruthperspective.logging');
    
    $config->set('global.level', 4);
    
    // Clear all module overrides to use global setting
    $modules = [
      'news_extractor',
      'newsmotivationmetrics', 
      'social_media_automation',
      'ai_conversation',
      'job_application_automation',
      'twitter_automation',
    ];
    
    foreach ($modules as $module) {
      $config->clear("modules.{$module}.level");
    }
    
    $config->save();
    
    $this->output()->writeln('<info>✅ Logging set to DEBUG level for all modules (development setting)</info>');
    $this->output()->writeln('<comment>⚠️  WARNING: This will create verbose logs. Use only for development!</comment>');
    
    // Log this change
    \Drupal::logger('newsmotivationmetrics')->warning('Logging configuration changed to DEBUG level via Drush command - verbose logging enabled');
  }

  /**
   * Set logging level for a specific module.
   *
   * @param string $module
   *   The module name.
   * @param int $level
   *   The logging level (1-4).
   *
   * @command thetruthperspective:logging:set-module
   * @aliases ttplog-set
   * @usage thetruthperspective:logging:set-module social_media_automation 1
   *   Set social_media_automation to ERROR only.
   */
  public function setModuleLevel(string $module, int $level) {
    if ($level < 1 || $level > 4) {
      $this->output()->writeln('<error>❌ Invalid level. Use 1 (ERROR), 2 (WARNING), 3 (INFO), or 4 (DEBUG)</error>');
      return;
    }
    
    $config = $this->configFactory->getEditable('thetruthperspective.logging');
    $config->set("modules.{$module}.level", $level);
    $config->save();
    
    $levels = [
      1 => 'ERROR ONLY',
      2 => 'WARNING & ERROR',
      3 => 'INFO, WARNING & ERROR',
      4 => 'ALL (INCLUDING DEBUG)',
    ];
    
    $this->output()->writeln("<info>✅ Module '{$module}' logging set to: {$levels[$level]}</info>");
    
    // Log this change
    \Drupal::logger('newsmotivationmetrics')->info("Logging level for module '{$module}' changed to {$levels[$level]} via Drush command");
  }

}
