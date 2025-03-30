<?php
/**
 * Template for rendering the marketplace shortcode
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-marketplace">
    <?php if (empty($artworks)): ?>
        <div class="vortex-no-results">
            <p><?php echo esc_html__('No artworks found matching your criteria.', 'vortex-ai-marketplace'); ?></p>
        </div>
    <?php else: ?>
        <div class="vortex-artwork-grid">
            <?php foreach ($artworks as $artwork): ?>
                <div class="vortex-artwork-item">
                    <div class="vortex-artwork-image">
                        <a href="<?php echo esc_url(get_permalink($artwork['id'])); ?>">
                            <img src="<?php echo esc_url($artwork['image']); ?>" alt="<?php echo esc_attr($artwork['title']); ?>" loading="lazy">
                            <?php if ($artwork['featured']): ?>
                                <span class="vortex-featured-badge"><?php echo esc_html__('Featured', 'vortex-ai-marketplace'); ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    
                    <div class="vortex-artwork-details">
                        <h3 class="vortex-artwork-title">
                            <a href="<?php echo esc_url(get_permalink($artwork['id'])); ?>"><?php echo esc_html($artwork['title']); ?></a>
                        </h3>
                        
                        <div class="vortex-artwork-artist">
                            <?php echo esc_html__('By', 'vortex-ai-marketplace'); ?> 
                            <a href="<?php echo esc_url(get_permalink($artwork['artist']['id'])); ?>"><?php echo esc_html($artwork['artist']['name']); ?></a>
                        </div>
                        
                        <div class="vortex-artwork-price">
                            <?php echo esc_html($artwork['price_formatted']); ?>
                        </div>
                        
                        <div class="vortex-artwork-actions">
                            <a href="<?php echo esc_url(add_query_arg('add-to-cart', $artwork['id'], wc_get_cart_url())); ?>" class="vortex-add-to-cart-btn">
                                <?php echo esc_html__('Add to Cart', 'vortex-ai-marketplace'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div> 