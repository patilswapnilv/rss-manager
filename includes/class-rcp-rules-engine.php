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
        add_action('wp_ajax_rcp_delete_rule', [$this, 'ajax_delete_rule']);
        add_action('wp_ajax_rcp_toggle_rule', [$this, 'ajax_toggle_rule']);
        add_action('wp_ajax_rcp_duplicate_rule', [$this, 'ajax_duplicate_rule']);
        add_action('wp_ajax_rcp_get_rule_templates', [$this, 'ajax_get_rule_templates']);
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
        check_ajax_referer('rcp_admin', 'nonce');
        
        if (!current_user_can('rcp_manage_workflows')) {
            wp_die('Insufficient permissions');
        }
        
        $rule_data = $_POST['rule_data'] ?? [];
        $test_content = $_POST['test_content'] ?? [];
        
        if (empty($rule_data) || empty($test_content)) {
            wp_send_json_error('Rule data and test content are required');
        }
        
        $result = $this->test_rule_against_content($rule_data, $test_content);
        wp_send_json_success($result);
    }
    
    /**
     * Test rule against sample content
     */
    public function test_rule_against_content($rule_data, $test_content) {
        $conditions = $rule_data['conditions'] ?? [];
        $actions = $rule_data['actions'] ?? [];
        
        // Evaluate conditions
        $condition_results = [];
        $overall_match = true;
        
        foreach ($conditions as $condition) {
            $result = $this->evaluate_condition($condition, $test_content);
            $condition_results[] = [
                'condition' => $condition,
                'result' => $result,
                'explanation' => $this->get_condition_explanation($condition, $test_content, $result)
            ];
            
            if (!$result) {
                $overall_match = false;
            }
        }
        
        // Simulate actions if conditions match
        $action_results = [];
        if ($overall_match) {
            foreach ($actions as $action) {
                $action_results[] = [
                    'action' => $action,
                    'result' => $this->simulate_action($action, $test_content)
                ];
            }
        }
        
        return [
            'overall_match' => $overall_match,
            'conditions' => $condition_results,
            'actions' => $action_results,
            'test_content' => $test_content
        ];
    }
    
    /**
     * Evaluate a single condition
     */
    private function evaluate_condition($condition, $content) {
        $type = $condition['type'] ?? '';
        $operator = $condition['operator'] ?? 'contains';
        $value = $condition['value'] ?? '';
        
        switch ($type) {
            case 'title_condition':
                return $this->evaluate_text_condition($content['title'] ?? '', $operator, $value);
                
            case 'content_condition':
                return $this->evaluate_text_condition($content['content'] ?? '', $operator, $value);
                
            case 'author_condition':
                return $this->evaluate_text_condition($content['author'] ?? '', $operator, $value);
                
            case 'category_condition':
                $categories = $content['categories'] ?? [];
                return $this->evaluate_array_condition($categories, $operator, $value);
                
            case 'source_condition':
                return $this->evaluate_text_condition($content['source_site'] ?? '', $operator, $value);
                
            case 'length_condition':
                $content_length = strlen(strip_tags($content['content'] ?? ''));
                return $this->evaluate_numeric_condition($content_length, $operator, intval($value));
                
            case 'has_media':
                $enclosures = $content['enclosures'] ?? [];
                return !empty($enclosures);
                
            default:
                return false;
        }
    }
    
    /**
     * Evaluate text-based conditions
     */
    private function evaluate_text_condition($text, $operator, $value) {
        $text = strtolower($text);
        $value = strtolower($value);
        
        switch ($operator) {
            case 'contains':
                return strpos($text, $value) !== false;
            case 'not_contains':
                return strpos($text, $value) === false;
            case 'equals':
                return $text === $value;
            case 'not_equals':
                return $text !== $value;
            case 'starts_with':
                return strpos($text, $value) === 0;
            case 'ends_with':
                return substr($text, -strlen($value)) === $value;
            case 'regex':
                return preg_match('/' . $value . '/i', $text);
            default:
                return false;
        }
    }
    
    /**
     * Evaluate array-based conditions
     */
    private function evaluate_array_condition($array, $operator, $value) {
        $value = strtolower($value);
        $array = array_map('strtolower', $array);
        
        switch ($operator) {
            case 'contains':
                return in_array($value, $array);
            case 'not_contains':
                return !in_array($value, $array);
            default:
                return false;
        }
    }
    
    /**
     * Evaluate numeric conditions
     */
    private function evaluate_numeric_condition($number, $operator, $value) {
        switch ($operator) {
            case 'equals':
                return $number == $value;
            case 'greater_than':
                return $number > $value;
            case 'less_than':
                return $number < $value;
            default:
                return false;
        }
    }
    
    /**
     * Get explanation for condition result
     */
    private function get_condition_explanation($condition, $content, $result) {
        $type = $condition['type'] ?? '';
        $operator = $condition['operator'] ?? '';
        $value = $condition['value'] ?? '';
        
        $field_value = '';
        switch ($type) {
            case 'title_condition':
                $field_value = $content['title'] ?? '';
                break;
            case 'content_condition':
                $field_value = wp_trim_words($content['content'] ?? '', 10);
                break;
            case 'author_condition':
                $field_value = $content['author'] ?? '';
                break;
        }
        
        $status = $result ? 'MATCHED' : 'DID NOT MATCH';
        return "{$status}: '{$field_value}' {$operator} '{$value}'";
    }
    
    /**
     * Simulate action execution
     */
    private function simulate_action($action, $content) {
        $type = $action['type'] ?? '';
        $value = $action['value'] ?? '';
        
        switch ($type) {
            case 'assign_category':
                return "Would assign category: {$value}";
            case 'assign_tags':
                return "Would assign tags: {$value}";
            case 'set_status':
                return "Would set post status to: {$value}";
            case 'send_to_webhook':
                return "Would send to webhook ID: {$value}";
            default:
                return "Unknown action: {$type}";
        }
    }
}
