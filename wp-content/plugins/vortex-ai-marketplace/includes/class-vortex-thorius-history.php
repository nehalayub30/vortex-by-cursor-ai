/**
 * User Interaction History Management
 */
class Vortex_Thorius_History {
    private $history_table;
    
    public function __construct() {
        global $wpdb;
        $this->history_table = $wpdb->prefix . 'vortex_thorius_interaction_history';
        $this->init_history_table();
    }
    
    /**
     * Initialize history table if it doesn't exist
     */
    private function init_history_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->history_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            interaction_type varchar(50) NOT NULL,
            interaction_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY interaction_type (interaction_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Track user interaction
     */
    public function track_interaction($user_id, $interaction_type, $data) {
        global $wpdb;
        
        $wpdb->insert(
            $this->history_table,
            array(
                'user_id' => $user_id,
                'interaction_type' => $interaction_type,
                'interaction_data' => maybe_serialize($data),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get user interaction history
     */
    public function get_history($user_id, $limit = 10, $offset = 0) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->history_table} 
            WHERE user_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d",
            $user_id, $limit, $offset
        ), ARRAY_A);
        
        return array_map(function($row) {
            $row['interaction_data'] = maybe_unserialize($row['interaction_data']);
            return $row;
        }, $results);
    }
    
    /**
     * Clear old history records
     * 
     * @param int $days Number of days to keep records for
     * @return int|false Number of deleted rows or false on error
     */
    public function clear_old_history($days = 30) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->history_table} WHERE created_at < %s",
            $cutoff_date
        ));
        
        return $result;
    }
} 