                'user_id' => $user_id,
                'amount' => $amount,
                'reward_type' => $reward_type,
                'metadata' => !empty($metadata) ? json_encode($metadata) : null,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            )
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get user rewards
     */
    public function get_user_rewards($user_id) {
        global $wpdb;
        
        $rewards = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_dao_rewards 
            WHERE user_id = %d 
            ORDER BY created_at DESC",
            $user_id
        ));
        
        // Enhance rewards with formatted data
        foreach ($rewards as &$reward) {
            // Parse metadata
            $reward->metadata_parsed = json_decode($reward->metadata, true);
            
            // Format amount
            $reward->amount_formatted = number_format($reward->amount, 2);
            
            // Format created date
            $reward->created_at_formatted = date('M j, Y', strtotime($reward->created_at));
            
            // Format reward type
            $reward->type_label = $this->get_reward_type_label($reward->reward_type);
            
            // Add status label
            $reward->status_label = ucfirst($reward->status);
            
            // Add status badge class
            $reward->status_class = 'status-' . $reward->status;
            
            // Add human-readable description
            $reward->description = $this->get_reward_description($reward);
        }
        
        return $rewards;
    }
    
    /**
     * Get total pending rewards for user
     */
    public function get_total_pending_rewards($user_id) {
        global $wpdb;
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}vortex_dao_rewards 
            WHERE user_id = %d AND status = 'pending'",
            $user_id
        ));
        
        return (float) $total;
    }
    
    /**
     * Claim rewards
     */
    public function claim_rewards($user_id, $wallet_address, $reward_type = 'all') {
        global $wpdb;
        
        // Get pending rewards
        $where = "user_id = %d AND status = 'pending'";
        $params = array($user_id);
        
        if ($reward_type !== 'all') {
            $where .= " AND reward_type = %s";
            $params[] = $reward_type;
        }
        
        $pending_rewards = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_dao_rewards 
            WHERE $where",
            $params
        ));
        
        // Calculate total amount
        $total_amount = 0;
        foreach ($pending_rewards as $reward) {
            $total_amount += $reward->amount;
        }
        
        // Check if there are rewards to claim
        if (empty($pending_rewards) || $total_amount <= 0) {
            return array(
                'success' => false,
                'message' => 'No pending rewards available to claim.'
            );
        }
        
        // Transfer tokens from treasury to user's wallet
        $treasury_address = get_option('vortex_dao_treasury_address');
        
        $transfer_result = $this->solana_api->transfer_tokens(
            $treasury_address,
            $wallet_address,
            $total_amount,
            'rewards_claim_' . $user_id . '_' . time()
        );
        
        if (!$transfer_result['success']) {
            return array(
                'success' => false,
                'message' => 'Failed to transfer rewards: ' . $transfer_result['message']
            );
        }
        
        // Update reward statuses
        $reward_ids = wp_list_pluck($pending_rewards, 'id');
        
        foreach ($reward_ids as $reward_id) {
            $wpdb->update(
                $wpdb->prefix . 'vortex_dao_rewards',
                array(
                    'status' => 'claimed',
                    'claimed_at' => current_time('mysql'),
                    'wallet_address' => $wallet_address,
                    'transaction_signature' => $transfer_result['transaction_signature']
                ),
                array('id' => $reward_id)
            );
        }
        
        // Log the token transfer
        $this->token->log_token_transfer(
            $treasury_address,
            $wallet_address,
            $total_amount,
            $transfer_result['transaction_signature'],
            'rewards_claim',
            array(
                'user_id' => $user_id,
                'reward_count' => count($reward_ids)
            )
        );
        
        return array(
            'success' => true,
            'message' => sprintf('Successfully claimed %s TOLA tokens as rewards.', number_format($total_amount, 2)),
            'transaction_signature' => $transfer_result['transaction_signature'],
            'amount' => $total_amount,
            'reward_count' => count($reward_ids)
        );
    }
    
    /**
     * Get reward type label
     */
    private function get_reward_type_label($reward_type) {
        $labels = array(
            'sale' => 'Artwork Sale',
            'purchase' => 'Artwork Purchase',
            'promotion' => 'Tier Promotion',
            'governance' => 'Governance Participation',
            'referral' => 'Referral',
            'engagement' => 'Marketplace Engagement',
            'listing' => 'Artwork Listing',
            'bonus' => 'Special Bonus'
        );
        
        return isset($labels[$reward_type]) ? $labels[$reward_type] : ucfirst($reward_type);
    }
    
    /**
     * Get reward description
     */
    private function get_reward_description($reward) {
        $metadata = json_decode($reward->metadata, true);
        
        switch ($reward->reward_type) {
            case 'sale':
                $artwork_id = isset($metadata['artwork_id']) ? $metadata['artwork_id'] : 0;
                $price = isset($metadata['price']) ? $metadata['price'] : 0;
                
                $artwork_title = $this->get_artwork_title($artwork_id);
                
                return sprintf(
                    'Reward for selling artwork "%s" for %s',
                    $artwork_title,
                    number_format($price, 2) . ' ' . get_option('vortex_marketplace_currency', 'USD')
                );
                
            case 'purchase':
                $artwork_id = isset($metadata['artwork_id']) ? $metadata['artwork_id'] : 0;
                
                $artwork_title = $this->get_artwork_title($artwork_id);
                
                return sprintf('Reward for purchasing artwork "%s"', $artwork_title);
                
            case 'promotion':
                $new_tier = isset($metadata['new_tier']) ? $metadata['new_tier'] : '';
                
                return sprintf('Reward for reaching %s tier', $new_tier);
                
            case 'governance':
                $action_type = isset($metadata['action_type']) ? $metadata['action_type'] : '';
                $proposal_id = isset($metadata['proposal_id']) ? $metadata['proposal_id'] : 0;
                
                if ($action_type === 'create_proposal') {
                    return 'Reward for creating a DAO proposal';
                } else if ($action_type === 'vote') {
                    return 'Reward for voting on a DAO proposal';
                } else {
                    return 'Reward for governance participation';
                }
                
            case 'referral':
                $referred_user = isset($metadata['referred_user']) ? $metadata['referred_user'] : 0;
                
                $user = get_user_by('id', $referred_user);
                $username = $user ? $user->display_name : 'a new user';
                
                return sprintf('Reward for referring %s', $username);
                
            case 'engagement':
                $engagement_type = isset($metadata['engagement_type']) ? $metadata['engagement_type'] : '';
                
                if ($engagement_type === 'comment') {
                    return 'Reward for active engagement and comments';
                } else if ($engagement_type === 'like') {
                    return 'Reward for appreciating artwork';
                } else {
                    return 'Reward for marketplace engagement';
                }
                
            case 'listing':
                $count = isset($metadata['count']) ? $metadata['count'] : 1;
                
                return sprintf('Reward for listing %d new artwork%s', $count, $count > 1 ? 's' : '');
                
            case 'bonus':
                $reason = isset($metadata['reason']) ? $metadata['reason'] : '';
                
                return $reason ? 'Bonus reward: ' . $reason : 'Special bonus reward';
                
            default:
                return 'TOLA token reward';
        }
    }
    
    /**
     * Get artwork title
     */
    private function get_artwork_title($artwork_id) {
        global $wpdb;
        
        $title = $wpdb->get_var($wpdb->prepare(
            "SELECT title FROM {$wpdb->prefix}vortex_artworks WHERE id = %d",
            $artwork_id
        ));
        
        return $title ? $title : 'Unknown Artwork';
    }
    
    /**
     * Get artist tier
     */
    private function get_artist_tier($artist_id) {
        global $wpdb;
        
        $tier = $wpdb->get_var($wpdb->prepare(
            "SELECT tier FROM {$wpdb->prefix}vortex_artist_profiles WHERE user_id = %d",
            $artist_id
        ));
        
        return $tier ? $tier : 'bronze';
    }
    
    /**
     * Get artist reward percentage based on tier
     */
    private function get_artist_reward_percentage($tier) {
        $percentages = array(
            'bronze' => 1.0,    // 1%
            'silver' => 1.5,    // 1.5%
            'gold' => 2.0,      // 2%
            'platinum' => 3.0,  // 3%
            'diamond' => 5.0    // 5%
        );
        
        return isset($percentages[$tier]) ? $percentages[$tier] : 1.0;
    }
    
    /**
     * Get buyer reward percentage
     */
    private function get_buyer_reward_percentage() {
        return get_option('vortex_dao_buyer_reward_percentage', 0.5); // Default 0.5%
    }
    
    /**
     * Get tier promotion reward
     */
    private function get_tier_promotion_reward($tier) {
        $rewards = array(
            'bronze' => 0,       // No reward for initial tier
            'silver' => 25,      // 25 TOLA for silver
            'gold' => 50,        // 50 TOLA for gold
            'platinum' => 100,   // 100 TOLA for platinum
            'diamond' => 250     // 250 TOLA for diamond
        );
        
        return isset($rewards[$tier]) ? $rewards[$tier] : 0;
    }
    
    /**
     * Rewards shortcode
     */
    public function rewards_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'show_claim' => 'true'
        ), $atts);
        
        // Enqueue necessary scripts and styles
        wp_enqueue_style('vortex-dao-rewards');
        wp_enqueue_script('vortex-dao-rewards');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="vortex-dao-rewards-guest">
                <p>Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to view and claim your rewards.</p>
            </div>';
        }
        
        // Get user rewards
        $user_id = get_current_user_id();
        $rewards = $this->get_user_rewards($user_id);
        $total_pending = $this->get_total_pending_rewards($user_id);
        
        // Get user wallets
        global $wpdb;
        $wallets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_wallet_addresses 
            WHERE user_id = %d AND verified = 1 
            ORDER BY is_primary DESC, token_balance DESC",
            $user_id
        ));
        
        // Start output buffer
        ob_start();
        
        // Include template
        include(VORTEX_PLUGIN_DIR . 'public/partials/rewards/vortex-dao-rewards-dashboard.php');
        
        return ob_get_clean();
    }
    
    /**
     * Calculate daily rewards
     */
    public function calculate_daily_rewards() {
        global $wpdb;
        
        // 1. Engagement Rewards - Reward users who engage with the platform
        $this->calculate_engagement_rewards();
        
        // 2. Listing Rewards - Reward artists who list new artwork
        $this->calculate_listing_rewards();
        
        // 3. Referral Rewards - Process pending referrals
        $this->process_pending_referrals();
    }
    
    /**
     * Calculate engagement rewards
     */
    private function calculate_engagement_rewards() {
        global $wpdb;
        
        // Get engagement activity in the last 24 hours
        $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        // Comments and likes
        $engagements = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                user_id, 
                SUM(CASE WHEN activity_type = 'comment' THEN 1 ELSE 0 END) as comment_count,
                SUM(CASE WHEN activity_type = 'like' THEN 1 ELSE 0 END) as like_count
            FROM {$wpdb->prefix}vortex_user_activity 
            WHERE created_at >= %s AND (activity_type = 'comment' OR activity_type = 'like')
            GROUP BY user_id
            HAVING comment_count > 0 OR like_count > 0",
            $yesterday
        ));
        
        // Reward users based on engagement
        $comment_reward = get_option('vortex_dao_comment_reward', 0.1); // 0.1 TOLA per comment
        $like_reward = get_option('vortex_dao_like_reward', 0.05); // 0.05 TOLA per like
        $max_daily_reward = get_option('vortex_dao_max_daily_engagement_reward', 5); // Max 5 TOLA per day
        
        foreach ($engagements as $engagement) {
            $total_reward = ($engagement->comment_count * $comment_reward) + ($engagement->like_count * $like_reward);
            
            // Cap at max daily reward
            $total_reward = min($total_reward, $max_daily_reward);
            
            if ($total_reward > 0) {
                $engagement_type = $engagement->comment_count > $engagement->like_count ? 'comment' : 'like';
                
                $this->add_reward($engagement->user_id, $total_reward, 'engagement', array(
                    'engagement_type' => $engagement_type,
                    'comment_count' => $engagement->comment_count,
                    'like_count' => $engagement->like_count
                ));
            }
        }
    }
    
    /**
     * Calculate listing rewards
     */
    private function calculate_listing_rewards() {
        global $wpdb;
        
        // Get artists who listed new artwork in the last 24 hours
        $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        $listings = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, COUNT(*) as artwork_count
            FROM {$wpdb->prefix}vortex_artworks 
            WHERE created_at >= %s AND status = 'published'
            GROUP BY user_id
            HAVING artwork_count > 0",
            $yesterday
        ));
        
        // Reward users based on listings
        $listing_reward = get_option('vortex_dao_listing_reward', 1); // 1 TOLA per artwork
        $max_daily_reward = get_option('vortex_dao_max_daily_listing_reward', 10); // Max 10 TOLA per day
        
        foreach ($listings as $listing) {
            $total_reward = $listing->artwork_count * $listing_reward;
            
            // Cap at max daily reward
            $total_reward = min($total_reward, $max_daily_reward);
            
            if ($total_reward > 0) {
                $this->add_reward($listing->user_id, $total_reward, 'listing', array(
                    'count' => $listing->artwork_count
                ));
            }
        }
    }
    
    /**
     * Process pending referrals
     */
    private function process_pending_referrals() {
        global $wpdb;
        
        // Get pending referrals
        $pending_referrals = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vortex_referrals 
            WHERE processed = 0 AND status = 'completed'"
        );
        
        // Process each referral
        $referral_reward = get_option('vortex_dao_referral_reward', 5); // 5 TOLA per referral
        
        foreach ($pending_referrals as $referral) {
            // Add referral reward
            $this->add_reward($referral->referrer_id, $referral_reward, 'referral', array(
                'referred_user' => $referral->referred_id,
                'referral_id' => $referral->id
            ));
            
            // Mark referral as processed
            $wpdb->update(
                $wpdb->prefix . 'vortex_referrals',
                array('processed' => 1, 'processed_at' => current_time('mysql')),
                array('id' => $referral->id)
            );
        }
    }
    
    /**
     * Create required database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Rewards table
        $table_rewards = $wpdb->prefix . 'vortex_dao_rewards';
        $sql_rewards = "CREATE TABLE $table_rewards (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            amount decimal(18,8) NOT NULL DEFAULT 0,
            reward_type varchar(50) NOT NULL,
            metadata text DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            claimed_at datetime DEFAULT NULL,
            wallet_address varchar(64) DEFAULT NULL,
            transaction_signature varchar(100) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY reward_type (reward_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_rewards);
    }
}

// Initialize Rewards class
$vortex_dao_rewards = VORTEX_DAO_Rewards::get_instance();

// Register activation hook for table creation
register_activation_hook(VORTEX_PLUGIN_FILE, array('VORTEX_DAO_Rewards', 'create_tables')); 