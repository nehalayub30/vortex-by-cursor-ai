<?php
/**
 * Database Migrations for Vortex AI Marketplace
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Database Migrations Class
 *
 * Handles database table creation and updates for the plugin
 *
 * @since      1.0.0
 */
class Vortex_DB_Migrations {
    /**
     * The version of the database schema.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $db_version    The current version of the database schema.
     */
    private $db_version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->db_version = get_option('vortex_ai_db_version', '0.0.0');
        
        // Register activation hook for database setup
        add_action('vortex_ai_activate', array($this, 'setup_database'));
        
        // Check if we need to update database
        add_action('plugins_loaded', array($this, 'check_update_database'));
    }

    /**
     * Check if we need to update the database schema
     *
     * @since    1.0.0
     */
    public function check_update_database() {
        $current_version = VORTEX_AI_VERSION;
        
        if (version_compare($this->db_version, $current_version, '<')) {
            $this->setup_database();
            update_option('vortex_ai_db_version', $current_version);
        }
    }

    /**
     * Set up all required database tables
     *
     * @since    1.0.0
     */
    public function setup_database() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create artwork themes table
        $this->create_artwork_themes_table($charset_collate);
        
        // Create user sessions table
        $this->create_user_sessions_table($charset_collate);
        
        // Create user activity table
        $this->create_user_activity_table($charset_collate);
        
        // Create CLOE analytics tables
        $this->create_cloe_analytics_tables($charset_collate);
        
        // Create artwork statistics table
        $this->create_artwork_statistics_table($charset_collate);
        
        // Create Thorius tables
        $this->create_thorius_tables($charset_collate);
    }

    /**
     * Create artwork themes table
     *
     * @since    1.0.0
     * @param    string    $charset_collate    Database charset and collation
     */
    private function create_artwork_themes_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_artwork_themes';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            theme_name varchar(191) NOT NULL,
            theme_slug varchar(191) NOT NULL,
            theme_description text,
            theme_parent bigint(20) unsigned DEFAULT NULL,
            popularity_score decimal(10,2) DEFAULT '0.00',
            creation_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            artwork_count int(11) DEFAULT '0',
            trending_score decimal(10,2) DEFAULT '0.00',
            PRIMARY KEY  (id),
            UNIQUE KEY theme_slug (theme_slug),
            KEY theme_parent (theme_parent),
            KEY popularity_score (popularity_score),
            KEY trending_score (trending_score)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create user sessions table
     *
     * @since    1.0.0
     * @param    string    $charset_collate    Database charset and collation
     */
    private function create_user_sessions_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_user_sessions';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            session_key varchar(64) NOT NULL,
            session_start datetime DEFAULT CURRENT_TIMESTAMP,
            session_end datetime DEFAULT NULL,
            session_duration int(11) DEFAULT '0',
            session_data longtext,
            client_ip varchar(40) DEFAULT NULL,
            user_agent text,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY session_key (session_key),
            KEY session_start (session_start)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create user activity table
     *
     * @since    1.0.0
     * @param    string    $charset_collate    Database charset and collation
     */
    private function create_user_activity_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_user_activity';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id bigint(20) unsigned DEFAULT NULL,
            activity_type varchar(64) NOT NULL,
            activity_time datetime DEFAULT CURRENT_TIMESTAMP,
            object_id bigint(20) unsigned DEFAULT NULL,
            object_type varchar(64) DEFAULT NULL,
            activity_data longtext,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY activity_type (activity_type),
            KEY activity_time (activity_time),
            KEY object_id (object_id),
            KEY object_type (object_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create CLOE analytics tables
     *
     * @since    1.0.0
     * @param    string    $charset_collate    Database charset and collation
     */
    private function create_cloe_analytics_tables($charset_collate) {
        global $wpdb;
        
        // User preferences table
        $table_name = $wpdb->prefix . 'vortex_user_preferences';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            preference_key varchar(191) NOT NULL,
            preference_value longtext,
            preference_score decimal(10,2) DEFAULT '0.00',
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_preference (user_id,preference_key),
            KEY preference_key (preference_key),
            KEY preference_score (preference_score)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Trend analytics table
        $table_name = $wpdb->prefix . 'vortex_trend_analytics';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            trend_type varchar(64) NOT NULL,
            trend_key varchar(191) NOT NULL,
            trend_score decimal(10,2) DEFAULT '0.00',
            sample_size int(11) DEFAULT '0',
            start_date datetime DEFAULT NULL,
            end_date datetime DEFAULT NULL,
            trend_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY trend_unique_key (trend_type,trend_key,start_date,end_date),
            KEY trend_type (trend_type),
            KEY trend_key (trend_key),
            KEY trend_score (trend_score),
            KEY start_date (start_date),
            KEY end_date (end_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create artwork statistics table
     *
     * @since    1.0.0
     * @param    string    $charset_collate    Database charset and collation
     */
    private function create_artwork_statistics_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_artwork_statistics';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            artwork_id bigint(20) unsigned NOT NULL,
            views int(11) DEFAULT '0',
            likes int(11) DEFAULT '0',
            shares int(11) DEFAULT '0',
            comments int(11) DEFAULT '0',
            avg_view_time int(11) DEFAULT '0',
            bounce_rate decimal(5,2) DEFAULT '0.00',
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY artwork_id (artwork_id),
            KEY views (views),
            KEY likes (likes),
            KEY shares (shares),
            KEY comments (comments)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create Thorius tables
     *
     * @since    1.0.0
     * @param    string    $charset_collate    Database charset and collation
     */
    private function create_thorius_tables($charset_collate) {
        global $wpdb;
        
        // Thorius sessions table
        $table_name = $wpdb->prefix . 'vortex_thorius_sessions';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_activity datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            session_data longtext,
            ip_address varchar(40) DEFAULT NULL,
            user_agent text,
            PRIMARY KEY  (id),
            UNIQUE KEY session_id (session_id),
            KEY user_id (user_id),
            KEY created_at (created_at),
            KEY last_activity (last_activity)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Thorius interaction history table
        $table_name = $wpdb->prefix . 'vortex_thorius_interaction_history';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            message_type enum('user','assistant') NOT NULL,
            message_content longtext NOT NULL,
            message_embedding longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY message_type (message_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Thorius user context table
        $table_name = $wpdb->prefix . 'vortex_thorius_user_context';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            context_key varchar(191) NOT NULL,
            context_value longtext,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_context (user_id,context_key),
            KEY context_key (context_key),
            KEY last_updated (last_updated)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the migrations class
new Vortex_DB_Migrations(); 