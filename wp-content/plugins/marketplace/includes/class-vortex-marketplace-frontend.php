<?php
/**
 * VORTEX Marketplace Frontend Handler
 *
 * Manages all frontend display functionality for the marketplace plugin.
 * Acts as a central hub for all shortcodes, asset loading, and frontend rendering.
 *
 * @package VORTEX
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_Marketplace_Frontend {

    /**
     * Instance of this class
     * @var VORTEX_Marketplace_Frontend
     */
    private static $instance = null;

    /**
     * Shortcode registry
     * @var array
     */
    private $shortcodes = array();
    
    /**
     * Plugin directory path
     * @var string
     */
    private $plugin_dir;
    
    /**
     * Plugin directory URL
     * @var string
     */
    private $plugin_url;
    
    /**
     * Frontend modules registry
     * @var array
     */
    private $modules = array();

    /**
     * Constructor
     */
    private function __construct() {
        $this->plugin_dir = plugin_dir_path(dirname(__FILE__));
        $this->plugin_url = plugin_dir_url(dirname(__FILE__));
        
        // Register hooks
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_action('init', array($this, 'init'));
        
        // Cache invalidation hooks
        add_action('vortex_investor_application_submitted', array($this, 'invalidate_marketplace_cache'));
        add_action('vortex_investor_status_changed', array($this, 'invalidate_marketplace_cache'));
        add_action('vortex_token_transaction_processed', array($this, 'invalidate_marketplace_cache'));
        
        // Add lazy loading to images
        add_filter('vortex_marketplace_frontend_output', array($this, 'add_lazy_loading'), 20);
        add_filter('vortex_marketplace_default_view_output', array($this, 'add_lazy_loading'), 20);
        
        // Load modules
        $this->load_modules();
        
        // Register shortcodes
        $this->register_shortcodes();
        
        // Allow plugins to hook into the frontend initialization
        do_action('vortex_marketplace_frontend_init', $this);
    }

    /**
     * Get instance of this class
     * @return VORTEX_Marketplace_Frontend
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the frontend
     */
    public function init() {
        // Check if Gutenberg is active
        if (function_exists('register_block_type')) {
            $this->register_blocks();
        }
        
        // Allow plugins to hook into the frontend initialization
        do_action('vortex_marketplace_frontend_after_init', $this);
    }

    /**
     * Load frontend modules
     */
    private function load_modules() {
        // Allow plugins to hook before loading modules
        do_action('vortex_marketplace_before_load_modules', $this);
        
        // Load existing modules
        require_once $this->plugin_dir . 'includes/dao/class-vortex-dao-shortcodes.php';
        
        // Register modules
        $this->modules['dao_shortcodes'] = VORTEX_DAO_Shortcodes::get_instance();
        
        // Add action for plugins to register their frontend modules
        do_action('vortex_marketplace_register_frontend_modules', $this);
        
        // Allow plugins to hook after loading modules
        do_action('vortex_marketplace_after_load_modules', $this);
    }

    /**
     * Register a module
     *
     * @param string $key Module key
     * @param object $module Module instance
     */
    public function register_module($key, $module) {
        // Allow filtering module registration
        $register = apply_filters('vortex_marketplace_register_module', true, $key, $module);
        
        if ($register) {
            $this->modules[$key] = $module;
            
            // Trigger action after module registration
            do_action('vortex_marketplace_module_registered', $key, $module, $this);
        }
    }

    /**
     * Register shared frontend assets
     */
    public function register_assets() {
        // Allow plugins to hook before registering assets
        do_action('vortex_marketplace_before_register_assets', $this);
        
        // Core CSS
        wp_register_style(
            'vortex-marketplace-frontend',
            $this->plugin_url . 'assets/css/marketplace-frontend.css',
            array(),
            VORTEX_MARKETPLACE_VERSION
        );
        
        // Core JS
        wp_register_script(
            'vortex-marketplace-frontend',
            $this->plugin_url . 'assets/js/marketplace-frontend.js',
            array('jquery'),
            VORTEX_MARKETPLACE_VERSION,
            true
        );
        
        // Get localization strings and allow filtering
        $localization_strings = $this->get_localization_strings();
        $localization_strings = apply_filters('vortex_marketplace_localization_strings', $localization_strings);
        
        // Localize script with common data
        wp_localize_script(
            'vortex-marketplace-frontend',
            'vortex_marketplace_vars',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vortex_marketplace_nonce'),
                'site_url' => get_site_url(),
                'plugin_url' => $this->plugin_url,
                'i18n' => $localization_strings,
                'rest_url' => rest_url('vortex-marketplace/v1'),
                'rest_nonce' => wp_create_nonce('wp_rest'),
            )
        );
        
        // Only register on demand, we'll enqueue as needed
        wp_register_style(
            'vortex-investor-application',
            $this->plugin_url . 'assets/css/vortex-investor-application.css',
            array('vortex-marketplace-frontend'),
            VORTEX_MARKETPLACE_VERSION
        );
        
        wp_register_style(
            'vortex-investor-dashboard',
            $this->plugin_url . 'assets/css/vortex-investor-dashboard.css',
            array('vortex-marketplace-frontend'),
            VORTEX_MARKETPLACE_VERSION
        );
        
        // Register scripts with defer attribute for non-critical scripts
        wp_register_script(
            'vortex-solana-wallet',
            $this->plugin_url . 'assets/js/vortex-solana-wallet.js',
            array('vortex-marketplace-frontend'),
            VORTEX_MARKETPLACE_VERSION,
            true
        );
        add_filter('script_loader_tag', array($this, 'add_defer_attribute'), 10, 2);
        
        wp_register_script(
            'vortex-dao-js',
            $this->plugin_url . 'assets/js/vortex-dao.js',
            array('vortex-marketplace-frontend', 'vortex-solana-wallet'),
            VORTEX_MARKETPLACE_VERSION,
            true
        );
        
        // Allow other modules to register their assets
        do_action('vortex_marketplace_register_frontend_assets', $this);
        
        // Only load assets if needed
        $this->conditionally_enqueue_assets();
    }
    
    /**
     * Conditionally enqueue assets based on current page content
     */
    private function conditionally_enqueue_assets() {
        global $post;
        
        // Skip if not a singular post/page or admin
        if (is_admin() || !is_singular()) {
            return;
        }
        
        $content = $post->post_content;
        
        // Check for our shortcodes
        $load_core_assets = false;
        $load_investor_application = false;
        $load_investor_dashboard = false;
        $load_wallet = false;
        $load_dao = false;
        
        // Check for core marketplace shortcode
        if (has_shortcode($content, 'marketplace_output')) {
            $load_core_assets = true;
            
            // Parse shortcode attributes to determine which specific assets to load
            $pattern = get_shortcode_regex(array('marketplace_output'));
            if (preg_match_all('/' . $pattern . '/s', $content, $matches) && !empty($matches[3])) {
                foreach ($matches[3] as $attrs) {
                    $atts = shortcode_parse_atts($attrs);
                    if (isset($atts['type'])) {
                        if ($atts['type'] === 'investor_application') {
                            $load_investor_application = true;
                            $load_wallet = true;
                        } else if ($atts['type'] === 'investor_dashboard') {
                            $load_investor_dashboard = true;
                            $load_wallet = true;
                            $load_dao = true;
                        }
                    }
                }
            }
        }
        
        // Check for specific DAO shortcodes
        if (has_shortcode($content, 'vortex_investor_application')) {
            $load_core_assets = true;
            $load_investor_application = true;
            $load_wallet = true;
        }
        
        if (has_shortcode($content, 'vortex_investor_dashboard')) {
            $load_core_assets = true;
            $load_investor_dashboard = true;
            $load_wallet = true;
            $load_dao = true;
        }
        
        // Only enqueue what we need
        if ($load_core_assets) {
            wp_enqueue_style('vortex-marketplace-frontend');
            wp_enqueue_script('vortex-marketplace-frontend');
        }
        
        if ($load_investor_application) {
            wp_enqueue_style('vortex-investor-application');
        }
        
        if ($load_investor_dashboard) {
            wp_enqueue_style('vortex-investor-dashboard');
        }
        
        if ($load_wallet) {
            wp_enqueue_script('vortex-solana-wallet');
        }
        
        if ($load_dao) {
            wp_enqueue_script('vortex-dao-js');
        }
    }
    
    /**
     * Get localization strings for JavaScript
     * 
     * @return array Localization strings
     */
    private function get_localization_strings() {
        return array(
            'error' => __('Error', 'vortex'),
            'success' => __('Success', 'vortex'),
            'loading' => __('Loading...', 'vortex'),
            'connect_wallet' => __('Connect Wallet', 'vortex'),
            'connecting' => __('Connecting...', 'vortex'),
            'wallet_connected' => __('Wallet Connected', 'vortex'),
            'connection_error' => __('Connection Error', 'vortex'),
            'form_validation_error' => __('Please fill in all required fields.', 'vortex'),
            'processing' => __('Processing...', 'vortex'),
            'submit' => __('Submit', 'vortex'),
        );
    }

    /**
     * Register shortcodes
     * External modules register their shortcodes through their own classes
     */
    private function register_shortcodes() {
        // Allow plugins to hook before registering shortcodes
        do_action('vortex_marketplace_before_register_shortcodes', $this);
        
        // Marketplace shortcode
        add_shortcode('marketplace_output', array($this, 'marketplace_output_shortcode'));
        
        // Allow other modules to register shortcodes
        do_action('vortex_marketplace_register_shortcodes', $this);
        
        // Allow plugins to hook after registering shortcodes
        do_action('vortex_marketplace_after_register_shortcodes', $this);
    }
    
    /**
     * Register a shortcode
     *
     * @param string $tag Shortcode tag
     * @param callable $callback Shortcode callback
     */
    public function register_shortcode($tag, $callback) {
        // Allow filtering shortcode registration
        $register = apply_filters('vortex_marketplace_register_shortcode', true, $tag, $callback);
        
        if ($register) {
            $this->shortcodes[$tag] = $callback;
            add_shortcode($tag, $callback);
            
            // Trigger action after shortcode registration
            do_action('vortex_marketplace_shortcode_registered', $tag, $callback, $this);
        }
    }

    /**
     * Register Gutenberg blocks
     */
    private function register_blocks() {
        // Allow plugins to hook before registering blocks
        do_action('vortex_marketplace_before_register_blocks', $this);
        
        // Register blocks and their assets
        register_block_type('vortex-marketplace/frontend-output', array(
            'editor_script' => 'vortex-marketplace-block-editor',
            'editor_style' => 'vortex-marketplace-block-editor',
            'render_callback' => array($this, 'render_frontend_block'),
            'attributes' => array(
                'className' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'displayType' => array(
                    'type' => 'string',
                    'default' => 'default',
                ),
            ),
        ));
        
        // Register editor script
        wp_register_script(
            'vortex-marketplace-block-editor',
            $this->plugin_url . 'assets/js/blocks/marketplace-block.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
            VORTEX_MARKETPLACE_VERSION
        );
        
        // Register editor style
        wp_register_style(
            'vortex-marketplace-block-editor',
            $this->plugin_url . 'assets/css/blocks/marketplace-block-editor.css',
            array('wp-edit-blocks'),
            VORTEX_MARKETPLACE_VERSION
        );
        
        // Allow plugins to hook after registering blocks
        do_action('vortex_marketplace_after_register_blocks', $this);
    }

    /**
     * Renders the marketplace frontend block
     *
     * @param array $attributes Block attributes
     * @return string HTML output
     */
    public function render_frontend_block($attributes) {
        $display_type = isset($attributes['displayType']) ? $attributes['displayType'] : 'default';
        $class_name = isset($attributes['className']) ? $attributes['className'] : '';
        
        // Allow filtering attributes before rendering
        $attributes = apply_filters('vortex_marketplace_block_attributes', $attributes);
        
        return $this->render_marketplace_output($display_type, $class_name);
    }

    /**
     * Marketplace output shortcode callback
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function marketplace_output_shortcode($atts) {
        // Allow filtering attributes before processing
        $atts = apply_filters('vortex_marketplace_shortcode_atts', $atts);
        
        $atts = shortcode_atts(array(
            'type' => 'default',
            'class' => '',
        ), $atts, 'marketplace_output');
        
        // Allow filtering processed attributes
        $atts = apply_filters('vortex_marketplace_processed_shortcode_atts', $atts, 'marketplace_output');
        
        return $this->render_marketplace_output($atts['type'], $atts['class']);
    }

    /**
     * Render the marketplace output
     *
     * @param string $type Display type
     * @param string $class Additional CSS classes
     * @return string HTML output
     */
    private function render_marketplace_output($type = 'default', $class = '') {
        // Generate a unique cache key based on the parameters
        $cache_key = 'vortex_marketplace_' . md5($type . $class . (is_user_logged_in() ? get_current_user_id() : 0));
        
        // Check if we have a cached version and user is not logged in or cache enabled for logged-in users
        if (!is_user_logged_in() || apply_filters('vortex_marketplace_cache_for_logged_in', false)) {
            $cached_output = get_transient($cache_key);
            if (false !== $cached_output) {
                return $cached_output;
            }
        }
        
        // Enqueue required styles and scripts
        wp_enqueue_style('vortex-marketplace-frontend');
        wp_enqueue_script('vortex-marketplace-frontend');
        
        // Allow for just-in-time enqueueing of additional assets
        do_action('vortex_marketplace_before_output', $type, $class);
        
        ob_start();
        
        // Before wrapper hook
        do_action('vortex_marketplace_before_wrapper', $type, $class);
        
        echo '<div class="marketplace-frontend-wrapper ' . esc_attr($class) . '">';
        
        // Before content hook
        do_action('vortex_marketplace_before_content', $type, $class);
        
        switch ($type) {
            case 'investor_application':
                if (shortcode_exists('vortex_investor_application')) {
                    do_action('vortex_marketplace_before_investor_application');
                    
                    echo do_shortcode('[vortex_investor_application]');
                    
                    do_action('vortex_marketplace_after_investor_application');
                } else {
                    // Allow plugins to provide alternative content
                    $alternative_content = apply_filters('vortex_marketplace_missing_module', 
                        '<p>' . __('Investor application module is not available.', 'vortex') . '</p>', 
                        'investor_application'
                    );
                    
                    echo $alternative_content;
                }
                break;
                
            case 'investor_dashboard':
                if (shortcode_exists('vortex_investor_dashboard')) {
                    do_action('vortex_marketplace_before_investor_dashboard');
                    
                    echo do_shortcode('[vortex_investor_dashboard]');
                    
                    do_action('vortex_marketplace_after_investor_dashboard');
                } else {
                    // Allow plugins to provide alternative content
                    $alternative_content = apply_filters('vortex_marketplace_missing_module', 
                        '<p>' . __('Investor dashboard module is not available.', 'vortex') . '</p>', 
                        'investor_dashboard'
                    );
                    
                    echo $alternative_content;
                }
                break;
                
            case 'default':
            default:
                // Allow plugins to provide custom content types
                $has_custom_content = apply_filters('vortex_marketplace_has_custom_content_type', false, $type);
                
                if ($has_custom_content) {
                    // Let plugins render their custom content
                    do_action('vortex_marketplace_render_custom_content', $type, $class);
                } else {
                    echo $this->render_marketplace_default_view();
                }
                break;
        }
        
        // After content hook
        do_action('vortex_marketplace_after_content', $type, $class);
        
        echo '</div>';
        
        // After wrapper hook
        do_action('vortex_marketplace_after_wrapper', $type, $class);
        
        $content = ob_get_clean();
        
        // Allow filtering of the final output
        $content = apply_filters('vortex_marketplace_frontend_output', $content, $type, $class);
        
        // Cache the output for non-logged in users or if explicitly enabled for logged-in users
        if (!is_user_logged_in() || apply_filters('vortex_marketplace_cache_for_logged_in', false)) {
            // Get cache expiration from settings, default to 1 hour
            $cache_expiration = apply_filters('vortex_marketplace_cache_expiration', HOUR_IN_SECONDS);
            set_transient($cache_key, $content, $cache_expiration);
        }
        
        return $content;
    }

    /**
     * Render the default marketplace view
     *
     * @return string HTML output
     */
    private function render_marketplace_default_view() {
        // Generate a cache key for the default view
        $cache_key = 'vortex_marketplace_default_view_' . (is_user_logged_in() ? get_current_user_id() : 0);
        
        // Check for cached version if not logged in or caching is enabled for logged in users
        if (!is_user_logged_in() || apply_filters('vortex_marketplace_cache_for_logged_in', false)) {
            $cached_output = get_transient($cache_key);
            if (false !== $cached_output) {
                return $cached_output;
            }
        }
        
        ob_start();
        
        // Before default view hook
        do_action('vortex_marketplace_before_default_view');
        
        ?>
        <div class="marketplace-default-view">
            <h2 class="marketplace-frontend-title"><?php echo apply_filters('vortex_marketplace_default_title', __('VORTEX AI Marketplace', 'vortex')); ?></h2>
            
            <div class="marketplace-frontend-content">
                <?php do_action('vortex_marketplace_default_view_before_sections'); ?>
                
                <div class="marketplace-sections">
                    <?php 
                    // Allow plugins to add sections before the default ones
                    do_action('vortex_marketplace_default_view_before_investor_section');
                    ?>
                    
                    <div class="marketplace-section">
                        <h3><?php echo apply_filters('vortex_marketplace_investor_section_title', __('Investor Portal', 'vortex')); ?></h3>
                        <p><?php echo apply_filters('vortex_marketplace_investor_section_description', __('Access investment opportunities in VORTEX AI Marketplace.', 'vortex')); ?></p>
                        <div class="marketplace-section-actions">
                            <?php do_action('vortex_marketplace_investor_section_before_actions'); ?>
                            
                            <a href="<?php echo esc_url(apply_filters('vortex_marketplace_investor_application_url', add_query_arg('type', 'investor_application', get_permalink()))); ?>" class="vortex-btn"><?php echo apply_filters('vortex_marketplace_investor_application_button_text', __('Apply as Investor', 'vortex')); ?></a>
                            <a href="<?php echo esc_url(apply_filters('vortex_marketplace_investor_dashboard_url', add_query_arg('type', 'investor_dashboard', get_permalink()))); ?>" class="vortex-btn"><?php echo apply_filters('vortex_marketplace_investor_dashboard_button_text', __('Investor Dashboard', 'vortex')); ?></a>
                            
                            <?php do_action('vortex_marketplace_investor_section_after_actions'); ?>
                        </div>
                    </div>
                    
                    <?php 
                    // Allow plugins to add sections after the default ones
                    do_action('vortex_marketplace_default_view_after_investor_section');
                    ?>
                </div>
                
                <?php do_action('vortex_marketplace_default_view_after_sections'); ?>
            </div>
        </div>
        <?php
        
        // After default view hook
        do_action('vortex_marketplace_after_default_view');
        
        $output = ob_get_clean();
        
        // Allow filtering of the default view output
        $output = apply_filters('vortex_marketplace_default_view_output', $output);
        
        // Cache the output for non-logged in users or if explicitly enabled for logged in users
        if (!is_user_logged_in() || apply_filters('vortex_marketplace_cache_for_logged_in', false)) {
            $cache_expiration = apply_filters('vortex_marketplace_default_view_cache_expiration', HOUR_IN_SECONDS * 6);
            set_transient($cache_key, $output, $cache_expiration);
        }
        
        return $output;
    }
    
    /**
     * Get registered modules
     * 
     * @return array Registered modules
     */
    public function get_modules() {
        return apply_filters('vortex_marketplace_modules', $this->modules);
    }
    
    /**
     * Get registered shortcodes
     * 
     * @return array Registered shortcodes
     */
    public function get_shortcodes() {
        return apply_filters('vortex_marketplace_shortcodes', $this->shortcodes);
    }

    /**
     * Invalidate marketplace cache
     */
    public function invalidate_marketplace_cache() {
        global $wpdb;
        
        // Delete all marketplace transients
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_vortex_marketplace_%'");
        
        // Allow extensions to clear their caches too
        do_action('vortex_marketplace_cache_invalidated');
    }

    /**
     * Add defer attribute to non-critical scripts
     *
     * @param string $tag Script HTML tag
     * @param string $handle Script handle
     * @return string Modified script HTML tag
     */
    public function add_defer_attribute($tag, $handle) {
        // List of scripts to defer
        $scripts_to_defer = apply_filters('vortex_marketplace_defer_scripts', array(
            'vortex-solana-wallet',
            'vortex-dao-js'
        ));
        
        if (in_array($handle, $scripts_to_defer)) {
            return str_replace(' src', ' defer src', $tag);
        }
        
        return $tag;
    }

    /**
     * Add lazy loading to images in the content
     * 
     * @param string $content The content to process
     * @return string The processed content
     */
    public function add_lazy_loading($content) {
        // Only process if content contains images
        if (strpos($content, '<img') === false) {
            return $content;
        }
        
        // Don't add lazy loading if the browser doesn't support it or if it's disabled
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Googlebot') !== false) {
            return $content;
        }
        
        // Don't process content if already has lazy loading
        if (strpos($content, 'loading="lazy"') !== false) {
            return $content;
        }
        
        // Add loading="lazy" to all images that don't already have it
        $content = preg_replace('/<img((?!loading=)[^>]*)>/i', '<img$1 loading="lazy">', $content);
        
        return $content;
    }
}

// Initialize the frontend
VORTEX_Marketplace_Frontend::get_instance(); 