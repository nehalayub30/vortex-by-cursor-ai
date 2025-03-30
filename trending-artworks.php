<?php
/**
 * VORTEX Marketplace Trending Artworks Template
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Rankings
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display trending artworks ranking
 *
 * @since 1.0.0
 * @param array $atts Display attributes
 * @return string HTML content
 */
function vortex_display_trending_artworks($atts = array()) {
    // Default attributes
    $default_atts = array(
        'count' => 5,
        'category' => 0,
        'title' => __('Trending Artworks', 'vortex-marketplace'),
        'show_rank' => true,
        'show_artist' => true,
        'show_trend_score' => true,
        'columns' => 1,
        'class' => ''
    );
    
    // Parse attributes
    $atts = wp_parse_args($atts, $default_atts);
    
    // Get current user
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Initialize AI agents for rankings display
    do_action('vortex_ai_agent_init', 'rankings_display', 
        array('HURAII', 'CLOE'), 
        'active',
        array(
            'user_id' => $user_id,
            'context' => 'trending_artworks_viewing',
            'rankings_session' => uniqid('trend_')
        )
    );
    
    // Get rankings
    $rankings = VORTEX_Rankings::get_instance();
    $artworks = $rankings->get_trending_artworks(
        $atts['count'], 
        $atts['category']
    );
    
    // Process AI-enhanced visuals and descriptions using HURAII
    foreach ($artworks as $key => $artwork) {
        // Get HURAII insights for this artwork
        $huraii_insights = apply_filters('vortex_huraii_artwork_insights', array(), $artwork['id']);
        
        if (!empty($huraii_insights)) {
            $artworks[$key]['ai_insights'] = $huraii_insights;
        }
    }
    
    // Get CLOE's curation insights
    $curation_insights = apply_filters('vortex_cloe_trending_curation', array(
        'highlighted_artwork' => 0,
        'curation_note' => '',
        'style_connections' => array()
    ), $artworks, $user_id);
    
    // Track interaction for AI learning
    do_action('vortex_ai_interaction', 'trending_artworks_view', array(
        'count' => $atts['count'],
        'category' => $atts['category']
    ), $user_id);
    
    // Classes for the container
    $classes = array(
        'vortex-trending-artworks',
        'vortex-columns-' . $atts['columns']
    );
    
    if (!empty($atts['class'])) {
        $classes[] = $atts['class'];
    }
    
    // Start output buffer
    ob_start();
    ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
        <?php if (!empty($atts['title'])) : ?>
            <h3 class="vortex-ranking-title"><?php echo esc_html($atts['title']); ?></h3>
        <?php endif; ?>
        
        <?php if (empty($artworks)) : ?>
            <p class="vortex-no-results"><?php _e('No trending artworks found.', 'vortex-marketplace'); ?></p>
        <?php else : ?>
            <div class="vortex-artworks-grid">
                <?php foreach ($artworks as $index => $artwork) : 
                    $is_highlighted = $curation_insights['highlighted_artwork'] == $artwork['id'];
                    $artwork_classes = array('vortex-artwork-card');
                    
                    if ($is_highlighted) {
                        $artwork_classes[] = 'vortex-highlighted';
                    }
                ?>
                    <div class="<?php echo esc_attr(implode(' ', $artwork_classes)); ?>">
                        <?php if ($atts['show_rank']) : ?>
                            <div class="vortex-artwork-rank"><?php echo esc_html($index + 1); ?></div>
                        <?php endif; ?>
                        
                        <div class="vortex-artwork-image">
                            <a href="<?php echo esc_url($artwork['permalink']); ?>">
                                <img src="<?php echo esc_url($artwork['thumbnail']); ?>" alt="<?php echo esc_attr($artwork['title']); ?>">
                                
                                <?php if ($atts['show_trend_score'] && isset($artwork['trend_score'])) : ?>
                                    <div class="vortex-trend-score">
                                        <span class="vortex-score-value"><?php echo esc_html(number_format($artwork['trend_score'] * 100, 0)); ?></span>
                                        <span class="vortex-score-label"><?php _e('Trend', 'vortex-marketplace'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                        
                        <div class="vortex-artwork-info">
                            <h4 class="vortex-artwork-title">
                                <a href="<?php echo esc_url($artwork['permalink']); ?>"><?php echo esc_html($artwork['title']); ?></a>
                            </h4>
                            
                            <?php if ($atts['show_artist'] && !empty($artwork['artist_name'])) : ?>
                                <div class="vortex-artwork-artist">
                                    <span class="vortex-artist-label"><?php _e('By', 'vortex-marketplace'); ?></span>
                                    <a href="<?php echo esc_url($artwork['artist_url']); ?>" class="vortex-artist-name">
                                        <?php echo esc_html($artwork['artist_name']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="vortex-artwork-metrics">
                                <div class="vortex-metric">
                                    <span class="vortex-metric-value"><?php echo esc_html($artwork['metrics']['views']); ?></span>
                                    <span class="vortex-metric-label"><?php _e('Views', 'vortex-marketplace'); ?></span>
                                </div>
                                
                                <?php if (isset($artwork['metrics']['sales'])) : ?>
                                    <div class="vortex-metric">
                                        <span class="vortex-metric-value"><?php echo esc_html($artwork['metrics']['sales']); ?></span>
                                        <span class="vortex-metric-label"><?php _e('Sales', 'vortex-marketplace'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($artwork['ai_insights'])) : ?>
                                <div class="vortex-artwork-insights">
                                    <?php if (!empty($artwork['ai_insights']['seed_art_highlight'])) : ?>
                                        <div class="vortex-seed-art-highlight">
                                            <span class="vortex-ai-badge"><?php _e('HURAII Analysis', 'vortex-marketplace'); ?></span>
                                            <p><?php echo esc_html($artwork['ai_insights']['seed_art_highlight']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($is_highlighted && !empty($curation_insights['curation_note'])) : ?>
                                <div class="vortex-cloe-insight">
                                    <span class="vortex-ai-badge"><?php _e('CLOE Pick', 'vortex-marketplace'); ?></span>
                                    <p><?php echo esc_html($curation_insights['curation_note']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="vortex-artwork-actions">
                            <a href="<?php echo esc_url($artwork['permalink']); ?>" class="vortex-view-artwork">
                                <?php _e('View Artwork', 'vortex-marketplace'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    
    return ob_get_clean();
}

// Register shortcode
add_shortcode('vortex_trending_artworks', 'vortex_display_trending_artworks'); 