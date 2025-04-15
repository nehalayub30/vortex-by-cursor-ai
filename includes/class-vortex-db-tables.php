<?php
/**
 * VORTEX Database Tables
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class to handle database tables creation and updates
 */
class VORTEX_DB_Tables {
    
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * Get instance of this class.
     *
     * @return VORTEX_DB_Tables
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create all tables
     */
    public function create_all_tables() {
        $this->create_searches_table();
        $this->create_user_sessions_table();
        $this->create_user_geo_data_table();
        $this->create_user_demographics_table();
        $this->create_user_languages_table();
        $this->create_artwork_views_table();
        $this->create_art_styles_table();
        $this->create_cart_abandonment_feedback_table();
        $this->create_carts_table();
        $this->create_cart_items_table();
        $this->create_transactions_table();
        $this->create_tags_table();
        $this->create_artwork_tags_table();
        $this->create_artworks_table();
        $this->create_artwork_themes_table();
        $this->create_artwork_theme_mapping_table();
        $this->create_search_transactions_table();
        $this->create_search_artwork_clicks_table();
        $this->create_search_results_table();
        $this->create_social_hashtags_table();
        $this->create_hashtag_share_mapping_table();
    }
    
    /**
     * Create user sessions table
     */
    public function create_user_sessions_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_user_sessions';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(32) NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            start_time datetime NOT NULL,
            end_time datetime DEFAULT NULL,
            last_activity datetime DEFAULT NULL,
            activity_time datetime DEFAULT CURRENT_TIMESTAMP,
            duration int(11) DEFAULT 0,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            referrer varchar(255) DEFAULT NULL,
            entry_page varchar(255) DEFAULT NULL,
            exit_page varchar(255) DEFAULT NULL,
            page_views int(11) DEFAULT 0,
            device_type varchar(20) DEFAULT NULL,
            browser varchar(50) DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY session_id (session_id),
            KEY user_id (user_id),
            KEY active (active),
            KEY start_time (start_time),
            KEY activity_time (activity_time)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create user geographical data table
     */
    public function create_user_geo_data_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_user_geo_data';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            country_code varchar(2) DEFAULT NULL,
            country varchar(100) DEFAULT NULL,
            region varchar(100) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            postal_code varchar(20) DEFAULT NULL,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            timezone varchar(50) DEFAULT NULL,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            KEY country_code (country_code),
            KEY region (region(20)),
            KEY city (city(20))
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create user demographics table
     */
    public function create_user_demographics_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_user_demographics';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            gender varchar(20) DEFAULT NULL,
            age_group varchar(20) DEFAULT NULL,
            income_range varchar(20) DEFAULT NULL,
            education_level varchar(50) DEFAULT NULL,
            occupation varchar(100) DEFAULT NULL,
            interests text DEFAULT NULL,
            self_disclosed tinyint(1) DEFAULT 0,
            ai_predicted tinyint(1) DEFAULT 0,
            confidence_score decimal(4,3) DEFAULT 0.000,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            KEY gender (gender),
            KEY age_group (age_group),
            KEY income_range (income_range)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create user languages table
     */
    public function create_user_languages_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_user_languages';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            language_code varchar(10) NOT NULL,
            language_name varchar(50) NOT NULL,
            proficiency varchar(20) DEFAULT 'native',
            is_primary tinyint(1) DEFAULT 0,
            source varchar(20) DEFAULT 'browser',
            last_updated datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_language (user_id, language_code),
            KEY language_code (language_code),
            KEY is_primary (is_primary)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create artwork views table
     */
    public function create_artwork_views_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artwork_views';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            artwork_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(32) DEFAULT NULL,
            view_time datetime DEFAULT CURRENT_TIMESTAMP,
            view_duration int(11) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            referrer varchar(255) DEFAULT NULL,
            is_unique tinyint(1) DEFAULT 1,
            platform varchar(20) DEFAULT 'web',
            device_type varchar(20) DEFAULT NULL,
            engagement_score decimal(4,2) DEFAULT 0.00,
            PRIMARY KEY  (id),
            KEY artwork_id (artwork_id),
            KEY user_id (user_id),
            KEY view_time (view_time),
            KEY is_unique (is_unique)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create art styles table
     */
    public function create_art_styles_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_art_styles';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            style_name varchar(100) NOT NULL,
            style_slug varchar(100) NOT NULL,
            style_description text DEFAULT NULL,
            parent_style_id bigint(20) unsigned DEFAULT NULL,
            visual_characteristics text DEFAULT NULL,
            historical_period varchar(100) DEFAULT NULL,
            origin_region varchar(100) DEFAULT NULL,
            creation_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            popularity_score decimal(5,2) DEFAULT 0.00,
            trend_score decimal(5,2) DEFAULT 0.00,
            artwork_count int(11) DEFAULT 0,
            is_featured tinyint(1) DEFAULT 0,
            is_ai_generated tinyint(1) DEFAULT 0,
            thumbnail_url varchar(255) DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY style_slug (style_slug),
            KEY parent_style_id (parent_style_id),
            KEY popularity_score (popularity_score),
            KEY trend_score (trend_score),
            KEY is_featured (is_featured),
            KEY is_ai_generated (is_ai_generated)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create cart abandonment feedback table
     */
    public function create_cart_abandonment_feedback_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_cart_abandonment_feedback';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cart_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(32) DEFAULT NULL,
            abandonment_time datetime DEFAULT CURRENT_TIMESTAMP,
            reason_category varchar(50) DEFAULT NULL,
            abandonment_reason varchar(50) DEFAULT NULL,
            reason_details text DEFAULT NULL,
            feedback_time datetime DEFAULT NULL,
            feedback_provided tinyint(1) DEFAULT 0,
            items_in_cart int(11) DEFAULT 0,
            cart_value decimal(10,2) DEFAULT 0.00,
            resolved tinyint(1) DEFAULT 0,
            resolution_notes text DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY cart_id (cart_id),
            KEY user_id (user_id),
            KEY abandonment_time (abandonment_time),
            KEY reason_category (reason_category),
            KEY abandonment_reason (abandonment_reason),
            KEY feedback_provided (feedback_provided)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create searches table
     */
    public function create_searches_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_searches';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(32) DEFAULT NULL,
            search_query varchar(255) NOT NULL,
            search_time datetime DEFAULT CURRENT_TIMESTAMP,
            results_count int(11) DEFAULT 0,
            result_clicked tinyint(1) DEFAULT 0,
            clicked_position int(11) DEFAULT NULL,
            clicked_result_id bigint(20) unsigned DEFAULT NULL,
            search_filters text DEFAULT NULL,
            search_category varchar(50) DEFAULT NULL,
            search_location varchar(100) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            search_page varchar(100) DEFAULT 'main',
            conversion tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY search_time (search_time),
            KEY search_query (search_query(191)),
            KEY search_category (search_category),
            KEY result_clicked (result_clicked),
            KEY conversion (conversion)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create carts table
     */
    public function create_carts_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_carts';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(32) DEFAULT NULL,
            cart_token varchar(64) NOT NULL,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            cart_status varchar(20) DEFAULT 'active',
            cart_total decimal(10,2) DEFAULT 0.00,
            items_count int(11) DEFAULT 0,
            currency varchar(3) DEFAULT 'USD',
            converted_to_order tinyint(1) DEFAULT 0,
            order_id bigint(20) unsigned DEFAULT NULL,
            abandoned tinyint(1) DEFAULT 0,
            abandoned_time datetime DEFAULT NULL,
            recovery_email_sent tinyint(1) DEFAULT 0,
            recovery_email_time datetime DEFAULT NULL,
            recovered tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY cart_token (cart_token),
            KEY user_id (user_id),
            KEY cart_status (cart_status),
            KEY created (created),
            KEY last_updated (last_updated),
            KEY abandoned (abandoned),
            KEY recovered (recovered)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create artworks table
     */
    public function create_artworks_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artworks';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            artwork_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            artist_id bigint(20) UNSIGNED NOT NULL,
            style_id bigint(20) UNSIGNED DEFAULT NULL,
            token_id varchar(255) DEFAULT NULL,
            contract_address varchar(255) DEFAULT NULL,
            blockchain varchar(50) DEFAULT NULL,
            mint_date datetime DEFAULT NULL,
            mint_hash varchar(255) DEFAULT NULL,
            token_uri text DEFAULT NULL,
            ai_model varchar(100) DEFAULT NULL,
            ai_prompt text DEFAULT NULL,
            ai_settings text DEFAULT NULL,
            edition_number int(11) UNSIGNED DEFAULT 1,
            edition_total int(11) UNSIGNED DEFAULT 1,
            is_minted tinyint(1) UNSIGNED DEFAULT 0,
            price decimal(20,8) UNSIGNED DEFAULT 0.00000000,
            status varchar(50) DEFAULT 'draft',
            creation_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (artwork_id),
            UNIQUE KEY post_id (post_id),
            KEY artist_id (artist_id),
            KEY style_id (style_id),
            KEY blockchain_token (blockchain, token_id, contract_address),
            KEY status (status),
            KEY ai_model (ai_model),
            KEY price (price),
            KEY creation_date (creation_date)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create transactions table
     */
    public function create_transactions_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            transaction_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned DEFAULT NULL,
            artwork_id bigint(20) unsigned DEFAULT NULL,
            buyer_id bigint(20) unsigned DEFAULT NULL,
            seller_id bigint(20) unsigned DEFAULT NULL,
            amount decimal(20,8) unsigned NOT NULL DEFAULT 0.00000000,
            transaction_fee decimal(20,8) unsigned DEFAULT 0.00000000,
            currency varchar(20) NOT NULL DEFAULT 'TOLA',
            transaction_hash varchar(255) DEFAULT NULL,
            blockchain varchar(50) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            payment_method varchar(50) DEFAULT NULL,
            transaction_type varchar(50) NOT NULL DEFAULT 'purchase',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            notes text DEFAULT NULL,
            metadata text DEFAULT NULL,
            PRIMARY KEY (transaction_id),
            KEY order_id (order_id),
            KEY artwork_id (artwork_id),
            KEY buyer_id (buyer_id),
            KEY seller_id (seller_id),
            KEY status (status),
            KEY transaction_type (transaction_type),
            KEY created_at (created_at),
            KEY transaction_hash (transaction_hash)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create tags table
     */
    public function create_tags_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_tags';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            tag_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tag_name varchar(100) NOT NULL,
            tag_slug varchar(100) NOT NULL,
            tag_description text DEFAULT NULL,
            parent_tag_id bigint(20) unsigned DEFAULT NULL,
            tag_type varchar(50) DEFAULT 'general',
            count int(11) DEFAULT 0,
            popularity_score decimal(10,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (tag_id),
            UNIQUE KEY tag_slug (tag_slug),
            KEY parent_tag_id (parent_tag_id),
            KEY tag_type (tag_type),
            KEY popularity_score (popularity_score),
            KEY count (count)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create artwork tags table
     */
    public function create_artwork_tags_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artwork_tags';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            artwork_id bigint(20) unsigned NOT NULL,
            tag_id bigint(20) unsigned NOT NULL,
            confidence decimal(5,2) DEFAULT 1.00,
            added_by bigint(20) unsigned DEFAULT NULL,
            is_auto_generated tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY artwork_tag (artwork_id, tag_id),
            KEY artwork_id (artwork_id),
            KEY tag_id (tag_id),
            KEY confidence (confidence),
            KEY is_auto_generated (is_auto_generated)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create artwork theme mapping table
     */
    public function create_artwork_theme_mapping_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artwork_theme_mapping';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            artwork_id bigint(20) unsigned NOT NULL,
            theme_id bigint(20) unsigned NOT NULL,
            relevance decimal(5,2) DEFAULT 1.00,
            added_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY artwork_theme (artwork_id, theme_id),
            KEY artwork_id (artwork_id),
            KEY theme_id (theme_id),
            KEY relevance (relevance)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create artwork themes table
     */
    public function create_artwork_themes_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artwork_themes';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            theme_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            theme_name varchar(191) NOT NULL,
            theme_slug varchar(191) NOT NULL,
            theme_description text,
            parent_id bigint(20) unsigned DEFAULT NULL,
            popularity_score decimal(10,2) DEFAULT '0.00',
            trending_score decimal(10,2) DEFAULT '0.00',
            creation_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            artwork_count int(11) DEFAULT '0',
            is_featured tinyint(1) DEFAULT '0',
            PRIMARY KEY  (theme_id),
            UNIQUE KEY theme_slug (theme_slug),
            KEY parent_id (parent_id),
            KEY popularity_score (popularity_score),
            KEY trending_score (trending_score),
            KEY is_featured (is_featured)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create cart items table
     */
    public function create_cart_items_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_cart_items';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cart_id bigint(20) unsigned NOT NULL,
            artwork_id bigint(20) unsigned NOT NULL,
            quantity int(11) unsigned NOT NULL DEFAULT 1,
            price decimal(20,8) unsigned NOT NULL DEFAULT 0.00000000,
            variation_id bigint(20) unsigned DEFAULT NULL,
            variation_data text DEFAULT NULL,
            added_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            custom_options text DEFAULT NULL,
            metadata text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY cart_id (cart_id),
            KEY artwork_id (artwork_id),
            KEY variation_id (variation_id)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create search transactions table
     */
    public function create_search_transactions_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_search_transactions';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            search_id bigint(20) unsigned NOT NULL,
            transaction_id bigint(20) unsigned NOT NULL,
            relation_type varchar(50) DEFAULT 'direct',
            time_between_search_transaction int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            metadata text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY search_transaction (search_id, transaction_id),
            KEY search_id (search_id),
            KEY transaction_id (transaction_id),
            KEY relation_type (relation_type)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create search artwork clicks table
     */
    public function create_search_artwork_clicks_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_search_artwork_clicks';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            search_id bigint(20) unsigned NOT NULL,
            artwork_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(32) DEFAULT NULL,
            click_time datetime DEFAULT CURRENT_TIMESTAMP,
            click_position int(11) DEFAULT NULL,
            search_page varchar(100) DEFAULT 'main',
            result_type varchar(50) DEFAULT 'search',
            time_spent_viewing int(11) DEFAULT NULL,
            converted tinyint(1) DEFAULT 0,
            conversion_type varchar(50) DEFAULT NULL,
            conversion_value decimal(10,2) DEFAULT 0.00,
            conversion_time datetime DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY search_id (search_id),
            KEY artwork_id (artwork_id),
            KEY user_id (user_id),
            KEY click_time (click_time),
            KEY converted (converted),
            KEY click_position (click_position)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create search results table
     */
    public function create_search_results_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_search_results';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            search_id bigint(20) unsigned NOT NULL,
            result_type varchar(50) NOT NULL DEFAULT 'artwork',
            result_id bigint(20) unsigned NOT NULL,
            relevance_score decimal(5,2) DEFAULT 1.00,
            display_position int(11) DEFAULT NULL,
            style_id bigint(20) unsigned DEFAULT NULL,
            theme_id bigint(20) unsigned DEFAULT NULL,
            was_clicked tinyint(1) DEFAULT 0,
            click_position int(11) DEFAULT NULL,
            click_time datetime DEFAULT NULL,
            impression_time datetime DEFAULT CURRENT_TIMESTAMP,
            metadata text DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY search_id (search_id),
            KEY result_type (result_type),
            KEY result_id (result_id),
            KEY style_id (style_id),
            KEY theme_id (theme_id),
            KEY was_clicked (was_clicked),
            KEY impression_time (impression_time)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create social hashtags table
     */
    public function create_social_hashtags_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_social_hashtags';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            hashtag_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            hashtag varchar(255) NOT NULL,
            category varchar(50) DEFAULT 'general',
            description text DEFAULT NULL,
            usage_count int(11) DEFAULT 0,
            engagement_score decimal(5,2) DEFAULT 0.00,
            first_used datetime DEFAULT CURRENT_TIMESTAMP,
            last_used datetime DEFAULT CURRENT_TIMESTAMP,
            is_trending tinyint(1) DEFAULT 0,
            is_featured tinyint(1) DEFAULT 0,
            is_blocked tinyint(1) DEFAULT 0,
            relevance_score decimal(5,2) DEFAULT 0.00,
            created_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY  (hashtag_id),
            UNIQUE KEY hashtag (hashtag(191)),
            KEY category (category),
            KEY usage_count (usage_count),
            KEY engagement_score (engagement_score),
            KEY is_trending (is_trending),
            KEY is_featured (is_featured),
            KEY is_blocked (is_blocked)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Create hashtag share mapping table
     */
    public function create_hashtag_share_mapping_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_hashtag_share_mapping';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            hashtag_id bigint(20) unsigned NOT NULL,
            share_id bigint(20) unsigned NOT NULL,
            artwork_id bigint(20) unsigned DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY hashtag_share (hashtag_id, share_id),
            KEY hashtag_id (hashtag_id),
            KEY share_id (share_id),
            KEY artwork_id (artwork_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        $this->execute_sql($sql);
    }
    
    /**
     * Execute SQL with proper error handling
     * 
     * @param string $sql SQL statement to execute
     * @return bool Success or failure
     */
    private function execute_sql($sql) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Use try-catch to prevent failed queries from breaking the page
        try {
            dbDelta($sql);
            return true;
        } catch (Exception $e) {
            error_log('VORTEX DB Tables Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Repair the vortex_artworks table to ensure it has style_id column
     * 
     * This can be called directly from admin screens to fix database issues
     * 
     * @return bool Success or failure
     */
    public function repair_artworks_table() {
        global $wpdb;
        $artworks_table = $wpdb->prefix . 'vortex_artworks';
        
        // Check if the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$artworks_table'") !== $artworks_table) {
            // Create the table if it doesn't exist
            $this->create_artworks_table();
            return true;
        }
        
        // Check if style_id column exists
        $column_exists = false;
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $artworks_table");
        foreach ($columns as $column) {
            if ($column->Field === 'style_id') {
                $column_exists = true;
                break;
            }
        }
        
        // Add the column if it doesn't exist
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE $artworks_table ADD COLUMN style_id bigint(20) unsigned DEFAULT NULL AFTER artist_id");
            $wpdb->query("ALTER TABLE $artworks_table ADD INDEX style_id (style_id)");
            
            // Trigger the population of the style_id values
            if (class_exists('Vortex_DB_Migrations')) {
                Vortex_DB_Migrations::populate_artwork_style_ids();
            }
            
            return true;
        }
        
        return true;
    }
} 