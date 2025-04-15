<?php
/**
 * Plugin Name: Vortex AI Marketplace
 * Plugin URI: https://www.vortex-ai.com/marketplace
 * Description: An AI-powered marketplace featuring HURAII, CLOE, Business Strategist, and TOLA agents with deep learning capabilities, blockchain integration, and secure admin controls.
 * Version: 1.0.0
 * Author: Vortex AI
 * Author URI: https://www.vortex-ai.com
 * Text Domain: vortex-marketplace
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VORTEX_MARKETPLACE_VERSION', '1.0.0');
define('VORTEX_MARKETPLACE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VORTEX_MARKETPLACE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VORTEX_MARKETPLACE_PLUGIN_FILE', __FILE__);

/**
 * Main VORTEX_Marketplace class
 */
class VORTEX_Marketplace {
    /**
     * Instance of this class
     * @var VORTEX_Marketplace
     */
    private static $instance = null;
    
    /**
     * Constructor
     */
    private function __construct() {
        // Include required files
        $this->includes();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Get instance of this class
     * @return VORTEX_Marketplace
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core files
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-ai-initializer.php';
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-assets-manager.php';
        
        // Security
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'security/class-vortex-command-control.php';
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'security/class-vortex-security.php';
        
        // Deep learning
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/deep-learning/class-vortex-deep-learning.php';
        
        // AI Agents
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/ai/class-vortex-huraii-persona.php';
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/ai/class-vortex-cloe-ad-system.php';
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/ai/class-vortex-business-strategist.php';
        
        // Blockchain
        require_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'includes/blockchain/class-vortex-tola-integration.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register activation/deactivation hooks
        register_activation_hook(VORTEX_MARKETPLACE_PLUGIN_FILE, array('VORTEX_AI_Initializer', 'activate_plugin'));
        register_deactivation_hook(VORTEX_MARKETPLACE_PLUGIN_FILE, array('VORTEX_AI_Initializer', 'deactivate_plugin'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register post types and taxonomies
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        
        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Add REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Front-end assets
        add_action('wp_enqueue_scripts', array($this, 'register_frontend_assets'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Vortex Marketplace', 'vortex-marketplace'),
            __('Vortex AI', 'vortex-marketplace'),
            'manage_options',
            'vortex-marketplace',
            array($this, 'render_admin_page'),
            'dashicons-art',
            30
        );
        
        // Add submenus - these will be overridden by the respective classes
        add_submenu_page(
            'vortex-marketplace',
            __('Dashboard', 'vortex-marketplace'),
            __('Dashboard', 'vortex-marketplace'),
            'manage_options',
            'vortex-marketplace',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        include_once VORTEX_MARKETPLACE_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Register post types
     */
    public function register_post_types() {
        // Register artwork post type
        register_post_type('vortex_artwork', array(
            'labels' => array(
                'name'                  => _x('Artworks', 'Post type general name', 'vortex-marketplace'),
                'singular_name'         => _x('Artwork', 'Post type singular name', 'vortex-marketplace'),
                'menu_name'             => _x('Artworks', 'Admin Menu text', 'vortex-marketplace'),
                'name_admin_bar'        => _x('Artwork', 'Add New on Toolbar', 'vortex-marketplace'),
                'add_new'               => __('Add New', 'vortex-marketplace'),
                'add_new_item'          => __('Add New Artwork', 'vortex-marketplace'),
                'new_item'              => __('New Artwork', 'vortex-marketplace'),
                'edit_item'             => __('Edit Artwork', 'vortex-marketplace'),
                'view_item'             => __('View Artwork', 'vortex-marketplace'),
                'all_items'             => __('All Artworks', 'vortex-marketplace'),
                'search_items'          => __('Search Artworks', 'vortex-marketplace'),
                'not_found'             => __('No artworks found.', 'vortex-marketplace'),
                'not_found_in_trash'    => __('No artworks found in Trash.', 'vortex-marketplace'),
                'featured_image'        => _x('Artwork Image', 'Overrides the "Featured Image" phrase', 'vortex-marketplace'),
                'set_featured_image'    => _x('Set artwork image', 'Overrides the "Set featured image" phrase', 'vortex-marketplace'),
                'remove_featured_image' => _x('Remove artwork image', 'Overrides the "Remove featured image" phrase', 'vortex-marketplace'),
                'use_featured_image'    => _x('Use as artwork image', 'Overrides the "Use as featured image" phrase', 'vortex-marketplace'),
            ),
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'artwork'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
            'show_in_rest'        => true,
        ));
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Register artwork style taxonomy
        register_taxonomy('vortex_artwork_style', 'vortex_artwork', array(
            'labels' => array(
                'name'                       => _x('Styles', 'taxonomy general name', 'vortex-marketplace'),
                'singular_name'              => _x('Style', 'taxonomy singular name', 'vortex-marketplace'),
                'search_items'               => __('Search Styles', 'vortex-marketplace'),
                'popular_items'              => __('Popular Styles', 'vortex-marketplace'),
                'all_items'                  => __('All Styles', 'vortex-marketplace'),
                'parent_item'                => null,
                'parent_item_colon'          => null,
                'edit_item'                  => __('Edit Style', 'vortex-marketplace'),
                'update_item'                => __('Update Style', 'vortex-marketplace'),
                'add_new_item'               => __('Add New Style', 'vortex-marketplace'),
                'new_item_name'              => __('New Style Name', 'vortex-marketplace'),
                'separate_items_with_commas' => __('Separate styles with commas', 'vortex-marketplace'),
                'add_or_remove_items'        => __('Add or remove styles', 'vortex-marketplace'),
                'choose_from_most_used'      => __('Choose from the most used styles', 'vortex-marketplace'),
                'not_found'                  => __('No styles found.', 'vortex-marketplace'),
                'menu_name'                  => __('Styles', 'vortex-marketplace'),
            ),
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'artwork-style'),
            'show_in_rest'      => true,
        ));
        
        // Register artwork theme taxonomy
        register_taxonomy('vortex_artwork_theme', 'vortex_artwork', array(
            'labels' => array(
                'name'                       => _x('Themes', 'taxonomy general name', 'vortex-marketplace'),
                'singular_name'              => _x('Theme', 'taxonomy singular name', 'vortex-marketplace'),
                'search_items'               => __('Search Themes', 'vortex-marketplace'),
                'popular_items'              => __('Popular Themes', 'vortex-marketplace'),
                'all_items'                  => __('All Themes', 'vortex-marketplace'),
                'parent_item'                => null,
                'parent_item_colon'          => null,
                'edit_item'                  => __('Edit Theme', 'vortex-marketplace'),
                'update_item'                => __('Update Theme', 'vortex-marketplace'),
                'add_new_item'               => __('Add New Theme', 'vortex-marketplace'),
                'new_item_name'              => __('New Theme Name', 'vortex-marketplace'),
                'separate_items_with_commas' => __('Separate themes with commas', 'vortex-marketplace'),
                'add_or_remove_items'        => __('Add or remove themes', 'vortex-marketplace'),
                'choose_from_most_used'      => __('Choose from the most used themes', 'vortex-marketplace'),
                'not_found'                  => __('No themes found.', 'vortex-marketplace'),
                'menu_name'                  => __('Themes', 'vortex-marketplace'),
            ),
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'artwork-theme'),
            'show_in_rest'      => true,
        ));
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('vortex-marketplace', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Register REST API endpoint for artwork metadata
        register_rest_route('vortex/v1', '/metadata/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_artwork_metadata'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Get artwork metadata
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response REST response
     */
    public function get_artwork_metadata($request) {
        global $wpdb;
        
        $metadata_id = $request->get_param('id');
        
        $table_name = $wpdb->prefix . 'vortex_artwork_metadata';
        $metadata = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $metadata_id
        ));
        
        if (!$metadata) {
            return new WP_REST_Response(array('error' => 'Metadata not found'), 404);
        }
        
        // Parse metadata
        $artwork_data = json_decode($metadata->metadata, true);
        
        // Add blockchain verification data if available
        $artwork_id = $metadata->artwork_id;
        $contract_id = get_post_meta($artwork_id, 'vortex_tola_contract_id', true);
        
        if ($contract_id && strpos($contract_id, 'pending_') === false) {
            $artwork_data['blockchain_verification'] = array(
                'contract_id' => $contract_id,
                'contract_hash' => get_post_meta($artwork_id, 'vortex_tola_contract_hash', true),
                'contract_url' => get_post_meta($artwork_id, 'vortex_tola_contract_url', true),
                'verified' => true
            );
        }
        
        return new WP_REST_Response($artwork_data, 200);
    }
    
    /**
     * Register front-end assets
     */
    public function register_frontend_assets() {
        // Core plugin assets that should be available on all pages
        // Note: Most assets will be enqueued by individual shortcodes when needed
        wp_enqueue_style('vortex-core', VORTEX_MARKETPLACE_PLUGIN_URL . 'assets/css/vortex-dao.css', array(), VORTEX_MARKETPLACE_VERSION);
    }
}

// Initialize the plugin
function vortex_marketplace() {
    return VORTEX_Marketplace::get_instance();
}

// Start the plugin
vortex_marketplace(); 