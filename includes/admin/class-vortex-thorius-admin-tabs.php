<?php
/**
 * Thorius Admin Tabs
 * 
 * Handles the admin tab interface for managing Thorius agents
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Admin Tabs
 */
class Vortex_Thorius_Admin_Tabs {
    /**
     * Orchestrator instance
     */
    private $orchestrator;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->orchestrator = new Vortex_Thorius_Orchestrator();
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register admin assets
        add_action('admin_enqueue_scripts', array($this, 'register_admin_assets'));
        
        // Register AJAX handlers
        add_action('wp_ajax_thorius_save_agent_settings', array($this, 'ajax_save_agent_settings'));
        add_action('wp_ajax_thorius_save_tab_state', array($this, 'ajax_save_tab_state'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Vortex AI Agents', 'vortex-ai-marketplace'),
            __('Vortex AI', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-ai-marketplace',
            array($this, 'render_admin_page'),
            'dashicons-superhero',
            30
        );
        
        add_submenu_page(
            'vortex-ai-marketplace',
            __('AI Agents', 'vortex-ai-marketplace'),
            __('AI Agents', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-ai-marketplace',
            array($this, 'render_admin_page')
        );
        
        add_submenu_page(
            'vortex-ai-marketplace',
            __('Analytics', 'vortex-ai-marketplace'),
            __('Analytics', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-ai-analytics',
            array($this, 'render_analytics_page')
        );
        
        add_submenu_page(
            'vortex-ai-marketplace',
            __('Settings', 'vortex-ai-marketplace'),
            __('Settings', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-ai-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register admin assets
     */
    public function register_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'vortex-ai') === false) {
            return;
        }
        
        // Styles
        wp_enqueue_style(
            'thorius-admin-tabs',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/thorius-admin.css',
            array(),
            '1.0.0'
        );
        
        // Scripts
        wp_enqueue_script(
            'thorius-admin-tabs',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/thorius-admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('thorius-admin-tabs', 'thorius_admin_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('thorius_admin_nonce'),
            'is_logged_in' => is_user_logged_in()
        ));
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $agent_tabs = $this->orchestrator->get_agent_tabs();
        $current_tab = $this->get_current_agent_tab();
        
        echo '<div class="wrap thorius-admin">';
        echo '<h1>' . __('Vortex AI Agents', 'vortex-ai-marketplace') . '</h1>';
        
        echo '<div id="thorius-agent-tabs" class="thorius-tabs" data-persistent="true">';
        
        // Render tab navigation
        echo '<nav class="thorius-tab-nav nav-tab-wrapper">';
        foreach ($agent_tabs as $agent_id => $agent_data) {
            $active_class = ($current_tab === $agent_id) ? 'nav-tab-active' : '';
            echo '<a href="#" class="thorius-tab-button nav-tab ' . esc_attr($active_class) . '" data-tab="' . esc_attr($agent_id) . '">' . esc_html($agent_data['title']) . '</a>';
        }
        echo '</nav>';
        
        // Render tab contents
        echo '<div class="thorius-tab-content-wrapper">';
        
        foreach ($agent_tabs as $agent_id => $agent_data) {
            $display = ($current_tab === $agent_id) ? 'block' : 'none';
            
            echo '<div id="' . esc_attr($agent_id) . '-content" class="thorius-tab-content" style="display: ' . esc_attr($display) . ';">';
            
            // Agent description
            echo '<div class="thorius-agent-description">';
            echo '<h2>' . esc_html($agent_data['title']) . '</h2>';
            echo '<p>' . esc_html($agent_data['description']) . '</p>';
            echo '</div>';
            
            // Agent settings
            echo '<div class="thorius-agent-settings">';
            echo '<h3>' . __('Agent Settings', 'vortex-ai-marketplace') . '</h3>';
            
            echo '<form id="' . esc_attr($agent_id) . '-settings-form" class="thorius-settings-form">';
            
            echo '<input type="hidden" name="agent_id" value="' . esc_attr($agent_id) . '">';
            
            // Model
            echo '<div class="thorius-form-field">';
            echo '<label for="' . esc_attr($agent_id) . '-model">' . __('Model', 'vortex-ai-marketplace') . '</label>';
            echo '<input type="text" id="' . esc_attr($agent_id) . '-model" name="model" value="' . esc_attr($agent_data['settings']['model']) . '">';
            echo '</div>';
            
            // Temperature
            echo '<div class="thorius-form-field">';
            echo '<label for="' . esc_attr($agent_id) . '-temperature">' . __('Temperature', 'vortex-ai-marketplace') . '</label>';
            echo '<input type="range" id="' . esc_attr($agent_id) . '-temperature" name="temperature" min="0" max="1" step="0.1" value="' . esc_attr($agent_data['settings']['temperature']) . '">';
            echo '<span class="thorius-range-value">' . esc_html($agent_data['settings']['temperature']) . '</span>';
            echo '</div>';
            
            // Max tokens
            echo '<div class="thorius-form-field">';
            echo '<label for="' . esc_attr($agent_id) . '-max-tokens">' . __('Max Tokens', 'vortex-ai-marketplace') . '</label>';
            echo '<input type="number" id="' . esc_attr($agent_id) . '-max-tokens" name="max_tokens" min="100" max="4000" step="100" value="' . esc_attr($agent_data['settings']['max_tokens']) . '">';
            echo '</div>';
            
            // Submit button
            echo '<div class="thorius-form-submit">';
            echo '<button type="submit" class="button button-primary">' . __('Save Settings', 'vortex-ai-marketplace') . '</button>';
            echo '</div>';
            
            echo '</form>';
            echo '</div>';
            
            // Agent shortcode examples
            echo '<div class="thorius-agent-shortcodes">';
            echo '<h3>' . __('Shortcode Usage', 'vortex-ai-marketplace') . '</h3>';
            
            echo '<div class="thorius-code-snippet">';
            echo '<code>[thorius_agent agent="' . esc_html($agent_id) . '"]</code>';
            echo '<p>' . __('Basic usage with default settings', 'vortex-ai-marketplace') . '</p>';
            echo '</div>';
            
            echo '<div class="thorius-code-snippet">';
            echo '<code>[thorius_agent agent="' . esc_html($agent_id) . '" theme="dark" height="500px" voice="true"]</code>';
            echo '<p>' . __('Advanced usage with custom settings', 'vortex-ai-marketplace') . '</p>';
            echo '</div>';
            
            echo '</div>';
            
            echo '</div>'; // End tab content
        }
        
        echo '</div>'; // End tab content wrapper
        echo '</div>'; // End tabs container
        echo '</div>'; // End wrap
    }
    
    /**
     * Get current agent tab
     */
    private function get_current_agent_tab() {
        $user_id = get_current_user_id();
        $saved_tab = get_user_meta($user_id, 'thorius_agent_tab', true);
        
        if (!empty($saved_tab)) {
            return $saved_tab;
        }
        
        // Default to first tab
        $agent_tabs = $this->orchestrator->get_agent_tabs();
        return key($agent_tabs);
    }
    
    /**
     * AJAX handler to save agent settings
     */
    public function ajax_save_agent_settings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'thorius_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Validate agent ID
        if (!isset($_POST['agent_id']) || empty($_POST['agent_id'])) {
            wp_send_json_error(array('message' => __('Invalid agent ID.', 'vortex-ai-marketplace')));
            exit;
        }
        
        $agent_id = sanitize_key($_POST['agent_id']);
        $agent_tabs = $this->orchestrator->get_agent_tabs();
        
        if (!isset($agent_tabs[$agent_id])) {
            wp_send_json_error(array('message' => __('Agent not found.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Get and sanitize settings
        $settings = array(
            'model' => isset($_POST['model']) ? sanitize_text_field($_POST['model']) : $agent_tabs[$agent_id]['settings']['model'],
            'temperature' => isset($_POST['temperature']) ? floatval($_POST['temperature']) : $agent_tabs[$agent_id]['settings']['temperature'],
            'max_tokens' => isset($_POST['max_tokens']) ? intval($_POST['max_tokens']) : $agent_tabs[$agent_id]['settings']['max_tokens']
        );
        
        // Validate settings
        if ($settings['temperature'] < 0 || $settings['temperature'] > 1) {
            $settings['temperature'] = 0.7;
        }
        
        if ($settings['max_tokens'] < 100 || $settings['max_tokens'] > 4000) {
            $settings['max_tokens'] = 1500;
        }
        
        // Save settings
        update_option('thorius_agent_' . $agent_id . '_settings', $settings);
        
        wp_send_json_success(array('message' => __('Settings saved successfully.', 'vortex-ai-marketplace')));
    }
    
    /**
     * AJAX handler to save tab state
     */
    public function ajax_save_tab_state() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'thorius_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Validate tab
        if (!isset($_POST['tab']) || empty($_POST['tab'])) {
            wp_send_json_error(array('message' => __('Invalid tab.', 'vortex-ai-marketplace')));
            exit;
        }
        
        $tab = sanitize_key($_POST['tab']);
        $user_id = get_current_user_id();
        
        // Save tab state
        update_user_meta($user_id, 'thorius_agent_tab', $tab);
        
        wp_send_json_success();
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        echo '<div class="wrap thorius-admin">';
        echo '<h1>' . __('Vortex AI Analytics', 'vortex-ai-marketplace') . '</h1>';
        
        // Analytics content will go here
        
        echo '</div>';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        echo '<div class="wrap thorius-admin">';
        echo '<h1>' . __('Vortex AI Settings', 'vortex-ai-marketplace') . '</h1>';
        
        // Settings content will go here
        
        echo '</div>';
    }
} 