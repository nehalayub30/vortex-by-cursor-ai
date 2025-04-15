/**
 * API callback for artwork stats
 * 
 * @param WP_REST_Request $request The request object
 * @return WP_REST_Response The response
 */
public function api_get_artwork_stats($request) {
    global $wpdb;
    
    // Get overall stats
    $overall_stats_query = "SELECT 
                              COUNT(*) as total_artworks,
                              SUM(CASE WHEN sale_status = 'sold' THEN 1 ELSE 0 END) as sold_artworks,
                              SUM(CASE WHEN sale_status = 'sold' THEN price ELSE 0 END) as total_sales,
                              COUNT(DISTINCT artist_id) as total_artists
                           FROM {$wpdb->prefix}vortex_artworks";
    
    $overall_stats = $wpdb->get_row($overall_stats_query);
    
    // Get top artists by sales
    $top_artists_query = "SELECT 
                              a.artist_id,
                              u.display_name,
                              COUNT(*) as artwork_count,
                              SUM(CASE WHEN a.sale_status = 'sold' THEN a.price ELSE 0 END) as total_sales
                            FROM {$wpdb->prefix}vortex_artworks a
                            JOIN {$wpdb->users} u ON a.artist_id = u.ID
                            GROUP BY a.artist_id
                            ORDER BY total_sales DESC
                            LIMIT 10";
    
    $top_artists = $wpdb->get_results($top_artists_query);
    
    // Get most viewed artworks
    $most_viewed_query = "SELECT 
                              a.artwork_id,
                              a.title,
                              u.display_name as artist_name,
                              COUNT(v.view_id) as view_count
                            FROM {$wpdb->prefix}vortex_artworks a
                            JOIN {$wpdb->users} u ON a.artist_id = u.ID
                            JOIN {$wpdb->prefix}vortex_artwork_views v ON a.artwork_id = v.artwork_id
                            GROUP BY a.artwork_id
                            ORDER BY view_count DESC
                            LIMIT 10";
                            
    $most_viewed = $wpdb->get_results($most_viewed_query);
    
    // Get most liked artworks
    $most_liked_query = "SELECT 
                              a.artwork_id,
                              a.title,
                              u.display_name as artist_name,
                              COUNT(l.like_id) as like_count
                            FROM {$wpdb->prefix}vortex_artworks a
                            JOIN {$wpdb->users} u ON a.artist_id = u.ID
                            JOIN {$wpdb->prefix}vortex_artwork_likes l ON a.artwork_id = l.artwork_id
                            GROUP BY a.artwork_id
                            ORDER BY like_count DESC
                            LIMIT 10";
                            
    $most_liked = $wpdb->get_results($most_liked_query);
    
    return rest_ensure_response(array(
        'overall' => $overall_stats,
        'top_artists' => $top_artists,
        'most_viewed' => $most_viewed,
        'most_liked' => $most_liked
    ));
}

/**
 * API callback for blockchain stats
 * 
 * @param WP_REST_Request $request The request object
 * @return WP_REST_Response The response
 */
public function api_get_blockchain_stats($request) {
    if (!$this->blockchain_enabled) {
        return new WP_Error('blockchain_disabled', 'Blockchain features are not enabled', array('status' => 400));
    }
    
    global $wpdb;
    
    // Get total TOLA rewards distributed
    $total_rewards_query = "SELECT 
                               SUM(amount) as total_rewards,
                               COUNT(DISTINCT user_id) as rewarded_users
                             FROM {$wpdb->prefix}vortex_tola_transactions
                             WHERE transaction_type IN ('achievement_reward', 'badge_reward', 'level_up_reward')";
                               
    $total_rewards = $wpdb->get_row($total_rewards_query);
    
    // Get NFT stats
    $nft_stats_query = "SELECT 
                               COUNT(*) as total_nfts,
                               COUNT(DISTINCT artist_id) as nft_artists,
                               SUM(price) as total_nft_value
                             FROM {$wpdb->prefix}vortex_artworks
                             WHERE blockchain_tokenized = 1";
                               
    $nft_stats = $wpdb->get_row($nft_stats_query);
    
    // Get top NFT artists
    $top_nft_artists_query = "SELECT 
                                   a.artist_id,
                                   u.display_name,
                                   COUNT(*) as nft_count,
                                   SUM(a.price) as total_value
                                 FROM {$wpdb->prefix}vortex_artworks a
                                 JOIN {$wpdb->users} u ON a.artist_id = u.ID
                                 WHERE a.blockchain_tokenized = 1
                                 GROUP BY a.artist_id
                                 ORDER BY total_value DESC
                                 LIMIT 10";
                                 
    $top_nft_artists = $wpdb->get_results($top_nft_artists_query);
    
    // Get recent NFT sales
    $recent_nft_sales_query = "SELECT 
                                   t.transaction_id,
                                   a.artwork_id,
                                   a.title,
                                   u.display_name as artist_name,
                                   t.amount as price,
                                   t.transaction_time
                                 FROM {$wpdb->prefix}vortex_transactions t
                                 JOIN {$wpdb->prefix}vortex_artworks a ON t.artwork_id = a.artwork_id
                                 JOIN {$wpdb->users} u ON a.artist_id = u.ID
                                 WHERE a.blockchain_tokenized = 1
                                 AND t.status = 'completed'
                                 ORDER BY t.transaction_time DESC
                                 LIMIT 10";
                                 
    $recent_nft_sales = $wpdb->get_results($recent_nft_sales_query);
    
    // Get most swapped artists
    $swapped_artists_query = "SELECT 
                                   a.artist_id,
                                   u.display_name,
                                   COUNT(*) as swap_count
                                 FROM {$wpdb->prefix}vortex_token_swaps s
                                 JOIN {$wpdb->prefix}vortex_artworks a ON s.artwork_id = a.artwork_id
                                 JOIN {$wpdb->users} u ON a.artist_id = u.ID
                                 GROUP BY a.artist_id
                                 ORDER BY swap_count DESC
                                 LIMIT 10";
                                 
    $swapped_artists = $wpdb->get_results($swapped_artists_query);
    
    // Get most swapped categories
    $swapped_categories_query = "SELECT 
                                       c.category_id,
                                       c.category_name,
                                       COUNT(*) as swap_count
                                     FROM {$wpdb->prefix}vortex_token_swaps s
                                     JOIN {$wpdb->prefix}vortex_artworks a ON s.artwork_id = a.artwork_id
                                     JOIN {$wpdb->prefix}vortex_categories c ON a.category_id = c.category_id
                                     GROUP BY c.category_id
                                     ORDER BY swap_count DESC
                                     LIMIT 10";
                                     
    $swapped_categories = $wpdb->get_results($swapped_categories_query);
    
    // Get blockchain activity metrics
    $blockchain_query = "SELECT 
                                  transaction_type,
                                  COUNT(*) as transaction_count,
                                  SUM(amount) as total_amount
                                FROM {$wpdb->prefix}vortex_tola_transactions
                                GROUP BY transaction_type
                                ORDER BY total_amount DESC";
                                
    $blockchain_activity = $wpdb->get_results($blockchain_query);
    
    return rest_ensure_response(array(
        'tola_rewards' => $total_rewards,
        'nft_stats' => $nft_stats,
        'top_nft_artists' => $top_nft_artists,
        'recent_nft_sales' => $recent_nft_sales,
        'most_swapped_artists' => $swapped_artists,
        'most_swapped_categories' => $swapped_categories,
        'blockchain_activity' => $blockchain_activity
    ));
}

/**
 * Checks API permissions
 * 
 * @param WP_REST_Request $request The request object
 * @return bool Whether the request has permission
 */
public function check_api_permissions($request) {
    // Check for current user permission
    if (is_user_logged_in()) {
        $user_id = $request->get_param('user_id');
        
        // If no user_id specified or requesting own data
        if (!$user_id || $user_id == get_current_user_id()) {
            return true;
        }
        
        // Admin can access any user's data
        if (current_user_can('manage_options')) {
            return true;
        }
    }
    
    // Check for API key
    $api_key = $request->get_header('X-Vortex-API-Key');
    if ($api_key) {
        $valid_key = get_option('vortex_api_key', '');
        return $api_key === $valid_key;
    }
    
    return false;
}

/**
 * Initialize the gamification system
 */
function vortex_initialize_gamification() {
    global $vortex_gamification;
    $vortex_gamification = new VORTEX_Gamification();
}
add_action('plugins_loaded', 'vortex_initialize_gamification');

/**
 * Add points to user
 * @param int $user_id User ID
 * @param int $points Points
 * @param string $type Points type
 * @param int $reference_id Reference ID (artwork, transaction, etc.)
 */
private function add_points($user_id, $points, $type, $reference_id = 0) {
    // Create points transaction
    $transaction_id = wp_insert_post(array(
        'post_type' => 'vortex_points',
        'post_title' => sprintf('%s: %d points', $type, $points),
        'post_status' => 'publish',
        'post_author' => $user_id
    ));
    
    if (is_wp_error($transaction_id)) {
        return false;
    }
    
    // Add transaction metadata
    update_post_meta($transaction_id, 'vortex_points_amount', $points);
    update_post_meta($transaction_id, 'vortex_points_type', $type);
    update_post_meta($transaction_id, 'vortex_reference_id', $reference_id);
    
    // Update user's total points
    $total_points = get_user_meta($user_id, 'vortex_total_points', true);
    $total_points = $total_points ? intval($total_points) + $points : $points;
    update_user_meta($user_id, 'vortex_total_points', $total_points);
    
    // Update points breakdown by type
    $type_points = get_user_meta($user_id, "vortex_points_{$type}", true);
    $type_points = $type_points ? intval($type_points) + $points : $points;
    update_user_meta($user_id, "vortex_points_{$type}", $type_points);
    
    // Notify user
    $this->notify_points_added($user_id, $points, $type);
    
    return true;
}

/**
 * Notify user about points
 * @param int $user_id User ID
 * @param int $points Points
 * @param string $type Points type
 */
private function notify_points_added($user_id, $points, $type) {
    // Create notification
    $type_label = str_replace('_', ' ', $type);
    $type_label = ucwords($type_label);
    
    $notification = array(
        'title' => 'Points Earned!',
        'message' => sprintf('You earned %d points for %s', $points, $type_label),
        'link' => '',
        'link_text' => ''
    );
    
    // Add to notification system
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_user_notifications';
    
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'notification_type' => 'browser',
            'notification_data' => maybe_serialize($notification),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        )
    );
}

/**
 * Calculate level based on points
 * @param int $points Points
 * @param string $type User type ('artist' or 'collector')
 * @return int Level
 */
private function calculate_level($points, $type) {
    $levels = isset($this->settings['levels'][$type]) ? $this->settings['levels'][$type] : array(1 => 0);
    
    $level = 1;
    foreach ($levels as $l => $required_points) {
        if ($points >= $required_points) {
            $level = $l;
        } else {
            break;
        }
    }
    
    return $level;
}

/**
 * Notify user about level up
 * @param int $user_id User ID
 * @param string $type Level type
 * @param int $level New level
 */
private function notify_level_up($user_id, $type, $level) {
    // Create notification
    $notification = array(
        'title' => 'Level Up!',
        'message' => sprintf('Congratulations! You reached level %d as a%s %s', 
                         $level, 
                         (in_array(substr($type, 0, 1), array('a', 'e', 'i', 'o', 'u')) ? 'n' : ''),
                         $type),
        'link' => '',
        'link_text' => ''
    );
    
    // Add to notification system
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_user_notifications';
    
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'notification_type' => 'browser',
            'notification_data' => maybe_serialize($notification),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        )
    );
    
    // Award TOLA tokens for level up (if blockchain enabled)
    $this->award_tola_for_level_up($user_id, $type, $level);
}

/**
 * Update artist badges
 * @param int $user_id User ID
 * @param int $level Artist level
 */
private function update_artist_badges($user_id, $level) {
    if ($level >= 9) {
        $this->add_badge($user_id, 'artist_master');
    } elseif ($level >= 6) {
        $this->add_badge($user_id, 'artist_elite');
    } elseif ($level >= 3) {
        $this->add_badge($user_id, 'artist_established');
    } else {
        $this->add_badge($user_id, 'artist_rookie');
    }
}

/**
 * Update collector badges
 * @param int $user_id User ID
 * @param int $level Collector level
 */
private function update_collector_badges($user_id, $level) {
    if ($level >= 8) {
        $this->add_badge($user_id, 'collector_patron');
    } elseif ($level >= 5) {
        $this->add_badge($user_id, 'collector_connoisseur');
    } elseif ($level >= 3) {
        $this->add_badge($user_id, 'collector_enthusiast');
    } else {
        $this->add_badge($user_id, 'collector_beginner');
    }
}

/**
 * Check for premium collector achievement
 * @param int $user_id User ID
 */
private function check_premium_collector($user_id) {
    global $wpdb;
    
    // Count premium purchases (over $100)
    $premium_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT artwork_id) 
         FROM {$wpdb->prefix}vortex_transactions
         WHERE buyer_id = %d AND price >= 100 AND status = 'completed'",
        $user_id
    ));
    
    if ($premium_count >= 5) {
        $this->check_achievement($user_id, 'premium_collector');
    }
}

/**
 * Check achievement
 * @param int $user_id User ID
 * @param string $achievement_code Achievement code
 */
private function check_achievement($user_id, $achievement_code) {
    // Check if already has achievement
    $user_achievements = get_user_meta($user_id, 'vortex_achievements', true);
    $user_achievements = $user_achievements ? $user_achievements : array();
    
    if (in_array($achievement_code, $user_achievements)) {
        return;
    }
    
    // Add achievement
    $user_achievements[] = $achievement_code;
    update_user_meta($user_id, 'vortex_achievements', $user_achievements);
    
    // Get achievement name
    $achievement_name = isset($this->settings['achievements'][$achievement_code]) 
        ? $this->settings['achievements'][$achievement_code] 
        : 'Achievement Unlocked';
    
    // Notify user
    $notification = array(
        'title' => 'Achievement Unlocked!',
        'message' => $achievement_name,
        'link' => '',
        'link_text' => ''
    );
    
    // Add to notification system
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_user_notifications';
    
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'notification_type' => 'browser',
            'notification_data' => maybe_serialize($notification),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        )
    );
    
    // Award TOLA tokens for achievement
    $this->award_tola_for_achievement($user_id, $achievement_code);
}

/**
 * Add badge to user
 * @param int $user_id User ID
 * @param string $badge_code Badge code
 */
private function add_badge($user_id, $badge_code) {
    // Check if already has badge
    $user_badges = get_user_meta($user_id, 'vortex_badges', true);
    $user_badges = $user_badges ? $user_badges : array();
    
    if (in_array($badge_code, $user_badges)) {
        return;
    }
    
    // Add badge
    $user_badges[] = $badge_code;
    update_user_meta($user_id, 'vortex_badges', $user_badges);
    
    // Get badge name
    $badge_name = isset($this->settings['badges'][$badge_code]) 
        ? $this->settings['badges'][$badge_code] 
        : 'Badge Awarded';
    
    // Notify user
    $notification = array(
        'title' => 'New Badge!',
        'message' => sprintf('You earned the "%s" badge', $badge_name),
        'link' => '',
        'link_text' => ''
    );
    
    // Add to notification system
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_user_notifications';
    
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'notification_type' => 'browser',
            'notification_data' => maybe_serialize($notification),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        )
    );
    
    // Award TOLA tokens for badge
    $this->award_tola_for_badge($user_id, $badge_code);
}

/**
 * Award TOLA tokens for achievement
 * @param int $user_id User ID
 * @param string $achievement_code Achievement code
 */
private function award_tola_for_achievement($user_id, $achievement_code) {
    // Check if blockchain integration is available
    if (!class_exists('VORTEX_TOLA_Integration')) {
        return;
    }
    
    // Token amounts for different achievements
    $token_amounts = array(
        'first_artwork' => 5,
        'first_sale' => 10,
        'blockchain_certified' => 15,
        'collection_started' => 5,
        'rising_star' => 20,
        'premium_collector' => 25,
        'deep_learning_contributor' => 30,
        'social_influencer' => 20,
        'master_artist' => 50,
        'art_connoisseur' => 40
    );
    
    $token_amount = isset($token_amounts[$achievement_code]) 
        ? $token_amounts[$achievement_code] 
        : 5;
    
    // Create TOLA transaction
    $tola = new VORTEX_TOLA_Integration();
    $transaction_data = array(
        'user_id' => $user_id,
        'amount' => $token_amount,
        'transaction_type' => 'achievement_reward',
        'reference_data' => array(
            'achievement_code' => $achievement_code
        )
    );
    
    $tola->create_token_transaction($transaction_data);
}

/**
 * Award TOLA tokens for badge
 * @param int $user_id User ID
 * @param string $badge_code Badge code
 */
private function award_tola_for_badge($user_id, $badge_code) {
    // Check if blockchain integration is available
    if (!class_exists('VORTEX_TOLA_Integration')) {
        return;
    }
    
    // Token amounts for different badges
    $token_amounts = array(
        'artist_rookie' => 2,
        'artist_established' => 5,
        'artist_elite' => 15,
        'artist_master' => 30,
        'collector_beginner' => 2,
        'collector_enthusiast' => 5,
        'collector_connoisseur' => 15,
        'collector_patron' => 30,
        'blockchain_verified' => 10,
        'deep_learning_contributor' => 15,
        'community_pillar' => 20
    );
    
    $token_amount = isset($token_amounts[$badge_code]) 
        ? $token_amounts[$badge_code] 
        : 2;
    
    // Create TOLA transaction
    $tola = new VORTEX_TOLA_Integration();
    $transaction_data = array(
        'user_id' => $user_id,
        'amount' => $token_amount,
        'transaction_type' => 'badge_reward',
        'reference_data' => array(
            'badge_code' => $badge_code
        )
    );
    
    $tola->create_token_transaction($transaction_data);
}

/**
 * Award TOLA tokens for level up
 * @param int $user_id User ID
 * @param string $type Level type
 * @param int $level New level
 */
private function award_tola_for_level_up($user_id, $type, $level) {
    // Check if blockchain integration is available
    if (!class_exists('VORTEX_TOLA_Integration')) {
        return;
    }
    
    // Base token amount
    $base_amount = 5;
    
    // Calculate token amount based on level
    $token_amount = $base_amount * $level;
    
    // Create TOLA transaction
    $tola = new VORTEX_TOLA_Integration();
    $transaction_data = array(
        'user_id' => $user_id,
        'amount' => $token_amount,
        'transaction_type' => 'level_up_reward',
        'reference_data' => array(
            'level_type' => $type,
            'level' => $level
        )
    );
    
    $tola->create_token_transaction($transaction_data);
}

/**
 * Get leaderboard
 * @param string $type Leaderboard type (artists, collectors, trending)
 * @param string $period Time period (week, month, all)
 * @param int $limit Number of entries
 * @return array Leaderboard data
 */
public function get_leaderboard($type = 'artists', $period = 'month', $limit = 10) {
    global $wpdb;
    
    $limit = intval($limit);
    
    // Get time period condition
    $time_condition = '';
    if ($period == 'week') {
        $time_condition = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($period == 'month') {
        $time_condition = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }
    
    if ($type == 'artists') {
        // Artists leaderboard based on points
        $query = $wpdb->prepare(
            "SELECT 
                u.ID as user_id,
                u.display_name,
                um.meta_value as total_points,
                COUNT(DISTINCT p.ID) as artwork_count,
                GET_LOCKED_VALUE(u.ID) as level
             FROM {$wpdb->users} u
             JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'vortex_total_points'
             LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'vortex_is_artist' AND um2.meta_value = '1'
             LEFT JOIN {$wpdb->posts} p ON u.ID = p.post_author AND p.post_type = 'vortex_artwork' AND p.post_status = 'publish' {$time_condition}
             WHERE um2.meta_value IS NOT NULL
             GROUP BY u.ID
             ORDER BY CAST(um.meta_value AS UNSIGNED) DESC
             LIMIT %d",
            $limit
        );
        
        return $wpdb->get_results($query);
    } elseif ($type == 'collectors') {
        // Collectors leaderboard based on purchases
        $query = $wpdb->prepare(
            "SELECT 
                u.ID as user_id,
                u.display_name,
                COUNT(DISTINCT t.transaction_id) as purchase_count,
                SUM(t.amount) as total_spent,
                um.meta_value as collector_level
             FROM {$wpdb->users} u
             JOIN {$wpdb->prefix}vortex_transactions t ON u.ID = t.buyer_id AND t.status = 'completed' {$time_condition}
             LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'vortex_collector_level'
             GROUP BY u.ID
             ORDER BY purchase_count DESC, total_spent DESC
             LIMIT %d",
            $limit
        );
        
        return $wpdb->get_results($query);
    } else { // trending
        // Trending artworks
        $query = $wpdb->prepare(
            "SELECT 
                a.artwork_id,
                a.title,
                u.display_name as artist_name,
                COUNT(DISTINCT v.view_id) as view_count,
                COUNT(DISTINCT l.like_id) as like_count,
                COUNT(DISTINCT s.share_id) as share_count,
                (COUNT(DISTINCT v.view_id) * 1 + COUNT(DISTINCT l.like_id) * 3 + COUNT(DISTINCT s.share_id) * 5) as trend_score
             FROM {$wpdb->prefix}vortex_artworks a
             JOIN {$wpdb->users} u ON a.artist_id = u.ID
             LEFT JOIN {$wpdb->prefix}vortex_artwork_views v ON a.artwork_id = v.artwork_id {$time_condition}
             LEFT JOIN {$wpdb->prefix}vortex_artwork_likes l ON a.artwork_id = l.artwork_id {$time_condition}
             LEFT JOIN {$wpdb->prefix}vortex_artwork_shares s ON a.artwork_id = s.artwork_id {$time_condition}
             GROUP BY a.artwork_id
             ORDER BY trend_score DESC
             LIMIT %d",
            $limit
        );
        
        return $wpdb->get_results($query);
    }
}

// Initialize gamification
add_action('plugins_loaded', function() {
    VORTEX_Gamification::get_instance();
});

/**
 * AJAX handler for claiming achievement rewards
 */
public function ajax_claim_achievement() {
    check_ajax_referer('vortex_gamification_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    $achievement_id = isset($_POST['achievement_id']) ? intval($_POST['achievement_id']) : 0;
    
    if (!$user_id) {
        wp_send_json_error(array('message' => 'User not logged in'));
        return;
    }
    
    if (!$achievement_id) {
        wp_send_json_error(array('message' => 'Invalid achievement ID'));
        return;
    }
    
    global $wpdb;
    
    // Check if user has earned this achievement
    $user_achievement = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vortex_user_achievements
         WHERE user_id = %d AND achievement_id = %d",
        $user_id, $achievement_id
    ));
    
    if (!$user_achievement) {
        wp_send_json_error(array('message' => 'You have not earned this achievement'));
        return;
    }
    
    // Check if reward already claimed
    if ($user_achievement->reward_claimed) {
        wp_send_json_error(array('message' => 'Reward already claimed'));
        return;
    }
    
    // Get achievement details
    $achievement = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vortex_achievements
         WHERE id = %d",
        $achievement_id
    ));
    
    if (!$achievement) {
        wp_send_json_error(array('message' => 'Achievement not found'));
        return;
    }
    
    // Process blockchain reward if applicable
    $transaction_id = null;
    if ($achievement->blockchain_reward && $achievement->tola_reward_amount > 0) {
        $transaction_id = $this->issue_tola_reward(
            $user_id,
            $achievement_id,
            $achievement->tola_reward_amount,
            'achievement_claim'
        );
        
        if (!$transaction_id) {
            wp_send_json_error(array('message' => 'Failed to process TOLA reward'));
            return;
        }
    }
    
    // Mark reward as claimed
    $wpdb->update(
        $wpdb->prefix . 'vortex_user_achievements',
        array(
            'reward_claimed' => 1,
            'blockchain_transaction_id' => $transaction_id
        ),
        array(
            'user_id' => $user_id,
            'achievement_id' => $achievement_id
        ),
        array('%d', '%s'),
        array('%d', '%d')
    );
    
    wp_send_json_success(array(
        'message' => 'Reward claimed successfully',
        'tola_amount' => $achievement->tola_reward_amount,
        'transaction_id' => $transaction_id
    ));
}

/**
 * Add admin menu
 */
public function add_admin_menu() {
    add_submenu_page(
        'vortex-marketplace',
        __('Gamification', 'vortex-marketplace'),
        __('Gamification', 'vortex-marketplace'),
        'manage_options',
        'vortex-gamification',
        array($this, 'render_admin_page')
    );
}

/**
 * Render admin page
 */
public function render_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle form submissions
    if (isset($_POST['vortex_save_achievement']) && check_admin_referer('vortex_gamification_admin')) {
        $this->save_achievement_settings($_POST);
    }
    
    // Get achievements
    global $wpdb;
    $achievements = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vortex_achievements ORDER BY points ASC");
    
    // Get stats
    $stats = array(
        'total_points' => $wpdb->get_var("SELECT SUM(points) FROM {$wpdb->prefix}vortex_points_transactions"),
        'total_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}vortex_points_transactions"),
        'total_achievements' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_user_achievements"),
        'tola_rewards' => $wpdb->get_var("SELECT SUM(tola_reward_amount) FROM {$wpdb->prefix}vortex_achievements")
    );
    
    include(plugin_dir_path(dirname(dirname(__FILE__))) . 'admin/gamification-settings.php');
}

/**
 * Save achievement settings
 */
private function save_achievement_settings($data) {
    // Handle achievement updates or creation
    global $wpdb;
    
    $id = isset($data['achievement_id']) ? intval($data['achievement_id']) : 0;
    $title = isset($data['title']) ? sanitize_text_field($data['title']) : '';
    $description = isset($data['description']) ? sanitize_textarea_field($data['description']) : '';
    $points = isset($data['points']) ? intval($data['points']) : 0;
    $trigger_type = isset($data['trigger_type']) ? sanitize_text_field($data['trigger_type']) : '';
    $trigger_value = isset($data['trigger_value']) ? intval($data['trigger_value']) : 1;
    $blockchain_reward = isset($data['blockchain_reward']) ? 1 : 0;
    $tola_reward_amount = isset($data['tola_reward_amount']) ? floatval($data['tola_reward_amount']) : 0;
    
    // Upload badge image if provided
    $badge_url = isset($data['badge_url']) ? esc_url_raw($data['badge_url']) : '';
    
    if (!empty($_FILES['badge_image']['name'])) {
        $upload = wp_upload_bits($_FILES['badge_image']['name'], null, file_get_contents($_FILES['badge_image']['tmp_name']));
        
        if (isset($upload['url'])) {
            $badge_url = $upload['url'];
        }
    }
    
    $achievement_data = array(
        'title' => $title,
        'description' => $description,
        'points' => $points,
        'badge_url' => $badge_url,
        'trigger_type' => $trigger_type,
        'trigger_value' => $trigger_value,
        'blockchain_reward' => $blockchain_reward,
        'tola_reward_amount' => $tola_reward_amount
    );
    
    if ($id > 0) {
        // Update existing achievement
        $wpdb->update(
            $wpdb->prefix . 'vortex_achievements',
            $achievement_data,
            array('id' => $id),
            array('%s', '%s', '%d', '%s', '%s', '%d', '%d', '%f'),
            array('%d')
        );
        
        add_settings_error('vortex_gamification', 'achievement_updated', 'Achievement updated successfully', 'updated');
    } else {
        // Create new achievement
        $achievement_data['created_at'] = current_time('mysql');
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_achievements',
            $achievement_data,
            array('%s', '%s', '%d', '%s', '%s', '%d', '%d', '%f', '%s')
        );
        
        add_settings_error('vortex_gamification', 'achievement_created', 'Achievement created successfully', 'updated');
    }
}

/**
 * Award points for blockchain activities
 */
public function award_blockchain_points($user_id, $activity_type, $data) {
    $points = 0;
    
    switch ($activity_type) {
        case 'tokenize_artwork':
            $points = 50;
            break;
        case 'artwork_sold':
            $points = 25;
            break;
        case 'artwork_purchased':
            $points = 15;
            break;
        case 'dao_vote':
            $points = 10;
            break;
    }
    
    if ($points > 0) {
        $this->add_points($user_id, $points, $activity_type, $data);
        $this->check_level_up($user_id);
        
        // Feed activity to AI for learning
        $orchestrator = VORTEX_Orchestrator::get_instance();
        $orchestrator->submit_data_for_learning('user_activity', array(
            'user_id' => $user_id,
            'activity_type' => $activity_type,
            'points' => $points,
            'data' => $data
        ));
    }
} 