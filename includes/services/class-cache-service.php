<?php
/**
 * Cache Service
 *
 * @package VortexAiAgents
 * @subpackage Services
 */

namespace VortexAiAgents\Services;

/**
 * Service for handling data caching
 */
class Cache_Service {
    /**
     * Cache group name
     *
     * @var string
     */
    private $group;

    /**
     * Cache expiration time in seconds
     *
     * @var int
     */
    private $expiration;

    /**
     * Whether to use transients instead of object cache
     *
     * @var bool
     */
    private $use_transients;

    /**
     * Constructor
     *
     * @param string $group       Cache group name.
     * @param int    $expiration  Cache expiration time in seconds.
     * @param bool   $use_transients Whether to use transients instead of object cache.
     */
    public function __construct( $group, $expiration = 3600, $use_transients = false ) {
        $this->group = sanitize_key( $group );
        $this->expiration = absint( $expiration );
        $this->use_transients = (bool) $use_transients;
    }

    /**
     * Get data from cache
     *
     * @param string $key Cache key.
     * @return mixed Cached data or false if not found
     */
    public function get( $key ) {
        $cache_key = $this->build_key( $key );
        
        if ( $this->use_transients ) {
            return get_transient( $cache_key );
        }
        
        return wp_cache_get( $cache_key, $this->group );
    }

    /**
     * Set data in cache
     *
     * @param string $key  Cache key.
     * @param mixed  $data Data to cache.
     * @param int    $expiration Optional. Expiration time in seconds. Default is the value set in constructor.
     * @return bool True on success, false on failure
     */
    public function set( $key, $data, $expiration = null ) {
        $cache_key = $this->build_key( $key );
        $expiration = null !== $expiration ? absint( $expiration ) : $this->expiration;
        
        if ( $this->use_transients ) {
            return set_transient( $cache_key, $data, $expiration );
        }
        
        return wp_cache_set( $cache_key, $data, $this->group, $expiration );
    }

    /**
     * Delete data from cache
     *
     * @param string $key Cache key.
     * @return bool True on success, false on failure
     */
    public function delete( $key ) {
        $cache_key = $this->build_key( $key );
        
        if ( $this->use_transients ) {
            return delete_transient( $cache_key );
        }
        
        return wp_cache_delete( $cache_key, $this->group );
    }

    /**
     * Flush all cache in this group
     *
     * @return bool True on success, false on failure
     */
    public function flush() {
        if ( $this->use_transients ) {
            // For transients, we need to get all transients with our prefix and delete them
            global $wpdb;
            
            $prefix = '_transient_' . $this->group . '_';
            $options = $wpdb->options;
            
            $t = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT option_name FROM $options WHERE option_name LIKE %s",
                    $prefix . '%'
                )
            );
            
            if ( ! empty( $t ) ) {
                foreach ( $t as $option ) {
                    $key = str_replace( '_transient_', '', $option->option_name );
                    delete_transient( $key );
                }
            }
            
            return true;
        }
        
        return wp_cache_flush_group( $this->group );
    }

    /**
     * Build a standardized cache key
     *
     * @param string $key Original key.
     * @return string Standardized cache key
     */
    private function build_key( $key ) {
        // Sanitize and limit key length
        $sanitized_key = sanitize_key( $key );
        
        // If using transients, we need to include the group in the key
        if ( $this->use_transients ) {
            return $this->group . '_' . $sanitized_key;
        }
        
        return $sanitized_key;
    }

    /**
     * Get or set cached value with callback for generation
     *
     * @param string   $key      Cache key.
     * @param callable $callback Callback function to generate value if not in cache.
     * @param int      $expiration Optional. Expiration time in seconds.
     * @return mixed Cached or newly generated value
     */
    public function remember( $key, $callback, $expiration = null ) {
        $cached = $this->get( $key );
        
        if ( false !== $cached ) {
            return $cached;
        }
        
        $value = call_user_func( $callback );
        $this->set( $key, $value, $expiration );
        
        return $value;
    }

    /**
     * Force refresh cached value with callback
     *
     * @param string   $key      Cache key.
     * @param callable $callback Callback function to generate new value.
     * @param int      $expiration Optional. Expiration time in seconds.
     * @return mixed Newly generated value
     */
    public function refresh( $key, $callback, $expiration = null ) {
        $value = call_user_func( $callback );
        $this->set( $key, $value, $expiration );
        
        return $value;
    }

    /**
     * Check if a key exists in the cache
     *
     * @param string $key Cache key.
     * @return bool True if key exists, false otherwise
     */
    public function has( $key ) {
        return false !== $this->get( $key );
    }

    /**
     * Increment a numeric cached value
     *
     * @param string $key   Cache key.
     * @param int    $offset Amount to increment by.
     * @return int|false New value on success, false on failure
     */
    public function increment( $key, $offset = 1 ) {
        $cache_key = $this->build_key( $key );
        $offset = absint( $offset );
        
        if ( $this->use_transients ) {
            $value = $this->get( $key );
            
            if ( false === $value ) {
                return false;
            }
            
            $value = intval( $value ) + $offset;
            $this->set( $key, $value );
            
            return $value;
        }
        
        return wp_cache_incr( $cache_key, $offset, $this->group );
    }

    /**
     * Decrement a numeric cached value
     *
     * @param string $key   Cache key.
     * @param int    $offset Amount to decrement by.
     * @return int|false New value on success, false on failure
     */
    public function decrement( $key, $offset = 1 ) {
        $cache_key = $this->build_key( $key );
        $offset = absint( $offset );
        
        if ( $this->use_transients ) {
            $value = $this->get( $key );
            
            if ( false === $value ) {
                return false;
            }
            
            $value = intval( $value ) - $offset;
            $this->set( $key, $value );
            
            return $value;
        }
        
        return wp_cache_decr( $cache_key, $offset, $this->group );
    }

    /**
     * Add data to cache only if it doesn't already exist
     *
     * @param string $key  Cache key.
     * @param mixed  $data Data to cache.
     * @param int    $expiration Optional. Expiration time in seconds.
     * @return bool True if data was added to the cache, false otherwise
     */
    public function add( $key, $data, $expiration = null ) {
        if ( $this->has( $key ) ) {
            return false;
        }
        
        return $this->set( $key, $data, $expiration );
    }

    /**
     * Get multiple cache items
     *
     * @param array $keys Array of cache keys.
     * @return array Array of cache data with keys as requested
     */
    public function get_multiple( $keys ) {
        $result = array();
        
        foreach ( $keys as $key ) {
            $result[ $key ] = $this->get( $key );
        }
        
        return $result;
    }

    /**
     * Set multiple cache items
     *
     * @param array $items      Array of key => value pairs to cache.
     * @param int   $expiration Optional. Expiration time in seconds.
     * @return bool True on success, false on failure
     */
    public function set_multiple( $items, $expiration = null ) {
        $success = true;
        
        foreach ( $items as $key => $value ) {
            $result = $this->set( $key, $value, $expiration );
            $success = $success && $result;
        }
        
        return $success;
    }

    /**
     * Delete multiple cache items
     *
     * @param array $keys Array of cache keys to delete.
     * @return bool True on success, false on failure
     */
    public function delete_multiple( $keys ) {
        $success = true;
        
        foreach ( $keys as $key ) {
            $result = $this->delete( $key );
            $success = $success && $result;
        }
        
        return $success;
    }
} 