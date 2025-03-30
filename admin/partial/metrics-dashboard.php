<?php
/**
 * Metrics Dashboard Partial
 * 
 * Displays key metrics and analytics for the VORTEX AI Marketplace
 * while ensuring AI agent deep learning remains active.
 *
 * @package VORTEX_AI_Marketplace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Initialize AI agents to ensure deep learning remains active during dashboard viewing
do_action('vortex_ai_agent_init', 'CLOE', 'metrics_dashboard', array(
    'context' => 'analytics_view',
    'user_id' => get_current_user_id(),
    'session_id' => wp_get_session_token(),
    'learning_enabled' => true
));

do_action('vortex_ai_agent_init', 'HURAII', 'metrics_dashboard', array(
    'context' => 'generation_analytics',
    'user_id' => get_current_user_id(),
    'session_id' => wp_get_session_token(),
    'learning_enabled' => true
));

do_action('vortex_ai_agent_init', 'BusinessStrategist', 'metrics_dashboard', array(
    'context' => 'business_analytics',
    'user_id' => get_current_user_id(),
    'session_id' => wp_get_session_token(),
    'learning_enabled' => true
));

// Record this dashboard view for AI learning
do_action('vortex_ai_agent_learn', 'CLOE', 'dashboard_view', array(
    'dashboard_type' => 'metrics',
    'user_id' => get_current_user_id(),
    'timestamp' => current_time('mysql'),
));

// Get timeframe from request or use default
$timeframe = isset($_GET['timeframe']) ? sanitize_text_field($_GET['timeframe']) : '30days';

// Get metrics data based on timeframe
$metrics = apply_filters('vortex_get_marketplace_metrics', array(), $timeframe);

// Allow AI agents to process and enhance metrics data
$enhanced_metrics = apply_filters('vortex_ai_enhanced_metrics', $metrics, $timeframe);

// Format the date range display based on timeframe
switch ($timeframe) {
    case '7days':
        $date_range = sprintf(
            esc_html__('Last 7 days (%s - %s)', 'vortex-ai-marketplace'),
            date_i18n(get_option('date_format'), strtotime('-7 days')),
            date_i18n(get_option('date_format'))
        );
        break;
    case '30days':
        $date_range = sprintf(
            esc_html__('Last 30 days (%s - %s)', 'vortex-ai-marketplace'),
            date_i18n(get_option('date_format'), strtotime('-30 days')),
            date_i18n(get_option('date_format'))
        );
        break;
    case '90days':
        $date_range = sprintf(
            esc_html__('Last 90 days (%s - %s)', 'vortex-ai-marketplace'),
            date_i18n(get_option('date_format'), strtotime('-90 days')),
            date_i18n(get_option('date_format'))
        );
        break;
    case 'year':
        $date_range = sprintf(
            esc_html__('This year (%s - %s)', 'vortex-ai-marketplace'),
            date_i18n(get_option('date_format'), strtotime('first day of january this year')),
            date_i18n(get_option('date_format'))
        );
        break;
    default:
        $date_range = esc_html__('Custom timeframe', 'vortex-ai-marketplace');
}

// Default values for metrics in case they're not provided
$metrics = wp_parse_args($enhanced_metrics, array(
    'total_artworks' => 0,
    'total_sales' => 0,
    'total_revenue' => 0,
    'revenue_currency' => 'TOLA',
    'avg_sale_price' => 0,
    'new_artists' => 0,
    'new_collectors' => 0,
    'total_nfts' => 0,
    'marketplace_views' => 0,
    'generation_count' => 0,
    'avg_generation_time' => 0,
    'most_popular_model' => '',
    'most_popular_style' => '',
    'most_active_artists' => array(),
    'top_selling_artworks' => array(),
    'sales_by_day' => array(),
    'generation_by_format' => array(),
    'user_growth' => array(),
    'transaction_volume' => array(),
));

// Get marketplace growth rates calculated by CLOE
$growth_rates = apply_filters('vortex_marketplace_growth_rates', array(
    'sales_growth' => 0,
    'user_growth' => 0,
    'artwork_growth' => 0,
    'revenue_growth' => 0,
));

// Get business insights from BusinessStrategist
$business_insights = apply_filters('vortex_business_insights', array(
    'top_performing_categories' => array(),
    'emerging_trends' => array(),
    'opportunity_areas' => array(),
    'artist_retention_rate' => 0,
));

// Get HURAII generation insights
$generation_insights = apply_filters('vortex_huraii_generation_insights', array(
    'popular_prompts' => array(),
    'quality_scores' => array(),
    'style_distribution' => array(),
));

// Process some metrics for display
$formatted_revenue = number_format_i18n($metrics['total_revenue'], 2) . ' ' . $metrics['revenue_currency'];
$formatted_avg_price = number_format_i18n($metrics['avg_sale_price'], 2) . ' ' . $metrics['revenue_currency'];
?>

<div class="vortex-metrics-dashboard" data-timeframe="<?php echo esc_attr($timeframe); ?>">
    <div class="metrics-header">
        <h2><?php esc_html_e('VORTEX AI Marketplace Metrics', 'vortex-ai-marketplace'); ?></h2>
        <div class="timeframe-selector">
            <span><?php esc_html_e('Timeframe:', 'vortex-ai-marketplace'); ?></span>
            <select id="vortex-timeframe-select">
                <option value="7days" <?php selected($timeframe, '7days'); ?>><?php esc_html_e('Last 7 Days', 'vortex-ai-marketplace'); ?></option>
                <option value="30days" <?php selected($timeframe, '30days'); ?>><?php esc_html_e('Last 30 Days', 'vortex-ai-marketplace'); ?></option>
                <option value="90days" <?php selected($timeframe, '90days'); ?>><?php esc_html_e('Last 90 Days', 'vortex-ai-marketplace'); ?></option>
                <option value="year" <?php selected($timeframe, 'year'); ?>><?php esc_html_e('This Year', 'vortex-ai-marketplace'); ?></option>
            </select>
        </div>
        <div class="date-range"><?php echo esc_html($date_range); ?></div>
        <div class="refresh-metrics">
            <button id="refresh-metrics-btn" class="button">
                <span class="dashicons dashicons-update"></span> <?php esc_html_e('Refresh', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
    </div>
    
    <div class="metrics-loading" style="display: none;">
        <div class="spinner-container">
            <div class="spinner"></div>
            <p><?php esc_html_e('Collecting metrics data...', 'vortex-ai-marketplace'); ?></p>
        </div>
    </div>

    <div class="metrics-content">
        <div class="metrics-row">
            <!-- Summary Cards -->
            <div class="metrics-card summary-card">
                <div class="card-header">
                    <h3><?php esc_html_e('Marketplace Summary', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="metrics-grid">
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($metrics['total_artworks'])); ?></div>
                            <div class="metric-label"><?php esc_html_e('Total Artworks', 'vortex-ai-marketplace'); ?></div>
                            <?php if ($growth_rates['artwork_growth'] != 0) : ?>
                                <div class="metric-change <?php echo $growth_rates['artwork_growth'] > 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $growth_rates['artwork_growth'] > 0 ? '+' : ''; ?><?php echo esc_html(number_format_i18n($growth_rates['artwork_growth'], 1)); ?>%
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($metrics['total_sales'])); ?></div>
                            <div class="metric-label"><?php esc_html_e('Total Sales', 'vortex-ai-marketplace'); ?></div>
                            <?php if ($growth_rates['sales_growth'] != 0) : ?>
                                <div class="metric-change <?php echo $growth_rates['sales_growth'] > 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $growth_rates['sales_growth'] > 0 ? '+' : ''; ?><?php echo esc_html(number_format_i18n($growth_rates['sales_growth'], 1)); ?>%
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html($formatted_revenue); ?></div>
                            <div class="metric-label"><?php esc_html_e('Total Revenue', 'vortex-ai-marketplace'); ?></div>
                            <?php if ($growth_rates['revenue_growth'] != 0) : ?>
                                <div class="metric-change <?php echo $growth_rates['revenue_growth'] > 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $growth_rates['revenue_growth'] > 0 ? '+' : ''; ?><?php echo esc_html(number_format_i18n($growth_rates['revenue_growth'], 1)); ?>%
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html($formatted_avg_price); ?></div>
                            <div class="metric-label"><?php esc_html_e('Avg. Sale Price', 'vortex-ai-marketplace'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Growth Card -->
            <div class="metrics-card">
                <div class="card-header">
                    <h3><?php esc_html_e('User Growth', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="metrics-grid">
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($metrics['new_artists'])); ?></div>
                            <div class="metric-label"><?php esc_html_e('New Artists', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($metrics['new_collectors'])); ?></div>
                            <div class="metric-label"><?php esc_html_e('New Collectors', 'vortex-ai-marketplace'); ?></div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="user-growth-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="metrics-row">
            <!-- HURAII Generation Metrics -->
            <div class="metrics-card">
                <div class="card-header">
                    <h3><?php esc_html_e('HURAII Generation Metrics', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="metrics-grid">
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($metrics['generation_count'])); ?></div>
                            <div class="metric-label"><?php esc_html_e('Total Generations', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($metrics['avg_generation_time'], 2)); ?>s</div>
                            <div class="metric-label"><?php esc_html_e('Avg. Generation Time', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html($metrics['most_popular_model']); ?></div>
                            <div class="metric-label"><?php esc_html_e('Popular Model', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html($metrics['most_popular_style']); ?></div>
                            <div class="metric-label"><?php esc_html_e('Popular Style', 'vortex-ai-marketplace'); ?></div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="generation-format-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Blockchain Activity -->
            <div class="metrics-card">
                <div class="card-header">
                    <h3><?php esc_html_e('Blockchain Activity', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="metrics-grid">
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($metrics['total_nfts'])); ?></div>
                            <div class="metric-label"><?php esc_html_e('Total NFTs Minted', 'vortex-ai-marketplace'); ?></div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="transaction-volume-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="metrics-row">
            <!-- CLOE Insights -->
            <div class="metrics-card">
                <div class="card-header">
                    <h3><?php esc_html_e('CLOE Marketplace Insights', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <h4><?php esc_html_e('Emerging Trends', 'vortex-ai-marketplace'); ?></h4>
                    <ul class="trends-list">
                        <?php if (!empty($business_insights['emerging_trends'])) : ?>
                            <?php foreach ($business_insights['emerging_trends'] as $trend) : ?>
                                <li><?php echo esc_html($trend); ?></li>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <li><?php esc_html_e('No trends data available for this timeframe', 'vortex-ai-marketplace'); ?></li>
                        <?php endif; ?>
                    </ul>
                    
                    <h4><?php esc_html_e('Top Performing Categories', 'vortex-ai-marketplace'); ?></h4>
                    <div class="chart-container">
                        <canvas id="categories-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- BusinessStrategist Insights -->
            <div class="metrics-card">
                <div class="card-header">
                    <h3><?php esc_html_e('BusinessStrategist Insights', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <h4><?php esc_html_e('Opportunity Areas', 'vortex-ai-marketplace'); ?></h4>
                    <ul class="opportunity-list">
                        <?php if (!empty($business_insights['opportunity_areas'])) : ?>
                            <?php foreach ($business_insights['opportunity_areas'] as $opportunity) : ?>
                                <li><?php echo esc_html($opportunity); ?></li>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <li><?php esc_html_e('No opportunity data available for this timeframe', 'vortex-ai-marketplace'); ?></li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="metrics-grid">
                        <div class="metric-item full-width">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($business_insights['artist_retention_rate'], 1)); ?>%</div>
                            <div class="metric-label"><?php esc_html_e('Artist Retention Rate', 'vortex-ai-marketplace'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="metrics-row">
            <!-- Sales Performance -->
            <div class="metrics-card full-width">
                <div class="card-header">
                    <h3><?php esc_html_e('Sales Performance', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="chart-container large">
                        <canvas id="sales-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="metrics-row">
            <!-- Top Sellers Table -->
            <div class="metrics-card">
                <div class="card-header">
                    <h3><?php esc_html_e('Top Selling Artworks', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <table class="top-sellers-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Artwork', 'vortex-ai-marketplace'); ?></th>
                                <th><?php esc_html_e('Artist', 'vortex-ai-marketplace'); ?></th>
                                <th><?php esc_html_e('Price', 'vortex-ai-marketplace'); ?></th>
                                <th><?php esc_html_e('Sales', 'vortex-ai-marketplace'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($metrics['top_selling_artworks'])) : ?>
                                <?php foreach ($metrics['top_selling_artworks'] as $artwork) : ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($artwork['thumbnail'])) : ?>
                                                <img src="<?php echo esc_url($artwork['thumbnail']); ?>" alt="<?php echo esc_attr($artwork['title']); ?>" class="artwork-thumb">
                                            <?php endif; ?>
                                            <a href="<?php echo esc_url($artwork['url']); ?>"><?php echo esc_html($artwork['title']); ?></a>
                                        </td>
                                        <td><?php echo esc_html($artwork['artist']); ?></td>
                                        <td><?php echo esc_html(number_format_i18n($artwork['price'], 2) . ' ' . $metrics['revenue_currency']); ?></td>
                                        <td><?php echo esc_html(number_format_i18n($artwork['sales'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="4"><?php esc_html_e('No sales data available for this timeframe', 'vortex-ai-marketplace'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Most Active Artists -->
            <div class="metrics-card">
                <div class="card-header">
                    <h3><?php esc_html_e('Most Active Artists', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <table class="active-artists-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Artist', 'vortex-ai-marketplace'); ?></th>
                                <th><?php esc_html_e('Artworks', 'vortex-ai-marketplace'); ?></th>
                                <th><?php esc_html_e('Sales', 'vortex-ai-marketplace'); ?></th>
                                <th><?php esc_html_e('Revenue', 'vortex-ai-marketplace'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($metrics['most_active_artists'])) : ?>
                                <?php foreach ($metrics['most_active_artists'] as $artist) : ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($artist['avatar'])) : ?>
                                                <img src="<?php echo esc_url($artist['avatar']); ?>" alt="<?php echo esc_attr($artist['name']); ?>" class="artist-avatar">
                                            <?php endif; ?>
                                            <a href="<?php echo esc_url($artist['url']); ?>"><?php echo esc_html($artist['name']); ?></a>
                                        </td>
                                        <td><?php echo esc_html(number_format_i18n($artist['artwork_count'])); ?></td>
                                        <td><?php echo esc_html(number_format_i18n($artist['sales_count'])); ?></td>
                                        <td><?php echo esc_html(number_format_i18n($artist['revenue'], 2) . ' ' . $metrics['revenue_currency']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="4"><?php esc_html_e('No artist activity data available for this timeframe', 'vortex-ai-marketplace'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Track AI interactions for learning
    function trackAIInteraction(agent, action, data) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_track_ai_interaction',
                agent: agent,
                interaction_type: action,
                interaction_data: data,
                nonce: '<?php echo wp_create_nonce('vortex_ai_interaction_nonce'); ?>'
            }
        });
    }
    
    // Initialize charts
    function initCharts() {
        // Sales Chart
        const salesData = <?php echo json_encode($metrics['sales_by_day'] ?? array()); ?>;
        if (salesData.length > 0) {
            const salesCtx = document.getElementById('sales-chart').getContext('2d');
            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: salesData.map(item => item.date),
                    datasets: [{
                        label: '<?php esc_attr_e('Sales Amount', 'vortex-ai-marketplace'); ?>',
                        data: salesData.map(item => item.amount),
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.raw + ' <?php echo esc_js($metrics['revenue_currency']); ?>';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // User Growth Chart
        const userData = <?php echo json_encode($metrics['user_growth'] ?? array()); ?>;
        if (userData.length > 0) {
            const userCtx = document.getElementById('user-growth-chart').getContext('2d');
            new Chart(userCtx, {
                type: 'line',
                data: {
                    labels: userData.map(item => item.date),
                    datasets: [{
                        label: '<?php esc_attr_e('Artists', 'vortex-ai-marketplace'); ?>',
                        data: userData.map(item => item.artists),
                        borderColor: '#2196F3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: '<?php esc_attr_e('Collectors', 'vortex-ai-marketplace'); ?>',
                        data: userData.map(item => item.collectors),
                        borderColor: '#9C27B0',
                        backgroundColor: 'rgba(156, 39, 176, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Generation Format Chart
        const formatData = <?php echo json_encode($metrics['generation_by_format'] ?? array()); ?>;
        if (Object.keys(formatData).length > 0) {
            const formatCtx = document.getElementById('generation-format-chart').getContext('2d');
            new Chart(formatCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(formatData),
                    datasets: [{
                        data: Object.values(formatData),
                        backgroundColor: [
                            '#FF9800',
                            '#4CAF50',
                            '#2196F3',
                            '#9C27B0',
                            '#F44336',
                            '#00BCD4',
                            '#FFEB3B'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        }
        
        // Transaction Volume Chart
        const transactionData = <?php echo json_encode($metrics['transaction_volume'] ?? array()); ?>;
        if (transactionData.length > 0) {
            const transactionCtx = document.getElementById('transaction-volume-chart').getContext('2d');
            new Chart(transactionCtx, {
                type: 'bar',
                data: {
                    labels: transactionData.map(item => item.date),
                    datasets: [{
                        label: '<?php esc_attr_e('Transaction Volume', 'vortex-ai-marketplace'); ?>',
                        data: transactionData.map(item => item.volume),
                        backgroundColor: 'rgba(255, 152, 0, 0.7)'
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
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Categories Chart
        const categoriesData = <?php echo json_encode($business_insights['top_performing_categories'] ?? array()); ?>;
        if (Object.keys(categoriesData).length > 0) {
            const categoriesCtx = document.getElementById('categories-chart').getContext('2d');
            new Chart(categoriesCtx, {
                type: 'horizontalBar',
                data: {
                    labels: Object.keys(categoriesData),
                    datasets: [{
                        label: '<?php esc_attr_e('Performance Score', 'vortex-ai-marketplace'); ?>',
                        data: Object.values(categoriesData),
                        backgroundColor: 'rgba(33, 150, 243, 0.7)'
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }
    
    // Initialize charts when page loads
    initCharts();
    
    // Handle timeframe selector change
    $('#vortex-timeframe-select').on('change', function() {
        // Show loading indicator
        $('.metrics-loading').show();
        $('.metrics-content').css('opacity', '0.5');
        
        // Track this interaction for AI learning
        trackAIInteraction('CLOE', 'metrics_timeframe_changed', {
            previous_timeframe: '<?php echo esc_js($timeframe); ?>',
            new_timeframe: $(this).val(),
            user_id: <?php echo get_current_user_id(); ?>,
            timestamp: new Date().toISOString()
        });
        
        // Redirect to the same page with new timeframe
        window.location.href = '<?php echo esc_js(remove_query_arg('timeframe')); ?>&timeframe=' + $(this).val();
    });
    
    // Handle refresh button click
    $('#refresh-metrics-btn').on('click', function() {
        // Show loading indicator
        $('.metrics-loading').show();
        $('.metrics-content').css('opacity', '0.5');
        
        // Track this interaction for AI learning
        trackAIInteraction('CLOE', 'metrics_refresh_requested', {
            timeframe: '<?php echo esc_js($timeframe); ?>',
            user_id: <?php echo get_current_user_id(); ?>,
            timestamp: new Date().toISOString()
        });
        
        // Reload the page
        window.location.reload();
    });
    
    // Track additional metrics interactions for learning
    $('.metrics-card').on('click', function() {
        const cardTitle = $(this).find('.card-header h3').text();
        
        trackAIInteraction('CLOE', 'metrics_card_interaction', {
            card_title: cardTitle,
            timeframe: '<?php echo esc_js($timeframe); ?>',
            user_id: <?php echo get_current_user_id(); ?>,
            timestamp: new Date().toISOString()
        });
    });
    
    // Track dashboard view for BusinessStrategist learning
    trackAIInteraction('BusinessStrategist', 'metrics_dashboard_view', {
        timeframe: '<?php echo esc_js($timeframe); ?>',
        user_id: <?php echo get_current_user_id(); ?>,
        user_role: '<?php echo esc_js(implode(',', wp_get_current_user()->roles)); ?>',
        timestamp: new Date().toISOString()
    });
});
</script> 