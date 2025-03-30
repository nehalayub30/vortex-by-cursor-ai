<?php
/**
 * Thorius Error Recovery System
 * 
 * Implements advanced error recovery and resilience strategies
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Error Recovery System
 */
class Vortex_Thorius_Recovery {
    /**
     * Maximum retry attempts
     */
    private $max_retries = 3;
    
    /**
     * Error tracking table
     */
    private $error_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->error_table = $wpdb->prefix . 'vortex_thorius_errors';
        
        // Get max retries from settings
        $this->max_retries = get_option('vortex_thorius_max_retries', 3);
        
        // Register recovery filters and actions
        add_filter('vortex_thorius_api_error', array($this, 'handle_api_error'), 10, 3);
        add_filter('vortex_thorius_agent_error', array($this, 'handle_agent_error'), 10, 3);
        
        // Schedule recovery attempts for critical errors
        add_action('vortex_thorius_retry_critical_operations', array($this, 'retry_critical_operations'));
        
        if (!wp_next_scheduled('vortex_thorius_retry_critical_operations')) {
            wp_schedule_event(time(), 'hourly', 'vortex_thorius_retry_critical_operations');
        }
    }
    
    /**
     * Handle API errors with recovery strategies
     * 
     * @param WP_Error $error Original error
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @return WP_Error|array Error or recovered response
     */
    public function handle_api_error($error, $endpoint, $params) {
        // Log error
        $this->log_error('api', $error->get_error_code(), $error->get_error_message(), [
            'endpoint' => $endpoint,
            'params' => $params
        ]);
        
        // Handle rate limiting with exponential backoff
        if ($error->get_error_code() === 'rate_limit_exceeded') {
            return $this->handle_rate_limit($error, $endpoint, $params);
        }
        
        // Handle temporary API outages
        if (in_array($error->get_error_code(), ['timeout', 'server_error'])) {
            return $this->handle_temporary_outage($error, $endpoint, $params);
        }
        
        // Handle different error types
        if ($error->get_error_code() === 'auth_error' || $error->get_error_code() === 401) {
            return $this->handle_auth_error($error, $endpoint, $params);
        }
        
        // Return original error for unhandled cases
        return $error;
    }
    
    /**
     * Handle agent errors with recovery strategies
     * 
     * @param WP_Error $error Original error
     * @param string $agent Agent ID
     * @param array $query_data Query data
     * @return WP_Error|array Error or recovered response
     */
    public function handle_agent_error($error, $agent, $query_data) {
        // Get error code and message
        $code = $error->get_error_code();
        $message = $error->get_error_message();
        
        // Log the error
        $this->log_error('agent', $code, $message, array(
            'agent' => $agent,
            'query' => $query_data
        ));
        
        // Try to use a fallback agent
        if (in_array($code, array('agent_unavailable', 'processing_error'))) {
            return $this->switch_to_fallback_agent($error, $agent, $query_data);
        }
        
        // Handle context processing errors
        if ($code === 'context_error') {
            return $this->handle_context_error($error, $agent, $query_data);
        }
        
        // Return original error for unhandled cases
        return $error;
    }
    
    /**
     * Handle rate limiting with exponential backoff
     * 
     * @param WP_Error $error Original error
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @return WP_Error|array Error or recovered response
     */
    private function handle_rate_limit($error, $endpoint, $params) {
        // Get retry count from error data
        $retry_count = isset($params['_retry_count']) ? $params['_retry_count'] : 0;
        
        // Check if we've hit max retries
        if ($retry_count >= $this->max_retries) {
            return new WP_Error(
                'max_retries_exceeded',
                __('Maximum retry attempts exceeded. Please try again later.', 'vortex-ai-marketplace'),
                array('retry_count' => $retry_count)
            );
        }
        
        // Calculate backoff time (exponential with jitter)
        $base_delay = pow(2, $retry_count) * 1000; // milliseconds
        $max_delay = min($base_delay * 1.5, 30000); // cap at 30 seconds
        $jitter = mt_rand(0, 1000) / 1000; // random between 0-1
        $delay = ($base_delay + ($max_delay - $base_delay) * $jitter) / 1000; // in seconds
        
        // Log the retry attempt
        error_log(sprintf(
            'Thorius AI: Rate limit hit. Retrying %s in %.2f seconds (attempt %d/%d)',
            $endpoint,
            $delay,
            $retry_count + 1,
            $this->max_retries
        ));
        
        // Sleep for the calculated delay
        sleep((int)$delay);
        
        // Increment retry count
        $params['_retry_count'] = $retry_count + 1;
        
        // Retry the request
        $api = new Vortex_Thorius_API_Manager();
        return $api->request($endpoint, $params);
    }
    
    /**
     * Handle temporary API outages
     * 
     * @param WP_Error $error Original error
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @return WP_Error|array Error or recovered response
     */
    private function handle_temporary_outage($error, $endpoint, $params) {
        // Get retry count from error data
        $retry_count = isset($params['_retry_count']) ? $params['_retry_count'] : 0;
        
        // Check if we've hit max retries
        if ($retry_count >= $this->max_retries) {
            // Store in recovery queue for later retry if critical
            if ($this->is_critical_operation($endpoint, $params)) {
                $this->queue_for_recovery($endpoint, $params);
            }
            
            return new WP_Error(
                'service_unavailable',
                __('The AI service is temporarily unavailable. Please try again later.', 'vortex-ai-marketplace'),
                array('original_error' => $error->get_error_message())
            );
        }
        
        // Use a fixed delay for server errors (5 seconds)
        sleep(5);
        
        // Increment retry count
        $params['_retry_count'] = $retry_count + 1;
        
        // Retry the request
        $api = new Vortex_Thorius_API_Manager();
        return $api->request($endpoint, $params);
    }
    
    /**
     * Handle authentication errors
     * 
     * @param WP_Error $error Original error
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @return WP_Error|array Error or recovered response
     */
    private function handle_auth_error($error, $endpoint, $params) {
        // Try to refresh authentication if possible
        $api = new Vortex_Thorius_API_Manager();
        $refreshed = $api->refresh_authentication();
        
        if ($refreshed) {
            // Retry the request with refreshed credentials
            return $api->request($endpoint, $params);
        }
        
        // Send admin notification about auth issues
        $this->notify_admin_of_auth_issues($error, $endpoint);
        
        return new WP_Error(
            'authentication_error',
            __('There was an authentication error. Please check your API credentials.', 'vortex-ai-marketplace'),
            array('original_error' => $error->get_error_message())
        );
    }
    
    /**
     * Switch to a fallback agent when primary agent fails
     * 
     * @param WP_Error $error Original error
     * @param string $agent Agent ID
     * @param array $query_data Query data
     * @return WP_Error|array Error or recovered response
     */
    private function switch_to_fallback_agent($error, $agent, $query_data) {
        $fallbacks = [
            'cloe' => 'huraii',
            'huraii' => 'cloe',
            'strategist' => 'cloe'
        ];
        
        if (isset($fallbacks[$agent]) && !isset($query_data['_using_fallback'])) {
            $query_data['_using_fallback'] = true;
            // Switch to fallback agent
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/agents/class-vortex-thorius-' . $fallbacks[$agent] . '.php';
            $fallback_class = 'Vortex_Thorius_' . strtoupper($fallbacks[$agent]);
            
            if (class_exists($fallback_class)) {
                $api_manager = new Vortex_Thorius_API_Manager();
                $fallback_agent = new $fallback_class($api_manager);
                
                // Process with fallback agent
                try {
                    $response = $fallback_agent->process_query($query_data['query'], $query_data['context'] ?? []);
                    
                    // Add fallback note to response
                    $response['fallback_used'] = true;
                    $response['original_agent'] = $agent;
                    
                    return $response;
                } catch (Exception $e) {
                    // Fallback also failed, return original error
                    return $error;
                }
            }
        }
        
        return $error;
    }
    
    /**
     * Log error for tracking and analysis
     * 
     * @param string $type Error type
     * @param string $code Error code
     * @param string $message Error message
     * @param array $data Additional error data
     */
    private function log_error($type, $code, $message, $data = array()) {
        global $wpdb;
        
        $wpdb->insert(
            $this->error_table,
            array(
                'error_type' => $type,
                'error_code' => $code,
                'error_message' => $message,
                'error_data' => maybe_serialize($data),
                'timestamp' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'resolved' => 0
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%d')
        );
    }
    
    /**
     * Check if an operation is critical and should be retried later
     * 
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @return bool Whether operation is critical
     */
    private function is_critical_operation($endpoint, $params) {
        // Define critical operations
        $critical_endpoints = array(
            'openai/chat/completions' => true,
            'openai/audio/transcriptions' => false,
            'thorius/admin/report' => true,
            'thorius/synthesis' => true
        );
        
        // Check if endpoint is critical
        return isset($critical_endpoints[$endpoint]) && $critical_endpoints[$endpoint];
    }
    
    /**
     * Queue operation for later recovery
     * 
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     */
    private function queue_for_recovery($endpoint, $params) {
        $recovery_queue = get_option('vortex_thorius_recovery_queue', array());
        
        $recovery_queue[] = array(
            'endpoint' => $endpoint,
            'params' => $params,
            'queued_at' => time()
        );
        
        update_option('vortex_thorius_recovery_queue', $recovery_queue);
    }
    
    /**
     * Retry critical operations that failed
     */
    public function retry_critical_operations() {
        $recovery_queue = get_option('vortex_thorius_recovery_queue', array());
        
        if (empty($recovery_queue)) {
            return;
        }
        
        $api = new Vortex_Thorius_API_Manager();
        $new_queue = array();
        
        foreach ($recovery_queue as $operation) {
            // Skip if operation is too old (24 hours)
            if (time() - $operation['queued_at'] > 86400) {
                continue;
            }
            
            // Try to execute the operation
            $result = $api->request($operation['endpoint'], $operation['params']);
            
            // If still failing, keep in queue
            if (is_wp_error($result)) {
                $new_queue[] = $operation;
            } else {
                // Log successful recovery
                error_log(sprintf(
                    'Thorius AI: Successfully recovered operation to %s',
                    $operation['endpoint']
                ));
                
                // Handle successful recovery (e.g., store report, update data)
                $this->handle_successful_recovery($operation['endpoint'], $operation['params'], $result);
            }
        }
        
        // Update queue with remaining operations
        update_option('vortex_thorius_recovery_queue', $new_queue);
    }
    
    /**
     * Handle successful recovery of an operation
     * 
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @param array $result Operation result
     */
    private function handle_successful_recovery($endpoint, $params, $result) {
        // Implement recovery logic based on endpoint
        if ($endpoint === 'thorius/admin/report') {
            // Store recovered report
            $report_manager = new Vortex_Thorius_Report();
            $report_manager->store_report($result);
        } else if ($endpoint === 'thorius/synthesis') {
            // Store recovered synthesis
            $synthesis_manager = new Vortex_Thorius_Synthesis();
            $synthesis_manager->store_synthesis($result);
        }
    }
    
    /**
     * Notify admin of authentication issues
     * 
     * @param WP_Error $error Original error
     * @param string $endpoint API endpoint
     */
    private function notify_admin_of_auth_issues($error, $endpoint) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(__('[%s] Thorius AI Authentication Error', 'vortex-ai-marketplace'), $site_name);
        
        $message = __('Thorius AI encountered an authentication error when trying to access the API.', 'vortex-ai-marketplace') . "\n\n";
        $message .= __('Error details:', 'vortex-ai-marketplace') . "\n";
        $message .= __('Endpoint:', 'vortex-ai-marketplace') . ' ' . $endpoint . "\n";
        $message .= __('Error message:', 'vortex-ai-marketplace') . ' ' . $error->get_error_message() . "\n\n";
        $message .= __('Please check your API credentials in the Thorius AI settings.', 'vortex-ai-marketplace') . "\n";
        $message .= admin_url('admin.php?page=vortex-thorius-settings');
        
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Register error management tabs
     */
    private function register_error_tabs() {
        return array(
            'active' => array(
                'title' => __('Active Errors', 'vortex-ai-marketplace'),
                'callback' => array($this, 'render_active_errors_tab')
            ),
            'resolved' => array(
                'title' => __('Resolved', 'vortex-ai-marketplace'),
                'callback' => array($this, 'render_resolved_errors_tab')
            ),
            'recovery' => array(
                'title' => __('Recovery Queue', 'vortex-ai-marketplace'),
                'callback' => array($this, 'render_recovery_queue_tab')
            ),
            'analytics' => array(
                'title' => __('Error Analytics', 'vortex-ai-marketplace'),
                'callback' => array($this, 'render_error_analytics_tab')
            )
        );
    }

    /**
     * Render error management interface
     */
    public function render_error_management() {
        $tabs = $this->register_error_tabs();
        $container_id = 'thorius-error-tabs';
        $current_tab = $this->get_saved_tab_state($container_id, 'active');
        
        echo '<div class="wrap thorius-error-management">';
        echo '<h1>' . __('Error Management', 'vortex-ai-marketplace') . '</h1>';
        
        echo '<div id="' . esc_attr($container_id) . '" class="thorius-tabs" data-persistent="true">';
        echo '<nav class="thorius-tab-nav">';
        foreach ($tabs as $tab_id => $tab) {
            printf(
                '<button class="thorius-tab-button %s" data-tab="%s">%s</button>',
                ($current_tab === $tab_id) ? 'active' : '',
                esc_attr($tab_id),
                esc_html($tab['title'])
            );
        }
        echo '</nav>';
        
        echo '<div class="thorius-tab-content-wrapper">';
        foreach ($tabs as $tab_id => $tab) {
            printf(
                '<div id="%s-content" class="thorius-tab-content" style="display: %s;">',
                esc_attr($tab_id),
                ($current_tab === $tab_id) ? 'block' : 'none'
            );
            if (isset($tab['callback'])) {
                call_user_func($tab['callback']);
            }
            echo '</div>';
        }
        echo '</div>';
        
        echo '</div>'; // Close tabs container
        echo '</div>'; // Close wrap
    }

    /**
     * Get saved tab state
     */
    private function get_saved_tab_state($container_id, $default = '') {
        $user_id = get_current_user_id();
        return get_user_meta($user_id, "thorius_tab_state_{$container_id}", true) ?: $default;
    }
} 