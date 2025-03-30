<?php
/**
 * The rankings database functionality of the plugin.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/database
 */

/**
 * The rankings database functionality of the plugin.
 *
 * This class handles database operations related to artist and artwork rankings.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/database
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Rankings_DB {

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Ensure rankings table exists
        $this->create_tables();
    }

    /**
     * Create the necessary database tables.
     *
     * @since    1.0.0
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $rankings_table = $wpdb->prefix . 'vortex_rankings';
        
        $sql = "CREATE TABLE IF NOT EXISTS $rankings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            artist_id bigint(20) NOT NULL,
            score decimal(10,2)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
} 