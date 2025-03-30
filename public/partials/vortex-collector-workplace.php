<?php
/**
 * Template for the collector-collector workplace interface
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get items for swiping
$items = get_posts(array(
    'post_type' => 'vortex_item',
    'post_status' => 'publish',
    'posts_per_page' => 10
));
?>

<div class="vortex-collector-workplace">
    <h2><?php _e('Collector Workplace', 'vortex-ai-marketplace'); ?></h2>
    
    <?php if (!empty($items)) : ?>
        <div class="vortex-swipe-container">
            <?php foreach ($items as $item) : ?>
                <div class="vortex-swipe-item" data-item-id="<?php echo esc_attr($item->ID); ?>">
                    <div class="vortex-swipe-content">
                        <h3><?php echo esc_html($item->post_title); ?></h3>
                        <p><?php echo wp_kses_post($item->post_content); ?></p>
                        
                        <div class="vortex-swipe-actions">
                            <button class="vortex-swipe-button reject"><?php _e('Reject', 'vortex-ai-marketplace'); ?></button>
                            <button class="vortex-swipe-button accept"><?php _e('Accept', 'vortex-ai-marketplace'); ?></button>
                        </div>
                        
                        <div class="vortex-message" style="display: none;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p><?php _e('No items available for swiping.', 'vortex-ai-marketplace'); ?></p>
    <?php endif; ?>
</div>

<style>
.vortex-collector-workplace {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.vortex-swipe-container {
    position: relative;
    overflow: hidden;
    width: 100%;
    height: 200px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 20px;
}

.vortex-swipe-item {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.vortex-swipe-item.swiped-left {
    transform: translateX(-100%);
}

.vortex-swipe-item.swiped-right {
    transform: translateX(100%);
}

.vortex-swipe-content {
    padding: 20px;
}

.vortex-swipe-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
}

.vortex-swipe-button {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
}

.vortex-swipe-button.reject {
    background: #dc3545;
    color: #fff;
}

.vortex-swipe-button.accept {
    background: #28a745;
    color: #fff;
}

.vortex-message {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
    display: none;
}

.vortex-message-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.vortex-message-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {
    .vortex-swipe-container {
        height: 150px;
    }
}
</style> 