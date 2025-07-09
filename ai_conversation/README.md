# AI Conversation Module for Drupal

This module provides a conversational AI interface for Drupal sites, allowing authenticated users to have persistent conversations with AI models (currently supports Claude from Anthropic).

## Features

- **Persistent Conversations**: Each conversation is stored as a Drupal node with full context preservation
- **Real-time Chat Interface**: AJAX-powered chat interface for seamless user experience
- **Multiple AI Models**: Support for different Claude models (Sonnet 4, Opus 4)
- **Configurable System Prompts**: Set custom context/system prompts for different conversations
- **User Access Control**: Only authenticated users can create and access their own conversations
- **Responsive Design**: Mobile-friendly chat interface

## Requirements

- Drupal 9.x or 10.x
- PHP 7.4 or higher
- Anthropic API key

## Installation

1. **Download the module**: Place the `ai_conversation` folder in your Drupal `modules/custom/` directory.

2. **Enable the module**: 
   ```bash
   drush en ai_conversation
   ```
   Or enable it through the Drupal admin interface at `/admin/modules`.

3. **Configure API settings**:
   - Go to `/admin/config/ai-conversation`
   - Enter your Anthropic API key
   - Configure default settings
   - Test the connection

## Configuration

### Getting an Anthropic API Key

1. Visit [Anthropic Console](https://console.anthropic.com/)
2. Create an account or log in
3. Generate an API key
4. Copy the key to your Drupal configuration

### Module Settings

Navigate to `/admin/config/ai-conversation` to configure:

- **Anthropic API Key**: Your API key for Claude
- **Default Model**: Choose between Claude Sonnet 4 and Opus 4
- **Default System Prompt**: Set a default context for new conversations
- **Max Tokens**: Configure response length limits

## Usage

### Creating a New Conversation

1. Go to `/node/add/ai-conversation`
2. Fill in the conversation details:
   - **Title**: Give your conversation a name
   - **AI Model**: Choose which model to use
   - **Context**: Optional system prompt to guide the AI

### Using the Chat Interface

1. After creating a conversation, click "Start Chat" or go to `/node/[node-id]/chat`
2. Type your message in the input field
3. Press Enter or click "Send" to submit
4. The AI will respond, and the conversation context is preserved

### Managing Conversations

- Users can view their conversations at `/admin/content` (filter by "AI Conversation")
- Edit conversation settings by editing the node
- Delete conversations through the standard Drupal content management interface

## Permissions

The module creates the following permissions:

- **Use AI Conversation**: Allows users to create and use AI conversations
- **Administer AI Conversation**: Allows configuration of module settings

By default, authenticated users receive permission to create and use AI conversations.

## File Structure

```
ai_conversation/
├── ai_conversation.info.yml           # Module definition
├── ai_conversation.install            # Installation hooks
├── ai_conversation.module             # Module hooks and theme
├── ai_conversation.permissions.yml    # Permission definitions
├── ai_conversation.routing.yml        # URL routing
├── ai_conversation.services.yml       # Service definitions
├── ai_conversation.libraries.yml      # Asset libraries
├── src/
│   ├── Controller/
│   │   └── ChatController.php         # Chat interface controller
│   ├── Form/
│   │   └── SettingsForm.php           # Configuration form
│   └── Service/
│       └── AIApiService.php           # AI API integration
├── templates/
│   └── ai-conversation-chat.html.twig # Chat interface template
├── css/
│   └── chat-interface.css             # Chat styling
└── js/
    └── chat-interface.js               # Chat functionality
```

## Troubleshooting

### Common Issues

1. **API Connection Failed**:
   - Verify your API key is correct
   - Check your server can make outbound HTTPS requests
   - Ensure your API key has sufficient credits

2. **Chat Interface Not Loading**:
   - Clear Drupal cache: `drush cr`
   - Check browser console for JavaScript errors
   - Verify the module is properly enabled

3. **Permissions Issues**:
   - Check user permissions at `/admin/people/permissions`
   - Ensure users have "Use AI Conversation" permission

### Debug Mode

Enable Drupal's logging to see detailed error messages:
- Check logs at `/admin/reports/dblog`
- Look for "ai_conversation" entries

## Security Considerations

- API keys are stored in Drupal's configuration system
- Users can only access their own conversations
- All API requests are server-side (API keys never exposed to browsers)
- CSRF protection on all AJAX requests

## Extending the Module

The module is designed to be extensible:

- **Add new AI providers**: Extend the `AIApiService` class
- **Custom message handling**: Override the `ChatController` methods
- **Theme customization**: Override the Twig template
- **Additional fields**: Add custom fields to the conversation content type

## API Rate Limits

Be aware of Anthropic's rate limits:
- Monitor your API usage in the Anthropic Console
- Consider implementing additional rate limiting for high-traffic sites
- The module includes basic error handling for rate limit responses

## License

This module is provided under the GPL-2.0 license, consistent with Drupal's licensing.

## Support

For issues and contributions, please use your project's issue tracking system or repository.

