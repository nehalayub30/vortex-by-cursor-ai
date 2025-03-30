<?php
/**
 * Adaptive User Experience
 * 
 * Customizes the AI interface and behavior based on user preferences,
 * interaction patterns, and skill level.
 */
class Vortex_Adaptive_UX {
    
    /**
     * Initialize the adaptive UX system
     */
    public function __construct() {
        // Track user interactions and preferences
        add_action('wp_ajax_vortex_track_ux_preference', array($this, 'track_preference'));
        
        // Apply personalization to AI interfaces
        add_filter('vortex_ai_form_attributes', array($this, 'personalize_interface'), 10, 2);
        add_filter('vortex_ai_response_format', array($this, 'personalize_response'), 10, 3);
        
        // Enqueue personalization scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_adaptive_scripts'));
    }
    
    /**
     * Track user preference
     */
    public function track_preference() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_ux_preference')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Ensure user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User must be logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $preference_type = sanitize_text_field($_POST['preference_type'] ?? '');
        $preference_value = sanitize_text_field($_POST['preference_value'] ?? '');
        
        if (empty($preference_type) || empty($preference_value)) {
            wp_send_json_error(array('message' => 'Missing preference data'));
            return;
        }
        
        // Store user preference
        $current_preferences = get_user_meta($user_id, 'vortex_ux_preferences', true);
        if (!is_array($current_preferences)) {
            $current_preferences = array();
        }
        
        $current_preferences[$preference_type] = $preference_value;
        update_user_meta($user_id, 'vortex_ux_preferences', $current_preferences);
        
        wp_send_json_success(array('message' => 'Preference saved'));
    }
    
    /**
     * Personalize interface based on user preferences
     * 
     * @param array $attributes Form attributes
     * @param string $form_type Form type
     * @return array Modified attributes
     */
    public function personalize_interface($attributes, $form_type) {
        if (!is_user_logged_in()) {
            return $attributes;
        }
        
        $user_id = get_current_user_id();
        $preferences = get_user_meta($user_id, 'vortex_ux_preferences', true);
        
        if (!is_array($preferences)) {
            return $attributes;
        }
        
        // Apply interface customizations based on preferences
        if (isset($preferences['complexity']) && $form_type === 'artwork') {
            switch ($preferences['complexity']) {
                case 'simple':
                    $attributes['show_advanced_options'] = false;
                    break;
                case 'advanced':
                    $attributes['show_advanced_options'] = true;
                    $attributes['show_technical_params'] = true;
                    break;
            }
        }
        
        return $attributes;
    }
    
    /**
     * Personalize AI response format
     * 
     * @param string $response_format Response format
     * @param array $response Response data
     * @param int $user_id User ID
     * @return string Modified response format
     */
    public function personalize_response($response_format, $response, $user_id) {
        if ($user_id === 0) {
            return $response_format;
        }
        
        $preferences = get_user_meta($user_id, 'vortex_ux_preferences', true);
        
        if (!is_array($preferences)) {
            return $response_format;
        }
        
        // Adapt response format based on preferences
        if (isset($preferences['detail_level'])) {
            switch ($preferences['detail_level']) {
                case 'concise':
                    // Simplify response format
                    $response_format = 'summary';
                    break;
                case 'detailed':
                    // Provide comprehensive response
                    $response_format = 'detailed';
                    break;
            }
        }
        
        return $response_format;
    }
    
    /**
     * Enqueue adaptive UX scripts
     */
    public function enqueue_adaptive_scripts() {
        if (!is_user_logged_in()) {
            return;
        }
        
        wp_enqueue_script(
            'vortex-adaptive-ux',
            plugin_dir_url(dirname(__FILE__)) . 'public/js/vortex-adaptive-ux.js',
            array('jquery'),
            time(),
            true
        );
        
        // Pass user preferences to JavaScript
        $user_id = get_current_user_id();
        $preferences = get_user_meta($user_id, 'vortex_ux_preferences', true);
        
        wp_localize_script('vortex-adaptive-ux', 'vortexUX', array(
            'preferences' => is_array($preferences) ? $preferences : array(),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_ux_preference')
        ));
    }
} 