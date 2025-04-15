<?php
/**
 * Fix script for Vortex Thorius Analytics
 * 
 * This script verifies and updates the Thorius Analytics class to include 
 * the missing get_dashboard_stats method.
 */

// Only run in CLI or admin context for security
if (!defined('ABSPATH') && php_sapi_name() !== 'cli') {
    echo "This script must be run from the WordPress admin or CLI.";
    exit;
}

// Define paths
$file_path = defined('ABSPATH') 
    ? ABSPATH . 'wp-content/plugins/vortex-ai-marketplace/includes/class-vortex-thorius-analytics.php'
    : __DIR__ . '/includes/class-vortex-thorius-analytics.php';

// Check if file exists
if (!file_exists($file_path)) {
    echo "Error: Could not find the Thorius Analytics file at: {$file_path}\n";
    echo "Please check the path and try again.\n";
    exit;
}

// Get current file content
$file_content = file_get_contents($file_path);

// Check if method already exists
if (strpos($file_content, 'function get_dashboard_stats') !== false) {
    echo "The get_dashboard_stats method already exists in the file.\n";
    
    // Let's verify if the file is correctly loaded
    echo "Checking for potential loading issues...\n";
    
    // Check if there are any syntax errors in the file
    $temp_file = tempnam(sys_get_temp_dir(), 'vortex');
    file_put_contents($temp_file, $file_content);
    
    $output = null;
    $return_var = null;
    exec("php -l {$temp_file}", $output, $return_var);
    
    if ($return_var !== 0) {
        echo "Syntax error detected in the file! Here's the output:\n";
        echo implode("\n", $output) . "\n";
        echo "Please fix the syntax errors and try again.\n";
    } else {
        echo "No syntax errors detected. The file appears to be valid PHP.\n";
    }
    
    unlink($temp_file);
    exit;
}

// Add the method if it doesn't exist
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

// Insert method before the last closing brace
$file_content = preg_replace('/(}\s*$)/', $method_code . '$1', $file_content);

// Write the updated file
if (file_put_contents($file_path, $file_content)) {
    echo "Success: Added the get_dashboard_stats method to the Thorius Analytics class.\n";
    echo "The error should now be fixed.\n";
} else {
    echo "Error: Failed to write to the file. Please check file permissions.\n";
}
?> 