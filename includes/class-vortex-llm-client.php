<?php
/**
 * LLM Client to handle API requests to different providers
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class for handling LLM API requests to different providers
 */
class Vortex_LLM_Client {
    /**
     * AI settings
     */
    private $settings;
    
    /**
     * Initialize the class
     */
    public function __construct() {
        $this->settings = get_option('vortex_ai_settings', array());
    }
    
    /**
     * Get the appropriate LLM client for a task
     *
     * @param string $task The task type (artwork, market, strategy)
     * @return string The provider to use
     */
    public function get_provider_for_task($task) {
        // Check if custom API keys are enabled
        $api_source = isset($this->settings['api_source']) ? $this->settings['api_source'] : 'built_in';
        
        // If using built-in keys, default to Vortex AI systems
        if ($api_source === 'built_in') {
            switch ($task) {
                case 'artwork':
                    return 'huraii';
                case 'market':
                    return 'cloe';
                case 'strategy':
                    return 'strategist';
                default:
                    return 'huraii';
            }
        }
        
        // Use task-specific provider if that option is selected
        if (isset($this->settings['provider_priority']) && $this->settings['provider_priority'] === 'task_specific') {
            $task_setting = 'task_' . $task;
            
            if (isset($this->settings[$task_setting])) {
                return $this->settings[$task_setting];
            }
        }
        
        // Otherwise, use priority-based selection
        $provider_priority = isset($this->settings['provider_priority']) ? $this->settings['provider_priority'] : 'huraii_first';
        
        if ($provider_priority === 'huraii_first') {
            // Try native Vortex AI systems first
            switch ($task) {
                case 'artwork':
                    return isset($this->settings['huraii_enabled']) && $this->settings['huraii_enabled'] ? 'huraii' : $this->get_first_available_external_provider();
                case 'market':
                    return isset($this->settings['cloe_enabled']) && $this->settings['cloe_enabled'] ? 'cloe' : $this->get_first_available_external_provider();
                case 'strategy':
                    return isset($this->settings['strategist_enabled']) && $this->settings['strategist_enabled'] ? 'strategist' : $this->get_first_available_external_provider();
                default:
                    return 'huraii';
            }
        } else {
            // Try external providers first
            $external_provider = $this->get_first_available_external_provider();
            if ($external_provider) {
                return $external_provider;
            }
            
            // Fall back to Vortex AI systems
            switch ($task) {
                case 'artwork':
                    return 'huraii';
                case 'market':
                    return 'cloe';
                case 'strategy':
                    return 'strategist';
                default:
                    return 'huraii';
            }
        }
    }
    
    /**
     * Get the first available external provider
     *
     * @return string|null Provider name or null if none available
     */
    private function get_first_available_external_provider() {
        if (isset($this->settings['openai_enabled']) && $this->settings['openai_enabled'] && !empty($this->settings['openai_api_key'])) {
            return 'openai';
        } elseif (isset($this->settings['anthropic_enabled']) && $this->settings['anthropic_enabled'] && !empty($this->settings['anthropic_api_key'])) {
            return 'anthropic';
        } elseif (isset($this->settings['google_enabled']) && $this->settings['google_enabled'] && !empty($this->settings['google_api_key'])) {
            return 'google';
        } elseif (isset($this->settings['grok_enabled']) && $this->settings['grok_enabled'] && !empty($this->settings['grok_api_key'])) {
            return 'grok';
        }
        
        return null;
    }
    
    /**
     * Make an LLM API request
     *
     * @param string $task The task type
     * @param array $params Request parameters
     * @param int $user_id User ID (optional, defaults to current user)
     * @return array|WP_Error Response or error
     */
    public function request($task, $params, $user_id = null) {
        // Get current user if none provided
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // Check for access if this is a user-initiated request
        if ($user_id > 0) {
            $wallet_manager = Vortex_AI_Marketplace::get_instance()->wallet;
            
            // Check access
            if (!$wallet_manager->check_llm_api_access($user_id)) {
                return new WP_Error(
                    'access_denied',
                    __('You need TOLA tokens to access AI features', 'vortex-ai-marketplace')
                );
            }
        }
        
        // Get provider and make request
        $provider = $this->get_provider_for_task($task);
        
        // Ensure parameters are properly formatted
        if (!isset($params['prompt']) && !isset($params['messages'])) {
            return new WP_Error(
                'invalid_request',
                __('Missing prompt or messages in request parameters', 'vortex-ai-marketplace')
            );
        }
        
        // Add default parameters if not set
        $params = wp_parse_args($params, array(
            'temperature' => 0.7,
            'max_tokens' => $this->settings['max_tokens_per_request'] ?? 2048
        ));
        
        // Make request to appropriate provider
        $response = null;
        $request_start = microtime(true);
        
        try {
            switch ($provider) {
                case 'openai':
                    $response = $this->openai_request($params);
                    break;
                case 'anthropic':
                    $response = $this->anthropic_request($params);
                    break;
                case 'google':
                    $response = $this->google_request($params);
                    break;
                case 'grok':
                    $response = $this->grok_request($params);
                    break;
                case 'huraii':
                    $response = $this->huraii_request($params);
                    break;
                case 'cloe':
                    $response = $this->cloe_request($params);
                    break;
                case 'strategist':
                    $response = $this->strategist_request($params);
                    break;
                default:
                    return new WP_Error(
                        'invalid_provider',
                        __('Invalid or unavailable AI provider', 'vortex-ai-marketplace')
                    );
            }
        } catch (Exception $e) {
            return new WP_Error('request_error', $e->getMessage());
        }
        
        // Log request if enabled
        if ($response && !is_wp_error($response)) {
            $request_time = microtime(true) - $request_start;
            $this->log_request($provider, $params, array_merge($response, array('request_time' => $request_time)));
        }
        
        return $response;
    }
    
    /**
     * Make request to OpenAI API
     *
     * @param array $params Request parameters
     * @return array|WP_Error Response or error
     */
    private function openai_request($params) {
        $api_key = $this->settings['openai_api_key'];
        $model = $this->settings['openai_model'] ?? 'gpt-4o';
        
        $request_data = array(
            'model' => $model,
            'messages' => isset($params['messages']) ? $params['messages'] : array(
                array('role' => 'user', 'content' => $params['prompt'])
            ),
            'max_tokens' => isset($params['max_tokens']) ? $params['max_tokens'] : $this->settings['max_tokens_per_request']
        );
        
        if (isset($params['temperature'])) {
            $request_data['temperature'] = $params['temperature'];
        }
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($request_data),
            'timeout' => $this->settings['request_timeout'] ?? 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status = wp_remote_retrieve_response_code($response);
        if ($status !== 200) {
            return new WP_Error(
                'openai_error', 
                sprintf(__('OpenAI API error: %s', 'vortex-ai-marketplace'), $status)
            );
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return array(
            'provider' => 'openai',
            'model' => $model,
            'content' => $body['choices'][0]['message']['content'],
            'raw_response' => $body
        );
    }
    
    /**
     * Make request to Anthropic API
     *
     * @param array $params Request parameters
     * @return array|WP_Error Response or error
     */
    private function anthropic_request($params) {
        $api_key = $this->settings['anthropic_api_key'];
        $model = $this->settings['anthropic_model'] ?? 'claude-3-opus';
        
        // Convert to Anthropic messages format if needed
        $messages = isset($params['messages']) ? $params['messages'] : array(
            array('role' => 'user', 'content' => $params['prompt'])
        );
        
        $request_data = array(
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => isset($params['max_tokens']) ? $params['max_tokens'] : $this->settings['max_tokens_per_request']
        );
        
        if (isset($params['temperature'])) {
            $request_data['temperature'] = $params['temperature'];
        }
        
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($request_data),
            'timeout' => $this->settings['request_timeout'] ?? 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status = wp_remote_retrieve_response_code($response);
        if ($status !== 200) {
            return new WP_Error(
                'anthropic_error', 
                sprintf(__('Anthropic API error: %s', 'vortex-ai-marketplace'), $status)
            );
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return array(
            'provider' => 'anthropic',
            'model' => $model,
            'content' => $body['content'][0]['text'],
            'raw_response' => $body
        );
    }
    
    /**
     * Make request to Google AI API
     *
     * @param array $params Request parameters
     * @return array|WP_Error Response or error
     */
    private function google_request($params) {
        $api_key = $this->settings['google_api_key'];
        $model = $this->settings['google_model'] ?? 'gemini-pro';
        
        // Convert to Google messages format if needed
        $messages = array();
        if (isset($params['messages'])) {
            foreach ($params['messages'] as $message) {
                $messages[] = array(
                    'role' => $message['role'],
                    'parts' => array(
                        array('text' => $message['content'])
                    )
                );
            }
        } else {
            $messages[] = array(
                'role' => 'user',
                'parts' => array(
                    array('text' => $params['prompt'])
                )
            );
        }
        
        $request_data = array(
            'contents' => $messages,
            'generationConfig' => array(
                'maxOutputTokens' => isset($params['max_tokens']) ? $params['max_tokens'] : $this->settings['max_tokens_per_request']
            )
        );
        
        if (isset($params['temperature'])) {
            $request_data['generationConfig']['temperature'] = $params['temperature'];
        }
        
        $endpoint = 'https://generativelanguage.googleapis.com/v1/models/' . $model . ':generateContent?key=' . $api_key;
        
        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($request_data),
            'timeout' => $this->settings['request_timeout'] ?? 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status = wp_remote_retrieve_response_code($response);
        if ($status !== 200) {
            return new WP_Error(
                'google_error', 
                sprintf(__('Google AI API error: %s', 'vortex-ai-marketplace'), $status)
            );
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return array(
            'provider' => 'google',
            'model' => $model,
            'content' => $body['candidates'][0]['content']['parts'][0]['text'],
            'raw_response' => $body
        );
    }
    
    /**
     * Make request to HURAII API
     *
     * @param array $params Request parameters
     * @return array|WP_Error Response or error
     */
    private function huraii_request($params) {
        // Implement HURAII API request
        // For now, return a simulated response
        return array(
            'provider' => 'huraii',
            'model' => $this->settings['huraii_model'] ?? 'balanced',
            'content' => 'Response from HURAII API: ' . ($params['prompt'] ?? ''),
            'raw_response' => array('status' => 'success')
        );
    }
    
    /**
     * Make request to CLOE API
     *
     * @param array $params Request parameters
     * @return array|WP_Error Response or error
     */
    private function cloe_request($params) {
        // Implement CLOE API request
        // For now, return a simulated response
        return array(
            'provider' => 'cloe',
            'model' => $this->settings['cloe_model'] ?? 'market-balanced',
            'content' => 'Market analysis from CLOE: ' . ($params['prompt'] ?? ''),
            'raw_response' => array('status' => 'success')
        );
    }
    
    /**
     * Make request to Business Strategist API
     *
     * @param array $params Request parameters
     * @return array|WP_Error Response or error
     */
    private function strategist_request($params) {
        // Implement Business Strategist API request
        // For now, return a simulated response
        return array(
            'provider' => 'strategist',
            'model' => $this->settings['strategist_model'] ?? 'analyst-pro',
            'content' => 'Business strategy recommendation: ' . ($params['prompt'] ?? ''),
            'raw_response' => array('status' => 'success')
        );
    }
    
    /**
     * Make request to Grok API
     *
     * @param array $params Request parameters
     * @return array|WP_Error Response or error
     */
    private function grok_request($params) {
        $api_key = $this->settings['grok_api_key'] ?? '';
        if (empty($api_key)) {
            return new WP_Error('missing_api_key', __('Grok API key is not configured', 'vortex-ai-marketplace'));
        }
        
        $model = $this->settings['grok_model'] ?? 'grok-1';
        
        // Format messages for Grok API
        $messages = array();
        if (isset($params['messages'])) {
            $messages = $params['messages'];
        } elseif (isset($params['prompt'])) {
            $messages = array(
                array(
                    'role' => 'user',
                    'content' => $params['prompt']
                )
            );
        }
        
        $request_data = array(
            'model' => $model,
            'messages' => $messages,
            'temperature' => floatval($params['temperature']),
            'max_tokens' => intval($params['max_tokens'])
        );
        
        // Grok API uses a similar format to OpenAI
        $response = wp_remote_post('https://api.grok.ai/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($request_data),
            'timeout' => $this->settings['request_timeout'] ?? 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status = wp_remote_retrieve_response_code($response);
        if ($status !== 200) {
            return new WP_Error(
                'grok_error', 
                sprintf(__('Grok API error: %s', 'vortex-ai-marketplace'), $status)
            );
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return array(
            'provider' => 'grok',
            'model' => $model,
            'content' => $body['choices'][0]['message']['content'],
            'raw_response' => $body
        );
    }
    
    /**
     * Log API request
     *
     * @param string $provider Provider name
     * @param array $request Request data
     * @param array $response Response data
     */
    private function log_request($provider, $request, $response) {
        // Trigger action for usage tracking
        do_action('vortex_ai_api_request', $provider, $request, $response);
    }

    /**
     * Make request to HURAII for image generation
     *
     * @param array $params Request parameters
     * @return array|WP_Error Response or error
     */
    private function huraii_image_request($params) {
        // Check if HURAII is enabled for artwork
        if (empty($this->settings['huraii_enabled'])) {
            return new WP_Error('provider_disabled', __('HURAII is currently disabled', 'vortex-ai-marketplace'));
        }
        
        // Get API key (if any)
        $api_key = $this->settings['huraii_api_key'] ?? '';
        
        // In a production environment, this would make an actual API call to HURAII's image generation endpoint
        // For demonstration, we'll simulate a response with a placeholder image
        
        // Extract parameters
        $prompt = $params['prompt'] ?? '';
        $size = $params['size'] ?? '1024x1024';
        
        // Generate unique filename based on prompt
        $filename = 'huraii-' . md5($prompt . time()) . '.jpg';
        
        // In a real implementation, this would be the URL returned by the HURAII API
        // For demo purposes, use a placeholder or random unsplash image
        $sizes = explode('x', $size);
        $width = $sizes[0] ?? 1024;
        $height = $sizes[1] ?? 1024;
        
        $image_url = "https://picsum.photos/{$width}/{$height}?random=" . rand(1, 1000);
        
        // Log this request
        $token_count = strlen($prompt) / 4; // Rough estimate for token count in image generation
        
        return array(
            'provider' => 'huraii',
            'model' => 'huraii-diffusion',
            'content' => $image_url,
            'raw_response' => array(
                'status' => 'success',
                'token_count' => $token_count,
                'image' => array(
                    'url' => $image_url,
                    'prompt' => $prompt,
                    'size' => $size
                )
            )
        );
    }
} 