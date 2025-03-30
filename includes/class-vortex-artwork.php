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
} 