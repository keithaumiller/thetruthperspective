<?php

namespace Drupal\newsmotivationmetrics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\newsmotivationmetrics\Service\LoggingConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for platform-wide logging levels.
 */
class LoggingConfigForm extends ConfigFormBase {

  /**
   * The logging config service.
   *
   * @var \Drupal\newsmotivationmetrics\Service\LoggingConfigService
   */
  protected $loggingConfig;

  /**
   * Constructor.
   *
   * @param \Drupal\newsmotivationmetrics\Service\LoggingConfigService $logging_config
   *   The logging config service.
   */
  public function __construct(LoggingConfigService $logging_config) {
    $this->loggingConfig = $logging_config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('newsmotivationmetrics.logging_config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'thetruthperspective_logging_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['thetruthperspective.logging'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('thetruthperspective.logging');
    $level_options = $this->loggingConfig->getLogLevelNames();

    $form['info'] = [
      '#markup' => '<div class="messages messages--warning">' .
        '<h3>⚠️ Production Logging Configuration</h3>' .
        '<p><strong>Important:</strong> This controls logging verbosity for The Truth Perspective platform. ' .
        'For production environments, <strong>Error Only</strong> is recommended to reduce log noise and improve performance.</p>' .
        '<ul>' .
        '<li><strong>None:</strong> Disables all logging (not recommended)</li>' .
        '<li><strong>Error Only:</strong> Only critical errors (recommended for production)</li>' .
        '<li><strong>Warning & Error:</strong> Errors and warnings</li>' .
        '<li><strong>Info, Warning & Error:</strong> Includes informational messages</li>' .
        '<li><strong>All:</strong> Includes debug messages (development only)</li>' .
        '</ul>' .
        '</div>',
    ];

    $form['global'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Global Logging Settings'),
      '#description' => $this->t('Default logging level for all modules.'),
    ];

    $form['global']['level'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Log Level'),
      '#options' => $level_options,
      '#default_value' => $config->get('global.level') ?? LoggingConfigService::LOG_LEVEL_ERROR,
      '#description' => $this->t('This applies to all modules unless overridden below.'),
    ];

    $form['modules'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Module-Specific Overrides'),
      '#description' => $this->t('Override the global level for specific modules.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $modules = [
      'news_extractor' => 'News Extractor',
      'newsmotivationmetrics' => 'News Motivation Metrics',
      'social_media_automation' => 'Social Media Automation',
      'ai_conversation' => 'AI Conversation',
      'job_application_automation' => 'Job Application Automation',
      'twitter_automation' => 'Twitter Automation',
    ];

    foreach ($modules as $module_key => $module_name) {
      $form['modules'][$module_key] = [
        '#type' => 'select',
        '#title' => $this->t('@module Log Level', ['@module' => $module_name]),
        '#options' => ['' => '- Use Global Setting -'] + $level_options,
        '#default_value' => $config->get("modules.{$module_key}.level") ?? '',
        '#description' => $this->t('Override global setting for @module module.', ['@module' => $module_name]),
      ];
    }

    $form['current_logs'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Current Log Status'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['current_logs']['info'] = [
      '#markup' => '<div class="messages messages--status">' .
        '<h4>📊 Current Configuration Impact</h4>' .
        '<p><strong>Current Global Level:</strong> ' . $level_options[$config->get('global.level') ?? LoggingConfigService::LOG_LEVEL_ERROR] . '</p>' .
        '<p><strong>Commands to check logs:</strong></p>' .
        '<ul>' .
        '<li><code>drush watchdog:show --count=50 --severity=3</code> (Errors only)</li>' .
        '<li><code>drush watchdog:show --count=50 --type=news_extractor</code> (News Extractor)</li>' .
        '<li><code>drush watchdog:show --count=50 --type=social_media_automation</code> (Social Media)</li>' .
        '</ul>' .
        '</div>',
    ];

    $form['actions']['clear_logs'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear All Logs (Drush Required)'),
      '#submit' => ['::clearLogs'],
      '#button_type' => 'danger',
      '#attributes' => [
        'onclick' => 'return confirm("This will clear ALL Drupal logs. Are you sure?");',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    
    $global_level = (int) $values['level'];
    $module_overrides = [];
    
    $modules = [
      'news_extractor',
      'newsmotivationmetrics', 
      'social_media_automation',
      'ai_conversation',
      'job_application_automation',
      'twitter_automation',
    ];
    
    foreach ($modules as $module) {
      if (!empty($values[$module])) {
        $module_overrides[$module] = (int) $values[$module];
      }
    }

    $this->loggingConfig->updateConfig($global_level, $module_overrides);

    // Log the configuration change at error level (always visible)
    $level_names = $this->loggingConfig->getLogLevelNames();
    $this->loggingConfig->error('newsmotivationmetrics', 
      'Logging configuration updated. Global level: @level', 
      ['@level' => $level_names[$global_level]]
    );

    $this->messenger()->addStatus($this->t('Logging configuration updated successfully. Global level: @level', [
      '@level' => $level_names[$global_level],
    ]));

    parent::submitForm($form, $form_state);
  }

  /**
   * Clear logs submit handler.
   */
  public function clearLogs(array &$form, FormStateInterface $form_state) {
    try {
      // This would require Drush in production
      $this->messenger()->addWarning($this->t('Log clearing requires Drush access on the production server. Use: <code>drush watchdog:delete all</code>'));
      
      // Log this action
      $this->loggingConfig->error('newsmotivationmetrics', 
        'Log clearing requested from admin interface (requires manual Drush command)'
      );
      
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot clear logs: @error', ['@error' => $e->getMessage()]));
    }
  }

}
