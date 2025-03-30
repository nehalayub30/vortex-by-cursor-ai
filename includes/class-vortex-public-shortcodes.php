<?php
/**
 * Public shortcodes for Vortex AI Marketplace
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_Public_Shortcodes {
    
    /**
     * Initialize the shortcodes
     */
    public static function init() {
        add_shortcode('vortex_event_form', array(__CLASS__, 'render_event_form'));
        add_shortcode('vortex_offer_form', array(__CLASS__, 'render_offer_form'));
        add_shortcode('vortex_collaboration_form', array(__CLASS__, 'render_collaboration_form'));
        add_shortcode('vortex_events_list', array(__CLASS__, 'render_events_list'));
        add_shortcode('vortex_offers_list', array(__CLASS__, 'render_offers_list'));
        add_shortcode('vortex_collaborations_list', array(__CLASS__, 'render_collaborations_list'));
    }
    
    /**
     * Render event creation form
     */
    public static function render_event_form($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="vortex-notice">' . 
                   esc_html__('Please log in to create an event.', 'vortex-ai-marketplace') . 
                   '<p><a href="' . esc_url(wp_login_url(get_permalink())) . '" class="vortex-button">' . 
                   esc_html__('Log In', 'vortex-ai-marketplace') . '</a></p></div>';
        }
        
        // Buffer the output
        ob_start();
        
        // Include the template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-event-form.php';
        
        return ob_get_clean();
    }
    
    /**
     * Render offer creation form
     */
    public static function render_offer_form($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="vortex-notice">' . 
                   esc_html__('Please log in to create an offer.', 'vortex-ai-marketplace') . 
                   '<p><a href="' . esc_url(wp_login_url(get_permalink())) . '" class="vortex-button">' . 
                   esc_html__('Log In', 'vortex-ai-marketplace') . '</a></p></div>';
        }
        
        // Buffer the output
        ob_start();
        
        // Include the template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-offer-form.php';
        
        return ob_get_clean();
    }
    
    /**
     * Render collaboration creation form
     */
    public static function render_collaboration_form($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="vortex-notice">' . 
                   esc_html__('Please log in to create a collaboration.', 'vortex-ai-marketplace') . 
                   '<p><a href="' . esc_url(wp_login_url(get_permalink())) . '" class="vortex-button">' . 
                   esc_html__('Log In', 'vortex-ai-marketplace') . '</a></p></div>';
        }
        
        // Buffer the output
        ob_start();
        
        // Include the template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-collaboration-form.php';
        
        return ob_get_clean();
    }
    
    /**
     * Render events list
     */
    public static function render_events_list($atts) {
        // Get events
        $args = array(
            'post_type' => 'vortex_event',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'meta_value',
            'meta_key' => 'event_date',
            'order' => 'ASC'
        );
        
        $events = get_posts($args);
        
        // Buffer the output
        ob_start();
        
        // Include the template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-events-list.php';
        
        return ob_get_clean();
    }
    
    /**
     * Render offers list
     */
    public static function render_offers_list($atts) {
        // Get offers
        $args = array(
            'post_type' => 'vortex_offer',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $offers = get_posts($args);
        
        // Buffer the output
        ob_start();
        
        // Include the template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-offers-list.php';
        
        return ob_get_clean();
    }
    
    /**
     * Render collaborations list
     */
    public static function render_collaborations_list($atts) {
        // Get collaborations
        $args = array(
            'post_type' => 'vortex_collaboration',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $collaborations = get_posts($args);
        
        // Buffer the output
        ob_start();
        
        // Include the template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-collaborations-list.php';
        
        return ob_get_clean();
    }
} 