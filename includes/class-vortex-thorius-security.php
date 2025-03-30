<?php
/**
 * Thorius Security Manager
 * 
 * Handles security features for Thorius AI
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Security Manager
 */
class Vortex_Thorius_Security {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add security checks
        add_filter('vortex_thorius_before_process_query', array($this, 'check_query_security'), 10, 1);
        add_filter('vortex_thorius_admin_query', array($this, 'check_admin_query_security'), 10, 1);
        
        // Add AJAX security checks
        add_action('wp_ajax_vortex_thorius_query', array($this, 'verify_ajax_nonce'));
        add_action('wp_ajax_vortex_thorius_admin_query', array($this, 'verify_admin_ajax_nonce'));
        add_action('wp_ajax_vortex_thorius_generate_report', array($this, 'verify_admin_ajax_nonce'));
        
        // Register content sanitization
        add_filter('vortex_thorius_response', array($this, 'sanitize_response'), 10, 1);
    }
    
    /**
     * Check query security
     * 
     * @param string $query User query
     * @return string|WP_Error Sanitized query or error
     */
    public function check_query_security($query) {
        // Sanitize the query
        $query = sanitize_text_field($query);
        
        // Check for empty query
        if (empty($query)) {
            return new WP_Error('empty_query', __('Query cannot be empty.', 'vortex-ai-marketplace'));
        }
        
        // Check for malicious content
        if ($this->contains_forbidden_content($query)) {
            $this->log_security_event('forbidden_content', $query);
            return new WP_Error('forbidden_content', __('Query contains forbidden content.', 'vortex-ai-marketplace'));
        }
        
        // Check query length
        if (strlen($query) > 1000) {
            return new WP_Error('query_too_long', __('Query is too long. Please limit to 1000 characters.', 'vortex-ai-marketplace'));
        }
        
        return $query;
    }
    
    /**
     * Check admin query security
     * 
     * @param string $query Admin query
     * @return string|WP_Error Sanitized query or error
     */
    public function check_admin_query_security($query) {
        // Verify user has admin capabilities
        if (!current_user_can('manage_options')) {
            $this->log_security_event('unauthorized_admin_query', $query);
            return new WP_Error('unauthorized', __('You do not have permission to perform this action.', 'vortex-ai-marketplace'));
        }
        
        // Perform standard query security checks
        return $this->check_query_security($query);
    }
    
    /**
     * Verify AJAX nonce for frontend queries
     */
    public function verify_ajax_nonce() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_thorius_nonce')) {
            $this->log_security_event('invalid_nonce', 'AJAX frontend query');
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
            exit;
        }
    }
    
    /**
     * Verify AJAX nonce for admin queries
     */
    public function verify_admin_ajax_nonce() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_thorius_admin_nonce')) {
            $this->log_security_event('invalid_admin_nonce', 'AJAX admin query');
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
            exit;
        }
        
        // Verify user has admin capabilities
        if (!current_user_can('manage_options')) {
            $this->log_security_event('unauthorized_admin_ajax', 'AJAX admin query');
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'vortex-ai-marketplace')
            ));
            exit;
        }
    }
    
    /**
     * Sanitize AI response for output
     * 
     * @param array $response AI response data
     * @return array Sanitized response
     */
    public function sanitize_response($response) {
        if (isset($response['response'])) {
            // Allow certain HTML tags but sanitize the content
            $response['response'] = wp_kses($response['response'], array(
                'p' => array(),
                'br' => array(),
                'em' => array(),
                'strong' => array(),
                'ul' => array(),
                'ol' => array(),
                'li' => array(),
                'a' => array(
                    'href' => array(),
                    'title' => array(),
                    'target' => array()
                ),
                'code' => array(),
                'pre' => array()
            ));
        }
        
        // Sanitize other elements if present
        if (isset($response['title'])) {
            $response['title'] = sanitize_text_field($response['title']);
        }
        
        if (isset($response['actions'])) {
            foreach ($response['actions'] as $key => $action) {
                $response['actions'][$key]['label'] = sanitize_text_field($action['label']);
                if (isset($action['url'])) {
                    $response['actions'][$key]['url'] = esc_url_raw($action['url']);
                }
            }
        }
        
        return $response;
    }
    
    /**
     * Check if string contains forbidden content
     * 
     * @param string $content Content to check
     * @return bool True if forbidden content found
     */
    private function contains_forbidden_content($content) {
        // Define patterns for forbidden content (SQL injection, XSS, etc.)
        $forbidden_patterns = array(
            // SQL injection patterns
            '/(\s|^)(SELECT|INSERT|UPDATE|DELETE|DROP|ALTER)\s/i',
            // Script injection patterns
            '/<script/i',
            // Potentially malicious PHP patterns
            '/(\s|^)(eval|exec|system|shell_exec|passthru)/i'
        );
        
        foreach ($forbidden_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log security event
     * 
     * @param string $type Event type
     * @param string $content Content that triggered the event
     */
    private function log_security_event($type, $content) {
        if (!class_exists('WP_Error')) {
            require_once(ABSPATH . WPINC . '/class-wp-error.php');
        }
        
        // Get current user info
        $user_id = get_current_user_id();
        $user_ip = $this->get_user_ip();
        
        // Prepare log entry
        $log_entry = array(
            'type' => $type,
            'content' => substr($content, 0, 255), // Limit content length
            'user_id' => $user_id,
            'ip' => $user_ip,
            'timestamp' => current_time('mysql'),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown'
        );
        
        // Store log in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_thorius_security_log';
        
        $wpdb->insert(
            $table_name,
            $log_entry,
            array(
                '%s', // type
                '%s', // content
                '%d', // user_id
                '%s', // ip
                '%s', // timestamp
                '%s'  // user_agent
            )
        );
        
        // Optionally, you could send an email alert for serious security events
        if (in_array($type, array('forbidden_content', 'unauthorized_admin_query', 'unauthorized_admin_ajax'))) {
            $this->send_security_alert($type, $log_entry);
        }
    }
    
    /**
     * Get user IP address
     * 
     * @return string User IP
     */
    private function get_user_ip() {
        $ip = '';
        
        // Check for proxy forwarded IP
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } else if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        
        // Anonymize IP if the setting is enabled
        if (get_option('vortex_thorius_anonymize_ips', false)) {
            // For IPv4, remove the last octet
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ip = preg_replace('/\.\d+$/', '.0', $ip);
            } 
            // For IPv6, remove the last 80 bits (last 5 hextets)
            else if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $ip = preg_replace('/:[0-9a-f]{1,4}(:[0-9a-f]{1,4}){4}$/i', ':0:0:0:0:0', $ip);
            }
        }
        
        return $ip;
    }
    
    /**
     * Send security alert email
     * 
     * @param string $type Alert type
     * @param array $log_entry Log entry data
     */
    private function send_security_alert($type, $log_entry) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(__('[%s] Thorius AI Security Alert: %s', 'vortex-ai-marketplace'), $site_name, $type);
        
        $message = sprintf(__('A security event of type "%s" was detected on your website.', 'vortex-ai-marketplace'), $type) . "\n\n";
        $message .= __('Details:', 'vortex-ai-marketplace') . "\n";
        $message .= __('Timestamp:', 'vortex-ai-marketplace') . ' ' . $log_entry['timestamp'] . "\n";
        $message .= __('User ID:', 'vortex-ai-marketplace') . ' ' . $log_entry['user_id'] . "\n";
        $message .= __('IP Address:', 'vortex-ai-marketplace') . ' ' . $log_entry['ip'] . "\n";
        $message .= __('Content:', 'vortex-ai-marketplace') . ' ' . $log_entry['content'] . "\n\n";
        $message .= __('User Agent:', 'vortex-ai-marketplace') . ' ' . $log_entry['user_agent'] . "\n\n";
        $message .= __('Please check your website security logs for more information.', 'vortex-ai-marketplace');
        
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Enhanced security features
     */
    public function enable_advanced_security() {
        // Implement rate limiting for API calls
        add_filter('vortex_thorius_before_api_request', array($this, 'check_rate_limits'), 10, 2);
        
        // Add encryption for conversation data in transit and at rest
        add_filter('vortex_thorius_conversation_save', array($this, 'encrypt_sensitive_data'), 10, 1);
        add_filter('vortex_thorius_conversation_load', array($this, 'decrypt_sensitive_data'), 10, 1);
        
        // Implement Web Application Firewall (WAF) rules for AI endpoints
        add_action('init', array($this, 'setup_waf_protection'));
        
        // Add cross-site request forgery tokens to all forms
        add_action('vortex_thorius_before_form', array($this, 'add_csrf_token'));
        add_filter('vortex_thorius_process_form', array($this, 'verify_csrf_token'), 10, 1);
    }

    /**
     * Rate limiting for API requests
     */
    public function check_rate_limits($should_proceed, $request_type) {
        $user_id = get_current_user_id();
        $ip = $this->get_user_ip();
        $rate_key = $user_id ? "thorius_rate_{$request_type}_{$user_id}" : "thorius_rate_{$request_type}_{$ip}";
        
        // Get current count and time window
        $rate_data = get_transient($rate_key);
        
        if (!$rate_data) {
            // First request in window
            set_transient($rate_key, array('count' => 1, 'time' => time()), 60); // 1 minute window
            return $should_proceed;
        }
        
        // Get limits based on user role or settings
        $max_requests = $this->get_rate_limit_for_user($user_id, $request_type);
        
        if ($rate_data['count'] >= $max_requests) {
            $this->log_security_event('rate_limit_exceeded', $request_type);
            return new WP_Error('rate_limit_exceeded', 
                sprintf(__('Rate limit exceeded. Please wait before making additional requests. Maximum %d requests per minute.', 'vortex-ai-marketplace'), $max_requests)
            );
        }
        
        // Increment counter
        $rate_data['count']++;
        set_transient($rate_key, $rate_data, 60 - (time() - $rate_data['time']));
        
        return $should_proceed;
    }

    /**
     * Implement blockchain-based request verification
     * 
     * @param string $request_data Request data
     * @param string $signature Signature
     * @return bool Verification result
     */
    public function verify_with_blockchain($request_data, $signature) {
        // Connect to blockchain node
        $blockchain = new Vortex_Blockchain_Connection();
        
        // Verify signature
        $verification = $blockchain->verify_signature(
            $request_data,
            $signature,
            $this->get_public_verification_key()
        );
        
        // Log verification attempt
        $this->log_verification_attempt(
            $verification ? 'success' : 'failure',
            $request_data
        );
        
        return $verification;
    }

    /**
     * Create secure HMAC signature for API requests
     * 
     * @param array $params Request parameters
     * @param string $secret Secret key
     * @return string HMAC signature
     */
    public function create_hmac_signature($params, $secret) {
        // Sort parameters by key
        ksort($params);
        
        // Create parameter string
        $param_string = '';
        foreach ($params as $key => $value) {
            $param_string .= $key . '=' . $value . '&';
        }
        $param_string = rtrim($param_string, '&');
        
        // Add timestamp
        $timestamp = time();
        $param_string .= '&timestamp=' . $timestamp;
        
        // Create HMAC signature
        $signature = hash_hmac('sha256', $param_string, $secret);
        
        return array(
            'signature' => $signature,
            'timestamp' => $timestamp
        );
    }

    /**
     * Implement advanced rate limiting with dynamic quotas
     * 
     * @param string $api_key API key
     * @param string $endpoint Endpoint
     * @return bool Rate limit check result
     */
    public function check_dynamic_rate_limit($api_key, $endpoint) {
        // Get user tier
        $user_tier = $this->get_user_tier_from_api_key($api_key);
        
        // Get tier limits
        $tier_limits = $this->get_tier_limits($user_tier);
        
        // Get current usage
        $current_usage = $this->get_current_usage($api_key, $endpoint);
        
        // Check if usage exceeds limits
        if ($current_usage >= $tier_limits[$endpoint]) {
            // Log rate limit hit
            $this->log_rate_limit_hit($api_key, $endpoint, $current_usage, $tier_limits[$endpoint]);
            
            return false;
        }
        
        // Increment usage counter
        $this->increment_usage_counter($api_key, $endpoint);
        
        return true;
    }
} 