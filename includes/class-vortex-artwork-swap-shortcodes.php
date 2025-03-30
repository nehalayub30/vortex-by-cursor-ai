<?php
/**
 * Artwork Swap Shortcodes
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Shortcodes for Artwork Swap functionality
 */
class Vortex_Artwork_Swap_Shortcodes {
    
    /**
     * Initialize the shortcodes
     */
    public function __construct() {
        // Register shortcodes
        add_shortcode('vortex_artwork_swap_dashboard', array($this, 'artwork_swap_dashboard_shortcode'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue scripts and styles for the artwork swap dashboard
     */
    public function enqueue_scripts() {
        // Only enqueue on pages with the shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'vortex_artwork_swap_dashboard')) {
            // Enqueue CSS
            wp_enqueue_style(
                'vortex-artwork-swap-dashboard',
                plugin_dir_url(dirname(__FILE__)) . 'public/css/artwork-swap-dashboard.css',
                array('dashicons'),
                VORTEX_AI_MARKETPLACE_VERSION
            );
            
            // Enqueue JavaScript
            wp_enqueue_script(
                'vortex-artwork-swap-dashboard',
                plugin_dir_url(dirname(__FILE__)) . 'public/js/artwork-swap-dashboard.js',
                array('jquery'),
                VORTEX_AI_MARKETPLACE_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script(
                'vortex-artwork-swap-dashboard',
                'vortex_params',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'verification_note' => __('Your verification request has been submitted and is pending review. You\'ll be notified once it\'s approved.', 'vortex-ai-marketplace'),
                    'submit_text' => __('Submit Verification', 'vortex-ai-marketplace'),
                    'error_message' => __('An error occurred. Please try again.', 'vortex-ai-marketplace'),
                    'form_incomplete' => __('Please fill out all required fields and agree to the terms.', 'vortex-ai-marketplace'),
                    'contract_prefix' => __('Contract Address', 'vortex-ai-marketplace'),
                    'transaction_prefix' => __('Transaction', 'vortex-ai-marketplace'),
                    'token_id_prefix' => __('Token ID', 'vortex-ai-marketplace'),
                    'explorer_url' => 'https://explorer.tola-chain.io/tx/',
                    'search_nonce' => wp_create_nonce('vortex_search_nonce'),
                    'profile_nonce' => wp_create_nonce('vortex_profile_nonce'),
                    'artwork_nonce' => wp_create_nonce('vortex_artwork_nonce'),
                    'swap_nonce' => wp_create_nonce('vortex_swap_nonce'),
                    'submitting_text' => __('Submitting...', 'vortex-ai-marketplace'),
                    'submit_proposal_text' => __('Submit Swap Proposal', 'vortex-ai-marketplace'),
                    'processing_text' => __('Processing...', 'vortex-ai-marketplace'),
                    'cancel_swap_text' => __('Cancel Swap', 'vortex-ai-marketplace'),
                    'complete_swap_text' => __('Complete Swap', 'vortex-ai-marketplace'),
                    'accept_swap_text' => __('Accept Swap', 'vortex-ai-marketplace'),
                    'decline_swap_text' => __('Decline', 'vortex-ai-marketplace'),
                    'cancel_confirm' => __('Are you sure you want to cancel this swap?', 'vortex-ai-marketplace'),
                    'decline_confirm' => __('Are you sure you want to decline this swap?', 'vortex-ai-marketplace'),
                    'complete_confirm' => __('Are you sure you want to complete this swap? This will execute the transfer on the blockchain.', 'vortex-ai-marketplace'),
                    'waiting_completion' => __('Waiting for completion...', 'vortex-ai-marketplace'),
                    'no_swaps_message' => __('You don\'t have any active swaps.', 'vortex-ai-marketplace'),
                    'no_initiated_swaps' => __('You haven\'t initiated any swaps yet.', 'vortex-ai-marketplace'),
                    'no_received_swaps' => __('You haven\'t received any swap proposals yet.', 'vortex-ai-marketplace'),
                    'no_swap_history' => __('You haven\'t completed any artwork swaps yet.', 'vortex-ai-marketplace'),
                    'swap_completed_title' => __('Swap Completed Successfully!', 'vortex-ai-marketplace'),
                    'tola_reward_message' => __('You\'ve earned 100 TOLA tokens for this swap!', 'vortex-ai-marketplace')
                )
            );
        }
    }
    
    /**
     * Artwork swap dashboard shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     */
    public function artwork_swap_dashboard_shortcode($atts) {
        // Extract attributes
        $atts = shortcode_atts(
            array(
                'default_tab' => 'my-artworks'
            ),
            $atts,
            'vortex_artwork_swap_dashboard'
        );
        
        // Set default tab in JavaScript
        wp_add_inline_script(
            'vortex-artwork-swap-dashboard',
            'localStorage.setItem("vortexActiveTab", "#' . esc_js($atts['default_tab']) . '");',
            'before'
        );
        
        // Start output buffer
        ob_start();
        
        // Include template
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/partials/artwork-swap-dashboard.php';
        
        // Return buffered content
        return ob_get_clean();
    }
}

// Initialize shortcodes
new Vortex_Artwork_Swap_Shortcodes(); 