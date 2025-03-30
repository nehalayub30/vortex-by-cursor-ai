/**
 * Thorius Admin Scripts
 */
(function($) {
    'use strict';
    
    // Tab Manager
    class ThoriusTabManager {
        constructor() {
            this.initTabs();
            this.initTabEvents();
            this.initRangeSliders();
            this.initFormSubmission();
            this.initWidgetPreview();
        }
        
        initTabs() {
            // Initialize all tab containers
            $('.thorius-tabs').each(function() {
                const $container = $(this);
                const $firstTab = $container.find('.thorius-tab-button:first');
                
                if ($firstTab.length) {
                    // Check if there's an active tab
                    const $activeTab = $container.find('.thorius-tab-button.nav-tab-active');
                    
                    if ($activeTab.length === 0) {
                        $firstTab.click();
                    }
                }
            });
        }
        
        initTabEvents() {
            // Tab switching logic
            $('.thorius-tab-button').on('click', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const tabId = $button.data('tab');
                const $container = $button.closest('.thorius-tabs');
                
                // Update active states
                $container.find('.thorius-tab-button').removeClass('nav-tab-active');
                $button.addClass('nav-tab-active');
                
                // Show selected content
                $container.find('.thorius-tab-content').hide();
                $container.find('#' + tabId + '-content').show();
                
                // Save state via AJAX
                if ($container.data('persistent') === true) {
                    $.ajax({
                        url: thorius_admin_data.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'thorius_save_tab_state',
                            nonce: thorius_admin_data.nonce,
                            tab: tabId
                        }
                    });
                }
            });
        }
        
        initRangeSliders() {
            // Update range slider values
            $('input[type="range"]').on('input', function() {
                const $slider = $(this);
                const value = $slider.val();
                $slider.next('.thorius-range-value').text(value);
            });
        }
        
        initFormSubmission() {
            // Form submission
            $('.thorius-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const formData = $form.serialize();
                
                // Show loading state
                const $submitButton = $form.find('button[type="submit"]');
                const originalText = $submitButton.text();
                $submitButton.text('Saving...').prop('disabled', true);
                
                // Submit via AJAX
                $.ajax({
                    url: thorius_admin_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'thorius_save_agent_settings',
                        nonce: thorius_admin_data.nonce,
                        ...Object.fromEntries(new FormData($form[0]))
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            const $message = $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                            $form.before($message);
                            
                            // Auto-dismiss after 3 seconds
                            setTimeout(function() {
                                $message.fadeOut(function() {
                                    $(this).remove();
                                });
                            }, 3000);
                        } else {
                            // Show error message
                            const $message = $('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>');
                            $form.before($message);
                        }
                    },
                    error: function() {
                        // Show error message
                        const $message = $('<div class="notice notice-error is-dismissible"><p>An error occurred while saving settings.</p></div>');
                        $form.before($message);
                    },
                    complete: function() {
                        // Restore button state
                        $submitButton.text(originalText).prop('disabled', false);
                    }
                });
            });
        }
        
        /**
         * Initialize widget preview functionality
         */
        initWidgetPreview() {
            $('.thorius-widget-preview-button').on('click', function(e) {
                e.preventDefault();
                
                const $form = $(this).closest('form');
                const formData = new FormData($form[0]);
                const previewParams = {};
                
                // Extract form data
                for (const [key, value] of formData.entries()) {
                    previewParams[key] = value;
                }
                
                // Build preview HTML
                const previewHtml = this.buildWidgetPreview(previewParams);
                
                // Display preview
                $('.thorius-widget-preview-container').html(previewHtml).show();
            }.bind(this));
        }
        
        /**
         * Build widget preview HTML
         */
        buildWidgetPreview(params) {
            let preview = '<div class="thorius-widget-preview">';
            
            // Widget header
            preview += '<div class="thorius-preview-header">';
            preview += '<h3>' + (params.title || 'AI Assistant') + '</h3>';
            preview += '</div>';
            
            // Widget content
            preview += '<div class="thorius-preview-content">';
            preview += '<div class="thorius-preview-message">';
            preview += '<div class="thorius-preview-avatar thorius-preview-avatar-' + (params.agent || 'cloe') + '"></div>';
            preview += '<div class="thorius-preview-message-text">' + (params.welcome_message || 'Hello! How can I help you?') + '</div>';
            preview += '</div>';
            preview += '</div>';
            
            // Widget input
            preview += '<div class="thorius-preview-input">';
            preview += '<div class="thorius-preview-textarea">Type your message...</div>';
            if (params.voice === 'on') {
                preview += '<div class="thorius-preview-voice-button"></div>';
            }
            preview += '<div class="thorius-preview-send-button"></div>';
            preview += '</div>';
            
            preview += '</div>'; // Close preview container
            
            return preview;
        }
    }
    
    // Initialize when document is ready
    $(document).ready(function() {
        new ThoriusTabManager();
    });
    
})(jQuery); 