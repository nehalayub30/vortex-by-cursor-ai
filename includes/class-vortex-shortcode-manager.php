<?php
/**
 * Manages all shortcodes for VORTEX AI Marketplace
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Shortcode Manager Class
 */
class Vortex_Shortcode_Manager {
    /**
     * Available shortcodes
     *
     * @var array
     */
    private $shortcodes = array();
    
    /**
     * Initialize the class
     */
    public function __construct() {
        // Register all shortcodes
        add_action('init', array($this, 'register_shortcodes'));
        
        // Register Ajax endpoint for shortcode preview
        add_action('wp_ajax_vortex_preview_shortcode', array($this, 'ajax_preview_shortcode'));
    }
    
    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        // Load shortcode files
        $this->load_shortcode_files();
        
        // Register each shortcode
        foreach ($this->shortcodes as $tag => $config) {
            if (isset($config['callback']) && is_callable($config['callback'])) {
                add_shortcode($tag, $config['callback']);
            }
        }
    }
    
    /**
     * Load shortcode files
     */
    private function load_shortcode_files() {
        // Define the shortcodes to load
        $this->shortcodes = array(
            'vortex_thorius' => array(
                'file' => VORTEX_PLUGIN_PATH . 'public/shortcodes/thorius-concierge-shortcode.php',
                'callback' => 'vortex_thorius_concierge_shortcode',
                'description' => __('Displays the Thorius AI Concierge interface', 'vortex-ai-marketplace'),
                'attributes' => array(
                    'theme' => array(
                        'type' => 'string',
                        'default' => 'light',
                        'description' => __('Interface theme (light, dark, auto)', 'vortex-ai-marketplace')
                    ),
                    'position' => array(
                        'type' => 'string',
                        'default' => 'right',
                        'description' => __('Interface position (left, right)', 'vortex-ai-marketplace')
                    ),
                    'welcome_message' => array(
                        'type' => 'boolean',
                        'default' => true,
                        'description' => __('Show welcome message', 'vortex-ai-marketplace')
                    ),
                    'show_tabs' => array(
                        'type' => 'boolean',
                        'default' => true,
                        'description' => __('Show interface tabs', 'vortex-ai-marketplace')
                    ),
                    'enable_voice' => array(
                        'type' => 'boolean',
                        'default' => true,
                        'description' => __('Enable voice interaction', 'vortex-ai-marketplace')
                    ),
                    'enable_location' => array(
                        'type' => 'boolean',
                        'default' => true,
                        'description' => __('Enable location awareness', 'vortex-ai-marketplace')
                    ),
                    'default_tab' => array(
                        'type' => 'string',
                        'default' => 'chat',
                        'description' => __('Default active tab', 'vortex-ai-marketplace')
                    ),
                    'available_tabs' => array(
                        'type' => 'string',
                        'default' => 'chat,artwork,tola,web3,ai,events',
                        'description' => __('Comma-separated list of available tabs', 'vortex-ai-marketplace')
                    )
                )
            ),
            'vortex_img2img' => array(
                'file' => VORTEX_PLUGIN_PATH . 'public/shortcodes/img2img-interface-shortcode.php',
                'callback' => 'vortex_img2img_interface_shortcode',
                'description' => __('Displays the HURAII image transformation interface', 'vortex-ai-marketplace'),
                'attributes' => array(
                    'width' => array(
                        'type' => 'number',
                        'default' => 768,
                        'description' => __('Canvas width', 'vortex-ai-marketplace')
                    ),
                    'height' => array(
                        'type' => 'number',
                        'default' => 768,
                        'description' => __('Canvas height', 'vortex-ai-marketplace')
                    ),
                    'advanced' => array(
                        'type' => 'boolean',
                        'default' => false,
                        'description' => __('Show advanced options', 'vortex-ai-marketplace')
                    ),
                    'modes' => array(
                        'type' => 'string',
                        'default' => 'style_transfer,inpainting,upscaling,enhancement',
                        'description' => __('Comma-separated list of available modes', 'vortex-ai-marketplace')
                    ),
                    'image' => array(
                        'type' => 'string',
                        'default' => '',
                        'description' => __('Initial image URL', 'vortex-ai-marketplace')
                    ),
                    'prompt' => array(
                        'type' => 'string',
                        'default' => '',
                        'description' => __('Initial prompt', 'vortex-ai-marketplace')
                    )
                )
            ),
            'vortex_huraii_library' => array(
                'file' => VORTEX_PLUGIN_PATH . 'public/shortcodes/huraii-library-shortcode.php',
                'callback' => 'vortex_huraii_library_shortcode',
                'description' => __('Displays the user\'s HURAII image library', 'vortex-ai-marketplace'),
                'attributes' => array(
                    'limit' => array(
                        'type' => 'number',
                        'default' => 12,
                        'description' => __('Number of images to display', 'vortex-ai-marketplace')
                    ),
                    'columns' => array(
                        'type' => 'number',
                        'default' => 3,
                        'description' => __('Number of columns', 'vortex-ai-marketplace')
                    ),
                    'show_prompt' => array(
                        'type' => 'boolean',
                        'default' => true,
                        'description' => __('Show image prompts', 'vortex-ai-marketplace')
                    ),
                    'show_actions' => array(
                        'type' => 'boolean',
                        'default' => true,
                        'description' => __('Show image actions', 'vortex-ai-marketplace')
                    )
                )
            )
        );
        
        // Load each file
        foreach ($this->shortcodes as $tag => $config) {
            if (isset($config['file']) && file_exists($config['file'])) {
                require_once $config['file'];
            }
        }
    }
    
    /**
     * Get all registered shortcodes
     *
     * @return array Registered shortcodes
     */
    public function get_shortcodes() {
        return $this->shortcodes;
    }
    
    /**
     * Preview shortcode via Ajax
     */
    public function ajax_preview_shortcode() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_admin_nonce')) {
            wp_send_json_error(__('Security check failed', 'vortex-ai-marketplace'));
            return;
        }
        
        // Check if user has permission
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('You do not have permission to do this', 'vortex-ai-marketplace'));
            return;
        }
        
        // Get shortcode and attributes
        $shortcode = isset($_POST['shortcode']) ? sanitize_text_field($_POST['shortcode']) : '';
        $attributes = isset($_POST['attributes']) ? $this->sanitize_attributes($_POST['attributes']) : array();
        
        // Build shortcode string
        $shortcode_str = '[' . $shortcode;
        
        foreach ($attributes as $key => $value) {
            if ($value !== '') {
                $shortcode_str .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
        }
        
        $shortcode_str .= ']';
        
        // Get shortcode output
        $output = do_shortcode($shortcode_str);
        
        wp_send_json_success(array(
            'html' => $output,
            'shortcode' => $shortcode_str
        ));
    }
    
    /**
     * Sanitize shortcode attributes
     *
     * @param array $attributes Attributes to sanitize
     * @return array Sanitized attributes
     */
    private function sanitize_attributes($attributes) {
        $sanitized = array();
        
        foreach ($attributes as $key => $value) {
            $key = sanitize_text_field($key);
            
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_attributes($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
}

// Initialize the shortcode manager
new Vortex_Shortcode_Manager(); 