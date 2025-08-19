# News Extractor Cron Maintenance Setup

## Overview

Automated maintenance system for ensuring all articles have complete assessment fields (authoritarianism, credibility, bias, sentiment scores). This system will automatically reprocess articles missing any assessment data.

## Available Commands

### 1. Comprehensive Cron Maintenance
```bash
drush news-extractor:cron-maintenance
```
- **Purpose**: Full maintenance including basic processing + assessment field validation
- **Default**: Process 30 articles from last 14 days
- **Options**:
  - `--limit=X`: Number of articles per run (default: 30)
  - `--max-age=X`: Only check articles from last X days (default: 14)
  - `--check-assessments=false`: Skip assessment validation

### 2. Assessment-Only Cleanup
```bash
drush news-extractor:cron-cleanup-assessments
```
- **Purpose**: Focus only on missing assessment fields
- **Default**: Process 20 articles from last 30 days
- **Options**:
  - `--limit=X`: Number of articles per run (default: 20)
  - `--max-age=X`: Only check articles from last X days (default: 30)

### 3. Assessment Status Monitoring
```bash
drush news-extractor:assessment-status
```
- **Purpose**: Quick overview of assessment field completion across all articles
- **Output**: Percentage complete for each field type

## Cron Setup

### Automated Script
Copy the cron maintenance script to your server:
```bash
sudo cp /var/www/html/drupal/modules/custom/news_extractor/scripts/cron-maintenance.sh /usr/local/bin/
sudo chmod +x /usr/local/bin/cron-maintenance.sh
```

### Recommended Cron Schedules

#### Option 1: Every 4 hours during business hours
```bash
sudo crontab -e
# Add this line:
0 8,12,16,20 * * * /usr/local/bin/cron-maintenance.sh >> /var/log/news-extractor-cron.log 2>&1
```

#### Option 2: Twice daily (morning and evening)
```bash
sudo crontab -e
# Add this line:
0 6,18 * * * /usr/local/bin/cron-maintenance.sh >> /var/log/news-extractor-cron.log 2>&1
```

#### Option 3: Manual drush cron (lightweight)
```bash
sudo crontab -e
# Add this line:
0 */6 * * * cd /var/www/html/drupal && /usr/local/bin/drush news-extractor:cron-maintenance --limit=15 >> /var/log/news-extractor-cron.log 2>&1
```

## Process Flow

### 1. Basic Processing (Priority)
- Checks for articles missing scraped data or AI analysis
- Processes up to 10 articles per run
- Full pipeline: Diffbot scraping → Claude AI → field population

### 2. Assessment Field Validation
- Checks for missing assessment scores in recent articles
- Reprocesses articles with full AI calls to populate missing fields
- Rate-limited to prevent API overload

### 3. Error Handling
- Comprehensive logging for monitoring
- Graceful failure handling
- Rate limiting between articles

## Monitoring

### Log Files
- **Location**: `/var/log/news-extractor-cron.log`
- **Content**: Detailed processing results and errors
- **Rotation**: Automatically keeps last 1000 lines

### Drupal Logs
- **Location**: Admin → Reports → Recent log messages
- **Filter**: news_extractor
- **Content**: Detailed error information and processing summaries

### Quick Status Check
```bash
# Check recent cron activity
tail -n 50 /var/log/news-extractor-cron.log

# Check assessment completion rates
drush news-extractor:assessment-status

# Check for recent errors
drush watchdog:show --filter=news_extractor
```

## Customization

### Adjust Processing Limits
Edit the script variables:
```bash
MAX_ARTICLES_PER_RUN=25    # Articles processed per cron run
MAX_AGE_DAYS=14           # Only process articles from last X days
```

### Change Assessment Fields
Modify the field list in `UpdateMissingFieldsCommands.php`:
```php
$fields = [
    'field_authoritarianism_score',
    'field_credibility_score', 
    'field_bias_rating',
    'field_article_sentiment_score'
];
```

## Troubleshooting

### Common Issues

1. **Command not found**: Ensure drush commands are registered
   ```bash
   drush cr
   drush list | grep news-extractor
   ```

2. **Permission errors**: Check file permissions
   ```bash
   sudo chown -R www-data:www-data /var/www/html/drupal/modules/custom/news_extractor
   ```

3. **API rate limits**: Reduce processing frequency or limit
   ```bash
   # Reduce to 10 articles every 8 hours
   0 */8 * * * /usr/local/bin/cron-maintenance.sh
   ```

4. **Memory issues**: Lower batch sizes
   ```bash
   drush news-extractor:cron-maintenance --limit=10
   ```

### Manual Recovery
If many articles need reprocessing:
```bash
# Process missing authoritarianism scores specifically
drush news-extractor:update-authoritarianism --all

# Check overall status
drush news-extractor:assessment-status

# Full reprocessing for recent articles
drush news-extractor:bulk-process --type=full --limit=100
```

## Performance Considerations

- **Rate Limiting**: 2-3 seconds between articles to prevent API overload
- **Batch Sizes**: Keep under 30 articles per run for stability
- **Frequency**: Every 4-6 hours is optimal for most sites
- **Priority**: Basic processing takes priority over assessment updates

## Integration with Existing Systems

This cron system integrates with:
- ✅ Existing `news-extractor:bulk-process` commands
- ✅ Drupal logging system
- ✅ Current AI processing pipeline
- ✅ Assessment field infrastructure
- ✅ Error handling and recovery mechanisms
