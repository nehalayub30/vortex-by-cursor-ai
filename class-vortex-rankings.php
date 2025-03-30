<?php
/**
 * VORTEX Rankings System
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Analytics
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Rankings Class
 * 
 * Manages marketplace rankings with AI-enhanced algorithms.
 * Integrates with AI agents for continuous learning and improvement.
 *
 * @since 1.0.0
 */
class VORTEX_Rankings {
    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var object
     */
    protected static $instance = null;
    
    /**
     * Active AI agent instances
     *
     * @since 1.0.0
     * @var array
     */
    private $ai_agents = array();
    
    /**
     * Rankings cache
     *
     * @since 1.0.0
     * @var array
     */
    private $rankings_cache = array();
    
    /**
     * Cache expiration (in seconds)
     *
     * @since 1.0.0
     * @var int
     */
    private $cache_expiration = 3600; // 1 hour
    
    /**
     * Ranking weight configurations
     *
     * @since 1.0.0
     * @var array
     */
    private $ranking_weights = array();
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Initialize AI agents
        $this->initialize_ai_agents();
        
        // Initialize ranking weights
        $this->initialize_ranking_weights();
        
        // Set up hooks
        $this->setup_hooks();
    }
    
    /**
     * Get instance of this class.
     *
     * @since 1.0.0
     * @return VORTEX_Rankings
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize AI agents for rankings processing
     *
     * @since 1.0.0
     * @return void
     */
    private function initialize_ai_agents() {
        // Initialize HURAII for visual quality and style rankings
        $this->ai_agents['HURAII'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'rankings_processing',
            'capabilities' => array(
                'visual_quality_assessment',
                'style_consistency_evaluation',
                'seed_art_technique_mastery'
            )
        );
        
        // Initialize CLOE for curation and discovery rankings
        $this->ai_agents['CLOE'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'curation_rankings',
            'capabilities' => array(
                'engagement_assessment',
                'collection_cohesion_evaluation',
                'audience_resonance_analysis'
            )
        );
        
        // Initialize BusinessStrategist for market and economic rankings
        $this->ai_agents['BusinessStrategist'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'market_rankings',
            'capabilities' => array(
                'sales_performance_evaluation',
                'price_optimization_assessment',
                'market_value_projection'
            )
        );
        
        // Initialize AI agents via action
        do_action('vortex_ai_agent_init', 'rankings_processing', array_keys($this->ai_agents), 'active');
    }
    
    /**
     * Initialize ranking weights
     *
     * @since 1.0.0
     * @return void
     */
    private function initialize_ranking_weights() {
        // Default weights for artwork rankings
        $this->ranking_weights['artwork'] = array(
            'views' => 20,
            'sales' => 25,
            'revenue' => 15,
            'seed_art_quality' => 15,
            'artistic_innovation' => 10,
            'engagement' => 10,
            'recency' => 5
        );
        
        // Default weights for artist rankings
        $this->ranking_weights['artist'] = array(
            'artwork_count' => 10,
            'total_views' => 15,
            'total_sales' => 20,
            'total_revenue' => 20,
            'avg_artwork_quality' => 15,
            'artistic_growth' => 10,
            'marketplace_engagement' => 10
        );
        
        // Default weights for collection rankings
        $this->ranking_weights['collection'] = array(
            'artwork_count' => 15,
            'collection_views' => 20,
            'total_sales' => 25,
            'collection_cohesion' => 20,
            'artistic_diversity' => 10,
            'curator_reputation' => 10
        );
        
        // Allow filtering of ranking weights
        $this->ranking_weights = apply_filters('vortex_ranking_weights', $this->ranking_weights);
    }
    
    /**
     * Set up hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function setup_hooks() {
        // Scheduled rankings generation
        add_action('vortex_daily_rankings_update', array($this, 'generate_all_rankings'));
        
        // AJAX handlers
        add_action('wp_ajax_vortex_get_rankings', array($this, 'ajax_get_rankings'));
        add_action('wp_ajax_nopriv_vortex_get_rankings', array($this, 'ajax_get_rankings'));
        
        // Rankings display shortcodes
        add_shortcode('vortex_top_artworks', array($this, 'shortcode_top_artworks'));
        add_shortcode('vortex_top_artists', array($this, 'shortcode_top_artists'));
        add_shortcode('vortex_trending_artworks', array($this, 'shortcode_trending_artworks'));
        add_shortcode('vortex_sales_leaderboard', array($this, 'shortcode_sales_leaderboard'));
        
        // Set up scheduled events if not already
        if (!wp_next_scheduled('vortex_daily_rankings_update')) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_rankings_update');
        }
        
        // Update rankings when certain events occur
        add_action('vortex_artwork_sale', array($this, 'schedule_rankings_update'), 10, 4);
        add_action('vortex_artwork_published', array($this, 'schedule_rankings_update'), 10, 1);
        add_action('vortex_artist_profile_updated', array($this, 'schedule_rankings_update'), 10, 1);
    }
    
    /**
     * AJAX handler for getting rankings
     *
     * @since 1.0.0
     * @return void
     */
    public function ajax_get_rankings() {
        check_ajax_referer('vortex_rankings_nonce', 'nonce');
        
        $ranking_type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'top_artworks';
        $count = isset($_POST['count']) ? intval($_POST['count']) : 10;
        $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '30days';
        
        $rankings = array();
        
        switch ($ranking_type) {
            case 'top_artworks':
                $rankings = $this->get_top_artworks($count, $category, $period);
                break;
            case 'top_artists':
                $rankings = $this->get_top_artists($count, $category, $period);
                break;
            case 'trending_artworks':
                $rankings = $this->get_trending_artworks($count, $category);
                break;
            case 'sales_leaderboard':
                $rankings = $this->get_sales_leaderboard($count, $category, $period);
                break;
            default:
                wp_send_json_error(array('message' => __('Invalid ranking type', 'vortex-marketplace')));
        }
        
        wp_send_json_success($rankings);
    }
    
    /**
     * Shortcode handler for top artworks
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function shortcode_top_artworks($atts) {
        $atts = shortcode_atts(array(
            'count' => 6,
            'category' => 0,
            'period' => '30days',
            'title' => __('Top Artworks', 'vortex-marketplace'),
            'columns' => 3,
            'show_rank' => true,
            'show_artist' => true,
            'show_price' => true
        ), $atts, 'vortex_top_artworks');
        
        $count = intval($atts['count']);
        $category = intval($atts['category']);
        $period = sanitize_text_field($atts['period']);
        $columns = intval($atts['columns']);
        $show_rank = filter_var($atts['show_rank'], FILTER_VALIDATE_BOOLEAN);
        $show_artist = filter_var($atts['show_artist'], FILTER_VALIDATE_BOOLEAN);
        $show_price = filter_var($atts['show_price'], FILTER_VALIDATE_BOOLEAN);
        
        // Get rankings
        $rankings = $this->get_top_artworks($count, $category, $period);
        
        // Start output buffer
        ob_start();
        
        // Include template
        include(VORTEX_PLUGIN_PATH . 'templates/rankings/top-artworks.php');
        
        // Return output
        return ob_get_clean();
    }
    
    /**
     * Shortcode handler for top artists
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function shortcode_top_artists($atts) {
        $atts = shortcode_atts(array(
            'count' => 6,
            'category' => 0,
            'period' => '30days',
            'title' => __('Top Artists', 'vortex-marketplace'),
            'columns' => 3,
            'show_rank' => true,
            'show_sales' => true
        ), $atts, 'vortex_top_artists');
        
        $count = intval($atts['count']);
        $category = intval($atts['category']);
        $period = sanitize_text_field($atts['period']);
        $columns = intval($atts['columns']);
        $show_rank = filter_var($atts['show_rank'], FILTER_VALIDATE_BOOLEAN);
        $show_sales = filter_var($atts['show_sales'], FILTER_VALIDATE_BOOLEAN);
        
        // Get rankings
        $rankings = $this->get_top_artists($count, $category, $period);
        
        // Start output buffer
        ob_start();
        
        // Include template
        include(VORTEX_PLUGIN_PATH . 'templates/rankings/top-artists.php');
        
        // Return output
        return ob_get_clean();
    }
    
    /**
     * Shortcode handler for trending artworks
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function shortcode_trending_artworks($atts) {
        $atts = shortcode_atts(array(
            'count' => 6,
            'category' => 0,
            'title' => __('Trending Artworks', 'vortex-marketplace'),
            'columns' => 3,
            'show_rank' => true,
            'show_artist' => true,
            'show_trend_score' => true
        ), $atts, 'vortex_trending_artworks');
        
        $count = intval($atts['count']);
        $category = intval($atts['category']);
        $columns = intval($atts['columns']);
        $show_rank = filter_var($atts['show_rank'], FILTER_VALIDATE_BOOLEAN);
        $show_artist = filter_var($atts['show_artist'], FILTER_VALIDATE_BOOLEAN);
        $show_trend_score = filter_var($atts['show_trend_score'], FILTER_VALIDATE_BOOLEAN);
        
        // Get rankings
        $rankings = $this->get_trending_artworks($count, $category);
        
        // Start output buffer
        ob_start();
        
        // Include template
        include(VORTEX_PLUGIN_PATH . 'templates/rankings/trending-artworks.php');
        
        // Return output
        return ob_get_clean();
    }
    
    /**
     * Shortcode handler for sales leaderboard
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function shortcode_sales_leaderboard($atts) {
        $atts = shortcode_atts(array(
            'count' => 6,
            'category' => 0,
            'period' => '30days',
            'title' => __('Sales Leaderboard', 'vortex-marketplace'),
            'columns' => 3,
            'show_rank' => true,
            'show_artist' => true,
            'show_sales' => true,
            'show_revenue' => true
        ), $atts, 'vortex_sales_leaderboard');
        
        $count = intval($atts['count']);
        $category = intval($atts['category']);
        $period = sanitize_text_field($atts['period']);
        $columns = intval($atts['columns']);
        $show_rank = filter_var($atts['show_rank'], FILTER_VALIDATE_BOOLEAN);
        $show_artist = filter_var($atts['show_artist'], FILTER_VALIDATE_BOOLEAN);
        $show_sales = filter_var($atts['show_sales'], FILTER_VALIDATE_BOOLEAN);
        $show_revenue = filter_var($atts['show_revenue'], FILTER_VALIDATE_BOOLEAN);
        
        // Get rankings
        $rankings = $this->get_sales_leaderboard($count, $category, $period);
        
        // Start output buffer
        ob_start();
        
        // Include template
        include(VORTEX_PLUGIN_PATH . 'templates/rankings/sales-leaderboard.php');
        
        // Return output
        return ob_get_clean();
    }
    
    /**
     * Get top artworks
     *
     * @since 1.0.0
     * @param int $count Number of artworks to return
     * @param int $category Category ID (0 for all)
     * @param string $period Period ('7days', '30days', '90days', 'alltime')
     * @return array Top artworks
     */
    public function get_top_artworks($count = 10, $category = 0, $period = '30days') {
        // Check cache first
        $cache_key = md5("top_artworks_{$count}_{$category}_{$period}");
        if (isset($this->rankings_cache[$cache_key]) && $this->rankings_cache[$cache_key]['expires'] > time()) {
            return $this->rankings_cache[$cache_key]['data'];
        }
        
        // Get days based on period
        $days = $this->get_days_from_period($period);
        
        // Get metrics system
        $metrics = VORTEX_Metrics::get_instance();
        
        // Get artworks with metrics data
        $artworks = $this->get_artworks_with_metrics($count * 3, $category, $days);
        
        if (empty($artworks)) {
            return array();
        }
        
        // Apply ranking algorithm
        $ranked_artworks = $this->apply_artwork_ranking_algorithm($artworks, $period);
        
        // Apply AI enhancements
        $ranked_artworks = $this->apply_ai_enhancements_to_artworks($ranked_artworks);
        
        // Limit to requested count
        $ranked_artworks = array_slice($ranked_artworks, 0, $count);
        
        // Cache the results
        $this->rankings_cache[$cache_key] = array(
            'data' => $ranked_artworks,
            'expires' => time() + $this->cache_expiration
        );
        
        return $ranked_artworks;
    }
    
    /**
     * Get top artists
     *
     * @since 1.0.0
     * @param int $count Number of artists to return
     * @param int $category Category ID (0 for all)
     * @param string $period Period ('7days', '30days', '90days', 'alltime')
     * @return array Top artists
     */
    public function get_top_artists($count = 10, $category = 0, $period = '30days') {
        // Check cache first
        $cache_key = md5("top_artists_{$count}_{$category}_{$period}");
        if (isset($this->rankings_cache[$cache_key]) && $this->rankings_cache[$cache_key]['expires'] > time()) {
            return $this->rankings_cache[$cache_key]['data'];
        }
        
        // Get days based on period
        $days = $this->get_days_from_period($period);
        
        // Get metrics system
        $metrics = VORTEX_Metrics::get_instance();
        
        // Get artists with metrics data
        $artists = $this->get_artists_with_metrics($count * 3, $category, $days);
        
        if (empty($artists)) {
            return array();
        }
        
        // Apply ranking algorithm
        $ranked_artists = $this->apply_artist_ranking_algorithm($artists, $period);
        
        // Apply AI enhancements
        $ranked_artists = $this->apply_ai_enhancements_to_artists($ranked_artists);
        
        // Limit to requested count
        $ranked_artists = array_slice($ranked_artists, 0, $count);
        
        // Cache the results
        $this->rankings_cache[$cache_key] = array(
            'data' => $ranked_artists,
            'expires' => time() + $this->cache_expiration
        );
        
        return $ranked_artists;
    }
    
    /**
     * Get trending artworks
     *
     * @since 1.0.0
     * @param int $count Number of artworks to return
     * @param int $category Category ID (0 for all)
     * @return array Trending artworks
     */
    public function get_trending_artworks($count = 10, $category = 0) {
        // Check cache first
        $cache_key = md5("trending_artworks_{$count}_{$category}");
        if (isset($this->rankings_cache[$cache_key]) && $this->rankings_cache[$cache_key]['expires'] > time()) {
            return $this->rankings_cache[$cache_key]['data'];
        }
        
        // Get metrics system
        $metrics = VORTEX_Metrics::get_instance();
        
        // Get artworks with recent metrics data
        $artworks = $this->get_artworks_with_metrics($count * 3, $category, 7); // Last 7 days
        
        if (empty($artworks)) {
            return array();
        }
        
        // Apply trending algorithm (emphasizes recent activity)
        $trending_artworks = $this->apply_trending_algorithm($artworks);
        
        // Apply AI enhancements with focus on CLOE's curation
        $trending_artworks = $this->apply_ai_enhancements_to_trending($trending_artworks);
        
        // Limit to requested count
        $trending_artworks = array_slice($trending_artworks, 0, $count);
        
        // Cache the results
        $this->rankings_cache[$cache_key] = array(
            'data' => $trending_artworks,
            'expires' => time() + 1800 // 30 minutes (shorter for trending)
        );
        
        return $trending_artworks;
    }
    
    /**
     * Get sales leaderboard
     *
     * @since 1.0.0
     * @param int $count Number of artworks to return
     * @param int $category Category ID (0 for all)
     * @param string $period Period ('7days', '30days', '90days', 'alltime')
     * @return array Sales leaderboard
     */
    public function get_sales_leaderboard($count = 10, $category = 0, $period = '30days') {
        // Check cache first
        $cache_key = md5("sales_leaderboard_{$count}_{$category}_{$period}");
        if (isset($this->rankings_cache[$cache_key]) && $this->rankings_cache[$cache_key]['expires'] > time()) {
            return $this->rankings_cache[$cache_key]['data'];
        }
        
        // Get days based on period
        $days = $this->get_days_from_period($period);
        
        // Get metrics system
        $metrics = VORTEX_Metrics::get_instance();
        
        // Get artworks with sales data
        $artworks = $this->get_artworks_with_sales($count * 2, $category, $days);
        
        if (empty($artworks)) {
            return array();
        }
        
        // Apply BusinessStrategist insights to sales rankings
        $ranked_artworks = $this->apply_business_insights_to_sales($artworks);
        
        // Limit to requested count
        $ranked_artworks = array_slice($ranked_artworks, 0, $count);
        
        // Cache the results
        $this->rankings_cache[$cache_key] = array(
            'data' => $ranked_artworks,
            'expires' => time() + $this->cache_expiration
        );
        
        return $ranked_artworks;
    }
    
    /**
     * Get days from period
     *
     * @since 1.0.0
     * @param string $period Period string
     * @return int Number of days
     */
    private function get_days_from_period($period) {
        switch ($period) {
            case '7days':
                return 7;
            case '30days':
                return 30;
            case '90days':
                return 90;
            case 'alltime':
                return 3650; // ~10 years
            default:
                return 30;
        }
    }
    
    /**
     * Get artworks with metrics
     *
     * @since 1.0.0
     * @param int $count Number of artworks to return
     * @param int $category Category ID (0 for all)
     * @param int $days Number of days to look back
     * @return array Artworks with metrics
     */
    private function get_artworks_with_metrics($count = 30, $category = 0, $days = 30) {
        global $wpdb;
        $metrics_table = $wpdb->prefix . 'vortex_metrics';
        
        // Base query to get artwork IDs with activity
        $query = "
            SELECT DISTINCT m.object_id 
            FROM {$metrics_table} m
            WHERE m.metric_type IN ('artwork_view', 'artwork_sale')
              AND m.date_recorded >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ";
        
        $args = array($days);
        
        // Add category filter if specified
        if ($category > 0) {
            $query .= " AND m.object_id IN (
                SELECT object_id FROM {$wpdb->term_relationships}
                WHERE term_taxonomy_id = %d
            )";
            $args[] = $category;
        }
        
        $query .= " LIMIT %d";
        $args[] = $count * 3; // Get more than needed for processing
        
        $artwork_ids = $wpdb->get_col($wpdb->prepare($query, $args));
        
        if (empty($artwork_ids)) {
            return array();
        }
        
        // Get artwork details and metrics
        $artworks = array();
        foreach ($artwork_ids as $artwork_id) {
            $artwork = get_post($artwork_id);
            if (!$artwork || $artwork->post_type !== 'vortex-artwork' || $artwork->post_status !== 'publish') {
                continue;
            }
            
            // Basic artwork info
            $artwork_data = array(
                'id' => $artwork->ID,
                'title' => $artwork->post_title,
                'permalink' => get_permalink($artwork->ID),
                'thumbnail' => get_the_post_thumbnail_url($artwork->ID, 'medium'),
                'artist_id' => get_post_meta($artwork->ID, 'vortex_artist_id', true),
                'price' => get_post_meta($artwork->ID, 'vortex_artwork_price', true),
                'date_created' => $artwork->post_date,
                'categories' => wp_get_post_terms($artwork->ID, 'vortex-artwork-category', array('fields' => 'names')),
                'styles' => wp_get_post_terms($artwork->ID, 'vortex-artwork-style', array('fields' => 'names')),
                'metrics' => array()
            );
            
            // Get artist info
            if (!empty($artwork_data['artist_id'])) {
                $artist = get_userdata($artwork_data['artist_id']);
                if ($artist) {
                    $artwork_data['artist_name'] = $artist->display_name;
                    $artwork_data['artist_url'] = get_author_posts_url($artist->ID);
                }
            }
            
            // Get view count
            $view_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) 
                 FROM {$metrics_table} 
                 WHERE metric_type = 'artwork_view' 
                   AND object_id = %d 
                   AND date_recorded >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                $artwork->ID,
                $days
            ));
            $artwork_data['metrics']['views'] = (int)$view_count;
            
            // Get sales count and revenue
            $sales_data = $wpdb->get_row($wpdb->prepare(
                "SELECT COUNT(*) as count, SUM(value) as revenue
                 FROM {$metrics_table} 
                 WHERE metric_type = 'artwork_sale' 
                   AND object_id = %d 
                   AND date_recorded >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                $artwork->ID,
                $days
            ));
            $artwork_data['metrics']['sales'] = (int)$sales_data->count;
            $artwork_data['metrics']['revenue'] = (float)$sales_data->revenue;
            
            // Get latest activity
            $latest_activity = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(date_recorded) 
                 FROM {$metrics_table} 
                 WHERE (metric_type = 'artwork_view' OR metric_type = 'artwork_sale') 
                   AND object_id = %d",
                $artwork->ID
            ));
            $artwork_data['metrics']['latest_activity'] = $latest_activity;
            
            // Get AI quality score if available
            $seed_art_analysis = get_post_meta($artwork->ID, 'vortex_seed_art_analysis', true);
            if (!empty($seed_art_analysis) && isset($seed_art_analysis['overall_score'])) {
                $artwork_data['metrics']['seed_art_quality'] = (float)$seed_art_analysis['overall_score'];
            } else {
                $artwork_data['metrics']['seed_art_quality'] = 0.0;
            }
            
            // Calculate recency score (0-1)
            if (!empty($latest_activity)) {
                $latest_timestamp = strtotime($latest_activity);
                $now = time();
                $days_ago = ($now - $latest_timestamp) / (60 * 60 * 24);
                $artwork_data['metrics']['recency'] = max(0, 1 - ($days_ago / $days));
            } else {
                $artwork_data['metrics']['recency'] = 0;
            }
            
            $artworks[] = $artwork_data;
        }
        
        return $artworks;
    }
    
    /**
     * Get artists with metrics
     *
     * @since 1.0.0
     * @param int $count Number of artists to return
     * @param int $category Category ID (0 for all)
     * @param int $days Number of days to look back
     * @return array Artists with metrics
     */
    private function get_artists_with_metrics($count = 30, $category = 0, $days = 30) {
        global $wpdb;
        $metrics_table = $wpdb->prefix . 'vortex_metrics';
        
        // Get artists with activity
        $artists_query = "
            SELECT DISTINCT pm.meta_value as artist_id
            FROM {$wpdb->postmeta} pm
            JOIN {$metrics_table} m ON pm.post_id = m.object_id
            WHERE pm.meta_key = 'vortex_artist_id'
              AND m.metric_type IN ('artwork_view', 'artwork_sale')
              AND m.date_recorded >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ";
        
        $args = array($days);
        
        // Add category filter if specified
        if ($category > 0) {
            $artists_query .= " AND m.object_id IN (
                SELECT object_id FROM {$wpdb->term_relationships}
                WHERE term_taxonomy_id = %d
            )";
            $args[] = $category;
        }
        
        $artists_query .= " LIMIT %d";
        $args[] = $count * 3; // Get more than needed for processing
        
        $artist_ids = $wpdb->get_col($wpdb->prepare($artists_query, $args));
        
        if (empty($artist_ids)) {
            return array();
        }
        
        // Get artist details and metrics
        $artists = array();
        foreach ($artist_ids as $artist_id) {
            $user = get_userdata($artist_id);
            if (!$user) {
                continue;
            }
            
            // Basic artist info
            $artist_data = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'url' => get_author_posts_url($user->ID),
                'avatar' => get_avatar_url($user->ID, array('size' => 150)),
                'bio' => get_user_meta($user->ID, 'description', true),
                'metrics' => array()
            );
            
            // Get artist's artworks
            $artwork_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                 WHERE meta_key = 'vortex_artist_id' AND meta_value = %d",
                $user->ID
            ));
            
            $artist_data['metrics']['artwork_count'] = count($artwork_ids);
            
            if (!empty($artwork_ids)) {
                $artwork_ids_str = implode(',', $artwork_ids);
                
                // Get total views
                $view_count = $wpdb->get_var(
                    "SELECT COUNT(*) 
                     FROM {$metrics_table} 
                     WHERE metric_type = 'artwork_view' 
                       AND object_id IN ({$artwork_ids_str}) 
                       AND date_recorded >= DATE_SUB(NOW(), INTERVAL {$days} DAY)"
                );
                $artist_data['metrics']['total_views'] = (int)$view_count;
                
                // Get total sales and revenue
                $sales_data = $wpdb->get_row(
                    "SELECT COUNT(*) as count, SUM(value) as revenue
                     FROM {$metrics_table} 
                     WHERE metric_type = 'artwork_sale' 
                       AND object_id IN ({$artwork_ids_str}) 
                       AND date_recorded >= DATE_SUB(NOW(), INTERVAL {$days} DAY)"
                );
                $artist_data['metrics']['total_sales'] = (int)$sales_data->count;
                $artist_data['metrics']['total_revenue'] = (float)$sales_data->revenue;
                
                // Get average artwork quality
                $quality_scores = array();
                foreach ($artwork_ids as $artwork_id) {
                    $seed_art_analysis = get_post_meta($artwork_id, 'vortex_seed_art_analysis', true);
                    if (!empty($seed_art_analysis) && isset($seed_art_analysis['overall_score'])) {
                        $quality_scores[] = (float)$seed_art_analysis['overall_score'];
                    }
                }
                
                if (!empty($quality_scores)) {
                    $artist_data['metrics']['avg_artwork_quality'] = array_sum($quality_scores) / count($quality_scores);
                } else {
                    $artist_data['metrics']['avg_artwork_quality'] = 0.0;
                }
                
                // Get latest activity
                $latest_activity = $wpdb->get_var(
                    "SELECT MAX(date_recorded) 
                     FROM {$metrics_table} 
                     WHERE (metric_type = 'artwork_view' OR metric_type = 'artwork_sale') 
                       AND object_id IN ({$artwork_ids_str})"
                );
                $artist_data['metrics']['latest_activity'] = $latest_activity;
                
                // Calculate marketplace engagement score (based on activity diversity)
                $engagement_query = $wpdb->get_results(
                    "SELECT metric_type, COUNT(*) as count
                     FROM {$metrics_table} 
                     WHERE object_id IN ({$artwork_ids_str}) 
                       AND date_recorded >= DATE_SUB(NOW(), INTERVAL {$days} DAY) 
                     GROUP BY metric_type"
                );
                
                $engagement_score = 0;
                $engagement_types = 0;
                
                foreach ($engagement_query as $engagement) {
                    $engagement_score += $engagement->count;
                    $engagement_types++;
                }
                
                // Normalize between 0-1, factoring in diversity of engagement
                if ($engagement_score > 0) {
                    $artist_data['metrics']['marketplace_engagement'] = 
                        min(1.0, ($engagement_score / 100) * ($engagement_types / 3));
                } else {
                    $artist_data['metrics']['marketplace_engagement'] = 0.0;
                }
                
                // Calculate artistic growth (based on quality improvement over time)
                $artist_data['metrics']['artistic_growth'] = $this->calculate_artistic_growth($artwork_ids);
            } else {
                // No artworks, set default metrics
                $artist_data['metrics']['total_views'] = 0;
                $artist_data['metrics']['total_sales'] = 0;
                $artist_data['metrics']['total_revenue'] = 0.0;
                $artist_data['metrics']['avg_artwork_quality'] = 0.0;
                $artist_data['metrics']['latest_activity'] = null;
                $artist_data['metrics']['marketplace_engagement'] = 0.0;
                $artist_data['metrics']['artistic_growth'] = 0.0;
            }
            
            $artists[] = $artist_data;
        }
        
        return $artists;
    }
    
    /**
     * Get artworks with sales
     *
     * @since 1.0.0
     * @param int $count Number of artworks to return
     * @param int $category Category ID (0 for all)
     * @param int $days Number of days to look back
     * @return array Artworks with sales
     */
    private function get_artworks_with_sales($count = 30, $category = 0, $days = 30) {
        global $wpdb;
        $metrics_table = $wpdb->prefix . 'vortex_metrics';
        
        // Query to get artworks with sales
        $query = "
            SELECT m.object_id, COUNT(*) as sales_count, SUM(m.value) as revenue
            FROM {$metrics_table} m
            WHERE m.metric_type = 'artwork_sale'
              AND m.date_recorded >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ";
        
        $args = array($days);
        
        // Add category filter if specified
        if ($category > 0) {
            $query .= " AND m.object_id IN (
                SELECT object_id FROM {$wpdb->term_relationships}
                WHERE term_taxonomy_id = %d
            )";
            $args[] = $category;
        }
        
        $query .= " GROUP BY m.object_id ORDER BY revenue DESC LIMIT %d";
        $args[] = $count * 2; // Get more than needed for processing
        
        $sales_data = $wpdb->get_results($wpdb->prepare($query, $args));
        
        if (empty($sales_data)) {
            return array();
        }
        
        // Get artwork details
        $artworks = array();
        foreach ($sales_data as $sale) {
            $artwork = get_post($sale->object_id);
            if (!$artwork || $artwork->post_type !== 'vortex-artwork' || $artwork->post_status !== 'publish') {
                continue;
            }
            
            // Basic artwork info
            $artwork_data = array(
                'id' => $artwork->ID,
                'title' => $artwork->post_title,
                'permalink' => get_permalink($artwork->ID),
                'thumbnail' => get_the_post_thumbnail_url($artwork->ID, 'medium'),
                'artist_id' => get_post_meta($artwork->ID, 'vortex_artist_id', true),
                'price' => get_post_meta($artwork->ID, 'vortex_artwork_price', true),
                'date_created' => $artwork->post_date,
                'categories' => wp_get_post_terms($artwork->ID, 'vortex-artwork-category', array('fields' => 'names')),
                'styles' => wp_get_post_terms($artwork->ID, 'vortex-artwork-style', array('fields' => 'names')),
                'metrics' => array()
            );
            
            // Get artist info
            if (!empty($artwork_data['artist_id'])) {
                $artist = get_userdata($artwork_data['artist_id']);
                if ($artist) {
                    $artwork_data['artist_name'] = $artist->display_name;
                    $artwork_data['artist_url'] = get_author_posts_url($artist->ID);
                }
            }
            
            // Get view count
            $view_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) 
                 FROM {$metrics_table} 
                 WHERE metric_type = 'artwork_view' 
                   AND object_id = %d 
                   AND date_recorded >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                $artwork->ID,
                $days
            ));
            $artwork_data['metrics']['views'] = (int)$view_count;
            
            // Get sales count and revenue
            $sales_data = $wpdb->get_row($wpdb->prepare(
                "SELECT COUNT(*) as count, SUM(value) as revenue
                 FROM {$metrics_table} 
                 WHERE metric_type = 'artwork_sale' 
                   AND object_id = %d 
                   AND date_recorded >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                $artwork->ID,
                $days
            ));
            $artwork_data['metrics']['sales'] = (int)$sales_data->count;
            $artwork_data['metrics']['revenue'] = (float)$sales_data->revenue;
            
            // Get latest activity
            $latest_activity = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(date_recorded) 
                 FROM {$metrics_table} 
                 WHERE (metric_type = 'artwork_view' OR metric_type = 'artwork_sale') 
                   AND object_id = %d",
                $artwork->ID
            ));
            $artwork_data['metrics']['latest_activity'] = $latest_activity;
            
            // Get AI quality score if available
            $seed_art_analysis = get_post_meta($artwork->ID, 'vortex_seed_art_analysis', true);
            if (!empty($seed_art_analysis) && isset($seed_art_analysis['overall_score'])) {
                $artwork_data['metrics']['seed_art_quality'] = (float)$seed_art_analysis['overall_score'];
            } else {
                $artwork_data['metrics']['seed_art_quality'] = 0.0;
            }
            
            // Calculate recency score (0-1)
            if (!empty($latest_activity)) {
                $latest_timestamp = strtotime($latest_activity);
                $now = time();
                $days_ago = ($now - $latest_timestamp) / (60 * 60 * 24);
                $artwork_data['metrics']['recency'] = max(0, 1 - ($days_ago / $days));
            } else {
                $artwork_data['metrics']['recency'] = 0;
            }
            
            $artworks[] = $artwork_data;
        }
        
        return $artworks;
    }
} 