<?php
/**
 * HURAII Settings Page
 * 
 * Handles configuration settings for the HURAII AI agent, ensuring
 * deep learning capabilities remain active during all operations.
 *
 * @package VORTEX_AI_Marketplace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the HURAII settings page
 */
function vortex_huraii_settings_page() {
    // Initialize AI agents to ensure deep learning is active during settings interactions
    do_action('vortex_ai_agent_init', 'HURAII', 'settings_page', array(
        'context' => 'admin_settings',
        'user_id' => get_current_user_id(),
        'session_id' => wp_get_session_token(),
        'learning_enabled' => true
    ));
    
    // Get saved settings with defaults
    $settings = get_option('vortex_huraii_settings', array(
        'default_model' => 'sd-v2-1',
        'enable_deep_learning' => 'yes',
        'default_canvas_width' => 1024,
        'default_canvas_height' => 1024,
        'max_generation_size' => 2048,
        'enable_3d_generation' => 'yes',
        'enable_video_generation' => 'yes',
        'enable_audio_generation' => 'yes',
        'seed_art_components' => array('color', 'composition', 'depth', 'texture', 'light', 'emotion', 'movement'),
        'learning_rate' => 0.85,
        'style_recognition_threshold' => 0.75,
        'api_request_batch_size' => 5,
        'generation_timeout' => 120,
    ));
    
    // Process form submission
    if (isset($_POST['vortex_huraii_settings_submit']) && check_admin_referer('vortex_huraii_settings_nonce')) {
        $new_settings = array(
            'default_model' => sanitize_text_field($_POST['default_model'] ?? $settings['default_model']),
            'enable_deep_learning' => sanitize_text_field($_POST['enable_deep_learning'] ?? 'yes'),
            'default_canvas_width' => absint($_POST['default_canvas_width'] ?? $settings['default_canvas_width']),
            'default_canvas_height' => absint($_POST['default_canvas_height'] ?? $settings['default_canvas_height']),
            'max_generation_size' => absint($_POST['max_generation_size'] ?? $settings['max_generation_size']),
            'enable_3d_generation' => sanitize_text_field($_POST['enable_3d_generation'] ?? 'no'),
            'enable_video_generation' => sanitize_text_field($_POST['enable_video_generation'] ?? 'no'),
            'enable_audio_generation' => sanitize_text_field($_POST['enable_audio_generation'] ?? 'no'),
            'seed_art_components' => isset($_POST['seed_art_components']) ? array_map('sanitize_text_field', $_POST['seed_art_components']) : $settings['seed_art_components'],
            'learning_rate' => floatval($_POST['learning_rate'] ?? $settings['learning_rate']),
            'style_recognition_threshold' => floatval($_POST['style_recognition_threshold'] ?? $settings['style_recognition_threshold']),
            'api_request_batch_size' => absint($_POST['api_request_batch_size'] ?? $settings['api_request_batch_size']),
            'generation_timeout' => absint($_POST['generation_timeout'] ?? $settings['generation_timeout']),
        );
        
        // Track settings changes for AI learning
        do_action('vortex_ai_agent_learn', 'HURAII', 'settings_updated', array(
            'previous_settings' => $settings,
            'new_settings' => $new_settings,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql'),
        ));
        
        // Update settings
        update_option('vortex_huraii_settings', $new_settings);
        $settings = $new_settings;
        
        // Display success message
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('HURAII settings saved successfully.', 'vortex-ai-marketplace') . '</p></div>';
        
        // Inform CLOE about settings change
        do_action('vortex_ai_agent_notify', 'CLOE', 'huraii_settings_updated', array(
            'settings' => $settings,
            'updated_by' => get_current_user_id(),
        ));
        
        // Inform BusinessStrategist about settings change
        do_action('vortex_ai_agent_notify', 'BusinessStrategist', 'huraii_settings_updated', array(
            'settings' => $settings,
            'impact_on_artists' => 'evaluate',
        ));
    }
    
    // Get available models for dropdown
    $available_models = apply_filters('vortex_huraii_available_models', array(
        'sd-v1-5' => 'Stable Diffusion v1.5',
        'sd-v2-1' => 'Stable Diffusion v2.1',
        'sd-xl' => 'Stable Diffusion XL',
        'dalle-mini' => 'DALL-E Mini',
    ));
    
    // Render settings form
    ?>
    <div class="wrap vortex-huraii-settings">
        <h1><?php esc_html_e('HURAII Settings', 'vortex-ai-marketplace'); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('vortex_huraii_settings_nonce'); ?>
            
            <div class="vortex-settings-section">
                <h2><?php esc_html_e('General Settings', 'vortex-ai-marketplace'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="default_model"><?php esc_html_e('Default Generation Model', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="default_model" id="default_model">
                                <?php foreach ($available_models as $model_id => $model_name) : ?>
                                    <option value="<?php echo esc_attr($model_id); ?>" <?php selected($settings['default_model'], $model_id); ?>>
                                        <?php echo esc_html($model_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Select the default AI model for artwork generation.', 'vortex-ai-marketplace'); ?></p>
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
                            <p class="description"><?php esc_html_e('Enable HURAII to learn from user interactions and improve over time.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="learning_rate"><?php esc_html_e('Learning Rate', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="range" name="learning_rate" id="learning_rate" min="0.1" max="1.0" step="0.05" value="<?php echo esc_attr($settings['learning_rate']); ?>" class="vortex-range-slider">
                            <span class="vortex-range-value"><?php echo esc_html($settings['learning_rate']); ?></span>
                            <p class="description"><?php esc_html_e('Adjust how quickly HURAII adapts to new patterns (higher values = faster adaptation).', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="vortex-settings-section">
                <h2><?php esc_html_e('Generation Settings', 'vortex-ai-marketplace'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="default_canvas_width"><?php esc_html_e('Default Canvas Width', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="default_canvas_width" id="default_canvas_width" value="<?php echo esc_attr($settings['default_canvas_width']); ?>" min="256" max="4096" step="8">
                            <p class="description"><?php esc_html_e('Default width in pixels for generation canvas.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_canvas_height"><?php esc_html_e('Default Canvas Height', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="default_canvas_height" id="default_canvas_height" value="<?php echo esc_attr($settings['default_canvas_height']); ?>" min="256" max="4096" step="8">
                            <p class="description"><?php esc_html_e('Default height in pixels for generation canvas.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="max_generation_size"><?php esc_html_e('Maximum Generation Size', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="max_generation_size" id="max_generation_size" value="<?php echo esc_attr($settings['max_generation_size']); ?>" min="512" max="8192" step="64">
                            <p class="description"><?php esc_html_e('Maximum size (width or height) in pixels for image generation.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="generation_timeout"><?php esc_html_e('Generation Timeout', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="generation_timeout" id="generation_timeout" value="<?php echo esc_attr($settings['generation_timeout']); ?>" min="30" max="600" step="10">
                            <p class="description"><?php esc_html_e('Maximum time in seconds for a generation request before timeout.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="vortex-settings-section">
                <h2><?php esc_html_e('Format Support', 'vortex-ai-marketplace'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_3d_generation"><?php esc_html_e('Enable 3D Generation', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="enable_3d_generation" id="enable_3d_generation">
                                <option value="yes" <?php selected($settings['enable_3d_generation'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['enable_3d_generation'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Enable generation of 3D models (requires more resources).', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_video_generation"><?php esc_html_e('Enable Video Generation', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="enable_video_generation" id="enable_video_generation">
                                <option value="yes" <?php selected($settings['enable_video_generation'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['enable_video_generation'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Enable generation of video content (requires more resources).', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_audio_generation"><?php esc_html_e('Enable Audio Generation', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="enable_audio_generation" id="enable_audio_generation">
                                <option value="yes" <?php selected($settings['enable_audio_generation'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['enable_audio_generation'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Enable generation of audio content.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="vortex-settings-section">
                <h2><?php esc_html_e('Seed Art Analysis', 'vortex-ai-marketplace'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Seed Art Components', 'vortex-ai-marketplace'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><?php esc_html_e('Seed Art Components', 'vortex-ai-marketplace'); ?></legend>
                                
                                <?php 
                                $all_components = array(
                                    'color' => esc_html__('Color Harmony', 'vortex-ai-marketplace'),
                                    'composition' => esc_html__('Composition', 'vortex-ai-marketplace'),
                                    'depth' => esc_html__('Depth and Perspective', 'vortex-ai-marketplace'),
                                    'texture' => esc_html__('Texture', 'vortex-ai-marketplace'),
                                    'light' => esc_html__('Light and Shadow', 'vortex-ai-marketplace'),
                                    'emotion' => esc_html__('Emotion and Narrative', 'vortex-ai-marketplace'),
                                    'movement' => esc_html__('Movement and Layering', 'vortex-ai-marketplace'),
                                );
                                
                                foreach ($all_components as $component_id => $component_label) :
                                    $checked = in_array($component_id, $settings['seed_art_components']) ? 'checked' : '';
                                ?>
                                    <label>
                                        <input type="checkbox" name="seed_art_components[]" value="<?php echo esc_attr($component_id); ?>" <?php echo $checked; ?>>
                                        <?php echo esc_html($component_label); ?>
                                    </label><br>
                                <?php endforeach; ?>
                                
                                <p class="description"><?php esc_html_e('Select which Seed Art components HURAII should analyze in artwork.', 'vortex-ai-marketplace'); ?></p>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="style_recognition_threshold"><?php esc_html_e('Style Recognition Threshold', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="range" name="style_recognition_threshold" id="style_recognition_threshold" min="0.3" max="0.95" step="0.05" value="<?php echo esc_attr($settings['style_recognition_threshold']); ?>" class="vortex-range-slider">
                            <span class="vortex-range-value"><?php echo esc_html($settings['style_recognition_threshold']); ?></span>
                            <p class="description"><?php esc_html_e('Confidence threshold for style recognition (higher values = more strict matching).', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="vortex-settings-section">
                <h2><?php esc_html_e('API Settings', 'vortex-ai-marketplace'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="api_request_batch_size"><?php esc_html_e('API Request Batch Size', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="api_request_batch_size" id="api_request_batch_size" value="<?php echo esc_attr($settings['api_request_batch_size']); ?>" min="1" max="20">
                            <p class="description"><?php esc_html_e('Number of images to generate in a single API request batch.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <p class="submit">
                <input type="submit" name="vortex_huraii_settings_submit" class="button button-primary" value="<?php esc_attr_e('Save HURAII Settings', 'vortex-ai-marketplace'); ?>">
            </p>
        </form>
        
        <div class="vortex-ai-learning-status">
            <h3><?php esc_html_e('AI Learning Status', 'vortex-ai-marketplace'); ?></h3>
            <?php
            // Get HURAII learning stats
            $learning_stats = apply_filters('vortex_huraii_learning_stats', array(
                'total_learning_events' => 0,
                'last_updated' => '',
                'model_version' => '1.0',
                'training_progress' => 0,
            ));
            ?>
            <table class="widefat">
                <tr>
                    <th><?php esc_html_e('Total Learning Events', 'vortex-ai-marketplace'); ?></th>
                    <td><?php echo esc_html($learning_stats['total_learning_events']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Last Updated', 'vortex-ai-marketplace'); ?></th>
                    <td><?php echo esc_html($learning_stats['last_updated']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Model Version', 'vortex-ai-marketplace'); ?></th>
                    <td><?php echo esc_html($learning_stats['model_version']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Training Progress', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <progress value="<?php echo esc_attr($learning_stats['training_progress']); ?>" max="100"></progress>
                        <?php echo esc_html($learning_stats['training_progress'] . '%'); ?>
                    </td>
                </tr>
            </table>
            <p>
                <button type="button" class="button" id="vortex-huraii-reset-learning">
                    <?php esc_html_e('Reset Learning Data', 'vortex-ai-marketplace'); ?>
                </button>
                <span class="spinner" style="float: none;"></span>
            </p>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Track AI interactions for learning
        function trackAIInteraction(action, data) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_track_ai_interaction',
                    agent: 'HURAII',
                    interaction_type: action,
                    interaction_data: data,
                    nonce: '<?php echo wp_create_nonce('vortex_ai_interaction_nonce'); ?>'
                }
            });
        }
        
        // Update range slider values
        $('.vortex-range-slider').on('input', function() {
            $(this).next('.vortex-range-value').text($(this).val());
            
            // Track for AI learning
            trackAIInteraction('settings_slider_changed', {
                setting: $(this).attr('id'),
                value: $(this).val()
            });
        });
        
        // Handle reset learning data button
        $('#vortex-huraii-reset-learning').on('click', function() {
            if (!confirm('<?php esc_attr_e('Are you sure you want to reset all HURAII learning data? This cannot be undone.', 'vortex-ai-marketplace'); ?>')) {
                return;
            }
            
            var $button = $(this);
            var $spinner = $button.next('.spinner');
            
            $button.prop('disabled', true);
            $spinner.css('visibility', 'visible');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_reset_huraii_learning',
                    nonce: '<?php echo wp_create_nonce('vortex_reset_learning_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php esc_attr_e('HURAII learning data has been reset successfully.', 'vortex-ai-marketplace'); ?>');
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php esc_attr_e('An error occurred while resetting learning data.', 'vortex-ai-marketplace'); ?>');
                    }
                },
                error: function() {
                    alert('<?php esc_attr_e('A server error occurred while resetting learning data.', 'vortex-ai-marketplace'); ?>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.css('visibility', 'hidden');
                }
            });
            
            // Track reset action for AI learning
            trackAIInteraction('learning_data_reset', {
                user_id: <?php echo get_current_user_id(); ?>,
                timestamp: new Date().toISOString()
            });
        });
        
        // Track settings page view for AI learning
        trackAIInteraction('settings_page_view', {
            user_id: <?php echo get_current_user_id(); ?>,
            timestamp: new Date().toISOString(),
            current_settings: <?php echo json_encode($settings); ?>
        });
    });
    </script>
    <?php
}

/**
 * Register AJAX handler for resetting HURAII learning data
 */
function vortex_register_huraii_reset_learning_ajax() {
    add_action('wp_ajax_vortex_reset_huraii_learning', 'vortex_ajax_reset_huraii_learning');
}
add_action('init', 'vortex_register_huraii_reset_learning_ajax');

/**
 * AJAX handler for resetting HURAII learning data
 */
function vortex_ajax_reset_huraii_learning() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_reset_learning_nonce')) {
        wp_send_json_error(array('message' => esc_html__('Security check failed.', 'vortex-ai-marketplace')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'vortex-ai-marketplace')));
    }
    
    // Inform HURAII of the reset request
    do_action('vortex_huraii_reset_learning');
    
    // Track this significant event
    do_action('vortex_ai_agent_learn', 'HURAII', 'learning_reset', array(
        'user_id' => get_current_user_id(),
        'timestamp' => current_time('mysql'),
        'reason' => 'manual_admin_reset'
    ));
    
    // Delete learning data
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_ai_learning_data';
    $wpdb->delete($table_name, array('agent' => 'HURAII'), array('%s'));
    
    // Inform other agents about the reset
    do_action('vortex_ai_agent_notify', 'CLOE', 'huraii_learning_reset', array(
        'reset_by' => get_current_user_id(),
        'timestamp' => current_time('mysql')
    ));
    
    do_action('vortex_ai_agent_notify', 'BusinessStrategist', 'huraii_learning_reset', array(
        'reset_by' => get_current_user_id(),
        'timestamp' => current_time('mysql')
    ));
    
    wp_send_json_success(array('message' => esc_html__('HURAII learning data has been reset successfully.', 'vortex-ai-marketplace')));
}

/**
 * Register the HURAII settings page in the admin menu
 */
function vortex_register_huraii_settings_page() {
    add_submenu_page(
        'vortex-dashboard', 
        esc_html__('HURAII Settings', 'vortex-ai-marketplace'),
        esc_html__('HURAII Settings', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex-huraii-settings',
        'vortex_huraii_settings_page'
    );
}
add_action('admin_menu', 'vortex_register_huraii_settings_page', 20);

/**
 * Enqueue admin styles and scripts for HURAII settings
 */
function vortex_huraii_settings_enqueue_scripts($hook) {
    if ('vortex-dashboard_page_vortex-huraii-settings' !== $hook) {
        return;
    }
    
    wp_enqueue_style(
        'vortex-huraii-settings-css',
        plugin_dir_url(__FILE__) . '../assets/css/vortex-huraii-settings.css',
        array(),
        VORTEX_VERSION
    );
    
    wp_enqueue_script(
        'vortex-huraii-settings-js',
        plugin_dir_url(__FILE__) . '../assets/js/vortex-huraii-settings.js',
        array('jquery'),
        VORTEX_VERSION,
        true
    );
    
    // Add learning context for script interaction
    wp_localize_script('vortex-huraii-settings-js', 'vortexHURAII', array(
        'learning_enabled' => get_option('vortex_huraii_settings', array())['enable_deep_learning'] === 'yes',
        'user_id' => get_current_user_id(),
        'is_admin' => current_user_can('manage_options'),
        'nonce' => wp_create_nonce('vortex_huraii_settings_nonce'),
        'i18n' => array(
            'confirm_reset' => esc_html__('Are you sure you want to reset all HURAII learning data? This cannot be undone.', 'vortex-ai-marketplace'),
            'reset_success' => esc_html__('HURAII learning data has been reset successfully.', 'vortex-ai-marketplace'),
            'reset_error' => esc_html__('An error occurred while resetting learning data.', 'vortex-ai-marketplace')
        )
    ));
}
add_action('admin_enqueue_scripts', 'vortex_huraii_settings_enqueue_scripts'); 