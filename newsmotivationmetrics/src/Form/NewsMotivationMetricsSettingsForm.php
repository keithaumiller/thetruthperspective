<?php

namespace Drupal\newsmotivationmetrics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for News Motivation Metrics module.
 */
class NewsMotivationMetricsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['newsmotivationmetrics.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'newsmotivationmetrics_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('newsmotivationmetrics.settings');

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General Settings'),
    ];

    $form['general']['cache_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache Duration (seconds)'),
      '#description' => $this->t('How long to cache metrics data. Default is 300 seconds (5 minutes).'),
      '#default_value' => $config->get('cache_duration') ?? 300,
      '#min' => 60,
      '#max' => 3600,
    ];

    $form['general']['chart_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Default Chart Height (pixels)'),
      '#description' => $this->t('Default height for charts in the dashboard.'),
      '#default_value' => $config->get('chart_height') ?? 400,
      '#min' => 200,
      '#max' => 800,
    ];

    $form['debug'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Debug Settings'),
    ];

    $form['debug']['enable_debug_logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Debug Logging'),
      '#description' => $this->t('Log detailed information for troubleshooting chart and data issues.'),
      '#default_value' => $config->get('enable_debug_logging') ?? FALSE,
    ];

    $form['debug']['show_query_info'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Database Query Information'),
      '#description' => $this->t('Display query execution times and row counts (for administrators only).'),
      '#default_value' => $config->get('show_query_info') ?? FALSE,
    ];

    $form['actions']['debug_tools'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Debug Tools'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['actions']['debug_tools']['chart_debug_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Chart.js Debug Console'),
      '#url' => \Drupal\Core\Url::fromRoute('newsmotivationmetrics.chart_debug'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
      '#prefix' => '<div class="form-actions">',
    ];

    $form['actions']['debug_tools']['chart_test_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Chart.js Test Page'),
      '#url' => \Drupal\Core\Url::fromRoute('newsmotivationmetrics.chart_test'),
      '#attributes' => [
        'class' => ['button'],
      ],
      '#suffix' => '</div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('newsmotivationmetrics.settings')
      ->set('cache_duration', $form_state->getValue('cache_duration'))
      ->set('chart_height', $form_state->getValue('chart_height'))
      ->set('enable_debug_logging', $form_state->getValue('enable_debug_logging'))
      ->set('show_query_info', $form_state->getValue('show_query_info'))
      ->save();

    $this->messenger()->addMessage($this->t('The configuration options have been saved.'));
  }

}
