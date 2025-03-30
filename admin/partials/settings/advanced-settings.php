<?php
/**
 * Advanced Settings Template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials/settings
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Add nonce verification and settings save handler
if (isset($_POST['vortex_advanced_save_settings']) && check_admin_referer('vortex_advanced_settings_nonce')) {
    // Sanitize and save settings
    $advanced_settings = array(
        // Performance Settings
        'cache_enabled' => isset($_POST['vortex_advanced_cache_enabled']),
        'cache_lifetime' => intval(isset($_POST['vortex_advanced_cache_lifetime']) ? $_POST['vortex_advanced_cache_lifetime'] : 3600),
        'lazy_loading' => isset($_POST['vortex_advanced_lazy_loading']),
        'image_optimization' => isset($_POST['vortex_advanced_image_optimization']),
        'minify_assets' => isset($_POST['vortex_advanced_minify_assets']),
        
        // Security Settings
        'rate_limiting' => isset($_POST['vortex_advanced_rate_limiting']),
        'max_requests_per_minute' => intval(isset($_POST['vortex_advanced_max_requests']) ? $_POST['vortex_advanced_max_requests'] : 60),
        'api_key_rotation' => isset($_POST['vortex_advanced_api_key_rotation']),
        'key_rotation_days' => intval(isset($_POST['vortex_advanced_key_rotation_days']) ? $_POST['vortex_advanced_key_rotation_days'] : 90),
        'enable_cors' => isset($_POST['vortex_advanced_enable_cors']),
        'allowed_origins' => sanitize_text_field(isset($_POST['vortex_advanced_allowed_origins']) ? $_POST['vortex_advanced_allowed_origins'] : ''),
        
        // Developer Settings
        'debug_mode' => isset($_POST['vortex_advanced_debug_mode']),
        'debug_level' => sanitize_text_field(isset($_POST['vortex_advanced_debug_level']) ? $_POST['vortex_advanced_debug_level'] : 'error'),
        'api_logging' => isset($_POST['vortex_advanced_api_logging']),
        'log_retention_days' => intval(isset($_POST['vortex_advanced_log_retention']) ? $_POST['vortex_advanced_log_retention'] : 30),
        'enable_rest_api' => isset($_POST['vortex_advanced_enable_rest_api']),
        'custom_endpoints' => isset($_POST['vortex_advanced_custom_endpoints']),
        
        // Integration Settings
        'enable_webhooks' => isset($_POST['vortex_advanced_enable_webhooks']),
        'webhook_url' => esc_url_raw(isset($_POST['vortex_advanced_webhook_url']) ? $_POST['vortex_advanced_webhook_url'] : ''),
        'webhook_secret' => sanitize_text_field(isset($_POST['vortex_advanced_webhook_secret']) ? $_POST['vortex_advanced_webhook_secret'] : ''),
        
        // AI Processing Settings
        'ai_processing_priority' => sanitize_text_field(isset($_POST['vortex_advanced_ai_priority']) ? $_POST['vortex_advanced_ai_priority'] : 'balanced'),
        'ai_batch_processing' => isset($_POST['vortex_advanced_ai_batch']),
        'ai_max_batch_size' => intval(isset($_POST['vortex_advanced_ai_batch_size']) ? $_POST['vortex_advanced_ai_batch_size'] : 10),
        'ai_threading' => isset($_POST['vortex_advanced_ai_threading']),
        'ai_max_threads' => intval(isset($_POST['vortex_advanced_ai_max_threads']) ? $_POST['vortex_advanced_ai_max_threads'] : 4)
    );
    
    // Securely store the settings
    update_option('vortex_advanced_settings', $advanced_settings);
    
    // Display success message
    add_settings_error(
        'vortex_messages', 
        'vortex_advanced_message', 
        esc_html__('Advanced Settings Saved Successfully', 'vortex-ai-marketplace'), 
        'updated'
    );
}

// Get current settings with default values
$advanced_settings = get_option('vortex_advanced_settings', array(
    // Performance Settings
    'cache_enabled' => true,
    'cache_lifetime' => 3600,
    'lazy_loading' => true,
    'image_optimization' => true,
    'minify_assets' => false,
    
    // Security Settings
    'rate_limiting' => true,
    'max_requests_per_minute' => 60,
    'api_key_rotation' => false,
    'key_rotation_days' => 90,
    'enable_cors' => false,
    'allowed_origins' => '',
    
    // Developer Settings
    'debug_mode' => false,
    'debug_level' => 'error',
    'api_logging' => true,
    'log_retention_days' => 30,
    'enable_rest_api' => true,
    'custom_endpoints' => false,
    
    // Integration Settings
    'enable_webhooks' => false,
    'webhook_url' => '',
    'webhook_secret' => '',
    
    // AI Processing Settings
    'ai_processing_priority' => 'balanced',
    'ai_batch_processing' => true,
    'ai_max_batch_size' => 10,
    'ai_threading' => false,
    'ai_max_threads' => 4
));

// Debug level options
$debug_levels = array(
    'error' => __('Errors Only', 'vortex-ai-marketplace'),
    'warning' => __('Warnings & Errors', 'vortex-ai-marketplace'),
    'info' => __('Info, Warnings & Errors', 'vortex-ai-marketplace'),
    'debug' => __('All (Debug)', 'vortex-ai-marketplace')
);

// AI processing priority options
$ai_priorities = array(
    'speed' => __('Speed (Faster, lower quality)', 'vortex-ai-marketplace'),
    'balanced' => __('Balanced (Default)', 'vortex-ai-marketplace'),
    'quality' => __('Quality (Slower, higher quality)', 'vortex-ai-marketplace'),
    'adaptive' => __('Adaptive (Context-aware)', 'vortex-ai-marketplace')
);

?>

<div class="vortex-settings-content">
    <h2><?php echo esc_html__('Advanced Settings', 'vortex-ai-marketplace'); ?></h2>
    <?php settings_errors('vortex_messages'); ?>
    
    <div class="vortex-advanced-warning">
        <p>
            <strong><?php esc_html_e('Warning:', 'vortex-ai-marketplace'); ?></strong> 
            <?php esc_html_e('These settings are intended for advanced users and developers. Incorrect configuration may impact your marketplace performance and stability.', 'vortex-ai-marketplace'); ?>
        </p>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('vortex_advanced_settings_nonce'); ?>

        <!-- Performance Settings Section -->
        <div class="vortex-section">
            <h3><?php esc_html_e('Performance Optimization', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Caching', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_advanced_cache_enabled" 
                                   value="1" 
                                   <?php checked($advanced_settings['cache_enabled']); ?>>
                            <?php esc_html_e('Enable result caching', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Cache AI responses and marketplace data to improve performance.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="cache-setting">
                    <th scope="row">
                        <label for="vortex_advanced_cache_lifetime">
                            <?php esc_html_e('Cache Lifetime', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="vortex_advanced_cache_lifetime" 
                               name="vortex_advanced_cache_lifetime" 
                               value="<?php echo esc_attr($advanced_settings['cache_lifetime']); ?>" 
                               min="300" 
                               step="300" 
                               class="small-text">
                        <span><?php esc_html_e('seconds', 'vortex-ai-marketplace'); ?></span>
                        <p class="description">
                            <?php esc_html_e('How long to keep items in cache (in seconds).', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Asset Optimization', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_advanced_lazy_loading" 
                                   value="1" 
                                   <?php checked($advanced_settings['lazy_loading']); ?>>
                            <?php esc_html_e('Enable lazy loading for images', 'vortex-ai-marketplace'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_advanced_image_optimization" 
                                   value="1" 
                                   <?php checked($advanced_settings['image_optimization']); ?>>
                            <?php esc_html_e('Enable automatic image optimization', 'vortex-ai-marketplace'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_advanced_minify_assets" 
                                   value="1" 
                                   <?php checked($advanced_settings['minify_assets']); ?>>
                            <?php esc_html_e('Minify CSS and JavaScript assets', 'vortex-ai-marketplace'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Security Settings Section -->
        <div class="vortex-section">
            <h3><?php esc_html_e('Security Settings', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Rate Limiting', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_advanced_rate_limiting" 
                                   value="1" 
                                   <?php checked($advanced_settings['rate_limiting']); ?>>
                            <?php esc_html_e('Enable API rate limiting', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Limit the number of requests to prevent abuse.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="rate-limit-setting">
                    <th scope="row">
                        <label for="vortex_advanced_max_requests">
                            <?php esc_html_e('Max Requests', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="vortex_advanced_max_requests" 
                               name="vortex_advanced_max_requests" 
                               value="<?php echo esc_attr($advanced_settings['max_requests_per_minute']); ?>" 
                               min="10" 
                               class="small-text">
                        <span><?php esc_html_e('requests per minute', 'vortex-ai-marketplace'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('API Key Security', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_advanced_api_key_rotation" 
                                   value="1" 
                                   <?php checked($advanced_settings['api_key_rotation']); ?>>
                            <?php esc_html_e('Enable automatic API key rotation', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Automatically generate new API keys periodically.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="key-rotation-setting">
                    <th scope="row">
                        <label for="vortex_advanced_key_rotation_days">
                            <?php esc_html_e('Rotation Period', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="vortex_advanced_key_rotation_days" 
                               name="vortex_advanced_key_rotation_days" 
                               value="<?php echo esc_attr($advanced_settings['key_rotation_days']); ?>" 
                               min="30" 
                               class="small-text">
                        <span><?php esc_html_e('days', 'vortex-ai-marketplace'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('CORS Settings', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_advanced_enable_cors" 
                                   value="1" 
                                   <?php checked($advanced_settings['enable_cors']); ?>>
                            <?php esc_html_e('Enable CORS for external access', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Allow Cross-Origin Resource Sharing for API endpoints.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="cors-setting">
                    <th scope="row">
                        <label for="vortex_advanced_allowed_origins">
                            <?php esc_html_e('Allowed Origins', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               id="vortex_advanced_allowed_origins" 
                               name="vortex_advanced_allowed_origins" 
                               value="<?php echo esc_attr($advanced_settings['allowed_origins']); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Comma-separated list of domains allowed to access the API (e.g., https://example.com).', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Developer Settings Section -->
        <div class="vortex-section">
            <h3><?php esc_html_e('Developer Options', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Debug Mode', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_advanced_debug_mode" 
                                   value="1" 
                                   <?php checked($advanced_settings['debug_mode']); ?>>
                            <?php esc_html_e('Enable debug mode', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Enables detailed error reporting and debugging information.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="debug-setting">
                    <th scope="row">
                        <label for="vortex_advanced_debug_level">
                            <?php esc_html_e('Debug Level', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="vortex_advanced_debug_level" name="vortex_advanced_debug_level">
                            <?php foreach ($debug_levels as $level_id => $level_name) : ?>
                                <option value="<?php echo esc_attr($level_id); ?>" <?php selected($advanced_settings['debug_level'], $level_id); ?>>
                                    <?php echo esc_html($level_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('API Logging', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_advanced_api_logging" 
                                   value="1" 
                                   <?php checked($advanced_settings['api_logging']); ?>>
                            <?php esc_html_e('Enable API request logging', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Log all API requests and responses for debugging.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="logging-setting">
                    <th scope="row">
                        <label for="vortex_advanced_log_retention">
                            <?php esc_html_e('Log Retention', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="vortex_advanced_log_retention" 
                               name="vortex_advanced_log_retention" 
                               value="<?php echo esc_attr($advanced_settings['log_retention_days']); ?>" 
                               min="1" 
                               max="90" 
                               class="small-text">
                        <span><?php esc_html_e('days', 'vortex-ai-marketplace'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('REST API', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_advanced_enable_rest_api" 
                                   value="1" 
                                   <?php checked($advanced_settings['enable_rest_api']); ?>>
                            <?php esc_html_e('Enable REST API endpoints', 'vortex-ai-marketplace'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_advanced_custom_endpoints" 
                                   value="1" 
                                   <?php checked($advanced_settings['custom_endpoints']); ?>>
                            <?php esc_html_e('Enable custom endpoints', 'vortex-ai-marketplace'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Integration Settings Section -->
        <div class="vortex-section">
            <h3><?php esc_html_e('Integration', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Webhooks', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_advanced_enable_webhooks" 
                                   value="1" 
                                   <?php checked($advanced_settings['enable_webhooks']); ?>>
                            <?php esc_html_e('Enable webhooks for marketplace events', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Send notifications to external services on marketplace events.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="webhook-setting">
                    <th scope="row">
                        <label for="vortex_advanced_webhook_url">
                            <?php esc_html_e('Webhook URL', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="url" 
                               id="vortex_advanced_webhook_url" 
                               name="vortex_advanced_webhook_url" 
                               value="<?php echo esc_attr($advanced_settings['webhook_url']); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr class="webhook-setting">
                    <th scope="row">
                        <label for="vortex_advanced_webhook_secret">
                            <?php esc_html_e('Webhook Secret', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                               id="vortex_advanced_webhook_secret" 
                               name="vortex_advanced_webhook_secret" 
                               value="<?php echo esc_attr($advanced_settings['webhook_secret']); ?>" 
                               class="regular-text"
                               autocomplete="off">
                        <button type="button" class="button toggle-password" data-target="vortex_advanced_webhook_secret">
                            <?php esc_html_e('Show/Hide', 'vortex-ai-marketplace'); ?>
                        </button>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- AI Processing Settings Section -->
        <div class="vortex-section">
            <h3><?php esc_html_e('AI Processing', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="vortex_advanced_ai_priority">
                            <?php esc_html_e('Processing Priority', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="vortex_advanced_ai_priority" name="vortex_advanced_ai_priority">
                            <?php foreach ($ai_priorities as $priority_id => $priority_name) : ?>
                                <option value="<?php echo esc_attr($priority_id); ?>" <?php selected($advanced_settings['ai_processing_priority'], $priority_id); ?>>
                                    <?php echo esc_html($priority_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select the processing priority for AI operations.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Batch Processing', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_advanced_ai_batch" 
                                   value="1" 
                                   <?php checked($advanced_settings['ai_batch_processing']); ?>>
                            <?php esc_html_e('Enable batch processing of AI tasks', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Process multiple AI tasks in batches for better performance.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="batch-setting">
                    <th scope="row">
                        <label for="vortex_advanced_ai_batch_size">
                            <?php esc_html_e('Max Batch Size', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="vortex_advanced_ai_batch_size" 
                               name="vortex_advanced_ai_batch_size" 
                               value="<?php echo esc_attr($advanced_settings['ai_max_batch_size']); ?>" 
                               min="1" 
                               max="50" 
                               class="small-text">
                        <p class="description">
                            <?php esc_html_e('Maximum number of tasks to process in a single batch.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Threading', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_advanced_ai_threading" 
                                   value="1" 
                                   <?php checked($advanced_settings['ai_threading']); ?>>
                            <?php esc_html_e('Enable multi-threaded AI processing', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Process AI tasks using multiple threads (may increase server load).', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="threading-setting">
                    <th scope="row">
                        <label for="vortex_advanced_ai_max_threads">
                            <?php esc_html_e('Max Threads', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="vortex_advanced_ai_max_threads" 
                               name="vortex_advanced_ai_max_threads" 
                               value="<?php echo esc_attr($advanced_settings['ai_max_threads']); ?>" 
                               min="2" 
                               max="16" 
                               class="small-text">
                        <p class="description">
                            <?php esc_html_e('Maximum number of concurrent threads for AI processing.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="vortex-shortcode-reference">
            <h3><?php esc_html_e('Advanced Shortcodes Reference', 'vortex-ai-marketplace'); ?></h3>
            <table class="vortex-shortcode-list">
                <tr>
                    <th><?php esc_html_e('Shortcode', 'vortex-ai-marketplace'); ?></th>
                    <th><?php esc_html_e('Description', 'vortex-ai-marketplace'); ?></th>
                    <th><?php esc_html_e('Parameters', 'vortex-ai-marketplace'); ?></th>
                </tr>
                <tr>
                    <td><code>[vortex_system_status]</code></td>
                    <td><?php esc_html_e('Displays system status information', 'vortex-ai-marketplace'); ?></td>
                    <td><code>show_api</code>, <code>show_cache</code>, <code>format</code></td>
                </tr>
                <tr>
                    <td><code>[vortex_performance_metrics]</code></td>
                    <td><?php esc_html_e('Shows performance metrics', 'vortex-ai-marketplace'); ?></td>
                    <td><code>period</code>, <code>metric</code>, <code>display</code></td>
                </tr>
                <tr>
                    <td><code>[vortex_debug_info]</code></td>
                    <td><?php esc_html_e('Shows debug information (admin only)', 'vortex-ai-marketplace'); ?></td>
                    <td><code>section</code>, <code>level</code>, <code>format</code></td>
                </tr>
                <tr>
                    <td><code>[vortex_system_tools]</code></td>
                    <td><?php esc_html_e('Provides system maintenance tools', 'vortex-ai-marketplace'); ?></td>
                    <td><code>tool</code>, <code>options</code>, <code>mode</code></td>
                </tr>
            </table>
        </div>

        <div class="vortex-submit-section">
            <input type="submit" 
                   name="vortex_advanced_save_settings" 
                   class="button button-primary" 
                   value="<?php esc_attr_e('Save Advanced Settings', 'vortex-ai-marketplace'); ?>">
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

.vortex-advanced-warning {
    background: #fff8e5;
    border-left: 4px solid #ffb900;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin: 20px 0;
    padding: 12px;
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
    
    // Toggle settings visibility based on parent option
    $('input[name="vortex_advanced_cache_enabled"]').on('change', function() {
        $('.cache-setting').toggle($(this).is(':checked'));
    }).trigger('change');
    
    $('input[name="vortex_advanced_rate_limiting"]').on('change', function() {
        $('.rate-limit-setting').toggle($(this).is(':checked'));
    }).trigger('change');
    
    $('input[name="vortex_advanced_api_key_rotation"]').on('change', function() {
        $('.key-rotation-setting').toggle($(this).is(':checked'));
    }).trigger('change');
    
    $('input[name="vortex_advanced_enable_cors"]').on('change', function() {
        $('.cors-setting').toggle($(this).is(':checked'));
    }).trigger('change');
    
    $('input[name="vortex_advanced_debug_mode"]').on('change', function() {
        $('.debug-setting').toggle($(this).is(':checked'));
    }).trigger('change');
    
    $('input[name="vortex_advanced_api_logging"]').on('change', function() {
        $('.logging-setting').toggle($(this).is(':checked'));
    }).trigger('change');
    
    $('input[name="vortex_advanced_enable_webhooks"]').on('change', function() {
        $('.webhook-setting').toggle($(this).is(':checked'));
    }).trigger('change');
    
    $('input[name="vortex_advanced_ai_batch"]').on('change', function() {
        $('.batch-setting').toggle($(this).is(':checked'));
    }).trigger('change');
    
    $('input[name="vortex_advanced_ai_threading"]').on('change', function() {
        $('.threading-setting').toggle($(this).is(':checked'));
    }).trigger('change');
    
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
});
</script> 