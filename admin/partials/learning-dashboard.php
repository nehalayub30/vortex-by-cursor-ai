<?php
/**
 * Thorius Learning Dashboard
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

// Mock data for learning system status if Thorius or learning system is not available
$status = array(
    'total_interactions' => 0,
    'total_feedback' => 0,
    'adaptations' => 0,
    'agents' => array(
        'cloe' => array(
            'metrics' => array(
                'accuracy' => 85.2,
                'response_quality' => 78.9,
                'learning_rate' => 0.03,
                'improvements' => array(
                    'accuracy' => 2.4,
                    'response_quality' => 1.8,
                    'learning_rate' => 0.005
                )
            ),
            'last_adaptation' => date('Y-m-d H:i:s', strtotime('-3 days'))
        ),
        'huraii' => array(
            'metrics' => array(
                'image_quality' => 82.7,
                'prompt_adherence' => 89.3,
                'style_consistency' => 76.2,
                'improvements' => array(
                    'image_quality' => 3.5,
                    'prompt_adherence' => 2.1,
                    'style_consistency' => 4.2
                )
            ),
            'last_adaptation' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ),
        'strategist' => array(
            'metrics' => array(
                'market_insight' => 81.5,
                'trend_prediction' => 75.8,
                'recommendation_relevance' => 83.7,
                'improvements' => array(
                    'market_insight' => 1.9,
                    'trend_prediction' => 2.8,
                    'recommendation_relevance' => 3.2
                )
            ),
            'last_adaptation' => date('Y-m-d H:i:s', strtotime('-7 days'))
        ),
        'thorius' => array(
            'metrics' => array(
                'blockchain_insight' => 88.2,
                'transaction_optimization' => 84.5,
                'security_assessment' => 91.7,
                'improvements' => array(
                    'blockchain_insight' => 2.7,
                    'transaction_optimization' => 3.9,
                    'security_assessment' => 1.5
                )
            ),
            'last_adaptation' => date('Y-m-d H:i:s', strtotime('-2 days'))
        )
    )
);

// Use real data if available
if ($thorius && method_exists($thorius, 'get_learning_system_status')) {
    $real_status = $thorius->get_learning_system_status();
    if (!empty($real_status)) {
        $status = $real_status;
    }
}

// Get default adaptation thresholds
$thresholds = array(
    'interaction_count' => 100,
    'feedback_quality' => 0.7,
    'confidence_improvement' => 0.15,
    'consistency_threshold' => 0.8
);

if ($thorius && method_exists($thorius, 'get_default_adaptation_thresholds')) {
    $real_thresholds = $thorius->get_default_adaptation_thresholds();
    if (!empty($real_thresholds)) {
        $thresholds = $real_thresholds;
    }
}

// Mock recent adaptations
$recent_adaptations = array(
    array(
        'agent' => 'cloe',
        'type' => 'recommendation_model',
        'date' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'training_size' => 1254,
        'status' => 'completed',
        'impact' => 0.045
    ),
    array(
        'agent' => 'huraii',
        'type' => 'image_quality',
        'date' => date('Y-m-d H:i:s', strtotime('-5 days')),
        'training_size' => 867,
        'status' => 'completed',
        'impact' => 0.062
    ),
    array(
        'agent' => 'strategist',
        'type' => 'market_prediction',
        'date' => date('Y-m-d H:i:s', strtotime('-7 days')),
        'training_size' => 1032,
        'status' => 'completed',
        'impact' => 0.038
    ),
    array(
        'agent' => 'thorius',
        'type' => 'blockchain_optimization',
        'date' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'training_size' => 542,
        'status' => 'completed',
        'impact' => 0.055
    )
);
?>

<div class="wrap thorius-learning-dashboard">
    <h1><?php _e('Thorius Deep Learning Status', 'vortex-ai-marketplace'); ?></h1>
    
    <!-- Overview metrics -->
    <div class="thorius-stats-cards">
        <div class="thorius-stat-card">
            <div class="thorius-stat-header"><?php _e('Total Interactions', 'vortex-ai-marketplace'); ?></div>
            <div class="thorius-stat-value"><?php echo number_format($status['total_interactions']); ?></div>
        </div>
        
        <div class="thorius-stat-card">
            <div class="thorius-stat-header"><?php _e('User Feedback', 'vortex-ai-marketplace'); ?></div>
            <div class="thorius-stat-value"><?php echo number_format($status['total_feedback']); ?></div>
        </div>
        
        <div class="thorius-stat-card">
            <div class="thorius-stat-header"><?php _e('Neural Adaptations', 'vortex-ai-marketplace'); ?></div>
            <div class="thorius-stat-value"><?php echo number_format($status['adaptations']); ?></div>
        </div>
    </div>

    <!-- Agent performance metrics -->
    <h2><?php _e('AI Agent Learning Progress', 'vortex-ai-marketplace'); ?></h2>
    
    <div class="thorius-agent-metrics">
        <?php foreach ($status['agents'] as $agent => $data): ?>
            <div class="thorius-agent-card">
                <div class="thorius-agent-header"><?php echo strtoupper($agent); ?></div>
                
                <div class="thorius-agent-metrics-grid">
                    <?php foreach ($data['metrics'] as $metric => $value): ?>
                        <?php if ($metric === 'improvements') continue; ?>
                        
                        <div class="thorius-agent-metric">
                            <div class="thorius-metric-name"><?php echo ucfirst(str_replace('_', ' ', $metric)); ?></div>
                            <div class="thorius-metric-value"><?php echo number_format($value, 2); ?></div>
                            
                            <?php if (isset($data['metrics']['improvements'][$metric])): ?>
                                <?php
                                $improvement = $data['metrics']['improvements'][$metric];
                                $class = $improvement > 0 ? 'positive' : ($improvement < 0 ? 'negative' : 'neutral');
                                ?>
                                <div class="thorius-metric-trend <?php echo $class; ?>"><?php echo sprintf('%+.2f', $improvement); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (isset($data['last_adaptation'])): ?>
                    <div class="thorius-agent-last-adaptation">
                        <strong><?php _e('Last Adaptation:', 'vortex-ai-marketplace'); ?></strong>
                        <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($data['last_adaptation'])); ?>
                    </div>
                <?php endif; ?>
                
                <div class="thorius-agent-actions">
                    <button class="button thorius-trigger-adaptation" data-agent="<?php echo esc_attr($agent); ?>"><?php _e('Trigger Adaptation', 'vortex-ai-marketplace'); ?></button>
                    <button class="button thorius-reset-learning" data-agent="<?php echo esc_attr($agent); ?>"><?php _e('Reset Learning', 'vortex-ai-marketplace'); ?></button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent adaptations -->
    <h2><?php _e('Recent Neural Adaptations', 'vortex-ai-marketplace'); ?></h2>
    
    <?php if (!empty($recent_adaptations)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Agent', 'vortex-ai-marketplace'); ?></th>
                    <th><?php _e('Type', 'vortex-ai-marketplace'); ?></th>
                    <th><?php _e('Date', 'vortex-ai-marketplace'); ?></th>
                    <th><?php _e('Training Data', 'vortex-ai-marketplace'); ?></th>
                    <th><?php _e('Status', 'vortex-ai-marketplace'); ?></th>
                    <th><?php _e('Impact', 'vortex-ai-marketplace'); ?></th>
                </tr>
            </thead>
            
            <tbody>
                <?php foreach ($recent_adaptations as $adaptation): ?>
                    <tr>
                        <td><?php echo strtoupper($adaptation['agent']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $adaptation['type'])); ?></td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($adaptation['date'])); ?></td>
                        <td><?php echo number_format($adaptation['training_size']); ?> examples</td>
                        
                        <?php
                        $status_class = $adaptation['status'] === 'completed' ? 'success' : ($adaptation['status'] === 'failed' ? 'error' : 'pending');
                        ?>
                        <td><span class="thorius-status-badge <?php echo $status_class; ?>"><?php echo ucfirst($adaptation['status']); ?></span></td>
                        
                        <?php
                        $impact = isset($adaptation['impact']) ? sprintf('%+.2f%%', $adaptation['impact'] * 100) : 'N/A';
                        $impact_class = isset($adaptation['impact']) ? ($adaptation['impact'] > 0 ? 'positive' : ($adaptation['impact'] < 0 ? 'negative' : 'neutral')) : '';
                        ?>
                        <td class="<?php echo $impact_class; ?>"><?php echo $impact; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p><?php _e('No adaptations have been performed yet.', 'vortex-ai-marketplace'); ?></p>
    <?php endif; ?>

    <!-- Global learning settings -->
    <h2><?php _e('Adaptation Settings', 'vortex-ai-marketplace'); ?></h2>
    
    <form id="thorius-learning-settings-form" method="post" action="options.php">
        <?php settings_fields('thorius_learning_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><label for="interaction_threshold"><?php _e('Minimum Interactions', 'vortex-ai-marketplace'); ?></label></th>
                <td>
                    <input type="number" id="interaction_threshold" name="thorius_adaptation_thresholds[interaction_count]" value="<?php echo esc_attr($thresholds['interaction_count']); ?>" min="10" max="1000" step="10">
                    <p class="description"><?php _e('Minimum number of interactions before adaptation is considered', 'vortex-ai-marketplace'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="feedback_quality"><?php _e('Feedback Quality Threshold', 'vortex-ai-marketplace'); ?></label></th>
                <td>
                    <input type="number" id="feedback_quality" name="thorius_adaptation_thresholds[feedback_quality]" value="<?php echo esc_attr($thresholds['feedback_quality']); ?>" min="0.5" max="0.95" step="0.05">
                    <p class="description"><?php _e('Minimum positive feedback ratio to prevent adaptation', 'vortex-ai-marketplace'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="confidence_improvement"><?php _e('Confidence Improvement', 'vortex-ai-marketplace'); ?></label></th>
                <td>
                    <input type="number" id="confidence_improvement" name="thorius_adaptation_thresholds[confidence_improvement]" value="<?php echo esc_attr($thresholds['confidence_improvement']); ?>" min="0.05" max="0.5" step="0.05">
                    <p class="description"><?php _e('Minimum confidence improvement required for adaptation to be considered successful', 'vortex-ai-marketplace'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="consistency_threshold"><?php _e('Consistency Threshold', 'vortex-ai-marketplace'); ?></label></th>
                <td>
                    <input type="number" id="consistency_threshold" name="thorius_adaptation_thresholds[consistency_threshold]" value="<?php echo esc_attr($thresholds['consistency_threshold']); ?>" min="0.5" max="0.95" step="0.05">
                    <p class="description"><?php _e('Minimum consistency score required across similar queries', 'vortex-ai-marketplace'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(__('Save Settings', 'vortex-ai-marketplace')); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Trigger adaptation button
    $('.thorius-trigger-adaptation').on('click', function() {
        var agent = $(this).data('agent');
        var $button = $(this);
        
        $button.prop('disabled', true).text('<?php _e('Processing...', 'vortex-ai-marketplace'); ?>');
        
        // AJAX call to trigger adaptation
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_thorius_trigger_adaptation',
                agent: agent,
                nonce: thorius_admin_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Adaptation triggered successfully for', 'vortex-ai-marketplace'); ?> ' + agent.toUpperCase());
                    location.reload();
                } else {
                    alert('<?php _e('Error triggering adaptation:', 'vortex-ai-marketplace'); ?> ' + response.data.message);
                    $button.prop('disabled', false).text('<?php _e('Trigger Adaptation', 'vortex-ai-marketplace'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Server error while triggering adaptation', 'vortex-ai-marketplace'); ?>');
                $button.prop('disabled', false).text('<?php _e('Trigger Adaptation', 'vortex-ai-marketplace'); ?>');
            }
        });
    });
    
    // Reset learning button
    $('.thorius-reset-learning').on('click', function() {
        var agent = $(this).data('agent');
        
        if (!confirm('<?php _e('Are you sure you want to reset learning for', 'vortex-ai-marketplace'); ?> ' + agent.toUpperCase() + '? <?php _e('This will delete all learned improvements.', 'vortex-ai-marketplace'); ?>')) {
            return;
        }
        
        var $button = $(this);
        $button.prop('disabled', true).text('<?php _e('Processing...', 'vortex-ai-marketplace'); ?>');
        
        // AJAX call to reset learning
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_thorius_reset_learning',
                agent: agent,
                nonce: thorius_admin_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Learning reset successfully for', 'vortex-ai-marketplace'); ?> ' + agent.toUpperCase());
                    location.reload();
                } else {
                    alert('<?php _e('Error resetting learning:', 'vortex-ai-marketplace'); ?> ' + response.data.message);
                    $button.prop('disabled', false).text('<?php _e('Reset Learning', 'vortex-ai-marketplace'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Server error while resetting learning', 'vortex-ai-marketplace'); ?>');
                $button.prop('disabled', false).text('<?php _e('Reset Learning', 'vortex-ai-marketplace'); ?>');
            }
        });
    });
});
</script> 