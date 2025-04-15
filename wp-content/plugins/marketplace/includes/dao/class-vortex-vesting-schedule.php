<?php
/**
 * VORTEX Vesting Schedule Handler
 *
 * Manages detailed vesting release schedules
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_Vesting_Schedule {
    
    private static $instance = null;
    private $token_vesting;
    
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
        $this->token_vesting = VORTEX_Token_Vesting::get_instance();
        
        // Hook into vesting schedule creation
        add_action('vortex_vesting_schedule_created', array($this, 'generate_release_schedule'), 10, 2);
    }
    
    /**
     * Generate release schedule for a vesting schedule
     *
     * @param int   $vesting_id The vesting schedule ID
     * @param array $vesting_data The vesting schedule data
     */
    public function generate_release_schedule($vesting_id, $vesting_data) {
        global $wpdb;
        
        // Get release interval in seconds
        $interval_seconds = $this->get_interval_seconds($vesting_data['release_interval']);
        if (!$interval_seconds) {
            return false;
        }
        
        $start_time = strtotime($vesting_data['start_timestamp']);
        $end_time = strtotime($vesting_data['end_timestamp']);
        $total_duration = $end_time - $start_time;
        
        // Handle cliff period
        $first_release = $start_time;
        if (!empty($vesting_data['cliff_end_timestamp'])) {
            $first_release = strtotime($vesting_data['cliff_end_timestamp']);
        }
        
        $releases = array();
        $current_time = $first_release;
        $total_percentage = 0;
        
        while ($current_time <= $end_time) {
            // Calculate release percentage for this interval
            $elapsed = min($current_time - $start_time, $total_duration);
            $percentage = ($elapsed / $total_duration) * 100;
            
            // Calculate release amount
            $release_percentage = $percentage - $total_percentage;
            $release_amount = ($vesting_data['total_amount'] * $release_percentage) / 100;
            
            if ($release_amount > 0) {
                $releases[] = array(
                    'vesting_id' => $vesting_id,
                    'release_date' => date('Y-m-d H:i:s', $current_time),
                    'release_amount' => $release_amount,
                    'release_percentage' => $release_percentage,
                    'created_at' => current_time('mysql')
                );
                
                $total_percentage = $percentage;
            }
            
            $current_time += $interval_seconds;
        }
        
        // Insert release schedule
        foreach ($releases as $release) {
            $wpdb->insert(
                $wpdb->prefix . 'vortex_vesting_schedule',
                $release,
                array(
                    '%d', // vesting_id
                    '%s', // release_date
                    '%f', // release_amount
                    '%f', // release_percentage
                    '%s'  // created_at
                )
            );
        }
        
        return true;
    }
    
    /**
     * Get interval seconds from interval type
     *
     * @param string $interval Interval type
     * @return int|false Seconds or false if invalid
     */
    private function get_interval_seconds($interval) {
        switch ($interval) {
            case 'daily':
                return DAY_IN_SECONDS;
            case 'weekly':
                return WEEK_IN_SECONDS;
            case 'monthly':
                return 30 * DAY_IN_SECONDS;
            case 'quarterly':
                return 90 * DAY_IN_SECONDS;
            case 'yearly':
                return YEAR_IN_SECONDS;
            default:
                return false;
        }
    }
    
    /**
     * Get upcoming releases for a vesting schedule
     *
     * @param int $vesting_id Vesting schedule ID
     * @param array $args Query arguments
     * @return array Array of upcoming releases
     */
    public function get_upcoming_releases($vesting_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'pending',
            'limit' => 10,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_vesting_schedule
            WHERE vesting_id = %d
            AND status = %s
            AND release_date > NOW()
            ORDER BY release_date ASC
            LIMIT %d OFFSET %d",
            $vesting_id,
            $args['status'],
            $args['limit'],
            $args['offset']
        );
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get past releases for a vesting schedule
     *
     * @param int $vesting_id Vesting schedule ID
     * @param array $args Query arguments
     * @return array Array of past releases
     */
    public function get_past_releases($vesting_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'completed',
            'limit' => 10,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_vesting_schedule
            WHERE vesting_id = %d
            AND status = %s
            ORDER BY release_date DESC
            LIMIT %d OFFSET %d",
            $vesting_id,
            $args['status'],
            $args['limit'],
            $args['offset']
        );
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get next release for a vesting schedule
     *
     * @param int $vesting_id Vesting schedule ID
     * @return array|null Next release or null if none
     */
    public function get_next_release($vesting_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_vesting_schedule
                WHERE vesting_id = %d
                AND status = 'pending'
                AND release_date > NOW()
                ORDER BY release_date ASC
                LIMIT 1",
                $vesting_id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Process a release
     *
     * @param int $release_id Release ID
     * @param string $transaction_hash Transaction hash
     * @param int $block_number Block number
     * @return bool Success or failure
     */
    public function process_release($release_id, $transaction_hash = '', $block_number = 0) {
        global $wpdb;
        
        $release = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_vesting_schedule
                WHERE id = %d AND status = 'pending'",
                $release_id
            ),
            ARRAY_A
        );
        
        if (!$release) {
            return false;
        }
        
        // Update release status
        $result = $wpdb->update(
            $wpdb->prefix . 'vortex_vesting_schedule',
            array(
                'status' => 'completed',
                'released_at' => current_time('mysql'),
                'transaction_hash' => $transaction_hash,
                'block_number' => $block_number
            ),
            array('id' => $release_id),
            array('%s', '%s', '%s', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            do_action('vortex_vesting_release_processed', $release_id, $release);
        }
        
        return $result !== false;
    }
    
    /**
     * Get release statistics for a vesting schedule
     *
     * @param int $vesting_id Vesting schedule ID
     * @return array Statistics
     */
    public function get_release_statistics($vesting_id) {
        global $wpdb;
        
        $stats = array(
            'total_releases' => 0,
            'completed_releases' => 0,
            'pending_releases' => 0,
            'total_released_amount' => 0,
            'total_pending_amount' => 0,
            'next_release' => null
        );
        
        // Get counts and amounts
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(release_amount) as total_amount
                FROM {$wpdb->prefix}vortex_vesting_schedule
                WHERE vesting_id = %d
                GROUP BY status",
                $vesting_id
            )
        );
        
        foreach ($results as $result) {
            $stats['total_releases'] += $result->count;
            
            if ($result->status === 'completed') {
                $stats['completed_releases'] = $result->count;
                $stats['total_released_amount'] = $result->total_amount;
            } else {
                $stats['pending_releases'] = $result->count;
                $stats['total_pending_amount'] = $result->total_amount;
            }
        }
        
        // Get next release
        $stats['next_release'] = $this->get_next_release($vesting_id);
        
        return $stats;
    }
}

// Initialize Vesting Schedule class
$vortex_vesting_schedule = VORTEX_Vesting_Schedule::get_instance(); 