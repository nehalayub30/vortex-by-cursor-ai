<?php
/**
 * Appearance Settings template.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials/settings
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get settings from the database
$primary_color = get_option('vortex_primary_color', '#7055DE');
$secondary_color = get_option('vortex_secondary_color', '#36D7B7');
$accent_color = get_option('vortex_accent_color', '#FF5E79');
$text_color = get_option('vortex_text_color', '#333333');
$background_color = get_option('vortex_background_color', '#FFFFFF');

$grid_columns_desktop = get_option('vortex_grid_columns_desktop', 4);
$grid_columns_tablet = get_option('vortex_grid_columns_tablet', 3);
$grid_columns_mobile = get_option('vortex_grid_columns_mobile', 2);

$enable_dark_mode = get_option('vortex_enable_dark_mode', 1);
$enable_rtl_support = get_option('vortex_enable_rtl_support', 1);
$custom_css = get_option('vortex_custom_css', '');

$marketplace_logo = get_option('vortex_marketplace_logo', '');
$marketplace_favicon = get_option('vortex_marketplace_favicon', '');
$show_powered_by = get_option('vortex_show_powered_by', 1);

$layout_style = get_option('vortex_layout_style', 'grid');
$enable_animations = get_option('vortex_enable_animations', 1);
$border_radius = get_option('vortex_border_radius', 8);

// Layout style options
$layout_options = array(
    'grid' => 'Grid Layout',
    'masonry' => 'Masonry Grid',
    'list' => 'List View',
    'carousel' => 'Carousel'
);
?>

<div class="vortex-settings-section" id="colors-settings">
    <h2><?php esc_html_e('Color Scheme', 'vortex-ai-marketplace'); ?></h2>
    <p class="description"><?php esc_html_e('Customize the colors used throughout the marketplace.', 'vortex-ai-marketplace'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="vortex_primary_color"><?php esc_html_e('Primary Color', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="color" id="vortex_primary_color" name="vortex_primary_color" 
                       value="<?php echo esc_attr($primary_color); ?>" class="vortex-color-picker" />
                <p class="description"><?php esc_html_e('Main brand color used for primary buttons and key UI elements.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_secondary_color"><?php esc_html_e('Secondary Color', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="color" id="vortex_secondary_color" name="vortex_secondary_color" 
                       value="<?php echo esc_attr($secondary_color); ?>" class="vortex-color-picker" />
                <p class="description"><?php esc_html_e('Secondary color used for accents and secondary UI elements.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_accent_color"><?php esc_html_e('Accent Color', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="color" id="vortex_accent_color" name="vortex_accent_color" 
                       value="<?php echo esc_attr($accent_color); ?>" class="vortex-color-picker" />
                <p class="description"><?php esc_html_e('Accent color used for highlights, badges, and notifications.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_text_color"><?php esc_html_e('Text Color', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="color" id="vortex_text_color" name="vortex_text_color" 
                       value="<?php echo esc_attr($text_color); ?>" class="vortex-color-picker" />
                <p class="description"><?php esc_html_e('Main text color used throughout the marketplace.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_background_color"><?php esc_html_e('Background Color', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="color" id="vortex_background_color" name="vortex_background_color" 
                       value="<?php echo esc_attr($background_color); ?>" class="vortex-color-picker" />
                <p class="description"><?php esc_html_e('Main background color for the marketplace pages.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
    </table>
</div>

<div class="vortex-settings-section" id="layout-settings">
    <h2><?php esc_html_e('Layout & Display', 'vortex-ai-marketplace'); ?></h2>
    <p class="description"><?php esc_html_e('Configure the layout and display settings for the marketplace.', 'vortex-ai-marketplace'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="vortex_layout_style"><?php esc_html_e('Default Layout Style', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <select id="vortex_layout_style" name="vortex_layout_style">
                    <?php foreach ($layout_options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($layout_style, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('Default layout style for displaying artworks in the marketplace.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_grid_columns_desktop"><?php esc_html_e('Grid Columns (Desktop)', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="number" id="vortex_grid_columns_desktop" name="vortex_grid_columns_desktop" 
                       value="<?php echo esc_attr($grid_columns_desktop); ?>" min="1" max="6" class="small-text" />
                <p class="description"><?php esc_html_e('Number of columns in the artwork grid on desktop screens.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_grid_columns_tablet"><?php esc_html_e('Grid Columns (Tablet)', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="number" id="vortex_grid_columns_tablet" name="vortex_grid_columns_tablet" 
                       value="<?php echo esc_attr($grid_columns_tablet); ?>" min="1" max="4" class="small-text" />
                <p class="description"><?php esc_html_e('Number of columns in the artwork grid on tablet screens.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_grid_columns_mobile"><?php esc_html_e('Grid Columns (Mobile)', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="number" id="vortex_grid_columns_mobile" name="vortex_grid_columns_mobile" 
                       value="<?php echo esc_attr($grid_columns_mobile); ?>" min="1" max="2" class="small-text" />
                <p class="description"><?php esc_html_e('Number of columns in the artwork grid on mobile screens.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_border_radius"><?php esc_html_e('Border Radius (px)', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <input type="number" id="vortex_border_radius" name="vortex_border_radius" 
                       value="<?php echo esc_attr($border_radius); ?>" min="0" max="50" class="small-text" />
                <p class="description"><?php esc_html_e('Border radius for cards, buttons, and other UI elements in pixels.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_enable_animations"><?php esc_html_e('Enable Animations', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <label for="vortex_enable_animations">
                    <input type="checkbox" id="vortex_enable_animations" name="vortex_enable_animations" 
                           value="1" <?php checked($enable_animations, 1); ?> />
                    <?php esc_html_e('Enable UI animations and transitions', 'vortex-ai-marketplace'); ?>
                </label>
                <p class="description"><?php esc_html_e('When enabled, UI elements will use animations and transitions for a more dynamic experience.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
    </table>
</div>

<div class="vortex-settings-section" id="branding-settings">
    <h2><?php esc_html_e('Branding', 'vortex-ai-marketplace'); ?></h2>
    <p class="description"><?php esc_html_e('Configure the branding elements for your marketplace.', 'vortex-ai-marketplace'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="vortex_marketplace_logo"><?php esc_html_e('Marketplace Logo', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <div class="vortex-image-preview-container">
                    <?php if (!empty($marketplace_logo)) : ?>
                        <img src="<?php echo esc_url($marketplace_logo); ?>" alt="Logo Preview" class="vortex-image-preview" />
                    <?php endif; ?>
                </div>
                <input type="hidden" id="vortex_marketplace_logo" name="vortex_marketplace_logo" value="<?php echo esc_attr($marketplace_logo); ?>" />
                <button type="button" class="button vortex-upload-image" data-target="vortex_marketplace_logo">
                    <?php esc_html_e('Select Logo', 'vortex-ai-marketplace'); ?>
                </button>
                <?php if (!empty($marketplace_logo)) : ?>
                    <button type="button" class="button vortex-remove-image" data-target="vortex_marketplace_logo">
                        <?php esc_html_e('Remove Logo', 'vortex-ai-marketplace'); ?>
                    </button>
                <?php endif; ?>
                <p class="description"><?php esc_html_e('Upload a logo for your marketplace. Recommended size: 200x50px.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_marketplace_favicon"><?php esc_html_e('Marketplace Favicon', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <div class="vortex-image-preview-container">
                    <?php if (!empty($marketplace_favicon)) : ?>
                        <img src="<?php echo esc_url($marketplace_favicon); ?>" alt="Favicon Preview" class="vortex-image-preview" style="max-width: 32px;" />
                    <?php endif; ?>
                </div>
                <input type="hidden" id="vortex_marketplace_favicon" name="vortex_marketplace_favicon" value="<?php echo esc_attr($marketplace_favicon); ?>" />
                <button type="button" class="button vortex-upload-image" data-target="vortex_marketplace_favicon">
                    <?php esc_html_e('Select Favicon', 'vortex-ai-marketplace'); ?>
                </button>
                <?php if (!empty($marketplace_favicon)) : ?>
                    <button type="button" class="button vortex-remove-image" data-target="vortex_marketplace_favicon">
                        <?php esc_html_e('Remove Favicon', 'vortex-ai-marketplace'); ?>
                    </button>
                <?php endif; ?>
                <p class="description"><?php esc_html_e('Upload a favicon for your marketplace. Recommended size: 32x32px.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_show_powered_by"><?php esc_html_e('Show "Powered by" Credit', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <label for="vortex_show_powered_by">
                    <input type="checkbox" id="vortex_show_powered_by" name="vortex_show_powered_by" 
                           value="1" <?php checked($show_powered_by, 1); ?> />
                    <?php esc_html_e('Show "Powered by VORTEX AI Marketplace" in the footer', 'vortex-ai-marketplace'); ?>
                </label>
                <p class="description"><?php esc_html_e('Display a small credit in the footer of marketplace pages.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
    </table>
</div>

<div class="vortex-settings-section" id="accessibility-settings">
    <h2><?php esc_html_e('Accessibility & Compatibility', 'vortex-ai-marketplace'); ?></h2>
    <p class="description"><?php esc_html_e('Configure accessibility and compatibility settings.', 'vortex-ai-marketplace'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="vortex_enable_dark_mode"><?php esc_html_e('Enable Dark Mode', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <label for="vortex_enable_dark_mode">
                    <input type="checkbox" id="vortex_enable_dark_mode" name="vortex_enable_dark_mode" 
                           value="1" <?php checked($enable_dark_mode, 1); ?> />
                    <?php esc_html_e('Enable dark mode theme option for users', 'vortex-ai-marketplace'); ?>
                </label>
                <p class="description"><?php esc_html_e('When enabled, users can switch between light and dark mode.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="vortex_enable_rtl_support"><?php esc_html_e('Enable RTL Support', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <label for="vortex_enable_rtl_support">
                    <input type="checkbox" id="vortex_enable_rtl_support" name="vortex_enable_rtl_support" 
                           value="1" <?php checked($enable_rtl_support, 1); ?> />
                    <?php esc_html_e('Enable right-to-left text direction support', 'vortex-ai-marketplace'); ?>
                </label>
                <p class="description"><?php esc_html_e('When enabled, the marketplace will support right-to-left languages like Arabic and Hebrew.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
    </table>
</div>

<div class="vortex-settings-section" id="custom-css-settings">
    <h2><?php esc_html_e('Custom CSS', 'vortex-ai-marketplace'); ?></h2>
    <p class="description"><?php esc_html_e('Add custom CSS to further customize the appearance of your marketplace.', 'vortex-ai-marketplace'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="vortex_custom_css"><?php esc_html_e('Custom CSS', 'vortex-ai-marketplace'); ?></label>
            </th>
            <td>
                <textarea id="vortex_custom_css" name="vortex_custom_css" rows="10" class="large-text code"><?php echo esc_textarea($custom_css); ?></textarea>
                <p class="description"><?php esc_html_e('Add custom CSS rules to customize the marketplace appearance. These styles will be applied on top of the default styles.', 'vortex-ai-marketplace'); ?></p>
            </td>
        </tr>
    </table>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Media uploader for logo and favicon
        $('.vortex-upload-image').click(function(e) {
            e.preventDefault();
            
            var button = $(this);
            var target = button.data('target');
            
            var mediaUploader = wp.media({
                title: '<?php esc_html_e('Select Image', 'vortex-ai-marketplace'); ?>',
                button: {
                    text: '<?php esc_html_e('Use This Image', 'vortex-ai-marketplace'); ?>'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#' + target).val(attachment.url);
                button.parent().find('.vortex-image-preview-container').html('<img src="' + attachment.url + '" alt="Preview" class="vortex-image-preview' + (target.includes('favicon') ? '" style="max-width: 32px;' : '') + '" />');
                button.after('<button type="button" class="button vortex-remove-image" data-target="' + target + '"><?php esc_html_e('Remove Image', 'vortex-ai-marketplace'); ?></button>');
            });
            
            mediaUploader.open();
        });
        
        // Remove image
        $(document).on('click', '.vortex-remove-image', function() {
            var button = $(this);
            var target = button.data('target');
            
            $('#' + target).val('');
            button.parent().find('.vortex-image-preview-container').html('');
            button.remove();
        });
    });
</script> 