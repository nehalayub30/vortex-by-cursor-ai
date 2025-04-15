<?php
/**
 * VORTEX CLOE AI Curation Agent
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_CLOE Class
 * 
 * CLOE (Curation & Learning Optimization Engine) handles personalized 
 * user experiences, behavioral analysis, and trend correlation.
 */
class VORTEX_CLOE {
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * Learning models and capabilities
     */
    private $learning_models = array();
    
    /**
     * User data tracking categories
     */
    private $tracking_categories = array();
    
    /**
     * Greeting templates
     */
    private $greeting_templates = array();
    
    /**
     * Current trends data
     */
    private $current_trends = array();
    
    /**
     * User behavior metrics
     */
    private $behavior_metrics = array();
    
    /**
     * Marketing intelligence data
     */
    private $marketing_data = array();
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize learning models
        $this->initialize_learning_models();
        
        // Initialize tracking categories
        $this->initialize_tracking_categories();
        
        // Initialize greeting templates
        $this->initialize_greeting_templates();
        
        // Set up hooks
        $this->setup_hooks();
        
        // Initialize behavioral data collection
        $this->initialize_behavior_metrics();
        
        // Initialize marketing data
        $this->initialize_marketing_data();
        
        // Initialize trends tracking
        $this->initialize_trend_tracking();
    }
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // ... existing methods

    /**
     * Continue session tracking
     * 
     * Tracks user activity during the session and updates AI learning models
     * Called on init hook for logged-in users
     *
     * @since    1.0.0
     * @return   void
     */
    public function continue_session_tracking() {
        // Only process for logged-in users
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $session_id = $this->get_current_session_id($user_id);
        
        // If no active session, don't continue
        if (empty($session_id)) {
            return;
        }
        
        // Get session data
        $session_data = get_user_meta($user_id, '_vortex_session_data_' . $session_id, true);
        if (empty($session_data) || !is_array($session_data)) {
            $session_data = array(
                'start_time' => time(),
                'last_activity' => time(),
                'page_views' => array(),
                'interactions' => array(),
                'referrer' => isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field($_SERVER['HTTP_REFERER']) : '',
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : ''
            );
        }
        
        // Update last activity time
        $session_data['last_activity'] = time();
        
        // Track current page view if not an AJAX request
        if (!wp_doing_ajax() && !is_admin()) {
            $current_url = home_url($_SERVER['REQUEST_URI']);
            $page_id = get_the_ID();
            
            // Add page view data
            $session_data['page_views'][] = array(
                'timestamp' => time(),
                'url' => $current_url,
                'page_id' => $page_id,
                'title' => get_the_title($page_id)
            );
            
            // Limit stored page views to prevent data bloat
            if (count($session_data['page_views']) > 100) {
                $session_data['page_views'] = array_slice($session_data['page_views'], -100);
            }
        }
        
        // Update session data
        update_user_meta($user_id, '_vortex_session_data_' . $session_id, $session_data);
        
        // Calculate session duration
        $session_duration = time() - $session_data['start_time'];
        update_user_meta($user_id, '_vortex_current_session_duration', $session_duration);
        
        // Check for session timeout
        $timeout = apply_filters('vortex_session_timeout', 30 * 60); // 30 minutes default
        if ($session_duration > $timeout && (!isset($session_data['last_activity']) || (time() - $session_data['last_activity']) > $timeout)) {
            $this->end_session_tracking();
            $this->start_session_tracking('', $user_id);
        }
        
        // Process data for AI learning
        $this->process_session_data_for_learning($user_id, $session_data);
    }
    
    /**
     * Get current session ID for a user
     *
     * @since    1.0.0
     * @param    int       $user_id    User ID
     * @return   string                Current session ID or empty if no active session
     */
    private function get_current_session_id($user_id) {
        return get_user_meta($user_id, '_vortex_current_session_id', true);
    }
    
    /**
     * Process session data for AI learning
     *
     * @since    1.0.0
     * @param    int       $user_id       User ID
     * @param    array     $session_data  Session data
     * @return   void
     */
    private function process_session_data_for_learning($user_id, $session_data) {
        // Extract behavioral patterns
        $patterns = $this->extract_behavioral_patterns($session_data);
        
        // Use patterns for learning
        if (!empty($patterns)) {
            do_action('vortex_ai_agent_learn', 'CLOE', 'session_patterns', array(
                'user_id' => $user_id,
                'patterns' => $patterns,
                'session_duration' => time() - $session_data['start_time'],
                'page_view_count' => count($session_data['page_views']),
                'interaction_count' => count($session_data['interactions'])
            ));
        }
        
        // Use data to update user preferences
        $this->update_user_preferences($user_id, $session_data);
    }
    
    /**
     * Extract behavioral patterns from session data
     *
     * @since    1.0.0
     * @param    array     $session_data  Session data
     * @return   array                    Behavioral patterns
     */
    private function extract_behavioral_patterns($session_data) {
        $patterns = array(
            'page_categories' => array(),
            'time_spent' => array(),
            'navigation_flow' => array(),
            'interaction_types' => array()
        );
        
        // Process page views
        if (!empty($session_data['page_views'])) {
            $prev_page = null;
            foreach ($session_data['page_views'] as $index => $page_view) {
                // Extract page categories
                if (!empty($page_view['page_id'])) {
                    $categories = get_the_category($page_view['page_id']);
                    if ($categories) {
                        foreach ($categories as $category) {
                            if (!isset($patterns['page_categories'][$category->slug])) {
                                $patterns['page_categories'][$category->slug] = 0;
                            }
                            $patterns['page_categories'][$category->slug]++;
                        }
                    }
                    
                    // Calculate time spent on each page
                    if ($index < count($session_data['page_views']) - 1) {
                        $next_page = $session_data['page_views'][$index + 1];
                        $time_spent = $next_page['timestamp'] - $page_view['timestamp'];
                        if ($time_spent > 0 && $time_spent < 3600) { // Ignore if more than an hour (likely left tab open)
                            if (!isset($patterns['time_spent'][$page_view['page_id']])) {
                                $patterns['time_spent'][$page_view['page_id']] = array('total' => 0, 'count' => 0);
                            }
                            $patterns['time_spent'][$page_view['page_id']]['total'] += $time_spent;
                            $patterns['time_spent'][$page_view['page_id']]['count']++;
                        }
                    }
                    
                    // Track navigation flow
                    if ($prev_page) {
                        $flow_key = $prev_page . '_to_' . $page_view['page_id'];
                        if (!isset($patterns['navigation_flow'][$flow_key])) {
                            $patterns['navigation_flow'][$flow_key] = 0;
                        }
                        $patterns['navigation_flow'][$flow_key]++;
                    }
                    
                    $prev_page = $page_view['page_id'];
                }
            }
        }
        
        // Process interactions
        if (!empty($session_data['interactions'])) {
            foreach ($session_data['interactions'] as $interaction) {
                if (!empty($interaction['type'])) {
                    if (!isset($patterns['interaction_types'][$interaction['type']])) {
                        $patterns['interaction_types'][$interaction['type']] = 0;
                    }
                    $patterns['interaction_types'][$interaction['type']]++;
                }
            }
        }
        
        return $patterns;
    }
    
    /**
     * Update user preferences based on session data
     *
     * @since    1.0.0
     * @param    int       $user_id       User ID
     * @param    array     $session_data  Session data
     * @return   void
     */
    private function update_user_preferences($user_id, $session_data) {
        // Get existing preferences
        $preferences = get_user_meta($user_id, '_vortex_user_preferences', true);
        if (empty($preferences) || !is_array($preferences)) {
            $preferences = array(
                'categories' => array(),
                'artists' => array(),
                'styles' => array(),
                'price_range' => array('min' => 0, 'max' => 0, 'count' => 0),
                'interaction_history' => array(),
                'last_updated' => 0
            );
        }
        
        // Update based on page views
        if (!empty($session_data['page_views'])) {
            foreach ($session_data['page_views'] as $page_view) {
                if (!empty($page_view['page_id'])) {
                    // Check for artwork pages
                    $post_type = get_post_type($page_view['page_id']);
                    if ($post_type === 'vortex_artwork') {
                        // Update category preferences
                        $terms = get_the_terms($page_view['page_id'], 'artwork_category');
                        if ($terms) {
                            foreach ($terms as $term) {
                                if (!isset($preferences['categories'][$term->slug])) {
                                    $preferences['categories'][$term->slug] = 0;
                                }
                                $preferences['categories'][$term->slug]++;
                            }
                        }
                        
                        // Update style preferences
                        $styles = get_the_terms($page_view['page_id'], 'artwork_style');
                        if ($styles) {
                            foreach ($styles as $style) {
                                if (!isset($preferences['styles'][$style->slug])) {
                                    $preferences['styles'][$style->slug] = 0;
                                }
                                $preferences['styles'][$style->slug]++;
                            }
                        }
                        
                        // Update artist preferences
                        $artist_id = get_post_meta($page_view['page_id'], '_vortex_artist_id', true);
                        if ($artist_id) {
                            if (!isset($preferences['artists'][$artist_id])) {
                                $preferences['artists'][$artist_id] = 0;
                            }
                            $preferences['artists'][$artist_id]++;
                        }
                        
                        // Update price range awareness
                        $price = floatval(get_post_meta($page_view['page_id'], '_vortex_price', true));
                        if ($price > 0) {
                            if ($preferences['price_range']['count'] === 0) {
                                $preferences['price_range']['min'] = $price;
                                $preferences['price_range']['max'] = $price;
                            } else {
                                $preferences['price_range']['min'] = min($preferences['price_range']['min'], $price);
                                $preferences['price_range']['max'] = max($preferences['price_range']['max'], $price);
                            }
                            $preferences['price_range']['count']++;
                        }
                    }
                }
            }
        }
        
        // Update interaction history
        if (!empty($session_data['interactions'])) {
            // Limit to last 100 interactions
            $preferences['interaction_history'] = array_merge(
                $preferences['interaction_history'],
                array_slice($session_data['interactions'], -100)
            );
            
            if (count($preferences['interaction_history']) > 100) {
                $preferences['interaction_history'] = array_slice($preferences['interaction_history'], -100);
            }
        }
        
        // Update timestamp
        $preferences['last_updated'] = time();
        
        // Save updated preferences
        update_user_meta($user_id, '_vortex_user_preferences', $preferences);
    }
} 