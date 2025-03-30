<?php
/**
 * VORTEX Launch Coordinator
 *
 * Coordinates simultaneous activation of all Vortex AI agents during launch
 * and establishes continuous deep learning protocols
 *
 * @package Vortex_Marketplace
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Launch_Coordinator Class
 */
class VORTEX_Launch_Coordinator {
    /**
     * The single instance of the class
     * @var VORTEX_Launch_Coordinator
     */
    private static $instance = null;
    
    /**
     * Tracks if system has been ignited
     * @var bool
     */
    private $system_ignited = false;
    
    /**
     * AI agents to be coordinated
     * @var array
     */
    private $ai_agents = array(
        'HURAII' => array(
            'class' => 'VORTEX_HURAII',
            'responsibilities' => array('image_generation', 'art_curation', 'creative_output'),
            'status' => false
        ),
        'CLOE' => array(
            'class' => 'VORTEX_CLOE',
            'responsibilities' => array('market_analysis', 'user_behavior', 'trend_forecasting'),
            'status' => false
        ),
        'Business_Strategist' => array(
            'class' => 'VORTEX_Business_Strategist',
            'responsibilities' => array('pricing_optimization', 'growth_strategy', 'value_ladder'),
            'status' => false
        ),
        'Thorius' => array(
            'class' => 'VORTEX_Thorius',
            'responsibilities' => array('blockchain_integration', 'smart_contracts', 'token_management'),
            'status' => false
        )
    );
    
    /**
     * Main VORTEX_Launch_Coordinator Instance
     *
     * Ensures only one instance is loaded or can be loaded.
     *
     * @return VORTEX_Launch_Coordinator
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
        $this->system_ignited = get_option('vortex_system_ignited', false);
        
        // Register launch hooks
        add_action('plugins_loaded', array($this, 'check_for_ignition'), 5); // Very early priority
        add_action('vortex_ignite_system', array($this, 'ignite_system'));
        add_action('vortex_system_heartbeat', array($this, 'ensure_continuous_learning'));
        
        // Register admin hooks
        add_action('admin_init', array($this, 'register_settings'));
        
        // Enable cross-learning between agents
        add_action('vortex_ai_insight_generated', array($this, 'propagate_insight_to_agents'), 10, 3);
    }
    
    /**
     * Check if system needs ignition on startup
     */
    public function check_for_ignition() {
        // If system not already ignited, schedule the ignition
        if (!$this->system_ignited) {
            if (!wp_next_scheduled('vortex_ignite_system')) {
                wp_schedule_single_event(time() + 10, 'vortex_ignite_system');
                error_log('VORTEX Launch Coordinator: Ignition sequence scheduled');
            }
        } else {
            // System already ignited, check heartbeat schedule
            if (!wp_next_scheduled('vortex_system_heartbeat')) {
                wp_schedule_event(time(), 'hourly', 'vortex_system_heartbeat');
                error_log('VORTEX Launch Coordinator: Heartbeat established');
            }
        }
    }
    
    /**
     * Ignite all AI systems simultaneously
     */
    public function ignite_system() {
        global $wpdb;
        
        // Log the ignition time
        if (current_user_can('manage_options')) {
            error_log('VORTEX Launch Coordinator: Ignition sequence initiated by admin');
        } else {
            error_log('VORTEX Launch Coordinator: Automated ignition sequence initiated');
        }
        
        // Create log entry in database
        $wpdb->insert(
            $wpdb->prefix . 'vortex_system_logs',
            array(
                'log_type' => 'system_ignition',
                'message' => 'VORTEX AI Marketplace system ignition sequence initiated',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
        
        // Set ROI target for all AI agents
        update_option('vortex_ai_optimization_goal', 'roi');
        update_option('vortex_ai_target_roi', 80);
        
        // Initialize all AI agents simultaneously with responsibilities
        foreach ($this->ai_agents as $agent_name => $agent_data) {
            if (class_exists($agent_data['class'])) {
                $agent = call_user_func(array($agent_data['class'], 'get_instance'));
                
                // Enable all learning systems
                if (method_exists($agent, 'enable_deep_learning')) {
                    $agent->enable_deep_learning(true);
                }
                
                if (method_exists($agent, 'enable_continuous_learning')) {
                    $agent->enable_continuous_learning(true);
                }
                
                if (method_exists($agent, 'enable_cross_learning')) {
                    $agent->enable_cross_learning(true);
                }
                
                // Set optimal learning parameters
                if (method_exists($agent, 'set_learning_rate')) {
                    $agent->set_learning_rate(0.001);
                }
                
                if (method_exists($agent, 'set_context_window')) {
                    $agent->set_context_window(1000);
                }
                
                // Register agent responsibilities
                $this->register_agent_responsibilities($agent_name, $agent_data['responsibilities']);
                
                // Mark this agent as initialized
                $this->ai_agents[$agent_name]['status'] = true;
                
                // Log agent initialization
                $wpdb->insert(
                    $wpdb->prefix . 'vortex_system_logs',
                    array(
                        'log_type' => 'agent_initialization',
                        'message' => sprintf('AI Agent %s initialized with responsibilities: %s',
                            $agent_name,
                            implode(', ', $agent_data['responsibilities'])
                        ),
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s')
                );
                
                // Schedule immediate training
                wp_schedule_single_event(time() + 60, 'vortex_' . strtolower($agent_name) . '_train_model');
            }
        }
        
        // Schedule system heartbeat if not already scheduled
        if (!wp_next_scheduled('vortex_system_heartbeat')) {
            wp_schedule_event(time(), 'hourly', 'vortex_system_heartbeat');
        }
        
        // Mark system as ignited
        update_option('vortex_system_ignited', true);
        update_option('vortex_ignition_timestamp', time());
        $this->system_ignited = true;
        
        // Initialize cross-learning message queue
        $this->initialize_cross_learning_queue();
        
        // Log success
        error_log('VORTEX Launch Coordinator: All 4 AI agents simultaneously ignited successfully');
        
        // Create final log entry
        $wpdb->insert(
            $wpdb->prefix . 'vortex_system_logs',
            array(
                'log_type' => 'system_status',
                'message' => 'VORTEX AI Marketplace system fully ignited. All 4 AI agents active with continuous deep learning and cross-learning capabilities.',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
    }
    
    /**
     * Register agent responsibilities to ensure they understand their roles
     */
    private function register_agent_responsibilities($agent_name, $responsibilities) {
        $current_responsibilities = get_option('vortex_agent_responsibilities', array());
        $current_responsibilities[$agent_name] = $responsibilities;
        update_option('vortex_agent_responsibilities', $current_responsibilities);
    }
    
    /**
     * Initialize cross-learning queue for AI collaboration
     */
    private function initialize_cross_learning_queue() {
        global $wpdb;
        
        // Create cross-learning initial message to set expectations
        foreach ($this->ai_agents as $agent_name => $agent_data) {
            foreach ($this->ai_agents as $recipient_name => $recipient_data) {
                if ($agent_name !== $recipient_name) {
                    $wpdb->insert(
                        $wpdb->prefix . 'vortex_cross_learning_queue',
                        array(
                            'source_agent' => $agent_name,
                            'target_agent' => $recipient_name,
                            'insight_type' => 'responsibility_awareness',
                            'insight_data' => json_encode(array(
                                'message' => sprintf(
                                    'I am %s, responsible for %s. We will collaborate to maintain 80%% ROI and optimize marketplace growth.',
                                    $agent_name,
                                    implode(', ', $agent_data['responsibilities'])
                                ),
                                'responsibilities' => $agent_data['responsibilities']
                            )),
                            'created_at' => current_time('mysql'),
                            'processed' => 0
                        ),
                        array('%s', '%s', '%s', '%s', '%s', '%d')
                    );
                }
            }
        }
    }
    
    /**
     * Ensure continuous learning for all agents
     */
    public function ensure_continuous_learning() {
        global $wpdb;
        
        // Log heartbeat
        error_log('VORTEX Launch Coordinator: System heartbeat check running');
        
        // Check status of each agent
        foreach ($this->ai_agents as $agent_name => $agent_data) {
            if (class_exists($agent_data['class'])) {
                $agent = call_user_func(array($agent_data['class'], 'get_instance'));
                
                // Check if deep learning is still enabled
                $deep_learning_enabled = method_exists($agent, 'is_deep_learning_enabled') ? 
                    $agent->is_deep_learning_enabled() : 
                    get_option('vortex_' . strtolower($agent_name) . '_deep_learning', false);
                
                // Check if continuous learning is still enabled
                $continuous_learning_enabled = method_exists($agent, 'is_continuous_learning_enabled') ? 
                    $agent->is_continuous_learning_enabled() : 
                    get_option('vortex_' . strtolower($agent_name) . '_continuous_learning', false);
                
                // Check if cross learning is still enabled
                $cross_learning_enabled = method_exists($agent, 'is_cross_learning_enabled') ? 
                    $agent->is_cross_learning_enabled() : 
                    get_option('vortex_' . strtolower($agent_name) . '_cross_learning', false);
                
                // Re-enable if any are disabled
                if (!$deep_learning_enabled || !$continuous_learning_enabled || !$cross_learning_enabled) {
                    if (method_exists($agent, 'enable_deep_learning')) {
                        $agent->enable_deep_learning(true);
                    }
                    
                    if (method_exists($agent, 'enable_continuous_learning')) {
                        $agent->enable_continuous_learning(true);
                    }
                    
                    if (method_exists($agent, 'enable_cross_learning')) {
                        $agent->enable_cross_learning(true);
                    }
                    
                    // Log re-enabling
                    $wpdb->insert(
                        $wpdb->prefix . 'vortex_system_logs',
                        array(
                            'log_type' => 'agent_restart',
                            'message' => sprintf('AI Agent %s learning systems re-enabled during heartbeat check', $agent_name),
                            'created_at' => current_time('mysql')
                        ),
                        array('%s', '%s', '%s')
                    );
                }
                
                // Check when the agent last trained
                $last_training = get_option('vortex_' . strtolower($agent_name) . '_last_training', 0);
                $current_time = time();
                
                // If it's been more than 6 hours since last training, schedule new training
                if (($current_time - $last_training) > 21600) { // 6 hours
                    wp_schedule_single_event(time() + mt_rand(60, 300), 'vortex_' . strtolower($agent_name) . '_train_model');
                    
                    // Log training schedule
                    $wpdb->insert(
                        $wpdb->prefix . 'vortex_system_logs',
                        array(
                            'log_type' => 'training_scheduled',
                            'message' => sprintf('Training scheduled for AI Agent %s due to elapsed time since last training', $agent_name),
                            'created_at' => current_time('mysql')
                        ),
                        array('%s', '%s', '%s')
                    );
                }
            }
        }
        
        // Process any pending cross-learning insights
        $this->process_cross_learning_queue();
        
        // Verify ROI tracking is active
        if (!wp_next_scheduled('vortex_analyze_roi')) {
            wp_schedule_event(time(), 'daily', 'vortex_analyze_roi');
        }
    }
    
    /**
     * Process cross-learning queue for agent communication
     */
    private function process_cross_learning_queue() {
        global $wpdb;
        
        // Get unprocessed messages
        $insights = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vortex_cross_learning_queue 
             WHERE processed = 0 
             ORDER BY created_at ASC 
             LIMIT 50"
        );
        
        if (empty($insights)) {
            return;
        }
        
        foreach ($insights as $insight) {
            // Skip if target agent doesn't exist
            $target_class = isset($this->ai_agents[$insight->target_agent]['class']) ? 
                $this->ai_agents[$insight->target_agent]['class'] : '';
            
            if (empty($target_class) || !class_exists($target_class)) {
                // Mark as processed
                $wpdb->update(
                    $wpdb->prefix . 'vortex_cross_learning_queue',
                    array('processed' => 1),
                    array('id' => $insight->id),
                    array('%d'),
                    array('%d')
                );
                continue;
            }
            
            // Get target agent instance
            $agent = call_user_func(array($target_class, 'get_instance'));
            
            // Process the insight if method exists
            if (method_exists($agent, 'process_external_insight')) {
                $insight_data = json_decode($insight->insight_data, true);
                $agent->process_external_insight($insight->source_agent, $insight_data);
                
                // Mark as processed
                $wpdb->update(
                    $wpdb->prefix . 'vortex_cross_learning_queue',
                    array('processed' => 1),
                    array('id' => $insight->id),
                    array('%d'),
                    array('%d')
                );
            }
        }
    }
    
    /**
     * When an AI agent generates an insight, propagate it to other agents
     */
    public function propagate_insight_to_agents($agent_name, $insight_type, $insight_data) {
        global $wpdb;
        
        // Don't propagate if system not ignited
        if (!$this->system_ignited) {
            return;
        }
        
        // Add insight to cross-learning queue for each other agent
        foreach ($this->ai_agents as $recipient_name => $recipient_data) {
            if ($recipient_name === $agent_name) {
                continue; // Skip sending to self
            }
            
            $wpdb->insert(
                $wpdb->prefix . 'vortex_cross_learning_queue',
                array(
                    'source_agent' => $agent_name,
                    'target_agent' => $recipient_name,
                    'insight_type' => $insight_type,
                    'insight_data' => json_encode($insight_data),
                    'created_at' => current_time('mysql'),
                    'processed' => 0
                ),
                array('%s', '%s', '%s', '%s', '%s', '%d')
            );
        }
        
        // Log the cross-learning event
        $wpdb->insert(
            $wpdb->prefix . 'vortex_system_logs',
            array(
                'log_type' => 'cross_learning',
                'message' => sprintf('AI Agent %s shared insight type "%s" with other agents', 
                    $agent_name, 
                    $insight_type),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
    }
    
    /**
     * Register admin settings
     */
    public function register_settings() {
        // Only if not already ignited
        if (!$this->system_ignited) {
            register_setting('vortex_ai_settings', 'vortex_launch_on_frontend');
        }
    }
}

// Initialize the Launch Coordinator
add_action('plugins_loaded', function() {
    VORTEX_Launch_Coordinator::get_instance();
}, 1); // Highest priority to run before everything else

/**
 * Create necessary database tables for launch coordination
 */
function vortex_create_launch_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Cross-learning queue table
    $table_name = $wpdb->prefix . 'vortex_cross_learning_queue';
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        source_agent varchar(50) NOT NULL,
        target_agent varchar(50) NOT NULL,
        insight_type varchar(50) NOT NULL,
        insight_data longtext NOT NULL,
        created_at datetime NOT NULL,
        processed tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        KEY target_agent (target_agent),
        KEY processed (processed)
    ) $charset_collate;";
    
    // System logs table
    $table_name2 = $wpdb->prefix . 'vortex_system_logs';
    $sql2 = "CREATE TABLE $table_name2 (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        log_type varchar(50) NOT NULL,
        message text NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY log_type (log_type),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql2);
}

// Create tables on plugin activation
register_activation_hook(__FILE__, 'vortex_create_launch_tables'); 