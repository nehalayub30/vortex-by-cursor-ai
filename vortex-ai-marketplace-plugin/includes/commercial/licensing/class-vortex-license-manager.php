<?php
/**
 * VORTEX License Management
 * 
 * Handles license activation, validation, and updates for all VORTEX products
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/commercial
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * VORTEX License Management
 */
class VORTEX_License_Manager {
    
    /**
     * License key option name
     * 
     * @var string
     */
    private $license_key_option;
    
    /**
     * License status option name
     * 
     * @var string
     */
    private $license_status_option;

    /**
     * Product slug
     * 
     * @var string
     */
    private $product_slug;

    /**
     * Product version
     * 
     * @var string
     */
    private $product_version;

    /**
     * Product name
     * 
     * @var string
     */
    private $product_name;
    
    /**
     * License API endpoint
     * 
     * @var string
     */
    private $api_endpoint = 'https://api.vortexmarketplace.io/v1/license';
    
    /**
     * Initialize license management
     * 
     * @param string $license_key_option    Option name for license key
     * @param string $license_status_option Option name for license status
     * @param string $product_slug          Product slug
     * @param string $product_version       Product version
     * @param string $product_name          Product name
     */
    public function __construct($license_key_option, $license_status_option, $product_slug, $product_version, $product_name) {
        $this->license_key_option = $license_key_option;
        $this->license_status_option = $license_status_option;
        $this->product_slug = $product_slug;
        $this->product_version = $product_version;
        $this->product_name = $product_name;
        
        // Add hooks for license management
        add_action('admin_init', array($this, 'schedule_license_check'));
    }
    
    /**
     * Schedule periodic license check
     */
    public function schedule_license_check() {
        if (!wp_next_scheduled('vortex_license_check')) {
            wp_schedule_event(time(), 'daily', 'vortex_license_check');
            add_action('vortex_license_check', array($this, 'check_license_status'));
        }
    }
    
    /**
     * Activate license
     * 
     * @param string $license_key The license key to activate
     * @return array Response containing success status and message
     */
    public function activate_license($license_key) {
        // Validate license key format
        if (!$this->is_valid_license_format($license_key)) {
            return array(
                'success' => false,
                'message' => __('Invalid license key format.', 'vortex-ai-marketplace')
            );
        }
        
        // API call to validate license
        $response = wp_remote_post($this->api_endpoint, array(
            'timeout' => 15,
            'sslverify' => true,
            'body' => array(
                'action' => 'activate',
                'license' => $license_key,
                'domain' => $this->get_site_domain(),
                'product' => $this->product_slug,
                'version' => $this->product_version,
                'instance' => $this->get_site_identifier()
            )
        ));
        
        // Process response and save status
        return $this->process_api_response($response, $license_key);
    }
    
    /**
     * Deactivate license
     * 
     * @return array Response containing success status and message
     */
    public function deactivate_license() {
        // Get the license key
        $license_key = get_option($this->license_key_option);
        
        if (empty($license_key)) {
            return array(
                'success' => false,
                'message' => __('No license key found.', 'vortex-ai-marketplace')
            );
        }
        
        // API call to deactivate license
        $response = wp_remote_post($this->api_endpoint, array(
            'timeout' => 15,
            'sslverify' => true,
            'body' => array(
                'action' => 'deactivate',
                'license' => $license_key,
                'domain' => $this->get_site_domain(),
                'product' => $this->product_slug,
                'instance' => $this->get_site_identifier()
            )
        ));
        
        // Check for error
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        // Parse response
        $license_data = json_decode(wp_remote_retrieve_body($response));
        
        // Check if deactivation was successful
        if (!isset($license_data->success) || $license_data->success !== true) {
            return array(
                'success' => false,
                'message' => isset($license_data->message) ? $license_data->message : __('License deactivation failed.', 'vortex-ai-marketplace')
            );
        }
        
        // Update license status and key
        update_option($this->license_status_option, 'inactive');
        
        return array(
            'success' => true,
            'message' => __('License successfully deactivated.', 'vortex-ai-marketplace')
        );
    }
    
    /**
     * Check license status
     * 
     * @return array Response containing success status and message
     */
    public function check_license_status() {
        // Get the license key
        $license_key = get_option($this->license_key_option);
        
        if (empty($license_key)) {
            update_option($this->license_status_option, 'invalid');
            return array(
                'success' => false,
                'message' => __('No license key found.', 'vortex-ai-marketplace')
            );
        }
        
        // API call to check license
        $response = wp_remote_post($this->api_endpoint, array(
            'timeout' => 15,
            'sslverify' => true,
            'body' => array(
                'action' => 'check_status',
                'license' => $license_key,
                'domain' => $this->get_site_domain(),
                'product' => $this->product_slug,
                'version' => $this->product_version,
                'instance' => $this->get_site_identifier()
            )
        ));
        
        // Process response
        return $this->process_api_response($response, $license_key);
    }
    
    /**
     * Process API response
     * 
     * @param array|WP_Error $response    The API response
     * @param string         $license_key The license key
     * @return array Response containing success status and message
     */
    private function process_api_response($response, $license_key) {
        // Check for error
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        // Parse response
        $license_data = json_decode(wp_remote_retrieve_body($response));
        
        // Check if request was successful
        if (!isset($license_data->success)) {
            return array(
                'success' => false,
                'message' => __('Invalid response from the license server.', 'vortex-ai-marketplace')
            );
        }
        
        // Handle unsuccessful requests
        if ($license_data->success !== true) {
            $message = isset($license_data->message) ? $license_data->message : __('License validation failed.', 'vortex-ai-marketplace');
            
            update_option($this->license_status_option, 'invalid');
            
            return array(
                'success' => false,
                'message' => $message
            );
        }
        
        // Update license data
        update_option($this->license_key_option, $license_key);
        update_option($this->license_status_option, $license_data->license);
        
        // Store license expiry
        if (isset($license_data->expires)) {
            update_option($this->license_key_option . '_expires', $license_data->expires);
        }
        
        // Store license data
        if (isset($license_data->features) && is_object($license_data->features)) {
            update_option('vortex_license_features', json_encode($license_data->features));
        }
        
        $message = '';
        switch ($license_data->license) {
            case 'valid':
                $message = __('License activated successfully!', 'vortex-ai-marketplace');
                break;
            case 'inactive':
                $message = __('License is inactive.', 'vortex-ai-marketplace');
                break;
            case 'expired':
                $message = isset($license_data->expires) 
                    ? sprintf(__('License expired on %s.', 'vortex-ai-marketplace'), date_i18n(get_option('date_format'), strtotime($license_data->expires)))
                    : __('License has expired.', 'vortex-ai-marketplace');
                break;
            case 'site_inactive':
                $message = __('License is not active for this site.', 'vortex-ai-marketplace');
                break;
            case 'disabled':
                $message = __('License has been disabled.', 'vortex-ai-marketplace');
                break;
            case 'invalid':
                $message = __('License key is invalid.', 'vortex-ai-marketplace');
                break;
            default:
                $message = __('License status: ', 'vortex-ai-marketplace') . $license_data->license;
        }
        
        return array(
            'success' => ($license_data->license === 'valid'),
            'message' => $message,
            'license_data' => $license_data
        );
    }
    
    /**
     * Validate license key format
     * 
     * @param string $license_key The license key to validate
     * @return bool True if valid, false otherwise
     */
    private function is_valid_license_format($license_key) {
        // Simple format validation: XXX-XXX-XXX-XXX
        return preg_match('/^[A-Z0-9]{4}(-[A-Z0-9]{4}){3}$/', $license_key);
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
    
    /**
     * Get site identifier
     * 
     * @return string A unique identifier for the site
     */
    private function get_site_identifier() {
        $identifier = get_option('vortex_site_identifier');
        
        if (!$identifier) {
            $identifier = md5($this->get_site_domain() . get_bloginfo('admin_email'));
            update_option('vortex_site_identifier', $identifier);
        }
        
        return $identifier;
    }
    
    /**
     * Check if license is valid
     * 
     * @return bool True if valid, false otherwise
     */
    public function is_license_valid() {
        return get_option($this->license_status_option) === 'valid';
    }
    
    /**
     * Get license expiration date
     * 
     * @return string|bool Expiration date or false if not set
     */
    public function get_license_expiry() {
        return get_option($this->license_key_option . '_expires', false);
    }
    
    /**
     * Get days until license expires
     * 
     * @return int|bool Number of days or false if expiry is not set
     */
    public function get_days_until_expiry() {
        $expiry = $this->get_license_expiry();
        
        if (!$expiry) {
            return false;
        }
        
        $expiry_time = strtotime($expiry);
        $current_time = time();
        
        if ($expiry_time < $current_time) {
            return 0; // Already expired
        }
        
        return floor(($expiry_time - $current_time) / DAY_IN_SECONDS);
    }
} 