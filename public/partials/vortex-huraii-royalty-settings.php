<div class="vortex-royalty-settings" id="vortex-royalty-settings">
    <div class="vortex-royalty-header">
        <h3><?php _e('Royalty Settings', 'vortex-ai-marketplace'); ?></h3>
        <p><?php _e('Define royalty recipients for your artwork. You can add up to 15% in royalties.', 'vortex-ai-marketplace'); ?></p>
        <p class="vortex-royalty-note"><?php _e('Note: A permanent 5% royalty is assigned to Marianne Nems, the creator of HURAII.', 'vortex-ai-marketplace'); ?></p>
    </div>
    
    <div class="vortex-royalty-list">
        <!-- Primary Creator (Fixed) -->
        <div class="vortex-royalty-item vortex-primary-creator">
            <div class="vortex-royalty-info">
                <span class="vortex-royalty-name">Marianne Nems (HURAII Creator)</span>
                <span class="vortex-royalty-percentage">5%</span>
            </div>
            <div class="vortex-royalty-address">
                <span class="vortex-royalty-label"><?php _e('Wallet:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-royalty-wallet">MNems5VRnbTH8VsJp7MYU3bWxkwqZQECaZJ12YBxVpP</span>
            </div>
            <div class="vortex-royalty-badge"><?php _e('Primary Creator', 'vortex-ai-marketplace'); ?></div>
        </div>
        
        <!-- Artist Default (Optional) -->
        <div class="vortex-royalty-item vortex-artist-default">
            <div class="vortex-royalty-controls">
                <label class="vortex-royalty-name-label">
                    <span><?php _e('Your Name:', 'vortex-ai-marketplace'); ?></span>
                    <input type="text" id="artist-royalty-name" name="artist_royalty_name" value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>" placeholder="<?php _e('Your Name', 'vortex-ai-marketplace'); ?>">
                </label>
                <label class="vortex-royalty-percentage-label">
                    <span><?php _e('Percentage:', 'vortex-ai-marketplace'); ?></span>
                    <input type="number" id="artist-royalty-percentage" name="artist_royalty_percentage" min="0" max="15" step="0.1" value="5" placeholder="0-15%">
                    <span class="vortex-percentage-symbol">%</span>
                </label>
            </div>
            <div class="vortex-royalty-wallet-field">
                <label for="artist-royalty-wallet">
                    <span><?php _e('Your Solana Wallet Address:', 'vortex-ai-marketplace'); ?></span>
                    <input type="text" id="artist-royalty-wallet" name="artist_royalty_wallet" placeholder="<?php _e('Solana wallet address', 'vortex-ai-marketplace'); ?>" value="<?php echo esc_attr(get_user_meta(get_current_user_id(), 'solana_wallet_address', true)); ?>">
                </label>
            </div>
            <div class="vortex-royalty-badge"><?php _e('Artist', 'vortex-ai-marketplace'); ?></div>
        </div>
    </div>
    
    <!-- Additional Royalty Recipients -->
    <div id="vortex-additional-royalties"></div>
    
    <div class="vortex-royalty-actions">
        <button type="button" id="vortex-add-royalty" class="vortex-button vortex-secondary-button">
            <i class="vortex-icon vortex-icon-plus"></i> <?php _e('Add Recipient', 'vortex-ai-marketplace'); ?>
        </button>
    </div>
    
    <div class="vortex-royalty-summary">
        <div class="vortex-royalty-total">
            <span class="vortex-royalty-total-label"><?php _e('Total Royalty:', 'vortex-ai-marketplace'); ?></span>
            <span class="vortex-royalty-total-value">5%</span>
            <span class="vortex-royalty-max"><?php _e('(Maximum: 20%)', 'vortex-ai-marketplace'); ?></span>
        </div>
        <div class="vortex-royalty-progress">
            <div class="vortex-royalty-progress-bar">
                <div class="vortex-royalty-progress-fill" style="width: 25%;"></div>
            </div>
        </div>
    </div>
</div> 