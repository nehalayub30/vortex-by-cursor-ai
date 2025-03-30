<?php
/**
 * AI Dashboard Template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get usage metrics
$metrics = get_option('vortex_ai_usage_metrics', array());
$total_tokens = 0;
$total_requests = 0;

foreach ($metrics as $provider => $data) {
    $total_tokens += $data['total_tokens'];
    $total_requests += $data['total_requests'];
}

// Get recent logs
global $wpdb;
$table_name = $wpdb->prefix . 'vortex_ai_usage_logs';
$recent_logs = $wpdb->get_results(
    "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 10",
    ARRAY_A
);
?>

<div class="wrap vortex-ai-dashboard">
    <h1><?php esc_html_e('AI Usage Dashboard', 'vortex-ai-marketplace'); ?></h1>
    
    <div class="vortex-metrics-cards">
        <div class="vortex-metric-card">
            <div class="vortex-metric-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="vortex-metric-content">
                <div class="vortex-metric-value"><?php echo number_format($total_tokens); ?></div>
                <div class="vortex-metric-label"><?php esc_html_e('Total Tokens Used', 'vortex-ai-marketplace'); ?></div>
            </div>
        </div>
        
        <div class="vortex-metric-card">
            <div class="vortex-metric-icon">
                <span class="dashicons dashicons-controls-repeat"></span>
            </div>
            <div class="vortex-metric-content">
                <div class="vortex-metric-value"><?php echo number_format($total_requests); ?></div>
                <div class="vortex-metric-label"><?php esc_html_e('Total API Requests', 'vortex-ai-marketplace'); ?></div>
            </div>
        </div>
        
        <div class="vortex-metric-card">
            <div class="vortex-metric-icon">
                <span class="dashicons dashicons-building"></span>
            </div>
            <div class="vortex-metric-content">
                <div class="vortex-metric-value"><?php echo count($metrics); ?></div>
                <div class="vortex-metric-label"><?php esc_html_e('Active AI Providers', 'vortex-ai-marketplace'); ?></div>
            </div>
        </div>
    </div>
    
    <div class="vortex-dashboard-card vortex-usage-summary">
        <h2><?php esc_html_e('AI Usage Summary', 'vortex-ai-marketplace'); ?></h2>
        
        <div class="vortex-summary-text">
            <p>
                <?php 
                echo sprintf(
                    esc_html__('Your AI systems have processed %s tokens across %s requests. Logs are retained for %d days.', 'vortex-ai-marketplace'),
                    '<strong>' . number_format($total_tokens) . '</strong>',
                    '<strong>' . number_format($total_requests) . '</strong>',
                    intval(get_option('vortex_ai_settings')['log_retention_days'] ?? 30)
                ); 
                ?>
            </p>
            
            <p>
                <?php
                // Get first and last request dates
                $first_request = $wpdb->get_var("SELECT MIN(timestamp) FROM $table_name");
                $last_request = $wpdb->get_var("SELECT MAX(timestamp) FROM $table_name");
                
                if ($first_request && $last_request) {
                    echo sprintf(
                        esc_html__('Data collected from %s to %s.', 'vortex-ai-marketplace'),
                        '<strong>' . date_i18n(get_option('date_format'), strtotime($first_request)) . '</strong>',
                        '<strong>' . date_i18n(get_option('date_format'), strtotime($last_request)) . '</strong>'
                    );
                }
                ?>
            </p>
        </div>
        
        <div class="vortex-cleanup-action">
            <form method="post" action="">
                <?php wp_nonce_field('vortex_run_cleanup'); ?>
                <input type="hidden" name="action" value="run_cleanup">
                <button type="submit" class="button button-secondary">
                    <?php esc_html_e('Run Cleanup Now', 'vortex-ai-marketplace'); ?>
                </button>
                <span class="description">
                    <?php esc_html_e('Manually remove logs older than the retention period.', 'vortex-ai-marketplace'); ?>
                </span>
            </form>
        </div>
    </div>
    
    <div class="vortex-dashboard-row">
        <div class="vortex-dashboard-column">
            <div class="vortex-dashboard-card">
                <h2><?php esc_html_e('Provider Usage', 'vortex-ai-marketplace'); ?></h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Provider', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Requests', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Tokens', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Last Used', 'vortex-ai-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($metrics as $provider => $data): ?>
                            <tr>
                                <td><?php echo esc_html(ucfirst($provider)); ?></td>
                                <td><?php echo number_format($data['total_requests']); ?></td>
                                <td><?php echo number_format($data['total_tokens']); ?></td>
                                <td><?php echo !empty($data['last_request']) ? esc_html(human_time_diff(strtotime($data['last_request']), current_time('timestamp')) . ' ago') : 'Never'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($metrics)): ?>
                            <tr>
                                <td colspan="4"><?php esc_html_e('No AI usage data available yet.', 'vortex-ai-marketplace'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="vortex-dashboard-column">
            <div class="vortex-dashboard-card">
                <h2><?php esc_html_e('Recent API Requests', 'vortex-ai-marketplace'); ?></h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Time', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Provider', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Task', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Tokens', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Status', 'vortex-ai-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html(human_time_diff(strtotime($log['timestamp']), current_time('timestamp')) . ' ago'); ?></td>
                                <td><?php echo esc_html(ucfirst($log['provider'])); ?></td>
                                <td><?php echo esc_html(ucfirst($log['task'])); ?></td>
                                <td><?php echo number_format($log['tokens_used']); ?></td>
                                <td>
                                    <span class="vortex-status-<?php echo esc_attr($log['status']); ?>">
                                        <?php echo esc_html(ucfirst($log['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($recent_logs)): ?>
                            <tr>
                                <td colspan="5"><?php esc_html_e('No recent API requests.', 'vortex-ai-marketplace'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.vortex-metrics-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
}

.vortex-metric-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 300px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.vortex-metric-icon {
    background: #f0f6fc;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.vortex-metric-icon .dashicons {
    color: #2271b1;
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.vortex-metric-value {
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
}

.vortex-metric-label {
    color: #646970;
    margin-top: 5px;
}

.vortex-dashboard-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.vortex-dashboard-column {
    flex: 1;
    min-width: 48%;
}

.vortex-dashboard-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.vortex-status-success {
    color: #46b450;
}

.vortex-status-error {
    color: #dc3232;
}

.vortex-usage-summary {
    margin-bottom: 20px;
}

.vortex-summary-text {
    margin-bottom: 20px;
}

.vortex-cleanup-action {
    padding-top: 10px;
    border-top: 1px solid #f0f0f1;
}
</style> 