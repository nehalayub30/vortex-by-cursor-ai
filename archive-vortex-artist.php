<?php
/**
 * The template for displaying artist archives
 *
 * @package VORTEX_AI_Marketplace
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

// Initialize AI agents for artist discovery
do_action('vortex_ai_agent_init', array(
    'context' => 'artist_archive',
    'agents' => array('HURAII', 'CLOE', 'BusinessStrategist'),
    'learning_mode' => 'active'
));

// Get AI-curated featured artists
$featured_artists = apply_filters('vortex_ai_get_featured_artists', array(
    'user_id' => get_current_user_id() ?: 0,
    'limit' => 3,
    'with_reasoning' => true
));

// Get AI-identified emerging artists
$emerging_artists = apply_filters('vortex_ai_get_emerging_artists', array(
    'limit' => 4
));

// Get current filter settings
$current_filters = apply_filters('vortex_ai_process_filter_request', $_GET);

// AI-enhanced sorting options
$sorting_options = apply_filters('vortex_ai_get_sorting_options', array(
    'entity_type' => 'artist',
    'include_personalized' => true
));

// Get artist specialty categories from BusinessStrategist
$specialty_categories = apply_filters('vortex_ai_get_artist_specialties', array(
    'limit' => 10
));

?>

<div id="primary" class="vortex-content-area vortex-archive-artist">
    <main id="main" class="vortex-site-main">

        <header class="vortex-page-header">
            <h1 class="vortex-page-title"><?php post_type_archive_title(); ?></h1>
            <?php the_archive_description('<div class="vortex-archive-description">', '</div>'); ?>
            
            <?php if (!empty($featured_artists)) : ?>
                <div class="vortex-featured-artists">
                    <h2><?php esc_html_e('Featured Artists', 'vortex'); ?></h2>
                    <div class="vortex-featured-grid">
                        <?php foreach ($featured_artists as $artist) : ?>
                            <div class="vortex-featured-artist">
                                <a href="<?php echo esc_url(get_permalink($artist->ID)); ?>" class="vortex-artist-link">
                                    <div class="vortex-artist-image">
                                        <?php echo get_the_post_thumbnail($artist->ID, 'large'); ?>
                                    </div>
                                    <div class="vortex-artist-overlay">
                                        <h3><?php echo esc_html(get_the_title($artist->ID)); ?></h3>
                                        <?php if (!empty($artist->specialty)) : ?>
                                            <p class="vortex-artist-specialty"><?php echo esc_html($artist->specialty); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </a>
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
                    <h2><?php esc_html_e('Filter Artists', 'vortex'); ?></h2>
                </div>
                
                <form method="get" class="vortex-filter-form">
                    <?php
                    // Output filter fields as created by AI
                    echo apply_filters('vortex_ai_render_filter_form', array(
                        'entity_type' => 'artist',
                        'current_filters' => $current_filters
                    ));
                    ?>
                    
                    <div class="vortex-filter-actions">
                        <button type="submit" class="vortex-filter-button">
                            <?php esc_html_e('Apply Filters', 'vortex'); ?>
                        </button>
                        <a href="<?php echo esc_url(get_post_type_archive_link('vortex-artist')); ?>" class="vortex-reset-button">
                            <?php esc_html_e('Reset', 'vortex'); ?>
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="vortex-archive-content">
                <div class="vortex-sorting-options">
                    <div class="vortex-sort-by">
                        <label for="vortex-sort"><?php esc_html_e('Sort by:', 'vortex'); ?></label>
                        <select id="vortex-sort" class="vortex-sort-select">
                            <?php foreach ($sorting_options as $key => $label) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($current_filters['sort'] ?? '', $key); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($sorting_options['ai_personalized'])) : ?>
                            <span class="vortex-ai-tag">
                                <?php esc_html_e('AI', 'vortex'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (have_posts()) : ?>
                    <div class="vortex-artist-grid">
                        <?php while (have_posts()) : the_post(); ?>
                            <div class="vortex-artist-card">
                                <a href="<?php the_permalink(); ?>" class="vortex-artist-link">
                                    <?php echo get_the_post_thumbnail(get_the_ID(), 'medium'); ?>
                                    <h3><?php the_title(); ?></h3>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php
                    // AI-enhanced pagination
                    echo apply_filters('vortex_ai_enhanced_pagination', array(
                        'show_load_more' => true,
                        'infinite_scroll' => false
                    ));
                    ?>
                    
                <?php else : ?>
                    <div class="vortex-no-artists">
                        <h2><?php esc_html_e('No artists found', 'vortex'); ?></h2>
                        <p><?php esc_html_e('Try adjusting your filters or search criteria.', 'vortex'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php
// AI agents learning from browsing behavior
add_action('wp_footer', function() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof VortexAISystem !== 'undefined') {
            // Initialize AI filter suggestions
            VortexAISystem.initFilterSuggestions('artist');
            
            // Track filter usage for learning
            document.querySelector('.vortex-filter-form')?.addEventListener('submit', function(e) {
                VortexAISystem.trackFilterUsage('artist', new FormData(this));
            });
            
            // Initialize sorting with AI learning
            document.querySelector('.vortex-sort-select')?.addEventListener('change', function() {
                const sort = this.value;
                VortexAISystem.trackSortPreference('artist', sort);
                
                // Reload with new sort
                const url = new URL(window.location);
                url.searchParams.set('sort', sort);
                window.location = url.toString();
            });
        }
    });
    </script>
    <?php
}, 20);

get_sidebar();
get_footer(); 