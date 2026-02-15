<?php
/**
 * MU loader for WP-CLI Login Command Server
 * - Loads the plugin from wp-content/plugins/...
 * - Hides it from the normal Plugins screen
 */

add_filter('all_plugins', function ($plugins) {
    // Change this to match your plugin folder/file
    $key = 'wp-cli-login-server/wp-cli-login-server.php';
    if (isset($plugins[$key])) {
        unset($plugins[$key]);
    }
    return $plugins;
});

// Load the actual plugin file
$plugin = WP_CONTENT_DIR . '/plugins/wp-cli-login-server/wp-cli-login-server.php';
if (file_exists($plugin)) {
    require_once $plugin;
}

