<?php
namespace Vortex;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems
 */
class Vortex_Activator {

    /**
     * Activate the plugin.
     *
     * Set up the default options and any necessary database tables.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Set default options
        if ( ! get_option( 'vortex_api_endpoint' ) ) {
            update_option( 'vortex_api_endpoint', 'https://www.vortexartec.com/api/v1' );
        }

        // Flush rewrite rules
        flush_rewrite_rules();

        // Add activation timestamp
        update_option( 'vortex_ai_marketplace_activated', time() );

        // Create necessary directories if they don't exist
        self::create_directories();
    }

    /**
     * Create necessary directories.
     *
     * @since    1.0.0
     */
    private static function create_directories() {
        // Create the cache directory if it doesn't exist
        $cache_dir = WP_CONTENT_DIR . '/cache/vortex-ai-marketplace';
        if ( ! file_exists( $cache_dir ) ) {
            wp_mkdir_p( $cache_dir );
        }

        // Create an .htaccess file to protect the cache directory
        $htaccess_file = $cache_dir . '/.htaccess';
        if ( ! file_exists( $htaccess_file ) ) {
            $htaccess_content = "# Disable directory browsing\n";
            $htaccess_content .= "Options -Indexes\n\n";
            $htaccess_content .= "# Deny access to all files\n";
            $htaccess_content .= "<FilesMatch \".*\">\n";
            $htaccess_content .= "    Order Allow,Deny\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</FilesMatch>\n";

            file_put_contents( $htaccess_file, $htaccess_content );
        }
    }

    /**
     * Create necessary database tables during plugin activation.
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        self::create_database_tables();
        self::create_required_pages();
        self::set_user_roles();
        self::create_thorius_learning_tables();
    }

    /**
     * Create the plugin's database tables.
     *
     * @since    1.0.0
     */
    private static function create_database_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Metrics table
        $table_metrics = $wpdb->prefix . 'vortex_metrics';
        $sql_metrics = "CREATE TABLE $table_metrics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            metric_type varchar(50) NOT NULL,
            metric_name varchar(100) NOT NULL,
            metric_value float NOT NULL,
            entity_id bigint(20) NOT NULL,
            entity_type varchar(50) NOT NULL,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY metric_type (metric_type),
            KEY entity_id (entity_id),
            KEY entity_type (entity_type)
        ) $charset_collate;";
        dbDelta($sql_metrics);
        
        // Rankings table
        $table_rankings = $wpdb->prefix . 'vortex_rankings';
        $sql_rankings = "CREATE TABLE $table_rankings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ranking_type varchar(50) NOT NULL,
            entity_id bigint(20) NOT NULL,
            entity_type varchar(50) NOT NULL,
            rank int(11) NOT NULL,
            score float NOT NULL,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ranking_type (ranking_type),
            KEY entity_id (entity_id),
            KEY entity_type (entity_type)
        ) $charset_collate;";
        dbDelta($sql_rankings);
        
        // TOLA Points table
        $table_tola = $wpdb->prefix . 'vortex_tola_points';
        $sql_tola = "CREATE TABLE $table_tola (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            balance float NOT NULL DEFAULT 0,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_tola);
        
        // Transactions table
        $table_transactions = $wpdb->prefix . 'vortex_transactions';
        $sql_transactions = "CREATE TABLE $table_transactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            transaction_id varchar(100) NOT NULL,
            from_address varchar(255) NOT NULL,
            to_address varchar(255) NOT NULL,
            amount float NOT NULL,
            token_type varchar(20) DEFAULT 'TOLA',
            transaction_data text,
            status varchar(50) NOT NULL,
            blockchain_tx_hash varchar(100) DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY transaction_id (transaction_id),
            KEY from_address (from_address),
            KEY to_address (to_address),
            KEY token_type (token_type)
        ) $charset_collate;";
        dbDelta($sql_transactions);
        
        // Artwork ownership table
        $table_ownership = $wpdb->prefix . 'vortex_artwork_ownership';
        $sql_ownership = "CREATE TABLE $table_ownership (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            artwork_id bigint(20) NOT NULL,
            owner_id bigint(20) NOT NULL,
            token_id varchar(100),
            purchase_price float,
            purchase_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_current tinyint(1) NOT NULL DEFAULT 1,
            transaction_id varchar(100),
            PRIMARY KEY  (id),
            KEY artwork_id (artwork_id),
            KEY owner_id (owner_id),
            KEY is_current (is_current)
        ) $charset_collate;";
        dbDelta($sql_ownership);
    }

    /**
     * Create required pages if they don't exist.
     *
     * @since    1.0.0
     */
    private static function create_required_pages() {
        $pages = array(
            'marketplace' => array(
                'title' => 'AI Art Marketplace',
                'content' => '<!-- wp:shortcode -->[vortex_marketplace]<!-- /wp:shortcode -->',
            ),
            'huraii' => array(
                'title' => 'HURAII AI Creator',
                'content' => '<!-- wp:shortcode -->[vortex_huraii]<!-- /wp:shortcode -->',
            ),
            'artists' => array(
                'title' => 'VORTEX Artists',
                'content' => '<!-- wp:shortcode -->[vortex_artists]<!-- /wp:shortcode -->',
            ),
            'wallet' => array(
                'title' => 'TOLA Wallet',
                'content' => '<!-- wp:shortcode -->[vortex_tola_wallet]<!-- /wp:shortcode -->',
            ),
            'metrics' => array(
                'title' => 'Marketplace Metrics',
                'content' => '<!-- wp:shortcode -->[vortex_metrics]<!-- /wp:shortcode -->',
            ),
        );
        
        foreach ($pages as $slug => $page_data) {
            // Check if page exists
            $page_exists = get_page_by_path($slug);
            
            if (!$page_exists) {
                // Create page
                $page_id = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug,
                ));
                
                // Save page ID in options
                update_option('vortex_page_' . $slug, $page_id);
            }
        }
    }

    /**
     * Set up user roles and capabilities.
     *
     * @since    1.0.0
     */
    private static function set_user_roles() {
        // Add Artist role
        add_role('vortex_artist', 'VORTEX Artist', array(
            'read' => true,
            'upload_files' => true,
            'publish_posts' => true,
            'edit_posts' => true,
            'delete_posts' => true,
        ));
        
        // Add Collector role
        add_role('vortex_collector', 'VORTEX Collector', array(
            'read' => true,
        ));
    }

    /**
     * Create Thorius agent learning tables
     */
    private static function create_thorius_learning_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Thorius agents table
        $agents_table = $wpdb->prefix . 'vortex_thorius_agents';
        $sql_agents = "CREATE TABLE IF NOT EXISTS $agents_table (
            agent_id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_name varchar(100) NOT NULL,
            agent_type varchar(50) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            model_version varchar(50) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (agent_id),
            KEY status (status),
            KEY agent_type (agent_type)
        ) $charset_collate;";
        
        // Agent learning metrics table
        $metrics_table = $wpdb->prefix . 'vortex_agent_learning_metrics';
        $sql_metrics = "CREATE TABLE IF NOT EXISTS $metrics_table (
            metric_id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_id bigint(20) NOT NULL,
            accuracy decimal(5,2) DEFAULT NULL,
            learning_rate decimal(5,2) DEFAULT NULL,
            efficiency decimal(5,2) DEFAULT NULL,
            adaptation_id bigint(20) DEFAULT NULL,
            recorded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (metric_id),
            KEY agent_id (agent_id),
            KEY recorded_at (recorded_at)
        ) $charset_collate;";
        
        // Agent adaptations table
        $adaptations_table = $wpdb->prefix . 'vortex_agent_adaptations';
        $sql_adaptations = "CREATE TABLE IF NOT EXISTS $adaptations_table (
            adaptation_id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_id bigint(20) NOT NULL,
            adaptation_type varchar(100) NOT NULL,
            impact varchar(20) DEFAULT 'medium',
            parameters longtext DEFAULT NULL,
            adaptation_time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (adaptation_id),
            KEY agent_id (agent_id),
            KEY adaptation_time (adaptation_time)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_agents);
        dbDelta($sql_metrics);
        dbDelta($sql_adaptations);
    }
}
