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
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Dashboard widgets
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
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
}
