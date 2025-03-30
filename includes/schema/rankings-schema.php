<?php
/**
 * Rankings database schema for VORTEX AI Marketplace
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/schema
 */

/**
 * Create rankings database tables for the VORTEX AI Marketplace.
 *
 * @since    1.0.0
 * @return   array    Array of success/error messages.
 */
function vortex_create_rankings_schema() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $results = array();

    // Array of SQL statements to create the rankings tables
    $sql = array();

    // Artist rankings
    $table_name = $wpdb->prefix . 'vortex_artist_rankings';
    $sql[] = "CREATE TABLE $table_name (
        ranking_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        artist_id bigint(20) UNSIGNED NOT NULL,
        period varchar(20) NOT NULL,
        score decimal(10,4) DEFAULT 0,
        sales int DEFAULT 0,
        revenue decimal(18,8) DEFAULT 0,
        views int DEFAULT 0,
        followers int DEFAULT 0,
        last_updated datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (ranking_id),
        UNIQUE KEY artist_period (artist_id, period),
        KEY period (period),
        KEY score (score),
        KEY sales (sales),
        KEY revenue (revenue),
        KEY followers (followers)
    ) $charset_collate;";

    // Artwork rankings
    $table_name = $wpdb->prefix . 'vortex_artwork_rankings';
    $sql[] = "CREATE TABLE $table_name (
        ranking_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        artwork_id bigint(20) UNSIGNED NOT NULL,
        period varchar(20) NOT NULL,
        score decimal(10,4) DEFAULT 0,
        sales int DEFAULT 0,
        revenue decimal(18,8) DEFAULT 0,
        views int DEFAULT 0,
        likes int DEFAULT 0,
        shares int DEFAULT 0,
        last_updated datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (ranking_id),
        UNIQUE KEY artwork_period (artwork_id, period),
        KEY period (period),
        KEY score (score),
        KEY sales (sales),
        KEY revenue (revenue),
        KEY views (views),
        KEY likes (likes)
    ) $charset_collate;";

    // Tag rankings
    $table_name = $wpdb->prefix . 'vortex_tag_rankings';
    $sql[] = "CREATE TABLE $table_name (
        ranking_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        tag_id bigint(20) UNSIGNED NOT NULL,
        tag_name varchar(255) NOT NULL,
        tag_slug varchar(255) NOT NULL,
        period varchar(20) NOT NULL,
        count int DEFAULT 0,
        last_updated datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (ranking_id),
        UNIQUE KEY tag_period (tag_id, period),
        KEY period (period),
        KEY count (count),
        KEY tag_slug (tag_slug)
    ) $charset_collate;";

    // AI model rankings
    $table_name = $wpdb->prefix . 'vortex_model_rankings';
    $sql[] = "CREATE TABLE $table_name (
        ranking_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        model_id bigint(20) UNSIGNED NOT NULL,
        model_name varchar(100) NOT NULL,
        model_slug varchar(100) NOT NULL,
        period varchar(20) NOT NULL,
        usage_count int DEFAULT 0,
        sales_count int DEFAULT 0,
        sales_volume decimal(18,8) DEFAULT 0,
        avg_price decimal(18,8) DEFAULT 0,
        last_updated datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (ranking_id),
        UNIQUE KEY model_period (model_id, period),
        KEY period (period),
        KEY usage_count (usage_count),
        KEY sales_count (sales_count),
        KEY sales_volume (sales_volume),
        KEY model_slug (model_slug)
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
 * Setup initial data for rankings tables
 *
 * @since    1.0.0
 */
function vortex_setup_rankings_initial_data() {
    global $wpdb;
    
    // Add default rankings settings to the settings table
    $settings_table = $wpdb->prefix . 'vortex_settings';
    
    $default_settings = array(
        array(
            'setting_name' => 'rankings_update_frequency',
            'setting_value' => 'daily',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'artist_ranking_formula',
            'setting_value' => '{"sales_weight":40,"revenue_weight":30,"views_weight":10,"followers_weight":20}',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'artwork_ranking_formula',
            'setting_value' => '{"sales_weight":40,"revenue_weight":30,"views_weight":15,"likes_weight":15}',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'display_top_rankings_count',
            'setting_value' => '10',
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