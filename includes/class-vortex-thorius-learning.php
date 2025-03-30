<?php
/**
 * Thorius Learning System
 * 
 * Collects feedback and interaction data to improve AI responses over time
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Learning System
 */
class Vortex_Thorius_Learning {
    /**
     * Database table for feedback
     */
    private $feedback_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->feedback_table = $wpdb->prefix . 'vortex_thorius_feedback';
        
        // Register feedback collection points
        add_action('wp_ajax_vortex_thorius_feedback', array($this, 'ajax_collect_feedback'));
        add_action('wp_ajax_nopriv_vortex_thorius_feedback', array($this, 'ajax_collect_feedback'));
        
        // Periodically analyze feedback to improve responses
        add_action('vortex_thorius_analyze_feedback', array($this, 'analyze_feedback_data'));
        
        // Schedule feedback analysis if not already scheduled
        if (!wp_next_scheduled('vortex_thorius_analyze_feedback')) {
            wp_schedule_event(time(), 'daily', 'vortex_thorius_analyze_feedback');
        }
    }
    
    /**
     * AJAX handler for collecting user feedback
     */
    public function ajax_collect_feedback() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_thorius_feedback_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
            exit;
        }
        
        $conversation_id = isset($_POST['conversation_id']) ? sanitize_text_field($_POST['conversation_id']) : '';
        $query_id = isset($_POST['query_id']) ? sanitize_text_field($_POST['query_id']) : '';
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $feedback_text = isset($_POST['feedback']) ? sanitize_textarea_field($_POST['feedback']) : '';
        $agent = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : '';
        
        // Store feedback
        $result = $this->store_feedback(array(
            'conversation_id' => $conversation_id,
            'query_id' => $query_id,
            'rating' => $rating,
            'feedback' => $feedback_text,
            'agent' => $agent,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ));
        
        if ($result) {
            wp_send_json_success(array('message' => __('Thank you for your feedback!', 'vortex-ai-marketplace')));
        } else {
            wp_send_json_error(array('message' => __('Unable to save feedback. Please try again.', 'vortex-ai-marketplace')));
        }
        
        exit;
    }
    
    /**
     * Store feedback in database
     * 
     * @param array $data Feedback data
     * @return bool Success status
     */
    private function store_feedback($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->feedback_table,
            $data,
            array(
                '%s', // conversation_id
                '%s', // query_id
                '%d', // rating
                '%s', // feedback
                '%s', // agent
                '%d', // user_id
                '%s'  // timestamp
            )
        );
        
        return $result !== false;
    }
    
    /**
     * Analyze feedback data and generate improvement suggestions
     */
    public function analyze_feedback_data() {
        global $wpdb;
        
        // Get feedback data from last 30 days
        $feedback = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->feedback_table} WHERE timestamp > %s",
                date('Y-m-d H:i:s', strtotime('-30 days'))
            )
        );
        
        if (empty($feedback)) {
            return;
        }
        
        // Analyze by agent
        $agent_ratings = array();
        $agent_feedback = array();
        
        foreach ($feedback as $item) {
            if (!isset($agent_ratings[$item->agent])) {
                $agent_ratings[$item->agent] = array();
                $agent_feedback[$item->agent] = array();
            }
            
            $agent_ratings[$item->agent][] = $item->rating;
            if (!empty($item->feedback)) {
                $agent_feedback[$item->agent][] = $item->feedback;
            }
        }
        
        // Generate report
        $report = array(
            'timestamp' => current_time('mysql'),
            'total_feedback' => count($feedback),
            'agents' => array()
        );
        
        foreach ($agent_ratings as $agent => $ratings) {
            $avg_rating = array_sum($ratings) / count($ratings);
            
            $report['agents'][$agent] = array(
                'average_rating' => round($avg_rating, 2),
                'total_ratings' => count($ratings),
                'improvement_areas' => $this->identify_improvement_areas($agent_feedback[$agent] ?? array()),
                'trend' => $this->calculate_rating_trend($agent)
            );
        }
        
        // Store report
        update_option('vortex_thorius_feedback_report', $report);
        
        // Notify admins if needed
        if (get_option('vortex_thorius_notify_feedback_reports', false)) {
            $this->notify_admins_of_report($report);
        }
    }
    
    /**
     * Identify common themes in negative feedback
     * 
     * @param array $feedback Array of feedback strings
     * @return array Identified improvement areas
     */
    private function identify_improvement_areas($feedback) {
        // This would be a more sophisticated NLP analysis in a real implementation
        // Simplified implementation for demonstration
        $areas = array();
        $keywords = array(
            'slow' => 'Response speed',
            'incorrect' => 'Accuracy',
            'inaccurate' => 'Accuracy',
            'not helpful' => 'Usefulness',
            'confusing' => 'Clarity',
            'unclear' => 'Clarity',
            'irrelevant' => 'Relevance',
            'off-topic' => 'Relevance'
        );
        
        foreach ($feedback as $text) {
            $text = strtolower($text);
            
            foreach ($keywords as $key => $area) {
                if (strpos($text, $key) !== false && !in_array($area, $areas)) {
                    $areas[] = $area;
                }
            }
        }
        
        return $areas;
    }
    
    /**
     * Calculate rating trend compared to previous period
     * 
     * @param string $agent Agent ID
     * @return string Trend (up, down, or stable)
     */
    private function calculate_rating_trend($agent) {
        global $wpdb;
        
        // Get average rating for current period (last 30 days)
        $current_avg = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(rating) FROM {$this->feedback_table} 
                WHERE agent = %s AND timestamp > %s",
                $agent,
                date('Y-m-d H:i:s', strtotime('-30 days'))
            )
        );
        
        // Get average rating for previous period (30-60 days ago)
        $previous_avg = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(rating) FROM {$this->feedback_table} 
                WHERE agent = %s AND timestamp BETWEEN %s AND %s",
                $agent,
                date('Y-m-d H:i:s', strtotime('-60 days')),
                date('Y-m-d H:i:s', strtotime('-30 days'))
            )
        );
        
        if (!$previous_avg) {
            return 'stable';
        }
        
        $difference = $current_avg - $previous_avg;
        
        if ($difference > 0.2) {
            return 'up';
        } else if ($difference < -0.2) {
            return 'down';
        } else {
            return 'stable';
        }
    }
    
    /**
     * Notify admins of feedback report
     * 
     * @param array $report Feedback analysis report
     */
    private function notify_admins_of_report($report) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(__('[%s] Thorius AI Feedback Analysis Report', 'vortex-ai-marketplace'), $site_name);
        
        $message = __('Thorius AI Feedback Analysis Report', 'vortex-ai-marketplace') . "\n\n";
        $message .= __('Total Feedback Collected:', 'vortex-ai-marketplace') . ' ' . $report['total_feedback'] . "\n\n";
        
        foreach ($report['agents'] as $agent => $data) {
            $message .= sprintf(
                __('Agent: %s | Average Rating: %s/5 | Trend: %s', 'vortex-ai-marketplace'),
                strtoupper($agent),
                $data['average_rating'],
                $data['trend']
            ) . "\n";
            
            if (!empty($data['improvement_areas'])) {
                $message .= __('Improvement Areas:', 'vortex-ai-marketplace') . ' ' . implode(', ', $data['improvement_areas']) . "\n";
            }
            
            $message .= "\n";
        }
        
        $message .= __('View the full report in your admin dashboard.', 'vortex-ai-marketplace') . "\n";
        $message .= admin_url('admin.php?page=vortex-thorius-analytics&tab=feedback');
        
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Generate personalized suggestions based on user interaction history
     * 
     * @param int $user_id User ID
     * @return array Personalized suggestions
     */
    public function generate_personalized_suggestions($user_id) {
        // Get user interaction history
        $history = $this->get_user_interaction_history($user_id, 20);
        
        if (empty($history)) {
            return array();
        }
        
        // Extract topics of interest
        $topics = $this->extract_topics_from_interactions($history);
        
        // Get top interests
        $top_interests = array_slice($topics, 0, 3);
        
        // Generate content and activity suggestions
        $suggestions = array(
            'artists' => $this->suggest_artists_by_interests($top_interests),
            'artworks' => $this->suggest_artworks_by_interests($top_interests),
            'events' => $this->suggest_events_by_interests($top_interests, $this->get_user_location($user_id)),
            'collections' => $this->suggest_collections_by_interests($top_interests),
            'learning' => $this->suggest_learning_resources($top_interests)
        );
        
        // Generate AI assistant conversations
        $suggestions['conversations'] = $this->generate_conversation_starters($top_interests);
        
        return $suggestions;
    }

    /**
     * Analyze user's language patterns to personalize AI responses
     * 
     * @param int $user_id User ID
     * @return array Language patterns
     */
    public function analyze_language_patterns($user_id) {
        // Get user messages
        $messages = $this->get_user_messages($user_id, 50);
        
        if (empty($messages)) {
            return array();
        }
        
        // Analyze formality level
        $formality = $this->analyze_formality($messages);
        
        // Analyze typical sentence length
        $sentence_length = $this->analyze_sentence_length($messages);
        
        // Analyze vocabulary richness
        $vocabulary = $this->analyze_vocabulary_richness($messages);
        
        // Analyze emoji/emoticon usage
        $emoji_usage = $this->analyze_emoji_usage($messages);
        
        return array(
            'formality' => $formality,
            'sentence_length' => $sentence_length,
            'vocabulary_richness' => $vocabulary,
            'emoji_usage' => $emoji_usage
        );
    }

    /**
     * Prepare parameter changes for adaptation
     * 
     * @param string $agent Agent ID
     * @param string $adaptation_type Adaptation type
     * @param array $metrics Performance metrics
     * @return array Parameter changes
     */
    private function prepare_parameter_changes($agent, $adaptation_type, $metrics) {
        $changes = array();
        
        switch ($adaptation_type) {
            case 'fine_tuning':
                $changes = array(
                    'learning_rate' => 0.0002,
                    'epochs' => 3,
                    'batch_size' => 8,
                    'model_base' => $this->determine_best_base_model($agent, $metrics),
                    'optimization_focus' => $this->determine_optimization_focus($metrics)
                );
                break;
                
            case 'prompt_optimization':
                $current_prompts = $this->get_agent_prompts($agent);
                $prompt_issues = $this->identify_prompt_issues($agent, $metrics);
                
                $changes = array(
                    'system_prompt' => $this->optimize_system_prompt($current_prompts['system'], $prompt_issues),
                    'context_format' => $this->optimize_context_format($current_prompts['context'], $prompt_issues),
                    'query_preprocessing' => $current_prompts['preprocessing'],
                    'response_formatting' => $this->optimize_response_formatting($current_prompts['formatting'], $prompt_issues)
                );
                break;
                
            case 'threshold_adjustment':
                $changes = array(
                    'confidence_threshold' => $this->calculate_optimal_confidence_threshold($agent, $metrics),
                    'creativity_level' => $this->calculate_optimal_creativity_level($agent, $metrics),
                    'response_length_factor' => $this->calculate_optimal_response_length($agent, $metrics),
                    'context_relevance_threshold' => $this->calculate_optimal_context_relevance($agent, $metrics)
                );
                break;
                
            case 'knowledge_expansion':
                $changes = array(
                    'knowledge_areas' => $this->identify_knowledge_gaps($agent),
                    'synthetic_examples' => $this->generate_synthetic_examples($agent),
                    'external_resources' => $this->identify_helpful_resources($agent),
                    'knowledge_fusion_strategy' => 'hierarchical_integration'
                );
                break;
        }
        
        return $changes;
    }

    /**
     * Execute fine-tuning adaptation
     * 
     * @param string $agent Agent ID
     * @param array $params Adaptation parameters
     * @return bool Success status
     */
    private function execute_fine_tuning($agent, $params) {
        // Prepare fine-tuning dataset in JSONL format
        $training_file = $this->prepare_fine_tuning_file($agent, $params['training_data']);
        
        // Submit fine-tuning job to API
        $fine_tuning_job = $this->api_manager->create_fine_tuning_job(array(
            'training_file' => $training_file,
            'model' => $params['changes']['model_base'],
            'hyperparameters' => array(
                'n_epochs' => $params['changes']['epochs'],
                'learning_rate_multiplier' => $params['changes']['learning_rate']
            ),
            'suffix' => $agent . '_' . date('Ymd')
        ));
        
        if (is_wp_error($fine_tuning_job)) {
            throw new Exception($fine_tuning_job->get_error_message());
        }
        
        // Store job ID for tracking
        update_option("thorius_{$agent}_fine_tuning_job", $fine_tuning_job);
        
        // Start polling for job status
        wp_schedule_single_event(time() + 300, 'thorius_check_fine_tuning_job', array($agent, $fine_tuning_job, 0));
        
        return true;
    }

    /**
     * Execute prompt optimization adaptation
     * 
     * @param string $agent Agent ID
     * @param array $params Adaptation parameters
     * @return bool Success status
     */
    private function execute_prompt_optimization($agent, $params) {
        // Store updated prompts
        update_option("thorius_{$agent}_system_prompt", $params['changes']['system_prompt']);
        update_option("thorius_{$agent}_context_format", $params['changes']['context_format']);
        update_option("thorius_{$agent}_response_formatting", $params['changes']['response_formatting']);
        
        // Test new prompts against sample queries
        $sample_queries = $this->get_sample_queries($agent, 5);
        $results = array();
        
        foreach ($sample_queries as $query) {
            // Test with new prompts
            $result = $this->test_prompt_with_query($agent, $params['changes'], $query);
            $results[] = $result;
        }
        
        // Calculate effectiveness score
        $effectiveness = $this->calculate_prompt_effectiveness($results);
        
        // Store effectiveness data
        update_option("thorius_{$agent}_prompt_effectiveness", $effectiveness);
        
        return true;
    }

    /**
     * Execute threshold adjustment adaptation
     * 
     * @param string $agent Agent ID
     * @param array $params Adaptation parameters
     * @return bool Success status
     */
    private function execute_threshold_adjustment($agent, $params) {
        // Update thresholds for agent
        $current_thresholds = get_option("thorius_{$agent}_thresholds", array());
        $new_thresholds = array_merge($current_thresholds, $params['changes']);
        
        // Store updated thresholds
        update_option("thorius_{$agent}_thresholds", $new_thresholds);
        
        return true;
    }

    /**
     * Execute knowledge expansion adaptation
     * 
     * @param string $agent Agent ID
     * @param array $params Adaptation parameters
     * @return bool Success status
     */
    private function execute_knowledge_expansion($agent, $params) {
        // Store expanded knowledge areas
        $knowledge_areas = get_option("thorius_{$agent}_knowledge_areas", array());
        $expanded_areas = array_merge($knowledge_areas, $params['changes']['knowledge_areas']);
        
        update_option("thorius_{$agent}_knowledge_areas", $expanded_areas);
        
        // Store synthetic examples
        $examples = get_option("thorius_{$agent}_synthetic_examples", array());
        $expanded_examples = array_merge($examples, $params['changes']['synthetic_examples']);
        
        update_option("thorius_{$agent}_synthetic_examples", $expanded_examples);
        
        // Prepare knowledge fusion instructions
        $fusion_instructions = $this->prepare_knowledge_fusion_instructions(
            $agent,
            $params['changes']['knowledge_areas'],
            $params['changes']['knowledge_fusion_strategy']
        );
        
        update_option("thorius_{$agent}_knowledge_fusion", $fusion_instructions);
        
        return true;
    }

    /**
     * Measure impact of adaptation
     * 
     * @param string $agent Agent ID
     * @param int $adaptation_id Adaptation ID
     * @param array $params Adaptation parameters
     * @return array Performance metrics after adaptation
     */
    private function measure_adaptation_impact($agent, $adaptation_id, $params) {
        // Test agent performance after adaptation
        $test_scenarios = $this->get_test_scenarios($agent, $params['type']);
        $performance = array();
        
        // Process test scenarios
        foreach ($test_scenarios as $scenario) {
            $result = $this->process_test_scenario($agent, $scenario);
            $scenario_metrics = $this->calculate_scenario_metrics($scenario, $result);
            
            foreach ($scenario_metrics as $key => $value) {
                if (!isset($performance[$key])) {
                    $performance[$key] = array();
                }
                $performance[$key][] = $value;
            }
        }
        
        // Calculate averages for metrics
        foreach ($performance as $key => $values) {
            $performance[$key] = array_sum($values) / count($values);
        }
        
        // Calculate improvement over previous metrics
        $improvements = array();
        foreach ($params['performance_before'] as $key => $value) {
            if (isset($performance[$key])) {
                $improvements[$key] = $performance[$key] - $value;
            }
        }
        
        // Add improvements to performance data
        $performance['improvements'] = $improvements;
        $performance['test_count'] = count($test_scenarios);
        $performance['measured_at'] = current_time('mysql');
        
        return $performance;
    }

    /**
     * Get test scenarios for measuring adaptation impact
     * 
     * @param string $agent Agent ID
     * @param string $adaptation_type Adaptation type
     * @return array Test scenarios
     */
    private function get_test_scenarios($agent, $adaptation_type) {
        // Get a mix of scenarios based on adaptation type
        global $wpdb;
        
        $scenarios = array();
        
        // Add some recent interactions as test scenarios
        $recent_interactions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->interaction_table} 
                 WHERE agent = %s 
                 ORDER BY id DESC 
                 LIMIT 10",
                $agent
            ),
            ARRAY_A
        );
        
        foreach ($recent_interactions as $interaction) {
            $scenarios[] = array(
                'type' => 'historical',
                'query' => $interaction['query'],
                'expected_response' => $interaction['response'],
                'context' => unserialize($interaction['context_data'])
            );
        }
        
        // Add scenarios from feedback
        $feedback_interactions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT i.*, f.rating, f.feedback_text 
                 FROM {$this->interaction_table} i
                 JOIN {$this->feedback_table} f ON i.id = f.interaction_id
                 WHERE i.agent = %s AND f.rating <= 3
                 ORDER BY f.created_at DESC
                 LIMIT 5",
                $agent
            ),
            ARRAY_A
        );
        
        foreach ($feedback_interactions as $interaction) {
            $scenarios[] = array(
                'type' => 'feedback',
                'query' => $interaction['query'],
                'previous_response' => $interaction['response'],
                'feedback' => $interaction['feedback_text'],
                'rating' => $interaction['rating'],
                'context' => unserialize($interaction['context_data'])
            );
        }
        
        // Add specific scenarios based on adaptation type
        switch ($adaptation_type) {
            case 'knowledge_expansion':
                // Add scenarios testing new knowledge areas
                $knowledge_areas = get_option("thorius_{$agent}_knowledge_areas", array());
                
                foreach ($knowledge_areas as $area) {
                    $scenarios[] = array(
                        'type' => 'knowledge_test',
                        'query' => $this->generate_knowledge_test_query($area),
                        'knowledge_area' => $area,
                        'context' => array('testing_knowledge' => true)
                    );
                }
                break;
                
            case 'prompt_optimization':
                // Add scenarios testing clarity and consistency
                $scenarios[] = array(
                    'type' => 'clarity_test',
                    'query' => 'Explain this complex topic in simple terms: machine learning neural networks',
                    'context' => array('testing_clarity' => true)
                );
                
                $scenarios[] = array(
                    'type' => 'consistency_test',
                    'queries' => array(
                        'What is the best way to learn painting?',
                        'How should I start learning to paint?',
                        'I want to begin painting as a hobby, what do you recommend?'
                    ),
                    'context' => array('testing_consistency' => true)
                );
                break;
        }
        
        return $scenarios;
    }

    /**
     * Process a test scenario with the agent
     * 
     * @param string $agent Agent ID
     * @param array $scenario Test scenario
     * @return array Test result
     */
    private function process_test_scenario($agent, $scenario) {
        $result = array(
            'scenario' => $scenario,
            'responses' => array(),
            'metrics' => array()
        );
        
        // Handle multiple queries for consistency tests
        if ($scenario['type'] === 'consistency_test' && isset($scenario['queries'])) {
            foreach ($scenario['queries'] as $query) {
                $response = $this->get_agent_response($agent, $query, $scenario['context']);
                $result['responses'][] = $response;
            }
            
            // Calculate consistency across responses
            $result['metrics']['consistency'] = $this->calculate_response_consistency($result['responses']);
        } else {
            // Standard single query test
            $query = $scenario['query'];
            $start_time = microtime(true);
            $response = $this->get_agent_response($agent, $query, $scenario['context'] ?? array());
            $end_time = microtime(true);
            
            $result['responses'][] = $response;
            $result['metrics']['response_time'] = $end_time - $start_time;
            
            // Compare to expected response if available
            if (isset($scenario['expected_response'])) {
                $result['metrics']['similarity'] = $this->calculate_response_similarity(
                    $response,
                    $scenario['expected_response']
                );
            }
            
            // Calculate other metrics
            $result['metrics']['response_length'] = strlen($response);
            $result['metrics']['complexity'] = $this->calculate_response_complexity($response);
            
            // For knowledge tests, check factual accuracy
            if ($scenario['type'] === 'knowledge_test') {
                $result['metrics']['factual_accuracy'] = $this->evaluate_factual_accuracy(
                    $response,
                    $scenario['knowledge_area']
                );
            }
        }
        
        return $result;
    }

    /**
     * Get response from agent for testing
     * 
     * @param string $agent Agent ID
     * @param string $query Test query
     * @param array $context Context data
     * @return string Agent response
     */
    private function get_agent_response($agent, $query, $context = array()) {
        // Add testing flag to context
        $context['is_test'] = true;
        
        // Get orchestrator
        $orchestrator = new Vortex_Thorius_Orchestrator();
        
        // Process with specific agent
        $response_data = $orchestrator->process_with_specific_agent($agent, $query, $context);
        
        // Return response text
        return $response_data['content'] ?? '';
    }

    /**
     * Calculate metrics for test scenario
     * 
     * @param array $scenario Test scenario
     * @param array $result Test result
     * @return array Metrics
     */
    private function calculate_scenario_metrics($scenario, $result) {
        $metrics = $result['metrics'] ?? array();
        
        // Add default metrics if not present
        if (!isset($metrics['quality'])) {
            $metrics['quality'] = $this->evaluate_response_quality(
                $scenario['query'],
                $result['responses'][0]
            );
        }
        
        if (!isset($metrics['relevance'])) {
            $metrics['relevance'] = $this->evaluate_response_relevance(
                $scenario['query'],
                $result['responses'][0]
            );
        }
        
        return $metrics;
    }

    /**
     * Run weekly model training
     */
    public function run_weekly_model_training() {
        // Get all agents
        $agents = array('cloe', 'huraii', 'strategist', 'thorius');
        
        foreach ($agents as $agent) {
            // Check if agent has enough new data for training
            $has_new_data = $this->check_for_new_training_data($agent);
            
            if ($has_new_data) {
                // Prepare training parameters
                $params = $this->prepare_training_parameters($agent);
                
                // Schedule training job
                $this->schedule_training_job($agent, $params);
            }
        }
    }

    /**
     * Execute cross-agent knowledge transfer
     */
    private function execute_cross_agent_knowledge_transfer() {
        // Get performance metrics for all agents
        $agent_metrics = array();
        $agents = array('cloe', 'huraii', 'strategist', 'thorius');
        
        foreach ($agents as $agent) {
            $agent_metrics[$agent] = $this->get_agent_performance_metrics($agent);
        }
        
        // Find knowledge domains where one agent excels
        $domain_strengths = $this->identify_domain_strengths($agent_metrics);
        
        // For each domain, transfer knowledge from strongest to others
        foreach ($domain_strengths as $domain => $strongest_agent) {
            $this->transfer_domain_knowledge($strongest_agent, $domain, $agents);
        }
    }

    /**
     * Identify domains where each agent has strength
     * 
     * @param array $agent_metrics Performance metrics for agents
     * @return array Domain to strongest agent mapping
     */
    private function identify_domain_strengths($agent_metrics) {
        $domain_metrics = array();
        $domains = array('art', 'business', 'technology', 'science', 'entertainment');
        
        // Calculate domain-specific performance for each agent
        foreach ($agents as $agent) {
            foreach ($domains as $domain) {
                $domain_performance = $this->get_domain_performance($agent, $domain);
                
                if (!isset($domain_metrics[$domain])) {
                    $domain_metrics[$domain] = array();
                }
                
                $domain_metrics[$domain][$agent] = $domain_performance;
            }
        }
        
        // Find strongest agent for each domain
        $domain_strengths = array();
        
        foreach ($domain_metrics as $domain => $performances) {
            arsort($performances); // Sort by performance (highest first)
            $strongest_agent = key($performances);
            $domain_strengths[$domain] = $strongest_agent;
        }
        
        return $domain_strengths;
    }

    /**
     * Transfer domain knowledge from one agent to others
     * 
     * @param string $source_agent Source agent ID
     * @param string $domain Knowledge domain
     * @param array $target_agents Target agent IDs
     */
    private function transfer_domain_knowledge($source_agent, $domain, $target_agents) {
        // Get domain-specific dataset from source agent
        $domain_dataset_key = "thorius_{$source_agent}_{$domain}_dataset";
        $domain_dataset = get_option($domain_dataset_key, array());
        
        if (empty($domain_dataset)) {
            return;
        }
        
        // Select high-quality examples for transfer
        $transfer_examples = $this->select_knowledge_transfer_examples($domain_dataset);
        
        // For each target agent (excluding source)
        foreach ($target_agents as $target_agent) {
            if ($target_agent === $source_agent) {
                continue;
            }
            
            // Adapt examples to target agent's style
            $adapted_examples = $this->adapt_examples_to_agent_style($transfer_examples, $target_agent);
            
            // Store adapted examples in target agent's knowledge base
            $this->store_transferred_knowledge($target_agent, $domain, $adapted_examples);
        }
    }

    /**
     * Check for immediate adaptation needs based on feedback
     * 
     * @param array $interaction Interaction data
     * @param int $rating User rating
     * @param string $feedback_text Feedback text
     */
    private function trigger_immediate_adaptation($interaction, $rating, $feedback_text) {
        $agent = $interaction['agent'];
        
        // Check for critical issues in feedback
        $critical_issues = $this->detect_critical_issues($feedback_text);
        
        if (empty($critical_issues)) {
            return;
        }
        
        // Get adaptation history for this agent
        $recent_adaptations = $this->get_recent_adaptations($agent);
        
        // Don't trigger too many adaptations in short time
        if (count($recent_adaptations) >= 3) {
            return;
        }
        
        // Prepare immediate adaptation parameters
        $adaptation_params = array(
            'type' => 'immediate_correction',
            'changes' => array(
                'critical_issues' => $critical_issues,
                'example_query' => $interaction['query'],
                'problematic_response' => $interaction['response'],
                'feedback' => $feedback_text
            ),
            'training_data' => array(
                array(
                    'query' => $interaction['query'],
                    'response' => $interaction['response'],
                    'feedback' => $feedback_text,
                    'rating' => $rating
                )
            ),
            'training_data_size' => 1,
            'performance_before' => $this->get_agent_performance_metrics($agent)
        );
        
        // Execute immediate adaptation
        $this->execute_neural_adaptation($agent, $adaptation_params);
        
        // Notify admins about critical issue (if enabled)
        if (get_option('vortex_thorius_notify_critical_issues', true)) {
            $this->notify_admins_of_critical_issue($agent, $interaction, $feedback_text, $critical_issues);
        }
    }

    /**
     * Detect critical issues in feedback
     * 
     * @param string $feedback_text Feedback text
     * @return array Critical issues
     */
    private function detect_critical_issues($feedback_text) {
        $critical_issues = array();
        
        // Critical issue keywords
        $issue_patterns = array(
            'factual_error' => '/\b(wrong|incorrect|false|not true|inaccurate|error)\b.*\b(fact|information|data|statistic)\b/i',
            'harmful_content' => '/\b(harmful|inappropriate|offensive|dangerous|unsafe)\b/i',
            'bias' => '/\b(bias|biased|prejudice|unfair|discrimination)\b/i',
            'hallucination' => '/\b(made up|hallucination|fabricated|invented|doesn\'t exist)\b/i',
            'nonsensical' => '/\b(nonsense|gibberish|incoherent|doesn\'t make sense)\b/i',
            'irrelevant' => '/\b(irrelevant|unrelated|off-topic|not what I asked)\b/i'
        );
        
        foreach ($issue_patterns as $issue_type => $pattern) {
            if (preg_match($pattern, $feedback_text)) {
                $critical_issues[] = $issue_type;
            }
        }
        
        return $critical_issues;
    }

    /**
     * Get feedback statistics for an agent
     * 
     * @param string $agent Agent ID
     * @param string $since_date Since date
     * @return array Feedback statistics
     */
    private function get_feedback_statistics($agent, $since_date = null) {
        global $wpdb;
        
        $where_clauses = array(
            $wpdb->prepare("i.agent = %s", $agent)
        );
        
        if ($since_date) {
            $where_clauses[] = $wpdb->prepare("i.created_at > %s", $since_date);
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $results = $wpdb->get_results(
            "SELECT f.rating, COUNT(*) as count
            FROM {$this->feedback_table} f
            JOIN {$this->interaction_table} i ON f.interaction_id = i.id
            WHERE {$where_sql}
            GROUP BY f.rating"
        );
        
        $stats = array(
            'total' => 0,
            'positive' => 0,
            'negative' => 0,
            'neutral' => 0,
            'average' => 0,
            'ratings' => array()
        );
        
        $total_count = 0;
        $rating_sum = 0;
        
        foreach ($results as $row) {
            $rating = intval($row->rating);
            $count = intval($row->count);
            
            $stats['ratings'][$rating] = $count;
            $stats['total'] += $count;
            
            if ($rating >= 4) {
                $stats['positive'] += $count;
            } else if ($rating <= 2) {
                $stats['negative'] += $count;
            } else {
                $stats['neutral'] += $count;
            }
            
            $rating_sum += ($rating * $count);
            $total_count += $count;
        }
        
        if ($total_count > 0) {
            $stats['average'] = $rating_sum / $total_count;
        }
        
        return $stats;
    }

    /**
     * Reset the learning system to factory settings
     * 
     * @param string $agent Agent ID to reset, or 'all' for all agents
     * @return bool Success status
     */
    public function reset_learning_system($agent = 'all') {
        $agents = ($agent === 'all') ? array('cloe', 'huraii', 'strategist', 'thorius') : array($agent);
        
        foreach ($agents as $curr_agent) {
            // Reset agent-specific options
            delete_option("thorius_{$curr_agent}_system_prompt");
            delete_option("thorius_{$curr_agent}_context_format");
            delete_option("thorius_{$curr_agent}_response_formatting");
            delete_option("thorius_{$curr_agent}_thresholds");
            delete_option("thorius_{$curr_agent}_knowledge_areas");
            delete_option("thorius_{$curr_agent}_synthetic_examples");
            delete_option("thorius_{$curr_agent}_knowledge_fusion");
            delete_option("thorius_{$curr_agent}_fine_tuning_job");
            delete_option("thorius_{$curr_agent}_prompt_effectiveness");
            
            // Delete domain datasets
            $domains = array('art', 'business', 'technology', 'science', 'entertainment');
            foreach ($domains as $domain) {
                delete_option("thorius_{$curr_agent}_{$domain}_dataset");
            }
        }
        
        // Reset global adaptation thresholds
        update_option('thorius_adaptation_thresholds', $this->get_default_adaptation_thresholds());
        
        return true;
    }

    /**
     * Get default adaptation thresholds
     * 
     * @return array Default thresholds
     */
    private function get_default_adaptation_thresholds() {
        return array(
            'interaction_count' => 100,
            'feedback_quality' => 0.75,
            'confidence_improvement' => 0.15,
            'consistency_threshold' => 0.8
        );
    }

    /**
     * Get learning system status for admin dashboard
     * 
     * @return array Learning system status
     */
    public function get_learning_system_status() {
        $agents = array('cloe', 'huraii', 'strategist', 'thorius');
        $status = array(
            'total_interactions' => 0,
            'total_feedback' => 0,
            'adaptations' => 0,
            'agents' => array()
        );
        
        global $wpdb;
        
        // Get total interactions
        $status['total_interactions'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->interaction_table}");
        
        // Get total feedback
        $status['total_feedback'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->feedback_table}");
        
        // Get total adaptations
        $status['adaptations'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->evolution_table} WHERE status = 'completed'");
        
        // Get agent-specific metrics
        foreach ($agents as $agent) {
            $agent_metrics = $this->get_agent_performance_metrics($agent);
            $agent_adaptations = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->evolution_table} WHERE agent = %s AND status = 'completed'",
                    $agent
                )
            );
            
            $status['agents'][$agent] = array(
                'metrics' => $agent_metrics,
                'adaptations' => $agent_adaptations,
                'last_adaptation' => $this->get_last_adaptation_date($agent)
            );
        }
        
        return $status;
    }

    /**
     * Get the date of the last adaptation for an agent
     * 
     * @param string $agent Agent ID
     * @return string|null Last adaptation date
     */
    private function get_last_adaptation_date($agent) {
        global $wpdb;
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT completed_at FROM {$this->evolution_table} 
                WHERE agent = %s AND status = 'completed' 
                ORDER BY completed_at DESC 
                LIMIT 1",
                $agent
            )
        );
    }
} 