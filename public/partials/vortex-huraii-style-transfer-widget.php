<?php
/**
 * Template for the HURAII Style Transfer Widget
 *
 * @package VORTEX
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vortex-huraii-widget vortex-huraii-style-transfer">
    <div class="vortex-huraii-widget-header">
        <h3 class="vortex-huraii-widget-title"><?php echo esc_html__('Style Transfer', 'vortex'); ?></h3>
    </div>

    <div class="vortex-huraii-widget-content">
        <form class="vortex-huraii-style-transfer-form" method="post">
            <div class="vortex-huraii-upload-group">
                <div class="vortex-huraii-upload-area">
                    <input type="file" 
                           name="source_image" 
                           class="vortex-huraii-file-input" 
                           accept="image/*" 
                           required
                           style="display: none;">
                    <div class="vortex-huraii-upload-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                    </div>
                    <p class="vortex-huraii-upload-text">
                        <?php echo esc_html__('Click to upload an image', 'vortex'); ?>
                    </p>
                    <p class="vortex-huraii-upload-hint">
                        <?php echo esc_html__('Supported formats: JPG, PNG, WebP', 'vortex'); ?>
                    </p>
                </div>

                <div class="vortex-huraii-preview">
                    <img src="" alt="<?php echo esc_attr__('Preview', 'vortex'); ?>">
                </div>
            </div>

            <div class="vortex-huraii-style-group">
                <label for="vortex-huraii-style" class="vortex-huraii-option-label">
                    <?php echo esc_html__('Target Style', 'vortex'); ?>
                </label>
                <select id="vortex-huraii-style" name="style" class="vortex-huraii-option-input" required>
                    <option value=""><?php echo esc_html__('Select a style...', 'vortex'); ?></option>
                    <option value="vangogh"><?php echo esc_html__('Van Gogh', 'vortex'); ?></option>
                    <option value="picasso"><?php echo esc_html__('Picasso', 'vortex'); ?></option>
                    <option value="monet"><?php echo esc_html__('Monet', 'vortex'); ?></option>
                    <option value="warhol"><?php echo esc_html__('Warhol', 'vortex'); ?></option>
                    <option value="custom"><?php echo esc_html__('Custom Style', 'vortex'); ?></option>
                </select>
            </div>

            <div class="vortex-huraii-custom-style" style="display: none;">
                <label for="vortex-huraii-custom-style-text" class="vortex-huraii-option-label">
                    <?php echo esc_html__('Describe the style', 'vortex'); ?>
                </label>
                <textarea 
                    id="vortex-huraii-custom-style-text" 
                    name="custom_style" 
                    class="vortex-huraii-option-input" 
                    rows="2"
                    placeholder="<?php echo esc_attr__('Describe the artistic style you want to apply...', 'vortex'); ?>"
                ></textarea>
            </div>

            <div class="vortex-huraii-loading">
                <div class="vortex-huraii-loading-spinner"></div>
                <p><?php echo esc_html__('Applying style transfer...', 'vortex'); ?></p>
            </div>

            <div class="vortex-huraii-error"></div>

            <div class="vortex-huraii-result"></div>

            <button type="submit" class="vortex-huraii-button">
                <?php echo esc_html__('Apply Style', 'vortex'); ?>
            </button>
        </form>
    </div>
</div> 