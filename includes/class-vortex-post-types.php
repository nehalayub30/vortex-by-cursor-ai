<?php
/**
 * Post type registration for Vortex AI Marketplace
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_Post_Types {
    
    /**
     * Initialize the post types
     */
    public static function init() {
        // Register post types
        add_action('init', array(__CLASS__, 'register_post_types'));
        
        // Register taxonomies
        add_action('init', array(__CLASS__, 'register_taxonomies'));
    }
    
    /**
     * Register custom post types
     */
    public static function register_post_types() {
        // Register event post type
        register_post_type('vortex_event', array(
            'labels' => array(
                'name' => __('Events', 'vortex-ai-marketplace'),
                'singular_name' => __('Event', 'vortex-ai-marketplace'),
                'menu_name' => __('Events', 'vortex-ai-marketplace'),
                'all_items' => __('All Events', 'vortex-ai-marketplace'),
                'add_new' => __('Add New', 'vortex-ai-marketplace'),
                'add_new_item' => __('Add New Event', 'vortex-ai-marketplace'),
                'edit_item' => __('Edit Event', 'vortex-ai-marketplace'),
                'new_item' => __('New Event', 'vortex-ai-marketplace'),
                'view_item' => __('View Event', 'vortex-ai-marketplace'),
                'search_items' => __('Search Events', 'vortex-ai-marketplace'),
                'not_found' => __('No events found', 'vortex-ai-marketplace'),
                'not_found_in_trash' => __('No events found in trash', 'vortex-ai-marketplace')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'menu_position' => 20,
            'supports' => array('title', 'editor', 'thumbnail'),
            'rewrite' => array('slug' => 'events'),
            'show_in_rest' => true
        ));
        
        // Register offer post type
        register_post_type('vortex_offer', array(
            'labels' => array(
                'name' => __('Offers', 'vortex-ai-marketplace'),
                'singular_name' => __('Offer', 'vortex-ai-marketplace'),
                'menu_name' => __('Offers', 'vortex-ai-marketplace'),
                'all_items' => __('All Offers', 'vortex-ai-marketplace'),
                'add_new' => __('Add New', 'vortex-ai-marketplace'),
                'add_new_item' => __('Add New Offer', 'vortex-ai-marketplace'),
                'edit_item' => __('Edit Offer', 'vortex-ai-marketplace'),
                'new_item' => __('New Offer', 'vortex-ai-marketplace'),
                'view_item' => __('View Offer', 'vortex-ai-marketplace'),
                'search_items' => __('Search Offers', 'vortex-ai-marketplace'),
                'not_found' => __('No offers found', 'vortex-ai-marketplace'),
                'not_found_in_trash' => __('No offers found in trash', 'vortex-ai-marketplace')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-money-alt',
            'menu_position' => 21,
            'supports' => array('title', 'editor', 'thumbnail'),
            'rewrite' => array('slug' => 'offers'),
            'show_in_rest' => true
        ));
        
        // Register collaboration post type
        register_post_type('vortex_collaboration', array(
            'labels' => array(
                'name' => __('Collaborations', 'vortex-ai-marketplace'),
                'singular_name' => __('Collaboration', 'vortex-ai-marketplace'),
                'menu_name' => __('Collaborations', 'vortex-ai-marketplace'),
                'all_items' => __('All Collaborations', 'vortex-ai-marketplace'),
                'add_new' => __('Add New', 'vortex-ai-marketplace'),
                'add_new_item' => __('Add New Collaboration', 'vortex-ai-marketplace'),
                'edit_item' => __('Edit Collaboration', 'vortex-ai-marketplace'),
                'new_item' => __('New Collaboration', 'vortex-ai-marketplace'),
                'view_item' => __('View Collaboration', 'vortex-ai-marketplace'),
                'search_items' => __('Search Collaborations', 'vortex-ai-marketplace'),
                'not_found' => __('No collaborations found', 'vortex-ai-marketplace'),
                'not_found_in_trash' => __('No collaborations found in trash', 'vortex-ai-marketplace')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-groups',
            'menu_position' => 22,
            'supports' => array('title', 'editor', 'thumbnail'),
            'rewrite' => array('slug' => 'collaborations'),
            'show_in_rest' => true
        ));
        
        // Register item post type for collector-collector workplace
        register_post_type('vortex_item', array(
            'labels' => array(
                'name' => __('Swipeable Items', 'vortex-ai-marketplace'),
                'singular_name' => __('Swipeable Item', 'vortex-ai-marketplace'),
                'menu_name' => __('Swipeable Items', 'vortex-ai-marketplace'),
                'all_items' => __('All Swipeable Items', 'vortex-ai-marketplace'),
                'add_new' => __('Add New', 'vortex-ai-marketplace'),
                'add_new_item' => __('Add New Swipeable Item', 'vortex-ai-marketplace'),
                'edit_item' => __('Edit Swipeable Item', 'vortex-ai-marketplace'),
                'new_item' => __('New Swipeable Item', 'vortex-ai-marketplace'),
                'view_item' => __('View Swipeable Item', 'vortex-ai-marketplace'),
                'search_items' => __('Search Swipeable Items', 'vortex-ai-marketplace'),
                'not_found' => __('No swipeable items found', 'vortex-ai-marketplace'),
                'not_found_in_trash' => __('No swipeable items found in trash', 'vortex-ai-marketplace')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-images-alt',
            'menu_position' => 23,
            'supports' => array('title', 'editor', 'thumbnail'),
            'rewrite' => array('slug' => 'items'),
            'show_in_rest' => true
        ));
        
        // Register project post type for project proposals
        register_post_type('vortex_project', array(
            'labels' => array(
                'name' => __('Projects', 'vortex-ai-marketplace'),
                'singular_name' => __('Project', 'vortex-ai-marketplace'),
                'menu_name' => __('Projects', 'vortex-ai-marketplace'),
                'all_items' => __('All Projects', 'vortex-ai-marketplace'),
                'add_new' => __('Add New', 'vortex-ai-marketplace'),
                'add_new_item' => __('Add New Project', 'vortex-ai-marketplace'),
                'edit_item' => __('Edit Project', 'vortex-ai-marketplace'),
                'new_item' => __('New Project', 'vortex-ai-marketplace'),
                'view_item' => __('View Project', 'vortex-ai-marketplace'),
                'search_items' => __('Search Projects', 'vortex-ai-marketplace'),
                'not_found' => __('No projects found', 'vortex-ai-marketplace'),
                'not_found_in_trash' => __('No projects found in trash', 'vortex-ai-marketplace')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-portfolio',
            'menu_position' => 24,
            'supports' => array('title', 'editor', 'thumbnail'),
            'rewrite' => array('slug' => 'projects'),
            'show_in_rest' => true
        ));
    }
    
    /**
     * Register custom taxonomies
     */
    public static function register_taxonomies() {
        // Register item category taxonomy
        register_taxonomy('item_category', 'vortex_item', array(
            'labels' => array(
                'name' => __('Item Categories', 'vortex-ai-marketplace'),
                'singular_name' => __('Item Category', 'vortex-ai-marketplace'),
                'menu_name' => __('Item Categories', 'vortex-ai-marketplace'),
                'all_items' => __('All Item Categories', 'vortex-ai-marketplace'),
                'edit_item' => __('Edit Item Category', 'vortex-ai-marketplace'),
                'view_item' => __('View Item Category', 'vortex-ai-marketplace'),
                'update_item' => __('Update Item Category', 'vortex-ai-marketplace'),
                'add_new_item' => __('Add New Item Category', 'vortex-ai-marketplace'),
                'new_item_name' => __('New Item Category Name', 'vortex-ai-marketplace'),
                'search_items' => __('Search Item Categories', 'vortex-ai-marketplace'),
                'popular_items' => __('Popular Item Categories', 'vortex-ai-marketplace'),
                'not_found' => __('No item categories found', 'vortex-ai-marketplace')
            ),
            'hierarchical' => true,
            'public' => true,
            'show_admin_column' => true,
            'show_in_rest' => true
        ));
        
        // Register project category taxonomy
        register_taxonomy('project_category', 'vortex_project', array(
            'labels' => array(
                'name' => __('Project Categories', 'vortex-ai-marketplace'),
                'singular_name' => __('Project Category', 'vortex-ai-marketplace'),
                'menu_name' => __('Project Categories', 'vortex-ai-marketplace'),
                'all_items' => __('All Project Categories', 'vortex-ai-marketplace'),
                'edit_item' => __('Edit Project Category', 'vortex-ai-marketplace'),
                'view_item' => __('View Project Category', 'vortex-ai-marketplace'),
                'update_item' => __('Update Project Category', 'vortex-ai-marketplace'),
                'add_new_item' => __('Add New Project Category', 'vortex-ai-marketplace'),
                'new_item_name' => __('New Project Category Name', 'vortex-ai-marketplace'),
                'search_items' => __('Search Project Categories', 'vortex-ai-marketplace'),
                'popular_items' => __('Popular Project Categories', 'vortex-ai-marketplace'),
                'not_found' => __('No project categories found', 'vortex-ai-marketplace')
            ),
            'hierarchical' => true,
            'public' => true,
            'show_admin_column' => true,
            'show_in_rest' => true
        ));
    }
} 