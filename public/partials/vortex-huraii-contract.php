<div class="vortex-artwork-contract">
    <div class="vortex-contract-header">
        <h3><?php _e('Artwork Smart Contract', 'vortex-ai-marketplace'); ?></h3>
        <p><?php _e('This artwork is protected by a smart contract that verifies its origin and includes creator royalties.', 'vortex-ai-marketplace'); ?></p>
    </div>
    
    <div class="vortex-contract-details">
        <div class="vortex-contract-item">
            <span class="vortex-contract-label"><?php _e('Contract Hash:', 'vortex-ai-marketplace'); ?></span>
            <span class="vortex-contract-value" id="contract-hash"><?php echo esc_html($contract['contract_hash']); ?></span>
            <button class="vortex-copy-button" data-copy="contract-hash">
                <span class="dashicons dashicons-clipboard"></span>
            </button>
        </div>
        
        <div class="vortex-contract-item">
            <span class="vortex-contract-label"><?php _e('Created:', 'vortex-ai-marketplace'); ?></span>
            <span class="vortex-contract-value"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($contract['creation_time']))); ?></span>
        </div>
        
        <div class="vortex-contract-item">
            <span class="vortex-contract-label"><?php _e('Creator Royalty:', 'vortex-ai-marketplace'); ?></span>
            <span class="vortex-contract-value"><?php echo esc_html($contract['creator']['royalty_percentage'] . '%'); ?></span>
        </div>
        
        <div class="vortex-contract-item">
            <span class="vortex-contract-label"><?php _e('Blockchain Record:', 'vortex-ai-marketplace'); ?></span>
            <span class="vortex-contract-value" id="blockchain-tx"><?php echo esc_html($contract['blockchain_record']['transaction_id']); ?></span>
            <button class="vortex-copy-button" data-copy="blockchain-tx">
                <span class="dashicons dashicons-clipboard"></span>
            </button>
        </div>
        
        <div class="vortex-contract-item vortex-contract-verification">
            <a href="<?php echo esc_url($contract['blockchain_record']['verification_url']); ?>" class="vortex-verification-link" target="_blank">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Verify Authenticity', 'vortex-ai-marketplace'); ?>
            </a>
        </div>
    </div>
    
    <div class="vortex-creator-signature">
        <blockquote>
            <?php echo esc_html($contract['creator']['signature']); ?>
            <cite><?php echo esc_html($contract['creator']['name']); ?> (<?php echo esc_html($contract['creator']['alias']); ?>)</cite>
        </blockquote>
    </div>
</div> 