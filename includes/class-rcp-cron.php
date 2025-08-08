<?php
/**
 * Cron Management Class
 *
 * @package RSSContentPlanner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RCP_Cron class for managing scheduled tasks
 */
class RCP_Cron {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rcp_fetch_feeds', [$this, 'fetch_feeds_cron']);
        add_filter('cron_schedules', [$this, 'add_custom_schedules']);
    }
    
    /**
     * Add custom cron schedules
     */
    public function add_custom_schedules($schedules) {
        $schedules['every_five_minutes'] = [
            'interval' => 300,
            'display' => __('Every 5 Minutes', 'rss-content-planner'),
        ];
        
        $schedules['every_fifteen_minutes'] = [
            'interval' => 900,
            'display' => __('Every 15 Minutes', 'rss-content-planner'),
        ];
        
        return $schedules;
    }
    
    /**
     * Fetch feeds cron job
     */
    public function fetch_feeds_cron() {
        $feed_manager = new RCP_Feed_Manager();
        $feed_manager->fetch_all_feeds();
    }
}
