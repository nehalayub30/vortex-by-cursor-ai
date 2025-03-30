<?php
namespace Vortex\AI\Blockchain;

use Vortex\AI\Interfaces\BlockchainInterface;

class Blockchain implements BlockchainInterface {
    /**
     * Platform wallet address for receiving royalties
     * @var string
     */
    private $platform_wallet;
    
    /**
     * Network to use (ethereum, polygon, etc.)
     * @var string
     */
    private $network;
    
    /**
     * Default royalty percentage for VORTEX AI AGENTS
     * @var float
     */
    private $platform_royalty_percentage = 3.0;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->platform_wallet = get_option('vortex_platform_wallet', '');
        $this->network = get_option('vortex_blockchain_network', 'ethereum');
        
        // Add hooks for admin settings
        add_action('admin_init', array($this, 'register_blockchain_settings'));
    }
    
    /**
     * Register blockchain settings
     */
    public function register_blockchain_settings() {
        register_setting('vortex_blockchain_settings', 'vortex_platform_wallet');
        register_setting('vortex_blockchain_settings', 'vortex_blockchain_network');
    }
    
    /**
     * Connect wallet to the platform
     * @param string $wallet_address User's wallet address
     * @return bool Success status
     */
    public function connect_wallet($wallet_address) {
        if (empty($wallet_address) || !$this->is_valid_address($wallet_address)) {
            return false;
        }
        
        // Store the connected wallet in user meta
        $user_id = get_current_user_id();
        if ($user_id) {
            update_user_meta($user_id, 'vortex_wallet_address', sanitize_text_field($wallet_address));
            return true;
        }
        
        return false;
    }

    /**
     * Mint new NFT with royalty information
     * 
     * @param array $metadata NFT metadata including:
     *                        - title: Artwork title
     *                        - description: Artwork description
     *                        - image: URL to the artwork image
     *                        - creator_wallet: Creator's wallet address
     *                        - creator_royalty: Additional creator royalty percentage (optional)
     *                        - royalty_shares: Array of {wallet, percentage} for royalty sharing (optional)
     * @return string NFT token ID or empty on failure
     */
    public function mint_nft($metadata) {
        if (empty($this->platform_wallet)) {
            error_log('VORTEX: Platform wallet not configured');
            return '';
        }
        
        // Validate metadata
        if (empty($metadata['title']) || empty($metadata['image']) || empty($metadata['creator_wallet'])) {
            return '';
        }
        
        // Prepare royalty data
        $royalties = array(
            // Platform royalty - fixed 3%
            array(
                'wallet' => $this->platform_wallet,
                'percentage' => $this->platform_royalty_percentage
            )
        );
        
        // Add creator royalty if specified
        if (!empty($metadata['creator_royalty']) && is_numeric($metadata['creator_royalty'])) {
            $royalties[] = array(
                'wallet' => $metadata['creator_wallet'],
                'percentage' => floatval($metadata['creator_royalty'])
            );
        }
        
        // Add additional royalty shares if specified
        if (!empty($metadata['royalty_shares']) && is_array($metadata['royalty_shares'])) {
            foreach ($metadata['royalty_shares'] as $share) {
                if (!empty($share['wallet']) && !empty($share['percentage'])) {
                    $royalties[] = array(
                        'wallet' => $share['wallet'],
                        'percentage' => floatval($share['percentage'])
                    );
                }
            }
        }
        
        // In a real implementation, this would communicate with the blockchain
        // For demonstration purposes, we'll return a mock token ID
        $token_id = 'vortex_' . time() . '_' . wp_generate_password(8, false);
        
        // Store NFT details in WordPress for reference
        $nft_post_id = wp_insert_post(array(
            'post_title' => sanitize_text_field($metadata['title']),
            'post_type' => 'vortex_nft',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
            'meta_input' => array(
                'vortex_nft_metadata' => $metadata,
                'vortex_nft_token_id' => $token_id,
                'vortex_nft_royalties' => $royalties,
                'vortex_nft_network' => $this->network
            )
        ));
        
        if (is_wp_error($nft_post_id)) {
            error_log('VORTEX: Failed to store NFT details - ' . $nft_post_id->get_error_message());
            return '';
        }
        
        // Return the token ID
        return $token_id;
    }
    
    /**
     * Get transaction status
     * 
     * @param string $tx_hash Transaction hash
     * @return array Transaction status data
     */
    public function get_transaction_status($tx_hash) {
        // In a real implementation, this would check the blockchain
        // For demonstration purposes, return a mock status
        return array(
            'status' => 'confirmed',
            'block_number' => rand(10000000, 20000000),
            'timestamp' => time(),
            'network' => $this->network
        );
    }
    
    /**
     * Calculate royalty amount for a sale
     * 
     * @param string $token_id NFT token ID
     * @param float $sale_amount Sale amount in currency units
     * @return array Array of royalty distributions
     */
    public function calculate_royalties($token_id, $sale_amount) {
        // Find the NFT post by token ID
        $nft_posts = get_posts(array(
            'post_type' => 'vortex_nft',
            'meta_key' => 'vortex_nft_token_id',
            'meta_value' => $token_id,
            'posts_per_page' => 1
        ));
        
        if (empty($nft_posts)) {
            return array();
        }
        
        $nft_post = $nft_posts[0];
        $royalties = get_post_meta($nft_post->ID, 'vortex_nft_royalties', true);
        
        if (empty($royalties) || !is_array($royalties)) {
            // Default to platform royalty only
            $royalties = array(
                array(
                    'wallet' => $this->platform_wallet,
                    'percentage' => $this->platform_royalty_percentage
                )
            );
        }
        
        $distributions = array();
        
        foreach ($royalties as $royalty) {
            $amount = ($royalty['percentage'] / 100) * $sale_amount;
            $distributions[] = array(
                'wallet' => $royalty['wallet'],
                'percentage' => $royalty['percentage'],
                'amount' => round($amount, 6)
            );
        }
        
        return $distributions;
    }
    
    /**
     * Register NFT post type
     */
    public function register_nft_post_type() {
        register_post_type('vortex_nft', array(
            'labels' => array(
                'name' => __('NFTs', 'vortex-ai-agents'),
                'singular_name' => __('NFT', 'vortex-ai-agents'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'vortex-dashboard',
            'supports' => array('title', 'thumbnail'),
            'capability_type' => 'post',
            'has_archive' => false,
        ));
    }
    
    /**
     * Validate a blockchain wallet address
     * 
     * @param string $address Wallet address to validate
     * @return bool Whether the address is valid
     */
    private function is_valid_address($address) {
        // Basic validation for Ethereum addresses
        if ($this->network === 'ethereum' || $this->network === 'polygon') {
            return preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
        }
        
        // For other networks, implement specific validation
        return true;
    }
}

// Register NFT post type on initialization
add_action('init', array('Vortex\AI\Blockchain\Blockchain', 'register_nft_post_type')); 