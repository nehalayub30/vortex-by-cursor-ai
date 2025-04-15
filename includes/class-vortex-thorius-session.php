<?php
/**
 * Thorius Session Management
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class responsible for managing Thorius sessions
 */
class Vortex_Thorius_Session {
    /**
     * Session table name
     */
    private $session_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->session_table = $wpdb->prefix . 'vortex_thorius_sessions';
        
        // Initialize session cleanup
        add_action('wp_loaded', array($this, 'initialize_cleanup'));
    }
    
    /**
     * Initialize session cleanup
     */
    public function initialize_cleanup() {
        if (!wp_next_scheduled('vortex_thorius_session_cleanup')) {
            wp_schedule_event(time(), 'daily', 'vortex_thorius_session_cleanup');
        }
        
        add_action('vortex_thorius_session_cleanup', array($this, 'cleanup_expired_sessions'));
    }
    
    /**
     * Create a new session
     *
     * @param int $user_id User ID
     * @param array $metadata Optional metadata
     * @return string Session ID
     */
    public function create_session($user_id, $metadata = array()) {
        global $wpdb;
        
        // Generate unique session ID
        $session_id = $this->generate_session_id();
        
        // Get current time
        $created_at = current_time('mysql');
        
        // Insert session record
        $wpdb->insert(
            $this->session_table,
            array(
                'session_id' => $session_id,
                'user_id' => $user_id,
                'created_at' => $created_at,
                'last_activity' => $created_at,
                'metadata' => json_encode($metadata)
            ),
            array('%s', '%d', '%s', '%s', '%s')
        );
        
        // Update user meta
        update_user_meta($user_id, '_vortex_current_session_id', $session_id);
        
        return $session_id;
    }
    
    /**
     * Get active session for user
     *
     * @param int $user_id User ID
     * @return string Session ID or empty string if no active session
     */
    public function get_active_session($user_id) {
        $session_id = get_user_meta($user_id, '_vortex_current_session_id', true);
        
        if (empty($session_id)) {
            return '';
        }
        
        // Verify session still exists
        if (!$this->session_exists($session_id)) {
            delete_user_meta($user_id, '_vortex_current_session_id');
            return '';
        }
        
        // Update last activity
        $this->update_last_activity($session_id);
        
        return $session_id;
    }
    
    /**
     * Check if a session exists
     *
     * @param string $session_id Session ID
     * @return bool True if session exists
     */
    public function session_exists($session_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->session_table} WHERE session_id = %s",
            $session_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Update session last activity time
     *
     * @param string $session_id Session ID
     * @return bool Success
     */
    public function update_last_activity($session_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->session_table,
            array('last_activity' => current_time('mysql')),
            array('session_id' => $session_id),
            array('%s'),
            array('%s')
        );
        
        return $result !== false;
    }
    
    /**
     * End a session
     *
     * @param string $session_id Session ID
     * @param int $user_id User ID
     * @return bool Success
     */
    public function end_session($session_id, $user_id) {
        // Update session status
        global $wpdb;
        
        $result = $wpdb->update(
            $this->session_table,
            array('status' => 'ended', 'ended_at' => current_time('mysql')),
            array('session_id' => $session_id),
            array('%s', '%s'),
            array('%s')
        );
        
        // Remove current session from user meta
        $current_session = get_user_meta($user_id, '_vortex_current_session_id', true);
        if ($current_session === $session_id) {
            delete_user_meta($user_id, '_vortex_current_session_id');
        }
        
        return $result !== false;
    }
    
    /**
     * Get session data
     *
     * @param string $session_id Session ID
     * @return array|false Session data or false if not found
     */
    public function get_session_data($session_id) {
        global $wpdb;
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->session_table} WHERE session_id = %s",
            $session_id
        ), ARRAY_A);
        
        if (!$data) {
            return false;
        }
        
        // Decode metadata
        if (isset($data['metadata']) && !empty($data['metadata'])) {
            $data['metadata'] = json_decode($data['metadata'], true);
        } else {
            $data['metadata'] = array();
        }
        
        return $data;
    }
    
    /**
     * Update session metadata
     *
     * @param string $session_id Session ID
     * @param array $metadata Metadata to update
     * @return bool Success
     */
    public function update_session_metadata($session_id, $metadata) {
        global $wpdb;
        
        // Get existing metadata
        $session = $this->get_session_data($session_id);
        if (!$session) {
            return false;
        }
        
        // Merge metadata
        $updated_metadata = array_merge($session['metadata'], $metadata);
        
        // Update session
        $result = $wpdb->update(
            $this->session_table,
            array('metadata' => json_encode($updated_metadata)),
            array('session_id' => $session_id),
            array('%s'),
            array('%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Cleanup expired sessions
     *
     * @return int Number of sessions cleaned up
     */
    public function cleanup_expired_sessions() {
        global $wpdb;
        
        // Get expiration time (30 days ago)
        $expiration_time = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        // Count sessions to clean up
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->session_table} WHERE last_activity < %s",
            $expiration_time
        ));
        
        // Delete expired sessions
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->session_table} WHERE last_activity < %s",
            $expiration_time
        ));
        
        return $count;
    }
    
    /**
     * Generate unique session ID
     *
     * @return string Session ID
     */
    private function generate_session_id() {
        return 'thor_' . md5(uniqid(mt_rand(), true));
    }
} 