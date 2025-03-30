<?php
/**
 * Language/Translation database schema for VORTEX AI Marketplace
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/schema
 */

/**
 * Create language/translation database tables for the VORTEX AI Marketplace.
 *
 * @since    1.0.0
 * @return   array    Array of success/error messages.
 */
function vortex_create_language_schema() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $results = array();

    // Array of SQL statements to create the language tables
    $sql = array();

    // Translations table
    $table_name = $wpdb->prefix . 'vortex_translations';
    $sql[] = "CREATE TABLE $table_name (
        translation_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        entity_type varchar(50) NOT NULL,
        entity_id bigint(20) UNSIGNED NOT NULL,
        field_name varchar(100) NOT NULL,
        source_language varchar(10) NOT NULL,
        target_language varchar(10) NOT NULL,
        source_text text NOT NULL,
        translated_text text NOT NULL,
        translation_method varchar(50) DEFAULT 'api',
        is_machine_translated tinyint(1) DEFAULT 1,
        is_verified tinyint(1) DEFAULT 0,
        verified_by bigint(20) UNSIGNED,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        date_modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (translation_id),
        UNIQUE KEY entity_field_language (entity_type, entity_id, field_name, target_language),
        KEY entity_type (entity_type),
        KEY entity_id (entity_id),
        KEY target_language (target_language),
        KEY is_verified (is_verified)
    ) $charset_collate;";

    // Language preferences table
    $table_name = $wpdb->prefix . 'vortex_language_preferences';
    $sql[] = "CREATE TABLE $table_name (
        preference_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        language_code varchar(10) NOT NULL,
        is_primary tinyint(1) DEFAULT 1,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        date_modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (preference_id),
        UNIQUE KEY user_language (user_id, language_code),
        KEY user_id (user_id),
        KEY language_code (language_code)
    ) $charset_collate;";

    // Translation API usage logs
    $table_name = $wpdb->prefix . 'vortex_translation_logs';
    $sql[] = "CREATE TABLE $table_name (
        log_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        service_provider varchar(50) NOT NULL,
        source_language varchar(10) NOT NULL,
        target_language varchar(10) NOT NULL,
        character_count int DEFAULT 0,
        status varchar(50) DEFAULT 'success',
        error_message text,
        api_response text,
        cost decimal(10,6) DEFAULT 0,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (log_id),
        KEY service_provider (service_provider),
        KEY source_language (source_language),
        KEY target_language (target_language),
        KEY status (status),
        KEY date_created (date_created)
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
 * Setup initial data for language tables
 *
 * @since    1.0.0
 */
function vortex_setup_language_initial_data() {
    global $wpdb;
    
    // Add default language settings to the settings table
    $settings_table = $wpdb->prefix . 'vortex_settings';
    
    $default_settings = array(
        array(
            'setting_name' => 'default_language',
            'setting_value' => 'en',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'enabled_languages',
            'setting_value' => 'en,es,fr,de,it,ja,zh,ru',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'translation_service',
            'setting_value' => 'google',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'auto_translate_content',
            'setting_value' => '1',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'translation_cache_days',
            'setting_value' => '30',
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