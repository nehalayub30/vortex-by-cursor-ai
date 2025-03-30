<?php
/**
 * Template for rendering token balance shortcode
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-token-balance">
    <div class="vortex-token-balance-header">
        <h3>
            <span class="vortex-token-icon dashicons dashicons-money-alt"></span>
            <?php echo esc_html__('Wallet Balance', 'vortex-ai-marketplace'); ?>
        </h3>
    </div>
    
    <div class="vortex-token-address">
        <span class="vortex-token-address-label"><?php echo esc_html__('Wallet Address:', 'vortex-ai-marketplace'); ?></span>
        <span class="vortex-token-address-value">
            <?php echo esc_html(substr($balance['wallet_address'], 0, 6) . '...' . substr($balance['wallet_address'], -4)); ?>
            <button class="vortex-copy-btn" data-clipboard-text="<?php echo esc_attr($balance['wallet_address']); ?>">
                <span class="dashicons dashicons-clipboard"></span>
            </button>
        </span>
    </div>
    
    <div class="vortex-token-list">
        <?php foreach ($balance['tokens'] as $token): ?>
            <div class="vortex-token-item">
                <div class="vortex-token-symbol"><?php echo esc_html($token['symbol']); ?></div>
                <div class="vortex-token-name"><?php echo esc_html($token['name']); ?></div>
                <div class="vortex-token-amount"><?php echo esc_html($token['balance']); ?></div>
                <div class="vortex-token-value"><?php echo esc_html('$' . number_format($token['value_usd'], 2)); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (!empty($balance['nfts'])): ?>
    <div class="vortex-token-nfts">
        <div class="vortex-token-nft-count">
            <span class="vortex-token-nft-label"><?php echo esc_html__('NFTs Owned:', 'vortex-ai-marketplace'); ?></span>
            <span class="vortex-token-nft-value"><?php echo esc_html($balance['nfts']); ?></span>
        </div>
        
        <a href="<?php echo esc_url(add_query_arg('user_id', $balance['user_id'], home_url('/my-nfts/'))); ?>" class="vortex-view-nfts-btn">
            <?php echo esc_html__('View My NFTs', 'vortex-ai-marketplace'); ?>
        </a>
    </div>
    <?php endif; ?>
    
    <div class="vortex-token-updated">
        <?php echo esc_html__('Last updated:', 'vortex-ai-marketplace'); ?> 
        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($balance['last_updated']))); ?>
    </div>
</div> 