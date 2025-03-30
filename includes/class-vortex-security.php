<?php

class Vortex_Security {
    public function enhance_security() {
        // Add nonce verification
        add_action('wp_ajax_vortex_transaction', array($this, 'verify_transaction_nonce'));
        
        // Add rate limiting
        add_filter('vortex_pre_process', array($this, 'check_rate_limit'));
        
        // Add input sanitization
        add_filter('vortex_process_input', array($this, 'sanitize_user_input'));
    }

    /**
     * Rate limiting functionality for AI agent requests
     *
     * @since 1.0.0
     * @param string $action The action being performed
     * @param int $user_id The user ID
     * @param int $max_requests Maximum number of requests allowed
     * @param int $time_period Time period in seconds
     * @return bool|WP_Error True if allowed, WP_Error if rate limited
     */
    public function check_rate_limit($action, $user_id, $max_requests = 10, $time_period = 60) {
        $transient_key = "vortex_rate_limit_{$action}_{$user_id}";
        $request_count = get_transient($transient_key);
        
        if (false === $request_count) {
            // First request in this period
            set_transient($transient_key, 1, $time_period);
            return true;
        }
        
        if ($request_count >= $max_requests) {
            // Rate limit exceeded
            $error_message = sprintf(
                __('Rate limit exceeded. Please try again in %d seconds.', 'vortex-ai-marketplace'),
                get_option("_transient_timeout_{$transient_key}") - time()
            );
            
            // Log rate limit violation
            error_log(sprintf(
                'Rate limit exceeded: Action=%s, User=%d, IP=%s, Count=%d, Limit=%d',
                $action,
                $user_id,
                $_SERVER['REMOTE_ADDR'],
                $request_count,
                $max_requests
            ));
            
            return new WP_Error('rate_limited', $error_message);
        }
        
        // Increment request count
        set_transient($transient_key, $request_count + 1, get_option("_transient_timeout_{$transient_key}") - time());
        return true;
    }

    /**
     * Hook to check rate limits for HURAII agent
     */
    public function check_huraii_rate_limit() {
        // Verify nonce first
        check_ajax_referer('vortex_huraii_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : 'unknown';
        
        // Different rate limits for different operations
        $rate_limits = array(
            'vortex_generate_artwork' => array('max' => 5, 'period' => 300), // 5 requests per 5 minutes
            'vortex_analyze_artwork' => array('max' => 10, 'period' => 300), // 10 requests per 5 minutes
            'vortex_style_transfer' => array('max' => 5, 'period' => 300),   // 5 requests per 5 minutes
            'default' => array('max' => 20, 'period' => 300)                 // 20 requests per 5 minutes
        );
        
        $limit = isset($rate_limits[$action]) ? $rate_limits[$action] : $rate_limits['default'];
        $result = $this->check_rate_limit($action, $user_id, $limit['max'], $limit['period']);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            exit;
        }
    }

    /**
     * Hook to check rate limits for CLOE agent
     */
    public function check_cloe_rate_limit() {
        // Verify nonce first
        check_ajax_referer('vortex_cloe_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : 'unknown';
        
        // Different rate limits for different operations
        $rate_limits = array(
            'vortex_get_recommendations' => array('max' => 10, 'period' => 300), // 10 requests per 5 minutes
            'vortex_get_insights' => array('max' => 10, 'period' => 300),       // 10 requests per 5 minutes
            'default' => array('max' => 20, 'period' => 300)                    // 20 requests per 5 minutes
        );
        
        $limit = isset($rate_limits[$action]) ? $rate_limits[$action] : $rate_limits['default'];
        $result = $this->check_rate_limit($action, $user_id, $limit['max'], $limit['period']);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            exit;
        }
    }

    /**
     * Hook to check rate limits for Business Strategist agent
     */
    public function check_business_strategist_rate_limit() {
        // Verify nonce first
        check_ajax_referer('vortex_business_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : 'unknown';
        
        // Different rate limits for different operations
        $rate_limits = array(
            'vortex_get_business_plan' => array('max' => 5, 'period' => 3600),         // 5 requests per hour
            'vortex_process_business_quiz' => array('max' => 3, 'period' => 3600),     // 3 requests per hour
            'default' => array('max' => 10, 'period' => 3600)                          // 10 requests per hour
        );
        
        $limit = isset($rate_limits[$action]) ? $rate_limits[$action] : $rate_limits['default'];
        $result = $this->check_rate_limit($action, $user_id, $limit['max'], $limit['period']);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            exit;
        }
    }

    /**
     * Sanitize AI prompt for security
     * 
     * @param string $prompt Prompt text
     * @return string Sanitized prompt
     */
    private function sanitize_prompt($prompt) {
        // Basic sanitization
        $prompt = sanitize_textarea_field($prompt);
        
        // Check for potential SQL injection attempts
        $sql_patterns = array('/\bUNION\b/i', '/\bSELECT\b/i', '/\bFROM\b/i', '/\bDROP\b/i', '/\bTABLE\b/i');
        $has_sql = false;
        
        foreach ($sql_patterns as $pattern) {
            if (preg_match($pattern, $prompt)) {
                $has_sql = true;
                break;
            }
        }
        
        if ($has_sql) {
            // Log potential SQL injection attempt
            do_action('vortex_security_event', 'sql_injection_attempt', array(
                'prompt' => $prompt,
                'user_id' => get_current_user_id(),
                'ip' => $_SERVER['REMOTE_ADDR']
            ), 'critical');
            
            // Replace SQL keywords
            $prompt = preg_replace($sql_patterns, '***', $prompt);
        }
        
        // Check for prompt injection patterns
        $injection_patterns = array(
            '/ignore previous instructions/i',
            '/disregard (all|previous) constraints/i',
            '/bypass (all|previous|your) (instructions|programming)/i'
        );
        
        $has_injection = false;
        
        foreach ($injection_patterns as $pattern) {
            if (preg_match($pattern, $prompt)) {
                $has_injection = true;
                break;
            }
        }
        
        if ($has_injection) {
            // Log potential prompt injection attempt
            do_action('vortex_security_event', 'prompt_injection_attempt', array(
                'prompt' => $prompt,
                'user_id' => get_current_user_id(),
                'ip' => $_SERVER['REMOTE_ADDR']
            ), 'critical');
            
            // Replace injection attempts
            $prompt = preg_replace($injection_patterns, '[redacted]', $prompt);
        }
        
        return $prompt;
    }

    /**
     * Sanitize AI output to prevent harmful content
     * 
     * @param string $content Output content
     * @param string $type Content type
     * @return string Sanitized content
     */
    public function sanitize_ai_output($content, $type) {
        // Handle different content types
        switch ($type) {
            case 'html':
                // Allow only a safe subset of HTML
                $allowed_html = array(
                    'p' => array(),
                    'br' => array(),
                    'strong' => array(),
                    'em' => array(),
                    'ul' => array(),
                    'ol' => array(),
                    'li' => array(),
                    'h2' => array(),
                    'h3' => array(),
                    'h4' => array(),
                    'a' => array(
                        'href' => array(),
                        'title' => array(),
                        'target' => array()
                    )
                );
                
                return wp_kses($content, $allowed_html);
                
            case 'text':
                // Strip all HTML
                return wp_strip_all_tags($content);
                
            case 'json':
                // Validate and sanitize JSON
                $decoded = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Log JSON parsing error
                    do_action('vortex_security_event', 'json_parse_error', array(
                        'error' => json_last_error_msg(),
                        'content' => substr($content, 0, 100) . '...'
                    ), 'warning');
                    
                    return '{}'; // Return empty JSON object on error
                }
                
                // Recursively sanitize JSON values
                $sanitized = $this->sanitize_array_recursive($decoded);
                return json_encode($sanitized);
                
            default:
                // Default text sanitization
                return sanitize_text_field($content);
        }
    }

    /**
     * Recursively sanitize array values
     * 
     * @param array $array Input array
     * @return array Sanitized array
     */
    private function sanitize_array_recursive($array) {
        if (!is_array($array)) {
            return is_scalar($array) ? sanitize_text_field($array) : '';
        }
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->sanitize_array_recursive($value);
            } else {
                $array[$key] = is_scalar($value) ? sanitize_text_field($value) : '';
            }
        }
        
        return $array;
    }
} 