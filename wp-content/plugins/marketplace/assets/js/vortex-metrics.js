/**
 * VORTEX Metrics Visualization
 * Handles DAO metrics visualization with Chart.js
 */

const VortexMetrics = (function() {
    // Private variables
    let _charts = {};
    let _metrics = {};
    let _timeframe = 'weekly';
    
    /**
     * Initialize metrics visualization
     */
    function init() {
        jQuery(document).ready(function($) {
            // Set up Chart.js defaults
            setupChartDefaults();
            
            // Set up timeframe selectors
            setupTimeframeSelectors();
            
            // Load initial metrics
            if ($('.vortex-metrics-dashboard').length) {
                loadMetrics(_timeframe);
            }
            
            // Handle export button
            $('.vortex-export-metrics').on('click', function(e) {
                e.preventDefault();
                exportMetricsCSV();
            });
            
            // Handle refresh button
            $('.vortex-refresh-metrics').on('click', function(e) {
                e.preventDefault();
                refreshMetrics();
            });
        });
    }
    
    /**
     * Setup Chart.js defaults
     */
    function setupChartDefaults() {
        // Global chart options
        Chart.defaults.font.family = "'Inter', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#6b7280';
        Chart.defaults.elements.line.borderWidth = 2;
        Chart.defaults.elements.line.tension = 0.4;
        Chart.defaults.elements.point.radius = 3;
        Chart.defaults.elements.point.hoverRadius = 5;
        
        // Disable animations for better performance on slower devices
        const deviceWidth = window.innerWidth;
        if (deviceWidth < 768) {
            Chart.defaults.animation = false;
        }
    }
    
    /**
     * Setup timeframe selectors
     */
    function setupTimeframeSelectors() {
        jQuery(document).ready(function($) {
            $('.vortex-timeframe-option').on('click', function(e) {
                e.preventDefault();
                
                const timeframe = $(this).data('timeframe');
                
                // Update active class
                $('.vortex-timeframe-option').removeClass('active');
                $(this).addClass('active');
                
                // Load metrics with selected timeframe
                _timeframe = timeframe;
                loadMetrics(timeframe);
            });
        });
    }
    
    /**
     * Load metrics data
     */
    function loadMetrics(timeframe) {
        jQuery(document).ready(function($) {
            const $loadingIndicator = $('.vortex-metrics-loading');
            
            // Show loading indicator
            $loadingIndicator.show();
            
            // Make AJAX request
            $.ajax({
                url: vortexParams.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'vortex_get_dao_metrics',
                    nonce: vortexParams.nonce,
                    period: timeframe,
                    limit: 30
                },
                success: function(response) {
                    // Hide loading indicator
                    $loadingIndicator.hide();
                    
                    if (response.success) {
                        // Store metrics
                        _metrics = response.data;
                        
                        // Update metrics dashboard
                        updateMetricsDashboard(_metrics);
                        
                        // Initialize charts
                        initCharts(_metrics);
                    } else {
                        console.error('Error loading metrics:', response.data.message);
                        $('.vortex-metrics-error').text('Error loading metrics. Please try again.').show();
                    }
                },
                error: function() {
                    $loadingIndicator.hide();
                    $('.vortex-metrics-error').text('Error loading metrics. Please try again.').show();
                }
            });
        });
    }
    
    /**
     * Update metrics dashboard
     */
    function updateMetricsDashboard(metrics) {
        jQuery(document).ready(function($) {
            // Update metrics cards
            updateMetricsCards(metrics.latest);
            
            // Update changes indicators
            updateChangesIndicators(metrics.changes);
        });
    }
    
    /**
     * Update metrics cards
     */
    function updateMetricsCards(latestMetrics) {
        jQuery(document).ready(function($) {
            if (!latestMetrics) return;
            
            // Update token metrics
            $('.vortex-metric-token-price').text('$' + numberFormat(latestMetrics.token_price, 4));
            $('.vortex-metric-market-cap').text('$' + abbreviateNumber(latestMetrics.market_cap));
            $('.vortex-metric-total-supply').text(abbreviateNumber(latestMetrics.total_supply) + ' TOLA');
            $('.vortex-metric-circulating-supply').text(abbreviateNumber(latestMetrics.circulating_supply) + ' TOLA');
            
            // Update treasury metrics
            $('.vortex-metric-treasury-balance').text('$' + abbreviateNumber(latestMetrics.treasury_balance));
            $('.vortex-metric-tvl').text('$' + abbreviateNumber(latestMetrics.total_value_locked));
            
            // Update governance metrics
            $('.vortex-metric-total-holders').text(numberFormat(latestMetrics.total_holders));
            $('.vortex-metric-active-proposals').text(latestMetrics.active_proposals);
            $('.vortex-metric-voter-participation').text(latestMetrics.voter_participation.toFixed(2) + '%');
            
            // Update timestamp
            $('.vortex-metrics-timestamp').text(
                'Last updated: ' + new Date(latestMetrics.recorded_at).toLocaleString()
            );
        });
    }
    
    /**
     * Update changes indicators
     */
    function updateChangesIndicators(changes) {
        jQuery(document).ready(function($) {
            if (!changes) return;
            
            // Loop through all change indicators
            for (const [metric, change] of Object.entries(changes)) {
                const $indicator = $(`.vortex-change-${metric}`);
                
                if ($indicator.length) {
                    const isPositive = change.percentage > 0;
                    const isNeutral = change.percentage === 0;
                    
                    // Update indicator text
                    let changeText = '';
                    
                    if (isNeutral) {
                        changeText = '0%';
                    } else {
                        changeText = (isPositive ? '+' : '') + change.percentage.toFixed(2) + '%';
                    }
                    
                    $indicator.text(changeText);
                    
                    // Update indicator class
                    $indicator.removeClass('positive negative neutral');
                    
                    if (isNeutral) {
                        $indicator.addClass('neutral');
                    } else if (isPositive) {
                        $indicator.addClass('positive');
                    } else {
                        $indicator.addClass('negative');
                    }
                }
            }
        });
    }
    
    /**
     * Initialize charts
     */
    function initCharts(metrics) {
        if (!metrics.history || !metrics.history.length) return;
        
        // Destroy existing charts
        for (const chartId in _charts) {
            if (_charts[chartId]) {
                _charts[chartId].destroy();
            }
        }
        
        _charts = {};
        
        // Initialize token price chart
        initTokenPriceChart(metrics.history);
        
        // Initialize market cap chart
        initMarketCapChart(metrics.history);
        
        // Initialize TVL chart
        initTVLChart(metrics.history);
        
        // Initialize participation chart
        initParticipationChart(metrics.history);
    }
    
    /**
     * Initialize token price chart
     */
    function initTokenPriceChart(history) {
        const ctx = document.getElementById('vortex-token-price-chart');
        if (!ctx) return;
        
        const data = prepareChartData(history, 'token_price');
        
        _charts.tokenPrice = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Token Price (USD)',
                    data: data.values,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(4);
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Price: $' + context.raw.toFixed(4);
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Initialize market cap chart
     */
    function initMarketCapChart(history) {
        const ctx = document.getElementById('vortex-market-cap-chart');
        if (!ctx) return;
        
        const data = prepareChartData(history, 'market_cap');
        
        _charts.marketCap = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Market Cap (USD)',
                    data: data.values,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    fill: true
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return '$' + abbreviateNumber(value);
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Market Cap: $' + numberFormat(context.raw);
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Initialize TVL chart
     */
    function initTVLChart(history) {
        const ctx = document.getElementById('vortex-tvl-chart');
        if (!ctx) return;
        
        const data = prepareChartData(history, 'total_value_locked');
        
        _charts.tvl = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Total Value Locked (USD)',
                    data: data.values,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return '$' + abbreviateNumber(value);
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'TVL: $' + numberFormat(context.raw);
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Initialize participation chart
     */
    function initParticipationChart(history) {
        const ctx = document.getElementById('vortex-participation-chart');
        if (!ctx) return;
        
        const data = prepareChartData(history, 'voter_participation');
        
        _charts.participation = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Voter Participation (%)',
                    data: data.values,
                    borderColor: '#ec4899',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    fill: true
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Participation: ' + context.raw.toFixed(2) + '%';
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Prepare chart data
     */
    function prepareChartData(history, field) {
        const labels = [];
        const values = [];
        
        // Ensure the data is sorted by date (oldest to newest)
        const sortedHistory = [...history].sort((a, b) => {
            return new Date(a.recorded_at) - new Date(b.recorded_at);
        });
        
        sortedHistory.forEach(record => {
            const date = new Date(record.recorded_at);
            labels.push(formatDate(date));
            values.push(record[field]);
        });
        
        return { labels, values };
    }
    
    /**
     * Refresh metrics
     */
    function refreshMetrics() {
        loadMetrics(_timeframe);
    }
    
    /**
     * Export metrics as CSV
     */
    function exportMetricsCSV() {
        jQuery.ajax({
            url: vortexParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_export_dao_metrics',
                nonce: vortexParams.nonce,
                timeframe: _timeframe
            },
            success: function(response) {
                if (response.success) {
                    // Create a temporary link to download the CSV
                    const link = document.createElement('a');
                    link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(response.data.csv);
                    link.download = 'vortex_dao_metrics.csv';
                    link.style.display = 'none';
                    
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert('Error exporting metrics: ' + response.data.message);
                }
            },
            error: function() {
                alert('Error exporting metrics. Please try again.');
            }
        });
    }
    
    /**
     * Format date for chart labels
     */
    function formatDate(date) {
        const options = { month: 'short', day: 'numeric' };
        
        if (_timeframe === 'yearly') {
            options.month = 'short';
            options.year = 'numeric';
        } else if (_timeframe === 'monthly') {
            options.month = 'short';
            options.day = 'numeric';
        } else {
            options.month = 'short';
            options.day = 'numeric';
        }
        
        return date.toLocaleDateString(undefined, options);
    }
    
    /**
     * Format number with commas
     */
    function numberFormat(number, decimals = 0) {
        return parseFloat(number).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }
    
    /**
     * Abbreviate large numbers
     */
    function abbreviateNumber(number) {
        if (number >= 1e9) {
            return (number / 1e9).toFixed(2) + 'B';
        } else if (number >= 1e6) {
            return (number / 1e6).toFixed(2) + 'M';
        } else if (number >= 1e3) {
            return (number / 1e3).toFixed(2) + 'K';
        }
        
        return number.toFixed(2);
    }
    
    // Public API
    return {
        init: init,
        refreshMetrics: refreshMetrics,
        exportMetricsCSV: exportMetricsCSV
    };
})();

// Initialize on document ready
jQuery(document).ready(function() {
    VortexMetrics.init();
}); 