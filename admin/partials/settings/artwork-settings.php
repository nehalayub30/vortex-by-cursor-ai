<?php
/**
 * Artwork Settings Template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials/settings
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    return;
}

// Get current settings or set defaults
$artwork_settings = get_option('vortex_artwork_settings', array(
    'image_quality' => 90,
    'max_file_size' => 10,
    'allowed_formats' => array('jpg', 'png', 'webp'),
    'enable_watermark' => false,
    'watermark_text' => get_bloginfo('name'),
    'watermark_opacity' => 30,
    'enable_ai_enhancement' => true,
    'enable_exif_cleaning' => true,
    'enable_cdn' => false,
    'cdn_url' => '',
    'thumbnail_sizes' => array(
        'small' => array(
            'width' => 300,
            'height' => 300,
            'crop' => true
        ),
        'medium' => array(
            'width' => 600,
            'height' => 600,
            'crop' => true
        ),
        'large' => array(
            'width' => 1200,
            'height' => 1200,
            'crop' => false
        )
    ),
    'display_settings' => array(
        'enable_lightbox' => true,
        'enable_zoom' => true,
        'lazy_loading' => true,
        'gallery_columns' => 3
    )
));

// Process form submission
if (isset($_POST['vortex_save_artwork_settings']) && check_admin_referer('vortex_artwork_settings_nonce')) {
    // Sanitize and save settings
    $artwork_settings['image_quality'] = intval(sanitize_text_field($_POST['image_quality']));
    $artwork_settings['max_file_size'] = intval(sanitize_text_field($_POST['max_file_size']));
    
    // Sanitize allowed formats
    $allowed_formats = isset($_POST['allowed_formats']) ? $_POST['allowed_formats'] : array();
    $artwork_settings['allowed_formats'] = array_map('sanitize_text_field', $allowed_formats);
    
    // Sanitize boolean values
    $artwork_settings['enable_watermark'] = isset($_POST['enable_watermark']);
    $artwork_settings['watermark_text'] = sanitize_text_field($_POST['watermark_text']);
    $artwork_settings['watermark_opacity'] = intval(sanitize_text_field($_POST['watermark_opacity']));
    $artwork_settings['enable_ai_enhancement'] = isset($_POST['enable_ai_enhancement']);
    $artwork_settings['enable_exif_cleaning'] = isset($_POST['enable_exif_cleaning']);
    $artwork_settings['enable_cdn'] = isset($_POST['enable_cdn']);
    $artwork_settings['cdn_url'] = esc_url_raw($_POST['cdn_url']);
    
    // Sanitize thumbnail sizes
    if (isset($_POST['thumbnail_small_width'])) {
        $artwork_settings['thumbnail_sizes']['small']['width'] = intval(sanitize_text_field($_POST['thumbnail_small_width']));
        $artwork_settings['thumbnail_sizes']['small']['height'] = intval(sanitize_text_field($_POST['thumbnail_small_height']));
        $artwork_settings['thumbnail_sizes']['small']['crop'] = isset($_POST['thumbnail_small_crop']);
        
        $artwork_settings['thumbnail_sizes']['medium']['width'] = intval(sanitize_text_field($_POST['thumbnail_medium_width']));
        $artwork_settings['thumbnail_sizes']['medium']['height'] = intval(sanitize_text_field($_POST['thumbnail_medium_height']));
        $artwork_settings['thumbnail_sizes']['medium']['crop'] = isset($_POST['thumbnail_medium_crop']);
        
        $artwork_settings['thumbnail_sizes']['large']['width'] = intval(sanitize_text_field($_POST['thumbnail_large_width']));
        $artwork_settings['thumbnail_sizes']['large']['height'] = intval(sanitize_text_field($_POST['thumbnail_large_height']));
        $artwork_settings['thumbnail_sizes']['large']['crop'] = isset($_POST['thumbnail_large_crop']);
    }
    
    // Sanitize display settings
    $artwork_settings['display_settings']['enable_lightbox'] = isset($_POST['enable_lightbox']);
    $artwork_settings['display_settings']['enable_zoom'] = isset($_POST['enable_zoom']);
    $artwork_settings['display_settings']['lazy_loading'] = isset($_POST['lazy_loading']);
    $artwork_settings['display_settings']['gallery_columns'] = intval(sanitize_text_field($_POST['gallery_columns']));
    
    // Save settings
    update_option('vortex_artwork_settings', $artwork_settings);
    
    // Show success message
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Artwork settings saved successfully!', 'vortex-ai-marketplace') . '</p></div>';
    
    // Clear any caches
    if (function_exists('vortex_clear_artwork_cache')) {
        vortex_clear_artwork_cache();
    }
}

?>

<div class="vortex-settings-wrapper">
    <form method="post" action="">
        <?php wp_nonce_field('vortex_artwork_settings_nonce'); ?>
        
        <div class="vortex-settings-section">
            <h2><?php echo esc_html__('Image Settings', 'vortex-ai-marketplace'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="image_quality"><?php echo esc_html__('Image Quality', 'vortex-ai-marketplace'); ?></label>
                    </th>
                    <td>
                        <input type="range" id="image_quality" name="image_quality" min="60" max="100" value="<?php echo esc_attr($artwork_settings['image_quality']); ?>" class="vortex-range-input">
                        <output for="image_quality"><?php echo esc_html($artwork_settings['image_quality']); ?></output>
                        <p class="description"><?php echo esc_html__('Image quality for JPEG compression (60-100).', 'vortex-ai-marketplace'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="max_file_size"><?php echo esc_html__('Maximum File Size (MB)', 'vortex-ai-marketplace'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="max_file_size" name="max_file_size" min="1" max="50" value="<?php echo esc_attr($artwork_settings['max_file_size']); ?>" class="small-text">
                        <p class="description"><?php echo esc_html__('Maximum file size for artwork uploads in megabytes.', 'vortex-ai-marketplace'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <?php echo esc_html__('Allowed Formats', 'vortex-ai-marketplace'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php echo esc_html__('Allowed Formats', 'vortex-ai-marketplace'); ?></legend>
                            
                            <label for="format_jpg">
                                <input type="checkbox" id="format_jpg" name="allowed_formats[]" value="jpg" <?php checked(in_array('jpg', $artwork_settings['allowed_formats'])); ?>>
                                <?php echo esc_html__('JPEG (.jpg, .jpeg)', 'vortex-ai-marketplace'); ?>
                            </label><br>
                            
                            <label for="format_png">
                                <input type="checkbox" id="format_png" name="allowed_formats[]" value="png" <?php checked(in_array('png', $artwork_settings['allowed_formats'])); ?>>
                                <?php echo esc_html__('PNG (.png)', 'vortex-ai-marketplace'); ?>
                            </label><br>
                            
                            <label for="format_webp">
                                <input type="checkbox" id="format_webp" name="allowed_formats[]" value="webp" <?php checked(in_array('webp', $artwork_settings['allowed_formats'])); ?>>
                                <?php echo esc_html__('WebP (.webp)', 'vortex-ai-marketplace'); ?>
                            </label><br>
                            
                            <label for="format_gif">
                                <input type="checkbox" id="format_gif" name="allowed_formats[]" value="gif" <?php checked(in_array('gif', $artwork_settings['allowed_formats'])); ?>>
                                <?php echo esc_html__('GIF (.gif)', 'vortex-ai-marketplace'); ?>
                            </label><br>
                            
                            <p class="description"><?php echo esc_html__('Select the file formats that artists can upload.', 'vortex-ai-marketplace'); ?></p>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="vortex-settings-section">
            <h2><?php echo esc_html__('Watermark Settings', 'vortex-ai-marketplace'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php echo esc_html__('Enable Watermark', 'vortex-ai-marketplace'); ?>
                    </th>
                    <td>
                        <label for="enable_watermark">
                            <input type="checkbox" id="enable_watermark" name="enable_watermark" <?php checked($artwork_settings['enable_watermark']); ?>>
                            <?php echo esc_html__('Add watermark to artwork images', 'vortex-ai-marketplace'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr class="watermark-setting">
                    <th scope="row">
                        <label for="watermark_text"><?php echo esc_html__('Watermark Text', 'vortex-ai-marketplace'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="watermark_text" name="watermark_text" value="<?php echo esc_attr($artwork_settings['watermark_text']); ?>" class="regular-text">
                        <p class="description"><?php echo esc_html__('Text to display as watermark. Leave empty to use site name.', 'vortex-ai-marketplace'); ?></p>
                    </td>
                </tr>
                
                <tr class="watermark-setting">
                    <th scope="row">
                        <label for="watermark_opacity"><?php echo esc_html__('Watermark Opacity', 'vortex-ai-marketplace'); ?></label>
                    </th>
                    <td>
                        <input type="range" id="watermark_opacity" name="watermark_opacity" min="10" max="100" value="<?php echo esc_attr($artwork_settings['watermark_opacity']); ?>" class="vortex-range-input">
                        <output for="watermark_opacity"><?php echo esc_html($artwork_settings['watermark_opacity']); ?></output>
                        <p class="description"><?php echo esc_html__('Opacity of the watermark (10-100).', 'vortex-ai-marketplace'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="vortex-settings-section">
            <h2><?php echo esc_html__('AI Enhancement', 'vortex-ai-marketplace'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php echo esc_html__('Enable AI Enhancement', 'vortex-ai-marketplace'); ?>
                    </th>
                    <td>
                        <label for="enable_ai_enhancement">
                            <input type="checkbox" id="enable_ai_enhancement" name="enable_ai_enhancement" <?php checked($artwork_settings['enable_ai_enhancement']); ?>>
                            <?php echo esc_html__('Use HURAII to enhance artwork quality', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description"><?php echo esc_html__('Applies intelligent optimization for better visual quality and smaller file sizes.', 'vortex-ai-marketplace'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <?php echo esc_html__('Clean EXIF Data', 'vortex-ai-marketplace'); ?>
                    </th>
                    <td>
                        <label for="enable_exif_cleaning">
                            <input type="checkbox" id="enable_exif_cleaning" name="enable_exif_cleaning" <?php checked($artwork_settings['enable_exif_cleaning']); ?>>
                            <?php echo esc_html__('Remove EXIF metadata from images', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description"><?php echo esc_html__('Removes personal information and reduces file size.', 'vortex-ai-marketplace'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="vortex-settings-section">
            <h2><?php echo esc_html__('Thumbnail Settings', 'vortex-ai-marketplace'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Small Size', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label for="thumbnail_small_width"><?php echo esc_html__('Width:', 'vortex-ai-marketplace'); ?></label>
                        <input type="number" id="thumbnail_small_width" name="thumbnail_small_width" value="<?php echo esc_attr($artwork_settings['thumbnail_sizes']['small']['width']); ?>" class="small-text" min="50" max="1000">
                        
                        <label for="thumbnail_small_height"><?php echo esc_html__('Height:', 'vortex-ai-marketplace'); ?></label>
                        <input type="number" id="thumbnail_small_height" name="thumbnail_small_height" value="<?php echo esc_attr($artwork_settings['thumbnail_sizes']['small']['height']); ?>" class="small-text" min="50" max="1000">
                        
                        <label for="thumbnail_small_crop">
                            <input type="checkbox" id="thumbnail_small_crop" name="thumbnail_small_crop" <?php checked($artwork_settings['thumbnail_sizes']['small']['crop']); ?>>
                            <?php echo esc_html__('Crop to exact dimensions', 'vortex-ai-marketplace'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Medium Size', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label for="thumbnail_medium_width"><?php echo esc_html__('Width:', 'vortex-ai-marketplace'); ?></label>
                        <input type="number" id="thumbnail_medium_width" name="thumbnail_medium_width" value="<?php echo esc_attr($artwork_settings['thumbnail_sizes']['medium']['width']); ?>" class="small-text" min="100" max="2000">
                        
                        <label for="thumbnail_medium_height"><?php echo esc_html__('Height:', 'vortex-ai-marketplace'); ?></label>
                        <input type="number" id="thumbnail_medium_height" name="thumbnail_medium_height" value="<?php echo esc_attr($artwork_settings['thumbnail_sizes']['medium']['height']); ?>" class="small-text" min="100" max="2000">
                        
                        <label for="thumbnail_medium_crop">
                            <input type="checkbox" id="thumbnail_medium_crop" name="thumbnail_medium_crop" <?php checked($artwork_settings['thumbnail_sizes']['medium']['crop']); ?>>
                            <?php echo esc_html__('Crop to exact dimensions', 'vortex-ai-marketplace'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Large Size', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label for="thumbnail_large_width"><?php echo esc_html__('Width:', 'vortex-ai-marketplace'); ?></label>
                        <input type="number" id="thumbnail_large_width" name="thumbnail_large_width" value="<?php echo esc_attr($artwork_settings['thumbnail_sizes']['large']['width']); ?>" class="small-text" min="200" max="5000">
                        
                        <label for="thumbnail_large_height"><?php echo esc_html__('Height:', 'vortex-ai-marketplace'); ?></label>
                        <input type="number" id="thumbnail_large_height" name="thumbnail_large_height" value="<?php echo esc_attr($artwork_settings['thumbnail_sizes']['large']['height']); ?>" class="small-text" min="200" max="5000">
                        
                        <label for="thumbnail_large_crop">
                            <input type="checkbox" id="thumbnail_large_crop" name="thumbnail_large_crop" <?php checked($artwork_settings['thumbnail_sizes']['large']['crop']); ?>>
                            <?php echo esc_html__('Crop to exact dimensions', 'vortex-ai-marketplace'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="vortex-settings-section">
            <h2><?php echo esc_html__('Display Settings', 'vortex-ai-marketplace'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Lightbox', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label for="enable_lightbox">
                            <input type="checkbox" id="enable_lightbox" name="enable_lightbox" <?php checked($artwork_settings['display_settings']['enable_lightbox']); ?>>
                            <?php echo esc_html__('Enable lightbox for artwork images', 'vortex-ai-marketplace'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Image Zoom', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label for="enable_zoom">
                            <input type="checkbox" id="enable_zoom" name="enable_zoom" <?php checked($artwork_settings['display_settings']['enable_zoom']); ?>>
                            <?php echo esc_html__('Enable image zoom on hover', 'vortex-ai-marketplace'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Lazy Loading', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label for="lazy_loading">
                            <input type="checkbox" id="lazy_loading" name="lazy_loading" <?php checked($artwork_settings['display_settings']['lazy_loading']); ?>>
                            <?php echo esc_html__('Enable lazy loading for images', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description"><?php echo esc_html__('Improves page load performance by loading images only when they enter the viewport.', 'vortex-ai-marketplace'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="gallery_columns"><?php echo esc_html__('Gallery Columns', 'vortex-ai-marketplace'); ?></label>
                    </th>
                    <td>
                        <select id="gallery_columns" name="gallery_columns">
                            <?php for ($i = 1; $i <= 6; $i++) : ?>
                                <option value="<?php echo esc_attr($i); ?>" <?php selected($artwork_settings['display_settings']['gallery_columns'], $i); ?>>
                                    <?php echo esc_html($i); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="vortex-settings-section">
            <h2><?php echo esc_html__('CDN Integration', 'vortex-ai-marketplace'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Enable CDN', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label for="enable_cdn">
                            <input type="checkbox" id="enable_cdn" name="enable_cdn" <?php checked($artwork_settings['enable_cdn']); ?>>
                            <?php echo esc_html__('Serve images through CDN', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description"><?php echo esc_html__('Improves loading speed and reduces server load.', 'vortex-ai-marketplace'); ?></p>
                    </td>
                </tr>
                
                <tr class="cdn-setting">
                    <th scope="row">
                        <label for="cdn_url"><?php echo esc_html__('CDN URL', 'vortex-ai-marketplace'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="cdn_url" name="cdn_url" value="<?php echo esc_url($artwork_settings['cdn_url']); ?>" class="regular-text">
                        <p class="description"><?php echo esc_html__('The base URL for your CDN (e.g., https://cdn.yourdomain.com).', 'vortex-ai-marketplace'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="submit" name="vortex_save_artwork_settings" class="button button-primary" value="<?php echo esc_attr__('Save Settings', 'vortex-ai-marketplace'); ?>">
        </p>
    </form>
</div>

<style>
.vortex-settings-wrapper {
    max-width: 1000px;
}

.vortex-settings-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.vortex-settings-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.vortex-range-input {
    vertical-align: middle;
    width: 200px;
}

output {
    display: inline-block;
    margin-left: 10px;
    background: #f0f0f1;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: bold;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Handle conditional display of watermark settings
    $('#enable_watermark').on('change', function() {
        $('.watermark-setting').toggle($(this).is(':checked'));
    }).trigger('change');
    
    // Handle conditional display of CDN settings
    $('#enable_cdn').on('change', function() {
        $('.cdn-setting').toggle($(this).is(':checked'));
    }).trigger('change');
    
    // Update range input output values
    $('input[type="range"]').on('input', function() {
        $(this).next('output').val($(this).val());
    });
    
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