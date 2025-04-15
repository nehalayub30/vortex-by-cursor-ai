<?php
/**
 * Thorius Context Management
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class responsible for managing Thorius context
 */
class Vortex_Thorius_Context {
    /**
     * Context table name
     */
    private $context_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->context_table = $wpdb->prefix . 'vortex_thorius_user_context';
        
        // Initialize context table
        $this->init_context_table();
    }
    
    /**
     * Initialize context table
     */
    private function init_context_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->context_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            session_id varchar(191) NOT NULL,
            context_key varchar(191) NOT NULL,
            context_value longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_session_key (user_id, session_id, context_key),
            KEY user_id (user_id),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set context value
     *
     * @param int $user_id User ID
     * @param string $session_id Session ID
     * @param string $key Context key
     * @param mixed $value Context value
     * @return bool Success
     */
    public function set_context($user_id, $session_id, $key, $value) {
        global $wpdb;
        
        // Check if context already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->context_table} 
            WHERE user_id = %d AND session_id = %s AND context_key = %s",
            $user_id, $session_id, $key
        ));
        
        if ($existing) {
            // Update existing context
            $result = $wpdb->update(
                $this->context_table,
                array(
                    'context_value' => json_encode($value),
                    'updated_at' => current_time('mysql')
                ),
                array(
                    'user_id' => $user_id,
                    'session_id' => $session_id,
                    'context_key' => $key
                ),
                array('%s', '%s'),
                array('%d', '%s', '%s')
            );
        } else {
            // Insert new context
            $result = $wpdb->insert(
                $this->context_table,
                array(
                    'user_id' => $user_id,
                    'session_id' => $session_id,
                    'context_key' => $key,
                    'context_value' => json_encode($value),
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s')
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Get context value
     *
     * @param int $user_id User ID
     * @param string $session_id Session ID
     * @param string $key Context key
     * @param mixed $default Default value if context not found
     * @return mixed Context value
     */
    public function get_context_value($user_id, $session_id, $key, $default = null) {
        global $wpdb;
        
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT context_value FROM {$this->context_table} 
            WHERE user_id = %d AND session_id = %s AND context_key = %s",
            $user_id, $session_id, $key
        ));
        
        if ($value === null) {
            return $default;
        }
        
        return json_decode($value, true);
    }
    
    /**
     * Get all context for a session
     *
     * @param int $user_id User ID
     * @param string $session_id Session ID
     * @return array Context data
     */
    public function get_context($user_id, $session_id) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT context_key, context_value FROM {$this->context_table} 
            WHERE user_id = %d AND session_id = %s",
            $user_id, $session_id
        ), ARRAY_A);
        
        $context = array();
        
        foreach ($results as $row) {
            $context[$row['context_key']] = json_decode($row['context_value'], true);
        }
        
        return $context;
    }
    
    /**
     * Delete context
     *
     * @param int $user_id User ID
     * @param string $session_id Session ID
     * @param string $key Optional context key
     * @return int|false Number of rows deleted or false on failure
     */
    public function delete_context($user_id, $session_id, $key = null) {
        global $wpdb;
        
        $where = array(
            'user_id' => $user_id,
            'session_id' => $session_id
        );
        
        $where_format = array('%d', '%s');
        
        if ($key !== null) {
            $where['context_key'] = $key;
            $where_format[] = '%s';
        }
        
        return $wpdb->delete($this->context_table, $where, $where_format);
    }
    
    /**
     * Update multiple context values at once
     *
     * @param int $user_id User ID
     * @param string $session_id Session ID
     * @param array $context_data Context data as key-value pairs
     * @return bool Success
     */
    public function update_context($user_id, $session_id, $context_data) {
        if (!is_array($context_data) || empty($context_data)) {
            return false;
        }
        
        $success = true;
        
        foreach ($context_data as $key => $value) {
            $result = $this->set_context($user_id, $session_id, $key, $value);
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Get all context keys for a user
     *
     * @param int $user_id User ID
     * @return array Context keys
     */
    public function get_context_keys($user_id) {
        global $wpdb;
        
        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT context_key FROM {$this->context_table} 
            WHERE user_id = %d",
            $user_id
        ));
        
        return $results;
    }
    
    /**
     * Clean up outdated context entries
     *
     * @param int $days_old Context entries older than this many days
     * @return int Number of entries deleted
     */
    public function cleanup_old_context($days_old = 90) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));
        
        $count = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->context_table} 
            WHERE updated_at < %s",
            $cutoff_date
        ));
        
        return $count;
    }
} 