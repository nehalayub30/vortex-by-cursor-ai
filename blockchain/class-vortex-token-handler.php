<?php
/**
 * TOLA Token Handler Class
 *
 * Manages token operations for the VORTEX AI Marketplace
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/blockchain
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * TOLA Token Handler Class
 *
 * This class handles token operations including balance checking,
 * transfers, approvals, and other token-related functionality.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/blockchain
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Token_Handler {

    /**
     * The blockchain integration instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Blockchain_Integration $blockchain The blockchain integration instance.
     */
    protected $blockchain;

    /**
     * The token contract address.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $contract_address The TOLA token contract address.
     */
    protected $contract_address;

    /**
     * The token contract ABI.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array $contract_abi The TOLA token contract ABI.
     */
    protected $contract_abi;

    /**
     * The marketplace wallet address.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $marketplace_wallet The marketplace wallet address.
     */
    protected $marketplace_wallet;

    /**
     * The commission rate for transactions.
     *
     * @since    1.0.0
     * @access   protected
     * @var      float $commission_rate The commission rate (0-100).
     */
    protected $commission_rate;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    Vortex_Blockchain_Integration $blockchain The blockchain integration instance.
     */
    public function __construct($blockchain) {
        $this->blockchain = $blockchain;
        $this->load_token_settings();
        $this->load_contract_abi();
        $this->register_hooks();
    }

    /**
     * Load token settings from WordPress options.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_token_settings() {
        $this->contract_address = get_option('vortex_tola_contract_address', '');
        $this->marketplace_wallet = get_option('vortex_marketplace_wallet_address', '');
        $this->commission_rate = floatval(get_option('vortex_marketplace_commission_rate', 5));
    }

    /**
     * Load the token contract ABI from JSON file.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_contract_abi() {
        $abi_file = plugin_dir_path(dirname(__FILE__)) . 'blockchain/tola-token-abi.json';
        
        if (file_exists($abi_file)) {
            $abi_json = file_get_contents($abi_file);
            $this->contract_abi = json_decode($abi_json, true);
        } else {
            error_log('TOLA token ABI file not found: ' . $abi_file);
            $this->contract_abi = array();
        }
    }

    /**
     * Register hooks related to token handling.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_hooks() {
        // AJAX handlers for token operations
        add_action('wp_ajax_vortex_get_token_balance', array($this, 'ajax_get_token_balance'));
        add_action('wp_ajax_vortex_transfer_tokens', array($this, 'ajax_transfer_tokens'));
        add_action('wp_ajax_vortex_approve_tokens', array($this, 'ajax_approve_tokens'));
        
        // Purchase hooks
        add_action('vortex_before_checkout_process', array($this, 'verify_token_balance'));
        add_action('vortex_after_checkout_complete', array($this, 'process_token_payment'), 10, 2);
        
        // Artist verification
        add_action('vortex_verify_artist', array($this, 'verify_artist_on_blockchain'), 10, 2);
    }

    /**
     * Get the token balance for a wallet address.
     *
     * @since    1.0.0
     * @param    string $wallet_address The wallet address to check.
     * @return   mixed The balance as a string or WP_Error on failure.
     */
    public function get_token_balance($wallet_address) {
        if (empty($wallet_address) || empty($this->contract_address)) {
            return new WP_Error('invalid_input', __('Invalid wallet address or contract', 'vortex-ai-marketplace'));
        }

        try {
            // Prepare contract call data
            $data = array(
                'contract_address' => $this->contract_address,
                'method' => 'balanceOf',
                'parameters' => array($wallet_address),
                'abi' => $this->contract_abi
            );

            // Call blockchain integration to execute read method
            $response = $this->blockchain->call_contract_method($data);

            if (is_wp_error($response)) {
                return $response;
            }

            // Convert balance to human-readable format (considering decimals)
            $decimals = $this->get_token_decimals();
            $balance = $this->format_token_amount($response, $decimals);

            return $balance;
        } catch (Exception $e) {
            return new WP_Error('balance_error', $e->getMessage());
        }
    }

    /**
     * Get token decimals value.
     *
     * @since    1.0.0
     * @return   int Token decimals or default 18.
     */
    public function get_token_decimals() {
        $cached_decimals = get_transient('vortex_tola_decimals');
        
        if (false !== $cached_decimals) {
            return (int) $cached_decimals;
        }

        try {
            // Prepare contract call data
            $data = array(
                'contract_address' => $this->contract_address,
                'method' => 'decimals',
                'parameters' => array(),
                'abi' => $this->contract_abi
            );

            // Call blockchain integration to execute read method
            $response = $this->blockchain->call_contract_method($data);

            if (is_wp_error($response)) {
                return 18; // Default to 18 decimals (Ethereum standard)
            }

            // Cache the result for 1 day
            set_transient('vortex_tola_decimals', (int) $response, DAY_IN_SECONDS);
            
            return (int) $response;
        } catch (Exception $e) {
            return 18; // Default to 18 decimals (Ethereum standard)
        }
    }

    /**
     * Format a token amount considering decimals.
     *
     * @since    1.0.0
     * @param    string $amount The raw token amount.
     * @param    int    $decimals The token decimals.
     * @return   string Formatted amount.
     */
    public function format_token_amount($amount, $decimals = null) {
        if (null === $decimals) {
            $decimals = $this->get_token_decimals();
        }

        // Convert from wei/smallest unit to main token unit
        $formatted = bcdiv($amount, bcpow('10', $decimals, 0), $decimals);
        
        // Trim trailing zeros
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        
        return $formatted;
    }

    /**
     * Transfer tokens between wallets.
     *
     * @since    1.0.0
     * @param    string $from_wallet The sending wallet address.
     * @param    string $to_wallet The recipient wallet address.
     * @param    float  $amount The amount to transfer.
     * @param    array  $options Additional options.
     * @return   mixed Transaction hash or WP_Error on failure.
     */
    public function transfer_tokens($from_wallet, $to_wallet, $amount, $options = array()) {
        if (empty($from_wallet) || empty($to_wallet) || empty($amount)) {
            return new WP_Error('invalid_input', __('Invalid transfer parameters', 'vortex-ai-marketplace'));
        }

        // Convert amount to wei/smallest unit
        $decimals = $this->get_token_decimals();
        $raw_amount = bcmul((string) $amount, bcpow('10', $decimals, 0), 0);

        try {
            // Prepare contract call data
            $data = array(
                'contract_address' => $this->contract_address,
                'method' => 'transfer',
                'parameters' => array($to_wallet, $raw_amount),
                'from_address' => $from_wallet,
                'gas_limit' => isset($options['gas_limit']) ? $options['gas_limit'] : null,
                'abi' => $this->contract_abi
            );

            // Call blockchain integration to execute write method
            $response = $this->blockchain->send_contract_transaction($data);

            if (is_wp_error($response)) {
                return $response;
            }

            // Log the transfer
            $this->log_token_transaction($from_wallet, $to_wallet, $amount, $response);

            return $response; // Transaction hash
        } catch (Exception $e) {
            return new WP_Error('transfer_error', $e->getMessage());
        }
    }

    /**
     * Approve tokens for spending by marketplace.
     *
     * @since    1.0.0
     * @param    string $wallet_address The wallet address giving approval.
     * @param    float  $amount The amount to approve.
     * @param    array  $options Additional options.
     * @return   mixed Transaction hash or WP_Error on failure.
     */
    public function approve_tokens($wallet_address, $amount, $options = array()) {
        if (empty($wallet_address) || empty($amount) || empty($this->marketplace_wallet)) {
            return new WP_Error('invalid_input', __('Invalid approval parameters', 'vortex-ai-marketplace'));
        }

        // Convert amount to wei/smallest unit
        $decimals = $this->get_token_decimals();
        $raw_amount = bcmul((string) $amount, bcpow('10', $decimals, 0), 0);

        try {
            // Prepare contract call data
            $data = array(
                'contract_address' => $this->contract_address,
                'method' => 'approve',
                'parameters' => array($this->marketplace_wallet, $raw_amount),
                'from_address' => $wallet_address,
                'gas_limit' => isset($options['gas_limit']) ? $options['gas_limit'] : null,
                'abi' => $this->contract_abi
            );

            // Call blockchain integration to execute write method
            $response = $this->blockchain->send_contract_transaction($data);

            if (is_wp_error($response)) {
                return $response;
            }

            return $response; // Transaction hash
        } catch (Exception $e) {
            return new WP_Error('approval_error', $e->getMessage());
        }
    }

    /**
     * Check if a wallet has sufficient token balance.
     *
     * @since    1.0.0
     * @param    string $wallet_address The wallet address to check.
     * @param    float  $required_amount The minimum required amount.
     * @return   bool|WP_Error True if sufficient or WP_Error.
     */
    public function has_sufficient_balance($wallet_address, $required_amount) {
        $balance = $this->get_token_balance($wallet_address);
        
        if (is_wp_error($balance)) {
            return $balance;
        }
        
        return (float) $balance >= (float) $required_amount;
    }

    /**
     * List an artwork for sale on the blockchain.
     *
     * @since    1.0.0
     * @param    int    $artwork_id The artwork ID.
     * @param    string $artist_wallet The artist's wallet address.
     * @param    float  $price The price in TOLA tokens.
     * @return   mixed Transaction hash or WP_Error on failure.
     */
    public function list_artwork_for_sale($artwork_id, $artist_wallet, $price) {
        if (empty($artwork_id) || empty($artist_wallet) || empty($price)) {
            return new WP_Error('invalid_input', __('Invalid listing parameters', 'vortex-ai-marketplace'));
        }

        // Convert price to wei/smallest unit
        $decimals = $this->get_token_decimals();
        $raw_price = bcmul((string) $price, bcpow('10', $decimals, 0), 0);

        try {
            // Prepare contract call data
            $data = array(
                'contract_address' => $this->contract_address,
                'method' => 'setArtworkForSale',
                'parameters' => array($artwork_id, $raw_price),
                'from_address' => $artist_wallet,
                'abi' => $this->contract_abi
            );

            // Call blockchain integration to execute write method
            $response = $this->blockchain->send_contract_transaction($data);

            if (is_wp_error($response)) {
                return $response;
            }

            // Update artwork metadata
            update_post_meta($artwork_id, '_vortex_artwork_blockchain_listed', true);
            update_post_meta($artwork_id, '_vortex_artwork_blockchain_price', $price);
            update_post_meta($artwork_id, '_vortex_artwork_blockchain_tx', $response);

            return $response; // Transaction hash
        } catch (Exception $e) {
            return new WP_Error('listing_error', $e->getMessage());
        }
    }

    /**
     * Purchase an artwork using TOLA tokens.
     *
     * @since    1.0.0
     * @param    int    $artwork_id The artwork ID.
     * @param    string $buyer_wallet The buyer's wallet address.
     * @return   mixed Transaction hash or WP_Error on failure.
     */
    public function purchase_artwork($artwork_id, $buyer_wallet) {
        if (empty($artwork_id) || empty($buyer_wallet)) {
            return new WP_Error('invalid_input', __('Invalid purchase parameters', 'vortex-ai-marketplace'));
        }

        try {
            // Prepare contract call data
            $data = array(
                'contract_address' => $this->contract_address,
                'method' => 'purchaseArtwork',
                'parameters' => array($artwork_id),
                'from_address' => $buyer_wallet,
                'abi' => $this->contract_abi
            );

            // Call blockchain integration to execute write method
            $response = $this->blockchain->send_contract_transaction($data);

            if (is_wp_error($response)) {
                return $response;
            }

            // Update artwork metadata
            update_post_meta($artwork_id, '_vortex_artwork_blockchain_sold', true);
            update_post_meta($artwork_id, '_vortex_artwork_blockchain_buyer', $buyer_wallet);
            update_post_meta($artwork_id, '_vortex_artwork_blockchain_purchase_tx', $response);

            return $response; // Transaction hash
        } catch (Exception $e) {
            return new WP_Error('purchase_error', $e->getMessage());
        }
    }

    /**
     * Verify an artist on the blockchain.
     *
     * @since    1.0.0
     * @param    int    $artist_id The artist ID.
     * @param    string $wallet_address The artist's wallet address.
     * @return   mixed Transaction hash or WP_Error on failure.
     */
    public function verify_artist_on_blockchain($artist_id, $wallet_address) {
        // Only admin users can verify artists
        if (!current_user_can('manage_options')) {
            return new WP_Error('permission_denied', __('You do not have permission to verify artists', 'vortex-ai-marketplace'));
        }

        if (empty($artist_id) || empty($wallet_address)) {
            return new WP_Error('invalid_input', __('Invalid artist verification parameters', 'vortex-ai-marketplace'));
        }

        // Check if marketplace wallet is set up
        if (empty($this->marketplace_wallet)) {
            return new WP_Error('marketplace_not_configured', __('Marketplace wallet not configured', 'vortex-ai-marketplace'));
        }

        try {
            // Prepare contract call data
            $data = array(
                'contract_address' => $this->contract_address,
                'method' => 'verifyArtist',
                'parameters' => array($wallet_address, true),
                'from_address' => $this->marketplace_wallet,
                'abi' => $this->contract_abi
            );

            // Call blockchain integration to execute write method
            $response = $this->blockchain->send_contract_transaction($data);

            if (is_wp_error($response)) {
                return $response;
            }

            // Update artist metadata
            update_post_meta($artist_id, '_vortex_artist_blockchain_verified', true);
            update_post_meta($artist_id, '_vortex_artist_blockchain_verify_tx', $response);

            return $response; // Transaction hash
        } catch (Exception $e) {
            return new WP_Error('verification_error', $e->getMessage());
        }
    }

    /**
     * Get token name and symbol.
     *
     * @since    1.0.0
     * @return   array Token information with name and symbol.
     */
    public function get_token_info() {
        $cached_info = get_transient('vortex_tola_info');
        
        if (false !== $cached_info) {
            return $cached_info;
        }

        $token_info = array(
            'name' => 'TOLA',
            'symbol' => 'TOLA',
            'decimals' => 18
        );

        try {
            // Get token name
            $name_data = array(
                'contract_address' => $this->contract_address,
                'method' => 'name',
                'parameters' => array(),
                'abi' => $this->contract_abi
            );
            
            $name_response = $this->blockchain->call_contract_method($name_data);
            if (!is_wp_error($name_response)) {
                $token_info['name'] = $name_response;
            }

            // Get token symbol
            $symbol_data = array(
                'contract_address' => $this->contract_address,
                'method' => 'symbol',
                'parameters' => array(),
                'abi' => $this->contract_abi
            );
            
            $symbol_response = $this->blockchain->call_contract_method($symbol_data);
            if (!is_wp_error($symbol_response)) {
                $token_info['symbol'] = $symbol_response;
            }

            // Get token decimals
            $decimals_data = array(
                'contract_address' => $this->contract_address,
                'method' => 'decimals',
                'parameters' => array(),
                'abi' => $this->contract_abi
            );
            
            $decimals_response = $this->blockchain->call_contract_method($decimals_data);
            if (!is_wp_error($decimals_response)) {
                $token_info['decimals'] = (int) $decimals_response;
            }

            // Cache token info for 1 day
            set_transient('vortex_tola_info', $token_info, DAY_IN_SECONDS);
            
            return $token_info;
        } catch (Exception $e) {
            return $token_info; // Return default info on error
        }
    }

    /**
     * Log token transaction in the database.
     *
     * @since    1.0.0
     * @access   private
     * @param    string $from_wallet From wallet address.
     * @param    string $to_wallet To wallet address.
     * @param    float  $amount Amount transferred.
     * @param    string $tx_hash Transaction hash.
     */
    private function log_token_transaction($from_wallet, $to_wallet, $amount, $tx_hash) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_token_transactions';
        
        $wpdb->insert(
            $table_name,
            array(
                'tx_hash' => $tx_hash,
                'from_wallet' => $from_wallet,
                'to_wallet' => $to_wallet,
                'amount' => $amount,
                'token_address' => $this->contract_address,
                'transaction_time' => current_time('mysql'),
                'status' => 'pending'
            ),
            array('%s', '%s', '%s', '%f', '%s', '%s', '%s')
        );
    }

    /**
     * AJAX handler for getting token balance.
     *
     * @since    1.0.0
     */
    public function ajax_get_token_balance() {
        // Check nonce
        check_ajax_referer('vortex_blockchain_nonce', 'nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to perform this action', 'vortex-ai-marketplace')));
            return;
        }
        
        $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
        
        if (empty($wallet_address)) {
            wp_send_json_error(array('message' => __('Wallet address is required', 'vortex-ai-marketplace')));
            return;
        }
        
        $balance = $this->get_token_balance($wallet_address);
        
        if (is_wp_error($balance)) {
            wp_send_json_error(array('message' => $balance->get_error_message()));
            return;
        }
        
        $token_info = $this->get_token_info();
        
        wp_send_json_success(array(
            'balance' => $balance,
            'formatted' => $balance . ' ' . $token_info['symbol'],
            'token' => $token_info
        ));
    }

    /**
     * AJAX handler for transferring tokens.
     *
     * @since    1.0.0
     */
    public function ajax_transfer_tokens() {
        // Check nonce
        check_ajax_referer('vortex_blockchain_nonce', 'nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to perform this action', 'vortex-ai-marketplace')));
            return;
        }
        
        $from_wallet = isset($_POST['from_wallet']) ? sanitize_text_field($_POST['from_wallet']) : '';
        $to_wallet = isset($_POST['to_wallet']) ? sanitize_text_field($_POST['to_wallet']) : '';
        $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
        
        if (empty($from_wallet) || empty($to_wallet) || empty($amount)) {
            wp_send_json_error(array('message' => __('All fields are required', 'vortex-ai-marketplace')));
            return;
        }
        
        $result = $this->transfer_tokens($from_wallet, $to_wallet, $amount);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array(
            'transaction_hash' => $result,
            'message' => __('Tokens transferred successfully', 'vortex-ai-marketplace')
        ));
    }

    /**
     * AJAX handler for approving tokens.
     *
     * @since    1.0.0
     */
    public function ajax_approve_tokens() {
        // Check nonce
        check_ajax_referer('vortex_blockchain_nonce', 'nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to perform this action', 'vortex-ai-marketplace')));
            return;
        }
        
        $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
        $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
        
        if (empty($wallet_address) || empty($amount)) {
            wp_send_json_error(array('message' => __('All fields are required', 'vortex-ai-marketplace')));
            return;
        }
        
        $result = $this->approve_tokens($wallet_address, $amount);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array(
            'transaction_hash' => $result,
            'message' => __('Tokens approved successfully', 'vortex-ai-marketplace')
        ));
    }

    /**
     * Verify token balance before checkout.
     *
     * @since    1.0.0
     * @param    array $cart_data Cart data.
     */
    public function verify_token_balance($cart_data) {
        // Only check for token payments
        if (!isset($cart_data['payment_method']) || $cart_data['payment_method'] !== 'tola_token') {
            return;
        }
        
        $wallet_address = isset($cart_data['wallet_address']) ? sanitize_text_field($cart_data['wallet_address']) : '';
        
        if (empty($wallet_address)) {
            wp_die(__('Wallet address is required for token payments', 'vortex-ai-marketplace'), __('Payment Error', 'vortex-ai-marketplace'), array('response' => 400));
        }
        
        $total_amount = isset($cart_data['total']) ? (float) $cart_data['total'] : 0;
        
        // Check if wallet has sufficient balance
        $has_balance = $this->has_sufficient_balance($wallet_address, $total_amount);
        
        if (is_wp_error($has_balance)) {
            wp_die($has_balance->get_error_message(), __('Payment Error', 'vortex-ai-marketplace'), array('response' => 400));
        }
        
        if (!$has_balance) {
            wp_die(__('Insufficient TOLA token balance', 'vortex-ai-marketplace'), __('Payment Error', 'vortex-ai-marketplace'), array('response' => 400));
        }
    }

    /**
     * Process token payment after checkout completion.
     *
     * @since    1.0.0
     * @param    int   $order_id Order ID.
     * @param    array $order_data Order data.
     */
    public function process_token_payment($order_id, $order_data) {
        // Only process token payments
        if (!isset($order_data['payment_method']) || $order_data['payment_method'] !== 'tola_token') {
            return;
        }
        
        $buyer_wallet = isset($order_data['wallet_address']) ? sanitize_text_field($order_data['wallet_address']) : '';
        $total_amount = isset($order_data['total']) ? (float) $order_data['total'] : 0;
        
        if (empty($buyer_wallet) || empty($total_amount)) {
            update_post_meta($order_id, '_vortex_payment_status', 'failed');
            update_post_meta($order_id, '_vortex_payment_error', __('Invalid payment data', 'vortex-ai-marketplace'));
            return;
        }
        
        // Process each item in the order
        foreach ($order_data['items'] as $item) {
            $artwork_id = $item['artwork_id'];
            $artist_id = get_post_meta($artwork_id, '_vortex_artwork_artist', true);
            
            if (!$artist_id) {
                continue;
            }
            
            $artist_wallet = get_post_meta($artist_id, '_vortex_artist_wallet_address', true);
            
            if (!$artist_wallet) {
                continue;
            }
            
            // Calculate artist's share and marketplace commission
            $item_price = $item['price'] * $item['quantity'];
            $commission_amount = $item_price * ($this->commission_rate / 100);
            $artist_amount = $item_price - $commission_amount;
            
            // Transfer tokens to artist
            $transfer_result = $this->transfer_tokens($buyer_wallet, $artist_wallet, $artist_amount);
            
            if (is_wp_error($transfer_result)) {
                update_post_meta($order_id, '_vortex_payment_status', 'failed');
                update_post_meta($order_id, '_vortex_payment_error', $transfer_result->get_error_message());
                return;
            }
            
            // Store transaction details
            update_post_meta($artwork_id, '_vortex_artwork_sold', true);
            update_post_meta($artwork_id, '_vortex_artwork_buyer', $buyer_wallet);
            update_post_meta($artwork_id, '_vortex_artwork_sale_tx', $transfer_result);
            
            // Transfer commission to marketplace wallet
            if ($commission_amount > 0 && !empty($this->marketplace_wallet)) {
                $commission_result = $this->transfer_tokens($buyer_wallet, $this->marketplace_wallet, $commission_amount);
                
                if (!is_wp_error($commission_result)) {
                    update_post_meta($order_id, '_vortex_commission_tx', $commission_result);
                }
            }
        }
        
        // Mark payment as complete
        update_post_meta($order_id, '_vortex_payment_status', 'complete');
        update_post_meta($order_id, '_vortex_payment_method', 'tola_token');
    }
} 