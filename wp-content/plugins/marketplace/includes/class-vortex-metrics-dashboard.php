class VORTEX_Metrics_Dashboard {
    public function get_realtime_tola_metrics() {
        $metrics = [
            'total_artworks' => $this->count_blockchain_artworks(),
            'top_artists' => $this->get_top_blockchain_artists(5),
            'popular_categories' => $this->get_popular_artwork_categories(5),
            'recent_transactions' => $this->get_recent_blockchain_transactions(10),
            'market_activity' => [
                'daily_volume' => $this->calculate_daily_volume(),
                'weekly_volume' => $this->calculate_weekly_volume(),
                'monthly_volume' => $this->calculate_monthly_volume()
            ],
            'trending_data' => [
                'trending_artists' => $this->identify_trending_artists(3),
                'trending_styles' => $this->identify_trending_styles(3),
                'price_movements' => $this->track_price_movements()
            ]
        ];
        
        return apply_filters('vortex_tola_metrics', $metrics);
    }
    
    private function count_blockchain_artworks() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'vortex_tola_contract_id' AND meta_value NOT LIKE 'pending_%'");
    }
    
    private function get_top_blockchain_artists($limit = 5) {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT p.post_author, COUNT(*) as artwork_count, u.display_name,
                   SUM(CAST(pm_price.meta_value AS DECIMAL(10,2))) as total_value
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm_contract ON p.ID = pm_contract.post_id
            JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id
            JOIN {$wpdb->users} u ON p.post_author = u.ID
            WHERE p.post_type = 'vortex_artwork'
            AND pm_contract.meta_key = 'vortex_tola_contract_id'
            AND pm_contract.meta_value NOT LIKE 'pending_%'
            AND pm_price.meta_key = 'vortex_artwork_price'
            GROUP BY p.post_author
            ORDER BY artwork_count DESC
            LIMIT %d", 
            $limit
        );
        
        return $wpdb->get_results($query);
    }
    
    private function get_popular_artwork_categories($limit = 5) {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT t.name, t.term_id, COUNT(*) as artwork_count
            FROM {$wpdb->term_relationships} tr
            JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            JOIN {$wpdb->posts} p ON tr.object_id = p.ID
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE tt.taxonomy = 'vortex_artwork_category'
            AND p.post_type = 'vortex_artwork'
            AND pm.meta_key = 'vortex_tola_contract_id'
            AND pm.meta_value NOT LIKE 'pending_%'
            GROUP BY t.term_id
            ORDER BY artwork_count DESC
            LIMIT %d",
            $limit
        );
        
        return $wpdb->get_results($query);
    }
    
    private function get_recent_blockchain_transactions($limit = 10) {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT t.*, p.post_title as artwork_title,
                   seller.display_name as seller_name,
                   buyer.display_name as buyer_name
            FROM {$wpdb->prefix}vortex_blockchain_transactions t
            JOIN {$wpdb->posts} p ON t.artwork_id = p.ID
            JOIN {$wpdb->users} seller ON t.seller_id = seller.ID
            JOIN {$wpdb->users} buyer ON t.buyer_id = buyer.ID
            ORDER BY t.transaction_date DESC
            LIMIT %d",
            $limit
        );
        
        return $wpdb->get_results($query);
    }
} 