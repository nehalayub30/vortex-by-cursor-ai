<?php
/**
 * CLOE Market Analysis template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-cloe-analysis <?php echo esc_attr($atts['class']); ?>" id="<?php echo esc_attr($analysis_id); ?>">
    <div class="vortex-cloe-form">
        <form class="vortex-analysis-form">
            <?php wp_nonce_field('vortex_analyze_market', 'analysis_nonce'); ?>
            <div class="vortex-form-field">
                <label for="<?php echo esc_attr($analysis_id); ?>-market"><?php esc_html_e('Market', 'vortex-ai-marketplace'); ?></label>
                <select id="<?php echo esc_attr($analysis_id); ?>-market" name="market">
                    <option value="crypto" <?php selected($atts['market'], 'crypto'); ?>><?php esc_html_e('Cryptocurrency', 'vortex-ai-marketplace'); ?></option>
                    <option value="stocks" <?php selected($atts['market'], 'stocks'); ?>><?php esc_html_e('Stock Market', 'vortex-ai-marketplace'); ?></option>
                    <option value="nft" <?php selected($atts['market'], 'nft'); ?>><?php esc_html_e('NFT Market', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="vortex-form-row">
                <div class="vortex-form-field">
                    <label for="<?php echo esc_attr($analysis_id); ?>-timeframe"><?php esc_html_e('Timeframe (days)', 'vortex-ai-marketplace'); ?></label>
                    <input type="number" id="<?php echo esc_attr($analysis_id); ?>-timeframe" name="timeframe" min="1" max="365" value="<?php echo esc_attr($atts['timeframe']); ?>">
                </div>
                
                <div class="vortex-form-field">
                    <label for="<?php echo esc_attr($analysis_id); ?>-detail"><?php esc_html_e('Detail Level', 'vortex-ai-marketplace'); ?></label>
                    <select id="<?php echo esc_attr($analysis_id); ?>-detail" name="detail_level">
                        <option value="low" <?php selected($atts['detail_level'], 'low'); ?>><?php esc_html_e('Basic', 'vortex-ai-marketplace'); ?></option>
                        <option value="medium" <?php selected($atts['detail_level'], 'medium'); ?>><?php esc_html_e('Standard', 'vortex-ai-marketplace'); ?></option>
                        <option value="high" <?php selected($atts['detail_level'], 'high'); ?>><?php esc_html_e('Advanced', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="vortex-form-submit">
                <button type="submit" class="vortex-button vortex-analyze-button"><?php esc_html_e('Analyze Market', 'vortex-ai-marketplace'); ?></button>
            </div>
        </form>
    </div>
    
    <div class="vortex-cloe-result">
        <div class="vortex-loading-indicator" style="display: none;">
            <div class="vortex-spinner"></div>
            <p><?php esc_html_e('CLOE is analyzing market data...', 'vortex-ai-marketplace'); ?></p>
        </div>
        <div class="vortex-analysis-display"></div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#<?php echo esc_js($analysis_id); ?> .vortex-analysis-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var resultContainer = $('#<?php echo esc_js($analysis_id); ?> .vortex-analysis-display');
        var loadingIndicator = $('#<?php echo esc_js($analysis_id); ?> .vortex-loading-indicator');
        
        // Show loading indicator
        loadingIndicator.show();
        resultContainer.hide();
        
        // Get form data
        var formData = {
            action: 'vortex_analyze_market',
            market: form.find('select[name="market"]').val(),
            timeframe: form.find('input[name="timeframe"]').val(),
            detail_level: form.find('select[name="detail_level"]').val(),
            nonce: form.find('#analysis_nonce').val()
        };
        
        // Send AJAX request
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                loadingIndicator.hide();
                
                if (response.success) {
                    // Display the analysis
                    resultContainer.html('<div class="vortex-analysis-content">' + response.data.analysis + '</div>');
                    resultContainer.show();
                } else {
                    // Show error message
                    resultContainer.html('<div class="vortex-error">' + response.data.message + '</div>');
                    resultContainer.show();
                }
            },
            error: function() {
                loadingIndicator.hide();
                resultContainer.html('<div class="vortex-error"><?php echo esc_js(__('An error occurred during analysis. Please try again.', 'vortex-ai-marketplace')); ?></div>');
                resultContainer.show();
            }
        });
    });
});
</script>

<style>
.vortex-cloe-analysis {
    margin: 20px 0;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

/* Rest of the styling is similar to artwork generation */
</style> 