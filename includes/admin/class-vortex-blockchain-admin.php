<?php
/**
 * VORTEX AI AGENTS Blockchain Admin
 *
 * @package VortexAiAgents
 */

namespace Vortex\AI\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Blockchain_Admin
 * Handles blockchain settings admin interface
 */
class Blockchain_Admin {

    /**
     * Initialize the class
     */
    public function __construct() {
        // Add menu page
        add_action( 'admin_menu', array( $this, 'add_blockchain_menu' ) );
        
        // Register settings
        add_action( 'admin_init', array( $this, 'register_blockchain_settings' ) );
        
        // Add AJAX handlers
        add_action( 'wp_ajax_vortex_connect_wallet', array( $this, 'ajax_connect_wallet' ) );
        add_action( 'wp_ajax_vortex_mint_nft', array( $this, 'ajax_mint_nft' ) );
        
        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Add blockchain menu
     */
    public function add_blockchain_menu() {
        add_submenu_page(
            'vortex-dashboard',
            __( 'Blockchain Settings', 'vortex-ai-agents' ),
            __( 'Blockchain', 'vortex-ai-agents' ),
            'manage_options',
            'vortex-blockchain',
            array( $this, 'render_blockchain_page' )
        );
    }

    /**
     * Register blockchain settings
     */
    public function register_blockchain_settings() {
        // Register settings
        register_setting( 'vortex_blockchain', 'vortex_blockchain_platform' );
        register_setting( 'vortex_blockchain', 'vortex_platform_wallet' );
        register_setting( 'vortex_blockchain', 'vortex_blockchain_network' );
        register_setting( 'vortex_blockchain', 'vortex_solana_rpc_url' );
        register_setting( 'vortex_blockchain', 'vortex_solana_network' );
        register_setting( 'vortex_blockchain', 'vortex_solana_decimals' );
        register_setting( 'vortex_blockchain', 'vortex_web3_provider_url' );
        
        // New TOLA token settings
        register_setting( 'vortex_blockchain', 'vortex_tola_token_address' );
        register_setting( 'vortex_blockchain', 'vortex_tola_decimals' );
        register_setting( 'vortex_blockchain', 'vortex_tola_metadata_url' );

        // Blockchain settings section
        add_settings_section(
            'vortex_blockchain_section',
            __( 'Blockchain Integration Settings', 'vortex-ai-marketplace' ),
            array( $this, 'blockchain_section_callback' ),
            'vortex_blockchain'
        );

        // TOLA token settings section
        add_settings_section(
            'vortex_tola_section',
            __( 'TOLA Token Settings', 'vortex-ai-marketplace' ),
            array( $this, 'tola_section_callback' ),
            'vortex_blockchain'
        );

        // Add settings fields - General
        add_settings_field(
            'vortex_platform_wallet',
            __( 'Platform Wallet Address', 'vortex-ai-marketplace' ),
            array( $this, 'platform_wallet_callback' ),
            'vortex_blockchain',
            'vortex_blockchain_section'
        );

        add_settings_field(
            'vortex_blockchain_network',
            __( 'Blockchain Network', 'vortex-ai-marketplace' ),
            array( $this, 'blockchain_network_callback' ),
            'vortex_blockchain',
            'vortex_blockchain_section'
        );

        // Add settings fields based on blockchain
        add_settings_field(
            'vortex_solana_rpc_url',
            __( 'Solana RPC URL', 'vortex-ai-marketplace' ),
            array( $this, 'render_solana_rpc_url_field' ),
            'vortex_blockchain',
            'vortex_blockchain_section'
        );

        add_settings_field(
            'vortex_solana_network',
            __( 'Solana Network', 'vortex-ai-marketplace' ),
            array( $this, 'render_solana_network_field' ),
            'vortex_blockchain',
            'vortex_blockchain_section'
        );

        add_settings_field(
            'vortex_solana_decimals',
            __( 'Solana Decimals', 'vortex-ai-marketplace' ),
            array( $this, 'render_solana_decimals_field' ),
            'vortex_blockchain',
            'vortex_blockchain_section'
        );

        add_settings_field(
            'vortex_web3_provider_url',
            __( 'Web3 Provider URL', 'vortex-ai-marketplace' ),
            array( $this, 'render_web3_provider_url_field' ),
            'vortex_blockchain',
            'vortex_blockchain_section'
        );
        
        // Add TOLA token settings fields
        add_settings_field(
            'vortex_tola_token_address',
            __( 'TOLA Token Address', 'vortex-ai-marketplace' ),
            array( $this, 'render_tola_token_address_field' ),
            'vortex_blockchain',
            'vortex_tola_section'
        );
        
        add_settings_field(
            'vortex_tola_decimals',
            __( 'TOLA Token Decimals', 'vortex-ai-marketplace' ),
            array( $this, 'render_tola_decimals_field' ),
            'vortex_blockchain',
            'vortex_tola_section'
        );
        
        add_settings_field(
            'vortex_tola_metadata_url',
            __( 'TOLA Token Metadata URL', 'vortex-ai-marketplace' ),
            array( $this, 'render_tola_metadata_url_field' ),
            'vortex_blockchain',
            'vortex_tola_section'
        );
    }

    /**
     * Blockchain section callback
     */
    public function blockchain_section_callback() {
        echo '<p>' . esc_html__( 'Configure the blockchain settings for NFT minting and royalty management.', 'vortex-ai-agents' ) . '</p>';
    }

    /**
     * Platform wallet callback
     */
    public function platform_wallet_callback() {
        $platform_wallet = get_option( 'vortex_platform_wallet', '' );
        ?>
        <input type="text" name="vortex_platform_wallet" value="<?php echo esc_attr( $platform_wallet ); ?>" class="regular-text" />
        <p class="description">
            <?php esc_html_e( 'The wallet address that will receive the 3% platform royalty from all sales.', 'vortex-ai-agents' ); ?>
        </p>
        <?php
    }

    /**
     * Blockchain network callback
     */
    public function blockchain_network_callback() {
        $network = get_option( 'vortex_blockchain_network', 'ethereum' );
        ?>
        <select name="vortex_blockchain_network">
            <option value="ethereum" <?php selected( $network, 'ethereum' ); ?>><?php esc_html_e( 'Ethereum', 'vortex-ai-agents' ); ?></option>
            <option value="polygon" <?php selected( $network, 'polygon' ); ?>><?php esc_html_e( 'Polygon', 'vortex-ai-agents' ); ?></option>
            <option value="rinkeby" <?php selected( $network, 'rinkeby' ); ?>><?php esc_html_e( 'Rinkeby (Testnet)', 'vortex-ai-agents' ); ?></option>
        </select>
        <p class="description">
            <?php esc_html_e( 'The blockchain network to use for NFT minting and transactions.', 'vortex-ai-agents' ); ?>
        </p>
        <?php
    }

    /**
     * Render the blockchain settings section
     */
    public function render_blockchain_section() {
        echo '<p>' . __( 'Configure the blockchain settings for your marketplace. This allows users to connect their wallets and make transactions.', 'vortex-ai-marketplace' ) . '</p>';
    }

    /**
     * Render the network field
     */
    public function render_network_field() {
        $network = get_option( 'vortex_blockchain_network', 'solana' );
        ?>
        <select id="vortex_blockchain_network" name="vortex_blockchain_network">
            <option value="solana" <?php selected( $network, 'solana' ); ?>><?php esc_html_e( 'Solana Mainnet', 'vortex-ai-marketplace' ); ?></option>
            <option value="solana-devnet" <?php selected( $network, 'solana-devnet' ); ?>><?php esc_html_e( 'Solana Devnet', 'vortex-ai-marketplace' ); ?></option>
            <option value="solana-testnet" <?php selected( $network, 'solana-testnet' ); ?>><?php esc_html_e( 'Solana Testnet', 'vortex-ai-marketplace' ); ?></option>
            <option value="ethereum" <?php selected( $network, 'ethereum' ); ?>><?php esc_html_e( 'Ethereum (Legacy)', 'vortex-ai-marketplace' ); ?></option>
        </select>
        <p class="description"><?php esc_html_e( 'Select the blockchain network to use for your marketplace.', 'vortex-ai-marketplace' ); ?></p>
        <?php
    }

    /**
     * Render the Solana RPC URL field
     */
    public function render_solana_rpc_url_field() {
        $rpc_url = get_option( 'vortex_solana_rpc_url', 'https://api.mainnet-beta.solana.com' );
        ?>
        <input type="text" id="vortex_solana_rpc_url" name="vortex_solana_rpc_url" value="<?php echo esc_attr( $rpc_url ); ?>" class="regular-text" />
        <p class="description">
            <?php esc_html_e( 'Enter the Solana RPC URL. You can use a public endpoint or set up your own.', 'vortex-ai-marketplace' ); ?>
            <br>
            <?php esc_html_e( 'Default Mainnet: https://api.mainnet-beta.solana.com', 'vortex-ai-marketplace' ); ?>
            <br>
            <?php esc_html_e( 'Default Devnet: https://api.devnet.solana.com', 'vortex-ai-marketplace' ); ?>
        </p>
        <?php
    }

    /**
     * Render the Solana Network field
     */
    public function render_solana_network_field() {
        $network = get_option( 'vortex_solana_network', 'mainnet-beta' );
        ?>
        <select id="vortex_solana_network" name="vortex_solana_network">
            <option value="mainnet-beta" <?php selected( $network, 'mainnet-beta' ); ?>><?php esc_html_e( 'Mainnet Beta', 'vortex-ai-marketplace' ); ?></option>
            <option value="devnet" <?php selected( $network, 'devnet' ); ?>><?php esc_html_e( 'Devnet', 'vortex-ai-marketplace' ); ?></option>
            <option value="testnet" <?php selected( $network, 'testnet' ); ?>><?php esc_html_e( 'Testnet', 'vortex-ai-marketplace' ); ?></option>
        </select>
        <p class="description"><?php esc_html_e( 'Select the Solana network to use.', 'vortex-ai-marketplace' ); ?></p>
        <?php
    }

    /**
     * Render the Solana Decimals field
     */
    public function render_solana_decimals_field() {
        $decimals = get_option( 'vortex_solana_decimals', 9 );
        ?>
        <input type="number" id="vortex_solana_decimals" name="vortex_solana_decimals" value="<?php echo esc_attr( $decimals ); ?>" min="0" max="9" />
        <p class="description"><?php esc_html_e( 'Number of decimal places for SOL tokens (default is 9 for Solana).', 'vortex-ai-marketplace' ); ?></p>
        <?php
    }

    /**
     * Render the Web3 Provider URL field (for legacy Ethereum support)
     */
    public function render_web3_provider_url_field() {
        $provider_url = get_option( 'vortex_web3_provider_url', 'https://mainnet.infura.io/v3/your-api-key' );
        ?>
        <input type="text" id="vortex_web3_provider_url" name="vortex_web3_provider_url" value="<?php echo esc_attr( $provider_url ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Enter your Web3 provider URL (e.g., Infura or Alchemy endpoint). Only needed for Ethereum.', 'vortex-ai-marketplace' ); ?></p>
        <?php
    }

    /**
     * Render blockchain page
     */
    public function render_blockchain_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Check if user has wallet connected
        $user_id = get_current_user_id();
        $user_wallet = get_user_meta( $user_id, 'vortex_wallet_address', true );
        
        // Get NFTs associated with the current user
        $nfts = get_posts( array(
            'post_type' => 'vortex_nft',
            'author' => $user_id,
            'posts_per_page' => -1,
        ) );
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <div class="vortex-admin-tabs">
                <div class="vortex-admin-tabs-nav">
                    <a href="#settings" class="active"><?php esc_html_e( 'Settings', 'vortex-ai-agents' ); ?></a>
                    <a href="#wallet"><?php esc_html_e( 'Wallet Integration', 'vortex-ai-agents' ); ?></a>
                    <a href="#nfts"><?php esc_html_e( 'NFTs & Royalties', 'vortex-ai-agents' ); ?></a>
                </div>
                
                <div class="vortex-admin-tab-content active" id="settings">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields( 'vortex_blockchain' );
                        do_settings_sections( 'vortex_blockchain' );
                        submit_button();
                        ?>
                    </form>
                </div>
                
                <div class="vortex-admin-tab-content" id="wallet">
                    <h2><?php esc_html_e( 'Wallet Integration', 'vortex-ai-agents' ); ?></h2>
                    
                    <div class="vortex-wallet-status">
                        <?php if ( empty( $user_wallet ) ) : ?>
                            <div class="vortex-wallet-not-connected">
                                <p><?php esc_html_e( 'You have not connected a blockchain wallet. Connect your wallet to mint NFTs and receive royalties.', 'vortex-ai-agents' ); ?></p>
                                <button type="button" class="button button-primary" id="vortex-connect-wallet">
                                    <?php esc_html_e( 'Connect Wallet', 'vortex-ai-agents' ); ?>
                                </button>
                            </div>
                        <?php else : ?>
                            <div class="vortex-wallet-connected">
                                <p>
                                    <strong><?php esc_html_e( 'Connected Wallet:', 'vortex-ai-agents' ); ?></strong>
                                    <code><?php echo esc_html( $user_wallet ); ?></code>
                                </p>
                                <button type="button" class="button" id="vortex-disconnect-wallet">
                                    <?php esc_html_e( 'Disconnect Wallet', 'vortex-ai-agents' ); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="vortex-network-info">
                        <h3><?php esc_html_e( 'Network Information', 'vortex-ai-agents' ); ?></h3>
                        <p>
                            <strong><?php esc_html_e( 'Current Network:', 'vortex-ai-agents' ); ?></strong>
                            <?php echo esc_html( ucfirst( get_option( 'vortex_blockchain_network', 'ethereum' ) ) ); ?>
                        </p>
                        <p>
                            <strong><?php esc_html_e( 'Smart Contract:', 'vortex-ai-agents' ); ?></strong>
                            <code>0x...</code> <!-- TBD -->
                        </p>
                    </div>
                </div>
                
                <div class="vortex-admin-tab-content" id="nfts">
                    <h2><?php esc_html_e( 'NFTs & Royalties', 'vortex-ai-agents' ); ?></h2>
                    
                    <div class="vortex-mint-nft">
                        <h3><?php esc_html_e( 'Mint NFT', 'vortex-ai-agents' ); ?></h3>
                        
                        <?php if ( empty( $user_wallet ) ) : ?>
                            <p class="vortex-warning">
                                <?php esc_html_e( 'Please connect your wallet before minting NFTs.', 'vortex-ai-agents' ); ?>
                            </p>
                        <?php else : ?>
                            <form id="vortex-mint-form">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><?php esc_html_e( 'Artwork Title', 'vortex-ai-agents' ); ?></th>
                                        <td>
                                            <input type="text" id="nft-title" name="nft-title" class="regular-text" required />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e( 'Description', 'vortex-ai-agents' ); ?></th>
                                        <td>
                                            <textarea id="nft-description" name="nft-description" rows="4" class="large-text"></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e( 'Artwork Image', 'vortex-ai-agents' ); ?></th>
                                        <td>
                                            <input type="text" id="nft-image" name="nft-image" class="regular-text" placeholder="https://..." required />
                                            <button type="button" class="button" id="vortex-select-artwork">
                                                <?php esc_html_e( 'Select Artwork', 'vortex-ai-agents' ); ?>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e( 'Your Royalty (%)', 'vortex-ai-agents' ); ?></th>
                                        <td>
                                            <input type="number" id="nft-royalty" name="nft-royalty" min="0" max="97" step="0.1" value="10" />
                                            <p class="description">
                                                <?php esc_html_e( 'Your royalty percentage on secondary sales (0-97%). This is in addition to the fixed 3% platform royalty.', 'vortex-ai-agents' ); ?>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e( 'Collaborators', 'vortex-ai-agents' ); ?></th>
                                        <td>
                                            <div id="vortex-collaborators">
                                                <!-- Collaborator fields will be added here -->
                                            </div>
                                            <button type="button" class="button" id="vortex-add-collaborator">
                                                <?php esc_html_e( 'Add Collaborator', 'vortex-ai-agents' ); ?>
                                            </button>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p class="royalty-summary">
                                    <strong><?php esc_html_e( 'Royalty Summary:', 'vortex-ai-agents' ); ?></strong>
                                    <span class="platform-royalty">3% VORTEX AI AGENTS Platform</span>
                                    <span class="creator-royalty">10% Creator (You)</span>
                                    <span class="total-royalty">13% Total</span>
                                </p>
                                
                                <p>
                                    <input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Mint NFT', 'vortex-ai-agents' ); ?>" />
                                </p>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <div class="vortex-nft-list">
                        <h3><?php esc_html_e( 'Your NFTs', 'vortex-ai-agents' ); ?></h3>
                        
                        <?php if ( empty( $nfts ) ) : ?>
                            <p><?php esc_html_e( 'You have not minted any NFTs yet.', 'vortex-ai-agents' ); ?></p>
                        <?php else : ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Artwork', 'vortex-ai-agents' ); ?></th>
                                        <th><?php esc_html_e( 'Title', 'vortex-ai-agents' ); ?></th>
                                        <th><?php esc_html_e( 'Token ID', 'vortex-ai-agents' ); ?></th>
                                        <th><?php esc_html_e( 'Royalties', 'vortex-ai-agents' ); ?></th>
                                        <th><?php esc_html_e( 'Actions', 'vortex-ai-agents' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $nfts as $nft ) : 
                                        $token_id = get_post_meta( $nft->ID, 'vortex_nft_token_id', true );
                                        $metadata = get_post_meta( $nft->ID, 'vortex_nft_metadata', true );
                                        $royalties = get_post_meta( $nft->ID, 'vortex_nft_royalties', true );
                                        
                                        $image = '';
                                        if ( ! empty( $metadata['image'] ) ) {
                                            $image = $metadata['image'];
                                        }
                                        
                                        // Calculate total royalty
                                        $total_royalty = 0;
                                        if ( ! empty( $royalties ) && is_array( $royalties ) ) {
                                            foreach ( $royalties as $royalty ) {
                                                $total_royalty += floatval( $royalty['percentage'] );
                                            }
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <?php if ( ! empty( $image ) ) : ?>
                                                    <img src="<?php echo esc_url( $image ); ?>" width="50" height="50" />
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo esc_html( $nft->post_title ); ?></td>
                                            <td><code><?php echo esc_html( $token_id ); ?></code></td>
                                            <td><?php echo esc_html( number_format( $total_royalty, 1 ) . '%' ); ?></td>
                                            <td>
                                                <a href="#" class="view-nft" data-id="<?php echo esc_attr( $nft->ID ); ?>">
                                                    <?php esc_html_e( 'View Details', 'vortex-ai-agents' ); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts( $hook ) {
        if ( 'vortex-dashboard_page_vortex-blockchain' !== $hook ) {
            return;
        }

        wp_enqueue_script(
            'vortex-blockchain-admin',
            plugin_dir_url( __FILE__ ) . 'js/blockchain-admin.js',
            array( 'jquery' ),
            VORTEX_VERSION,
            true
        );

        // Web3 library for wallet connection
        wp_enqueue_script(
            'web3',
            'https://cdn.jsdelivr.net/npm/web3@1.7.4/dist/web3.min.js',
            array(),
            '1.7.4',
            true
        );

        wp_localize_script(
            'vortex-blockchain-admin',
            'vortexBlockchain',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'vortex_blockchain_nonce' ),
                'network' => get_option( 'vortex_blockchain_network', 'ethereum' ),
                'contractAddress' => '0x...', // TBD
            )
        );
    }

    /**
     * AJAX handler for wallet connection
     */
    public function ajax_connect_wallet() {
        check_ajax_referer( 'vortex_blockchain_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vortex-ai-agents' ) ) );
        }

        $wallet_address = isset( $_POST['wallet_address'] ) ? sanitize_text_field( $_POST['wallet_address'] ) : '';

        if ( empty( $wallet_address ) ) {
            wp_send_json_error( array( 'message' => __( 'Wallet address is required.', 'vortex-ai-agents' ) ) );
        }

        // Initialize blockchain service
        $blockchain = new \Vortex\AI\Blockchain\Blockchain();
        $result = $blockchain->connect_wallet( $wallet_address );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Wallet connected successfully.', 'vortex-ai-agents' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to connect wallet.', 'vortex-ai-agents' ) ) );
        }
    }

    /**
     * AJAX handler for NFT minting
     */
    public function ajax_mint_nft() {
        check_ajax_referer( 'vortex_blockchain_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vortex-ai-agents' ) ) );
        }

        $user_id = get_current_user_id();
        $wallet_address = get_user_meta( $user_id, 'vortex_wallet_address', true );

        if ( empty( $wallet_address ) ) {
            wp_send_json_error( array( 'message' => __( 'You must connect a wallet before minting NFTs.', 'vortex-ai-agents' ) ) );
        }

        $title = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
        $description = isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '';
        $image = isset( $_POST['image'] ) ? esc_url_raw( $_POST['image'] ) : '';
        $creator_royalty = isset( $_POST['royalty'] ) ? floatval( $_POST['royalty'] ) : 0;
        $collaborators = isset( $_POST['collaborators'] ) ? json_decode( stripslashes( $_POST['collaborators'] ), true ) : array();

        if ( empty( $title ) || empty( $image ) ) {
            wp_send_json_error( array( 'message' => __( 'Title and image are required.', 'vortex-ai-agents' ) ) );
        }

        // Initialize blockchain service
        $blockchain = new \Vortex\AI\Blockchain\Blockchain();

        // Prepare metadata
        $metadata = array(
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'creator_wallet' => $wallet_address,
            'creator_royalty' => $creator_royalty
        );

        // Add collaborators if any
        if ( ! empty( $collaborators ) ) {
            $royalty_shares = array();
            foreach ( $collaborators as $collaborator ) {
                if ( ! empty( $collaborator['wallet'] ) && ! empty( $collaborator['percentage'] ) ) {
                    $royalty_shares[] = array(
                        'wallet' => sanitize_text_field( $collaborator['wallet'] ),
                        'percentage' => floatval( $collaborator['percentage'] )
                    );
                }
            }
            $metadata['royalty_shares'] = $royalty_shares;
        }

        // Mint NFT
        $token_id = $blockchain->mint_nft( $metadata );

        if ( ! empty( $token_id ) ) {
            wp_send_json_success( array(
                'message' => __( 'NFT minted successfully.', 'vortex-ai-agents' ),
                'token_id' => $token_id
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to mint NFT.', 'vortex-ai-agents' ) ) );
        }
    }

    /**
     * TOLA token section callback
     */
    public function tola_section_callback() {
        echo '<p>' . esc_html__( 'Configure the TOLA token settings for the marketplace. TOLA (Token of Love and Appreciation) is the native utility token used for transactions in the marketplace.', 'vortex-ai-marketplace' ) . '</p>';
    }

    /**
     * Render the TOLA Token Address field
     */
    public function render_tola_token_address_field() {
        $token_address = get_option( 'vortex_tola_token_address', 'H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky' );
        ?>
        <input type="text" id="vortex_tola_token_address" name="vortex_tola_token_address" value="<?php echo esc_attr( $token_address ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Enter the Solana address of the TOLA token mint.', 'vortex-ai-marketplace' ); ?></p>
        <?php
    }

    /**
     * Render the TOLA Token Decimals field
     */
    public function render_tola_decimals_field() {
        $decimals = get_option( 'vortex_tola_decimals', 9 );
        ?>
        <input type="number" id="vortex_tola_decimals" name="vortex_tola_decimals" value="<?php echo esc_attr( $decimals ); ?>" min="0" max="18" />
        <p class="description"><?php esc_html_e( 'Number of decimal places for TOLA tokens (default is 9 for Solana SPL tokens).', 'vortex-ai-marketplace' ); ?></p>
        <?php
    }

    /**
     * Render the TOLA Token Metadata URL field
     */
    public function render_tola_metadata_url_field() {
        $metadata_url = get_option( 'vortex_tola_metadata_url', '' );
        ?>
        <input type="text" id="vortex_tola_metadata_url" name="vortex_tola_metadata_url" value="<?php echo esc_attr( $metadata_url ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Enter the URL for the TOLA token metadata (optional).', 'vortex-ai-marketplace' ); ?></p>
        <?php
    }
} 