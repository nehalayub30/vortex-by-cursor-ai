<?php
/**
 * Thorius Cache Management
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class responsible for managing Thorius cache
 */
class Vortex_Thorius_Cache {
    /**
     * Cache table name
     */
    private $table_name;
    
    /**
     * Cache group
     */
    private $cache_group;
    
    /**
     * Default TTL in seconds
     */
    private $default_ttl;
    
    /**
     * Constructor
     *
     * @param string $cache_group Cache group name
     * @param int $ttl Default TTL in hours
     */
    public function __construct($cache_group = 'thorius', $ttl = 24) {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vortex_thorius_cache';
        $this->cache_group = $cache_group;
        $this->default_ttl = $ttl * HOUR_IN_SECONDS;
        
        // Initialize cleanup schedule
        if (!wp_next_scheduled('vortex_thorius_cache_cleanup')) {
            wp_schedule_event(time(), 'daily', 'vortex_thorius_cache_cleanup');
        }
        
        // Add cleanup hook
        add_action('vortex_thorius_cache_cleanup', array($this, 'cleanup_expired_cache'));
        
        // Add filter for query results
        add_filter('vortex_thorius_before_query', array($this, 'check_query_cache'), 10, 2);
        add_action('vortex_thorius_after_query', array($this, 'store_query_result'), 10, 3);
    }
    
    /**
     * Initialize cache table
     */
    public function init_cache_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            cache_key varchar(191) NOT NULL,
            cache_value longtext NOT NULL,
            cache_group varchar(50) NOT NULL,
            expiration datetime NOT NULL,
            PRIMARY KEY  (cache_key),
            KEY cache_group (cache_group),
            KEY expiration (expiration)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set cache value
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl TTL in seconds (optional)
     * @return bool Success
     */
    public function set($key, $value, $ttl = null) {
        global $wpdb;
        
        if ($ttl === null) {
            $ttl = $this->default_ttl;
        }
        
        // Normalize key
        $key = $this->normalize_key($key);
        
        // Calculate expiration time
        $expiration = date('Y-m-d H:i:s', time() + $ttl);
        
        // Serialize value
        $serialized_value = serialize($value);
        
        // Check if key exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE cache_key = %s",
            $key
        ));
        
        if ($exists) {
            // Update existing cache
            $result = $wpdb->update(
                $this->table_name,
                array(
                    'cache_value' => $serialized_value,
                    'expiration' => $expiration
                ),
                array('cache_key' => $key),
                array('%s', '%s'),
                array('%s')
            );
        } else {
            // Insert new cache
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'cache_key' => $key,
                    'cache_value' => $serialized_value,
                    'cache_group' => $this->cache_group,
                    'expiration' => $expiration
                ),
                array('%s', '%s', '%s', '%s')
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Get cache value
     *
     * @param string $key Cache key
     * @param mixed $default Default value if not found
     * @return mixed Cached value or default
     */
    public function get($key, $default = null) {
        global $wpdb;
        
        // Normalize key
        $key = $this->normalize_key($key);
        
        // Get cached value
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT cache_value FROM {$this->table_name} 
            WHERE cache_key = %s AND expiration > %s",
            $key,
            current_time('mysql')
        ));
        
        if ($value === null) {
            return $default;
        }
        
        // Unserialize value
        $unserialized = @unserialize($value);
        
        if ($unserialized === false && $value !== serialize(false)) {
            return $default;
        }
        
        return $unserialized;
    }
    
    /**
     * Delete cache
     *
     * @param string $key Cache key
     * @return bool Success
     */
    public function delete($key) {
        global $wpdb;
        
        // Normalize key
        $key = $this->normalize_key($key);
        
        $result = $wpdb->delete(
            $this->table_name,
            array('cache_key' => $key),
            array('%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Cleanup expired cache
     *
     * @return int Number of items deleted
     */
    public function cleanup_expired_cache() {
        global $wpdb;
        
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE expiration < %s",
            current_time('mysql')
        ));
        
        return $result;
    }
    
    /**
     * Normalize cache key
     *
     * @param string $key Raw key
     * @return string Normalized key
     */
    private function normalize_key($key) {
        return 'vortex_thorius_' . $key;
    }
    
    /**
     * Cache query results
     *
     * @param bool $is_cached Whether to use cache or not
     */
    private function is_cache_enabled() {
        return !defined('VORTEX_THORIUS_DISABLE_CACHE') || !VORTEX_THORIUS_DISABLE_CACHE;
    }
    
    /**
     * Check for cached query results
     *
     * @param mixed $result Current result (null)
     * @param array $params Query parameters
     * @return mixed Cached result or null
     */
    public function check_query_cache($result, $params) {
        // Skip if cache is disabled
        if (!$this->is_cache_enabled()) {
            return $result;
        }
        
        // Generate cache key based on query params
        $cache_key = 'query_' . md5(serialize($params));
        
        // Get from cache
        $cached = $this->get($cache_key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        return $result;
    }
    
    /**
     * Store query result in cache
     *
     * @param array $params Query parameters
     * @param mixed $result Query result
     * @param int $ttl Time to live in seconds
     */
    public function store_query_result($params, $result, $ttl = null) {
        // Skip if cache is disabled
        if (!$this->is_cache_enabled()) {
            return;
        }
        
        // Skip caching errors
        if (is_wp_error($result)) {
            return;
        }
        
        // Use custom TTL or default for this type of query
        if ($ttl === null) {
            $ttl = $this->get_ttl_for_query_type($params);
        }
        
        // Generate cache key based on query params
        $cache_key = 'query_' . md5(serialize($params));
        
        // Store in cache
        $this->set($cache_key, $result, $ttl);
    }
    
    /**
     * Get appropriate TTL for different query types
     *
     * @param array $params Query parameters
     * @return int TTL in seconds
     */
    private function get_ttl_for_query_type($params) {
        $default_ttl = $this->default_ttl;
        
        // Check for custom TTL setting
        $custom_ttl = get_option('vortex_thorius_cache_ttl', $default_ttl);
        
        if (isset($params['type'])) {
            switch ($params['type']) {
                case 'conversation':
                    // Conversation queries shouldn't be cached as long
                    return min($custom_ttl, 3600); // 1 hour max
                    
                case 'artwork_search':
                    // Art search results can be cached longer
                    return $custom_ttl;
                    
                case 'market_data':
                    // Market data needs fresher data
                    return min($custom_ttl, 1800); // 30 minutes max
                    
                default:
                    return $custom_ttl;
            }
        }
        
        return $custom_ttl;
    }
} 