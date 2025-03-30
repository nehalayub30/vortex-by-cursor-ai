<?php
/**
 * VORTEX AI Learning Pipeline
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_AI_Learning Class
 * 
 * Manages the deep learning pipeline for all AI agents
 *
 * @since 1.0.0
 */
class VORTEX_AI_Learning {
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * Learning models by agent
     */
    private $agent_models = [];
    
    /**
     * Learning contexts
     */
    private $learning_contexts = [];
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize agent models
        $this->initialize_agent_models();
        
        // Initialize learning contexts
        $this->initialize_learning_contexts();
        
        // Set up hooks
        $this->setup_hooks();
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
    
    /**
     * Initialize agent models
     */
    private function initialize_agent_models() {
        $this->agent_models = [
            'HURAII' => [
                'visual_analysis' => [
                    'model_path' => VORTEX_PLUGIN_PATH . 'models/huraii/visual_analysis.model',
                    'batch_size' => 32,
                    'learning_rate' => 0.001,
                    'momentum' => 0.9,
                    'dropout_rate' => 0.2,
                    'last_trained' => get_option('vortex_huraii_visual_analysis_trained', 0),
                    'cross_agent_learning' => ['CLOE', 'BusinessStrategist']
                ],
                'seed_art' => [
                    'model_path' => VORTEX_PLUGIN_PATH . 'models/huraii/seed_art.model',
                    'batch_size' => 16,
                    'learning_rate' => 0.0005,
                    'momentum' => 0.9,
                    'dropout_rate' => 0.2,
                    'last_trained' => get_option('vortex_huraii_seed_art_trained', 0),
                    'cross_agent_learning' => ['CLOE']
                ],
                'layer_detection' => [
                    'model_path' => VORTEX_PLUGIN_PATH . 'models/huraii/layer_detection.model',
                    'batch_size' => 64,
                    'learning_rate' => 0.002,
                    'momentum' => 0.9,
                    'dropout_rate' => 0.2,
                    'last_trained' => get_option('vortex_huraii_layer_detection_trained', 0),
                    'cross_agent_learning' => ['CLOE']
                ]
            ],
            'CLOE' => [
                'curation' => [
                    'model_path' => VORTEX_PLUGIN_PATH . 'models/cloe/curation.model',
                    'batch_size' => 48,
                    'learning_rate' => 0.001,
                    'momentum' => 0.9,
                    'dropout_rate' => 0.2,
                    'last_trained' => get_option('vortex_cloe_curation_trained', 0),
                    'cross_agent_learning' => ['HURAII', 'BusinessStrategist']
                ],
                'user_preferences' => [
                    'model_path' => VORTEX_PLUGIN_PATH . 'models/cloe/user_preferences.model',
                    'batch_size' => 64,
                    'learning_rate' => 0.002,
                    'momentum' => 0.9,
                    'dropout_rate' => 0.2,
                    'last_trained' => get_option('vortex_cloe_user_preferences_trained', 0),
                    'cross_agent_learning' => ['BusinessStrategist']
                ]
            ],
            'BusinessStrategist' => [
                'market_analysis' => [
                    'model_path' => VORTEX_PLUGIN_PATH . 'models/business/market_analysis.model',
                    'batch_size' => 32,
                    'learning_rate' => 0.001,
                    'momentum' => 0.9,
                    'dropout_rate' => 0.2,
                    'last_trained' => get_option('vortex_business_market_analysis_trained', 0),
                    'cross_agent_learning' => ['HURAII', 'CLOE']
                ],
                'token_economics' => [
                    'model_path' => VORTEX_PLUGIN_PATH . 'models/business/token_economics.model',
                    'batch_size' => 48,
                    'learning_rate' => 0.0005,
                    'momentum' => 0.9,
                    'dropout_rate' => 0.2,
                    'last_trained' => get_option('vortex_business_token_economics_trained', 0),
                    'cross_agent_learning' => ['CLOE']
                ]
            ]
        ];
    }
    
    /**
     * Initialize learning contexts
     */
    private function initialize_learning_contexts() {
        $this->learning_contexts = [
            'artwork_generation' => [
                'priority' => 'high',
                'data_sources' => ['user_prompts', 'artwork_metadata', 'generation_settings'],
                'related_agents' => ['HURAII', 'CLOE']
            ],
            'marketplace_activity' => [
                'priority' => 'high',
                'data_sources' => ['sales_data', 'user_browsing', 'wallet_activity'],
                'related_agents' => ['BusinessStrategist', 'CLOE']
            ],
            'user_interaction' => [
                'priority' => 'medium',
                'data_sources' => ['user_clicks', 'time_spent', 'favorites'],
                'related_agents' => ['CLOE', 'BusinessStrategist']
            ],
            'token_transactions' => [
                'priority' => 'medium',
                'data_sources' => ['token_transfers', 'swap_data', 'price_movements'],
                'related_agents' => ['BusinessStrategist']
            ],
            'artist_activity' => [
                'priority' => 'medium',
                'data_sources' => ['creation_data', 'style_evolution', 'popularity_metrics'],
                'related_agents' => ['HURAII', 'BusinessStrategist']
            ],
            'communication' => [
                'priority' => 'low',
                'data_sources' => ['agent_messages', 'response_effectiveness'],
                'related_agents' => ['HURAII', 'CLOE', 'BusinessStrategist']
            ]
        ];
    }
    
    /**
     * Set up hooks
     */
    private function setup_hooks() {
        // Learning hooks
        add_action('vortex_ai_agent_learn', [$this, 'process_learning_event'], 10, 3);
        add_action('vortex_train_ai_models', [$this, 'train_models']);
        
        // Schedule regular training
        if (!wp_next_scheduled('vortex_train_ai_models')) {
            wp_schedule_event(time(), 'daily', 'vortex_train_ai_models');
        }
    }
    
    /**
     * Process learning event for AI agent with adaptive learning
     */
    public function process_learning_event($agent_name, $context_type, $data) {
        // Validate agent and context
        if (!isset($this->agent_models[$agent_name]) || !isset($this->learning_contexts[$context_type])) {
            return false;
        }
        
        // Ensure agent should learn from this context
        if (!in_array($agent_name, $this->learning_contexts[$context_type]['related_agents'])) {
            return false;
        }
        
        // Store learning data
        $learning_data = [
            'agent' => $agent_name,
            'context' => $context_type,
            'data' => $data,
            'timestamp' => isset($data['timestamp']) ? $data['timestamp'] : current_time('timestamp')
        ];
        
        // Store in database for batch training
        $this->store_learning_data($learning_data);
        
        // Adaptive learning based on context priority and data quality
        $priority = $this->learning_contexts[$context_type]['priority'];
        $data_quality = $this->assess_data_quality($data);
        
        if ($priority === 'high' || $data_quality > 0.8) {
            $this->immediate_learning($agent_name, $context_type, $data);
            
            // Trigger cross-agent learning
            $this->trigger_cross_agent_learning($agent_name, $context_type, $data);
        }
        
        return true;
    }
    
    /**
     * Trigger cross-agent learning
     */
    private function trigger_cross_agent_learning($source_agent, $context_type, $data) {
        foreach ($this->agent_models[$source_agent] as $model_type => $config) {
            if (isset($config['cross_agent_learning'])) {
                foreach ($config['cross_agent_learning'] as $target_agent) {
                    if (isset($this->agent_models[$target_agent])) {
                        $this->process_cross_agent_learning($source_agent, $target_agent, $model_type, $data);
                    }
                }
            }
        }
    }
    
    /**
     * Process cross-agent learning
     */
    private function process_cross_agent_learning($source_agent, $target_agent, $model_type, $data) {
        // Transform data for target agent
        $transformed_data = $this->transform_data_for_agent($data, $source_agent, $target_agent);
        
        // Process learning for target agent
        $this->immediate_learning($target_agent, $model_type, $transformed_data);
    }
    
    /**
     * Assess data quality for learning
     */
    private function assess_data_quality($data) {
        $quality_score = 0;
        
        // Check data completeness
        if (isset($data['completeness'])) {
            $quality_score += $data['completeness'] * 0.4;
        }
        
        // Check data accuracy
        if (isset($data['accuracy'])) {
            $quality_score += $data['accuracy'] * 0.3;
        }
        
        // Check data relevance
        if (isset($data['relevance'])) {
            $quality_score += $data['relevance'] * 0.3;
        }
        
        return min(1, max(0, $quality_score));
    }
    
    /**
     * Train AI models based on collected data
     */
    public function train_models() {
        foreach ($this->agent_models as $agent_name => $models) {
            foreach ($models as $model_type => $config) {
                // Check if training is due (at least 24 hours since last training)
                $last_trained = $config['last_trained'];
                $now = time();
                
                if (($now - $last_trained) < 24 * HOUR_IN_SECONDS) {
                    continue;
                }
                
                // Get training data for this model
                $training_data = $this->get_training_data($agent_name, $model_type);
                
                if (empty($training_data)) {
                    continue;
                }
                
                // Train the model
                $training_result = $this->train_model(
                    $agent_name,
                    $model_type,
                    $config['model_path'],
                    $training_data,
                    $config['batch_size'],
                    $config['learning_rate']
                );
                
                // Update last trained timestamp
                if ($training_result) {
                    update_option('vortex_' . strtolower($agent_name) . '_' . $model_type . '_trained', $now);
                }
            }
        }
    }
    
    /**
     * Get training data for model
     */
    private function get_training_data($agent_name, $model_type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_ai_learning_data';
        
        // Get relevant contexts for this model
        $relevant_contexts = $this->get_relevant_contexts($agent_name, $model_type);
        
        if (empty($relevant_contexts)) {
            return [];
        }
        
        // Format contexts for SQL
        $context_placeholders = implode(',', array_fill(0, count($relevant_contexts), '%s'));
        $query_args = array_merge([$agent_name], $relevant_contexts);
        
        // Get data from last 30 days
        $thirty_days_ago = time() - (30 * DAY_IN_SECONDS);
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE agent = %s 
            AND context IN ($context_placeholders)
            AND timestamp > %d
            ORDER BY timestamp DESC
            LIMIT 10000",
            array_merge($query_args, [$thirty_days_ago])
        );
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        return $results ?: [];
    }
    
    /**
     * Train model with data
     */
    private function train_model($agent_name, $model_type, $model_path, $training_data, $batch_size, $learning_rate) {
        // Model training would be implemented with actual ML libraries
        // This is a placeholder showing the expected functionality
        
        // Log training start
        error_log("Starting training for $agent_name $model_type model with " . count($training_data) . " samples");
        
        // Simulate successful training
        return true;
    }
}

// Initialize AI Learning
VORTEX_AI_Learning::get_instance(); 