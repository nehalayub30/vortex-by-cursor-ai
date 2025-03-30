<?php
/**
 * The template for displaying single artist
 *
 * @package VORTEX_AI_Marketplace
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

// Initialize AI agents for artist profile enhancement
do_action('vortex_ai_agent_init', array(
    'context' => 'single_artist',
    'agents' => array('HURAII', 'CLOE', 'BusinessStrategist'),
    'learning_mode' => 'active'
));

// Load artist data with AI-enhanced insights
$artist_id = get_the_ID();
$artist_data = apply_filters('vortex_ai_enhance_artist_data', array(
    'id' => $artist_id,
    'name' => get_the_title(),
    'bio' => get_the_content(),
    'timestamp' => get_the_time('U'),
    'modified' => get_the_modified_time('U')
));

// Track user interaction for AI learning
do_action('vortex_ai_track_interaction', array(
    'entity_type' => 'artist',
    'entity_id' => $artist_id,
    'action' => 'view',
    'user_id' => get_current_user_id() ?: 0
));

// Get AI-enhanced artist insights
$artist_insights = apply_filters('vortex_ai_get_artist_insights', $artist_id);

// Get AI-curated top artworks by this artist
$top_artworks = apply_filters('vortex_ai_get_top_artworks', array(
    'artist_id' => $artist_id,
    'limit' => 6,
    'sort_by' => 'ai_recommended'
));

// Get AI-predicted artist trajectory
$artist_trajectory = apply_filters('vortex_ai_get_artist_trajectory', $artist_id);

?>

<div id="primary" class="vortex-content-area vortex-single-artist">
    <main id="main" class="vortex-site-main">

        <?php while (have_posts()) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class('vortex-artist'); ?>>
                <div class="vortex-artist-container">
                    <?php get_template_part('template-parts/artist-single', null, array(
                        'artist_data' => $artist_data,
                        'artist_insights' => $artist_insights,
                        'artist_trajectory' => $artist_trajectory
                    )); ?>
                    
                    <div class="vortex-artist-collection">
                        <h2><?php esc_html_e('Featured Artwork', 'vortex'); ?></h2>
                        
                        <?php if (!empty($top_artworks)) : ?>
                            <div class="vortex-artwork-grid">
                                <?php foreach ($top_artworks as $artwork) : ?>
                                    <div class="vortex-artwork-item">
                                        <a href="<?php echo esc_url(get_permalink($artwork->ID)); ?>">
                                            <?php echo get_the_post_thumbnail($artwork->ID, 'medium'); ?>
                                            <h3><?php echo esc_html(get_the_title($artwork->ID)); ?></h3>
                                        </a>
                                        <?php if (!empty($artwork->ai_significance)) : ?>
                                            <div class="vortex-ai-insight">
                                                <span class="vortex-ai-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-14h2v7h-2zm0 8h2v2h-2z" fill="currentColor"/></svg>
                                                </span>
                                                <?php echo esc_html($artwork->ai_significance); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="vortex-ai-label">
                                <?php esc_html_e('Selection curated by CLOE AI', 'vortex'); ?>
                            </div>
                        <?php else : ?>
                            <p><?php esc_html_e('No artwork found.', 'vortex'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    // AI-enhanced market insights for this artist
                    $market_insights = apply_filters('vortex_ai_get_artist_market_insights', $artist_id);
                    
                    if ($market_insights && !empty($market_insights)) : 
                    ?>
                        <div class="vortex-artist-market-insights">
                            <h2><?php esc_html_e('Market Insights', 'vortex'); ?></h2>
                            <div class="vortex-market-data">
                                <?php foreach ($market_insights as $insight) : ?>
                                    <div class="vortex-market-insight">
                                        <h3><?php echo esc_html($insight['title']); ?></h3>
                                        <p><?php echo esc_html($insight['description']); ?></p>
                                        <?php if (!empty($insight['data'])) : ?>
                                            <div class="vortex-insight-chart" 
                                                 data-chart="<?php echo esc_attr(json_encode($insight['data'])); ?>"
                                                 data-type="<?php echo esc_attr($insight['chart_type']); ?>">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <div class="vortex-ai-label">
                                    <?php esc_html_e('Insights powered by BusinessStrategist AI', 'vortex'); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                    // AI-suggested similar artists
                    $similar_artists = apply_filters('vortex_ai_get_similar_artists', array(
                        'artist_id' => $artist_id,
                        'limit' => 4
                    ));
                    
                    if ($similar_artists && !empty($similar_artists)) : 
                    ?>
                        <div class="vortex-similar-artists">
                            <h2><?php esc_html_e('Similar Artists', 'vortex'); ?></h2>
                            <div class="vortex-artist-grid">
                                <?php foreach ($similar_artists as $sim_artist) : ?>
                                    <div class="vortex-artist-card">
                                        <a href="<?php echo esc_url(get_permalink($sim_artist->ID)); ?>">
                                            <?php echo get_the_post_thumbnail($sim_artist->ID, 'thumbnail'); ?>
                                            <h3><?php echo esc_html(get_the_title($sim_artist->ID)); ?></h3>
                                        </a>
                                        <?php if (!empty($sim_artist->similarity_reason)) : ?>
                                            <div class="vortex-similarity-reason">
                                                <?php echo esc_html($sim_artist->similarity_reason); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="vortex-ai-label">
                                <?php esc_html_e('Suggested by HURAII AI', 'vortex'); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </article>

        <?php endwhile; ?>

    </main>
</div>

<?php
// AI Agent real-time learning from artist page interaction
add_action('wp_footer', function() use ($artist_id) {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof VortexAISystem !== 'undefined') {
            // Initialize chart rendering
            VortexAISystem.renderInsightCharts();
            
            // Track artist view for AI learning
            VortexAISystem.trackEntityView({
                entityType: 'artist',
                entityId: <?php echo json_encode($artist_id); ?>,
                sessionId: VortexAISystem.getSessionId(),
                timestamp: Date.now()
            });
            
            // Initialize CLOE for style analysis
            VortexAISystem.initAgentModule('CLOE', {
                context: 'artist_style_analysis',
                entityId: <?php echo json_encode($artist_id); ?>
            });
            
            // Initialize BusinessStrategist for market prediction
            VortexAISystem.initAgentModule('BusinessStrategist', {
                context: 'artist_market_prediction',
                entityId: <?php echo json_encode($artist_id); ?>
            });
        }
    });
    </script>
    <?php
}, 20);

get_sidebar();
get_footer(); 