<?php
/**
 * VORTEX DAO Grants Handler
 *
 * Handles grant operations and tracking for the DAO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_DAO_Grants {
    
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
        add_action('wp_ajax_vortex_get_grants', array($this, 'ajax_get_grants'));
        add_action('wp_ajax_nopriv_vortex_get_grants', array($this, 'ajax_get_grants'));
    }
    
    /**
     * Create a new grant record
     *
     * @param array $grant_data Grant information
     * @return int|false The ID of the new grant, or false on failure
     */
    public function create_grant($grant_data) {
        global $wpdb;
        
        // Validate required data
        if (empty($grant_data['proposal_id']) || 
            empty($grant_data['recipient']) || 
            empty($grant_data['amount']) || 
            empty($grant_data['purpose'])) {
            return false;
        }
        
        // Prepare grant data
        $data = array(
            'proposal_id' => intval($grant_data['proposal_id']),
            'recipient' => sanitize_text_field($grant_data['recipient']),
            'amount' => floatval($grant_data['amount']),
            'purpose' => sanitize_textarea_field($grant_data['purpose']),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );
        
        // Insert grant record
        $result = $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_grants',
            $data,
            array(
                '%d', // proposal_id
                '%s', // recipient
                '%f', // amount
                '%s', // purpose
                '%s', // status
                '%s'  // created_at
            )
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update grant status and transaction details
     *
     * @param int    $grant_id Grant ID
     * @param string $status New status
     * @param string $transaction_signature Transaction signature
     * @return bool Success or failure
     */
    public function update_grant_status($grant_id, $status, $transaction_signature = '') {
        global $wpdb;
        
        $data = array(
            'status' => sanitize_text_field($status)
        );
        
        $format = array('%s');
        
        if (!empty($transaction_signature)) {
            $data['transaction_signature'] = sanitize_text_field($transaction_signature);
            $format[] = '%s';
        }
        
        if ($status === 'completed') {
            $data['executed_at'] = current_time('mysql');
            $format[] = '%s';
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'vortex_dao_grants',
            $data,
            array('id' => $grant_id),
            $format,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get grants with filtering options
     *
     * @param array $args Query arguments
     * @return array Array of grants
     */
    public function get_grants($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'proposal_id' => 0,
            'recipient' => '',
            'status' => '',
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
        if (!empty($args['proposal_id'])) {
            $where[] = 'proposal_id = %d';
            $values[] = $args['proposal_id'];
        }
        
        if (!empty($args['recipient'])) {
            $where[] = 'recipient = %s';
            $values[] = $args['recipient'];
        }
        
        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
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
        $query = "SELECT g.*, p.title as proposal_title 
                 FROM {$wpdb->prefix}vortex_dao_grants g
                 LEFT JOIN {$wpdb->prefix}vortex_dao_proposals p ON g.proposal_id = p.id
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY {$args['orderby']} {$args['order']}
                 LIMIT %d OFFSET %d";
        
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        // Execute query
        $grants = $wpdb->get_results(
            $wpdb->prepare($query, $values),
            ARRAY_A
        );
        
        // Process grants
        foreach ($grants as &$grant) {
            $grant['amount_formatted'] = number_format($grant['amount'], 2) . ' TOLA';
            $grant['recipient_short'] = substr($grant['recipient'], 0, 6) . '...' . substr($grant['recipient'], -4);
            $grant['created_at_formatted'] = human_time_diff(strtotime($grant['created_at']), current_time('timestamp')) . ' ago';
            
            if (!empty($grant['executed_at'])) {
                $grant['executed_at_formatted'] = human_time_diff(strtotime($grant['executed_at']), current_time('timestamp')) . ' ago';
            }
            
            // Add transaction explorer link if available
            if (!empty($grant['transaction_signature'])) {
                $grant['transaction_url'] = $this->get_transaction_explorer_url($grant['transaction_signature']);
            }
        }
        
        return $grants;
    }
    
    /**
     * AJAX handler for getting grants
     */
    public function ajax_get_grants() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        $args = array(
            'proposal_id' => isset($_POST['proposal_id']) ? intval($_POST['proposal_id']) : 0,
            'recipient' => isset($_POST['recipient']) ? sanitize_text_field($_POST['recipient']) : '',
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '',
            'limit' => isset($_POST['limit']) ? intval($_POST['limit']) : 50,
            'offset' => isset($_POST['offset']) ? intval($_POST['offset']) : 0
        );
        
        $grants = $this->get_grants($args);
        
        wp_send_json_success(array(
            'grants' => $grants,
            'count' => count($grants)
        ));
    }
    
    /**
     * Get transaction explorer URL
     *
     * @param string $signature Transaction signature
     * @return string Explorer URL
     */
    private function get_transaction_explorer_url($signature) {
        $network = get_option('vortex_dao_blockchain_network', 'solana');
        $is_mainnet = get_option('vortex_dao_is_mainnet', false);
        
        if ($network === 'solana') {
            $base_url = $is_mainnet ? 
                'https://explorer.solana.com/tx/' : 
                'https://explorer.solana.com/tx/?cluster=devnet';
            return $base_url . $signature;
        }
        
        return '#';
    }
    
    /**
     * Get total granted amount
     *
     * @param string $status Optional status filter
     * @return float Total amount
     */
    public function get_total_granted_amount($status = 'completed') {
        global $wpdb;
        
        $where = '';
        if ($status) {
            $where = $wpdb->prepare("WHERE status = %s", $status);
        }
        
        return (float) $wpdb->get_var(
            "SELECT SUM(amount) FROM {$wpdb->prefix}vortex_dao_grants $where"
        );
    }
    
    /**
     * Get grant statistics
     *
     * @return array Statistics
     */
    public function get_grant_statistics() {
        global $wpdb;
        
        $stats = array(
            'total_granted' => $this->get_total_granted_amount('completed'),
            'total_pending' => $this->get_total_granted_amount('pending'),
            'grant_count' => array(
                'total' => 0,
                'completed' => 0,
                'pending' => 0,
                'failed' => 0
            ),
            'recent_grants' => array()
        );
        
        // Get counts by status
        $counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count 
            FROM {$wpdb->prefix}vortex_dao_grants 
            GROUP BY status"
        );
        
        foreach ($counts as $count) {
            $stats['grant_count'][$count->status] = (int) $count->count;
            $stats['grant_count']['total'] += (int) $count->count;
        }
        
        // Get recent grants
        $stats['recent_grants'] = $this->get_grants(array(
            'limit' => 5,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ));
        
        return $stats;
    }
}

// Initialize Grants class
$vortex_dao_grants = VORTEX_DAO_Grants::get_instance(); 