<?php
/**
 * Simple cache clearing script for Drupal
 * This can be called via web request to clear cache after module updates
 */

// Simple security check - remove this file after use
if (!isset($_GET['clear_cache']) || $_GET['clear_cache'] !== 'confirm') {
    die('Access denied. Use ?clear_cache=confirm');
}

// Bootstrap Drupal
$drupal_root = '/var/www/html/drupal';
if (file_exists($drupal_root . '/vendor/autoload.php')) {
    require_once $drupal_root . '/vendor/autoload.php';
    require_once $drupal_root . '/core/includes/bootstrap.inc';
    
    // Bootstrap Drupal
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $kernel = \Drupal\Core\DrupalKernel::createFromRequest($request, $autoloader);
    $kernel->boot();
    $kernel->prepareLegacyRequest($request);
    
    // Clear all caches
    drupal_flush_all_caches();
    
    echo "Cache cleared successfully!";
} else {
    echo "Could not find Drupal installation.";
}
?>
