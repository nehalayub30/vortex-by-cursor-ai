<?php
/**
 * VORTEX Wallet Addresses Handler
 *
 * Manages user wallet associations and verification
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_Wallet_Addresses {
    
    private static $instance = null;
    
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
        add_action('wp_ajax_vortex_verify_wallet', array($this, 'ajax_verify_wallet'));
        add_action('wp_ajax_vortex_add_wallet', array($this, 'ajax_add_wallet'));
        add_action('wp_ajax_vortex_remove_wallet', array($this, 'ajax_remove_wallet'));
        add_action('wp_ajax_vortex_set_primary_wallet', array($this, 'ajax_set_primary_wallet'));
    }
    
    /**
     * Add a new wallet address for a user
     *
     * @param array $wallet_data Wallet information
     * @return int|false The ID of the new wallet record, or false on failure
     */
    public function add_wallet($wallet_data) {
        global $wpdb;
        
        // Validate required data
        if (empty($wallet_data['user_id']) || 
            empty($wallet_data['wallet_address'])) {
            return false;
        }
        
        // Check if wallet already exists
        $existing = $this->get_wallet_by_address($wallet_data['wallet_address']);
        if ($existing) {
            return false;
        }
        
        // Check if this would be the user's first wallet
        $is_first = !$this->get_user_wallets($wallet_data['user_id']);
        
        // Prepare wallet data
        $data = array(
            'user_id' => intval($wallet_data['user_id']),
            'wallet_address' => sanitize_text_field($wallet_data['wallet_address']),
            'wallet_type' => isset($wallet_data['wallet_type']) ? 
                sanitize_text_field($wallet_data['wallet_type']) : 'solana',
            'is_primary' => $is_first ? 1 : 0,
            'verified' => 0,
            'token_balance' => 0,
            'created_at' => current_time('mysql')
        );
        
        // Insert wallet record
        $result = $wpdb->insert(
            $wpdb->prefix . 'vortex_wallet_addresses',
            $data,
            array(
                '%d', // user_id
                '%s', // wallet_address
                '%s', // wallet_type
                '%d', // is_primary
                '%d', // verified
                '%f', // token_balance
                '%s'  // created_at
            )
        );
        
        if ($result === false) {
            return false;
        }
        
        do_action('vortex_wallet_added', $wpdb->insert_id, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Verify a wallet address
     *
     * @param int    $wallet_id Wallet ID
     * @param string $signature Verification signature
     * @return bool Success or failure
     */
    public function verify_wallet($wallet_id, $signature) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'vortex_wallet_addresses',
            array(
                'verified' => 1,
                'verification_signature' => $signature
            ),
            array('id' => $wallet_id),
            array('%d', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            do_action('vortex_wallet_verified', $wallet_id, $signature);
        }
        
        return $result !== false;
    }
    
    /**
     * Set a wallet as primary for a user
     *
     * @param int $wallet_id Wallet ID
     * @return bool Success or failure
     */
    public function set_primary_wallet($wallet_id) {
        global $wpdb;
        
        // Get wallet details
        $wallet = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_wallet_addresses WHERE id = %d",
                $wallet_id
            ),
            ARRAY_A
        );
        
        if (!$wallet || !$wallet['verified']) {
            return false;
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        // Remove primary status from other wallets
        $wpdb->update(
            $wpdb->prefix . 'vortex_wallet_addresses',
            array('is_primary' => 0),
            array('user_id' => $wallet['user_id']),
            array('%d'),
            array('%d')
        );
        
        // Set new primary wallet
        $result = $wpdb->update(
            $wpdb->prefix . 'vortex_wallet_addresses',
            array('is_primary' => 1),
            array('id' => $wallet_id),
            array('%d'),
            array('%d')
        );
        
        if ($result !== false) {
            $wpdb->query('COMMIT');
            do_action('vortex_primary_wallet_changed', $wallet_id, $wallet['user_id']);
            return true;
        } else {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }
    
    /**
     * Update wallet token balance
     *
     * @param int   $wallet_id Wallet ID
     * @param float $balance New balance
     * @return bool Success or failure
     */
    public function update_wallet_balance($wallet_id, $balance) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'vortex_wallet_addresses',
            array(
                'token_balance' => $balance,
                'last_balance_update' => current_time('mysql')
            ),
            array('id' => $wallet_id),
            array('%f', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get wallet by address
     *
     * @param string $address Wallet address
     * @return array|null Wallet data or null if not found
     */
    public function get_wallet_by_address($address) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_wallet_addresses
                WHERE wallet_address = %s",
                $address
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get user's wallets
     *
     * @param int $user_id User ID
     * @return array Array of wallet records
     */
    public function get_user_wallets($user_id) {
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_wallet_addresses
                WHERE user_id = %d
                ORDER BY is_primary DESC, created_at DESC",
                $user_id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get user's primary wallet
     *
     * @param int $user_id User ID
     * @return array|null Primary wallet data or null if not found
     */
    public function get_user_primary_wallet($user_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_wallet_addresses
                WHERE user_id = %d AND is_primary = 1",
                $user_id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Remove a wallet address
     *
     * @param int $wallet_id Wallet ID
     * @return bool Success or failure
     */
    public function remove_wallet($wallet_id) {
        global $wpdb;
        
        $wallet = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_wallet_addresses WHERE id = %d",
                $wallet_id
            ),
            ARRAY_A
        );
        
        if (!$wallet || $wallet['is_primary']) {
            return false;
        }
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'vortex_wallet_addresses',
            array('id' => $wallet_id),
            array('%d')
        );
        
        if ($result !== false) {
            do_action('vortex_wallet_removed', $wallet_id, $wallet);
        }
        
        return $result !== false;
    }
    
    /**
     * AJAX handler for wallet verification
     */
    public function ajax_verify_wallet() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        $wallet_id = isset($_POST['wallet_id']) ? intval($_POST['wallet_id']) : 0;
        $signature = isset($_POST['signature']) ? sanitize_text_field($_POST['signature']) : '';
        
        if (!$wallet_id || !$signature) {
            wp_send_json_error('Invalid data');
            return;
        }
        
        $result = $this->verify_wallet($wallet_id, $signature);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Verification failed');
        }
    }
    
    /**
     * AJAX handler for adding wallet
     */
    public function ajax_add_wallet() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
            return;
        }
        
        $wallet_data = array(
            'user_id' => get_current_user_id(),
            'wallet_address' => isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '',
            'wallet_type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'solana'
        );
        
        $wallet_id = $this->add_wallet($wallet_data);
        
        if ($wallet_id) {
            wp_send_json_success(array('wallet_id' => $wallet_id));
        } else {
            wp_send_json_error('Failed to add wallet');
        }
    }
    
    /**
     * AJAX handler for removing wallet
     */
    public function ajax_remove_wallet() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
            return;
        }
        
        $wallet_id = isset($_POST['wallet_id']) ? intval($_POST['wallet_id']) : 0;
        
        if (!$wallet_id) {
            wp_send_json_error('Invalid wallet ID');
            return;
        }
        
        $result = $this->remove_wallet($wallet_id);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to remove wallet');
        }
    }
    
    /**
     * AJAX handler for setting primary wallet
     */
    public function ajax_set_primary_wallet() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
            return;
        }
        
        $wallet_id = isset($_POST['wallet_id']) ? intval($_POST['wallet_id']) : 0;
        
        if (!$wallet_id) {
            wp_send_json_error('Invalid wallet ID');
            return;
        }
        
        $result = $this->set_primary_wallet($wallet_id);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to set primary wallet');
        }
    }
}

// Initialize Wallet Addresses class
$vortex_wallet_addresses = VORTEX_Wallet_Addresses::get_instance(); 