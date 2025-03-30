<?php
/**
 * VORTEX AI Initializer
 * 
 * Handles initialization and continuous learning for all AI agents
 *
 * @package   VORTEX_Marketplace
 * @author    VORTEX Development Team
 * @license   GPL-2.0+
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class VORTEX_AI_Initializer {
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * AI agent classes
     */
    private $ai_agents = array(
        'huraii' => 'VORTEX_HURAII',
        'cloe' => 'VORTEX_CLOE',
        'business_strategist' => 'VORTEX_Business_Strategist',
        'thorius' => 'VORTEX_Thorius'
    );
    
    /**
     * AI learning parameters
     */
    private $learning_params = array(
        'learning_rate' => 0.001,
        'context_window' => 1000,
        'deep_learning' => true,
        'continuous_learning' => true,
        'cross_learning' => true
    );
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize AI agents on plugin load
        add_action('plugins_loaded', array($this, 'initialize_ai_agents'), 20);
        
        // Schedule regular learning cycles
        add_action('admin_init', array($this, 'schedule_learning_cycles'));
        
        // Register AJAX handlers
        add_action('wp_ajax_vortex_toggle_ai_learning', array($this, 'ajax_toggle_ai_learning'));
        
        // Register hooks for each learning cycle
        add_action('vortex_huraii_learning_cycle', array($this, 'run_huraii_learning_cycle'));
        add_action('vortex_cloe_learning_cycle', array($this, 'run_cloe_learning_cycle'));
        add_action('vortex_business_strategist_learning_cycle', array($this, 'run_business_strategist_learning_cycle'));
        add_action('vortex_thorius_learning_cycle', array($this, 'run_thorius_learning_cycle'));
        
        // Register cross-learning hook
        add_action('vortex_cross_learning_cycle', array($this, 'run_cross_learning_cycle'));
        
        // Register hooks for dashboard display
        add_action('vortex_admin_dashboard_widgets', array($this, 'add_ai_learning_widget'));
    }
    
    /**
     * Return an instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
    /**
     * Initialize all AI agents
     */
    public function initialize_ai_agents() {
        // Ensure agent classes are loaded
        foreach ($this->ai_agents as $agent_id => $class_name) {
            if (class_exists($class_name)) {
                // Get agent instance
                call_user_func(array($class_name, 'get_instance'));
                
                // Ensure deep learning is enabled
                $this->ensure_agent_deep_learning($agent_id);
            }
        }
    }
    
    /**
     * Ensure agent deep learning is enabled
     */
    private function ensure_agent_deep_learning($agent_id) {
        $option_name = "vortex_{$agent_id}_settings";
        $settings = get_option($option_name, array());
        
        $updated = false;
        
        // Check deep learning settings
        if (!isset($settings['deep_learning_enabled']) || $settings['deep_learning_enabled'] !== true) {
            $settings['deep_learning_enabled'] = true;
            $updated = true;
        }
        
        // Check learning rate
        if (!isset($settings['learning_rate']) || $settings['learning_rate'] != $this->learning_params['learning_rate']) {
            $settings['learning_rate'] = $this->learning_params['learning_rate'];
            $updated = true;
        }
        
        // Check context window
        if (!isset($settings['context_window']) || $settings['context_window'] != $this->learning_params['context_window']) {
            $settings['context_window'] = $this->learning_params['context_window'];
            $updated = true;
        }
        
        // Check continuous learning
        if (!isset($settings['continuous_learning']) || $settings['continuous_learning'] !== true) {
            $settings['continuous_learning'] = true;
            $updated = true;
        }
        
        // Check cross learning
        if (!isset($settings['cross_learning']) || $settings['cross_learning'] !== true) {
            $settings['cross_learning'] = true;
            $updated = true;
        }
        
        // Update settings if needed
        if ($updated) {
            update_option($option_name, $settings);
            
            // Log this change
            $this->log_agent_update("Ensured deep learning for $agent_id agent");
        }
    }
    
    /**
     * Schedule learning cycles
     */
    public function schedule_learning_cycles() {
        // Schedule individual learning cycles
        foreach ($this->ai_agents as $agent_id => $class_name) {
            $hook_name = "vortex_{$agent_id}_learning_cycle";
            
            if (!wp_next_scheduled($hook_name)) {
                // Stagger times to avoid server load spikes
                $offset = array_search($agent_id, array_keys($this->ai_agents)) * 900; // 15 min intervals
                wp_schedule_event(time() + $offset, 'daily', $hook_name);
            }
        }
        
        // Schedule cross-learning cycle
        if (!wp_next_scheduled('vortex_cross_learning_cycle')) {
            wp_schedule_event(time() + 43200, 'daily', 'vortex_cross_learning_cycle'); // 12 hours offset
        }
    }
    
    /**
     * Run HURAII learning cycle
     */
    public function run_huraii_learning_cycle() {
        if (class_exists('VORTEX_HURAII')) {
            $huraii = VORTEX_HURAII::get_instance();
            
            if (method_exists($huraii, 'train_deep_learning_model')) {
                $huraii->train_deep_learning_model();
                $this->log_learning_cycle('huraii');
            }
        }
    }
    
    /**
     * Run CLOE learning cycle
     */
    public function run_cloe_learning_cycle() {
        if (class_exists('VORTEX_CLOE')) {
            $cloe = VORTEX_CLOE::get_instance();
            
            if (method_exists($cloe, 'train_deep_learning_model')) {
                $cloe->train_deep_learning_model();
                $this->log_learning_cycle('cloe');
            }
        }
    }
    
    /**
     * Run Business Strategist learning cycle
     */
    public function run_business_strategist_learning_cycle() {
        if (class_exists('VORTEX_Business_Strategist')) {
            $bs = VORTEX_Business_Strategist::get_instance();
            
            if (method_exists($bs, 'train_deep_learning_model')) {
                $bs->train_deep_learning_model();
                $this->log_learning_cycle('business_strategist');
            }
        }
    }
    
    /**
     * Run Thorius learning cycle
     */
    public function run_thorius_learning_cycle() {
        if (class_exists('VORTEX_Thorius')) {
            $thorius = VORTEX_Thorius::get_instance();
            
            if (method_exists($thorius, 'train_deep_learning_model')) {
                $thorius->train_deep_learning_model();
                $this->log_learning_cycle('thorius');
            }
        }
    }
    
    /**
     * Run cross-learning cycle
     */
    public function run_cross_learning_cycle() {
        global $wpdb;
        
        // Get recent AI insights from each agent
        $insights = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vortex_ai_insights
             WHERE confidence_score >= 0.7
             AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY confidence_score DESC
             LIMIT 50"
        );
        
        if (empty($insights)) {
            return;
        }
        
        // Prepare cross-learning data
        $cross_learning_data = array();
        foreach ($insights as $insight) {
            $cross_learning_data[] = array(
                'agent' => $insight->agent,
                'insight_type' => $insight->insight_type,
                'content' => $insight->content,
                'confidence_score' => $insight->confidence_score,
                'created_at' => $insight->created_at
            );
        }
        
        // Share insights with each agent
        foreach ($this->ai_agents as $agent_id => $class_name) {
            if (class_exists($class_name)) {
                $agent = call_user_func(array($class_name, 'get_instance'));
                
                if (method_exists($agent, 'process_cross_learning_data')) {
                    $agent->process_cross_learning_data($cross_learning_data);
                }
            }
        }
        
        // Log cross-learning event
        $this->log_learning_cycle('cross_learning', count($insights));
    }
    
    /**
     * Log learning cycle
     */
    private function log_learning_cycle($agent_id, $items = 0) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_system_logs',
            array(
                'log_type' => 'ai_learning',
                'message' => "Learning cycle completed for {$agent_id}" . ($items > 0 ? " with {$items} items" : ""),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
        
        // Update last learning timestamp
        update_option("vortex_{$agent_id}_last_learning", current_time('mysql'));
    }
    
    /**
     * Log agent update
     */
    private function log_agent_update($message) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_system_logs',
            array(
                'log_type' => 'ai_update',
                'message' => $message,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
    }
    
    /**
     * AJAX handler to toggle AI learning
     */
    public function ajax_toggle_ai_learning() {
        // Security check
        check_ajax_referer('vortex_admin_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }
        
        $agent_id = isset($_POST['agent_id']) ? sanitize_text_field($_POST['agent_id']) : '';
        $enable = isset($_POST['enable']) ? (bool)$_POST['enable'] : true;
        
        if (empty($agent_id) || !array_key_exists($agent_id, $this->ai_agents)) {
            wp_send_json_error(array('message' => 'Invalid agent ID'));
            return;
        }
        
        $option_name = "vortex_{$agent_id}_settings";
        $settings = get_option($option_name, array());
        
        $settings['deep_learning_enabled'] = $enable;
        $settings['continuous_learning'] = $enable;
        
        update_option($option_name, $settings);
        
        // Log this change
        $action = $enable ? 'enabled' : 'disabled';
        $this->log_agent_update("{$action} learning for {$agent_id} agent");
        
        wp_send_json_success(array(
            'message' => "Learning {$action} for {$agent_id}",
            'agent_id' => $agent_id,
            'enabled' => $enable
        ));
    }
    
    /**
     * Add AI learning widget to dashboard
     */
    public function add_ai_learning_widget() {
        ?>
        <div class="vortex-admin-widget vortex-ai-learning-widget">
            <h3 class="widget-title">
                <i class="dashicons dashicons-chart-line"></i> 
                AI Deep Learning Status
            </h3>
            
            <div class="widget-content">
                <div class="ai-agents-status">
                    <?php foreach ($this->ai_agents as $agent_id => $class_name): ?>
                        <?php 
                        $settings = get_option("vortex_{$agent_id}_settings", array());
                        $learning_enabled = isset($settings['deep_learning_enabled']) ? $settings['deep_learning_enabled'] : false;
                        $last_learning = get_option("vortex_{$agent_id}_last_learning", '');
                        $agent_class = str_replace('_', '-', strtolower($agent_id));
                        $agent_name = str_replace('VORTEX_', '', $class_name);
                        ?>
                        <div class="ai-agent-item">
                            <div class="ai-agent-icon agent-<?php echo esc_attr($agent_class); ?>"></div>
                            <div class="ai-agent-info">
                                <h4 class="agent-name"><?php echo esc_html($agent_name); ?></h4>
                                <div class="agent-status">
                                    <span class="status-label">Learning:</span>
                                    <span class="status-value <?php echo $learning_enabled ? 'enabled' : 'disabled'; ?>">
                                        <?php echo $learning_enabled ? 'Enabled' : 'Disabled'; ?>
                                    </span>
                                </div>
                                <?php if (!empty($last_learning)): ?>
                                <div class="agent-last-learning">
                                    <span class="status-label">Last training:</span>
                                    <span class="status-time">
                                        <?php echo human_time_diff(strtotime($last_learning), current_time('timestamp')) . ' ago'; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="ai-agent-actions">
                                <button class="toggle-learning-button button <?php echo $learning_enabled ? 'button-primary' : ''; ?>"
                                        data-agent="<?php echo esc_attr($agent_id); ?>"
                                        data-action="<?php echo $learning_enabled ? 'disable' : 'enable'; ?>">
                                    <?php echo $learning_enabled ? 'Disable Learning' : 'Enable Learning'; ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="ai-global-actions">
                    <button id="enable-all-learning" class="button button-primary">
                        Enable All Learning
                    </button>
                    <button id="run-learning-cycle" class="button">
                        Run Learning Cycle Now
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle individual agent learning
            $('.toggle-learning-button').on('click', function() {
                var $button = $(this);
                var agent = $button.data('agent');
                var action = $button.data('action');
                var enable = (action === 'enable');
                
                $button.prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_toggle_ai_learning',
                        nonce: '<?php echo wp_create_nonce('vortex_admin_actions'); ?>',
                        agent_id: agent,
                        enable: enable
                    },
                    success: function(response) {
                        if (response.success) {
                            var $status = $button.closest('.ai-agent-item').find('.status-value');
                            
                            if (enable) {
                                $button.data('action', 'disable').text('Disable Learning').addClass('button-primary');
                                $status.removeClass('disabled').addClass('enabled').text('Enabled');
                            } else {
                                $button.data('action', 'enable').text('Enable Learning').removeClass('button-primary');
                                $status.removeClass('enabled').addClass('disabled').text('Disabled');
                            }
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                        
                        $button.prop('disabled', false);
                    },
                    error: function() {
                        alert('Network error. Please try again.');
                        $button.prop('disabled', false);
                    }
                });
            });
            
            // Enable all learning
            $('#enable-all-learning').on('click', function() {
                var $button = $(this);
                $button.prop('disabled', true);
                
                var enablePromises = [];
                
                $('.toggle-learning-button').each(function() {
                    var $agentButton = $(this);
                    var agent = $agentButton.data('agent');
                    var action = $agentButton.data('action');
                    
                    // Only enable agents that are currently disabled
                    if (action === 'enable') {
                        $agentButton.prop('disabled', true);
                        
                        var promise = $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'vortex_toggle_ai_learning',
                                nonce: '<?php echo wp_create_nonce('vortex_admin_actions'); ?>',
                                agent_id: agent,
                                enable: true
                            }
                        });
                        
                        enablePromises.push(promise);
                    }
                });
                
                $.when.apply($, enablePromises).then(function() {
                    // All requests completed
                    location.reload();
                }).fail(function() {
                    alert('Error enabling learning for some agents.');
                    $button.prop('disabled', false);
                });
            });
            
            // Run learning cycle
            $('#run-learning-cycle').on('click', function() {
                var $button = $(this);
                $button.prop('disabled', true).text('Running...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_run_learning_cycle',
                        nonce: '<?php echo wp_create_nonce('vortex_admin_actions'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $button.text('Learning Cycle Started');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            alert('Error: ' + response.data.message);
                            $button.prop('disabled', false).text('Run Learning Cycle Now');
                        }
                    },
                    error: function() {
                        alert('Network error. Please try again.');
                        $button.prop('disabled', false).text('Run Learning Cycle Now');
                    }
                });
            });
        });
        </script>
        <?php
    }
}

// Initialize class
VORTEX_AI_Initializer::get_instance(); 