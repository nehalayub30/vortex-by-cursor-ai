        $equity_tokens = VORTEX_DAO_Tokens::get_instance();
        $founder_balance = $equity_tokens->get_equity_token_balance_by_address($this->config['founder_address']);
        
        $governance_threshold = $this->config['governance_threshold'];
        $founder_control_check = ($founder_balance > $governance_threshold);
        
        $security_checks[] = [
            'name' => __('Founder Control', 'vortex'),
            'description' => __('Verifies founder maintains controlling stake in governance', 'vortex'),
            'status' => $founder_control_check ? 'passed' : 'failed',
            'details' => sprintf(
                __('Founder balance: %s tokens, Governance threshold: %s tokens', 'vortex'),
                number_format($founder_balance),
                number_format($governance_threshold)
            )
        ];
        
        if (!$founder_control_check) {
            $critical_issues[] = __('Founder does not have controlling stake in governance', 'vortex');
        }
        
        // 2. Check for suspicious activities in last 24 hours
        $suspicious_activity_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_dao_security_logs 
            WHERE event_type IN ('invalid_signature', 'wallet_mismatch', 'founder_action_blocked', 'transfer_blocked', 'signature_missing')
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        
        $suspicious_activity_check = ($suspicious_activity_count < 5);
        
        $security_checks[] = [
            'name' => __('Recent Suspicious Activity', 'vortex'),
            'description' => __('Checks for suspicious activities in the last 24 hours', 'vortex'),
            'status' => $suspicious_activity_check ? 'passed' : 'warning',
            'details' => sprintf(
                __('%d suspicious activities detected in last 24 hours', 'vortex'),
                $suspicious_activity_count
            )
        ];
        
        if (!$suspicious_activity_check) {
            $critical_issues[] = sprintf(
                __('%d suspicious activities detected in last 24 hours', 'vortex'),
                $suspicious_activity_count
            );
        }
        
        // 3. Verify database tables exist and have proper structure
        $required_tables = [
            $wpdb->prefix . 'vortex_dao_investors',
            $wpdb->prefix . 'vortex_dao_tokens',
            $wpdb->prefix . 'vortex_dao_proposals',
            $wpdb->prefix . 'vortex_dao_votes',
            $wpdb->prefix . 'vortex_dao_distributions',
            $wpdb->prefix . 'vortex_dao_claims',
            $wpdb->prefix . 'vortex_dao_security_logs'
        ];
        
        $missing_tables = [];
        
        foreach ($required_tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") != $table) {
                $missing_tables[] = $table;
            }
        }
        
        $tables_check = empty($missing_tables);
        
        $security_checks[] = [
            'name' => __('Database Structure', 'vortex'),
            'description' => __('Verifies all required database tables exist', 'vortex'),
            'status' => $tables_check ? 'passed' : 'failed',
            'details' => $tables_check 
                ? __('All required database tables exist', 'vortex')
                : sprintf(__('Missing tables: %s', 'vortex'), implode(', ', $missing_tables))
        ];
        
        if (!$tables_check) {
            $critical_issues[] = sprintf(__('Missing database tables: %s', 'vortex'), implode(', ', $missing_tables));
        }
        
        // 4. Verify vesting rules are enforced
        $vesting_violations = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_dao_security_logs 
            WHERE event_type IN ('vesting_violation', 'founder_vesting_violation')
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        $vesting_check = ($vesting_violations == 0);
        
        $security_checks[] = [
            'name' => __('Vesting Enforcement', 'vortex'),
            'description' => __('Verifies vesting rules are being enforced', 'vortex'),
            'status' => $vesting_check ? 'passed' : 'warning',
            'details' => $vesting_check 
                ? __('No vesting violations detected', 'vortex')
                : sprintf(__('%d vesting violations detected in last 7 days', 'vortex'), $vesting_violations)
        ];
        
        if (!$vesting_check) {
            $critical_issues[] = sprintf(__('%d vesting violations detected in last 7 days', 'vortex'), $vesting_violations);
        }
        
        // 5. Verify founder veto is enabled
        $veto_check = $this->config['founder_veto_enabled'];
        
        $security_checks[] = [
            'name' => __('Founder Veto', 'vortex'),
            'description' => __('Verifies founder veto power is enabled', 'vortex'),
            'status' => $veto_check ? 'passed' : 'warning',
            'details' => $veto_check 
                ? __('Founder veto power is enabled', 'vortex')
                : __('Founder veto power is disabled', 'vortex')
        ];
        
        // 6. Verify anti-dilution protection is enabled
        $dilution_check = $this->config['anti_dilution_protection'];
        
        $security_checks[] = [
            'name' => __('Anti-Dilution Protection', 'vortex'),
            'description' => __('Verifies investor anti-dilution protection is enabled', 'vortex'),
            'status' => $dilution_check ? 'passed' : 'warning',
            'details' => $dilution_check 
                ? __('Anti-dilution protection is enabled', 'vortex')
                : __('Anti-dilution protection is disabled', 'vortex')
        ];
        
        // Log the security verification
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_security_logs',
            [
                'event_type' => 'security_verification',
                'message' => sprintf(
                    'Security verification performed by user ID %d with %d issues found',
                    get_current_user_id(),
                    count($critical_issues)
                ),
                'data' => json_encode([
                    'checks' => $security_checks,
                    'critical_issues' => $critical_issues
                ]),
                'user_id' => get_current_user_id(),
                'ip_address' => $this->get_client_ip(),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );
        
        // Generate message based on results
        if (empty($critical_issues)) {
            $message = __('Security verification completed successfully. All checks passed!', 'vortex');
        } else {
            $message = sprintf(
                __('Security verification completed with %d issues found. Please review the details.', 'vortex'),
                count($critical_issues)
            );
        }
        
        wp_send_json_success([
            'message' => $message,
            'checks' => $security_checks,
            'critical_issues' => $critical_issues,
            'status' => empty($critical_issues) ? 'secure' : 'issues'
        ]);
    }
    
    /**
     * Get client IP address.
     *
     * @return string Client IP address.
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
}

// Initialize the class
VORTEX_DAO_Security_AJAX::get_instance(); 