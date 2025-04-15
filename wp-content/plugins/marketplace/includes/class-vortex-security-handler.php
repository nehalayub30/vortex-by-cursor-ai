                $wpdb->update(
                    $wpdb->prefix . 'vortex_rate_limits',
                    array(
                        'request_count' => $count_data->request_count + 1,
                        'last_request' => $now
                    ),
                    array('id' => $count_data->id),
                    array('%d', '%s'),
                    array('%d')
                );
            }
        } else {
            // Create new entry
            $wpdb->insert(
                $wpdb->prefix . 'vortex_rate_limits',
                array(
                    'request_ip' => $client_ip,
                    'action_key' => $action,
                    'request_count' => 1,
                    'first_request' => $now,
                    'last_request' => $now
                ),
                array('%s', '%s', '%d', '%s', '%s')
            );
        }
    }
    
    /**
     * Log a request
     * 
     * @param string $action The AJAX action
     */
    private function log_request($action) {
        // Store in memory for potential security analysis
        $this->request_log[] = array(
            'action' => $action,
            'ip' => $this->get_client_ip(),
            'time' => microtime(true),
            'user_id' => get_current_user_id(),
            'request_data' => $this->sanitize_request_data($_REQUEST)
        );
        
        // Log to database if it's a sensitive action
        $sensitive_actions = array(
            'vortex_trigger_agent_learning',
            'vortex_toggle_integration',
            'vortex_verify_wallet',
            'vortex_add_wallet',
            'vortex_remove_wallet',
            'vortex_set_primary_wallet',
            'vortex_release_vested_tokens'
        );
        
        if (in_array($action, $sensitive_actions)) {
            $this->log_security_event(
                'sensitive_action_request',
                array(
                    'action' => $action,
                    'data' => $this->sanitize_request_data($_REQUEST)
                ),
                'info'
            );
        }
    }
    
    /**
     * Check request for potential security issues
     * 
     * @param string $action The AJAX action
     * @throws Exception if security issue is detected
     */
    private function check_request_security($action) {
        // Get sanitized request data
        $request_data = $this->sanitize_request_data($_REQUEST);
        
        // Check for suspicious input patterns
        if ($this->contains_suspicious_patterns($request_data)) {
            $this->log_security_event(
                'suspicious_input_detected',
                array(
                    'action' => $action,
                    'data' => $request_data
                ),
                'warning'
            );
            
            throw new Exception('Invalid request parameters', 400);
        }
        
        // Check for required nonce
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'vortex_nonce')) {
            $this->log_security_event(
                'nonce_verification_failed',
                array(
                    'action' => $action,
                    'data' => $request_data
                ),
                'warning'
            );
            
            throw new Exception('Security check failed', 403);
        }
        
        // Additional checks for sensitive actions
        if (in_array($action, array('vortex_trigger_agent_learning', 'vortex_toggle_integration'))) {
            if (!current_user_can('manage_options')) {
                $this->log_security_event(
                    'unauthorized_sensitive_action',
                    array(
                        'action' => $action,
                        'user_id' => get_current_user_id()
                    ),
                    'warning'
                );
                
                throw new Exception('You do not have permission to perform this action', 403);
            }
        }
    }
    
    /**
     * Log a security event
     * 
     * @param string $log_type Type of log
     * @param array $data Additional data
     * @param string $severity Severity level: info, warning, error, critical
     */
    public function log_security_event($log_type, $data = array(), $severity = 'info') {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_security_logs',
            array(
                'log_type' => $log_type,
                'user_id' => get_current_user_id(),
                'request_ip' => $this->get_client_ip(),
                'request_data' => wp_json_encode($data),
                'request_action' => isset($data['action']) ? $data['action'] : '',
                'severity' => $severity,
                'request_time' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        // For critical events, trigger immediate notification
        if ($severity === 'critical') {
            $this->notify_security_admin($log_type, $data);
        }
        
        // Hook for other actions based on security events
        do_action('vortex_security_event_logged', $log_type, $data, $severity);
    }
    
    /**
     * Enhanced error handler for AJAX requests
     * 
     * @param callable $callback The callback function
     * @param array $data The data for the callback
     * @return mixed The callback result
     */
    public function handle_ajax_error($callback, $data = array()) {
        try {
            return call_user_func($callback, $data);
        } catch (Exception $e) {
            $this->log_security_event(
                'ajax_error',
                array(
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'data' => $data,
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ),
                'error'
            );
            
            wp_send_json_error(array(
                'message' => 'An error occurred while processing your request.',
                'code' => $e->getCode()
            ));
            exit;
        }
    }
    
    /**
     * Sanitize request data for logging
     * 
     * @param array $data Request data
     * @return array Sanitized data
     */
    private function sanitize_request_data($data) {
        $sanitized = array();
        
        // Define sensitive keys to mask
        $sensitive_keys = array('password', 'pass', 'pwd', 'secret', 'key', 'token', 'nonce', 'signature');
        
        foreach ($data as $key => $value) {
            // Mask sensitive data
            if (in_array(strtolower($key), $sensitive_keys)) {
                $sanitized[$key] = '***REDACTED***';
            } 
            // Recursively sanitize arrays
            elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitize_request_data($value);
            } 
            // Truncate long values
            elseif (is_string($value) && strlen($value) > 500) {
                $sanitized[$key] = substr($value, 0, 500) . '... [truncated]';
            } 
            // Pass through other values
            else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Check if input contains suspicious patterns
     * 
     * @param array $data The data to check
     * @return bool True if suspicious patterns found
     */
    private function contains_suspicious_patterns($data) {
        $suspicious_patterns = array(
            '/<script/i',
            '/javascript:/i',
            '/eval\(/i',
            '/document\.cookie/i',
            '/\bonload\s*=/i',
            '/\)\s*\{.*?;.*?\}/is',
            '/\bSELECT\b.*?\bFROM\b/is',
            '/\bINSERT\b.*?\bINTO\b/is',
            '/\bUPDATE\b.*?\bSET\b/is',
            '/\bDELETE\b.*?\bFROM\b/is',
            '/\bUNION\b.*?\bSELECT\b/is',
            '/\bDROP\b.*?\bTABLE\b/is',
            '/\bALTER\b.*?\bTABLE\b/is'
        );
        
        // Check each value in the array
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->contains_suspicious_patterns($value)) {
                    return true;
                }
            } elseif (is_string($value)) {
                foreach ($suspicious_patterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP
     */
    private function get_client_ip() {
        // Check various server variables for IP
        $ip_sources = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_sources as $source) {
            if (isset($_SERVER[$source])) {
                $ip = $_SERVER[$source];
                
                // Handle multiple IPs in HTTP_X_FORWARDED_FOR
                if ($source === 'HTTP_X_FORWARDED_FOR') {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Notify security admin about a critical security event
     * 
     * @param string $event_type Type of event
     * @param array $data Event data
     */
    private function notify_security_admin($event_type, $data) {
        $admin_email = get_option('admin_email');
        if (!$admin_email) {
            return;
        }
        
        $subject = '[VORTEX Security Alert] ' . ucfirst(str_replace('_', ' ', $event_type));
        
        $message = "A critical security event has been detected by the VORTEX Security system.\n\n";
        $message .= "Event Type: " . ucfirst(str_replace('_', ' ', $event_type)) . "\n";
        $message .= "Time: " . current_time('mysql') . "\n";
        $message .= "IP Address: " . $this->get_client_ip() . "\n";
        $message .= "User ID: " . get_current_user_id() . "\n\n";
        
        $message .= "Event Data:\n";
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $message .= "- $key: " . wp_json_encode($value) . "\n";
            } else {
                $message .= "- $key: $value\n";
            }
        }
        
        $message .= "\nPlease check the security logs for more details.\n";
        $message .= admin_url('admin.php?page=vortex-security-logs');
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get security log entries
     * 
     * @param array $args Query arguments
     * @return array Array of log entries
     */
    public function get_security_logs($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'log_type' => '',
            'severity' => '',
            'user_id' => 0,
            'from_date' => '',
            'to_date' => '',
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'request_time',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $values = array();
        
        if (!empty($args['log_type'])) {
            $where[] = 'log_type = %s';
            $values[] = $args['log_type'];
        }
        
        if (!empty($args['severity'])) {
            $where[] = 'severity = %s';
            $values[] = $args['severity'];
        }
        
        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = $args['user_id'];
        }
        
        if (!empty($args['from_date'])) {
            $where[] = 'request_time >= %s';
            $values[] = $args['from_date'];
        }
        
        if (!empty($args['to_date'])) {
            $where[] = 'request_time <= %s';
            $values[] = $args['to_date'];
        }
        
        $query = "SELECT * FROM {$wpdb->prefix}vortex_security_logs WHERE " . 
                 implode(' AND ', $where) . 
                 " ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($query, $values), ARRAY_A);
    }

    public function process_request($action, $data) {
        if (!$this->verify_nonce($action)) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        if ($this->is_rate_limited($action)) {
            wp_send_json_error('Rate limit exceeded');
            return;
        }
        
        try {
            $result = $this->handle_action($action, $data);
            wp_send_json_success($result);
        } catch (Exception $e) {
            $this->log_error($action, $e);
            wp_send_json_error('An error occurred');
        }
    }
}

// Initialize Security Handler
$vortex_security_handler = VORTEX_Security_Handler::get_instance(); 