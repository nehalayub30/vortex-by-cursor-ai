<?php

class VORTEX_Cloe {
    public function get_analytics() {
        return array(
            'users_analyzed' => $this->get_analyzed_users_count(),
            'recommendations_made' => $this->get_recommendations_count(),
            'prediction_accuracy' => $this->calculate_accuracy()
        );
    }

    private function get_analyzed_users_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_user_analytics';
        return $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table");
    }

    private function get_recommendations_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_recommendations';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }

    private function calculate_accuracy() {
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_recommendations';
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $accurate = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE accuracy = 1");
        return $total > 0 ? round(($accurate / $total) * 100) : 0;
    }

    /**
     * Analyze market based on parameters
     */
    public function analyze_market($market, $timeframe, $question = '') {
        // Add user context
        $user_id = get_current_user_id();
        $context = $this->get_user_context($user_id);
        
        $prompt = "Analyze the $market market over a $timeframe timeframe.";
        
        if (!empty($question)) {
            $prompt .= " Focus on: $question";
        }
        
        // Enhance with user context
        $prompt = $this->enhance_prompt_with_context($prompt, $user_id);
        
        // Rest of the function...
    }
} 