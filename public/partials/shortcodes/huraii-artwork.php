<?php
/**
 * Template for rendering HURAII AI-generated artwork shortcode
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-huraii-artwork">
    <div class="vortex-huraii-image">
        <img src="<?php echo esc_url($artwork['image']); ?>" alt="<?php echo esc_attr($artwork['prompt']); ?>">
    </div>
    
    <div class="vortex-huraii-info">
        <h3 class="vortex-huraii-title">
            <?php echo esc_html__('AI-Generated Artwork', 'vortex-ai-marketplace'); ?>
        </h3>
        
        <div class="vortex-huraii-prompt">
            <span class="vortex-huraii-label"><?php echo esc_html__('Prompt:', 'vortex-ai-marketplace'); ?></span>
            <span class="vortex-huraii-value"><?php echo esc_html($artwork['prompt']); ?></span>
        </div>
        
        <div class="vortex-huraii-metadata">
            <div class="vortex-huraii-style">
                <span class="vortex-huraii-label"><?php echo esc_html__('Style:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-huraii-value"><?php echo esc_html($artwork['style']); ?></span>
            </div>
            
            <div class="vortex-huraii-model">
                <span class="vortex-huraii-label"><?php echo esc_html__('Model:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-huraii-value"><?php echo esc_html($artwork['metadata']['model']); ?></span>
            </div>
            
            <div class="vortex-huraii-created">
                <span class="vortex-huraii-label"><?php echo esc_html__('Created:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-huraii-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($artwork['created']))); ?></span>
            </div>
        </div>
        
        <?php if ($show_actions): ?>
        <div class="vortex-huraii-actions">
            <a href="<?php echo esc_url(add_query_arg('prompt', urlencode($artwork['prompt']), home_url('/create-artwork/'))); ?>" class="vortex-huraii-btn vortex-huraii-customize">
                <?php echo esc_html__('Customize', 'vortex-ai-marketplace'); ?>
            </a>
            
            <a href="<?php echo esc_url(add_query_arg('huraii_id', $artwork['id'], home_url('/list-for-sale/'))); ?>" class="vortex-huraii-btn vortex-huraii-list">
                <?php echo esc_html__('List for Sale', 'vortex-ai-marketplace'); ?>
            </a>
            
            <a href="<?php echo esc_url($artwork['image']); ?>" download class="vortex-huraii-btn vortex-huraii-download">
                <?php echo esc_html__('Download', 'vortex-ai-marketplace'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div> 