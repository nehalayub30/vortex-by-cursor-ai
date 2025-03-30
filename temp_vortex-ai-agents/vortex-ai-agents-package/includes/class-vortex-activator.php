<?php
namespace Vortex\AI;

class VortexActivator {
    public static function activate() {
        // Create necessary database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create required directories
        self::create_directories();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    private static function create_tables() {
        global \;
        \ = \->get_charset_collate();

        \ = [
            "CREATE TABLE IF NOT EXISTS {\->prefix}vortex_transactions (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                transaction_type varchar(50) NOT NULL,
                amount decimal(10,2) NOT NULL,
                status varchar(20) NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) \;",
            
            "CREATE TABLE IF NOT EXISTS {\->prefix}vortex_nfts (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                owner_id bigint(20) NOT NULL,
                contract_address varchar(42) NOT NULL,
                token_id varchar(78) NOT NULL,
                metadata text NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) \;"
        ];

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach(\ as \) {
            dbDelta(\);
        }
    }

    private static function set_default_options() {
        add_option('vortex_version', VORTEX_VERSION);
        add_option('vortex_blockchain_network', 'ethereum');
        add_option('vortex_marketplace_fee', '2.5');
    }

    private static function create_directories() {
        \ = wp_upload_dir();
        \ = \['basedir'] . '/vortex-ai';
        
        if (!file_exists(\)) {
            wp_mkdir_p(\);
            wp_mkdir_p(\ . '/temp');
            wp_mkdir_p(\ . '/nft-metadata');
        }
    }
}
