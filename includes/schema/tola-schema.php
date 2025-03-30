<?php
/**
 * TOLA token database schema for VORTEX AI Marketplace
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/schema
 */

/**
 * Create TOLA token database tables for the VORTEX AI Marketplace.
 *
 * @since    1.0.0
 * @return   array    Array of success/error messages.
 */
function vortex_create_tola_schema() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $results = array();

    // Array of SQL statements to create the token-related tables
    $sql = array();

    // Wallets table
    $table_name = $wpdb->prefix . 'vortex_wallets';
    $sql[] = "CREATE TABLE $table_name (
        wallet_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        address varchar(255) NOT NULL,
        blockchain_type varchar(50) DEFAULT 'solana',
        is_primary tinyint(1) DEFAULT 0,
        is_verified tinyint(1) DEFAULT 0,
        verification_date datetime,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        date_modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (wallet_id),
        KEY user_id (user_id),
        KEY address (address),
        KEY blockchain_type (blockchain_type)
    ) $charset_collate;";

    // TOLA token transactions
    $table_name = $wpdb->prefix . 'vortex_tola_transactions';
    $sql[] = "CREATE TABLE $table_name (
        transaction_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        wallet_address varchar(255) NOT NULL,
        transaction_hash varchar(255),
        transaction_type varchar(50) NOT NULL,
        amount decimal(18,8) NOT NULL,
        status varchar(50) DEFAULT 'pending',
        related_entity_type varchar(50),
        related_entity_id bigint(20) UNSIGNED,
        blockchain_confirmations int DEFAULT 0,
        transaction_fee decimal(18,8) DEFAULT 0,
        notes text,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        date_modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (transaction_id),
        KEY wallet_address (wallet_address),
        KEY transaction_hash (transaction_hash),
        KEY transaction_type (transaction_type),
        KEY status (status),
        KEY date_created (date_created)
    ) $charset_collate;";

    // TOLA token balances
    $table_name = $wpdb->prefix . 'vortex_tola_balances';
    $sql[] = "CREATE TABLE $table_name (
        balance_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        wallet_address varchar(255) NOT NULL,
        user_id bigint(20) UNSIGNED,
        balance decimal(18,8) DEFAULT 0,
        staked_balance decimal(18,8) DEFAULT 0,
        last_updated datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (balance_id),
        UNIQUE KEY wallet_address (wallet_address),
        KEY user_id (user_id)
    ) $charset_collate;";

    // NFT minting logs
    $table_name = $wpdb->prefix . 'vortex_nft_minting';
    $sql[] = "CREATE TABLE $table_name (
        mint_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        artwork_id bigint(20) UNSIGNED NOT NULL,
        artist_id bigint(20) UNSIGNED NOT NULL,
        token_id varchar(255),
        token_uri varchar(255),
        metadata_uri varchar(255),
        contract_address varchar(255),
        blockchain_type varchar(50) DEFAULT 'solana',
        transaction_hash varchar(255),
        status varchar(50) DEFAULT 'pending',
        mint_date datetime,
        royalty_percentage decimal(5,2) DEFAULT 0,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        date_modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (mint_id),
        KEY artwork_id (artwork_id),
        KEY artist_id (artist_id),
        KEY token_id (token_id),
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
 * Setup initial data for TOLA token tables
 *
 * @since    1.0.0
 */
function vortex_setup_tola_initial_data() {
    global $wpdb;
    
    // Add default TOLA token settings to the settings table
    $settings_table = $wpdb->prefix . 'vortex_settings';
    
    $default_settings = array(
        array(
            'setting_name' => 'tola_contract_address',
            'setting_value' => '4ToLaDzTx6NdskNhiReRpaXGYVVVjpG5fAHC9kajrEv4',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'tola_blockchain_network',
            'setting_value' => 'solana',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'tola_nft_contract_address',
            'setting_value' => '8UNMsq7LhfxSMkHkVD8CWBKAeURBdPAVgwufnuqedfBz',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'tola_default_royalty',
            'setting_value' => '10',
            'autoload' => 1
        ),
        array(
            'setting_name' => 'tola_token_decimals',
            'setting_value' => '9',
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