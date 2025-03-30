<?php
/**
 * Multi-Modal Upload Form Template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-multimodal-uploader <?php echo esc_attr($atts['class']); ?>" id="<?php echo esc_attr($uploader_id); ?>" data-target="<?php echo esc_attr($atts['target']); ?>">
    <h3><?php _e('Enhance Your AI Experience', 'vortex-ai-marketplace'); ?></h3>
    <p class="vortex-upload-description"><?php _e('Upload images, documents, or data to provide additional context to the AI.', 'vortex-ai-marketplace'); ?></p>
    
    <form class="vortex-multimodal-form">
        <div class="vortex-file-upload-area">
            <input type="file" name="multimodal_file" accept="<?php echo esc_attr($atts['accept']); ?>" class="vortex-file-input" />
            <div class="vortex-upload-placeholder">
                <span class="dashicons dashicons-upload"></span>
                <p><?php _e('Drag and drop files here or click to browse', 'vortex-ai-marketplace'); ?></p>
            </div>
        </div>
        
        <div class="vortex-upload-preview"></div>
        <div class="vortex-upload-message"></div>
        
        <div class="vortex-button-row">
            <button type="button" class="vortex-upload-button"><?php echo esc_html($atts['button_text']); ?></button>
        </div>
        
        <?php wp_nonce_field('vortex_multimodal_upload', 'multimodal_nonce'); ?>
    </form>
</div> 