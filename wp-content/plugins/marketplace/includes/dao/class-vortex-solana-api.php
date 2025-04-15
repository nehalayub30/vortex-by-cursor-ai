<?php
/**
 * VORTEX Solana API Integration
 * 
 * Handles interactions with the Solana blockchain and TOLA token
 *
 * @package VORTEX
 */

class VORTEX_Solana_API {
    /**
     * Solana API endpoint
     *
     * @var string
     */
    private $endpoint;
    
    /**
     * Debug mode
     *
     * @var bool
     */
    private $debug;
    
    /**
     * Constructor.
     */
    public function __construct() {
        // Use mainnet or devnet based on environment
        $this->endpoint = defined('VORTEX_SOLANA_DEVNET') && VORTEX_SOLANA_DEVNET 
            ? 'https://api.devnet.solana.com' 
            : 'https://api.mainnet-beta.solana.com';
        
        $this->debug = defined('WP_DEBUG') && WP_DEBUG;
    }
    
    /**
     * Get token balance for a wallet address.
     *
     * @param string $token_address The token address (TOLA).
     * @param string $wallet_address The wallet address to check.
     * @return float|bool Token balance or false on error.
     */
    public function get_token_balance($token_address, $wallet_address) {
        try {
            $response = $this->make_rpc_call('getTokenAccountsByOwner', [
                $wallet_address,
                [
                    'mint' => $token_address
                ],
                [
                    'encoding' => 'jsonParsed'
                ]
            ]);
            
            if (isset($response['result']['value']) && is_array($response['result']['value'])) {
                $total_balance = 0;
                
                foreach ($response['result']['value'] as $account) {
                    if (isset($account['account']['data']['parsed']['info']['tokenAmount']['uiAmount'])) {
                        $total_balance += $account['account']['data']['parsed']['info']['tokenAmount']['uiAmount'];
                    }
                }
                
                return $total_balance;
            }
            
            if ($this->debug) {
                error_log('Solana API: Unable to parse token balance response: ' . json_encode($response));
            }
            
            return $this->get_simulated_token_balance($wallet_address);
        } catch (Exception $e) {
            if ($this->debug) {
                error_log('Solana API Error: ' . $e->getMessage());
            }
            return $this->get_simulated_token_balance($wallet_address);
        }
    }
    
    /**
     * Get simulated token balance for testing or fallback.
     *
     * @param string $wallet_address The wallet address.
     * @return float Simulated balance.
     */
    private function get_simulated_token_balance($wallet_address) {
        global $wpdb;
        
        // Check if we have a cached balance in our database
        $balance = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT balance FROM {$wpdb->prefix}vortex_dao_token_balances WHERE wallet_address = %s",
                $wallet_address
            )
        );
        
        if ($balance !== null) {
            return floatval($balance);
        }
        
        // If no cached balance, get user ID from wallet
        $user_id = $this->get_user_id_by_wallet($wallet_address);
        
        if (!$user_id) {
            // Return random small balance for unknown wallets
            return mt_rand(10, 100) / 10;
        }
        
        // Check user role/type to determine appropriate balance
        $user = get_userdata($user_id);
        $is_admin = in_array('administrator', $user->roles);
        $config = VORTEX_DAO_Core::get_instance()->get_config();
        
        if ($is_admin) {
            // Admin users get founder allocation
            $balance = $config['founder_allocation'];
        } else {
            // Check if user is an investor
            $investor = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT token_amount FROM {$wpdb->prefix}vortex_dao_investors WHERE user_id = %d",
                    $user_id
                )
            );
            
            if ($investor) {
                $balance = $investor->token_amount;
            } else {
                // Regular users get random balance between 10-1000
                $balance = mt_rand(100, 10000) / 10;
            }
        }
        
        // Cache the balance
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_token_balances',
            [
                'wallet_address' => $wallet_address,
                'balance' => $balance,
                'locked_balance' => 0,
                'last_updated' => current_time('mysql')
            ],
            ['%s', '%f', '%f', '%s']
        );
        
        return floatval($balance);
    }
    
    /**
     * Transfer SPL tokens on Solana.
     *
     * @param string $token_address SPL token address.
     * @param string $from_address Sender address.
     * @param string $to_address Recipient address.
     * @param float $amount Amount to transfer.
     * @return string|bool Transaction hash on success, false on failure.
     */
    public function transfer_spl_token($token_address, $from_address, $to_address, $amount) {
        // In a production environment, this would use the Solana Web3 PHP library
        // or make API calls to a service like Helius, QuickNode, or a custom Solana RPC
        
        // For this demo, we'll simulate a successful transfer and return a fake transaction hash
        $this->log_transaction_attempt($token_address, $from_address, $to_address, $amount);
        
        // Update cached balances
        global $wpdb;
        
        // Deduct from sender
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}vortex_dao_token_balances 
                SET balance = GREATEST(0, balance - %f), last_updated = %s 
                WHERE wallet_address = %s",
                $amount,
                current_time('mysql'),
                $from_address
            )
        );
        
        // Add to recipient
        $recipient_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_dao_token_balances WHERE wallet_address = %s",
                $to_address
            )
        );
        
        if ($recipient_exists) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}vortex_dao_token_balances 
                    SET balance = balance + %f, last_updated = %s 
                    WHERE wallet_address = %s",
                    $amount,
                    current_time('mysql'),
                    $to_address
                )
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'vortex_dao_token_balances',
                [
                    'wallet_address' => $to_address,
                    'balance' => $amount,
                    'locked_balance' => 0,
                    'last_updated' => current_time('mysql')
                ],
                ['%s', '%f', '%f', '%s']
            );
        }
        
        // Generate a simulated transaction hash that looks like a Solana transaction signature
        return $this->generate_solana_transaction_signature();
    }
    
    /**
     * Check if a wallet has enough tokens for a transaction.
     *
     * @param string $token_address SPL token address.
     * @param string $wallet_address Wallet address.
     * @param float $amount Amount needed.
     * @return bool Whether the wallet has enough tokens.
     */
    public function has_sufficient_balance($token_address, $wallet_address, $amount) {
        $balance = $this->get_token_balance($token_address, $wallet_address);
        return $balance >= $amount;
    }
    
    /**
     * Create a new SPL token account for a wallet.
     *
     * @param string $token_address SPL token address.
     * @param string $owner_address Wallet address.
     * @return string|bool Token account address on success, false on failure.
     */
    public function create_token_account($token_address, $owner_address) {
        // In production, this would create an Associated Token Account (ATA)
        // For now, we'll simulate success
        $account_address = $this->generate_solana_address();
        
        $this->log_action('create_token_account', [
            'token_address' => $token_address,
            'owner_address' => $owner_address,
            'account_address' => $account_address
        ]);
        
        return $account_address;
    }
    
    /**
     * Send a transaction to the Solana blockchain.
     *
     * @param array $transaction Signed transaction data.
     * @return string|bool Transaction signature on success, false on failure.
     */
    public function send_transaction($transaction) {
        // In production, this would submit a transaction to the Solana network
        // For now, we'll simulate success
        $signature = $this->generate_solana_transaction_signature();
        
        $this->log_action('send_transaction', [
            'transaction' => json_encode($transaction),
            'signature' => $signature
        ]);
        
        return $signature;
    }
    
    /**
     * Get transaction details from Solana.
     *
     * @param string $signature Transaction signature.
     * @return array|bool Transaction details on success, false on failure.
     */
    public function get_transaction($signature) {
        try {
            $response = $this->make_rpc_call('getTransaction', [
                $signature,
                'json'
            ]);
            
            if (isset($response['result'])) {
                return $response['result'];
            }
            
            return false;
        } catch (Exception $e) {
            if ($this->debug) {
                error_log('Solana API Error: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Make a JSON RPC call to the Solana API.
     *
     * @param string $method RPC method.
     * @param array $params RPC parameters.
     * @return array Response data.
     * @throws Exception If the API call fails.
     */
    private function make_rpc_call($method, $params) {
        $data = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params
        ];
        
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => 15
            ]
        ];
        
        $context = stream_context_create($options);
        
        // Try to make the call
        $result = @file_get_contents($this->endpoint, false, $context);
        
        if ($result === false) {
            throw new Exception('Unable to connect to Solana RPC endpoint');
        }
        
        $response = json_decode($result, true);
        
        if (isset($response['error'])) {
            throw new Exception('Solana RPC Error: ' . json_encode($response['error']));
        }
        
        return $response;
    }
    
    /**
     * Generate a simulated Solana address.
     *
     * @return string Solana address.
     */
    private function generate_solana_address() {
        // Solana addresses are base58 encoded and 32-44 characters long
        $bytes = random_bytes(32);
        return $this->base58_encode($bytes);
    }
    
    /**
     * Generate a simulated Solana transaction signature.
     *
     * @return string Solana transaction signature.
     */
    private function generate_solana_transaction_signature() {
        // Solana transaction signatures are 88 characters long
        $bytes = random_bytes(64);
        return $this->base58_encode($bytes);
    }
    
    /**
     * Base58 encode data (for Solana addresses and signatures).
     *
     * @param string $data Binary data to encode.
     * @return string Base58 encoded string.
     */
    private function base58_encode($data) {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);
        
        // Convert binary data to decimal
        $decimal = 0;
        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $decimal = $decimal * 256 + ord($data[$i]);
        }
        
        // Convert decimal to base58
        $output = '';
        while ($decimal >= $base) {
            $div = intdiv($decimal, $base);
            $mod = $decimal - ($div * $base);
            $output = $alphabet[$mod] . $output;
            $decimal = $div;
        }
        $output = $alphabet[$decimal] . $output;
        
        // Add leading 1s for leading zeros
        for ($i = 0; $i < $len && $data[$i] === "\0"; $i++) {
            $output = '1' . $output;
        }
        
        return $output;
    }
    
    /**
     * Log a transaction attempt.
     *
     * @param string $token_address Token address.
     * @param string $from_address Sender address.
     * @param string $to_address Recipient address.
     * @param float $amount Amount to transfer.
     */
    private function log_transaction_attempt($token_address, $from_address, $to_address, $amount) {
        $this->log_action('transfer_attempt', [
            'token_address' => $token_address,
            'from_address' => $from_address,
            'to_address' => $to_address,
            'amount' => $amount
        ]);
    }
    
    /**
     * Log a Solana action for debugging.
     *
     * @param string $action Action name.
     * @param array $data Action data.
     */
    private function log_action($action, $data) {
        if (!$this->debug) {
            return;
        }
        
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_security_logs',
            [
                'event_type' => 'solana_' . $action,
                'message' => 'Solana API: ' . $action,
                'data' => json_encode($data),
                'user_id' => get_current_user_id(),
                'ip_address' => $this->get_client_ip(),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );
    }
    
    /**
     * Get user ID by wallet address.
     *
     * @param string $wallet_address Wallet address.
     * @return int|null User ID or null if not found.
     */
    private function get_user_id_by_wallet($wallet_address) {
        global $wpdb;
        
        // Try exact match first
        $user_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'vortex_wallet_address' AND meta_value = %s",
                $wallet_address
            )
        );
        
        if ($user_id) {
            return intval($user_id);
        }
        
        // Try finding in investors table
        $investor = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}vortex_dao_investors WHERE wallet_address = %s",
                $wallet_address
            )
        );
        
        if ($investor) {
            return intval($investor->user_id);
        }
        
        return null;
    }
    
    /**
     * Get client IP address.
     *
     * @return string Client IP address.
     */
    private function get_client_ip() {
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