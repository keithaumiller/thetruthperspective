#!/bin/bash

echo "=== The Truth Perspective Logging Cleanup Script ==="
echo "This script converts legacy logging calls to use the new configurable logging system."
echo ""

# Define the modules to update
MODULES=(
  "news_extractor"
  "social_media_automation" 
  "newsmotivationmetrics"
  "ai_conversation"
  "job_application_automation"
  "twitter_automation"
)

# Function to copy the logging trait to each module
copy_logging_trait() {
  local module=$1
  local trait_path="/workspaces/thetruthperspective/${module}/src/Traits"
  
  echo "📁 Creating Traits directory for ${module}..."
  mkdir -p "${trait_path}"
  
  echo "📄 Copying ConfigurableLoggingTrait to ${module}..."
  cat > "${trait_path}/ConfigurableLoggingTrait.php" << 'EOF'
<?php

namespace Drupal\MODULE_NAME\Traits;

/**
 * Trait for implementing controlled logging in MODULE_NAME.
 * 
 * This trait provides standard logging methods that respect the platform's
 * logging configuration to reduce log noise in production environments.
 */
trait ConfigurableLoggingTrait {

  /**
   * Get the current logging level for this module.
   *
   * @return int
   *   The logging level.
   */
  protected function getLogLevel(): int {
    $config = \Drupal::config('thetruthperspective.logging');
    
    // Check for module-specific override first
    $module_level = $config->get('modules.MODULE_NAME.level');
    if ($module_level !== NULL) {
      return (int) $module_level;
    }
    
    // Fall back to global level (default to ERROR only)
    return (int) $config->get('global.level') ?? 1;
  }

  /**
   * Check if a log level should be recorded.
   *
   * @param int $message_level
   *   The level of the message being logged (1=error, 2=warning, 3=info, 4=debug).
   *
   * @return bool
   *   TRUE if the message should be logged.
   */
  protected function shouldLog(int $message_level): bool {
    $configured_level = $this->getLogLevel();
    return $message_level <= $configured_level;
  }

  /**
   * Log an error message (always logs).
   *
   * @param string $message
   *   The message to log.
   * @param array $context
   *   The context array.
   */
  protected function logError(string $message, array $context = []): void {
    if (isset($this->logger)) {
      $this->logger->error($message, $context);
    }
  }

  /**
   * Log a warning message (only if warnings enabled).
   *
   * @param string $message
   *   The message to log.
   * @param array $context
   *   The context array.
   */
  protected function logWarning(string $message, array $context = []): void {
    if ($this->shouldLog(2) && isset($this->logger)) {
      $this->logger->warning($message, $context);
    }
  }

  /**
   * Log an info message (only if info enabled).
   *
   * @param string $message
   *   The message to log.
   * @param array $context
   *   The context array.
   */
  protected function logInfo(string $message, array $context = []): void {
    if ($this->shouldLog(3) && isset($this->logger)) {
      $this->logger->info($message, $context);
    }
  }

  /**
   * Log a debug message (only if debug enabled).
   *
   * @param string $message
   *   The message to log.
   * @param array $context
   *   The context array.
   */
  protected function logDebug(string $message, array $context = []): void {
    if ($this->shouldLog(4) && isset($this->logger)) {
      $this->logger->debug($message, $context);
    }
  }

}
EOF

  # Replace MODULE_NAME with actual module name
  sed -i "s/MODULE_NAME/${module}/g" "${trait_path}/ConfigurableLoggingTrait.php"
  
  echo "✅ Logging trait created for ${module}"
}

# Function to update PHP files to use the new logging methods
update_logging_calls() {
  local module=$1
  local module_path="/workspaces/thetruthperspective/${module}"
  
  echo "🔄 Updating logging calls in ${module}..."
  
  # Find all PHP files in the module
  find "${module_path}" -name "*.php" -type f | while read -r php_file; do
    # Skip if file doesn't contain logging calls
    if ! grep -q "->logger->" "${php_file}" 2>/dev/null; then
      continue
    fi
    
    echo "  📝 Processing: $(basename "${php_file}")"
    
    # Create a backup
    cp "${php_file}" "${php_file}.backup"
    
    # Replace logging calls (be careful with sed)
    sed -i 's/\$this->logger->error(/\$this->logError(/g' "${php_file}"
    sed -i 's/\$this->logger->warning(/\$this->logWarning(/g' "${php_file}"
    sed -i 's/\$this->logger->info(/\$this->logInfo(/g' "${php_file}"
    sed -i 's/\$this->logger->debug(/\$this->logDebug(/g' "${php_file}"
    
    # Check if the file uses any of our logging methods now
    if grep -q "logError\|logWarning\|logInfo\|logDebug" "${php_file}"; then
      # Add the trait if not already present
      if ! grep -q "use.*ConfigurableLoggingTrait" "${php_file}"; then
        # Find the class declaration and add the trait
        if grep -q "^class " "${php_file}"; then
          # Add trait after class declaration
          sed -i "/^class /a\\  use \\\\Drupal\\\\${module}\\\\Traits\\\\ConfigurableLoggingTrait;" "${php_file}"
          echo "    ✅ Added ConfigurableLoggingTrait to $(basename "${php_file}")"
        fi
      fi
    else
      # No logging methods used, restore backup
      mv "${php_file}.backup" "${php_file}"
    fi
  done
}

# Main execution
echo "Starting logging cleanup for The Truth Perspective platform..."
echo ""

for module in "${MODULES[@]}"; do
  echo "📦 Processing module: ${module}"
  
  # Copy the logging trait
  copy_logging_trait "${module}"
  
  # Update logging calls
  update_logging_calls "${module}"
  
  echo ""
done

echo "🎉 Logging cleanup completed!"
echo ""
echo "Next steps:"
echo "1. Test the logging configuration form at /admin/config/development/thetruthperspective/logging"
echo "2. Set logging level to 'Error Only' for production"
echo "3. Commit and push the changes"
echo "4. Deploy to production server"
