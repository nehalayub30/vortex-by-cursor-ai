<?php
/**
 * VORTEX Analytics System
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Analytics
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Analytics Class
 * 
 * Advanced analytics processing and insights generation.
 * Integrates with AI agents for deep learning and predictive analytics.
 *
 * @since 1.0.0
 */
class VORTEX_Analytics {
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
     * Analytics cache
     *
     * @since 1.0.0
     * @var array
     */
    private $analytics_cache = array();
    
    /**
     * Cache expiration (in seconds)
     *
     * @since 1.0.0
     * @var int
     */
    private $cache_expiration = 1800; // 30 minutes
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Initialize AI agents
        $this->initialize_ai_agents();
        
        // Set up hooks
        $this->setup_hooks();
    }
    
    /**
     * Get instance of this class.
     *
     * @since 1.0.0
     * @return VORTEX_Analytics
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize AI agents for analytics processing
     *
     * @since 1.0.0
     * @return void
     */
    private function initialize_ai_agents() {
        // Initialize HURAII for visual insight analysis
        $this->ai_agents['HURAII'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'analytics_processing',
            'capabilities' => array(
                'style_pattern_recognition',
                'visual_trend_prediction',
                'seed_art_component_forecasting'
            )
        );
        
        // Initialize CLOE for curation and discovery analytics
        $this->ai_agents['CLOE'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'curation_analytics',
            'capabilities' => array(
                'audience_preference_modeling',
                'emerging_artist_identification',
                'collection_recommendation_optimization'
            )
        );
        
        // Initialize BusinessStrategist for market analytics
        $this->ai_agents['BusinessStrategist'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'market_analytics',
            'capabilities' => array(
                'price_trend_analysis',
                'market_segment_forecasting',
                'revenue_optimization_strategies'
            )
        );
        
        // Initialize AI agents via action
        do_action('vortex_ai_agent_init', 'analytics_processing', array_keys($this->ai_agents), 'active');
    }
    
    /**
     * Set up hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function setup_hooks() {
        // Scheduled analytics generation
        add_action('vortex_daily_analytics_processing', array($this, 'generate_daily_analytics'));
        
        // Admin page hooks
        add_action('admin_menu', array($this, 'add_analytics_menu'));
        
        // AJAX handlers
        add_action('wp_ajax_vortex_get_analytics', array($this, 'ajax_get_analytics'));
        
        // Set up scheduled events if not already
        if (!wp_next_scheduled('vortex_daily_analytics_processing')) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_analytics_processing');
        }
    }
    
    /**
     * Add analytics menu
     *
     * @since 1.0.0
     * @return void
     */
    public function add_analytics_menu() {
        add_submenu_page(
            'vortex-dashboard',
            __('Analytics', 'vortex-marketplace'),
            __('Analytics', 'vortex-marketplace'),
            'manage_options',
            'vortex-analytics',
            array($this, 'render_analytics_page')
        );
    }
    
    /**
     * Render analytics page
     *
     * @since 1.0.0
     * @return void
     */
    public function render_analytics_page() {
        // Load metrics system
        $metrics = VORTEX_Metrics::get_instance();
        
        // Get current user
        $current_user = wp_get_current_user();
        
        // Include template
        include(VORTEX_PLUGIN_PATH . 'templates/admin/analytics-dashboard.php');
    }
    
    /**
     * AJAX handler for getting analytics
     *
     * @since 1.0.0
     * @return void
     */
    public function ajax_get_analytics() {
        check_ajax_referer('vortex_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'vortex-marketplace')));
        }
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'monthly';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        
        $data = array();
        
        switch ($type) {
            case 'sales_trend':
                $data = $this->get_sales_trend_analytics($period, $start_date, $end_date);
                break;
            case 'artist_performance':
                $data = $this->get_artist_performance_analytics($period, $start_date, $end_date);
                break;
            case 'category_popularity':
                $data = $this->get_category_popularity_analytics($period, $start_date, $end_date);
                break;
            case 'seed_art_component_usage':
                $data = $this->get_seed_art_component_analytics($period, $start_date, $end_date);
                break;
            case 'market_predictions':
                $data = $this->get_market_predictions_analytics($period, $start_date, $end_date);
                break;
            default:
                wp_send_json_error(array('message' => __('Invalid analytics type', 'vortex-marketplace')));
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Get sales trend analytics
     *
     * @since 1.0.0
     * @param string $period Period (daily, weekly, monthly, yearly)
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Sales trend analytics
     */
    public function get_sales_trend_analytics($period = 'monthly', $start_date = '', $end_date = '') {
        // Check cache first
        $cache_key = md5("sales_trend_{$period}_{$start_date}_{$end_date}");
        if (isset($this->analytics_cache[$cache_key]) && $this->analytics_cache[$cache_key]['expires'] > time()) {
            return $this->analytics_cache[$cache_key]['data'];
        }
        
        // Get metrics system
        $metrics = VORTEX_Metrics::get_instance();
        
        // Get raw sales data
        $sales_data = $metrics->get_sales_metrics($period, $start_date, $end_date);
        
        // Process with BusinessStrategist for enhanced insights
        $enhanced_data = $this->enhance_with_business_strategist($sales_data, 'sales_trend', $period);
        
        // Cache the results
        $this->analytics_cache[$cache_key] = array(
            'data' => $enhanced_data,
            'expires' => time() + $this->cache_expiration
        );
        
        return $enhanced_data;
    }
    
    /**
     * Get artist performance analytics
     *
     * @since 1.0.0
     * @param string $period Period (daily, weekly, monthly, yearly)
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Artist performance analytics
     */
    public function get_artist_performance_analytics($period = 'monthly', $start_date = '', $end_date = '') {
        // Check cache first
        $cache_key = md5("artist_performance_{$period}_{$start_date}_{$end_date}");
        if (isset($this->analytics_cache[$cache_key]) && $this->analytics_cache[$cache_key]['expires'] > time()) {
            return $this->analytics_cache[$cache_key]['data'];
        }
        
        // Get metrics system
        $metrics = VORTEX_Metrics::get_instance();
        
        // Get top artists by sales
        $top_artists_sales = $metrics->get_top_items('artwork_sale', 'artist', 10);
        
        // Get top artists by views
        $top_artists_views = $metrics->get_top_items('artwork_view', 'artist', 10);
        
        // Process with CLOE for enhanced insights
        $raw_data = array(
            'top_by_sales' => $top_artists_sales,
            'top_by_views' => $top_artists_views
        );
        
        $enhanced_data = $this->enhance_with_cloe($raw_data, 'artist_performance', $period);
        
        // Cache the results
        $this->analytics_cache[$cache_key] = array(
            'data' => $enhanced_data,
            'expires' => time() + $this->cache_expiration
        );
        
        return $enhanced_data;
    }
    
    /**
     * Get category popularity analytics
     *
     * @since 1.0.0
     * @param string $period Period (daily, weekly, monthly, yearly)
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Category popularity analytics
     */
    public function get_category_popularity_analytics($period = 'monthly', $start_date = '', $end_date = '') {
        // Check cache first
        $cache_key = md5("category_popularity_{$period}_{$start_date}_{$end_date}");
        if (isset($this->analytics_cache[$cache_key]) && $this->analytics_cache[$cache_key]['expires'] > time()) {
            return $this->analytics_cache[$cache_key]['data'];
        }
        
        global $wpdb;
        
        // Get categories with view counts
        $categories = get_terms(array(
            'taxonomy' => 'vortex-artwork-category',
            'hide_empty' => true
        ));
        
        $category_data = array();
        
        if (!empty($categories)) {
            foreach ($categories as $category) {
                // Get artworks in this category
                $artworks = get_posts(array(
                    'post_type' => 'vortex-artwork',
                    'posts_per_page' => -1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'vortex-artwork-category',
                            'field' => 'term_id',
                            'terms' => $category->term_id
                        )
                    ),
                    'fields' => 'ids'
                ));
                
                if (!empty($artworks)) {
                    // Get view count for these artworks
                    $date_condition = '';
                    if (!empty($start_date) && !empty($end_date)) {
                        $date_condition = $wpdb->prepare(
                            " AND date_recorded BETWEEN %s AND %s",
                            $start_date . ' 00:00:00',
                            $end_date . ' 23:59:59'
                        );
                    }
                    
                    $artwork_ids = implode(',', $artworks);
                    $metrics_table = $wpdb->prefix . 'vortex_metrics';
                    
                    $views = $wpdb->get_var(
                        "SELECT COUNT(*) 
                         FROM {$metrics_table} 
                         WHERE metric_type = 'artwork_view' 
                           AND object_id IN ({$artwork_ids})
                           {$date_condition}"
                    );
                    
                    $sales = $wpdb->get_var(
                        "SELECT COUNT(*) 
                         FROM {$metrics_table} 
                         WHERE metric_type = 'artwork_sale' 
                           AND object_id IN ({$artwork_ids})
                           {$date_condition}"
                    );
                    
                    $revenue = $wpdb->get_var(
                        "SELECT SUM(value) 
                         FROM {$metrics_table} 
                         WHERE metric_type = 'artwork_sale' 
                           AND object_id IN ({$artwork_ids})
                           {$date_condition}"
                    );
                    
                    $category_data[] = array(
                        'id' => $category->term_id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'artwork_count' => count($artworks),
                        'views' => (int)$views,
                        'sales' => (int)$sales,
                        'revenue' => (float)$revenue
                    );
                }
            }
        }
        
        // Process with CLOE for enhanced insights
        $enhanced_data = $this->enhance_with_cloe($category_data, 'category_popularity', $period);
        
        // Cache the results
        $this->analytics_cache[$cache_key] = array(
            'data' => $enhanced_data,
            'expires' => time() + $this->cache_expiration
        );
        
        return $enhanced_data;
    }
    
    /**
     * Get Seed Art component analytics
     *
     * @since 1.0.0
     * @param string $period Period (daily, weekly, monthly, yearly)
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Seed Art component analytics
     */
    public function get_seed_art_component_analytics($period = 'monthly', $start_date = '', $end_date = '') {
        // Check cache first
        $cache_key = md5("seed_art_component_{$period}_{$start_date}_{$end_date}");
        if (isset($this->analytics_cache[$cache_key]) && $this->analytics_cache[$cache_key]['expires'] > time()) {
            return $this->analytics_cache[$cache_key]['data'];
        }
        
        // Define Seed Art components
        $components = array(
            'color_theory',
            'composition',
            'texture',
            'light_and_shadow',
            'perspective',
            'symbolism',
            'movement_and_layering'
        );
        
        $component_data = array();
        
        // Get artworks with Seed Art analysis
        $artworks = get_posts(array(
            'post_type' => 'vortex-artwork',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'vortex_seed_art_analysis',
                    'compare' => 'EXISTS'
                )
            ),
            'fields' => 'ids'
        ));
        
        if (!empty($artworks)) {
            foreach ($components as $component) {
                $usage_count = 0;
                $efficiency_sum = 0;
                $scores = array();
                
                foreach ($artworks as $artwork_id) {
                    $analysis = get_post_meta($artwork_id, 'vortex_seed_art_analysis', true);
                    if (!empty($analysis) && isset($analysis['components'][$component])) {
                        $usage_count++;
                        $scores[] = $analysis['components'][$component]['score'];
                        
                        if (isset($analysis['components'][$component]['efficiency'])) {
                            $efficiency_sum += $analysis['components'][$component]['efficiency'];
                        }
                    }
                }
                
                $component_data[$component] = array(
                    'name' => ucwords(str_replace('_', ' ', $component)),
                    'usage_count' => $usage_count,
                    'avg_score' => $usage_count > 0 ? array_sum($scores) / $usage_count : 0,
                    'avg_efficiency' => $usage_count > 0 ? $efficiency_sum / $usage_count : 0,
                    'popularity' => $usage_count / count($artworks) * 100
                );
            }
        }
        
        // Process with HURAII for enhanced insights
        $enhanced_data = $this->enhance_with_huraii($component_data, 'seed_art_components', $period);
        
        // Cache the results
        $this->analytics_cache[$cache_key] = array(
            'data' => $enhanced_data,
            'expires' => time() + $this->cache_expiration
        );
        
        return $enhanced_data;
    }
    
    /**
     * Get market predictions analytics
     *
     * @since 1.0.0
     * @param string $period Period (daily, weekly, monthly, yearly)
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Market predictions analytics
     */
    public function get_market_predictions_analytics($period = 'monthly', $start_date = '', $end_date = '') {
        // Check cache first
        $cache_key = md5("market_predictions_{$period}_{$start_date}_{$end_date}");
        if (isset($this->analytics_cache[$cache_key]) && $this->analytics_cache[$cache_key]['expires'] > time()) {
            return $this->analytics_cache[$cache_key]['data'];
        }
        
        // Get metrics system
        $metrics = VORTEX_Metrics::get_instance();
        
        // Get sales data for trend analysis
        $sales_data = $metrics->get_sales_metrics($period, $start_date, $end_date);
        
        // Get trending artworks
        $trending_artworks = $metrics->get_trending_items('artwork', 20);
        
        // Get trending artists
        $trending_artists = $metrics->get_trending_items('artist', 10);
        
        $raw_data = array(
            'sales_data' => $sales_data,
            'trending_artworks' => $trending_artworks,
            'trending_artists' => $trending_artists
        );
        
        // Process with BusinessStrategist for predictive analytics
        $enhanced_data = $this->enhance_with_business_strategist($raw_data, 'market_predictions', $period);
        
        // Combine with HURAII's style predictions
        $style_predictions = $this->get_huraii_style_predictions();
        if (!empty($style_predictions)) {
            $enhanced_data['style_predictions'] = $style_predictions;
        }
        
        // Cache the results
        $this->analytics_cache[$cache_key] = array(
            'data' => $enhanced_data,
            'expires' => time() + $this->cache_expiration
        );
        
        return $enhanced_data;
    }
    
    /**
     * Enhance data with HURAII insights
     *
     * @since 1.0.0
     * @param array $data Raw data
     * @param string $analysis_type Type of analysis
     * @param string $period Period of analysis
     * @return array Enhanced data
     */
    private function enhance_with_huraii($data, $analysis_type, $period) {
        // Check if HURAII is active for AI enhancement
        if (!$this->ai_agents['HURAII']['active']) {
            return $data;
        }
        
        // Feed data to HURAII for analysis
        $analysis_context = array(
            'data' => $data,
            'analysis_type' => $analysis_type,
            'period' => $period
        );
        
        // Send data to HURAII through action hook
        do_action('vortex_ai_agent_analyze', 'HURAII', 'analytics', $analysis_context);
        
        // Get enhanced insights from HURAII
        $insights = apply_filters('vortex_huraii_analytics_insights', array(), $analysis_type, $analysis_context);
        
        // Merge insights with data
        $enhanced_data = $data;
        if (!empty($insights)) {
            $enhanced_data['ai_insights'] = $insights;
        }
        
        return $enhanced_data;
    }
    
    /**
     * Enhance data with CLOE insights
     *
     * @since 1.0.0
     * @param array $data Raw data
     * @param string $analysis_type Type of analysis
     * @param string $period Period of analysis
     * @return array Enhanced data
     */
    private function enhance_with_cloe($data, $analysis_type, $period) {
        // Check if CLOE is active for AI enhancement
        if (!$this->ai_agents['CLOE']['active']) {
            return $data;
        }
        
        // Feed data to CLOE for analysis
        $analysis_context = array(
            'data' => $data,
            'analysis_type' => $analysis_type,
            'period' => $period
        );
        
        // Send data to CLOE through action hook
        do_action('vortex_ai_agent_analyze', 'CLOE', 'analytics', $analysis_context);
        
        // Get enhanced insights from CLOE
        $insights = apply_filters('vortex_cloe_analytics_insights', array(), $analysis_type, $analysis_context);
        
        // Merge insights with data
        $enhanced_data = $data;
        if (!empty($insights)) {
            $enhanced_data['ai_insights'] = $insights;
        }
        
        return $enhanced_data;
    }
    
    /**
     * Enhance data with BusinessStrategist insights
     *
     * @since 1.0.0
     * @param array $data Raw data
     * @param string $analysis_type Type of analysis
     * @param string $period Period of analysis
     * @return array Enhanced data
     */
    private function enhance_with_business_strategist($data, $analysis_type, $period) {
        // Check if BusinessStrategist is active for AI enhancement
        if (!$this->ai_agents['BusinessStrategist']['active']) {
            return $data;
        }
        
        // Feed data to BusinessStrategist for analysis
        $analysis_context = array(
            'data' => $data,
            'analysis_type' => $analysis_type,
            'period' => $period
        );
        
        // Send data to BusinessStrategist through action hook
        do_action('vortex_ai_agent_analyze', 'BusinessStrategist', 'analytics', $analysis_context);
        
        // Get enhanced insights from BusinessStrategist
        $insights = apply_filters('vortex_business_strategist_analytics_insights', array(), $analysis_type, $analysis_context);
        
        // Get predictions if applicable
        $predictions = array();
        if ($analysis_type === 'market_predictions' || $analysis_type === 'sales_trend') {
            $predictions = apply_filters('vortex_business_strategist_market_predictions', array(), $analysis_context);
        }
        
        // Merge insights with data
        $enhanced_data = $data;
        if (!empty($insights)) {
            $enhanced_data['ai_insights'] = $insights;
        }
        
        if (!empty($predictions)) {
            $enhanced_data['ai_predictions'] = $predictions;
        }
        
        return $enhanced_data;
    }
    
    /**
     * Get HURAII style predictions
     *
     * @since 1.0.0
     * @return array Style predictions
     */
    private function get_huraii_style_predictions() {
        // Check if HURAII is active
        if (!$this->ai_agents['HURAII']['active']) {
            return array();
        }
        
        // Get predictions from HURAII through filter
        $predictions = apply_filters('vortex_huraii_style_predictions', array());
        
        // If no predictions from filter, generate baseline predictions
        if (empty($predictions)) {
            // Get artwork styles
            $styles = get_terms(array(
                'taxonomy' => 'vortex-artwork-style',
                'hide_empty' => true
            ));
            
            if (!empty($styles)) {
                $predictions = array();
                
                foreach ($styles as $style) {
                    // Get artworks in this style
                    $artwork_count = get_posts(array(
                        'post_type' => 'vortex-artwork',
                        'posts_per_page' => -1,
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'vortex-artwork-style',
                                'field' => 'term_id',
                                'terms' => $style->term_id
                            )
                        ),
                        'fields' => 'ids'
                    ));
                    
                    if (!empty($artwork_count)) {
                        $trend_score = mt_rand(0, 100) / 100; // Placeholder for AI-generated score
                        
                        $predictions[] = array(
                            'style_id' => $style->term_id,
                            'style_name' => $style->name,
                            'trend_score' => $trend_score,
                            'current_popularity' => count($artwork_count),
                            'growth_potential' => $trend_score > 0.7 ? 'high' : ($trend_score > 0.4 ? 'medium' : 'low'),
                        );
                    }
                }
                
                // Sort by trend score
                usort($predictions, function($a, $b) {
                    return $b['trend_score'] <=> $a['trend_score'];
                });
                
                // Keep only top 10
                $predictions = array_slice($predictions, 0, 10);
            }
        }
        
        return $predictions;
    }
    
    /**
     * Generate daily analytics
     *
     * @since 1.0.0
     * @return void
     */
    public function generate_daily_analytics() {
        // Get yesterday's date
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Generate sales analytics
        $this->get_sales_trend_analytics('daily', $yesterday, $yesterday);
        
        // Generate artist performance analytics
        $this->get_artist_performance_analytics('daily', $yesterday, $yesterday);
        
        // Generate category popularity analytics
        $this->get_category_popularity_analytics('daily', $yesterday, $yesterday);
        
        // Generate Seed Art component analytics
        $this->get_seed_art_component_analytics('daily', $yesterday, $yesterday);
        
        // Generate market predictions
        $this->get_market_predictions_analytics('daily', $yesterday, $yesterday);
        
        // Clear older cache entries
        $this->clear_old_cache_entries();
    }
    
    /**
     * Clear old cache entries
     *
     * @since 1.0.0
     * @return void
     */
    private function clear_old_cache_entries() {
        $current_time = time();
        
        foreach ($this->analytics_cache as $key => $cache_entry) {
            if ($cache_entry['expires'] < $current_time) {
                unset($this->analytics_cache[$key]);
            }
        }
    }
    
    /**
     * Get analytics summary
     *
     * @since 1.0.0
     * @return array Analytics summary
     */
    public function get_analytics_summary() {
        global $wpdb;
        $metrics_table = $wpdb->prefix . 'vortex_metrics';
        
        // Last 30 days
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $end_date = date('Y-m-d');
        
        // Total sales
        $total_sales = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$metrics_table} 
             WHERE metric_type = 'artwork_sale' 
               AND date_recorded BETWEEN %s AND %s",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));
        
        // Total revenue
        $total_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(value) 
             FROM {$metrics_table} 
             WHERE metric_type = 'artwork_sale' 
               AND date_recorded BETWEEN %s AND %s",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));
        
        // Total views
        $total_views = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$metrics_table} 
             WHERE metric_type = 'artwork_view' 
               AND date_recorded BETWEEN %s AND %s",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));
        
        // Active artists (artists with at least one view)
        $active_artists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) 
             FROM {$metrics_table} 
             WHERE metric_type = 'artist_view' 
               AND date_recorded BETWEEN %s AND %s",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));
        
        // AI interactions
        $ai_interactions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$metrics_table} 
             WHERE metric_type = 'ai_interaction' 
               AND date_recorded BETWEEN %s AND %s",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ));
        
        // Get trending data
        $metrics = VORTEX_Metrics::get_instance();
        $trending_artworks = $metrics->get_trending_items('artwork', 5);
        $trending_artists = $metrics->get_trending_items('artist', 5);
        
        // Prepare summary
        $summary = array(
            'period' => array(
                'start_date' => $start_date,
                'end_date' => $end_date,
                'days' => 30
            ),
            'metrics' => array(
                'total_sales' => (int)$total_sales,
                'total_revenue' => (float)$total_revenue,
                'total_views' => (int)$total_views,
                'active_artists' => (int)$active_artists,
                'ai_interactions' => (int)$ai_interactions
            ),
            'trending' => array(
                'artworks' => $trending_artworks,
                'artists' => $trending_artists
            )
        );
        
        // Enhance with AI insights
        $summary = $this->enhance_with_ai_agents($summary, 'summary');
        
        return $summary;
    }
    
    /**
     * Enhance summary with all AI agents
     *
     * @since 1.0.0
     * @param array $summary Summary data
     * @param string $context Context
     * @return array Enhanced summary
     */
    private function enhance_with_ai_agents($summary, $context) {
        $enhanced_summary = $summary;
        
        // Enhanced with HURAII
        if ($this->ai_agents['HURAII']['active']) {
            do_action('vortex_ai_agent_analyze', 'HURAII', 'summary', array('data' => $summary, 'context' => $context));
            $huraii_insights = apply_filters('vortex_huraii_summary_insights', array(), $context);
            
            if (!empty($huraii_insights)) {
                $enhanced_summary['ai_insights']['HURAII'] = $huraii_insights;
            }
        }
        
        // Enhanced with CLOE
        if ($this->ai_agents['CLOE']['active']) {
            do_action('vortex_ai_agent_analyze', 'CLOE', 'summary', array('data' => $summary, 'context' => $context));
            $cloe_insights = apply_filters('vortex_cloe_summary_insights', array(), $context);
            
            if (!empty($cloe_insights)) {
                $enhanced_summary['ai_insights']['CLOE'] = $cloe_insights;
            }
        }
        
        // Enhanced with BusinessStrategist
        if ($this->ai_agents['BusinessStrategist']['active']) {
            do_action('vortex_ai_agent_analyze', 'BusinessStrategist', 'summary', array('data' => $summary, 'context' => $context));
            $bs_insights = apply_filters('vortex_business_strategist_summary_insights', array(), $context);
            
            if (!empty($bs_insights)) {
                $enhanced_summary['ai_insights']['BusinessStrategist'] = $bs_insights;
            }
        }
        
        return $enhanced_summary;
    }
}

// Initialize the analytics system
add_action('plugins_loaded', function() {
    VORTEX_Analytics::get_instance();
}); 