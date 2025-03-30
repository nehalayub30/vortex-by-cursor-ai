/**
 * Create required database tables
 */
public function create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Feedback table for learning system
    $table_name = $wpdb->prefix . 'vortex_thorius_feedback';
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        conversation_id varchar(32) NOT NULL,
        query_id varchar(32) NOT NULL,
        rating tinyint(1) NOT NULL DEFAULT 0,
        feedback text NOT NULL,
        agent varchar(50) NOT NULL,
        user_id bigint(20) NOT NULL DEFAULT 0,
        timestamp datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY conversation_id (conversation_id),
        KEY agent (agent),
        KEY rating (rating),
        KEY timestamp (timestamp)
    ) $charset_collate;";
    
    // Cache table for performance optimization
    $table_name = $wpdb->prefix . 'vortex_thorius_cache';
    $sql .= "CREATE TABLE $table_name (
        cache_key varchar(255) NOT NULL,
        cache_value longtext NOT NULL,
        expiry bigint(20) NOT NULL,
        PRIMARY KEY  (cache_key),
        KEY expiry (expiry)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Store database version
    update_option('vortex_thorius_db_version', VORTEX_THORIUS_DB_VERSION);
} 