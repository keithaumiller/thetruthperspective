# This directory contains block configuration files that define the default
# placement and settings for News Motivation Metrics dashboard blocks.
#
# These configurations ensure that:
# 1. Blocks are placed in the hero region
# 2. Blocks are only visible on the front page (<front>)
# 3. Blocks maintain proper ordering/weight
# 4. Settings are preserved during deployments
#
# When the module is installed, the newsmotivationmetrics.install file
# will automatically create these block placements.
#
# To preserve existing block configurations during deployment:
# 1. Export current configuration: drush config:export
# 2. Commit block configuration files to repository
# 3. Import after deployment: drush config:import
#
# Manual restoration command if needed:
# drush php:eval "_newsmotivationmetrics_configure_default_blocks();"
