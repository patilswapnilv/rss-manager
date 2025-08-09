<?php
/**
 * Admin Class
 *
 * @package RSSContentPlanner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RCP_Admin class for handling admin functionality
 */
class RCP_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('RSS Content Planner', 'rss-content-planner'),
            __('RSS Planner', 'rss-content-planner'),
            'rcp_manage_feeds',
            'rss-content-planner',
            [$this, 'admin_page'],
            'dashicons-rss',
            30
        );
        
        add_submenu_page(
            'rss-content-planner',
            __('Feeds', 'rss-content-planner'),
            __('Feeds', 'rss-content-planner'),
            'rcp_manage_feeds',
            'rcp-feeds',
            [$this, 'feeds_page']
        );
        
        add_submenu_page(
            'rss-content-planner',
            __('Workflows', 'rss-content-planner'),
            __('Workflows', 'rss-content-planner'),
            'rcp_manage_workflows',
            'rcp-workflows',
            [$this, 'workflows_page']
        );
        
        add_submenu_page(
            'rss-content-planner',
            __('Inbox', 'rss-content-planner'),
            __('Inbox', 'rss-content-planner'),
            'rcp_edit_items',
            'rcp-inbox',
            [$this, 'inbox_page']
        );
        
        add_submenu_page(
            'rss-content-planner',
            __('Settings', 'rss-content-planner'),
            __('Settings', 'rss-content-planner'),
            'rcp_manage_settings',
            'rcp-settings',
            [$this, 'settings_page']
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'rss-content-planner') === false && strpos($hook, 'rcp-') === false) {
            return;
        }
        
        wp_enqueue_script(
            'rcp-admin',
            RCP_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            RCP_PLUGIN_VERSION,
            true
        );
        
        wp_enqueue_style(
            'rcp-admin',
            RCP_PLUGIN_URL . 'assets/css/admin.css',
            [],
            RCP_PLUGIN_VERSION
        );
        
        wp_localize_script('rcp-admin', 'rcpAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rcp_admin'),
        ]);
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        echo '<div class="wrap"><h1>' . __('RSS Content Planner Dashboard', 'rss-content-planner') . '</h1>';
        echo '<p>' . __('Welcome to RSS Content Planner. Get started by adding feeds and configuring workflows.', 'rss-content-planner') . '</p>';
        echo '</div>';
    }
    
    /**
     * Feeds page
     */
    public function feeds_page() {
        $action = $_GET['action'] ?? 'list';
        $feed_id = $_GET['feed_id'] ?? null;
        
        switch ($action) {
            case 'add':
                $this->render_add_feed_page();
                break;
            case 'edit':
                $this->render_edit_feed_page($feed_id);
                break;
            case 'bulk_import':
                $this->render_bulk_import_page();
                break;
            default:
                $this->render_feeds_list_page();
        }
    }
    
    /**
     * Workflows page
     */
    public function workflows_page() {
        echo '<div class="wrap"><h1>' . __('n8n Workflows', 'rss-content-planner') . '</h1>';
        echo '<p>' . __('Manage your n8n workflow integrations here.', 'rss-content-planner') . '</p>';
        echo '</div>';
    }
    
    /**
     * Inbox page
     */
    public function inbox_page() {
        $status_filter = $_GET['status'] ?? 'all';
        $feed_filter = $_GET['feed'] ?? 'all';
        
        $this->render_content_inbox($status_filter, $feed_filter);
    }
    
    /**
     * Render content inbox
     */
    private function render_content_inbox($status_filter, $feed_filter) {
        $items = $this->get_inbox_items($status_filter, $feed_filter);
        $feeds = $this->get_feeds_for_filter();
        $status_counts = $this->get_status_counts();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Content Inbox', 'rss-content-planner'); ?></h1>
            <hr class="wp-header-end">
            
            <!-- Status Tabs -->
            <div class="rcp-status-tabs">
                <?php
                $statuses = [
                    'all' => __('All', 'rss-content-planner'),
                    'pending' => __('Pending', 'rss-content-planner'),
                    'processing' => __('Processing', 'rss-content-planner'),
                    'completed' => __('Completed', 'rss-content-planner'),
                    'error' => __('Error', 'rss-content-planner'),
                ];
                
                foreach ($statuses as $status => $label) {
                    $count = $status_counts[$status] ?? 0;
                    $active = $status_filter === $status ? 'nav-tab-active' : '';
                    $url = admin_url("admin.php?page=rcp-inbox&status={$status}");
                    
                    echo "<a href='{$url}' class='nav-tab {$active}'>{$label} <span class='count'>({$count})</span></a>";
                }
                ?>
            </div>
            
            <!-- Filters -->
            <div class="rcp-filters" style="margin: 20px 0;">
                <form method="get" action="" style="display: inline-block;">
                    <input type="hidden" name="page" value="rcp-inbox">
                    <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
                    
                    <select name="feed" onchange="this.form.submit()">
                        <option value="all"><?php _e('All Feeds', 'rss-content-planner'); ?></option>
                        <?php foreach ($feeds as $feed): ?>
                            <option value="<?php echo $feed->id; ?>" <?php selected($feed_filter, $feed->id); ?>>
                                <?php echo esc_html($feed->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                
                <div style="float: right;">
                    <button class="button" onclick="location.reload()">
                        <?php _e('Refresh', 'rss-content-planner'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Content Items -->
            <?php if (empty($items)): ?>
                <div class="notice notice-info">
                    <p><?php _e('No content items found for the selected filters.', 'rss-content-planner'); ?></p>
                </div>
            <?php else: ?>
                <div class="rcp-inbox-grid">
                    <?php foreach ($items as $item): ?>
                        <div class="rcp-inbox-item" data-item-id="<?php echo $item->ID; ?>">
                            <div class="rcp-item-header">
                                <h3 class="rcp-item-title">
                                    <a href="<?php echo get_edit_post_link($item->ID); ?>">
                                        <?php echo esc_html($item->post_title); ?>
                                    </a>
                                </h3>
                                <div class="rcp-item-meta">
                                    <span class="rcp-status-badge <?php echo $this->get_processing_status($item->ID); ?>">
                                        <?php echo ucfirst($this->get_processing_status($item->ID)); ?>
                                    </span>
                                    <span class="rcp-source">
                                        <?php echo esc_html(get_post_meta($item->ID, '_rss_source_site', true)); ?>
                                    </span>
                                    <span class="rcp-date">
                                        <?php echo human_time_diff(strtotime($item->post_date), time()) . ' ago'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="rcp-item-content">
                                <div class="rcp-excerpt">
                                    <?php echo wp_trim_words($item->post_content, 30); ?>
                                </div>
                                
                                <?php
                                $featured_image = get_post_meta($item->ID, '_rss_featured_image_url', true);
                                if ($featured_image):
                                ?>
                                <div class="rcp-featured-image">
                                    <img src="<?php echo esc_url($featured_image); ?>" alt="" style="max-width: 100px; height: auto;">
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="rcp-item-actions">
                                <a href="<?php echo get_post_meta($item->ID, '_rss_source_url', true); ?>" 
                                   target="_blank" class="button button-small">
                                    <?php _e('View Source', 'rss-content-planner'); ?>
                                </a>
                                
                                <button class="button button-small rcp-process-item" 
                                        data-item-id="<?php echo $item->ID; ?>">
                                    <?php _e('Process Now', 'rss-content-planner'); ?>
                                </button>
                                
                                <button class="button button-small rcp-create-post" 
                                        data-item-id="<?php echo $item->ID; ?>">
                                    <?php _e('Create Post', 'rss-content-planner'); ?>
                                </button>
                                
                                <button class="button button-small button-link-delete rcp-delete-item" 
                                        data-item-id="<?php echo $item->ID; ?>">
                                    <?php _e('Delete', 'rss-content-planner'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .rcp-status-tabs {
            border-bottom: 1px solid #ccd0d4;
            margin-bottom: 20px;
        }
        
        .rcp-inbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }
        
        .rcp-inbox-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .rcp-item-header h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        
        .rcp-item-meta {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 12px;
            color: #666;
        }
        
        .rcp-status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .rcp-status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .rcp-status-badge.processing {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .rcp-status-badge.completed {
            background: #d4edda;
            color: #155724;
        }
        
        .rcp-status-badge.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .rcp-item-content {
            margin-bottom: 15px;
        }
        
        .rcp-excerpt {
            line-height: 1.5;
            color: #555;
        }
        
        .rcp-item-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .rcp-featured-image {
            margin-top: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Get inbox items based on filters
     */
    private function get_inbox_items($status_filter, $feed_filter) {
        $args = [
            'post_type' => 'rss_item',
            'post_status' => 'private',
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
        ];
        
        $meta_query = [];
        
        if ($status_filter !== 'all') {
            $meta_query[] = [
                'key' => '_processing_status',
                'value' => $status_filter,
                'compare' => '='
            ];
        }
        
        if ($feed_filter !== 'all') {
            $meta_query[] = [
                'key' => '_rss_feed_id',
                'value' => $feed_filter,
                'compare' => '='
            ];
        }
        
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }
        
        return get_posts($args);
    }
    
    /**
     * Get feeds for filter dropdown
     */
    private function get_feeds_for_filter() {
        $feed_manager = new RCP_Feed_Manager();
        return $feed_manager->get_feeds('active');
    }
    
    /**
     * Get status counts for tabs
     */
    private function get_status_counts() {
        global $wpdb;
        
        $counts = $wpdb->get_results("
            SELECT meta_value as status, COUNT(*) as count 
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_processing_status' 
            AND p.post_type = 'rss_item'
            GROUP BY meta_value
        ");
        
        $status_counts = ['all' => 0];
        
        foreach ($counts as $count) {
            $status_counts[$count->status] = $count->count;
            $status_counts['all'] += $count->count;
        }
        
        return $status_counts;
    }
    
    /**
     * Get processing status for an item
     */
    private function get_processing_status($item_id) {
        return get_post_meta($item_id, '_processing_status', true) ?: 'pending';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $settings = new RCP_Settings();
        $current_settings = $settings->get_all();
        
        ?>
        <div class="wrap">
            <h1><?php _e('RSS Content Planner Settings', 'rss-content-planner'); ?></h1>
            
            <form method="post" action="options.php" class="rcp-settings-form">
                <?php settings_fields('rcp_settings_group'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Processing Mode', 'rss-content-planner'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="rcp_settings[processing_mode]" value="n8n" 
                                           <?php checked($current_settings['processing_mode'], 'n8n'); ?>>
                                    <?php _e('n8n Workflows (Recommended)', 'rss-content-planner'); ?>
                                </label><br>
                                <label>
                                    <input type="radio" name="rcp_settings[processing_mode]" value="direct_api" 
                                           <?php checked($current_settings['processing_mode'], 'direct_api'); ?>>
                                    <?php _e('Direct API Integration', 'rss-content-planner'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Download Media', 'rss-content-planner'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="rcp_settings[download_media]" value="1" 
                                       <?php checked($current_settings['download_media']); ?>>
                                <?php _e('Download and store media files locally', 'rss-content-planner'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Max Items Per Fetch', 'rss-content-planner'); ?></th>
                        <td>
                            <input type="number" name="rcp_settings[max_items_per_fetch]" 
                                   value="<?php echo esc_attr($current_settings['max_items_per_fetch']); ?>" 
                                   min="1" max="500" class="small-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Enable Logging', 'rss-content-planner'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="rcp_settings[enable_logging]" value="1" 
                                       <?php checked($current_settings['enable_logging']); ?>>
                                <?php _e('Enable debug logging', 'rss-content-planner'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render feeds list page
     */
    private function render_feeds_list_page() {
        $feed_manager = new RCP_Feed_Manager();
        $feeds = $feed_manager->get_feeds();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('RSS Feeds', 'rss-content-planner'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=rcp-feeds&action=add'); ?>" class="page-title-action">
                <?php _e('Add New Feed', 'rss-content-planner'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=rcp-feeds&action=bulk_import'); ?>" class="page-title-action">
                <?php _e('Bulk Import', 'rss-content-planner'); ?>
            </a>
            <hr class="wp-header-end">
            
            <?php if (empty($feeds)): ?>
                <div class="notice notice-info">
                    <p><?php _e('No feeds found. Add your first RSS feed to get started.', 'rss-content-planner'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'rss-content-planner'); ?></th>
                            <th><?php _e('URL', 'rss-content-planner'); ?></th>
                            <th><?php _e('Status', 'rss-content-planner'); ?></th>
                            <th><?php _e('Last Fetch', 'rss-content-planner'); ?></th>
                            <th><?php _e('Items', 'rss-content-planner'); ?></th>
                            <th><?php _e('Actions', 'rss-content-planner'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feeds as $feed): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=rcp-feeds&action=edit&feed_id=' . $feed->id); ?>">
                                            <?php echo esc_html($feed->name); ?>
                                        </a>
                                    </strong>
                                    <?php if ($feed->description): ?>
                                        <br><small class="description"><?php echo esc_html($feed->description); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url($feed->url); ?>" target="_blank" rel="noopener">
                                        <?php echo esc_html(wp_trim_words($feed->url, 5, '...')); ?>
                                        <span class="dashicons dashicons-external"></span>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    $status_class = $feed->status === 'active' ? 'success' : ($feed->status === 'error' ? 'error' : 'warning');
                                    echo "<span class='status-{$status_class}'>" . ucfirst($feed->status) . "</span>";
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($feed->last_fetch) {
                                        echo human_time_diff(strtotime($feed->last_fetch), time()) . ' ago';
                                    } else {
                                        echo __('Never', 'rss-content-planner');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $item_count = $this->get_feed_item_count($feed->id);
                                    echo number_format($item_count);
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=rcp-feeds&action=edit&feed_id=' . $feed->id); ?>" class="button button-small">
                                        <?php _e('Edit', 'rss-content-planner'); ?>
                                    </a>
                                    <button class="button button-small rcp-fetch-feed" data-feed-id="<?php echo $feed->id; ?>">
                                        <?php _e('Fetch Now', 'rss-content-planner'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Get feed item count
     */
    private function get_feed_item_count($feed_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_rss_feed_id' AND meta_value = %d",
            $feed_id
        ));
    }
    
    /**
     * Render add feed page
     */
    private function render_add_feed_page() {
        // Placeholder for add feed form
        echo '<div class="wrap">';
        echo '<h1>' . __('Add New RSS Feed', 'rss-content-planner') . '</h1>';
        echo '<p>' . __('Add feed form will be implemented here.', 'rss-content-planner') . '</p>';
        echo '</div>';
    }
    
    /**
     * Render edit feed page
     */
    private function render_edit_feed_page($feed_id) {
        // Placeholder for edit feed form
        echo '<div class="wrap">';
        echo '<h1>' . __('Edit RSS Feed', 'rss-content-planner') . '</h1>';
        echo '<p>' . __('Edit feed form will be implemented here.', 'rss-content-planner') . '</p>';
        echo '</div>';
    }
    
    /**
     * Render bulk import page
     */
    private function render_bulk_import_page() {
        // Placeholder for bulk import
        echo '<div class="wrap">';
        echo '<h1>' . __('Bulk Import Feeds', 'rss-content-planner') . '</h1>';
        echo '<p>' . __('Bulk import functionality will be implemented here.', 'rss-content-planner') . '</p>';
        echo '</div>';
    }
}
