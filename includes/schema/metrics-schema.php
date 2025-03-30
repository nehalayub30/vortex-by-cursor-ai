<?php
/**
 * Metrics database schema for VORTEX AI Marketplace
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/schema
 */

/**
 * Create metrics database tables for the VORTEX AI Marketplace.
 *
 * @since    1.0.0
 * @return   array    Array of success/error messages.
 */
function vortex_create_metrics_schema() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $results = array();

    // Array of SQL statements to create the metrics tables
    $sql = array();

    // Artwork statistics
    $table_name = $wpdb->prefix . 'vortex_artwork_stats';
    $sql[] = "CREATE TABLE $table_name (
        stats_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        artwork_id bigint(20) UNSIGNED NOT NULL,
        views int DEFAULT 0,
        likes int DEFAULT 0,
        shares int DEFAULT 0,
        downloads int DEFAULT 0,
        sales_count int DEFAULT 0,
        total_revenue decimal(18,8) DEFAULT 0,
        last_updated datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (stats_id),
        UNIQUE KEY artwork_id (artwork_id),
        KEY views (views),
        KEY likes (likes),
        KEY sales_count (sales_count)
    ) $charset_collate;";

    // Artist statistics
    $table_name = $wpdb->prefix . 'vortex_artist_stats';
    $sql[] = "CREATE TABLE $table_name (
        stats_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        artist_id bigint(20) UNSIGNED NOT NULL,
        total_views int DEFAULT 0,
        total_followers int DEFAULT 0,
        total_artworks int DEFAULT 0,
        total_sales int DEFAULT 0,
        total_revenue decimal(18,8) DEFAULT 0,
        artworks_count int DEFAULT 0,
        artworks_for_sale int DEFAULT 0,
        last_updated datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (stats_id),
        UNIQUE KEY artist_id (artist_id),
        KEY total_followers (total_followers),
        KEY total_sales (total_sales)
    ) $charset_collate;";

    // Page views tracking
    $table_name = $wpdb->prefix . 'vortex_page_views';
    $sql[] = "CREATE TABLE $table_name (
        view_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        entity_type varchar(50) NOT NULL,
        entity_id bigint(20) UNSIGNED NOT NULL,
        user_id bigint(20) UNSIGNED,
        session_id varchar(255),
        ip_address varchar(45),
        user_agent text,
        referrer varchar(255),
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (view_id),
        KEY entity_type (entity_type),
        KEY entity_id (entity_id),
        KEY user_id (user_id),
        KEY date_created (date_created)
    ) $charset_collate;";

    // User activity logs
    $table_name = $wpdb->prefix . 'vortex_user_activity';
    $sql[] = "CREATE TABLE $table_name (
        activity_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        activity_type varchar(50) NOT NULL,
        activity_data text,
        ip_address varchar(45),
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (activity_id),
        KEY user_id (user_id),
        KEY activity_type (activity_type),
        KEY date_created (date_created)
    ) $charset_collate;";

    // Daily metrics aggregation
    $table_name = $wpdb->prefix . 'vortex_daily_metrics';
    $sql[] = "CREATE TABLE $table_name (
        metric_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        metric_date date NOT NULL,
        metric_type varchar(50) NOT NULL,
        metric_value decimal(18,8) DEFAULT 0,
        additional_data text,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (metric_id),
        UNIQUE KEY date_type (metric_date, metric_type),
        KEY metric_date (metric_date),
        KEY metric_type (metric_type)
    ) $charset_collate;";

    // Monthly metrics aggregation
    $table_name = $wpdb->prefix . 'vortex_monthly_metrics';
    $sql[] = "CREATE TABLE $table_name (
        metric_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        year int NOT NULL,
        month int NOT NULL,
        metric_type varchar(50) NOT NULL,
        metric_value decimal(18,8) DEFAULT 0,
        additional_data text,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (metric_id),
        UNIQUE KEY year_month_type (year, month, metric_type),
        KEY year (year),
        KEY month (month),
        KEY metric_type (metric_type)
    ) $charset_collate;";

    // Execute the SQL statements
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    foreach ($sql as $query) {
        dbDelta($query);
        
        // Check for errors
        if ($wpdb->last_error) {
            $results[] = array(
                'status' => 'error',
                'message' => $wpdb->last_error,
                'query' => $query
            );
        } else {
            $table = preg_match('/CREATE TABLE ([^\s(]+)/', $query, $matches) ? $matches[1] : 'Unknown table';
            $results[] = array(
                'status' => 'success',
                'message' => "Table $table created or updated successfully",
                'query' => $query
            );
        }
    }

    return $results;
}

/**
 * Setup initial data for metrics tables
 *
 * @since    1.0.0
 */
function vortex_setup_metrics_initial_data() {
    global $wpdb;
    
    // Add default metrics settings to the settings table
    $settings_table = $wpdb->prefix . 'vortex_settings';
    
    $default_settings = array(
        array(
            'setting_name' => 'metrics_retention_days',
            'setting_value' => '90',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'track_anonymous_views',
            'setting_value' => '1',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'metrics_update_interval',
            'setting_value' => 'hourly',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'metrics_exclude_admins',
            'setting_value' => '1',
            'autoload' => 1
        )
    );
    
    foreach ($default_settings as $setting) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $settings_table WHERE setting_name = %s",
            $setting['setting_name']
        ));
        
        if (!$exists) {
            $wpdb->insert(
                $settings_table,
                $setting
            );
        }
    }
} 