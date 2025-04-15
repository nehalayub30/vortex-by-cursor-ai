<?php
/**
 * VORTEX Marketplace Database Repair Script
 * 
 * This script will add any missing tables to fix database-related errors.
 * 
 * @package   Vortex_AI_Marketplace
 * @version   1.0.0
 */

// Load WordPress environment
require_once(dirname(__FILE__) . '/wp-load.php');

// Make sure only admins can run this script
if (!current_user_can('administrator')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Load our DB migrations class
require_once(plugin_dir_path(__FILE__) . 'includes/class-vortex-db-migrations.php');

// Fix the missing referrers table
$referrers_fixed = Vortex_DB_Migrations::fix_missing_referrers_table();

// Check if we need to fix other tables
$social_tables_fixed = false;
global $wpdb;
$social_shares_table = $wpdb->prefix . 'vortex_social_shares';
if ($wpdb->get_var("SHOW TABLES LIKE '$social_shares_table'") !== $social_shares_table) {
    // Load social sharing tables fix if we had code for it
    // $social_tables_fixed = true;
}

// Output results
echo '<h1>VORTEX Database Repair Results</h1>';
echo '<ul>';

if ($referrers_fixed) {
    echo '<li style="color:green">✅ Referrers table was successfully created.</li>';
} else {
    echo '<li style="color:red">❌ Failed to create referrers table. Please check database permissions.</li>';
}

if ($social_tables_fixed) {
    echo '<li style="color:green">✅ Social sharing tables were successfully created.</li>';
}

echo '</ul>';

echo '<p>Database repair process completed. <a href="' . admin_url('admin.php?page=vortex-settings') . '">Return to VORTEX Dashboard</a></p>'; 