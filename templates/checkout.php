<?php
/**
 * Checkout Page Template
 *
 * This template can be overridden by copying it to yourtheme/vortex/checkout.php
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/templates
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

do_action('vortex_before_checkout');

// Check if cart is empty
if (vortex_get_cart_contents_count() === 0) {
    ?>
    <div class="vortex-checkout-empty">
        <p><?php esc_html_e('Your cart is empty. Please add some artworks before proceeding to checkout.', 'vortex-ai-marketplace'); ?></p>
        <p><a class="vortex-button" href="<?php echo esc_url(vortex_get_marketplace_url()); ?>"><?php esc_html_e('Browse Marketplace', 'vortex-ai-marketplace'); ?></a></p>
    </div>
    <?php
    return;
}

// Check if user is logged in or guest checkout allowed
if (!is_user_logged_in() && !vortex_allow_guest_checkout()) {
    if ($show_login_form === 'yes') {
        ?>
        <div class="vortex-checkout-login">
            <p><?php esc_html_e('Please log in to complete your purchase.', 'vortex-ai-marketplace'); ?></p>
            
            <?php vortex_login_form(array('redirect' => vortex_get_checkout_url())); ?>
            
            <p><?php esc_html_e('Don\'t have an account?', 'vortex-ai-marketplace'); ?> <a href="<?php echo esc_url(vortex_get_registration_url()); ?>"><?php esc_html_e('Register', 'vortex-ai-marketplace'); ?></a></p>
        </div>
        <?php
    } else {
        ?>
        <div class="vortex-checkout-login-required">
            <p><?php esc_html_e('Please log in to complete your purchase.', 'vortex-ai-marketplace'); ?></p>
            <p><a class="vortex-button" href="<?php echo esc_url(wp_login_url(vortex_get_checkout_url())); ?>"><?php esc_html_e('Log In', 'vortex-ai-marketplace'); ?></a></p>
        </div>
        <?php
    }
    return;
}
?>

<div class="vortex-checkout">
    <h1 class="vortex-checkout-title"><?php echo esc_html($title); ?></h1>

    <div class="vortex-checkout-content">
        <?php if ($show_order_summary === 'yes') : ?>
            <div class="vortex-checkout-order-summary">
                <h2><?php esc_html_e('Order Summary', 'vortex-ai-marketplace'); ?></h2>
                
                <div class="v 