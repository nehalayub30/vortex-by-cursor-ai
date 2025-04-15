<?php
/**
 * Thorius History Management
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class responsible for managing Thorius interaction history
 */
class Vortex_Thorius_History {
    /**
     * History table name
     */
    private $history_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->history_table = $wpdb->prefix . 'vortex_thorius_interaction_history';
        
        // Initialize table if it doesn't exist
        $this->init_history_table();
    }
    
    /**
     * Initialize history table
     */
    private function init_history_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->history_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            session_id varchar(191) DEFAULT NULL,
            interaction_type varchar(50) NOT NULL,
            interaction_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Log user interaction
     *
     * @param int $user_id User ID
     * @param string $session_id Session ID
     * @param string $query User query
     * @param string $response System response
     * @param array $additional_data Additional data to log
     * @return int|false Interaction ID or false on failure
     */
    public function log_interaction($user_id, $session_id, $query, $response, $additional_data = array()) {
        global $wpdb;
        
        // Prepare interaction data
        $interaction_data = array(
            'query' => $query,
            'response' => $response,
            'timestamp' => current_time('mysql'),
            'additional_data' => $additional_data
        );
        
        // Insert record
        $result = $wpdb->insert(
            $this->history_table,
            array(
                'user_id' => $user_id,
                'session_id' => $session_id,
                'interaction_type' => 'conversation',
                'interaction_data' => json_encode($interaction_data)
            ),
            array('%d', '%s', '%s', '%s')
        );
        
        if (!$result) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Track user interaction of any type
     *
     * @param int $user_id User ID
     * @param string $type Interaction type
     * @param array $data Interaction data
     * @return int|false Interaction ID or false on failure
     */
    public function track_interaction($user_id, $type, $data) {
        global $wpdb;
        
        // Get current session if not explicitly provided
        $session_id = '';
        if (!empty($data['session_id'])) {
            $session_id = $data['session_id'];
            unset($data['session_id']);
        }
        
        // Insert record
        $result = $wpdb->insert(
            $this->history_table,
            array(
                'user_id' => $user_id,
                'session_id' => $session_id,
                'interaction_type' => $type,
                'interaction_data' => json_encode($data)
            ),
            array('%d', '%s', '%s', '%s')
        );
        
        if (!$result) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get user interaction history
     *
     * @param int $user_id User ID
     * @param int $limit Number of records to retrieve
     * @param int $offset Offset
     * @param string $session_id Optional session ID filter
     * @return array Interaction history
     */
    public function get_history($user_id, $limit = 10, $offset = 0, $session_id = '') {
        global $wpdb;
        
        $query = "SELECT * FROM {$this->history_table} WHERE user_id = %d";
        $params = array($user_id);
        
        if (!empty($session_id)) {
            $query .= " AND session_id = %s";
            $params[] = $session_id;
        }
        
        $query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        $results = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
        
        // Decode interaction data
        foreach ($results as &$row) {
            if (isset($row['interaction_data'])) {
                $row['interaction_data'] = json_decode($row['interaction_data'], true);
            }
        }
        
        return $results;
    }
    
    /**
     * Get conversation history for a session
     *
     * @param string $session_id Session ID
     * @param int $limit Number of records to retrieve
     * @return array Conversation history formatted for AI context
     */
    public function get_conversation_history($session_id, $limit = 10) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT interaction_data FROM {$this->history_table} 
            WHERE session_id = %s AND interaction_type = 'conversation' 
            ORDER BY created_at DESC LIMIT %d",
            $session_id,
            $limit
        ), ARRAY_A);
        
        $conversation = array();
        
        foreach (array_reverse($results) as $row) {
            $data = json_decode($row['interaction_data'], true);
            
            if (isset($data['query']) && isset($data['response'])) {
                $conversation[] = array(
                    'user' => $data['query'],
                    'assistant' => $data['response']
                );
            }
        }
        
        return $conversation;
    }
    
    /**
     * Get user's recent queries
     *
     * @param int $user_id User ID
     * @param int $limit Number of queries to retrieve
     * @return array Recent queries
     */
    public function get_recent_queries($user_id, $limit = 5) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT interaction_data FROM {$this->history_table} 
            WHERE user_id = %d AND interaction_type = 'conversation' 
            ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A);
        
        $queries = array();
        
        foreach ($results as $row) {
            $data = json_decode($row['interaction_data'], true);
            
            if (isset($data['query'])) {
                $queries[] = $data['query'];
            }
        }
        
        return $queries;
    }
    
    /**
     * Delete history for a user
     *
     * @param int $user_id User ID
     * @param string $session_id Optional session ID to delete only session history
     * @return int|false Number of rows deleted or false on failure
     */
    public function delete_history($user_id, $session_id = '') {
        global $wpdb;
        
        if (!empty($session_id)) {
            return $wpdb->delete(
                $this->history_table,
                array(
                    'user_id' => $user_id,
                    'session_id' => $session_id
                ),
                array('%d', '%s')
            );
        } else {
            return $wpdb->delete(
                $this->history_table,
                array('user_id' => $user_id),
                array('%d')
            );
        }
    }
    
    /**
     * Get most common queries across all users
     *
     * @param int $limit Number of queries to retrieve
     * @return array Common queries with frequency
     */
    public function get_common_queries($limit = 10) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT interaction_data FROM {$this->history_table} 
            WHERE interaction_type = 'conversation' 
            ORDER BY created_at DESC LIMIT 1000"
        ), ARRAY_A);
        
        $queries = array();
        
        foreach ($results as $row) {
            $data = json_decode($row['interaction_data'], true);
            
            if (isset($data['query'])) {
                $query = strtolower(trim($data['query']));
                if (!isset($queries[$query])) {
                    $queries[$query] = 0;
                }
                $queries[$query]++;
            }
        }
        
        arsort($queries);
        
        return array_slice($queries, 0, $limit, true);
    }
} 