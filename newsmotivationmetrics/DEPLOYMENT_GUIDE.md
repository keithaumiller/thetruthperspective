# News Motivation Metrics - Block Configuration Deployment Guide

## Overview
The News Motivation Metrics module creates dashboard blocks that are configured to display in the **hero region** and be visible **only on the front page** (`<front>`). This guide ensures these configurations are preserved during deployments.

## Block Configuration Protection

### Automatic Installation
When the module is installed, the `newsmotivationmetrics.install` file automatically:
- Creates all dashboard blocks in the hero region
- Sets visibility to front page only (`<front>`)
- Applies proper ordering/weights
- Logs all actions for debugging

### Blocks Created
1. **metrics_header** (weight: -10)
2. **content_analysis_overview** (weight: -9)
3. **temporal_processing_analytics** (weight: -8)
4. **sentiment_distribution_analysis** (weight: -7)
5. **taxonomy_timeline_chart** (weight: -6)
6. **entity_recognition_metrics** (weight: -5)
7. **recent_activity_metrics** (weight: -4)
8. **analysis_quality_metrics** (weight: -3)
9. **about_truth_perspective_analytics** (weight: -2)

## Deployment Protection Strategies

### Method 1: Configuration Export/Import (Recommended)
```bash
# Before deployment - export current configuration
drush config:export

# Commit block configuration files
git add config/sync/block.block.*.yml
git commit -m "Export block configurations for deployment protection"

# After deployment - import configuration
drush config:import
```

### Method 2: Manual Restoration Commands
If blocks get lost or misconfigured during deployment:

```bash
# Restore all blocks to default configuration
drush php:eval "newsmotivationmetrics_restore_block_configuration();"

# Check current block status
drush php:eval "newsmotivationmetrics_check_block_status();"

# Run update function to restore blocks
drush updatedb
```

### Method 3: Module Reinstall (Last Resort)
```bash
# This will remove and recreate all blocks
drush pm:uninstall newsmotivationmetrics
drush pm:install newsmotivationmetrics
```

## Verification Commands

### Check Block Status
```bash
# Detailed status report
drush php:eval "newsmotivationmetrics_check_block_status();"

# Quick check via Drush
drush block:list --region=hero

# Check front page visibility
drush config:get block.block.olivero_metrics_header visibility
```

### Test Front Page Display
```bash
# Clear caches
drush cache:rebuild

# Check front page
curl -s https://thetruthperspective.org/ | grep -i "metrics\|analysis\|content"
```

## Troubleshooting

### Blocks Not Appearing
1. **Check if blocks exist:**
   ```bash
   drush php:eval "newsmotivationmetrics_check_block_status();"
   ```

2. **Verify region placement:**
   ```bash
   drush block:list --region=hero
   ```

3. **Check page visibility:**
   ```bash
   drush config:get block.block.olivero_metrics_header visibility.request_path
   ```

4. **Restore if needed:**
   ```bash
   drush php:eval "newsmotivationmetrics_restore_block_configuration();"
   ```

### Wrong Theme Configuration
If blocks are created for wrong theme:
```bash
# Check current default theme
drush config:get system.theme default

# Recreate blocks for correct theme
drush php:eval "newsmotivationmetrics_restore_block_configuration();"
```

### Blocks in Wrong Region
```bash
# Move blocks to hero region
drush php:eval "
\$theme = \Drupal::config('system.theme')->get('default');
\$block_ids = ['metrics_header', 'content_analysis_overview', 'temporal_processing_analytics', 'sentiment_distribution_analysis', 'taxonomy_timeline_chart', 'entity_recognition_metrics', 'recent_activity_metrics', 'analysis_quality_metrics', 'about_truth_perspective_analytics'];
foreach (\$block_ids as \$block_id) {
  \$block = \Drupal\block\Entity\Block::load(\$theme . '_' . \$block_id);
  if (\$block) {
    \$block->setRegion('hero');
    \$block->save();
  }
}
"
```

## Configuration Files Location
- Install hooks: `newsmotivationmetrics/newsmotivationmetrics.install`
- Helper functions: `newsmotivationmetrics/newsmotivationmetrics.module`
- Configuration docs: `newsmotivationmetrics/config/README.md`

## Production Deployment Checklist
- [ ] Export configuration before deployment
- [ ] Commit block configuration files
- [ ] Deploy code changes
- [ ] Import configuration after deployment
- [ ] Verify blocks are in hero region
- [ ] Confirm front page only visibility
- [ ] Test dashboard functionality
- [ ] Clear all caches

## Notes
- Block machine names follow pattern: `{theme}_{block_id}`
- All blocks are set to front page only by default
- Blocks maintain proper ordering through weight system
- Configuration is logged for debugging purposes
- Module provides helper functions for easy restoration
