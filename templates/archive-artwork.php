<?php
/**
 * The Template for displaying artwork archives
 *
 * This template can be overridden by copying it to yourtheme/vortex/archive-artwork.php
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
 * Hook: vortex_before_artwork_loop
 * 
 * @hooked vortex_artwork_ordering - 10
 * @hooked vortex_artwork_filter - 20
 */
do_action('vortex_before_artwork_loop');

if (have_posts()) {
    /**
     * Hook: vortex_before_artwork_loop_items
     */
    do_action('vortex_before_artwork_loop_items');
    ?>

    <div class="vortex-artwork-grid" data-columns="<?php echo esc_attr(vortex_get_artwork_columns()); ?>">
        <?php
        while (have_posts()) {
            the_post();

            /**
             * Hook: vortex_artwork_loop
             * 
             * @hooked vortex_artwork_loop_template - 10
             */
            do_action('vortex_artwork_loop');
        }
        ?>
    </div>

    <?php
    /**
     * Hook: vortex_after_artwork_loop_items
     */
    do_action('vortex_after_artwork_loop_items');

    /**
     * Hook: vortex_after_artwork_loop
     * 
     * @hooked vortex_pagination - 10
     */
    do_action('vortex_after_artwork_loop');
} else {
    /**
     * Hook: vortex_no_artworks_found
     * 
     * @hooked vortex_no_artworks_found_template - 10
     */
    do_action('vortex_no_artworks_found');
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