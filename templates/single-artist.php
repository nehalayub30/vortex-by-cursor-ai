<?php
/**
 * The Template for displaying single artist profiles
 *
 * This template can be overridden by copying it to yourtheme/vortex/single-artist.php
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/templates
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header('vortex');

/**
 * Hook: vortex_before_main_content
 * 
 * @hooked vortex_output_content_wrapper - 10 (outputs opening divs for the content)
 */
do_action('vortex_before_main_content');
?>

<div id="vortex-artist-<?php the_ID(); ?>" <?php post_class('vortex-single-artist'); ?>>
    
    <?php
    /**
     * Hook: vortex_before_single_artist
     */
    do_action('vortex_before_single_artist');
    ?>

    <div class="vortex-artist-profile">
        <div class="vortex-artist-header">
            <?php
            /**
             * Hook: vortex_artist_profile_header
             * 
             * @hooked vortex_artist_profile_image - 10
             * @hooked vortex_artist_profile_name - 20
             */
            do_action('vortex_artist_profile_header');
            ?>
        </div>

        <div class="vortex-artist-details">
            <?php
            /**
             * Hook: vortex_artist_profile_details
             * 
             * @hooked vortex_artist_profile_bio - 10
             * @hooked vortex_artist_profile_stats - 20
             * @hooked vortex_artist_profile_social - 30
             */
            do_action('vortex_artist_profile_details');
            ?>
        </div>

        <?php if (vortex_artist_has_wallet()) : ?>
        <div class="vortex-artist-blockchain">
            <?php
            /**
             * Hook: vortex_artist_profile_blockchain
             * 
             * @hooked vortex_artist_profile_
        </div>
        <?php endif; ?>
    </div>

    <?php
    /**
     * Hook: vortex_after_single_artist
     */
    do_action('vortex_after_single_artist');
    ?>

</div>

<?php
/**
 * Hook: vortex_after_main_content
 * 
 * @hooked vortex_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action('vortex_after_main_content');

/**
 * Hook: vortex_sidebar
 * 
 * @hooked vortex_get_sidebar - 10
 */
do_action('vortex_sidebar');

get_footer('vortex');
?> 