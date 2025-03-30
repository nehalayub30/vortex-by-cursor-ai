<?php
/**
 * Deep Learning System for Vortex AI Marketplace
 *
 * @package Vortex_Marketplace
 * @subpackage DeepLearning
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Deep_Learning class
 * Manages deep learning functionality across AI agents
 */
class VORTEX_Deep_Learning {
    /**
     * Instance of this class
     * @var VORTEX_Deep_Learning
     */
    private static $instance = null;
    
    /**
     * Active AI models
     * @var array
     */
    private $active_models = array();
    
    /**
     * Learning settings
     * @var array
     */
    private $learning_settings;
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->learning_settings = get_option('vortex_deep_learning_settings', $this->get_default_settings());
        
        // Initialize hooks
        add_action('init', array($this, 'initialize_models'));
        add_action('vortex_marketplace_activated', array($this, 'activate_deep_learning'));
        
        // Schedule learning tasks
        add_action('vortex_scheduled_learning', array($this, 'perform_scheduled_learning'));
        
        // Register admin settings
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Get instance of this class
     * @return VORTEX_Deep_Learning
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Get default deep learning settings
     * @return array Default settings
     */
    private function get_default_settings() {
        return array(
            'enabled' => true,
            'learning_rate' => 0.001,
            'batch_size' => 64,
            'context_window' => 1000,
            'continuous_learning' => true,
            'cross_learning' => true,
            'learning_interval' => 'daily',
            'storage_path' => WP_CONTENT_DIR . '/vortex-ai-models/',
            'models' => array(
                'huraii' => array(
                    'enabled' => true,
                    'version' => '1.0',
                    'layers' => 6,
                    'hidden_units' => 512
                ),
                'cloe' => array(
                    'enabled' => true,
                    'version' => '1.0',
                    'layers' => 4,
                    'hidden_units' => 256
                ),
                'business_strategist' => array(
                    'enabled' => true,
                    'version' => '1.0',
                    'layers' => 4,
                    'hidden_units' => 256
                ),
                'tola' => array(
                    'enabled' => true,
                    'version' => '1.0',
                    'layers' => 3,
                    'hidden_units' => 128
                )
            )
        );
    }
    
    /**
     * Initialize models on WordPress init
     */
    public function initialize_models() {
        if (!$this->learning_settings['enabled']) {
            return;
        }
        
        // Create storage directory if it doesn't exist
        if (!file_exists($this->learning_settings['storage_path'])) {
            wp_mkdir_p($this->learning_settings['storage_path']);
            // Create .htaccess to protect model files
            file_put_contents(
                $this->learning_settings['storage_path'] . '.htaccess',
                "Order Deny,Allow\nDeny from all"
            );
        }
        
        // Initialize each enabled model
        foreach ($this->learning_settings['models'] as $model_id => $model_settings) {
            if ($model_settings['enabled']) {
                $this->initialize_model($model_id, $model_settings);
            }
        }
        
        // Set up storage security
        $this->secure_model_storage();
    }
    
    /**
     * Initialize a specific model
     * @param string $model_id Model identifier
     * @param array $model_settings Model settings
     */
    private function initialize_model($model_id, $model_settings) {
        // Build model configuration
        $model_config = array(
            'id' => $model_id,
            'version' => $model_settings['version'],
            'architecture' => array(
                'layers' => $model_settings['layers'],
                'hidden_units' => $model_settings['hidden_units'],
                'learning_rate' => $this->learning_settings['learning_rate'],
                'batch_size' => $this->learning_settings['batch_size'],
                'context_window' => $this->learning_settings['context_window']
            ),
            'continuous_learning' => $this->learning_settings['continuous_learning'],
            'cross_learning' => $this->learning_settings['cross_learning'],
            'storage_path' => $this->learning_settings['storage_path'] . $model_id . '/'
        );
        
        // Create model directory
        if (!file_exists($model_config['storage_path'])) {
            wp_mkdir_p($model_config['storage_path']);
        }
        
        // Load pre-trained weights if available
        $weights_file = $model_config['storage_path'] . 'weights-' . $model_settings['version'] . '.json';
        if (file_exists($weights_file)) {
            $model_config['weights'] = json_decode(file_get_contents($weights_file), true);
        }
        
        // Register model
        $this->active_models[$model_id] = $model_config;
        
        // Notify agent about model
        do_action("vortex_{$model_id}_model_initialized", $model_config);
    }
    
    /**
     * Activate deep learning on plugin activation
     */
    public function activate_deep_learning() {
        // Enable deep learning for all agents
        $this->update_agent_deep_learning_settings(true);
        
        // Schedule initial training
        wp_schedule_single_event(time() + 300, 'vortex_scheduled_learning');
        
        // Schedule regular learning based on settings
        if (!wp_next_scheduled('vortex_scheduled_learning')) {
            $interval = $this->learning_settings['learning_interval'];
            wp_schedule_event(time() + 86400, $interval, 'vortex_scheduled_learning');
        }
        
        // Log activation
        error_log('Vortex AI Marketplace: Deep learning activated');
    }
    
    /**
     * Update agent deep learning settings
     * @param bool $status Enable/disable status
     */
    private function update_agent_deep_learning_settings($status) {
        // Update each agent's settings
        $agents = array('huraii', 'cloe', 'business_strategist', 'tola');
        
        foreach ($agents as $agent) {
            $option_name = "vortex_{$agent}_settings";
            $agent_settings = get_option($option_name, array());
            
            $agent_settings['deep_learning_enabled'] = $status;
            $agent_settings['learning_rate'] = $this->learning_settings['learning_rate'];
            $agent_settings['context_window'] = $this->learning_settings['context_window'];
            $agent_settings['continuous_learning'] = $this->learning_settings['continuous_learning'];
            $agent_settings['cross_learning'] = $this->learning_settings['cross_learning'];
            
            update_option($option_name, $agent_settings);
            
            // Notify agent
            do_action("vortex_{$agent}_settings_updated", $agent_settings);
        }
    }
    
    /**
     * Secure model storage
     */
    private function secure_model_storage() {
        // Create index.php to prevent directory listing
        $index_file = $this->learning_settings['storage_path'] . 'index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
        
        // Create security measures
        $this->create_model_access_control();
    }
    
    /**
     * Create access control for models
     */
    private function create_model_access_control() {
        // Generate access token if not exists
        $access_token = get_option('vortex_model_access_token', '');
        if (empty($access_token)) {
            $access_token = wp_generate_password(64, true, true);
            update_option('vortex_model_access_token', $access_token);
        }
        
        // Store token expiration (24 hours)
        update_option('vortex_model_token_expiration', time() + 86400);
    }
    
    /**
     * Perform scheduled learning
     */
    public function perform_scheduled_learning() {
        if (!$this->learning_settings['enabled']) {
            return;
        }
        
        // Log learning start
        error_log('Vortex AI Marketplace: Starting scheduled deep learning');
        
        // Process each active model
        foreach ($this->active_models as $model_id => $model_config) {
            $this->train_model($model_id);
            
            // Apply cross-learning if enabled
            if ($this->learning_settings['cross_learning']) {
                $this->apply_cross_learning($model_id);
            }
        }
        
        // Log completion
        error_log('Vortex AI Marketplace: Completed scheduled deep learning');
    }
    
    /**
     * Train a specific model
     * @param string $model_id Model identifier
     */
    private function train_model($model_id) {
        // Collect training data
        $training_data = $this->collect_training_data($model_id);
        
        if (empty($training_data)) {
            error_log("Vortex AI Marketplace: No training data available for {$model_id}");
            return;
        }
        
        // Process training data
        $processed_data = $this->process_training_data($model_id, $training_data);
        
        // Train model
        $model_config = $this->active_models[$model_id];
        $training_result = $this->execute_training($model_id, $model_config, $processed_data);
        
        // Update model weights
        $this->update_model_weights($model_id, $training_result);
        
        // Log training metrics
        $this->log_training_metrics($model_id, $training_result);
    }
    
    /**
     * Collect training data for a model
     * @param string $model_id Model identifier
     * @return array Training data
     */
    private function collect_training_data($model_id) {
        global $wpdb;
        
        // Get training data from database
        $table_name = $wpdb->prefix . 'vortex_training_data';
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE agent_id = %s 
             AND timestamp > %d 
             ORDER BY confidence_score DESC 
             LIMIT 1000",
            $model_id,
            time() - (86400 * 7) // Last 7 days
        );
        
        $training_data = $wpdb->get_results($query, ARRAY_A);
        
        return $training_data;
    }
    
    /**
     * Process training data
     * @param string $model_id Model identifier
     * @param array $training_data Raw training data
     * @return array Processed training data
     */
    private function process_training_data($model_id, $training_data) {
        $processed_data = array(
            'inputs' => array(),
            'outputs' => array(),
            'metadata' => array(
                'model_id' => $model_id,
                'timestamp' => time(),
                'sample_count' => count($training_data)
            )
        );
        
        // Process each training example
        foreach ($training_data as $example) {
            $processed_data['inputs'][] = json_decode($example['input_data'], true);
            $processed_data['outputs'][] = json_decode($example['output_data'], true);
        }
        
        return $processed_data;
    }
    
    /**
     * Execute model training
     * @param string $model_id Model identifier
     * @param array $model_config Model configuration
     * @param array $processed_data Processed training data
     * @return array Training results
     */
    private function execute_training($model_id, $model_config, $processed_data) {
        // In a real implementation, this would call a ML framework
        // For this plugin, we'll simulate training
        
        $training_result = array(
            'model_id' => $model_id,
            'version' => $model_config['version'],
            'timestamp' => time(),
            'examples_processed' => count($processed_data['inputs']),
            'epochs' => 10,
            'final_loss' => 0.05 + (mt_rand(0, 20) / 1000), // Simulated loss value
            'accuracy' => 0.92 + (mt_rand(0, 80) / 1000), // Simulated accuracy
            'training_time' => mt_rand(30, 300), // Simulated training time in seconds
            'updated_weights' => $this->simulate_weight_updates($model_config)
        );
        
        return $training_result;
    }
    
    /**
     * Simulate weight updates
     * @param array $model_config Model configuration
     * @return array Updated weights
     */
    private function simulate_weight_updates($model_config) {
        // In a real implementation, this would be actual model weights
        // For this plugin, we'll generate placeholder data
        
        $weights = array();
        $layers = $model_config['architecture']['layers'];
        $units = $model_config['architecture']['hidden_units'];
        
        // Generate random weight adjustments
        for ($i = 0; $i < $layers; $i++) {
            $weights["layer_{$i}"] = array(
                'mean' => mt_rand(-100, 100) / 1000,
                'std' => mt_rand(1, 100) / 1000,
                'updated_parameters' => mt_rand(80, 100),
                'total_parameters' => 100
            );
        }
        
        return $weights;
    }
    
    /**
     * Update model weights
     * @param string $model_id Model identifier
     * @param array $training_result Training results
     */
    private function update_model_weights($model_id, $training_result) {
        // In a real implementation, this would save actual model weights
        // For this plugin, we'll save placeholder data
        
        $model_config = $this->active_models[$model_id];
        $weights_file = $model_config['storage_path'] . 'weights-' . $model_config['version'] . '.json';
        
        // Create directory if it doesn't exist
        if (!file_exists(dirname($weights_file))) {
            wp_mkdir_p(dirname($weights_file));
        }
        
        // Save weights data
        $weights_data = array(
            'model_id' => $model_id,
            'version' => $model_config['version'],
            'last_updated' => time(),
            'training_metrics' => array(
                'loss' => $training_result['final_loss'],
                'accuracy' => $training_result['accuracy'],
                'examples_processed' => $training_result['examples_processed']
            ),
            'weights' => $training_result['updated_weights']
        );
        
        file_put_contents($weights_file, json_encode($weights_data));
        
        // Update model version in config
        $this->active_models[$model_id]['version'] = $this->increment_version($model_config['version']);
    }
    
    /**
     * Increment version number
     * @param string $version Current version
     * @return string New version
     */
    private function increment_version($version) {
        $parts = explode('.', $version);
        $minor = intval(array_pop($parts));
        $parts[] = $minor + 1;
        return implode('.', $parts);
    }
    
    /**
     * Log training metrics
     * @param string $model_id Model identifier
     * @param array $training_result Training results
     */
    private function log_training_metrics($model_id, $training_result) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_model_metrics';
        
        $wpdb->insert(
            $table_name,
            array(
                'model_id' => $model_id,
                'version' => $training_result['version'],
                'timestamp' => $training_result['timestamp'],
                'examples_processed' => $training_result['examples_processed'],
                'loss' => $training_result['final_loss'],
                'accuracy' => $training_result['accuracy'],
                'training_time' => $training_result['training_time']
            )
        );
        
        // Log to error log for debugging
        error_log("Vortex AI: {$model_id} model trained - Loss: {$training_result['final_loss']}, Accuracy: {$training_result['accuracy']}");
    }
    
    /**
     * Apply cross-learning between models
     * @param string $source_model_id Source model ID
     */
    private function apply_cross_learning($source_model_id) {
        if (!$this->learning_settings['cross_learning']) {
            return;
        }
        
        // Get insights from source model
        $insights = $this->extract_model_insights($source_model_id);
        
        if (empty($insights)) {
            return;
        }
        
        // Apply insights to other models
        foreach ($this->active_models as $target_model_id => $target_config) {
            // Skip self
            if ($target_model_id === $source_model_id) {
                continue;
            }
            
            // Share insights
            $this->share_insights($source_model_id, $target_model_id, $insights);
        }
    }
    
    /**
     * Extract insights from a model
     * @param string $model_id Model identifier
     * @return array Model insights
     */
    private function extract_model_insights($model_id) {
        // In a real implementation, this would extract actual insights
        // For this plugin, we'll generate placeholder insights
        
        $insight_types = array(
            'huraii' => array('art_style', 'visual_preferences', 'creative_patterns'),
            'cloe' => array('user_behavior', 'market_trend', 'content_performance'),
            'business_strategist' => array('pricing_strategy', 'market_opportunity', 'business_pattern'),
            'tola' => array('transaction_pattern', 'token_activity', 'security_insight')
        );
        
        $available_types = isset($insight_types[$model_id]) ? $insight_types[$model_id] : array('generic_insight');
        
        $insights = array();
        $insight_count = mt_rand(1, 5);
        
        for ($i = 0; $i < $insight_count; $i++) {
            $type = $available_types[array_rand($available_types)];
            
            $insights[] = array(
                'type' => $type,
                'confidence' => 0.7 + (mt_rand(0, 300) / 1000),
                'data' => array(
                    'description' => "Simulated {$type} insight from {$model_id}",
                    'value' => mt_rand(1, 100) / 100,
                    'timestamp' => time()
                )
            );
        }
        
        return $insights;
    }
    
    /**
     * Share insights between models
     * @param string $source_model_id Source model ID
     * @param string $target_model_id Target model ID
     * @param array $insights Insights to share
     */
    private function share_insights($source_model_id, $target_model_id, $insights) {
        // Map model IDs to agent actions
        $agent_map = array(
            'huraii' => 'huraii',
            'cloe' => 'cloe',
            'business_strategist' => 'business_strategist',
            'tola' => 'tola'
        );
        
        if (isset($agent_map[$source_model_id]) && isset($agent_map[$target_model_id])) {
            $source_agent = $agent_map[$source_model_id];
            $target_agent = $agent_map[$target_model_id];
            
            foreach ($insights as $insight) {
                // Trigger cross-learning action
                do_action("vortex_{$target_agent}_process_external_insight", $source_agent, $insight['type'], $insight['data']);
            }
            
            // Log cross-learning
            error_log("Vortex AI: Cross-learning from {$source_agent} to {$target_agent} - " . count($insights) . " insights shared");
        }
    }
    
    /**
     * Register admin settings
     */
    public function register_settings() {
        register_setting('vortex_ai_settings', 'vortex_deep_learning_settings');
        
        add_settings_section(
            'vortex_deep_learning_section',
            'Deep Learning Settings',
            array($this, 'render_deep_learning_section'),
            'vortex-ai-settings'
        );
        
        add_settings_field(
            'vortex_deep_learning_enabled',
            'Enable Deep Learning',
            array($this, 'render_deep_learning_enabled_field'),
            'vortex-ai-settings',
            'vortex_deep_learning_section'
        );
        
        add_settings_field(
            'vortex_learning_rate',
            'Learning Rate',
            array($this, 'render_learning_rate_field'),
            'vortex-ai-settings',
            'vortex_deep_learning_section'
        );
        
        add_settings_field(
            'vortex_continuous_learning',
            'Continuous Learning',
            array($this, 'render_continuous_learning_field'),
            'vortex-ai-settings',
            'vortex_deep_learning_section'
        );
        
        add_settings_field(
            'vortex_cross_learning',
            'Cross-Agent Learning',
            array($this, 'render_cross_learning_field'),
            'vortex-ai-settings',
            'vortex_deep_learning_section'
        );
    }
    
    /**
     * Render deep learning section
     */
    public function render_deep_learning_section() {
        echo '<p>Configure deep learning settings for all AI agents in the marketplace.</p>';
    }
    
    /**
     * Render deep learning enabled field
     */
    public function render_deep_learning_enabled_field() {
        $settings = get_option('vortex_deep_learning_settings', $this->get_default_settings());
        
        echo '<input type="checkbox" name="vortex_deep_learning_settings[enabled]" ' . checked($settings['enabled'], true, false) . ' value="1" />';
        echo '<span class="description">Enable deep learning for all AI agents</span>';
    }
    
    /**
     * Render learning rate field
     */
    public function render_learning_rate_field() {
        $settings = get_option('vortex_deep_learning_settings', $this->get_default_settings());
        
        echo '<input type="number" step="0.0001" min="0.0001" max="0.1" name="vortex_deep_learning_settings[learning_rate]" value="' . esc_attr($settings['learning_rate']) . '" />';
        echo '<span class="description">Learning rate for AI model training (lower values = more stable learning)</span>';
    }
    
    /**
     * Render continuous learning field
     */
    public function render_continuous_learning_field() {
        $settings = get_option('vortex_deep_learning_settings', $this->get_default_settings());
        
        echo '<input type="checkbox" name="vortex_deep_learning_settings[continuous_learning]" ' . checked($settings['continuous_learning'], true, false) . ' value="1" />';
        echo '<span class="description">Enable continuous learning for AI agents</span>';
    }
    
    /**
     * Render cross learning field
     */
    public function render_cross_learning_field() {
        $settings = get_option('vortex_deep_learning_settings', $this->get_default_settings());
        
        echo '<input type="checkbox" name="vortex_deep_learning_settings[cross_learning]" ' . checked($settings['cross_learning'], true, false) . ' value="1" />';
        echo '<span class="description">Enable cross-learning between AI agents</span>';
    }
}

// Initialize deep learning
add_action('plugins_loaded', function() {
    VORTEX_Deep_Learning::get_instance();
}); 