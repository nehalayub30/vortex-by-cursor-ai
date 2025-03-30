<?php
/**
 * VORTEX Bootloader
 *
 * Ensures all AI agents start immediately when the site comes online
 * and maintains continuous deep learning at all times
 *
 * @package Vortex_Marketplace
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Bootloader Class
 */
class VORTEX_Bootloader {
    /**
     * The single instance of the class
     * @var VORTEX_Bootloader
     */
    private static $instance = null;
    
    /**
     * Launch status flag
     * @var bool
     */
    private $is_launched = false;
    
    /**
     * Main VORTEX_Bootloader Instance
     *
     * Ensures only one instance is loaded or can be loaded.
     *
     * @return VORTEX_Bootloader
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
        // Set priority to 1 to run before anything else
        add_action('init', array($this, 'check_and_execute_bootloader'), 1);
        
        // Add a hook that runs after plugins are loaded to ensure we catch the first visitor
        add_action('plugins_loaded', array($this, 'register_first_visitor_hook'), 1);
        
        // Check for online status
        add_action('wp_footer', array($this, 'detect_online_status'));
        add_action('admin_footer', array($this, 'detect_online_status'));
    }
    
    /**
     * Register hook for first visitor
     */
    public function register_first_visitor_hook() {
        if (!defined('WP_INSTALLING') || !WP_INSTALLING) {
            $this->check_for_first_visitor();
        }
    }
    
    /**
     * Check for first visitor
     */
    private function check_for_first_visitor() {
        $site_launched = get_option('vortex_site_launched', false);
        
        if (!$site_launched) {
            $this->is_launched = true;
            update_option('vortex_site_launched', true);
            update_option('vortex_launch_timestamp', time());
            
            $this->schedule_bootloader();
            
            error_log('VORTEX Bootloader: First visitor detected, launching bootloader');
        }
    }
    
    /**
     * Schedule bootloader
     */
    private function schedule_bootloader() {
        if (!wp_next_scheduled('vortex_bootloader_execution')) {
            wp_schedule_single_event(time() + 10, 'vortex_bootloader_execution');
        }
    }
    
    /**
     * Check and execute bootloader
     */
    public function check_and_execute_bootloader() {
        if (isset($_GET['vortex_force_boot']) && current_user_can('manage_options')) {
            // Force execution from admin
            $this->execute_bootloader();
            wp_redirect(remove_query_arg('vortex_force_boot'));
            exit;
        }
    }
    
    /**
     * Detect online status
     */
    public function detect_online_status() {
        // Only run once
        static $has_run = false;
        if ($has_run) return;
        $has_run = true;
        
        // If the site is not already launched, mark it as launched
        if (!get_option('vortex_site_launched', false)) {
            update_option('vortex_site_launched', true);
            update_option('vortex_launch_timestamp', time());
            
            $this->is_launched = true;
            $this->schedule_bootloader();
            
            error_log('VORTEX Bootloader: Online status detected, launching bootloader');
        }
        
        // Add a hidden pixel for detection
        echo '<img src="' . esc_url(admin_url('admin-ajax.php?action=vortex_heartbeat')) . '" style="position:absolute;width:1px;height:1px;opacity:0" />';
    }
    
    /**
     * Execute bootloader
     */
    public function execute_bootloader() {
        if (get_option('vortex_bootloader_executed', false)) {
            error_log('VORTEX Bootloader: Already executed, skipping');
            return;
        }
        
        error_log('VORTEX Bootloader: Starting execution');
        
        // 1. Ensure tables are created
        $this->create_required_tables();
        
        // 2. Start Launch Coordinator
        if (class_exists('VORTEX_Launch_Coordinator')) {
            $coordinator = VORTEX_Launch_Coordinator::get_instance();
            $coordinator->ignite_system();
        }
        
        // 3. Activate Enforcer in Strict Mode
        update_option('vortex_enforcer_strict_mode', true);
        
        // 4. Set ROI focus target
        update_option('vortex_ai_optimization_goal', 'roi');
        update_option('vortex_ai_target_roi', 80);
        
        // 5. Schedule regular heartbeats to ensure continuous learning
        if (!wp_next_scheduled('vortex_system_heartbeat')) {
            wp_schedule_event(time(), 'hourly', 'vortex_system_heartbeat');
        }
        
        // 6. Set bootloader executed flag
        update_option('vortex_bootloader_executed', true);
        update_option('vortex_bootloader_execution_time', time());
        
        error_log('VORTEX Bootloader: Execution completed');
    }
    
    /**
     * Create required tables
     */
    private function create_required_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create logs table if not exists
        $table_name = $wpdb->prefix . 'vortex_system_logs';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            log_type varchar(50) NOT NULL,
            message text NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY log_type (log_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Create cross-learning queue table if not exists
        $table_name2 = $wpdb->prefix . 'vortex_cross_learning_queue';
        $sql2 = "CREATE TABLE IF NOT EXISTS $table_name2 (
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
        
        // Create agent performance table if not exists
        $table_name3 = $wpdb->prefix . 'vortex_agent_performance';
        $sql3 = "CREATE TABLE IF NOT EXISTS $table_name3 (
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
        dbDelta($sql2);
        dbDelta($sql3);
        
        // Log table creation
        $wpdb->insert(
            $wpdb->prefix . 'vortex_system_logs',
            array(
                'log_type' => 'bootloader',
                'message' => 'Required database tables created or verified by bootloader',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
    }
}

// Register bootloader execution hook
add_action('vortex_bootloader_execution', function() {
    $bootloader = VORTEX_Bootloader::get_instance();
    $bootloader->execute_bootloader();
});

// Initialize the bootloader
add_action('plugins_loaded', function() {
    VORTEX_Bootloader::get_instance();
}, 1); // Highest priority to run before anything else

// Add AJAX handler for heartbeat
add_action('wp_ajax_vortex_heartbeat', 'vortex_handle_heartbeat');
add_action('wp_ajax_nopriv_vortex_heartbeat', 'vortex_handle_heartbeat');

function vortex_handle_heartbeat() {
    // Check if bootloader has been executed
    if (!get_option('vortex_bootloader_executed', false)) {
        // Schedule bootloader if not already scheduled
        if (!wp_next_scheduled('vortex_bootloader_execution')) {
            wp_schedule_single_event(time() + 10, 'vortex_bootloader_execution');
        }
    }
    
    // Return a transparent 1x1 pixel
    header('Content-Type: image/gif');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit;
} 