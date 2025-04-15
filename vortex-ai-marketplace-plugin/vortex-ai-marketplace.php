<?php
/**
 * Plugin Name: VORTEX AI Marketplace
 * Plugin URI: https://vortexmarketplace.io
 * Description: Advanced AI-powered marketplace solutions with commercialization features
 * Version: 1.0.0
 * Author: VORTEX Team
 * Author URI: https://vortexmarketplace.io
 * Text Domain: vortex-ai-marketplace
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * 
 * @package VORTEX_AI_Marketplace
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VORTEX_AI_MARKETPLACE_VERSION', '1.0.0');
define('VORTEX_AI_MARKETPLACE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VORTEX_AI_MARKETPLACE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VORTEX_AI_MARKETPLACE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The core plugin class
 */
class VORTEX_AI_Marketplace {
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * License key option name
     */
    private $license_key_option = 'vortex_ai_marketplace_license_key';

    /**
     * License status option name
     */
    private $license_status_option = 'vortex_ai_marketplace_license_status';

    /**
     * Get a single instance of this class
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
        // Load required files
        $this->load_dependencies();

        // Register hooks
        $this->register_hooks();

        // Initialize commercialization
        $this->init_commercialization();
    }

    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Commercial features
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/commercial/class-vortex-license-manager.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/commercial/class-vortex-license-api.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/commercial/class-vortex-update-manager.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/commercial/class-vortex-subscription-manager.php';
        
        // Admin
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'admin/class-vortex-admin.php';
    }

    /**
     * Register hooks
     */
    private function register_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Plugin update hooks
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_license_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'license_notices'));
        
        // AJAX actions
        add_action('wp_ajax_vortex_activate_license', array($this, 'ajax_activate_license'));
        add_action('wp_ajax_vortex_deactivate_license', array($this, 'ajax_deactivate_license'));
        
        // Plugin row meta
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
    }

    /**
     * Initialize commercialization components
     */
    private function init_commercialization() {
        // Initialize license manager
        $license_manager = new VORTEX_License_Manager(
            $this->license_key_option,
            $this->license_status_option,
            'vortex-ai-marketplace',
            VORTEX_AI_MARKETPLACE_VERSION,
            'VORTEX AI Marketplace'
        );
        
        // Check license status
        if ('valid' !== get_option($this->license_status_option)) {
            // Disable premium features if license is invalid
            add_filter('vortex_ai_marketplace_features_enabled', array($this, 'disable_premium_features'));
        }
        
        // Initialize update manager
        $update_manager = new VORTEX_Update_Manager(
            'vortex-ai-marketplace',
            VORTEX_AI_MARKETPLACE_VERSION,
            get_option($this->license_key_option)
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create necessary database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create license management table
        $table_name = $wpdb->prefix . 'vortex_license_data';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            license_key varchar(255) NOT NULL,
            status varchar(50) NOT NULL,
            expiration_date datetime NOT NULL,
            site_url varchar(255) NOT NULL,
            activation_date datetime NOT NULL,
            last_check datetime NOT NULL,
            features text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Set default options
     */
    private function set_default_options() {
        // Default feature settings
        $default_features = array(
            'ai_agents' => true,
            'blockchain_integration' => false,
            'advanced_analytics' => false,
            'premium_templates' => false,
            'api_access' => false
        );
        
        update_option('vortex_ai_marketplace_features', $default_features);
        
        // Default license settings
        if (!get_option($this->license_key_option)) {
            update_option($this->license_key_option, '');
        }
        
        if (!get_option($this->license_status_option)) {
            update_option($this->license_status_option, 'invalid');
        }
    }

    /**
     * Check for updates
     */
    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $license_key = get_option($this->license_key_option);
        if (empty($license_key)) {
            return $transient;
        }
        
        // Check with our update server
        $update_manager = new VORTEX_Update_Manager(
            'vortex-ai-marketplace',
            VORTEX_AI_MARKETPLACE_VERSION,
            $license_key
        );
        
        $update_data = $update_manager->check_for_updates();
        
        if (!empty($update_data) && version_compare(VORTEX_AI_MARKETPLACE_VERSION, $update_data->new_version, '<')) {
            $transient->response[VORTEX_AI_MARKETPLACE_PLUGIN_BASENAME] = $update_data;
        }
        
        return $transient;
    }

    /**
     * Plugin info for the update process
     */
    public function plugin_info($result, $action, $args) {
        // Check if this is our plugin
        if ('plugin_information' !== $action || !isset($args->slug) || 'vortex-ai-marketplace' !== $args->slug) {
            return $result;
        }
        
        $license_key = get_option($this->license_key_option);
        if (empty($license_key)) {
            return $result;
        }
        
        // Get plugin info from our API
        $update_manager = new VORTEX_Update_Manager(
            'vortex-ai-marketplace',
            VORTEX_AI_MARKETPLACE_VERSION,
            $license_key
        );
        
        $plugin_info = $update_manager->get_plugin_info();
        
        if (!empty($plugin_info)) {
            return $plugin_info;
        }
        
        return $result;
    }

    /**
     * Add license menu
     */
    public function add_license_menu() {
        add_options_page(
            __('VORTEX AI Marketplace License', 'vortex-ai-marketplace'),
            __('VORTEX License', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-license',
            array($this, 'license_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('vortex_license_group', $this->license_key_option);
    }

    /**
     * License page
     */
    public function license_page() {
        include VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'admin/partials/license-page.php';
    }

    /**
     * Display license notices
     */
    public function license_notices() {
        $license_status = get_option($this->license_status_option);
        
        if ('valid' !== $license_status && is_admin() && current_user_can('manage_options')) {
            ?>
            <div class="notice notice-warning">
                <p><?php _e('VORTEX AI Marketplace license is not active. <a href="options-general.php?page=vortex-license">Activate your license</a> to receive updates and access premium features.', 'vortex-ai-marketplace'); ?></p>
            </div>
            <?php
        }
    }

    /**
     * AJAX license activation
     */
    public function ajax_activate_license() {
        // Check for permissions and nonce
        if (!current_user_can('manage_options') || !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_license_nonce')) {
            wp_send_json_error(__('Security check failed.', 'vortex-ai-marketplace'));
        }
        
        $license_key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
        
        if (empty($license_key)) {
            wp_send_json_error(__('Please enter a license key.', 'vortex-ai-marketplace'));
        }
        
        // Activate license
        $license_manager = new VORTEX_License_Manager(
            $this->license_key_option,
            $this->license_status_option,
            'vortex-ai-marketplace',
            VORTEX_AI_MARKETPLACE_VERSION,
            'VORTEX AI Marketplace'
        );
        
        $activation_response = $license_manager->activate_license($license_key);
        
        if ($activation_response['success']) {
            wp_send_json_success($activation_response['message']);
        } else {
            wp_send_json_error($activation_response['message']);
        }
    }

    /**
     * AJAX license deactivation
     */
    public function ajax_deactivate_license() {
        // Check for permissions and nonce
        if (!current_user_can('manage_options') || !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_license_nonce')) {
            wp_send_json_error(__('Security check failed.', 'vortex-ai-marketplace'));
        }
        
        $license_key = get_option($this->license_key_option);
        
        if (empty($license_key)) {
            wp_send_json_error(__('No active license found.', 'vortex-ai-marketplace'));
        }
        
        // Deactivate license
        $license_manager = new VORTEX_License_Manager(
            $this->license_key_option,
            $this->license_status_option,
            'vortex-ai-marketplace',
            VORTEX_AI_MARKETPLACE_VERSION,
            'VORTEX AI Marketplace'
        );
        
        $deactivation_response = $license_manager->deactivate_license();
        
        if ($deactivation_response['success']) {
            wp_send_json_success($deactivation_response['message']);
        } else {
            wp_send_json_error($deactivation_response['message']);
        }
    }

    /**
     * Disable premium features for invalid licenses
     */
    public function disable_premium_features($features) {
        foreach ($features as $key => $enabled) {
            if ('ai_agents' !== $key) {
                $features[$key] = false;
            }
        }
        
        return $features;
    }

    /**
     * Add custom links to plugin row meta
     */
    public function plugin_row_meta($links, $file) {
        if (VORTEX_AI_MARKETPLACE_PLUGIN_BASENAME === $file) {
            $row_meta = array(
                'license' => '<a href="' . esc_url(admin_url('options-general.php?page=vortex-license')) . '">' . __('License', 'vortex-ai-marketplace') . '</a>',
                'docs'    => '<a href="https://vortexmarketplace.io/docs/" target="_blank">' . __('Documentation', 'vortex-ai-marketplace') . '</a>',
                'support' => '<a href="https://vortexmarketplace.io/support/" target="_blank">' . __('Support', 'vortex-ai-marketplace') . '</a>',
            );
            
            return array_merge($links, $row_meta);
        }
        
        return $links;
    }
}

// Initialize the plugin
function vortex_ai_marketplace() {
    return VORTEX_AI_Marketplace::get_instance();
}

// Start the plugin
vortex_ai_marketplace(); 