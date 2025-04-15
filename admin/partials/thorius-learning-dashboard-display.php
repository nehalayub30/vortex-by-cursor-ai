<?php
/**
 * Display template for the Thorius Learning Dashboard
 *
 * @link       https://vortexai.io
 * @since      1.0.0
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
    <h1 class="wp-heading-inline"><?php _e('Thorius Learning Dashboard', 'vortex-ai-marketplace'); ?></h1>
    
    <div id="thorius-notifications" class="thorius-notifications-container"></div>
    
    <!-- Dashboard Controls -->
    <div class="thorius-dashboard-controls">
        <div class="thorius-time-range">
            <span class="dashicons dashicons-calendar-alt"></span>
            <span id="thorius-time-range-label"><?php echo esc_html($dashboard_data['time_range']); ?></span>
            <button id="thorius-change-range" class="button-link">
                <span class="dashicons dashicons-edit"></span>
            </button>
        </div>
        
        <div class="thorius-dashboard-actions">
            <button id="thorius-refresh-dashboard" class="button">
                <span class="dashicons dashicons-update"></span> <?php _e('Refresh Data', 'vortex-ai-marketplace'); ?>
            </button>
            <button id="thorius-toggle-settings" class="button">
                <span class="dashicons dashicons-admin-generic"></span> <?php _e('Settings', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
    </div>
    
    <!-- Settings Panel (Hidden by Default) -->
    <div id="thorius-settings-panel" class="thorius-settings-panel" style="display: none;">
        <h2><?php _e('Dashboard Settings', 'vortex-ai-marketplace'); ?></h2>
        
        <form id="thorius-settings-form" method="post">
            <div class="thorius-settings-section">
                <h3><?php _e('Display Options', 'vortex-ai-marketplace'); ?></h3>
                
                <div class="thorius-settings-field">
                    <label><?php _e('Date Range:', 'vortex-ai-marketplace'); ?></label>
                    <select name="date_range">
                        <option value="7d" <?php selected($dashboard_data['settings']['date_range'], '7d'); ?>><?php _e('Last 7 Days', 'vortex-ai-marketplace'); ?></option>
                        <option value="30d" <?php selected($dashboard_data['settings']['date_range'], '30d'); ?>><?php _e('Last 30 Days', 'vortex-ai-marketplace'); ?></option>
                        <option value="90d" <?php selected($dashboard_data['settings']['date_range'], '90d'); ?>><?php _e('Last 90 Days', 'vortex-ai-marketplace'); ?></option>
                        <option value="365d" <?php selected($dashboard_data['settings']['date_range'], '365d'); ?>><?php _e('Last Year', 'vortex-ai-marketplace'); ?></option>
                        <option value="custom" <?php selected($dashboard_data['settings']['date_range'], 'custom'); ?>><?php _e('Custom Range', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div id="thorius-custom-date-range" class="thorius-custom-date-inputs" <?php echo $dashboard_data['settings']['date_range'] === 'custom' ? '' : 'style="display: none;"'; ?>>
                    <div class="thorius-date-field">
                        <label><?php _e('Start Date:', 'vortex-ai-marketplace'); ?></label>
                        <input type="date" name="custom_start" value="<?php echo esc_attr($dashboard_data['settings']['custom_start']); ?>">
                    </div>
                    <div class="thorius-date-field">
                        <label><?php _e('End Date:', 'vortex-ai-marketplace'); ?></label>
                        <input type="date" name="custom_end" value="<?php echo esc_attr($dashboard_data['settings']['custom_end']); ?>">
                    </div>
                </div>
                
                <div class="thorius-settings-field">
                    <label><?php _e('Metrics to Display:', 'vortex-ai-marketplace'); ?></label>
                    <div class="thorius-checkbox-grid">
                        <?php 
                        $available_metrics = array(
                            'accuracy' => __('Accuracy', 'vortex-ai-marketplace'),
                            'adaptations' => __('Adaptations', 'vortex-ai-marketplace'),
                            'learning_rate' => __('Learning Rate', 'vortex-ai-marketplace'),
                            'efficiency' => __('Efficiency', 'vortex-ai-marketplace')
                        );
                        
                        foreach ($available_metrics as $metric_id => $metric_label) :
                            $checked = in_array($metric_id, $dashboard_data['settings']['metrics_to_show']);
                        ?>
                            <div class="thorius-checkbox-item">
                                <input type="checkbox" 
                                       id="metric_<?php echo esc_attr($metric_id); ?>" 
                                       name="metrics_to_show[]" 
                                       value="<?php echo esc_attr($metric_id); ?>"
                                       <?php checked($checked); ?>>
                                <label for="metric_<?php echo esc_attr($metric_id); ?>"><?php echo esc_html($metric_label); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="thorius-settings-field">
                    <label><?php _e('Number of Recent Adaptations:', 'vortex-ai-marketplace'); ?></label>
                    <input type="number" name="recent_limit" min="1" max="50" step="1" value="<?php echo esc_attr($dashboard_data['settings']['recent_limit']); ?>">
                </div>
                
                <div class="thorius-settings-field">
                    <label><?php _e('Auto-Refresh Rate (minutes):', 'vortex-ai-marketplace'); ?></label>
                    <input type="number" name="refresh_rate" min="0" max="60" step="1" value="<?php echo esc_attr($dashboard_data['settings']['refresh_rate']); ?>">
                    <p class="description"><?php _e('Set to 0 to disable auto-refresh', 'vortex-ai-marketplace'); ?></p>
                </div>
                
                <h3><?php _e('Filter Agents', 'vortex-ai-marketplace'); ?></h3>
                <div class="thorius-settings-field">
                    <div class="thorius-agent-filters">
                        <?php foreach ($dashboard_data['agents'] as $agent) : ?>
                            <div class="thorius-checkbox-item">
                                <input type="checkbox" 
                                       id="agent_<?php echo esc_attr($agent['agent_id']); ?>" 
                                       name="agents_to_show[]" 
                                       value="<?php echo esc_attr($agent['agent_id']); ?>"
                                       <?php checked(empty($dashboard_data['settings']['agents_to_show']) || in_array($agent['agent_id'], $dashboard_data['settings']['agents_to_show'])); ?>>
                                <label for="agent_<?php echo esc_attr($agent['agent_id']); ?>"><?php echo esc_html($agent['agent_name']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="thorius-settings-actions">
                <button type="submit" class="button button-primary"><?php _e('Save Settings', 'vortex-ai-marketplace'); ?></button>
                <button type="button" id="thorius-cancel-settings" class="button"><?php _e('Cancel', 'vortex-ai-marketplace'); ?></button>
            </div>
        </form>
    </div>
    
    <!-- Dashboard Grid -->
    <div class="thorius-dashboard-grid">
        <!-- Learning Statistics -->
        <div class="thorius-card thorius-stats-card">
            <div class="thorius-card-header">
                <h2><?php _e('Learning Statistics', 'vortex-ai-marketplace'); ?></h2>
            </div>
            <div class="thorius-card-content">
                <div class="thorius-stats-grid">
                    <div class="thorius-stat-item">
                        <span class="thorius-stat-icon dashicons dashicons-groups"></span>
                        <div class="thorius-stat-data">
                            <span class="thorius-stat-value"><?php echo esc_html($dashboard_data['stats']['total_agents']); ?></span>
                            <span class="thorius-stat-label"><?php _e('Total Agents', 'vortex-ai-marketplace'); ?></span>
                        </div>
                    </div>
                    
                    <div class="thorius-stat-item">
                        <span class="thorius-stat-icon dashicons dashicons-admin-network"></span>
                        <div class="thorius-stat-data">
                            <span class="thorius-stat-value"><?php echo esc_html($dashboard_data['stats']['active_agents']); ?></span>
                            <span class="thorius-stat-label"><?php _e('Active Agents', 'vortex-ai-marketplace'); ?></span>
                        </div>
                    </div>
                    
                    <div class="thorius-stat-item">
                        <span class="thorius-stat-icon dashicons dashicons-update"></span>
                        <div class="thorius-stat-data">
                            <span class="thorius-stat-value"><?php echo esc_html($dashboard_data['stats']['total_adaptations']); ?></span>
                            <span class="thorius-stat-label"><?php _e('Total Adaptations', 'vortex-ai-marketplace'); ?></span>
                        </div>
                    </div>
                    
                    <div class="thorius-stat-item">
                        <span class="thorius-stat-icon dashicons dashicons-chart-line"></span>
                        <div class="thorius-stat-data">
                            <span class="thorius-stat-value"><?php echo esc_html($dashboard_data['stats']['learning_efficiency']); ?></span>
                            <span class="thorius-stat-label"><?php _e('Learning Efficiency', 'vortex-ai-marketplace'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Agent Performance Chart -->
        <div class="thorius-card thorius-chart-card">
            <div class="thorius-card-header">
                <h2><?php _e('Agent Performance', 'vortex-ai-marketplace'); ?></h2>
            </div>
            <div class="thorius-card-content">
                <div id="thorius-performance-chart" class="thorius-chart-container">
                    <canvas id="agent-performance-chart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Recent Adaptations -->
        <div class="thorius-card thorius-adaptations-card">
            <div class="thorius-card-header">
                <h2><?php _e('Recent Adaptations', 'vortex-ai-marketplace'); ?></h2>
            </div>
            <div class="thorius-card-content">
                <div id="thorius-recent-adaptations">
                    <?php if (empty($dashboard_data['recent_adaptations'])) : ?>
                        <div class="thorius-no-data"><?php _e('No recent adaptations found', 'vortex-ai-marketplace'); ?></div>
                    <?php else : ?>
                        <table class="thorius-data-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Agent', 'vortex-ai-marketplace'); ?></th>
                                    <th><?php _e('Type', 'vortex-ai-marketplace'); ?></th>
                                    <th><?php _e('Impact', 'vortex-ai-marketplace'); ?></th>
                                    <th><?php _e('Time', 'vortex-ai-marketplace'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard_data['recent_adaptations'] as $adaptation) : 
                                    $impact_class = '';
                                    $impact_level = isset($adaptation['impact']) ? $adaptation['impact'] : 'medium';
                                    
                                    switch ($impact_level) {
                                        case 'high':
                                            $impact_class = 'high-impact';
                                            break;
                                        case 'low':
                                            $impact_class = 'low-impact';
                                            break;
                                        default:
                                            $impact_class = 'medium-impact';
                                            break;
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo esc_html($adaptation['agent_name']); ?></td>
                                        <td><?php echo esc_html($adaptation['adaptation_type']); ?></td>
                                        <td><span class="thorius-impact-badge <?php echo $impact_class; ?>"><?php echo esc_html(ucfirst($impact_level)); ?></span></td>
                                        <td><?php echo human_time_diff(strtotime($adaptation['adaptation_time']), current_time('timestamp')); ?> <?php _e('ago', 'vortex-ai-marketplace'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Agent Performance Metrics -->
        <div class="thorius-card thorius-agents-card">
            <div class="thorius-card-header">
                <h2><?php _e('Agent Metrics', 'vortex-ai-marketplace'); ?></h2>
            </div>
            <div class="thorius-card-content">
                <?php if (empty($dashboard_data['agents'])) : ?>
                    <div class="thorius-no-data"><?php _e('No agent data available', 'vortex-ai-marketplace'); ?></div>
                <?php else : ?>
                    <table class="thorius-data-table thorius-agents-table">
                        <thead>
                            <tr>
                                <th><?php _e('Agent', 'vortex-ai-marketplace'); ?></th>
                                <?php if (in_array('accuracy', $dashboard_data['settings']['metrics_to_show'])) : ?>
                                    <th><?php _e('Accuracy', 'vortex-ai-marketplace'); ?></th>
                                <?php endif; ?>
                                
                                <?php if (in_array('adaptations', $dashboard_data['settings']['metrics_to_show'])) : ?>
                                    <th><?php _e('Adaptations', 'vortex-ai-marketplace'); ?></th>
                                <?php endif; ?>
                                
                                <?php if (in_array('learning_rate', $dashboard_data['settings']['metrics_to_show'])) : ?>
                                    <th><?php _e('Learning Rate', 'vortex-ai-marketplace'); ?></th>
                                <?php endif; ?>
                                
                                <?php if (in_array('efficiency', $dashboard_data['settings']['metrics_to_show'])) : ?>
                                    <th><?php _e('Efficiency', 'vortex-ai-marketplace'); ?></th>
                                <?php endif; ?>
                                
                                <th><?php _e('Last Update', 'vortex-ai-marketplace'); ?></th>
                                <th><?php _e('Actions', 'vortex-ai-marketplace'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dashboard_data['agents'] as $agent) : ?>
                                <tr data-agent-id="<?php echo esc_attr($agent['agent_id']); ?>">
                                    <td class="thorius-agent-name"><?php echo esc_html($agent['agent_name']); ?></td>
                                    
                                    <?php if (in_array('accuracy', $dashboard_data['settings']['metrics_to_show'])) : ?>
                                        <td class="thorius-metric-cell thorius-accuracy" data-metric="accuracy">
                                            <?php echo isset($agent['avg_accuracy']) ? number_format($agent['avg_accuracy'], 1) . '%' : '0%'; ?>
                                        </td>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array('adaptations', $dashboard_data['settings']['metrics_to_show'])) : ?>
                                        <td class="thorius-metric-cell thorius-adaptations" data-metric="adaptations">
                                            <?php echo isset($agent['total_adaptations']) ? intval($agent['total_adaptations']) : 0; ?>
                                        </td>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array('learning_rate', $dashboard_data['settings']['metrics_to_show'])) : ?>
                                        <td class="thorius-metric-cell thorius-learning-rate" data-metric="learning_rate">
                                            <?php echo isset($agent['avg_learning_rate']) ? number_format($agent['avg_learning_rate'], 1) . '%' : '0%'; ?>
                                        </td>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array('efficiency', $dashboard_data['settings']['metrics_to_show'])) : ?>
                                        <td class="thorius-metric-cell thorius-efficiency" data-metric="efficiency">
                                            <?php echo isset($agent['avg_efficiency']) ? number_format($agent['avg_efficiency'], 1) . '%' : '0%'; ?>
                                        </td>
                                    <?php endif; ?>
                                    
                                    <td class="thorius-last-update">
                                        <?php 
                                        if (isset($agent['last_update'])) {
                                            echo human_time_diff(strtotime($agent['last_update']), current_time('timestamp')) . ' ' . __('ago', 'vortex-ai-marketplace');
                                        } else {
                                            _e('Never', 'vortex-ai-marketplace');
                                        }
                                        ?>
                                    </td>
                                    <td class="thorius-agent-actions">
                                        <button class="button thorius-trigger-adaptation" data-agent="<?php echo esc_attr($agent['agent_id']); ?>">
                                            <?php _e('Adapt', 'vortex-ai-marketplace'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div> 