<?php
/**
 * VORTEX Cloe AI Agent Widgets
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Cloe_Widgets Class
 * 
 * Handles the creation and management of Cloe AI Agent widgets
 * with WordPress theme integration and continuous AI agent activity.
 */
class VORTEX_Cloe_Widgets {
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * Cloe AI Agent instance
     */
    private $cloe;
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->cloe = VORTEX_CLOE::get_instance();
        
        // Set up hooks
        add_action('widgets_init', array($this, 'register_widgets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_vortex_cloe_get_greeting', array($this, 'handle_get_greeting'));
        add_action('wp_ajax_vortex_cloe_get_recommendations', array($this, 'handle_get_recommendations'));
        add_action('wp_ajax_vortex_cloe_update_preferences', array($this, 'handle_update_preferences'));
        
        // Register shortcodes
        add_shortcode('vortex_cloe_greeting', array($this, 'render_greeting'));
        add_shortcode('vortex_cloe_recommendations', array($this, 'render_recommendations'));
        add_shortcode('vortex_cloe_preferences', array($this, 'render_preferences'));
        add_shortcode('vortex_cloe_insights', array($this, 'render_insights'));
    }
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Register widgets
     */
    public function register_widgets() {
        register_widget('Vortex_Cloe_Greeting_Widget');
        register_widget('Vortex_Cloe_Recommendations_Widget');
        register_widget('Vortex_Cloe_Preferences_Widget');
        register_widget('Vortex_Cloe_Insights_Widget');
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Enqueue styles
        wp_enqueue_style(
            'vortex-cloe-widgets',
            VORTEX_PLUGIN_URL . 'css/vortex-cloe-widgets.css',
            array(),
            VORTEX_VERSION
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'vortex-cloe-widgets',
            VORTEX_PLUGIN_URL . 'js/vortex-cloe-widgets.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('vortex-cloe-widgets', 'vortexCloe', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_cloe_nonce'),
            'i18n' => array(
                'loading' => __('Loading...', 'vortex-ai-marketplace'),
                'error' => __('An error occurred. Please try again.', 'vortex-ai-marketplace'),
                'update_success' => __('Preferences updated successfully.', 'vortex-ai-marketplace'),
                'update_error' => __('Failed to update preferences.', 'vortex-ai-marketplace')
            )
        ));
        
        // Get theme colors for dynamic styling
        $theme_colors = $this->get_theme_colors();
        wp_localize_script('vortex-cloe-widgets', 'vortexThemeColors', $theme_colors);
    }
    
    /**
     * Get theme colors
     */
    private function get_theme_colors() {
        $colors = array(
            'primary' => get_theme_mod('primary_color', '#007bff'),
            'secondary' => get_theme_mod('secondary_color', '#6c757d'),
            'accent' => get_theme_mod('accent_color', '#28a745'),
            'background' => get_theme_mod('background_color', '#ffffff'),
            'text' => get_theme_mod('text_color', '#212529')
        );
        
        return $colors;
    }
    
    /**
     * Handle get greeting AJAX request
     */
    public function handle_get_greeting() {
        check_ajax_referer('vortex_cloe_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }
        
        $user_id = get_current_user_id();
        $greeting = $this->cloe->get_personalized_greeting($user_id);
        
        wp_send_json_success(array(
            'greeting' => $greeting
        ));
    }
    
    /**
     * Handle get recommendations AJAX request
     */
    public function handle_get_recommendations() {
        check_ajax_referer('vortex_cloe_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }
        
        $user_id = get_current_user_id();
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'artwork';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
        
        $recommendations = $this->cloe->get_personalized_recommendations($user_id, $type, $limit);
        
        wp_send_json_success(array(
            'recommendations' => $recommendations
        ));
    }
    
    /**
     * Handle update preferences AJAX request
     */
    public function handle_update_preferences() {
        check_ajax_referer('vortex_cloe_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }
        
        $user_id = get_current_user_id();
        $preferences = isset($_POST['preferences']) ? $_POST['preferences'] : array();
        
        // Validate and sanitize preferences
        $sanitized_preferences = array();
        foreach ($preferences as $key => $value) {
            $sanitized_preferences[sanitize_text_field($key)] = sanitize_text_field($value);
        }
        
        // Update user preferences
        update_user_meta($user_id, 'vortex_cloe_preferences', $sanitized_preferences);
        
        wp_send_json_success(array(
            'message' => __('Preferences updated successfully.', 'vortex-ai-marketplace')
        ));
    }
    
    /**
     * Render greeting shortcode
     */
    public function render_greeting($atts) {
        if (!is_user_logged_in()) {
            return sprintf(
                '<p>%s <a href="%s">%s</a></p>',
                __('Please', 'vortex-ai-marketplace'),
                wp_login_url(get_permalink()),
                __('log in', 'vortex-ai-marketplace')
            );
        }
        
        ob_start();
        include VORTEX_PLUGIN_PATH . 'public/partials/vortex-cloe-greeting-widget.php';
        return ob_get_clean();
    }
    
    /**
     * Render recommendations shortcode
     */
    public function render_recommendations($atts) {
        if (!is_user_logged_in()) {
            return sprintf(
                '<p>%s <a href="%s">%s</a></p>',
                __('Please', 'vortex-ai-marketplace'),
                wp_login_url(get_permalink()),
                __('log in', 'vortex-ai-marketplace')
            );
        }
        
        ob_start();
        include VORTEX_PLUGIN_PATH . 'public/partials/vortex-cloe-recommendations-widget.php';
        return ob_get_clean();
    }
    
    /**
     * Render preferences shortcode
     */
    public function render_preferences($atts) {
        if (!is_user_logged_in()) {
            return sprintf(
                '<p>%s <a href="%s">%s</a></p>',
                __('Please', 'vortex-ai-marketplace'),
                wp_login_url(get_permalink()),
                __('log in', 'vortex-ai-marketplace')
            );
        }
        
        ob_start();
        include VORTEX_PLUGIN_PATH . 'public/partials/vortex-cloe-preferences-widget.php';
        return ob_get_clean();
    }
    
    /**
     * Render insights shortcode
     */
    public function render_insights($atts) {
        if (!is_user_logged_in()) {
            return sprintf(
                '<p>%s <a href="%s">%s</a></p>',
                __('Please', 'vortex-ai-marketplace'),
                wp_login_url(get_permalink()),
                __('log in', 'vortex-ai-marketplace')
            );
        }
        
        ob_start();
        include VORTEX_PLUGIN_PATH . 'public/partials/vortex-cloe-insights-widget.php';
        return ob_get_clean();
    }
} 