<?php
/**
 * TOLA Token Database Operations
 *
 * Handles database operations related to TOLA tokens
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/database
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * TOLA Token Database Operations Class
 *
 * Manages database operations for TOLA token transactions, balances,
 * and related blockchain data.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/database
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Tola_DB {

    /**
     * Core database instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_DB $db Core database instance.
     */
    protected $db;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    Vortex_DB $db Core database instance.
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Log a token transaction in the database.
     *
     * @since    1.0.0
     * @param    array  $transaction_data Transaction data.
     * @return   mixed The transaction ID if successful, false otherwise.
     */
    public function log_transaction($transaction_data) {
        $required_fields = array(
            'tx_hash', 'from_wallet', 'to_wallet', 'amount', 'status'
        );
        
        foreach ($required_fields as $field) {
            if (!isset($transaction_data[$field])) {
                return false;
            }
        }
        
        // Set default values if not provided
        $transaction_data = wp_parse_args($transaction_data, array(
            'token_address' => get_option('vortex_tola_contract_address', ''),
            'token_name' => 'TOLA',
            'transaction_time' => current_time('mysql'),
            'token_decimals' => 18,
            'network' => get_option('vortex_blockchain_network', 'solana'),
            'related_entity_id' => 0,
            'related_entity_type' => '',
            'fee_amount' => 0,
            'notes' => ''
        ));
        
        $result = $this->db->insert('token_transactions', $transaction_data);
        
        if ($result) {
            return $this->db->last_insert_id();
        }
        
        return false;
    }

    /**
     * Update transaction status.
     *
     * @since    1.0.0
     * @param    int    $transaction_id Transaction ID.
     * @param    string $status New status.
     * @param    string $notes Optional. Additional notes.
     * @return   boolean True on success, false on failure.
     */
    public function update_transaction_status($transaction_id, $status, $notes = '') {
        $data = array(
            'status' => $status
        );
        
        if (!empty($notes)) {
            $data['notes'] = $notes;
        }
        
        $where = array(
            'id' => $transaction_id
        );
        
        return (bool) $this->db->update('token_transactions', $data, $where);
    }

    /**
     * Get transaction by hash.
     *
     * @since    1.0.0
     * @param    string $tx_hash Transaction hash.
     * @return   mixed Transaction data or false.
     */
    public function get_transaction_by_hash($tx_hash) {
        global $wpdb;
        
        $table = $this->db->get_table_name('token_transactions');
        $query = $wpdb->prepare("SELECT * FROM {$table} WHERE tx_hash = %s", $tx_hash);
        
        return $wpdb->get_row($query, ARRAY_A);
    }

    /**
     * Get transactions by wallet address.
     *
     * @since    1.0.0
     * @param    string $wallet_address Wallet address.
     * @param    string $direction Optional. 'incoming', 'outgoing', or 'all'.
     * @param    int    $limit Optional. Number of transactions to return.
     * @param    int    $offset Optional. Offset for pagination.
     * @return   array Transactions data.
     */
    public function get_wallet_transactions($wallet_address, $direction = 'all', $limit = 50, $offset = 0) {
        global $wpdb;
        
        $table = $this->db->get_table_name('token_transactions');
        $where = '';
        
        switch ($direction) {
            case 'incoming':
                $where = $wpdb->prepare("WHERE to_wallet = %s", $wallet_address);
                break;
            case 'outgoing':
                $where = $wpdb->prepare("WHERE from_wallet = %s", $wallet_address);
                break;
            case 'all':
            default:
                $where = $wpdb->prepare("WHERE from_wallet = %s OR to_wallet = %s", $wallet_address, $wallet_address);
                break;
        }
        
        $limit_clause = $wpdb->prepare("LIMIT %d OFFSET %d", $limit, $offset);
        
        $query = "SELECT * FROM {$table} {$where} ORDER BY transaction_time DESC {$limit_clause}";
        
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Count wallet transactions.
     *
     * @since    1.0.0
     * @param    string $wallet_address Wallet address.
     * @param    string $direction Optional. 'incoming', 'outgoing', or 'all'.
     * @return   int Number of transactions.
     */
    public function count_wallet_transactions($wallet_address, $direction = 'all') {
        global $wpdb;
        
        $table = $this->db->get_table_name('token_transactions');
        $where = '';
        
        switch ($direction) {
            case 'incoming':
                $where = $wpdb->prepare("WHERE to_wallet = %s", $wallet_address);
                break;
            case 'outgoing':
                $where = $wpdb->prepare("WHERE from_wallet = %s", $wallet_address);
                break;
            case 'all':
            default:
                $where = $wpdb->prepare("WHERE from_wallet = %s OR to_wallet = %s", $wallet_address, $wallet_address);
                break;
        }
        
        $query = "SELECT COUNT(*) FROM {$table} {$where}";
        
        return (int) $wpdb->get_var($query);
    }

    /**
     * Get transactions by related entity.
     *
     * @since    1.0.0
     * @param    int    $entity_id Entity ID.
     * @return   mixed Transactions data or false.
     */
    public function get_transactions_by_related_entity($entity_id) {
        global $wpdb;
        
        $table = $this->db->get_table_name('token_transactions');
        $query = $wpdb->prepare("SELECT * FROM {$table} WHERE related_entity_id = %d", $entity_id);
        
        return $wpdb->get_results($query, ARRAY_A);
    }
} 