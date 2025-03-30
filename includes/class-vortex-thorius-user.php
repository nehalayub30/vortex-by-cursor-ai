/**
 * User Library Management for Thorius
 */
class Vortex_Thorius_User {
    /**
     * User preferences table
     */
    private $preferences_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->preferences_table = $wpdb->prefix . 'vortex_thorius_user_preferences';
        
        // Initialize tables if needed
        $this->init_tables();
    }
    
    /**
     * Initialize database tables
     */
    private function init_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->preferences_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            preference_key varchar(191) NOT NULL,
            preference_value longtext NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY preference_key (preference_key)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get user preferences
     */
    public function get_preferences($user_id) {
        global $wpdb;
        
        $cached = wp_cache_get("thorius_user_prefs_{$user_id}", 'thorius');
        if ($cached !== false) {
            return $cached;
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT preference_key, preference_value FROM {$this->preferences_table} WHERE user_id = %d",
            $user_id
        ), ARRAY_A);
        
        $preferences = array();
        foreach ($results as $row) {
            $preferences[$row['preference_key']] = maybe_unserialize($row['preference_value']);
        }
        
        wp_cache_set("thorius_user_prefs_{$user_id}", $preferences, 'thorius', 3600);
        
        return $preferences;
    }
    
    /**
     * Update user preference with improved error handling
     */
    public function update_preference($user_id, $key, $value) {
        global $wpdb;
        
        try {
            $serialized_value = maybe_serialize($value);
            
            $result = $wpdb->replace(
                $this->preferences_table,
                array(
                    'user_id' => $user_id,
                    'preference_key' => $key,
                    'preference_value' => $serialized_value,
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                error_log('Thorius Error: Failed to update user preference - ' . $wpdb->last_error);
                return false;
            }
            
            wp_cache_delete("thorius_user_prefs_{$user_id}", 'thorius');
            return true;
        } catch (Exception $e) {
            error_log('Thorius Exception: ' . $e->getMessage());
            return false;
        }
    }
} 