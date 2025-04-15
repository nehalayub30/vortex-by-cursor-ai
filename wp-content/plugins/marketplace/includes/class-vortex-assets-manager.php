<?php
/**
 * VORTEX Assets Manager Class
 * 
 * Manages the registration and enqueuing of all front-end CSS and JavaScript assets
 * for the VORTEX Marketplace plugin.
 * 
 * @package VORTEX
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_Assets_Manager {
    
    /**
     * Instance of this class
     * @var VORTEX_Assets_Manager
     */
    private static $instance = null;
    
    /**
     * Registered scripts
     * @var array
     */
    private $scripts = array();
    
    /**
     * Registered styles
     * @var array
     */
    private $styles = array();
    
    /**
     * Script dependencies mapping
     * @var array
     */
    private $script_dependencies = array(
        'vortex-dao' => array('jquery'),
        'vortex-dao-ai' => array('jquery'),
        'vortex-metrics' => array('jquery', 'wp-i18n'),
        'vortex-governance' => array('jquery'),
        'vortex-wallet-connection' => array('jquery'),
        'vortex-agent-insights' => array('jquery', 'wp-i18n'),
        'vortex-blockchain-metrics' => array('jquery', 'chart-js'),
        'vortex-dao-rewards' => array('jquery'),
        'vortex-solana-wallet' => array('jquery'),
        'vortex-dao-woocommerce' => array('jquery'),
        'real-time-metrics' => array('jquery', 'chart-js'),
        'dashboard-image-generator' => array('jquery'),
        'image-generator' => array('jquery'),
    );
    
    /**
     * Style dependencies mapping
     * @var array
     */
    private $style_dependencies = array(
        'vortex-dao' => array(),
        'vortex-dao-ai' => array('vortex-dao'),
        'vortex-agent-insights' => array('vortex-dao'),
        'vortex-blockchain-metrics' => array('vortex-dao'),
        'vortex-dao-info' => array('vortex-dao'),
        'vortex-dao-rewards' => array('vortex-dao'),
        'vortex-dao-investor' => array('vortex-dao'),
        'vortex-dao-woocommerce' => array('vortex-dao'),
        'dashboard-image-generator' => array(),
        'image-generator-frontend' => array(),
        'image-generator' => array(),
    );
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize hooks
        add_action('wp_enqueue_scripts', array($this, 'register_frontend_assets'), 10);
        add_action('admin_enqueue_scripts', array($this, 'register_admin_assets'), 10);
        
        // Register external libraries
        add_action('wp_enqueue_scripts', array($this, 'register_external_libraries'), 9);
        add_action('admin_enqueue_scripts', array($this, 'register_external_libraries'), 9);
    }
    
    /**
     * Get instance of this class
     * @return VORTEX_Assets_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Register external libraries used by the plugin
     */
    public function register_external_libraries() {
        wp_register_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
            array(),
            '3.7.1',
            true
        );
    }
    
    /**
     * Register all front-end assets
     */
    public function register_frontend_assets() {
        // Register all scripts and styles
        $this->register_scripts();
        $this->register_styles();
    }
    
    /**
     * Register all admin assets
     */
    public function register_admin_assets() {
        // Register all admin scripts and styles
        $this->register_scripts('admin');
        $this->register_styles('admin');
    }
    
    /**
     * Register scripts
     * 
     * @param string $context Context (frontend or admin)
     */
    private function register_scripts($context = 'frontend') {
        $js_dir = VORTEX_MARKETPLACE_PLUGIN_URL . 'assets/js/';
        $version = VORTEX_MARKETPLACE_VERSION;
        
        $scripts = array(
            'vortex-dao' => 'vortex-dao.js',
            'vortex-dao-ai' => 'vortex-dao-ai.js',
            'vortex-metrics' => 'vortex-metrics.js',
            'vortex-governance' => 'vortex-governance.js',
            'vortex-wallet-connection' => 'vortex-wallet-connection.js',
            'vortex-agent-insights' => 'vortex-agent-insights.js',
            'vortex-blockchain-metrics' => 'vortex-blockchain-metrics.js',
            'vortex-dao-rewards' => 'vortex-dao-rewards.js',
            'vortex-solana-wallet' => 'vortex-solana-wallet.js',
            'vortex-dao-woocommerce' => 'vortex-dao-woocommerce.js',
            'real-time-metrics' => 'real-time-metrics.js',
            'dashboard-image-generator' => 'dashboard-image-generator.js',
            'image-generator' => 'image-generator.js'
        );
        
        foreach ($scripts as $handle => $script) {
            $dependencies = isset($this->script_dependencies[$handle]) ? $this->script_dependencies[$handle] : array('jquery');
            
            wp_register_script(
                $handle, 
                $js_dir . $script, 
                $dependencies, 
                $version, 
                true
            );
            
            // Store the registered script
            $this->scripts[$handle] = array(
                'src' => $js_dir . $script,
                'deps' => $dependencies,
                'ver' => $version,
                'in_footer' => true
            );
        }
        
        // Add localization for scripts that need it
        $this->localize_scripts();
    }
    
    /**
     * Register styles
     * 
     * @param string $context Context (frontend or admin)
     */
    private function register_styles($context = 'frontend') {
        $css_dir = VORTEX_MARKETPLACE_PLUGIN_URL . 'assets/css/';
        $version = VORTEX_MARKETPLACE_VERSION;
        
        $styles = array(
            'vortex-dao' => 'vortex-dao.css',
            'vortex-dao-ai' => 'vortex-dao-ai.css',
            'vortex-agent-insights' => 'vortex-agent-insights.css',
            'vortex-blockchain-metrics' => 'vortex-blockchain-metrics.css',
            'vortex-dao-info' => 'vortex-dao-info.css',
            'vortex-dao-rewards' => 'vortex-dao-rewards.css',
            'vortex-dao-investor' => 'vortex-dao-investor.css',
            'vortex-dao-woocommerce' => 'vortex-dao-woocommerce.css',
            'dashboard-image-generator' => 'dashboard-image-generator.css',
            'image-generator-frontend' => 'image-generator-frontend.css',
            'image-generator' => 'image-generator.css'
        );
        
        foreach ($styles as $handle => $style) {
            $dependencies = isset($this->style_dependencies[$handle]) ? $this->style_dependencies[$handle] : array();
            
            wp_register_style(
                $handle, 
                $css_dir . $style, 
                $dependencies, 
                $version
            );
            
            // Store the registered style
            $this->styles[$handle] = array(
                'src' => $css_dir . $style,
                'deps' => $dependencies,
                'ver' => $version,
                'media' => 'all'
            );
        }
    }
    
    /**
     * Localize scripts with necessary data
     */
    private function localize_scripts() {
        // Image Generator Localization
        wp_localize_script('image-generator', 'vortexImageGenerator', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_image_generator_nonce'),
            'i18n' => array(
                'generating' => __('Generating image...', 'vortex-marketplace'),
                'deleting' => __('Deleting...', 'vortex-marketplace'),
                'error' => __('Error:', 'vortex-marketplace'),
                'noPrompt' => __('Please enter a prompt.', 'vortex-marketplace'),
                'downloadImage' => __('Download Image', 'vortex-marketplace'),
                'confirmDelete' => __('Are you sure you want to delete this image?', 'vortex-marketplace')
            )
        ));
        
        // DAO Scripts Localization
        wp_localize_script('vortex-dao', 'vortexDAOData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_dao_nonce'),
            'i18n' => array(
                'loading' => __('Loading...', 'vortex-marketplace'),
                'error' => __('Error:', 'vortex-marketplace'),
                'success' => __('Success!', 'vortex-marketplace')
            )
        ));
        
        // Wallet Connection Localization
        wp_localize_script('vortex-wallet-connection', 'vortexWalletData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_wallet_nonce'),
            'solanaProvider' => get_option('vortex_solana_provider', 'https://api.mainnet-beta.solana.com')
        ));
        
        // Real-time Metrics Localization
        wp_localize_script('real-time-metrics', 'vortexMetricsData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_metrics_nonce'),
            'refreshInterval' => apply_filters('vortex_metrics_refresh_interval', 30000) // 30 seconds
        ));
    }
    
    /**
     * Enqueue a specific script
     * 
     * @param string $handle Script handle
     */
    public function enqueue_script($handle) {
        if (isset($this->scripts[$handle])) {
            wp_enqueue_script($handle);
            
            // Also enqueue any associated localization data
            $this->maybe_localize_script($handle);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Enqueue a specific style
     * 
     * @param string $handle Style handle
     */
    public function enqueue_style($handle) {
        if (isset($this->styles[$handle])) {
            wp_enqueue_style($handle);
            return true;
        }
        
        return false;
    }
    
    /**
     * Localize a script if it hasn't been done already
     * 
     * @param string $handle Script handle
     */
    private function maybe_localize_script($handle) {
        // The localization is actually done in the localize_scripts method
        // This is just a placeholder in case we want to add dynamic localization later
        return true;
    }
    
    /**
     * Enqueue image generator front-end assets
     */
    public function enqueue_image_generator_assets() {
        $this->enqueue_style('image-generator-frontend');
        $this->enqueue_script('image-generator');
    }
    
    /**
     * Enqueue DAO front-end assets
     */
    public function enqueue_dao_assets() {
        $this->enqueue_style('vortex-dao');
        $this->enqueue_script('vortex-dao');
        $this->enqueue_script('vortex-wallet-connection');
    }
    
    /**
     * Enqueue blockchain metrics assets
     */
    public function enqueue_blockchain_metrics_assets() {
        $this->enqueue_style('vortex-blockchain-metrics');
        $this->enqueue_script('vortex-blockchain-metrics');
    }
    
    /**
     * Enqueue real-time metrics assets
     */
    public function enqueue_realtime_metrics_assets() {
        $this->enqueue_script('real-time-metrics');
    }
    
    /**
     * Enqueue AI insights assets
     */
    public function enqueue_ai_insights_assets() {
        $this->enqueue_style('vortex-agent-insights');
        $this->enqueue_script('vortex-agent-insights');
    }
}

// Initialize the assets manager
function vortex_assets_manager() {
    return VORTEX_Assets_Manager::get_instance();
}

// Start the assets manager
vortex_assets_manager(); 