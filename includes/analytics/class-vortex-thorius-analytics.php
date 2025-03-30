<?php
/**
 * Thorius Analytics Class
 * 
 * Handles user behavior analysis and platform analytics for Thorius
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/analytics
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Analytics Class
 */
class Vortex_Thorius_Analytics {
    
    /**
     * Analytics database table
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vortex_thorius_analytics';
        
        // Create table if it doesn't exist
        $this->create_table();
        
        // Set up hooks
        add_action('wp_ajax_vortex_thorius_track_analytics', array($this, 'track_analytics'));
        add_action('wp_ajax_nopriv_vortex_thorius_track_analytics', array($this, 'track_analytics'));
    }
    
    /**
     * Create analytics table
     */
    private function create_table() {
        global $wpdb;
        
        $table_name = $this->table_name;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL DEFAULT 0,
                session_id varchar(50) NOT NULL,
                action_type varchar(50) NOT NULL,
                action_data longtext NOT NULL,
                page_url varchar(255) NOT NULL,
                referrer varchar(255) NOT NULL,
                ip_address varchar(45) NOT NULL,
                user_agent varchar(255) NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY session_id (session_id),
                KEY action_type (action_type),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    /**
     * Track analytics data via AJAX
     */
    public function track_analytics() {
        // Check nonce (should use specific nonce for analytics)
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_thorius_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get analytics data
        $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        $action_data = isset($_POST['action_data']) ? wp_unslash($_POST['action_data']) : array();
        
        if (empty($action_type)) {
            wp_send_json_error(array('message' => __('Action type is required', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        // Get or create session ID
        $session_id = isset($_COOKIE['vortex_thorius_session']) ? sanitize_text_field($_COOKIE['vortex_thorius_session']) : uniqid('thorius_');
        if (!isset($_COOKIE['vortex_thorius_session'])) {
            setcookie('vortex_thorius_session', $session_id, time() + (86400 * 30), '/'); // 30 days
        }
        
        // Get page and browser info
        $page_url = isset($_POST['page_url']) ? esc_url_raw($_POST['page_url']) : '';
        $referrer = isset($_POST['referrer']) ? esc_url_raw($_POST['referrer']) : '';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        
        // Get IP address with privacy
        $ip_address = $this->get_anonymized_ip();
        
        // Save analytics data
        $this->save_analytics($user_id, $session_id, $action_type, $action_data, $page_url, $referrer, $ip_address, $user_agent);
        
        wp_send_json_success(array('message' => __('Analytics tracked', 'vortex-ai-marketplace')));
    }
    
    /**
     * Save analytics data to database
     *
     * @param int $user_id User ID
     * @param string $session_id Session ID
     * @param string $action_type Action type
     * @param array $action_data Action data
     * @param string $page_url Page URL
     * @param string $referrer Referrer URL
     * @param string $ip_address IP address
     * @param string $user_agent User agent
     * @return int|false The number of rows affected, or false on error
     */
    public function save_analytics($user_id, $session_id, $action_type, $action_data, $page_url, $referrer, $ip_address, $user_agent) {
        global $wpdb;
        
        return $wpdb->insert(
            $this->table_name,
            array(
                'user_id'     => $user_id,
                'session_id'  => $session_id,
                'action_type' => $action_type,
                'action_data' => is_array($action_data) ? json_encode($action_data) : $action_data,
                'page_url'    => $page_url,
                'referrer'    => $referrer,
                'ip_address'  => $ip_address,
                'user_agent'  => $user_agent,
                'created_at'  => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get user behavior analytics
     *
     * @param int $user_id User ID
     * @param string $period Period (day, week, month, year, all)
     * @return array User behavior analytics
     */
    public function get_user_behavior($user_id, $period = 'month') {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        
        $query = $wpdb->prepare(
            "SELECT action_type, COUNT(*) as count, 
             MIN(created_at) as first_action, 
             MAX(created_at) as last_action 
             FROM {$this->table_name} 
             WHERE user_id = %d 
             {$date_condition}
             GROUP BY action_type 
             ORDER BY count DESC",
            $user_id
        );
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        $behavior = array(
            'actions' => $results,
            'total_actions' => 0,
            'unique_sessions' => 0,
            'first_seen' => null,
            'last_seen' => null,
            'activity_by_day' => array(),
            'activity_by_hour' => array(),
            'most_active_page' => '',
            'most_active_page_count' => 0
        );
        
        // Calculate total actions
        foreach ($results as $result) {
            $behavior['total_actions'] += $result['count'];
        }
        
        // Get unique sessions
        $query = $wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) as count 
             FROM {$this->table_name} 
             WHERE user_id = %d
             {$date_condition}",
            $user_id
        );
        
        $behavior['unique_sessions'] = $wpdb->get_var($query);
        
        // Get first and last seen
        $query = $wpdb->prepare(
            "SELECT MIN(created_at) as first_seen, MAX(created_at) as last_seen 
             FROM {$this->table_name} 
             WHERE user_id = %d",
            $user_id
        );
        
        $seen_data = $wpdb->get_row($query, ARRAY_A);
        $behavior['first_seen'] = $seen_data['first_seen'];
        $behavior['last_seen'] = $seen_data['last_seen'];
        
        // Get activity by day
        $query = $wpdb->prepare(
            "SELECT DATE(created_at) as day, COUNT(*) as count 
             FROM {$this->table_name} 
             WHERE user_id = %d 
             {$date_condition}
             GROUP BY day 
             ORDER BY day ASC",
            $user_id
        );
        
        $behavior['activity_by_day'] = $wpdb->get_results($query, ARRAY_A);
        
        // Get activity by hour
        $query = $wpdb->prepare(
            "SELECT HOUR(created_at) as hour, COUNT(*) as count 
             FROM {$this->table_name} 
             WHERE user_id = %d 
             {$date_condition}
             GROUP BY hour 
             ORDER BY hour ASC",
            $user_id
        );
        
        $behavior['activity_by_hour'] = $wpdb->get_results($query, ARRAY_A);
        
        // Get most active page
        $query = $wpdb->prepare(
            "SELECT page_url, COUNT(*) as count 
             FROM {$this->table_name} 
             WHERE user_id = %d 
             {$date_condition}
             GROUP BY page_url 
             ORDER BY count DESC 
             LIMIT 1",
            $user_id
        );
        
        $most_active_page = $wpdb->get_row($query, ARRAY_A);
        if ($most_active_page) {
            $behavior['most_active_page'] = $most_active_page['page_url'];
            $behavior['most_active_page_count'] = $most_active_page['count'];
        }
        
        return $behavior;
    }
    
    /**
     * Get market trends
     *
     * @param string $period Period (day, week, month, year, all)
     * @return array Market trends
     */
    public function get_market_trends($period = 'month') {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        
        // Get overall platform stats
        $query = "SELECT 
                COUNT(*) as total_actions,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT session_id) as unique_sessions
                FROM {$this->table_name}
                WHERE user_id > 0
                {$date_condition}";
        
        $overall_stats = $wpdb->get_row($query, ARRAY_A);
        
        // Get popular action types
        $query = "SELECT action_type, COUNT(*) as count
                FROM {$this->table_name}
                WHERE user_id > 0
                {$date_condition}
                GROUP BY action_type
                ORDER BY count DESC
                LIMIT 10";
        
        $popular_actions = $wpdb->get_results($query, ARRAY_A);
        
        // Get user growth over time
        $query = "SELECT DATE(created_at) as day, COUNT(DISTINCT user_id) as count
                FROM {$this->table_name}
                WHERE user_id > 0
                {$date_condition}
                GROUP BY day
                ORDER BY day ASC";
        
        $user_growth = $wpdb->get_results($query, ARRAY_A);
        
        // Get popular search terms
        $query = "SELECT action_data, COUNT(*) as count
                FROM {$this->table_name}
                WHERE action_type = 'search' AND user_id > 0
                {$date_condition}
                GROUP BY action_data
                ORDER BY count DESC
                LIMIT 10";
        
        $search_terms_raw = $wpdb->get_results($query, ARRAY_A);
        $search_terms = array();
        
        foreach ($search_terms_raw as $term) {
            $data = json_decode($term['action_data'], true);
            if (isset($data['query'])) {
                $search_terms[] = array(
                    'term' => $data['query'],
                    'count' => $term['count']
                );
            }
        }
        
        // Get top referrers
        $query = "SELECT referrer, COUNT(*) as count
                FROM {$this->table_name}
                WHERE user_id > 0 AND referrer != ''
                {$date_condition}
                GROUP BY referrer
                ORDER BY count DESC
                LIMIT 10";
        
        $top_referrers = $wpdb->get_results($query, ARRAY_A);
        
        return array(
            'overall_stats' => $overall_stats,
            'popular_actions' => $popular_actions,
            'user_growth' => $user_growth,
            'search_terms' => $search_terms,
            'top_referrers' => $top_referrers
        );
    }
    
    /**
     * Get date condition for SQL query
     *
     * @param string $period Period (day, week, month, year, all)
     * @return string SQL condition
     */
    private function get_date_condition($period) {
        if ($period === 'all') {
            return '';
        }
        
        $date = new DateTime();
        
        switch ($period) {
            case 'day':
                $date->modify('-1 day');
                break;
            case 'week':
                $date->modify('-1 week');
                break;
            case 'month':
                $date->modify('-1 month');
                break;
            case 'year':
                $date->modify('-1 year');
                break;
            default:
                $date->modify('-1 month');
                break;
        }
        
        $date_string = $date->format('Y-m-d H:i:s');
        
        return " AND created_at >= '{$date_string}'";
    }
    
    /**
     * Get anonymized IP address
     *
     * @return string Anonymized IP address
     */
    private function get_anonymized_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Anonymize IP by removing last octet for IPv4 or last 80 bits for IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip = preg_replace('/\.\d+$/', '.0', $ip);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ip = substr($ip, 0, strrpos($ip, ':')) . ':0000';
        }
        
        return $ip;
    }
} 