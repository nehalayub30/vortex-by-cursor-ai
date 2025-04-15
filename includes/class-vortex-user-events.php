<?php
/**
 * VORTEX User Events
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class to handle user events tracking and storage
 */
class VORTEX_User_Events {
    
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * Database table name.
     */
    private $table_name;
    
    /**
     * Get instance of this class.
     *
     * @return VORTEX_User_Events
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vortex_user_events';
        
        // Ensure table exists
        $this->ensure_table_exists();
        
        // Set up hooks
        add_action('wp_login', array($this, 'track_login'), 10, 2);
        add_action('wp_logout', array($this, 'track_logout'));
        
        // Registration tracking
        add_action('user_register', array($this, 'track_registration'));
        
        // Add cron event cleanup job - run daily to clean up old events
        if (!wp_next_scheduled('vortex_cleanup_old_events')) {
            wp_schedule_event(time(), 'daily', 'vortex_cleanup_old_events');
        }
        add_action('vortex_cleanup_old_events', array($this, 'cleanup_old_events'));
    }
    
    /**
     * Ensure the events table exists
     */
    private function ensure_table_exists() {
        global $wpdb;
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        
        if (!$table_exists) {
            $this->create_events_table();
        }
    }
    
    /**
     * Create the events table
     */
    public function create_events_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            event_type varchar(50) NOT NULL,
            event_data longtext DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            session_id varchar(32) DEFAULT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY timestamp (timestamp),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Record a user event
     *
     * @param int $user_id User ID (can be null for non-logged in users)
     * @param string $event_type Event type
     * @param array $event_data Event data
     * @return int|false The ID of the inserted record, or false on failure
     */
    public function record_event($user_id, $event_type, $event_data = array()) {
        global $wpdb;
        
        // Make sure table exists
        $this->ensure_table_exists();
        
        // Get IP address and user agent
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        
        // Get session ID if available
        $session_id = null;
        if ($user_id) {
            $session_id = get_user_meta($user_id, 'vortex_current_session', true);
        }
        
        // Ensure event_data is serialized
        $event_data_serialized = is_array($event_data) || is_object($event_data) 
            ? maybe_serialize($event_data) 
            : $event_data;
        
        // Insert the event
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'event_type' => $event_type,
                'event_data' => $event_data_serialized,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'session_id' => $session_id,
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('VORTEX User Events: Failed to record event. Type: ' . $event_type);
            return false;
        }
        
        $event_id = $wpdb->insert_id;
        
        // Do action for other components to hook into
        do_action('vortex_user_event_recorded', $event_id, $user_id, $event_type, $event_data, $session_id);
        
        return $event_id;
    }
    
    /**
     * Track user login
     *
     * @param string $user_login The user login
     * @param WP_User $user The user object
     */
    public function track_login($user_login, $user) {
        $this->record_event(
            $user->ID,
            'login',
            array(
                'username' => $user_login,
                'display_name' => $user->display_name
            )
        );
    }
    
    /**
     * Track user logout
     */
    public function track_logout() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $this->record_event(
                $user_id,
                'logout',
                array()
            );
        }
    }
    
    /**
     * Track user registration
     *
     * @param int $user_id The user ID
     */
    public function track_registration($user_id) {
        $user = get_userdata($user_id);
        
        if ($user) {
            $this->record_event(
                $user_id,
                'registration',
                array(
                    'username' => $user->user_login,
                    'display_name' => $user->display_name,
                    'email' => $user->user_email
                )
            );
        }
    }
    
    /**
     * Track page view
     * 
     * @param int $user_id User ID (can be null for non-logged in users)
     * @param string $page Page being viewed
     * @param array $additional_data Additional data to record
     */
    public function track_page_view($user_id, $page, $additional_data = array()) {
        $data = array_merge(array(
            'page' => $page,
            'url' => isset($_SERVER['REQUEST_URI']) ? esc_url_raw($_SERVER['REQUEST_URI']) : '',
            'referrer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : ''
        ), $additional_data);
        
        $this->record_event($user_id, 'page_view', $data);
    }
    
    /**
     * Track artwork view
     * 
     * @param int $user_id User ID
     * @param int $artwork_id Artwork ID
     */
    public function track_artwork_view($user_id, $artwork_id) {
        $this->record_event(
            $user_id,
            'artwork_view',
            array(
                'artwork_id' => $artwork_id,
                'artwork_title' => get_the_title($artwork_id)
            )
        );
    }
    
    /**
     * Track search query
     * 
     * @param int $user_id User ID
     * @param string $query Search query
     * @param array $additional_data Additional data like filters
     */
    public function track_search($user_id, $query, $additional_data = array()) {
        $data = array_merge(array(
            'query' => $query
        ), $additional_data);
        
        $this->record_event($user_id, 'search', $data);
    }
    
    /**
     * Get user events by type
     * 
     * @param int $user_id User ID
     * @param string $event_type Event type
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Array of events
     */
    public function get_user_events_by_type($user_id, $event_type, $limit = 100, $offset = 0) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE user_id = %d 
            AND event_type = %s 
            ORDER BY timestamp DESC 
            LIMIT %d OFFSET %d",
            $user_id,
            $event_type,
            $limit,
            $offset
        ), ARRAY_A);
        
        // Unserialize event data
        if ($results) {
            foreach ($results as &$result) {
                if (isset($result['event_data'])) {
                    $result['event_data'] = maybe_unserialize($result['event_data']);
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Get user events
     * 
     * @param int $user_id User ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Array of events
     */
    public function get_user_events($user_id, $limit = 100, $offset = 0) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE user_id = %d 
            ORDER BY timestamp DESC 
            LIMIT %d OFFSET %d",
            $user_id,
            $limit,
            $offset
        ), ARRAY_A);
        
        // Unserialize event data
        if ($results) {
            foreach ($results as &$result) {
                if (isset($result['event_data'])) {
                    $result['event_data'] = maybe_unserialize($result['event_data']);
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Get session events
     * 
     * @param string $session_id Session ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Array of events
     */
    public function get_session_events($session_id, $limit = 100, $offset = 0) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE session_id = %s 
            ORDER BY timestamp DESC 
            LIMIT %d OFFSET %d",
            $session_id,
            $limit,
            $offset
        ), ARRAY_A);
        
        // Unserialize event data
        if ($results) {
            foreach ($results as &$result) {
                if (isset($result['event_data'])) {
                    $result['event_data'] = maybe_unserialize($result['event_data']);
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Get event counts by type
     * 
     * @param string $event_type Event type
     * @param int $days_ago How many days back to count
     * @return int Count of events
     */
    public function get_event_counts_by_type($event_type, $days_ago = 30) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE event_type = %s 
            AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $event_type,
            $days_ago
        ));
        
        return (int)$count;
    }
    
    /**
     * Get event counts by user
     * 
     * @param int $user_id User ID
     * @param int $days_ago How many days back to count
     * @return array Array of event types and counts
     */
    public function get_event_counts_by_user($user_id, $days_ago = 30) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT event_type, COUNT(*) as count FROM {$this->table_name} 
            WHERE user_id = %d 
            AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY event_type",
            $user_id,
            $days_ago
        ), ARRAY_A);
        
        $counts = array();
        foreach ($results as $row) {
            $counts[$row['event_type']] = (int)$row['count'];
        }
        
        return $counts;
    }
    
    /**
     * Cleanup old events
     * 
     * @param int $days_to_keep How many days of events to keep
     * @return int Number of events deleted
     */
    public function cleanup_old_events($days_to_keep = 90) {
        global $wpdb;
        
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} 
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days_to_keep
        ));
        
        if ($result !== false) {
            return (int)$result;
        }
        
        return 0;
    }
} 