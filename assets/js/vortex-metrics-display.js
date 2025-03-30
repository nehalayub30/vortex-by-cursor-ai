/**
 * VORTEX AI Marketplace - Metrics Display
 * 
 * Handles the display and interaction with metrics and analytics data
 * while ensuring AI agents (HURAII, CLOE, BusinessStrategist) maintain
 * continuous learning from user interactions.
 */
(function($) {
    'use strict';
    
    /**
     * VortexMetricsDisplay - Handles metrics visualization and interaction
     */
    const VortexMetricsDisplay = {
        /**
         * Configuration settings
         */
        config: {
            ajaxUrl: vortex_metrics_data.ajax_url || '',
            nonce: vortex_metrics_data.nonce || '',
            userId: vortex_metrics_data.user_id || 0,
            userRole: vortex_metrics_data.user_role || 'viewer',
            refreshInterval: vortex_metrics_data.refresh_interval || 60000,
            chartColors: vortex_metrics_data.chart_colors || ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6'],
            showRealtime: vortex_metrics_data.show_realtime === '1',
            dateFormat: vortex_metrics_data.date_format || 'MMM D, YYYY',
            learningEnabled: vortex_metrics_data.learning_enabled !== '0',
            i18n: vortex_metrics_data.i18n || {}
        },
        
        /**
         * State management
         */
        state: {
            chartInstances: {},
            currentView: 'overview',
            timeRange: '7d',
            customDateStart: null,
            customDateEnd: null,
            filterCategory: 'all',
            isLoading: false,
            refreshTimer: null,
            interactionHistory: [],
            lastUpdate: null,
            metricData: {},
            aiInsights: {}
        },
        
        /**
         * Initialize metrics display
         */
        init: function() {
            // Initialize metrics display functionality
            this.setupEventListeners();
            this.loadInitialData();
            
            // Setup refresh timer if realtime is enabled
            if (this.config.showRealtime) {
                this.setupRefreshTimer();
            }
            
            // Track initialization for AI learning
            this.trackAIInteraction('metrics_display_initialized', {
                user_role: this.config.userRole,
                realtime_enabled: this.config.showRealtime,
                initial_view: this.state.currentView,
                timestamp: new Date().toISOString()
            });
            
            // Apply any URL parameters for deep linking
            this.applyUrlParameters();
        },
        
        /**
         * Setup event listeners for user interactions
         */
        setupEventListeners: function() {
            // Time range selector
            $('.vortex-time-range-selector button').on('click', (e) => {
                const $button = $(e.currentTarget);
                const range = $button.data('range');
                
                // Update UI
                $('.vortex-time-range-selector button').removeClass('active');
                $button.addClass('active');
                
                // Update state
                this.state.timeRange = range;
                
                // Hide custom date range if not selected
                if (range !== 'custom') {
                    $('.vortex-custom-date-range').slideUp();
                } else {
                    $('.vortex-custom-date-range').slideDown();
                }
                
                // Reload data
                this.reloadData();
                
                // Track for AI learning
                this.trackAIInteraction('time_range_changed', {
                    previous_range: this.state.timeRange,
                    new_range: range,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Custom date range
            $('#vortex-date-start, #vortex-date-end').on('change', () => {
                this.state.customDateStart = $('#vortex-date-start').val();
                this.state.customDateEnd = $('#vortex-date-end').val();
                
                if (this.state.customDateStart && this.state.customDateEnd) {
                    this.reloadData();
                    
                    // Track for AI learning
                    this.trackAIInteraction('custom_date_range_set', {
                        start_date: this.state.customDateStart,
                        end_date: this.state.customDateEnd,
                        timestamp: new Date().toISOString()
                    });
                }
            });
            
            // View selector
            $('.vortex-metrics-view-selector button').on('click', (e) => {
                const $button = $(e.currentTarget);
                const view = $button.data('view');
                
                // Update UI
                $('.vortex-metrics-view-selector button').removeClass('active');
                $button.addClass('active');
                
                // Update state
                const previousView = this.state.currentView;
                this.state.currentView = view;
                
                // Show/hide appropriate sections
                $('.vortex-metrics-section').hide();
                $(`.vortex-metrics-section[data-view="${view}"]`).show();
                
                // Reload data for the new view
                this.reloadData();
                
                // Track for AI learning
                this.trackAIInteraction('metrics_view_changed', {
                    previous_view: previousView,
                    new_view: view,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Category filter
            $('#vortex-category-filter').on('change', (e) => {
                const previousCategory = this.state.filterCategory;
                this.state.filterCategory = $(e.target).val();
                
                // Reload data
                this.reloadData();
                
                // Track for AI learning
                this.trackAIInteraction('category_filter_changed', {
                    previous_category: previousCategory,
                    new_category: this.state.filterCategory,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Export data buttons
            $('.vortex-export-csv').on('click', () => {
                this.exportData('csv');
                
                // Track for AI learning
                this.trackAIInteraction('metrics_exported', {
                    format: 'csv',
                    view: this.state.currentView,
                    timestamp: new Date().toISOString()
                });
            });
            
            $('.vortex-export-json').on('click', () => {
                this.exportData('json');
                
                // Track for AI learning
                this.trackAIInteraction('metrics_exported', {
                    format: 'json',
                    view: this.state.currentView,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Refresh data button
            $('.vortex-refresh-metrics').on('click', () => {
                this.reloadData(true);
                
                // Track for AI learning
                this.trackAIInteraction('metrics_manually_refreshed', {
                    view: this.state.currentView,
                    timestamp: new Date().toISOString()
                });
            });
            
            // AI Insights toggle
            $('.vortex-toggle-ai-insights').on('click', (e) => {
                e.preventDefault();
                $('.vortex-ai-insights-container').slideToggle();
                
                const isVisible = $('.vortex-ai-insights-container').is(':visible');
                
                // Track for AI learning
                this.trackAIInteraction('ai_insights_toggled', {
                    now_visible: isVisible,
                    timestamp: new Date().toISOString()
                });
            });
            
            // Data point hover for detailed info
            $('.vortex-metrics-container').on('mouseover', '.chartjs-tooltip, .vortex-data-point', (e) => {
                const dataPoint = $(e.currentTarget).data();
                if (dataPoint && dataPoint.value) {
                    // Track for AI learning - but debounced to avoid spam
                    this.debounce(() => {
                        this.trackAIInteraction('data_point_examined', {
                            point_value: dataPoint.value,
                            point_label: dataPoint.label,
                            timestamp: new Date().toISOString()
                        });
                    }, 1000)();
                }
            });
        },
        
        /**
         * Load initial metrics data
         */
        loadInitialData: function() {
            this.showLoading();
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_metrics_data',
                    view: this.state.currentView,
                    time_range: this.state.timeRange,
                    start_date: this.state.customDateStart,
                    end_date: this.state.customDateEnd,
                    category: this.state.filterCategory,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.state.metricData = response.data.metrics || {};
                        this.state.aiInsights = response.data.ai_insights || {};
                        this.state.lastUpdate = new Date();
                        
                        this.renderMetrics();
                        this.renderAIInsights();
                        
                        // Track for AI learning
                        this.trackAIInteraction('initial_metrics_loaded', {
                            view: this.state.currentView,
                            data_points: this.countDataPoints(this.state.metricData),
                            has_insights: Object.keys(this.state.aiInsights).length > 0,
                            timestamp: new Date().toISOString()
                        });
                    } else {
                        this.showError(response.data.message || this.config.i18n.loadError || 'Error loading metrics data');
                    }
                },
                error: () => {
                    this.showError(this.config.i18n.connectionError || 'Connection error');
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        },
        
        /**
         * Reload metrics data
         * 
         * @param {boolean} forceReload Force reload even if not necessary
         */
        reloadData: function(forceReload = false) {
            // Skip if already loading
            if (this.state.isLoading) return;
            
            // Skip if recent update unless forced
            if (!forceReload && this.state.lastUpdate) {
                const now = new Date();
                const timeSinceUpdate = now - this.state.lastUpdate;
                if (timeSinceUpdate < 10000) { // Less than 10 seconds ago
                    return;
                }
            }
            
            this.showLoading();
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_metrics_data',
                    view: this.state.currentView,
                    time_range: this.state.timeRange,
                    start_date: this.state.customDateStart,
                    end_date: this.state.customDateEnd,
                    category: this.state.filterCategory,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.state.metricData = response.data.metrics || {};
                        this.state.aiInsights = response.data.ai_insights || {};
                        this.state.lastUpdate = new Date();
                        
                        this.renderMetrics();
                        this.renderAIInsights();
                        
                        // Update last refresh time
                        $('.vortex-last-updated').text(
                            (this.config.i18n.lastUpdated || 'Last updated:') + ' ' + 
                            this.formatTime(this.state.lastUpdate)
                        );
                        
                        // Track for AI learning
                        this.trackAIInteraction('metrics_reloaded', {
                            view: this.state.currentView,
                            force_reload: forceReload,
                            data_points: this.countDataPoints(this.state.metricData),
                            timestamp: new Date().toISOString()
                        });
                    } else {
                        this.showError(response.data.message || this.config.i18n.loadError || 'Error loading metrics data');
                    }
                },
                error: () => {
                    this.showError(this.config.i18n.connectionError || 'Connection error');
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        },
        
        /**
         * Render metrics based on current view
         */
        renderMetrics: function() {
            // Clear existing charts
            this.destroyCharts();
            
            // Get the metrics container for the current view
            const $container = $(`.vortex-metrics-section[data-view="${this.state.currentView}"]`);
            
            if (!$container.length) {
                this.showError(this.config.i18n.invalidView || 'Invalid metrics view');
                return;
            }
            
            // Render different charts based on the current view
            switch (this.state.currentView) {
                case 'overview':
                    this.renderOverviewMetrics($container);
                    break;
                case 'artists':
                    this.renderArtistMetrics($container);
                    break;
                case 'collectors':
                    this.renderCollectorMetrics($container);
                    break;
                case 'marketplace':
                    this.renderMarketplaceMetrics($container);
                    break;
                case 'ai':
                    this.renderAIMetrics($container);
                    break;
                default:
                    this.showError(this.config.i18n.unsupportedView || 'Unsupported metrics view');
            }
            
            // Show the container
            $container.show();
            
            // Track metrics rendering for AI learning
            this.trackAIInteraction('metrics_rendered', {
                view: this.state.currentView,
                charts_count: Object.keys(this.state.chartInstances).length,
                timestamp: new Date().toISOString()
            });
        },
        
        /**
         * Render overview metrics
         * 
         * @param {jQuery} $container The container element
         */
        renderOverviewMetrics: function($container) {
            const data = this.state.metricData;
            
            if (!data.overview) {
                $container.html(`<p class="vortex-no-data">${this.config.i18n.noData || 'No data available for this view'}</p>`);
                return;
            }
            
            // Key performance indicators
            if (data.overview.kpi) {
                const $kpiContainer = $container.find('.vortex-kpi-container');
                $kpiContainer.empty();
                
                $.each(data.overview.kpi, (key, value) => {
                    const trend = value.trend > 0 ? 'up' : (value.trend < 0 ? 'down' : 'neutral');
                    const trendClass = trend === 'up' ? 'positive' : (trend === 'down' ? 'negative' : '');
                    
                    const $kpi = $(`
                        <div class="vortex-kpi-item">
                            <div class="vortex-kpi-icon"><span class="dashicons dashicons-${value.icon || 'chart-bar'}"></span></div>
                            <div class="vortex-kpi-content">
                                <h4>${value.label}</h4>
                                <div class="vortex-kpi-value">${value.formatted || value.value}</div>
                                <div class="vortex-kpi-trend ${trendClass}">
                                    <span class="dashicons dashicons-arrow-${trend}"></span>
                                    ${Math.abs(value.trend)}% ${trend === 'up' ? 'increase' : (trend === 'down' ? 'decrease' : '')}
                                </div>
                            </div>
                        </div>
                    `);
                    
                    $kpiContainer.append($kpi);
                });
            }
            
            // Activity over time chart
            if (data.overview.activity && data.overview.activity.labels && data.overview.activity.datasets) {
                const $canvas = $container.find('#vortex-activity-chart');
                
                if ($canvas.length) {
                    const ctx = $canvas[0].getContext('2d');
                    
                    this.state.chartInstances.activity = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.overview.activity.labels,
                            datasets: data.overview.activity.datasets.map((dataset, index) => ({
                                label: dataset.label,
                                data: dataset.data,
                                borderColor: this.config.chartColors[index % this.config.chartColors.length],
                                backgroundColor: this.hexToRgba(this.config.chartColors[index % this.config.chartColors.length], 0.1),
                                borderWidth: 2,
                                tension: 0.2,
                                fill: true
                            }))
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    callbacks: {
                                        label: (context) => {
                                            // Track for AI learning on tooltip display
                                            this.debounce(() => {
                                                this.trackAIInteraction('chart_tooltip_viewed', {
                                                    chart: 'activity',
                                                    dataset: context.dataset.label,
                                                    value: context.raw,
                                                    timestamp: new Date().toISOString()
                                                });
                                            }, 1000)();
                                            
                                            return context.dataset.label + ': ' + context.raw;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true
                                }
                            },
                            interaction: {
                                mode: 'nearest',
                                axis: 'x',
                                intersect: false
                            }
                        }
                    });
                }
            }
            
            // Distribution chart
            if (data.overview.distribution && data.overview.distribution.labels && data.overview.distribution.data) {
                const $canvas = $container.find('#vortex-distribution-chart');
                
                if ($canvas.length) {
                    const ctx = $canvas[0].getContext('2d');
                    
                    this.state.chartInstances.distribution = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: data.overview.distribution.labels,
                            datasets: [{
                                data: data.overview.distribution.data,
                                backgroundColor: this.config.chartColors.slice(0, data.overview.distribution.labels.length),
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: (context) => {
                                            // Track for AI learning on tooltip display
                                            this.debounce(() => {
                                                this.trackAIInteraction('chart_tooltip_viewed', {
                                                    chart: 'distribution',
                                                    dataset: 'distribution',
                                                    value: context.raw,
                                                    timestamp: new Date().toISOString()
                                                });
                                            }, 1000)();
                                            
                                            const value = context.raw;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return context.label + ': ' + value + ' (' + percentage + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
            
            // Top items table
            if (data.overview.topItems && data.overview.topItems.length) {
                const $tableContainer = $container.find('.vortex-top-items-table');
                $tableContainer.empty();
                
                const $table = $(`
                    <table class="vortex-data-table">
                        <thead>
                            <tr>
                                <th>${this.config.i18n.rank || 'Rank'}</th>
                                <th>${this.config.i18n.item || 'Item'}</th>
                                <th>${this.config.i18n.category || 'Category'}</th>
                                <th>${this.config.i18n.value || 'Value'}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                `);
                
                const $tbody = $table.find('tbody');
                
                $.each(data.overview.topItems, (index, item) => {
                    const $row = $(`
                        <tr>
                            <td>${index + 1}</td>
                            <td>${item.name}</td>
                            <td>${item.category}</td>
                            <td>${item.formatted_value || item.value}</td>
                        </tr>
                    `);
                    
                    $tbody.append($row);
                });
                
                $tableContainer.append($table);
                
                // Add click handlers for rows
                $table.find('tbody tr').on('click', (e) => {
                    const index = $(e.currentTarget).index();
                    const item = data.overview.topItems[index];
                    
                    if (item && item.link) {
                        window.location.href = item.link;
                    }
                    
                    // Track for AI learning
                    this.trackAIInteraction('top_item_clicked', {
                        item_name: item.name,
                        item_category: item.category,
                        item_value: item.value,
                        timestamp: new Date().toISOString()
                    });
                });
            }
        },
        
        /**
         * Render artist metrics
         * 
         * @param {jQuery} $container The container element
         */
        renderArtistMetrics: function($container) {
            const data = this.state.metricData;
            
            if (!data.artists) {
                $container.html(`<p class="vortex-no-data">${this.config.i18n.noData || 'No data available for this view'}</p>`);
                return;
            }
            
            // Artist growth chart
            if (data.artists.growth && data.artists.growth.labels && data.artists.growth.data) {
                const $canvas = $container.find('#vortex-artist-growth-chart');
                
                if ($canvas.length) {
                    const ctx = $canvas[0].getContext('2d');
                    
                    this.state.chartInstances.artistGrowth = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.artists.growth.labels,
                            datasets: [{
                                label: this.config.i18n.newArtists || 'New Artists',
                                data: data.artists.growth.data,
                                borderColor: this.config.chartColors[0],
                                backgroundColor: this.hexToRgba(this.config.chartColors[0], 0.1),
                                borderWidth: 2,
                                tension: 0.2,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: (context) => {
                                            return (this.config.i18n.newArtists || 'New Artists') + ': ' + context.raw;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            }
            
            // Artist productivity chart
            if (data.artists.productivity && data.artists.productivity.labels && data.artists.productivity.data) {
                const $canvas = $container.find('#vortex-artist-productivity-chart');
                
                if ($canvas.length) {
                    const ctx = $canvas[0].getContext('2d');
                    
                    this.state.chartInstances.artistProductivity = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.artists.productivity.labels,
                            datasets: [{
                                label: this.config.i18n.artworksCreated || 'Artworks Created',
                                data: data.artists.productivity.data,
                                backgroundColor: this.config.chartColors[1],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            }
            
            // Top artists table
            if (data.artists.topArtists && data.artists.topArtists.length) {
                const $tableContainer = $container.find('.vortex-top-artists-table');
                $tableContainer.empty();
                
                const $table = $(`
                    <table class="vortex-data-table">
                        <thead>
                            <tr>
                                <th>${this.config.i18n.rank || 'Rank'}</th>
                                <th>${this.config.i18n.artist || 'Artist'}</th>
                                <th>${this.config.i18n.artworks || 'Artworks'}</th>
                                <th>${this.config.i18n.sales || 'Sales'}</th>
                                <th>${this.config.i18n.averagePrice || 'Avg. Price'}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                `);
                
                const $tbody = $table.find('tbody');
                
                $.each(data.artists.topArtists, (index, artist) => {
                    const $row = $(`
                        <tr data-artist-id="${artist.id}">
                            <td>${index + 1}</td>
                            <td>
                                <div class="vortex-artist-info">
                                    <img src="${artist.avatar}" alt="${artist.name}" class="vortex-artist-avatar" />
                                    <span>${artist.name}</span>
                                </div>
                            </td>
                            <td>${artist.artworks_count}</td>
                            <td>${artist.sales_count}</td>
                            <td>${artist.average_price}</td>
                        </tr>
                    `);
                    
                    $tbody.append($row);
                });
                
                $tableContainer.append($table);
                
                // Add click handlers for rows
                $table.find('tbody tr').on('click', (e) => {
                    const artistId = $(e.currentTarget).data('artistId');
                    
                    if (artistId) {
                        window.location.href = `${vortex_metrics_data.admin_url}?page=vortex-artist-profile&id=${artistId}`;
                    }
                    
                    // Track for AI learning
                    this.trackAIInteraction('artist_profile_clicked', {
                        artist_id: artistId,
                        from_view: 'metrics',
                        timestamp: new Date().toISOString()
                    });
                });
            }
        },
        
        /**
         * Render collector metrics
         * 
         * @param {jQuery} $container The container element
         */
        renderCollectorMetrics: function($container) {
            const data = this.state.metricData;
            
            if (!data.collectors) {
                $container.html(`<p class="vortex-no-data">${this.config.i18n.noData || 'No data available for this view'}</p>`);
                return;
            }
            
            // Implement collector metrics rendering (omitted for brevity)
            // Similar pattern to artist metrics with different data
        },
        
        /**
         * Render marketplace metrics
         * 
         * @param {jQuery} $container The container element
         */
        renderMarketplaceMetrics: function($container) {
            const data = this.state.metricData;
            
            if (!data.marketplace) {
                $container.html(`<p class="vortex-no-data">${this.config.i18n.noData || 'No data available for this view'}</p>`);
                return;
            }
            
            // Implement marketplace metrics rendering (omitted for brevity)
            // Similar pattern to overview metrics with different data
        },
        
        /**
         * Render AI metrics
         * 
         * @param {jQuery} $container The container element
         */
        renderAIMetrics: function($container) {
            const data = this.state.metricData;
            
            if (!data.ai) {
                $container.html(`<p class="vortex-no-data">${this.config.i18n.noData || 'No data available for this view'}</p>`);
                return;
            }
            
            // AI agent activity chart
            if (data.ai.agentActivity && data.ai.agentActivity.labels && data.ai.agentActivity.datasets) {
                const $canvas = $container.find('#vortex-ai-activity-chart');
                
                if ($canvas.length) {
                    const ctx = $canvas[0].getContext('2d');
                    
                    this.state.chartInstances.aiActivity = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.ai.agentActivity.labels,
                            datasets: data.ai.agentActivity.datasets.map((dataset, index) => ({
                                label: dataset.label,
                                data: dataset.data,
                                borderColor: this.config.chartColors[index % this.config.chartColors.length],
                                backgroundColor: this.hexToRgba(this.config.chartColors[index % this.config.chartColors.length], 0.1),
                                borderWidth: 2,
                                tension: 0.2,
                                fill: true
                            }))
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            }
            
            // AI learning progress
            if (data.ai.learningProgress && data.ai.learningProgress.length) {
                const $progressContainer = $container.find('.vortex-ai-learning-progress');
                $progressContainer.empty();
                
                $.each(data.ai.learningProgress, (index, agent) => {
                    const $progress = $(`
                        <div class="vortex-ai-progress-item">
                            <div class="vortex-ai-progress-header">
                                <h4>${agent.name}</h4>
                                <span>${agent.percentage}%</span>
                            </div>
                            <div class="vortex-ai-progress-bar">
                                <div class="vortex-ai-progress-fill" style="width: ${agent.percentage}%; background-color: ${this.config.chartColors[index % this.config.chartColors.length]}"></div>
                            </div>
                            <div class="vortex-ai-progress-stats">
                                <div>${this.config.i18n.interactions || 'Interactions'}: ${agent.interactions}</div>
                                <div>${this.config.i18n.modelUpdates || 'Model Updates'}: ${agent.model_updates}</div>
                            </div>
                        </div>
                    `);
                    
                    $progressContainer.append($progress);
                });
            }
            
            // Top AI interactions
            if (data.ai.topInteractions && data.ai.topInteractions.length) {
                const $listContainer = $container.find('.vortex-ai-top-interactions');
                $listContainer.empty();
                
                const $list = $('<ul class="vortex-interaction-list"></ul>');
                
                $.each(data.ai.topInteractions, (index, interaction) => {
                    const $item = $(`
                        <li>
                            <div class="vortex-interaction-type">${interaction.type}</div>
                            <div class="vortex-interaction-count">${interaction.count}</div>
                            <div class="vortex-interaction-context">${interaction.context}</div>
                        </li>
                    `);
                    
                    $list.append($item);
                });
                
                $listContainer.append($list);
            }
        },
        
        /**
         * Render AI insights from CLOE and BusinessStrategist
         */
        renderAIInsights: function() {
            const insights = this.state.aiInsights;
            const $container = $('.vortex-ai-insights-content');
            
            if (!$container.length || !insights || Object.keys(insights).length === 0) {
                $('.vortex-ai-insights-container').hide();
                return;
            }
            
            $container.empty();
            
            // Add CLOE insights
            if (insights.cloe) {
                const $cloeInsights = $(`
                    <div class="vortex-cloe-insights">
                        <h3>
                            <span class="dashicons dashicons-chart-line"></span>
                            ${this.config.i18n.cloeInsights || 'CLOE Insights'}
                        </h3>
                        <div class="vortex-cloe-content"></div>
                    </div>
                `);
                
                const $cloeContent = $cloeInsights.find('.vortex-cloe-content');
                
                if (insights.cloe.summary) {
                    $cloeContent.append(`<p class="vortex-insight-summary">${insights.cloe.summary}</p>`);
                }
                
                if (insights.cloe.key_findings && insights.cloe.key_findings.length) {
                    const $findings = $('<ul class="vortex-key-findings"></ul>');
                    
                    $.each(insights.cloe.key_findings, (index, finding) => {
                        $findings.append(`<li>${finding}</li>`);
                    });
                    
                    $cloeContent.append($findings);
                }
                
                $container.append($cloeInsights);
            }
            
            // Add BusinessStrategist insights
            if (insights.business) {
                const $businessInsights = $(`
                    <div class="vortex-business-insights">
                        <h3>
                            <span class="dashicons dashicons-businessman"></span>
                            ${this.config.i18n.businessInsights || 'Business Insights'}
                        </h3>
                        <div class="vortex-business-content"></div>
                    </div>
                `);
                
                const $businessContent = $businessInsights.find('.vortex-business-content');
                
                if (insights.business.summary) {
                    $businessContent.append(`<p class="vortex-insight-summary">${insights.business.summary}</p>`);
                }
                
                if (insights.business.recommendations && insights.business.recommendations.length) {
                    const $recommendations = $('<div class="vortex-recommendations"></div>');
                    $recommendations.append(`<h4>${this.config.i18n.recommendations || 'Recommendations'}</h4>`);
                    
                    const $recList = $('<ul></ul>');
                    
                    $.each(insights.business.recommendations, (index, recommendation) => {
                        $recList.append(`<li>${recommendation}</li>`);
                    });
                    
                    $recommendations.append($recList);
                    $businessContent.append($recommendations);
                }
                
                if (insights.business.market_opportunities && insights.business.market_opportunities.length) {
                    const $opportunities = $('<div class="vortex-opportunities"></div>');
                    $opportunities.append(`<h4>${this.config.i18n.marketOpportunities || 'Market Opportunities'}</h4>`);
                    
                    const $oppList = $('<ul></ul>');
                    
                    $.each(insights.business.market_opportunities, (index, opportunity) => {
                        $oppList.append(`<li>${opportunity}</li>`);
                    });
                    
                    $opportunities.append($oppList);
                    $businessContent.append($opportunities);
                }
                
                $container.append($businessInsights);
            }
            
            // Add HURAII creative insights if available
            if (insights.huraii) {
                const $huraiInsights = $(`
                    <div class="vortex-huraii-insights">
                        <h3>
                            <span class="dashicons dashicons-art"></span>
                            ${this.config.i18n.huraiiInsights || 'HURAII Creative Insights'}
                        </h3>
                        <div class="vortex-huraii-content"></div>
                    </div>
                `);
                
                const $huraiContent = $huraiInsights.find('.vortex-huraii-content');
                
                if (insights.huraii.summary) {
                    $huraiContent.append(`<p class="vortex-insight-summary">${insights.huraii.summary}</p>`);
                }
                
                if (insights.huraii.trends && insights.huraii.trends.length) {
                    const $trends = $('<div class="vortex-creative-trends"></div>');
                    $trends.append(`<h4>${this.config.i18n.creativeTrends || 'Creative Trends'}</h4>`);
                    
                    const $trendList = $('<ul></ul>');
                    
                    $.each(insights.huraii.trends, (index, trend) => {
                        $trendList.append(`<li>${trend}</li>`);
                    });
                    
                    $trends.append($trendList);
                    $huraiContent.append($trends);
                }
                
                $container.append($huraiInsights);
            }
            
            // Show insights container
            $('.vortex-ai-insights-container').show();
            
            // Track insights viewing for AI learning
            this.trackAIInteraction('ai_insights_viewed', {
                has_cloe: !!insights.cloe,
                has_business: !!insights.business,
                has_huraii: !!insights.huraii,
                timestamp: new Date().toISOString()
            });
        },
        
        /**
         * Destroy existing charts
         */
        destroyCharts: function() {
            const chartInstances = this.state.chartInstances;
            for (const chartId in chartInstances) {
                if (chartInstances[chartId]) {
                    chartInstances[chartId].destroy();
                    delete chartInstances[chartId];
                }
            }
        },
        
        /**
         * Show loading message
         */
        showLoading: function() {
            this.state.isLoading = true;
            $('.vortex-loading-overlay').show();
        },
        
        /**
         * Hide loading message
         */
        hideLoading: function() {
            this.state.isLoading = false;
            $('.vortex-loading-overlay').hide();
        },
        
        /**
         * Show error message
         */
        showError: function(message) {
            const $messageContainer = $('.vortex-messages');
            
            if (!$messageContainer.length) {
                return;
            }
            
            // Create error message element
            const $error = $('<div>', {
                class: 'vortex-message vortex-error',
                text: message
            });
            
            // Add to container
            $messageContainer.append($error);
            $messageContainer.show();
            
            // Auto-hide after 8 seconds (longer for errors)
            setTimeout(function() {
                $error.fadeOut(function() {
                    $(this).remove();
                    if ($messageContainer.children().length === 0) {
                        $messageContainer.hide();
                    }
                });
            }, 8000);
            
            // Track error for AI learning
            this.trackAIInteraction('ui_error_shown', {
                error_message: message,
                timestamp: new Date().toISOString()
            });
        },
        
        /**
         * Format time
         */
        formatTime: function(date) {
            if (!date) return '';
            
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            
            return `${hours}:${minutes}:${seconds}`;
        },
        
        /**
         * Count data points
         */
        countDataPoints: function(data) {
            let count = 0;
            
            // Count data points in different data types
            if (data) {
                Object.keys(data).forEach(view => {
                    if (data[view]) {
                        // Count KPI items
                        if (data[view].kpi) {
                            count += Object.keys(data[view].kpi).length;
                        }
                        
                        // Count chart data
                        if (data[view].activity && data[view].activity.datasets) {
                            data[view].activity.datasets.forEach(dataset => {
                                count += dataset.data ? dataset.data.length : 0;
                            });
                        }
                        
                        // Count distribution data
                        if (data[view].distribution && data[view].distribution.data) {
                            count += data[view].distribution.data.length;
                        }
                        
                        // Count table data
                        if (data[view].topItems) {
                            count += data[view].topItems.length;
                        }
                        
                        if (data[view].topArtists) {
                            count += data[view].topArtists.length;
                        }
                        
                        // Count AI specific data
                        if (view === 'ai') {
                            if (data[view].learningProgress) {
                                count += data[view].learningProgress.length;
                            }
                            
                            if (data[view].topInteractions) {
                                count += data[view].topInteractions.length;
                            }
                        }
                    }
                });
            }
            
            return count;
        },
        
        /**
         * Export data
         */
        exportData: function(format) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_export_metrics',
                    format: format,
                    view: this.state.currentView,
                    time_range: this.state.timeRange,
                    start_date: this.state.customDateStart,
                    end_date: this.state.customDateEnd,
                    category: this.state.filterCategory,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success && response.data.download_url) {
                        // Create temporary link and trigger download
                        const $link = $('<a>', {
                            href: response.data.download_url,
                            download: response.data.filename || `vortex-metrics-${this.state.currentView}.${format}`
                        });
                        
                        $('body').append($link);
                        $link[0].click();
                        $link.remove();
                        
                        this.showMessage(this.config.i18n.exportSuccess || 'Export successful');
                    } else {
                        this.showError(response.data.message || this.config.i18n.exportError || 'Error exporting data');
                    }
                },
                error: () => {
                    this.showError(this.config.i18n.connectionError || 'Connection error');
                }
            });
        },
        
        /**
         * Setup refresh timer
         */
        setupRefreshTimer: function() {
            // Clear any existing timer
            if (this.state.refreshTimer) {
                clearInterval(this.state.refreshTimer);
            }
            
            // Set up new timer
            this.state.refreshTimer = setInterval(() => {
                this.reloadData();
            }, this.config.refreshInterval);
            
            // Track for AI learning
            this.trackAIInteraction('refresh_timer_started', {
                interval: this.config.refreshInterval,
                timestamp: new Date().toISOString()
            });
        },
        
        /**
         * Apply URL parameters
         */
        applyUrlParameters: function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Set time range if specified
            if (urlParams.has('time_range')) {
                const timeRange = urlParams.get('time_range');
                if (['7d', '30d', '90d', '1y', 'custom'].includes(timeRange)) {
                    this.state.timeRange = timeRange;
                    $('.vortex-time-range-selector button').removeClass('active');
                    $(`.vortex-time-range-selector button[data-range="${timeRange}"]`).addClass('active');
                    
                    // Show custom date fields if needed
                    if (timeRange === 'custom') {
                        $('.vortex-custom-date-range').show();
                        
                        // Set custom date range if specified
                        if (urlParams.has('start_date') && urlParams.has('end_date')) {
                            this.state.customDateStart = urlParams.get('start_date');
                            this.state.customDateEnd = urlParams.get('end_date');
                            
                            $('#vortex-date-start').val(this.state.customDateStart);
                            $('#vortex-date-end').val(this.state.customDateEnd);
                        }
                    } else {
                        $('.vortex-custom-date-range').hide();
                    }
                }
            }
            
            // Set view if specified
            if (urlParams.has('view')) {
                const view = urlParams.get('view');
                if (['overview', 'artists', 'collectors', 'marketplace', 'ai'].includes(view)) {
                    this.state.currentView = view;
                    $('.vortex-metrics-view-selector button').removeClass('active');
                    $(`.vortex-metrics-view-selector button[data-view="${view}"]`).addClass('active');
                }
            }
            
            // Set category filter if specified
            if (urlParams.has('category')) {
                this.state.filterCategory = urlParams.get('category');
                $('#vortex-category-filter').val(this.state.filterCategory);
            }
            
            // Reload data with these parameters
            this.reloadData(true);
        },
        
        /**
         * Track AI interaction
         */
        trackAIInteraction: function(action, data) {
            // Skip if learning is disabled
            if (!this.config.learningEnabled) {
                return;
            }
            
            // Add to interaction history
            this.state.interactionHistory.push({
                action: action,
                data: data,
                timestamp: new Date().toISOString()
            });
            
            // Send to server for AI learning if action is significant
            // or if we've accumulated enough interactions
            const significantActions = [
                'metrics_view_changed',
                'ai_insights_viewed',
                'custom_date_range_set',
                'metrics_exported',
                'artist_profile_clicked',
                'collector_profile_clicked'
            ];
            
            if (significantActions.includes(action) || this.state.interactionHistory.length >= 15) {
                // Create a batch of interactions to send
                const batch = this.state.interactionHistory.slice(-15);
                
                // Clear the history to avoid duplicate sends
                // but keep the last 5 for context
                if (this.state.interactionHistory.length > 5) {
                    this.state.interactionHistory = this.state.interactionHistory.slice(-5);
                }
                
                // Send asynchronously to avoid blocking UI
                setTimeout(() => {
                    this.sendAILearningData(batch);
                }, 100);
            }
        },
        
        /**
         * Send AI learning data to the server
         * 
         * @param {Array} interactions Array of interaction data
         */
        sendAILearningData: function(interactions) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_metrics_learn',
                    interactions: interactions,
                    user_id: this.config.userId,
                    session_id: this.generateSessionId(),
                    nonce: this.config.nonce
                },
                success: response => {
                    if (response.success && response.data.insights) {
                        // Apply any AI insights returned from server
                        this.updateAIInsights(response.data.insights);
                    }
                },
                // Use minimal error handling to avoid impacting user experience
                error: () => {
                    console.warn('AI learning data could not be sent');
                }
            });
        },
        
        /**
         * Update AI insights based on learning
         * 
         * @param {Object} insights New insights from AI
         */
        updateAIInsights: function(insights) {
            // Update insights if they exist
            if (insights && Object.keys(insights).length > 0) {
                // Merge with existing insights
                this.state.aiInsights = {
                    ...this.state.aiInsights,
                    ...insights
                };
                
                // Re-render insights
                this.renderAIInsights();
                
                // Show notification if new significant insights are available
                if (insights.notification) {
                    this.showMessage(insights.notification);
                }
            }
        },
        
        /**
         * Generate a unique session ID
         * 
         * @returns {string} Session ID
         */
        generateSessionId: function() {
            // Use stored session ID if available
            if (this._sessionId) {
                return this._sessionId;
            }
            
            // Generate a new session ID
            const timestamp = new Date().getTime();
            const randomPart = Math.floor(Math.random() * 1000000);
            this._sessionId = `metrics_${timestamp}_${randomPart}`;
            
            return this._sessionId;
        },
        
        /**
         * Show a message to the user
         * 
         * @param {string} message The message to show
         */
        showMessage: function(message) {
            const $messageContainer = $('.vortex-messages');
            
            if (!$messageContainer.length) {
                return;
            }
            
            // Create message element
            const $message = $('<div>', {
                class: 'vortex-message',
                text: message
            });
            
            // Add to container
            $messageContainer.append($message);
            $messageContainer.show();
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                    if ($messageContainer.children().length === 0) {
                        $messageContainer.hide();
                    }
                });
            }, 5000);
        },
        
        /**
         * Convert hex color to rgba
         * 
         * @param {string} hex Hex color code
         * @param {number} alpha Alpha value (0-1)
         * @returns {string} RGBA color string
         */
        hexToRgba: function(hex, alpha) {
            // Default fallback color
            if (!hex) {
                return `rgba(59, 130, 246, ${alpha})`;
            }
            
            // Remove # if present
            hex = hex.replace('#', '');
            
            // Convert 3-digit hex to 6-digit
            if (hex.length === 3) {
                hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
            }
            
            // Get RGB values
            const r = parseInt(hex.substring(0, 2), 16);
            const g = parseInt(hex.substring(2, 4), 16);
            const b = parseInt(hex.substring(4, 6), 16);
            
            // Return rgba string
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        },
        
        /**
         * Debounce function to limit function calls
         * 
         * @param {Function} func Function to debounce
         * @param {number} wait Wait time in milliseconds
         * @returns {Function} Debounced function
         */
        debounce: function(func, wait) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        // Initialize only if the necessary elements exist
        if ($('.vortex-metrics-container').length) {
            VortexMetricsDisplay.init();
        }
    });
    
    // Expose to global scope for external access
    window.VortexMetricsDisplay = VortexMetricsDisplay;
    
})(jQuery); 