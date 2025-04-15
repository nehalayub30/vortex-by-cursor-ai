<?php
/**
 * VORTEX DAO WooCommerce Database Setup
 *
 * @package VORTEX
 */

class VORTEX_DAO_WooCommerce_DB {
    /**
     * Create database tables.
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Product rewards table
        $table_name = $wpdb->prefix . 'vortex_product_rewards';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            tola_amount float NOT NULL DEFAULT 0,
            claimed tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            claimed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY product_id (product_id),
            KEY order_id (order_id)
        ) $charset_collate;";
        
        // TOLA transactions table
        $table_transactions = $wpdb->prefix . 'vortex_tola_transactions';
        
        $sql .= "CREATE TABLE $table_transactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            amount float NOT NULL,
            type varchar(20) NOT NULL,
            metadata text DEFAULT NULL,
            created_at datetime NOT NULL,
            blockchain_ref varchar(255) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY type (type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Set default options
        if (get_option('vortex_dao_tola_reward_rate') === false) {
            update_option('vortex_dao_tola_reward_rate', 0.05); // 5% default
        }
        
        if (get_option('vortex_dao_min_tola_reward') === false) {
            update_option('vortex_dao_min_tola_reward', 1); // 1 TOLA minimum
        }
        
        // Flush rewrite rules on next load
        update_option('vortex_dao_flush_rewrite_rules', 'yes');
    }
    
    /**
     * Create sample achievements for WooCommerce.
     */
    public static function create_sample_achievements() {
        // Check if Achievements class exists
        if (!class_exists('VORTEX_Achievements')) {
            return;
        }
        
        $achievements_manager = VORTEX_Achievements::get_instance();
        
        // Sample achievements
        $sample_achievements = [
            [
                'title' => 'First Purchase',
                'description' => 'Completed your first purchase in our marketplace',
                'category' => 'purchase',
                'points' => 50,
                'icon' => 'shopping-cart',
                'rarity' => 'common'
            ],
            [
                'title' => 'Premium Collector',
                'description' => 'Purchased a premium marketplace item',
                'category' => 'purchase',
                'points' => 100,
                'icon' => 'star-filled',
                'rarity' => 'uncommon'
            ],
            [
                'title' => 'Big Spender',
                'description' => 'Made a purchase worth over $100',
                'category' => 'purchase',
                'points' => 150,
                'icon' => 'money-alt',
                'rarity' => 'rare'
            ],
            [
                'title' => 'Loyal Customer',
                'description' => 'Made 5+ purchases in our marketplace',
                'category' => 'purchase',
                'points' => 200,
                'icon' => 'heart',
                'rarity' => 'rare'
            ],
            [
                'title' => 'Whale Status',
                'description' => 'Lifetime spending over $1,000',
                'category' => 'purchase',
                'points' => 500,
                'icon' => 'superhero-alt',
                'rarity' => 'epic'
            ]
        ];
        
        // Create achievements
        $created_ids = [];
        
        foreach ($sample_achievements as $achievement) {
            $result = $achievements_manager->create_achievement($achievement);
            
            if ($result && !is_wp_error($result)) {
                $created_ids[$achievement['title']] = $result;
            }
        }
        
        // Set specific achievements for order totals and other metrics
        if (isset($created_ids['First Purchase'])) {
            update_option('vortex_dao_first_purchase_achievement', $created_ids['First Purchase']);
        }
        
        if (isset($created_ids['Big Spender'])) {
            $order_total_achievements = [
                100 => $created_ids['Big Spender']
            ];
            update_option('vortex_dao_order_total_achievements', $order_total_achievements);
        }
        
        if (isset($created_ids['Whale Status'])) {
            $lifetime_spend_achievements = [
                1000 => $created_ids['Whale Status']
            ];
            update_option('vortex_dao_lifetime_spend_achievements', $lifetime_spend_achievements);
        }
    }
} 