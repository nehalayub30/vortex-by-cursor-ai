<?php
/**
 * VORTEX Marketplace Sales Leaderboard Template
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Rankings
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display sales leaderboard ranking
 *
 * @since 1.0.0
 * @param array $atts Display attributes
 * @return string HTML content
 */
function vortex_display_sales_leaderboard($atts = array()) {
    // Default attributes
    $default_atts = array(
        'count' => 5,
        'category' => 0,
        'period' => '30days',
        'title' => __('Sales Leaderboard', 'vortex-marketplace'),
        'show_rank' => true,
        'show_artist' => true,
        'show_sales' => true,
        'show_revenue' => true,
        'columns' => 1,
        'class' => ''
    );
    
    // Parse attributes
    $atts = wp_parse_args($atts, $default_atts);
    
    // Get current user
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Initialize AI agents for sales leaderboard
    do_action('vortex_ai_agent_init', 'rankings_display', 
        array('BusinessStrategist', 'HURAII'), 
        'active',
        array(
            'user_id' => $user_id,
            'context' => 'sales_leaderboard_viewing',
            'rankings_session' => uniqid('sales_')
        )
    );
    
    // Get rankings
    $rankings = VORTEX_Rankings::get_instance();
    $artworks = $rankings->get_sales_leaderboard(
        $atts['count'], 
        $atts['category'],
        $atts['period']
    );
    
    // Get Business Strategist insights
    $market_insights = apply_filters('vortex_business_strategist_sales_insights', array(
        'price_trends' => array(),
        'market_note' => '',
        'investment_recommendations' => array()
    ), $artworks, $atts['period']);
    
    // Get HURAII visual quality insights
    foreach ($artworks as $key => $artwork) {
        // Get HURAII quality assessment for this artwork
        $quality_analysis = apply_filters('vortex_huraii_quality_assessment', array(), $artwork['id']);
        
        if (!empty($quality_analysis)) {
            $artworks[$key]['quality_analysis'] = $quality_analysis;
        }
    }
    
    // Track interaction for AI learning
    do_action('vortex_ai_interaction', 'sales_leaderboard_view', array(
        'count' => $atts['count'],
        'category' => $atts['category'],
        'period' => $atts['period']
    ), $user_id);
    
    // Classes for the container
    $classes = array(
        'vortex-sales-leaderboard',
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
        
        <?php if (!empty($market_insights['market_note'])) : ?>
            <div class="vortex-market-insight">
                <span class="vortex-ai-badge"><?php _e('Business Strategist', 'vortex-marketplace'); ?></span>
                <p><?php echo esc_html($market_insights['market_note']); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (empty($artworks)) : ?>
            <p class="vortex-no-results"><?php _e('No sales data found for this period.', 'vortex-marketplace'); ?></p>
        <?php else : ?>
            <div class="vortex-artworks-grid">
                <?php foreach ($artworks as $index => $artwork) : ?>
                    <div class="vortex-artwork-card">
                        <?php if ($atts['show_rank']) : ?>
                            <div class="vortex-artwork-rank"><?php echo esc_html($index + 1); ?></div>
                        <?php endif; ?>
                        
                        <div class="vortex-artwork-image">
                            <a href="<?php echo esc_url($artwork['permalink']); ?>">
                                <img src="<?php echo esc_url($artwork['thumbnail']); ?>" alt="<?php echo esc_attr($artwork['title']); ?>">
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
                            
                            <?php if (!empty($artwork['quality_analysis'])) : ?>
                                <div class="vortex-artwork-insights">
                                    <?php if (!empty($artwork['quality_analysis']['seed_art_highlight'])) : ?>
                                        <div class="vortex-seed-art-highlight">
                                            <span class="vortex-ai-badge"><?php _e('HURAII Analysis', 'vortex-marketplace'); ?></span>
                                            <p><?php echo esc_html($artwork['quality_analysis']['seed_art_highlight']); ?></p>
                                        </div>
                                    <?php endif; ?>
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
add_shortcode('vortex_sales_leaderboard', 'vortex_display_sales_leaderboard'); 