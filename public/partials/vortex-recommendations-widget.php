<?php
/**
 * Template for the Recommendations Widget
 * Displays personalized recommendations based on user preferences and behavior
 */

// Get current user data
$user_id = get_current_user_id();
if (!$user_id) {
    return;
}

// Get recommendations from Cloe
$cloe = VORTEX_Cloe::get_instance();
$recommendations = $cloe->get_personalized_recommendations($user_id);

// Group recommendations by category
$categorized_recommendations = array();
foreach ($recommendations as $rec) {
    $category = $rec['category'];
    if (!isset($categorized_recommendations[$category])) {
        $categorized_recommendations[$category] = array();
    }
    $categorized_recommendations[$category][] = $rec;
}
?>

<div class="vortex-cloe-widget vortex-recommendations-widget">
    <div class="vortex-cloe-widget-header">
        <i class="vortex-cloe-widget-icon fas fa-lightbulb"></i>
        <h2 class="vortex-cloe-widget-title"><?php esc_html_e('Personalized Recommendations', 'vortex'); ?></h2>
    </div>

    <div class="vortex-recommendations-content">
        <?php if (empty($recommendations)) : ?>
            <div class="vortex-recommendations-empty">
                <i class="fas fa-info-circle"></i>
                <p><?php esc_html_e('No recommendations available yet. Complete your profile to get personalized suggestions.', 'vortex'); ?></p>
            </div>
        <?php else : ?>
            <?php foreach ($categorized_recommendations as $category => $items) : ?>
                <div class="vortex-recommendations-category">
                    <h3 class="vortex-recommendations-category-title">
                        <i class="<?php echo esc_attr($items[0]['category_icon']); ?>"></i>
                        <?php echo esc_html($category); ?>
                    </h3>

                    <div class="vortex-recommendations-grid">
                        <?php foreach ($items as $rec) : ?>
                            <div class="vortex-recommendation-card" data-id="<?php echo esc_attr($rec['id']); ?>">
                                <?php if (!empty($rec['image'])) : ?>
                                    <div class="vortex-recommendation-image-wrapper">
                                        <img src="<?php echo esc_url($rec['image']); ?>" 
                                             alt="<?php echo esc_attr($rec['title']); ?>"
                                             class="vortex-recommendation-image">
                                    </div>
                                <?php endif; ?>

                                <div class="vortex-recommendation-content">
                                    <h4 class="vortex-recommendation-title">
                                        <?php echo esc_html($rec['title']); ?>
                                    </h4>

                                    <p class="vortex-recommendation-description">
                                        <?php echo esc_html($rec['description']); ?>
                                    </p>

                                    <?php if (!empty($rec['tags'])) : ?>
                                        <div class="vortex-recommendation-tags">
                                            <?php foreach ($rec['tags'] as $tag) : ?>
                                                <span class="vortex-recommendation-tag">
                                                    <?php echo esc_html($tag); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="vortex-recommendation-meta">
                                        <?php if (!empty($rec['rating'])) : ?>
                                            <span class="vortex-recommendation-rating">
                                                <i class="fas fa-star"></i>
                                                <?php echo esc_html($rec['rating']); ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if (!empty($rec['engagement'])) : ?>
                                            <span class="vortex-recommendation-engagement">
                                                <i class="fas fa-chart-line"></i>
                                                <?php echo esc_html($rec['engagement']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($rec['action_url'])) : ?>
                                        <a href="<?php echo esc_url($rec['action_url']); ?>" 
                                           class="vortex-button vortex-button-secondary">
                                            <?php echo esc_html($rec['action_label']); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <button class="vortex-button vortex-refresh-recommendations">
                <i class="fas fa-sync-alt"></i>
                <?php esc_html_e('Refresh Recommendations', 'vortex'); ?>
            </button>
        <?php endif; ?>
    </div>
</div>

<style>
.vortex-recommendations-empty {
    text-align: center;
    padding: 40px 20px;
    color: var(--vortex-text-muted, #6c757d);
}

.vortex-recommendations-empty i {
    font-size: 48px;
    margin-bottom: 15px;
    color: var(--vortex-primary-light, #e7f1ff);
}

.vortex-recommendations-category {
    margin-bottom: 30px;
}

.vortex-recommendations-category-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    color: var(--vortex-text, #212529);
    font-size: 1.1rem;
}

.vortex-recommendations-category-title i {
    color: var(--vortex-primary, #007bff);
}

.vortex-recommendation-image-wrapper {
    position: relative;
    padding-top: 56.25%; /* 16:9 aspect ratio */
    overflow: hidden;
    border-radius: 6px;
    margin-bottom: 15px;
}

.vortex-recommendation-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.vortex-recommendation-card:hover .vortex-recommendation-image {
    transform: scale(1.05);
}

.vortex-recommendation-content {
    padding: 0 5px;
}

.vortex-recommendation-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin: 10px 0;
}

.vortex-recommendation-tag {
    font-size: 0.75rem;
    padding: 4px 8px;
    background: var(--vortex-background-alt, #f8f9fa);
    border-radius: 4px;
    color: var(--vortex-text-muted, #6c757d);
}

.vortex-recommendation-meta {
    display: flex;
    align-items: center;
    gap: 15px;
    margin: 15px 0;
    font-size: 0.875rem;
    color: var(--vortex-text-muted, #6c757d);
}

.vortex-recommendation-meta i {
    margin-right: 5px;
}

.vortex-recommendation-card.vortex-recommendation-clicked {
    animation: vortex-pulse 0.5s ease;
}

@keyframes vortex-pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.02);
    }
    100% {
        transform: scale(1);
    }
}

@media (max-width: 768px) {
    .vortex-recommendations-grid {
        grid-template-columns: 1fr;
    }
    
    .vortex-recommendation-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}
</style> 