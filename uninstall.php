<?php
/**
 * Uninstall script for RSS Content Planner
 *
 * @package RSSContentPlanner
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Include the database class
require_once plugin_dir_path(__FILE__) . 'includes/class-rcp-database.php';

// Remove database tables and options
$database = new RCP_Database();
$database->drop_tables();

// Remove all plugin options
delete_option('rcp_version');
delete_option('rcp_db_version');
delete_option('rcp_processing_mode');
delete_option('rcp_settings');

// Remove capabilities from all roles
$capabilities = [
    'rcp_manage_feeds',
    'rcp_edit_items',
    'rcp_manage_workflows',
    'rcp_view_analytics',
    'rcp_manage_settings',
];

$roles = ['administrator', 'editor'];
foreach ($roles as $role_name) {
    $role = get_role($role_name);
    if ($role) {
        foreach ($capabilities as $cap) {
            $role->remove_cap($cap);
        }
    }
}

// Remove all RSS items
$rss_items = get_posts([
    'post_type' => 'rss_item',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

foreach ($rss_items as $item_id) {
    wp_delete_post($item_id, true);
}

// Clear scheduled cron jobs
wp_clear_scheduled_hook('rcp_fetch_feeds');
