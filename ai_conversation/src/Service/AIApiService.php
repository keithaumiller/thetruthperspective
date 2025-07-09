<?php

namespace Drupal\ai_conversation\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;
use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Exception\AwsException;

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
   * The AWS Bedrock client.
   *
   * @var \Aws\BedrockRuntime\BedrockRuntimeClient
   */
  protected $bedrock;

  /**
   * Constructs a new AIApiService object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('ai_conversation');
    $this->initializeBedrockClient();
  }

  /**
   * Initialize the AWS Bedrock client.
   */
  private function initializeBedrockClient() {
    $config = $this->configFactory->get('ai_conversation.settings');
    
    $this->bedrock = new BedrockRuntimeClient([
      'version' => 'latest',
      'region' => $config->get('aws_region') ?: 'us-east-1',
      'credentials' => [
        'key' => $config->get('aws_access_key_id'),
        'secret' => $config->get('aws_secret_access_key'),
      ],
    ]);
  }

  /**
   * Send a message to the AI model.
   */
  public function sendMessage(NodeInterface $conversation, string $message) {
    $config = $this->configFactory->get('ai_conversation.settings');
    
    if (!$config->get('aws_access_key_id') || !$config->get('aws_secret_access_key')) {
      throw new \Exception('AWS credentials not configured');
    }

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

    // Add system message to the beginning if it exists.
    if ($system_prompt) {
      array_unshift($messages, [
        'role' => 'user',
        'content' => $system_prompt,
      ]);
      array_unshift($messages, [
        'role' => 'assistant',
        'content' => 'I understand. I will act as a helpful AI assistant.',
      ]);
    }

    try {
      $response = $this->bedrock->invokeModel([
        'modelId' => $model,
        'body' => json_encode([
          'anthropic_version' => 'bedrock-2023-05-31',
          'max_tokens' => $config->get('max_tokens') ?: 4000,
          'messages' => $messages,
        ])
      ]);

      $response_body = json_decode($response['body'], TRUE);
      
      if (isset($response_body['content'][0]['text'])) {
        return $response_body['content'][0]['text'];
      } else {
        $this->logger->error('Unexpected API response format: @response', ['@response' => print_r($response_body, TRUE)]);
        throw new \Exception('Unexpected API response format');
      }

    } catch (AwsException $e) {
      $this->logger->error('AWS Bedrock API request failed: @error', ['@error' => $e->getMessage()]);
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
    
    if (!$config->get('aws_access_key_id') || !$config->get('aws_secret_access_key')) {
      return ['success' => FALSE, 'message' => 'AWS credentials not configured'];
    }

    try {
      $response = $this->bedrock->invokeModel([
        'modelId' => 'anthropic.claude-3-5-sonnet-20240620-v1:0',
        'body' => json_encode([
          'anthropic_version' => 'bedrock-2023-05-31',
          'max_tokens' => 10,
          'messages' => [
            [
              'role' => 'user',
              'content' => 'Hello',
            ],
          ],
        ])
      ]);

      $response_body = json_decode($response['body'], TRUE);
      
      if (isset($response_body['content'][0]['text'])) {
        return ['success' => TRUE, 'message' => 'AWS Bedrock connection successful'];
      } else {
        return ['success' => FALSE, 'message' => 'Unexpected API response'];
      }

    } catch (AwsException $e) {
      return ['success' => FALSE, 'message' => 'AWS Bedrock API request failed: ' . $e->getMessage()];
    }
  }

}
