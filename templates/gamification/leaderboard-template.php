<?php
/**
 * Template for displaying gamification leaderboard
 *
 * This template can be overridden by copying it to yourtheme/vortex/gamification/leaderboard-template.php
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Enqueue necessary scripts and styles
wp_enqueue_style('vortex-gamification-style', VORTEX_PLUGIN_URL . 'assets/css/vortex-gamification.css', array(), VORTEX_VERSION);
wp_enqueue_script('vortex-gamification-script', VORTEX_PLUGIN_URL . 'assets/js/vortex-gamification.js', array('jquery'), VORTEX_VERSION, true);

// Format period for display
switch ($atts['period']) {
    case 'weekly':
        $period_label = __('Weekly Leaderboard', 'vortex-marketplace');
        break;
    case 'monthly':
        $period_label = __('Monthly Leaderboard', 'vortex-marketplace');
        break;
    case 'all_time':
    default:
        $period_label = __('All-Time Leaderboard', 'vortex-marketplace');
        break;
}
?>

<div class="vortex-leaderboard-container">
    <div class="vortex-leaderboard-header">
        <h2><?php echo $period_label; ?></h2>
        
        <div class="vortex-leaderboard-filter">
            <a href="<?php echo add_query_arg('period', 'weekly'); ?>" class="<?php echo $atts['period'] === 'weekly' ? 'active' : ''; ?>">
                <?php _e('Weekly', 'vortex-marketplace'); ?>
            </a>
            <a href="<?php echo add_query_arg('period', 'monthly'); ?>" class="<?php echo $atts['period'] === 'monthly' ? 'active' : ''; ?>">
                <?php _e('Monthly', 'vortex-marketplace'); ?>
            </a>
            <a href="<?php echo add_query_arg('period', 'all_time'); ?>" class="<?php echo $atts['period'] === 'all_time' ? 'active' : ''; ?>">
                <?php _e('All Time', 'vortex-marketplace'); ?>
            </a>
        </div>
    </div>
    
    <?php if (!empty($leaderboard)): ?>
        <div class="vortex-leaderboard-top3">
            <?php 
            // Extract top 3 users
            $top_users = array_slice($leaderboard, 0, 3);
            
            // Place the second user first, first user second, third user third for visual layout
            if (count($top_users) >= 3) {
                $temp = $top_users[0];
                $top_users[0] = $top_users[1];
                $top_users[1] = $temp;
            }
            
            foreach ($top_users as $index => $user):
                $position = $index == 0 ? 2 : ($index == 1 ? 1 : 3);
                $position_class = 'position-' . $position;
            ?>
                <div class="leaderboard-top-user <?php echo $position_class; ?>">
                    <div class="position-badge"><?php echo $position; ?></div>
                    <div class="user-avatar">
                        <img src="<?php echo esc_url($user['avatar']); ?>" alt="<?php echo esc_attr($user['display_name']); ?>">
                    </div>
                    <div class="user-details">
                        <h3 class="user-name"><?php echo esc_html($user['display_name']); ?></h3>
                        <div class="user-level">
                            <?php printf(__('Level %d', 'vortex-marketplace'), $user['level']); ?>
                        </div>
                        <div class="user-points">
                            <?php printf(_n('%s point', '%s points', $user['points'], 'vortex-marketplace'), 
                                number_format($user['points'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="vortex-leaderboard-table">
            <table>
                <thead>
                    <tr>
                        <th><?php _e('Rank', 'vortex-marketplace'); ?></th>
                        <th><?php _e('User', 'vortex-marketplace'); ?></th>
                        <th><?php _e('Level', 'vortex-marketplace'); ?></th>
                        <th><?php _e('Points', 'vortex-marketplace'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaderboard as $user): ?>
                        <tr>
                            <td class="user-rank"><?php echo $user['rank']; ?></td>
                            <td class="user-info">
                                <div class="table-user-avatar">
                                    <img src="<?php echo esc_url($user['avatar']); ?>" alt="<?php echo esc_attr($user['display_name']); ?>">
                                </div>
                                <span class="table-user-name">
                                    <?php echo esc_html($user['display_name']); ?>
                                </span>
                            </td>
                            <td class="user-level"><?php echo $user['level']; ?></td>
                            <td class="user-points"><?php echo number_format($user['points']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php 
        // Check if current user is logged in
        if (is_user_logged_in()):
            $current_user_id = get_current_user_id();
            $found = false;
            $current_user_rank = 0;
            
            // Find current user in leaderboard
            foreach ($leaderboard as $user) {
                if ($user['user_id'] == $current_user_id) {
                    $current_user_rank = $user;
                    $found = true;
                    break;
                }
            }
            
            // If user not in displayed leaderboard, get their rank separately
            if (!$found) {
                $all_users = $gamification->get_all_user_rankings($atts['period']);
                foreach ($all_users as $rank => $user) {
                    if ($user['user_id'] == $current_user_id) {
                        $current_user_rank = $user;
                        $found = true;
                        break;
                    }
                }
            }
            
            if ($found):
        ?>
            <div class="vortex-leaderboard-current-user">
                <h3><?php _e('Your Ranking', 'vortex-marketplace'); ?></h3>
                <div class="current-user-stats">
                    <div class="stat-block user-rank">
                        <span class="stat-label"><?php _e('Rank', 'vortex-marketplace'); ?></span>
                        <span class="stat-value"><?php echo $current_user_rank['rank']; ?></span>
                    </div>
                    <div class="stat-block user-level">
                        <span class="stat-label"><?php _e('Level', 'vortex-marketplace'); ?></span>
                        <span class="stat-value"><?php echo $current_user_rank['level']; ?></span>
                    </div>
                    <div class="stat-block user-points">
                        <span class="stat-label"><?php _e('Points', 'vortex-marketplace'); ?></span>
                        <span class="stat-value"><?php echo number_format($current_user_rank['points']); ?></span>
                    </div>
                </div>
                
                <?php
                // Get user's progress to next level
                $next_level = $current_user_rank['level'] + 1;
                $current_points = $current_user_rank['points'];
                $level_thresholds = $gamification->get_level_thresholds();
                
                if (isset($level_thresholds[$next_level])):
                    $next_threshold = $level_thresholds[$next_level];
                    $prev_threshold = isset($level_thresholds[$current_user_rank['level']]) ? 
                        $level_thresholds[$current_user_rank['level']] : 0;
                    
                    $points_needed = $next_threshold - $current_points;
                    $level_progress = ($current_points - $prev_threshold) / ($next_threshold - $prev_threshold) * 100;
                ?>
                <div class="level-progress">
                    <div class="progress-text">
                        <?php printf(__('Level %d progress: %d%%', 'vortex-marketplace'), 
                            $current_user_rank['level'], round($level_progress)); ?>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $level_progress; ?>%"></div>
                    </div>
                    <div class="progress-next-level">
                        <?php printf(__('%s more points to level %d', 'vortex-marketplace'), 
                            number_format($points_needed), $next_level); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="leaderboard-actions">
                    <a href="<?php echo esc_url(site_url('/profile/achievements/')); ?>" class="view-achievements-btn">
                        <?php _e('View Your Achievements', 'vortex-marketplace'); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="vortex-leaderboard-empty">
            <p><?php _e('No data available for the leaderboard.', 'vortex-marketplace'); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="vortex-leaderboard-footer">
        <h3><?php _e('How to Earn Points', 'vortex-marketplace'); ?></h3>
        <div class="vortex-point-types">
            <div class="point-type-card">
                <div class="point-icon creation">
                    <i class="dashicons dashicons-art"></i>
                </div>
                <div class="point-details">
                    <h4><?php _e('Creation Points', 'vortex-marketplace'); ?></h4>
                    <p><?php _e('Earned by creating and listing artwork in the marketplace.', 'vortex-marketplace'); ?></p>
                </div>
            </div>
            
            <div class="point-type-card">
                <div class="point-icon transaction">
                    <i class="dashicons dashicons-money-alt"></i>
                </div>
                <div class="point-details">
                    <h4><?php _e('Transaction Points', 'vortex-marketplace'); ?></h4>
                    <p><?php _e('Earned through buying, selling, and trading artwork.', 'vortex-marketplace'); ?></p>
                </div>
            </div>
            
            <div class="point-type-card">
                <div class="point-icon social">
                    <i class="dashicons dashicons-groups"></i>
                </div>
                <div class="point-details">
                    <h4><?php _e('Social Points', 'vortex-marketplace'); ?></h4>
                    <p><?php _e('Earned through community interaction and engagement.', 'vortex-marketplace'); ?></p>
                </div>
            </div>
            
            <div class="point-type-card">
                <div class="point-icon governance">
                    <i class="dashicons dashicons-clipboard"></i>
                </div>
                <div class="point-details">
                    <h4><?php _e('Governance Points', 'vortex-marketplace'); ?></h4>
                    <p><?php _e('Earned through participation in DAO governance.', 'vortex-marketplace'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div> 