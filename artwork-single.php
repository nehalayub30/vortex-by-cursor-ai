<?php
/**
 * Template part for displaying single artwork content
 *
 * @package VORTEX_AI_Marketplace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get the current artwork
global $post;
$artwork_id = $post->ID;
$user_id = get_current_user_id();

// Get artwork details with error handling
$artist_id = get_post_meta($artwork_id, 'vortex_artwork_artist', true);
$price = get_post_meta($artwork_id, 'vortex_artwork_price', true);
$dimensions = get_post_meta($artwork_id, 'vortex_artwork_dimensions', true);
$medium = get_post_meta($artwork_id, 'vortex_artwork_medium', true);
$year = get_post_meta($artwork_id, 'vortex_artwork_year', true);
$edition_info = get_post_meta($artwork_id, 'vortex_artwork_edition_info', true);
$availability = get_post_meta($artwork_id, 'vortex_artwork_availability', true) ?: 'available';

// Get HURAII AI analysis of the artwork with error handling
$seed_art_analysis = apply_filters('vortex_ai_get_huraii_analysis', array(
    'artwork_id' => $artwork_id,
    'analysis_type' => 'seed_art',
    'include_components' => array(
        'sacred_geometry',
        'color_weight',
        'light_shadow',
        'texture',
        'perspective',
        'artwork_size',
        'movement_layering'
    )
));

// Get CLOE's artwork insights with error handling
$artwork_insights = apply_filters('vortex_ai_get_cloe_insights', array(
    'artwork_id' => $artwork_id,
    'insight_type' => 'artwork_context',
    'user_id' => $user_id
));

// Get BusinessStrategist's market valuation with error handling
$artwork_valuation = apply_filters('vortex_ai_get_business_valuation', array(
    'artwork_id' => $artwork_id,
    'valuation_type' => 'market_value'
));

// Track artwork view for AI learning
do_action('vortex_ai_track_interaction', array(
    'entity_type' => 'artwork',
    'entity_id' => $artwork_id,
    'action' => 'view',
    'user_id' => $user_id ?: 0
));
?>

<article id="artwork-<?php echo esc_attr($artwork_id); ?>" <?php post_class('vortex-artwork-single'); ?>>
    <div class="vortex-artwork-gallery">
        <div class="vortex-artwork-main-image">
            <?php 
            if (has_post_thumbnail()) {
                the_post_thumbnail('vortex-artwork-large', array('class' => 'artwork-featured-image'));
            } else {
                // Fallback image
                echo '<img src="' . esc_url(VORTEX_PLUGIN_URL . 'assets/images/artwork-placeholder.jpg') . '" alt="' . esc_attr(get_the_title()) . '" class="artwork-featured-image placeholder-image">';
            }
            ?>
            
            <div class="vortex-image-zoom-controls">
                <button class="vortex-zoom-in" aria-label="<?php esc_attr_e('Zoom in', 'vortex'); ?>">+</button>
                <button class="vortex-zoom-out" aria-label="<?php esc_attr_e('Zoom out', 'vortex'); ?>">-</button>
                <button class="vortex-zoom-reset" aria-label="<?php esc_attr_e('Reset zoom', 'vortex'); ?>">↺</button>
            </div>
            
            <?php if (!empty($artwork_insights['highlight_regions'])) : ?>
                <div class="vortex-artwork-hotspots">
                    <?php foreach ($artwork_insights['highlight_regions'] as $index => $hotspot) : ?>
                        <div class="vortex-hotspot" style="left: <?php echo esc_attr($hotspot['x']); ?>%; top: <?php echo esc_attr($hotspot['y']); ?>%;" 
                             data-index="<?php echo esc_attr($index); ?>" data-title="<?php echo esc_attr($hotspot['title']); ?>">
                            <span class="vortex-hotspot-dot"></span>
                            <div class="vortex-hotspot-tooltip">
                                <h4><?php echo esc_html($hotspot['title']); ?></h4>
                                <p><?php echo esc_html($hotspot['description']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php 
        // Get additional artwork images
        $gallery_images = get_post_meta($artwork_id, 'vortex_artwork_gallery', true);
        if (!empty($gallery_images)) : 
        ?>
            <div class="vortex-artwork-thumbnails">
                <div class="thumbnail active" data-image="<?php echo esc_url(get_the_post_thumbnail_url($artwork_id, 'vortex-artwork-large')); ?>">
                    <?php the_post_thumbnail('vortex-artwork-thumbnail'); ?>
                </div>
                
                <?php foreach ($gallery_images as $image_id) : ?>
                    <div class="thumbnail" data-image="<?php echo esc_url(wp_get_attachment_image_url($image_id, 'vortex-artwork-large')); ?>">
                        <?php echo wp_get_attachment_image($image_id, 'vortex-artwork-thumbnail'); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="vortex-artwork-content">
        <header class="vortex-artwork-header">
            <h1 class="vortex-artwork-title"><?php the_title(); ?></h1>
            
            <?php if ($artist_id) : 
                $artist = get_post($artist_id);
                if ($artist && !is_wp_error($artist)) :
            ?>
                <div class="vortex-artwork-artist">
                    <?php echo get_the_post_thumbnail($artist_id, 'thumbnail', array('class' => 'artist-thumbnail')); ?>
                    <div class="vortex-artist-info">
                        <span class="artist-by"><?php esc_html_e('By', 'vortex'); ?></span>
                        <a href="<?php echo esc_url(get_permalink($artist_id)); ?>" class="artist-name">
                            <?php echo esc_html(get_the_title($artist_id)); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="vortex-artwork-status <?php echo esc_attr($availability); ?>">
                <?php
                if ($availability === 'available') {
                    echo '<span class="status-indicator available"></span>' . esc_html__('Available for Purchase', 'vortex');
                } elseif ($availability === 'sold') {
                    echo '<span class="status-indicator sold"></span>' . esc_html__('Sold', 'vortex');
                } elseif ($availability === 'reserved') {
                    echo '<span class="status-indicator reserved"></span>' . esc_html__('Reserved', 'vortex');
                }
                ?>
            </div>
        </header>
        
        <div class="vortex-artwork-details">
            <?php if (!empty($price) && $availability !== 'sold') : ?>
                <div class="vortex-artwork-price">
                    <span class="price-label"><?php esc_html_e('Price:', 'vortex'); ?></span>
                    <span class="price-value"><?php echo esc_html(vortex_format_price($price)); ?></span>
                    
                    <?php if (!empty($artwork_valuation['price_trend'])) : ?>
                        <div class="vortex-price-trend <?php echo esc_attr($artwork_valuation['price_trend_direction']); ?>">
                            <span class="trend-indicator"></span>
                            <span class="trend-text"><?php echo esc_html($artwork_valuation['price_trend']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="vortex-artwork-meta">
                <?php if (!empty($medium)) : ?>
                    <div class="vortex-artwork-meta-item">
                        <span class="meta-label"><?php esc_html_e('Medium:', 'vortex'); ?></span>
                        <span class="meta-value"><?php echo esc_html($medium); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($dimensions)) : ?>
                    <div class="vortex-artwork-meta-item">
                        <span class="meta-label"><?php esc_html_e('Dimensions:', 'vortex'); ?></span>
                        <span class="meta-value"><?php echo esc_html($dimensions); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($year)) : ?>
                    <div class="vortex-artwork-meta-item">
                        <span class="meta-label"><?php esc_html_e('Year:', 'vortex'); ?></span>
                        <span class="meta-value"><?php echo esc_html($year); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($edition_info)) : ?>
                    <div class="vortex-artwork-meta-item">
                        <span class="meta-label"><?php esc_html_e('Edition:', 'vortex'); ?></span>
                        <span class="meta-value"><?php echo esc_html($edition_info); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="vortex-artwork-actions">
                <?php if ($availability === 'available') : ?>
                    <form class="vortex-add-to-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
                        <input type="hidden" name="artwork_id" value="<?php echo esc_attr($artwork_id); ?>">
                        <?php wp_nonce_field('vortex_add_to_cart', 'vortex_add_to_cart_nonce'); ?>
                        
                        <button type="submit" class="vortex-add-to-cart-btn" name="add-to-cart" value="<?php echo esc_attr($artwork_id); ?>">
                            <?php esc_html_e('Add to Cart', 'vortex'); ?>
                        </button>
                    </form>
                    
                    <button class="vortex-buy-now-btn" data-artwork-id="<?php echo esc_attr($artwork_id); ?>">
                        <?php esc_html_e('Buy Now', 'vortex'); ?>
                    </button>
                <?php elseif ($availability === 'sold') : ?>
                    <div class="vortex-sold-notice">
                        <?php esc_html_e('This artwork has been sold', 'vortex'); ?>
                    </div>
                    
                    <button class="vortex-similar-works-btn" data-artist-id="<?php echo esc_attr($artist_id); ?>">
                        <?php esc_html_e('View Similar Works', 'vortex'); ?>
                    </button>
                <?php elseif ($availability === 'reserved') : ?>
                    <div class="vortex-reserved-notice">
                        <?php esc_html_e('This artwork is currently reserved', 'vortex'); ?>
                    </div>
                    
                    <button class="vortex-inquiry-btn" data-artwork-id="<?php echo esc_attr($artwork_id); ?>">
                        <?php esc_html_e('Make an Inquiry', 'vortex'); ?>
                    </button>
                <?php endif; ?>
                
                <button class="vortex-wishlist-btn" data-artwork-id="<?php echo esc_attr($artwork_id); ?>">
                    <span class="wishlist-icon"></span>
                    <span class="wishlist-text"><?php esc_html_e('Add to Wishlist', 'vortex'); ?></span>
                </button>
            </div>
            
            <div class="vortex-artwork-share">
                <span class="share-label"><?php esc_html_e('Share:', 'vortex'); ?></span>
                <div class="vortex-share-buttons">
                    <a href="https://facebook.com/sharer/sharer.php?u=<?php echo esc_url(get_permalink()); ?>" target="_blank" class="vortex-share-button facebook" aria-label="<?php esc_attr_e('Share on Facebook', 'vortex'); ?>">
                        <span class="share-icon facebook-icon"></span>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo esc_url(get_permalink()); ?>&text=<?php echo esc_attr(get_the_title()); ?>" target="_blank" class="vortex-share-button twitter" aria-label="<?php esc_attr_e('Share on Twitter', 'vortex'); ?>">
                        <span class="share-icon twitter-icon"></span>
                    </a>
                    <a href="https://pinterest.com/pin/create/button/?url=<?php echo esc_url(get_permalink()); ?>&media=<?php echo esc_url(get_the_post_thumbnail_url($artwork_id, 'large')); ?>&description=<?php echo esc_attr(get_the_title()); ?>" target="_blank" class="vortex-share-button pinterest" aria-label="<?php esc_attr_e('Pin on Pinterest', 'vortex'); ?>">
                        <span class="share-icon pinterest-icon"></span>
                    </a>
                    <a href="mailto:?subject=<?php echo esc_attr(get_the_title()); ?>&body=<?php echo esc_attr(sprintf(__('Check out this artwork: %s', 'vortex'), get_permalink())); ?>" class="vortex-share-button email" aria-label="<?php esc_attr_e('Share via Email', 'vortex'); ?>">
                        <span class="share-icon email-icon"></span>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="vortex-artwork-description">
            <h2><?php esc_html_e('About This Artwork', 'vortex'); ?></h2>
            <?php 
            if (has_excerpt() || !empty(get_the_content())) {
                the_content();
            } else {
                echo '<p class="vortex-no-description">' . esc_html__('The artist has not provided a description for this artwork.', 'vortex') . '</p>';
            }
            ?>
            
            <?php if (!empty($artwork_insights) && !empty($artwork_insights['ai_enhanced_description'])) : ?>
                <div class="vortex-ai-enhanced-description">
                    <h3><?php esc_html_e('CLOE\'s Artistic Insight', 'vortex'); ?></h3>
                    <div class="vortex-ai-card">
                        <div class="vortex-ai-avatar">
                            <img src="<?php echo esc_url(VORTEX_PLUGIN_URL . 'assets/images/cloe-avatar.png'); ?>" alt="CLOE" />
                        </div>
                        <div class="vortex-ai-content">
                            <?php echo wp_kses_post($artwork_insights['ai_enhanced_description']); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($seed_art_analysis)) : ?>
            <div class="vortex-seed-art-analysis">
                <h2><?php esc_html_e('HURAII Seed Art Analysis', 'vortex'); ?></h2>
                <div class="vortex-ai-card">
                    <div class="vortex-ai-avatar">
                        <img src="<?php echo esc_url(VORTEX_PLUGIN_URL . 'assets/images/huraii-avatar.png'); ?>" alt="HURAII" />
                    </div>
                    <div class="vortex-ai-content">
                        <p class="vortex-huraii-greeting"><?php echo esc_html($seed_art_analysis['greeting']); ?></p>
                        
                        <div class="vortex-seed-components">
                            <?php if (!empty($seed_art_analysis['sacred_geometry'])) : ?>
                                <div class="vortex-seed-component">
                                    <h4><?php esc_html_e('Sacred Geometry', 'vortex'); ?></h4>
                                    <p><?php echo esc_html($seed_art_analysis['sacred_geometry']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($seed_art_analysis['color_weight'])) : ?>
                                <div class="vortex-seed-component">
                                    <h4><?php esc_html_e('Color Weight', 'vortex'); ?></h4>
                                    <p><?php echo esc_html($seed_art_analysis['color_weight']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($seed_art_analysis['light_shadow'])) : ?>
                                <div class="vortex-seed-component">
                                    <h4><?php esc_html_e('Light & Shadow', 'vortex'); ?></h4>
                                    <p><?php echo esc_html($seed_art_analysis['light_shadow']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($seed_art_analysis['texture'])) : ?>
                                <div class="vortex-seed-component">
                                    <h4><?php esc_html_e('Texture', 'vortex'); ?></h4>
                                    <p><?php echo esc_html($seed_art_analysis['texture']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($seed_art_analysis['perspective'])) : ?>
                                <div class="vortex-seed-component">
                                    <h4><?php esc_html_e('Perspective', 'vortex'); ?></h4>
                                    <p><?php echo esc_html($seed_art_analysis['perspective']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($seed_art_analysis['artwork_size'])) : ?>
                                <div class="vortex-seed-component">
                                    <h4><?php esc_html_e('Artwork Size', 'vortex'); ?></h4>
                                    <p><?php echo esc_html($seed_art_analysis['artwork_size']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($seed_art_analysis['movement_layering'])) : ?>
                                <div class="vortex-seed-component">
                                    <h4><?php esc_html_e('Movement & Layering', 'vortex'); ?></h4>
                                    <p><?php echo esc_html($seed_art_analysis['movement_layering']); ?></p>
                                    
                                    <?php if (!empty($seed_art_analysis['layer_count'])) : ?>
                                        <div class="vortex-layer-detail">
                                            <span class="layer-count-label"><?php esc_html_e('Layer Count:', 'vortex'); ?></span>
                                            <span class="layer-count-value"><?php echo esc_html($seed_art_analysis['layer_count']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($seed_art_analysis['efficiency_analysis'])) : ?>
                                        <div class="vortex-efficiency-analysis">
                                            <h5><?php esc_html_e('Efficiency Analysis', 'vortex'); ?></h5>
                                            <p><?php echo esc_html($seed_art_analysis['efficiency_analysis']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($seed_art_analysis['create_similar_url'])) : ?>
                            <div class="vortex-create-similar">
                                <a href="<?php echo esc_url($seed_art_analysis['create_similar_url']); ?>" class="vortex-create-btn">
                                    <span class="create-icon"></span>
                                    <?php esc_html_e('Create Similar with HURAII', 'vortex'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($artwork_valuation) && !empty($artwork_valuation['market_analysis'])) : ?>
            <div class="vortex-market-valuation">
                <h2><?php esc_html_e('Market Analysis', 'vortex'); ?></h2>
                <div class="vortex-ai-card">
                    <div class="vortex-ai-avatar">
                        <img src="<?php echo esc_url(VORTEX_PLUGIN_URL . 'assets/images/business-strategist-avatar.png'); ?>" alt="Business Strategist" />
                    </div>
                    <div class="vortex-ai-content">
                        <div class="vortex-valuation-summary">
                            <?php echo wp_kses_post($artwork_valuation['market_analysis']); ?>
                        </div>
                        
                        <?php if (!empty($artwork_valuation['investment_potential'])) : ?>
                            <div class="vortex-investment-potential">
                                <h4><?php esc_html_e('Investment Potential', 'vortex'); ?></h4>
                                
                                <div class="vortex-potential-rating">
                                    <div class="vortex-rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                                            <span class="vortex-star <?php echo ($i <= $artwork_valuation['investment_rating']) ? 'filled' : ''; ?>"></span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="vortex-rating-text"><?php echo esc_html($artwork_valuation['investment_potential']); ?></span>
                                </div>
                                
                                <p class="vortex-investment-notes"><?php echo esc_html($artwork_valuation['investment_notes']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($artwork_valuation['price_trends'])) : ?>
                            <div class="vortex-price-trends">
                                <h4><?php esc_html_e('Price Trend Analysis', 'vortex'); ?></h4>
                                <div class="vortex-trends-chart">
                                    <div id="vortexPriceTrendsChart" class="vortex-chart" data-trends="<?php echo esc_attr(json_encode($artwork_valuation['price_trends'])); ?>"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="vortex-similar-artworks">
            <h2><?php esc_html_e('You May Also Like', 'vortex'); ?></h2>
            
            <?php
            // Get CLOE's personalized recommendations
            $similar_artworks = apply_filters('vortex_ai_get_cloe_recommendations', array(
                'artwork_id' => $artwork_id,
                'user_id' => $user_id ?: 0,
                'limit' => 3
            ));
            
            if (!empty($similar_artworks) && !empty($similar_artworks['items'])) : 
            ?>
                <div class="vortex-ai-card">
                    <div class="vortex-ai-avatar">
                        <img src="<?php echo esc_url(VORTEX_PLUGIN_URL . 'assets/images/cloe-avatar.png'); ?>" alt="CLOE" />
                    </div>
                    <div class="vortex-ai-content">
                        <p class="vortex-cloe-recommendation-intro">
                            <?php echo esc_html($similar_artworks['introduction'] ?? __('Based on your interests and this artwork, you might also like these pieces:', 'vortex')); ?>
                        </p>
                    </div>
                </div>
                
                <div class="vortex-similar-grid">
                    <?php foreach ($similar_artworks['items'] as $similar_artwork) : ?>
                        <div class="vortex-similar-item">
                            <a href="<?php echo esc_url($similar_artwork['permalink']); ?>" class="vortex-similar-link">
                                <div class="vortex-similar-image">
                                    <img src="<?php echo esc_url($similar_artwork['image']); ?>" alt="<?php echo esc_attr($similar_artwork['title']); ?>">
                                </div>
                                <div class="vortex-similar-details">
                                    <h3 class="vortex-similar-title"><?php echo esc_html($similar_artwork['title']); ?></h3>
                                    <p class="vortex-similar-artist"><?php echo esc_html($similar_artwork['artist']); ?></p>
                                    <div class="vortex-similar-price"><?php echo esc_html(vortex_format_price($similar_artwork['price'])); ?></div>
                                </div>
                            </a>
                            
                            <?php if (!empty($similar_artwork['match_reason'])) : ?>
                                <div class="vortex-match-reason">
                                    <span class="match-icon"></span>
                                    <span class="match-text"><?php echo esc_html($similar_artwork['match_reason']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="vortex-no-recommendations">
                    <p><?php esc_html_e('No similar artworks are currently available. Browse our collection to discover more art.', 'vortex'); ?></p>
                    <a href="<?php echo esc_url(get_post_type_archive_link('vortex-artwork')); ?>" class="vortex-browse-btn">
                        <?php esc_html_e('Browse All Artworks', 'vortex'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</article>

<div id="vortexArtworkInquiryModal" class="vortex-modal" style="display:none;">
    <div class="vortex-modal-content">
        <span class="vortex-modal-close">&times;</span>
        <h2><?php esc_html_e('Inquire About This Artwork', 'vortex'); ?></h2>
        
        <form id="vortexArtworkInquiryForm" class="vortex-inquiry-form">
            <input type="hidden" name="artwork_id" value="<?php echo esc_attr($artwork_id); ?>">
            <input type="hidden" name="artwork_title" value="<?php echo esc_attr(get_the_title()); ?>">
            
            <div class="vortex-form-field">
                <label for="inquiry_name"><?php esc_html_e('Your Name', 'vortex'); ?> *</label>
                <input type="text" id="inquiry_name" name="name" required>
            </div>
            
            <div class="vortex-form-field">
                <label for="inquiry_email"><?php esc_html_e('Your Email', 'vortex'); ?> *</label>
                <input type="email" id="inquiry_email" name="email" required>
            </div>
            
            <div class="vortex-form-field">
                <label for="inquiry_phone"><?php esc_html_e('Phone Number', 'vortex'); ?></label>
                <input type="tel" id="inquiry_phone" name="phone">
            </div>
            
            <div class="vortex-form-field">
                <label for="inquiry_message"><?php esc_html_e('Your Message', 'vortex'); ?> *</label>
                <textarea id="inquiry_message" name="message" rows="4" required><?php echo esc_textarea(sprintf(__('I am interested in learning more about "%s".', 'vortex'), get_the_title())); ?></textarea>
            </div>
            
            <div class="vortex-form-field vortex-checkbox-field">
                <input type="checkbox" id="inquiry_privacy" name="privacy_consent" required>
                <label for="inquiry_privacy"><?php esc_html_e('I agree to the privacy policy and consent to being contacted about this artwork.', 'vortex'); ?> *</label>
            </div>
            
            <div class="vortex-form-actions">
                <button type="submit" class="vortex-submit-inquiry"><?php esc_html_e('Send Inquiry', 'vortex'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof VortexAISystem !== 'undefined') {
        // Initialize HURAII for Seed Art analysis
        try {
            VortexAISystem.initArtworkAnalysis(<?php echo json_encode($artwork_id); ?>, {
                agent: 'HURAII',
                analysisType: 'seed_art',
                components: [
                    'sacred_geometry',
                    'color_weight',
                    'light_shadow', 
                    'texture',
                    'perspective',
                    'artwork_size',
                    'movement_layering'
                ],
                layerAnalysisEnabled: true,
                efficiencyEnabled: true,
                preserveUserPrivacy: true
            });
        } catch (e) {
            console.error('Error initializing HURAII analysis:', e);
        }
        
        // Initialize CLOE for artwork insights
        try {
            VortexAISystem.initArtworkAnalysis(<?php echo json_encode($artwork_id); ?>, {
                agent: 'CLOE',
                analysisType: 'artistic_insight',
                personalized: true,
                userId: <?php echo json_encode($user_id ?: 0); ?>,
                preserveUserPrivacy: true
            });
        } catch (e) {
            console.error('Error initializing CLOE analysis:', e);
        }
        
        // Initialize BusinessStrategist for market valuation
        try {
            VortexAISystem.initArtworkAnalysis(<?php echo json_encode($artwork_id); ?>, {
                agent: 'BusinessStrategist',
                analysisType: 'market_valuation',
                priceTrendEnabled: true,
                investmentAnalysisEnabled: true,
                preserveUserPrivacy: true
            });
        } catch (e) {
            console.error('Error initializing BusinessStrategist analysis:', e);
        }
        
        // Track user interactions
        const trackInteraction = function(interactionType, data = {}) {
            try {
                VortexAISystem.trackInteraction({
                    entityType: 'artwork',
                    entityId: <?php echo json_encode($artwork_id); ?>,
                    action: interactionType,
                    timestamp: Date.now(),
                    ...data
                });
            } catch (e) {
                console.error('Error tracking interaction:', e);
            }
        };
        
        // Track hotspot interactions
        document.querySelectorAll('.vortex-hotspot').forEach(function(hotspot) {
            hotspot.addEventListener('click', function() {
                trackInteraction('hotspot_click', {
                    hotspotIndex: this.dataset.index,
                    hotspotTitle: this.dataset.title
                });
            });
        });
        
        // Render price trend chart if data exists
        const chartContainer = document.getElementById('vortexPriceTrendsChart');
        if (chartContainer && chartContainer.dataset.trends) {
            try {
                const trendsData = JSON.parse(chartContainer.dataset.trends);
                VortexAISystem.renderPriceTrendChart(chartContainer, trendsData);
            } catch (e) {
                console.error('Error rendering price trend chart:', e);
                chartContainer.innerHTML = '<p class="vortex-chart-error"><?php esc_html_e('Unable to load chart data.', 'vortex'); ?></p>';
            }
        }
        
        // Track wishlist interactions
        const wishlistBtn = document.querySelector('.vortex-wishlist-btn');
        if (wishlistBtn) {
            wishlistBtn.addEventListener('click', function() {
                trackInteraction('wishlist_add');
                
                // Toggle wishlist UI state
                this.classList.toggle('in-wishlist');
                const wishlistText = this.querySelector('.wishlist-text');
                if (this.classList.contains('in-wishlist')) {
                    wishlistText.textContent = '<?php esc_html_e('Added to Wishlist', 'vortex'); ?>';
                } else {
                    wishlistText.textContent = '<?php esc_html_e('Add to Wishlist', 'vortex'); ?>';
                }
                
                // Make AJAX call to update wishlist
                fetch(vortex_ajax.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'vortex_toggle_wishlist',
                        'artwork_id': <?php echo json_encode($artwork_id); ?>,
                        'nonce': vortex_ajax.nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Error updating wishlist:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        }
        
        // Image zoom functionality
        const mainImage = document.querySelector('.vortex-artwork-main-image img');
        const zoomIn = document.querySelector('.vortex-zoom-in');
        const zoomOut = document.querySelector('.vortex-zoom-out');
        const zoomReset = document.querySelector('.vortex-zoom-reset');
        
        if (mainImage && zoomIn && zoomOut && zoomReset) {
            let scale = 1;
            let panning = false;
            let pointX = 0;
            let pointY = 0;
            let start = { x: 0, y: 0 };
            
            function setTransform() {
                mainImage.style.transform = `translate(${pointX}px, ${pointY}px) scale(${scale})`;
            }
            
            zoomIn.addEventListener('click', function() {
                scale = Math.min(scale + 0.25, 3);
                setTransform();
                trackInteraction('image_zoom_in', { zoomLevel: scale });
            });
            
            zoomOut.addEventListener('click', function() {
                scale = Math.max(scale - 0.25, 1);
                pointX = 0;
                pointY = 0;
                setTransform();
                trackInteraction('image_zoom_out', { zoomLevel: scale });
            });
            
            zoomReset.addEventListener('click', function() {
                scale = 1;
                pointX = 0;
                pointY = 0;
                setTransform();
                trackInteraction('image_zoom_reset');
            });
        }
    }
});
</script> 