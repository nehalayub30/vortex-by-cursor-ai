<?php
/**
 * Thorius Cache Manager
 * 
 * Handles caching of API responses and query results for optimal performance
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Cache Manager
 */
class Vortex_Thorius_Cache {
    /**
     * Cache table name
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vortex_thorius_cache';
        
        // Schedule cache cleanup
        if (!wp_next_scheduled('vortex_thorius_cache_cleanup')) {
            wp_schedule_event(time(), 'daily', 'vortex_thorius_cache_cleanup');
        }
        
        // Register cleanup action
        add_action('vortex_thorius_cache_cleanup', array($this, 'cleanup_expired_cache'));
        
        // Register query filters to utilize cache
        add_filter('vortex_thorius_before_query', array($this, 'check_query_cache'), 10, 2);
        add_action('vortex_thorius_after_query', array($this, 'store_query_result'), 10, 3);
    }
    
    /**
     * Get cached item
     * 
     * @param string $key Cache key
     * @return mixed|false Cached data or false if not found
     */
    public function get($key) {
        global $wpdb;
        
        // Check if caching is enabled
        if (!$this->is_caching_enabled()) {
            return false;
        }
        
        // Get cached item
        $cached = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT cache_value, expiry FROM {$this->table_name} WHERE cache_key = %s",
                $key
            )
        );
        
        // Check if item exists and is not expired
        if ($cached && time() < $cached->expiry) {
            return maybe_unserialize($cached->cache_value);
        }
        
        return false;
    }
    
    /**
     * Store item in cache
     * 
     * @param string $key Cache key
     * @param mixed $value Data to cache
     * @param int $ttl Time to live in seconds
     * @return bool Success status
     */
    public function set($key, $value, $ttl = 3600) {
        global $wpdb;
        
        // Check if caching is enabled
        if (!$this->is_caching_enabled()) {
            return false;
        }
        
        $expiry = time() + $ttl;
        $data = maybe_serialize($value);
        
        // Check if key exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE cache_key = %s",
                $key
            )
        );
        
        if ($exists) {
            // Update existing item
            $result = $wpdb->update(
                $this->table_name,
                array(
                    'cache_value' => $data,
                    'expiry' => $expiry
                ),
                array('cache_key' => $key),
                array('%s', '%d'),
                array('%s')
            );
        } else {
            // Insert new item
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'cache_key' => $key,
                    'cache_value' => $data,
                    'expiry' => $expiry
                ),
                array('%s', '%s', '%d')
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Delete cached item
     * 
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete($key) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array('cache_key' => $key),
            array('%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Clear all cache
     * 
     * @return bool Success status
     */
    public function clear_all() {
        global $wpdb;
        
        $result = $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        
        return $result !== false;
    }
    
    /**
     * Clean up expired cache items
     */
    public function cleanup_expired_cache() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE expiry < %d",
                time()
            )
        );
    }
    
    /**
     * Check if query result is in cache
     * 
     * @param bool $should_proceed Whether to proceed with query
     * @param array $query_data Query data
     * @return mixed|bool Cached result or original value
     */
    public function check_query_cache($should_proceed, $query_data) {
        // Skip cache if disabled or if explicitly requested
        if (!$should_proceed || !empty($query_data['skip_cache'])) {
            return $should_proceed;
        }
        
        // Generate cache key
        $cache_key = 'thorius_query_' . md5(serialize($query_data));
        
        // Check cache
        $cached = $this->get($cache_key);
        
        if ($cached !== false) {
            // Return cached response
            return $cached;
        }
        
        return $should_proceed;
    }
    
    /**
     * Store query result in cache
     * 
     * @param array $response Query response
     * @param array $query_data Original query data
     * @param bool $success Whether query was successful
     */
    public function store_query_result($response, $query_data, $success) {
        // Skip if query failed or caching is explicitly skipped
        if (!$success || !empty($query_data['skip_cache'])) {
            return;
        }
        
        // Generate cache key
        $cache_key = 'thorius_query_' . md5(serialize($query_data));
        
        // Get cache TTL based on query type
        $ttl = $this->get_cache_ttl($query_data);
        
        // Store in cache
        $this->set($cache_key, $response, $ttl);
    }
    
    /**
     * Get cache TTL based on query type
     * 
     * @param array $query_data Query data
     * @return int TTL in seconds
     */
    private function get_cache_ttl($query_data) {
        // Default TTL is 1 hour
        $default_ttl = 3600;
        
        // Get custom TTL from options
        $custom_ttl = get_option('vortex_thorius_cache_ttl', $default_ttl);
        
        // Get query-specific TTL
        $query_type = isset($query_data['type']) ? $query_data['type'] : 'default';
        
        switch ($query_type) {
            case 'conversation':
                // Conversations have shorter TTL as they're more dynamic
                return min($custom_ttl, 900); // Max 15 minutes
                
            case 'reference':
                // Reference data can be cached longer
                return max($custom_ttl, 86400); // Min 24 hours
                
            case 'analytics':
                // Analytics data should be kept fresh
                return min($custom_ttl, 300); // Max 5 minutes
                
            default:
                return $custom_ttl;
        }
    }
    
    /**
     * Check if caching is enabled
     * 
     * @return bool Whether caching is enabled
     */
    private function is_caching_enabled() {
        return get_option('vortex_thorius_enable_caching', true);
    }
} 