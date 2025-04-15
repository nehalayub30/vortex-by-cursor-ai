<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * The admin-specific functionality of the plugin.
 */
class VORTEX_Admin {
    
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        // Admin hooks
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Register the admin menu.
     */
    public function register_admin_menu() {
        // Main menu
        add_menu_page(
            __('VORTEX AI', 'vortex-ai-marketplace'),
            __('VORTEX AI', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-ai-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-welcome-widgets-menus',
            30
        );
        
        // Dashboard
        add_submenu_page(
            'vortex-ai-dashboard',
            __('Dashboard', 'vortex-ai-marketplace'),
            __('Dashboard', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-ai-dashboard',
            array($this, 'render_dashboard_page')
        );
        
        // AI Agents
        add_submenu_page(
            'vortex-ai-dashboard',
            __('AI Agents', 'vortex-ai-marketplace'),
            __('AI Agents', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-ai-agents',
            array($this, 'render_agents_page')
        );
        
        // Settings
        add_submenu_page(
            'vortex-ai-dashboard',
            __('Settings', 'vortex-ai-marketplace'),
            __('Settings', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-ai-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_scripts($hook) {
        // Only enqueue on our plugin pages
        if (strpos($hook, 'vortex-ai') === false) {
            return;
        }
        
        // Styles
        wp_enqueue_style(
            'vortex-ai-admin',
            VORTEX_AI_MARKETPLACE_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            VORTEX_AI_MARKETPLACE_VERSION
        );
        
        // Scripts
        wp_enqueue_script(
            'vortex-ai-admin',
            VORTEX_AI_MARKETPLACE_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            VORTEX_AI_MARKETPLACE_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script(
            'vortex-ai-admin',
            'vortex_ai_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vortex_ai_admin_nonce'),
                'i18n' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'vortex-ai-marketplace'),
                    'saving' => __('Saving...', 'vortex-ai-marketplace'),
                    'saved' => __('Saved!', 'vortex-ai-marketplace'),
                    'error' => __('An error occurred.', 'vortex-ai-marketplace')
                )
            )
        );
    }
    
    /**
     * Render dashboard page.
     */
    public function render_dashboard_page() {
        include VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'admin/partials/dashboard-page.php';
    }
    
    /**
     * Render agents page.
     */
    public function render_agents_page() {
        include VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'admin/partials/agents-page.php';
    }
    
    /**
     * Render settings page.
     */
    public function render_settings_page() {
        include VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }
} 