<?php
/**
 * Fired during plugin activation
 *
 * @link       https://github.com/MarianneNems/VORTEX
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Create custom post types during activation
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/post-types/class-vortex-artwork.php';
        $artwork_post_type = new Vortex_Artwork();
        $artwork_post_type->register();
        
        // Create custom database tables if needed
        self::create_database_tables();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules to ensure our custom post types work
        flush_rewrite_rules();
    }
    
    /**
     * Create custom database tables for the marketplace
     *
     * @since    1.0.0
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for storing blockchain transaction data
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            artwork_id mediumint(9) NOT NULL,
            transaction_hash varchar(255) NOT NULL,
            blockchain varchar(50) NOT NULL,
            seller_address varchar(255) NOT NULL,
            buyer_address varchar(255) NOT NULL,
            price decimal(18,8) NOT NULL,
            transaction_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        
        // Create TOLA token related tables
        if (class_exists('Vortex_TOLA')) {
            $tola = new Vortex_TOLA();
            $tola->create_database_tables();
            $tola->create_purchase_tables();
        }
    }
    
    /**
     * Set default options for the plugin
     *
     * @since    1.0.0
     */
    private static function set_default_options() {
        // General settings
        add_option( 'vortex_marketplace_currency', 'TOLA' );
        add_option( 'vortex_marketplace_commission', '2.5' ); // 2.5% commission
        
        // Blockchain settings
        add_option( 'vortex_blockchain_network', 'ethereum' );
        add_option( 'vortex_contract_address', '' );
        
        // HURAII AI settings
        add_option( 'vortex_huraii_enabled', 'yes' );
        add_option( 'vortex_huraii_default_style', 'abstract' );
    }
} 