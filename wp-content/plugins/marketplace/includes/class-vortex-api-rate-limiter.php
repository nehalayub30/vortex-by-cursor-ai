<?php
/**
 * VORTEX API Rate Limiter
 *
 * Implements rate limiting for API endpoints to prevent abuse
 *
 * @package VORTEX_Marketplace
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_API_Rate_Limiter {
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Rate limit table name
     */
    private $table_name;
    
    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vortex_api_rate_limits';
        
        $this->init();
    }
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Initialize
     */
    private function init() {
        $this->create_table();
        
        // Add cleanup schedule
        if (!wp_next_scheduled('vortex_rate_limit_cleanup')) {
            wp_schedule_event(time(), 'daily', 'vortex_rate_limit_cleanup');
        }
        
        add_action('vortex_rate_limit_cleanup', array($this, 'cleanup_old_entries'));
        
        // Add REST API hooks
        add_action('rest_api_init', array($this, 'register_rate_limiting'), 5);
    }
    
    /**
     * Create table
     */
    private function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            endpoint varchar(255) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_id bigint(20),
            request_count int(11) NOT NULL DEFAULT 1,
            last_request datetime NOT NULL,
            window_start datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY endpoint_ip_user (endpoint, ip_address, user_id),
            KEY window_start (window_start)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Register rate limiting for REST API endpoints
     */
    public function register_rate_limiting() {
        // Apply rate limiting to all VORTEX REST API endpoints
        add_filter('rest_pre_dispatch', array($this, 'check_rate_limit'), 10, 3);
    }
    
    /**
     * Check rate limit for API request
     *
     * @param mixed $result Response to replace the requested version with
     * @param WP_REST_Server $server Server instance
     * @param WP_REST_Request $request Request used to generate the response
     * @return mixed|WP_Error Response or error
     */
    public function check_rate_limit($result, $server, $request) {
        $route = $request->get_route();
        
        // Only rate limit VORTEX API endpoints
        if (strpos($route, '/vortex/') !== 0) {
            return $result;
        }
        
        // Get endpoint-specific rate limits
        $rate_limits = $this->get_rate_limits();
        
        // Find matching rate limit pattern
        $matched_pattern = null;
        $limit = 0;
        $window = 0;
        
        foreach ($rate_limits as $pattern => $config) {
            if (preg_match('#^' . $pattern . '$#', $route)) {
                $matched_pattern = $pattern;
                $limit = $config['limit'];
                $window = $config['window'];
                break;
            }
        }
        
        // If no matching pattern, use default limit
        if (!$matched_pattern) {
            $limit = 60; // 60 requests
            $window = 60; // per minute
        }
        
        // Check if rate limit is exceeded
        $is_limited = $this->is_rate_limited($route, $limit, $window);
        
        if ($is_limited) {
            $retry_after = $this->get_retry_after($route);
            
            return new WP_Error(
                'rest_rate_limited',
                'Too many requests, please try again later.',
                array(
                    'status' => 429,
                    'retry-after' => $retry_after
                )
            );
        }
        
        return $result;
    }
    
    /**
     * Get endpoint-specific rate limits
     *
     * @return array Rate limits by endpoint pattern
     */
    private function get_rate_limits() {
        $defaults = array(
            // Blockchain metrics - 30 requests per minute
            'vortex/v1/blockchain-metrics' => array(
                'limit' => 30,
                'window' => 60
            ),
            // TOLA token data - 30 requests per minute
            'vortex/v1/tola' => array(
                'limit' => 30,
                'window' => 60
            ),
            // Real-time data - 20 requests per minute
            'vortex/v1/real-time' => array(
                'limit' => 20,
                'window' => 60
            ),
            // Artwork data - 60 requests per minute
            'vortex/v1/artworks' => array(
                'limit' => 60,
                'window' => 60
            ),
            // Artist data - 60 requests per minute
            'vortex/v1/artists' => array(
                'limit' => 60,
                'window' => 60
            ),
            // NFT token data - 20 requests per minute
            'vortex/v1/nft' => array(
                'limit' => 20,
                'window' => 60
            ),
            // Auth endpoints - 10 requests per minute
            'vortex/v1/auth' => array(
                'limit' => 10,
                'window' => 60
            ),
            // Write operations - 10 requests per minute
            'vortex/v1/write' => array(
                'limit' => 10,
                'window' => 60
            )
        );
        
        // Allow customizing rate limits
        return apply_filters('vortex_api_rate_limits', $defaults);
    }
    
    /**
     * Check if the current request exceeds rate limits
     *
     * @param string $endpoint API endpoint
     * @param int $limit Request limit
     * @param int $window Time window in seconds
     * @return bool True if rate limited
     */
    public function is_rate_limited($endpoint, $limit, $window) {
        global $wpdb;
        
        // Get client information
        $ip_address = $this->get_client_ip();
        $user_id = get_current_user_id() ? get_current_user_id() : 0;
        
        // Check for whitelisted IPs or admin users
        if ($this->is_whitelisted($ip_address, $user_id)) {
            return false;
        }
        
        // The current time
        $now = current_time('mysql');
        
        // Calculate window start time
        $window_start = date('Y-m-d H:i:s', strtotime("-{$window} seconds"));
        
        // Check if we have a record for this endpoint/IP/user in the current window
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE endpoint = %s
            AND ip_address = %s
            AND (user_id = %d OR user_id IS NULL)
            AND window_start >= %s",
            $endpoint,
            $ip_address,
            $user_id,
            $window_start
        ));
        
        if ($record) {
            // Update existing record
            $new_count = $record->request_count + 1;
            
            $wpdb->update(
                $this->table_name,
                array(
                    'request_count' => $new_count,
                    'last_request' => $now
                ),
                array('id' => $record->id),
                array('%d', '%s'),
                array('%d')
            );
            
            // Check if limit is exceeded
            return $new_count > $limit;
        } else {
            // Create new record
            $wpdb->insert(
                $this->table_name,
                array(
                    'endpoint' => $endpoint,
                    'ip_address' => $ip_address,
                    'user_id' => $user_id ?: null,
                    'request_count' => 1,
                    'last_request' => $now,
                    'window_start' => $now
                ),
                array('%s', '%s', '%d', '%d', '%s', '%s')
            );
            
            // First request is never rate limited
            return false;
        }
    }
    
    /**
     * Get the client IP address
     *
     * @return string IP address
     */
    private function get_client_ip() {
        // Check if using proxy
        $ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
        
        // Handle multiple IPs in HTTP_X_FORWARDED_FOR
        if (strpos($ip, ',') !== false) {
            $ip = explode(',', $ip)[0];
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP);
    }
    
    /**
     * Check if client is whitelisted from rate limiting
     *
     * @param string $ip_address Client IP address
     * @param int $user_id User ID
     * @return bool True if whitelisted
     */
    private function is_whitelisted($ip_address, $user_id) {
        // Allow administrators to bypass rate limits
        if ($user_id && current_user_can('manage_options')) {
            return true;
        }
        
        // Check IP whitelist
        $whitelist = get_option('vortex_api_rate_limit_whitelist', array());
        
        if (in_array($ip_address, $whitelist)) {
            return true;
        }
        
        // Allow for custom whitelisting logic
        return apply_filters('vortex_api_rate_limit_is_whitelisted', false, $ip_address, $user_id);
    }
    
    /**
     * Calculate seconds until rate limit resets
     *
     * @param string $endpoint API endpoint
     * @return int Seconds until rate limit resets
     */
    public function get_retry_after($endpoint) {
        global $wpdb;
        
        $ip_address = $this->get_client_ip();
        $user_id = get_current_user_id() ? get_current_user_id() : 0;
        
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE endpoint = %s
            AND ip_address = %s
            AND (user_id = %d OR user_id IS NULL)
            ORDER BY window_start DESC
            LIMIT 1",
            $endpoint,
            $ip_address,
            $user_id
        ));
        
        if (!$record) {
            return 60; // Default 60 seconds
        }
        
        // Get rate limits
        $rate_limits = $this->get_rate_limits();
        $window = 60; // Default window
        
        // Find matching pattern
        foreach ($rate_limits as $pattern => $config) {
            if (preg_match('#^' . $pattern . '$#', $endpoint)) {
                $window = $config['window'];
                break;
            }
        }
        
        // Calculate seconds since window start
        $window_start = strtotime($record->window_start);
        $now = current_time('timestamp');
        $elapsed = $now - $window_start;
        
        // Calculate seconds until window reset
        $retry_after = max(0, $window - $elapsed);
        
        return $retry_after ?: 1; // Minimum 1 second
    }
    
    /**
     * Cleanup old rate limit entries
     */
    public function cleanup_old_entries() {
        global $wpdb;
        
        // Delete entries older than 1 day
        $cutoff = date('Y-m-d H:i:s', strtotime('-1 day'));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name}
            WHERE window_start < %s",
            $cutoff
        ));
    }
    
    /**
     * Add IP to whitelist
     *
     * @param string $ip_address IP address to whitelist
     * @return bool Success status
     */
    public function add_to_whitelist($ip_address) {
        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        $whitelist = get_option('vortex_api_rate_limit_whitelist', array());
        
        if (!in_array($ip_address, $whitelist)) {
            $whitelist[] = $ip_address;
            return update_option('vortex_api_rate_limit_whitelist', $whitelist);
        }
        
        return true;
    }
    
    /**
     * Remove IP from whitelist
     *
     * @param string $ip_address IP address to remove
     * @return bool Success status
     */
    public function remove_from_whitelist($ip_address) {
        $whitelist = get_option('vortex_api_rate_limit_whitelist', array());
        
        $key = array_search($ip_address, $whitelist);
        
        if ($key !== false) {
            unset($whitelist[$key]);
            return update_option('vortex_api_rate_limit_whitelist', array_values($whitelist));
        }
        
        return true;
    }
}

// Initialize rate limiter
VORTEX_API_Rate_Limiter::get_instance(); 