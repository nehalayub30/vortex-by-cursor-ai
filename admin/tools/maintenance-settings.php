<style>
.vortex-maintenance-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.vortex-maintenance-card h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.vortex-maintenance-card p {
    margin-bottom: 15px;
}

.vortex-action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.vortex-action-card {
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
}

.vortex-action-card h4 {
    margin-top: 0;
    margin-bottom: 10px;
}

.vortex-action-card p {
    margin-bottom: 15px;
    font-size: 13px;
    color: #666;
}

.vortex-action-card .button {
    width: 100%;
    text-align: center;
}

.vortex-action-card.ai-action {
    background: #f0f8ff;
    border-color: #c8d8e8;
}

.vortex-action-card.security-action {
    background: #f6fff0;
    border-color: #d8e8c8;
}

.scheduled-info {
    display: flex;
    align-items: center;
    margin-top: 10px;
    font-size: 12px;
    color: #666;
}

.scheduled-info .dashicons {
    margin-right: 5px;
    color: #0073aa;
}

.vortex-progress-bar {
    height: 5px;
    background: #eee;
    margin-top: 5px;
    overflow: hidden;
    border-radius: 2px;
}

.vortex-progress-bar > div {
    height: 100%;
    background: #0073aa;
    width: 0%;
    transition: width 0.5s ease-in-out;
}

.vortex-maintenance-schedule {
    margin-top: 20px;
}

.vortex-maintenance-schedule h3 {
    margin-top: 0;
}

.vortex-maintenance-schedule table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.vortex-maintenance-schedule th,
.vortex-maintenance-schedule td {
    text-align: left;
    padding: 8px;
    border-bottom: 1px solid #eee;
}

.vortex-maintenance-schedule th {
    font-weight: 600;
    color: #32373c;
}

.vortex-maintenance-tip {
    background: #f0f6fc;
    border-left: 4px solid #72aee6;
    padding: 12px;
    margin: 20px 0;
}

.vortex-action-card.strategy-action {
    background: #f7f6ff;
    border-color: #d8d6e8;
}

.vortex-business-insights {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.vortex-insights-list {
    margin: 0 0 15px 0;
    padding: 0;
    list-style: none;
}

.insight-item {
    padding: 12px 15px;
    border-left: 4px solid #6b5ecd;
    background: #f8f9fa;
    margin-bottom: 10px;
    display: grid;
    grid-template-columns: 30px 1fr;
    gap: 10px;
    align-items: center;
}

.insight-icon {
    color: #6b5ecd;
}

.insight-content {
    font-size: 14px;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Handle ajax actions
    $('.vortex-ajax-action').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var action = $button.data('action');
        var $card = $button.closest('.vortex-action-card');
        var $progress = $card.find('.vortex-progress-bar > div');
        
        // Update button state
        $button.prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'vortex-ai-marketplace')); ?>');
        
        // Show progress animation
        $progress.css('width', '0%').animate({width: '90%'}, 1500);
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'vortex_maintenance_ajax',
                maintenance_action: action,
                nonce: '<?php echo wp_create_nonce('vortex_maintenance_ajax_nonce'); ?>'
            },
            success: function(response) {
                // Complete the progress bar animation
                $progress.stop().animate({width: '100%'}, 200, function() {
                    setTimeout(function() {
                        $progress.css('width', '0%');
                    }, 500);
                });
                
                if (response.success) {
                    // Show success message
                    $card.append('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                    
                    // Update button state
                    $button.text('<?php echo esc_js(__('Completed', 'vortex-ai-marketplace')); ?>');
                    
                    // Update the health score if provided
                    if (response.data.health_score) {
                        updateHealthScore(response.data.health_score);
                    }
                    
                    // Re-enable button after 3 seconds
                    setTimeout(function() {
                        $button.prop('disabled', false).text(response.data.button_text || '<?php echo esc_js(__('Run Again', 'vortex-ai-marketplace')); ?>');
                    }, 3000);
                } else {
                    // Show error message
                    $card.append('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                    
                    // Reset button state
                    $button.prop('disabled', false).text('<?php echo esc_js(__('Try Again', 'vortex-ai-marketplace')); ?>');
                }
                
                // Remove the message after 5 seconds
                setTimeout(function() {
                    $card.find('.notice').fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            },
            error: function() {
                // Complete the progress bar animation
                $progress.stop().animate({width: '100%'}, 200, function() {
                    setTimeout(function() {
                        $progress.css('width', '0%');
                    }, 500);
                });
                
                // Show error message
                $card.append('<div class="notice notice-error inline"><p><?php echo esc_js(__('Something went wrong. Please try again.', 'vortex-ai-marketplace')); ?></p></div>');
                
                // Reset button state
                $button.prop('disabled', false).text('<?php echo esc_js(__('Try Again', 'vortex-ai-marketplace')); ?>');
                
                // Remove the message after 5 seconds
                setTimeout(function() {
                    $card.find('.notice').fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        });
    });
    
    // Function to update health score display
    function updateHealthScore(score) {
        var $scoreElem = $('.score-circle');
        var $scoreParent = $('.vortex-health-score');
        
        // Update the score value
        $scoreElem.text(score);
        
        // Update the color class
        $scoreParent.removeClass('health-good health-warning health-critical');
        
        if (score >= 80) {
            $scoreParent.addClass('health-good');
        } else if (score >= 60) {
            $scoreParent.addClass('health-warning');
        } else {
            $scoreParent.addClass('health-critical');
        }
    }
    
    // Toggle sections with expandable content
    $('.vortex-expandable-header').on('click', function() {
        $(this).next('.vortex-expandable-content').slideToggle(300);
        $(this).toggleClass('expanded');
    });
});
</script>

<div class="wrap">
    <h1><?php echo esc_html__('Vortex AI Marketplace Maintenance', 'vortex-ai-marketplace'); ?></h1>
    
    <?php if ($action_performed): ?>
        <?php if (!empty($action_messages)): ?>
            <div class="notice notice-success is-dismissible">
                <?php foreach ($action_messages as $message): ?>
                    <p><?php echo esc_html($message); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($action_errors)): ?>
            <div class="notice notice-error is-dismissible">
                <?php foreach ($action_errors as $error): ?>
                    <p><?php echo esc_html($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- System Health Dashboard -->
    <div class="vortex-dashboard-header">
        <div class="vortex-health-score <?php echo $system_health >= 80 ? 'health-good' : ($system_health >= 60 ? 'health-warning' : 'health-critical'); ?>">
            <div class="score-circle"><?php echo esc_html($system_health); ?></div>
            <div class="score-label"><?php echo esc_html__('Health Score', 'vortex-ai-marketplace'); ?></div>
        </div>
        
        <div class="vortex-system-summary">
            <h2><?php echo esc_html__('System Summary', 'vortex-ai-marketplace'); ?></h2>
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="summary-label"><?php echo esc_html__('Plugin Version', 'vortex-ai-marketplace'); ?></span>
                    <span class="summary-value"><?php echo esc_html(VORTEX_AI_MARKETPLACE_VERSION); ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="summary-label"><?php echo esc_html__('Database Size', 'vortex-ai-marketplace'); ?></span>
                    <span class="summary-value"><?php echo esc_html($db_size); ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="summary-label"><?php echo esc_html__('Cache Status', 'vortex-ai-marketplace'); ?></span>
                    <span class="summary-value"><?php echo $cache_active ? esc_html__('Active', 'vortex-ai-marketplace') : esc_html__('Inactive', 'vortex-ai-marketplace'); ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="summary-label"><?php echo esc_html__('Last Maintenance', 'vortex-ai-marketplace'); ?></span>
                    <span class="summary-value"><?php echo esc_html($last_maintenance); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($security_issues)): ?>
    <!-- Security Issues Section -->
    <div class="vortex-security-issues">
        <h2><?php echo esc_html__('Security Issues Detected', 'vortex-ai-marketplace'); ?></h2>
        <p><?php echo esc_html__('The following security issues were found in your marketplace. Please address them to improve your system health.', 'vortex-ai-marketplace'); ?></p>
        
        <ul class="issues-list">
            <?php foreach ($security_issues as $issue): ?>
                <li class="issue-item severity-<?php echo esc_attr($issue['severity']); ?>">
                    <div class="issue-severity"><?php echo esc_html(ucfirst($issue['severity'])); ?></div>
                    <div class="issue-description"><?php echo esc_html($issue['description']); ?></div>
                    <div class="issue-resolution"><?php echo esc_html($issue['resolution']); ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <form method="post" action="">
            <?php wp_nonce_field('vortex_maintenance_nonce'); ?>
            <input type="hidden" name="vortex_maintenance_action" value="security_audit">
            <button type="submit" class="button"><?php echo esc_html__('Run Security Audit Again', 'vortex-ai-marketplace'); ?></button>
        </form>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($business_insights)): ?>
    <!-- Business Strategy Insights -->
    <div class="vortex-business-insights">
        <h2><?php echo esc_html__('Business Strategy Insights', 'vortex-ai-marketplace'); ?></h2>
        <p><?php echo esc_html__('The Business Strategist AI has identified the following opportunities:', 'vortex-ai-marketplace'); ?></p>
        
        <ul class="vortex-insights-list">
            <?php foreach ($business_insights as $insight): ?>
                <li class="insight-item">
                    <div class="insight-icon"><span class="dashicons dashicons-chart-line"></span></div>
                    <div class="insight-content"><?php echo esc_html($insight); ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <form method="post" action="">
            <?php wp_nonce_field('vortex_maintenance_nonce'); ?>
            <input type="hidden" name="vortex_maintenance_action" value="business_strategy_analysis">
            <button type="submit" class="button"><?php echo esc_html__('Generate New Business Insights', 'vortex-ai-marketplace'); ?></button>
        </form>
    </div>
    <?php endif; ?>
    
    <!-- Maintenance Actions -->
    <div class="vortex-maintenance-columns">
        <div class="vortex-maintenance-actions">
            <div class="vortex-maintenance-card">
                <h3><?php echo esc_html__('Maintenance Actions', 'vortex-ai-marketplace'); ?></h3>
                <p><?php echo esc_html__('Perform maintenance tasks to keep your marketplace running smoothly. Regular maintenance helps improve performance and security.', 'vortex-ai-marketplace'); ?></p>
                
                <div class="vortex-action-grid">
                    <!-- Cache Maintenance -->
                    <div class="vortex-action-card">
                        <h4><?php echo esc_html__('Clear Cache', 'vortex-ai-marketplace'); ?></h4>
                        <p><?php echo esc_html__('Clear the plugin cache to ensure the latest settings and content are being used.', 'vortex-ai-marketplace'); ?></p>
                        <div class="vortex-progress-bar"><div></div></div>
                        <button type="button" class="button vortex-ajax-action" data-action="clear_cache">
                            <?php echo esc_html__('Clear Cache', 'vortex-ai-marketplace'); ?>
                        </button>
                        <div class="scheduled-info">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html__('Last run:', 'vortex-ai-marketplace'); ?> <?php echo esc_html($last_cache_clear); ?>
                        </div>
                    </div>
                    
                    <!-- Database Optimization -->
                    <div class="vortex-action-card">
                        <h4><?php echo esc_html__('Optimize Database', 'vortex-ai-marketplace'); ?></h4>
                        <p><?php echo esc_html__('Optimize database tables to improve query performance and reduce storage size.', 'vortex-ai-marketplace'); ?></p>
                        <div class="vortex-progress-bar"><div></div></div>
                        <button type="button" class="button vortex-ajax-action" data-action="optimize_database">
                            <?php echo esc_html__('Optimize Database', 'vortex-ai-marketplace'); ?>
                        </button>
                        <div class="scheduled-info">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html__('Last run:', 'vortex-ai-marketplace'); ?> <?php echo esc_html($last_db_optimization); ?>
                        </div>
                    </div>
                    
                    <!-- Clean Orphaned Data -->
                    <div class="vortex-action-card">
                        <h4><?php echo esc_html__('Clean Orphaned Data', 'vortex-ai-marketplace'); ?></h4>
                        <p><?php echo esc_html__('Remove orphaned metadata, temporary files, and other unused data.', 'vortex-ai-marketplace'); ?></p>
                        <div class="vortex-progress-bar"><div></div></div>
                        <button type="button" class="button vortex-ajax-action" data-action="clean_orphaned_data">
                            <?php echo esc_html__('Clean Orphaned Data', 'vortex-ai-marketplace'); ?>
                        </button>
                        <div class="scheduled-info">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html__('Last run:', 'vortex-ai-marketplace'); ?> <?php echo esc_html($last_orphaned_clean); ?>
                        </div>
                    </div>
                    
                    <!-- Error Log Cleanup -->
                    <div class="vortex-action-card">
                        <h4><?php echo esc_html__('Clean Error Logs', 'vortex-ai-marketplace'); ?></h4>
                        <p><?php echo esc_html__('Remove old error logs to free up space and improve performance.', 'vortex-ai-marketplace'); ?></p>
                        <div class="vortex-progress-bar"><div></div></div>
                        <button type="button" class="button vortex-ajax-action" data-action="error_log_cleanup">
                            <?php echo esc_html__('Clean Error Logs', 'vortex-ai-marketplace'); ?>
                        </button>
                        <div class="scheduled-info">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html__('Last run:', 'vortex-ai-marketplace'); ?> <?php echo esc_html($last_log_cleanup); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($ai_settings['huraii_enabled'])): ?>
                    <!-- HURAII Model Optimization -->
                    <div class="vortex-action-card ai-action">
                        <h4><?php echo esc_html__('HURAII Model Optimization', 'vortex-ai-marketplace'); ?></h4>
                        <p><?php echo esc_html__('Optimize AI model caches for faster art generation and improved results.', 'vortex-ai-marketplace'); ?></p>
                        <div class="vortex-progress-bar"><div></div></div>
                        <button type="button" class="button vortex-ajax-action" data-action="ai_model_optimization">
                            <?php echo esc_html__('Optimize AI Models', 'vortex-ai-marketplace'); ?>
                        </button>
                        <div class="scheduled-info">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html__('Last run:', 'vortex-ai-marketplace'); ?> <?php echo esc_html($last_ai_optimization); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($ai_settings['cloe_enabled'])): ?>
                    <!-- CLOE Market Analysis -->
                    <div class="vortex-action-card ai-action">
                        <h4><?php echo esc_html__('CLOE Market Analysis', 'vortex-ai-marketplace'); ?></h4>
                        <p><?php echo esc_html__('Run market analysis to optimize pricing and identify trending art styles.', 'vortex-ai-marketplace'); ?></p>
                        <div class="vortex-progress-bar"><div></div></div>
                        <button type="button" class="button vortex-ajax-action" data-action="market_analysis">
                            <?php echo esc_html__('Run Market Analysis', 'vortex-ai-marketplace'); ?>
                        </button>
                        <div class="scheduled-info">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html__('Last run:', 'vortex-ai-marketplace'); ?> <?php echo esc_html($last_market_analysis); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Security Audit -->
                    <div class="vortex-action-card security-action">
                        <h4><?php echo esc_html__('Security Audit', 'vortex-ai-marketplace'); ?></h4>
                        <p><?php echo esc_html__('Run a comprehensive security audit to identify potential vulnerabilities.', 'vortex-ai-marketplace'); ?></p>
                        <div class="vortex-progress-bar"><div></div></div>
                        <button type="button" class="button vortex-ajax-action" data-action="security_audit">
                            <?php echo esc_html__('Run Security Audit', 'vortex-ai-marketplace'); ?>
                        </button>
                        <div class="scheduled-info">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html__('Last run:', 'vortex-ai-marketplace'); ?> <?php echo esc_html($last_security_audit); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($ai_settings['strategist_enabled'])): ?>
                    <!-- Business Strategist Analysis -->
                    <div class="vortex-action-card ai-action strategy-action">
                        <h4><?php echo esc_html__('Business Strategy Analysis', 'vortex-ai-marketplace'); ?></h4>
                        <p><?php echo esc_html__('Generate business optimization strategies to increase revenue and user engagement.', 'vortex-ai-marketplace'); ?></p>
                        <div class="vortex-progress-bar"><div></div></div>
                        <button type="button" class="button vortex-ajax-action" data-action="business_strategy_analysis">
                            <?php echo esc_html__('Run Business Analysis', 'vortex-ai-marketplace'); ?>
                        </button>
                        <div class="scheduled-info">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html__('Last run:', 'vortex-ai-marketplace'); ?> <?php echo esc_html($last_business_analysis); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="vortex-maintenance-info">
            <div class="vortex-maintenance-schedule">
                <h3><?php echo esc_html__('Scheduled Maintenance', 'vortex-ai-marketplace'); ?></h3>
                <p><?php echo esc_html__('These tasks run automatically at scheduled intervals:', 'vortex-ai-marketplace'); ?></p>
                
                <table>
                    <tr>
                        <th><?php echo esc_html__('Task', 'vortex-ai-marketplace'); ?></th>
                        <th><?php echo esc_html__('Frequency', 'vortex-ai-marketplace'); ?></th>
                    </tr>
                    <tr>
                        <td><?php echo esc_html__('Cache Cleanup', 'vortex-ai-marketplace'); ?></td>
                        <td><?php echo esc_html__('Daily', 'vortex-ai-marketplace'); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html__('Database Optimization', 'vortex-ai-marketplace'); ?></td>
                        <td><?php echo esc_html__('Weekly', 'vortex-ai-marketplace'); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html__('Error Log Cleanup', 'vortex-ai-marketplace'); ?></td>
                        <td><?php echo esc_html__('Weekly', 'vortex-ai-marketplace'); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html__('AI Model Optimization', 'vortex-ai-marketplace'); ?></td>
                        <td><?php echo esc_html__('Monthly', 'vortex-ai-marketplace'); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html__('Market Analysis', 'vortex-ai-marketplace'); ?></td>
                        <td><?php echo esc_html__('Weekly', 'vortex-ai-marketplace'); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html__('Security Audit', 'vortex-ai-marketplace'); ?></td>
                        <td><?php echo esc_html__('Monthly', 'vortex-ai-marketplace'); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html__('Business Strategy Analysis', 'vortex-ai-marketplace'); ?></td>
                        <td><?php echo esc_html__('Monthly', 'vortex-ai-marketplace'); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="vortex-maintenance-tip">
                <p>
                    <strong><?php echo esc_html__('Pro Tip:', 'vortex-ai-marketplace'); ?></strong>
                    <?php echo esc_html__('For optimal performance, run database optimization after making significant changes to settings or uploading many new artworks.', 'vortex-ai-marketplace'); ?>
                </p>
            </div>
            
            <div class="vortex-maintenance-tip">
                <p>
                    <strong><?php echo esc_html__('AI Enhancement:', 'vortex-ai-marketplace'); ?></strong>
                    <?php echo esc_html__('HURAII and CLOE continuously learn from maintenance operations to improve marketplace efficiency and user experience.', 'vortex-ai-marketplace'); ?>
                </p>
            </div>
            
            <div class="vortex-maintenance-tip">
                <p>
                    <strong><?php echo esc_html__('Business Intelligence:', 'vortex-ai-marketplace'); ?></strong>
                    <?php echo esc_html__('The Business Strategist AI analyzes marketplace patterns to provide data-driven recommendations for revenue growth and user engagement.', 'vortex-ai-marketplace'); ?>
                </p>
            </div>
        </div>
    </div>
</div> 