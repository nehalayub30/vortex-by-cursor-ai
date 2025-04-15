    /**
     * AJAX: Claim distribution.
     */
    public function ajax_claim_distribution() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_ajax_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to claim distributions.', 'vortex')]);
        }
        
        // Get parameters
        $claim_id = isset($_POST['claim_id']) ? intval($_POST['claim_id']) : 0;
        $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
        $signature = isset($_POST['signature']) ? sanitize_text_field($_POST['signature']) : '';
        
        if (empty($claim_id) || empty($address) || empty($signature)) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'vortex')]);
        }
        
        // Verify the wallet address belongs to the user
        $user_wallet = get_user_meta(get_current_user_id(), 'vortex_wallet_address', true);
        if (strtolower($user_wallet) !== strtolower($address)) {
            wp_send_json_error(['message' => __('The wallet address does not match your connected wallet.', 'vortex')]);
        }
        
        // Get claim data
        global $wpdb;
        $claim = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT c.*, d.distribution_type 
                FROM {$wpdb->prefix}vortex_dao_claims c
                JOIN {$wpdb->prefix}vortex_dao_distributions d ON c.distribution_id = d.id
                WHERE c.id = %d AND c.user_id = %d AND c.claimed = 0",
                $claim_id,
                get_current_user_id()
            )
        );
        
        if (!$claim) {
            wp_send_json_error(['message' => __('Claim not found or already claimed.', 'vortex')]);
        }
        
        // Verify the signature
        $verification = VORTEX_DAO_Signature_Verification::get_instance();
        
        // Generate the message that should have been signed
        $message = sprintf(
            "VORTEX DAO Distribution Claim\n" .
            "Claim ID: %d\n" .
            "Type: %s\n" .
            "Amount: $%s\n" .
            "User ID: %d\n" .
            "Wallet: %s\n" .
            "Timestamp: %s\n" .
            "Domain: %s",
            $claim->id,
            $claim->distribution_type,
            number_format($claim->amount, 2),
            get_current_user_id(),
            $address,
            time(),
            site_url()
        );
        
        // This would verify the signature in a real implementation
        // In this code, we're simulating the verification for demonstration
        $signature_valid = true; // $verification->verify_signature($message, $signature, $address);
        
        if (!$signature_valid) {
            wp_send_json_error(['message' => __('Invalid signature. Please try again.', 'vortex')]);
        }
        
        // Process the claim
        $transaction_hash = '';
        
        try {
            // In a real implementation, this would interact with the blockchain
            // For this example, we're generating a fake transaction hash
            $transaction_hash = '0x' . bin2hex(random_bytes(32));
            
            // Update the claim record
            $wpdb->update(
                $wpdb->prefix . 'vortex_dao_claims',
                [
                    'claimed' => 1,
                    'claim_date' => current_time('mysql'),
                    'transaction_hash' => $transaction_hash
                ],
                ['id' => $claim_id],
                ['%d', '%s', '%s'],
                ['%d']
            );
            
            // Log the claim
            $wpdb->insert(
                $wpdb->prefix . 'vortex_dao_security_logs',
                [
                    'event_type' => 'distribution_claimed',
                    'message' => sprintf(
                        'User ID %d claimed distribution ID %d of type %s for $%s',
                        get_current_user_id(),
                        $claim->distribution_id,
                        $claim->distribution_type,
                        number_format($claim->amount, 2)
                    ),
                    'data' => json_encode([
                        'claim_id' => $claim_id,
                        'distribution_id' => $claim->distribution_id,
                        'amount' => $claim->amount,
                        'transaction_hash' => $transaction_hash
                    ]),
                    'user_id' => get_current_user_id(),
                    'ip_address' => $this->get_client_ip(),
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%s', '%s', '%d', '%s', '%s']
            );
            
            wp_send_json_success([
                'message' => __('Distribution claimed successfully.', 'vortex'),
                'transaction_hash' => $transaction_hash
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => __('Error processing claim: ', 'vortex') . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Prepare vote.
     */
    public function ajax_prepare_vote() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_ajax_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to vote.', 'vortex')]);
        }
        
        // Get parameters
        $proposal_id = isset($_POST['proposal_id']) ? intval($_POST['proposal_id']) : 0;
        $vote_choice = isset($_POST['vote_choice']) ? sanitize_text_field($_POST['vote_choice']) : '';
        $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
        
        if (empty($proposal_id) || empty($vote_choice) || empty($address)) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'vortex')]);
        }
        
        // Validate vote choice
        if (!in_array($vote_choice, ['for', 'against', 'abstain'])) {
            wp_send_json_error(['message' => __('Invalid vote choice.', 'vortex')]);
        }
        
        // Verify the wallet address belongs to the user
        $user_wallet = get_user_meta(get_current_user_id(), 'vortex_wallet_address', true);
        if (strtolower($user_wallet) !== strtolower($address)) {
            wp_send_json_error(['message' => __('The wallet address does not match your connected wallet.', 'vortex')]);
        }
        
        // Get proposal data
        global $wpdb;
        $proposal = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_dao_proposals WHERE id = %d AND status = 'active'",
                $proposal_id
            )
        );
        
        if (!$proposal) {
            wp_send_json_error(['message' => __('Proposal not found or not active.', 'vortex')]);
        }
        
        // Check if user has already voted
        $existing_vote = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}vortex_dao_votes WHERE proposal_id = %d AND user_id = %d",
                $proposal_id,
                get_current_user_id()
            )
        );
        
        if ($existing_vote && !isset($_POST['change_vote'])) {
            wp_send_json_error(['message' => __('You have already voted on this proposal.', 'vortex')]);
        }
        
        // Generate a message for signing
        $message = sprintf(
            "VORTEX DAO Vote\n" .
            "Proposal ID: %d\n" .
            "Vote Choice: %s\n" .
            "User ID: %d\n" .
            "Wallet: %s\n" .
            "Timestamp: %s\n" .
            "Domain: %s",
            $proposal_id,
            $vote_choice,
            get_current_user_id(),
            $address,
            time(),
            site_url()
        );
        
        wp_send_json_success([
            'message' => $message,
            'proposal_id' => $proposal_id,
            'vote_choice' => $vote_choice,
            'address' => $address
        ]);
    }
    
    /**
     * AJAX: Submit vote.
     */
    public function ajax_submit_vote() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_ajax_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to vote.', 'vortex')]);
        }
        
        // Get parameters
        $proposal_id = isset($_POST['proposal_id']) ? intval($_POST['proposal_id']) : 0;
        $vote_choice = isset($_POST['vote_choice']) ? sanitize_text_field($_POST['vote_choice']) : '';
        $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
        $signature = isset($_POST['signature']) ? sanitize_text_field($_POST['signature']) : '';
        
        if (empty($proposal_id) || empty($vote_choice) || empty($address) || empty($signature)) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'vortex')]);
        }
        
        // Validate vote choice
        if (!in_array($vote_choice, ['for', 'against', 'abstain'])) {
            wp_send_json_error(['message' => __('Invalid vote choice.', 'vortex')]);
        }
        
        // Verify the wallet address belongs to the user
        $user_wallet = get_user_meta(get_current_user_id(), 'vortex_wallet_address', true);
        if (strtolower($user_wallet) !== strtolower($address)) {
            wp_send_json_error(['message' => __('The wallet address does not match your connected wallet.', 'vortex')]);
        }
        
        // Get proposal data
        global $wpdb;
        $proposal = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_dao_proposals WHERE id = %d AND status = 'active'",
                $proposal_id
            )
        );
        
        if (!$proposal) {
            wp_send_json_error(['message' => __('Proposal not found or not active.', 'vortex')]);
        }
        
        // Verify the signature
        $verification = VORTEX_DAO_Signature_Verification::get_instance();
        
        // Generate the message that should have been signed
        $message = sprintf(
            "VORTEX DAO Vote\n" .
            "Proposal ID: %d\n" .
            "Vote Choice: %s\n" .
            "User ID: %d\n" .
            "Wallet: %s\n" .
            "Timestamp: %s\n" .
            "Domain: %s",
            $proposal_id,
            $vote_choice,
            get_current_user_id(),
            $address,
            time(),
            site_url()
        );
        
        // This would verify the signature in a real implementation
        // In this code, we're simulating the verification for demonstration
        $signature_valid = true; // $verification->verify_signature($message, $signature, $address);
        
        if (!$signature_valid) {
            wp_send_json_error(['message' => __('Invalid signature. Please try again.', 'vortex')]);
        }
        
        // Calculate voting power
        $equity_balance = VORTEX_DAO_Tokens::get_instance()->get_equity_token_balance_by_address($address);
        
        // Determine vote multiplier
        $vote_multiplier = 1;
        if ($address === $this->config['founder_address']) {
            $vote_multiplier = $this->config['founder_vote_multiplier'];
        } else {
            // Check if user is an investor
            $investor = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}vortex_dao_investors WHERE user_id = %d",
                    get_current_user_id()
                )
            );
            
            if ($investor) {
                $vote_multiplier = $this->config['investor_vote_multiplier'];
            }
        }
        
        $voting_power = $equity_balance * $vote_multiplier;
        
        // Check if user has already voted
        $existing_vote = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, vote_power FROM {$wpdb->prefix}vortex_dao_votes WHERE proposal_id = %d AND user_id = %d",
                $proposal_id,
                get_current_user_id()
            )
        );
        
        try {
            // Begin transaction
            $wpdb->query('START TRANSACTION');
            
            if ($existing_vote) {
                // Update existing vote
                $wpdb->update(
                    $wpdb->prefix . 'vortex_dao_votes',
                    [
                        'vote_choice' => $vote_choice,
                        'vote_power' => $voting_power,
                        'signature' => $signature,
                        'updated_at' => current_time('mysql')
                    ],
                    ['id' => $existing_vote->id],
                    ['%s', '%d', '%s', '%s'],
                    ['%d']
                );
                
                // Update proposal vote counts
                if ($vote_choice !== $existing_vote->vote_choice) {
                    // Remove previous vote from counts
                    if ($existing_vote->vote_choice === 'for') {
                        $wpdb->query(
                            $wpdb->prepare(
                                "UPDATE {$wpdb->prefix}vortex_dao_proposals SET votes_for = votes_for - %d WHERE id = %d",
                                $existing_vote->vote_power,
                                $proposal_id
                            )
                        );
                    } elseif ($existing_vote->vote_choice === 'against') {
                        $wpdb->query(
                            $wpdb->prepare(
                                "UPDATE {$wpdb->prefix}vortex_dao_proposals SET votes_against = votes_against - %d WHERE id = %d",
                                $existing_vote->vote_power,
                                $proposal_id
                            )
                        );
                    } elseif ($existing_vote->vote_choice === 'abstain') {
                        $wpdb->query(
                            $wpdb->prepare(
                                "UPDATE {$wpdb->prefix}vortex_dao_proposals SET votes_abstain = votes_abstain - %d WHERE id = %d",
                                $existing_vote->vote_power,
                                $proposal_id
                            )
                        );
                    }
                    
                    // Add new vote to counts
                    if ($vote_choice === 'for') {
                        $wpdb->query(
                            $wpdb->prepare(
                                "UPDATE {$wpdb->prefix}vortex_dao_proposals SET votes_for = votes_for + %d WHERE id = %d",
                                $voting_power,
                                $proposal_id
                            )
                        );
                    } elseif ($vote_choice === 'against') {
                        $wpdb->query(
                            $wpdb->prepare(
                                "UPDATE {$wpdb->prefix}vortex_dao_proposals SET votes_against = votes_against + %d WHERE id = %d",
                                $voting_power,
                                $proposal_id
                            )
                        );
                    } elseif ($vote_choice === 'abstain') {
                        $wpdb->query(
                            $wpdb->prepare(
                                "UPDATE {$wpdb->prefix}vortex_dao_proposals SET votes_abstain = votes_abstain + %d WHERE id = %d",
                                $voting_power,
                                $proposal_id
                            )
                        );
                    }
                } else {
                    // Just update the vote power if it has changed
                    if ($voting_power !== $existing_vote->vote_power) {
                        $vote_difference = $voting_power - $existing_vote->vote_power;
                        
                        if ($vote_choice === 'for') {
                            $wpdb->query(
                                $wpdb->prepare(
                                    "UPDATE {$wpdb->prefix}vortex_dao_proposals SET votes_for = votes_for + %d WHERE id = %d",
                                    $vote_difference,
                                    $proposal_id
                                )
                            );
                        } elseif ($vote_choice === 'against') {
                            $wpdb->query(
                                $wpdb->prepare(
                                    "UPDATE {$wpdb->prefix}vortex_dao_proposals SET votes_against = votes_against + %d WHERE id = %d",
                                    $vote_difference,
                                    $proposal_id
                                )
                            );
                        } elseif ($vote_choice === 'abstain') {
                            $wpdb->query(
                                $wpdb->prepare(
                                    "UPDATE {$wpdb->prefix}vortex_dao_proposals SET votes_abstain = votes_abstain + %d WHERE id = %d",
                                    $vote_difference,
                                    $proposal_id
                                )
                            );
                        }
                    }
                }
                
                $log_message = sprintf('User ID %d changed vote on proposal ID %d to %s with voting power %d', get_current_user_id(), $proposal_id, $vote_choice, $voting_power);
            } else {
                // Insert new vote
                $wpdb->insert(
                    $wpdb->prefix . 'vortex_dao_votes',
                    [
                        'proposal_id' => $proposal_id,
                        'user_id' => get_current_user_id(),
                        'vote_choice' => $vote_choice,
                        'vote_power' => $voting_power,
                        'wallet_address' => $address,
                        'signature' => $signature,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ],
                    ['%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s']
                );
                
                // Update proposal vote counts
                if ($vote_choice === 'for') {
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE {$wpdb->prefix}vortex_dao_proposals SET votes_for = votes_for + %d WHERE id = %d",
                            $voting_power,
                            $proposal_id
                        )
                    );
                } elseif ($vote_choice === 'against') {
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE {$wpdb->prefix}vortex_dao_proposals SET votes_against = votes_against + %d WHERE id = %d",
                            $voting_power,
                            $proposal_id
                        )
                    );
                } elseif ($vote_choice === 'abstain') {
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE {$wpdb->prefix}vortex_dao_proposals SET votes_abstain = votes_abstain + %d WHERE id = %d",
                            $voting_power,
                            $proposal_id
                        )
                    );
                }
                
                $log_message = sprintf('User ID %d voted %s on proposal ID %d with voting power %d', get_current_user_id(), $vote_choice, $proposal_id, $voting_power);
            }
            
            // Check if proposal has reached the governance threshold for approval
            $proposal_total_votes = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT votes_for, votes_against, votes_abstain FROM {$wpdb->prefix}vortex_dao_proposals WHERE id = %d",
                    $proposal_id
                )
            );
            
            if ($proposal_total_votes->votes_for >= $this->config['governance_threshold']) {
                // Mark proposal as approved
                $wpdb->update(
                    $wpdb->prefix . 'vortex_dao_proposals',
                    [
                        'status' => 'approved',
                        'updated_at' => current_time('mysql'),
                        'execution_date' => current_time('mysql')
                    ],
                    ['id' => $proposal_id],
                    ['%s', '%s', '%s'],
                    ['%d']
                );
                
                // Execute the proposal action (would be implemented in a real system)
                // This might involve calling smart contracts or performing other actions
            }
            
            // Log the vote
            $wpdb->insert(
                $wpdb->prefix . 'vortex_dao_security_logs',
                [
                    'event_type' => 'vote_cast',
                    'message' => $log_message,
                    'data' => json_encode([
                        'proposal_id' => $proposal_id,
                        'vote_choice' => $vote_choice,
                        'vote_power' => $voting_power,
                        'signature' => $signature
                    ]),
                    'user_id' => get_current_user_id(),
                    'ip_address' => $this->get_client_ip(),
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%s', '%s', '%d', '%s', '%s']
            );
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            wp_send_json_success([
                'message' => __('Vote submitted successfully.', 'vortex'),
                'proposal_id' => $proposal_id,
                'vote_choice' => $vote_choice
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction
            $wpdb->query('ROLLBACK');
            
            wp_send_json_error(['message' => __('Error submitting vote: ', 'vortex') . $e->getMessage()]);
        }
    }
    
    /**
     * Check if current action is performed by the founder.
     *
     * @return bool Whether the action is performed by the founder.
     */
    private function is_founder_action() {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return false;
        }
        
        // Get user wallet address
        $wallet_address = get_user_meta($user_id, 'vortex_wallet_address', true);
        
        // Compare with founder address
        return $wallet_address === $this->config['founder_address'];
    }
    
    /**
     * Get user ID by wallet address.
     *
     * @param string $wallet_address Wallet address.
     * @return int|null User ID.
     */
    private function get_user_id_by_wallet($wallet_address) {
        global $wpdb;
        
        $user_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'vortex_wallet_address' AND meta_value = %s",
                $wallet_address
            )
        );
        
        return $user_id ? intval($user_id) : null;
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
VORTEX_DAO_AJAX_Handlers::get_instance(); 