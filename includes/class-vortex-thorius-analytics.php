<?php
/**
 * Thorius Analytics
 * 
 * Handles usage tracking and analytics dashboard
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Analytics
 */
class Vortex_Thorius_Analytics {
    
    /**
     * Database table name
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vortex_thorius_analytics';
        
        // Create analytics table if it doesn't exist
        $this->create_analytics_table();
        
        // Add AJAX handlers
        add_action('wp_ajax_vortex_thorius_get_analytics', array($this, 'get_analytics_data'));
    }
    
    /**
     * Create analytics table if it doesn't exist
     */
    private function create_analytics_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_data longtext NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Track analytics event
     *
     * @param string $event_type Event type
     * @param array $event_data Event data
     * @return bool Success
     */
    public function track_analytics($event_type, $event_data = array()) {
        global $wpdb;
        
        // Anonymize IP for GDPR compliance
        $ip = $this->anonymize_ip($_SERVER['REMOTE_ADDR']);
        
        // Get current user ID
        $user_id = get_current_user_id();
        if ($user_id === 0) {
            $user_id = null;
        }
        
        // Insert record
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'event_type' => $event_type,
                'event_data' => json_encode($event_data),
                'user_id' => $user_id,
                'ip_address' => $ip,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Get analytics data via AJAX
     */
    public function get_analytics_data() {
        check_ajax_referer('vortex_thorius_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to access analytics.', 'vortex-ai-marketplace')));
        }
        
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '30days';
        $event_type = isset($_POST['event_type']) ? sanitize_text_field($_POST['event_type']) : 'all';
        
        $data = $this->get_analytics($period, $event_type);
        
        wp_send_json_success($data);
    }
    
    /**
     * Get analytics data
     *
     * @param string $period Time period
     * @param string $event_type Event type filter
     * @return array Analytics data
     */
    public function get_analytics($period = '30days', $event_type = 'all') {
        global $wpdb;
        
        // Calculate date range
        $end_date = current_time('mysql');
        $start_date = '';
        
        switch ($period) {
            case '7days':
                $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;
            case '30days':
                $start_date = date('Y-m-d H:i:s', strtotime('-30 days'));
                break;
            case '90days':
                $start_date = date('Y-m-d H:i:s', strtotime('-90 days'));
                break;
            case '1year':
                $start_date = date('Y-m-d H:i:s', strtotime('-1 year'));
                break;
            default:
                $start_date = date('Y-m-d H:i:s', strtotime('-30 days'));
                break;
        }
        
        // Build query
        $query = "SELECT * FROM {$this->table_name} WHERE created_at BETWEEN %s AND %s";
        $params = array($start_date, $end_date);
        
        if ($event_type !== 'all') {
            $query .= " AND event_type = %s";
            $params[] = $event_type;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        // Execute query
        $results = $wpdb->get_results($wpdb->prepare($query, $params));
        
        // Format results
        $formatted_results = array();
        foreach ($results as $row) {
            $formatted_results[] = array(
                'id' => $row->id,
                'event_type' => $row->event_type,
                'event_data' => json_decode($row->event_data, true),
                'created_at' => $row->created_at
            );
        }
        
        // Generate summary
        $summary = $this->generate_analytics_summary($formatted_results);
        
        return array(
            'results' => $formatted_results,
            'summary' => $summary,
            'period' => $period,
            'start_date' => $start_date,
            'end_date' => $end_date
        );
    }
    
    /**
     * Generate summary statistics from analytics data
     *
     * @param array $data Analytics data
     * @return array Summary statistics
     */
    private function generate_analytics_summary($data) {
        $summary = array(
            'total_events' => count($data),
            'event_types' => array(),
            'agents' => array(
                'cloe' => 0,
                'huraii' => 0,
                'strategist' => 0
            ),
            'daily_activity' => array()
        );
        
        // Process data
        foreach ($data as $event) {
            // Count event types
            if (!isset($summary['event_types'][$event['event_type']])) {
                $summary['event_types'][$event['event_type']] = 0;
            }
            $summary['event_types'][$event['event_type']]++;
            
            // Count agent usage
            if ($event['event_type'] === 'agent_request' && isset($event['event_data']['agent'])) {
                $agent = $event['event_data']['agent'];
                if (isset($summary['agents'][$agent])) {
                    $summary['agents'][$agent]++;
                }
            }
            
            // Daily activity
            $date = date('Y-m-d', strtotime($event['created_at']));
            if (!isset($summary['daily_activity'][$date])) {
                $summary['daily_activity'][$date] = 0;
            }
            $summary['daily_activity'][$date]++;
        }
        
        // Sort daily activity by date
        ksort($summary['daily_activity']);
        
        return $summary;
    }
    
    /**
     * Anonymize IP address for GDPR compliance
     *
     * @param string $ip IP address
     * @return string Anonymized IP
     */
    private function anonymize_ip($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // For IPv4, replace the last octet with 0
            return preg_replace('/\.\d+$/', '.0', $ip);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // For IPv6, replace the last 80 bits (last 20 hex chars) with zeros
            return substr($ip, 0, strrpos($ip, ':')) . ':0000';
        }
        
        return '';
    }
} 