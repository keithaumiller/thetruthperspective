# Social Media Automation Cron Setup

## Overview

The social media automation module now automatically posts to Mastodon (and other enabled platforms) **three times per day** during optimal engagement hours. The system rotates through different content types to provide variety and maintain audience engagement.

## How It Works

### 1. **Cron Schedule**
- **Morning Window**: 8 AM - 10 AM (2-hour window)
- **Afternoon Window**: 12 PM - 2 PM (2-hour window)
- **Evening Window**: 6 PM - 8 PM (2-hour window)
- **Frequency**: Three times per day
- **Time Zone**: Server time (production server timezone)

### 2. **Content Rotation by Time Window**
**Morning Posts (8-10 AM):**
- **recent_article** - Features the most recent article with AI motivation analysis
- **analytics_summary** - Daily statistics and insights

**Afternoon Posts (12-2 PM):**
- **trending_topics** - Popular themes from recent articles
- **bias_insight** - AI bias detection insights and platform transparency

**Evening Posts (6-8 PM):**
- **recent_article** - Features the most recent article with AI motivation analysis
- **analytics_summary** - Daily statistics and insights

### 3. **Automatic Process Flow**
```
Cron Run (every hour) → 
Check if current time in posting window → 
Check if already posted today for this window → 
Queue post with appropriate content type for window → 
Queue worker processes post → 
Content generator creates platform-specific content → 
Post to all enabled platforms → 
Update tracking state for this window
```

## Configuration

### 1. **Enable Automation**
Visit: `/admin/config/services/social-media-automation`
- Check "Enable automated posting"
- Configure and enable Mastodon platform
- Save configuration

### 2. **Mastodon Configuration**
Required fields:
- **Server URL**: Your Mastodon instance (e.g., `https://mastodon.social`)
- **Access Token**: Your app's access token

### 3. **Content Requirements**
For best results, ensure you have:
- Published articles with `field_motivation_analysis` data
- Taxonomy terms (tags) on articles for trending topics
- Active news analysis pipeline

## Testing & Monitoring

### Drush Commands

#### Check Status
```bash
drush social-media:status
```
Shows automation status, last posts, queue count, and platform details.

#### Test Content Generation
```bash
drush social-media:test-content recent_article
drush social-media:test-content analytics_summary
drush social-media:test-content trending_topics
drush social-media:test-content bias_insight
```

#### Manual Cron Testing
```bash
drush social-media:cron
```
Manually triggers the cron logic and shows whether posts would be queued.

#### Process Queue Manually
```bash
drush social-media:process-queue
```
Immediately processes any queued posts.

#### Test Platform Connections
```bash
drush social-media:test-connections
```

### Web Interface Testing

#### AI Content Preview
- Visit social media automation settings page
- Use "Generate AI Social Media Content Preview" button
- Review generated content before it would be posted

#### Platform Test Posts
- Use "Send AI-Generated Post" button for Mastodon
- Sends actual test post to verify integration

## Production Setup

### 1. **Drupal Cron**
Ensure Drupal cron is running regularly:
```bash
# Add to system crontab for every 15 minutes
*/15 * * * * cd /var/www/html/drupal && drush cron
```

### 2. **Queue Processing**
Posts are queued and processed by Drupal's queue system. If you want more frequent processing:
```bash
# Process social media queue specifically
*/5 * * * * cd /var/www/html/drupal && drush queue:run social_media_automation_posts
```

### 3. **Monitor Logs**
Check Drupal logs for automation activity:
```bash
drush watchdog:show --filter=social_media_automation
```

## Content Examples

### Recent Article Post
```
🔍 Latest Analysis: "Article Title Here" - Our AI reveals the underlying motivational patterns in this story. Key insights about political motivations driving the narrative.

🔗 https://thetruthperspective.org/article-url

#LatestAnalysis #AIInsights #NewsAnalysis #TheTruthPerspective
```

### Analytics Summary
```
📊 Latest AI analysis: 45 articles from 12 sources processed. Our algorithms detected patterns in narrative construction, bias indicators, and motivational drivers across the media landscape.

🔗 thetruthperspective.org

#AIAnalysis #MediaBias #NewsAnalysis #DataTransparency
```

## Troubleshooting

### No Posts Being Generated
1. Check if automation is enabled in settings
2. Verify Mastodon credentials are correct
3. Ensure current time is within posting window (10 AM - 2 PM)
4. Check if post was already sent today
5. Run `drush social-media:cron` to see debug information

### Queue Not Processing
1. Ensure Drupal cron is running
2. Manually process with `drush social-media:process-queue`
3. Check queue status with `drush social-media:status`

### Content Generation Issues
1. Verify you have published articles
2. Check that articles have `field_motivation_analysis` data
3. Test content generation with `drush social-media:test-content recent_article`

### Platform Connection Issues
1. Test connections with `drush social-media:test-connections`
2. Verify credentials in settings form
3. Check Mastodon server accessibility

## State Tracking

The system tracks posting state in Drupal's state system:
- `social_media_automation.last_daily_post` - Timestamp of last daily post
- `social_media_automation.last_content_type` - Last content type used
- `social_media_automation.enabled` - Whether automation is enabled

## Benefits of Daily Posting

1. **Consistent Engagement**: Regular content keeps audience engaged
2. **Content Variety**: Rotation prevents repetitive posts
3. **Business Hours**: Posts during optimal engagement times
4. **Automated**: Requires no manual intervention once configured
5. **Scalable**: Easy to add more platforms or adjust frequency

## Future Enhancements

Potential improvements:
- Multiple daily posts at different times
- Audience analytics integration
- Custom content templates
- Social media performance tracking
- Multi-timezone support
- AI-powered optimal timing

---

**Ready to activate daily automation?**
1. Configure Mastodon credentials
2. Enable automation in settings
3. Monitor with Drush commands
4. Let the system handle daily posting automatically!
