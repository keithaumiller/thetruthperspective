#!/bin/bash

echo "=== Enhanced Mastodon Form & Database Debugging ==="
echo "This script shows comprehensive debugging output for form submission and database verification."
echo ""
echo "Instructions:"
echo "1. Go to: https://thetruthperspective.org/admin/config/services/social-media-automation/settings"
echo "2. Fill in Mastodon credentials:"
echo "   - Server URL: https://mastodon.social"
echo "   - Access Token: (your token)"
echo "   - Enable Mastodon: Check the box"
echo "3. Click 'Save configuration'"
echo "4. Watch output below..."
echo ""

echo "=== CURRENT CONFIG STATE ==="
echo "Before form submission, current database values:"
sudo -u www-data drush config:get social_media_automation.settings mastodon.server_url 2>/dev/null || echo "mastodon.server_url: NOT SET"
sudo -u www-data drush config:get social_media_automation.settings mastodon.access_token 2>/dev/null | head -c 50 || echo "mastodon.access_token: NOT SET"
sudo -u www-data drush config:get social_media_automation.settings mastodon.enabled 2>/dev/null || echo "mastodon.enabled: NOT SET"

echo ""
echo "=== WATCHING FOR FORM SUBMISSION ==="
echo "Submit the form now and watch for debugging output..."
echo ""

# Monitor logs with enhanced filtering
tail -f /var/log/syslog 2>/dev/null | grep -E "(social_media_automation|mastodon)" --line-buffered | while read line; do
    echo "$(date '+%H:%M:%S') $line"
done &

# Also check drupal logs
sudo -u www-data drush watchdog:tail --extended --type=social_media_automation 2>/dev/null &

echo "Press Ctrl+C to stop monitoring"
wait
