<?php
/**
 * Main marketplace template
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Extract shortcode attributes
$title = isset($atts['title']) ? esc_html($atts['title']) : esc_html__('VORTEX AI Marketplace', 'vortex-ai-marketplace');
$description = isset($atts['description']) ? wp_kses_post($atts['description']) : '';
$show_featured = isset($atts['featured']) && $atts['featured'] === 'yes';
$show_categories = isset($atts['categories']) && $atts['categories'] === 'yes';
$show_latest = isset($atts['latest']) && $atts['latest'] === 'yes';
$show_artists = isset($atts['artists']) && $atts['artists'] === 'yes';
$show_search = isset($atts['search']) && $atts['search'] === 'yes';
?>

<div class="vortex-marketplace">
    <header class="vortex-marketplace-header">
        <h1 class="vortex-marketplace-title"><?php echo $title; ?></h1>
        
        <?php if (!empty($description)) : ?>
            <div class="vortex-marketplace-description">
                <?php echo $description; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($show_search) : ?>
            <div class="vortex-marketplace-search">
                <?php 
                echo do_shortcode('[vortex_artwork_search title="" placeholder="' . esc_attr__('Search for artworks...', 'vortex-ai-marketplace') . '" show_categories="no" show_tags="no" show_artists="no" show_price_filter="no" show_ai_filter="no"]'); 
                ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if ($show_featured) : ?>
        <div class="vortex-marketplace-section vortex-featured-artworks">
            <h2 class="vortex-section-title"><?php esc_html_e('Featured Artworks', 'vortex-ai-marketplace'); ?></h2>
            <?php 
            echo do_shortcode('[vortex_artwork_grid title="" count="6" columns="3" featured="yes" show_filters="no" show_pagination="no"]');
            ?>
            <a href="<?php echo esc_url(get_post_type_archive_link('vortex_artwork')); ?>?featured=1" class="vortex-view-all"><?php esc_html_e('View All Featured', 'vortex-ai-marketplace'); ?> →</a>
        </div>
    <?php endif; ?>

    <?php if ($show_categories) : ?>
        <div class="vortex-marketplace-section vortex-artwork-categories">
            <h2 class="vortex-section-title"><?php esc_html_e('Browse Categories', 'vortex-ai-marketplace'); ?></h2>
            <div class="vortex-categories-grid">
                <?php
                $categories = get_terms(array(
                    'taxonomy' => 'vortex_artwork_category',
                    'hide_empty' => true,
                    'number' => 6,
                ));

                if (!empty($categories) && !is_wp_error($categories)) :
                    foreach ($categories as $category) :
                        $category_link = get_term_link($category);
                        $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                        $image_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
                        ?>
                        <div class="vortex-category-item">
                            <a href="<?php echo esc_url($category_link); ?>" class="vortex-category-link">
                                <?php if ($image_url) : ?>
                                    <div class="vortex-category-image">
                                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($category->name); ?>">
                                    </div>
                                <?php endif; ?>
                                <h3 class="vortex-category-name"><?php echo esc_html($category->name); ?></h3>
                                <span class="vortex-category-count"><?php echo esc_html($category->count); ?> <?php esc_html_e('artworks', 'vortex-ai-marketplace'); ?></span>
                            </a>
                        </div>
                        <?php
                    endforeach;
                endif;
                ?>
            </div>
            <a href="<?php echo esc_url(get_post_type_archive_link('vortex_artwork')); ?>" class="vortex-view-all"><?php esc_html_e('View All Categories', 'vortex-ai-marketplace'); ?> →</a>
        </div>
    <?php endif; ?>

    <?php if ($show_latest) : ?>
        <div class="vortex-marketplace-section vortex-latest-artworks">
            <h2 class="vortex-section-title"><?php esc_html_e('Latest Artworks', 'vortex-ai-marketplace'); ?></h2>
            <?php 
            echo do_shortcode('[vortex_artwork_grid title="" count="6" columns="3" orderby="date" order="DESC" show_filters="no" show_pagination="no"]');
            ?>
            <a href="<?php echo esc_url(get_post_type_archive_link('vortex_artwork')); ?>" class="vortex-view-all"><?php esc_html_e('View All Artworks', 'vortex-ai-marketplace'); ?> →</a>
        </div>
    <?php endif; ?>

    <?php if ($show_artists) : ?>
        <div class="vortex-marketplace-section vortex-featured-artists">
            <h2 class="vortex-section-title"><?php esc_html_e('Featured Artists', 'vortex-ai-marketplace'); ?></h2>
            <?php 
            echo do_shortcode('[vortex_artist_grid title="" count="4" columns="4" featured="yes" show_filters="no" show_pagination="no"]');
            ?>
            <a href="<?php echo esc_url(get_post_type_archive_link('vortex_artist')); ?>" class="vortex-view-all"><?php esc_html_e('View All Artists', 'vortex-ai-marketplace'); ?> →</a>
        </div>
    <?php endif; ?>

    <div class="vortex-marketplace-section vortex-generate-artwork">
        <div class="vortex-generate-cta">
            <h2 class="vortex-section-title"><?php esc_html_e('Create Your Own AI Artwork', 'vortex-ai-marketplace'); ?></h2>
            <p class="vortex-generate-description"><?php esc_html_e('Use our AI-powered generator to create unique artworks from your imagination.', 'vortex-ai-marketplace'); ?></p>
            <a href="<?php echo esc_url(get_permalink(get_option('vortex_generator_page_id'))); ?>" class="vortex-button vortex-generate-button"><?php esc_html_e('Generate Artwork', 'vortex-ai-marketplace'); ?></a>
        </div>
    </div>
</div> 