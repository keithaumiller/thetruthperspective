# Social Media Automation Module

## Overview

Unified social media automation system for The Truth Perspective platform. Provides automated content generation and posting across multiple social media platforms with a single configuration interface and shared scheduling logic.

## Architecture

### Core Design Principles

1. **Platform Agnostic**: Common content generation and scheduling logic
2. **Modular Services**: Separate service for each platform with unified interface
3. **Unified Configuration**: Single admin interface for all platform credentials
4. **Flexible Content**: Adaptive content generation based on platform constraints
5. **Reliable Posting**: Queue-based system with retry logic and error handling

### Module Structure

```
social_media_automation/
├── src/
│   ├── Controller/
│   │   ├── SocialMediaController.php         # Main dashboard and management
│   │   └── ContentPreviewController.php      # Content preview and testing
│   ├── Form/
│   │   ├── SocialMediaSettingsForm.php       # Unified platform configuration
│   │   └── ContentGenerationForm.php         # Manual content creation
│   ├── Service/
│   │   ├── ContentGenerator.php              # Platform-agnostic content creation
│   │   ├── SocialMediaScheduler.php          # Unified scheduling and queue management
│   │   ├── Platform/
│   │   │   ├── PlatformInterface.php         # Common interface for all platforms
│   │   │   ├── MastodonClient.php            # Mastodon API integration
│   │   │   ├── LinkedInClient.php            # LinkedIn API integration
│   │   │   ├── FacebookClient.php            # Facebook API integration
│   │   │   └── TwitterClient.php             # Twitter API integration (legacy/paid)
│   │   └── PlatformManager.php               # Platform registry and factory
│   ├── Plugin/
│   │   └── QueueWorker/
│   │       └── SocialMediaPostWorker.php     # Queue processing for posts
│   └── Commands/
│       └── SocialMediaCommands.php           # Drush commands for testing
├── templates/
│   ├── social-media-dashboard.html.twig      # Admin dashboard
│   ├── content-preview.html.twig             # Content preview interface
│   └── platform-status.html.twig             # Platform connection status
├── css/
│   └── social-media-admin.css                # Admin interface styling
├── js/
│   ├── content-preview.js                    # Live content preview
│   └── platform-manager.js                   # Platform status management
└── config/
    └── install/
        └── social_media_automation.settings.yml
```

## Platform Integration

### Supported Platforms

| Platform | Status | API Cost | Authentication | Character Limit | Features |
|----------|--------|----------|---------------|-----------------|----------|
| **Mastodon** | ✅ Primary | Free | OAuth 2.0 | 500 chars | Full automation |
| **LinkedIn** | ✅ Secondary | Free (Personal) | OAuth 2.0 | 3000 chars | Personal posts |
| **Facebook** | 🔄 Planned | Free (with review) | OAuth 2.0 | 63,206 chars | Page posts |
| **Twitter** | 💰 Optional | $100/month | OAuth 1.0a | 280 chars | Full features |
| **Reddit** | 🔄 Future | Free | OAuth 2.0 | 40,000 chars | Subreddit posts |

### Platform Service Interface

```php
interface PlatformInterface {
  public function getName(): string;
  public function isConfigured(): bool;
  public function testConnection(): bool;
  public function getCharacterLimit(): int;
  public function postContent(string $content, array $options = []): array|false;
  public function formatContent(string $content, array $context = []): string;
  public function getAuthenticationUrl(): string;
  public function getRequiredCredentials(): array;
}
```

## Content Generation System

### Content Types

1. **Analytics Summary** (Morning Posts 8AM-12PM)
   - Daily metrics from newsmotivationmetrics
   - Article processing statistics
   - Source diversity reports
   - Platform-optimized formatting

2. **Trending Topics** (Evening Posts 6PM-10PM)
   - Most frequent themes from recent articles
   - AI-detected narrative trends
   - Cross-platform engagement optimization

3. **Bias Insights** (Educational Content)
   - AI limitation explanations
   - Methodology transparency
   - Critical thinking promotion

4. **Manual Content** (On-demand)
   - Admin-generated posts
   - Immediate or scheduled posting
   - Multi-platform distribution

### Content Adaptation

```php
class ContentGenerator {
  public function generateContent(string $type, array $context = []): array {
    // Generate base content
    $baseContent = $this->createBaseContent($type, $context);
    
    // Adapt for each enabled platform
    $platformContent = [];
    foreach ($this->getEnabledPlatforms() as $platform) {
      $platformContent[$platform] = $this->adaptForPlatform($baseContent, $platform);
    }
    
    return $platformContent;
  }
}
```

## Configuration System

### Unified Settings Structure

```yaml
social_media_automation.settings:
  global:
    enabled: true
    posting_schedule:
      morning_start: '08:00'
      morning_end: '12:00'
      evening_start: '18:00'
      evening_end: '22:00'
    content_types:
      analytics_summary: true
      trending_topics: true
      bias_insights: true
  
  platforms:
    mastodon:
      enabled: true
      server_url: 'https://mastodon.social'
      client_id: 'xxx'
      client_secret: 'xxx'
      access_token: 'xxx'
      character_limit: 500
    
    linkedin:
      enabled: true
      client_id: 'xxx'
      client_secret: 'xxx'
      access_token: 'xxx'
      character_limit: 3000
    
    facebook:
      enabled: false
      app_id: 'xxx'
      app_secret: 'xxx'
      access_token: 'xxx'
      page_id: 'xxx'
    
    twitter:
      enabled: false
      api_key: 'xxx'
      api_secret: 'xxx'
      access_token: 'xxx'
      access_secret: 'xxx'
```

## Queue System

### Posting Workflow

1. **Cron Trigger**: Check if current time falls within posting windows
2. **Content Generation**: Create platform-specific content
3. **Queue Items**: Add posts to Drupal queue system
4. **Queue Processing**: Worker processes posts with retry logic
5. **Status Tracking**: Log success/failure for each platform
6. **Error Handling**: Retry failed posts with backoff strategy

### Queue Item Structure

```php
class SocialMediaPostItem {
  public string $platform;
  public string $content;
  public array $options;
  public int $retry_count;
  public int $scheduled_time;
  public string $content_type;
}
```

## Admin Interface

### Dashboard Features

- **Platform Status**: Real-time connection status for each platform
- **Recent Posts**: Last 10 posts across all platforms with engagement metrics
- **Pending Queue**: Upcoming scheduled posts and retry queue
- **Content Preview**: Live preview of generated content for each platform
- **Manual Posting**: Create and send immediate posts
- **Analytics**: Basic posting statistics and platform performance

### Configuration Interface

- **Platform Credentials**: Tabbed interface for each platform setup
- **Posting Schedule**: Visual time range selectors
- **Content Settings**: Enable/disable content types per platform
- **Test Connections**: Individual platform testing with detailed feedback
- **Bulk Operations**: Enable/disable multiple platforms at once

## Installation & Setup

### 1. Enable Module
```bash
drush en social_media_automation
```

### 2. Configure Platforms
1. Go to `/admin/config/services/social-media-automation`
2. Configure credentials for each desired platform
3. Test connections using the "Test Connection" buttons
4. Enable automated posting

### 3. Platform-Specific Setup

#### Mastodon Setup
1. Choose a Mastodon server (mastodon.social, mastodon.world, etc.)
2. Create account on chosen server
3. Go to Preferences > Development > New Application
4. Copy Client ID, Client Secret, and Access Token
5. Enter in module configuration

#### LinkedIn Setup
1. Create LinkedIn Developer App
2. Set up OAuth 2.0 with appropriate scopes
3. Generate access token for personal posting
4. Configure in module settings

#### Facebook Setup (Optional)
1. Create Facebook App in Meta Developers
2. Request pages_manage_posts permission
3. Complete app review process
4. Configure page access token

## Features

### Automated Posting
- ✅ **Smart Scheduling**: Posts during optimal time windows
- ✅ **Content Adaptation**: Platform-specific formatting and character limits
- ✅ **Queue Management**: Reliable posting with retry logic
- ✅ **Error Recovery**: Automatic retry for failed posts

### Content Management
- ✅ **Multi-Type Content**: Analytics, trends, insights, and manual posts
- ✅ **Live Preview**: See how content appears on each platform
- ✅ **Manual Override**: Create and post custom content immediately
- ✅ **Scheduling**: Queue posts for specific times

### Platform Support
- ✅ **Mastodon**: Full automation with free API
- ✅ **LinkedIn**: Personal profile posting
- 🔄 **Facebook**: Page posting (requires app review)
- 💰 **Twitter**: Full features with paid API
- 🔄 **Reddit**: Community posting (future)

### Administration
- ✅ **Unified Dashboard**: All platforms in one interface
- ✅ **Status Monitoring**: Real-time platform health checks
- ✅ **Activity Logs**: Detailed posting history and error tracking
- ✅ **Performance Metrics**: Basic engagement and reach statistics

## Dependencies

- **newsmotivationmetrics**: For analytics data and content generation
- **Drupal Core**: Queue, cron, config, state, logging, HTTP client
- **Platform APIs**: OAuth libraries and HTTP clients for each platform

## Security & Privacy

- ✅ **Encrypted Storage**: All API credentials stored in Drupal configuration
- ✅ **Permission-Based**: Admin access required for configuration
- ✅ **Audit Trail**: Complete logging of all posting activities
- ✅ **No Personal Data**: Only public analytics data is shared
- ✅ **Platform Compliance**: Follows each platform's API terms of service

## Performance Considerations

- **Lightweight Cron**: Minimal impact on site performance
- **Queue-Based**: Prevents timeouts and allows retry logic
- **Efficient Content Generation**: Cached expensive operations
- **Rate Limiting**: Respects platform API limits
- **Error Handling**: Graceful degradation when platforms are unavailable

## Future Enhancements

### Phase 2
- **Reddit Integration**: Subreddit posting for relevant communities
- **Image Support**: Automated image generation and posting
- **Analytics Integration**: Platform-specific engagement metrics
- **Content Templates**: Customizable post templates

### Phase 3
- **AI-Powered Timing**: Optimal posting time prediction
- **Cross-Platform Analytics**: Unified performance dashboard
- **A/B Testing**: Content variation testing across platforms
- **Advanced Scheduling**: Campaign-based posting strategies

## Troubleshooting

### Common Issues

1. **Platform Connection Failed**
   - Verify API credentials are correct
   - Check platform-specific rate limits
   - Ensure proper OAuth scopes/permissions

2. **Posts Not Sending**
   - Confirm automation is enabled globally and per-platform
   - Check cron is running regularly
   - Review queue worker status

3. **Content Generation Errors**
   - Verify newsmotivationmetrics module is functioning
   - Check article data availability
   - Review content type configurations

### Debug Commands

```bash
# Test platform connections
drush sma:test-connections

# Generate test content
drush sma:generate-content analytics_summary

# Process posting queue manually
drush sma:process-queue

# Show platform status
drush sma:status
```

## API Documentation

### Content Generation API
```php
// Generate content for all platforms
$content = \Drupal::service('social_media_automation.content_generator')
  ->generateContent('analytics_summary');

// Post to specific platform
$result = \Drupal::service('social_media_automation.platform_manager')
  ->getPlatform('mastodon')
  ->postContent($content['mastodon']);
```

### Platform Integration API
```php
// Register new platform
class CustomPlatformClient implements PlatformInterface {
  // Implementation...
}

// Add to platform manager
\Drupal::service('social_media_automation.platform_manager')
  ->registerPlatform('custom', CustomPlatformClient::class);
```

This architecture provides a robust, extensible foundation for multi-platform social media automation while maintaining the proven content generation and scheduling logic from the original Twitter module.
