<?php
/**
 * Template for the Learning Resources Widget
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get user data
$user_id = get_current_user_id();
$learning_resources = get_user_meta($user_id, 'vortex_learning_resources', true);

// If no resources exist, get personalized resources from Business Strategist
if (!$learning_resources) {
    $business_strategist = VORTEX_BusinessStrategist::get_instance();
    $learning_resources = $business_strategist->get_personalized_learning_resources($user_id);
    update_user_meta($user_id, 'vortex_learning_resources', $learning_resources);
}

// Group resources by category
$resources_by_category = array();
foreach ($learning_resources as $resource) {
    $category = $resource['category'];
    if (!isset($resources_by_category[$category])) {
        $resources_by_category[$category] = array();
    }
    $resources_by_category[$category][] = $resource;
}
?>

<div class="vortex-business-widget vortex-resources-widget">
    <div class="vortex-business-widget-header">
        <span class="vortex-business-widget-icon">
            <i class="fas fa-graduation-cap"></i>
        </span>
        <h3 class="vortex-business-widget-title">
            <?php esc_html_e('Learning Resources', 'vortex-ai-marketplace'); ?>
        </h3>
    </div>

    <div class="vortex-resources-categories">
        <?php foreach ($resources_by_category as $category => $resources) : ?>
            <div class="vortex-resources-category">
                <h4 class="vortex-resources-category-title">
                    <?php echo esc_html($category); ?>
                </h4>
                <div class="vortex-resources-grid">
                    <?php foreach ($resources as $resource) : ?>
                        <div class="vortex-resource">
                            <div class="vortex-resource-icon">
                                <?php
                                switch ($resource['type']) {
                                    case 'video':
                                        echo '<i class="fas fa-play-circle"></i>';
                                        break;
                                    case 'article':
                                        echo '<i class="fas fa-file-alt"></i>';
                                        break;
                                    case 'course':
                                        echo '<i class="fas fa-book"></i>';
                                        break;
                                    case 'workshop':
                                        echo '<i class="fas fa-chalkboard-teacher"></i>';
                                        break;
                                    default:
                                        echo '<i class="fas fa-info-circle"></i>';
                                }
                                ?>
                            </div>
                            <div class="vortex-resource-content">
                                <h5 class="vortex-resource-title">
                                    <?php echo esc_html($resource['title']); ?>
                                </h5>
                                <p class="vortex-resource-description">
                                    <?php echo esc_html($resource['description']); ?>
                                </p>
                                <div class="vortex-resource-meta">
                                    <?php if (!empty($resource['duration'])) : ?>
                                        <span>
                                            <i class="fas fa-clock"></i>
                                            <?php echo esc_html($resource['duration']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($resource['difficulty'])) : ?>
                                        <span>
                                            <i class="fas fa-signal"></i>
                                            <?php echo esc_html($resource['difficulty']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($resource['prerequisites'])) : ?>
                                        <span>
                                            <i class="fas fa-list-check"></i>
                                            <?php echo esc_html($resource['prerequisites']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($resource['url'])) : ?>
                                    <a href="<?php echo esc_url($resource['url']); ?>" class="vortex-button" target="_blank" rel="noopener noreferrer">
                                        <?php esc_html_e('Access Resource', 'vortex-ai-marketplace'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="vortex-resources-summary">
        <div class="vortex-resources-stats">
            <div class="vortex-resources-stat">
                <span class="vortex-resources-stat-value">
                    <?php echo count($learning_resources); ?>
                </span>
                <span class="vortex-resources-stat-label">
                    <?php esc_html_e('Total Resources', 'vortex-ai-marketplace'); ?>
                </span>
            </div>
            <div class="vortex-resources-stat">
                <span class="vortex-resources-stat-value">
                    <?php echo count($resources_by_category); ?>
                </span>
                <span class="vortex-resources-stat-label">
                    <?php esc_html_e('Categories', 'vortex-ai-marketplace'); ?>
                </span>
            </div>
            <div class="vortex-resources-stat">
                <span class="vortex-resources-stat-value">
                    <?php
                    $total_duration = array_sum(array_map(function($resource) {
                        return isset($resource['duration_minutes']) ? $resource['duration_minutes'] : 0;
                    }, $learning_resources));
                    echo round($total_duration / 60, 1);
                    ?>
                </span>
                <span class="vortex-resources-stat-label">
                    <?php esc_html_e('Hours of Content', 'vortex-ai-marketplace'); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="vortex-resources-actions">
        <button class="vortex-button" id="vortex-refresh-resources">
            <i class="fas fa-sync"></i>
            <?php esc_html_e('Refresh Resources', 'vortex-ai-marketplace'); ?>
        </button>
        <button class="vortex-button" id="vortex-suggest-resources">
            <i class="fas fa-lightbulb"></i>
            <?php esc_html_e('Get More Suggestions', 'vortex-ai-marketplace'); ?>
        </button>
    </div>
</div> 