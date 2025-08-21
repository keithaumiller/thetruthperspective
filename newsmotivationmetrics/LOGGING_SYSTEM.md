# The Truth Perspective - Logging Configuration System

## Overview

This document describes the centralized logging management system implemented to reduce log noise and improve performance in The Truth Perspective platform.

## Problem Addressed

The platform was generating excessive log entries (debug, info, warning messages) that:
- Cluttered system logs making error diagnosis difficult
- Impacted performance with unnecessary I/O operations
- Made monitoring and alerting less effective

## Solution

A centralized logging configuration system that:
- **Filters logs at the system level** using Drupal's `hook_logger_log_alter()`
- **Defaults to ERROR-only logging** for production environments
- **Provides easy configuration** via web interface and Drush commands
- **Maintains module independence** while offering centralized control

## Configuration Levels

| Level | Name | Includes |
|-------|------|----------|
| 1 | **ERROR ONLY** | Emergency, Alert, Critical, Error |
| 2 | **WARNING & ERROR** | + Warning |
| 3 | **INFO, WARNING & ERROR** | + Notice, Info |
| 4 | **ALL (INCLUDING DEBUG)** | + Debug |

## Quick Commands

### Check Current Status
```bash
drush ttplog-status
```

### Production Setting (Recommended)
```bash
drush ttplog-error
```

### Debugging Setting
```bash
drush ttplog-info
```

### Development Setting (Verbose)
```bash
drush ttplog-debug
```

### Module-Specific Setting
```bash
drush ttplog-set social_media_automation 1
drush ttplog-set news_extractor 3
```

## Web Interface

Configure logging levels at:
**Administration → Configuration → Development → Platform Logging Configuration**

Direct URL: `/admin/config/development/thetruthperspective/logging`

## Implementation Details

### System Integration
- **Hook**: `newsmotivationmetrics_logger_log_alter()` in `newsmotivationmetrics.module`
- **Configuration**: Stored in `thetruthperspective.logging` config
- **Service**: `newsmotivationmetrics.logging_config` for programmatic access

### Default Configuration
All modules default to **ERROR ONLY** (level 1) for production safety:
- `news_extractor`
- `newsmotivationmetrics` 
- `social_media_automation`
- `ai_conversation`
- `job_application_automation`
- `twitter_automation`

### Module Independence
Individual modules can override global settings:
```yaml
modules:
  social_media_automation:
    level: 2  # WARNING & ERROR for this module only
```

## Production Deployment

### 1. Install/Update Module
```bash
drush en newsmotivationmetrics
drush updb
```

### 2. Set Production Logging
```bash
drush ttplog-error
```

### 3. Verify Configuration
```bash
drush ttplog-status
```

### 4. Clear Existing Logs (Optional)
```bash
drush watchdog:delete all
```

### 5. Monitor Results
```bash
# Check only errors
drush watchdog:show --severity=3

# Check specific module errors  
drush watchdog:show --type=social_media_automation --severity=3
```

## Troubleshooting

### Enable Debug for Specific Issue
```bash
# Enable debug for problematic module
drush ttplog-set social_media_automation 4

# Reproduce issue
# ...

# Return to production setting
drush ttplog-set social_media_automation 1
```

### Temporary Full Debugging
```bash
# Enable all logging
drush ttplog-debug

# Reproduce issue and check logs
drush watchdog:show --count=50

# Return to production
drush ttplog-error
```

## Performance Impact

### Before (Excessive Logging)
- Debug messages: ~500-1000 per hour
- Info messages: ~200-400 per hour  
- Total: ~700-1400 log entries per hour

### After (ERROR Only)
- Error messages: ~5-20 per hour
- Reduction: **~95% fewer log entries**

## Benefits

1. **Cleaner Logs**: Only critical errors visible in production
2. **Better Performance**: Reduced I/O from logging operations
3. **Easier Monitoring**: Errors stand out clearly
4. **Flexible Debugging**: Easy to enable verbose logging when needed
5. **Consistent Platform**: All modules use same logging standards

## Files Modified

- `newsmotivationmetrics.module` - Core logging hooks
- `src/Service/LoggingConfigService.php` - Configuration service
- `src/Form/LoggingConfigForm.php` - Admin interface
- `src/Commands/LoggingCommands.php` - Drush commands
- `config/install/thetruthperspective.logging.yml` - Default config
- `newsmotivationmetrics.routing.yml` - Admin route
- `newsmotivationmetrics.links.menu.yml` - Admin menu

## Future Enhancements

- **Log Rotation**: Automatic cleanup of old logs
- **Alert Integration**: Email/Slack notifications for errors
- **Performance Metrics**: Track logging overhead
- **Visual Dashboard**: Charts showing log trends
