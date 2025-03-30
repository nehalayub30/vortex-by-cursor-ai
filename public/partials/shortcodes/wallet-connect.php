<?php
/**
 * Template for wallet connect shortcode
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get wallet address if user is logged in
$wallet_address = '';
$is_connected = false;
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $wallet_address = get_user_meta($user_id, 'vortex_wallet_address', true);
    $is_connected = !empty($wallet_address);
}

$button_class = 'vortex-wallet-connect-button';
if (!empty($atts['class'])) {
    $button_class .= ' ' . esc_attr($atts['class']);
}

$redirect_url = !empty($atts['redirect']) ? esc_url($atts['redirect']) : '';
?>

<div class="vortex-wallet-connect-container">
    <?php if ($is_connected): ?>
        <div class="vortex-wallet-connected">
            <span class="vortex-wallet-address">
                <?php echo esc_html(substr($wallet_address, 0, 6) . '...' . substr($wallet_address, -4)); ?>
            </span>
            <button type="button" class="vortex-wallet-disconnect-btn">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
    <?php else: ?>
        <button type="button" class="<?php echo esc_attr($button_class); ?>" 
                data-redirect="<?php echo esc_attr($redirect_url); ?>">
            <?php echo esc_html($atts['text']); ?>
        </button>
    <?php endif; ?>
    
    <div class="vortex-wallet-modal" style="display: none;">
        <div class="vortex-wallet-modal-content">
            <button type="button" class="vortex-wallet-modal-close">&times;</button>
            <h3><?php _e('Connect Wallet', 'vortex-ai-marketplace'); ?></h3>
            
            <div class="vortex-wallet-options">
                <button type="button" class="vortex-wallet-option" data-wallet="metamask">
                    <img src="<?php echo esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/img/metamask.png'); ?>" alt="MetaMask">
                    <span>MetaMask</span>
                </button>
                
                <button type="button" class="vortex-wallet-option" data-wallet="wallet-connect">
                    <img src="<?php echo esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/img/walletconnect.png'); ?>" alt="WalletConnect">
                    <span>WalletConnect</span>
                </button>
                
                <button type="button" class="vortex-wallet-option" data-wallet="coinbase">
                    <img src="<?php echo esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/img/coinbase.png'); ?>" alt="Coinbase Wallet">
                    <span>Coinbase Wallet</span>
                </button>
            </div>
            
            <div class="vortex-wallet-message"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Connect wallet button click
    $('.vortex-wallet-connect-button').on('click', function() {
        $('.vortex-wallet-modal').fadeIn(300);
    });
    
    // Close modal on X click
    $('.vortex-wallet-modal-close').on('click', function() {
        $('.vortex-wallet-modal').fadeOut(300);
    });
    
    // Close modal on outside click
    $('.vortex-wallet-modal').on('click', function(e) {
        if ($(e.target).hasClass('vortex-wallet-modal')) {
            $(this).fadeOut(300);
        }
    });
    
    // Wallet option click
    $('.vortex-wallet-option').on('click', function() {
        var wallet = $(this).data('wallet');
        var messageDiv = $('.vortex-wallet-message');
        
        // Show connecting message
        messageDiv.html('<p class="vortex-connecting"><?php echo esc_js(__('Connecting...', 'vortex-ai-marketplace')); ?></p>');
        
        // Simulate connection (in real implementation, use Web3 libraries)
        setTimeout(function() {
            // Simulate success
            var walletAddress = '0x' + Math.random().toString(16).substr(2, 40);
            
            // In real implementation, verify with signature
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_verify_wallet',
                    wallet_address: walletAddress,
                    signature: 'demo_signature',
                    nonce: vortex_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        messageDiv.html('<p class="vortex-success">' + response.data + '</p>');
                        
                        // Redirect or reload page
                        var redirect = $('.vortex-wallet-connect-button').data('redirect');
                        setTimeout(function() {
                            if (redirect) {
                                window.location.href = redirect;
                            } else {
                                window.location.reload();
                            }
                        }, 1500);
                    } else {
                        messageDiv.html('<p class="vortex-error">' + response.data + '</p>');
                    }
                },
                error: function() {
                    messageDiv.html('<p class="vortex-error"><?php echo esc_js(__('Connection failed. Please try again.', 'vortex-ai-marketplace')); ?></p>');
                }
            });
        }, 2000);
    });
    
    // Disconnect wallet
    $('.vortex-wallet-disconnect-btn').on('click', function() {
        if (confirm('<?php echo esc_js(__('Are you sure you want to disconnect your wallet?', 'vortex-ai-marketplace')); ?>')) {
            // In real implementation, clear Web3 connection
            // For demo, just reload the page
            window.location.reload();
        }
    });
});
</script> 