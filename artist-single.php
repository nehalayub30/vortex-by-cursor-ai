<?php
/**
 * Template part for displaying single artist content
 *
 * @package VORTEX_AI_Marketplace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get the current artist
global $post;
$artist_id = $post->ID;
$user_id = get_current_user_id();

// Get artist details
$location = get_post_meta($artist_id, 'vortex_artist_location', true);
$specialty = get_post_meta($artist_id, 'vortex_artist_specialty', true);
$artist_since = get_post_meta($artist_id, 'vortex_artist_since', true);
$artist_type = get_post_meta($artist_id, 'vortex_artist_type', true);
$social_links = get_post_meta($artist_id, 'vortex_artist_social', true);
$website = get_post_meta($artist_id, 'vortex_artist_website', true);

// Get CLOE's artist insights
$artist_insights = apply_filters('vortex_ai_get_cloe_insights', array(
    'artist_id' => $artist_id,
    'insight_type' => 'artist_analysis',
    'user_id' => $user_id
));

// Get BusinessStrategist's market analysis
$artist_market = apply_filters('vortex_ai_get_business_insights', array(
    'artist_id' => $artist_id,
    'analysis_type' => 'artist_market_position'
));

// Get HURAII's style analysis
$style_analysis = apply_filters('vortex_ai_get_huraii_analysis', array(
    'artist_id' => $artist_id,
    'analysis_type' => 'style_signature'
));

// Track artist profile view for AI learning
do_action('vortex_ai_track_interaction', array(
    'entity_type' => 'artist',
    'entity_id' => $artist_id,
    'action' => 'view',
    'user_id' => $user_id ?: 0
));

// Get the artist's artworks
$artist_artworks = get_posts(array(
    'post_type' => 'vortex-artwork',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => 'vortex_artwork_artist',
            'value' => $artist_id
        )
    )
));

// Get AI-curated collections/exhibitions by this artist
$artist_collections = apply_filters('vortex_ai_get_cloe_curation', array(
    'artist_id' => $artist_id,
    'curation_type' => 'artist_collections',
    'limit' => 2
));
?>

<article id="artist-<?php echo esc_attr($artist_id); ?>" <?php post_class('vortex-artist-single'); ?>>
    <div class="vortex-artist-header">
        <div class="vortex-artist-profile">
            <div class="vortex-artist-avatar">
                <?php 
                if (has_post_thumbnail()) {
                    the_post_thumbnail('vortex-artist-large', array('class' => 'artist-featured-image'));
                }
                ?>
            </div>
            
            <div class="vortex-artist-headline">
                <h1 class="vortex-artist-name"><?php the_title(); ?></h1>
                
                <?php if (!empty($specialty)) : ?>
                    <div class="vortex-artist-specialty"><?php echo esc_html($specialty); ?></div>
                <?php endif; ?>
                
                <div class="vortex-artist-meta">
                    <?php if (!empty($location)) : ?>
                        <div class="vortex-artist-meta-item">
                            <span class="meta-icon location-icon"></span>
                            <span class="meta-text"><?php echo esc_html($location); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($artist_since)) : ?>
                        <div class="vortex-artist-meta-item">
                            <span class="meta-icon calendar-icon"></span>
                            <span class="meta-text"><?php echo esc_html(sprintf(__('Artist since %s', 'vortex'), $artist_since)); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($artist_type)) : ?>
                        <div class="vortex-artist-meta-item">
                            <span class="meta-icon artist-type-icon"></span>
                            <span class="meta-text"><?php echo esc_html($artist_type); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="vortex-artist-actions">
                    <button class="vortex-follow-artist-btn" data-artist-id="<?php echo esc_attr($artist_id); ?>">
                        <span class="follow-icon"></span>
                        <span class="follow-text"><?php esc_html_e('Follow Artist', 'vortex'); ?></span>
                    </button>
                    
                    <?php if (!empty($website)) : ?>
                        <a href="<?php echo esc_url($website); ?>" class="vortex-artist-website-btn" target="_blank">
                            <span class="website-icon"></span>
                            <span class="website-text"><?php esc_html_e('Visit Website', 'vortex'); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($social_links)) : ?>
                    <div class="vortex-artist-social">
                        <?php foreach ($social_links as $platform => $url) : ?>
                            <a href="<?php echo esc_url($url); ?>" class="vortex-social-link <?php echo esc_attr($platform); ?>" target="_blank">
                                <span class="social-icon <?php echo esc_attr($platform); ?>-icon"></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($artist_market['notable_achievements'])) : ?>
            <div class="vortex-artist-achievements">
                <h3><?php esc_html_e('Notable Achievements', 'vortex'); ?></h3>
                <ul class="vortex-achievements-list">
                    <?php foreach ($artist_market['notable_achievements'] as $achievement) : ?>
                        <li>
                            <span class="achievement-year"><?php echo esc_html($achievement['year']); ?></span>
                            <span class="achievement-desc"><?php echo esc_html($achievement['description']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="vortex-artist-content">
        <div class="vortex-artist-bio">
            <h2><?php esc_html_e('About the Artist', 'vortex'); ?></h2>
            <?php the_content(); ?>
            
            <?php if (!empty($artist_insights['ai_enhanced_bio'])) : ?>
                <div class="vortex-ai-enhanced-bio">
                    <div class="vortex-ai-card">
                        <div class="vortex-ai-avatar">
                            <img src="<?php echo esc_url(VORTEX_PLUGIN_URL . 'assets/images/cloe-avatar.png'); ?>" alt="CLOE" />
                        </div>
                        <div class="vortex-ai-content">
                            <h3><?php esc_html_e('CLOE\'s Artist Insight', 'vortex'); ?></h3>
                            <?php echo wp_kses_post($artist_insights['ai_enhanced_bio']); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($style_analysis)) : ?>
            <div class="vortex-style-analysis">
                <h2><?php esc_html_e('Artistic Style Analysis', 'vortex'); ?></h2>
                <div class="vortex-ai-card">
                    <div class="vortex-ai-avatar">
                        <img src="<?php echo esc_url(VORTEX_PLUGIN_URL . 'assets/images/huraii-avatar.png'); ?>" alt="HURAII" />
                    </div>
                    <div class="vortex-ai-content">
                        <div class="vortex-style-summary">
                            <p class="vortex-huraii-greeting"><?php echo esc_html($style_analysis['greeting']); ?></p>
                            <?php echo wp_kses_post($style_analysis['style_summary']); ?>
                        </div>
                        
                        <?php if (!empty($style_analysis['key_elements'])) : ?>
                            <div class="vortex-style-elements">
                                <h4><?php esc_html_e('Key Stylistic Elements', 'vortex'); ?></h4>
                                <div class="vortex-elements-grid">
                                    <?php foreach ($style_analysis['key_elements'] as $element) : ?>
                                        <div class="vortex-style-element">
                                            <h5><?php echo esc_html($element['name']); ?></h5>
                                            <p><?php echo esc_html($element['description']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($style_analysis['influences'])) : ?>
                            <div class="vortex-style-influences">
                                <h4><?php esc_html_e('Artistic Influences', 'vortex'); ?></h4>
                                <p><?php echo esc_html($style_analysis['influences_summary']); ?></p>
                                <div class="vortex-influences-list">
                                    <?php foreach ($style_analysis['influences'] as $influence) : ?>
                                        <div class="vortex-influence-item">
                                            <span class="influence-name"><?php echo esc_html($influence['name']); ?></span>
                                            <span class="influence-connection"><?php echo esc_html($influence['connection']); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($style_analysis['signature_techniques'])) : ?>
                            <div class="vortex-signature-techniques">
                                <h4><?php esc_html_e('Signature Techniques', 'vortex'); ?></h4>
                                <ul>
                                    <?php foreach ($style_analysis['signature_techniques'] as $technique) : ?>
                                        <li><?php echo esc_html($technique); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($style_analysis['create_in_style_url'])) : ?>
                            <div class="vortex-create-in-style">
                                <a href="<?php echo esc_url($style_analysis['create_in_style_url']); ?>" class="vortex-create-btn">
                                    <?php esc_html_e('Create in This Style with HURAII', 'vortex'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($artist_market)) : ?>
            <div class="vortex-market-analysis">
                <h2><?php esc_html_e('Market Position & Investment Potential', 'vortex'); ?></h2>
                <div class="vortex-ai-card">
                    <div class="vortex-ai-avatar">
                        <img src="<?php echo esc_url(VORTEX_PLUGIN_URL . 'assets/images/business-strategist-avatar.png'); ?>" alt="Business Strategist" />
                    </div>
                    <div class="vortex-ai-content">
                        <div class="vortex-market-position">
                            <?php echo wp_kses_post($artist_market['market_position']); ?>
                        </div>
                        
                        <?php if (!empty($artist_market['investment_potential'])) : ?>
                            <div class="vortex-investment-potential">
                                <h4><?php esc_html_e('Investment Potential', 'vortex'); ?></h4>
                                
                                <div class="vortex-potential-rating">
                                    <div class="vortex-rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                                            <span class="vortex-star <?php echo ($i <= $artist_market['investment_rating']) ? 'filled' : ''; ?>"></span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="vortex-rating-text"><?php echo esc_html($artist_market['investment_potential']); ?></span>
                                </div>
                                
                                <p class="vortex-investment-notes"><?php echo esc_html($artist_market['investment_notes']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</article> 