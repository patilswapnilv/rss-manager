<?php
/**
 * Activation Test Script
 * Run this to test plugin activation without going through WordPress admin
 */

// Include WordPress
require_once('../../../wp-load.php');

echo '<h1>RSS Content Planner - Activation Test</h1>';

// Check if we're in multisite
if (is_multisite()) {
    echo '<p>✅ <strong>Multisite Environment Detected</strong></p>';
    echo '<p>Network: ' . get_network()->domain . get_network()->path . '</p>';
    echo '<p>Current Site: ' . get_bloginfo('name') . ' (' . get_bloginfo('url') . ')</p>';
} else {
    echo '<p>✅ <strong>Single Site Environment</strong></p>';
}

// Test 1: Check if plugin file exists and is readable
echo '<h2>1. Plugin File Check</h2>';
$plugin_file = 'rss-content-planner.php';
if (file_exists($plugin_file) && is_readable($plugin_file)) {
    echo '<p>✅ Plugin file exists and is readable</p>';
} else {
    echo '<p>❌ Plugin file missing or not readable</p>';
    exit;
}

// Test 2: Try to include plugin file
echo '<h2>2. Plugin Loading Test</h2>';
try {
    // Don't actually include it if it's already loaded
    if (!class_exists('RSSContentPlanner')) {
        include_once $plugin_file;
        echo '<p>✅ Plugin file included successfully</p>';
    } else {
        echo '<p>✅ Plugin already loaded</p>';
    }
    
    if (class_exists('RSSContentPlanner')) {
        echo '<p>✅ RSSContentPlanner class found</p>';
    } else {
        echo '<p>❌ RSSContentPlanner class not found</p>';
    }
} catch (Exception $e) {
    echo '<p>❌ Error loading plugin: ' . esc_html($e->getMessage()) . '</p>';
}

// Test 3: Check if plugin is active
echo '<h2>3. Plugin Status Check</h2>';
$plugin_path = 'rss-manager/rss-content-planner.php';

if (is_plugin_active($plugin_path)) {
    echo '<p>✅ Plugin is currently ACTIVE</p>';
} else {
    echo '<p>⚠️ Plugin is currently INACTIVE</p>';
}

if (is_multisite()) {
    if (is_plugin_active_for_network($plugin_path)) {
        echo '<p>✅ Plugin is NETWORK ACTIVE</p>';
    } else {
        echo '<p>⚠️ Plugin is not network active</p>';
    }
}

// Test 4: Check WordPress compatibility
echo '<h2>4. Compatibility Check</h2>';
global $wp_version;

echo '<p>WordPress Version: ' . $wp_version . '</p>';
echo '<p>PHP Version: ' . PHP_VERSION . '</p>';

if (version_compare($wp_version, '6.5', '>=')) {
    echo '<p>✅ WordPress version compatible</p>';
} else {
    echo '<p>❌ WordPress version too old (requires 6.5+)</p>';
}

if (version_compare(PHP_VERSION, '8.1', '>=')) {
    echo '<p>✅ PHP version compatible</p>';
} else {
    echo '<p>❌ PHP version too old (requires 8.1+)</p>';
}

// Test 5: Try manual activation simulation
echo '<h2>5. Manual Activation Test</h2>';

if (!is_plugin_active($plugin_path)) {
    echo '<p>⚠️ Plugin not active. To activate manually, go to:</p>';
    if (is_multisite()) {
        echo '<p><a href="' . network_admin_url('plugins.php') . '" target="_blank">Network Admin > Plugins</a></p>';
    } else {
        echo '<p><a href="' . admin_url('plugins.php') . '" target="_blank">Admin > Plugins</a></p>';
    }
} else {
    echo '<p>✅ Plugin is already active!</p>';
    
    // Test plugin functionality
    echo '<h3>Plugin Functionality Test</h3>';
    
    // Check if main function exists
    if (function_exists('rss_content_planner')) {
        echo '<p>✅ Main plugin function available</p>';
        
        try {
            $plugin_instance = rss_content_planner();
            echo '<p>✅ Plugin instance retrieved successfully</p>';
        } catch (Exception $e) {
            echo '<p>❌ Error getting plugin instance: ' . esc_html($e->getMessage()) . '</p>';
        }
    } else {
        echo '<p>❌ Main plugin function not available</p>';
    }
    
    // Check if custom post type is registered
    if (post_type_exists('rss_item')) {
        echo '<p>✅ Custom post type "rss_item" registered</p>';
    } else {
        echo '<p>❌ Custom post type "rss_item" not registered</p>';
    }
    
    // Check if admin menu is available
    if (is_admin()) {
        echo '<p>✅ Running in admin context</p>';
    }
}

// Test 6: Database Tables Check
echo '<h2>6. Database Tables Check</h2>';
if (class_exists('RCP_Database')) {
    global $wpdb;
    
    $tables = [
        'rcp_feeds' => 'Feed management',
        'rcp_webhooks' => 'n8n webhook integration', 
        'rcp_executions' => 'Workflow execution tracking',
        'rcp_templates' => 'Workflow templates',
        'rcp_rules' => 'Processing rules',
        'rcp_logs' => 'Activity logging'
    ];
    
    foreach ($tables as $table => $description) {
        $table_name = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        if ($exists) {
            echo "<p>✅ Table '$table_name' exists ($description)</p>";
        } else {
            echo "<p>❌ Table '$table_name' missing ($description)</p>";
        }
    }
} else {
    echo '<p>⚠️ RCP_Database class not available for table check</p>';
}

echo '<hr>';
echo '<h2>Summary</h2>';
echo '<p>If you see mostly ✅ above, the plugin should work correctly.</p>';
echo '<p>If you see ❌ errors, those need to be fixed before the plugin will work properly.</p>';

if (!is_plugin_active($plugin_path)) {
    echo '<p><strong>Next Step:</strong> Try activating the plugin through the WordPress admin interface.</p>';
} else {
    echo '<p><strong>✅ Plugin appears to be working!</strong> You can access it via:</p>';
    echo '<ul>';
    echo '<li><a href="' . admin_url('admin.php?page=rss-content-planner') . '" target="_blank">RSS Planner Dashboard</a></li>';
    echo '<li><a href="' . admin_url('admin.php?page=rcp-feeds') . '" target="_blank">Manage Feeds</a></li>';
    echo '<li><a href="' . admin_url('admin.php?page=rcp-workflows') . '" target="_blank">Manage Workflows</a></li>';
    echo '</ul>';
}
?>
