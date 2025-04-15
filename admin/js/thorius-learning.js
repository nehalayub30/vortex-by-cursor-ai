/**
 * Thorius Learning Dashboard JavaScript
 *
 * Handles all interactive functionality for the Thorius learning dashboard
 */
(function($) {
    'use strict';

    // Dashboard initialization
    $(document).ready(function() {
        initDashboard();
        setupEventListeners();
        startAutoRefresh();
    });

    /**
     * Initialize the dashboard
     */
    function initDashboard() {
        // Show the first agent tab by default
        $('.thorius-agent-tab-button:first').addClass('active');
        $('.thorius-agent-tab-content:first').addClass('active');
    }

    /**
     * Set up all event listeners for the dashboard
     */
    function setupEventListeners() {
        // Tab switching
        $('.thorius-agent-tab-button').on('click', switchAgentTab);
        
        // Refresh metrics button
        $('#refresh-metrics').on('click', refreshAllMetrics);
        
        // Agent actions
        $('.thorius-adapt-button').on('click', triggerAdaptation);
        $('.thorius-reset-button').on('click', resetAgentLearning);
        
        // Settings form
        $('#thorius-learning-settings-form').on('submit', saveSettings);
    }

    /**
     * Switch between agent tabs
     */
    function switchAgentTab(e) {
        e.preventDefault();
        
        const agentId = $(this).data('agent');
        
        // Update active tab button
        $('.thorius-agent-tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Show selected tab content
        $('.thorius-agent-tab-content').removeClass('active');
        $('#agent-tab-' + agentId).addClass('active');
    }

    /**
     * Refresh all dashboard metrics
     */
    function refreshAllMetrics(e) {
        if (e) {
            e.preventDefault();
        }
        
        const $button = $('#refresh-metrics');
        $button.prop('disabled', true).addClass('updating');
        $button.html('<span class="dashicons dashicons-update-alt spin"></span> Refreshing...');
        
        // AJAX call to fetch updated metrics
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_learning_metrics',
                security: thorius_learning.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboardMetrics(response.data);
                    showNotification('Metrics refreshed successfully', 'success');
                } else {
                    showNotification('Error refreshing metrics: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Server error while refreshing metrics', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).removeClass('updating');
                $button.html('Refresh Now');
            }
        });
    }

    /**
     * Update dashboard with new metrics data
     */
    function updateDashboardMetrics(data) {
        // Update overview metrics
        $('#total-interactions').text(data.total_interactions);
        $('#total-feedback').text(data.total_feedback);
        $('#total-adaptations').text(data.total_adaptations);
        $('#learning-rate').text(data.learning_rate);
        
        // Update agent metrics
        for (const agentId in data.agents) {
            const agentData = data.agents[agentId];
            
            // Update metrics values
            $('#' + agentId + '-confidence').html(
                agentData.confidence + getTrendIcon(agentData.confidence_trend)
            );
            $('#' + agentId + '-accuracy').html(
                agentData.accuracy + getTrendIcon(agentData.accuracy_trend)
            );
            $('#' + agentId + '-adaptability').html(
                agentData.adaptability + getTrendIcon(agentData.adaptability_trend)
            );
            $('#' + agentId + '-consistency').html(
                agentData.consistency + getTrendIcon(agentData.consistency_trend)
            );
            
            // Update last adaptation time
            $('#' + agentId + '-last-adaptation').text(agentData.last_adaptation);
        }
        
        // Update adaptations table
        updateAdaptationsTable(data.adaptations);
    }
    
    /**
     * Get trend icon HTML based on trend value
     */
    function getTrendIcon(trend) {
        if (trend > 0) {
            return '<span class="trend-up dashicons dashicons-arrow-up-alt"></span>';
        } else if (trend < 0) {
            return '<span class="trend-down dashicons dashicons-arrow-down-alt"></span>';
        }
        return '';
    }
    
    /**
     * Update the adaptations table with new data
     */
    function updateAdaptationsTable(adaptations) {
        const $tableBody = $('#thorius-adaptations-table tbody');
        $tableBody.empty();
        
        if (adaptations.length === 0) {
            $tableBody.append('<tr><td colspan="4">No adaptations recorded yet.</td></tr>');
            return;
        }
        
        adaptations.forEach(function(adaptation) {
            const impactClass = adaptation.impact > 0 
                ? 'positive' 
                : (adaptation.impact < 0 ? 'negative' : '');
            
            const impactText = adaptation.impact > 0 
                ? '+' + adaptation.impact + '%' 
                : adaptation.impact + '%';
            
            const row = `
                <tr>
                    <td>${adaptation.agent}</td>
                    <td>${adaptation.date}</td>
                    <td>${adaptation.trigger}</td>
                    <td class="adaptation-impact ${impactClass}">${impactText}</td>
                </tr>
            `;
            
            $tableBody.append(row);
        });
    }

    /**
     * Trigger an adaptation for a specific agent
     */
    function triggerAdaptation(e) {
        e.preventDefault();
        
        const $button = $(this);
        const agentId = $button.data('agent');
        
        if (confirm('Are you sure you want to trigger an adaptation for ' + agentId.toUpperCase() + '?')) {
            $button.prop('disabled', true).addClass('updating');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'trigger_adaptation',
                    agent: agentId,
                    security: thorius_learning.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Adaptation triggered successfully for ' + agentId.toUpperCase(), 'success');
                        refreshAllMetrics();
                    } else {
                        showNotification('Error: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotification('Server error while triggering adaptation', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('updating');
                }
            });
        }
    }

    /**
     * Reset learning data for a specific agent
     */
    function resetAgentLearning(e) {
        e.preventDefault();
        
        const $button = $(this);
        const agentId = $button.data('agent');
        
        if (confirm('WARNING: Are you sure you want to reset all learning data for ' + agentId.toUpperCase() + '?\nThis action cannot be undone.')) {
            $button.prop('disabled', true).addClass('updating');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'reset_learning',
                    agent: agentId,
                    security: thorius_learning.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Learning data reset successfully for ' + agentId.toUpperCase(), 'success');
                        refreshAllMetrics();
                    } else {
                        showNotification('Error: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotification('Server error while resetting learning data', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('updating');
                }
            });
        }
    }

    /**
     * Save learning settings
     */
    function saveSettings(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        
        $submitButton.prop('disabled', true).addClass('updating');
        
        const formData = $form.serialize();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_learning_settings',
                form_data: formData,
                security: thorius_learning.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Settings saved successfully', 'success');
                } else {
                    showNotification('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Server error while saving settings', 'error');
            },
            complete: function() {
                $submitButton.prop('disabled', false).removeClass('updating');
            }
        });
    }

    /**
     * Show a notification message
     */
    function showNotification(message, type) {
        const $notification = $('<div class="notice is-dismissible thorius-notice"></div>');
        
        if (type === 'success') {
            $notification.addClass('notice-success');
        } else if (type === 'error') {
            $notification.addClass('notice-error');
        } else if (type === 'warning') {
            $notification.addClass('notice-warning');
        } else {
            $notification.addClass('notice-info');
        }
        
        $notification.html('<p>' + message + '</p>');
        
        // Add dismiss button
        const $dismissButton = $('<button type="button" class="notice-dismiss"></button>');
        $dismissButton.on('click', function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        $notification.append($dismissButton);
        
        // Add to notifications area
        $('#thorius-notifications').append($notification);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Start auto refresh for dashboard metrics
     */
    function startAutoRefresh() {
        // Refresh metrics every 5 minutes (300000 ms)
        setInterval(refreshAllMetrics, 300000);
    }

})(jQuery); 