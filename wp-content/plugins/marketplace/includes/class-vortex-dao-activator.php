<?php
/**
 * VORTEX DAO Activator
 * 
 * Handles activation setup for the DAO functionality.
 *
 * @package VORTEX
 */

class VORTEX_DAO_Activator {
    /**
     * Run activation procedures.
     */
    public static function activate() {
        self::create_tables();
        self::seed_default_config();
        self::register_capabilities();
        
        // Set activation flag
        update_option('vortex_dao_activated', true);
        update_option('vortex_dao_version', '1.0.0');
        update_option('vortex_dao_start_date', current_time('mysql'));
        
        // Log the activation
        self::log_activation();
    }
    
    /**
     * Create database tables.
     */
    private static function create_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Investors table
        $table_investors = $wpdb->prefix . 'vortex_dao_investors';
        $sql_investors = "CREATE TABLE $table_investors (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            wallet_address varchar(42) NOT NULL,
            investment_amount decimal(15,2) NOT NULL DEFAULT 0,
            token_amount bigint(20) NOT NULL DEFAULT 0,
            investment_date datetime NOT NULL,
            vesting_start_date datetime NOT NULL,
            vesting_end_date datetime NOT NULL,
            pro_rata_rights tinyint(1) NOT NULL DEFAULT 0,
            anti_dilution_protection tinyint(1) NOT NULL DEFAULT 0,
            liquidation_preference decimal(5,2) NOT NULL DEFAULT 1.00,
            blockchain_tx varchar(66) DEFAULT NULL,
            agreement_signature text DEFAULT NULL,
            agreement_signed_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY wallet_address (wallet_address)
        ) $charset_collate;";
        
        // Tokens table
        $table_tokens = $wpdb->prefix . 'vortex_dao_tokens';
        $sql_tokens = "CREATE TABLE $table_tokens (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            token_type enum('equity', 'utility') NOT NULL,
            wallet_address varchar(42) NOT NULL,
            balance bigint(20) NOT NULL DEFAULT 0,
            locked_balance bigint(20) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY wallet_token (wallet_address, token_type),
            KEY token_type (token_type)
        ) $charset_collate;";
        
        // Proposals table
        $table_proposals = $wpdb->prefix . 'vortex_dao_proposals';
        $sql_proposals = "CREATE TABLE $table_proposals (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            action varchar(50) NOT NULL,
            action_params text DEFAULT NULL,
            created_by bigint(20) NOT NULL,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            status enum('draft', 'active', 'approved', 'rejected', 'executed', 'cancelled') NOT NULL DEFAULT 'draft',
            votes_for bigint(20) NOT NULL DEFAULT 0,
            votes_against bigint(20) NOT NULL DEFAULT 0,
            votes_abstain bigint(20) NOT NULL DEFAULT 0,
            execution_date datetime DEFAULT NULL,
            blockchain_tx varchar(66) DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        // Votes table
        $table_votes = $wpdb->prefix . 'vortex_dao_votes';
        $sql_votes = "CREATE TABLE $table_votes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            proposal_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            vote_choice enum('for', 'against', 'abstain') NOT NULL,
            vote_power bigint(20) NOT NULL DEFAULT 0,
            wallet_address varchar(42) NOT NULL,
            signature text DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY proposal_user (proposal_id, user_id),
            KEY proposal_id (proposal_id),
            KEY user_id (user_id),
            KEY wallet_address (wallet_address)
        ) $charset_collate;";
        
        // Distributions table
        $table_distributions = $wpdb->prefix . 'vortex_dao_distributions';
        $sql_distributions = "CREATE TABLE $table_distributions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            distribution_type enum('revenue', 'dividend', 'grant') NOT NULL,
            total_amount decimal(15,2) NOT NULL DEFAULT 0,
            description text DEFAULT NULL,
            created_by bigint(20) NOT NULL,
            blockchain_tx varchar(66) DEFAULT NULL,
            status enum('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY distribution_type (distribution_type),
            KEY status (status),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        // Claims table
        $table_claims = $wpdb->prefix . 'vortex_dao_claims';
        $sql_claims = "CREATE TABLE $table_claims (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            distribution_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            amount decimal(15,2) NOT NULL DEFAULT 0,
            claimed tinyint(1) NOT NULL DEFAULT 0,
            claim_date datetime DEFAULT NULL,
            transaction_hash varchar(66) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY distribution_id (distribution_id),
            KEY user_id (user_id),
            KEY claimed (claimed)
        ) $charset_collate;";
        
        // Security logs table
        $table_security_logs = $wpdb->prefix . 'vortex_dao_security_logs';
        $sql_security_logs = "CREATE TABLE $table_security_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            message text NOT NULL,
            data text DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Execute table creation
        dbDelta($sql_investors);
        dbDelta($sql_tokens);
        dbDelta($sql_proposals);
        dbDelta($sql_votes);
        dbDelta($sql_distributions);
        dbDelta($sql_claims);
        dbDelta($sql_security_logs);
    }
    
    /**
     * Seed default configuration.
     */
    private static function seed_default_config() {
        $default_config = [
            // Founder and ownership structure
            'founder_address' => '',
            'treasury_address' => '',
            'equity_token_address' => '',
            'utility_token_address' => '',
            
            // Token distribution (10 million tokens total supply)
            'founder_allocation' => 6500000,    // 65% for founder (protected)
            'investor_allocation' => 1500000,   // 15% for $1.75M investment
            'team_allocation' => 1000000,       // 10% for team (vesting)
            'reserve_allocation' => 1000000,    // 10% for future raises/incentives
            
            // Valuation and investment terms
            'min_investment' => 250000,         // $250K minimum investment
            'token_price' => 1.17,              // $1.17 per token ($11.7M valuation)
            'investment_round_cap' => 1750000,  // $1.75M round cap
            
            // Governance parameters
            'founder_vote_multiplier' => 10,    // 10x voting power for founder
            'investor_vote_multiplier' => 1,    // 1x voting power for investors
            'team_vote_multiplier' => 2,        // 2x voting power for team
            'governance_phase' => 'founder',    // founder, transition, decentralized
            'governance_threshold' => 5100000,  // 51% threshold for proposals
            'founder_veto_enabled' => true,     // Founder has veto power
            
            // Vesting schedules
            'founder_vesting_months' => 36,     // 3-year vesting for founder
            'founder_cliff_months' => 6,        // 6-month cliff for founder
            'investor_vesting_months' => 24,    // 2-year vesting for investors
            'investor_cliff_months' => 3,       // 3-month cliff for investors
            'team_vesting_months' => 36,        // 3-year vesting for team
            'team_cliff_months' => 6,           // 6-month cliff for team
            
            // Investment milestone structure
            'investment_tranches' => [
                [
                    'amount' => 750000,         // $750K first tranche
                    'equity' => 600000,         // 6% of tokens (600K tokens)
                    'milestone' => 'Initial investment',
                    'status' => 'pending'
                ],
                [
                    'amount' => 500000,         // $500K second tranche
                    'equity' => 450000,         // 4.5% of tokens (450K tokens)
                    'milestone' => 'Beta marketplace launch with 50+ artists',
                    'status' => 'pending'
                ],
                [
                    'amount' => 500000,         // $500K third tranche
                    'equity' => 450000,         // 4.5% of tokens (450K tokens)
                    'milestone' => 'Full platform launch with TOLA blockchain integration',
                    'status' => 'pending'
                ]
            ],
            
            // Anti-dilution protection
            'anti_dilution_protection' => true, // Weighted-average anti-dilution protection
            'investor_pro_rata_rights' => true, // Investors can maintain ownership % in future rounds
            
            // Liquidity preferences
            'liquidation_preference' => 1.5,    // 1.5x liquidation preference
            'liquidation_priority' => 'investors_first', // Investors get paid first
            
            // Revenue allocation 
            'revenue_operations_allocation' => 40,
            'revenue_investor_allocation' => 30,
            'revenue_creator_grant_allocation' => 20,
            'revenue_reserve_allocation' => 10,
            
            // Other fee configurations
            'swap_fee' => 6,
            'sale_fee' => 89,
            'marketplace_commission' => 15,
            'subscription_fee' => 50,
            'exhibition_ticket' => 45,
            'blockchain_network' => 'polygon',
        ];
        
        update_option('vortex_dao_config', $default_config);
    }
    
    /**
     * Register capabilities.
     */
    private static function register_capabilities() {
        // Get administrator role
        $admin_role = get_role('administrator');
        
        if ($admin_role) {
            // Add DAO management capabilities
            $admin_role->add_cap('manage_vortex_dao');
            $admin_role->add_cap('manage_vortex_investments');
            $admin_role->add_cap('view_vortex_security');
            $admin_role->add_cap('manage_vortex_proposals');
            $admin_role->add_cap('manage_vortex_distributions');
        }
        
        // Create a custom role for DAO Managers
        if (!get_role('vortex_dao_manager')) {
            add_role(
                'vortex_dao_manager',
                __('VORTEX DAO Manager', 'vortex'),
                [
                    'read' => true,
                    'manage_vortex_dao' => true,
                    'view_vortex_security' => true,
                    'manage_vortex_proposals' => true,
                    'manage_vortex_distributions' => true
                ]
            );
        }
    }
    
    /**
     * Log the activation.
     */
    private static function log_activation() {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_security_logs',
            [
                'event_type' => 'system_activated',
                'message' => 'VORTEX DAO system activated',
                'user_id' => get_current_user_id(),
                'ip_address' => self::get_client_ip(),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%d', '%s', '%s']
        );
    }
    
    /**
     * Get client IP address.
     *
     * @return string Client IP address.
     */
    private static function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
} 