#!/bin/bash

echo "=== Logging Framework Utilization Summary ==="
echo ""

# Check module by module with specific counts
echo "📊 LOGGING FRAMEWORK UTILIZATION STATUS"
echo "========================================="
echo ""

# news_extractor analysis
echo "📦 news_extractor:"
echo "  - ConfigurableLoggingTrait.php: $(ls /workspaces/thetruthperspective/news_extractor/src/Traits/ConfigurableLoggingTrait.php 2>/dev/null && echo '✅ Present' || echo '❌ Missing')"

old_logging_ne=$(find /workspaces/thetruthperspective/news_extractor -name "*.php" -o -name "*.module" | xargs grep -l "Drupal::logger\|->info\|->debug\|->warning\|->error" 2>/dev/null | wc -l)
new_logging_ne=$(find /workspaces/thetruthperspective/news_extractor -name "*.php" -o -name "*.module" | xargs grep -l "logInfo\|logDebug\|logWarning\|logError" 2>/dev/null | wc -l)
trait_usage_ne=$(find /workspaces/thetruthperspective/news_extractor/src -name "*.php" -exec grep -l "use ConfigurableLoggingTrait" {} \; 2>/dev/null | wc -l)

echo "  - Files with old logging patterns: $old_logging_ne"
echo "  - Files with new logging patterns: $new_logging_ne"
echo "  - Classes using ConfigurableLoggingTrait: $trait_usage_ne"

if [[ $old_logging_ne -gt 0 ]] && [[ $new_logging_ne -eq 0 ]]; then
    echo "  ❌ NOT MIGRATED - Using old logging patterns only"
elif [[ $old_logging_ne -gt 0 ]] && [[ $new_logging_ne -gt 0 ]]; then
    echo "  🔄 PARTIALLY MIGRATED - Mixed old/new patterns"
elif [[ $old_logging_ne -eq 0 ]] && [[ $new_logging_ne -gt 0 ]]; then
    echo "  ✅ FULLY MIGRATED - Using new logging framework"
else
    echo "  ℹ️  NO LOGGING - No logging calls found"
fi
echo ""

# social_media_automation analysis  
echo "📦 social_media_automation:"
echo "  - ConfigurableLoggingTrait.php: $(ls /workspaces/thetruthperspective/social_media_automation/src/Traits/ConfigurableLoggingTrait.php 2>/dev/null && echo '✅ Present' || echo '❌ Missing')"

old_logging_sma=$(find /workspaces/thetruthperspective/social_media_automation -name "*.php" -o -name "*.module" | xargs grep -l "Drupal::logger\|->info\|->debug\|->warning\|->error" 2>/dev/null | wc -l)
new_logging_sma=$(find /workspaces/thetruthperspective/social_media_automation -name "*.php" -o -name "*.module" | xargs grep -l "logInfo\|logDebug\|logWarning\|logError" 2>/dev/null | wc -l)
trait_usage_sma=$(find /workspaces/thetruthperspective/social_media_automation/src -name "*.php" -exec grep -l "use ConfigurableLoggingTrait" {} \; 2>/dev/null | wc -l)

echo "  - Files with old logging patterns: $old_logging_sma"
echo "  - Files with new logging patterns: $new_logging_sma"
echo "  - Classes using ConfigurableLoggingTrait: $trait_usage_sma"

if [[ $old_logging_sma -gt 0 ]] && [[ $new_logging_sma -eq 0 ]]; then
    echo "  ❌ NOT MIGRATED - Using old logging patterns only"
elif [[ $old_logging_sma -gt 0 ]] && [[ $new_logging_sma -gt 0 ]]; then
    echo "  🔄 PARTIALLY MIGRATED - Mixed old/new patterns"
elif [[ $old_logging_sma -eq 0 ]] && [[ $new_logging_sma -gt 0 ]]; then
    echo "  ✅ FULLY MIGRATED - Using new logging framework"
else
    echo "  ℹ️  NO LOGGING - No logging calls found"
fi
echo ""

# newsmotivationmetrics analysis
echo "📦 newsmotivationmetrics:"
echo "  - ConfigurableLoggingTrait.php: $(ls /workspaces/thetruthperspective/newsmotivationmetrics/src/Traits/ConfigurableLoggingTrait.php 2>/dev/null && echo '✅ Present' || echo '❌ Missing')"

old_logging_nmm=$(find /workspaces/thetruthperspective/newsmotivationmetrics -name "*.php" -o -name "*.module" | xargs grep -l "Drupal::logger\|->info\|->debug\|->warning\|->error" 2>/dev/null | wc -l)
new_logging_nmm=$(find /workspaces/thetruthperspective/newsmotivationmetrics -name "*.php" -o -name "*.module" | xargs grep -l "logInfo\|logDebug\|logWarning\|logError" 2>/dev/null | wc -l)
trait_usage_nmm=$(find /workspaces/thetruthperspective/newsmotivationmetrics/src -name "*.php" -exec grep -l "use ConfigurableLoggingTrait" {} \; 2>/dev/null | wc -l)

echo "  - Files with old logging patterns: $old_logging_nmm"
echo "  - Files with new logging patterns: $new_logging_nmm"
echo "  - Classes using ConfigurableLoggingTrait: $trait_usage_nmm"

if [[ $old_logging_nmm -gt 0 ]] && [[ $new_logging_nmm -eq 0 ]]; then
    echo "  ❌ NOT MIGRATED - Using old logging patterns only"
elif [[ $old_logging_nmm -gt 0 ]] && [[ $new_logging_nmm -gt 0 ]]; then
    echo "  🔄 PARTIALLY MIGRATED - Mixed old/new patterns"
elif [[ $old_logging_nmm -eq 0 ]] && [[ $new_logging_nmm -gt 0 ]]; then
    echo "  ✅ FULLY MIGRATED - Using new logging framework"
else
    echo "  ℹ️  NO LOGGING - No logging calls found"
fi
echo ""

# ai_conversation analysis
echo "📦 ai_conversation:"
echo "  - ConfigurableLoggingTrait.php: $(ls /workspaces/thetruthperspective/ai_conversation/src/Traits/ConfigurableLoggingTrait.php 2>/dev/null && echo '✅ Present' || echo '❌ Missing')"

old_logging_ai=$(find /workspaces/thetruthperspective/ai_conversation -name "*.php" -o -name "*.module" | xargs grep -l "Drupal::logger\|->info\|->debug\|->warning\|->error" 2>/dev/null | wc -l)
new_logging_ai=$(find /workspaces/thetruthperspective/ai_conversation -name "*.php" -o -name "*.module" | xargs grep -l "logInfo\|logDebug\|logWarning\|logError" 2>/dev/null | wc -l)
trait_usage_ai=$(find /workspaces/thetruthperspective/ai_conversation/src -name "*.php" -exec grep -l "use ConfigurableLoggingTrait" {} \; 2>/dev/null | wc -l)

echo "  - Files with old logging patterns: $old_logging_ai"
echo "  - Files with new logging patterns: $new_logging_ai"
echo "  - Classes using ConfigurableLoggingTrait: $trait_usage_ai"

if [[ $old_logging_ai -gt 0 ]] && [[ $new_logging_ai -eq 0 ]]; then
    echo "  ❌ NOT MIGRATED - Using old logging patterns only"
elif [[ $old_logging_ai -gt 0 ]] && [[ $new_logging_ai -gt 0 ]]; then
    echo "  🔄 PARTIALLY MIGRATED - Mixed old/new patterns"
elif [[ $old_logging_ai -eq 0 ]] && [[ $new_logging_ai -gt 0 ]]; then
    echo "  ✅ FULLY MIGRATED - Using new logging framework"
else
    echo "  ℹ️  NO LOGGING - No logging calls found"
fi
echo ""

# Calculate totals
total_old=$(($old_logging_ne + $old_logging_sma + $old_logging_nmm + $old_logging_ai))
total_new=$(($new_logging_ne + $new_logging_sma + $new_logging_nmm + $new_logging_ai))
total_traits=$(($trait_usage_ne + $trait_usage_sma + $trait_usage_nmm + $trait_usage_ai))

echo "📊 PLATFORM SUMMARY"
echo "==================="
echo "Total files with old logging patterns: $total_old"
echo "Total files with new logging patterns: $total_new"
echo "Total classes using ConfigurableLoggingTrait: $total_traits"
echo ""

# Overall status
if [[ $total_old -gt 0 ]] && [[ $total_new -eq 0 ]]; then
    echo "🚨 PLATFORM STATUS: NOT MIGRATED - All logging using old patterns"
elif [[ $total_old -gt 0 ]] && [[ $total_new -gt 0 ]]; then
    echo "⚠️  PLATFORM STATUS: PARTIALLY MIGRATED - Mixed logging patterns"
elif [[ $total_old -eq 0 ]] && [[ $total_new -gt 0 ]]; then
    echo "✅ PLATFORM STATUS: FULLY MIGRATED - All logging using new framework"
else
    echo "ℹ️  PLATFORM STATUS: NO LOGGING DETECTED"
fi

echo ""
echo "🔧 KEY FILES TO UPDATE"
echo "======================"

if [[ $old_logging_ne -gt 0 ]]; then
    echo "📦 news_extractor files needing update:"
    find /workspaces/thetruthperspective/news_extractor -name "*.php" -o -name "*.module" | xargs grep -l "Drupal::logger\|->info\|->debug\|->warning\|->error" 2>/dev/null | while read file; do
        echo "  - $(basename $file)"
    done
    echo ""
fi

if [[ $old_logging_sma -gt 0 ]]; then
    echo "📦 social_media_automation files needing update:"
    find /workspaces/thetruthperspective/social_media_automation -name "*.php" -o -name "*.module" | xargs grep -l "Drupal::logger\|->info\|->debug\|->warning\|->error" 2>/dev/null | while read file; do
        echo "  - $(basename $file)"
    done
    echo ""
fi

if [[ $old_logging_ai -gt 0 ]]; then
    echo "📦 ai_conversation files needing update:"
    find /workspaces/thetruthperspective/ai_conversation -name "*.php" -o -name "*.module" | xargs grep -l "Drupal::logger\|->info\|->debug\|->warning\|->error" 2>/dev/null | while read file; do
        echo "  - $(basename $file)"
    done
    echo ""
fi

echo "💡 NEXT ACTIONS REQUIRED"
echo "========================"
echo "1. For CLASS files: Add 'use ConfigurableLoggingTrait' and replace logging calls"
echo "2. For PROCEDURAL files: Use centralized service calls"
echo "3. Test logging configuration after updates"
echo "4. Deploy to production with ERROR-only logging"
echo ""
echo "📖 See: newsmotivationmetrics/LOGGING_SYSTEM.md for detailed instructions"
echo ""
