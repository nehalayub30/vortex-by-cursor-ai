<?php
/**
 * VORTEX DAO Token Management
 *
 * Handles all TOLA token management functions
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_DAO_Token {
    
    private static $instance = null;
    private $solana_api;
    private $token_address = 'H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky'; // TOLA token address on Solana
    
    /**
     * Get class instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->solana_api = VORTEX_Solana_API::get_instance();
        
        // Register AJAX handlers
        add_action('wp_ajax_vortex_get_token_balance', array($this, 'ajax_get_token_balance'));
        add_action('wp_ajax_vortex_verify_token_holding', array($this, 'ajax_verify_token_holding'));
        add_action('wp_ajax_vortex_get_token_transfers', array($this, 'ajax_get_token_transfers'));
        add_action('wp_ajax_vortex_get_vesting_status', array($this, 'ajax_get_vesting_status'));
    }
    
    /**
     * Get token balance for a wallet address
     */
    public function get_balance($wallet_address) {
        if (empty($wallet_address)) {
            return false;
        }
        
        // Get balance from Solana API
        $balance = $this->solana_api->get_token_balance($wallet_address, $this->token_address);
        
        if ($balance === false) {
            // Fallback to database if API call fails
            global $wpdb;
            $stored_balance = $wpdb->get_var($wpdb->prepare(
                "SELECT token_balance FROM {$wpdb->prefix}vortex_wallet_addresses WHERE wallet_address = %s",
                $wallet_address
            ));
            
            return $stored_balance ? floatval($stored_balance) : 0;
        }
        
        return $balance;
    }
    
    /**
     * Check if a wallet has minimum required tokens for an action
     */
    public function has_minimum_tokens($wallet_address, $required_amount) {
        $balance = $this->get_balance($wallet_address);
        return ($balance !== false && $balance >= $required_amount);
    }
    
    /**
     * Get voting weight for a wallet address
     */
    public function get_voting_weight($wallet_address) {
        // Get user role for the wallet
        $user_id = $this->get_user_id_from_wallet($wallet_address);
        $user_role = '';
        
        if ($user_id) {
            $user = get_user_by('id', $user_id);
            if ($user) {
                // Check for founder role first
                if (in_array('vortex_founder', (array) $user->roles)) {
                    $user_role = 'founder';
                } elseif (in_array('vortex_investor', (array) $user->roles)) {
                    $user_role = 'investor';
                } elseif (in_array('vortex_team', (array) $user->roles)) {
                    $user_role = 'team';
                }
            }
        }
        
        // Get token balance
        $balance = $this->get_balance($wallet_address);
        
        // Apply role-based multipliers
        $weight_multiplier = 1; // Default multiplier
        
        switch ($user_role) {
            case 'founder':
                $weight_multiplier = 10; // 10x voting power for founders
                break;
            case 'investor':
                $weight_multiplier = 1; // Standard voting power for investors
                break;
            case 'team':
                $weight_multiplier = 0.5; // Half voting power for team members
                break;
        }
        
        // Calculate final voting weight
        $voting_weight = $balance * $weight_multiplier;
        
        return $voting_weight;
    }
    
    /**
     * Get user ID from wallet address
     */
    private function get_user_id_from_wallet($wallet_address) {
        global $wpdb;
        
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}vortex_wallet_addresses WHERE wallet_address = %s",
            $wallet_address
        ));
        
        return $user_id ? intval($user_id) : 0;
    }
    
    /**
     * Check if tokens are locked in vesting
     */
    public function get_vesting_status($wallet_address) {
        global $wpdb;
        
        $vesting_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_token_vesting WHERE wallet_address = %s",
            $wallet_address
        ));
        
        if (!$vesting_data) {
            return array(
                'has_vesting' => false,
                'total_tokens' => 0,
                'vested_tokens' => 0,
                'locked_tokens' => 0,
                'vesting_complete' => true,
                'next_release_date' => null,
                'next_release_amount' => 0,
                'vesting_schedule' => array()
            );
        }
        
        // Get vesting schedule
        $schedule = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_vesting_schedule WHERE vesting_id = %d ORDER BY release_date ASC",
            $vesting_data->id
        ));
        
        // Calculate vested and locked tokens
        $now = time();
        $vested_tokens = 0;
        $next_release = null;
        $next_release_amount = 0;
        
        foreach ($schedule as $release) {
            $release_timestamp = strtotime($release->release_date);
            
            if ($release_timestamp <= $now) {
                $vested_tokens += $release->token_amount;
            } else {
                if ($next_release === null) {
                    $next_release = $release->release_date;
                    $next_release_amount = $release->token_amount;
                }
            }
        }
        
        $locked_tokens = $vesting_data->total_tokens - $vested_tokens;
        $vesting_complete = ($locked_tokens <= 0);
        
        // Format vesting schedule for display
        $formatted_schedule = array();
        foreach ($schedule as $release) {
            $formatted_schedule[] = array(
                'date' => $release->release_date,
                'amount' => $release->token_amount,
                'status' => (strtotime($release->release_date) <= $now) ? 'released' : 'pending'
            );
        }
        
        return array(
            'has_vesting' => true,
            'total_tokens' => $vesting_data->total_tokens,
            'vested_tokens' => $vested_tokens,
            'locked_tokens' => $locked_tokens,
            'vesting_complete' => $vesting_complete,
            'next_release_date' => $next_release,
            'next_release_amount' => $next_release_amount,
            'vesting_schedule' => $formatted_schedule
        );
    }
    
    /**
     * Log token transfer in database
     */
    public function log_token_transfer($from_address, $to_address, $amount, $transaction_signature, $transaction_type = 'transfer') {
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'vortex_token_transfers',
            array(
                'from_address' => $from_address,
                'to_address' => $to_address,
                'token_amount' => $amount,
                'transaction_signature' => $transaction_signature,
                'transaction_type' => $transaction_type,
                'created_at' => current_time('mysql')
            )
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * AJAX: Get token balance
     */
    public function ajax_get_token_balance() {
        // Verify nonce
        check_ajax_referer('vortex_token_nonce', 'nonce');
        
        // Get wallet address
        $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
        
        if (empty($wallet_address)) {
            wp_send_json_error(array('message' => 'Wallet address is required.'));
            return;
        }
        
        $balance = $this->get_balance($wallet_address);
        
        if ($balance === false) {
            wp_send_json_error(array('message' => 'Error fetching token balance.'));
            return;
        }
        
        wp_send_json_success(array(
            'balance' => $balance,
            'formatted_balance' => number_format($balance, 2)
        ));
    }
    
    /**
     * AJAX: Verify token holding
     */
    public function ajax_verify_token_holding() {
        // Verify nonce
        check_ajax_referer('vortex_token_nonce', 'nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User not logged in.'));
            return;
        }
        
        // Get wallet address and required amount
        $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
        $required_amount = isset($_POST['required_amount']) ? floatval($_POST['required_amount']) : 0;
        
        if (empty($wallet_address)) {
            wp_send_json_error(array('message' => 'Wallet address is required.'));
            return;
        }
        
        // Verify wallet belongs to current user
        $current_user_id = get_current_user_id();
        $wallet_user_id = $this->get_user_id_from_wallet($wallet_address);
        
        if ($wallet_user_id !== $current_user_id) {
            wp_send_json_error(array('message' => 'Wallet does not belong to current user.'));
            return;
        }
        
        // Check token balance
        $has_tokens = $this->has_minimum_tokens($wallet_address, $required_amount);
        
        wp_send_json_success(array(
            'has_tokens' => $has_tokens,
            'required_amount' => $required_amount
        ));
    }
    
    /**
     * AJAX: Get token transfers
     */
    public function ajax_get_token_transfers() {
        // Verify nonce
        check_ajax_referer('vortex_token_nonce', 'nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User not logged in.'));
            return;
        }
        
        // Get wallet address
        $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        
        if (empty($wallet_address)) {
            wp_send_json_error(array('message' => 'Wallet address is required.'));
            return;
        }
        
        // Verify wallet belongs to current user
        $current_user_id = get_current_user_id();
        $wallet_user_id = $this->get_user_id_from_wallet($wallet_address);
        
        if ($wallet_user_id !== $current_user_id && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access to wallet transfers.'));
            return;
        }
        
        // Get transfers from database
        global $wpdb;
        $transfers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_token_transfers 
            WHERE from_address = %s OR to_address = %s 
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d",
            $wallet_address, $wallet_address, $limit, $offset
        ));
        
        wp_send_json_success(array(
            'transfers' => $transfers,
            'count' => count($transfers)
        ));
    }
    
    /**
     * AJAX: Get vesting status
     */
    public function ajax_get_vesting_status() {
        // Verify nonce
        check_ajax_referer('vortex_token_nonce', 'nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User not logged in.'));
            return;
        }
        
        // Get wallet address
        $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
        
        if (empty($wallet_address)) {
            wp_send_json_error(array('message' => 'Wallet address is required.'));
            return;
        }
        
        // Verify wallet belongs to current user
        $current_user_id = get_current_user_id();
        $wallet_user_id = $this->get_user_id_from_wallet($wallet_address);
        
        if ($wallet_user_id !== $current_user_id && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access to vesting information.'));
            return;
        }
        
        // Get vesting status
        $vesting_status = $this->get_vesting_status($wallet_address);
        
        wp_send_json_success($vesting_status);
    }
    
    /**
     * AJAX: Get artist token stats
     */
    public function ajax_get_artist_token_stats() {
        // Verify nonce
        check_ajax_referer('vortex_token_nonce', 'nonce');
        
        // Get artist ID and period
        $artist_id = isset($_POST['artist_id']) ? intval($_POST['artist_id']) : 0;
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'all';
        
        if (!$artist_id) {
            wp_send_json_error(array('message' => 'Artist ID is required.'));
            return;
        }
        
        // Get stats
        $stats = $this->get_artist_token_stats($artist_id, $period);
        
        if ($stats === false) {
            wp_send_json_error(array('message' => 'Error retrieving artist token statistics.'));
            return;
        }
        
        wp_send_json_success(array('stats' => $stats));
    }
    
    /**
     * Create required database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Token transfers table
        $table_transfers = $wpdb->prefix . 'vortex_token_transfers';
        $sql_transfers = "CREATE TABLE $table_transfers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            from_address varchar(255) NOT NULL,
            to_address varchar(255) NOT NULL,
            token_amount decimal(18,8) NOT NULL,
            transaction_signature varchar(255) NOT NULL,
            transaction_type varchar(50) NOT NULL DEFAULT 'transfer',
            metadata text DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY from_address (from_address),
            KEY to_address (to_address),
            KEY transaction_type (transaction_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Token vesting table
        $table_vesting = $wpdb->prefix . 'vortex_token_vesting';
        $sql_vesting = "CREATE TABLE $table_vesting (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            wallet_address varchar(255) NOT NULL,
            vesting_type varchar(50) NOT NULL,
            total_tokens decimal(18,8) NOT NULL,
            start_date datetime NOT NULL,
            cliff_end_date datetime DEFAULT NULL,
            end_date datetime NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY wallet_address (wallet_address),
            KEY user_id (user_id),
            KEY vesting_type (vesting_type)
        ) $charset_collate;";
        
        // Vesting schedule table
        $table_schedule = $wpdb->prefix . 'vortex_vesting_schedule';
        $sql_schedule = "CREATE TABLE $table_schedule (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            vesting_id bigint(20) NOT NULL,
            release_date datetime NOT NULL,
            token_amount decimal(18,8) NOT NULL,
            release_notes text DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY vesting_id (vesting_id),
            KEY release_date (release_date)
        ) $charset_collate;";
        
        // Wallet addresses table
        $table_wallets = $wpdb->prefix . 'vortex_wallet_addresses';
        $sql_wallets = "CREATE TABLE $table_wallets (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            wallet_address varchar(255) NOT NULL,
            wallet_type varchar(50) NOT NULL DEFAULT 'solana',
            is_primary tinyint(1) NOT NULL DEFAULT 0,
            token_balance decimal(18,8) DEFAULT NULL,
            verified tinyint(1) NOT NULL DEFAULT 0,
            verification_signature text DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY wallet_address (wallet_address),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Token metrics history table
        $table_metrics = $wpdb->prefix . 'vortex_token_metrics_history';
        $sql_metrics = "CREATE TABLE $table_metrics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            price decimal(18,8) NOT NULL DEFAULT 0,
            market_cap decimal(18,2) NOT NULL DEFAULT 0,
            circulating_supply bigint(20) NOT NULL DEFAULT 0,
            total_supply bigint(20) NOT NULL DEFAULT 0,
            holder_count int(11) NOT NULL DEFAULT 0,
            volume_24h decimal(18,2) NOT NULL DEFAULT 0,
            artwork_count int(11) NOT NULL DEFAULT 0,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY updated_at (updated_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_transfers);
        dbDelta($sql_vesting);
        dbDelta($sql_schedule);
        dbDelta($sql_wallets);
        dbDelta($sql_metrics);
    }
}

// Initialize Token class
$vortex_dao_token = VORTEX_DAO_Token::get_instance();

// Register activation hook for table creation
register_activation_hook(VORTEX_PLUGIN_FILE, array('VORTEX_DAO_Token', 'create_tables')); 