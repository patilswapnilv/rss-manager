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
            
            // Fetch feed button
            $(document).on('click', '.rcp-fetch-feed', this.fetchFeed);
            
            // Test webhook button
            $(document).on('click', '.rcp-test-webhook', this.testWebhook);
            
            // Add feed form
            $(document).on('submit', '#rcp-add-feed-form', this.submitAddFeed);
            
            // Add webhook form
            $(document).on('submit', '#rcp-add-webhook-form', this.submitAddWebhook);
            
            // CSV import
            $(document).on('submit', '#rcp-csv-import-form', this.submitCsvImport);
            
            // Dynamic feed URL testing
            $(document).on('blur', '#feed_url', this.autoTestFeed);
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
            var feedUrl = $button.data('url') || $('#feed_url').val();
            var $result = $('#rcp-feed-test-result');
            
            if (!feedUrl) {
                RCPAdmin.showFeedTestResult('Please enter a feed URL first.', 'error');
                return;
            }
            
            $button.prop('disabled', true).text('Testing...');
            $result.hide();
            
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
                        var message = 'Feed test successful!<br>';
                        message += '<strong>Type:</strong> ' + response.data.type + '<br>';
                        message += '<strong>Title:</strong> ' + response.data.title;
                        RCPAdmin.showFeedTestResult(message, 'success');
                    } else {
                        RCPAdmin.showFeedTestResult('Feed test failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    RCPAdmin.showFeedTestResult('Test failed due to network error.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Feed');
                }
            });
        },
        
        /**
         * Auto test feed when URL field loses focus
         */
        autoTestFeed: function(e) {
            var feedUrl = $(this).val();
            if (feedUrl && feedUrl.length > 10) {
                // Delay to avoid rapid firing
                setTimeout(function() {
                    $('.rcp-test-feed').trigger('click');
                }, 500);
            }
        },
        
        /**
         * Fetch feed now functionality
         */
        fetchFeed: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var feedId = $button.data('feed-id');
            
            if (!feedId) {
                alert('Feed ID not found.');
                return;
            }
            
            $button.prop('disabled', true).html('<span class="rcp-loading"></span> Fetching...');
            
            $.ajax({
                url: rcpAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'rcp_fetch_feed_now',
                    feed_id: feedId,
                    nonce: rcpAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        RCPAdmin.showNotification('Feed fetched successfully! Processed ' + response.data.items_processed + ' items.', 'success');
                        // Optionally reload the page to show updated stats
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        RCPAdmin.showNotification('Feed fetch failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    RCPAdmin.showNotification('Feed fetch failed due to network error.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Fetch Now');
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
        },
        
        /**
         * Show feed test result
         */
        showFeedTestResult: function(message, type) {
            var $result = $('#rcp-feed-test-result');
            
            if ($result.length === 0) {
                $result = $('<div id="rcp-feed-test-result" class="rcp-feed-test-result"></div>');
                $('#feed_url').closest('td').append($result);
            }
            
            $result.removeClass('success error')
                   .addClass(type)
                   .html(message)
                   .show();
        },
        
        /**
         * Submit CSV import form
         */
        submitCsvImport: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('input[type="submit"]');
            var $progress = $('.rcp-import-progress');
            var $progressBar = $('.rcp-progress-bar-fill');
            
            // Check if file is selected
            var fileInput = $form.find('input[type="file"]')[0];
            if (!fileInput.files || !fileInput.files[0]) {
                alert('Please select a CSV file to import.');
                return;
            }
            
            var formData = new FormData($form[0]);
            formData.append('action', 'rcp_import_feeds_csv');
            formData.append('nonce', rcpAdmin.nonce);
            
            $submitBtn.prop('disabled', true).val('Importing...');
            $progress.addClass('show');
            $progressBar.css('width', '0%');
            
            // Simulate progress (since we can't track actual progress with simple AJAX)
            var progressInterval = setInterval(function() {
                var currentWidth = parseInt($progressBar.css('width'));
                if (currentWidth < 90) {
                    $progressBar.css('width', (currentWidth + 10) + '%');
                }
            }, 500);
            
            $.ajax({
                url: rcpAdmin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    clearInterval(progressInterval);
                    $progressBar.css('width', '100%');
                    
                    if (response.success) {
                        var result = response.data;
                        var message = 'Import completed!<br>';
                        message += 'Imported: ' + result.imported + ' feeds<br>';
                        message += 'Total rows: ' + result.total_rows;
                        
                        if (result.errors && result.errors.length > 0) {
                            message += '<br><br>Errors:<br>' + result.errors.join('<br>');
                        }
                        
                        RCPAdmin.showNotification(message, 'success');
                        
                        // Redirect to feeds list after 3 seconds
                        setTimeout(function() {
                            window.location.href = rcpAdmin.admin_url + 'admin.php?page=rcp-feeds';
                        }, 3000);
                    } else {
                        RCPAdmin.showNotification('Import failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    clearInterval(progressInterval);
                    RCPAdmin.showNotification('Import failed due to network error.', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).val('Import Feeds');
                    setTimeout(function() {
                        $progress.removeClass('show');
                    }, 2000);
                }
            });
        }
    };
    
    // API Key Management Functions (Global scope for inline onclick handlers)
    window.copyApiKey = function() {
        const apiKey = document.getElementById('rcp-api-key').textContent;
        navigator.clipboard.writeText(apiKey).then(() => {
            RCPAdmin.showNotification('API key copied to clipboard!', 'success');
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = apiKey;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            RCPAdmin.showNotification('API key copied to clipboard!', 'success');
        });
    };
    
    window.regenerateApiKey = function() {
        if (confirm('Are you sure? This will break any existing n8n workflows using the current API key.')) {
            $.post(rcpAdmin.ajax_url, {
                action: 'rcp_regenerate_api_key',
                nonce: rcpAdmin.nonce
            }, function(response) {
                if (response.success) {
                    document.getElementById('rcp-api-key').textContent = response.data.new_key;
                    RCPAdmin.showNotification('API key regenerated successfully!', 'success');
                } else {
                    RCPAdmin.showNotification('Failed to regenerate API key', 'error');
                }
            });
        }
    };
    
    window.downloadTemplate = function(templateName) {
        // Create download URL for the template file
        const templateUrl = rcpAdmin.plugin_url + '/n8n-workflow-templates.json';
        
        $.getJSON(templateUrl)
            .done(function(templates) {
                if (templates[templateName]) {
                    const template = templates[templateName];
                    const blob = new Blob([JSON.stringify(template.workflow, null, 2)], {
                        type: 'application/json'
                    });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = templateName + '-workflow.json';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    RCPAdmin.showNotification('Workflow template downloaded!', 'success');
                } else {
                    RCPAdmin.showNotification('Template not found', 'error');
                }
            })
            .fail(function() {
                RCPAdmin.showNotification('Failed to download template', 'error');
            });
    };
    
})(jQuery);
