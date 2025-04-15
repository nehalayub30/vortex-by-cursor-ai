<?php
/**
 * Thorius Learning Dashboard template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap thorius-learning-dashboard">
    <h1><span class="dashicons dashicons-welcome-learn-more"></span> Thorius Learning Dashboard</h1>
    
    <div id="thorius-notifications"></div>
    
    <div class="thorius-dashboard-container">
        <!-- Overview Panel -->
        <div class="thorius-dashboard-panel thorius-overview-panel">
            <h2>Learning Overview</h2>
            <div class="thorius-metrics-grid">
                <div class="thorius-metric-card">
                    <h3>Total Interactions</h3>
                    <div class="metric-value" id="total-interactions"><?php echo esc_html($metrics['total_interactions']); ?></div>
                </div>
                <div class="thorius-metric-card">
                    <h3>Total Feedback</h3>
                    <div class="metric-value" id="total-feedback"><?php echo esc_html($metrics['total_feedback']); ?></div>
                </div>
                <div class="thorius-metric-card">
                    <h3>Total Adaptations</h3>
                    <div class="metric-value" id="total-adaptations"><?php echo esc_html($metrics['total_adaptations']); ?></div>
                </div>
                <div class="thorius-metric-card">
                    <h3>Learning Rate</h3>
                    <div class="metric-value" id="learning-rate"><?php echo esc_html($metrics['learning_rate']); ?></div>
                </div>
            </div>
            <div class="thorius-refresh-info">
                <span class="dashicons dashicons-update"></span> Metrics auto-refresh every 5 minutes.
                <button id="refresh-metrics" class="button">Refresh Now</button>
            </div>
        </div>
        
        <!-- Agent Metrics Panel -->
        <div class="thorius-dashboard-panel thorius-agents-panel">
            <h2>Agent Metrics</h2>
            
            <div class="thorius-agent-tabs">
                <div class="thorius-agent-tab-nav">
                    <?php foreach ($agents as $agent_id => $agent_metrics) : ?>
                        <button class="thorius-agent-tab-button <?php echo $agent_id === 'thorius' ? 'active' : ''; ?>" 
                                data-agent="<?php echo esc_attr($agent_id); ?>">
                            <?php echo esc_html(strtoupper($agent_id)); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <?php foreach ($agents as $agent_id => $agent_metrics) : ?>
                    <div class="thorius-agent-tab-content <?php echo $agent_id === 'thorius' ? 'active' : ''; ?>"
                         id="agent-tab-<?php echo esc_attr($agent_id); ?>">
                        
                        <div class="thorius-agent-metrics">
                            <div class="thorius-agent-metric">
                                <h4>Confidence</h4>
                                <div class="agent-metric-value" id="<?php echo esc_attr($agent_id); ?>-confidence">
                                    <?php echo esc_html($agent_metrics['confidence']); ?>
                                    <?php if ($agent_metrics['confidence_trend'] > 0) : ?>
                                        <span class="trend-up dashicons dashicons-arrow-up-alt"></span>
                                    <?php elseif ($agent_metrics['confidence_trend'] < 0) : ?>
                                        <span class="trend-down dashicons dashicons-arrow-down-alt"></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="thorius-agent-metric">
                                <h4>Accuracy</h4>
                                <div class="agent-metric-value" id="<?php echo esc_attr($agent_id); ?>-accuracy">
                                    <?php echo esc_html($agent_metrics['accuracy']); ?>
                                    <?php if ($agent_metrics['accuracy_trend'] > 0) : ?>
                                        <span class="trend-up dashicons dashicons-arrow-up-alt"></span>
                                    <?php elseif ($agent_metrics['accuracy_trend'] < 0) : ?>
                                        <span class="trend-down dashicons dashicons-arrow-down-alt"></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="thorius-agent-metric">
                                <h4>Adaptability</h4>
                                <div class="agent-metric-value" id="<?php echo esc_attr($agent_id); ?>-adaptability">
                                    <?php echo esc_html($agent_metrics['adaptability']); ?>
                                    <?php if ($agent_metrics['adaptability_trend'] > 0) : ?>
                                        <span class="trend-up dashicons dashicons-arrow-up-alt"></span>
                                    <?php elseif ($agent_metrics['adaptability_trend'] < 0) : ?>
                                        <span class="trend-down dashicons dashicons-arrow-down-alt"></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="thorius-agent-metric">
                                <h4>Consistency</h4>
                                <div class="agent-metric-value" id="<?php echo esc_attr($agent_id); ?>-consistency">
                                    <?php echo esc_html($agent_metrics['consistency']); ?>
                                    <?php if ($agent_metrics['consistency_trend'] > 0) : ?>
                                        <span class="trend-up dashicons dashicons-arrow-up-alt"></span>
                                    <?php elseif ($agent_metrics['consistency_trend'] < 0) : ?>
                                        <span class="trend-down dashicons dashicons-arrow-down-alt"></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="thorius-agent-info">
                            <p>Last adaptation: <span id="<?php echo esc_attr($agent_id); ?>-last-adaptation">
                                <?php echo esc_html($agent_metrics['last_adaptation']); ?>
                            </span></p>
                        </div>
                        
                        <div class="thorius-agent-actions">
                            <button class="button thorius-adapt-button" data-agent="<?php echo esc_attr($agent_id); ?>">
                                <span class="dashicons dashicons-update"></span> Trigger Adaptation
                            </button>
                            <button class="button thorius-reset-button" data-agent="<?php echo esc_attr($agent_id); ?>">
                                <span class="dashicons dashicons-backup"></span> Reset Learning
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Recent Adaptations Panel -->
        <div class="thorius-dashboard-panel thorius-adaptations-panel">
            <h2>Recent Adaptations</h2>
            
            <table class="widefat thorius-adaptations-table" id="thorius-adaptations-table">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Date</th>
                        <th>Trigger</th>
                        <th>Impact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($adaptations)) : ?>
                        <?php foreach ($adaptations as $adaptation) : ?>
                            <tr>
                                <td><?php echo esc_html($adaptation['agent']); ?></td>
                                <td><?php echo esc_html($adaptation['date']); ?></td>
                                <td><?php echo esc_html($adaptation['trigger']); ?></td>
                                <td class="adaptation-impact <?php echo $adaptation['impact'] > 0 ? 'positive' : ($adaptation['impact'] < 0 ? 'negative' : ''); ?>">
                                    <?php if ($adaptation['impact'] > 0) : ?>
                                        +<?php echo esc_html($adaptation['impact']); ?>%
                                    <?php else : ?>
                                        <?php echo esc_html($adaptation['impact']); ?>%
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4">No adaptations recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Settings Panel -->
        <div class="thorius-dashboard-panel thorius-settings-panel">
            <h2>Learning Settings</h2>
            
            <form id="thorius-learning-settings-form">
                <div class="thorius-settings-grid">
                    <div class="thorius-setting">
                        <label for="auto-adaptation">Automatic Adaptation</label>
                        <select id="auto-adaptation" name="auto_adaptation">
                            <option value="enabled" <?php selected($settings['auto_adaptation'], 'enabled'); ?>>Enabled</option>
                            <option value="disabled" <?php selected($settings['auto_adaptation'], 'disabled'); ?>>Disabled</option>
                        </select>
                    </div>
                    
                    <div class="thorius-setting">
                        <label for="adaptation-threshold">Adaptation Threshold (%)</label>
                        <input type="number" id="adaptation-threshold" name="adaptation_threshold" 
                               min="0" max="100" value="<?php echo esc_attr($settings['adaptation_threshold']); ?>">
                    </div>
                    
                    <div class="thorius-setting">
                        <label for="learning-interval">Learning Interval</label>
                        <select id="learning-interval" name="learning_interval">
                            <option value="hourly" <?php selected($settings['learning_interval'], 'hourly'); ?>>Hourly</option>
                            <option value="daily" <?php selected($settings['learning_interval'], 'daily'); ?>>Daily</option>
                            <option value="weekly" <?php selected($settings['learning_interval'], 'weekly'); ?>>Weekly</option>
                        </select>
                    </div>
                    
                    <div class="thorius-setting">
                        <label for="feedback-weight">Feedback Weight</label>
                        <select id="feedback-weight" name="feedback_weight">
                            <option value="low" <?php selected($settings['feedback_weight'], 'low'); ?>>Low</option>
                            <option value="medium" <?php selected($settings['feedback_weight'], 'medium'); ?>>Medium</option>
                            <option value="high" <?php selected($settings['feedback_weight'], 'high'); ?>>High</option>
                        </select>
                    </div>
                    
                    <div class="thorius-setting">
                        <label for="confidence-threshold">Confidence Threshold (%)</label>
                        <input type="number" id="confidence-threshold" name="confidence_threshold" 
                               min="0" max="100" value="<?php echo esc_attr($settings['confidence_threshold']); ?>">
                    </div>
                </div>
                
                <div class="thorius-settings-actions">
                    <button type="submit" class="button button-primary">Save Settings</button>
                    <button type="reset" class="button">Reset</button>
                </div>
            </form>
        </div>
    </div>
</div> 