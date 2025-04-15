                                'description' => $suggestion['description'] ?? '',
                                'feature_type' => $suggestion['feature_type'] ?? 'general',
                                'implementation_difficulty' => $suggestion['difficulty'] ?? 'medium',
                                'user_impact' => $suggestion['user_impact'] ?? 'medium',
                                'revenue_potential' => $suggestion['revenue_potential'] ?? 'medium',
                                'confidence' => $suggestion['confidence'] ?? 70,
                                'source' => $agent,
                                'technical_requirements' => $suggestion['technical_requirements'] ?? array(),
                                'integration_steps' => $suggestion['integration_steps'] ?? array()
                            );
                        }
                    }
                }
            }
        }
        
        // Sort by confidence and then user impact
        usort($integration_suggestions, function($a, $b) {
            if ($b['confidence'] !== $a['confidence']) {
                return $b['confidence'] - $a['confidence'];
            }
            
            $impact_values = array('low' => 1, 'medium' => 2, 'high' => 3);
            $a_impact = $impact_values[$a['user_impact']] ?? 2;
            $b_impact = $impact_values[$b['user_impact']] ?? 2;
            
            return $b_impact - $a_impact;
        });
        
        return $integration_suggestions;
    }
    
    /**
     * Log an event
     */
    public function log_event($event_type, $message, $data = array()) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_agent_logs',
            array(
                'agent_name' => 'orchestrator',
                'event_type' => $event_type,
                'message' => $message,
                'data' => !empty($data) ? json_encode($data) : null,
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Register necessary AJAX actions
     */
    private function register_ajax_actions() {
        add_action('wp_ajax_vortex_get_agent_status', array($this, 'ajax_get_agent_status'));
        add_action('wp_ajax_vortex_trigger_agent_learning', array($this, 'ajax_trigger_agent_learning'));
        add_action('wp_ajax_vortex_get_agent_insights', array($this, 'ajax_get_agent_insights'));
        add_action('wp_ajax_nopriv_vortex_get_agent_insights', array($this, 'ajax_get_agent_insights'));
        
        // Admin-only actions
        add_action('wp_ajax_vortex_admin_get_learning_metrics', array($this, 'ajax_admin_get_learning_metrics'));
        add_action('wp_ajax_vortex_admin_get_agent_logs', array($this, 'ajax_admin_get_agent_logs'));
    }
    
    /**
     * AJAX handler for getting agent learning metrics (admin only)
     */
    public function ajax_admin_get_learning_metrics() {
        // Check nonce for security
        check_ajax_referer('vortex_admin_nonce', 'security');
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to access this data'));
            return;
        }
        
        global $wpdb;
        
        // Get metrics history
        $metrics_history = $wpdb->get_results("
            SELECT *
            FROM {$wpdb->prefix}vortex_learning_metrics_history
            ORDER BY created_at DESC
            LIMIT 10
        ");
        
        // Get current overall metrics
        $current_metrics = array(
            'total_artworks_analyzed' => $this->get_total_artworks_analyzed(),
            'total_insights_generated' => $this->get_total_insights_generated(),
            'learning_cycles_completed' => $this->get_learning_cycles_completed(),
            'agent_health' => $this->check_agent_health(),
            'last_learning_session' => get_option('vortex_last_learning_time', ''),
            'learning_metrics_history' => $metrics_history
        );
        
        wp_send_json_success($current_metrics);
    }
    
    /**
     * AJAX handler for getting agent logs (admin only)
     */
    public function ajax_admin_get_agent_logs() {
        // Check nonce for security
        check_ajax_referer('vortex_admin_nonce', 'security');
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to access this data'));
            return;
        }
        
        $agent = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : 'all';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        $limit = min($limit, 200); // Cap at 200 for performance
        
        global $wpdb;
        
        $query = "
            SELECT *
            FROM {$wpdb->prefix}vortex_agent_logs
        ";
        
        if ($agent !== 'all') {
            $query .= $wpdb->prepare(" WHERE agent_name = %s", $agent);
        }
        
        $query .= "
            ORDER BY created_at DESC
            LIMIT %d
        ";
        
        $logs = $wpdb->get_results($wpdb->prepare($query, $limit));
        
        wp_send_json_success(array('logs' => $logs));
    }
    
    /**
     * Set up cron jobs for learning
     */
    public function setup_cron_jobs() {
        if (!wp_next_scheduled('vortex_orchestrator_daily_learning')) {
            wp_schedule_event(time(), 'daily', 'vortex_orchestrator_daily_learning');
        }
        
        if (!wp_next_scheduled('vortex_orchestrator_weekly_deep_learning')) {
            wp_schedule_event(time(), 'weekly', 'vortex_orchestrator_weekly_deep_learning');
        }
    }
    
    /**
     * Remove cron jobs on deactivation
     */
    public static function remove_cron_jobs() {
        $timestamp = wp_next_scheduled('vortex_orchestrator_daily_learning');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'vortex_orchestrator_daily_learning');
        }
        
        $timestamp = wp_next_scheduled('vortex_orchestrator_weekly_deep_learning');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'vortex_orchestrator_weekly_deep_learning');
        }
    }
    
    /**
     * Get agent insights count
     */
    private function get_agent_insights_count($agent_name) {
        global $wpdb;
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_agent_insights WHERE agent_name = %s",
            $agent_name
        ));
    }
    
    /**
     * Get agent display name
     */
    private function get_agent_display_name($agent_name) {
        $display_names = array(
            'huraii' => 'HURAII',
            'cloe' => 'CLOE',
            'business_strategist' => 'Business Strategist',
            'thorius' => 'Thorius',
            'orchestrator' => 'AI Orchestrator'
        );
        
        return isset($display_names[$agent_name]) ? $display_names[$agent_name] : ucfirst($agent_name);
    }
    
    /**
     * Get agent color
     */
    private function get_agent_color($agent_name) {
        $colors = array(
            'huraii' => '#4e54c8',
            'cloe' => '#36b37e',
            'business_strategist' => '#ff9f43',
            'thorius' => '#ff6b6b',
            'orchestrator' => '#6c5ce7'
        );
        
        return isset($colors[$agent_name]) ? $colors[$agent_name] : '#333333';
    }
    
    /**
     * Get total artworks analyzed
     */
    private function get_total_artworks_analyzed() {
        global $wpdb;
        
        return (int) $wpdb->get_var("SELECT SUM(artworks_analyzed) FROM {$wpdb->prefix}vortex_learning_metrics_history");
    }
    
    /**
     * Get total insights generated
     */
    private function get_total_insights_generated() {
        global $wpdb;
        
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_agent_insights");
    }
    
    /**
     * Get learning cycles completed
     */
    private function get_learning_cycles_completed() {
        global $wpdb;
        
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_learning_metrics_history");
    }
    
    /**
     * Check agent health
     */
    private function check_agent_health() {
        return array(
            'huraii' => $this->huraii->get_health(),
            'cloe' => $this->cloe->get_health(),
            'business_strategist' => $this->business_strategist->get_health(),
            'thorius' => $this->thorius->get_health()
        );
    }
    
    /**
     * Trigger learning for a single agent
     */
    public function trigger_single_agent_learning($agent) {
        if ($this->learning_active) {
            return array(
                'success' => false,
                'message' => 'Learning already in progress'
            );
        }
        
        $this->learning_active = true;
        $this->log_event('learning_started', "Learning started for agent: $agent");
        
        $agent_object = null;
        switch ($agent) {
            case 'huraii':
                $agent_object = $this->huraii;
                break;
            case 'cloe':
                $agent_object = $this->cloe;
                break;
            case 'business_strategist':
                $agent_object = $this->business_strategist;
                break;
            case 'thorius':
                $agent_object = $this->thorius;
                break;
            default:
                $this->learning_active = false;
                return array(
                    'success' => false,
                    'message' => 'Invalid agent specified'
                );
        }
        
        // Get data relevant to this agent
        $artworks = $this->get_new_artworks_since_last_learning();
        $user_interactions = $this->get_user_interactions_since_last_learning();
        $market_data = $this->get_market_data_since_last_learning();
        $security_data = $this->get_security_data_since_last_learning();
        
        $start_time = microtime(true);
        
        // Run learning for the specified agent
        $result = $agent_object->learn(array(
            'artworks' => $artworks,
            'interactions' => $user_interactions,
            'market_data' => $market_data,
            'security_data' => $security_data,
            'mode' => 'single'
        ));
        
        $duration = round(microtime(true) - $start_time);
        
        // Log the result
        $this->log_event('learning_completed', "Learning completed for agent: $agent", array(
            'agent' => $agent,
            'health' => $result['health'],
            'insights_generated' => $result['insights_generated'],
            'duration_seconds' => $duration
        ));
        
        $this->learning_active = false;
        
        return array(
            'success' => true,
            'message' => "Learning completed for agent: {$this->get_agent_display_name($agent)}. Generated {$result['insights_generated']} new insights.",
            'health' => $result['health'],
            'insights_generated' => $result['insights_generated'],
            'duration_seconds' => $duration
        );
    }
    
    /**
     * Trigger learning for all agents
     */
    public function trigger_all_agents_learning() {
        if ($this->learning_active) {
            return array(
                'success' => false,
                'message' => 'Learning already in progress'
            );
        }
        
        // Run daily learning cycle
        $this->trigger_daily_learning();
        
        return array(
            'success' => true,
            'message' => 'Learning cycle completed for all agents.'
        );
    }
    
    /**
     * Agent insights shortcode
     */
    public function agent_insights_shortcode($atts) {
        $atts = shortcode_atts(array(
            'agent' => 'all',
            'insight_type' => 'latest',
            'limit' => 5
        ), $atts);
        
        // Enqueue necessary styles and scripts
        wp_enqueue_style('vortex-ai-insights');
        wp_enqueue_script('vortex-ai-insights');
        
        // Get insights
        $insights = $this->get_agent_insights_for_display(
            $atts['agent'],
            $atts['insight_type'],
            intval($atts['limit'])
        );
        
        // Start output buffer
        ob_start();
        
        // Include the template
        include VORTEX_PLUGIN_DIR . 'public/partials/vortex-agent-insights.php';
        
        return ob_get_clean();
    }
    
    /**
     * Ensure continuous deep learning for all agents
     */
    public function ensure_continuous_learning() {
        $agents = [
            'huraii' => VORTEX_HURAII::get_instance(),
            'cloe' => VORTEX_CLOE::get_instance(),
            'business_strategist' => VORTEX_Business_Strategist::get_instance(),
            'thorius' => VORTEX_THORIUS::get_instance()
        ];
        
        foreach ($agents as $agent_id => $agent) {
            if (!$agent->is_learning()) {
                $this->trigger_deep_learning($agent_id);
                error_log("VORTEX: Triggered deep learning for {$agent_id}");
            }
            
            // Check health score and retrain if below threshold
            $health = $agent->get_health_score();
            if ($health < 0.75) {
                $this->trigger_maintenance_learning($agent_id);
                error_log("VORTEX: Triggered maintenance learning for {$agent_id} due to low health score: {$health}");
            }
        }
    }
    
    /**
     * Trigger daily learning cycle
     */
    public function trigger_daily_learning() {
        // Implementation of trigger_daily_learning method
    }
    
    /**
     * Trigger weekly deep learning cycle
     */
    public function trigger_weekly_deep_learning() {
        // Implementation of trigger_weekly_deep_learning method
    }
    
    /**
     * Process integration data from external systems
     * 
     * @param string $integration_name Name of the integration
     * @param array $data Integration data
     */
    public function process_integration_data($integration_name, $data) {
        error_log("Processing integration data from: $integration_name");
        
        // Determine which agent(s) should process this data
        switch ($integration_name) {
            case 'marketplace_revenue':
            case 'marketplace_sales':
            case 'marketplace_listings':
                // Business strategic data
                $this->business_strategist->process_external_data($integration_name, $data);
                break;
                
            case 'user_activity':
            case 'social_interactions':
            case 'artist_profile_updates':
                // CLOE social/community data
                $this->cloe->process_external_data($integration_name, $data);
                break;
                
            case 'security_alerts':
            case 'access_logs':
            case 'user_flags':
                // HURAII security data
                $this->huraii->process_external_data($integration_name, $data);
                break;
                
            case 'blockchain_transactions':
            case 'smart_contract_events':
            case 'token_transfers':
                // Thorius blockchain data
                $this->thorius->process_external_data($integration_name, $data);
                break;
                
            case 'dao_governance':
            case 'voting_results':
                // Multiple agents may be interested in this data
                $this->cloe->process_external_data($integration_name, $data);
                $this->business_strategist->process_external_data($integration_name, $data);
                break;
                
            default:
                // For unknown integration types, let all agents see it
                $this->huraii->process_external_data($integration_name, $data);
                $this->cloe->process_external_data($integration_name, $data);
                $this->business_strategist->process_external_data($integration_name, $data);
                $this->thorius->process_external_data($integration_name, $data);
        }
        
        // Check if we should trigger incremental learning
        $incremental_learning_threshold = get_option('vortex_incremental_learning_threshold', 100);
        $incremental_learning_counter = get_option('vortex_incremental_learning_counter', 0);
        $incremental_learning_counter++;
        
        if ($incremental_learning_counter >= $incremental_learning_threshold) {
            // Reset counter
            update_option('vortex_incremental_learning_counter', 0);
            
            // Trigger incremental learning in a background process
            wp_schedule_single_event(time(), 'vortex_incremental_learning');
        } else {
            update_option('vortex_incremental_learning_counter', $incremental_learning_counter);
        }
    }
    
    /**
     * AJAX handler for getting agent status
     */
    public function ajax_get_agent_status() {
        // Check nonce for security
        check_ajax_referer('vortex_nonce', 'nonce');
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to access this data'));
            return;
        }
        
        $response = array(
            'learning_active' => $this->learning_active,
            'last_learning_time' => $this->last_learning_time,
            'last_learning_time_formatted' => $this->last_learning_time > 0 ? 
                human_time_diff($this->last_learning_time, time()) . ' ago' : 'Never',
            'agent_health' => $this->check_agent_health(),
            'total_insights' => array(
                'huraii' => $this->get_agent_insights_count('huraii'),
                'cloe' => $this->get_agent_insights_count('cloe'),
                'business_strategist' => $this->get_agent_insights_count('business_strategist'),
                'thorius' => $this->get_agent_insights_count('thorius'),
                'orchestrator' => $this->get_agent_insights_count('orchestrator')
            ),
            'learning_cycles_completed' => $this->get_learning_cycles_completed(),
            'total_artworks_analyzed' => $this->get_total_artworks_analyzed()
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * AJAX handler for triggering agent learning
     */
    public function ajax_trigger_agent_learning() {
        // Check nonce for security
        check_ajax_referer('vortex_nonce', 'nonce');
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to trigger learning'));
            return;
        }
        
        // Check if learning is already active
        if ($this->learning_active) {
            wp_send_json_error(array('message' => 'Learning is already in progress'));
            return;
        }
        
        $agent = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : 'all';
        
        if ($agent === 'all') {
            // Trigger learning for all agents
            wp_schedule_single_event(time(), 'vortex_daily_learning');
            wp_send_json_success(array('message' => 'Learning triggered for all agents'));
        } else {
            // Validate agent
            if (!in_array($agent, array('huraii', 'cloe', 'business_strategist', 'thorius'))) {
                wp_send_json_error(array('message' => 'Invalid agent specified'));
                return;
            }
            
            // Trigger learning for the specified agent
            $result = $this->trigger_single_agent_learning($agent);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
        }
    }
    
    /**
     * AJAX handler for getting agent insights
     */
    public function ajax_get_agent_insights() {
        // Check nonce for security
        check_ajax_referer('vortex_nonce', 'nonce');
        
        $agent = isset($_GET['agent']) ? sanitize_text_field($_GET['agent']) : 'all';
        $insight_type = isset($_GET['insight_type']) ? sanitize_text_field($_GET['insight_type']) : 'latest';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        
        // Validate agent
        if ($agent !== 'all' && !in_array($agent, array('huraii', 'cloe', 'business_strategist', 'thorius', 'orchestrator'))) {
            wp_send_json_error(array('message' => 'Invalid agent specified'));
            return;
        }
        
        // Validate insight type
        $valid_types = array('latest', 'trending', 'recommendation', 'blockchain', 'alert', 'all');
        if (!in_array($insight_type, $valid_types)) {
            $insight_type = 'latest';
        }
        
        // Get insights
        $insights = $this->get_agent_insights($agent, $insight_type, $limit, $offset);
        
        // Get insight count for pagination
        global $wpdb;
        
        $where = array("1=1");
        $where_values = array();
        
        if ($agent !== 'all') {
            $where[] = "agent_name = %s";
            $where_values[] = $agent;
        }
        
        if ($insight_type !== 'all') {
            $where[] = "insight_type = %s";
            $where_values[] = $insight_type;
        }
        
        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_agent_insights WHERE " . implode(' AND ', $where);
        $total = $wpdb->get_var($wpdb->prepare($query, $where_values));
        
        wp_send_json_success(array(
            'insights' => $insights,
            'total' => (int)$total,
            'has_more' => ($offset + $limit) < $total
        ));
    }
}

// Initialize the Orchestrator
$vortex_orchestrator = VORTEX_Orchestrator::get_instance();

// Ensure continuous learning is active
add_action('init', array($vortex_orchestrator, 'ensure_continuous_learning'));

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('VORTEX_Orchestrator', 'create_tables'));
register_activation_hook(__FILE__, array($vortex_orchestrator, 'setup_cron_jobs'));
register_deactivation_hook(__FILE__, array('VORTEX_Orchestrator', 'remove_cron_jobs')); 