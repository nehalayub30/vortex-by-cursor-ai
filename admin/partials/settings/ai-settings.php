<?php
/**
 * AI Settings Template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials/settings
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Add nonce verification and settings save handler
if (isset($_POST['vortex_ai_save_settings']) && check_admin_referer('vortex_ai_settings_nonce')) {
    // Sanitize and save settings
    $ai_settings = array(
        // HURAII Configuration
        'huraii_enabled' => isset($_POST['vortex_ai_huraii_enabled']),
        'huraii_model' => sanitize_text_field(isset($_POST['vortex_ai_huraii_model']) ? $_POST['vortex_ai_huraii_model'] : 'balanced'),
        'huraii_api_key' => sanitize_text_field(isset($_POST['vortex_ai_huraii_api_key']) ? $_POST['vortex_ai_huraii_api_key'] : ''),
        'huraii_context_size' => intval(isset($_POST['vortex_ai_huraii_context']) ? $_POST['vortex_ai_huraii_context'] : 4096),
        
        // CLOE Configuration
        'cloe_enabled' => isset($_POST['vortex_ai_cloe_enabled']),
        'cloe_model' => sanitize_text_field(isset($_POST['vortex_ai_cloe_model']) ? $_POST['vortex_ai_cloe_model'] : 'market-balanced'),
        'cloe_api_key' => sanitize_text_field(isset($_POST['vortex_ai_cloe_api_key']) ? $_POST['vortex_ai_cloe_api_key'] : ''),
        'cloe_prediction_horizon' => intval(isset($_POST['vortex_ai_cloe_horizon']) ? $_POST['vortex_ai_cloe_horizon'] : 90),
        
        // Business Strategist AI
        'strategist_enabled' => isset($_POST['vortex_ai_strategist_enabled']),
        'strategist_model' => sanitize_text_field(isset($_POST['vortex_ai_strategist_model']) ? $_POST['vortex_ai_strategist_model'] : 'analyst-pro'),
        'strategist_api_key' => sanitize_text_field(isset($_POST['vortex_ai_strategist_api_key']) ? $_POST['vortex_ai_strategist_api_key'] : ''),
        
        // General AI Settings
        'max_tokens_per_request' => intval(isset($_POST['vortex_ai_max_tokens']) ? $_POST['vortex_ai_max_tokens'] : 2048),
        'cache_duration' => intval(isset($_POST['vortex_ai_cache_duration']) ? $_POST['vortex_ai_cache_duration'] : 3600),
        'request_timeout' => intval(isset($_POST['vortex_ai_request_timeout']) ? $_POST['vortex_ai_request_timeout'] : 30),
        'fallback_enabled' => isset($_POST['vortex_ai_fallback_enabled']),
        'log_requests' => isset($_POST['vortex_ai_log_requests']),
        'log_retention_days' => intval(isset($_POST['vortex_ai_log_retention_days']) ? $_POST['vortex_ai_log_retention_days'] : 30),
        
        // Content Moderation
        'moderation_enabled' => isset($_POST['vortex_ai_moderation_enabled']),
        'moderation_threshold' => floatval(isset($_POST['vortex_ai_moderation_threshold']) ? $_POST['vortex_ai_moderation_threshold'] : 0.8),
        
        // Fine-tuning Settings
        'fine_tuning_enabled' => isset($_POST['vortex_ai_fine_tuning_enabled']),
        'training_data_collection' => isset($_POST['vortex_ai_data_collection']),
        'auto_improvement' => isset($_POST['vortex_ai_auto_improvement']),
        
        // External LLM API Providers
        'api_source' => sanitize_text_field(isset($_POST['vortex_ai_api_source']) ? $_POST['vortex_ai_api_source'] : 'built_in'),
        'openai_enabled' => isset($_POST['vortex_ai_openai_enabled']),
        'openai_api_key' => sanitize_text_field(isset($_POST['vortex_ai_openai_api_key']) ? $_POST['vortex_ai_openai_api_key'] : ''),
        'openai_model' => sanitize_text_field(isset($_POST['vortex_ai_openai_model']) ? $_POST['vortex_ai_openai_model'] : 'gpt-4o'),
        'anthropic_enabled' => isset($_POST['vortex_ai_anthropic_enabled']),
        'anthropic_api_key' => sanitize_text_field(isset($_POST['vortex_ai_anthropic_api_key']) ? $_POST['vortex_ai_anthropic_api_key'] : ''),
        'anthropic_model' => sanitize_text_field(isset($_POST['vortex_ai_anthropic_model']) ? $_POST['vortex_ai_anthropic_model'] : 'claude-3-opus'),
        'google_enabled' => isset($_POST['vortex_ai_google_enabled']),
        'google_api_key' => sanitize_text_field(isset($_POST['vortex_ai_google_api_key']) ? $_POST['vortex_ai_google_api_key'] : ''),
        'google_model' => sanitize_text_field(isset($_POST['vortex_ai_google_model']) ? $_POST['vortex_ai_google_model'] : 'gemini-pro'),
        
        // API Integration Settings
        'provider_priority' => sanitize_text_field(isset($_POST['vortex_ai_provider_priority']) ? $_POST['vortex_ai_provider_priority'] : 'huraii_first'),
        'task_artwork' => sanitize_text_field(isset($_POST['vortex_ai_task_artwork']) ? $_POST['vortex_ai_task_artwork'] : 'huraii'),
        'task_market' => sanitize_text_field(isset($_POST['vortex_ai_task_market']) ? $_POST['vortex_ai_task_market'] : 'cloe'),
        'task_strategy' => sanitize_text_field(isset($_POST['vortex_ai_task_strategy']) ? $_POST['vortex_ai_task_strategy'] : 'strategist'),
        'grok_enabled' => isset($_POST['vortex_ai_grok_enabled']),
        'grok_api_key' => sanitize_text_field(isset($_POST['vortex_ai_grok_api_key']) ? $_POST['vortex_ai_grok_api_key'] : ''),
        'grok_model' => sanitize_text_field(isset($_POST['vortex_ai_grok_model']) ? $_POST['vortex_ai_grok_model'] : 'grok-1')
    );
    
    // Securely store the settings
    update_option('vortex_ai_settings', $ai_settings);
    
    // Display success message
    add_settings_error(
        'vortex_messages', 
        'vortex_ai_message', 
        esc_html__('AI Settings Saved Successfully', 'vortex-ai-marketplace'), 
        'updated'
    );
}

// Get current settings with default values
$ai_settings = get_option('vortex_ai_settings', array(
    // HURAII Configuration
    'huraii_enabled' => true,
    'huraii_model' => 'balanced',
    'huraii_api_key' => '',
    'huraii_context_size' => 4096,
    
    // CLOE Configuration
    'cloe_enabled' => true,
    'cloe_model' => 'market-balanced',
    'cloe_api_key' => '',
    'cloe_prediction_horizon' => 90,
    
    // Business Strategist AI
    'strategist_enabled' => true,
    'strategist_model' => 'analyst-pro',
    'strategist_api_key' => '',
    
    // General AI Settings
    'max_tokens_per_request' => 2048,
    'cache_duration' => 3600,
    'request_timeout' => 30,
    'fallback_enabled' => true,
    'log_requests' => true,
    'log_retention_days' => 30,
    
    // Content Moderation
    'moderation_enabled' => true,
    'moderation_threshold' => 0.8,
    
    // Fine-tuning Settings
    'fine_tuning_enabled' => false,
    'training_data_collection' => true,
    'auto_improvement' => false,
    
    // External LLM API Providers
    'api_source' => 'built_in',
    'openai_enabled' => false,
    'openai_api_key' => '',
    'openai_model' => 'gpt-4o',
    'anthropic_enabled' => false,
    'anthropic_api_key' => '',
    'anthropic_model' => 'claude-3-opus',
    'google_enabled' => false,
    'google_api_key' => '',
    'google_model' => 'gemini-pro',
    
    // API Integration Settings
    'provider_priority' => 'huraii_first',
    'task_artwork' => 'huraii',
    'task_market' => 'cloe',
    'task_strategy' => 'strategist',
    'grok_enabled' => false,
    'grok_api_key' => '',
    'grok_model' => 'grok-1'
));

// HURAII model options
$huraii_models = array(
    'balanced' => __('Balanced (Default)', 'vortex-ai-marketplace'),
    'creative' => __('Creative (Artistic Focus)', 'vortex-ai-marketplace'),
    'precise' => __('Precise (Technical Focus)', 'vortex-ai-marketplace'),
    'efficient' => __('Efficient (Fast Response)', 'vortex-ai-marketplace'),
    'custom' => __('Custom Fine-tuned Model', 'vortex-ai-marketplace')
);

// CLOE model options
$cloe_models = array(
    'market-balanced' => __('Market Balanced (Default)', 'vortex-ai-marketplace'),
    'trend-analyzer' => __('Trend Analyzer', 'vortex-ai-marketplace'),
    'price-optimizer' => __('Price Optimizer', 'vortex-ai-marketplace'),
    'market-predictor' => __('Market Predictor (Advanced)', 'vortex-ai-marketplace'),
    'custom' => __('Custom Fine-tuned Model', 'vortex-ai-marketplace')
);

// Business Strategist model options
$strategist_models = array(
    'analyst-pro' => __('Analyst Pro (Default)', 'vortex-ai-marketplace'),
    'growth-advisor' => __('Growth Advisor', 'vortex-ai-marketplace'),
    'risk-assessor' => __('Risk Assessor', 'vortex-ai-marketplace'),
    'market-explorer' => __('Market Explorer', 'vortex-ai-marketplace'),
    'custom' => __('Custom Fine-tuned Model', 'vortex-ai-marketplace')
);

?>

<div class="vortex-settings-content">
    <h2><?php echo esc_html__('AI System Settings', 'vortex-ai-marketplace'); ?></h2>
    <?php settings_errors('vortex_messages'); ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('vortex_ai_settings_nonce'); ?>

        <!-- HURAII Configuration Section -->
        <div class="vortex-section">
            <h3><?php esc_html_e('HURAII - Art Intelligence', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable HURAII', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_ai_huraii_enabled" 
                                   value="1" 
                                   <?php checked($ai_settings['huraii_enabled']); ?>>
                            <?php esc_html_e('Enable HURAII art intelligence system', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('HURAII provides intelligent art analysis, creation assistance, and marketplace optimization.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="huraii-setting">
                    <th scope="row">
                        <label for="vortex_ai_huraii_model">
                            <?php esc_html_e('HURAII Model', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="vortex_ai_huraii_model" name="vortex_ai_huraii_model">
                            <?php foreach ($huraii_models as $model_id => $model_name) : ?>
                                <option value="<?php echo esc_attr($model_id); ?>" <?php selected($ai_settings['huraii_model'], $model_id); ?>>
                                    <?php echo esc_html($model_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select the HURAII model that best fits your marketplace needs.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="huraii-setting">
                    <th scope="row">
                        <label for="vortex_ai_huraii_api_key">
                            <?php esc_html_e('HURAII API Key', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                               id="vortex_ai_huraii_api_key" 
                               name="vortex_ai_huraii_api_key" 
                               value="<?php echo esc_attr($ai_settings['huraii_api_key']); ?>" 
                               class="regular-text"
                               autocomplete="off">
                        <button type="button" class="button toggle-password" data-target="vortex_ai_huraii_api_key">
                            <?php esc_html_e('Show/Hide', 'vortex-ai-marketplace'); ?>
                        </button>
                        <p class="description">
                            <?php esc_html_e('Enter your HURAII API key. Leave empty to use the integrated service.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="huraii-setting">
                    <th scope="row">
                        <label for="vortex_ai_huraii_context">
                            <?php esc_html_e('Context Size', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="vortex_ai_huraii_context" 
                               name="vortex_ai_huraii_context" 
                               value="<?php echo esc_attr($ai_settings['huraii_context_size']); ?>" 
                               min="1024" 
                               max="16384" 
                               step="1024" 
                               class="medium-text">
                        <p class="description">
                            <?php esc_html_e('Maximum context size for HURAII processing (tokens).', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- CLOE Configuration Section -->
        <div class="vortex-section">
            <h3><?php esc_html_e('CLOE - Market Intelligence', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable CLOE', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_ai_cloe_enabled" 
                                   value="1" 
                                   <?php checked($ai_settings['cloe_enabled']); ?>>
                            <?php esc_html_e('Enable CLOE market intelligence system', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('CLOE analyzes market trends, optimizes pricing, and provides business insights.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="cloe-setting">
                    <th scope="row">
                        <label for="vortex_ai_cloe_model">
                            <?php esc_html_e('CLOE Model', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="vortex_ai_cloe_model" name="vortex_ai_cloe_model">
                            <?php foreach ($cloe_models as $model_id => $model_name) : ?>
                                <option value="<?php echo esc_attr($model_id); ?>" <?php selected($ai_settings['cloe_model'], $model_id); ?>>
                                    <?php echo esc_html($model_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select the CLOE model that best fits your marketplace strategy.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="cloe-setting">
                    <th scope="row">
                        <label for="vortex_ai_cloe_api_key">
                            <?php esc_html_e('CLOE API Key', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                               id="vortex_ai_cloe_api_key" 
                               name="vortex_ai_cloe_api_key" 
                               value="<?php echo esc_attr($ai_settings['cloe_api_key']); ?>" 
                               class="regular-text"
                               autocomplete="off">
                        <button type="button" class="button toggle-password" data-target="vortex_ai_cloe_api_key">
                            <?php esc_html_e('Show/Hide', 'vortex-ai-marketplace'); ?>
                        </button>
                        <p class="description">
                            <?php esc_html_e('Enter your CLOE API key. Leave empty to use the integrated service.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="cloe-setting">
                    <th scope="row">
                        <label for="vortex_ai_cloe_horizon">
                            <?php esc_html_e('Prediction Horizon', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="vortex_ai_cloe_horizon" 
                               name="vortex_ai_cloe_horizon" 
                               value="<?php echo esc_attr($ai_settings['cloe_prediction_horizon']); ?>" 
                               min="7" 
                               max="365" 
                               class="small-text">
                        <span><?php esc_html_e('days', 'vortex-ai-marketplace'); ?></span>
                        <p class="description">
                            <?php esc_html_e('Number of days for CLOE to forecast market trends.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Business Strategist Configuration Section -->
        <div class="vortex-section">
            <h3><?php esc_html_e('Business Strategist AI', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable Strategist AI', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_ai_strategist_enabled" 
                                   value="1" 
                                   <?php checked($ai_settings['strategist_enabled']); ?>>
                            <?php esc_html_e('Enable Strategist AI', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Strategist AI provides business strategy analysis and recommendation.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="strategist-setting">
                    <th scope="row">
                        <label for="vortex_ai_strategist_model">
                            <?php esc_html_e('Strategist Model', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="vortex_ai_strategist_model" name="vortex_ai_strategist_model">
                            <?php foreach ($strategist_models as $model_id => $model_name) : ?>
                                <option value="<?php echo esc_attr($model_id); ?>" <?php selected($ai_settings['strategist_model'], $model_id); ?>>
                                    <?php echo esc_html($model_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select the Strategist model that best fits your business strategy needs.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="strategist-setting">
                    <th scope="row">
                        <label for="vortex_ai_strategist_api_key">
                            <?php esc_html_e('Strategist API Key', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                               id="vortex_ai_strategist_api_key" 
                               name="vortex_ai_strategist_api_key" 
                               value="<?php echo esc_attr($ai_settings['strategist_api_key']); ?>" 
                               class="regular-text"
                               autocomplete="off">
                        <button type="button" class="button toggle-password" data-target="vortex_ai_strategist_api_key">
                            <?php esc_html_e('Show/Hide', 'vortex-ai-marketplace'); ?>
                        </button>
                        <p class="description">
                            <?php esc_html_e('Enter your Strategist API key. Leave empty to use the integrated service.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- External LLM API Providers Section -->
        <div class="vortex-section">
            <h3><?php esc_html_e('External LLM API Providers', 'vortex-ai-marketplace'); ?></h3>
            <p class="description">
                <?php esc_html_e('Configure external Large Language Model providers to enhance the AI capabilities of your marketplace.', 'vortex-ai-marketplace'); ?>
            </p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('API Source', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="radio" 
                                   name="vortex_ai_api_source" 
                                   value="built_in" 
                                   <?php checked(isset($ai_settings['api_source']) ? $ai_settings['api_source'] : 'built_in', 'built_in'); ?>>
                            <?php esc_html_e('Use Vortex-provided API keys (recommended)', 'vortex-ai-marketplace'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="radio" 
                                   name="vortex_ai_api_source" 
                                   value="custom" 
                                   <?php checked(isset($ai_settings['api_source']) ? $ai_settings['api_source'] : 'built_in', 'custom'); ?>>
                            <?php esc_html_e('Use my own API keys', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Choose whether to use our pre-configured API keys or your own.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <div id="vortex-custom-api-settings" style="<?php echo (isset($ai_settings['api_source']) && $ai_settings['api_source'] === 'custom') ? 'display:block' : 'display:none'; ?>">
                <h4><?php esc_html_e('Available LLM Providers', 'vortex-ai-marketplace'); ?></h4>
                
                <!-- OpenAI -->
                <div class="vortex-api-provider-card">
                    <div class="vortex-api-provider-header">
                        <img src="<?php echo esc_url(plugin_dir_url(dirname(dirname(dirname(__FILE__)))) . 'admin/assets/img/openai-logo.png'); ?>" alt="OpenAI Logo">
                        <h4>OpenAI</h4>
                        <label class="vortex-toggle-switch">
                            <input type="checkbox" 
                                   name="vortex_ai_openai_enabled" 
                                   value="1" 
                                   <?php checked(isset($ai_settings['openai_enabled']) ? $ai_settings['openai_enabled'] : false); ?>>
                            <span class="vortex-toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="vortex-api-provider-settings" id="openai-settings" style="<?php echo (isset($ai_settings['openai_enabled']) && $ai_settings['openai_enabled']) ? 'display:block' : 'display:none'; ?>">
                        <div class="vortex-api-field">
                            <label for="vortex_ai_openai_api_key">
                                <?php esc_html_e('API Key', 'vortex-ai-marketplace'); ?>
                            </label>
                            <input type="password" 
                                   id="vortex_ai_openai_api_key" 
                                   name="vortex_ai_openai_api_key" 
                                   value="<?php echo esc_attr(isset($ai_settings['openai_api_key']) ? $ai_settings['openai_api_key'] : ''); ?>"
                                   class="regular-text">
                            <button type="button" class="toggle-password" data-target="vortex_ai_openai_api_key">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                        </div>
                        
                        <div class="vortex-api-field">
                            <label for="vortex_ai_openai_model">
                                <?php esc_html_e('Model', 'vortex-ai-marketplace'); ?>
                            </label>
                            <select id="vortex_ai_openai_model" name="vortex_ai_openai_model">
                                <option value="gpt-4o" <?php selected(isset($ai_settings['openai_model']) ? $ai_settings['openai_model'] : 'gpt-4o', 'gpt-4o'); ?>>
                                    <?php esc_html_e('GPT-4o', 'vortex-ai-marketplace'); ?>
                                </option>
                                <option value="gpt-4" <?php selected(isset($ai_settings['openai_model']) ? $ai_settings['openai_model'] : 'gpt-4o', 'gpt-4'); ?>>
                                    <?php esc_html_e('GPT-4', 'vortex-ai-marketplace'); ?>
                                </option>
                                <option value="gpt-3.5-turbo" <?php selected(isset($ai_settings['openai_model']) ? $ai_settings['openai_model'] : 'gpt-4o', 'gpt-3.5-turbo'); ?>>
                                    <?php esc_html_e('GPT-3.5 Turbo', 'vortex-ai-marketplace'); ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="vortex-api-actions">
                            <button type="button" class="button vortex-test-api-btn" data-provider="openai">
                                <?php esc_html_e('Test Connection', 'vortex-ai-marketplace'); ?>
                            </button>
                            <span class="vortex-api-status" id="openai-status"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Anthropic -->
                <div class="vortex-api-provider-card">
                    <div class="vortex-api-provider-header">
                        <img src="<?php echo esc_url(plugin_dir_url(dirname(dirname(dirname(__FILE__)))) . 'admin/assets/img/anthropic-logo.png'); ?>" alt="Anthropic Logo">
                        <h4>Anthropic</h4>
                        <label class="vortex-toggle-switch">
                            <input type="checkbox" 
                                   name="vortex_ai_anthropic_enabled" 
                                   value="1" 
                                   <?php checked(isset($ai_settings['anthropic_enabled']) ? $ai_settings['anthropic_enabled'] : false); ?>>
                            <span class="vortex-toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="vortex-api-provider-settings" id="anthropic-settings" style="<?php echo (isset($ai_settings['anthropic_enabled']) && $ai_settings['anthropic_enabled']) ? 'display:block' : 'display:none'; ?>">
                        <div class="vortex-api-field">
                            <label for="vortex_ai_anthropic_api_key">
                                <?php esc_html_e('API Key', 'vortex-ai-marketplace'); ?>
                            </label>
                            <input type="password" 
                                   id="vortex_ai_anthropic_api_key" 
                                   name="vortex_ai_anthropic_api_key" 
                                   value="<?php echo esc_attr(isset($ai_settings['anthropic_api_key']) ? $ai_settings['anthropic_api_key'] : ''); ?>"
                                   class="regular-text">
                            <button type="button" class="toggle-password" data-target="vortex_ai_anthropic_api_key">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                        </div>
                        
                        <div class="vortex-api-field">
                            <label for="vortex_ai_anthropic_model">
                                <?php esc_html_e('Model', 'vortex-ai-marketplace'); ?>
                            </label>
                            <select id="vortex_ai_anthropic_model" name="vortex_ai_anthropic_model">
                                <option value="claude-3-opus-20240229" <?php selected(isset($ai_settings['anthropic_model']) ? $ai_settings['anthropic_model'] : 'claude-3-opus-20240229', 'claude-3-opus-20240229'); ?>>Claude 3 Opus</option>
                                <option value="claude-3-sonnet-20240229" <?php selected(isset($ai_settings['anthropic_model']) ? $ai_settings['anthropic_model'] : 'claude-3-opus-20240229', 'claude-3-sonnet-20240229'); ?>>Claude 3 Sonnet</option>
                                <option value="claude-3-haiku-20240307" <?php selected(isset($ai_settings['anthropic_model']) ? $ai_settings['anthropic_model'] : 'claude-3-opus-20240229', 'claude-3-haiku-20240307'); ?>>Claude 3 Haiku</option>
                                <option value="claude-3.5-sonnet-20240620" <?php selected(isset($ai_settings['anthropic_model']) ? $ai_settings['anthropic_model'] : 'claude-3-opus-20240229', 'claude-3.5-sonnet-20240620'); ?>>Claude 3.5 Sonnet</option>
                                <option value="claude-3.7-sonnet-max-20240620" <?php selected(isset($ai_settings['anthropic_model']) ? $ai_settings['anthropic_model'] : 'claude-3-opus-20240229', 'claude-3.7-sonnet-max-20240620'); ?>>Claude 3.7 Sonnet Max</option>
                            </select>
                        </div>
                        
                        <div class="vortex-api-actions">
                            <button type="button" class="button vortex-test-api-btn" data-provider="anthropic">
                                <?php esc_html_e('Test Connection', 'vortex-ai-marketplace'); ?>
                            </button>
                            <span class="vortex-api-status" id="anthropic-status"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Google AI -->
                <div class="vortex-api-provider-card">
                    <div class="vortex-api-provider-header">
                        <img src="<?php echo esc_url(plugin_dir_url(dirname(dirname(dirname(__FILE__)))) . 'admin/assets/img/google-ai-logo.png'); ?>" alt="Google AI Logo">
                        <h4>Google AI</h4>
                        <label class="vortex-toggle-switch">
                            <input type="checkbox" 
                                   name="vortex_ai_google_enabled" 
                                   value="1" 
                                   <?php checked(isset($ai_settings['google_enabled']) ? $ai_settings['google_enabled'] : false); ?>>
                            <span class="vortex-toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="vortex-api-provider-settings" id="google-settings" style="<?php echo (isset($ai_settings['google_enabled']) && $ai_settings['google_enabled']) ? 'display:block' : 'display:none'; ?>">
                        <div class="vortex-api-field">
                            <label for="vortex_ai_google_api_key">
                                <?php esc_html_e('API Key', 'vortex-ai-marketplace'); ?>
                            </label>
                            <input type="password" 
                                   id="vortex_ai_google_api_key" 
                                   name="vortex_ai_google_api_key" 
                                   value="<?php echo esc_attr(isset($ai_settings['google_api_key']) ? $ai_settings['google_api_key'] : ''); ?>"
                                   class="regular-text">
                            <button type="button" class="toggle-password" data-target="vortex_ai_google_api_key">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                        </div>
                        
                        <div class="vortex-api-field">
                            <label for="vortex_ai_google_model">
                                <?php esc_html_e('Model', 'vortex-ai-marketplace'); ?>
                            </label>
                            <select id="vortex_ai_google_model" name="vortex_ai_google_model">
                                <option value="gemini-pro" <?php selected(isset($ai_settings['google_model']) ? $ai_settings['google_model'] : 'gemini-pro', 'gemini-pro'); ?>>
                                    <?php esc_html_e('Gemini Pro', 'vortex-ai-marketplace'); ?>
                                </option>
                                <option value="gemini-1.5-pro" <?php selected(isset($ai_settings['google_model']) ? $ai_settings['google_model'] : 'gemini-pro', 'gemini-1.5-pro'); ?>>
                                    <?php esc_html_e('Gemini 1.5 Pro', 'vortex-ai-marketplace'); ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="vortex-api-actions">
                            <button type="button" class="button vortex-test-api-btn" data-provider="google">
                                <?php esc_html_e('Test Connection', 'vortex-ai-marketplace'); ?>
                            </button>
                            <span class="vortex-api-status" id="google-status"></span>
                        </div>
                    </div>
                </div>

                <!-- Grok AI -->
                <div class="vortex-api-provider-card">
                    <div class="vortex-api-provider-header">
                        <img src="<?php echo esc_url(plugin_dir_url(dirname(dirname(dirname(__FILE__)))) . 'admin/assets/img/grok-logo.png'); ?>" alt="Grok Logo">
                        <h4>Grok AI</h4>
                        <label class="vortex-toggle-switch">
                            <input type="checkbox" 
                                   name="vortex_ai_grok_enabled" 
                                   <?php checked(isset($ai_settings['grok_enabled']) ? $ai_settings['grok_enabled'] : false); ?>>
                            <span class="vortex-toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="vortex-api-provider-settings" id="grok-settings" style="<?php echo (isset($ai_settings['grok_enabled']) && $ai_settings['grok_enabled']) ? 'display:block' : 'display:none'; ?>">
                        <div class="vortex-api-field">
                            <label for="vortex_ai_grok_api_key"><?php esc_html_e('API Key', 'vortex-ai-marketplace'); ?></label>
                            <input type="password" 
                                   id="vortex_ai_grok_api_key" 
                                   name="vortex_ai_grok_api_key" 
                                   value="<?php echo esc_attr(isset($ai_settings['grok_api_key']) ? $ai_settings['grok_api_key'] : ''); ?>" 
                                   class="regular-text">
                            <button type="button" class="button button-secondary toggle-password" data-target="vortex_ai_grok_api_key">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                        </div>
                        
                        <div class="vortex-api-field">
                            <label for="vortex_ai_grok_model"><?php esc_html_e('Model', 'vortex-ai-marketplace'); ?></label>
                            <select name="vortex_ai_grok_model" id="vortex_ai_grok_model">
                                <option value="grok-1" <?php selected(isset($ai_settings['grok_model']) ? $ai_settings['grok_model'] : 'grok-1', 'grok-1'); ?>>Grok-1</option>
                                <option value="grok-2" <?php selected(isset($ai_settings['grok_model']) ? $ai_settings['grok_model'] : 'grok-1', 'grok-2'); ?>>Grok-2</option>
                            </select>
                        </div>
                        
                        <div class="vortex-api-actions">
                            <button type="button" class="button button-secondary vortex-test-api-btn" data-provider="grok">
                                <?php esc_html_e('Test Connection', 'vortex-ai-marketplace'); ?>
                            </button>
                            <div id="grok-status" class="vortex-api-status"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="vortex-api-provider-integration">
                <h4><?php esc_html_e('API Integration Settings', 'vortex-ai-marketplace'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Provider Priority', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <select id="vortex_ai_provider_priority" name="vortex_ai_provider_priority">
                                <option value="huraii_first" <?php selected(isset($ai_settings['provider_priority']) ? $ai_settings['provider_priority'] : 'huraii_first', 'huraii_first'); ?>>
                                    <?php esc_html_e('HURAII First, External LLMs as Fallback', 'vortex-ai-marketplace'); ?>
                                </option>
                                <option value="external_first" <?php selected(isset($ai_settings['provider_priority']) ? $ai_settings['provider_priority'] : 'huraii_first', 'external_first'); ?>>
                                    <?php esc_html_e('External LLMs First, HURAII as Fallback', 'vortex-ai-marketplace'); ?>
                                </option>
                                <option value="task_specific" <?php selected(isset($ai_settings['provider_priority']) ? $ai_settings['provider_priority'] : 'huraii_first', 'task_specific'); ?>>
                                    <?php esc_html_e('Task-Specific (Advanced)', 'vortex-ai-marketplace'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Choose how to prioritize different AI providers for various tasks.', 'vortex-ai-marketplace'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr id="vortex-task-specific-settings" style="<?php echo (isset($ai_settings['provider_priority']) && $ai_settings['provider_priority'] === 'task_specific') ? 'display:table-row' : 'display:none'; ?>">
                        <th scope="row"><?php esc_html_e('Task Assignments', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <div class="vortex-task-assignments">
                                <div class="vortex-task-item">
                                    <label><?php esc_html_e('Artwork Generation:', 'vortex-ai-marketplace'); ?></label>
                                    <select name="vortex_ai_task_artwork">
                                        <option value="huraii" <?php selected(isset($ai_settings['task_artwork']) ? $ai_settings['task_artwork'] : 'huraii', 'huraii'); ?>>HURAII</option>
                                        <option value="openai" <?php selected(isset($ai_settings['task_artwork']) ? $ai_settings['task_artwork'] : 'huraii', 'openai'); ?>>OpenAI</option>
                                        <option value="anthropic" <?php selected(isset($ai_settings['task_artwork']) ? $ai_settings['task_artwork'] : 'huraii', 'anthropic'); ?>>Anthropic</option>
                                        <option value="google" <?php selected(isset($ai_settings['task_artwork']) ? $ai_settings['task_artwork'] : 'huraii', 'google'); ?>>Google AI</option>
                                        <option value="grok" <?php selected(isset($ai_settings['task_artwork']) ? $ai_settings['task_artwork'] : 'huraii', 'grok'); ?>>Grok AI</option>
                                    </select>
                                </div>
                                <div class="vortex-task-item">
                                    <label><?php esc_html_e('Market Analysis:', 'vortex-ai-marketplace'); ?></label>
                                    <select name="vortex_ai_task_market">
                                        <option value="cloe" <?php selected(isset($ai_settings['task_market']) ? $ai_settings['task_market'] : 'cloe', 'cloe'); ?>>CLOE</option>
                                        <option value="openai" <?php selected(isset($ai_settings['task_market']) ? $ai_settings['task_market'] : 'cloe', 'openai'); ?>>OpenAI</option>
                                        <option value="anthropic" <?php selected(isset($ai_settings['task_market']) ? $ai_settings['task_market'] : 'cloe', 'anthropic'); ?>>Anthropic</option>
                                        <option value="google" <?php selected(isset($ai_settings['task_market']) ? $ai_settings['task_market'] : 'cloe', 'google'); ?>>Google AI</option>
                                        <option value="grok" <?php selected(isset($ai_settings['task_market']) ? $ai_settings['task_market'] : 'cloe', 'grok'); ?>>Grok AI</option>
                                    </select>
                                </div>
                                <div class="vortex-task-item">
                                    <label><?php esc_html_e('Business Strategy:', 'vortex-ai-marketplace'); ?></label>
                                    <select name="vortex_ai_task_strategy">
                                        <option value="strategist" <?php selected(isset($ai_settings['task_strategy']) ? $ai_settings['task_strategy'] : 'strategist', 'strategist'); ?>>Business Strategist</option>
                                        <option value="openai" <?php selected(isset($ai_settings['task_strategy']) ? $ai_settings['task_strategy'] : 'strategist', 'openai'); ?>>OpenAI</option>
                                        <option value="anthropic" <?php selected(isset($ai_settings['task_strategy']) ? $ai_settings['task_strategy'] : 'strategist', 'anthropic'); ?>>Anthropic</option>
                                        <option value="google" <?php selected(isset($ai_settings['task_strategy']) ? $ai_settings['task_strategy'] : 'strategist', 'google'); ?>>Google AI</option>
                                        <option value="grok" <?php selected(isset($ai_settings['task_strategy']) ? $ai_settings['task_strategy'] : 'strategist', 'grok'); ?>>Grok AI</option>
                                    </select>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="vortex-submit-section">
            <input type="submit" 
                   name="vortex_ai_save_settings" 
                   class="button button-primary" 
                   value="<?php esc_attr_e('Save AI Settings', 'vortex-ai-marketplace'); ?>">
        </div>
    </form>
</div>

<style>
.vortex-section {
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.vortex-section h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.vortex-shortcode-list {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.vortex-shortcode-list th,
.vortex-shortcode-list td {
    padding: 8px;
    text-align: left;
    border: 1px solid #ddd;
}

.vortex-shortcode-list th {
    background-color: #f8f9fa;
}

.vortex-submit-section {
    margin-top: 20px;
    padding: 20px 0;
    border-top: 1px solid #ddd;
}

.number-range {
    display: flex;
    align-items: center;
}

.number-range input[type="range"] {
    flex-grow: 1;
    margin-right: 10px;
}

.number-range output {
    min-width: 40px;
    text-align: center;
}

/* Complete Styling for AI Settings Page */
.vortex-settings-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin: 20px 0;
    padding: 20px;
}

.vortex-settings-section h2 {
    margin-top: 0;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
}

.vortex-ai-dashboard {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin: 20px 0;
    padding: 20px;
}

.vortex-ai-status-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 15px;
}

.vortex-ai-status-card {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    display: flex;
    align-items: center;
    width: calc(33.33% - 14px);
    box-sizing: border-box;
}

.vortex-ai-status-card.connected {
    border-left: 4px solid #46b450;
}

.vortex-ai-status-card.disconnected {
    border-left: 4px solid #dc3232;
}

.vortex-ai-status-icon {
    margin-right: 15px;
}

.vortex-ai-status-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.vortex-ai-status-card.connected .vortex-ai-status-icon .dashicons {
    color: #46b450;
}

.vortex-ai-status-card.disconnected .vortex-ai-status-icon .dashicons {
    color: #dc3232;
}

.vortex-ai-status-info h3 {
    margin: 0 0 5px 0;
    font-size: 14px;
}

.vortex-ai-status-message {
    margin: 0;
    color: #646970;
}

.connection-result {
    display: inline-block;
    margin-left: 10px;
    vertical-align: middle;
}

.connection-result .testing {
    color: #646970;
}

.connection-result .success {
    color: #46b450;
}

.connection-result .error {
    color: #dc3232;
}

.show-hide-api-key {
    background: none;
    border: none;
    color: #2271b1;
    cursor: pointer;
    padding: 0;
    vertical-align: middle;
}

.api-key-wrapper {
    display: flex;
    align-items: center;
}

.api-key-wrapper input {
    flex-grow: 1;
    margin-right: 5px;
}

/* Network status colors */
.vortex-network-status-active {
    color: #46b450;
    font-weight: 600;
}

.vortex-network-status-inactive,
.vortex-network-status-down {
    color: #dc3232;
    font-weight: 600;
}

.vortex-network-status-maintenance {
    color: #ff6f00;
    font-weight: 600;
}

/* Responsive styling */
@media screen and (max-width: 782px) {
    .vortex-ai-status-card {
        width: 100%;
    }
}

/* Add these styles for the LLM provider cards */
.vortex-api-provider-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 15px;
    overflow: hidden;
}

.vortex-api-provider-header {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.vortex-api-provider-header img {
    width: 32px;
    height: 32px;
    margin-right: 10px;
}

.vortex-api-provider-header h4 {
    margin: 0;
    flex-grow: 1;
}

.vortex-api-provider-settings {
    padding: 15px;
}

.vortex-api-field {
    margin-bottom: 15px;
    position: relative;
}

.vortex-api-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.vortex-api-actions {
    display: flex;
    align-items: center;
}

.vortex-api-status {
    margin-left: 10px;
}

.vortex-api-status .success {
    color: #46b450;
}

.vortex-api-status .error {
    color: #dc3232;
}

.vortex-api-status .testing {
    color: #ffb900;
}

.vortex-toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.vortex-toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.vortex-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.vortex-toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .vortex-toggle-slider {
    background-color: #2271b1;
}

input:checked + .vortex-toggle-slider:before {
    transform: translateX(26px);
}

.vortex-task-assignments {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 10px;
}

.vortex-task-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.vortex-api-provider-integration {
    margin-top: 20px;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Toggle password visibility
    $('.toggle-password').on('click', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        var field = $('#' + target);
        var fieldType = field.attr('type');
        field.attr('type', fieldType === 'password' ? 'text' : 'password');
    });
    
    // Toggle HURAII settings visibility
    $('input[name="vortex_ai_huraii_enabled"]').on('change', function() {
        $('.huraii-setting').toggle($(this).is(':checked'));
    }).trigger('change');
    
    // Toggle CLOE settings visibility
    $('input[name="vortex_ai_cloe_enabled"]').on('change', function() {
        $('.cloe-setting').toggle($(this).is(':checked'));
    }).trigger('change');
    
    // Toggle Strategist settings visibility
    $('input[name="vortex_ai_strategist_enabled"]').on('change', function() {
        $('.strategist-setting').toggle($(this).is(':checked'));
    }).trigger('change');
    
    // Toggle fine-tuning settings visibility
    $('input[name="vortex_ai_fine_tuning_enabled"]').on('change', function() {
        $('.fine-tuning-setting').toggle($(this).is(':checked'));
    }).trigger('change');
    
    // Range sliders
    $('input[type="range"]').on('input', function() {
        $(this).next('output').val($(this).val());
    }).trigger('input');
    
    // Form change tracking
    var formChanged = false;
    
    $('form input, form select').on('change', function() {
        formChanged = true;
    });
    
    $('form').on('submit', function() {
        window.onbeforeunload = null;
        return true;
    });
    
    window.onbeforeunload = function() {
        if (formChanged) {
            return '<?php echo esc_js(__('You have unsaved changes. Are you sure you want to leave?', 'vortex-ai-marketplace')); ?>';
        }
    };

    // External LLM API Provider Settings
    $('input[name="vortex_ai_api_source"]').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#vortex-custom-api-settings').show();
        } else {
            $('#vortex-custom-api-settings').hide();
        }
    });
    
    // Toggle provider settings visibility
    $('input[name="vortex_ai_openai_enabled"]').on('change', function() {
        $('#openai-settings').toggle($(this).is(':checked'));
    });
    
    $('input[name="vortex_ai_anthropic_enabled"]').on('change', function() {
        $('#anthropic-settings').toggle($(this).is(':checked'));
    });
    
    $('input[name="vortex_ai_google_enabled"]').on('change', function() {
        $('#google-settings').toggle($(this).is(':checked'));
    });
    
    // Show/hide task-specific settings
    $('#vortex_ai_provider_priority').on('change', function() {
        $('#vortex-task-specific-settings').toggle($(this).val() === 'task_specific');
    });
    
    // Test API connection
    $('.vortex-test-api-btn').on('click', function() {
        var provider = $(this).data('provider');
        var status_element = $('#' + provider + '-status');
        var api_key = $('#vortex_ai_' + provider + '_api_key').val();
        var api_url = '';
        
        if (provider === 'openai') {
            api_url = 'https://api.openai.com/v1';
        } else if (provider === 'anthropic') {
            api_url = 'https://api.anthropic.com/v1';
        } else if (provider === 'google') {
            api_url = 'https://generativelanguage.googleapis.com/v1';
        }
        
        if (!api_key) {
            status_element.html('<span class="error"><?php echo esc_js(__('API key is required', 'vortex-ai-marketplace')); ?></span>');
            return;
        }
        
        status_element.html('<span class="testing"><?php echo esc_js(__('Testing connection...', 'vortex-ai-marketplace')); ?></span>');
        
        // Make AJAX request to test connection
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_test_ai_connection',
                service: provider,
                api_key: api_key,
                api_url: api_url,
                nonce: '<?php echo wp_create_nonce('vortex_test_ai_connection'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    status_element.html('<span class="success">' + response.data + '</span>');
                } else {
                    status_element.html('<span class="error">' + response.data + '</span>');
                }
            },
            error: function() {
                status_element.html('<span class="error"><?php echo esc_js(__('Connection test failed', 'vortex-ai-marketplace')); ?></span>');
            }
        });
    });

    // Toggle Grok settings visibility
    $('input[name="vortex_ai_grok_enabled"]').on('change', function() {
        $('#grok-settings').toggle($(this).is(':checked'));
    });
});
</script> 