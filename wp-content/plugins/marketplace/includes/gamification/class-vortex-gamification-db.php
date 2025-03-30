<?php
/**
 * VORTEX Gamification Database
 *
 * Handles database tables and schema for gamification features
 *
 * @package Vortex_AI_Marketplace
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * VORTEX Gamification Database Class
 */
class VORTEX_Gamification_DB {
    
    /**
     * Initialize database tables
     */
    public static function init() {
        self::create_tables();
    }
    
    /**
     * Create necessary database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // User achievements table
        $table_achievements = $wpdb->prefix . 'vortex_user_achievements';
        $sql_achievements = "CREATE TABLE IF NOT EXISTS $table_achievements (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            achievement_type varchar(50) NOT NULL,
            achievement_level int(11) NOT NULL DEFAULT 1,
            unlock_date datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY achievement_type (achievement_type)
        ) $charset_collate;";
        
        // User badges table
        $table_badges = $wpdb->prefix . 'vortex_user_badges';
        $sql_badges = "CREATE TABLE IF NOT EXISTS $table_badges (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            badge_id varchar(50) NOT NULL,
            award_date datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY badge_id (badge_id)
        ) $charset_collate;";
        
        // User points table
        $table_points = $wpdb->prefix . 'vortex_user_points';
        $sql_points = "CREATE TABLE IF NOT EXISTS $table_points (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            points int(11) NOT NULL,
            action_type varchar(50) NOT NULL,
            transaction_date datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action_type (action_type)
        ) $charset_collate;";
        
        // User activity table
        $table_activity = $wpdb->prefix . 'vortex_user_activity';
        $sql_activity = "CREATE TABLE IF NOT EXISTS $table_activity (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            activity_type varchar(50) NOT NULL,
            activity_data longtext DEFAULT NULL,
            activity_time datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY activity_type (activity_type),
            KEY activity_time (activity_time)
        ) $charset_collate;";
        
        // Artwork views table
        $table_views = $wpdb->prefix . 'vortex_artwork_views';
        $sql_views = "CREATE TABLE IF NOT EXISTS $table_views (
            view_id bigint(20) NOT NULL AUTO_INCREMENT,
            artwork_id bigint(20) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            view_duration int(11) DEFAULT 0,
            view_time datetime NOT NULL,
            source_search_id bigint(20) DEFAULT NULL,
            PRIMARY KEY (view_id),
            KEY artwork_id (artwork_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Artwork likes table
        $table_likes = $wpdb->prefix . 'vortex_artwork_likes';
        $sql_likes = "CREATE TABLE IF NOT EXISTS $table_likes (
            like_id bigint(20) NOT NULL AUTO_INCREMENT,
            artwork_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            like_time datetime NOT NULL,
            PRIMARY KEY (like_id),
            UNIQUE KEY artwork_user (artwork_id, user_id),
            KEY artwork_id (artwork_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // TOLA transactions table
        $table_tola = $wpdb->prefix . 'vortex_tola_transactions';
        $sql_tola = "CREATE TABLE IF NOT EXISTS $table_tola (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            amount decimal(18,8) NOT NULL,
            transaction_type varchar(50) NOT NULL,
            reference_id varchar(100) DEFAULT NULL,
            transaction_date datetime NOT NULL,
            blockchain_tx_id varchar(100) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY transaction_type (transaction_type),
            KEY blockchain_tx_id (blockchain_tx_id)
        ) $charset_collate;";
        
        // Token swaps table
        $table_swaps = $wpdb->prefix . 'vortex_token_swaps';
        $sql_swaps = "CREATE TABLE IF NOT EXISTS $table_swaps (
            swap_id bigint(20) NOT NULL AUTO_INCREMENT,
            from_token_id varchar(100) NOT NULL,
            to_token_id varchar(100) NOT NULL,
            artwork_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            swap_amount decimal(18,8) NOT NULL,
            swap_time datetime NOT NULL,
            blockchain_tx_id varchar(100) DEFAULT NULL,
            PRIMARY KEY (swap_id),
            KEY from_token_id (from_token_id),
            KEY to_token_id (to_token_id),
            KEY artwork_id (artwork_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // User notifications table
        $table_notifications = $wpdb->prefix . 'vortex_user_notifications';
        $sql_notifications = "CREATE TABLE IF NOT EXISTS $table_notifications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            notification_type varchar(50) NOT NULL,
            notification_data longtext NOT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY notification_type (notification_type),
            KEY is_read (is_read)
        ) $charset_collate;";
        
        // AI training logs table
        $table_ai_logs = $wpdb->prefix . 'vortex_ai_training_logs';
        $sql_ai_logs = "CREATE TABLE IF NOT EXISTS $table_ai_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            model_name varchar(50) NOT NULL,
            model_version varchar(20) NOT NULL,
            examples_processed int(11) NOT NULL,
            iterations int(11) NOT NULL,
            initial_loss float NOT NULL,
            final_loss float NOT NULL,
            accuracy_improvement float NOT NULL,
            training_time int(11) NOT NULL,
            PRIMARY KEY (id),
            KEY model_name (model_name),
            KEY training_time (training_time)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create tables
        dbDelta($sql_achievements);
        dbDelta($sql_badges);
        dbDelta($sql_points);
        dbDelta($sql_activity);
        dbDelta($sql_views);
        dbDelta($sql_likes);
        dbDelta($sql_tola);
        dbDelta($sql_swaps);
        dbDelta($sql_notifications);
        dbDelta($sql_ai_logs);
    }
    
    /**
     * Insert initial achievement and badge data
     */
    public static function insert_initial_data() {
        // This method would populate default values if needed
        // For now, we'll rely on the class definitions
    }
}

// Initialize database tables on plugin activation
register_activation_hook(VORTEX_PLUGIN_FILE, array('VORTEX_Gamification_DB', 'init')); 