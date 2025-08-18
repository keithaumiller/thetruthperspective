#!/bin/bash

echo "=== Mastodon Form Debugging Tool ==="
echo "This script helps debug the form submission process."
echo ""
echo "1. Go to: https://thetruthperspective.org/admin/config/services/social-media-automation/settings"
echo "2. Fill in Mastodon credentials and click 'Save configuration'"
echo "3. Watch this terminal for debug output"
echo ""
echo "Monitoring logs for form submission data..."
echo "Press Ctrl+C to stop monitoring"
echo ""

# Function to check logs
check_logs() {
    echo "=== $(date) ==="
    
    # Check for platform structure
    echo "🔍 Looking for platform structure..."
    sudo -u www-data drush watchdog:show --count=5 --type=social_media_automation | grep -i "platforms\[mastodon\]" | head -3
    
    # Check for debug test values
    echo "🔧 Looking for debug test values..."
    sudo -u www-data drush watchdog:show --count=5 --type=social_media_automation | grep "DEBUG TEST" | head -3
    
    # Check for general form submission
    echo "📝 Looking for form submission..."
    sudo -u www-data drush watchdog:show --count=3 --type=social_media_automation | grep -E "(form|submit|values)" | head -2
    
    echo "---"
}

# Initial check
check_logs

# Monitor for changes
while true; do
    sleep 3
    check_logs
done
