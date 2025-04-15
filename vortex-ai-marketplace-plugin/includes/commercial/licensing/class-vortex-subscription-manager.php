<?php
/**
 * VORTEX Subscription Manager
 * 
 * Handles SaaS subscription management
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/commercial
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * VORTEX Subscription Manager
 */
class VORTEX_Subscription_Manager {
    
    /**
     * License key option name
     * 
     * @var string
     */
    private $license_key_option;
    
    /**
     * Subscription data option name
     * 
     * @var string
     */
    private $subscription_option = 'vortex_subscription_data';
    
    /**
     * API endpoint
     * 
     * @var string
     */
    private $api_url = 'https://api.vortexmarketplace.io/v1/subscription';
    
    /**
     * License API instance
     * 
     * @var VORTEX_License_API
     */
    private $license_api;
    
    /**
     * Initialize subscription management
     * 
     * @param string $license_key_option License key option name
     */
    public function __construct($license_key_option) {
        $this->license_key_option = $license_key_option;
        $this->license_api = new VORTEX_License_API($this->api_url);
        
        // Register hooks
        add_action('admin_init', array($this, 'schedule_subscription_check'));
        add_action('vortex_subscription_check', array($this, 'check_subscription_status'));
    }
    
    /**
     * Schedule periodic subscription check
     */
    public function schedule_subscription_check() {
        if (!wp_next_scheduled('vortex_subscription_check')) {
            wp_schedule_event(time(), 'daily', 'vortex_subscription_check');
        }
    }
    
    /**
     * Check subscription status
     * 
     * @return array Subscription data
     */
    public function check_subscription_status() {
        // Get the license key
        $license_key = get_option($this->license_key_option);
        
        if (empty($license_key)) {
            return $this->get_default_subscription_data();
        }
        
        // API call to check subscription
        $response = $this->license_api->make_request('subscription_status', array(
            'license' => $license_key,
            'domain' => $this->get_site_domain()
        ));
        
        // Process response and update subscription data
        return $this->process_api_response($response);
    }
    
    /**
     * Get subscription data
     * 
     * @return array Subscription data
     */
    public function get_subscription_data() {
        $subscription_data = get_option($this->subscription_option);
        
        if (empty($subscription_data) || !is_array($subscription_data)) {
            return $this->get_default_subscription_data();
        }
        
        return $subscription_data;
    }
    
    /**
     * Get default subscription data
     * 
     * @return array Default subscription data
     */
    private function get_default_subscription_data() {
        return array(
            'status' => 'inactive',
            'plan' => 'free',
            'tier' => 'basic',
            'features' => array(
                'ai_agents' => 1,
                'api_requests' => 100,
                'blockchain_integration' => false,
                'premium_templates' => false
            ),
            'limits' => array(
                'daily_requests' => 100,
                'models' => array('basic')
            ),
            'expiry_date' => '',
            'next_payment' => '',
            'payment_method' => '',
            'last_checked' => current_time('mysql')
        );
    }
    
    /**
     * Process API response
     * 
     * @param object|WP_Error $response API response
     * @return array Subscription data
     */
    private function process_api_response($response) {
        // Default data
        $subscription_data = $this->get_default_subscription_data();
        
        // Check for error
        if (is_wp_error($response)) {
            error_log('VORTEX Subscription Error: ' . $response->get_error_message());
            $subscription_data['last_error'] = $response->get_error_message();
            update_option($this->subscription_option, $subscription_data);
            return $subscription_data;
        }
        
        // Check if request was successful
        if (!isset($response->success) || $response->success !== true) {
            $message = isset($response->message) ? $response->message : 'Unknown error';
            error_log('VORTEX Subscription Error: ' . $message);
            $subscription_data['last_error'] = $message;
            update_option($this->subscription_option, $subscription_data);
            return $subscription_data;
        }
        
        // Extract subscription data
        $subscription_data = array(
            'status' => isset($response->status) ? $response->status : 'inactive',
            'plan' => isset($response->plan) ? $response->plan : 'free',
            'tier' => isset($response->tier) ? $response->tier : 'basic',
            'features' => isset($response->features) ? (array) $response->features : $subscription_data['features'],
            'limits' => isset($response->limits) ? (array) $response->limits : $subscription_data['limits'],
            'expiry_date' => isset($response->expiry_date) ? $response->expiry_date : '',
            'next_payment' => isset($response->next_payment) ? $response->next_payment : '',
            'payment_method' => isset($response->payment_method) ? $response->payment_method : '',
            'last_checked' => current_time('mysql')
        );
        
        // Store subscription data
        update_option($this->subscription_option, $subscription_data);
        
        return $subscription_data;
    }
    
    /**
     * Check if subscription is active
     * 
     * @return bool True if active, false otherwise
     */
    public function is_subscription_active() {
        $subscription_data = $this->get_subscription_data();
        return ($subscription_data['status'] === 'active');
    }
    
    /**
     * Check if feature is enabled
     * 
     * @param string $feature Feature name
     * @return bool|mixed Feature value or false if not enabled
     */
    public function is_feature_enabled($feature) {
        $subscription_data = $this->get_subscription_data();
        
        if (!isset($subscription_data['features'][$feature])) {
            return false;
        }
        
        return $subscription_data['features'][$feature];
    }
    
    /**
     * Get feature limit
     * 
     * @param string $limit Limit name
     * @return mixed|null Limit value or null if not set
     */
    public function get_limit($limit) {
        $subscription_data = $this->get_subscription_data();
        
        if (!isset($subscription_data['limits'][$limit])) {
            return null;
        }
        
        return $subscription_data['limits'][$limit];
    }
    
    /**
     * Get days until subscription expires
     * 
     * @return int|false Number of days or false if no expiry
     */
    public function get_days_until_expiry() {
        $subscription_data = $this->get_subscription_data();
        
        if (empty($subscription_data['expiry_date'])) {
            return false;
        }
        
        $expiry_time = strtotime($subscription_data['expiry_date']);
        $current_time = time();
        
        if ($expiry_time < $current_time) {
            return 0; // Already expired
        }
        
        return floor(($expiry_time - $current_time) / DAY_IN_SECONDS);
    }
    
    /**
     * Get subscription plan display name
     * 
     * @return string Plan display name
     */
    public function get_plan_name() {
        $subscription_data = $this->get_subscription_data();
        
        $plan_names = array(
            'free' => __('Free', 'vortex-ai-marketplace'),
            'basic' => __('Basic', 'vortex-ai-marketplace'),
            'pro' => __('Professional', 'vortex-ai-marketplace'),
            'business' => __('Business', 'vortex-ai-marketplace'),
            'enterprise' => __('Enterprise', 'vortex-ai-marketplace')
        );
        
        if (isset($plan_names[$subscription_data['plan']])) {
            return $plan_names[$subscription_data['plan']];
        }
        
        return $subscription_data['plan'];
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