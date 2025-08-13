# AI Conversation Module

## 🎯 Overview

The AI Conversation module provides a sophisticated chat interface for interacting with AI models through AWS Bedrock. It features intelligent conversation management with rolling summary functionality to optimize token usage and maintain conversation context over extended interactions.

## ✨ Features

- **Interactive Chat Interface**: Real-time conversation with AI models
- **Rolling Summary System**: Automatically manages conversation context to prevent token overflow
- **AWS Bedrock Integration**: Uses Claude 3.5 Sonnet for high-quality responses
- **Token Management**: Intelligent token tracking and optimization
- **Conversation Continuity**: Maintains context while managing memory efficiently
- **Configurable Settings**: Customizable parameters for different use cases

## 🔧 Technical Details

### AI Integration
- **Service**: AWS Bedrock Runtime
- **Model**: `anthropic.claude-3-5-sonnet-20240620-v1:0`
- **Region**: `us-west-2`
- **Max Tokens**: Configurable (default: 4000)

### Core Architecture

#### AIApiService Class
Main service handling AI communication and conversation management.

**Key Methods**:
- `sendMessage(NodeInterface $conversation, string $message)`: Send messages to AI
- `buildOptimizedContext()`: Creates context with summary + recent messages
- `checkAndUpdateSummary()`: Manages rolling summary updates
- `estimateTokens()`: Estimates token usage for optimization

#### Rolling Summary System
- **Automatic Summarization**: Triggers when conversation exceeds thresholds
- **Context Optimization**: Maintains summary + recent messages only
- **Token-Based Logic**: Summarizes based on token count, not just message count
- **Intelligent Pruning**: Removes older messages while preserving context

### Database Schema

#### Conversation Content Type Fields
- **`field_conversation_summary`**: Stores rolling summary of older messages
- **`field_message_count`**: Tracks total messages for summary logic
- **`field_summary_updated`**: Timestamp of last summary update
- **`field_total_tokens`**: Running count of tokens used
- **`field_ai_model`**: Selected AI model for the conversation

## 🚀 Installation

1. Enable the module: `drush pm:enable ai_conversation`
2. Run database updates: `drush updatedb`
3. Configure AWS credentials for Bedrock access
4. Configure module settings
5. Clear cache: `drush cr`

## 📋 Requirements

- Drupal 9, 10, or 11
- Node module
- AWS SDK for PHP
- AWS Bedrock access with Claude model permissions

## 🔑 Configuration

### AWS Setup
Ensure your server has AWS credentials configured with access to:
- AWS Bedrock Runtime
- Claude 3.5 Sonnet model permissions

### Module Configuration
Navigate to **Admin → Configuration → AI Conversation** to configure:

#### Memory Management
- **Max Recent Messages**: Number of recent messages to keep (default: 10)
- **Token Threshold**: Maximum tokens before triggering summary (default: 6000)
- **Summary Frequency**: Update summary every N messages (default: 20)

#### Response Settings
- **Max Tokens**: Maximum tokens for AI responses (default: 4000)
- **Model Selection**: Choose AI model (supports fallback to default)

#### Debug Options
- **Debug Mode**: Enable detailed logging
- **Statistics Display**: Show token usage and conversation stats

## 📊 Usage

### Starting a Conversation
1. **Create Conversation Node**: Add new AI Conversation content
2. **Configure Settings**: Select AI model and parameters
3. **Begin Chat**: Use the chat interface to interact with AI

### Chat Interface Features
- **Real-time Responses**: Immediate AI responses
- **Message History**: Full conversation history with timestamps
- **Context Awareness**: AI maintains conversation context
- **Auto-scrolling**: Interface automatically scrolls to new messages

### Conversation Management
- **Automatic Summarization**: System manages context automatically
- **Token Tracking**: Monitor usage in conversation details
- **Context Optimization**: Recent messages + summary for best performance

## 🎨 Advanced Features

### Rolling Summary System
The module implements a sophisticated memory management system:

1. **Monitors Conversation Length**: Tracks both message count and token usage
2. **Triggers Summarization**: When thresholds are exceeded
3. **Generates Summary**: AI creates concise summary of older messages
4. **Prunes History**: Removes older messages while keeping summary
5. **Maintains Context**: Recent messages + summary for continuity

### Token Optimization
- **Input Token Estimation**: Calculates tokens before sending to AI
- **Output Token Tracking**: Monitors response token usage
- **Total Token Management**: Maintains running totals per conversation
- **Cost Optimization**: Reduces API costs through efficient context management

## 🔍 Logging and Monitoring

The module provides comprehensive logging:

- **Conversation Events**: Message sending and receiving
- **Summary Operations**: When summaries are created/updated
- **Token Usage**: Detailed token consumption tracking
- **Error Conditions**: API errors and recovery attempts
- **Performance Metrics**: Response times and optimization events

Access logs: **Reports > Recent log messages > ai_conversation**

## 🛠️ Troubleshooting

### Common Issues

**AI Not Responding**
- Check AWS Bedrock permissions and connectivity
- Verify Claude model access
- Review error logs for API issues
- Test with simple messages first

**Token Limit Exceeded**
- Adjust max tokens setting
- Check summary frequency configuration
- Review conversation length and complexity

**Summary Not Working**
- Verify token threshold settings
- Check summary frequency configuration
- Review conversation message count

**Model Errors**
- Confirm model ID is correct
- Check for model availability in region
- Verify AWS service status

### Debug Steps
1. Enable debug mode in module configuration
2. Check recent log messages for detailed information
3. Test AWS connectivity with simple requests
4. Verify conversation node configuration
5. Monitor token usage in conversation details

## 🔄 Customization

### Prompt Engineering
Modify the system prompts in `AIApiService.php` to:
- Adjust AI personality and behavior
- Add specialized knowledge domains
- Customize response formatting
- Implement role-based responses

### Model Configuration
- Change AI model by updating configuration
- Support for multiple models per conversation
- Fallback model configuration for reliability

### Summary Behavior
Customize summarization by modifying:
- Summary prompt templates
- Trigger thresholds
- Context window size
- Message retention policies

## 🚀 Future Enhancements

Potential improvements:
- Multi-model conversations
- Conversation templates
- Export/import functionality
- API integration
- Mobile-optimized interface
- Voice interaction support

## 📞 Support

For issues or questions:
1. Enable debug mode for detailed error information
2. Check AWS Bedrock service status and permissions
3. Review conversation configuration settings
4. Test with minimal conversation complexity
5. Monitor token usage and adjust thresholds accordingly
2. Start chatting - the system will automatically handle summarization
3. Monitor the conversation statistics panel
4. Watch as older messages get summarized after reaching thresholds

## 🚀 How It Works

### Context Building Process
1. **System prompt** (from node context field)
2. **Conversation summary** (if exists)
3. **Recent messages** (last N messages)
4. **Current user message**

### Summary Generation Logic
```
IF (message_count > max_recent_messages) {
  IF (message_count % summary_frequency == 0) OR (tokens > max_tokens_before_summary) {
    GENERATE_SUMMARY()
    PRUNE_OLD_MESSAGES()
  }
}
```

### Summary Content
- **Existing summary** (if updating)
- **Key topics and decisions** from older messages
- **Important context** for conversation continuity
- **Concise but comprehensive** overview

## 📊 User Experience

### Chat Interface Features
- **Statistics panel** showing:
  - Total messages vs. recent messages
  - Summary status (Yes/No)
  - Estimated token usage
  - Last summary update time

- **Visual indicators**:
  - Summary indicator when conversation is summarized
  - Loading spinner during AI responses
  - Error handling for failed requests

- **Manual controls**:
  - Trigger summary update button (appears after 20+ messages)
  - Clear input button
  - Enter to send, Shift+Enter for new line

### Performance Monitoring
- **Real-time statistics** update after each message
- **Token estimation** to predict API costs
- **Summary effectiveness** tracking

## 🛠️ API Endpoints

### New Endpoints
- **`/ai-conversation/stats`** - Get conversation statistics
- **`/node/{node}/trigger-summary`** - Manually trigger summary update
- **`/admin/config/ai-conversation`** - Configuration form

### Enhanced Endpoints
- **`/ai-conversation/send-message`** - Now returns updated statistics
- **`/node/{node}/chat`** - Includes statistics in interface

## 🔍 Debugging & Monitoring

### Debug Mode Features
- **Detailed logging** of summary generation
- **Token usage tracking**
- **Message pruning logs**
- **API call monitoring**

### Statistics Available
```php
$stats = [
  'total_messages' => 45,
  'recent_messages' => 10,
  'has_summary' => true,
  'estimated_tokens' => 2847,
  'summary_updated' => 1704067200
];
```

## 📈 Performance Benefits

### Before Implementation
- **Growing context** sent to API with every message
- **Token usage** increases linearly with conversation length
- **API costs** escalate with longer conversations
- **Response time** degrades with large contexts

### After Implementation
- **Constant context size** (summary + recent messages)
- **Predictable token usage** regardless of conversation length
- **Optimized API costs** through smart context management
- **Consistent response times** with bounded context

## 🎛️ Configuration Options

### Essential Settings
| Setting | Default | Description |
|---------|---------|-------------|
| `max_recent_messages` | 10 | Recent messages to keep in full |
| `max_tokens_before_summary` | 6000 | Token threshold for summary trigger |
| `summary_frequency` | 20 | Messages between summary updates |
| `enable_auto_summary` | TRUE | Enable automatic summarization |

### Advanced Settings
| Setting | Default | Description |
|---------|---------|-------------|
| `max_tokens` | 4000 | Max tokens for AI responses |
| `debug_mode` | FALSE | Enable detailed logging |
| `show_stats` | TRUE | Show statistics in chat interface |

## 🔄 Deployment with GitHub Actions

Your existing deployment pipeline will automatically handle:
- Database updates (`drush updatedb`)
- Cache clearing (`drush cache:rebuild`)
- Code deployment (via Git or rsync)

## 🧪 Testing Strategy

### Manual Testing
1. **Create long conversation** (30+ messages)
2. **Verify summary generation** at configured intervals
3. **Check context optimization** in API calls
4. **Monitor token usage** through statistics
5. **Test manual summary trigger**

### Automated Testing
```bash
# Test API connection
drush ai-conversation:test

# Check configuration
drush config:get ai_conversation.settings

# Verify database schema
drush sql:query "DESCRIBE node__field_conversation_summary"
```

## 🚨 Troubleshooting

### Common Issues

**Summary not generating**
- Check `enable_auto_summary` setting
- Verify message count threshold
- Review debug logs

**High token usage**
- Reduce `max_recent_messages`
- Lower `max_tokens_before_summary`
- Check summary quality

**API errors**
- Verify AWS credentials
- Check Bedrock model availability
- Review error logs

### Debug Commands
```bash
# Check module status
drush pm:list | grep ai_conversation

# Test AI API connection
drush ai-conversation:test

# Check configuration settings
drush config:get ai_conversation.settings

# View recent logs for troubleshooting
drush watchdog:show --type=ai_conversation

# Verify database schema
drush sql:query "DESCRIBE node__field_conversation_summary"

# Clear all caches
drush cr

# Update database schema if needed
drush updatedb
```

## 🎯 Next Steps

### Immediate Actions
1. **Deploy the updated module** using your GitHub Actions pipeline
2. **Configure settings** through the admin interface
3. **Test with existing conversations** to verify functionality
4. **Monitor performance** through the statistics panel

### Future Enhancements
- **Advanced summarization** with topic extraction
- **Conversation branching** for different discussion threads
- **Export/import** of conversation summaries
- **Integration with other AI models** for specialized summarization

## 📝 File Structure

```
ai_conversation/
├── ai_conversation.install          # Database schema + updates
├── ai_conversation.module           # Hooks and theme definitions
├── ai_conversation.routing.yml      # Route definitions
├── ai_conversation.services.yml     # Service definitions
├── ai_conversation.libraries.yml    # Asset libraries
├── src/
│   ├── Service/
│   │   └── AIApiService.php        # Enhanced AI service
│   ├── Controller/
│   │   └── ChatController.php      # Enhanced chat controller
│   └── Form/
│       └── SettingsForm.php        # Configuration form
├── templates/
│   └── ai-conversation-chat.html.twig  # Chat interface template
├── css/
│   └── chat-interface.css          # Styles
└── js/
    └── chat-interface.js           # JavaScript functionality
```

## 🎉 Success Metrics

Your rolling summary system is working correctly when:
- ✅ **Conversation statistics** update in real-time
- ✅ **Summary indicator** appears after threshold reached
- ✅ **Token usage** remains stable regardless of conversation length
- ✅ **API response times** stay consistent
- ✅ **Context quality** maintained through summarization

**Ready to revolutionize your AI conversations with intelligent context management!** 🚀
