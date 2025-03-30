<?php
/**
 * VORTEX TOLA Blockchain Integration
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Blockchain
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_TOLA_Integration Class
 * 
 * Manages integration with TOLA blockchain, ensuring AI agents
 * maintain continuous learning during blockchain operations.
 *
 * @since 1.0.0
 */
class VORTEX_TOLA_Integration {
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * Active AI agents for blockchain learning
     */
    private $ai_agents = array();
    
    /**
     * TOLA API endpoints
     */
    private $api_endpoints = array();
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize API endpoints
        $this->initialize_api_endpoints();
        
        // Initialize AI agents
        $this->initialize_ai_agents();
        
        // Set up hooks
        $this->setup_hooks();
    }
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize API endpoints
     */
    private function initialize_api_endpoints() {
        $this->api_endpoints = array(
            'mainnet' => array(
                'base' => 'https://api.tola-blockchain.com/v1/',
                'transactions' => 'transactions/',
                'tokens' => 'tokens/',
                'balance' => 'wallets/{address}/balance',
                'nft' => 'nft/',
                'swap' => 'swap/'
            ),
            'testnet' => array(
                'base' => 'https://testnet-api.tola-blockchain.com/v1/',
                'transactions' => 'transactions/',
                'tokens' => 'tokens/',
                'balance' => 'wallets/{address}/balance',
                'nft' => 'nft/',
                'swap' => 'swap/'
            )
        );
    }
    
    /**
     * Initialize AI agents for blockchain operations
     */
    private function initialize_ai_agents() {
        // Initialize HURAII for NFT visual verification
        $this->ai_agents['HURAII'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'blockchain_nft',
            'capabilities' => array(
                'nft_visual_verification',
                'artwork_authenticity_check',
                'visual_hash_validation'
            )
        );
        
        // Initialize BusinessStrategist for market operations
        $this->ai_agents['BusinessStrategist'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'token_economics',
            'capabilities' => array(
                'token_value_prediction',
                'market_liquidity_analysis',
                'swap_rate_optimization'
            )
        );
        
        // Initialize AI agents
        do_action('vortex_ai_agent_init', 'blockchain_operations', array_keys($this->ai_agents), 'active');
    }
    
    /**
     * Set up hooks
     */
    private function setup_hooks() {
        // AJAX endpoints for blockchain operations
        add_action('wp_ajax_vortex_check_tola_balance', array($this, 'ajax_check_tola_balance'));
        add_action('wp_ajax_vortex_transfer_tola', array($this, 'ajax_transfer_tola'));
        add_action('wp_ajax_vortex_mint_nft', array($this, 'ajax_mint_nft'));
        add_action('wp_ajax_vortex_get_transactions', array($this, 'ajax_get_transactions'));
        
        // Filters for integration with other components
        add_filter('vortex_verify_transaction', array($this, 'verify_tola_transaction'), 10, 2);
        add_filter('vortex_get_wallet_balance', array($this, 'get_wallet_balance'), 10, 2);
        
        // Smart contract integration
        add_action('vortex_execute_smart_contract', array($this, 'execute_smart_contract'), 10, 2);
        
        // Scheduled blockchain sync
        add_action('vortex_tola_blockchain_sync', array($this, 'sync_blockchain_data'));
        
        if (!wp_next_scheduled('vortex_tola_blockchain_sync')) {
            wp_schedule_event(time(), 'hourly', 'vortex_tola_blockchain_sync');
        }
    }
    
    /**
     * Get API endpoint URL
     */
    private function get_api_endpoint($endpoint, $network = 'mainnet', $params = array()) {
        $use_network = $this->get_network();
        
        if (!isset($this->api_endpoints[$use_network]) || !isset($this->api_endpoints[$use_network][$endpoint])) {
            return false;
        }
        
        $url = $this->api_endpoints[$use_network]['base'] . $this->api_endpoints[$use_network][$endpoint];
        
        // Replace path parameters
        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }
        
        return $url;
    }
    
    /**
     * Get current network (mainnet/testnet)
     */
    private function get_network() {
        $network = get_option('vortex_tola_network', 'testnet');
        return ($network === 'mainnet') ? 'mainnet' : 'testnet';
    }
    
    /**
     * Make API request to TOLA blockchain
     */
    private function api_request($endpoint, $method = 'GET', $params = array(), $body = array()) {
        $url = $this->get_api_endpoint($endpoint, $this->get_network(), $params);
        
        if (!$url) {
            return array(
                'success' => false,
                'message' => 'Invalid API endpoint'
            );
        }
        
        $api_key = get_option('vortex_tola_api_key', '');
        
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            )
        );
        
        if (!empty($body) && in_array($method, array('POST', 'PUT'))) {
            $args['body'] = json_encode($body);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'success' => true,
                'data' => $response_body
            );
        } else {
            return array(
                'success' => false,
                'message' => isset($response_body['message']) ? $response_body['message'] : 'API request failed',
                'code' => $response_code
            );
        }
    }
    
    /**
     * Get TOLA wallet balance
     */
    public function get_wallet_balance($wallet_address, $token = 'TOLA') {
        $response = $this->api_request('balance', 'GET', array('address' => $wallet_address));
        
        if (!$response['success']) {
            return array(
                'success' => false,
                'message' => $response['message']
            );
        }
        
        // Process response to extract balance for the requested token
        $balances = $response['data']['balances'] ?? array();
        $token_balance = 0;
        
        foreach ($balances as $balance) {
            if ($balance['token'] === $token) {
                $token_balance = $balance['amount'];
                break;
            }
        }
        
        return array(
            'success' => true,
            'balance' => $token_balance,
            'token' => $token,
            'wallet' => $wallet_address
        );
    }
    
    /**
     * Transfer TOLA tokens
     */
    public function transfer_tola($from_wallet, $to_wallet, $amount, $private_key, $memo = '') {
        // Prepare transaction data
        $transaction_data = array(
            'from' => $from_wallet,
            'to' => $to_wallet,
            'amount' => $amount,
            'token' => 'TOLA',
            'memo' => $memo,
            'timestamp' => time()
        );
        
        // Sign transaction (would be handled by a proper blockchain library)
        $transaction_data['signature'] = $this->sign_transaction($transaction_data, $private_key);
        
        // Submit transaction to blockchain
        $response = $this->api_request('transactions', 'POST', array(), $transaction_data);
        
        if (!$response['success']) {
            return array(
                'success' => false,
                'message' => $response['message']
            );
        }
        
        $transaction_id = $response['data']['transaction_id'] ?? '';
        
        // Track for AI learning
        foreach ($this->ai_agents as $agent_name => $config) {
            if ($config['active']) {
                do_action('vortex_ai_agent_learn', $agent_name, 'tola_transfer', array(
                    'from_wallet' => $from_wallet,
                    'to_wallet' => $to_wallet,
                    'amount' => $amount,
                    'token' => 'TOLA',
                    'transaction_id' => $transaction_id,
                    'timestamp' => current_time('timestamp')
                ));
            }
        }
        
        return array(
            'success' => true,
            'transaction_id' => $transaction_id,
            'amount' => $amount,
            'from' => $from_wallet,
            'to' => $to_wallet
        );
    }
    
    /**
     * Mint NFT on TOLA blockchain
     */
    public function mint_nft($artwork_id, $owner_wallet, $private_key) {
        // Get artwork data
        $artwork = get_post($artwork_id);
        if (!$artwork) {
            return array(
                'success' => false,
                'message' => 'Artwork not found'
            );
        }
        
        // Prepare NFT metadata
        $metadata = array(
            'name' => $artwork->post_title,
            'description' => $artwork->post_content,
            'creator' => get_post_meta($artwork_id, 'vortex_artist_name', true),
            'creator_wallet' => get_post_meta($artwork_id, 'vortex_artist_wallet', true),
            'image_url' => get_the_post_thumbnail_url($artwork_id, 'full'),
            'attributes' => array()
        );
        
        // Add Seed Art components as attributes if available
        $seed_art_analysis = get_post_meta($artwork_id, 'vortex_seed_art_analysis', true);
        if (!empty($seed_art_analysis) && is_array($seed_art_analysis)) {
            foreach ($seed_art_analysis['components'] as $component => $data) {
                $metadata['attributes'][] = array(
                    'trait_type' => ucwords(str_replace('_', ' ', $component)),
                    'value' => isset($data['score']) ? number_format($data['score'] * 100, 0) . '%' : 'N/A'
                );
            }
        }
        
        // Add categories and styles as attributes
        $categories = wp_get_post_terms($artwork_id, 'vortex-artwork-category', array('fields' => 'names'));
        if (!empty($categories)) {
            $metadata['attributes'][] = array(
                'trait_type' => 'Category',
                'value' => implode(', ', $categories)
            );
        }
        
        $styles = wp_get_post_terms($artwork_id, 'vortex-artwork-style', array('fields' => 'names'));
        if (!empty($styles)) {
            $metadata['attributes'][] = array(
                'trait_type' => 'Style',
                'value' => implode(', ', $styles)
            );
        }
        
        // Add royalty information
        $royalty_percentage = get_post_meta($artwork_id, 'vortex_royalty_percentage', true);
        if ($royalty_percentage) {
            $metadata['royalty_percentage'] = floatval($royalty_percentage);
        }
        
        // Add HURAII creator royalty (fixed 5%)
        $metadata['huraii_creator_royalty'] = 5.0;
        $metadata['huraii_creator_wallet'] = get_option('vortex_huraii_creator_wallet', '');
        
        // Prepare minting data
        $mint_data = array(
            'owner_wallet' => $owner_wallet,
            'metadata' => $metadata,
            'timestamp' => time()
        );
        
        // Get HURAII visual verification if AI is active
        if ($this->ai_agents['HURAII']['active'] && class_exists('VORTEX_HURAII')) {
            $huraii = VORTEX_HURAII::get_instance();
            if (method_exists($huraii, 'verify_artwork_authenticity')) {
                $verification = $huraii->verify_artwork_authenticity($artwork_id);
                $mint_data['ai_verification'] = $verification;
            }
        }
        
        // Sign minting request
        $mint_data['signature'] = $this->sign_transaction($mint_data, $private_key);
        
        // Submit minting request
        $response = $this->api_request('nft', 'POST', array(), $mint_data);
        
        if (!$response['success']) {
            return array(
                'success' => false,
                'message' => $response['message']
            );
        }
        
        $nft_token_id = $response['data']['token_id'] ?? '';
        $transaction_id = $response['data']['transaction_id'] ?? '';
        
        // Update artwork with NFT token ID
        update_post_meta($artwork_id, 'vortex_nft_token_id', $nft_token_id);
        update_post_meta($artwork_id, 'vortex_nft_transaction_id', $transaction_id);
        update_post_meta($artwork_id, 'vortex_nft_minted', true);
        update_post_meta($artwork_id, 'vortex_nft_minted_date', current_time('mysql'));
        
        // Track for AI learning
        foreach ($this->ai_agents as $agent_name => $config) {
            if ($config['active']) {
                do_action('vortex_ai_agent_learn', $agent_name, 'nft_minting', array(
                    'artwork_id' => $artwork_id,
                    'owner_wallet' => $owner_wallet,
                    'token_id' => $nft_token_id,
                    'transaction_id' => $transaction_id,
                    'metadata' => $metadata,
                    'timestamp' => current_time('timestamp')
                ));
            }
        }
        
        return array(
            'success' => true,
            'token_id' => $nft_token_id,
            'transaction_id' => $transaction_id,
            'artwork_id' => $artwork_id,
            'owner_wallet' => $owner_wallet
        );
    }
    
    /**
     * Sign transaction (placeholder - would use actual blockchain library)
     */
    private function sign_transaction($transaction_data, $private_key) {
        // This is a placeholder for actual cryptographic signing
        // In a production environment, this would use proper blockchain libraries
        // For example: Web3.js, ethers.js, etc.
        
        // For audit purposes, we're just simulating the signature
        return hash('sha256', json_encode($transaction_data) . $private_key);
    }
    
    /**
     * Execute smart contract
     */
    public function execute_smart_contract($contract_id, $function_data) {
        // Validate contract ID
        if (empty($contract_id)) {
            return array(
                'success' => false,
                'message' => 'Invalid contract ID'
            );
        }
        
        // Get contract details
        $contract = get_post($contract_id);
        if (!$contract || $contract->post_type !== 'vortex-smart-contract') {
            return array(
                'success' => false,
                'message' => 'Contract not found'
            );
        }
        
        // Prepare contract execution data
        $contract_address = get_post_meta($contract_id, 'vortex_contract_address', true);
        $contract_abi = get_post_meta($contract_id, 'vortex_contract_abi', true);
        
        if (empty($contract_address) || empty($contract_abi)) {
            return array(
                'success' => false,
                'message' => 'Invalid contract configuration'
            );
        }
        
        // Prepare execution data
        $execution_data = array(
            'contract_address' => $contract_address,
            'function_name' => $function_data['function'] ?? '',
            'parameters' => $function_data['params'] ?? array(),
            'caller_address' => $function_data['caller'] ?? '',
            'timestamp' => time()
        );
        
        // Validate function data
        if (empty($execution_data['function_name']) || empty($execution_data['caller_address'])) {
            return array(
                'success' => false,
                'message' => 'Invalid function data'
            );
        }
        
        // Sign execution if private key provided
        if (isset($function_data['private_key'])) {
            $execution_data['signature'] = $this->sign_transaction($execution_data, $function_data['private_key']);
        }
        
        // Submit contract execution
        $response = $this->api_request('transactions', 'POST', array(), array(
            'type' => 'contract_execution',
            'data' => $execution_data
        ));
        
        if (!$response['success']) {
            return array(
                'success' => false,
                'message' => $response['message']
            );
        }
        
        $transaction_id = $response['data']['transaction_id'] ?? '';
        
        // Track for AI learning (primarily BusinessStrategist)
        if ($this->ai_agents['BusinessStrategist']['active']) {
            do_action('vortex_ai_agent_learn', 'BusinessStrategist', 'contract_execution', array(
                'contract_id' => $contract_id,
                'contract_address' => $contract_address,
                'function' => $execution_data['function_name'],
                'caller' => $execution_data['caller_address'],
                'transaction_id' => $transaction_id,
                'timestamp' => current_time('timestamp')
            ));
        }
        
        return array(
            'success' => true,
            'transaction_id' => $transaction_id,
            'contract_id' => $contract_id,
            'function' => $execution_data['function_name']
        );
    }
    
    /**
     * Verify TOLA transaction
     */
    public function verify_tola_transaction($transaction_id, $expected_data = array()) {
        $response = $this->api_request('transactions', 'GET', array('id' => $transaction_id));
        
        if (!$response['success']) {
            return array(
                'success' => false,
                'message' => $response['message']
            );
        }
        
        $transaction = $response['data'] ?? array();
        
        // Verify transaction exists
        if (empty($transaction)) {
            return array(
                'success' => false,
                'message' => 'Transaction not found'
            );
        }
        
        // Verify transaction matches expected data if provided
        $valid = true;
        $mismatches = array();
        
        if (!empty($expected_data)) {
            foreach ($expected_data as $key => $value) {
                if (isset($transaction[$key]) && $transaction[$key] != $value) {
                    $valid = false;
                    $mismatches[] = $key;
                }
            }
        }
        
        if (!$valid) {
            return array(
                'success' => false,
                'message' => 'Transaction data mismatch',
                'mismatches' => $mismatches
            );
        }
        
        return array(
            'success' => true,
            'transaction' => $transaction
        );
    }
    
    /**
     * Sync blockchain data
     * Keeps local database in sync with blockchain
     */
    public function sync_blockchain_data() {
        // 1. Sync NFT ownership
        $this->sync_nft_ownership();
        
        // 2. Sync wallet balances for active users
        $this->sync_user_wallet_balances();
        
        // 3. Sync transaction history
        $this->sync_transaction_history();
        
        // Log the sync operation
        error_log('TOLA blockchain sync completed: ' . current_time('mysql'));
    }
    
    /**
     * AJAX handler for checking TOLA balance
     */
    public function ajax_check_tola_balance() {
        check_ajax_referer('vortex_blockchain_nonce', 'security');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in', 'vortex-marketplace')));
            return;
        }
        
        $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
        
        if (empty($wallet_address)) {
            wp_send_json_error(array('message' => __('Wallet address is required', 'vortex-marketplace')));
            return;
        }
        
        $response = $this->get_wallet_balance($wallet_address);
        
        if ($response['success']) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error($response);
        }
    }
    
    /**
     * AJAX handler for transferring TOLA
     */
    public function ajax_transfer_tola() {
        check_ajax_referer('vortex_blockchain_nonce', 'security');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in', 'vortex-marketplace')));
            return;
        }
        
        $from_wallet = isset($_POST['from_wallet']) ? sanitize_text_field($_POST['from_wallet']) : '';
        $to_wallet = isset($_POST['to_wallet']) ? sanitize_text_field($_POST['to_wallet']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $private_key = isset($_POST['private_key']) ? sanitize_text_field($_POST['private_key']) : '';
        $memo = isset($_POST['memo']) ? sanitize_textarea_field($_POST['memo']) : '';
        
        if (empty($from_wallet) || empty($to_wallet) || $amount <= 0 || empty($private_key)) {
            wp_send_json_error(array('message' => __('Missing required fields', 'vortex-marketplace')));
            return;
        }
        
        $response = $this->transfer_tola($from_wallet, $to_wallet, $amount, $private_key, $memo);
        
        if ($response['success']) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error($response);
        }
    }
}

// Initialize TOLA Integration
VORTEX_TOLA_Integration::get_instance(); 