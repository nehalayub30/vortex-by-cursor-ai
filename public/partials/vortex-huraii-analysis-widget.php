<?php
/**
 * Template for the HURAII Analysis Widget
 *
 * @package VORTEX
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vortex-huraii-widget vortex-huraii-analysis">
    <div class="vortex-huraii-widget-header">
        <h3 class="vortex-huraii-widget-title"><?php echo esc_html__('Artwork Analysis', 'vortex'); ?></h3>
    </div>

    <div class="vortex-huraii-widget-content">
        <form class="vortex-huraii-analysis-form" method="post">
            <div class="vortex-huraii-upload-group">
                <div class="vortex-huraii-upload-area">
                    <input type="file" 
                           name="artwork_image" 
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
                        <?php echo esc_html__('Click to upload an artwork', 'vortex'); ?>
                    </p>
                    <p class="vortex-huraii-upload-hint">
                        <?php echo esc_html__('Supported formats: JPG, PNG, WebP', 'vortex'); ?>
                    </p>
                </div>

                <div class="vortex-huraii-preview">
                    <img src="" alt="<?php echo esc_attr__('Preview', 'vortex'); ?>">
                </div>
            </div>

            <div class="vortex-huraii-analysis-options">
                <div class="vortex-huraii-option-group">
                    <label class="vortex-huraii-option-label">
                        <?php echo esc_html__('Analysis Type', 'vortex'); ?>
                    </label>
                    <div class="vortex-huraii-checkbox-group">
                        <label class="vortex-huraii-checkbox">
                            <input type="checkbox" name="analysis_type[]" value="style" checked>
                            <?php echo esc_html__('Art Style', 'vortex'); ?>
                        </label>
                        <label class="vortex-huraii-checkbox">
                            <input type="checkbox" name="analysis_type[]" value="composition" checked>
                            <?php echo esc_html__('Composition', 'vortex'); ?>
                        </label>
                        <label class="vortex-huraii-checkbox">
                            <input type="checkbox" name="analysis_type[]" value="color" checked>
                            <?php echo esc_html__('Color Analysis', 'vortex'); ?>
                        </label>
                        <label class="vortex-huraii-checkbox">
                            <input type="checkbox" name="analysis_type[]" value="technique" checked>
                            <?php echo esc_html__('Technique', 'vortex'); ?>
                        </label>
                    </div>
                </div>

                <div class="vortex-huraii-option-group">
                    <label for="vortex-huraii-analysis-depth" class="vortex-huraii-option-label">
                        <?php echo esc_html__('Analysis Depth', 'vortex'); ?>
                    </label>
                    <select id="vortex-huraii-analysis-depth" name="analysis_depth" class="vortex-huraii-option-input">
                        <option value="basic"><?php echo esc_html__('Basic', 'vortex'); ?></option>
                        <option value="detailed"><?php echo esc_html__('Detailed', 'vortex'); ?></option>
                        <option value="comprehensive"><?php echo esc_html__('Comprehensive', 'vortex'); ?></option>
                    </select>
                </div>
            </div>

            <div class="vortex-huraii-loading">
                <div class="vortex-huraii-loading-spinner"></div>
                <p><?php echo esc_html__('Analyzing artwork...', 'vortex'); ?></p>
            </div>

            <div class="vortex-huraii-error"></div>

            <div class="vortex-huraii-analysis-result">
                <div class="vortex-huraii-analysis-section">
                    <h4><?php echo esc_html__('Style Analysis', 'vortex'); ?></h4>
                    <div class="vortex-huraii-analysis-content"></div>
                </div>
                <div class="vortex-huraii-analysis-section">
                    <h4><?php echo esc_html__('Composition Analysis', 'vortex'); ?></h4>
                    <div class="vortex-huraii-analysis-content"></div>
                </div>
                <div class="vortex-huraii-analysis-section">
                    <h4><?php echo esc_html__('Color Analysis', 'vortex'); ?></h4>
                    <div class="vortex-huraii-analysis-content"></div>
                </div>
                <div class="vortex-huraii-analysis-section">
                    <h4><?php echo esc_html__('Technical Analysis', 'vortex'); ?></h4>
                    <div class="vortex-huraii-analysis-content"></div>
                </div>
            </div>

            <button type="submit" class="vortex-huraii-button">
                <?php echo esc_html__('Analyze Artwork', 'vortex'); ?>
            </button>
        </form>
    </div>
</div> 