<?php
/**
 * Blockchain Synchronization Enhancement
 *
 * Implements webhook notifications for real-time updates and batch processing
 * for large token transfers with the TOLA blockchain.
 *
 * @package VORTEX
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_Blockchain_Sync {
    
    private static $instance = null;
    private $webhook_endpoint;
    private $webhook_secret;
    private $batch_size = 50;
    
    /**
     * Get class instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->webhook_endpoint = rest_url('vortex/v1/blockchain-webhook');
        $this->webhook_secret = get_option('vortex_blockchain_webhook_secret', '');
        
        // Generate webhook secret if not exists
        if (empty($this->webhook_secret)) {
            $this->webhook_secret = wp_generate_password(32, true, true);
            update_option('vortex_blockchain_webhook_secret', $this->webhook_secret);
        }
        
        // Register REST API endpoint for webhook
        add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
        
        // Register batch processing hooks
        add_action('vortex_process_token_transfer_batch', array($this, 'process_token_transfer_batch'), 10, 2);
    }
    
    /**
     * Register webhook endpoint
     */
    public function register_webhook_endpoint() {
        register_rest_route('vortex/v1', '/blockchain-webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => array($this, 'validate_webhook')
        ));
    }
    
    /**
     * Validate incoming webhook
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function validate_webhook($request) {
        $signature = $request->get_header('X-TOLA-Signature');
        
        if (empty($signature)) {
            return false;
        }
        
        $payload = $request->get_body();
        $expected_signature = hash_hmac('sha256', $payload, $this->webhook_secret);
        
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Handle webhook notification
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_webhook($request) {
        $payload = $request->get_json_params();
        
        if (empty($payload) || empty($payload['event_type'])) {
            return new WP_REST_Response(array('status' => 'error', 'message' => 'Invalid payload'), 400);
        }
        
        switch ($payload['event_type']) {
            case 'token_transfer':
                $this->handle_token_transfer($payload);
                break;
                
            case 'contract_update':
                $this->handle_contract_update($payload);
                break;
                
            case 'new_artwork':
                $this->handle_new_artwork($payload);
                break;
                
            case 'marketplace_sale':
                $this->handle_marketplace_sale($payload);
                break;
                
            default:
                // Log unknown event type
                error_log('Unknown blockchain event type: ' . $payload['event_type']);
                break;
        }
        
        return new WP_REST_Response(array('status' => 'success'), 200);
    }
    
    /**
     * Handle token transfer event
     */
    private function handle_token_transfer($payload) {
        if (empty($payload['data']) || 
            empty($payload['data']['from']) || 
            empty($payload['data']['to']) || 
            empty($payload['data']['amount'])) {
            return;
        }
        
        $data = $payload['data'];
        
        // Check if this is a large transfer that needs batching
        if ($data['amount'] > 10000) { // Threshold for "large" transfer
            $this->batch_process_token_transfer($data);
        } else {
            $this->process_single_token_transfer($data);
        }
        
        // Update metrics cache
        do_action('vortex_update_dao_metrics');
    }
    
    /**
     * Process single token transfer
     */
    private function process_single_token_transfer($data) {
        global $wpdb;
        
        // Record the transfer
        $wpdb->insert(
            $wpdb->prefix . 'vortex_token_transfers',
            array(
                'from_address' => $data['from'],
                'to_address' => $data['to'],
                'amount' => $data['amount'],
                'transaction_hash' => $data['tx_hash'],
                'block_number' => $data['block_number'],
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%f', '%s', '%d', '%s')
        );
        
        // Update user balances if addresses are associated with users
        $from_user_id = $this->get_user_id_by_wallet($data['from']);
        $to_user_id = $this->get_user_id_by_wallet($data['to']);
        
        if ($from_user_id) {
            $this->update_user_token_balance($from_user_id);
        }
        
        if ($to_user_id) {
            $this->update_user_token_balance($to_user_id);
        }
    }
    
    /**
     * Batch process token transfer
     */
    private function batch_process_token_transfer($data) {
        $total_amount = $data['amount'];
        $batch_count = ceil($total_amount / $this->batch_size);
        
        for ($i = 0; $i < $batch_count; $i++) {
            $batch_amount = ($i == $batch_count - 1) 
                ? $total_amount - ($this->batch_size * ($batch_count - 1)) 
                : $this->batch_size;
            
            $batch_data = array(
                'from' => $data['from'],
                'to' => $data['to'],
                'amount' => $batch_amount,
                'tx_hash' => $data['tx_hash'],
                'block_number' => $data['block_number'],
                'batch' => $i + 1,
                'total_batches' => $batch_count
            );
            
            wp_schedule_single_event(
                time() + ($i * 10), // 10 seconds delay between batches
                'vortex_process_token_transfer_batch',
                array($batch_data, $i === ($batch_count - 1)) // Last batch flag
            );
        }
    }
    
    /**
     * Process token transfer batch (scheduled action)
     */
    public function process_token_transfer_batch($batch_data, $is_last_batch) {
        $this->process_single_token_transfer($batch_data);
        
        // If this is the last batch, perform final updates
        if ($is_last_batch) {
            // Refresh metrics
            do_action('vortex_update_dao_metrics');
            
            // Notify about completed large transfer
            $this->notify_large_transfer_complete($batch_data);
        }
    }
    
    /**
     * Handle contract update event
     */
    private function handle_contract_update($payload) {
        if (empty($payload['data']) || empty($payload['data']['contract_id'])) {
            return;
        }
        
        $data = $payload['data'];
        
        // Update artwork contract data
        global $wpdb;
        $artwork_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = 'vortex_tola_contract_id' AND meta_value = %s",
            $data['contract_id']
        ));
        
        if ($artwork_id) {
            update_post_meta($artwork_id, 'vortex_tola_contract_status', $data['status']);
            update_post_meta($artwork_id, 'vortex_tola_contract_updated', current_time('mysql'));
            
            if (!empty($data['metadata'])) {
                update_post_meta($artwork_id, 'vortex_tola_contract_metadata', $data['metadata']);
            }
        }
    }
    
    /**
     * Handle new artwork event
     */
    private function handle_new_artwork($payload) {
        if (empty($payload['data']) || 
            empty($payload['data']['token_id']) || 
            empty($payload['data']['creator'])) {
            return;
        }
        
        $data = $payload['data'];
        
        // Check if this artwork is already in our database
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = 'vortex_tola_token_id' AND meta_value = %s",
            $data['token_id']
        ));
        
        if ($exists) {
            // Update existing record
            update_post_meta($exists, 'vortex_tola_blockchain_verified', true);
            return;
        }
        
        // Get user by wallet address
        $user_id = $this->get_user_id_by_wallet($data['creator']);
        
        if (!$user_id) {
            // Cannot proceed without a user
            error_log('Cannot import artwork: no user found for wallet ' . $data['creator']);
            return;
        }
        
        // Create new artwork post
        $artwork_id = wp_insert_post(array(
            'post_title' => !empty($data['title']) ? $data['title'] : 'Untitled Artwork',
            'post_content' => !empty($data['description']) ? $data['description'] : '',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_type' => 'vortex_artwork'
        ));
        
        if (is_wp_error($artwork_id)) {
            error_log('Failed to create artwork: ' . $artwork_id->get_error_message());
            return;
        }
        
        // Set metadata
        update_post_meta($artwork_id, 'vortex_tola_token_id', $data['token_id']);
        update_post_meta($artwork_id, 'vortex_tola_contract_id', $data['contract_id']);
        update_post_meta($artwork_id, 'vortex_tola_blockchain_verified', true);
        
        // Set additional metadata
        if (!empty($data['metadata'])) {
            foreach ($data['metadata'] as $key => $value) {
                update_post_meta($artwork_id, 'vortex_' . $key, $value);
            }
        }
    }
    
    /**
     * Handle marketplace sale event
     */
    private function handle_marketplace_sale($payload) {
        if (empty($payload['data']) || 
            empty($payload['data']['token_id']) || 
            empty($payload['data']['seller']) ||
            empty($payload['data']['buyer']) ||
            empty($payload['data']['price'])) {
            return;
        }
        
        $data = $payload['data'];
        
        // Find artwork
        global $wpdb;
        $artwork_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = 'vortex_tola_token_id' AND meta_value = %s",
            $data['token_id']
        ));
        
        if (!$artwork_id) {
            error_log('Cannot record sale: artwork not found for token ' . $data['token_id']);
            return;
        }
        
        // Get user IDs
        $seller_id = $this->get_user_id_by_wallet($data['seller']);
        $buyer_id = $this->get_user_id_by_wallet($data['buyer']);
        
        // Record sale
        $wpdb->insert(
            $wpdb->prefix . 'vortex_artwork_sales',
            array(
                'artwork_id' => $artwork_id,
                'seller_id' => $seller_id ? $seller_id : 0,
                'buyer_id' => $buyer_id ? $buyer_id : 0,
                'price' => $data['price'],
                'currency' => !empty($data['currency']) ? $data['currency'] : 'TOLA',
                'transaction_hash' => $data['tx_hash'],
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%f', '%s', '%s', '%s')
        );
        
        // Update artwork owner
        update_post_meta($artwork_id, 'vortex_owner', $buyer_id);
        update_post_meta($artwork_id, 'vortex_last_sale_price', $data['price']);
        update_post_meta($artwork_id, 'vortex_last_sale_date', current_time('mysql'));
        
        // Trigger sale actions
        do_action('vortex_artwork_sold', $artwork_id, $seller_id, $buyer_id, $data['price']);
    }
    
    /**
     * Get user ID by wallet address
     */
    private function get_user_id_by_wallet($wallet_address) {
        global $wpdb;
        
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}vortex_wallet_addresses 
            WHERE wallet_address = %s AND verified = 1",
            $wallet_address
        ));
        
        return $user_id;
    }
    
    /**
     * Update user token balance
     */
    private function update_user_token_balance($user_id) {
        $token = VORTEX_DAO_Token::get_instance();
        $wallet_address = get_user_meta($user_id, 'vortex_wallet_address', true);
        
        if (empty($wallet_address)) {
            return;
        }
        
        $token_address = get_option('vortex_token_address', '');
        $balance = $token->get_balance($wallet_address, $token_address);
        
        update_user_meta($user_id, 'vortex_token_balance', $balance);
        update_user_meta($user_id, 'vortex_token_balance_updated', current_time('mysql'));
    }
    
    /**
     * Notify about completed large transfer
     */
    private function notify_large_transfer_complete($batch_data) {
        $admin_email = get_option('admin_email');
        
        wp_mail(
            $admin_email,
            'Large Token Transfer Completed',
            sprintf(
                'A large token transfer of %s TOLA from %s to %s has been processed in %d batches. Transaction hash: %s',
                number_format($batch_data['amount'] * $batch_data['total_batches'], 2),
                $batch_data['from'],
                $batch_data['to'],
                $batch_data['total_batches'],
                $batch_data['tx_hash']
            )
        );
    }
    
    /**
     * Register webhook with TOLA blockchain
     * 
     * @return bool Success status
     */
    public function register_webhook_with_tola() {
        // Get API credentials
        $api_key = get_option('vortex_tola_api_key', '');
        
        if (empty($api_key)) {
            return false;
        }
        
        // Prepare request
        $api_endpoint = 'https://api.tola-blockchain.io/v1/webhooks';
        $request_data = array(
            'url' => $this->webhook_endpoint,
            'secret' => $this->webhook_secret,
            'events' => array(
                'token_transfer',
                'contract_update',
                'new_artwork',
                'marketplace_sale'
            )
        );
        
        // Make API request
        $response = wp_remote_post(
            $api_endpoint,
            array(
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key
                ),
                'body' => json_encode($request_data)
            )
        );
        
        if (is_wp_error($response)) {
            error_log('Failed to register webhook: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200 && $status_code !== 201) {
            error_log('Failed to register webhook: Received status code ' . $status_code);
            return false;
        }
        
        // Save webhook ID
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($body['webhook_id'])) {
            update_option('vortex_tola_webhook_id', $body['webhook_id']);
        }
        
        return true;
    }
}

// Initialize Blockchain Sync
function vortex_blockchain_sync() {
    return VORTEX_Blockchain_Sync::get_instance();
}
vortex_blockchain_sync(); 