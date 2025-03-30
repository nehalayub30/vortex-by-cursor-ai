<?php
/**
 * Thorius Artwork Tab
 * 
 * Template for the artwork tab in Thorius Concierge
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-thorius-artwork">
    <div class="vortex-thorius-artwork-header">
        <h4><?php esc_html_e('AI Art Generation', 'vortex-ai-marketplace'); ?></h4>
        <p><?php esc_html_e('Create unique artwork with HURAII AI', 'vortex-ai-marketplace'); ?></p>
    </div>
    
    <div class="vortex-thorius-artwork-prompt">
        <textarea id="vortex-thorius-artwork-prompt" placeholder="<?php esc_attr_e('Describe the image you want to create...', 'vortex-ai-marketplace'); ?>"></textarea>
        
        <div class="vortex-thorius-artwork-options">
            <div class="vortex-thorius-artwork-option">
                <label for="vortex-thorius-artwork-style"><?php esc_html_e('Style:', 'vortex-ai-marketplace'); ?></label>
                <select id="vortex-thorius-artwork-style">
                    <option value="realistic"><?php esc_html_e('Realistic', 'vortex-ai-marketplace'); ?></option>
                    <option value="abstract"><?php esc_html_e('Abstract', 'vortex-ai-marketplace'); ?></option>
                    <option value="digital-art"><?php esc_html_e('Digital Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="watercolor"><?php esc_html_e('Watercolor', 'vortex-ai-marketplace'); ?></option>
                    <option value="oil-painting"><?php esc_html_e('Oil Painting', 'vortex-ai-marketplace'); ?></option>
                    <option value="cartoon"><?php esc_html_e('Cartoon', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="vortex-thorius-artwork-option">
                <label for="vortex-thorius-artwork-size"><?php esc_html_e('Size:', 'vortex-ai-marketplace'); ?></label>
                <select id="vortex-thorius-artwork-size">
                    <option value="1024x1024"><?php esc_html_e('Square (1024×1024)', 'vortex-ai-marketplace'); ?></option>
                    <option value="1024x1792"><?php esc_html_e('Portrait (1024×1792)', 'vortex-ai-marketplace'); ?></option>
                    <option value="1792x1024"><?php esc_html_e('Landscape (1792×1024)', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
        </div>
        
        <button id="vortex-thorius-artwork-generate" class="vortex-thorius-btn vortex-thorius-primary-btn"><?php esc_html_e('Generate Image', 'vortex-ai-marketplace'); ?></button>
    </div>
    
    <div class="vortex-thorius-artwork-result">
        <div id="vortex-thorius-artwork-loading" class="vortex-thorius-artwork-loading" style="display:none;">
            <div class="vortex-thorius-loading-spinner"></div>
            <p><?php esc_html_e('Creating your masterpiece...', 'vortex-ai-marketplace'); ?></p>
        </div>
        <div id="vortex-thorius-artwork-output" class="vortex-thorius-artwork-output"></div>
    </div>
    
    <div class="vortex-thorius-artwork-actions" style="display:none;">
        <button id="vortex-thorius-artwork-save" class="vortex-thorius-btn"><?php esc_html_e('Save Image', 'vortex-ai-marketplace'); ?></button>
        <button id="vortex-thorius-artwork-nft" class="vortex-thorius-btn"><?php esc_html_e('Create NFT', 'vortex-ai-marketplace'); ?></button>
        <button id="vortex-thorius-artwork-modify" class="vortex-thorius-btn"><?php esc_html_e('Modify', 'vortex-ai-marketplace'); ?></button>
    </div>
</div> 