<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks for
 * enqueuing the admin-specific stylesheet and JavaScript.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Admin {

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
     * Admin pages array.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $admin_pages    Array of admin pages.
     */
    private $admin_pages = array();

    /**
     * Admin page tabs array.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $admin_tabs    Array of admin page tabs.
     */
    private $admin_tabs = array();

    /**
     * The marketplace instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Marketplace    $marketplace    The marketplace instance.
     */
    private $marketplace;

    /**
     * The artists manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Artists    $artists    The artists manager instance.
     */
    private $artists;

    /**
     * The artwork manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Artwork    $artwork    The artwork manager instance.
     */
    private $artwork;

    /**
     * The blockchain API instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Blockchain_API    $blockchain_api    The blockchain API instance.
     */
    private $blockchain_api;

    /**
     * The TOLA token instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Tola    $tola    The TOLA token instance.
     */
    private $tola;

    /**
     * The Huraii AI instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Huraii    $huraii    The Huraii AI instance.
     */
    private $huraii;

    /**
     * The Image-to-Image processor instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Img2img    $img2img    The Image-to-Image processor instance.
     */
    private $img2img;

    /**
     * The metrics manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Metrics    $metrics    The metrics manager instance.
     */
    private $metrics;

    /**
     * The analytics processor instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Analytics    $analytics    The analytics processor instance.
     */
    private $analytics;

    /**
     * The rankings manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Rankings    $rankings    The rankings manager instance.
     */
    private $rankings;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name    The name of this plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Setup admin hooks
        $this->setup_admin_hooks();
        
        // Setup admin pages
        $this->setup_admin_pages();
    }

    /**
     * Register the hooks for the admin area.
     *
     * @since    1.0.0
     */
    private function setup_admin_hooks() {
        // Admin menu
        add_action('admin_menu', array($this, 'register_admin_menu'));
        
        // Admin notices
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        // Admin AJAX handlers
        add_action('wp_ajax_vortex_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_vortex_reset_settings', array($this, 'ajax_reset_settings'));
        add_action('wp_ajax_vortex_dismiss_notice', array($this, 'ajax_dismiss_notice'));
        add_action('wp_ajax_vortex_load_dashboard_data', array($this, 'ajax_load_dashboard_data'));
        add_action('wp_ajax_vortex_get_metrics_data', array($this, 'ajax_get_metrics_data'));
        add_action('wp_ajax_vortex_get_rankings_data', array($this, 'ajax_get_rankings_data'));
        add_action('wp_ajax_vortex_recalculate_rankings', array($this, 'ajax_recalculate_rankings'));
        add_action('wp_ajax_vortex_verify_artist', array($this, 'ajax_verify_artist'));
        add_action('wp_ajax_vortex_update_artist_status', array($this, 'ajax_update_artist_status'));
        add_action('wp_ajax_vortex_get_shortcode_preview', array($this, 'ajax_get_shortcode_preview'));
        add_action('wp_ajax_vortex_update_database', array($this, 'ajax_update_database'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Dashboard widgets
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        
        // TinyMCE buttons
        add_filter('mce_buttons', array($this, 'register_mce_buttons'));
        add_filter('mce_external_plugins', array($this, 'register_mce_plugins'));
        add_action('admin_head', array($this, 'add_tinymce_styles'));
        add_action('admin_enqueue_scripts', array($this, 'register_mce_javascript'));

        // Add the database repair page
        add_action('admin_menu', array($this, 'add_database_repair_page'));
    }

    /**
     * Setup admin pages and tabs.
     *
     * @since    1.0.0
     */
    private function setup_admin_pages() {
        // Main dashboard page
        $this->admin_pages['dashboard'] = array(
            'title' => __('Dashboard', 'vortex-ai-marketplace'),
            'menu_title' => __('VORTEX AI Market', 'vortex-ai-marketplace'),
            'capability' => 'manage_options',
            'slug' => 'vortex-dashboard',
            'icon' => 'dashicons-store',
            'position' => 30,
            'tabs' => false,
        );
        
        // Artworks page
        $this->admin_pages['artworks'] = array(
            'title' => __('Artworks', 'vortex-ai-marketplace'),
            'menu_title' => __('Artworks', 'vortex-ai-marketplace'),
            'capability' => 'edit_posts',
            'slug' => 'edit.php?post_type=vortex_artwork',
            'is_submenu' => false,
            'external' => true,
        );
        
        // Artists page
        $this->admin_pages['artists'] = array(
            'title' => __('Artists', 'vortex-ai-marketplace'),
            'menu_title' => __('Artists', 'vortex-ai-marketplace'),
            'capability' => 'edit_posts',
            'slug' => 'vortex-artists',
            'is_submenu' => true,
            'parent_slug' => 'vortex-dashboard',
            'tabs' => false,
        );
        
        // Orders page
        $this->admin_pages['orders'] = array(
            'title' => __('Orders', 'vortex-ai-marketplace'),
            'menu_title' => __('Orders', 'vortex-ai-marketplace'),
            'capability' => 'edit_posts',
            'slug' => 'vortex-orders',
            'is_submenu' => true,
            'parent_slug' => 'vortex-dashboard',
            'tabs' => false,
        );
        
        // Settings page
        $this->admin_pages['settings'] = array(
            'title' => __('Settings', 'vortex-ai-marketplace'),
            'menu_title' => __('Settings', 'vortex-ai-marketplace'),
            'capability' => 'manage_options',
            'slug' => 'vortex-settings',
            'is_submenu' => true,
            'parent_slug' => 'vortex-dashboard',
            'tabs' => true,
        );
        
        // Tools page
        $this->admin_pages['tools'] = array(
            'title' => __('Tools', 'vortex-ai-marketplace'),
            'menu_title' => __('Tools', 'vortex-ai-marketplace'),
            'capability' => 'manage_options',
            'slug' => 'vortex-tools',
            'is_submenu' => true,
            'parent_slug' => 'vortex-dashboard',
            'tabs' => true,
        );
        
        // Status page
        $this->admin_pages['status'] = array(
            'title' => __('Status', 'vortex-ai-marketplace'),
            'menu_title' => __('Status', 'vortex-ai-marketplace'),
            'capability' => 'manage_options',
            'slug' => 'vortex-status',
            'is_submenu' => true,
            'parent_slug' => 'vortex-dashboard',
            'tabs' => true,
        );
        
        // Setup tabs for settings page
        $this->admin_tabs['settings'] = array(
            'general' => __('General', 'vortex-ai-marketplace'),
            'artwork' => __('Artwork', 'vortex-ai-marketplace'),
            'artists' => __('Artists', 'vortex-ai-marketplace'),
            'payments' => __('Payments', 'vortex-ai-marketplace'),
            'blockchain' => __('Blockchain', 'vortex-ai-marketplace'),
            'ai_models' => __('AI Models', 'vortex-ai-marketplace'),
            'advanced' => __('Advanced', 'vortex-ai-marketplace'),
        );
        
        // Setup tabs for tools page
        $this->admin_tabs['tools'] = array(
            'import_export' => __('Import/Export', 'vortex-ai-marketplace'),
            'maintenance' => __('Maintenance', 'vortex-ai-marketplace'),
        );
        
        // Setup tabs for status page
        $this->admin_tabs['status'] = array(
            'system_info' => __('System Info', 'vortex-ai-marketplace'),
            'logs' => __('Logs', 'vortex-ai-marketplace'),
        );
    }

    /**
     * Register the admin menu and submenu pages.
     *
     * @since    1.0.0
     */
    public function register_admin_menu() {
        foreach ($this->admin_pages as $page_id => $page) {
            if (isset($page['external']) && $page['external']) {
                // Skip external pages
                continue;
            }
            
            if (!isset($page['is_submenu']) || !$page['is_submenu']) {
                // Add main menu page
                add_menu_page(
                    $page['title'],
                    $page['menu_title'],
                    $page['capability'],
                    $page['slug'],
                    array($this, 'display_' . $page_id . '_page'),
                    isset($page['icon']) ? $page['icon'] : '',
                    isset($page['position']) ? $page['position'] : null
                );
            } else {
                // Add submenu page
                add_submenu_page(
                    $page['parent_slug'],
                    $page['title'],
                    $page['menu_title'],
                    $page['capability'],
                    $page['slug'],
                    array($this, 'display_' . $page_id . '_page')
                );
            }
        }
    }

    /**
     * Register all settings fields.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // General settings
        register_setting('vortex_general_settings', 'vortex_marketplace_title');
        register_setting('vortex_general_settings', 'vortex_marketplace_description');
        register_setting('vortex_general_settings', 'vortex_currency', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('vortex_general_settings', 'vortex_currency_symbol', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('vortex_general_settings', 'vortex_commission_rate', array('sanitize_callback' => array($this, 'sanitize_percentage')));
        register_setting('vortex_general_settings', 'vortex_featured_items_count', array('sanitize_callback' => 'absint'));
        register_setting('vortex_general_settings', 'vortex_enable_reviews', array('sanitize_callback' => 'absint'));
        
        // Artwork settings
        register_setting('vortex_artwork_settings', 'vortex_artwork_default_license', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('vortex_artwork_settings', 'vortex_artwork_default_copyright', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('vortex_artwork_settings', 'vortex_artwork_enable_editions', array('sanitize_callback' => 'absint'));
        register_setting('vortex_artwork_settings', 'vortex_artwork_default_editions', array('sanitize_callback' => 'absint'));
        register_setting('vortex_artwork_settings', 'vortex_artwork_min_price', array('sanitize_callback' => 'floatval'));
        register_setting('vortex_artwork_settings', 'vortex_artwork_max_resolution', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('vortex_artwork_settings', 'vortex_artwork_allowed_formats', array('sanitize_callback' => array($this, 'sanitize_array')));
        
        // Artists settings
        register_setting('vortex_artists_settings', 'vortex_artist_verification_required', array('sanitize_callback' => 'absint'));
        register_setting('vortex_artists_settings', 'vortex_artist_auto_approval', array('sanitize_callback' => 'absint'));
        register_setting('vortex_artists_settings', 'vortex_artist_default_commission', array('sanitize_callback' => array($this, 'sanitize_percentage')));
        register_setting('vortex_artists_settings', 'vortex_artist_featured_count', array('sanitize_callback' => 'absint'));
        register_setting('vortex_artists_settings', 'vortex_artist_min_artworks', array('sanitize_callback' => 'absint'));
        
        // Payments settings
        register_setting('vortex_payments_settings', 'vortex_payments_methods', array('sanitize_callback' => array($this, 'sanitize_array')));
        register_setting('vortex_payments_settings', 'vortex_payments_default_method', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('vortex_payments_settings', 'vortex_payments_tola_only', array('sanitize_callback' => 'absint'));
        register_setting('vortex_payments_settings', 'vortex_payments_tola_discount', array('sanitize_callback' => array($this, 'sanitize_percentage')));
        register_setting('vortex_payments_settings', 'vortex_payments_wallet_prefix', array('sanitize_callback' => 'sanitize_text_field'));
        
        // Blockchain settings
        register_setting('vortex_blockchain_settings', 'vortex_blockchain_network', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('vortex_blockchain_settings', 'vortex_marketplace_wallet_address', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('vortex_blockchain_settings', 'vortex_tola_contract_address', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('vortex_blockchain_settings', 'vortex_nft_contract_address', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('vortex_blockchain_settings', 'vortex_default_royalty', array('sanitize_callback' => array($this, 'sanitize_percentage')));
        
        // AI models settings
        register_setting('vortex_ai_models_settings', 'vortex_ai_models_enabled', array('sanitize_callback' => array($this, 'sanitize_array')));
        register_setting('vortex_ai_models_settings', 'vortex_ai_default_model', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('vortex_ai_models_settings', 'vortex_ai_huraii_endpoint', array('sanitize_callback' => 'esc_url_raw'));
        register_setting('vortex_ai_models_settings', 'vortex_ai_huraii_api_key', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('vortex_ai_models_settings', 'vortex_ai_img2img_enabled', array('sanitize_callback' => 'absint'));
        register_setting('vortex_ai_models_settings', 'vortex_ai_img2img_endpoint', array('sanitize_callback' => 'esc_url_raw'));
        
        // Advanced settings
        register_setting('vortex_advanced_settings', 'vortex_debug_mode', array('sanitize_callback' => 'absint'));
        register_setting('vortex_advanced_settings', 'vortex_cache_expiration', array('sanitize_callback' => 'absint'));
        register_setting('vortex_advanced_settings', 'vortex_metrics_collection_interval', array('sanitize_callback' => 'absint'));
        register_setting('vortex_advanced_settings', 'vortex_rankings_update_interval', array('sanitize_callback' => 'absint'));
        register_setting('vortex_advanced_settings', 'vortex_max_image_upload_size', array('sanitize_callback' => 'absint'));

        // Add a Database Tools section in the admin settings
        $this->add_database_tools_section();
    }

    /**
     * Add a Database Tools section in the admin settings
     */
    public function add_database_tools_section() {
        add_settings_section(
            'vortex_database_tools_section',
            __('Database Tools', 'vortex-ai-marketplace'),
            array($this, 'render_database_tools_section'),
            'vortex_advanced_settings'
        );
        
        add_settings_field(
            'vortex_update_database',
            __('Update Database', 'vortex-ai-marketplace'),
            array($this, 'render_update_database_field'),
            'vortex_advanced_settings',
            'vortex_database_tools_section'
        );
    }

    /**
     * Render the database tools section
     */
    public function render_database_tools_section() {
        echo '<p>' . __('Database maintenance tools for the VORTEX AI Marketplace.', 'vortex-ai-marketplace') . '</p>';
    }

    /**
     * Render the update database field
     */
    public function render_update_database_field() {
        echo '<button type="button" class="button button-secondary" id="vortex-update-database">' . __('Run Database Update', 'vortex-ai-marketplace') . '</button>';
        echo '<p class="description">' . __('This will create or update any missing database tables required by the plugin.', 'vortex-ai-marketplace') . '</p>';
        echo '<div id="vortex-database-update-result" style="margin-top: 10px;"></div>';
        
        // Add inline script for AJAX call
        wp_add_inline_script('vortex-admin-js', '
            jQuery(document).ready(function($) {
                $("#vortex-update-database").on("click", function() {
                    $(this).prop("disabled", true);
                    $("#vortex-database-update-result").html("<p><em>' . __('Updating database...', 'vortex-ai-marketplace') . '</em></p>");
                    
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "vortex_update_database",
                            nonce: "' . wp_create_nonce('vortex_update_database_nonce') . '"
                        },
                        success: function(response) {
                            if (response.success) {
                                $("#vortex-database-update-result").html("<p class=\"notice notice-success\">" + response.data.message + "</p>");
                            } else {
                                $("#vortex-database-update-result").html("<p class=\"notice notice-error\">" + response.data.message + "</p>");
                            }
                            $("#vortex-update-database").prop("disabled", false);
                        },
                        error: function() {
                            $("#vortex-database-update-result").html("<p class=\"notice notice-error\">' . __('An error occurred while updating the database.', 'vortex-ai-marketplace') . '</p>");
                            $("#vortex-update-database").prop("disabled", false);
                        }
                    });
                });
            });
        ');
    }

    /**
     * Register styles for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        /**
         * An instance of this class should be passed to the run() function
         * defined in Vortex_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Vortex_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        $screen = get_current_screen();
        
        // Main admin styles for all admin pages
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/vortex-admin.css', array(), $this->version, 'all');
        
        // Dashboard specific styles
        if ($screen && $screen->id === 'toplevel_page_vortex-dashboard') {
            wp_enqueue_style($this->plugin_name . '-dashboard', plugin_dir_url(__FILE__) . 'css/vortex-dashboard.css', array(), $this->version, 'all');
        }
        
        // Artwork edit page styles
        if ($screen && $screen->id === 'vortex_artwork') {
            wp_enqueue_style($this->plugin_name . '-post-edit', plugin_dir_url(__FILE__) . 'css/vortex-post-edit.css', array(), $this->version, 'all');
            wp_enqueue_style('wp-color-picker');
        }
        
        // Settings page styles
        if ($screen && strpos($screen->id, 'vortex-settings') !== false) {
            wp_enqueue_style('wp-color-picker');
        }
        
        // Metrics dashboard styles
        if ($screen && strpos($screen->id, 'page_vortex-tools') !== false) {
            wp_enqueue_style($this->plugin_name . '-metrics', plugin_dir_url(__FILE__) . 'css/vortex-metrics-dashboard.css', array(), $this->version, 'all');
        }
        
        // Language admin styles
        if ($screen && (strpos($screen->id, 'page_vortex-settings') !== false || strpos($screen->id, 'page_vortex-status') !== false)) {
            wp_enqueue_style($this->plugin_name . '-language', plugin_dir_url(__FILE__) . 'css/vortex-language-admin.css', array(), $this->version, 'all');
        }
    }

    /**
     * Register JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        /**
         * An instance of this class should be passed to the run() function
         * defined in Vortex_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Vortex_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        $screen = get_current_screen();
        
        // Main admin scripts for all admin pages
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/vortex-admin.js', array('jquery', 'jquery-ui-sortable', 'jquery-ui-dialog'), $this->version, false);
        
        wp_localize_script($this->plugin_name, 'vortex_admin', array(
            'ajaxurl' => admin_url('ajax.php'),
            'nonce' => wp_create_nonce('vortex_admin_nonce'),
            'confirm_delete' => __('Are you sure you want to delete this item? This action cannot be undone.', 'vortex-ai-marketplace'),
            'confirm_reset' => __('Are you sure you want to reset settings to defaults? This action cannot be undone.', 'vortex-ai-marketplace'),
            'saving' => __('Saving...', 'vortex-ai-marketplace'),
            'save_success' => __('Settings saved successfully!', 'vortex-ai-marketplace'),
            'save_error' => __('Error saving settings. Please try again.', 'vortex-ai-marketplace'),
        ));
        
        // Dashboard specific scripts
        if ($screen && $screen->id === 'toplevel_page_vortex-dashboard') {
            wp_enqueue_script($this->plugin_name . '-dashboard', plugin_dir_url(__FILE__) . 'js/vortex-dashboard.js', array('jquery', $this->plugin_name), $this->version, false);
        }
        
        // Artwork edit page scripts
        if ($screen && $screen->id === 'vortex_artwork') {
            wp_enqueue_script($this->plugin_name . '-post-edit', plugin_dir_url(__FILE__) . 'js/vortex-post-edit.js', array('jquery', 'wp-color-picker', $this->plugin_name), $this->version, false);
        }
        
        // Settings page scripts
        if ($screen && strpos($screen->id, 'vortex-settings') !== false) {
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_media();
        }
        
        // Metrics dashboard scripts
        if ($screen && strpos($screen->id, 'page_vortex-tools') !== false) {
            wp_enqueue_script($this->plugin_name . '-metrics', plugin_dir_url(__FILE__) . 'js/vortex-metrics-dashboard.js', array('jquery', $this->plugin_name), $this->version, false);
        }
        
        // Language admin scripts
        if ($screen && (strpos($screen->id, 'page_vortex-settings') !== false || strpos($screen->id, 'page_vortex-status') !== false)) {
            wp_enqueue_script($this->plugin_name . '-language', plugin_dir_url(__FILE__) . 'js/vortex-language-admin.js', array('jquery', $this->plugin_name), $this->version, false);
        }
    }

    /**
     * Display the dashboard page.
     *
     * @since    1.0.0
     */
    public function display_dashboard_page() {
        include plugin_dir_path(__FILE__) . 'partials/vortex-admin-dashboard.php';
    }

    /**
     * Display the artists page.
     *
     * @since    1.0.0
     */
    public function display_artists_page() {
        include plugin_dir_path(__FILE__) . 'partials/vortex-admin-artists.php';
    }

    /**
     * Display the orders page.
     *
     * @since    1.0.0
     */
    public function display_orders_page() {
        include plugin_dir_path(__FILE__) . 'partials/vortex-admin-orders.php';
    }

    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        
        // Include the settings page template
        include plugin_dir_path(__FILE__) . 'partials/vortex-admin-settings.php';
    }

    /**
     * Display the tools page.
     *
     * @since    1.0.0
     */
    public function display_tools_page() {
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'import_export';
        
        // Include the tools page template
        include plugin_dir_path(__FILE__) . 'partials/vortex-admin-tools.php';
    }

    /**
     * Display the status page.
     *
     * @since    1.0.0
     */
    public function display_status_page() {
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'system_info';
        
        // Include the status page template
        include plugin_dir_path(__FILE__) . 'partials/vortex-admin-status.php';
    }

    /**
     * Display admin notices.
     *
     * @since    1.0.0
     */
    public function display_admin_notices() {
        // Check for notices in the transient
        $notices = get_transient('vortex_admin_notices');
        
        if ($notices && is_array($notices)) {
            foreach ($notices as $notice_id => $notice) {
                // Check if notice is dismissed
                $dismissed_notices = get_option('vortex_dismissed_notices', array());
                if (isset($dismissed_notices[$notice_id])) {
                    continue;
                }
                
                $class = isset($notice['class']) ? $notice['class'] : 'notice-info';
                $message = isset($notice['message']) ? $notice['message'] : '';
                $dismissible = isset($notice['dismissible']) && $notice['dismissible'] ? 'is-dismissible' : '';
                
                printf(
                    '<div class="notice %1$s %2$s" data-notice-id="%3$s"><p>%4$s</p></div>',
                    esc_attr($class),
                    esc_attr($dismissible),
                    esc_attr($notice_id),
                    $message // Intentionally not escaped as it may contain HTML
                );
            }
        }
    }

    /**
     * Add dashboard widgets.
     *
     * @since    1.0.0
     */
    public function add_dashboard_widgets() {
        wp_add_dashboard_widget(
            'vortex_marketplace_summary',
            __('VORTEX AI Marketplace Summary', 'vortex-ai-marketplace'),
            array($this, 'render_dashboard_summary_widget')
        );
    }

    /**
     * Render dashboard summary widget.
     *
     * @since    1.0.0
     */
    public function render_dashboard_summary_widget() {
        include plugin_dir_path(__FILE__) . 'partials/widgets/dashboard-summary-widget.php';
    }

    /**
     * AJAX handler for saving settings.
     *
     * @since    1.0.0
     */
    public function ajax_save_settings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'vortex-ai-marketplace')));
        }
        
        $settings_group = isset($_POST['group']) ? sanitize_key($_POST['group']) : '';
        $settings_data = isset($_POST['data']) ? $_POST['data'] : array();
        
        if (empty($settings_group)) {
            wp_send_json_error(array('message' => __('Settings group is required.', 'vortex-ai-marketplace')));
        }
        
        // Process the settings
        foreach ($settings_data as $option_name => $option_value) {
            update_option($option_name, $option_value);
        }
        
        wp_send_json_success(array('message' => __('Settings saved successfully!', 'vortex-ai-marketplace')));
    }

    /**
     * AJAX handler for resetting settings.
     *
     * @since    1.0.0
     */
    public function ajax_reset_settings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'vortex-ai-marketplace')));
        }
        
        $settings_group = isset($_POST['group']) ? sanitize_key($_POST['group']) : '';
        
        if (empty($settings_group)) {
            wp_send_json_error(array('message' => __('Settings group is required.', 'vortex-ai-marketplace')));
        }
        
        // Reset settings based on group
        switch ($settings_group) {
            case 'general':
                $this->reset_general_settings();
                break;
                
            case 'artwork':
                $this->reset_artwork_settings();
                break;
                
            case 'artists':
                $this->reset_artists_settings();
                break;
                
            case 'payments':
                $this->reset_payments_settings();
                break;
                
            case 'blockchain':
                $this->reset_blockchain_settings();
                break;
                
            case 'ai_models':
                $this->reset_ai_models_settings();
                break;
                
            case 'advanced':
                $this->reset_advanced_settings();
                break;
                
            default:
                wp_send_json_error(array('message' => __('Invalid settings group.', 'vortex-ai-marketplace')));
                break;
        }
        
        wp_send_json_success(array('message' => __('Settings reset to defaults!', 'vortex-ai-marketplace')));
    }

    /**
     * Reset general settings to defaults.
     *
     * @since    1.0.0
     */
    private function reset_general_settings() {
        update_option('vortex_marketplace_title', 'VORTEX AI Marketplace');
        update_option('vortex_marketplace_description', 'AI-Generated Artwork Marketplace');
        update_option('vortex_currency', 'TOLA');
        update_option('vortex_currency_symbol', 'T');
        update_option('vortex_commission_rate', 10);
        update_option('vortex_featured_items_count', 6);
        update_option('vortex_enable_reviews', 1);
    }

    /**
     * Reset artwork settings to defaults.
     *
     * @since    1.0.0
     */
    private function reset_artwork_settings() {
        update_option('vortex_artwork_default_license', 'Standard License');
        update_option('vortex_artwork_default_copyright', 'All Rights Reserved');
        update_option('vortex_artwork_enable_editions', 1);
        update_option('vortex_artwork_default_editions', 1);
        update_option('vortex_artwork_min_price', 10);
        update_option('vortex_artwork_max_resolution', '4096x4096');
        update_option('vortex_artwork_allowed_formats', array('jpg', 'png', 'gif', 'webp'));
    }

    /**
     * Reset artists settings to defaults.
     *
     * @since    1.0.0
     */
    private function reset_artists_settings() {
        update_option('vortex_artist_verification_required', 1);
        update_option('vortex_artist_auto_approval', 0);
        update_option('vortex_artist_default_commission', 80);
        update_option('vortex_artist_featured_count', 4);
        update_option('vortex_artist_min_artworks', 1);
    }

    /**
     * Reset payments settings to defaults.
     *
     * @since    1.0.0
     */
    private function reset_payments_settings() {
        update_option('vortex_payments_methods', array('tola', 'credit_card'));
        update_option('vortex_payments_default_method', 'tola');
        update_option('vortex_payments_tola_only', 0);
        update_option('vortex_payments_tola_discount', 10);
        update_option('vortex_payments_wallet_prefix', 'vortex_');
    }

    /**
     * Reset blockchain settings to defaults.
     *
     * @since    1.0.0
     */
    private function reset_blockchain_settings() {
        update_option('vortex_blockchain_network', 'solana');
        update_option('vortex_marketplace_wallet_address', '');
        update_option('vortex_tola_contract_address', '');
        update_option('vortex_nft_contract_address', '');
        update_option('vortex_default_royalty', 10);
    }

    /**
     * Reset AI models settings to defaults.
     *
     * @since    1.0.0
     */
    private function reset_ai_models_settings() {
        update_option('vortex_ai_models_enabled', array('huraii', 'stable_diffusion', 'midjourney'));
        update_option('vortex_ai_default_model', 'huraii');
        update_option('vortex_ai_huraii_endpoint', '');
        update_option('vortex_ai_huraii_api_key', '');
        update_option('vortex_ai_img2img_enabled', 1);
        update_option('vortex_ai_img2img_endpoint', '');
    }

    /**
     * Reset advanced settings to defaults.
     *
     * @since    1.0.0
     */
    private function reset_advanced_settings() {
        update_option('vortex_debug_mode', 0);
        update_option('vortex_cache_expiration', 86400); // 24 hours
        update_option('vortex_metrics_collection_interval', 3600); // 1 hour
        update_option('vortex_rankings_update_interval', 86400); // 24 hours
        update_option('vortex_max_image_upload_size', 10); // 10 MB
    }

    /**
     * AJAX handler for dismissing admin notices.
     *
     * @since    1.0.0
     */
    public function ajax_dismiss_notice() {
        // Check
    }

    /**
     * Register custom TinyMCE buttons.
     *
     * @since    1.0.0
     * @param    array    $buttons    Array of existing buttons.
     * @return   array                Modified array of buttons.
     */
    public function register_mce_buttons($buttons) {
        // Add custom buttons
        array_push($buttons, 'separator', 'vortex_artwork_shortcode', 'vortex_artist_shortcode');
        return $buttons;
    }

    /**
     * Register custom TinyMCE plugins.
     *
     * @since    1.0.0
     * @param    array    $plugin_array    Array of existing plugins.
     * @return   array                     Modified array of plugins.
     */
    public function register_mce_plugins($plugin_array) {
        // Add custom plugins
        $plugin_array['vortex_shortcodes'] = plugin_dir_url(__FILE__) . 'js/vortex-tinymce-plugin.js';
        return $plugin_array;
    }

    /**
     * Add custom styles for TinyMCE buttons.
     *
     * @since    1.0.0
     */
    public function add_tinymce_styles() {
        // Only add styles on post/page edit screens
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->base, array('post', 'page'))) {
            return;
        }
        
        ?>
        <style type="text/css">
            i.mce-i-dashicons-art::before {
                font-family: 'dashicons';
                content: '\f309';
            }
            
            i.mce-i-dashicons-businessman::before {
                font-family: 'dashicons';
                content: '\f338';
            }
            
            .mce-btn.mce-btn-vortex-artwork,
            .mce-btn.mce-btn-vortex-artist {
                background-color: #f9f9f9;
                border-color: #dedede;
            }
            
            .mce-btn.mce-btn-vortex-artwork:hover,
            .mce-btn.mce-btn-vortex-artist:hover {
                background-color: #f0f0f0;
                border-color: #ccc;
            }
        </style>
        <?php
    }

    /**
     * Register JavaScript for TinyMCE editor.
     *
     * Conditionally loads JavaScript files needed for TinyMCE editor extensions.
     *
     * @since    1.0.0
     */
    public function register_mce_javascript() {
        // Only enqueue on post/page edit screens
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->base, array('post', 'page'))) {
            return;
        }
        
        // Register TinyMCE helper script for enhanced functionality
        wp_enqueue_script(
            $this->plugin_name . '-tinymce-helpers',
            plugin_dir_url(__FILE__) . 'js/vortex-tinymce-helpers.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Localize script with artwork and artist data for the shortcode dialog
        global $wpdb;
        
        // Get artworks
        $artworks = $wpdb->get_results(
            "SELECT ID, post_title 
            FROM {$wpdb->posts} 
            WHERE post_type = 'vortex_artwork' 
            AND post_status = 'publish' 
            ORDER BY post_title ASC 
            LIMIT 100"
        );
        
        // Get artists (WordPress users with artwork)
        $artists = $wpdb->get_results(
            "SELECT DISTINCT u.ID, u.display_name 
            FROM {$wpdb->users} u
            JOIN {$wpdb->posts} p ON p.post_author = u.ID
            WHERE p.post_type = 'vortex_artwork'
            ORDER BY u.display_name ASC
            LIMIT 100"
        );
        
        // Prepare data for localization
        $artwork_options = array();
        if ($artworks) {
            foreach ($artworks as $artwork) {
                $artwork_options[] = array(
                    'text' => $artwork->post_title . ' (ID: ' . $artwork->ID . ')',
                    'value' => $artwork->ID
                );
            }
        }
        
        $artist_options = array();
        if ($artists) {
            foreach ($artists as $artist) {
                $artist_options[] = array(
                    'text' => $artist->display_name . ' (ID: ' . $artist->ID . ')',
                    'value' => $artist->ID
                );
            }
        }
        
        wp_localize_script($this->plugin_name . '-tinymce-helpers', 'vortex_tinymce_data', array(
            'artwork_options' => $artwork_options,
            'artist_options' => $artist_options,
            'plugin_url' => plugin_dir_url(__FILE__),
            'ajax_url' => admin_url('ajax.php'),
            'nonce' => wp_create_nonce('vortex_tinymce_nonce')
        ));
    }

    /**
     * AJAX handler for getting shortcode previews.
     *
     * @since    1.0.0
     */
    public function ajax_get_shortcode_preview() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_tinymce_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
        }
        
        // Get parameters
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $type = isset($_POST['type']) ? sanitize_key($_POST['type']) : '';
        $options = isset($_POST['options']) ? $_POST['options'] : array();
        
        if (empty($id) || empty($type)) {
            wp_send_json_error(array('message' => __('Missing required parameters.', 'vortex-ai-marketplace')));
        }
        
        $html = '';
        
        // Generate preview based on type
        switch ($type) {
            case 'artwork':
                $html = $this->generate_artwork_preview($id, $options);
                break;
                
            case 'artist':
                $html = $this->generate_artist_preview($id, $options);
                break;
                
            default:
                wp_send_json_error(array('message' => __('Invalid preview type.', 'vortex-ai-marketplace')));
                break;
        }
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Generate preview HTML for artwork.
     *
     * @since     1.0.0
     * @param     int       $artwork_id   Artwork ID
     * @param     array     $options      Display options
     * @return    string                  Preview HTML
     */
    private function generate_artwork_preview($artwork_id, $options) {
        $artwork = get_post($artwork_id);
        
        if (!$artwork || $artwork->post_type !== 'vortex_artwork') {
            return '<div class="notice notice-error"><p>' . __('Artwork not found.', 'vortex-ai-marketplace') . '</p></div>';
        }
        
        // Get artwork data
        $title = $artwork->post_title;
        $image = get_the_post_thumbnail_url($artwork_id, 'medium');
        $price = get_post_meta($artwork_id, '_vortex_artwork_price', true);
        $artist_id = $artwork->post_author;
        $artist_name = get_the_author_meta('display_name', $artist_id);
        
        // Default display style
        $display = isset($options['display']) ? $options['display'] : 'card';
        $show_price = isset($options['show_price']) ? $options['show_price'] : true;
        $show_artist = isset($options['show_artist']) ? $options['show_artist'] : true;
        
        // Currency symbol
        $currency_symbol = get_option('vortex_currency_symbol', '$');
        
        $html = '<div class="vortex-artwork-preview vortex-artwork-preview-' . esc_attr($display) . '">';
        
        if ($image) {
            $html .= '<div class="vortex-artwork-preview-image">';
            $html .= '<img src="' . esc_url($image) . '" alt="' . esc_attr($title) . '">';
            $html .= '</div>';
        }
        
        $html .= '<div class="vortex-artwork-preview-details">';
        $html .= '<div class="vortex-artwork-preview-title">' . esc_html($title) . '</div>';
        
        if ($show_artist && $artist_name) {
            $html .= '<div class="vortex-artwork-preview-artist">' . esc_html__('By', 'vortex-ai-marketplace') . ' ' . esc_html($artist_name) . '</div>';
        }
        
        if ($show_price && $price) {
            $html .= '<div class="vortex-artwork-preview-price">' . esc_html($currency_symbol) . esc_html(number_format((float) $price, 2)) . '</div>';
        }
        
        $html .= '</div>'; // End details
        $html .= '</div>'; // End preview
        
        return $html;
    }
    
    /**
     * Generate preview HTML for artist.
     *
     * @since     1.0.0
     * @param     int       $artist_id    Artist ID
     * @param     array     $options      Display options
     * @return    string                  Preview HTML
     */
    private function generate_artist_preview($artist_id, $options) {
        $user_data = get_userdata($artist_id);
        
        if (!$user_data) {
            return '<div class="notice notice-error"><p>' . __('Artist not found.', 'vortex-ai-marketplace') . '</p></div>';
        }
        
        // Get artist data
        $name = $user_data->display_name;
        $bio = get_user_meta($artist_id, 'description', true);
        $avatar = get_avatar_url($artist_id, array('size' => 200));
        
        // Count artworks
        global $wpdb;
        $artwork_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'vortex_artwork' AND post_status = 'publish'",
            $artist_id
        ));
        
        // Default display style
        $display = isset($options['display']) ? $options['display'] : 'profile';
        
        $html = '<div class="vortex-artist-preview vortex-artist-preview-' . esc_attr($display) . '">';
        
        if ($avatar) {
            $html .= '<div class="vortex-artist-preview-avatar">';
            $html .= '<img src="' . esc_url($avatar) . '" alt="' . esc_attr($name) . '">';
            $html .= '</div>';
        }
        
        $html .= '<div class="vortex-artist-preview-info">';
        $html .= '<div class="vortex-artist-preview-name">' . esc_html($name) . '</div>';
        
        if ($bio) {
            $html .= '<div class="vortex-artist-preview-bio">' . wp_kses_post(wp_trim_words($bio, 25)) . '</div>';
        }
        
        $html .= '<div class="vortex-artist-preview-stats">';
        $html .= '<div class="vortex-artist-preview-stat">';
        $html .= '<div class="vortex-artist-preview-stat-value">' . esc_html($artwork_count) . '</div>';
        $html .= '<div class="vortex-artist-preview-stat-label">' . esc_html__('Artworks', 'vortex-ai-marketplace') . '</div>';
        $html .= '</div>';
        $html .= '</div>'; // End stats
        
        $html .= '</div>'; // End info
        $html .= '</div>'; // End preview
        
        return $html;
    }

    /**
     * Add a menu page for database table repairs
     */
    public function add_database_repair_page() {
        add_submenu_page(
            null, // Don't show in menu
            __('Fix Database Tables', 'vortex-ai-marketplace'),
            __('Fix Database Tables', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-fix-tables',
            array($this, 'render_database_repair_page')
        );
    }

    /**
     * Render the database repair page
     */
    public function render_database_repair_page() {
        // Security check
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'vortex-ai-marketplace'));
        }

        // Check if the user clicked the repair button
        $repair_triggered = isset($_POST['vortex_repair_tables']) && $_POST['vortex_repair_tables'] === '1';
        $repair_results = array();
        
        if ($repair_triggered) {
            // Verify nonce
            check_admin_referer('vortex_repair_tables_nonce', 'vortex_repair_tables_nonce');
            
            // Fix missing tables
            require_once(VORTEX_PLUGIN_DIR . 'includes/class-vortex-db-migrations.php');
            $referrers_fixed = Vortex_DB_Migrations::fix_missing_referrers_table();
            
            $repair_results['referrers'] = $referrers_fixed;
        }
        
        // Display the page
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('VORTEX Database Repair', 'vortex-ai-marketplace'); ?></h1>
            
            <?php if ($repair_triggered): ?>
                <div class="notice notice-info">
                    <p><?php _e('Database repair process completed.', 'vortex-ai-marketplace'); ?></p>
                </div>
                
                <h2><?php _e('Repair Results', 'vortex-ai-marketplace'); ?></h2>
                <ul class="vortex-repair-results">
                    <?php if (isset($repair_results['referrers'])): ?>
                        <?php if ($repair_results['referrers']): ?>
                            <li class="vortex-repair-success">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Referrers table was successfully created.', 'vortex-ai-marketplace'); ?>
                            </li>
                        <?php else: ?>
                            <li class="vortex-repair-error">
                                <span class="dashicons dashicons-no"></span>
                                <?php _e('Failed to create referrers table. Please check database permissions.', 'vortex-ai-marketplace'); ?>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <p>
                    <a href="<?php echo admin_url('admin.php?page=vortex-settings'); ?>" class="button button-primary">
                        <?php _e('Return to VORTEX Dashboard', 'vortex-ai-marketplace'); ?>
                    </a>
                </p>
            <?php else: ?>
                <p><?php _e('This tool will attempt to repair missing database tables for the VORTEX AI Marketplace plugin.', 'vortex-ai-marketplace'); ?></p>
                <p><?php _e('The following database issues have been detected:', 'vortex-ai-marketplace'); ?></p>
                
                <ul class="vortex-issues-list">
                    <?php
                    global $wpdb;
                    $missing_tables = array();
                    
                    // Check for missing tables
                    $tables_to_check = array(
                        'vortex_referrers' => __('Referrers Tracking Table', 'vortex-ai-marketplace'),
                        'vortex_campaigns' => __('Campaigns Table', 'vortex-ai-marketplace')
                    );
                    
                    foreach ($tables_to_check as $table => $description) {
                        $full_table_name = $wpdb->prefix . $table;
                        if ($wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") !== $full_table_name) {
                            echo '<li><span class="dashicons dashicons-warning"></span> ';
                            echo sprintf(__('Missing: %s (%s)', 'vortex-ai-marketplace'), $description, '<code>' . $full_table_name . '</code>');
                            echo '</li>';
                            $missing_tables[] = $table;
                        }
                    }
                    
                    if (empty($missing_tables)) {
                        echo '<li><span class="dashicons dashicons-yes"></span> ';
                        echo __('No database issues detected.', 'vortex-ai-marketplace');
                        echo '</li>';
                    }
                    ?>
                </ul>
                
                <?php if (!empty($missing_tables)): ?>
                    <form method="post">
                        <?php wp_nonce_field('vortex_repair_tables_nonce', 'vortex_repair_tables_nonce'); ?>
                        <input type="hidden" name="vortex_repair_tables" value="1">
                        
                        <p class="submit">
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Repair Database Tables', 'vortex-ai-marketplace'); ?>">
                        </p>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <style>
            .vortex-issues-list {
                margin: 20px 0;
                padding: 15px;
                background: #f8f8f8;
                border-left: 4px solid #ddd;
            }
            .vortex-issues-list li {
                margin-bottom: 10px;
            }
            .vortex-issues-list .dashicons-warning {
                color: #e74c3c;
            }
            .vortex-issues-list .dashicons-yes {
                color: #2ecc71;
            }
            .vortex-repair-results {
                margin: 20px 0;
                padding: 15px;
                background: #f8f8f8;
                border-left: 4px solid #ddd;
            }
            .vortex-repair-success {
                color: #2ecc71;
            }
            .vortex-repair-error {
                color: #e74c3c;
            }
        </style>
        <?php
    }

    /**
     * AJAX handler for updating database tables
     * 
     * @since    1.0.0
     */
    public function ajax_update_database() {
        // Check nonce
        check_ajax_referer('vortex_update_database_nonce', 'nonce');
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'vortex-ai-marketplace')));
            return;
        }
        
        // Load the database migrations class
        require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-db-migrations.php';
        
        // Run the fix for missing tables
        $referrers_fixed = Vortex_DB_Migrations::fix_missing_referrers_table();
        
        // Prepare response
        if ($referrers_fixed) {
            wp_send_json_success(array(
                'message' => __('Database tables updated successfully.', 'vortex-ai-marketplace'),
                'tables_fixed' => array('vortex_referrers')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to update database tables. Please check database permissions.', 'vortex-ai-marketplace')
            ));
        }
    }
}
