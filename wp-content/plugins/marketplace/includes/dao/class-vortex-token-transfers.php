<?php
/**
 * VORTEX Token Transfers Handler
 *
 * Handles tracking and management of token transfers
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_Token_Transfers {
    
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
        add_action('wp_ajax_vortex_get_transfers', array($this, 'ajax_get_transfers'));
        add_action('wp_ajax_nopriv_vortex_get_transfers', array($this, 'ajax_get_transfers'));
    }
    
    /**
     * Record a new token transfer
     *
     * @param array $transfer_data Transfer information
     * @return int|false The ID of the new transfer record, or false on failure
     */
    public function record_transfer($transfer_data) {
        global $wpdb;
        
        // Validate required data
        if (empty($transfer_data['from_address']) || 
            empty($transfer_data['to_address']) || 
            !isset($transfer_data['amount'])) {
            return false;
        }
        
        // Prepare transfer data
        $data = array(
            'from_address' => sanitize_text_field($transfer_data['from_address']),
            'to_address' => sanitize_text_field($transfer_data['to_address']),
            'amount' => floatval($transfer_data['amount']),
            'transaction_hash' => isset($transfer_data['transaction_hash']) ? 
                sanitize_text_field($transfer_data['transaction_hash']) : '',
            'block_number' => isset($transfer_data['block_number']) ? 
                intval($transfer_data['block_number']) : 0,
            'transfer_type' => isset($transfer_data['transfer_type']) ? 
                sanitize_text_field($transfer_data['transfer_type']) : 'transfer',
            'status' => 'completed',
            'created_at' => current_time('mysql'),
            'metadata' => isset($transfer_data['metadata']) ? 
                wp_json_encode($transfer_data['metadata']) : ''
        );
        
        // Insert transfer record
        $result = $wpdb->insert(
            $wpdb->prefix . 'vortex_token_transfers',
            $data,
            array(
                '%s', // from_address
                '%s', // to_address
                '%f', // amount
                '%s', // transaction_hash
                '%d', // block_number
                '%s', // transfer_type
                '%s', // status
                '%s', // created_at
                '%s'  // metadata
            )
        );
        
        if ($result === false) {
            return false;
        }
        
        do_action('vortex_token_transfer_recorded', $wpdb->insert_id, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get transfers with filtering options
     *
     * @param array $args Query arguments
     * @return array Array of transfers
     */
    public function get_transfers($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'from_address' => '',
            'to_address' => '',
            'transfer_type' => '',
            'status' => '',
            'min_amount' => '',
            'max_amount' => '',
            'from_date' => '',
            'to_date' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $values = array();
        
        // Build WHERE clause
        if (!empty($args['from_address'])) {
            $where[] = 'from_address = %s';
            $values[] = $args['from_address'];
        }
        
        if (!empty($args['to_address'])) {
            $where[] = 'to_address = %s';
            $values[] = $args['to_address'];
        }
        
        if (!empty($args['transfer_type'])) {
            $where[] = 'transfer_type = %s';
            $values[] = $args['transfer_type'];
        }
        
        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }
        
        if (!empty($args['min_amount'])) {
            $where[] = 'amount >= %f';
            $values[] = floatval($args['min_amount']);
        }
        
        if (!empty($args['max_amount'])) {
            $where[] = 'amount <= %f';
            $values[] = floatval($args['max_amount']);
        }
        
        if (!empty($args['from_date'])) {
            $where[] = 'created_at >= %s';
            $values[] = $args['from_date'];
        }
        
        if (!empty($args['to_date'])) {
            $where[] = 'created_at <= %s';
            $values[] = $args['to_date'];
        }
        
        // Build query
        $query = "SELECT * FROM {$wpdb->prefix}vortex_token_transfers
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY {$args['orderby']} {$args['order']}
                 LIMIT %d OFFSET %d";
        
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        // Execute query
        $transfers = $wpdb->get_results(
            $wpdb->prepare($query, $values),
            ARRAY_A
        );
        
        // Process transfers
        foreach ($transfers as &$transfer) {
            $transfer['amount_formatted'] = number_format($transfer['amount'], 2) . ' TOLA';
            $transfer['from_address_short'] = substr($transfer['from_address'], 0, 6) . '...' . substr($transfer['from_address'], -4);
            $transfer['to_address_short'] = substr($transfer['to_address'], 0, 6) . '...' . substr($transfer['to_address'], -4);
            $transfer['created_at_formatted'] = human_time_diff(strtotime($transfer['created_at']), current_time('timestamp')) . ' ago';
            
            if (!empty($transfer['transaction_hash'])) {
                $transfer['transaction_url'] = $this->get_transaction_explorer_url($transfer['transaction_hash']);
            }
            
            if (!empty($transfer['metadata'])) {
                $transfer['metadata'] = json_decode($transfer['metadata'], true);
            }
        }
        
        return $transfers;
    }
    
    /**
     * Get transfer statistics
     *
     * @param string $period Period to get stats for (daily, weekly, monthly)
     * @return array Statistics
     */
    public function get_transfer_statistics($period = 'daily') {
        global $wpdb;
        
        $stats = array(
            'total_volume' => 0,
            'total_transfers' => 0,
            'unique_senders' => 0,
            'unique_recipients' => 0,
            'average_amount' => 0,
            'transfer_types' => array()
        );
        
        // Get date range based on period
        switch ($period) {
            case 'weekly':
                $from_date = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;
            case 'monthly':
                $from_date = date('Y-m-d H:i:s', strtotime('-30 days'));
                break;
            default: // daily
                $from_date = date('Y-m-d H:i:s', strtotime('-24 hours'));
        }
        
        // Get basic stats
        $basic_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_transfers,
                COUNT(DISTINCT from_address) as unique_senders,
                COUNT(DISTINCT to_address) as unique_recipients,
                SUM(amount) as total_volume,
                AVG(amount) as average_amount
            FROM {$wpdb->prefix}vortex_token_transfers
            WHERE created_at >= %s",
            $from_date
        ));
        
        if ($basic_stats) {
            $stats['total_volume'] = (float) $basic_stats->total_volume;
            $stats['total_transfers'] = (int) $basic_stats->total_transfers;
            $stats['unique_senders'] = (int) $basic_stats->unique_senders;
            $stats['unique_recipients'] = (int) $basic_stats->unique_recipients;
            $stats['average_amount'] = (float) $basic_stats->average_amount;
        }
        
        // Get transfer types distribution
        $type_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT transfer_type, COUNT(*) as count, SUM(amount) as volume
            FROM {$wpdb->prefix}vortex_token_transfers
            WHERE created_at >= %s
            GROUP BY transfer_type",
            $from_date
        ));
        
        foreach ($type_stats as $type) {
            $stats['transfer_types'][$type->transfer_type] = array(
                'count' => (int) $type->count,
                'volume' => (float) $type->volume
            );
        }
        
        return $stats;
    }
    
    /**
     * AJAX handler for getting transfers
     */
    public function ajax_get_transfers() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        $args = array(
            'from_address' => isset($_POST['from_address']) ? sanitize_text_field($_POST['from_address']) : '',
            'to_address' => isset($_POST['to_address']) ? sanitize_text_field($_POST['to_address']) : '',
            'transfer_type' => isset($_POST['transfer_type']) ? sanitize_text_field($_POST['transfer_type']) : '',
            'min_amount' => isset($_POST['min_amount']) ? floatval($_POST['min_amount']) : '',
            'max_amount' => isset($_POST['max_amount']) ? floatval($_POST['max_amount']) : '',
            'limit' => isset($_POST['limit']) ? intval($_POST['limit']) : 50,
            'offset' => isset($_POST['offset']) ? intval($_POST['offset']) : 0
        );
        
        $transfers = $this->get_transfers($args);
        
        wp_send_json_success(array(
            'transfers' => $transfers,
            'count' => count($transfers)
        ));
    }
    
    /**
     * Get transaction explorer URL
     *
     * @param string $hash Transaction hash
     * @return string Explorer URL
     */
    private function get_transaction_explorer_url($hash) {
        $network = get_option('vortex_dao_blockchain_network', 'solana');
        $is_mainnet = get_option('vortex_dao_is_mainnet', false);
        
        if ($network === 'solana') {
            $base_url = $is_mainnet ? 
                'https://explorer.solana.com/tx/' : 
                'https://explorer.solana.com/tx/?cluster=devnet';
            return $base_url . $hash;
        }
        
        return '#';
    }
}

// Initialize Token Transfers class
$vortex_token_transfers = VORTEX_Token_Transfers::get_instance(); 