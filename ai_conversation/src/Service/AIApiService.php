<?php

namespace Drupal\ai_conversation\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Service for AI API communication using AWS Bedrock with rolling conversation summary.
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
   * Maximum number of recent messages to keep (configurable).
   *
   * @var int
   */
  protected $maxRecentMessages = 10;

  /**
   * Maximum tokens before triggering summary update.
   *
   * @var int
   */
  protected $maxTokensBeforeSummary = 6000;

  /**
   * Constructs a new AIApiService object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('ai_conversation');
    
    // Load configuration.
    $config = $this->configFactory->get('ai_conversation.settings');
    $this->maxRecentMessages = $config->get('max_recent_messages') ?: 10;
    $this->maxTokensBeforeSummary = $config->get('max_tokens_before_summary') ?: 6000;
  }

  /**
   * Send a message to the AI model with rolling summary management.
   */
  public function sendMessage(NodeInterface $conversation, string $message) {
    try {
      // Check if we need to update the summary before processing.
      $this->checkAndUpdateSummary($conversation);

      // Use the same AWS SDK approach as news_extractor module.
      $sdk = new \Aws\Sdk([
        'region' => 'us-west-2',
        'version' => 'latest',
      ]);
      
      $bedrock = $sdk->createBedrockRuntime();
      
      // Get the AI model from the conversation.
      $model = $conversation->get('field_ai_model')->value ?: 'anthropic.claude-3-5-sonnet-20240620-v1:0';
      
      // Validate and fix common model ID issues
      if (strpos($model, 'claude-sonnet-4') !== false) {
        $model = 'anthropic.claude-3-5-sonnet-20240620-v1:0';
        $this->logger->warning('Invalid model ID detected, using default: @model', ['@model' => $model]);
      }

      // Build the optimized conversation context (summary + recent messages).
      $context = $this->buildOptimizedContext($conversation, $message);

      // Get max tokens from config.
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
              'content' => $context
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
   * Build optimized context using summary + recent messages.
   */
  private function buildOptimizedContext(NodeInterface $conversation, string $new_message) {
    // Get system prompt/context.
    $system_prompt = $conversation->get('field_context')->value ?: 'You are a helpful AI assistant.';
    
    // Start building context.
    $context = $system_prompt . "\n\n";

    // Add conversation summary if it exists.
    if ($conversation->hasField('field_conversation_summary') && !$conversation->get('field_conversation_summary')->isEmpty()) {
      $summary = $conversation->get('field_conversation_summary')->value;
      if (!empty($summary)) {
        $context .= "CONVERSATION SUMMARY (Previous Discussion):\n" . $summary . "\n\n";
      }
    }

    // Add recent messages.
    $context .= "RECENT CONVERSATION:\n";
    $recent_messages = $this->getRecentMessages($conversation);
    
    foreach ($recent_messages as $msg) {
      $role = $msg['role'] === 'user' ? 'Human' : 'Assistant';
      $context .= $role . ": " . $msg['content'] . "\n\n";
    }

    // Add current message.
    $context .= "Human: " . $new_message . "\n\n";

    return $context;
  }

  /**
   * Get recent messages (up to maxRecentMessages).
   */
  private function getRecentMessages(NodeInterface $conversation) {
    $messages = [];
    
    if ($conversation->hasField('field_messages') && !$conversation->get('field_messages')->isEmpty()) {
      $all_messages = [];
      foreach ($conversation->get('field_messages') as $message_item) {
        $message_data = json_decode($message_item->value, TRUE);
        if ($message_data && isset($message_data['role']) && isset($message_data['content'])) {
          $all_messages[] = [
            'role' => $message_data['role'],
            'content' => $message_data['content'],
            'timestamp' => $message_data['timestamp'] ?? time(),
          ];
        }
      }

      // Sort by timestamp (most recent first) and take the last N messages.
      usort($all_messages, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
      });

      // Take the most recent messages (up to maxRecentMessages).
      $recent_messages = array_slice($all_messages, 0, $this->maxRecentMessages);
      
      // Reverse to get chronological order.
      $messages = array_reverse($recent_messages);
    }

    return $messages;
  }

  /**
   * Check if we need to update the conversation summary.
   */
  private function checkAndUpdateSummary(NodeInterface $conversation) {
    // Get current message count.
    $message_count = $conversation->get('field_message_count')->value ?: 0;
    
    // If we have more than maxRecentMessages, and summary hasn't been updated recently.
    if ($message_count > $this->maxRecentMessages) {
      // Check if we need to update summary based on token count or message count.
      $should_update = FALSE;
      
      // Update summary every 20 messages or when token count gets high.
      if ($message_count % 20 === 0) {
        $should_update = TRUE;
      }
      
      // Or if the context is getting too long.
      $context_length = $this->estimateTokenCount($conversation);
      if ($context_length > $this->maxTokensBeforeSummary) {
        $should_update = TRUE;
      }

      if ($should_update) {
        $this->updateConversationSummary($conversation);
      }
    }
  }

  /**
   * Update the conversation summary.
   */
  private function updateConversationSummary(NodeInterface $conversation) {
    try {
      // Get all messages except the most recent ones.
      $all_messages = $this->getAllMessages($conversation);
      $messages_to_summarize = array_slice($all_messages, 0, -$this->maxRecentMessages);
      
      if (empty($messages_to_summarize)) {
        return;
      }

      // Build context for summary generation.
      $summary_context = $this->buildSummaryContext($conversation, $messages_to_summarize);

      // Generate summary using Claude.
      $summary = $this->generateSummary($summary_context);

      // Update the conversation with the new summary.
      $conversation->set('field_conversation_summary', $summary);
      $conversation->set('field_summary_updated', time());
      
      // Remove old messages, keep only recent ones.
      $recent_messages = array_slice($all_messages, -$this->maxRecentMessages);
      $this->updateMessagesField($conversation, $recent_messages);
      
      $this->logger->info('Updated conversation summary for node @nid', ['@nid' => $conversation->id()]);
      
    } catch (\Exception $e) {
      $this->logger->error('Error updating conversation summary: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Generate a summary of the conversation messages.
   */
  private function generateSummary(string $context) {
    try {
      $sdk = new \Aws\Sdk([
        'region' => 'us-west-2',
        'version' => 'latest',
      ]);
      
      $bedrock = $sdk->createBedrockRuntime();

      $response = $bedrock->invokeModel([
        'modelId' => 'anthropic.claude-3-5-sonnet-20240620-v1:0',
        'body' => json_encode([
          'anthropic_version' => 'bedrock-2023-05-31',
          'max_tokens' => 1000,
          'messages' => [
            [
              'role' => 'user',
              'content' => $context
            ]
          ]
        ])
      ]);

      $result = json_decode($response['body']->getContents(), true);
      
      if (isset($result['content'][0]['text'])) {
        return $result['content'][0]['text'];
      }
      
      throw new \Exception('Unexpected API response format');
      
    } catch (\Exception $e) {
      $this->logger->error('Error generating summary: @message', [
        '@message' => $e->getMessage(),
      ]);
      return 'Summary generation failed.';
    }
  }

  /**
   * Build context for summary generation.
   */
  private function buildSummaryContext(NodeInterface $conversation, array $messages_to_summarize) {
    $context = "Please create a comprehensive summary of the following conversation. ";
    $context .= "Focus on key topics discussed, important decisions made, and relevant context that would be useful for continuing the conversation. ";
    $context .= "Keep the summary concise but informative.\n\n";

    // Add existing summary if it exists.
    if ($conversation->hasField('field_conversation_summary') && !$conversation->get('field_conversation_summary')->isEmpty()) {
      $existing_summary = $conversation->get('field_conversation_summary')->value;
      if (!empty($existing_summary)) {
        $context .= "EXISTING SUMMARY:\n" . $existing_summary . "\n\n";
        $context .= "UPDATE THE ABOVE SUMMARY WITH THE FOLLOWING NEW MESSAGES:\n\n";
      }
    }

    $context .= "CONVERSATION TO SUMMARIZE:\n";
    foreach ($messages_to_summarize as $msg) {
      $role = $msg['role'] === 'user' ? 'Human' : 'Assistant';
      $context .= $role . ": " . $msg['content'] . "\n\n";
    }

    return $context;
  }

  /**
   * Get all messages from the conversation.
   */
  private function getAllMessages(NodeInterface $conversation) {
    $messages = [];
    
    if ($conversation->hasField('field_messages') && !$conversation->get('field_messages')->isEmpty()) {
      foreach ($conversation->get('field_messages') as $message_item) {
        $message_data = json_decode($message_item->value, TRUE);
        if ($message_data && isset($message_data['role']) && isset($message_data['content'])) {
          $messages[] = [
            'role' => $message_data['role'],
            'content' => $message_data['content'],
            'timestamp' => $message_data['timestamp'] ?? time(),
          ];
        }
      }

      // Sort by timestamp.
      usort($messages, function($a, $b) {
        return $a['timestamp'] - $b['timestamp'];
      });
    }

    return $messages;
  }

  /**
   * Update the messages field with new message array.
   */
  private function updateMessagesField(NodeInterface $conversation, array $messages) {
    $field_values = [];
    foreach ($messages as $message) {
      $field_values[] = ['value' => json_encode($message)];
    }
    $conversation->set('field_messages', $field_values);
  }

  /**
   * Estimate token count for the conversation context.
   */
  private function estimateTokenCount(NodeInterface $conversation) {
    $context = $this->buildOptimizedContext($conversation, '');
    // Rough estimate: 1 token ≈ 4 characters.
    return strlen($context) / 4;
  }

  /**
   * Build conversation history from node messages (legacy method for backward compatibility).
   */
  private function buildConversationHistory(NodeInterface $conversation) {
    // For backward compatibility, this now uses the optimized approach.
    return $this->getRecentMessages($conversation);
  }

  /**
   * Test API connection.
   */
  public function testConnection() {
    try {
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

  /**
   * Get conversation statistics.
   */
  public function getConversationStats(NodeInterface $conversation) {
    $stats = [
      'total_messages' => $conversation->get('field_message_count')->value ?: 0,
      'recent_messages' => count($this->getRecentMessages($conversation)),
      'has_summary' => !empty($conversation->get('field_conversation_summary')->value),
      'estimated_tokens' => $this->estimateTokenCount($conversation),
      'summary_updated' => $conversation->get('field_summary_updated')->value,
    ];

    return $stats;
  }

}
