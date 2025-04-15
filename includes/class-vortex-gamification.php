<?php
/**
 * VORTEX Gamification Class
 *
 * Manages gamification elements like points, achievements, and leaderboards
 */

class VORTEX_Gamification {
    private $db;
    private $point_types = array();
    private $achievement_types = array();
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        
        // Register hooks
        add_action('init', array($this, 'register_achievement_post_type'));
        add_action('vortex_artwork_created', array($this, 'award_points_artwork_creation'), 10, 2);
        add_action('vortex_purchase_completed', array($this, 'award_points_purchase'), 10, 3);
        add_action('vortex_offer_made', array($this, 'award_points_offer'), 10, 3);
        add_action('vortex_proposal_created', array($this, 'award_points_governance'), 10, 2);
        add_action('vortex_proposal_vote_cast', array($this, 'award_points_voting'), 10, 4);
        
        // Init default point types
        $this->init_point_types();
        $this->init_achievement_types();
        
        // AJAX handlers
        add_action('wp_ajax_vortex_claim_achievement', array($this, 'ajax_claim_achievement'));
    }
    
    /**
     * Initialize default point types
     */
    private function init_point_types() {
        $this->point_types = array(
            'creation' => array(
                'name' => __('Creation Points', 'vortex-marketplace'),
                'description' => __('Earned by creating artwork', 'vortex-marketplace'),
                'icon' => 'dashicons-art'
            ),
            'transaction' => array(
                'name' => __('Transaction Points', 'vortex-marketplace'),
                'description' => __('Earned through marketplace transactions', 'vortex-marketplace'),
                'icon' => 'dashicons-money-alt'
            ),
            'social' => array(
                'name' => __('Social Points', 'vortex-marketplace'),
                'description' => __('Earned through social interactions', 'vortex-marketplace'),
                'icon' => 'dashicons-groups'
            ),
            'governance' => array(
                'name' => __('Governance Points', 'vortex-marketplace'),
                'description' => __('Earned through DAO participation', 'vortex-marketplace'),
                'icon' => 'dashicons-clipboard'
            )
        );
    }
    
    /**
     * Initialize achievement types
     */
    private function init_achievement_types() {
        $this->achievement_types = array(
            'creator' => array(
                'name' => __('Creator Achievements', 'vortex-marketplace'),
                'icon' => 'dashicons-art'
            ),
            'collector' => array(
                'name' => __('Collector Achievements', 'vortex-marketplace'),
                'icon' => 'dashicons-portfolio'
            ),
            'community' => array(
                'name' => __('Community Achievements', 'vortex-marketplace'),
                'icon' => 'dashicons-groups'
            ),
            'milestone' => array(
                'name' => __('Milestone Achievements', 'vortex-marketplace'),
                'icon' => 'dashicons-flag'
            )
        );
    }
    
    /**
     * Register achievement post type
     */
    public function register_achievement_post_type() {
        register_post_type('vortex_achievement', array(
            'labels' => array(
                'name' => __('Achievements', 'vortex-marketplace'),
                'singular_name' => __('Achievement', 'vortex-marketplace')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-awards',
            'show_in_rest' => true,
        ));
        
        // Register achievement category taxonomy
        register_taxonomy(
            'vortex_achievement_type',
            'vortex_achievement',
            array(
                'label' => __('Achievement Types', 'vortex-marketplace'),
                'hierarchical' => true,
                'show_in_rest' => true
            )
        );
    }
    
    /**
     * Award points to a user
     */
    public function award_points($user_id, $point_type, $points, $context = array()) {
        if (!array_key_exists($point_type, $this->point_types)) {
            return false;
        }
        
        if (!is_numeric($points) || $points <= 0) {
            return false;
        }
        
        // Get current points
        $current_points = $this->get_user_points($user_id, $point_type);
        $new_total = $current_points + $points;
        
        // Store context as JSON
        $context_json = !empty($context) ? json_encode($context) : '';
        
        // Insert point transaction
        $result = $this->db->insert(
            $this->db->prefix . 'vortex_user_points',
            array(
                'user_id' => $user_id,
                'point_type' => $point_type,
                'points' => $points,
                'context' => $context_json,
                'awarded_date' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s', '%s')
        );
        
        if (!$result) {
            return false;
        }
        
        // Update user's total points for this type
        update_user_meta($user_id, 'vortex_points_' . $point_type, $new_total);
        
        // Update total points
        $total_points = $this->get_user_total_points($user_id);
        update_user_meta($user_id, 'vortex_total_points', $total_points + $points);
        
        // Check for level up
        $this->check_for_level_up($user_id);
        
        // Check for achievements
        $this->check_for_achievements($user_id, $point_type, $new_total);
        
        // Fire action for other integrations
        do_action('vortex_points_awarded', $user_id, $point_type, $points, $new_total, $context);
        
        return true;
    }
    
    /**
     * Get user's points for a specific type
     */
    public function get_user_points($user_id, $point_type) {
        $points = get_user_meta($user_id, 'vortex_points_' . $point_type, true);
        return $points ? intval($points) : 0;
    }
    
    /**
     * Get user's total points across all types
     */
    public function get_user_total_points($user_id) {
        $total = get_user_meta($user_id, 'vortex_total_points', true);
        
        if ('' === $total) {
            // Calculate from individual types if not set
            $total = 0;
            foreach (array_keys($this->point_types) as $type) {
                $total += $this->get_user_points($user_id, $type);
            }
            update_user_meta($user_id, 'vortex_total_points', $total);
        }
        
        return intval($total);
    }
    
    /**
     * Get user's current level
     */
    public function get_user_level($user_id) {
        $level = get_user_meta($user_id, 'vortex_user_level', true);
        return $level ? intval($level) : 1;
    }
    
    /**
     * Check if user should level up
     */
    private function check_for_level_up($user_id) {
        $current_level = $this->get_user_level($user_id);
        $total_points = $this->get_user_total_points($user_id);
        
        // Get level thresholds from settings
        $level_thresholds = $this->get_level_thresholds();
        
        // Check if user should level up
        foreach ($level_thresholds as $level => $threshold) {
            if ($level > $current_level && $total_points >= $threshold) {
                // Update user level
                update_user_meta($user_id, 'vortex_user_level', $level);
                
                // Record level up timestamp
                update_user_meta($user_id, 'vortex_level_' . $level . '_achieved', current_time('timestamp'));
                
                // Fire level up action
                do_action('vortex_user_level_up', $user_id, $level, $current_level);
                
                return $level;
            }
        }
        
        return $current_level;
    }
    
    /**
     * Get level thresholds
     */
    private function get_level_thresholds() {
        $defaults = array(
            2 => 100,
            3 => 300,
            4 => 600,
            5 => 1000,
            6 => 1500,
            7 => 2200,
            8 => 3000,
            9 => 4000,
            10 => 5000
        );
        
        $thresholds = get_option('vortex_level_thresholds', $defaults);
        return $thresholds;
    }
    
    /**
     * Check for achievements based on points
     */
    private function check_for_achievements($user_id, $point_type, $points) {
        // Get achievements that are unlocked by this point type
        $achievements = $this->get_point_based_achievements($point_type);
        
        foreach ($achievements as $achievement) {
            $threshold = get_post_meta($achievement->ID, 'vortex_achievement_point_threshold', true);
            
            if ($points >= $threshold) {
                $this->unlock_achievement($user_id, $achievement->ID);
            }
        }
    }
    
    /**
     * Get achievements unlocked by a point type
     */
    private function get_point_based_achievements($point_type) {
        $args = array(
            'post_type' => 'vortex_achievement',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'vortex_achievement_point_type',
                    'value' => $point_type,
                    'compare' => '='
                ),
                array(
                    'key' => 'vortex_achievement_type',
                    'value' => 'point_threshold',
                    'compare' => '='
                )
            )
        );
        
        return get_posts($args);
    }
    
    /**
     * Unlock an achievement for a user
     */
    public function unlock_achievement($user_id, $achievement_id) {
        // Check if already unlocked
        if ($this->has_achievement($user_id, $achievement_id)) {
            return false;
        }
        
        // Get achievement data
        $achievement = get_post($achievement_id);
        if (!$achievement || $achievement->post_type !== 'vortex_achievement') {
            return false;
        }
        
        // Record the unlock
        $result = $this->db->insert(
            $this->db->prefix . 'vortex_user_achievements',
            array(
                'user_id' => $user_id,
                'achievement_id' => $achievement_id,
                'unlocked_date' => current_time('mysql'),
                'claimed' => 0
            ),
            array('%d', '%d', '%s', '%d')
        );
        
        if (!$result) {
            return false;
        }
        
        // Add to user's achievement count
        $count = get_user_meta($user_id, 'vortex_achievement_count', true);
        update_user_meta($user_id, 'vortex_achievement_count', intval($count) + 1);
        
        // Award points if configured
        $points = get_post_meta($achievement_id, 'vortex_achievement_points', true);
        if ($points) {
            $this->award_points($user_id, 'achievement', intval($points), array(
                'achievement_id' => $achievement_id,
                'achievement_title' => $achievement->post_title
            ));
        }
        
        // Fire action
        do_action('vortex_achievement_unlocked', $user_id, $achievement_id);
        
        return true;
    }
    
    /**
     * Check if user has an achievement
     */
    public function has_achievement($user_id, $achievement_id) {
        $count = $this->db->get_var($this->db->prepare(
            "SELECT COUNT(*) FROM {$this->db->prefix}vortex_user_achievements 
            WHERE user_id = %d AND achievement_id = %d",
            $user_id,
            $achievement_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Claim an achievement reward
     */
    public function claim_achievement($user_id, $achievement_id) {
        // Check if already claimed
        $record = $this->db->get_row($this->db->prepare(
            "SELECT * FROM {$this->db->prefix}vortex_user_achievements 
            WHERE user_id = %d AND achievement_id = %d",
            $user_id,
            $achievement_id
        ));
        
        if (!$record || $record->claimed) {
            return false;
        }
        
        // Update claimed status
        $result = $this->db->update(
            $this->db->prefix . 'vortex_user_achievements',
            array('claimed' => 1, 'claimed_date' => current_time('mysql')),
            array('user_id' => $user_id, 'achievement_id' => $achievement_id),
            array('%d', '%s'),
            array('%d', '%d')
        );
        
        if (!$result) {
            return false;
        }
        
        // Process rewards
        $reward_type = get_post_meta($achievement_id, 'vortex_achievement_reward_type', true);
        $reward_value = get_post_meta($achievement_id, 'vortex_achievement_reward_value', true);
        
        if ($reward_type && $reward_value) {
            switch ($reward_type) {
                case 'points':
                    $this->award_points($user_id, 'reward', intval($reward_value), array(
                        'achievement_id' => $achievement_id
                    ));
                    break;
                    
                case 'badge':
                    update_user_meta($user_id, 'vortex_badge_' . $reward_value, '1');
                    break;
                    
                case 'discount':
                    // Generate discount code
                    $code = $this->generate_discount_code($user_id, $reward_value);
                    update_user_meta($user_id, 'vortex_discount_code', $code);
                    break;
            }
        }
        
        // Fire action
        do_action('vortex_achievement_claimed', $user_id, $achievement_id, $reward_type, $reward_value);
        
        return true;
    }
    
    /**
     * Generate a discount code
     */
    private function generate_discount_code($user_id, $discount_value) {
        $code = 'VORTEX' . $user_id . strtoupper(substr(md5(time()), 0, 6));
        
        // Store the code in options
        $discounts = get_option('vortex_discount_codes', array());
        $discounts[$code] = array(
            'user_id' => $user_id,
            'value' => $discount_value,
            'created' => current_time('mysql'),
            'used' => false
        );
        
        update_option('vortex_discount_codes', $discounts);
        
        return $code;
    }
    
    /**
     * Get leaderboard data
     */
    public function get_leaderboard($limit = 10, $period = 'all_time') {
        $cache_key = 'vortex_leaderboard_' . $period . '_' . $limit;
        $leaderboard = wp_cache_get($cache_key);
        
        if (false === $leaderboard) {
            global $wpdb;
            
            switch ($period) {
                case 'weekly':
                    $date_filter = "AND awarded_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                    break;
                case 'monthly':
                    $date_filter = "AND awarded_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                    break;
                default:
                    $date_filter = "";
            }
            
            $query = $wpdb->prepare(
                "SELECT user_id, SUM(points) as total_points
                FROM {$wpdb->prefix}vortex_user_points
                WHERE 1=1 {$date_filter}
                GROUP BY user_id
                ORDER BY total_points DESC
                LIMIT %d",
                $limit
            );
            
            $results = $wpdb->get_results($query);
            
            $leaderboard = array();
            foreach ($results as $index => $result) {
                $user = get_userdata($result->user_id);
                if ($user) {
                    $leaderboard[] = array(
                        'rank' => $index + 1,
                        'user_id' => $result->user_id,
                        'display_name' => $user->display_name,
                        'avatar' => get_avatar_url($result->user_id),
                        'points' => $result->total_points,
                        'level' => $this->get_user_level($result->user_id)
                    );
                }
            }
            
            wp_cache_set($cache_key, $leaderboard, '', 3600); // Cache for 1 hour
        }
        
        return $leaderboard;
    }
    
    /**
     * Award points for artwork creation
     */
    public function award_points_artwork_creation($artwork_id, $user_id) {
        $points = get_option('vortex_points_artwork_creation', 10);
        
        return $this->award_points($user_id, 'creation', $points, array(
            'artwork_id' => $artwork_id,
            'action' => 'creation'
        ));
    }
    
    /**
     * Award points for purchase
     */
    public function award_points_purchase($purchase_id, $buyer_id, $artwork_id) {
        // Buyer points
        $buyer_points = get_option('vortex_points_purchase', 5);
        $this->award_points($buyer_id, 'transaction', $buyer_points, array(
            'purchase_id' => $purchase_id,
            'artwork_id' => $artwork_id,
            'action' => 'purchase'
        ));
        
        // Seller points
        $seller_id = get_post_field('post_author', $artwork_id);
        $seller_points = get_option('vortex_points_sale', 3);
        
        return $this->award_points($seller_id, 'transaction', $seller_points, array(
            'purchase_id' => $purchase_id,
            'artwork_id' => $artwork_id,
            'action' => 'sale'
        ));
    }
    
    /**
     * Award points for making an offer
     */
    public function award_points_offer($offer_id, $buyer_id, $artwork_id) {
        $points = get_option('vortex_points_offer', 2);
        
        return $this->award_points($buyer_id, 'social', $points, array(
            'offer_id' => $offer_id,
            'artwork_id' => $artwork_id,
            'action' => 'offer'
        ));
    }
    
    /**
     * Award points for governance activities
     */
    public function award_points_governance($proposal_id, $user_id) {
        $points = get_option('vortex_points_proposal', 15);
        
        return $this->award_points($user_id, 'governance', $points, array(
            'proposal_id' => $proposal_id,
            'action' => 'proposal_creation'
        ));
    }
    
    /**
     * Award points for voting
     */
    public function award_points_voting($proposal_id, $user_id, $vote, $voting_power) {
        $points = get_option('vortex_points_vote', 5);
        
        return $this->award_points($user_id, 'governance', $points, array(
            'proposal_id' => $proposal_id,
            'vote' => $vote,
            'action' => 'voting'
        ));
    }
    
    /**
     * AJAX handler for claiming achievements
     */
    public function ajax_claim_achievement() {
        check_ajax_referer('vortex_gamification_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to claim achievements', 'vortex-marketplace')));
            return;
        }
        
        $user_id = get_current_user_id();
        $achievement_id = intval($_POST['achievement_id']);
        
        $result = $this->claim_achievement($user_id, $achievement_id);
        
        if ($result) {
            $reward_type = get_post_meta($achievement_id, 'vortex_achievement_reward_type', true);
            $reward_value = get_post_meta($achievement_id, 'vortex_achievement_reward_value', true);
            
            wp_send_json_success(array(
                'message' => __('Achievement claimed successfully!', 'vortex-marketplace'),
                'reward_type' => $reward_type,
                'reward_value' => $reward_value
            ));
        } else {
            wp_send_json_error(array('message' => __('Unable to claim achievement', 'vortex-marketplace')));
        }
    }
    
    /**
     * Install database tables
     */
    public static function install() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = array();
        
        // Points table
        $sql[] = "CREATE TABLE {$wpdb->prefix}vortex_user_points (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            point_type varchar(20) NOT NULL,
            points int(11) NOT NULL,
            context text,
            awarded_date datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY point_type (point_type),
            KEY awarded_date (awarded_date)
        ) $charset_collate;";
        
        // Achievements table
        $sql[] = "CREATE TABLE {$wpdb->prefix}vortex_user_achievements (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            achievement_id bigint(20) NOT NULL,
            unlocked_date datetime NOT NULL,
            claimed tinyint(1) NOT NULL DEFAULT 0,
            claimed_date datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY achievement_id (achievement_id),
            UNIQUE KEY user_achievement (user_id,achievement_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }
} 