<?php
/**
 * HURAII Artwork generation template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-huraii-artwork <?php echo esc_attr($atts['class']); ?>" id="<?php echo esc_attr($artwork_id); ?>">
    <div class="vortex-huraii-form">
        <form class="vortex-artwork-generator-form">
            <?php wp_nonce_field('vortex_generate_artwork', 'artwork_nonce'); ?>
            <div class="vortex-form-field">
                <label for="<?php echo esc_attr($artwork_id); ?>-prompt"><?php esc_html_e('Describe your artwork', 'vortex-ai-marketplace'); ?></label>
                <textarea id="<?php echo esc_attr($artwork_id); ?>-prompt" name="prompt" rows="3" placeholder="<?php esc_attr_e('Describe what you want HURAII to create...', 'vortex-ai-marketplace'); ?>"><?php echo esc_textarea($atts['prompt']); ?></textarea>
            </div>
            
            <div class="vortex-form-row">
                <div class="vortex-form-field">
                    <label for="<?php echo esc_attr($artwork_id); ?>-style"><?php esc_html_e('Art Style', 'vortex-ai-marketplace'); ?></label>
                    <select id="<?php echo esc_attr($artwork_id); ?>-style" name="style">
                        <option value="realistic" <?php selected($atts['style'], 'realistic'); ?>><?php esc_html_e('Realistic', 'vortex-ai-marketplace'); ?></option>
                        <option value="abstract" <?php selected($atts['style'], 'abstract'); ?>><?php esc_html_e('Abstract', 'vortex-ai-marketplace'); ?></option>
                        <option value="digital" <?php selected($atts['style'], 'digital'); ?>><?php esc_html_e('Digital Art', 'vortex-ai-marketplace'); ?></option>
                        <option value="painting" <?php selected($atts['style'], 'painting'); ?>><?php esc_html_e('Oil Painting', 'vortex-ai-marketplace'); ?></option>
                        <option value="sketch" <?php selected($atts['style'], 'sketch'); ?>><?php esc_html_e('Sketch', 'vortex-ai-marketplace'); ?></option>
                        <option value="pixel" <?php selected($atts['style'], 'pixel'); ?>><?php esc_html_e('Pixel Art', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="vortex-form-field">
                    <label for="<?php echo esc_attr($artwork_id); ?>-size"><?php esc_html_e('Size', 'vortex-ai-marketplace'); ?></label>
                    <select id="<?php echo esc_attr($artwork_id); ?>-size" name="size">
                        <option value="small" <?php selected($atts['size'], 'small'); ?>><?php esc_html_e('Small (512x512)', 'vortex-ai-marketplace'); ?></option>
                        <option value="medium" <?php selected($atts['size'], 'medium'); ?>><?php esc_html_e('Medium (1024x1024)', 'vortex-ai-marketplace'); ?></option>
                        <option value="large" <?php selected($atts['size'], 'large'); ?>><?php esc_html_e('Large (2048x2048)', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="vortex-form-submit">
                <button type="submit" class="vortex-button vortex-generate-button"><?php esc_html_e('Generate Artwork', 'vortex-ai-marketplace'); ?></button>
            </div>
        </form>
    </div>
    
    <div class="vortex-huraii-result">
        <div class="vortex-loading-indicator" style="display: none;">
            <div class="vortex-spinner"></div>
            <p><?php esc_html_e('HURAII is generating your artwork...', 'vortex-ai-marketplace'); ?></p>
        </div>
        <div class="vortex-artwork-display"></div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#<?php echo esc_js($artwork_id); ?> .vortex-artwork-generator-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var resultContainer = $('#<?php echo esc_js($artwork_id); ?> .vortex-artwork-display');
        var loadingIndicator = $('#<?php echo esc_js($artwork_id); ?> .vortex-loading-indicator');
        
        // Show loading indicator
        loadingIndicator.show();
        resultContainer.hide();
        
        // Get form data
        var formData = {
            action: 'vortex_generate_artwork',
            prompt: form.find('textarea[name="prompt"]').val(),
            style: form.find('select[name="style"]').val(),
            size: form.find('select[name="size"]').val(),
            nonce: form.find('#artwork_nonce').val()
        };
        
        // Send AJAX request
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                loadingIndicator.hide();
                
                if (response.success) {
                    // Display the generated artwork
                    resultContainer.html('<img src="' + response.data.image_url + '" alt="' + response.data.prompt + '" class="vortex-generated-artwork" />');
                    resultContainer.append('<div class="vortex-artwork-info"><p>' + response.data.prompt + '</p></div>');
                    resultContainer.show();
                } else {
                    // Show error message
                    resultContainer.html('<div class="vortex-error">' + response.data.message + '</div>');
                    resultContainer.show();
                }
            },
            error: function() {
                loadingIndicator.hide();
                resultContainer.html('<div class="vortex-error"><?php echo esc_js(__('An error occurred while generating artwork. Please try again.', 'vortex-ai-marketplace')); ?></div>');
                resultContainer.show();
            }
        });
    });
});
</script>

<style>
.vortex-huraii-artwork {
    margin: 20px 0;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.vortex-form-field {
    margin-bottom: 15px;
}

.vortex-form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.vortex-form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.vortex-form-row .vortex-form-field {
    flex: 1;
}

.vortex-form-submit {
    margin-top: 20px;
}

.vortex-loading-indicator {
    text-align: center;
    padding: 30px;
}

.vortex-spinner {
    border: 5px solid #f3f3f3;
    border-top: 5px solid #3498db;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: vortex-spin 2s linear infinite;
    margin: 0 auto 15px;
}

@keyframes vortex-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.vortex-artwork-display {
    margin-top: 20px;
    text-align: center;
}

.vortex-generated-artwork {
    max-width: 100%;
    height: auto;
    border-radius: 5px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.vortex-artwork-info {
    margin-top: 10px;
    font-style: italic;
    color: #666;
}

.vortex-error {
    color: #e74c3c;
    padding: 15px;
    background: #fdf4f4;
    border-left: 4px solid #e74c3c;
    margin: 15px 0;
}
</style> 