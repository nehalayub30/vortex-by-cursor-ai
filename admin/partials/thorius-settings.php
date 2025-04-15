<?php
/**
 * Thorius AI Settings Page
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get saved settings with defaults
$settings = get_option('vortex_thorius_settings', array(
    'openai_api_key' => '',
    'stability_api_key' => '',
    'enable_deep_learning' => 'yes',
    'learning_rate' => 0.85,
    'enable_cloe' => false,
    'enable_huraii' => false,
    'enable_strategist' => false,
    'max_tokens' => 2048,
    'temperature' => 0.7,
    'default_model' => 'gpt-4',
    'context_retention_days' => 30,
    'enable_analytics' => 'yes',
    'privacy_mode' => 'standard',
    'safe_mode' => 'yes',
    'emergency_shutdown' => 'no',
));

// Process form submission
if (isset($_POST['vortex_thorius_settings_submit']) && check_admin_referer('vortex_thorius_settings_nonce')) {
    $new_settings = array(
        'openai_api_key' => sanitize_text_field($_POST['openai_api_key'] ?? $settings['openai_api_key']),
        'stability_api_key' => sanitize_text_field($_POST['stability_api_key'] ?? $settings['stability_api_key']),
        'enable_deep_learning' => sanitize_text_field($_POST['enable_deep_learning'] ?? 'no'),
        'learning_rate' => floatval($_POST['learning_rate'] ?? $settings['learning_rate']),
        'enable_cloe' => isset($_POST['enable_cloe']) ? true : false,
        'enable_huraii' => isset($_POST['enable_huraii']) ? true : false,
        'enable_strategist' => isset($_POST['enable_strategist']) ? true : false,
        'max_tokens' => absint($_POST['max_tokens'] ?? $settings['max_tokens']),
        'temperature' => floatval($_POST['temperature'] ?? $settings['temperature']),
        'default_model' => sanitize_text_field($_POST['default_model'] ?? $settings['default_model']),
        'context_retention_days' => absint($_POST['context_retention_days'] ?? $settings['context_retention_days']),
        'enable_analytics' => sanitize_text_field($_POST['enable_analytics'] ?? 'no'),
        'privacy_mode' => sanitize_text_field($_POST['privacy_mode'] ?? 'standard'),
        'safe_mode' => sanitize_text_field($_POST['safe_mode'] ?? 'yes'),
        'emergency_shutdown' => sanitize_text_field($_POST['emergency_shutdown'] ?? 'no'),
    );
    
    // Update settings
    update_option('vortex_thorius_settings', $new_settings);
    
    // Update individual agent enable options for compatibility
    update_option('vortex_thorius_enable_cloe', $new_settings['enable_cloe']);
    update_option('vortex_thorius_enable_huraii', $new_settings['enable_huraii']);
    update_option('vortex_thorius_enable_strategist', $new_settings['enable_strategist']);
    
    // Update API keys for individual services
    update_option('vortex_thorius_openai_key', $new_settings['openai_api_key']);
    update_option('vortex_thorius_stability_key', $new_settings['stability_api_key']);
    
    $settings = $new_settings;
    
    // Display success message
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Thorius AI settings saved successfully.', 'vortex-ai-marketplace') . '</p></div>';
}

// Get available AI models
$available_models = array(
    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
    'gpt-4' => 'GPT-4',
    'gpt-4-turbo' => 'GPT-4 Turbo',
    'claude-instant' => 'Claude Instant',
    'claude-2' => 'Claude 2',
);

?>

<div class="wrap vortex-thorius-settings">
    <h1><?php esc_html_e('Thorius AI Settings', 'vortex-ai-marketplace'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('vortex_thorius_settings_nonce'); ?>
        
        <div class="thorius-tabs">
            <div class="thorius-tab-navigation">
                <a href="#general-settings" class="thorius-tab active"><?php esc_html_e('General Settings', 'vortex-ai-marketplace'); ?></a>
                <a href="#api-settings" class="thorius-tab"><?php esc_html_e('API Settings', 'vortex-ai-marketplace'); ?></a>
                <a href="#agent-settings" class="thorius-tab"><?php esc_html_e('Agent Settings', 'vortex-ai-marketplace'); ?></a>
                <a href="#advanced-settings" class="thorius-tab"><?php esc_html_e('Advanced Settings', 'vortex-ai-marketplace'); ?></a>
                <a href="#security-settings" class="thorius-tab"><?php esc_html_e('Security & Privacy', 'vortex-ai-marketplace'); ?></a>
            </div>
            
            <!-- General Settings -->
            <div id="general-settings" class="thorius-tab-content active">
                <h2><?php esc_html_e('General Settings', 'vortex-ai-marketplace'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="default_model"><?php esc_html_e('Default AI Model', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="default_model" id="default_model">
                                <?php foreach ($available_models as $model_id => $model_name) : ?>
                                    <option value="<?php echo esc_attr($model_id); ?>" <?php selected($settings['default_model'], $model_id); ?>>
                                        <?php echo esc_html($model_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Select the default AI model for Thorius to use.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="max_tokens"><?php esc_html_e('Max Tokens', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="max_tokens" id="max_tokens" value="<?php echo esc_attr($settings['max_tokens']); ?>" min="256" max="8192">
                            <p class="description"><?php esc_html_e('Maximum tokens per request. Higher values allow for longer responses but use more resources.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="temperature"><?php esc_html_e('Temperature', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="range" name="temperature" id="temperature" min="0.1" max="1.0" step="0.1" value="<?php echo esc_attr($settings['temperature']); ?>" class="thorius-range-slider">
                            <span class="thorius-range-value"><?php echo esc_html($settings['temperature']); ?></span>
                            <p class="description"><?php esc_html_e('Controls creativity level. Lower values are more deterministic, higher values more creative.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="context_retention_days"><?php esc_html_e('Context Retention', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="context_retention_days" id="context_retention_days" value="<?php echo esc_attr($settings['context_retention_days']); ?>" min="1" max="90">
                            <p class="description"><?php esc_html_e('Number of days to retain user context history.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- API Settings -->
            <div id="api-settings" class="thorius-tab-content">
                <h2><?php esc_html_e('API Settings', 'vortex-ai-marketplace'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="openai_api_key"><?php esc_html_e('OpenAI API Key', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="password" name="openai_api_key" id="openai_api_key" value="<?php echo esc_attr($settings['openai_api_key']); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Enter your OpenAI API key for GPT models.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="stability_api_key"><?php esc_html_e('Stability API Key', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="password" name="stability_api_key" id="stability_api_key" value="<?php echo esc_attr($settings['stability_api_key']); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Enter your Stability API key for image generation.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Agent Settings -->
            <div id="agent-settings" class="thorius-tab-content">
                <h2><?php esc_html_e('Agent Settings', 'vortex-ai-marketplace'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Enable Agents', 'vortex-ai-marketplace'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><?php esc_html_e('Enable Agents', 'vortex-ai-marketplace'); ?></legend>
                                
                                <label>
                                    <input type="checkbox" name="enable_cloe" value="1" <?php checked($settings['enable_cloe'], true); ?>>
                                    <?php esc_html_e('Enable CLOE (Art Discovery and Curation Agent)', 'vortex-ai-marketplace'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="enable_huraii" value="1" <?php checked($settings['enable_huraii'], true); ?>>
                                    <?php esc_html_e('Enable HURAII (AI Image Generation Agent)', 'vortex-ai-marketplace'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="enable_strategist" value="1" <?php checked($settings['enable_strategist'], true); ?>>
                                    <?php esc_html_e('Enable Business Strategist (Market Analysis Agent)', 'vortex-ai-marketplace'); ?>
                                </label><br>
                                
                                <p class="description"><?php esc_html_e('Select which AI agents to enable in the system.', 'vortex-ai-marketplace'); ?></p>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_deep_learning"><?php esc_html_e('Enable Deep Learning', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="enable_deep_learning" id="enable_deep_learning">
                                <option value="yes" <?php selected($settings['enable_deep_learning'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['enable_deep_learning'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Enable Thorius and other agents to learn from interactions and improve over time.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="learning_rate"><?php esc_html_e('Learning Rate', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="range" name="learning_rate" id="learning_rate" min="0.1" max="1.0" step="0.05" value="<?php echo esc_attr($settings['learning_rate']); ?>" class="thorius-range-slider">
                            <span class="thorius-range-value"><?php echo esc_html($settings['learning_rate']); ?></span>
                            <p class="description"><?php esc_html_e('Adjust how quickly AI adapts to new patterns (higher values = faster adaptation).', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Advanced Settings -->
            <div id="advanced-settings" class="thorius-tab-content">
                <h2><?php esc_html_e('Advanced Settings', 'vortex-ai-marketplace'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_analytics"><?php esc_html_e('Enable Analytics', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="enable_analytics" id="enable_analytics">
                                <option value="yes" <?php selected($settings['enable_analytics'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['enable_analytics'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Enable collection of anonymous usage data for better AI training.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="emergency_shutdown"><?php esc_html_e('Emergency Shutdown', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="emergency_shutdown" id="emergency_shutdown">
                                <option value="no" <?php selected($settings['emergency_shutdown'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                                <option value="yes" <?php selected($settings['emergency_shutdown'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Emergency shutdown of all AI functions. Use only in case of critical issues.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <div class="thorius-advanced-actions">
                    <button type="button" id="reset-thorius-data" class="button"><?php esc_html_e('Reset All Thorius Data', 'vortex-ai-marketplace'); ?></button>
                    <button type="button" id="repair-database" class="button"><?php esc_html_e('Repair Database Tables', 'vortex-ai-marketplace'); ?></button>
                    <p class="description"><?php esc_html_e('Warning: These actions cannot be undone!', 'vortex-ai-marketplace'); ?></p>
                </div>
            </div>
            
            <!-- Security & Privacy -->
            <div id="security-settings" class="thorius-tab-content">
                <h2><?php esc_html_e('Security & Privacy Settings', 'vortex-ai-marketplace'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="privacy_mode"><?php esc_html_e('Privacy Mode', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="privacy_mode" id="privacy_mode">
                                <option value="standard" <?php selected($settings['privacy_mode'], 'standard'); ?>><?php esc_html_e('Standard', 'vortex-ai-marketplace'); ?></option>
                                <option value="enhanced" <?php selected($settings['privacy_mode'], 'enhanced'); ?>><?php esc_html_e('Enhanced', 'vortex-ai-marketplace'); ?></option>
                                <option value="maximum" <?php selected($settings['privacy_mode'], 'maximum'); ?>><?php esc_html_e('Maximum', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Control how user data is handled. Enhanced and Maximum modes may limit some AI functionality.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="safe_mode"><?php esc_html_e('Safe Mode', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="safe_mode" id="safe_mode">
                                <option value="yes" <?php selected($settings['safe_mode'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['safe_mode'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Enable content filtering for all AI-generated responses.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" name="vortex_thorius_settings_submit" class="button button-primary" value="<?php esc_attr_e('Save Thorius Settings', 'vortex-ai-marketplace'); ?>">
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.thorius-tab').on('click', function(e) {
        e.preventDefault();
        
        // Update active tab
        $('.thorius-tab').removeClass('active');
        $(this).addClass('active');
        
        // Show target content
        var target = $(this).attr('href');
        $('.thorius-tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Range slider value display
    $('.thorius-range-slider').on('input', function() {
        $(this).next('.thorius-range-value').text($(this).val());
    });
    
    // Reset data confirmation
    $('#reset-thorius-data').on('click', function() {
        if (confirm('<?php echo esc_js(__('WARNING: This will delete ALL Thorius data including user sessions, interaction history, and learning data. This action cannot be undone. Are you sure you want to proceed?', 'vortex-ai-marketplace')); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_reset_thorius_data',
                    nonce: '<?php echo wp_create_nonce('vortex_thorius_reset_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php echo esc_js(__('Thorius data has been reset successfully.', 'vortex-ai-marketplace')); ?>');
                        location.reload();
                    } else {
                        alert('<?php echo esc_js(__('Error: ', 'vortex-ai-marketplace')); ?>' + response.data.message);
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('Server error while resetting data.', 'vortex-ai-marketplace')); ?>');
                }
            });
        }
    });
    
    // Repair database confirmation
    $('#repair-database').on('click', function() {
        if (confirm('<?php echo esc_js(__('This will attempt to repair Thorius database tables. Continue?', 'vortex-ai-marketplace')); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_repair_thorius_database',
                    nonce: '<?php echo wp_create_nonce('vortex_thorius_db_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php echo esc_js(__('Database tables repaired successfully.', 'vortex-ai-marketplace')); ?>');
                        location.reload();
                    } else {
                        alert('<?php echo esc_js(__('Error: ', 'vortex-ai-marketplace')); ?>' + response.data.message);
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('Server error while repairing database.', 'vortex-ai-marketplace')); ?>');
                }
            });
        }
    });
    
    // Form validation
    $('form').on('submit', function() {
        if ($('#emergency_shutdown').val() === 'yes') {
            return confirm('<?php echo esc_js(__('WARNING: Enabling Emergency Shutdown will disable ALL AI functions. Are you sure you want to proceed?', 'vortex-ai-marketplace')); ?>');
        }
        return true;
    });
});
</script>

<style>
.thorius-tab-navigation {
    margin-bottom: 20px;
    border-bottom: 1px solid #ccc;
    padding-bottom: 0;
}

.thorius-tab {
    display: inline-block;
    padding: 10px 15px;
    margin-right: 5px;
    margin-bottom: -1px;
    border: 1px solid transparent;
    border-top-left-radius: 3px;
    border-top-right-radius: 3px;
    text-decoration: none;
    color: #23282d;
    font-weight: 600;
}

.thorius-tab.active {
    border-color: #ccc;
    border-bottom-color: #f1f1f1;
    background: #f1f1f1;
}

.thorius-tab-content {
    display: none;
    padding: 20px;
    background: #f1f1f1;
    border: 1px solid #ccc;
    border-top: none;
}

.thorius-tab-content.active {
    display: block;
}

.thorius-range-slider {
    width: 300px;
    vertical-align: middle;
}

.thorius-range-value {
    display: inline-block;
    margin-left: 10px;
    font-weight: bold;
}

.thorius-advanced-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.thorius-advanced-actions .button {
    margin-right: 10px;
}
</style> 