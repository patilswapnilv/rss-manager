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
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_feeds'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_feed'],
                'permission_callback' => [$this, 'check_permissions'],
                'args' => $this->get_feed_schema(),
            ]
        ]);
        
        register_rest_route($namespace, '/feeds/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_feed'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_feed'],
                'permission_callback' => [$this, 'check_permissions'],
                'args' => $this->get_feed_schema(),
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_feed'],
                'permission_callback' => [$this, 'check_permissions'],
            ]
        ]);
        
        register_rest_route($namespace, '/feeds/(?P<id>\d+)/fetch', [
            'methods' => 'POST',
            'callback' => [$this, 'fetch_feed'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        // Webhooks endpoints
        register_rest_route($namespace, '/webhooks', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_webhooks'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_webhook'],
                'permission_callback' => [$this, 'check_permissions'],
                'args' => $this->get_webhook_schema(),
            ]
        ]);
        
        register_rest_route($namespace, '/webhooks/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_webhook'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_webhook'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_webhook'],
                'permission_callback' => [$this, 'check_permissions'],
            ]
        ]);
        
        register_rest_route($namespace, '/webhooks/(?P<id>\d+)/test', [
            'methods' => 'POST',
            'callback' => [$this, 'test_webhook'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        // Rules endpoints
        register_rest_route($namespace, '/rules', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_rules'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_rule'],
                'permission_callback' => [$this, 'check_permissions'],
            ]
        ]);
        
        register_rest_route($namespace, '/rules/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_rule'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_rule'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_rule'],
                'permission_callback' => [$this, 'check_permissions'],
            ]
        ]);
        
        // Items endpoints
        register_rest_route($namespace, '/items', [
            'methods' => 'GET',
            'callback' => [$this, 'get_items'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        register_rest_route($namespace, '/items/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_item'],
                'permission_callback' => [$this, 'check_permissions'],
            ]
        ]);
        
        register_rest_route($namespace, '/items/(?P<id>\d+)/process', [
            'methods' => 'POST',
            'callback' => [$this, 'process_item'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        // Templates endpoints
        register_rest_route($namespace, '/templates', [
            'methods' => 'GET',
            'callback' => [$this, 'get_templates'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        register_rest_route($namespace, '/templates/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_template'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        // Stats endpoints
        register_rest_route($namespace, '/stats/dashboard', [
            'methods' => 'GET',
            'callback' => [$this, 'get_dashboard_stats'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        register_rest_route($namespace, '/stats/feeds', [
            'methods' => 'GET',
            'callback' => [$this, 'get_feeds_stats'],
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
