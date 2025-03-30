<?php
/**
 * The Template for displaying single artwork items
 *
 * This template can be overridden by copying it to yourtheme/vortex/single-artwork.php
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

<div id="vortex-artwork-<?php the_ID(); ?>" <?php post_class('vortex-single-artwork'); ?>>
    
    <?php
    /**
     * Hook: vortex_before_single_artwork
     */
    do_action('vortex_before_single_artwork');

    /**
     * Hook: vortex 