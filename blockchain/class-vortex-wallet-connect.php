<?php
/**
 * Wallet Connection Handler Class
 *
 * Manages blockchain wallet connections for the VORTEX AI Marketplace
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/blockchain
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Wallet Connection Handler Class
 *
 * This class handles wallet connectivity, authentication,
 * signatures, and user wallet management.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/blockchain
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Wallet_Connect {

    /**
     * The blockchain integration instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Blockchain_Integration $blockchain The blockchain integration instance.
     */
    protected $blockchain;

    /**
     * The supported wallet providers.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array $supported_wallets List of supported wallet providers.
     */
    protected $supported_wallets;

    /**
     * The selected blockchain network.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $network Selected blockchain network.
     */
    protected $network;

    /**
     * Network configuration details.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array $network_config Network configuration details.
     */
    protected $network_config;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    Vortex_Blockchain_Integration $blockchain The blockchain integration instance.
     */
    public function __construct($blockchain) {
        $this->blockchain = $blockchain;
        $this->load_wallet_settings();
        $this->register_hooks();
    }

    /**
     * Load wallet and network settings from WordPress options.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_wallet_settings() {
        $this->network = get_option('vortex_blockchain_network', 'solana');
        
        // Define supported wallets by network
        $this->supported_wallets = array(
            'ethereum' => array(
                'metamask' => array(
                    'name' => 'MetaMask',
                    'icon' => 'metamask.svg',
                    'description' => __('Connect using MetaMask browser extension', 'vortex-ai-marketplace')
                ),
                'walletconnect' => array(
                    'name' => 'WalletConnect',
                    'icon' => 'walletconnect.svg',
                    'description' => __('Connect using WalletConnect compatible wallets', 'vortex-ai-marketplace')
                )
            ),
            'solana' => array(
                'phantom' => array(
                    'name' => 'Phantom',
                    'icon' => 'phantom.svg',
                    'description' => __('Connect using Phantom wallet extension', 'vortex-ai-marketplace')
                ),
                'solflare' => array(
                    'name' => 'Solflare',
                    'icon' => 'solflare.svg',
                    'description' => __('Connect using Solflare wallet extension', 'vortex-ai-marketplace')
                )
            ),
            'polygon' => array(
                'metamask' => array(
                    'name' => 'MetaMask',
                    'icon' => 'metamask.svg',
                    'description' => __('Connect using MetaMask browser extension', 'vortex-ai-marketplace')
                ),
                'walletconnect' => array(
                    'name' => 'WalletConnect',
                    'icon' => 'walletconnect.svg',
                    'description' => __('Connect using WalletConnect compatible wallets', 'vortex-ai-marketplace')
                )
            )
        );
        
        // Define network configurations
        $this->network_config = array(
            'ethereum' => array(
                'chain_id' => get_option('vortex_ethereum_chain_id', '1'),
                'rpc_url' => get_option('vortex_ethereum_rpc_url', 'https://mainnet.infura.io/v3/your-infura-key'),
                'explorer_url' => get_option('vortex_ethereum_explorer_url', 'https://etherscan.io')
            ),
            'solana' => array(
                'network' => get_option('vortex_solana_network', 'mainnet-beta'),
                'rpc_url' => get_option('vortex_solana_rpc_url', 'https://api.mainnet-beta.solana.com'),
                'explorer_url' => get_option('vortex_solana_explorer_url', 'https://solscan.io')
            ),
            'polygon' => array(
                'chain_id' => get_option('vortex_polygon_chain_id', '137'),
                'rpc_url' => get_option('vortex_polygon_rpc_url', 'https://polygon-rpc.com'),
                'explorer_url' => get_option('vortex_polygon_explorer_url', 'https://polygonscan.com')
            )
        );
    }

    /**
     * Register hooks related to wallet connectivity.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_hooks() {
        // AJAX handlers for wallet operations
        add_action('wp_ajax_vortex_connect_wallet', array($this, 'ajax_connect_wallet'));
        add_action('wp_ajax_nopriv_vortex_connect_wallet', array($this, 'ajax_connect_wallet'));
        
        add_action('wp_ajax_vortex_disconnect_wallet', array($this, 'ajax_disconnect_wallet'));
        
        add_action('wp_ajax_vortex_verify_wallet_ownership', array($this, 'ajax_verify_wallet_ownership'));
        add_action('wp_ajax_nopriv_vortex_verify_wallet_ownership', array($this, 'ajax_verify_wallet_ownership'));
        
        add_action('wp_ajax_vortex_get_wallet_details', array($this, 'ajax_get_wallet_details'));
        
        // Add wallet data to user profiles
    }
} 