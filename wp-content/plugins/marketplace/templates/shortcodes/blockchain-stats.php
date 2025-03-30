<?php
/**
 * Blockchain Stats shortcode template
 *
 * @package Vortex_Marketplace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get blockchain stats
$days = isset($atts['days']) ? intval($atts['days']) : 30;
$show_chart = isset($atts['show_chart']) ? $atts['show_chart'] === 'yes' : true;
$type = isset($atts['type']) ? $atts['type'] : 'all';
?>

<div class="vortex-blockchain-stats">
    <h2 class="vortex-section-title">TOLA Blockchain Statistics</h2>
    <p class="vortex-stats-period">Showing data for the past <?php echo $days; ?> days</p>
    
    <div class="vortex-stats-summary">
        <div class="vortex-stats-card vortex-stats-primary">
            <span class="vortex-stats-value"><?php echo number_format($stats['tola']['total_transactions']); ?></span>
            <span class="vortex-stats-label">Transactions</span>
        </div>
        
        <div class="vortex-stats-card vortex-stats-primary">
            <span class="vortex-stats-value"><?php echo number_format($stats['tola']['total_amount'], 2); ?></span>
            <span class="vortex-stats-label">TOLA Tokens</span>
        </div>
        
        <div class="vortex-stats-card">
            <span class="vortex-stats-value"><?php echo number_format($stats['tola']['unique_users']); ?></span>
            <span class="vortex-stats-label">Active Users</span>
        </div>
        
        <?php if (isset($stats['smart_contracts'])): ?>
        <div class="vortex-stats-card">
            <span class="vortex-stats-value"><?php echo number_format($stats['smart_contracts']['total_contracts']); ?></span>
            <span class="vortex-stats-label">Smart Contracts</span>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($show_chart && !empty($stats['daily_transactions'])): ?>
    <div class="vortex-blockchain-chart">
        <h3>Daily Transaction Volume</h3>
        <canvas id="vortex-blockchain-chart" width="800" height="400"></canvas>
        <script>
        jQuery(document).ready(function($) {
            var ctx = document.getElementById('vortex-blockchain-chart').getContext('2d');
            
            var dates = <?php echo json_encode(array_column($stats['daily_transactions'], 'date')); ?>;
            var amounts = <?php echo json_encode(array_column($stats['daily_transactions'], 'amount')); ?>;
            var counts = <?php echo json_encode(array_column($stats['daily_transactions'], 'count')); ?>;
            
            var chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [
                        {
                            label: 'Token Amount',
                            data: amounts,
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.05)',
                            pointRadius: 3,
                            pointBackgroundColor: '#4e73df',
                            pointBorderColor: '#4e73df',
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: '#4e73df',
                            pointHoverBorderColor: '#4e73df',
                            pointHitRadius: 10,
                            pointBorderWidth: 2,
                            fill: true,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Transaction Count',
                            data: counts,
                            borderColor: '#36b9cc',
                            backgroundColor: 'rgba(54, 185, 204, 0.05)',
                            pointRadius: 3,
                            pointBackgroundColor: '#36b9cc',
                            pointBorderColor: '#36b9cc',
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: '#36b9cc',
                            pointHoverBorderColor: '#36b9cc',
                            pointHitRadius: 10,
                            pointBorderWidth: 2,
                            fill: true,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Token Amount'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Transaction Count'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        });
        </script>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($stats['most_swapped_artists'])): ?>
    <div class="vortex-blockchain-artists">
        <h3>Most Swapped Artists</h3>
        <div class="vortex-table-responsive">
            <table class="vortex-table">
                <thead>
                    <tr>
                        <th>Artist</th>
                        <th>Swap Count</th>
                        <th>Total TOLA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['most_swapped_artists'] as $artist): ?>
                    <tr>
                        <td><?php echo esc_html($artist->artist_name); ?></td>
                        <td><?php echo number_format($artist->swap_count); ?></td>
                        <td><?php echo number_format($artist->total_tokens, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($stats['most_swapped_categories'])): ?>
    <div class="vortex-blockchain-categories">
        <h3>Most Swapped Artwork Categories</h3>
        <div class="vortex-table-responsive">
            <table class="vortex-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Swap Count</th>
                        <th>Total TOLA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['most_swapped_categories'] as $category): ?>
                    <tr>
                        <td><?php echo esc_html($category->category_name); ?></td>
                        <td><?php echo number_format($category->swap_count); ?></td>
                        <td><?php echo number_format($category->total_tokens, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <p class="vortex-stats-footer">Data updated: <?php echo date('F j, Y, g:i a', $stats['generated_at']); ?></p>
</div>

<style>
.vortex-blockchain-stats {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    padding: 30px;
    margin-bottom: 30px;
}

.vortex-section-title {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 24px;
    color: #333;
}

.vortex-stats-period {
    color: #666;
    margin-bottom: 30px;
}

.vortex-stats-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
}

.vortex-stats-card {
    flex: 1;
    min-width: 200px;
    background: #f9f9f9;
    border-radius: 6px;
    padding: 20px;
    text-align: center;
    display: flex;
    flex-direction: column;
}

.vortex-stats-primary {
    background: #4e73df;
    color: white;
}

.vortex-stats-value {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 5px;
}

.vortex-stats-label {
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.vortex-blockchain-chart {
    margin-bottom: 30px;
}

.vortex-table-responsive {
    overflow-x: auto;
}

.vortex-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 30px;
}

.vortex-table th,
.vortex-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.vortex-table th {
    background-color: #f5f5f5;
    font-weight: bold;
}

.vortex-table tr:hover {
    background-color: #f9f9f9;
}

.vortex-stats-footer {
    color: #888;
    font-size: 12px;
    text-align: right;
    font-style: italic;
}
</style> 