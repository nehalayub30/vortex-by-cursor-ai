<?php
/**
 * VORTEX AI Marketplace Metrics Page Template
 *
 * @package VORTEX
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

// Security check
if (!current_user_can('manage_options')) {
    wp_die(__('Access denied', 'vortex'));
}

// Initialize AI agents
$huraii = VORTEX_AI_Manager::get_instance()->get_agent('huraii');
$cloe = VORTEX_AI_Manager::get_instance()->get_agent('cloe');
$business_strategist = VORTEX_AI_Manager::get_instance()->get_agent('business_strategist');

// Get time range from request with default
$time_range = isset($_GET['range']) ? sanitize_text_field($_GET['range']) : '30days';
$valid_ranges = array('7days', '30days', '90days', '1year');
$time_range = in_array($time_range, $valid_ranges) ? $time_range : '30days';

// Security nonce
$nonce = wp_create_nonce('vortex_metrics_nonce');
?>

<div class="wrap vortex-metrics-page">
    <h1><?php _e('VORTEX Marketplace Metrics', 'vortex'); ?></h1>

    <!-- AI Learning Indicator -->
    <div class="vortex-ai-learning-indicator">
        <span class="vortex-ai-learning-dot"></span>
        <?php _e('AI Agents Learning Active', 'vortex'); ?>
    </div>

    <!-- Time Range Selector -->
    <div class="vortex-metrics-controls">
        <select id="vortex-time-range" class="vortex-select">
            <option value="7days" <?php selected($time_range, '7days'); ?>><?php _e('Last 7 Days', 'vortex'); ?></option>
            <option value="30days" <?php selected($time_range, '30days'); ?>><?php _e('Last 30 Days', 'vortex'); ?></option>
            <option value="90days" <?php selected($time_range, '90days'); ?>><?php _e('Last 90 Days', 'vortex'); ?></option>
            <option value="1year" <?php selected($time_range, '1year'); ?>><?php _e('Last Year', 'vortex'); ?></option>
        </select>
    </div>

    <!-- Key Performance Metrics -->
    <div class="vortex-metrics-grid">
        <?php
        try {
            $kpi_data = $business_strategist->get_key_performance_metrics($time_range);
            foreach ($kpi_data as $metric) :
                ?>
                <div class="vortex-metric-card <?php echo esc_attr($metric['trend_direction']); ?>">
                    <h3><?php echo esc_html($metric['label']); ?></h3>
                    <div class="vortex-metric-value"><?php echo esc_html($metric['value']); ?></div>
                    <div class="vortex-metric-trend">
                        <?php echo esc_html($metric['trend_percentage']); ?>%
                    </div>
                </div>
                <?php
            endforeach;
        } catch (Exception $e) {
            echo '<div class="vortex-error-message">' . esc_html($e->getMessage()) . '</div>';
        }
        ?>
    </div>

    <!-- AI Insights Section -->
    <div class="vortex-ai-insights-container">
        <h2><?php _e('AI Insights', 'vortex'); ?></h2>
        
        <!-- HURAII Insights -->
        <div class="vortex-insight-section vortex-huraii-insight">
            <h3>
                <span class="dashicons dashicons-art"></span>
                <?php _e('HURAII Art Trends', 'vortex'); ?>
            </h3>
            <div class="vortex-insight-content">
                <?php
                try {
                    $huraii_insights = $huraii->get_marketplace_insights($time_range);
                    echo wp_kses_post($huraii_insights['summary']);
                } catch (Exception $e) {
                    echo '<div class="vortex-error-message">' . esc_html($e->getMessage()) . '</div>';
                }
                ?>
            </div>
        </div>

        <!-- CLOE Insights -->
        <div class="vortex-insight-section vortex-cloe-insight">
            <h3>
                <span class="dashicons dashicons-groups"></span>
                <?php _e('CLOE User Behavior Analysis', 'vortex'); ?>
            </h3>
            <div class="vortex-insight-content">
                <?php
                try {
                    $cloe_insights = $cloe->get_user_behavior_insights($time_range);
                    echo wp_kses_post($cloe_insights['summary']);
                } catch (Exception $e) {
                    echo '<div class="vortex-error-message">' . esc_html($e->getMessage()) . '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Business Strategist Insights -->
        <div class="vortex-insight-section vortex-business-insight">
            <h3>
                <span class="dashicons dashicons-chart-line"></span>
                <?php _e('Market Strategy Insights', 'vortex'); ?>
            </h3>
            <div class="vortex-insight-content">
                <?php
                try {
                    $business_insights = $business_strategist->get_market_insights($time_range);
                    echo wp_kses_post($business_insights['summary']);
                } catch (Exception $e) {
                    echo '<div class="vortex-error-message">' . esc_html($e->getMessage()) . '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Performance Charts -->
    <div class="vortex-charts-container">
        <div id="vortex-sales-chart" class="vortex-chart">
            <h3><?php _e('Sales Performance', 'vortex'); ?></h3>
            <canvas id="salesChart"></canvas>
        </div>
        
        <div id="vortex-artist-performance" class="vortex-chart">
            <h3><?php _e('Artist Performance', 'vortex'); ?></h3>
            <canvas id="artistChart"></canvas>
        </div>
    </div>

    <!-- Top Performers Table -->
    <div class="vortex-top-performers">
        <h3><?php _e('Top Performing Artists', 'vortex'); ?></h3>
        <div class="vortex-table-container">
            <?php
            try {
                $top_performers = $business_strategist->get_top_performers($time_range);
                if (!empty($top_performers)) : ?>
                    <table class="vortex-table">
                        <thead>
                            <tr>
                                <th><?php _e('Artist', 'vortex'); ?></th>
                                <th><?php _e('Sales', 'vortex'); ?></th>
                                <th><?php _e('Revenue', 'vortex'); ?></th>
                                <th><?php _e('Growth', 'vortex'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_performers as $performer) : ?>
                                <tr>
                                    <td><?php echo esc_html($performer['name']); ?></td>
                                    <td><?php echo esc_html($performer['sales']); ?></td>
                                    <td><?php echo esc_html($performer['revenue']); ?></td>
                                    <td class="<?php echo esc_attr($performer['growth_class']); ?>">
                                        <?php echo esc_html($performer['growth']); ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p class="vortex-no-data"><?php _e('No performance data available for the selected period.', 'vortex'); ?></p>
                <?php endif;
            } catch (Exception $e) {
                echo '<div class="vortex-error-message">' . esc_html($e->getMessage()) . '</div>';
            }
            ?>
        </div>
    </div>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Initialize metrics with AI learning
    const vortexMetrics = new VortexMetrics({
        nonce: '<?php echo esc_js($nonce); ?>',
        timeRange: '<?php echo esc_js($time_range); ?>',
        ajaxUrl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
        aiLearningEnabled: true
    });

    // Initialize charts and bind events
    vortexMetrics.init();
});
</script> 