<?php
/**
 * Business Strategist Recommendation template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-strategy-recommendation <?php echo esc_attr($atts['class']); ?>" id="<?php echo esc_attr($strategy_id); ?>">
    <div class="vortex-strategy-form">
        <form class="vortex-strategy-generator-form">
            <?php wp_nonce_field('vortex_generate_strategy', 'strategy_nonce'); ?>
            
            <div class="vortex-form-field">
                <label for="<?php echo esc_attr($strategy_id); ?>-industry"><?php esc_html_e('Industry', 'vortex-ai-marketplace'); ?></label>
                <select id="<?php echo esc_attr($strategy_id); ?>-industry" name="industry">
                    <option value="general" <?php selected($atts['industry'], 'general'); ?>><?php esc_html_e('General Business', 'vortex-ai-marketplace'); ?></option>
                    <option value="tech" <?php selected($atts['industry'], 'tech'); ?>><?php esc_html_e('Technology', 'vortex-ai-marketplace'); ?></option>
                    <option value="finance" <?php selected($atts['industry'], 'finance'); ?>><?php esc_html_e('Finance', 'vortex-ai-marketplace'); ?></option>
                    <option value="retail" <?php selected($atts['industry'], 'retail'); ?>><?php esc_html_e('Retail', 'vortex-ai-marketplace'); ?></option>
                    <option value="healthcare" <?php selected($atts['industry'], 'healthcare'); ?>><?php esc_html_e('Healthcare', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="vortex-form-row">
                <div class="vortex-form-field">
                    <label for="<?php echo esc_attr($strategy_id); ?>-focus"><?php esc_html_e('Strategic Focus', 'vortex-ai-marketplace'); ?></label>
                    <select id="<?php echo esc_attr($strategy_id); ?>-focus" name="focus">
                        <option value="growth" <?php selected($atts['focus'], 'growth'); ?>><?php esc_html_e('Growth', 'vortex-ai-marketplace'); ?></option>
                        <option value="efficiency" <?php selected($atts['focus'], 'efficiency'); ?>><?php esc_html_e('Operational Efficiency', 'vortex-ai-marketplace'); ?></option>
                        <option value="innovation" <?php selected($atts['focus'], 'innovation'); ?>><?php esc_html_e('Innovation', 'vortex-ai-marketplace'); ?></option>
                        <option value="customer" <?php selected($atts['focus'], 'customer'); ?>><?php esc_html_e('Customer Experience', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="vortex-form-field">
                    <label for="<?php echo esc_attr($strategy_id); ?>-timeframe"><?php esc_html_e('Timeframe', 'vortex-ai-marketplace'); ?></label>
                    <select id="<?php echo esc_attr($strategy_id); ?>-timeframe" name="timeframe">
                        <option value="short" <?php selected($atts['timeframe'], 'short'); ?>><?php esc_html_e('Short Term (3-6 months)', 'vortex-ai-marketplace'); ?></option>
                        <option value="medium" <?php selected($atts['timeframe'], 'medium'); ?>><?php esc_html_e('Medium Term (6-18 months)', 'vortex-ai-marketplace'); ?></option>
                        <option value="long" <?php selected($atts['timeframe'], 'long'); ?>><?php esc_html_e('Long Term (18+ months)', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="vortex-form-field">
                <label for="<?php echo esc_attr($strategy_id); ?>-context"><?php esc_html_e('Business Context (Optional)', 'vortex-ai-marketplace'); ?></label>
                <textarea id="<?php echo esc_attr($strategy_id); ?>-context" name="context" rows="3" placeholder="<?php esc_attr_e('Provide additional context about your business challenges or goals...', 'vortex-ai-marketplace'); ?>"></textarea>
            </div>
            
            <div class="vortex-form-submit">
                <button type="submit" class="vortex-button vortex-generate-button"><?php esc_html_e('Generate Strategy', 'vortex-ai-marketplace'); ?></button>
            </div>
        </form>
    </div>
    
    <div class="vortex-strategy-result">
        <div class="vortex-loading-indicator" style="display: none;">
            <div class="vortex-spinner"></div>
            <p><?php esc_html_e('Business Strategist is generating recommendations...', 'vortex-ai-marketplace'); ?></p>
        </div>
        <div class="vortex-strategy-display"></div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#<?php echo esc_js($strategy_id); ?> .vortex-strategy-generator-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var resultContainer = $('#<?php echo esc_js($strategy_id); ?> .vortex-strategy-display');
        var loadingIndicator = $('#<?php echo esc_js($strategy_id); ?> .vortex-loading-indicator');
        
        // Show loading indicator
        loadingIndicator.show();
        resultContainer.hide();
        
        // Get form data
        var formData = {
            action: 'vortex_generate_strategy',
            industry: form.find('select[name="industry"]').val(),
            focus: form.find('select[name="focus"]').val(),
            timeframe: form.find('select[name="timeframe"]').val(),
            context: form.find('textarea[name="context"]').val(),
            nonce: form.find('#strategy_nonce').val()
        };
        
        // Send AJAX request
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                loadingIndicator.hide();
                
                if (response.success) {
                    // Display the strategy
                    resultContainer.html('<div class="vortex-strategy-content">' + response.data.strategy + '</div>');
                    resultContainer.show();
                } else {
                    // Show error message
                    resultContainer.html('<div class="vortex-error">' + response.data.message + '</div>');
                    resultContainer.show();
                }
            },
            error: function() {
                loadingIndicator.hide();
                resultContainer.html('<div class="vortex-error"><?php echo esc_js(__('An error occurred while generating strategy. Please try again.', 'vortex-ai-marketplace')); ?></div>');
                resultContainer.show();
            }
        });
    });
});
</script>

<style>
.vortex-strategy-recommendation {
    margin: 20px 0;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

/* Rest of the styling is similar to artwork generation */
</style> 