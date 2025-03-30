<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://github.com/MarianneNems/VORTEX
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define global variables for plugin tables
global $wpdb;
$artwork_table = $wpdb->prefix . 'vortex_artworks';
$artist_table = $wpdb->prefix . 'vortex_artists';
$metrics_table = $wpdb->prefix . 'vortex_metrics';
$rankings_table = $wpdb->prefix . 'vortex_rankings';
$transactions_table = $wpdb->prefix . 'vortex_transactions';

// Remove plugin tables if they exist
$wpdb->query("DROP TABLE IF EXISTS $artwork_table");
$wpdb->query("DROP TABLE IF EXISTS $artist_table");
$wpdb->query("DROP TABLE IF EXISTS $metrics_table");
$wpdb->query("DROP TABLE IF EXISTS $rankings_table");
$wpdb->query("DROP TABLE IF EXISTS $transactions_table");

// Remove plugin options
delete_option('vortex_ai_marketplace_settings');
delete_option('vortex_huraii_settings');
delete_option('vortex_blockchain_settings');
delete_option('vortex_marketplace_design');
delete_option('vortex_metrics_settings');
delete_option('vortex_language_settings');

// Remove custom post types data
$post_types = array('vortex-artwork', 'vortex-artist');
foreach ($post_types as $post_type) {
    $posts = get_posts(
        array(
            'post_type' => $post_type,
            'numberposts' => -1,
            'post_status' => 'any',
        )
    );
    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }
}

// Clear any cached data
wp_cache_flush(); 