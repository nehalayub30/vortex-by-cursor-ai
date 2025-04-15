/**
 * VORTEX Blockchain Metrics JavaScript
 * 
 * Handles interactive elements for blockchain metrics display
 */
(function($) {
    'use strict';
    
    // Store chart instance to update later
    let priceChart = null;
    
    // Initialize everything when document is ready
    $(document).ready(function() {
        initBlockchainMetrics();
    });
    
    /**
     * Initialize blockchain metrics functionality
     */
    function initBlockchainMetrics() {
        // Set up refresh button
        $('.vortex-metrics-refresh').on('click', function() {
            refreshBlockchainMetrics();
            $(this).addClass('spinning');
            setTimeout(() => {
                $(this).removeClass('spinning');
            }, 1000);
        });
        
        // Set up chart timeframe buttons
        $('.vortex-chart-timeframe').on('click', function() {
            $('.vortex-chart-timeframe').removeClass('active');
            $(this).addClass('active');
            
            const timeframe = $(this).data('timeframe');
            updatePriceChart(timeframe);
        });
        
        // Initialize price chart if canvas exists
        if ($('#vortexPriceChart').length) {
            initPriceChart('7d');
        }
        
        // Auto-refresh metrics every minute (set in localized script parameter)
        const refreshInterval = vortexBlockchainParams.refreshInterval || 60000;
        setInterval(refreshBlockchainMetrics, refreshInterval);
    }
    
    /**
     * Refresh blockchain metrics data via AJAX
     */
    function refreshBlockchainMetrics() {
        $.ajax({
            url: vortexBlockchainParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_get_blockchain_metrics',
                nonce: vortexBlockchainParams.nonce,
                chart_data: $('#vortexPriceChart').length ? 'true' : 'false',
                timeframe: $('.vortex-chart-timeframe.active').data('timeframe') || '7d'
            },
            success: function(response) {
                if (response.success) {
                    updateMetricsDisplay(response.data);
                    
                    // Update chart if it exists and chart data is provided
                    if (priceChart && response.data.chart_data) {
                        updateChartData(priceChart, response.data.chart_data);
                    }
                }
            },
            error: function() {
                console.error('Error refreshing blockchain metrics');
            }
        });
    }
    
    /**
     * Update metrics display with new data
     */
    function updateMetricsDisplay(data) {
        // Update each metric
        for (const metricKey in data) {
            const $metricCard = $(`.vortex-metric-card[data-metric="${metricKey}"]`);
            
            if ($metricCard.length) {
                const $valueElement = $metricCard.find('.vortex-metric-value');
                
                switch (metricKey) {
                    case 'token_price':
                        // Format and update the price
                        let priceHtml = formatCurrency(data.token_price);
                        if (data.price_change_24h) {
                            const changeClass = data.price_change_24h >= 0 ? 'positive' : 'negative';
                            const changeSign = data.price_change_24h >= 0 ? '+' : '';
                            priceHtml += ` <span class="vortex-metric-change ${changeClass}">${changeSign}${data.price_change_24h.toFixed(2)}%</span>`;
                        }
                        $valueElement.html(priceHtml);
                        break;
                        
                    case 'market_cap':
                        $valueElement.text(formatCurrency(data.market_cap, true));
                        break;
                        
                    case 'total_supply':
                    case 'circulating_supply':
                        $valueElement.html(`${numberWithCommas(data[metricKey])} TOLA`);
                        
                        // Update percentage if this is circulating supply
                        if (metricKey === 'circulating_supply' && data.total_supply) {
                            const percentage = ((data.circulating_supply / data.total_supply) * 100).toFixed(1);
                            $valueElement.append(` <span class="vortex-metric-percentage">(${percentage}%)</span>`);
                        }
                        break;
                        
                    case 'volume_24h':
                        $valueElement.text(formatCurrency(data.volume_24h, true));
                        break;
                        
                    case 'holders':
                    case 'nft_count':
                    case 'total_transactions':
                        $valueElement.text(numberWithCommas(data[metricKey]));
                        break;
                }
            }
        }
        
        // Update marketplace insights if they exist
        if (data.most_active_artist) {
            $('.vortex-marketplace-metric:contains("Most Active Artist") .vortex-marketplace-metric-value')
                .text(data.most_active_artist);
        }
        
        if (data.top_nft_category) {
            $('.vortex-marketplace-metric:contains("Top NFT Category") .vortex-marketplace-metric-value')
                .text(data.top_nft_category);
        }
        
        if (data.highest_sale_24h) {
            $('.vortex-marketplace-metric:contains("Highest Sale") .vortex-marketplace-metric-value')
                .text(formatCurrency(data.highest_sale_24h));
        }
        
        if (data.weekly_trade_volume) {
            $('.vortex-marketplace-metric:contains("Weekly Trade Volume") .vortex-marketplace-metric-value')
                .text(formatCurrency(data.weekly_trade_volume, true));
        }
        
        // Update last updated timestamp
        if (data.last_updated) {
            $('.vortex-data-timestamp').text('Last updated: ' + formatTimestamp(data.last_updated));
        }
    }
    
    /**
     * Initialize price chart
     */
    function initPriceChart(timeframe) {
        if (!window.Chart) {
            console.error('Chart.js not loaded');
            return;
        }
        
        // Get chart context
        const ctx = document.getElementById('vortexPriceChart').getContext('2d');
        
        // Request initial chart data
        $.ajax({
            url: vortexBlockchainParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_get_blockchain_metrics',
                nonce: vortexBlockchainParams.nonce,
                chart_data: 'true',
                timeframe: timeframe
            },
            success: function(response) {
                if (response.success && response.data.chart_data) {
                    createPriceChart(ctx, response.data.chart_data);
                }
            },
            error: function() {
                console.error('Error loading chart data');
            }
        });
    }
    
    /**
     * Create price chart with data
     */
    function createPriceChart(ctx, chartData) {
        const gradientFill = ctx.createLinearGradient(0, 0, 0, 350);
        gradientFill.addColorStop(0, 'rgba(55, 110, 224, 0.3)');
        gradientFill.addColorStop(1, 'rgba(55, 110, 224, 0.05)');
        
        priceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'TOLA Price (USD)',
                    data: chartData.prices,
                    borderColor: 'rgba(55, 110, 224, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(55, 110, 224, 1)',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1.5,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true,
                    backgroundColor: gradientFill,
                    tension: 0.4
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
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return `TOLA Price: $${context.raw.toFixed(2)}`;
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
                        grid: {
                            borderDash: [2, 2]
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Update chart with new data for the selected timeframe
     */
    function updatePriceChart(timeframe) {
        $.ajax({
            url: vortexBlockchainParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_get_blockchain_metrics',
                nonce: vortexBlockchainParams.nonce,
                chart_data: 'true',
                timeframe: timeframe
            },
            success: function(response) {
                if (response.success && response.data.chart_data) {
                    updateChartData(priceChart, response.data.chart_data);
                }
            },
            error: function() {
                console.error('Error updating chart data');
            }
        });
    }
    
    /**
     * Update chart with new data
     */
    function updateChartData(chart, data) {
        chart.data.labels = data.labels;
        chart.data.datasets[0].data = data.prices;
        chart.update();
    }
    
    /**
     * Format currency for display
     */
    function formatCurrency(amount, abbreviate = false) {
        if (abbreviate) {
            if (amount >= 1000000) {
                return '$' + (amount / 1000000).toFixed(1) + 'M';
            } else if (amount >= 1000) {
                return '$' + (amount / 1000).toFixed(1) + 'K';
            }
        }
        
        return '$' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    /**
     * Format numbers with commas
     */
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    /**
     * Format timestamp for display
     */
    function formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleString();
    }
    
    // Creation Trends Chart
    function initCreationTrendsChart() {
        const canvas = document.getElementById('creationTrendsChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Get chart data from data attributes
        const labelsJSON = canvas.getAttribute('data-labels');
        const countJSON = canvas.getAttribute('data-creation-count');
        const salesJSON = canvas.getAttribute('data-sales-count');
        
        if (!labelsJSON || !countJSON) return;
        
        const labels = JSON.parse(labelsJSON);
        const creationCount = JSON.parse(countJSON);
        const salesCount = salesJSON ? JSON.parse(salesJSON) : null;
        
        const datasets = [{
            label: 'Artworks Created',
            data: creationCount,
            borderColor: '#6c5ce7',
            backgroundColor: 'rgba(108, 92, 231, 0.1)',
            borderWidth: 2,
            tension: 0.3,
            yAxisID: 'y-axis-count'
        }];
        
        if (salesCount) {
            datasets.push({
                label: 'Artworks Sold',
                data: salesCount,
                borderColor: '#ff6b6b',
                backgroundColor: 'rgba(255, 107, 107, 0.1)',
                borderWidth: 2,
                tension: 0.3,
                yAxisID: 'y-axis-count'
            });
        }
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        id: 'y-axis-count',
                        title: {
                            display: true,
                            text: 'Count'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Swap Timeline Chart
    function initSwapTimelineChart() {
        const canvas = document.getElementById('swapTimelineChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Get chart data from data attributes
        const labelsJSON = canvas.getAttribute('data-labels');
        const swapCountJSON = canvas.getAttribute('data-swap-count');
        const valueJSON = canvas.getAttribute('data-swap-value');
        
        if (!labelsJSON || !swapCountJSON) return;
        
        const labels = JSON.parse(labelsJSON);
        const swapCount = JSON.parse(swapCountJSON);
        const swapValue = valueJSON ? JSON.parse(valueJSON) : null;
        
        const datasets = [{
            label: 'Swap Count',
            data: swapCount,
            borderColor: '#fd79a8',
            backgroundColor: 'rgba(253, 121, 168, 0.1)',
            borderWidth: 2,
            tension: 0.3,
            yAxisID: 'y-axis-count'
        }];
        
        if (swapValue) {
            datasets.push({
                label: 'Swap Value',
                data: swapValue,
                borderColor: '#00b894',
                backgroundColor: 'rgba(0, 184, 148, 0.1)',
                borderWidth: 2,
                tension: 0.3,
                yAxisID: 'y-axis-value'
            });
        }
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        id: 'y-axis-count',
                        title: {
                            display: true,
                            text: 'Swap Count'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
        
        if (swapValue) {
            chart.options.scales['y-axis-value'] = {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Swap Value (TOLA)'
                },
                grid: {
                    drawOnChartArea: false,
                },
                beginAtZero: true
            };
            chart.update();
        }
    }
    
    // Helper function to generate chart colors
    function generateColors(count) {
        const predefinedColors = [
            '#4e54c8', '#36b37e', '#ff9f43', '#ff6b6b', '#6c5ce7',
            '#fd79a8', '#00b894', '#fdcb6e', '#0984e3', '#e84393'
        ];
        
        // If we have enough predefined colors, use them
        if (count <= predefinedColors.length) {
            return predefinedColors.slice(0, count);
        }
        
        // Otherwise, generate more colors
        const colors = [...predefinedColors];
        
        for (let i = predefinedColors.length; i < count; i++) {
            const hue = (i * 137) % 360; // Golden angle approximation for good distribution
            const saturation = 65 + Math.random() * 10;
            const lightness = 55 + Math.random() * 10;
            
            colors.push(`hsl(${hue}, ${saturation}%, ${lightness}%)`);
        }
        
        return colors;
    }
    
    // Export initialization functions
    window.vortexBlockchainMetrics = {
        refreshMetrics: refreshBlockchainMetrics,
        initCharts: initBlockchainMetricsCharts
    };
    
})(jQuery); 