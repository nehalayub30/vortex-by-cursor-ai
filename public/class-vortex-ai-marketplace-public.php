/**
 * Handle AJAX request for artwork generation
 */
public function generate_artwork() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_generate_artwork')) {
        wp_send_json_error(array(
            'message' => __('Security verification failed', 'vortex-ai-marketplace')
        ));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => __('You must be logged in to generate artwork', 'vortex-ai-marketplace')
        ));
    }
    
    // Check if user has access (TOLA tokens)
    $wallet = Vortex_AI_Marketplace::get_instance()->wallet;
    if (!$wallet->check_llm_api_access(get_current_user_id())) {
        wp_send_json_error(array(
            'message' => __('You need TOLA tokens to access this feature', 'vortex-ai-marketplace')
        ));
    }
    
    // Get parameters
    $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
    $style = isset($_POST['style']) ? sanitize_text_field($_POST['style']) : 'realistic';
    $size = isset($_POST['size']) ? sanitize_text_field($_POST['size']) : 'medium';
    
    if (empty($prompt)) {
        wp_send_json_error(array(
            'message' => __('Please provide a description for your artwork', 'vortex-ai-marketplace')
        ));
    }
    
    // Map size to dimensions
    $dimensions = array(
        'small' => '512x512',
        'medium' => '1024x1024',
        'large' => '2048x2048'
    );
    
    $dimension = isset($dimensions[$size]) ? $dimensions[$size] : $dimensions['medium'];
    
    // Create complete prompt with style
    $full_prompt = $prompt . '. Style: ' . $style;
    
    // Get LLM client
    $llm_client = Vortex_AI_Marketplace::get_instance()->llm_client;
    
    // Prepare request parameters
    $params = array(
        'prompt' => $full_prompt,
        'size' => $dimension,
        'model' => 'huraii-diffusion',
        'n' => 1,
        'response_format' => 'url',
        'task' => 'artwork'
    );
    
    try {
        // Make request to HURAII
        $result = $llm_client->request('artwork', $params, get_current_user_id());
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        // Extract image URL from result
        $image_url = $result['content'];
        
        if (empty($image_url)) {
            wp_send_json_error(array(
                'message' => __('Failed to generate artwork', 'vortex-ai-marketplace')
            ));
        }
        
        // Deduct TOLA tokens for successful generation
        // The amount should depend on size and complexity
        $token_cost = array(
            'small' => 5,
            'medium' => 10,
            'large' => 20
        );
        
        $cost = isset($token_cost[$size]) ? $token_cost[$size] : $token_cost['medium'];
        $wallet->deduct_tola_tokens(get_current_user_id(), $cost, 'artwork_generation');
        
        // Return success with image URL
        wp_send_json_success(array(
            'image_url' => $image_url,
            'prompt' => $prompt,
            'style' => $style,
            'size' => $dimension,
            'tokens_used' => $cost
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => $e->getMessage()
        ));
    }
}

/**
 * Register the JavaScript for the public-facing side of the site.
 *
 * @since    1.0.0
 */
public function enqueue_scripts() {
    // Existing scripts
    wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/vortex-ai-marketplace-public.js', array('jquery'), $this->version, true);
    
    // Add new scripts for revolutionary features
    wp_enqueue_script('vortex-adaptive-ux', plugin_dir_url(__FILE__) . 'js/vortex-adaptive-ux.js', array('jquery'), $this->version, true);
    wp_enqueue_script('vortex-multimodal', plugin_dir_url(__FILE__) . 'js/vortex-multimodal.js', array('jquery'), $this->version, true);
    wp_enqueue_script('vortex-task-automation', plugin_dir_url(__FILE__) . 'js/vortex-task-automation.js', array('jquery'), $this->version, true);
    
    // Localize the script with our data
    wp_localize_script($this->plugin_name, 'vortex_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'predictions_nonce' => wp_create_nonce('vortex_predictions'),
        'multimodal_nonce' => wp_create_nonce('vortex_multimodal_upload'),
        'automation_nonce' => wp_create_nonce('vortex_automation')
    ));
    
    // Register AJAX handlers (existing)
    add_action('wp_ajax_vortex_generate_artwork', array($this, 'generate_artwork'));
    add_action('wp_ajax_nopriv_vortex_generate_artwork', array($this, 'handle_not_logged_in'));
    
    add_action('wp_ajax_vortex_analyze_market', array($this, 'analyze_market'));
    add_action('wp_ajax_nopriv_vortex_analyze_market', array($this, 'handle_not_logged_in'));
    
    add_action('wp_ajax_vortex_generate_strategy', array($this, 'generate_strategy'));
    add_action('wp_ajax_nopriv_vortex_generate_strategy', array($this, 'handle_not_logged_in'));
    
    // Register new AJAX handlers
    add_action('wp_ajax_vortex_upload_multimodal', array($this, 'handle_multimodal_upload'));
    add_action('wp_ajax_vortex_get_predictions', array($this, 'get_user_predictions'));
    add_action('wp_ajax_vortex_use_prediction', array($this, 'use_prediction'));
    add_action('wp_ajax_vortex_create_automation_task', array($this, 'create_automation_task'));
    add_action('wp_ajax_vortex_get_automation_tasks', array($this, 'get_automation_tasks'));
    add_action('wp_ajax_vortex_toggle_automation_task', array($this, 'toggle_automation_task'));
}

/**
 * Handle AJAX requests from non-logged in users
 */
public function handle_not_logged_in() {
    wp_send_json_error(array(
        'message' => __('You must be logged in to perform this action', 'vortex-ai-marketplace')
    ));
}

/**
 * Handle AJAX request for market analysis
 */
public function analyze_market() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_analyze_market')) {
        wp_send_json_error(array(
            'message' => __('Security verification failed', 'vortex-ai-marketplace')
        ));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => __('You must be logged in to access market analysis', 'vortex-ai-marketplace')
        ));
    }
    
    // Check if user has access (TOLA tokens)
    $wallet = Vortex_AI_Marketplace::get_instance()->wallet;
    if (!$wallet->check_llm_api_access(get_current_user_id())) {
        wp_send_json_error(array(
            'message' => __('You need TOLA tokens to access this feature', 'vortex-ai-marketplace')
        ));
    }
    
    // Get parameters
    $market = isset($_POST['market']) ? sanitize_text_field($_POST['market']) : '';
    $timeframe = isset($_POST['timeframe']) ? sanitize_text_field($_POST['timeframe']) : '';
    $question = isset($_POST['question']) ? sanitize_textarea_field($_POST['question']) : '';
    $analysis_depth = isset($_POST['analysis_depth']) ? sanitize_text_field($_POST['analysis_depth']) : 'standard';
    
    // Add multimodal data if available
    $multimodal_data = isset($_POST['multimodal_data']) ? sanitize_text_field($_POST['multimodal_data']) : '';
    
    // Different prompt based on analysis depth
    $analysis_prompt = $analysis_depth === 'advanced' 
        ? "Provide an advanced, in-depth analysis of the $market market over a $timeframe period. Include technical indicators, fundamental factors, and detailed projections." 
        : "Analyze the $market market over a $timeframe period";
        
    // Combine with user question if provided
    if (!empty($question)) {
        $analysis_prompt .= ". Focus on: $question";
    }
    
    // Get LLM client
    $llm_client = Vortex_AI_Marketplace::get_instance()->llm_client;
    
    // Prepare request parameters
    $params = array(
        'prompt' => $analysis_prompt,
        'temperature' => 0.5,
        'max_tokens' => 1000,
        'task' => 'market'
    );
    
    try {
        // Make request to CLOE
        $result = $llm_client->request('market', $params, get_current_user_id());
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        // Format the analysis for display
        $analysis = '<h3>' . sprintf(__('%s Market Analysis (%d days)', 'vortex-ai-marketplace'), 
                           ucfirst($market), $timeframe) . '</h3>';
        $analysis .= '<div class="vortex-analysis-content">' . nl2br(esc_html($result['content'])) . '</div>';
        
        // Deduct TOLA tokens based on detail level
        $token_cost = array(
            'low' => 3,
            'medium' => 5,
            'high' => 10
        );
        
        $cost = isset($token_cost[$analysis_depth]) ? $token_cost[$analysis_depth] : $token_cost['medium'];
        $wallet->deduct_tola_tokens(get_current_user_id(), $cost, 'market_analysis');
        
        // Return success with analysis
        wp_send_json_success(array(
            'analysis' => $analysis,
            'market' => $market,
            'timeframe' => $timeframe,
            'tokens_used' => $cost
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => $e->getMessage()
        ));
    }
}

/**
 * Handle AJAX request for business strategy
 */
public function generate_strategy() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_generate_strategy')) {
        wp_send_json_error(array(
            'message' => __('Security verification failed', 'vortex-ai-marketplace')
        ));
    }
    
    // Get parameters
    $industry = isset($_POST['industry']) ? sanitize_text_field($_POST['industry']) : '';
    $focus = isset($_POST['focus']) ? sanitize_text_field($_POST['focus']) : '';
    $details = isset($_POST['details']) ? sanitize_textarea_field($_POST['details']) : '';
    $recommendation_type = isset($_POST['recommendation_type']) ? sanitize_text_field($_POST['recommendation_type']) : 'standard';
    
    // Add multimodal data if available
    $multimodal_data = isset($_POST['multimodal_data']) ? sanitize_text_field($_POST['multimodal_data']) : '';
    
    // Different prompt based on recommendation type
    $strategy_prompt = $recommendation_type === 'detailed'
        ? "Provide a comprehensive, detailed business strategy for a $industry company focusing on $focus. Include actionable steps, KPIs, and implementation timeline."
        : "Create a business strategy for a $industry company focusing on $focus";
        
    // Combine with user details if provided
    if (!empty($details)) {
        $strategy_prompt .= ". Additional context: $details";
    }
    
    // Get LLM client
    $llm_client = Vortex_AI_Marketplace::get_instance()->llm_client;
    
    // Prepare request parameters
    $params = array(
        'prompt' => $strategy_prompt,
        'temperature' => 0.7,
        'max_tokens' => 1500,
        'task' => 'strategy'
    );
    
    try {
        // Make request to Business Strategist
        $result = $llm_client->request('strategy', $params, get_current_user_id());
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        // Format the strategy for display
        $strategy = '<h3>' . sprintf(__('%s Strategy: %s Focus (%s Term)', 'vortex-ai-marketplace'), 
                            ucfirst($industry), ucfirst($focus), ucfirst($recommendation_type)) . '</h3>';
        $strategy .= '<div class="vortex-strategy-content">' . nl2br(esc_html($result['content'])) . '</div>';
        
        // Deduct TOLA tokens
        $token_cost = 15; // Strategic advice costs more tokens
        $wallet->deduct_tola_tokens(get_current_user_id(), $token_cost, 'strategy_recommendation');
        
        // Return success with strategy
        wp_send_json_success(array(
            'strategy' => $strategy,
            'industry' => $industry,
            'focus' => $focus,
            'recommendation_type' => $recommendation_type,
            'tokens_used' => $token_cost
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => $e->getMessage()
        ));
    }
}

/**
 * Handle TOLA purchase completion
 */
public function complete_tola_purchase() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_tola_purchase')) {
        wp_send_json_error(array(
            'message' => __('Security verification failed', 'vortex-ai-marketplace')
        ));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => __('You must be logged in to purchase TOLA tokens', 'vortex-ai-marketplace')
        ));
    }
    
    $user_id = get_current_user_id();
    $amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
    
    if ($amount <= 0) {
        wp_send_json_error(array(
            'message' => __('Invalid amount specified', 'vortex-ai-marketplace')
        ));
    }
    
    try {
        // In a real implementation, we would process the blockchain transaction here
        // For demonstration, we'll just credit the tokens directly
        
        // Add tokens to user's balance
        $wallet = Vortex_AI_Marketplace::get_instance()->wallet;
        $wallet->add_tola_tokens($user_id, $amount, 'purchase');
        
        // Trigger action for showing agreement after purchase
        do_action('vortex_after_tola_purchase', $user_id);
        
        // Return success
        wp_send_json_success(array(
            'message' => sprintf(__('Successfully purchased %d TOLA tokens', 'vortex-ai-marketplace'), $amount),
            'new_balance' => $wallet->get_tola_balance($user_id),
            'show_agreement' => !Vortex_AI_Marketplace::get_instance()->user_agreement->has_user_agreed($user_id)
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => $e->getMessage()
        ));
    }
}

/**
 * Register AJAX handlers and enqueue scripts
 */
public function register_ajax_handlers() {
    // Existing AJAX handlers...
    
    // New AJAX handlers for enhanced features
    add_action('wp_ajax_vortex_upload_multimodal', array($this, 'handle_multimodal_upload'));
    add_action('wp_ajax_vortex_get_predictions', array($this, 'get_user_predictions'));
    add_action('wp_ajax_vortex_use_prediction', array($this, 'use_prediction'));
    add_action('wp_ajax_vortex_create_automation_task', array($this, 'create_automation_task'));
    add_action('wp_ajax_vortex_get_automation_tasks', array($this, 'get_automation_tasks'));
    add_action('wp_ajax_vortex_toggle_automation_task', array($this, 'toggle_automation_task'));
    
    // Business Idea Processing
    add_action('wp_ajax_vortex_process_business_idea', array($this, 'process_business_idea'));
    add_action('wp_ajax_nopriv_vortex_process_business_idea', array($this, 'handle_not_logged_in'));
    
    // Notification Preferences
    add_action('wp_ajax_vortex_enable_notifications', array($this, 'enable_notifications'));
    add_action('wp_ajax_nopriv_vortex_enable_notifications', array($this, 'handle_not_logged_in'));
}

/**
 * Get user predictions via AJAX
 */
public function get_user_predictions() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_predictions')) {
        wp_send_json_error(array(
            'message' => __('Security verification failed', 'vortex-ai-marketplace')
        ));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => __('You must be logged in to access predictions', 'vortex-ai-marketplace')
        ));
    }
    
    $user_id = get_current_user_id();
    $count = isset($_POST['count']) ? intval($_POST['count']) : 5;
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'all';
    
    // Get predictions
    $predictive_engine = Vortex_AI_Marketplace::get_instance()->predictive_engine;
    $predictions = $predictive_engine->get_user_predictions($user_id);
    
    // Filter by type if needed
    if ($type !== 'all' && !empty($predictions[$type])) {
        $filtered_predictions = array($type => $predictions[$type]);
        $predictions = $filtered_predictions;
    }
    
    // Format predictions for display
    $html = '';
    $shown = 0;
    
    foreach ($predictions as $pred_type => $type_predictions) {
        foreach ($type_predictions as $prediction) {
            if ($shown >= $count) break;
            $html .= $predictive_engine->format_prediction_item(array_merge($prediction, array('category' => $pred_type)));
            $shown++;
        }
        if ($shown >= $count) break;
    }
    
    if (empty($html)) {
        $html = '<li class="vortex-no-predictions">' . 
                __('No predictions available yet. Keep using the AI agents to receive personalized predictions.', 'vortex-ai-marketplace') . 
                '</li>';
    }
    
    wp_send_json_success(array(
        'html' => $html,
        'count' => $shown
    ));
}

/**
 * Use a prediction via AJAX
 */
public function use_prediction() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_predictions')) {
        wp_send_json_error(array(
            'message' => __('Security verification failed', 'vortex-ai-marketplace')
        ));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => __('You must be logged in to use predictions', 'vortex-ai-marketplace')
        ));
    }
    
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
    $value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';
    
    if (empty($type) || empty($value)) {
        wp_send_json_error(array(
            'message' => __('Invalid prediction data', 'vortex-ai-marketplace')
        ));
    }
    
    // Track prediction usage
    $predictive_engine = Vortex_AI_Marketplace::get_instance()->predictive_engine;
    $predictive_engine->track_prediction_usage(get_current_user_id(), $type, $value);
    
    // Return appropriate action based on prediction type
    switch ($type) {
        case 'artwork':
            wp_send_json_success(array(
                'action' => 'redirect',
                'url' => add_query_arg(array(
                    'prompt' => urlencode($value)
                ), get_permalink(get_option('vortex_huraii_page_id')))
            ));
            break;
            
        case 'market':
            wp_send_json_success(array(
                'action' => 'redirect',
                'url' => add_query_arg(array(
                    'market' => urlencode($value)
                ), get_permalink(get_option('vortex_cloe_page_id')))
            ));
            break;
            
        case 'strategy':
            wp_send_json_success(array(
                'action' => 'redirect',
                'url' => add_query_arg(array(
                    'industry' => urlencode($value)
                ), get_permalink(get_option('vortex_strategist_page_id')))
            ));
            break;
            
        default:
            wp_send_json_success(array(
                'action' => 'show',
                'value' => $value
            ));
    }
}

/**
 * Create automated task via AJAX
 */
public function create_automation_task() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_create_automation_task')) {
        wp_send_json_error(array(
            'message' => __('Security verification failed', 'vortex-ai-marketplace')
        ));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => __('You must be logged in to create automated tasks', 'vortex-ai-marketplace')
        ));
    }
    
    // Get task parameters
    $user_id = get_current_user_id();
    $task_name = isset($_POST['task_name']) ? sanitize_text_field($_POST['task_name']) : '';
    $task_type = isset($_POST['task_type']) ? sanitize_text_field($_POST['task_type']) : '';
    $task_params = isset($_POST['task_params']) ? sanitize_text_field($_POST['task_params']) : '{}';
    $frequency = isset($_POST['frequency']) ? sanitize_text_field($_POST['frequency']) : 'daily';
    
    // Validate required fields
    if (empty($task_name) || empty($task_type)) {
        wp_send_json_error(array(
            'message' => __('Task name and type are required', 'vortex-ai-marketplace')
        ));
    }
    
    // Create task
    $task_automation = Vortex_AI_Marketplace::get_instance()->task_automation;
    $result = $task_automation->create_task($user_id, $task_name, $task_type, $task_params, $frequency);
    
    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ));
    }
    
    wp_send_json_success(array(
        'message' => __('Task created successfully', 'vortex-ai-marketplace'),
        'task_id' => $result
    ));
}

/**
 * Get user's automation tasks via AJAX
 */
public function get_automation_tasks() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_automation')) {
        wp_send_json_error(array(
            'message' => __('Security verification failed', 'vortex-ai-marketplace')
        ));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => __('You must be logged in to view automated tasks', 'vortex-ai-marketplace')
        ));
    }
    
    $user_id = get_current_user_id();
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
    
    // Get tasks
    $task_automation = Vortex_AI_Marketplace::get_instance()->task_automation;
    $tasks = $task_automation->get_user_tasks($user_id, $limit);
    
    // Generate HTML for tasks
    $html = '';
    foreach ($tasks as $task) {
        ob_start();
        ?>
        <div class="vortex-task-item" data-id="<?php echo esc_attr($task['id']); ?>">
            <div class="vortex-task-header">
                <h5 class="vortex-task-name"><?php echo esc_html($task['task_name']); ?></h5>
                <span class="vortex-task-status <?php echo $task['active'] ? 'vortex-active' : 'vortex-inactive'; ?>">
                    <?php echo $task['active'] ? esc_html__('Active', 'vortex-ai-marketplace') : esc_html__('Inactive', 'vortex-ai-marketplace'); ?>
                </span>
            </div>
            
            <div class="vortex-task-details">
                <p class="vortex-task-type">
                    <strong><?php _e('Type:', 'vortex-ai-marketplace'); ?></strong> 
                    <?php echo esc_html($task_automation->get_task_type_label($task['task_type'])); ?>
                </p>
                
                <p class="vortex-task-frequency">
                    <strong><?php _e('Frequency:', 'vortex-ai-marketplace'); ?></strong> 
                    <?php echo esc_html(ucfirst($task['frequency'])); ?>
                </p>
                
                <?php if ($task['last_run']): ?>
                <p class="vortex-task-last-run">
                    <strong><?php _e('Last Run:', 'vortex-ai-marketplace'); ?></strong> 
                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($task['last_run']))); ?>
                </p>
                <?php endif; ?>
                
                <p class="vortex-task-next-run">
                    <strong><?php _e('Next Run:', 'vortex-ai-marketplace'); ?></strong> 
                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($task['next_run']))); ?>
                </p>
            </div>
            
            <div class="vortex-task-actions">
                <button class="vortex-toggle-task" data-id="<?php echo esc_attr($task['id']); ?>" data-active="<?php echo $task['active'] ? '1' : '0'; ?>">
                    <?php echo $task['active'] ? esc_html__('Deactivate', 'vortex-ai-marketplace') : esc_html__('Activate', 'vortex-ai-marketplace'); ?>
                </button>
            </div>
        </div>
        <?php
        $html .= ob_get_clean();
    }
    
    wp_send_json_success(array(
        'tasks' => $tasks,
        'html' => $html
    ));
}

/**
 * Toggle automation task active status via AJAX
 */
public function toggle_automation_task() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_automation')) {
        wp_send_json_error(array(
            'message' => __('Security verification failed', 'vortex-ai-marketplace')
        ));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => __('You must be logged in to manage automated tasks', 'vortex-ai-marketplace')
        ));
    }
    
    $user_id = get_current_user_id();
    $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
    $active = isset($_POST['active']) ? intval($_POST['active']) : 0;
    
    if ($task_id <= 0) {
        wp_send_json_error(array(
            'message' => __('Invalid task ID', 'vortex-ai-marketplace')
        ));
    }
    
    // Update task
    $task_automation = Vortex_AI_Marketplace::get_instance()->task_automation;
    $result = $task_automation->toggle_task($user_id, $task_id, $active);
    
    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ));
    }
    
    wp_send_json_success(array(
        'message' => $active ? __('Task activated', 'vortex-ai-marketplace') : __('Task deactivated', 'vortex-ai-marketplace'),
        'next_run' => $result ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($result)) : null
    ));
}

/**
 * Handle user registration
 */
public function register_user() {
    // Verify nonce
    if (!isset($_POST['vortex_register_nonce']) || !wp_verify_nonce($_POST['vortex_register_nonce'], 'vortex_register_nonce')) {
        wp_send_json_error(array('message' => __('Security verification failed', 'vortex-ai-marketplace')));
        return;
    }
    
    // Validate required fields
    if (empty($_POST['vortex_username']) || empty($_POST['vortex_email']) || empty($_POST['vortex_password']) || empty($_POST['vortex_password_confirm'])) {
        wp_send_json_error(array('message' => __('All fields are required', 'vortex-ai-marketplace')));
        return;
    }
    
    // Validate email
    $email = sanitize_email($_POST['vortex_email']);
    if (!is_email($email)) {
        wp_send_json_error(array('message' => __('Please enter a valid email address', 'vortex-ai-marketplace')));
        return;
    }
    
    // Check if email already exists
    if (email_exists($email)) {
        wp_send_json_error(array('message' => __('This email is already registered', 'vortex-ai-marketplace')));
        return;
    }
    
    // Validate username
    $username = sanitize_user($_POST['vortex_username']);
    if (username_exists($username)) {
        wp_send_json_error(array('message' => __('This username is already taken', 'vortex-ai-marketplace')));
        return;
    }
    
    // Check password strength
    $password = $_POST['vortex_password'];
    $password_confirm = $_POST['vortex_password_confirm'];
    
    if ($password !== $password_confirm) {
        wp_send_json_error(array('message' => __('Passwords do not match', 'vortex-ai-marketplace')));
        return;
    }
    
    if (strlen($password) < 8) {
        wp_send_json_error(array('message' => __('Password must be at least 8 characters long', 'vortex-ai-marketplace')));
        return;
    }
    
    // Validate terms agreement
    if (!isset($_POST['vortex_terms'])) {
        wp_send_json_error(array('message' => __('You must agree to the terms and conditions', 'vortex-ai-marketplace')));
        return;
    }
    
    // Get user role
    $user_role = isset($_POST['vortex_user_role']) ? sanitize_text_field($_POST['vortex_user_role']) : 'artist';
    
    // Get selected categories based on role
    $categories = array();
    if ($user_role === 'artist' && !empty($_POST['vortex_artist_categories'])) {
        $categories = array_map('sanitize_text_field', $_POST['vortex_artist_categories']);
        $categories = array_slice($categories, 0, 3); // Limit to 3 categories
    } elseif ($user_role === 'collector' && !empty($_POST['vortex_collector_categories'])) {
        $categories = array_map('sanitize_text_field', $_POST['vortex_collector_categories']);
        $categories = array_slice($categories, 0, 3); // Limit to 3 categories
    }
    
    // Create user
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => $user_id->get_error_message()));
        return;
    }
    
    // Set user role and save categories as user meta
    update_user_meta($user_id, 'vortex_user_role', $user_role);
    update_user_meta($user_id, 'vortex_user_categories', $categories);
    
    // If using subscriber role for all users
    $user = new WP_User($user_id);
    $user->set_role('subscriber');
    
    // Optional: Auto-login user
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    // Create wallet for new user
    $wallet = Vortex_AI_Marketplace::get_instance()->wallet;
    if ($wallet) {
        $wallet->create_user_wallet($user_id);
    }
    
    // Welcome bonus
    $welcome_bonus = get_option('vortex_welcome_bonus', 5);
    if ($welcome_bonus > 0 && $wallet) {
        $wallet->credit_tola_tokens($user_id, $welcome_bonus, 'welcome_bonus');
    }
    
    // Redirect to business idea form
    $business_idea_page = get_option('vortex_business_idea_page');
    if (!empty($business_idea_page)) {
        $redirect_url = get_permalink($business_idea_page);
    } else {
        // Default redirect
        $redirect_url = get_option('vortex_registration_redirect');
        if (empty($redirect_url)) {
            $redirect_url = home_url();
        }
    }
    
    wp_send_json_success(array(
        'message' => __('Registration successful! Welcome to Vortex.', 'vortex-ai-marketplace'),
        'redirect' => $redirect_url
    ));
}

/**
 * Process business idea submission
 */
public function process_business_idea() {
    // Get business strategist instance and process
    $business_strategist = Vortex_AI_Marketplace::get_instance()->business_strategist;
    
    if (!$business_strategist) {
        wp_send_json_error(array('message' => __('Business strategist is not available', 'vortex-ai-marketplace')));
        return;
    }
    
    // Pass to business strategist for processing
    $business_strategist->process_business_idea_submission();
}

/**
 * Enable notifications for a user
 */
public function enable_notifications() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_notifications_nonce')) {
        wp_send_json_error(array('message' => __('Security verification failed', 'vortex-ai-marketplace')));
        return;
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in', 'vortex-ai-marketplace')));
        return;
    }
    
    // Save notification preference
    update_user_meta(get_current_user_id(), 'vortex_notifications_enabled', true);
    
    wp_send_json_success(array('message' => __('Notifications enabled successfully', 'vortex-ai-marketplace')));
}

/**
 * Register the stylesheets for the public-facing side of the site.
 */
public function enqueue_styles() {
    // ... your existing enqueues
    
    // Add the shortcodes stylesheet
    wp_enqueue_style(
        'vortex-shortcodes-css',
        plugin_dir_url(__FILE__) . 'css/vortex-shortcodes.css',
        array(),
        $this->version,
        'all'
    );
} 