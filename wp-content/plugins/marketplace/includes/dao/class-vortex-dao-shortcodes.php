<?php
/**
 * VORTEX DAO Shortcodes
 * 
 * Provides shortcodes for displaying DAO functionality on the frontend.
 *
 * @package VORTEX
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_DAO_Shortcodes {
    
    /**
     * Instance of this class
     * @var VORTEX_DAO_Shortcodes
     */
    private static $instance = null;
    
    /**
     * DAO investment instance
     * @var VORTEX_DAO_Investment
     */
    private $investment;
    
    /**
     * DAO config
     * @var array
     */
    private $config;
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize investment class
        $this->investment = VORTEX_DAO_Investment::get_instance();
        
        // Get DAO config
        $this->config = $this->get_dao_config();
        
        // Register shortcodes
        add_shortcode('vortex_investor_application', array($this, 'investor_application_shortcode'));
        add_shortcode('vortex_investor_dashboard', array($this, 'investor_dashboard_shortcode'));
        
        // Enqueue required assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Get instance of this class
     * @return VORTEX_DAO_Shortcodes
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Enqueue required assets for shortcodes
     */
    public function enqueue_assets() {
        global $post;
        
        // Only enqueue if shortcode is present in the content
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'vortex_investor_application') || 
            has_shortcode($post->post_content, 'vortex_investor_dashboard')
        )) {
            // Enqueue application styles
            wp_enqueue_style(
                'vortex-investor-application', 
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/vortex-investor-application.css', 
                array(), 
                VORTEX_MARKETPLACE_VERSION
            );
            
            // Enqueue dashboard styles
            wp_enqueue_style(
                'vortex-investor-dashboard', 
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/vortex-investor-dashboard.css', 
                array(), 
                VORTEX_MARKETPLACE_VERSION
            );
            
            // Enqueue frontend marketplace script (for wallet connection)
            wp_enqueue_script(
                'marketplace-frontend', 
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/marketplace-frontend.js', 
                array('jquery'), 
                VORTEX_MARKETPLACE_VERSION, 
                true
            );
            
            // Localize script with necessary data
            wp_localize_script(
                'marketplace-frontend',
                'vortex_marketplace_vars',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('vortex_marketplace_nonce'),
                    'site_url' => get_site_url(),
                    'plugin_url' => plugin_dir_url(dirname(dirname(__FILE__))),
                )
            );
        }
    }
    
    /**
     * Get DAO configuration
     * @return array DAO config
     */
    private function get_dao_config() {
        // Default values
        $config = array(
            'min_investment' => 1000,
            'token_price' => 0.10,
            'investor_vesting_months' => 24,
            'investor_cliff_months' => 6,
            'investor_vote_multiplier' => 1,
            'founder_vote_multiplier' => 5,
            'team_vote_multiplier' => 3,
            'governance_threshold' => 500000,
            'governance_phase' => 'Transition',
            'revenue_investor_allocation' => 30,
            'liquidation_preference' => 1.5,
            'investor_pro_rata_rights' => true,
            'anti_dilution_protection' => true,
            'investment_round_cap' => 1000000,
            'investment_tranches' => array(
                array(
                    'amount' => 250000,
                    'milestone' => 'Initial Investment',
                    'equity' => 2500000
                ),
                array(
                    'amount' => 750000,
                    'milestone' => 'Product Launch',
                    'equity' => 7500000
                )
            ),
        );
        
        // Allow config to be filtered
        return apply_filters('vortex_dao_config', $config);
    }
    
    /**
     * Investor application shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function investor_application_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'title' => __('Investor Application', 'vortex'),
        ), $atts, 'vortex_investor_application');
        
        // Check if application form template exists
        if (!function_exists('vortex_generate_investor_application_form')) {
            require_once dirname(__FILE__) . '/templates/investor-application-form.php';
        }
        
        // Generate the application form
        return vortex_generate_investor_application_form($this->config);
    }
    
    /**
     * Investor dashboard shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function investor_dashboard_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'title' => __('Investor Dashboard', 'vortex'),
        ), $atts, 'vortex_investor_dashboard');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="vortex-login-required-message marketplace-frontend-wrapper">
                <p>' . __('You must be logged in to view the investor dashboard.', 'vortex') . '</p>
                <a href="' . wp_login_url(get_permalink()) . '" class="vortex-btn">' . __('Log In', 'vortex') . '</a>
            </div>';
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Check if user is an investor
        $investor_data = $this->investment->get_investor_data($user_id);
        
        if (!$investor_data) {
            // User is not an investor, show application link
            $application_page_id = get_option('vortex_investor_application_page');
            $application_url = $application_page_id ? get_permalink($application_page_id) : '#';
            
            return '<div class="vortex-investor-application-notice marketplace-frontend-wrapper">
                <div class="marketplace-frontend-title">' . esc_html($atts['title']) . '</div>
                <div class="marketplace-frontend-content">
                    <p>' . __('You are not currently registered as an investor.', 'vortex') . '</p>
                    <p>' . __('To become an investor in VORTEX AI Marketplace, please complete our investor application.', 'vortex') . '</p>
                    <a href="' . esc_url($application_url) . '" class="vortex-btn vortex-btn-primary">' . __('Apply to Invest', 'vortex') . '</a>
                </div>
            </div>';
        }
        
        // User is an investor, load the dashboard
        ob_start();
        
        include dirname(__FILE__) . '/../dao/partials/investor-dashboard.php';
        
        return ob_get_clean();
    }
}

// Initialize the shortcodes
VORTEX_DAO_Shortcodes::get_instance(); 