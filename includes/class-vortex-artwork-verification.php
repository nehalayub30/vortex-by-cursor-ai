<?php
/**
 * Artwork Smart Contract Verification
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class Vortex_Artwork_Verification {
    
    /**
     * Blockchain connection instance
     */
    private $blockchain;
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once plugin_dir_path(__FILE__) . 'class-vortex-blockchain-connection.php';
        $this->blockchain = new Vortex_Blockchain_Connection();
        
        // Register hooks for artwork verification
        add_action('wp_ajax_vortex_verify_artwork', array($this, 'handle_verification_request'));
        add_action('wp_ajax_vortex_check_artwork_verification', array($this, 'check_verification_status'));
        
        // Add meta box to artwork post type
        add_action('add_meta_boxes', array($this, 'add_verification_meta_box'));
        
        // Save verification metadata
        add_action('save_post_vortex_artwork', array($this, 'save_verification_meta'), 10, 2);
        
        // Register hooks for blockchain transactions
        add_action('vortex_artwork_swap_completed', array($this, 'process_swap_transaction'), 10, 3);
        
        // Register TOLA token hooks
        add_action('vortex_artwork_verified', array($this, 'award_verification_tokens'), 10, 2);
        add_action('vortex_swap_completed', array($this, 'award_swap_tokens'), 10, 3);
    }
    
    /**
     * Verify artwork has valid smart contract
     *
     * @param int $artwork_id Artwork post ID
     * @return bool Whether artwork has valid contract
     */
    public function has_valid_contract($artwork_id) {
        // Get contract address from artwork meta
        $contract_address = get_post_meta($artwork_id, 'vortex_artwork_contract', true);
        
        if (empty($contract_address)) {
            return false;
        }
        
        // Verify contract on blockchain
        $is_valid = $this->blockchain->verify_contract(
            $contract_address, 
            array('artwork_id' => $artwork_id)
        );
        
        return $is_valid;
    }
    
    /**
     * Verify artwork is on Tola network
     *
     * @param int $artwork_id Artwork post ID
     * @return bool Whether artwork is on Tola network
     */
    public function is_on_tola_network($artwork_id) {
        $network = get_post_meta($artwork_id, 'vortex_artwork_network', true);
        return $network === 'tola';
    }
    
    /**
     * Get all verified artworks by artist
     *
     * @param int $artist_id Artist user ID
     * @return array Array of verified artwork post IDs
     */
    public function get_verified_artworks($artist_id) {
        // Query artworks by artist
        $args = array(
            'post_type' => 'vortex_artwork',
            'author' => $artist_id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        );
        
        $artworks = get_posts($args);
        $verified_artworks = array();
        
        // Filter to only verified artworks
        foreach ($artworks as $artwork_id) {
            if ($this->has_valid_contract($artwork_id) && $this->is_on_tola_network($artwork_id)) {
                $verified_artworks[] = $artwork_id;
            }
        }
        
        return $verified_artworks;
    }
    
    /**
     * Check if artwork is eligible for swapping
     *
     * @param int $artwork_id Artwork post ID
     * @return bool|WP_Error True if eligible or error with reason
     */
    public function is_eligible_for_swap($artwork_id) {
        // Check if post exists and is an artwork
        $artwork = get_post($artwork_id);
        if (!$artwork || $artwork->post_type !== 'vortex_artwork') {
            return new WP_Error('invalid_artwork', __('Invalid artwork', 'vortex-ai-marketplace'));
        }
        
        // Check if artwork has valid contract
        if (!$this->has_valid_contract($artwork_id)) {
            return new WP_Error('no_contract', __('Artwork has no valid smart contract', 'vortex-ai-marketplace'));
        }
        
        // Check if artwork is on Tola network
        if (!$this->is_on_tola_network($artwork_id)) {
            return new WP_Error('wrong_network', __('Artwork is not on the Tola network', 'vortex-ai-marketplace'));
        }
        
        // Check if artwork is already involved in an active swap
        $is_in_swap = get_post_meta($artwork_id, 'vortex_in_swap', true);
        if ($is_in_swap) {
            return new WP_Error('already_in_swap', __('Artwork is already involved in a swap', 'vortex-ai-marketplace'));
        }
        
        return true;
    }
    
    /**
     * Handle verification request
     */
    public function handle_verification_request() {
        check_ajax_referer('vortex_artwork_nonce', 'nonce');
        
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
        
        // Validate request
        if (!$artwork_id || !$wallet_address) {
            wp_send_json_error(array(
                'message' => __('Invalid request. Artwork ID and wallet address are required.', 'vortex-ai-marketplace')
            ));
        }
        
        // Check if user has permission to verify this artwork
        $artwork = get_post($artwork_id);
        if (!$artwork || $artwork->post_author != get_current_user_id()) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to verify this artwork.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify wallet address format
        if (!$this->is_valid_wallet_address($wallet_address)) {
            wp_send_json_error(array(
                'message' => __('Invalid wallet address format.', 'vortex-ai-marketplace')
            ));
        }
        
        // Create smart contract for artwork
        $contract_result = $this->create_artwork_contract($artwork_id, $wallet_address);
        
        if (is_wp_error($contract_result)) {
            wp_send_json_error(array(
                'message' => $contract_result->get_error_message()
            ));
        }
        
        // Update artwork meta with contract data
        update_post_meta($artwork_id, 'vortex_artwork_contract', $contract_result['contract_address']);
        update_post_meta($artwork_id, 'vortex_artwork_network', 'tola');
        update_post_meta($artwork_id, 'vortex_artwork_token_id', $contract_result['token_id']);
        update_post_meta($artwork_id, 'vortex_artwork_owner', $wallet_address);
        update_post_meta($artwork_id, 'vortex_artwork_verified', 'yes');
        update_post_meta($artwork_id, 'vortex_artwork_verified_date', current_time('mysql'));
        update_post_meta($artwork_id, 'vortex_artwork_transaction', $contract_result['transaction_hash']);
        
        // Award TOLA tokens for verification
        do_action('vortex_artwork_verified', $artwork_id, get_current_user_id());
        
        wp_send_json_success(array(
            'message' => __('Artwork successfully verified and added to the Tola blockchain network.', 'vortex-ai-marketplace'),
            'contract' => $contract_result
        ));
    }
    
    /**
     * Create artwork smart contract
     *
     * @param int $artwork_id Artwork post ID
     * @param string $wallet_address Owner wallet address
     * @return array|WP_Error Contract data or error
     */
    private function create_artwork_contract($artwork_id, $wallet_address) {
        // Get artwork data
        $artwork = get_post($artwork_id);
        $title = $artwork->post_title;
        $description = strip_tags($artwork->post_content);
        $artist_id = $artwork->post_author;
        
        // Get artist data
        $artist_user = get_user_by('id', $artist_id);
        $artist_name = $artist_user->display_name;
        
        // Get artwork featured image
        $thumbnail_id = get_post_thumbnail_id($artwork_id);
        $image_url = wp_get_attachment_url($thumbnail_id);
        
        // Prepare metadata for token
        $metadata = array(
            'name' => $title,
            'description' => $description,
            'image' => $image_url,
            'artist' => $artist_name,
            'created' => get_the_date('c', $artwork_id),
            'attributes' => array(
                array(
                    'trait_type' => 'Artist',
                    'value' => $artist_name
                ),
                array(
                    'trait_type' => 'Creation Date',
                    'value' => get_the_date('Y-m-d', $artwork_id)
                )
            )
        );
        
        // Add custom artwork attributes if exists
        $custom_attributes = get_post_meta($artwork_id, 'vortex_artwork_attributes', true);
        if (!empty($custom_attributes) && is_array($custom_attributes)) {
            foreach ($custom_attributes as $attr_key => $attr_value) {
                $metadata['attributes'][] = array(
                    'trait_type' => $attr_key,
                    'value' => $attr_value
                );
            }
        }
        
        // Create the contract
        return $this->blockchain->create_nft_contract($metadata, $wallet_address);
    }
    
    /**
     * Check if wallet address is valid
     *
     * @param string $address Wallet address
     * @return bool Whether address is valid
     */
    private function is_valid_wallet_address($address) {
        // Basic validation for Ethereum-like addresses
        return (bool) preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
    }
    
    /**
     * Check verification status
     */
    public function check_verification_status() {
        check_ajax_referer('vortex_artwork_nonce', 'nonce');
        
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        
        if (!$artwork_id) {
            wp_send_json_error(array(
                'message' => __('Invalid artwork ID.', 'vortex-ai-marketplace')
            ));
        }
        
        // Get verification data
        $is_verified = get_post_meta($artwork_id, 'vortex_artwork_verified', true) === 'yes';
        $contract_address = get_post_meta($artwork_id, 'vortex_artwork_contract', true);
        $network = get_post_meta($artwork_id, 'vortex_artwork_network', true);
        $token_id = get_post_meta($artwork_id, 'vortex_artwork_token_id', true);
        $owner = get_post_meta($artwork_id, 'vortex_artwork_owner', true);
        $verified_date = get_post_meta($artwork_id, 'vortex_artwork_verified_date', true);
        $transaction = get_post_meta($artwork_id, 'vortex_artwork_transaction', true);
        
        // If verified, check current status on blockchain
        $blockchain_status = null;
        if ($is_verified && !empty($contract_address)) {
            $blockchain_status = $this->blockchain->get_token_status($contract_address, $token_id);
        }
        
        wp_send_json_success(array(
            'verified' => $is_verified,
            'contract_address' => $contract_address,
            'network' => $network,
            'token_id' => $token_id,
            'owner' => $owner,
            'verified_date' => $verified_date,
            'transaction' => $transaction,
            'blockchain_status' => $blockchain_status
        ));
    }
    
    /**
     * Add verification meta box to artwork
     */
    public function add_verification_meta_box() {
        add_meta_box(
            'vortex_artwork_verification',
            __('Artwork Blockchain Verification', 'vortex-ai-marketplace'),
            array($this, 'render_verification_meta_box'),
            'vortex_artwork',
            'side',
            'high'
        );
    }
    
    /**
     * Render verification meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_verification_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('vortex_artwork_verification', 'vortex_artwork_verification_nonce');
        
        // Get verification status
        $is_verified = get_post_meta($post->ID, 'vortex_artwork_verified', true) === 'yes';
        $contract_address = get_post_meta($post->ID, 'vortex_artwork_contract', true);
        $owner = get_post_meta($post->ID, 'vortex_artwork_owner', true);
        
        // Include meta box template
        include plugin_dir_path(dirname(__FILE__)) . 'admin/partials/artwork-verification-meta-box.php';
    }
    
    /**
     * Save verification meta
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     */
    public function save_verification_meta($post_id, $post) {
        // Check if our nonce is set and verify it
        if (!isset($_POST['vortex_artwork_verification_nonce']) || 
            !wp_verify_nonce($_POST['vortex_artwork_verification_nonce'], 'vortex_artwork_verification')) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Don't save on autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Save wallet address if provided
        if (isset($_POST['vortex_artwork_owner'])) {
            $wallet_address = sanitize_text_field($_POST['vortex_artwork_owner']);
            if ($this->is_valid_wallet_address($wallet_address)) {
                update_post_meta($post_id, 'vortex_artwork_owner', $wallet_address);
            }
        }
    }
    
    /**
     * Process swap transaction on the blockchain
     *
     * @param int $swap_id Swap ID
     * @param int $first_artwork_id First artwork ID
     * @param int $second_artwork_id Second artwork ID
     * @return array|WP_Error Transaction data or error
     */
    public function process_swap_transaction($swap_id, $first_artwork_id, $second_artwork_id) {
        // Get artwork owners
        $first_owner = get_post_meta($first_artwork_id, 'vortex_artwork_owner', true);
        $second_owner = get_post_meta($second_artwork_id, 'vortex_artwork_owner', true);
        
        // Get contract addresses
        $first_contract = get_post_meta($first_artwork_id, 'vortex_artwork_contract', true);
        $second_contract = get_post_meta($second_artwork_id, 'vortex_artwork_contract', true);
        
        // Get token IDs
        $first_token = get_post_meta($first_artwork_id, 'vortex_artwork_token_id', true);
        $second_token = get_post_meta($second_artwork_id, 'vortex_artwork_token_id', true);
        
        // Execute swap transaction
        $transaction = $this->blockchain->execute_swap_transaction(
            $first_contract,
            $first_token,
            $first_owner,
            $second_contract,
            $second_token,
            $second_owner
        );
        
        if (is_wp_error($transaction)) {
            return $transaction;
        }
        
        // Update artwork ownership
        update_post_meta($first_artwork_id, 'vortex_artwork_owner', $second_owner);
        update_post_meta($second_artwork_id, 'vortex_artwork_owner', $first_owner);
        
        // Update swap transaction data
        update_post_meta($swap_id, 'vortex_swap_transaction', $transaction['transaction_hash']);
        update_post_meta($swap_id, 'vortex_swap_completed', current_time('mysql'));
        
        // Mark artworks as no longer in swap
        update_post_meta($first_artwork_id, 'vortex_in_swap', false);
        update_post_meta($second_artwork_id, 'vortex_in_swap', false);
        
        return $transaction;
    }
    
    /**
     * Award TOLA tokens for artwork verification
     *
     * @param int $artwork_id Artwork ID
     * @param int $user_id User ID
     */
    public function award_verification_tokens($artwork_id, $user_id) {
        // Check if Vortex_TOLA class exists
        if (!class_exists('Vortex_TOLA')) {
            return;
        }
        
        // Get TOLA instance
        $tola = Vortex_TOLA::get_instance();
        
        // Award tokens for verification (50 TOLA)
        $tola->award_tokens($user_id, 50, 'artwork_verification', array(
            'artwork_id' => $artwork_id,
            'description' => sprintf(
                __('Verification of artwork: %s', 'vortex-ai-marketplace'),
                get_the_title($artwork_id)
            )
        ));
    }
    
    /**
     * Award TOLA tokens for completing artwork swap
     *
     * @param int $swap_id Swap ID
     * @param int $first_artwork_id First artwork ID
     * @param int $second_artwork_id Second artwork ID
     */
    public function award_swap_tokens($swap_id, $first_artwork_id, $second_artwork_id) {
        // Check if Vortex_TOLA class exists
        if (!class_exists('Vortex_TOLA')) {
            return;
        }
        
        // Get artwork owners (artists)
        $first_artwork = get_post($first_artwork_id);
        $second_artwork = get_post($second_artwork_id);
        
        $first_artist_id = $first_artwork->post_author;
        $second_artist_id = $second_artwork->post_author;
        
        // Get TOLA instance
        $tola = Vortex_TOLA::get_instance();
        
        // Award tokens to both artists (100 TOLA each)
        $tola->award_tokens($first_artist_id, 100, 'artwork_swap', array(
            'swap_id' => $swap_id,
            'description' => sprintf(
                __('Completed artwork swap: %s for %s', 'vortex-ai-marketplace'),
                get_the_title($first_artwork_id),
                get_the_title($second_artwork_id)
            )
        ));
        
        $tola->award_tokens($second_artist_id, 100, 'artwork_swap', array(
            'swap_id' => $swap_id,
            'description' => sprintf(
                __('Completed artwork swap: %s for %s', 'vortex-ai-marketplace'),
                get_the_title($second_artwork_id),
                get_the_title($first_artwork_id)
            )
        ));
    }
} 