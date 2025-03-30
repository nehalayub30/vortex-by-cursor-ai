<?php
/**
 * Vortex Wallet Connect
 *
 * Handles the connection to blockchain wallets for the Vortex AI Marketplace.
 *
 * @package VortexAI
 * @subpackage Blockchain
 * @since 1.0.0
 */

namespace VortexAI\Blockchain;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wallet Connect class.
 *
 * Manages cryptocurrency wallet connections for the Vortex marketplace.
 * Supports Phantom, Solflare, and other Solana wallets.
 */
class Wallet_Connect {
    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var object
     */
    private static $instance = null;

    /**
     * Connected wallet address
     *
     * @since 1.0.0
     * @var string
     */
    private $wallet_address = '';

    /**
     * Connected wallet type
     *
     * @since 1.0.0
     * @var string
     */
    private $wallet_type = '';

    /**
     * Connection status
     *
     * @since 1.0.0
     * @var bool
     */
    private $is_connected = false;

    /**
     * Get a single instance of this class.
     *
     * @since 1.0.0
     * @return Wallet_Connect
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        // Register shortcodes
        add_shortcode('vortex_wallet_connect_button', array($this, 'render_connect_button'));
        
        // Register AJAX handlers
        add_action('wp_ajax_vortex_connect_wallet', array($this, 'ajax_connect_wallet'));
        add_action('wp_ajax_nopriv_vortex_connect_wallet', array($this, 'ajax_connect_wallet'));
        add_action('wp_ajax_vortex_disconnect_wallet', array($this, 'ajax_disconnect_wallet'));
        
        // Enqueue necessary scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue scripts and styles.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts() {
        // Only enqueue on pages where wallet connection is needed
        if (!$this->is_wallet_page()) {
            return;
        }

        wp_enqueue_script(
            'vortex-wallet-connect',
            plugins_url('assets/js/wallet-connect.js', dirname(dirname(__FILE__))),
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script(
            'vortex-wallet-connect',
            'vortexWalletConnect',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vortex-wallet-connect-nonce'),
                'connectingText' => __('Connecting...', 'vortex-ai-agents'),
                'connectedText' => __('Connected', 'vortex-ai-agents'),
                'disconnectText' => __('Disconnect', 'vortex-ai-agents'),
                'errorText' => __('Connection Error', 'vortex-ai-agents'),
            )
        );

        wp_enqueue_style(
            'vortex-wallet-connect',
            plugins_url('assets/css/wallet-connect.css', dirname(dirname(__FILE__))),
            array(),
            '1.0.0'
        );
    }

    /**
     * Check if current page needs wallet functionality.
     *
     * @since 1.0.0
     * @return bool Whether the current page needs wallet connection functionality.
     */
    private function is_wallet_page() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Check if the content contains the shortcode
        if (has_shortcode($post->post_content, 'vortex_wallet_connect_button')) {
            return true;
        }
        
        // Add additional checks as needed
        return false;
    }

    /**
     * Render wallet connect button.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_connect_button($atts) {
        $atts = shortcode_atts(
            array(
                'text' => __('Connect Wallet', 'vortex-ai-agents'),
                'class' => 'vortex-wallet-connect-btn',
            ),
            $atts,
            'vortex_wallet_connect_button'
        );

        ob_start();
        ?>
        <div class="vortex-wallet-connect-container">
            <button id="vortex-wallet-connect" class="<?php echo esc_attr($atts['class']); ?>" data-status="disconnected">
                <?php echo esc_html($atts['text']); ?>
            </button>
            <div id="vortex-wallet-info" class="vortex-wallet-info" style="display: none;">
                <span class="vortex-wallet-address"></span>
                <span class="vortex-wallet-balance"></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler for wallet connection.
     *
     * @since 1.0.0
     */
    public function ajax_connect_wallet() {
        check_ajax_referer('vortex-wallet-connect-nonce', 'nonce');

        $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
        $wallet_type = isset($_POST['wallet_type']) ? sanitize_text_field($_POST['wallet_type']) : '';

        if (empty($wallet_address)) {
            wp_send_json_error(array('message' => __('Invalid wallet address', 'vortex-ai-agents')));
            return;
        }

        // Store wallet info in user meta if user is logged in
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            update_user_meta($user_id, 'vortex_wallet_address', $wallet_address);
            update_user_meta($user_id, 'vortex_wallet_type', $wallet_type);
        }

        // Store in session for non-logged in users
        $_SESSION['vortex_wallet_address'] = $wallet_address;
        $_SESSION['vortex_wallet_type'] = $wallet_type;

        // Update internal properties
        $this->wallet_address = $wallet_address;
        $this->wallet_type = $wallet_type;
        $this->is_connected = true;

        // Process successful connection
        do_action('vortex_wallet_connected', $wallet_address, $wallet_type);

        wp_send_json_success(array(
            'message' => __('Wallet connected successfully', 'vortex-ai-agents'),
            'wallet_address' => $this->format_wallet_address($wallet_address),
        ));
    }

    /**
     * AJAX handler for wallet disconnection.
     *
     * @since 1.0.0
     */
    public function ajax_disconnect_wallet() {
        check_ajax_referer('vortex-wallet-connect-nonce', 'nonce');

        // Clear user meta if user is logged in
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            delete_user_meta($user_id, 'vortex_wallet_address');
            delete_user_meta($user_id, 'vortex_wallet_type');
        }

        // Clear session data
        if (isset($_SESSION['vortex_wallet_address'])) {
            unset($_SESSION['vortex_wallet_address']);
        }
        if (isset($_SESSION['vortex_wallet_type'])) {
            unset($_SESSION['vortex_wallet_type']);
        }

        // Update internal properties
        $this->wallet_address = '';
        $this->wallet_type = '';
        $this->is_connected = false;

        do_action('vortex_wallet_disconnected');

        wp_send_json_success(array(
            'message' => __('Wallet disconnected successfully', 'vortex-ai-agents'),
        ));
    }

    /**
     * Format wallet address for display (truncate middle).
     *
     * @since 1.0.0
     * @param string $address Full wallet address.
     * @return string Formatted address.
     */
    private function format_wallet_address($address) {
        if (strlen($address) <= 12) {
            return $address;
        }

        $prefix = substr($address, 0, 6);
        $suffix = substr($address, -4);

        return $prefix . '...' . $suffix;
    }

    /**
     * Check if wallet is connected.
     *
     * @since 1.0.0
     * @return bool Whether a wallet is connected.
     */
    public function is_wallet_connected() {
        return $this->is_connected;
    }

    /**
     * Get connected wallet address.
     *
     * @since 1.0.0
     * @param bool $formatted Whether to return a formatted address.
     * @return string Wallet address.
     */
    public function get_wallet_address($formatted = false) {
        if (empty($this->wallet_address)) {
            return '';
        }

        return $formatted ? $this->format_wallet_address($this->wallet_address) : $this->wallet_address;
    }

    /**
     * Get connected wallet type.
     *
     * @since 1.0.0
     * @return string Wallet type (e.g., 'phantom', 'solflare').
     */
    public function get_wallet_type() {
        return $this->wallet_type;
    }

    /**
     * Verify signature for wallet authentication.
     *
     * @since 1.0.0
     * @param string $message The message that was signed.
     * @param string $signature The signature to verify.
     * @param string $public_key The public key to verify against.
     * @return bool Whether the signature is valid.
     */
    public function verify_signature($message, $signature, $public_key) {
        // Implement signature verification logic
        // This would typically involve using a library to verify Ed25519 signatures for Solana
        
        // Placeholder for actual implementation
        return true;
    }
}

// Initialize the wallet connector
function vortex_wallet_connect() {
    return Wallet_Connect::get_instance();
}

// Create global accessor function
if (!function_exists('vortex_wallet')) {
    function vortex_wallet() {
        return vortex_wallet_connect();
    }
} 