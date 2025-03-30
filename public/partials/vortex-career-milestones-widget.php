<?php
/**
 * Template for the Career Milestones Widget
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
$career_milestones = get_user_meta($user_id, 'vortex_career_milestones', true);

// If no milestones exist, get default milestones from Business Strategist
if (!$career_milestones) {
    $business_strategist = VORTEX_BusinessStrategist::get_instance();
    $career_milestones = $business_strategist->get_default_career_milestones($user_id);
    update_user_meta($user_id, 'vortex_career_milestones', $career_milestones);
}

// Calculate overall progress
$total_milestones = count($career_milestones);
$completed_milestones = array_filter($career_milestones, function($milestone) {
    return $milestone['status'] === 'completed';
});
$progress_percentage = ($total_milestones > 0) ? (count($completed_milestones) / $total_milestones) * 100 : 0;
?>

<div class="vortex-business-widget vortex-milestones-widget">
    <div class="vortex-business-widget-header">
        <span class="vortex-business-widget-icon">
            <i class="fas fa-flag-checkered"></i>
        </span>
        <h3 class="vortex-business-widget-title">
            <?php esc_html_e('Career Milestones', 'vortex-ai-marketplace'); ?>
        </h3>
    </div>

    <div class="vortex-milestones-progress">
        <div class="vortex-milestones-progress-bar">
            <div class="vortex-milestones-progress-fill" style="width: <?php echo esc_attr($progress_percentage); ?>%"></div>
        </div>
        <div class="vortex-milestones-progress-text">
            <?php
            printf(
                esc_html__('Progress: %d%% (%d/%d milestones completed)', 'vortex-ai-marketplace'),
                round($progress_percentage),
                count($completed_milestones),
                $total_milestones
            );
            ?>
        </div>
    </div>

    <div class="vortex-milestones-list">
        <?php foreach ($career_milestones as $milestone) : ?>
            <div class="vortex-milestone" data-milestone-id="<?php echo esc_attr($milestone['id']); ?>">
                <div class="vortex-milestone-status <?php echo esc_attr($milestone['status']); ?>" data-status="<?php echo esc_attr($milestone['status']); ?>">
                    <?php if ($milestone['status'] === 'completed') : ?>
                        <i class="fas fa-check"></i>
                    <?php else : ?>
                        <i class="fas fa-clock"></i>
                    <?php endif; ?>
                </div>
                <div class="vortex-milestone-content">
                    <h4 class="vortex-milestone-title">
                        <?php echo esc_html($milestone['title']); ?>
                    </h4>
                    <p class="vortex-milestone-description">
                        <?php echo esc_html($milestone['description']); ?>
                    </p>
                    <?php if (!empty($milestone['tasks'])) : ?>
                        <div class="vortex-milestone-tasks">
                            <h5><?php esc_html_e('Required Tasks:', 'vortex-ai-marketplace'); ?></h5>
                            <ul>
                                <?php foreach ($milestone['tasks'] as $task) : ?>
                                    <li><?php echo esc_html($task); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($milestone['rewards'])) : ?>
                        <div class="vortex-milestone-rewards">
                            <h5><?php esc_html_e('Rewards:', 'vortex-ai-marketplace'); ?></h5>
                            <ul>
                                <?php foreach ($milestone['rewards'] as $reward) : ?>
                                    <li><?php echo esc_html($reward); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if ($milestone['status'] === 'completed' && !empty($milestone['completion_date'])) : ?>
                        <div class="vortex-milestone-completion">
                            <?php
                            printf(
                                esc_html__('Completed on: %s', 'vortex-ai-marketplace'),
                                date_i18n(get_option('date_format'), strtotime($milestone['completion_date']))
                            );
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($progress_percentage === 100) : ?>
        <div class="vortex-milestones-completion">
            <i class="fas fa-trophy"></i>
            <h4><?php esc_html_e('Congratulations!', 'vortex-ai-marketplace'); ?></h4>
            <p><?php esc_html_e('You have completed all career milestones. Keep up the great work!', 'vortex-ai-marketplace'); ?></p>
        </div>
    <?php endif; ?>
</div> 