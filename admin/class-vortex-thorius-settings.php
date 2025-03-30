<?php
/**
 * Thorius Admin Settings
 * 
 * Manages the admin settings interface for Thorius AI
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Admin Settings
 */
class Vortex_Thorius_Settings {
    
    /**
     * Initialize settings
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_menu_page(
            __('Thorius AI', 'vortex-ai-marketplace'),
            __('Thorius AI', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-thorius',
            array($this, 'render_settings_page'),
            'dashicons-superhero',
            30
        );
        
        add_submenu_page(
            'vortex-thorius',
            __('Settings', 'vortex-ai-marketplace'),
            __('Settings', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-thorius',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'vortex-thorius',
            __('Analytics', 'vortex-ai-marketplace'),
            __('Analytics', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-thorius-analytics',
            array($this, 'render_analytics_page')
        );
        
        add_submenu_page(
            'vortex-thorius',
            __('License', 'vortex-ai-marketplace'),
            __('License', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-thorius-license',
            array($this, 'render_license_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // API Settings
        register_setting('vortex_thorius_api_settings', 'vortex_thorius_openai_key');
        register_setting('vortex_thorius_api_settings', 'vortex_thorius_stability_key');
        
        // General Settings
        register_setting('vortex_thorius_general_settings', 'vortex_thorius_default_position');
        register_setting('vortex_thorius_general_settings', 'vortex_thorius_default_theme');
        register_setting('vortex_thorius_general_settings', 'vortex_thorius_default_tab');
    }
    
    // Additional settings methods...
} 