/**
 * RSS Content Planner Admin JavaScript
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        RCPAdmin.init();
    });
    
    // Main admin object
    window.RCPAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initComponents();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Test feed button
            $(document).on('click', '.rcp-test-feed', this.testFeed);
            
            // Test webhook button
            $(document).on('click', '.rcp-test-webhook', this.testWebhook);
            
            // Add feed form
            $(document).on('submit', '#rcp-add-feed-form', this.submitAddFeed);
            
            // Add webhook form
            $(document).on('submit', '#rcp-add-webhook-form', this.submitAddWebhook);
        },
        
        /**
         * Initialize components
         */
        initComponents: function() {
            // Initialize any complex UI components here
        },
        
        /**
         * Test feed functionality
         */
        testFeed: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var feedUrl = $button.data('url') || $('#feed-url').val();
            
            if (!feedUrl) {
                alert('Please enter a feed URL first.');
                return;
            }
            
            $button.prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: rcpAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'rcp_test_feed',
                    url: feedUrl,
                    nonce: rcpAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Feed test successful! Type: ' + response.data.type + ', Title: ' + response.data.title);
                    } else {
                        alert('Feed test failed: ' + response.data);
                    }
                },
                error: function() {
                    alert('Test failed due to network error.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Feed');
                }
            });
        },
        
        /**
         * Test webhook functionality
         */
        testWebhook: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var webhookId = $button.data('webhook-id');
            
            $button.prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: rcpAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'rcp_test_webhook',
                    webhook_id: webhookId,
                    nonce: rcpAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Webhook test successful!');
                    } else {
                        alert('Webhook test failed: ' + response.data);
                    }
                },
                error: function() {
                    alert('Test failed due to network error.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Webhook');
                }
            });
        },
        
        /**
         * Submit add feed form
         */
        submitAddFeed: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('input[type="submit"]');
            var formData = $form.serialize();
            
            $submitBtn.prop('disabled', true).val('Adding...');
            
            $.ajax({
                url: rcpAdmin.ajax_url,
                type: 'POST',
                data: formData + '&action=rcp_add_feed&nonce=' + rcpAdmin.nonce,
                success: function(response) {
                    if (response.success) {
                        alert('Feed added successfully!');
                        location.reload();
                    } else {
                        alert('Failed to add feed: ' + response.data);
                    }
                },
                error: function() {
                    alert('Failed to add feed due to network error.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).val('Add Feed');
                }
            });
        },
        
        /**
         * Submit add webhook form
         */
        submitAddWebhook: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('input[type="submit"]');
            var formData = $form.serialize();
            
            $submitBtn.prop('disabled', true).val('Creating...');
            
            $.ajax({
                url: rcpAdmin.ajax_url,
                type: 'POST',
                data: formData + '&action=rcp_create_webhook&nonce=' + rcpAdmin.nonce,
                success: function(response) {
                    if (response.success) {
                        alert('Webhook created successfully!');
                        location.reload();
                    } else {
                        alert('Failed to create webhook: ' + response.data);
                    }
                },
                error: function() {
                    alert('Failed to create webhook due to network error.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).val('Create Webhook');
                }
            });
        },
        
        /**
         * Show loading state
         */
        showLoading: function($element) {
            $element.append('<span class="rcp-loading"></span>');
        },
        
        /**
         * Hide loading state
         */
        hideLoading: function($element) {
            $element.find('.rcp-loading').remove();
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut();
            }, 5000);
        }
    };
    
})(jQuery);
