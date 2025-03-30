    /**
     * Check rate limits
     */
    public function check_rate_limits() {
        if (!isset($this->settings['api_rate_limiting']) || !$this->settings['api_rate_limiting']) {
            return;
        }
        
        // Only check on API requests
        if (!$this->is_api_request()) {
            return;
        }
        
        $ip_address = $this->get_client_ip();
        $max_requests = isset($this->settings['max_requests_per_minute']) ? intval($this->settings['max_requests_per_minute']) : 60;
        
        // Check if already over limit
        $current_count = $this->get_request_count($ip_address);
        
        if ($current_count >= $max_requests) {
            // Log excessive requests
            $this->log_security_event('rate_limit', "Rate limit exceeded for IP: {$ip_address}", 'warning');
            
            // Return 429 Too Many Requests
            status_header(429);
            header('Retry-After: 60');
            echo json_encode(array('error' => 'Rate limit exceeded. Please try again later.'));
            exit;
        }
        
        // Increment request count
        $this->increment_request_count($ip_address);
    }
    
    /**
     * Check if current request is an API request
     * @return bool Whether current request is an API request
     */
    private function is_api_request() {
        return (
            (defined('REST_REQUEST') && REST_REQUEST) ||
            strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false ||
            strpos($_SERVER['REQUEST_URI'], '/vortex-api/') !== false
        );
    }
    
    /**
     * Get client IP address
     * @return string IP address
     */
    private function get_client_ip() {
        $ip_address = '';
        
        // Check for shared internet connection
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for IP from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        // Otherwise use the remote address
        else {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip_address);
    }
    
    /**
     * Get request count for IP
     * @param string $ip_address IP address
     * @return int Request count
     */
    private function get_request_count($ip_address) {
        $transient_name = 'vortex_rate_limit_' . md5($ip_address);
        $count = get_transient($transient_name);
        
        return $count ? intval($count) : 0;
    }
    
    /**
     * Increment request count for IP
     * @param string $ip_address IP address
     */
    private function increment_request_count($ip_address) {
        $transient_name = 'vortex_rate_limit_' . md5($ip_address);
        $current_count = $this->get_request_count($ip_address);
        
        // Set for 1 minute
        set_transient($transient_name, $current_count + 1, 60);
    }
    
    /**
     * Register security settings
     */
    public function register_security_settings() {
        register_setting('vortex_security_options', 'vortex_security_settings');
        
        add_settings_section(
            'vortex_security_section',
            'Security Settings',
            array($this, 'render_security_section'),
            'vortex-security'
        );
        
        add_settings_field(
            'vortex_admin_only_commands',
            'Admin-Only Commands',
            array($this, 'render_admin_only_commands_field'),
            'vortex-security',
            'vortex_security_section'
        );
        
        add_settings_field(
            'vortex_secure_storage',
            'Secure Storage',
            array($this, 'render_secure_storage_field'),
            'vortex-security',
            'vortex_security_section'
        );
        
        add_settings_field(
            'vortex_api_rate_limiting',
            'API Rate Limiting',
            array($this, 'render_api_rate_limiting_field'),
            'vortex-security',
            'vortex_security_section'
        );
    }
    
    /**
     * Log security event
     * @param string $type Event type
     * @param string $message Event message
     * @param string $level Event level (info, warning, error)
     */
    public function log_security_event($type, $message, $level = 'info') {
        global $wpdb;
        
        if (!isset($this->settings['log_security_events']) || !$this->settings['log_security_events']) {
            return;
        }
        
        $table_name = $wpdb->prefix . 'vortex_security_log';
        
        $wpdb->insert(
            $table_name,
            array(
                'log_type' => $type,
                'log_level' => $level,
                'message' => $message,
                'timestamp' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'ip_address' => $this->get_client_ip()
            )
        );
        
        if ($level == 'error' || $level == 'warning') {
            error_log("Vortex Security [{$level}]: {$message}");
        }
    }
}

// Initialize security
add_action('plugins_loaded', function() {
    VORTEX_Security::get_instance();
}); 