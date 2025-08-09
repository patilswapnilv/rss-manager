<?php
/**
 * Settings Manager Class
 *
 * @package RSSContentPlanner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RCP_Settings class for managing plugin settings
 */
class RCP_Settings {
    
    /**
     * Settings page slug
     */
    const SETTINGS_PAGE = 'rcp-settings';
    
    /**
     * Option name for storing settings
     */
    const OPTION_NAME = 'rcp_settings';
    
    /**
     * Default settings
     */
    private $defaults = [
        'processing_mode' => 'n8n',
        'download_media' => false,
        'media_timeout' => 30,
        'max_items_per_fetch' => 50,
        'default_post_status' => 'draft',
        'auto_attribution' => true,
        'attribution_template' => 'Originally published on {source_site} on {pub_date}.',
        'similarity_threshold' => 80,
        'enable_logging' => true,
        'log_level' => 'info',
        'cache_duration' => 3600,
        'webhook_timeout' => 30,
        'webhook_retries' => 3,
        'openai_api_key' => '',
        'openai_model' => 'gpt-4',
        'openai_temperature' => 0.7,
        'openai_max_tokens' => 2000,
        'enable_cron' => true,
        'cron_interval' => 'hourly',
        'cleanup_old_items' => false,
        'cleanup_days' => 30,
        'rate_limit_requests' => 100,
        'rate_limit_window' => 3600,
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_rcp_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_rcp_reset_settings', [$this, 'ajax_reset_settings']);
        add_action('wp_ajax_rcp_test_api_key', [$this, 'ajax_test_api_key']);
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('rcp_settings_group', self::OPTION_NAME, [
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);
    }
    
    /**
     * Get setting value
     */
    public function get($key, $default = null) {
        $settings = get_option(self::OPTION_NAME, $this->defaults);
        
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        
        return $default !== null ? $default : ($this->defaults[$key] ?? null);
    }
    
    /**
     * Set setting value
     */
    public function set($key, $value) {
        $settings = get_option(self::OPTION_NAME, $this->defaults);
        $settings[$key] = $value;
        
        return update_option(self::OPTION_NAME, $settings);
    }
    
    /**
     * Get all settings
     */
    public function get_all() {
        return wp_parse_args(get_option(self::OPTION_NAME, []), $this->defaults);
    }
    
    /**
     * Update multiple settings
     */
    public function update($settings) {
        $current = $this->get_all();
        $updated = array_merge($current, $settings);
        
        return update_option(self::OPTION_NAME, $updated);
    }
    
    /**
     * Reset to defaults
     */
    public function reset() {
        return update_option(self::OPTION_NAME, $this->defaults);
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = [];
        
        // Processing mode
        $sanitized['processing_mode'] = in_array($input['processing_mode'], ['n8n', 'direct_api']) 
            ? $input['processing_mode'] : 'n8n';
        
        // Boolean settings
        $boolean_fields = [
            'download_media', 'auto_attribution', 'enable_logging', 
            'enable_cron', 'cleanup_old_items'
        ];
        foreach ($boolean_fields as $field) {
            $sanitized[$field] = !empty($input[$field]);
        }
        
        // Integer settings
        $int_fields = [
            'media_timeout', 'max_items_per_fetch', 'similarity_threshold',
            'cache_duration', 'webhook_timeout', 'webhook_retries',
            'openai_max_tokens', 'cleanup_days', 'rate_limit_requests', 'rate_limit_window'
        ];
        foreach ($int_fields as $field) {
            $sanitized[$field] = intval($input[$field] ?? $this->defaults[$field]);
        }
        
        // Float settings
        $sanitized['openai_temperature'] = floatval($input['openai_temperature'] ?? $this->defaults['openai_temperature']);
        
        // Text settings
        $sanitized['default_post_status'] = sanitize_text_field($input['default_post_status'] ?? 'draft');
        $sanitized['attribution_template'] = sanitize_textarea_field($input['attribution_template'] ?? '');
        $sanitized['log_level'] = in_array($input['log_level'], ['debug', 'info', 'warning', 'error']) 
            ? $input['log_level'] : 'info';
        $sanitized['openai_model'] = sanitize_text_field($input['openai_model'] ?? 'gpt-4');
        $sanitized['cron_interval'] = sanitize_text_field($input['cron_interval'] ?? 'hourly');
        
        // API Key (encrypted storage)
        if (!empty($input['openai_api_key'])) {
            $sanitized['openai_api_key'] = $this->encrypt_api_key($input['openai_api_key']);
        } else {
            $sanitized['openai_api_key'] = $this->get('openai_api_key', '');
        }
        
        return $sanitized;
    }
    
    /**
     * Encrypt API key for storage
     */
    private function encrypt_api_key($key) {
        if (empty($key)) {
            return '';
        }
        
        // Simple obfuscation - in production, use proper encryption
        return base64_encode($key . '|' . wp_salt('auth'));
    }
    
    /**
     * Decrypt API key
     */
    public function decrypt_api_key($encrypted_key) {
        if (empty($encrypted_key)) {
            return '';
        }
        
        $decrypted = base64_decode($encrypted_key);
        $parts = explode('|', $decrypted);
        
        if (count($parts) === 2 && $parts[1] === wp_salt('auth')) {
            return $parts[0];
        }
        
        return '';
    }
    
    /**
     * Get OpenAI API key (decrypted)
     */
    public function get_openai_api_key() {
        $encrypted = $this->get('openai_api_key', '');
        return $this->decrypt_api_key($encrypted);
    }
    
    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('rcp_admin', 'nonce');
        
        if (!current_user_can('rcp_manage_settings')) {
            wp_die('Insufficient permissions');
        }
        
        $settings = $_POST['settings'] ?? [];
        
        if (empty($settings)) {
            wp_send_json_error('No settings provided');
        }
        
        $sanitized = $this->sanitize_settings($settings);
        $result = update_option(self::OPTION_NAME, $sanitized);
        
        if ($result) {
            // Update cron schedule if changed
            if (isset($settings['cron_interval'])) {
                $this->update_cron_schedule($settings['cron_interval']);
            }
            
            wp_send_json_success('Settings saved successfully');
        } else {
            wp_send_json_error('Failed to save settings');
        }
    }
    
    /**
     * AJAX: Reset settings
     */
    public function ajax_reset_settings() {
        check_ajax_referer('rcp_admin', 'nonce');
        
        if (!current_user_can('rcp_manage_settings')) {
            wp_die('Insufficient permissions');
        }
        
        $result = $this->reset();
        
        if ($result) {
            wp_send_json_success('Settings reset to defaults');
        } else {
            wp_send_json_error('Failed to reset settings');
        }
    }
    
    /**
     * AJAX: Test API key
     */
    public function ajax_test_api_key() {
        check_ajax_referer('rcp_admin', 'nonce');
        
        if (!current_user_can('rcp_manage_settings')) {
            wp_die('Insufficient permissions');
        }
        
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($api_key)) {
            wp_send_json_error('API key is required');
        }
        
        $result = $this->test_openai_connection($api_key);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Test OpenAI API connection
     */
    private function test_openai_connection($api_key) {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'timeout' => 10,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => 'Test connection']
                ],
                'max_tokens' => 5,
            ]),
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            return [
                'success' => true,
                'message' => 'API key is valid and working'
            ];
        } elseif ($status_code === 401) {
            return [
                'success' => false,
                'message' => 'Invalid API key'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'API returned status code: ' . $status_code
            ];
        }
    }
    
    /**
     * Update cron schedule
     */
    private function update_cron_schedule($interval) {
        // Clear existing schedule
        wp_clear_scheduled_hook('rcp_fetch_feeds');
        
        // Schedule new interval if cron is enabled
        if ($this->get('enable_cron', true)) {
            wp_schedule_event(time(), $interval, 'rcp_fetch_feeds');
        }
    }
    
    /**
     * Get settings schema for validation
     */
    public function get_schema() {
        return [
            'processing_mode' => [
                'type' => 'string',
                'enum' => ['n8n', 'direct_api'],
                'default' => 'n8n',
                'description' => 'Content processing mode'
            ],
            'download_media' => [
                'type' => 'boolean',
                'default' => false,
                'description' => 'Download and store media files locally'
            ],
            'media_timeout' => [
                'type' => 'integer',
                'minimum' => 5,
                'maximum' => 120,
                'default' => 30,
                'description' => 'Media download timeout in seconds'
            ],
            'max_items_per_fetch' => [
                'type' => 'integer',
                'minimum' => 1,
                'maximum' => 500,
                'default' => 50,
                'description' => 'Maximum items to process per feed fetch'
            ],
            'default_post_status' => [
                'type' => 'string',
                'enum' => ['draft', 'pending', 'private'],
                'default' => 'draft',
                'description' => 'Default status for processed posts'
            ],
            'webhook_timeout' => [
                'type' => 'integer',
                'minimum' => 5,
                'maximum' => 300,
                'default' => 30,
                'description' => 'Webhook request timeout in seconds'
            ],
            'openai_api_key' => [
                'type' => 'string',
                'format' => 'password',
                'description' => 'OpenAI API key for direct processing'
            ],
        ];
    }
    
    /**
     * Export settings for backup
     */
    public function export_settings() {
        $settings = $this->get_all();
        
        // Remove sensitive data from export
        unset($settings['openai_api_key']);
        
        return [
            'version' => RCP_PLUGIN_VERSION,
            'exported_at' => current_time('mysql'),
            'settings' => $settings,
        ];
    }
    
    /**
     * Import settings from backup
     */
    public function import_settings($import_data) {
        if (!isset($import_data['settings']) || !is_array($import_data['settings'])) {
            return new WP_Error('invalid_format', 'Invalid import format');
        }
        
        $sanitized = $this->sanitize_settings($import_data['settings']);
        $result = update_option(self::OPTION_NAME, $sanitized);
        
        if ($result) {
            return true;
        } else {
            return new WP_Error('import_failed', 'Failed to import settings');
        }
    }
}
