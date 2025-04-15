<?php
/**
 * VORTEX AI Marketplace - Fix Missing Tables Script
 *
 * This script fixes the 'wp_vortex_referrers' and 'wp_vortex_campaigns' tables issue.
 * Run this script directly when encountering database errors related to these tables.
 */

// Load WordPress environment - this assumes that this file is in the root folder of the plugin
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Make sure only administrators can run this script
if (!current_user_can('administrator')) {
    wp_die('You need to be an administrator to run this script.');
}

// Load database migrations class
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-migrations.php';

// Create an instance and fix the missing tables
$db_migrations = new Vortex_DB_Migrations();
$result = $db_migrations->create_referrers_table();

// Output the result
echo "<h1>VORTEX Database Fix</h1>";

if ($result) {
    echo "<p style='color: green;'>✅ Success: The referrers table has been created successfully.</p>";
    
    // Verify campaigns table
    global $wpdb;
    $campaigns_table = $wpdb->prefix . 'vortex_campaigns';
    if ($wpdb->get_var("SHOW TABLES LIKE '$campaigns_table'") === $campaigns_table) {
        echo "<p style='color: green;'>✅ Success: The campaigns table has been verified/created successfully.</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: The campaigns table could not be created. Please check the database permissions.</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Error: Failed to create the referrers table. Please check the database permissions.</p>";
}

// Show next steps
echo "<p>Next steps:</p>";
echo "<ul>";
echo "<li>Return to your WordPress dashboard and check if the database errors have been resolved.</li>";
echo "<li>If you still encounter errors, please contact your administrator or plugin support.</li>";
echo "</ul>";

echo "<p><a href='" . admin_url() . "'>Return to WordPress Dashboard</a></p>"; 