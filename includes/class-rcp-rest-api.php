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
        
        // n8n Integration endpoints
        register_rest_route($namespace, '/trigger-processing', [
            'methods' => 'POST',
            'callback' => [$this, 'trigger_content_processing'],
            'permission_callback' => [$this, 'check_api_key'],
            'args' => [
                'post_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'Specific post ID to process'
                ],
                'feed_id' => [
                    'required' => false,
                    'type' => 'integer', 
                    'description' => 'Process all pending items from specific feed'
                ],
                'status' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => 'pending',
                    'description' => 'Process items with specific status'
                ]
            ]
        ]);
        
        register_rest_route($namespace, '/content/(?P<id>\d+)/processed', [
            'methods' => 'POST',
            'callback' => [$this, 'update_processed_content'],
            'permission_callback' => [$this, 'check_api_key'],
            'args' => [
                'title' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Updated title'
                ],
                'content' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Processed content'
                ],
                'excerpt' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Content excerpt'
                ],
                'status' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => 'draft',
                    'description' => 'Post status after processing'
                ],
                'meta' => [
                    'required' => false,
                    'type' => 'object',
                    'description' => 'Additional meta fields'
                ]
            ]
        ]);
        
        register_rest_route($namespace, '/n8n-webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'n8n_webhook_handler'],
            'permission_callback' => '__return_true', // Will validate via API key in handler
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
    
    /**
     * Check API key for n8n integration
     */
    public function check_api_key($request) {
        $api_key = $request->get_header('X-RCP-API-Key') ?: $request->get_param('api_key');
        $stored_key = get_option('rcp_api_key');
        
        if (empty($stored_key)) {
            // Generate API key if it doesn't exist
            $stored_key = wp_generate_password(32, false);
            update_option('rcp_api_key', $stored_key);
        }
        
        return hash_equals($stored_key, $api_key ?: '');
    }
    
    /**
     * Trigger content processing for n8n
     */
    public function trigger_content_processing($request) {
        $post_id = $request->get_param('post_id');
        $feed_id = $request->get_param('feed_id');
        $status = $request->get_param('status') ?: 'pending';
        
        $args = [
            'post_type' => 'rss_item',
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => '_rcp_processing_status',
                    'value' => $status,
                    'compare' => '='
                ]
            ]
        ];
        
        if ($post_id) {
            $args['p'] = $post_id;
        }
        
        if ($feed_id) {
            $args['meta_query'][] = [
                'key' => '_rss_feed_id',
                'value' => $feed_id,
                'compare' => '='
            ];
        }
        
        $posts = get_posts($args);
        $processed_items = [];
        
        foreach ($posts as $post) {
            // Update status to processing
            update_post_meta($post->ID, '_rcp_processing_status', 'processing');
            update_post_meta($post->ID, '_rcp_processing_started', current_time('mysql'));
            
            $processed_items[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'meta' => [
                    'source_url' => get_post_meta($post->ID, '_rss_item_url', true),
                    'source_name' => get_post_meta($post->ID, '_rss_source_name', true),
                    'published_date' => get_post_meta($post->ID, '_rss_item_date', true),
                    'categories' => wp_get_post_terms($post->ID, 'rcp_source_site', ['fields' => 'names']),
                ]
            ];
        }
        
        return rest_ensure_response([
            'success' => true,
            'count' => count($processed_items),
            'items' => $processed_items
        ]);
    }
    
    /**
     * Update processed content from n8n
     */
    public function update_processed_content($request) {
        $post_id = $request->get_param('id');
        $title = $request->get_param('title');
        $content = $request->get_param('content');
        $excerpt = $request->get_param('excerpt');
        $status = $request->get_param('status') ?: 'draft';
        $meta = $request->get_param('meta') ?: [];
        
        $post_data = ['ID' => $post_id];
        
        if ($title) {
            $post_data['post_title'] = sanitize_text_field($title);
        }
        
        if ($content) {
            $post_data['post_content'] = wp_kses_post($content);
        }
        
        if ($excerpt) {
            $post_data['post_excerpt'] = sanitize_text_field($excerpt);
        }
        
        if ($status) {
            $post_data['post_status'] = sanitize_text_field($status);
        }
        
        $result = wp_update_post($post_data);
        
        if (is_wp_error($result)) {
            return new WP_Error('update_failed', 'Failed to update post', ['status' => 400]);
        }
        
        // Update processing status
        update_post_meta($post_id, '_rcp_processing_status', 'completed');
        update_post_meta($post_id, '_rcp_processing_completed', current_time('mysql'));
        
        // Update additional meta fields
        foreach ($meta as $key => $value) {
            update_post_meta($post_id, '_rcp_' . sanitize_key($key), sanitize_text_field($value));
        }
        
        return rest_ensure_response([
            'success' => true,
            'post_id' => $post_id,
            'message' => 'Content updated successfully'
        ]);
    }
    
    /**
     * Generic n8n webhook handler
     */
    public function n8n_webhook_handler($request) {
        // Validate API key from header or body
        $api_key = $request->get_header('X-RCP-API-Key') ?: $request->get_param('api_key');
        if (!$this->check_api_key($request)) {
            return new WP_Error('invalid_key', 'Invalid API key', ['status' => 401]);
        }
        
        $action = $request->get_param('action');
        $data = $request->get_json_params();
        
        switch ($action) {
            case 'get_pending_content':
                return $this->trigger_content_processing($request);
                
            case 'update_content':
                return $this->update_processed_content($request);
                
            case 'get_feeds':
                return $this->get_feeds($request);
                
            default:
                return new WP_Error('invalid_action', 'Invalid action specified', ['status' => 400]);
        }
    }
}
