<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * This class defines all code necessary to run during the plugin's uninstallation.
 */
class Vortex_Uninstaller {

    /**
     * Main uninstall function to clean up all plugin data.
     */
    public static function uninstall() {
        // Get global wpdb class
        global $wpdb;

        // Set time limit for large sites
        set_time_limit( 300 );

        // Define custom tables to drop
        $tables = array(
            $wpdb->prefix . 'vortex_transactions',
            $wpdb->prefix . 'vortex_product_purchases',
            $wpdb->prefix . 'vortex_metrics',
            $wpdb->prefix . 'vortex_rankings',
            $wpdb->prefix . 'vortex_sales',
            $wpdb->prefix . 'vortex_tola',
            $wpdb->prefix . 'vortex_tola_transactions',
        );

        // Drop custom tables
        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
        }

        // Delete post types
        $post_types = array(
            'vortex_artwork',
            'vortex_artist',
            'vortex_huraii_template',
        );

        foreach ( $post_types as $post_type ) {
            $items = get_posts( array(
                'post_type'   => $post_type,
                'post_status' => 'any',
                'numberposts' => -1,
                'fields'      => 'ids',
            ) );

            if ( $items ) {
                foreach ( $items as $item ) {
                    wp_delete_post( $item, true );
                }
            }
        }

        // Delete terms and taxonomies
        $taxonomies = array(
            'vortex_artwork_category',
            'vortex_artwork_tag',
        );

        foreach ( $taxonomies as $taxonomy ) {
            $terms = get_terms( array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'fields'     => 'ids',
            ) );

            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                foreach ( $terms as $term ) {
                    wp_delete_term( $term, $taxonomy );
                }
            }
        }

        // Clear any cached data that has been cached
        wp_cache_flush();

        // Delete plugin options
        $options = array(
            'vortex_marketplace_currency',
            'vortex_marketplace_commission',
            'vortex_blockchain_network',
            'vortex_contract_address',
            'vortex_solana_rpc_url',
            'vortex_solana_network',
            'vortex_tola_token_address',
            'vortex_tola_decimals',
            'vortex_platform_wallet_address',
            'vortex_huraii_enabled',
            'vortex_huraii_default_style',
            'vortex_stability_ai_api_key',
            'vortex_openai_api_key',
            'vortex_midjourney_api_key',
            'vortex_midjourney_api_url',
            'vortex_default_text2img_model',
            'vortex_rankings_last_updated',
        );

        foreach ( $options as $option ) {
            delete_option( $option );
        }

        // Delete user meta
        $user_meta_keys = array(
            'vortex_wallet_address',
            'vortex_tola_balance',
            'vortex_purchased_products',
        );

        // Get all users
        $users = get_users( array( 'fields' => 'ids' ) );
        
        foreach ( $users as $user_id ) {
            foreach ( $user_meta_keys as $meta_key ) {
                delete_user_meta( $user_id, $meta_key );
            }
            
            // Delete all expiration meta keys that match pattern
            $wpdb->query( $wpdb->prepare( 
                "DELETE FROM {$wpdb->usermeta} 
                WHERE user_id = %d 
                AND meta_key LIKE %s",
                $user_id,
                'vortex_product_%_expiration'
            ) );
        }

        // Remove scheduled events
        wp_clear_scheduled_hook( 'vortex_daily_metrics_update' );
        wp_clear_scheduled_hook( 'vortex_weekly_rankings_update' );
        wp_clear_scheduled_hook( 'vortex_check_subscription_expirations' );
    }
}

// Run uninstaller
Vortex_Uninstaller::uninstall();
