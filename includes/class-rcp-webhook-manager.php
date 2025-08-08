<?php
/**
 * Webhook Manager Class for n8n Integration
 *
 * @package RSSContentPlanner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RCP_Webhook_Manager class for managing n8n webhooks
 */
class RCP_Webhook_Manager {
    
    /**
     * Database instance
     */
    private $db;
    
    /**
     * Constructor
     */
    public function __construct($database = null) {
        $this->db = $database ?: new RCP_Database();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('rest_api_init', [$this, 'register_webhook_endpoints']);
        add_action('wp_ajax_rcp_test_webhook', [$this, 'test_webhook']);
        add_action('wp_ajax_rcp_create_webhook', [$this, 'create_webhook']);
    }
    
    /**
     * Register REST API endpoints for webhook callbacks
     */
    public function register_webhook_endpoints() {
        register_rest_route('rcp/v1', '/webhook/callback', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook_callback'],
            'permission_callback' => [$this, 'verify_webhook_auth'],
        ]);
        
        register_rest_route('rcp/v1', '/webhook/test', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_test_webhook'],
            'permission_callback' => [$this, 'verify_webhook_auth'],
        ]);
    }
    
    /**
     * Create a new webhook
     */
    public function create_webhook($name, $webhook_url, $workflow_data = []) {
        global $wpdb;
        
        $webhooks_table = $this->db->get_table_name('webhooks');
        
        // Generate secure auth token
        $auth_token = wp_generate_password(32, false);
        
        $webhook_data = [
            'name' => sanitize_text_field($name),
            'webhook_url' => esc_url_raw($webhook_url),
            'auth_token' => $auth_token,
            'workflow_name' => sanitize_text_field($workflow_data['name'] ?? ''),
            'workflow_description' => sanitize_textarea_field($workflow_data['description'] ?? ''),
            'n8n_workflow_json' => json_encode($workflow_data),
            'processing_type' => sanitize_text_field($workflow_data['processing_type'] ?? 'content_rewrite'),
            'active' => 1,
        ];
        
        $result = $wpdb->insert($webhooks_table, $webhook_data);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create webhook');
        }
        
        $webhook_id = $wpdb->insert_id;
        
        // Log the creation
        $this->db->log('info', 'webhook', "Webhook created: {$name}", ['webhook_id' => $webhook_id]);
        
        return [
            'webhook_id' => $webhook_id,
            'auth_token' => $auth_token,
            'callback_url' => rest_url('rcp/v1/webhook/callback'),
        ];
    }
    
    /**
     * Send content to n8n webhook
     */
    public function send_to_webhook($webhook_id, $content_data) {
        global $wpdb;
        
        $webhooks_table = $this->db->get_table_name('webhooks');
        $executions_table = $this->db->get_table_name('executions');
        
        // Get webhook details
        $webhook = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $webhooks_table WHERE id = %d AND active = 1",
            $webhook_id
        ));
        
        if (!$webhook) {
            return new WP_Error('webhook_not_found', 'Webhook not found or inactive');
        }
        
        // Generate execution ID
        $execution_id = uniqid('rcp_', true);
        
        // Prepare payload
        $payload = [
            'execution_id' => $execution_id,
            'webhook_id' => $webhook_id,
            'callback_url' => rest_url('rcp/v1/webhook/callback'),
            'auth_token' => $webhook->auth_token,
            'content' => $content_data['content'] ?? '',
            'title' => $content_data['title'] ?? '',
            'source_url' => $content_data['source_url'] ?? '',
            'metadata' => $content_data['metadata'] ?? [],
            'processing_type' => $webhook->processing_type,
            'timestamp' => current_time('mysql'),
        ];
        
        // Create execution record
        $execution_data = [
            'item_id' => $content_data['item_id'] ?? 0,
            'webhook_id' => $webhook_id,
            'execution_id' => $execution_id,
            'status' => 'pending',
            'request_payload' => json_encode($payload),
        ];
        
        $wpdb->insert($executions_table, $execution_data);
        $execution_record_id = $wpdb->insert_id;
        
        // Send to n8n
        $response = wp_remote_post($webhook->webhook_url, [
            'timeout' => $webhook->timeout_seconds ?: 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Auth-Token' => $webhook->auth_token,
                'User-Agent' => 'RSS-Content-Planner/' . RCP_PLUGIN_VERSION,
            ],
            'body' => json_encode($payload),
        ]);
        
        // Update execution record
        if (is_wp_error($response)) {
            $wpdb->update(
                $executions_table,
                [
                    'status' => 'error',
                    'error_message' => $response->get_error_message(),
                    'completed_at' => current_time('mysql'),
                ],
                ['id' => $execution_record_id]
            );
            
            // Update webhook error count
            $wpdb->query($wpdb->prepare(
                "UPDATE $webhooks_table SET error_count = error_count + 1 WHERE id = %d",
                $webhook_id
            ));
            
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            $wpdb->update(
                $executions_table,
                [
                    'status' => 'running',
                    'response_payload' => $response_body,
                ],
                ['id' => $execution_record_id]
            );
            
            // Update webhook success count and last used
            $wpdb->query($wpdb->prepare(
                "UPDATE $webhooks_table SET success_count = success_count + 1, last_used = %s WHERE id = %d",
                current_time('mysql'),
                $webhook_id
            ));
            
            return [
                'success' => true,
                'execution_id' => $execution_id,
                'response' => json_decode($response_body, true),
            ];
        } else {
            $wpdb->update(
                $executions_table,
                [
                    'status' => 'error',
                    'error_message' => "HTTP {$response_code}: {$response_body}",
                    'completed_at' => current_time('mysql'),
                ],
                ['id' => $execution_record_id]
            );
            
            return new WP_Error('webhook_error', "Webhook returned HTTP {$response_code}");
        }
    }
    
    /**
     * Handle webhook callback from n8n
     */
    public function handle_webhook_callback($request) {
        $params = $request->get_json_params();
        
        if (!$params || !isset($params['execution_id'])) {
            return new WP_Error('invalid_payload', 'Invalid callback payload', ['status' => 400]);
        }
        
        global $wpdb;
        $executions_table = $this->db->get_table_name('executions');
        
        // Find execution record
        $execution = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $executions_table WHERE execution_id = %s",
            $params['execution_id']
        ));
        
        if (!$execution) {
            return new WP_Error('execution_not_found', 'Execution not found', ['status' => 404]);
        }
        
        // Update execution record
        $update_data = [
            'status' => $params['status'] ?? 'success',
            'response_payload' => json_encode($params),
            'completed_at' => current_time('mysql'),
        ];
        
        if (isset($params['error'])) {
            $update_data['error_message'] = sanitize_text_field($params['error']);
            $update_data['status'] = 'error';
        }
        
        if (isset($params['processing_time_ms'])) {
            $update_data['processing_time_ms'] = intval($params['processing_time_ms']);
        }
        
        $wpdb->update($executions_table, $update_data, ['id' => $execution->id]);
        
        // Process the returned content
        if ($update_data['status'] === 'success' && isset($params['processed_content'])) {
            $this->process_returned_content($execution, $params['processed_content']);
        }
        
        // Log the callback
        $this->db->log('info', 'webhook_callback', 'Webhook callback received', [
            'execution_id' => $params['execution_id'],
            'status' => $update_data['status'],
        ]);
        
        return rest_ensure_response(['success' => true]);
    }
    
    /**
     * Process content returned from n8n
     */
    private function process_returned_content($execution, $processed_content) {
        // Get the original RSS item
        $rss_item = get_post($execution->item_id);
        if (!$rss_item) {
            return;
        }
        
        // Create or update the WordPress post
        $post_data = [
            'post_title' => sanitize_text_field($processed_content['title'] ?? $rss_item->post_title),
            'post_content' => wp_kses_post($processed_content['content'] ?? $rss_item->post_content),
            'post_excerpt' => sanitize_textarea_field($processed_content['excerpt'] ?? ''),
            'post_status' => 'draft', // Always create as draft for review
            'post_type' => 'post',
            'meta_input' => [
                '_rss_source_url' => get_post_meta($rss_item->ID, '_rss_source_url', true),
                '_rss_source_guid' => get_post_meta($rss_item->ID, '_rss_source_guid', true),
                '_rss_canonical' => get_post_meta($rss_item->ID, '_rss_canonical', true),
                '_rss_source_license' => get_post_meta($rss_item->ID, '_rss_source_license', true),
                '_ai_provenance' => json_encode([
                    'processor' => 'n8n_webhook',
                    'execution_id' => $execution->execution_id,
                    'processed_at' => current_time('mysql'),
                    'webhook_id' => $execution->webhook_id,
                ]),
            ],
        ];
        
        // Add SEO metadata if provided
        if (isset($processed_content['seo'])) {
            $seo = $processed_content['seo'];
            if (isset($seo['meta_description'])) {
                $post_data['meta_input']['_yoast_wpseo_metadesc'] = sanitize_text_field($seo['meta_description']);
            }
            if (isset($seo['focus_keywords'])) {
                $post_data['meta_input']['_yoast_wpseo_focuskw'] = sanitize_text_field($seo['focus_keywords']);
            }
        }
        
        // Create the post
        $post_id = wp_insert_post($post_data);
        
        if (!is_wp_error($post_id)) {
            // Update RSS item with reference to created post
            update_post_meta($rss_item->ID, '_processed_post_id', $post_id);
            update_post_meta($rss_item->ID, '_processing_status', 'completed');
            
            // Assign categories and tags if provided
            if (isset($processed_content['categories'])) {
                wp_set_post_categories($post_id, $processed_content['categories']);
            }
            if (isset($processed_content['tags'])) {
                wp_set_post_tags($post_id, $processed_content['tags']);
            }
            
            do_action('rcp_content_processed', $post_id, $rss_item->ID, $execution);
        }
    }
    
    /**
     * Verify webhook authentication
     */
    public function verify_webhook_auth($request) {
        $auth_token = $request->get_header('X-Auth-Token');
        
        if (!$auth_token) {
            return false;
        }
        
        global $wpdb;
        $webhooks_table = $this->db->get_table_name('webhooks');
        
        $webhook = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $webhooks_table WHERE auth_token = %s AND active = 1",
            $auth_token
        ));
        
        return $webhook !== null;
    }
    
    /**
     * Test webhook connectivity
     */
    public function test_webhook() {
        check_ajax_referer('rcp_test_webhook', 'nonce');
        
        if (!current_user_can('rcp_manage_workflows')) {
            wp_die('Insufficient permissions');
        }
        
        $webhook_id = intval($_POST['webhook_id']);
        
        $test_data = [
            'item_id' => 0,
            'content' => 'This is a test message from RSS Content Planner.',
            'title' => 'Test Content',
            'source_url' => 'https://example.com/test',
            'metadata' => ['test' => true],
        ];
        
        $result = $this->send_to_webhook($webhook_id, $test_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    /**
     * Get webhook statistics
     */
    public function get_webhook_stats($webhook_id = null) {
        global $wpdb;
        
        $executions_table = $this->db->get_table_name('executions');
        
        $where = $webhook_id ? $wpdb->prepare('WHERE webhook_id = %d', $webhook_id) : '';
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_executions,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as failed,
                AVG(processing_time_ms) as avg_processing_time,
                MAX(started_at) as last_execution
            FROM $executions_table 
            $where
        ");
        
        return $stats;
    }
    
    /**
     * Get all webhooks
     */
    public function get_webhooks($active_only = true) {
        global $wpdb;
        
        $webhooks_table = $this->db->get_table_name('webhooks');
        
        $where = $active_only ? 'WHERE active = 1' : '';
        
        return $wpdb->get_results("SELECT * FROM $webhooks_table $where ORDER BY created_at DESC");
    }
    
    /**
     * Delete webhook
     */
    public function delete_webhook($webhook_id) {
        global $wpdb;
        
        $webhooks_table = $this->db->get_table_name('webhooks');
        
        $result = $wpdb->delete($webhooks_table, ['id' => $webhook_id]);
        
        if ($result !== false) {
            $this->db->log('info', 'webhook', "Webhook deleted", ['webhook_id' => $webhook_id]);
        }
        
        return $result;
    }
}
