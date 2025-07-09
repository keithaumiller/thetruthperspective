<?php

namespace Drupal\ai_conversation\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Service for AI API communication.
 */
class AIApiService {

  /**
   * The HTTP client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new AIApiService object.
   */
  public function __construct(ClientFactory $http_client_factory, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClientFactory = $http_client_factory;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('ai_conversation');
  }

  /**
   * Send a message to the AI model.
   */
  public function sendMessage(NodeInterface $conversation, string $message) {
    $config = $this->configFactory->get('ai_conversation.settings');
    $api_key = $config->get('anthropic_api_key');
    
    if (!$api_key) {
      throw new \Exception('Anthropic API key not configured');
    }

    // Get the AI model from the conversation.
    $model = $conversation->get('field_ai_model')->value ?: 'claude-sonnet-4-20250514';

    // Build the conversation history.
    $messages = $this->buildConversationHistory($conversation);

    // Add the current message.
    $messages[] = [
      'role' => 'user',
      'content' => $message,
    ];

    // Get system prompt/context.
    $system_prompt = $conversation->get('field_context')->value ?: 'You are a helpful AI assistant.';

    // Prepare the API request.
    $request_data = [
      'model' => $model,
      'max_tokens' => 4000,
      'messages' => $messages,
      'system' => $system_prompt,
    ];

    $client = $this->httpClientFactory->fromOptions([
      'timeout' => 30,
      'headers' => [
        'Content-Type' => 'application/json',
        'X-API-Key' => $api_key,
        'anthropic-version' => '2023-06-01',
      ],
    ]);

    try {
      $response = $client->post('https://api.anthropic.com/v1/messages', [
        'json' => $request_data,
      ]);

      $response_data = json_decode($response->getBody()->getContents(), TRUE);
      
      if (isset($response_data['content'][0]['text'])) {
        return $response_data['content'][0]['text'];
      } else {
        $this->logger->error('Unexpected API response format: @response', ['@response' => print_r($response_data, TRUE)]);
        throw new \Exception('Unexpected API response format');
      }

    } catch (RequestException $e) {
      $this->logger->error('API request failed: @error', ['@error' => $e->getMessage()]);
      throw new \Exception('Failed to communicate with AI service: ' . $e->getMessage());
    }
  }

  /**
   * Build conversation history from node messages.
   */
  private function buildConversationHistory(NodeInterface $conversation) {
    $messages = [];
    
    if ($conversation->hasField('field_messages') && !$conversation->get('field_messages')->isEmpty()) {
      foreach ($conversation->get('field_messages') as $message_item) {
        $message_data = json_decode($message_item->value, TRUE);
        if ($message_data && isset($message_data['role']) && isset($message_data['content'])) {
          $messages[] = [
            'role' => $message_data['role'],
            'content' => $message_data['content'],
          ];
        }
      }
    }

    return $messages;
  }

  /**
   * Test API connection.
   */
  public function testConnection() {
    $config = $this->configFactory->get('ai_conversation.settings');
    $api_key = $config->get('anthropic_api_key');
    
    if (!$api_key) {
      return ['success' => FALSE, 'message' => 'API key not configured'];
    }

    $client = $this->httpClientFactory->fromOptions([
      'timeout' => 10,
      'headers' => [
        'Content-Type' => 'application/json',
        'X-API-Key' => $api_key,
        'anthropic-version' => '2023-06-01',
      ],
    ]);

    try {
      $response = $client->post('https://api.anthropic.com/v1/messages', [
        'json' => [
          'model' => 'claude-sonnet-4-20250514',
          'max_tokens' => 10,
          'messages' => [
            [
              'role' => 'user',
              'content' => 'Hello',
            ],
          ],
        ],
      ]);

      $response_data = json_decode($response->getBody()->getContents(), TRUE);
      
      if (isset($response_data['content'][0]['text'])) {
        return ['success' => TRUE, 'message' => 'API connection successful'];
      } else {
        return ['success' => FALSE, 'message' => 'Unexpected API response'];
      }

    } catch (RequestException $e) {
      return ['success' => FALSE, 'message' => 'API request failed: ' . $e->getMessage()];
    }
  }

}
