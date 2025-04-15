    /**
     * Reward tier promotion.
     *
     * @param int $user_id User ID.
     * @param string $user_type User type (artist or collector).
     * @param int $new_tier_level New tier level.
     * @return bool Whether the reward was successful.
     */
    private function reward_tier_promotion($user_id, $user_type, $new_tier_level) {
        // Base bonus amount
        $base_bonus = 50; // Base TOLA-U tokens
        
        // Multiply by tier level
        $reward_amount = $base_bonus * ($new_tier_level + 1);
        
        // Create distribution
        $token_manager = VORTEX_DAO_Tokens::get_instance();
        
        $distribution_id = $token_manager->create_distribution(
            'tier_promotion',
            $reward_amount,
            'TOLA-U',
            sprintf(__('Reward for promotion to %s tier', 'vortex'), $this->get_tier_name($user_type, $new_tier_level))
        );
        
        if (!$distribution_id) {
            return false;
        }
        
        // Create claim for the user
        $claim_id = $token_manager->create_user_claim(
            $user_id,
            $distribution_id,
            $reward_amount,
            'TOLA-U'
        );
        
        if (!$claim_id) {
            return false;
        }
        
        // Process claim immediately
        $token_manager->process_claim($claim_id);
        
        // Log the reward
        $this->log_reward($user_id, 'tier_promotion', $reward_amount, $new_tier_level);
        
        return true;
    }
    
    /**
     * Issue grant to artist.
     *
     * @param int $user_id User ID.
     * @param float $amount Grant amount.
     * @param string $purpose Grant purpose.
     * @param string $notes Additional notes.
     * @return bool Whether the grant was issued successfully.
     */
    public function issue_grant($user_id, $amount, $purpose, $notes = '') {
        // Check if user is an artist
        $user_type = $this->get_user_type($user_id);
        if ($user_type !== 'artist') {
            return false;
        }
        
        // Check if there are enough available funds
        $treasury = VORTEX_DAO_Treasury::get_instance();
        $available_funds = $treasury->get_available_grant_funds();
        
        if ($available_funds < $amount) {
            return false;
        }
        
        // Create distribution
        $token_manager = VORTEX_DAO_Tokens::get_instance();
        
        $distribution_id = $token_manager->create_distribution(
            'artist_grant',
            $amount,
            'TOLA-U',
            sprintf(__('Grant for %s: %s', 'vortex'), get_userdata($user_id)->display_name, $purpose)
        );
        
        if (!$distribution_id) {
            return false;
        }
        
        // Create claim for the artist
        $claim_id = $token_manager->create_user_claim(
            $user_id,
            $distribution_id,
            $amount,
            'TOLA-U'
        );
        
        if (!$claim_id) {
            return false;
        }
        
        // Process claim immediately
        $token_manager->process_claim($claim_id);
        
        // Update available grant funds
        update_option('vortex_dao_available_grant_funds', $available_funds - $amount);
        
        // Log the grant
        $this->log_grant($user_id, $amount, $purpose, $notes);
        
        return true;
    }
    
    /**
     * Log user reward.
     *
     * @param int $user_id User ID.
     * @param string $reward_type Reward type.
     * @param float $amount Reward amount.
     * @param mixed $reference_id Reference ID.
     * @return bool Whether the log was created successfully.
     */
    private function log_reward($user_id, $reward_type, $amount, $reference_id = '') {
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_reward_logs',
            [
                'user_id' => $user_id,
                'reward_type' => $reward_type,
                'amount' => $amount,
                'reference_id' => $reference_id,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%f', '%s', '%s']
        );
        
        return $result !== false;
    }
    
    /**
     * Log grant.
     *
     * @param int $user_id User ID.
     * @param float $amount Grant amount.
     * @param string $purpose Grant purpose.
     * @param string $notes Additional notes.
     * @return bool Whether the log was created successfully.
     */
    private function log_grant($user_id, $amount, $purpose, $notes = '') {
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_grant_logs',
            [
                'user_id' => $user_id,
                'amount' => $amount,
                'purpose' => $purpose,
                'notes' => $notes,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%f', '%s', '%s', '%s']
        );
        
        return $result !== false;
    }
    
    /**
     * Get user type.
     *
     * @param int $user_id User ID.
     * @return string User type (artist, collector, or both).
     */
    public function get_user_type($user_id) {
        // Check if user has created artwork
        $artwork_count = $this->get_artist_artwork_count($user_id);
        
        // Check if user has purchased artwork
        $purchase_count = $this->get_collector_purchase_count($user_id);
        
        if ($artwork_count > 0 && $purchase_count == 0) {
            return 'artist';
        } elseif ($artwork_count == 0 && $purchase_count > 0) {
            return 'collector';
        } elseif ($artwork_count > 0 && $purchase_count > 0) {
            // If user has more artwork than purchases, they're primarily an artist
            return $artwork_count >= $purchase_count ? 'artist' : 'collector';
        }
        
        // Default to collector if we can't determine
        return 'collector';
    }
    
    /**
     * Get tier name.
     *
     * @param string $user_type User type (artist or collector).
     * @param int $tier_level Tier level.
     * @return string Tier name.
     */
    public function get_tier_name($user_type, $tier_level) {
        $artist_tiers = [
            0 => __('Emerging Artist', 'vortex'),
            1 => __('Established Artist', 'vortex'),
            2 => __('Signature Artist', 'vortex'),
            3 => __('Elite Artist', 'vortex'),
        ];
        
        $collector_tiers = [
            0 => __('Beginner Collector', 'vortex'),
            1 => __('Enthusiast Collector', 'vortex'),
            2 => __('Patron Collector', 'vortex'),
            3 => __('Whale Collector', 'vortex'),
        ];
        
        if ($user_type === 'artist') {
            return isset($artist_tiers[$tier_level]) ? $artist_tiers[$tier_level] : $artist_tiers[0];
        } else {
            return isset($collector_tiers[$tier_level]) ? $collector_tiers[$tier_level] : $collector_tiers[0];
        }
    }
    
    /**
     * Get user tier information.
     *
     * @param int $user_id User ID.
     * @param string $user_type User type (artist or collector).
     * @return array Tier information.
     */
    public function get_user_tier_info($user_id, $user_type) {
        global $wpdb;
        
        $tier = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_dao_user_tiers WHERE user_id = %d AND user_type = %s",
                $user_id,
                $user_type
            )
        );
        
        if ($tier) {
            return [
                'id' => $tier->id,
                'tier_level' => $tier->tier_level,
                'tier_name' => $tier->tier_name,
                'requirements_met' => json_decode($tier->requirements_met, true),
                'promotion_date' => $tier->promotion_date,
            ];
        }
        
        // Default tier info if not found
        return [
            'id' => 0,
            'tier_level' => 0,
            'tier_name' => $this->get_tier_name($user_type, 0),
            'requirements_met' => [],
            'promotion_date' => null,
        ];
    }
    
    /**
     * Get users by type.
     *
     * @param string $type User type (artist or collector).
     * @return array Array of user IDs.
     */
    private function get_users_by_type($type) {
        global $wpdb;
        
        if ($type === 'artist') {
            // Get users who have created artwork
            $users = $wpdb->get_col(
                "SELECT DISTINCT post_author FROM {$wpdb->posts} 
                WHERE post_type = 'vortex_artwork' AND post_status = 'publish'"
            );
        } else {
            // Get users who have purchased artwork
            $users = $wpdb->get_col(
                "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} 
                WHERE meta_key = '_vortex_artwork_buyer'"
            );
        }
        
        return array_map('intval', $users);
    }
    
    /**
     * Get artist artwork count.
     *
     * @param int $user_id User ID.
     * @return int Artwork count.
     */
    private function get_artist_artwork_count($user_id) {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
                WHERE post_author = %d AND post_type = 'vortex_artwork' AND post_status = 'publish'",
                $user_id
            )
        );
        
        return intval($count);
    }
    
    /**
     * Get artist average rating.
     *
     * @param int $user_id User ID.
     * @return float Average rating.
     */
    private function get_artist_avg_rating($user_id) {
        global $wpdb;
        
        // Get all artwork IDs by this artist
        $artwork_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} 
                WHERE post_author = %d AND post_type = 'vortex_artwork' AND post_status = 'publish'",
                $user_id
            )
        );
        
        if (empty($artwork_ids)) {
            return 0;
        }
        
        // Get average rating for these artworks
        $artwork_ids_string = implode(',', array_map('intval', $artwork_ids));
        
        $avg_rating = $wpdb->get_var(
            "SELECT AVG(meta_value) FROM {$wpdb->postmeta} 
            WHERE post_id IN ($artwork_ids_string) AND meta_key = '_vortex_artwork_rating'"
        );
        
        return $avg_rating ? floatval($avg_rating) : 0;
    }
    
    /**
     * Get artist sales count.
     *
     * @param int $user_id User ID.
     * @return int Sales count.
     */
    private function get_artist_sales_count($user_id) {
        global $wpdb;
        
        // Get all artwork IDs by this artist
        $artwork_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} 
                WHERE post_author = %d AND post_type = 'vortex_artwork' AND post_status = 'publish'",
                $user_id
            )
        );
        
        if (empty($artwork_ids)) {
            return 0;
        }
        
        // Count sales for these artworks
        $artwork_ids_string = implode(',', array_map('intval', $artwork_ids));
        
        $sales_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE post_id IN ($artwork_ids_string) AND meta_key = '_vortex_artwork_sold' AND meta_value = '1'"
        );
        
        return intval($sales_count);
    }
    
    /**
     * Get artist follower count.
     *
     * @param int $user_id User ID.
     * @return int Follower count.
     */
    private function get_artist_follower_count($user_id) {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_user_follows 
                WHERE followed_id = %d AND status = 'active'",
                $user_id
            )
        );
        
        return intval($count);
    }
    
    /**
     * Get collector purchase count.
     *
     * @param int $user_id User ID.
     * @return int Purchase count.
     */
    private function get_collector_purchase_count($user_id) {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} 
                WHERE meta_key = '_vortex_artwork_buyer' AND meta_value = %d",
                $user_id
            )
        );
        
        return intval($count);
    }
    
    /**
     * Get collector total spent.
     *
     * @param int $user_id User ID.
     * @return float Total spent.
     */
    private function get_collector_total_spent($user_id) {
        global $wpdb;
        
        // Get all artwork IDs purchased by this collector
        $artwork_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_vortex_artwork_buyer' AND meta_value = %d",
                $user_id
            )
        );
        
        if (empty($artwork_ids)) {
            return 0;
        }
        
        // Sum prices for these artworks
        $artwork_ids_string = implode(',', array_map('intval', $artwork_ids));
        
        $total_spent = $wpdb->get_var(
            "SELECT SUM(meta_value) FROM {$wpdb->postmeta} 
            WHERE post_id IN ($artwork_ids_string) AND meta_key = '_vortex_artwork_sale_price'"
        );
        
        return $total_spent ? floatval($total_spent) : 0;
    }
    
    /**
     * Get collector unique artists.
     *
     * @param int $user_id User ID.
     * @return int Unique artists count.
     */
    private function get_collector_unique_artists($user_id) {
        global $wpdb;
        
        // Get all artwork IDs purchased by this collector
        $artwork_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_vortex_artwork_buyer' AND meta_value = %d",
                $user_id
            )
        );
        
        if (empty($artwork_ids)) {
            return 0;
        }
        
        // Count unique artists for these artworks
        $artwork_ids_string = implode(',', array_map('intval', $artwork_ids));
        
        $unique_artists = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_author) FROM {$wpdb->posts} 
            WHERE ID IN ($artwork_ids_string)"
        );
        
        return intval($unique_artists);
    }
    
    /**
     * Get collector token balance.
     *
     * @param int $user_id User ID.
     * @return float Token balance.
     */
    private function get_collector_token_balance($user_id) {
        $token_manager = VORTEX_DAO_Tokens::get_instance();
        return $token_manager->get_utility_token_balance($user_id);
    }
    
    /**
     * Get user rewards.
     *
     * @param int $user_id User ID.
     * @param int $limit Optional limit.
     * @return array User rewards.
     */
    public function get_user_rewards($user_id, $limit = 10) {
        global $wpdb;
        
        $rewards = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_dao_reward_logs 
                WHERE user_id = %d 
                ORDER BY created_at DESC 
                LIMIT %d",
                $user_id,
                $limit
            )
        );
        
        return $rewards;
    }
    
    /**
     * Notify tier change.
     *
     * @param int $user_id User ID.
     * @param string $user_type User type (artist or collector).
     * @param int $old_tier Old tier level.
     * @param int $new_tier New tier level.
     */
    private function notify_tier_change($user_id, $user_type, $old_tier, $new_tier) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        $old_tier_name = $this->get_tier_name($user_type, $old_tier);
        $new_tier_name = $this->get_tier_name($user_type, $new_tier);
        
        // Send email notification
        $subject = sprintf(__('Your %s tier has been updated on %s', 'vortex'), $user_type, get_bloginfo('name'));
        
        $message = sprintf(
            __("Hello %s,\n\nCongratulations! Your %s tier has been updated from %s to %s on %s.\n\nThis tier upgrade comes with new benefits and increased rewards. Visit your dashboard to learn more about your new tier benefits.\n\nThank you for being part of our community!\n\n%s Team", 'vortex'),
            $user->display_name,
            $user_type,
            $old_tier_name,
            $new_tier_name,
            get_bloginfo('name'),
            get_bloginfo('name')
        );
        
        wp_mail($user->user_email, $subject, $message);
        
        // Add notification in the system
        if (function_exists('vortex_add_notification')) {
            vortex_add_notification(
                $user_id,
                'tier_change',
                sprintf(
                    __('Congratulations! You have been promoted to %s tier', 'vortex'),
                    $new_tier_name
                ),
                [
                    'user_type' => $user_type,
                    'old_tier' => $old_tier,
                    'new_tier' => $new_tier,
                ]
            );
        }
    }
    
    /**
     * AJAX: Issue grant.
     */
    public function ajax_issue_grant() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_issue_grant')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'vortex')]);
        }
        
        // Get grant details
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $purpose = isset($_POST['purpose']) ? sanitize_text_field($_POST['purpose']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        if ($user_id <= 0 || $amount <= 0 || empty($purpose)) {
            wp_send_json_error(['message' => __('Invalid grant details.', 'vortex')]);
        }
        
        // Issue grant
        $success = $this->issue_grant($user_id, $amount, $purpose, $notes);
        
        if (!$success) {
            wp_send_json_error(['message' => __('Failed to issue grant.', 'vortex')]);
        }
        
        wp_send_json_success([
            'message' => sprintf(__('Successfully issued grant of %s TOLA-U tokens to %s.', 'vortex'), 
                number_format($amount, 2),
                get_userdata($user_id)->display_name
            ),
        ]);
    }
    
    /**
     * AJAX: Update user tier.
     */
    public function ajax_update_user_tier() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_update_user_tier')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'vortex')]);
        }
        
        // Get tier details
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $user_type = isset($_POST['user_type']) ? sanitize_text_field($_POST['user_type']) : '';
        $tier_level = isset($_POST['tier_level']) ? intval($_POST['tier_level']) : 0;
        
        if ($user_id <= 0 || empty($user_type) || $tier_level < 0 || $tier_level > 3) {
            wp_send_json_error(['message' => __('Invalid tier details.', 'vortex')]);
        }
        
        // Update tier
        if ($user_type === 'artist') {
            $success = $this->update_artist_tier($user_id);
        } else {
            $success = $this->update_collector_tier($user_id);
        }
        
        if (!$success) {
            wp_send_json_error(['message' => __('Failed to update user tier.', 'vortex')]);
        }
        
        wp_send_json_success([
            'message' => sprintf(__('Successfully updated %s tier for %s.', 'vortex'), 
                $user_type,
                get_userdata($user_id)->display_name
            ),
        ]);
    }
} 