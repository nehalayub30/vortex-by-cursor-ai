<?php
/**
 * Thorius AI Agents Management
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get instance of Thorius
$thorius = null;
if (class_exists('Vortex_Thorius')) {
    $thorius = Vortex_Thorius::get_instance();
}

// Default agents configuration
$agents = array(
    'huraii' => array(
        'name' => 'HURAII',
        'description' => 'Advanced AI image generation and transformation',
        'capabilities' => array(
            'image_generation', 
            'image_transformation', 
            'style_transfer', 
            'nft_creation'
        ),
        'enabled' => get_option('vortex_thorius_enable_huraii', false),
        'settings' => get_option('vortex_huraii_settings', array())
    ),
    'cloe' => array(
        'name' => 'CLOE',
        'description' => 'Art discovery and curation assistant',
        'capabilities' => array(
            'art_discovery', 
            'style_analysis', 
            'art_recommendations', 
            'artist_discovery'
        ),
        'enabled' => get_option('vortex_thorius_enable_cloe', false),
        'settings' => get_option('vortex_cloe_settings', array())
    ),
    'strategist' => array(
        'name' => 'Business Strategist',
        'description' => 'Market insights and trend analysis',
        'capabilities' => array(
            'market_analysis', 
            'trend_prediction', 
            'price_optimization', 
            'audience_analysis'
        ),
        'enabled' => get_option('vortex_thorius_enable_strategist', false),
        'settings' => get_option('vortex_strategist_settings', array())
    ),
    'thorius' => array(
        'name' => 'Thorius',
        'description' => 'AI orchestration and integration system',
        'capabilities' => array(
            'agent_coordination', 
            'contextual_understanding', 
            'learning_adaptation', 
            'self_improvement'
        ),
        'enabled' => true, // Thorius is always enabled
        'settings' => get_option('vortex_thorius_settings', array())
    )
);

// Get real agent data if available
if ($thorius && method_exists($thorius, 'get_agents_data')) {
    $real_agents = $thorius->get_agents_data();
    if (!empty($real_agents)) {
        $agents = $real_agents;
    }
}

// Get agent health status if available
$health_status = array();
if ($thorius && method_exists($thorius, 'get_agents_health')) {
    $health_status = $thorius->get_agents_health();
}

// Get usage statistics
$usage_stats = array();
if ($thorius && method_exists($thorius, 'get_agents_usage_stats')) {
    $usage_stats = $thorius->get_agents_usage_stats();
}
?>

<div class="wrap vortex-thorius-agents">
    <h1><?php _e('Thorius AI Agents Management', 'vortex-ai-marketplace'); ?></h1>
    
    <div class="thorius-tab-navigation">
        <a href="#agent-overview" class="thorius-tab active"><?php _e('Agents Overview', 'vortex-ai-marketplace'); ?></a>
        <a href="#agent-configuration" class="thorius-tab"><?php _e('Configuration', 'vortex-ai-marketplace'); ?></a>
        <a href="#agent-performance" class="thorius-tab"><?php _e('Performance', 'vortex-ai-marketplace'); ?></a>
        <a href="#agent-capabilities" class="thorius-tab"><?php _e('Capabilities', 'vortex-ai-marketplace'); ?></a>
    </div>
    
    <!-- Agents Overview Tab -->
    <div id="agent-overview" class="thorius-tab-content active">
        <div class="thorius-agents-grid">
            <?php foreach ($agents as $agent_id => $agent): ?>
                <div class="thorius-agent-card <?php echo $agent['enabled'] ? 'enabled' : 'disabled'; ?>">
                    <div class="thorius-agent-header">
                        <h2><?php echo esc_html($agent['name']); ?></h2>
                        <div class="thorius-agent-status">
                            <?php if ($agent['enabled']): ?>
                                <span class="status-badge enabled"><?php _e('Enabled', 'vortex-ai-marketplace'); ?></span>
                            <?php else: ?>
                                <span class="status-badge disabled"><?php _e('Disabled', 'vortex-ai-marketplace'); ?></span>
                            <?php endif; ?>
                            
                            <?php if (isset($health_status[$agent_id])): ?>
                                <?php
                                $health = $health_status[$agent_id];
                                $health_class = $health >= 0.8 ? 'healthy' : ($health >= 0.5 ? 'warning' : 'critical');
                                ?>
                                <span class="health-badge <?php echo $health_class; ?>"><?php echo sprintf(__('Health: %s%%', 'vortex-ai-marketplace'), number_format($health * 100)); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="thorius-agent-description">
                        <?php echo esc_html($agent['description']); ?>
                    </div>
                    
                    <div class="thorius-agent-capabilities">
                        <h3><?php _e('Capabilities', 'vortex-ai-marketplace'); ?></h3>
                        <ul>
                            <?php foreach ($agent['capabilities'] as $capability): ?>
                                <li><?php echo esc_html(ucfirst(str_replace('_', ' ', $capability))); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <?php if (isset($usage_stats[$agent_id])): ?>
                        <div class="thorius-agent-usage">
                            <h3><?php _e('Usage Statistics', 'vortex-ai-marketplace'); ?></h3>
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <span class="stat-label"><?php _e('Queries', 'vortex-ai-marketplace'); ?></span>
                                    <span class="stat-value"><?php echo number_format($usage_stats[$agent_id]['queries']); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label"><?php _e('Users', 'vortex-ai-marketplace'); ?></span>
                                    <span class="stat-value"><?php echo number_format($usage_stats[$agent_id]['users']); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label"><?php _e('Success Rate', 'vortex-ai-marketplace'); ?></span>
                                    <span class="stat-value"><?php echo number_format($usage_stats[$agent_id]['success_rate'] * 100, 1); ?>%</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="thorius-agent-actions">
                        <?php if ($agent_id !== 'thorius'): // Thorius can't be disabled ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-thorius-settings&section=' . $agent_id)); ?>" class="button"><?php _e('Configure', 'vortex-ai-marketplace'); ?></a>
                            <?php if ($agent['enabled']): ?>
                                <button class="button toggle-agent-status" data-agent="<?php echo esc_attr($agent_id); ?>" data-status="disable"><?php _e('Disable', 'vortex-ai-marketplace'); ?></button>
                            <?php else: ?>
                                <button class="button button-primary toggle-agent-status" data-agent="<?php echo esc_attr($agent_id); ?>" data-status="enable"><?php _e('Enable', 'vortex-ai-marketplace'); ?></button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-thorius-settings')); ?>" class="button"><?php _e('Configure', 'vortex-ai-marketplace'); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Agent Configuration Tab -->
    <div id="agent-configuration" class="thorius-tab-content">
        <div class="thorius-settings-container">
            <h2><?php _e('Agent Configuration', 'vortex-ai-marketplace'); ?></h2>
            <p><?php _e('Configure settings for each AI agent in the Thorius ecosystem.', 'vortex-ai-marketplace'); ?></p>
            
            <form id="thorius-agents-config" method="post" action="options.php">
                <?php settings_fields('thorius_agents_settings'); ?>
                
                <div class="thorius-agents-settings-grid">
                    <?php foreach ($agents as $agent_id => $agent): ?>
                        <div class="agent-config-section">
                            <h3><?php echo esc_html($agent['name']); ?></h3>
                            
                            <?php if ($agent_id !== 'thorius'): // Thorius can't be disabled ?>
                                <div class="form-field">
                                    <label for="<?php echo esc_attr($agent_id); ?>-enabled">
                                        <input type="checkbox" id="<?php echo esc_attr($agent_id); ?>-enabled" 
                                               name="vortex_thorius_enable_<?php echo esc_attr($agent_id); ?>" 
                                               value="1" <?php checked($agent['enabled'], true); ?>>
                                        <?php echo sprintf(__('Enable %s', 'vortex-ai-marketplace'), $agent['name']); ?>
                                    </label>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-field">
                                <label for="<?php echo esc_attr($agent_id); ?>-model">
                                    <?php _e('AI Model', 'vortex-ai-marketplace'); ?>
                                </label>
                                <select id="<?php echo esc_attr($agent_id); ?>-model" 
                                        name="vortex_<?php echo esc_attr($agent_id); ?>_settings[model]">
                                    <option value="standard" <?php selected(isset($agent['settings']['model']) ? $agent['settings']['model'] : 'standard', 'standard'); ?>>
                                        <?php _e('Standard', 'vortex-ai-marketplace'); ?>
                                    </option>
                                    <option value="enhanced" <?php selected(isset($agent['settings']['model']) ? $agent['settings']['model'] : 'standard', 'enhanced'); ?>>
                                        <?php _e('Enhanced', 'vortex-ai-marketplace'); ?>
                                    </option>
                                    <option value="premium" <?php selected(isset($agent['settings']['model']) ? $agent['settings']['model'] : 'standard', 'premium'); ?>>
                                        <?php _e('Premium', 'vortex-ai-marketplace'); ?>
                                    </option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label for="<?php echo esc_attr($agent_id); ?>-learning">
                                    <?php _e('Learning Mode', 'vortex-ai-marketplace'); ?>
                                </label>
                                <select id="<?php echo esc_attr($agent_id); ?>-learning" 
                                        name="vortex_<?php echo esc_attr($agent_id); ?>_settings[learning_mode]">
                                    <option value="passive" <?php selected(isset($agent['settings']['learning_mode']) ? $agent['settings']['learning_mode'] : 'passive', 'passive'); ?>>
                                        <?php _e('Passive (Observe Only)', 'vortex-ai-marketplace'); ?>
                                    </option>
                                    <option value="active" <?php selected(isset($agent['settings']['learning_mode']) ? $agent['settings']['learning_mode'] : 'passive', 'active'); ?>>
                                        <?php _e('Active (Adapt Automatically)', 'vortex-ai-marketplace'); ?>
                                    </option>
                                    <option value="manual" <?php selected(isset($agent['settings']['learning_mode']) ? $agent['settings']['learning_mode'] : 'passive', 'manual'); ?>>
                                        <?php _e('Manual (Admin Approval)', 'vortex-ai-marketplace'); ?>
                                    </option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label for="<?php echo esc_attr($agent_id); ?>-logging">
                                    <input type="checkbox" id="<?php echo esc_attr($agent_id); ?>-logging" 
                                           name="vortex_<?php echo esc_attr($agent_id); ?>_settings[enable_logging]" 
                                           value="1" <?php checked(isset($agent['settings']['enable_logging']) ? $agent['settings']['enable_logging'] : false, true); ?>>
                                    <?php _e('Enable Enhanced Logging', 'vortex-ai-marketplace'); ?>
                                </label>
                            </div>
                            
                            <div class="form-field">
                                <label for="<?php echo esc_attr($agent_id); ?>-priority">
                                    <?php _e('Processing Priority', 'vortex-ai-marketplace'); ?>
                                </label>
                                <select id="<?php echo esc_attr($agent_id); ?>-priority" 
                                        name="vortex_<?php echo esc_attr($agent_id); ?>_settings[priority]">
                                    <option value="low" <?php selected(isset($agent['settings']['priority']) ? $agent['settings']['priority'] : 'normal', 'low'); ?>>
                                        <?php _e('Low', 'vortex-ai-marketplace'); ?>
                                    </option>
                                    <option value="normal" <?php selected(isset($agent['settings']['priority']) ? $agent['settings']['priority'] : 'normal', 'normal'); ?>>
                                        <?php _e('Normal', 'vortex-ai-marketplace'); ?>
                                    </option>
                                    <option value="high" <?php selected(isset($agent['settings']['priority']) ? $agent['settings']['priority'] : 'normal', 'high'); ?>>
                                        <?php _e('High', 'vortex-ai-marketplace'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php submit_button(__('Save Agent Settings', 'vortex-ai-marketplace')); ?>
            </form>
        </div>
    </div>
    
    <!-- Agent Performance Tab -->
    <div id="agent-performance" class="thorius-tab-content">
        <div class="thorius-performance-container">
            <h2><?php _e('AI Agent Performance Metrics', 'vortex-ai-marketplace'); ?></h2>
            <p><?php _e('View detailed performance metrics for each AI agent.', 'vortex-ai-marketplace'); ?></p>
            
            <div class="thorius-period-selector">
                <label for="performance-period"><?php _e('Time Period:', 'vortex-ai-marketplace'); ?></label>
                <select id="performance-period">
                    <option value="7"><?php _e('Last 7 Days', 'vortex-ai-marketplace'); ?></option>
                    <option value="30" selected><?php _e('Last 30 Days', 'vortex-ai-marketplace'); ?></option>
                    <option value="90"><?php _e('Last 90 Days', 'vortex-ai-marketplace'); ?></option>
                </select>
                <button id="refresh-metrics" class="button"><?php _e('Refresh Metrics', 'vortex-ai-marketplace'); ?></button>
            </div>
            
            <!-- Performance metrics will be loaded via AJAX -->
            <div id="thorius-performance-metrics"></div>
        </div>
    </div>
    
    <!-- Agent Capabilities Tab -->
    <div id="agent-capabilities" class="thorius-tab-content">
        <div class="thorius-capabilities-container">
            <h2><?php _e('AI Agent Capabilities Management', 'vortex-ai-marketplace'); ?></h2>
            <p><?php _e('Enable or disable specific capabilities for each AI agent.', 'vortex-ai-marketplace'); ?></p>
            
            <form id="thorius-capabilities-config" method="post" action="options.php">
                <?php settings_fields('thorius_capabilities_settings'); ?>
                
                <?php foreach ($agents as $agent_id => $agent): ?>
                    <div class="agent-capabilities-section">
                        <h3><?php echo esc_html($agent['name']); ?> <?php _e('Capabilities', 'vortex-ai-marketplace'); ?></h3>
                        
                        <table class="form-table">
                            <?php 
                            // Get available capabilities for this agent
                            $all_capabilities = isset($agent['all_capabilities']) ? $agent['all_capabilities'] : $agent['capabilities'];
                            $enabled_capabilities = isset($agent['enabled_capabilities']) ? $agent['enabled_capabilities'] : $agent['capabilities'];
                            
                            foreach ($all_capabilities as $capability): 
                                $capability_enabled = in_array($capability, $enabled_capabilities);
                                $capability_name = ucfirst(str_replace('_', ' ', $capability));
                            ?>
                                <tr>
                                    <th scope="row"><?php echo esc_html($capability_name); ?></th>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" 
                                                   name="vortex_<?php echo esc_attr($agent_id); ?>_capabilities[<?php echo esc_attr($capability); ?>]" 
                                                   value="1" 
                                                   <?php checked($capability_enabled, true); ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td class="capability-description">
                                        <?php 
                                        // Display capability descriptions
                                        $descriptions = array(
                                            'image_generation' => __('Create images from text descriptions', 'vortex-ai-marketplace'),
                                            'image_transformation' => __('Transform and modify existing images', 'vortex-ai-marketplace'),
                                            'style_transfer' => __('Apply artistic styles to images', 'vortex-ai-marketplace'),
                                            'nft_creation' => __('Generate NFT-ready artwork', 'vortex-ai-marketplace'),
                                            'art_discovery' => __('Find and recommend artwork', 'vortex-ai-marketplace'),
                                            'style_analysis' => __('Analyze artistic styles', 'vortex-ai-marketplace'),
                                            'art_recommendations' => __('Provide art recommendations', 'vortex-ai-marketplace'),
                                            'artist_discovery' => __('Find and suggest artists', 'vortex-ai-marketplace'),
                                            'market_analysis' => __('Analyze marketplace trends', 'vortex-ai-marketplace'),
                                            'trend_prediction' => __('Predict future market trends', 'vortex-ai-marketplace'),
                                            'price_optimization' => __('Suggest optimal pricing', 'vortex-ai-marketplace'),
                                            'audience_analysis' => __('Analyze audience demographics', 'vortex-ai-marketplace'),
                                            'agent_coordination' => __('Coordinate between AI agents', 'vortex-ai-marketplace'),
                                            'contextual_understanding' => __('Understand user context', 'vortex-ai-marketplace'),
                                            'learning_adaptation' => __('Learn and adapt from interactions', 'vortex-ai-marketplace'),
                                            'self_improvement' => __('Self-improve over time', 'vortex-ai-marketplace'),
                                        );
                                        echo isset($descriptions[$capability]) ? esc_html($descriptions[$capability]) : '';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endforeach; ?>
                
                <?php submit_button(__('Save Capabilities Settings', 'vortex-ai-marketplace')); ?>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.thorius-tab').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href');
        
        // Update active tab
        $('.thorius-tab').removeClass('active');
        $(this).addClass('active');
        
        // Show target content
        $('.thorius-tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Toggle agent status
    $('.toggle-agent-status').on('click', function() {
        var agent = $(this).data('agent');
        var action = $(this).data('status'); // 'enable' or 'disable'
        var $button = $(this);
        
        $button.prop('disabled', true);
        
        // AJAX call to toggle agent status
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_toggle_agent_status',
                agent: agent,
                status: action,
                nonce: thorius_admin_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload(); // Reload to show updated status
                } else {
                    alert('<?php _e('Error:', 'vortex-ai-marketplace'); ?> ' + response.data.message);
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                alert('<?php _e('Server error while updating agent status', 'vortex-ai-marketplace'); ?>');
                $button.prop('disabled', false);
            }
        });
    });
    
    // Load performance metrics
    function loadPerformanceMetrics() {
        var period = $('#performance-period').val();
        
        $('#thorius-performance-metrics').html('<div class="loading-spinner"></div>');
        
        // AJAX call to get performance metrics
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_get_agent_performance',
                period: period,
                nonce: thorius_admin_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#thorius-performance-metrics').html(response.data.html);
                    initCharts(); // Initialize charts if needed
                } else {
                    $('#thorius-performance-metrics').html('<div class="error-message">' + response.data.message + '</div>');
                }
            },
            error: function() {
                $('#thorius-performance-metrics').html('<div class="error-message"><?php _e('Server error while loading performance metrics', 'vortex-ai-marketplace'); ?></div>');
            }
        });
    }
    
    // Initialize performance tab when selected
    $('.thorius-tab[href="#agent-performance"]').on('click', function() {
        if ($('#thorius-performance-metrics').is(':empty')) {
            loadPerformanceMetrics();
        }
    });
    
    // Refresh metrics button
    $('#refresh-metrics').on('click', function() {
        loadPerformanceMetrics();
    });
    
    // Period selector change
    $('#performance-period').on('change', function() {
        loadPerformanceMetrics();
    });
});
</script> 