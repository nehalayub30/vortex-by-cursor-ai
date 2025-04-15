<?php
/**
 * VORTEX Revenue Manager
 *
 * Handles revenue distribution and treasury management
 *
 * @link       https://vortexmarketplace.io
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_Revenue_Manager {
    
    private static $instance = null;
    
    // Constants for revenue allocation
    const MARKETPLACE_COMMISSION = 15.0;
    const CREATOR_ALLOCATION = 5.0;
    const VORTEX_INC_ALLOCATION = 7.0;
    const ECOSYSTEM_ALLOCATION = 3.0;
    
    // Constants for ecosystem distribution
    const GRANTS_ALLOCATION = 1.0;
    const EXHIBITIONS_ALLOCATION = 1.0;
    const ARTIST_SUPPORT_ALLOCATION = 1.0;
    
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
        // Add hooks for processing sales
        add_action('vortex_sale_completed', array($this, 'process_revenue_distribution'), 10, 2);
        
        // Add hooks for fixed fees
        add_action('vortex_artist_swap_completed', array($this, 'process_swap_fee'), 10, 2);
        add_action('vortex_artwork_purchase_completed', array($this, 'process_artwork_purchase_fee'), 10, 1);
    }
    
    /**
     * Process revenue distribution from a sale
     */
    public function process_revenue_distribution($sale_id, $sale_data) {
        global $wpdb;
        
        // Extract sale data
        $sale_price = floatval($sale_data['total_price']);
        $marketplace_commission = floatval($sale_data['marketplace_commission']);
        
        // Calculate component allocations
        $creator_amount = ($sale_price * (self::CREATOR_ALLOCATION / 100));
        $vortex_inc_amount = ($sale_price * (self::VORTEX_INC_ALLOCATION / 100));
        $ecosystem_amount = ($sale_price * (self::ECOSYSTEM_ALLOCATION / 100));
        
        // Verify the total matches the marketplace commission
        $calculated_commission = $creator_amount + $vortex_inc_amount + $ecosystem_amount;
        
        // Handle small floating point differences
        if (abs($calculated_commission - $marketplace_commission) > 0.01) {
            // Log the discrepancy
            error_log("Commission calculation discrepancy: Expected {$marketplace_commission}, got {$calculated_commission}");
            
            // Adjust to match expected commission
            $ecosystem_amount += ($marketplace_commission - $calculated_commission);
        }
        
        // Further breakdown ecosystem allocation
        $grants_amount = ($sale_price * (self::GRANTS_ALLOCATION / 100));
        $exhibitions_amount = ($sale_price * (self::EXHIBITIONS_ALLOCATION / 100));
        $artist_support_amount = ($sale_price * (self::ARTIST_SUPPORT_ALLOCATION / 100));
        
        // Transfer to appropriate wallets/accounts
        $this->transfer_to_creator($creator_amount, $sale_id);
        $this->transfer_to_vortex_inc($vortex_inc_amount, $sale_id);
        $this->allocate_to_ecosystem($ecosystem_amount, $grants_amount, $exhibitions_amount, $artist_support_amount, $sale_id);
        
        // Log the distribution
        $this->log_revenue_distribution($sale_id, array(
            'sale_price' => $sale_price,
            'marketplace_commission' => $marketplace_commission,
            'creator_amount' => $creator_amount,
            'vortex_inc_amount' => $vortex_inc_amount,
            'ecosystem_amount' => $ecosystem_amount,
            'grants_amount' => $grants_amount,
            'exhibitions_amount' => $exhibitions_amount,
            'artist_support_amount' => $artist_support_amount
        ));
    }
    
    /**
     * Process swap fee
     */
    public function process_swap_fee($swap_id, $swap_data) {
        // Fixed $3 per artist ($6 total)
        $swap_fee_per_artist = 3.00;
        $total_fee = $swap_fee_per_artist * 2;
        
        // Transfer to Vortex Inc
        $this->transfer_to_vortex_inc($total_fee, $swap_id, 'swap_fee');
        
        // Log the fee
        $this->log_fixed_fee($swap_id, 'swap', $total_fee);
    }
    
    /**
     * Process artwork purchase fee
     */
    public function process_artwork_purchase_fee($purchase_id) {
        // Fixed $89 one-time fee
        $purchase_fee = 89.00;
        
        // Transfer to Vortex Inc
        $this->transfer_to_vortex_inc($purchase_fee, $purchase_id, 'artwork_purchase_fee');
        
        // Log the fee
        $this->log_fixed_fee($purchase_id, 'artwork_purchase', $purchase_fee);
    }
    
    /**
     * Transfer funds to creator wallet
     */
    private function transfer_to_creator($amount, $reference_id) {
        $creator_wallet = get_option('vortex_creator_wallet_address');
        
        // Use Solana API to transfer tokens
        $solana_api = VORTEX_Solana_API::get_instance();
        $treasury_address = get_option('vortex_dao_treasury_address');
        
        $result = $solana_api->transfer_tokens(
            $treasury_address,
            $creator_wallet,
            $amount,
            'creator_revenue_' . $reference_id
        );
        
        return $result;
    }
    
    /**
     * Transfer funds to Vortex Inc
     */
    private function transfer_to_vortex_inc($amount, $reference_id, $fee_type = 'commission') {
        $vortex_company_wallet = get_option('vortex_company_wallet_address');
        
        // Use Solana API to transfer tokens or log for accounting
        $solana_api = VORTEX_Solana_API::get_instance();
        $treasury_address = get_option('vortex_dao_treasury_address');
        
        $result = $solana_api->transfer_tokens(
            $treasury_address,
            $vortex_company_wallet,
            $amount,
            'vortex_inc_' . $fee_type . '_' . $reference_id
        );
        
        return $result;
    }
    
    /**
     * Allocate funds to ecosystem initiatives
     */
    private function allocate_to_ecosystem($total_amount, $grants_amount, $exhibitions_amount, $artist_support_amount, $reference_id) {
        global $wpdb;
        
        // Log allocations to DAO treasury subaccounts
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_treasury_allocations',
            array(
                'reference_id' => $reference_id,
                'reference_type' => 'sale',
                'total_amount' => $total_amount,
                'grants_amount' => $grants_amount,
                'exhibitions_amount' => $exhibitions_amount,
                'artist_support_amount' => $artist_support_amount,
                'created_at' => current_time('mysql')
            )
        );
        
        // Update treasury balances
        $this->update_treasury_balances($grants_amount, $exhibitions_amount, $artist_support_amount);
        
        return true;
    }
    
    /**
     * Update treasury balances
     */
    private function update_treasury_balances($grants_amount, $exhibitions_amount, $artist_support_amount) {
        // Update grants balance
        $current_grants_balance = get_option('vortex_dao_grants_balance', 0);
        update_option('vortex_dao_grants_balance', $current_grants_balance + $grants_amount);
        
        // Update exhibitions balance
        $current_exhibitions_balance = get_option('vortex_dao_exhibitions_balance', 0);
        update_option('vortex_dao_exhibitions_balance', $current_exhibitions_balance + $exhibitions_amount);
        
        // Update artist support balance
        $current_artist_support_balance = get_option('vortex_dao_artist_support_balance', 0);
        update_option('vortex_dao_artist_support_balance', $current_artist_support_balance + $artist_support_amount);
    }
    
    /**
     * Log revenue distribution
     */
    private function log_revenue_distribution($sale_id, $distribution_data) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_revenue_distributions',
            array(
                'reference_id' => $sale_id,
                'reference_type' => 'sale',
                'distribution_data' => json_encode($distribution_data),
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Log fixed fee
     */
    private function log_fixed_fee($reference_id, $fee_type, $amount) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_revenue_distributions',
            array(
                'reference_id' => $reference_id,
                'reference_type' => $fee_type,
                'distribution_data' => json_encode(array(
                    'fee_type' => $fee_type,
                    'amount' => $amount,
                    'recipient' => 'vortex_inc'
                )),
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Create necessary database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Revenue distributions table
        $table_distributions = $wpdb->prefix . 'vortex_revenue_distributions';
        $sql_distributions = "CREATE TABLE $table_distributions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            reference_id bigint(20) NOT NULL,
            reference_type varchar(50) NOT NULL,
            distribution_data text NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY reference_id (reference_id),
            KEY reference_type (reference_type)
        ) $charset_collate;";
        
        // DAO treasury allocations table
        $table_allocations = $wpdb->prefix . 'vortex_dao_treasury_allocations';
        $sql_allocations = "CREATE TABLE $table_allocations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            reference_id bigint(20) NOT NULL,
            reference_type varchar(50) NOT NULL,
            total_amount decimal(18,8) NOT NULL DEFAULT 0,
            grants_amount decimal(18,8) NOT NULL DEFAULT 0,
            exhibitions_amount decimal(18,8) NOT NULL DEFAULT 0,
            artist_support_amount decimal(18,8) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY reference_id (reference_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_distributions);
        dbDelta($sql_allocations);
    }
}

// Initialize Revenue Manager
$vortex_revenue_manager = VORTEX_Revenue_Manager::get_instance();

// Register activation hook for table creation
register_activation_hook(VORTEX_PLUGIN_FILE, array('VORTEX_Revenue_Manager', 'create_tables')); 