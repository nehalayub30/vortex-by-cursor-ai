<?php
/**
 * SQL Fixes for VORTEX AI Marketplace - Share Rate Reference
 *
 * This file fixes the "Reference 'share_rate' not supported" error
 * by replacing alias references in HAVING clauses with their original expressions
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/sql-fixes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Function to fix SQL queries with 'share_rate' reference in HAVING clauses
 * 
 * @param string $query SQL query to fix
 * @return string Fixed SQL query
 */
function vortex_fix_share_rate_reference($query) {
    // Check if this is a query with the share_rate issue
    if (stripos($query, 'share_rate') !== false && stripos($query, 'HAVING') !== false) {
        // Replace HAVING share_rate > X with the expanded expression
        $query = preg_replace(
            '/HAVING\s+share_rate\s+>\s+(\d+)/i',
            'HAVING (COUNT(s.share_id) / COUNT(DISTINCT v.view_id)) * 100 > $1',
            $query
        );
        
        // Handle other comparison operators
        $query = preg_replace(
            '/HAVING\s+share_rate\s+>=\s+(\d+)/i',
            'HAVING (COUNT(s.share_id) / COUNT(DISTINCT v.view_id)) * 100 >= $1',
            $query
        );
        
        $query = preg_replace(
            '/HAVING\s+share_rate\s+<\s+(\d+)/i',
            'HAVING (COUNT(s.share_id) / COUNT(DISTINCT v.view_id)) * 100 < $1',
            $query
        );
        
        $query = preg_replace(
            '/HAVING\s+share_rate\s+<=\s+(\d+)/i',
            'HAVING (COUNT(s.share_id) / COUNT(DISTINCT v.view_id)) * 100 <= $1',
            $query
        );
        
        // Fix ORDER BY share_rate as well
        $query = preg_replace(
            '/ORDER BY\s+share_rate\s+(ASC|DESC)/i',
            'ORDER BY (COUNT(s.share_id) / COUNT(DISTINCT v.view_id)) * 100 $1',
            $query
        );
    }
    
    return $query;
}

/**
 * Hook into WP query filter to fix share_rate references
 */
function vortex_apply_share_rate_fixes() {
    add_filter('query', 'vortex_fix_share_rate_reference', 10, 1);
}

// Apply the fix
add_action('init', 'vortex_apply_share_rate_fixes', 5);

/**
 * Fixed version of analyze_social_sharing_impact method that contains the share_rate reference
 * 
 * @param string $period Time period for analysis
 * @return array Social sharing impact data
 */
function vortex_fixed_analyze_social_sharing_impact($period = 'month') {
    try {
        global $wpdb;
        
        // Get time constraint
        if (class_exists('VORTEX_CLOE') && method_exists(VORTEX_CLOE::get_instance(), 'get_time_constraint')) {
            $cloe = VORTEX_CLOE::get_instance();
            $time_constraint = $cloe->get_time_constraint($period);
        } else {
            // Fallback time constraint calculation
            $now = time();
            $interval = 30 * DAY_IN_SECONDS; // Default to month
            
            switch($period) {
                case 'day':
                    $interval = 1 * DAY_IN_SECONDS;
                    break;
                case 'week':
                    $interval = 7 * DAY_IN_SECONDS;
                    break;
                case 'quarter':
                    $interval = 90 * DAY_IN_SECONDS;
                    break;
                case 'year':
                    $interval = 365 * DAY_IN_SECONDS;
                    break;
            }
            
            $time_constraint = date('Y-m-d H:i:s', $now - $interval);
        }
        
        // Find artworks with significant social impact
        $query = $wpdb->prepare(
            "SELECT 
                a.artwork_id,
                a.title,
                COUNT(DISTINCT v.view_id) as view_count,
                COUNT(s.share_id) as share_count,
                COUNT(DISTINCT CASE WHEN t.transaction_time >= %s THEN t.transaction_id ELSE NULL END) as purchase_count,
                (COUNT(s.share_id) / COUNT(DISTINCT v.view_id)) * 100 as share_rate
            FROM {$wpdb->prefix}vortex_artworks a
            LEFT JOIN {$wpdb->prefix}vortex_artwork_views v ON 
                a.artwork_id = v.artwork_id AND 
                v.view_time >= %s
            LEFT JOIN {$wpdb->prefix}vortex_social_shares s ON 
                a.artwork_id = s.artwork_id AND 
                s.share_time >= %s
            LEFT JOIN {$wpdb->prefix}vortex_transactions t ON 
                a.artwork_id = t.artwork_id AND 
                t.status = 'completed'
            GROUP BY a.artwork_id
            HAVING COUNT(DISTINCT v.view_id) > 10
            AND (COUNT(s.share_id) / COUNT(DISTINCT v.view_id)) * 100 > 5
            ORDER BY (COUNT(s.share_id) / COUNT(DISTINCT v.view_id)) * 100 DESC
            LIMIT 20",
            $time_constraint,
            $time_constraint,
            $time_constraint
        );
        
        $viral_artworks = $wpdb->get_results($query);
        
        // Get platform-specific impact
        $platform_query = $wpdb->prepare(
            "SELECT 
                s.platform,
                COUNT(s.share_id) as share_count,
                SUM(s.click_count) as click_count,
                SUM(s.engagement_count) as engagement_count,
                COUNT(DISTINCT t.transaction_id) as conversion_count,
                (COUNT(DISTINCT t.transaction_id) / COUNT(s.share_id)) * 100 as conversion_rate
            FROM {$wpdb->prefix}vortex_social_shares s
            LEFT JOIN {$wpdb->prefix}vortex_transactions t ON 
                s.artwork_id = t.artwork_id AND 
                t.user_id = s.user_id AND
                t.transaction_time >= s.share_time AND
                t.status = 'completed'
            WHERE s.share_time >= %s
            GROUP BY s.platform
            HAVING COUNT(s.share_id) > 5
            ORDER BY (COUNT(DISTINCT t.transaction_id) / COUNT(s.share_id)) * 100 DESC",
            $time_constraint
        );
        
        $platform_impact = $wpdb->get_results($platform_query);
        
        // Get social hashtag impact
        $hashtag_query = $wpdb->prepare(
            "SELECT 
                h.hashtag,
                COUNT(DISTINCT m.share_id) as usage_count,
                COUNT(DISTINCT s.artwork_id) as artwork_count,
                COUNT(DISTINCT t.transaction_id) as transaction_count,
                SUM(t.amount) as revenue_impact
            FROM {$wpdb->prefix}vortex_social_hashtags h
            JOIN {$wpdb->prefix}vortex_hashtag_share_mapping m ON h.hashtag_id = m.hashtag_id
            JOIN {$wpdb->prefix}vortex_social_shares s ON m.share_id = s.share_id
            LEFT JOIN {$wpdb->prefix}vortex_transactions t ON 
                s.artwork_id = t.artwork_id AND 
                t.transaction_time >= s.share_time AND
                t.status = 'completed'
            WHERE s.share_time >= %s
            GROUP BY h.hashtag
            HAVING COUNT(DISTINCT m.share_id) > 3
            ORDER BY SUM(t.amount) DESC
            LIMIT 25",
            $time_constraint
        );
        
        $hashtag_impact = $wpdb->get_results($hashtag_query);
        
        // Return combined analysis
        return array(
            'status' => 'success',
            'viral_artworks' => $viral_artworks,
            'platform_impact' => $platform_impact,
            'hashtag_impact' => $hashtag_impact
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Hook the fixed method to replace the original in VORTEX_CLOE class
 */
function vortex_replace_social_sharing_methods() {
    // Only apply fixes if the class exists
    if (!class_exists('VORTEX_CLOE')) {
        return;
    }
    
    // Get CLOE instance
    $cloe = VORTEX_CLOE::get_instance();
    
    // Override the method with the fixed version
    if (method_exists($cloe, 'set_method_override')) {
        $cloe->set_method_override('analyze_social_sharing_impact', 'vortex_fixed_analyze_social_sharing_impact');
    }
}

// Apply the method override when WordPress initializes
add_action('init', 'vortex_replace_social_sharing_methods', 10);

/**
 * Check if the analyze_social_sharing_impact method exists and contains the share_rate issue
 * 
 * @return bool True if issue is detected, false otherwise
 */
function vortex_detect_share_rate_issue() {
    // Only run this check for administrators
    if (!current_user_can('administrator')) {
        return false;
    }
    
    if (!class_exists('VORTEX_CLOE')) {
        return false;
    }
    
    // Use direct database query to find existing error logs
    global $wpdb;
    $logs_table = $wpdb->prefix . 'vortex_error_logs';
    
    // Check if the error logs table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$logs_table'") === $logs_table;
    
    if ($table_exists) {
        // Look for share_rate reference errors in the logs
        $has_errors = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $logs_table 
                 WHERE error_message LIKE %s
                 AND error_time >= %s",
                '%Reference \'share_rate\' not supported%',
                date('Y-m-d H:i:s', strtotime('-30 days'))
            )
        );
        
        if ($has_errors && intval($has_errors) > 0) {
            return true;
        }
    }
    
    // If no recorded errors, check method through reflection
    $cloe = VORTEX_CLOE::get_instance();
    
    if (method_exists($cloe, 'analyze_social_sharing_impact')) {
        // Use Reflection to analyze the method code
        $reflection = new ReflectionMethod($cloe, 'analyze_social_sharing_impact');
        
        // Check if the method is available and can be analyzed
        if ($reflection->isPublic() || $reflection->isProtected()) {
            try {
                // Make the method accessible if needed
                if (!$reflection->isPublic()) {
                    $reflection->setAccessible(true);
                }
                
                // Get the file and start/end lines
                $file = $reflection->getFileName();
                $start_line = $reflection->getStartLine() - 1;
                $end_line = $reflection->getEndLine();
                
                // Read the method source
                $source = file($file);
                $method_source = implode('', array_slice($source, $start_line, $end_line - $start_line));
                
                // Look for pattern indicating the issue
                if (preg_match('/HAVING\s+share_rate\s+>\s+/i', $method_source) || 
                    preg_match('/ORDER BY\s+share_rate\s+/i', $method_source)) {
                    return true;
                }
            } catch (Exception $e) {
                // If reflection fails, we can't determine if there's an issue
                return false;
            }
        }
    }
    
    return false;
}

/**
 * Force repair for the share_rate issue
 * 
 * @return bool True if fixed successfully, false otherwise
 */
function vortex_force_repair_share_rate_issue() {
    if (!class_exists('VORTEX_CLOE')) {
        return false;
    }
    
    $cloe = VORTEX_CLOE::get_instance();
    
    // Check if the set_method_override method exists
    if (!method_exists($cloe, 'set_method_override')) {
        // Try to add the method override capability
        if (function_exists('vortex_add_method_override_to_cloe')) {
            vortex_add_method_override_to_cloe();
        } else {
            return false;
        }
    }
    
    // Set the method override
    if (method_exists($cloe, 'set_method_override')) {
        $cloe->set_method_override('analyze_social_sharing_impact', 'vortex_fixed_analyze_social_sharing_impact');
        
        // Also hook the query filter to catch any other instances
        add_filter('query', 'vortex_fix_share_rate_reference', 10, 1);
        
        return true;
    }
    
    return false;
}

/**
 * AJAX handler to force repair the share_rate issue
 */
function vortex_ajax_repair_share_rate_issue() {
    // Security check
    check_ajax_referer('vortex_repair_nonce', 'nonce');
    
    // Check if the user has admin privileges
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'vortex-ai-marketplace')));
        return;
    }
    
    // Attempt the repair
    $result = vortex_force_repair_share_rate_issue();
    
    if ($result) {
        // Record that we've fixed the issue
        update_option('vortex_share_rate_fixed', true);
        
        wp_send_json_success(array(
            'message' => __('The share_rate reference issue has been successfully fixed.', 'vortex-ai-marketplace')
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Could not fix the share_rate reference issue. Please check the system logs for more information.', 'vortex-ai-marketplace')
        ));
    }
    
    wp_die();
}
add_action('wp_ajax_vortex_repair_share_rate_issue', 'vortex_ajax_repair_share_rate_issue');

/**
 * Display admin notice for share_rate issue
 */
function vortex_show_share_rate_issue_notice() {
    // Only show to administrators
    if (!is_admin() || !current_user_can('administrator')) {
        return;
    }
    
    // Check if the issue is already fixed
    if (get_option('vortex_share_rate_fixed', false)) {
        return;
    }
    
    // Check for the issue
    $has_issue = vortex_detect_share_rate_issue();
    
    if ($has_issue) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong><?php _e('VORTEX AI Marketplace Database Issue', 'vortex-ai-marketplace'); ?></strong></p>
                <p><?php _e('The system has detected SQL queries with "Reference \'share_rate\' not supported" errors.', 'vortex-ai-marketplace'); ?></p>
                <p>
                    <button type="button" id="vortex-repair-share-rate-issue" class="button button-primary">
                        <?php _e('Fix Share Rate Reference Issue', 'vortex-ai-marketplace'); ?>
                    </button>
                    <span id="vortex-repair-share-rate-status" style="display: none; margin-left: 10px;"></span>
                </p>
            </div>
            <script>
            jQuery(document).ready(function($) {
                $('#vortex-repair-share-rate-issue').on('click', function() {
                    var $button = $(this);
                    var $status = $('#vortex-repair-share-rate-status');
                    
                    $button.prop('disabled', true);
                    $status.text('<?php _e('Fixing issue...', 'vortex-ai-marketplace'); ?>').show();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'vortex_repair_share_rate_issue',
                            nonce: '<?php echo wp_create_nonce('vortex_repair_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $status.text(response.data.message).css('color', 'green');
                                setTimeout(function() {
                                    $button.closest('.notice').fadeOut();
                                }, 3000);
                            } else {
                                $button.prop('disabled', false);
                                $status.text(response.data.message).css('color', 'red');
                            }
                        },
                        error: function() {
                            $button.prop('disabled', false);
                            $status.text('<?php _e('An error occurred. Please try again.', 'vortex-ai-marketplace'); ?>').css('color', 'red');
                        }
                    });
                });
            });
            </script>
            <?php
        });
    }
}
add_action('admin_init', 'vortex_show_share_rate_issue_notice'); 