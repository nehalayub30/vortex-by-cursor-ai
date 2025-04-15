<?php
/**
 * Plugin Name: VORTEX AI Marketplace
 * Plugin URI: https://vortexai.com/marketplace
 * Description: VORTEX AI Marketplace plugin for managing digital assets, investments, and tokens on Solana blockchain.
 * Version: 1.0.0
 * Author: VORTEX AI
 * Author URI: https://vortexai.com
 * Text Domain: vortex
 * Domain Path: /languages
 *
 * @package VORTEX
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VORTEX_MARKETPLACE_VERSION', '1.0.0');
define('VORTEX_MARKETPLACE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VORTEX_MARKETPLACE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VORTEX_MARKETPLACE_PLUGIN_FILE', __FILE__);
define('VORTEX_MARKETPLACE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Plugin activation/deactivation hooks
register_activation_hook(__FILE__, 'vortex_marketplace_activate');
register_deactivation_hook(__FILE__, 'vortex_marketplace_deactivate');

/**
 * Plugin activation function
 */
function vortex_marketplace_activate() {
    // Activation tasks
    do_action('vortex_marketplace_activated');
}

/**
 * Plugin deactivation function
 */
function vortex_marketplace_deactivate() {
    // Deactivation tasks
    do_action('vortex_marketplace_deactivated');
}

/**
 * Main marketplace class
 */
final class VORTEX_Marketplace {
    /**
     * Instance of this class
     * @var VORTEX_Marketplace
     */
    private static $instance = null;
    
    /**
     * Admin instance
     * @var object
     */
    public $admin = null;
    
    /**
     * Frontend instance
     * @var object
     */
    public $frontend = null;
    
    /**
     * DAO Core instance
     * @var object
     */
    public $dao = null;
    
    /**
     * Token instance
     * @var object
     */
    public $token = null;
    
    /**
     * Investment instance
     * @var object
     */
    public $investment = null;
    
    /**
     * API instance
     * @var object
     */
    public $api = null;
    
    /**
     * Cache Manager instance
     * @var object
     */
    public $cache_manager = null;
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize plugin components
        add_action('plugins_loaded', array($this, 'init_plugin'));
    }
    
    /**
     * Get instance of this class
     * @return VORTEX_Marketplace
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize the plugin
     */
    public function init_plugin() {
        // Load plugin textdomain
        load_plugin_textdomain('vortex', false, basename(dirname(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_components();
        
        // Add plugin action links
        add_filter('plugin_action_links_' . VORTEX_MARKETPLACE_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
        
        // Init hooks for plugin
        $this->init_hooks();
        
        // Signal that plugin is fully loaded
        do_action('vortex_marketplace_loaded');
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core includes
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-marketplace-admin.php';
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-marketplace-frontend.php';
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-cache-manager.php';
        
        // DAO components
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/dao/class-vortex-dao-core.php';
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/dao/class-vortex-dao-token.php';
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/dao/class-vortex-dao-investment.php';
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/dao/class-vortex-solana-api.php';
        
        // API components
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-marketplace-api.php';
        
        // Helper functions
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/marketplace-functions.php';
        
        // Allow other plugins to include files
        do_action('vortex_marketplace_includes');
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize admin
        $this->admin = VORTEX_Marketplace_Admin::get_instance();
        
        // Initialize frontend
        $this->frontend = VORTEX_Marketplace_Frontend::get_instance();
        
        // Initialize Cache Manager
        $this->cache_manager = VORTEX_Cache_Manager::get_instance();
        
        // Initialize DAO Core
        $this->dao = VORTEX_DAO_Core::get_instance();
        
        // Initialize Token
        $this->token = VORTEX_DAO_Token::get_instance();
        
        // Initialize Investment
        $this->investment = VORTEX_DAO_Investment::get_instance();
        
        // Initialize API
        $this->api = VORTEX_Marketplace_API::get_instance();
        
        // Allow other plugins to init components
        do_action('vortex_marketplace_init_components', $this);
    }
    
    /**
     * Init hooks
     */
    private function init_hooks() {
        // Register the marketplace REST API namespace
        add_action('rest_api_init', array($this, 'register_rest_namespace'));
        
        // Plugin lifecycle hooks
        add_action('vortex_marketplace_activated', array($this, 'plugin_activated'));
        add_action('vortex_marketplace_deactivated', array($this, 'plugin_deactivated'));
        
        // Register plugin styles and scripts
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // Allow other plugins to add hooks
        do_action('vortex_marketplace_init_hooks', $this);
    }
    
    /**
     * Register the Marketplace API namespace
     */
    public function register_rest_namespace() {
        register_rest_namespace('vortex-marketplace', __('VORTEX AI Marketplace API', 'vortex'));
    }
    
    /**
     * Admin scripts
     */
    public function admin_scripts() {
        wp_register_style('vortex-admin', VORTEX_MARKETPLACE_PLUGIN_URL . 'assets/css/admin.css', array(), VORTEX_MARKETPLACE_VERSION);
        wp_enqueue_style('vortex-admin');
        
        // Admin JS
        wp_register_script('vortex-admin', VORTEX_MARKETPLACE_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), VORTEX_MARKETPLACE_VERSION, true);
        wp_localize_script('vortex-admin', 'vortex_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_admin_nonce'),
            'rest_url' => rest_url('vortex-marketplace/v1'),
            'rest_nonce' => wp_create_nonce('wp_rest'),
        ));
        wp_enqueue_script('vortex-admin');
        
        // Allow plugins to register admin scripts
        do_action('vortex_marketplace_admin_scripts');
    }
    
    /**
     * Plugin activated
     */
    public function plugin_activated() {
        // Create plugin roles and capabilities
        $this->create_roles();
        
        // Create plugin database tables
        $this->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivated
     */
    public function plugin_deactivated() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create plugin roles and capabilities
     */
    private function create_roles() {
        // Create investor role
        add_role('vortex_investor', __('VORTEX Investor', 'vortex'), array(
            'read' => true,
        ));
        
        // Add investor capabilities to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('vortex_investor_capability');
            $admin_role->add_cap('vortex_manage_marketplace');
        }
        
        // Allow plugins to create roles and capabilities
        do_action('vortex_marketplace_create_roles');
    }
    
    /**
     * Create plugin database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Investors table
        $table_name = $wpdb->prefix . 'vortex_investors';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            investment_amount decimal(15,2) NOT NULL DEFAULT 0,
            tokens_allocated bigint(20) NOT NULL DEFAULT 0,
            solana_wallet varchar(255) DEFAULT '',
            ethereum_wallet varchar(255) DEFAULT '',
            vesting_start datetime DEFAULT NULL,
            application_status varchar(50) DEFAULT 'pending',
            agreement_signed tinyint(1) DEFAULT 0,
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Token transactions table
        $table_name = $wpdb->prefix . 'vortex_token_transactions';
        $sql .= "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            transaction_type varchar(50) NOT NULL,
            amount bigint(20) NOT NULL,
            transaction_hash varchar(255) DEFAULT '',
            status varchar(50) DEFAULT 'pending',
            transaction_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Execute table creation
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        // Allow plugins to create tables
        do_action('vortex_marketplace_create_tables');
    }
    
    /**
     * Add plugin action links
     *
     * @param array $links Action links
     * @return array
     */
    public function plugin_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=vortex-marketplace') . '">' . __('Settings', 'vortex') . '</a>',
            '<a href="' . admin_url('admin.php?page=vortex-marketplace-docs') . '">' . __('Documentation', 'vortex') . '</a>',
        );
        
        // Allow plugins to add action links
        $plugin_links = apply_filters('vortex_marketplace_plugin_action_links', $plugin_links);
        
        return array_merge($plugin_links, $links);
    }
    
    /**
     * Get plugin option
     *
     * @param string $key Option key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get_option($key, $default = false) {
        $options = get_option('vortex_marketplace_options', array());
        return isset($options[$key]) ? $options[$key] : $default;
    }
    
    /**
     * Update plugin option
     *
     * @param string $key Option key
     * @param mixed $value Option value
     * @return bool
     */
    public function update_option($key, $value) {
        $options = get_option('vortex_marketplace_options', array());
        $options[$key] = $value;
        return update_option('vortex_marketplace_options', $options);
    }
    
    /**
     * Get plugin path
     *
     * @param string $path Path to append
     * @return string
     */
    public function plugin_path($path = '') {
        return VORTEX_MARKETPLACE_PLUGIN_DIR . ltrim($path, '/');
    }
    
    /**
     * Get plugin URL
     *
     * @param string $path Path to append
     * @return string
     */
    public function plugin_url($path = '') {
        return VORTEX_MARKETPLACE_PLUGIN_URL . ltrim($path, '/');
    }
    
    /**
     * Get available plugin templates
     * 
     * @return array Templates
     */
    public function get_templates() {
        $templates = array(
            'investor-dashboard' => array(
                'label' => __('Investor Dashboard', 'vortex'),
                'path' => 'dao/templates/investor-dashboard.php',
            ),
            'investor-application' => array(
                'label' => __('Investor Application', 'vortex'),
                'path' => 'dao/templates/investor-application.php',
            ),
            'investor-agreement' => array(
                'label' => __('Investor Agreement', 'vortex'),
                'path' => 'dao/templates/investor-agreement-template.php',
            ),
        );
        
        // Allow plugins to add templates
        return apply_filters('vortex_marketplace_templates', $templates);
    }
    
    /**
     * Get template path
     * 
     * @param string $template Template name
     * @return string Template path
     */
    public function get_template_path($template) {
        $templates = $this->get_templates();
        
        if (!isset($templates[$template])) {
            return false;
        }
        
        $template_path = $templates[$template]['path'];
        
        // Check theme directory first
        $theme_path = get_stylesheet_directory() . '/vortex-marketplace/' . $template_path;
        
        if (file_exists($theme_path)) {
            return $theme_path;
        }
        
        // Check plugin directory
        $plugin_path = $this->plugin_path('includes/' . $template_path);
        
        if (file_exists($plugin_path)) {
            return $plugin_path;
        }
        
        return false;
    }
    
    /**
     * Load template
     * 
     * @param string $template Template name
     * @param array $args Template arguments
     * @return void
     */
    public function load_template($template, $args = array()) {
        $template_path = $this->get_template_path($template);
        
        if (!$template_path) {
            return;
        }
        
        // Extract arguments
        if (!empty($args) && is_array($args)) {
            extract($args);
        }
        
        // Allow plugins to modify template arguments
        $args = apply_filters('vortex_marketplace_template_args', $args, $template);
        
        // Include template file
        include $template_path;
    }
}

/**
 * Get marketplace instance
 * 
 * @return VORTEX_Marketplace
 */
function VORTEX_Marketplace() {
    return VORTEX_Marketplace::get_instance();
}

// Initialize the plugin
VORTEX_Marketplace();

/**
 * Helper functions for plugins
 */

/**
 * Register a marketplace extension
 * 
 * @param string $extension_id Extension ID
 * @param array $args Extension arguments
 * @return void
 */
function vortex_register_extension($extension_id, $args = array()) {
    do_action('vortex_marketplace_register_extension', $extension_id, $args);
}

/**
 * Get marketplace extension
 * 
 * @param string $extension_id Extension ID
 * @return array|false Extension data or false if not found
 */
function vortex_get_extension($extension_id) {
    return apply_filters('vortex_marketplace_get_extension', false, $extension_id);
}

/**
 * Get registered marketplace extensions
 * 
 * @return array Extensions
 */
function vortex_get_extensions() {
    return apply_filters('vortex_marketplace_extensions', array());
}

/**
 * Check if marketplace extension is active
 * 
 * @param string $extension_id Extension ID
 * @return bool
 */
function vortex_is_extension_active($extension_id) {
    return apply_filters('vortex_marketplace_is_extension_active', false, $extension_id);
}

/**
 * Register a marketplace template
 * 
 * @param string $template_id Template ID
 * @param array $args Template arguments
 * @return void
 */
function vortex_register_template($template_id, $args = array()) {
    add_filter('vortex_marketplace_templates', function($templates) use ($template_id, $args) {
        $templates[$template_id] = $args;
        return $templates;
    });
}

/**
 * Register a marketplace investor field
 * 
 * @param string $field_id Field ID
 * @param array $args Field arguments
 * @return void
 */
function vortex_register_investor_field($field_id, $args = array()) {
    add_filter('vortex_marketplace_investor_fields', function($fields) use ($field_id, $args) {
        $fields[$field_id] = $args;
        return $fields;
    });
}

/**
 * Get marketplace investor fields
 * 
 * @return array Fields
 */
function vortex_get_investor_fields() {
    return apply_filters('vortex_marketplace_investor_fields', array());
}

/**
 * Register a marketplace REST API endpoint
 * 
 * @param string $route Route
 * @param array $args Arguments
 * @return void
 */
function vortex_register_api_endpoint($route, $args = array()) {
    add_action('rest_api_init', function() use ($route, $args) {
        register_rest_route('vortex-marketplace/v1', $route, $args);
    });
}

// ... rest of existing code ... 