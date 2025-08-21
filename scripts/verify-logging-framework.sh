#!/bin/bash

echo "=== The Truth Perspective - Logging Framework Verification ==="
echo ""

# Function to check if a file uses the trait
check_trait_usage() {
  local file=$1
  if grep -q "use ConfigurableLoggingTrait" "$file" 2>/dev/null; then
    echo "    ✅ Uses ConfigurableLoggingTrait"
    return 0
  else
    echo "    ❌ NOT using ConfigurableLoggingTrait"
    return 1
  fi
}

# Function to check logging call patterns
check_logging_patterns() {
  local file=$1
  local old_pattern_count=$(grep -c "\->info\|\->debug\|\->warning\|\->error\|\\\\Drupal::logger" "$file" 2>/dev/null || echo "0")
  local new_pattern_count=$(grep -c "logInfo\|logDebug\|logWarning\|logError" "$file" 2>/dev/null || echo "0")
  
  if [[ "$old_pattern_count" -gt 0 ]] && [[ "$new_pattern_count" -eq 0 ]]; then
    echo "    ⚠️  Has $old_pattern_count OLD logging calls (needs updating)"
    return 1
  elif [[ "$new_pattern_count" -gt 0 ]]; then
    echo "    ✅ Has $new_pattern_count NEW logging calls"
    return 0
  else
    echo "    ℹ️  No logging calls found"
    return 0
  fi
}

echo "📊 CENTRALIZED LOGGING FRAMEWORK STATUS"
echo ""

# Define modules
MODULES=(
  "news_extractor"
  "social_media_automation" 
  "newsmotivationmetrics"
  "ai_conversation"
  "job_application_automation"
  "twitter_automation"
)

total_files=0
compliant_files=0
trait_files=0

for module in "${MODULES[@]}"; do
  echo "📦 Module: $module"
  
  # Check if trait file exists
  trait_file="/workspaces/thetruthperspective/${module}/src/Traits/ConfigurableLoggingTrait.php"
  if [ -f "$trait_file" ]; then
    echo "  📁 ConfigurableLoggingTrait.php: ✅ Present"
    trait_files=$((trait_files + 1))
  else
    echo "  📁 ConfigurableLoggingTrait.php: ❌ Missing"
  fi
  
  # Find all PHP class files in this module
  php_files=$(find "/workspaces/thetruthperspective/${module}/src" -name "*.php" -type f 2>/dev/null | grep -v Traits)
  
  if [ -z "$php_files" ]; then
    echo "  📄 No PHP class files found in src/"
  else
    echo "  📄 Checking PHP class files:"
    
    while IFS= read -r file; do
      if [ -f "$file" ]; then
        total_files=$((total_files + 1))
        filename=$(basename "$file")
        echo "    🔍 $filename"
        
        # Check if it's a class file
        if grep -q "^class " "$file" 2>/dev/null; then
          check_trait_usage "$file"
          trait_result=$?
          
          check_logging_patterns "$file"
          pattern_result=$?
          
          if [ $trait_result -eq 0 ] || [ $pattern_result -eq 0 ]; then
            compliant_files=$((compliant_files + 1))
          fi
        else
          echo "    ℹ️  Not a class file (procedural code)"
        fi
      fi
    done <<< "$php_files"
  fi
  
  # Check procedural files (like .module files)
  echo "  📄 Checking procedural files:"
  
  procedural_files=$(find "/workspaces/thetruthperspective/${module}" -name "*.php" -type f -not -path "*/src/*" 2>/dev/null)
  procedural_files+=$'\n'$(find "/workspaces/thetruthperspective/${module}" -name "*.module" -type f 2>/dev/null)
  
  if [ -n "$procedural_files" ]; then
    while IFS= read -r file; do
      if [ -f "$file" ] && [ -n "$file" ]; then
        filename=$(basename "$file")
        echo "    🔍 $filename"
        check_logging_patterns "$file"
      fi
    done <<< "$procedural_files"
  fi
  
  echo ""
done

echo "📋 SUMMARY"
echo "=========="
echo "Total modules checked: ${#MODULES[@]}"
echo "Modules with traits: $trait_files/${#MODULES[@]}"
echo "PHP class files checked: $total_files"
echo "Compliant class files: $compliant_files/$total_files"
echo ""

if [ $trait_files -eq ${#MODULES[@]} ]; then
  echo "✅ All modules have ConfigurableLoggingTrait available"
else
  echo "❌ Some modules missing ConfigurableLoggingTrait"
fi

echo ""
echo "🔧 INTEGRATION STATUS BY MODULE"
echo "================================"

for module in "${MODULES[@]}"; do
  echo "📦 $module:"
  
  # Count files using trait
  trait_usage=$(find "/workspaces/thetruthperspective/${module}/src" -name "*.php" -type f -exec grep -l "use ConfigurableLoggingTrait" {} \; 2>/dev/null | wc -l)
  
  # Count files with old logging patterns
  old_logging=$(find "/workspaces/thetruthperspective/${module}" -name "*.php" -o -name "*.module" | xargs grep -l "->info\|->debug\|->warning\|->error\|\\Drupal::logger" 2>/dev/null | wc -l)
  
  # Count files with new logging patterns  
  new_logging=$(find "/workspaces/thetruthperspective/${module}" -name "*.php" -o -name "*.module" | xargs grep -l "logInfo\|logDebug\|logWarning\|logError" 2>/dev/null | wc -l)
  
  echo "  - Classes using trait: $trait_usage"
  echo "  - Files with old logging: $old_logging"
  echo "  - Files with new logging: $new_logging"
  
  if [ $old_logging -eq 0 ] && [ $new_logging -gt 0 ]; then
    echo "  ✅ Fully migrated to new logging framework"
  elif [ $old_logging -gt 0 ] && [ $new_logging -gt 0 ]; then
    echo "  🔄 Partially migrated (mixed old/new logging)"
  elif [ $old_logging -gt 0 ] && [ $new_logging -eq 0 ]; then
    echo "  ❌ Using old logging patterns only"
  else
    echo "  ℹ️  No logging calls found"
  fi
  
  echo ""
done

echo "🎯 NEXT STEPS"
echo "============="
echo "1. Update class files to use ConfigurableLoggingTrait"
echo "2. Replace old logging calls with new trait methods:"
echo "   - \\Drupal::logger('module')->info(...) → \$this->logInfo(...)"
echo "   - \$this->logger->error(...) → \$this->logError(...)"
echo "3. For procedural files, use centralized service:"
echo "   - \\Drupal::service('newsmotivationmetrics.logging_config')->info('channel', ...)"
echo ""
echo "📚 Documentation: newsmotivationmetrics/LOGGING_SYSTEM.md"
echo "🔗 Admin Interface: /admin/config/development/thetruthperspective/logging"
echo ""
