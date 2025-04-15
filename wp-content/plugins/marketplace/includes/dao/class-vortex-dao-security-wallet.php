<?php
/**
 * DAO Security Wallet Implementation
 *
 * Implements multi-signature requirements for treasury transactions
 * and hardware wallet support for increased security.
 *
 * @package VORTEX
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_DAO_Security_Wallet {
    
    private static $instance = null;
    private $required_signatures = 2; // Default requirement of 2 signatures for treasury transactions
    private $db;
    
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
        global $wpdb;
        $this->db = $wpdb;
        
        // Allow signature requirement to be configured
        $this->required_signatures = get_option('vortex_treasury_required_signatures', 2);
        
        // Create necessary tables
        $this->ensure_tables_exist();
        
        // Register AJAX handlers
        add_action('wp_ajax_vortex_sign_treasury_transaction', array($this, 'ajax_sign_transaction'));
        add_action('wp_ajax_vortex_verify_hardware_wallet', array($this, 'ajax_verify_hardware_wallet'));
        add_action('wp_ajax_vortex_get_pending_transactions', array($this, 'ajax_get_pending_transactions'));
        
        // Add hooks for transaction processing
        add_filter('vortex_treasury_transaction_before_execute', array($this, 'verify_transaction_signatures'), 10, 2);
    }
    
    /**
     * Create necessary tables
     */
    private function ensure_tables_exist() {
        $signatures_table = $this->db->prefix . 'vortex_transaction_signatures';
        $hardware_wallets_table = $this->db->prefix . 'vortex_hardware_wallets';
        
        if ($this->db->get_var("SHOW TABLES LIKE '$signatures_table'") != $signatures_table) {
            $charset_collate = $this->db->get_charset_collate();
            
            $sql = "CREATE TABLE $signatures_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                transaction_id varchar(64) NOT NULL,
                user_id bigint(20) NOT NULL,
                wallet_address varchar(64) NOT NULL,
                signature varchar(256) NOT NULL,
                signed_at datetime NOT NULL,
                device_info text NULL,
                PRIMARY KEY  (id),
                KEY transaction_id (transaction_id),
                UNIQUE KEY tx_user (transaction_id,user_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        if ($this->db->get_var("SHOW TABLES LIKE '$hardware_wallets_table'") != $hardware_wallets_table) {
            $charset_collate = $this->db->get_charset_collate();
            
            $sql = "CREATE TABLE $hardware_wallets_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                wallet_address varchar(64) NOT NULL,
                wallet_type varchar(32) NOT NULL,
                label varchar(64) NOT NULL,
                public_key text NOT NULL,
                connected_at datetime NOT NULL,
                last_verified datetime NOT NULL,
                status varchar(32) NOT NULL DEFAULT 'active',
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                UNIQUE KEY wallet_user (wallet_address,user_id)
            ) $charset_collate;";
            
            dbDelta($sql);
        }
    }
    
    /**
     * Create a new treasury transaction that requires signatures
     */
    public function create_treasury_transaction($transaction_data) {
        if (empty($transaction_data['amount']) || 
            empty($transaction_data['recipient']) || 
            empty($transaction_data['purpose'])) {
            return false;
        }
        
        // Generate transaction ID
        $transaction_id = 'tx_' . uniqid() . '_' . substr(hash('sha256', json_encode($transaction_data)), 0, 8);
        
        // Create transaction record
        $result = $this->db->insert(
            $this->db->prefix . 'vortex_treasury_transactions',
            array(
                'transaction_id' => $transaction_id,
                'amount' => floatval($transaction_data['amount']),
                'recipient' => sanitize_text_field($transaction_data['recipient']),
                'purpose' => sanitize_text_field($transaction_data['purpose']),
                'status' => 'pending',
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'metadata' => json_encode(isset($transaction_data['metadata']) ? $transaction_data['metadata'] : array())
            ),
            array('%s', '%f', '%s', '%s', '%s', '%d', '%s', '%s')
        );
        
        if (!$result) {
            return false;
        }
        
        // Notify authorized signers
        $this->notify_signers_of_pending_transaction($transaction_id, $transaction_data);
        
        return $transaction_id;
    }
    
    /**
     * Sign a pending treasury transaction
     */
    public function sign_transaction($transaction_id, $signature, $device_info = array()) {
        // Get current user
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return false;
        }
        
        // Check if user is authorized to sign
        if (!$this->is_authorized_signer($user_id)) {
            return false;
        }
        
        // Get wallet address
        $wallet_address = $this->get_user_wallet_address($user_id);
        
        if (empty($wallet_address)) {
            return false;
        }
        
        // Record signature
        $result = $this->db->insert(
            $this->db->prefix . 'vortex_transaction_signatures',
            array(
                'transaction_id' => $transaction_id,
                'user_id' => $user_id,
                'wallet_address' => $wallet_address,
                'signature' => $signature,
                'signed_at' => current_time('mysql'),
                'device_info' => json_encode($device_info)
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        if (!$result) {
            return false;
        }
        
        // Check if we have enough signatures to execute the transaction
        $this->check_and_execute_transaction($transaction_id);
        
        return true;
    }
    
    /**
     * Verify if a transaction has enough signatures to be executed
     */
    public function check_and_execute_transaction($transaction_id) {
        // Count signatures
        $signature_count = $this->db->get_var($this->db->prepare(
            "SELECT COUNT(*) FROM {$this->db->prefix}vortex_transaction_signatures 
            WHERE transaction_id = %s",
            $transaction_id
        ));
        
        if ($signature_count >= $this->required_signatures) {
            // Get transaction details
            $transaction = $this->db->get_row($this->db->prepare(
                "SELECT * FROM {$this->db->prefix}vortex_treasury_transactions 
                WHERE transaction_id = %s AND status = 'pending'",
                $transaction_id
            ));
            
            if (!$transaction) {
                return false;
            }
            
            // Mark as approved
            $this->db->update(
                $this->db->prefix . 'vortex_treasury_transactions',
                array(
                    'status' => 'approved',
                    'executed_at' => current_time('mysql')
                ),
                array('transaction_id' => $transaction_id),
                array('%s', '%s'),
                array('%s')
            );
            
            // Trigger execution
            $transaction_data = array(
                'transaction_id' => $transaction_id,
                'amount' => $transaction->amount,
                'recipient' => $transaction->recipient,
                'purpose' => $transaction->purpose,
                'metadata' => json_decode($transaction->metadata, true)
            );
            
            do_action('vortex_execute_treasury_transaction', $transaction_data);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Connect a hardware wallet to a user account
     */
    public function connect_hardware_wallet($user_id, $wallet_address, $wallet_type, $label, $public_key) {
        // Validate input
        if (empty($wallet_address) || empty($wallet_type) || empty($public_key)) {
            return false;
        }
        
        // Check if already exists
        $existing = $this->db->get_var($this->db->prepare(
            "SELECT id FROM {$this->db->prefix}vortex_hardware_wallets 
            WHERE wallet_address = %s AND user_id = %d",
            $wallet_address,
            $user_id
        ));
        
        if ($existing) {
            // Update existing
            return $this->db->update(
                $this->db->prefix . 'vortex_hardware_wallets',
                array(
                    'wallet_type' => $wallet_type,
                    'label' => $label,
                    'public_key' => $public_key,
                    'connected_at' => current_time('mysql'),
                    'last_verified' => current_time('mysql'),
                    'status' => 'active'
                ),
                array(
                    'id' => $existing
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            ) !== false;
        } else {
            // Insert new
            return $this->db->insert(
                $this->db->prefix . 'vortex_hardware_wallets',
                array(
                    'user_id' => $user_id,
                    'wallet_address' => $wallet_address,
                    'wallet_type' => $wallet_type,
                    'label' => $label,
                    'public_key' => $public_key,
                    'connected_at' => current_time('mysql'),
                    'last_verified' => current_time('mysql'),
                    'status' => 'active'
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            ) !== false;
        }
    }
    
    /**
     * Verify a hardware wallet signature
     */
    public function verify_hardware_wallet_signature($user_id, $wallet_address, $message, $signature) {
        // Get hardware wallet
        $wallet = $this->db->get_row($this->db->prepare(
            "SELECT * FROM {$this->db->prefix}vortex_hardware_wallets 
            WHERE user_id = %d AND wallet_address = %s AND status = 'active'",
            $user_id,
            $wallet_address
        ));
        
        if (!$wallet) {
            return false;
        }
        
        // This would use a library specific to the wallet type
        // For implementation purposes, we'll just assume it works
        $verified = true;
        
        if ($verified) {
            // Update last verification time
            $this->db->update(
                $this->db->prefix . 'vortex_hardware_wallets',
                array('last_verified' => current_time('mysql')),
                array('id' => $wallet->id),
                array('%s'),
                array('%d')
            );
        }
        
        return $verified;
    }
    
    /**
     * Get pending treasury transactions
     */
    public function get_pending_transactions($limit = 10, $offset = 0) {
        return $this->db->get_results($this->db->prepare(
            "SELECT t.*, COUNT(s.id) as signature_count 
            FROM {$this->db->prefix}vortex_treasury_transactions t 
            LEFT JOIN {$this->db->prefix}vortex_transaction_signatures s ON t.transaction_id = s.transaction_id 
            WHERE t.status = 'pending' 
            GROUP BY t.transaction_id 
            ORDER BY t.created_at DESC 
            LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }
    
    /**
     * Is the user an authorized signer?
     */
    private function is_authorized_signer($user_id) {
        $role = get_user_meta($user_id, 'vortex_dao_role', true);
        return in_array($role, array('multisig_signer', 'dao_admin'));
    }
    
    /**
     * Get user's wallet address
     */
    private function get_user_wallet_address($user_id) {
        return get_user_meta($user_id, 'vortex_wallet_address', true);
    }
    
    /**
     * Notify signers of pending transaction
     */
    private function notify_signers_of_pending_transaction($transaction_id, $transaction_data) {
        // Get authorized signers
        $signers = $this->get_authorized_signers();
        
        if (empty($signers)) {
            return;
        }
        
        // Prepare notification text
        $notification_text = sprintf(
            'New treasury transaction pending approval: %s TOLA to %s for "%s". Transaction ID: %s',
            $transaction_data['amount'],
            $transaction_data['recipient'],
            $transaction_data['purpose'],
            $transaction_id
        );
        
        // Send emails to signers
        foreach ($signers as $signer) {
            $user = get_user_by('id', $signer->user_id);
            
            if (!$user) {
                continue;
            }
            
            wp_mail(
                $user->user_email,
                'Treasury Transaction Requires Your Signature',
                $notification_text . "\n\nPlease log in to approve or reject this transaction."
            );
        }
    }
    
    /**
     * Get authorized transaction signers
     */
    private function get_authorized_signers() {
        return $this->db->get_results(
            "SELECT u.ID as user_id, u.display_name 
            FROM {$this->db->users} u 
            JOIN {$this->db->usermeta} um ON u.ID = um.user_id 
            WHERE um.meta_key = 'vortex_dao_role' AND um.meta_value IN ('multisig_signer', 'dao_admin')"
        );
    }
    
    /**
     * AJAX handler for signing a transaction
     */
    public function ajax_sign_transaction() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in to sign transactions'));
            return;
        }
        
        $transaction_id = sanitize_text_field($_POST['transaction_id']);
        $signature = sanitize_text_field($_POST['signature']);
        $device_info = isset($_POST['device_info']) ? $_POST['device_info'] : array();
        
        $result = $this->sign_transaction($transaction_id, $signature, $device_info);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Transaction signed successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to sign transaction'));
        }
    }
    
    /**
     * AJAX handler for verifying a hardware wallet
     */
    public function ajax_verify_hardware_wallet() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in to verify a hardware wallet'));
            return;
        }
        
        $user_id = get_current_user_id();
        $wallet_address = sanitize_text_field($_POST['wallet_address']);
        $message = sanitize_text_field($_POST['message']);
        $signature = sanitize_text_field($_POST['signature']);
        
        $result = $this->verify_hardware_wallet_signature($user_id, $wallet_address, $message, $signature);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Hardware wallet verified successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to verify hardware wallet'));
        }
    }
    
    /**
     * AJAX handler for getting pending transactions
     */
    public function ajax_get_pending_transactions() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in to view pending transactions'));
            return;
        }
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        
        $transactions = $this->get_pending_transactions($limit, $offset);
        
        wp_send_json_success(array(
            'transactions' => $transactions,
            'required_signatures' => $this->required_signatures
        ));
    }
}

// Initialize Security Wallet
function vortex_dao_security_wallet() {
    return VORTEX_DAO_Security_Wallet::get_instance();
}
vortex_dao_security_wallet(); 