# Social Media Automation

A comprehensive Drupal 11 module for automated posting to multiple social media platforms including Mastodon, LinkedIn, Facebook, and Twitter. This module replaces and extends the functionality of the single-platform Twitter automation module.

## Features

### Multi-Platform Support
- **Mastodon**: Full posting support with content warnings and hashtags
- **LinkedIn**: Professional content formatting and posting
- **Facebook**: Page posting with engagement optimization
- **Twitter**: OAuth 1.0a support (requires paid API plan)

### Intelligent Content Adaptation
- Platform-specific character limits and formatting
- Automated hashtag and URL inclusion based on platform capabilities
- Professional tone adjustment for LinkedIn
- Concise formatting for character-limited platforms

### Automated Scheduling
- **Morning Posts (8 AM - 12 PM)**: Analytics summaries from newsmotivationmetrics
- **Evening Posts (6 PM - 10 PM)**: Alternating trending topics and bias insights
- Queue-based posting with retry logic
- Cron-driven automation

### Content Generation Types
- **Analytics Summary**: Daily metrics and insights from news analysis
- **Trending Topics**: Current trending themes from analyzed articles
- **Bias Insight**: AI bias detection and media literacy content

## Installation

1. Place the module in your Drupal modules directory
2. Enable the module: `drush en social_media_automation -y`
3. Configure platform credentials in admin settings
4. Enable automation and configure posting schedule

## Configuration

### Access the Settings
Navigate to: `Administration > Configuration > Web Services > Social Media Automation`

### Platform Configuration

#### Mastodon (Recommended - Free)
1. Create account on your preferred Mastodon server
2. Go to Preferences > Development > New Application
3. Set permissions to "write:statuses"
4. Copy the Access Token to module settings
5. Enter your server URL (e.g., https://mastodon.social)

#### LinkedIn (Free with limitations)
1. Create LinkedIn Developer app
2. Configure OAuth 2.0 redirect URIs
3. Generate access token with "w_member_social" permission
4. Enter Client ID, Client Secret, and Access Token

#### Facebook (Requires app review for posting)
1. Create Facebook Developer app
2. Add Pages API product
3. Generate Page Access Token
4. Submit app for review to post publicly
5. Enter Page ID and Access Token

#### Twitter (Requires paid plan)
1. Create Twitter Developer app ($100/month required for posting)
2. Generate Consumer Keys (API Key/Secret)
3. Generate Access Token and Secret with write permissions
4. Enter all four credentials

## Content Generation

The module integrates with the `newsmotivationmetrics` service to generate three types of content:

### Analytics Summary
Daily overview of processed articles, sources analyzed, and key insights from AI analysis.

### Trending Topics
Current trending themes and topics derived from recent article analysis and taxonomy data.

### Bias Insight
Educational content about AI bias detection, media literacy, and algorithmic transparency.

## Platform-Specific Adaptations

### Character Limits
- **Mastodon**: 500 characters
- **LinkedIn**: 3000 characters  
- **Facebook**: 63,206 characters
- **Twitter**: 280 characters

### Content Adaptations
- **Mastodon**: Full content with hashtags and content warnings for bias discussions
- **LinkedIn**: Professional tone with extended explanations
- **Facebook**: Engaging format with call-to-action questions
- **Twitter**: Concise versions with smart truncation

### Feature Support
- **Hashtags**: Mastodon, LinkedIn, Facebook, Twitter
- **Mentions**: All platforms (when implemented)
- **Media**: Future enhancement for all platforms
- **Threads**: Mastodon, Twitter (when needed)

## API Integration

### Dependencies
- **newsmotivationmetrics.metrics_data_service**: For analytics data
- **entity_type.manager**: For article and taxonomy access
- **http_client**: For platform API calls
- **queue**: For reliable posting with retry logic

### Error Handling
- Comprehensive logging for debugging
- Graceful degradation when platforms fail
- Retry logic for temporary failures
- Platform-specific error messages

## Usage

### Automated Posting
Once configured, the module automatically:
1. Checks current time during cron runs
2. Queues appropriate content type for time period
3. Generates platform-specific content
4. Posts to all enabled platforms
5. Updates statistics and tracks success/failure

### Manual Testing
Use admin interface test buttons or Drush commands:

```bash
# Test content generation
drush social-media:test-content analytics_summary

# Test platform connections
drush social-media:test-connections

# Send test posts
drush social-media:test-post trending_topics

# Check status
drush social-media:status
```

## Drush Commands

### Content Testing
- `social-media:test-content [type]` - Generate test content for all platforms
- `social-media:test-all` - Test all content types

### Platform Testing  
- `social-media:test-connections` - Test all platform connections
- `social-media:test-platform [platform]` - Test specific platform
- `social-media:platforms` - List all platforms and status

### Posting Tests
- `social-media:test-post [type]` - Send test post to all platforms
- `social-media:test-platform-post [platform] [type]` - Test specific platform

### Status & Monitoring
- `social-media:status` - Show automation status and statistics

## Architecture

### Services
- **PlatformManager**: Registry and factory for platform clients
- **ContentGenerator**: Multi-platform content generation and adaptation
- **SocialMediaScheduler**: Automated scheduling and queue management

### Platform Interface
All platforms implement `PlatformInterface` providing:
- `postContent()` - Post content to platform
- `testConnection()` - Verify credentials and connectivity
- `formatContent()` - Platform-specific content formatting
- `getCharacterLimit()` - Platform character constraints
- `getSupportedFeatures()` - Available platform features

### Queue Processing
- Queue worker processes posts during cron
- Platform failures don't block other platforms
- Comprehensive logging for troubleshooting
- Automatic retry logic for temporary failures

## Security

### Credential Management
- All API keys stored in Drupal configuration
- Password fields for sensitive credentials
- No credentials logged or exposed in UI
- Secure HTTP client for all API calls

### Access Control
- Admin permission required for configuration
- Platform credentials isolated per platform
- No cross-platform credential sharing

## Performance

### Efficiency Features
- Platform content generated once, adapted per platform
- Caching of expensive operations
- Batch processing during off-peak hours
- Minimal resource usage during cron runs

### Monitoring
- Success/failure tracking per platform
- Character count validation before posting
- Error logging with context
- Queue status monitoring

## Migration from Twitter Automation

This module replaces the `twitter_automation` module with these improvements:

### Enhanced Features
- Multi-platform support vs single Twitter platform
- Unified administration interface
- Platform-specific content optimization
- Better error handling and logging

### Migration Process
1. Install and configure `social_media_automation`
2. Test functionality with existing credentials
3. Verify automated posting works correctly
4. Disable `twitter_automation` module
5. Remove old module after verification

### Preserved Functionality
- All Twitter OAuth 1.0a logic preserved
- Same content generation algorithms
- Identical posting schedule and timing
- Compatible with existing newsmotivationmetrics integration

## Troubleshooting

### Common Issues

#### No Content Generated
- Check newsmotivationmetrics service availability
- Verify article data exists in database
- Check entity permissions and access

#### Platform Connection Failures
- Verify credentials in admin settings
- Check platform API status and rate limits
- Review error logs for specific error messages
- Test individual platforms using Drush commands

#### Posting Failures
- Confirm platform API quotas and limitations
- Check content character limits
- Verify platform-specific authentication
- Review queue processing logs

### Debugging Tools
- Admin dashboard with real-time status
- Drush commands for isolated testing
- Comprehensive error logging
- Content preview before posting

## Development

### Adding New Platforms
1. Create platform client implementing `PlatformInterface`
2. Add service definition with `social_media_platform` tag
3. Update settings form with platform credentials
4. Add platform-specific content adaptations

### Extending Content Types
1. Add content generation method to `ContentGenerator`
2. Update admin interface with new type
3. Add Drush command support
4. Include platform-specific adaptations

## License

This module is part of The Truth Perspective platform and follows the same licensing terms as the parent project.

## Support

For issues, feature requests, or contributions, please refer to the main project repository and documentation.
