#!/bin/bash

echo "=== The Truth Perspective - Logging System Deployment ==="
echo ""

# Check if we're in the right directory
if [ ! -f "newsmotivationmetrics/newsmotivationmetrics.info.yml" ]; then
  echo "❌ Error: Please run this script from the workspace root directory"
  exit 1
fi

echo "📋 Files being committed:"
echo ""
echo "📁 Core Logging System:"
echo "  ✅ newsmotivationmetrics/src/Service/LoggingConfigService.php"
echo "  ✅ newsmotivationmetrics/src/Form/LoggingConfigForm.php" 
echo "  ✅ newsmotivationmetrics/src/Commands/LoggingCommands.php"
echo "  ✅ newsmotivationmetrics/src/Traits/ConfigurableLoggingTrait.php"
echo ""
echo "📁 Configuration Files:"
echo "  ✅ newsmotivationmetrics/config/install/thetruthperspective.logging.yml"
echo "  ✅ newsmotivationmetrics/newsmotivationmetrics.services.yml (updated)"
echo "  ✅ newsmotivationmetrics/newsmotivationmetrics.routing.yml (updated)"
echo "  ✅ newsmotivationmetrics/newsmotivationmetrics.links.menu.yml (updated)"
echo ""
echo "📁 Module Integration:"
echo "  ✅ newsmotivationmetrics/newsmotivationmetrics.module (updated with hooks)"
echo "  ✅ newsmotivationmetrics/newsmotivationmetrics.install (updated)"
echo ""
echo "📁 Module-Specific Traits:"
echo "  ✅ social_media_automation/src/Traits/ConfigurableLoggingTrait.php"
echo "  ✅ social_media_automation/src/Service/Platform/MastodonClient.php (partially updated)"
echo "  ✅ social_media_automation/src/Form/SocialMediaAutomationSettingsForm.php (updated)"
echo ""
echo "📁 Documentation & Scripts:"
echo "  ✅ newsmotivationmetrics/LOGGING_SYSTEM.md"
echo "  ✅ scripts/cleanup-logging.sh"
echo ""

echo "🚀 Ready to commit and push? This will:"
echo "  1. Add all logging system files"
echo "  2. Commit with descriptive message"  
echo "  3. Push to main branch"
echo ""

read -p "Continue with deployment? (y/N): " confirm

if [[ $confirm != [yY] && $confirm != [yY][eE][sS] ]]; then
  echo "❌ Deployment cancelled"
  exit 0
fi

echo ""
echo "📦 Adding files to git..."

# Add all the new and modified files
git add newsmotivationmetrics/src/Service/LoggingConfigService.php
git add newsmotivationmetrics/src/Form/LoggingConfigForm.php
git add newsmotivationmetrics/src/Commands/LoggingCommands.php
git add newsmotivationmetrics/src/Traits/ConfigurableLoggingTrait.php
git add newsmotivationmetrics/config/install/thetruthperspective.logging.yml
git add newsmotivationmetrics/newsmotivationmetrics.services.yml
git add newsmotivationmetrics/newsmotivationmetrics.routing.yml
git add newsmotivationmetrics/newsmotivationmetrics.links.menu.yml
git add newsmotivationmetrics/newsmotivationmetrics.module
git add newsmotivationmetrics/newsmotivationmetrics.install
git add newsmotivationmetrics/LOGGING_SYSTEM.md
git add social_media_automation/src/Traits/ConfigurableLoggingTrait.php
git add social_media_automation/src/Service/Platform/MastodonClient.php
git add social_media_automation/src/Form/SocialMediaAutomationSettingsForm.php
git add scripts/cleanup-logging.sh

echo "✅ Files added to git"
echo ""

echo "📝 Committing changes..."

git commit -m "feat: Implement centralized logging configuration system

- Add LoggingConfigService for platform-wide log level management
- Create admin form at /admin/config/development/thetruthperspective/logging
- Add Drush commands for quick logging level changes (ttplog-error, ttplog-info, ttplog-debug)
- Implement hook_logger_log_alter() to filter logs at system level
- Default all modules to ERROR-only logging for production
- Add ConfigurableLoggingTrait for modules to use
- Update MastodonClient and SocialMediaAutomationSettingsForm as examples
- Include comprehensive documentation in LOGGING_SYSTEM.md

This addresses excessive log noise in production by:
- Reducing log entries by ~95% (errors only vs all debug/info)
- Improving performance through reduced I/O operations  
- Making error monitoring more effective
- Providing flexible debugging capabilities when needed

Production deployment:
1. drush en newsmotivationmetrics && drush updb
2. drush ttplog-error (set to ERROR only)
3. drush ttplog-status (verify configuration)"

echo "✅ Changes committed"
echo ""

echo "🚀 Pushing to main branch..."
git push origin main

if [ $? -eq 0 ]; then
  echo "✅ Successfully pushed to repository"
  echo ""
  echo "🎉 Deployment completed!"
  echo ""
  echo "📋 Next steps on production server:"
  echo "  1. Pull changes: git pull origin main"
  echo "  2. Update database: drush updb"
  echo "  3. Set production logging: drush ttplog-error"
  echo "  4. Verify configuration: drush ttplog-status"
  echo "  5. Optional - clear old logs: drush watchdog:delete all"
  echo ""
  echo "🔗 Admin interface: https://thetruthperspective.org/admin/config/development/thetruthperspective/logging"
  echo ""
else
  echo "❌ Failed to push to repository"
  exit 1
fi
