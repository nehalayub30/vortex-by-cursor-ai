<?php
/**
 * Vortex Blockchain AJAX Handlers
 *
 * Handles AJAX requests for blockchain metrics and functionality
 *
 * @package Vortex_Marketplace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Blockchain_AJAX Class
 */
class VORTEX_Blockchain_AJAX {
    /**
     * Constructor
     */
    public function __construct() {
        // Add AJAX handlers
        add_action('wp_ajax_vortex_get_blockchain_metrics', array($this, 'get_blockchain_metrics'));
        add_action('wp_ajax_nopriv_vortex_get_blockchain_metrics', array($this, 'get_blockchain_metrics'));
        
        add_action('wp_ajax_vortex_get_tola_balance', array($this, 'get_user_tola_balance'));
        
        add_action('wp_ajax_vortex_tokenize_artwork', array($this, 'tokenize_artwork'));
        
        add_action('wp_ajax_vortex_create_smart_contract', array($this, 'create_smart_contract'));
    }
    
    /**
     * Get blockchain metrics via AJAX
     */
    public function get_blockchain_metrics() {
        // Check nonce
        check_ajax_referer('vortex_real_time_metrics', 'nonce');
        
        // Get metrics instance
        $blockchain_metrics = VORTEX_Blockchain_Metrics::get_instance();
        
        // Force update metrics
        $metrics = $blockchain_metrics->update_metrics();
        
        wp_send_json_success($metrics);
    }
    
    /**
     * Get user TOLA balance
     */
    public function get_user_tola_balance() {
        // Check nonce
        check_ajax_referer('vortex_tola_actions', 'nonce');
        
        // Get current user
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
            return;
        }
        
        // Get TOLA integration instance
        $tola_integration = VORTEX_TOLA_Integration::get_instance();
        $balance = $tola_integration->get_user_balance($user_id);
        
        wp_send_json_success(array(
            'balance' => $balance,
            'formatted_balance' => number_format($balance, 2) . ' TOLA'
        ));
    }
    
    /**
     * Tokenize artwork via AJAX
     */
    public function tokenize_artwork() {
        // Check nonce
        check_ajax_referer('vortex_tola_actions', 'nonce');
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Get artwork ID
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        
        if (!$artwork_id) {
            wp_send_json_error(array('message' => 'Invalid artwork ID'));
            return;
        }
        
        // Get tokenization settings
        $settings = array(
            'initial_price' => isset($_POST['initial_price']) ? floatval($_POST['initial_price']) : 0,
            'royalty_percentage' => isset($_POST['royalty_percentage']) ? floatval($_POST['royalty_percentage']) : 10,
            'token_supply' => isset($_POST['token_supply']) ? intval($_POST['token_supply']) : 1
        );
        
        // Get TOLA integration instance
        $tola_integration = VORTEX_TOLA_Integration::get_instance();
        $result = $tola_integration->tokenize_artwork($artwork_id, $settings);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Award points for tokenization
        if (class_exists('VORTEX_Gamification')) {
            VORTEX_Gamification::get_instance()->add_points(
                get_current_user_id(),
                'artwork_tokenized',
                50,
                sprintf(__('Tokenized artwork "%s" on the blockchain', 'vortex-marketplace'), get_the_title($artwork_id))
            );
        }
        
        wp_send_json_success(array(
            'message' => __('Artwork tokenized successfully', 'vortex-marketplace'),
            'transaction_id' => $result['transaction_id'],
            'token_id' => $result['token_id']
        ));
    }
    
    /**
     * Create smart contract via AJAX
     */
    public function create_smart_contract() {
        // Check nonce
        check_ajax_referer('vortex_tola_actions', 'nonce');
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // Get contract parameters
        $contract_type = isset($_POST['contract_type']) ? sanitize_text_field($_POST['contract_type']) : '';
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        $terms = isset($_POST['terms']) ? $_POST['terms'] : array();
        
        if (!$contract_type || !$artwork_id) {
            wp_send_json_error(array('message' => 'Missing required parameters'));
            return;
        }
        
        // Get TOLA integration instance
        $tola_integration = VORTEX_TOLA_Integration::get_instance();
        $result = $tola_integration->create_smart_contract($contract_type, $artwork_id, $terms);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Award points for creating a smart contract
        if (class_exists('VORTEX_Gamification')) {
            VORTEX_Gamification::get_instance()->add_points(
                get_current_user_id(),
                'smart_contract_created',
                75,
                sprintf(__('Created a %s smart contract for artwork "%s"', 'vortex-marketplace'), 
                    $contract_type, 
                    get_the_title($artwork_id)
                )
            );
        }
        
        wp_send_json_success(array(
            'message' => __('Smart contract created successfully', 'vortex-marketplace'),
            'contract_id' => $result['contract_id'],
            'transaction_id' => $result['transaction_id']
        ));
    }
}

// Initialize Blockchain AJAX handlers
new VORTEX_Blockchain_AJAX(); 