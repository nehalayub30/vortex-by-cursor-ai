<?php
/**
 * Database setup class for Vortex AI Marketplace
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_DB_Setup {
    
    /**
     * Initialize the database tables
     */
    public static function init() {
        self::create_event_tables();
        self::create_offer_tables();
        self::create_collaboration_tables();
    }
    
    /**
     * Create event-related tables
     */
    private static function create_event_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Event registrations table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vortex_event_registrations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            registration_date datetime NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            payment_status varchar(20) NOT NULL DEFAULT 'pending',
            payment_amount decimal(10,2) NOT NULL,
            payment_currency varchar(10) NOT NULL DEFAULT 'TOLA',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event_id (event_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create offer-related tables
     */
    private static function create_offer_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Offer responses table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vortex_offer_responses (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            offer_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            response_date datetime NOT NULL,
            response_type varchar(20) NOT NULL,
            response_message text,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY offer_id (offer_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create collaboration-related tables
     */
    private static function create_collaboration_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Collaboration requests table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vortex_collaboration_requests (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            collaboration_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            request_date datetime NOT NULL,
            requested_role varchar(50) NOT NULL,
            request_status varchar(20) NOT NULL DEFAULT 'pending',
            request_message text,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY collaboration_id (collaboration_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Collaboration members table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vortex_collaboration_members (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            collaboration_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            role varchar(50) NOT NULL,
            join_date datetime NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY collaboration_id (collaboration_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Drop all custom tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'vortex_event_registrations',
            $wpdb->prefix . 'vortex_offer_responses',
            $wpdb->prefix . 'vortex_collaboration_requests',
            $wpdb->prefix . 'vortex_collaboration_members'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
} 