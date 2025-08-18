# Mastodon Setup Guide for The Truth Perspective

## Quick Start: Setting up Mastodon Integration

### Step 1: Create a Mastodon Account
1. Go to https://mastodon.social (or any Mastodon instance)
2. Sign up for a new account
3. Complete email verification

### Step 2: Create an Application for API Access
1. Go to **Preferences** → **Development** 
2. Click **New Application**
3. Fill in the form:
   - **Application name**: "The Truth Perspective Bot"
   - **Application website**: https://thetruthperspective.org
   - **Redirect URI**: Leave default (urn:ietf:wg:oauth:2.0:oob)
   - **Scopes**: Select "write" (for posting) and "read" (for verification)
4. Click **Submit**

### Step 3: Get Your Access Token
1. Click on your newly created application
2. Copy the **Access token** - this is what you'll paste into the Drupal admin
3. Note the **Server URL** (e.g., https://mastodon.social)

### Step 4: Configure in Drupal (DEBUGGING MODE)
1. Go to https://thetruthperspective.org/admin/config/services/social-media-automation/settings
2. **Current Status**: Form is simplified to show only Mastodon and testing sections for debugging
3. Under "Mastodon Configuration":
   - **Mastodon Server URL**: https://mastodon.social (or your chosen server)
   - **Access Token**: Paste the token from Step 3
   - **Enable Mastodon**: Check the box
4. Click "Test Mastodon Connection" to verify
5. **Most Important**: Click "Save configuration" and then check the logs:
   ```bash
   sudo -u www-data drush watchdog:show --count=20 --type=social_media_automation | grep -E "(DEBUG TEST|PLATFORM STRUCTURE|MASTODON)"
   ```
6. **Console Debugging**: Open browser console (F12) to see any JavaScript errors

### Step 5: Debugging Form Submission (FIXED ISSUES)
**Recent fixes applied:**
- ✅ **Form submission working**: Data IS being saved to database
- ✅ **Enabled checkbox**: Fixed to properly save enabled status  
- ✅ **URL validation**: Automatically removes `www.` prefix from server URLs

**Check these after submission:**
1. **Form Values**: Should now show proper enabled status and clean URLs
   ```bash
   # Check the comprehensive debug output
   sudo -u www-data drush watchdog:show --count=20 --type=social_media_automation | grep -E "(ENABLED DEBUG|URL CLEANUP)"
   ```

2. **Database Verification**: Should show enabled=true and clean URL
   ```bash
   # Check what's actually saved
   sudo -u www-data drush watchdog:show --count=10 --type=social_media_automation | grep "DB CHECK"
   ```

3. **Connection Test**: Should now work with proper credentials
   ```bash
   # Look for successful connection
   sudo -u www-data drush watchdog:show --count=10 --type=social_media_automation | grep "connection test"
   ```

**Expected Success Output:**
- `🔧 ENABLED DEBUG - Final platform_values[enabled]: "1"`
- `🔧 URL CLEANUP - Original: "https://www.mastodon.social", Cleaned: "https://mastodon.social"`
- `🔍 DB CHECK - mastodon.enabled: "TRUE"`
- `=== Mastodon connection test completed ===` (without 401 errors)
1. In the admin interface, scroll to "Testing" section
2. Select content type (Analytics Summary recommended)
3. Click "Test Mastodon Only"
4. Check your Mastodon account for the test post

## Alternative Mastodon Servers (Free Options)

### General Purpose Servers
- **mastodon.social** - Main instance, most stable
- **mastodon.online** - General purpose, good uptime
- **fosstodon.org** - Tech/open source focused
- **mstdn.social** - General community server

### News/Politics Focused Servers
- **journa.host** - Journalist community
- **newsie.social** - News enthusiasts
- **social.vivaldi.net** - Vivaldi browser community (tech news)

### How to Choose a Server
1. **Stability**: Larger servers like mastodon.social are most stable
2. **Community**: Pick a server aligned with your content focus
3. **Rules**: Check server rules to ensure your content is welcome
4. **Federation**: All servers can communicate with each other

## API Rate Limits
- **Mastodon**: 300 requests per 5-minute window
- **Character Limit**: 500 characters per post
- **Media**: Supports images, videos, polls
- **Cost**: Completely free

## Troubleshooting

### Connection Test Fails
1. **Double-check access token**: Make sure you copied the full token
2. **Verify server URL**: Must include https:// and exact domain
3. **Check application permissions**: Ensure "write" scope is enabled
4. **Server status**: Check if the Mastodon server is online

### Posts Not Appearing
1. **Check character limit**: Must be under 500 characters
2. **Rate limiting**: Wait a few minutes between posts
3. **Server connectivity**: Test connection first
4. **Content filtering**: Some servers filter certain content

### Getting Better Reach
1. **Use relevant hashtags**: #News #Politics #MediaAnalysis
2. **Post timing**: Check when your server's users are most active
3. **Engage**: Reply to others and build community
4. **Quality content**: Mastodon users value thoughtful content

## Content Strategy for The Truth Perspective

### Effective Post Types
1. **Analytics Summaries**: "📊 Latest analysis shows..."
2. **Bias Insights**: "🔍 New research reveals media bias patterns..."
3. **Trending Topics**: "📈 Currently tracking: [topic]"
4. **Methodology**: "🔬 How we analyze media narratives..."

### Hashtag Strategy
- Primary: #MediaAnalysis #NewsAnalysis #TruthPerspective
- Secondary: #MediaBias #Journalism #DataAnalysis
- Trending: Use current event hashtags when relevant

### Posting Schedule
- **Morning Posts (8-12 PM)**: Analytics summaries, research insights
- **Evening Posts (6-10 PM)**: Trending topics, community engagement

Remember: Mastodon values authentic community engagement over promotional content. Focus on providing value and insights rather than just promoting the website.
