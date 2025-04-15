<?php
/**
 * VORTEX License API
 * 
 * Handles communication with the VORTEX licensing server
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/commercial
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * VORTEX License API
 */
class VORTEX_License_API {
    
    /**
     * API endpoint
     * 
     * @var string
     */
    private $api_url = 'https://api.vortexmarketplace.io/v1/license';
    
    /**
     * API timeout in seconds
     * 
     * @var int
     */
    private $timeout = 15;
    
    /**
     * Constructor
     * 
     * @param string $api_url Optional custom API URL
     */
    public function __construct($api_url = '') {
        if (!empty($api_url)) {
            $this->api_url = $api_url;
        }
        
        // Allow API URL to be filtered
        $this->api_url = apply_filters('vortex_license_api_url', $this->api_url);
    }
    
    /**
     * Activate license
     * 
     * @param string $license_key The license key
     * @param string $product     The product slug
     * @param string $domain      The site domain
     * @param string $instance    The site instance ID
     * @param string $version     The product version
     * @return object|WP_Error Response object or error
     */
    public function activate_license($license_key, $product, $domain, $instance, $version) {
        return $this->make_request('activate', array(
            'license'  => $license_key,
            'product'  => $product,
            'domain'   => $domain,
            'instance' => $instance,
            'version'  => $version
        ));
    }
    
    /**
     * Deactivate license
     * 
     * @param string $license_key The license key
     * @param string $product     The product slug
     * @param string $domain      The site domain
     * @param string $instance    The site instance ID
     * @return object|WP_Error Response object or error
     */
    public function deactivate_license($license_key, $product, $domain, $instance) {
        return $this->make_request('deactivate', array(
            'license'  => $license_key,
            'product'  => $product,
            'domain'   => $domain,
            'instance' => $instance
        ));
    }
    
    /**
     * Check license status
     * 
     * @param string $license_key The license key
     * @param string $product     The product slug
     * @param string $domain      The site domain
     * @param string $instance    The site instance ID
     * @param string $version     The product version
     * @return object|WP_Error Response object or error
     */
    public function check_license($license_key, $product, $domain, $instance, $version) {
        return $this->make_request('check_status', array(
            'license'  => $license_key,
            'product'  => $product,
            'domain'   => $domain,
            'instance' => $instance,
            'version'  => $version
        ));
    }
    
    /**
     * Get license information
     * 
     * @param string $license_key The license key
     * @param string $product     The product slug
     * @return object|WP_Error Response object or error
     */
    public function get_license_info($license_key, $product) {
        return $this->make_request('get_info', array(
            'license' => $license_key,
            'product' => $product
        ));
    }
    
    /**
     * Make API request
     * 
     * @param string $action The API action
     * @param array  $args   Request arguments
     * @return object|WP_Error Response object or error
     */
    private function make_request($action, $args = array()) {
        // Add action to arguments
        $args['action'] = $action;
        
        // Make the API request
        $response = wp_remote_post($this->api_url, array(
            'timeout'   => $this->timeout,
            'sslverify' => true,
            'body'      => $args
        ));
        
        // Check for error
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Check response code
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code != 200) {
            return new WP_Error(
                'invalid_response',
                sprintf(__('Invalid response from the license server (code: %d)', 'vortex-ai-marketplace'), $response_code)
            );
        }
        
        // Parse response body
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        
        if (!is_object($data)) {
            return new WP_Error(
                'invalid_response',
                __('Invalid response from the license server', 'vortex-ai-marketplace')
            );
        }
        
        return $data;
    }
    
    /**
     * Log API request for debugging
     * 
     * @param string $action   The API action
     * @param array  $args     Request arguments
     * @param mixed  $response API response
     * @return void
     */
    private function log_request($action, $args, $response) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        // Remove sensitive data
        if (isset($args['license'])) {
            $args['license'] = $this->mask_license_key($args['license']);
        }
        
        $log = array(
            'time'     => current_time('mysql'),
            'action'   => $action,
            'args'     => $args,
            'response' => $response
        );
        
        // Get existing log
        $logs = get_option('vortex_license_api_logs', array());
        
        // Limit log size
        if (count($logs) >= 10) {
            array_shift($logs);
        }
        
        // Add new log entry
        $logs[] = $log;
        
        // Update logs
        update_option('vortex_license_api_logs', $logs);
    }
    
    /**
     * Mask license key for security
     * 
     * @param string $license_key The license key
     * @return string Masked license key
     */
    private function mask_license_key($license_key) {
        if (empty($license_key)) {
            return '';
        }
        
        // Get first and last 4 characters
        $first = substr($license_key, 0, 4);
        $last = substr($license_key, -4);
        
        // Calculate masked length
        $masked_length = strlen($license_key) - 8;
        
        // Return masked key
        return $first . str_repeat('*', $masked_length) . $last;
    }
} 