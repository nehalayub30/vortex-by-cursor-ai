/**
 * Register AI agents
 */
private function register_agents() {
    // CLOE
    if (class_exists('VORTEX_CLOE')) {
        $this->agents['cloe'] = VORTEX_CLOE::get_instance();
    }
    
    // HURAII
    if (class_exists('VORTEX_HURAII')) {
        $this->agents['huraii'] = VORTEX_HURAII::get_instance();
    }
    
    // Business Strategist
    if (class_exists('VORTEX_Business_Strategist')) {
        $this->agents['business'] = VORTEX_Business_Strategist::get_instance();
    }
    
    // Thorius
    if (class_exists('VORTEX_Thorius')) {
        $this->agents['thorius'] = VORTEX_Thorius::get_instance();
    }
    
    // Ensure deep learning is enabled for all agents
    foreach ($this->agents as $agent) {
        if (method_exists($agent, 'enable_deep_learning')) {
            $agent->enable_deep_learning(true);
        }
        if (method_exists($agent, 'enable_continuous_learning')) {
            $agent->enable_continuous_learning(true);
        }
        if (method_exists($agent, 'enable_cross_learning')) {
            $agent->enable_cross_learning(true);
        }
    }
    
    do_action('vortex_ai_agents_registered', $this->agents);
}

/**
 * Schedule learning tasks
 */
private function schedule_learning_tasks() {
    // Hourly agent sync
    if (!wp_next_scheduled('vortex_hourly_agent_sync')) {
        wp_schedule_event(time(), 'hourly', 'vortex_hourly_agent_sync');
    }
    
    // Daily deep learning
    if (!wp_next_scheduled('vortex_daily_deep_learning')) {
        wp_schedule_event(time(), 'daily', 'vortex_daily_deep_learning');
    }
    
    // Weekly comprehensive learning
    if (!wp_next_scheduled('vortex_weekly_comprehensive_learning')) {
        wp_schedule_event(time(), 'weekly', 'vortex_weekly_comprehensive_learning');
    }
    
    // Add hook for the weekly comprehensive learning
    add_action('vortex_weekly_comprehensive_learning', array($this, 'run_comprehensive_learning'));
}

/**
 * Sync agent knowledge
 */
public function sync_agent_knowledge() {
    // Skip if no agents
    if (empty($this->agents)) {
        error_log('No AI agents registered for synchronization');
        return;
    }
    
    // Log start of sync
    error_log('Starting AI agent knowledge synchronization');
    
    try {
        // Create a shared knowledge pool
        $knowledge_pool = array();
        
        // Each agent contributes to the knowledge pool
        foreach ($this->agents as $agent_id => $agent) {
            if (method_exists($agent, 'get_shareable_insights')) {
                $insights = $agent->get_shareable_insights();
                if (!empty($insights)) {
                    $knowledge_pool[$agent_id] = $insights;
                }
            }
        }
        
        // Each agent processes insights from others
        foreach ($this->agents as $agent_id => $agent) {
            if (method_exists($agent, 'process_external_insight')) {
                foreach ($knowledge_pool as $source_id => $insights) {
                    if ($source_id !== $agent_id) {
                        foreach ($insights as $insight) {
                            $agent->process_external_insight($insight, $source_id);
                        }
                    }
                }
            }
        }
        
        // Update sync timestamp
        update_option('vortex_last_agent_sync', time());
        
        // Log successful sync
        error_log('AI agent knowledge synchronization completed successfully');
    } catch (Exception $e) {
        error_log('Error during AI agent knowledge sync: ' . $e->getMessage());
    }
}

/**
 * Run deep learning cycle for all agents
 */
public function run_deep_learning_cycle() {
    // Skip if no agents
    if (empty($this->agents)) {
        error_log('No AI agents registered for deep learning');
        return;
    }
    
    // Log start of deep learning
    error_log('Starting AI agent deep learning cycle');
    
    try {
        // First collect all recent data for learning
        $this->collect_learning_data();
        
        // Train each agent
        foreach ($this->agents as $agent_id => $agent) {
            if (method_exists($agent, 'train_deep_learning_model')) {
                error_log("Training deep learning model for agent: $agent_id");
                $agent->train_deep_learning_model();
            }
        }
        
        // Update learning timestamp
        update_option('vortex_last_deep_learning', time());
        
        // Log successful learning
        error_log('AI agent deep learning cycle completed successfully');
    } catch (Exception $e) {
        error_log('Error during AI agent deep learning: ' . $e->getMessage());
    }
}

/**
 * Run comprehensive learning (performed weekly)
 */
public function run_comprehensive_learning() {
    // Skip if no agents
    if (empty($this->agents)) {
        error_log('No AI agents registered for comprehensive learning');
        return;
    }
    
    // Log start of comprehensive learning
    error_log('Starting AI agent comprehensive learning cycle');
    
    try {
        // Adjust learning parameters to be more thorough
        foreach ($this->agents as $agent_id => $agent) {
            if (method_exists($agent, 'set_learning_rate')) {
                // Lower learning rate for more stability
                $agent->set_learning_rate(0.001);
            }
            
            if (method_exists($agent, 'set_context_window')) {
                // Larger context window for better understanding
                $agent->set_context_window(2000);
            }
        }
        
        // First sync knowledge
        $this->sync_agent_knowledge();
        
        // Collect historical data for deeper learning
        $this->collect_comprehensive_data();
        
        // Train each agent with comprehensive data
        foreach ($this->agents as $agent_id => $agent) {
            if (method_exists($agent, 'train_deep_learning_model')) {
                error_log("Comprehensive training for agent: $agent_id");
                $agent->train_deep_learning_model();
            }
        }
        
        // Reset parameters to normal
        foreach ($this->agents as $agent_id => $agent) {
            if (method_exists($agent, 'set_learning_rate')) {
                $agent->set_learning_rate(0.01);
            }
            
            if (method_exists($agent, 'set_context_window')) {
                $agent->set_context_window(1000);
            }
        }
        
        // Update learning timestamp
        update_option('vortex_last_comprehensive_learning', time());
        
        // Log successful learning
        error_log('AI agent comprehensive learning cycle completed successfully');
    } catch (Exception $e) {
        error_log('Error during AI agent comprehensive learning: ' . $e->getMessage());
    }
}

/**
 * Collect learning data for agents
 */
private function collect_learning_data() {
    global $wpdb;
    
    // Collect recent marketplace activity
    $recent_activity = $wpdb->get_results("
        SELECT * FROM {$wpdb->prefix}vortex_user_activities
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY created_at DESC
        LIMIT 1000
    ");
    
    // Collect recent blockchain activity
    $blockchain_activity = $wpdb->get_results("
        SELECT * FROM {$wpdb->prefix}vortex_tola_transactions
        WHERE transaction_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY transaction_time DESC
        LIMIT 500
    ");
    
    // Store data for agents to access
    update_option('vortex_recent_activity_data', $recent_activity);
    update_option('vortex_recent_blockchain_data', $blockchain_activity);
}

/**
 * Collect comprehensive historical data for deep learning
 */
private function collect_comprehensive_data() {
    global $wpdb;
    
    // Collect wider timeframe of marketplace activity
    $comprehensive_activity = $wpdb->get_results("
        SELECT * FROM {$wpdb->prefix}vortex_user_activities
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY created_at DESC
        LIMIT 5000
    ");
    
    // Collect historical blockchain data
    $blockchain_history = $wpdb->get_results("
        SELECT * FROM {$wpdb->prefix}vortex_tola_transactions
        WHERE transaction_time > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY transaction_time DESC
        LIMIT 2000
    ");
    
    // Collect artwork data
    $artwork_data = $wpdb->get_results("
        SELECT * FROM {$wpdb->prefix}vortex_artworks
        ORDER BY created_at DESC
        LIMIT 1000
    ");
    
    // Store comprehensive data
    update_option('vortex_comprehensive_activity_data', $comprehensive_activity);
    update_option('vortex_comprehensive_blockchain_data', $blockchain_history);
    update_option('vortex_comprehensive_artwork_data', $artwork_data);
}

/**
 * Add admin menu
 */
public function add_admin_menu() {
    add_submenu_page(
        'vortex-marketplace',
        'AI Agents',
        'AI Agents',
        'manage_options',
        'vortex-ai-agents',
        array($this, 'render_admin_page')
    );
}

/**
 * Register REST API routes
 */
public function register_rest_routes() {
    register_rest_route('vortex/v1', '/ai/status', array(
        'methods' => 'GET',
        'callback' => array($this, 'get_ai_status'),
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
    
    register_rest_route('vortex/v1', '/ai/sync', array(
        'methods' => 'POST',
        'callback' => array($this, 'trigger_ai_sync'),
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
    
    register_rest_route('vortex/v1', '/ai/train', array(
        'methods' => 'POST',
        'callback' => array($this, 'trigger_ai_training'),
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
}

/**
 * REST API endpoint for AI status
 */
public function get_ai_status($request) {
    $status = array(
        'agents' => array(),
        'last_sync' => get_option('vortex_last_agent_sync', 0),
        'last_deep_learning' => get_option('vortex_last_deep_learning', 0),
        'last_comprehensive_learning' => get_option('vortex_last_comprehensive_learning', 0)
    );
    
    foreach ($this->agents as $agent_id => $agent) {
        $agent_status = array(
            'id' => $agent_id,
            'deep_learning' => false,
            'continuous_learning' => false,
            'cross_learning' => false
        );
        
        if (method_exists($agent, 'is_deep_learning_enabled')) {
            $agent_status['deep_learning'] = $agent->is_deep_learning_enabled();
        }
        
        if (method_exists($agent, 'is_continuous_learning_enabled')) {
            $agent_status['continuous_learning'] = $agent->is_continuous_learning_enabled();
        }
        
        if (method_exists($agent, 'is_cross_learning_enabled')) {
            $agent_status['cross_learning'] = $agent->is_cross_learning_enabled();
        }
        
        $status['agents'][$agent_id] = $agent_status;
    }
    
    return rest_ensure_response($status);
}

/**
 * REST API endpoint to trigger AI sync
 */
public function trigger_ai_sync($request) {
    $this->sync_agent_knowledge();
    return rest_ensure_response(array(
        'success' => true,
        'message' => 'AI agent sync triggered successfully',
        'timestamp' => time()
    ));
}

/**
 * REST API endpoint to trigger AI training
 */
public function trigger_ai_training($request) {
    $type = $request->get_param('type') ?: 'regular';
    
    if ($type === 'comprehensive') {
        $this->run_comprehensive_learning();
        $message = 'Comprehensive AI training triggered successfully';
    } else {
        $this->run_deep_learning_cycle();
        $message = 'Regular AI training triggered successfully';
    }
    
    return rest_ensure_response(array(
        'success' => true,
        'message' => $message,
        'timestamp' => time()
    ));
}

/**
 * Render admin page
 */
public function render_admin_page() {
    $last_sync = get_option('vortex_last_agent_sync', 0);
    $last_learning = get_option('vortex_last_deep_learning', 0);
    $last_comprehensive = get_option('vortex_last_comprehensive_learning', 0);
    ?>
    <div class="wrap">
        <h1><?php _e('Vortex AI Agents', 'vortex-marketplace'); ?></h1>
        
        <div class="notice notice-info is-dismissible">
            <p><?php _e('This page allows you to manage the AI agents and their learning settings.', 'vortex-marketplace'); ?></p>
        </div>
        
        <div class="card">
            <h2><?php _e('AI Agent Status', 'vortex-marketplace'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Agent', 'vortex-marketplace'); ?></th>
                        <th><?php _e('Deep Learning', 'vortex-marketplace'); ?></th>
                        <th><?php _e('Continuous Learning', 'vortex-marketplace'); ?></th>
                        <th><?php _e('Cross Learning', 'vortex-marketplace'); ?></th>
                        <th><?php _e('Actions', 'vortex-marketplace'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->agents as $agent_id => $agent): ?>
                        <tr>
                            <td><strong><?php echo esc_html(ucfirst($agent_id)); ?></strong></td>
                            <td>
                                <?php if (method_exists($agent, 'is_deep_learning_enabled')): ?>
                                    <?php echo $agent->is_deep_learning_enabled() ? 
                                        '<span class="dashicons dashicons-yes" style="color:green;"></span> Enabled' : 
                                        '<span class="dashicons dashicons-no" style="color:red;"></span> Disabled'; ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-minus"></span> N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (method_exists($agent, 'is_continuous_learning_enabled')): ?>
                                    <?php echo $agent->is_continuous_learning_enabled() ? 
                                        '<span class="dashicons dashicons-yes" style="color:green;"></span> Enabled' : 
                                        '<span class="dashicons dashicons-no" style="color:red;"></span> Disabled'; ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-minus"></span> N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (method_exists($agent, 'is_cross_learning_enabled')): ?>
                                    <?php echo $agent->is_cross_learning_enabled() ? 
                                        '<span class="dashicons dashicons-yes" style="color:green;"></span> Enabled' : 
                                        '<span class="dashicons dashicons-no" style="color:red;"></span> Disabled'; ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-minus"></span> N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="button train-agent" data-agent="<?php echo esc_attr($agent_id); ?>">
                                    <?php _e('Train Now', 'vortex-marketplace'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h2><?php _e('Learning Schedule', 'vortex-marketplace'); ?></h2>
            <p><strong><?php _e('Last Knowledge Sync:', 'vortex-marketplace'); ?></strong> 
                <?php echo $last_sync ? date('F j, Y, g:i a', $last_sync) : __('Never', 'vortex-marketplace'); ?>
            </p>
            <p><strong><?php _e('Last Deep Learning Cycle:', 'vortex-marketplace'); ?></strong> 
                <?php echo $last_learning ? date('F j, Y, g:i a', $last_learning) : __('Never', 'vortex-marketplace'); ?>
            </p>
            <p><strong><?php _e('Last Comprehensive Learning:', 'vortex-marketplace'); ?></strong> 
                <?php echo $last_comprehensive ? date('F j, Y, g:i a', $last_comprehensive) : __('Never', 'vortex-marketplace'); ?>
            </p>
            
            <div class="learning-actions">
                <button class="button button-primary" id="sync-all-agents">
                    <?php _e('Sync Knowledge Now', 'vortex-marketplace'); ?>
                </button>
                <button class="button" id="train-all-agents">
                    <?php _e('Run Deep Learning Now', 'vortex-marketplace'); ?>
                </button>
                <button class="button" id="comprehensive-training">
                    <?php _e('Run Comprehensive Learning', 'vortex-marketplace'); ?>
                </button>
            </div>
        </div>
        
        <div class="card">
            <h2><?php _e('Learning Settings', 'vortex-marketplace'); ?></h2>
            <form method="post" action="options.php">
                <?php settings_fields('vortex_ai_learning_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Deep Learning for All Agents', 'vortex-marketplace'); ?></th>
                        <td>
                            <input type="checkbox" name="vortex_enable_all_deep_learning" value="1" 
                                <?php checked(get_option('vortex_enable_all_deep_learning', '1'), '1'); ?>>
                            <p class="description">
                                <?php _e('When enabled, all AI agents will use deep learning capabilities.', 'vortex-marketplace'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Enable Continuous Learning for All Agents', 'vortex-marketplace'); ?></th>
                        <td>
                            <input type="checkbox" name="vortex_enable_all_continuous_learning" value="1" 
                                <?php checked(get_option('vortex_enable_all_continuous_learning', '1'), '1'); ?>>
                            <p class="description">
                                <?php _e('When enabled, all AI agents will continuously learn from new data.', 'vortex-marketplace'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Enable Cross-Learning Between Agents', 'vortex-marketplace'); ?></th>
                        <td>
                            <input type="checkbox" name="vortex_enable_all_cross_learning" value="1" 
                                <?php checked(get_option('vortex_enable_all_cross_learning', '1'), '1'); ?>>
                            <p class="description">
                                <?php _e('When enabled, AI agents will share insights and learn from each other.', 'vortex-marketplace'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        // Train single agent
        $('.train-agent').on('click', function() {
            var agent = $(this).data('agent');
            var $button = $(this);
            
            $button.prop('disabled', true).text('Training...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_train_agent',
                    agent: agent,
                    nonce: '<?php echo wp_create_nonce('vortex_train_agent'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Agent training initiated successfully.');
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                    $button.prop('disabled', false).text('Train Now');
                },
                error: function() {
                    alert('An error occurred while communicating with the server.');
                    $button.prop('disabled', false).text('Train Now');
                }
            });
        });
        
        // Sync all agents
        $('#sync-all-agents').on('click', function() {
            var $button = $(this);
            
            $button.prop('disabled', true).text('Syncing...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_sync_all_agents',
                    nonce: '<?php echo wp_create_nonce('vortex_sync_all_agents'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Knowledge sync initiated successfully.');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                    $button.prop('disabled', false).text('Sync Knowledge Now');
                },
                error: function() {
                    alert('An error occurred while communicating with the server.');
                    $button.prop('disabled', false).text('Sync Knowledge Now');
                }
            });
        });
        
        // Train all agents
        $('#train-all-agents').on('click', function() {
            var $button = $(this);
            
            $button.prop('disabled', true).text('Training...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_train_all_agents',
                    nonce: '<?php echo wp_create_nonce('vortex_train_all_agents'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Deep learning cycle initiated successfully.');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                    $button.prop('disabled', false).text('Run Deep Learning Now');
                },
                error: function() {
                    alert('An error occurred while communicating with the server.');
                    $button.prop('disabled', false).text('Run Deep Learning Now');
                }
            });
        });
        
        // Comprehensive training
        $('#comprehensive-training').on('click', function() {
            var $button = $(this);
            
            if (!confirm('Comprehensive learning requires significant resources and may take some time. Continue?')) {
                return;
            }
            
            $button.prop('disabled', true).text('Running Comprehensive Learning...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_comprehensive_learning',
                    nonce: '<?php echo wp_create_nonce('vortex_comprehensive_learning'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Comprehensive learning initiated successfully.');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                    $button.prop('disabled', false).text('Run Comprehensive Learning');
                },
                error: function() {
                    alert('An error occurred while communicating with the server.');
                    $button.prop('disabled', false).text('Run Comprehensive Learning');
                }
            });
        });
    });
    </script>
    <?php
}

// Initialize AI Coordinator
add_action('plugins_loaded', function() {
    VORTEX_AI_Coordinator::get_instance();
});

// Register settings
add_action('admin_init', function() {
    register_setting('vortex_ai_learning_settings', 'vortex_enable_all_deep_learning');
    register_setting('vortex_ai_learning_settings', 'vortex_enable_all_continuous_learning');
    register_setting('vortex_ai_learning_settings', 'vortex_enable_all_cross_learning');
});

// AJAX handlers
add_action('wp_ajax_vortex_train_agent', 'vortex_ajax_train_agent');
add_action('wp_ajax_vortex_sync_all_agents', 'vortex_ajax_sync_all_agents');
add_action('wp_ajax_vortex_train_all_agents', 'vortex_ajax_train_all_agents');
add_action('wp_ajax_vortex_comprehensive_learning', 'vortex_ajax_comprehensive_learning');

/**
 * AJAX handler for training a single agent
 */
function vortex_ajax_train_agent() {
    check_ajax_referer('vortex_train_agent', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $agent_id = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : '';
    
    if (empty($agent_id)) {
        wp_send_json_error(array('message' => 'Invalid agent ID'));
        return;
    }
    
    $coordinator = VORTEX_AI_Coordinator::get_instance();
    $agents = $coordinator->get_agents();
    
    if (isset($agents[$agent_id]) && method_exists($agents[$agent_id], 'train_deep_learning_model')) {
        $agents[$agent_id]->train_deep_learning_model();
        wp_send_json_success(array('message' => 'Agent training initiated'));
    } else {
        wp_send_json_error(array('message' => 'Agent not found or does not support training'));
    }
}

/**
 * AJAX handler for syncing all agents
 */
function vortex_ajax_sync_all_agents() {
    check_ajax_referer('vortex_sync_all_agents', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $coordinator = VORTEX_AI_Coordinator::get_instance();
    $coordinator->sync_agent_knowledge();
    
    wp_send_json_success(array('message' => 'Knowledge sync initiated'));
}

/**
 * AJAX handler for training all agents
 */
function vortex_ajax_train_all_agents() {
    check_ajax_referer('vortex_train_all_agents', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $coordinator = VORTEX_AI_Coordinator::get_instance();
    $coordinator->run_deep_learning_cycle();
    
    wp_send_json_success(array('message' => 'Deep learning cycle initiated'));
}

/**
 * AJAX handler for comprehensive learning
 */
function vortex_ajax_comprehensive_learning() {
    check_ajax_referer('vortex_comprehensive_learning', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $coordinator = VORTEX_AI_Coordinator::get_instance();
    $coordinator->run_comprehensive_learning();
    
    wp_send_json_success(array('message' => 'Comprehensive learning initiated'));
} 