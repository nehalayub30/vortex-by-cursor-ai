/**
 * VORTEX AI Marketplace Dashboard JavaScript
 *
 * Dashboard-specific functionality for analytics, charts, and summary displays.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/js
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */

(function($) {
    'use strict';

    /**
     * VORTEX Dashboard functionality
     */
    const VortexDashboard = {
        /**
         * Chart instances
         */
        charts: {},

        /**
         * Color palettes for charts
         */
        chartColors: {
            primary: [
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 99, 132, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 159, 64, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 205, 86, 0.8)',
                'rgba(201, 203, 207, 0.8)',
                'rgba(255, 99, 132, 0.8)'
            ],
            secondary: [
                'rgba(54, 162, 235, 0.4)',
                'rgba(255, 99, 132, 0.4)',
                'rgba(75, 192, 192, 0.4)',
                'rgba(255, 159, 64, 0.4)',
                'rgba(153, 102, 255, 0.4)',
                'rgba(255, 205, 86, 0.4)',
                'rgba(201, 203, 207, 0.4)',
                'rgba(255, 99, 132, 0.4)'
            ],
            background: [
                'rgba(54, 162, 235, 0.1)',
                'rgba(255, 99, 132, 0.1)',
                'rgba(75, 192, 192, 0.1)',
                'rgba(255, 159, 64, 0.1)',
                'rgba(153, 102, 255, 0.1)',
                'rgba(255, 205, 86, 0.1)',
                'rgba(201, 203, 207, 0.1)',
                'rgba(255, 99, 132, 0.1)'
            ]
        },

        /**
         * Initialize dashboard features
         */
        init: function() {
            this.initDashboardSummary();
            this.initCharts();
            this.initDateRangeFilters();
            this.initMarketplaceInsights();
            this.initRefreshButtons();
            this.initExportButtons();
            this.setupResponsiveBehavior();
        },
        
        /**
         * Initialize dashboard summary
         */
        initDashboardSummary: function() {
            $('.vortex-dashboard-widget').each(function() {
                const widget = $(this);
                const widgetId = widget.data('widget-id');
                
                if (widgetId && widget.hasClass('vortex-load-summary')) {
                    VortexDashboard.loadSummaryData(widgetId, widget);
                }
            });
        },
        
        /**
         * Load summary data for dashboard widget
         */
        loadSummaryData: function(widgetId, widget) {
            const loadingIndicator = widget.find('.vortex-widget-loading');
            const contentArea = widget.find('.vortex-widget-content');
            
            // Show loading indicator
            loadingIndicator.show();
            contentArea.hide();
            
            // Get date range if applicable
            let dateRange = '';
            const rangeSelector = widget.find('.vortex-date-range-selector');
            if (rangeSelector.length) {
                dateRange = rangeSelector.val();
            }
            
            // Make AJAX request to get data
            $.ajax({
                url: vortexAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_admin_action',
                    nonce: vortexAdmin.nonce,
                    vortex_action: 'get_dashboard_widget_data',
                    widget_id: widgetId,
                    date_range: dateRange
                },
                success: function(response) {
                    if (response.success) {
                        contentArea.html(response.data.html);
                        
                        // Initialize any charts in the response
                        if (response.data.chart_data) {
                            VortexDashboard.initWidgetChart(widgetId, response.data.chart_data);
                        }
                        
                        // Initialize any metric cards in the response
                        VortexDashboard.initMetricCards(widget);
                        
                        // Show content
                        contentArea.show();
                        
                        // Trigger widget loaded event
                        widget.trigger('vortex:widget-loaded', [response.data]);
                    } else {
                        contentArea.html('<p class="vortex-error">' + (response.data.message || 'Error loading widget data') + '</p>');
                        contentArea.show();
                    }
                },
                error: function() {
                    contentArea.html('<p class="vortex-error">Error loading widget data. Please try again.</p>');
                    contentArea.show();
                },
                complete: function() {
                    loadingIndicator.hide();
                }
            });
        },
        
        /**
         * Initialize chart containers
         */
        initCharts: function() {
            $('.vortex-chart-container').each(function() {
                const chartId = $(this).data('chart-id');
                const chartType = $(this).data('chart-type');
                const dataEndpoint = $(this).data('chart-endpoint');
                
                if (chartId && chartType && dataEndpoint) {
                    VortexDashboard.loadChartData(chartId, chartType, dataEndpoint, $(this));
                }
            });
        },
        
        /**
         * Load chart data and render chart
         */
        loadChartData: function(chartId, chartType, dataEndpoint, container) {
            const loadingIndicator = container.find('.vortex-chart-loading');
            const chartCanvas = container.find('canvas');
            const chartContext = chartCanvas[0].getContext('2d');
            
            // Show loading indicator
            loadingIndicator.show();
            
            // Get filters
            const filters = {};
            container.find('.vortex-chart-filter').each(function() {
                filters[$(this).data('filter-key')] = $(this).val();
            });
            
            // Make AJAX request to get data
            $.ajax({
                url: vortexAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_analytics_chart',
                    nonce: vortexAdmin.nonce,
                    chart_id: chartId,
                    chart_type: chartType,
                    endpoint: dataEndpoint,
                    filters: filters
                },
                success: function(response) {
                    if (response.success) {
                        // Destroy existing chart if it exists
                        if (VortexDashboard.charts[chartId]) {
                            VortexDashboard.charts[chartId].destroy();
                        }
                        
                        // Create new chart
                        VortexDashboard.createChart(chartId, chartType, chartContext, response.data);
                    } else {
                        container.html('<p class="vortex-error">' + (response.data.message || 'Error loading chart data') + '</p>');
                    }
                },
                error: function() {
                    container.html('<p class="vortex-error">Error loading chart data. Please try again.</p>');
                },
                complete: function() {
                    loadingIndicator.hide();
                }
            });
        },
        
        /**
         * Create a chart from data
         */
        createChart: function(chartId, chartType, ctx, chartData) {
            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            };
            
            // Merge default options with any provided options
            const chartOptions = $.extend(true, {}, defaultOptions, chartData.options || {});
            
            // Set options specific to chart type
            switch (chartType) {
                case 'line':
                    // Add tension to lines
                    if (chartData.datasets) {
                        chartData.datasets.forEach(function(dataset) {
                            dataset.tension = 0.3;
                        });
                    }
                    break;
                    
                case 'bar':
                    // Add bar specific options
                    chartOptions.scales = chartOptions.scales || {};
                    chartOptions.scales.y = chartOptions.scales.y || {};
                    chartOptions.scales.y.beginAtZero = true;
                    break;
                    
                case 'pie':
                case 'doughnut':
                    // Add specific options for pie/doughnut
                    chartOptions.plugins = chartOptions.plugins || {};
                    chartOptions.plugins.legend = chartOptions.plugins.legend || {};
                    chartOptions.plugins.legend.position = 'right';
                    break;
            }
            
            // Create chart
            VortexDashboard.charts[chartId] = new Chart(ctx, {
                type: chartType,
                data: {
                    labels: chartData.labels,
                    datasets: chartData.datasets
                },
                options: chartOptions
            });
        },
        
        /**
         * Initialize widget-specific chart
         */
        initWidgetChart: function(widgetId, chartData) {
            const container = $('#vortex-widget-' + widgetId);
            const chartCanvas = container.find('.vortex-widget-chart canvas');
            
            if (chartCanvas.length) {
                const chartId = 'widget-chart-' + widgetId;
                const chartContext = chartCanvas[0].getContext('2d');
                
                // Destroy existing chart if it exists
                if (VortexDashboard.charts[chartId]) {
                    VortexDashboard.charts[chartId].destroy();
                }
                
                // Create new chart
                VortexDashboard.createChart(chartId, chartData.type, chartContext, chartData);
            }
        },
        
        /**
         * Initialize metric cards with trend indicators
         */
        initMetricCards: function(container) {
            container.find('.vortex-metric-card').each(function() {
                const card = $(this);
                const trendValue = card.data('trend-value');
                const trendIndicator = card.find('.vortex-trend-indicator');
                
                if (trendValue !== undefined && trendIndicator.length) {
                    // Clear existing classes
                    trendIndicator.removeClass('trend-up trend-down trend-neutral');
                    
                    // Add appropriate class based on trend value
                    if (trendValue > 0) {
                        trendIndicator.addClass('trend-up');
                    } else if (trendValue < 0) {
                        trendIndicator.addClass('trend-down');
                    } else {
                        trendIndicator.addClass('trend-neutral');
                    }
                }
            });
        },

        /**
         * Initialize date range filters
         */
        initDateRangeFilters: function() {
            // Implementation of initDateRangeFilters function
        },

        /**
         * Initialize marketplace insights
         */
        initMarketplaceInsights: function() {
            // Implementation of initMarketplaceInsights function
        },

        /**
         * Initialize refresh buttons
         */
        initRefreshButtons: function() {
            // Implementation of initRefreshButtons function
        },

        /**
         * Initialize export buttons
         */
        initExportButtons: function() {
            // Implementation of initExportButtons function
        },

        /**
         * Setup responsive behavior
         */
        setupResponsiveBehavior: function() {
            // Implementation of setupResponsiveBehavior function
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        VortexDashboard.init();
    });

    // Make VortexDashboard accessible globally
    window.VortexDashboard = VortexDashboard;

})(jQuery); 