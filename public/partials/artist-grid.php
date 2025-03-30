<?php
/**
 * Artist grid template
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

// Extract attributes
$title = isset($atts['title']) ? esc_html($atts['title']) : '';
$columns = isset($atts['columns']) ? intval($atts['columns']) : 4;
$show_filters = isset($atts['show_filters']) && $atts['show_filters'] === 'yes';
$show_pagination = isset($atts['show_pagination']) && $atts['show_pagination'] === 'yes';
$show_bio = isset($atts['show_bio']) && $atts['show_bio'] === 'yes';

// Query arguments
$args = array(
    'post_type' => 'vortex_artist',
    'posts_per_page' => isset($atts['count']) ? intval($atts['count']) : 8,
    'orderby' => isset($atts['orderby']) ? $atts['orderby'] : 'date',
    'order' => isset($atts['order']) ? $atts['order'] : 'DESC',
);

// Add taxonomy query if specified
if (!empty($atts['category'])) {
    $args['tax_query'][] = array(
        'taxonomy' => 'vortex_artist_category',
        'field' => 'slug',
        'terms' => explode(',', $atts['category']),
    );
}

// Filter by featured status
if (isset($atts['featured']) && $atts['featured'] === 'yes') {
    $args['meta_query'][] = array(
        'key' => '_vortex_artist_featured',
        'value' => '1',
        'compare' => '=',
    );
}

// Filter by verified status
if (isset($atts['verified']) && !empty($atts['verified'])) {
    $is_verified = $atts['verified'] === 'yes' ? '1' : '0';
    $args['meta_query'][] = array(
        'key' => '_vortex_artist_verified',
        'value' => $is_verified,
        'compare' => '=',
    );
}

// Get the current page for pagination
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
if ($show_pagination) {
    $args['paged'] = $paged;
}

// The Query
$artist_query = new WP_Query($args);
?>

<div class="vortex-artist-grid-container">
    <?php if (!empty($title)) : ?>
        <h2 class="vortex-grid-title"><?php echo $title; ?></h2>
    <?php endif; ?>

    <?php if ($show_filters) : ?>
        <div class="vortex-filters">
            <?php
            // Categories filter
            $categories = get_terms(array(
                'taxonomy' => 'vortex_artist_category',
                'hide_empty' => true,
            ));
            
            if (!empty($categories) && !is_wp_error($categories)) : 
            ?>
                <div class="vortex-filter-group vortex-category-filter">
                    <h3 class="vortex-filter-heading"><?php esc_html_e('Categories', 'vortex-ai-marketplace'); ?></h3>
                    <div class="vortex-filter-options">
                        <button class="vortex-filter-button active" data-filter="all"><?php esc_html_e('All', 'vortex-ai-marketplace'); ?></button>
                        <?php foreach ($categories as $category) : ?>
                            <button class="vortex-filter-button" data-filter="<?php echo esc_attr($category->slug); ?>">
                                <?php echo esc_html($category->name); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            // Verification filter
            ?>
            <div class="vortex-filter-group vortex-verification-filter">
                <h3 class="vortex-filter-heading"><?php esc_html_e('Verification', 'vortex-ai-marketplace'); ?></h3>
                <div class="vortex-filter-options">
                    <button class="vortex-filter-button active" data-filter="all"><?php esc_html_e('All', 'vortex-ai-marketplace'); ?></button>
                    <button class="vortex-filter-button" data-filter="verified"><?php esc_html_e('Verified Only', 'vortex-ai-marketplace'); ?></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($artist_query->have_posts()) : ?>
        <div class="vortex-grid vortex-artist-grid" data-columns="<?php echo esc_attr($columns); ?>">
            <?php while ($artist_query->have_posts()) : $artist_query->the_post(); 
                $artist_id = get_the_ID();
                $artist_url = get_permalink();
                $is_verified = get_post_meta($artist_id, '_vortex_artist_verified', true);
                $is_featured = get_post_meta($artist_id, '_vortex_artist_featured', true);
                $artwork_count = vortex_get_artist_artwork_count($artist_id);
                
                // Get categories for filtering
                $artist_categories = get_the_terms($artist_id, 'vortex_artist_category');
                $category_slugs = array();
                if ($artist_categories && !is_wp_error($artist_categories)) {
                    foreach ($artist_categories as $cat) {
                        $category_slugs[] = $cat->slug;
                    }
                }
                $category_attr = !empty($category_slugs) ? implode(' ', $category_slugs) : '';
            ?>
                <div class="vortex-grid-item vortex-artist-item" 
                     data-category="<?php echo esc_attr($category_attr); ?>"
                     data-verified="<?php echo esc_attr($is_verified ? 'verified' : 'unverified'); ?>">
                    
                    <div class="vortex-artist-image">
                        <a href="<?php echo esc_url($artist_url); ?>" class="vortex-artist-link">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium', array('class' => 'vortex-lazy-load')); ?>
                            <?php else : ?>
                                <div class="vortex-artist-placeholder"></div>
                            <?php endif; ?>
                            
                            <?php if ($is_featured) : ?>
                                <span class="vortex-featured-badge" title="<?php esc_attr_e('Featured Artist', 'vortex-ai-marketplace'); ?>">★</span>
                            <?php endif; ?>
                        </a>
                    </div>
                    
                    <div class="vortex-artist-details">
                        <h3 class="vortex-artist-name">
                            <a href="<?php echo esc_url($artist_url); ?>"><?php the_title(); ?></a>
                            <?php if ($is_verified) : ?>
                                <span class="vortex-verified-badge" title="<?php esc_attr_e('Verified Artist', 'vortex-ai-marketplace'); ?>">✓</span>
                            <?php endif; ?>
                        </h3>
                        
                        <?php if ($show_bio) : ?>
                            <div class="vortex-artist-bio">
                                <?php echo wp_trim_words(get_the_excerpt(), 15, '...'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="vortex-artist-stats">
                            <span class="vortex-artist-artwork-count">
                                <?php echo esc_html(sprintf(_n('%s Artwork', '%s Artworks', $artwork_count, 'vortex-ai-marketplace'), number_format_i18n($artwork_count))); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="vortex-artist-actions">
                        <a href="<?php echo esc_url($artist_url); ?>" class="vortex-button vortex-artist-view-button">
                            <?php esc_html_e('View Profile', 'vortex-ai-marketplace'); ?>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php if ($show_pagination && $artist_query->max_num_pages > 1) : ?>
            <div class="vortex-pagination">
                <?php
                $big = 999999999; // need an unlikely integer
                echo paginate_links(array(
                    'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                    'format' => '?paged=%#%',
                    'current' => max(1, $paged),
                    'total' => $artist_query->max_num_pages,
                    'prev_text' => '&laquo; ' . esc_html__('Previous', 'vortex-ai-marketplace'),
                    'next_text' => esc_html__('Next', 'vortex-ai-marketplace') . ' &raquo;',
                ));
                ?>
            </div>
        <?php endif; ?>
        
    <?php else : ?>
        <div class="vortex-no-results">
            <p><?php esc_html_e('No artists found.', 'vortex-ai-marketplace'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php wp_reset_postdata(); ?>
</div> 