<?php
/**
 * VORTEX Gamification System
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Engagement
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Gamification Class
 * 
 * Manages gamification elements and collector behavior tracking
 * with continuous AI learning integration.
 *
 * @since 1.0.0
 */
class VORTEX_Gamification {
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * Active AI agents for gamification
     */
    private $ai_agents = array();
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize AI agents
        $this->initialize_ai_agents();
        
        // Set up hooks
        $this->setup_hooks();
    }
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize AI agents for gamification
     */
    private function initialize_ai_agents() {
        // Initialize HURAII for visual feedback
        $this->ai_agents['HURAII'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'gamification',
            'capabilities' => array(
                'visual_engagement_tracking',
                'style_preference_learning',
                'collector_taste_profiling'
            )
        );
        
        // Initialize CLOE for collection curation
        $this->ai_agents['CLOE'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'collector_behavior',
            'capabilities' => array(
                'collection_pattern_analysis',
                'engagement_prediction',
                'recommendation_optimization'
            )
        );
        
        // Initialize BusinessStrategist for rewards strategy
        $this->ai_agents['BusinessStrategist'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'reward_strategy',
            'capabilities' => array(
                'reward_optimization',
                'pricing_strategy',
                'collector_incentive_analysis'
            )
        );
        
        // Initialize AI agents
        do_action('vortex_ai_agent_init', 'gamification', array_keys($this->ai_agents), 'active');
    }
    
    /**
     * Set up hooks
     */
    private function setup_hooks() {
        // Swipe action tracking
        add_action('wp_ajax_vortex_artwork_swipe', array($this, 'track_artwork_swipe'));
        add_action('wp_ajax_nopriv_vortex_artwork_swipe', array($this, 'track_artwork_swipe'));
        
        // Collector behavior tracking
        add_action('vortex_artwork_viewed', array($this, 'track_viewing_behavior'), 10, 2);
        add_action('vortex_artwork_saved', array($this, 'track_collection_behavior'), 10, 2);
        
        // Achievement tracking
        add_action('vortex_artwork_purchase', array($this, 'check_purchase_achievements'), 10, 3);
        add_action('vortex_artist_follow', array($this, 'check_social_achievements'), 10, 2);
        
        // Rewards system
        add_action('wp_ajax_vortex_claim_reward', array($this, 'process_reward_claim'));
        
        // Leveling system
        add_action('vortex_artwork_action', array($this, 'award_experience_points'), 10, 3);
        
        // Challenges system
        add_action('wp_ajax_vortex_start_challenge', array($this, 'start_user_challenge'));
        add_action('wp_ajax_vortex_complete_challenge', array($this, 'verify_challenge_completion'));
        
        // Leaderboard updates
        add_action('vortex_update_leaderboards', array($this, 'update_gamification_leaderboards'));
        
        // Token swap functionality
        add_action('wp_ajax_vortex_token_swap', array($this, 'process_token_swap'));
        
        // Schedule regular leaderboard updates
        if (!wp_next_scheduled('vortex_update_leaderboards')) {
            wp_schedule_event(time(), 'daily', 'vortex_update_leaderboards');
        }
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_gamification_scripts'));
    }
    
    /**
     * Enqueue gamification scripts
     */
    public function enqueue_gamification_scripts() {
        wp_enqueue_style('vortex-gamification-css', VORTEX_PLUGIN_URL . 'assets/css/gamification.css', array(), VORTEX_VERSION);
        wp_enqueue_script('vortex-swiper-js', VORTEX_PLUGIN_URL . 'assets/js/swiper.min.js', array('jquery'), '6.8.0', true);
        wp_enqueue_script('vortex-gamification-js', VORTEX_PLUGIN_URL . 'assets/js/gamification.js', array('jquery', 'vortex-swiper-js'), VORTEX_VERSION, true);
        
        // Localize script with user data and gamification state
        wp_localize_script('vortex-gamification-js', 'vortexGamification', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('vortex_gamification_nonce'),
            'userId' => get_current_user_id(),
            'userLevel' => $this->get_user_level(),
            'userPoints' => $this->get_user_points(),
            'userBadges' => $this->get_user_badges(),
            'activeChallenges' => $this->get_active_user_challenges(),
            'isLoggedIn' => is_user_logged_in(),
            'tolaEnabled' => $this->is_tola_enabled(),
            'swiperEnabled' => true,
            'i18n' => array(
                'liked' => __('Added to favorites', 'vortex-marketplace'),
                'disliked' => __('Removed from recommendations', 'vortex-marketplace'),
                'levelUp' => __('Level Up! You are now level', 'vortex-marketplace'),
                'pointsEarned' => __('You earned', 'vortex-marketplace'),
                'points' => __('points', 'vortex-marketplace'),
                'badgeEarned' => __('New Badge Earned:', 'vortex-marketplace'),
                'challengeComplete' => __('Challenge Completed!', 'vortex-marketplace'),
                'swipeToDiscover' => __('Swipe to discover art', 'vortex-marketplace')
            )
        ));
    }
    
    /**
     * Track artwork swipe
     * Handles the swipe left/right interactions for artwork discovery
     */
    public function track_artwork_swipe() {
        check_ajax_referer('vortex_gamification_nonce', 'security');
        
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        $direction = isset($_POST['direction']) ? sanitize_text_field($_POST['direction']) : '';
        $user_id = get_current_user_id();
        
        if (!$artwork_id || !in_array($direction, array('left', 'right'))) {
            wp_send_json_error(array('message' => __('Invalid data', 'vortex-marketplace')));
            return;
        }
        
        // Process swipe action
        $action_type = ($direction === 'right') ? 'like' : 'dislike';
        
        // Record swipe in database
        $this->record_swipe_action($artwork_id, $user_id, $action_type);
        
        // Send data to AI agents for learning
        $this->feed_swipe_data_to_ai($artwork_id, $user_id, $action_type);
        
        // Update user preferences
        $this->update_user_preferences($artwork_id, $user_id, $action_type);
        
        // Award points for engagement
        $points_earned = 0;
        if ($user_id) {
            $points_earned = $this->award_points($user_id, 'artwork_swipe', 1);
        }
        
        // Get next artworks for swipe deck
        $next_artworks = $this->get_next_swipe_artworks($user_id, 3);
        
        wp_send_json_success(array(
            'message' => ($action_type === 'like') ? __('Added to favorites', 'vortex-marketplace') : __('Removed from recommendations', 'vortex-marketplace'),
            'points_earned' => $points_earned,
            'next_artworks' => $next_artworks
        ));
    }
    
    /**
     * Record swipe action in database
     */
    private function record_swipe_action($artwork_id, $user_id, $action_type) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_user_interactions';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id ?: 0,
                'artwork_id' => $artwork_id,
                'interaction_type' => 'swipe_' . $action_type,
                'interaction_data' => json_encode(array(
                    'timestamp' => current_time('timestamp'),
                    'session_id' => isset($_COOKIE['vortex_session']) ? sanitize_text_field($_COOKIE['vortex_session']) : '',
                    'device_type' => wp_is_mobile() ? 'mobile' : 'desktop'
                )),
                'date_created' => current_time('mysql')
            )
        );
    }
    
    /**
     * Feed swipe data to AI agents for learning
     */
    private function feed_swipe_data_to_ai($artwork_id, $user_id, $action_type) {
        // Get artwork data
        $artwork = get_post($artwork_id);
        if (!$artwork) {
            return;
        }
        
        $artwork_data = array(
            'id' => $artwork->ID,
            'title' => $artwork->post_title,
            'artist_id' => get_post_meta($artwork->ID, 'vortex_artist_id', true),
            'category' => wp_get_post_terms($artwork->ID, 'vortex-artwork-category', array('fields' => 'names')),
            'style' => wp_get_post_terms($artwork->ID, 'vortex-artwork-style', array('fields' => 'names')),
            'price' => get_post_meta($artwork->ID, 'vortex_artwork_price', true),
            'seed_art_analysis' => get_post_meta($artwork->ID, 'vortex_seed_art_analysis', true)
        );
        
        // Prepare learning data
        $learning_data = array(
            'artwork' => $artwork_data,
            'user_id' => $user_id,
            'action' => $action_type,
            'timestamp' => current_time('timestamp')
        );
        
        // Send to HURAII for visual preference learning
        if ($this->ai_agents['HURAII']['active']) {
            do_action('vortex_ai_agent_learn', 'HURAII', 'swipe_interaction', $learning_data);
        }
        
        // Send to CLOE for collection pattern learning
        if ($this->ai_agents['CLOE']['active']) {
            do_action('vortex_ai_agent_learn', 'CLOE', 'swipe_interaction', $learning_data);
        }
        
        // Send to BusinessStrategist for market preference learning
        if ($this->ai_agents['BusinessStrategist']['active']) {
            do_action('vortex_ai_agent_learn', 'BusinessStrategist', 'swipe_interaction', $learning_data);
        }
    }
    
    /**
     * Update user preferences based on swipe
     */
    private function update_user_preferences($artwork_id, $user_id, $action_type) {
        if (!$user_id) {
            return;
        }
        
        // Get current preferences
        $preferences = get_user_meta($user_id, 'vortex_art_preferences', true);
        if (!is_array($preferences)) {
            $preferences = array(
                'liked_artworks' => array(),
                'disliked_artworks' => array(),
                'liked_categories' => array(),
                'disliked_categories' => array(),
                'liked_styles' => array(),
                'disliked_styles' => array(),
                'last_updated' => current_time('timestamp')
            );
        }
        
        // Update artwork lists
        if ($action_type === 'like') {
            if (!in_array($artwork_id, $preferences['liked_artworks'])) {
                $preferences['liked_artworks'][] = $artwork_id;
            }
            // Remove from disliked if it was there
            $preferences['disliked_artworks'] = array_diff($preferences['disliked_artworks'], array($artwork_id));
            
            // Update category and style preferences
            $this->update_category_preferences($preferences, $artwork_id, 'like');
            $this->update_style_preferences($preferences, $artwork_id, 'like');
        } else {
            if (!in_array($artwork_id, $preferences['disliked_artworks'])) {
                $preferences['disliked_artworks'][] = $artwork_id;
            }
            // Remove from liked if it was there
            $preferences['liked_artworks'] = array_diff($preferences['liked_artworks'], array($artwork_id));
            
            // Update category and style preferences
            $this->update_category_preferences($preferences, $artwork_id, 'dislike');
            $this->update_style_preferences($preferences, $artwork_id, 'dislike');
        }
        
        $preferences['last_updated'] = current_time('timestamp');
        
        // Save updated preferences
        update_user_meta($user_id, 'vortex_art_preferences', $preferences);
    }
    
    /**
     * Update category preferences
     */
    private function update_category_preferences(&$preferences, $artwork_id, $action_type) {
        $categories = wp_get_post_terms($artwork_id, 'vortex-artwork-category', array('fields' => 'ids'));
        
        if (!empty($categories)) {
            foreach ($categories as $category_id) {
                if ($action_type === 'like') {
                    // Add to liked categories counter
                    if (!isset($preferences['liked_categories'][$category_id])) {
                        $preferences['liked_categories'][$category_id] = 1;
                    } else {
                        $preferences['liked_categories'][$category_id]++;
                    }
                } else {
                    // Add to disliked categories counter
                    if (!isset($preferences['disliked_categories'][$category_id])) {
                        $preferences['disliked_categories'][$category_id] = 1;
                    } else {
                        $preferences['disliked_categories'][$category_id]++;
                    }
                }
            }
        }
    }
    
    /**
     * Update style preferences
     */
    private function update_style_preferences(&$preferences, $artwork_id, $action_type) {
        $styles = wp_get_post_terms($artwork_id, 'vortex-artwork-style', array('fields' => 'ids'));
        
        if (!empty($styles)) {
            foreach ($styles as $style_id) {
                if ($action_type === 'like') {
                    // Add to liked styles counter
                    if (!isset($preferences['liked_styles'][$style_id])) {
                        $preferences['liked_styles'][$style_id] = 1;
                    } else {
                        $preferences['liked_styles'][$style_id]++;
                    }
                } else {
                    // Add to disliked styles counter
                    if (!isset($preferences['disliked_styles'][$style_id])) {
                        $preferences['disliked_styles'][$style_id] = 1;
                    } else {
                        $preferences['disliked_styles'][$style_id]++;
                    }
                }
            }
        }
    }
    
    /**
     * Get next artworks for swipe deck
     */
    private function get_next_swipe_artworks($user_id, $count = 3) {
        // Get user preferences
        $preferences = array();
        if ($user_id) {
            $preferences = get_user_meta($user_id, 'vortex_art_preferences', true);
        }
        
        // Default arguments
        $args = array(
            'post_type' => 'vortex-artwork',
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'orderby' => 'rand'
        );
        
        // Exclude already interacted artworks
        if (!empty($preferences) && is_array($preferences)) {
            $exclude_ids = array_merge(
                $preferences['liked_artworks'] ?? array(),
                $preferences['disliked_artworks'] ?? array()
            );
            
            if (!empty($exclude_ids)) {
                $args['post__not_in'] = $exclude_ids;
            }
            
            // Prioritize categories/styles user likes
            if (!empty($preferences['liked_categories'])) {
                $liked_categories = array_keys($preferences['liked_categories']);
                $args['tax_query'][] = array(
                    'taxonomy' => 'vortex-artwork-category',
                    'field' => 'term_id',
                    'terms' => $liked_categories,
                    'operator' => 'IN'
                );
            }
            
            if (!empty($preferences['liked_styles'])) {
                $liked_styles = array_keys($preferences['liked_styles']);
                $args['tax_query'][] = array(
                    'taxonomy' => 'vortex-artwork-style',
                    'field' => 'term_id',
                    'terms' => $liked_styles,
                    'operator' => 'IN'
                );
            }
        }
        
        // Use AI agent recommendations if available
        $ai_recommendations = $this->get_ai_artwork_recommendations($user_id, $count);
        if (!empty($ai_recommendations)) {
            $args['post__in'] = $ai_recommendations;
            $args['orderby'] = 'post__in';
            unset($args['post__not_in']); // Clear exclusions to use AI recommendations directly
            unset($args['tax_query']); // Clear tax query to use AI recommendations directly
        }
        
        // Get artworks
        $artwork_query = new WP_Query($args);
        $artworks = array();
        
        if ($artwork_query->have_posts()) {
            while ($artwork_query->have_posts()) {
                $artwork_query->the_post();
                $artwork_id = get_the_ID();
                
                $artworks[] = array(
                    'id' => $artwork_id,
                    'title' => get_the_title(),
                    'permalink' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url($artwork_id, 'medium'),
                    'artist' => get_post_meta($artwork_id, 'vortex_artist_name', true),
                    'price' => get_post_meta($artwork_id, 'vortex_artwork_price', true),
                    'ai_enhanced' => !empty(get_post_meta($artwork_id, 'vortex_ai_enhanced', true)),
                    'categories' => wp_get_post_terms($artwork_id, 'vortex-artwork-category', array('fields' => 'names')),
                    'styles' => wp_get_post_terms($artwork_id, 'vortex-artwork-style', array('fields' => 'names'))
                );
            }
            wp_reset_postdata();
        }
        
        return $artworks;
    }
    
    /**
     * Get AI artwork recommendations
     */
    private function get_ai_artwork_recommendations($user_id, $count) {
        if (!$user_id) {
            return array();
        }
        
        $recommendations = array();
        
        // Get CLOE recommendations first
        if ($this->ai_agents['CLOE']['active'] && class_exists('VORTEX_CLOE')) {
            $cloe = VORTEX_CLOE::get_instance();
            if (method_exists($cloe, 'get_personalized_artwork_recommendations')) {
                $cloe_recommendations = $cloe->get_personalized_artwork_recommendations($user_id, $count);
                if (!empty($cloe_recommendations)) {
                    $recommendations = array_merge($recommendations, $cloe_recommendations);
                }
            }
        }
        
        // Add HURAII visual style recommendations if needed
        if (count($recommendations) < $count && $this->ai_agents['HURAII']['active'] && class_exists('VORTEX_HURAII')) {
            $huraii = VORTEX_HURAII::get_instance();
            if (method_exists($huraii, 'get_style_recommendations')) {
                $needed = $count - count($recommendations);
                $huraii_recommendations = $huraii->get_style_recommendations($user_id, $needed);
                if (!empty($huraii_recommendations)) {
                    $recommendations = array_merge($recommendations, $huraii_recommendations);
                }
            }
        }
        
        // Ensure no duplicates
        $recommendations = array_unique($recommendations);
        
        return array_slice($recommendations, 0, $count);
    }
    
    /**
     * Award points to user
     */
    public function award_points($user_id, $action_type, $points = 1) {
        if (!$user_id) {
            return 0;
        }
        
        // Get current points
        $current_points = get_user_meta($user_id, 'vortex_gamification_points', true);
        if (!is_numeric($current_points)) {
            $current_points = 0;
        }
        
        // Add points
        $new_points = $current_points + $points;
        update_user_meta($user_id, 'vortex_gamification_points', $new_points);
        
        // Track point history
        $points_history = get_user_meta($user_id, 'vortex_gamification_points_history', true);
        if (!is_array($points_history)) {
            $points_history = array();
        }
        $points_history[] = array(
            'action' => $action_type,
            'points' => $points,
            'timestamp' => current_time('timestamp')
        );
        update_user_meta($user_id, 'vortex_gamification_points_history', $points_history);
        
        return $new_points;
    }
} 