<?php
/**
 * Template for displaying a single artwork
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/marketplace
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Extract attributes to variables for convenience
$show_artist = filter_var($atts['show_artist'], FILTER_VALIDATE_BOOLEAN);
$show_price = filter_var($atts['show_price'], FILTER_VALIDATE_BOOLEAN);
$show_purchase = filter_var($atts['show_purchase'], FILTER_VALIDATE_BOOLEAN);
$show_details = filter_var($atts['show_details'], FILTER_VALIDATE_BOOLEAN);
?>

<div class="vortex-single-artwork" id="artwork-<?php echo esc_attr($artwork['id']); ?>">
    <div class="vortex-artwork-main">
        <div class="vortex-artwork-image-container">
            <img src="<?php echo esc_url($artwork['image_full']); ?>" 
                 alt="<?php echo esc_attr($artwork['title']); ?>" 
                 class="vortex-artwork-main-image" />
            
            <?php if ($artwork['is_tokenized']): ?>
                <div class="vortex-artwork-token-badge" title="<?php esc_attr_e('This artwork is tokenized on the blockchain', 'vortex-ai-marketplace'); ?>">
                    <span class="vortex-token-icon">🔗</span>
                    <?php esc_html_e('Tokenized', 'vortex-ai-marketplace'); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="vortex-artwork-details-container">
            <h1 class="vortex-artwork-title"><?php echo esc_html($artwork['title']); ?></h1>
            
            <?php if ($show_artist && !empty($artwork['artist'])): ?>
                <div class="vortex-artwork-artist-info">
                    <div class="vortex-artist-avatar">
                        <img src="<?php echo esc_url($artwork['artist']['avatar']); ?>" 
                             alt="<?php echo esc_attr($artwork['artist']['name']); ?>" />
                    </div>
                    <div class="vortex-artist-details">
                        <span class="vortex-by-label"><?php esc_html_e('By', 'vortex-ai-marketplace'); ?></span>
                        <a href="<?php echo esc_url($artwork['artist']['url']); ?>" class="vortex-artist-name">
                            <?php echo esc_html($artwork['artist']['name']); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($show_price && !empty($artwork['price'])): ?>
                <div class="vortex-artwork-price-container">
                    <div class="vortex-artwork-price">
                        <span class="vortex-price-value"><?php echo esc_html(number_format($artwork['price'], 2)); ?></span>
                        <span class="vortex-price-currency">TOLA</span>
                    </div>
                    
                    <?php if ($show_purchase): ?>
                        <button class="vortex-purchase-button" data-artwork-id="<?php echo esc_attr($artwork['id']); ?>">
                            <?php esc_html_e('Purchase Now', 'vortex-ai-marketplace'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($show_details): ?>
                <div class="vortex-artwork-metadata">
                    <?php if (!empty($artwork['year'])): ?>
                        <div class="vortex-metadata-item">
                            <span class="vortex-metadata-label"><?php esc_html_e('Year:', 'vortex-ai-marketplace'); ?></span>
                            <span class="vortex-metadata-value"><?php echo esc_html($artwork['year']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($artwork['medium'])): ?>
                        <div class="vortex-metadata-item">
                            <span class="vortex-metadata-label"><?php esc_html_e('Medium:', 'vortex-ai-marketplace'); ?></span>
                            <span class="vortex-metadata-value"><?php echo esc_html($artwork['medium']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($artwork['dimensions'])): ?>
                        <div class="vortex-metadata-item">
                            <span class="vortex-metadata-label"><?php esc_html_e('Dimensions:', 'vortex-ai-marketplace'); ?></span>
                            <span class="vortex-metadata-value"><?php echo esc_html($artwork['dimensions']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($artwork['ai_engine'])): ?>
                        <div class="vortex-metadata-item">
                            <span class="vortex-metadata-label"><?php esc_html_e('AI Engine:', 'vortex-ai-marketplace'); ?></span>
                            <span class="vortex-metadata-value"><?php echo esc_html($artwork['ai_engine']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($artwork['categories']) && is_array($artwork['categories'])): ?>
                        <div class="vortex-metadata-item">
                            <span class="vortex-metadata-label"><?php esc_html_e('Categories:', 'vortex-ai-marketplace'); ?></span>
                            <span class="vortex-metadata-value">
                                <?php 
                                $category_links = array();
                                foreach ($artwork['categories'] as $category) {
                                    $category_links[] = '<a href="' . esc_url(get_term_link($category)) . '">' . esc_html($category->name) . '</a>';
                                }
                                echo implode(', ', $category_links);
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($artwork['styles']) && is_array($artwork['styles'])): ?>
                        <div class="vortex-metadata-item">
                            <span class="vortex-metadata-label"><?php esc_html_e('Styles:', 'vortex-ai-marketplace'); ?></span>
                            <span class="vortex-metadata-value">
                                <?php 
                                $style_links = array();
                                foreach ($artwork['styles'] as $style) {
                                    $style_links[] = '<a href="' . esc_url(get_term_link($style)) . '">' . esc_html($style->name) . '</a>';
                                }
                                echo implode(', ', $style_links);
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($artwork['description'])): ?>
                    <div class="vortex-artwork-description">
                        <h3><?php esc_html_e('Description', 'vortex-ai-marketplace'); ?></h3>
                        <div class="vortex-description-content">
                            <?php echo wp_kses_post($artwork['description']); ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="vortex-artwork-actions">
                <button class="vortex-like-button" data-artwork-id="<?php echo esc_attr($artwork['id']); ?>">
                    <span class="vortex-like-icon">❤</span>
                    <span class="vortex-like-count"><?php echo esc_html($artwork['likes']); ?></span>
                </button>
                
                <button class="vortex-share-button" data-artwork-id="<?php echo esc_attr($artwork['id']); ?>">
                    <span class="vortex-share-icon">🔗</span>
                    <?php esc_html_e('Share', 'vortex-ai-marketplace'); ?>
                </button>
                
                <?php if (is_user_logged_in()): ?>
                    <button class="vortex-save-button" data-artwork-id="<?php echo esc_attr($artwork['id']); ?>">
                        <span class="vortex-save-icon">🔖</span>
                        <?php esc_html_e('Save', 'vortex-ai-marketplace'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div> 