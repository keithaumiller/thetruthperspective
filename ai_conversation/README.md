# AI Conversation Module for Drupal

This module provides a conversational AI interface for Drupal sites, allowing authenticated users to have persistent conversations with AI models using AWS Bedrock (Claude from Anthropic).

## Features

- **Persistent Conversations**: Each conversation is stored as a Drupal node with full context preservation
- **Real-time Chat Interface**: AJAX-powered chat interface for seamless user experience
- **Multiple AI Models**: Support for different Claude models via AWS Bedrock
- **Configurable System Prompts**: Set custom context/system prompts for different conversations
- **User Access Control**: Only authenticated users can create and access their own conversations
- **Responsive Design**: Mobile-friendly chat interface
- **AWS Bedrock Integration**: Uses AWS Bedrock for secure, scalable AI access

## Requirements

- Drupal 9.x or 10.x
- PHP 7.4 or higher
- AWS account with Bedrock access
- AWS SDK for PHP (usually available via Composer)

## Installation

1. **Download the module**: Place the `ai_conversation` folder in your Drupal `modules/custom/` directory.

2. **Enable the module**: 
   ```bash
   drush en ai_conversation
   ```
   Or enable it through the Drupal admin interface at `/admin/modules`.

3. **Configure AWS settings**:
   - Go to `/admin/config/ai-conversation`
   - Enter your AWS Access Key ID and Secret Access Key
   - Select your AWS region
   - Configure default settings
   - Test the connection

## Configuration

### AWS Bedrock Setup

1. **Enable Bedrock models** in your AWS account:
   - Go to AWS Bedrock console
   - Request access to Claude models
   - Ensure your region supports Bedrock

2. **Create IAM user** with Bedrock permissions:
   ```json
   {
     "Version": "2012-10-17",
     "Statement": [
       {
         "Effect": "Allow",
         "Action": [
           "bedrock:InvokeModel"
         ],
         "Resource": "arn:aws:bedrock:*:*:model/anthropic.claude-*"
       }
     ]
   }
   ```

3. **Get Access Keys** for the IAM user

### Module Settings

Navigate to `/admin/config/ai-conversation` to configure:

- **AWS Access Key ID**: Your AWS Access Key ID
- **AWS Secret Access Key**: Your AWS Secret Access Key
- **AWS Region**: Choose the region where Bedrock is available
- **Default Model**: Choose from available Claude models:
  - Claude 3.5 Sonnet (June 2024) - `anthropic.claude-3-5-sonnet-20240620-v1:0`
  - Claude 3.5 Sonnet (October 2024) - `anthropic.claude-3-5-sonnet-20241022-v2:0`
  - Claude 3 Opus - `anthropic.claude-3-opus-20240229-v1:0`
  - Claude 3 Haiku - `anthropic.claude-3-haiku-20240307-v1:0`
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

## Available Models

The module supports these AWS Bedrock Claude models:

- **Claude 3.5 Sonnet (June 2024)**: `anthropic.claude-3-5-sonnet-20240620-v1:0` (Default)
- **Claude 3.5 Sonnet (October 2024)**: `anthropic.claude-3-5-sonnet-20241022-v2:0`
- **Claude 3 Opus**: `anthropic.claude-3-opus-20240229-v1:0`
- **Claude 3 Haiku**: `anthropic.claude-3-haiku-20240307-v1:0`

## Security Considerations

- AWS credentials are stored in Drupal's configuration system
- Users can only access their own conversations
- All API requests are server-side (AWS keys never exposed to browsers)
- CSRF protection on all AJAX requests
- Uses AWS IAM for secure API access

## Troubleshooting

### Common Issues

1. **AWS Bedrock Connection Failed**:
   - Verify your AWS credentials are correct
   - Check your AWS region supports Bedrock
   - Ensure your IAM user has bedrock:InvokeModel permissions
   - Verify the Claude models are enabled in your AWS account

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

## API Rate Limits

Be aware of AWS Bedrock's rate limits:
- Monitor your AWS usage in the AWS Console
- Consider implementing additional rate limiting for high-traffic sites
- The module includes basic error handling for rate limit responses

## License

This module is provided under the GPL-2.0 license, consistent with Drupal's licensing.

## Support

For issues and contributions, please use your project's issue tracking system or repository.
