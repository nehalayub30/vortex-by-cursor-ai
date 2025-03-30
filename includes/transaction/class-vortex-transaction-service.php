<?php
namespace Vortex;

/**
 * Handles transactions including fees and blockchain integrations.
 *
 * @since      1.0.0
 */
class Vortex_Transaction_Service {
    
    // Transaction fee constants
    const SWAP_FEE = VORTEX_DEFAULT_SWAP_FEE;  // $3 for swapping between artists
    const TRANSACTION_FEE = VORTEX_DEFAULT_TRANSACTION_FEE;  // $80 for buy/sell transactions
    
    /**
     * Initialize the class.
     */
    public function __construct() {
        // Constructor code
        add_action('wp_ajax_vortex_process_transaction', array($this, 'ajax_process_transaction'));
    }
    
    /**
     * Process a transaction via AJAX.
     */
    public function ajax_process_transaction() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex-nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        $transaction_type = sanitize_text_field($_POST['transaction_type']);
        $sender_id = intval($_POST['sender_id']);
        $recipient_id = intval($_POST['recipient_id']);
        $amount = floatval($_POST['amount']);
        $item_id = intval($_POST['item_id']);
        $fee_arrangement = sanitize_text_field($_POST['fee_arrangement']);
        
        $result = $this->process_transaction($transaction_type, $sender_id, $recipient_id, $amount, $item_id, $fee_arrangement);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Process a transaction.
     */
    public function process_transaction($transaction_type, $sender_id, $recipient_id, $amount, $item_id, $fee_arrangement = 'split') {
        // Enforce TOLA-only transactions
        $currency = isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : 'TOLA';
        if ($currency !== 'TOLA') {
            return array(
                'success' => false,
                'message' => __('Only TOLA tokens are accepted for transactions', 'vortex-ai-marketplace')
            );
        }
        
        // Calculate fee based on transaction type
        $fee = $this->calculate_transaction_fee($transaction_type);
        
        // Determine who pays the fee
        $sender_fee = 0;
        $recipient_fee = 0;
        
        switch ($fee_arrangement) {
            case 'sender_pays':
                $sender_fee = $fee;
                break;
            case 'recipient_pays':
                $recipient_fee = $fee;
                break;
            case 'split':
            default:
                $sender_fee = $fee / 2;
                $recipient_fee = $fee / 2;
                break;
        }
        
        // Check if sender has enough TOLA
        $user_manager = new Vortex_User_Manager();
        $sender_balance = $user_manager->get_user_tola_balance($sender_id);
        
        if ($sender_balance < ($amount + $sender_fee)) {
            return array(
                'success' => false,
                'message' => __('Insufficient TOLA balance', 'vortex-ai-marketplace')
            );
        }
        
        // Process the transaction
        // Here we would integrate with the blockchain
        
        // Record transaction in database
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_transactions';
        
        $wpdb->insert(
            $table,
            array(
                'sender_id' => $sender_id,
                'recipient_id' => $recipient_id,
                'amount' => $amount,
                'fee' => $fee,
                'sender_fee' => $sender_fee,
                'recipient_fee' => $recipient_fee,
                'transaction_type' => $transaction_type,
                'item_id' => $item_id,
                'currency' => 'TOLA', // Explicitly set currency to TOLA
                'status' => 'completed',
                'created_at' => current_time('mysql')
            )
        );
        
        // Log transaction for security audit
        $this->log_transaction($wpdb->insert_id, $sender_id, $recipient_id, $amount, $transaction_type);
        
        // Return successful result
        return array(
            'success' => true,
            'transaction_id' => $wpdb->insert_id,
            'amount' => $amount,
            'fee' => $fee,
            'sender_fee' => $sender_fee,
            'recipient_fee' => $recipient_fee,
            'currency' => 'TOLA'
        );
    }
    
    /**
     * Calculate transaction fee based on transaction type.
     */
    public function calculate_transaction_fee($transaction_type) {
        switch ($transaction_type) {
            case 'artist_swap':
                return self::SWAP_FEE;
            case 'nft_purchase':
                return self::TRANSACTION_FEE;
            default:
                return 0;
        }
    }
    
    /**
     * Log transaction for security audit
     */
    private function log_transaction($transaction_id, $sender_id, $recipient_id, $amount, $transaction_type) {
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
        
        error_log(sprintf(
            'TOLA Transaction: ID=%d, Sender=%d, Recipient=%d, Amount=%f, Type=%s, IP=%s',
            $transaction_id,
            $sender_id,
            $recipient_id,
            $amount,
            $transaction_type,
            $user_ip
        ));
    }
} 