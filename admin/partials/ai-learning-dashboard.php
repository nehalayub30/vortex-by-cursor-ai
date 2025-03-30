<?php
/**
 * AI Learning Dashboard Template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get statistics
global $wpdb;
$interactions_table = $wpdb->prefix . 'vortex_user_interactions';
$insights_table = $wpdb->prefix . 'vortex_ai_insights';

$total_interactions = $wpdb->get_var("SELECT COUNT(*) FROM $interactions_table");
$total_insights = $wpdb->get_var("SELECT COUNT(*) FROM $insights_table");
$applied_insights = $wpdb->get_var("SELECT COUNT(*) FROM $insights_table WHERE applied = 1");

// Get agent breakdown
$agent_stats = $wpdb->get_results(
    "SELECT ai_agent, COUNT(*) as count FROM $insights_table GROUP BY ai_agent",
    ARRAY_A
);

// Get recent insights
$recent_insights = $wpdb->get_results(
    "SELECT * FROM $insights_table ORDER BY created_at DESC LIMIT 10",
    ARRAY_A
);
?>

<div class="wrap vortex-ai-learning-dashboard">
    <h1><?php esc_html_e('AI Learning Dashboard', 'vortex-ai-marketplace'); ?></h1>
    
    <div class="vortex-dashboard-row">
        <div class="vortex-dashboard-card vortex-metrics-card">
            <h2><?php esc_html_e('Learning Metrics', 'vortex-ai-marketplace'); ?></h2>
            <div class="vortex-metrics-grid">
                <div class="vortex-metric">
                    <span class="vortex-metric-value"><?php echo number_format($total_interactions); ?></span>
                    <span class="vortex-metric-label"><?php esc_html_e('User Interactions', 'vortex-ai-marketplace'); ?></span>
                </div>
                <div class="vortex-metric">
                    <span class="vortex-metric-value"><?php echo number_format($total_insights); ?></span>
                    <span class="vortex-metric-label"><?php esc_html_e('AI Insights Generated', 'vortex-ai-marketplace'); ?></span>
                </div>
                <div class="vortex-metric">
                    <span class="vortex-metric-value"><?php echo number_format($applied_insights); ?></span>
                    <span class="vortex-metric-label"><?php esc_html_e('Insights Applied', 'vortex-ai-marketplace'); ?></span>
                </div>
                <div class="vortex-metric">
                    <span class="vortex-metric-value"><?php echo $total_insights > 0 ? round(($applied_insights / $total_insights) * 100) : 0; ?>%</span>
                    <span class="vortex-metric-label"><?php esc_html_e('Application Rate', 'vortex-ai-marketplace'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="vortex-dashboard-card vortex-agent-stats">
            <h2><?php esc_html_e('AI Agent Learning', 'vortex-ai-marketplace'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('AI Agent', 'vortex-ai-marketplace'); ?></th>
                        <th><?php esc_html_e('Insights Generated', 'vortex-ai-marketplace'); ?></th>
                        <th><?php esc_html_e('Learning Progress', 'vortex-ai-marketplace'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agent_stats as $stat): ?>
                    <tr>
                        <td><?php echo esc_html($stat['ai_agent']); ?></td>
                        <td><?php echo number_format($stat['count']); ?></td>
                        <td>
                            <div class="vortex-progress-bar">
                                <?php 
                                $progress = min(100, ($stat['count'] / 100) * 100);
                                ?>
                                <div class="vortex-progress-fill" style="width: <?php echo esc_attr($progress); ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="vortex-dashboard-card vortex-recent-insights">
        <h2><?php esc_html_e('Recent AI Insights', 'vortex-ai-marketplace'); ?></h2>
        <?php if (empty($recent_insights)): ?>
            <p><?php esc_html_e('No insights have been generated yet.', 'vortex-ai-marketplace'); ?></p>
        <?php else: ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('AI Agent', 'vortex-ai-marketplace'); ?></th>
                        <th><?php esc_html_e('Insight Type', 'vortex-ai-marketplace'); ?></th>
                        <th><?php esc_html_e('Created', 'vortex-ai-marketplace'); ?></th>
                        <th><?php esc_html_e('Status', 'vortex-ai-marketplace'); ?></th>
                        <th><?php esc_html_e('Actions', 'vortex-ai-marketplace'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_insights as $insight): ?>
                    <tr>
                        <td><?php echo esc_html($insight['ai_agent']); ?></td>
                        <td><?php echo esc_html($insight['insight_type']); ?></td>
                        <td><?php echo esc_html(human_time_diff(strtotime($insight['created_at']), current_time('timestamp'))); ?> <?php esc_html_e('ago', 'vortex-ai-marketplace'); ?></td>
                        <td>
                            <?php if ($insight['applied']): ?>
                                <span class="vortex-badge vortex-badge-success"><?php esc_html_e('Applied', 'vortex-ai-marketplace'); ?></span>
                            <?php else: ?>
                                <span class="vortex-badge vortex-badge-pending"><?php esc_html_e('Pending', 'vortex-ai-marketplace'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small vortex-view-insight" data-id="<?php echo esc_attr($insight['id']); ?>"><?php esc_html_e('View', 'vortex-ai-marketplace'); ?></button>
                            <?php if (!$insight['applied']): ?>
                                <button type="button" class="button button-small vortex-apply-insight" data-id="<?php echo esc_attr($insight['id']); ?>"><?php esc_html_e('Apply', 'vortex-ai-marketplace'); ?></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="vortex-dashboard-card vortex-learning-settings">
        <h2><?php esc_html_e('AI Learning Settings', 'vortex-ai-marketplace'); ?></h2>
        <form method="post" action="options.php">
            <?php 
            settings_fields('vortex_ai_learning_settings');
            $settings = get_option('vortex_ai_learning_settings', array(
                'enabled' => true,
                'user_consent_required' => true,
                'learning_frequency' => 'daily',
                'data_retention_days' => 90
            ));
            ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('AI Learning', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="vortex_ai_learning_settings[enabled]" value="1" <?php checked($settings['enabled']); ?>>
                            <?php esc_html_e('Enable AI learning capabilities', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('When enabled, AI agents will learn from user interactions to improve their performance over time.', 'vortex-ai-marketplace'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('User Consent', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="vortex_ai_learning_settings[user_consent_required]" value="1" <?php checked($settings['user_consent_required']); ?>>
                            <?php esc_html_e('Require explicit user consent for data collection', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('When enabled, users must explicitly opt-in to data collection for AI learning.', 'vortex-ai-marketplace'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Learning Frequency', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <select name="vortex_ai_learning_settings[learning_frequency]">
                            <option value="hourly" <?php selected($settings['learning_frequency'], 'hourly'); ?>><?php esc_html_e('Hourly', 'vortex-ai-marketplace'); ?></option>
                            <option value="daily" <?php selected($settings['learning_frequency'], 'daily'); ?>><?php esc_html_e('Daily', 'vortex-ai-marketplace'); ?></option>
                            <option value="weekly" <?php selected($settings['learning_frequency'], 'weekly'); ?>><?php esc_html_e('Weekly', 'vortex-ai-marketplace'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('How often AI agents should process collected data for learning.', 'vortex-ai-marketplace'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Data Retention', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <input type="number" name="vortex_ai_learning_settings[data_retention_days]" value="<?php echo esc_attr($settings['data_retention_days']); ?>" min="1" max="365" class="small-text"> <?php esc_html_e('days', 'vortex-ai-marketplace'); ?>
                        <p class="description"><?php esc_html_e('How long to retain user interaction data for learning purposes.', 'vortex-ai-marketplace'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Save Settings', 'vortex-ai-marketplace')); ?>
        </form>
    </div>
</div>

<style>
.vortex-metrics-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-top: 15px;
}

.vortex-metric {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-align: center;
}

.vortex-metric-value {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
    margin-bottom: 5px;
}

.vortex-metric-label {
    font-size: 14px;
    color: #50575e;
}

.vortex-progress-bar {
    height: 12px;
    background: #f0f0f1;
    border-radius: 6px;
    overflow: hidden;
}

.vortex-progress-fill {
    height: 100%;
    background: #2271b1;
}

.vortex-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 500;
}

.vortex-badge-success {
    background: #edfaef;
    color: #00a32a;
}

.vortex-badge-pending {
    background: #fcf9e8;
    color: #bd8600;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // View insight details
    $('.vortex-view-insight').on('click', function() {
        var insightId = $(this).data('id');
        // In a real implementation, this would show a modal with insight details
        alert('View insight #' + insightId + ' (would show detail modal in production)');
    });
    
    // Apply insight
    $('.vortex-apply-insight').on('click', function() {
        var insightId = $(this).data('id');
        // In a real implementation, this would apply the insight via AJAX
        alert('Apply insight #' + insightId + ' (would apply via AJAX in production)');
    });
});
</script> 