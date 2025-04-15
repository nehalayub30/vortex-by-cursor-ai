<?php
/**
 * SQL Fixes for VORTEX AI Marketplace
 *
 * This file contains fixes for SQL queries that cause "Reference not supported" errors
 * These fixes replace alias references in HAVING clauses with their original expressions
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/sql-fixes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Fixed version of get_emerging_themes method from VORTEX_CLOE
 * 
 * @param string $period Time period for comparison
 * @return array Emerging themes data
 */
function vortex_fixed_get_emerging_themes($period = 'month') {
    try {
        global $wpdb;
        
        // Get the time constraints based on the period
        $current_period = '';
        $previous_period = '';
        
        if (method_exists('VORTEX_CLOE', 'get_time_constraint')) {
            $cloe = VORTEX_CLOE::get_instance();
            $current_period = $cloe->get_time_constraint($period);
            $previous_period = $cloe->get_time_constraint($period, true);
        } else {
            // Fallback if method doesn't exist
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
            
            $current_period = date('Y-m-d H:i:s', $now - $interval);
            $previous_period = date('Y-m-d H:i:s', $now - (2 * $interval));
        }
        
        // Execute the fixed query that doesn't use alias references in HAVING
        $query = $wpdb->prepare(
            "SELECT 
                t.theme_id,
                t.theme_name,
                COUNT(DISTINCT CASE WHEN tr.transaction_time >= %s THEN tr.transaction_id ELSE NULL END) as current_purchases,
                COUNT(DISTINCT CASE WHEN tr.transaction_time >= %s AND tr.transaction_time < %s THEN tr.transaction_id ELSE NULL END) as previous_purchases
            FROM {$wpdb->prefix}vortex_artwork_themes t
            JOIN {$wpdb->prefix}vortex_artwork_theme_mapping tm ON t.theme_id = tm.theme_id
            JOIN {$wpdb->prefix}vortex_artworks a ON tm.artwork_id = a.artwork_id
            LEFT JOIN {$wpdb->prefix}vortex_transactions tr ON a.artwork_id = tr.artwork_id AND tr.status = 'completed'
            GROUP BY t.theme_id, t.theme_name
            HAVING COUNT(DISTINCT CASE WHEN tr.transaction_time >= %s THEN tr.transaction_id ELSE NULL END) > 
                   COUNT(DISTINCT CASE WHEN tr.transaction_time >= %s AND tr.transaction_time < %s THEN tr.transaction_id ELSE NULL END)
            ORDER BY (COUNT(DISTINCT CASE WHEN tr.transaction_time >= %s THEN tr.transaction_id ELSE NULL END) - 
                     COUNT(DISTINCT CASE WHEN tr.transaction_time >= %s AND tr.transaction_time < %s THEN tr.transaction_id ELSE NULL END)) DESC",
            $current_period,
            $previous_period,
            $current_period,
            $current_period,
            $previous_period,
            $current_period,
            $current_period,
            $previous_period,
            $current_period
        );
        
        $results = $wpdb->get_results($query);
        
        // Calculate growth percentage
        foreach ($results as &$theme) {
            $theme->growth_percentage = $theme->previous_purchases > 0 
                ? (($theme->current_purchases - $theme->previous_purchases) / $theme->previous_purchases) * 100 
                : ($theme->current_purchases > 0 ? 100 : 0);
        }
        
        return array(
            'status' => 'success',
            'data' => $results
        );
    } catch (Exception $e) {
        return array(
            'status' => 'error',
            'message' => $e->getMessage()
        );
    }
}

/**
 * Hook the fixed method to replace the original
 */
function vortex_replace_cloe_methods() {
    // Only apply fixes if the class exists
    if (!class_exists('VORTEX_CLOE')) {
        return;
    }
    
    // Get CLOE instance
    $cloe = VORTEX_CLOE::get_instance();
    
    // Override the method with the fixed version
    // Note: This requires that the original class has a method_override option
    if (method_exists($cloe, 'set_method_override')) {
        $cloe->set_method_override('get_emerging_themes', 'vortex_fixed_get_emerging_themes');
    }
}

// Apply the fixes early when WordPress initializes
add_action('init', 'vortex_replace_cloe_methods', 5);

/**
 * Generic function to fix SQL queries that have alias references in HAVING clauses
 * 
 * @param string $query SQL query to fix
 * @return string Fixed SQL query
 */
function vortex_fix_having_references($query) {
    // Common patterns that need fixing
    $patterns = array(
        '/HAVING\s+current_purchases\s+>\s+previous_purchases/i' => 'HAVING COUNT(DISTINCT CASE WHEN tr.transaction_time >= %current_period% THEN tr.transaction_id ELSE NULL END) > COUNT(DISTINCT CASE WHEN tr.transaction_time >= %previous_period% AND tr.transaction_time < %current_period% THEN tr.transaction_id ELSE NULL END)',
        '/HAVING\s+search_count\s+>\s+(\d+)/i' => 'HAVING COUNT(*) > $1',
        '/HAVING\s+view_count\s+>\s+(\d+)/i' => 'HAVING COUNT(DISTINCT v.view_id) > $1',
        '/HAVING\s+artwork_count\s+>\s+(\d+)/i' => 'HAVING COUNT(DISTINCT a.artwork_id) > $1',
        '/HAVING\s+search_count\s+>\s+(\d+)\s+AND\s+conversions\s+>\s+(\d+)/i' => 'HAVING COUNT(*) > $1 AND SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) > $2',
        '/HAVING\s+search_count\s+>\s+(\d+)\s+AND\s+conversion_rate\s+>\s+(\d+)/i' => 'HAVING COUNT(*) > $1 AND (SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 > $2',
        '/HAVING\s+search_count\s+>\s+(\d+)\s+AND\s+avg_results\s+<\s+(\d+)/i' => 'HAVING COUNT(*) > $1 AND AVG(result_count) < $2'
    );
    
    // Apply patterns
    foreach ($patterns as $pattern => $replacement) {
        $query = preg_replace($pattern, $replacement, $query);
    }
    
    return $query;
}

/**
 * Function to fix SQL queries in wpdb before they're executed
 * 
 * @param string $query SQL query to execute
 * @return string Modified SQL query
 */
function vortex_fix_wpdb_queries($query) {
    // Only fix SELECT queries with HAVING clauses
    if (stripos($query, 'SELECT') === 0 && stripos($query, 'HAVING') !== false) {
        $query = vortex_fix_having_references($query);
    }
    
    return $query;
}

// Apply global SQL query fixer for all wpdb queries (optional - can be enabled if needed)
// add_filter('query', 'vortex_fix_wpdb_queries'); 