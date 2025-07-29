# News Extractor Module

## 🎯 Overview

The News Extractor module is a powerful Drupal module that automatically extracts and processes news articles from various sources using AI-powered content analysis. cool

## ✨ Features

- **Automated Article Extraction**: Extracts article content from URLs
- **AI-Powered Summarization**: Uses AWS Bedrock Claude to generate intelligent summaries
- **Content Processing**: Automatically cleans and formats extracted content
- **Taxonomy Integration**: Creates and assigns appropriate tags to articles
- **Scheduled Processing**: Supports bulk processing of multiple articles

## 🔧 Technical Details

### AI Integration
- **Service**: AWS Bedrock Runtime
- **Model**: `anthropic.claude-3-5-sonnet-20240620-v1:0`
- **Region**: `us-west-2`
- **Max Tokens**: 1000 for summaries

### Core Functions

#### `_news_extractor_generate_ai_summary($article_text, $article_title)`
Generates AI-powered summaries of news articles using AWS Bedrock Claude.

#### `_news_extractor_get_or_create_tag($tag_name)`
Automatically creates or retrieves taxonomy terms for article tagging.

### Content Processing
- Extracts clean article text from web pages
- Removes ads, navigation, and other non-content elements
- Generates concise, informative summaries
- Assigns relevant tags based on content analysis

## 🚀 Installation

1. Enable the module in Drupal admin
2. Configure AWS credentials for Bedrock access
3. Set up content types with appropriate fields
4. Configure taxonomy vocabularies for tagging

## 📋 Requirements

- Drupal 9, 10, or 11
- AWS SDK for PHP
- AWS Bedrock access with Claude model permissions
- Node module (for content type support)

## 🔑 Configuration

### AWS Setup
Ensure your server has AWS credentials configured with access to:
- AWS Bedrock Runtime
- Claude 3.5 Sonnet model permissions

### Content Types
The module works with news article content types containing:
- Title field
- Body/content field
- URL source field
- Summary field (for AI-generated content)
- Tags field (taxonomy reference)

## 📊 Usage

### Manual Processing
Articles can be processed individually through the Drupal admin interface.

### Bulk Processing
The module supports processing multiple articles in batches for efficiency.

### API Integration
Can be integrated with external news feeds and content aggregation systems.

## 🔍 Logging

The module logs all activities including:
- Successful article extractions
- AI summary generation events
- Error conditions and failures
- Performance metrics

Access logs through: **Reports > Recent log messages > news_extractor**

## 🛠️ Troubleshooting

### Common Issues

**AI Summary Generation Fails**
- Check AWS Bedrock permissions
- Verify network connectivity
- Review error logs for specific error messages

**Article Extraction Issues**
- Ensure source URLs are accessible
- Check for content extraction library dependencies
- Verify content type field configurations

**Tag Creation Problems**
- Confirm taxonomy vocabulary exists
- Check permissions for taxonomy term creation
- Review field mapping configurations

## 🔄 Updates and Maintenance

The module is designed to work with evolving AI models and can be updated to use newer versions of Claude or other AI services by modifying the model ID in the code.

## 📞 Support

For issues or questions:
1. Check the Drupal logs for error details
2. Verify AWS service status
3. Review module configuration settings
4. Test with simple article extractions first
