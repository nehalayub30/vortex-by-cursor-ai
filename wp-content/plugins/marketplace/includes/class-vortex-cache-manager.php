<?php
/**
 * VORTEX Cache Manager Class
 *
 * Handles cache operations for the VORTEX Marketplace plugin
 * Provides centralized methods for setting, getting, and purging caches
 *
 * @package VORTEX
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_Cache_Manager {
    /**
     * Instance of this class
     * @var VORTEX_Cache_Manager
     */
    private static $instance = null;
    
    /**
     * Cache groups
     * @var array
     */
    private $cache_groups = array(
        'marketplace' => 'vortex_marketplace_',
        'api' => 'vortex_api_',
        'investor' => 'vortex_investor_',
        'token' => 'vortex_token_'
    );
    
    /**
     * Default cache expiration times (in seconds)
     * @var array
     */
    private $default_expiration = array(
        'marketplace' => 3600, // 1 hour
        'api' => 3600, // 1 hour
        'investor' => 1800, // 30 minutes
        'token' => 3600 // 1 hour
    );
    
    /**
     * Whether caching is enabled for logged-in users
     * @var bool
     */
    private $cache_for_logged_in = false;
    
    /**
     * Constructor
     */
    private function __construct() {
        // Get cache settings
        $this->cache_for_logged_in = apply_filters('vortex_marketplace_cache_for_logged_in', false);
        
        // Add hooks for cache invalidation
        add_action('vortex_investor_application_submitted', array($this, 'purge_group'), 10, 0, array('investor', 'marketplace'));
        add_action('vortex_investor_status_changed', array($this, 'purge_group'), 10, 0, array('investor', 'marketplace'));
        add_action('vortex_token_transaction_processed', array($this, 'purge_group'), 10, 0, array('token', 'marketplace'));
        
        // Add hook for admin cache purging
        add_action('admin_post_vortex_purge_cache', array($this, 'handle_admin_cache_purge'));
        
        // Add admin settings
        add_action('admin_init', array($this, 'register_cache_settings'));
        add_action('admin_menu', array($this, 'add_cache_settings_page'));
        
        // Add cron event for automatic cache purging
        if (!wp_next_scheduled('vortex_auto_purge_cache')) {
            wp_schedule_event(time(), 'daily', 'vortex_auto_purge_cache');
        }
        add_action('vortex_auto_purge_cache', array($this, 'purge_all'));
    }
    
    /**
     * Get instance of this class
     * @return VORTEX_Cache_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get cache key with prefix
     * 
     * @param string $key The base cache key
     * @param string $group The cache group
     * @return string The prefixed cache key
     */
    public function get_cache_key($key, $group = 'marketplace') {
        $prefix = isset($this->cache_groups[$group]) ? $this->cache_groups[$group] : $this->cache_groups['marketplace'];
        return $prefix . $key;
    }
    
    /**
     * Get cache value
     * 
     * @param string $key The cache key
     * @param string $group The cache group
     * @return mixed The cached value or false if not found
     */
    public function get($key, $group = 'marketplace') {
        // Don't use cache for logged-in users unless explicitly enabled
        if (is_user_logged_in() && !$this->cache_for_logged_in) {
            return false;
        }
        
        $cache_key = $this->get_cache_key($key, $group);
        return get_transient($cache_key);
    }
    
    /**
     * Set cache value
     * 
     * @param string $key The cache key
     * @param mixed $value The value to cache
     * @param string $group The cache group
     * @param int $expiration The cache expiration time in seconds (0 for no expiration)
     * @return bool True if the cache was set successfully
     */
    public function set($key, $value, $group = 'marketplace', $expiration = 0) {
        // Don't set cache for logged-in users unless explicitly enabled
        if (is_user_logged_in() && !$this->cache_for_logged_in) {
            return false;
        }
        
        $cache_key = $this->get_cache_key($key, $group);
        
        // Use default expiration if not specified
        if ($expiration === 0) {
            $expiration = isset($this->default_expiration[$group]) 
                ? $this->default_expiration[$group] 
                : $this->default_expiration['marketplace'];
        }
        
        return set_transient($cache_key, $value, $expiration);
    }
    
    /**
     * Delete a specific cache
     * 
     * @param string $key The cache key
     * @param string $group The cache group
     * @return bool True if the cache was deleted successfully
     */
    public function delete($key, $group = 'marketplace') {
        $cache_key = $this->get_cache_key($key, $group);
        return delete_transient($cache_key);
    }
    
    /**
     * Purge all caches in a group
     * 
     * @param string|array $groups The cache group(s) to purge
     * @return bool True if the operation was successful
     */
    public function purge_group($groups) {
        global $wpdb;
        
        if (!is_array($groups)) {
            $groups = array($groups);
        }
        
        $success = true;
        
        foreach ($groups as $group) {
            if (!isset($this->cache_groups[$group])) {
                continue;
            }
            
            $prefix = $this->cache_groups[$group];
            $query = $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $prefix . '%',
                '_transient_timeout_' . $prefix . '%'
            );
            
            $result = $wpdb->query($query);
            
            if ($result === false) {
                $success = false;
            }
        }
        
        // Notify that cache was purged
        do_action('vortex_cache_purged', $groups);
        
        return $success;
    }
    
    /**
     * Purge all caches
     * 
     * @return bool True if the operation was successful
     */
    public function purge_all() {
        $groups = array_keys($this->cache_groups);
        $result = $this->purge_group($groups);
        
        // Notify that all caches were purged
        do_action('vortex_all_caches_purged');
        
        return $result;
    }
    
    /**
     * Handle admin cache purge
     */
    public function handle_admin_cache_purge() {
        // Check for nonce
        check_admin_referer('vortex_purge_cache_nonce');
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to purge caches.', 'vortex'));
        }
        
        // Get the group to purge
        $group = isset($_POST['cache_group']) ? sanitize_text_field($_POST['cache_group']) : 'all';
        
        // Purge the cache
        if ($group === 'all') {
            $this->purge_all();
        } else {
            $this->purge_group($group);
        }
        
        // Redirect back to the referrer
        wp_safe_redirect(wp_get_referer());
        exit;
    }
    
    /**
     * Add cache control headers
     * 
     * @param int $max_age Max age in seconds
     * @param bool $public Whether the cache is public or private
     */
    public function add_cache_headers($max_age = 3600, $public = false) {
        // Don't set cache headers for logged-in users
        if (is_user_logged_in()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            return;
        }
        
        $cache_control = $public ? 'public' : 'private';
        header("Cache-Control: $cache_control, max-age=$max_age");
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $max_age) . ' GMT');
    }
    
    /**
     * Register cache settings
     */
    public function register_cache_settings() {
        register_setting('vortex_cache_settings', 'vortex_cache_enabled', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ));
        
        register_setting('vortex_cache_settings', 'vortex_cache_for_logged_in', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ));
        
        register_setting('vortex_cache_settings', 'vortex_cache_expiration', array(
            'type' => 'integer',
            'default' => 3600,
            'sanitize_callback' => 'absint',
        ));
        
        register_setting('vortex_cache_settings', 'vortex_auto_purge_frequency', array(
            'type' => 'string',
            'default' => 'daily',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        
        add_settings_section(
            'vortex_cache_settings_section',
            __('Cache Settings', 'vortex'),
            array($this, 'render_cache_settings_section'),
            'vortex_cache_settings'
        );
        
        add_settings_field(
            'vortex_cache_enabled',
            __('Enable Caching', 'vortex'),
            array($this, 'render_cache_enabled_field'),
            'vortex_cache_settings',
            'vortex_cache_settings_section'
        );
        
        add_settings_field(
            'vortex_cache_for_logged_in',
            __('Cache for Logged-in Users', 'vortex'),
            array($this, 'render_cache_for_logged_in_field'),
            'vortex_cache_settings',
            'vortex_cache_settings_section'
        );
        
        add_settings_field(
            'vortex_cache_expiration',
            __('Cache Expiration', 'vortex'),
            array($this, 'render_cache_expiration_field'),
            'vortex_cache_settings',
            'vortex_cache_settings_section'
        );
        
        add_settings_field(
            'vortex_auto_purge_frequency',
            __('Auto-purge Frequency', 'vortex'),
            array($this, 'render_auto_purge_frequency_field'),
            'vortex_cache_settings',
            'vortex_cache_settings_section'
        );
        
        add_settings_field(
            'vortex_purge_cache',
            __('Purge Cache', 'vortex'),
            array($this, 'render_purge_cache_field'),
            'vortex_cache_settings',
            'vortex_cache_settings_section'
        );
    }
    
    /**
     * Add cache settings page
     */
    public function add_cache_settings_page() {
        add_submenu_page(
            'edit.php?post_type=vortex_artwork',
            __('Cache Settings', 'vortex'),
            __('Cache Settings', 'vortex'),
            'manage_options',
            'vortex-cache-settings',
            array($this, 'render_cache_settings_page')
        );
    }
    
    /**
     * Render cache settings section
     */
    public function render_cache_settings_section() {
        echo '<p>' . __('Configure caching settings for the marketplace.', 'vortex') . '</p>';
    }
    
    /**
     * Render cache enabled field
     */
    public function render_cache_enabled_field() {
        $enabled = get_option('vortex_cache_enabled', true);
        echo '<label><input type="checkbox" name="vortex_cache_enabled" value="1" ' . checked($enabled, true, false) . ' /> ' . __('Enable caching for better performance', 'vortex') . '</label>';
    }
    
    /**
     * Render cache for logged-in users field
     */
    public function render_cache_for_logged_in_field() {
        $cache_for_logged_in = get_option('vortex_cache_for_logged_in', false);
        echo '<label><input type="checkbox" name="vortex_cache_for_logged_in" value="1" ' . checked($cache_for_logged_in, true, false) . ' /> ' . __('Enable caching for logged-in users (not recommended for dynamic content)', 'vortex') . '</label>';
    }
    
    /**
     * Render cache expiration field
     */
    public function render_cache_expiration_field() {
        $expiration = get_option('vortex_cache_expiration', 3600);
        echo '<select name="vortex_cache_expiration">';
        $options = array(
            300 => __('5 minutes', 'vortex'),
            900 => __('15 minutes', 'vortex'),
            1800 => __('30 minutes', 'vortex'),
            3600 => __('1 hour', 'vortex'),
            7200 => __('2 hours', 'vortex'),
            21600 => __('6 hours', 'vortex'),
            43200 => __('12 hours', 'vortex'),
            86400 => __('1 day', 'vortex'),
            604800 => __('1 week', 'vortex'),
        );
        
        foreach ($options as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($expiration, $value, false) . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
    }
    
    /**
     * Render auto-purge frequency field
     */
    public function render_auto_purge_frequency_field() {
        $frequency = get_option('vortex_auto_purge_frequency', 'daily');
        echo '<select name="vortex_auto_purge_frequency">';
        $options = array(
            'hourly' => __('Hourly', 'vortex'),
            'twicedaily' => __('Twice Daily', 'vortex'),
            'daily' => __('Daily', 'vortex'),
            'weekly' => __('Weekly', 'vortex'),
        );
        
        foreach ($options as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($frequency, $value, false) . '>' . esc_html($label) . '</option>';
        }
        
        echo '</select>';
    }
    
    /**
     * Render purge cache field
     */
    public function render_purge_cache_field() {
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="vortex_purge_cache">
            <?php wp_nonce_field('vortex_purge_cache_nonce'); ?>
            
            <select name="cache_group">
                <option value="all"><?php esc_html_e('All Caches', 'vortex'); ?></option>
                <option value="marketplace"><?php esc_html_e('Marketplace Cache', 'vortex'); ?></option>
                <option value="api"><?php esc_html_e('API Cache', 'vortex'); ?></option>
                <option value="investor"><?php esc_html_e('Investor Cache', 'vortex'); ?></option>
                <option value="token"><?php esc_html_e('Token Cache', 'vortex'); ?></option>
            </select>
            
            <input type="submit" class="button" value="<?php esc_attr_e('Purge Cache', 'vortex'); ?>">
        </form>
        <?php
    }
    
    /**
     * Render cache settings page
     */
    public function render_cache_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Cache Settings', 'vortex'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('vortex_cache_settings');
                do_settings_sections('vortex_cache_settings');
                submit_button();
                ?>
            </form>
            
            <div class="vortex-cache-info">
                <h2><?php esc_html_e('Cache Information', 'vortex'); ?></h2>
                
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Cache Group', 'vortex'); ?></th>
                            <th><?php esc_html_e('Size', 'vortex'); ?></th>
                            <th><?php esc_html_e('Items', 'vortex'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        global $wpdb;
                        
                        foreach ($this->cache_groups as $group => $prefix) {
                            $count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE %s",
                                '_transient_' . $prefix . '%'
                            ));
                            
                            echo '<tr>';
                            echo '<td>' . esc_html(ucfirst($group)) . '</td>';
                            echo '<td>-</td>';
                            echo '<td>' . esc_html($count) . '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
                
                <p class="description">
                    <?php esc_html_e('Cache is stored using WordPress transients in the options table.', 'vortex'); ?>
                </p>
            </div>
        </div>
        <?php
    }
}

// Initialize the cache manager
function vortex_cache_manager() {
    return VORTEX_Cache_Manager::get_instance();
}

// Global function for easy access to cache manager
function vortex_get_cache($key, $group = 'marketplace') {
    return vortex_cache_manager()->get($key, $group);
}

function vortex_set_cache($key, $value, $group = 'marketplace', $expiration = 0) {
    return vortex_cache_manager()->set($key, $value, $group, $expiration);
}

function vortex_delete_cache($key, $group = 'marketplace') {
    return vortex_cache_manager()->delete($key, $group);
}

function vortex_purge_cache_group($group) {
    return vortex_cache_manager()->purge_group($group);
}

function vortex_purge_all_caches() {
    return vortex_cache_manager()->purge_all();
} 