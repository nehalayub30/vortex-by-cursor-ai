<?php
/**
 * VORTEX Metrics System
 * Handles marketplace metrics with AI agent integration
 *
 * @package VORTEX
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Vortex_Metrics {
    private $ai_manager;
    private $db;
    private $cache_group = 'vortex_metrics';
    private $cache_expiry = 1800; // 30 minutes
    private $blockchain_manager;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->blockchain_manager = new VORTEX_Blockchain_Manager();
        $this->init();
    }

    /**
     * Initialize metrics system
     */
    private function init() {
        try {
            $this->ai_manager = VORTEX_AI_Manager::get_instance();
            $this->setup_hooks();
            $this->initialize_metrics_tables();
        } catch (Exception $e) {
            $this->log_error('Initialization failed', $e);
        }
    }

    /**
     * Initialize metrics tables
     * Creates the necessary database tables if they don't exist
     */
    private function initialize_metrics_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Main metrics table
        $table_name = $wpdb->prefix . 'vortex_metrics';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            metric_name varchar(255) NOT NULL,
            metric_value longtext NOT NULL,
            metric_date datetime NOT NULL,
            user_id bigint(20) NOT NULL DEFAULT 0,
            context longtext,
            PRIMARY KEY  (id),
            KEY metric_name (metric_name),
            KEY metric_date (metric_date),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Metrics aggregation table (for faster dashboard queries)
        $agg_table_name = $wpdb->prefix . 'vortex_metrics_aggregated';
        
        $sql .= "CREATE TABLE IF NOT EXISTS $agg_table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            metric_name varchar(255) NOT NULL,
            aggregate_type varchar(50) NOT NULL,
            time_period varchar(50) NOT NULL,
            period_start datetime NOT NULL,
            period_end datetime NOT NULL,
            value longtext NOT NULL,
            last_updated datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY metric_period (metric_name,aggregate_type,time_period,period_start),
            KEY period_start (period_start),
            KEY period_end (period_end)
        ) $charset_collate;";
        
        // Execute SQL
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Schedule weekly maintenance if not already scheduled
        if (!wp_next_scheduled('vortex_weekly_maintenance')) {
            wp_schedule_event(time(), 'weekly', 'vortex_weekly_maintenance');
        }
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        add_action('vortex_hourly_metrics_update', array($this, 'update_metrics'));
        add_action('vortex_daily_metrics_analysis', array($this, 'analyze_metrics'));
        add_action('vortex_artwork_sold', array($this, 'track_sale_metrics'), 10, 2);
        add_filter('vortex_dashboard_metrics', array($this, 'get_dashboard_metrics'));
        add_action('vortex_weekly_maintenance', array($this, 'cleanup_old_metrics'));
    }

    /**
     * Get marketplace metrics
     */
    public function get_metrics($view = 'summary', $limit = 10, $days = 30) {
        switch ($view) {
            case 'artists':
                return $this->get_artist_metrics($limit, $days);
            
            case 'categories':
                return $this->get_category_metrics($limit, $days);
                
            case 'transactions':
                return $this->get_transaction_metrics($limit, $days);
                
            case 'blockchain':
                return $this->get_blockchain_metrics($limit);
                
            case 'summary':
            default:
                return $this->get_summary_metrics($days);
        }
    }

    /**
     * Get summary metrics
     */
    private function get_summary_metrics($days) {
        global $wpdb;
        
        $date_threshold = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        // Total artworks
        $total_artworks = wp_count_posts('vortex_artwork')->publish;
        
        // New artworks in period
        $new_artworks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->posts 
            WHERE post_type = 'vortex_artwork' 
            AND post_status = 'publish' 
            AND post_date >= %s",
            $date_threshold
        ));
        
        // Total artists (users who have published artworks)
        $total_artists = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_author) 
            FROM $wpdb->posts 
            WHERE post_type = 'vortex_artwork' 
            AND post_status = 'publish'"
        );
        
        // Total sales count
        $total_sales = $wpdb->get_var(
            "SELECT COUNT(*) FROM $wpdb->posts 
            WHERE post_type = 'vortex_transaction' 
            AND post_status = 'publish'"
        );
        
        // Sales in period
        $period_sales = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->posts 
            WHERE post_type = 'vortex_transaction' 
            AND post_status = 'publish' 
            AND post_date >= %s",
            $date_threshold
        ));
        
        // Total sales value
        $total_value = $wpdb->get_var(
            "SELECT SUM(meta_value) FROM $wpdb->postmeta 
            WHERE meta_key = 'vortex_transaction_amount' 
            AND post_id IN (
                SELECT ID FROM $wpdb->posts 
                WHERE post_type = 'vortex_transaction' 
                AND post_status = 'publish'
            )"
        );
        
        // Period sales value
        $period_value = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(pm.meta_value) FROM $wpdb->postmeta pm
            JOIN $wpdb->posts p ON pm.post_id = p.ID
            WHERE pm.meta_key = 'vortex_transaction_amount'
            AND p.post_type = 'vortex_transaction'
            AND p.post_status = 'publish'
            AND p.post_date >= %s",
            $date_threshold
        ));
        
        // Blockchain metrics
        $blockchain_data = $this->blockchain_manager->get_blockchain_metrics();
        
        return array(
            'total_artworks' => $total_artworks,
            'new_artworks' => $new_artworks,
            'total_artists' => $total_artists,
            'total_sales' => $total_sales,
            'period_sales' => $period_sales,
            'total_value' => floatval($total_value),
            'period_value' => floatval($period_value),
            'blockchain' => $blockchain_data,
            'period_days' => $days
        );
    }

    /**
     * Get artist metrics
     */
    private function get_artist_metrics($limit, $days) {
        global $wpdb;
        
        $date_threshold = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        // Most active artists (by number of artworks)
        $most_active = $wpdb->get_results($wpdb->prepare(
            "SELECT p.post_author as user_id, COUNT(*) as artwork_count 
            FROM $wpdb->posts p
            WHERE p.post_type = 'vortex_artwork' 
            AND p.post_status = 'publish' 
            AND p.post_date >= %s
            GROUP BY p.post_author
            ORDER BY artwork_count DESC
            LIMIT %d",
            $date_threshold,
            $limit
        ));
        
        // Add user info
        foreach ($most_active as $index => $artist) {
            $user = get_userdata($artist->user_id);
            if ($user) {
                $most_active[$index]->display_name = $user->display_name;
                $most_active[$index]->username = $user->user_login;
                $most_active[$index]->avatar = get_avatar_url($artist->user_id);
            }
        }
        
        // Top selling artists (by sales value)
        $top_selling = $wpdb->get_results($wpdb->prepare(
            "SELECT pm2.meta_value as artist_id, SUM(pm1.meta_value) as total_sales 
            FROM $wpdb->postmeta pm1
            JOIN $wpdb->posts p ON pm1.post_id = p.ID
            JOIN $wpdb->postmeta pm2 ON p.ID = pm2.post_id
            WHERE pm1.meta_key = 'vortex_transaction_amount'
            AND pm2.meta_key = 'vortex_artwork_author'
            AND p.post_type = 'vortex_transaction'
            AND p.post_status = 'publish'
            AND p.post_date >= %s
            GROUP BY pm2.meta_value
            ORDER BY total_sales DESC
            LIMIT %d",
            $date_threshold,
            $limit
        ));
        
        // Add user info
        foreach ($top_selling as $index => $artist) {
            $user = get_userdata($artist->artist_id);
            if ($user) {
                $top_selling[$index]->display_name = $user->display_name;
                $top_selling[$index]->username = $user->user_login;
                $top_selling[$index]->avatar = get_avatar_url($artist->artist_id);
                $top_selling[$index]->total_sales = floatval($artist->total_sales);
            }
        }
        
        // Most popular artists (by views)
        $most_viewed = $wpdb->get_results($wpdb->prepare(
            "SELECT p.post_author as user_id, SUM(pm.meta_value) as total_views 
            FROM $wpdb->posts p
            JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
            WHERE p.post_type = 'vortex_artwork'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'vortex_view_count'
            AND p.post_date >= %s
            GROUP BY p.post_author
            ORDER BY total_views DESC
            LIMIT %d",
            $date_threshold,
            $limit
        ));
        
        // Add user info
        foreach ($most_viewed as $index => $artist) {
            $user = get_userdata($artist->user_id);
            if ($user) {
                $most_viewed[$index]->display_name = $user->display_name;
                $most_viewed[$index]->username = $user->user_login;
                $most_viewed[$index]->avatar = get_avatar_url($artist->user_id);
                $most_viewed[$index]->total_views = intval($artist->total_views);
            }
        }
        
        // Blockchain metrics for artists
        $blockchain_artists = $this->blockchain_manager->get_most_active_artists($limit);
        
        return array(
            'most_active' => $most_active,
            'top_selling' => $top_selling,
            'most_viewed' => $most_viewed,
            'blockchain' => $blockchain_artists,
            'period_days' => $days
        );
    }

    /**
     * Get category metrics
     */
    private function get_category_metrics($limit, $days) {
        global $wpdb;
        
        $date_threshold = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        // Most popular categories (by artwork count)
        $popular_categories = $wpdb->get_results($wpdb->prepare(
            "SELECT t.term_id, t.name, COUNT(tr.object_id) as artwork_count
            FROM $wpdb->terms t
            JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id
            JOIN $wpdb->term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            JOIN $wpdb->posts p ON tr.object_id = p.ID
            WHERE tt.taxonomy = 'vortex_category'
            AND p.post_type = 'vortex_artwork'
            AND p.post_status = 'publish'
            AND p.post_date >= %s
            GROUP BY t.term_id
            ORDER BY artwork_count DESC
            LIMIT %d",
            $date_threshold,
            $limit
        ));
        
        // Top selling categories (by sales value)
        $top_selling = $wpdb->get_results($wpdb->prepare(
            "SELECT t.term_id, t.name, SUM(pm.meta_value) as total_sales
            FROM $wpdb->terms t
            JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id
            JOIN $wpdb->term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            JOIN $wpdb->postmeta pm ON tr.object_id = pm.post_id
            JOIN $wpdb->posts p ON tr.object_id = p.ID
            WHERE tt.taxonomy = 'vortex_category'
            AND p.post_type = 'vortex_artwork'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'vortex_total_sales'
            AND p.post_date >= %s
            GROUP BY t.term_id
            ORDER BY total_sales DESC
            LIMIT %d",
            $date_threshold,
            $limit
        ));
        
        foreach ($top_selling as $index => $category) {
            $top_selling[$index]->total_sales = floatval($category->total_sales);
        }
        
        // Most viewed categories
        $most_viewed = $wpdb->get_results($wpdb->prepare(
            "SELECT t.term_id, t.name, SUM(pm.meta_value) as total_views
            FROM $wpdb->terms t
            JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id
            JOIN $wpdb->term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            JOIN $wpdb->postmeta pm ON tr.object_id = pm.post_id
            JOIN $wpdb->posts p ON tr.object_id = p.ID
            WHERE tt.taxonomy = 'vortex_category'
            AND p.post_type = 'vortex_artwork'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'vortex_view_count'
            AND p.post_date >= %s
            GROUP BY t.term_id
            ORDER BY total_views DESC
            LIMIT %d",
            $date_threshold,
            $limit
        ));
        
        foreach ($most_viewed as $index => $category) {
            $most_viewed[$index]->total_views = intval($category->total_views);
        }
        
        // Blockchain metrics for categories
        $blockchain_categories = $this->blockchain_manager->get_popular_categories($limit);

            return array(
            'popular_categories' => $popular_categories,
            'top_selling' => $top_selling,
            'most_viewed' => $most_viewed,
            'blockchain' => $blockchain_categories,
            'period_days' => $days
        );
    }

    /**
     * Get transaction metrics
     */
    private function get_transaction_metrics($limit, $days) {
        global $wpdb;
        
        $date_threshold = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        // Recent transactions
        $recent_transactions = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_date, pm1.meta_value as amount, pm2.meta_value as artwork_id, 
            pm3.meta_value as buyer_id, pm4.meta_value as seller_id
            FROM $wpdb->posts p
            LEFT JOIN $wpdb->postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'vortex_transaction_amount'
            LEFT JOIN $wpdb->postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'vortex_transaction_artwork'
            LEFT JOIN $wpdb->postmeta pm3 ON p.ID = pm3.post_id AND pm3.meta_key = 'vortex_transaction_buyer'
            LEFT JOIN $wpdb->postmeta pm4 ON p.ID = pm4.post_id AND pm4.meta_key = 'vortex_transaction_seller'
            WHERE p.post_type = 'vortex_transaction'
            AND p.post_status = 'publish'
            AND p.post_date >= %s
            ORDER BY p.post_date DESC
            LIMIT %d",
            $date_threshold,
            $limit
        ));
        
        // Format transaction data
        foreach ($recent_transactions as $index => $transaction) {
            $recent_transactions[$index]->amount = floatval($transaction->amount);
            
            // Get artwork title
            $artwork = get_post($transaction->artwork_id);
            $recent_transactions[$index]->artwork_title = $artwork ? $artwork->post_title : 'Unknown Artwork';
            
            // Get buyer info
            $buyer = get_userdata($transaction->buyer_id);
            $recent_transactions[$index]->buyer_name = $buyer ? $buyer->display_name : 'Unknown Buyer';
            
            // Get seller info
            $seller = get_userdata($transaction->seller_id);
            $recent_transactions[$index]->seller_name = $seller ? $seller->display_name : 'Unknown Seller';
            
            // Format date
            $recent_transactions[$index]->formatted_date = date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'), 
                strtotime($transaction->post_date)
            );
        }
        
        // Transaction volume by day
        $daily_volume = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(p.post_date) as date, COUNT(*) as count, SUM(pm.meta_value) as volume
            FROM $wpdb->posts p
            JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'vortex_transaction_amount'
            WHERE p.post_type = 'vortex_transaction'
            AND p.post_status = 'publish'
            AND p.post_date >= %s
            GROUP BY DATE(p.post_date)
            ORDER BY date DESC
            LIMIT %d",
            $date_threshold,
            $days
        ));
        
        foreach ($daily_volume as $index => $day) {
            $daily_volume[$index]->volume = floatval($day->volume);
            $daily_volume[$index]->count = intval($day->count);
        }
        
        // Highest value transactions
        $highest_value = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_date, pm1.meta_value as amount, pm2.meta_value as artwork_id, 
            pm3.meta_value as buyer_id, pm4.meta_value as seller_id
            FROM $wpdb->posts p
            LEFT JOIN $wpdb->postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'vortex_transaction_amount'
            LEFT JOIN $wpdb->postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'vortex_transaction_artwork'
            LEFT JOIN $wpdb->postmeta pm3 ON p.ID = pm3.post_id AND pm3.meta_key = 'vortex_transaction_buyer'
            LEFT JOIN $wpdb->postmeta pm4 ON p.ID = pm4.post_id AND pm4.meta_key = 'vortex_transaction_seller'
            WHERE p.post_type = 'vortex_transaction'
            AND p.post_status = 'publish'
            AND p.post_date >= %s
            ORDER BY pm1.meta_value+0 DESC
            LIMIT %d",
            $date_threshold,
            $limit
        ));
        
        // Format transaction data
        foreach ($highest_value as $index => $transaction) {
            $highest_value[$index]->amount = floatval($transaction->amount);
            
            // Get artwork title
            $artwork = get_post($transaction->artwork_id);
            $highest_value[$index]->artwork_title = $artwork ? $artwork->post_title : 'Unknown Artwork';
            
            // Get buyer info
            $buyer = get_userdata($transaction->buyer_id);
            $highest_value[$index]->buyer_name = $buyer ? $buyer->display_name : 'Unknown Buyer';
            
            // Get seller info
            $seller = get_userdata($transaction->seller_id);
            $highest_value[$index]->seller_name = $seller ? $seller->display_name : 'Unknown Seller';
            
            // Format date
            $highest_value[$index]->formatted_date = date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'), 
                strtotime($transaction->post_date)
            );
        }
        
        return array(
            'recent_transactions' => $recent_transactions,
            'daily_volume' => $daily_volume,
            'highest_value' => $highest_value,
            'period_days' => $days
        );
    }

    /**
     * Get blockchain-specific metrics
     */
    private function get_blockchain_metrics($limit) {
        // Call blockchain manager to get data from TOLA
        $summary = $this->blockchain_manager->get_blockchain_metrics();
        $artists = $this->blockchain_manager->get_most_active_artists($limit);
        $categories = $this->blockchain_manager->get_popular_categories($limit);
        
        return array(
            'summary' => $summary,
            'artists' => $artists,
            'categories' => $categories
        );
    }

    /**
     * Track sale metrics
     */
    public function track_sale_metrics($artwork_id, $sale_data) {
        try {
            // Validate sale data
            $this->validate_sale_data($sale_data);

            // Record sale metrics
            $this->record_sale($artwork_id, $sale_data);

            // Get AI analysis of sale
            $ai_analysis = $this->ai_manager->analyze_sale($artwork_id, $sale_data);

            // Update market trends
            $this->update_market_trends($ai_analysis);

            // Track for AI learning
            $this->track_metric_event('sale', array(
                'artwork_id' => $artwork_id,
                'sale_data' => $sale_data,
                'analysis' => $ai_analysis
            ));

        } catch (Exception $e) {
            $this->log_error('Sale tracking failed', $e);
        }
    }

    /**
     * Analyze metrics with AI
     */
    public function analyze_metrics() {
        try {
            // Get current metrics
            $metrics = $this->get_metrics('all', '30days');

            // Get AI analysis
            $analysis = array(
                'huraii' => $this->ai_manager->get_agent('huraii')
                    ->analyze_market_trends($metrics),
                'cloe' => $this->ai_manager->get_agent('cloe')
                    ->analyze_user_behavior($metrics),
                'business' => $this->ai_manager->get_agent('business_strategist')
                    ->analyze_market_performance($metrics)
            );

            // Update insights
            $this->update_market_insights($analysis);

            // Track for AI learning
            $this->track_metric_event('analysis', $analysis);

        } catch (Exception $e) {
            $this->log_error('Metrics analysis failed', $e);
        }
    }

    /**
     * Get dashboard metrics
     */
    public function get_dashboard_metrics($user_id = 0) {
        try {
            $cache_key = "dashboard_metrics_" . ($user_id ?: 'global');
            $metrics = wp_cache_get($cache_key, $this->cache_group);

            if (false === $metrics) {
                // Get base metrics
                $metrics = $this->calculate_dashboard_metrics($user_id);

                // Get AI recommendations
                $recommendations = $this->get_ai_recommendations($user_id, $metrics);

                // Merge recommendations with metrics
                $metrics['recommendations'] = $recommendations;

                wp_cache_set($cache_key, $metrics, $this->cache_group, $this->cache_expiry);
            }

            return $metrics;

        } catch (Exception $e) {
            $this->log_error('Dashboard metrics retrieval failed', $e);
            return array();
        }
    }

    /**
     * Track metric event for AI learning
     */
    private function track_metric_event($type, $data) {
        try {
            $this->ai_manager->track_event('metric_event', array(
                'type' => $type,
                'data' => $data,
                'timestamp' => current_time('timestamp')
            ));
        } catch (Exception $e) {
            $this->log_error('Event tracking failed', $e);
        }
    }

    /**
     * Utility functions
     */
    private function get_period_start_date($period) {
        switch ($period) {
            case '7days':
                return strtotime('-7 days');
            case '30days':
                return strtotime('-30 days');
            case '90days':
                return strtotime('-90 days');
            case '1year':
                return strtotime('-1 year');
            default:
                return strtotime('-30 days');
        }
    }

    /**
     * Validation
     */
    private function validate_sale_data($data) {
        if (empty($data['price']) || !is_numeric($data['price'])) {
            throw new Exception(__('Invalid sale price', 'vortex'));
        }

        if (empty($data['buyer_id'])) {
            throw new Exception(__('Invalid buyer information', 'vortex'));
        }

        return true;
    }

    /**
     * Store a metric in the database
     *
     * @param string $metric_name The name of the metric
     * @param mixed $metric_value The value of the metric
     * @param array $context Additional contextual data
     * @return int|bool ID of the stored metric or false on failure
     */
    public function store_metric($metric_name, $metric_value, $context = array()) {
        global $wpdb;
        
        if (empty($metric_name)) {
            return false;
        }
        
        try {
            // Prepare data for insertion
            $metric_data = array(
                'metric_name' => sanitize_text_field($metric_name),
                'metric_value' => is_numeric($metric_value) ? $metric_value : json_encode($metric_value),
                'metric_date' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'context' => !empty($context) ? json_encode($context) : ''
            );
            
            // Insert into database
            $result = $wpdb->insert(
                $wpdb->prefix . 'vortex_metrics',
                $metric_data,
                array(
                    '%s', // metric_name
                    '%s', // metric_value
                    '%s', // metric_date
                    '%d', // user_id
                    '%s'  // context
                )
            );
            
            if ($result) {
                // Log for AI analysis if relevant
                if (!empty($context['track_for_ai'])) {
                    $this->track_metric_event('new_metric', array(
                        'metric_name' => $metric_name,
                        'metric_value' => $metric_value,
                        'context' => $context
                    ));
                }
                
                return $wpdb->insert_id;
            }
            
            return false;
        } catch (Exception $e) {
            $this->log_error('Failed to store metric', $e);
            return false;
        }
    }

    /**
     * Error logging
     */
    private function log_error($message, $error) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[VORTEX Metrics] %s: %s',
                $message,
                $error->getMessage()
            ));
        }
    }
    
    /**
     * Cleanup old metrics data
     * 
     * @param int $days Number of days to keep metrics (default: 90)
     * @return bool True on success, false on failure
     */
    public function cleanup_old_metrics($days = 90) {
        global $wpdb;
        
        try {
            // Calculate the cutoff date
            $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            
            // Delete old metrics from main metrics table
            $metrics_deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}vortex_metrics WHERE metric_date < %s",
                $cutoff_date
            ));
            
            // Delete old aggregated metrics that are no longer relevant
            $agg_deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}vortex_metrics_aggregated WHERE period_end < %s",
                $cutoff_date
            ));
            
            // Log results
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    '[VORTEX Metrics] Cleaned up old metrics: %d metrics and %d aggregated metrics deleted',
                    $metrics_deleted !== false ? $metrics_deleted : 0,
                    $agg_deleted !== false ? $agg_deleted : 0
                ));
            }
            
            // Track the cleanup event
            $this->store_metric('metrics_cleanup', array(
                'metrics_deleted' => $metrics_deleted !== false ? $metrics_deleted : 0,
                'aggregated_deleted' => $agg_deleted !== false ? $agg_deleted : 0,
                'cutoff_date' => $cutoff_date,
                'days_kept' => $days
            ));
            
            // Run cleanup on related data if needed
            $this->cleanup_related_data($cutoff_date);
            
            return true;
        } catch (Exception $e) {
            $this->log_error('Metrics cleanup failed', $e);
            return false;
        }
    }
    
    /**
     * Clean up related metrics data
     * 
     * @param string $cutoff_date MySQL formatted date
     * @return void
     */
    private function cleanup_related_data($cutoff_date) {
        global $wpdb;
        
        // Example: Clean up page view logs
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}vortex_page_views'") == "{$wpdb->prefix}vortex_page_views") {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}vortex_page_views WHERE timestamp < %s",
                $cutoff_date
            ));
        }
        
        // Example: Clean up event logs
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}vortex_events'") == "{$wpdb->prefix}vortex_events") {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}vortex_events WHERE timestamp < %s",
                $cutoff_date
            ));
        }
        
        // Apply any other necessary cleanups for related tables
    }
}

// Initialize the metrics system
new Vortex_Metrics(); 