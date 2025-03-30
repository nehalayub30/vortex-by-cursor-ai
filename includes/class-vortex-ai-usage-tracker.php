<?php
/**
 * AI Usage Tracking
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class for tracking AI API usage
 */
class Vortex_AI_Usage_Tracker {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('vortex_ai_api_request', array($this, 'log_api_request'), 10, 3);
        
        // Register cleanup cron event
        if (!wp_next_scheduled('vortex_ai_logs_cleanup')) {
            wp_schedule_event(time(), 'weekly', 'vortex_ai_logs_cleanup');
        }
        
        add_action('vortex_ai_logs_cleanup', array($this, 'cleanup_old_logs'));
    }
    
    /**
     * Log AI API request
     *
     * @param string $provider Provider name
     * @param array $request Request data
     * @param array $response Response data 
     */
    public function log_api_request($provider, $request, $response) {
        $ai_settings = get_option('vortex_ai_settings', array());
        
        // Only log if enabled
        if (empty($ai_settings['log_requests'])) {
            return;
        }
        
        // Create entry
        $entry = array(
            'timestamp' => current_time('mysql'),
            'provider' => $provider,
            'task' => isset($request['task']) ? $request['task'] : 'general',
            'tokens_used' => $this->calculate_tokens_used($provider, $response),
            'user_id' => get_current_user_id(),
            'status' => isset($response['error']) ? 'error' : 'success',
        );
        
        // Store in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_ai_usage_logs';
        
        $wpdb->insert(
            $table_name,
            $entry,
            array('%s', '%s', '%s', '%d', '%d', '%s')
        );
        
        // Update usage totals
        $this->update_usage_metrics($provider, $entry['tokens_used']);
    }
    
    /**
     * Calculate tokens used in a request
     * 
     * @param string $provider Provider name
     * @param array $response Response data
     * @return int Token count
     */
    private function calculate_tokens_used($provider, $response) {
        // Each provider reports token usage differently
        if (isset($response['raw_response'])) {
            switch ($provider) {
                case 'openai':
                    return isset($response['raw_response']['usage']['total_tokens']) ? 
                        intval($response['raw_response']['usage']['total_tokens']) : 0;
                
                case 'anthropic':
                    return isset($response['raw_response']['usage']['input_tokens']) && 
                           isset($response['raw_response']['usage']['output_tokens']) ? 
                        intval($response['raw_response']['usage']['input_tokens']) + 
                        intval($response['raw_response']['usage']['output_tokens']) : 0;
                
                case 'google':
                case 'grok':
                    // Estimate based on content length if not provided directly
                    return isset($response['content']) ? 
                        intval(strlen($response['content']) / 4) : 0;
                
                case 'huraii':
                case 'cloe':
                case 'strategist':
                    // Internal systems use custom metrics
                    return isset($response['raw_response']['token_count']) ? 
                        intval($response['raw_response']['token_count']) : 0;
            }
        }
        
        return 0;
    }
    
    /**
     * Update usage metrics
     * 
     * @param string $provider Provider name
     * @param int $tokens_used Token count
     */
    private function update_usage_metrics($provider, $tokens_used) {
        $metrics = get_option('vortex_ai_usage_metrics', array());
        
        if (!isset($metrics[$provider])) {
            $metrics[$provider] = array(
                'total_tokens' => 0,
                'total_requests' => 0,
                'last_request' => '',
            );
        }
        
        $metrics[$provider]['total_tokens'] += $tokens_used;
        $metrics[$provider]['total_requests']++;
        $metrics[$provider]['last_request'] = current_time('mysql');
        
        update_option('vortex_ai_usage_metrics', $metrics);
    }
    
    /**
     * Create usage logging table on activation
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_ai_usage_logs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL,
            provider varchar(50) NOT NULL,
            task varchar(50) NOT NULL,
            tokens_used int(11) NOT NULL DEFAULT 0,
            user_id bigint(20) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'success',
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Clean up old logs to prevent database growth
     */
    public function cleanup_old_logs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_ai_usage_logs';
        
        // Get retention period from settings (default to 30 days)
        $ai_settings = get_option('vortex_ai_settings', array());
        $retention_days = isset($ai_settings['log_retention_days']) ? intval($ai_settings['log_retention_days']) : 30;
        
        // Ensure a minimum retention period
        $retention_days = max(7, $retention_days);
        
        // Delete logs older than the retention period
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE timestamp < %s",
                date('Y-m-d H:i:s', strtotime("-{$retention_days} days"))
            )
        );
        
        // Log the cleanup operation
        error_log(sprintf(
            'Vortex AI Marketplace: Cleaned up AI usage logs older than %d days. Removed %d records.',
            $retention_days,
            $wpdb->rows_affected
        ));
    }
    
    /**
     * Remove scheduled cleanup on plugin deactivation
     */
    public static function deactivate_cleanup() {
        $timestamp = wp_next_scheduled('vortex_ai_logs_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'vortex_ai_logs_cleanup');
        }
    }
} 