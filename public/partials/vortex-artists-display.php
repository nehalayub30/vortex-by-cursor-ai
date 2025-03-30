<?php
/**
 * Template for displaying artists grid
 * 
 * @package VORTEX_AI_Marketplace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get AI recommendations if enabled
$ai_recommendations = array();
if (class_exists('Vortex_AI_Coordinator')) {
    $ai_coordinator = Vortex_AI_Coordinator::get_instance();
    $ai_recommendations = $ai_coordinator->get_artist_recommendations($artists);
}

// Security check
if (!wp_verify_nonce($atts['security_nonce'] ?? '', 'vortex_artists_display')) {
    wp_die('Security check failed');
}
?>

<div class="vortex-artists-container <?php echo esc_attr($atts['classes']); ?>">
    <?php if ($show_filters): ?>
        <div class="vortex-artists-filters">
            <form method="get" class="vortex-filter-form">
                <?php wp_nonce_field('vortex_artists_filter', 'filter_nonce'); ?>
                <select name="orderby" class="vortex-filter-select">
                    <option value="name" <?php selected($atts['orderby'], 'name'); ?>><?php esc_html_e('Name', 'vortex-ai-marketplace'); ?></option>
                    <option value="sales" <?php selected($atts['orderby'], 'sales'); ?>><?php esc_html_e('Sales', 'vortex-ai-marketplace'); ?></option>
                    <option value="rating" <?php selected($atts['orderby'], 'rating'); ?>><?php esc_html_e('Rating', 'vortex-ai-marketplace'); ?></option>
                </select>
                <button type="submit" class="vortex-filter-submit"><?php esc_html_e('Apply', 'vortex-ai-marketplace'); ?></button>
            </form>
        </div>
    <?php endif; ?>
    
    <div class="vortex-artists-grid">
        <?php foreach ($artists as $artist): 
            // Get AI insights for this artist
            $ai_insights = isset($ai_recommendations[$artist->artist_id]) ? $ai_recommendations[$artist->artist_id] : null;
        ?>
            <div class="vortex-artist-card <?php echo esc_attr($atts['card_style']); ?>">
                <div class="vortex-artist-avatar">
                    <?php echo get_avatar($artist->user_id, 150); ?>
                </div>
                <div class="vortex-artist-info">
                    <h3 class="vortex-artist-name">
                        <?php echo esc_html($artist->display_name); ?>
                        <?php if ($artist->is_verified): ?>
                            <span class="vortex-verified-badge" title="<?php esc_attr_e('Verified Artist', 'vortex-ai-marketplace'); ?>">✓</span>
                        <?php endif; ?>
                    </h3>
                    
                    <?php if ($show_stats): ?>
                        <div class="vortex-artist-stats">
                            <span class="vortex-stat">
                                <?php echo esc_html(sprintf(__('%d Artworks', 'vortex-ai-marketplace'), $artist->total_artworks)); ?>
                            </span>
                            <span class="vortex-stat">
                                <?php echo esc_html(sprintf(__('%d Sales', 'vortex-ai-marketplace'), $artist->total_sales)); ?>
                            </span>
                            <span class="vortex-stat">
                                <?php echo esc_html(sprintf(__('Rating: %.1f', 'vortex-ai-marketplace'), $artist->artist_rating)); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($ai_insights): ?>
                        <div class="vortex-ai-insights">
                            <span class="vortex-ai-badge" title="<?php echo esc_attr($ai_insights['recommendation']); ?>">
                                <?php esc_html_e('AI Recommended', 'vortex-ai-marketplace'); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($show_artworks): ?>
                    <div class="vortex-artist-artworks">
                        <?php
                        $artworks = get_artist_recent_artworks($artist->artist_id, 3);
                        if ($artworks): ?>
                            <div class="vortex-artworks-grid">
                                <?php foreach ($artworks as $artwork): ?>
                                    <div class="vortex-artwork-thumbnail">
                                        <?php echo get_artwork_thumbnail($artwork->ID); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($show_pagination): ?>
        <div class="vortex-pagination">
            <?php
            $total_pages = ceil($total_artists / $per_page);
            if ($total_pages > 1): ?>
                <div class="vortex-pagination-links">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo; Previous', 'vortex-ai-marketplace'),
                        'next_text' => __('Next &raquo;', 'vortex-ai-marketplace'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div> 