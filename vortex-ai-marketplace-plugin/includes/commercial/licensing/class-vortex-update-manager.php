<?php
/**
 * VORTEX Update Manager
 * 
 * Handles plugin update checks and information
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/commercial
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * VORTEX Update Manager
 */
class VORTEX_Update_Manager {
    
    /**
     * Product slug
     * 
     * @var string
     */
    private $product_slug;
    
    /**
     * Current version
     * 
     * @var string
     */
    private $current_version;
    
    /**
     * License key
     * 
     * @var string
     */
    private $license_key;
    
    /**
     * API endpoint
     * 
     * @var string
     */
    private $api_url = 'https://api.vortexmarketplace.io/v1/updates';
    
    /**
     * Constructor
     * 
     * @param string $product_slug     The product slug
     * @param string $current_version  The current product version
     * @param string $license_key      The license key
     * @param string $api_url          Optional custom API URL
     */
    public function __construct($product_slug, $current_version, $license_key, $api_url = '') {
        $this->product_slug = $product_slug;
        $this->current_version = $current_version;
        $this->license_key = $license_key;
        
        if (!empty($api_url)) {
            $this->api_url = $api_url;
        }
        
        // Allow API URL to be filtered
        $this->api_url = apply_filters('vortex_update_api_url', $this->api_url);
    }
    
    /**
     * Check for plugin updates
     * 
     * @return object|false Update data object or false if no update
     */
    public function check_for_updates() {
        // Make API request
        $response = $this->make_request('check_update', array(
            'slug' => $this->product_slug,
            'version' => $this->current_version
        ));
        
        // Check for error
        if (is_wp_error($response)) {
            error_log('VORTEX Update Error: ' . $response->get_error_message());
            return false;
        }
        
        // No update available
        if (!isset($response->update_available) || $response->update_available !== true) {
            return false;
        }
        
        // Prepare update information
        $update_data = new stdClass();
        $update_data->slug = $this->product_slug;
        $update_data->plugin = $this->product_slug . '/' . $this->product_slug . '.php';
        $update_data->new_version = $response->version;
        $update_data->url = isset($response->url) ? $response->url : '';
        $update_data->package = isset($response->download_link) ? $response->download_link : '';
        $update_data->tested = isset($response->tested) ? $response->tested : '';
        $update_data->requires_php = isset($response->requires_php) ? $response->requires_php : '';
        $update_data->icons = isset($response->icons) ? (array) $response->icons : array();
        $update_data->banners = isset($response->banners) ? (array) $response->banners : array();
        
        return $update_data;
    }
    
    /**
     * Get plugin information for the WordPress updates screen
     * 
     * @return object|false Plugin information object or false on error
     */
    public function get_plugin_info() {
        // Make API request
        $response = $this->make_request('plugin_information', array(
            'slug' => $this->product_slug
        ));
        
        // Check for error
        if (is_wp_error($response)) {
            error_log('VORTEX Plugin Info Error: ' . $response->get_error_message());
            return false;
        }
        
        // Prepare plugin information
        $info = new stdClass();
        $info->slug = $this->product_slug;
        $info->name = isset($response->name) ? $response->name : '';
        $info->version = isset($response->version) ? $response->version : '';
        $info->author = isset($response->author) ? $response->author : '';
        $info->author_profile = isset($response->author_profile) ? $response->author_profile : '';
        $info->requires = isset($response->requires) ? $response->requires : '';
        $info->tested = isset($response->tested) ? $response->tested : '';
        $info->requires_php = isset($response->requires_php) ? $response->requires_php : '';
        $info->last_updated = isset($response->last_updated) ? $response->last_updated : '';
        $info->homepage = isset($response->homepage) ? $response->homepage : '';
        $info->download_link = isset($response->download_link) ? $response->download_link : '';
        $info->sections = isset($response->sections) ? (array) $response->sections : array();
        $info->banners = isset($response->banners) ? (array) $response->banners : array();
        $info->icons = isset($response->icons) ? (array) $response->icons : array();
        
        return $info;
    }
    
    /**
     * Make API request
     * 
     * @param string $action The API action
     * @param array  $args   Request arguments
     * @return object|WP_Error Response object or error
     */
    private function make_request($action, $args = array()) {
        // Add common parameters to request
        $args = array_merge($args, array(
            'action' => $action,
            'license' => $this->license_key,
            'domain' => $this->get_site_domain()
        ));
        
        // Make the API request
        $response = wp_remote_post($this->api_url, array(
            'timeout' => 15,
            'sslverify' => true,
            'body' => $args
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
                sprintf(__('Invalid response from the update server (code: %d)', 'vortex-ai-marketplace'), $response_code)
            );
        }
        
        // Parse response body
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        
        if (!is_object($data)) {
            return new WP_Error(
                'invalid_response',
                __('Invalid response from the update server', 'vortex-ai-marketplace')
            );
        }
        
        return $data;
    }
    
    /**
     * Get site domain
     * 
     * @return string The site domain
     */
    private function get_site_domain() {
        $home_url = home_url();
        $domain = parse_url($home_url, PHP_URL_HOST);
        
        return $domain;
    }
} 