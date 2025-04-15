/**
 * Thorius Learning Dashboard - JavaScript
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/js
 */
(function($) {
    'use strict';

    /**
     * Initialize the dashboard when the document is ready
     */
    $(document).ready(function() {
        const ThoriusDashboard = {
            /**
             * Dashboard configuration and state variables
             */
            config: {
                refreshInterval: null,
                chartInstance: null,
                ajaxUrl: thorius_dashboard.ajax_url,
                nonce: thorius_dashboard.nonce
            },

            /**
             * Initialize the dashboard
             */
            init: function() {
                this.setupEventListeners();
                this.initializeCharts();
                this.setupAutoRefresh();
            },

            /**
             * Set up all event listeners for the dashboard
             */
            setupEventListeners: function() {
                // Dashboard refresh button
                $('#thorius-refresh-dashboard').on('click', this.refreshDashboard.bind(this));
                
                // Toggle settings panel
                $('#thorius-toggle-settings').on('click', this.toggleSettingsPanel.bind(this));
                $('#thorius-cancel-settings').on('click', this.toggleSettingsPanel.bind(this));
                
                // Change date range
                $('#thorius-change-range').on('click', this.toggleSettingsPanel.bind(this));
                
                // Date range selection change
                $('select[name="date_range"]').on('change', this.handleDateRangeChange.bind(this));
                
                // Settings form submission
                $('#thorius-settings-form').on('submit', this.saveSettings.bind(this));
                
                // Trigger adaptation buttons
                $('.thorius-trigger-adaptation').on('click', this.triggerAdaptation.bind(this));
            },
            
            /**
             * Initialize chart visualizations
             */
            initializeCharts: function() {
                // Only proceed if Chart.js is available
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js is required for the Thorius Learning Dashboard');
                    return;
                }
                
                // Collect data for the chart
                const chartData = this.prepareChartData();
                
                // Initialize the chart
                const chartCtx = document.getElementById('agent-performance-chart').getContext('2d');
                
                this.config.chartInstance = new Chart(chartCtx, {
                    type: 'bar',
                    data: chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Agent Performance Metrics'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        let value = context.parsed.y;
                                        return label + value + '%';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                title: {
                                    display: true,
                                    text: 'Performance (%)'
                                }
                            }
                        }
                    }
                });
            },
            
            /**
             * Prepare data for the performance chart
             * 
             * @return {Object} Chart data configuration
             */
            prepareChartData: function() {
                const agents = [];
                const accuracy = [];
                const efficiency = [];
                const learningRate = [];
                
                // Extract data from the agents table
                $('.thorius-agents-table tbody tr').each(function() {
                    const agentName = $(this).find('.thorius-agent-name').text();
                    agents.push(agentName);
                    
                    // Parse percentage values, removing the % sign
                    const accuracyVal = parseFloat($(this).find('.thorius-accuracy').text().replace('%', '')) || 0;
                    const efficiencyVal = parseFloat($(this).find('.thorius-efficiency').text().replace('%', '')) || 0;
                    const learningRateVal = parseFloat($(this).find('.thorius-learning-rate').text().replace('%', '')) || 0;
                    
                    accuracy.push(accuracyVal);
                    efficiency.push(efficiencyVal);
                    learningRate.push(learningRateVal);
                });
                
                return {
                    labels: agents,
                    datasets: [
                        {
                            label: 'Accuracy',
                            data: accuracy,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Efficiency',
                            data: efficiency,
                            backgroundColor: 'rgba(75, 192, 192, 0.6)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Learning Rate',
                            data: learningRate,
                            backgroundColor: 'rgba(153, 102, 255, 0.6)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1
                        }
                    ]
                };
            },
            
            /**
             * Set up auto-refresh for the dashboard
             */
            setupAutoRefresh: function() {
                const refreshRate = parseInt($('input[name="refresh_rate"]').val());
                
                // Clear any existing interval
                if (this.config.refreshInterval) {
                    clearInterval(this.config.refreshInterval);
                }
                
                // Only set up auto-refresh if rate is greater than 0
                if (refreshRate > 0) {
                    // Convert minutes to milliseconds
                    const refreshMs = refreshRate * 60 * 1000;
                    
                    this.config.refreshInterval = setInterval(this.refreshDashboard.bind(this), refreshMs);
                }
            },
            
            /**
             * Handle date range selection change
             * 
             * @param {Event} e The change event
             */
            handleDateRangeChange: function(e) {
                const selectedValue = $(e.target).val();
                
                // Show/hide custom date inputs based on selection
                if (selectedValue === 'custom') {
                    $('#thorius-custom-date-range').show();
                } else {
                    $('#thorius-custom-date-range').hide();
                }
            },
            
            /**
             * Toggle the settings panel
             * 
             * @param {Event} e The click event
             */
            toggleSettingsPanel: function(e) {
                e.preventDefault();
                $('#thorius-settings-panel').slideToggle(300);
            },
            
            /**
             * Refresh dashboard data via AJAX
             * 
             * @param {Event} e The click event (optional)
             */
            refreshDashboard: function(e) {
                if (e) {
                    e.preventDefault();
                }
                
                const self = this;
                const $refreshButton = $('#thorius-refresh-dashboard');
                
                // Disable refresh button and show loading state
                $refreshButton.prop('disabled', true).addClass('updating');
                $refreshButton.html('<span class="dashicons dashicons-update-alt spinning"></span> ' + thorius_dashboard.i18n.loading);
                
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'thorius_refresh_dashboard',
                        nonce: this.config.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            self.updateDashboardData(response.data);
                            self.showNotification(thorius_dashboard.i18n.success, 'success');
                        } else {
                            self.showNotification(response.data.message || thorius_dashboard.i18n.error, 'error');
                        }
                    },
                    error: function() {
                        self.showNotification(thorius_dashboard.i18n.error, 'error');
                    },
                    complete: function() {
                        // Restore refresh button state
                        $refreshButton.prop('disabled', false).removeClass('updating');
                        $refreshButton.html('<span class="dashicons dashicons-update"></span> ' + thorius_dashboard.i18n.refresh_data);
                    }
                });
            },
            
            /**
             * Update dashboard with new data
             * 
             * @param {Object} data The dashboard data from AJAX response
             */
            updateDashboardData: function(data) {
                // Update stats
                if (data.stats) {
                    $('.thorius-stats-card .thorius-stat-value').each(function() {
                        const $this = $(this);
                        const key = $this.closest('.thorius-stat-item').find('.thorius-stat-label').text().trim().toLowerCase().replace(/\s+/g, '_');
                        
                        if (data.stats[key]) {
                            $this.text(data.stats[key]);
                        }
                    });
                }
                
                // Update recent adaptations
                if (data.recent_adaptations) {
                    $('#thorius-recent-adaptations').html(data.recent_adaptations);
                }
                
                // Update agent performance metrics
                if (data.agent_performance) {
                    $('.thorius-agents-table tbody tr').each(function() {
                        const agentId = $(this).data('agent-id');
                        
                        if (data.agent_performance[agentId]) {
                            const metrics = data.agent_performance[agentId];
                            
                            $(this).find('.thorius-accuracy').text(metrics.accuracy);
                            $(this).find('.thorius-adaptations').text(metrics.adaptations);
                            $(this).find('.thorius-learning-rate').text(metrics.learning_rate);
                            $(this).find('.thorius-efficiency').text(metrics.efficiency);
                            $(this).find('.thorius-last-update').text(metrics.last_update);
                        }
                    });
                }
                
                // Update time range label
                if (data.time_range) {
                    $('#thorius-time-range-label').text(data.time_range);
                }
                
                // Update charts
                this.updateCharts();
            },
            
            /**
             * Update chart visualizations with new data
             */
            updateCharts: function() {
                if (this.config.chartInstance) {
                    const newData = this.prepareChartData();
                    
                    // Update chart data
                    this.config.chartInstance.data.labels = newData.labels;
                    
                    // Update each dataset
                    newData.datasets.forEach((dataset, index) => {
                        this.config.chartInstance.data.datasets[index].data = dataset.data;
                    });
                    
                    // Refresh the chart
                    this.config.chartInstance.update();
                }
            },
            
            /**
             * Save dashboard settings
             * 
             * @param {Event} e The form submit event
             */
            saveSettings: function(e) {
                e.preventDefault();
                
                const self = this;
                const $form = $('#thorius-settings-form');
                const $submitButton = $form.find('button[type="submit"]');
                
                // Disable submit button
                $submitButton.prop('disabled', true);
                
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: $form.serialize() + '&action=thorius_save_dashboard_settings&nonce=' + this.config.nonce,
                    success: function(response) {
                        if (response.success) {
                            self.showNotification(thorius_dashboard.i18n.settings_saved, 'success');
                            
                            // Close settings panel
                            $('#thorius-settings-panel').slideUp(300);
                            
                            // Refresh dashboard with new settings
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            self.showNotification(response.data.message || thorius_dashboard.i18n.error, 'error');
                        }
                    },
                    error: function() {
                        self.showNotification(thorius_dashboard.i18n.error, 'error');
                    },
                    complete: function() {
                        // Re-enable submit button
                        $submitButton.prop('disabled', false);
                    }
                });
            },
            
            /**
             * Trigger an adaptation for an agent
             * 
             * @param {Event} e The click event
             */
            triggerAdaptation: function(e) {
                e.preventDefault();
                
                const self = this;
                const $button = $(e.target);
                const agentId = $button.data('agent');
                
                // Confirm before proceeding
                if (!confirm(thorius_dashboard.i18n.confirm_adaptation)) {
                    return;
                }
                
                // Disable the button
                $button.prop('disabled', true);
                
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'thorius_trigger_adaptation',
                        agent: agentId,
                        nonce: this.config.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            self.showNotification(thorius_dashboard.i18n.adaptation_triggered, 'success');
                            
                            // Refresh dashboard after a short delay
                            setTimeout(function() {
                                self.refreshDashboard();
                            }, 1000);
                        } else {
                            self.showNotification(response.data.message || thorius_dashboard.i18n.error, 'error');
                        }
                    },
                    error: function() {
                        self.showNotification(thorius_dashboard.i18n.error, 'error');
                    },
                    complete: function() {
                        // Re-enable the button
                        $button.prop('disabled', false);
                    }
                });
            },
            
            /**
             * Show a notification message
             * 
             * @param {string} message The notification message
             * @param {string} type The notification type (success, error, warning, info)
             */
            showNotification: function(message, type) {
                // Create notification element
                const $notification = $('<div class="thorius-notification ' + type + '">' + message + '</div>');
                
                // Add notification to container
                $('#thorius-notifications').append($notification);
                
                // Auto-remove after delay
                setTimeout(function() {
                    $notification.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 3000);
            }
        };
        
        // Initialize the dashboard
        ThoriusDashboard.init();
    });
})(jQuery); 