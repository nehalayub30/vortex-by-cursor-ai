/**
 * User Session Management
 */
class Vortex_Thorius_Session {
    private $session_table;
    
    public function __construct() {
        global $wpdb;
        $this->session_table = $wpdb->prefix . 'vortex_thorius_sessions';
        $this->init_session_table();
    }
    
    /**
     * Initialize session table
     */
    private function init_session_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->session_table} (
            session_id varchar(191) NOT NULL,
            user_id bigint(20) NOT NULL,
            session_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            PRIMARY KEY  (session_id),
            KEY user_id (user_id),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Start or resume user session
     */
    public function start_session($user_id) {
        $session_id = $this->generate_session_id();
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        global $wpdb;
        $wpdb->insert(
            $this->session_table,
            array(
                'session_id' => $session_id,
                'user_id' => $user_id,
                'session_data' => serialize(array()),
                'expires_at' => $expires
            ),
            array('%s', '%d', '%s', '%s')
        );
        
        return $session_id;
    }

    /**
     * Generate secure session ID
     * 
     * @return string Secure session ID
     */
    private function generate_session_id() {
        try {
            return wp_generate_password(32, false);
        } catch (Exception $e) {
            // Fallback if wp_generate_password fails
            return md5(uniqid(mt_rand(), true));
        }
    }

    /**
     * Get session data with error handling
     */
    public function get_session_data($session_id) {
        global $wpdb;
        
        try {
            $session_data = $wpdb->get_var($wpdb->prepare(
                "SELECT session_data FROM {$this->session_table} WHERE session_id = %s AND expires_at > %s",
                $session_id,
                current_time('mysql')
            ));
            
            if (!$session_data) {
                return array();
            }
            
            return maybe_unserialize($session_data);
        } catch (Exception $e) {
            error_log('Thorius Session Error: ' . $e->getMessage());
            return array();
        }
    }
} 