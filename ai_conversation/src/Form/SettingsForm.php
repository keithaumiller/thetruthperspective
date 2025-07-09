<?php

namespace Drupal\ai_conversation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ai_conversation\Service\AIApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure AI Conversation settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The AI API service.
   *
   * @var \Drupal\ai_conversation\Service\AIApiService
   */
  protected $aiApiService;

  /**
   * Constructs a new SettingsForm object.
   */
  public function __construct(AIApiService $ai_api_service) {
    $this->aiApiService = $ai_api_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ai_conversation.api_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ai_conversation.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ai_conversation_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ai_conversation.settings');

    $form['api_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Settings'),
      '#collapsible' => FALSE,
    ];

    $form['api_settings']['anthropic_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Anthropic API Key'),
      '#description' => $this->t('Enter your Anthropic API key. Get one from <a href="@url" target="_blank">Anthropic Console</a>.', [
        '@url' => 'https://console.anthropic.com/',
      ]),
      '#default_value' => $config->get('anthropic_api_key'),
      '#required' => TRUE,
    ];

    $form['api_settings']['test_connection'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test Connection'),
      '#submit' => ['::testConnection'],
      '#limit_validation_errors' => [['anthropic_api_key']],
    ];

    $form['default_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default Settings'),
      '#collapsible' => FALSE,
    ];

    $form['default_settings']['default_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Default AI Model'),
      '#description' => $this->t('Select the default AI model for new conversations.'),
      '#options' => [
        'claude-sonnet-4-20250514' => 'Claude Sonnet 4',
        'claude-opus-4-20250514' => 'Claude Opus 4',
      ],
      '#default_value' => $config->get('default_model') ?: 'claude-sonnet-4-20250514',
    ];

    $form['default_settings']['default_system_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default System Prompt'),
      '#description' => $this->t('Default system prompt for new conversations.'),
      '#default_value' => $config->get('default_system_prompt') ?: 'You are a helpful AI assistant.',
      '#rows' => 4,
    ];

    $form['default_settings']['max_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Tokens'),
      '#description' => $this->t('Maximum number of tokens in AI responses.'),
      '#default_value' => $config->get('max_tokens') ?: 4000,
      '#min' => 1,
      '#max' => 8000,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Test API connection.
   */
  public function testConnection(array &$form, FormStateInterface $form_state) {
    // Temporarily save the API key to test.
    $api_key = $form_state->getValue('anthropic_api_key');
    $config = $this->config('ai_conversation.settings');
    $config->set('anthropic_api_key', $api_key)->save();

    $result = $this->aiApiService->testConnection();
    
    if ($result['success']) {
      $this->messenger()->addMessage($this->t('API connection successful!'));
    } else {
      $this->messenger()->addError($this->t('API connection failed: @message', [
        '@message' => $result['message'],
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ai_conversation.settings')
      ->set('anthropic_api_key', $form_state->getValue('anthropic_api_key'))
      ->set('default_model', $form_state->getValue('default_model'))
      ->set('default_system_prompt', $form_state->getValue('default_system_prompt'))
      ->set('max_tokens', $form_state->getValue('max_tokens'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
