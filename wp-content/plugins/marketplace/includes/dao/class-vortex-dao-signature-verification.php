<?php
/**
 * VORTEX DAO Signature Verification
 * 
 * Manages digital signatures for investment agreements and critical transactions.
 *
 * @package VORTEX
 */

class VORTEX_DAO_Signature_Verification {
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
        $this->config = VORTEX_DAO_Core::get_instance()->get_config();
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
     * Initialize hooks.
     */
    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_vortex_sign_investment_agreement', [$this, 'ajax_sign_investment_agreement']);
        add_action('wp_ajax_vortex_verify_signature', [$this, 'ajax_verify_signature']);
        
        // Filters
        add_filter('vortex_dao_verify_transaction', [$this, 'verify_transaction_signature'], 10, 3);
    }
    
    /**
     * Generate a message for signing.
     *
     * @param array $data Data to sign.
     * @return string Formatted message for signing.
     */
    public function generate_message_to_sign($data) {
        // Create a structured message with important data that needs to be signed
        $message = "VORTEX DAO Transaction\n";
        $message .= "====================\n";
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $message .= "{$key}: " . json_encode($value) . "\n";
            } else {
                $message .= "{$key}: {$value}\n";
            }
        }
        
        $message .= "====================\n";
        $message .= "Timestamp: " . time() . "\n";
        $message .= "Domain: " . site_url() . "\n";
        
        return $message;
    }
    
    /**
     * Verify a Solana signature.
     *
     * @param string $message     The original message that was signed.
     * @param string $signature   The signature to verify.
     * @param string $address     The Solana address that supposedly signed the message.
     * @return bool Whether the signature is valid.
     */
    public function verify_signature($message, $signature, $address) {
        // Solana signature verification requires different approach
        // than EVM-based chains
        
        // For production, use a Solana library or API service to verify
        // Solana signatures which use ed25519 rather than secp256k1
        
        // Example of API call to verify signature:
        $verification_result = $this->call_solana_verification_api($message, $signature, $address);
        
        // Log the verification attempt
        $this->log_verification_attempt($message, $signature, $address);
        
        return $verification_result;
    }
    
    /**
     * Call Solana verification API.
     * 
     * @param string $message   The message that was signed
     * @param string $signature The signature
     * @param string $address   The Solana address
     * @return bool Whether signature is valid
     */
    private function call_solana_verification_api($message, $signature, $address) {
        // This would be implemented with a proper API call or library
        // For demonstration purposes
        
        // In a real implementation, you would use a Solana library or web3 API to verify
        $verification_result = false;
        
        try {
            // Example API call
            $response = wp_remote_post('https://your-solana-verification-api.com/verify', [
                'body' => [
                    'message' => $message,
                    'signature' => $signature,
                    'publicKey' => $address
                ]
            ]);
            
            if (!is_wp_error($response)) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $verification_result = isset($body['valid']) ? $body['valid'] : false;
            }
        } catch (Exception $e) {
            error_log('Solana signature verification error: ' . $e->getMessage());
        }
        
        return $verification_result;
    }
    
    /**
     * Log a signature verification attempt.
     *
     * @param string $message   The message that was signed.
     * @param string $signature The signature.
     * @param string $address   The address that supposedly signed the message.
     */
    private function log_verification_attempt($message, $signature, $address) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_security_logs',
            [
                'event_type' => 'signature_verification',
                'message' => sprintf('Verified signature for address %s', $address),
                'data' => json_encode([
                    'message' => $message,
                    'signature' => $signature,
                    'address' => $address,
                    'timestamp' => time(),
                    'ip_address' => $this->get_client_ip()
                ]),
                'user_id' => get_current_user_id(),
                'ip_address' => $this->get_client_ip(),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );
    }
    
    /**
     * Verify a transaction signature.
     *
     * @param bool   $valid       Whether the transaction is currently considered valid.
     * @param string $transaction Transaction type.
     * @param array  $data        Transaction data.
     * @return bool Whether the transaction should be considered valid.
     */
    public function verify_transaction_signature($valid, $transaction, $data) {
        // If the transaction is already invalid, don't override that decision
        if (!$valid) {
            return false;
        }
        
        // These transaction types require signature verification
        $signature_required_transactions = [
            'add_investor',
            'transfer_equity',
            'distribute_revenue',
            'change_governance',
            'execute_proposal'
        ];
        
        if (in_array($transaction, $signature_required_transactions)) {
            // Check if signature data is provided
            if (!isset($data['signature']) || !isset($data['address'])) {
                // Log this security event
                global $wpdb;
                $wpdb->insert(
                    $wpdb->prefix . 'vortex_dao_security_logs',
                    [
                        'event_type' => 'signature_missing',
                        'message' => sprintf('Missing signature for %s transaction', $transaction),
                        'data' => json_encode($data),
                        'user_id' => get_current_user_id(),
                        'ip_address' => $this->get_client_ip(),
                        'created_at' => current_time('mysql')
                    ],
                    ['%s', '%s', '%s', '%d', '%s', '%s']
                );
                return false;
            }
            
            // Generate the message that should have been signed
            $message = $this->generate_message_to_sign($data);
            
            // Verify the signature
            if (!$this->verify_signature($message, $data['signature'], $data['address'])) {
                // Log this security event
                global $wpdb;
                $wpdb->insert(
                    $wpdb->prefix . 'vortex_dao_security_logs',
                    [
                        'event_type' => 'invalid_signature',
                        'message' => sprintf('Invalid signature for %s transaction from address %s', $transaction, $data['address']),
                        'data' => json_encode([
                            'transaction' => $transaction,
                            'data' => $data,
                            'message' => $message
                        ]),
                        'user_id' => get_current_user_id(),
                        'ip_address' => $this->get_client_ip(),
                        'created_at' => current_time('mysql')
                    ],
                    ['%s', '%s', '%s', '%d', '%s', '%s']
                );
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * AJAX: Sign investment agreement.
     */
    public function ajax_sign_investment_agreement() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_dao_sign_investment')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to sign an agreement.', 'vortex')]);
        }
        
        // Get parameters
        $investor_id = isset($_POST['investor_id']) ? intval($_POST['investor_id']) : 0;
        $signature = isset($_POST['signature']) ? sanitize_text_field($_POST['signature']) : '';
        
        if (empty($investor_id) || empty($signature)) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'vortex')]);
        }
        
        // Get investor data
        global $wpdb;
        $investor = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_dao_investors WHERE id = %d",
                $investor_id
            )
        );
        
        if (!$investor) {
            wp_send_json_error(['message' => __('Investor not found.', 'vortex')]);
        }
        
        // Verify the user is the investor or the founder
        $current_user_id = get_current_user_id();
        $is_founder = $this->is_founder_action();
        
        if ($current_user_id != $investor->user_id && !$is_founder) {
            wp_send_json_error(['message' => __('You do not have permission to sign this agreement.', 'vortex')]);
        }
        
        // Store the signature
        $wpdb->update(
            $wpdb->prefix . 'vortex_dao_investors',
            ['agreement_signature' => $signature, 'agreement_signed_at' => current_time('mysql')],
            ['id' => $investor_id],
            ['%s', '%s'],
            ['%d']
        );
        
        // Log the signing event
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_security_logs',
            [
                'event_type' => 'agreement_signed',
                'message' => sprintf('Investment agreement signed for investor ID %d', $investor_id),
                'data' => json_encode([
                    'investor_id' => $investor_id,
                    'signature' => $signature,
                    'user_id' => $current_user_id,
                    'is_founder' => $is_founder
                ]),
                'user_id' => $current_user_id,
                'ip_address' => $this->get_client_ip(),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );
        
        wp_send_json_success([
            'message' => __('Agreement signed successfully.', 'vortex'),
            'investor_id' => $investor_id
        ]);
    }
    
    /**
     * AJAX: Verify a signature.
     */
    public function ajax_verify_signature() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_dao_verify_signature')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Get parameters
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $signature = isset($_POST['signature']) ? sanitize_text_field($_POST['signature']) : '';
        $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
        
        if (empty($message) || empty($signature) || empty($address)) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'vortex')]);
        }
        
        // Verify the signature
        $is_valid = $this->verify_signature($message, $signature, $address);
        
        wp_send_json_success([
            'is_valid' => $is_valid,
            'message' => $is_valid ? __('Signature is valid.', 'vortex') : __('Signature is invalid.', 'vortex')
        ]);
    }
    
    /**
     * Check if current action is performed by the founder.
     *
     * @return bool Whether the action is performed by the founder.
     */
    private function is_founder_action() {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return false;
        }
        
        // Get user wallet address
        $wallet_address = get_user_meta($user_id, 'vortex_wallet_address', true);
        
        // Compare with founder address
        return $wallet_address === $this->config['founder_address'];
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

// Initialize the class
VORTEX_DAO_Signature_Verification::get_instance(); 