<?php
/**
 * The Metrics API functionality of the plugin.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The Metrics API class.
 *
 * This class handles the collection, analysis, and API endpoints for marketplace metrics.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Metrics_API {

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
     * Metrics cache expiration time in seconds.
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
            'artwork_stats' => $wpdb->prefix . 'vortex_artwork_stats',
            'artist_stats' => $wpdb->prefix . 'vortex_artist_stats',
            'sales' => $wpdb->prefix . 'vortex_sales',
            'page_views' => $wpdb->prefix . 'vortex_page_views',
            'user_activity' => $wpdb->prefix . 'vortex_user_activity',
            'daily_metrics' => $wpdb->prefix . 'vortex_daily_metrics',
            'monthly_metrics' => $wpdb->prefix . 'vortex_monthly_metrics',
            'ai_generation_logs' => $wpdb->prefix . 'vortex_ai_generation_logs',
        ];
        
        // Default cache expiration: 1 hour
        $this->cache_expiration = 3600;
        
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_api_endpoints'));
        
        // Register admin AJAX endpoints
        add_action('wp_ajax_vortex_get_dashboard_metrics', array($this, 'ajax_get_dashboard_metrics'));
        add_action('wp_ajax_vortex_get_artwork_metrics', array($this, 'ajax_get_artwork_metrics'));
        add_action('wp_ajax_vortex_get_artist_metrics', array($this, 'ajax_get_artist_metrics'));
        add_action('wp_ajax_vortex_get_sales_metrics', array($this, 'ajax_get_sales_metrics'));
        add_action('wp_ajax_vortex_get_user_metrics', array($this, 'ajax_get_user_metrics'));
        add_action('wp_ajax_vortex_get_ai_metrics', array($this, 'ajax_get_ai_metrics'));
        
        // Register cron events for metrics aggregation
        add_action('vortex_daily_metrics_aggregation', array($this, 'aggregate_daily_metrics'));
        add_action('vortex_monthly_metrics_aggregation', array($this, 'aggregate_monthly_metrics'));
    }

    /**
     * Register REST API endpoints.
     *
     * @since    1.0.0
     */
    public function register_api_endpoints() {
        register_rest_route('vortex/v1', '/metrics/summary', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_metrics_summary'),
            'permission_callback' => array($this, 'api_metrics_permissions_check'),
        ));
        
        register_rest_route('vortex/v1', '/metrics/artwork/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_artwork_metrics'),
            'permission_callback' => array($this, 'api_metrics_permissions_check'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
        
        register_rest_route('vortex/v1', '/metrics/artist/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_artist_metrics'),
            'permission_callback' => array($this, 'api_metrics_permissions_check'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
        
        register_rest_route('vortex/v1', '/metrics/sales', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_sales_metrics'),
            'permission_callback' => array($this, 'api_metrics_permissions_check'),
        ));

        register_rest_route('vortex/v1', '/metrics/ai', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_ai_metrics'),
            'permission_callback' => array($this, 'api_metrics_permissions_check'),
        ));

        register_rest_route('vortex/v1', '/metrics/trends', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_marketplace_trends'),
            'permission_callback' => array($this, 'api_metrics_permissions_check'),
        ));
    }

    /**
     * Check permissions for API endpoints.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request Full data about the request.
     * @return   bool|WP_Error
     */
    public function api_metrics_permissions_check($request) {
        // Public endpoints for basic metrics
        if (in_array($request->get_route(), [
            '/vortex/v1/metrics/summary'
        ])) {
            return true;
        }
        
        // Admin-only endpoints
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('You do not have permission to access this endpoint.', 'vortex-ai-marketplace'),
                array('status' => 403)
            );
        }

        return true;
    }

    /**
     * Get marketplace metrics summary via API.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request Full data about the request.
     * @return   WP_REST_Response
     */
    public function api_get_metrics_summary($request) {
        $period = $request->get_param('period') ?: 'week';
        $summary = $this->get_marketplace_summary($period);
        
        return new WP_REST_Response($summary, 200);
    }

    /**
     * Get artwork metrics via API.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request Full data about the request.
     * @return   WP_REST_Response
     */
    public function api_get_artwork_metrics($request) {
        $artwork_id = $request->get_param('id');
        $period = $request->get_param('period') ?: 'week';
        
        $metrics = $this->get_artwork_metrics($artwork_id, $period);
        
        return new WP_REST_Response($metrics, 200);
    }

    /**
     * Get artist metrics via API.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request Full data about the request.
     * @return   WP_REST_Response
     */
    public function api_get_artist_metrics($request) {
        $artist_id = $request->get_param('id');
        $period = $request->get_param('period') ?: 'week';
        
        $metrics = $this->get_artist_metrics($artist_id, $period);
        
        return new WP_REST_Response($metrics, 200);
    }

    /**
     * Get sales metrics via API.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request Full data about the request.
     * @return   WP_REST_Response
     */
    public function api_get_sales_metrics($request) {
        $period = $request->get_param('period') ?: 'week';
        $metrics = $this->get_sales_metrics($period);
        
        return new WP_REST_Response($metrics, 200);
    }

    /**
     * Get AI generation metrics via API.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request Full data about the request.
     * @return   WP_REST_Response
     */
    public function api_get_ai_metrics($request) {
        $period = $request->get_param('period') ?: 'week';
        $metrics = $this->get_ai_metrics($period);
        
        return new WP_REST_Response($metrics, 200);
    }

    /**
     * Get marketplace trends via API.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request Full data about the request.
     * @return   WP_REST_Response
     */
    public function api_get_marketplace_trends($request) {
        $period = $request->get_param('period') ?: 'month';
        $trends = $this->get_marketplace_trends($period);
        
        return new WP_REST_Response($trends, 200);
    }

    /**
     * Handle AJAX request for dashboard metrics.
     *
     * @since    1.0.0
     */
    public function ajax_get_dashboard_metrics() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to access this data.', 'vortex-ai-marketplace')));
        }
        
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'week';
        $data_type = isset($_POST['data_type']) ? sanitize_text_field($_POST['data_type']) : 'summary';
        
        switch ($data_type) {
            case 'summary':
                $data = $this->get_marketplace_summary($period);
                break;
            case 'sales':
                $data = $this->get_sales_metrics($period);
                break;
            case 'artists':
                $data = $this->get_artists_summary($period);
                break;
            case 'artworks':
                $data = $this->get_artworks_summary($period);
                break;
            case 'ai':
                $data = $this->get_ai_metrics($period);
                break;
            case 'users':
                $data = $this->get_user_metrics($period);
                break;
            default:
                $data = $this->get_marketplace_summary($period);
                break;
        }
        
        wp_send_json_success($data);
    }

    /**
     * Handle AJAX request for artwork metrics.
     *
     * @since    1.0.0
     */
    public function ajax_get_artwork_metrics() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to access this data.', 'vortex-ai-marketplace')));
        }
        
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        
        if (!$artwork_id) {
            wp_send_json_error(array('message' => __('Invalid artwork ID.', 'vortex-ai-marketplace')));
        }
        
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'week';
        $metrics = $this->get_artwork_metrics($artwork_id, $period);
        
        wp_send_json_success($metrics);
    }

    /**
     * Handle AJAX request for artist metrics.
     *
     * @since    1.0.0
     */
    public function ajax_get_artist_metrics() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to access this data.', 'vortex-ai-marketplace')));
        }
        
        $artist_id = isset($_POST['artist_id']) ? intval($_POST['artist_id']) : 0;
        
        if (!$artist_id) {
            wp_send_json_error(array('message' => __('Invalid artist ID.', 'vortex-ai-marketplace')));
        }
        
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'week';
        $metrics = $this->get_artist_metrics($artist_id, $period);
        
        wp_send_json_success($metrics);
    }

    /**
     * Handle AJAX request for sales metrics.
     *
     * @since    1.0.0
     */
    public function ajax_get_sales_metrics() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to access this data.', 'vortex-ai-marketplace')));
        }
        
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'week';
        $metrics = $this->get_sales_metrics($period);
        
        wp_send_json_success($metrics);
    }

    /**
     * Handle AJAX request for user metrics.
     *
     * @since    1.0.0
     */
    public function ajax_get_user_metrics() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to access this data.', 'vortex-ai-marketplace')));
        }
        
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'week';
        $metrics = $this->get_user_metrics($period);
        
        wp_send_json_success($metrics);
    }

    /**
     * Handle AJAX request for AI metrics.
     *
     * @since    1.0.0
     */
    public function ajax_get_ai_metrics() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to access this data.', 'vortex-ai-marketplace')));
        }
        
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'week';
        $metrics = $this->get_ai_metrics($period);
        
        wp_send_json_success($metrics);
    }

    /**
     * Get marketplace summary metrics.
     *
     * @since    1.0.0
     * @param    string    $period    The period to get metrics for (day, week, month, year, all).
     * @return   array     The marketplace summary metrics.
     */
    public function get_marketplace_summary($period = 'week') {
        $cache_key = 'vortex_marketplace_summary_' . $period;
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        global $wpdb;
        
        // Get date range
        $date_range = $this->get_date_range($period);
        
        // Get total counts
        $total_artworks = $this->get_total_artworks();
        $total_artists = $this->get_total_artists();
        $total_sales = $this->get_total_sales($date_range['start'], $date_range['end']);
        $total_volume = $this->get_total_sales_volume($date_range['start'], $date_range['end']);
        $total_users = $this->get_total_users();
        
        // Get period counts
        $period_artworks = $this->get_period_artworks($date_range['start'], $date_range['end']);
        $period_artists = $this->get_period_artists($date_range['start'], $date_range['end']);
        $period_sales = $this->get_period_sales($date_range['start'], $date_range['end']);
        $period_volume = $this->get_period_sales_volume($date_range['start'], $date_range['end']);
        $period_users = $this->get_period_users($date_range['start'], $date_range['end']);
        
        // Get growth rates
        $artwork_growth = $this->calculate_growth_rate($total_artworks, $period_artworks);
        $artist_growth = $this->calculate_growth_rate($total_artists, $period_artists);
        $sales_growth = $this->calculate_growth_rate($total_sales, $period_sales);
        $volume_growth = $this->calculate_growth_rate($total_volume, $period_volume);
        $user_growth = $this->calculate_growth_rate($total_users, $period_users);
        
        // Get top artists, artworks, and tags
        $top_artists = $this->get_top_artists($date_range['start'], $date_range['end'], 5);
        $top_artworks = $this->get_top_artworks($date_range['start'], $date_range['end'], 5);
        $top_tags = $this->get_top_tags($date_range['start'], $date_range['end'], 10);
        $top_ai_models = $this->get_top_ai_models($date_range['start'], $date_range['end'], 5);
        
        // Get sales data for chart
        $sales_chart_data = $this->get_sales_chart_data($date_range['start'], $date_range['end']);
        
        // Create summary array
        $summary = array(
            'period' => $period,
            'date_range' => $date_range,
            'total_artworks' => $total_artworks,
            'total_artists' => $total_artists,
            'total_sales' => $total_sales,
            'total_volume' => $total_volume,
            'total_users' => $total_users,
            'period_artworks' => $period_artworks,
            'period_artists' => $period_artists,
            'period_sales' => $period_sales,
            'period_volume' => $period_volume,
            'period_users' => $period_users,
            'artwork_growth' => $artwork_growth,
            'artist_growth' => $artist_growth,
            'sales_growth' => $sales_growth,
            'volume_growth' => $volume_growth,
            'user_growth' => $user_growth,
            'top_artists' => $top_artists,
            'top_artworks' => $top_artworks,
            'top_tags' => $top_tags,
            'top_ai_models' => $top_ai_models,
            'sales_chart_data' => $sales_chart_data,
        );
        
        // Cache the data
        set_transient($cache_key, $summary, $this->cache_expiration);
        
        return $summary;
    }

    /**
     * Get artwork metrics.
     *
     * @since    1.0.0
     * @param    int       $artwork_id    The artwork ID.
     * @param    string    $period        The period to get metrics for (day, week, month, year, all).
     * @return   array     The artwork metrics.
     */
    public function get_artwork_metrics($artwork_id, $period = 'week') {
        $cache_key = 'vortex_artwork_metrics_' . $artwork_id . '_' . $period;
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        global $wpdb;
        
        // Get date range
        $date_range = $this->get_date_range($period);
        
        // Get artwork stats
        $stats_table = $this->tables['artwork_stats'];
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$stats_table} WHERE artwork_id = %d",
            $artwork_id
        ));
        
        if (!$stats) {
            return array(
                'error' => 'Artwork stats not found',
                'artwork_id' => $artwork_id
            );
        }
        
        // Get view history
        $views_table = $this->tables['page_views'];
        $view_history = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(date_created) as date, COUNT(*) as count
             FROM {$views_table}
             WHERE entity_type = 'artwork' AND entity_id = %d
             AND date_created BETWEEN %s AND %s
             GROUP BY DATE(date_created)
             ORDER BY date ASC",
            $artwork_id,
            $date_range['start'],
            $date_range['end']
        ));
        
        // Format view history for chart
        $view_chart_data = array(
            'labels' => array(),
            'datasets' => array(
                array(
                    'label' => __('Views', 'vortex-ai-marketplace'),
                    'data' => array(),
                    'borderColor' => '#4CAF50',
                    'backgroundColor' => 'rgba(76, 175, 80, 0.1)',
                )
            )
        );
        
        foreach ($view_history as $data_point) {
            $view_chart_data['labels'][] = $data_point->date;
            $view_chart_data['datasets'][0]['data'][] = $data_point->count;
        }
        
        // Get sales info
        $sales_table = $this->tables['sales'];
        $sales = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$sales_table} WHERE artwork_id = %d ORDER BY date_created DESC",
            $artwork_id
        ));
        
        // Get total and average price
        $price_data = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as sales_count, SUM(price) as total_price, AVG(price) as avg_price
             FROM {$sales_table}
             WHERE artwork_id = %d",
            $artwork_id
        ));
        
        // Get related artworks (same artist or tags)
        $related_artworks = $this->get_related_artworks($artwork_id, 5);
        
        // Create metrics array
        $metrics = array(
            'artwork_id' => $artwork_id,
            'period' => $period,
            'date_range' => $date_range,
            'stats' => $stats,
            'view_chart_data' => $view_chart_data,
            'sales' => $sales,
            'price_data' => $price_data,
            'related_artworks' => $related_artworks,
        );
        
        // Cache the data
        set_transient($cache_key, $metrics, $this->cache_expiration);
        
        return $metrics;
    }

    /**
     * Get artist metrics.
     *
     * @since    1.0.0
     * @param    int       $artist_id    The artist ID.
     * @param    string    $period       The period to get metrics for (day, week, month, year, all).
     * @return   array     The artist metrics.
     */
    public function get_artist_metrics($artist_id, $period = 'week') {
        $cache_key = 'vortex_artist_metrics_' . $artist_id . '_' . $period;
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        global $wpdb;
        
        // Get date range
        $date_range = $this->get_date_range($period);
        
        // Get artist stats
        $stats_table = $this->tables['artist_stats'];
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$stats_table} WHERE artist_id = %d",
            $artist_id
        ));
        
        if (!$stats) {
            return array(
                'error' => 'Artist stats not found',
                'artist_id' => $artist_id
            );
        }
        
        // Get artist's artworks
        $artworks_table = $wpdb->prefix . 'vortex_artworks';
        $artworks = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$artworks_table} WHERE artist_id = %d ORDER BY date_created DESC",
            $artist_id
        ));
        
        // Get view history
        $views_table = $this->tables['page_views'];
        $view_history = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(date_created) as date, COUNT(*) as count
             FROM {$views_table}
             WHERE entity_type = 'artist' AND entity_id = %d
             AND date_created BETWEEN %s AND %s
             GROUP BY DATE(date_created)
             ORDER BY date ASC",
            $artist_id,
            $date_range['start'],
            $date_range['end']
        ));
        
        // Format view history for chart
        $view_chart_data = array(
            'labels' => array(),
            'datasets' => array(
                array(
                    'label' => __('Views', 'vortex-ai-marketplace'),
                    'data' => array(),
                    'borderColor' => '#4CAF50',
                    'backgroundColor' => 'rgba(76, 175, 80, 0.1)',
                )
            )
        );
        
        foreach ($view_history as $data_point) {
            $view_chart_data['labels'][] = $data_point->date;
            $view_chart_data['datasets'][0]['data'][] = $data_point->count;
        }
        
        // Get sales history
        $sales_table = $this->tables['sales'];
        $sales_history = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(date_created) as date, COUNT(*) as count, SUM(price) as volume
             FROM {$sales_table}
             WHERE artist_id = %d
             AND date_created BETWEEN %s AND %s
             GROUP BY DATE(date_created)
             ORDER BY date ASC",
            $artist_id,
            $date_range['start'],
            $date_range['end']
        ));
        
        // Format sales history for chart
        $sales_chart_data = array(
            'labels' => array(),
            'datasets' => array(
                array(
                    'label' => __('Sales Count', 'vortex-ai-marketplace'),
                    'data' => array(),
                    'borderColor' => '#2196F3',
                    'backgroundColor' => 'rgba(33, 150, 243, 0.1)',
                    'yAxisID' => 'y-axis-1',
                ),
                array(
                    'label' => __('Sales Volume', 'vortex-ai-marketplace'),
                    'data' => array(),
                    'borderColor' => '#FF9800',
                    'backgroundColor' => 'rgba(255, 152, 0, 0.1)',
                    'yAxisID' => 'y-axis-2',
                )
            )
        );
        
        foreach ($sales_history as $data_point) {
            $sales_chart_data['labels'][] = $data_point->date;
            $sales_chart_data['datasets'][0]['data'][] = $data_point->count;
            $sales_chart_data['datasets'][1]['data'][] = $data_point->volume;
        }
        
        // Get top selling artworks
        $top_artworks = $wpdb->get_results($wpdb->prepare(
            "SELECT a.artwork_id, a.title, a.thumbnail, COUNT(s.sale_id) as sales_count, SUM(s.price) as sales_volume
             FROM {$artworks_table} a
             LEFT JOIN {$sales_table} s ON a.artwork_id = s.artwork_id
             WHERE a.artist_id = %d
             GROUP BY a.artwork_id
             ORDER BY sales_count DESC, sales_volume DESC
             LIMIT 5",
            $artist_id
        ));
        
        // Get followers count
        $followers_table = $wpdb->prefix . 'vortex_artist_followers';
        $followers_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$followers_table} WHERE artist_id = %d",
            $artist_id
        ));
        
        // Create metrics array
        $metrics = array(
            'artist_id' => $artist_id,
            'period' => $period,
            'date_range' => $date_range,
            'stats' => $stats,
            'artworks' => $artworks,
            'view_chart_data' => $view_chart_data,
            'sales_chart_data' => $sales_chart_data,
            'top_artworks' => $top_artworks,
            'followers_count' => $followers_count,
        );
        
        // Cache the data
        set_transient($cache_key, $metrics, $this->cache_expiration);
        
        return $metrics;
    }

    /**
     * Get sales metrics.
     *
     * @since    1.0.0
     * @param    string    $period    The period to get metrics for (day, week, month, year, all).
     * @return   array     The sales metrics.
     */
    public function get_sales_metrics($period = 'week') {
        $cache_key = 'vortex_sales_metrics_' . $period;
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        global $wpdb;
        
        // Get date range
        $date_range = $this->get_date_range($period);
        
        // Get sales summary
        $sales_table = $this->tables['sales'];
        $sales_summary = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total_sales, SUM(price) as total_volume, AVG(price) as avg_price,
             MIN(price) as min_price, MAX(price) as max_price
             FROM {$sales_table}
             WHERE date_created BETWEEN %s AND %s",
            $date_range['start'],
            $date_range['end']
        ));
        
        // Get sales history
        $sales_history = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(date_created) as date, COUNT(*) as count, SUM(price) as volume
             FROM {$sales_table}
             WHERE date_created BETWEEN %s AND %s
             GROUP BY DATE(date_created)
             ORDER BY date ASC",
            $date_range['start'],
            $date_range['end']
        ));
        
        // Format sales history for chart
        $sales_chart_data = array(
            'labels' => array(),
            'datasets' => array(
                array(
                    'label' => __('Sales Count', 'vortex-ai-marketplace'),
                    'data' => array(),
                    'borderColor' => '#2196F3',
                    'backgroundColor' => 'rgba(33, 150, 243, 0.1)',
                    'yAxisID' => 'y-axis-1',
                ),
                array(
                    'label' => __('Sales Volume', 'vortex-ai-marketplace'),
                    'data' => array(),
                    'borderColor' => '#FF9800',
                    'backgroundColor' => 'rgba(255, 152, 0, 0.1)',
                    'yAxisID' => 'y-axis-2',
                )
            )
        );
        
        foreach ($sales_history as $data_point) {
            $sales_chart_data['labels'][] = $data_point->date;
            $sales_chart_data['datasets'][0]['data'][] = $data_point->count;
            $sales_chart_data['datasets'][1]['data'][] = $data_point->volume;
        }
        
        // Get sales by payment method
        $sales_by_payment = $wpdb->get_results($wpdb->prepare(
            "SELECT payment_method, COUNT(*) as count, SUM(price) as volume
             FROM {$sales_table}
             WHERE date_created BETWEEN %s AND %s
             GROUP BY payment_method
             ORDER BY count DESC",
            $date_range['start'],
            $date_range['end']
        ));
        
        // Format sales by payment for chart
        $payment_chart_data = array(
            'labels' => array(),
            'datasets' => array(
                array(
                    'label' => __('Sales by Payment Method', 'vortex-ai-marketplace'),
                    'data' => array(),
                    'backgroundColor' => array(
                        '#4CAF50',
                        '#2196F3',
                        '#FF9800',
                        '#F44336',
                        '#9C27B0',
                        '#607D8B',
                    ),
                )
            )
        );
        
        foreach ($sales_by_payment as $data_point) {
            $payment_chart_data['labels'][] = $data_point->payment_method;
            $payment_chart_data['datasets'][0]['data'][] = $data_point->count;
        }
        
        // Get latest sales
        $latest_sales = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, a.title as artwork_title, a.thumbnail
             FROM {$sales_table} s
             LEFT JOIN {$wpdb->prefix}vortex_artworks a ON s.artwork_id = a.artwork_id
             WHERE s.date_created BETWEEN %s AND %s",
            $date_range['start'],
            $date_range['end']
        ));
        
        // Create metrics array
        $metrics = array(
            'period' => $period,
            'date_range' => $date_range,
            'sales_summary' => $sales_summary,
            'sales_history' => $sales_history,
            'sales_chart_data' => $sales_chart_data,
            'sales_by_payment' => $sales_by_payment,
            'latest_sales' => $latest_sales,
        );
        
        // Cache the data
        set_transient($cache_key, $metrics, $this->cache_expiration);
        
        return $metrics;
    }

    /**
     * Get AI metrics.
     *
     * @since    1.0.0
     * @param    string    $period    The period to get metrics for (day, week, month, year, all).
     * @return   array     The AI metrics.
     */
    public function get_ai_metrics($period = 'week') {
        $cache_key = 'vortex_ai_metrics_' . $period;
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        global $wpdb;
        
        // Get date range
        $date_range = $this->get_date_range($period);
        
        // Get AI generation logs
        $ai_logs_table = $this->tables['ai_generation_logs'];
        $ai_logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$ai_logs_table}
             WHERE date_created BETWEEN %s AND %s",
            $date_range['start'],
            $date_range['end']
        ));
        
        // Get AI generation summary
        $ai_summary = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total_generations, AVG(duration) as avg_duration, SUM(tokens) as total_tokens
             FROM {$ai_logs_table}
             WHERE date_created BETWEEN %s AND %s",
            $date_range['start'],
            $date_range['end']
        ));
        
        // Create metrics array
        $metrics = array(
            'period' => $period,
            'date_range' => $date_range,
            'ai_logs' => $ai_logs,
            'ai_summary' => $ai_summary,
        );
        
        // Cache the data
        set_transient($cache_key, $metrics, $this->cache_expiration);
        
        return $metrics;
    }

    /**
     * Get marketplace trends.
     *
     * @since    1.0.0
     * @param    string    $period    The period to get trends for (day, week, month, year, all).
     * @return   array     The marketplace trends.
     */
    public function get_marketplace_trends($period = 'month') {
        $cache_key = 'vortex_marketplace_trends_' . $period;
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        global $wpdb;
        
        // Get date range
        $date_range = $this->get_date_range($period);
        
        // Get total counts
        $total_artworks = $this->get_total_artworks();
        $total_artists = $this->get_total_artists();
        $total_sales = $this->get_total_sales($date_range['start'], $date_range['end']);
        $total_volume = $this->get_total_sales_volume($date_range['start'], $date_range['end']);
        $total_users = $this->get_total_users();
        
        // Get period counts
        $period_artworks = $this->get_period_artworks($date_range['start'], $date_range['end']);
        $period_artists = $this->get_period_artists($date_range['start'], $date_range['end']);
        $period_sales = $this->get_period_sales($date_range['start'], $date_range['end']);
        $period_volume = $this->get_period_sales_volume($date_range['start'], $date_range['end']);
        $period_users = $this->get_period_users($date_range['start'], $date_range['end']);
        
        // Get growth rates
        $artwork_growth = $this->calculate_growth_rate($total_artworks, $period_artworks);
        $artist_growth = $this->calculate_growth_rate($total_artists, $period_artists);
        $sales_growth = $this->calculate_growth_rate($total_sales, $period_sales);
        $volume_growth = $this->calculate_growth_rate($total_volume, $period_volume);
        $user_growth = $this->calculate_growth_rate($total_users, $period_users);
        
        // Get top artists, artworks, and tags
        $top_artists = $this->get_top_artists($date_range['start'], $date_range['end'], 5);
        $top_artworks = $this->get_top_artworks($date_range['start'], $date_range['end'], 5);
        $top_tags = $this->get_top_tags($date_range['start'], $date_range['end'], 10);
        $top_ai_models = $this->get_top_ai_models($date_range['start'], $date_range['end'], 5);
        
        // Get sales data for chart
        $sales_chart_data = $this->get_sales_chart_data($date_range['start'], $date_range['end']);
        
        // Create trends array
        $trends = array(
            'period' => $period,
            'date_range' => $date_range,
            'total_artworks' => $total_artworks,
            'total_artists' => $total_artists,
            'total_sales' => $total_sales,
            'total_volume' => $total_volume,
            'total_users' => $total_users,
            'period_artworks' => $period_artworks,
            'period_artists' => $period_artists,
            'period_sales' => $period_sales,
            'period_volume' => $period_volume,
            'period_users' => $period_users,
            'artwork_growth' => $artwork_growth,
            'artist_growth' => $artist_growth,
            'sales_growth' => $sales_growth,
            'volume_growth' => $volume_growth,
            'user_growth' => $user_growth,
            'top_artists' => $top_artists,
            'top_artworks' => $top_artworks,
            'top_tags' => $top_tags,
            'top_ai_models' => $top_ai_models,
            'sales_chart_data' => $sales_chart_data,
        );
        
        // Cache the data
        set_transient($cache_key, $trends, $this->cache_expiration);
        
        return $trends;
    }

    /**
     * Get date range.
     *
     * @since    1.0.0
     * @param    string    $period    The period to get the date range for (day, week, month, year, all).
     * @return   array     The date range.
     */
    private function get_date_range($period = 'week') {
        $date_range = array();
        $now = current_time('timestamp');
        
        switch ($period) {
            case 'day':
                $date_range['start'] = date('Y-m-d', $now - DAY_IN_SECONDS);
                $date_range['end'] = date('Y-m-d', $now);
                break;
            case 'week':
                $date_range['start'] = date('Y-m-d', $now - WEEK_IN_SECONDS);
                $date_range['end'] = date('Y-m-d', $now);
                break;
            case 'month':
                $date_range['start'] = date('Y-m-01', $now);
                $date_range['end'] = date('Y-m-t', $now);
                break;
            case 'year':
                $date_range['start'] = date('Y-01-01', $now);
                $date_range['end'] = date('Y-12-31', $now);
                break;
            case 'all':
                $date_range['start'] = date('Y-01-01', $now);
                $date_range['end'] = date('Y-12-31', $now);
                break;
            default:
                $date_range['start'] = date('Y-m-d', $now - WEEK_IN_SECONDS);
                $date_range['end'] = date('Y-m-d', $now);
                break;
        }
        
        return $date_range;
    }

    /**
     * Get total artworks.
     *
     * @since    1.0.0
     * @return   int     The total number of artworks.
     */
    private function get_total_artworks() {
        global $wpdb;
        $artworks_table = $this->tables['artwork_stats'];
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$artworks_table}"));
    }

    /**
     * Get total artists.
     *
     * @since    1.0.0
     * @return   int     The total number of artists.
     */
    private function get_total_artists() {
        global $wpdb;
        $artists_table = $this->tables['artist_stats'];
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$artists_table}"));
    }

    /**
     * Get total sales.
     *
     * @since    1.0.0
     * @param    string    $start_date    The start date.
     * @param    string    $end_date      The end date.
     * @return   int       The total number of sales.
     */
    private function get_total_sales($start_date, $end_date) {
        global $wpdb;
        $sales_table = $this->tables['sales'];
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$sales_table} WHERE date_created BETWEEN %s AND %s", $start_date, $end_date));
    }

    /**
     * Get total sales volume.
     *
     * @since    1.0.0
     * @param    string    $start_date    The start date.
     * @param    string    $end_date      The end date.
     * @return   float     The total sales volume.
     */
    private function get_total_sales_volume($start_date, $end_date) {
        global $wpdb;
        $sales_table = $this->tables['sales'];
        return $wpdb->get_var($wpdb->prepare("SELECT SUM(price) FROM {$sales_table} WHERE date_created BETWEEN %s AND %s", $start_date, $end_date));
    }

    /**
     * Get total users.
     *
     * @since    1.0.0
     * @return   int     The total number of users.
     */
    private function get_total_users() {
        global $wpdb;
        $users_table = $this->tables['user_activity'];
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM {$users_table}"));
    }

    /**
     * Get period artworks.
     *
     * @since    1.0.0
     * @param    string    $start_date    The start date.
     * @param    string    $end_date      The end date.
     * @return   int       The number of artworks in the period.
     */
    private function get_period_artworks($start_date, $end_date) {
        global $wpdb;
        $artworks_table = $this->tables['artwork_stats'];
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$artworks_table} WHERE date_created BETWEEN %s AND %s", $start_date, $end_date));
    }

    /**
     * Get period artists.
     *
     * @since    1.0.0
     * @param    string    $start_date    The start date.
     * @param    string    $end_date      The end date.
     * @return   int       The number of artists in the period.
     */
    private function get_period_artists($start_date, $end_date) {
        global $wpdb;
        $artists_table = $this->tables['artist_stats'];
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$artists_table} WHERE date_created BETWEEN %s AND %s", $start_date, $end_date));
    }

    /**
     * Get period sales.
     *
     * @since    1.0.0
     * @param    string    $start_date    The start date.
     * @param    string    $end_date      The end date.
     * @return   int       The number of sales in the period.
     */
    private function get_period_sales($start_date, $end_date) {
        global $wpdb;
        $sales_table = $this->tables['sales'];
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$sales_table} WHERE date_created BETWEEN %s AND %s", $start_date, $end_date));
    }

    /**
     * Get period sales volume.
     *
     * @since    1.0.0
     * @param    string    $start_date    The start date.
     * @param    string    $end_date      The end date.
     * @return   float     The sales volume in the period.
     */
    private function get_period_sales_volume($start_date, $end_date) {
        global $wpdb;
        $sales_table = $this->tables['sales'];
        return $wpdb->get_var($wpdb->prepare("SELECT SUM(price) FROM {$sales_table} WHERE date_created BETWEEN %s AND %s", $start_date, $end_date));
    }

    /**
     * Get period users.
     *
     * @since    1.0.0
     * @param    string    $start_date    The start date.
     * @param    string    $end_date      The end date.
     * @return   int       The number of users in the period.
     */
    private function get_period_users($start_date, $end_date) {
        global $wpdb;
        $users_table = $this->tables['user_activity'];
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM {$users_table} WHERE date_created BETWEEN %s AND %s", $start_date, $end_date));
    }

    /**
     * Get top artists.
     *
     * @since    1.0.0
     * @param    string    $start_date    The start date.
     * @param    string    $end_date      The end date.
     * @param    int       $limit         The number of top artists to return.
     * @return   array     The top artists.
     */
    private function get_top_artists($start_date, $end_date, $limit = 5) {
        global $wpdb;
        $artists_table = $this->tables['artist_stats'];
        return $wpdb->get_results($wpdb->prepare("SELECT artist_id, artist_name, COUNT(s.sale_id) as sales_count, SUM(s.price) as sales_volume
             FROM {$artists_table} a
             LEFT JOIN {$wpdb->prefix}vortex_artworks aw ON a.artist_id = aw.artist_id
             LEFT JOIN {$wpdb->prefix}vortex_sales s ON aw.artwork_id = s.artwork_id
             WHERE a.date_created BETWEEN %s AND %s
             GROUP BY a.artist_id
             ORDER BY sales_count DESC, sales_volume DESC
             LIMIT %d", $start_date, $end_date, $limit));
    }

    /**
     * Get top artworks.
     *
     * @since    1.0.0
     * @param    string    $start_date    The start date.
     * @param    string    $end_date      The end date.
     * @param    int       $limit         The number of top artworks to return.
     * @return   array     The top artworks.
     */
    private function get_top_artworks($start_date, $end_date, $limit = 5) {
        global $wpdb;
        $artworks_table = $this->tables['artwork_stats'];
        return $wpdb->get_results($wpdb->prepare("SELECT artwork_id, title, COUNT(s.sale_id) as sales_count, SUM(s.price) as sales_volume
             FROM {$artworks_table} a
             LEFT JOIN {$wpdb->prefix}vortex_sales s ON a.artwork_id = s.artwork_id
             WHERE a.date_created BETWEEN %s AND %s
             GROUP BY a.artwork_id
             ORDER BY sales_count DESC, sales_volume DESC
             LIMIT %d", $start_date, $end_date, $limit));
    }

    /**
     * Get top tags.
     *
     * @since    1.0.0
     * @param    string    $start_date    The start date.
     * @param    string    $end_date      The end date.
     * @param    int       $limit         The number of top tags to return.
     * @return   array     The top tags.
     */
    private function get_top_tags($start_date, $end_date, $limit = 10) {
        global $wpdb;
        $tags_table = $this->tables['page_views'];
        return $wpdb->get_results($wpdb->prepare("SELECT tag, COUNT(*) as view_count
             FROM {$tags_table}
             WHERE entity_type = 'tag' AND date_created BETWEEN %s AND %s
             GROUP BY tag
             ORDER BY view_count DESC
             LIMIT %d", $start_date, $end_date, $limit));
    }

    /**
     * Get top AI models.
     *
     * @since    1.0.0
     * @param    string    $start_date    The start date.
     * @param    string    $end_date      The end date.
     * @param    int       $limit         The number of top AI models to return.
     * @return   array     The top AI models.
     */
    private function get_top_ai_models($start_date, $end_date, $limit = 5) {
        global $wpdb;
        $ai_logs_table = $this->tables['ai_generation_logs'];
        return $wpdb->get_results($wpdb->prepare("SELECT model, COUNT(*) as generation_count
             FROM {$ai_logs_table}
             WHERE date_created BETWEEN %s AND %s
             GROUP BY model
             ORDER BY generation_count DESC
             LIMIT %d", $start_date, $end_date, $limit));
    }

    /**
     * Get sales chart data.
     *
     * @since    1.0.0
     * @param    string    $start_date    The start date.
     * @param    string    $end_date      The end date.
     * @return   array     The sales chart data.
     */
    private function get_sales_chart_data($start_date, $end_date) {
        global $wpdb;
        $sales_table = $this->tables['sales'];
        $sales_history = $wpdb->get_results($wpdb->prepare("SELECT DATE(date_created) as date, COUNT(*) as count, SUM(price) as volume
             FROM {$sales_table}
             WHERE date_created BETWEEN %s AND %s
             GROUP BY DATE(date_created)
             ORDER BY date ASC", $start_date, $end_date));
        
        $sales_chart_data = array(
            'labels' => array(),
            'datasets' => array(
                array(
                    'label' => __('Sales Count', 'vortex-ai-marketplace'),
                    'data' => array(),
                    'borderColor' => '#2196F3',
                    'backgroundColor' => 'rgba(33, 150, 243, 0.1)',
                    'yAxisID' => 'y-axis-1',
                ),
                array(
                    'label' => __('Sales Volume', 'vortex-ai-marketplace'),
                    'data' => array(),
                    'borderColor' => '#FF9800',
                    'backgroundColor' => 'rgba(255, 152, 0, 0.1)',
                    'yAxisID' => 'y-axis-2',
                )
            )
        );
        
        foreach ($sales_history as $data_point) {
            $sales_chart_data['labels'][] = $data_point->date;
            $sales_chart_data['datasets'][0]['data'][] = $data_point->count;
            $sales_chart_data['datasets'][1]['data'][] = $data_point->volume;
        }
        
        return $sales_chart_data;
    }

    /**
     * Get related artworks.
     *
     * @since    1.0.0
     * @param    int       $artwork_id    The artwork ID.
     * @param    int       $limit         The number of related artworks to return.
     * @return   array     The related artworks.
     */
    private function get_related_artworks($artwork_id, $limit = 5) {
        global $wpdb;
        $artworks_table = $this->tables['artwork_stats'];
        return $wpdb->get_results($wpdb->prepare("SELECT a.artwork_id, a.title, a.thumbnail
             FROM {$artworks_table} a
             LEFT JOIN {$wpdb->prefix}vortex_artwork_tags at ON a.artwork_id = at.artwork_id
             LEFT JOIN {$wpdb->prefix}vortex_tags t ON at.tag_id = t.tag_id
             WHERE a.artwork_id != %d AND t.tag = (SELECT tag FROM {$artworks_table} WHERE artwork_id = %d)
             GROUP BY a.artwork_id
             ORDER BY COUNT(at.tag_id) DESC, a.artwork_id
             LIMIT %d", $artwork_id, $artwork_id, $limit));
    }
} 