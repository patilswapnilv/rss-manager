<?php
/**
 * Rules Engine Class
 *
 * @package RSSContentPlanner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RCP_Rules_Engine class for managing content processing rules
 */
class RCP_Rules_Engine {
    
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
        add_action('wp_ajax_rcp_save_rule', [$this, 'ajax_save_rule']);
        add_action('wp_ajax_rcp_test_rule', [$this, 'ajax_test_rule']);
    }
    
    /**
     * Create or update a rule
     */
    public function save_rule($rule_data) {
        global $wpdb;
        
        $rules_table = $this->db->get_table_name('rules');
        
        $data = [
            'name' => sanitize_text_field($rule_data['name']),
            'feed_id' => isset($rule_data['feed_id']) ? intval($rule_data['feed_id']) : null,
            'priority' => intval($rule_data['priority'] ?? 10),
            'conditions' => json_encode($rule_data['conditions']),
            'actions' => json_encode($rule_data['actions']),
            'webhook_id' => isset($rule_data['webhook_id']) ? intval($rule_data['webhook_id']) : null,
            'active' => intval($rule_data['active'] ?? 1),
        ];
        
        if (isset($rule_data['id']) && $rule_data['id']) {
            // Update existing rule
            $result = $wpdb->update($rules_table, $data, ['id' => intval($rule_data['id'])]);
            $rule_id = intval($rule_data['id']);
        } else {
            // Create new rule
            $result = $wpdb->insert($rules_table, $data);
            $rule_id = $wpdb->insert_id;
        }
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to save rule');
        }
        
        return $rule_id;
    }
    
    /**
     * AJAX: Save rule
     */
    public function ajax_save_rule() {
        check_ajax_referer('rcp_save_rule', 'nonce');
        
        if (!current_user_can('rcp_manage_workflows')) {
            wp_die('Insufficient permissions');
        }
        
        $rule_data = $_POST['rule_data'];
        $result = $this->save_rule($rule_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(['rule_id' => $result]);
        }
    }
    
    /**
     * AJAX: Test rule
     */
    public function ajax_test_rule() {
        check_ajax_referer('rcp_test_rule', 'nonce');
        
        // Test rule logic implementation
        wp_send_json_success(['message' => 'Rule test completed']);
    }
}
