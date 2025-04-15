<?php
/**
 * VORTEX DAO Admin Functionality
 *
 * Handles DAO admin functionality including reward analytics
 *
 * @link       https://vortexmarketplace.io
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_DAO_Admin {
    
    private static $instance = null;
    
    /**
     * Get class instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register admin menu items
        add_action('admin_menu', array($this, 'register_admin_menus'));
        
        // Register AJAX handlers for admin
        add_action('wp_ajax_vortex_get_reward_distribution', array($this, 'ajax_get_reward_distribution'));
        add_action('wp_ajax_vortex_export_rewards_data', array($this, 'ajax_export_rewards_data'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Register admin menu items
     */
    public function register_admin_menus() {
        add_menu_page(
            'VORTEX DAO',
            'VORTEX DAO',
            'manage_options',
            'vortex-dao',
            array($this, 'render_main_dashboard'),
            'dashicons-chart-area',
            30
        );
        
        add_submenu_page(
            'vortex-dao',
            'DAO Dashboard',
            'Dashboard',
            'manage_options',
            'vortex-dao',
            array($this, 'render_main_dashboard')
        );
        
        add_submenu_page(
            'vortex-dao',
            'Reward Settings',
            'Reward Settings',
            'manage_options',
            'vortex-dao-rewards',
            array($this, 'render_rewards_settings')
        );
        
        add_submenu_page(
            'vortex-dao',
            'Rewards Log',
            'Rewards Log',
            'manage_options',
            'vortex-dao-rewards-log',
            array($this, 'render_rewards_log')
        );
        
        add_submenu_page(
            'vortex-dao',
            'Governance',
            'Governance',
            'manage_options',
            'vortex-dao-governance',
            array($this, 'render_governance_dashboard')
        );
        
        add_submenu_page(
            'vortex-dao',
            'Token Management',
            'Token Management',
            'manage_options',
            'vortex-dao-token',
            array($this, 'render_token_management')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin's pages
        if (strpos($hook, 'vortex-dao') === false) {
            return;
        }
        
        // Enqueue Chart.js for analytics
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js',
            array(),
            '3.7.0',
            true
        );
        
        // Enqueue our admin scripts and styles
        wp_enqueue_style(
            'vortex-dao-admin',
            VORTEX_PLUGIN_URL . 'assets/css/vortex-dao-admin.css',
            array(),
            VORTEX_VERSION
        );
        
        wp_enqueue_script(
            'vortex-dao-admin',
            VORTEX_PLUGIN_URL . 'assets/js/vortex-dao-admin.js',
            array('jquery', 'chartjs'),
            VORTEX_VERSION,
            true
        );
        
        wp_localize_script('vortex-dao-admin', 'vortex_admin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_admin_nonce')
        ));
    }
    
    /**
     * Render main dashboard
     */
    public function render_main_dashboard() {
        include(VORTEX_PLUGIN_DIR . 'admin/partials/vortex-dao-dashboard.php');
    }
    
    /**
     * Render rewards settings
     */
    public function render_rewards_settings() {
        include(VORTEX_PLUGIN_DIR . 'admin/partials/vortex-dao-rewards-settings.php');
    }
    
    /**
     * Render rewards log
     */
    public function render_rewards_log() {
        include(VORTEX_PLUGIN_DIR . 'admin/partials/vortex-dao-rewards-log.php');
    }
    
    /**
     * Render governance dashboard
     */
    public function render_governance_dashboard() {
        include(VORTEX_PLUGIN_DIR . 'admin/partials/vortex-dao-governance-dashboard.php');
    }
    
    /**
     * Render token management
     */
    public function render_token_management() {
        include(VORTEX_PLUGIN_DIR . 'admin/partials/vortex-dao-token-management.php');
    }
    
    /**
     * AJAX: Get reward distribution data for charts
     */
    public function ajax_get_reward_distribution() {
        // Verify nonce
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        // Check if user has permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to access this data.'));
            return;
        }
        
        global $wpdb;
        
        // Get distribution by reward type
        $reward_types = $wpdb->get_results("
            SELECT reward_type, SUM(amount) as total_amount
            FROM {$wpdb->prefix}vortex_dao_rewards
            GROUP BY reward_type
            ORDER BY total_amount DESC
        ");
        
        $labels = array();
        $values = array();
        
        // Format labels for display
        $type_labels = array(
            'sale' => 'Artwork Sales',
            'purchase' => 'Purchases',
            'promotion' => 'Tier Promotions',
            'governance' => 'Governance',
            'referral' => 'Referrals',
            'engagement' => 'Engagement',
            'listing' => 'Artwork Listings',
            'bonus' => 'Bonuses'
        );
        
        foreach ($reward_types as $type) {
            $label = isset($type_labels[$type->reward_type]) ? $type_labels[$type->reward_type] : ucfirst($type->reward_type);
            $labels[] = $label;
            $values[] = floatval($type->total_amount);
        }
        
        wp_send_json_success(array(
            'labels' => $labels,
            'values' => $values
        ));
    }
    
    /**
     * AJAX: Export rewards data
     */
    public function ajax_export_rewards_data() {
        // Verify nonce
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        // Check if user has permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to export this data.'));
            return;
        }
        
        // Get export type
        $export_type = isset($_POST['export_type']) ? sanitize_text_field($_POST['export_type']) : 'all';
        
        // Load metrics class
        $metrics = VORTEX_DAO_Metrics::get_instance();
        
        // Get CSV data
        $csv = $metrics->export_metrics_as_csv($export_type);
        
        // Return CSV data
        wp_send_json_success(array(
            'filename' => $csv['filename'],
            'content' => $csv['content']
        ));
    }
}

// Initialize Admin class
$vortex_dao_admin = VORTEX_DAO_Admin::get_instance(); 