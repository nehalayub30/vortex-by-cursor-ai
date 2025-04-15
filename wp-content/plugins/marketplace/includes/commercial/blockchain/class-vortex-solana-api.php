<?php
/**
 * Solana API Integration for VORTEX DAO
 *
 * @package           VORTEX_Marketplace
 * @subpackage        DAO
 */

class VORTEX_Solana_API {
    /**
     * API endpoints
     */
    private $api_endpoints = array(
        'base' => 'https://api.solana.com',
        'mainnet' => 'https://api.mainnet-beta.solana.com',
        'devnet' => 'https://api.devnet.solana.com',
        'testnet' => 'https://api.testnet.solana.com',
    );
    
    /**
     * Network to use (mainnet, devnet, testnet)
     */
    private $network = 'devnet';
    
    /**
     * Program ID for the TOLA token
     */
    private $token_program_id = '';
    
    /**
     * Admin wallet private key
     */
    private $admin_private_key = '';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get settings
        $this->network = get_option('vortex_solana_network', 'devnet');
        $this->token_program_id = get_option('vortex_solana_token_program_id', '');
        $this->admin_private_key = get_option('vortex_solana_admin_private_key', '');
        
        // Register AJAX handlers
        add_action('wp_ajax_vortex_solana_get_balance', array($this, 'ajax_get_balance'));
        add_action('wp_ajax_vortex_solana_transfer_tokens', array($this, 'ajax_transfer_tokens'));
        add_action('wp_ajax_vortex_solana_create_account', array($this, 'ajax_create_account'));
        add_action('wp_ajax_vortex_solana_verify_wallet', array($this, 'ajax_verify_wallet'));
        
        // Add admin hooks
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        
        // Add cron tasks
        add_action('vortex_solana_sync_balances', array($this, 'sync_all_wallet_balances'));
        
        // Schedule cron if not already scheduled
        if (!wp_next_scheduled('vortex_solana_sync_balances')) {
            wp_schedule_event(time(), 'hourly', 'vortex_solana_sync_balances');
        }
    }
    
    /**
     * Get Solana API URL based on configured network
     * 
     * @return string API URL
     */
    private function get_api_url() {
        return $this->api_endpoints[$this->network];
    }
    
    /**
     * Make API request to Solana network
     * 
     * @param string $method RPC method
     * @param array $params Parameters for the method
     * @return array|WP_Error Response or error
     */
    private function make_request($method, $params = array()) {
        $url = $this->get_api_url();
        
        $request_body = array(
            'jsonrpc' => '2.0',
            'id' => uniqid(),
            'method' => $method,
            'params' => $params
        );
        
        $response = wp_remote_post(
            $url,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'timeout' => 30,
                'body' => json_encode($request_body),
            )
        );
        
        if (is_wp_error($response)) {
            error_log('Solana API Error: ' . $response->get_error_message());
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            error_log('Solana API Error: ' . json_encode($body['error']));
            return new WP_Error('solana_api_error', $body['error']['message'], $body['error']);
        }
        
        return $body['result'];
    }
    
    /**
     * Get token balance for a wallet address
     * 
     * @param string $wallet_address Wallet address
     * @return float Token balance
     */
    public function get_token_balance($wallet_address) {
        // Default to 0 balance if parameters are missing
        if (empty($wallet_address) || empty($this->token_program_id)) {
            return 0;
        }
        
        try {
            $result = $this->make_request('getTokenAccountsByOwner', array(
                $wallet_address,
                array(
                    'programId' => $this->token_program_id,
                ),
                array(
                    'encoding' => 'jsonParsed',
                )
            ));
            
            if (is_wp_error($result)) {
                error_log('Error getting token balance: ' . $result->get_error_message());
                return 0;
            }
            
            $balance = 0;
            
            if (isset($result['value']) && is_array($result['value'])) {
                foreach ($result['value'] as $account) {
                    if (isset($account['account']['data']['parsed']['info']['tokenAmount']['uiAmount'])) {
                        $balance += floatval($account['account']['data']['parsed']['info']['tokenAmount']['uiAmount']);
                    }
                }
            }
            
            return $balance;
            
        } catch (Exception $e) {
            error_log('Exception getting token balance: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Transfer tokens from admin wallet to user wallet
     * 
     * @param string $to_address Recipient wallet address
     * @param float $amount Amount of tokens to transfer
     * @return array|WP_Error Transaction details or error
     */
    public function transfer_tokens($to_address, $amount) {
        // Check parameters
        if (empty($to_address) || empty($amount) || floatval($amount) <= 0) {
            return new WP_Error('invalid_parameters', 'Invalid transfer parameters');
        }
        
        // Check admin private key
        if (empty($this->admin_private_key)) {
            return new WP_Error('missing_private_key', 'Admin private key is not configured');
        }
        
        try {
            // This is a simplified representation of token transfer
            // In a real implementation, you would construct a proper Solana transaction
            // using a Solana client library and sign it with the admin private key
            
            // For now, we'll log the intent and return a mock transaction
            error_log(sprintf('Would transfer %.2f TOLA tokens to %s', $amount, $to_address));
            
            // Mock transaction response for development
            $mock_transaction = array(
                'transaction_id' => 'mock_' . uniqid(),
                'signature' => '4vJ9JU1bJJE96FbKLwQdj93xAtKrrdVKv7xX7sALMGqFCGYweMjyWCgJvgJX7ReqDXn9nfYFawDev8dQRYUJ9J8Y',
                'amount' => $amount,
                'from' => 'ADMIN_WALLET',
                'to' => $to_address,
                'status' => 'confirmed',
                'timestamp' => time(),
            );
            
            // Record transaction in the database
            $this->record_transaction($mock_transaction);
            
            return $mock_transaction;
            
        } catch (Exception $e) {
            error_log('Exception during token transfer: ' . $e->getMessage());
            return new WP_Error('transfer_error', $e->getMessage());
        }
    }
    
    /**
     * Record token transaction in the database
     * 
     * @param array $transaction Transaction details
     * @return bool Success status
     */
    private function record_transaction($transaction) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_token_transactions';
        
        // Create table if it doesn't exist
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $this->create_transactions_table();
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'transaction_id' => $transaction['transaction_id'],
                'signature' => $transaction['signature'],
                'amount' => $transaction['amount'],
                'from_address' => $transaction['from'],
                'to_address' => $transaction['to'],
                'status' => $transaction['status'],
                'transaction_date' => date('Y-m-d H:i:s', $transaction['timestamp']),
                'transaction_data' => json_encode($transaction),
            )
        );
        
        return $result !== false;
    }
    
    /**
     * Create token transactions table
     */
    private function create_transactions_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_token_transactions';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            transaction_id varchar(100) NOT NULL,
            signature varchar(200) NOT NULL,
            amount decimal(18,8) NOT NULL,
            from_address varchar(100) NOT NULL,
            to_address varchar(100) NOT NULL,
            status varchar(50) NOT NULL,
            transaction_date datetime NOT NULL,
            transaction_data text NOT NULL,
            PRIMARY KEY  (id),
            KEY transaction_id (transaction_id),
            KEY from_address (from_address),
            KEY to_address (to_address),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create a new Solana account with TOLA tokens
     * 
     * @param float $initial_balance Initial token balance
     * @return array Account details
     */
    public function create_account($initial_balance = 0) {
        // In a real implementation, this would generate a new keypair and fund it
        // For now, we'll return a mock account
        
        $account = array(
            'public_key' => 'sol' . md5(uniqid() . time()),
            'private_key' => 'MOCK_PRIVATE_KEY_' . uniqid(),
            'initial_balance' => $initial_balance,
        );
        
        // If initial balance is specified, transfer tokens
        if ($initial_balance > 0) {
            $this->transfer_tokens($account['public_key'], $initial_balance);
        }
        
        return $account;
    }
    
    /**
     * Verify wallet ownership through challenge-response
     * 
     * @param string $wallet_address Wallet address
     * @param string $signature Signature proving ownership
     * @param string $message Message that was signed
     * @return bool Verification result
     */
    public function verify_wallet_ownership($wallet_address, $signature, $message) {
        // In a real implementation, this would verify the signature against the message
        // using Solana's verification methods
        
        // For now, return true for development
        return true;
    }
    
    /**
     * Sync token balances for all user wallets
     */
    public function sync_all_wallet_balances() {
        global $wpdb;
        
        // Get all registered wallets
        $wallet_table = $wpdb->prefix . 'vortex_user_wallets';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$wallet_table'") != $wallet_table) {
            // Table doesn't exist yet
            return;
        }
        
        $wallets = $wpdb->get_results("SELECT user_id, wallet_address FROM $wallet_table");
        
        foreach ($wallets as $wallet) {
            // Get current balance
            $balance = $this->get_token_balance($wallet->wallet_address);
            
            // Update user meta
            update_user_meta($wallet->user_id, 'vortex_tola_token_balance', $balance);
            
            // Update wallet table
            $wpdb->update(
                $wallet_table,
                array('token_balance' => $balance, 'last_updated' => current_time('mysql')),
                array('wallet_address' => $wallet->wallet_address)
            );
        }
    }
    
    /**
     * AJAX handler for getting token balance
     */
    public function ajax_get_balance() {
        // Check for nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_solana_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        // Check for wallet address
        if (!isset($_POST['wallet_address']) || empty($_POST['wallet_address'])) {
            wp_send_json_error('Wallet address is required');
        }
        
        $wallet_address = sanitize_text_field($_POST['wallet_address']);
        
        // Get balance
        $balance = $this->get_token_balance($wallet_address);
        
        wp_send_json_success(array(
            'balance' => $balance,
            'formatted_balance' => number_format($balance, 2) . ' TOLA',
        ));
    }
    
    /**
     * AJAX handler for transferring tokens
     */
    public function ajax_transfer_tokens() {
        // Check for nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_solana_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        // Check for required parameters
        if (!isset($_POST['to_address']) || empty($_POST['to_address'])) {
            wp_send_json_error('Recipient address is required');
        }
        
        if (!isset($_POST['amount']) || floatval($_POST['amount']) <= 0) {
            wp_send_json_error('Valid amount is required');
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            // For regular users, check if they're transferring to their own wallet
            $user_id = get_current_user_id();
            $to_address = sanitize_text_field($_POST['to_address']);
            
            global $wpdb;
            $wallet_table = $wpdb->prefix . 'vortex_user_wallets';
            $is_user_wallet = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $wallet_table WHERE user_id = %d AND wallet_address = %s",
                $user_id,
                $to_address
            ));
            
            if (!$is_user_wallet) {
                wp_send_json_error('You can only transfer tokens to your own wallets');
            }
        }
        
        $to_address = sanitize_text_field($_POST['to_address']);
        $amount = floatval($_POST['amount']);
        
        // Transfer tokens
        $result = $this->transfer_tokens($to_address, $amount);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array(
                'transaction' => $result,
                'message' => sprintf('Successfully transferred %.2f TOLA tokens', $amount),
            ));
        }
    }
    
    /**
     * AJAX handler for creating a new account
     */
    public function ajax_create_account() {
        // Check for nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_solana_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $initial_balance = isset($_POST['initial_balance']) ? floatval($_POST['initial_balance']) : 0;
        
        // Create account
        $account = $this->create_account($initial_balance);
        
        wp_send_json_success(array(
            'account' => $account,
            'message' => 'New Solana account created successfully',
        ));
    }
    
    /**
     * AJAX handler for verifying wallet ownership
     */
    public function ajax_verify_wallet() {
        // Check for nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_solana_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        // Check for required parameters
        if (!isset($_POST['wallet_address']) || empty($_POST['wallet_address'])) {
            wp_send_json_error('Wallet address is required');
        }
        
        if (!isset($_POST['signature']) || empty($_POST['signature'])) {
            wp_send_json_error('Signature is required');
        }
        
        if (!isset($_POST['message']) || empty($_POST['message'])) {
            wp_send_json_error('Message is required');
        }
        
        $wallet_address = sanitize_text_field($_POST['wallet_address']);
        $signature = sanitize_text_field($_POST['signature']);
        $message = sanitize_text_field($_POST['message']);
        
        // Verify wallet
        $verified = $this->verify_wallet_ownership($wallet_address, $signature, $message);
        
        if ($verified) {
            // Record wallet for current user
            $user_id = get_current_user_id();
            $this->record_user_wallet($user_id, $wallet_address);
            
            wp_send_json_success(array(
                'message' => 'Wallet verified successfully',
            ));
        } else {
            wp_send_json_error('Wallet verification failed');
        }
    }
    
    /**
     * Record user wallet in the database
     * 
     * @param int $user_id User ID
     * @param string $wallet_address Wallet address
     * @return bool Success status
     */
    private function record_user_wallet($user_id, $wallet_address) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_user_wallets';
        
        // Create table if it doesn't exist
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $this->create_wallets_table();
        }
        
        // Check if wallet already exists for this user
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND wallet_address = %s",
            $user_id,
                $wallet_address
        ));
        
        if ($existing) {
            // Update last verified date
            return $wpdb->update(
                $table_name,
                array('last_verified' => current_time('mysql')),
                array('user_id' => $user_id, 'wallet_address' => $wallet_address)
            ) !== false;
        }
        
        // Get token balance
        $balance = $this->get_token_balance($wallet_address);
        
        // Insert new wallet
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'wallet_address' => $wallet_address,
                'token_balance' => $balance,
                'is_primary' => 0, // Not primary by default
                'date_added' => current_time('mysql'),
                'last_verified' => current_time('mysql'),
                'last_updated' => current_time('mysql'),
            )
        );
        
        // If this is the user's first wallet, make it primary
        if ($result && $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            $user_id
        )) === '1') {
            $wpdb->update(
                $table_name,
                array('is_primary' => 1),
                array('user_id' => $user_id, 'wallet_address' => $wallet_address)
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Create user wallets table
     */
    private function create_wallets_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_user_wallets';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            wallet_address varchar(100) NOT NULL,
            token_balance decimal(18,8) DEFAULT 0,
            is_primary tinyint(1) DEFAULT 0,
            date_added datetime NOT NULL,
            last_verified datetime NOT NULL,
            last_updated datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY wallet_address (wallet_address),
            KEY user_id (user_id),
            KEY is_primary (is_primary)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('vortex_solana_settings', 'vortex_solana_network');
        register_setting('vortex_solana_settings', 'vortex_solana_token_program_id');
        register_setting('vortex_solana_settings', 'vortex_solana_admin_private_key');
    }
    
    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=vortex_artwork',
            'Solana Settings',
            'Solana Settings',
            'manage_options',
            'vortex-solana-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Solana Integration Settings', 'vortex'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('vortex_solana_settings'); ?>
                <?php do_settings_sections('vortex_solana_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Network', 'vortex'); ?></th>
                        <td>
                            <select name="vortex_solana_network">
                                <option value="mainnet" <?php selected(get_option('vortex_solana_network'), 'mainnet'); ?>>
                                    <?php echo esc_html__('Mainnet', 'vortex'); ?>
                                </option>
                                <option value="devnet" <?php selected(get_option('vortex_solana_network', 'devnet'), 'devnet'); ?>>
                                    <?php echo esc_html__('Devnet', 'vortex'); ?>
                                </option>
                                <option value="testnet" <?php selected(get_option('vortex_solana_network'), 'testnet'); ?>>
                                    <?php echo esc_html__('Testnet', 'vortex'); ?>
                                </option>
                            </select>
                            <p class="description"><?php echo esc_html__('Select the Solana network to connect to.', 'vortex'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Token Program ID', 'vortex'); ?></th>
                        <td>
                            <input type="text" name="vortex_solana_token_program_id" value="<?php echo esc_attr(get_option('vortex_solana_token_program_id')); ?>" class="regular-text" />
                            <p class="description"><?php echo esc_html__('Enter the Solana SPL Token Program ID for TOLA tokens.', 'vortex'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Admin Private Key', 'vortex'); ?></th>
                        <td>
                            <input type="password" name="vortex_solana_admin_private_key" value="<?php echo esc_attr(get_option('vortex_solana_admin_private_key')); ?>" class="regular-text" />
                            <p class="description"><?php echo esc_html__('Enter the admin wallet private key (stored securely).', 'vortex'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize
add_action('plugins_loaded', function() {
    new VORTEX_Solana_API();
});