<?php
/**
 * VORTEX Marketplace Wallet Protection
 *
 * Utility class for securing wallet addresses and transaction data.
 * Uses the VORTEX_Encryption_Keys class for secure data handling.
 *
 * @package VORTEX_Marketplace
 * @subpackage Security
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include the encryption keys class
require_once plugin_dir_path(__FILE__) . 'encryption-keys.php';

/**
 * Wallet Protection and Security Utility
 */
class VORTEX_Wallet_Protection {
    /**
     * Store wallet address securely for a user
     *
     * @param int $user_id The ID of the user
     * @param string $wallet_address The wallet address to store
     * @return bool True on success, false on failure
     */
    public static function store_wallet_address($user_id, $wallet_address) {
        if (empty($user_id) || empty($wallet_address)) {
            return false;
        }
        
        // Validate wallet address format
        if (!self::validate_wallet_address($wallet_address)) {
            return false;
        }
        
        // Encrypt the wallet address
        $encrypted_address = VORTEX_Encryption_Keys::encrypt($wallet_address);
        
        // Store the encrypted address as user meta
        return update_user_meta($user_id, '_vortex_encrypted_wallet_address', $encrypted_address);
    }
    
    /**
     * Retrieve wallet address for a user
     *
     * @param int $user_id The ID of the user
     * @return string|false The wallet address on success, false on failure
     */
    public static function get_wallet_address($user_id) {
        if (empty($user_id)) {
            return false;
        }
        
        // Get the encrypted wallet address
        $encrypted_address = get_user_meta($user_id, '_vortex_encrypted_wallet_address', true);
        
        if (empty($encrypted_address)) {
            return false;
        }
        
        // Decrypt the wallet address
        return VORTEX_Encryption_Keys::decrypt($encrypted_address);
    }
    
    /**
     * Validate a wallet address format (supports Solana and Ethereum addresses)
     *
     * @param string $wallet_address The wallet address to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate_wallet_address($wallet_address) {
        // Validate Solana wallet address (Base58 encoded, 32-44 characters)
        if (preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $wallet_address)) {
            return true;
        }
        
        // Validate Ethereum wallet address (0x followed by 40 hex characters)
        if (preg_match('/^0x[a-fA-F0-9]{40}$/', $wallet_address)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Obfuscate a wallet address for display
     *
     * @param string $wallet_address The wallet address to obfuscate
     * @return string The obfuscated wallet address
     */
    public static function obfuscate_wallet_address($wallet_address) {
        if (empty($wallet_address)) {
            return '';
        }
        
        $length = strlen($wallet_address);
        
        // Show first 4 and last 4 characters, replace the rest with asterisks
        if ($length > 8) {
            return substr($wallet_address, 0, 4) . '...' . substr($wallet_address, -4);
        } else if ($length > 4) {
            return substr($wallet_address, 0, 2) . '...' . substr($wallet_address, -2);
        } else {
            return $wallet_address;
        }
    }
    
    /**
     * Log wallet activity securely
     *
     * @param int $user_id The ID of the user
     * @param string $action The action performed
     * @param array $data Additional data about the action
     * @return bool True on success, false on failure
     */
    public static function log_wallet_activity($user_id, $action, $data = []) {
        global $wpdb;
        
        if (empty($user_id) || empty($action)) {
            return false;
        }
        
        // Prepare data for logging
        $log_data = [
            'user_id' => $user_id,
            'action' => $action,
            'ip_address' => self::get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'timestamp' => current_time('mysql'),
            'additional_data' => maybe_serialize($data)
        ];
        
        // Check if the logs table exists
        $table_name = $wpdb->prefix . 'vortex_wallet_activity_logs';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // Create the logs table if it doesn't exist
            self::create_logs_table();
        }
        
        // Insert the log entry
        $result = $wpdb->insert(
            $table_name,
            $log_data,
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );
        
        return $result !== false;
    }
    
    /**
     * Create the wallet activity logs table
     *
     * @return bool True on success, false on failure
     */
    private static function create_logs_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_wallet_activity_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action varchar(255) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            timestamp datetime NOT NULL,
            additional_data longtext NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        return dbDelta($sql);
    }
    
    /**
     * Get user's IP address
     *
     * @return string The user's IP address
     */
    private static function get_user_ip() {
        // Check for proxy
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Sanitize IP
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }
    
    /**
     * Generate a secure transaction reference ID
     *
     * @return string The transaction reference ID
     */
    public static function generate_transaction_reference() {
        return 'VTXTRX' . strtoupper(VORTEX_Encryption_Keys::generate_token(16));
    }
    
    /**
     * Store transaction details securely
     *
     * @param array $transaction_data Transaction data to store
     * @return int|false The transaction ID on success, false on failure
     */
    public static function store_transaction($transaction_data) {
        global $wpdb;
        
        if (empty($transaction_data)) {
            return false;
        }
        
        // Required fields
        $required_fields = ['user_id', 'transaction_type', 'amount'];
        foreach ($required_fields as $field) {
            if (!isset($transaction_data[$field])) {
                return false;
            }
        }
        
        // Generate transaction reference if not provided
        if (!isset($transaction_data['transaction_reference'])) {
            $transaction_data['transaction_reference'] = self::generate_transaction_reference();
        }
        
        // Add timestamp if not provided
        if (!isset($transaction_data['timestamp'])) {
            $transaction_data['timestamp'] = current_time('mysql');
        }
        
        // Encrypt sensitive data
        if (isset($transaction_data['wallet_address'])) {
            $transaction_data['wallet_address'] = VORTEX_Encryption_Keys::encrypt($transaction_data['wallet_address']);
        }
        
        if (isset($transaction_data['transaction_hash'])) {
            $transaction_data['transaction_hash'] = VORTEX_Encryption_Keys::encrypt($transaction_data['transaction_hash']);
        }
        
        // Check if the transactions table exists
        $table_name = $wpdb->prefix . 'vortex_wallet_transactions';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // Create the transactions table if it doesn't exist
            self::create_transactions_table();
        }
        
        // Insert the transaction
        $result = $wpdb->insert($table_name, $transaction_data);
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Create the wallet transactions table
     *
     * @return bool True on success, false on failure
     */
    private static function create_transactions_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_wallet_transactions';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            transaction_reference varchar(50) NOT NULL,
            transaction_type varchar(50) NOT NULL,
            amount decimal(20,10) NOT NULL,
            wallet_address text,
            transaction_hash text,
            status varchar(20) DEFAULT 'pending',
            timestamp datetime NOT NULL,
            additional_data longtext,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            UNIQUE KEY transaction_reference (transaction_reference)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        return dbDelta($sql);
    }
    
    /**
     * Get transaction details
     *
     * @param int|string $transaction_id_or_reference Transaction ID or reference
     * @param bool $decrypt Whether to decrypt sensitive data
     * @return array|false Transaction data on success, false on failure
     */
    public static function get_transaction($transaction_id_or_reference, $decrypt = true) {
        global $wpdb;
        
        if (empty($transaction_id_or_reference)) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'vortex_wallet_transactions';
        
        // Check if input is numeric (ID) or string (reference)
        if (is_numeric($transaction_id_or_reference)) {
            $query = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $transaction_id_or_reference);
        } else {
            $query = $wpdb->prepare("SELECT * FROM $table_name WHERE transaction_reference = %s", $transaction_id_or_reference);
        }
        
        $transaction = $wpdb->get_row($query, ARRAY_A);
        
        if (!$transaction) {
            return false;
        }
        
        // Decrypt sensitive data if requested
        if ($decrypt) {
            if (!empty($transaction['wallet_address'])) {
                $transaction['wallet_address'] = VORTEX_Encryption_Keys::decrypt($transaction['wallet_address']);
            }
            
            if (!empty($transaction['transaction_hash'])) {
                $transaction['transaction_hash'] = VORTEX_Encryption_Keys::decrypt($transaction['transaction_hash']);
            }
        }
        
        return $transaction;
    }
    
    /**
     * Update transaction status
     *
     * @param int|string $transaction_id_or_reference Transaction ID or reference
     * @param string $status The new status
     * @return bool True on success, false on failure
     */
    public static function update_transaction_status($transaction_id_or_reference, $status) {
        global $wpdb;
        
        if (empty($transaction_id_or_reference) || empty($status)) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'vortex_wallet_transactions';
        
        // Check if input is numeric (ID) or string (reference)
        if (is_numeric($transaction_id_or_reference)) {
            return $wpdb->update(
                $table_name,
                ['status' => $status],
                ['id' => $transaction_id_or_reference],
                ['%s'],
                ['%d']
            ) !== false;
        } else {
            return $wpdb->update(
                $table_name,
                ['status' => $status],
                ['transaction_reference' => $transaction_id_or_reference],
                ['%s'],
                ['%s']
            ) !== false;
        }
    }
} 