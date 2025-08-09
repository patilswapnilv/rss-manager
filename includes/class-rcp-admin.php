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
        // Check if this is first time setup
        $is_setup_complete = get_option('rcp_setup_complete', false);
        
        if (!$is_setup_complete && !isset($_GET['skip_setup'])) {
            $this->render_onboarding_wizard();
            return;
        }
        
        $this->render_dashboard();
    }
    
    /**
     * Render onboarding wizard
     */
    private function render_onboarding_wizard() {
        ?>
        <div class="wrap rcp-onboarding">
            <div class="rcp-onboarding-container">
                <div class="rcp-onboarding-header">
                    <div class="rcp-logo">
                        <span class="dashicons dashicons-rss"></span>
                        <h1><?php _e('RSS Content Planner', 'rss-content-planner'); ?></h1>
                    </div>
                    <p class="rcp-tagline"><?php _e('Transform RSS feeds into powerful content workflows with AI automation', 'rss-content-planner'); ?></p>
                </div>

                <div class="rcp-wizard-progress">
                    <div class="rcp-progress-bar">
                        <div class="rcp-progress-fill" style="width: 0%"></div>
                    </div>
                    <div class="rcp-step-indicators">
                        <span class="rcp-step active" data-step="1">1</span>
                        <span class="rcp-step" data-step="2">2</span>
                        <span class="rcp-step" data-step="3">3</span>
                        <span class="rcp-step" data-step="4">4</span>
                        <span class="rcp-step" data-step="5">5</span>
                    </div>
                </div>

                <div class="rcp-wizard-content">
                    <!-- Step 1: Welcome -->
                    <div class="rcp-wizard-step active" data-step="1">
                        <div class="rcp-step-content">
                            <h2><?php _e('Welcome to RSS Content Planner!', 'rss-content-planner'); ?></h2>
                            <p><?php _e('This powerful plugin helps you aggregate RSS feeds, process content with AI workflows, and manage your content pipeline efficiently.', 'rss-content-planner'); ?></p>
                            
                            <div class="rcp-features-grid">
                                <div class="rcp-feature">
                                    <span class="dashicons dashicons-rss"></span>
                                    <h3><?php _e('Smart Feed Management', 'rss-content-planner'); ?></h3>
                                    <p><?php _e('Import multiple RSS feeds with advanced filtering and deduplication', 'rss-content-planner'); ?></p>
                                </div>
                                <div class="rcp-feature">
                                    <span class="dashicons dashicons-admin-settings"></span>
                                    <h3><?php _e('AI-Powered Workflows', 'rss-content-planner'); ?></h3>
                                    <p><?php _e('Connect with n8n to automate content rewriting, SEO optimization, and more', 'rss-content-planner'); ?></p>
                                </div>
                                <div class="rcp-feature">
                                    <span class="dashicons dashicons-analytics"></span>
                                    <h3><?php _e('Editorial Workflow', 'rss-content-planner'); ?></h3>
                                    <p><?php _e('Manage content through an organized inbox with status tracking', 'rss-content-planner'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Setup Mode -->
                    <div class="rcp-wizard-step" data-step="2">
                        <div class="rcp-step-content">
                            <h2><?php _e('Choose Your Setup', 'rss-content-planner'); ?></h2>
                            <p><?php _e('Select how you want to configure RSS Content Planner:', 'rss-content-planner'); ?></p>
                            
                            <div class="rcp-setup-options">
                                <div class="rcp-setup-option recommended" data-setup="guided">
                                    <div class="rcp-option-header">
                                        <span class="dashicons dashicons-admin-tools"></span>
                                        <h3><?php _e('Guided Setup', 'rss-content-planner'); ?></h3>
                                        <span class="rcp-recommended"><?php _e('Recommended', 'rss-content-planner'); ?></span>
                                    </div>
                                    <p><?php _e('We\'ll walk you through each step and help you configure everything properly.', 'rss-content-planner'); ?></p>
                                    <ul>
                                        <li><?php _e('Pre-configured templates', 'rss-content-planner'); ?></li>
                                        <li><?php _e('Sample feeds and workflows', 'rss-content-planner'); ?></li>
                                        <li><?php _e('Best practice settings', 'rss-content-planner'); ?></li>
                                    </ul>
                                </div>
                                
                                <div class="rcp-setup-option" data-setup="manual">
                                    <div class="rcp-option-header">
                                        <span class="dashicons dashicons-admin-generic"></span>
                                        <h3><?php _e('Manual Setup', 'rss-content-planner'); ?></h3>
                                    </div>
                                    <p><?php _e('Configure everything yourself with full control over all settings.', 'rss-content-planner'); ?></p>
                                    <ul>
                                        <li><?php _e('Complete customization', 'rss-content-planner'); ?></li>
                                        <li><?php _e('Advanced configuration options', 'rss-content-planner'); ?></li>
                                        <li><?php _e('For experienced users', 'rss-content-planner'); ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Sample Content -->
                    <div class="rcp-wizard-step" data-step="3">
                        <div class="rcp-step-content">
                            <h2><?php _e('Add Sample Feeds', 'rss-content-planner'); ?></h2>
                            <p><?php _e('Let\'s start with some popular RSS feeds to get you familiar with the system:', 'rss-content-planner'); ?></p>
                            
                            <div class="rcp-sample-feeds">
                                <div class="rcp-feed-category">
                                    <h3><?php _e('Technology', 'rss-content-planner'); ?></h3>
                                    <div class="rcp-feed-options">
                                        <label><input type="checkbox" value="https://techcrunch.com/feed/" checked> TechCrunch</label>
                                        <label><input type="checkbox" value="https://www.theverge.com/rss/index.xml"> The Verge</label>
                                        <label><input type="checkbox" value="https://feeds.arstechnica.com/arstechnica/index"> Ars Technica</label>
                                    </div>
                                </div>
                                
                                <div class="rcp-feed-category">
                                    <h3><?php _e('WordPress', 'rss-content-planner'); ?></h3>
                                    <div class="rcp-feed-options">
                                        <label><input type="checkbox" value="https://wordpress.org/news/feed/" checked> WordPress News</label>
                                        <label><input type="checkbox" value="https://wptavern.com/feed"> WP Tavern</label>
                                        <label><input type="checkbox" value="https://www.wpbeginner.com/feed/"> WPBeginner</label>
                                    </div>
                                </div>
                                
                                <div class="rcp-feed-category">
                                    <h3><?php _e('Business', 'rss-content-planner'); ?></h3>
                                    <div class="rcp-feed-options">
                                        <label><input type="checkbox" value="https://feeds.feedburner.com/entrepreneur/latest"> Entrepreneur</label>
                                        <label><input type="checkbox" value="https://feeds.harvard.edu/hbs"> Harvard Business Review</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="rcp-custom-feed">
                                <h4><?php _e('Add Custom Feed', 'rss-content-planner'); ?></h4>
                                <input type="url" placeholder="<?php _e('Enter RSS feed URL...', 'rss-content-planner'); ?>" id="rcp-custom-feed-url">
                                <button class="button" id="rcp-add-custom-feed"><?php _e('Add Feed', 'rss-content-planner'); ?></button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Workflow Setup -->
                    <div class="rcp-wizard-step" data-step="4">
                        <div class="rcp-step-content">
                            <h2><?php _e('Configure Workflows', 'rss-content-planner'); ?></h2>
                            <p><?php _e('Choose how you want to process your RSS content:', 'rss-content-planner'); ?></p>
                            
                            <div class="rcp-workflow-options">
                                <div class="rcp-workflow-option" data-workflow="basic">
                                    <h3><?php _e('Basic Processing', 'rss-content-planner'); ?></h3>
                                    <p><?php _e('Import feeds as-is with basic filtering and categorization.', 'rss-content-planner'); ?></p>
                                    <div class="rcp-workflow-features">
                                        <span class="rcp-feature-tag"><?php _e('Content Import', 'rss-content-planner'); ?></span>
                                        <span class="rcp-feature-tag"><?php _e('Auto Categorization', 'rss-content-planner'); ?></span>
                                        <span class="rcp-feature-tag"><?php _e('Duplicate Detection', 'rss-content-planner'); ?></span>
                                    </div>
                                </div>
                                
                                <div class="rcp-workflow-option recommended" data-workflow="n8n">
                                    <h3><?php _e('AI-Powered Workflows', 'rss-content-planner'); ?></h3>
                                    <span class="rcp-recommended"><?php _e('Recommended', 'rss-content-planner'); ?></span>
                                    <p><?php _e('Use n8n automation to rewrite, optimize, and enhance your content.', 'rss-content-planner'); ?></p>
                                    <div class="rcp-workflow-features">
                                        <span class="rcp-feature-tag"><?php _e('Content Rewriting', 'rss-content-planner'); ?></span>
                                        <span class="rcp-feature-tag"><?php _e('SEO Optimization', 'rss-content-planner'); ?></span>
                                        <span class="rcp-feature-tag"><?php _e('Custom Processing', 'rss-content-planner'); ?></span>
                                    </div>
                                    
                                    <div class="rcp-n8n-setup" style="display: none;">
                                        <div class="rcp-input-group">
                                            <label><?php _e('n8n Webhook URL:', 'rss-content-planner'); ?></label>
                                            <input type="url" placeholder="https://your-n8n-instance.com/webhook/..." id="rcp-n8n-webhook">
                                        </div>
                                        <div class="rcp-input-group">
                                            <label><?php _e('Authentication Token (Optional):', 'rss-content-planner'); ?></label>
                                            <input type="text" placeholder="Enter your webhook token..." id="rcp-n8n-token">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Complete -->
                    <div class="rcp-wizard-step" data-step="5">
                        <div class="rcp-step-content">
                            <div class="rcp-completion-message">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <h2><?php _e('Setup Complete!', 'rss-content-planner'); ?></h2>
                                <p><?php _e('Your RSS Content Planner is now configured and ready to use.', 'rss-content-planner'); ?></p>
                            </div>
                            
                            <div class="rcp-next-steps">
                                <h3><?php _e('What\'s Next?', 'rss-content-planner'); ?></h3>
                                <div class="rcp-next-step">
                                    <span class="dashicons dashicons-rss"></span>
                                    <div>
                                        <strong><?php _e('Monitor Your Feeds', 'rss-content-planner'); ?></strong>
                                        <p><?php _e('Your feeds will start fetching content automatically.', 'rss-content-planner'); ?></p>
                                    </div>
                                </div>
                                <div class="rcp-next-step">
                                    <span class="dashicons dashicons-admin-tools"></span>
                                    <div>
                                        <strong><?php _e('Set Up Rules', 'rss-content-planner'); ?></strong>
                                        <p><?php _e('Create processing rules to automate content handling.', 'rss-content-planner'); ?></p>
                                    </div>
                                </div>
                                <div class="rcp-next-step">
                                    <span class="dashicons dashicons-analytics"></span>
                                    <div>
                                        <strong><?php _e('Review Content', 'rss-content-planner'); ?></strong>
                                        <p><?php _e('Use the inbox to manage and review processed content.', 'rss-content-planner'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rcp-wizard-navigation">
                    <button class="button" id="rcp-wizard-prev" style="display: none;"><?php _e('Previous', 'rss-content-planner'); ?></button>
                    <div class="rcp-wizard-skip">
                        <a href="<?php echo add_query_arg('skip_setup', '1'); ?>"><?php _e('Skip Setup', 'rss-content-planner'); ?></a>
                    </div>
                    <button class="button button-primary" id="rcp-wizard-next"><?php _e('Get Started', 'rss-content-planner'); ?></button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let currentStep = 1;
            const totalSteps = 5;
            
            function updateProgress() {
                const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
                $('.rcp-progress-fill').css('width', progress + '%');
                
                $('.rcp-step').removeClass('active completed');
                for(let i = 1; i < currentStep; i++) {
                    $(`.rcp-step[data-step="${i}"]`).addClass('completed');
                }
                $(`.rcp-step[data-step="${currentStep}"]`).addClass('active');
            }
            
            function showStep(step) {
                $('.rcp-wizard-step').removeClass('active');
                $(`.rcp-wizard-step[data-step="${step}"]`).addClass('active');
                
                // Update navigation
                $('#rcp-wizard-prev').toggle(step > 1);
                
                if (step === totalSteps) {
                    $('#rcp-wizard-next').text('<?php _e('Complete Setup', 'rss-content-planner'); ?>');
                } else if (step === 1) {
                    $('#rcp-wizard-next').text('<?php _e('Get Started', 'rss-content-planner'); ?>');
                } else {
                    $('#rcp-wizard-next').text('<?php _e('Continue', 'rss-content-planner'); ?>');
                }
                
                updateProgress();
            }
            
            $('#rcp-wizard-next').on('click', function() {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                } else {
                    // Complete setup
                    $.post(ajaxurl, {
                        action: 'rcp_complete_setup',
                        nonce: '<?php echo wp_create_nonce('rcp_setup'); ?>',
                        feeds: JSON.stringify($('.rcp-sample-feeds input:checked').map(function() { return this.value; }).get()),
                        workflow: $('.rcp-workflow-option.selected').data('workflow') || 'basic',
                        n8n_webhook: $('#rcp-n8n-webhook').val(),
                        n8n_token: $('#rcp-n8n-token').val()
                    }, function() {
                        window.location.reload();
                    });
                }
            });
            
            $('#rcp-wizard-prev').on('click', function() {
                if (currentStep > 1) {
                    currentStep--;
                    showStep(currentStep);
                }
            });
            
            // Handle setup option selection
            $('.rcp-setup-option').on('click', function() {
                $('.rcp-setup-option').removeClass('selected');
                $(this).addClass('selected');
            });
            
            // Handle workflow selection
            $('.rcp-workflow-option').on('click', function() {
                $('.rcp-workflow-option').removeClass('selected');
                $(this).addClass('selected');
                
                if ($(this).data('workflow') === 'n8n') {
                    $('.rcp-n8n-setup').show();
                } else {
                    $('.rcp-n8n-setup').hide();
                }
            });
            
            // Auto-select recommended options
            $('.rcp-setup-option.recommended').addClass('selected');
            $('.rcp-workflow-option.recommended').addClass('selected');
            $('.rcp-n8n-setup').show();
        });
        </script>
        <?php
    }
    
    /**
     * Render enhanced dashboard
     */
    private function render_dashboard() {
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="wrap rcp-dashboard">
            <div class="rcp-dashboard-header">
                <h1><?php _e('RSS Content Planner Dashboard', 'rss-content-planner'); ?></h1>
                <div class="rcp-header-actions">
                    <a href="<?php echo admin_url('admin.php?page=rcp-feeds&action=add'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span> <?php _e('Add Feed', 'rss-content-planner'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=rcp-workflows'); ?>" class="button">
                        <span class="dashicons dashicons-admin-settings"></span> <?php _e('Workflows', 'rss-content-planner'); ?>
                    </a>
                </div>
            </div>

            <div class="rcp-dashboard-stats">
                <div class="rcp-stat-card">
                    <div class="rcp-stat-icon">
                        <span class="dashicons dashicons-rss"></span>
                    </div>
                    <div class="rcp-stat-content">
                        <h3><?php echo number_format($stats['total_feeds']); ?></h3>
                        <p><?php _e('Active Feeds', 'rss-content-planner'); ?></p>
                        <span class="rcp-stat-change <?php echo $stats['feeds_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $stats['feeds_change'] >= 0 ? '+' : ''; ?><?php echo $stats['feeds_change']; ?>% <?php _e('this week', 'rss-content-planner'); ?>
                        </span>
                    </div>
                </div>

                <div class="rcp-stat-card">
                    <div class="rcp-stat-icon">
                        <span class="dashicons dashicons-admin-post"></span>
                    </div>
                    <div class="rcp-stat-content">
                        <h3><?php echo number_format($stats['total_items']); ?></h3>
                        <p><?php _e('Content Items', 'rss-content-planner'); ?></p>
                        <span class="rcp-stat-change positive">
                            +<?php echo $stats['items_today']; ?> <?php _e('today', 'rss-content-planner'); ?>
                        </span>
                    </div>
                </div>

                <div class="rcp-stat-card">
                    <div class="rcp-stat-icon">
                        <span class="dashicons dashicons-admin-tools"></span>
                    </div>
                    <div class="rcp-stat-content">
                        <h3><?php echo number_format($stats['total_workflows']); ?></h3>
                        <p><?php _e('Active Workflows', 'rss-content-planner'); ?></p>
                        <span class="rcp-stat-change neutral">
                            <?php echo $stats['workflow_executions']; ?> <?php _e('executions today', 'rss-content-planner'); ?>
                        </span>
                    </div>
                </div>

                <div class="rcp-stat-card">
                    <div class="rcp-stat-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="rcp-stat-content">
                        <h3><?php echo number_format($stats['processed_items']); ?></h3>
                        <p><?php _e('Processed Today', 'rss-content-planner'); ?></p>
                        <span class="rcp-stat-change <?php echo $stats['success_rate'] >= 90 ? 'positive' : ($stats['success_rate'] >= 70 ? 'neutral' : 'negative'); ?>">
                            <?php echo $stats['success_rate']; ?>% <?php _e('success rate', 'rss-content-planner'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="rcp-dashboard-grid">
                <div class="rcp-dashboard-main">
                    <!-- Recent Activity -->
                    <div class="rcp-widget">
                        <div class="rcp-widget-header">
                            <h2><?php _e('Recent Activity', 'rss-content-planner'); ?></h2>
                            <a href="<?php echo admin_url('admin.php?page=rcp-inbox'); ?>" class="rcp-widget-action"><?php _e('View All', 'rss-content-planner'); ?></a>
                        </div>
                        <div class="rcp-widget-content">
                            <?php $this->render_recent_activity(); ?>
                        </div>
                    </div>

                    <!-- Feed Performance -->
                    <div class="rcp-widget">
                        <div class="rcp-widget-header">
                            <h2><?php _e('Feed Performance', 'rss-content-planner'); ?></h2>
                            <select id="rcp-performance-period">
                                <option value="7"><?php _e('Last 7 days', 'rss-content-planner'); ?></option>
                                <option value="30"><?php _e('Last 30 days', 'rss-content-planner'); ?></option>
                                <option value="90"><?php _e('Last 90 days', 'rss-content-planner'); ?></option>
                            </select>
                        </div>
                        <div class="rcp-widget-content">
                            <canvas id="rcp-performance-chart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <div class="rcp-dashboard-sidebar">
                    <!-- Quick Actions -->
                    <div class="rcp-widget">
                        <div class="rcp-widget-header">
                            <h2><?php _e('Quick Actions', 'rss-content-planner'); ?></h2>
                        </div>
                        <div class="rcp-widget-content">
                            <div class="rcp-quick-actions">
                                <a href="<?php echo admin_url('admin.php?page=rcp-feeds&action=add'); ?>" class="rcp-quick-action">
                                    <span class="dashicons dashicons-plus-alt"></span>
                                    <span><?php _e('Add New Feed', 'rss-content-planner'); ?></span>
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=rcp-feeds&action=bulk-import'); ?>" class="rcp-quick-action">
                                    <span class="dashicons dashicons-upload"></span>
                                    <span><?php _e('Bulk Import Feeds', 'rss-content-planner'); ?></span>
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=rcp-workflows&action=add'); ?>" class="rcp-quick-action">
                                    <span class="dashicons dashicons-admin-settings"></span>
                                    <span><?php _e('Create Workflow', 'rss-content-planner'); ?></span>
                                </a>
                                <a href="#" id="rcp-fetch-all-feeds" class="rcp-quick-action">
                                    <span class="dashicons dashicons-update"></span>
                                    <span><?php _e('Fetch All Feeds', 'rss-content-planner'); ?></span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- System Status -->
                    <div class="rcp-widget">
                        <div class="rcp-widget-header">
                            <h2><?php _e('System Status', 'rss-content-planner'); ?></h2>
                        </div>
                        <div class="rcp-widget-content">
                            <?php $this->render_system_status(); ?>
                        </div>
                    </div>

                    <!-- Workflow Templates -->
                    <div class="rcp-widget">
                        <div class="rcp-widget-header">
                            <h2><?php _e('Workflow Templates', 'rss-content-planner'); ?></h2>
                        </div>
                        <div class="rcp-widget-content">
                            <div class="rcp-template-list">
                                <div class="rcp-template-item">
                                    <h4><?php _e('Content Rewriter', 'rss-content-planner'); ?></h4>
                                    <p><?php _e('AI-powered content rewriting with SEO optimization', 'rss-content-planner'); ?></p>
                                    <button class="button button-small rcp-use-template" data-template="rewriter"><?php _e('Use Template', 'rss-content-planner'); ?></button>
                                </div>
                                <div class="rcp-template-item">
                                    <h4><?php _e('Multi-language', 'rss-content-planner'); ?></h4>
                                    <p><?php _e('Translate content into multiple languages', 'rss-content-planner'); ?></p>
                                    <button class="button button-small rcp-use-template" data-template="translator"><?php _e('Use Template', 'rss-content-planner'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        $stats = [
            'total_feeds' => 0,
            'feeds_change' => 0,
            'total_items' => 0,
            'items_today' => 0,
            'total_workflows' => 0,
            'workflow_executions' => 0,
            'processed_items' => 0,
            'success_rate' => 100
        ];
        
        // Get feed stats
        $stats['total_feeds'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rcp_feeds WHERE status = 'active'");
        
        // Get content items stats
        $stats['total_items'] = wp_count_posts('rss_item')->publish + wp_count_posts('rss_item')->pending;
        $stats['items_today'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'rss_item' AND DATE(post_date) = %s",
            current_time('Y-m-d')
        ));
        
        // Get workflow stats
        $stats['total_workflows'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rcp_webhooks WHERE status = 'active'");
        $stats['workflow_executions'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rcp_executions WHERE DATE(created_at) = %s",
            current_time('Y-m-d')
        ));
        
        // Calculate success rate
        $total_executions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rcp_executions WHERE DATE(created_at) = %s",
            current_time('Y-m-d')
        ));
        
        if ($total_executions > 0) {
            $successful_executions = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rcp_executions WHERE DATE(created_at) = %s AND status = 'success'",
                current_time('Y-m-d')
            ));
            $stats['success_rate'] = round(($successful_executions / $total_executions) * 100);
        }
        
        $stats['processed_items'] = $stats['workflow_executions'];
        
        return $stats;
    }
    
    /**
     * Render recent activity widget
     */
    private function render_recent_activity() {
        global $wpdb;
        
        $activities = $wpdb->get_results($wpdb->prepare("
            SELECT l.*, f.name as feed_name 
            FROM {$wpdb->prefix}rcp_logs l
            LEFT JOIN {$wpdb->prefix}rcp_feeds f ON l.feed_id = f.id
            WHERE l.created_at >= %s
            ORDER BY l.created_at DESC 
            LIMIT 10
        ", date('Y-m-d H:i:s', strtotime('-24 hours'))));
        
        if (empty($activities)) {
            echo '<p class="rcp-no-activity">' . __('No recent activity', 'rss-content-planner') . '</p>';
            return;
        }
        
        echo '<div class="rcp-activity-list">';
        foreach ($activities as $activity) {
            $time_diff = human_time_diff(strtotime($activity->created_at));
            $icon = $this->get_activity_icon($activity->level);
            $class = 'rcp-activity-' . $activity->level;
            
            echo '<div class="rcp-activity-item ' . esc_attr($class) . '">';
            echo '<span class="rcp-activity-icon dashicons ' . esc_attr($icon) . '"></span>';
            echo '<div class="rcp-activity-content">';
            echo '<p>' . esc_html($activity->message) . '</p>';
            if ($activity->feed_name) {
                echo '<span class="rcp-activity-meta">' . esc_html($activity->feed_name) . ' • ' . $time_diff . ' ago</span>';
            } else {
                echo '<span class="rcp-activity-meta">' . $time_diff . ' ago</span>';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    /**
     * Get activity icon based on log level
     */
    private function get_activity_icon($level) {
        switch ($level) {
            case 'error':
                return 'dashicons-warning';
            case 'success':
                return 'dashicons-yes-alt';
            case 'info':
                return 'dashicons-info';
            default:
                return 'dashicons-admin-generic';
        }
    }
    
    /**
     * Render system status widget
     */
    private function render_system_status() {
        $status_items = [
            [
                'label' => __('WordPress Cron', 'rss-content-planner'),
                'status' => wp_next_scheduled('rcp_fetch_feeds') ? 'good' : 'warning',
                'message' => wp_next_scheduled('rcp_fetch_feeds') ? __('Running normally', 'rss-content-planner') : __('Not scheduled', 'rss-content-planner')
            ],
            [
                'label' => __('SimplePie Library', 'rss-content-planner'),
                'status' => class_exists('SimplePie') ? 'good' : 'error',
                'message' => class_exists('SimplePie') ? __('Available', 'rss-content-planner') : __('Not available', 'rss-content-planner')
            ],
            [
                'label' => __('cURL Support', 'rss-content-planner'),
                'status' => function_exists('curl_version') ? 'good' : 'warning',
                'message' => function_exists('curl_version') ? __('Available', 'rss-content-planner') : __('Not available', 'rss-content-planner')
            ],
            [
                'label' => __('Database Tables', 'rss-content-planner'),
                'status' => $this->check_database_tables() ? 'good' : 'error',
                'message' => $this->check_database_tables() ? __('All tables exist', 'rss-content-planner') : __('Missing tables', 'rss-content-planner')
            ]
        ];
        
        echo '<div class="rcp-status-list">';
        foreach ($status_items as $item) {
            $icon = $this->get_status_icon($item['status']);
            echo '<div class="rcp-status-item rcp-status-' . esc_attr($item['status']) . '">';
            echo '<span class="rcp-status-icon dashicons ' . esc_attr($icon) . '"></span>';
            echo '<div class="rcp-status-content">';
            echo '<strong>' . esc_html($item['label']) . '</strong>';
            echo '<span>' . esc_html($item['message']) . '</span>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    /**
     * Get status icon
     */
    private function get_status_icon($status) {
        switch ($status) {
            case 'good':
                return 'dashicons-yes-alt';
            case 'warning':
                return 'dashicons-warning';
            case 'error':
                return 'dashicons-dismiss';
            default:
                return 'dashicons-admin-generic';
        }
    }
    
    /**
     * Check if database tables exist
     */
    private function check_database_tables() {
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
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                return false;
            }
        }
        
        return true;
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
        $active_tab = $_GET['tab'] ?? 'general';
        
        ?>
        <div class="wrap rcp-settings">
            <div class="rcp-settings-header">
                <h1><?php _e('RSS Content Planner Settings', 'rss-content-planner'); ?></h1>
                <div class="rcp-settings-nav">
                    <nav class="nav-tab-wrapper">
                        <a href="?page=rcp-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                            <span class="dashicons dashicons-admin-generic"></span> <?php _e('General', 'rss-content-planner'); ?>
                        </a>
                        <a href="?page=rcp-settings&tab=feeds" class="nav-tab <?php echo $active_tab === 'feeds' ? 'nav-tab-active' : ''; ?>">
                            <span class="dashicons dashicons-rss"></span> <?php _e('Feed Processing', 'rss-content-planner'); ?>
                        </a>
                        <a href="?page=rcp-settings&tab=ai" class="nav-tab <?php echo $active_tab === 'ai' ? 'nav-tab-active' : ''; ?>">
                            <span class="dashicons dashicons-admin-settings"></span> <?php _e('AI & Automation', 'rss-content-planner'); ?>
                        </a>
                        <a href="?page=rcp-settings&tab=performance" class="nav-tab <?php echo $active_tab === 'performance' ? 'nav-tab-active' : ''; ?>">
                            <span class="dashicons dashicons-performance"></span> <?php _e('Performance', 'rss-content-planner'); ?>
                        </a>
                        <a href="?page=rcp-settings&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                            <span class="dashicons dashicons-admin-tools"></span> <?php _e('Advanced', 'rss-content-planner'); ?>
                        </a>
                    </nav>
                </div>
            </div>

            <div class="rcp-settings-content">
                <form method="post" action="options.php" class="rcp-settings-form">
                    <?php settings_fields('rcp_settings_group'); ?>
                    
                    <?php
                    switch ($active_tab) {
                        case 'general':
                            $this->render_general_settings($current_settings);
                            break;
                        case 'feeds':
                            $this->render_feed_settings($current_settings);
                            break;
                        case 'ai':
                            $this->render_ai_settings($current_settings);
                            break;
                        case 'performance':
                            $this->render_performance_settings($current_settings);
                            break;
                        case 'advanced':
                            $this->render_advanced_settings($current_settings);
                            break;
                        default:
                            $this->render_general_settings($current_settings);
                    }
                    ?>
                    
                    <div class="rcp-settings-actions">
                        <?php submit_button(__('Save Settings', 'rss-content-planner'), 'primary', 'submit', false, ['class' => 'button-large']); ?>
                        <button type="button" id="rcp-reset-settings" class="button button-large">
                            <?php _e('Reset to Defaults', 'rss-content-planner'); ?>
                        </button>
                        <button type="button" id="rcp-export-settings" class="button button-large">
                            <?php _e('Export Settings', 'rss-content-planner'); ?>
                        </button>
                        <button type="button" id="rcp-import-settings" class="button button-large">
                            <?php _e('Import Settings', 'rss-content-planner'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Settings navigation
            $('.nav-tab').on('click', function(e) {
                if ($(this).hasClass('nav-tab-active')) {
                    e.preventDefault();
                }
            });
            
            // Reset settings
            $('#rcp-reset-settings').on('click', function() {
                if (confirm('<?php _e('Are you sure you want to reset all settings to defaults?', 'rss-content-planner'); ?>')) {
                    $.post(ajaxurl, {
                        action: 'rcp_reset_settings',
                        nonce: '<?php echo wp_create_nonce('rcp_reset_settings'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    });
                }
            });
            
            // Export settings
            $('#rcp-export-settings').on('click', function() {
                const settings = <?php echo json_encode($current_settings); ?>;
                const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(settings, null, 2));
                const downloadAnchorNode = document.createElement('a');
                downloadAnchorNode.setAttribute("href", dataStr);
                downloadAnchorNode.setAttribute("download", "rcp-settings-" + new Date().toISOString().split('T')[0] + ".json");
                document.body.appendChild(downloadAnchorNode);
                downloadAnchorNode.click();
                downloadAnchorNode.remove();
            });
            
            // Import settings
            $('#rcp-import-settings').on('click', function() {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = '.json';
                input.onchange = function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            try {
                                const settings = JSON.parse(e.target.result);
                                if (confirm('<?php _e('Import these settings? This will overwrite current settings.', 'rss-content-planner'); ?>')) {
                                    $.post(ajaxurl, {
                                        action: 'rcp_import_settings',
                                        nonce: '<?php echo wp_create_nonce('rcp_import_settings'); ?>',
                                        settings: JSON.stringify(settings)
                                    }, function(response) {
                                        if (response.success) {
                                            location.reload();
                                        } else {
                                            alert(response.data.message);
                                        }
                                    });
                                }
                            } catch (error) {
                                alert('<?php _e('Invalid settings file', 'rss-content-planner'); ?>');
                            }
                        };
                        reader.readAsText(file);
                    }
                };
                input.click();
            });
        });
        </script>
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
        ?>
        <div class="wrap rcp-add-feed">
            <div class="rcp-page-header">
                <h1><?php _e('Add New RSS Feed', 'rss-content-planner'); ?></h1>
                <a href="<?php echo admin_url('admin.php?page=rcp-feeds'); ?>" class="button">
                    <span class="dashicons dashicons-arrow-left-alt2"></span> <?php _e('Back to Feeds', 'rss-content-planner'); ?>
                </a>
            </div>

            <div class="rcp-form-container">
                <form id="rcp-add-feed-form" method="post">
                    <?php wp_nonce_field('rcp_add_feed', 'rcp_feed_nonce'); ?>
                    
                    <div class="rcp-form-grid">
                        <div class="rcp-form-main">
                            <?php $this->render_feed_form_fields(); ?>
                        </div>
                        <div class="rcp-form-sidebar">
                            <?php $this->render_feed_form_sidebar(); ?>
                        </div>
                    </div>

                    <div class="rcp-form-actions">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-plus-alt"></span> <?php _e('Add Feed', 'rss-content-planner'); ?>
                        </button>
                        <button type="button" id="rcp-save-and-test" class="button button-large">
                            <span class="dashicons dashicons-update"></span> <?php _e('Save & Test', 'rss-content-planner'); ?>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=rcp-feeds'); ?>" class="button button-link">
                            <?php _e('Cancel', 'rss-content-planner'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php
        $this->render_feed_form_scripts();
    }
    
    /**
     * Render edit feed page
     */
    private function render_edit_feed_page($feed_id) {
        global $wpdb;
        $feed = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rcp_feeds WHERE id = %d", $feed_id));
        
        if (!$feed) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . __('Feed not found.', 'rss-content-planner') . '</p></div></div>';
            return;
        }
        ?>
        <div class="wrap rcp-edit-feed">
            <div class="rcp-page-header">
                <h1><?php echo sprintf(__('Edit Feed: %s', 'rss-content-planner'), esc_html($feed->name)); ?></h1>
                <a href="<?php echo admin_url('admin.php?page=rcp-feeds'); ?>" class="button">
                    <span class="dashicons dashicons-arrow-left-alt2"></span> <?php _e('Back to Feeds', 'rss-content-planner'); ?>
                </a>
            </div>

            <div class="rcp-form-container">
                <form id="rcp-edit-feed-form" method="post">
                    <?php wp_nonce_field('rcp_edit_feed', 'rcp_feed_nonce'); ?>
                    <input type="hidden" name="feed_id" value="<?php echo esc_attr($feed->id); ?>">
                    
                    <div class="rcp-form-grid">
                        <div class="rcp-form-main">
                            <?php $this->render_feed_form_fields($feed); ?>
                        </div>
                        <div class="rcp-form-sidebar">
                            <?php $this->render_feed_form_sidebar(); ?>
                            
                            <div class="rcp-form-section">
                                <h3><?php _e('Feed Statistics', 'rss-content-planner'); ?></h3>
                                <div class="rcp-feed-stats">
                                    <div class="rcp-stat-item">
                                        <strong><?php _e('Last Fetch:', 'rss-content-planner'); ?></strong>
                                        <span><?php echo $feed->last_fetched ? human_time_diff(strtotime($feed->last_fetched)) . ' ago' : __('Never', 'rss-content-planner'); ?></span>
                                    </div>
                                    <div class="rcp-stat-item">
                                        <strong><?php _e('Total Items:', 'rss-content-planner'); ?></strong>
                                        <span><?php echo $this->get_feed_item_count($feed->id); ?></span>
                                    </div>
                                    <div class="rcp-stat-item">
                                        <strong><?php _e('Status:', 'rss-content-planner'); ?></strong>
                                        <span class="rcp-status-badge <?php echo esc_attr($feed->status); ?>"><?php echo esc_html(ucfirst($feed->status)); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rcp-form-actions">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-update"></span> <?php _e('Update Feed', 'rss-content-planner'); ?>
                        </button>
                        <button type="button" id="rcp-fetch-now" class="button button-large" data-feed-id="<?php echo esc_attr($feed->id); ?>">
                            <span class="dashicons dashicons-update"></span> <?php _e('Fetch Now', 'rss-content-planner'); ?>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=rcp-feeds'); ?>" class="button button-link">
                            <?php _e('Cancel', 'rss-content-planner'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php
        $this->render_feed_form_scripts();
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
    
    /**
     * Render feed form fields
     */
    private function render_feed_form_fields($feed = null) {
        ?>
        <div class="rcp-form-section">
            <h2><?php _e('Feed Information', 'rss-content-planner'); ?></h2>
            
            <div class="rcp-form-row">
                <label for="feed_url" class="rcp-label">
                    <?php _e('RSS Feed URL', 'rss-content-planner'); ?> <span class="required">*</span>
                </label>
                <div class="rcp-input-group">
                    <input type="url" id="feed_url" name="feed_url" class="rcp-input" required 
                           value="<?php echo esc_attr($feed->url ?? ''); ?>"
                           placeholder="https://example.com/feed.xml">
                    <button type="button" id="rcp-test-feed-btn" class="button">
                        <span class="dashicons dashicons-update"></span> <?php _e('Test Feed', 'rss-content-planner'); ?>
                    </button>
                </div>
                <div id="rcp-feed-test-result"></div>
            </div>

            <div class="rcp-form-row">
                <label for="feed_name" class="rcp-label">
                    <?php _e('Feed Name', 'rss-content-planner'); ?> <span class="required">*</span>
                </label>
                <input type="text" id="feed_name" name="feed_name" class="rcp-input" required 
                       value="<?php echo esc_attr($feed->name ?? ''); ?>"
                       placeholder="<?php _e('My RSS Feed', 'rss-content-planner'); ?>">
            </div>

            <div class="rcp-form-row">
                <label for="feed_description" class="rcp-label">
                    <?php _e('Description', 'rss-content-planner'); ?>
                </label>
                <textarea id="feed_description" name="feed_description" class="rcp-textarea" rows="3"
                          placeholder="<?php _e('Brief description...', 'rss-content-planner'); ?>"><?php echo esc_textarea($feed->description ?? ''); ?></textarea>
            </div>
        </div>

        <div class="rcp-form-section">
            <h2><?php _e('Fetch Settings', 'rss-content-planner'); ?></h2>
            
            <div class="rcp-form-row">
                <label for="fetch_frequency" class="rcp-label">
                    <?php _e('Fetch Frequency', 'rss-content-planner'); ?>
                </label>
                <select id="fetch_frequency" name="fetch_frequency" class="rcp-select">
                    <option value="900" <?php selected($feed->fetch_frequency ?? 3600, 900); ?>><?php _e('Every 15 minutes', 'rss-content-planner'); ?></option>
                    <option value="1800" <?php selected($feed->fetch_frequency ?? 3600, 1800); ?>><?php _e('Every 30 minutes', 'rss-content-planner'); ?></option>
                    <option value="3600" <?php selected($feed->fetch_frequency ?? 3600, 3600); ?>><?php _e('Every hour', 'rss-content-planner'); ?></option>
                    <option value="7200" <?php selected($feed->fetch_frequency ?? 3600, 7200); ?>><?php _e('Every 2 hours', 'rss-content-planner'); ?></option>
                    <option value="21600" <?php selected($feed->fetch_frequency ?? 3600, 21600); ?>><?php _e('Every 6 hours', 'rss-content-planner'); ?></option>
                    <option value="86400" <?php selected($feed->fetch_frequency ?? 3600, 86400); ?>><?php _e('Daily', 'rss-content-planner'); ?></option>
                </select>
            </div>

            <div class="rcp-form-row">
                <label for="max_items" class="rcp-label">
                    <?php _e('Max Items per Fetch', 'rss-content-planner'); ?>
                </label>
                <input type="number" id="max_items" name="max_items" class="rcp-input" 
                       value="<?php echo esc_attr($feed->max_items ?? 50); ?>" min="1" max="200">
            </div>

            <div class="rcp-form-row">
                <label class="rcp-checkbox-label">
                    <input type="checkbox" id="auto_fetch" name="auto_fetch" value="1" 
                           <?php checked($feed->status ?? 'active', 'active'); ?>>
                    <span class="rcp-checkmark"></span>
                    <?php _e('Enable automatic fetching', 'rss-content-planner'); ?>
                </label>
            </div>
        </div>

        <div class="rcp-form-section">
            <h2><?php _e('Content Processing', 'rss-content-planner'); ?></h2>
            
            <div class="rcp-form-row">
                <label for="default_author" class="rcp-label">
                    <?php _e('Default Author', 'rss-content-planner'); ?>
                </label>
                <?php
                wp_dropdown_users([
                    'name' => 'default_author',
                    'id' => 'default_author',
                    'class' => 'rcp-select',
                    'selected' => $feed->default_author ?? get_current_user_id(),
                    'show_option_none' => __('Use feed author', 'rss-content-planner')
                ]);
                ?>
            </div>

            <div class="rcp-form-row">
                <label for="default_status" class="rcp-label">
                    <?php _e('Default Post Status', 'rss-content-planner'); ?>
                </label>
                <select id="default_status" name="default_status" class="rcp-select">
                    <option value="pending" <?php selected($feed->default_status ?? 'pending', 'pending'); ?>><?php _e('Pending Review', 'rss-content-planner'); ?></option>
                    <option value="draft" <?php selected($feed->default_status ?? 'pending', 'draft'); ?>><?php _e('Draft', 'rss-content-planner'); ?></option>
                    <option value="publish" <?php selected($feed->default_status ?? 'pending', 'publish'); ?>><?php _e('Published', 'rss-content-planner'); ?></option>
                </select>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render feed form sidebar
     */
    private function render_feed_form_sidebar() {
        ?>
        <div class="rcp-form-section">
            <h3><?php _e('Feed Preview', 'rss-content-planner'); ?></h3>
            <div id="rcp-feed-preview" class="rcp-feed-preview">
                <p class="rcp-no-preview"><?php _e('Test the feed URL to see a preview', 'rss-content-planner'); ?></p>
            </div>
        </div>

        <div class="rcp-form-section">
            <h3><?php _e('Quick Setup', 'rss-content-planner'); ?></h3>
            <div class="rcp-quick-setup">
                <button type="button" class="rcp-quick-btn" data-preset="news">
                    <span class="dashicons dashicons-media-document"></span>
                    <?php _e('News Site', 'rss-content-planner'); ?>
                </button>
                <button type="button" class="rcp-quick-btn" data-preset="blog">
                    <span class="dashicons dashicons-admin-post"></span>
                    <?php _e('Blog', 'rss-content-planner'); ?>
                </button>
                <button type="button" class="rcp-quick-btn" data-preset="ecommerce">
                    <span class="dashicons dashicons-store"></span>
                    <?php _e('E-commerce', 'rss-content-planner'); ?>
                </button>
            </div>
        </div>

        <div class="rcp-form-section">
            <h3><?php _e('Tips', 'rss-content-planner'); ?></h3>
            <div class="rcp-tips">
                <div class="rcp-tip">
                    <span class="dashicons dashicons-lightbulb"></span>
                    <p><?php _e('Use "Test Feed" to validate the RSS URL before saving', 'rss-content-planner'); ?></p>
                </div>
                <div class="rcp-tip">
                    <span class="dashicons dashicons-lightbulb"></span>
                    <p><?php _e('Set to "Pending Review" to manually approve content', 'rss-content-planner'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render feed form scripts
     */
    private function render_feed_form_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Quick setup presets
            $('.rcp-quick-btn').on('click', function() {
                const preset = $(this).data('preset');
                const presets = {
                    news: { fetch_frequency: 1800, max_items: 20, default_status: 'pending' },
                    blog: { fetch_frequency: 3600, max_items: 50, default_status: 'draft' },
                    ecommerce: { fetch_frequency: 7200, max_items: 10, default_status: 'pending' }
                };
                
                if (presets[preset]) {
                    Object.keys(presets[preset]).forEach(key => {
                        $(`#${key}`).val(presets[preset][key]);
                    });
                    showNotification('<?php _e('Preset applied successfully', 'rss-content-planner'); ?>', 'success');
                }
            });

            // Auto-populate feed name from URL
            $('#feed_url').on('blur', function() {
                if ($(this).val() && !$('#feed_name').val()) {
                    autoTestFeed();
                }
            });

            // Form submission
            $('#rcp-add-feed-form').on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'rcp_add_feed');
                formData.append('nonce', rcpAdmin.nonce);
                
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.html('<span class="dashicons dashicons-update rcp-spin"></span> <?php _e('Adding...', 'rss-content-planner'); ?>').prop('disabled', true);
                
                $.post(rcpAdmin.ajax_url, formData, function(response) {
                    if (response.success) {
                        showNotification(response.data.message, 'success');
                        setTimeout(() => {
                            window.location.href = '<?php echo admin_url('admin.php?page=rcp-feeds'); ?>';
                        }, 1500);
                    } else {
                        showNotification(response.data.message || '<?php _e('Error adding feed', 'rss-content-planner'); ?>', 'error');
                        submitBtn.html(originalText).prop('disabled', false);
                    }
                }).fail(function() {
                    showNotification('<?php _e('Network error. Please try again.', 'rss-content-planner'); ?>', 'error');
                    submitBtn.html(originalText).prop('disabled', false);
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render general settings tab
     */
    private function render_general_settings($settings) {
        ?>
        <div class="rcp-settings-section">
            <h2><?php _e('General Settings', 'rss-content-planner'); ?></h2>
            <p class="description"><?php _e('Configure basic plugin behavior and default settings.', 'rss-content-planner'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Processing Mode', 'rss-content-planner'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="rcp_settings[processing_mode]" value="n8n" 
                                       <?php checked($settings['processing_mode'], 'n8n'); ?>>
                                <?php _e('n8n Workflows (Recommended)', 'rss-content-planner'); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="rcp_settings[processing_mode]" value="direct_api" 
                                       <?php checked($settings['processing_mode'], 'direct_api'); ?>>
                                <?php _e('Direct API Integration', 'rss-content-planner'); ?>
                            </label>
                            <p class="description"><?php _e('Choose how content should be processed. n8n workflows provide more flexibility and easier setup.', 'rss-content-planner'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Default Author', 'rss-content-planner'); ?></th>
                    <td>
                        <?php
                        wp_dropdown_users([
                            'name' => 'rcp_settings[default_author]',
                            'selected' => $settings['default_author'] ?? get_current_user_id(),
                            'show_option_none' => __('Use feed author when available', 'rss-content-planner')
                        ]);
                        ?>
                        <p class="description"><?php _e('Default author for imported content when feed author is not available.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Default Post Status', 'rss-content-planner'); ?></th>
                    <td>
                        <select name="rcp_settings[default_post_status]">
                            <option value="pending" <?php selected($settings['default_post_status'] ?? 'pending', 'pending'); ?>><?php _e('Pending Review', 'rss-content-planner'); ?></option>
                            <option value="draft" <?php selected($settings['default_post_status'] ?? 'pending', 'draft'); ?>><?php _e('Draft', 'rss-content-planner'); ?></option>
                            <option value="publish" <?php selected($settings['default_post_status'] ?? 'pending', 'publish'); ?>><?php _e('Published', 'rss-content-planner'); ?></option>
                        </select>
                        <p class="description"><?php _e('Default status for imported content.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render feed settings tab
     */
    private function render_feed_settings($settings) {
        ?>
        <div class="rcp-settings-section">
            <h2><?php _e('Feed Processing Settings', 'rss-content-planner'); ?></h2>
            <p class="description"><?php _e('Configure how RSS feeds are fetched and processed.', 'rss-content-planner'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Max Items Per Fetch', 'rss-content-planner'); ?></th>
                    <td>
                        <input type="number" name="rcp_settings[max_items_per_fetch]" 
                               value="<?php echo esc_attr($settings['max_items_per_fetch']); ?>" 
                               min="1" max="500" class="small-text">
                        <p class="description"><?php _e('Maximum number of items to process per feed fetch. Higher numbers may impact performance.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Download Media', 'rss-content-planner'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="rcp_settings[download_media]" value="1" 
                                   <?php checked($settings['download_media']); ?>>
                            <?php _e('Download and store media files locally', 'rss-content-planner'); ?>
                        </label>
                        <p class="description"><?php _e('Downloads images and media from feeds to your media library. Requires more storage space.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Content Sanitization', 'rss-content-planner'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="rcp_settings[sanitize_content]" value="1" 
                                   <?php checked($settings['sanitize_content'] ?? true); ?>>
                            <?php _e('Remove potentially harmful HTML tags', 'rss-content-planner'); ?>
                        </label>
                        <p class="description"><?php _e('Recommended for security. Removes scripts, forms, and other potentially harmful content.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Duplicate Detection', 'rss-content-planner'); ?></th>
                    <td>
                        <select name="rcp_settings[duplicate_detection]">
                            <option value="guid" <?php selected($settings['duplicate_detection'] ?? 'guid', 'guid'); ?>><?php _e('By GUID (Recommended)', 'rss-content-planner'); ?></option>
                            <option value="title" <?php selected($settings['duplicate_detection'] ?? 'guid', 'title'); ?>><?php _e('By Title', 'rss-content-planner'); ?></option>
                            <option value="url" <?php selected($settings['duplicate_detection'] ?? 'guid', 'url'); ?>><?php _e('By URL', 'rss-content-planner'); ?></option>
                            <option value="content" <?php selected($settings['duplicate_detection'] ?? 'guid', 'content'); ?>><?php _e('By Content Hash', 'rss-content-planner'); ?></option>
                        </select>
                        <p class="description"><?php _e('Method used to detect duplicate content across feeds.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Feed Timeout', 'rss-content-planner'); ?></th>
                    <td>
                        <input type="number" name="rcp_settings[feed_timeout]" 
                               value="<?php echo esc_attr($settings['feed_timeout'] ?? 30); ?>" 
                               min="5" max="300" class="small-text"> <?php _e('seconds', 'rss-content-planner'); ?>
                        <p class="description"><?php _e('How long to wait for feed responses before timing out.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render AI settings tab
     */
    private function render_ai_settings($settings) {
        ?>
        <div class="rcp-settings-section">
            <h2><?php _e('AI & Automation Settings', 'rss-content-planner'); ?></h2>
            <p class="description"><?php _e('Configure AI processing and automation workflows.', 'rss-content-planner'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('OpenAI API Key', 'rss-content-planner'); ?></th>
                    <td>
                        <input type="password" name="rcp_settings[openai_api_key]" 
                               value="<?php echo esc_attr($settings['openai_api_key'] ?? ''); ?>" 
                               class="regular-text" placeholder="sk-...">
                        <p class="description"><?php _e('Required for direct AI processing. Keep this secure.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Default AI Model', 'rss-content-planner'); ?></th>
                    <td>
                        <select name="rcp_settings[ai_model]">
                            <option value="gpt-4o-mini" <?php selected($settings['ai_model'] ?? 'gpt-4o-mini', 'gpt-4o-mini'); ?>>GPT-4o Mini (Recommended)</option>
                            <option value="gpt-4o" <?php selected($settings['ai_model'] ?? 'gpt-4o-mini', 'gpt-4o'); ?>>GPT-4o</option>
                            <option value="gpt-3.5-turbo" <?php selected($settings['ai_model'] ?? 'gpt-4o-mini', 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                        </select>
                        <p class="description"><?php _e('AI model to use for content processing. GPT-4o Mini offers the best balance of quality and cost.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Content Rewriting', 'rss-content-planner'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="rcp_settings[enable_rewriting]" value="1" 
                                   <?php checked($settings['enable_rewriting'] ?? false); ?>>
                            <?php _e('Enable automatic content rewriting', 'rss-content-planner'); ?>
                        </label>
                        <p class="description"><?php _e('Automatically rewrite imported content to make it unique and improve SEO.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('SEO Enhancement', 'rss-content-planner'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="rcp_settings[enable_seo]" value="1" 
                                   <?php checked($settings['enable_seo'] ?? false); ?>>
                            <?php _e('Generate SEO titles and meta descriptions', 'rss-content-planner'); ?>
                        </label>
                        <p class="description"><?php _e('Automatically generate optimized titles and meta descriptions for better search visibility.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Attribution Template', 'rss-content-planner'); ?></th>
                    <td>
                        <textarea name="rcp_settings[attribution_template]" rows="3" class="large-text"><?php echo esc_textarea($settings['attribution_template'] ?? 'Originally published at {source_name}: {source_url}'); ?></textarea>
                        <p class="description"><?php _e('Template for source attribution. Available variables: {source_name}, {source_url}, {author}, {date}', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render performance settings tab
     */
    private function render_performance_settings($settings) {
        ?>
        <div class="rcp-settings-section">
            <h2><?php _e('Performance Settings', 'rss-content-planner'); ?></h2>
            <p class="description"><?php _e('Optimize plugin performance and resource usage.', 'rss-content-planner'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Caching', 'rss-content-planner'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="rcp_settings[enable_caching]" value="1" 
                                   <?php checked($settings['enable_caching'] ?? true); ?>>
                            <?php _e('Enable feed caching', 'rss-content-planner'); ?>
                        </label>
                        <p class="description"><?php _e('Cache feed content to reduce server load and improve performance.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Cache Duration', 'rss-content-planner'); ?></th>
                    <td>
                        <input type="number" name="rcp_settings[cache_duration]" 
                               value="<?php echo esc_attr($settings['cache_duration'] ?? 3600); ?>" 
                               min="300" max="86400" class="small-text"> <?php _e('seconds', 'rss-content-planner'); ?>
                        <p class="description"><?php _e('How long to cache feed content. Longer durations reduce server load but may delay new content.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Batch Processing', 'rss-content-planner'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="rcp_settings[enable_batch_processing]" value="1" 
                                   <?php checked($settings['enable_batch_processing'] ?? true); ?>>
                            <?php _e('Process feeds in batches', 'rss-content-planner'); ?>
                        </label>
                        <p class="description"><?php _e('Process multiple feeds simultaneously for better performance on large installations.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Memory Limit', 'rss-content-planner'); ?></th>
                    <td>
                        <input type="number" name="rcp_settings[memory_limit]" 
                               value="<?php echo esc_attr($settings['memory_limit'] ?? 256); ?>" 
                               min="128" max="1024" class="small-text"> <?php _e('MB', 'rss-content-planner'); ?>
                        <p class="description"><?php _e('Memory limit for feed processing operations. Increase if you experience memory issues.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Rate Limiting', 'rss-content-planner'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="rcp_settings[enable_rate_limiting]" value="1" 
                                   <?php checked($settings['enable_rate_limiting'] ?? true); ?>>
                            <?php _e('Enable API rate limiting', 'rss-content-planner'); ?>
                        </label>
                        <p class="description"><?php _e('Prevent overwhelming external APIs with too many requests.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render advanced settings tab
     */
    private function render_advanced_settings($settings) {
        ?>
        <div class="rcp-settings-section">
            <h2><?php _e('Advanced Settings', 'rss-content-planner'); ?></h2>
            <p class="description"><?php _e('Advanced configuration options for developers and power users.', 'rss-content-planner'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Debug Logging', 'rss-content-planner'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="rcp_settings[enable_logging]" value="1" 
                                   <?php checked($settings['enable_logging']); ?>>
                            <?php _e('Enable debug logging', 'rss-content-planner'); ?>
                        </label>
                        <p class="description"><?php _e('Log detailed information for troubleshooting. May impact performance.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Log Level', 'rss-content-planner'); ?></th>
                    <td>
                        <select name="rcp_settings[log_level]">
                            <option value="error" <?php selected($settings['log_level'] ?? 'info', 'error'); ?>><?php _e('Errors Only', 'rss-content-planner'); ?></option>
                            <option value="warning" <?php selected($settings['log_level'] ?? 'info', 'warning'); ?>><?php _e('Warnings & Errors', 'rss-content-planner'); ?></option>
                            <option value="info" <?php selected($settings['log_level'] ?? 'info', 'info'); ?>><?php _e('All Information', 'rss-content-planner'); ?></option>
                            <option value="debug" <?php selected($settings['log_level'] ?? 'info', 'debug'); ?>><?php _e('Debug (Verbose)', 'rss-content-planner'); ?></option>
                        </select>
                        <p class="description"><?php _e('Level of detail to include in logs.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Data Retention', 'rss-content-planner'); ?></th>
                    <td>
                        <input type="number" name="rcp_settings[data_retention_days]" 
                               value="<?php echo esc_attr($settings['data_retention_days'] ?? 90); ?>" 
                               min="1" max="365" class="small-text"> <?php _e('days', 'rss-content-planner'); ?>
                        <p class="description"><?php _e('How long to keep logs and execution data. Older data will be automatically cleaned up.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Custom User Agent', 'rss-content-planner'); ?></th>
                    <td>
                        <input type="text" name="rcp_settings[user_agent]" 
                               value="<?php echo esc_attr($settings['user_agent'] ?? 'RSS Content Planner/' . RCP_PLUGIN_VERSION); ?>" 
                               class="regular-text">
                        <p class="description"><?php _e('User agent string sent when fetching feeds. Some sites may require specific user agents.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Webhook Security', 'rss-content-planner'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="rcp_settings[require_webhook_auth]" value="1" 
                                   <?php checked($settings['require_webhook_auth'] ?? true); ?>>
                            <?php _e('Require authentication for webhooks', 'rss-content-planner'); ?>
                        </label>
                        <p class="description"><?php _e('Require authentication tokens for all webhook communications.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Database Optimization', 'rss-content-planner'); ?></th>
                    <td>
                        <button type="button" id="rcp-optimize-db" class="button">
                            <?php _e('Optimize Database', 'rss-content-planner'); ?>
                        </button>
                        <button type="button" id="rcp-cleanup-data" class="button">
                            <?php _e('Cleanup Old Data', 'rss-content-planner'); ?>
                        </button>
                        <p class="description"><?php _e('Optimize database tables and remove old data to improve performance.', 'rss-content-planner'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#rcp-optimize-db').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true).text('<?php _e('Optimizing...', 'rss-content-planner'); ?>');
                
                $.post(ajaxurl, {
                    action: 'rcp_optimize_database',
                    nonce: '<?php echo wp_create_nonce('rcp_optimize_db'); ?>'
                }, function(response) {
                    btn.prop('disabled', false).text('<?php _e('Optimize Database', 'rss-content-planner'); ?>');
                    alert(response.data.message);
                });
            });
            
            $('#rcp-cleanup-data').on('click', function() {
                if (confirm('<?php _e('This will permanently delete old logs and execution data. Continue?', 'rss-content-planner'); ?>')) {
                    const btn = $(this);
                    btn.prop('disabled', true).text('<?php _e('Cleaning...', 'rss-content-planner'); ?>');
                    
                    $.post(ajaxurl, {
                        action: 'rcp_cleanup_data',
                        nonce: '<?php echo wp_create_nonce('rcp_cleanup_data'); ?>'
                    }, function(response) {
                        btn.prop('disabled', false).text('<?php _e('Cleanup Old Data', 'rss-content-planner'); ?>');
                        alert(response.data.message);
                    });
                }
            });
        });
        </script>
        <?php
    }
}
