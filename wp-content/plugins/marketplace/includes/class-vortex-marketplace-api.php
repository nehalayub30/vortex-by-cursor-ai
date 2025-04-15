<?php
/**
 * VORTEX Marketplace API Handler
 *
 * Manages REST API endpoints for the marketplace plugin.
 * Provides external access points for third-party integrations.
 *
 * @package VORTEX
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_Marketplace_API {

    /**
     * Instance of this class
     * @var VORTEX_Marketplace_API
     */
    private static $instance = null;
    
    /**
     * REST API namespace
     * @var string
     */
    private $namespace = 'vortex-marketplace/v1';
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize REST API routes
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Get instance of this class
     * @return VORTEX_Marketplace_API
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Register general marketplace data endpoint
        register_rest_route($this->namespace, '/marketplace', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_marketplace_data'),
                'permission_callback' => array($this, 'get_permission_check'),
            )
        ));
        
        // Register investor application endpoint
        register_rest_route($this->namespace, '/investor/apply', array(
            array(
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'submit_investor_application'),
                'permission_callback' => array($this, 'post_permission_check'),
                'args' => $this->get_investor_application_args()
            )
        ));
        
        // Register investor data endpoint
        register_rest_route($this->namespace, '/investor/(?P<id>\d+)', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_investor_data'),
                'permission_callback' => array($this, 'get_item_permissions_check'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                ),
            ),
            array(
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_investor_data'),
                'permission_callback' => array($this, 'update_item_permissions_check'),
                'args' => $this->get_investor_update_args()
            ),
        ));
        
        // Register token data endpoint
        register_rest_route($this->namespace, '/tokens', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_token_data'),
                'permission_callback' => array($this, 'get_permission_check'),
            )
        ));
        
        // Allow extensions to register their own routes
        do_action('vortex_marketplace_register_rest_routes', $this->namespace);
    }
    
    /**
     * Permission check for GET requests
     * 
     * @param WP_REST_Request $request The request object
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    public function get_permission_check($request) {
        // Allow public access to read-only data by default
        return apply_filters('vortex_marketplace_api_get_permission', true, $request);
    }
    
    /**
     * Permission check for POST requests
     * 
     * @param WP_REST_Request $request The request object
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    public function post_permission_check($request) {
        // Require valid nonce for any data modification
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error(
                'rest_forbidden',
                __('Invalid or missing nonce.', 'vortex'),
                array('status' => 403)
            );
        }
        
        // Allow third-party plugins to modify permission
        return apply_filters('vortex_marketplace_api_post_permission', true, $request);
    }
    
    /**
     * Permission check for getting a specific item
     * 
     * @param WP_REST_Request $request The request object
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    public function get_item_permissions_check($request) {
        $investor_id = $request['id'];
        
        // Get user's permissions
        $user_id = get_current_user_id();
        
        // If user is not logged in, deny access
        if (!$user_id) {
            return new WP_Error(
                'rest_forbidden',
                __('You must be logged in to access this information.', 'vortex'),
                array('status' => 401)
            );
        }
        
        // Get investor data
        $investment = VORTEX_DAO_Investment::get_instance();
        $investor_data = $investment->get_investor_by_id($investor_id);
        
        if (!$investor_data) {
            return new WP_Error(
                'rest_not_found',
                __('Investor not found.', 'vortex'),
                array('status' => 404)
            );
        }
        
        // Users can access their own investor data, admins can access any
        if ($investor_data->user_id === $user_id || current_user_can('manage_options')) {
            return true;
        }
        
        // Allow third-party plugins to modify permission
        return apply_filters('vortex_marketplace_api_get_investor_permission', false, $investor_id, $user_id, $request);
    }
    
    /**
     * Permission check for updating a specific item
     * 
     * @param WP_REST_Request $request The request object
     * @return bool|WP_Error True if permission granted, WP_Error otherwise
     */
    public function update_item_permissions_check($request) {
        // Only admins can update investor data by default
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to update investor data.', 'vortex'),
                array('status' => 403)
            );
        }
        
        // Allow third-party plugins to modify permission
        return apply_filters('vortex_marketplace_api_update_investor_permission', true, $request);
    }
    
    /**
     * Get args schema for investor application
     * 
     * @return array Args schema
     */
    protected function get_investor_application_args() {
        return array(
            'wallet_address' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return !empty($param);
                },
            ),
            'investment_amount' => array(
                'required' => true,
                'type' => 'number',
                'sanitize_callback' => 'absint',
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
            ),
            'first_name' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'last_name' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'email' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => function($param) {
                    return is_email($param);
                },
            ),
            'phone' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'accredited' => array(
                'required' => false,
                'type' => 'boolean',
                'default' => false,
            ),
        );
    }
    
    /**
     * Get args schema for investor update
     * 
     * @return array Args schema
     */
    protected function get_investor_update_args() {
        return array(
            'wallet_address' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'investment_amount' => array(
                'type' => 'number',
                'sanitize_callback' => 'absint',
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
            ),
            'kyc_status' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return in_array($param, array('pending', 'approved', 'rejected'));
                },
            ),
            'notes' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
        );
    }

    /**
     * Get marketplace data
     * 
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response Response object
     */
    public function get_marketplace_data($request) {
        // Check if we have a cached response
        $cache_key = 'vortex_api_marketplace_data';
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return rest_ensure_response($cached_data);
        }
        
        // Get total investors, total investment, and basic marketplace stats
        $investment = VORTEX_DAO_Investment::get_instance();
        
        $marketplace_data = array(
            'total_investors' => count($investment->get_all_investors()),
            'total_investment' => $investment->get_total_investment(),
            'total_tokens_allocated' => $investment->get_total_tokens_allocated(),
            'token_price' => get_option('vortex_token_price', 0.1),
            'min_investment' => get_option('vortex_min_investment', 1000),
        );
        
        // Apply filters to allow extensions to modify the data
        $marketplace_data = apply_filters('vortex_marketplace_api_marketplace_data', $marketplace_data, $request);
        
        // Cache the data for 1 hour
        set_transient($cache_key, $marketplace_data, HOUR_IN_SECONDS);
        
        return rest_ensure_response($marketplace_data);
    }
    
    /**
     * Submit investor application
     * 
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response|WP_Error Response object
     */
    public function submit_investor_application($request) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_forbidden',
                __('You must be logged in to apply.', 'vortex'),
                array('status' => 401)
            );
        }
        
        $user_id = get_current_user_id();
        
        // Get investment instance
        $investment = VORTEX_DAO_Investment::get_instance();
        
        // Check if already an investor
        $existing = $investment->get_investor_data($user_id);
        if ($existing) {
            return new WP_Error(
                'rest_forbidden',
                __('You are already registered as an investor.', 'vortex'),
                array('status' => 403)
            );
        }
        
        // Get application details
        $wallet_address = $request['wallet_address'];
        $investment_amount = $request['investment_amount'];
        $first_name = $request['first_name'];
        $last_name = $request['last_name'];
        $email = $request['email'];
        $phone = $request['phone'];
        $accredited = $request['accredited'] ? true : false;
        
        // Get configuration
        $config = apply_filters('vortex_dao_config', array(
            'min_investment' => get_option('vortex_min_investment', 1000),
            'token_price' => get_option('vortex_token_price', 0.1),
        ));
        
        // Validate data further
        if (empty($wallet_address) || $investment_amount < $config['min_investment']) {
            return new WP_Error(
                'rest_invalid_param',
                sprintf(
                    __('Invalid application details. Investment amount must be at least %s.', 'vortex'),
                    number_format($config['min_investment'], 2)
                ),
                array('status' => 400)
            );
        }
        
        // Allow pre-processing before submission
        $pre_process = apply_filters('vortex_marketplace_api_pre_investor_application', true, array(
            'user_id' => $user_id,
            'wallet_address' => $wallet_address,
            'investment_amount' => $investment_amount,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'accredited' => $accredited,
        ), $request);
        
        if (is_wp_error($pre_process)) {
            return $pre_process;
        }
        
        // Prepare notes from application
        $notes = sprintf(
            __("Investment Application\nName: %s %s\nEmail: %s\nPhone: %s\nAccredited Investor: %s\nApplication Date: %s", 'vortex'),
            $first_name,
            $last_name,
            $email,
            $phone,
            $accredited ? 'Yes' : 'No',
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'))
        );
        
        // Add investor with pending KYC status
        $investor_id = $investment->add_investor(
            $user_id,
            $wallet_address,
            $investment_amount,
            $config['token_price'],
            730, // 2-year vesting by default
            $notes
        );
        
        if (!$investor_id) {
            return new WP_Error(
                'rest_server_error',
                __('Failed to submit application. Please try again.', 'vortex'),
                array('status' => 500)
            );
        }
        
        // Send notification email to admin
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('[%s] New Investor Application', 'vortex'), get_bloginfo('name'));
        $message = sprintf(
            __("A new investor application has been submitted.\n\nName: %s %s\nEmail: %s\nPhone: %s\nWallet: %s\nInvestment Amount: %s\nAccredited Investor: %s\n\nPlease review this application in the WordPress admin.", 'vortex'),
            $first_name,
            $last_name,
            $email,
            $phone,
            $wallet_address,
            number_format($investment_amount, 2),
            $accredited ? 'Yes' : 'No'
        );
        
        wp_mail($admin_email, $subject, $message);
        
        // Update user meta with application data
        update_user_meta($user_id, 'vortex_wallet_address', $wallet_address);
        update_user_meta($user_id, 'vortex_investor_application_date', current_time('mysql'));
        
        // Update user's first/last name if not set
        $user = get_userdata($user_id);
        if (empty($user->first_name) && !empty($first_name)) {
            update_user_meta($user_id, 'first_name', $first_name);
        }
        if (empty($user->last_name) && !empty($last_name)) {
            update_user_meta($user_id, 'last_name', $last_name);
        }
        
        // Prepare response data
        $response_data = array(
            'message' => __('Your investment application has been submitted successfully. We will review your application and contact you soon.', 'vortex'),
            'investor_id' => $investor_id,
        );
        
        // Allow post-processing after submission
        do_action('vortex_marketplace_api_post_investor_application', $investor_id, $user_id, $request);
        
        // Clear any related caches
        $this->purge_api_caches();
        
        return rest_ensure_response($response_data);
    }
    
    /**
     * Get investor data
     * 
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response Response object
     */
    public function get_investor_data($request) {
        $investor_id = $request['id'];
        $user_id = get_current_user_id();
        
        // Don't cache admin views of investor data
        $should_cache = !current_user_can('manage_options');
        
        // Generate cache key specific to this investor and user
        $cache_key = 'vortex_api_investor_' . $investor_id . '_user_' . $user_id;
        
        if ($should_cache) {
            $cached_data = get_transient($cache_key);
            if (false !== $cached_data) {
                return rest_ensure_response($cached_data);
            }
        }
        
        // Get investment instance
        $investment = VORTEX_DAO_Investment::get_instance();
        
        // Get investor data
        $investor = $investment->get_investor_by_id($investor_id);
        
        if (!$investor) {
            return new WP_Error(
                'rest_not_found',
                __('Investor not found.', 'vortex'),
                array('status' => 404)
            );
        }
        
        // Get user data
        $user = get_userdata($investor->user_id);
        
        // Prepare investor data
        $investor_data = array(
            'id' => $investor->id,
            'user_id' => $investor->user_id,
            'display_name' => $user ? $user->display_name : '',
            'wallet_address' => $investor->wallet_address,
            'investment_amount' => $investor->investment_amount,
            'token_amount' => $investor->token_amount,
            'token_price' => $investor->token_price,
            'vesting_period_days' => $investor->vesting_period_days,
            'purchase_date' => $investor->purchase_date,
            'kyc_status' => $investor->kyc_status,
        );
        
        // Calculate vesting status
        $purchase_timestamp = strtotime($investor->purchase_date);
        $vesting_end_timestamp = strtotime("+{$investor->vesting_period_days} days", $purchase_timestamp);
        $current_timestamp = current_time('timestamp');
        
        // Calculate vested percentage
        $vested_percentage = 0;
        $vesting_elapsed = $current_timestamp - $purchase_timestamp;
        $vesting_total = $vesting_end_timestamp - $purchase_timestamp;
        
        if ($vesting_elapsed > 0 && $vesting_total > 0) {
            $vested_percentage = min(100, ($vesting_elapsed / $vesting_total) * 100);
        }
        
        $investor_data['vested_percentage'] = round($vested_percentage, 2);
        $investor_data['vested_tokens'] = floor(($investor->token_amount * $vested_percentage) / 100);
        
        // Apply filters to allow extensions to modify the data
        $investor_data = apply_filters('vortex_marketplace_api_investor_data', $investor_data, $investor_id, $request);
        
        // Cache investor data for 30 minutes, except for admins
        if ($should_cache) {
            set_transient($cache_key, $investor_data, 30 * MINUTE_IN_SECONDS);
        }
        
        return rest_ensure_response($investor_data);
    }
    
    /**
     * Update investor data
     * 
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response Response object
     */
    public function update_investor_data($request) {
        $investor_id = $request['id'];
        
        // Get investment instance
        $investment = VORTEX_DAO_Investment::get_instance();
        
        // Get investor data
        $investor = $investment->get_investor_by_id($investor_id);
        
        if (!$investor) {
            return new WP_Error(
                'rest_not_found',
                __('Investor not found.', 'vortex'),
                array('status' => 404)
            );
        }
        
        // Collect update data
        $update_data = array();
        
        if (isset($request['wallet_address'])) {
            $update_data['wallet_address'] = $request['wallet_address'];
        }
        
        if (isset($request['investment_amount'])) {
            $update_data['investment_amount'] = $request['investment_amount'];
        }
        
        if (isset($request['kyc_status'])) {
            $update_data['kyc_status'] = $request['kyc_status'];
        }
        
        if (isset($request['notes'])) {
            $update_data['notes'] = $request['notes'];
        }
        
        if (empty($update_data)) {
            return new WP_Error(
                'rest_invalid_param',
                __('No data provided to update.', 'vortex'),
                array('status' => 400)
            );
        }
        
        // Allow pre-processing before update
        $pre_process = apply_filters('vortex_marketplace_api_pre_investor_update', true, $investor_id, $update_data, $request);
        
        if (is_wp_error($pre_process)) {
            return $pre_process;
        }
        
        // Update investor
        $success = $investment->update_investor($investor_id, $update_data);
        
        if (!$success) {
            return new WP_Error(
                'rest_server_error',
                __('Failed to update investor.', 'vortex'),
                array('status' => 500)
            );
        }
        
        // Get updated investor data
        $updated_investor = $investment->get_investor_by_id($investor_id);
        
        // Prepare response data
        $response_data = array(
            'message' => __('Investor updated successfully!', 'vortex'),
            'investor_id' => $investor_id,
            'data' => array(
                'wallet_address' => $updated_investor->wallet_address,
                'investment_amount' => $updated_investor->investment_amount,
                'kyc_status' => $updated_investor->kyc_status,
            ),
        );
        
        // Allow post-processing after update
        do_action('vortex_marketplace_api_post_investor_update', $investor_id, $update_data, $request);
        
        // Clear any related caches
        $this->purge_api_caches();
        
        return rest_ensure_response($response_data);
    }
    
    /**
     * Get token data
     * 
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response Response object
     */
    public function get_token_data($request) {
        // Check if we have a cached response
        $cache_key = 'vortex_api_token_data';
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return rest_ensure_response($cached_data);
        }
        
        // Get token instance
        $tokens = VORTEX_DAO_Token::get_instance();
        
        // Get token data
        $token_data = array(
            'token_price' => get_option('vortex_token_price', 0.1),
            'total_supply' => 10000000, // 10 million tokens
            'circulating_supply' => $tokens->get_circulating_supply(),
            'token_symbol' => 'TOLA',
            'token_name' => 'TOLA Equity',
            'token_contract' => get_option('vortex_governance_token_address', ''),
        );
        
        // Apply filters to allow extensions to modify the data
        $token_data = apply_filters('vortex_marketplace_api_token_data', $token_data, $request);
        
        // Cache the data for 1 hour
        set_transient($cache_key, $token_data, HOUR_IN_SECONDS);
        
        return rest_ensure_response($token_data);
    }
    
    /**
     * Purge API caches when data changes
     */
    private function purge_api_caches() {
        global $wpdb;
        
        // Delete all marketplace API transients
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_vortex_api_%'");
        
        // Trigger action for extensions
        do_action('vortex_marketplace_api_cache_purged');
    }
}

// Initialize the API
VORTEX_Marketplace_API::get_instance(); 