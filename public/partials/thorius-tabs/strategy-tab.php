<?php
/**
 * Thorius Strategy Tab
 * 
 * Template for the strategy tab in Thorius Concierge
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-thorius-strategy">
    <div class="vortex-thorius-strategy-header">
        <h4><?php esc_html_e('Business Strategy Assistant', 'vortex-ai-marketplace'); ?></h4>
        <p><?php esc_html_e('Get market insights and strategic recommendations', 'vortex-ai-marketplace'); ?></p>
    </div>
    
    <div class="vortex-thorius-strategy-form">
        <div class="vortex-thorius-strategy-filters">
            <div class="vortex-thorius-strategy-filter">
                <label for="vortex-thorius-strategy-market"><?php esc_html_e('Market:', 'vortex-ai-marketplace'); ?></label>
                <select id="vortex-thorius-strategy-market">
                    <option value="nft"><?php esc_html_e('NFT Market', 'vortex-ai-marketplace'); ?></option>
                    <option value="digital-art"><?php esc_html_e('Digital Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="traditional-art"><?php esc_html_e('Traditional Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="collectibles"><?php esc_html_e('Collectibles', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="vortex-thorius-strategy-filter">
                <label for="vortex-thorius-strategy-time"><?php esc_html_e('Timeframe:', 'vortex-ai-marketplace'); ?></label>
                <select id="vortex-thorius-strategy-time">
                    <option value="7days"><?php esc_html_e('7 Days', 'vortex-ai-marketplace'); ?></option>
                    <option value="30days" selected><?php esc_html_e('30 Days', 'vortex-ai-marketplace'); ?></option>
                    <option value="90days"><?php esc_html_e('90 Days', 'vortex-ai-marketplace'); ?></option>
                    <option value="1year"><?php esc_html_e('1 Year', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="vortex-thorius-strategy-filter">
                <label for="vortex-thorius-strategy-type"><?php esc_html_e('Analysis Type:', 'vortex-ai-marketplace'); ?></label>
                <select id="vortex-thorius-strategy-type">
                    <option value="market_analysis"><?php esc_html_e('Market Analysis', 'vortex-ai-marketplace'); ?></option>
                    <option value="price_optimization"><?php esc_html_e('Price Optimization', 'vortex-ai-marketplace'); ?></option>
                    <option value="trend_prediction"><?php esc_html_e('Trend Prediction', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
        </div>
        
        <div id="vortex-thorius-strategy-customFields" class="vortex-thorius-strategy-customFields">
            <!-- Dynamic fields will be added here based on analysis type -->
        </div>
        
        <button id="vortex-thorius-strategy-analyze" class="vortex-thorius-btn vortex-thorius-primary-btn"><?php esc_html_e('Analyze', 'vortex-ai-marketplace'); ?></button>
    </div>
    
    <div class="vortex-thorius-strategy-result">
        <div id="vortex-thorius-strategy-loading" class="vortex-thorius-strategy-loading" style="display:none;">
            <div class="vortex-thorius-loading-spinner"></div>
            <p><?php esc_html_e('Analyzing market data...', 'vortex-ai-marketplace'); ?></p>
        </div>
        <div id="vortex-thorius-strategy-output" class="vortex-thorius-strategy-output"></div>
    </div>
</div> 