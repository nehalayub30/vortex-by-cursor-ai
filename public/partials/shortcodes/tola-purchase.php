<?php
/**
 * Template for TOLA purchase shortcode
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
    <div class="vortex-tola-purchase-login">
        <p><?php _e('Please login to purchase TOLA tokens.', 'vortex-ai-marketplace'); ?></p>
        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="vortex-login-btn">
            <?php _e('Log In', 'vortex-ai-marketplace'); ?>
        </a>
        <a href="<?php echo esc_url(wp_registration_url()); ?>" class="vortex-register-btn">
            <?php _e('Register', 'vortex-ai-marketplace'); ?>
        </a>
    </div>
    <?php
    return;
}

// Get user wallet
$user_id = get_current_user_id();
$wallet_address = get_user_meta($user_id, 'vortex_wallet_address', true);
$current_tola_balance = floatval(get_user_meta($user_id, 'vortex_tola_balance', true));

// If no wallet, show error
if (empty($wallet_address)) {
    ?>
    <div class="vortex-tola-purchase-error">
        <p><?php _e('You need to create a wallet before purchasing TOLA tokens.', 'vortex-ai-marketplace'); ?></p>
        <p><?php _e('Please contact an administrator to create a wallet for your account.', 'vortex-ai-marketplace'); ?></p>
    </div>
    <?php
    return;
}

// Define TOLA packages
$tola_packages = array(
    array(
        'amount' => 50,
        'price' => 10,
        'currency' => 'USD',
        'popular' => false
    ),
    array(
        'amount' => 100,
        'price' => 19,
        'currency' => 'USD',
        'popular' => true
    ),
    array(
        'amount' => 250,
        'price' => 45,
        'currency' => 'USD',
        'popular' => false
    ),
    array(
        'amount' => 500,
        'price' => 85,
        'currency' => 'USD',
        'popular' => false
    ),
    array(
        'amount' => 1000,
        'price' => 150,
        'currency' => 'USD',
        'popular' => false
    )
);

// Get conversion rate (token to USD)
$conversion_rate = 0.20; // 1 TOLA = $0.20 USD

// Check if redirected from access restriction
$access_restricted = isset($_GET['access']) && $_GET['access'] === 'restricted';
?>

<div class="vortex-tola-purchase">
    <?php if ($access_restricted): ?>
        <div class="vortex-access-restricted">
            <p><?php _e('Access to that content requires TOLA tokens. Please purchase tokens to continue.', 'vortex-ai-marketplace'); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="vortex-tola-purchase-header">
        <h2><?php _e('Purchase TOLA Tokens', 'vortex-ai-marketplace'); ?></h2>
        <p><?php _e('TOLA tokens are required to access and purchase AI-generated artwork on our marketplace.', 'vortex-ai-marketplace'); ?></p>
    </div>
    
    <div class="vortex-tola-wallet-info">
        <div class="vortex-tola-balance">
            <span class="vortex-tola-balance-label"><?php _e('Current Balance:', 'vortex-ai-marketplace'); ?></span>
            <span class="vortex-tola-balance-value"><?php echo esc_html($current_tola_balance); ?> TOLA</span>
            <span class="vortex-tola-balance-usd">
                (<?php echo esc_html('$' . number_format($current_tola_balance * $conversion_rate, 2)); ?> USD)
            </span>
        </div>
        <div class="vortex-tola-wallet">
            <span class="vortex-tola-wallet-label"><?php _e('Wallet Address:', 'vortex-ai-marketplace'); ?></span>
            <span class="vortex-tola-wallet-value">
                <?php echo esc_html(substr($wallet_address, 0, 6) . '...' . substr($wallet_address, -4)); ?>
                <button type="button" class="vortex-copy-btn" data-clipboard-text="<?php echo esc_attr($wallet_address); ?>">
                    <span class="dashicons dashicons-clipboard"></span>
                </button>
            </span>
        </div>
    </div>
    
    <?php if ($show_packages): ?>
    <div class="vortex-tola-packages">
        <h3><?php _e('Choose a Package', 'vortex-ai-marketplace'); ?></h3>
        <div class="vortex-tola-packages-grid">
            <?php foreach ($tola_packages as $package): ?>
                <div class="vortex-tola-package <?php echo $package['popular'] ? 'vortex-tola-package-popular' : ''; ?>"
                     data-amount="<?php echo esc_attr($package['amount']); ?>"
                     data-price="<?php echo esc_attr($package['price']); ?>">
                    
                    <?php if ($package['popular']): ?>
                        <div class="vortex-tola-package-popular-tag">
                            <?php _e('Most Popular', 'vortex-ai-marketplace'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="vortex-tola-package-amount">
                        <?php echo esc_html($package['amount']); ?> TOLA
                    </div>
                    
                    <div class="vortex-tola-package-price">
                        <?php echo esc_html($package['currency'] . ' ' . number_format($package['price'], 2)); ?>
                    </div>
                    
                    <div class="vortex-tola-package-rate">
                        <?php echo esc_html('$' . number_format($package['price'] / $package['amount'], 3) . ' per token'); ?>
                    </div>
                    
                    <button type="button" class="vortex-tola-package-select">
                        <?php _e('Select', 'vortex-ai-marketplace'); ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($show_custom): ?>
    <div class="vortex-tola-custom">
        <h3><?php _e('Custom Amount', 'vortex-ai-marketplace'); ?></h3>
        <div class="vortex-tola-custom-input">
            <label for="vortex-tola-custom-amount"><?php _e('Enter TOLA Amount:', 'vortex-ai-marketplace'); ?></label>
            <input type="number" id="vortex-tola-custom-amount" min="<?php echo esc_attr($min_amount); ?>" 
                   max="<?php echo esc_attr($max_amount); ?>" step="1" value="<?php echo esc_attr($min_amount); ?>">
            <div class="vortex-tola-custom-price">
                <?php _e('Price:', 'vortex-ai-marketplace'); ?> <span id="vortex-tola-custom-price-value">$<?php echo number_format($min_amount * $conversion_rate, 2); ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="vortex-tola-payment">
        <h3><?php _e('Payment Method', 'vortex-ai-marketplace'); ?></h3>
        <div class="vortex-tola-payment-methods">
            <div class="vortex-tola-payment-method">
                <input type="radio" name="vortex-payment-method" id="vortex-payment-credit-card" value="credit_card" checked>
                <label for="vortex-payment-credit-card">
                    <img src="<?php echo esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/img/credit-card.png'); ?>" alt="Credit Card">
                    <span><?php _e('Credit Card', 'vortex-ai-marketplace'); ?></span>
                </label>
            </div>
            
            <div class="vortex-tola-payment-method">
                <input type="radio" name="vortex-payment-method" id="vortex-payment-paypal" value="paypal">
                <label for="vortex-payment-paypal">
                    <img src="<?php echo esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/img/paypal.png'); ?>" alt="PayPal">
                    <span><?php _e('PayPal', 'vortex-ai-marketplace'); ?></span>
                </label>
            </div>
            
            <div class="vortex-tola-payment-method">
                <input type="radio" name="vortex-payment-method" id="vortex-payment-crypto" value="crypto">
                <label for="vortex-payment-crypto">
                    <img src="<?php echo esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/img/crypto.png'); ?>" alt="Cryptocurrency">
                    <span><?php _e('Cryptocurrency', 'vortex-ai-marketplace'); ?></span>
                </label>
            </div>
        </div>
        
        <div class="vortex-tola-summary">
            <h4><?php _e('Order Summary', 'vortex-ai-marketplace'); ?></h4>
            <div class="vortex-tola-summary-item">
                <span class="vortex-tola-summary-label"><?php _e('TOLA Amount:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-tola-summary-value" id="vortex-tola-summary-amount">0</span>
            </div>
            <div class="vortex-tola-summary-item">
                <span class="vortex-tola-summary-label"><?php _e('Price:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-tola-summary-value" id="vortex-tola-summary-price">$0.00</span>
            </div>
            <div class="vortex-tola-summary-item vortex-tola-summary-total">
                <span class="vortex-tola-summary-label"><?php _e('Total:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-tola-summary-value" id="vortex-tola-summary-total">$0.00</span>
            </div>
            
            <button type="button" id="vortex-tola-purchase-btn" class="vortex-tola-purchase-btn" disabled>
                <?php _e('Purchase TOLA', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
    </div>
    
    <div class="vortex-tola-ai-powered">
        <div class="vortex-tola-ai-info">
            <h4><?php _e('TOLA Token Insights', 'vortex-ai-marketplace'); ?></h4>
            <p><?php _e('Our AI systems analyze market trends to provide insights about TOLA token usage:', 'vortex-ai-marketplace'); ?></p>
            
            <div class="vortex-tola-insights">
                <?php
                // Get AI systems' status and insights
                $cloe_enabled = get_option('vortex_ai_settings')['cloe_enabled'] ?? false;
                $strategist_enabled = get_option('vortex_ai_settings')['strategist_enabled'] ?? false;
                
                if ($cloe_enabled) {
                    // Simulated CLOE market analysis
                    ?>
                    <div class="vortex-tola-insight">
                        <span class="vortex-tola-insight-icon dashicons dashicons-chart-area"></span>
                        <div class="vortex-tola-insight-content">
                            <h5><?php _e('CLOE Market Analysis', 'vortex-ai-marketplace'); ?></h5>
                            <p><?php _e('TOLA token demand increased 15% this month. Average artwork purchase requires 35 TOLA.', 'vortex-ai-marketplace'); ?></p>
                        </div>
                    </div>
                    <?php
                }
                
                if ($strategist_enabled) {
                    // Simulated Business Strategist recommendation
                    ?>
                    <div class="vortex-tola-insight">
                        <span class="vortex-tola-insight-icon dashicons dashicons-chart-bar"></span>
                        <div class="vortex-tola-insight-content">
                            <h5><?php _e('Business Strategist', 'vortex-ai-marketplace'); ?></h5>
                            <p><?php _e('The 100 TOLA package offers optimal value based on average marketplace spending patterns.', 'vortex-ai-marketplace'); ?></p>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var selectedAmount = 0;
    var conversionRate = <?php echo json_encode($conversion_rate); ?>;
    
    // Package selection
    $('.vortex-tola-package-select').on('click', function() {
        $('.vortex-tola-package').removeClass('selected');
        $(this).closest('.vortex-tola-package').addClass('selected');
        
        selectedAmount = parseInt($(this).closest('.vortex-tola-package').data('amount'));
        updateSummary();
    });
    
    // Custom amount change
    $('#vortex-tola-custom-amount').on('input', function() {
        var amount = parseInt($(this).val());
        
        // Clear package selection
        $('.vortex-tola-package').removeClass('selected');
        
        if (isNaN(amount) || amount < <?php echo esc_js($min_amount); ?>) {
            amount = <?php echo esc_js($min_amount); ?>;
            $(this).val(amount);
        } else if (amount > <?php echo esc_js($max_amount); ?>) {
            amount = <?php echo esc_js($max_amount); ?>;
            $(this).val(amount);
        }
        
        selectedAmount = amount;
        
        // Update custom price display
        $('#vortex-tola-custom-price-value').text('$' + (amount * conversionRate).toFixed(2));
        
        updateSummary();
    });
    
    // Update order summary
    function updateSummary() {
        var price = selectedAmount * conversionRate;
        var total = price;
        
        $('#vortex-tola-summary-amount').text(selectedAmount + ' TOLA');
        $('#vortex-tola-summary-price').text('$' + price.toFixed(2));
        $('#vortex-tola-summary-total').text('$' + total.toFixed(2));
        
        if (selectedAmount > 0) {
            $('#vortex-tola-purchase-btn').prop('disabled', false);
        } else {
            $('#vortex-tola-purchase-btn').prop('disabled', true);
        }
    }
    
    // Purchase button click
    $('#vortex-tola-purchase-btn').on('click', function() {
        if (selectedAmount <= 0) {
            alert('<?php echo esc_js(__('Please select a TOLA amount', 'vortex-ai-marketplace')); ?>');
            return;
        }
        
        var paymentMethod = $('input[name="vortex-payment-method"]:checked').val();
        
        // Disable button and show loading
        $(this).prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'vortex-ai-marketplace')); ?>');
        
        // AJAX call to purchase TOLA
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_purchase_tola',
                amount: selectedAmount,
                payment_method: paymentMethod,
                nonce: vortex_ajax.purchase_tola_nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message and redirect
                    alert(response.data.message);
                    window.location.reload();
                } else {
                    // Show error message
                    alert(response.data);
                    $('#vortex-tola-purchase-btn').prop('disabled', false).text('<?php echo esc_js(__('Purchase TOLA', 'vortex-ai-marketplace')); ?>');
                }
            },
            error: function() {
                // Show error message
                alert('<?php echo esc_js(__('Payment processing failed. Please try again.', 'vortex-ai-marketplace')); ?>');
                $('#vortex-tola-purchase-btn').prop('disabled', false).text('<?php echo esc_js(__('Purchase TOLA', 'vortex-ai-marketplace')); ?>');
            }
        });
    });
    
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