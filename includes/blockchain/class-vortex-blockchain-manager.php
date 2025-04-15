<?php
/**
 * VORTEX Blockchain Manager Class
 *
 * Manages all blockchain interactions with TOLA
 */

class VORTEX_Blockchain_Manager {
    private $tola_endpoint;
    private $contract_address;
    private $api_key;
    private $web3;
    
    public function __construct() {
        $this->tola_endpoint = get_option('vortex_tola_endpoint', 'https://api.tola.network/v1');
        $this->contract_address = get_option('vortex_contract_address', '');
        $this->api_key = get_option('vortex_tola_api_key', '');
        
        // Initialize Web3 connection if PHP extension is available
        if (class_exists('Web3')) {
            $this->web3 = new Web3\Web3($this->tola_endpoint);
        }
        
        // Add hooks for blockchain transactions
        add_action('vortex_mint_nft', array($this, 'mint_nft'), 10, 3);
        add_action('vortex_transfer_nft', array($this, 'transfer_nft'), 10, 4);
        add_action('vortex_update_nft_metadata', array($this, 'update_nft_metadata'), 10, 3);
    }
    
    /**
     * Send request to TOLA blockchain API
     */
    private function send_tola_request($endpoint, $method = 'GET', $data = array()) {
        $url = trailingslashit($this->tola_endpoint) . $endpoint;
        
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            )
        );
        
        if (!empty($data) && in_array($method, array('POST', 'PUT'))) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code >= 200 && $code < 300) {
            return array(
                'success' => true,
                'data' => json_decode($body, true)
            );
        } else {
            return array(
                'success' => false,
                'error' => json_decode($body, true),
                'code' => $code
            );
        }
    }
    
    /**
     * Mint new NFT on TOLA blockchain
     */
    public function mint_nft($artwork_id, $user_id, $metadata) {
        // Prepare metadata for blockchain
        $artwork_data = get_post($artwork_id);
        if (!$artwork_data) {
            return new WP_Error('invalid_artwork', 'Invalid artwork ID');
        }
        
        $nft_metadata = array(
            'name' => $artwork_data->post_title,
            'description' => $artwork_data->post_excerpt,
            'image' => get_the_post_thumbnail_url($artwork_id, 'full'),
            'attributes' => $metadata,
            'creator' => get_user_meta($user_id, 'vortex_wallet_address', true)
        );
        
        // Send minting request to TOLA
        $response = $this->send_tola_request('nft/mint', 'POST', array(
            'contract_address' => $this->contract_address,
            'recipient' => get_user_meta($user_id, 'vortex_wallet_address', true),
            'metadata' => $nft_metadata
        ));
        
        if ($response['success']) {
            // Store token ID and transaction hash
            update_post_meta($artwork_id, 'vortex_token_id', $response['data']['token_id']);
            update_post_meta($artwork_id, 'vortex_tx_hash', $response['data']['tx_hash']);
            update_post_meta($artwork_id, 'vortex_blockchain_status', 'minted');
            
            // Log the transaction
            do_action('vortex_blockchain_transaction', $response['data']['tx_hash'], $user_id, array(
                'action' => 'mint',
                'artwork_id' => $artwork_id,
                'token_id' => $response['data']['token_id']
            ));
            
            return $response['data'];
        } else {
            update_post_meta($artwork_id, 'vortex_blockchain_status', 'failed');
            update_post_meta($artwork_id, 'vortex_blockchain_error', $response['error']);
            return new WP_Error('mint_failed', $response['error']);
        }
    }
    
    /**
     * Transfer NFT to new owner
     */
    public function transfer_nft($token_id, $from_user_id, $to_user_id, $transaction_id) {
        $from_address = get_user_meta($from_user_id, 'vortex_wallet_address', true);
        $to_address = get_user_meta($to_user_id, 'vortex_wallet_address', true);
        
        if (empty($from_address) || empty($to_address)) {
            return new WP_Error('invalid_address', 'Missing wallet address');
        }
        
        // Send transfer request to TOLA
        $response = $this->send_tola_request('nft/transfer', 'POST', array(
            'contract_address' => $this->contract_address,
            'token_id' => $token_id,
            'from' => $from_address,
            'to' => $to_address
        ));
        
        if ($response['success']) {
            // Update transaction record
            update_post_meta($transaction_id, 'vortex_tx_hash', $response['data']['tx_hash']);
            update_post_meta($transaction_id, 'vortex_blockchain_status', 'transferred');
            
            // Log the transaction
            do_action('vortex_blockchain_transaction', $response['data']['tx_hash'], $from_user_id, array(
                'action' => 'transfer',
                'token_id' => $token_id,
                'to_user' => $to_user_id,
                'transaction_id' => $transaction_id
            ));
            
            return $response['data'];
        } else {
            update_post_meta($transaction_id, 'vortex_blockchain_status', 'failed');
            update_post_meta($transaction_id, 'vortex_blockchain_error', $response['error']);
            return new WP_Error('transfer_failed', $response['error']);
        }
    }
    
    /**
     * Update NFT metadata
     */
    public function update_nft_metadata($token_id, $user_id, $metadata) {
        // Verify ownership
        $artwork_id = $this->get_artwork_by_token_id($token_id);
        if (!$artwork_id || get_post_field('post_author', $artwork_id) != $user_id) {
            return new WP_Error('unauthorized', 'User is not the owner of this NFT');
        }
        
        // Send metadata update request
        $response = $this->send_tola_request('nft/metadata', 'PUT', array(
            'contract_address' => $this->contract_address,
            'token_id' => $token_id,
            'metadata' => $metadata
        ));
        
        if ($response['success']) {
            // Update artwork metadata
            update_post_meta($artwork_id, 'vortex_nft_metadata', $metadata);
            
            // Log the transaction
            do_action('vortex_blockchain_transaction', $response['data']['tx_hash'], $user_id, array(
                'action' => 'update_metadata',
                'token_id' => $token_id,
                'artwork_id' => $artwork_id
            ));
            
            return $response['data'];
        } else {
            return new WP_Error('update_failed', $response['error']);
        }
    }
    
    /**
     * Get blockchain metrics
     */
    public function get_blockchain_metrics() {
        $cache_key = 'vortex_blockchain_metrics';
        $metrics = get_transient($cache_key);
        
        if (false === $metrics) {
            // Fetch metrics from TOLA API
            $response = $this->send_tola_request('metrics/summary');
            
            if ($response['success']) {
                $metrics = $response['data'];
                set_transient($cache_key, $metrics, 15 * MINUTE_IN_SECONDS); // Cache for 15 minutes
            } else {
                return array();
            }
        }
        
        return $metrics;
    }
    
    /**
     * Get most active artists on blockchain
     */
    public function get_most_active_artists($limit = 10) {
        $response = $this->send_tola_request('metrics/artists', 'GET', array(
            'limit' => $limit
        ));
        
        if ($response['success']) {
            return $response['data'];
        }
        
        return array();
    }
    
    /**
     * Get most popular artwork categories
     */
    public function get_popular_categories($limit = 10) {
        $response = $this->send_tola_request('metrics/categories', 'GET', array(
            'limit' => $limit
        ));
        
        if ($response['success']) {
            return $response['data'];
        }
        
        return array();
    }
    
    /**
     * Verify artwork on blockchain
     */
    public function verify_artwork($token_id) {
        $response = $this->send_tola_request('nft/verify/' . $token_id);
        
        if ($response['success']) {
            return $response['data'];
        }
        
        return false;
    }
    
    /**
     * Get artwork by token ID
     */
    private function get_artwork_by_token_id($token_id) {
        global $wpdb;
        
        $meta_key = 'vortex_token_id';
        $artwork_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
            $meta_key,
            $token_id
        ));
        
        return $artwork_id;
    }
    
    /**
     * Check if TOLA connection is working
     */
    public function test_connection() {
        $response = $this->send_tola_request('status');
        return $response['success'];
    }
} 