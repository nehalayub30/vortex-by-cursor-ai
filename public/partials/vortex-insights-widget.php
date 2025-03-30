<?php
/**
 * Template for the Insights Widget
 * Displays personalized insights and analytics based on user behavior
 */

// Get current user data
$user_id = get_current_user_id();
if (!$user_id) {
    return;
}

// Get insights from Cloe
$cloe = VORTEX_Cloe::get_instance();
$insights = $cloe->get_user_insights($user_id);

// Group insights by category
$categorized_insights = array();
foreach ($insights as $insight) {
    $category = $insight['category'];
    if (!isset($categorized_insights[$category])) {
        $categorized_insights[$category] = array();
    }
    $categorized_insights[$category][] = $insight;
}
?>

<div class="vortex-cloe-widget vortex-insights-widget">
    <div class="vortex-cloe-widget-header">
        <i class="vortex-cloe-widget-icon fas fa-chart-bar"></i>
        <h2 class="vortex-cloe-widget-title"><?php esc_html_e('Your Insights', 'vortex'); ?></h2>
    </div>

    <div class="vortex-insights-content">
        <?php if (empty($insights)) : ?>
            <div class="vortex-insights-empty">
                <i class="fas fa-chart-line"></i>
                <p><?php esc_html_e('No insights available yet. Start interacting with the marketplace to receive personalized insights.', 'vortex'); ?></p>
            </div>
        <?php else : ?>
            <?php foreach ($categorized_insights as $category => $items) : ?>
                <div class="vortex-insights-category">
                    <h3 class="vortex-insights-category-title">
                        <i class="<?php echo esc_attr($items[0]['category_icon']); ?>"></i>
                        <?php echo esc_html($category); ?>
                    </h3>

                    <div class="vortex-insights-grid">
                        <?php foreach ($items as $insight) : ?>
                            <div class="vortex-insight-card" data-id="<?php echo esc_attr($insight['id']); ?>">
                                <div class="vortex-insight-header">
                                    <i class="vortex-insight-icon <?php echo esc_attr($insight['icon']); ?>"></i>
                                    <h4 class="vortex-insight-title">
                                        <?php echo esc_html($insight['title']); ?>
                                    </h4>
                                </div>

                                <div class="vortex-insight-content">
                                    <?php echo wp_kses_post($insight['content']); ?>
                                </div>

                                <?php if (!empty($insight['metrics'])) : ?>
                                    <div class="vortex-insight-metrics">
                                        <?php foreach ($insight['metrics'] as $metric) : ?>
                                            <div class="vortex-insight-metric">
                                                <div class="vortex-insight-metric-value">
                                                    <?php echo esc_html($metric['value']); ?>
                                                </div>
                                                <div class="vortex-insight-metric-label">
                                                    <?php echo esc_html($metric['label']); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($insight['trend'])) : ?>
                                    <div class="vortex-insight-trend">
                                        <span class="vortex-insight-trend-label">
                                            <?php esc_html_e('Trend:', 'vortex'); ?>
                                        </span>
                                        <span class="vortex-insight-trend-value <?php echo esc_attr($insight['trend']['direction']); ?>">
                                            <i class="fas fa-<?php echo $insight['trend']['direction'] === 'up' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                            <?php echo esc_html($insight['trend']['value']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($insight['recommendations'])) : ?>
                                    <div class="vortex-insight-recommendations">
                                        <h5 class="vortex-insight-recommendations-title">
                                            <?php esc_html_e('Recommendations', 'vortex'); ?>
                                        </h5>
                                        <ul class="vortex-insight-recommendations-list">
                                            <?php foreach ($insight['recommendations'] as $rec) : ?>
                                                <li>
                                                    <i class="fas fa-check-circle"></i>
                                                    <?php echo esc_html($rec); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <button class="vortex-button vortex-refresh-insights">
                <i class="fas fa-sync-alt"></i>
                <?php esc_html_e('Refresh Insights', 'vortex'); ?>
            </button>
        <?php endif; ?>
    </div>
</div>

<style>
.vortex-insights-empty {
    text-align: center;
    padding: 40px 20px;
    color: var(--vortex-text-muted, #6c757d);
}

.vortex-insights-empty i {
    font-size: 48px;
    margin-bottom: 15px;
    color: var(--vortex-primary-light, #e7f1ff);
}

.vortex-insights-category {
    margin-bottom: 30px;
}

.vortex-insights-category-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    color: var(--vortex-text, #212529);
    font-size: 1.1rem;
}

.vortex-insights-category-title i {
    color: var(--vortex-primary, #007bff);
}

.vortex-insight-card {
    background: var(--vortex-background, #ffffff);
    border-radius: 8px;
    border: 1px solid var(--vortex-border-color, #e9ecef);
    padding: 20px;
    transition: all 0.2s ease;
}

.vortex-insight-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.vortex-insight-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.vortex-insight-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--vortex-primary-light, #e7f1ff);
    color: var(--vortex-primary, #007bff);
    border-radius: 6px;
}

.vortex-insight-title {
    margin: 0;
    font-size: 1.1rem;
    color: var(--vortex-text, #212529);
}

.vortex-insight-content {
    color: var(--vortex-text-muted, #6c757d);
    margin-bottom: 20px;
    line-height: 1.5;
}

.vortex-insight-trend {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 15px 0;
    padding: 10px;
    background: var(--vortex-background-alt, #f8f9fa);
    border-radius: 6px;
}

.vortex-insight-trend-label {
    color: var(--vortex-text-muted, #6c757d);
}

.vortex-insight-trend-value {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 500;
}

.vortex-insight-trend-value.up {
    color: var(--vortex-success, #28a745);
}

.vortex-insight-trend-value.down {
    color: var(--vortex-danger, #dc3545);
}

.vortex-insight-recommendations {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--vortex-border-color, #e9ecef);
}

.vortex-insight-recommendations-title {
    margin: 0 0 10px;
    font-size: 1rem;
    color: var(--vortex-text, #212529);
}

.vortex-insight-recommendations-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.vortex-insight-recommendations-list li {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    color: var(--vortex-text-muted, #6c757d);
}

.vortex-insight-recommendations-list li:last-child {
    margin-bottom: 0;
}

.vortex-insight-recommendations-list i {
    color: var(--vortex-success, #28a745);
}

@media (max-width: 768px) {
    .vortex-insights-grid {
        grid-template-columns: 1fr;
    }
    
    .vortex-insight-card {
        padding: 15px;
    }
    
    .vortex-insight-metrics {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style> 