<?php
/**
 * Template for rendering the single artwork shortcode
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-single-artwork">
    <div class="vortex-artwork-showcase">
        <div class="vortex-artwork-image-large">
            <img src="<?php echo esc_url($artwork['image']); ?>" alt="<?php echo esc_attr($artwork['title']); ?>" class="vortex-lightbox-trigger">
        </div>
        
        <?php if (!empty($artwork['gallery'])): ?>
        <div class="vortex-artwork-thumbnails">
            <?php foreach ($artwork['gallery'] as $gallery_image): ?>
                <div class="vortex-thumbnail">
                    <img src="<?php echo esc_url($gallery_image['thumb']); ?>" data-full="<?php echo esc_url($gallery_image['full']); ?>" alt="<?php echo esc_attr($artwork['title']); ?>" class="vortex-thumbnail-img">
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="vortex-artwork-info">
        <h2 class="vortex-artwork-title"><?php echo esc_html($artwork['title']); ?></h2>
        
        <?php if ($show_artist && !empty($artwork['artist'])): ?>
        <div class="vortex-artwork-artist">
            <span><?php echo esc_html__('Artist:', 'vortex-ai-marketplace'); ?></span>
            <a href="<?php echo esc_url(get_permalink($artwork['artist']['id'])); ?>"><?php echo esc_html($artwork['artist']['name']); ?></a>
        </div>
        <?php endif; ?>
        
        <div class="vortex-artwork-description">
            <?php echo wp_kses_post($artwork['description']); ?>
        </div>
        
        <div class="vortex-artwork-meta">
            <div class="vortex-artwork-category">
                <span><?php echo esc_html__('Category:', 'vortex-ai-marketplace'); ?></span>
                <?php echo esc_html($artwork['category']); ?>
            </div>
            
            <div class="vortex-artwork-style">
                <span><?php echo esc_html__('Style:', 'vortex-ai-marketplace'); ?></span>
                <?php echo esc_html($artwork['style']); ?>
            </div>
            
            <div class="vortex-artwork-dimensions">
                <span><?php echo esc_html__('Dimensions:', 'vortex-ai-marketplace'); ?></span>
                <?php echo esc_html($artwork['dimensions']); ?>
            </div>
            
            <div class="vortex-artwork-medium">
                <span><?php echo esc_html__('Medium:', 'vortex-ai-marketplace'); ?></span>
                <?php echo esc_html($artwork['medium']); ?>
            </div>
            
            <?php if (!empty($artwork['nft'])): ?>
            <div class="vortex-artwork-nft">
                <span><?php echo esc_html__('NFT:', 'vortex-ai-marketplace'); ?></span>
                <a href="<?php echo esc_url($artwork['nft']['link']); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html__('View on Blockchain', 'vortex-ai-marketplace'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($show_price && !empty($artwork['price_formatted'])): ?>
        <div class="vortex-artwork-price">
            <span><?php echo esc_html__('Price:', 'vortex-ai-marketplace'); ?></span>
            <span class="vortex-price"><?php echo esc_html($artwork['price_formatted']); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($show_purchase): ?>
        <div class="vortex-artwork-actions">
            <a href="<?php echo esc_url(add_query_arg('add-to-cart', $artwork['id'], wc_get_cart_url())); ?>" class="vortex-add-to-cart-btn">
                <?php echo esc_html__('Add to Cart', 'vortex-ai-marketplace'); ?>
            </a>
            
            <button class="vortex-wishlist-btn" data-artwork-id="<?php echo esc_attr($artwork['id']); ?>">
                <?php echo esc_html__('Add to Wishlist', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
        <?php endif; ?>
    </div>
</div> 