/**
 * User Context Management
 */
class Vortex_Thorius_Context {
    private $context_table;
    
    public function __construct() {
        global $wpdb;
        $this->context_table = $wpdb->prefix . 'vortex_thorius_user_context';
        $this->init_context_table();
    }
    
    /**
     * Initialize context table if it doesn't exist
     */
    private function init_context_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->context_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            context_data longtext NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY updated_at (updated_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Update user context with improved validation
     */
    public function update_context($user_id, $context_data) {
        global $wpdb;
        
        // Validate input
        if (!is_array($context_data) || empty($context_data)) {
            error_log('Thorius Context Error: Invalid context data');
            return false;
        }
        
        try {
            // Sanitize context data
            $sanitized_context = $this->sanitize_context_data($context_data);
            
            $existing = $this->get_context($user_id);
            $merged_context = array_merge($existing, $sanitized_context);
            
            // Limit context size to prevent database issues
            if (strlen(serialize($merged_context)) > 1000000) { // ~1MB limit
                $merged_context = $this->trim_context($merged_context);
            }
            
            $result = $wpdb->replace(
                $this->context_table,
                array(
                    'user_id' => $user_id,
                    'context_data' => maybe_serialize($merged_context),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s')
            );
            
            if ($result === false) {
                error_log('Thorius Context Update Error: ' . $wpdb->last_error);
                return false;
            }
            
            wp_cache_delete("thorius_user_context_{$user_id}", 'thorius');
            return true;
        } catch (Exception $e) {
            error_log('Thorius Context Exception: ' . $e->getMessage());
            return false;
        }
    }
} 