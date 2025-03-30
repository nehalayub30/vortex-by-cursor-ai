<?php
/**
 * Thorius Agent Handler
 * 
 * Manages AI agent dispatching and responses
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/agents
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Agent Handler
 */
class Vortex_Thorius_Agent_Handler {
    
    /**
     * Deep learning integration
     *
     * @var Vortex_Thorius_Deep_Learning
     */
    private $deep_learning;
    
    /**
     * Analytics integration
     *
     * @var Vortex_Thorius_Analytics
     */
    private $analytics;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize deep learning
        require_once plugin_dir_path(__FILE__) . 'class-vortex-thorius-deep-learning.php';
        $this->deep_learning = new Vortex_Thorius_Deep_Learning();
        
        // Initialize analytics
        require_once plugin_dir_path(dirname(__FILE__)) . 'class-vortex-thorius-analytics.php';
        $this->analytics = new Vortex_Thorius_Analytics();
        
        // Add AJAX handlers
        add_action('wp_ajax_vortex_thorius_agent_request', array($this, 'handle_agent_request'));
        add_action('wp_ajax_nopriv_vortex_thorius_agent_request', array($this, 'handle_agent_request'));
    }
    
    /**
     * Handle agent request via AJAX
     */
    public function handle_agent_request() {
        check_ajax_referer('vortex_thorius_nonce', 'nonce');
        
        $agent = sanitize_text_field($_POST['agent'] ?? '');
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        $params = isset($_POST['params']) ? $this->sanitize_params($_POST['params']) : array();
        
        // Track request in analytics
        $this->analytics->track_analytics('agent_request', array(
            'agent' => $agent,
            'action' => $action
        ));
        
        // Process request based on agent
        $response = $this->process_agent_request($agent, $action, $prompt, $params);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message()
            ));
        } else {
            wp_send_json_success($response);
        }
        
        wp_die();
    }
    
    /**
     * Process agent request
     *
     * @param string $agent Agent identifier
     * @param string $action Action to perform
     * @param string $prompt User prompt
     * @param array $params Additional parameters
     * @return array|WP_Error Response data or error
     */
    private function process_agent_request($agent, $action, $prompt, $params) {
        try {
            switch ($agent) {
                case 'huraii':
                    return $this->process_huraii_request($action, $prompt, $params);
                    
                case 'cloe':
                    return $this->process_cloe_request($action, $prompt, $params);
                    
                case 'strategist':
                    return $this->process_strategist_request($action, $prompt, $params);
                    
                default:
                    return new WP_Error('invalid_agent', __('Invalid agent specified', 'vortex-ai-marketplace'));
            }
        } catch (Exception $e) {
            return new WP_Error('agent_error', $e->getMessage());
        }
    }
    
    /**
     * Process HURAII agent request (artwork generation)
     */
    private function process_huraii_request($action, $prompt, $params) {
        switch ($action) {
            case 'generate_artwork':
                $style = sanitize_text_field($params['style'] ?? 'realistic');
                $size = sanitize_text_field($params['size'] ?? '1024x1024');
                
                return $this->deep_learning->generate_artwork($prompt, $style, $size);
                
            case 'modify_artwork':
                $image_url = esc_url_raw($params['image_url'] ?? '');
                $modification = sanitize_text_field($params['modification'] ?? '');
                
                return $this->deep_learning->modify_artwork($image_url, $prompt, $modification);
                
            default:
                return new WP_Error('invalid_action', __('Invalid action for HURAII', 'vortex-ai-marketplace'));
        }
    }
    
    /**
     * Process CLOE agent request (chat)
     */
    private function process_cloe_request($action, $prompt, $params) {
        switch ($action) {
            case 'chat':
                $context = isset($params['context']) ? sanitize_textarea_field($params['context']) : '';
                
                return $this->deep_learning->generate_chat_response($prompt, $context);
                
            case 'search_artworks':
                $filters = isset($params['filters']) ? $this->sanitize_params($params['filters']) : array();
                
                return $this->deep_learning->search_artworks($prompt, $filters);
                
            default:
                return new WP_Error('invalid_action', __('Invalid action for CLOE', 'vortex-ai-marketplace'));
        }
    }
    
    /**
     * Process Strategist agent request (business analysis)
     */
    private function process_strategist_request($action, $prompt, $params) {
        switch ($action) {
            case 'market_analysis':
                $market = sanitize_text_field($params['market'] ?? 'nft');
                $timeframe = sanitize_text_field($params['timeframe'] ?? '30days');
                
                return $this->deep_learning->generate_market_analysis($market, $timeframe, $prompt);
                
            case 'price_optimization':
                $current_price = floatval($params['current_price'] ?? 0);
                $market = sanitize_text_field($params['market'] ?? 'nft');
                
                return $this->deep_learning->optimize_price($current_price, $market, $prompt);
                
            case 'trend_prediction':
                $market = sanitize_text_field($params['market'] ?? 'nft');
                $timeframe = sanitize_text_field($params['timeframe'] ?? '30days');
                
                return $this->deep_learning->predict_trends($market, $timeframe, $prompt);
                
            default:
                return new WP_Error('invalid_action', __('Invalid action for Strategist', 'vortex-ai-marketplace'));
        }
    }
    
    /**
     * Sanitize parameters array
     *
     * @param array $params Parameters to sanitize
     * @return array Sanitized parameters
     */
    private function sanitize_params($params) {
        if (!is_array($params)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $sanitized[sanitize_key($key)] = $this->sanitize_params($value);
            } else {
                $sanitized[sanitize_key($key)] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
} 