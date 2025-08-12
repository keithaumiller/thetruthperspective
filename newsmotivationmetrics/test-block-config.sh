#!/bin/bash

# News Motivation Metrics - Block Configuration Test Script
# This script verifies that dashboard blocks are properly configured

echo "=== News Motivation Metrics Block Configuration Test ==="
echo

# Check if module is installed
echo "1. Checking module status..."
if drush pm:list --filter=newsmotivationmetrics --status=enabled | grep -q "newsmotivationmetrics"; then
    echo "✅ Module is installed and enabled"
else
    echo "❌ Module not found or not enabled"
    exit 1
fi

echo

# Get current theme
echo "2. Checking current theme..."
THEME=$(drush config:get system.theme default --format=string)
echo "Current theme: $THEME"

echo

# Check block status
echo "3. Checking block configurations..."
echo "Running block status check..."

drush php:eval "newsmotivationmetrics_check_block_status();"

echo

# Check hero region blocks
echo "4. Checking hero region blocks..."
drush block:list --region=hero | grep -E "(metrics|analysis|sentiment|entity|activity|quality|about|timeline)"

echo

# Test front page access
echo "5. Testing front page accessibility..."
if command -v curl &> /dev/null; then
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://thetruthperspective.org/)
    if [ "$HTTP_CODE" = "200" ]; then
        echo "✅ Front page accessible (HTTP $HTTP_CODE)"
        
        # Check for metrics content
        if curl -s https://thetruthperspective.org/ | grep -q "Content Analysis Overview"; then
            echo "✅ Metrics content found on front page"
        else
            echo "⚠️  Metrics content not found on front page"
        fi
    else
        echo "❌ Front page not accessible (HTTP $HTTP_CODE)"
    fi
else
    echo "⚠️  curl not available, skipping front page test"
fi

echo

# Show restoration commands
echo "6. Quick restoration commands (if needed):"
echo "   Restore blocks: drush php:eval \"newsmotivationmetrics_restore_block_configuration();\""
echo "   Check status:   drush php:eval \"newsmotivationmetrics_check_block_status();\""
echo "   Clear caches:   drush cache:rebuild"

echo
echo "=== Test Complete ==="
