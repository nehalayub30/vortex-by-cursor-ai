<?php
/**
 * Core database schema for VORTEX AI Marketplace
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/schema
 */

/**
 * Create core database tables for the VORTEX AI Marketplace.
 *
 * @since    1.0.0
 * @return   array    Array of success/error messages.
 */
function vortex_create_core_schema() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $results = array();

    // Array of SQL statements to create the core tables
    $sql = array();

    // Artists table
    $table_name = $wpdb->prefix . 'vortex_artists';
    $sql[] = "CREATE TABLE $table_name (
        artist_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        display_name varchar(100) NOT NULL,
        bio text,
        profile_image varchar(255),
        wallet_address varchar(255),
        social_links text,
        specialties text,
        website varchar(255),
        verified tinyint(1) DEFAULT 0,
        status varchar(50) DEFAULT 'pending',
        status_updated datetime,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        date_modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (artist_id),
        KEY user_id (user_id),
        KEY status (status),
        KEY date_created (date_created)
    ) $charset_collate;";

    // Artworks table
    $table_name = $wpdb->prefix . 'vortex_artworks';
    $sql[] = "CREATE TABLE $table_name (
        artwork_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id bigint(20) UNSIGNED,
        artist_id bigint(20) UNSIGNED NOT NULL,
        title varchar(255) NOT NULL,
        description text,
        short_description text,
        thumbnail varchar(255),
        full_image varchar(255),
        price decimal(18,8) DEFAULT 0,
        currency varchar(10) DEFAULT 'TOLA',
        is_for_sale tinyint(1) DEFAULT 0,
        is_minted tinyint(1) DEFAULT 0,
        status varchar(50) DEFAULT 'draft',
        artist_name varchar(100),
        ai_generated tinyint(1) DEFAULT 0,
        ai_model varchar(100),
        ai_prompt text,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        date_modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (artwork_id),
        KEY post_id (post_id),
        KEY artist_id (artist_id),
        KEY status (status),
        KEY is_for_sale (is_for_sale),
        KEY is_minted (is_minted),
        KEY date_created (date_created)
    ) $charset_collate;";

    // Sales/Orders table
    $table_name = $wpdb->prefix . 'vortex_sales';
    $sql[] = "CREATE TABLE $table_name (
        sale_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        artwork_id bigint(20) UNSIGNED NOT NULL,
        artist_id bigint(20) UNSIGNED NOT NULL,
        buyer_id bigint(20) UNSIGNED,
        transaction_id varchar(255),
        payment_method varchar(100),
        price decimal(18,8) NOT NULL,
        currency varchar(10) DEFAULT 'TOLA',
        commission decimal(18,8) DEFAULT 0,
        artist_payout decimal(18,8) DEFAULT 0,
        status varchar(50) DEFAULT 'pending',
        blockchain_tx_hash varchar(255),
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        date_modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (sale_id),
        KEY artwork_id (artwork_id),
        KEY artist_id (artist_id),
        KEY buyer_id (buyer_id),
        KEY status (status),
        KEY date_created (date_created)
    ) $charset_collate;";

    // Artist followers table
    $table_name = $wpdb->prefix . 'vortex_artist_followers';
    $sql[] = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        artist_id bigint(20) UNSIGNED NOT NULL,
        user_id bigint(20) UNSIGNED NOT NULL,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY artist_user (artist_id, user_id),
        KEY artist_id (artist_id),
        KEY user_id (user_id)
    ) $charset_collate;";

    // Artwork favorites/likes table
    $table_name = $wpdb->prefix . 'vortex_artwork_likes';
    $sql[] = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        artwork_id bigint(20) UNSIGNED NOT NULL,
        user_id bigint(20) UNSIGNED NOT NULL,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY artwork_user (artwork_id, user_id),
        KEY artwork_id (artwork_id),
        KEY user_id (user_id)
    ) $charset_collate;";

    // AI Generation logs
    $table_name = $wpdb->prefix . 'vortex_ai_generation_logs';
    $sql[] = "CREATE TABLE $table_name (
        log_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        prompt text NOT NULL,
        ai_model varchar(100) NOT NULL,
        parameters text,
        result_image varchar(255),
        status varchar(50) DEFAULT 'completed',
        execution_time float DEFAULT 0,
        credits_used int DEFAULT 0,
        artwork_id bigint(20) UNSIGNED,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (log_id),
        KEY user_id (user_id),
        KEY ai_model (ai_model),
        KEY status (status),
        KEY date_created (date_created)
    ) $charset_collate;";

    // User credits table
    $table_name = $wpdb->prefix . 'vortex_user_credits';
    $sql[] = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        credits_balance int DEFAULT 0,
        last_purchase_date datetime,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        date_modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;";

    // Settings table
    $table_name = $wpdb->prefix . 'vortex_settings';
    $sql[] = "CREATE TABLE $table_name (
        setting_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        setting_name varchar(100) NOT NULL,
        setting_value text,
        autoload tinyint(1) DEFAULT 1,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        date_modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (setting_id),
        UNIQUE KEY setting_name (setting_name),
        KEY autoload (autoload)
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
 * Setup initial data for core tables
 *
 * @since    1.0.0
 */
function vortex_setup_core_initial_data() {
    global $wpdb;
    
    // Insert default settings
    $settings_table = $wpdb->prefix . 'vortex_settings';
    
    $default_settings = array(
        array(
            'setting_name' => 'commission_rate',
            'setting_value' => '10',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'default_currency',
            'setting_value' => 'TOLA',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'marketplace_status',
            'setting_value' => 'active',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'free_credits_new_user',
            'setting_value' => '10',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'credits_per_generation',
            'setting_value' => '1',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'featured_artworks_count',
            'setting_value' => '8',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'artist_verification_required',
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