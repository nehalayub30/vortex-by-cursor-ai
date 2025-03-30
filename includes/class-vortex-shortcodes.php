<?php
/**
 * Shortcode Registration and Management
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class that handles shortcode registration and loading
 */
class Vortex_Shortcodes {

    /**
     * Initialize and register all shortcodes
     */
    public static function init() {
        // Load shortcode files
        self::load_shortcode_files();
        
        // Register shortcodes
        add_shortcode('vortex_payment_button', 'vortex_payment_button_shortcode');
        add_shortcode('vortex_transaction_history', 'vortex_transaction_history_shortcode');
        add_shortcode('vortex_artist_earnings', 'vortex_artist_earnings_shortcode');
        add_shortcode('vortex_price_estimator', 'vortex_price_estimator_shortcode');
        add_shortcode('vortex_thorius_concierge', 'vortex_thorius_concierge_shortcode');
    }
    
    /**
     * Load all shortcode files
     */
    private static function load_shortcode_files() {
        $plugin_dir = plugin_dir_path(dirname(__FILE__));
        
        // Load each shortcode file
        require_once $plugin_dir . 'public/shortcodes/payment-button-shortcode.php';
        require_once $plugin_dir . 'public/shortcodes/transaction-history-shortcode.php';
        require_once $plugin_dir . 'public/shortcodes/artist-earnings-shortcode.php';
        require_once $plugin_dir . 'public/shortcodes/price-estimator-shortcode.php';
        require_once $plugin_dir . 'public/shortcodes/thorius-concierge-shortcode.php';
    }
}

// Initialize shortcodes
add_action('init', array('Vortex_Shortcodes', 'init')); 