<?php
/**
 * Handles artist royalty management and distribution
 */
class Vortex_Royalty_Manager {
    /**
     * Initialize the royalty manager
     */
    public function __construct() {
        add_action('vortex_secondary_sale', array($this, 'process_royalty_distribution'), 10, 3);
        add_action('vortex_collaboration_sale', array($this, 'process_collaboration_royalties'), 10, 4);
        add_filter('vortex_nft_metadata', array($this, 'add_royalty_metadata'), 10, 2);
        add_action('init', array($this, 'register_royalty_endpoints'));
    }
    
    /**
     * Process royalty distribution for secondary sales
     * 
     * @param int $artwork_id The artwork ID
     * @param float $sale_amount The sale amount
     * @param int $artist_id The artist ID
     * @return bool Success status
     */
    public function process_royalty_distribution($artwork_id, $sale_amount, $artist_id) {
        // Get royalty percentage for this artwork (default 2.5% if not set)
        $royalty_percentage = get_post_meta($artwork_id, 'vortex_royalty_percentage', true);
        $royalty_percentage = $royalty_percentage ? floatval($royalty_percentage) : 2.5;
        
        // Calculate royalty amount
        $royalty_amount = $sale_amount * ($royalty_percentage / 100);
        
        // Get artist wallet address
        $artist_wallet = get_user_meta($artist_id, 'vortex_wallet_address', true);
        if (empty($artist_wallet)) {
            return new WP_Error('invalid_wallet', 'Artist wallet address not found');
        }
        
        // Execute blockchain transaction for royalty
        $blockchain = Vortex_AI_Marketplace::get_instance()->blockchain;
        $result = $blockchain->send_tokens($artist_wallet, $royalty_amount, 'royalty_payment');
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Log royalty payment
        $this->log_royalty_payment($artwork_id, $artist_id, $royalty_amount, $royalty_percentage);
        
        return true;
    }
    
    /**
     * Process royalties for collaborative works
     */
    public function process_collaboration_royalties($artwork_id, $sale_amount, $collaborators, $percentages) {
        // Implementation for split royalties among collaborators
        // Code would validate percentages add up to 100% and distribute accordingly
    }
    
    /**
     * Log royalty payment details
     */
    private function log_royalty_payment($artwork_id, $artist_id, $amount, $percentage) {
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_royalty_payments';
        
        return $wpdb->insert(
            $table,
            array(
                'artwork_id' => $artwork_id,
                'artist_id' => $artist_id,
                'amount' => $amount,
                'percentage' => $percentage,
                'payment_date' => current_time('mysql'),
                'transaction_id' => uniqid('royalty_')
            ),
            array('%d', '%d', '%f', '%f', '%s', '%s')
        );
    }
} 