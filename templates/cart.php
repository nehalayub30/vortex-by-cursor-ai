<?php
/**
 * Cart Page Template
 *
 * This template can be overridden by copying it to yourtheme/vortex/cart.php
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/templates
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

do_action('vortex_before_cart');
?>

<div class="vortex-cart">
    <h1 class="vortex-cart-title"><?php echo esc_html($title); ?></h1>

    <?php do_action('vortex_before_cart_table'); ?>

    <form class="vortex-cart-form" action="<?php echo esc_url(vortex_get_cart_url()); ?>" method="post">
        <?php if (vortex_get_cart_contents_count() > 0) : ?>
            <table class="vortex-cart-table">
                <thead>
                    <tr>
                        <?php if ($show_thumbnails === 'yes') : ?> 