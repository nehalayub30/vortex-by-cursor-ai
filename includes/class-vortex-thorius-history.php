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
} 