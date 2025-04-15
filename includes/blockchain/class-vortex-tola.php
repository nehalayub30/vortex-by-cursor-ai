<?php
/**
 * Solana TOLA Token Integration
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/blockchain
 * @author     Marianne Nems
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Solana TOLA Token Integration Class.
 *
 * This class integrates native Solana TOLA token functionality with the WordPress plugin.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/blockchain
 * @author     Marianne Nems
 */
class Vortex_TOLA {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The blockchain integration instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Blockchain_Integration    $blockchain    The blockchain integration instance.
     */
    private $blockchain;

    /**
     * The wallet connection instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Wallet_Connect    $wallet_connect    The wallet connection instance.
     */
    private $wallet_connect;

    /**
     * The TOLA token contract address.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $contract_address    The token contract address.
     */
    private $contract_address;

    /**
     * The TOLA token contract ABI.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $contract_abi    The token contract ABI.
     */
    private $contract_abi;

    /**
     * The TOLA token decimals.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $decimals    The token decimals.
     */
    private $decimals;

    /**
     * Logger instance for debugging.
     *
     * @since    1.0.0
     * @access   private
     * @var      object    $logger    Logger instance.
     */
    private $logger;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string                        $plugin_name       The name of this plugin.
     * @param    string                        $version           The version of this plugin.
     * @param    Vortex_Blockchain_Integration $blockchain        The blockchain integration instance.
     * @param    Vortex_Wallet_Connect         $wallet_connect    The wallet connection instance.
     * @param    object                        $logger            Optional. Logger instance.
     */
    public function __construct( $plugin_name, $version, $blockchain, $wallet_connect, $logger = null ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->blockchain = $blockchain;
        $this->wallet_connect = $wallet_connect;
        $this->logger = $logger;

        // Initialize TOLA contract settings
        $this->init_contract_settings();

        // Register hooks
        add_action( 'init', array( $this, 'register_hooks' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_ajax_vortex_tola_balance', array( $this, 'ajax_get_balance' ) );
        add_action( 'wp_ajax_nopriv_vortex_tola_balance', array( $this, 'ajax_get_balance' ) );
        add_action( 'wp_ajax_vortex_tola_transfer', array( $this, 'ajax_transfer_tokens' ) );
        add_action( 'wp_ajax_vortex_tola_stake', array( $this, 'ajax_stake_tokens' ) );
        add_action( 'wp_ajax_vortex_tola_unstake', array( $this, 'ajax_unstake_tokens' ) );
        add_action( 'wp_ajax_vortex_tola_claim_rewards', array( $this, 'ajax_claim_rewards' ) );
    }

    /**
     * Initialize TOLA contract settings.
     *
     * @since    1.0.0
     */
    private function init_contract_settings() {
        // Get contract settings from options
        $this->contract_address = get_option( 'vortex_tola_contract_address', '' );
        $this->decimals = get_option( 'vortex_tola_decimals', 18 );

        // Load contract ABI
        $abi_path = plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'includes/blockchain/contract-abi.json';
        if ( file_exists( $abi_path ) ) {
            $abi_json = file_get_contents( $abi_path );
            $this->contract_abi = json_decode( $abi_json, true );
        } else {
            $this->log( 'Contract ABI file not found: ' . $abi_path, 'error' );
            $this->contract_abi = array();
        }
    }

    /**
     * Register hooks for TOLA functionality.
     *
     * @since    1.0.0
     */
    public function register_hooks() {
        // Enqueue scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

        // Shortcodes
        add_shortcode( 'vortex_tola_balance', array( $this, 'balance_shortcode' ) );
        add_shortcode( 'vortex_tola_transfer', array( $this, 'transfer_shortcode' ) );
        add_shortcode( 'vortex_tola_stake', array( $this, 'staking_shortcode' ) );

        // User profile integration
        add_action( 'show_user_profile', array( $this, 'add_wallet_fields' ) );
        add_action( 'edit_user_profile', array( $this, 'add_wallet_fields' ) );
        add_action( 'personal_options_update', array( $this, 'save_wallet_fields' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_wallet_fields' ) );

        // WooCommerce integration
        add_filter( 'woocommerce_payment_gateways', array( $this, 'add_tola_payment_gateway' ) );
        
        // WooCommerce product custom fields
        if ( class_exists( 'WooCommerce' ) ) {
            add_action( 'woocommerce_product_options_pricing', array( $this, 'add_tola_product_fields' ) );
            add_action( 'woocommerce_process_product_meta', array( $this, 'save_tola_product_fields' ) );
        }

        // Schedule token rewards distribution
        if ( ! wp_next_scheduled( 'vortex_daily_token_rewards' ) ) {
            wp_schedule_event( time(), 'daily', 'vortex_daily_token_rewards' );
        }
        add_action( 'vortex_daily_token_rewards', array( $this, 'distribute_daily_rewards' ) );

        // Artwork marketplace hooks
        add_action( 'vortex_artwork_purchased', array( $this, 'process_artwork_purchase' ), 10, 3 );
        add_action( 'vortex_artist_payout', array( $this, 'process_artist_payout' ), 10, 2 );
        
        // Add meta boxes for TOLA pricing options to product pages
        add_action( 'add_meta_boxes', array( $this, 'add_product_meta_boxes' ) );
        
        // Save meta box data
        add_action( 'save_post_product', array( $this, 'save_tola_pricing_meta_box' ) );
        add_action( 'save_post_vortex_artwork', array( $this, 'save_tola_pricing_meta_box' ) );
    }

    /**
     * Register REST API routes.
     *
     * @since    1.0.0
     */
    public function register_rest_routes() {
        register_rest_route( 'vortex/v1', '/tola/balance/(?P<address>[a-zA-Z0-9]+)', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'api_get_balance' ),
            'permission_callback' => '__return_true',
        ));

        register_rest_route( 'vortex/v1', '/tola/transfer', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'api_transfer_tokens' ),
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ));

        register_rest_route( 'vortex/v1', '/tola/stake', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'api_stake_tokens' ),
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ));

        register_rest_route( 'vortex/v1', '/tola/unstake', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'api_unstake_tokens' ),
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ));

        register_rest_route( 'vortex/v1', '/tola/rewards/(?P<address>[a-zA-Z0-9]+)', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'api_get_rewards' ),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Register settings for TOLA configuration.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting( 'vortex_blockchain_settings', 'vortex_tola_contract_address' );
        register_setting( 'vortex_blockchain_settings', 'vortex_tola_decimals', array(
            'default' => 18,
            'sanitize_callback' => 'absint',
        ));
        register_setting( 'vortex_blockchain_settings', 'vortex_tola_reward_rate', array(
            'default' => '0.01',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting( 'vortex_blockchain_settings', 'vortex_tola_staking_rate', array(
            'default' => '0.05',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting( 'vortex_blockchain_settings', 'vortex_tola_minimum_stake', array(
            'default' => '100',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting( 'vortex_blockchain_settings', 'vortex_tola_marketplace_fee', array(
            'default' => '0.025',
            'sanitize_callback' => 'sanitize_text_field',
        ));
    }

    /**
     * Enqueue scripts and styles for frontend.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Only enqueue on relevant pages
        global $post;
        if ( is_singular() && ( has_shortcode( $post->post_content, 'vortex_tola_balance' ) || 
                              has_shortcode( $post->post_content, 'vortex_tola_transfer' ) || 
                              has_shortcode( $post->post_content, 'vortex_tola_stake' ) ) ) {
            
            wp_enqueue_style(
                'vortex-tola',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/css/vortex-tola.css',
                array(),
                $this->version
            );
            
            wp_enqueue_script(
                'web3',
                'https://cdn.jsdelivr.net/npm/web3@1.8.0/dist/web3.min.js',
                array(),
                '1.8.0',
                true
            );
            
            wp_enqueue_script(
                'vortex-tola',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/js/vortex-tola.js',
                array( 'jquery', 'web3' ),
                $this->version,
                true
            );
            
            wp_localize_script(
                'vortex-tola',
                'vortexTola',
                array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'restUrl' => esc_url_raw( rest_url( 'vortex/v1/' ) ),
                    'nonce' => wp_create_nonce( 'wp_rest' ),
                    'contractAddress' => $this->contract_address,
                    'decimals' => $this->decimals,
                    'isLoggedIn' => is_user_logged_in(),
                )
            );
        }
    }

    /**
     * Enqueue scripts and styles for admin.
     *
     * @since    1.0.0
     * @param    string    $hook    Current admin page.
     */
    public function admin_enqueue_scripts( $hook ) {
        // Only enqueue on relevant admin pages
        if ( 'settings_page_vortex_blockchain_settings' === $hook ) {
            wp_enqueue_style(
                'vortex-tola-admin',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'admin/css/vortex-tola-admin.css',
                array(),
                $this->version
            );
            
            wp_enqueue_script(
                'web3',
                'https://cdn.jsdelivr.net/npm/web3@1.8.0/dist/web3.min.js',
                array(),
                '1.8.0',
                true
            );
            
            wp_enqueue_script(
                'vortex-tola-admin',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'admin/js/vortex-tola-admin.js',
                array( 'jquery', 'web3' ),
                $this->version,
                true
            );
            
            wp_localize_script(
                'vortex-tola-admin',
                'vortexTolaAdmin',
                array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'restUrl' => esc_url_raw( rest_url( 'vortex/v1/' ) ),
                    'nonce' => wp_create_nonce( 'wp_rest' ),
                    'contractAddress' => $this->contract_address,
                    'decimals' => $this->decimals,
                )
            );
        }
    }

    /**
     * Get token balance for a given address.
     *
     * @since    1.0.0
     * @param    string    $address    The wallet address.
     * @return   mixed     Balance or WP_Error.
     */
    public function get_balance( $address ) {
        if ( empty( $address ) || empty( $this->contract_address ) ) {
            return new WP_Error( 'invalid_params', __( 'Invalid address or contract configuration', 'vortex-ai-marketplace' ) );
        }

        try {
            // Call blockchain integration to get balance
            $result = $this->blockchain->call_contract(
                $this->contract_address,
                $this->contract_abi,
                'balanceOf',
                array( $address )
            );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            // Convert from wei to tokens (considering decimals)
            $balance = $this->from_wei( $result );
            
            return array(
                'address' => $address,
                'balance' => $balance,
                'raw_balance' => $result,
                'token' => 'TOLA',
                'decimals' => $this->decimals,
            );
        } catch ( Exception $e ) {
            $this->log( 'Error getting balance: ' . $e->getMessage(), 'error' );
            return new WP_Error( 'contract_error', $e->getMessage() );
        }
    }

    /**
     * Transfer TOLA tokens.
     *
     * @since    1.0.0
     * @param    string    $from           The sender address.
     * @param    string    $to             The recipient address.
     * @param    float     $amount         The amount to transfer.
     * @param    string    $private_key    The sender's private key or signature.
     * @return   mixed     Transaction result or WP_Error.
     */
    public function transfer_tokens( $from, $to, $amount, $private_key ) {
        if ( empty( $from ) || empty( $to ) || empty( $amount ) || empty( $private_key ) ) {
            return new WP_Error( 'invalid_params', __( 'Missing required parameters', 'vortex-ai-marketplace' ) );
        }

        if ( empty( $this->contract_address ) ) {
            return new WP_Error( 'invalid_contract', __( 'Contract address not configured', 'vortex-ai-marketplace' ) );
        }

        try {
            // Convert amount to wei (considering decimals)
            $amount_wei = $this->to_wei( $amount );
            
            // Call blockchain integration to send transaction
            $result = $this->blockchain->send_contract_transaction(
                $this->contract_address,
                $this->contract_abi,
                'transfer',
                array( $to, $amount_wei ),
                $from,
                $private_key
            );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            // Log successful transfer
            $this->log( sprintf(
                'TOLA Transfer: %s from %s to %s (Transaction: %s)',
                $amount,
                $from,
                $to,
                $result['transaction_hash']
            ), 'info' );
            
            // Update user meta if the sender is a registered user
            $user = $this->get_user_by_wallet_address( $from );
            if ( $user ) {
                $prev_balance = get_user_meta( $user->ID, 'vortex_tola_balance', true );
                if ( is_numeric( $prev_balance ) ) {
                    update_user_meta( $user->ID, 'vortex_tola_balance', $prev_balance - $amount );
                }
            }
            
            // Update user meta if the recipient is a registered user
            $recipient = $this->get_user_by_wallet_address( $to );
            if ( $recipient ) {
                $prev_balance = get_user_meta( $recipient->ID, 'vortex_tola_balance', true );
                if ( is_numeric( $prev_balance ) ) {
                    update_user_meta( $recipient->ID, 'vortex_tola_balance', $prev_balance + $amount );
                }
            }
            
            return $result;
        } catch ( Exception $e ) {
            $this->log( 'Error transferring tokens: ' . $e->getMessage(), 'error' );
            return new WP_Error( 'transfer_error', $e->getMessage() );
        }
    }

    /**
     * Stake TOLA tokens.
     *
     * @since    1.0.0
     * @param    string    $from           The staker address.
     * @param    float     $amount         The amount to stake.
     * @param    string    $private_key    The staker's private key or signature.
     * @return   mixed     Transaction result or WP_Error.
     */
    public function stake_tokens( $from, $amount, $private_key ) {
        if ( empty( $from ) || empty( $amount ) || empty( $private_key ) ) {
            return new WP_Error( 'invalid_params', __( 'Missing required parameters', 'vortex-ai-marketplace' ) );
        }

        // Get minimum stake amount
        $minimum_stake = get_option( 'vortex_tola_minimum_stake', '100' );
        if ( $amount < floatval( $minimum_stake ) ) {
            return new WP_Error( 'below_minimum', sprintf(
                __( 'Minimum stake amount is %s TOLA', 'vortex-ai-marketplace' ),
                $minimum_stake
            ));
        }

        try {
            // Convert amount to wei (considering decimals)
            $amount_wei = $this->to_wei( $amount );
            
            // Call blockchain integration to stake tokens
            $result = $this->blockchain->send_contract_transaction(
                $this->contract_address,
                $this->contract_abi,
                'stake',
                array( $amount_wei ),
                $from,
                $private_key
            );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            // Log successful staking
            $this->log( sprintf(
                'TOLA Staking: %s staked by %s (Transaction: %s)',
                $amount,
                $from,
                $result['transaction_hash']
            ), 'info' );
            
            // Update user meta if the staker is a registered user
            $user = $this->get_user_by_wallet_address( $from );
            if ( $user ) {
                $prev_staked = get_user_meta( $user->ID, 'vortex_tola_staked', true );
                if ( ! is_numeric( $prev_staked ) ) {
                    $prev_staked = 0;
                }
                update_user_meta( $user->ID, 'vortex_tola_staked', $prev_staked + $amount );
                
                // Update staking timestamp
                update_user_meta( $user->ID, 'vortex_tola_stake_time', time() );
                
                // Update total staked in options for analytics
                $total_staked = get_option( 'vortex_tola_total_staked', 0 );
                update_option( 'vortex_tola_total_staked', $total_staked + $amount );
            }
            
            return $result;
        } catch ( Exception $e ) {
            $this->log( 'Error staking tokens: ' . $e->getMessage(), 'error' );
            return new WP_Error( 'stake_error', $e->getMessage() );
        }
    }

    /**
     * Unstake TOLA tokens.
     *
     * @since    1.0.0
     * @param    string    $from           The staker address.
     * @param    float     $amount         The amount to unstake.
     * @param    string    $private_key    The staker's private key or signature.
     * @return   mixed     Transaction result or WP_Error.
     */
    public function unstake_tokens( $from, $amount, $private_key ) {
        if ( empty( $from ) || empty( $amount ) || empty( $private_key ) ) {
            return new WP_Error( 'invalid_params', __( 'Missing required parameters', 'vortex-ai-marketplace' ) );
        }

        try {
            // Convert amount to wei (considering decimals)
            $amount_wei = $this->to_wei( $amount );
            
            // Call blockchain integration to unstake tokens
            $result = $this->blockchain->send_contract_transaction(
                $this->contract_address,
                $this->contract_abi,
                'unstake',
                array( $amount_wei ),
                $from,
                $private_key
            );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            // Log successful unstaking
            $this->log( sprintf(
                'TOLA Unstaking: %s unstaked by %s (Transaction: %s)',
                $amount,
                $from,
                $result['transaction_hash']
            ), 'info' );
            
            // Update user meta if the staker is a registered user
            $user = $this->get_user_by_wallet_address( $from );
            if ( $user ) {
                $prev_staked = get_user_meta( $user->ID, 'vortex_tola_staked', true );
                if ( is_numeric( $prev_staked ) && $prev_staked >= $amount ) {
                    update_user_meta( $user->ID, 'vortex_tola_staked', $prev_staked - $amount );
                    
                    // Update total staked in options for analytics
                    $total_staked = get_option( 'vortex_tola_total_staked', 0 );
                    update_option( 'vortex_tola_total_staked', max( 0, $total_staked - $amount ) );
                }
            }
            
            return $result;
        } catch ( Exception $e ) {
            $this->log( 'Error unstaking tokens: ' . $e->getMessage(), 'error' );
            return new WP_Error( 'unstake_error', $e->getMessage() );
        }
    }

    /**
     * Get staking rewards for a given address.
     *
     * @since    1.0.0
     * @param    string    $address    The wallet address.
     * @return   mixed     Rewards data or WP_Error.
     */
    public function get_rewards( $address ) {
        if ( empty( $address ) ) {
            return new WP_Error( 'invalid_params', __( 'Invalid address', 'vortex-ai-marketplace' ) );
        }

        try {
            // Call blockchain integration to get staked amount
            $staked_result = $this->blockchain->call_contract(
                $this->contract_address,
                $this->contract_abi,
                'stakedBalance',
                array( $address )
            );

            if ( is_wp_error( $staked_result ) ) {
                return $staked_result;
            }

            // Convert from wei to tokens (considering decimals)
            $staked_amount = $this->from_wei( $staked_result );
            
            // Call blockchain integration to get rewards
            $rewards_result = $this->blockchain->call_contract(
                $this->contract_address,
                $this->contract_abi,
                'pendingRewards',
                array( $address )
            );

            if ( is_wp_error( $rewards_result ) ) {
                return $rewards_result;
            }

            // Convert from wei to tokens (considering decimals)
            $rewards_amount = $this->from_wei( $rewards_result );
            
            // Get staking start time from blockchain
            $time_result = $this->blockchain->call_contract(
                $this->contract_address,
                $this->contract_abi,
                'stakingTime',
                array( $address )
            );
            
            $staking_time = is_wp_error( $time_result ) ? 0 : intval( $time_result );
            
            return array(
                'address' => $address,
                'staked_amount' => $staked_amount,
                'rewards' => $rewards_amount,
                'staking_time' => $staking_time,
                'token' => 'TOLA',
                'decimals' => $this->decimals,
            );
        } catch ( Exception $e ) {
            $this->log( 'Error getting rewards: ' . $e->getMessage(), 'error' );
            return new WP_Error( 'rewards_error', $e->getMessage() );
        }
    }

    /**
     * Claim staking rewards.
     *
     * @since    1.0.0
     * @param    string    $address        The wallet address.
     * @param    string    $private_key    The wallet's private key or signature.
     * @return   mixed     Transaction result or WP_Error.
     */
    public function claim_rewards( $address, $private_key ) {
        if ( empty( $address ) || empty( $private_key ) ) {
            return new WP_Error( 'invalid_params', __( 'Missing required parameters', 'vortex-ai-marketplace' ) );
        }

        try {
            // Call blockchain integration to claim rewards
            $result = $this->blockchain->send_contract_transaction(
                $this->contract_address,
                $this->contract_abi,
                'claimRewards',
                array(),
                $address,
                $private_key
            );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            // Get claimed amount from transaction receipt or recalculate
            $rewards = $this->get_rewards( $address );
            $claimed_amount = is_wp_error( $rewards ) ? 0 : $rewards['rewards'];

            // Log successful claim
            $this->log( sprintf(
                'TOLA Rewards Claim: %s claimed by %s (Transaction: %s)',
                $claimed_amount,
                $address,
                $result['transaction_hash']
            ), 'info' );
            
            // Update user meta if the claimer is a registered user
            $user = $this->get_user_by_wallet_address( $address );
            if ( $user ) {
                // Reset pending rewards in user meta
                update_user_meta( $user->ID, 'vortex_tola_pending_rewards', 0 );
                
                // Update total claimed rewards
                $prev_claimed = get_user_meta( $user->ID, 'vortex_tola_claimed_rewards', true );
                if ( ! is_numeric( $prev_claimed ) ) {
                    $prev_claimed = 0;
                }
                update_user_meta( $user->ID, 'vortex_tola_claimed_rewards', $prev_claimed + $claimed_amount );
                
                // Update total claimed in options for analytics
                $total_claimed = get_option( 'vortex_tola_total_claimed_rewards', 0 );
                update_option( 'vortex_tola_total_claimed_rewards', $total_claimed + $claimed_amount );
            }
            
            return array(
                'transaction' => $result,
                'claimed_amount' => $claimed_amount,
            );
        } catch ( Exception $e ) {
            $this->log( 'Error claiming rewards: ' . $e->getMessage(), 'error' );
            return new WP_Error( 'claim_error', $e->getMessage() );
        }
    }

    /**
     * Distribute daily token rewards to active users.
     *
     * @since    1.0.0
     */
    public function distribute_daily_rewards() {
        // Get reward rate
        $reward_rate = get_option( 'vortex_tola_reward_rate', '0.01' );
        $reward_rate = floatval( $reward_rate );
        
        if ( $reward_rate <= 0 ) {
            $this->log( 'Skipping daily rewards distribution: reward rate is zero', 'info' );
            return;
        }
        
        // Get staking rate
        $staking_rate = get_option( 'vortex_tola_staking_rate', '0.05' );
        $staking_rate = floatval( $staking_rate );
        
        // Get active users with wallet addresses
        $users = get_users( array(
            'meta_key' => 'vortex_wallet_address',
            'meta_compare' => 'EXISTS',
        ));
        
        if ( empty( $users ) ) {
            $this->log( 'No users with wallet addresses found for rewards distribution', 'info' );
            return;
        }
        
        $this->log( sprintf( 'Starting daily rewards distribution for %d users', count( $users ) ), 'info' );
        
        // Initialize counters
        $total_distributed = 0;
        $successful_distributions = 0;
        
        // Set default admin wallet for sending rewards
        $admin_wallet = get_option( 'vortex_admin_wallet_address', '' );
        $admin_key = get_option( 'vortex_admin_wallet_private_key', '' );
        
        if ( empty( $admin_wallet ) || empty( $admin_key ) ) {
            $this->log( 'Admin wallet not configured for rewards distribution', 'error' );
            return;
        }
        
        // Check admin wallet balance
        $admin_balance = $this->get_balance( $admin_wallet );
        if ( is_wp_error( $admin_balance ) || ! isset( $admin_balance['balance'] ) ) {
            $this->log( 'Error checking admin wallet balance for rewards distribution', 'error' );
            return;
        }
        
        $available_tokens = floatval( $admin_balance['balance'] );
        $this->log( sprintf( 'Admin wallet has %s TOLA available for distribution', $available_tokens ), 'info' );
        
        foreach ( $users as $user ) {
            $wallet_address = get_user_meta( $user->ID, 'vortex_wallet_address', true );
            if ( empty( $wallet_address ) ) {
                continue;
            }
            
            // Calculate base reward
            $reward_amount = $reward_rate;
            
            // Check if user has staked tokens for bonus rewards
            $staked_amount = get_user_meta( $user->ID, 'vortex_tola_staked', true );
            if ( is_numeric( $staked_amount ) && $staked_amount > 0 ) {
                // Add staking bonus
                $staking_bonus = $staked_amount * $staking_rate / 365; // Daily rate
                $reward_amount += $staking_bonus;
            }
            
            // Check if we have enough tokens left
            if ( $reward_amount > $available_tokens ) {
                $this->log( sprintf( 
                    'Insufficient tokens for user %s, needed %s TOLA but only %s TOLA available', 
                    $user->user_login, 
                    $reward_amount, 
                    $available_tokens 
                ), 'warning' );
                continue;
            }
            
            // Send reward tokens
            $result = $this->transfer_tokens( $admin_wallet, $wallet_address, $reward_amount, $admin_key );
            
            if ( is_wp_error( $result ) ) {
                $this->log( sprintf( 
                    'Error distributing rewards to user %s: %s', 
                    $user->user_login, 
                    $result->get_error_message() 
                ), 'error' );
                continue;
            }
            
            $this->log( sprintf( 
                'Distributed %s TOLA rewards to user %s (wallet: %s)', 
                $reward_amount, 
                $user->user_login, 
                $wallet_address 
            ), 'info' );
            
            // Update counters
            $total_distributed += $reward_amount;
            $successful_distributions++;
            $available_tokens -= $reward_amount;
            
            // Update user reward stats
            $total_rewards = get_user_meta( $user->ID, 'vortex_tola_total_rewards', true );
            if ( ! is_numeric( $total_rewards ) ) {
                $total_rewards = 0;
            }
            update_user_meta( $user->ID, 'vortex_tola_total_rewards', $total_rewards + $reward_amount );
            
            // Store last reward date and amount
            update_user_meta( $user->ID, 'vortex_tola_last_reward_date', current_time( 'mysql' ) );
            update_user_meta( $user->ID, 'vortex_tola_last_reward_amount', $reward_amount );
        }
        
        // Update global stats
        $total_rewards_distributed = get_option( 'vortex_tola_total_rewards_distributed', 0 );
        update_option( 'vortex_tola_total_rewards_distributed', $total_rewards_distributed + $total_distributed );
        
        $this->log( sprintf( 
            'Completed daily rewards distribution: %s TOLA distributed to %d users', 
            $total_distributed, 
            $successful_distributions 
        ), 'info' );
    }

    /**
     * Process artwork purchase with TOLA.
     *
     * @since    1.0.0
     * @param    int       $artwork_id      The artwork post ID.
     * @param    float     $price           The price in TOLA.
     * @param    int       $buyer_user_id   The buyer user ID.
     * @return   mixed     Transaction result or WP_Error.
     */
    public function process_artwork_purchase( $artwork_id, $price, $buyer_user_id ) {
        // Get artwork details
        $artwork = get_post( $artwork_id );
        if ( ! $artwork || 'vortex_artwork' !== $artwork->post_type ) {
            return new WP_Error( 'invalid_artwork', __( 'Invalid artwork', 'vortex-ai-marketplace' ) );
        }

        // Get artist ID and wallet address
        $artist_id = $artwork->post_author;
        $artist_user = get_user_by( 'id', $artist_id );
        if ( ! $artist_user ) {
            return new WP_Error( 'invalid_artist', __( 'Artwork artist not found', 'vortex-ai-marketplace' ) );
        }

        $artist_wallet = get_user_meta( $artist_id, 'vortex_wallet_address', true );
        if ( empty( $artist_wallet ) ) {
            return new WP_Error( 'missing_wallet', __( 'Artist wallet address not configured', 'vortex-ai-marketplace' ) );
        }

        // Get buyer wallet address
        $buyer_wallet = get_user_meta( $buyer_user_id, 'vortex_wallet_address', true );
        if ( empty( $buyer_wallet ) ) {
            return new WP_Error( 'missing_wallet', __( 'Buyer wallet address not configured', 'vortex-ai-marketplace' ) );
        }

        // Get marketplace fee
        $marketplace_fee_rate = get_option( 'vortex_tola_marketplace_fee', '0.025' );
        $marketplace_fee_rate = floatval( $marketplace_fee_rate );
        $marketplace_fee = $price * $marketplace_fee_rate;
        $artist_amount = $price - $marketplace_fee;

        // Get marketplace wallet
        $marketplace_wallet = get_option( 'vortex_marketplace_wallet_address', '' );
        if ( empty( $marketplace_wallet ) ) {
            return new WP_Error( 'missing_wallet', __( 'Marketplace wallet address not configured', 'vortex-ai-marketplace' ) );
        }

        // Get buyer's private key or signature
        $buyer_key = isset( $_POST['private_key'] ) ? sanitize_text_field( wp_unslash( $_POST['private_key'] ) ) : '';
        if ( empty( $buyer_key ) ) {
            return new WP_Error( 'missing_key', __( 'Transaction signature required', 'vortex-ai-marketplace' ) );
        }

        // Transfer tokens from buyer to artist
        $artist_result = $this->transfer_tokens( $buyer_wallet, $artist_wallet, $artist_amount, $buyer_key );
        if ( is_wp_error( $artist_result ) ) {
            return $artist_result;
        }

        // Transfer marketplace fee
        if ( $marketplace_fee > 0 ) {
            $fee_result = $this->transfer_tokens( $buyer_wallet, $marketplace_wallet, $marketplace_fee, $buyer_key );
            if ( is_wp_error( $fee_result ) ) {
                $this->log( sprintf(
                    'Error transferring marketplace fee for artwork %d: %s',
                    $artwork_id,
                    $fee_result->get_error_message()
                ), 'error' );
            }
        }

        // Create transaction record
        $transaction_id = wp_insert_post(array(
            'post_type' => 'vortex_transaction',
            'post_title' => sprintf( __( 'Purchase of %s', 'vortex-ai-marketplace' ), $artwork->post_title ),
            'post_status' => 'publish',
            'post_author' => $buyer_user_id,
        ));

        if ( ! is_wp_error( $transaction_id ) ) {
            // Add transaction meta
            update_post_meta( $transaction_id, '_vortex_transaction_type', 'artwork_purchase' );
            update_post_meta( $transaction_id, '_vortex_transaction_artwork_id', $artwork_id );
            update_post_meta( $transaction_id, '_vortex_transaction_amount', $price );
            update_post_meta( $transaction_id, '_vortex_transaction_currency', 'TOLA' );
            update_post_meta( $transaction_id, '_vortex_transaction_buyer_id', $buyer_user_id );
            update_post_meta( $transaction_id, '_vortex_transaction_seller_id', $artist_id );
            update_post_meta( $transaction_id, '_vortex_transaction_fee', $marketplace_fee );
            update_post_meta( $transaction_id, '_vortex_transaction_hash', $artist_result['transaction_hash'] );
            update_post_meta( $transaction_id, '_vortex_transaction_date', current_time( 'mysql' ) );
        }

        // Update artwork meta
        update_post_meta( $artwork_id, '_vortex_artwork_sold', true );
        update_post_meta( $artwork_id, '_vortex_artwork_sold_to', $buyer_user_id );
        update_post_meta( $artwork_id, '_vortex_artwork_sold_date', current_time( 'mysql' ) );
        update_post_meta( $artwork_id, '_vortex_artwork_sold_price', $price );
        update_post_meta( $artwork_id, '_vortex_artwork_sold_currency', 'TOLA' );
        update_post_meta( $artwork_id, '_vortex_artwork_transaction_id', $transaction_id );

        // Update artist stats
        $artist_sales = get_user_meta( $artist_id, 'vortex_tola_sales_count', true );
        update_user_meta( $artist_id, 'vortex_tola_sales_count', ( $artist_sales ? intval( $artist_sales ) : 0 ) + 1 );

        $artist_revenue = get_user_meta( $artist_id, 'vortex_tola_total_revenue', true );
        update_user_meta( $artist_id, 'vortex_tola_total_revenue', ( $artist_revenue ? floatval( $artist_revenue ) : 0 ) + $artist_amount );

        // Update buyer stats
        $buyer_purchases = get_user_meta( $buyer_user_id, 'vortex_tola_purchases_count', true );
        update_user_meta( $buyer_user_id, 'vortex_tola_purchases_count', ( $buyer_purchases ? intval( $buyer_purchases ) : 0 ) + 1 );

        $buyer_spent = get_user_meta( $buyer_user_id, 'vortex_tola_total_spent', true );
        update_user_meta( $buyer_user_id, 'vortex_tola_total_spent', ( $buyer_spent ? floatval( $buyer_spent ) : 0 ) + $price );

        // Log successful purchase
        $this->log( sprintf(
            'Artwork Purchase: %s (ID: %d) purchased by User %d from Artist %d for %s TOLA (Transaction: %s)',
            $artwork->post_title,
            $artwork_id,
            $buyer_user_id,
            $artist_id,
            $price,
            $artist_result['transaction_hash']
        ), 'info' );

        // Trigger action for other integrations
        do_action( 'vortex_artwork_purchase_completed', $artwork_id, $buyer_user_id, $artist_id, $price, $transaction_id );

        return array(
            'transaction_id' => $transaction_id,
            'transaction_hash' => $artist_result['transaction_hash'],
            'artwork_id' => $artwork_id,
            'buyer_id' => $buyer_user_id,
            'artist_id' => $artist_id,
            'price' => $price,
            'artist_amount' => $artist_amount,
            'marketplace_fee' => $marketplace_fee,
        );
    }

    /**
     * Process artist payout with TOLA.
     *
     * @since    1.0.0
     * @param    int       $artist_id    The artist user ID.
     * @param    float     $amount       The amount to pay out.
     * @return   mixed     Transaction result or WP_Error.
     */
    public function process_artist_payout( $artist_id, $amount ) {
        // Verify artist
        $artist_user = get_user_by( 'id', $artist_id );
        if ( ! $artist_user ) {
            return new WP_Error( 'invalid_artist', __( 'Artist not found', 'vortex-ai-marketplace' ) );
        }

        // Get artist wallet address
        $artist_wallet = get_user_meta( $artist_id, 'vortex_wallet_address', true );
        if ( empty( $artist_wallet ) ) {
            return new WP_Error( 'missing_wallet', __( 'Artist wallet address not configured', 'vortex-ai-marketplace' ) );
        }

        // Get marketplace wallet
        $marketplace_wallet = get_option( 'vortex_marketplace_wallet_address', '' );
        $marketplace_key = get_option( 'vortex_marketplace_wallet_private_key', '' );
        
        if ( empty( $marketplace_wallet ) || empty( $marketplace_key ) ) {
            return new WP_Error( 'missing_wallet', __( 'Marketplace wallet not configured', 'vortex-ai-marketplace' ) );
        }

        // Transfer tokens from marketplace to artist
        $result = $this->transfer_tokens( $marketplace_wallet, $artist_wallet, $amount, $marketplace_key );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Create transaction record
        $transaction_id = wp_insert_post(array(
            'post_type' => 'vortex_transaction',
            'post_title' => sprintf( __( 'Payout to %s', 'vortex-ai-marketplace' ), $artist_user->display_name ),
            'post_status' => 'publish',
            'post_author' => $artist_id,
        ));

        if ( ! is_wp_error( $transaction_id ) ) {
            // Add transaction meta
            update_post_meta( $transaction_id, '_vortex_transaction_type', 'artist_payout' );
            update_post_meta( $transaction_id, '_vortex_transaction_amount', $amount );
            update_post_meta( $transaction_id, '_vortex_transaction_currency', 'TOLA' );
            update_post_meta( $transaction_id, '_vortex_transaction_artist_id', $artist_id );
            update_post_meta( $transaction_id, '_vortex_transaction_hash', $result['transaction_hash'] );
            update_post_meta( $transaction_id, '_vortex_transaction_date', current_time( 'mysql' ) );
        }

        // Update artist stats
        $artist_payouts = get_user_meta( $artist_id, 'vortex_tola_payouts_count', true );
        update_user_meta( $artist_id, 'vortex_tola_payouts_count', ( $artist_payouts ? intval( $artist_payouts ) : 0 ) + 1 );

        $artist_payout_total = get_user_meta( $artist_id, 'vortex_tola_payout_total', true );
        update_user_meta( $artist_id, 'vortex_tola_payout_total', ( $artist_payout_total ? floatval( $artist_payout_total ) : 0 ) + $amount );

        // Log successful payout
        $this->log( sprintf(
            'Artist Payout: %s TOLA paid to Artist %s (ID: %d) (Transaction: %s)',
            $amount,
            $artist_user->display_name,
            $artist_id,
            $result['transaction_hash']
        ), 'info' );

        // Trigger action for other integrations
        do_action( 'vortex_artist_payout_completed', $artist_id, $amount, $transaction_id, $result['transaction_hash'] );

        return array(
            'transaction_id' => $transaction_id,
            'transaction_hash' => $result['transaction_hash'],
            'artist_id' => $artist_id,
            'amount' => $amount,
        );
    }
    
    /**
     * Convert from wei to token amount considering decimals.
     *
     * @since    1.0.0
     * @param    string    $wei_amount    The amount in wei.
     * @return   float     The amount in tokens.
     */
    private function from_wei( $wei_amount ) {
        $divisor = pow( 10, $this->decimals );
        return floatval( $wei_amount ) / $divisor;
    }
    
    /**
     * Convert from token amount to wei considering decimals.
     *
     * @since    1.0.0
     * @param    float     $token_amount    The amount in tokens.
     * @return   string    The amount in wei.
     */
    private function to_wei( $token_amount ) {
        $multiplier = pow( 10, $this->decimals );
        return strval( floatval( $token_amount ) * $multiplier );
    }

    /**
     * Get user by wallet address.
     *
     * @since    1.0.0
     * @param    string    $wallet_address    The wallet address.
     * @return   WP_User|false    User object or false.
     */
    private function get_user_by_wallet_address( $wallet_address ) {
        $users = get_users( array(
            'meta_key' => 'vortex_wallet_address',
            'meta_value' => $wallet_address,
        ));

        return ! empty( $users ) ? $users[0] : false;
    }

    /**
     * Add wallet fields to user profile.
     *
     * @since    1.0.0
     * @param    WP_User    $user    User object.
     */
    public function add_wallet_fields( $user ) {
        // Check if current user can edit this profile
        if ( ! current_user_can( 'edit_user', $user->ID ) ) {
            return;
        }

        $wallet_address = get_user_meta( $user->ID, 'vortex_wallet_address', true );
        $tola_balance = get_user_meta( $user->ID, 'vortex_tola_balance', true );
        $tola_staked = get_user_meta( $user->ID, 'vortex_tola_staked', true );
        ?>
        <h3><?php esc_html_e( 'TOLA Token & Wallet Information', 'vortex-ai-marketplace' ); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="vortex_wallet_address"><?php esc_html_e( 'Wallet Address', 'vortex-ai-marketplace' ); ?></label></th>
                <td>
                    <input type="text" name="vortex_wallet_address" id="vortex_wallet_address" value="<?php echo esc_attr( $wallet_address ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Your blockchain wallet address for TOLA tokens.', 'vortex-ai-marketplace' ); ?></p>
                </td>
            </tr>
            <?php if ( ! empty( $wallet_address ) ) : ?>
                <tr>
                    <th><?php esc_html_e( 'TOLA Balance', 'vortex-ai-marketplace' ); ?></th>
                    <td>
                        <p id="vortex-tola-balance-display">
                            <?php if ( is_numeric( $tola_balance ) ) : ?>
                                <?php echo esc_html( $tola_balance ); ?> TOLA
                            <?php else : ?>
                                <span class="loading"><?php esc_html_e( 'Loading...', 'vortex-ai-marketplace' ); ?></span>
                            <?php endif; ?>
                        </p>
                        <button type="button" class="button" id="vortex-refresh-balance" data-address="<?php echo esc_attr( $wallet_address ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'vortex_tola_nonce' ) ); ?>">
                            <?php esc_html_e( 'Refresh Balance', 'vortex-ai-marketplace' ); ?>
                        </button>
                    </td>
                </tr>
                <?php if ( is_numeric( $tola_staked ) && $tola_staked > 0 ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Staked TOLA', 'vortex-ai-marketplace' ); ?></th>
                        <td>
                            <p><?php echo esc_html( $tola_staked ); ?> TOLA</p>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>
        </table>
        <?php
    }

    /**
     * Save wallet fields from user profile.
     *
     * @since    1.0.0
     * @param    int    $user_id    The user ID.
     */
    public function save_wallet_fields( $user_id ) {
        // Check if current user can edit this profile
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return;
        }

        // Save wallet address
        if ( isset( $_POST['vortex_wallet_address'] ) ) {
            $wallet_address = sanitize_text_field( wp_unslash( $_POST['vortex_wallet_address'] ) );
            update_user_meta( $user_id, 'vortex_wallet_address', $wallet_address );
        }
    }

    /**
     * AJAX handler for getting token balance.
     *
     * @since    1.0.0
     */
    public function ajax_get_balance() {
        // Check nonce
        if ( ! check_ajax_referer( 'vortex_tola_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'vortex-ai-marketplace' ) ) );
        }

        // Get and validate address
        $address = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '';
        if ( empty( $address ) ) {
            wp_send_json_error( array( 'message' => __( 'Wallet address is required', 'vortex-ai-marketplace' ) ) );
        }

        // Get balance
        $result = $this->get_balance( $address );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        // Update user meta if this is the current user's wallet
        $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
        if ( $user_id > 0 ) {
            update_user_meta( $user_id, 'vortex_tola_balance', $result['balance'] );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX handler for transferring tokens.
     *
     * @since    1.0.0
     */
    public function ajax_transfer_tokens() {
        // Check nonce and authentication
        if ( ! check_ajax_referer( 'vortex_tola_nonce', 'nonce', false ) || ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'vortex-ai-marketplace' ) ) );
        }

        // Get and validate parameters
        $from = isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : '';
        $to = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '';
        $amount = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
        $private_key = isset( $_POST['private_key'] ) ? sanitize_text_field( wp_unslash( $_POST['private_key'] ) ) : '';

        if ( empty( $from ) || empty( $to ) || $amount <= 0 || empty( $private_key ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing required parameters', 'vortex-ai-marketplace' ) ) );
        }

        // Transfer tokens
        $result = $this->transfer_tokens( $from, $to, $amount, $private_key );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX handler for staking tokens.
     *
     * @since    1.0.0
     */
    public function ajax_stake_tokens() {
        // Check nonce and authentication
        if ( ! check_ajax_referer( 'vortex_tola_nonce', 'nonce', false ) || ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'vortex-ai-marketplace' ) ) );
        }

        // Get and validate parameters
        $address = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '';
        $amount = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
        $private_key = isset( $_POST['private_key'] ) ? sanitize_text_field( wp_unslash( $_POST['private_key'] ) ) : '';

        if ( empty( $address ) || $amount <= 0 || empty( $private_key ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing required parameters', 'vortex-ai-marketplace' ) ) );
        }

        // Stake tokens
        $result = $this->stake_tokens( $address, $amount, $private_key );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX handler for unstaking tokens.
     *
     * @since    1.0.0
     */
    public function ajax_unstake_tokens() {
        // Check nonce and authentication
        if ( ! check_ajax_referer( 'vortex_tola_nonce', 'nonce', false ) || ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'vortex-ai-marketplace' ) ) );
        }

        // Get and validate parameters
        $address = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '';
        $amount = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
        $private_key = isset( $_POST['private_key'] ) ? sanitize_text_field( wp_unslash( $_POST['private_key'] ) ) : '';

        if ( empty( $address ) || $amount <= 0 || empty( $private_key ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing required parameters', 'vortex-ai-marketplace' ) ) );
        }

        // Unstake tokens
        $result = $this->unstake_tokens( $address, $amount, $private_key );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX handler for claiming rewards.
     *
     * @since    1.0.0
     */
    public function ajax_claim_rewards() {
        // Check nonce and authentication
        if ( ! check_ajax_referer( 'vortex_tola_nonce', 'nonce', false ) || ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'vortex-ai-marketplace' ) ) );
        }

        // Get and validate parameters
        $address = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '';
        $private_key = isset( $_POST['private_key'] ) ? sanitize_text_field( wp_unslash( $_POST['private_key'] ) ) : '';

        if ( empty( $address ) || empty( $private_key ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing required parameters', 'vortex-ai-marketplace' ) ) );
        }

        // Claim rewards
        $result = $this->claim_rewards( $address, $private_key );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    /**
     * Shortcode for displaying TOLA balance.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    Shortcode output.
     */
    public function balance_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'address' => '',
            'show_refresh' => 'yes',
            'show_token' => 'yes',
        ), $atts, 'vortex_tola_balance' );

        // Get wallet address
        $address = $atts['address'];
        if ( empty( $address ) && is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $address = get_user_meta( $user_id, 'vortex_wallet_address', true );
        }

        // If no address, show connect wallet message
        if ( empty( $address ) ) {
            return '<div class="vortex-tola-balance-widget vortex-no-wallet">' .
                   '<p class="vortex-connect-wallet-message">' . 
                   __( 'Connect your wallet to see your TOLA balance', 'vortex-ai-marketplace' ) . 
                   '</p>' .
                   '<button class="vortex-connect-wallet-button">' . 
                   __( 'Connect Wallet', 'vortex-ai-marketplace' ) . 
                   '</button></div>';
        }

        // Show balance loader
        $output = '<div class="vortex-tola-balance-widget" data-address="' . esc_attr( $address ) . '">';
        $output .= '<div class="vortex-tola-balance-content">';
        $output .= '<span class="vortex-tola-balance-amount">' . 
                   '<span class="vortex-tola-loading">' . __( 'Loading...', 'vortex-ai-marketplace' ) . '</span>' . 
                   '</span>';
                   
        if ( 'yes' === $atts['show_token'] ) {
            $output .= ' <span class="vortex-tola-token-name">TOLA</span>';
        }
                   
        $output .= '</div>';
        
        if ( 'yes' === $atts['show_refresh'] ) {
            $output .= '<button class="vortex-tola-refresh-button" data-address="' . esc_attr( $address ) . '">' . 
                      __( 'Refresh', 'vortex-ai-marketplace' ) . 
                      '</button>';
        }
        
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Shortcode for displaying TOLA transfer form.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    Shortcode output.
     */
    public function transfer_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'show_balance' => 'yes',
            'min_amount' => '0.1',
            'max_amount' => '',
        ), $atts, 'vortex_tola_transfer' );

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            return '<div class="vortex-tola-transfer-widget vortex-login-required">' .
                   '<p class="vortex-login-message">' . 
                   __( 'You must be logged in to transfer TOLA tokens', 'vortex-ai-marketplace' ) . 
                   '</p>' .
                   '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="vortex-login-button">' . 
                   __( 'Log In', 'vortex-ai-marketplace' ) . 
                   '</a></div>';
        }

        // Get wallet address
        $user_id = get_current_user_id();
        $from_address = get_user_meta( $user_id, 'vortex_wallet_address', true );

        // If no wallet address, show connect wallet message
        if ( empty( $from_address ) ) {
            return '<div class="vortex-tola-transfer-widget vortex-no-wallet">' .
                   '<p class="vortex-connect-wallet-message">' . 
                   __( 'Connect your wallet to transfer TOLA tokens', 'vortex-ai-marketplace' ) . 
                   '</p>' .
                   '<button class="vortex-connect-wallet-button">' . 
                   __( 'Connect Wallet', 'vortex-ai-marketplace' ) . 
                   '</button></div>';
        }

        // Build transfer form
        $output = '<div class="vortex-tola-transfer-widget">';
        
        if ( 'yes' === $atts['show_balance'] ) {
            $balance = get_user_meta( $user_id, 'vortex_tola_balance', true );
            $output .= '<div class="vortex-tola-balance">';
            $output .= '<label>' . __( 'Your Balance:', 'vortex-ai-marketplace' ) . '</label> ';
            $output .= '<span class="vortex-tola-balance-amount" data-address="' . esc_attr( $from_address ) . '">';
            
            if ( is_numeric( $balance ) ) {
                $output .= esc_html( $balance );
            } else {
                $output .= '<span class="vortex-tola-loading">' . __( 'Loading...', 'vortex-ai-marketplace' ) . '</span>';
            }
            
            $output .= '</span> TOLA';
            $output .= '</div>';
        }
        
        $output .= '<form class="vortex-tola-transfer-form" action="" method="post">';
        $output .= wp_nonce_field( 'vortex_tola_transfer', 'vortex_tola_transfer_nonce', true, false );
        $output .= '<input type="hidden" name="from_address" value="' . esc_attr( $from_address ) . '">';
        
        $output .= '<div class="vortex-form-row">';
        $output .= '<label for="vortex-to-address">' . __( 'Recipient Address', 'vortex-ai-marketplace' ) . '</label>';
        $output .= '<input type="text" id="vortex-to-address" name="to_address" required placeholder="' . 
                  esc_attr__( 'Enter recipient wallet address', 'vortex-ai-marketplace' ) . '">';
        $output .= '</div>';
        
        $output .= '<div class="vortex-form-row">';
        $output .= '<label for="vortex-amount">' . __( 'Amount (TOLA)', 'vortex-ai-marketplace' ) . '</label>';
        $output .= '<input type="number" id="vortex-amount" name="amount" step="0.01" min="' . esc_attr( $atts['min_amount'] ) . '" ' . 
                  ( ! empty( $atts['max_amount'] ) ? 'max="' . esc_attr( $atts['max_amount'] ) . '"' : '' ) . 
                  ' required placeholder="' . esc_attr__( 'Enter amount to transfer', 'vortex-ai-marketplace' ) . '">';
        $output .= '</div>';
        
        $output .= '<div class="vortex-form-row vortex-private-key-row">';
        $output .= '<label for="vortex-private-key">' . __( 'Private Key or Signature', 'vortex-ai-marketplace' ) . '</label>';
        $output .= '<input type="password" id="vortex-private-key" name="private_key" required placeholder="' . 
                  esc_attr__( 'Enter your private key or signature', 'vortex-ai-marketplace' ) . '">';
        $output .= '<p class="vortex-security-note">' . 
                  __( 'Your key is used only for this transaction and is not stored.', 'vortex-ai-marketplace' ) . 
                  '</p>';
        $output .= '</div>';
        
        $output .= '<div class="vortex-form-row">';
        $output .= '<button type="submit" class="vortex-tola-transfer-button">' . 
                  __( 'Transfer TOLA', 'vortex-ai-marketplace' ) . 
                  '</button>';
        $output .= '</div>';
        
        $output .= '<div class="vortex-transfer-result"></div>';
        
        $output .= '</form>';
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Shortcode for displaying TOLA staking interface.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    Shortcode output.
     */
    public function staking_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'show_balance' => 'yes',
            'show_rewards' => 'yes',
            'min_stake' => '',
            'max_stake' => '',
        ), $atts, 'vortex_tola_stake' );
        
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            return '<div class="vortex-tola-staking-widget vortex-login-required">' .
                   '<p class="vortex-login-message">' . 
                   __( 'You must be logged in to stake TOLA tokens', 'vortex-ai-marketplace' ) . 
                   '</p>' .
                   '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="vortex-login-button">' . 
                   __( 'Log In', 'vortex-ai-marketplace' ) . 
                   '</a></div>';
        }

        // Get wallet address
        $user_id = get_current_user_id();
        $address = get_user_meta( $user_id, 'vortex_wallet_address', true );

        // If no wallet address, show connect wallet message
        if ( empty( $address ) ) {
            return '<div class="vortex-tola-staking-widget vortex-no-wallet">' .
                   '<p class="vortex-connect-wallet-message">' . 
                   __( 'Connect your wallet to stake TOLA tokens', 'vortex-ai-marketplace' ) . 
                   '</p>' .
                   '<button class="vortex-connect-wallet-button">' . 
                   __( 'Connect Wallet', 'vortex-ai-marketplace' ) . 
                   '</button></div>';
        }

        // Get minimum stake amount
        $minimum_stake = get_option( 'vortex_tola_minimum_stake', '100' );
        if ( empty( $atts['min_stake'] ) ) {
            $atts['min_stake'] = $minimum_stake;
        }

        // Get staking rates
        $staking_rate = get_option( 'vortex_tola_staking_rate', '0.05' );
        $annual_

    /**
     * Add meta boxes for TOLA pricing options to product pages.
     *
     * @since    1.0.0
     */
    public function add_product_meta_boxes() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Add the meta box to products
        add_meta_box(
            'vortex_tola_pricing',
            __('TOLA Token Pricing', 'vortex-ai-marketplace'),
            array($this, 'render_tola_pricing_meta_box'),
            'product',
            'normal',
            'high'
        );
        
        // Add meta box to artwork post type if available
        if (post_type_exists('vortex_artwork')) {
            add_meta_box(
                'vortex_tola_pricing',
                __('TOLA Token Pricing', 'vortex-ai-marketplace'),
                array($this, 'render_tola_pricing_meta_box'),
                'vortex_artwork',
                'side',
                'high'
            );
        }
    }
    
    /**
     * Render the TOLA pricing meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_tola_pricing_meta_box($post) {
        // Add a nonce field for security
        wp_nonce_field('vortex_tola_pricing_nonce', 'vortex_tola_pricing_nonce');
        
        // Get current values
        $enable_tola = get_post_meta($post->ID, '_vortex_enable_tola_payment', true);
        $tola_price = get_post_meta($post->ID, '_vortex_tola_price', true);
        $tola_discount = get_post_meta($post->ID, '_vortex_tola_discount', true);
        
        // Default discount from settings
        $default_discount = get_option('vortex_tola_discount', 10);
        
        if (!$tola_discount) {
            $tola_discount = $default_discount;
        }
        
        // Output form fields
        ?>
        <div class="vortex-meta-field">
            <input type="checkbox" id="vortex_enable_tola_payment" name="vortex_enable_tola_payment" 
                   value="1" <?php checked($enable_tola, '1'); ?>>
            <label for="vortex_enable_tola_payment">
                <?php esc_html_e('Enable TOLA Token Payment', 'vortex-ai-marketplace'); ?>
            </label>
            <p class="description">
                <?php esc_html_e('Allow customers to purchase this item using TOLA tokens.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="vortex-tola-options" style="<?php echo $enable_tola ? '' : 'display:none;'; ?>">
            <div class="vortex-meta-field">
                <label for="vortex_tola_price">
                    <?php esc_html_e('TOLA Price', 'vortex-ai-marketplace'); ?>:
                </label>
                <input type="number" id="vortex_tola_price" name="vortex_tola_price" 
                       value="<?php echo esc_attr($tola_price); ?>" step="0.01" min="0">
                <p class="description">
                    <?php esc_html_e('Set a custom TOLA token price. Leave empty to auto-calculate from fiat price.', 'vortex-ai-marketplace'); ?>
                </p>
            </div>
            
            <div class="vortex-meta-field">
                <label for="vortex_tola_discount">
                    <?php esc_html_e('TOLA Discount (%)', 'vortex-ai-marketplace'); ?>:
                </label>
                <input type="number" id="vortex_tola_discount" name="vortex_tola_discount" 
                       value="<?php echo esc_attr($tola_discount); ?>" step="1" min="0" max="100">
                <p class="description">
                    <?php esc_html_e('Discount percentage for TOLA token payments. Default is set in TOLA settings.', 'vortex-ai-marketplace'); ?>
                </p>
            </div>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                $('#vortex_enable_tola_payment').change(function() {
                    if($(this).is(':checked')) {
                        $('.vortex-tola-options').show();
                    } else {
                        $('.vortex-tola-options').hide();
                    }
                });
            });
        </script>
        <?php
    }
    
    /**
     * Save TOLA pricing meta box data.
     *
     * @since    1.0.0
     * @param    int    $post_id    The post ID.
     */
    public function save_tola_pricing_meta_box($post_id) {
        // Check if nonce is set
        if (!isset($_POST['vortex_tola_pricing_nonce'])) {
            return;
        }
        
        // Verify the nonce
        if (!wp_verify_nonce($_POST['vortex_tola_pricing_nonce'], 'vortex_tola_pricing_nonce')) {
            return;
        }
        
        // If this is an autosave, don't do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (isset($_POST['post_type']) && 'product' === $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        } elseif (isset($_POST['post_type']) && 'vortex_artwork' === $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        } else {
            return;
        }
        
        // Save the data
        $enable_tola = isset($_POST['vortex_enable_tola_payment']) ? '1' : '0';
        update_post_meta($post_id, '_vortex_enable_tola_payment', $enable_tola);
        
        if (isset($_POST['vortex_tola_price'])) {
            $tola_price = sanitize_text_field($_POST['vortex_tola_price']);
            update_post_meta($post_id, '_vortex_tola_price', $tola_price);
        }
        
        if (isset($_POST['vortex_tola_discount'])) {
            $tola_discount = absint($_POST['vortex_tola_discount']);
            if ($tola_discount > 100) {
                $tola_discount = 100;
            }
            update_post_meta($post_id, '_vortex_tola_discount', $tola_discount);
        }
    }

    /**
     * Add TOLA pricing fields to WooCommerce product options.
     * 
     * @since    1.0.0
     */
    public function add_tola_product_fields() {
        global $post;
        
        echo '<div class="options_group show_if_simple show_if_variable">';
        
        // Enable TOLA Payment Checkbox
        woocommerce_wp_checkbox(
            array(
                'id'            => '_vortex_enable_tola_payment',
                'label'         => __('Enable TOLA Payment', 'vortex-ai-marketplace'),
                'description'   => __('Allow customers to pay for this product using TOLA tokens.', 'vortex-ai-marketplace')
            )
        );
        
        // TOLA Price
        woocommerce_wp_text_input(
            array(
                'id'            => '_vortex_tola_price',
                'label'         => __('TOLA Price', 'vortex-ai-marketplace'),
                'desc_tip'      => true,
                'description'   => __('Custom price in TOLA tokens. Leave empty to auto-calculate from regular price.', 'vortex-ai-marketplace'),
                'type'          => 'number',
                'custom_attributes' => array(
                    'step'  => '0.01',
                    'min'   => '0'
                )
            )
        );
        
        // TOLA Discount
        $default_discount = get_option('vortex_tola_discount', 10);
        woocommerce_wp_text_input(
            array(
                'id'            => '_vortex_tola_discount',
                'label'         => __('TOLA Discount (%)', 'vortex-ai-marketplace'),
                'desc_tip'      => true,
                'description'   => __('Discount percentage for TOLA token payments.', 'vortex-ai-marketplace'),
                'type'          => 'number',
                'default'       => $default_discount,
                'custom_attributes' => array(
                    'step'  => '1',
                    'min'   => '0',
                    'max'   => '100'
                )
            )
        );
        
        echo '</div>';
    }
    
    /**
     * Save TOLA pricing fields for WooCommerce products.
     * 
     * @since    1.0.0
     * @param    int    $post_id    The post ID.
     */
    public function save_tola_product_fields($post_id) {
        // Enable TOLA Payment
        $enable_tola = isset($_POST['_vortex_enable_tola_payment']) ? 'yes' : 'no';
        update_post_meta($post_id, '_vortex_enable_tola_payment', $enable_tola);
        
        // TOLA Price
        if (isset($_POST['_vortex_tola_price'])) {
            $tola_price = wc_format_decimal(sanitize_text_field($_POST['_vortex_tola_price']));
            update_post_meta($post_id, '_vortex_tola_price', $tola_price);
        }
        
        // TOLA Discount
        if (isset($_POST['_vortex_tola_discount'])) {
            $tola_discount = absint($_POST['_vortex_tola_discount']);
            if ($tola_discount > 100) {
                $tola_discount = 100;
            }
            update_post_meta($post_id, '_vortex_tola_discount', $tola_discount);
        }
    }
} 