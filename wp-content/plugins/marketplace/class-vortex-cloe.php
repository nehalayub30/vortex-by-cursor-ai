/**
 * Correlates internal and external trends
 * 
 * @return array Correlated trend data
 */
public function correlate_trends() {
    try {
        // Get internal marketplace trends
        $internal_styles = $this->get_popular_styles('month');
        $internal_themes = $this->get_emerging_themes('month');
        
        // Get external trends
        $external_trends = $this->fetch_external_art_trends();
        
        // Build correlation data
        $correlations = array();
        
        if ($internal_styles['status'] === 'success' && $external_trends['status'] === 'success') {
            $internal_style_names = array_column($internal_styles['data'], 'style_name');
            
            // Extract external style names from various sources
            $external_style_names = array();
            foreach ($external_trends['data'] as $source => $data) {
                if (isset($data['styles'])) {
                    foreach ($data['styles'] as $style) {
                        $external_style_names[] = $style['name'];
                    }
                }
            }
            
            // Find matching styles
            $matching_styles = array_intersect($internal_style_names, $external_style_names);
            
            // Find trending styles in external sources that aren't popular internally
            $opportunity_styles = array_diff($external_style_names, $internal_style_names);
            
            $correlations['styles'] = array(
                'matching' => $matching_styles,
                'opportunities' => $opportunity_styles
            );
        }
        
        // Similar analysis for themes, mediums, price points, etc.
        // ...
        
        return array(
            'status' => 'success',
            'data' => $correlations
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Enables deep learning capabilities for CLOE
 * 
 * @param bool $status Enable or disable deep learning
 * @return bool Success status
 */
public function enable_deep_learning($status = true) {
    try {
        $this->deep_learning_enabled = (bool) $status;
        update_option('vortex_cloe_deep_learning', $status);
        
        if ($status) {
            // Initialize deep learning components
            $this->initialize_deep_learning_model();
            
            // Set up scheduled tasks for model training
            if (!wp_next_scheduled('vortex_cloe_model_training')) {
                wp_schedule_event(time(), 'daily', 'vortex_cloe_model_training');
            }
            
            add_action('vortex_cloe_model_training', array($this, 'train_deep_learning_model'));
        } else {
            // Clean up if disabling
            wp_clear_scheduled_hook('vortex_cloe_model_training');
        }
        
        return true;
    } catch (Exception $e) {
        $this->log_error('Failed to enable deep learning: ' . $e->getMessage());
        return false;
    }
}

/**
 * Trains the deep learning model
 */
public function train_deep_learning_model() {
    try {
        // Get training data
        $training_data = $this->collect_training_data();
        
        // Train model
        $training_result = $this->perform_model_training($training_data);
        
        // Log training metrics
        $this->log_training_metrics($training_result);
        
        // Save updated model weights
        $this->save_model_weights();
        
        return true;
    } catch (Exception $e) {
        $this->log_error('Model training failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Collects training data for the model
 * 
 * @return array Training data
 */
private function collect_training_data() {
    global $wpdb;
    
    // Get recent analytics outputs with high confidence scores
    $query = "SELECT * FROM {$wpdb->prefix}vortex_cloe_analytics_outputs 
              WHERE confidence_score >= 0.8
              ORDER BY created_at DESC
              LIMIT 1000";
              
    $analytics_data = $wpdb->get_results($query);
    
    // Process raw data into training examples
    $training_examples = array();
    foreach ($analytics_data as $data) {
        // Transform data into input-output pairs
        $input_data = json_decode($data->input_parameters, true);
        $output_data = json_decode($data->output_data, true);
        
        if ($input_data && $output_data) {
            $training_examples[] = array(
                'input' => $input_data,
                'output' => $output_data,
                'weight' => $data->confidence_score // Use confidence as weight
            );
        }
    }
    
    return $training_examples;
}

/**
 * Performs model training on collected data
 * 
 * @param array $training_data Training examples
 * @return array Training results and metrics
 */
private function perform_model_training($training_data) {
    // This would typically call an external ML service or library
    // Simulated implementation for framework
    
    $training_metrics = array(
        'examples_processed' => count($training_data),
        'iterations' => 10,
        'initial_loss' => 0.38,
        'final_loss' => 0.29,
        'accuracy_improvement' => 0.09,
    );
    
    return $training_metrics;
}

/**
 * Logs training metrics
 * 
 * @param array $metrics Training metrics
 */
private function log_training_metrics($metrics) {
    global $wpdb;
    
    $wpdb->insert(
        $wpdb->prefix . 'vortex_ai_training_logs',
        array(
            'model_name' => 'cloe',
            'model_version' => $this->model_version,
            'examples_processed' => $metrics['examples_processed'],
            'iterations' => $metrics['iterations'],
            'initial_loss' => $metrics['initial_loss'],
            'final_loss' => $metrics['final_loss'],
            'accuracy_improvement' => $metrics['accuracy_improvement'],
            'training_time' => time()
        )
    );
}

/**
 * Saves updated model weights
 */
private function save_model_weights() {
    // Implementation to save model weights
    $weights_dir = WP_CONTENT_DIR . '/uploads/vortex/models';
    
    // Ensure directory exists
    if (!file_exists($weights_dir)) {
        wp_mkdir_p($weights_dir);
    }
    
    $weights_path = $weights_dir . '/cloe_weights.dat';
    
    // Save weights logic
    // This would normally interact with ML framework
}

/**
 * Sets the learning rate for the AI model
 * 
 * @param float $rate The learning rate value (default: 0.001)
 * @return bool Success status
 */
public function set_learning_rate($rate = 0.001) {
    try {
        // Validate input
        $rate = floatval($rate);
        if ($rate <= 0 || $rate > 1) {
            throw new Exception('Learning rate must be between 0 and 1');
        }
        
        $this->learning_rate = $rate;
        update_option('vortex_cloe_learning_rate', $rate);
        
        // Apply learning rate to model configuration
        $this->model_config['learning_rate'] = $rate;
        
        return true;
    } catch (Exception $e) {
        $this->log_error('Failed to set learning rate: ' . $e->getMessage());
        return false;
    }
}

/**
 * Enables continuous learning capability
 * 
 * @param bool $status Enable or disable continuous learning
 * @return bool Success status
 */
public function enable_continuous_learning($status = true) {
    try {
        $this->continuous_learning = (bool) $status;
        update_option('vortex_cloe_continuous_learning', $status);
        
        if ($status) {
            // Initialize continuous learning components
            $this->setup_feedback_loop();
            $this->initialize_learning_pipeline();
        } else {
            // Clean up if disabling
            remove_action('vortex_user_feedback', array($this, 'process_feedback'));
            remove_action('vortex_cloe_analysis', array($this, 'evaluate_analysis'));
            wp_clear_scheduled_hook('vortex_cloe_learning_update');
        }
        
        return true;
    } catch (Exception $e) {
        $this->log_error('Failed to set continuous learning: ' . $e->getMessage());
        return false;
    }
}

/**
 * Sets the context window size for the AI model
 * 
 * @param int $window_size Size of the context window
 * @return bool Success status
 */
public function set_context_window($window_size = 1000) {
    try {
        // Validate input
        $window_size = intval($window_size);
        if ($window_size < 100 || $window_size > 10000) {
            throw new Exception('Context window size must be between 100 and 10000');
        }
        
        $this->context_window = $window_size;
        update_option('vortex_cloe_context_window', $window_size);
        
        // Apply context window to model configuration
        $this->model_config['context_window'] = $window_size;
        
        return true;
    } catch (Exception $e) {
        $this->log_error('Failed to set context window: ' . $e->getMessage());
        return false;
    }
}

/**
 * Enables cross-learning between different AI agents
 * 
 * @param bool $status Enable or disable cross-learning
 * @return bool Success status
 */
public function enable_cross_learning($status = true) {
    try {
        $this->cross_learning = (bool) $status;
        update_option('vortex_cloe_cross_learning', $status);
        
        if ($status) {
            // Register with knowledge hub for cross-agent learning
            $this->register_with_knowledge_hub();
            
            // Initialize cross-learning hooks
            add_action('vortex_ai_insight_generated', array($this, 'process_external_insight'), 10, 3);
        } else {
            remove_action('vortex_ai_insight_generated', array($this, 'process_external_insight'));
        }
        
        return true;
    } catch (Exception $e) {
        $this->log_error('Failed to set cross learning: ' . $e->getMessage());
        return false;
    }
}

/**
 * Private method to initialize deep learning model
 */
private function initialize_deep_learning_model() {
    // Implementation details for model initialization
    // This would connect to a machine learning backend or service
    
    $this->model_config = array(
        'learning_rate' => $this->learning_rate,
        'context_window' => $this->context_window,
        'layers' => 4,
        'hidden_units' => 256,
        'activation' => 'relu'
    );
    
    // Register model with AI system
    do_action('vortex_register_ai_model', 'cloe', $this->model_config);
}

/**
 * Private method to set up the feedback loop for continuous learning
 */
private function setup_feedback_loop() {
    // Implementation of feedback collection and processing
    add_action('vortex_user_feedback', array($this, 'process_feedback'), 10, 3);
    add_action('vortex_cloe_analysis', array($this, 'evaluate_analysis'), 10, 2);
}

/**
 * Private method to initialize the learning pipeline
 */
private function initialize_learning_pipeline() {
    // Set up scheduled tasks for model updates
    if (!wp_next_scheduled('vortex_cloe_learning_update')) {
        wp_schedule_event(time(), 'daily', 'vortex_cloe_learning_update');
    }
    
    add_action('vortex_cloe_learning_update', array($this, 'update_model_weights'));
}

/**
 * Registers this agent with the central knowledge hub
 */
private function register_with_knowledge_hub() {
    // Register capabilities and knowledge domains with central hub
    $capabilities = array(
        'market_analysis',
        'trend_detection',
        'user_behavior_analysis',
        'content_recommendations',
        'pricing_optimization'
    );
    
    $knowledge_domains = array(
        'art_market_trends',
        'user_preferences',
        'social_sharing_patterns',
        'purchase_behaviors',
        'content_engagement_metrics'
    );
    
    do_action('vortex_register_ai_agent', 'cloe', $capabilities, $knowledge_domains);
}

/**
 * Helper method to get time constraint for database queries
 * 
 * @param string $period Time period (day, week, month, year)
 * @param bool $previous Whether to get the previous period
 * @return string SQL-formatted datetime
 */
private function get_time_constraint($period = 'month', $previous = false) {
    $now = time();
    
    switch($period) {
        case 'day':
            $interval = 1 * DAY_IN_SECONDS;
            break;
        case 'week':
            $interval = 7 * DAY_IN_SECONDS;
            break;
        case 'month':
            $interval = 30 * DAY_IN_SECONDS;
            break;
        case 'quarter':
            $interval = 90 * DAY_IN_SECONDS;
            break;
        case 'year':
            $interval = 365 * DAY_IN_SECONDS;
            break;
        default:
            $interval = 30 * DAY_IN_SECONDS;
    }
    
    if ($previous) {
        return date('Y-m-d H:i:s', $now - (2 * $interval));
    } else {
        return date('Y-m-d H:i:s', $now - $interval);
    }
}

/**
 * Helper method to calculate overall average from results
 * 
 * @param array $results Array of database results
 * @param string $field Field name to average
 * @return float Overall average
 */
private function calculate_overall_average($results, $field) {
    if (empty($results)) {
        return 0;
    }
    
    $sum = 0;
    $count = 0;
    
    foreach ($results as $result) {
        if (isset($result->$field)) {
            $sum += $result->$field;
            $count++;
        }
    }
    
    return $count > 0 ? $sum / $count : 0;
}

/**
 * Gets emerging themes in artwork
 * 
 * @param string $period Time period for comparison
 * @return array Emerging themes data
 */
public function get_emerging_themes($period = 'month') {
    try {
        global $wpdb;
        
        $current_period = $this->get_time_constraint($period);
        $previous_period = $this->get_time_constraint($period, true);
        
        $query = $wpdb->prepare(
            "SELECT 
                t.theme_id,
                t.theme_name,
                COUNT(DISTINCT CASE WHEN tr.transaction_time >= %s THEN tr.transaction_id ELSE NULL END) as current_purchases,
                COUNT(DISTINCT CASE WHEN tr.transaction_time >= %s AND tr.transaction_time < %s THEN tr.transaction_id ELSE NULL END) as previous_purchases
            FROM {$wpdb->prefix}vortex_artwork_themes t
            JOIN {$wpdb->prefix}vortex_artwork_theme_mapping tm ON t.theme_id = tm.theme_id
            JOIN {$wpdb->prefix}vortex_artworks a ON tm.artwork_id = a.artwork_id
            LEFT JOIN {$wpdb->prefix}vortex_transactions tr ON a.artwork_id = tr.artwork_id AND tr.status = 'completed'
            GROUP BY t.theme_id, t.theme_name
            HAVING COUNT(DISTINCT CASE WHEN tr.transaction_time >= %s THEN tr.transaction_id ELSE NULL END) > 
                   COUNT(DISTINCT CASE WHEN tr.transaction_time >= %s AND tr.transaction_time < %s THEN tr.transaction_id ELSE NULL END)
            ORDER BY (COUNT(DISTINCT CASE WHEN tr.transaction_time >= %s THEN tr.transaction_id ELSE NULL END) - 
                     COUNT(DISTINCT CASE WHEN tr.transaction_time >= %s AND tr.transaction_time < %s THEN tr.transaction_id ELSE NULL END)) DESC",
            $current_period,
            $previous_period,
            $current_period,
            $current_period,
            $previous_period,
            $current_period,
            $current_period,
            $previous_period,
            $current_period
        );
        
        $results = $wpdb->get_results($query);
        
        // Calculate growth percentage
        foreach ($results as &$theme) {
            $theme->growth_percentage = $theme->previous_purchases > 0 
                ? (($theme->current_purchases - $theme->previous_purchases) / $theme->previous_purchases) * 100 
                : ($theme->current_purchases > 0 ? 100 : 0);
        }
        
        return array(
            'status' => 'success',
            'data' => $results
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Identifies content gaps in the marketplace
 * 
 * @return array Content gap data
 */
public function identify_content_gaps() {
    try {
        global $wpdb;
        
        // Find styles/themes with high search volume but low inventory
        $query = "SELECT 
                    'style' as type,
                    s.style_id as id,
                    s.style_name as name,
                    COUNT(DISTINCT sr.search_id) as search_count,
                    COUNT(DISTINCT a.artwork_id) as artwork_count,
                    (COUNT(DISTINCT sr.search_id) / GREATEST(COUNT(DISTINCT a.artwork_id), 1)) as demand_supply_ratio
                  FROM {$wpdb->prefix}vortex_art_styles s
                  LEFT JOIN {$wpdb->prefix}vortex_search_results sr ON s.style_id = sr.style_id
                  LEFT JOIN {$wpdb->prefix}vortex_artworks a ON s.style_id = a.style_id
                  GROUP BY s.style_id
                  
                  UNION
                  
                  SELECT 
                    'theme' as type,
                    t.theme_id as id,
                    t.theme_name as name,
                    COUNT(DISTINCT sr.search_id) as search_count,
                    COUNT(DISTINCT tm.artwork_id) as artwork_count,
                    (COUNT(DISTINCT sr.search_id) / GREATEST(COUNT(DISTINCT tm.artwork_id), 1)) as demand_supply_ratio
                  FROM {$wpdb->prefix}vortex_artwork_themes t
                  LEFT JOIN {$wpdb->prefix}vortex_search_results sr ON t.theme_id = sr.theme_id
                  LEFT JOIN {$wpdb->prefix}vortex_artwork_theme_mapping tm ON t.theme_id = tm.theme_id
                  GROUP BY t.theme_id
                  
                  ORDER BY demand_supply_ratio DESC
                  LIMIT 20";
        
        $content_gaps = $wpdb->get_results($query);
        
        // Find price range gaps
        $price_gap_query = "SELECT 
                              c.category_id,
                              c.category_name,
                              MIN(a.price) as min_price,
                              MAX(a.price) as max_price,
                              AVG(a.price) as avg_price,
                              COUNT(*) as artwork_count,
                              MAX(a.price) - MIN(a.price) as price_range,
                              STDDEV(a.price) as price_stddev
                            FROM {$wpdb->prefix}vortex_categories c
                            JOIN {$wpdb->prefix}vortex_artworks a ON c.category_id = a.category_id
                            GROUP BY c.category_id
                            HAVING artwork_count > 5 AND price_stddev > 0
                            ORDER BY price_stddev DESC";
        
        $price_gaps = $wpdb->get_results($price_gap_query);
        
        return array(
            'status' => 'success',
            'content_gaps' => $content_gaps,
            'price_gaps' => $price_gaps
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Gets top referral sources to the marketplace
 * 
 * @param string $period Time period for analysis
 * @return array Referral source data
 */
public function get_top_referral_sources($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        $query = $wpdb->prepare(
            "SELECT 
                referrer_domain,
                COUNT(*) as visit_count,
                COUNT(DISTINCT user_id) as unique_visitors,
                SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) as conversions,
                SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100 as conversion_rate
            FROM {$wpdb->prefix}vortex_referrers
            WHERE visit_time >= %s
            GROUP BY referrer_domain
            ORDER BY conversions DESC, visit_count DESC",
            $time_constraint
        );
        
        $results = $wpdb->get_results($query);
        
        return array(
            'status' => 'success',
            'data' => $results
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Gets campaign performance data
 * 
 * @param string $period Time period for analysis
 * @return array Campaign performance data
 */
public function get_campaign_performance($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        $query = $wpdb->prepare(
            "SELECT 
                c.campaign_id,
                c.campaign_name,
                c.campaign_type,
                c.start_date,
                c.end_date,
                COUNT(r.visit_id) as total_clicks,
                COUNT(DISTINCT r.user_id) as unique_visitors,
                SUM(CASE WHEN r.converted = 1 THEN 1 ELSE 0 END) as conversions,
                SUM(CASE WHEN r.converted = 1 THEN 1 ELSE 0 END) / COUNT(r.visit_id) * 100 as conversion_rate,
                SUM(t.amount) as total_revenue,
                SUM(t.amount) / SUM(CASE WHEN r.converted = 1 THEN 1 ELSE 0 END) as revenue_per_conversion,
                c.campaign_cost,
                CASE 
                    WHEN c.campaign_cost > 0 
                    THEN (SUM(t.amount) - c.campaign_cost) / c.campaign_cost * 100 
                    ELSE 0 
                END as roi
            FROM {$wpdb->prefix}vortex_campaigns c
            LEFT JOIN {$wpdb->prefix}vortex_referrers r ON c.campaign_id = r.campaign_id AND r.visit_time >= %s
            LEFT JOIN {$wpdb->prefix}vortex_transactions t ON r.user_id = t.user_id AND t.status = 'completed' AND t.transaction_time >= r.visit_time
            WHERE (c.end_date IS NULL OR c.end_date >= %s)
            GROUP BY c.campaign_id
            ORDER BY roi DESC",
            $time_constraint,
            $time_constraint
        );
        
        $results = $wpdb->get_results($query);
        
        return array(
            'status' => 'success',
            'data' => $results
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Gets user retention rates
 * 
 * @param string $period Time period for analysis
 * @return array Retention rate data
 */
public function get_retention_rates($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        // Get new users in the period
        $new_users_query = $wpdb->prepare(
            "SELECT 
                user_id,
                registration_date
             FROM {$wpdb->prefix}vortex_users
             WHERE registration_date >= %s",
            $time_constraint
        );
        
        $new_users = $wpdb->get_results($new_users_query);
        $total_new_users = count($new_users);
        
        if ($total_new_users === 0) {
            return array(
                'status' => 'success',
                'data' => array(
                    'total_new_users' => 0,
                    'retention_rates' => array()
                )
            );
        }
        
        // Calculate retention at various intervals
        $intervals = array(1, 7, 14, 30, 60, 90);
        $retention_rates = array();
        
        foreach ($intervals as $days) {
            $retained_users = 0;
            
            foreach ($new_users as $user) {
                $retention_date = date('Y-m-d H:i:s', strtotime($user->registration_date . ' + ' . $days . ' days'));
                
                if ($retention_date > date('Y-m-d H:i:s')) {
                    // Skip future dates
                    continue;
                }
                
                $activity_check_query = $wpdb->prepare(
                    "SELECT COUNT(*) 
                     FROM {$wpdb->prefix}vortex_user_activity
                     WHERE user_id = %d
                     AND activity_time >= %s",
                    $user->user_id,
                    $retention_date
                );
                
                $has_activity = $wpdb->get_var($activity_check_query);
                
                if ($has_activity > 0) {
                    $retained_users++;
                }
            }
            
            $eligible_users = $total_new_users;
            $future_count = 0;
            
            // Don't count users that haven't reached this retention period
            foreach ($new_users as $user) {
                $retention_date = date('Y-m-d H:i:s', strtotime($user->registration_date . ' + ' . $days . ' days'));
                if ($retention_date > date('Y-m-d H:i:s')) {
                    $future_count++;
                }
            }
            
            $eligible_users -= $future_count;
            
            $retention_rates[$days] = array(
                'days' => $days,
                'retained_users' => $retained_users,
                'eligible_users' => $eligible_users,
                'retention_rate' => $eligible_users > 0 ? ($retained_users / $eligible_users) * 100 : 0
            );
        }
        
        return array(
            'status' => 'success',
            'data' => array(
                'total_new_users' => $total_new_users,
                'retention_rates' => $retention_rates
            )
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Gets social sharing analytics
 * 
 * @param string $period Time period for analysis
 * @return array Social sharing data
 */
public function get_social_sharing_analytics($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        // Get sharing statistics by platform
        $platform_query = $wpdb->prepare(
            "SELECT 
                platform,
                COUNT(*) as share_count,
                COUNT(DISTINCT artwork_id) as artwork_count,
                COUNT(DISTINCT user_id) as user_count,
                SUM(click_count) as click_count,
                SUM(engagement_count) as engagement_count
            FROM {$wpdb->prefix}vortex_social_shares
            WHERE share_time >= %s
            GROUP BY platform
            ORDER BY share_count DESC",
            $time_constraint
        );
        
        $platform_stats = $wpdb->get_results($platform_query);
        
        // Get most shared artworks
        $artwork_query = $wpdb->prepare(
            "SELECT 
                a.artwork_id,
                a.title,
                COUNT(s.share_id) as share_count,
                COUNT(DISTINCT s.platform) as platform_count,
                SUM(s.click_count) as click_count,
                SUM(s.engagement_count) as engagement_count
            FROM {$wpdb->prefix}vortex_social_shares s
            JOIN {$wpdb->prefix}vortex_artworks a ON s.artwork_id = a.artwork_id
            WHERE s.share_time >= %s
            GROUP BY a.artwork_id
            ORDER BY share_count DESC
            LIMIT 10",
            $time_constraint
        );
        
        $artwork_stats = $wpdb->get_results($artwork_query);
        
        return array(
            'status' => 'success',
            'platform_stats' => $platform_stats,
            'top_shared_artworks' => $artwork_stats
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Analyzes viral content in the marketplace
 * 
 * @param string $period Time period for analysis
 * @return array Viral content analysis
 */
public function analyze_viral_content($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        // Find artworks with viral potential (high sharing/engagement ratios)
        $query = $wpdb->prepare(
            "SELECT 
                a.artwork_id,
                a.title,
                a.artist_id,
                (SELECT user_name FROM {$wpdb->prefix}vortex_users WHERE user_id = a.artist_id) as artist_name,
                COUNT(DISTINCT s.share_id) as share_count,
                SUM(s.click_count) as click_count,
                SUM(s.engagement_count) as engagement_count,
                COUNT(DISTINCT v.view_id) as view_count,
                (COUNT(DISTINCT s.share_id) / GREATEST(COUNT(DISTINCT v.view_id), 1)) * 100 as share_rate,
                (SUM(s.engagement_count) / GREATEST(SUM(s.click_count), 1)) * 100 as engagement_rate
            FROM {$wpdb->prefix}vortex_artworks a
            LEFT JOIN {$wpdb->prefix}vortex_social_shares s ON a.artwork_id = s.artwork_id AND s.share_time >= %s
            LEFT JOIN {$wpdb->prefix}vortex_artwork_views v ON a.artwork_id = v.artwork_id AND v.view_time >= %s
            GROUP BY a.artwork_id
            HAVING share_count > 0 AND view_count > 10
            ORDER BY (share_rate * engagement_rate) DESC
            LIMIT 20",
            $time_constraint,
            $time_constraint
        );
        
        $viral_content = $wpdb->get_results($query);
        
        // Calculate virality metrics
        foreach ($viral_content as &$content) {
            $content->virality_score = ($content->share_rate * $content->engagement_rate) / 100;
            $content->viral_coefficient = $content->share_count > 0 ? $content->click_count / $content->share_count : 0;
        }
        
        return array(
            'status' => 'success',
            'data' => $viral_content
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Analyzes hashtag effectiveness
 * 
 * @param string $period Time period for analysis
 * @return array Hashtag effectiveness data
 */
public function analyze_hashtag_effectiveness($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        $query = $wpdb->prepare(
            "SELECT 
                h.hashtag,
                COUNT(DISTINCT s.share_id) as usage_count,
                SUM(s.click_count) as click_count,
                SUM(s.engagement_count) as engagement_count,
                SUM(s.click_count) / COUNT(DISTINCT s.share_id) as clicks_per_share,
                SUM(s.engagement_count) / COUNT(DISTINCT s.share_id) as engagement_per_share
            FROM {$wpdb->prefix}vortex_social_hashtags h
            JOIN {$wpdb->prefix}vortex_hashtag_share_mapping m ON h.hashtag_id = m.hashtag_id
            JOIN {$wpdb->prefix}vortex_social_shares s ON m.share_id = s.share_id
            WHERE s.share_time >= %s
            GROUP BY h.hashtag
            HAVING usage_count > 5
            ORDER BY engagement_per_share DESC
            LIMIT 20",
            $time_constraint
        );
        
        $results = $wpdb->get_results($query);
        
        return array(
            'status' => 'success',
            'data' => $results
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Analyzes platform trends
 * 
 * @param string $period Time period for analysis
 * @return array Platform trend data
 */
public function analyze_platform_trends($period = 'month') {
    try {
        global $wpdb;
        
        $current_period = $this->get_time_constraint($period);
        $previous_period = $this->get_time_constraint($period, true);
        
        $query = $wpdb->prepare(
            "SELECT 
                platform,
                COUNT(DISTINCT CASE WHEN share_time >= %s THEN share_id ELSE NULL END) as current_shares,
                COUNT(DISTINCT CASE WHEN share_time >= %s AND share_time < %s THEN share_id ELSE NULL END) as previous_shares,
                SUM(CASE WHEN share_time >= %s THEN click_count ELSE 0 END) as current_clicks,
                SUM(CASE WHEN share_time >= %s AND share_time < %s THEN click_count ELSE 0 END) as previous_clicks,
                SUM(CASE WHEN share_time >= %s THEN engagement_count ELSE 0 END) as current_engagement,
                SUM(CASE WHEN share_time >= %s AND share_time < %s THEN engagement_count ELSE 0 END) as previous_engagement
            FROM {$wpdb->prefix}vortex_social_shares
            WHERE share_time >= %s
            GROUP BY platform",
            $current_period,
            $previous_period,
            $current_period,
            $current_period,
            $previous_period,
            $current_period,
            $current_period,
            $previous_period,
            $current_period,
            $previous_period
        );
        
        $results = $wpdb->get_results($query);
        
        // Calculate growth percentages
        foreach ($results as &$platform) {
            $platform->share_growth = $platform->previous_shares > 0 
                ? (($platform->current_shares - $platform->previous_shares) / $platform->previous_shares) * 100 
                : ($platform->current_shares > 0 ? 100 : 0);
                
            $platform->click_growth = $platform->previous_clicks > 0 
                ? (($platform->current_clicks - $platform->previous_clicks) / $platform->previous_clicks) * 100 
                : ($platform->current_clicks > 0 ? 100 : 0);
                
            $platform->engagement_growth = $platform->previous_engagement > 0 
                ? (($platform->current_engagement - $platform->previous_engagement) / $platform->previous_engagement) * 100 
                : ($platform->current_engagement > 0 ? 100 : 0);
        }
        
        return array(
            'status' => 'success',
            'data' => $results
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Fetches external art trends from APIs
 * 
 * @return array External art trend data
 */
public function fetch_external_art_trends() {
    try {
        $cached_trends = get_transient('vortex_external_art_trends');
        
        if ($cached_trends !== false) {
            return array(
                'status' => 'success',
                'source' => 'cache',
                'data' => $cached_trends
            );
        }
        
        // Initialize results array
        $trends = array();
        
        // Fetch from external APIs - implemented as separate methods for modularity
        $artsy_trends = $this->fetch_artsy_trends();
        if ($artsy_trends['status'] === 'success') {
            $trends['artsy'] = $artsy_trends['data'];
        }
        
        $auction_trends = $this->fetch_auction_trends();
        if ($auction_trends['status'] === 'success') {
            $trends['auctions'] = $auction_trends['data'];
        }
        
        $gallery_trends = $this->fetch_gallery_trends();
        if ($gallery_trends['status'] === 'success') {
            $trends['galleries'] = $gallery_trends['data'];
        }
        
        $social_art_trends = $this->fetch_social_art_trends();
        if ($social_art_trends['status'] === 'success') {
            $trends['social'] = $social_art_trends['data'];
        }
        
        // Cache the results for 12 hours
        set_transient('vortex_external_art_trends', $trends, 12 * HOUR_IN_SECONDS);
        
        return array(
            'status' => 'success',
            'source' => 'api',
            'data' => $trends
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Fetches trends from Artsy API
 * 
 * @return array Artsy trend data
 */
private function fetch_artsy_trends() {
    // Implementation for fetching from Artsy API
    // This would use the WordPress HTTP API to make external requests
    
    // Simplified mock implementation
    return array(
        'status' => 'success',
        'data' => array(
            'styles' => array(
                array('name' => 'Abstract Expressionism', 'trend_score' => 89),
                array('name' => 'Contemporary', 'trend_score' => 95),
                array('name' => 'Pop Art', 'trend_score' => 82),
            ),
            'mediums' => array(
                array('name' => 'Digital Art', 'trend_score' => 93),
                array('name' => 'Painting', 'trend_score' => 88),
                array('name' => 'Photography', 'trend_score' => 85),
            ),
            'themes' => array(
                array('name' => 'Nature', 'trend_score' => 91),
                array('name' => 'Urban', 'trend_score' => 87),
                array('name' => 'Political', 'trend_score' => 79),
            )
        )
    );
}

/**
 * Fetches trends from auction data
 * 
 * @return array Auction trend data
 */
private function fetch_auction_trends() {
    // Implementation for fetching from auction data sources
    
    // Simplified mock implementation
    return array(
        'status' => 'success',
        'data' => array(
            'styles' => array(
                array('name' => 'Impressionism', 'trend_score' => 86),
                array('name' => 'Modern', 'trend_score' => 91),
                array('name' => 'Minimalism', 'trend_score' => 84),
            ),
            'price_ranges' => array(
                array('range' => 'Under $500', 'volume' => 35),
                array('range' => '$500-$2000', 'volume' => 28),
                array('range' => '$2000-$10000', 'volume' => 22),
                array('range' => 'Over $10000', 'volume' => 15),
            )
        )
    );
}

/**
 * Fetches trends from gallery data
 * 
 * @return array Gallery trend data
 */
private function fetch_gallery_trends() {
    // Implementation for fetching from gallery data sources
    
    // Simplified mock implementation
    return array(
        'status' => 'success',
        'data' => array(
            'styles' => array(
                array('name' => 'Contemporary', 'trend_score' => 93),
                array('name' => 'Surrealism', 'trend_score' => 81),
                array('name' => 'Abstract', 'trend_score' => 88),
            ),
            'exhibition_themes' => array(
                array('name' => 'Climate Change', 'trend_score' => 89),
                array('name' => 'Identity', 'trend_score' => 92),
                array('name' => 'Technology', 'trend_score' => 90),
            )
        )
    );
}

/**
 * Fetches trends from social media
 * 
 * @return array Social media art trend data
 */
private function fetch_social_art_trends() {
    // Implementation for fetching from social media APIs
    
    // Simplified mock implementation
    return array(
        'status' => 'success',
        'data' => array(
            'hashtags' => array(
                array('tag' => '#DigitalArt', 'frequency' => 12500),
                array('tag' => '#NFTArt', 'frequency' => 8900),
                array('tag' => '#ContemporaryArt', 'frequency' => 7600),
            ),
            'styles' => array(
                array('name' => 'Digital', 'trend_score' => 95),
                array('name' => 'Street Art', 'trend_score' => 92),
                array('name' => 'Anime-Inspired', 'trend_score' => 88),
            )
        )
    );
}

/**
 * Get style name from style ID
 * 
 * @param int $style_id Style ID
 * @return string Style name
 */
private function get_style_name($style_id) {
    global $wpdb;
    
    $style_name = $wpdb->get_var($wpdb->prepare(
        "SELECT style_name FROM {$wpdb->prefix}vortex_art_styles WHERE style_id = %d",
        $style_id
    ));
    
    return $style_name ? $style_name : '';
}

/**
 * Get category name from category ID
 * 
 * @param int $category_id Category ID
 * @return string Category name
 */
private function get_category_name($category_id) {
    global $wpdb;
    
    $category_name = $wpdb->get_var($wpdb->prepare(
        "SELECT category_name FROM {$wpdb->prefix}vortex_categories WHERE category_id = %d",
        $category_id
    ));
    
    return $category_name ? $category_name : '';
}

/**
 * Log error message
 * 
 * @param string $message Error message
 */
private function log_error($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('VORTEX CLOE Error: ' . $message);
    }
}

/**
 * Analyze platform trends based on internal data
 * 
 * @return array Platform trend data
 */
private function analyze_platform_trends() {
    try {
        global $wpdb;
        
        $trends = array();
        
        // Get popular styles based on views, likes, and purchases
        $styles_query = "
            SELECT 
                s.style_id,
                s.style_name,
                COUNT(DISTINCT a.artwork_id) as artwork_count,
                SUM(a.view_count) as total_views,
                SUM(a.like_count) as total_likes,
                COUNT(DISTINCT t.transaction_id) as purchase_count,
                SUM(t.amount) as total_sales
            FROM {$wpdb->prefix}vortex_art_styles s
            JOIN {$wpdb->prefix}vortex_artworks a ON s.style_id = a.style_id
            LEFT JOIN {$wpdb->prefix}vortex_transactions t ON a.artwork_id = t.artwork_id AND t.status = 'completed'
            GROUP BY s.style_id
            ORDER BY total_views DESC
            LIMIT 20
        ";
        
        $popular_styles = $wpdb->get_results($styles_query);
        $trends['popular_styles'] = $popular_styles;
        
        // Get emerging themes (growing in popularity)
        $current_date = current_time('mysql');
        $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days', strtotime($current_date)));
        $sixty_days_ago = date('Y-m-d H:i:s', strtotime('-60 days', strtotime($current_date)));
        
        $themes_query = "
            SELECT 
                t.theme_id,
                t.theme_name,
                COUNT(DISTINCT CASE WHEN a.created_at >= %s THEN a.artwork_id ELSE NULL END) as recent_count,
                COUNT(DISTINCT CASE WHEN a.created_at >= %s AND a.created_at < %s THEN a.artwork_id ELSE NULL END) as previous_count,
                COUNT(DISTINCT a.artwork_id) as total_count
            FROM {$wpdb->prefix}vortex_artwork_themes t
            JOIN {$wpdb->prefix}vortex_artwork_theme_mapping tm ON t.theme_id = tm.theme_id
            JOIN {$wpdb->prefix}vortex_artworks a ON tm.artwork_id = a.artwork_id
            GROUP BY t.theme_id
            HAVING recent_count > previous_count
            ORDER BY (recent_count - previous_count) DESC
            LIMIT 15
        ";
        
        $emerging_themes = $wpdb->get_results($wpdb->prepare(
            $themes_query,
            $thirty_days_ago,
            $sixty_days_ago,
            $thirty_days_ago
        ));
        $trends['emerging_themes'] = $emerging_themes;
        
        // Get price trends
        $price_query = "
            SELECT 
                YEAR(t.transaction_time) as year,
                MONTH(t.transaction_time) as month,
                AVG(t.amount) as avg_price,
                MIN(t.amount) as min_price,
                MAX(t.amount) as max_price,
                COUNT(*) as transaction_count
            FROM {$wpdb->prefix}vortex_transactions t
            WHERE t.status = 'completed'
            GROUP BY YEAR(t.transaction_time), MONTH(t.transaction_time)
            ORDER BY year DESC, month DESC
            LIMIT 12
        ";
        
        $price_trends = $wpdb->get_results($price_query);
        $trends['price_trends'] = $price_trends;
        
        // Get trending artists
        $artist_query = "
            SELECT 
                a.user_id as artist_id,
                u.display_name as artist_name,
                COUNT(DISTINCT aw.artwork_id) as artwork_count,
                SUM(aw.view_count) as total_views,
                SUM(aw.like_count) as total_likes,
                COUNT(DISTINCT t.transaction_id) as sales_count,
                SUM(t.amount) as sales_volume
            FROM {$wpdb->prefix}vortex_artworks aw
            JOIN {$wpdb->users} u ON aw.user_id = u.ID
            JOIN {$wpdb->prefix}vortex_artists a ON u.ID = a.user_id
            LEFT JOIN {$wpdb->prefix}vortex_transactions t ON aw.artwork_id = t.artwork_id AND t.status = 'completed'
            WHERE aw.created_at >= %s
            GROUP BY a.user_id
            ORDER BY total_views DESC
            LIMIT 10
        ";
        
        $trending_artists = $wpdb->get_results($wpdb->prepare(
            $artist_query,
            $thirty_days_ago
        ));
        $trends['trending_artists'] = $trending_artists;
        
        return array(
            'status' => 'success',
            'data' => $trends
        );
    } catch (Exception $e) {
        $this->log_error('Failed to analyze platform trends: ' . $e->getMessage());
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Fetch external art trends from APIs and other sources
 * 
 * @return array External trend data
 */
public function fetch_external_art_trends() {
    try {
        $external_trends = array();
        
        // Call APIs for trend data from various sources
        $art_news_trends = $this->fetch_art_news_trends();
        $social_media_trends = $this->fetch_social_media_art_trends();
        $market_analysis = $this->fetch_art_market_analysis();
        $auction_data = $this->fetch_auction_house_data();
        
        // Combine data from different sources
        $external_trends = array(
            'art_news' => $art_news_trends,
            'social_media' => $social_media_trends,
            'market_analysis' => $market_analysis,
            'auction_data' => $auction_data
        );
        
        // Cache the data
        set_transient('vortex_external_art_trends', $external_trends, 24 * HOUR_IN_SECONDS);
        
        return array(
            'status' => 'success',
            'data' => $external_trends
        );
    } catch (Exception $e) {
        $this->log_error('Failed to fetch external art trends: ' . $e->getMessage());
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Correlate internal and external trends
 * 
 * @param array $platform_trends Internal platform trends
 * @param array $external_trends External art market trends
 * @return array Correlated trend data
 */
public function correlate_trends($platform_trends, $external_trends) {
    try {
        // Initialize correlation results
        $correlations = array();
        
        // Match platform styles with external trends
        if (isset($platform_trends['data']['popular_styles']) && isset($external_trends['data']['art_news']['styles'])) {
            $internal_styles = array_column($platform_trends['data']['popular_styles'], 'style_name');
            
            $external_styles = array();
            foreach ($external_trends['data'] as $source => $data) {
                if (isset($data['styles'])) {
                    foreach ($data['styles'] as $style) {
                        if (isset($style['name'])) {
                            $external_styles[] = $style['name'];
                        }
                    }
                }
            }
            
            // Find matching styles
            $matching_styles = array_intersect($internal_styles, $external_styles);
            
            // Find opportunity styles (trending externally but not internally)
            $opportunity_styles = array_diff($external_styles, $internal_styles);
            
            $correlations['styles'] = array(
                'matching' => $matching_styles,
                'opportunities' => $opportunity_styles
            );
        }
        
        // Correlate price trends
        if (isset($platform_trends['data']['price_trends']) && isset($external_trends['data']['market_analysis']['price_data'])) {
            $internal_prices = array_column($platform_trends['data']['price_trends'], 'avg_price');
            $external_prices = array_column($external_trends['data']['market_analysis']['price_data'], 'avg_price');
            
            $price_correlation = $this->calculate_correlation($internal_prices, $external_prices);
            
            $correlations['price_trends'] = array(
                'correlation' => $price_correlation,
                'internal_avg' => array_sum($internal_prices) / count($internal_prices),
                'external_avg' => array_sum($external_prices) / count($external_prices)
            );
        }
        
        // Correlate emerging themes
        if (isset($platform_trends['data']['emerging_themes']) && isset($external_trends['data']['social_media']['trending_topics'])) {
            $internal_themes = array_column($platform_trends['data']['emerging_themes'], 'theme_name');
            $external_themes = array_column($external_trends['data']['social_media']['trending_topics'], 'topic');
            
            // Find matching themes
            $matching_themes = array_intersect($internal_themes, $external_themes);
            
            // Find opportunity themes
            $opportunity_themes = array_diff($external_themes, $internal_themes);
            
            $correlations['themes'] = array(
                'matching' => $matching_themes,
                'opportunities' => $opportunity_themes
            );
        }
        
        return array(
            'status' => 'success',
            'data' => $correlations
        );
    } catch (Exception $e) {
        $this->log_error('Failed to correlate trends: ' . $e->getMessage());
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Fetch art news trends from art news APIs and sites
 * 
 * @return array Art news trend data
 */
private function fetch_art_news_trends() {
    // This would call external APIs or parse relevant websites
    // For example, we'd query an art news API or RSS feed
    
    // For now, returning sample data
    return array(
        'styles' => array(
            array('name' => 'Digital Surrealism', 'mentions' => 145),
            array('name' => 'Neo-Expressionism', 'mentions' => 112),
            array('name' => 'Computational Art', 'mentions' => 98),
            array('name' => 'Post-Digital', 'mentions' => 87),
            array('name' => 'AI-Assisted Realism', 'mentions' => 76)
        ),
        'artists' => array(
            array('name' => 'Sofia Crespo', 'mentions' => 87),
            array('name' => 'Refik Anadol', 'mentions' => 76),
            array('name' => 'Mario Klingemann', 'mentions' => 65),
            array('name' => 'Claire Silver', 'mentions' => 59),
            array('name' => 'Tyler Hobbs', 'mentions' => 52)
        ),
        'topics' => array(
            array('topic' => 'NFT Sustainability', 'mentions' => 189),
            array('topic' => 'AI Ethics', 'mentions' => 156),
            array('topic' => 'Digital Ownership', 'mentions' => 132),
            array('topic' => 'Generative Art', 'mentions' => 124),
            array('topic' => 'Blockchain Art Authentication', 'mentions' => 98)
        )
    );
}

/**
 * Fetch social media art trends
 * 
 * @return array Social media trend data
 */
private function fetch_social_media_art_trends() {
    // This would connect to social media APIs to get trend data
    // For example, Twitter API for trending hashtags or Instagram API for popular art posts
    
    // For now, returning sample data
    return array(
        'platforms' => array(
            'instagram' => array(
                'hashtags' => array(
                    '#digitalart' => 2456789,
                    '#nftart' => 1345678,
                    '#aiart' => 987654,
                    '#generativeart' => 876543,
                    '#cryptoart' => 765432
                )
            ),
            'twitter' => array(
                'hashtags' => array(
                    '#nftcollector' => 456789,
                    '#digitalartist' => 345678,
                    '#artmarket' => 234567,
                    '#aiartcommunity' => 123456,
                    '#blockchainart' => 98765
                )
            )
        ),
        'trending_topics' => array(
            array('topic' => 'Generative Art', 'volume' => 345678),
            array('topic' => 'Digital Surrealism', 'volume' => 234567),
            array('topic' => 'Nature-Inspired Digital Art', 'volume' => 123456),
            array('topic' => 'Abstract Digital Landscapes', 'volume' => 98765),
            array('topic' => 'Cyberpunk Aesthetics', 'volume' => 87654)
        )
    );
}

/**
 * Fetch art market analysis from financial and art market sources
 * 
 * @return array Art market analysis data
 */
private function fetch_art_market_analysis() {
    // This would connect to art market analytics APIs or websites
    
    // For now, returning sample data
    return array(
        'market_trends' => array(
            array('category' => 'Digital Art', 'growth_rate' => 23.5),
            array('category' => 'AI Art', 'growth_rate' => 34.2),
            array('category' => 'NFT Collections', 'growth_rate' => 15.7),
            array('category' => 'Generative Art', 'growth_rate' => 27.8),
            array('category' => 'Digital Sculpture', 'growth_rate' => 12.3)
        ),
        'price_data' => array(
            array('month' => 'January', 'avg_price' => 2345.67),
            array('month' => 'February', 'avg_price' => 2456.78),
            array('month' => 'March', 'avg_price' => 2567.89),
            array('month' => 'April', 'avg_price' => 2678.90),
            array('month' => 'May', 'avg_price' => 2789.01),
            array('month' => 'June', 'avg_price' => 2890.12)
        ),
        'investment_insights' => array(
            array('style' => 'AI-Generated Art', 'roi_potential' => 'High'),
            array('style' => 'Generative Landscapes', 'roi_potential' => 'Medium-High'),
            array('style' => 'Digital Portraits', 'roi_potential' => 'Medium'),
            array('style' => 'Abstract Digital', 'roi_potential' => 'Medium-High'),
            array('style' => 'Blockchain Visualizations', 'roi_potential' => 'Medium')
        )
    );
}

/**
 * Fetch auction house data for major art sales
 * 
 * @return array Auction house data
 */
private function fetch_auction_house_data() {
    // This would connect to auction house APIs or websites
    
    // For now, returning sample data
    return array(
        'recent_sales' => array(
            array(
                'title' => 'Digital Harmony #42',
                'artist' => 'Alexandra Chen',
                'price' => 125000,
                'auction_house' => 'Christie\'s',
                'date' => '2023-05-15'
            ),
            array(
                'title' => 'Neural Network Dreams',
                'artist' => 'Marcus Wei',
                'price' => 98000,
                'auction_house' => 'Sotheby\'s',
                'date' => '2023-05-12'
            ),
            array(
                'title' => 'Emergent Consciousness',
                'artist' => 'Sophia Rodriguez',
                'price' => 82500,
                'auction_house' => 'Phillips',
                'date' => '2023-05-08'
            )
        ),
        'price_records' => array(
            array(
                'category' => 'AI-Generated Art',
                'record_price' => 6900000,
                'artwork' => 'The First 5000 Days',
                'artist' => 'Beeple',
                'date' => '2021-03-11'
            ),
            array(
                'category' => 'Generative Art',
                'record_price' => 2800000,
                'artwork' => 'Ringers #109',
                'artist' => 'Dmitri Cherniak',
                'date' => '2022-01-22'
            )
        )
    );
}

/**
 * Calculate correlation coefficient between two data sets
 * 
 * @param array $x First data set
 * @param array $y Second data set
 * @return float Correlation coefficient
 */
private function calculate_correlation($x, $y) {
    // Check that arrays have the same length
    $n = count($x);
    if ($n !== count($y) || $n === 0) {
        return 0;
    }
    
    // Calculate means
    $x_mean = array_sum($x) / $n;
    $y_mean = array_sum($y) / $n;
    
    // Calculate covariance and variances
    $covariance = 0;
    $x_variance = 0;
    $y_variance = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $x_diff = $x[$i] - $x_mean;
        $y_diff = $y[$i] - $y_mean;
        
        $covariance += $x_diff * $y_diff;
        $x_variance += $x_diff * $x_diff;
        $y_variance += $y_diff * $y_diff;
    }
    
    // Avoid division by zero
    if ($x_variance == 0 || $y_variance == 0) {
        return 0;
    }
    
    // Calculate correlation
    return $covariance / (sqrt($x_variance) * sqrt($y_variance));
}

/**
 * Get average session duration for users
 * 
 * @param string $period Time period for calculation
 * @return float Average session duration in minutes
 */
private function get_average_session_duration($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        $query = $wpdb->prepare(
            "SELECT AVG(session_duration) as avg_duration
            FROM {$wpdb->prefix}vortex_user_sessions
            WHERE session_start >= %s
            AND session_duration > 0",
            $time_constraint
        );
        
        $avg_duration = $wpdb->get_var($query);
        
        // Convert to minutes and round to 2 decimal places
        $avg_duration_minutes = round($avg_duration / 60, 2);
        
        return $avg_duration_minutes ? $avg_duration_minutes : 0;
    } catch (Exception $e) {
        $this->log_error('Failed to get average session duration: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get peak activity hours from user behavior
 * 
 * @param string $period Time period for calculation
 * @return array Peak activity hours data
 */
private function get_peak_activity_hours($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        $query = $wpdb->prepare(
            "SELECT 
                HOUR(activity_time) as hour,
                COUNT(*) as activity_count
            FROM {$wpdb->prefix}vortex_user_activity
            WHERE activity_time >= %s
            GROUP BY HOUR(activity_time)
            ORDER BY activity_count DESC",
            $time_constraint
        );
        
        $results = $wpdb->get_results($query);
        
        // Prepare hour distribution
        $hour_distribution = array();
        for ($i = 0; $i < 24; $i++) {
            $hour_distribution[$i] = 0;
        }
        
        // Populate with actual data
        foreach ($results as $row) {
            $hour_distribution[$row->hour] = intval($row->activity_count);
        }
        
        // Find peak hours (top 3)
        $peak_hours = array();
        $temp_distribution = $hour_distribution;
        arsort($temp_distribution);
        $peak_hours = array_slice(array_keys($temp_distribution), 0, 3);
        
        return array(
            'hour_distribution' => $hour_distribution,
            'peak_hours' => $peak_hours,
            'total_activity' => array_sum($hour_distribution)
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get peak activity hours: ' . $e->getMessage());
        return array(
            'hour_distribution' => array(),
            'peak_hours' => array(),
            'total_activity' => 0
        );
    }
}

/**
 * Get weekday distribution of user activity
 * 
 * @param string $period Time period for calculation
 * @return array Weekday distribution data
 */
private function get_weekday_distribution($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        $query = $wpdb->prepare(
            "SELECT 
                DAYOFWEEK(activity_time) as day_of_week,
                COUNT(*) as activity_count
            FROM {$wpdb->prefix}vortex_user_activity
            WHERE activity_time >= %s
            GROUP BY DAYOFWEEK(activity_time)
            ORDER BY DAYOFWEEK(activity_time)",
            $time_constraint
        );
        
        $results = $wpdb->get_results($query);
        
        // MySQL's DAYOFWEEK returns 1 for Sunday, 2 for Monday, etc.
        // Let's convert to 0 for Sunday, 1 for Monday, etc. to match PHP's convention
        $days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
        
        // Initialize all days with zero
        $weekday_distribution = array();
        foreach ($days as $index => $day) {
            $weekday_distribution[$day] = 0;
        }
        
        // Fill in the actual data
        foreach ($results as $row) {
            $day_index = $row->day_of_week - 1; // Adjust for 0-based index
            $weekday_distribution[$days[$day_index]] = intval($row->activity_count);
        }
        
        // Calculate percentages
        $total_activity = array_sum($weekday_distribution);
        $weekday_percentages = array();
        
        if ($total_activity > 0) {
            foreach ($weekday_distribution as $day => $count) {
                $weekday_percentages[$day] = round(($count / $total_activity) * 100, 2);
            }
        }
        
        return array(
            'distribution' => $weekday_distribution,
            'percentages' => $weekday_percentages,
            'total_activity' => $total_activity
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get weekday distribution: ' . $e->getMessage());
        return array(
            'distribution' => array(),
            'percentages' => array(),
            'total_activity' => 0
        );
    }
}

/**
 * Get region distribution of users
 * 
 * @param string $period Time period for analysis
 * @return array Region distribution data
 */
private function get_region_distribution($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        $query = $wpdb->prepare(
            "SELECT 
                region,
                COUNT(DISTINCT user_id) as user_count
            FROM {$wpdb->prefix}vortex_user_geo_data
            WHERE update_time >= %s
            GROUP BY region
            ORDER BY user_count DESC",
            $time_constraint
        );
        
        $results = $wpdb->get_results($query);
        
        // Total users in the period
        $total_users = 0;
        foreach ($results as $row) {
            $total_users += $row->user_count;
        }
        
        // Calculate percentages
        $region_distribution = array();
        foreach ($results as $row) {
            $region_distribution[] = array(
                'region' => $row->region,
                'user_count' => $row->user_count,
                'percentage' => $total_users > 0 ? round(($row->user_count / $total_users) * 100, 2) : 0
            );
        }
        
        // Get dominant regions (top 5)
        $dominant_regions = array_slice($region_distribution, 0, 5);
        
        return array(
            'distribution' => $region_distribution,
            'dominant_regions' => $dominant_regions,
            'total_users' => $total_users
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get region distribution: ' . $e->getMessage());
        return array(
            'distribution' => array(),
            'dominant_regions' => array(),
            'total_users' => 0
        );
    }
}

/**
 * Get age group distribution of users
 * 
 * @param string $period Time period for analysis
 * @return array Age group distribution data
 */
private function get_age_group_distribution($period = 'month') {
    try {
        global $wpdb;
        
        // Define age groups
        $age_groups = array(
            'under_18' => 'Under 18',
            '18_24' => '18-24',
            '25_34' => '25-34',
            '35_44' => '35-44',
            '45_54' => '45-54',
            '55_64' => '55-64',
            '65_plus' => '65+',
            'undisclosed' => 'Undisclosed'
        );
        
        $time_constraint = $this->get_time_constraint($period);
        
        $query = $wpdb->prepare(
            "SELECT 
                age_group,
                COUNT(DISTINCT user_id) as user_count
            FROM {$wpdb->prefix}vortex_user_demographics
            WHERE update_time >= %s
            GROUP BY age_group
            ORDER BY FIELD(age_group, 'under_18', '18_24', '25_34', '35_44', '45_54', '55_64', '65_plus', 'undisclosed')",
            $time_constraint
        );
        
        $results = $wpdb->get_results($query);
        
        // Initialize all age groups with zero
        $age_distribution = array();
        foreach ($age_groups as $key => $label) {
            $age_distribution[$key] = array(
                'label' => $label,
                'count' => 0,
                'percentage' => 0
            );
        }
        
        // Fill in the actual data
        $total_users = 0;
        foreach ($results as $row) {
            if (isset($age_distribution[$row->age_group])) {
                $age_distribution[$row->age_group]['count'] = intval($row->user_count);
                $total_users += intval($row->user_count);
            }
        }
        
        // Calculate percentages
        if ($total_users > 0) {
            foreach ($age_distribution as $key => $data) {
                $age_distribution[$key]['percentage'] = round(($data['count'] / $total_users) * 100, 2);
            }
        }
        
        return array(
            'distribution' => $age_distribution,
            'total_users' => $total_users
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get age group distribution: ' . $e->getMessage());
        return array(
            'distribution' => array(),
            'total_users' => 0
        );
    }
}

/**
 * Get gender distribution of users
 * 
 * @param string $period Time period for analysis
 * @return array Gender distribution data
 */
private function get_gender_distribution($period = 'month') {
    try {
        global $wpdb;
        
        // Define gender categories
        $gender_categories = array(
            'male' => 'Male',
            'female' => 'Female',
            'non_binary' => 'Non-Binary',
            'other' => 'Other',
            'undisclosed' => 'Undisclosed'
        );
        
        $time_constraint = $this->get_time_constraint($period);
        
        $query = $wpdb->prepare(
            "SELECT 
                gender,
                COUNT(DISTINCT user_id) as user_count
            FROM {$wpdb->prefix}vortex_user_demographics
            WHERE update_time >= %s
            GROUP BY gender",
            $time_constraint
        );
        
        $results = $wpdb->get_results($query);
        
        // Initialize all gender categories with zero
        $gender_distribution = array();
        foreach ($gender_categories as $key => $label) {
            $gender_distribution[$key] = array(
                'label' => $label,
                'count' => 0,
                'percentage' => 0
            );
        }
        
        // Fill in the actual data
        $total_users = 0;
        foreach ($results as $row) {
            if (isset($gender_distribution[$row->gender])) {
                $gender_distribution[$row->gender]['count'] = intval($row->user_count);
                $total_users += intval($row->user_count);
            }
        }
        
        // Calculate percentages
        if ($total_users > 0) {
            foreach ($gender_distribution as $key => $data) {
                $gender_distribution[$key]['percentage'] = round(($data['count'] / $total_users) * 100, 2);
            }
        }
        
        return array(
            'distribution' => $gender_distribution,
            'total_users' => $total_users
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get gender distribution: ' . $e->getMessage());
        return array(
            'distribution' => array(),
            'total_users' => 0
        );
    }
}

/**
 * Get language preferences of users
 * 
 * @param string $period Time period for analysis
 * @return array Language preferences data
 */
private function get_language_preferences($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        $query = $wpdb->prepare(
            "SELECT 
                language_code,
                language_name,
                COUNT(DISTINCT user_id) as user_count
            FROM {$wpdb->prefix}vortex_user_languages
            WHERE last_used >= %s
            GROUP BY language_code, language_name
            ORDER BY user_count DESC",
            $time_constraint
        );
        
        $results = $wpdb->get_results($query);
        
        // Total users in the period
        $total_users = 0;
        foreach ($results as $row) {
            $total_users += $row->user_count;
        }
        
        // Calculate percentages
        $language_distribution = array();
        foreach ($results as $row) {
            $language_distribution[] = array(
                'code' => $row->language_code,
                'name' => $row->language_name,
                'user_count' => $row->user_count,
                'percentage' => $total_users > 0 ? round(($row->user_count / $total_users) * 100, 2) : 0
            );
        }
        
        // Get primary languages (top 5)
        $primary_languages = array_slice($language_distribution, 0, 5);
        
        return array(
            'distribution' => $language_distribution,
            'primary_languages' => $primary_languages,
            'total_users' => $total_users
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get language preferences: ' . $e->getMessage());
        return array(
            'distribution' => array(),
            'primary_languages' => array(),
            'total_users' => 0
        );
    }
}

/**
 * Calculate view to like ratio for artworks
 * 
 * @param string $period Time period for analysis
 * @return array View to like ratio data
 */
private function calculate_view_to_like_ratio($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        // Get overall stats
        $query = $wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT v.view_id) as total_views,
                COUNT(DISTINCT l.like_id) as total_likes
            FROM {$wpdb->prefix}vortex_artwork_views v
            LEFT JOIN {$wpdb->prefix}vortex_artwork_likes l ON 
                v.artwork_id = l.artwork_id AND 
                v.user_id = l.user_id AND 
                l.like_time >= v.view_time AND
                l.like_time <= DATE_ADD(v.view_time, INTERVAL 24 HOUR)
            WHERE v.view_time >= %s",
            $time_constraint
        );
        
        $overall_stats = $wpdb->get_row($query);
        
        // Calculate overall ratio
        $overall_ratio = 0;
        if ($overall_stats && $overall_stats->total_views > 0) {
            $overall_ratio = round(($overall_stats->total_likes / $overall_stats->total_views) * 100, 2);
        }
        
        // Get category-specific ratios
        $category_query = $wpdb->prepare(
            "SELECT 
                c.category_id,
                c.category_name,
                COUNT(DISTINCT v.view_id) as views,
                COUNT(DISTINCT l.like_id) as likes,
                CASE 
                    WHEN COUNT(DISTINCT v.view_id) > 0 
                    THEN (COUNT(DISTINCT l.like_id) / COUNT(DISTINCT v.view_id)) * 100
                    ELSE 0
                END as ratio
            FROM {$wpdb->prefix}vortex_categories c
            JOIN {$wpdb->prefix}vortex_artworks a ON c.category_id = a.category_id
            JOIN {$wpdb->prefix}vortex_artwork_views v ON a.artwork_id = v.artwork_id
            LEFT JOIN {$wpdb->prefix}vortex_artwork_likes l ON 
                v.artwork_id = l.artwork_id AND 
                v.user_id = l.user_id AND 
                l.like_time >= v.view_time AND
                l.like_time <= DATE_ADD(v.view_time, INTERVAL 24 HOUR)
            WHERE v.view_time >= %s
            GROUP BY c.category_id
            HAVING views > 10
            ORDER BY ratio DESC",
            $time_constraint
        );
        
        $category_ratios = $wpdb->get_results($category_query);
        
        // Process category ratios
        $processed_categories = array();
        foreach ($category_ratios as $category) {
            $processed_categories[] = array(
                'category_id' => $category->category_id,
                'category_name' => $category->category_name,
                'views' => $category->views,
                'likes' => $category->likes,
                'ratio' => round($category->ratio, 2)
            );
        }
        
        return array(
            'overall_views' => $overall_stats ? $overall_stats->total_views : 0,
            'overall_likes' => $overall_stats ? $overall_stats->total_likes : 0,
            'overall_ratio' => $overall_ratio,
            'category_ratios' => $processed_categories
        );
    } catch (Exception $e) {
        $this->log_error('Failed to calculate view to like ratio: ' . $e->getMessage());
        return array(
            'overall_views' => 0,
            'overall_likes' => 0,
            'overall_ratio' => 0,
            'category_ratios' => array()
        );
    }
}

/**
 * Get average view duration for artworks
 * 
 * @param string $period Time period for analysis
 * @return array View duration data
 */
private function get_average_view_duration($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        // Get overall average view duration
        $query = $wpdb->prepare(
            "SELECT AVG(view_duration) as avg_duration
            FROM {$wpdb->prefix}vortex_artwork_views
            WHERE view_time >= %s
            AND view_duration > 0
            AND view_duration < 3600", // Exclude sessions over 1 hour (likely left open)
            $time_constraint
        );
        
        $avg_duration = $wpdb->get_var($query);
        $avg_duration_seconds = $avg_duration ? round($avg_duration, 2) : 0;
        
        // Get category-specific average view durations
        $category_query = $wpdb->prepare(
            "SELECT 
                c.category_id,
                c.category_name,
                AVG(v.view_duration) as avg_duration,
                COUNT(v.view_id) as view_count
            FROM {$wpdb->prefix}vortex_categories c
            JOIN {$wpdb->prefix}vortex_artworks a ON c.category_id = a.category_id
            JOIN {$wpdb->prefix}vortex_artwork_views v ON a.artwork_id = v.artwork_id
            WHERE v.view_time >= %s
            AND v.view_duration > 0
            AND v.view_duration < 3600
            GROUP BY c.category_id
            HAVING view_count > 10
            ORDER BY avg_duration DESC",
            $time_constraint
        );
        
        $category_durations = $wpdb->get_results($category_query);
        
        // Process category durations
        $processed_categories = array();
        foreach ($category_durations as $category) {
            $processed_categories[] = array(
                'category_id' => $category->category_id,
                'category_name' => $category->category_name,
                'avg_duration_seconds' => round($category->avg_duration, 2),
                'view_count' => $category->view_count
            );
        }
        
        // Get view duration distribution by time ranges
        $ranges = array(
            array('min' => 0, 'max' => 10, 'label' => '<10s'), 
            array('min' => 10, 'max' => 30, 'label' => '10-30s'),
            array('min' => 30, 'max' => 60, 'label' => '30-60s'),
            array('min' => 60, 'max' => 120, 'label' => '1-2m'),
            array('min' => 120, 'max' => 300, 'label' => '2-5m'),
            array('min' => 300, 'max' => 3600, 'label' => '>5m')
        );
        
        $distribution = array();
        foreach ($ranges as $range) {
            $range_query = $wpdb->prepare(
                "SELECT COUNT(*) as count
                FROM {$wpdb->prefix}vortex_artwork_views
                WHERE view_time >= %s
                AND view_duration > %d
                AND view_duration <= %d",
                $time_constraint,
                $range['min'],
                $range['max']
            );
            
            $count = $wpdb->get_var($range_query);
            
            $distribution[] = array(
                'range' => $range['label'],
                'count' => intval($count)
            );
        }
        
        return array(
            'avg_duration_seconds' => $avg_duration_seconds,
            'avg_duration_formatted' => $this->format_duration($avg_duration_seconds),
            'category_durations' => $processed_categories,
            'duration_distribution' => $distribution
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get average view duration: ' . $e->getMessage());
        return array(
            'avg_duration_seconds' => 0,
            'avg_duration_formatted' => '0s',
            'category_durations' => array(),
            'duration_distribution' => array()
        );
    }
}

/**
 * Helper function to format duration in seconds to a readable format
 * 
 * @param int $seconds Duration in seconds
 * @return string Formatted duration
 */
private function format_duration($seconds) {
    if ($seconds < 60) {
        return round($seconds) . 's';
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $remaining_seconds = $seconds % 60;
        return $minutes . 'm ' . $remaining_seconds . 's';
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . 'h ' . $minutes . 'm';
    }
}

/**
 * Get style affinity clusters for user preferences
 * 
 * @param string $period Time period for analysis
 * @return array Style affinity data
 */
private function get_style_affinity_clusters($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        // Get overall style popularity
        $style_query = $wpdb->prepare(
            "SELECT 
                s.style_id,
                s.style_name,
                COUNT(DISTINCT a.artwork_id) as artwork_count,
                COUNT(DISTINCT v.user_id) as viewer_count,
                COUNT(DISTINCT l.user_id) as liker_count,
                COUNT(DISTINCT p.transaction_id) as purchase_count
            FROM {$wpdb->prefix}vortex_art_styles s
            JOIN {$wpdb->prefix}vortex_artworks a ON s.style_id = a.style_id
            LEFT JOIN {$wpdb->prefix}vortex_artwork_views v ON 
                a.artwork_id = v.artwork_id AND 
                v.view_time >= %s
            LEFT JOIN {$wpdb->prefix}vortex_artwork_likes l ON 
                a.artwork_id = l.artwork_id AND 
                l.like_time >= %s
            LEFT JOIN {$wpdb->prefix}vortex_transactions p ON 
                a.artwork_id = p.artwork_id AND 
                p.transaction_time >= %s AND
                p.status = 'completed'
            GROUP BY s.style_id
            HAVING artwork_count > 0
            ORDER BY viewer_count DESC",
            $time_constraint,
            $time_constraint,
            $time_constraint
        );
        
        $style_popularity = $wpdb->get_results($style_query);
        
        // Calculate engagement scores for each style
        $styles_with_scores = array();
        foreach ($style_popularity as $style) {
            // Engagement score = (liker_count/viewer_count) + 5*(purchase_count/viewer_count)
            $engagement_rate = $style->viewer_count > 0 ? $style->liker_count / $style->viewer_count : 0;
            $conversion_rate = $style->viewer_count > 0 ? $style->purchase_count / $style->viewer_count : 0;
            $engagement_score = $engagement_rate + (5 * $conversion_rate);
            
            $styles_with_scores[] = array(
                'style_id' => $style->style_id,
                'style_name' => $style->style_name,
                'artwork_count' => $style->artwork_count,
                'viewer_count' => $style->viewer_count,
                'liker_count' => $style->liker_count,
                'purchase_count' => $style->purchase_count,
                'engagement_score' => round($engagement_score, 4)
            );
        }
        
        // Sort by engagement score
        usort($styles_with_scores, function($a, $b) {
            return $b['engagement_score'] <=> $a['engagement_score'];
        });
        
        // Get co-occurrence of style preferences (which styles are liked together)
        $cooccurrence_query = $wpdb->prepare(
            "SELECT 
                s1.style_id as style1_id,
                s1.style_name as style1_name,
                s2.style_id as style2_id,
                s2.style_name as style2_name,
                COUNT(DISTINCT l1.user_id) as common_users
            FROM {$wpdb->prefix}vortex_artwork_likes l1
            JOIN {$wpdb->prefix}vortex_artwork_likes l2 ON 
                l1.user_id = l2.user_id AND
                l1.artwork_id <> l2.artwork_id
            JOIN {$wpdb->prefix}vortex_artworks a1 ON l1.artwork_id = a1.artwork_id
            JOIN {$wpdb->prefix}vortex_artworks a2 ON l2.artwork_id = a2.artwork_id
            JOIN {$wpdb->prefix}vortex_art_styles s1 ON a1.style_id = s1.style_id
            JOIN {$wpdb->prefix}vortex_art_styles s2 ON a2.style_id = s2.style_id
            WHERE l1.like_time >= %s
            AND l2.like_time >= %s
            AND s1.style_id < s2.style_id
            GROUP BY s1.style_id, s2.style_id
            HAVING common_users > 5
            ORDER BY common_users DESC
            LIMIT 20",
            $time_constraint,
            $time_constraint
        );
        
        $style_cooccurrences = $wpdb->get_results($cooccurrence_query);
        
        // Identify distinct affinity clusters
        $clusters = array();
        $processed_styles = array();
        
        // First pass: seed clusters with strongest co-occurrences
        foreach ($style_cooccurrences as $cooccurrence) {
            if (count($clusters) >= 5) break; // Limit to 5 clusters
            
            // Skip if both styles already in a cluster
            if (
                in_array($cooccurrence->style1_id, $processed_styles) && 
                in_array($cooccurrence->style2_id, $processed_styles)
            ) {
                continue;
            }
            
            // Create new cluster
            $cluster = array(
                'styles' => array(
                    array(
                        'id' => $cooccurrence->style1_id,
                        'name' => $cooccurrence->style1_name
                    ),
                    array(
                        'id' => $cooccurrence->style2_id,
                        'name' => $cooccurrence->style2_name
                    )
                ),
                'strength' => $cooccurrence->common_users
            );
            
            $clusters[] = $cluster;
            $processed_styles[] = $cooccurrence->style1_id;
            $processed_styles[] = $cooccurrence->style2_id;
        }
        
        // Second pass: grow existing clusters
        foreach ($style_cooccurrences as $cooccurrence) {
            $style1_in_cluster = in_array($cooccurrence->style1_id, $processed_styles);
            $style2_in_cluster = in_array($cooccurrence->style2_id, $processed_styles);
            
            // If one style is in a cluster and the other isn't
            if ($style1_in_cluster && !$style2_in_cluster) {
                foreach ($clusters as $key => $cluster) {
                    foreach ($cluster['styles'] as $style) {
                        if ($style['id'] == $cooccurrence->style1_id) {
                            // Add style2 to this cluster
                            $clusters[$key]['styles'][] = array(
                                'id' => $cooccurrence->style2_id,
                                'name' => $cooccurrence->style2_name
                            );
                            $processed_styles[] = $cooccurrence->style2_id;
                            break 2;
                        }
                    }
                }
            } elseif (!$style1_in_cluster && $style2_in_cluster) {
                foreach ($clusters as $key => $cluster) {
                    foreach ($cluster['styles'] as $style) {
                        if ($style['id'] == $cooccurrence->style2_id) {
                            // Add style1 to this cluster
                            $clusters[$key]['styles'][] = array(
                                'id' => $cooccurrence->style1_id,
                                'name' => $cooccurrence->style1_name
                            );
                            $processed_styles[] = $cooccurrence->style1_id;
                            break 2;
                        }
                    }
                }
            }
        }
        
        return array(
            'style_popularity' => $styles_with_scores,
            'cooccurrences' => $style_cooccurrences,
            'affinity_clusters' => $clusters
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get style affinity clusters: ' . $e->getMessage());
        return array(
            'style_popularity' => array(),
            'cooccurrences' => array(),
            'affinity_clusters' => array()
        );
    }
}

/**
 * Get purchase funnel metrics
 * 
 * @param string $period Time period for analysis
 * @return array Purchase funnel data
 */
private function get_purchase_funnel_metrics($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        // Get overall funnel metrics
        $query = $wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT v.user_id) as unique_viewers,
                COUNT(DISTINCT l.user_id) as users_who_liked,
                COUNT(DISTINCT c.user_id) as users_who_added_to_cart,
                COUNT(DISTINCT t.user_id) as users_who_purchased
            FROM 
                (SELECT DISTINCT user_id FROM {$wpdb->prefix}vortex_artwork_views 
                 WHERE view_time >= %s) as v
                LEFT JOIN (
                    SELECT DISTINCT user_id FROM {$wpdb->prefix}vortex_artwork_likes 
                    WHERE like_time >= %s
                ) as l ON v.user_id = l.user_id
                LEFT JOIN (
                    SELECT DISTINCT user_id FROM {$wpdb->prefix}vortex_cart_items 
                    WHERE added_time >= %s
                ) as c ON v.user_id = c.user_id
                LEFT JOIN (
                    SELECT DISTINCT user_id FROM {$wpdb->prefix}vortex_transactions 
                    WHERE transaction_time >= %s AND status = 'completed'
                ) as t ON v.user_id = t.user_id",
            $time_constraint,
            $time_constraint,
            $time_constraint,
            $time_constraint
        );
        
        $funnel_metrics = $wpdb->get_row($query);
        
        // Calculate conversion rates
        $view_to_like_rate = 0;
        $like_to_cart_rate = 0;
        $cart_to_purchase_rate = 0;
        $overall_conversion_rate = 0;
        
        if ($funnel_metrics) {
            if ($funnel_metrics->unique_viewers > 0) {
                $view_to_like_rate = round(($funnel_metrics->users_who_liked / $funnel_metrics->unique_viewers) * 100, 2);
                $overall_conversion_rate = round(($funnel_metrics->users_who_purchased / $funnel_metrics->unique_viewers) * 100, 2);
            }
            
            if ($funnel_metrics->users_who_liked > 0) {
                $like_to_cart_rate = round(($funnel_metrics->users_who_added_to_cart / $funnel_metrics->users_who_liked) * 100, 2);
            }
            
            if ($funnel_metrics->users_who_added_to_cart > 0) {
                $cart_to_purchase_rate = round(($funnel_metrics->users_who_purchased / $funnel_metrics->users_who_added_to_cart) * 100, 2);
            }
        }
        
        // Get funnel metrics per price range
        $price_ranges = array(
            array('min' => 0, 'max' => 50, 'label' => 'Under $50'),
            array('min' => 50, 'max' => 100, 'label' => '$50-$100'),
            array('min' => 100, 'max' => 250, 'label' => '$100-$250'),
            array('min' => 250, 'max' => 500, 'label' => '$250-$500'),
            array('min' => 500, 'max' => 1000, 'label' => '$500-$1000'),
            array('min' => 1000, 'max' => PHP_INT_MAX, 'label' => 'Over $1000')
        );
        
        $price_range_metrics = array();
        
        foreach ($price_ranges as $range) {
            $range_query = $wpdb->prepare(
                "SELECT 
                    COUNT(DISTINCT v.user_id) as unique_viewers,
                    COUNT(DISTINCT l.user_id) as users_who_liked,
                    COUNT(DISTINCT c.user_id) as users_who_added_to_cart,
                    COUNT(DISTINCT t.user_id) as users_who_purchased
                FROM {$wpdb->prefix}vortex_artworks a
                LEFT JOIN {$wpdb->prefix}vortex_artwork_views v ON 
                    a.artwork_id = v.artwork_id AND
                    v.view_time >= %s
                LEFT JOIN {$wpdb->prefix}vortex_artwork_likes l ON 
                    a.artwork_id = l.artwork_id AND
                    l.like_time >= %s
                LEFT JOIN {$wpdb->prefix}vortex_cart_items c ON 
                    a.artwork_id = c.artwork_id AND
                    c.added_time >= %s
                LEFT JOIN {$wpdb->prefix}vortex_transactions t ON 
                    a.artwork_id = t.artwork_id AND
                    t.transaction_time >= %s AND
                    t.status = 'completed'
                WHERE a.price > %f AND a.price <= %f",
                $time_constraint,
                $time_constraint,
                $time_constraint,
                $time_constraint,
                $range['min'],
                $range['max']
            );
            
            $range_metrics = $wpdb->get_row($range_query);
            
            // Calculate conversion rate for this price range
            $range_conversion = 0;
            if ($range_metrics && $range_metrics->unique_viewers > 0) {
                $range_conversion = round(($range_metrics->users_who_purchased / $range_metrics->unique_viewers) * 100, 2);
            }
            
            $price_range_metrics[] = array(
                'range' => $range['label'],
                'viewers' => $range_metrics ? $range_metrics->unique_viewers : 0,
                'likers' => $range_metrics ? $range_metrics->users_who_liked : 0,
                'cart_adds' => $range_metrics ? $range_metrics->users_who_added_to_cart : 0,
                'purchasers' => $range_metrics ? $range_metrics->users_who_purchased : 0,
                'conversion_rate' => $range_conversion
            );
        }
        
        return array(
            'unique_viewers' => $funnel_metrics ? $funnel_metrics->unique_viewers : 0,
            'users_who_liked' => $funnel_metrics ? $funnel_metrics->users_who_liked : 0,
            'users_who_added_to_cart' => $funnel_metrics ? $funnel_metrics->users_who_added_to_cart : 0,
            'users_who_purchased' => $funnel_metrics ? $funnel_metrics->users_who_purchased : 0,
            'view_to_like_rate' => $view_to_like_rate,
            'like_to_cart_rate' => $like_to_cart_rate,
            'cart_to_purchase_rate' => $cart_to_purchase_rate,
            'overall_conversion_rate' => $overall_conversion_rate,
            'price_range_metrics' => $price_range_metrics
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get purchase funnel metrics: ' . $e->getMessage());
        return array(
            'unique_viewers' => 0,
            'users_who_liked' => 0,
            'users_who_added_to_cart' => 0,
            'users_who_purchased' => 0,
            'view_to_like_rate' => 0,
            'like_to_cart_rate' => 0,
            'cart_to_purchase_rate' => 0,
            'overall_conversion_rate' => 0,
            'price_range_metrics' => array()
        );
    }
}

/**
 * Get abandoned cart statistics
 * 
 * @param string $period Time period for analysis
 * @return array Abandoned cart data
 */
private function get_abandoned_cart_stats($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        // Get overall abandoned cart stats
        $query = $wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT c.cart_id) as total_carts,
                COUNT(DISTINCT CASE WHEN t.transaction_id IS NULL THEN c.cart_id ELSE NULL END) as abandoned_carts,
                COUNT(DISTINCT CASE WHEN t.transaction_id IS NOT NULL THEN c.cart_id ELSE NULL END) as converted_carts,
                SUM(c.total_amount) as total_cart_value,
                SUM(CASE WHEN t.transaction_id IS NULL THEN c.total_amount ELSE 0 END) as abandoned_value
            FROM {$wpdb->prefix}vortex_carts c
            LEFT JOIN {$wpdb->prefix}vortex_transactions t ON 
                c.user_id = t.user_id AND 
                t.transaction_time >= c.last_updated AND
                t.transaction_time <= DATE_ADD(c.last_updated, INTERVAL 24 HOUR) AND
                t.status = 'completed'
            WHERE c.created_at >= %s",
            $time_constraint
        );
        
        $cart_stats = $wpdb->get_row($query);
        
        // Calculate abandonment rate and recovery potential
        $abandonment_rate = 0;
        $avg_abandoned_value = 0;
        $recovery_potential = 0;
        
        if ($cart_stats) {
            if ($cart_stats->total_carts > 0) {
                $abandonment_rate = round(($cart_stats->abandoned_carts / $cart_stats->total_carts) * 100, 2);
            }
            
            if ($cart_stats->abandoned_carts > 0) {
                $avg_abandoned_value = round($cart_stats->abandoned_value / $cart_stats->abandoned_carts, 2);
            }
            
            $recovery_potential = round($cart_stats->abandoned_value * 0.25, 2); // Assume 25% recovery rate
        }
        
        // Get abandonment by price range
        $price_ranges = array(
            array('min' => 0, 'max' => 50, 'label' => 'Under $50'),
            array('min' => 50, 'max' => 100, 'label' => '$50-$100'),
            array('min' => 100, 'max' => 250, 'label' => '$100-$250'),
            array('min' => 250, 'max' => 500, 'label' => '$250-$500'),
            array('min' => 500, 'max' => 1000, 'label' => '$500-$1000'),
            array('min' => 1000, 'max' => PHP_INT_MAX, 'label' => 'Over $1000')
        );
        
        $price_range_stats = array();
        
        foreach ($price_ranges as $range) {
            $range_query = $wpdb->prepare(
                "SELECT 
                    COUNT(DISTINCT c.cart_id) as total_carts,
                    COUNT(DISTINCT CASE WHEN t.transaction_id IS NULL THEN c.cart_id ELSE NULL END) as abandoned_carts
                FROM {$wpdb->prefix}vortex_carts c
                JOIN {$wpdb->prefix}vortex_cart_items i ON c.cart_id = i.cart_id
                JOIN {$wpdb->prefix}vortex_artworks a ON i.artwork_id = a.artwork_id
                LEFT JOIN {$wpdb->prefix}vortex_transactions t ON 
                    c.user_id = t.user_id AND 
                    t.transaction_time >= c.last_updated AND
                    t.transaction_time <= DATE_ADD(c.last_updated, INTERVAL 24 HOUR) AND
                    t.status = 'completed'
                WHERE c.created_at >= %s
                AND a.price > %f AND a.price <= %f
                GROUP BY a.price > %f AND a.price <= %f",
                $time_constraint,
                $range['min'],
                $range['max'],
                $range['min'],
                $range['max']
            );
            
            $range_stats = $wpdb->get_row($range_query);
            
            // Calculate abandonment rate for this price range
            $range_abandonment_rate = 0;
            if ($range_stats && $range_stats->total_carts > 0) {
                $range_abandonment_rate = round(($range_stats->abandoned_carts / $range_stats->total_carts) * 100, 2);
            }
            
            $price_range_stats[] = array(
                'range' => $range['label'],
                'total_carts' => $range_stats ? $range_stats->total_carts : 0,
                'abandoned_carts' => $range_stats ? $range_stats->abandoned_carts : 0,
                'abandonment_rate' => $range_abandonment_rate
            );
        }
        
        // Get top reasons for abandonment (from user feedback and exit surveys)
        $reasons_query = $wpdb->prepare(
            "SELECT 
                abandonment_reason,
                COUNT(*) as count
            FROM {$wpdb->prefix}vortex_cart_abandonment_feedback
            WHERE feedback_time >= %s
            GROUP BY abandonment_reason
            ORDER BY count DESC
            LIMIT 5",
            $time_constraint
        );
        
        $abandonment_reasons = $wpdb->get_results($reasons_query);
        
        return array(
            'total_carts' => $cart_stats ? $cart_stats->total_carts : 0,
            'abandoned_carts' => $cart_stats ? $cart_stats->abandoned_carts : 0,
            'converted_carts' => $cart_stats ? $cart_stats->converted_carts : 0,
            'total_cart_value' => $cart_stats ? $cart_stats->total_cart_value : 0,
            'abandoned_value' => $cart_stats ? $cart_stats->abandoned_value : 0,
            'abandonment_rate' => $abandonment_rate,
            'avg_abandoned_value' => $avg_abandoned_value,
            'recovery_potential' => $recovery_potential,
            'price_range_stats' => $price_range_stats,
            'abandonment_reasons' => $abandonment_reasons
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get abandoned cart stats: ' . $e->getMessage());
        return array(
            'total_carts' => 0,
            'abandoned_carts' => 0,
            'converted_carts' => 0,
            'total_cart_value' => 0,
            'abandoned_value' => 0,
            'abandonment_rate' => 0,
            'avg_abandoned_value' => 0,
            'recovery_potential' => 0,
            'price_range_stats' => array(),
            'abandonment_reasons' => array()
        );
    }
}

/**
 * Get price sensitivity data
 * 
 * @param string $period Time period for analysis
 * @return array Price sensitivity data
 */
private function get_price_sensitivity_data($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        // Define price ranges for analysis
        $price_ranges = array(
            array('min' => 0, 'max' => 50, 'label' => 'Under $50'),
            array('min' => 50, 'max' => 100, 'label' => '$50-$100'),
            array('min' => 100, 'max' => 250, 'label' => '$100-$250'),
            array('min' => 250, 'max' => 500, 'label' => '$250-$500'),
            array('min' => 500, 'max' => 1000, 'label' => '$500-$1000'),
            array('min' => 1000, 'max' => PHP_INT_MAX, 'label' => 'Over $1000')
        );
        
        $price_sensitivity = array();
        
        foreach ($price_ranges as $range) {
            $range_query = $wpdb->prepare(
                "SELECT 
                    COUNT(DISTINCT v.view_id) as view_count,
                    COUNT(DISTINCT l.like_id) as like_count,
                    COUNT(DISTINCT c.id) as cart_add_count,
                    COUNT(DISTINCT t.transaction_id) as purchase_count,
                    COALESCE(SUM(t.amount), 0) as total_revenue
                FROM {$wpdb->prefix}vortex_artworks a
                LEFT JOIN {$wpdb->prefix}vortex_artwork_views v ON 
                    a.artwork_id = v.artwork_id AND
                    v.view_time >= %s
                LEFT JOIN {$wpdb->prefix}vortex_artwork_likes l ON 
                    a.artwork_id = l.artwork_id AND
                    l.like_time >= %s
                LEFT JOIN {$wpdb->prefix}vortex_cart_items c ON 
                    a.artwork_id = c.artwork_id AND
                    c.added_time >= %s
                LEFT JOIN {$wpdb->prefix}vortex_transactions t ON 
                    a.artwork_id = t.artwork_id AND
                    t.transaction_time >= %s AND
                    t.status = 'completed'
                WHERE a.price > %f AND a.price <= %f",
                $time_constraint,
                $time_constraint,
                $time_constraint,
                $time_constraint,
                $range['min'],
                $range['max']
            );
            
            $range_stats = $wpdb->get_row($range_query);
            
            // Calculate engagement metrics for this price range
            $view_to_like_rate = 0;
            $like_to_cart_rate = 0;
            $cart_to_purchase_rate = 0;
            $view_to_purchase_rate = 0;
            
            if ($range_stats) {
                if ($range_stats->view_count > 0) {
                    $view_to_like_rate = round(($range_stats->like_count / $range_stats->view_count) * 100, 2);
                    $view_to_purchase_rate = round(($range_stats->purchase_count / $range_stats->view_count) * 100, 2);
                }
                
                if ($range_stats->like_count > 0) {
                    $like_to_cart_rate = round(($range_stats->cart_add_count / $range_stats->like_count) * 100, 2);
                }
                
                if ($range_stats->cart_add_count > 0) {
                    $cart_to_purchase_rate = round(($range_stats->purchase_count / $range_stats->cart_add_count) * 100, 2);
                }
            }
            
            $price_sensitivity[] = array(
                'range' => $range['label'],
                'view_count' => $range_stats ? $range_stats->view_count : 0,
                'like_count' => $range_stats ? $range_stats->like_count : 0,
                'cart_add_count' => $range_stats ? $range_stats->cart_add_count : 0,
                'purchase_count' => $range_stats ? $range_stats->purchase_count : 0,
                'total_revenue' => $range_stats ? $range_stats->total_revenue : 0,
                'view_to_like_rate' => $view_to_like_rate,
                'like_to_cart_rate' => $like_to_cart_rate,
                'cart_to_purchase_rate' => $cart_to_purchase_rate,
                'view_to_purchase_rate' => $view_to_purchase_rate
            );
        }
        
        // Get optimal price points (where conversion rate is highest) by category
        $category_query = $wpdb->prepare(
            "SELECT 
                c.category_id,
                c.category_name,
                AVG(a.price) as avg_price,
                ROUND(AVG(a.price), -1) as price_bracket,
                COUNT(DISTINCT v.view_id) as view_count,
                COUNT(DISTINCT t.transaction_id) as purchase_count,
                CASE 
                    WHEN COUNT(DISTINCT v.view_id) > 0 
                    THEN (COUNT(DISTINCT t.transaction_id) / COUNT(DISTINCT v.view_id)) * 100
                    ELSE 0
                END as conversion_rate
            FROM {$wpdb->prefix}vortex_categories c
            JOIN {$wpdb->prefix}vortex_artworks a ON c.category_id = a.category_id
            LEFT JOIN {$wpdb->prefix}vortex_artwork_views v ON 
                a.artwork_id = v.artwork_id AND
                v.view_time >= %s
            LEFT JOIN {$wpdb->prefix}vortex_transactions t ON 
                a.artwork_id = t.artwork_id AND
                t.transaction_time >= %s AND
                t.status = 'completed'
            GROUP BY c.category_id, ROUND(a.price, -1)
            HAVING COUNT(DISTINCT v.view_id) > 20
            ORDER BY c.category_name, conversion_rate DESC",
            $time_constraint,
            $time_constraint
        );
        
        $category_results = $wpdb->get_results($category_query);
        
        // Process to find optimal price points per category
        $optimal_prices = array();
        $current_category = null;
        
        foreach ($category_results as $result) {
            if ($current_category !== $result->category_id) {
                $current_category = $result->category_id;
                $optimal_prices[$result->category_id] = array(
                    'category_name' => $result->category_name,
                    'optimal_price' => $result->price_bracket,
                    'conversion_rate' => $result->conversion_rate
                );
            }
        }
        
        return array(
            'price_sensitivity_by_range' => $price_sensitivity,
            'optimal_price_points' => array_values($optimal_prices)
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get price sensitivity data: ' . $e->getMessage());
        return array(
            'price_sensitivity_by_range' => array(),
            'optimal_price_points' => array()
        );
    }
}

/**
 * Get top performing keywords
 * 
 * @param string $period Time period for analysis
 * @return array Top performing keywords data
 */
private function get_top_performing_keywords($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        // Get top search keywords by search volume
        $search_query = $wpdb->prepare(
            "SELECT 
                search_term,
                COUNT(*) as search_count,
                COUNT(DISTINCT user_id) as unique_searchers,
                AVG(result_count) as avg_results,
                SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) as conversions,
                CASE 
                    WHEN COUNT(*) > 0 
                    THEN (SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100
                    ELSE 0
                END as conversion_rate
            FROM {$wpdb->prefix}vortex_searches
            WHERE search_time >= %s
            GROUP BY search_term
            HAVING COUNT(*) > 5
            ORDER BY search_count DESC
            LIMIT 20",
            $time_constraint
        );
        
        $top_search_terms = $wpdb->get_results($search_query);
        
        // Get top converting keywords
        $conversion_query = $wpdb->prepare(
            "SELECT 
                search_term,
                COUNT(*) as search_count,
                SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) as conversions,
                CASE 
                    WHEN COUNT(*) > 0 
                    THEN (SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100
                    ELSE 0
                END as conversion_rate
            FROM {$wpdb->prefix}vortex_searches
            WHERE search_time >= %s
            GROUP BY search_term
            HAVING COUNT(*) > 5 AND SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) > 0
            ORDER BY conversion_rate DESC, conversions DESC
            LIMIT 20",
            $time_constraint
        );
        
        $top_converting_terms = $wpdb->get_results($conversion_query);
        
        // Get keywords associated with highest revenue
        $revenue_query = $wpdb->prepare(
            "SELECT 
                s.search_term,
                COUNT(DISTINCT t.transaction_id) as purchases,
                SUM(t.amount) as total_revenue,
                SUM(t.amount) / COUNT(DISTINCT t.transaction_id) as avg_order_value
            FROM {$wpdb->prefix}vortex_searches s
            JOIN {$wpdb->prefix}vortex_search_transactions st ON s.search_id = st.search_id
            JOIN {$wpdb->prefix}vortex_transactions t ON st.transaction_id = t.transaction_id
            WHERE s.search_time >= %s
            AND t.status = 'completed'
            GROUP BY s.search_term
            HAVING COUNT(DISTINCT t.transaction_id) > 2
            ORDER BY total_revenue DESC
            LIMIT 20",
            $time_constraint
        );
        
        $top_revenue_terms = $wpdb->get_results($revenue_query);
        
        // Get keywords with low results but high search volume (content gap opportunities)
        $opportunity_query = $wpdb->prepare(
            "SELECT 
                search_term,
                COUNT(*) as search_count,
                AVG(result_count) as avg_results
            FROM {$wpdb->prefix}vortex_searches
            WHERE search_time >= %s
            GROUP BY search_term
            HAVING COUNT(*) > 10 AND AVG(result_count) < 5
            ORDER BY search_count DESC
            LIMIT 20",
            $time_constraint
        );
        
        $opportunity_terms = $wpdb->get_results($opportunity_query);
        
        return array(
            'top_search_terms' => $top_search_terms,
            'top_converting_terms' => $top_converting_terms,
            'top_revenue_terms' => $top_revenue_terms,
            'opportunity_terms' => $opportunity_terms
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get top performing keywords: ' . $e->getMessage());
        return array(
            'top_search_terms' => array(),
            'top_converting_terms' => array(),
            'top_revenue_terms' => array(),
            'opportunity_terms' => array()
        );
    }
}

/**
 * Get abandoned cart statistics
 * 
 * @param string $period Time period for analysis
 * @return array Abandoned cart data
 */
private function get_abandoned_cart_stats($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        // Get overall abandoned cart stats
        $query = $wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT c.cart_id) as total_carts,
                COUNT(DISTINCT CASE WHEN t.transaction_id IS NULL THEN c.cart_id ELSE NULL END) as abandoned_carts,
                COUNT(DISTINCT CASE WHEN t.transaction_id IS NOT NULL THEN c.cart_id ELSE NULL END) as converted_carts,
                SUM(c.total_amount) as total_cart_value,
                SUM(CASE WHEN t.transaction_id IS NULL THEN c.total_amount ELSE 0 END) as abandoned_value
            FROM {$wpdb->prefix}vortex_carts c
            LEFT JOIN {$wpdb->prefix}vortex_transactions t ON 
                c.user_id = t.user_id AND 
                t.transaction_time >= c.last_updated AND
                t.transaction_time <= DATE_ADD(c.last_updated, INTERVAL 24 HOUR) AND
                t.status = 'completed'
            WHERE c.created_at >= %s",
            $time_constraint
        );
        
        $cart_stats = $wpdb->get_row($query);
        
        // Calculate abandonment rate and recovery potential
        $abandonment_rate = 0;
        $avg_abandoned_value = 0;
        $recovery_potential = 0;
        
        if ($cart_stats) {
            if ($cart_stats->total_carts > 0) {
                $abandonment_rate = round(($cart_stats->abandoned_carts / $cart_stats->total_carts) * 100, 2);
            }
            
            if ($cart_stats->abandoned_carts > 0) {
                $avg_abandoned_value = round($cart_stats->abandoned_value / $cart_stats->abandoned_carts, 2);
            }
            
            $recovery_potential = round($cart_stats->abandoned_value * 0.25, 2); // Assume 25% recovery rate
        }
        
        // Get abandonment by price range
        $price_ranges = array(
            array('min' => 0, 'max' => 50, 'label' => 'Under $50'),
            array('min' => 50, 'max' => 100, 'label' => '$50-$100'),
            array('min' => 100, 'max' => 250, 'label' => '$100-$250'),
            array('min' => 250, 'max' => 500, 'label' => '$250-$500'),
            array('min' => 500, 'max' => 1000, 'label' => '$500-$1000'),
            array('min' => 1000, 'max' => PHP_INT_MAX, 'label' => 'Over $1000')
        );
        
        $price_range_stats = array();
        
        foreach ($price_ranges as $range) {
            $range_query = $wpdb->prepare(
                "SELECT 
                    COUNT(DISTINCT c.cart_id) as total_carts,
                    COUNT(DISTINCT CASE WHEN t.transaction_id IS NULL THEN c.cart_id ELSE NULL END) as abandoned_carts
                FROM {$wpdb->prefix}vortex_carts c
                JOIN {$wpdb->prefix}vortex_cart_items i ON c.cart_id = i.cart_id
                JOIN {$wpdb->prefix}vortex_artworks a ON i.artwork_id = a.artwork_id
                LEFT JOIN {$wpdb->prefix}vortex_transactions t ON 
                    c.user_id = t.user_id AND 
                    t.transaction_time >= c.last_updated AND
                    t.transaction_time <= DATE_ADD(c.last_updated, INTERVAL 24 HOUR) AND
                    t.status = 'completed'
                WHERE c.created_at >= %s
                AND a.price > %f AND a.price <= %f
                GROUP BY a.price > %f AND a.price <= %f",
                $time_constraint,
                $range['min'],
                $range['max'],
                $range['min'],
                $range['max']
            );
            
            $range_stats = $wpdb->get_row($range_query);
            
            // Calculate abandonment rate for this price range
            $range_abandonment_rate = 0;
            if ($range_stats && $range_stats->total_carts > 0) {
                $range_abandonment_rate = round(($range_stats->abandoned_carts / $range_stats->total_carts) * 100, 2);
            }
            
            $price_range_stats[] = array(
                'range' => $range['label'],
                'total_carts' => $range_stats ? $range_stats->total_carts : 0,
                'abandoned_carts' => $range_stats ? $range_stats->abandoned_carts : 0,
                'abandonment_rate' => $range_abandonment_rate
            );
        }
        
        // Get top reasons for abandonment (from user feedback and exit surveys)
        $reasons_query = $wpdb->prepare(
            "SELECT 
                abandonment_reason,
                COUNT(*) as count
            FROM {$wpdb->prefix}vortex_cart_abandonment_feedback
            WHERE feedback_time >= %s
            GROUP BY abandonment_reason
            ORDER BY count DESC
            LIMIT 5",
            $time_constraint
        );
        
        $abandonment_reasons = $wpdb->get_results($reasons_query);
        
        return array(
            'total_carts' => $cart_stats ? $cart_stats->total_carts : 0,
            'abandoned_carts' => $cart_stats ? $cart_stats->abandoned_carts : 0,
            'converted_carts' => $cart_stats ? $cart_stats->converted_carts : 0,
            'total_cart_value' => $cart_stats ? $cart_stats->total_cart_value : 0,
            'abandoned_value' => $cart_stats ? $cart_stats->abandoned_value : 0,
            'abandonment_rate' => $abandonment_rate,
            'avg_abandoned_value' => $avg_abandoned_value,
            'recovery_potential' => $recovery_potential,
            'price_range_stats' => $price_range_stats,
            'abandonment_reasons' => $abandonment_reasons
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get abandoned cart stats: ' . $e->getMessage());
        return array(
            'total_carts' => 0,
            'abandoned_carts' => 0,
            'converted_carts' => 0,
            'total_cart_value' => 0,
            'abandoned_value' => 0,
            'abandonment_rate' => 0,
            'avg_abandoned_value' => 0,
            'recovery_potential' => 0,
            'price_range_stats' => array(),
            'abandonment_reasons' => array()
        );
    }
}

/**
 * Get price sensitivity data
 * 
 * @param string $period Time period for analysis
 * @return array Price sensitivity data
 */
private function get_price_sensitivity_data($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        // Define price ranges for analysis
        $price_ranges = array(
            array('min' => 0, 'max' => 50, 'label' => 'Under $50'),
            array('min' => 50, 'max' => 100, 'label' => '$50-$100'),
            array('min' => 100, 'max' => 250, 'label' => '$100-$250'),
            array('min' => 250, 'max' => 500, 'label' => '$250-$500'),
            array('min' => 500, 'max' => 1000, 'label' => '$500-$1000'),
            array('min' => 1000, 'max' => PHP_INT_MAX, 'label' => 'Over $1000')
        );
        
        $price_sensitivity = array();
        
        foreach ($price_ranges as $range) {
            $range_query = $wpdb->prepare(
                "SELECT 
                    COUNT(DISTINCT v.view_id) as view_count,
                    COUNT(DISTINCT l.like_id) as like_count,
                    COUNT(DISTINCT c.id) as cart_add_count,
                    COUNT(DISTINCT t.transaction_id) as purchase_count,
                    COALESCE(SUM(t.amount), 0) as total_revenue
                FROM {$wpdb->prefix}vortex_artworks a
                LEFT JOIN {$wpdb->prefix}vortex_artwork_views v ON 
                    a.artwork_id = v.artwork_id AND
                    v.view_time >= %s
                LEFT JOIN {$wpdb->prefix}vortex_artwork_likes l ON 
                    a.artwork_id = l.artwork_id AND
                    l.like_time >= %s
                LEFT JOIN {$wpdb->prefix}vortex_cart_items c ON 
                    a.artwork_id = c.artwork_id AND
                    c.added_time >= %s
                LEFT JOIN {$wpdb->prefix}vortex_transactions t ON 
                    a.artwork_id = t.artwork_id AND
                    t.transaction_time >= %s AND
                    t.status = 'completed'
                WHERE a.price > %f AND a.price <= %f",
                $time_constraint,
                $time_constraint,
                $time_constraint,
                $time_constraint,
                $range['min'],
                $range['max']
            );
            
            $range_stats = $wpdb->get_row($range_query);
            
            // Calculate engagement metrics for this price range
            $view_to_like_rate = 0;
            $like_to_cart_rate = 0;
            $cart_to_purchase_rate = 0;
            $view_to_purchase_rate = 0;
            
            if ($range_stats) {
                if ($range_stats->view_count > 0) {
                    $view_to_like_rate = round(($range_stats->like_count / $range_stats->view_count) * 100, 2);
                    $view_to_purchase_rate = round(($range_stats->purchase_count / $range_stats->view_count) * 100, 2);
                }
                
                if ($range_stats->like_count > 0) {
                    $like_to_cart_rate = round(($range_stats->cart_add_count / $range_stats->like_count) * 100, 2);
                }
                
                if ($range_stats->cart_add_count > 0) {
                    $cart_to_purchase_rate = round(($range_stats->purchase_count / $range_stats->cart_add_count) * 100, 2);
                }
            }
            
            $price_sensitivity[] = array(
                'range' => $range['label'],
                'view_count' => $range_stats ? $range_stats->view_count : 0,
                'like_count' => $range_stats ? $range_stats->like_count : 0,
                'cart_add_count' => $range_stats ? $range_stats->cart_add_count : 0,
                'purchase_count' => $range_stats ? $range_stats->purchase_count : 0,
                'total_revenue' => $range_stats ? $range_stats->total_revenue : 0,
                'view_to_like_rate' => $view_to_like_rate,
                'like_to_cart_rate' => $like_to_cart_rate,
                'cart_to_purchase_rate' => $cart_to_purchase_rate,
                'view_to_purchase_rate' => $view_to_purchase_rate
            );
        }
        
        // Get optimal price points (where conversion rate is highest) by category
        $category_query = $wpdb->prepare(
            "SELECT 
                c.category_id,
                c.category_name,
                AVG(a.price) as avg_price,
                ROUND(AVG(a.price), -1) as price_bracket,
                COUNT(DISTINCT v.view_id) as view_count,
                COUNT(DISTINCT t.transaction_id) as purchase_count,
                CASE 
                    WHEN COUNT(DISTINCT v.view_id) > 0 
                    THEN (COUNT(DISTINCT t.transaction_id) / COUNT(DISTINCT v.view_id)) * 100
                    ELSE 0
                END as conversion_rate
            FROM {$wpdb->prefix}vortex_categories c
            JOIN {$wpdb->prefix}vortex_artworks a ON c.category_id = a.category_id
            LEFT JOIN {$wpdb->prefix}vortex_artwork_views v ON 
                a.artwork_id = v.artwork_id AND
                v.view_time >= %s
            LEFT JOIN {$wpdb->prefix}vortex_transactions t ON 
                a.artwork_id = t.artwork_id AND
                t.transaction_time >= %s AND
                t.status = 'completed'
            GROUP BY c.category_id, ROUND(a.price, -1)
            HAVING COUNT(DISTINCT v.view_id) > 20
            ORDER BY c.category_name, conversion_rate DESC",
            $time_constraint,
            $time_constraint
        );
        
        $category_results = $wpdb->get_results($category_query);
        
        // Process to find optimal price points per category
        $optimal_prices = array();
        $current_category = null;
        
        foreach ($category_results as $result) {
            if ($current_category !== $result->category_id) {
                $current_category = $result->category_id;
                $optimal_prices[$result->category_id] = array(
                    'category_name' => $result->category_name,
                    'optimal_price' => $result->price_bracket,
                    'conversion_rate' => $result->conversion_rate
                );
            }
        }
        
        return array(
            'price_sensitivity_by_range' => $price_sensitivity,
            'optimal_price_points' => array_values($optimal_prices)
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get price sensitivity data: ' . $e->getMessage());
        return array(
            'price_sensitivity_by_range' => array(),
            'optimal_price_points' => array()
        );
    }
}

/**
 * Get trending search terms
 * 
 * @param string $period Time period for analysis
 * @return array Trending search terms data
 */
private function get_trending_search_terms($period = 'month') {
    try {
        global $wpdb;
        
        $current_period = $this->get_time_constraint($period);
        $previous_period = $this->get_time_constraint($period, true);
        
        // Get trending search terms (highest growth in searches)
        $query = $wpdb->prepare(
            "SELECT 
                search_term,
                COUNT(CASE WHEN search_time >= %s THEN 1 ELSE NULL END) as current_period_searches,
                COUNT(CASE WHEN search_time >= %s AND search_time < %s THEN 1 ELSE NULL END) as previous_period_searches
            FROM {$wpdb->prefix}vortex_searches
            WHERE search_time >= %s
            GROUP BY search_term
            HAVING current_period_searches > 5 AND previous_period_searches > 0
            ORDER BY (current_period_searches - previous_period_searches) DESC
            LIMIT 30",
            $current_period,
            $previous_period,
            $current_period,
            $previous_period
        );
        
        $trending_terms = $wpdb->get_results($query);
        
        // Calculate growth rates and add to results
        $processed_terms = array();
        foreach ($trending_terms as $term) {
            $growth_rate = 0;
            if ($term->previous_period_searches > 0) {
                $growth_rate = round((($term->current_period_searches - $term->previous_period_searches) / $term->previous_period_searches) * 100, 2);
            }
            
            $processed_terms[] = array(
                'term' => $term->search_term,
                'current_searches' => $term->current_period_searches,
                'previous_searches' => $term->previous_period_searches,
                'growth_rate' => $growth_rate
            );
        }
        
        // Find new trending terms (not present in previous period)
        $new_query = $wpdb->prepare(
            "SELECT 
                s1.search_term,
                COUNT(*) as search_count
            FROM {$wpdb->prefix}vortex_searches s1
            WHERE s1.search_time >= %s
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->prefix}vortex_searches s2
                WHERE s2.search_term = s1.search_term
                AND s2.search_time >= %s AND s2.search_time < %s
            )
            GROUP BY s1.search_term
            HAVING COUNT(*) > 3
            ORDER BY search_count DESC
            LIMIT 20",
            $current_period,
            $previous_period,
            $current_period
        );
        
        $new_trending_terms = $wpdb->get_results($new_query);
        
        // Get trending terms by category
        $category_query = $wpdb->prepare(
            "SELECT 
                c.category_id,
                c.category_name,
                s.search_term,
                COUNT(*) as search_count
            FROM {$wpdb->prefix}vortex_searches s
            JOIN {$wpdb->prefix}vortex_search_artwork_clicks sac ON s.search_id = sac.search_id
            JOIN {$wpdb->prefix}vortex_artworks a ON sac.artwork_id = a.artwork_id
            JOIN {$wpdb->prefix}vortex_categories c ON a.category_id = c.category_id
            WHERE s.search_time >= %s
            GROUP BY c.category_id, s.search_term
            ORDER BY c.category_name, search_count DESC",
            $current_period
        );
        
        $category_trends = $wpdb->get_results($category_query);
        
        // Process category-specific trending terms
        $trending_by_category = array();
        $current_category = null;
        $category_terms = array();
        
        foreach ($category_trends as $trend) {
            if ($current_category !== $trend->category_id) {
                // Save previous category terms if they exist
                if ($current_category !== null && !empty($category_terms)) {
                    $trending_by_category[] = array(
                        'category_id' => $current_category,
                        'category_name' => $category_name,
                        'terms' => $category_terms
                    );
                }
                
                // Start new category
                $current_category = $trend->category_id;
                $category_name = $trend->category_name;
                $category_terms = array();
            }
            
            // Add term to current category (limit to top 5 per category)
            if (count($category_terms) < 5) {
                $category_terms[] = array(
                    'term' => $trend->search_term,
                    'search_count' => $trend->search_count
                );
            }
        }
        
        // Add the last category if it exists
        if ($current_category !== null && !empty($category_terms)) {
            $trending_by_category[] = array(
                'category_id' => $current_category,
                'category_name' => $category_name,
                'terms' => $category_terms
            );
        }
        
        return array(
            'trending_terms' => $processed_terms,
            'new_trending_terms' => $new_trending_terms,
            'trending_by_category' => $trending_by_category
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get trending search terms: ' . $e->getMessage());
        return array(
            'trending_terms' => array(),
            'new_trending_terms' => array(),
            'trending_by_category' => array()
        );
    }
}

/**
 * Generate optimal tags for artwork
 * 
 * @param int $artwork_id Optional artwork ID to generate tags for
 * @param string $period Time period for analysis
 * @return array Optimal tags data
 */
private function generate_optimal_tags($artwork_id = null, $period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        // Get artwork details if specified
        $artwork_data = null;
        if ($artwork_id) {
            $artwork_query = $wpdb->prepare(
                "SELECT 
                    a.title,
                    a.description,
                    s.style_name,
                    c.category_name,
                    GROUP_CONCAT(DISTINCT t.tag_name SEPARATOR ',') as existing_tags
                FROM {$wpdb->prefix}vortex_artworks a
                LEFT JOIN {$wpdb->prefix}vortex_art_styles s ON a.style_id = s.style_id
                LEFT JOIN {$wpdb->prefix}vortex_categories c ON a.category_id = c.category_id
                LEFT JOIN {$wpdb->prefix}vortex_artwork_tags at ON a.artwork_id = at.artwork_id
                LEFT JOIN {$wpdb->prefix}vortex_tags t ON at.tag_id = t.tag_id
                WHERE a.artwork_id = %d
                GROUP BY a.artwork_id",
                $artwork_id
            );
            
            $artwork_data = $wpdb->get_row($artwork_query);
        }
        
        // Get top converting search terms
        $converting_terms_query = $wpdb->prepare(
            "SELECT 
                search_term,
                COUNT(*) as search_count,
                SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) as conversions,
                (SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as conversion_rate
            FROM {$wpdb->prefix}vortex_searches
            WHERE search_time >= %s
            GROUP BY search_term
            HAVING COUNT(*) > 5 AND (SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 > 1
            ORDER BY conversion_rate DESC, conversions DESC
            LIMIT 100",
            $time_constraint
        );
        
        $converting_terms = $wpdb->get_results($converting_terms_query);
        
        // Get trending tags
        $trending_tags_query = $wpdb->prepare(
            "SELECT 
                t.tag_name,
                COUNT(DISTINCT at.artwork_id) as artwork_count,
                COUNT(DISTINCT v.view_id) as view_count,
                COUNT(DISTINCT tr.transaction_id) as transaction_count
            FROM {$wpdb->prefix}vortex_tags t
            JOIN {$wpdb->prefix}vortex_artwork_tags at ON t.tag_id = at.tag_id
            LEFT JOIN {$wpdb->prefix}vortex_artwork_views v ON 
                at.artwork_id = v.artwork_id AND 
                v.view_time >= %s
            LEFT JOIN {$wpdb->prefix}vortex_transactions tr ON 
                at.artwork_id = tr.artwork_id AND 
                tr.transaction_time >= %s AND
                tr.status = 'completed'
            GROUP BY t.tag_id
            HAVING COUNT(DISTINCT v.view_id) > 10
            ORDER BY transaction_count DESC, view_count DESC
            LIMIT 50",
            $time_constraint,
            $time_constraint
        );
        
        $trending_tags = $wpdb->get_results($trending_tags_query);
        
        // Generate optimal tags
        $optimal_tags = array();
        
        // If we have specific artwork data, generate personalized tags
        if ($artwork_data) {
            // Extract keywords from artwork title and description
            $artwork_keywords = $this->extract_keywords($artwork_data->title . ' ' . $artwork_data->description);
            
            // Add style and category as tags
            $base_tags = array(
                $artwork_data->style_name,
                $artwork_data->category_name
            );
            
            // Add existing tags
            $existing_tags = $artwork_data->existing_tags ? explode(',', $artwork_data->existing_tags) : array();
            
            // Combine all potential tags
            $all_potential_tags = array_merge($base_tags, $artwork_keywords, $existing_tags);
            
            // Find matching converting search terms
            $matching_converting_terms = array();
            foreach ($converting_terms as $term) {
                foreach ($all_potential_tags as $tag) {
                    if (stripos($term->search_term, $tag) !== false || stripos($tag, $term->search_term) !== false) {
                        $matching_converting_terms[] = $term->search_term;
                        break;
                    }
                }
            }
            
            // Filter trending tags relevant to this artwork
            $relevant_trending_tags = array();
            foreach ($trending_tags as $tag) {
                foreach ($all_potential_tags as $artwork_tag) {
                    if (stripos($tag->tag_name, $artwork_tag) !== false || stripos($artwork_tag, $tag->tag_name) !== false) {
                        $relevant_trending_tags[] = $tag->tag_name;
                        break;
                    }
                }
            }
            
            // Combine and deduplicate tags
            $optimal_tags = array_unique(array_merge($base_tags, $matching_converting_terms, $relevant_trending_tags));
            
            // Limit to top 15 tags
            $optimal_tags = array_slice($optimal_tags, 0, 15);
            
            return array(
                'artwork_id' => $artwork_id,
                'existing_tags' => $existing_tags,
                'optimal_tags' => $optimal_tags,
                'base_tags' => $base_tags,
                'converting_terms' => $matching_converting_terms,
                'trending_tags' => $relevant_trending_tags
            );
        } else {
            // Return general tag recommendations
            $optimal_tags = array(
                'top_converting_terms' => array_column($converting_terms, 'search_term'),
                'trending_tags' => array_column($trending_tags, 'tag_name')
            );
            
            return $optimal_tags;
        }
    } catch (Exception $e) {
        $this->log_error('Failed to generate optimal tags: ' . $e->getMessage());
        return array(
            'artwork_id' => $artwork_id,
            'existing_tags' => array(),
            'optimal_tags' => array(),
            'error' => $e->getMessage()
        );
    }
}

/**
 * Extract keywords from text
 * 
 * @param string $text Text to extract keywords from
 * @return array Keywords
 */
private function extract_keywords($text) {
    // Simple keyword extraction by removing common words
    $stop_words = array(
        'a', 'an', 'the', 'and', 'or', 'but', 'of', 'in', 'on', 'at', 'to', 'for', 
        'with', 'by', 'about', 'like', 'from', 'as', 'into', 'than', 'after', 'before', 
        'when', 'if', 'this', 'that', 'these', 'those', 'it', 'its', 'is', 'are', 'was', 
        'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'not'
    );
    
    // Convert to lowercase and remove punctuation
    $text = strtolower($text);
    $text = preg_replace('/[^\w\s]/', ' ', $text);
    
    // Split into words
    $words = preg_split('/\s+/', $text);
    
    // Filter out stop words and short words
    $keywords = array();
    foreach ($words as $word) {
        if (strlen($word) > 3 && !in_array($word, $stop_words)) {
            $keywords[] = $word;
        }
    }
    
    // Return unique keywords
    return array_unique($keywords);
}

/**
 * Get popular styles based on various metrics
 * 
 * @param string $period Time period for analysis
 * @return array Popular styles data with success/error status
 */
private function get_popular_styles($period = 'month') {
    try {
        global $wpdb;
        
        $time_constraint = $this->get_time_constraint($period);
        
        // Get styles ranked by different metrics
        $query = $wpdb->prepare(
            "SELECT 
                s.style_id,
                s.style_name,
                COUNT(DISTINCT a.artwork_id) as artwork_count,
                COUNT(DISTINCT v.view_id) as view_count,
                COUNT(DISTINCT l.like_id) as like_count,
                COUNT(DISTINCT t.transaction_id) as purchase_count,
                COALESCE(SUM(t.amount), 0) as sales_value,
                CASE 
                    WHEN COUNT(DISTINCT v.view_id) > 0 
                    THEN COUNT(DISTINCT t.transaction_id) / COUNT(DISTINCT v.view_id)
                    ELSE 0
                END as conversion_rate
            FROM {$wpdb->prefix}vortex_art_styles s
            JOIN {$wpdb->prefix}vortex_artworks a ON s.style_id = a.style_id
            LEFT JOIN {$wpdb->prefix}vortex_artwork_views v ON 
                a.artwork_id = v.artwork_id AND 
                v.view_time >= %s
            LEFT JOIN {$wpdb->prefix}vortex_artwork_likes l ON 
                a.artwork_id = l.artwork_id AND 
                l.like_time >= %s
            LEFT JOIN {$wpdb->prefix}vortex_transactions t ON 
                a.artwork_id = t.artwork_id AND 
                t.transaction_time >= %s AND
                t.status = 'completed'
            GROUP BY s.style_id
            HAVING artwork_count > 0",
            $time_constraint,
            $time_constraint,
            $time_constraint
        );
        
        $styles = $wpdb->get_results($query);
        
        // Calculate engagement scores and organize by different metrics
        $processed_styles = array();
        $by_views = array();
        $by_likes = array();
        $by_purchases = array();
        $by_conversion = array();
        $by_revenue = array();
        
        foreach ($styles as $style) {
            // Calculate engagement score (weighted combination of metrics)
            $view_weight = 1;
            $like_weight = 5;
            $purchase_weight = 20;
            
            $engagement_score = 
                ($style->view_count * $view_weight) + 
                ($style->like_count * $like_weight) + 
                ($style->purchase_count * $purchase_weight);
            
            $processed_style = array(
                'style_id' => $style->style_id,
                'style_name' => $style->style_name,
                'artwork_count' => $style->artwork_count,
                'view_count' => $style->view_count,
                'like_count' => $style->like_count,
                'purchase_count' => $style->purchase_count,
                'sales_value' => round($style->sales_value, 2),
                'conversion_rate' => round($style->conversion_rate * 100, 2),
                'engagement_score' => $engagement_score
            );
            
            $processed_styles[] = $processed_style;
            
            // Add to specific metric arrays
            $by_views[] = array(
                'style_id' => $style->style_id,
                'style_name' => $style->style_name,
                'value' => $style->view_count
            );
            
            $by_likes[] = array(
                'style_id' => $style->style_id,
                'style_name' => $style->style_name,
                'value' => $style->like_count
            );
            
            $by_purchases[] = array(
                'style_id' => $style->style_id,
                'style_name' => $style->style_name,
                'value' => $style->purchase_count
            );
            
            $by_conversion[] = array(
                'style_id' => $style->style_id,
                'style_name' => $style->style_name,
                'value' => round($style->conversion_rate * 100, 2)
            );
            
            $by_revenue[] = array(
                'style_id' => $style->style_id,
                'style_name' => $style->style_name,
                'value' => round($style->sales_value, 2)
            );
        }
        
        // Sort by engagement score
        usort($processed_styles, function($a, $b) {
            return $b['engagement_score'] <=> $a['engagement_score'];
        });
        
        // Sort by specific metrics
        usort($by_views, function($a, $b) {
            return $b['value'] <=> $a['value'];
        });
        
        usort($by_likes, function($a, $b) {
            return $b['value'] <=> $a['value'];
        });
        
        usort($by_purchases, function($a, $b) {
            return $b['value'] <=> $a['value'];
        });
        
        usort($by_conversion, function($a, $b) {
            return $b['value'] <=> $a['value'];
        });
        
        usort($by_revenue, function($a, $b) {
            return $b['value'] <=> $a['value'];
        });
        
        // Limit each category to top 10
        $by_views = array_slice($by_views, 0, 10);
        $by_likes = array_slice($by_likes, 0, 10);
        $by_purchases = array_slice($by_purchases, 0, 10);
        $by_conversion = array_slice($by_conversion, 0, 10);
        $by_revenue = array_slice($by_revenue, 0, 10);
        
        return array(
            'status' => 'success',
            'data' => array(
                'all_styles' => $processed_styles,
                'by_views' => $by_views,
                'by_likes' => $by_likes,
                'by_purchases' => $by_purchases,
                'by_conversion' => $by_conversion,
                'by_revenue' => $by_revenue
            )
        );
    } catch (Exception $e) {
        $this->log_error('Failed to get popular styles: ' . $e->getMessage());
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}