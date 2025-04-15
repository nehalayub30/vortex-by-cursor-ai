        // Achievements multiselect
        $achievements = $this->get_all_achievements();
        $selected_achievements = get_post_meta($post->ID, '_vortex_dao_achievements', true) ?: [];
        
        echo '<p class="form-field">';
        echo '<label for="_vortex_dao_achievements">' . __('Unlock Achievements', 'vortex') . '</label>';
        echo '<select id="_vortex_dao_achievements" name="_vortex_dao_achievements[]" multiple="multiple" class="wc-enhanced-select">';
        
        if (!empty($achievements)) {
            foreach ($achievements as $achievement) {
                echo '<option value="' . esc_attr($achievement->id) . '" ' . 
                     (in_array($achievement->id, $selected_achievements) ? 'selected="selected"' : '') . '>' . 
                     esc_html($achievement->title) . ' (' . esc_html($achievement->category) . ')</option>';
            }
        }
        
        echo '</select>';
        echo '<span class="description">' . __('Select achievements that will be unlocked when purchasing this product.', 'vortex') . '</span>';
        echo '</p>';
        
        // Featured DAO product toggle
        woocommerce_wp_checkbox([
            'id' => '_vortex_dao_featured_product',
            'label' => __('Featured DAO Product', 'vortex'),
            'description' => __('Feature this product in DAO rewards sections and dashboards.', 'vortex')
        ]);
        
        echo '</div>'; // Close options_group
    }
    
    /**
     * Save DAO reward product options.
     *
     * @param int $post_id Product ID.
     */
    public function save_dao_reward_product_options($post_id) {
        // Save TOLA reward
        if (isset($_POST['_vortex_dao_tola_reward'])) {
            $tola_reward = sanitize_text_field($_POST['_vortex_dao_tola_reward']);
            update_post_meta($post_id, '_vortex_dao_tola_reward', $tola_reward);
        }
        
        // Save achievements
        if (isset($_POST['_vortex_dao_achievements'])) {
            $achievements = array_map('intval', (array) $_POST['_vortex_dao_achievements']);
            update_post_meta($post_id, '_vortex_dao_achievements', $achievements);
        } else {
            update_post_meta($post_id, '_vortex_dao_achievements', []);
        }
        
        // Save featured DAO product
        $featured = isset($_POST['_vortex_dao_featured_product']) ? 'yes' : 'no';
        update_post_meta($post_id, '_vortex_dao_featured_product', $featured);
    }
    
    /**
     * AJAX handler for claiming product rewards.
     */
    public function ajax_claim_product_reward() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_dao_woocommerce_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check user
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to claim rewards.', 'vortex')]);
        }
        
        // Check if reward ID is provided
        if (!isset($_POST['reward_id'])) {
            wp_send_json_error(['message' => __('Invalid reward.', 'vortex')]);
        }
        
        $reward_id = intval($_POST['reward_id']);
        $user_id = get_current_user_id();
        
        // Get reward from database
        global $wpdb;
        $reward = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_product_rewards 
                WHERE id = %d AND user_id = %d",
                $reward_id, $user_id
            )
        );
        
        // Check if reward exists and belongs to user
        if (!$reward) {
            wp_send_json_error(['message' => __('Reward not found or does not belong to you.', 'vortex')]);
        }
        
        // Check if reward is already claimed
        if ($reward->claimed) {
            wp_send_json_error(['message' => __('This reward has already been claimed.', 'vortex')]);
        }
        
        // Process reward claim
        $tola_amount = floatval($reward->tola_amount);
        
        // Update user's TOLA balance
        $this->add_tola_to_user($user_id, $tola_amount, [
            'source' => 'product_reward',
            'product_id' => $reward->product_id,
            'order_id' => $reward->order_id
        ]);
        
        // Mark reward as claimed
        $wpdb->update(
            $wpdb->prefix . 'vortex_product_rewards',
            ['claimed' => 1, 'claimed_at' => current_time('mysql')],
            ['id' => $reward_id],
            ['%d', '%s'],
            ['%d']
        );
        
        // Trigger action for reward claimed
        do_action('vortex_dao_product_reward_claimed', $reward_id, $user_id, $tola_amount);
        
        // Return success
        wp_send_json_success([
            'message' => sprintf(__('Successfully claimed %s TOLA tokens!', 'vortex'), $tola_amount),
            'tola_amount' => $tola_amount
        ]);
    }
    
    /**
     * Add TOLA tokens to user balance.
     *
     * @param int $user_id User ID.
     * @param float $amount Amount of TOLA to add.
     * @param array $metadata Optional metadata about the transaction.
     * @return bool Success status.
     */
    private function add_tola_to_user($user_id, $amount, $metadata = []) {
        // Try blockchain integration first if available
        if (class_exists('VORTEX_Blockchain')) {
            $blockchain = VORTEX_Blockchain::get_instance();
            $result = $blockchain->add_tokens_to_user($user_id, $amount, $metadata);
            
            if ($result !== false) {
                return $result;
            }
        }
        
        // Fallback to simple user meta storage
        $current_balance = get_user_meta($user_id, 'vortex_dao_tola_balance', true);
        $current_balance = !empty($current_balance) ? floatval($current_balance) : 0;
        
        $new_balance = $current_balance + $amount;
        update_user_meta($user_id, 'vortex_dao_tola_balance', $new_balance);
        
        // Log transaction
        $this->log_tola_transaction($user_id, $amount, 'add', $metadata);
        
        return true;
    }
    
    /**
     * Log TOLA transaction.
     *
     * @param int $user_id User ID.
     * @param float $amount Amount of TOLA.
     * @param string $type Transaction type (add/subtract).
     * @param array $metadata Transaction metadata.
     */
    private function log_tola_transaction($user_id, $amount, $type, $metadata = []) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_tola_transactions',
            [
                'user_id' => $user_id,
                'amount' => $amount,
                'type' => $type,
                'metadata' => maybe_serialize($metadata),
                'created_at' => current_time('mysql')
            ],
            ['%d', '%f', '%s', '%s', '%s']
        );
    }
    
    /**
     * Get all achievements.
     *
     * @return array|object|null Array of achievements or null.
     */
    private function get_all_achievements() {
        if (!class_exists('VORTEX_Achievements')) {
            return [];
        }
        
        $achievements_manager = VORTEX_Achievements::get_instance();
        return $achievements_manager->get_all_achievements();
    }
}

// Initialize the class
VORTEX_DAO_WooCommerce::get_instance(); 