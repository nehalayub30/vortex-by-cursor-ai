<?php
/**
 * Fix for Vortex Thorius Analytics
 * 
 * This file fixes the "Call to undefined method Vortex_Thorius_Analytics::get_dashboard_stats()" error
 * by adding the missing method to the class if it doesn't exist.
 *
 * Instructions:
 * 1. Upload this file to your WordPress site (in the plugins/vortex-ai-marketplace/ folder)
 * 2. Access it via browser or run from command line: php fix-thorius-analytics.php
 * 3. Delete this file after successful execution
 */

// Only run in CLI or admin context for security
if (!defined('WPINC') && !defined('ABSPATH') && php_sapi_name() !== 'cli') {
    // If running in browser, require WordPress
    $wp_load_file = dirname(__FILE__) . '/../../wp-load.php';
    if (file_exists($wp_load_file)) {
        require_once($wp_load_file);
    } else {
        die("Cannot load WordPress. Please run this file from the correct location.");
    }
}

// Setup paths
if (defined('ABSPATH')) {
    $plugin_dir = defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR : ABSPATH . 'wp-content/plugins';
    $file_path = $plugin_dir . '/vortex-ai-marketplace/includes/class-vortex-thorius-analytics.php';
} else {
    // Local development / CLI context
    $file_path = dirname(__FILE__) . '/includes/class-vortex-thorius-analytics.php';
}

// Check if the file exists
if (!file_exists($file_path)) {
    die("Error: The Thorius Analytics file does not exist at: $file_path");
}

// Get the file content
$file_content = file_get_contents($file_path);

// Check if the method already exists
if (strpos($file_content, 'function get_dashboard_stats') !== false) {
    echo "The get_dashboard_stats() method already exists in the file.\n";
    exit();
}

// Add the missing method
$method_code = <<<'EOT'

    /**
     * Get analytics data for admin dashboard
     *
     * @param string $period Period to get stats for (7, 30, 90 days)
     * @return array Dashboard statistics
     */
    public function get_dashboard_stats($period = '30') {
        global $wpdb;
        
        // Calculate date range based on period
        $end_date = current_time('mysql');
        $start_date = date('Y-m-d H:i:s', strtotime("-{$period} days"));
        
        // Default stats array
        $stats = array(
            'total_queries' => 0,
            'unique_users' => 0,
            'avg_queries_per_user' => 0,
            'tokens_used' => 0,
            'agent_distribution' => array(
                'CLOE' => 0,
                'HURAII' => 0,
                'Business Strategist' => 0,
                'Thorius' => 0
            ),
            'recent_activity' => array()
        );
        
        // Get total queries
        $total_queries = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE created_at BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $stats['total_queries'] = $total_queries ?: 0;
        
        // Get unique users
        $unique_users = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$this->table_name} 
            WHERE created_at BETWEEN %s AND %s AND user_id IS NOT NULL",
            $start_date, $end_date
        ));
        
        $stats['unique_users'] = $unique_users ?: 0;
        
        // Calculate average queries per user
        $stats['avg_queries_per_user'] = ($stats['unique_users'] > 0) 
            ? ($stats['total_queries'] / $stats['unique_users']) 
            : 0;
        
        // Get token usage (requires event_data to contain token information)
        $tokens_query = $wpdb->prepare(
            "SELECT SUM(JSON_EXTRACT(event_data, '$.tokens')) as total_tokens 
            FROM {$this->table_name} 
            WHERE created_at BETWEEN %s AND %s 
            AND event_type = 'agent_request'
            AND JSON_EXTRACT(event_data, '$.tokens') IS NOT NULL",
            $start_date, $end_date
        );
        
        $tokens_used = $wpdb->get_var($tokens_query);
        $stats['tokens_used'] = $tokens_used ?: 0;
        
        // Get agent distribution
        $agent_query = $wpdb->prepare(
            "SELECT JSON_EXTRACT(event_data, '$.agent') as agent, COUNT(*) as count 
            FROM {$this->table_name} 
            WHERE created_at BETWEEN %s AND %s 
            AND event_type = 'agent_request'
            AND JSON_EXTRACT(event_data, '$.agent') IS NOT NULL
            GROUP BY agent",
            $start_date, $end_date
        );
        
        $agent_results = $wpdb->get_results($agent_query);
        
        if ($agent_results) {
            foreach ($agent_results as $row) {
                $agent = strtoupper(trim($row->agent, '"')); // Remove JSON quotes
                
                if ($agent == 'CLOE' && isset($stats['agent_distribution']['CLOE'])) {
                    $stats['agent_distribution']['CLOE'] = (int)$row->count;
                } else if ($agent == 'HURAII' && isset($stats['agent_distribution']['HURAII'])) {
                    $stats['agent_distribution']['HURAII'] = (int)$row->count;
                } else if ($agent == 'BUSINESS_STRATEGIST' || $agent == 'BUSINESS STRATEGIST' || $agent == 'STRATEGIST') {
                    $stats['agent_distribution']['Business Strategist'] = (int)$row->count;
                } else if ($agent == 'THORIUS' && isset($stats['agent_distribution']['Thorius'])) {
                    $stats['agent_distribution']['Thorius'] = (int)$row->count;
                }
            }
        }
        
        // Get recent activity (last 10 interactions)
        $recent_query = $wpdb->prepare(
            "SELECT a.*, u.display_name 
            FROM {$this->table_name} a
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            WHERE a.event_type = 'agent_request'
            AND a.created_at BETWEEN %s AND %s
            ORDER BY a.created_at DESC
            LIMIT 10",
            $start_date, $end_date
        );
        
        $recent_results = $wpdb->get_results($recent_query);
        
        if ($recent_results) {
            foreach ($recent_results as $row) {
                $event_data = json_decode($row->event_data, true);
                
                $stats['recent_activity'][] = array(
                    'time' => date_i18n(get_option('time_format'), strtotime($row->created_at)),
                    'user' => $row->display_name ?: __('Guest', 'vortex-ai-marketplace'),
                    'agent' => isset($event_data['agent']) ? strtoupper($event_data['agent']) : __('Unknown', 'vortex-ai-marketplace'),
                    'query' => isset($event_data['query']) ? 
                        (strlen($event_data['query']) > 50 ? substr($event_data['query'], 0, 50) . '...' : $event_data['query']) : 
                        __('N/A', 'vortex-ai-marketplace')
                );
            }
        }
        
        return $stats;
    }
EOT;

// Insert the method before the last closing brace
$file_content = preg_replace('/(}\s*$)/', $method_code . '$1', $file_content);

// Write the file
if (file_put_contents($file_path, $file_content)) {
    echo "Success: The get_dashboard_stats() method has been added to the Thorius Analytics class.\n";
    echo "The error should now be fixed.\n";
} else {
    echo "Error: Failed to write to the file. Check the file permissions.\n";
}
?> 