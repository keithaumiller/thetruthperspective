# Twitter Automation Module

Userid:@TheTruthPers

## Overview

The Twitter Automation module provides automated posting capabilities for The Truth Perspective platform. It generates and posts content twice daily based on analytics data from the newsmotivationmetrics module.

## Features

- **Automated Posting**: Posts twice daily (morning and evening) during configurable time windows
- **Content Generation**: Creates engaging content from analytics data, trending topics, and AI bias insights
- **Queue Management**: Uses Drupal queue system for reliable posting
- **Twitter API v2**: Modern API integration with Bearer token authentication
- **Admin Dashboard**: Complete management interface with statistics and content previews
- **Testing Tools**: Send test tweets and verify API connectivity

## Architecture

### Services

1. **TwitterApiClient**: Handles Twitter API v2 communication
2. **ContentGenerator**: Creates tweet content from analytics data
3. **TwitterScheduler**: Manages posting schedule and queue processing

### Content Types

- **Analytics Summary**: Daily metrics and article processing stats
- **Trending Topics**: Popular themes from recent news analysis
- **Bias Insight**: Educational content about AI bias and media analysis

### Posting Schedule

- **Morning Posts (8 AM - 12 PM)**: Analytics summaries
- **Evening Posts (6 PM - 10 PM)**: Alternating between trending topics and bias insights

## Installation

1. Enable the module:
```bash
drush en twitter_automation
```

2. Configure Twitter API credentials:
   - Go to `/admin/config/services/twitter-automation/settings`
   - Add your Twitter API v2 Bearer Token
   - Test the connection

3. Enable automated posting and configure settings

## Configuration

### Twitter API Setup

1. Create a Twitter Developer account at https://developer.twitter.com
2. Create a new app and generate API v2 Bearer Token
3. Ensure your app has "Read and Write" permissions
4. Add the Bearer Token to the module configuration

### Settings

- **Bearer Token**: Twitter API v2 authentication
- **Enable Automated Posting**: Toggle for automation
- **Time Windows**: Fixed ranges for morning (8-12) and evening (6-10) posts

## Usage

### Admin Dashboard

Visit `/admin/config/services/twitter-automation` to:
- View posting statistics
- Preview generated content
- Monitor queue status
- Access configuration

### Manual Testing

Use the settings form to:
- Test API connectivity
- Send test tweets with different content types
- Verify authentication

### Cron Integration

The module automatically:
- Checks posting windows during cron runs
- Queues posts when appropriate
- Processes queue items to send tweets
- Tracks posting history

## Content Generation

### Analytics Summary
- Pulls data from newsmotivationmetrics service
- Reports article counts and source statistics
- Includes transparency messaging

### Trending Topics
- Analyzes recent article taxonomy terms
- Identifies most frequent themes
- Creates engaging topic summaries

### Bias Insights
- Educational content about AI limitations
- Highlights transparency goals
- Promotes critical thinking about algorithms

## Queue System

- Uses Drupal's queue API for reliable processing
- Queue worker processes items during cron
- Automatic retry logic for failed posts
- Detailed logging for troubleshooting

## Logging

All activities are logged to 'twitter_automation' channel:
- Successful posts with content previews
- API connection issues
- Content generation errors
- Queue processing status

## Dependencies

- **newsmotivationmetrics**: For analytics data and metrics service
- **Drupal Core**: queue, cron, config, state, logging systems
- **Twitter API v2**: Bearer token authentication required

## Troubleshooting

### Common Issues

1. **Connection Failed**
   - Verify Bearer Token is correct
   - Check Twitter app permissions (Read and Write)
   - Test connection in admin interface

2. **Posts Not Sending**
   - Confirm automation is enabled
   - Check cron is running regularly
   - Review logs for error messages

3. **Content Generation Errors**
   - Ensure newsmotivationmetrics module is enabled
   - Verify article data is available
   - Check entity permissions

### Debug Steps

1. Visit admin dashboard for status overview
2. Check Recent Reports logs for twitter_automation
3. Test API connection manually
4. Send test tweets to verify functionality
5. Monitor queue status and processing

## Security

- Bearer Token stored in encrypted configuration
- Admin access requires 'administer twitter automation' permission
- No personal data is collected or transmitted
- All content is public-facing analytics data

## Performance

- Lightweight cron integration
- Queue-based processing prevents timeouts
- Content generation caches expensive operations
- Minimal impact on site performance

## Future Enhancements

- Multiple account support
- Custom content templates
- Advanced scheduling options
- Analytics integration with Twitter metrics
- Image posting capabilities

## Support

For issues or questions:
1. Check Drupal logs for error details
2. Verify Twitter API status and quotas
3. Test individual components (API, content generation, scheduling)
4. Review module configuration and permissions
