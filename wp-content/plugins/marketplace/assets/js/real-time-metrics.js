/**
 * Real-Time Blockchain Metrics
 * Handles the auto-refresh functionality for blockchain metrics
 */
(function($) {
    'use strict';
    
    // Variables
    var refreshTimer = null;
    var isRefreshing = false;
    
    // Initialize when document is ready
    $(document).ready(function() {
        initRefreshButton();
        setupAutoRefresh();
    });
    
    /**
     * Initialize the refresh button
     */
    function initRefreshButton() {
        $('.vortex-refresh-button').on('click', function() {
            if (!isRefreshing) {
                refreshMetrics();
            }
        });
    }
    
    /**
     * Setup auto-refresh functionality
     */
    function setupAutoRefresh() {
        // Check if auto refresh is enabled
        if (vortexRealTimeMetrics.autoRefresh) {
            // Start the timer
            startRefreshTimer();
            
            // Add visual indicator for auto-refresh
            $('.vortex-auto-refresh-status').show();
        } else {
            $('.vortex-auto-refresh-status').hide();
        }
    }
    
    /**
     * Start the refresh timer
     */
    function startRefreshTimer() {
        // Clear any existing timer
        if (refreshTimer) {
            clearTimeout(refreshTimer);
        }
        
        // Set up new timer
        refreshTimer = setTimeout(function() {
            refreshMetrics();
        }, vortexRealTimeMetrics.refreshInterval);
    }
    
    /**
     * Refresh the metrics data
     */
    function refreshMetrics() {
        if (isRefreshing) {
            return;
        }
        
        isRefreshing = true;
        var $refreshButton = $('.vortex-refresh-button');
        var originalText = $refreshButton.text();
        
        // Update button state
        $refreshButton.text(vortexRealTimeMetrics.i18n.updating).prop('disabled', true);
        
        // Make AJAX request to get updated metrics
        $.ajax({
            url: vortexRealTimeMetrics.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_get_blockchain_metrics',
                nonce: vortexRealTimeMetrics.nonce,
                metric: $('.vortex-real-time-metrics').data('metric')
            },
            success: function(response) {
                if (response.success) {
                    updateMetricsDisplay(response.data);
                    
                    // Update last updated timestamp
                    var now = new Date();
                    var timeString = now.getHours().toString().padStart(2, '0') + ':' +
                                     now.getMinutes().toString().padStart(2, '0') + ':' +
                                     now.getSeconds().toString().padStart(2, '0');
                    $('.vortex-last-updated time').text(timeString).attr('datetime', now.toISOString());
                    
                    // If auto-refresh is enabled, restart the timer
                    if (vortexRealTimeMetrics.autoRefresh) {
                        startRefreshTimer();
                    }
                } else {
                    console.error('Error refreshing metrics:', response.data.message);
                }
                
                // Reset button state
                $refreshButton.text(originalText).prop('disabled', false);
                isRefreshing = false;
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                
                // Reset button state
                $refreshButton.text(originalText).prop('disabled', false);
                isRefreshing = false;
            }
        });
    }
    
    /**
     * Update the metrics display with new data
     */
    function updateMetricsDisplay(data) {
        // Update tokenized artworks section
        if ($('.vortex-tokenized-artworks').length) {
            $('.vortex-tokenized-artworks .vortex-metric-value').eq(0).text(formatNumber(data.artworks.total_artworks));
            $('.vortex-tokenized-artworks .vortex-metric-value').eq(1).text(formatNumber(data.artworks.unique_artists));
            $('.vortex-tokenized-artworks .vortex-metric-value').eq(2).text('$' + formatNumber(data.artworks.total_value));
            $('.vortex-tokenized-artworks .vortex-metric-value').eq(3).text('$' + formatNumber(data.artworks.average_value));
        }
        
        // Update top artists table
        if ($('.vortex-top-artists').length) {
            var $artistsTable = $('.vortex-top-artists tbody');
            $artistsTable.empty();
            
            data.top_artists.forEach(function(artist) {
                var $row = $('<tr></tr>');
                $row.append('<td>' + escapeHtml(artist.display_name) + '</td>');
                $row.append('<td>' + formatNumber(artist.artwork_count) + '</td>');
                $row.append('<td>$' + formatNumber(artist.total_value) + '</td>');
                $artistsTable.append($row);
            });
        }
        
        // Update top categories table
        if ($('.vortex-top-categories').length) {
            var $categoriesTable = $('.vortex-top-categories tbody');
            $categoriesTable.empty();
            
            data.top_categories.forEach(function(category) {
                var $row = $('<tr></tr>');
                $row.append('<td>' + escapeHtml(category.category_name) + '</td>');
                $row.append('<td>' + formatNumber(category.artwork_count) + '</td>');
                $row.append('<td>$' + formatNumber(category.total_value) + '</td>');
                $categoriesTable.append($row);
            });
        }
        
        // Update most swapped table
        if ($('.vortex-most-swapped').length) {
            var $swappedTable = $('.vortex-most-swapped tbody');
            $swappedTable.empty();
            
            data.most_swapped.forEach(function(artwork) {
                var $row = $('<tr></tr>');
                $row.append('<td>' + escapeHtml(artwork.title) + '</td>');
                $row.append('<td>' + escapeHtml(artwork.artist_name) + '</td>');
                $row.append('<td>' + formatNumber(artwork.swap_count) + '</td>');
                $row.append('<td>' + formatNumber(artwork.average_token_amount, 2) + '</td>');
                $swappedTable.append($row);
            });
        }
        
        // If chart exists, update it
        if (typeof Chart !== 'undefined' && $('#vortex-daily-activity-chart').length) {
            var chart = Chart.getChart('vortex-daily-activity-chart');
            
            if (chart) {
                var dates = data.daily_activity.map(function(item) { return item.date; });
                var counts = data.daily_activity.map(function(item) { return item.transaction_count; });
                var amounts = data.daily_activity.map(function(item) { return item.total_amount; });
                
                chart.data.labels = dates;
                chart.data.datasets[0].data = counts;
                chart.data.datasets[1].data = amounts;
                chart.update();
            }
        }
    }
    
    /**
     * Format number with commas
     */
    function formatNumber(value, decimals) {
        decimals = decimals || 0;
        return parseFloat(value).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
    
})(jQuery); 