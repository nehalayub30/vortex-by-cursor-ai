<?php
/**
 * The artwork functionality of the plugin.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The artwork functionality of the plugin.
 *
 * Defines the artwork post type, metadata, and related operations
 * for storing and managing artworks in the marketplace.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Artwork {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Artwork post type.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $post_type    The artwork post type name.
     */
    private $post_type = 'vortex_artwork';

    /**
     * Category taxonomy.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $category_taxonomy    The artwork category taxonomy name.
     */
    private $category_taxonomy = 'artwork_category';

    /**
     * Tag taxonomy.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $tag_taxonomy    The artwork tag taxonomy name.
     */
    private $tag_taxonomy = 'artwork_tag';

    /**
     * AI model taxonomy.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $model_taxonomy    The AI model taxonomy name.
     */
    private $model_taxonomy = 'ai_model';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Initialize hooks
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_' . $this->post_type, array($this, 'save_meta_box_data'));
        add_filter('manage_' . $this->post_type . '_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_' . $this->post_type . '_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('manage_edit-' . $this->post_type . '_sortable_columns', array($this, 'set_sortable_columns'));
        add_filter('post_row_actions', array($this, 'modify_row_actions'), 10, 2);
        add_filter('single_template', array($this, 'single_artwork_template'));
        add_filter('archive_template', array($this, 'archive_artwork_template'));
        add_filter('taxonomy_template', array($this, 'taxonomy_artwork_template'));
        add_action('rest_api_init', array($this, 'register_rest_fields'));
        
        // AJAX actions
        add_action('wp_ajax_vortex_update_artwork_featured', array($this, 'ajax_update_featured'));
        add_action('wp_ajax_vortex_update_artwork_price', array($this, 'ajax_update_price'));
    }

    /**
     * Register post types for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_post_types() {
        // Implementation of register_post_types method
    }

    /**
     * Register taxonomies for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_taxonomies() {
        // Implementation of register_taxonomies method
    }

    /**
     * Add meta boxes for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function add_meta_boxes() {
        // Implementation of add_meta_boxes method
    }

    /**
     * Save meta box data for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function save_meta_box_data($post_id) {
        // Implementation of save_meta_box_data method
    }

    /**
     * Set custom columns for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_custom_columns($columns) {
        // Implementation of set_custom_columns method
        return $columns;
    }

    /**
     * Custom column content for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function custom_column_content($column, $post_id) {
        // Implementation of custom_column_content method
    }

    /**
     * Set sortable columns for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_sortable_columns($columns) {
        // Implementation of set_sortable_columns method
        return $columns;
    }

    /**
     * Modify row actions for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function modify_row_actions($actions, $post) {
        // Implementation of modify_row_actions method
        return $actions;
    }

    /**
     * Single artwork template for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function single_artwork_template($template) {
        // Implementation of single_artwork_template method
        return $template;
    }

    /**
     * Archive artwork template for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function archive_artwork_template($template) {
        // Implementation of archive_artwork_template method
        return $template;
    }

    /**
     * Taxonomy artwork template for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function taxonomy_artwork_template($template) {
        // Implementation of taxonomy_artwork_template method
        return $template;
    }

    /**
     * AJAX update featured for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function ajax_update_featured() {
        // Implementation of ajax_update_featured method
    }

    /**
     * AJAX update price for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function ajax_update_price() {
        // Implementation of ajax_update_price method
    }

    /**
     * Register REST API fields for artwork post type.
     *
     * @since    1.0.0
     */
    public function register_rest_fields() {
        register_rest_field(
            $this->post_type,
            'artwork_meta',
            array(
                'get_callback'    => array($this, 'get_artwork_meta_for_api'),
                'update_callback' => null,
                'schema'          => null,
            )
        );
        
        register_rest_field(
            $this->post_type,
            'artwork_stats',
            array(
                'get_callback'    => array($this, 'get_artwork_stats_for_api'),
                'update_callback' => null,
                'schema'          => null,
            )
        );
        
        register_rest_field(
            $this->post_type,
            'artwork_ai_data',
            array(
                'get_callback'    => array($this, 'get_artwork_ai_data_for_api'),
                'update_callback' => null,
                'schema'          => null,
            )
        );
        
        register_rest_field(
            $this->post_type,
            'artwork_blockchain',
            array(
                'get_callback'    => array($this, 'get_artwork_blockchain_for_api'),
                'update_callback' => null,
                'schema'          => null,
            )
        );
    }
    
    /**
     * Get artwork meta data for REST API.
     *
     * @since    1.0.0
     * @param    array     $object      The post object.
     * @param    string    $field_name  The field name.
     * @param    array     $request     The request data.
     * @return   array                  The artwork meta data.
     */
    public function get_artwork_meta_for_api($object, $field_name, $request) {
        $post_id = $object['id'];
        
        return array(
            'price'             => (float) get_post_meta($post_id, '_vortex_artwork_price', true),
            'tola_price'        => (float) get_post_meta($post_id, '_vortex_tola_price', true),
            'edition_size'      => (int) get_post_meta($post_id, '_vortex_artwork_edition_size', true),
            'dimensions'        => get_post_meta($post_id, '_vortex_artwork_dimensions', true),
            'medium'            => get_post_meta($post_id, '_vortex_artwork_medium', true),
            'is_featured'       => (bool) get_post_meta($post_id, '_vortex_artwork_is_featured', true),
            'is_sold_out'       => (bool) get_post_meta($post_id, '_vortex_artwork_is_sold_out', true),
            'is_limited_edition' => (bool) get_post_meta($post_id, '_vortex_artwork_is_limited_edition', true),
        );
    }
    
    /**
     * Get artwork stats data for REST API.
     *
     * @since    1.0.0
     * @param    array     $object      The post object.
     * @param    string    $field_name  The field name.
     * @param    array     $request     The request data.
     * @return   array                  The artwork stats data.
     */
    public function get_artwork_stats_for_api($object, $field_name, $request) {
        $post_id = $object['id'];
        
        return array(
            'view_count'        => (int) get_post_meta($post_id, '_vortex_artwork_view_count', true),
            'like_count'        => (int) get_post_meta($post_id, '_vortex_artwork_like_count', true),
            'share_count'       => (int) get_post_meta($post_id, '_vortex_artwork_share_count', true),
            'purchase_count'    => (int) get_post_meta($post_id, '_vortex_artwork_purchase_count', true),
        );
    }
    
    /**
     * Get artwork AI data for REST API.
     *
     * @since    1.0.0
     * @param    array     $object      The post object.
     * @param    string    $field_name  The field name.
     * @param    array     $request     The request data.
     * @return   array                  The artwork AI data.
     */
    public function get_artwork_ai_data_for_api($object, $field_name, $request) {
        $post_id = $object['id'];
        
        return array(
            'created_with_huraii' => (bool) get_post_meta($post_id, '_vortex_created_with_huraii', true),
            'ai_prompt'           => get_post_meta($post_id, '_vortex_artwork_ai_prompt', true),
            'ai_negative_prompt'  => get_post_meta($post_id, '_vortex_artwork_ai_negative_prompt', true),
            'ai_model'            => get_post_meta($post_id, '_vortex_artwork_ai_model', true),
            'ai_seed'             => get_post_meta($post_id, '_vortex_artwork_ai_seed', true),
            'ai_guidance_scale'   => (float) get_post_meta($post_id, '_vortex_artwork_ai_guidance_scale', true),
            'ai_steps'            => (int) get_post_meta($post_id, '_vortex_artwork_ai_steps', true),
        );
    }
    
    /**
     * Get artwork blockchain data for REST API.
     *
     * @since    1.0.0
     * @param    array     $object      The post object.
     * @param    string    $field_name  The field name.
     * @param    array     $request     The request data.
     * @return   array                  The artwork blockchain data.
     */
    public function get_artwork_blockchain_for_api($object, $field_name, $request) {
        $post_id = $object['id'];
        
        return array(
            'token_id'           => get_post_meta($post_id, '_vortex_blockchain_token_id', true),
            'contract_address'   => get_post_meta($post_id, '_vortex_blockchain_contract_address', true),
            'blockchain_name'    => get_post_meta($post_id, '_vortex_blockchain_name', true),
        );
    }
} 