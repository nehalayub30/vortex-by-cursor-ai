<?php
/**
 * VORTEX Orchestrator
 *
 * Coordinates all AI agents with continuous deep learning capabilities, cross-agent communication,
 * and model optimization.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Orchestrator Class
 *
 * @since 1.0.0
 */
class VORTEX_Orchestrator {
    /**
     * The single instance of this class
     *
     * @since 1.0.0
     * @access private
     * @var VORTEX_Orchestrator
     */
    private static $instance = null;

    /**
     * HURAII AI agent instance
     *
     * @since 1.0.0
     * @access private
     * @var VORTEX_HURAII
     */
    private $huraii;

    /**
     * CLOE AI agent instance
     *
     * @since 1.0.0
     * @access private
     * @var VORTEX_CLOE
     */
    private $cloe;

    /**
     * Business Strategist AI agent instance
     *
     * @since 1.0.0
     * @access private
     * @var VORTEX_Business_Strategist
     */
    private $business_strategist;

    /**
     * Thorius AI agent instance
     *
     * @since 1.0.0
     * @access private
     * @var VORTEX_Thorius
     */
    private $thorius;

    /**
     * Flag for active learning
     *
     * @since 1.0.0
     * @access private
     * @var bool
     */
    private $learning_active = false;

    /**
     * Last learning time
     *
     * @since 1.0.0
     * @access private
     * @var int
     */
    private $last_learning_time = 0;

    /**
     * Get instance - Singleton pattern
     *
     * @since 1.0.0
     * @return VORTEX_Orchestrator
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->init_agent_references();
        $this->init_learning_state();
        $this->setup_hooks();
    }

    /**
     * Initialize agent references
     *
     * @since 1.0.0
     */
    private function init_agent_references() {
        // Initialize HURAII reference if available
        if (class_exists('VORTEX_HURAII')) {
            $this->huraii = VORTEX_HURAII::get_instance();
        }

        // Initialize CLOE reference if available
        if (class_exists('VORTEX_CLOE')) {
            $this->cloe = VORTEX_CLOE::get_instance();
        }

        // Initialize Business Strategist reference if available
        if (class_exists('VORTEX_Business_Strategist')) {
            $this->business_strategist = VORTEX_Business_Strategist::get_instance();
        }

        // Initialize Thorius reference if available
        if (class_exists('VORTEX_Thorius')) {
            $this->thorius = VORTEX_Thorius::get_instance();
        }
    }

    /**
     * Initialize learning state
     *
     * @since 1.0.0
     */
    private function init_learning_state() {
        $this->learning_active = false;
        $this->last_learning_time = get_option('vortex_last_learning_time', 0);
    }

    /**
     * Setup hooks
     *
     * @since 1.0.0
     */
    private function setup_hooks() {
        // Set up cron jobs for periodic learning
        add_action('init', array($this, 'setup_cron_jobs'));
        
        // Register AJAX actions
        $this->register_ajax_actions();
        
        // Add hooks for the learning cycles
        add_action('vortex_orchestrator_daily_learning', array($this, 'trigger_daily_learning'));
        add_action('vortex_orchestrator_weekly_deep_learning', array($this, 'trigger_weekly_deep_learning'));
        add_action('vortex_incremental_learning', array($this, 'trigger_incremental_learning'));
        
        // Add shortcodes
        add_shortcode('vortex_agent_insights', array($this, 'agent_insights_shortcode'));
        
        // Initialize agent discovery
        add_action('wp_loaded', array($this, 'discover_agents'));
    }

    /**
     * Discover agents in the system
     *
     * @since 1.0.0
     */
    public function discover_agents() {
        $agent_classes = apply_filters('vortex_discoverable_agents', array(
            'huraii' => 'VORTEX_HURAII',
            'cloe' => 'VORTEX_CLOE',
            'business_strategist' => 'VORTEX_Business_Strategist',
            'thorius' => 'VORTEX_Thorius'
        ));
        
        foreach ($agent_classes as $agent_id => $class_name) {
            if (class_exists($class_name)) {
                $method = 'get_instance';
                if (method_exists($class_name, $method)) {
                    $this->{$agent_id} = call_user_func(array($class_name, $method));
                }
            }
        }
        
        do_action('vortex_agents_discovered', $this);
    }
    
    /**
     * Create necessary database tables
     *
     * @since 1.0.0
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Agent logs table
        $table_name = $wpdb->prefix . 'vortex_agent_logs';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_name varchar(50) NOT NULL,
            event_type varchar(50) NOT NULL,
            message text NOT NULL,
            data longtext,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY agent_name (agent_name),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Agent insights table
        $table_name = $wpdb->prefix . 'vortex_agent_insights';
        $sql .= "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_name varchar(50) NOT NULL,
            insight_type varchar(50) NOT NULL,
            insight_data longtext NOT NULL,
            confidence float(4,2) NOT NULL DEFAULT 0.5,
            related_entities longtext,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY agent_name (agent_name),
            KEY insight_type (insight_type),
            KEY confidence (confidence),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Learning metrics history table
        $table_name = $wpdb->prefix . 'vortex_learning_metrics_history';
        $sql .= "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            artworks_analyzed int(11) NOT NULL DEFAULT 0,
            users_analyzed int(11) NOT NULL DEFAULT 0,
            insights_generated int(11) NOT NULL DEFAULT 0,
            learning_duration int(11) NOT NULL DEFAULT 0,
            huraii_health float(4,2) NOT NULL DEFAULT 0.5,
            cloe_health float(4,2) NOT NULL DEFAULT 0.5,
            business_strategist_health float(4,2) NOT NULL DEFAULT 0.5,
            thorius_health float(4,2) NOT NULL DEFAULT 0.5,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Cross-agent connections table
        $table_name = $wpdb->prefix . 'vortex_agent_connections';
        $sql .= "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_agent varchar(50) NOT NULL,
            target_agent varchar(50) NOT NULL,
            connection_type varchar(50) NOT NULL,
            connection_strength float(4,2) NOT NULL DEFAULT 0.5,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY agent_connection (source_agent,target_agent,connection_type),
            KEY source_agent (source_agent),
            KEY target_agent (target_agent),
            KEY connection_type (connection_type),
            KEY connection_strength (connection_strength)
        ) $charset_collate;";
        
        // Use dbDelta for database updates
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Setup cron jobs for learning
     * 
     * @since 1.0.0
     */
    public function setup_cron_jobs() {
        if (!wp_next_scheduled('vortex_orchestrator_daily_learning')) {
            wp_schedule_event(time(), 'daily', 'vortex_orchestrator_daily_learning');
        }
        
        if (!wp_next_scheduled('vortex_orchestrator_weekly_deep_learning')) {
            wp_schedule_event(time(), 'weekly', 'vortex_orchestrator_weekly_deep_learning');
        }
    }
    
    /**
     * Remove cron jobs on deactivation
     * 
     * @since 1.0.0
     */
    public static function remove_cron_jobs() {
        $timestamp = wp_next_scheduled('vortex_orchestrator_daily_learning');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'vortex_orchestrator_daily_learning');
        }
        
        $timestamp = wp_next_scheduled('vortex_orchestrator_weekly_deep_learning');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'vortex_orchestrator_weekly_deep_learning');
        }
    }
    
    /**
     * Trigger learning for a single agent
     * 
     * @since 1.0.0
     * @param string $agent Agent identifier
     * @return array Result of learning process
     */
    public function trigger_single_agent_learning($agent) {
        if ($this->learning_active) {
            return array(
                'success' => false,
                'message' => 'Learning already in progress'
            );
        }
        
        $this->learning_active = true;
        $this->log_event('learning_started', "Learning started for agent: $agent");
        
        $agent_object = null;
        switch ($agent) {
            case 'huraii':
                $agent_object = $this->huraii;
                break;
            case 'cloe':
                $agent_object = $this->cloe;
                break;
            case 'business_strategist':
                $agent_object = $this->business_strategist;
                break;
            case 'thorius':
                $agent_object = $this->thorius;
                break;
            default:
                $this->learning_active = false;
                return array(
                    'success' => false,
                    'message' => 'Invalid agent specified'
                );
        }
        
        // Get data relevant to this agent
        $artworks = $this->get_new_artworks_since_last_learning();
        $user_interactions = $this->get_user_interactions_since_last_learning();
        $market_data = $this->get_market_data_since_last_learning();
        $security_data = $this->get_security_data_since_last_learning();
        
        $start_time = microtime(true);
        
        // Run learning for the specified agent
        $result = $agent_object->learn(array(
            'artworks' => $artworks,
            'interactions' => $user_interactions,
            'market_data' => $market_data,
            'security_data' => $security_data,
            'mode' => 'single'
        ));
        
        $duration = round(microtime(true) - $start_time);
        
        // Log the result
        $this->log_event('learning_completed', "Learning completed for agent: $agent", array(
            'agent' => $agent,
            'health' => $result['health'],
            'insights_generated' => $result['insights_generated'],
            'duration_seconds' => $duration
        ));
        
        $this->learning_active = false;
        
        return array(
            'success' => true,
            'message' => "Learning completed for agent: {$this->get_agent_display_name($agent)}. Generated {$result['insights_generated']} new insights.",
            'health' => $result['health'],
            'insights_generated' => $result['insights_generated'],
            'duration_seconds' => $duration
        );
    }
    
    /**
     * Get new artworks since last learning cycle
     * 
     * @since 1.0.0
     * @return array Array of artwork data
     */
    private function get_new_artworks_since_last_learning() {
        $query_args = array(
            'post_type' => 'vortex_artwork',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'date_query' => array(
                array(
                    'after' => date('Y-m-d H:i:s', $this->last_learning_time)
                )
            )
        );
        
        $artwork_query = new WP_Query($query_args);
        $artworks = array();
        
        if ($artwork_query->have_posts()) {
            while ($artwork_query->have_posts()) {
                $artwork_query->the_post();
                $artwork_id = get_the_ID();
                
                // Get artwork metadata
                $metadata = get_post_meta($artwork_id);
                
                // Format artwork data for analysis
                $artworks[] = array(
                    'id' => $artwork_id,
                    'title' => get_the_title(),
                    'description' => get_the_content(),
                    'artist_id' => get_post_field('post_author', $artwork_id),
                    'creation_date' => get_the_date('Y-m-d H:i:s'),
                    'price' => isset($metadata['_vortex_artwork_price']) ? $metadata['_vortex_artwork_price'][0] : 0,
                    'edition_size' => isset($metadata['_vortex_artwork_edition_size']) ? $metadata['_vortex_artwork_edition_size'][0] : 1,
                    'ai_prompt' => isset($metadata['_vortex_artwork_ai_prompt']) ? $metadata['_vortex_artwork_ai_prompt'][0] : '',
                    'created_with_huraii' => isset($metadata['_vortex_created_with_huraii']) ? (bool)$metadata['_vortex_created_with_huraii'][0] : false,
                    'blockchain_token_id' => isset($metadata['_vortex_blockchain_token_id']) ? $metadata['_vortex_blockchain_token_id'][0] : '',
                    'blockchain_contract_address' => isset($metadata['_vortex_blockchain_contract_address']) ? $metadata['_vortex_blockchain_contract_address'][0] : '',
                    'blockchain_name' => isset($metadata['_vortex_blockchain_name']) ? $metadata['_vortex_blockchain_name'][0] : '',
                    'categories' => wp_get_post_terms($artwork_id, 'art_category', array('fields' => 'names')),
                    'styles' => wp_get_post_terms($artwork_id, 'art_style', array('fields' => 'names')),
                );
            }
        }
        
        wp_reset_postdata();
        return $artworks;
    }
    
    /**
     * Get user interactions since last learning cycle
     * 
     * @since 1.0.0
     * @return array Array of user interaction data
     */
    private function get_user_interactions_since_last_learning() {
        global $wpdb;
        
        // Get view interactions
        $view_interactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_user_interactions 
             WHERE interaction_type = 'view' 
             AND created_at > %s 
             ORDER BY created_at DESC",
            date('Y-m-d H:i:s', $this->last_learning_time)
        ));
        
        // Get like interactions
        $like_interactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_user_interactions 
             WHERE interaction_type = 'like' 
             AND created_at > %s 
             ORDER BY created_at DESC",
            date('Y-m-d H:i:s', $this->last_learning_time)
        ));
        
        // Get save interactions
        $save_interactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_user_interactions 
             WHERE interaction_type = 'save' 
             AND created_at > %s 
             ORDER BY created_at DESC",
            date('Y-m-d H:i:s', $this->last_learning_time)
        ));
        
        // Get share interactions
        $share_interactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_user_interactions 
             WHERE interaction_type = 'share' 
             AND created_at > %s 
             ORDER BY created_at DESC",
            date('Y-m-d H:i:s', $this->last_learning_time)
        ));
        
        // Get comment interactions
        $comment_interactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_user_interactions 
             WHERE interaction_type = 'comment' 
             AND created_at > %s 
             ORDER BY created_at DESC",
            date('Y-m-d H:i:s', $this->last_learning_time)
        ));
        
        return array(
            'views' => $view_interactions,
            'likes' => $like_interactions,
            'saves' => $save_interactions,
            'shares' => $share_interactions,
            'comments' => $comment_interactions
        );
    }
    
    /**
     * Get market data since last learning cycle
     * 
     * @since 1.0.0
     * @return array Array of market data
     */
    private function get_market_data_since_last_learning() {
        global $wpdb;
        
        // Get sales data
        $sales_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_sales 
             WHERE sale_date > %s 
             ORDER BY sale_date DESC",
            date('Y-m-d H:i:s', $this->last_learning_time)
        ));
        
        // Get token transactions
        $token_transactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_transactions 
             WHERE created_at > %s 
             ORDER BY created_at DESC",
            date('Y-m-d H:i:s', $this->last_learning_time)
        ));
        
        return array(
            'sales' => $sales_data,
            'token_transactions' => $token_transactions
        );
    }
    
    /**
     * Get security data since last learning cycle
     * 
     * @since 1.0.0
     * @return array Array of security data
     */
    private function get_security_data_since_last_learning() {
        global $wpdb;
        
        // Get security alerts
        $security_alerts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_security_alerts 
             WHERE created_at > %s 
             ORDER BY created_at DESC",
            date('Y-m-d H:i:s', $this->last_learning_time)
        ));
        
        // Get user reports
        $user_reports = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_user_reports 
             WHERE created_at > %s 
             ORDER BY created_at DESC",
            date('Y-m-d H:i:s', $this->last_learning_time)
        ));
        
        return array(
            'security_alerts' => $security_alerts,
            'user_reports' => $user_reports
        );
    }
    
    /**
     * Log an event
     * 
     * @since 1.0.0
     * @param string $event_type Type of event
     * @param string $message Event message
     * @param array $data Additional data
     */
    public function log_event($event_type, $message, $data = array()) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_agent_logs',
            array(
                'agent_name' => 'orchestrator',
                'event_type' => $event_type,
                'message' => $message,
                'data' => !empty($data) ? json_encode($data) : null,
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Get agent display name
     * 
     * @since 1.0.0
     * @param string $agent_name Technical agent name
     * @return string User-friendly agent name
     */
    private function get_agent_display_name($agent_name) {
        $display_names = array(
            'huraii' => 'HURAII',
            'cloe' => 'CLOE',
            'business_strategist' => 'Business Strategist',
            'thorius' => 'Thorius',
            'orchestrator' => 'AI Orchestrator'
        );
        
        return isset($display_names[$agent_name]) ? $display_names[$agent_name] : ucfirst($agent_name);
    }

    /**
     * Register necessary AJAX actions
     * 
     * @since 1.0.0
     */
    private function register_ajax_actions() {
        add_action('wp_ajax_vortex_get_agent_status', array($this, 'ajax_get_agent_status'));
        add_action('wp_ajax_vortex_trigger_agent_learning', array($this, 'ajax_trigger_agent_learning'));
        add_action('wp_ajax_vortex_get_agent_insights', array($this, 'ajax_get_agent_insights'));
        add_action('wp_ajax_nopriv_vortex_get_agent_insights', array($this, 'ajax_get_agent_insights'));
        
        // Admin-only actions
        add_action('wp_ajax_vortex_admin_get_learning_metrics', array($this, 'ajax_admin_get_learning_metrics'));
        add_action('wp_ajax_vortex_admin_get_agent_logs', array($this, 'ajax_admin_get_agent_logs'));
    }
    
    /**
     * Trigger daily learning cycle
     * 
     * @since 1.0.0
     */
    public function trigger_daily_learning() {
        if ($this->learning_active) {
            $this->log_event('daily_learning_skipped', 'Daily learning skipped because learning is already active');
            return;
        }
        
        $this->learning_active = true;
        $this->log_event('daily_learning_started', 'Daily learning cycle started');
        
        $start_time = time();
        
        try {
            // Get data for learning
            $artworks = $this->get_new_artworks_since_last_learning();
            $user_interactions = $this->get_user_interactions_since_last_learning();
            $market_data = $this->get_market_data_since_last_learning();
            $security_data = $this->get_security_data_since_last_learning();
            
            $data_package = array(
                'artworks' => $artworks,
                'interactions' => $user_interactions,
                'market_data' => $market_data,
                'security_data' => $security_data,
                'mode' => 'daily'
            );
            
            // Initialize metrics
            $metrics = array(
                'artworks_analyzed' => count($artworks),
                'users_analyzed' => $this->count_unique_users_in_interactions($user_interactions),
                'insights_generated' => 0,
                'learning_duration' => 0,
                'huraii_health' => 0,
                'cloe_health' => 0,
                'business_strategist_health' => 0,
                'thorius_health' => 0,
            );
            
            // Process learning for each agent
            if (isset($this->huraii) && method_exists($this->huraii, 'learn')) {
                $result = $this->huraii->learn($data_package);
                $metrics['insights_generated'] += isset($result['insights_generated']) ? $result['insights_generated'] : 0;
                $metrics['huraii_health'] = isset($result['health']) ? $result['health'] : 0.5;
            }
            
            if (isset($this->cloe) && method_exists($this->cloe, 'learn')) {
                $result = $this->cloe->learn($data_package);
                $metrics['insights_generated'] += isset($result['insights_generated']) ? $result['insights_generated'] : 0;
                $metrics['cloe_health'] = isset($result['health']) ? $result['health'] : 0.5;
            }
            
            if (isset($this->business_strategist) && method_exists($this->business_strategist, 'learn')) {
                $result = $this->business_strategist->learn($data_package);
                $metrics['insights_generated'] += isset($result['insights_generated']) ? $result['insights_generated'] : 0;
                $metrics['business_strategist_health'] = isset($result['health']) ? $result['health'] : 0.5;
            }
            
            if (isset($this->thorius) && method_exists($this->thorius, 'learn')) {
                $result = $this->thorius->learn($data_package);
                $metrics['insights_generated'] += isset($result['insights_generated']) ? $result['insights_generated'] : 0;
                $metrics['thorius_health'] = isset($result['health']) ? $result['health'] : 0.5;
            }
            
            // Calculate duration
            $metrics['learning_duration'] = time() - $start_time;
            
            // Perform cross-agent learning
            $this->perform_cross_agent_learning();
            
            // Log metrics
            $this->log_learning_metrics($metrics);
            
            // Update last learning time
            $this->last_learning_time = time();
            update_option('vortex_last_learning_time', $this->last_learning_time);
            
            // Log completion
            $this->log_event('daily_learning_completed', 'Daily learning cycle completed', $metrics);
            
            // Fire action for other components to react to learning completion
            do_action('vortex_daily_learning_complete', $metrics);
            
        } catch (Exception $e) {
            $this->log_event('daily_learning_error', 'Error during daily learning: ' . $e->getMessage());
        }
        
        $this->learning_active = false;
    }
    
    /**
     * Perform cross-agent learning and insight sharing
     * 
     * @since 1.0.0
     */
    private function perform_cross_agent_learning() {
        global $wpdb;
        
        // Get the most recent insights from each agent
        $recent_insights = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vortex_agent_insights 
             WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
             ORDER BY created_at DESC"
        );
        
        if (empty($recent_insights)) {
            return;
        }
        
        // Group insights by agent
        $insights_by_agent = array();
        foreach ($recent_insights as $insight) {
            if (!isset($insights_by_agent[$insight->agent_name])) {
                $insights_by_agent[$insight->agent_name] = array();
            }
            $insights_by_agent[$insight->agent_name][] = $insight;
        }
        
        // Perform cross-agent learning
        $cross_learning_results = array();
        
        // HURAII learns from other agents
        if (isset($this->huraii) && method_exists($this->huraii, 'process_cross_agent_insights') && isset($insights_by_agent)) {
            $insights_for_huraii = array();
            if (isset($insights_by_agent['cloe'])) $insights_for_huraii = array_merge($insights_for_huraii, $insights_by_agent['cloe']);
            if (isset($insights_by_agent['business_strategist'])) $insights_for_huraii = array_merge($insights_for_huraii, $insights_by_agent['business_strategist']);
            if (isset($insights_by_agent['thorius'])) $insights_for_huraii = array_merge($insights_for_huraii, $insights_by_agent['thorius']);
            
            if (!empty($insights_for_huraii)) {
                $result = $this->huraii->process_cross_agent_insights($insights_for_huraii);
                $cross_learning_results['huraii'] = $result;
            }
        }
        
        // CLOE learns from other agents
        if (isset($this->cloe) && method_exists($this->cloe, 'process_cross_agent_insights') && isset($insights_by_agent)) {
            $insights_for_cloe = array();
            if (isset($insights_by_agent['huraii'])) $insights_for_cloe = array_merge($insights_for_cloe, $insights_by_agent['huraii']);
            if (isset($insights_by_agent['business_strategist'])) $insights_for_cloe = array_merge($insights_for_cloe, $insights_by_agent['business_strategist']);
            if (isset($insights_by_agent['thorius'])) $insights_for_cloe = array_merge($insights_for_cloe, $insights_by_agent['thorius']);
            
            if (!empty($insights_for_cloe)) {
                $result = $this->cloe->process_cross_agent_insights($insights_for_cloe);
                $cross_learning_results['cloe'] = $result;
            }
        }
        
        // Business Strategist learns from other agents
        if (isset($this->business_strategist) && method_exists($this->business_strategist, 'process_cross_agent_insights') && isset($insights_by_agent)) {
            $insights_for_bs = array();
            if (isset($insights_by_agent['huraii'])) $insights_for_bs = array_merge($insights_for_bs, $insights_by_agent['huraii']);
            if (isset($insights_by_agent['cloe'])) $insights_for_bs = array_merge($insights_for_bs, $insights_by_agent['cloe']);
            if (isset($insights_by_agent['thorius'])) $insights_for_bs = array_merge($insights_for_bs, $insights_by_agent['thorius']);
            
            if (!empty($insights_for_bs)) {
                $result = $this->business_strategist->process_cross_agent_insights($insights_for_bs);
                $cross_learning_results['business_strategist'] = $result;
            }
        }
        
        // Thorius learns from other agents
        if (isset($this->thorius) && method_exists($this->thorius, 'process_cross_agent_insights') && isset($insights_by_agent)) {
            $insights_for_thorius = array();
            if (isset($insights_by_agent['huraii'])) $insights_for_thorius = array_merge($insights_for_thorius, $insights_by_agent['huraii']);
            if (isset($insights_by_agent['cloe'])) $insights_for_thorius = array_merge($insights_for_thorius, $insights_by_agent['cloe']);
            if (isset($insights_by_agent['business_strategist'])) $insights_for_thorius = array_merge($insights_for_thorius, $insights_by_agent['business_strategist']);
            
            if (!empty($insights_for_thorius)) {
                $result = $this->thorius->process_cross_agent_insights($insights_for_thorius);
                $cross_learning_results['thorius'] = $result;
            }
        }
        
        // Update agent connections based on learning results
        $this->update_agent_connections($cross_learning_results);
        
        // Log cross-agent learning
        $this->log_event('cross_agent_learning_completed', 'Cross-agent learning completed', $cross_learning_results);
    }
    
    /**
     * Update agent connections based on learning results
     * 
     * @since 1.0.0
     * @param array $cross_learning_results Results from cross-agent learning
     */
    private function update_agent_connections($cross_learning_results) {
        global $wpdb;
        
        foreach ($cross_learning_results as $target_agent => $results) {
            if (empty($results['source_agents'])) {
                continue;
            }
            
            foreach ($results['source_agents'] as $source_agent => $metrics) {
                $connection_strength = isset($metrics['influence']) ? floatval($metrics['influence']) : 0.5;
                $connection_type = isset($metrics['type']) ? $metrics['type'] : 'insight_sharing';
                
                // Update or insert connection record
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}vortex_agent_connections 
                     WHERE source_agent = %s AND target_agent = %s AND connection_type = %s",
                    $source_agent, $target_agent, $connection_type
                ));
                
                if ($existing) {
                    $wpdb->update(
                        $wpdb->prefix . 'vortex_agent_connections',
                        array(
                            'connection_strength' => $connection_strength,
                            'updated_at' => current_time('mysql')
                        ),
                        array(
                            'id' => $existing
                        )
                    );
                } else {
                    $wpdb->insert(
                        $wpdb->prefix . 'vortex_agent_connections',
                        array(
                            'source_agent' => $source_agent,
                            'target_agent' => $target_agent,
                            'connection_type' => $connection_type,
                            'connection_strength' => $connection_strength,
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql')
                        )
                    );
                }
            }
        }
    }
    
    /**
     * Log learning metrics to database
     * 
     * @since 1.0.0
     * @param array $metrics Learning metrics
     */
    private function log_learning_metrics($metrics) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_learning_metrics_history',
            array(
                'artworks_analyzed' => $metrics['artworks_analyzed'],
                'users_analyzed' => $metrics['users_analyzed'],
                'insights_generated' => $metrics['insights_generated'],
                'learning_duration' => $metrics['learning_duration'],
                'huraii_health' => $metrics['huraii_health'],
                'cloe_health' => $metrics['cloe_health'],
                'business_strategist_health' => $metrics['business_strategist_health'],
                'thorius_health' => $metrics['thorius_health'],
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Count unique users in interaction data
     * 
     * @since 1.0.0
     * @param array $interactions User interaction data
     * @return int Number of unique users
     */
    private function count_unique_users_in_interactions($interactions) {
        $unique_users = array();
        
        foreach ($interactions as $type => $interaction_list) {
            foreach ($interaction_list as $interaction) {
                if (isset($interaction->user_id) && !in_array($interaction->user_id, $unique_users)) {
                    $unique_users[] = $interaction->user_id;
                }
            }
        }
        
        return count($unique_users);
    }
    
    /**
     * Trigger weekly deep learning cycle
     * 
     * @since 1.0.0
     */
    public function trigger_weekly_deep_learning() {
        if ($this->learning_active) {
            $this->log_event('weekly_deep_learning_skipped', 'Weekly deep learning skipped because learning is already active');
            return;
        }
        
        $this->learning_active = true;
        $this->log_event('weekly_deep_learning_started', 'Weekly deep learning cycle started');
        
        try {
            // Get comprehensive historical data
            $comprehensive_data = $this->get_comprehensive_historical_data();
            
            // Perform deep learning for each agent
            $agents = array(
                'huraii' => $this->huraii,
                'cloe' => $this->cloe,
                'business_strategist' => $this->business_strategist,
                'thorius' => $this->thorius
            );
            
            $deep_learning_results = array();
            
            foreach ($agents as $agent_name => $agent) {
                if (isset($agent) && method_exists($agent, 'deep_learn')) {
                    $result = $agent->deep_learn($comprehensive_data);
                    $deep_learning_results[$agent_name] = $result;
                }
            }
            
            // Update connections after deep learning
            $this->update_deep_learning_connections($deep_learning_results);
            
            // Log completion
            $this->log_event('weekly_deep_learning_completed', 'Weekly deep learning cycle completed', $deep_learning_results);
            
            // Fire action for other components to react to deep learning completion
            do_action('vortex_weekly_deep_learning_complete', $deep_learning_results);
            
        } catch (Exception $e) {
            $this->log_event('weekly_deep_learning_error', 'Error during weekly deep learning: ' . $e->getMessage());
        }
        
        $this->learning_active = false;
    }
    
    /**
     * Update connections after deep learning
     * 
     * @since 1.0.0
     * @param array $deep_learning_results Results from deep learning
     */
    private function update_deep_learning_connections($deep_learning_results) {
        global $wpdb;
        
        foreach ($deep_learning_results as $agent_name => $result) {
            if (empty($result['agent_connections'])) {
                continue;
            }
            
            foreach ($result['agent_connections'] as $target_agent => $connection) {
                $connection_strength = isset($connection['strength']) ? floatval($connection['strength']) : 0.5;
                $connection_type = 'deep_learning';
                
                // Update or insert connection record
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}vortex_agent_connections 
                     WHERE source_agent = %s AND target_agent = %s AND connection_type = %s",
                    $agent_name, $target_agent, $connection_type
                ));
                
                if ($existing) {
                    $wpdb->update(
                        $wpdb->prefix . 'vortex_agent_connections',
                        array(
                            'connection_strength' => $connection_strength,
                            'updated_at' => current_time('mysql')
                        ),
                        array(
                            'id' => $existing
                        )
                    );
                } else {
                    $wpdb->insert(
                        $wpdb->prefix . 'vortex_agent_connections',
                        array(
                            'source_agent' => $agent_name,
                            'target_agent' => $target_agent,
                            'connection_type' => $connection_type,
                            'connection_strength' => $connection_strength,
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql')
                        )
                    );
                }
            }
        }
    }

    /**
     * Get comprehensive historical data for deep learning
     * 
     * @since 1.0.0
     * @return array Comprehensive historical data
     */
    private function get_comprehensive_historical_data() {
        global $wpdb;
        
        // Get all artworks with basic metrics
        $artworks = $wpdb->get_results(
            "SELECT p.ID, p.post_title, p.post_date, p.post_author,
                    COUNT(DISTINCT l.id) as total_likes,
                    COUNT(DISTINCT v.id) as total_views,
                    COUNT(DISTINCT s.id) as total_sales,
                    AVG(s.amount) as average_sale_price
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->prefix}vortex_user_interactions l ON p.ID = l.object_id AND l.interaction_type = 'like'
             LEFT JOIN {$wpdb->prefix}vortex_user_interactions v ON p.ID = v.object_id AND v.interaction_type = 'view'
             LEFT JOIN {$wpdb->prefix}vortex_sales s ON p.ID = s.artwork_id
             WHERE p.post_type = 'vortex_artwork'
             GROUP BY p.ID
             ORDER BY p.post_date DESC"
        );
        
        // Get all artist metrics
        $artists = $wpdb->get_results(
            "SELECT u.ID, u.display_name, u.user_registered,
                    COUNT(DISTINCT p.ID) as total_artworks,
                    COUNT(DISTINCT s.id) as total_sales,
                    SUM(s.amount) as total_revenue,
                    AVG(s.amount) as average_sale_price
             FROM {$wpdb->users} u
             LEFT JOIN {$wpdb->posts} p ON u.ID = p.post_author AND p.post_type = 'vortex_artwork'
             LEFT JOIN {$wpdb->prefix}vortex_sales s ON p.ID = s.artwork_id
             GROUP BY u.ID
             ORDER BY total_revenue DESC"
        );
        
        // Get all interactions grouped by type and period
        $interactions = $wpdb->get_results(
            "SELECT interaction_type, 
                    DATE_FORMAT(created_at, '%Y-%m-%d') as date,
                    COUNT(*) as count
             FROM {$wpdb->prefix}vortex_user_interactions
             GROUP BY interaction_type, DATE_FORMAT(created_at, '%Y-%m-%d')
             ORDER BY created_at DESC"
        );
        
        // Get all sales grouped by period
        $sales = $wpdb->get_results(
            "SELECT DATE_FORMAT(sale_date, '%Y-%m-%d') as date,
                    COUNT(*) as count,
                    SUM(amount) as total_amount,
                    AVG(amount) as average_amount
             FROM {$wpdb->prefix}vortex_sales
             GROUP BY DATE_FORMAT(sale_date, '%Y-%m-%d')
             ORDER BY sale_date DESC"
        );
        
        // Get all token transactions
        $token_transactions = $wpdb->get_results(
            "SELECT DATE_FORMAT(created_at, '%Y-%m-%d') as date,
                    COUNT(*) as count,
                    SUM(amount) as total_amount,
                    AVG(amount) as average_amount
             FROM {$wpdb->prefix}vortex_transactions
             GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d')
             ORDER BY created_at DESC"
        );
        
        // Get agent insights
        $insights = $wpdb->get_results(
            "SELECT agent_name, insight_type, 
                    DATE_FORMAT(created_at, '%Y-%m-%d') as date,
                    COUNT(*) as count
             FROM {$wpdb->prefix}vortex_agent_insights
             GROUP BY agent_name, insight_type, DATE_FORMAT(created_at, '%Y-%m-%d')
             ORDER BY created_at DESC"
        );
        
        return array(
            'artworks' => $artworks,
            'artists' => $artists,
            'interactions' => $interactions,
            'sales' => $sales,
            'token_transactions' => $token_transactions,
            'insights' => $insights
        );
    }
    
    /**
     * Trigger incremental learning
     * 
     * @since 1.0.0
     */
    public function trigger_incremental_learning() {
        if ($this->learning_active) {
            return;
        }
        
        $this->learning_active = true;
        $this->log_event('incremental_learning_started', 'Incremental learning started');
        
        try {
            // Get recent data
            $recent_data = array(
                'artworks' => $this->get_recent_artworks(10),
                'interactions' => $this->get_recent_interactions(50),
                'market_data' => $this->get_recent_market_data(20),
                'security_data' => $this->get_recent_security_data(10)
            );
            
            // Perform incremental learning for each agent
            $agents = array(
                'huraii' => $this->huraii,
                'cloe' => $this->cloe,
                'business_strategist' => $this->business_strategist,
                'thorius' => $this->thorius
            );
            
            $incremental_results = array();
            
            foreach ($agents as $agent_name => $agent) {
                if (isset($agent) && method_exists($agent, 'incremental_learn')) {
                    $result = $agent->incremental_learn($recent_data);
                    $incremental_results[$agent_name] = $result;
                }
            }
            
            // Log completion
            $this->log_event('incremental_learning_completed', 'Incremental learning completed', $incremental_results);
            
        } catch (Exception $e) {
            $this->log_event('incremental_learning_error', 'Error during incremental learning: ' . $e->getMessage());
        }
        
        $this->learning_active = false;
    }
    
    /**
     * Get recent artworks
     * 
     * @since 1.0.0
     * @param int $limit Number of artworks to retrieve
     * @return array Recent artworks
     */
    private function get_recent_artworks($limit = 10) {
        $query_args = array(
            'post_type' => 'vortex_artwork',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $artwork_query = new WP_Query($query_args);
        $artworks = array();
        
        if ($artwork_query->have_posts()) {
            while ($artwork_query->have_posts()) {
                $artwork_query->the_post();
                $artwork_id = get_the_ID();
                
                // Get artwork metadata
                $metadata = get_post_meta($artwork_id);
                
                // Format artwork data for analysis
                $artworks[] = array(
                    'id' => $artwork_id,
                    'title' => get_the_title(),
                    'description' => get_the_content(),
                    'artist_id' => get_post_field('post_author', $artwork_id),
                    'creation_date' => get_the_date('Y-m-d H:i:s'),
                    'price' => isset($metadata['_vortex_artwork_price']) ? $metadata['_vortex_artwork_price'][0] : 0,
                    'edition_size' => isset($metadata['_vortex_artwork_edition_size']) ? $metadata['_vortex_artwork_edition_size'][0] : 1,
                    'ai_prompt' => isset($metadata['_vortex_artwork_ai_prompt']) ? $metadata['_vortex_artwork_ai_prompt'][0] : '',
                    'created_with_huraii' => isset($metadata['_vortex_created_with_huraii']) ? (bool)$metadata['_vortex_created_with_huraii'][0] : false,
                    'blockchain_token_id' => isset($metadata['_vortex_blockchain_token_id']) ? $metadata['_vortex_blockchain_token_id'][0] : '',
                    'blockchain_contract_address' => isset($metadata['_vortex_blockchain_contract_address']) ? $metadata['_vortex_blockchain_contract_address'][0] : '',
                    'blockchain_name' => isset($metadata['_vortex_blockchain_name']) ? $metadata['_vortex_blockchain_name'][0] : '',
                    'categories' => wp_get_post_terms($artwork_id, 'art_category', array('fields' => 'names')),
                    'styles' => wp_get_post_terms($artwork_id, 'art_style', array('fields' => 'names')),
                );
            }
        }
        
        wp_reset_postdata();
        return $artworks;
    }
    
    /**
     * Get recent interactions
     * 
     * @since 1.0.0
     * @param int $limit Number of interactions to retrieve
     * @return array Recent interactions
     */
    private function get_recent_interactions($limit = 50) {
        global $wpdb;
        
        $interactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_user_interactions 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        ));
        
        return $interactions;
    }
    
    /**
     * Get recent market data
     * 
     * @since 1.0.0
     * @param int $limit Number of records to retrieve
     * @return array Recent market data
     */
    private function get_recent_market_data($limit = 20) {
        global $wpdb;
        
        // Get recent sales
        $sales = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_sales 
             ORDER BY sale_date DESC 
             LIMIT %d",
            $limit
        ));
        
        // Get recent transactions
        $transactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_transactions 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        ));
        
        return array(
            'sales' => $sales,
            'transactions' => $transactions
        );
    }
    
    /**
     * Get recent security data
     * 
     * @since 1.0.0
     * @param int $limit Number of records to retrieve
     * @return array Recent security data
     */
    private function get_recent_security_data($limit = 10) {
        global $wpdb;
        
        // Get recent security alerts
        $alerts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_security_alerts 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        ));
        
        // Get recent user reports
        $reports = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_user_reports 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        ));
        
        return array(
            'alerts' => $alerts,
            'reports' => $reports
        );
    }
    
    /**
     * Get agent insights from database
     * 
     * @since 1.0.0
     * @param string $agent Agent name or 'all'
     * @param string $insight_type Type of insight or 'all'
     * @param int $limit Number of insights to retrieve
     * @param int $offset Pagination offset
     * @return array Agent insights
     */
    public function get_agent_insights($agent = 'all', $insight_type = 'all', $limit = 5, $offset = 0) {
        global $wpdb;
        
        $where = array("1=1");
        $where_values = array();
        
        if ($agent !== 'all') {
            $where[] = "agent_name = %s";
            $where_values[] = $agent;
        }
        
        if ($insight_type !== 'all') {
            $where[] = "insight_type = %s";
            $where_values[] = $insight_type;
        }
        
        $query = "SELECT * FROM {$wpdb->prefix}vortex_agent_insights 
                 WHERE " . implode(' AND ', $where) . " 
                 ORDER BY created_at DESC
                 LIMIT %d, %d";
        
        $where_values[] = $offset;
        $where_values[] = $limit;
        
        $insights = $wpdb->get_results($wpdb->prepare($query, $where_values));
        
        return $insights;
    }
    
    /**
     * Get agent insights for display
     * 
     * @since 1.0.0
     * @param string $agent Agent name or 'all'
     * @param string $insight_type Type of insight or 'latest'
     * @param int $limit Number of insights to retrieve
     * @return array Formatted agent insights for display
     */
    public function get_agent_insights_for_display($agent = 'all', $insight_type = 'latest', $limit = 5) {
        $insights = $this->get_agent_insights($agent, ($insight_type === 'latest' ? 'all' : $insight_type), $limit);
        
        $formatted_insights = array();
        
        foreach ($insights as $insight) {
            $insight_data = json_decode($insight->insight_data, true);
            
            $formatted_insights[] = array(
                'id' => $insight->id,
                'agent_name' => $insight->agent_name,
                'agent_display_name' => $this->get_agent_display_name($insight->agent_name),
                'agent_color' => $this->get_agent_color($insight->agent_name),
                'insight_type' => $insight->insight_type,
                'insight_title' => isset($insight_data['title']) ? $insight_data['title'] : 'Insight',
                'insight_description' => isset($insight_data['description']) ? $insight_data['description'] : '',
                'confidence' => $insight->confidence * 100, // Convert to percentage
                'created_at' => $insight->created_at,
                'created_at_human' => human_time_diff(strtotime($insight->created_at), current_time('timestamp')) . ' ago',
                'related_entities' => json_decode($insight->related_entities, true),
                'data' => $insight_data
            );
        }
        
        return $formatted_insights;
    }
    
    /**
     * Get agent color
     * 
     * @since 1.0.0
     * @param string $agent_name Agent name
     * @return string Color code for agent
     */
    private function get_agent_color($agent_name) {
        $colors = array(
            'huraii' => '#4e54c8',
            'cloe' => '#36b37e',
            'business_strategist' => '#ff9f43',
            'thorius' => '#ff6b6b',
            'orchestrator' => '#6c5ce7'
        );
        
        return isset($colors[$agent_name]) ? $colors[$agent_name] : '#333333';
    }
    
    /**
     * Ensure continuous deep learning for all agents
     * 
     * @since 1.0.0
     */
    public function ensure_continuous_learning() {
        $agents = array(
            'huraii' => $this->huraii,
            'cloe' => $this->cloe,
            'business_strategist' => $this->business_strategist,
            'thorius' => $this->thorius
        );
        
        foreach ($agents as $agent_id => $agent) {
            if (!$agent) {
                continue;
            }
            
            if (method_exists($agent, 'is_learning') && !$agent->is_learning()) {
                if (method_exists($agent, 'get_health_score')) {
                    // Check health score and retrain if below threshold
                    $health = $agent->get_health_score();
                    if ($health < 0.75) {
                        $this->trigger_single_agent_learning($agent_id);
                        $this->log_event('maintenance_learning', "Triggered maintenance learning for {$agent_id} due to low health score: {$health}");
                    }
                }
            }
        }
    }
    
    /**
     * AJAX handler for getting agent status
     * 
     * @since 1.0.0
     */
    public function ajax_get_agent_status() {
        // Check nonce for security
        check_ajax_referer('vortex_nonce', 'nonce');
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to access this data'));
            return;
        }
        
        $response = array(
            'learning_active' => $this->learning_active,
            'last_learning_time' => $this->last_learning_time,
            'last_learning_time_formatted' => $this->last_learning_time > 0 ? 
                human_time_diff($this->last_learning_time, time()) . ' ago' : 'Never',
            'agent_health' => $this->check_agent_health(),
            'total_insights' => array(
                'huraii' => $this->get_agent_insights_count('huraii'),
                'cloe' => $this->get_agent_insights_count('cloe'),
                'business_strategist' => $this->get_agent_insights_count('business_strategist'),
                'thorius' => $this->get_agent_insights_count('thorius'),
                'orchestrator' => $this->get_agent_insights_count('orchestrator')
            ),
            'learning_cycles_completed' => $this->get_learning_cycles_completed(),
            'total_artworks_analyzed' => $this->get_total_artworks_analyzed()
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Get total artworks analyzed
     * 
     * @since 1.0.0
     * @return int Number of artworks analyzed
     */
    private function get_total_artworks_analyzed() {
        global $wpdb;
        
        return (int) $wpdb->get_var("SELECT SUM(artworks_analyzed) FROM {$wpdb->prefix}vortex_learning_metrics_history");
    }
    
    /**
     * Get total insights generated
     * 
     * @since 1.0.0
     * @return int Number of insights generated
     */
    private function get_total_insights_generated() {
        global $wpdb;
        
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_agent_insights");
    }
    
    /**
     * Get agent insights count
     * 
     * @since 1.0.0
     * @param string $agent_name Agent name
     * @return int Number of insights for agent
     */
    private function get_agent_insights_count($agent_name) {
        global $wpdb;
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_agent_insights WHERE agent_name = %s",
            $agent_name
        ));
    }
    
    /**
     * Get learning cycles completed
     * 
     * @since 1.0.0
     * @return int Number of learning cycles completed
     */
    private function get_learning_cycles_completed() {
        global $wpdb;
        
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_learning_metrics_history");
    }
    
    /**
     * Check agent health
     * 
     * @since 1.0.0
     * @return array Health status for each agent
     */
    private function check_agent_health() {
        $health = array(
            'huraii' => 0.5,
            'cloe' => 0.5,
            'business_strategist' => 0.5,
            'thorius' => 0.5
        );
        
        if (isset($this->huraii) && method_exists($this->huraii, 'get_health_score')) {
            $health['huraii'] = $this->huraii->get_health_score();
        }
        
        if (isset($this->cloe) && method_exists($this->cloe, 'get_health_score')) {
            $health['cloe'] = $this->cloe->get_health_score();
        }
        
        if (isset($this->business_strategist) && method_exists($this->business_strategist, 'get_health_score')) {
            $health['business_strategist'] = $this->business_strategist->get_health_score();
        }
        
        if (isset($this->thorius) && method_exists($this->thorius, 'get_health_score')) {
            $health['thorius'] = $this->thorius->get_health_score();
        }
        
        return $health;
    }
    
    /**
     * AJAX handler for triggering agent learning
     * 
     * @since 1.0.0
     */
    public function ajax_trigger_agent_learning() {
        // Check nonce for security
        check_ajax_referer('vortex_nonce', 'nonce');
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to trigger learning'));
            return;
        }
        
        // Check if learning is already active
        if ($this->learning_active) {
            wp_send_json_error(array('message' => 'Learning is already in progress'));
            return;
        }
        
        $agent = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : 'all';
        
        if ($agent === 'all') {
            // Trigger learning for all agents
            wp_schedule_single_event(time(), 'vortex_orchestrator_daily_learning');
            wp_send_json_success(array('message' => 'Learning triggered for all agents'));
        } else {
            // Validate agent
            if (!in_array($agent, array('huraii', 'cloe', 'business_strategist', 'thorius'))) {
                wp_send_json_error(array('message' => 'Invalid agent specified'));
                return;
            }
            
            // Trigger learning for the specified agent
            $result = $this->trigger_single_agent_learning($agent);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
        }
    }
    
    /**
     * AJAX handler for getting agent insights
     * 
     * @since 1.0.0
     */
    public function ajax_get_agent_insights() {
        // Check nonce for security
        check_ajax_referer('vortex_nonce', 'nonce');
        
        $agent = isset($_GET['agent']) ? sanitize_text_field($_GET['agent']) : 'all';
        $insight_type = isset($_GET['insight_type']) ? sanitize_text_field($_GET['insight_type']) : 'latest';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        
        // Validate agent
        if ($agent !== 'all' && !in_array($agent, array('huraii', 'cloe', 'business_strategist', 'thorius', 'orchestrator'))) {
            wp_send_json_error(array('message' => 'Invalid agent specified'));
            return;
        }
        
        // Validate insight type
        $valid_types = array('latest', 'trending', 'recommendation', 'blockchain', 'alert', 'market', 'all');
        if (!in_array($insight_type, $valid_types)) {
            $insight_type = 'latest';
        }
        
        // Get insights
        $insights = $this->get_agent_insights_for_display($agent, $insight_type, $limit);
        
        // Get insight count for pagination
        global $wpdb;
        
        $where = array("1=1");
        $where_values = array();
        
        if ($agent !== 'all') {
            $where[] = "agent_name = %s";
            $where_values[] = $agent;
        }
        
        if ($insight_type !== 'all' && $insight_type !== 'latest') {
            $where[] = "insight_type = %s";
            $where_values[] = $insight_type;
        }
        
        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_agent_insights WHERE " . implode(' AND ', $where);
        $total = $wpdb->get_var($wpdb->prepare($query, $where_values));
        
        wp_send_json_success(array(
            'insights' => $insights,
            'total' => (int)$total,
            'has_more' => ($offset + $limit) < $total
        ));
    }
    
    /**
     * AJAX handler for getting admin learning metrics
     * 
     * @since 1.0.0
     */
    public function ajax_admin_get_learning_metrics() {
        // Check nonce for security
        check_ajax_referer('vortex_admin_nonce', 'security');
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to access this data'));
            return;
        }
        
        global $wpdb;
        
        // Get metrics history
        $metrics_history = $wpdb->get_results("
            SELECT *
            FROM {$wpdb->prefix}vortex_learning_metrics_history
            ORDER BY created_at DESC
            LIMIT 10
        ");
        
        // Get current overall metrics
        $current_metrics = array(
            'total_artworks_analyzed' => $this->get_total_artworks_analyzed(),
            'total_insights_generated' => $this->get_total_insights_generated(),
            'learning_cycles_completed' => $this->get_learning_cycles_completed(),
            'agent_health' => $this->check_agent_health(),
            'last_learning_session' => get_option('vortex_last_learning_time', ''),
            'learning_metrics_history' => $metrics_history
        );
        
        wp_send_json_success($current_metrics);
    }
    
    /**
     * AJAX handler for getting agent logs
     * 
     * @since 1.0.0
     */
    public function ajax_admin_get_agent_logs() {
        // Check nonce for security
        check_ajax_referer('vortex_admin_nonce', 'security');
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to access this data'));
            return;
        }
        
        $agent = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : 'all';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        $limit = min($limit, 200); // Cap at 200 for performance
        
        global $wpdb;
        
        $query = "
            SELECT *
            FROM {$wpdb->prefix}vortex_agent_logs
        ";
        
        if ($agent !== 'all') {
            $query .= $wpdb->prepare(" WHERE agent_name = %s", $agent);
        }
        
        $query .= "
            ORDER BY created_at DESC
            LIMIT %d
        ";
        
        $logs = $wpdb->get_results($wpdb->prepare($query, $limit));
        
        wp_send_json_success(array('logs' => $logs));
    }
    
    /**
     * Agent insights shortcode
     * 
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function agent_insights_shortcode($atts) {
        $atts = shortcode_atts(array(
            'agent' => 'all',
            'insight_type' => 'latest',
            'limit' => 5
        ), $atts);
        
        // Enqueue necessary styles and scripts
        wp_enqueue_style('vortex-ai-insights');
        wp_enqueue_script('vortex-ai-insights');
        
        // Get insights
        $insights = $this->get_agent_insights_for_display(
            $atts['agent'],
            $atts['insight_type'],
            intval($atts['limit'])
        );
        
        // Start output buffer
        ob_start();
        
        // Include the template
        include VORTEX_PLUGIN_DIR . 'public/partials/vortex-agent-insights.php';
        
        return ob_get_clean();
    }

    /**
     * Enhance cross-agent learning to maximize AI capabilities
     * 
     * Enables all agents to learn from each other's experiences and insights
     * through a coordinated knowledge sharing system.
     * 
     * @since 1.0.0
     * @return void
     */
    public function enhance_cross_agent_learning() {
        foreach ($this->ai_agents as $agent_id => $agent) {
            if (method_exists($agent, 'enhance_learning_from_peers')) {
                $agent->enhance_learning_from_peers($this->ai_agents);
            }
        }
        
        // Log enhancement for monitoring
        error_log('VORTEX Orchestrator: Cross-agent learning enhancement executed at ' . current_time('mysql'));
        
        // Track enhancement in database
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'vortex_system_logs',
            array(
                'log_type' => 'cross_learning',
                'message' => 'Cross-agent learning enhanced across ' . count($this->ai_agents) . ' agents',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
    }
}

// Initialize the Orchestrator
add_action('plugins_loaded', function() {
    $orchestrator = VORTEX_Orchestrator::get_instance();
}, 15);

// Register activation and deactivation hooks for database tables
register_activation_hook(VORTEX_PLUGIN_DIR . 'vortex-ai-marketplace.php', array('VORTEX_Orchestrator', 'create_tables'));
register_deactivation_hook(VORTEX_PLUGIN_DIR . 'vortex-ai-marketplace.php', array('VORTEX_Orchestrator', 'remove_cron_jobs')); 