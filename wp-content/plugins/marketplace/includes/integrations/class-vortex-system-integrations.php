    /**
     * Update swap metrics
     */
    private function update_swap_metrics($artwork_id) {
        global $wpdb;
        
        // Update global swap counts
        $swap_count = intval(get_option('vortex_total_token_swaps', 0)) + 1;
        update_option('vortex_total_token_swaps', $swap_count);
        
        // Update artwork swap count
        $artwork_swap_count = intval(get_post_meta($artwork_id, 'vortex_swap_count', true)) + 1;
        update_post_meta($artwork_id, 'vortex_swap_count', $artwork_swap_count);
        
        // Update artist swap metrics
        $artist_id = get_post_meta($artwork_id, 'vortex_artist_id', true);
        if ($artist_id) {
            $artist_swap_count = intval(get_user_meta($artist_id, 'vortex_artist_swap_count', true)) + 1;
            update_user_meta($artist_id, 'vortex_artist_swap_count', $artist_swap_count);
        }
        
        // Update category swap metrics
        $categories = wp_get_post_terms($artwork_id, 'vortex_artwork_category', array('fields' => 'ids'));
        foreach ($categories as $category_id) {
            $term_meta = get_term_meta($category_id, 'vortex_swap_count', true);
            $category_swap_count = intval($term_meta) + 1;
            update_term_meta($category_id, 'vortex_swap_count', $category_swap_count);
        }
        
        // Update recent swap metrics in database for analytics
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}vortex_token_swap_metrics'") === $wpdb->prefix . 'vortex_token_swap_metrics') {
            $wpdb->insert(
                $wpdb->prefix . 'vortex_token_swap_metrics',
                array(
                    'artwork_id' => $artwork_id,
                    'artist_id' => $artist_id,
                    'recorded_at' => current_time('mysql'),
                    'categories' => implode(',', $categories)
                )
            );
        }
        
        // Trigger metrics update for real-time data
        do_action('vortex_blockchain_data_updated', array(
            'type' => 'swap_metrics',
            'artwork_id' => $artwork_id,
            'artist_id' => $artist_id,
            'categories' => $categories,
            'timestamp' => time()
        ));
    }
    
    /**
     * Update contract metrics
     */
    private function update_contract_metrics($contract_id, $contract_data) {
        global $wpdb;
        
        // Update global contract counts
        $contract_count = intval(get_option('vortex_total_smart_contracts', 0)) + 1;
        update_option('vortex_total_smart_contracts', $contract_count);
        
        // Update contract type counts
        $contract_type = $contract_data['type'];
        $type_count = intval(get_option("vortex_contract_type_{$contract_type}", 0)) + 1;
        update_option("vortex_contract_type_{$contract_type}", $type_count);
        
        // If artwork ID is available, update artwork contract metrics
        if (!empty($contract_data['artwork_id'])) {
            $artwork_id = $contract_data['artwork_id'];
            $artwork_contracts = get_post_meta($artwork_id, 'vortex_smart_contracts', true);
            
            if (!is_array($artwork_contracts)) {
                $artwork_contracts = array();
            }
            
            $artwork_contracts[] = $contract_id;
            update_post_meta($artwork_id, 'vortex_smart_contracts', $artwork_contracts);
            update_post_meta($artwork_id, 'vortex_has_smart_contracts', '1');
        }
        
        // If artist ID is available, update artist contract metrics
        if (!empty($contract_data['artist_id'])) {
            $artist_id = $contract_data['artist_id'];
            $artist_contracts = get_user_meta($artist_id, 'vortex_artist_contracts', true);
            
            if (!is_array($artist_contracts)) {
                $artist_contracts = array();
            }
            
            $artist_contracts[] = $contract_id;
            update_user_meta($artist_id, 'vortex_artist_contracts', $artist_contracts);
            
            // Update artist contract count
            $artist_contract_count = intval(get_user_meta($artist_id, 'vortex_artist_contract_count', true)) + 1;
            update_user_meta($artist_id, 'vortex_artist_contract_count', $artist_contract_count);
        }
        
        // Update contract metrics in database for analytics
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}vortex_smart_contract_metrics'") === $wpdb->prefix . 'vortex_smart_contract_metrics') {
            $wpdb->insert(
                $wpdb->prefix . 'vortex_smart_contract_metrics',
                array(
                    'contract_id' => $contract_id,
                    'contract_type' => $contract_type,
                    'artwork_id' => $contract_data['artwork_id'] ?? 0,
                    'artist_id' => $contract_data['artist_id'] ?? 0,
                    'contract_value' => $contract_data['value'] ?? 0,
                    'executed_at' => current_time('mysql')
                )
            );
        }
        
        // Trigger metrics update for real-time data
        do_action('vortex_blockchain_data_updated', array(
            'type' => 'contract_metrics',
            'contract_id' => $contract_id,
            'contract_type' => $contract_type,
            'contract_data' => $contract_data,
            'timestamp' => time()
        ));
    }
    
    /**
     * Update tokenization metrics
     */
    private function update_tokenization_metrics($artwork_id, $token_id) {
        global $wpdb;
        
        // Update global tokenization counts
        $token_count = intval(get_option('vortex_total_tokenized_artworks', 0)) + 1;
        update_option('vortex_total_tokenized_artworks', $token_count);
        
        // Mark artwork as tokenized
        update_post_meta($artwork_id, 'vortex_blockchain_tokenized', '1');
        update_post_meta($artwork_id, 'vortex_token_id', $token_id);
        update_post_meta($artwork_id, 'vortex_tokenized_date', current_time('mysql'));
        
        // Get artist ID and update artist tokenization metrics
        $artist_id = get_post_meta($artwork_id, 'vortex_artist_id', true);
        if ($artist_id) {
            $artist_token_count = intval(get_user_meta($artist_id, 'vortex_tokenized_artworks', true)) + 1;
            update_user_meta($artist_id, 'vortex_tokenized_artworks', $artist_token_count);
            
            // If first artwork tokenized, mark as blockchain artist
            if ($artist_token_count === 1) {
                update_user_meta($artist_id, 'vortex_blockchain_artist', '1');
            }
        }
        
        // Update category tokenization metrics
        $categories = wp_get_post_terms($artwork_id, 'vortex_artwork_category', array('fields' => 'ids'));
        foreach ($categories as $category_id) {
            $term_meta = get_term_meta($category_id, 'vortex_tokenized_count', true);
            $category_token_count = intval($term_meta) + 1;
            update_term_meta($category_id, 'vortex_tokenized_count', $category_token_count);
        }
        
        // Update tokenization metrics in database for analytics
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}vortex_tokenization_metrics'") === $wpdb->prefix . 'vortex_tokenization_metrics') {
            $wpdb->insert(
                $wpdb->prefix . 'vortex_tokenization_metrics',
                array(
                    'artwork_id' => $artwork_id,
                    'token_id' => $token_id,
                    'artist_id' => $artist_id,
                    'categories' => implode(',', $categories),
                    'tokenized_at' => current_time('mysql')
                )
            );
        }
        
        // Trigger metrics update for real-time data
        do_action('vortex_blockchain_data_updated', array(
            'type' => 'tokenization_metrics',
            'artwork_id' => $artwork_id,
            'token_id' => $token_id,
            'artist_id' => $artist_id,
            'categories' => $categories,
            'timestamp' => time()
        ));
    }
}

// Initialize the System Integrations
add_action('plugins_loaded', function() {
    VORTEX_System_Integrations::get_instance();
}); 