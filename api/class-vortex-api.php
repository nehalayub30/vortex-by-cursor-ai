<?php
/**
 * Base API Class
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/api
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Base API Class
 *
 * This is the base class that all VORTEX API classes extend.
 * It provides common API functionality like request handling,
 * response parsing, and error management.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/api
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_API {

    /**
     * API base URL.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $api_url    The base URL for API requests.
     */
    protected $api_url;

    /**
     * API key.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $api_key    The API key for authentication.
     */
    protected $api_key;

    /**
     * API version.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $api_version    The API version.
     */
    protected $api_version;

    /**
     * Last error message.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $last_error    The last error message.
     */
    protected $last_error;

    /**
     * Debug mode.
     *
     * @since    1.0.0
     * @access   protected
     * @var      boolean    $debug    Whether debug mode is enabled.
     */
    protected $debug;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $api_url       Optional. The base URL for API requests.
     * @param    string    $api_key       Optional. The API key for authentication.
     * @param    string    $api_version   Optional. The API version.
     */
    public function __construct($api_url = '', $api_key = '', $api_version = 'v1') {
        $this->api_url = $api_url;
        $this->api_key = $api_key;
        $this->api_version = $api_version;
        $this->last_error = '';
        $this->debug = defined('WP_DEBUG') && WP_DEBUG;
        
        // Initialize API-specific settings
        $this->init();
    }

    /**
     * Initialize API-specific settings.
     *
     * This method can be overridden by child classes to set
     * API-specific properties.
     *
     * @since    1.0.0
     * @access   protected
     */
    protected function init() {
        // To be overridden by child classes
    }

    /**
     * Set the API key.
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key.
     */
    public function set_api_key($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Set the API URL.
     *
     * @since    1.0.0
     * @param    string    $api_url    The API URL.
     */
    public function set_api_url($api_url) {
        $this->api_url = $api_url;
    }

    /**
     * Set the API version.
     *
     * @since    1.0.0
     * @param    string    $api_version    The API version.
     */
    public function set_api_version($api_version) {
        $this->api_version = $api_version;
    }

    /**
     * Get the last error message.
     *
     * @since    1.0.0
     * @return   string    The last error message.
     */
    public function get_last_error() {
        return $this->last_error;
    }

    /**
     * Set the last error message.
     *
     * @since    1.0.0
     * @param    string    $error    The error message.
     * @access   protected
     */
    protected function set_last_error($error) {
        $this->last_error = $error;
        
        if ($this->debug) {
            error_log('[VORTEX API Error] ' . $error);
        }
    }

    /**
     * Make an API request.
     *
     * @since    1.0.0
     * @param    string    $endpoint       The API endpoint.
     * @param    array     $params         Optional. Request parameters.
     * @param    string    $method         Optional. Request method (GET, POST, etc.).
     * @param    array     $headers        Optional. Request headers.
     * @return   mixed     API response or WP_Error.
     */
    protected function request($endpoint, $params = array(), $method = 'GET', $headers = array()) {
        // Build the full API URL
        $url = trailingslashit($this->api_url) . $endpoint;
        
        // Set up default headers
        $default_headers = array(
            'Accept' => 'application/json',
        );
        
        // Add API key to headers if available
        if (!empty($this->api_key)) {
            $default_headers['Authorization'] = 'Bearer ' . $this->api_key;
        }
        
        // Merge custom headers with defaults
        $headers = wp_parse_args($headers, $default_headers);
        
        // Set up request arguments
        $args = array(
            'method'    => $method,
            'headers'   => $headers,
            'timeout'   => 30,
            'sslverify' => true,
        );
        
        // Add params to URL for GET requests or to body for other methods
        if ('GET' === $method) {
            $url = add_query_arg($params, $url);
        } else {
            $args['body'] = json_encode($params);
            $headers['Content-Type'] = 'application/json';
        }
        
        // Make the request
        $response = wp_remote_request($url, $args);
        
        // Check for errors
        if (is_wp_error($response)) {
            $this->set_last_error($response->get_error_message());
            return $response;
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        
        // Check for non-200 response codes
        if ($response_code < 200 || $response_code >= 300) {
            $this->set_last_error(sprintf(
                'API request failed with response code %d: %s',
                $response_code,
                wp_remote_retrieve_response_message($response)
            ));
            
            return new WP_Error(
                'api_error',
                sprintf('API request failed with response code %d', $response_code),
                array(
                    'response' => $response,
                    'code'     => $response_code,
                )
            );
        }
        
        // Parse the JSON response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Check for JSON parsing errors
        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->set_last_error('Failed to parse API response: ' . json_last_error_msg());
            
            return new WP_Error(
                'api_response_error',
                'Failed to parse API response',
                array(
                    'response' => $response,
                    'code'     => json_last_error(),
                )
            );
        }
        
        return $data;
    }

    /**
     * Check if the API is properly configured.
     *
     * @since    1.0.0
     * @return   boolean    True if the API is configured, false otherwise.
     */
    public function is_configured() {
        return !empty($this->api_url) && !empty($this->api_key);
    }

    /**
     * Register WordPress REST API endpoints.
     *
     * This method should be overridden by child classes that
     * need to register REST API endpoints.
     *
     * @since    1.0.0
     */
    public function register_rest_routes() {
        // To be overridden by child classes
    }

    /**
     * Validate API key.
     *
     * @since    1.0.0
     * @return   boolean    True if the API key is valid, false otherwise.
     */
    public function validate_api_key() {
        // Base implementation just checks if the key exists
        return !empty($this->api_key);
    }

    /**
     * Format API response for WordPress REST API.
     *
     * @since    1.0.0
     * @param    mixed     $response    The API response.
     * @param    boolean   $success     Whether the request was successful.
     * @param    integer   $status_code The response status code.
     * @return   array     Formatted response.
     * @access   protected
     */
    protected function format_rest_response($response, $success = true, $status_code = 200) {
        return array(
            'success' => $success,
            'code'    => $status_code,
            'data'    => $response,
        );
    }

    /**
     * Format error response for WordPress REST API.
     *
     * @since    1.0.0
     * @param    string    $message     The error message.
     * @param    string    $code        The error code.
     * @param    integer   $status_code The response status code.
     * @return   WP_Error  Formatted error response.
     * @access   protected
     */
    protected function format_rest_error($message, $code = 'error', $status_code = 400) {
        return new WP_Error(
            $code,
            $message,
            array('status' => $status_code)
        );
    }

    /**
     * Log API activity.
     *
     * @since    1.0.0
     * @param    string    $action      The API action.
     * @param    mixed     $request     The request data.
     * @param    mixed     $response    The response data.
     * @access   protected
     */
    protected function log_activity($action, $request, $response) {
        if (!$this->debug) {
            return;
        }
        
        $log = array(
            'time'     => current_time('mysql'),
            'action'   => $action,
            'request'  => $request,
            'response' => $response,
        );
        
        error_log('[VORTEX API Activity] ' . wp_json_encode($log));
    }
} 