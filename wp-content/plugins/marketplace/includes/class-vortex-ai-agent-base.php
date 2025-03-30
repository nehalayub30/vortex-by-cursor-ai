    /**
     * Share insight with other agents
     *
     * @param string $insight_type Type of insight
     * @param array $insight_data Insight data
     * @return bool Success status
     */
    public function share_insight_with_other_agents($insight_type, $insight_data) {
        // Only share if cross learning is enabled
        if (!$this->cross_learning_enabled) {
            return false;
        }
        
        // Add agent source information
        $insight_data['source_agent'] = $this->agent_name;
        $insight_data['timestamp'] = time();
        
        // Trigger the propagation hook
        do_action('vortex_ai_insight_generated', $this->agent_name, $insight_type, $insight_data);
        
        return true;
    }
    
    /**
     * Check for learning resources
     */
    protected function check_for_learning_resources() {
        global $wpdb;
        
        // Get available data for learning
        $available_data = $this->get_training_data();
        
        if (empty($available_data)) {
            error_log("VORTEX {$this->agent_name}: No training data available");
            return 0;
        }
        
        $processed_count = count($available_data);
        
        // Log learning activity
        $wpdb->insert(
            $wpdb->prefix . 'vortex_system_logs',
            array(
                'log_type' => 'learning_activity',
                'message' => sprintf('AI Agent %s processed %d training examples', 
                    $this->agent_name, 
                    $processed_count),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
        
        return $processed_count;
    }
    
    /**
     * Get training data
     * Should be overridden by child classes
     */
    protected function get_training_data() {
        return array();
    }
    
    /**
     * Get agent's responsibilities as string
     */
    public function get_responsibilities_description() {
        if (empty($this->responsibilities)) {
            return 'general AI services';
        }
        
        return implode(', ', $this->responsibilities);
    }
}

/**
 * Initialize the base agent
 */
add_action('init', function() {
    // This is just to ensure the class is loaded
    // Actual instances are created by the specific agent classes
}, 5); 