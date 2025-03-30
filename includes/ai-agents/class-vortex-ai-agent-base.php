<?php
/**
 * Base class for all AI agents
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/ai-agents
 */

abstract class Vortex_AI_Agent_Base {
    
    /**
     * Agent name
     */
    protected $agent_name;
    
    /**
     * Learning database
     */
    protected $learning_data;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Set up hooks for learning
        add_action('vortex_ai_agent_learn', array($this, 'process_learning_data'), 10, 2);
        
        // Set up database for learning if not exists
        $this->setup_learning_database();
        
        // Load learning data
        $this->load_learning_data();
    }
    
    /**
     * Setup learning database
     */
    protected function setup_learning_database() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_ai_learning_data';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                agent_name varchar(50) NOT NULL,
                user_id bigint(20) NOT NULL,
                interaction_type varchar(50) NOT NULL,
                interaction_data longtext NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id),
                KEY agent_name (agent_name),
                KEY user_id (user_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    /**
     * Load learning data
     */
    protected function load_learning_data() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_ai_learning_data';
        
        // Get learning data for this agent, limit to last 5000 interactions for performance
        $data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE agent_name = %s ORDER BY created_at DESC LIMIT 5000",
                $this->agent_name
            ),
            ARRAY_A
        );
        
        $this->learning_data = $data;
    }
    
    /**
     * Process learning data
     *
     * @param string $interaction_type Interaction type
     * @param array $data Interaction data
     */
    public function process_learning_data($interaction_type, $data) {
        // Only process if this is the right agent
        if (!isset($data['agent']) || $data['agent'] !== $this->agent_name) {
            return;
        }
        
        // Default user ID is 0 (guest)
        $user_id = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        
        // Store learning data
        $this->store_learning_data($user_id, $interaction_type, $data);
        
        // Add to local cache
        array_unshift($this->learning_data, array(
            'agent_name' => $this->agent_name,
            'user_id' => $user_id,
            'interaction_type' => $interaction_type,
            'interaction_data' => json_encode($data),
            'created_at' => current_time('mysql')
        ));
        
        // Limit local cache to 5000 items
        if (count($this->learning_data) > 5000) {
            array_pop($this->learning_data);
        }
    }
    
    /**
     * Store learning data in database
     *
     * @param int $user_id User ID
     * @param string $interaction_type Interaction type
     * @param array $data Interaction data
     */
    protected function store_learning_data($user_id, $interaction_type, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_ai_learning_data';
        
        $wpdb->insert(
            $table_name,
            array(
                'agent_name' => $this->agent_name,
                'user_id' => $user_id,
                'interaction_type' => $interaction_type,
                'interaction_data' => json_encode($data),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get learning insights for a user
     *
     * @param int $user_id User ID
     * @return array Learning insights
     */
    protected function get_learning_insights($user_id) {
        // Default implementation - child classes should override this
        return array();
    }
    
    /**
     * Get global learning insights
     *
     * @return array Learning insights
     */
    protected function get_global_insights() {
        // Default implementation - child classes should override this
        return array();
    }
} 