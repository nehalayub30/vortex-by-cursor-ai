<?php
/**
 * Smart Contract Integration for TOLA Tokens with Royalty System
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Blockchain_Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Smart_Contract Class
 * 
 * Handles interaction with blockchain smart contracts for
 * token minting, transfers, and verification. Implements
 * the royalty system for HURAII and creators.
 *
 * @since 1.0.0
 */
class VORTEX_Smart_Contract {
    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var object
     */
    protected static $instance = null;
    
    /**
     * Smart contract configuration
     *
     * @since 1.0.0
     * @var array
     */
    private $contract_config = array();
    
    /**
     * Active AI agent instances
     *
     * @since 1.0.0
     * @var array
     */
    private $ai_agents = array();
    
    /**
     * Web3 provider URL
     *
     * @since 1.0.0
     * @var string
     */
    private $web3_provider = '';
    
    /**
     * Default HURAII royalty percentage
     * 
     * @since 1.0.0
     * @var float
     */
    private $huraii_royalty_percentage = 5.0;
    
    /**
     * Maximum artist royalty percentage
     * 
     * @since 1.0.0
     * @var float
     */
    private $max_artist_royalty_percentage = 15.0;
    
    /**
     * HURAII creator wallet address
     * 
     * @since 1.0.0
     * @var string
     */
    private $huraii_creator_address = '';
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Load contract configuration
        $this->load_configuration();
        
        // Initialize AI agents
        $this->initialize_ai_agents();
        
        // Set up hooks
        $this->setup_hooks();
    }
    
    /**
     * Get instance of this class.
     *
     * @since 1.0.0
     * @return VORTEX_Smart_Contract
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load contract configuration
     *
     * @since 1.0.0
     * @return void
     */
    private function load_configuration() {
        $this->contract_config = get_option('vortex_smart_contract_config', array(
            'tola_contract_address' => '',
            'nft_contract_address' => '',
            'chain_id' => '1', // Ethereum mainnet
            'api_key' => '',
            'gas_limit' => 300000,
            'test_mode' => true
        ));
        
        // Set HURAII creator wallet address
        $this->huraii_creator_address = get_option('vortex_huraii_creator_address', '');
        
        // If creator address is not set, use a default value
        if (empty($this->huraii_creator_address)) {
            // This should be set to the actual wallet address of the HURAII creator in production
            $this->huraii_creator_address = '0x0000000000000000000000000000000000000000';
        }
        
        // Set Web3 provider
        if ($this->contract_config['test_mode']) {
            // Use testnet
            $this->web3_provider = 'https://goerli.infura.io/v3/' . $this->contract_config['api_key'];
        } else {
            // Use mainnet
            $this->web3_provider = 'https://mainnet.infura.io/v3/' . $this->contract_config['api_key'];
        }
    }
    
    /**
     * Initialize AI agents for blockchain operations
     *
     * @since 1.0.0
     * @return void
     */
    private function initialize_ai_agents() {
        // Initialize HURAII for NFT generation
        $this->ai_agents['HURAII'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'blockchain_operations',
            'capabilities' => array(
                'nft_metadata_generation',
                'provenance_verification',
                'visual_authentication',
                'royalty_tracking'
            )
        );
        
        // Initialize CLOE for token curation
        $this->ai_agents['CLOE'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'blockchain_curation',
            'capabilities' => array(
                'collection_organization',
                'on-chain_trend_analysis',
                'marketplace_optimization',
                'royalty_distribution_analysis'
            )
        );
        
        // Initialize BusinessStrategist for token economics
        $this->ai_agents['BusinessStrategist'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'blockchain_economics',
            'capabilities' => array(
                'gas_optimization',
                'market_timing_analysis',
                'value_maximization',
                'royalty_optimization'
            )
        );
        
        do_action('vortex_ai_agent_init', 'blockchain_integration', array_keys($this->ai_agents), 'active');
    }
    
    /**
     * Set up hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function setup_hooks() {
        // Admin settings
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_royalty_settings_page'));
        
        // AJAX handlers
        add_action('wp_ajax_vortex_mint_nft', array($this, 'ajax_mint_nft'));
        add_action('wp_ajax_vortex_verify_token', array($this, 'ajax_verify_token'));
        add_action('wp_ajax_vortex_set_artist_royalty', array($this, 'ajax_set_artist_royalty'));
        
        // Integration with TOLA system
        add_action('vortex_token_created', array($this, 'on_token_created'), 10, 2);
        add_action('vortex_token_transferred', array($this, 'on_token_transferred'), 10, 3);
        add_action('vortex_artwork_published', array($this, 'handle_artwork_royalties'), 10, 2);
        
        // Secondary sale royalties
        add_action('vortex_artwork_sale', array($this, 'process_sale_royalties'), 10, 4);
        
        // Add metadata fields to artwork edit screen
        add_action('add_meta_boxes', array($this, 'add_royalty_meta_box'));
        add_action('save_post_vortex-artwork', array($this, 'save_royalty_meta'));
        
        // Enqueue blockchain scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Add royalty settings meta box to artwork edit screen
     *
     * @since 1.0.0
     */
    public function add_royalty_meta_box() {
        add_meta_box(
            'vortex_royalty_settings',
            __('Royalty Settings', 'vortex'),
            array($this, 'render_royalty_meta_box'),
            'vortex-artwork',
            'side',
            'high'
        );
    }
    
    /**
     * Render royalty settings meta box
     *
     * @since 1.0.0
     * @param WP_Post $post Current post object
     */
    public function render_royalty_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('vortex_royalty_settings', 'vortex_royalty_nonce');
        
        // Get current royalty percentage
        $artist_royalty = get_post_meta($post->ID, 'vortex_artist_royalty', true);
        if ($artist_royalty === '') {
            $artist_royalty = 10.0; // Default artist royalty
        }
        
        // Display field for artist royalty
        ?>
        <p>
            <label for="vortex_artist_royalty">
                <?php esc_html_e('Your Royalty Percentage:', 'vortex'); ?>
            </label>
            <input type="number" step="0.1" min="0" max="15" id="vortex_artist_royalty" 
                name="vortex_artist_royalty" value="<?php echo esc_attr($artist_royalty); ?>" 
                style="width: 70px;" />
            %
        </p>
        <p class="description">
            <?php esc_html_e('Set your royalty percentage for secondary sales (max 15%).', 'vortex'); ?>
        </p>
        <p class="description">
            <strong><?php esc_html_e('Note:', 'vortex'); ?></strong> 
            <?php esc_html_e('An additional 5% royalty goes to HURAII & SEED ART TECHNIQUE creator.', 'vortex'); ?>
        </p>
        <p class="description">
            <?php esc_html_e('Total royalties: ', 'vortex'); ?>
            <span id="total_royalty"><?php echo esc_html(number_format($artist_royalty + 5, 1)); ?></span>%
        </p>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#vortex_artist_royalty').on('input', function() {
                    var artistRoyalty = parseFloat($(this).val()) || 0;
                    if (artistRoyalty > 15) {
                        artistRoyalty = 15;
                        $(this).val(15);
                    }
                    $('#total_royalty').text((artistRoyalty + 5).toFixed(1));
                });
            });
        </script>
        <?php
    }
    
    /**
     * Save royalty meta when artwork is saved
     *
     * @since 1.0.0
     * @param int $post_id Post ID
     */
    public function save_royalty_meta($post_id) {
        // Check if nonce is set
        if (!isset($_POST['vortex_royalty_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['vortex_royalty_nonce'], 'vortex_royalty_settings')) {
            return;
        }
        
        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save artist royalty
        if (isset($_POST['vortex_artist_royalty'])) {
            $artist_royalty = floatval($_POST['vortex_artist_royalty']);
            
            // Limit to max allowed percentage
            $artist_royalty = min($artist_royalty, $this->max_artist_royalty_percentage);
            
            update_post_meta($post_id, 'vortex_artist_royalty', $artist_royalty);
        }
    }
    
    /**
     * Handle artwork royalties when published
     *
     * @since 1.0.0
     * @param int $artwork_id Artwork post ID
     * @param int $artist_id Artist user ID
     */
    public function handle_artwork_royalties($artwork_id, $artist_id) {
        // Get artwork data
        $artwork = get_post($artwork_id);
        if (!$artwork || $artwork->post_type !== 'vortex-artwork') {
            return;
        }
        
        // Set default royalty if not set
        $artist_royalty = get_post_meta($artwork_id, 'vortex_artist_royalty', true);
        if ($artist_royalty === '') {
            $artist_royalty = 10.0; // Default artist royalty
            update_post_meta($artwork_id, 'vortex_artist_royalty', $artist_royalty);
        }
        
        // Store royalty configuration in metadata
        $royalty_config = array(
            'huraii_creator' => array(
                'address' => $this->huraii_creator_address,
                'percentage' => $this->huraii_royalty_percentage
            ),
            'artist' => array(
                'user_id' => $artist_id,
                'address' => get_user_meta($artist_id, 'vortex_wallet_address', true),
                'percentage' => floatval($artist_royalty)
            ),
            'total_percentage' => $this->huraii_royalty_percentage + floatval($artist_royalty),
            'timestamp' => current_time('timestamp')
        );
        
        update_post_meta($artwork_id, 'vortex_royalty_config', $royalty_config);
        
        // If this is HURAII-generated artwork, mark it for royalty tracking
        $is_huraii_generated = get_post_meta($artwork_id, 'vortex_huraii_generated', true);
        if ($is_huraii_generated) {
            update_post_meta($artwork_id, 'vortex_requires_huraii_royalty', true);
        }
    }
    
    /**
     * Process royalties when artwork is sold
     *
     * @since 1.0.0
     * @param int $artwork_id Artwork post ID
     * @param int $seller_id Seller user ID
     * @param int $buyer_id Buyer user ID
     * @param float $sale_amount Sale amount
     * @return array Royalty distribution details
     */
    public function process_sale_royalties($artwork_id, $seller_id, $buyer_id, $sale_amount) {
        // Get royalty configuration
        $royalty_config = get_post_meta($artwork_id, 'vortex_royalty_config', true);
        if (empty($royalty_config)) {
            // Create default royalty config if not exists
            $this->handle_artwork_royalties($artwork_id, get_post_meta($artwork_id, 'vortex_artist_id', true));
            $royalty_config = get_post_meta($artwork_id, 'vortex_royalty_config', true);
        }
        
        // Calculate royalty amounts
        $huraii_royalty_amount = ($sale_amount * $royalty_config['huraii_creator']['percentage']) / 100;
        $artist_royalty_amount = ($sale_amount * $royalty_config['artist']['percentage']) / 100;
        $total_royalty_amount = $huraii_royalty_amount + $artist_royalty_amount;
        
        // Amount that goes to seller after royalties
        $seller_amount = $sale_amount - $total_royalty_amount;
        
        // Record royalty transaction for HURAII creator
        $huraii_transaction = new VORTEX_Transaction();
        $huraii_transaction_id = $huraii_transaction->create(array(
            'type' => 'royalty_payment',
            'token_id' => 0,
            'from_user_id' => $buyer_id,
            'to_user_id' => 0, // System account for HURAII creator
            'amount' => $huraii_royalty_amount,
            'currency_type' => 'tola_credit',
            'status' => 'completed',
            'notes' => sprintf(
                __('HURAII creator royalty for artwork #%d', 'vortex'),
                $artwork_id
            ),
            'metadata' => array(
                'artwork_id' => $artwork_id,
                'royalty_type' => 'huraii_creator',
                'royalty_percentage' => $royalty_config['huraii_creator']['percentage'],
                'sale_amount' => $sale_amount
            )
        ));
        
        // Record royalty transaction for artist
        $artist_id = $royalty_config['artist']['user_id'];
        $artist_transaction = new VORTEX_Transaction();
        $artist_transaction_id = $artist_transaction->create(array(
            'type' => 'royalty_payment',
            'token_id' => 0,
            'from_user_id' => $buyer_id,
            'to_user_id' => $artist_id,
            'amount' => $artist_royalty_amount,
            'currency_type' => 'tola_credit',
            'status' => 'completed',
            'notes' => sprintf(
                __('Artist royalty for artwork #%d', 'vortex'),
                $artwork_id
            ),
            'metadata' => array(
                'artwork_id' => $artwork_id,
                'royalty_type' => 'artist',
                'royalty_percentage' => $royalty_config['artist']['percentage'],
                'sale_amount' => $sale_amount
            )
        ));
        
        // Record the distribution for tracking
        $royalty_distribution = array(
            'sale_id' => md5($artwork_id . $seller_id . $buyer_id . time()),
            'artwork_id' => $artwork_id,
            'sale_amount' => $sale_amount,
            'sale_date' => current_time('mysql'),
            'huraii_creator' => array(
                'amount' => $huraii_royalty_amount,
                'percentage' => $royalty_config['huraii_creator']['percentage'],
                'transaction_id' => $huraii_transaction_id
            ),
            'artist' => array(
                'user_id' => $artist_id,
                'amount' => $artist_royalty_amount,
                'percentage' => $royalty_config['artist']['percentage'],
                'transaction_id' => $artist_transaction_id
            ),
            'seller' => array(
                'user_id' => $seller_id,
                'amount' => $seller_amount
            ),
            'buyer' => array(
                'user_id' => $buyer_id
            )
        );
        
        // Record royalty distribution in artwork metadata
        $royalty_history = get_post_meta($artwork_id, 'vortex_royalty_history', true);
        if (!is_array($royalty_history)) {
            $royalty_history = array();
        }
        $royalty_history[] = $royalty_distribution;
        update_post_meta($artwork_id, 'vortex_royalty_history', $royalty_history);
        
        // Learn from this royalty distribution
        $this->learn_from_royalty_distribution($royalty_distribution);
        
        return $royalty_distribution;
    }
    
    /**
     * Mint a new NFT on the blockchain with royalty configuration
     *
     * @since 1.0.0
     * @param int $artwork_id The artwork post ID
     * @param int $owner_id The owner user ID
     * @param array $metadata Additional metadata
     * @return array|WP_Error Transaction details or error
     */
    public function mint_nft($artwork_id, $owner_id, $metadata = array()) {
        // Check if contract address is configured
        if (empty($this->contract_config['nft_contract_address'])) {
            return new WP_Error('contract_not_configured', __('NFT contract not configured', 'vortex'));
        }
        
        // Get artwork data
        $artwork = get_post($artwork_id);
        if (!$artwork || $artwork->post_type !== 'vortex-artwork') {
            return new WP_Error('invalid_artwork', __('Invalid artwork', 'vortex'));
        }
        
        // Get royalty configuration
        $royalty_config = get_post_meta($artwork_id, 'vortex_royalty_config', true);
        if (empty($royalty_config)) {
            // Create default royalty config if not exists
            $this->handle_artwork_royalties($artwork_id, get_post_meta($artwork_id, 'vortex_artist_id', true));
            $royalty_config = get_post_meta($artwork_id, 'vortex_royalty_config', true);
        }
        
        // Get artwork metadata
        $artwork_meta = array(
            'title' => $artwork->post_title,
            'description' => $artwork->post_excerpt ?: $artwork->post_content,
            'artist_id' => get_post_meta($artwork_id, 'vortex_artist_id', true),
            'creation_date' => $artwork->post_date,
            'edition_number' => get_post_meta($artwork_id, 'vortex_edition_number', true) ?: 1,
            'total_editions' => get_post_meta($artwork_id, 'vortex_total_editions', true) ?: 1,
        );
        
        // Get artwork image
        $image_url = get_the_post_thumbnail_url($artwork_id, 'full');
        if (!$image_url) {
            return new WP_Error('no_image', __('Artwork has no image', 'vortex'));
        }
        
        // Generate unique URL for the artwork
        $unique_url = site_url('vortex-artwork/' . $artwork_id . '/' . md5($artwork_id . $owner_id . time()));
        update_post_meta($artwork_id, 'vortex_unique_url', $unique_url);
        
        // Generate IPFS metadata using HURAII's analysis
        $ipfs_metadata = $this->generate_nft_metadata($artwork_id, $artwork_meta, $image_url, $metadata, $royalty_config, $unique_url);
        if (is_wp_error($ipfs_metadata)) {
            return $ipfs_metadata;
        }
        
        // In a real implementation, this would interact with the blockchain
        // Here we'll simulate a successful transaction
        $transaction = array(
            'transaction_hash' => 'tx_' . md5($artwork_id . $owner_id . time()),
            'contract_address' => $this->contract_config['nft_contract_address'],
            'token_id' => rand(10000, 9999999),
            'owner_address' => get_user_meta($owner_id, 'vortex_wallet_address', true),
            'metadata_uri' => $ipfs_metadata['metadata_uri'],
            'ipfs_image' => $ipfs_metadata['ipfs_image'],
            'unique_url' => $unique_url,
            'royalty_config' => $royalty_config,
            'timestamp' => current_time('timestamp')
        );
        
        // Store transaction details
        update_post_meta($artwork_id, 'vortex_nft_transaction', $transaction);
        update_post_meta($artwork_id, 'vortex_blockchain_token_id', $transaction['token_id']);
        
        // Track the minting for AI learning
        $this->learn_from_nft_minting($artwork_id, $owner_id, $transaction);
        
        return $transaction;
    }
    
    /**
     * Generate NFT metadata using HURAII with royalty information
     *
     * @since 1.0.0
     * @param int $artwork_id Artwork ID
     * @param array $artwork_meta Artwork metadata
     * @param string $image_url Artwork image URL
     * @param array $additional_metadata Additional metadata
     * @param array $royalty_config Royalty configuration
     * @param string $unique_url Unique URL for the artwork
     * @return array|WP_Error Metadata with IPFS URIs or error
     */
    private function generate_nft_metadata($artwork_id, $artwork_meta, $image_url, $additional_metadata, $royalty_config, $unique_url) {
        // Get AI insights from HURAII if available
        $ai_insights = array();
        
        if (class_exists('VORTEX_HURAII') && $this->ai_agents['HURAII']['active']) {
            $huraii = VORTEX_HURAII::get_instance();
            $seed_art_analysis = $huraii->analyze_seed_art_components($artwork_id);
            
            if (!is_wp_error($seed_art_analysis)) {
                $ai_insights['seed_art_analysis'] = $seed_art_analysis;
            }
        }
        
        // Format royalties for the metadata
        $royalties = array(
            array(
                'recipient' => 'HURAII & SEED ART TECHNIQUE Creator',
                'recipient_address' => $royalty_config['huraii_creator']['address'],
                'percentage' => $royalty_config['huraii_creator']['percentage']
            ),
            array(
                'recipient' => 'Artist',
                'recipient_address' => $royalty_config['artist']['address'],
                'percentage' => $royalty_config['artist']['percentage']
            )
        );
        
        // Combine all metadata
        $nft_metadata = array(
            'name' => $artwork_meta['title'],
            'description' => $artwork_meta['description'],
            'external_url' => $unique_url,
            'image' => $image_url, // This would be replaced with IPFS link in production
            'attributes' => array(
                array(
                    'trait_type' => 'Artist',
                    'value' => get_the_author_meta('display_name', $artwork_meta['artist_id'])
                ),
                array(
                    'trait_type' => 'Edition',
                    'value' => $artwork_meta['edition_number'] . ' of ' . $artwork_meta['total_editions']
                ),
                array(
                    'trait_type' => 'Creation Date',
                    'value' => date('Y-m-d', strtotime($artwork_meta['creation_date']))
                ),
                array(
                    'trait_type' => 'Total Royalties',
                    'value' => $royalty_config['total_percentage'] . '%'
                )
            ),
            'ai_insights' => $ai_insights,
            'royalties' => $royalties,
            'additional_metadata' => $additional_metadata
        );
        
        // In a real implementation, this would upload to IPFS
        // Here we'll simulate IPFS links
        $ipfs_hash = 'Qm' . substr(md5($artwork_id . json_encode($nft_metadata) . time()), 0, 44);
        $image_ipfs_hash = 'Qm' . substr(md5($image_url . time()), 0, 44);
        
        return array(
            'metadata_uri' => "ipfs://{$ipfs_hash}",
            'ipfs_image' => "ipfs://{$image_ipfs_hash}",
            'metadata' => $nft_metadata
        );
    }
    
    /**
     * Learn from royalty distribution
     *
     * @since 1.0.0
     * @param array $distribution Royalty distribution details
     * @return void
     */
    private function learn_from_royalty_distribution($distribution) {
        // Skip if all AI agents are inactive
        if (!$this->ai_agents['HURAII']['active'] && 
            !$this->ai_agents['CLOE']['active'] && 
            !$this->ai_agents['BusinessStrategist']['active']) {
            return;
        }
        
        // HURAII learns from royalty distribution
        if ($this->ai_agents['HURAII']['active']) {
            do_action('vortex_ai_agent_learn', 'HURAII', 'royalty_distribution', array(
                'artwork_id' => $distribution['artwork_id'],
                'sale_amount' => $distribution['sale_amount'],
                'huraii_royalty_amount' => $distribution['huraii_creator']['amount'],
                'artist_royalty_amount' => $distribution['artist']['amount'],
                'timestamp' => current_time('timestamp')
            ));
        }
        
        // CLOE learns from sales patterns
        if ($this->ai_agents['CLOE']['active']) {
            do_action('vortex_ai_agent_learn', 'CLOE', 'artwork_sale', array(
                'artwork_id' => $distribution['artwork_id'],
                'sale_amount' => $distribution['sale_amount'],
                'buyer_id' => $distribution['buyer']['user_id'],
                'seller_id' => $distribution['seller']['user_id'],
                'timestamp' => current_time('timestamp')
            ));
        }
        
        // BusinessStrategist learns from economic aspects
        if ($this->ai_agents['BusinessStrategist']['active']) {
            do_action('vortex_ai_agent_learn', 'BusinessStrategist', 'royalty_economics', array(
                'artwork_id' => $distribution['artwork_id'],
                'sale_amount' => $distribution['sale_amount'],
                'total_royalty_percentage' => 
                    $distribution['huraii_creator']['percentage'] + 
                    $distribution['artist']['percentage'],
                'total_royalty_amount' => 
                    $distribution['huraii_creator']['amount'] + 
                    $distribution['artist']['amount'],
                'seller_net_amount' => $distribution['seller']['amount'],
                'timestamp' => current_time('timestamp')
            ));
        }
    }
    
    /**
     * Add royalty settings page to admin menu
     *
     * @since 1.0.0
     */
    public function add_royalty_settings_page() {
        add_submenu_page(
            'vortex-marketplace',
            __('VORTEX Royalty Settings', 'vortex'),
            __('Royalty Settings', 'vortex'),
            'manage_options',
            'vortex-royalty-settings',
            array($this, 'render_royalty_settings_page')
        );
    }
    
    /**
     * Register plugin settings
     *
     * @since 1.0.0
     */
    public function register_settings() {
        register_setting('vortex_royalty_settings', 'vortex_huraii_creator_address');
        
        add_settings_section(
            'vortex_royalty_section',
            __('HURAII Royalty Settings', 'vortex'),
            array($this, 'render_royalty_section'),
            'vortex-royalty-settings'
        );
        
        add_settings_field(
            'vortex_huraii_creator_address',
            __('HURAII Creator Wallet Address', 'vortex'),
            array($this, 'render_creator_address_field'),
            'vortex-royalty-settings',
            'vortex_royalty_section'
        );
    }
    
    /**
     * Render royalty settings section
     *
     * @since 1.0.0
     */
    public function render_royalty_section() {
        echo '<p>' . __('Configure the royalty settings for HURAII and the SEED ART TECHNIQUE Creator.', 'vortex') . '</p>';
        echo '<p>' . __('A 5% royalty will be automatically applied to all sales of HURAII-generated artwork.', 'vortex') . '</p>';
    }
    
    /**
     * Render creator address field
     *
     * @since 1.0.0
     */
    public function render_creator_address_field() {
        $address = get_option('vortex_huraii_creator_address', '');
        ?>
        <input type="text" name="vortex_huraii_creator_address" value="<?php echo esc_attr($address); ?>" class="regular-text" />
        <p class="description">
            <?php esc_html_e('Enter the wallet address of the HURAII & SEED ART TECHNIQUE creator for royalty payments.', 'vortex'); ?>
        </p>
        <?php
    }
    
    /**
     * Render royalty settings page
     *
     * @since 1.0.0
     */
    public function render_royalty_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('VORTEX Royalty Settings', 'vortex'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('vortex_royalty_settings');
                do_settings_sections('vortex-royalty-settings');
                submit_button();
                ?>
            </form>
            
            <div class="vortex-royalty-info">
                <h2><?php esc_html_e('About VORTEX Royalties', 'vortex'); ?></h2>
                <p>
                    <?php esc_html_e('The VORTEX platform implements a dual royalty system:', 'vortex'); ?>
                </p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><?php esc_html_e('5% royalty to the creator of HURAII & the SEED ART TECHNIQUE for all HURAII-generated artworks', 'vortex'); ?></li>
                    <li><?php esc_html_e('Up to 15% royalty for the artist (configurable per artwork)', 'vortex'); ?></li>
                </ul>
                <p>
                    <?php esc_html_e('These royalties are enforced through our smart contract and apply to all sales on the VORTEX platform.', 'vortex'); ?>
                </p>
                
                <h3><?php esc_html_e('Royalty Distribution Statistics', 'vortex'); ?></h3>
                <?php $this->display_royalty_statistics(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display royalty statistics
     *
     * @since 1.0.0
     */
    private function display_royalty_statistics() {
        global $wpdb;
        
        // Get data from royalty transactions
        $transactions_table = $wpdb->prefix . 'vortex_transactions';
        
        $huraii_royalties = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$transactions_table} 
             WHERE type = %s AND metadata LIKE %s",
            'royalty_payment',
            '%"royalty_type":"huraii_creator"%'
        ));
        
        $artist_royalties = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$transactions_table} 
             WHERE type = %s AND metadata LIKE %s",
            'royalty_payment',
            '%"royalty_type":"artist"%'
        ));
        
        $total_sales = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$transactions_table} 
             WHERE type = %s",
            'token_sale'
        ));
        
        $huraii_royalties = $huraii_royalties ? floatval($huraii_royalties) : 0;
        $artist_royalties = $artist_royalties ? floatval($artist_royalties) : 0;
        $total_sales = $total_sales ? intval($total_sales) : 0;
        
        ?>
        <div class="vortex-royalty-stats" style="display: flex; margin-top: 20px;">
            <div class="vortex-stat-box" style="flex: 1; padding: 15px; background: #f9f9f9; margin-right: 15px; border-radius: 5px; text-align: center;">
                <h4><?php esc_html_e('Total HURAII Creator Royalties', 'vortex'); ?></h4>
                <div class="vortex-stat-value"><?php echo esc_html(number_format($huraii_royalties, 2)); ?> TOLA</div>
            </div>
            
            <div class="vortex-stat-box" style="flex: 1; padding: 15px; background: #f9f9f9; margin-right: 15px; border-radius: 5px; text-align: center;">
                <h4><?php esc_html_e('Total Artist Royalties', 'vortex'); ?></h4>
                <div class="vortex-stat-value"><?php echo esc_html(number_format($artist_royalties, 2)); ?> TOLA</div>
            </div>
            
            <div class="vortex-stat-box" style="flex: 1; padding: 15px; background: #f9f9f9; border-radius: 5px; text-align: center;">
                <h4><?php esc_html_e('Total Sales', 'vortex'); ?></h4>
                <div class="vortex-stat-value"><?php echo esc_html(number_format($total_sales)); ?></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue blockchain scripts
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueue_scripts() {
        // Only enqueue on relevant pages
        if (!is_singular('vortex-artwork') && !is_page('marketplace') && !is_page('wallet')) {
            return;
        }
        
        wp_enqueue_script('web3', 'https://cdn.jsdelivr.net/npm/web3@1.7.0/dist/web3.min.js', array(), '1.7.0', true);
        wp_enqueue_script('vortex-blockchain', VORTEX_PLUGIN_URL . 'assets/js/blockchain.js', array('jquery', 'web3'), VORTEX_VERSION, true);
        
        wp_localize_script('vortex-blockchain', 'vortexBlockchain', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_blockchain_nonce'),
            'contractAddress' => $this->contract_config['tola_contract_address'],
            'nftContractAddress' => $this->contract_config['nft_contract_address'],
            'chainId' => $this->contract_config['chain_id'],
            'testMode' => $this->contract_config['test_mode']
        ));
    }
} 