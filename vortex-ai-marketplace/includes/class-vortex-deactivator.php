<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/MarianneNems/VORTEX
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear any scheduled events
        wp_clear_scheduled_hook('vortex_daily_blockchain_sync');
        wp_clear_scheduled_hook('vortex_hourly_metrics_update');
        
        // Don't delete custom post types or database tables on deactivation
        // That should be done on uninstall only
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
} 