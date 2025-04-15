<?php
/**
 * VORTEX Blockchain Error Logger
 *
 * Dedicated error logging and monitoring for blockchain operations
 *
 * @package VORTEX_Marketplace
 * @subpackage Blockchain
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_Blockchain_Error_Logger {
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Log table name
     */
    private $log_table;
    
    /**
     * Maximum logs to keep
     */
    private $max_logs = 1000;
    
    /**
     * Log levels
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    
    /**
     * Operation categories
     */
    const CAT_TRANSACTION = 'transaction';
    const CAT_TOKENIZATION = 'tokenization';
    const CAT_WALLET = 'wallet';
    const CAT_CONTRACT = 'contract';
    const CAT_API = 'api';
    const CAT_SYNC = 'sync';
    const CAT_METRICS = 'metrics';
    const CAT_NETWORK = 'network';
    
    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->log_table = $wpdb->prefix . 'vortex_blockchain_error_logs';
        
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
        if (!wp_next_scheduled('vortex_blockchain_logs_cleanup')) {
            wp_schedule_event(time(), 'daily', 'vortex_blockchain_logs_cleanup');
        }
        
        add_action('vortex_blockchain_logs_cleanup', array($this, 'cleanup_logs'));
        
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Create log table
     */
    private function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->log_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL,
            category varchar(50) NOT NULL,
            operation varchar(100) NOT NULL,
            message text NOT NULL,
            context longtext,
            data longtext,
            tx_hash varchar(66),
            wallet_address varchar(255),
            user_id bigint(20),
            timestamp datetime NOT NULL,
            resolved tinyint(1) NOT NULL DEFAULT 0,
            resolution_notes text,
            PRIMARY KEY (id),
            KEY level (level),
            KEY category (category),
            KEY operation (operation),
            KEY timestamp (timestamp),
            KEY resolved (resolved),
            KEY tx_hash (tx_hash),
            KEY wallet_address (wallet_address),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Log a message
     *
     * @param string $level Log level
     * @param string $category Operation category
     * @param string $operation Specific operation
     * @param string $message Log message
     * @param array $context Additional context
     * @param array $data Technical data
     * @return int|false Log ID or false on failure
     */
    public function log($level, $category, $operation, $message, $context = array(), $data = array()) {
        global $wpdb;
        
        // Validate level
        if (!in_array($level, array(self::LEVEL_DEBUG, self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_ERROR, self::LEVEL_CRITICAL))) {
            $level = self::LEVEL_INFO;
        }
        
        // Only log debug messages if debug mode is enabled
        if ($level === self::LEVEL_DEBUG && !$this->is_debug_enabled()) {
            return false;
        }
        
        // Extract common fields from context if available
        $tx_hash = isset($context['tx_hash']) ? $context['tx_hash'] : null;
        $wallet_address = isset($context['wallet_address']) ? $context['wallet_address'] : null;
        $user_id = isset($context['user_id']) ? $context['user_id'] : null;
        
        // Insert log
        $result = $wpdb->insert(
            $this->log_table,
            array(
                'level' => $level,
                'category' => $category,
                'operation' => $operation,
                'message' => $message,
                'context' => is_array($context) ? json_encode($context) : $context,
                'data' => is_array($data) ? json_encode($data) : $data,
                'tx_hash' => $tx_hash,
                'wallet_address' => $wallet_address,
                'user_id' => $user_id,
                'timestamp' => current_time('mysql'),
                'resolved' => 0
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d')
        );
        
        if ($result) {
            $log_id = $wpdb->insert_id;
            
            // For critical errors, notify admin
            if ($level === self::LEVEL_CRITICAL) {
                $this->notify_admin($log_id, $category, $operation, $message, $context);
            }
            
            // Keep WP debug log in sync for errors and critical issues
            if (in_array($level, array(self::LEVEL_ERROR, self::LEVEL_CRITICAL)) && defined('WP_DEBUG') && WP_DEBUG) {
                error_log("VORTEX Blockchain {$level}: [{$category}] {$operation} - {$message}");
            }
            
            return $log_id;
        }
        
        return false;
    }
    
    /**
     * Log a debug message
     *
     * @param string $category Operation category
     * @param string $operation Specific operation
     * @param string $message Log message
     * @param array $context Additional context
     * @param array $data Technical data
     * @return int|false Log ID or false on failure
     */
    public function debug($category, $operation, $message, $context = array(), $data = array()) {
        return $this->log(self::LEVEL_DEBUG, $category, $operation, $message, $context, $data);
    }
    
    /**
     * Log an info message
     *
     * @param string $category Operation category
     * @param string $operation Specific operation
     * @param string $message Log message
     * @param array $context Additional context
     * @param array $data Technical data
     * @return int|false Log ID or false on failure
     */
    public function info($category, $operation, $message, $context = array(), $data = array()) {
        return $this->log(self::LEVEL_INFO, $category, $operation, $message, $context, $data);
    }
    
    /**
     * Log a warning message
     *
     * @param string $category Operation category
     * @param string $operation Specific operation
     * @param string $message Log message
     * @param array $context Additional context
     * @param array $data Technical data
     * @return int|false Log ID or false on failure
     */
    public function warning($category, $operation, $message, $context = array(), $data = array()) {
        return $this->log(self::LEVEL_WARNING, $category, $operation, $message, $context, $data);
    }
    
    /**
     * Log an error message
     *
     * @param string $category Operation category
     * @param string $operation Specific operation
     * @param string $message Log message
     * @param array $context Additional context
     * @param array $data Technical data
     * @return int|false Log ID or false on failure
     */
    public function error($category, $operation, $message, $context = array(), $data = array()) {
        return $this->log(self::LEVEL_ERROR, $category, $operation, $message, $context, $data);
    }
    
    /**
     * Log a critical message
     *
     * @param string $category Operation category
     * @param string $operation Specific operation
     * @param string $message Log message
     * @param array $context Additional context
     * @param array $data Technical data
     * @return int|false Log ID or false on failure
     */
    public function critical($category, $operation, $message, $context = array(), $data = array()) {
        return $this->log(self::LEVEL_CRITICAL, $category, $operation, $message, $context, $data);
    }
    
    /**
     * Check if debug logging is enabled
     *
     * @return bool True if debug logging is enabled
     */
    private function is_debug_enabled() {
        return get_option('vortex_blockchain_debug_logging', false);
    }
    
    /**
     * Notify admin of critical issue
     *
     * @param int $log_id Log ID
     * @param string $category Operation category
     * @param string $operation Specific operation
     * @param string $message Log message
     * @param array $context Additional context
     */
    private function notify_admin($log_id, $category, $operation, $message, $context) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = "[{$site_name}] Critical blockchain error: {$operation}";
        
        $body = "<h1>Critical Blockchain Error</h1>";
        $body .= "<p><strong>Log ID:</strong> {$log_id}</p>";
        $body .= "<p><strong>Category:</strong> {$category}</p>";
        $body .= "<p><strong>Operation:</strong> {$operation}</p>";
        $body .= "<p><strong>Message:</strong> {$message}</p>";
        
        if (!empty($context)) {
            $body .= "<h2>Context</h2>";
            $body .= "<pre>" . print_r($context, true) . "</pre>";
        }
        
        $body .= "<p>Please check the blockchain error logs in your admin dashboard for more details.</p>";
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($admin_email, $subject, $body, $headers);
    }
    
    /**
     * Get logs with filtering
     *
     * @param array $filters Filters to apply
     * @param int $page Page number
     * @param int $per_page Items per page
     * @return array Logs and count
     */
    public function get_logs($filters = array(), $page = 1, $per_page = 20) {
        global $wpdb;
        
        $default_filters = array(
            'level' => '',
            'category' => '',
            'operation' => '',
            'tx_hash' => '',
            'wallet_address' => '',
            'user_id' => '',
            'resolved' => '',
            'start_date' => '',
            'end_date' => '',
            'search' => ''
        );
        
        $filters = wp_parse_args($filters, $default_filters);
        
        // Build query
        $query = "SELECT * FROM {$this->log_table} WHERE 1=1";
        $count_query = "SELECT COUNT(*) FROM {$this->log_table} WHERE 1=1";
        
        $query_args = array();
        
        // Apply filters
        if (!empty($filters['level'])) {
            $query .= " AND level = %s";
            $count_query .= " AND level = %s";
            $query_args[] = $filters['level'];
        }
        
        if (!empty($filters['category'])) {
            $query .= " AND category = %s";
            $count_query .= " AND category = %s";
            $query_args[] = $filters['category'];
        }
        
        if (!empty($filters['operation'])) {
            $query .= " AND operation = %s";
            $count_query .= " AND operation = %s";
            $query_args[] = $filters['operation'];
        }
        
        if (!empty($filters['tx_hash'])) {
            $query .= " AND tx_hash = %s";
            $count_query .= " AND tx_hash = %s";
            $query_args[] = $filters['tx_hash'];
        }
        
        if (!empty($filters['wallet_address'])) {
            $query .= " AND wallet_address = %s";
            $count_query .= " AND wallet_address = %s";
            $query_args[] = $filters['wallet_address'];
        }
        
        if (!empty($filters['user_id'])) {
            $query .= " AND user_id = %d";
            $count_query .= " AND user_id = %d";
            $query_args[] = $filters['user_id'];
        }
        
        if ($filters['resolved'] !== '') {
            $query .= " AND resolved = %d";
            $count_query .= " AND resolved = %d";
            $query_args[] = (int)$filters['resolved'];
        }
        
        if (!empty($filters['start_date'])) {
            $query .= " AND timestamp >= %s";
            $count_query .= " AND timestamp >= %s";
            $query_args[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $query .= " AND timestamp <= %s";
            $count_query .= " AND timestamp <= %s";
            $query_args[] = $filters['end_date'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (message LIKE %s OR context LIKE %s OR data LIKE %s)";
            $count_query .= " AND (message LIKE %s OR context LIKE %s OR data LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $query_args[] = $search_term;
            $query_args[] = $search_term;
            $query_args[] = $search_term;
        }
        
        // Get total count
        $count = 0;
        if (!empty($query_args)) {
            $count = $wpdb->get_var($wpdb->prepare($count_query, $query_args));
        } else {
            $count = $wpdb->get_var($count_query);
        }
        
        // Add pagination
        $query .= " ORDER BY timestamp DESC";
        $query .= " LIMIT %d, %d";
        $offset = ($page - 1) * $per_page;
        $query_args[] = $offset;
        $query_args[] = $per_page;
        
        // Get logs
        $logs = array();
        if (!empty($query_args)) {
            $logs = $wpdb->get_results($wpdb->prepare($query, $query_args));
        } else {
            $logs = $wpdb->get_results($query);
        }
        
        // Process logs
        foreach ($logs as &$log) {
            if (!empty($log->context)) {
                $log->context = json_decode($log->context, true);
            }
            
            if (!empty($log->data)) {
                $log->data = json_decode($log->data, true);
            }
        }
        
        return array(
            'logs' => $logs,
            'total' => $count,
            'pages' => ceil($count / $per_page)
        );
    }
    
    /**
     * Mark log as resolved
     *
     * @param int $log_id Log ID
     * @param string $notes Resolution notes
     * @return bool Success status
     */
    public function resolve_log($log_id, $notes = '') {
        global $wpdb;
        
        return $wpdb->update(
            $this->log_table,
            array(
                'resolved' => 1,
                'resolution_notes' => $notes
            ),
            array('id' => $log_id),
            array('%d', '%s'),
            array('%d')
        );
    }
    
    /**
     * Cleanup old logs
     */
    public function cleanup_logs() {
        global $wpdb;
        
        // Get log count
        $log_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->log_table}");
        
        // If log count exceeds max logs, delete oldest logs
        if ($log_count > $this->max_logs) {
            $logs_to_delete = $log_count - $this->max_logs;
            
            // Don't delete critical unresolved logs
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$this->log_table}
                WHERE id IN (
                    SELECT id FROM (
                        SELECT id FROM {$this->log_table}
                        WHERE level != %s OR (level = %s AND resolved = 1)
                        ORDER BY timestamp ASC
                        LIMIT %d
                    ) as temp
                )",
                self::LEVEL_CRITICAL,
                self::LEVEL_CRITICAL,
                $logs_to_delete
            ));
        }
    }
    
    /**
     * Register REST API endpoints
     */
    public function register_rest_routes() {
        register_rest_route('vortex/v1', '/blockchain-logs', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_logs'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
        
        register_rest_route('vortex/v1', '/blockchain-logs/(?P<id>\d+)/resolve', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_resolve_log'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
    }
    
    /**
     * REST API endpoint for getting logs
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function rest_get_logs($request) {
        $filters = array();
        
        // Get filters from request
        $filter_keys = array('level', 'category', 'operation', 'tx_hash', 'wallet_address', 'user_id', 'resolved', 'start_date', 'end_date', 'search');
        
        foreach ($filter_keys as $key) {
            if ($request->get_param($key) !== null) {
                $filters[$key] = $request->get_param($key);
            }
        }
        
        // Get pagination params
        $page = $request->get_param('page') ? (int)$request->get_param('page') : 1;
        $per_page = $request->get_param('per_page') ? (int)$request->get_param('per_page') : 20;
        
        // Get logs
        $result = $this->get_logs($filters, $page, $per_page);
        
        return rest_ensure_response($result);
    }
    
    /**
     * REST API endpoint for resolving a log
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function rest_resolve_log($request) {
        $log_id = (int)$request->get_param('id');
        $notes = $request->get_param('notes') ? sanitize_textarea_field($request->get_param('notes')) : '';
        
        $result = $this->resolve_log($log_id, $notes);
        
        if ($result) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Log resolved successfully'
            ));
        }
        
        return new WP_Error(
            'log_not_resolved',
            'Failed to resolve log',
            array('status' => 500)
        );
    }
}

// Initialize logger
VORTEX_Blockchain_Error_Logger::get_instance(); 