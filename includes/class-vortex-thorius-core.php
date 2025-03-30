<?php
/**
 * Thorius Core Class
 * 
 * Main coordinator for all Thorius AI functionalities
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Core Class
 */
class Vortex_Thorius_Core {
    
    /**
     * The agent orchestrator
     *
     * @var Vortex_Thorius_Orchestrator
     */
    private $orchestrator;
    
    /**
     * The deep learning integration
     *
     * @var Vortex_Thorius_Deep_Learning
     */
    private $deep_learning;
    
    /**
     * The analytics class
     *
     * @var Vortex_Thorius_Analytics
     */
    private $analytics;
    
    /**
     * Initialize the class
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Load orchestrator
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-orchestrator.php';
        $this->orchestrator = new Vortex_Thorius_Orchestrator();
        
        // Load deep learning
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/agents/class-vortex-thorius-deep-learning.php';
        $this->deep_learning = new Vortex_Thorius_Deep_Learning();
        
        // Load analytics
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-analytics.php';
        $this->analytics = new Vortex_Thorius_Analytics();
        
        // Load agent handler
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/agents/class-vortex-thorius-agent-handler.php';
        new Vortex_Thorius_Agent_Handler();
    }
    
    /**
     * Define hooks for Thorius core functionality
     */
    private function define_hooks() {
        // Register AJAX handlers
        add_action('wp_ajax_vortex_thorius_chat', array($this, 'process_chat_request'));
        add_action('wp_ajax_vortex_thorius_artwork', array($this, 'process_artwork_request'));
        add_action('wp_ajax_vortex_thorius_strategy', array($this, 'process_strategy_request'));
        add_action('wp_ajax_vortex_thorius_nft', array($this, 'process_nft_request'));
        
        // Allow some actions for non-logged in users
        add_action('wp_ajax_nopriv_vortex_thorius_chat', array($this, 'process_chat_request'));
        
        // Register shortcodes
        add_action('init', array($this, 'register_shortcodes'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        // Load shortcodes class
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/shortcodes/class-vortex-thorius-shortcodes.php';
        $shortcodes = new Vortex_Thorius_Shortcodes();
        $shortcodes->register_shortcodes();
    }
    
    /**
     * Enqueue necessary scripts and styles
     */
    public function enqueue_assets() {
        // Styles
        wp_enqueue_style(
            'vortex-thorius-styles',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/thorius-concierge.css',
            array(),
            VORTEX_AI_MARKETPLACE_VERSION
        );
        
        wp_enqueue_style(
            'vortex-thorius-enhanced-styles',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/thorius-enhanced.css',
            array('vortex-thorius-styles'),
            VORTEX_AI_MARKETPLACE_VERSION
        );
        
        // Scripts
        wp_enqueue_script(
            'vortex-thorius-performance',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/thorius-performance.js',
            array('jquery'),
            VORTEX_AI_MARKETPLACE_VERSION,
            true
        );
        
        wp_enqueue_script(
            'vortex-thorius-accessibility',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/thorius-accessibility.js',
            array('jquery'),
            VORTEX_AI_MARKETPLACE_VERSION,
            true
        );
        
        wp_enqueue_script(
            'vortex-thorius-concierge',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/thorius-concierge.js',
            array('jquery', 'vortex-thorius-performance', 'vortex-thorius-accessibility'),
            VORTEX_AI_MARKETPLACE_VERSION,
            true
        );
        
        wp_enqueue_script(
            'vortex-thorius-agents',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/thorius-agents.js',
            array('jquery', 'vortex-thorius-concierge'),
            VORTEX_AI_MARKETPLACE_VERSION,
            true
        );
        
        // Localize script with necessary data
        wp_localize_script(
            'vortex-thorius-concierge',
            'vortex_thorius',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vortex_thorius_nonce'),
                'user_logged_in' => is_user_logged_in(),
                'default_responses' => array(
                    'greeting' => __('Hello! I\'m Thorius, your AI concierge. How can I assist you today?', 'vortex-ai-marketplace'),
                    'fallback' => __('I\'m sorry, I couldn\'t process that request. Please try again.', 'vortex-ai-marketplace'),
                    'login_required' => __('This feature requires you to be logged in. Please log in to continue.', 'vortex-ai-marketplace')
                )
            )
        );
        
        // Register service worker if the browser supports it
        if (function_exists('wp_register_service_worker') && !is_admin()) {
            wp_register_service_worker(
                'thorius-service-worker',
                plugin_dir_url(dirname(__FILE__)) . 'assets/js/thorius-service-worker.js'
            );
        }
    }
    
    /**
     * Process chat request
     */
    public function process_chat_request() {
        // Validate nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_thorius_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Get message and sanitize
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        if (empty($message)) {
            wp_send_json_error(array('message' => 'Message cannot be empty'));
        }
        
        try {
            // Track interaction for analytics
            $this->analytics->track_analytics('chat_message', array(
                'message_length' => strlen($message)
            ));
            
            // Process the message
            // In a real implementation, this would integrate with an AI service
            // For now, we'll simulate a response
            
            $response = array(
                'message' => $this->generate_chat_response($message),
                'timestamp' => current_time('mysql')
            );
            
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Process artwork request
     */
    public function process_artwork_request() {
        // Validate nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_thorius_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Check if user is logged in for this feature
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in to use this feature'));
        }
        
        // Get parameters and sanitize
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        $style = isset($_POST['style']) ? sanitize_text_field($_POST['style']) : 'realistic';
        $size = isset($_POST['size']) ? sanitize_text_field($_POST['size']) : '1024x1024';
        
        if (empty($prompt)) {
            wp_send_json_error(array('message' => 'Prompt cannot be empty'));
        }
        
        try {
            // Track interaction for analytics
            $this->analytics->track_analytics('artwork_generation', array(
                'prompt_length' => strlen($prompt),
                'style' => $style,
                'size' => $size
            ));
            
            // Use deep learning to process the artwork request
            $result = $this->deep_learning->process_model('huraii', 'image_generation', array(
                'prompt' => $prompt,
                'style' => $style,
                'size' => $size
            ));
            
            if (!$result['success']) {
                throw new Exception($result['message']);
            }
            
            // In a real implementation, this would return the generated image
            // For now, we'll simulate a response with placeholder image URLs
            
            $response = array(
                'artwork' => array(
                    'id' => uniqid('art_'),
                    'prompt' => $prompt,
                    'style' => $style,
                    'image_url' => 'https://example.com/generated-art-' . rand(1, 1000) . '.jpg',
                    'thumbnail_url' => 'https://example.com/generated-art-thumb-' . rand(1, 1000) . '.jpg',
                ),
                'timestamp' => current_time('mysql')
            );
            
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Process strategy request
     */
    public function process_strategy_request() {
        // Similar implementation to process_artwork_request
        // Would use the strategist agent via orchestrator
    }
    
    /**
     * Process NFT request
     */
    public function process_nft_request() {
        // Similar implementation to process_artwork_request
        // Would handle NFT creation and minting
    }
    
    /**
     * Generate a chat response (simulation)
     *
     * @param string $message User message
     * @return string AI response
     */
    private function generate_chat_response($message) {
        // Simple simulation - in reality would call a language model API
        $message = strtolower($message);
        
        if (strpos($message, 'hello') !== false || strpos($message, 'hi ') !== false) {
            return 'Hello! I\'m Thorius, your AI concierge. How can I assist you today?';
        }
        
        if (strpos($message, 'art') !== false || strpos($message, 'image') !== false) {
            return 'I can help you create artwork with HURAII, our advanced AI art generation system. Just switch to the Artwork tab and describe what you want to create!';
        }
        
        if (strpos($message, 'nft') !== false || strpos($message, 'token') !== false) {
            return 'Interested in NFTs? I can help you create and mint your digital artwork as NFTs. Check out the NFT tab to get started.';
        }
        
        if (strpos($message, 'market') !== false || strpos($message, 'trend') !== false || strpos($message, 'strategy') !== false) {
            return 'Looking for market insights? Our Business Strategist can provide market analysis, trend predictions, and pricing optimization. Head to the Strategy tab to explore more.';
        }
        
        if (strpos($message, 'thank') !== false) {
            return 'You\'re welcome! If you need anything else, I\'m here to help.';
        }
        
        // Default response
        return 'I\'m here to help with artwork creation, NFT minting, and market strategy for digital art. How can I assist you today?';
    }
} 