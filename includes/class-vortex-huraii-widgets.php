<?php
/**
 * HURAII Visual Widgets
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI_Processing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_HURAII_Widgets Class
 * 
 * Handles the registration and rendering of HURAII visual widgets
 * with WordPress theme integration.
 *
 * @since 1.0.0
 */
class VORTEX_HURAII_Widgets {
    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var object
     */
    protected static $instance = null;
    
    /**
     * HURAII instance
     *
     * @since 1.0.0
     * @var VORTEX_HURAII
     */
    private $huraii;
    
    /**
     * Initializes the plugin by setting up hooks.
     *
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        $instance = self::get_instance();
        return $instance;
    }
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->huraii = VORTEX_HURAII::get_instance();
        $this->setup_hooks();
    }
    
    /**
     * Get instance of this class.
     *
     * @since 1.0.0
     * @return VORTEX_HURAII_Widgets
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Setup hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function setup_hooks() {
        // Register widgets
        add_action('widgets_init', array($this, 'register_widgets'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add shortcodes
        add_shortcode('vortex_huraii_generator', array($this, 'render_generator_widget'));
        add_shortcode('vortex_huraii_style_transfer', array($this, 'render_style_transfer_widget'));
        add_shortcode('vortex_huraii_analysis', array($this, 'render_analysis_widget'));
    }
    
    /**
     * Register widgets
     *
     * @since 1.0.0
     * @return void
     */
    public function register_widgets() {
        register_widget('Vortex_HURAII_Generator_Widget');
        register_widget('Vortex_HURAII_Style_Transfer_Widget');
        register_widget('Vortex_HURAII_Analysis_Widget');
    }
    
    /**
     * Enqueue scripts and styles
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueue_scripts() {
        // Get current theme colors
        $theme_colors = $this->get_theme_colors();
        
        // Enqueue HURAII styles
        wp_enqueue_style(
            'vortex-huraii-widgets',
            VORTEX_PLUGIN_URL . 'css/vortex-huraii-widgets.css',
            array(),
            VORTEX_VERSION
        );
        
        // Add theme-specific styles
        wp_add_inline_style('vortex-huraii-widgets', $this->generate_theme_styles($theme_colors));
        
        // Enqueue scripts
        wp_enqueue_script(
            'vortex-huraii-widgets',
            VORTEX_PLUGIN_URL . 'js/vortex-huraii-widgets.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );
        
        // Localize script data
        wp_localize_script('vortex-huraii-widgets', 'vortexHURAII', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_huraii_nonce'),
            'themeColors' => $theme_colors,
            'i18n' => array(
                'generating' => __('Generating artwork...', 'vortex-marketplace'),
                'analyzing' => __('Analyzing artwork...', 'vortex-marketplace'),
                'transferring' => __('Transferring style...', 'vortex-marketplace'),
                'error' => __('An error occurred. Please try again.', 'vortex-marketplace')
            )
        ));
    }
    
    /**
     * Get current theme colors
     *
     * @since 1.0.0
     * @return array
     */
    private function get_theme_colors() {
        $colors = array(
            'primary' => get_theme_mod('primary_color', '#007bff'),
            'secondary' => get_theme_mod('secondary_color', '#6c757d'),
            'accent' => get_theme_mod('accent_color', '#ffc107'),
            'background' => get_theme_mod('background_color', '#ffffff'),
            'text' => get_theme_mod('text_color', '#212529')
        );
        
        return apply_filters('vortex_huraii_theme_colors', $colors);
    }
    
    /**
     * Generate theme-specific styles
     *
     * @since 1.0.0
     * @param array $colors Theme colors
     * @return string
     */
    private function generate_theme_styles($colors) {
        ob_start();
        ?>
        .vortex-huraii-widget {
            background-color: <?php echo esc_attr($colors['background']); ?>;
            color: <?php echo esc_attr($colors['text']); ?>;
        }
        
        .vortex-huraii-button {
            background-color: <?php echo esc_attr($colors['primary']); ?>;
            color: #ffffff;
        }
        
        .vortex-huraii-button:hover {
            background-color: <?php echo esc_attr($this->adjust_brightness($colors['primary'], -10)); ?>;
        }
        
        .vortex-huraii-secondary-button {
            background-color: <?php echo esc_attr($colors['secondary']); ?>;
            color: #ffffff;
        }
        
        .vortex-huraii-accent {
            color: <?php echo esc_attr($colors['accent']); ?>;
        }
        <?php
        return ob_get_clean();
    }
    
    /**
     * Adjust color brightness
     *
     * @since 1.0.0
     * @param string $hex Hex color code
     * @param int $steps Steps to adjust (-255 to 255)
     * @return string
     */
    private function adjust_brightness($hex, $steps) {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
    
    /**
     * Render generator widget
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_generator_widget($atts) {
        // Security check
        if (!wp_verify_nonce($atts['security_nonce'] ?? '', 'vortex_huraii_generator_display')) {
            return '<p class="vortex-error">' . __('Security check failed.', 'vortex-marketplace') . '</p>';
        }
        
        // Sanitize and validate attributes
        $atts = shortcode_atts(array(
            'title' => __('AI Art Generator', 'vortex-marketplace'),
            'width' => '100%',
            'height' => '600px',
            'show_advanced' => 'no',
            'theme' => 'light',
            'classes' => '',
            'security_nonce' => wp_create_nonce('vortex_huraii_generator_display')
        ), $atts, 'vortex_huraii_generator');
        
        // Buffer the output
        ob_start();
        
        // Include the template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-huraii-generator-display.php';
        
        return ob_get_clean();
    }
    
    /**
     * Render style transfer widget
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_style_transfer_widget($atts) {
        // Security check
        if (!wp_verify_nonce($atts['security_nonce'] ?? '', 'vortex_huraii_style_transfer_display')) {
            return '<p class="vortex-error">' . __('Security check failed.', 'vortex-marketplace') . '</p>';
        }
        
        // Sanitize and validate attributes
        $atts = shortcode_atts(array(
            'title' => __('Style Transfer', 'vortex-marketplace'),
            'width' => '100%',
            'height' => '500px',
            'show_preview' => 'yes',
            'theme' => 'light',
            'classes' => '',
            'security_nonce' => wp_create_nonce('vortex_huraii_style_transfer_display')
        ), $atts, 'vortex_huraii_style_transfer');
        
        // Buffer the output
        ob_start();
        
        // Include the template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-huraii-style-transfer-display.php';
        
        return ob_get_clean();
    }
    
    /**
     * Render analysis widget
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_analysis_widget($atts) {
        // Security check
        if (!wp_verify_nonce($atts['security_nonce'] ?? '', 'vortex_huraii_analysis_display')) {
            return '<p class="vortex-error">' . __('Security check failed.', 'vortex-marketplace') . '</p>';
        }
        
        // Sanitize and validate attributes
        $atts = shortcode_atts(array(
            'title' => __('Artwork Analysis', 'vortex-marketplace'),
            'width' => '100%',
            'height' => '400px',
            'show_details' => 'yes',
            'theme' => 'light',
            'classes' => '',
            'security_nonce' => wp_create_nonce('vortex_huraii_analysis_display')
        ), $atts, 'vortex_huraii_analysis');
        
        // Buffer the output
        ob_start();
        
        // Include the template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-huraii-analysis-display.php';
        
        return ob_get_clean();
    }
} 