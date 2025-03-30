<?php
/**
 * The template for displaying single artwork
 *
 * @package VORTEX_AI_Marketplace
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

// Initialize AI agents for content enhancement and personalization
do_action('vortex_ai_agent_init', array(
    'context' => 'single_artwork',
    'agents' => array('HURAII', 'CLOE', 'BusinessStrategist'),
    'learning_mode' => 'active'
));

// Load artwork data with AI-enhanced metadata
$artwork_id = get_the_ID();
$artwork_data = apply_filters('vortex_ai_enhance_artwork_data', array(
    'id' => $artwork_id,
    'title' => get_the_title(),
    'content' => get_the_content(),
    'timestamp' => get_the_time('U'),
    'modified' => get_the_modified_time('U')
));

// Track user interaction for AI learning
do_action('vortex_ai_track_interaction', array(
    'entity_type' => 'artwork',
    'entity_id' => $artwork_id,
    'action' => 'view',
    'user_id' => get_current_user_id() ?: 0
));

?>

<div id="primary" class="vortex-content-area vortex-single-artwork">
    <main id="main" class="vortex-site-main">

        <?php while (have_posts()) : the_post(); ?>

            <?php
            // Generate AI-enhanced recommendations
            $ai_recommendations = apply_filters('vortex_ai_get_recommendations', array(
                'entity_type' => 'artwork',
                'entity_id' => $artwork_id,
                'user_id' => get_current_user_id() ?: 0,
                'max_items' => 4
            ));
            
            // Get AI-enhanced artwork insights
            $artwork_insights = apply_filters('vortex_ai_get_artwork_insights', $artwork_id);
            ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class('vortex-artwork'); ?>>
                <div class="vortex-artwork-container">
                    <?php get_template_part('template-parts/artwork-single', null, array(
                        'artwork_data' => $artwork_data,
                        'artwork_insights' => $artwork_insights,
                        'ai_recommendations' => $ai_recommendations
                    )); ?>
                    
                    <?php 
                    // AI-enhanced comments and discussion
                    if (comments_open() || get_comments_number()) {
                        // Preprocess comments with AI for sentiment analysis and moderation
                        do_action('vortex_ai_preprocess_comments', $artwork_id);
                        comments_template();
                    }
                    ?>
                    
                    <div class="vortex-ai-powered-features">
                        <div class="vortex-ai-recommendations">
                            <h3><?php esc_html_e('You may also like', 'vortex'); ?></h3>
                            <div class="vortex-ai-recommendation-grid">
                                <?php
                                // Display AI-curated recommendations
                                foreach ($ai_recommendations['items'] as $item) :
                                    get_template_part('template-parts/artwork-card', null, array(
                                        'artwork' => $item,
                                        'show_reason' => true
                                    ));
                                endforeach;
                                ?>
                            </div>
                            <div class="vortex-ai-label">
                                <?php esc_html_e('Recommendations powered by VORTEX AI', 'vortex'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

        <?php endwhile; ?>

    </main>
</div>

<?php
// AI Agent real-time learning from page interaction
add_action('wp_footer', function() use ($artwork_id) {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof VortexAISystem !== 'undefined') {
            VortexAISystem.trackEntityView({
                entityType: 'artwork',
                entityId: <?php echo json_encode($artwork_id); ?>,
                sessionId: VortexAISystem.getSessionId(),
                timestamp: Date.now()
            });
            
            // Initialize continuous learning for HURAII
            VortexAISystem.initContinuousLearning('HURAII', {
                context: 'artwork_viewing',
                entityId: <?php echo json_encode($artwork_id); ?>
            });
            
            // Initialize business intelligence for market trends
            VortexAISystem.initAgentModule('BusinessStrategist', {
                context: 'market_analysis',
                entityType: 'artwork'
            });
        }
    });
    </script>
    <?php
}, 20);

get_sidebar();
get_footer(); 