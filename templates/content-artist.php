<?php
/**
 * The template for displaying artist content within loops
 *
 * This template can be overridden by copying it to yourtheme/vortex/content-artist.php
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/templates
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $artist;
?>

<div <?php vortex_artist_class('vortex-artist-item'); ?>>
    <?php
    /**
     * Hook: vortex_before_artist_item
     */
    do_action('vortex_before_artist_item');
    ?>

    <div class="vortex-artist-item-image">
        <a href="<?php the_permalink(); ?>" class="vortex-artist-item-link">
            <?php
            /**
             * Hook: vortex_artist_item_image
             * 
             * @hooked vortex_template_loop_artist_thumbnail - 10
             * @hooked vortex_template_loop_artist_badges - 20
             */
            do_action('vortex_artist_item_image');
            ?>
        </a>
    </div>

    <div class="vortex-artist-item-details">
        <h3 class="vortex-artist-item-name">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            <?php if (vortex_artist_is_verified()) : ?>
                <span class="vortex-verified-badge" title="<?php esc_attr_e('Verified Artist', 'vortex-ai-marketplace'); ?>">✓</span>
            <?php endif; ?>
        </h3>

        <?php
        /**
         * Hook: vortex_artist_item_details
         * 
         * @hooked vortex_template_loop_artist_bio - 10
         * @hooked vortex_template_loop_artist_stats - 20
         */
        do_action('vortex_artist_item_details');
        ?>
    </div>

    <div class="vortex-artist-item-actions">
        <?php
        /**
         * Hook: vortex_artist_item_actions
         * 
         * @hooked vortex_template_loop_follow_button - 10
         */
        do_action('vortex_artist_item_actions');
        ?>
    </div>

    <?php
    /**
     * Hook: vortex_after_artist_item
     */
    do_action('vortex_after_artist_item');
    ?>
</div> 