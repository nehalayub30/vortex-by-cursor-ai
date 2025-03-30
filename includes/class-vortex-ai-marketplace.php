<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_AI_Marketplace {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The marketplace instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Marketplace    $marketplace    The marketplace instance.
     */
    protected $marketplace;

    /**
     * The artists manager instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Artists    $artists    The artists manager instance.
     */
    protected $artists;

    /**
     * The artwork manager instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Artwork    $artwork    The artwork manager instance.
     */
    protected $artwork;

    /**
     * The blockchain API instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Blockchain_API    $blockchain_api    The blockchain API instance.
     */
    protected $blockchain_api;

    /**
     * The TOLA token instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Tola    $tola    The TOLA token instance.
     */
    protected $tola;

    /**
     * The Huraii AI instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Huraii    $huraii    The Huraii AI instance.
     */
    protected $huraii;

    /**
     * The Image-to-Image processor instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Img2img    $img2img    The Image-to-Image processor instance.
     */
    protected $img2img;

    /**
     * The metrics manager instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Metrics    $metrics    The metrics manager instance.
     */
    protected $metrics;

    /**
     * The analytics processor instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Analytics    $analytics    The analytics processor instance.
     */
    protected $analytics;

    /**
     * The rankings manager instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Rankings    $rankings    The rankings manager instance.
     */
    protected $rankings;

    /**
     * The theme compatibility instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Theme_Compatibility    $theme_compatibility    The theme compatibility instance.
     */
    protected $theme_compatibility;

    /**
     * The shortcodes manager instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Shortcodes    $shortcodes    The shortcodes manager instance.
     */
    protected $shortcodes;

    /**
     * The wallet instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_AI_Marketplace_Wallet    $wallet    The wallet instance.
     */
    protected $wallet;

    /**
     * The LLM client for interacting with various AI providers.
     *
     * @since    1.0.0
     * @access   public
     * @var      Vortex_LLM_Client    $llm_client    Handles LLM API requests.
     */
    public $llm_client;

    /**
     * The AI usage tracker instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_AI_Usage_Tracker    $usage_tracker    Handles AI API usage tracking.
     */
    protected $usage_tracker;

    /**
     * The AI learning instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_AI_Learning    $ai_learning    The AI learning instance.
     */
    protected $ai_learning;

    /**
     * The user agreement instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_User_Agreement    $user_agreement    The user agreement instance.
     */
    protected $user_agreement;

    /**
     * The predictive engine instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Predictive_Engine    $predictive_engine    The predictive engine instance.
     */
    protected $predictive_engine;

    /**
     * The cross-learning system instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_AI_Crosslearning    $crosslearning    The cross-learning system instance.
     */
    protected $crosslearning;

    /**
     * The adaptive UX instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Adaptive_UX    $adaptive_ux    The adaptive UX instance.
     */
    protected $adaptive_ux;

    /**
     * The enhanced security instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Security    $security    The enhanced security instance.
     */
    protected $security;

    /**
     * The multimodal processor instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Multimodal_Processor    $multimodal    The multimodal processor instance.
     */
    protected $multimodal;

    /**
     * The task automation instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Task_Automation    $task_automation    The task automation instance.
     */
    protected $task_automation;

    /**
     * The royalty manager instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Royalty_Manager    $royalty    The royalty manager instance.
     */
    protected $royalty;

    /**
     * The provenance system instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Provenance    $provenance    The provenance system instance.
     */
    protected $provenance;

    /**
     * The collaboration framework instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Collaboration    $collaboration    The collaboration framework instance.
     */
    protected $collaboration;

    /**
     * The concierge experience instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Concierge    $concierge    The concierge experience instance.
     */
    protected $concierge;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('VORTEX_AI_MARKETPLACE_VERSION')) {
            $this->version = VORTEX_AI_MARKETPLACE_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'vortex-ai-marketplace';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_api_hooks();
        $this->define_blockchain_hooks();
        $this->define_shortcodes();
        $this->register_post_types();
        $this->register_taxonomies();
        $this->initialize_database();
        $this->init_wallet();
        $this->define_core_hooks();
        $this->define_ai_learning_hooks();
        $this->define_revolutionary_features();
        $this->define_advanced_ai_capabilities();
        $this->define_web3_features();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Vortex_Loader. Orchestrates the hooks of the plugin.
     * - Vortex_i18n. Defines internationalization functionality.
     * - Vortex_Admin. Defines all hooks for the admin area.
     * - Vortex_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-vortex-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-vortex-public.php';

        /**
         * The class responsible for theme compatibility.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-theme-compatibility.php';

        /**
         * Core marketplace functionality.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-marketplace.php';

        /**
         * Artist management.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-artists.php';

        /**
         * Artwork management.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-artwork.php';

        /**
         * Blockchain API.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-vortex-api.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-vortex-blockchain-api.php';

        /**
         * TOLA token integration.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-tola.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-vortex-tola-api.php';

        /**
         * AI integration.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-huraii.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-img2img.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-model-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-image-processor.php';

        /**
         * Analytics and metrics.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-metrics.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-analytics.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-rankings.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-vortex-metrics-api.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-vortex-rankings-api.php';

        /**
         * Translation and internationalization.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-vortex-translation-api.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-language-db.php';

        /**
         * Shortcodes.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-shortcodes.php';

        /**
         * Template functions.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/vortex-template-functions.php';

        /**
         * Blocks and Gutenberg integration.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'blocks/class-vortex-blocks.php';

        /**
         * Wallet functionality.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-ai-marketplace-wallet.php';

        /**
         * LLM Client.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-llm-client.php';

        /**
         * AI Usage Tracker.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-ai-usage-tracker.php';

        $this->loader = new Vortex_Loader();

        // Initialize LLM client
        $this->define_llm_client();

        // Define AI learning and user agreement hooks
        $this->define_ai_learning_hooks();

        // Define revolutionary features
        $this->define_revolutionary_features();

        // Define advanced AI capabilities
        $this->define_advanced_ai_capabilities();

        // Define Web3 enhanced features
        $this->define_web3_features();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Vortex_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Vortex_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
        $this->loader->add_filter('locale', $plugin_i18n, 'filter_plugin_locale', 10, 1);
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Vortex_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Initialize marketplace instance
        $this->marketplace = new Vortex_Marketplace($this->get_plugin_name(), $this->get_version());
        
        // Initialize artists manager
        $this->artists = new Vortex_Artists($this->get_plugin_name(), $this->get_version());
        
        // Initialize artwork manager
        $this->artwork = new Vortex_Artwork($this->get_plugin_name(), $this->get_version());
        
        // Initialize theme compatibility
        $this->theme_compatibility = new Vortex_Theme_Compatibility($this->get_plugin_name(), $this->get_version());
        
        // Initialize blockchain API
        $this->blockchain_api = new Vortex_Blockchain_API();
        
        // Initialize TOLA token
        $this->tola = new Vortex_Tola($this->get_plugin_name(), $this->get_version(), $this->blockchain_api);
        
        // Initialize Huraii AI
        $this->huraii = new Vortex_Huraii($this->get_plugin_name(), $this->get_version());
        
        // Initialize Image-to-Image processor
        $this->img2img = new Vortex_Img2img($this->get_plugin_name(), $this->get_version());
        
        // Initialize metrics and analytics
        $this->metrics = new Vortex_Metrics($this->get_plugin_name(), $this->get_version());
        $this->analytics = new Vortex_Analytics($this->get_plugin_name(), $this->get_version());
        $this->rankings = new Vortex_Rankings($this->get_plugin_name(), $this->get_version());
        
        // Provide instances to admin class
        $plugin_admin->set_marketplace($this->marketplace);
        $plugin_admin->set_artists($this->artists);
        $plugin_admin->set_artwork($this->artwork);
        $plugin_admin->set_blockchain_api($this->blockchain_api);
        $plugin_admin->set_tola($this->tola);
        $plugin_admin->set_huraii($this->huraii);
        $plugin_admin->set_img2img($this->img2img);
        $plugin_admin->set_metrics($this->metrics);
        $plugin_admin->set_analytics($this->analytics);
        $plugin_admin->set_rankings($this->rankings);
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Vortex_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Provide instances to public class
        $plugin_public->set_marketplace($this->marketplace);
        $plugin_public->set_artists($this->artists);
        $plugin_public->set_artwork($this->artwork);
        $plugin_public->set_blockchain_api($this->blockchain_api);
        $plugin_public->set_tola($this->tola);
        $plugin_public->set_huraii($this->huraii);
        $plugin_public->set_img2img($this->img2img);
        $plugin_public->set_metrics($this->metrics);
        $plugin_public->set_rankings($this->rankings);
        $plugin_public->set_theme_compatibility($this->theme_compatibility);
        
        // Theme compatibility hooks
        $this->loader->add_action('wp_enqueue_scripts', $this->theme_compatibility, 'enqueue_compatibility_styles');
        $this->loader->add_action('wp_head', $this->theme_compatibility, 'add_dynamic_styles');
        $this->loader->add_filter('body_class', $this->theme_compatibility, 'add_body_classes');
        $this->loader->add_filter('template_include', $this->theme_compatibility, 'template_loader', 99);
    }

    /**
     * Register all of the hooks related to the API functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_api_hooks() {
        // Register REST API endpoints
        $this->loader->add_action('rest_api_init', $this->blockchain_api, 'register_rest_routes');
        $this->loader->add_action('rest_api_init', $this->tola, 'register_rest_routes');
        $this->loader->add_action('rest_api_init', $this->marketplace, 'register_rest_routes');
        $this->loader->add_action('rest_api_init', $this->artists, 'register_rest_routes');
        $this->loader->add_action('rest_api_init', $this->artwork, 'register_rest_routes');
        $this->loader->add_action('rest_api_init', $this->huraii, 'register_rest_routes');
        $this->loader->add_action('rest_api_init', $this->img2img, 'register_rest_routes');
        $this->loader->add_action('rest_api_init', $this->metrics, 'register_rest_routes');
        $this->loader->add_action('rest_api_init', $this->rankings, 'register_rest_routes');
    }

    /**
     * Register all of the hooks related to the blockchain functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_blockchain_hooks() {
        // Set up global instances
        global $vortex_blockchain_api, $vortex_tola_api;
        $vortex_blockchain_api = $this->blockchain_api;
        $vortex_tola_api = new Vortex_Tola_API();
        
        // Connect TOLA API to blockchain API
        $vortex_tola_api->set_blockchain_api($this->blockchain_api);
    }

    /**
     * Register shortcodes for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_shortcodes() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-shortcodes.php';
        $this->shortcodes = new Vortex_Shortcodes();
        
        // Register all shortcodes
        add_action('init', array($this->shortcodes, 'register_artwork_shortcode'));
        add_action('init', array($this->shortcodes, 'register_marketplace_shortcode'));
        add_action('init', array($this->shortcodes, 'register_wallet_connect_shortcode'));
        add_action('init', array($this->shortcodes, 'register_tola_purchase_shortcode'));
        add_action('init', array($this->shortcodes, 'register_wallet_status_shortcode'));
        add_action('init', array($this->shortcodes, 'register_registration_shortcode'));
        add_action('init', array($this->shortcodes, 'register_blockchain_status_shortcode'));
        add_action('init', array($this->shortcodes, 'register_token_balance_shortcode'));
        add_action('init', array($this->shortcodes, 'register_huraii_artwork_shortcode'));
        add_action('init', array($this->shortcodes, 'register_cloe_analysis_shortcode'));
        add_action('init', array($this->shortcodes, 'register_strategy_recommendation_shortcode'));
    }

    /**
     * Register post types for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_post_types() {
        $this->loader->add_action('init', $this->artwork, 'register_post_types');
        $this->loader->add_action('init', $this->artists, 'register_post_types');
    }

    /**
     * Register taxonomies for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_taxonomies() {
        $this->loader->add_action('init', $this->artwork, 'register_taxonomies');
    }

    /**
     * Initialize the database tables for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function initialize_database() {
        // Check if tables need to be created/updated
        $this->loader->add_action('plugins_loaded', $this, 'check_database_version');
    }

    /**
     * Check the database version and update if necessary.
     *
     * @since    1.0.0
     */
    public function check_database_version() {
        $current_db_version = get_option('vortex_database_version', '0');
        
        if (version_compare($current_db_version, $this->version, '<')) {
            $this->create_database_tables();
            update_option('vortex_database_version', $this->version);
        }
    }

    /**
     * Create the database tables for the plugin.
     *
     * @since    1.0.0
     */
    public function create_database_tables() {
        global $wpdb;
        
        // Get collation
        $charset_collate = $wpdb->get_charset_collate();
        
        // Include database schema files
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        require_once plugin_dir_path(dirname(__FILE__)) . 'database/schemas/core-schema.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'database/schemas/tola-schema.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'database/schemas/metrics-schema.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'database/schemas/rankings-schema.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'database/schemas/language-schema.php';
        
        // Execute core schema
        vortex_create_core_schema($charset_collate);
        
        // Execute TOLA schema
        vortex_create_tola_schema($charset_collate);
        
        // Execute metrics schema
        vortex_create_metrics_schema($charset_collate);
        
        // Execute rankings schema
        vortex_create_rankings_schema($charset_collate);
        
        // Execute language schema
        vortex_create_language_schema($charset_collate);
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Vortex_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Get the marketplace instance.
     *
     * @since     1.0.0
     * @return    Vortex_Marketplace    The marketplace instance.
     */
    public function get_marketplace() {
        return $this->marketplace;
    }

    /**
     * Get the artists manager instance.
     *
     * @since     1.0.0
     * @return    Vortex_Artists    The artists manager instance.
     */
    public function get_artists() {
        return $this->artists;
    }

    /**
     * Get the artwork manager instance.
     *
     * @since     1.0.0
     * @return    Vortex_Artwork    The artwork manager instance.
     */
    public function get_artwork() {
        return $this->artwork;
    }

    /**
     * Get the blockchain API instance.
     *
     * @since     1.0.0
     * @return    Vortex_Blockchain_API    The blockchain API instance.
     */
    public function get_blockchain_api() {
        return $this->blockchain_api;
    }

    /**
     * Get the TOLA token instance.
     *
     * @since     1.0.0
     * @return    Vortex_Tola    The TOLA token instance.
     */
    public function get_tola() {
        return $this->tola;
    }

    /**
     * Get the Huraii AI instance.
     *
     * @since     1.0.0
     * @return    Vortex_Huraii    The Huraii AI instance.
     */
    public function get_huraii() {
        return $this->huraii;
    }

    /**
     * Get the Image-to-Image processor instance.
     *
     * @since     1.0.0
     * @return    Vortex_Img2img    The Image-to-Image processor instance.
     */
    public function get_img2img() {
        return $this->img2img;
    }

    /**
     * Get the metrics manager instance.
     *
     * @since     1.0.0
     * @return    Vortex_Metrics    The metrics manager instance.
     */
    public function get_metrics() {
        return $this->metrics;
    }

    /**
     * Get the analytics processor instance.
     *
     * @since     1.0.0
     * @return    Vortex_Analytics    The analytics processor instance.
     */
    public function get_analytics() {
        return $this->analytics;
    }

    /**
     * Get the rankings manager instance.
     *
     * @since     1.0.0
     * @return    Vortex_Rankings    The rankings manager instance.
     */
    public function get_rankings() {
        return $this->rankings;
    }

    /**
     * Get the theme compatibility instance.
     *
     * @since     1.0.0
     * @return    Vortex_Theme_Compatibility    The theme compatibility instance.
     */
    public function get_theme_compatibility() {
        return $this->theme_compatibility;
    }

    /**
     * Get the shortcodes manager instance.
     *
     * @since     1.0.0
     * @return    Vortex_Shortcodes    The shortcodes manager instance.
     */
    public function get_shortcodes() {
        return $this->shortcodes;
    }

    /**
     * Add initialization for the wallet
     *
     * @since    1.0.0
     */
    public function init_wallet() {
        // Include and initialize wallet functionality
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-ai-marketplace-wallet.php';
        $this->wallet = new Vortex_AI_Marketplace_Wallet();
        
        // If HURAII is enabled, integrate AI with wallet
        $ai_settings = get_option('vortex_ai_settings', array());
        if (!empty($ai_settings['huraii_enabled'])) {
            // HURAII integration with wallet for pricing optimization
            add_filter('vortex_tola_price_recommendation', array($this, 'huraii_price_optimization'), 10, 2);
        }
        
        // If CLOE is enabled, integrate with wallet for market analysis
        if (!empty($ai_settings['cloe_enabled'])) {
            // CLOE integration with wallet for token trends
            add_filter('vortex_tola_market_analysis', array($this, 'cloe_market_analysis'), 10, 1);
        }
        
        // If Business Strategist is enabled, integrate with wallet for strategy recommendations
        if (!empty($ai_settings['strategist_enabled'])) {
            // Business Strategist integration for token purchase optimization
            add_filter('vortex_tola_purchase_strategy', array($this, 'strategist_purchase_recommendation'), 10, 2);
        }
    }

    /**
     * HURAII price optimization for TOLA tokens
     * 
     * @param float $price Current token price
     * @param int $quantity Token quantity
     * @return float Optimized price
     */
    public function huraii_price_optimization($price, $quantity) {
        // Check if there are any enabled external providers
        $ai_settings = get_option('vortex_ai_settings', array());
        
        if ($ai_settings['api_source'] === 'custom' && !empty($this->llm_client)) {
            // Use the LLM client to determine pricing optimization
            $params = array(
                'prompt' => 'Optimize pricing for ' . $quantity . ' TOLA tokens, current price: $' . $price,
                'temperature' => 0.2,
                'max_tokens' => 100
            );
            
            $result = $this->llm_client->request('market', $params);
            
            if (!is_wp_error($result)) {
                // Extract price from response (in a real implementation, we'd need to parse this properly)
                $content = $result['content'];
                // Simple extraction for demonstration
                if (preg_match('/\$(\d+\.\d+)/', $content, $matches)) {
                    return floatval($matches[1]);
                }
            }
        }
        
        // Fall back to simple calculation if external providers aren't available
        if ($quantity >= 500) {
            return $price * 0.8; // 20% discount for large purchases
        } elseif ($quantity >= 250) {
            return $price * 0.9; // 10% discount for medium purchases
        }
        
        return $price;
    }

    /**
     * CLOE market analysis for TOLA tokens
     * 
     * @param array $analysis Current analysis data
     * @return array Updated analysis data
     */
    public function cloe_market_analysis($analysis) {
        // In a real implementation, fetch data from CLOE API
        // For demo, return static analysis
        return array(
            'trend' => 'up',
            'trend_percentage' => 15,
            'forecast' => 'bullish',
            'avg_spend' => 35,
            'popularity_rank' => 1,
            'recommendation' => 'The TOLA token is trending up with increased marketplace activity.'
        );
    }

    /**
     * Business Strategist recommendation for TOLA purchases
     * 
     * @param string $recommendation Current recommendation
     * @param array $user_data User data
     * @return string Updated recommendation
     */
    public function strategist_purchase_recommendation($recommendation, $user_data) {
        // In a real implementation, use Business Strategist API
        // For demo, return static recommendation based on basic user data
        if (empty($user_data['purchases'])) {
            return 'For new users, we recommend starting with the 100 TOLA package to explore the marketplace.';
        } elseif ($user_data['purchases'] < 3) {
            return 'Based on your activity, the 250 TOLA package offers the best value for your continued exploration.';
        } else {
            return 'As an active marketplace user, the 500 TOLA package provides optimal value for your regular purchases.';
        }
    }

    /**
     * Register the LLM client.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_llm_client() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-llm-client.php';
        $this->llm_client = new Vortex_LLM_Client();
    }

    /**
     * Define additional core hooks
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_core_hooks() {
        // Initialize AI usage tracker
        $this->usage_tracker = new Vortex_AI_Usage_Tracker();
        
        // Register activation hook for creating database table
        register_activation_hook(__FILE__, array('Vortex_AI_Usage_Tracker', 'create_table'));
        
        // Register deactivation hook to clean up scheduled events
        register_deactivation_hook(__FILE__, array('Vortex_AI_Usage_Tracker', 'deactivate_cleanup'));
    }

    /**
     * Define all hooks for AI learning and user agreement.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_ai_learning_hooks() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-ai-learning.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-user-agreement.php';
        
        $this->ai_learning = new Vortex_AI_Learning();
        $this->user_agreement = new Vortex_User_Agreement();
        
        // Register activation hook for creating database tables
        register_activation_hook(__FILE__, array('Vortex_AI_Learning', 'create_tables'));
        
        // Schedule learning processing
        add_action('vortex_process_ai_learning', array($this->ai_learning, 'process_learning'));
    }

    /**
     * Define the revolutionary AI features.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_revolutionary_features() {
        // Initialize Security first
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-security.php';
        $this->security = new Vortex_Security();
        
        // Initialize Cross-learning
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-ai-crosslearning.php';
        $this->crosslearning = new Vortex_AI_Crosslearning();
        
        // Initialize Adaptive UX
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-adaptive-ux.php';
        $this->adaptive_ux = new Vortex_Adaptive_UX();
        
        // Initialize Predictive Engine
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-predictive-engine.php';
        $this->predictive_engine = new Vortex_Predictive_Engine();
        
        // Register activation hooks for database setup
        register_activation_hook(__FILE__, array('Vortex_AI_Crosslearning', 'create_tables'));
        
        // Schedule predictive engine tasks
        if (!wp_next_scheduled('vortex_generate_predictions')) {
            wp_schedule_event(time(), 'daily', 'vortex_generate_predictions');
        }
    }

    /**
     * Define additional revolutionary AI capabilities
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_advanced_ai_capabilities() {
        // Initialize Multimodal Processing
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-multimodal-processor.php';
        $this->multimodal = new Vortex_Multimodal_Processor();
        
        // Initialize Task Automation
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-task-automation.php';
        $this->task_automation = new Vortex_Task_Automation();
        
        // Register activation hooks for database setup
        register_activation_hook(__FILE__, array('Vortex_Task_Automation', 'create_tables'));
    }

    /**
     * Define Web3 enhanced features
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_web3_features() {
        // Initialize Royalty Manager
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-royalty-manager.php';
        $this->royalty = new Vortex_Royalty_Manager();
        
        // Initialize Provenance System
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-provenance.php';
        $this->provenance = new Vortex_Provenance();
        
        // Initialize Collaboration Framework
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-collaboration.php';
        $this->collaboration = new Vortex_Collaboration();
        
        // Initialize Concierge Experience if enabled
        if (get_option('vortex_enable_concierge', false)) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-concierge.php';
            $this->concierge = new Vortex_Concierge();
        }
    }

    /**
     * Register TOLA verification endpoint
     */
    public function register_tola_verification_endpoint() {
        add_rewrite_rule(
            'verify/([a-zA-Z0-9]+)/?$',
            'index.php?tola_verification=1&contract_hash=$matches[1]',
            'top'
        );
        
        add_rewrite_tag('%tola_verification%', '([0-1])');
        add_rewrite_tag('%contract_hash%', '([^&]+)');
        
        // Register template for verification page
        add_filter('template_include', function($template) {
            if (get_query_var('tola_verification')) {
                $contract_hash = get_query_var('contract_hash');
                
                // Load the contract and artwork data
                require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-huraii-smart-contract.php';
                $contract_manager = Vortex_HURAII_Smart_Contract::get_instance();
                $contract = $contract_manager->get_contract_by_hash($contract_hash);
                
                if ($contract) {
                    global $wpdb;
                    $artwork_table = $wpdb->prefix . 'vortex_artworks';
                    
                    $artwork = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $artwork_table WHERE id = %d",
                        $contract->artwork_id
                    ));
                    
                    if ($artwork) {
                        $artist = get_userdata($artwork->user_id);
                        
                        // Load the verification template
                        return plugin_dir_path(__FILE__) . 'public/partials/vortex-tola-verification.php';
                    } else {
                        $error_message = __('Artwork not found for this contract.', 'vortex-ai-marketplace');
                    }
                } else {
                    $error_message = __('Invalid contract hash.', 'vortex-ai-marketplace');
                }
                
                // Still load the template to show the error
                return plugin_dir_path(__FILE__) . 'public/partials/vortex-tola-verification.php';
            }
            
            return $template;
        });
        
        // Flush rewrite rules once
        if (get_option('vortex_tola_verification_flush') != '1') {
            flush_rewrite_rules();
            update_option('vortex_tola_verification_flush', '1');
        }
    }
} 