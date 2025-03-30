<?php
/**
 * Artwork search template
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
$title = isset($atts['title']) ? esc_html($atts['title']) : esc_html__('Search Artworks', 'vortex-ai-marketplace');
$placeholder = isset($atts['placeholder']) ? esc_attr($atts['placeholder']) : esc_attr__('Search for artworks...', 'vortex-ai-marketplace');
$show_categories = isset($atts['show_categories']) && $atts['show_categories'] === 'yes';
$show_tags = isset($atts['show_tags']) && $atts['show_tags'] === 'yes';
$show_artists = isset($atts['show_artists']) && $atts['show_artists'] === 'yes';
$show_price_filter = isset($atts['show_price_filter']) && $atts['show_price_filter'] === 'yes';
$show_ai_filter = isset($atts['show_ai_filter']) && $atts['show_ai_filter'] === 'yes';
$results_count = isset($atts['results_count']) ? intval($atts['results_count']) : 12;
$results_columns = isset($atts['results_columns']) ? intval($atts['results_columns']) : 3;
?>

<div class="vortex-artwork-search">
    <?php if (!empty($title)) : ?>
        <h2 class="vortex-search-title"><?php echo $title; ?></h2>
    <?php endif; ?>
    
    <div class="vortex-search-container">
        <form class="vortex-search-form" action="<?php echo esc_url(home_url('/')); ?>" method="get">
            <div class="vortex-search-input-wrapper">
                <input type="text" class="vortex-search-input" name="s" placeholder="<?php echo $placeholder; ?>" value="<?php echo get_search_query(); ?>" />
                <input type="hidden" name="post_type" value="vortex_artwork" />
                <button type="submit" class="vortex-search-button">
                    <span class="vortex-search-icon"></span>
                    <span class="screen-reader-text"><?php esc_html_e('Search', 'vortex-ai-marketplace'); ?></span>
                </button>
                <span class="vortex-search-loading"></span>
            </div>
            
            <?php if ($show_categories || $show_tags || $show_artists || $show_price_filter || $show_ai_filter) : ?>
                <div class="vortex-advanced-search-toggle">
                    <button type="button" class="vortex-toggle-button">
                        <?php esc_html_e('Advanced Search', 'vortex-ai-marketplace'); ?>
                        <span class="vortex-toggle-icon"></span>
                    </button>
                </div>
                
                <div class="vortex-advanced-search-filters">
                    <?php if ($show_categories) : ?>
                        <div class="vortex-filter-section">
                            <h3 class="vortex-filter-heading"><?php esc_html_e('Categories', 'vortex-ai-marketplace'); ?></h3>
                            <div class="vortex-filter-options">
                                <?php
                                $categories = get_terms(array(
                                    'taxonomy' => 'vortex_artwork_category',
                                    'hide_empty' => true,
                                ));
                                
                                if (!empty($categories) && !is_wp_error($categories)) :
                                    foreach ($categories as $category) :
                                    ?>
                                        <label class="vortex-filter-checkbox">
                                            <input type="checkbox" name="artwork_categories[]" value="<?php echo esc_attr($category->slug); ?>" />
                                            <?php echo esc_html($category->name); ?>
                                        </label>
                                    <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($show_tags) : ?>
                        <div class="vortex-filter-section">
                            <h3 class="vortex-filter-heading"><?php esc_html_e('Tags', 'vortex-ai-marketplace'); ?></h3>
                            <div class="vortex-filter-options">
                                <?php
                                $tags = get_terms(array(
                                    'taxonomy' => 'vortex_artwork_tag',
                                    'hide_empty' => true,
                                    'number' => 20,
                                ));
                                
                                if (!empty($tags) && !is_wp_error($tags)) :
                                    foreach ($tags as $tag) :
                                    ?>
                                        <label class="vortex-filter-checkbox">
                                            <input type="checkbox" name="artwork_tags[]" value="<?php echo esc_attr($tag->slug); ?>" />
                                            <?php echo esc_html($tag->name); ?>
                                        </label>
                                    <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($show_artists) : ?>
                        <div class="vortex-filter-section">
                            <h3 class="vortex-filter-heading"><?php esc_html_e('Artists', 'vortex-ai-marketplace'); ?></h3>
                            <div class="vortex-filter-options">
                                <?php
                                $artists = get_posts(array(
                                    'post_type' => 'vortex_artist',
                                    'posts_per_page' => 20,
                                    'orderby' => 'title',
                                    'order' => 'ASC',
                                ));
                                
                                if (!empty($artists) && !is_wp_error($artists)) :
                                    foreach ($artists as $artist) :
                                    ?>
                                        <label class="vortex-filter-checkbox">
                                            <input type="checkbox" name="artwork_artists[]" value="<?php echo esc_attr($artist->post_title); ?>" />
                                            <?php echo esc_html($artist->post_title); ?>
                                        </label>
                                    <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($show_price_filter) : ?>
                        <div class="vortex-filter-section">
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
                    <?php endif; ?>
                    
                    <?php if ($show_ai_filter) : ?>
                        <div class="vortex-filter-section">
                            <h3 class="vortex-filter-heading"><?php esc_html_e('AI Generated', 'vortex-ai-marketplace'); ?></h3>
                            <div class="vortex-filter-options">
                                <button class="vortex-filter-button active" data-filter="all"><?php esc_html_e('All', 'vortex-ai-marketplace'); ?></button>
                                <button class="vortex-filter-button" data-filter="ai"><?php esc_html_e('AI Only', 'vortex-ai-marketplace'); ?></button>
                                <button class="vortex-filter-button" data-filter="non-ai"><?php esc_html_e('Non-AI Only', 'vortex-ai-marketplace'); ?></button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div> 