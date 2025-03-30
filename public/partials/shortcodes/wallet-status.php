<?php
/**
 * Template for wallet status shortcode
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    ?>
    <div class="vortex-wallet-login-required">
        <p><?php _e('Please login to view your wallet status.', 'vortex-ai-marketplace'); ?></p>
        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="vortex-login-btn">
            <?php _e('Log In', 'vortex-ai-marketplace'); ?>
        </a>
    </div>
    <?php
    return;
}

// Get user wallet
$user_id = get_current_user_id();
$wallet_address = get_user_meta($user_id, 'vortex_wallet_address', true);
$tola_balance = floatval(get_user_meta($user_id, 'vortex_tola_balance', true));

// If no wallet, show error
if (empty($wallet_address)) {
    ?>
    <div class="vortex-wallet-status-error">
        <p><?php _e('You don\'t have a wallet yet.', 'vortex-ai-marketplace'); ?></p>
        <p><?php _e('Please contact an administrator to create a wallet for your account.', 'vortex-ai-marketplace'); ?></p>
    </div>
    <?php
    return;
}

// For demo purposes, create sample tokens data
$tokens = array(
    array(
        'symbol' => 'TOLA',
        'name' => 'TOLA Token',
        'balance' => $tola_balance,
        'value_usd' => $tola_balance * 0.20, // Assuming 1 TOLA = $0.20
    )
);

// Add additional sample tokens if needed for demo
if ($tola_balance > 0) {
    // Only show these tokens if user has some TOLA (for demo purposes)
    $tokens[] = array(
        'symbol' => 'CREA',
        'name' => 'VORTEX Creator Token',
        'balance' => 5.0,
        'value_usd' => 5.0 * 0.10, // Assuming 1 CREA = $0.10
    );
}
?>

<div class="vortex-wallet-status">
    <div class="vortex-wallet-status-header">
        <h3><?php _e('My Wallet', 'vortex-ai-marketplace'); ?></h3>
    </div>
    
    <?php if ($show_balance): ?>
        <div class="vortex-wallet-balance-section">
            <div class="vortex-wallet-address-display">
                <span class="vortex-wallet-address-label"><?php _e('Wallet Address:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-wallet-address-value">
                    <?php echo esc_html(substr($wallet_address, 0, 6) . '...' . substr($wallet_address, -4)); ?>
                    <button type="button" class="vortex-copy-btn" data-clipboard-text="<?php echo esc_attr($wallet_address); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                    </button>
                </span>
            </div>
            
            <?php if (!empty($tokens)): ?>
                <div class="vortex-wallet-tokens">
                    <h4><?php _e('My Tokens', 'vortex-ai-marketplace'); ?></h4>
                    <div class="vortex-wallet-tokens-list">
                        <?php foreach ($tokens as $token): ?>
                            <div class="vortex-wallet-token-item">
                                <div class="vortex-token-icon"><?php echo esc_html($token['symbol']); ?></div>
                                <div class="vortex-token-info">
                                    <div class="vortex-token-name"><?php echo esc_html($token['name']); ?></div>
                                    <div class="vortex-token-amount"><?php echo esc_html($token['balance']); ?></div>
                                </div>
                                <div class="vortex-token-value">
                                    $<?php echo esc_html(number_format($token['value_usd'], 2)); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="vortex-wallet-total">
                        <span class="vortex-wallet-total-label"><?php _e('Total Value:', 'vortex-ai-marketplace'); ?></span>
                        <span class="vortex-wallet-total-value">
                            $<?php 
                            $total = array_sum(array_column($tokens, 'value_usd'));
                            echo esc_html(number_format($total, 2)); 
                            ?>
                        </span>
                    </div>
                    
                    <div class="vortex-wallet-actions">
                        <a href="<?php echo esc_url(home_url('/purchase-tola/')); ?>" class="vortex-buy-tokens-btn">
                            <?php _e('Buy More TOLA', 'vortex-ai-marketplace'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($show_transactions): ?>
        <div class="vortex-wallet-transactions-section">
            <h4><?php _e('Recent Transactions', 'vortex-ai-marketplace'); ?></h4>
            <?php
            // For demo, create sample transactions
            $transactions = array(
                array(
                    'type' => 'purchase',
                    'amount' => '100',
                    'token' => 'TOLA',
                    'date' => date('Y-m-d H:i:s', strtotime('-2 days')),
                    'status' => 'completed',
                    'hash' => '0x' . bin2hex(random_bytes(16)),
                ),
                array(
                    'type' => 'nft_mint',
                    'token_id' => '1234',
                    'date' => date('Y-m-d H:i:s', strtotime('-5 days')),
                    'status' => 'completed',
                    'hash' => '0x' . bin2hex(random_bytes(16)),
                ),
                array(
                    'type' => 'artwork_purchase',
                    'artwork_id' => '567',
                    'amount' => '25',
                    'token' => 'TOLA',
                    'date' => date('Y-m-d H:i:s', strtotime('-10 days')),
                    'status' => 'completed',
                    'hash' => '0x' . bin2hex(random_bytes(16)),
                ),
            );
            
            if (empty($transactions)): ?>
                <p class="vortex-no-transactions"><?php _e('No transactions found.', 'vortex-ai-marketplace'); ?></p>
            <?php else: ?>
                <div class="vortex-transactions-list">
                    <?php foreach (array_slice($transactions, 0, $limit) as $tx): ?>
                        <div class="vortex-transaction vortex-transaction-<?php echo esc_attr($tx['type']); ?> vortex-status-<?php echo esc_attr($tx['status']); ?>">
                            <div class="vortex-transaction-type">
                                <?php 
                                switch ($tx['type']) {
                                    case 'purchase':
                                        echo '<span class="dashicons dashicons-money-alt"></span> ' . esc_html__('Token Purchase', 'vortex-ai-marketplace');
                                        break;
                                    case 'nft_mint':
                                        echo '<span class="dashicons dashicons-art"></span> ' . esc_html__('NFT Mint', 'vortex-ai-marketplace');
                                        break;
                                    case 'artwork_purchase':
                                        echo '<span class="dashicons dashicons-format-image"></span> ' . esc_html__('Artwork Purchase', 'vortex-ai-marketplace');
                                        break;
                                    default:
                                        echo '<span class="dashicons dashicons-randomize"></span> ' . esc_html__('Transaction', 'vortex-ai-marketplace');
                                }
                                ?>
                            </div>
                            
                            <div class="vortex-transaction-details">
                                <?php if (!empty($tx['amount'])): ?>
                                    <div class="vortex-transaction-amount">
                                        <?php echo esc_html($tx['amount']); ?> <?php echo esc_html($tx['token']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($tx['artwork_id'])): ?>
                                    <div class="vortex-transaction-artwork">
                                        <?php _e('Artwork ID:', 'vortex-ai-marketplace'); ?> <?php echo esc_html($tx['artwork_id']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($tx['token_id'])): ?>
                                    <div class="vortex-transaction-token">
                                        <?php _e('Token ID:', 'vortex-ai-marketplace'); ?> <?php echo esc_html($tx['token_id']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="vortex-transaction-date">
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($tx['date']))); ?>
                                </div>
                            </div>
                            
                            <div class="vortex-transaction-status">
                                <?php echo esc_html(ucfirst($tx['status'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($transactions) > $limit): ?>
                    <div class="vortex-transactions-more">
                        <a href="<?php echo esc_url(home_url('/transactions/')); ?>" class="vortex-view-all-btn">
                            <?php _e('View All Transactions', 'vortex-ai-marketplace'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($show_nfts): ?>
        <div class="vortex-wallet-nfts-section">
            <h4><?php _e('My NFTs', 'vortex-ai-marketplace'); ?></h4>
            <?php
            // For demo, create sample NFTs
            $nfts = array(
                array(
                    'id' => '1001',
                    'name' => 'Cosmic Dream #42',
                    'image' => plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/img/sample-nft-1.jpg',
                    'collection' => 'Vortex Originals',
                    'date_acquired' => date('Y-m-d H:i:s', strtotime('-7 days')),
                ),
                array(
                    'id' => '1002',
                    'name' => 'Digital Soul #17',
                    'image' => plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/img/sample-nft-2.jpg',
                    'collection' => 'AI Masters',
                    'date_acquired' => date('Y-m-d H:i:s', strtotime('-14 days')),
                ),
            );
            
            if (empty($nfts)): ?>
                <p class="vortex-no-nfts"><?php _e('No NFTs found in your wallet.', 'vortex-ai-marketplace'); ?></p>
            <?php else: ?>
                <div class="vortex-nfts-grid">
                    <?php foreach (array_slice($nfts, 0, $limit) as $nft): ?>
                        <div class="vortex-nft-item">
                            <div class="vortex-nft-image">
                                <img src="<?php echo esc_url($nft['image']); ?>" alt="<?php echo esc_attr($nft['name']); ?>">
                            </div>
                            <div class="vortex-nft-info">
                                <h5 class="vortex-nft-name"><?php echo esc_html($nft['name']); ?></h5>
                                <div class="vortex-nft-collection"><?php echo esc_html($nft['collection']); ?></div>
                                <div class="vortex-nft-acquired">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($nft['date_acquired']))); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($nfts) > $limit): ?>
                    <div class="vortex-nfts-more">
                        <a href="<?php echo esc_url(home_url('/my-nfts/')); ?>" class="vortex-view-all-btn">
                            <?php _e('View All NFTs', 'vortex-ai-marketplace'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Clipboard functionality
    $('.vortex-copy-btn').on('click', function() {
        var text = $(this).data('clipboard-text');
        var tempInput = $('<input>');
        
        $('body').append(tempInput);
        tempInput.val(text).select();
        document.execCommand('copy');
        tempInput.remove();
        
        alert('<?php echo esc_js(__('Copied to clipboard!', 'vortex-ai-marketplace')); ?>');
    });
});
</script> 