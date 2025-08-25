<?php

namespace Drupal\news_extractor\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure News Extractor settings.
 */
class NewsExtractorSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['news_extractor.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'news_extractor_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('news_extractor.settings');

    $form['api_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('API Configuration'),
      '#open' => TRUE,
    ];

    $form['api_settings']['diffbot_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Diffbot API Token'),
      '#default_value' => $config->get('diffbot_token'),
      '#description' => $this->t('Enter your Diffbot API token.'),
      '#required' => TRUE,
    ];

    $form['daily_limits'] = [
      '#type' => 'details',
      '#title' => $this->t('Daily Processing Limits'),
      '#open' => TRUE,
      '#description' => $this->t('Configure daily article processing limits per news source to manage resource usage and API costs.'),
    ];

    $form['daily_limits']['daily_limit_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable daily processing limits'),
      '#default_value' => $config->get('daily_limit_enabled'),
      '#description' => $this->t('When enabled, each news source will be limited to a maximum number of articles processed per day.'),
    ];

    $form['daily_limits']['default_daily_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Default daily limit per news source'),
      '#default_value' => $config->get('default_daily_limit') ?: 5,
      '#description' => $this->t('Default number of articles to process per news source per day. Individual sources can have custom limits.'),
      '#min' => 1,
      '#max' => 100,
      '#states' => [
        'visible' => [
          ':input[name="daily_limit_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['daily_limits']['enforce_limits_globally'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enforce limits globally'),
      '#default_value' => $config->get('enforce_limits_globally'),
      '#description' => $this->t('When enabled, limits are enforced for all processing including manual operations. When disabled, limits only apply to automatic feed imports.'),
      '#states' => [
        'visible' => [
          ':input[name="daily_limit_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['daily_limits']['limit_reset_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Daily limit reset time'),
      '#default_value' => $config->get('limit_reset_time') ?: '00:00',
      '#description' => $this->t('Time when daily limits reset (HH:MM format, 24-hour). Default is midnight (00:00).'),
      '#pattern' => '^([01]?[0-9]|2[0-3]):[0-5][0-9]$',
      '#states' => [
        'visible' => [
          ':input[name="daily_limit_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Add current status information
    if ($config->get('daily_limit_enabled')) {
      /** @var \Drupal\news_extractor\Service\DailyLimitService $daily_limit_service */
      $daily_limit_service = \Drupal::service('news_extractor.daily_limit');
      $today_counts = $daily_limit_service->getAllDailyCounts();
      
      if (!empty($today_counts)) {
        $total_processed = array_sum(array_column($today_counts, 'count'));
        $sources_at_limit = count(array_filter($today_counts, function($data) { return $data['at_limit']; }));
        
        $form['daily_limits']['current_status'] = [
          '#type' => 'item',
          '#title' => $this->t('Today\'s Status'),
          '#markup' => $this->t('📊 <strong>@sources</strong> sources tracked, <strong>@at_limit</strong> at their daily limit, <strong>@total</strong> total articles processed today. <a href="@link">View detailed dashboard</a>', [
            '@sources' => count($today_counts),
            '@at_limit' => $sources_at_limit,
            '@total' => $total_processed,
            '@link' => '/admin/reports/news-extractor/daily-limits',
          ]),
          '#states' => [
            'visible' => [
              ':input[name="daily_limit_enabled"]' => ['checked' => TRUE],
            ],
          ],
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('news_extractor.settings');
    
    $config
      ->set('diffbot_token', $form_state->getValue('diffbot_token'))
      ->set('daily_limit_enabled', $form_state->getValue('daily_limit_enabled'))
      ->set('default_daily_limit', $form_state->getValue('default_daily_limit'))
      ->set('enforce_limits_globally', $form_state->getValue('enforce_limits_globally'))
      ->set('limit_reset_time', $form_state->getValue('limit_reset_time'))
      ->save();

    $status = $form_state->getValue('daily_limit_enabled') ? 'enabled' : 'disabled';
    $this->messenger()->addMessage($this->t('News Extractor settings have been saved. Daily limits are now @status.', ['@status' => $status]));

    parent::submitForm($form, $form_state);
  }
}