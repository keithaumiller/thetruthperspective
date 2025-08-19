#!/bin/bash

# News Extractor Automated Maintenance Script
# Add this to cron for automated processing of missing assessment fields
# 
# Recommended cron schedule:
# # Run every 4 hours during business hours
# 0 8,12,16,20 * * * /var/www/html/drupal/modules/custom/news_extractor/scripts/cron-maintenance.sh >> /var/log/news-extractor-cron.log 2>&1
#
# # Or run twice daily
# 0 6,18 * * * /var/www/html/drupal/modules/custom/news_extractor/scripts/cron-maintenance.sh >> /var/log/news-extractor-cron.log 2>&1

# Configuration
DRUPAL_ROOT="/var/www/html/drupal"
DRUSH="/usr/local/bin/drush"
LOG_FILE="/var/log/news-extractor-cron.log"
MAX_ARTICLES_PER_RUN=25
MAX_AGE_DAYS=14

# Ensure we're in the correct directory
cd "$DRUPAL_ROOT" || exit 1

# Log start time
echo "================================================================"
echo "$(date '+%Y-%m-%d %H:%M:%S') - Starting News Extractor Cron Maintenance"
echo "================================================================"

# Check if drush is available
if ! command -v "$DRUSH" &> /dev/null; then
    echo "ERROR: Drush not found at $DRUSH"
    exit 1
fi

# Run the comprehensive maintenance command
echo "$(date '+%Y-%m-%d %H:%M:%S') - Running comprehensive maintenance..."
"$DRUSH" news-extractor:cron-maintenance \
    --limit="$MAX_ARTICLES_PER_RUN" \
    --max-age="$MAX_AGE_DAYS" \
    --check-assessments=true

# Check exit status
if [ $? -eq 0 ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - Maintenance completed successfully"
else
    echo "$(date '+%Y-%m-%d %H:%M:%S') - Maintenance failed with exit code $?"
fi

# Run assessment status check for monitoring
echo ""
echo "$(date '+%Y-%m-%d %H:%M:%S') - Running assessment status check..."
"$DRUSH" news-extractor:assessment-status

echo "================================================================"
echo "$(date '+%Y-%m-%d %H:%M:%S') - Cron maintenance completed"
echo "================================================================"
echo ""

# Optional: Clean up old log entries (keep last 1000 lines)
if [ -f "$LOG_FILE" ]; then
    tail -n 1000 "$LOG_FILE" > "${LOG_FILE}.tmp" && mv "${LOG_FILE}.tmp" "$LOG_FILE"
fi
