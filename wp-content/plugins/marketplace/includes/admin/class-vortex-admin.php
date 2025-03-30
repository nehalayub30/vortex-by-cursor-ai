/**
 * Display ROI status in admin notices - ADMIN ONLY
 */
public function display_roi_admin_notice() {
    // Only show to administrators
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $roi = get_option('vortex_current_roi', 0);
    $target_roi = get_option('vortex_ai_target_roi', 80);
    
    // Only show warning if ROI is below target
    if ($roi < $target_roi) {
        $class = ($roi < 60) ? 'notice-error' : 'notice-warning';
        ?>
        <div class="notice <?php echo $class; ?> is-dismissible">
            <p><strong><?php esc_html_e('VORTEX AI Marketplace ROI Alert', 'vortex-marketplace'); ?></strong></p>
            <p><?php printf(esc_html__('Current ROI is at %1$s%%, below the target of %2$s%%. The AI agents have generated recommendations for improvements.', 'vortex-marketplace'), 
                number_format($roi, 1), $target_roi); ?></p>
            <p><a href="<?php echo esc_url(admin_url('admin.php?page=vortex-analytics')); ?>" class="button button-primary">
                <?php esc_html_e('View AI Recommendations', 'vortex-marketplace'); ?>
            </a></p>
        </div>
        <?php
    }
} 