<?php
/**
 * Real-Time Blockchain Metrics template
 *
 * @package Vortex_Marketplace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get the metrics instance
$blockchain_metrics = VORTEX_Blockchain_Metrics::get_instance();
$metrics = $blockchain_metrics->get_metrics();

// Auto-refresh settings
$auto_refresh = $atts['auto_refresh'] === 'yes';
$refresh_interval = intval($atts['refresh_interval']) * 1000; // Convert to milliseconds

// Enqueue necessary scripts
wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
wp_enqueue_script('vortex-real-time-metrics', plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/real-time-metrics.js', array('jquery', 'chart-js'), '1.1.0', true);

wp_localize_script('vortex-real-time-metrics', 'vortexRealTimeMetrics', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('vortex_real_time_metrics'),
    'autoRefresh' => $auto_refresh,
    'refreshInterval' => $refresh_interval,
    'i18n' => array(
        'updating' => __('Updating metrics...', 'vortex-marketplace'),
        'updated' => __('Updated:', 'vortex-marketplace'),
        'error' => __('Error updating metrics', 'vortex-marketplace')
    )
));
?>

<div class="vortex-real-time-metrics" data-metric="<?php echo esc_attr($atts['metric']); ?>">
    <div class="vortex-metrics-header">
        <h2><?php echo esc_html__('Real-Time TOLA Blockchain Metrics', 'vortex-marketplace'); ?></h2>
        <div class="vortex-metrics-controls">
            <span class="vortex-last-updated">
                <?php echo esc_html__('Last updated:', 'vortex-marketplace'); ?> 
                <time datetime="<?php echo date('c', $metrics['generated_at']); ?>"><?php echo date('H:i:s', $metrics['generated_at']); ?></time>
            </span>
            <?php if ($auto_refresh): ?>
                <span class="vortex-auto-refresh-status"><?php echo esc_html__('Auto-refresh: ON', 'vortex-marketplace'); ?></span>
            <?php endif; ?>
            <button class="vortex-refresh-button"><?php echo esc_html__('Refresh', 'vortex-marketplace'); ?></button>
        </div>
    </div>
    
    <?php if ($atts['metric'] === 'all' || $atts['metric'] === 'artworks'): ?>
    <div class="vortex-metric-section vortex-tokenized-artworks">
        <h3><?php echo esc_html__('Tokenized Artworks', 'vortex-marketplace'); ?></h3>
        <div class="vortex-metric-cards">
            <div class="vortex-metric-card">
                <div class="vortex-metric-value"><?php echo number_format($metrics['artworks']['total_artworks']); ?></div>
                <div class="vortex-metric-label"><?php echo esc_html__('Total Artworks', 'vortex-marketplace'); ?></div>
            </div>
            <div class="vortex-metric-card">
                <div class="vortex-metric-value"><?php echo number_format($metrics['artworks']['unique_artists']); ?></div>
                <div class="vortex-metric-label"><?php echo esc_html__('Unique Artists', 'vortex-marketplace'); ?></div>
            </div>
            <div class="vortex-metric-card">
                <div class="vortex-metric-value">$<?php echo number_format($metrics['artworks']['total_value']); ?></div>
                <div class="vortex-metric-label"><?php echo esc_html__('Total Value', 'vortex-marketplace'); ?></div>
            </div>
            <div class="vortex-metric-card">
                <div class="vortex-metric-value">$<?php echo number_format($metrics['artworks']['average_value']); ?></div>
                <div class="vortex-metric-label"><?php echo esc_html__('Average Value', 'vortex-marketplace'); ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($atts['metric'] === 'all' || $atts['metric'] === 'artists'): ?>
    <div class="vortex-metric-section vortex-top-artists">
        <h3><?php echo esc_html__('Most Active Artists on Blockchain', 'vortex-marketplace'); ?></h3>
        <div class="vortex-table-container">
            <table class="vortex-metrics-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Artist', 'vortex-marketplace'); ?></th>
                        <th><?php echo esc_html__('Artworks', 'vortex-marketplace'); ?></th>
                        <th><?php echo esc_html__('Total Value', 'vortex-marketplace'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($metrics['top_artists'] as $artist): ?>
                    <tr>
                        <td><?php echo esc_html($artist->display_name); ?></td>
                        <td><?php echo number_format($artist->artwork_count); ?></td>
                        <td>$<?php echo number_format($artist->total_value); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($atts['metric'] === 'all' || $atts['metric'] === 'categories'): ?>
    <div class="vortex-metric-section vortex-top-categories">
        <h3><?php echo esc_html__('Most Popular Artwork Categories', 'vortex-marketplace'); ?></h3>
        <div class="vortex-table-container">
            <table class="vortex-metrics-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Category', 'vortex-marketplace'); ?></th>
                        <th><?php echo esc_html__('Artworks', 'vortex-marketplace'); ?></th>
                        <th><?php echo esc_html__('Total Value', 'vortex-marketplace'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($metrics['top_categories'] as $category): ?>
                    <tr>
                        <td><?php echo esc_html($category->category_name); ?></td>
                        <td><?php echo number_format($category->artwork_count); ?></td>
                        <td>$<?php echo number_format($category->total_value); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($atts['metric'] === 'all' || $atts['metric'] === 'swaps'): ?>
    <div class="vortex-metric-section vortex-most-swapped">
        <h3><?php echo esc_html__('Most Swapped Artworks', 'vortex-marketplace'); ?></h3>
        <div class="vortex-table-container">
            <table class="vortex-metrics-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Artwork', 'vortex-marketplace'); ?></th>
                        <th><?php echo esc_html__('Artist', 'vortex-marketplace'); ?></th>
                        <th><?php echo esc_html__('Swap Count', 'vortex-marketplace'); ?></th>
                        <th><?php echo esc_html__('Avg. Tokens', 'vortex-marketplace'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($metrics['most_swapped'] as $artwork): ?>
                    <tr>
                        <td><?php echo esc_html($artwork->title); ?></td>
                        <td><?php echo esc_html($artwork->artist_name); ?></td>
                        <td><?php echo number_format($artwork->swap_count); ?></td>
                        <td><?php echo number_format($artwork->average_token_amount, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (($atts['metric'] === 'all' || $atts['metric'] === 'transactions') && $atts['show_chart'] === 'yes'): ?>
    <div class="vortex-metric-section vortex-daily-activity">
        <h3><?php echo esc_html__('Daily Blockchain Activity', 'vortex-marketplace'); ?></h3>
        <div class="vortex-chart-container">
            <canvas id="vortex-daily-activity-chart"></canvas>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var ctx = document.getElementById('vortex-daily-activity-chart').getContext('2d');
                var dailyData = <?php echo json_encode($metrics['daily_activity']); ?>;
                
                var dates = dailyData.map(function(item) { return item.date; });
                var counts = dailyData.map(function(item) { return item.transaction_count; });
                var amounts = dailyData.map(function(item) { return item.total_amount; });
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: dates,
                        datasets: [
                            {
                                label: '<?php echo esc_js(__('Transaction Count', 'vortex-marketplace')); ?>',
                                data: counts,
                                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1,
                                yAxisID: 'y'
                            },
                            {
                                label: '<?php echo esc_js(__('Total Amount', 'vortex-marketplace')); ?>',
                                data: amounts,
                                type: 'line',
                                fill: false,
                                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 2,
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
                                    text: '<?php echo esc_js(__('Transaction Count', 'vortex-marketplace')); ?>'
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: '<?php echo esc_js(__('Total Amount (TOLA)', 'vortex-marketplace')); ?>'
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
</div>

<style>
.vortex-real-time-metrics {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    padding: 25px;
    margin-bottom: 30px;
}

.vortex-metrics-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.vortex-metrics-header h2 {
    margin: 0;
    font-size: 24px;
    color: #1e293b;
}

.vortex-metrics-controls {
    display: flex;
    align-items: center;
    gap: 15px;
}

.vortex-last-updated {
    color: #64748b;
    font-size: 14px;
}

.vortex-auto-refresh-status {
    background: #ecfdf5;
    color: #065f46;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.vortex-refresh-button {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.2s;
}

.vortex-refresh-button:hover {
    background: #2563eb;
}

.vortex-metric-section {
    margin-bottom: 30px;
}

.vortex-metric-section h3 {
    font-size: 18px;
    color: #334155;
    margin-bottom: 15px;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 10px;
}

.vortex-metric-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.vortex-metric-card {
    background: #f8fafc;
    border-radius: 6px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.vortex-metric-value {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 5px;
}

.vortex-metric-label {
    font-size: 14px;
    color: #64748b;
}

.vortex-table-container {
    overflow-x: auto;
}

.vortex-metrics-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.vortex-metrics-table th,
.vortex-metrics-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.vortex-metrics-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #475569;
}

.vortex-metrics-table tr:last-child td {
    border-bottom: none;
}

.vortex-chart-container {
    height: 300px;
    margin-top: 20px;
}

@media (max-width: 768px) {
    .vortex-metrics-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .vortex-metric-cards {
        grid-template-columns: 1fr;
    }
}
</style> 