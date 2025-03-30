<?php
/**
 * VORTEX AI Responsibility Enforcer
 *
 * Ensures that all AI agents continuously maintain deep learning
 * and fulfill their designated responsibilities
 *
 * @package Vortex_Marketplace
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_AI_Responsibility_Enforcer Class
 */
class VORTEX_AI_Responsibility_Enforcer {
    /**
     * The single instance of the class
     * @var VORTEX_AI_Responsibility_Enforcer
     */
    private static $instance = null;
    
    /**
     * Main VORTEX_AI_Responsibility_Enforcer Instance
     *
     * Ensures only one instance is loaded or can be loaded.
     *
     * @return VORTEX_AI_Responsibility_Enforcer
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register heartbeat hooks
        add_action('vortex_system_heartbeat', array($this, 'enforce_responsibilities'), 20);
        
        // Register admin hooks
        add_action('admin_init', array($this, 'register_settings'));
        
        // Register activation hook for immediate enforcement
        add_action('plugins_loaded', array($this, 'immediate_enforcement'), 15);
        
        // Monitor system health
        add_action('admin_notices', array($this, 'display_system_health_notice'));
    }
    
    /**
     * Immediate enforcement on plugin load
     */
    public function immediate_enforcement() {
        if (!get_option('vortex_enforcer_initialized', false)) {
            // Create necessary tables
            $this->create_monitoring_tables();
            
            // Set initial stats
            $this->initialize_agent_stats();
            
            // Mark as initialized
            update_option('vortex_enforcer_initialized', true);
            
            // Log initialization
            error_log('VORTEX AI Responsibility Enforcer: Initialized and monitoring all agent activity');
        }
    }
    
    /**
     * Create monitoring tables
     */
    private function create_monitoring_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Agent performance monitoring table
        $table_name = $wpdb->prefix . 'vortex_agent_performance';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_name varchar(50) NOT NULL,
            examples_processed int(11) NOT NULL DEFAULT 0,
            insights_generated int(11) NOT NULL DEFAULT 0,
            last_training datetime DEFAULT NULL,
            learning_status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY agent_name (agent_name),
            KEY learning_status (learning_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Initialize agent stats
     */
    private function initialize_agent_stats() {
        global $wpdb;
        
        $agent_classes = array(
            'HURAII' => 'VORTEX_HURAII',
            'CLOE' => 'VORTEX_CLOE',
            'Business_Strategist' => 'VORTEX_Business_Strategist',
            'Thorius' => 'VORTEX_Thorius'
        );
        
        $now = current_time('mysql');
        
        foreach ($agent_classes as $agent_name => $class_name) {
            // Check if entry exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_agent_performance WHERE agent_name = %s",
                $agent_name
            ));
            
            if (!$exists) {
                // Create initial entry
                $wpdb->insert(
                    $wpdb->prefix . 'vortex_agent_performance',
                    array(
                        'agent_name' => $agent_name,
                        'examples_processed' => 0,
                        'insights_generated' => 0,
                        'learning_status' => 'initializing',
                        'created_at' => $now,
                        'updated_at' => $now
                    ),
                    array('%s', '%d', '%d', '%s', '%s', '%s')
                );
            }
        }
    }
    
    /**
     * Register admin settings
     */
    public function register_settings() {
        register_setting('vortex_ai_settings', 'vortex_enforcer_strict_mode');
    }
    
    /**
     * Enforce responsibilities
     */
    public function enforce_responsibilities() {
        global $wpdb;
        
        error_log('VORTEX AI Responsibility Enforcer: Checking agent compliance');
        
        $agent_classes = array(
            'HURAII' => 'VORTEX_HURAII',
            'CLOE' => 'VORTEX_CLOE',
            'Business_Strategist' => 'VORTEX_Business_Strategist',
            'Thorius' => 'VORTEX_Thorius'
        );
        
        $strict_mode = get_option('vortex_enforcer_strict_mode', false);
        $now = current_time('mysql');
        $current_time = time();
        
        foreach ($agent_classes as $agent_name => $class_name) {
            if (!class_exists($class_name)) {
                error_log("VORTEX AI Responsibility Enforcer: Agent class $class_name not found");
                continue;
            }
            
            $agent = call_user_func(array($class_name, 'get_instance'));
            
            // Check learning status
            $deep_learning_enabled = method_exists($agent, 'is_deep_learning_enabled') ? 
                $agent->is_deep_learning_enabled() : 
                get_option('vortex_' . strtolower($agent_name) . '_deep_learning', false);
                
            $continuous_learning_enabled = method_exists($agent, 'is_continuous_learning_enabled') ? 
                $agent->is_continuous_learning_enabled() : 
                get_option('vortex_' . strtolower($agent_name) . '_continuous_learning', false);
                
            $cross_learning_enabled = method_exists($agent, 'is_cross_learning_enabled') ? 
                $agent->is_cross_learning_enabled() : 
                get_option('vortex_' . strtolower($agent_name) . '_cross_learning', false);
            
            // Check last training time
            $last_training = get_option('vortex_' . strtolower($agent_name) . '_last_training', 0);
            $last_training_mysql = date('Y-m-d H:i:s', $last_training);
            
            // Determine learning status
            $learning_status = 'active';
            
            if (!$deep_learning_enabled || !$continuous_learning_enabled || !$cross_learning_enabled) {
                $learning_status = 'disabled';
                
                // Re-enable in strict mode
                if ($strict_mode) {
                    if (method_exists($agent, 'enable_deep_learning')) {
                        $agent->enable_deep_learning(true);
                    }
                    
                    if (method_exists($agent, 'enable_continuous_learning')) {
                        $agent->enable_continuous_learning(true);
                    }
                    
                    if (method_exists($agent, 'enable_cross_learning')) {
                        $agent->enable_cross_learning(true);
                    }
                    
                    $learning_status = 'reactivated';
                    
                    // Log reactivation
                    $wpdb->insert(
                        $wpdb->prefix . 'vortex_system_logs',
                        array(
                            'log_type' => 'enforcer_action',
                            'message' => sprintf('AI Agent %s learning systems forcibly reactivated by Enforcer', $agent_name),
                            'created_at' => $now
                        ),
                        array('%s', '%s', '%s')
                    );
                }
            } else if (($current_time - $last_training) > 86400) { // 24 hours
                $learning_status = 'stalled';
                
                // Force training in strict mode or if stalled
                wp_schedule_single_event(time() + mt_rand(60, 300), 'vortex_' . strtolower($agent_name) . '_train_model');
                
                // Log forced training
                $wpdb->insert(
                    $wpdb->prefix . 'vortex_system_logs',
                    array(
                        'log_type' => 'enforcer_action',
                        'message' => sprintf('AI Agent %s training forcibly scheduled by Enforcer due to inactivity', $agent_name),
                        'created_at' => $now
                    ),
                    array('%s', '%s', '%s')
                );
            }
            
            // Update agent status in database
            $wpdb->update(
                $wpdb->prefix . 'vortex_agent_performance',
                array(
                    'learning_status' => $learning_status,
                    'last_training' => $last_training_mysql,
                    'updated_at' => $now
                ),
                array('agent_name' => $agent_name),
                array('%s', '%s', '%s'),
                array('%s')
            );
        }
        
        // Log completion
        error_log('VORTEX AI Responsibility Enforcer: Agent compliance check complete');
    }
    
    /**
     * Display system health notice
     */
    public function display_system_health_notice() {
        // Only show to administrators
        if (!current_user_can('manage_options')) {
            return;
        }
        
        global $wpdb;
        
        // Get agent statuses
        $agents = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vortex_agent_performance 
             WHERE learning_status != 'active'"
        );
        
        if (empty($agents)) {
            return;
        }
        
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>VORTEX AI System Alert</strong></p>';
        echo '<p>The following AI agents require attention:</p>';
        echo '<ul>';
        
        foreach ($agents as $agent) {
            echo '<li>';
            echo '<strong>' . esc_html($agent->agent_name) . '</strong>: ';
            
            switch ($agent->learning_status) {
                case 'disabled':
                    echo 'Learning systems disabled';
                    break;
                case 'stalled':
                    echo 'Training stalled since ' . esc_html($agent->last_training);
                    break;
                case 'initializing':
                    echo 'Still initializing';
                    break;
                case 'reactivated':
                    echo 'Recently reactivated by Enforcer';
                    break;
                default:
                    echo esc_html($agent->learning_status);
            }
            
            echo '</li>';
        }
        
        echo '</ul>';
        echo '<p><a href="' . esc_url(admin_url('admin.php?page=vortex-system-status')) . '" class="button button-primary">View System Status</a></p>';
        echo '</div>';
    }
}

// Initialize the Enforcer
add_action('plugins_loaded', function() {
    VORTEX_AI_Responsibility_Enforcer::get_instance();
}, 12); // After the launch coordinator but before other components 