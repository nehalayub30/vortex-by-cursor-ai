<?php
/**
 * Artwork grid template
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
$columns = isset($atts['columns']) ? intval($atts['columns']) : 3;
$show_filters = isset($atts['show_filters']) && $atts['show_filters'] === 'yes';
$show_pagination = isset($atts['show_pagination']) && $atts['show_pagination'] === 'yes';
$show_price = isset($atts['show_price']) && $atts['show_price'] === 'yes';
$show_artist = isset($atts['show_artist']) && $atts['show_artist'] === 'yes';

// Query arguments
$args = array(
    'post_type' => 'vortex_artwork',
    'posts_per_page' => isset($atts['count']) ? intval($atts['count']) : 12,
    'orderby' => isset($atts['orderby']) ? $atts['orderby'] : 'date',
    'order' => isset($atts['order']) ? $atts['order'] : 'DESC',
);

// Add taxonomy query if specified
if (!empty($atts['category'])) {
    $args['tax_query'][] = array(
        'taxonomy' => 'vortex_artwork_category',
        'field' => 'slug',
        'terms' => explode(',', $atts['category']),
    );
}

if (!empty($atts['tag'])) {
    $args['tax_query'][] = array(
        'taxonomy' => 'vortex_artwork_tag',
        'field' => 'slug',
        'terms' => explode(',', $atts['tag']),
    );
}

// Filter by artist if specified
if (!empty($atts['artist'])) {
    $artist_id = is_numeric($atts['artist']) ? intval($atts['artist']) : 0;
    
    // If not numeric, try to find by slug
    if (!$artist_id) {
        $artist = get_page_by_path($atts['artist'], OBJECT, 'vortex_artist');
        if ($artist) {
            $artist_id = $artist->ID;
        }
    }
    
    if ($artist_id) {
        $args['meta_query'][] = array(
            'key' => '_vortex_artwork_artist',
            'value' => $artist_id,
            'compare' => '=',
        );
    }
}

// Filter by featured status
if (isset($atts['featured']) && $atts['featured'] === 'yes') {
    $args['meta_query'][] = array(
        'key' => '_vortex_artwork_featured',
        'value' => '1',
        'compare' => '=',
    );
}

// Filter by AI generated status
if (isset($atts['ai_generated']) && !empty($atts['ai_generated'])) {
    $is_ai = $atts['ai_generated'] === 'yes' ? '1' : '0';
    $args['meta_query'][] = array(
        'key' => '_vortex_artwork_ai_generated',
        'value' => $is_ai,
        'compare' => '=',
    );
}

// Get the current page for pagination
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
if ($show_pagination) {
    $args['paged'] = $paged;
}

// The Query
$artwork_query = new WP_Query($args);
?>

<div class="vortex-artwork-grid-container">
    <?php if (!empty($title)) : ?>
        <h2 class="vortex-grid-title"><?php echo $title; ?></h2>
    <?php endif; ?>

    <?php if ($show_filters) : ?>
        <div class="vortex-filters">
            <?php
            // Categories filter
            $categories = get_terms(array(
                'taxonomy' => 'vortex_artwork_category',
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
            // Price range filter
            ?>
            <div class="vortex-filter-group vortex-price-filter">
                <h3 class="vortex-filter-heading"><?php esc_html_e('Price Range', 'vortex-ai-marketplace'); ?></h3>
                <div class="vortex-range-filter" data-filter-type="price">
                    <div class="vortex-range-inputs">
                        <input type="number" class="vortex-range-min" placeholder="<?php esc_attr_e('Min', 'vortex-ai-marketplace'); ?>" min="0">
                        <span class="vortex-range-separator">-</span>
                        <input type="number" class="vortex-range-max" placeholder="<?php esc_attr_e('Max', 'vortex-ai-marketplace'); ?>" min="0">
                    </div>
                    <button class="vortex-range-apply vortex-button"><?php esc_html_e('Apply', 'vortex-ai-marketplace'); ?></button>
                </div>
            </div>

            <?php
            // AI filter
            ?>
            <div class="vortex-filter-group vortex-ai-filter">
                <h3 class="vortex-filter-heading"><?php esc_html_e('AI Generated', 'vortex-ai-marketplace'); ?></h3>
                <div class="vortex-filter-options">
                    <button class="vortex-filter-button active" data-filter="all"><?php esc_html_e('All', 'vortex-ai-marketplace'); ?></button>
                    <button class="vortex-filter-button" data-filter="ai"><?php esc_html_e('AI Only', 'vortex-ai-marketplace'); ?></button>
                    <button class="vortex-filter-button" data-filter="non-ai"><?php esc_html_e('Non-AI Only', 'vortex-ai-marketplace'); ?></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($artwork_query->have_posts()) : ?>
        <div class="vortex-grid vortex-artwork-grid" data-columns="<?php echo esc_attr($columns); ?>">
            <?php while ($artwork_query->have_posts()) : $artwork_query->the_post(); 
                $artwork_id = get_the_ID();
                $artwork_url = get_permalink();
                $artist_id = get_post_meta($artwork_id, '_vortex_artwork_artist', true);
                $is_ai_generated = get_post_meta($artwork_id, '_vortex_artwork_ai_generated', true);
                $is_featured = get_post_meta($artwork_id, '_vortex_artwork_featured', true);
                $price = get_post_meta($artwork_id, '_vortex_artwork_price', true);
                
                // Get categories for filtering
                $artwork_categories = get_the_terms($artwork_id, 'vortex_artwork_category');
                $category_slugs = array();
                if ($artwork_categories && !is_wp_error($artwork_categories)) {
                    foreach ($artwork_categories as $cat) {
                        $category_slugs[] = $cat->slug;
                    }
                }
                $category_attr = !empty($category_slugs) ? implode(' ', $category_slugs) : '';
            ?>
                <div class="vortex-grid-item vortex-artwork-item" 
                     data-price="<?php echo esc_attr($price); ?>" 
                     data-category="<?php echo esc_attr($category_attr); ?>"
                     data-ai-generated="<?php echo esc_attr($is_ai_generated ? 'ai' : 'non-ai'); ?>">
                    
                    <div class="vortex-artwork-image">
                        <a href="<?php echo esc_url($artwork_url); ?>" class="vortex-artwork-link">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium', array('class' => 'vortex-lazy-load')); ?>
                            <?php else : ?>
                                <div class="vortex-artwork-placeholder"></div>
                            <?php endif; ?>
                            
                            <?php if ($is_featured) : ?>
                                <span class="vortex-featured-badge" title="<?php esc_attr_e('Featured Artwork', 'vortex-ai-marketplace'); ?>">★</span>
                            <?php endif; ?>
                            
                            <?php if ($is_ai_generated) : ?>
                                <span class="vortex-ai-badge" title="<?php esc_attr_e('AI Generated', 'vortex-ai-marketplace'); ?>">AI</span>
                            <?php endif; ?>
                        </a>
                    </div>
                    
                    <div class="vortex-artwork-details">
                        <h3 class="vortex-artwork-title">
                            <a href="<?php echo esc_url($artwork_url); ?>"><?php the_title(); ?></a>
                        </h3>
                        
                        <?php if ($show_artist && $artist_id) : ?>
                            <div class="vortex-artwork-artist">
                                <?php 
                                // Include artist name template
                                include plugin_dir_path(__FILE__) . 'artist-name.php';
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($show_price && $price) : ?>
                            <div class="vortex-artwork-price">
                                <?php 
                                // Include price template
                                include plugin_dir_path(__FILE__) . 'artwork-price.php';
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="vortex-artwork-actions">
                        <?php 
                        // Include add to cart button
                        include plugin_dir_path(__FILE__) . 'add-to-cart-button.php';
                        ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php if ($show_pagination && $artwork_query->max_num_pages > 1) : ?>
            <div class="vortex-pagination">
                <?php
                $big = 999999999; // need an unlikely integer
                echo paginate_links(array(
                    'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                    'format' => '?paged=%#%',
                    'current' => max(1, $paged),
                    'total' => $artwork_query->max_num_pages,
                    'prev_text' => '&laquo; ' . esc_html__('Previous', 'vortex-ai-marketplace'),
                    'next_text' => esc_html__('Next', 'vortex-ai-marketplace') . ' &raquo;',
                ));
                ?>
            </div>
        <?php endif; ?>
        
    <?php else : ?>
        <div class="vortex-no-results">
            <p><?php esc_html_e('No artworks found.', 'vortex-ai-marketplace'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php wp_reset_postdata(); ?>
</div> 