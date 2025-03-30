<?php
/**
 * Thorius API Manager
 * 
 * Manages connections to external AI APIs
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/api
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius API Manager
 */
class Vortex_Thorius_API_Manager {
    
    /**
     * OpenAI API client
     */
    private $openai_client;
    
    /**
     * Stability.ai API client
     */
    private $stability_client;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_api_clients();
    }
    
    /**
     * Initialize API clients
     */
    private function init_api_clients() {
        // Initialize OpenAI client
        $openai_key = get_option('vortex_thorius_openai_key', '');
        if (!empty($openai_key)) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-vortex-thorius-openai.php';
            $this->openai_client = new Vortex_Thorius_OpenAI($openai_key);
        }
        
        // Initialize Stability.ai client
        $stability_key = get_option('vortex_thorius_stability_key', '');
        if (!empty($stability_key)) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-vortex-thorius-stability.php';
            $this->stability_client = new Vortex_Thorius_Stability($stability_key);
        }
    }
    
    /**
     * Generate chat completion
     *
     * @param string $message User message
     * @param string $system_prompt System prompt
     * @return string|WP_Error Response or error
     */
    public function generate_chat_completion($message, $system_prompt = '') {
        if (!$this->openai_client) {
            return new WP_Error('api_error', __('OpenAI API key not configured', 'vortex-ai-marketplace'));
        }
        
        return $this->openai_client->chat_completion($message, $system_prompt);
    }
    
    /**
     * Generate image
     *
     * @param string $prompt Image description
     * @param string $style Image style
     * @param string $size Image size
     * @return string|WP_Error Image URL or error
     */
    public function generate_image($prompt, $style = 'realistic', $size = '1024x1024') {
        if (!$this->stability_client) {
            if (!$this->openai_client) {
                return new WP_Error('api_error', __('No image generation API configured', 'vortex-ai-marketplace'));
            }
            
            // Fall back to OpenAI if Stability.ai is not available
            return $this->openai_client->generate_image($prompt, $size);
        }
        
        return $this->stability_client->generate_image($prompt, $style, $size);
    }
    
    // Additional API methods...
} 