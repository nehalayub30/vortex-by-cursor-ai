<?php
/**
 * Template for displaying artwork provenance
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('WPINC')) {
    die;
}

// Get artwork data
$artwork_title = get_the_title($artwork_id);
$artist_id = get_post_meta($artwork_id, 'vortex_artwork_creator', true);
$artist_name = get_the_author_meta('display_name', $artist_id);
$creation_date = get_the_date('F j, Y', $artwork_id);
$blockchain = get_post_meta($artwork_id, 'vortex_blockchain_network', true) ?: 'Solana';
$royalty_percentage = get_post_meta($artwork_id, 'vortex_royalty_percentage', true) ?: '2.5';
?>

<div class="vortex-provenance-container">
    <h2 class="vortex-provenance-title"><?php _e('Artwork Provenance', 'vortex-ai-marketplace'); ?></h2>
    
    <div class="vortex-provenance-summary">
        <div class="vortex-provenance-artwork">
            <?php if (has_post_thumbnail($artwork_id)) : ?>
                <?php echo get_the_post_thumbnail($artwork_id, 'medium'); ?>
            <?php endif; ?>
        </div>
        
        <div class="vortex-provenance-details">
            <h3 class="vortex-artwork-title"><?php echo esc_html($artwork_title); ?></h3>
            
            <div class="vortex-provenance-meta">
                <div class="vortex-meta-item">
                    <span class="vortex-meta-label"><?php _e('Artist:', 'vortex-ai-marketplace'); ?></span>
                    <span class="vortex-meta-value"><?php echo esc_html($artist_name); ?></span>
                </div>
                
                <div class="vortex-meta-item">
                    <span class="vortex-meta-label"><?php _e('Creation Date:', 'vortex-ai-marketplace'); ?></span>
                    <span class="vortex-meta-value"><?php echo esc_html($creation_date); ?></span>
                </div>
                
                <div class="vortex-meta-item">
                    <span class="vortex-meta-label"><?php _e('Blockchain:', 'vortex-ai-marketplace'); ?></span>
                    <span class="vortex-meta-value"><?php echo esc_html($blockchain); ?></span>
                </div>
                
                <div class="vortex-meta-item">
                    <span class="vortex-meta-label"><?php _e('Royalty:', 'vortex-ai-marketplace'); ?></span>
                    <span class="vortex-meta-value"><?php echo esc_html($royalty_percentage); ?>%</span>
                </div>
                
                <div class="vortex-meta-item vortex-provenance-id">
                    <span class="vortex-meta-label"><?php _e('Provenance ID:', 'vortex-ai-marketplace'); ?></span>
                    <span class="vortex-meta-value"><?php echo esc_html($provenance_id); ?></span>
                </div>
                
                <div class="vortex-meta-item vortex-blockchain-tx">
                    <span class="vortex-meta-label"><?php _e('Transaction ID:', 'vortex-ai-marketplace'); ?></span>
                    <span class="vortex-meta-value">
                        <a href="https://explorer.solana.com/tx/<?php echo esc_attr($blockchain_tx); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html(substr($blockchain_tx, 0, 16) . '...'); ?>
                            <span class="dashicons dashicons-external"></span>
                        </a>
                    </span>
                </div>
            </div>
            
            <?php if ($show_qr) : ?>
            <div class="vortex-provenance-qr">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode(home_url('/artwork-verification/' . $provenance_id)); ?>" alt="Verification QR Code">
                <p class="vortex-qr-caption"><?php _e('Scan to verify authenticity', 'vortex-ai-marketplace'); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="vortex-provenance-history">
        <h3 class="vortex-history-title"><?php _e('Ownership History', 'vortex-ai-marketplace'); ?></h3>
        
        <?php if (empty($ownership_history)) : ?>
        
        <div class="vortex-no-history">
            <?php _e('Original creation - No transfers yet', 'vortex-ai-marketplace'); ?>
        </div>
        
        <?php else : ?>
        
        <div class="vortex-history-timeline">
            <?php foreach ($ownership_history as $record) : ?>
            <div class="vortex-history-event">
                <div class="vortex-event-date">
                    <?php echo date_i18n(get_option('date_format'), strtotime($record['date'])); ?>
                </div>
                
                <div class="vortex-event-details">
                    <div class="vortex-event-type">
                        <?php echo esc_html($record['type']); ?>
                    </div>
                    
                    <div class="vortex-event-description">
                        <?php if ($record['type'] === 'Creation') : ?>
                            <?php _e('Created by', 'vortex-ai-marketplace'); ?> <strong><?php echo esc_html($record['from_name']); ?></strong>
                        <?php elseif ($record['type'] === 'Transfer') : ?>
                            <?php _e('Transferred from', 'vortex-ai-marketplace'); ?> <strong><?php echo esc_html($record['from_name']); ?></strong> 
                            <?php _e('to', 'vortex-ai-marketplace'); ?> <strong><?php echo esc_html($record['to_name']); ?></strong>
                        <?php elseif ($record['type'] === 'Sale') : ?>
                            <?php _e('Sold by', 'vortex-ai-marketplace'); ?> <strong><?php echo esc_html($record['from_name']); ?></strong> 
                            <?php _e('to', 'vortex-ai-marketplace'); ?> <strong><?php echo esc_html($record['to_name']); ?></strong>
                            <?php _e('for', 'vortex-ai-marketplace'); ?> <strong><?php echo esc_html($record['price']); ?> TOLA</strong>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($record['tx_id'])) : ?>
                    <div class="vortex-event-transaction">
                        <a href="https://explorer.solana.com/tx/<?php echo esc_attr($record['tx_id']); ?>" target="_blank" rel="noopener noreferrer">
                            <?php _e('View Transaction', 'vortex-ai-marketplace'); ?>
                            <span class="dashicons dashicons-external"></span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
    
    <div class="vortex-provenance-verification">
        <h3 class="vortex-verification-title"><?php _e('Verify Authenticity', 'vortex-ai-marketplace'); ?></h3>
        
        <div class="vortex-verification-links">
            <a href="<?php echo esc_url(home_url('/artwork-verification/' . $provenance_id)); ?>" class="vortex-verification-link" target="_blank">
                <span class="dashicons dashicons-visibility"></span>
                <?php _e('View Authentication Page', 'vortex-ai-marketplace'); ?>
            </a>
            
            <a href="https://explorer.solana.com/address/<?php echo esc_attr(get_post_meta($artwork_id, 'vortex_token_address', true)); ?>" class="vortex-blockchain-link" target="_blank" rel="noopener noreferrer">
                <span class="dashicons dashicons-admin-links"></span>
                <?php _e('View on Blockchain', 'vortex-ai-marketplace'); ?>
            </a>
        </div>
    </div>
</div> 