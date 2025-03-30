    /**
     * Find contrasting artists for creative inspiration
     *
     * @param int $user_id User ID
     * @param array $visual_analysis Visual analysis data
     * @return array Contrasting artists
     */
    private function find_contrasting_artists($user_id, $visual_analysis) {
        $artists = array();
        $all_artists = $this->artist_verification->get_all_artists();
        
        // Filter out current user
        $all_artists = array_filter($all_artists, function($artist_id) use ($user_id) {
            return $artist_id != $user_id;
        });
        
        if (empty($all_artists)) {
            return $artists;
        }
        
        // Shuffle artists for variety
        shuffle($all_artists);
        
        // Limit to 3 recommendations
        $limit = min(3, count($all_artists));
        $selected_artists = array_slice($all_artists, 0, $limit);
        
        // Generate AI recommendations
        foreach ($selected_artists as $artist_id) {
            $artists[] = array(
                'id' => $artist_id,
                'reason' => $this->generate_artist_recommendation_reason('huraii', $artist_id, $visual_analysis)
            );
        }
        
        return $artists;
    }
    
    /**
     * Find strategic partnerships based on market analysis
     *
     * @param int $user_id User ID
     * @param array $market_analysis Market analysis data
     * @return array Strategic artist partnerships
     */
    private function find_strategic_partnerships($user_id, $market_analysis) {
        $artists = array();
        $all_artists = $this->artist_verification->get_all_artists();
        
        // Filter out current user
        $all_artists = array_filter($all_artists, function($artist_id) use ($user_id) {
            return $artist_id != $user_id;
        });
        
        if (empty($all_artists)) {
            return $artists;
        }
        
        // Shuffle artists for variety
        shuffle($all_artists);
        
        // Limit to 3 recommendations
        $limit = min(3, count($all_artists));
        $selected_artists = array_slice($all_artists, 0, $limit);
        
        // Generate AI recommendations
        foreach ($selected_artists as $artist_id) {
            $artists[] = array(
                'id' => $artist_id,
                'reason' => $this->generate_artist_recommendation_reason('strategist', $artist_id, $market_analysis)
            );
        }
        
        return $artists;
    }
    
    /**
     * Find optimal artist matches considering all factors
     *
     * @param int $user_id User ID
     * @param array $holistic_analysis Holistic analysis data
     * @return array Optimal artist matches
     */
    private function find_optimal_artist_matches($user_id, $holistic_analysis) {
        $artists = array();
        $all_artists = $this->artist_verification->get_all_artists();
        
        // Filter out current user
        $all_artists = array_filter($all_artists, function($artist_id) use ($user_id) {
            return $artist_id != $user_id;
        });
        
        if (empty($all_artists)) {
            return $artists;
        }
        
        // Shuffle artists for variety
        shuffle($all_artists);
        
        // Limit to 3 recommendations
        $limit = min(3, count($all_artists));
        $selected_artists = array_slice($all_artists, 0, $limit);
        
        // Generate AI recommendations
        foreach ($selected_artists as $artist_id) {
            $artists[] = array(
                'id' => $artist_id,
                'reason' => $this->generate_artist_recommendation_reason('thorius', $artist_id, $holistic_analysis)
            );
        }
        
        return $artists;
    }
    
    /**
     * Generate artist recommendation reason based on AI agent
     *
     * @param string $agent AI agent ID
     * @param int $artist_id Artist ID
     * @param array $analysis Analysis data
     * @return string Recommendation reason
     */
    private function generate_artist_recommendation_reason($agent, $artist_id, $analysis) {
        $artist = get_userdata($artist_id);
        if (!$artist) {
            return '';
        }
        
        $thorius = Vortex_Thorius::get_instance();
        $artist_name = $artist->display_name;
        
        switch ($agent) {
            case 'cloe':
                // Analyze artistic compatibility
                $prompt = "As CLOE, generate a brief reason (30-40 words) why artist styles would complement each other. Artist's style includes " . 
                    implode(', ', array_slice($analysis['elements'], 0, 3)) . 
                    ". Focus on artistic compatibility with {$artist_name}.";
                break;
                
            case 'huraii':
                // Focus on creative inspiration
                $prompt = "As HURAII, generate a brief reason (30-40 words) why these artists should swap artwork. Artist's visual style includes " . 
                    implode(', ', array_slice($analysis['prominent_features'], 0, 3)) . 
                    ". Focus on creative inspiration potential with {$artist_name}.";
                break;
                
            case 'strategist':
                // Focus on market benefits
                $prompt = "As Business Strategist, generate a brief reason (30-40 words) for a strategic artist partnership. Artist's market segments include " . 
                    implode(', ', array_slice($analysis['market_segments'], 0, 2)) . 
                    ". Focus on mutual market benefits with {$artist_name}.";
                break;
                
            case 'thorius':
            default:
                // Holistic recommendation
                $prompt = "As Thorius, generate a brief reason (30-40 words) for why these artists should connect. Artist's key aspects include " . 
                    implode(', ', array_slice($analysis['key_aspects'], 0, 3)) . 
                    ". Provide a balanced perspective on compatibility with {$artist_name}.";
                break;
        }
        
        $context = array('agent' => $agent);
        $reason = $thorius->process_query($prompt, $context);
        
        // Limit length
        if (strlen($reason) > 150) {
            $reason = substr($reason, 0, 147) . '...';
        }
        
        return $reason;
    }
    
    /**
     * Find artwork matches based on style analysis
     *
     * @param int $user_id User ID
     * @param array $user_artworks User's artwork IDs
     * @param array $style_analysis Style analysis data
     * @return array Artwork matches
     */
    private function find_artwork_matches($user_id, $user_artworks, $style_analysis) {
        $matches = array();
        
        // Get all artists except current user
        $all_artists = $this->artist_verification->get_all_artists();
        $all_artists = array_filter($all_artists, function($artist_id) use ($user_id) {
            return $artist_id != $user_id;
        });
        
        if (empty($all_artists) || empty($user_artworks)) {
            return $matches;
        }
        
        // Get one random artwork from user
        $user_artwork = $user_artworks[array_rand($user_artworks)];
        
        // Get verified artworks from other artists
        $other_artworks = array();
        foreach ($all_artists as $artist_id) {
            $artist_artworks = $this->artwork_verification->get_verified_artworks($artist_id);
            
            if (!empty($artist_artworks)) {
                foreach ($artist_artworks as $artwork_id) {
                    // Skip artworks currently in a swap
                    $in_swap = get_post_meta($artwork_id, 'vortex_in_swap', true);
                    if (!$in_swap) {
                        $other_artworks[] = $artwork_id;
                    }
                }
            }
        }
        
        if (empty($other_artworks)) {
            return $matches;
        }
        
        // Shuffle artworks
        shuffle($other_artworks);
        
        // Get up to 2 matches
        $limit = min(2, count($other_artworks));
        $selected_artworks = array_slice($other_artworks, 0, $limit);
        
        // Get Thorius instance for generating match reasons
        $thorius = Vortex_Thorius::get_instance();
        
        foreach ($selected_artworks as $other_artwork) {
            // Generate match score (0.7-0.95 for demo)
            $match_score = (mt_rand(70, 95) / 100);
            
            // Generate match reason
            $user_artwork_title = get_the_title($user_artwork);
            $other_artwork_title = get_the_title($other_artwork);
            
            $prompt = "As CLOE, generate a brief reason (30-40 words) why these two artworks would make a good swap match: '{$user_artwork_title}' and '{$other_artwork_title}'. Focus on artistic complementarity and style elements like " . 
                implode(', ', array_slice($style_analysis['elements'], 0, 2)) . ".";
            
            $context = array('agent' => 'cloe');
            $reason = $thorius->process_query($prompt, $context);
            
            // Limit reason length
            if (strlen($reason) > 120) {
                $reason = substr($reason, 0, 117) . '...';
            }
            
            $matches[] = array(
                'your_artwork' => $user_artwork,
                'their_artwork' => $other_artwork,
                'score' => $match_score,
                'reason' => $reason
            );
        }
        
        return $matches;
    }
    
    /**
     * Find visual storytelling matches
     *
     * @param int $user_id User ID
     * @param array $user_artworks User's artwork IDs
     * @return array Visual storytelling matches
     */
    private function find_visual_storytelling_matches($user_id, $user_artworks) {
        $matches = array();
        
        // Similar logic to find_artwork_matches but with focus on visual storytelling
        // For brevity, using a simplified implementation here
        
        // Get all artists except current user
        $all_artists = $this->artist_verification->get_all_artists();
        $all_artists = array_filter($all_artists, function($artist_id) use ($user_id) {
            return $artist_id != $user_id;
        });
        
        if (empty($all_artists) || empty($user_artworks)) {
            return $matches;
        }
        
        // Get one random artwork from user
        $user_artwork = $user_artworks[array_rand($user_artworks)];
        
        // Get verified artworks from other artists
        $other_artworks = array();
        foreach ($all_artists as $artist_id) {
            $artist_artworks = $this->artwork_verification->get_verified_artworks($artist_id);
            
            if (!empty($artist_artworks)) {
                foreach ($artist_artworks as $artwork_id) {
                    // Skip artworks currently in a swap
                    $in_swap = get_post_meta($artwork_id, 'vortex_in_swap', true);
                    if (!$in_swap) {
                        $other_artworks[] = $artwork_id;
                    }
                }
            }
        }
        
        if (empty($other_artworks)) {
            return $matches;
        }
        
        // Shuffle artworks
        shuffle($other_artworks);
        
        // Get up to 2 matches
        $limit = min(2, count($other_artworks));
        $selected_artworks = array_slice($other_artworks, 0, $limit);
        
        // Get Thorius instance for generating match reasons
        $thorius = Vortex_Thorius::get_instance();
        
        foreach ($selected_artworks as $other_artwork) {
            // Generate match score (0.7-0.95 for demo)
            $match_score = (mt_rand(70, 95) / 100);
            
            // Generate match reason
            $user_artwork_title = get_the_title($user_artwork);
            $other_artwork_title = get_the_title($other_artwork);
            
            $prompt = "As HURAII, generate a brief reason (30-40 words) why these two artworks would create an interesting visual dialogue: '{$user_artwork_title}' and '{$other_artwork_title}'. Focus on creative contrast and storytelling potential.";
            
            $context = array('agent' => 'huraii');
            $reason = $thorius->process_query($prompt, $context);
            
            // Limit reason length
            if (strlen($reason) > 120) {
                $reason = substr($reason, 0, 117) . '...';
            }
            
            $matches[] = array(
                'your_artwork' => $user_artwork,
                'their_artwork' => $other_artwork,
                'score' => $match_score,
                'reason' => $reason
            );
        }
        
        return $matches;
    }
    
    /**
     * Find strategic exchanges based on market analysis
     *
     * @param int $user_id User ID
     * @param array $user_artworks User's artwork IDs
     * @param array $market_analysis Market analysis data
     * @return array Strategic exchanges
     */
    private function find_strategic_exchanges($user_id, $user_artworks, $market_analysis) {
        // Similar implementation to find_artwork_matches but with strategic focus
        // For brevity, using a simplified approach
        
        $matches = array();
        
        // Get all artists except current user
        $all_artists = $this->artist_verification->get_all_artists();
        $all_artists = array_filter($all_artists, function($artist_id) use ($user_id) {
            return $artist_id != $user_id;
        });
        
        if (empty($all_artists) || empty($user_artworks)) {
            return $matches;
        }
        
        // Get one random artwork from user
        $user_artwork = $user_artworks[array_rand($user_artworks)];
        
        // Get verified artworks from other artists
        $other_artworks = array();
        foreach ($all_artists as $artist_id) {
            $artist_artworks = $this->artwork_verification->get_verified_artworks($artist_id);
            
            if (!empty($artist_artworks)) {
                foreach ($artist_artworks as $artwork_id) {
                    // Skip artworks currently in a swap
                    $in_swap = get_post_meta($artwork_id, 'vortex_in_swap', true);
                    if (!$in_swap) {
                        $other_artworks[] = $artwork_id;
                    }
                }
            }
        }
        
        if (empty($other_artworks)) {
            return $matches;
        }
        
        // Shuffle artworks
        shuffle($other_artworks);
        
        // Get up to 2 matches
        $limit = min(2, count($other_artworks));
        $selected_artworks = array_slice($other_artworks, 0, $limit);
        
        // Get Thorius instance for generating match reasons
        $thorius = Vortex_Thorius::get_instance();
        
        foreach ($selected_artworks as $other_artwork) {
            // Generate match score (0.7-0.95 for demo)
            $match_score = (mt_rand(70, 95) / 100);
            
            // Generate match reason
            $user_artwork_title = get_the_title($user_artwork);
            $other_artwork_title = get_the_title($other_artwork);
            
            $prompt = "As Business Strategist, generate a brief reason (30-40 words) why exchanging these two artworks would be strategically beneficial: '{$user_artwork_title}' and '{$other_artwork_title}'. Focus on market positioning and audience growth in the " . 
                implode(' and ', array_slice($market_analysis['market_segments'], 0, 2)) . " segments.";
            
            $context = array('agent' => 'strategist');
            $reason = $thorius->process_query($prompt, $context);
            
            // Limit reason length
            if (strlen($reason) > 120) {
                $reason = substr($reason, 0, 117) . '...';
            }
            
            $matches[] = array(
                'your_artwork' => $user_artwork,
                'their_artwork' => $other_artwork,
                'score' => $match_score,
                'reason' => $reason
            );
        }
        
        return $matches;
    }
    
    /**
     * Find optimal artwork matches
     *
     * @param int $user_id User ID
     * @param array $user_artworks User's artwork IDs
     * @param array $holistic_analysis Holistic analysis data
     * @return array Optimal artwork matches
     */
    private function find_optimal_artwork_matches($user_id, $user_artworks, $holistic_analysis) {
        // Similar implementation to find_artwork_matches but with holistic focus
        // For brevity, using a simplified approach
        
        $matches = array();
        
        // Get all artists except current user
        $all_artists = $this->artist_verification->get_all_artists();
        $all_artists = array_filter($all_artists, function($artist_id) use ($user_id) {
            return $artist_id != $user_id;
        });
        
        if (empty($all_artists) || empty($user_artworks)) {
            return $matches;
        }
        
        // Get one random artwork from user
        $user_artwork = $user_artworks[array_rand($user_artworks)];
        
        // Get verified artworks from other artists
        $other_artworks = array();
        foreach ($all_artists as $artist_id) {
            $artist_artworks = $this->artwork_verification->get_verified_artworks($artist_id);
            
            if (!empty($artist_artworks)) {
                foreach ($artist_artworks as $artwork_id) {
                    // Skip artworks currently in a swap
                    $in_swap = get_post_meta($artwork_id, 'vortex_in_swap', true);
                    if (!$in_swap) {
                        $other_artworks[] = $artwork_id;
                    }
                }
            }
        }
        
        if (empty($other_artworks)) {
            return $matches;
        }
        
        // Shuffle artworks
        shuffle($other_artworks);
        
        // Get up to 2 matches
        $limit = min(2, count($other_artworks));
        $selected_artworks = array_slice($other_artworks, 0, $limit);
        
        // Get Thorius instance for generating match reasons
        $thorius = Vortex_Thorius::get_instance();
        
        foreach ($selected_artworks as $other_artwork) {
            // Generate match score (0.7-0.95 for demo)
            $match_score = (mt_rand(70, 95) / 100);
            
            // Generate match reason
            $user_artwork_title = get_the_title($user_artwork);
            $other_artwork_title = get_the_title($other_artwork);
            
            $prompt = "As Thorius, generate a brief reason (30-40 words) why these two artworks would be optimal to swap: '{$user_artwork_title}' and '{$other_artwork_title}'. Consider artistic style, creative potential, and market elements like " . 
                implode(', ', array_slice($holistic_analysis['key_aspects'], 0, 3)) . ".";
            
            $context = array('agent' => 'thorius');
            $reason = $thorius->process_query($prompt, $context);
            
            // Limit reason length
            if (strlen($reason) > 120) {
                $reason = substr($reason, 0, 117) . '...';
            }
            
            $matches[] = array(
                'your_artwork' => $user_artwork,
                'their_artwork' => $other_artwork,
                'score' => $match_score,
                'reason' => $reason
            );
        }
        
        return $matches;
    }
    
    /**
     * Get swap reward data
     *
     * @param int $swap_id Swap ID
     * @param int $user_id User ID
     * @return array|bool Reward data or false
     */
    private function get_swap_reward_data($swap_id, $user_id) {
        global $wpdb;
        
        $reward = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_tola_activities 
            WHERE user_id = %d 
            AND activity_type = 'artwork_swap' 
            AND activity_data LIKE %s",
            $user_id,
            '%"swap_id":' . $swap_id . '%'
        ), ARRAY_A);
        
        if (!$reward) {
            return false;
        }
        
        return array(
            'amount' => floatval($reward['reward_amount']),
            'timestamp' => $reward['created_at']
        );
    }
    
    /**
     * Get agent display name
     *
     * @param string $agent_id Agent ID
     * @return string Display name
     */
    private function get_agent_display_name($agent_id) {
        switch ($agent_id) {
            case 'cloe':
                return 'CLOE (Conversational Learning and Orchestration Engine)';
            case 'huraii':
                return 'HURAII (Human Understanding and Responsive AI Interface)';
            case 'strategist':
                return 'Business Strategist';
            case 'thorius':
                return 'Thorius AI Concierge';
            default:
                return ucfirst($agent_id);
        }
    }
    
    /**
     * Get blockchain explorer URL
     *
     * @param string $tx_hash Transaction hash
     * @return string Explorer URL
     */
    private function get_blockchain_explorer_url($tx_hash) {
        // Determine network
        $network = get_option('vortex_tola_network', 'ethereum');
        
        switch ($network) {
            case 'ethereum':
                return 'https://etherscan.io/tx/' . $tx_hash;
            case 'polygon':
                return 'https://polygonscan.com/tx/' . $tx_hash;
            case 'binance':
                return 'https://bscscan.com/tx/' . $tx_hash;
            default:
                return '#';
        }
    }
    
    /**
     * AJAX handler for initiating swap
     */
    public function ajax_initiate_swap() {
        // Check nonce
        check_ajax_referer('vortex_swap_nonce', 'nonce');
        
        // Get parameters
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        $target_artist = isset($_POST['target_artist']) ? intval($_POST['target_artist']) : 0;
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        
        // Get current user
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('You must be logged in to initiate a swap.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Initiate swap
        $result = $this->initiate_swap($user_id, $artwork_id, $target_artist, $message);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            exit;
        }
        
        wp_send_json_success(array(
            'message' => __('Swap offer initiated successfully!', 'vortex-ai-marketplace'),
            'swap_id' => $result
        ));
        exit;
    }
    
    /**
     * AJAX handler for responding to swap
     */
    public function ajax_respond_to_swap() {
        // Check nonce
        check_ajax_referer('vortex_swap_nonce', 'nonce');
        
        // Get parameters
        $swap_id = isset($_POST['swap_id']) ? intval($_POST['swap_id']) : 0;
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        
        // Get current user
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('You must be logged in to respond to a swap.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Respond to swap
        $result = $this->respond_to_swap($swap_id, $user_id, $artwork_id, $message);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            exit;
        }
        
        wp_send_json_success(array(
            'message' => __('Response submitted successfully!', 'vortex-ai-marketplace'),
            'swap_id' => $swap_id
        ));
        exit;
    }
    
    /**
     * AJAX handler for canceling swap
     */
    public function ajax_cancel_swap() {
        // Check nonce
        check_ajax_referer('vortex_swap_nonce', 'nonce');
        
        // Get parameters
        $swap_id = isset($_POST['swap_id']) ? intval($_POST['swap_id']) : 0;
        
        // Get current user
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('You must be logged in to cancel a swap.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Get swap details
        $swap = get_post($swap_id);
        
        if (!$swap || $swap->post_type !== 'vortex_artwork_swap') {
            wp_send_json_error(array('message' => __('Invalid swap.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Check if user is allowed to cancel
        $is_author = ($swap->post_author == $user_id);
        $is_responder = (get_post_meta($swap_id, 'vortex_responding_artist', true) == $user_id);
        
        if (!$is_author && !$is_responder) {
            wp_send_json_error(array('message' => __('You do not have permission to cancel this swap.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Get offered artwork
        $offered_artwork = get_post_meta($swap_id, 'vortex_offered_artwork', true);
        
        // Get counter artwork if exists
        $counter_artwork = get_post_meta($swap_id, 'vortex_counter_artwork', true);
        
        // Update swap status
        update_post_meta($swap_id, 'vortex_swap_status', 'cancelled');
        update_post_meta($swap_id, 'vortex_swap_cancelled', current_time('mysql'));
        update_post_meta($swap_id, 'vortex_cancelled_by', $user_id);
        
        // Clear the in-swap flag from artworks
        if ($offered_artwork) {
            delete_post_meta($offered_artwork, 'vortex_in_swap');
        }
        
        if ($counter_artwork) {
            delete_post_meta($counter_artwork, 'vortex_in_swap');
        }
        
        // Notify the other party
        if ($is_author) {
            $other_party = get_post_meta($swap_id, 'vortex_responding_artist', true);
            if ($other_party) {
                $this->notify_artist($other_party, 'swap_cancelled', $swap_id);
            }
        } else {
            $this->notify_artist($swap->post_author, 'swap_cancelled', $swap_id);
        }
        
        wp_send_json_success(array(
            'message' => __('Swap cancelled successfully.', 'vortex-ai-marketplace')
        ));
        exit;
    }
    
    /**
     * AJAX handler for completing swap
     */
    public function ajax_complete_swap() {
        // Check nonce
        check_ajax_referer('vortex_swap_nonce', 'nonce');
        
        // Get parameters
        $swap_id = isset($_POST['swap_id']) ? intval($_POST['swap_id']) : 0;
        
        // Get current user
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('You must be logged in to complete a swap.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Get swap details
        $swap = get_post($swap_id);
        
        if (!$swap || $swap->post_type !== 'vortex_artwork_swap') {
            wp_send_json_error(array('message' => __('Invalid swap.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Check if user is the original offerer
        if ($swap->post_author != $user_id) {
            wp_send_json_error(array('message' => __('Only the original offerer can complete the swap.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Complete the swap
        $result = $this->complete_swap($swap_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            exit;
        }
        
        wp_send_json_success(array(
            'message' => __('Swap completed successfully!', 'vortex-ai-marketplace'),
            'swap_id' => $swap_id
        ));
        exit;
    }
} 