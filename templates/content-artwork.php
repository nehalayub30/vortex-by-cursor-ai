<?php
/**
 * The template for displaying artwork content within loops
 *
 * This template can be overridden by copying it to yourtheme/vortex/content-artwork.php
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/templates
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $artwork;
?>

<div <?php vortex_artwork_class('vortex-artwork-item'); ?>>
    <?php
    /**
     * Hook: vortex_before_artwork_item
     */
    do_action('vortex_before_artwork_item');
    ?>

    <div class="vortex-artwork-item-image">
        <a href="<?php the_permalink(); ?>" class="vortex-artwork-item-link">
            <?php
            /**
             * Hook: vortex_artwork_item_image
             * 
             * @hooked vortex_template_loop_artwork_thumbnail - 10
             * @hooked vortex_template_loop_artwork_badges - 20
             */
            do_action('vortex_artwork_item_image');
            ?>
        </a>
    </div>

    <div class 