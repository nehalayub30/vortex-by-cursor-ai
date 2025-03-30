<?php
/**
 * VORTEX AI AGENTS Blockchain Integration
 *
 * Handles integration between WordPress and blockchain networks
 *
 * @package VortexAiAgents
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Blockchain Integration Class
 */
class Vortex_Blockchain_Integration {

    /**
     * Contract address
     *
     * @var string
     */
    private $contract_address;

    /**
     * Contract ABI
     *
     * @var array
     */
    private $contract_abi;

    /**
     * Network RPC URL
     *
     * @var string
     */
    private $network_rpc_url;

    /**
     * Network name
     *
     * @var string
     */
    private $network;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize properties
        $this->network = get_option( 'vortex_blockchain_network', 'polygon' );
        $this->contract_address = get_option( 'vortex_contract_address', '' );
        $this->load_contract_abi();
        $this->setup_network_rpc();

        // Register hooks
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'save_post_vortex_nft', array( $this, 'save_nft_meta' ) );
    }

    /**
     * Load contract ABI from JSON file
     */
    private function load_contract_abi() {
        $abi_file = VORTEX_AI_AGENTS_PLUGIN_PATH . 'includes/blockchain/contract-abi.json';
        
        if ( file_exists( $abi_file ) ) {
            $abi_json = file_get_contents( $abi_file );
            $this->contract_abi = json_decode( $abi_json, true );
        } else {
            // Fallback to a minimal ABI if file doesn't exist
            $this->contract_abi = [
                [
                    'name' => 'mintWithRoyalties',
                    'type' => 'function',
                    'inputs' => [
                        ['name' => 'to', 'type' => 'address'],
                        ['name' => 'tokenURI', 'type' => 'string'],
                        ['name' => 'royaltyRecipients', 'type' => 'address[]'],
                        ['name' => 'royaltyShares', 'type' => 'uint96[]']
                    ],
                    'outputs' => [
                        ['name' => 'tokenId', 'type' => 'uint256']
                    ]
                ],
                [
                    'name' => 'royaltyInfo',
                    'type' => 'function',
                    'inputs' => [
                        ['name' => 'tokenId', 'type' => 'uint256'],
                        ['name' => 'salePrice', 'type' => 'uint256']
                    ],
                    'outputs' => [
                        ['name' => 'receiver', 'type' => 'address'],
                        ['name' => 'royaltyAmount', 'type' => 'uint256']
                    ]
                ]
            ];
        }
    }

    /**
     * Set up network RPC URL based on selected network
     */
    private function setup_network_rpc() {
        switch ( $this->network ) {
            case 'ethereum':
                $this->network_rpc_url = 'https://mainnet.infura.io/v3/' . get_option( 'vortex_infura_api_key', '' );
                break;
            case 'polygon':
                $this->network_rpc_url = 'https://polygon-rpc.com';
                break;
            case 'rinkeby':
                $this->network_rpc_url = 'https://rinkeby.infura.io/v3/' . get_option( 'vortex_infura_api_key', '' );
                break;
            default:
                // Default to a public Polygon RPC
                $this->network_rpc_url = 'https://polygon-rpc.com';
                break;
        }
    }

    /**
     * Register NFT post type
     */
    public function register_post_types() {
        register_post_type( 'vortex_nft', array(
            'labels' => array(
                'name'               => __( 'NFTs', 'vortex-ai-agents' ),
                'singular_name'      => __( 'NFT', 'vortex-ai-agents' ),
                'add_new'            => __( 'Add New', 'vortex-ai-agents' ),
                'add_new_item'       => __( 'Add New NFT', 'vortex-ai-agents' ),
                'edit_item'          => __( 'Edit NFT', 'vortex-ai-agents' ),
                'new_item'           => __( 'New NFT', 'vortex-ai-agents' ),
                'view_item'          => __( 'View NFT', 'vortex-ai-agents' ),
                'search_items'       => __( 'Search NFTs', 'vortex-ai-agents' ),
                'not_found'          => __( 'No NFTs found', 'vortex-ai-agents' ),
                'not_found_in_trash' => __( 'No NFTs found in trash', 'vortex-ai-agents' )
            ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'vortex-dashboard',
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'nfts' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author' ),
            'menu_icon'          => 'dashicons-art'
        ) );

        // Register meta fields
        register_meta( 'post', 'vortex_token_id', array(
            'type'              => 'string',
            'description'       => 'NFT Token ID',
            'single'            => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest'      => true,
        ) );

        register_meta( 'post', 'vortex_blockchain_status', array(
            'type'              => 'string',
            'description'       => 'NFT Blockchain Status',
            'single'            => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest'      => true,
        ) );

        register_meta( 'post', 'vortex_royalties', array(
            'type'              => 'string',
            'description'       => 'NFT Royalties JSON',
            'single'            => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest'      => true,
        ) );

        register_meta( 'post', 'vortex_transaction_hash', array(
            'type'              => 'string',
            'description'       => 'NFT Transaction Hash',
            'single'            => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest'      => true,
        ) );
    }

    /**
     * Save NFT metadata when post is saved
     *
     * @param int $post_id Post ID.
     */
    public function save_nft_meta( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save metadata from $_POST if available
        if ( isset( $_POST['vortex_token_id'] ) ) {
            update_post_meta( $post_id, 'vortex_token_id', sanitize_text_field( $_POST['vortex_token_id'] ) );
        }
        
        if ( isset( $_POST['vortex_blockchain_status'] ) ) {
            update_post_meta( $post_id, 'vortex_blockchain_status', sanitize_text_field( $_POST['vortex_blockchain_status'] ) );
        }
        
        if ( isset( $_POST['vortex_royalties'] ) ) {
            update_post_meta( $post_id, 'vortex_royalties', sanitize_text_field( $_POST['vortex_royalties'] ) );
        }
        
        if ( isset( $_POST['vortex_transaction_hash'] ) ) {
            update_post_meta( $post_id, 'vortex_transaction_hash', sanitize_text_field( $_POST['vortex_transaction_hash'] ) );
        }
    }

    /**
     * Mint an NFT
     *
     * @param array $nft_data NFT data.
     * @return array|WP_Error Result of minting operation.
     */
    public function mint_nft( $nft_data ) {
        // Validate required data
        if ( empty( $nft_data['title'] ) || empty( $nft_data['image'] ) || empty( $nft_data['creator_wallet'] ) ) {
            return new WP_Error( 'missing_data', __( 'Missing required NFT data', 'vortex-ai-agents' ) );
        }

        // Create new NFT post
        $post_id = wp_insert_post( array(
            'post_title'   => $nft_data['title'],
            'post_content' => isset( $nft_data['description'] ) ? $nft_data['description'] : '',
            'post_status'  => 'publish',
            'post_type'    => 'vortex_nft',
            'post_author'  => get_current_user_id(),
        ) );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Set featured image if URL is provided
        if ( ! empty( $nft_data['image'] ) ) {
            $this->set_featured_image_from_url( $post_id, $nft_data['image'] );
        }

        // Prepare royalty data
        $platform_wallet = get_option( 'vortex_platform_wallet_address', '' );
        
        $royalty_recipients = array( $platform_wallet );
        $royalty_percentages = array( 3 ); // Fixed 3% platform royalty
        
        // Add creator royalty
        $royalty_recipients[] = $nft_data['creator_wallet'];
        $royalty_percentages[] = isset( $nft_data['royalty'] ) ? floatval( $nft_data['royalty'] ) : 5;
        
        // Add collaborators if provided
        if ( ! empty( $nft_data['collaborators'] ) && is_array( $nft_data['collaborators'] ) ) {
            foreach ( $nft_data['collaborators'] as $collaborator ) {
                if ( ! empty( $collaborator['wallet'] ) && isset( $collaborator['percentage'] ) ) {
                    $royalty_recipients[] = $collaborator['wallet'];
                    $royalty_percentages[] = floatval( $collaborator['percentage'] );
                }
            }
        }
        
        // Store royalty information as post meta
        $royalties = array();
        for ( $i = 0; $i < count( $royalty_recipients ); $i++ ) {
            $royalties[] = array(
                'wallet'     => $royalty_recipients[$i],
                'percentage' => $royalty_percentages[$i],
            );
        }
        
        update_post_meta( $post_id, 'vortex_royalties', wp_json_encode( $royalties ) );
        update_post_meta( $post_id, 'vortex_blockchain_status', 'pending' );
        
        // Generate metadata JSON for the NFT
        $metadata = $this->generate_nft_metadata( $post_id, $nft_data );
        
        // Store metadata in post meta
        update_post_meta( $post_id, 'vortex_nft_metadata', wp_json_encode( $metadata ) );
        
        // If user provided a transaction hash (for manual minting), store it
        if ( ! empty( $nft_data['transaction_hash'] ) ) {
            update_post_meta( $post_id, 'vortex_transaction_hash', $nft_data['transaction_hash'] );
            update_post_meta( $post_id, 'vortex_blockchain_status', 'minted' );
            
            if ( ! empty( $nft_data['token_id'] ) ) {
                update_post_meta( $post_id, 'vortex_token_id', $nft_data['token_id'] );
            }
            
            return array(
                'success'   => true,
                'post_id'   => $post_id,
                'token_id'  => $nft_data['token_id'],
                'status'    => 'minted',
                'tx_hash'   => $nft_data['transaction_hash'],
            );
        }
        
        // For now, just return the post ID without actual blockchain minting
        // In a real implementation, this would call the blockchain to mint
        return array(
            'success'  => true,
            'post_id'  => $post_id,
            'status'   => 'pending',
            'metadata' => $metadata,
            'message'  => __( 'NFT created and ready for minting. Connect your wallet in the NFT details page to complete minting.', 'vortex-ai-agents' ),
        );
    }

    /**
     * Set featured image from URL
     *
     * @param int    $post_id Post ID.
     * @param string $image_url Image URL.
     * @return int|false Attachment ID or false on failure.
     */
    private function set_featured_image_from_url( $post_id, $image_url ) {
        // Check if the URL is already in the media library
        $attachment_id = attachment_url_to_postid( $image_url );
        
        if ( ! $attachment_id ) {
            // URL is not in media library, try to upload it
            $upload_dir = wp_upload_dir();
            $image_data = file_get_contents( $image_url );
            
            if ( $image_data === false ) {
                return false;
            }
            
            $filename = basename( $image_url );
            
            // Check if file exists and rename if necessary
            if ( file_exists( $upload_dir['path'] . '/' . $filename ) ) {
                $filename = wp_unique_filename( $upload_dir['path'], $filename );
            }
            
            $file = $upload_dir['path'] . '/' . $filename;
            
            file_put_contents( $file, $image_data );
            
            $wp_filetype = wp_check_filetype( $filename, null );
            
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title'     => sanitize_file_name( $filename ),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );
            
            $attachment_id = wp_insert_attachment( $attachment, $file, $post_id );
            
            if ( ! is_wp_error( $attachment_id ) ) {
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                $attachment_data = wp_generate_attachment_metadata( $attachment_id, $file );
                wp_update_attachment_metadata( $attachment_id, $attachment_data );
            }
        }
        
        if ( $attachment_id ) {
            set_post_thumbnail( $post_id, $attachment_id );
        }
        
        return $attachment_id;
    }

    /**
     * Generate NFT metadata
     *
     * @param int   $post_id Post ID.
     * @param array $nft_data NFT data.
     * @return array NFT metadata.
     */
    private function generate_nft_metadata( $post_id, $nft_data ) {
        $permalink = get_permalink( $post_id );
        $image_url = isset( $nft_data['image'] ) ? $nft_data['image'] : '';
        
        if ( has_post_thumbnail( $post_id ) ) {
            $image_id  = get_post_thumbnail_id( $post_id );
            $image_url = wp_get_attachment_image_url( $image_id, 'full' );
        }
        
        $metadata = array(
            'name'        => isset( $nft_data['title'] ) ? $nft_data['title'] : get_the_title( $post_id ),
            'description' => isset( $nft_data['description'] ) ? $nft_data['description'] : get_the_excerpt( $post_id ),
            'image'       => $image_url,
            'external_url' => $permalink,
            'attributes'  => array(),
        );
        
        // Add platform data
        $metadata['attributes'][] = array(
            'trait_type' => 'Platform',
            'value'      => 'VORTEX AI AGENTS'
        );
        
        // Add creator info
        if ( ! empty( $nft_data['creator_wallet'] ) ) {
            $metadata['attributes'][] = array(
                'trait_type' => 'Creator',
                'value'      => $nft_data['creator_wallet']
            );
        }
        
        // Add network info
        $metadata['attributes'][] = array(
            'trait_type' => 'Network',
            'value'      => $this->network
        );
        
        // Add timestamp
        $metadata['attributes'][] = array(
            'trait_type' => 'Created',
            'value'      => current_time( 'c' )
        );
        
        // Add post ID for reference
        $metadata['attributes'][] = array(
            'trait_type' => 'Post ID',
            'value'      => $post_id
        );
        
        return $metadata;
    }

    /**
     * Get NFT data
     *
     * @param int $post_id Post ID.
     * @return array NFT data.
     */
    public function get_nft_data( $post_id ) {
        $post = get_post( $post_id );
        
        if ( ! $post || 'vortex_nft' !== $post->post_type ) {
            return array();
        }
        
        $token_id = get_post_meta( $post_id, 'vortex_token_id', true );
        $status = get_post_meta( $post_id, 'vortex_blockchain_status', true );
        $royalties_json = get_post_meta( $post_id, 'vortex_royalties', true );
        $tx_hash = get_post_meta( $post_id, 'vortex_transaction_hash', true );
        
        $royalties = array();
        if ( ! empty( $royalties_json ) ) {
            $royalties = json_decode( $royalties_json, true );
        }
        
        $image_url = '';
        if ( has_post_thumbnail( $post_id ) ) {
            $image_id  = get_post_thumbnail_id( $post_id );
            $image_url = wp_get_attachment_image_url( $image_id, 'full' );
        }
        
        $creator_wallet = '';
        if ( ! empty( $royalties ) ) {
            // Platform wallet is usually the first entry
            // Creator is usually the second entry
            if ( isset( $royalties[1]['wallet'] ) ) {
                $creator_wallet = $royalties[1]['wallet'];
            }
        }
        
        return array(
            'id'             => $post_id,
            'title'          => $post->post_title,
            'description'    => $post->post_content,
            'image'          => $image_url,
            'token_id'       => $token_id,
            'status'         => $status,
            'royalties'      => $royalties,
            'tx_hash'        => $tx_hash,
            'creator_wallet' => $creator_wallet,
            'network'        => $this->network,
            'permalink'      => get_permalink( $post_id ),
            'date_created'   => $post->post_date,
        );
    }

    /**
     * Get user NFTs
     *
     * @param int   $user_id User ID.
     * @param array $args Query arguments.
     * @return array User NFTs.
     */
    public function get_user_nfts( $user_id = 0, $args = array() ) {
        if ( empty( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        
        $default_args = array(
            'post_type'      => 'vortex_nft',
            'post_status'    => 'publish',
            'author'         => $user_id,
            'posts_per_page' => 10,
            'paged'          => 1,
        );
        
        $args = wp_parse_args( $args, $default_args );
        
        $query = new WP_Query( $args );
        
        $nfts = array();
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $nfts[] = $this->get_nft_data( get_the_ID() );
            }
            wp_reset_postdata();
        }
        
        return array(
            'nfts'       => $nfts,
            'total'      => $query->found_posts,
            'page'       => $args['paged'],
            'total_pages' => $query->max_num_pages,
        );
    }
}

// Initialize the blockchain integration
$vortex_blockchain_integration = new Vortex_Blockchain_Integration(); 