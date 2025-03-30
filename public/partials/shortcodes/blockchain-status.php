<?php
/**
 * Template for rendering blockchain status shortcode
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-blockchain-status">
    <div class="vortex-blockchain-status-header">
        <h3>
            <span class="vortex-blockchain-icon dashicons dashicons-database"></span>
            <?php echo esc_html__('Blockchain Status', 'vortex-ai-marketplace'); ?>
        </h3>
        
        <div class="vortex-blockchain-connection <?php echo esc_attr($status['connected'] ? 'connected' : 'disconnected'); ?>">
            <?php if ($status['connected']): ?>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php echo esc_html__('Connected', 'vortex-ai-marketplace'); ?>
            <?php else: ?>
                <span class="dashicons dashicons-no-alt"></span>
                <?php echo esc_html__('Disconnected', 'vortex-ai-marketplace'); ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($show_network && !empty($status['network']) && $status['connected']): ?>
    <div class="vortex-blockchain-section">
        <h4 class="vortex-blockchain-section-title"><?php echo esc_html__('Network', 'vortex-ai-marketplace'); ?></h4>
        <div class="vortex-blockchain-info-grid">
            <div class="vortex-blockchain-info-item">
                <span class="vortex-blockchain-info-label"><?php echo esc_html__('Name:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-blockchain-info-value"><?php echo esc_html($status['network']['name']); ?></span>
            </div>
            
            <div class="vortex-blockchain-info-item">
                <span class="vortex-blockchain-info-label"><?php echo esc_html__('Chain ID:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-blockchain-info-value"><?php echo esc_html($status['network']['chain_id']); ?></span>
            </div>
            
            <div class="vortex-blockchain-info-item">
                <span class="vortex-blockchain-info-label"><?php echo esc_html__('Status:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-blockchain-info-value vortex-network-status-<?php echo esc_attr($status['network']['status']); ?>">
                    <?php echo esc_html(ucfirst($status['network']['status'])); ?>
                </span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($show_gas && !empty($status['gas']) && $status['connected']): ?>
    <div class="vortex-blockchain-section">
        <h4 class="vortex-blockchain-section-title"><?php echo esc_html__('Gas Price (Gwei)', 'vortex-ai-marketplace'); ?></h4>
        <div class="vortex-blockchain-gas-prices">
            <div class="vortex-blockchain-gas-item">
                <div class="vortex-blockchain-gas-label"><?php echo esc_html__('Low', 'vortex-ai-marketplace'); ?></div>
                <div class="vortex-blockchain-gas-value"><?php echo esc_html($status['gas']['low']); ?></div>
            </div>
            
            <div class="vortex-blockchain-gas-item vortex-blockchain-gas-current">
                <div class="vortex-blockchain-gas-label"><?php echo esc_html__('Current', 'vortex-ai-marketplace'); ?></div>
                <div class="vortex-blockchain-gas-value"><?php echo esc_html($status['gas']['current']); ?></div>
            </div>
            
            <div class="vortex-blockchain-gas-item">
                <div class="vortex-blockchain-gas-label"><?php echo esc_html__('High', 'vortex-ai-marketplace'); ?></div>
                <div class="vortex-blockchain-gas-value"><?php echo esc_html($status['gas']['high']); ?></div>
            </div>
        </div>
        
        <div class="vortex-blockchain-gas-updated">
            <?php echo esc_html__('Last updated:', 'vortex-ai-marketplace'); ?> 
            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($status['gas']['updated']))); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($show_contract && !empty($status['contract']) && $status['connected']): ?>
    <div class="vortex-blockchain-section">
        <h4 class="vortex-blockchain-section-title"><?php echo esc_html__('Smart Contract', 'vortex-ai-marketplace'); ?></h4>
        <div class="vortex-blockchain-info-grid">
            <div class="vortex-blockchain-info-item">
                <span class="vortex-blockchain-info-label"><?php echo esc_html__('Address:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-blockchain-info-value vortex-contract-address">
                    <?php echo esc_html(substr($status['contract']['address'], 0, 8) . '...' . substr($status['contract']['address'], -6)); ?>
                    <button class="vortex-copy-btn" data-clipboard-text="<?php echo esc_attr($status['contract']['address']); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                    </button>
                </span>
            </div>
            
            <div class="vortex-blockchain-info-item">
                <span class="vortex-blockchain-info-label"><?php echo esc_html__('Verified:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-blockchain-info-value">
                    <?php if ($status['contract']['verified']): ?>
                        <span class="dashicons dashicons-yes-alt"></span>
                    <?php else: ?>
                        <span class="dashicons dashicons-no-alt"></span>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="vortex-blockchain-info-item">
                <span class="vortex-blockchain-info-label"><?php echo esc_html__('Version:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-blockchain-info-value"><?php echo esc_html($status['contract']['version']); ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div> 