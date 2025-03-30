<?php
/**
 * VORTEX Metrics System
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Analytics
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Metrics Class
 * 
 * Core metrics collection and processing system.
 * Integrates with AI agents for continuous learning.
 *
 * @since 1.0.0
 */
class VORTEX_Metrics {
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
     * Metrics cache
     *
     * @since 1.0.0
     * @var array
     */
    private $metrics_cache = array();
    
    /**
     * Cache expiration (in seconds)
     *
     * @since 1.0.0
     * @var int
     */
    private $cache_expiration = 3600; // 1 hour
    
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
     * @return VORTEX_Metrics
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize AI agents for metrics processing
     *
     * @since 1.0.0
     * @return void
     */
    private function initialize_ai_agents() {
        // Initialize HURAII for visual data analysis
        $this->ai_agents['HURAII'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'metrics_analysis',
            'capabilities' => array(
                'visual_trend_detection',
                'style_popularity_tracking',
                'seed_art_component_analytics'
            )
        );
        
        // Initialize CLOE for curation metrics
        $this->ai_agents['CLOE'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'curation_metrics',
            'capabilities' => array(
                'engagement_analysis',
                'collection_performance',
                'category_trend_detection'
            )
        );
        
        // Initialize BusinessStrategist for economic metrics
        $this->ai_agents['BusinessStrategist'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'economic_metrics',
            'capabilities' => array(
                'sales_forecasting',
                'price_optimization',
                'market_segment_analysis'
            )
        );
        
        do_action('vortex_ai_agent_init', 'metrics_processing', array_keys($this->ai_agents), 'active');
    }
    
    /**
     * Set up hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function setup_hooks() {
        // Track artwork views
        add_action('vortex_artwork_viewed', array($this, 'track_artwork_view'), 10, 2);
        
        // Track artist profile views
        add_action('vortex_artist_viewed', array($this, 'track_artist_view'), 10, 2);
        
        // Track sales
        add_action('vortex_artwork_sale', array($this, 'track_artwork_sale'), 10, 4);
        
        // Track AI interactions
        add_action('vortex_ai_interaction', array($this, 'track_ai_interaction'), 10, 3);
        
        // Track NFT minting
        add_action('vortex_nft_minted', array($this, 'track_nft_minting'), 10, 2);
        
        // Track search queries
        add_action('vortex_search_performed', array($this, 'track_search_query'), 10, 2);
        
        // Scheduled aggregation
        add_action('vortex_daily_metrics_aggregation', array($this, 'aggregate_daily_metrics'));
        
        // Set up scheduled events if not already
        if (!wp_next_scheduled('vortex_daily_metrics_aggregation')) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_metrics_aggregation');
        }
    }
    
    /**
     * Track artwork view
     *
     * @since 1.0.0
     * @param int $artwork_id Artwork ID
     * @param int $user_id User ID (0 for guests)
     * @return void
     */
    public function track_artwork_view($artwork_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_metrics';
        
        // Record the view
        $wpdb->insert(
            $table_name,
            array(
                'metric_type' => 'artwork_view',
                'object_id' => $artwork_id,
                'user_id' => $user_id,
                'value' => 1,
                'date_recorded' => current_time('mysql')
            )
        );
        
        // Update artwork view count
        $current_views = get_post_meta($artwork_id, 'vortex_view_count', true);
        if (empty($current_views)) {
            $current_views = 0;
        }
        update_post_meta($artwork_id, 'vortex_view_count', $current_views + 1);
        
        // Feed data to AI agents for learning
        $this->feed_metric_to_ai_agents('artwork_view', array(
            'artwork_id' => $artwork_id,
            'user_id' => $user_id,
            'timestamp' => current_time('timestamp')
        ));
    }
    
    /**
     * Track artist profile view
     *
     * @since 1.0.0
     * @param int $artist_id Artist user ID
     * @param int $viewer_id Viewer user ID (0 for guests)
     * @return void
     */
    public function track_artist_view($artist_id, $viewer_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_metrics';
        
        // Record the view
        $wpdb->insert(
            $table_name,
            array(
                'metric_type' => 'artist_view',
                'object_id' => $artist_id,
                'user_id' => $viewer_id,
                'value' => 1,
                'date_recorded' => current_time('mysql')
            )
        );
        
        // Update artist view count
        $current_views = get_user_meta($artist_id, 'vortex_profile_views', true);
        if (empty($current_views)) {
            $current_views = 0;
        }
        update_user_meta($artist_id, 'vortex_profile_views', $current_views + 1);
        
        // Feed data to AI agents for learning
        $this->feed_metric_to_ai_agents('artist_view', array(
            'artist_id' => $artist_id,
            'viewer_id' => $viewer_id,
            'timestamp' => current_time('timestamp')
        ));
    }
    
    /**
     * Track artwork sale
     *
     * @since 1.0.0
     * @param int $artwork_id Artwork ID
     * @param int $seller_id Seller user ID
     * @param int $buyer_id Buyer user ID
     * @param float $amount Sale amount
     * @return void
     */
    public function track_artwork_sale($artwork_id, $seller_id, $buyer_id, $amount) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_metrics';
        
        // Record the sale
        $wpdb->insert(
            $table_name,
            array(
                'metric_type' => 'artwork_sale',
                'object_id' => $artwork_id,
                'user_id' => $buyer_id,
                'value' => $amount,
                'date_recorded' => current_time('mysql'),
                'metadata' => maybe_serialize(array(
                    'seller_id' => $seller_id,
                    'transaction_type' => 'primary_sale'
                ))
            )
        );
        
        // Update artwork sales count
        $sales_count = get_post_meta($artwork_id, 'vortex_sales_count', true);
        if (empty($sales_count)) {
            $sales_count = 0;
        }
        update_post_meta($artwork_id, 'vortex_sales_count', $sales_count + 1);
        
        // Update artist sales metrics
        $artist_id = get_post_meta($artwork_id, 'vortex_artist_id', true);
        if ($artist_id) {
            $artist_sales = get_user_meta($artist_id, 'vortex_total_sales', true);
            if (empty($artist_sales)) {
                $artist_sales = 0;
            }
            update_user_meta($artist_id, 'vortex_total_sales', $artist_sales + 1);
            
            $artist_revenue = get_user_meta($artist_id, 'vortex_total_revenue', true);
            if (empty($artist_revenue)) {
                $artist_revenue = 0;
            }
            update_user_meta($artist_id, 'vortex_total_revenue', $artist_revenue + $amount);
        }
        
        // Feed data to AI agents for learning
        $this->feed_metric_to_ai_agents('artwork_sale', array(
            'artwork_id' => $artwork_id,
            'seller_id' => $seller_id,
            'buyer_id' => $buyer_id,
            'amount' => $amount,
            'artist_id' => $artist_id,
            'timestamp' => current_time('timestamp')
        ));
    }
    
    /**
     * Track AI interaction
     *
     * @since 1.0.0
     * @param string $interaction_type Type of interaction
     * @param array $interaction_data Interaction data
     * @param int $user_id User ID
     * @return void
     */
    public function track_ai_interaction($interaction_type, $interaction_data, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_metrics';
        
        // Record the interaction
        $wpdb->insert(
            $table_name,
            array(
                'metric_type' => 'ai_interaction',
                'object_id' => isset($interaction_data['object_id']) ? $interaction_data['object_id'] : 0,
                'user_id' => $user_id,
                'value' => 1,
                'date_recorded' => current_time('mysql'),
                'metadata' => maybe_serialize(array(
                    'interaction_type' => $interaction_type,
                    'interaction_data' => $interaction_data
                ))
            )
        );
        
        // Feed data to AI agents for learning
        $this->feed_metric_to_ai_agents('ai_interaction', array(
            'interaction_type' => $interaction_type,
            'interaction_data' => $interaction_data,
            'user_id' => $user_id,
            'timestamp' => current_time('timestamp')
        ));
    }
    
    /**
     * Track NFT minting
     *
     * @since 1.0.0
     * @param int $artwork_id Artwork ID
     * @param array $nft_data NFT data
     * @return void
     */
    public function track_nft_minting($artwork_id, $nft_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_metrics';
        
        // Record the minting
        $wpdb->insert(
            $table_name,
            array(
                'metric_type' => 'nft_minting',
                'object_id' => $artwork_id,
                'user_id' => isset($nft_data['owner_id']) ? $nft_data['owner_id'] : 0,
                'value' => 1,
                'date_recorded' => current_time('mysql'),
                'metadata' => maybe_serialize($nft_data)
            )
        );
        
        // Feed data to AI agents for learning
        $this->feed_metric_to_ai_agents('nft_minting', array(
            'artwork_id' => $artwork_id,
            'nft_data' => $nft_data,
            'timestamp' => current_time('timestamp')
        ));
    }
    
    /**
     * Track search query
     *
     * @since 1.0.0
     * @param string $query Search query
     * @param int $user_id User ID
     * @return void
     */
    public function track_search_query($query, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_metrics';
        
        // Record the search
        $wpdb->insert(
            $table_name,
            array(
                'metric_type' => 'search_query',
                'object_id' => 0,
                'user_id' => $user_id,
                'value' => 1,
                'date_recorded' => current_time('mysql'),
                'metadata' => maybe_serialize(array(
                    'query' => $query
                ))
            )
        );
        
        // Feed data to AI agents for learning
        $this->feed_metric_to_ai_agents('search_query', array(
            'query' => $query,
            'user_id' => $user_id,
            'timestamp' => current_time('timestamp')
        ));
    }
    
    /**
     * Feed metric data to AI agents for learning
     *
     * @since 1.0.0
     * @param string $metric_type Type of metric
     * @param array $metric_data Metric data
     * @return void
     */
    private function feed_metric_to_ai_agents($metric_type, $metric_data) {
        // Feed to HURAII for visual trend analysis
        if ($this->ai_agents['HURAII']['active'] && 
            in_array($metric_type, array('artwork_view', 'artwork_sale', 'ai_interaction'))) {
            
            do_action('vortex_ai_agent_learn', 'HURAII', $metric_type, $metric_data);
        }
        
        // Feed to CLOE for curation analysis
        if ($this->ai_agents['CLOE']['active'] && 
            in_array($metric_type, array('artwork_view', 'artist_view', 'search_query'))) {
            
            do_action('vortex_ai_agent_learn', 'CLOE', $metric_type, $metric_data);
        }
        
        // Feed to BusinessStrategist for economic analysis
        if ($this->ai_agents['BusinessStrategist']['active'] && 
            in_array($metric_type, array('artwork_sale', 'nft_minting'))) {
            
            do_action('vortex_ai_agent_learn', 'BusinessStrategist', $metric_type, $metric_data);
        }
    }
    
    /**
     * Get metrics for a specific period
     *
     * @since 1.0.0
     * @param string $metric_type Type of metric
     * @param string $period Period (daily, weekly, monthly, yearly)
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Metrics data
     */
    public function get_metrics($metric_type, $period = 'daily', $start_date = '', $end_date = '') {
        // Set default dates if not provided
        if (empty($start_date)) {
            switch ($period) {
                case 'daily':
                    $start_date = date('Y-m-d', strtotime('-7 days'));
                    break;
                case 'weekly':
                    $start_date = date('Y-m-d', strtotime('-8 weeks'));
                    break;
                case 'monthly':
                    $start_date = date('Y-m-d', strtotime('-6 months'));
                    break;
                case 'yearly':
                    $start_date = date('Y-m-d', strtotime('-2 years'));
                    break;
                default:
                    $start_date = date('Y-m-d', strtotime('-30 days'));
            }
        }
        
        if (empty($end_date)) {
            $end_date = date('Y-m-d');
        }
        
        // Check cache first
        $cache_key = md5("metrics_{$metric_type}_{$period}_{$start_date}_{$end_date}");
        if (isset($this->metrics_cache[$cache_key]) && $this->metrics_cache[$cache_key]['expires'] > time()) {
            return $this->metrics_cache[$cache_key]['data'];
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_metrics';
        
        // Build the SQL query based on period
        $date_format = '';
        switch ($period) {
            case 'daily':
                $date_format = '%Y-%m-%d';
                break;
            case 'weekly':
                $date_format = '%Y-%u'; // Year-Week
                break;
            case 'monthly':
                $date_format = '%Y-%m';
                break;
            case 'yearly':
                $date_format = '%Y';
                break;
            default:
                $date_format = '%Y-%m-%d';
        }
        
        $query = $wpdb->prepare(
            "SELECT DATE_FORMAT(date_recorded, %s) as period_date, 
                    SUM(value) as total_value, 
                    COUNT(*) as count 
             FROM {$table_name} 
             WHERE metric_type = %s 
               AND date_recorded BETWEEN %s AND %s 
             GROUP BY period_date 
             ORDER BY period_date ASC",
            $date_format,
            $metric_type,
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        );
        
        $results = $wpdb->get_results($query);
        
        // Cache the results
        $this->metrics_cache[$cache_key] = array(
            'data' => $results,
            'expires' => time() + $this->cache_expiration
        );
        
        return $results;
    }
    
    /**
     * Get top items by metric
     *
     * @since 1.0.0
     * @param string $metric_type Type of metric
     * @param string $item_type Type of item (artwork, artist, etc.)
     * @param int $limit Number of items to return
     * @param int $days Number of days to look back
     * @return array Top items
     */
    public function get_top_items($metric_type, $item_type, $limit = 10, $days = 30) {
        // Check cache first
        $cache_key = md5("top_{$item_type}_{$metric_type}_{$limit}_{$days}");
        if (isset($this->metrics_cache[$cache_key]) && $this->metrics_cache[$cache_key]['expires'] > time()) {
            return $this->metrics_cache[$cache_key]['data'];
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_metrics';
        
        $object_id_field = 'object_id';
        if ($item_type === 'artist' && $metric_type !== 'artist_view') {
            // For artists, we need to join with posts to get the artist_id
            if ($metric_type === 'artwork_sale') {
                $query = $wpdb->prepare(
                    "SELECT pm.meta_value as item_id, 
                            SUM(m.value) as total_value, 
                            COUNT(*) as count 
                     FROM {$table_name} m 
                     JOIN {$wpdb->postmeta} pm ON m.object_id = pm.post_id 
                     WHERE m.metric_type = %s 
                       AND pm.meta_key = 'vortex_artist_id' 
                       AND m.date_recorded >= DATE_SUB(NOW(), INTERVAL %d DAY) 
                     GROUP BY pm.meta_value 
                     ORDER BY total_value DESC 
                     LIMIT %d",
                    $metric_type,
                    $days,
                    $limit
                );
            } else {
                $query = $wpdb->prepare(
                    "SELECT pm.meta_value as item_id, 
                            COUNT(*) as count 
                     FROM {$table_name} m 
                     JOIN {$wpdb->postmeta} pm ON m.object_id = pm.post_id 
                     WHERE m.metric_type = %s 
                       AND pm.meta_key = 'vortex_artist_id' 
                       AND m.date_recorded >= DATE_SUB(NOW(), INTERVAL %d DAY) 
                     GROUP BY pm.meta_value 
                     ORDER BY count DESC 
                     LIMIT %d",
                    $metric_type,
                    $days,
                    $limit
                );
            }
        } else {
            // Standard query for artwork or direct artist metrics
            if (in_array($metric_type, array('artwork_sale'))) {
                $query = $wpdb->prepare(
                    "SELECT object_id as item_id, 
                            SUM(value) as total_value, 
                            COUNT(*) as count 
                     FROM {$table_name} 
                     WHERE metric_type = %s 
                       AND date_recorded >= DATE_SUB(NOW(), INTERVAL %d DAY) 
                     GROUP BY object_id 
                     ORDER BY total_value DESC 
                     LIMIT %d",
                    $metric_type,
                    $days,
                    $limit
                );
            } else {
                $query = $wpdb->prepare(
                    "SELECT object_id as item_id, 
                            COUNT(*) as count 
                     FROM {$table_name} 
                     WHERE metric_type = %s 
                       AND date_recorded >= DATE_SUB(NOW(), INTERVAL %d DAY) 
                     GROUP BY object_id 
                     ORDER BY count DESC 
                     LIMIT %d",
                    $metric_type,
                    $days,
                    $limit
                );
            }
        }
        
        $results = $wpdb->get_results($query);
        
        // Add additional information based on item type
        foreach ($results as &$item) {
            if ($item_type === 'artwork') {
                $post = get_post($item->item_id);
                if ($post) {
                    $item->title = $post->post_title;
                    $item->url = get_permalink($post->ID);
                    $item->thumbnail = get_the_post_thumbnail_url($post->ID, 'thumbnail');
                    $item->artist_id = get_post_meta($post->ID, 'vortex_artist_id', true);
                    $item->artist_name = get_the_author_meta('display_name', $item->artist_id);
                }
            } elseif ($item_type === 'artist') {
                $user = get_userdata($item->item_id);
                if ($user) {
                    $item->name = $user->display_name;
                    $item->url = get_author_posts_url($user->ID);
                    $item->avatar = get_avatar_url($user->ID);
                }
            }
        }
        
        // Cache the results
        $this->metrics_cache[$cache_key] = array(
            'data' => $results,
            'expires' => time() + $this->cache_expiration
        );
        
        return $results;
    }
    
    /**
     * Get trending items with AI enhancement
     *
     * @since 1.0.0
     * @param string $item_type Type of item (artwork, artist, etc.)
     * @param int $limit Number of items to return
     * @return array Trending items
     */
    public function get_trending_items($item_type, $limit = 10) {
        // Check cache first
        $cache_key = md5("trending_{$item_type}_{$limit}");
        if (isset($this->metrics_cache[$cache_key]) && $this->metrics_cache[$cache_key]['expires'] > time()) {
            return $this->metrics_cache[$cache_key]['data'];
        }
        
        // Start with basic trending calculation (views + weight for recency)
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_metrics';
        
        if ($item_type === 'artwork') {
            $metric_type = 'artwork_view';
            $query = $wpdb->prepare(
                "SELECT object_id as item_id, 
                        COUNT(*) as count,
                        MAX(date_recorded) as latest_view
                 FROM {$table_name} 
                 WHERE metric_type = %s 
                   AND date_recorded >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                 GROUP BY object_id 
                 ORDER BY count DESC, latest_view DESC
                 LIMIT %d",
                $metric_type,
                $limit * 2 // Get more than needed for AI curation
            );
        } elseif ($item_type === 'artist') {
            $metric_type = 'artist_view';
            $query = $wpdb->prepare(
                "SELECT object_id as item_id, 
                        COUNT(*) as count,
                        MAX(date_recorded) as latest_view
                 FROM {$table_name} 
                 WHERE metric_type = %s 
                   AND date_recorded >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                 GROUP BY object_id 
                 ORDER BY count DESC, latest_view DESC
                 LIMIT %d",
                $metric_type,
                $limit * 2 // Get more than needed for AI curation
            );
        }
        
        $results = $wpdb->get_results($query);
        
        // Apply AI curation if agents are active
        if (!empty($results)) {
            if ($item_type === 'artwork' && $this->ai_agents['CLOE']['active']) {
                $results = $this->apply_cloe_curation($results, $item_type, $limit);
            } elseif ($item_type === 'artist' && $this->ai_agents['BusinessStrategist']['active']) {
                $results = $this->apply_businessstrategist_curation($results, $item_type, $limit);
            }
        }
        
        // Limit to requested number
        $results = array_slice($results, 0, $limit);
        
        // Add additional information
        foreach ($results as &$item) {
            if ($item_type === 'artwork') {
                $post = get_post($item->item_id);
                if ($post) {
                    $item->title = $post->post_title;
                    $item->url = get_permalink($post->ID);
                    $item->thumbnail = get_the_post_thumbnail_url($post->ID, 'thumbnail');
                    $item->artist_id = get_post_meta($post->ID, 'vortex_artist_id', true);
                    $item->artist_name = get_the_author_meta('display_name', $item->artist_id);
                }
            } elseif ($item_type === 'artist') {
                $user = get_userdata($item->item_id);
                if ($user) {
                    $item->name = $user->display_name;
                    $item->url = get_author_posts_url($user->ID);
                    $item->avatar = get_avatar_url($user->ID);
                }
            }
        }
        
        // Cache the results
        $this->metrics_cache[$cache_key] = array(
            'data' => $results,
            'expires' => time() + $this->cache_expiration
        );
        
        return $results;
    }
    
    /**
     * Apply CLOE curation to trending items
     *
     * @since 1.0.0
     * @param array $items Items to curate
     * @param string $item_type Type of item
     * @param int $limit Number of items to return
     * @return array Curated items
     */
    private function apply_cloe_curation($items, $item_type, $limit) {
        if (class_exists('VORTEX_CLOE')) {
            $cloe = VORTEX_CLOE::get_instance();
            $curated_items = $cloe->curate_trending_items($items, $item_type, $limit);
            if (!empty($curated_items)) {
                return $curated_items;
            }
        }
        return $items;
    }
    
    /**
     * Apply BusinessStrategist curation to trending items
     *
     * @since 1.0.0
     * @param array $items Items to curate
     * @param string $item_type Type of item
     * @param int $limit Number of items to return
     * @return array Curated items
     */
    private function apply_businessstrategist_curation($items, $item_type, $limit) {
        if (class_exists('VORTEX_BusinessStrategist')) {
            $bs = VORTEX_BusinessStrategist::get_instance();
            $curated_items = $bs->curate_trending_items($items, $item_type, $limit);
            if (!empty($curated_items)) {
                return $curated_items;
            }
        }
        return $items;
    }
    
    /**
     * Get sales metrics
     *
     * @since 1.0.0
     * @param string $period Period (daily, weekly, monthly, yearly)
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Sales metrics
     */
    public function get_sales_metrics($period = 'daily', $start_date = '', $end_date = '') {
        return $this->get_metrics('artwork_sale', $period, $start_date, $end_date);
    }
    
    /**
     * Get view metrics
     *
     * @since 1.0.0
     * @param string $view_type View type (artwork_view, artist_view)
     * @param string $period Period (daily, weekly, monthly, yearly)
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array View metrics
     */
    public function get_view_metrics($view_type = 'artwork_view', $period = 'daily', $start_date = '', $end_date = '') {
        return $this->get_metrics($view_type, $period, $start_date, $end_date);
    }
    
    /**
     * Run daily aggregation of metrics
     *
     * @since 1.0.0
     * @return void
     */
    public function aggregate_daily_metrics() {
        global $wpdb;
        $metrics_table = $wpdb->prefix . 'vortex_metrics';
        $aggregates_table = $wpdb->prefix . 'vortex_metrics_aggregates';
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Aggregate views
        $this->aggregate_metric_type('artwork_view', $yesterday);
        $this->aggregate_metric_type('artist_view', $yesterday);
        
        // Aggregate sales
        $this->aggregate_metric_type('artwork_sale', $yesterday);
        
        // Aggregate AI interactions
        $this->aggregate_metric_type('ai_interaction', $yesterday);
        
        // Trigger AI learning on the aggregated data
        $this->feed_aggregated_metrics_to_ai_agents($yesterday);
    }
    
    /**
     * Aggregate a specific metric type
     *
     * @since 1.0.0
     * @param string $metric_type Metric type
     * @param string $date Date to aggregate (Y-m-d)
     * @return void
     */
    private function aggregate_metric_type($metric_type, $date) {
        global $wpdb;
        $metrics_table = $wpdb->prefix . 'vortex_metrics';
        $aggregates_table = $wpdb->prefix . 'vortex_metrics_aggregates';
        
        // Check if already aggregated
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$aggregates_table} WHERE metric_type = %s AND date = %s",
            $metric_type,
            $date
        ));
        
        if ($exists) {
            return; // Already aggregated
        }
        
        // Get metrics for the day
        $metrics = $wpdb->get_results($wpdb->prepare(
            "SELECT COUNT(*) as count, SUM(value) as total_value 
             FROM {$metrics_table} 
             WHERE metric_type = %s 
               AND date_recorded BETWEEN %s AND %s",
            $metric_type,
            $date . ' 00:00:00',
            $date . ' 23:59:59'
        ));
        
        if (!empty($metrics) && isset($metrics[0])) {
            // Insert aggregate
            $wpdb->insert(
                $aggregates_table,
                array(
                    'metric_type' => $metric_type,
                    'date' => $date,
                    'count' => $metrics[0]->count,
                    'total_value' => $metrics[0]->total_value,
                    'created_at' => current_time('mysql')
                )
            );
        }
    }
    
    /**
     * Feed aggregated metrics to AI agents
     *
     * @since 1.0.0
     * @param string $date Date of aggregated metrics
     * @return void
     */
    private function feed_aggregated_metrics_to_ai_agents($date) {
        global $wpdb;
        $aggregates_table = $wpdb->prefix . 'vortex_metrics_aggregates';
        
        // Get all aggregates for the date
        $aggregates = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$aggregates_table} WHERE date = %s",
            $date
        ));
        
        if (empty($aggregates)) {
            return;
        }
        
        // Prepare data for AI agents
        $data_for_ai = array(
            'date' => $date,
            'metrics' => array()
        );
        
        foreach ($aggregates as $aggregate) {
            $data_for_ai['metrics'][$aggregate->metric_type] = array(
                'count' => $aggregate->count,
                'total_value' => $aggregate->total_value
            );
        }
        
        // Feed to HURAII
        if ($this->ai_agents['HURAII']['active']) {
            do_action('vortex_ai_agent_learn', 'HURAII', 'daily_metrics_aggregate', $data_for_ai);
        }
        
        // Feed to CLOE
        if ($this->ai_agents['CLOE']['active']) {
            do_action('vortex_ai_agent_learn', 'CLOE', 'daily_metrics_aggregate', $data_for_ai);
        }
        
        // Feed to BusinessStrategist
        if ($this->ai_agents['BusinessStrategist']['active']) {
            do_action('vortex_ai_agent_learn', 'BusinessStrategist', 'daily_metrics_aggregate', $data_for_ai);
        }
    }
}

// Initialize the metrics system
add_action('plugins_loaded', function() {
    VORTEX_Metrics::get_instance();
}); 