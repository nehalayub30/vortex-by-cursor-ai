<?php
/**
 * The Template for displaying artist archives
 *
 * This template can be overridden by copying it to yourtheme/vortex/archive-artist.php
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

<header class="vortex-archive-header">
    <?php if (apply_filters('vortex_show_page_title', true)) : ?>
        <h1 class="vortex-archive-title"><?php vortex_page_title(); ?></h1>
    <?php endif; ?>

    <?php
    /**
     * Hook: vortex_archive_description
     * 
     * @hooked vortex_taxonomy_archive_description - 10
     */
    do_action('vortex_archive_description');
    ?>
</header>

<?php
/**
 * Hook: vortex_before_artist_loop
 * 
 * @hooked vortex_artist_filter - 10
 */
do_action('vortex_before_artist_loop');

if (have_posts()) {
    /**
     * Hook: vortex_before_artist_loop_items
     */
    do_action('vortex_before_artist_loop_items');
    ?>

    <div class="vortex-artist-grid" data-columns="<?php echo esc_attr(vortex_get_artist_columns()); ?>">
        <?php
        while (have_posts()) {
            the_post();

            /**
             * Hook: vortex_artist_loop
             * 
             * @hooked vortex_artist_loop_template - 10
             */
            do_action('vortex_artist_loop');
        }
        ?>
    </div>

    <?php
    /**
     * Hook: vortex_after_artist_loop_items
     */
    do_action('vortex_after_artist_loop_items');

    /**
     * Hook: vortex_after_artist_loop
     * 
     * @hooked vortex_pagination - 10
     */
    do_action('vortex_after_artist_loop');
} else {
    /**
     * Hook: vortex_no_artists_found
     * 
     * @hooked vortex_no_artists_found_template - 10
     */
    do_action('vortex_no_artists_found');
}
?>

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