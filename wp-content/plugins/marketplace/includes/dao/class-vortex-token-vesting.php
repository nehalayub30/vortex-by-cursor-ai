<?php
/**
 * VORTEX Token Vesting Handler
 *
 * Manages token vesting schedules and releases
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_Token_Vesting {
    
    private static $instance = null;
    private $token_transfers;
    
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
        $this->token_transfers = VORTEX_Token_Transfers::get_instance();
        
        add_action('wp_ajax_vortex_get_vesting_schedules', array($this, 'ajax_get_vesting_schedules'));
        add_action('wp_ajax_vortex_release_vested_tokens', array($this, 'ajax_release_vested_tokens'));
    }
    
    /**
     * Create a new vesting schedule
     *
     * @param array $schedule_data Vesting schedule information
     * @return int|false The ID of the new schedule, or false on failure
     */
    public function create_vesting_schedule($schedule_data) {
        global $wpdb;
        
        // Validate required data
        if (empty($schedule_data['beneficiary_address']) || 
            empty($schedule_data['allocation_type']) || 
            empty($schedule_data['total_amount']) || 
            empty($schedule_data['start_timestamp']) || 
            empty($schedule_data['end_timestamp'])) {
            return false;
        }
        
        // Prepare schedule data
        $data = array(
            'beneficiary_address' => sanitize_text_field($schedule_data['beneficiary_address']),
            'user_id' => isset($schedule_data['user_id']) ? intval($schedule_data['user_id']) : null,
            'allocation_type' => sanitize_text_field($schedule_data['allocation_type']),
            'total_amount' => floatval($schedule_data['total_amount']),
            'start_timestamp' => $schedule_data['start_timestamp'],
            'end_timestamp' => $schedule_data['end_timestamp'],
            'release_interval' => isset($schedule_data['release_interval']) ? 
                sanitize_text_field($schedule_data['release_interval']) : 'monthly',
            'release_percentage' => isset($schedule_data['release_percentage']) ? 
                floatval($schedule_data['release_percentage']) : 0,
            'revocable' => isset($schedule_data['revocable']) ? 
                (bool)$schedule_data['revocable'] : false,
            'created_at' => current_time('mysql'),
            'status' => 'active'
        );
        
        // Add cliff if specified
        if (!empty($schedule_data['cliff_end_timestamp'])) {
            $data['cliff_end_timestamp'] = $schedule_data['cliff_end_timestamp'];
        }
        
        // Add metadata if provided
        if (!empty($schedule_data['metadata'])) {
            $data['metadata'] = wp_json_encode($schedule_data['metadata']);
        }
        
        // Insert schedule
        $result = $wpdb->insert(
            $wpdb->prefix . 'vortex_token_vesting',
            $data,
            array(
                '%s', // beneficiary_address
                '%d', // user_id
                '%s', // allocation_type
                '%f', // total_amount
                '%s', // start_timestamp
                '%s', // end_timestamp
                '%s', // release_interval
                '%f', // release_percentage
                '%d', // revocable
                '%s', // created_at
                '%s'  // status
            )
        );
        
        if ($result === false) {
            return false;
        }
        
        do_action('vortex_vesting_schedule_created', $wpdb->insert_id, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Calculate releasable amount for a vesting schedule
     *
     * @param array $schedule Vesting schedule data
     * @return float Releasable amount
     */
    public function calculate_releasable_amount($schedule) {
        if ($schedule['status'] !== 'active' || 
            strtotime($schedule['start_timestamp']) > current_time('timestamp')) {
            return 0;
        }
        
        // Check if cliff period is still active
        if (!empty($schedule['cliff_end_timestamp']) && 
            strtotime($schedule['cliff_end_timestamp']) > current_time('timestamp')) {
            return 0;
        }
        
        $total_duration = strtotime($schedule['end_timestamp']) - strtotime($schedule['start_timestamp']);
        $elapsed_time = min(
            current_time('timestamp') - strtotime($schedule['start_timestamp']),
            $total_duration
        );
        
        // Calculate vested amount based on elapsed time
        $vested_percentage = ($elapsed_time / $total_duration) * 100;
        $vested_amount = ($schedule['total_amount'] * $vested_percentage) / 100;
        
        // Subtract already released amount
        $releasable = $vested_amount - $schedule['released_amount'];
        
        return max(0, $releasable);
    }
    
    /**
     * Release vested tokens
     *
     * @param int $schedule_id Schedule ID
     * @return bool|array False on failure, or array with release details
     */
    public function release_vested_tokens($schedule_id) {
        global $wpdb;
        
        $schedule = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_token_vesting WHERE id = %d",
                $schedule_id
            ),
            ARRAY_A
        );
        
        if (!$schedule || $schedule['status'] !== 'active') {
            return false;
        }
        
        $releasable_amount = $this->calculate_releasable_amount($schedule);
        
        if ($releasable_amount <= 0) {
            return false;
        }
        
        // Record token transfer
        $transfer_data = array(
            'from_address' => get_option('vortex_dao_treasury_address'),
            'to_address' => $schedule['beneficiary_address'],
            'amount' => $releasable_amount,
            'transfer_type' => 'vesting_release',
            'metadata' => array(
                'vesting_schedule_id' => $schedule_id,
                'allocation_type' => $schedule['allocation_type']
            )
        );
        
        $transfer_id = $this->token_transfers->record_transfer($transfer_data);
        
        if (!$transfer_id) {
            return false;
        }
        
        // Update released amount
        $result = $wpdb->update(
            $wpdb->prefix . 'vortex_token_vesting',
            array(
                'released_amount' => $schedule['released_amount'] + $releasable_amount,
                'last_release_at' => current_time('mysql')
            ),
            array('id' => $schedule_id),
            array('%f', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return false;
        }
        
        do_action('vortex_vesting_tokens_released', $schedule_id, $releasable_amount);
        
        return array(
            'amount' => $releasable_amount,
            'transfer_id' => $transfer_id
        );
    }
    
    /**
     * Revoke vesting schedule
     *
     * @param int $schedule_id Schedule ID
     * @param int $revoked_by User ID who revoked the schedule
     * @return bool Success or failure
     */
    public function revoke_vesting_schedule($schedule_id, $revoked_by) {
        global $wpdb;
        
        $schedule = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_token_vesting WHERE id = %d",
                $schedule_id
            ),
            ARRAY_A
        );
        
        if (!$schedule || !$schedule['revocable'] || $schedule['status'] !== 'active') {
            return false;
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'vortex_token_vesting',
            array(
                'status' => 'revoked',
                'revoked_at' => current_time('mysql'),
                'revoked_by' => $revoked_by
            ),
            array('id' => $schedule_id),
            array('%s', '%s', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            do_action('vortex_vesting_schedule_revoked', $schedule_id, $revoked_by);
        }
        
        return $result !== false;
    }
    
    /**
     * Get vesting schedules with filtering options
     *
     * @param array $args Query arguments
     * @return array Array of vesting schedules
     */
    public function get_vesting_schedules($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'beneficiary_address' => '',
            'user_id' => 0,
            'allocation_type' => '',
            'status' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $values = array();
        
        if (!empty($args['beneficiary_address'])) {
            $where[] = 'beneficiary_address = %s';
            $values[] = $args['beneficiary_address'];
        }
        
        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = $args['user_id'];
        }
        
        if (!empty($args['allocation_type'])) {
            $where[] = 'allocation_type = %s';
            $values[] = $args['allocation_type'];
        }
        
        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }
        
        $query = "SELECT * FROM {$wpdb->prefix}vortex_token_vesting
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY {$args['orderby']} {$args['order']}
                 LIMIT %d OFFSET %d";
        
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        $schedules = $wpdb->get_results(
            $wpdb->prepare($query, $values),
            ARRAY_A
        );
        
        foreach ($schedules as &$schedule) {
            $schedule['releasable_amount'] = $this->calculate_releasable_amount($schedule);
            $schedule['vesting_progress'] = min(100, 
                ($schedule['released_amount'] / $schedule['total_amount']) * 100
            );
            
            if (!empty($schedule['metadata'])) {
                $schedule['metadata'] = json_decode($schedule['metadata'], true);
            }
        }
        
        return $schedules;
    }
    
    /**
     * AJAX handler for getting vesting schedules
     */
    public function ajax_get_vesting_schedules() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        $args = array(
            'beneficiary_address' => isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '',
            'allocation_type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '',
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '',
            'limit' => isset($_POST['limit']) ? intval($_POST['limit']) : 50,
            'offset' => isset($_POST['offset']) ? intval($_POST['offset']) : 0
        );
        
        $schedules = $this->get_vesting_schedules($args);
        
        wp_send_json_success(array(
            'schedules' => $schedules,
            'count' => count($schedules)
        ));
    }
    
    /**
     * AJAX handler for releasing vested tokens
     */
    public function ajax_release_vested_tokens() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        if (!current_user_can('manage_vortex_dao')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
        
        if (!$schedule_id) {
            wp_send_json_error('Invalid schedule ID');
            return;
        }
        
        $result = $this->release_vested_tokens($schedule_id);
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error('Failed to release tokens');
        }
    }
}

// Initialize Token Vesting class
$vortex_token_vesting = VORTEX_Token_Vesting::get_instance(); 