<?php
/**
 * VORTEX History Manager Class
 *
 * Manages all marketplace history logging and retrieval
 */

class VORTEX_History_Manager {
    private $db;
    private $retention_days = 14; // Default retention period
    
    public function __construct() {
        $this->db = new VORTEX_History_DB();
        $this->retention_days = get_option('vortex_history_retention_days', 14);
        
        // Hook into various marketplace actions
        add_action('vortex_artwork_created', array($this, 'log_artwork_created'), 10, 2);
        add_action('vortex_purchase_completed', array($this, 'log_purchase'), 10, 3);
        add_action('vortex_offer_made', array($this, 'log_offer_made'), 10, 3);
        add_action('vortex_offer_accepted', array($this, 'log_offer_accepted'), 10, 3);
        add_action('vortex_offer_rejected', array($this, 'log_offer_rejected'), 10, 3);
        add_action('vortex_collection_updated', array($this, 'log_collection_update'), 10, 2);
        add_action('vortex_blockchain_transaction', array($this, 'log_blockchain_transaction'), 10, 3);
        
        // Run cleanup daily
        if (!wp_next_scheduled('vortex_history_cleanup')) {
            wp_schedule_event(time(), 'daily', 'vortex_history_cleanup');
        }
        add_action('vortex_history_cleanup', array($this, 'cleanup_old_records'));
    }
    
    /**
     * Log artwork creation
     */
    public function log_artwork_created($artwork_id, $user_id) {
        $artwork_data = get_post($artwork_id);
        if (!$artwork_data) return false;
        
        $data = array(
            'item_id' => $artwork_id,
            'item_title' => $artwork_data->post_title,
            'action_details' => json_encode(array(
                'artwork_id' => $artwork_id,
                'creator' => $user_id,
                'creation_date' => current_time('mysql')
            ))
        );
        
        return $this->db->insert_record($user_id, 'artwork_created', $data);
    }
    
    /**
     * Log purchase
     */
    public function log_purchase($purchase_id, $buyer_id, $artwork_id) {
        $artwork_data = get_post($artwork_id);
        if (!$artwork_data) return false;
        
        $purchase_data = get_post_meta($purchase_id, 'purchase_details', true);
        $seller_id = get_post_field('post_author', $artwork_id);
        
        // Log for buyer
        $buyer_data = array(
            'item_id' => $artwork_id,
            'item_title' => $artwork_data->post_title,
            'action_details' => json_encode(array(
                'purchase_id' => $purchase_id,
                'seller_id' => $seller_id,
                'price' => isset($purchase_data['price']) ? $purchase_data['price'] : 0,
                'currency' => isset($purchase_data['currency']) ? $purchase_data['currency'] : 'ETH',
                'purchase_date' => current_time('mysql')
            ))
        );
        $this->db->insert_record($buyer_id, 'purchase', $buyer_data);
        
        // Log for seller
        $seller_data = array(
            'item_id' => $artwork_id,
            'item_title' => $artwork_data->post_title,
            'action_details' => json_encode(array(
                'purchase_id' => $purchase_id,
                'buyer_id' => $buyer_id,
                'price' => isset($purchase_data['price']) ? $purchase_data['price'] : 0,
                'currency' => isset($purchase_data['currency']) ? $purchase_data['currency'] : 'ETH',
                'sale_date' => current_time('mysql')
            ))
        );
        return $this->db->insert_record($seller_id, 'sale', $seller_data);
    }
    
    /**
     * Log offer made
     */
    public function log_offer_made($offer_id, $buyer_id, $artwork_id) {
        $artwork_data = get_post($artwork_id);
        if (!$artwork_data) return false;
        
        $offer_data = get_post_meta($offer_id, 'offer_details', true);
        $seller_id = get_post_field('post_author', $artwork_id);
        
        // Log for buyer
        $buyer_data = array(
            'item_id' => $artwork_id,
            'item_title' => $artwork_data->post_title,
            'action_details' => json_encode(array(
                'offer_id' => $offer_id,
                'seller_id' => $seller_id,
                'price' => isset($offer_data['price']) ? $offer_data['price'] : 0,
                'currency' => isset($offer_data['currency']) ? $offer_data['currency'] : 'ETH',
                'offer_date' => current_time('mysql')
            ))
        );
        $this->db->insert_record($buyer_id, 'offer_made', $buyer_data);
        
        // Log for seller
        $seller_data = array(
            'item_id' => $artwork_id,
            'item_title' => $artwork_data->post_title,
            'action_details' => json_encode(array(
                'offer_id' => $offer_id,
                'buyer_id' => $buyer_id,
                'price' => isset($offer_data['price']) ? $offer_data['price'] : 0,
                'currency' => isset($offer_data['currency']) ? $offer_data['currency'] : 'ETH',
                'offer_date' => current_time('mysql')
            ))
        );
        return $this->db->insert_record($seller_id, 'offer_received', $seller_data);
    }
    
    /**
     * Log offer accepted
     */
    public function log_offer_accepted($offer_id, $seller_id, $artwork_id) {
        $artwork_data = get_post($artwork_id);
        if (!$artwork_data) return false;
        
        $offer_data = get_post_meta($offer_id, 'offer_details', true);
        $buyer_id = isset($offer_data['buyer_id']) ? $offer_data['buyer_id'] : 0;
        
        // Log for seller
        $seller_data = array(
            'item_id' => $artwork_id,
            'item_title' => $artwork_data->post_title,
            'action_details' => json_encode(array(
                'offer_id' => $offer_id,
                'buyer_id' => $buyer_id,
                'price' => isset($offer_data['price']) ? $offer_data['price'] : 0,
                'currency' => isset($offer_data['currency']) ? $offer_data['currency'] : 'ETH',
                'accepted_date' => current_time('mysql')
            ))
        );
        $this->db->insert_record($seller_id, 'offer_accepted', $seller_data);
        
        // Log for buyer
        $buyer_data = array(
            'item_id' => $artwork_id,
            'item_title' => $artwork_data->post_title,
            'action_details' => json_encode(array(
                'offer_id' => $offer_id,
                'seller_id' => $seller_id,
                'price' => isset($offer_data['price']) ? $offer_data['price'] : 0,
                'currency' => isset($offer_data['currency']) ? $offer_data['currency'] : 'ETH',
                'accepted_date' => current_time('mysql')
            ))
        );
        return $this->db->insert_record($buyer_id, 'offer_accepted_by_seller', $buyer_data);
    }
    
    /**
     * Log offer rejected
     */
    public function log_offer_rejected($offer_id, $seller_id, $artwork_id) {
        $artwork_data = get_post($artwork_id);
        if (!$artwork_data) return false;
        
        $offer_data = get_post_meta($offer_id, 'offer_details', true);
        $buyer_id = isset($offer_data['buyer_id']) ? $offer_data['buyer_id'] : 0;
        
        // Log for seller
        $seller_data = array(
            'item_id' => $artwork_id,
            'item_title' => $artwork_data->post_title,
            'action_details' => json_encode(array(
                'offer_id' => $offer_id,
                'buyer_id' => $buyer_id,
                'price' => isset($offer_data['price']) ? $offer_data['price'] : 0,
                'currency' => isset($offer_data['currency']) ? $offer_data['currency'] : 'ETH',
                'rejected_date' => current_time('mysql')
            ))
        );
        $this->db->insert_record($seller_id, 'offer_rejected', $seller_data);
        
        // Log for buyer
        $buyer_data = array(
            'item_id' => $artwork_id,
            'item_title' => $artwork_data->post_title,
            'action_details' => json_encode(array(
                'offer_id' => $offer_id,
                'seller_id' => $seller_id,
                'price' => isset($offer_data['price']) ? $offer_data['price'] : 0,
                'currency' => isset($offer_data['currency']) ? $offer_data['currency'] : 'ETH',
                'rejected_date' => current_time('mysql')
            ))
        );
        return $this->db->insert_record($buyer_id, 'offer_rejected_by_seller', $buyer_data);
    }
    
    /**
     * Log collection update
     */
    public function log_collection_update($collection_id, $user_id) {
        $collection_data = get_term($collection_id, 'vortex_collection');
        if (!$collection_data) return false;
        
        $data = array(
            'item_id' => $collection_id,
            'item_title' => $collection_data->name,
            'action_details' => json_encode(array(
                'collection_id' => $collection_id,
                'update_date' => current_time('mysql')
            ))
        );
        
        return $this->db->insert_record($user_id, 'collection_updated', $data);
    }
    
    /**
     * Log blockchain transaction
     */
    public function log_blockchain_transaction($tx_hash, $user_id, $details) {
        $data = array(
            'item_id' => 0,
            'item_title' => 'Blockchain Transaction',
            'action_details' => json_encode(array(
                'tx_hash' => $tx_hash,
                'details' => $details,
                'transaction_date' => current_time('mysql')
            ))
        );
        
        return $this->db->insert_record($user_id, 'blockchain_transaction', $data);
    }
    
    /**
     * Get user history
     */
    public function get_user_history($user_id, $filters = array()) {
        return $this->db->get_user_records($user_id, $filters);
    }
    
    /**
     * Get all history (admin only)
     */
    public function get_all_history($filters = array()) {
        return $this->db->get_all_records($filters);
    }
    
    /**
     * Clean up old records
     */
    public function cleanup_old_records() {
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-' . $this->retention_days . ' days'));
        return $this->db->delete_old_records($cutoff_date);
    }
    
    /**
     * Get retention period
     */
    public function get_retention_period() {
        return $this->retention_days;
    }
    
    /**
     * Update retention period
     */
    public function update_retention_period($days) {
        if ($days < 1) $days = 1;
        update_option('vortex_history_retention_days', $days);
        $this->retention_days = $days;
        return true;
    }
} 