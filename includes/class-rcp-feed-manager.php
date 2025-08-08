<?php
/**
 * Feed Manager Class
 *
 * @package RSSContentPlanner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RCP_Feed_Manager class for managing RSS/Atom feeds
 */
class RCP_Feed_Manager {
    
    /**
     * Database instance
     */
    private $db;
    
    /**
     * Webhook manager instance
     */
    private $webhook_manager;
    
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
        add_action('rcp_fetch_feeds', [$this, 'fetch_all_feeds']);
        add_action('wp_ajax_rcp_add_feed', [$this, 'ajax_add_feed']);
        add_action('wp_ajax_rcp_test_feed', [$this, 'ajax_test_feed']);
        add_action('wp_ajax_rcp_import_feeds_csv', [$this, 'ajax_import_feeds_csv']);
    }
    
    /**
     * Set webhook manager instance
     */
    public function set_webhook_manager($webhook_manager) {
        $this->webhook_manager = $webhook_manager;
    }
    
    /**
     * Add a new feed
     */
    public function add_feed($feed_data) {
        global $wpdb;
        
        $feeds_table = $this->db->get_table_name('feeds');
        
        // Validate required fields
        if (empty($feed_data['name']) || empty($feed_data['url'])) {
            return new WP_Error('missing_data', 'Feed name and URL are required');
        }
        
        // Validate feed URL
        $validation_result = $this->validate_feed($feed_data['url']);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }
        
        // Prepare feed data
        $insert_data = [
            'name' => sanitize_text_field($feed_data['name']),
            'url' => esc_url_raw($feed_data['url']),
            'description' => sanitize_textarea_field($feed_data['description'] ?? ''),
            'source_site' => sanitize_text_field($feed_data['source_site'] ?? ''),
            'language' => sanitize_text_field($feed_data['language'] ?? 'en'),
            'polling_interval' => intval($feed_data['polling_interval'] ?? 3600),
            'default_category' => intval($feed_data['default_category'] ?? 0),
            'default_tags' => sanitize_text_field($feed_data['default_tags'] ?? ''),
            'default_author' => intval($feed_data['default_author'] ?? get_current_user_id()),
            'attribution_template' => sanitize_text_field($feed_data['attribution_template'] ?? ''),
            'license_note' => sanitize_text_field($feed_data['license_note'] ?? ''),
            'settings' => json_encode($feed_data['settings'] ?? []),
            'status' => 'active',
        ];
        
        $result = $wpdb->insert($feeds_table, $insert_data);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to add feed');
        }
        
        $feed_id = $wpdb->insert_id;
        
        // Log the creation
        $this->db->log('info', 'feed', "Feed added: {$insert_data['name']}", ['feed_id' => $feed_id]);
        
        // Trigger initial fetch
        $this->fetch_feed($feed_id);
        
        return $feed_id;
    }
    
    /**
     * Validate a feed URL
     */
    public function validate_feed($url) {
        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', 'Invalid feed URL');
        }
        
        // Try to fetch the feed
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'user-agent' => 'RSS-Content-Planner/' . RCP_PLUGIN_VERSION,
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('fetch_error', 'Unable to fetch feed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code < 200 || $response_code >= 300) {
            return new WP_Error('http_error', "HTTP error: {$response_code}");
        }
        
        $content = wp_remote_retrieve_body($response);
        if (empty($content)) {
            return new WP_Error('empty_content', 'Feed content is empty');
        }
        
        // Try to parse as XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            $error_message = 'Invalid XML';
            if (!empty($errors)) {
                $error_message .= ': ' . $errors[0]->message;
            }
            return new WP_Error('invalid_xml', $error_message);
        }
        
        // Check if it's a valid RSS/Atom feed
        if (!isset($xml->channel) && !isset($xml->entry) && $xml->getName() !== 'feed') {
            return new WP_Error('invalid_feed', 'Not a valid RSS or Atom feed');
        }
        
        return [
            'valid' => true,
            'type' => $this->detect_feed_type($xml),
            'title' => $this->extract_feed_title($xml),
            'etag' => wp_remote_retrieve_header($response, 'etag'),
            'last_modified' => wp_remote_retrieve_header($response, 'last-modified'),
        ];
    }
    
    /**
     * Detect feed type (RSS or Atom)
     */
    private function detect_feed_type($xml) {
        if ($xml->getName() === 'feed') {
            return 'atom';
        } elseif (isset($xml->channel)) {
            return 'rss';
        }
        return 'unknown';
    }
    
    /**
     * Extract feed title
     */
    private function extract_feed_title($xml) {
        if ($xml->getName() === 'feed') {
            return (string) $xml->title;
        } elseif (isset($xml->channel)) {
            return (string) $xml->channel->title;
        }
        return '';
    }
    
    /**
     * Fetch all active feeds
     */
    public function fetch_all_feeds() {
        global $wpdb;
        
        $feeds_table = $this->db->get_table_name('feeds');
        
        // Get feeds that are due for fetching
        $feeds = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $feeds_table 
            WHERE status = 'active' 
            AND (last_fetch IS NULL OR last_fetch < DATE_SUB(NOW(), INTERVAL polling_interval SECOND))
            ORDER BY RAND()
            LIMIT %d
        ", 10)); // Limit concurrent fetches
        
        foreach ($feeds as $feed) {
            $this->fetch_feed($feed->id);
        }
    }
    
    /**
     * Fetch a specific feed
     */
    public function fetch_feed($feed_id) {
        global $wpdb;
        
        $feeds_table = $this->db->get_table_name('feeds');
        
        $feed = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $feeds_table WHERE id = %d",
            $feed_id
        ));
        
        if (!$feed) {
            return new WP_Error('feed_not_found', 'Feed not found');
        }
        
        // Update last fetch timestamp
        $wpdb->update($feeds_table, 
            ['last_fetch' => current_time('mysql')], 
            ['id' => $feed_id]
        );
        
        // Prepare request headers
        $headers = [
            'User-Agent' => 'RSS-Content-Planner/' . RCP_PLUGIN_VERSION,
        ];
        
        if ($feed->etag) {
            $headers['If-None-Match'] = $feed->etag;
        }
        
        if ($feed->last_modified) {
            $headers['If-Modified-Since'] = $feed->last_modified;
        }
        
        // Fetch the feed
        $response = wp_remote_get($feed->url, [
            'timeout' => 30,
            'headers' => $headers,
        ]);
        
        if (is_wp_error($response)) {
            $this->handle_feed_error($feed_id, $response->get_error_message());
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        // Handle 304 Not Modified
        if ($response_code === 304) {
            $this->db->log('info', 'feed_fetch', "Feed not modified: {$feed->name}", ['feed_id' => $feed_id]);
            return ['status' => 'not_modified'];
        }
        
        if ($response_code < 200 || $response_code >= 300) {
            $this->handle_feed_error($feed_id, "HTTP error: {$response_code}");
            return new WP_Error('http_error', "HTTP error: {$response_code}");
        }
        
        // Update ETag and Last-Modified
        $new_etag = wp_remote_retrieve_header($response, 'etag');
        $new_last_modified = wp_remote_retrieve_header($response, 'last-modified');
        
        if ($new_etag || $new_last_modified) {
            $wpdb->update($feeds_table, [
                'etag' => $new_etag,
                'last_modified' => $new_last_modified,
            ], ['id' => $feed_id]);
        }
        
        $content = wp_remote_retrieve_body($response);
        
        // Parse feed content
        $parsed_items = $this->parse_feed_content($content, $feed);
        
        if (is_wp_error($parsed_items)) {
            $this->handle_feed_error($feed_id, $parsed_items->get_error_message());
            return $parsed_items;
        }
        
        // Process each item
        $processed_count = 0;
        foreach ($parsed_items as $item) {
            if ($this->process_feed_item($item, $feed)) {
                $processed_count++;
            }
        }
        
        // Reset error count on successful fetch
        $wpdb->update($feeds_table, [
            'error_count' => 0,
            'error_message' => null,
        ], ['id' => $feed_id]);
        
        $this->db->log('info', 'feed_fetch', "Feed fetched successfully: {$feed->name}", [
            'feed_id' => $feed_id,
            'items_processed' => $processed_count,
        ]);
        
        return ['status' => 'success', 'items_processed' => $processed_count];
    }
    
    /**
     * Parse feed content
     */
    private function parse_feed_content($content, $feed) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        
        if ($xml === false) {
            return new WP_Error('parse_error', 'Failed to parse feed XML');
        }
        
        $items = [];
        
        // Handle RSS feeds
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $items[] = $this->parse_rss_item($item, $feed);
            }
        }
        // Handle Atom feeds
        elseif ($xml->getName() === 'feed' && isset($xml->entry)) {
            foreach ($xml->entry as $entry) {
                $items[] = $this->parse_atom_entry($entry, $feed);
            }
        }
        
        return $items;
    }
    
    /**
     * Parse RSS item
     */
    private function parse_rss_item($item, $feed) {
        $namespaces = $item->getNamespaces(true);
        
        return [
            'title' => (string) $item->title,
            'content' => (string) ($item->children($namespaces['content'] ?? [])->encoded ?? $item->description),
            'description' => (string) $item->description,
            'link' => (string) $item->link,
            'guid' => (string) $item->guid,
            'author' => (string) $item->author,
            'pub_date' => (string) $item->pubDate,
            'categories' => $this->extract_categories($item),
            'feed_id' => $feed->id,
            'source_site' => $feed->source_site,
        ];
    }
    
    /**
     * Parse Atom entry
     */
    private function parse_atom_entry($entry, $feed) {
        $content = '';
        if (isset($entry->content)) {
            $content = (string) $entry->content;
        } elseif (isset($entry->summary)) {
            $content = (string) $entry->summary;
        }
        
        $link = '';
        if (isset($entry->link)) {
            foreach ($entry->link as $link_element) {
                if ((string) $link_element['rel'] === 'alternate' || !isset($link_element['rel'])) {
                    $link = (string) $link_element['href'];
                    break;
                }
            }
        }
        
        return [
            'title' => (string) $entry->title,
            'content' => $content,
            'description' => (string) $entry->summary,
            'link' => $link,
            'guid' => (string) $entry->id,
            'author' => (string) $entry->author->name,
            'pub_date' => (string) $entry->published ?: (string) $entry->updated,
            'categories' => $this->extract_atom_categories($entry),
            'feed_id' => $feed->id,
            'source_site' => $feed->source_site,
        ];
    }
    
    /**
     * Extract categories from RSS item
     */
    private function extract_categories($item) {
        $categories = [];
        if (isset($item->category)) {
            foreach ($item->category as $category) {
                $categories[] = (string) $category;
            }
        }
        return $categories;
    }
    
    /**
     * Extract categories from Atom entry
     */
    private function extract_atom_categories($entry) {
        $categories = [];
        if (isset($entry->category)) {
            foreach ($entry->category as $category) {
                $categories[] = (string) $category['term'];
            }
        }
        return $categories;
    }
    
    /**
     * Process a feed item
     */
    private function process_feed_item($item_data, $feed) {
        // Check for duplicates
        if ($this->is_duplicate_item($item_data)) {
            return false;
        }
        
        // Create RSS item post
        $post_data = [
            'post_title' => wp_trim_words($item_data['title'], 20),
            'post_content' => wp_kses_post($item_data['content']),
            'post_excerpt' => wp_trim_words(strip_tags($item_data['description']), 55),
            'post_status' => 'private',
            'post_type' => 'rss_item',
            'post_date' => $this->parse_date($item_data['pub_date']),
            'meta_input' => [
                '_rss_source_url' => esc_url($item_data['link']),
                '_rss_source_guid' => sanitize_text_field($item_data['guid']),
                '_rss_canonical' => esc_url($item_data['link']),
                '_rss_source_license' => sanitize_text_field($feed->license_note),
                '_rss_feed_id' => $feed->id,
                '_rss_source_site' => sanitize_text_field($item_data['source_site']),
                '_rss_author' => sanitize_text_field($item_data['author']),
                '_rss_categories' => json_encode($item_data['categories']),
                '_processing_status' => 'pending',
            ],
        ];
        
        $rss_item_id = wp_insert_post($post_data);
        
        if (is_wp_error($rss_item_id)) {
            return false;
        }
        
        // Apply source site taxonomy
        if ($item_data['source_site']) {
            wp_set_object_terms($rss_item_id, $item_data['source_site'], 'rcp_source_site');
        }
        
        // Trigger processing if webhook manager is available
        if ($this->webhook_manager) {
            $this->trigger_content_processing($rss_item_id, $item_data, $feed);
        }
        
        do_action('rcp_item_processed', $rss_item_id, $item_data, $feed);
        
        return true;
    }
    
    /**
     * Check for duplicate items
     */
    private function is_duplicate_item($item_data) {
        global $wpdb;
        
        // Check by GUID first
        if (!empty($item_data['guid'])) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_rss_source_guid' AND meta_value = %s",
                $item_data['guid']
            ));
            
            if ($existing) {
                return true;
            }
        }
        
        // Check by URL
        if (!empty($item_data['link'])) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_rss_source_url' AND meta_value = %s",
                $item_data['link']
            ));
            
            if ($existing) {
                return true;
            }
        }
        
        // TODO: Implement fuzzy similarity check for title + content
        
        return false;
    }
    
    /**
     * Parse publication date
     */
    private function parse_date($date_string) {
        if (empty($date_string)) {
            return current_time('mysql');
        }
        
        $timestamp = strtotime($date_string);
        if ($timestamp === false) {
            return current_time('mysql');
        }
        
        return date('Y-m-d H:i:s', $timestamp);
    }
    
    /**
     * Trigger content processing
     */
    private function trigger_content_processing($rss_item_id, $item_data, $feed) {
        // Check if feed has associated webhooks
        global $wpdb;
        
        $rules_table = $this->db->get_table_name('rules');
        
        $applicable_rules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $rules_table WHERE (feed_id = %d OR feed_id IS NULL) AND active = 1 ORDER BY priority ASC",
            $feed->id
        ));
        
        foreach ($applicable_rules as $rule) {
            if ($this->evaluate_rule($rule, $item_data, $feed)) {
                $this->execute_rule_actions($rule, $rss_item_id, $item_data, $feed);
                break; // Execute only the first matching rule
            }
        }
    }
    
    /**
     * Evaluate rule conditions
     */
    private function evaluate_rule($rule, $item_data, $feed) {
        $conditions = json_decode($rule->conditions, true);
        if (!$conditions) {
            return false;
        }
        
        // Simple condition evaluation (can be expanded)
        foreach ($conditions as $condition) {
            switch ($condition['type']) {
                case 'title_contains':
                    if (stripos($item_data['title'], $condition['value']) === false) {
                        return false;
                    }
                    break;
                    
                case 'content_contains':
                    if (stripos($item_data['content'], $condition['value']) === false) {
                        return false;
                    }
                    break;
                    
                case 'source_domain':
                    $domain = parse_url($item_data['link'], PHP_URL_HOST);
                    if ($domain !== $condition['value']) {
                        return false;
                    }
                    break;
            }
        }
        
        return true;
    }
    
    /**
     * Execute rule actions
     */
    private function execute_rule_actions($rule, $rss_item_id, $item_data, $feed) {
        $actions = json_decode($rule->actions, true);
        if (!$actions) {
            return;
        }
        
        foreach ($actions as $action) {
            switch ($action['type']) {
                case 'send_to_webhook':
                    if ($rule->webhook_id && $this->webhook_manager) {
                        $content_data = [
                            'item_id' => $rss_item_id,
                            'content' => $item_data['content'],
                            'title' => $item_data['title'],
                            'source_url' => $item_data['link'],
                            'metadata' => [
                                'author' => $item_data['author'],
                                'categories' => $item_data['categories'],
                                'pub_date' => $item_data['pub_date'],
                            ],
                        ];
                        
                        $this->webhook_manager->send_to_webhook($rule->webhook_id, $content_data);
                    }
                    break;
                    
                case 'assign_category':
                    update_post_meta($rss_item_id, '_auto_assigned_category', $action['value']);
                    break;
                    
                case 'assign_tags':
                    update_post_meta($rss_item_id, '_auto_assigned_tags', $action['value']);
                    break;
            }
        }
    }
    
    /**
     * Handle feed errors
     */
    private function handle_feed_error($feed_id, $error_message) {
        global $wpdb;
        
        $feeds_table = $this->db->get_table_name('feeds');
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $feeds_table SET error_count = error_count + 1, error_message = %s WHERE id = %d",
            $error_message,
            $feed_id
        ));
        
        // Deactivate feed if too many errors
        $feed = $wpdb->get_row($wpdb->prepare("SELECT * FROM $feeds_table WHERE id = %d", $feed_id));
        if ($feed && $feed->error_count >= 5) {
            $wpdb->update($feeds_table, ['status' => 'error'], ['id' => $feed_id]);
            
            $this->db->log('warning', 'feed', "Feed deactivated due to repeated errors: {$feed->name}", [
                'feed_id' => $feed_id,
                'error_count' => $feed->error_count,
            ]);
        }
        
        $this->db->log('error', 'feed_fetch', $error_message, ['feed_id' => $feed_id]);
    }
    
    /**
     * AJAX: Add feed
     */
    public function ajax_add_feed() {
        check_ajax_referer('rcp_add_feed', 'nonce');
        
        if (!current_user_can('rcp_manage_feeds')) {
            wp_die('Insufficient permissions');
        }
        
        $feed_data = [
            'name' => sanitize_text_field($_POST['name']),
            'url' => esc_url_raw($_POST['url']),
            'description' => sanitize_textarea_field($_POST['description']),
            'source_site' => sanitize_text_field($_POST['source_site']),
            'language' => sanitize_text_field($_POST['language']),
            'polling_interval' => intval($_POST['polling_interval']),
            'default_category' => intval($_POST['default_category']),
            'default_tags' => sanitize_text_field($_POST['default_tags']),
            'attribution_template' => sanitize_text_field($_POST['attribution_template']),
            'license_note' => sanitize_text_field($_POST['license_note']),
        ];
        
        $result = $this->add_feed($feed_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(['feed_id' => $result]);
        }
    }
    
    /**
     * AJAX: Test feed
     */
    public function ajax_test_feed() {
        check_ajax_referer('rcp_test_feed', 'nonce');
        
        $url = esc_url_raw($_POST['url']);
        $result = $this->validate_feed($url);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    /**
     * Get all feeds
     */
    public function get_feeds($status = null) {
        global $wpdb;
        
        $feeds_table = $this->db->get_table_name('feeds');
        
        $where = $status ? $wpdb->prepare('WHERE status = %s', $status) : '';
        
        return $wpdb->get_results("SELECT * FROM $feeds_table $where ORDER BY created_at DESC");
    }
    
    /**
     * Delete feed
     */
    public function delete_feed($feed_id) {
        global $wpdb;
        
        $feeds_table = $this->db->get_table_name('feeds');
        
        // Get feed info for logging
        $feed = $wpdb->get_row($wpdb->prepare("SELECT name FROM $feeds_table WHERE id = %d", $feed_id));
        
        $result = $wpdb->delete($feeds_table, ['id' => $feed_id]);
        
        if ($result !== false) {
            // Delete associated RSS items
            $rss_items = get_posts([
                'post_type' => 'rss_item',
                'meta_key' => '_rss_feed_id',
                'meta_value' => $feed_id,
                'posts_per_page' => -1,
                'fields' => 'ids',
            ]);
            
            foreach ($rss_items as $item_id) {
                wp_delete_post($item_id, true);
            }
            
            $this->db->log('info', 'feed', "Feed deleted: {$feed->name}", ['feed_id' => $feed_id]);
        }
        
        return $result;
    }
}
