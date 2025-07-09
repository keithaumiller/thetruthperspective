<?php

namespace Drupal\ai_conversation\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Service for AI API communication using AWS Bedrock.
 */
class AIApiService {

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
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('ai_conversation');
  }

  /**
   * Send a message to the AI model.
   */
  public function sendMessage(NodeInterface $conversation, string $message) {
    try {
      // Use the same AWS SDK approach as news_extractor module
      $sdk = new \Aws\Sdk([
        'region' => 'us-west-2',
        'version' => 'latest',
      ]);
      
      $bedrock = $sdk->createBedrockRuntime();
      
      // Get the AI model from the conversation.
      $model = $conversation->get('field_ai_model')->value ?: 'anthropic.claude-3-5-sonnet-20240620-v1:0';

      // Build the conversation history.
      $messages = $this->buildConversationHistory($conversation);

      // Add the current message.
      $messages[] = [
        'role' => 'user',
        'content' => $message,
      ];

      // Get system prompt/context.
      $system_prompt = $conversation->get('field_context')->value ?: 'You are a helpful AI assistant.';

      // Build the prompt similar to news_extractor approach
      $prompt = $system_prompt . "\n\n";
      
      // Add conversation history to prompt
      foreach ($messages as $msg) {
        $role = $msg['role'] === 'user' ? 'Human' : 'Assistant';
        $prompt .= $role . ": " . $msg['content'] . "\n\n";
      }

      // Get max tokens from config
      $config = $this->configFactory->get('ai_conversation.settings');
      $max_tokens = $config->get('max_tokens') ?: 4000;

      $response = $bedrock->invokeModel([
        'modelId' => $model,
        'body' => json_encode([
          'anthropic_version' => 'bedrock-2023-05-31',
          'max_tokens' => $max_tokens,
          'messages' => [
            [
              'role' => 'user',
              'content' => $prompt
            ]
          ]
        ])
      ]);

      $result = json_decode($response['body']->getContents(), true);
      
      if (isset($result['content'][0]['text'])) {
        return $result['content'][0]['text'];
      }
      
      $this->logger->error('Unexpected API response format: @response', ['@response' => print_r($result, TRUE)]);
      throw new \Exception('Unexpected API response format');
      
    } catch (\Exception $e) {
      $this->logger->error('Error communicating with AI service: @message', [
        '@message' => $e->getMessage(),
      ]);
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
    try {
      // Use the same AWS SDK approach as news_extractor module
      $sdk = new \Aws\Sdk([
        'region' => 'us-west-2',
        'version' => 'latest',
      ]);
      
      $bedrock = $sdk->createBedrockRuntime();

      $response = $bedrock->invokeModel([
        'modelId' => 'anthropic.claude-3-5-sonnet-20240620-v1:0',
        'body' => json_encode([
          'anthropic_version' => 'bedrock-2023-05-31',
          'max_tokens' => 10,
          'messages' => [
            [
              'role' => 'user',
              'content' => 'Hello'
            ]
          ]
        ])
      ]);

      $result = json_decode($response['body']->getContents(), true);
      
      if (isset($result['content'][0]['text'])) {
        return ['success' => TRUE, 'message' => 'AWS Bedrock connection successful'];
      } else {
        return ['success' => FALSE, 'message' => 'Unexpected API response'];
      }

    } catch (\Exception $e) {
      return ['success' => FALSE, 'message' => 'AWS Bedrock connection failed: ' . $e->getMessage()];
    }
  }

}
