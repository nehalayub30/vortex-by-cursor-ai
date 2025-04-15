                'notes' => $notes,
                'created_at' => current_time('mysql'),
            ],
            [
                '%s', '%f', '%s', '%s', '%s', '%s', '%s'
            ]
        );
        
        if ($result) {
            // Return the distribution ID
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Create user claim for a distribution.
     *
     * @param int $user_id User ID.
     * @param int $distribution_id Distribution ID.
     * @param float $amount Amount to claim.
     * @return int|bool Claim ID on success, false on failure.
     */
    public function create_user_claim($user_id, $distribution_id, $amount) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_claims',
            [
                'user_id' => $user_id,
                'distribution_id' => $distribution_id,
                'amount' => $amount,
                'token_type' => 'TOLA',
                'claimed' => 0,
                'created_at' => current_time('mysql'),
            ],
            [
                '%d', '%d', '%f', '%s', '%d', '%s'
            ]
        );
        
        if ($result) {
            // Return the claim ID
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Process token claims.
     *
     * @param int $claim_id Claim ID.
     * @return bool Whether the claim was processed successfully.
     */
    public function process_claim($claim_id) {
        global $wpdb;
        
        // Get claim data
        $claim = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_dao_claims WHERE id = %d",
                $claim_id
            )
        );
        
        if (!$claim || $claim->claimed) {
            return false;
        }
        
        // Here you would integrate with blockchain to transfer tokens
        // For now, we'll just mark it as claimed
        
        $result = $wpdb->update(
            $wpdb->prefix . 'vortex_dao_claims',
            [
                'claimed' => 1,
                'claim_date' => current_time('mysql'),
                'blockchain_tx' => '0x' . md5('claim_tx_' . time()), // Simulated transaction
            ],
            ['id' => $claim_id],
            ['%d', '%s', '%s'],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * AJAX: Token transfer.
     */
    public function ajax_token_transfer() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_token_transfer')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'vortex')]);
        }
        
        // Get transfer details
        $recipient = isset($_POST['recipient']) ? sanitize_text_field($_POST['recipient']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        
        // Validate data
        if (empty($recipient) || $amount <= 0) {
            wp_send_json_error(['message' => __('Invalid transfer details.', 'vortex')]);
        }
        
        // Create a distribution for this transfer
        $distribution_id = $this->create_distribution(
            'manual_transfer',
            $amount,
            sprintf(__('Manual transfer to %s', 'vortex'), $recipient)
        );
        
        if (!$distribution_id) {
            wp_send_json_error(['message' => __('Failed to create distribution record.', 'vortex')]);
        }
        
        // Get user ID from wallet address or create user if needed
        $user_id = $this->get_user_id_by_wallet($recipient);
        
        if (!$user_id) {
            wp_send_json_error(['message' => __('Recipient not found.', 'vortex')]);
        }
        
        // Create claim for the user
        $claim_id = $this->create_user_claim($user_id, $distribution_id, $amount);
        
        if (!$claim_id) {
            wp_send_json_error(['message' => __('Failed to create claim record.', 'vortex')]);
        }
        
        // Process the claim immediately
        $success = $this->process_claim($claim_id);
        
        if (!$success) {
            wp_send_json_error(['message' => __('Failed to process token transfer.', 'vortex')]);
        }
        
        // Update distribution status
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'vortex_dao_distributions',
            [
                'status' => 'completed',
                'blockchain_tx' => '0x' . md5('distribution_tx_' . time()), // Simulated transaction
            ],
            ['id' => $distribution_id],
            ['%s', '%s'],
            ['%d']
        );
        
        wp_send_json_success([
            'message' => sprintf(__('%s TOLA tokens transferred successfully to %s', 'vortex'), $amount, $recipient),
            'distribution_id' => $distribution_id,
            'claim_id' => $claim_id,
        ]);
    }
    
    /**
     * AJAX: Token distribution.
     */
    public function ajax_token_distribution() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_token_distribution')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'vortex')]);
        }
        
        // Get distribution details
        $distribution_type = isset($_POST['distribution_type']) ? sanitize_text_field($_POST['distribution_type']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $recipients = isset($_POST['recipients']) ? $_POST['recipients'] : [];
        
        // Validate data
        if (empty($distribution_type) || $amount <= 0 || empty($recipients)) {
            wp_send_json_error(['message' => __('Invalid distribution details.', 'vortex')]);
        }
        
        // Create a distribution record
        $distribution_id = $this->create_distribution(
            $distribution_type,
            $amount,
            sprintf(__('Distribution of %s TOLA tokens to %d recipients', 'vortex'), $amount, count($recipients))
        );
        
        if (!$distribution_id) {
            wp_send_json_error(['message' => __('Failed to create distribution record.', 'vortex')]);
        }
        
        // Calculate amount per recipient
        $per_recipient = $amount / count($recipients);
        
        // Create claims for each recipient
        $claims = [];
        $successful_claims = 0;
        
        foreach ($recipients as $recipient) {
            $user_id = intval($recipient);
            
            if ($user_id > 0) {
                $claim_id = $this->create_user_claim($user_id, $distribution_id, $per_recipient);
                
                if ($claim_id) {
                    $claims[] = [
                        'user_id' => $user_id,
                        'claim_id' => $claim_id,
                        'amount' => $per_recipient,
                    ];
                    
                    $successful_claims++;
                }
            }
        }
        
        if ($successful_claims === 0) {
            wp_send_json_error(['message' => __('Failed to create claims for recipients.', 'vortex')]);
        }
        
        // Update distribution status
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'vortex_dao_distributions',
            [
                'status' => 'pending',
                'snapshot_id' => 'batch_' . time(), // Simulated snapshot ID
            ],
            ['id' => $distribution_id],
            ['%s', '%s'],
            ['%d']
        );
        
        wp_send_json_success([
            'message' => sprintf(
                __('Distribution created successfully. %d out of %d claims created.', 'vortex'),
                $successful_claims,
                count($recipients)
            ),
            'distribution_id' => $distribution_id,
            'claims' => $claims,
        ]);
    }
    
    /**
     * Get user ID by wallet address.
     *
     * @param string $wallet_address Wallet address.
     * @return int|bool User ID or false if not found.
     */
    private function get_user_id_by_wallet($wallet_address) {
        global $wpdb;
        
        // Try exact match first
        $user_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'vortex_wallet_address' AND meta_value = %s",
                $wallet_address
            )
        );
        
        if ($user_id) {
            return intval($user_id);
        }
        
        // Try finding in investors table
        $investor = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}vortex_dao_investors WHERE wallet_address = %s",
                $wallet_address
            )
        );
        
        if ($investor) {
            return intval($investor->user_id);
        }
        
        return false;
    }
    
    /**
     * Transfer tokens on Solana blockchain.
     *
     * @param string $from_address Sender address.
     * @param string $to_address Recipient address.
     * @param int $amount Amount to transfer.
     * @return bool Whether the transfer was successful.
     */
    public function transfer_tokens($from_address, $to_address, $amount) {
        // Get TOLA token address
        $token_address = $this->config['token_address'];
        
        if (empty($token_address)) {
            error_log('TOLA token address not configured');
            return false;
        }
        
        try {
            // Initialize Solana connection
            $solana_api = new VORTEX_Solana_API();
            
            // Execute token transfer
            $transaction_hash = $solana_api->transfer_spl_token(
                $token_address,
                $from_address,
                $to_address,
                $amount
            );
            
            if ($transaction_hash) {
                // Update local records
                $this->update_token_balance($from_address, -$amount);
                $this->update_token_balance($to_address, $amount);
                
                // Log the transaction
                global $wpdb;
                $wpdb->insert(
                    $wpdb->prefix . 'vortex_dao_security_logs',
                    [
                        'event_type' => 'token_transfer',
                        'message' => sprintf(
                            'Transferred %d TOLA tokens from %s to %s',
                            $amount,
                            $from_address,
                            $to_address
                        ),
                        'data' => json_encode([
                            'token_address' => $token_address,
                            'from_address' => $from_address,
                            'to_address' => $to_address,
                            'amount' => $amount,
                            'transaction_hash' => $transaction_hash
                        ]),
                        'created_at' => current_time('mysql')
                    ],
                    ['%s', '%s', '%s', '%s']
                );
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Solana token transfer error: ' . $e->getMessage());
            return false;
        }
    }
} 