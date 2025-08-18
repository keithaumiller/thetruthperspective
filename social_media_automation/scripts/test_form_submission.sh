#!/bin/bash

# Test form submission with Mastodon credentials
echo "Testing form submission with platform structure..."

# First get the form page to extract form tokens
FORM_PAGE=$(curl -s "https://thetruthperspective.org/admin/config/social-media-automation" -H "Cookie: $(cat /workspaces/thetruthperspective/cookie.txt)")

# Extract form tokens
FORM_BUILD_ID=$(echo "$FORM_PAGE" | grep -o 'name="form_build_id" value="[^"]*"' | sed 's/name="form_build_id" value="//;s/"//')
FORM_TOKEN=$(echo "$FORM_PAGE" | grep -o 'name="form_token" value="[^"]*"' | sed 's/name="form_token" value="//;s/"//')

echo "Form Build ID: $FORM_BUILD_ID"
echo "Form Token: $FORM_TOKEN"

# Submit form with test Mastodon data
curl -X POST "https://thetruthperspective.org/admin/config/social-media-automation" \
  -H "Cookie: $(cat /workspaces/thetruthperspective/cookie.txt)" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  --data-urlencode "enabled=1" \
  --data-urlencode "platforms[mastodon][enabled]=1" \
  --data-urlencode "platforms[mastodon][server_url]=https://mastodon.social" \
  --data-urlencode "platforms[mastodon][access_token]=test_mastodon_token_12345" \
  --data-urlencode "test_server_url=https://mastodon.social" \
  --data-urlencode "test_access_token=test_token_12345_should_appear_in_logs" \
  --data-urlencode "test_checkbox=1" \
  --data-urlencode "form_build_id=$FORM_BUILD_ID" \
  --data-urlencode "form_token=$FORM_TOKEN" \
  --data-urlencode "form_id=social_media_automation_settings_form" \
  --data-urlencode "op=Save configuration" \
  -v

echo -e "\n\nChecking logs for form submission data..."
echo "Looking for DEBUG TEST and platform structure..."
