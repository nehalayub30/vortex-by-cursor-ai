<?php
/**
 * The template for displaying artwork archives
 *
 * @package VORTEX_AI_Marketplace
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

// Initialize AI agents for smart artwork curation
do_action('vortex_ai_agent_init', array(
    'context' => 'artwork_archive',
    'agents' => array('HURAII', 'CLOE', 'BusinessStrategist'),
    'learning_mode' => 'active'
));

// Get AI-optimized filter settings
$ai_filters = apply_filters('vortex_ai_get_optimal_filters', array(
    'entity_type' => 'artwork',
    'user_id' => get_current_user_id() ?: 0
));

// Get trending categories as determined by BusinessStrategist AI
$trending_categories = apply_filters('vortex_ai_get_trending_categories', array(
    'taxonomy' => 'vortex-artwork-category',
    'limit' => 5
));

// AI-enhanced sorting options
$sorting_options = apply_filters('vortex_ai_get_sorting_options', array(
    'entity_type' => 'artwork',
    'include_personalized' => true
));

// Get current filter settings
$current_filters = apply_filters('vortex_ai_process_filter_request', $_GET);

// AI-powered featured artworks selected for current user
$featured_artworks = apply_filters('vortex_ai_get_featured_artworks', array(
    'user_id' => get_current_user_id() ?: 0,
    'limit' => 3
));

?>

<div id="primary" class="vortex-content-area vortex-archive-artwork">
    <main id="main" class="vortex-site-main">

        <header class="vortex-page-header">
            <h1 class="vortex-page-title"><?php post_type_archive_title(); ?></h1>
            <?php the_archive_description('<div class="vortex-archive-description">', '</div>'); ?>
            
            <?php if (!empty($featured_artworks)) : ?>
                <div class="vortex-featured-artworks">
                    <h2><?php esc_html_e('Featured Artworks', 'vortex'); ?></h2>
                    <div class="vortex-featured-grid">
                        <?php foreach ($featured_artworks as $artwork) : ?>
                            <div class="vortex-featured-item">
                                <a href="<?php echo esc_url(get_permalink($artwork->ID)); ?>">
                                    <?php echo get_the_post_thumbnail($artwork->ID, 'large'); ?>
                                    <div class="vortex-featured-overlay">
                                        <h3><?php echo esc_html(get_the_title($artwork->ID)); ?></h3>
                                        <?php if (!empty($artwork->artist_name)) : ?>
                                            <p class="vortex-featured-artist"><?php echo esc_html($artwork->artist_name); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <?php if (!empty($artwork->ai_recommendation_reason)) : ?>
                                    <div class="vortex-ai-reason">
                                        <span class="vortex-ai-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm0-4h-2V7h2v8z" fill="currentColor"/></svg>
                                        </span>
                                        <?php echo esc_html($artwork->ai_recommendation_reason); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="vortex-ai-label">
                        <?php esc_html_e('Curated by HURAII AI', 'vortex'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </header>
        
        <div class="vortex-archive-container">
            <div class="vortex-filter-sidebar">
                <div class="vortex-filter-heading">
                    <h2><?php esc_html_e('Filter Artworks', 'vortex'); ?></h2>
                    <?php if (!empty($ai_filters['suggested'])) : ?>
                        <button type="button" class="vortex-ai-suggested-filters" id="vortex-apply-ai-filters">
                            <?php esc_html_e('Apply AI Filters', 'vortex'); ?>
                        </button>
                    <?php endif; ?>
                </div>
                
                <form method="get" class="vortex-filter-form">
                    <?php
                    // Output filter fields as created by AI
                    echo apply_filters('vortex_ai_render_filter_form', array(
                        'entity_type' => 'artwork',
                        'current_filters' => $current_filters
                    ));
                    ?>
                    
                    <div class="vortex-filter-actions">
                        <button type="submit" class="vortex-filter-button">
                            <?php esc_html_e('Apply Filters', 'vortex'); ?>
                        </button>
                        <a href="<?php echo esc_url(get_post_type_archive_link('vortex-artwork')); ?>" class="vortex-reset-button">
                            <?php esc_html_e('Reset', 'vortex'); ?>
                        </a>
                    </div>
                </form>
                
                <?php if (!empty($trending_categories)) : ?>
                    <div class="vortex-trending-categories">
                        <h3><?php esc_html_e('Trending Categories', 'vortex'); ?></h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php
get_sidebar();
get_footer(); 