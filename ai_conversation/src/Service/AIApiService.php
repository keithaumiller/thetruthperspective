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
  protected $maxRecentMessages = 20;

  /**
   * Update summary every N messages.
   *
   * @var int
   */
  protected $summaryFrequency = 10;

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
      
      // Estimate input tokens.
      $input_tokens = $this->estimateTokens($context);

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
        $ai_response = $result['content'][0]['text'];
        
        // Estimate output tokens and update total.
        $output_tokens = $this->estimateTokens($ai_response);
        $this->updateTokenCount($conversation, $input_tokens + $output_tokens);
        
        return $ai_response;
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
   * Update total token count for conversation.
   */
  private function updateTokenCount(NodeInterface $conversation, int $tokens) {
    $current_tokens = $conversation->get('field_total_tokens')->value ?: 0;
    $new_total = $current_tokens + $tokens;
    $conversation->set('field_total_tokens', $new_total);
    
    $this->logger->info('Updated token count for conversation @nid: +@tokens (total: @total)', [
      '@nid' => $conversation->id(),
      '@tokens' => $tokens,
      '@total' => $new_total,
    ]);
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
    // Use field_summary_message_count exclusively for summary logic.
    $summary_message_count = $conversation->get('field_summary_message_count')->value ?? 0;
    $summary_message_count++;
    $conversation->set('field_summary_message_count', $summary_message_count);

    // If 10 messages have gone by, generate summary and reset counter.
    if ($summary_message_count >= $this->summaryFrequency) {
      $this->updateConversationSummary($conversation);
      // Reset summary message count to 0 after summary generation.
      $conversation->set('field_summary_message_count', 0);
    }
  }

  /**
   * Update the conversation summary.
   */
  private function updateConversationSummary(NodeInterface $conversation) {
    try {
      // Get all messages.
      $all_messages = $this->getAllMessages($conversation);
      
      // Keep only the most recent 20 messages, summarize the rest.
      if (count($all_messages) <= $this->maxRecentMessages) {
        return; // Not enough messages to summarize.
      }

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
      
      $this->logger->info('Updated conversation summary for node @nid: summarized @count messages, kept @keep recent', [
        '@nid' => $conversation->id(),
        '@count' => count($messages_to_summarize),
        '@keep' => count($recent_messages),
      ]);
      
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
    $context = "Please create a concise summary of the following conversation. ";
    $context .= "Focus on key topics discussed and important information that would be useful for continuing the conversation. ";
    $context .= "Keep the summary brief but informative.\n\n";

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
    return $this->estimateTokens($context);
  }

  /**
   * Estimate token count for text (rough approximation).
   */
  private function estimateTokens(string $text) {
    // Rough estimate: 1 token ≈ 4 characters.
    return intval(strlen($text) / 4);
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
      'total_tokens' => $conversation->get('field_total_tokens')->value ?: 0,
      'has_summary' => !empty($conversation->get('field_conversation_summary')->value),
      'summary_updated' => $conversation->get('field_summary_updated')->value,
      'estimated_tokens' => $this->estimateTokenCount($conversation),
    ];

    return $stats;
  }

}