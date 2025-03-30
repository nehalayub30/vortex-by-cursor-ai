<?php
/**
 * The Rankings API functionality of the plugin.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The Rankings API class.
 *
 * This class handles the calculation, storage, and retrieval of rankings for
 * artists, artworks, and other entities in the marketplace.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Rankings_API {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Database tables used by this class.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $tables    Database table names.
     */
    private $tables;

    /**
     * Metrics API instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Metrics_API    $metrics_api    Metrics API instance.
     */
    private $metrics_api;

    /**
     * Rankings cache expiration time in seconds.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $cache_expiration    Cache expiration time.
     */
    private $cache_expiration;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        global $wpdb;
        $this->tables = [
            'artist_rankings' => $wpdb->prefix . 'vortex_artist_rankings',
            'artwork_rankings' => $wpdb->prefix . 'vortex_artwork_rankings',
            'tag_rankings' => $wpdb->prefix . 'vortex_tag_rankings',
            'model_rankings' => $wpdb->prefix . 'vortex_model_rankings',
            'artist_stats' => $wpdb->prefix . 'vortex_artist_stats',
            'artwork_stats' => $wpdb->prefix . 'vortex_artwork_stats',
            'artists' => $wpdb->prefix . 'vortex_artists',
            'artworks' => $wpdb->prefix . 'vortex_artworks',
            'sales' => $wpdb->prefix . 'vortex_sales',
        ];
        
        // Default cache expiration: 6 hours
        $this->cache_expiration = 6 * HOUR_IN_SECONDS;
        
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_api_endpoints'));
        
        // Register admin AJAX endpoints
        add_action('wp_ajax_vortex_get_rankings', array($this, 'ajax_get_rankings'));
        add_action('wp_ajax_nopriv_vortex_get_rankings', array($this, 'ajax_get_rankings'));
        add_action('wp_ajax_vortex_recalculate_rankings', array($this, 'ajax_recalculate_rankings'));
        
        // Schedule rankings updates
        add_action('vortex_daily_rankings_update', array($this, 'update_all_rankings'));
    }

    /**
     * Set the Metrics API instance.
     *
     * @since    1.0.0
     * @param    Vortex_Metrics_API    $metrics_api    Metrics API instance.
     */
    public function set_metrics_api($metrics_api) {
        $this->metrics_api = $metrics_api;
    }

    /**
     * Register REST API endpoints.
     *
     * @since    1.0.0
     */
    public function register_api_endpoints() {
        register_rest_route('vortex/v1', '/rankings/artists', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_artist_rankings'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route('vortex/v1', '/rankings/artworks', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_artwork_rankings'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route('vortex/v1', '/rankings/tags', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_tag_rankings'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route('vortex/v1', '/rankings/models', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_model_rankings'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Get artist rankings via API.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request Full data about the request.
     * @return   WP_REST_Response
     */
    public function api_get_artist_rankings($request) {
        $period = $request->get_param('period') ?: 'week';
        $limit = intval($request->get_param('limit') ?: 10);
        $offset = intval($request->get_param('offset') ?: 0);
        $sort_by = $request->get_param('sort_by') ?: 'score';
        
        $rankings = $this->get_artist_rankings($period, $limit, $offset, $sort_by);
        
        return new WP_REST_Response($rankings, 200);
    }

    /**
     * Get artwork rankings via API.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request Full data about the request.
     * @return   WP_REST_Response
     */
    public function api_get_artwork_rankings($request) {
        $period = $request->get_param('period') ?: 'week';
        $limit = intval($request->get_param('limit') ?: 10);
        $offset = intval($request->get_param('offset') ?: 0);
        $sort_by = $request->get_param('sort_by') ?: 'score';
        
        $rankings = $this->get_artwork_rankings($period, $limit, $offset, $sort_by);
        
        return new WP_REST_Response($rankings, 200);
    }

    /**
     * Get tag rankings via API.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request Full data about the request.
     * @return   WP_REST_Response
     */
    public function api_get_tag_rankings($request) {
        $period = $request->get_param('period') ?: 'week';
        $limit = intval($request->get_param('limit') ?: 20);
        $offset = intval($request->get_param('offset') ?: 0);
        
        $rankings = $this->get_tag_rankings($period, $limit, $offset);
        
        return new WP_REST_Response($rankings, 200);
    }

    /**
     * Get model rankings via API.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request Full data about the request.
     * @return   WP_REST_Response
     */
    public function api_get_model_rankings($request) {
        $period = $request->get_param('period') ?: 'week';
        $limit = intval($request->get_param('limit') ?: 10);
        $offset = intval($request->get_param('offset') ?: 0);
        
        $rankings = $this->get_model_rankings($period, $limit, $offset);
        
        return new WP_REST_Response($rankings, 200);
    }

    /**
     * Handle AJAX request for rankings.
     *
     * @since    1.0.0
     */
    public function ajax_get_rankings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_public_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
        }
        
        $ranking_type = isset($_POST['ranking_type']) ? sanitize_text_field($_POST['ranking_type']) : 'artists';
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'week';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'score';
        
        $rankings = array();
        
        switch ($ranking_type) {
            case 'artists':
                $rankings = $this->get_artist_rankings($period, $limit, $offset, $sort_by);
                break;
            
            case 'artworks':
                $rankings = $this->get_artwork_rankings($period, $limit, $offset, $sort_by);
                break;
            
            case 'tags':
                $rankings = $this->get_tag_rankings($period, $limit, $offset);
                break;
            
            case 'models':
                $rankings = $this->get_model_rankings($period, $limit, $offset);
                break;
            
            default:
                wp_send_json_error(array('message' => __('Invalid ranking type.', 'vortex-ai-marketplace')));
                break;
        }
        
        wp_send_json_success($rankings);
    }

    /**
     * Handle AJAX request for recalculating rankings.
     *
     * @since    1.0.0
     */
    public function ajax_recalculate_rankings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'vortex-ai-marketplace')));
        }
        
        $ranking_type = isset($_POST['ranking_type']) ? sanitize_text_field($_POST['ranking_type']) : 'all';
        
        switch ($ranking_type) {
            case 'artists':
                $this->calculate_artist_rankings();
                break;
            
            case 'artworks':
                $this->calculate_artwork_rankings();
                break;
            
            case 'tags':
                $this->calculate_tag_rankings();
                break;
            
            case 'models':
                $this->calculate_model_rankings();
                break;
            
            case 'all':
                $this->update_all_rankings();
                break;
            
            default:
                wp_send_json_error(array('message' => __('Invalid ranking type.', 'vortex-ai-marketplace')));
                break;
        }
        
        wp_send_json_success(array('message' => __('Rankings recalculated successfully.', 'vortex-ai-marketplace')));
    }

    /**
     * Update all rankings.
     *
     * @since    1.0.0
     */
    public function update_all_rankings() {
        $this->calculate_artist_rankings();
        $this->calculate_artwork_rankings();
        $this->calculate_tag_rankings();
        $this->calculate_model_rankings();
        
        // Clear all ranking caches
        $this->clear_rankings_cache();
        
        // Log the update
        error_log('All rankings updated at ' . current_time('mysql'));
    }

    /**
     * Get artist rankings.
     *
     * @since    1.0.0
     * @param    string    $period     The period for rankings (day, week, month, year).
     * @param    int       $limit      The number of results to return.
     * @param    int       $offset     The offset for pagination.
     * @param    string    $sort_by    The field to sort by (score, sales, revenue, followers).
     * @return   array     The artist rankings.
     */
    public function get_artist_rankings($period = 'week', $limit = 10, $offset = 0, $sort_by = 'score') {
        $cache_key = 'vortex_artist_rankings_' . $period . '_' . $limit . '_' . $offset . '_' . $sort_by;
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        global $wpdb;
        
        $rankings_table = $this->tables['artist_rankings'];
        $artists_table = $this->tables['artists'];
        
        // Validate period
        $valid_periods = array('day', 'week', 'month', 'year');
        if (!in_array($period, $valid_periods)) {
            $period = 'week';
        }
        
        // Validate sort_by
        $valid_sort_fields = array('score', 'sales', 'revenue', 'followers', 'views');
        if (!in_array($sort_by, $valid_sort_fields)) {
            $sort_by = 'score';
        }
        
        // Get rankings
        $rankings = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, a.user_id, a.display_name, a.bio, a.profile_image, a.website, a.social_links, a.verified,
            (SELECT COUNT(*) FROM {$this->tables['artworks']} WHERE artist_id = r.artist_id) as artwork_count
            FROM {$rankings_table} r
            INNER JOIN {$artists_table} a ON r.artist_id = a.artist_id
            WHERE r.period = %s AND a.status = 'active'
            ORDER BY r.{$sort_by} DESC
            LIMIT %d OFFSET %d",
            $period,
            $limit,
            $offset
        ));
        
        // Process rankings
        $result = array(
            'period' => $period,
            'sort_by' => $sort_by,
            'total' => $this->get_active_artists_count(),
            'items' => $rankings,
        );
        
        // Cache the data
        set_transient($cache_key, $result, $this->cache_expiration);
        
        return $result;
    }

    /**
     * Get artwork rankings.
     *
     * @since    1.0.0
     * @param    string    $period     The period for rankings (day, week, month, year).
     * @param    int       $limit      The number of results to return.
     * @param    int       $offset     The offset for pagination.
     * @param    string    $sort_by    The field to sort by (score, sales, revenue, views, likes).
     * @return   array     The artwork rankings.
     */
    public function get_artwork_rankings($period = 'week', $limit = 10, $offset = 0, $sort_by = 'score') {
        $cache_key = 'vortex_artwork_rankings_' . $period . '_' . $limit . '_' . $offset . '_' . $sort_by;
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        global $wpdb;
        
        $rankings_table = $this->tables['artwork_rankings'];
        $artworks_table = $this->tables['artworks'];
        
        // Validate period
        $valid_periods = array('day', 'week', 'month', 'year');
        if (!in_array($period, $valid_periods)) {
            $period = 'week';
        }
        
        // Validate sort_by
        $valid_sort_fields = array('score', 'sales', 'revenue', 'views', 'likes');
        if (!in_array($sort_by, $valid_sort_fields)) {
            $sort_by = 'score';
        }
        
        // Get rankings
        $rankings = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, a.title, a.description, a.thumbnail, a.price, a.artist_id, a.is_minted, a.is_for_sale, 
            art.display_name as artist_name, art.profile_image as artist_image
            FROM {$rankings_table} r
            INNER JOIN {$artworks_table} a ON r.artwork_id = a.artwork_id
            INNER JOIN {$this->tables['artists']} art ON a.artist_id = art.artist_id
            WHERE r.period = %s AND a.status = 'active'
            ORDER BY r.{$sort_by} DESC
            LIMIT %d OFFSET %d",
            $period,
            $limit,
            $offset
        ));
        
        // Process rankings
        $result = array(
            'period' => $period,
            'sort_by' => $sort_by,
            'total' => $this->get_active_artworks_count(),
            'items' => $rankings,
        );
        
        // Cache the data
        set_transient($cache_key, $result, $this->cache_expiration);
        
        return $result;
    }

    /**
     * Get tag rankings.
     *
     * @since    1.0.0
     * @param    string    $period    The period for rankings (day, week, month, year).
     * @param    int       $limit     The number of results to return.
     * @param    int       $offset    The offset for pagination.
     * @return   array     The tag rankings.
     */
    public function get_tag_rankings($period = 'week', $limit = 20, $offset = 0) {
        $cache_key = 'vortex_tag_rankings_' . $period . '_' . $limit . '_' . $offset;
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        global $wpdb;
        
        $rankings_table = $this->tables['tag_rankings'];
        
        // Validate period
        $valid_periods = array('day', 'week', 'month', 'year');
        if (!in_array($period, $valid_periods)) {
            $period = 'week';
        }
        
        // Get rankings
        $rankings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$rankings_table}
            WHERE period = %s
            ORDER BY count DESC
            LIMIT %d OFFSET %d",
            $period,
            $limit,
            $offset
        ));
        
        // Process rankings
        $result = array(
            'period' => $period,
            'total' => $this->get_total_tags_count(),
            'items' => $rankings,
        );
        
        // Cache the data
        set_transient($cache_key, $result, $this->cache_expiration);
        
        return $result;
    }

    /**
     * Get model rankings.
     *
     * @since    1.0.0
     * @param    string    $period    The period for rankings (day, week, month, year).
     * @param    int       $limit     The number of results to return.
     * @param    int       $offset    The offset for pagination.
     * @return   array     The model rankings.
     */
    public function get_model_rankings($period = 'week', $limit = 10, $offset = 0) {
        $cache_key = 'vortex_model_rankings_' . $period . '_' . $limit . '_' . $offset;
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        global $wpdb;
        
        $rankings_table = $this->tables['model_rankings'];
        
        // Validate period
        $valid_periods = array('day', 'week', 'month', 'year');
        if (!in_array($period, $valid_periods)) {
            $period = 'week';
        }
        
        // Get rankings
        $rankings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$rankings_table}
            WHERE period = %s
            ORDER BY usage_count DESC
            LIMIT %d OFFSET %d",
            $period,
            $limit,
            $offset
        ));
        
        // Process rankings
        $result = array(
            'period' => $period,
            'total' => $this->get_total_models_count(),
            'items' => $rankings,
        );
        
        // Cache the data
        set_transient($cache_key, $result, $this->cache_expiration);
        
        return $result;
    }

    /**
     * Calculate artist rankings.
     *
     * @since    1.0.0
     */
    public function calculate_artist_rankings() {
        global $wpdb;
        
        // Get all active artists
        $artists_table = $this->tables['artists'];
        $artists = $wpdb->get_results(
            "SELECT artist_id FROM {$artists_table} WHERE status = 'active'"
        );
        
        if (empty($artists)) {
            return;
        }
        
        $rankings_table = $this->tables['artist_rankings'];
        $sales_table = $this->tables['sales'];
        $artist_stats_table = $this->tables['artist_stats'];
        
        // Define periods for rankings calculation
        $periods = array(
            'day' => '1 day',
            'week' => '7 days',
            'month' => '30 days',
            'year' => '365 days'
        );
        
        // Loop through each period and calculate rankings
        foreach ($periods as $period_key => $period_interval) {
            // Clear existing rankings for this period
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$rankings_table} WHERE period = %s",
                $period_key
            ));
            
            // Set date range
            $end_date = current_time('mysql');
            $start_date = date('Y-m-d H:i:s', strtotime("-{$period_interval}", strtotime($end_date)));
            
            // Loop through artists and calculate scores
            foreach ($artists as $artist) {
                $artist_id = $artist->artist_id;
                
                // Get sales metrics
                $sales_metrics = $wpdb->get_row($wpdb->prepare(
                    "SELECT COUNT(*) as sales, SUM(price) as revenue
                     FROM {$sales_table}
                     WHERE artist_id = %d AND date_created BETWEEN %s AND %s",
                    $artist_id,
                    $start_date,
                    $end_date
                ));
                
                // Get artist stats
                $artist_stats = $wpdb->get_row($wpdb->prepare(
                    "SELECT total_views as views, total_followers as followers, artworks_count
                     FROM {$artist_stats_table}
                     WHERE artist_id = %d",
                    $artist_id
                ));
                
                // Default values if no data
                $sales = $sales_metrics ? intval($sales_metrics->sales) : 0;
                $revenue = $sales_metrics ? floatval($sales_metrics->revenue) : 0;
                $views = $artist_stats ? intval($artist_stats->views) : 0;
                $followers = $artist_stats ? intval($artist_stats->followers) : 0;
                $artworks_count = $artist_stats ? intval($artist_stats->artworks_count) : 0;
                
                // Calculate ranking score - customize this formula as needed
                $score = $this->calculate_artist_score($sales, $revenue, $views, $followers, $artworks_count);
                
                // Insert into rankings table
                $wpdb->insert(
                    $rankings_table,
                    array(
                        'artist_id' => $artist_id,
                        'period' => $period_key,
                        'score' => $score,
                        'sales' => $sales,
                        'revenue' => $revenue,
                        'views' => $views,
                        'followers' => $followers,
                        'last_updated' => current_time('mysql')
                    ),
                    array('%d', '%s', '%f', '%d', '%f', '%d', '%d', '%s')
                );
            }
        }
        
        // Clear rankings cache
        $this->clear_artist_rankings_cache();
    }

    /**
     * Calculate artwork rankings.
     *
     * @since    1.0.0
     */
    public function calculate_artwork_rankings() {
        global $wpdb;
        
        // Get all active artworks
        $artworks_table = $this->tables['artworks'];
        $artworks = $wpdb->get_results(
            "SELECT artwork_id FROM {$artworks_table} WHERE status = 'active'"
        );
        
        if (empty($artworks)) {
            return;
        }
        
        $rankings_table = $this->tables['artwork_rankings'];
        $sales_table = $this->tables['sales'];
        $artwork_stats_table = $this->tables['artwork_stats'];
        
        // Define periods for rankings calculation
        $periods = array(
            'day' => '1 day',
            'week' => '7 days',
            'month' => '30 days',
            'year' => '365 days'
        );
        
        // Loop through each period and calculate rankings
        foreach ($periods as $period_key => $period_interval) {
            // Clear existing rankings for this period
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$rankings_table} WHERE period = %s",
                $period_key
            ));
            
            // Set date range
            $end_date = current_time('mysql');
            $start_date = date('Y-m-d H:i:s', strtotime("-{$period_interval}", strtotime($end_date)));
            
            // Loop through artworks and calculate scores
            foreach ($artworks as $artwork) {
                $artwork_id = $artwork->artwork_id;
                
                // Get sales metrics
                $sales_metrics = $wpdb->get_row($wpdb->prepare(
                    "SELECT COUNT(*) as sales, SUM(price) as revenue
                     FROM {$sales_table}
                     WHERE artwork_id = %d AND date_created BETWEEN %s AND %s",
                    $artwork_id,
                    $start_date,
                    $end_date
                ));
                
                // Get artwork stats
                $artwork_stats = $wpdb->get_row($wpdb->prepare(
                    "SELECT views, likes, shares
                     FROM {$artwork_stats_table}
                     WHERE artwork_id = %d",
                    $artwork_id
                ));
                
                // Default values if no data
                $sales = $sales_metrics ? intval($sales_metrics->sales) : 0;
                $revenue = $sales_metrics ? floatval($sales_metrics->revenue) : 0;
                $views = $artwork_stats ? intval($artwork_stats->views) : 0;
                $likes = $artwork_stats ? intval($artwork_stats->likes) : 0;
                $shares = $artwork_stats ? intval($artwork_stats->shares) : 0;
                
                // Calculate ranking score - customize this formula as needed
                $score = $this->calculate_artwork_score($sales, $revenue, $views, $likes, $shares);
                
                // Insert into rankings table
                $wpdb->insert(
                    $rankings_table,
                    array(
                        'artwork_id' => $artwork_id,
                        'period' => $period_key,
                        'score' => $score,
                        'sales' => $sales,
                        'revenue' => $revenue,
                        'views' => $views,
                        'likes' => $likes,
                        'shares' => $shares,
                        'last_updated' => current_time('mysql')
                    ),
                    array('%d', '%s', '%f', '%d', '%f', '%d', '%d', '%d', '%s')
                );
            }
        }
        
        // Clear rankings cache
        $this->clear_artwork_rankings_cache();
    }

    /**
     * Calculate tag rankings.
     *
     * @since    1.0.0
     */
    public function calculate_tag_rankings() {
        global $wpdb;
        
        $rankings_table = $this->tables['tag_rankings'];
        $artworks_table = $this->tables['artworks'];
        
        // Define periods for rankings calculation
        $periods = array(
            'day' => '1 day',
            'week' => '7 days',
            'month' => '30 days',
            'year' => '365 days'
        );
        
        // Get active artwork IDs
        $active_artwork_ids = $wpdb->get_col(
            "SELECT artwork_id FROM {$artworks_table} WHERE status = 'active'"
        );
        
        if (empty($active_artwork_ids)) {
            return;
        }
        
        // Get all unique tags from artworks
        $tag_terms = get_terms(array(
            'taxonomy' => 'artwork_tag',
            'hide_empty' => true,
        ));
        
        if (is_wp_error($tag_terms) || empty($tag_terms)) {
            return;
        }
        
        // Loop through each period and calculate rankings
        foreach ($periods as $period_key => $period_interval) {
            // Clear existing rankings for this period
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$rankings_table} WHERE period = %s",
                $period_key
            ));
            
            // Set date range
            $end_date = current_time('mysql');
            $start_date = date('Y-m-d H:i:s', strtotime("-{$period_interval}", strtotime($end_date)));
            
            // Loop through tags and count usage
            foreach ($tag_terms as $tag) {
                // Get artwork post IDs with this tag
                $tagged_artwork_posts = get_posts(array(
                    'post_type' => 'vortex_artwork',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'artwork_tag',
                            'field' => 'term_id',
                            'terms' => $tag->term_id,
                        ),
                    ),
                    'date_query' => array(
                        array(
                            'after'     => $start_date,
                            'before'    => $end_date,
                            'inclusive' => true,
                        ),
                    ),
                ));
                
                // Count only active artworks
                $count = 0;
                if (!empty($tagged_artwork_posts)) {
                    // Get corresponding artwork_ids from post_ids
                    $tagged_artwork_ids = array();
                    foreach ($tagged_artwork_posts as $post_id) {
                        $artwork_id = get_post_meta($post_id, '_vortex_artwork_id', true);
                        if ($artwork_id && in_array($artwork_id, $active_artwork_ids)) {
                            $tagged_artwork_ids[] = $artwork_id;
                        }
                    }
                    $count = count($tagged_artwork_ids);
                }
                
                if ($count > 0) {
                    // Insert into rankings table
                    $wpdb->insert(
                        $rankings_table,
                        array(
                            'tag_id' => $tag->term_id,
                            'tag_name' => $tag->name,
                            'tag_slug' => $tag->slug,
                            'period' => $period_key,
                            'count' => $count,
                            'last_updated' => current_time('mysql')
                        ),
                        array('%d', '%s', '%s', '%s', '%d', '%s')
                    );
                }
            }
        }
        
        // Clear rankings cache
        $this->clear_tag_rankings_cache();
    }

    /**
     * Calculate model rankings.
     *
     * @since    1.0.0
     */
    public function calculate_model_rankings() {
        global $wpdb;
        
        $rankings_table = $this->tables['model_rankings'];
        $artworks_table = $this->tables['artworks'];
        
        // Define periods for rankings calculation
        $periods = array(
            'day' => '1 day',
            'week' => '7 days',
            'month' => '30 days',
            'year' => '365 days'
        );
        
        // Get active artwork IDs
        $active_artwork_ids = $wpdb->get_col(
            "SELECT artwork_id FROM {$artworks_table} WHERE status = 'active'"
        );
        
        if (empty($active_artwork_ids)) {
            return;
        }
        
        // Get all unique AI models from artworks
        $model_terms = get_terms(array(
            'taxonomy' => 'ai_model',
            'hide_empty' => true,
        ));
        
        if (is_wp_error($model_terms) || empty($model_terms)) {
            return;
        }
        
        // Loop through each period and calculate rankings
        foreach ($periods as $period_key => $period_interval) {
            // Clear existing rankings for this period
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$rankings_table} WHERE period = %s",
                $period_key
            ));
            
            // Set date range
            $end_date = current_time('mysql');
            $start_date = date('Y-m-d H:i:s', strtotime("-{$period_interval}", strtotime($end_date)));
            
            // Loop through models and count usage
            foreach ($model_terms as $model) {
                // Get artwork post IDs with this model
                $model_artwork_posts = get_posts(array(
                    'post_type' => 'vortex_artwork',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'ai_model',
                            'field' => 'term_id',
                            'terms' => $model->term_id,
                        ),
                    ),
                    'date_query' => array(
                        array(
                            'after'     => $start_date,
                            'before'    => $end_date,
                            'inclusive' => true,
                        ),
                    ),
                ));
                
                // Count only active artworks
                $count = 0;
                $sales_count = 0;
                $sales_volume = 0;
                
                if (!empty($model_artwork_posts)) {
                    // Get corresponding artwork_ids from post_ids
                    $model_artwork_ids = array();
                    foreach ($model_artwork_posts as $post_id) {
                        $artwork_id = get_post_meta($post_id, '_vortex_artwork_id', true);
                        if ($artwork_id && in_array($artwork_id, $active_artwork_ids)) {
                            $model_artwork_ids[] = $artwork_id;
                        }
                    }
                    
                    $count = count($model_artwork_ids);
                    
                    // If we have artworks, get sales metrics
                    if (!empty($model_artwork_ids)) {
                        $placeholders = implode(',', array_fill(0, count($model_artwork_ids), '%d'));
                        $query_params = array_merge([$start_date, $end_date], $model_artwork_ids);
                        
                        $sales_metrics = $wpdb->get_row($wpdb->prepare(
                            "SELECT COUNT(*) as sales_count, SUM(price) as sales_volume
                             FROM {$this->tables['sales']}
                             WHERE date_created BETWEEN %s AND %s
                             AND artwork_id IN ({$placeholders})",
                            $query_params
                        ));
                        
                        $sales_count = $sales_metrics ? intval($sales_metrics->sales_count) : 0;
                        $sales_volume = $sales_metrics ? floatval($sales_metrics->sales_volume) : 0;
                    }
                }
                
                if ($count > 0) {
                    // Insert into rankings table
                    $wpdb->insert(
                        $rankings_table,
                        array(
                            'model_id' => $model->term_id,
                            'model_name' => $model->name,
                            'model_slug' => $model->slug,
                            'period' => $period_key,
                            'count' => $count,
                            'sales_count' => $sales_count,
                            'sales_volume' => $sales_volume,
                            'last_updated' => current_time('mysql')
                        ),
                        array('%d', '%s', '%s', '%s', '%d', '%d', '%f', '%s')
                    );
                }
            }
        }
        
        // Clear rankings cache
        $this->clear_model_rankings_cache();
    }
} 