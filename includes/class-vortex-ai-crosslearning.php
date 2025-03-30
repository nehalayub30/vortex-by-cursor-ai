<?php
/**
 * AI Cross-Learning System
 * 
 * Enables AI agents to learn from each other's insights and create
 * a more cohesive intelligence ecosystem.
 */
class Vortex_AI_Crosslearning {
    
    /**
     * Initialize cross-learning system
     */
    public function __construct() {
        add_action('vortex_ai_insight_generated', array($this, 'distribute_insight'), 10, 3);
        add_filter('vortex_ai_request_params', array($this, 'enhance_with_crosslearning'), 10, 3);
    }
    
    /**
     * Distribute insights across AI agents
     * 
     * @param string $source_agent Source AI agent
     * @param string $insight_type Type of insight
     * @param array $insight_data Insight data
     */
    public function distribute_insight($source_agent, $insight_type, $insight_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_ai_crosslearning';
        
        // Store the cross-learning data
        $wpdb->insert(
            $table,
            array(
                'source_agent' => $source_agent,
                'insight_type' => $insight_type,
                'insight_data' => json_encode($insight_data),
                'shared' => 0,
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Enhance AI requests with cross-learning data
     * 
     * @param array $params Request parameters
     * @param string $task_type Task type
     * @param string $agent Agent name
     * @return array Enhanced parameters
     */
    public function enhance_with_crosslearning($params, $task_type, $agent) {
        // Get relevant insights from other agents
        $related_insights = $this->get_related_insights($agent, $task_type, $params);
        
        if (!empty($related_insights)) {
            // Add cross-learning context to the request
            $params['cross_learning_context'] = $related_insights;
        }
        
        return $params;
    }
    
    /**
     * Get related insights for an agent request
     * 
     * @param string $agent Agent name
     * @param string $task_type Task type
     * @param array $params Request parameters
     * @return array Related insights
     */
    private function get_related_insights($agent, $task_type, $params) {
        // Implementation would retrieve relevant insights from other agents
        // based on the current request context
        // This is a simplified version for demonstration
        
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_ai_crosslearning';
        
        // Extract keywords from the prompt
        $keywords = $this->extract_keywords($params['prompt'] ?? '');
        
        if (empty($keywords)) {
            return array();
        }
        
        $insights = array();
        
        // Build search condition for each keyword
        $search_conditions = array();
        foreach ($keywords as $keyword) {
            $search_conditions[] = "insight_data LIKE '%" . esc_sql($keyword) . "%'";
        }
        
        $search_sql = implode(' OR ', $search_conditions);
        
        // Get insights from other agents related to these keywords
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table 
                WHERE source_agent != %s 
                AND ($search_sql)
                ORDER BY created_at DESC
                LIMIT 5",
                $agent
            ),
            ARRAY_A
        );
        
        foreach ($results as $row) {
            $insights[] = json_decode($row['insight_data'], true);
            
            // Mark as shared
            $wpdb->update(
                $table,
                array('shared' => 1),
                array('id' => $row['id'])
            );
        }
        
        return $insights;
    }
    
    /**
     * Extract keywords from text
     * 
     * @param string $text Input text
     * @return array Keywords
     */
    private function extract_keywords($text) {
        // In a production environment, this would use NLP
        // Here's a simplified version
        $text = strtolower($text);
        $words = preg_split('/\W+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Remove common stop words
        $stop_words = array('the', 'and', 'a', 'to', 'of', 'in', 'that', 'is', 'for');
        $words = array_diff($words, $stop_words);
        
        // Return only words of sufficient length
        return array_filter($words, function($word) {
            return strlen($word) >= 4;
        });
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table = $wpdb->prefix . 'vortex_ai_crosslearning';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_agent varchar(32) NOT NULL,
            insight_type varchar(32) NOT NULL,
            insight_data longtext NOT NULL,
            shared tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY source_agent (source_agent),
            KEY insight_type (insight_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
} 