<?php
/**
 * Smart Contract Validator for TOLA and Royalty Enforcement
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Blockchain_Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Smart_Contract_Validator Class
 * 
 * Provides validation and enforcement mechanisms for the VORTEX
 * smart contract, ensuring royalty payments are correctly processed
 * and TOLA is used for all transactions.
 *
 * @since 1.0.0
 */
class VORTEX_Smart_Contract_Validator {
    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var object
     */
    protected static $instance = null;
    
    /**
     * Active AI agent instances for validation
     *
     * @since 1.0.0
     * @var array
     */
    private $ai_agents = array();
    
    /**
     * Fixed HURAII creator royalty percentage
     * 
     * @since 1.0.0
     * @var float
     */
    private $huraii_royalty_percentage = 5.0;
    
    /**
     * Maximum artist royalty percentage
     * 
     * @since 1.0.0
     * @var float
     */
    private $max_artist_royalty_percentage = 15.0;
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Initialize AI agents
        $this->initialize_ai_agents();
        
        // Set up hooks
        $this->setup_hooks();
    }
    
    /**
     * Get instance of this class.
     *
     * @since 1.0.0
     * @return VORTEX_Smart_Contract_Validator
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize AI agents for validation
     *
     * @since 1.0.0
     * @return void
     */
    private function initialize_ai_agents() {
        // Initialize HURAII for transaction verification
        $this->ai_agents['HURAII'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'contract_validation',
            'capabilities' => array(
                'transaction_verification',
                'royalty_enforcement',
                'fraud_detection'
            )
        );
        
        // Initialize BusinessStrategist for economic validation
        $this->ai_agents['BusinessStrategist'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'economic_validation',
            'capabilities' => array(
                'royalty_calculation',
                'tola_enforcement',
                'market_compliance'
            )
        );
        
        do_action('vortex_ai_agent_init', 'contract_validation', array_keys($this->ai_agents), 'active');
    }
    
    /**
     * Set up hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function setup_hooks() {
        // Validate transactions before processing
        add_filter('vortex_pre_process_transaction', array($this, 'validate_transaction'), 10, 2);
        
        // Validate artwork sales for royalty enforcement
        add_filter('vortex_pre_artwork_sale', array($this, 'validate_artwork_sale'), 10, 4);
        
        // Enforce TOLA for all marketplace transactions
        add_filter('vortex_transaction_currency', array($this, 'enforce_tola_currency'), 10, 2);
        
        // Validate royalty percentages on artwork publication
        add_filter('vortex_pre_save_royalty_meta', array($this, 'validate_royalty_percentages'), 10, 3);
        
        // Validate NFT minting
        add_filter('vortex_pre_mint_nft', array($this, 'validate_nft_metadata'), 10, 3);
    }
    
    /**
     * Validate transaction currency and enforce TOLA
     *
     * @since 1.0.0
     * @param string $currency Currency type for transaction
     * @param array $transaction_data Transaction data
     * @return string Enforced currency (always TOLA)
     */
    public function enforce_tola_currency($currency, $transaction_data) {
        // Always enforce TOLA as the currency for all marketplace transactions
        return 'tola_credit';
    }
    
    /**
     * Validate transaction before processing
     *
     * @since 1.0.0
     * @param bool $valid Whether transaction is valid
     * @param array $transaction_data Transaction data
     * @return bool|WP_Error True if valid or error
     */
    public function validate_transaction($valid, $transaction_data) {
        // Check if currency is TOLA
        if (isset($transaction_data['currency_type']) && $transaction_data['currency_type'] !== 'tola_credit') {
            return new WP_Error(
                'invalid_currency',
                __('Only TOLA tokens can be used for transactions in the VORTEX marketplace', 'vortex')
            );
        }
        
        // For token sales, validate royalty enforcement
        if (isset($transaction_data['type']) && $transaction_data['type'] === 'token_sale') {
            $token_id = isset($transaction_data['token_id']) ? $transaction_data['token_id'] : 0;
            
            if ($token_id > 0) {
                // Get token data
                global $wpdb;
                $tokens_table = $wpdb->prefix . 'vortex_tokens';
                
                $token = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$tokens_table} WHERE id = %d",
                    $token_id
                ));
                
                if ($token) {
                    $metadata = maybe_unserialize($token->metadata);
                    
                    // If token represents artwork, validate royalties
                    if (isset($metadata['artwork_id'])) {
                        $artwork_id = $metadata['artwork_id'];
                        $royalty_config = get_post_meta($artwork_id, 'vortex_royalty_config', true);
                        
                        if (empty($royalty_config)) {
                            return new WP_Error(
                                'missing_royalty_config',
                                __('Artwork is missing royalty configuration', 'vortex')
                            );
                        }
                        
                        // Validate HURAII creator royalty is correctly set at 5%
                        if (!isset($royalty_config['huraii_creator']) || 
                            $royalty_config['huraii_creator']['percentage'] != $this->huraii_royalty_percentage) {
                            
                            return new WP_Error(
                                'invalid_huraii_royalty',
                                sprintf(
                                    __('HURAII creator royalty must be exactly %s%%', 'vortex'),
                                    $this->huraii_royalty_percentage
                                )
                            );
                        }
                        
                        // Validate artist royalty is within allowed range (0-15%)
                        if (!isset($royalty_config['artist']) || 
                            $royalty_config['artist']['percentage'] < 0 || 
                            $royalty_config['artist']['percentage'] > $this->max_artist_royalty_percentage) {
                            
                            return new WP_Error(
                                'invalid_artist_royalty',
                                sprintf(
                                    __('Artist royalty must be between 0%% and %s%%', 'vortex'),
                                    $this->max_artist_royalty_percentage
                                )
                            );
                        }
                        
                        // Validate total royalty is correct
                        $expected_total = $royalty_config['huraii_creator']['percentage'] + $royalty_config['artist']['percentage'];
                        if (!isset($royalty_config['total_percentage']) || 
                            abs($royalty_config['total_percentage'] - $expected_total) > 0.01) { // Allow for small floating point differences
                            
                            return new WP_Error(
                                'invalid_total_royalty',
                                __('Total royalty percentage is incorrectly calculated', 'vortex')
                            );
                        }
                    }
                }
            }
        }
        
        return $valid;
    }
    
    /**
     * Validate artwork sale to ensure royalties are properly applied
     *
     * @since 1.0.0
     * @param bool $valid Whether sale is valid
     * @param int $artwork_id Artwork ID
     * @param float $sale_amount Sale amount
     * @param array $sale_data Sale data
     * @return bool|WP_Error True if valid or error
     */
    public function validate_artwork_sale($valid, $artwork_id, $sale_amount, $sale_data) {
        // Verify artwork exists
        $artwork = get_post($artwork_id);
        if (!$artwork || $artwork->post_type !== 'vortex-artwork') {
            return new WP_Error('invalid_artwork', __('Invalid artwork', 'vortex'));
        }
        
        // Get royalty configuration
        $royalty_config = get_post_meta($artwork_id, 'vortex_royalty_config', true);
        if (empty($royalty_config)) {
            return new WP_Error('missing_royalty_config', __('Artwork is missing royalty configuration', 'vortex'));
        }
        
        // Verify HURAII creator royalty is fixed at 5%
        if (!isset($royalty_config['huraii_creator']) || 
            $royalty_config['huraii_creator']['percentage'] != $this->huraii_royalty_percentage) {
            
            // Correct the royalty configuration
            $royalty_config['huraii_creator']['percentage'] = $this->huraii_royalty_percentage;
            $royalty_config['total_percentage'] = $this->huraii_royalty_percentage + $royalty_config['artist']['percentage'];
            update_post_meta($artwork_id, 'vortex_royalty_config', $royalty_config);
            
            // Log the correction
            error_log(sprintf(
                'VORTEX Smart Contract: Corrected HURAII creator royalty for artwork #%d to %s%%',
                $artwork_id,
                $this->huraii_royalty_percentage
            ));
        }
        
        // Check if the currency is TOLA
        if (isset($sale_data['currency_type']) && $sale_data['currency_type'] !== 'tola_credit') {
            return new WP_Error(
                'invalid_currency',
                __('Only TOLA tokens can be used for artwork sales in the VORTEX marketplace', 'vortex')
            );
        }
        
        // Get transaction mode
        $transaction_mode = isset($sale_data['transaction_mode']) ? $sale_data['transaction_mode'] : 'onchain';
        
        // For on-chain transactions, verify smart contract will enforce royalties
        if ($transaction_mode === 'onchain') {
            // In a real implementation, this would verify the smart contract interaction
            // Here we'll simulate the verification
            $verified = $this->verify_smart_contract_royalties($artwork_id, $royalty_config);
            
            if (is_wp_error($verified)) {
                return $verified;
            }
        }
        
        return $valid;
    }
    
    /**
     * Verify smart contract will enforce royalties
     *
     * @since 1.0.0
     * @param int $artwork_id Artwork ID
     * @param array $royalty_config Royalty configuration
     * @return bool|WP_Error True if verified or error
     */
    private function verify_smart_contract_royalties($artwork_id, $royalty_config) {
        // Get contract configuration
        $contract_config = get_option('vortex_smart_contract_config', array());
        
        // Check if contract address is configured
        if (empty($contract_config['nft_contract_address'])) {
            return new WP_Error('contract_not_configured', __('NFT contract not configured', 'vortex'));
        }
        
        // Check if HURAII creator address is set
        if (empty($royalty_config['huraii_creator']['address'])) {
            return new WP_Error(
                'missing_huraii_address',
                __('HURAII creator wallet address is not configured', 'vortex')
            );
        }
        
        // In a real implementation, this would call the blockchain to verify
        // the smart contract's royalty configuration for this token
        // Here we'll simulate a successful verification
        return true;
    }
    
    /**
     * Validate royalty percentages before saving
     *
     * @since 1.0.0
     * @param bool $valid Whether valid
     * @param int $post_id Post ID
     * @param float $artist_royalty Artist royalty percentage
     * @return bool|WP_Error True if valid or error
     */
    public function validate_royalty_percentages($valid, $post_id, $artist_royalty) {
        // Verify artist royalty is within allowed range
        if ($artist_royalty < 0 || $artist_royalty > $this->max_artist_royalty_percentage) {
            return new WP_Error(
                'invalid_artist_royalty',
                sprintf(
                    __('Artist royalty must be between 0%% and %s%%', 'vortex'),
                    $this->max_artist_royalty_percentage
                )
            );
        }
        
        // Calculate total royalty
        $total_royalty = $this->huraii_royalty_percentage + $artist_royalty;
        
        // Verify total royalty doesn't exceed maximum (20%)
        if ($total_royalty > ($this->huraii_royalty_percentage + $this->max_artist_royalty_percentage)) {
            return new WP_Error(
                'excessive_total_royalty',
                sprintf(
                    __('Total royalty cannot exceed %s%%', 'vortex'),
                    $this->huraii_royalty_percentage + $this->max_artist_royalty_percentage
                )
            );
        }
        
        return $valid;
    }
    
    /**
     * Validate NFT metadata before minting
     *
     * @since 1.0.0
     * @param bool $valid Whether valid
     * @param int $artwork_id Artwork ID
     * @param array $metadata NFT metadata
     * @return bool|WP_Error True if valid or error
     */
    public function validate_nft_metadata($valid, $artwork_id, $metadata) {
        // Verify artwork exists
        $artwork = get_post($artwork_id);
        if (!$artwork || $artwork->post_type !== 'vortex-artwork') {
            return new WP_Error('invalid_artwork', __('Invalid artwork', 'vortex'));
        }
        
        // Check if HURAII-generated artwork has required flag
        $is_huraii_generated = get_post_meta($artwork_id, 'vortex_huraii_generated', true);
        if ($is_huraii_generated) {
            $requires_huraii_royalty = get_post_meta($artwork_id, 'vortex_requires_huraii_royalty', true);
            if (!$requires_huraii_royalty) {
                update_post_meta($artwork_id, 'vortex_requires_huraii_royalty', true);
            }
        }
        
        // Get royalty configuration
        $royalty_config = get_post_meta($artwork_id, 'vortex_royalty_config', true);
        if (empty($royalty_config)) {
            return new WP_Error('missing_royalty_config', __('Artwork is missing royalty configuration', 'vortex'));
        }
        
        // Ensure a unique URL exists or will be generated
        $unique_url = get_post_meta($artwork_id, 'vortex_unique_url', true);
        if (empty($unique_url) && empty($metadata['generate_unique_url'])) {
            return new WP_Error(
                'missing_unique_url',
                __('Artwork must have a unique URL for royalty enforcement', 'vortex')
            );
        }
        
        return $valid;
    }
}

// Initialize the validator
add_action('plugins_loaded', function() {
    VORTEX_Smart_Contract_Validator::get_instance();
}); 