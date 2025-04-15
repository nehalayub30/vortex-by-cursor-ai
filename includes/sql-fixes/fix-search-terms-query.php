<?php
/**
 * SQL Fix for CLOE Search Terms Query
 * 
 * This utility file provides a direct fix for the "Reference 'current_period_searches' not supported" error
 * in the CLOE search terms query by creating a database view that will work properly.
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage SQL_Fixes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fix the search terms query by creating a database view
 * 
 * @return array Status information
 */
function vortex_fix_search_terms_query() {
    global $wpdb;
    
    $results = array(
        'success' => false,
        'messages' => array(),
        'errors' => array(),
        'actions_taken' => array()
    );
    
    try {
        // Check if the searches table exists
        $searches_table = $wpdb->prefix . 'vortex_searches';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$searches_table'") !== $searches_table) {
            $results['errors'][] = 'The vortex_searches table does not exist.';
            return $results;
        }
        
        // Check if we have permission to create views
        $can_create_view = false;
        $grants = $wpdb->get_results("SHOW GRANTS FOR CURRENT_USER()");
        
        foreach ($grants as $grant) {
            $grant_str = reset($grant);
            if (strpos($grant_str, 'ALL PRIVILEGES') !== false || 
                strpos($grant_str, 'CREATE VIEW') !== false) {
                $can_create_view = true;
                break;
            }
        }
        
        // If we can't create views, we'll use a different approach with a table instead
        if (!$can_create_view) {
            $results['messages'][] = 'No permission to create views. Using table-based approach instead.';
            
            // Create a temporary table to store search term statistics
            $stats_table = $wpdb->prefix . 'vortex_search_term_stats';
            
            // Drop the table if it exists
            $wpdb->query("DROP TABLE IF EXISTS $stats_table");
            
            // Create the table
            $create_table_sql = "
                CREATE TABLE $stats_table (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    search_term varchar(255) NOT NULL,
                    current_period_searches int(11) DEFAULT 0,
                    previous_period_searches int(11) DEFAULT 0,
                    PRIMARY KEY (id),
                    UNIQUE KEY search_term (search_term)
                ) " . $wpdb->get_charset_collate();
            
            $wpdb->query($create_table_sql);
            $results['actions_taken'][] = 'Created search term statistics table.';
            
            // Create a stored procedure to update the statistics table
            $proc_name = $wpdb->prefix . 'update_search_term_stats';
            
            // Drop the procedure if it exists
            $wpdb->query("DROP PROCEDURE IF EXISTS $proc_name");
            
            // Create the procedure
            $create_proc_sql = "
                CREATE PROCEDURE $proc_name(IN period_days INT)
                BEGIN
                    DECLARE current_date DATETIME;
                    DECLARE previous_start DATETIME;
                    
                    SET current_date = NOW();
                    SET previous_start = DATE_SUB(current_date, INTERVAL (period_days * 2) DAY);
                    
                    -- Clear existing stats
                    TRUNCATE TABLE $stats_table;
                    
                    -- Insert new stats
                    INSERT INTO $stats_table (search_term, current_period_searches, previous_period_searches)
                    SELECT 
                        search_term,
                        COUNT(CASE WHEN search_time >= DATE_SUB(current_date, INTERVAL period_days DAY) THEN 1 ELSE NULL END) as current_period_searches,
                        COUNT(CASE WHEN search_time >= previous_start AND search_time < DATE_SUB(current_date, INTERVAL period_days DAY) THEN 1 ELSE NULL END) as previous_period_searches
                    FROM $searches_table
                    WHERE search_time >= previous_start
                    GROUP BY search_term;
                END
            ";
            
            // Create the procedure if we have CREATE ROUTINE privilege
            $has_create_routine = false;
            foreach ($grants as $grant) {
                $grant_str = reset($grant);
                if (strpos($grant_str, 'ALL PRIVILEGES') !== false || 
                    strpos($grant_str, 'CREATE ROUTINE') !== false) {
                    $has_create_routine = true;
                    break;
                }
            }
            
            if ($has_create_routine) {
                $wpdb->query($create_proc_sql);
                $results['actions_taken'][] = 'Created stored procedure to update search term statistics.';
                
                // Run the procedure to populate initial data (using 30 days as default period)
                $wpdb->query("CALL $proc_name(30)");
                $results['actions_taken'][] = 'Populated search term statistics table with initial data.';
                
                // Create an event to update stats regularly if we have EVENT privilege
                $has_event_privilege = false;
                foreach ($grants as $grant) {
                    $grant_str = reset($grant);
                    if (strpos($grant_str, 'ALL PRIVILEGES') !== false || 
                        strpos($grant_str, 'EVENT') !== false) {
                        $has_event_privilege = true;
                        break;
                    }
                }
                
                if ($has_event_privilege) {
                    // Drop existing event if it exists
                    $event_name = $wpdb->prefix . 'update_search_stats_event';
                    $wpdb->query("DROP EVENT IF EXISTS $event_name");
                    
                    // Create the event to run daily
                    $create_event_sql = "
                        CREATE EVENT $event_name
                        ON SCHEDULE EVERY 1 DAY
                        DO CALL $proc_name(30)
                    ";
                    
                    $wpdb->query($create_event_sql);
                    $results['actions_taken'][] = 'Created event to update search term statistics daily.';
                } else {
                    $results['messages'][] = 'No EVENT privilege. Stats table will need to be updated manually or via cron job.';
                    
                    // Add a cron job hook
                    if (!wp_next_scheduled('vortex_update_search_term_stats')) {
                        wp_schedule_event(time(), 'daily', 'vortex_update_search_term_stats');
                        $results['actions_taken'][] = 'Scheduled WordPress cron job to update search term statistics daily.';
                    }
                }
            } else {
                $results['messages'][] = 'No CREATE ROUTINE privilege. Stats table will need to be populated manually.';
                
                // Directly populate the table
                $current_date = current_time('mysql');
                $period_days = 30;
                $previous_start = date('Y-m-d H:i:s', strtotime('-' . ($period_days * 2) . ' days', strtotime($current_date)));
                $current_start = date('Y-m-d H:i:s', strtotime('-' . $period_days . ' days', strtotime($current_date)));
                
                $populate_sql = "
                    INSERT INTO $stats_table (search_term, current_period_searches, previous_period_searches)
                    SELECT 
                        search_term,
                        COUNT(CASE WHEN search_time >= %s THEN 1 ELSE NULL END) as current_period_searches,
                        COUNT(CASE WHEN search_time >= %s AND search_time < %s THEN 1 ELSE NULL END) as previous_period_searches
                    FROM $searches_table
                    WHERE search_time >= %s
                    GROUP BY search_term
                ";
                
                $wpdb->query($wpdb->prepare($populate_sql, $current_start, $previous_start, $current_start, $previous_start));
                $results['actions_taken'][] = 'Populated search term statistics table with initial data.';
                
                // Add a cron job hook
                if (!wp_next_scheduled('vortex_update_search_term_stats')) {
                    wp_schedule_event(time(), 'daily', 'vortex_update_search_term_stats');
                    $results['actions_taken'][] = 'Scheduled WordPress cron job to update search term statistics daily.';
                }
            }
            
            $results['success'] = true;
            $results['messages'][] = 'Successfully created and populated search term statistics table.';
        } else {
            // We can create views, so let's do that
            $view_name = $wpdb->prefix . 'vortex_search_term_stats_view';
            
            // Drop the view if it exists
            $wpdb->query("DROP VIEW IF EXISTS $view_name");
            
            // Create the view
            $create_view_sql = "
                CREATE VIEW $view_name AS
                SELECT 
                    search_term,
                    COUNT(CASE WHEN search_time >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE NULL END) as current_period_searches,
                    COUNT(CASE WHEN search_time >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND search_time < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE NULL END) as previous_period_searches
                FROM $searches_table
                WHERE search_time >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                GROUP BY search_term
            ";
            
            $wpdb->query($create_view_sql);
            $results['actions_taken'][] = 'Created search term statistics view.';
            
            $results['success'] = true;
            $results['messages'][] = 'Successfully created search term statistics view.';
        }
        
        // Update the CLOE class options to use the new approach
        update_option('vortex_use_search_term_stats', true);
        update_option('vortex_search_term_stats_fixed', current_time('mysql'));
        $results['actions_taken'][] = 'Updated CLOE configuration to use new statistics.';
        
    } catch (Exception $e) {
        $results['success'] = false;
        $results['errors'][] = 'Error: ' . $e->getMessage();
    }
    
    return $results;
}

/**
 * Function to handle the cron job
 */
function vortex_update_search_term_stats_cron() {
    global $wpdb;
    
    // Get the table name
    $stats_table = $wpdb->prefix . 'vortex_search_term_stats';
    $searches_table = $wpdb->prefix . 'vortex_searches';
    
    // Check if the table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$stats_table'") !== $stats_table) {
        error_log('VORTEX: Search term statistics table does not exist.');
        return;
    }
    
    // Get current date and calculate periods
    $current_date = current_time('mysql');
    $period_days = 30;
    $previous_start = date('Y-m-d H:i:s', strtotime('-' . ($period_days * 2) . ' days', strtotime($current_date)));
    $current_start = date('Y-m-d H:i:s', strtotime('-' . $period_days . ' days', strtotime($current_date)));
    
    // Empty the table
    $wpdb->query("TRUNCATE TABLE $stats_table");
    
    // Repopulate with fresh data
    $populate_sql = "
        INSERT INTO $stats_table (search_term, current_period_searches, previous_period_searches)
        SELECT 
            search_term,
            COUNT(CASE WHEN search_time >= %s THEN 1 ELSE NULL END) as current_period_searches,
            COUNT(CASE WHEN search_time >= %s AND search_time < %s THEN 1 ELSE NULL END) as previous_period_searches
        FROM $searches_table
        WHERE search_time >= %s
        GROUP BY search_term
    ";
    
    $wpdb->query($wpdb->prepare($populate_sql, $current_start, $previous_start, $current_start, $previous_start));
    
    error_log('VORTEX: Search term statistics table updated successfully.');
}
add_action('vortex_update_search_term_stats', 'vortex_update_search_term_stats_cron');

/**
 * Add the fix to the admin menu
 */
function vortex_add_sql_fixes_menu() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    add_submenu_page(
        'tools.php',
        'VORTEX SQL Fixes',
        'VORTEX SQL Fixes',
        'manage_options',
        'vortex-sql-fixes',
        'vortex_sql_fixes_page'
    );
}
add_action('admin_menu', 'vortex_add_sql_fixes_menu');

/**
 * SQL Fixes admin page
 */
function vortex_sql_fixes_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $results = array();
    
    // Check if we're applying the fix
    if (isset($_POST['vortex_fix_search_terms']) && check_admin_referer('vortex_sql_fixes')) {
        $results = vortex_fix_search_terms_query();
    }
    
    ?>
    <div class="wrap">
        <h1>VORTEX SQL Fixes</h1>
        
        <?php if (!empty($results)): ?>
            <div class="notice <?php echo $results['success'] ? 'notice-success' : 'notice-error'; ?>">
                <h3>Results:</h3>
                <?php if (!empty($results['messages'])): ?>
                    <p><strong>Messages:</strong></p>
                    <ul>
                        <?php foreach ($results['messages'] as $message): ?>
                            <li><?php echo esc_html($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (!empty($results['errors'])): ?>
                    <p><strong>Errors:</strong></p>
                    <ul>
                        <?php foreach ($results['errors'] as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (!empty($results['actions_taken'])): ?>
                    <p><strong>Actions Taken:</strong></p>
                    <ul>
                        <?php foreach ($results['actions_taken'] as $action): ?>
                            <li><?php echo esc_html($action); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Fix Search Terms Query</h2>
            <p>This fix addresses the "Reference 'current_period_searches' not supported" error in the CLOE search terms query.</p>
            <p>The fix creates a special table or view that allows proper filtering and aggregation of search terms data.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('vortex_sql_fixes'); ?>
                <p>
                    <button type="submit" name="vortex_fix_search_terms" class="button button-primary">
                        Apply Search Terms Query Fix
                    </button>
                </p>
            </form>
        </div>
        
        <?php
        // Display status of fixes
        $fix_applied = get_option('vortex_search_term_stats_fixed', false);
        $using_stats = get_option('vortex_use_search_term_stats', false);
        
        if ($fix_applied || $using_stats):
        ?>
        <div class="card">
            <h2>Fix Status</h2>
            <table class="widefat">
                <tr>
                    <th>Fix Applied</th>
                    <td><?php echo $fix_applied ? esc_html($fix_applied) : 'No'; ?></td>
                </tr>
                <tr>
                    <th>Using Statistics Table/View</th>
                    <td><?php echo $using_stats ? 'Yes' : 'No'; ?></td>
                </tr>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// Hook this file into the main plugin
function vortex_initialize_sql_fixes() {
    // Check if a manual update is needed
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'vortex-sql-fixes') {
        // We're on the SQL fixes page, no need to check further
        return;
    }
    
    // If using the stats approach and not running the cron, check if cron is scheduled
    if (get_option('vortex_use_search_term_stats', false) && !defined('DOING_CRON')) {
        if (!wp_next_scheduled('vortex_update_search_term_stats')) {
            wp_schedule_event(time(), 'daily', 'vortex_update_search_term_stats');
        }
    }
}
add_action('admin_init', 'vortex_initialize_sql_fixes'); 