    /**
     * AJAX: Update investor.
     */
    public function ajax_update_investor() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_update_investor')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'vortex')]);
        }
        
        // Get investor details
        $investor_id = isset($_POST['investor_id']) ? intval($_POST['investor_id']) : 0;
        
        if ($investor_id <= 0) {
            wp_send_json_error(['message' => __('Invalid investor ID.', 'vortex')]);
        }
        
        // Collect update data
        $update_data = [];
        
        if (isset($_POST['wallet_address'])) {
            $update_data['wallet_address'] = sanitize_text_field($_POST['wallet_address']);
        }
        
        if (isset($_POST['investment_amount'])) {
            $update_data['investment_amount'] = floatval($_POST['investment_amount']);
        }
        
        if (isset($_POST['token_price'])) {
            $update_data['token_price'] = floatval($_POST['token_price']);
        }
        
        if (isset($_POST['vesting_period_days'])) {
            $update_data['vesting_period_days'] = intval($_POST['vesting_period_days']);
        }
        
        if (isset($_POST['kyc_status'])) {
            $update_data['kyc_status'] = sanitize_text_field($_POST['kyc_status']);
        }
        
        if (isset($_POST['notes'])) {
            $update_data['notes'] = sanitize_textarea_field($_POST['notes']);
        }
        
        if (empty($update_data)) {
            wp_send_json_error(['message' => __('No data provided to update.', 'vortex')]);
        }
        
        // Update investor
        $success = $this->update_investor($investor_id, $update_data);
        
        if (!$success) {
            wp_send_json_error(['message' => __('Failed to update investor.', 'vortex')]);
        }
        
        wp_send_json_success([
            'message' => __('Investor updated successfully!', 'vortex'),
            'investor_id' => $investor_id,
        ]);
    }
    
    /**
     * AJAX: Distribute dividends.
     */
    public function ajax_distribute_dividends() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_distribute_dividends')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'vortex')]);
        }
        
        // Get distribution details
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $distribution_type = isset($_POST['distribution_type']) ? sanitize_text_field($_POST['distribution_type']) : 'dividend';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        if ($amount <= 0) {
            wp_send_json_error(['message' => __('Invalid distribution amount.', 'vortex')]);
        }
        
        // Distribute dividends
        $distribution_id = $this->distribute_dividends($amount, $distribution_type, $notes);
        
        if (!$distribution_id) {
            wp_send_json_error(['message' => __('Failed to distribute dividends.', 'vortex')]);
        }
        
        wp_send_json_success([
            'message' => sprintf(__('Successfully created dividend distribution of %s.', 'vortex'), number_format($amount, 2)),
            'distribution_id' => $distribution_id,
        ]);
    }
    
    /**
     * AJAX: Investor application.
     */
    public function ajax_investor_application() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_investor_application')) {
            wp_send_json_error(array(
                'message' => __('Security verification failed. Please reload the page and try again.', 'vortex')
            ));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to submit an application.', 'vortex')
            ));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Check if user is already an investor
        if ($this->is_user_investor($user_id)) {
            wp_send_json_error(array(
                'message' => __('You are already registered as an investor.', 'vortex')
            ));
            return;
        }

        // Define required fields and their validation types
        $required_fields = array(
            'first_name' => 'text',
            'last_name' => 'text',
            'email' => 'email',
            'phone' => 'text',
            'wallet_address' => 'wallet',
            'investment_amount' => 'numeric',
            'terms_agreement' => 'boolean',
            'risk_acknowledgment' => 'boolean'
        );

        // Initialize sanitized data array and errors array
        $data = array();
        $errors = array();

        // Process and validate required fields
        foreach ($required_fields as $field => $type) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $errors[$field] = sprintf(__('%s is required.', 'vortex'), ucfirst(str_replace('_', ' ', $field)));
                continue;
            }

            // Sanitize and validate based on field type
            switch ($type) {
                case 'text':
                    $data[$field] = sanitize_text_field($_POST[$field]);
                    break;
                
                case 'email':
                    $email = sanitize_email($_POST[$field]);
                    if (!is_email($email)) {
                        $errors[$field] = __('Please enter a valid email address.', 'vortex');
                    } else {
                        $data[$field] = $email;
                    }
                    break;
                
                case 'numeric':
                    $value = filter_var($_POST[$field], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    if (!is_numeric($value) || $value <= 0) {
                        $errors[$field] = __('Please enter a valid positive number.', 'vortex');
                    } else {
                        // Verify minimum investment amount
                        $min_investment = get_option('vortex_dao_min_investment', 1000);
                        if ($value < $min_investment) {
                            $errors[$field] = sprintf(__('Minimum investment amount is $%s.', 'vortex'), number_format($min_investment, 2));
                        } else {
                            $data[$field] = (float) $value;
                        }
                    }
                    break;
                
                case 'wallet':
                    // Sanitize wallet address (alphanumeric with limited special chars)
                    $wallet = preg_replace('/[^a-zA-Z0-9]/', '', $_POST[$field]);
                    
                    // Verify wallet address format (this is a basic check, adapt for your blockchain)
                    if (strlen($wallet) < 32 || strlen($wallet) > 64) {
                        $errors[$field] = __('Please enter a valid wallet address.', 'vortex');
                    } else {
                        $data[$field] = sanitize_text_field($_POST[$field]); // Preserve original format
                    }
                    break;
                
                case 'boolean':
                    $data[$field] = ($_POST[$field] === 'yes') ? 'yes' : 'no';
                    if ($data[$field] !== 'yes') {
                        $errors[$field] = __('You must agree to this term to proceed.', 'vortex');
                    }
                    break;
            }
        }

        // Handle optional fields
        if (isset($_POST['accredited'])) {
            $data['accredited'] = ($_POST['accredited'] === 'yes') ? 'yes' : 'no';
        } else {
            $data['accredited'] = 'no';
        }

        // Verify GDPR consent if enabled
        $gdpr_enabled = get_option('vortex_enable_gdpr', 'yes');
        if ($gdpr_enabled === 'yes') {
            if (!isset($_POST['privacy_consent']) || $_POST['privacy_consent'] !== 'yes') {
                $errors['privacy_consent'] = __('You must consent to our privacy policy to proceed.', 'vortex');
            } else {
                $data['privacy_consent'] = 'yes';
                // Record timestamp of consent for GDPR compliance
                $data['privacy_consent_timestamp'] = current_time('mysql');
            }
        }

        // Return errors if validation failed
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => __('Please correct the errors in your application.', 'vortex'),
                'errors' => $errors
            ));
            return;
        }

        // Store IP anonymized for GDPR compliance
        $ip = $this->anonymize_ip($_SERVER['REMOTE_ADDR']);
        $data['application_ip'] = $ip;
        
        // Record application date
        $data['application_date'] = current_time('mysql');
        
        // Calculate tokens based on investment amount and token price
        $token_price = get_option('vortex_dao_token_price', 1);
        $data['tokens_allocated'] = floor($data['investment_amount'] / $token_price);
        
        // Set initial status
        $data['status'] = 'pending';
        
        // Store application in database
        $result = $this->db->insert(
            $this->db->prefix . 'vortex_investor_applications',
            array(
                'user_id' => $user_id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'wallet_address' => $data['wallet_address'],
                'investment_amount' => $data['investment_amount'],
                'tokens_allocated' => $data['tokens_allocated'],
                'accredited_investor' => $data['accredited'],
                'application_date' => $data['application_date'],
                'application_ip' => $data['application_ip'],
                'status' => $data['status']
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s')
        );
        
        if (!$result) {
            wp_send_json_error(array(
                'message' => __('Failed to submit application. Please try again or contact support.', 'vortex')
            ));
            return;
        }
        
        // Store the application ID
        $application_id = $this->db->insert_id;
        
        // Store additional data as user meta
        update_user_meta($user_id, 'vortex_investor_application_id', $application_id);
        update_user_meta($user_id, 'vortex_wallet_address', $data['wallet_address']);
        update_user_meta($user_id, 'vortex_investor_status', 'applied');
        
        // Store consent information if GDPR is enabled
        if ($gdpr_enabled === 'yes' && isset($data['privacy_consent_timestamp'])) {
            update_user_meta($user_id, 'vortex_privacy_consent', array(
                'consented' => true,
                'timestamp' => $data['privacy_consent_timestamp'],
                'ip' => $data['application_ip']
            ));
        }
        
        // Send notification email to admin
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('New Investor Application: %s %s', 'vortex'), $data['first_name'], $data['last_name']);
        
        // Prepare email content with escaping for security
        $message = sprintf(
            __('A new investor application has been submitted by %s %s (%s).

Investment Amount: $%s
Tokens to be Allocated: %s
Wallet Address: %s
Phone: %s
Accredited Investor: %s

Please review this application in the admin dashboard.', 'vortex'),
            esc_html($data['first_name']),
            esc_html($data['last_name']),
            esc_html($data['email']),
            number_format($data['investment_amount'], 2),
            number_format($data['tokens_allocated']),
            esc_html($data['wallet_address']),
            esc_html($data['phone']),
            $data['accredited'] === 'yes' ? __('Yes', 'vortex') : __('No', 'vortex')
        );
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($admin_email, $subject, nl2br($message), $headers);
        
        // Log the event
        $this->log_sensitive_action(
            'investor_application_submitted',
            sprintf('User %d submitted investor application #%d', $user_id, $application_id),
            $user_id
        );
        
        // Return success response with safe data
        wp_send_json_success(array(
            'message' => __('Your investor application has been submitted successfully. We will review your application and contact you soon.', 'vortex'),
            'application_id' => $application_id
        ));
    }
    
    /**
     * Anonymize IP address for GDPR compliance
     * 
     * @param string $ip The IP address to anonymize
     * @return string The anonymized IP address
     */
    private function anonymize_ip($ip) {
        if (empty($ip)) return '';
        
        // IPv4 address
        if (strpos($ip, '.') !== false) {
            // Replace last octet with 0
            return preg_replace('/\.\d+$/', '.0', $ip);
        }
        
        // IPv6 address
        if (strpos($ip, ':') !== false) {
            // Keep first 3 blocks, replace rest with zeros
            $ipv6_parts = explode(':', $ip);
            if (count($ipv6_parts) > 3) {
                return $ipv6_parts[0] . ':' . $ipv6_parts[1] . ':' . $ipv6_parts[2] . ':0:0:0:0:0';
            }
        }
        
        return $ip;
    }
    
    /**
     * Log sensitive actions securely
     * 
     * @param string $action The action being performed
     * @param string $description Description of the action
     * @param int $user_id The user ID performing the action
     */
    private function log_sensitive_action($action, $description, $user_id = 0) {
        if (empty($action)) return;
        
        $log_data = array(
            'action' => sanitize_text_field($action),
            'description' => sanitize_text_field($description),
            'user_id' => absint($user_id),
            'ip' => $this->anonymize_ip($_SERVER['REMOTE_ADDR']),
            'date' => current_time('mysql')
        );
        
        // Insert into secure audit log
        $this->db->insert(
            $this->db->prefix . 'vortex_security_log',
            $log_data,
            array('%s', '%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * Get all investors.
     *
     * @return array Array of investors.
     */
    public function get_all_investors() {
        global $wpdb;
        
        $investors = $wpdb->get_results(
            "SELECT i.*, u.display_name, u.user_email 
            FROM {$wpdb->prefix}vortex_dao_investors i
            JOIN {$wpdb->users} u ON i.user_id = u.ID
            ORDER BY i.investment_amount DESC"
        );
        
        return $investors;
    }
    
    /**
     * Get total investment amount.
     *
     * @return float Total investment amount.
     */
    public function get_total_investment() {
        global $wpdb;
        
        $total = $wpdb->get_var(
            "SELECT SUM(investment_amount) FROM {$wpdb->prefix}vortex_dao_investors"
        );
        
        return $total ? floatval($total) : 0;
    }
    
    /**
     * Get total tokens allocated to investors.
     *
     * @return float Total tokens allocated.
     */
    public function get_total_tokens_allocated() {
        global $wpdb;
        
        $total = $wpdb->get_var(
            "SELECT SUM(token_amount) FROM {$wpdb->prefix}vortex_dao_investors"
        );
        
        return $total ? floatval($total) : 0;
    }
} 