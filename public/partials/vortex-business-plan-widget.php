<?php
/**
 * Template for the Business Plan Widget
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
$business_plan = get_user_meta($user_id, 'vortex_business_plan', true);
?>

<div class="vortex-business-widget vortex-plan-widget">
    <div class="vortex-business-widget-header">
        <span class="vortex-business-widget-icon">
            <i class="fas fa-chart-line"></i>
        </span>
        <h3 class="vortex-business-widget-title">
            <?php esc_html_e('Business Plan Generator', 'vortex-ai-marketplace'); ?>
        </h3>
    </div>

    <?php if (!$business_plan) : ?>
        <form id="vortex-plan-form" class="vortex-plan-form">
            <div class="vortex-plan-type-selector">
                <div class="vortex-plan-type" data-type="starter">
                    <h4 class="vortex-plan-type-title">
                        <?php esc_html_e('Starter Plan', 'vortex-ai-marketplace'); ?>
                    </h4>
                    <p class="vortex-plan-type-description">
                        <?php esc_html_e('Perfect for artists just starting their journey. Focus on building your portfolio and establishing your presence.', 'vortex-ai-marketplace'); ?>
                    </p>
                    <ul class="vortex-plan-type-features">
                        <li><?php esc_html_e('30-day action plan', 'vortex-ai-marketplace'); ?></li>
                        <li><?php esc_html_e('Portfolio optimization', 'vortex-ai-marketplace'); ?></li>
                        <li><?php esc_html_e('Basic marketing strategy', 'vortex-ai-marketplace'); ?></li>
                    </ul>
                </div>

                <div class="vortex-plan-type" data-type="growth">
                    <h4 class="vortex-plan-type-title">
                        <?php esc_html_e('Growth Plan', 'vortex-ai-marketplace'); ?>
                    </h4>
                    <p class="vortex-plan-type-description">
                        <?php esc_html_e('Ideal for artists ready to expand their reach and increase sales. Includes advanced marketing and networking strategies.', 'vortex-ai-marketplace'); ?>
                    </p>
                    <ul class="vortex-plan-type-features">
                        <li><?php esc_html_e('90-day comprehensive plan', 'vortex-ai-marketplace'); ?></li>
                        <li><?php esc_html_e('Advanced marketing tactics', 'vortex-ai-marketplace'); ?></li>
                        <li><?php esc_html_e('Networking and collaboration', 'vortex-ai-marketplace'); ?></li>
                    </ul>
                </div>

                <div class="vortex-plan-type" data-type="professional">
                    <h4 class="vortex-plan-type-title">
                        <?php esc_html_e('Professional Plan', 'vortex-ai-marketplace'); ?>
                    </h4>
                    <p class="vortex-plan-type-description">
                        <?php esc_html_e('For established artists looking to scale their business. Includes business development and brand building.', 'vortex-ai-marketplace'); ?>
                    </p>
                    <ul class="vortex-plan-type-features">
                        <li><?php esc_html_e('180-day strategic plan', 'vortex-ai-marketplace'); ?></li>
                        <li><?php esc_html_e('Brand development', 'vortex-ai-marketplace'); ?></li>
                        <li><?php esc_html_e('Business scaling strategies', 'vortex-ai-marketplace'); ?></li>
                    </ul>
                </div>
            </div>

            <div class="vortex-plan-messages">
                <div class="vortex-error"></div>
                <div class="vortex-success"></div>
            </div>

            <button type="submit" class="vortex-button">
                <?php esc_html_e('Generate Plan', 'vortex-ai-marketplace'); ?>
            </button>
        </form>
    <?php else : ?>
        <div class="vortex-plan-content">
            <div class="vortex-plan-timeline">
                <?php foreach ($business_plan['milestones'] as $milestone) : ?>
                    <div class="vortex-plan-milestone">
                        <div class="vortex-plan-milestone-header">
                            <h4><?php echo esc_html($milestone['title']); ?></h4>
                            <span class="vortex-plan-milestone-date">
                                <?php printf(esc_html__('Day %d', 'vortex-ai-marketplace'), $milestone['day']); ?>
                            </span>
                        </div>
                        <ul class="vortex-plan-milestone-tasks">
                            <?php foreach ($milestone['tasks'] as $task) : ?>
                                <li><?php echo esc_html($task); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="vortex-plan-summary">
                <h4><?php esc_html_e('Plan Summary', 'vortex-ai-marketplace'); ?></h4>
                <p><?php echo esc_html($business_plan['summary']); ?></p>
                
                <div class="vortex-plan-metrics">
                    <div class="vortex-plan-metric">
                        <span class="vortex-plan-metric-label">
                            <?php esc_html_e('Duration', 'vortex-ai-marketplace'); ?>
                        </span>
                        <span class="vortex-plan-metric-value">
                            <?php printf(esc_html__('%d days', 'vortex-ai-marketplace'), $business_plan['duration']); ?>
                        </span>
                    </div>
                    <div class="vortex-plan-metric">
                        <span class="vortex-plan-metric-label">
                            <?php esc_html_e('Milestones', 'vortex-ai-marketplace'); ?>
                        </span>
                        <span class="vortex-plan-metric-value">
                            <?php printf(esc_html__('%d', 'vortex-ai-marketplace'), count($business_plan['milestones'])); ?>
                        </span>
                    </div>
                    <div class="vortex-plan-metric">
                        <span class="vortex-plan-metric-label">
                            <?php esc_html_e('Tasks', 'vortex-ai-marketplace'); ?>
                        </span>
                        <span class="vortex-plan-metric-value">
                            <?php
                            $total_tasks = array_sum(array_map(function($milestone) {
                                return count($milestone['tasks']);
                            }, $business_plan['milestones']));
                            printf(esc_html__('%d', 'vortex-ai-marketplace'), $total_tasks);
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div> 