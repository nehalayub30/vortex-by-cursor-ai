<?php
/**
 * Predictive AI Engine
 * 
 * Makes proactive recommendations based on user behavior
 * and market trends before users explicitly ask.
 */
class Vortex_Predictive_Engine {
    
    /**
     * Initialize the predictive engine
     */
    public function __construct() {
        // Schedule prediction generation
        add_action('vortex_generate_predictions', array($this, 'generate_user_predictions'));
        
        // Register prediction display hooks
        add_action('vortex_ai_dashboard_after_content', array($this, 'display_predictions'));
        add_action('wp_ajax_vortex_get_predictions', array($this, 'ajax_get_predictions'));
        
        // Filter content with relevant predictions
        add_filter('the_content', array($this, 'enhance_content_with_predictions'));
    }
    
    /**
     * Generate predictions for users
     */
    public function generate_user_predictions() {
        global $wpdb;
        
        // Get users who have used AI features
        $users = $wpdb->get_col("
            SELECT DISTINCT user_id FROM {$wpdb->prefix}vortex_ai_usage 
            WHERE user_id > 0
            ORDER BY timestamp DESC
            LIMIT 100
        ");
        
        foreach ($users as $user_id) {
            $this->generate_predictions_for_user($user_id);
        }
    }
    
    /**
     * Generate predictions for a specific user
     * 
     * @param int $user_id User ID
     */
    private function generate_predictions_for_user($user_id) {
        // Get user's recent AI usage
        $recent_usage = $this->get_user_ai_usage($user_id);
        
        if (empty($recent_usage)) {
            return;
        }
        
        $predictions = array();
        
        // Generate artwork predictions
        if ($this->has_usage_type($recent_usage, 'artwork')) {
            $predictions['artwork'] = $this->predict_artwork_interests($user_id, $recent_usage);
        }
        
        // Generate market predictions
        if ($this->has_usage_type($recent_usage, 'market')) {
            $predictions['market'] = $this->predict_market_interests($user_id, $recent_usage);
        }
        
        // Generate strategy predictions
        if ($this->has_usage_type($recent_usage, 'strategy')) {
            $predictions['strategy'] = $this->predict_strategy_needs($user_id, $recent_usage);
        }
        
        // Store predictions
        if (!empty($predictions)) {
            update_user_meta($user_id, 'vortex_ai_predictions', array(
                'predictions' => $predictions,
                'generated' => time()
            ));
        }
    }
    
    /**
     * Check if usage contains a specific type
     * 
     * @param array $usage Usage data
     * @param string $type Usage type
     * @return bool Has usage type
     */
    private function has_usage_type($usage, $type) {
        foreach ($usage as $item) {
            if ($item['type'] === $type) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get user's AI usage history
     * 
     * @param int $user_id User ID
     * @return array Usage history
     */
    private function get_user_ai_usage($user_id) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}vortex_ai_usage 
            WHERE user_id = %d
            ORDER BY timestamp DESC
            LIMIT 50
        ", $user_id), ARRAY_A);
        
        return $results ?: array();
    }
    
    /**
     * Predict artwork interests based on usage patterns
     * 
     * @param int $user_id User ID
     * @param array $usage Usage history
     * @return array Artwork predictions
     */
    private function predict_artwork_interests($user_id, $usage) {
        // Extract artwork-related usage
        $artwork_usage = array_filter($usage, function($item) {
            return $item['type'] === 'artwork';
        });
        
        if (empty($artwork_usage)) {
            return array();
        }
        
        // Extract common themes from previous prompts
        $themes = $this->extract_themes($artwork_usage);
        $styles = $this->extract_styles($artwork_usage);
        
        // Generate recommendations
        $recommendations = array();
        
        // Theme-based recommendations
        if (!empty($themes)) {
            $top_themes = array_slice($themes, 0, 3);
            foreach ($top_themes as $theme) {
                $recommendations[] = array(
                    'type' => 'theme',
                    'value' => $theme,
                    'prompt' => "Create artwork featuring $theme in a unique style",
                    'confidence' => 0.85
                );
            }
        }
        
        // Style-based recommendations
        if (!empty($styles)) {
            $top_styles = array_slice($styles, 0, 2);
            foreach ($top_styles as $style) {
                // Choose a theme that hasn't been paired with this style yet
                $unused_theme = $this->find_unused_pair($themes, $style, $artwork_usage);
                
                if ($unused_theme) {
                    $recommendations[] = array(
                        'type' => 'style',
                        'value' => $style,
                        'prompt' => "Create a $style artwork featuring $unused_theme",
                        'confidence' => 0.78
                    );
                }
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Find a theme that hasn't been paired with a style
     * 
     * @param array $themes Themes
     * @param string $style Style
     * @param array $usage Usage history
     * @return string|null Unused theme
     */
    private function find_unused_pair($themes, $style, $usage) {
        if (empty($themes)) {
            return null;
        }
        
        foreach ($themes as $theme) {
            $paired = false;
            
            foreach ($usage as $item) {
                if (isset($item['params']) && is_array($item['params'])) {
                    $params = $item['params'];
                    
                    if (isset($params['prompt']) && isset($params['style'])) {
                        if (strpos($params['prompt'], $theme) !== false && $params['style'] === $style) {
                            $paired = true;
                            break;
                        }
                    }
                }
            }
            
            if (!$paired) {
                return $theme;
            }
        }
        
        // If all themes have been paired, return the first one
        return $themes[0];
    }
    
    /**
     * Extract common themes from artwork prompts
     * 
     * @param array $usage Artwork usage
     * @return array Themes
     */
    private function extract_themes($usage) {
        // In a real implementation, this would use NLP to extract themes
        // Simplified version for demonstration
        
        $prompt_text = '';
        foreach ($usage as $item) {
            if (isset($item['params']['prompt'])) {
                $prompt_text .= ' ' . $item['params']['prompt'];
            }
        }
        
        // Extract nouns as potential themes
        preg_match_all('/(landscape|portrait|nature|city|mountain|ocean|animal|person|fantasy|abstract|space)/i', $prompt_text, $matches);
        
        if (empty($matches[0])) {
            return array();
        }
        
        // Count frequency
        $themes = array_count_values(array_map('strtolower', $matches[0]));
        arsort($themes);
        
        return array_keys($themes);
    }
    
    /**
     * Extract common styles from artwork usage
     * 
     * @param array $usage Artwork usage
     * @return array Styles
     */
    private function extract_styles($usage) {
        $styles = array();
        
        foreach ($usage as $item) {
            if (isset($item['params']['style'])) {
                $styles[] = $item['params']['style'];
            }
        }
        
        if (empty($styles)) {
            return array();
        }
        
        // Count frequency
        $style_counts = array_count_values($styles);
        arsort($style_counts);
        
        return array_keys($style_counts);
    }
    
    /**
     * Predict market interests based on usage patterns
     * 
     * @param int $user_id User ID
     * @param array $usage Usage history
     * @return array Market predictions
     */
    private function predict_market_interests($user_id, $usage) {
        // Similar implementation as predict_artwork_interests
        // Simplified for this example
        return array(
            array(
                'type' => 'trend',
                'value' => 'cryptocurrency',
                'analysis' => 'Recent market indicators suggest emerging opportunities in renewable energy cryptocurrencies',
                'confidence' => 0.72
            ),
            array(
                'type' => 'alert',
                'value' => 'stock market',
                'analysis' => 'Your portfolio sectors show increased volatility - consider diversification',
                'confidence' => 0.81
            )
        );
    }
    
    /**
     * Predict strategy needs based on usage patterns
     * 
     * @param int $user_id User ID
     * @param array $usage Usage history
     * @return array Strategy predictions
     */
    private function predict_strategy_needs($user_id, $usage) {
        // Similar implementation as predict_artwork_interests
        // Simplified for this example
        return array(
            array(
                'type' => 'opportunity',
                'value' => 'market expansion',
                'strategy' => 'Based on your recent queries, the following markets show high potential for expansion...',
                'confidence' => 0.68
            )
        );
    }
    
    /**
     * Display predictions on dashboard
     */
    public function display_predictions() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $prediction_data = get_user_meta($user_id, 'vortex_ai_predictions', true);
        
        if (empty($prediction_data) || empty($prediction_data['predictions'])) {
            return;
        }
        
        $predictions = $prediction_data['predictions'];
        $generated = isset($prediction_data['generated']) ? $prediction_data['generated'] : 0;
        
        // Only show predictions generated in the last 7 days
        if (time() - $generated > 7 * DAY_IN_SECONDS) {
            return;
        }
        
        // Display predictions
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/predictive-recommendations.php';
    }
    
    /**
     * AJAX handler for fetching predictions
     */
    public function ajax_get_predictions() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User not logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $prediction_data = get_user_meta($user_id, 'vortex_ai_predictions', true);
        
        if (empty($prediction_data) || empty($prediction_data['predictions'])) {
            wp_send_json_error(array('message' => 'No predictions available'));
            return;
        }
        
        $predictions = $prediction_data['predictions'];
        $generated = isset($prediction_data['generated']) ? $prediction_data['generated'] : 0;
        
        wp_send_json_success(array(
            'predictions' => $predictions,
            'generated' => $generated,
            'generated_human' => human_time_diff($generated, time()) . ' ago'
        ));
    }
    
    /**
     * Enhance content with relevant predictions
     * 
     * @param string $content Post content
     * @return string Enhanced content
     */
    public function enhance_content_with_predictions($content) {
        // Only modify content for logged-in users
        if (!is_user_logged_in() || !is_singular()) {
            return $content;
        }
        
        $user_id = get_current_user_id();
        $prediction_data = get_user_meta($user_id, 'vortex_ai_predictions', true);
        
        if (empty($prediction_data) || empty($prediction_data['predictions'])) {
            return $content;
        }
        
        $predictions = $prediction_data['predictions'];
        
        // Check if content matches any predictions
        $post = get_post();
        $matches = $this->find_matching_predictions($post, $predictions);
        
        if (empty($matches)) {
            return $content;
        }
        
        // Add predictions widget to end of content
        $prediction_html = '<div class="vortex-content-predictions">';
        $prediction_html .= '<h4>' . __('AI Insights Related to This Content', 'vortex-ai-marketplace') . '</h4>';
        $prediction_html .= '<ul class="vortex-prediction-list">';
        
        foreach ($matches as $match) {
            $prediction_html .= $this->format_prediction_item($match);
        }
        
        $prediction_html .= '</ul></div>';
        
        return $content . $prediction_html;
    }
    
    /**
     * Find predictions that match the post content
     * 
     * @param WP_Post $post Post object
     * @param array $predictions Predictions
     * @return array Matching predictions
     */
    private function find_matching_predictions($post, $predictions) {
        $matches = array();
        $post_text = strtolower($post->post_title . ' ' . $post->post_content);
        
        // Check artwork predictions
        if (!empty($predictions['artwork'])) {
            foreach ($predictions['artwork'] as $prediction) {
                if (isset($prediction['value']) && strpos($post_text, strtolower($prediction['value'])) !== false) {
                    $matches[] = array_merge($prediction, array('category' => 'artwork'));
                }
            }
        }
        
        // Check market predictions
        if (!empty($predictions['market'])) {
            foreach ($predictions['market'] as $prediction) {
                if (isset($prediction['value']) && strpos($post_text, strtolower($prediction['value'])) !== false) {
                    $matches[] = array_merge($prediction, array('category' => 'market'));
                }
            }
        }
        
        // Check strategy predictions
        if (!empty($predictions['strategy'])) {
            foreach ($predictions['strategy'] as $prediction) {
                if (isset($prediction['value']) && strpos($post_text, strtolower($prediction['value'])) !== false) {
                    $matches[] = array_merge($prediction, array('category' => 'strategy'));
                }
            }
        }
        
        return $matches;
    }
    
    /**
     * Format a prediction item for display
     * 
     * @param array $prediction Prediction data
     * @return string Formatted HTML
     */
    private function format_prediction_item($prediction) {
        $html = '<li class="vortex-prediction-item vortex-prediction-' . esc_attr($prediction['category']) . '">';
        
        switch ($prediction['category']) {
            case 'artwork':
                $html .= '<span class="vortex-prediction-icon dashicons dashicons-art"></span>';
                $html .= '<strong>' . __('Creative Suggestion:', 'vortex-ai-marketplace') . '</strong> ';
                $html .= esc_html($prediction['prompt']);
                $html .= ' <a href="#" class="vortex-use-prediction" data-type="artwork" data-prompt="' . esc_attr($prediction['prompt']) . '">';
                $html .= __('Generate Now', 'vortex-ai-marketplace') . '</a>';
                break;
                
            case 'market':
                $html .= '<span class="vortex-prediction-icon dashicons dashicons-chart-line"></span>';
                $html .= '<strong>' . __('Market Insight:', 'vortex-ai-marketplace') . '</strong> ';
                $html .= esc_html($prediction['analysis']);
                $html .= ' <a href="#" class="vortex-use-prediction" data-type="market" data-value="' . esc_attr($prediction['value']) . '">';
                $html .= __('Analyze Further', 'vortex-ai-marketplace') . '</a>';
                break;
                
            case 'strategy':
                $html .= '<span class="vortex-prediction-icon dashicons dashicons-lightbulb"></span>';
                $html .= '<strong>' . __('Strategic Opportunity:', 'vortex-ai-marketplace') . '</strong> ';
                $html .= esc_html($prediction['strategy']);
                $html .= ' <a href="#" class="vortex-use-prediction" data-type="strategy" data-value="' . esc_attr($prediction['value']) . '">';
                $html .= __('Get Strategy', 'vortex-ai-marketplace') . '</a>';
                break;
        }
        
        $html .= '</li>';
        return $html;
    }
} 