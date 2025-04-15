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
     * Cache prefix
     */
    private $prefix;
    
    /**
     * Cache TTL (time to live) in hours
     */
    private $ttl;
    
    /**
     * Constructor
     * 
     * @param string $prefix Cache prefix for keys
     * @param int $ttl Cache time to live in hours
     */
    public function __construct($prefix = '', $ttl = 24) {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vortex_thorius_cache';
        $this->prefix = $prefix;
        $this->ttl = $ttl * HOUR_IN_SECONDS;
        
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
        $prefixed_key = $this->get_prefixed_key($key);
        $cached = get_transient($prefixed_key);
        
        if ($cached !== false) {
            return maybe_unserialize($cached);
        }
        
        return false;
    }
    
    /**
     * Store item in cache
     * 
     * @param string $key Cache key
     * @param mixed $value Data to cache
     * @param int $ttl Time to live in seconds, overrides constructor TTL if provided
     * @return bool Success status
     */
    public function set($key, $value, $ttl = null) {
        $prefixed_key = $this->get_prefixed_key($key);
        $expiry = ($ttl !== null) ? $ttl : $this->ttl;
        
        return set_transient($prefixed_key, maybe_serialize($value), $expiry);
    }
    
    /**
     * Delete cached item
     * 
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete($key) {
        $prefixed_key = $this->get_prefixed_key($key);
        return delete_transient($prefixed_key);
    }
    
    /**
     * Clear all cache with current prefix
     * 
     * @return bool Success status
     */
    public function clear() {
        global $wpdb;
        
        if (empty($this->prefix)) {
            // Don't allow clearing all cache without a prefix for safety
            return false;
        }
        
        $like = $wpdb->esc_like('_transient_' . $this->prefix) . '%';
        $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name LIKE %s", $like));
        
        return true;
    }
    
    /**
     * Clean up expired cache items
     */
    public function cleanup_expired_cache() {
        // WordPress automatically cleans up expired transients
        return true;
    }
    
    /**
     * Get prefixed cache key
     * 
     * @param string $key Original key
     * @return string Prefixed key
     */
    private function get_prefixed_key($key) {
        if (empty($this->prefix)) {
            return 'vortex_thorius_' . $key;
        }
        
        return $this->prefix . '_' . $key;
    }
    
    /**
     * Check if caching is enabled
     * 
     * @return bool Whether caching is enabled
     */
    private function is_caching_enabled() {
        return !defined('VORTEX_THORIUS_DISABLE_CACHE') || !VORTEX_THORIUS_DISABLE_CACHE;
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
} 