<?php
/**
 * VORTEX Marketplace Revenue Bridge
 *
 * Handles revenue sharing between marketplace and DAO
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_Marketplace_Revenue_Bridge {
    
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
        // Hook into marketplace sales to capture revenue
        add_action('vortex_artwork_sold', array($this, 'process_sale_revenue'), 10, 3);
        
        // Hook into marketplace subscription payments
        add_action('vortex_subscription_payment_completed', array($this, 'process_subscription_revenue'), 10, 2);
        
        // Hook into marketplace fee collection
        add_action('vortex_marketplace_fee_collected', array($this, 'process_marketplace_fee'), 10, 2);
        
        // Schedule daily revenue distribution
        if (!wp_next_scheduled('vortex_daily_revenue_distribution')) {
            wp_schedule_event(strtotime('midnight'), 'daily', 'vortex_daily_revenue_distribution');
        }
        add_action('vortex_daily_revenue_distribution', array($this, 'distribute_daily_revenue'));
    }
    
    /**
     * Process marketplace sale revenue
     *
     * @param int    $artwork_id The artwork ID
     * @param float  $sale_amount The sale amount
     * @param array  $sale_data Additional sale data
     */
    public function process_sale_revenue($artwork_id, $sale_amount, $sale_data) {
        global $wpdb;
        
        // Get revenue sharing configuration
        $dao_share_percentage = $this->get_dao_share_percentage('artwork_sale');
        
        // Calculate DAO share
        $dao_share = $sale_amount * ($dao_share_percentage / 100);
        
        // Record revenue share
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_revenue',
            array(
                'revenue_type' => 'artwork_sale',
                'source_id' => $artwork_id,
                'total_amount' => $sale_amount,
                'dao_share_amount' => $dao_share,
                'dao_share_percentage' => $dao_share_percentage,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'metadata' => wp_json_encode($sale_data)
            ),
            array('%s', '%d', '%f', '%f', '%f', '%s', '%s', '%s')
        );
    }
    
    /**
     * Process subscription revenue
     *
     * @param int    $subscription_id The subscription ID
     * @param array  $payment_data Payment data
     */
    public function process_subscription_revenue($subscription_id, $payment_data) {
        global $wpdb;
        
        // Get revenue sharing configuration
        $dao_share_percentage = $this->get_dao_share_percentage('subscription');
        
        // Calculate DAO share
        $dao_share = $payment_data['amount'] * ($dao_share_percentage / 100);
        
        // Record revenue share
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_revenue',
            array(
                'revenue_type' => 'subscription',
                'source_id' => $subscription_id,
                'total_amount' => $payment_data['amount'],
                'dao_share_amount' => $dao_share,
                'dao_share_percentage' => $dao_share_percentage,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'metadata' => wp_json_encode($payment_data)
            ),
            array('%s', '%d', '%f', '%f', '%f', '%s', '%s', '%s')
        );
    }
    
    /**
     * Process marketplace fee
     *
     * @param float  $fee_amount The fee amount
     * @param array  $fee_data Fee data
     */
    public function process_marketplace_fee($fee_amount, $fee_data) {
        global $wpdb;
        
        // Get revenue sharing configuration
        $dao_share_percentage = $this->get_dao_share_percentage('marketplace_fee');
        
        // Calculate DAO share
        $dao_share = $fee_amount * ($dao_share_percentage / 100);
        
        // Record revenue share
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_revenue',
            array(
                'revenue_type' => 'marketplace_fee',
                'source_id' => $fee_data['transaction_id'],
                'total_amount' => $fee_amount,
                'dao_share_amount' => $dao_share,
                'dao_share_percentage' => $dao_share_percentage,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'metadata' => wp_json_encode($fee_data)
            ),
            array('%s', '%d', '%f', '%f', '%f', '%s', '%s', '%s')
        );
    }
    
    /**
     * Distribute daily revenue to DAO treasury
     */
    public function distribute_daily_revenue() {
        global $wpdb;
        
        // Get treasury address
        $treasury_address = get_option('vortex_dao_treasury_address');
        if (empty($treasury_address)) {
            error_log('DAO treasury address not set. Revenue distribution skipped.');
            return;
        }
        
        // Start a database transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Get pending revenue
            $pending_revenue = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}vortex_dao_revenue
                WHERE status = 'pending'",
                ARRAY_A
            );
            
            if (empty($pending_revenue)) {
                $wpdb->query('COMMIT');
                return;
            }
            
            // Calculate total
            $total_dao_share = 0;
            $revenue_ids = array();
            
            foreach ($pending_revenue as $revenue) {
                $total_dao_share += $revenue['dao_share_amount'];
                $revenue_ids[] = $revenue['id'];
            }
            
            // Only proceed if there's revenue to distribute
            if ($total_dao_share <= 0) {
                $wpdb->query('COMMIT');
                return;
            }
            
            // Prepare revenue distribution transaction
            $distribution_data = array(
                'from_address' => get_option('vortex_marketplace_fee_address'),
                'to_address' => $treasury_address,
                'amount' => $total_dao_share,
                'transfer_type' => 'revenue_distribution',
                'metadata' => array(
                    'revenue_count' => count($pending_revenue),
                    'revenue_ids' => $revenue_ids,
                    'distribution_date' => current_time('mysql')
                )
            );
            
            // Record token transfer
            $token_transfers = VORTEX_Token_Transfers::get_instance();
            $transfer_id = $token_transfers->record_transfer($distribution_data);
            
            if (!$transfer_id) {
                throw new Exception('Failed to record token transfer');
            }
            
            // Update revenue records
            foreach ($revenue_ids as $revenue_id) {
                $wpdb->update(
                    $wpdb->prefix . 'vortex_dao_revenue',
                    array(
                        'status' => 'distributed',
                        'distributed_at' => current_time('mysql'),
                        'transfer_id' => $transfer_id
                    ),
                    array('id' => $revenue_id),
                    array('%s', '%s', '%d'),
                    array('%d')
                );
            }
            
            // Record distribution in governance logs
            $governance_logs = new VORTEX_DAO_Governance();
            $governance_logs->log_governance_action('revenue_distribution', array(
                'transfer_id' => $transfer_id,
                'amount' => $total_dao_share,
                'revenue_count' => count($pending_revenue),
                'status' => 'completed'
            ));
            
            // Update DAO metrics
            $dao_metrics = VORTEX_DAO_Metrics::get_instance();
            $dao_metrics->record_metrics('revenue');
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            // Notify DAO members of distribution
            $this->notify_distribution_complete($total_dao_share, count($pending_revenue));
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Revenue distribution failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get DAO revenue share percentage
     *
     * @param string $revenue_type Type of revenue
     * @return float Percentage of revenue that goes to DAO
     */
    private function get_dao_share_percentage($revenue_type) {
        $default_percentages = array(
            'artwork_sale' => 5.0,
            'subscription' => 10.0,
            'marketplace_fee' => 50.0
        );
        
        $option_name = 'vortex_dao_revenue_share_' . $revenue_type;
        return floatval(get_option($option_name, $default_percentages[$revenue_type]));
    }
    
    /**
     * Notify DAO members of completed distribution
     *
     * @param float $total_amount Total distributed amount
     * @param int   $revenue_count Number of revenue items
     */
    private function notify_distribution_complete($total_amount, $revenue_count) {
        // Create governance insight
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_agent_insights',
            array(
                'agent_name' => 'Business Strategist',
                'insight_type' => 'notification',
                'insight_category' => 'revenue',
                'title' => 'Daily Revenue Distribution Completed',
                'content' => "A total of {$total_amount} TOLA has been distributed to the DAO treasury from {$revenue_count} marketplace transactions.",
                'confidence' => 1.0,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%f', '%s')
        );
    }
    
    /**
     * Create revenue distribution table
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'vortex_dao_revenue';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            revenue_type varchar(50) NOT NULL,
            source_id bigint(20) NOT NULL,
            total_amount decimal(18,9) NOT NULL,
            dao_share_amount decimal(18,9) NOT NULL,
            dao_share_percentage decimal(5,2) NOT NULL,
            status varchar(20) NOT NULL,
            created_at datetime NOT NULL,
            distributed_at datetime DEFAULT NULL,
            transfer_id bigint(20) DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY revenue_type (revenue_type),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize revenue bridge
$vortex_marketplace_revenue_bridge = VORTEX_Marketplace_Revenue_Bridge::get_instance(); 