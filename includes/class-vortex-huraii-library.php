<?php
/**
 * HURAII Image Library Handler
 * 
 * Manages saving, retrieving, and organizing AI-generated visuals
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * The class that handles HURAII image library functions
 */
class Vortex_HURAII_Library {
    
    /**
     * Library post type
     */
    private $post_type = 'vortex_ai_image';
    
    /**
     * Get instance of HURAII Library
     * 
     * @return Vortex_HURAII_Library
     */
    public static function get_instance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
    }
    
    /**
     * Register the AI Image post type
     */
    public function register_post_type() {
        register_post_type($this->post_type, array(
            'labels' => array(
                'name'               => __('AI Images', 'vortex-ai-marketplace'),
                'singular_name'      => __('AI Image', 'vortex-ai-marketplace'),
                'menu_name'          => __('HURAII Library', 'vortex-ai-marketplace'),
                'add_new'            => __('Add New', 'vortex-ai-marketplace'),
                'add_new_item'       => __('Add New AI Image', 'vortex-ai-marketplace'),
                'edit_item'          => __('Edit AI Image', 'vortex-ai-marketplace'),
                'new_item'           => __('New AI Image', 'vortex-ai-marketplace'),
                'view_item'          => __('View AI Image', 'vortex-ai-marketplace'),
                'search_items'       => __('Search AI Images', 'vortex-ai-marketplace'),
                'not_found'          => __('No AI images found', 'vortex-ai-marketplace'),
                'not_found_in_trash' => __('No AI images found in trash', 'vortex-ai-marketplace'),
            ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'ai-images'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-format-image',
            'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields', 'author'),
        ));
    }
    
    /**
     * Register taxonomies for AI images
     */
    public function register_taxonomies() {
        // Register AI Style taxonomy
        register_taxonomy('ai_style', $this->post_type, array(
            'hierarchical'      => true,
            'labels'            => array(
                'name'              => __('AI Styles', 'vortex-ai-marketplace'),
                'singular_name'     => __('AI Style', 'vortex-ai-marketplace'),
                'search_items'      => __('Search AI Styles', 'vortex-ai-marketplace'),
                'all_items'         => __('All AI Styles', 'vortex-ai-marketplace'),
                'parent_item'       => __('Parent AI Style', 'vortex-ai-marketplace'),
                'parent_item_colon' => __('Parent AI Style:', 'vortex-ai-marketplace'),
                'edit_item'         => __('Edit AI Style', 'vortex-ai-marketplace'),
                'update_item'       => __('Update AI Style', 'vortex-ai-marketplace'),
                'add_new_item'      => __('Add New AI Style', 'vortex-ai-marketplace'),
                'new_item_name'     => __('New AI Style Name', 'vortex-ai-marketplace'),
                'menu_name'         => __('AI Styles', 'vortex-ai-marketplace'),
            ),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'ai-style'),
        ));
        
        // Register AI Technique taxonomy
        register_taxonomy('ai_technique', $this->post_type, array(
            'hierarchical'      => false,
            'labels'            => array(
                'name'              => __('AI Techniques', 'vortex-ai-marketplace'),
                'singular_name'     => __('AI Technique', 'vortex-ai-marketplace'),
                'search_items'      => __('Search AI Techniques', 'vortex-ai-marketplace'),
                'all_items'         => __('All AI Techniques', 'vortex-ai-marketplace'),
                'parent_item'       => __('Parent AI Technique', 'vortex-ai-marketplace'),
                'parent_item_colon' => __('Parent AI Technique:', 'vortex-ai-marketplace'),
                'edit_item'         => __('Edit AI Technique', 'vortex-ai-marketplace'),
                'update_item'       => __('Update AI Technique', 'vortex-ai-marketplace'),
                'add_new_item'      => __('Add New AI Technique', 'vortex-ai-marketplace'),
                'new_item_name'     => __('New AI Technique Name', 'vortex-ai-marketplace'),
                'menu_name'         => __('AI Techniques', 'vortex-ai-marketplace'),
            ),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'ai-technique'),
        ));
    }
    
    /**
     * Save an AI-generated image to the library
     *
     * @param string $image_url URL of the image
     * @param array $metadata Image metadata (prompt, settings, etc.)
     * @param int $user_id User ID who created the image
     * @return int|WP_Error Post ID on success, WP_Error on failure
     */
    public function save_image($image_url, $metadata, $user_id = 0) {
        // If no user ID provided, use current user
        if (!$user_id && is_user_logged_in()) {
            $user_id = get_current_user_id();
        }
        
        // Create post title from prompt or default
        $title = !empty($metadata['prompt']) ? substr($metadata['prompt'], 0, 50) . '...' : __('HURAII Generated Image', 'vortex-ai-marketplace');
        
        // Create post array
        $post_data = array(
            'post_title'   => $title,
            'post_content' => !empty($metadata['prompt']) ? $metadata['prompt'] : '',
            'post_status'  => 'publish',
            'post_author'  => $user_id,
            'post_type'    => $this->post_type,
        );
        
        // Insert the post
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Store metadata
        foreach ($metadata as $key => $value) {
            update_post_meta($post_id, 'vortex_' . $key, $value);
        }
        
        // Add special meta for HURAII attribution
        update_post_meta($post_id, 'vortex_ai_generated', true);
        update_post_meta($post_id, 'vortex_ai_engine', 'HURAII');
        update_post_meta($post_id, 'vortex_generation_date', current_time('mysql'));
        
        // Save blockchain verification status
        update_post_meta($post_id, 'vortex_blockchain_verified', false);
        
        // Import the image from URL and set as featured image
        $this->set_featured_image_from_url($post_id, $image_url);
        
        // Add taxonomies if provided
        if (!empty($metadata['style'])) {
            wp_set_object_terms($post_id, $metadata['style'], 'ai_style');
        }
        
        if (!empty($metadata['technique'])) {
            wp_set_object_terms($post_id, $metadata['technique'], 'ai_technique');
        }
        
        // Return the post ID
        return $post_id;
    }
    
    /**
     * Set featured image from URL
     *
     * @param int $post_id Post ID
     * @param string $image_url Image URL
     * @return int|WP_Error Attachment ID if successful, WP_Error on failure
     */
    private function set_featured_image_from_url($post_id, $image_url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Download image to media library
        $attachment_id = media_sideload_image($image_url, $post_id, '', 'id');
        
        if (!is_wp_error($attachment_id)) {
            // Set as featured image
            set_post_thumbnail($post_id, $attachment_id);
        }
        
        return $attachment_id;
    }
    
    /**
     * Get user's AI image library
     *
     * @param int $user_id User ID
     * @param int $limit Number of images to return
     * @param int $page Page number
     * @return array Array of AI images
     */
    public function get_user_library($user_id, $limit = 10, $page = 1) {
        $args = array(
            'post_type'      => $this->post_type,
            'author'         => $user_id,
            'posts_per_page' => $limit,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );
        
        $query = new WP_Query($args);
        
        $images = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $image = array(
                    'id'          => $post_id,
                    'title'       => get_the_title(),
                    'url'         => get_permalink(),
                    'image_url'   => get_the_post_thumbnail_url($post_id, 'large'),
                    'thumbnail'   => get_the_post_thumbnail_url($post_id, 'thumbnail'),
                    'date'        => get_the_date('c'),
                    'prompt'      => get_post_meta($post_id, 'vortex_prompt', true),
                    'engine'      => get_post_meta($post_id, 'vortex_ai_engine', true),
                    'blockchain'  => get_post_meta($post_id, 'vortex_blockchain_verified', true),
                );
                
                $images[] = $image;
            }
            
            wp_reset_postdata();
        }
        
        return array(
            'images' => $images,
            'total'  => $query->found_posts,
            'pages'  => $query->max_num_pages,
        );
    }
    
    /**
     * Check if a post is an AI-generated image
     *
     * @param int $post_id Post ID
     * @return bool True if AI-generated, false otherwise
     */
    public function is_ai_image($post_id) {
        return get_post_type($post_id) === $this->post_type && 
               get_post_meta($post_id, 'vortex_ai_generated', true);
    }
    
    /**
     * Generate NFT from an AI image
     *
     * @param int $image_id AI image post ID
     * @param array $nft_metadata Additional NFT metadata
     * @param int $user_id User ID
     * @return int|WP_Error NFT post ID if successful, WP_Error on failure
     */
    public function create_nft_from_image($image_id, $nft_metadata, $user_id = 0) {
        if (!$this->is_ai_image($image_id)) {
            return new WP_Error('invalid_image', __('Not a valid AI image', 'vortex-ai-marketplace'));
        }
        
        // Implementation would connect to NFT creation system
        // This would typically involve blockchain interaction
        
        // For now, just mark the image as prepared for NFT
        update_post_meta($image_id, 'vortex_nft_ready', true);
        update_post_meta($image_id, 'vortex_nft_metadata', $nft_metadata);
        
        // Return the original image ID for now
        // In a real implementation, this would be the NFT post ID
        return $image_id;
    }
    
    /**
     * Associate an artwork with themes
     *
     * @param int $artwork_id The artwork ID
     * @param array $theme_ids Array of theme IDs to associate
     * @param int $user_id User who is making the association
     * @return array Results of the operation
     */
    public function associate_artwork_with_themes($artwork_id, $theme_ids, $user_id = 0) {
        global $wpdb;
        
        if (!$user_id && is_user_logged_in()) {
            $user_id = get_current_user_id();
        }
        
        $results = array(
            'success' => true,
            'added' => 0,
            'errors' => array()
        );
        
        if (empty($theme_ids) || !is_array($theme_ids)) {
            $results['success'] = false;
            $results['errors'][] = __('No themes provided for association', 'vortex-ai-marketplace');
            return $results;
        }
        
        // Verify the artwork exists
        if (!$this->is_ai_image($artwork_id)) {
            $results['success'] = false;
            $results['errors'][] = __('Invalid artwork ID', 'vortex-ai-marketplace');
            return $results;
        }
        
        // Check if the mapping table exists
        $table_name = $wpdb->prefix . 'vortex_artwork_theme_mapping';
        
        try {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
                // Try to create the table if it doesn't exist
                require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-db-migrations.php';
                Vortex_DB_Migrations::ensure_artwork_theme_mapping_table();
                
                // Check again
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
                    $results['success'] = false;
                    $results['errors'][] = __('Theme mapping table does not exist and could not be created', 'vortex-ai-marketplace');
                    return $results;
                }
            }
            
            // Add associations
            foreach ($theme_ids as $theme_id) {
                $theme_id = intval($theme_id);
                if (!$theme_id) continue;
                
                // Check if this association already exists
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE artwork_id = %d AND theme_id = %d",
                    $artwork_id,
                    $theme_id
                ));
                
                if ($exists) {
                    continue; // Skip if already exists
                }
                
                // Add the association
                $inserted = $wpdb->insert(
                    $table_name,
                    array(
                        'artwork_id' => $artwork_id,
                        'theme_id' => $theme_id,
                        'added_by' => $user_id,
                        'created_at' => current_time('mysql')
                    ),
                    array('%d', '%d', '%d', '%s')
                );
                
                if ($inserted) {
                    $results['added']++;
                    
                    // Increment theme's artwork count
                    $themes_table = $wpdb->prefix . 'vortex_artwork_themes';
                    $wpdb->query($wpdb->prepare(
                        "UPDATE $themes_table SET artwork_count = artwork_count + 1 WHERE theme_id = %d",
                        $theme_id
                    ));
                } else {
                    $results['errors'][] = sprintf(__('Failed to associate artwork with theme ID %d', 'vortex-ai-marketplace'), $theme_id);
                }
            }
            
            if ($results['added'] === 0 && !empty($results['errors'])) {
                $results['success'] = false;
            }
            
            return $results;
            
        } catch (Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            return $results;
        }
    }
    
    /**
     * Get themes associated with an artwork
     *
     * @param int $artwork_id The artwork ID
     * @return array Associated themes
     */
    public function get_artwork_themes($artwork_id) {
        global $wpdb;
        
        $themes = array();
        
        // Check if the mapping table exists
        $mapping_table = $wpdb->prefix . 'vortex_artwork_theme_mapping';
        $themes_table = $wpdb->prefix . 'vortex_artwork_themes';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$mapping_table'") !== $mapping_table ||
            $wpdb->get_var("SHOW TABLES LIKE '$themes_table'") !== $themes_table) {
            return $themes;
        }
        
        // Get associated themes
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT t.theme_id, t.theme_name, t.theme_slug, t.theme_description, t.popularity_score, 
                    tm.relevance, tm.created_at as association_date
             FROM $themes_table t
             JOIN $mapping_table tm ON t.theme_id = tm.theme_id
             WHERE tm.artwork_id = %d
             ORDER BY tm.relevance DESC, t.popularity_score DESC",
            $artwork_id
        ));
        
        if ($results) {
            $themes = $results;
        }
        
        return $themes;
    }
    
    /**
     * Remove theme association from an artwork
     *
     * @param int $artwork_id The artwork ID
     * @param int $theme_id The theme ID to remove
     * @return bool Success status
     */
    public function remove_artwork_theme($artwork_id, $theme_id) {
        global $wpdb;
        
        $mapping_table = $wpdb->prefix . 'vortex_artwork_theme_mapping';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$mapping_table'") !== $mapping_table) {
            return false;
        }
        
        $result = $wpdb->delete(
            $mapping_table,
            array(
                'artwork_id' => $artwork_id,
                'theme_id' => $theme_id
            ),
            array('%d', '%d')
        );
        
        if ($result) {
            // Decrement theme's artwork count
            $themes_table = $wpdb->prefix . 'vortex_artwork_themes';
            if ($wpdb->get_var("SHOW TABLES LIKE '$themes_table'") === $themes_table) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE $themes_table SET artwork_count = GREATEST(0, artwork_count - 1) WHERE theme_id = %d",
                    $theme_id
                ));
            }
            return true;
        }
        
        return false;
    }
} 