<?php
/**
 * Thorius Error Handler
 * 
 * Provides centralized error handling and logging
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Error Handler
 */
class Vortex_Thorius_Error_Handler {
    
    /**
     * Log error
     *
     * @param string $message Error message
     * @param string $type Error type
     * @param array $context Additional context
     */
    public static function log_error($message, $type = 'general', $context = array()) {
        if (!is_dir(WP_CONTENT_DIR . '/thorius-logs')) {
            mkdir(WP_CONTENT_DIR . '/thorius-logs', 0755, true);
        }
        
        $log_file = WP_CONTENT_DIR . '/thorius-logs/thorius-' . date('Y-m-d') . '.log';
        
        $timestamp = date('Y-m-d H:i:s');
        $context_string = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $log_message = "[$timestamp] [$type] $message$context_string" . PHP_EOL;
        
        error_log($log_message, 3, $log_file);
        
        // Also send critical errors to admin if enabled
        if ($type === 'critical' && get_option('vortex_thorius_notify_errors', 'no') === 'yes') {
            self::notify_admin($message, $context);
        }
    }
    
    /**
     * Log API error
     *
     * @param string $api API name
     * @param string $endpoint API endpoint
     * @param mixed $response API response
     * @param array $request Request data
     */
    public static function log_api_error($api, $endpoint, $response, $request = array()) {
        $context = array(
            'api' => $api,
            'endpoint' => $endpoint,
            'request' => $request,
            'response' => $response
        );
        
        self::log_error("API Error: $api - $endpoint", 'api', $context);
    }
    
    /**
     * Notify admin of critical errors
     *
     * @param string $message Error message
     * @param array $context Error context
     */
    private static function notify_admin($message, $context = array()) {
        $admin_email = get_option('admin_email');
        
        if (empty($admin_email)) {
            return;
        }
        
        $subject = __('Thorius AI Critical Error', 'vortex-ai-marketplace');
        
        $body = __('A critical error occurred in Thorius AI Concierge:', 'vortex-ai-marketplace') . "\n\n";
        $body .= $message . "\n\n";
        
        if (!empty($context)) {
            $body .= __('Additional context:', 'vortex-ai-marketplace') . "\n";
            $body .= json_encode($context, JSON_PRETTY_PRINT) . "\n\n";
        }
        
        $body .= __('Site URL:', 'vortex-ai-marketplace') . ' ' . home_url() . "\n";
        $body .= __('Time:', 'vortex-ai-marketplace') . ' ' . current_time('mysql') . "\n";
        
        wp_mail($admin_email, $subject, $body);
    }
} 