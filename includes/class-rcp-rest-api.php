<?php
/**
 * REST API Class
 *
 * @package RSSContentPlanner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RCP_REST_API class for REST API endpoints
 */
class RCP_REST_API {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        $namespace = 'rcp/v1';
        
        // Feeds endpoints
        register_rest_route($namespace, '/feeds', [
            'methods' => 'GET',
            'callback' => [$this, 'get_feeds'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        register_rest_route($namespace, '/feeds', [
            'methods' => 'POST',
            'callback' => [$this, 'create_feed'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        // Webhooks endpoints
        register_rest_route($namespace, '/webhooks', [
            'methods' => 'GET',
            'callback' => [$this, 'get_webhooks'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        register_rest_route($namespace, '/webhooks', [
            'methods' => 'POST',
            'callback' => [$this, 'create_webhook'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
    }
    
    /**
     * Check permissions
     */
    public function check_permissions() {
        return current_user_can('rcp_manage_feeds');
    }
    
    /**
     * Get feeds endpoint
     */
    public function get_feeds($request) {
        $feed_manager = new RCP_Feed_Manager();
        $feeds = $feed_manager->get_feeds();
        return rest_ensure_response($feeds);
    }
    
    /**
     * Create feed endpoint
     */
    public function create_feed($request) {
        $feed_manager = new RCP_Feed_Manager();
        $result = $feed_manager->add_feed($request->get_json_params());
        
        if (is_wp_error($result)) {
            return new WP_Error('creation_failed', $result->get_error_message(), ['status' => 400]);
        }
        
        return rest_ensure_response(['id' => $result]);
    }
    
    /**
     * Get webhooks endpoint
     */
    public function get_webhooks($request) {
        $webhook_manager = new RCP_Webhook_Manager();
        $webhooks = $webhook_manager->get_webhooks();
        return rest_ensure_response($webhooks);
    }
    
    /**
     * Create webhook endpoint
     */
    public function create_webhook($request) {
        $webhook_manager = new RCP_Webhook_Manager();
        $params = $request->get_json_params();
        
        $result = $webhook_manager->create_webhook(
            $params['name'],
            $params['webhook_url'],
            $params['workflow_data'] ?? []
        );
        
        if (is_wp_error($result)) {
            return new WP_Error('creation_failed', $result->get_error_message(), ['status' => 400]);
        }
        
        return rest_ensure_response($result);
    }
}
