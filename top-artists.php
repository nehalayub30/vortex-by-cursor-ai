<?php
/**
 * VORTEX Marketplace Top Artists Template
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Rankings
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display top artists ranking
 *
 * @since 1.0.0
 * @param array $atts Display attributes
 * @return string HTML content
 */
function vortex_display_top_artists($atts = array()) {
    // Default attributes
    $default_atts = array(
        'count' => 5,
        'category' => 0,
        'period' => '30days',
        'title' => __('Top Artists', 'vortex-marketplace'),
        'show_rank' => true,
        'show_sales' => true,
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
        array('CLOE', 'BusinessStrategist'), 
        'active',
        array(
            'user_id' => $user_id,
            'context' => 'top_artists_viewing',
            'rankings_session' => uniqid('rank_')
        )
    );
    
    // Get rankings
    $rankings = VORTEX_Rankings::get_instance();
    $artists = $rankings->get_top_artists(
        $atts['count'], 
        $atts['category'], 
        $atts['period']
    );
    
    // Process AI-enhanced recommendations
    $enhanced_display = apply_filters('vortex_cloe_enhance_artists_display', array(
        'highlighted_artist' => 0,
        'personalized_note' => '',
        'recommended_artists' => array()
    ), $artists, $user_id);
    
    // Track interaction for AI learning
    do_action('vortex_ai_interaction', 'top_artists_view', array(
        'count' => $atts['count'],
        'category' => $atts['category'],
        'period' => $atts['period']
    ), $user_id);
    
    // Classes for the container
    $classes = array(
        'vortex-top-artists',
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
        
        <?php if (empty($artists)) : ?>
            <p class="vortex-no-results"><?php _e('No artists found.', 'vortex-marketplace'); ?></p>
        <?php else : ?>
            <div class="vortex-artists-grid">
                <?php foreach ($artists as $index => $artist) : 
                    $is_highlighted = $enhanced_display['highlighted_artist'] == $artist['id'];
                    $artist_classes = array('vortex-artist-card');
                    
                    if ($is_highlighted) {
                        $artist_classes[] = 'vortex-highlighted';
                    }
                    
                    if (in_array($artist['id'], $enhanced_display['recommended_artists'])) {
                        $artist_classes[] = 'vortex-recommended';
                    }
                ?>
                    <div class="<?php echo esc_attr(implode(' ', $artist_classes)); ?>">
                        <?php
                        // Rest of the function content remains unchanged
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
} 