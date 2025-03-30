<?php
/**
 * Template for rendering CLOE market analysis shortcode
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-cloe-analysis">
    <div class="vortex-cloe-header">
        <div class="vortex-cloe-title">
            <h3>
                <span class="vortex-cloe-icon dashicons dashicons-chart-area"></span>
                <?php echo esc_html__('CLOE Market Analysis', 'vortex-ai-marketplace'); ?>
                <?php if (!empty($category)): ?>
                    <span class="vortex-cloe-category">: <?php echo esc_html($category); ?></span>
                <?php endif; ?>
            </h3>
            <p class="vortex-cloe-subtitle"><?php echo esc_html__('Powered by advanced AI market analysis', 'vortex-ai-marketplace'); ?></p>
        </div>
        
        <?php if (!empty($analysis['last_updated'])): ?>
        <div class="vortex-cloe-meta">
            <span class="vortex-cloe-updated">
                <?php echo esc_html__('Last updated:', 'vortex-ai-marketplace'); ?>
                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($analysis['last_updated']))); ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($analysis['trends'])): ?>
    <div class="vortex-cloe-section">
        <h4 class="vortex-cloe-section-title"><?php echo esc_html__('Market Trends', 'vortex-ai-marketplace'); ?></h4>
        
        <div class="vortex-cloe-trends">
            <?php foreach ($analysis['trends'] as $trend): ?>
                <div class="vortex-cloe-trend-item <?php echo esc_attr($trend['direction']); ?>">
                    <div class="vortex-cloe-trend-icon">
                        <span class="dashicons <?php echo esc_attr($trend['direction'] === 'up' ? 'dashicons-arrow-up-alt' : 'dashicons-arrow-down-alt'); ?>"></span>
                    </div>
                    <div class="vortex-cloe-trend-info">
                        <h5 class="vortex-cloe-trend-name"><?php echo esc_html($trend['name']); ?></h5>
                        <p class="vortex-cloe-trend-desc"><?php echo esc_html($trend['description']); ?></p>
                        <div class="vortex-cloe-trend-stat">
                            <?php echo esc_html($trend['change']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($analysis['insights'])): ?>
    <div class="vortex-cloe-section">
        <h4 class="vortex-cloe-section-title"><?php echo esc_html__('Market Insights', 'vortex-ai-marketplace'); ?></h4>
        
        <div class="vortex-cloe-insights">
            <?php foreach ($analysis['insights'] as $insight): ?>
                <div class="vortex-cloe-insight-item">
                    <h5 class="vortex-cloe-insight-title"><?php echo esc_html($insight['title']); ?></h5>
                    <p class="vortex-cloe-insight-text"><?php echo esc_html($insight['description']); ?></p>
                    <?php if (!empty($insight['confidence'])): ?>
                        <div class="vortex-cloe-confidence">
                            <span class="vortex-cloe-confidence-label"><?php echo esc_html__('Confidence:', 'vortex-ai-marketplace'); ?></span>
                            <div class="vortex-cloe-confidence-bar">
                                <div class="vortex-cloe-confidence-fill" style="width: <?php echo esc_attr($insight['confidence']); ?>%"></div>
                            </div>
                            <span class="vortex-cloe-confidence-value"><?php echo esc_html($insight['confidence']); ?>%</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($analysis['recommendations'])): ?>
    <div class="vortex-cloe-section">
        <h4 class="vortex-cloe-section-title"><?php echo esc_html__('Recommendations', 'vortex-ai-marketplace'); ?></h4>
        
        <div class="vortex-cloe-recommendations">
            <?php foreach ($analysis['recommendations'] as $recommendation): ?>
                <div class="vortex-cloe-recommendation-item">
                    <div class="vortex-cloe-recommendation-icon">
                        <span class="dashicons dashicons-lightbulb"></span>
                    </div>
                    <div class="vortex-cloe-recommendation-text">
                        <?php echo esc_html($recommendation); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div> 