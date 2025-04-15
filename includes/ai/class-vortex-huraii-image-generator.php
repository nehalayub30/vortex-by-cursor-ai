<?php
/**
 * HURAII Image Generator Class
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/ai
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * VORTEX_HURAII_Image_Generator Class
 * 
 * Handles AI image generation and related shortcodes
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/ai
 */
class VORTEX_HURAII_Image_Generator {

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     * @var      object    $instance    The single instance of this class.
     */
    protected static $instance = null;

    /**
     * HURAII instance
     *
     * @since    1.0.0
     * @var      VORTEX_HURAII
     */
    private $huraii;

    /**
     * Default generation settings
     *
     * @since    1.0.0
     * @var      array
     */
    private $default_settings = array(
        'width' => 512,
        'height' => 512,
        'steps' => 30,
        'guidance_scale' => 7.5,
        'seed' => -1,
        'sampler' => 'k_euler_ancestral',
        'model' => 'sd-v2-1'
    );

    /**
     * Get a single instance of this class
     *
     * @since     1.0.0
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    private function __construct() {
        // Initialize HURAII reference if available
        if (class_exists('VORTEX_HURAII')) {
            $this->huraii = VORTEX_HURAII::get_instance();
        }

        // Add shortcode handlers
        add_action('init', array($this, 'register_scripts'));
    }

    /**
     * Register scripts and styles for the image generator
     *
     * @since    1.0.0
     */
    public function register_scripts() {
        wp_register_style(
            'vortex-image-generator',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'public/css/image-generator.css',
            array(),
            VORTEX_VERSION
        );

        wp_register_script(
            'vortex-image-generator',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'public/js/image-generator.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );

        wp_localize_script('vortex-image-generator', 'vortex_img_gen', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_image_generator_nonce'),
            'i18n' => array(
                'generating' => __('Generating image...', 'vortex-ai-marketplace'),
                'error' => __('Error generating image', 'vortex-ai-marketplace'),
                'success' => __('Image generated successfully', 'vortex-ai-marketplace')
            )
        ));
    }

    /**
     * Image generator shortcode
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes
     * @return   string             HTML output
     */
    public function image_generator_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'width' => $this->default_settings['width'],
            'height' => $this->default_settings['height'],
            'steps' => $this->default_settings['steps'],
            'guidance_scale' => $this->default_settings['guidance_scale'],
            'show_advanced' => 'yes',
            'show_history' => 'yes',
            'allow_download' => 'yes',
            'allow_sharing' => 'yes',
            'theme' => 'light'
        ), $atts, 'vortex_image_generator');

        // Enqueue styles and scripts
        wp_enqueue_style('vortex-image-generator');
        wp_enqueue_script('vortex-image-generator');

        // Start output buffer
        ob_start();

        // Check if user is logged in (if required)
        $require_login = apply_filters('vortex_huraii_require_login', false);
        if ($require_login && !is_user_logged_in()) {
            include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/login-required.php';
            return ob_get_clean();
        }

        // Include template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/image-generator.php';

        return ob_get_clean();
    }

    /**
     * Generate an image based on prompt
     *
     * @since    1.0.0
     * @param    string    $prompt           The text prompt
     * @param    string    $negative_prompt  Negative prompt (optional)
     * @param    array     $settings         Generation settings
     * @return   array|WP_Error             Generated image data or error
     */
    public function generate_image($prompt, $negative_prompt = '', $settings = array()) {
        // Check if HURAII is available
        if (!$this->huraii) {
            return new WP_Error('huraii_unavailable', __('HURAII AI system is not available', 'vortex-ai-marketplace'));
        }

        // Merge with default settings
        $settings = wp_parse_args($settings, $this->default_settings);

        try {
            // Sanitize inputs
            $prompt = sanitize_text_field($prompt);
            $negative_prompt = sanitize_text_field($negative_prompt);
            
            // Call HURAII to generate the image
            $result = $this->huraii->generate_image($prompt, $negative_prompt, $settings);
            
            if (is_wp_error($result)) {
                return $result;
            }
            
            // Process and return the result
            return array(
                'success' => true,
                'image_url' => $result['url'],
                'image_data' => $result['data'],
                'settings' => $settings,
                'prompt' => $prompt,
                'negative_prompt' => $negative_prompt,
                'seed' => $result['seed'],
                'generation_time' => $result['generation_time']
            );
        } catch (Exception $e) {
            return new WP_Error('generation_failed', $e->getMessage());
        }
    }

    /**
     * Save generated image to user's library
     *
     * @since    1.0.0
     * @param    array     $image_data    Generated image data
     * @param    int       $user_id       User ID (optional, defaults to current user)
     * @return   int|WP_Error            Post ID or error
     */
    public function save_to_library($image_data, $user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
            if (!$user_id) {
                return new WP_Error('user_not_logged_in', __('User must be logged in to save images', 'vortex-ai-marketplace'));
            }
        }

        // Create attachment from image data
        $upload_dir = wp_upload_dir();
        $filename = 'vortex-huraii-' . uniqid() . '.png';
        $upload_path = $upload_dir['path'] . '/' . $filename;
        
        // Decode base64 image data
        $image_data_decoded = base64_decode(str_replace('data:image/png;base64,', '', $image_data['image_data']));
        
        // Save image to upload directory
        file_put_contents($upload_path, $image_data_decoded);
        
        // Prepare attachment data
        $attachment = array(
            'post_mime_type' => 'image/png',
            'post_title' => sanitize_text_field($image_data['prompt']),
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => $user_id
        );
        
        // Insert attachment
        $attachment_id = wp_insert_attachment($attachment, $upload_path);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        
        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        // Save additional metadata
        update_post_meta($attachment_id, '_vortex_huraii_prompt', $image_data['prompt']);
        update_post_meta($attachment_id, '_vortex_huraii_negative_prompt', $image_data['negative_prompt']);
        update_post_meta($attachment_id, '_vortex_huraii_settings', $image_data['settings']);
        update_post_meta($attachment_id, '_vortex_huraii_seed', $image_data['seed']);
        update_post_meta($attachment_id, '_vortex_huraii_generation_time', $image_data['generation_time']);
        
        return $attachment_id;
    }
} 