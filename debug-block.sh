#!/bin/bash

echo "=== Taxonomy Timeline Block Debugging ==="
echo ""

echo "1. Checking if block plugin is discoverable..."
drush eval "print_r(array_keys(\Drupal::service('plugin.manager.block')->getDefinitions()));" | grep -i taxonomy || echo "❌ Block not found in plugin definitions"

echo ""
echo "2. Checking services availability..."
drush eval "
try {
  \$service = \Drupal::service('newsmotivationmetrics.chart_data_service');
  echo '✅ Chart data service available: ' . get_class(\$service) . PHP_EOL;
} catch (Exception \$e) {
  echo '❌ Chart data service error: ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "3. Checking module status..."
drush pm:list | grep newsmotivationmetrics

echo ""
echo "4. Checking for PHP errors..."
drush eval "
try {
  \$block_manager = \Drupal::service('plugin.manager.block');
  \$block_plugin = \$block_manager->createInstance('taxonomy_timeline_chart');
  echo '✅ Block plugin instantiated successfully' . PHP_EOL;
  
  \$build = \$block_plugin->build();
  echo '✅ Block build method executed successfully' . PHP_EOL;
  print_r(array_keys(\$build));
} catch (Exception \$e) {
  echo '❌ Block error: ' . \$e->getMessage() . PHP_EOL;
  echo 'Stack trace: ' . \$e->getTraceAsString() . PHP_EOL;
}
"

echo ""
echo "5. Clearing caches..."
drush cr

echo ""
echo "=== Debug Complete ==="
