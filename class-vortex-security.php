<?php
/**
 * VORTEX Security Handler
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Core
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Security Class
 * 
 * Manages security for all plugin operations
 * particularly focusing on blockchain transactions and AI interactions.
 *
 * @since 1.0.0
 */
class VORTEX_Security {
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize security measures
        $this->initialize_security();
        
        // Set up hooks
        add_action('init', array($this, 'security_headers'));
        add_filter('vortex_validate_transaction', array($this, 'validate_transaction'), 10, 2);
        add_filter('vortex_validate_smart_contract', array($this, 'validate_smart_contract'), 10, 2);
        add_filter('vortex_validate_ai_request', array($this, 'validate_ai_request'), 10, 2);
        
        // AJAX request validation
        add_action('wp_ajax_nopriv_vortex_marketplace_action', array($this, 'validate_ajax_request'), 1);
        add_action('wp_ajax_vortex_marketplace_action', array($this, 'validate_ajax_request'), 1);
        
        // Rate limiting for API endpoints
        add_action('rest_api_init', array($this, 'register_rate_limiting'));
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
     * Initialize security measures
     */
    private function initialize_security() {
        // Set up nonce verification for actions
        add_action('wp_enqueue_scripts', array($this, 'enqueue_security_assets'));
    }
    
    /**
     * Add security headers
     */
    public function security_headers() {
        if (!headers_sent()) {
            // Content Security Policy - adjust as needed for integrations
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://*.vortex-marketplace.com;");
            
            // XSS Protection
            header("X-XSS-Protection: 1; mode=block");
            
            // Frame options
            header("X-Frame-Options: SAMEORIGIN");
            
            // Content Type Options
            header("X-Content-Type-Options: nosniff");
            
            // Referrer Policy
            header("Referrer-Policy: strict-origin-when-cross-origin");
        }
    }
    
    /**
     * Enqueue security assets
     */
    public function enqueue_security_assets() {
        // Add nonces for AJAX requests
        wp_localize_script('vortex-marketplace', 'vortex_security', array(
            'ajax_nonce' => wp_create_nonce('vortex_ajax_nonce'),
            'blockchain_nonce' => wp_create_nonce('vortex_blockchain_nonce'),
            'ai_request_nonce' => wp_create_nonce('vortex_ai_request_nonce'),
            'form_nonce' => wp_create_nonce('vortex_form_nonce')
        ));
    }
    
    /**
     * Validate transaction
     */
    public function validate_transaction($valid, $transaction_data) {
        // Verify user authentication
        if (!is_user_logged_in() && !isset($transaction_data['guest_token'])) {
            return false;
        }
        
        // Validate nonce if present
        if (isset($transaction_data['security_nonce'])) {
            if (!wp_verify_nonce($transaction_data['security_nonce'], 'vortex_blockchain_nonce')) {
                return false;
            }
        }
        
        // Verify TOLA token presence and format for blockchain transactions
        if (isset($transaction_data['token_type']) && $transaction_data['token_type'] === 'TOLA') {
            if (!$this->validate_tola_token($transaction_data)) {
                return false;
            }
        }
        
        // Validate transaction amounts are positive and not zero
        if (isset($transaction_data['amount']) && (!is_numeric($transaction_data['amount']) || $transaction_data['amount'] <= 0)) {
            return false;
        }
        
        // Verify no replay attacks (transaction timestamps)
        if (isset($transaction_data['timestamp'])) {
            $time_diff = time() - $transaction_data['timestamp'];
            if ($time_diff > 300 || $time_diff < -60) { // Within 5 minutes in the past or 1 minute in future
                return false;
            }
        }
        
        // Log valid transaction attempt for audit
        $this->log_security_event('transaction_validation', array(
            'status' => 'valid',
            'user_id' => get_current_user_id(),
            'transaction_type' => $transaction_data['type'] ?? 'unknown',
            'timestamp' => current_time('mysql')
        ));
        
        return $valid;
    }
    
    /**
     * Validate TOLA token
     */
    private function validate_tola_token($transaction_data) {
        // Verify token format (regex pattern for TOLA addresses)
        if (isset($transaction_data['wallet_address'])) {
            $tola_address_pattern = '/^(TOLA|tola)[a-zA-Z0-9]{34,42}$/';
            if (!preg_match($tola_address_pattern, $transaction_data['wallet_address'])) {
                return false;
            }
        }
        
        // Additional TOLA-specific validations would go here
        
        return true;
    }
    
    /**
     * Validate smart contract
     */
    public function validate_smart_contract($valid, $contract_data) {
        // Verify user authentication for contract operations
        if (!is_user_logged_in() && !current_user_can('manage_options')) {
            return false;
        }
        
        // Validate nonce
        if (isset($contract_data['security_nonce'])) {
            if (!wp_verify_nonce($contract_data['security_nonce'], 'vortex_blockchain_nonce')) {
                return false;
            }
        }
        
        // Verify contract integrity
        if (isset($contract_data['contract_hash'])) {
            // Verify contract hash matches expected format
            $hash_pattern = '/^0x[a-fA-F0-9]{64}$/';
            if (!preg_match($hash_pattern, $contract_data['contract_hash'])) {
                return false;
            }
        }
        
        // Verify royalty settings are within acceptable range
        if (isset($contract_data['royalty_percentage'])) {
            $royalty = floatval($contract_data['royalty_percentage']);
            if ($royalty < 0 || $royalty > 20) { // Max 20% total royalties
                return false;
            }
        }
        
        // Log valid contract operation
        $this->log_security_event('smart_contract_validation', array(
            'status' => 'valid',
            'user_id' => get_current_user_id(),
            'contract_type' => $contract_data['type'] ?? 'unknown',
            'timestamp' => current_time('mysql')
        ));
        
        return $valid;
    }
    
    /**
     * Validate AI request
     */
    public function validate_ai_request($valid, $request_data) {
        // Validate nonce
        if (isset($request_data['security_nonce'])) {
            if (!wp_verify_nonce($request_data['security_nonce'], 'vortex_ai_request_nonce')) {
                return false;
            }
        }
        
        // Validate AI request parameters - sanitize inputs
        if (isset($request_data['prompt'])) {
            $sanitized_prompt = sanitize_textarea_field($request_data['prompt']);
            if (empty($sanitized_prompt) || $sanitized_prompt !== $request_data['prompt']) {
                return false;
            }
        }
        
        // Check for prohibited content in AI requests
        if (isset($request_data['prompt']) && $this->contains_prohibited_content($request_data['prompt'])) {
            return false;
        }
        
        // Rate limiting for AI requests
        $user_id = get_current_user_id();
        if ($user_id && !$this->check_ai_rate_limit($user_id, $request_data['ai_agent'] ?? '')) {
            return false;
        }
        
        // Log valid AI request
        $this->log_security_event('ai_request_validation', array(
            'status' => 'valid',
            'user_id' => $user_id,
            'ai_agent' => $request_data['ai_agent'] ?? 'unknown',
            'timestamp' => current_time('mysql')
        ));
        
        return $valid;
    }
    
    /**
     * Check if content contains prohibited terms
     */
    private function contains_prohibited_content($content) {
        $prohibited_terms = array(
            'hack', 'exploit', 'vulnerability', 'malware', 'phishing',
            'attack', 'steal', 'fraud', 'illegal', 'criminal'
        );
        
        $content = strtolower($content);
        foreach ($prohibited_terms as $term) {
            if (strpos($content, $term) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check AI rate limit
     */
    private function check_ai_rate_limit($user_id, $ai_agent) {
        $limit_key = 'vortex_ai_rate_limit_' . $user_id . '_' . $ai_agent;
        $current_count = get_transient($limit_key);
        
        if ($current_count === false) {
            // First request in this time period
            set_transient($limit_key, 1, 60); // 1 minute window
            return true;
        } else if ($current_count < 10) { // Allow 10 requests per minute
            // Increment counter
            set_transient($limit_key, $current_count + 1, 60);
            return true;
        }
        
        // Rate limit exceeded
        return false;
    }
    
    /**
     * Validate AJAX request
     */
    public function validate_ajax_request() {
        if (!check_ajax_referer('vortex_ajax_nonce', 'security', false)) {
            wp_send_json_error(array('message' => 'Security validation failed'));
            exit;
        }
    }
    
    /**
     * Register rate limiting for REST API
     */
    public function register_rate_limiting() {
        add_filter('rest_pre_dispatch', array($this, 'apply_rest_rate_limiting'), 10, 3);
    }
    
    /**
     * Apply rate limiting to REST API
     */
    public function apply_rest_rate_limiting($result, $server, $request) {
        $route = $request->get_route();
        
        // Only apply to plugin endpoints
        if (strpos($route, '/vortex/') === 0) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $limit_key = 'vortex_api_rate_limit_' . md5($ip . $route);
            $current_count = get_transient($limit_key);
            
            if ($current_count === false) {
                set_transient($limit_key, 1, 60);
            } else if ($current_count < 30) { // 30 requests per minute
                set_transient($limit_key, $current_count + 1, 60);
            } else {
                return new WP_Error(
                    'rest_rate_limited',
                    'Rate limit exceeded',
                    array('status' => 429)
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Log security event
     */
    private function log_security_event($event_type, $data) {
        if (!isset($data['timestamp'])) {
            $data['timestamp'] = current_time('mysql');
        }
        
        $data['ip'] = $_SERVER['REMOTE_ADDR'];
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_security_log';
        
        $wpdb->insert(
            $table_name,
            array(
                'event_type' => $event_type,
                'event_data' => maybe_serialize($data),
                'date_created' => $data['timestamp']
            )
        );
    }
}

// Initialize Security Manager
VORTEX_Security::get_instance(); 