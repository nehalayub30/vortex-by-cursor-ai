<?php
/**
 * Thorius NFT Tab
 * 
 * Template for the NFT tab in Thorius Concierge
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-thorius-nft">
    <div class="vortex-thorius-nft-header">
        <h4><?php esc_html_e('NFT Creation & Management', 'vortex-ai-marketplace'); ?></h4>
        <p><?php esc_html_e('Create, mint, and manage your NFT collection', 'vortex-ai-marketplace'); ?></p>
    </div>
    
    <div class="vortex-thorius-nft-section">
        <h5><?php esc_html_e('Create New NFT', 'vortex-ai-marketplace'); ?></h5>
        
        <div class="vortex-thorius-nft-artwork-selection">
            <div class="vortex-thorius-nft-artwork-options">
                <button id="vortex-thorius-nft-select-existing" class="vortex-thorius-nft-option active">
                    <?php esc_html_e('Select Existing', 'vortex-ai-marketplace'); ?>
                </button>
                <button id="vortex-thorius-nft-create-new" class="vortex-thorius-nft-option">
                    <?php esc_html_e('Create New Artwork', 'vortex-ai-marketplace'); ?>
                </button>
                <button id="vortex-thorius-nft-upload" class="vortex-thorius-nft-option">
                    <?php esc_html_e('Upload File', 'vortex-ai-marketplace'); ?>
                </button>
            </div>
            
            <div id="vortex-thorius-nft-existing-artworks" class="vortex-thorius-nft-option-panel active">
                <div class="vortex-thorius-nft-gallery">
                    <p class="vortex-thorius-empty-state"><?php esc_html_e('No generated artworks found. Try creating some in the Artwork tab first.', 'vortex-ai-marketplace'); ?></p>
                </div>
            </div>
            
            <div id="vortex-thorius-nft-create-artwork" class="vortex-thorius-nft-option-panel">
                <p><?php esc_html_e('Create new artwork with HURAII:', 'vortex-ai-marketplace'); ?></p>
                <textarea id="vortex-thorius-nft-prompt" placeholder="<?php esc_attr_e('Describe the image to create...', 'vortex-ai-marketplace'); ?>"></textarea>
                <button id="vortex-thorius-nft-generate" class="vortex-thorius-btn"><?php esc_html_e('Generate', 'vortex-ai-marketplace'); ?></button>
                <div id="vortex-thorius-nft-generation-result" class="vortex-thorius-nft-generation-result"></div>
            </div>
            
            <div id="vortex-thorius-nft-upload-artwork" class="vortex-thorius-nft-option-panel">
                <p><?php esc_html_e('Upload your artwork file:', 'vortex-ai-marketplace'); ?></p>
                <input type="file" id="vortex-thorius-nft-file" accept="image/*">
                <p class="vortex-thorius-nft-note"><?php esc_html_e('Supported formats: JPG, PNG, GIF, WEBP. Max size: 15MB', 'vortex-ai-marketplace'); ?></p>
                <div id="vortex-thorius-nft-upload-preview" class="vortex-thorius-nft-upload-preview"></div>
            </div>
        </div>
        
        <div class="vortex-thorius-nft-details">
            <h5><?php esc_html_e('NFT Details', 'vortex-ai-marketplace'); ?></h5>
            
            <div class="vortex-thorius-nft-form">
                <div class="vortex-thorius-nft-field">
                    <label for="vortex-thorius-nft-name"><?php esc_html_e('Name:', 'vortex-ai-marketplace'); ?></label>
                    <input type="text" id="vortex-thorius-nft-name" placeholder="<?php esc_attr_e('Enter NFT name', 'vortex-ai-marketplace'); ?>">
                </div>
                
                <div class="vortex-thorius-nft-field">
                    <label for="vortex-thorius-nft-description"><?php esc_html_e('Description:', 'vortex-ai-marketplace'); ?></label>
                    <textarea id="vortex-thorius-nft-description" placeholder="<?php esc_attr_e('Enter NFT description', 'vortex-ai-marketplace'); ?>"></textarea>
                </div>
                
                <div class="vortex-thorius-nft-field">
                    <label for="vortex-thorius-nft-collection"><?php esc_html_e('Collection:', 'vortex-ai-marketplace'); ?></label>
                    <select id="vortex-thorius-nft-collection">
                        <option value="new"><?php esc_html_e('Create New Collection', 'vortex-ai-marketplace'); ?></option>
                        <option value="vortex-originals"><?php esc_html_e('Vortex Originals', 'vortex-ai-marketplace'); ?></option>
                        <option value="digital-dreams"><?php esc_html_e('Digital Dreams', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div id="vortex-thorius-nft-new-collection" class="vortex-thorius-nft-field" style="display:none;">
                    <label for="vortex-thorius-nft-collection-name"><?php esc_html_e('New Collection Name:', 'vortex-ai-marketplace'); ?></label>
                    <input type="text" id="vortex-thorius-nft-collection-name" placeholder="<?php esc_attr_e('Enter collection name', 'vortex-ai-marketplace'); ?>">
                </div>
                
                <div class="vortex-thorius-nft-field">
                    <label for="vortex-thorius-nft-royalty"><?php esc_html_e('Royalty Percentage:', 'vortex-ai-marketplace'); ?></label>
                    <input type="number" id="vortex-thorius-nft-royalty" min="0" max="15" value="10" step="0.5">
                    <span class="vortex-thorius-nft-field-suffix">%</span>
                </div>
                
                <div class="vortex-thorius-nft-field">
                    <label for="vortex-thorius-nft-price"><?php esc_html_e('Initial Price:', 'vortex-ai-marketplace'); ?></label>
                    <input type="number" id="vortex-thorius-nft-price" min="0" step="0.01" value="0.1">
                    <select id="vortex-thorius-nft-currency">
                        <option value="eth">ETH</option>
                        <option value="matic">MATIC</option>
                        <option value="sol">SOL</option>
                    </select>
                </div>
                
                <div class="vortex-thorius-nft-field">
                    <label for="vortex-thorius-nft-editions"><?php esc_html_e('Number of Editions:', 'vortex-ai-marketplace'); ?></label>
                    <input type="number" id="vortex-thorius-nft-editions" min="1" value="1">
                </div>
                
                <div class="vortex-thorius-nft-actions">
                    <button id="vortex-thorius-nft-mint" class="vortex-thorius-btn vortex-thorius-primary-btn" disabled><?php esc_html_e('Mint NFT', 'vortex-ai-marketplace'); ?></button>
                    <button id="vortex-thorius-nft-save-draft" class="vortex-thorius-btn"><?php esc_html_e('Save as Draft', 'vortex-ai-marketplace'); ?></button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="vortex-thorius-nft-marketplace-link">
        <a href="#" class="vortex-thorius-external-link"><?php esc_html_e('View Your NFT Collection in Marketplace', 'vortex-ai-marketplace'); ?> →</a>
    </div>
</div> 