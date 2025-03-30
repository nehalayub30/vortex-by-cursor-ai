<?php
/**
 * Template for AI Agents Statistics Widget
 * Displays stats for HURAII, CLOE, and Business Strategist
 */

// Get instances of AI agents
$huraii = V_HURAII::get_instance();
$cloe = VORTEX_Cloe::get_instance();
$strategist = VORTEX_Business_Strategist::get_instance();

// Get statistics
$stats = array(
    'huraii' => $huraii->get_performance_stats(),
    'cloe' => $cloe->get_analytics(),
    'strategist' => $strategist->get_insights_stats()
);
?>

<div class="vortex-widget vortex-ai-stats-widget">
    <!-- HURAII Stats -->
    <div class="vortex-ai-agent-section">
        <div class="agent-header">
            <i class="fas fa-robot"></i>
            <h2><?php esc_html_e('HURAII Analytics', 'vortex'); ?></h2>
        </div>
        <div class="agent-stats-grid">
            <div class="stat-card">
                <span class="stat-label"><?php esc_html_e('Art Generated', 'vortex'); ?></span>
                <span class="stat-value"><?php echo esc_html(number_format($stats['huraii']['artworks_generated'])); ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label"><?php esc_html_e('Success Rate', 'vortex'); ?></span>
                <span class="stat-value"><?php echo esc_html($stats['huraii']['success_rate']); ?>%</span>
            </div>
            <div class="stat-card">
                <span class="stat-label"><?php esc_html_e('Processing Time', 'vortex'); ?></span>
                <span class="stat-value"><?php echo esc_html($stats['huraii']['avg_processing_time']); ?>s</span>
            </div>
        </div>
    </div>

    <!-- CLOE Stats -->
    <div class="vortex-ai-agent-section">
        <div class="agent-header">
            <i class="fas fa-brain"></i>
            <h2><?php esc_html_e('CLOE Insights', 'vortex'); ?></h2>
        </div>
        <div class="agent-stats-grid">
            <div class="stat-card">
                <span class="stat-label"><?php esc_html_e('Users Analyzed', 'vortex'); ?></span>
                <span class="stat-value"><?php echo esc_html(number_format($stats['cloe']['users_analyzed'])); ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label"><?php esc_html_e('Recommendations', 'vortex'); ?></span>
                <span class="stat-value"><?php echo esc_html(number_format($stats['cloe']['recommendations_made'])); ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label"><?php esc_html_e('Accuracy', 'vortex'); ?></span>
                <span class="stat-value"><?php echo esc_html($stats['cloe']['prediction_accuracy']); ?>%</span>
            </div>
        </div>
    </div>

    <!-- Business Strategist Stats -->
    <div class="vortex-ai-agent-section">
        <div class="agent-header">
            <i class="fas fa-chart-bar"></i>
            <h2><?php esc_html_e('Business Strategist Metrics', 'vortex'); ?></h2>
        </div>
        <div class="agent-stats-grid">
            <div class="stat-card">
                <span class="stat-label"><?php esc_html_e('Plans Generated', 'vortex'); ?></span>
                <span class="stat-value"><?php echo esc_html(number_format($stats['strategist']['plans_generated'])); ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label"><?php esc_html_e('Success Rate', 'vortex'); ?></span>
                <span class="stat-value"><?php echo esc_html($stats['strategist']['success_rate']); ?>%</span>
            </div>
            <div class="stat-card">
                <span class="stat-label"><?php esc_html_e('Active Users', 'vortex'); ?></span>
                <span class="stat-value"><?php echo esc_html(number_format($stats['strategist']['active_users'])); ?></span>
            </div>
        </div>
    </div>
</div> 