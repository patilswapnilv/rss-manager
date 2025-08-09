<?php
/**
 * Plugin Name: RSS Content Planner
 * Plugin URI: https://github.com/patilswapnilv/rss-content-planner
 * Description: Aggregate content from RSS/Atom feeds, auto-classify into WordPress taxonomies, and power an editorial workflow with n8n automation to plan, rewrite, and republish ethically attributed content.
 * Version: 1.0.0
 * Author: patilswapnilv
 * Author URI: https://swapnilpatil.in/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rss-content-planner
 * Domain Path: /languages
 * Requires at least: 6.5
 * Tested up to: 6.4
 * Requires PHP: 8.1
 * Network: true
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RCP_PLUGIN_VERSION', '1.0.0');
define('RCP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RCP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('RCP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main RSS Content Planner Class
 */
class RSSContentPlanner {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);
        
        add_action('plugins_loaded', [$this, 'init'], 20); // Run later to ensure all dependencies are loaded
        add_action('init', [$this, 'register_post_types_and_taxonomies'], 5); // Register post types early in init
        add_action('init', [$this, 'load_textdomain']);
        
        // Multisite support
        if (is_multisite()) {
            add_action('wpmu_new_blog', [$this, 'new_blog_activation'], 10, 6);
        }
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once RCP_PLUGIN_PATH . 'includes/class-rcp-database.php';
        require_once RCP_PLUGIN_PATH . 'includes/class-rcp-settings.php';
        require_once RCP_PLUGIN_PATH . 'includes/class-rcp-feed-manager.php';
        require_once RCP_PLUGIN_PATH . 'includes/class-rcp-webhook-manager.php';
        require_once RCP_PLUGIN_PATH . 'includes/class-rcp-rules-engine.php';
        require_once RCP_PLUGIN_PATH . 'includes/class-rcp-content-processor.php';
        require_once RCP_PLUGIN_PATH . 'includes/class-rcp-admin.php';
        require_once RCP_PLUGIN_PATH . 'includes/class-rcp-rest-api.php';
        require_once RCP_PLUGIN_PATH . 'includes/class-rcp-cron.php';
        
        // Admin UI classes (will be loaded when needed)
        // Note: Admin classes will be implemented in future versions
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check WordPress and PHP version compatibility
        if (!$this->check_compatibility()) {
            return;
        }
        
        // Initialize components with error handling
        try {
            $this->database = new RCP_Database();
            $this->settings = new RCP_Settings();
            $this->feed_manager = new RCP_Feed_Manager($this->database);
            $this->webhook_manager = new RCP_Webhook_Manager($this->database);
            $this->rules_engine = new RCP_Rules_Engine($this->database);
            $this->content_processor = new RCP_Content_Processor();
            $this->rest_api = new RCP_REST_API();
            $this->cron = new RCP_Cron();
            
            // Connect components
            $this->feed_manager->set_webhook_manager($this->webhook_manager);
            
            if (is_admin()) {
                $this->admin = new RCP_Admin();
            }
            
            do_action('rcp_init');
            
        } catch (Exception $e) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>';
                echo esc_html__('RSS Content Planner initialization error: ', 'rss-content-planner') . esc_html($e->getMessage());
                echo '</p></div>';
            });
        }
    }
    
    /**
     * Check WordPress and PHP compatibility
     */
    private function check_compatibility() {
        global $wp_version;
        
        // Check WordPress version
        if (version_compare($wp_version, '6.5', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo esc_html__('RSS Content Planner requires WordPress 6.5 or higher.', 'rss-content-planner');
                echo '</p></div>';
            });
            return false;
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.1', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo esc_html__('RSS Content Planner requires PHP 8.1 or higher.', 'rss-content-planner');
                echo '</p></div>';
            });
            return false;
        }
        
        return true;
    }
    
    /**
     * Register custom post types and taxonomies
     * Called during 'init' hook to ensure rewrite system is ready
     */
    public function register_post_types_and_taxonomies() {
        // Only register if plugin is properly initialized
        if (!$this->check_compatibility()) {
            return;
        }
        
        $this->register_post_types();
        $this->register_taxonomies();
    }
    
    /**
     * Register custom post types
     */
    private function register_post_types() {
        // RSS Item CPT for storing fetched content
        register_post_type('rss_item', [
            'labels' => [
                'name' => __('RSS Items', 'rss-content-planner'),
                'singular_name' => __('RSS Item', 'rss-content-planner'),
            ],
            'public' => false,
            'show_ui' => false,
            'supports' => ['title', 'editor', 'custom-fields'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);
    }
    
    /**
     * Register custom taxonomies
     */
    private function register_taxonomies() {
        // Source site taxonomy
        register_taxonomy('rcp_source_site', ['post', 'rss_item'], [
            'labels' => [
                'name' => __('Source Sites', 'rss-content-planner'),
                'singular_name' => __('Source Site', 'rss-content-planner'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'hierarchical' => false,
        ]);
    }
    
    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'rss-content-planner',
            false,
            dirname(RCP_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        try {
            // Check if this is a multisite activation
            if (is_multisite() && isset($_GET['activate-multi'])) {
                // For network activation, handle each site
                $this->network_activate();
            } else {
                // Single site activation
                $this->single_site_activate();
            }
            
        } catch (Exception $e) {
            // Log the error and show user-friendly message
            error_log('RSS Content Planner activation error: ' . $e->getMessage());
            wp_die(
                'Plugin activation failed: ' . esc_html($e->getMessage()) . '<br><br>' .
                '<a href="' . admin_url('plugins.php') . '">Return to Plugins</a>',
                'Plugin Activation Error',
                ['back_link' => true]
            );
        }
    }
    
    /**
     * Network activation for multisite
     */
    private function network_activate() {
        global $wpdb;
        
        // Get all blog IDs
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            $this->single_site_activate();
            restore_current_blog();
        }
    }
    
    /**
     * Single site activation
     */
    private function single_site_activate() {
        // Set basic options first
        add_option('rcp_version', RCP_PLUGIN_VERSION);
        add_option('rcp_processing_mode', 'n8n'); // Default to n8n mode
        
        // Create database tables
        require_once RCP_PLUGIN_PATH . 'includes/class-rcp-database.php';
        $database = new RCP_Database();
        $database->create_tables();
        
        // Create default capabilities
        $this->add_capabilities();
        
        // Schedule cron jobs
        if (!wp_next_scheduled('rcp_fetch_feeds')) {
            wp_schedule_event(time(), 'hourly', 'rcp_fetch_feeds');
        }
        
        // Don't flush rewrite rules during activation in multisite
        // This will be handled when post types are registered during 'init'
        if (!is_multisite()) {
            flush_rewrite_rules();
        }
    }
    
    /**
     * Handle new blog creation in multisite
     */
    public function new_blog_activation($blog_id, $user_id, $domain, $path, $site_id, $meta) {
        if (is_plugin_active_for_network(plugin_basename(__FILE__))) {
            switch_to_blog($blog_id);
            $this->single_site_activate();
            restore_current_blog();
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('rcp_fetch_feeds');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove database tables
        require_once RCP_PLUGIN_PATH . 'includes/class-rcp-database.php';
        $database = new RCP_Database();
        $database->drop_tables();
        
        // Remove options
        delete_option('rcp_version');
        delete_option('rcp_processing_mode');
        delete_option('rcp_settings');
        
        // Remove capabilities
        self::remove_capabilities();
    }
    
    /**
     * Add custom capabilities
     */
    private function add_capabilities() {
        $capabilities = [
            'rcp_manage_feeds',
            'rcp_edit_items',
            'rcp_manage_workflows',
            'rcp_view_analytics',
            'rcp_manage_settings',
        ];
        
        // Add to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }
        
        // Add to editor role (limited permissions)
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('rcp_edit_items');
            $editor_role->add_cap('rcp_view_analytics');
        }
    }
    
    /**
     * Remove custom capabilities
     */
    private static function remove_capabilities() {
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
    }
}

// Initialize the plugin
function rss_content_planner() {
    return RSSContentPlanner::get_instance();
}

// Start the plugin
rss_content_planner();
