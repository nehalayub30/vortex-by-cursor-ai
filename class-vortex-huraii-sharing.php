<?php
/**
 * VORTEX HURAII Sharing and Download Handlers
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage HURAII
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_HURAII_Sharing Class
 * 
 * Handles sharing and downloading of HURAII-generated content
 */
class VORTEX_HURAII_Sharing {
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * AI agents for sharing
     */
    private $ai_agents = array();
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize AI agents
        $this->initialize_ai_agents();
        
        // Set up hooks
        $this->setup_hooks();
    }
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize AI agents for sharing
     */
    private function initialize_ai_agents() {
        $this->ai_agents['HURAII'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'content_sharing',
            'capabilities' => array(
                'download_tracking',
                'share_analysis',
                'user_preference_learning'
            )
        );
        
        // Initialize AI agent
        do_action('vortex_ai_agent_init', 'HURAII', 'content_sharing', 'active');
    }
    
    /**
     * Set up hooks
     */
    private function setup_hooks() {
        // AJAX handlers for downloads and sharing
        add_action('wp_ajax_vortex_download_huraii_content', array($this, 'handle_download'));
        add_action('wp_ajax_nopriv_vortex_download_huraii_content', array($this, 'handle_download'));
        
        // Add new handler for resolution upscaling
        add_action('wp_ajax_vortex_upscale_huraii_to_resolution', array($this, 'handle_upscale_to_resolution'));
        add_action('wp_ajax_nopriv_vortex_upscale_huraii_to_resolution', array($this, 'handle_upscale_to_resolution'));
    }
    
    /**
     * Handle content upscaling to specific resolution
     */
    public function handle_upscale_to_resolution() {
        check_ajax_referer('vortex-huraii-sharing-nonce', 'nonce');
        
        $file_path = isset($_POST['file_path']) ? sanitize_text_field($_POST['file_path']) : '';
        $target_resolution = isset($_POST['target_resolution']) ? sanitize_text_field($_POST['target_resolution']) : '4k';
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        
        if (empty($file_path)) {
            wp_send_json_error(array('message' => __('Invalid file path', 'vortex-marketplace')));
            return;
        }
        
        // Convert URL to file path if needed
        if (strpos($file_path, 'http') === 0) {
            $file_path = str_replace(
                wp_upload_dir()['baseurl'],
                wp_upload_dir()['basedir'],
                $file_path
            );
        }
        
        // Validate target resolution
        $valid_resolutions = array('4k', '2k', 'hd', '720p');
        if (!in_array($target_resolution, $valid_resolutions) && $target_resolution !== 'screen') {
            wp_send_json_error(array('message' => __('Invalid target resolution', 'vortex-marketplace')));
            return;
        }
        
        // Get HURAII instance
        $huraii = VORTEX_HURAII::get_instance();
        
        // Upscale the content
        $result = $huraii->upscale_to_resolution($file_path, $target_resolution, $settings);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Track upscale for AI learning
        do_action('vortex_ai_agent_learn', 'HURAII', 'content_resolution_upscaled', array(
            'file_path' => $file_path,
            'target_resolution' => $target_resolution,
            'result' => $result,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('timestamp')
        ));
        
        wp_send_json_success($result);
    }
} 