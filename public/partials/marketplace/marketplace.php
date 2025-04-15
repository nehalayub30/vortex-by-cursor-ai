<?php
/**
 * Template for displaying the main marketplace
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/marketplace
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Extract attributes to variables for convenience
$show_filters = filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN);
$show_search = filter_var($atts['show_search'], FILTER_VALIDATE_BOOLEAN);
$show_sorting = filter_var($atts['show_sorting'], FILTER_VALIDATE_BOOLEAN);
$columns = intval($atts['columns']);
$default_sort = sanitize_text_field($atts['default_sort']);
?>

<div class="vortex-marketplace">
    <?php if ($show_search || $show_filters || $show_sorting): ?>
        <div class="vortex-marketplace-controls">
            <?php if ($show_search): ?>
                <div class="vortex-marketplace-search">
                    <form role="search" method="get" class="vortex-search-form" action="<?php echo esc_url(home_url('/')); ?>">
                        <input type="search" class="vortex-search-field" placeholder="<?php echo esc_attr__('Search artworks...', 'vortex-ai-marketplace'); ?>" value="<?php echo get_search_query(); ?>" name="s" />
                        <input type="hidden" name="post_type" value="vortex_artwork" />
                        <button type="submit" class="vortex-search-submit"><?php echo esc_html__('Search', 'vortex-ai-marketplace'); ?></button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($show_filters): ?>
                <div class="vortex-marketplace-filters">
                    <div class="vortex-filter">
                        <label for="vortex-category-filter"><?php echo esc_html__('Category:', 'vortex-ai-marketplace'); ?></label>
                        <select id="vortex-category-filter" class="vortex-filter-select" data-filter="category">
                            <option value=""><?php echo esc_html__('All Categories', 'vortex-ai-marketplace'); ?></option>
                            <?php 
                            $categories = get_terms(array(
                                'taxonomy' => 'artwork_category',
                                'hide_empty' => true,
                            ));
                            
                            if (!is_wp_error($categories) && !empty($categories)) {
                                foreach ($categories as $category) {
                                    echo '<option value="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="vortex-filter">
                        <label for="vortex-style-filter"><?php echo esc_html__('Style:', 'vortex-ai-marketplace'); ?></label>
                        <select id="vortex-style-filter" class="vortex-filter-select" data-filter="style">
                            <option value=""><?php echo esc_html__('All Styles', 'vortex-ai-marketplace'); ?></option>
                            <?php 
                            $styles = get_terms(array(
                                'taxonomy' => 'artwork_style',
                                'hide_empty' => true,
                            ));
                            
                            if (!is_wp_error($styles) && !empty($styles)) {
                                foreach ($styles as $style) {
                                    echo '<option value="' . esc_attr($style->slug) . '">' . esc_html($style->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($show_sorting): ?>
                <div class="vortex-marketplace-sorting">
                    <label for="vortex-sort"><?php echo esc_html__('Sort by:', 'vortex-ai-marketplace'); ?></label>
                    <select id="vortex-sort" class="vortex-sort-select">
                        <option value="newest" <?php selected($default_sort, 'newest'); ?>><?php echo esc_html__('Newest', 'vortex-ai-marketplace'); ?></option>
                        <option value="popular" <?php selected($default_sort, 'popular'); ?>><?php echo esc_html__('Most Popular', 'vortex-ai-marketplace'); ?></option>
                        <option value="price_low" <?php selected($default_sort, 'price_low'); ?>><?php echo esc_html__('Price: Low to High', 'vortex-ai-marketplace'); ?></option>
                        <option value="price_high" <?php selected($default_sort, 'price_high'); ?>><?php echo esc_html__('Price: High to Low', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="vortex-marketplace-content">
        <div class="vortex-marketplace-loading" style="display: none;">
            <div class="vortex-loading-spinner"></div>
            <p><?php echo esc_html__('Loading artworks...', 'vortex-ai-marketplace'); ?></p>
        </div>

        <div class="vortex-artwork-grid columns-<?php echo esc_attr($columns); ?>">
            <?php
            // Get the artwork query based on parameters
            $args = array(
                'post_type' => 'vortex_artwork',
                'posts_per_page' => intval($atts['items_per_page']),
                'paged' => 1,
            );

            // Add category filter if provided
            if (!empty($atts['category'])) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'artwork_category',
                    'field' => 'slug',
                    'terms' => explode(',', sanitize_text_field($atts['category'])),
                );
            }

            // Add style filter if provided
            if (!empty($atts['style'])) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'artwork_style',
                    'field' => 'slug',
                    'terms' => explode(',', sanitize_text_field($atts['style'])),
                );
            }

            // Add artist filter if provided
            if (!empty($atts['artist'])) {
                $args['meta_query'][] = array(
                    'key' => '_vortex_artist_id',
                    'value' => intval($atts['artist']),
                );
            }

            // Add AI engine filter if provided
            if (!empty($atts['ai_engine'])) {
                $args['meta_query'][] = array(
                    'key' => '_vortex_ai_engine',
                    'value' => sanitize_text_field($atts['ai_engine']),
                );
            }

            // Set sorting
            switch ($default_sort) {
                case 'popular':
                    $args['meta_key'] = '_vortex_view_count';
                    $args['orderby'] = 'meta_value_num';
                    $args['order'] = 'DESC';
                    break;
                case 'price_low':
                    $args['meta_key'] = '_vortex_price';
                    $args['orderby'] = 'meta_value_num';
                    $args['order'] = 'ASC';
                    break;
                case 'price_high':
                    $args['meta_key'] = '_vortex_price';
                    $args['orderby'] = 'meta_value_num';
                    $args['order'] = 'DESC';
                    break;
                case 'newest':
                default:
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
                    break;
            }

            $artwork_query = new WP_Query($args);

            if ($artwork_query->have_posts()) {
                while ($artwork_query->have_posts()) {
                    $artwork_query->the_post();
                    $artwork_id = get_the_ID();
                    ?>
                    <div class="vortex-artwork-item">
                        <div class="vortex-artwork-inner">
                            <a href="<?php the_permalink(); ?>" class="vortex-artwork-link">
                                <div class="vortex-artwork-image">
                                    <?php 
                                    if (has_post_thumbnail()) {
                                        the_post_thumbnail('medium_large');
                                    } else {
                                        echo '<div class="vortex-no-thumbnail">' . esc_html__('No Image', 'vortex-ai-marketplace') . '</div>';
                                    }
                                    ?>
                                </div>
                                <div class="vortex-artwork-details">
                                    <h3 class="vortex-artwork-title"><?php the_title(); ?></h3>
                                    <?php 
                                    // Get artist information
                                    $artist_id = get_post_meta($artwork_id, '_vortex_artist_id', true);
                                    if ($artist_id) {
                                        $artist = get_user_by('ID', $artist_id);
                                        if ($artist) {
                                            echo '<div class="vortex-artwork-artist">' . esc_html($artist->display_name) . '</div>';
                                        }
                                    }
                                    
                                    // Get price
                                    $price = get_post_meta($artwork_id, '_vortex_price', true);
                                    if ($price) {
                                        echo '<div class="vortex-artwork-price">' . esc_html(number_format($price, 2)) . ' TOLA</div>';
                                    }
                                    ?>
                                </div>
                            </a>
                        </div>
                    </div>
                    <?php
                }
                wp_reset_postdata();
            } else {
                echo '<div class="vortex-no-artworks">' . esc_html__('No artworks found.', 'vortex-ai-marketplace') . '</div>';
            }
            ?>
        </div>

        <?php if ($artwork_query->max_num_pages > 1): ?>
            <div class="vortex-marketplace-pagination">
                <button class="vortex-load-more"><?php echo esc_html__('Load More', 'vortex-ai-marketplace'); ?></button>
                <div class="vortex-pagination-info">
                    <?php 
                    echo sprintf(
                        esc_html__('Showing %1$d of %2$d artworks', 'vortex-ai-marketplace'),
                        min($artwork_query->post_count, $artwork_query->found_posts),
                        $artwork_query->found_posts
                    );
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div> 