<?php
/**
 * Handles the premium Concierge experience connecting digital to physical
 */
class Vortex_Concierge {
    /**
     * Initialize the concierge service
     */
    public function __construct() {
        add_action('init', array($this, 'register_concierge_post_types'));
        add_action('rest_api_init', array($this, 'register_api_endpoints'));
        add_shortcode('vortex_concierge_events', array($this, 'render_events_shortcode'));
        add_shortcode('vortex_concierge_booking', array($this, 'render_booking_shortcode'));
    }
    
    /**
     * Register necessary post types for Concierge experiences
     */
    public function register_concierge_post_types() {
        // Register 'vortex_experience' post type
        register_post_type('vortex_experience', array(
            'labels' => array(
                'name' => __('Experiences', 'vortex-ai-marketplace'),
                'singular_name' => __('Experience', 'vortex-ai-marketplace')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_icon' => 'dashicons-tickets-alt',
            'show_in_rest' => true,
        ));
        
        // Register 'vortex_location' taxonomy
        register_taxonomy('vortex_location', 'vortex_experience', array(
            'labels' => array(
                'name' => __('Locations', 'vortex-ai-marketplace'),
                'singular_name' => __('Location', 'vortex-ai-marketplace')
            ),
            'hierarchical' => true,
            'show_in_rest' => true,
        ));
        
        // Register 'vortex_experience_type' taxonomy
        register_taxonomy('vortex_experience_type', 'vortex_experience', array(
            'labels' => array(
                'name' => __('Experience Types', 'vortex-ai-marketplace'),
                'singular_name' => __('Experience Type', 'vortex-ai-marketplace')
            ),
            'hierarchical' => true,
            'show_in_rest' => true,
        ));
    }
    
    /**
     * Render the concierge events shortcode
     */
    public function render_events_shortcode($atts) {
        $atts = shortcode_atts(array(
            'location' => '',
            'type' => '',
            'limit' => 6,
            'days' => 30,
        ), $atts, 'vortex_concierge_events');
        
        // Query upcoming events based on attributes
        $args = array(
            'post_type' => 'vortex_experience',
            'posts_per_page' => intval($atts['limit']),
            'meta_query' => array(
                array(
                    'key' => 'vortex_experience_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                ),
                array(
                    'key' => 'vortex_experience_date',
                    'value' => date('Y-m-d', strtotime('+' . intval($atts['days']) . ' days')),
                    'compare' => '<=',
                    'type' => 'DATE'
                )
            ),
            'orderby' => 'meta_value',
            'meta_key' => 'vortex_experience_date',
            'order' => 'ASC'
        );
        
        // Add taxonomy filters if specified
        if (!empty($atts['location'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'vortex_location',
                'field' => 'slug',
                'terms' => explode(',', $atts['location'])
            );
        }
        
        if (!empty($atts['type'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'vortex_experience_type',
                'field' => 'slug',
                'terms' => explode(',', $atts['type'])
            );
        }
        
        $experiences = new WP_Query($args);
        
        ob_start();
        if ($experiences->have_posts()) {
            include plugin_dir_path(dirname(__FILE__)) . 'public/partials/concierge-events.php';
        } else {
            echo '<div class="vortex-notice">' . __('No upcoming experiences found.', 'vortex-ai-marketplace') . '</div>';
        }
        return ob_get_clean();
    }
    
    /**
     * Render the concierge booking shortcode
     */
    public function render_booking_shortcode($atts) {
        // Implementation for booking form
    }
} 