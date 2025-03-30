/**
 * Blockchain Metrics Dashboard JavaScript
 */
(function($) {
    'use strict';
    
    // Charts objects
    let monthlyActivityChart = null;
    let categoriesChart = null;
    
    // Load metrics data on page load
    $(document).ready(function() {
        loadMetricsData();
        
        // Set up refresh button
        $('#refresh-metrics').on('click', function() {
            $('.metrics-container').hide();
            $('.metrics-loading').show();
            loadMetricsData();
        });
    });
    
    // Load metrics data via AJAX
    function loadMetricsData() {
        $.ajax({
            url: vortex_metrics.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_blockchain_metrics',
                nonce: vortex_metrics.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update last updated time
                    $('#last-updated-time').text(getCurrentDateTime());
                    
                    // Update metrics
                    updateMetrics(response.data);
                    
                    // Show metrics container
                    $('.metrics-loading').hide();
                    $('.metrics-container').show();
                } else {
                    showError('Error loading metrics data.');
                }
            },
            error: function() {
                showError('Network error while loading metrics data.');
            }
        });
    }
    
    // Update all metrics with data
    function updateMetrics(data) {
        // Update summary cards
        $('#total-artworks').text(formatNumber(data.total_artworks));
        $('#total-artists').text(formatNumber(data.total_artists));
        $('#total-swaps').text(formatNumber(data.total_swaps));
        $('#total-tola').text(formatNumber(data.tola_stats.total_tola));
        
        // Update charts
        updateMonthlyActivityChart(data.monthly_activity);
        updateCategoriesChart(data.top_categories);
        
        // Update top artists
        updateTopArtists(data.top_artists);
        
        // Update recent transactions
        updateRecentTransactions(data.recent_transactions);
    }
    
    // Update monthly activity chart
    function updateMonthlyActivityChart(monthlyData) {
        const ctx = document.getElementById('monthly-activity-chart').getContext('2d');
        
        // Extract data
        const labels = monthlyData.map(item => item.month);
        const artworksData = monthlyData.map(item => item.new_artworks);
        const swapsData = monthlyData.map(item => item.completed_swaps);
        
        // Destroy previous chart if exists
        if (monthlyActivityChart) {
            monthlyActivityChart.destroy();
        }
        
        // Create new chart
        monthlyActivityChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'New Verified Artworks',
                        data: artworksData,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Completed Swaps',
                        data: swapsData,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }
    
    // Update categories chart
    function updateCategoriesChart(categoriesData) {
        const ctx = document.getElementById('categories-chart').getContext('2d');
        
        // Extract data
        const labels = categoriesData.map(item => item.category_name);
        const data = categoriesData.map(item => item.artwork_count);
        
        // Generate colors
        const backgroundColors = generateColors(labels.length, 0.6);
        const borderColors = generateColors(labels.length, 1);
        
        // Destroy previous chart if exists
        if (categoriesChart) {
            categoriesChart.destroy();
        }
        
        // Create new chart
        categoriesChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [
                    {
                        data: data,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 1
                    }
                ]
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
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Update top artists list
    function updateTopArtists(artistsData) {
        if (artistsData.length === 0) {
            $('#top-artists-list').html('<div class="empty-state">No artist swap data available yet.</div>');
            return;
        }
        
        let html = '<div class="artists-grid">';
        
        artistsData.forEach(function(artist, index) {
            html += `
                <div class="artist-rank-card">
                    <div class="artist-rank">#${index + 1}</div>
                    <div class="artist-info">
                        <div class="artist-name">${escapeHtml(artist.artist_name)}</div>
                        <div class="artist-swaps">${artist.swap_count} ${artist.swap_count === 1 ? 'swap' : 'swaps'}</div>
                    </div>
                    <a href="#" class="view-artist-link" data-artist-id="${artist.artist_id}">View Profile</a>
                </div>
            `;
        });
        
        html += '</div>';
        $('#top-artists-list').html(html);
    }
    
    // Update recent transactions table
    function updateRecentTransactions(transactionsData) {
        if (transactionsData.length === 0) {
            $('#recent-transactions').html('<tr><td colspan="4" class="empty-state">No transaction data available yet.</td></tr>');
            return;
        }
        
        let html = '';
        
        transactionsData.forEach(function(transaction) {
            const txHash = transaction.transaction_hash;
            const shortHash = txHash.substring(0, 10) + '...' + txHash.substring(txHash.length - 8);
            const date = formatDate(transaction.transaction_date);
            
            html += `
                <tr>
                    <td class="hash-cell">
                        <span class="short-hash" title="${txHash}">${shortHash}</span>
                    </td>
                    <td>${date}</td>
                    <td>Artwork Swap</td>
                    <td>
                        <a href="https://explorer.tola-chain.io/tx/${txHash}" target="_blank" class="view-tx-link">
                            View on Explorer <span class="dashicons dashicons-external"></span>
                        </a>
                    </td>
                </tr>
            `;
        });
        
        $('#recent-transactions').html(html);
    }
    
    // Helper function to format numbers
    function formatNumber(number) {
        return new Intl.NumberFormat().format(number);
    }
    
    // Helper function to format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
    
    // Helper function to get current date time
    function getCurrentDateTime() {
        const now = new Date();
        return now.toLocaleDateString() + ' ' + now.toLocaleTimeString();
    }
    
    // Helper function to generate colors for charts
    function generateColors(count, alpha) {
        const baseColors = [
            `rgba(54, 162, 235, ${alpha})`,
            `rgba(255, 99, 132, ${alpha})`,
            `rgba(255, 206, 86, ${alpha})`,
            `rgba(75, 192, 192, ${alpha})`,
            `rgba(153, 102, 255, ${alpha})`,
            `rgba(255, 159, 64, ${alpha})`,
            `rgba(199, 199, 199, ${alpha})`,
            `rgba(83, 102, 255, ${alpha})`,
            `rgba(40, 167, 69, ${alpha})`,
            `rgba(220, 53, 69, ${alpha})`
        ];
        
        // If we need more colors than in our base set, generate them
        if (count > baseColors.length) {
            const colors = [...baseColors];
            for (let i = baseColors.length; i < count; i++) {
                const r = Math.floor(Math.random() * 255);
                const g = Math.floor(Math.random() * 255);
                const b = Math.floor(Math.random() * 255);
                colors.push(`rgba(${r}, ${g}, ${b}, ${alpha})`);
            }
            return colors;
        }
        
        // Otherwise just return the subset we need
        return baseColors.slice(0, count);
    }
    
    // Helper function to escape HTML
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    // Show error message
    function showError(message) {
        $('.metrics-loading').html(`
            <div class="error-message">
                <span class="dashicons dashicons-warning"></span>
                <p>${message}</p>
                <button id="retry-loading" class="retry-button">Retry</button>
            </div>
        `);
        
        // Set up retry button
        $('#retry-loading').on('click', function() {
            $('.metrics-loading').html(`
                <div class="spinner"></div>
                <p>Loading blockchain data...</p>
            `);
            loadMetricsData();
        });
    }
})(jQuery); 