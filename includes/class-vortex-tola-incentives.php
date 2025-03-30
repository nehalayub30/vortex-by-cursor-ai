    /**
     * Log reward calculation
     * 
     * @param int $user_id User ID
     * @param string $action_type Action type
     * @param float $base_amount Base amount
     * @param float $final_reward Final reward
     * @param array $factors Calculation factors
     */
    private function log_reward_calculation($user_id, $action_type, $base_amount, $final_reward, $factors) {
        // Only log in debug mode
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        error_log(sprintf(
            'TOLA Reward Calculation - User: %d, Action: %s, Base: %.2f, Final: %.2f, Factors: %s',
            $user_id,
            $action_type,
            $base_amount,
            $final_reward,
            json_encode($factors)
        ));
    }
    
    /**
     * Log reward claim
     * 
     * @param int $user_id User ID
     * @param float $amount Claim amount
     * @param string $transaction Transaction hash
     */
    private function log_reward_claim($user_id, $amount, $transaction) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_tola_claims';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'amount' => $amount,
                'transaction_hash' => $transaction,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%f', '%s', '%s')
        );
    }
    
    /**
     * Log reputation update
     * 
     * @param int $user_id User ID
     * @param int $points_change Points change
     * @param int $new_total New total
     */
    private function log_reputation_update($user_id, $points_change, $new_total) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_reputation_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'points_change' => $points_change,
                'new_total' => $new_total,
                'reason' => 'activity_reward',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s', '%s')
        );
    }
    
    /**
     * Log tier advancement
     * 
     * @param int $user_id User ID
     * @param string $old_tier Old tier
     * @param string $new_tier New tier
     */
    private function log_tier_advancement($user_id, $old_tier, $new_tier) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_reputation_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'points_change' => 0,
                'new_total' => $this->get_neural_reputation_score($user_id),
                'reason' => sprintf('tier_advancement:%s:%s', $old_tier, $new_tier),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s', '%s')
        );
    }
    
    /**
     * Update reward statistics
     * 
     * @param float $amount Claimed amount
     */
    private function update_reward_statistics($amount) {
        // Update total claimed rewards
        $total_claimed = get_option('vortex_tola_total_claimed', 0);
        $new_total = floatval($total_claimed) + $amount;
        update_option('vortex_tola_total_claimed', $new_total);
        
        // Update daily claimed rewards
        $today = date('Y-m-d');
        $daily_stats = get_option('vortex_tola_daily_claims', array());
        
        if (!isset($daily_stats[$today])) {
            $daily_stats[$today] = 0;
        }
        
        $daily_stats[$today] += $amount;
        
        // Keep only last 30 days
        if (count($daily_stats) > 30) {
            $daily_stats = array_slice($daily_stats, -30, 30, true);
        }
        
        update_option('vortex_tola_daily_claims', $daily_stats);
    }
    
    /**
     * Log staking transaction
     * 
     * @param int $user_id User ID
     * @param float $amount Staked amount
     * @param int $lock_period Lock period in days
     * @param string $transaction Transaction hash
     */
    private function log_staking_transaction($user_id, $amount, $lock_period, $transaction) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_tola_staking';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'amount' => $amount,
                'lock_period' => $lock_period,
                'transaction_hash' => $transaction,
                'action_type' => 'stake',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%f', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Log unstaking transaction
     * 
     * @param int $user_id User ID
     * @param float $amount Unstaked amount
     * @param string $transaction Transaction hash
     */
    private function log_unstaking_transaction($user_id, $amount, $transaction) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_tola_staking';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'amount' => $amount,
                'lock_period' => 0,
                'transaction_hash' => $transaction,
                'action_type' => 'unstake',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%f', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get action frequency for user
     * 
     * @param int $user_id User ID
     * @param string $action_type Action type
     * @return int Action frequency in last 7 days
     */
    private function get_action_frequency($user_id, $action_type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_tola_activities';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} 
            WHERE user_id = %d 
            AND activity_type = %s 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            $user_id, $action_type
        ));
        
        return intval($count);
    }
    
    /**
     * Get quality modifier for user action
     * 
     * @param int $user_id User ID
     * @param string $action_type Action type
     * @return float Quality modifier (0.5 to 1.5)
     */
    private function get_quality_modifier($user_id, $action_type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_tola_activities';
        
        // For content creation, check average content quality
        if ($action_type === 'creation') {
            // Get recent content IDs
            $activity_data = $wpdb->get_col($wpdb->prepare(
                "SELECT activity_data FROM {$table_name} 
                WHERE user_id = %d 
                AND activity_type = 'creation' 
                ORDER BY created_at DESC 
                LIMIT 5",
                $user_id
            ));
            
            if (empty($activity_data)) {
                return 1.0; // Default modifier
            }
            
            $quality_scores = array();
            
            foreach ($activity_data as $data) {
                $data = maybe_unserialize($data);
                
                if (isset($data['content_id'])) {
                    $content = get_post($data['content_id']);
                    
                    if ($content) {
                        $factors = $this->assess_content_quality($content);
                        $quality_scores[] = array_sum($factors) / count($factors);
                    }
                }
            }
            
            if (empty($quality_scores)) {
                return 1.0; // Default modifier
            }
            
            // Average quality score, scaled to 0.5-1.5 range
            $avg_quality = array_sum($quality_scores) / count($quality_scores);
            return 0.5 + $avg_quality;
        }
        
        // For feedback, check average feedback quality
        if ($action_type === 'feedback') {
            // Get recent feedback data
            $activity_data = $wpdb->get_col($wpdb->prepare(
                "SELECT activity_data FROM {$table_name} 
                WHERE user_id = %d 
                AND activity_type = 'feedback' 
                ORDER BY created_at DESC 
                LIMIT 5",
                $user_id
            ));
            
            if (empty($activity_data)) {
                return 1.0; // Default modifier
            }
            
            $quality_scores = array();
            
            foreach ($activity_data as $data) {
                $data = maybe_unserialize($data);
                
                // Score based on feedback length and having a rating
                $score = 0.5; // Base score
                
                if (isset($data['rating']) && $data['rating'] > 0) {
                    $score += 0.2; // Having a rating adds 0.2
                }
                
                if (isset($data['feedback_text'])) {
                    $length = strlen($data['feedback_text']);
                    $score += min(0.3, $length / 200); // Up to 0.3 for length
                }
                
                $quality_scores[] = $score;
            }
            
            if (empty($quality_scores)) {
                return 1.0; // Default modifier
            }
            
            // Average quality score, scaled to 0.5-1.5 range
            $avg_quality = array_sum($quality_scores) / count($quality_scores);
            return 0.5 + $avg_quality;
        }
        
        // Default modifier for other action types
        return 1.0;
    }
    
    /**
     * Get user wallet from register shortcode
     */
    public function register_wallet_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'theme' => 'light',
            'redirect' => '',
        ), $atts);
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return '<div class="tola-wallet-register tola-theme-' . esc_attr($atts['theme']) . '">
                <p>' . __('Please log in to register your wallet', 'vortex-ai-marketplace') . '</p>
            </div>';
        }
        
        // Enqueue necessary assets
        wp_enqueue_style('tola-widgets-css');
        wp_enqueue_script('tola-wallet-js');
        wp_enqueue_script('web3-js');
        
        // Generate nonce
        $nonce = wp_create_nonce('vortex_wallet_nonce');
        
        // Get current wallet
        $current_wallet = $this->get_user_wallet_address($user_id);
        
        ob_start();
        
        echo '<div class="tola-wallet-register tola-theme-' . esc_attr($atts['theme']) . '" id="tola-wallet-' . uniqid() . '">';
        echo '<h3>' . __('Connect Your Wallet', 'vortex-ai-marketplace') . '</h3>';
        
        if ($current_wallet) {
            echo '<div class="tola-current-wallet">';
            echo '<p>' . __('Currently connected wallet:', 'vortex-ai-marketplace') . '</p>';
            echo '<div class="tola-wallet-address">' . $this->format_wallet_address($current_wallet) . '</div>';
            echo '<button class="tola-disconnect-wallet" data-nonce="' . esc_attr($nonce) . '">' . __('Disconnect Wallet', 'vortex-ai-marketplace') . '</button>';
            echo '</div>';
        } else {
            echo '<p>' . __('Connect your Ethereum wallet to receive TOLA rewards and participate in the marketplace.', 'vortex-ai-marketplace') . '</p>';
            
            echo '<div class="tola-wallet-buttons">';
            echo '<button class="tola-connect-metamask" data-nonce="' . esc_attr($nonce) . '">' . __('Connect with MetaMask', 'vortex-ai-marketplace') . '</button>';
            echo '<button class="tola-connect-walletconnect" data-nonce="' . esc_attr($nonce) . '">' . __('Connect with WalletConnect', 'vortex-ai-marketplace') . '</button>';
            echo '</div>';
            
            echo '<div class="tola-wallet-message"></div>';
        }
        
        echo '</div>'; // Close container
        
        // Add redirect data
        if (!empty($atts['redirect'])) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    if (typeof TolaWallet !== "undefined") {
                        TolaWallet.setRedirect("' . esc_url($atts['redirect']) . '");
                    }
                });
            </script>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Install the incentive system
     */
    public function install() {
        // Initialize reputation tiers
        $this->init_neural_reputation_system();
        
        // Create database tables
        $this->init_tables();
        
        // Set default options
        add_option('vortex_tola_transaction_reward_percentage', 2.0);
        add_option('vortex_tola_artist_reward_percentage', 1.0);
        add_option('vortex_tola_agent_interaction_reward', 1.0);
        add_option('vortex_enable_referrals', true);
        
        // Create TOLA token info
        add_option('vortex_tola_token_name', 'Thorius Artificial Intelligence Token');
        add_option('vortex_tola_token_symbol', 'TOLA');
        add_option('vortex_tola_token_decimals', 18);
        add_option('vortex_tola_network', 'ethereum');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Uninstall the incentive system
     */
    public function uninstall() {
        // Keep data by default, only remove if specified
        if (get_option('vortex_tola_delete_data_on_uninstall', false)) {
            global $wpdb;
            
            // Drop tables
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vortex_tola_activities");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vortex_reputation_logs");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vortex_tola_claims");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vortex_tola_staking");
            
            // Delete options
            delete_option('vortex_tola_transaction_reward_percentage');
            delete_option('vortex_tola_artist_reward_percentage');
            delete_option('vortex_tola_agent_interaction_reward');
            delete_option('vortex_enable_referrals');
            delete_option('vortex_tola_token_name');
            delete_option('vortex_tola_token_symbol');
            delete_option('vortex_tola_token_decimals');
            delete_option('vortex_tola_network');
            delete_option('vortex_tola_signing_key');
            delete_option('vortex_tola_token_contract');
            delete_option('vortex_tola_rewards_pool_contract');
            delete_option('vortex_tola_marketplace_contract');
            delete_option('vortex_tola_total_claimed');
            delete_option('vortex_tola_daily_claims');
            delete_option('vortex_tola_delete_data_on_uninstall');
        }
    }
} 