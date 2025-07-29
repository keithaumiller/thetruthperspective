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

    $form['diffbot_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Diffbot API Token'),
      '#default_value' => $config->get('diffbot_token'),
      '#description' => $this->t('Enter your Diffbot API token.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('news_extractor.settings')
      ->set('diffbot_token', $form_state->getValue('diffbot_token'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}