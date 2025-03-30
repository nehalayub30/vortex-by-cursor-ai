<?php
/**
 * TOLA API functionality for Vortex AI Marketplace
 *
 * @package Vortex_Marketplace
 * @subpackage API
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_TOLA_API class
 * Handles all TOLA blockchain integration API functionality
 */
class VORTEX_TOLA_API {
    /**
     * API endpoint for TOLA blockchain
     * @var string
     */
    private $endpoint;
    
    /**
     * API key for TOLA blockchain
     * @var string
     */
    private $api_key;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->endpoint = get_option('vortex_tola_api_endpoint', 'https://api.tola-blockchain.io/v1');
        $this->api_key = get_option('vortex_tola_api_key', '');
        
        // Register hooks
        add_action('init', array($this, 'register_routes'));
    }
    
    /**
     * Register API routes
     */
    public function register_routes() {
        // Register REST API routes
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
} 