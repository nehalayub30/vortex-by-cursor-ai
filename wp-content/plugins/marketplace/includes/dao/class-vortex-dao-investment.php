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
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_investor_application')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to apply.', 'vortex')]);
        }
        
        $user_id = get_current_user_id();
        
        // Check if already an investor
        $existing = $this->get_investor_data($user_id);
        if ($existing) {
            wp_send_json_error(['message' => __('You are already registered as an investor.', 'vortex')]);
        }
        
        // Get application details
        $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
        $investment_amount = isset($_POST['investment_amount']) ? floatval($_POST['investment_amount']) : 0;
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $accredited = isset($_POST['accredited']) && $_POST['accredited'] === 'yes';
        
        // Validate data
        if (empty($wallet_address) || $investment_amount < $this->config['min_investment']) {
            wp_send_json_error([
                'message' => sprintf(
                    __('Invalid application details. Investment amount must be at least %s.', 'vortex'),
                    number_format($this->config['min_investment'], 2)
                )
            ]);
        }
        
        // Prepare notes from application
        $notes = sprintf(
            __("Investment Application\nName: %s %s\nEmail: %s\nPhone: %s\nAccredited Investor: %s\nApplication Date: %s", 'vortex'),
            $first_name,
            $last_name,
            $email,
            $phone,
            $accredited ? 'Yes' : 'No',
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'))
        );
        
        // Add investor with pending KYC status
        $investor_id = $this->add_investor(
            $user_id,
            $wallet_address,
            $investment_amount,
            $this->config['token_price'],
            730, // 2-year vesting by default
            $notes
        );
        
        if (!$investor_id) {
            wp_send_json_error(['message' => __('Failed to submit application. Please try again.', 'vortex')]);
        }
        
        // Send notification email to admin
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('[%s] New Investor Application', 'vortex'), get_bloginfo('name'));
        $message = sprintf(
            __("A new investor application has been submitted.\n\nName: %s %s\nEmail: %s\nPhone: %s\nWallet: %s\nInvestment Amount: %s\nAccredited Investor: %s\n\nPlease review this application in the WordPress admin.", 'vortex'),
            $first_name,
            $last_name,
            $email,
            $phone,
            $wallet_address,
            number_format($investment_amount, 2),
            $accredited ? 'Yes' : 'No'
        );
        
        wp_mail($admin_email, $subject, $message);
        
        // Update user meta with application data
        update_user_meta($user_id, 'vortex_wallet_address', $wallet_address);
        update_user_meta($user_id, 'vortex_investor_application_date', current_time('mysql'));
        
        // Update user's first/last name if not set
        $user = get_userdata($user_id);
        if (empty($user->first_name) && !empty($first_name)) {
            update_user_meta($user_id, 'first_name', $first_name);
        }
        if (empty($user->last_name) && !empty($last_name)) {
            update_user_meta($user_id, 'last_name', $last_name);
        }
        
        wp_send_json_success([
            'message' => __('Your investment application has been submitted successfully. We will review your application and contact you soon.', 'vortex'),
            'investor_id' => $investor_id,
        ]);
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