<?php
/**
 * Payment Settings Template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials/settings
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Add nonce verification and settings save handler
if (isset($_POST['vortex_payments_save_settings']) && check_admin_referer('vortex_payments_settings_nonce')) {
    // Sanitize and save settings
    $payment_settings = array(
        'currency' => sanitize_text_field($_POST['vortex_payment_currency'] ?? 'USD'),
        'tola_enabled' => isset($_POST['vortex_payment_tola_enabled']),
        'stripe_enabled' => isset($_POST['vortex_payment_stripe_enabled']),
        'paypal_enabled' => isset($_POST['vortex_payment_paypal_enabled']),
        'crypto_enabled' => isset($_POST['vortex_payment_crypto_enabled']),
        'stripe_public_key' => sanitize_text_field($_POST['vortex_payment_stripe_public_key'] ?? ''),
        'stripe_secret_key' => sanitize_text_field($_POST['vortex_payment_stripe_secret_key'] ?? ''),
        'paypal_client_id' => sanitize_text_field($_POST['vortex_payment_paypal_client_id'] ?? ''),
        'paypal_secret' => sanitize_text_field($_POST['vortex_payment_paypal_secret'] ?? ''),
        'tola_contract_address' => sanitize_text_field($_POST['vortex_payment_tola_contract'] ?? ''),
        'transaction_fee' => floatval($_POST['vortex_payment_transaction_fee'] ?? 2.9),
        'fee_structure' => sanitize_text_field($_POST['vortex_payment_fee_structure'] ?? 'percentage'),
        'minimum_withdrawal' => floatval($_POST['vortex_payment_min_withdrawal'] ?? 50),
        'ai_pricing_enabled' => isset($_POST['vortex_payment_ai_pricing_enabled']),
        'huraii_market_analysis' => isset($_POST['vortex_payment_huraii_analysis']),
        'cloe_pricing_optimization' => isset($_POST['vortex_payment_cloe_optimization']),
        'payout_schedule' => sanitize_text_field($_POST['vortex_payment_payout_schedule'] ?? 'monthly')
    );
    
    update_option('vortex_payment_settings', $payment_settings);
    add_settings_error('vortex_messages', 'vortex_payment_message', 
        __('Payment Settings Saved Successfully', 'vortex-ai-marketplace'), 'updated');
}

// Get current settings
$payment_settings = get_option('vortex_payment_settings', array(
    'currency' => 'USD',
    'tola_enabled' => true,
    'stripe_enabled' => true,
    'paypal_enabled' => true,
    'crypto_enabled' => false,
    'stripe_public_key' => '',
    'stripe_secret_key' => '',
    'paypal_client_id' => '',
    'paypal_secret' => '',
    'tola_contract_address' => '',
    'transaction_fee' => 2.9,
    'fee_structure' => 'percentage',
    'minimum_withdrawal' => 50,
    'ai_pricing_enabled' => true,
    'huraii_market_analysis' => true,
    'cloe_pricing_optimization' => true,
    'payout_schedule' => 'monthly'
));

// Currency options
$currencies = array(
    'USD' => array('name' => __('US Dollar', 'vortex-ai-marketplace'), 'symbol' => '$'),
    'EUR' => array('name' => __('Euro', 'vortex-ai-marketplace'), 'symbol' => '€'),
    'GBP' => array('name' => __('British Pound', 'vortex-ai-marketplace'), 'symbol' => '£'),
    'JPY' => array('name' => __('Japanese Yen', 'vortex-ai-marketplace'), 'symbol' => '¥'),
    'CAD' => array('name' => __('Canadian Dollar', 'vortex-ai-marketplace'), 'symbol' => '$'),
    'AUD' => array('name' => __('Australian Dollar', 'vortex-ai-marketplace'), 'symbol' => '$'),
    'TOLA' => array('name' => __('TOLA Token', 'vortex-ai-marketplace'), 'symbol' => 'TOLA')
);

?>

<div class="wrap">
    <h2><?php echo esc_html__('Payment Settings', 'vortex-ai-marketplace'); ?></h2>
    <?php settings_errors('vortex_messages'); ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('vortex_payments_settings_nonce'); ?>

        <div class="vortex-section">
            <h3><?php esc_html_e('Currency Settings', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="vortex_payment_currency">
                            <?php esc_html_e('Default Currency', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="vortex_payment_currency" name="vortex_payment_currency">
                            <?php foreach ($currencies as $code => $details) : ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php selected($payment_settings['currency'], $code); ?>>
                                    <?php echo esc_html($code . ' - ' . $details['name'] . ' (' . $details['symbol'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select the default currency for your marketplace.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="vortex-section">
            <h3><?php esc_html_e('Payment Gateways', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Enabled Gateways', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_payment_tola_enabled" 
                                   value="1" 
                                   <?php checked($payment_settings['tola_enabled']); ?>>
                            <?php esc_html_e('TOLA Token Payments', 'vortex-ai-marketplace'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_payment_stripe_enabled" 
                                   value="1" 
                                   <?php checked($payment_settings['stripe_enabled']); ?>>
                            <?php esc_html_e('Stripe', 'vortex-ai-marketplace'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_payment_paypal_enabled" 
                                   value="1" 
                                   <?php checked($payment_settings['paypal_enabled']); ?>>
                            <?php esc_html_e('PayPal', 'vortex-ai-marketplace'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_payment_crypto_enabled" 
                                   value="1" 
                                   <?php checked($payment_settings['crypto_enabled']); ?>>
                            <?php esc_html_e('Cryptocurrency (BTC, ETH)', 'vortex-ai-marketplace'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="vortex-section gateway-settings" id="stripe-settings" style="<?php echo $payment_settings['stripe_enabled'] ? '' : 'display: none;'; ?>">
            <h3><?php esc_html_e('Stripe Settings', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="vortex_payment_stripe_public_key">
                            <?php esc_html_e('Publishable Key', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               id="vortex_payment_stripe_public_key" 
                               name="vortex_payment_stripe_public_key" 
                               value="<?php echo esc_attr($payment_settings['stripe_public_key']); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="vortex_payment_stripe_secret_key">
                            <?php esc_html_e('Secret Key', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                               id="vortex_payment_stripe_secret_key" 
                               name="vortex_payment_stripe_secret_key" 
                               value="<?php echo esc_attr($payment_settings['stripe_secret_key']); ?>"
                               class="regular-text">
                        <button type="button" class="button toggle-password" data-target="vortex_payment_stripe_secret_key">
                            <?php esc_html_e('Show/Hide', 'vortex-ai-marketplace'); ?>
                        </button>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="vortex-section gateway-settings" id="paypal-settings" style="<?php echo $payment_settings['paypal_enabled'] ? '' : 'display: none;'; ?>">
            <h3><?php esc_html_e('PayPal Settings', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="vortex_payment_paypal_client_id">
                            <?php esc_html_e('Client ID', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               id="vortex_payment_paypal_client_id" 
                               name="vortex_payment_paypal_client_id" 
                               value="<?php echo esc_attr($payment_settings['paypal_client_id']); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="vortex_payment_paypal_secret">
                            <?php esc_html_e('Secret', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                               id="vortex_payment_paypal_secret" 
                               name="vortex_payment_paypal_secret" 
                               value="<?php echo esc_attr($payment_settings['paypal_secret']); ?>"
                               class="regular-text">
                        <button type="button" class="button toggle-password" data-target="vortex_payment_paypal_secret">
                            <?php esc_html_e('Show/Hide', 'vortex-ai-marketplace'); ?>
                        </button>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="vortex-section gateway-settings" id="tola-settings" style="<?php echo $payment_settings['tola_enabled'] ? '' : 'display: none;'; ?>">
            <h3><?php esc_html_e('TOLA Token Settings', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="vortex_payment_tola_contract">
                            <?php esc_html_e('Contract Address', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               id="vortex_payment_tola_contract" 
                               name="vortex_payment_tola_contract" 
                               value="<?php echo esc_attr($payment_settings['tola_contract_address']); ?>"
                               class="regular-text code">
                        <p class="description">
                            <?php esc_html_e('The Ethereum contract address for the TOLA token.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="vortex-section">
            <h3><?php esc_html_e('Transaction Settings', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="vortex_payment_fee_structure">
                            <?php esc_html_e('Fee Structure', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="vortex_payment_fee_structure" name="vortex_payment_fee_structure">
                            <option value="percentage" <?php selected($payment_settings['fee_structure'], 'percentage'); ?>>
                                <?php esc_html_e('Percentage Based', 'vortex-ai-marketplace'); ?>
                            </option>
                            <option value="fixed" <?php selected($payment_settings['fee_structure'], 'fixed'); ?>>
                                <?php esc_html_e('Fixed Fee', 'vortex-ai-marketplace'); ?>
                            </option>
                            <option value="hybrid" <?php selected($payment_settings['fee_structure'], 'hybrid'); ?>>
                                <?php esc_html_e('Hybrid (Fixed + Percentage)', 'vortex-ai-marketplace'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="vortex_payment_transaction_fee">
                            <?php esc_html_e('Transaction Fee', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="vortex_payment_transaction_fee" 
                               name="vortex_payment_transaction_fee" 
                               value="<?php echo esc_attr($payment_settings['transaction_fee']); ?>"
                               min="0" 
                               max="100" 
                               step="0.1">
                        <span id="fee-type-label">
                            <?php echo ($payment_settings['fee_structure'] == 'percentage') ? '%' : $currencies[$payment_settings['currency']]['symbol']; ?>
                        </span>
                        <p class="description">
                            <?php esc_html_e('Fee applied to each transaction (in addition to artist commission).', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="vortex_payment_min_withdrawal">
                            <?php esc_html_e('Minimum Withdrawal', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="vortex_payment_min_withdrawal" 
                               name="vortex_payment_min_withdrawal" 
                               value="<?php echo esc_attr($payment_settings['minimum_withdrawal']); ?>"
                               min="0" 
                               step="0.01">
                        <span><?php echo esc_html($currencies[$payment_settings['currency']]['symbol']); ?></span>
                        <p class="description">
                            <?php esc_html_e('Minimum balance required for artists to withdraw funds.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="vortex_payment_payout_schedule">
                            <?php esc_html_e('Payout Schedule', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="vortex_payment_payout_schedule" name="vortex_payment_payout_schedule">
                            <option value="instant" <?php selected($payment_settings['payout_schedule'], 'instant'); ?>>
                                <?php esc_html_e('Instant', 'vortex-ai-marketplace'); ?>
                            </option>
                            <option value="daily" <?php selected($payment_settings['payout_schedule'], 'daily'); ?>>
                                <?php esc_html_e('Daily', 'vortex-ai-marketplace'); ?>
                            </option>
                            <option value="weekly" <?php selected($payment_settings['payout_schedule'], 'weekly'); ?>>
                                <?php esc_html_e('Weekly', 'vortex-ai-marketplace'); ?>
                            </option>
                            <option value="biweekly" <?php selected($payment_settings['payout_schedule'], 'biweekly'); ?>>
                                <?php esc_html_e('Biweekly', 'vortex-ai-marketplace'); ?>
                            </option>
                            <option value="monthly" <?php selected($payment_settings['payout_schedule'], 'monthly'); ?>>
                                <?php esc_html_e('Monthly', 'vortex-ai-marketplace'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('How often payouts are processed for artists.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="vortex-section">
            <h3><?php esc_html_e('AI-Powered Pricing', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="vortex_payment_ai_pricing_enabled">
                            <?php esc_html_e('Enable AI Pricing', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="vortex_payment_ai_pricing_enabled" 
                                   name="vortex_payment_ai_pricing_enabled" 
                                   value="1" 
                                   <?php checked($payment_settings['ai_pricing_enabled']); ?>>
                            <?php esc_html_e('Use AI to assist with artwork pricing', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('AI will suggest optimal pricing based on market analysis.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="ai-pricing-options" style="<?php echo $payment_settings['ai_pricing_enabled'] ? '' : 'display: none;'; ?>">
                    <th scope="row"><?php esc_html_e('AI Pricing Features', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_payment_huraii_analysis" 
                                   value="1" 
                                   <?php checked($payment_settings['huraii_market_analysis']); ?>>
                            <?php esc_html_e('HURAII Market Analysis', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Uses HURAII to analyze market trends and suggest competitive pricing.', 'vortex-ai-marketplace'); ?>
                        </p>
                        <br>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_payment_cloe_optimization" 
                                   value="1" 
                                   <?php checked($payment_settings['cloe_pricing_optimization']); ?>>
                            <?php esc_html_e('CLOE Pricing Optimization', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Uses CLOE to optimize pricing strategies for maximum artist revenue.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="vortex-shortcode-reference">
            <h3><?php esc_html_e('Payment Shortcodes Reference', 'vortex-ai-marketplace'); ?></h3>
            <table class="vortex-shortcode-list">
                <tr>
                    <th><?php esc_html_e('Shortcode', 'vortex-ai-marketplace'); ?></th>
                    <th><?php esc_html_e('Description', 'vortex-ai-marketplace'); ?></th>
                    <th><?php esc_html_e('Parameters', 'vortex-ai-marketplace'); ?></th>
                    <th><?php esc_html_e('Implementation File', 'vortex-ai-marketplace'); ?></th>
                </tr>
                <tr>
                    <td><code>[vortex_payment_button]</code></td>
                    <td><?php esc_html_e('Displays a payment button for an artwork', 'vortex-ai-marketplace'); ?></td>
                    <td><code>id</code>, <code>type</code>, <code>text</code></td>
                    <td><code>public/shortcodes/payment-button-shortcode.php</code></td>
                </tr>
                <tr>
                    <td><code>[vortex_transaction_history]</code></td>
                    <td><?php esc_html_e('Shows transaction history for current user', 'vortex-ai-marketplace'); ?></td>
                    <td><code>limit</code>, <code>type</code></td>
                    <td><code>public/shortcodes/transaction-history-shortcode.php</code></td>
                </tr>
                <tr>
                    <td><code>[vortex_artist_earnings]</code></td>
                    <td><?php esc_html_e('Displays earnings summary for an artist', 'vortex-ai-marketplace'); ?></td>
                    <td><code>id</code>, <code>period</code></td>
                    <td><code>public/shortcodes/artist-earnings-shortcode.php</code></td>
                </tr>
                <tr>
                    <td><code>[vortex_price_estimator]</code></td>
                    <td><?php esc_html_e('AI-powered price estimation tool', 'vortex-ai-marketplace'); ?></td>
                    <td><code>style</code>, <code>size</code>, <code>complexity</code></td>
                    <td><code>public/shortcodes/price-estimator-shortcode.php</code></td>
                </tr>
                <tr>
                    <td><code>[vortex_thorius_concierge]</code></td>
                    <td><?php esc_html_e('Displays Thorius AI Concierge chat interface', 'vortex-ai-marketplace'); ?></td>
                    <td><code>theme</code>, <code>position</code>, <code>welcome_message</code></td>
                    <td><code>public/shortcodes/thorius-concierge-shortcode.php</code></td>
                </tr>
            </table>
            <p class="description">
                <?php esc_html_e('Note: AI-powered features (HURAII Market Analysis and CLOE Pricing Optimization) are integrated with the price estimator shortcode when enabled in the settings above.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>

        <div class="vortex-submit-section">
            <input type="submit" 
                   name="vortex_payments_save_settings" 
                   class="button button-primary" 
                   value="<?php esc_attr_e('Save Payment Settings', 'vortex-ai-marketplace'); ?>">
        </div>
    </form>
</div>

<style>
.vortex-section {
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.vortex-section h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.vortex-shortcode-list {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.vortex-shortcode-list th,
.vortex-shortcode-list td {
    padding: 8px;
    text-align: left;
    border: 1px solid #ddd;
}

.vortex-shortcode-list th {
    background-color: #f8f9fa;
}

.vortex-submit-section {
    margin-top: 20px;
    padding: 20px 0;
    border-top: 1px solid #ddd;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Toggle password visibility
    $('.toggle-password').on('click', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        var field = $('#' + target);
        var type = field.attr('type') === 'password' ? 'text' : 'password';
        field.attr('type', type);
    });
    
    // Toggle payment gateway settings visibility
    $('input[name="vortex_payment_stripe_enabled"]').on('change', function() {
        $('#stripe-settings').toggle($(this).is(':checked'));
    });
    
    $('input[name="vortex_payment_paypal_enabled"]').on('change', function() {
        $('#paypal-settings').toggle($(this).is(':checked'));
    });
    
    $('input[name="vortex_payment_tola_enabled"]').on('change', function() {
        $('#tola-settings').toggle($(this).is(':checked'));
    });
    
    // Toggle AI pricing options
    $('input[name="vortex_payment_ai_pricing_enabled"]').on('change', function() {
        $('.ai-pricing-options').toggle($(this).is(':checked'));
    });
    
    // Update fee type label based on fee structure
    $('#vortex_payment_fee_structure').on('change', function() {
        var feeType = $(this).val();
        var currencySymbol = '<?php echo esc_js($currencies[$payment_settings['currency']]['symbol']); ?>';
        
        if (feeType === 'percentage') {
            $('#fee-type-label').text('%');
        } else {
            $('#fee-type-label').text(currencySymbol);
        }
    });
    
    // Form change tracking
    var formChanged = false;
    
    $('form input, form select, form textarea').on('change', function() {
        formChanged = true;
    });
    
    $('form').on('submit', function() {
        window.onbeforeunload = null;
        return true;
    });
    
    window.onbeforeunload = function() {
        if (formChanged) {
            return '<?php echo esc_js(__('You have unsaved changes. Are you sure you want to leave?', 'vortex-ai-marketplace')); ?>';
        }
    };
});
</script> 