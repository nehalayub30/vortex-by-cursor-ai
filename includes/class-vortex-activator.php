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
     * Initialize database tables, settings, and initial content during plugin activation.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Check minimum requirements
        self::check_requirements();
        
        // Create database tables
        self::create_tables();
        
        // Create default options
        self::create_options();
        
        // Schedule recurring events
        self::schedule_events();
        
        // Create default content if needed
        self::create_initial_content();
        
        // Create sample swipeable items for collector-collector workplace
        self::create_sample_items();
        
        // Create rewrite rules and flush them
        self::create_rewrite_rules();
        
        // Set activation flag
        update_option( 'vortex_activated', 'yes' );
        update_option( 'vortex_activation_time', time() );
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
     * Set default plugin options.
     *
     * @since    1.0.0
     */
    private static function create_options() {
        // Set blockchain options if they don't exist
        if ( false === get_option( 'vortex_blockchain_network' ) ) {
            add_option( 'vortex_blockchain_network', 'solana' );
        }
        
        if ( false === get_option( 'vortex_solana_rpc_url' ) ) {
            add_option( 'vortex_solana_rpc_url', 'https://api.mainnet-beta.solana.com' );
        }
        
        if ( false === get_option( 'vortex_solana_network' ) ) {
            add_option( 'vortex_solana_network', 'mainnet-beta' );
        }
        
        if ( false === get_option( 'vortex_solana_decimals' ) ) {
            add_option( 'vortex_solana_decimals', 9 );
        }
        
        // Legacy options
        if ( false === get_option( 'vortex_web3_provider_url' ) ) {
            add_option( 'vortex_web3_provider_url', 'https://mainnet.infura.io/v3/your-project-id' );
        }
        
        // Marketplace options
        if ( false === get_option( 'vortex_marketplace_enabled' ) ) {
            add_option( 'vortex_marketplace_enabled', 'yes' );
        }
        
        if ( false === get_option( 'vortex_marketplace_currency' ) ) {
            add_option( 'vortex_marketplace_currency', 'SOL' );
        }
        
        if ( false === get_option( 'vortex_marketplace_commission' ) ) {
            add_option( 'vortex_marketplace_commission', 5 );
        }
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
     * Flush rewrite rules to make custom post types work.
     *
     * @since    1.0.0
     */
    private static function create_rewrite_rules() {
        flush_rewrite_rules();
    }

    /**
     * Check minimum requirements.
     *
     * @since    1.0.0
     */
    private static function check_requirements() {
        // Implementation of check_requirements method
    }

    /**
     * Schedule recurring events.
     *
     * @since    1.0.0
     */
    private static function schedule_events() {
        // Implementation of schedule_events method
    }

    /**
     * Create initial content if needed.
     *
     * @since    1.0.0
     */
    private static function create_initial_content() {
        // Implementation of create_initial_content method
    }

    /**
     * Create sample swipeable items
     */
    private static function create_sample_items() {
        // Check if items already exist
        $existing_items = get_posts(array(
            'post_type' => 'vortex_item',
            'post_status' => 'publish',
            'numberposts' => 1
        ));
        
        if (!empty($existing_items)) {
            return; // Sample items already exist
        }
        
        // Sample items data
        $sample_items = array(
            array(
                'title' => 'Abstract Digital Art Collection',
                'content' => 'A stunning collection of abstract digital art pieces created by leading AI artists. This collection explores the boundaries between human creativity and machine learning.',
                'category' => 'Digital Art'
            ),
            array(
                'title' => 'Physical Oil Painting - "Summer Breeze"',
                'content' => 'Original oil painting depicting a serene summer landscape. Created by emerging artist Maria Laurent, this piece captures the essence of warm summer afternoons.',
                'category' => 'Painting'
            ),
            array(
                'title' => 'Limited Edition Photography Print',
                'content' => 'Black and white urban photography capturing the architecture of modern cities. Limited edition of 50 prints, each signed and numbered by the photographer.',
                'category' => 'Photography'
            ),
            array(
                'title' => 'AI-Generated Portrait Series',
                'content' => 'A series of unique AI-generated portraits exploring human emotions and expressions. Each piece is a one-of-a-kind digital asset authenticated on the blockchain.',
                'category' => 'AI Art'
            ),
            array(
                'title' => 'Surrealist Sculpture Collection',
                'content' => 'Digital 3D models of surrealist sculptures inspired by the works of Salvador Dalí. These models can be printed or used in virtual reality environments.',
                'category' => '3D Models'
            )
        );
        
        // Register the item category taxonomy if it doesn't exist yet
        if (!taxonomy_exists('item_category')) {
            register_taxonomy('item_category', 'vortex_item', array(
                'hierarchical' => true,
                'public' => true,
                'show_admin_column' => true,
                'show_in_rest' => true
            ));
        }
        
        // Create sample items
        foreach ($sample_items as $item) {
            // Create item post
            $post_id = wp_insert_post(array(
                'post_title' => $item['title'],
                'post_content' => $item['content'],
                'post_status' => 'publish',
                'post_type' => 'vortex_item'
            ));
            
            if (!is_wp_error($post_id)) {
                // Create category if it doesn't exist
                $term = term_exists($item['category'], 'item_category');
                if (!$term) {
                    $term = wp_insert_term($item['category'], 'item_category');
                }
                
                // Assign category to item
                if (!is_wp_error($term)) {
                    wp_set_object_terms($post_id, $term['term_id'], 'item_category');
                }
            }
        }
    }
}
