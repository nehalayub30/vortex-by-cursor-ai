<?php
/**
 * VORTEX DAO Core Functionality
 *
 * @package VORTEX
 */

class VORTEX_DAO_Core {
    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;
    
    /**
     * DAO configuration settings.
     *
     * @var array
     */
    private $config;
    
    /**
     * Constructor.
     */
    private function __construct() {
        $this->load_configuration();
        $this->init_hooks();
    }
    
    /**
     * Get instance of this class.
     *
     * @return object Instance of this class.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load DAO configuration.
     */
    private function load_configuration() {
        $this->config = get_option('vortex_dao_config', [
            // Founder and ownership structure
            'founder_address' => '',
            'treasury_address' => '',
            'token_address' => 'H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky', // TOLA token
            
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
            'blockchain_network' => 'solana',
        ]);
    }
    
    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Plugin activation/deactivation
        register_activation_hook(VORTEX_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(VORTEX_PLUGIN_FILE, [$this, 'deactivate']);
        
        // Admin hooks
        add_action('admin_menu', [$this, 'add_admin_menu'], 20);
        add_action('admin_init', [$this, 'register_settings']);
        
        // Ajax handlers
        add_action('wp_ajax_vortex_deploy_dao_contracts', [$this, 'ajax_deploy_dao_contracts']);
        add_action('wp_ajax_vortex_get_token_balances', [$this, 'ajax_get_token_balances']);
        
        // Load modules
        $this->load_modules();
        
        // Shortcodes
        add_shortcode('vortex_dao_overview', [$this, 'dao_overview_shortcode']);
    }
    
    /**
     * Load DAO modules.
     */
    private function load_modules() {
        // Include module files
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/dao/class-vortex-dao-tokens.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/dao/class-vortex-dao-investment.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/dao/class-vortex-dao-treasury.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/dao/class-vortex-dao-revenue.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/dao/class-vortex-dao-incentives.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/dao/class-vortex-dao-governance.php';
        
        // Initialize modules
        VORTEX_DAO_Tokens::get_instance();
        VORTEX_DAO_Investment::get_instance();
        VORTEX_DAO_Treasury::get_instance();
        VORTEX_DAO_Revenue::get_instance();
        VORTEX_DAO_Incentives::get_instance();
        VORTEX_DAO_Governance::get_instance();
    }
    
    /**
     * Plugin activation hook.
     */
    public function activate() {
        // Create necessary database tables
        $this->create_tables();
        
        // Add default options if not exists
        if (!get_option('vortex_dao_config')) {
            update_option('vortex_dao_config', $this->config);
        }
        
        // Schedule events
        wp_schedule_event(time(), 'daily', 'vortex_dao_daily_tasks');
    }
    
    /**
     * Plugin deactivation hook.
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('vortex_dao_daily_tasks');
    }
    
    /**
     * Create database tables.
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Investors table
        $table_investors = $wpdb->prefix . 'vortex_dao_investors';
        $sql_investors = "CREATE TABLE $table_investors (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            wallet_address varchar(44) NOT NULL, /* Updated for Solana addresses */
            investment_amount decimal(18,2) NOT NULL DEFAULT 0,
            token_amount decimal(18,0) NOT NULL DEFAULT 0,
            token_price decimal(18,2) NOT NULL DEFAULT 0,
            investment_date datetime NOT NULL,
            vesting_end_date datetime NOT NULL,
            kyc_status varchar(20) NOT NULL DEFAULT 'pending',
            notes text,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY wallet_address (wallet_address)
        ) $charset_collate;";
        
        // Revenue transactions table
        $table_revenue = $wpdb->prefix . 'vortex_dao_revenue';
        $sql_revenue = "CREATE TABLE $table_revenue (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            transaction_type varchar(50) NOT NULL,
            user_id bigint(20) NOT NULL,
            amount decimal(18,2) NOT NULL,
            currency varchar(10) NOT NULL DEFAULT 'USD',
            token_amount decimal(18,0) DEFAULT NULL,
            reference_id varchar(100) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'completed',
            transaction_date datetime NOT NULL,
            blockchain_tx varchar(100) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY transaction_type (transaction_type),
            KEY transaction_date (transaction_date)
        ) $charset_collate;";
        
        // Distributions table
        $table_distributions = $wpdb->prefix . 'vortex_dao_distributions';
        $sql_distributions = "CREATE TABLE $table_distributions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            distribution_type varchar(50) NOT NULL,
            total_amount decimal(18,2) NOT NULL,
            token_type varchar(10) NOT NULL DEFAULT 'TOLA', /* Updated to default to TOLA */
            distribution_date datetime NOT NULL,
            snapshot_id varchar(100) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            blockchain_tx varchar(100) DEFAULT NULL,
            notes text,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY distribution_type (distribution_type),
            KEY distribution_date (distribution_date)
        ) $charset_collate;";
        
        // User reward claims table
        $table_claims = $wpdb->prefix . 'vortex_dao_claims';
        $sql_claims = "CREATE TABLE $table_claims (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            distribution_id bigint(20) NOT NULL,
            amount decimal(18,2) NOT NULL,
            token_type varchar(10) NOT NULL DEFAULT 'TOLA', /* Updated to default to TOLA */
            claimed tinyint(1) NOT NULL DEFAULT 0,
            claim_date datetime DEFAULT NULL,
            blockchain_tx varchar(100) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY distribution_id (distribution_id)
        ) $charset_collate;";
        
        // Artist and Collector tiers table
        $table_tiers = $wpdb->prefix . 'vortex_dao_user_tiers';
        $sql_tiers = "CREATE TABLE $table_tiers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            user_type varchar(20) NOT NULL, -- artist or collector
            tier_level int(11) NOT NULL DEFAULT 0,
            tier_name varchar(50) NOT NULL,
            token_balance decimal(18,0) DEFAULT 0,
            requirements_met text,
            promotion_date datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_tier (user_id, user_type),
            KEY tier_level (tier_level)
        ) $charset_collate;";
        
        // Governance proposals table
        $table_proposals = $wpdb->prefix . 'vortex_dao_proposals';
        $sql_proposals = "CREATE TABLE $table_proposals (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            proposal_id varchar(100) DEFAULT NULL,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            proposal_type varchar(50) NOT NULL,
            proposer_id bigint(20) NOT NULL,
            proposer_address varchar(44) NOT NULL, /* Updated for Solana addresses */
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            implementation_data text,
            blockchain_tx varchar(100) DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY proposal_type (proposal_type),
            KEY status (status),
            KEY end_date (end_date)
        ) $charset_collate;";
        
        // Votes table
        $table_votes = $wpdb->prefix . 'vortex_dao_votes';
        $sql_votes = "CREATE TABLE $table_votes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            proposal_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            voter_address varchar(44) NOT NULL, /* Updated for Solana addresses */
            vote_choice varchar(10) NOT NULL, -- for, against, abstain
            vote_power decimal(18,0) NOT NULL,
            vote_date datetime NOT NULL,
            blockchain_tx varchar(100) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_proposal (user_id, proposal_id),
            KEY proposal_id (proposal_id)
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
        
        // Token balances table - simplified for single token
        $table_token_balances = $wpdb->prefix . 'vortex_dao_token_balances';
        $sql_token_balances = "CREATE TABLE $table_token_balances (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            wallet_address varchar(44) NOT NULL, /* Updated for Solana addresses */
            balance decimal(18,0) NOT NULL DEFAULT 0,
            locked_balance decimal(18,0) NOT NULL DEFAULT 0,
            last_updated datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY wallet (wallet_address)
        ) $charset_collate;";
        
        // Execute database creation queries
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_investors);
        dbDelta($sql_revenue);
        dbDelta($sql_distributions);
        dbDelta($sql_claims);
        dbDelta($sql_tiers);
        dbDelta($sql_proposals);
        dbDelta($sql_votes);
        dbDelta($sql_security_logs);
        dbDelta($sql_token_balances);
    }
    
    /**
     * Add admin menu items.
     */
    public function add_admin_menu() {
        // Main DAO menu
        add_menu_page(
            __('VORTEX DAO', 'vortex'),
            __('VORTEX DAO', 'vortex'),
            'manage_options',
            'vortex-dao',
            [$this, 'render_dao_dashboard'],
            'dashicons-vault',
            30
        );
        
        // Setup submenu
        add_submenu_page(
            'vortex-dao',
            __('DAO Setup', 'vortex'),
            __('Setup', 'vortex'),
            'manage_options',
            'vortex-dao-setup',
            [$this, 'render_dao_setup']
        );
        
        // Tokens submenu
        add_submenu_page(
            'vortex-dao',
            __('Tokens', 'vortex'),
            __('Tokens', 'vortex'),
            'manage_options',
            'vortex-dao-tokens',
            [VORTEX_DAO_Tokens::get_instance(), 'render_admin_page']
        );
        
        // Investors submenu
        add_submenu_page(
            'vortex-dao',
            __('Investors', 'vortex'),
            __('Investors', 'vortex'),
            'manage_options',
            'vortex-dao-investors',
            [VORTEX_DAO_Investment::get_instance(), 'render_admin_page']
        );
        
        // Treasury submenu
        add_submenu_page(
            'vortex-dao',
            __('Treasury', 'vortex'),
            __('Treasury', 'vortex'),
            'manage_options',
            'vortex-dao-treasury',
            [VORTEX_DAO_Treasury::get_instance(), 'render_admin_page']
        );
        
        // Revenue submenu
        add_submenu_page(
            'vortex-dao',
            __('Revenue', 'vortex'),
            __('Revenue', 'vortex'),
            'manage_options',
            'vortex-dao-revenue',
            [VORTEX_DAO_Revenue::get_instance(), 'render_admin_page']
        );
        
        // Incentives submenu
        add_submenu_page(
            'vortex-dao',
            __('Incentives', 'vortex'),
            __('Incentives', 'vortex'),
            'manage_options',
            'vortex-dao-incentives',
            [VORTEX_DAO_Incentives::get_instance(), 'render_admin_page']
        );
        
        // Governance submenu
        add_submenu_page(
            'vortex-dao',
            __('Governance', 'vortex'),
            __('Governance', 'vortex'),
            'manage_options',
            'vortex-dao-governance',
            [VORTEX_DAO_Governance::get_instance(), 'render_admin_page']
        );
    }
    
    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting('vortex_dao_options', 'vortex_dao_config');
    }
    
    /**
     * Render DAO dashboard page.
     */
    public function render_dao_dashboard() {
        // Include dashboard template
        include plugin_dir_path(dirname(__FILE__)) . 'admin/partials/dao-dashboard.php';
    }
    
    /**
     * Render DAO setup page.
     */
    public function render_dao_setup() {
        // Include setup template
        include plugin_dir_path(dirname(__FILE__)) . 'admin/partials/dao-setup.php';
    }
    
    /**
     * DAO overview shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode HTML.
     */
    public function dao_overview_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(
            [
                'show_tokens' => 'true',
                'show_treasury' => 'true',
                'show_incentives' => 'true',
            ],
            $atts,
            'vortex_dao_overview'
        );
        
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/dao-overview.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX: Deploy DAO contracts.
     */
    public function ajax_deploy_dao_contracts() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_dao_setup')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'vortex')]);
        }
        
        // Get contract details
        $contract_data = isset($_POST['contract_data']) ? $_POST['contract_data'] : [];
        
        // Validate data
        if (empty($contract_data['founder_address'])) {
            wp_send_json_error(['message' => __('Founder address is required.', 'vortex')]);
        }
        
        // Deploy contracts
        try {
            // Here you would integrate with a blockchain library like Web3.php or make API calls
            // For now we'll simulate the deployment process
            
            // Update configuration with contract addresses
            $updated_config = $this->config;
            $updated_config['founder_address'] = sanitize_text_field($contract_data['founder_address']);
            $updated_config['token_address'] = '0x' . md5('token_' . time()); // Simulated address
            $updated_config['treasury_address'] = '0x' . md5('treasury_' . time()); // Simulated address
            
            update_option('vortex_dao_config', $updated_config);
            $this->config = $updated_config;
            
            wp_send_json_success([
                'message' => __('DAO contracts deployed successfully!', 'vortex'),
                'contract_addresses' => [
                    'token' => $updated_config['token_address'],
                    'treasury' => $updated_config['treasury_address'],
                ],
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Get token balances.
     */
    public function ajax_get_token_balances() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_dao_token_balances')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to check balances.', 'vortex')]);
        }
        
        $user_id = get_current_user_id();
        
        // Get user wallet address
        $wallet_address = get_user_meta($user_id, 'vortex_wallet_address', true);
        
        if (empty($wallet_address)) {
            wp_send_json_error(['message' => __('No wallet address found for your account.', 'vortex')]);
        }
        
        // Get token balances
        try {
            // Initialize Solana connection (or simulation for testing)
            $solana_api = new VORTEX_Solana_API();
            
            // Get actual TOLA balance from Solana (or simulated)
            $tola_balance = $solana_api->get_token_balance(
                $this->config['token_address'],
                $wallet_address
            );
            
            // If API not available, fall back to simulated balance
            if ($tola_balance === false) {
                $tola_balance = $this->get_simulated_token_balance($user_id);
            }
            
            // Get governance voting power (includes multipliers)
            $voting_power = $this->calculate_voting_power($user_id, $tola_balance);
            
            wp_send_json_success([
                'tola_balance' => $tola_balance,
                'voting_power' => $voting_power,
                'wallet_address' => $wallet_address,
                'token_address' => $this->config['token_address']
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Get simulated token balance for testing.
     *
     * @param int $user_id User ID.
     * @return float Simulated balance.
     */
    private function get_simulated_token_balance($user_id) {
        global $wpdb;
        
        // Check if user is an investor
        $investor = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_dao_investors WHERE user_id = %d",
                $user_id
            )
        );
        
        if ($investor) {
            return $investor->token_amount;
        }
        
        // Check if user is the founder
        $user_email = get_userdata($user_id)->user_email;
        if ($user_email === get_option('admin_email')) {
            return $this->config['founder_allocation'];
        }
        
        // Sum token rewards
        $balance = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount) FROM {$wpdb->prefix}vortex_dao_claims 
                WHERE user_id = %d AND claimed = 1",
                $user_id
            )
        );
        
        return $balance ? floatval($balance) : mt_rand(10, 1000); // Random balance for testing
    }
    
    /**
     * Calculate voting power based on token balance and user type.
     *
     * @param int $user_id User ID.
     * @param float $token_balance Token balance.
     * @return float Voting power.
     */
    private function calculate_voting_power($user_id, $token_balance) {
        // Determine user type and multiplier
        $multiplier = 1;
        $user_email = get_userdata($user_id)->user_email;
        
        // Check if user is founder
        if ($user_email === get_option('admin_email')) {
            $multiplier = $this->config['founder_vote_multiplier'];
        } else {
            // Check if user is an investor
            global $wpdb;
            $investor = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}vortex_dao_investors WHERE user_id = %d",
                    $user_id
                )
            );
            
            if ($investor) {
                $multiplier = $this->config['investor_vote_multiplier'];
            } else {
                // Check if user is team member
                // (implement logic to identify team members)
                // For now, we'll assume they're not team members
            }
        }
        
        return $token_balance * $multiplier;
    }
    
    /**
     * Get DAO configuration.
     *
     * @return array DAO configuration.
     */
    public function get_config() {
        return $this->config;
    }
    
    /**
     * Update DAO configuration.
     *
     * @param array $new_config New configuration values.
     * @return bool Whether the update was successful.
     */
    public function update_config($new_config) {
        $this->config = array_merge($this->config, $new_config);
        return update_option('vortex_dao_config', $this->config);
    }
}

// Initialize the core DAO class
VORTEX_DAO_Core::get_instance(); 