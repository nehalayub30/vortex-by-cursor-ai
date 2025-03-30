<?php
/**
 * Image-to-Image Settings Page
 * 
 * Handles configuration settings for the image-to-image transformation functionality,
 * ensuring AI agent deep learning capabilities remain active during all operations.
 *
 * @package VORTEX_AI_Marketplace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the Image-to-Image settings page
 */
function vortex_image_to_image_settings_page() {
    // Initialize AI agents to ensure deep learning during settings interactions
    do_action('vortex_ai_agent_init', 'HURAII', 'img2img_settings', array(
        'context' => 'admin_settings',
        'user_id' => get_current_user_id(),
        'session_id' => wp_get_session_token(),
        'learning_enabled' => true
    ));
    
    // Also initialize CLOE for learning from admin settings interactions
    do_action('vortex_ai_agent_init', 'CLOE', 'img2img_settings', array(
        'context' => 'admin_preferences',
        'user_id' => get_current_user_id(),
        'session_id' => wp_get_session_token(),
        'learning_enabled' => true
    ));
    
    // Get saved settings with defaults
    $settings = get_option('vortex_img2img_settings', array(
        'default_strength' => 0.75,
        'max_upload_size' => 10, // In MB
        'allowed_input_formats' => array('jpg', 'jpeg', 'png', 'webp'),
        'default_output_format' => 'png',
        'enable_style_transfer' => 'yes',
        'enable_advanced_transforms' => 'yes',
        'default_guidance_scale' => 7.5,
        'max_processing_steps' => 50,
        'preserve_color_option' => 'yes',
        'preserve_composition_option' => 'yes',
        'enable_face_enhancement' => 'yes',
        'enable_upscaling' => 'yes',
        'default_upscale_factor' => 2,
        'enable_batch_processing' => 'yes',
        'max_batch_size' => 5,
    ));
    
    // Process form submission
    if (isset($_POST['vortex_img2img_settings_submit']) && check_admin_referer('vortex_img2img_settings_nonce')) {
        $new_settings = array(
            'default_strength' => floatval($_POST['default_strength'] ?? $settings['default_strength']),
            'max_upload_size' => absint($_POST['max_upload_size'] ?? $settings['max_upload_size']),
            'allowed_input_formats' => isset($_POST['allowed_input_formats']) ? array_map('sanitize_text_field', $_POST['allowed_input_formats']) : $settings['allowed_input_formats'],
            'default_output_format' => sanitize_text_field($_POST['default_output_format'] ?? $settings['default_output_format']),
            'enable_style_transfer' => sanitize_text_field($_POST['enable_style_transfer'] ?? 'no'),
            'enable_advanced_transforms' => sanitize_text_field($_POST['enable_advanced_transforms'] ?? 'no'),
            'default_guidance_scale' => floatval($_POST['default_guidance_scale'] ?? $settings['default_guidance_scale']),
            'max_processing_steps' => absint($_POST['max_processing_steps'] ?? $settings['max_processing_steps']),
            'preserve_color_option' => sanitize_text_field($_POST['preserve_color_option'] ?? 'no'),
            'preserve_composition_option' => sanitize_text_field($_POST['preserve_composition_option'] ?? 'no'),
            'enable_face_enhancement' => sanitize_text_field($_POST['enable_face_enhancement'] ?? 'no'),
            'enable_upscaling' => sanitize_text_field($_POST['enable_upscaling'] ?? 'no'),
            'default_upscale_factor' => absint($_POST['default_upscale_factor'] ?? $settings['default_upscale_factor']),
            'enable_batch_processing' => sanitize_text_field($_POST['enable_batch_processing'] ?? 'no'),
            'max_batch_size' => absint($_POST['max_batch_size'] ?? $settings['max_batch_size']),
        );
        
        // Track settings changes for AI learning
        do_action('vortex_ai_agent_learn', 'HURAII', 'img2img_settings_updated', array(
            'previous_settings' => $settings,
            'new_settings' => $new_settings,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql'),
        ));
        
        // Also let CLOE learn from these settings changes
        do_action('vortex_ai_agent_learn', 'CLOE', 'admin_preference_updated', array(
            'setting_type' => 'img2img',
            'previous_settings' => $settings,
            'new_settings' => $new_settings,
            'user_id' => get_current_user_id(),
        ));
        
        // Update settings
        update_option('vortex_img2img_settings', $new_settings);
        $settings = $new_settings;
        
        // Display success message
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Image-to-Image settings saved successfully.', 'vortex-ai-marketplace') . '</p></div>';
        
        // Inform BusinessStrategist about settings change for artist impact assessment
        do_action('vortex_ai_agent_notify', 'BusinessStrategist', 'img2img_settings_updated', array(
            'settings' => $settings,
            'impact_on_artists' => 'evaluate',
        ));
    }
    
    // Get all supported output formats
    $output_formats = apply_filters('vortex_img2img_output_formats', array(
        'png' => 'PNG (High Quality)',
        'jpg' => 'JPG (Smaller Size)',
        'webp' => 'WebP (Modern Format)',
    ));
    
    // Get all supported input formats
    $input_formats = apply_filters('vortex_img2img_input_formats', array(
        'jpg' => 'JPG',
        'jpeg' => 'JPEG',
        'png' => 'PNG',
        'webp' => 'WebP',
        'gif' => 'GIF',
        'bmp' => 'BMP',
        'tiff' => 'TIFF',
    ));
    
    // Render settings form
    ?>
    <div class="wrap vortex-img2img-settings">
        <h1><?php esc_html_e('Image-to-Image Settings', 'vortex-ai-marketplace'); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('vortex_img2img_settings_nonce'); ?>
            
            <div class="vortex-settings-section">
                <h2><?php esc_html_e('General Settings', 'vortex-ai-marketplace'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="default_strength"><?php esc_html_e('Default Transformation Strength', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="range" name="default_strength" id="default_strength" min="0.1" max="1.0" step="0.05" value="<?php echo esc_attr($settings['default_strength']); ?>" class="vortex-range-slider">
                            <span class="vortex-range-value"><?php echo esc_html($settings['default_strength']); ?></span>
                            <p class="description"><?php esc_html_e('Default strength of transformation (higher values = more change from original).', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="max_upload_size"><?php esc_html_e('Maximum Upload Size (MB)', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="max_upload_size" id="max_upload_size" value="<?php echo esc_attr($settings['max_upload_size']); ?>" min="1" max="50">
                            <p class="description"><?php esc_html_e('Maximum allowed file size for uploaded images in megabytes.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_guidance_scale"><?php esc_html_e('Default Guidance Scale', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="range" name="default_guidance_scale" id="default_guidance_scale" min="1.0" max="20.0" step="0.5" value="<?php echo esc_attr($settings['default_guidance_scale']); ?>" class="vortex-range-slider">
                            <span class="vortex-range-value"><?php echo esc_html($settings['default_guidance_scale']); ?></span>
                            <p class="description"><?php esc_html_e('How closely the image follows the prompt (higher values = more precise).', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="max_processing_steps"><?php esc_html_e('Maximum Processing Steps', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="max_processing_steps" id="max_processing_steps" value="<?php echo esc_attr($settings['max_processing_steps']); ?>" min="10" max="150">
                            <p class="description"><?php esc_html_e('Maximum number of diffusion steps (higher values = better quality but slower).', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="vortex-settings-section">
                <h2><?php esc_html_e('Format Settings', 'vortex-ai-marketplace'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Allowed Input Formats', 'vortex-ai-marketplace'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><?php esc_html_e('Allowed Input Formats', 'vortex-ai-marketplace'); ?></legend>
                                
                                <?php foreach ($input_formats as $format_id => $format_label) : 
                                    $checked = in_array($format_id, $settings['allowed_input_formats']) ? 'checked' : '';
                                ?>
                                    <label>
                                        <input type="checkbox" name="allowed_input_formats[]" value="<?php echo esc_attr($format_id); ?>" <?php echo $checked; ?>>
                                        <?php echo esc_html($format_label); ?>
                                    </label><br>
                                <?php endforeach; ?>
                                
                                <p class="description"><?php esc_html_e('Select which image formats can be uploaded for transformation.', 'vortex-ai-marketplace'); ?></p>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_output_format"><?php esc_html_e('Default Output Format', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="default_output_format" id="default_output_format">
                                <?php foreach ($output_formats as $format_id => $format_label) : ?>
                                    <option value="<?php echo esc_attr($format_id); ?>" <?php selected($settings['default_output_format'], $format_id); ?>>
                                        <?php echo esc_html($format_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Default format for transformed images.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="vortex-settings-section">
                <h2><?php esc_html_e('Transformation Features', 'vortex-ai-marketplace'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_style_transfer"><?php esc_html_e('Enable Style Transfer', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="enable_style_transfer" id="enable_style_transfer">
                                <option value="yes" <?php selected($settings['enable_style_transfer'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['enable_style_transfer'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Enable artistic style transfer between images.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_advanced_transforms"><?php esc_html_e('Enable Advanced Transformations', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="enable_advanced_transforms" id="enable_advanced_transforms">
                                <option value="yes" <?php selected($settings['enable_advanced_transforms'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['enable_advanced_transforms'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Enable advanced transformations like inpainting and outpainting.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="preserve_color_option"><?php esc_html_e('Preserve Color Option', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="preserve_color_option" id="preserve_color_option">
                                <option value="yes" <?php selected($settings['preserve_color_option'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['preserve_color_option'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Allow users to preserve original image colors during transformation.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="preserve_composition_option"><?php esc_html_e('Preserve Composition Option', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="preserve_composition_option" id="preserve_composition_option">
                                <option value="yes" <?php selected($settings['preserve_composition_option'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['preserve_composition_option'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Allow users to preserve original image composition during transformation.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="vortex-settings-section">
                <h2><?php esc_html_e('Enhancement Features', 'vortex-ai-marketplace'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_face_enhancement"><?php esc_html_e('Enable Face Enhancement', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="enable_face_enhancement" id="enable_face_enhancement">
                                <option value="yes" <?php selected($settings['enable_face_enhancement'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['enable_face_enhancement'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Enable AI face enhancement to improve face details in transformed images.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_upscaling"><?php esc_html_e('Enable Upscaling', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="enable_upscaling" id="enable_upscaling">
                                <option value="yes" <?php selected($settings['enable_upscaling'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['enable_upscaling'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Enable AI upscaling to increase image resolution after transformation.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_upscale_factor"><?php esc_html_e('Default Upscale Factor', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="default_upscale_factor" id="default_upscale_factor">
                                <option value="2" <?php selected($settings['default_upscale_factor'], 2); ?>><?php esc_html_e('2x', 'vortex-ai-marketplace'); ?></option>
                                <option value="4" <?php selected($settings['default_upscale_factor'], 4); ?>><?php esc_html_e('4x', 'vortex-ai-marketplace'); ?></option>
                                <option value="8" <?php selected($settings['default_upscale_factor'], 8); ?>><?php esc_html_e('8x (High resource usage)', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Default upscaling factor when upscaling is enabled.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="vortex-settings-section">
                <h2><?php esc_html_e('Batch Processing', 'vortex-ai-marketplace'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_batch_processing"><?php esc_html_e('Enable Batch Processing', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="enable_batch_processing" id="enable_batch_processing">
                                <option value="yes" <?php selected($settings['enable_batch_processing'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['enable_batch_processing'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Enable batch processing of multiple images at once.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="max_batch_size"><?php esc_html_e('Maximum Batch Size', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="max_batch_size" id="max_batch_size" value="<?php echo esc_attr($settings['max_batch_size']); ?>" min="1" max="20">
                            <p class="description"><?php esc_html_e('Maximum number of images that can be processed in a single batch.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <p class="submit">
                <input type="submit" name="vortex_img2img_settings_submit" class="button button-primary" value="<?php esc_attr_e('Save Image-to-Image Settings', 'vortex-ai-marketplace'); ?>">
            </p>
        </form>
        
        <div class="vortex-ai-usage-stats">
            <h3><?php esc_html_e('Image-to-Image Usage Statistics', 'vortex-ai-marketplace'); ?></h3>
            <?php
            // Get usage stats with defaults
            $usage_stats = apply_filters('vortex_img2img_usage_stats', array(
                'total_transformations' => 0,
                'avg_processing_time' => 0,
                'most_used_style' => '-',
                'most_common_resolution' => '-',
                'avg_strength' => 0,
                'total_storage' => '0 MB',
            ));
            ?>
            <table class="widefat">
                <tr>
                    <th><?php esc_html_e('Total Transformations', 'vortex-ai-marketplace'); ?></th>
                    <td><?php echo esc_html(number_format($usage_stats['total_transformations'])); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Avg. Processing Time', 'vortex-ai-marketplace'); ?></th>
                    <td><?php echo esc_html(number_format($usage_stats['avg_processing_time'], 2) . ' ' . __('seconds', 'vortex-ai-marketplace')); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Most Used Style', 'vortex-ai-marketplace'); ?></th>
                    <td><?php echo esc_html($usage_stats['most_used_style']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Most Common Resolution', 'vortex-ai-marketplace'); ?></th>
                    <td><?php echo esc_html($usage_stats['most_common_resolution']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Average Strength', 'vortex-ai-marketplace'); ?></th>
                    <td><?php echo esc_html(number_format($usage_stats['avg_strength'], 2)); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Total Storage Used', 'vortex-ai-marketplace'); ?></th>
                    <td><?php echo esc_html($usage_stats['total_storage']); ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Track AI interactions for learning
        function trackAIInteraction(agent, action, data) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_track_ai_interaction',
                    agent: agent,
                    interaction_type: action,
                    interaction_data: data,
                    nonce: '<?php echo wp_create_nonce('vortex_ai_interaction_nonce'); ?>'
                }
            });
        }
        
        // Update range slider values
        $('.vortex-range-slider').on('input', function() {
            $(this).next('.vortex-range-value').text($(this).val());
            
            // Track for HURAII learning
            trackAIInteraction('HURAII', 'img2img_setting_adjusted', {
                setting: $(this).attr('id'),
                value: $(this).val(),
                user_id: <?php echo get_current_user_id(); ?>
            });
            
            // Also track for CLOE to learn about admin preferences
            trackAIInteraction('CLOE', 'admin_preference_updated', {
                setting_type: 'img2img_slider',
                setting_id: $(this).attr('id'),
                value: $(this).val(),
                user_id: <?php echo get_current_user_id(); ?>
            });
        });
        
        // Track settings page view for AI learning
        trackAIInteraction('HURAII', 'img2img_settings_page_view', {
            user_id: <?php echo get_current_user_id(); ?>,
            timestamp: new Date().toISOString(),
            current_settings: <?php echo json_encode($settings); ?>
        });
        
        // Track for BusinessStrategist to analyze admin behavior
        trackAIInteraction('BusinessStrategist', 'admin_section_engagement', {
            section: 'img2img_settings',
            user_id: <?php echo get_current_user_id(); ?>,
            timestamp: new Date().toISOString()
        });
        
        // Handle dependent options
        $('#enable_upscaling').on('change', function() {
            if ($(this).val() === 'no') {
                $('#default_upscale_factor').closest('tr').addClass('disabled-setting');
            } else {
                $('#default_upscale_factor').closest('tr').removeClass('disabled-setting');
            }
        }).trigger('change');
        
        $('#enable_batch_processing').on('change', function() {
            if ($(this).val() === 'no') {
                $('#max_batch_size').closest('tr').addClass('disabled-setting');
            } else {
                $('#max_batch_size').closest('tr').removeClass('disabled-setting');
            }
        }).trigger('change');
    });
    </script>
    <?php
}

/**
 * Register the Image-to-Image settings page in the admin menu
 */
function vortex_register_img2img_settings_page() {
    add_submenu_page(
        'vortex-dashboard', 
        esc_html__('Image-to-Image Settings', 'vortex-ai-marketplace'),
        esc_html__('Img2Img Settings', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex-img2img-settings',
        'vortex_image_to_image_settings_page'
    );
}
add_action('admin_menu', 'vortex_register_img2img_settings_page', 25);

/**
 * Enqueue admin styles and scripts for Image-to-Image settings
 */
function vortex_img2img_settings_enqueue_scripts($hook) {
    if ('vortex-dashboard_page_vortex-img2img-settings' !== $hook) {
        return;
    }
    
    wp_enqueue_style(
        'vortex-img2img-settings-css',
        plugin_dir_url(__FILE__) . '../assets/css/vortex-img2img-settings.css',
        array(),
        VORTEX_VERSION
    );
    
    wp_enqueue_script(
        'vortex-img2img-settings-js',
        plugin_dir_url(__FILE__) . '../assets/js/vortex-img2img-settings.js',
        array('jquery'),
        VORTEX_VERSION,
        true
    );
    
    // Add learning context for script interaction
    wp_localize_script('vortex-img2img-settings-js', 'vortexImg2Img', array(
        'nonce' => wp_create_nonce('vortex_img2img_settings_nonce'),
        'user_id' => get_current_user_id(),
        'is_admin' => current_user_can('manage_options'),
        'i18n' => array(
            'save_success' => esc_html__('Settings saved successfully.', 'vortex-ai-marketplace'),
            'save_error' => esc_html__('Error saving settings.', 'vortex-ai-marketplace')
        )
    ));
}
add_action('admin_enqueue_scripts', 'vortex_img2img_settings_enqueue_scripts');

/**
 * Register AJAX handler for getting image-to-image usage statistics
 */
function vortex_register_img2img_stats_ajax() {
    add_action('wp_ajax_vortex_get_img2img_stats', 'vortex_ajax_get_img2img_stats');
}
add_action('init', 'vortex_register_img2img_stats_ajax');

/**
 * AJAX handler for getting image-to-image usage statistics
 */
function vortex_ajax_get_img2img_stats() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_img2img_settings_nonce')) {
        wp_send_json_error(array('message' => esc_html__('Security check failed.', 'vortex-ai-marketplace')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'vortex-ai-marketplace')));
    }
    
    // Get stats from database or cache
    $stats = apply_filters('vortex_img2img_usage_stats', array(
        'total_transformations' => 0,
        'avg_processing_time' => 0,
        'most_used_style' => '-',
        'most_common_resolution' => '-',
        'avg_strength' => 0,
        'total_storage' => '0 MB',
    ));
    
    // Track this analytics request for AI learning
    do_action('vortex_ai_agent_learn', 'CLOE', 'admin_data_request', array(
        'data_type' => 'img2img_stats',
        'user_id' => get_current_user_id(),
        'timestamp' => current_time('mysql')
    ));
    
    wp_send_json_success($stats);
} 