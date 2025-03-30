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
    
    /**
     * Sanitize context data
     */
    private function sanitize_context_data($context_data) {
        $sanitized = array();
        
        foreach ($context_data as $key => $value) {
            // Sanitize key
            $clean_key = sanitize_key($key);
            
            // Sanitize value based on type
            if (is_array($value)) {
                $sanitized[$clean_key] = $this->sanitize_context_data($value);
            } else if (is_string($value)) {
                $sanitized[$clean_key] = sanitize_text_field($value);
            } else {
                $sanitized[$clean_key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Trim context if it gets too large
     */
    private function trim_context($context) {
        // Prioritize recent data and remove older entries
        if (isset($context['history']) && is_array($context['history'])) {
            // Keep only last 10 history items
            $context['history'] = array_slice($context['history'], -10);
        }
        
        return $context;
    }
    
    /**
     * Get user context
     */
    public function get_context($user_id) {
        $cached = wp_cache_get("thorius_user_context_{$user_id}", 'thorius');
        if ($cached !== false) {
            return $cached;
        }
        
        global $wpdb;
        $context = $wpdb->get_var($wpdb->prepare(
            "SELECT context_data FROM {$this->context_table} WHERE user_id = %d",
            $user_id
        ));
        
        $context_data = $context ? maybe_unserialize($context) : array();
        wp_cache_set("thorius_user_context_{$user_id}", $context_data, 'thorius', 3600);
        
        return $context_data;
    }
} 