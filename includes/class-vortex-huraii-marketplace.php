<?php
/**
 * HURAII TOLA Marketplace Integration
 *
 * Handles specific marketplace functionality for HURAII-generated artwork
 * with TOLA integration.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_HURAII_Marketplace {
    /**
     * The single instance of this class
     */
    private static $instance = null;
    
    /**
     * Get the singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize the marketplace integration
     */
    public function init() {
        add_action('vortex_before_artwork_purchase', array($this, 'verify_tola_artwork'), 10, 2);
        add_action('vortex_after_artwork_purchase', array($this, 'process_tola_royalties'), 10, 3);
        add_filter('vortex_artwork_price_display', array($this, 'modify_price_display_for_tola'), 10, 2);
    }
    
    /**
     * Verify artwork is properly registered on TOLA before purchase
     */
    public function verify_tola_artwork($artwork_id, $buyer_id) {
        // Check if artwork is HURAII-generated
        $generator = get_post_meta($artwork_id, 'artwork_generator', true);
        
        if ($generator !== 'huraii') {
            return true; // Not a HURAII artwork, skip verification
        }
        
        // Check if NFT exists on TOLA
        global $wpdb;
        $nft_table = $wpdb->prefix . 'vortex_tola_nfts';
        
        $nft = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $nft_table WHERE artwork_id = %d",
            $artwork_id
        ));
        
        if (!$nft) {
            // NFT not found - this is an error for HURAII artwork
            return new WP_Error(
                'tola_verification_failed', 
                __('This HURAII artwork is not properly registered on the TOLA blockchain', 'vortex-ai-marketplace')
            );
        }
        
        return true;
    }
    
    /**
     * Process royalty payments after artwork purchase
     */
    public function process_tola_royalties($artwork_id, $buyer_id, $purchase_data) {
        // Check if artwork is HURAII-generated
        $generator = get_post_meta($artwork_id, 'artwork_generator', true);
        
        if ($generator !== 'huraii') {
            return; // Not a HURAII artwork, skip royalty processing
        }
        
        // Get the smart contract for royalty info
        require_once plugin_dir_path(__FILE__) . 'class-vortex-huraii-smart_contract.php';
        $contract_manager = Vortex_HURAII_Smart_Contract::get_instance();
        
        // Process the royalty payment
        $contract_manager->record_royalty_payment(
            $artwork_id,
            $purchase_data['price'],
            'marketplace_sale'
        );
        
        // Additional blockchain processing would go here in production
        
        // Log the successful royalty payment
        error_log('TOLA royalties processed for artwork #' . $artwork_id);
    }
    
    /**
     * Modify price display to show TOLA logo for HURAII artwork
     */
    public function modify_price_display_for_tola($price_html, $artwork_id) {
        // Check if artwork is HURAII-generated
        $generator = get_post_meta($artwork_id, 'artwork_generator', true);
        
        if ($generator !== 'huraii') {
            return $price_html; // Not a HURAII artwork, use default display
        }
        
        // Add TOLA logo to price display
        $tola_logo_url = plugin_dir_url(dirname(__FILE__)) . 'assets/images/tola-token-icon.png';
        
        return '<span class="vortex-tola-price">
                <img src="' . esc_url($tola_logo_url) . '" alt="TOLA" class="vortex-tola-icon">
                ' . $price_html . '
               </span>';
    }
} 