<?php
/**
 * Admin Class
 *
 * @package RSSContentPlanner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RCP_Admin class for handling admin functionality
 */
class RCP_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('RSS Content Planner', 'rss-content-planner'),
            __('RSS Planner', 'rss-content-planner'),
            'rcp_manage_feeds',
            'rss-content-planner',
            [$this, 'admin_page'],
            'dashicons-rss',
            30
        );
        
        add_submenu_page(
            'rss-content-planner',
            __('Feeds', 'rss-content-planner'),
            __('Feeds', 'rss-content-planner'),
            'rcp_manage_feeds',
            'rcp-feeds',
            [$this, 'feeds_page']
        );
        
        add_submenu_page(
            'rss-content-planner',
            __('Workflows', 'rss-content-planner'),
            __('Workflows', 'rss-content-planner'),
            'rcp_manage_workflows',
            'rcp-workflows',
            [$this, 'workflows_page']
        );
        
        add_submenu_page(
            'rss-content-planner',
            __('Inbox', 'rss-content-planner'),
            __('Inbox', 'rss-content-planner'),
            'rcp_edit_items',
            'rcp-inbox',
            [$this, 'inbox_page']
        );
        
        add_submenu_page(
            'rss-content-planner',
            __('Settings', 'rss-content-planner'),
            __('Settings', 'rss-content-planner'),
            'rcp_manage_settings',
            'rcp-settings',
            [$this, 'settings_page']
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'rss-content-planner') === false && strpos($hook, 'rcp-') === false) {
            return;
        }
        
        wp_enqueue_script(
            'rcp-admin',
            RCP_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            RCP_PLUGIN_VERSION,
            true
        );
        
        wp_enqueue_style(
            'rcp-admin',
            RCP_PLUGIN_URL . 'assets/css/admin.css',
            [],
            RCP_PLUGIN_VERSION
        );
        
        wp_localize_script('rcp-admin', 'rcpAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rcp_admin'),
        ]);
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        echo '<div class="wrap"><h1>' . __('RSS Content Planner Dashboard', 'rss-content-planner') . '</h1>';
        echo '<p>' . __('Welcome to RSS Content Planner. Get started by adding feeds and configuring workflows.', 'rss-content-planner') . '</p>';
        echo '</div>';
    }
    
    /**
     * Feeds page
     */
    public function feeds_page() {
        echo '<div class="wrap"><h1>' . __('RSS Feeds', 'rss-content-planner') . '</h1>';
        echo '<p>' . __('Manage your RSS/Atom feeds here.', 'rss-content-planner') . '</p>';
        echo '</div>';
    }
    
    /**
     * Workflows page
     */
    public function workflows_page() {
        echo '<div class="wrap"><h1>' . __('n8n Workflows', 'rss-content-planner') . '</h1>';
        echo '<p>' . __('Manage your n8n workflow integrations here.', 'rss-content-planner') . '</p>';
        echo '</div>';
    }
    
    /**
     * Inbox page
     */
    public function inbox_page() {
        echo '<div class="wrap"><h1>' . __('Content Inbox', 'rss-content-planner') . '</h1>';
        echo '<p>' . __('Review and manage processed content here.', 'rss-content-planner') . '</p>';
        echo '</div>';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        echo '<div class="wrap"><h1>' . __('Settings', 'rss-content-planner') . '</h1>';
        echo '<p>' . __('Configure your RSS Content Planner settings.', 'rss-content-planner') . '</p>';
        echo '</div>';
    }
}
