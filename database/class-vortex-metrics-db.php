<?php
/**
 * Metrics Database Operations
 *
 * Handles database operations for tracking metrics and analytics
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/database
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Metrics Database Operations Class
 *
 * Manages storage and retrieval of performance metrics, user engagement,
 * and other analytics data for the VORTEX AI Marketplace.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/database
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Metrics_DB {

    /**
     * Core database instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_DB $db Core database instance.
     */
    protected $db;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    Vortex_DB $db Core database instance.
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Record a metric data point.
     *
     * @since    1.0.0
     * @param    string $metric_name The name of the metric.
     * @param    mixed  $metric_value The value of the metric.
     * @param    array  $context Optional. Additional context for the metric.
     * @return   mixed The metric ID if successful, false otherwise.
     */
    public function record_metric($metric_name, $metric_value, $context = array()) {
        if (empty($metric_name)) {
            return false;
        }
        
        $data = array(
            'metric_name' => $metric_name,
            'metric_value' => is_numeric($metric_value) ? $metric_value : json_encode($metric_value),
            'metric_date' => current_time('mysql'),
            'context' => !empty($context) ? json_encode($context) : ''
        );
        
        $result = $this->db->insert('metrics', $data);
        
        if ($result) {
            return $this->db->last_insert_id();
        }
        
        return false;
    }

    /**
     * Get metrics by name.
     *
     * @since    1.0.0
     * @param    string $metric_name The name of the metric.
     * @param    string $start_date Optional. Start date in MySQL datetime format.
     * @param    string $end_date Optional. End date in MySQL datetime format.
     * @param    int    $limit Optional. Maximum number of records to return.
     * @return   array Metrics data.
     */
    public function get_metrics_by_name($metric_name, $start_date = '', $end_date = '', $limit = 1000) {
        global $wpdb;
        
        $table = $this->db->get_table_name('metrics');
        $where = $wpdb->prepare("WHERE metric_name = %s", $metric_name);
        
        if (!empty($start_date)) {
            $where .= $wpdb->prepare(" AND metric_date >= %s", $start_date);
        }
        
        if (!empty($end_date)) {
            $where .= $wpdb->prepare(" AND metric_date <= %s", $end_date);
        }
        
        $limit_clause = $wpdb->prepare("LIMIT %d", $limit);
        
        $query = "SELECT * FROM {$table} {$where} ORDER BY metric_date DESC {$limit_clause}";
        
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Get metrics by time period.
     *
     * @since    1.0.0
     * @param    string $period The time period ('day', 'week', 'month', 'year').
     * @param    string $metric_name Optional. Filter by metric name.
     * @return   array Metrics data.
     */
    public function get_metrics_by_period($period, $metric_name = '') {
        global $wpdb;
        
        $table = $this->db->get_table_name('metrics');
        $where = '';
        
        if (!empty($metric_name)) {
            $where = $wpdb->prepare("WHERE metric_name = %s", $metric_name);
        } else {
            $where = "WHERE 1=1";
        }
        
        switch ($period) {
            case 'day':
                $where .= " AND metric_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $where .= " AND metric_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $where .= " AND metric_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $where .= " AND metric_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            default:
                // No date filter
                break;
        }
        
        $query = "SELECT * FROM {$table} {$where} ORDER BY metric_date DESC";
        
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Record a page view.
     *
     * @since    1.0.0
     * @param    array     $data    The page view data.
     * @return   int|bool           The inserted ID or false on failure.
     */
    public function record_page_view( $data ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_page_views';
        
        $result = $wpdb->insert(
            $table,
            $data,
            array(
                '%d', // page_id
                '%s', // page_type
                '%s', // page_url
                '%d', // user_id
                '%s', // session_id
                '%s', // ip_address
                '%s', // user_agent
                '%s', // referrer
                '%s', // device_type
                '%s', // country
                '%s', // timestamp
            )
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Record an event.
     *
     * @since    1.0.0
     * @param    array     $data    The event data.
     * @return   int|bool           The inserted ID or false on failure.
     */
    public function record_event( $data ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_events';
        
        $result = $wpdb->insert(
            $table,
            $data,
            array(
                '%s', // event_type
                '%s', // event_action
                '%d', // user_id
                '%d', // object_id
                '%s', // object_type
                '%s', // session_id
                '%s', // data
                '%s', // timestamp
            )
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Record a search.
     *
     * @since    1.0.0
     * @param    array     $data    The search data.
     * @return   int|bool           The inserted ID or false on failure.
     */
    public function record_search( $data ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_searches';
        
        $result = $wpdb->insert(
            $table,
            $data,
            array(
                '%s', // search_term
                '%d', // user_id
                '%s', // session_id
                '%d', // result_count
                '%d', // converted
                '%s', // timestamp
            )
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get page views for a specific period.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date in Y-m-d format.
     * @param    string    $end_date      End date in Y-m-d format.
     * @param    array     $filters       Optional filters.
     * @return   array                    The page view data.
     */
    public function get_page_views( $start_date, $end_date, $filters = array() ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_page_views';
        
        $where = "WHERE timestamp BETWEEN %s AND %s";
        $params = array( $start_date . ' 00:00:00', $end_date . ' 23:59:59' );
        
        // Add filters
        if ( isset( $filters['page_type'] ) ) {
            $where .= " AND page_type = %s";
            $params[] = $filters['page_type'];
        }
        
        if ( isset( $filters['user_id'] ) ) {
            $where .= " AND user_id = %d";
            $params[] = $filters['user_id'];
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table $where ORDER BY timestamp DESC",
            $params
        );
        
        return $wpdb->get_results( $query, ARRAY_A );
    }

    /**
     * Get events for a specific period.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date in Y-m-d format.
     * @param    string    $end_date      End date in Y-m-d format.
     * @param    array     $filters       Optional filters.
     * @return   array                    The event data.
     */
    public function get_events( $start_date, $end_date, $filters = array() ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_events';
        
        $where = "WHERE timestamp BETWEEN %s AND %s";
        $params = array( $start_date . ' 00:00:00', $end_date . ' 23:59:59' );
        
        // Add filters
        if ( isset( $filters['event_type'] ) ) {
            $where .= " AND event_type = %s";
            $params[] = $filters['event_type'];
        }
        
        if ( isset( $filters['event_action'] ) ) {
            $where .= " AND event_action = %s";
            $params[] = $filters['event_action'];
        }
        
        if ( isset( $filters['user_id'] ) ) {
            $where .= " AND user_id = %d";
            $params[] = $filters['user_id'];
        }
        
        if ( isset( $filters['object_id'] ) ) {
            $where .= " AND object_id = %d";
            $params[] = $filters['object_id'];
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table $where ORDER BY timestamp DESC",
            $params
        );
        
        return $wpdb->get_results( $query, ARRAY_A );
    }

    /**
     * Delete old metrics data based on retention period.
     *
     * @since    1.0.0
     * @param    int       $days    Number of days to retain data.
     * @return   bool               Whether the operation was successful.
     */
    public function delete_old_data( $days = 365 ) {
        global $wpdb;
        
        $cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-$days days" ) );
        
        $tables = array(
            $wpdb->prefix . 'vortex_page_views',
            $wpdb->prefix . 'vortex_events',
            $wpdb->prefix . 'vortex_searches'
        );
        
        $success = true;
        
        foreach ( $tables as $table ) {
            $result = $wpdb->query( $wpdb->prepare(
                "DELETE FROM $table WHERE timestamp < %s",
                $cutoff_date
            ) );
            
            if ( $result === false ) {
                $success = false;
            }
        }
        
        return $success;
    }
} 