<?php
/**
 * VORTEX DAO Activator
 *
 * Handles activation and database setup for DAO functionality
 *
 * @link       https://vortexmarketplace.io
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_DAO_Activator {
    
    /**
     * Run activation procedures
     */
    public static function activate() {
        self::create_tables();
        self::seed_default_configuration();
        self::register_capabilities();
    }
    
    /**
     * Create required database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Proposals table
        $table_proposals = $wpdb->prefix . 'vortex_dao_proposals';
        $sql_proposals = "CREATE TABLE $table_proposals (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            description longtext NOT NULL,
            proposal_type varchar(50) NOT NULL,
            action_data text NOT NULL,
            for_votes decimal(18,8) NOT NULL DEFAULT 0,
            against_votes decimal(18,8) NOT NULL DEFAULT 0,
            abstain_votes decimal(18,8) NOT NULL DEFAULT 0,
            total_votes decimal(18,8) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            voting_end_date datetime NOT NULL,
            executed_at datetime DEFAULT NULL,
            executed_by bigint(20) DEFAULT NULL,
            execution_data text DEFAULT NULL,
            execution_tx varchar(100) DEFAULT NULL,
            vetoed_by bigint(20) DEFAULT NULL,
            vetoed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY proposal_type (proposal_type),
            KEY created_at (created_at),
            KEY voting_end_date (voting_end_date)
        ) $charset_collate;";
        
        // Votes table
        $table_votes = $wpdb->prefix . 'vortex_dao_votes';
        $sql_votes = "CREATE TABLE $table_votes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            proposal_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            wallet_address varchar(64) NOT NULL,
            vote varchar(10) NOT NULL,
            vote_weight decimal(18,8) NOT NULL,
            vote_reason text DEFAULT NULL,
            transaction_signature varchar(100) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY proposal_id (proposal_id),
            KEY user_id (user_id),
            UNIQUE KEY proposal_user (proposal_id, user_id)
        ) $charset_collate;";
        
        // Governance logs table
        $table_logs = $wpdb->prefix . 'vortex_dao_governance_logs';
        $sql_logs = "CREATE TABLE IF NOT EXISTS $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            action_type varchar(50) NOT NULL,
            proposal_id bigint(20) DEFAULT NULL,
            user_id bigint(20) NOT NULL,
            wallet_address varchar(44) NOT NULL,
            action_data longtext NOT NULL,
            token_amount decimal(18,9) DEFAULT NULL,
            status varchar(20) NOT NULL,
            transaction_hash varchar(64) DEFAULT NULL,
            block_number bigint(20) DEFAULT NULL,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY action_type (action_type),
            KEY proposal_id (proposal_id),
            KEY user_id (user_id),
            KEY wallet_address (wallet_address),
            KEY status (status),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        // Grants table
        $table_grants = $wpdb->prefix . 'vortex_dao_grants';
        $sql_grants = "CREATE TABLE $table_grants (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            proposal_id bigint(20) NOT NULL,
            recipient varchar(64) NOT NULL,
            amount decimal(18,8) NOT NULL,
            purpose text NOT NULL,
            transaction_signature varchar(100) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            executed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY proposal_id (proposal_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Wallet addresses table
        $table_wallets = $wpdb->prefix . 'vortex_wallet_addresses';
        $sql_wallets = "CREATE TABLE $table_wallets (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            wallet_address varchar(64) NOT NULL,
            wallet_type varchar(20) NOT NULL DEFAULT 'solana',
            is_primary tinyint(1) NOT NULL DEFAULT 0,
            verified tinyint(1) NOT NULL DEFAULT 0,
            verification_signature varchar(100) DEFAULT NULL,
            token_balance decimal(18,8) NOT NULL DEFAULT 0,
            last_balance_update datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            UNIQUE KEY wallet_address (wallet_address)
        ) $charset_collate;";
        
        // Token transfers table
        $table_transfers = $wpdb->prefix . 'vortex_token_transfers';
        $sql_transfers = "CREATE TABLE IF NOT EXISTS $table_transfers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            from_address varchar(44) NOT NULL,
            to_address varchar(44) NOT NULL,
            amount decimal(18,9) NOT NULL,
            transaction_hash varchar(64) DEFAULT NULL,
            block_number bigint(20) DEFAULT NULL,
            transfer_type varchar(20) NOT NULL DEFAULT 'transfer',
            status varchar(20) NOT NULL DEFAULT 'completed',
            created_at datetime NOT NULL,
            metadata longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY from_address (from_address),
            KEY to_address (to_address),
            KEY transaction_hash (transaction_hash),
            KEY transfer_type (transfer_type),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Metrics history table
        $table_metrics = $wpdb->prefix . 'vortex_dao_metrics_history';
        $sql_metrics = "CREATE TABLE $table_metrics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            total_supply decimal(18,9) NOT NULL,
            circulating_supply decimal(18,9) NOT NULL,
            treasury_balance decimal(18,9) NOT NULL,
            total_holders int(11) NOT NULL,
            active_proposals int(11) NOT NULL,
            total_votes int(11) NOT NULL,
            voter_participation decimal(5,2) NOT NULL,
            average_vote_weight decimal(18,9) NOT NULL,
            token_price decimal(18,9) NOT NULL,
            market_cap decimal(18,9) NOT NULL,
            total_value_locked decimal(18,9) NOT NULL,
            governance_stats longtext NOT NULL,
            snapshot_type varchar(20) NOT NULL,
            recorded_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY snapshot_type (snapshot_type),
            KEY recorded_at (recorded_at)
        ) $charset_collate;";
        
        // Rewards table
        $table_rewards = $wpdb->prefix . 'vortex_dao_rewards';
        $sql_rewards = "CREATE TABLE $table_rewards (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            amount decimal(18,8) NOT NULL DEFAULT 0,
            reward_type varchar(50) NOT NULL,
            metadata text DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            claimed_at datetime DEFAULT NULL,
            wallet_address varchar(64) DEFAULT NULL,
            transaction_signature varchar(100) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY reward_type (reward_type)
        ) $charset_collate;";
        
        // Create the tables
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_proposals);
        dbDelta($sql_votes);
        dbDelta($sql_logs);
        dbDelta($sql_grants);
        dbDelta($sql_wallets);
        dbDelta($sql_transfers);
        dbDelta($sql_metrics);
        dbDelta($sql_rewards);
    }
    
    /**
     * Seed default configuration
     */
    private static function seed_default_configuration() {
        // Set default DAO configuration if not exists
        if (!get_option('vortex_dao_token_address')) {
            update_option('vortex_dao_token_address', 'H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky');
        }
        
        if (!get_option('vortex_dao_blockchain_network')) {
            update_option('vortex_dao_blockchain_network', 'solana');
        }
        
        if (!get_option('vortex_dao_token_name')) {
            update_option('vortex_dao_token_name', 'TOLA');
        }
        
        if (!get_option('vortex_dao_token_symbol')) {
            update_option('vortex_dao_token_symbol', 'TOLA');
        }
        
        // Royalty configuration
        if (!get_option('vortex_dao_artist_royalty_max')) {
            update_option('vortex_dao_artist_royalty_max', 15);
        }
        
        if (!get_option('vortex_dao_vortex_creator_royalty')) {
            update_option('vortex_dao_vortex_creator_royalty', 5);
        }
        
        if (!get_option('vortex_dao_royalty_cap')) {
            update_option('vortex_dao_royalty_cap', 20);
        }
        
        // Revenue configuration
        if (!get_option('vortex_dao_marketplace_commission')) {
            update_option('vortex_dao_marketplace_commission', 15);
        }
        
        if (!get_option('vortex_dao_dao_allocation')) {
            update_option('vortex_dao_dao_allocation', 3);
        }
        
        if (!get_option('vortex_dao_admin_allocation')) {
            update_option('vortex_dao_admin_allocation', 7);
        }
        
        if (!get_option('vortex_dao_creator_allocation')) {
            update_option('vortex_dao_creator_allocation', 5);
        }
        
        // Treasury allocation
        if (!get_option('vortex_dao_grants_allocation')) {
            update_option('vortex_dao_grants_allocation', 1);
        }
        
        if (!get_option('vortex_dao_exhibitions_allocation')) {
            update_option('vortex_dao_exhibitions_allocation', 1);
        }
        
        if (!get_option('vortex_dao_artist_support_allocation')) {
            update_option('vortex_dao_artist_support_allocation', 1);
        }
        
        // Governance configuration
        if (!get_option('vortex_dao_min_proposal_tokens')) {
            update_option('vortex_dao_min_proposal_tokens', 1000);
        }
        
        if (!get_option('vortex_dao_quorum_percentage')) {
            update_option('vortex_dao_quorum_percentage', 10);
        }
        
        if (!get_option('vortex_dao_proposal_threshold')) {
            update_option('vortex_dao_proposal_threshold', 51);
        }
        
        if (!get_option('vortex_dao_voting_period_days')) {
            update_option('vortex_dao_voting_period_days', 7);
        }
    }
    
    /**
     * Register user capabilities and roles
     */
    private static function register_capabilities() {
        // Add DAO-specific capabilities to administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_vortex_dao');
            $admin->add_cap('create_dao_proposals');
            $admin->add_cap('vote_dao_proposals');
            $admin->add_cap('execute_dao_proposals');
            $admin->add_cap('veto_dao_proposals');
            $admin->add_cap('view_dao_metrics');
        }
        
        // Maybe create a DAO Manager role
        $dao_manager = get_role('vortex_dao_manager');
        if (!$dao_manager) {
            add_role(
                'vortex_dao_manager',
                'VORTEX DAO Manager',
                array(
                    'read' => true,
                    'manage_vortex_dao' => true,
                    'create_dao_proposals' => true,
                    'vote_dao_proposals' => true,
                    'execute_dao_proposals' => true,
                    'view_dao_metrics' => true
                )
            );
        }
        
        // Initialize and register DAO roles
        $dao_roles = VORTEX_DAO_Roles::get_instance();
        $dao_roles->register_roles();
    }

    /**
     * Create token vesting table
     */
    private function create_token_vesting_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_token_vesting';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            beneficiary_address varchar(44) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            allocation_type varchar(50) NOT NULL,
            total_amount decimal(18,9) NOT NULL,
            released_amount decimal(18,9) NOT NULL DEFAULT 0,
            start_timestamp datetime NOT NULL,
            cliff_end_timestamp datetime DEFAULT NULL,
            end_timestamp datetime NOT NULL,
            release_interval varchar(20) NOT NULL DEFAULT 'monthly',
            release_percentage decimal(5,2) NOT NULL DEFAULT 0,
            revocable tinyint(1) NOT NULL DEFAULT 0,
            revoked_at datetime DEFAULT NULL,
            revoked_by bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_release_at datetime DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            PRIMARY KEY  (id),
            KEY beneficiary_address (beneficiary_address),
            KEY user_id (user_id),
            KEY allocation_type (allocation_type),
            KEY status (status),
            KEY start_timestamp (start_timestamp),
            KEY end_timestamp (end_timestamp)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create vesting schedule table
     */
    private function create_vesting_schedule_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_vesting_schedule';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            vesting_id bigint(20) NOT NULL,
            release_date datetime NOT NULL,
            release_amount decimal(18,9) NOT NULL,
            release_percentage decimal(5,2) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            released_at datetime DEFAULT NULL,
            transaction_hash varchar(64) DEFAULT NULL,
            block_number bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY vesting_id (vesting_id),
            KEY release_date (release_date),
            KEY status (status),
            CONSTRAINT fk_vesting_schedule_vesting 
                FOREIGN KEY (vesting_id) 
                REFERENCES {$wpdb->prefix}vortex_token_vesting (id) 
                ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create DAO metrics history table
     */
    private function create_dao_metrics_history_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_dao_metrics_history';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            total_supply decimal(18,9) NOT NULL,
            circulating_supply decimal(18,9) NOT NULL,
            treasury_balance decimal(18,9) NOT NULL,
            total_holders int(11) NOT NULL,
            active_proposals int(11) NOT NULL,
            total_votes int(11) NOT NULL,
            voter_participation decimal(5,2) NOT NULL,
            average_vote_weight decimal(18,9) NOT NULL,
            token_price decimal(18,9) NOT NULL,
            market_cap decimal(18,9) NOT NULL,
            total_value_locked decimal(18,9) NOT NULL,
            governance_stats longtext NOT NULL,
            snapshot_type varchar(20) NOT NULL,
            recorded_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY snapshot_type (snapshot_type),
            KEY recorded_at (recorded_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
} 