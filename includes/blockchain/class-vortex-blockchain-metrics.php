/**
 * Get expanded blockchain metrics with detailed artist and category data
 * 
 * Provides comprehensive blockchain metrics including trending artists,
 * popular categories, and recent tokenized artworks.
 *
 * @return array Expanded blockchain metrics
 */
public function get_expanded_blockchain_metrics() {
    return array_merge($this->get_blockchain_metrics(), [
        'trending_artists' => $this->get_top_swapped_artists(10),
        'popular_categories' => $this->get_top_artwork_categories(10),
        'recent_tokens' => $this->get_recent_tokenized_artworks(5),
        'market_trends' => [
            'daily_volume' => $this->get_daily_trading_volume(),
            'weekly_growth' => $this->calculate_weekly_growth_rate(),
            'price_movements' => $this->get_price_movements()
        ],
        'timestamp' => current_time('timestamp'),
        'refresh_interval' => apply_filters('vortex_blockchain_metrics_refresh', 300) // 5-minute default refresh
    ]);
}

/**
 * Get recent tokenized artworks
 * 
 * @param int $limit Number of recent artworks to return
 * @return array Recent tokenized artworks
 */
private function get_recent_tokenized_artworks($limit = 5) {
    global $wpdb;
    
    $recent_tokens = $wpdb->get_results($wpdb->prepare(
        "SELECT a.id, a.title, a.artist_id, u.display_name as artist_name, 
                tk.token_id, tk.created_at
         FROM {$wpdb->prefix}vortex_artwork_tokens tk
         JOIN {$wpdb->prefix}vortex_artworks a ON tk.artwork_id = a.id
         JOIN {$wpdb->users} u ON a.artist_id = u.ID
         ORDER BY tk.created_at DESC
         LIMIT %d",
        $limit
    ), ARRAY_A);
    
    return $recent_tokens;
}

/**
 * Get daily trading volume
 * 
 * @return float Daily trading volume
 */
private function get_daily_trading_volume() {
    global $wpdb;
    
    $volume = $wpdb->get_var(
        "SELECT SUM(amount) 
         FROM {$wpdb->prefix}vortex_token_transfers
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)"
    );
    
    return floatval($volume ?: 0);
}

/**
 * Calculate weekly growth rate
 * 
 * @return float Weekly growth rate percentage
 */
private function calculate_weekly_growth_rate() {
    global $wpdb;
    
    // Get current week volume
    $current_week = $wpdb->get_var(
        "SELECT SUM(amount) 
         FROM {$wpdb->prefix}vortex_token_transfers
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );
    
    // Get previous week volume
    $previous_week = $wpdb->get_var(
        "SELECT SUM(amount) 
         FROM {$wpdb->prefix}vortex_token_transfers
         WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );
    
    // Calculate growth rate
    $current_week = floatval($current_week ?: 0);
    $previous_week = floatval($previous_week ?: 1); // Avoid division by zero
    
    return (($current_week - $previous_week) / $previous_week) * 100;
}

/**
 * Get price movements for tokenized artworks
 * 
 * @return array Price movement data
 */
private function get_price_movements() {
    global $wpdb;
    
    $movements = $wpdb->get_results(
        "SELECT a.id, a.title, 
                first_price.amount as initial_price,
                latest_price.amount as current_price,
                (latest_price.amount - first_price.amount) / first_price.amount * 100 as price_change
         FROM (
             SELECT tk.artwork_id, MIN(t.id) as first_id, MAX(t.id) as latest_id
             FROM {$wpdb->prefix}vortex_token_transfers t
             JOIN {$wpdb->prefix}vortex_artwork_tokens tk ON t.metadata LIKE CONCAT('%%', tk.token_id, '%%')
             WHERE t.transfer_type = 'sale'
             GROUP BY tk.artwork_id
             HAVING COUNT(t.id) > 1
         ) as price_points
         JOIN {$wpdb->prefix}vortex_artworks a ON price_points.artwork_id = a.id
         JOIN {$wpdb->prefix}vortex_token_transfers first_price ON price_points.first_id = first_price.id
         JOIN {$wpdb->prefix}vortex_token_transfers latest_price ON price_points.latest_id = latest_price.id
         ORDER BY ABS(price_change) DESC
         LIMIT 10"
    , ARRAY_A);
    
    return $movements;
} 