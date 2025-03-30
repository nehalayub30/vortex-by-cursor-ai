<?php
/**
 * Handles decentralized collaboration between artists
 */
class Vortex_Collaboration {
    /**
     * Initialize the collaboration framework
     */
    public function __construct() {
        add_action('init', array($this, 'register_collaboration_post_types'));
        add_shortcode('vortex_collaborations', array($this, 'render_collaborations_shortcode'));
        add_shortcode('vortex_collaboration_form', array($this, 'render_collaboration_form_shortcode'));
        add_action('wp_ajax_vortex_create_collaboration', array($this, 'create_collaboration'));
        add_action('wp_ajax_vortex_join_collaboration', array($this, 'join_collaboration'));
    }
    
    /**
     * Register collaboration post types
     */
    public function register_collaboration_post_types() {
        register_post_type('vortex_collab', array(
            'labels' => array(
                'name' => __('Collaborations', 'vortex-ai-marketplace'),
                'singular_name' => __('Collaboration', 'vortex-ai-marketplace')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'author'),
            'menu_icon' => 'dashicons-groups',
            'show_in_rest' => true,
        ));
        
        register_taxonomy('vortex_collab_type', 'vortex_collab', array(
            'labels' => array(
                'name' => __('Collaboration Types', 'vortex-ai-marketplace'),
                'singular_name' => __('Collaboration Type', 'vortex-ai-marketplace')
            ),
            'hierarchical' => true,
            'show_in_rest' => true,
        ));
    }
    
    /**
     * Render collaborations shortcode
     */
    public function render_collaborations_shortcode($atts) {
        $atts = shortcode_atts(array(
            'status' => 'open', // open, active, completed
            'type' => '',
            'limit' => 10,
            'user_id' => 0
        ), $atts, 'vortex_collaborations');
        
        // Convert attributes
        $limit = intval($atts['limit']);
        $user_id = intval($atts['user_id']);
        if (!$user_id && is_user_logged_in()) {
            $user_id = get_current_user_id();
        }
        
        // Build query
        $args = array(
            'post_type' => 'vortex_collab',
            'posts_per_page' => $limit,
            'meta_query' => array(
                array(
                    'key' => 'vortex_collab_status',
                    'value' => $atts['status'],
                    'compare' => '='
                )
            )
        );
        
        // Filter by collaboration type if specified
        if (!empty($atts['type'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'vortex_collab_type',
                    'field' => 'slug',
                    'terms' => explode(',', $atts['type'])
                )
            );
        }
        
        // Filter by user participation if specified
        if ($user_id > 0) {
            $args['meta_query'][] = array(
                'key' => 'vortex_collab_participants',
                'value' => serialize($user_id),
                'compare' => 'LIKE'
            );
        }
        
        $collaborations = new WP_Query($args);
        
        ob_start();
        if ($collaborations->have_posts()) {
            include plugin_dir_path(dirname(__FILE__)) . 'public/partials/collaborations.php';
        } else {
            echo '<div class="vortex-notice">' . __('No collaborations found.', 'vortex-ai-marketplace') . '</div>';
        }
        return ob_get_clean();
    }
    
    /**
     * Render collaboration creation form shortcode
     */
    public function render_collaboration_form_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="vortex-notice vortex-notice-warning">' . 
                   __('Please log in to create a collaboration.', 'vortex-ai-marketplace') . 
                   '</div>';
        }
        
        // Check if user has TOLA tokens
        $wallet = Vortex_AI_Marketplace::get_instance()->wallet;
        if (!$wallet->check_llm_api_access(get_current_user_id())) {
            return '<div class="vortex-notice vortex-notice-warning">' . 
                   __('You need TOLA tokens to create collaborations.', 'vortex-ai-marketplace') . 
                   '</div>';
        }
        
        // Get collaboration types
        $types = get_terms(array(
            'taxonomy' => 'vortex_collab_type',
            'hide_empty' => false
        ));
        
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/collaboration-form.php';
        return ob_get_clean();
    }
    
    /**
     * Handle collaboration creation via AJAX
     */
    public function create_collaboration() {
        // Implementation for creating a smart contract-based collaboration
    }
} 