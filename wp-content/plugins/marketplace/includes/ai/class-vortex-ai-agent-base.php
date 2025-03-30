<?php
/**
 * Vortex AI Agent Base Class
 *
 * Provides core functionality for all AI agents including deep learning capabilities
 *
 * @package Vortex_Marketplace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

abstract class VORTEX_AI_Agent_Base {
    /**
     * Agent identifier
     * @var string
     */
    protected $agent_id = '';
    
    /**
     * Deep learning enabled flag
     * @var bool
     */
    protected $deep_learning_enabled = false;
    
    /**
     * Continuous learning enabled flag
     * @var bool
     */
    protected $continuous_learning_enabled = false;
    
    /**
     * Cross learning enabled flag
     * @var bool
     */
    protected $cross_learning_enabled = false;
    
    /**
     * Learning rate
     * @var float
     */
    protected $learning_rate = 0.01;
    
    /**
     * Context window size
     * @var int
     */
    protected $context_window = 1000;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Set default agent ID from class name if not specified
        if (empty($this->agent_id)) {
            $class_name = get_class($this);
            $this->agent_id = strtolower(str_replace('VORTEX_', '', $class_name));
        }
        
        // Load settings from options
        $this->deep_learning_enabled = (bool) get_option("vortex_{$this->agent_id}_deep_learning", true);
        $this->continuous_learning_enabled = (bool) get_option("vortex_{$this->agent_id}_continuous_learning", true);
        $this->cross_learning_enabled = (bool) get_option("vortex_{$this->agent_id}_cross_learning", true);
        $this->learning_rate = (float) get_option("vortex_{$this->agent_id}_learning_rate", 0.01);
        $this->context_window = (int) get_option("vortex_{$this->agent_id}_context_window", 1000);
        
        // Apply global settings if set
        if (get_option('vortex_enable_all_deep_learning', '1') === '1') {
            $this->deep_learning_enabled = true;
        }
        
        if (get_option('vortex_enable_all_continuous_learning', '1') === '1') {
            $this->continuous_learning_enabled = true;
        }
        
        if (get_option('vortex_enable_all_cross_learning', '1') === '1') {
            $this->cross_learning_enabled = true;
        }
        
        // Initialize components based on settings
        $this->initialize();
    }
    
    /**
     * Initialize agent components
     */
    protected function initialize() {
        // Initialize deep learning if enabled
        if ($this->deep_learning_enabled) {
            $this->initialize_deep_learning();
        }
        
        // Initialize continuous learning if enabled
        if ($this->continuous_learning_enabled) {
            $this->initialize_continuous_learning();
        }
        
        // Initialize cross learning if enabled
        if ($this->cross_learning_enabled) {
            $this->initialize_cross_learning();
        }
    }
    
    /**
     * Initialize deep learning components
     */
    protected function initialize_deep_learning() {
        // Override in child classes
    }
    
    /**
     * Initialize continuous learning components
     */
    protected function initialize_continuous_learning() {
        // Override in child classes
    }
    
    /**
     * Initialize cross learning components
     */
    protected function initialize_cross_learning() {
        // Override in child classes
        
        // Register with knowledge hub
        if (method_exists($this, 'register_with_knowledge_hub')) {
            $this->register_with_knowledge_hub();
        }
    }
    
    /**
     * Enable or disable deep learning
     *
     * @param bool $status Whether to enable deep learning
     * @return bool Success status
     */
    public function enable_deep_learning($status = true) {
        $this->deep_learning_enabled = (bool) $status;
        update_option("vortex_{$this->agent_id}_deep_learning", $status ? '1' : '0');
        
        if ($status) {
            $this->initialize_deep_learning();
        }
        
        return true;
    }
    
    /**
     * Check if deep learning is enabled
     *
     * @return bool
     */
    public function is_deep_learning_enabled() {
        return $this->deep_learning_enabled;
    }
    
    /**
     * Set learning rate
     *
     * @param float $rate Learning rate (0.0001 to 0.1)
     * @return bool Success status
     */
    public function set_learning_rate($rate = 0.01) {
        // Validate rate
        $rate = floatval($rate);
        if ($rate < 0.0001 || $rate > 0.1) {
            return false;
        }
        
        $this->learning_rate = $rate;
        update_option("vortex_{$this->agent_id}_learning_rate", $rate);
        
        return true;
    }
    
    /**
     * Get learning rate
     *
     * @return float
     */
    public function get_learning_rate() {
        return $this->learning_rate;
    }
    
    /**
     * Enable or disable continuous learning
     *
     * @param bool $status Whether to enable continuous learning
     * @return bool Success status
     */
    public function enable_continuous_learning($status = true) {
        $this->continuous_learning_enabled = (bool) $status;
        update_option("vortex_{$this->agent_id}_continuous_learning", $status ? '1' : '0');
        
        if ($status) {
            $this->initialize_continuous_learning();
        }
        
        return true;
    }
    
    /**
     * Check if continuous learning is enabled
     *
     * @return bool
     */
    public function is_continuous_learning_enabled() {
        return $this->continuous_learning_enabled;
    }
    
    /**
     * Set context window size
     *
     * @param int $window_size Context window size (100 to 5000)
     * @return bool Success status
     */
    public function set_context_window($window_size = 1000) {
        // Validate window size
        $window_size = intval($window_size);
        if ($window_size < 100 || $window_size > 5000) {
            return false;
        }
        
        $this->context_window = $window_size;
        update_option("vortex_{$this->agent_id}_context_window", $window_size);
        
        return true;
    }
    
    /**
     * Get context window size
     *
     * @return int
     */
    public function get_context_window() {
        return $this->context_window;
    }
    
    /**
     * Enable or disable cross learning
     *
     * @param bool $status Whether to enable cross learning
     * @return bool Success status
     */
    public function enable_cross_learning($status = true) {
        $this->cross_learning_enabled = (bool) $status;
        update_option("vortex_{$this->agent_id}_cross_learning", $status ? '1' : '0');
        
        if ($status) {
            $this->initialize_cross_learning();
        }
        
        return true;
    }
    
    /**
     * Check if cross learning is enabled
     *
     * @return bool
     */
    public function is_cross_learning_enabled() {
        return $this->cross_learning_enabled;
    }
    
    /**
     * Train deep learning model
     *
     * @return array Training metrics
     */
    public function train_deep_learning_model() {
        if (!$this->deep_learning_enabled) {
            return array('status' => 'error', 'message' => 'Deep learning is not enabled');
        }
        
        try {
            // Collect training data
            $training_data = $this->collect_training_data();
            
            // Perform model training
            $metrics = $this->perform_model_training($training_data);
            
            // Log training metrics
            $this->log_training_metrics($metrics);
            
            // Save updated model weights
            $this->save_model_weights();
            
            return array(
                'status' => 'success',
                'metrics' => $metrics,
                'timestamp' => time()
            );
        } catch (Exception $e) {
            error_log("Error training {$this->agent_id} model: " . $e->getMessage());
            return array(
                'status' => 'error',
                'message' => $e->getMessage(),
                'timestamp' => time()
            );
        }
    }
    
    /**
     * Get shareable insights
     *
     * @return array Insights for other agents
     */
    public function get_shareable_insights() {
        // Override in child classes
        return array();
    }
    
    /**
     * Process external insight from another agent
     *
     * @param array $insight Insight data
     * @param string $source_agent Source agent ID
     * @return bool Success status
     */
    public function process_external_insight($insight, $source_agent) {
        // Override in child classes
        return false;
    }
    
    /**
     * Collect training data
     * 
     * @return array Training data
     */
    protected function collect_training_data() {
        // Override in child classes
        return array();
    }
    
    /**
     * Perform model training
     * 
     * @param array $training_data Training data
     * @return array Training metrics
     */
    protected function perform_model_training($training_data) {
        // Override in child classes
        return array(
            'examples_processed' => 0,
            'loss' => 0,
            'accuracy' => 0
        );
    }
    
    /**
     * Log training metrics
     * 
     * @param array $metrics Training metrics
     */
    protected function log_training_metrics($metrics) {
        global $wpdb;
        
        // Log metrics in database if available
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}vortex_deep_learning_metrics'") === $wpdb->prefix . 'vortex_deep_learning_metrics') {
            $wpdb->insert(
                $wpdb->prefix . 'vortex_deep_learning_metrics',
                array(
                    'agent_id' => $this->agent_id,
                    'examples_processed' => $metrics['examples_processed'] ?? 0,
                    'loss' => $metrics['loss'] ?? 0,
                    'accuracy' => $metrics['accuracy'] ?? 0,
                    'learning_rate' => $this->learning_rate,
                    'context_window' => $this->context_window,
                    'recorded_at' => current_time('mysql')
                )
            );
        }
        
        // Update latest metrics in options
        update_option("vortex_{$this->agent_id}_latest_metrics", $metrics);
        update_option("vortex_{$this->agent_id}_last_trained", time());
    }
    
    /**
     * Save model weights
     */
    protected function save_model_weights() {
        // Override in child classes
    }
} 