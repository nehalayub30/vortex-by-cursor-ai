<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://vortexartec.com
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
     * Handles tasks performed during plugin deactivation.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Clean up temporary data
        self::clean_temp_data();
        
        // Log deactivation
        self::log_deactivation();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Clear all scheduled events created by the plugin.
     *
     * @since    1.0.0
     */
    private static function clear_scheduled_events() {
        // Clear daily metrics update event
        wp_clear_scheduled_hook( 'vortex_daily_metrics_update' );
        
        // Clear weekly rankings update event
        wp_clear_scheduled_hook( 'vortex_weekly_rankings_update' );
        
        // Clear subscription expiration check event
        wp_clear_scheduled_hook( 'vortex_check_subscription_expirations' );
        
        // Clear any other scheduled events
        wp_clear_scheduled_hook( 'vortex_blockchain_sync' );
        wp_clear_scheduled_hook( 'vortex_clean_temp_files' );
    }

    /**
     * Clean up temporary data on deactivation.
     *
     * @since    1.0.0
     */
    private static function clean_temp_data() {
        // Delete transients
        delete_transient( 'vortex_marketplace_stats' );
        delete_transient( 'vortex_featured_artworks' );
        delete_transient( 'vortex_top_artists' );
        delete_transient( 'vortex_blockchain_status' );
        
        // Clean up temp directory
        $temp_dir = wp_upload_dir()['basedir'] . '/vortex-temp';
        if ( is_dir( $temp_dir ) ) {
            $files = glob( $temp_dir . '/*' );
            
            // Only delete files in temp directory, not the directory itself
            // This ensures custom theme files aren't accidentally removed
            foreach ( $files as $file ) {
                if ( is_file( $file ) ) {
                    @unlink( $file );
                }
            }
        }
    }

    /**
     * Log plugin deactivation for troubleshooting.
     *
     * @since    1.0.0
     */
    private static function log_deactivation() {
        // Store deactivation time
        update_option( 'vortex_deactivated_time', time() );
        
        // Store WordPress and PHP versions at deactivation for troubleshooting
        update_option( 'vortex_deactivation_wp_version', get_bloginfo( 'version' ) );
        update_option( 'vortex_deactivation_php_version', PHP_VERSION );
        
        // Log deactivation reason if provided by user
        if ( isset( $_POST['deactivate_reason'] ) ) {
            update_option( 
                'vortex_deactivation_reason', 
                sanitize_text_field( wp_unslash( $_POST['deactivate_reason'] ) )
            );
        }
    }
}
