<?php
/**
 * AI Agent Response Cache
 *
 * Implements caching for common AI agent responses and request rate limiting
 * to prevent API abuse and improve performance.
 *
 * @package VORTEX
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_AI_Response_Cache {
    
    private static $instance = null;
    private $cache_expiration = 3600; // 1 hour default
    private $rate_limits = array();
    private $db;
    
    /**
     * Get class instance
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
        $this->db = $wpdb;
        
        // Set default rate limits per agent (requests per minute)
        $this->rate_limits = array(
            'huraii' => 10,
            'cloe' => 15,
            'business_strategist' => 8,
            'thorius' => 5
        );
        
        // Allow rate limits to be overridden via options
        foreach ($this->rate_limits as $agent => $limit) {
            $option_name = "vortex_{$agent}_rate_limit";
            $option_value = get_option($option_name, null);
            if ($option_value !== null) {
                $this->rate_limits[$agent] = intval($option_value);
            }
        }
        
        // Create table if it doesn't exist
        $this->ensure_tables_exist();
        
        // Schedule cleanup of old cache entries
        if (!wp_next_scheduled('vortex_ai_cache_cleanup')) {
            wp_schedule_event(time(), 'daily', 'vortex_ai_cache_cleanup');
        }
        add_action('vortex_ai_cache_cleanup', array($this, 'cleanup_cache'));
    }
    
    /**
     * Create necessary tables if they don't exist
     */
    private function ensure_tables_exist() {
        $table_name = $this->db->prefix . 'vortex_ai_response_cache';
        $rate_limit_table = $this->db->prefix . 'vortex_ai_rate_limits';
        
        if ($this->db->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $this->db->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                agent varchar(50) NOT NULL,
                request_hash varchar(64) NOT NULL,
                request_data text NOT NULL,
                response_data longtext NOT NULL,
                created_at datetime NOT NULL,
                expires_at datetime NOT NULL,
                hit_count int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY  (id),
                UNIQUE KEY request_hash (agent, request_hash),
                KEY expires_at (expires_at)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        if ($this->db->get_var("SHOW TABLES LIKE '$rate_limit_table'") != $rate_limit_table) {
            $charset_collate = $this->db->get_charset_collate();
            
            $sql = "CREATE TABLE $rate_limit_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                agent varchar(50) NOT NULL,
                user_id bigint(20) NOT NULL,
                ip_address varchar(45) NOT NULL,
                request_count int(11) NOT NULL DEFAULT 1,
                last_request datetime NOT NULL,
                time_window datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY time_window (time_window),
                KEY user_lookup (agent, user_id, ip_address)
            ) $charset_collate;";
            
            dbDelta($sql);
        }
    }
    
    /**
     * Get cached response
     *
     * @param string $agent Agent identifier (huraii, cloe, business_strategist, thorius)
     * @param array $request_data The request data to check for in cache
     * @return array|false Cached response or false if not found
     */
    public function get_cached_response($agent, $request_data) {
        // Generate a hash of the request data
        $request_hash = $this->hash_request($agent, $request_data);
        
        // Check if we have a cache hit
        $cached = $this->db->get_row($this->db->prepare(
            "SELECT * FROM {$this->db->prefix}vortex_ai_response_cache 
            WHERE agent = %s AND request_hash = %s AND expires_at > NOW()",
            $agent,
            $request_hash
        ));
        
        if (!$cached) {
            return false;
        }
        
        // Update hit count
        $this->db->query($this->db->prepare(
            "UPDATE {$this->db->prefix}vortex_ai_response_cache 
            SET hit_count = hit_count + 1 
            WHERE id = %d",
            $cached->id
        ));
        
        // Return cached response
        return array(
            'agent' => $cached->agent,
            'response' => json_decode($cached->response_data, true),
            'created_at' => $cached->created_at,
            'hit_count' => $cached->hit_count + 1 // +1 for the current hit
        );
    }
    
    /**
     * Store response in cache
     *
     * @param string $agent Agent identifier
     * @param array $request_data The request data to cache
     * @param array $response_data The response data to cache
     * @param int $expiration Optional custom expiration in seconds
     * @return bool Success status
     */
    public function cache_response($agent, $request_data, $response_data, $expiration = null) {
        // Check if caching is enabled for this agent
        if (!$this->is_caching_enabled($agent)) {
            return false;
        }
        
        // Skip non-cacheable requests
        if (!$this->is_cacheable($agent, $request_data)) {
            return false;
        }
        
        // Generate a hash of the request data
        $request_hash = $this->hash_request($agent, $request_data);
        
        // Set expiration time
        if ($expiration === null) {
            $expiration = $this->get_cache_expiration($agent, $request_data);
        }
        
        $expires_at = date('Y-m-d H:i:s', time() + $expiration);
        
        // Store in database
        return $this->db->replace(
            $this->db->prefix . 'vortex_ai_response_cache',
            array(
                'agent' => $agent,
                'request_hash' => $request_hash,
                'request_data' => json_encode($request_data),
                'response_data' => json_encode($response_data),
                'created_at' => current_time('mysql'),
                'expires_at' => $expires_at,
                'hit_count' => 0
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d')
        ) !== false;
    }
    
    /**
     * Check if a request can be made under rate limits
     *
     * @param string $agent Agent identifier
     * @param int $user_id User ID (0 for guests)
     * @param string $ip_address IP address
     * @return bool Whether request is allowed
     */
    public function check_rate_limit($agent, $user_id = 0, $ip_address = '') {
        // Always allow admin users
        if ($user_id > 0 && current_user_can('manage_options')) {
            return true;
        }
        
        // Get rate limit for agent
        $rate_limit = isset($this->rate_limits[$agent]) ? $this->rate_limits[$agent] : 5;
        
        // Get IP address if not provided
        if (empty($ip_address)) {
            $ip_address = $this->get_client_ip();
        }
        
        // Check current request count in the time window
        $time_window = date('Y-m-d H:i:s', strtotime('-1 minute'));
        
        $request_count = $this->db->get_var($this->db->prepare(
            "SELECT request_count FROM {$this->db->prefix}vortex_ai_rate_limits 
            WHERE agent = %s AND (user_id = %d OR ip_address = %s) AND time_window > %s 
            ORDER BY request_count DESC LIMIT 1",
            $agent,
            $user_id,
            $ip_address,
            $time_window
        ));
        
        if ($request_count && intval($request_count) >= $rate_limit) {
            // Rate limit exceeded
            return false;
        }
        
        // Record this request
        $this->record_request($agent, $user_id, $ip_address);
        
        return true;
    }
    
    /**
     * Record an API request for rate limiting
     *
     * @param string $agent Agent identifier
     * @param int $user_id User ID
     * @param string $ip_address IP address
     */
    private function record_request($agent, $user_id, $ip_address) {
        // Check if entry exists for this user/IP in current time window
        $time_window = date('Y-m-d H:i:s', strtotime('-1 minute'));
        
        $existing = $this->db->get_row($this->db->prepare(
            "SELECT id, request_count FROM {$this->db->prefix}vortex_ai_rate_limits 
            WHERE agent = %s AND user_id = %d AND ip_address = %s AND time_window > %s",
            $agent,
            $user_id,
            $ip_address,
            $time_window
        ));
        
        if ($existing) {
            // Update existing entry
            $this->db->update(
                $this->db->prefix . 'vortex_ai_rate_limits',
                array(
                    'request_count' => $existing->request_count + 1,
                    'last_request' => current_time('mysql')
                ),
                array('id' => $existing->id),
                array('%d', '%s'),
                array('%d')
            );
        } else {
            // Create new entry
            $new_time_window = date('Y-m-d H:i:s', strtotime('+1 minute'));
            
            $this->db->insert(
                $this->db->prefix . 'vortex_ai_rate_limits',
                array(
                    'agent' => $agent,
                    'user_id' => $user_id,
                    'ip_address' => $ip_address,
                    'request_count' => 1,
                    'last_request' => current_time('mysql'),
                    'time_window' => $new_time_window
                ),
                array('%s', '%d', '%s', '%d', '%s', '%s')
            );
        }
    }
    
    /**
     * Clean up expired cache entries
     */
    public function cleanup_cache() {
        // Delete expired cache entries
        $this->db->query("DELETE FROM {$this->db->prefix}vortex_ai_response_cache WHERE expires_at < NOW()");
        
        // Delete old rate limit records (older than 1 hour)
        $this->db->query("DELETE FROM {$this->db->prefix}vortex_ai_rate_limits WHERE time_window < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        
        // Log cleanup
        $deleted_count = $this->db->rows_affected;
        if ($deleted_count > 0) {
            error_log("VORTEX AI: Cleaned up $deleted_count expired cache entries");
        }
    }
    
    /**
     * Generate a unique hash for a request
     *
     * @param string $agent Agent identifier
     * @param array $request_data Request data
     * @return string Hash
     */
    private function hash_request($agent, $request_data) {
        // Remove non-deterministic fields like timestamps or random IDs
        $normalized_data = $this->normalize_request_data($agent, $request_data);
        
        // Serialize and hash
        return hash('sha256', $agent . '-' . json_encode($normalized_data));
    }
    
    /**
     * Normalize request data for consistent hashing
     *
     * @param string $agent Agent identifier
     * @param array $request_data Original request data
     * @return array Normalized data
     */
    private function normalize_request_data($agent, $request_data) {
        $normalized = $request_data;
        
        // Remove non-deterministic fields
        $fields_to_remove = array('timestamp', 'request_id', 'random_seed');
        
        foreach ($fields_to_remove as $field) {
            if (isset($normalized[$field])) {
                unset($normalized[$field]);
            }
        }
        
        // Agent-specific normalization
        switch ($agent) {
            case 'huraii':
                // For image generation, keep only the prompt and style but not params
                if (isset($normalized['type']) && $normalized['type'] === 'image_generation') {
                    $normalized = array(
                        'type' => 'image_generation',
                        'prompt' => $normalized['prompt'],
                        'style' => isset($normalized['style']) ? $normalized['style'] : 'default'
                    );
                }
                break;
                
            case 'cloe':
                // For market analysis, keep only essential market parameters
                if (isset($normalized['analysis_type']) && $normalized['analysis_type'] === 'market') {
                    // Remove time-sensitive data for market analysis
                    if (isset($normalized['date_range'])) {
                        unset($normalized['date_range']);
                    }
                }
                break;
        }
        
        return $normalized;
    }
    
    /**
     * Determine if a request is cacheable
     *
     * @param string $agent Agent identifier
     * @param array $request_data Request data
     * @return bool Whether request is cacheable
     */
    private function is_cacheable($agent, $request_data) {
        // Some requests should never be cached
        
        // Check for force_refresh flag
        if (isset($request_data['force_refresh']) && $request_data['force_refresh']) {
            return false;
        }
        
        // Agent-specific caching rules
        switch ($agent) {
            case 'huraii':
                // Don't cache personalized creative requests
                if (isset($request_data['personalized']) && $request_data['personalized']) {
                    return false;
                }
                
                // Don't cache requests with personal data
                if (isset($request_data['prompt']) && 
                    (stripos($request_data['prompt'], 'my ') !== false || 
                     stripos($request_data['prompt'], 'mine') !== false || 
                     stripos($request_data['prompt'], 'I want') !== false)) {
                    return false;
                }
                break;
                
            case 'cloe':
                // Don't cache real-time market data that constantly changes
                if (isset($request_data['analysis_type']) && 
                    $request_data['analysis_type'] === 'real_time_market') {
                    return false;
                }
                break;
                
            case 'business_strategist':
                // Don't cache personalized business advice
                if (isset($request_data['business_id']) || isset($request_data['user_id'])) {
                    return false;
                }
                break;
                
            case 'thorius':
                // Don't cache blockchain transaction verification requests
                if (isset($request_data['type']) && 
                    ($request_data['type'] === 'verify_transaction' || 
                     $request_data['type'] === 'transaction_status')) {
                    return false;
                }
                break;
        }
        
        return true;
    }
    
    /**
     * Get cache expiration time for a specific request
     *
     * @param string $agent Agent identifier
     * @param array $request_data Request data
     * @return int Expiration time in seconds
     */
    private function get_cache_expiration($agent, $request_data) {
        // Start with default expiration
        $expiration = $this->cache_expiration;
        
        // Agent-specific expiration times
        switch ($agent) {
            case 'huraii':
                // Creative content can be cached longer
                $expiration = 24 * 3600; // 24 hours
                
                // Image generation responses should be cached longer
                if (isset($request_data['type']) && $request_data['type'] === 'image_generation') {
                    $expiration = 7 * 24 * 3600; // 7 days
                }
                break;
                
            case 'cloe':
                // Market analysis expires faster
                if (isset($request_data['analysis_type']) && $request_data['analysis_type'] === 'market') {
                    $expiration = 6 * 3600; // 6 hours
                }
                break;
                
            case 'business_strategist':
                // Business advice is valid for a medium time
                $expiration = 12 * 3600; // 12 hours
                break;
                
            case 'thorius':
                // Blockchain information should refresh more frequently
                $expiration = 2 * 3600; // 2 hours
                break;
        }
        
        // Allow for custom expiration via filter
        return apply_filters('vortex_ai_cache_expiration', $expiration, $agent, $request_data);
    }
    
    /**
     * Check if caching is enabled for an agent
     *
     * @param string $agent Agent identifier
     * @return bool Whether caching is enabled
     */
    private function is_caching_enabled($agent) {
        $option_name = "vortex_{$agent}_enable_cache";
        return get_option($option_name, true);
    }
    
    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
    
    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function get_cache_stats() {
        // Get total cache entries
        $total_entries = $this->db->get_var("SELECT COUNT(*) FROM {$this->db->prefix}vortex_ai_response_cache");
        
        // Get hits by agent
        $hits_by_agent = $this->db->get_results(
            "SELECT agent, SUM(hit_count) as hits, COUNT(*) as entries 
            FROM {$this->db->prefix}vortex_ai_response_cache 
            GROUP BY agent",
            OBJECT_K
        );
        
        // Get popular cache items
        $popular_items = $this->db->get_results(
            "SELECT id, agent, request_data, hit_count 
            FROM {$this->db->prefix}vortex_ai_response_cache 
            ORDER BY hit_count DESC 
            LIMIT 10"
        );
        
        // Get agents that exceed rate limits often
        $rate_limit_offenders = $this->db->get_results(
            "SELECT agent, user_id, ip_address, MAX(request_count) as max_requests 
            FROM {$this->db->prefix}vortex_ai_rate_limits 
            GROUP BY agent, user_id, ip_address 
            HAVING max_requests > 10 
            ORDER BY max_requests DESC 
            LIMIT 10"
        );
        
        return array(
            'total_entries' => $total_entries,
            'hits_by_agent' => $hits_by_agent,
            'popular_items' => $popular_items,
            'rate_limit_offenders' => $rate_limit_offenders
        );
    }
    
    /**
     * Flush cache for specific agent
     *
     * @param string $agent Agent identifier
     * @return int Number of entries removed
     */
    public function flush_agent_cache($agent) {
        $this->db->query($this->db->prepare(
            "DELETE FROM {$this->db->prefix}vortex_ai_response_cache WHERE agent = %s",
            $agent
        ));
        
        return $this->db->rows_affected;
    }
    
    /**
     * Register action hooks for AI API calls
     */
    public function register_hooks() {
        // Wrap the API handlers with caching and rate limiting
        add_filter('vortex_pre_huraii_api_call', array($this, 'intercept_huraii_call'), 10, 2);
        add_filter('vortex_pre_cloe_api_call', array($this, 'intercept_cloe_call'), 10, 2);
        add_filter('vortex_pre_business_strategist_api_call', array($this, 'intercept_business_strategist_call'), 10, 2);
        add_filter('vortex_pre_thorius_api_call', array($this, 'intercept_thorius_call'), 10, 2);
        
        // Store responses in cache
        add_action('vortex_after_huraii_api_call', array($this, 'store_huraii_response'), 10, 3);
        add_action('vortex_after_cloe_api_call', array($this, 'store_cloe_response'), 10, 3);
        add_action('vortex_after_business_strategist_api_call', array($this, 'store_business_strategist_response'), 10, 3);
        add_action('vortex_after_thorius_api_call', array($this, 'store_thorius_response'), 10, 3);
    }
    
    /**
     * Intercept HURAII API call
     *
     * @param array|null $cached_response
     * @param array $request_data
     * @return array|null Cached response or null
     */
    public function intercept_huraii_call($cached_response, $request_data) {
        $user_id = get_current_user_id();
        
        // Check rate limit
        if (!$this->check_rate_limit('huraii', $user_id)) {
            return array(
                'error' => true,
                'message' => 'Rate limit exceeded. Please try again later.'
            );
        }
        
        // Check cache
        return $this->get_cached_response('huraii', $request_data);
    }
    
    /**
     * Store HURAII response in cache
     *
     * @param array $request_data
     * @param array $response_data
     * @param int $runtime_ms
     */
    public function store_huraii_response($request_data, $response_data, $runtime_ms) {
        $this->cache_response('huraii', $request_data, $response_data);
    }
    
    /**
     * Intercept CLOE API call
     *
     * @param array|null $cached_response
     * @param array $request_data
     * @return array|null Cached response or null
     */
    public function intercept_cloe_call($cached_response, $request_data) {
        $user_id = get_current_user_id();
        
        // Check rate limit
        if (!$this->check_rate_limit('cloe', $user_id)) {
            return array(
                'error' => true,
                'message' => 'Rate limit exceeded. Please try again later.'
            );
        }
        
        // Check cache
        return $this->get_cached_response('cloe', $request_data);
    }
    
    /**
     * Store CLOE response in cache
     *
     * @param array $request_data
     * @param array $response_data
     * @param int $runtime_ms
     */
    public function store_cloe_response($request_data, $response_data, $runtime_ms) {
        $this->cache_response('cloe', $request_data, $response_data);
    }
    
    /**
     * Intercept Business Strategist API call
     *
     * @param array|null $cached_response
     * @param array $request_data
     * @return array|null Cached response or null
     */
    public function intercept_business_strategist_call($cached_response, $request_data) {
        $user_id = get_current_user_id();
        
        // Check rate limit
        if (!$this->check_rate_limit('business_strategist', $user_id)) {
            return array(
                'error' => true,
                'message' => 'Rate limit exceeded. Please try again later.'
            );
        }
        
        // Check cache
        return $this->get_cached_response('business_strategist', $request_data);
    }
    
    /**
     * Store Business Strategist response in cache
     *
     * @param array $request_data
     * @param array $response_data
     * @param int $runtime_ms
     */
    public function store_business_strategist_response($request_data, $response_data, $runtime_ms) {
        $this->cache_response('business_strategist', $request_data, $response_data);
    }
    
    /**
     * Intercept Thorius API call
     *
     * @param array|null $cached_response
     * @param array $request_data
     * @return array|null Cached response or null
     */
    public function intercept_thorius_call($cached_response, $request_data) {
        $user_id = get_current_user_id();
        
        // Check rate limit
        if (!$this->check_rate_limit('thorius', $user_id)) {
            return array(
                'error' => true,
                'message' => 'Rate limit exceeded. Please try again later.'
            );
        }
        
        // Check cache
        return $this->get_cached_response('thorius', $request_data);
    }
    
    /**
     * Store Thorius response in cache
     *
     * @param array $request_data
     * @param array $response_data
     * @param int $runtime_ms
     */
    public function store_thorius_response($request_data, $response_data, $runtime_ms) {
        $this->cache_response('thorius', $request_data, $response_data);
    }
}

// Initialize AI Response Cache
function vortex_ai_response_cache() {
    $instance = VORTEX_AI_Response_Cache::get_instance();
    $instance->register_hooks();
    return $instance;
}
vortex_ai_response_cache(); 