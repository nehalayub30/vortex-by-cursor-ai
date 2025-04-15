<?php
/**
 * VORTEX DAO Core
 *
 * Handles core DAO functionality and configuration
 *
 * @link       https://vortexmarketplace.io
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_DAO_Core {
    
    private static $instance = null;
    
    // TOLA token configuration
    private $token_address = 'H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky'; // Solana address
    private $blockchain_network = 'solana';
    private $token_name = 'TOLA';
    private $token_symbol = 'TOLA';
    
    // Royalty configuration
    private $artist_royalty_max = 15; // Maximum artist royalty percentage
    private $vortex_creator_royalty = 5; // Fixed creator royalty percentage
    private $royalty_cap = 20; // Maximum total royalty percentage
    
    // Revenue configuration
    private $marketplace_commission = 15; // Marketplace commission percentage
    private $dao_allocation_percentage = 3; // Percentage of marketplace commission allocated to DAO
    private $admin_allocation_percentage = 7; // Percentage of marketplace commission allocated to VORTEX Inc.
    private $creator_allocation_percentage = 5; // Percentage of marketplace commission allocated to creator
    
    // DAO Treasury allocation
    private $grants_allocation = 1; // Percentage for artist grants
    private $exhibitions_allocation = 1; // Percentage for community art exhibitions
    private $artist_support_allocation = 1; // Percentage for artist supply and residency support
    
    // Admin addresses
    private $vortex_creator_wallet = ''; // Creator wallet address (Marianne Nems)
    private $vortex_company_wallet = ''; // Company wallet address (VORTEX Inc.)
    private $dao_treasury_wallet = ''; // DAO treasury wallet address
    
    // Swap fees
    private $swap_fee_per_artist = 3; // Fee per artist for swaps in USD

    // Artwork purchase fees
    private $artwork_purchase_fee = 89; // One-time fee in USD
    
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
        // Load configuration from database
        $this->load_configuration();
        
        // Register hooks for AJAX endpoints
        add_action('wp_ajax_vortex_get_dao_configuration', array($this, 'ajax_get_dao_configuration'));
        add_action('wp_ajax_nopriv_vortex_get_dao_configuration', array($this, 'ajax_get_dao_configuration'));
        
        // Admin only AJAX endpoints
        add_action('wp_ajax_vortex_update_dao_configuration', array($this, 'ajax_update_dao_configuration'));
        
        // Register shortcodes
        add_shortcode('vortex_dao_info', array($this, 'dao_info_shortcode'));
    }
    
    /**
     * Load configuration from database
     */
    private function load_configuration() {
        // TOLA token configuration
        $this->token_address = get_option('vortex_dao_token_address', $this->token_address);
        $this->blockchain_network = get_option('vortex_dao_blockchain_network', $this->blockchain_network);
        $this->token_name = get_option('vortex_dao_token_name', $this->token_name);
        $this->token_symbol = get_option('vortex_dao_token_symbol', $this->token_symbol);
        
        // Royalty configuration
        $this->artist_royalty_max = get_option('vortex_dao_artist_royalty_max', $this->artist_royalty_max);
        $this->vortex_creator_royalty = get_option('vortex_dao_vortex_creator_royalty', $this->vortex_creator_royalty);
        $this->royalty_cap = get_option('vortex_dao_royalty_cap', $this->royalty_cap);
        
        // Revenue configuration
        $this->marketplace_commission = get_option('vortex_dao_marketplace_commission', $this->marketplace_commission);
        $this->dao_allocation_percentage = get_option('vortex_dao_dao_allocation', $this->dao_allocation_percentage);
        $this->admin_allocation_percentage = get_option('vortex_dao_admin_allocation', $this->admin_allocation_percentage);
        $this->creator_allocation_percentage = get_option('vortex_dao_creator_allocation', $this->creator_allocation_percentage);
        
        // DAO Treasury allocation
        $this->grants_allocation = get_option('vortex_dao_grants_allocation', $this->grants_allocation);
        $this->exhibitions_allocation = get_option('vortex_dao_exhibitions_allocation', $this->exhibitions_allocation);
        $this->artist_support_allocation = get_option('vortex_dao_artist_support_allocation', $this->artist_support_allocation);
        
        // Admin addresses
        $this->vortex_creator_wallet = get_option('vortex_dao_creator_wallet', $this->vortex_creator_wallet);
        $this->vortex_company_wallet = get_option('vortex_dao_company_wallet', $this->vortex_company_wallet);
        $this->dao_treasury_wallet = get_option('vortex_dao_treasury_wallet', $this->dao_treasury_wallet);
        
        // Swap fees
        $this->swap_fee_per_artist = get_option('vortex_dao_swap_fee_per_artist', $this->swap_fee_per_artist);
        
        // Artwork purchase fees
        $this->artwork_purchase_fee = get_option('vortex_dao_artwork_purchase_fee', $this->artwork_purchase_fee);
    }
    
    /**
     * Get basic DAO configuration
     */
    public function get_configuration() {
        return array(
            'token' => array(
                'address' => $this->token_address,
                'blockchain' => $this->blockchain_network,
                'name' => $this->token_name,
                'symbol' => $this->token_symbol
            ),
            'royalties' => array(
                'artist_max' => $this->artist_royalty_max,
                'vortex_creator' => $this->vortex_creator_royalty,
                'cap' => $this->royalty_cap
            ),
            'revenue' => array(
                'marketplace_commission' => $this->marketplace_commission,
                'dao_allocation' => $this->dao_allocation_percentage,
                'admin_allocation' => $this->admin_allocation_percentage,
                'creator_allocation' => $this->creator_allocation_percentage
            ),
            'treasury' => array(
                'grants' => $this->grants_allocation,
                'exhibitions' => $this->exhibitions_allocation,
                'artist_support' => $this->artist_support_allocation
            ),
            'fees' => array(
                'swap_fee_per_artist' => $this->swap_fee_per_artist,
                'artwork_purchase_fee' => $this->artwork_purchase_fee
            ),
            'wallets' => array(
                'creator' => $this->mask_wallet_address($this->vortex_creator_wallet),
                'company' => $this->mask_wallet_address($this->vortex_company_wallet),
                'treasury' => $this->mask_wallet_address($this->dao_treasury_wallet)
            )
        );
    }
    
    /**
     * Get all DAO configuration (admin only)
     */
    public function get_admin_configuration() {
        // Only accessible to admins
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        return array(
            'token' => array(
                'address' => $this->token_address,
                'blockchain' => $this->blockchain_network,
                'name' => $this->token_name,
                'symbol' => $this->token_symbol
            ),
            'royalties' => array(
                'artist_max' => $this->artist_royalty_max,
                'vortex_creator' => $this->vortex_creator_royalty,
                'cap' => $this->royalty_cap
            ),
            'revenue' => array(
                'marketplace_commission' => $this->marketplace_commission,
                'dao_allocation' => $this->dao_allocation_percentage,
                'admin_allocation' => $this->admin_allocation_percentage,
                'creator_allocation' => $this->creator_allocation_percentage
            ),
            'treasury' => array(
                'grants' => $this->grants_allocation,
                'exhibitions' => $this->exhibitions_allocation,
                'artist_support' => $this->artist_support_allocation
            ),
            'fees' => array(
                'swap_fee_per_artist' => $this->swap_fee_per_artist,
                'artwork_purchase_fee' => $this->artwork_purchase_fee
            ),
            'wallets' => array(
                'creator' => $this->vortex_creator_wallet,
                'company' => $this->vortex_company_wallet,
                'treasury' => $this->dao_treasury_wallet
            )
        );
    }
    
    /**
     * Mask wallet address for public display
     */
    private function mask_wallet_address($address) {
        if (empty($address)) {
            return '';
        }
        
        return substr($address, 0, 6) . '...' . substr($address, -4);
    }
    
    /**
     * AJAX: Get DAO configuration
     */
    public function ajax_get_dao_configuration() {
        // Verify nonce
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        // Check if requesting admin configuration
        $admin_config = isset($_POST['admin']) && $_POST['admin'] === 'true';
        
        if ($admin_config) {
            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'You do not have permission to access this data.'));
                return;
            }
            
            $config = $this->get_admin_configuration();
        } else {
            $config = $this->get_configuration();
        }
        
        wp_send_json_success(array('configuration' => $config));
    }
    
    /**
     * AJAX: Update DAO configuration (admin only)
     */
    public function ajax_update_dao_configuration() {
        // Verify nonce
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to update configuration.'));
            return;
        }
        
        // Validate and update token configuration
        if (isset($_POST['token'])) {
            $token = json_decode(stripslashes($_POST['token']), true);
            
            if (isset($token['address'])) {
                update_option('vortex_dao_token_address', sanitize_text_field($token['address']));
            }
            
            if (isset($token['blockchain'])) {
                update_option('vortex_dao_blockchain_network', sanitize_text_field($token['blockchain']));
            }
            
            if (isset($token['name'])) {
                update_option('vortex_dao_token_name', sanitize_text_field($token['name']));
            }
            
            if (isset($token['symbol'])) {
                update_option('vortex_dao_token_symbol', sanitize_text_field($token['symbol']));
            }
        }
        
        // Validate and update royalty configuration
        if (isset($_POST['royalties'])) {
            $royalties = json_decode(stripslashes($_POST['royalties']), true);
            
            if (isset($royalties['artist_max'])) {
                $artist_max = floatval($royalties['artist_max']);
                // Ensure artist max is not greater than cap
                $cap = isset($royalties['cap']) ? floatval($royalties['cap']) : $this->royalty_cap;
                $creator_royalty = isset($royalties['vortex_creator']) ? floatval($royalties['vortex_creator']) : $this->vortex_creator_royalty;
                
                if ($artist_max + $creator_royalty <= $cap) {
                    update_option('vortex_dao_artist_royalty_max', $artist_max);
                }
            }
            
            if (isset($royalties['vortex_creator'])) {
                $creator_royalty = floatval($royalties['vortex_creator']);
                update_option('vortex_dao_vortex_creator_royalty', $creator_royalty);
            }
            
            if (isset($royalties['cap'])) {
                $cap = floatval($royalties['cap']);
                // Ensure cap is not less than creator royalty
                if ($cap >= $this->vortex_creator_royalty) {
                    update_option('vortex_dao_royalty_cap', $cap);
                }
            }
        }
        
        // Validate and update revenue configuration
        if (isset($_POST['revenue'])) {
            $revenue = json_decode(stripslashes($_POST['revenue']), true);
            
            if (isset($revenue['marketplace_commission'])) {
                update_option('vortex_dao_marketplace_commission', floatval($revenue['marketplace_commission']));
            }
            
            // Ensure allocations sum up to 15% (marketplace commission)
            if (isset($revenue['dao_allocation']) && isset($revenue['admin_allocation']) && isset($revenue['creator_allocation'])) {
                $dao_allocation = floatval($revenue['dao_allocation']);
                $admin_allocation = floatval($revenue['admin_allocation']);
                $creator_allocation = floatval($revenue['creator_allocation']);
                
                if ($dao_allocation + $admin_allocation + $creator_allocation == 15) {
                    update_option('vortex_dao_dao_allocation', $dao_allocation);
                    update_option('vortex_dao_admin_allocation', $admin_allocation);
                    update_option('vortex_dao_creator_allocation', $creator_allocation);
                }
            }
        }
        
        // Validate and update treasury allocation
        if (isset($_POST['treasury'])) {
            $treasury = json_decode(stripslashes($_POST['treasury']), true);
            
            if (isset($treasury['grants'])) {
                update_option('vortex_dao_grants_allocation', floatval($treasury['grants']));
            }
            
            if (isset($treasury['exhibitions'])) {
                update_option('vortex_dao_exhibitions_allocation', floatval($treasury['exhibitions']));
            }
            
            if (isset($treasury['artist_support'])) {
                update_option('vortex_dao_artist_support_allocation', floatval($treasury['artist_support']));
            }
        }
        
        // Validate and update fees
        if (isset($_POST['fees'])) {
            $fees = json_decode(stripslashes($_POST['fees']), true);
            
            if (isset($fees['swap_fee_per_artist'])) {
                update_option('vortex_dao_swap_fee_per_artist', floatval($fees['swap_fee_per_artist']));
            }
            
            if (isset($fees['artwork_purchase_fee'])) {
                update_option('vortex_dao_artwork_purchase_fee', floatval($fees['artwork_purchase_fee']));
            }
        }
        
        // Validate and update wallet addresses
        if (isset($_POST['wallets'])) {
            $wallets = json_decode(stripslashes($_POST['wallets']), true);
            
            if (isset($wallets['creator'])) {
                update_option('vortex_dao_creator_wallet', sanitize_text_field($wallets['creator']));
            }
            
            if (isset($wallets['company'])) {
                update_option('vortex_dao_company_wallet', sanitize_text_field($wallets['company']));
            }
            
            if (isset($wallets['treasury'])) {
                update_option('vortex_dao_treasury_wallet', sanitize_text_field($wallets['treasury']));
            }
        }
        
        // Reload configuration
        $this->load_configuration();
        
        wp_send_json_success(array(
            'message' => 'Configuration updated successfully',
            'configuration' => $this->get_admin_configuration()
        ));
    }
    
    /**
     * Shortcode: Display DAO information
     */
    public function dao_info_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_token' => 'true',
            'show_royalties' => 'true',
            'show_revenue' => 'true'
        ), $atts);
        
        // Get configuration
        $config = $this->get_configuration();
        
        // Enqueue necessary styles
        wp_enqueue_style('vortex-dao-info-style');
        
        // Start output buffer
        ob_start();
        
        include(VORTEX_PLUGIN_DIR . 'public/partials/vortex-dao-info.php');
        
        return ob_get_clean();
    }
    
    /**
     * Calculate fee distribution for an artwork sale
     */
    public function calculate_sale_distribution($sale_price, $artist_royalty_percentage, $seller_is_creator = false) {
        // Ensure artist royalty is within limits
        $artist_royalty_percentage = min($artist_royalty_percentage, $this->artist_royalty_max);
        
        // Ensure total royalty doesn't exceed cap
        $total_royalty_percentage = $artist_royalty_percentage + $this->vortex_creator_royalty;
        
        if ($total_royalty_percentage > $this->royalty_cap) {
            $artist_royalty_percentage = $this->royalty_cap - $this->vortex_creator_royalty;
        }
        
        // Calculate marketplace commission
        $marketplace_commission = ($sale_price * $this->marketplace_commission) / 100;
        
        // Calculate DAO allocation
        $dao_allocation = ($sale_price * $this->dao_allocation_percentage) / 100;
        
        // Calculate admin allocation
        $admin_allocation = ($sale_price * $this->admin_allocation_percentage) / 100;
        
        // Calculate creator allocation
        $creator_allocation = ($sale_price * $this->creator_allocation_percentage) / 100;
        
        // Calculate royalties
        $artist_royalty = 0;
        $creator_royalty = 0;
        
        // First-time sales vs. Resales
        if ($seller_is_creator) {
            // First-time sale
            // Artist royalty is already included in the sale price
            // Creator royalty comes from marketplace commission
            $creator_royalty = ($sale_price * $this->vortex_creator_royalty) / 100;
            $amount_to_seller = $sale_price - $marketplace_commission;
        } else {
            // Resale
            // Calculate artist and creator royalties
            $artist_royalty = ($sale_price * $artist_royalty_percentage) / 100;
            $creator_royalty = ($sale_price * $this->vortex_creator_royalty) / 100;
            $amount_to_seller = $sale_price - ($marketplace_commission + $artist_royalty + $creator_royalty);
        }
        
        return array(
            'sale_price' => $sale_price,
            'marketplace_commission' => $marketplace_commission,
            'dao_allocation' => $dao_allocation,
            'admin_allocation' => $admin_allocation,
            'creator_allocation' => $creator_allocation,
            'artist_royalty' => $artist_royalty,
            'artist_royalty_percentage' => $artist_royalty_percentage,
            'creator_royalty' => $creator_royalty,
            'creator_royalty_percentage' => $this->vortex_creator_royalty,
            'amount_to_seller' => $amount_to_seller,
            'seller_is_creator' => $seller_is_creator
        );
    }
    
    /**
     * Calculate swap fees
     */
    public function calculate_swap_fees() {
        return array(
            'per_artist' => $this->swap_fee_per_artist,
            'total' => $this->swap_fee_per_artist * 2, // Two artists involved in swap
            'currency' => get_option('vortex_marketplace_currency', 'USD')
        );
    }
    
    /**
     * Calculate artwork purchase fee
     */
    public function get_artwork_purchase_fee() {
        return array(
            'fee' => $this->artwork_purchase_fee,
            'currency' => get_option('vortex_marketplace_currency', 'USD')
        );
    }
    
    /**
     * Get token address
     */
    public function get_token_address() {
        return $this->token_address;
    }
    
    /**
     * Get blockchain network
     */
    public function get_blockchain_network() {
        return $this->blockchain_network;
    }
    
    /**
     * Get token symbol
     */
    public function get_token_symbol() {
        return $this->token_symbol;
    }
    
    /**
     * Create required database tables
     */
    public static function create_tables() {
        // No specific tables for core DAO functionality
    }
}

// Initialize Core DAO class
$vortex_dao_core = VORTEX_DAO_Core::get_instance();

// Register activation hook for table creation
register_activation_hook(VORTEX_PLUGIN_FILE, array('VORTEX_DAO_Core', 'create_tables')); 