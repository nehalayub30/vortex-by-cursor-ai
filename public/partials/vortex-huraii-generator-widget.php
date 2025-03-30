<?php
/**
 * Template for the HURAII Generator Widget
 *
 * @package VORTEX
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vortex-huraii-widget vortex-huraii-generator">
    <div class="vortex-huraii-widget-header">
        <h3 class="vortex-huraii-widget-title"><?php echo esc_html__('AI Art Generator', 'vortex'); ?></h3>
        <button type="button" class="vortex-huraii-advanced-toggle">
            <?php echo esc_html__('Advanced Options', 'vortex'); ?>
        </button>
    </div>

    <div class="vortex-huraii-widget-content">
        <form class="vortex-huraii-generator-form" method="post">
            <div class="vortex-huraii-prompt-group">
                <label for="vortex-huraii-prompt" class="vortex-huraii-option-label">
                    <?php echo esc_html__('Describe your artwork', 'vortex'); ?>
                </label>
                <textarea 
                    id="vortex-huraii-prompt" 
                    name="prompt" 
                    class="vortex-huraii-prompt-input" 
                    rows="3" 
                    required
                    placeholder="<?php echo esc_attr__('Enter a detailed description of the artwork you want to generate...', 'vortex'); ?>"
                ></textarea>
            </div>

            <div class="vortex-huraii-advanced-options">
                <div class="vortex-huraii-option-group">
                    <label for="vortex-huraii-style" class="vortex-huraii-option-label">
                        <?php echo esc_html__('Art Style', 'vortex'); ?>
                    </label>
                    <select id="vortex-huraii-style" name="style" class="vortex-huraii-option-input">
                        <option value="realistic"><?php echo esc_html__('Realistic', 'vortex'); ?></option>
                        <option value="abstract"><?php echo esc_html__('Abstract', 'vortex'); ?></option>
                        <option value="impressionist"><?php echo esc_html__('Impressionist', 'vortex'); ?></option>
                        <option value="surreal"><?php echo esc_html__('Surreal', 'vortex'); ?></option>
                        <option value="digital"><?php echo esc_html__('Digital Art', 'vortex'); ?></option>
                    </select>
                </div>

                <div class="vortex-huraii-option-group">
                    <label for="vortex-huraii-size" class="vortex-huraii-option-label">
                        <?php echo esc_html__('Image Size', 'vortex'); ?>
                    </label>
                    <select id="vortex-huraii-size" name="size" class="vortex-huraii-option-input">
                        <option value="512x512">512x512</option>
                        <option value="768x768">768x768</option>
                        <option value="1024x1024">1024x1024</option>
                    </select>
                </div>

                <div class="vortex-huraii-option-group">
                    <label for="vortex-huraii-quality" class="vortex-huraii-option-label">
                        <?php echo esc_html__('Quality Level', 'vortex'); ?>
                    </label>
                    <select id="vortex-huraii-quality" name="quality" class="vortex-huraii-option-input">
                        <option value="standard"><?php echo esc_html__('Standard', 'vortex'); ?></option>
                        <option value="high"><?php echo esc_html__('High', 'vortex'); ?></option>
                        <option value="ultra"><?php echo esc_html__('Ultra', 'vortex'); ?></option>
                    </select>
                </div>
            </div>

            <div class="vortex-huraii-loading">
                <div class="vortex-huraii-loading-spinner"></div>
                <p><?php echo esc_html__('Generating your artwork...', 'vortex'); ?></p>
            </div>

            <div class="vortex-huraii-error"></div>

            <div class="vortex-huraii-result"></div>

            <button type="submit" class="vortex-huraii-button">
                <?php echo esc_html__('Generate Artwork', 'vortex'); ?>
            </button>
        </form>
    </div>
</div> 