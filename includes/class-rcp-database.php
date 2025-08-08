<?php
/**
 * Database Management Class
 *
 * @package RSSContentPlanner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RCP_Database class for managing custom database tables
 */
class RCP_Database {
    
    /**
     * Database version
     */
    const DB_VERSION = '1.0.0';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Only add the version check hook if we're not in activation context
        if (!wp_installing() && did_action('plugins_loaded') === 0) {
            add_action('plugins_loaded', [$this, 'check_db_version']);
        }
    }
    
    /**
     * Check and update database version
     */
    public function check_db_version() {
        $installed_version = get_option('rcp_db_version', '0.0.0');
        
        if (version_compare($installed_version, self::DB_VERSION, '<')) {
            $this->create_tables();
            update_option('rcp_db_version', self::DB_VERSION);
        }
    }
    
    /**
     * Create custom database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Feeds table
        $feeds_table = $wpdb->prefix . 'rcp_feeds';
        $feeds_sql = "CREATE TABLE $feeds_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            url text NOT NULL,
            description text,
            source_site varchar(255),
            language varchar(10) DEFAULT 'en',
            polling_interval int(11) DEFAULT 3600,
            default_category bigint(20) unsigned,
            default_tags text,
            default_author bigint(20) unsigned,
            attribution_template text,
            license_note text,
            settings longtext,
            status enum('active','inactive','error') DEFAULT 'active',
            last_fetch datetime,
            etag varchar(255),
            last_modified varchar(255),
            error_count int(11) DEFAULT 0,
            error_message text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY last_fetch (last_fetch),
            KEY polling_interval (polling_interval)
        ) $charset_collate;";
        
        // Rules table
        $rules_table = $wpdb->prefix . 'rcp_rules';
        $rules_sql = "CREATE TABLE $rules_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            feed_id bigint(20) unsigned,
            priority int(11) DEFAULT 10,
            conditions longtext NOT NULL,
            actions longtext NOT NULL,
            webhook_id bigint(20) unsigned,
            active tinyint(1) DEFAULT 1,
            execution_count int(11) DEFAULT 0,
            success_count int(11) DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY feed_id (feed_id),
            KEY priority (priority),
            KEY active (active),
            KEY webhook_id (webhook_id)
        ) $charset_collate;";
        
        // Webhooks table for n8n integration
        $webhooks_table = $wpdb->prefix . 'rcp_webhooks';
        $webhooks_sql = "CREATE TABLE $webhooks_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            webhook_url text NOT NULL,
            auth_token varchar(255) NOT NULL,
            workflow_name varchar(255),
            workflow_description text,
            n8n_workflow_json longtext,
            feed_ids text,
            processing_type enum('content_rewrite','seo_optimize','translate','custom') DEFAULT 'content_rewrite',
            active tinyint(1) DEFAULT 1,
            test_mode tinyint(1) DEFAULT 0,
            timeout_seconds int(11) DEFAULT 30,
            retry_attempts int(11) DEFAULT 3,
            success_count int(11) DEFAULT 0,
            error_count int(11) DEFAULT 0,
            last_used datetime,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY active (active),
            KEY processing_type (processing_type),
            KEY last_used (last_used),
            UNIQUE KEY auth_token (auth_token)
        ) $charset_collate;";
        
        // Executions table for tracking workflow runs
        $executions_table = $wpdb->prefix . 'rcp_executions';
        $executions_sql = "CREATE TABLE $executions_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            item_id bigint(20) unsigned NOT NULL,
            webhook_id bigint(20) unsigned,
            execution_id varchar(255),
            n8n_execution_id varchar(255),
            status enum('pending','running','success','error','timeout') DEFAULT 'pending',
            request_payload longtext,
            response_payload longtext,
            error_message text,
            processing_time_ms int(11),
            started_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime,
            PRIMARY KEY (id),
            KEY item_id (item_id),
            KEY webhook_id (webhook_id),
            KEY status (status),
            KEY started_at (started_at),
            KEY execution_id (execution_id)
        ) $charset_collate;";
        
        // Templates table for n8n workflow templates
        $templates_table = $wpdb->prefix . 'rcp_templates';
        $templates_sql = "CREATE TABLE $templates_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            category varchar(100) DEFAULT 'general',
            tags text,
            n8n_json longtext NOT NULL,
            preview_image varchar(255),
            author_name varchar(255),
            author_url varchar(255),
            version varchar(20) DEFAULT '1.0.0',
            min_n8n_version varchar(20),
            download_count int(11) DEFAULT 0,
            rating_average decimal(3,2) DEFAULT 0.00,
            rating_count int(11) DEFAULT 0,
            featured tinyint(1) DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY category (category),
            KEY featured (featured),
            KEY active (active),
            KEY rating_average (rating_average)
        ) $charset_collate;";
        
        // Logs table
        $logs_table = $wpdb->prefix . 'rcp_logs';
        $logs_sql = "CREATE TABLE $logs_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            level enum('debug','info','warning','error','critical') DEFAULT 'info',
            context varchar(100),
            message text NOT NULL,
            data longtext,
            user_id bigint(20) unsigned,
            ip_address varchar(45),
            user_agent text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY context (context),
            KEY created_at (created_at),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($feeds_sql);
        dbDelta($rules_sql);
        dbDelta($webhooks_sql);
        dbDelta($executions_sql);
        dbDelta($templates_sql);
        dbDelta($logs_sql);
        
        // Insert default templates
        $this->insert_default_templates();
    }
    
    /**
     * Insert default n8n workflow templates
     */
    private function insert_default_templates() {
        global $wpdb;
        
        $templates_table = $wpdb->prefix . 'rcp_templates';
        
        // Check if templates already exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $templates_table");
        if ($existing > 0) {
            return;
        }
        
        $default_templates = [
            [
                'name' => 'Basic Content Rewriter',
                'slug' => 'basic-content-rewriter',
                'description' => 'Simple content rewriting with OpenAI GPT-4',
                'category' => 'content-rewriting',
                'tags' => 'openai,rewrite,basic',
                'n8n_json' => json_encode([
                    'name' => 'RSS Content Rewriter',
                    'nodes' => [
                        [
                            'parameters' => [
                                'httpMethod' => 'POST',
                                'path' => 'rss-webhook',
                                'authentication' => 'headerAuth'
                            ],
                            'type' => 'n8n-nodes-base.webhook',
                            'name' => 'RSS Input'
                        ],
                        [
                            'parameters' => [
                                'model' => 'gpt-4',
                                'prompt' => 'Rewrite the following article in a professional tone while maintaining all key facts and information. Make it engaging and well-structured:\n\n{{ $json["content"] }}'
                            ],
                            'type' => 'n8n-nodes-base.openAi',
                            'name' => 'Rewrite Content'
                        ],
                        [
                            'parameters' => [
                                'url' => '{{ $json["callback_url"] }}',
                                'sendBody' => true,
                                'bodyParameters' => [
                                    'status' => 'success',
                                    'rewritten_content' => '{{ $json["choices"][0]["message"]["content"] }}',
                                    'execution_id' => '{{ $json["execution_id"] }}'
                                ]
                            ],
                            'type' => 'n8n-nodes-base.httpRequest',
                            'name' => 'Return to WordPress'
                        ]
                    ]
                ]),
                'featured' => 1
            ],
            [
                'name' => 'SEO-Optimized Content',
                'slug' => 'seo-optimized-content',
                'description' => 'Multi-step SEO enhancement with keyword research',
                'category' => 'seo',
                'tags' => 'seo,keywords,optimization',
                'n8n_json' => json_encode([
                    'name' => 'SEO Content Pipeline',
                    'nodes' => [
                        [
                            'type' => 'n8n-nodes-base.webhook',
                            'name' => 'RSS Input'
                        ],
                        [
                            'type' => 'n8n-nodes-base.googleTrends',
                            'name' => 'Keyword Research'
                        ],
                        [
                            'type' => 'n8n-nodes-base.openAi',
                            'name' => 'SEO Rewrite'
                        ],
                        [
                            'type' => 'n8n-nodes-base.httpRequest',
                            'name' => 'Return Enhanced Content'
                        ]
                    ]
                ]),
                'featured' => 1
            ],
            [
                'name' => 'Multi-Language Translation',
                'slug' => 'multi-language-translation',
                'description' => 'Translate and culturally adapt content for different markets',
                'category' => 'translation',
                'tags' => 'translation,localization,multilingual',
                'n8n_json' => json_encode([
                    'name' => 'Translation Pipeline',
                    'nodes' => [
                        [
                            'type' => 'n8n-nodes-base.webhook',
                            'name' => 'RSS Input'
                        ],
                        [
                            'type' => 'n8n-nodes-base.googleTranslate',
                            'name' => 'Translate Content'
                        ],
                        [
                            'type' => 'n8n-nodes-base.openAi',
                            'name' => 'Cultural Adaptation'
                        ],
                        [
                            'type' => 'n8n-nodes-base.httpRequest',
                            'name' => 'Return Translated Content'
                        ]
                    ]
                ]),
                'featured' => 0
            ]
        ];
        
        foreach ($default_templates as $template) {
            $wpdb->insert($templates_table, $template);
        }
    }
    
    /**
     * Drop all custom tables
     */
    public function drop_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'rcp_feeds',
            $wpdb->prefix . 'rcp_rules',
            $wpdb->prefix . 'rcp_webhooks',
            $wpdb->prefix . 'rcp_executions',
            $wpdb->prefix . 'rcp_templates',
            $wpdb->prefix . 'rcp_logs'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        delete_option('rcp_db_version');
    }
    
    /**
     * Get table name with prefix
     */
    public function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'rcp_' . $table;
    }
    
    /**
     * Log an event
     */
    public function log($level, $context, $message, $data = null) {
        global $wpdb;
        
        $logs_table = $this->get_table_name('logs');
        
        $log_data = [
            'level' => $level,
            'context' => $context,
            'message' => $message,
            'data' => $data ? json_encode($data) : null,
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];
        
        $wpdb->insert($logs_table, $log_data);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
