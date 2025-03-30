<?php
/**
 * The file that defines the Artwork post type
 *
 * @link       https://github.com/MarianneNems/VORTEX
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/post-types
 */

/**
 * The Artwork post type class.
 *
 * Defines and registers the artwork custom post type for the marketplace.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/post-types
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Artwork {

    /**
     * The post type name.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $post_type    The post type name.
     */
    private $post_type = 'vortex_artwork';

    /**
     * Register the custom post type.
     *
     * @since    1.0.0
     */
    public function register() {
        $labels = array(
            'name'               => _x( 'Artworks', 'post type general name', 'vortex-ai-marketplace' ),
            'singular_name'      => _x( 'Artwork', 'post type singular name', 'vortex-ai-marketplace' ),
            'menu_name'          => _x( 'Artworks', 'admin menu', 'vortex-ai-marketplace' ),
            'name_admin_bar'     => _x( 'Artwork', 'add new on admin bar', 'vortex-ai-marketplace' ),
            'add_new'            => _x( 'Add New', 'artwork', 'vortex-ai-marketplace' ),
            'add_new_item'       => __( 'Add New Artwork', 'vortex-ai-marketplace' ),
            'new_item'           => __( 'New Artwork', 'vortex-ai-marketplace' ),
            'edit_item'          => __( 'Edit Artwork', 'vortex-ai-marketplace' ),
            'view_item'          => __( 'View Artwork', 'vortex-ai-marketplace' ),
            'all_items'          => __( 'All Artworks', 'vortex-ai-marketplace' ),
            'search_items'       => __( 'Search Artworks', 'vortex-ai-marketplace' ),
            'parent_item_colon'  => __( 'Parent Artworks:', 'vortex-ai-marketplace' ),
            'not_found'          => __( 'No artworks found.', 'vortex-ai-marketplace' ),
            'not_found_in_trash' => __( 'No artworks found in Trash.', 'vortex-ai-marketplace' )
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __( 'Digital artworks for the VORTEX marketplace', 'vortex-ai-marketplace' ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'artwork' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-art',
            'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
            'show_in_rest'       => true,
        );

        register_post_type( $this->post_type, $args );
        
        // Register taxonomies
        $this->register_taxonomies();
        
        // Register meta boxes
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        
        // Save post meta
        add_action( 'save_post_' . $this->post_type, array( $this, 'save_meta_box_data' ) );
    }
    
    /**
     * Register custom taxonomies for the artwork post type
     *
     * @since    1.0.0
     */
    private function register_taxonomies() {
        // Art Style Taxonomy
        $style_labels = array(
            'name'              => _x( 'Art Styles', 'taxonomy general name', 'vortex-ai-marketplace' ),
            'singular_name'     => _x( 'Art Style', 'taxonomy singular name', 'vortex-ai-marketplace' ),
            'search_items'      => __( 'Search Art Styles', 'vortex-ai-marketplace' ),
            'all_items'         => __( 'All Art Styles', 'vortex-ai-marketplace' ),
            'parent_item'       => __( 'Parent Art Style', 'vortex-ai-marketplace' ),
            'parent_item_colon' => __( 'Parent Art Style:', 'vortex-ai-marketplace' ),
            'edit_item'         => __( 'Edit Art Style', 'vortex-ai-marketplace' ),
            'update_item'       => __( 'Update Art Style', 'vortex-ai-marketplace' ),
            'add_new_item'      => __( 'Add New Art Style', 'vortex-ai-marketplace' ),
            'new_item_name'     => __( 'New Art Style Name', 'vortex-ai-marketplace' ),
            'menu_name'         => __( 'Art Style', 'vortex-ai-marketplace' ),
        );

        $style_args = array(
            'hierarchical'      => true,
            'labels'            => $style_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'art-style' ),
            'show_in_rest'      => true,
        );

        register_taxonomy( 'art_style', array( $this->post_type ), $style_args );
        
        // AI Engine Taxonomy
        $engine_labels = array(
            'name'              => _x( 'AI Engines', 'taxonomy general name', 'vortex-ai-marketplace' ),
            'singular_name'     => _x( 'AI Engine', 'taxonomy singular name', 'vortex-ai-marketplace' ),
            'search_items'      => __( 'Search AI Engines', 'vortex-ai-marketplace' ),
            'all_items'         => __( 'All AI Engines', 'vortex-ai-marketplace' ),
            'parent_item'       => __( 'Parent AI Engine', 'vortex-ai-marketplace' ),
            'parent_item_colon' => __( 'Parent AI Engine:', 'vortex-ai-marketplace' ),
            'edit_item'         => __( 'Edit AI Engine', 'vortex-ai-marketplace' ),
            'update_item'       => __( 'Update AI Engine', 'vortex-ai-marketplace' ),
            'add_new_item'      => __( 'Add New AI Engine', 'vortex-ai-marketplace' ),
            'new_item_name'     => __( 'New AI Engine Name', 'vortex-ai-marketplace' ),
            'menu_name'         => __( 'AI Engine', 'vortex-ai-marketplace' ),
        );

        $engine_args = array(
            'hierarchical'      => false,
            'labels'            => $engine_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'ai-engine' ),
            'show_in_rest'      => true,
        );

        register_taxonomy( 'ai_engine', array( $this->post_type ), $engine_args );
    }
    
    /**
     * Add meta boxes for the artwork post type
     *
     * @since    1.0.0
     */
    public function add_meta_boxes() {
        add_meta_box(
            'vortex_artwork_details',
            __( 'Artwork Details', 'vortex-ai-marketplace' ),
            array( $this, 'render_details_meta_box' ),
            $this->post_type,
            'normal',
            'high'
        );
        
        add_meta_box(
            'vortex_blockchain_details',
            __( 'Blockchain Details', 'vortex-ai-marketplace' ),
            array( $this, 'render_blockchain_meta_box' ),
            $this->post_type,
            'normal',
            'high'
        );
    }
    
    /**
     * Render the details meta box
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_details_meta_box( $post ) {
        // Add nonce for security
        wp_nonce_field( 'vortex_artwork_details_nonce', 'vortex_artwork_details_nonce' );
        
        // Get saved values
        $price = get_post_meta( $post->ID, '_vortex_artwork_price', true );
        $edition_size = get_post_meta( $post->ID, '_vortex_artwork_edition_size', true );
        $ai_prompt = get_post_meta( $post->ID, '_vortex_artwork_ai_prompt', true );
        $created_with_huraii = get_post_meta( $post->ID, '_vortex_created_with_huraii', true );
        
        // Output fields
        ?>
        <p>
            <label for="vortex-artwork-price"><?php _e( 'Price (TOLA):', 'vortex-ai-marketplace' ); ?></label>
            <input type="text" id="vortex-artwork-price" name="vortex_artwork_price" value="<?php echo esc_attr( $price ); ?>" class="regular-text">
        </p>
        <p>
            <label for="vortex-artwork-edition-size"><?php _e( 'Edition Size:', 'vortex-ai-marketplace' ); ?></label>
            <input type="number" id="vortex-artwork-edition-size" name="vortex_artwork_edition_size" value="<?php echo esc_attr( $edition_size ); ?>" min="1">
            <span class="description"><?php _e( 'Number of editions available for this artwork', 'vortex-ai-marketplace' ); ?></span>
        </p>
        <p>
            <label for="vortex-artwork-ai-prompt"><?php _e( 'AI Prompt:', 'vortex-ai-marketplace' ); ?></label>
            <textarea id="vortex-artwork-ai-prompt" name="vortex_artwork_ai_prompt" rows="4" class="large-text"><?php echo esc_textarea( $ai_prompt ); ?></textarea>
            <span class="description"><?php _e( 'The prompt used to generate this artwork (if AI-generated)', 'vortex-ai-marketplace' ); ?></span>
        </p>
        <p>
            <label for="vortex-created-with-huraii">
                <input type="checkbox" id="vortex-created-with-huraii" name="vortex_created_with_huraii" value="1" <?php checked( $created_with_huraii, '1' ); ?>>
                <?php _e( 'Created with HURAII', 'vortex-ai-marketplace' ); ?>
            </label>
        </p>
        <?php
    }
    
    /**
     * Render the blockchain meta box
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_blockchain_meta_box( $post ) {
        // Add nonce for security
        wp_nonce_field( 'vortex_blockchain_details_nonce', 'vortex_blockchain_details_nonce' );
        
        // Get saved values
        $token_id = get_post_meta( $post->ID, '_vortex_blockchain_token_id', true );
        $contract_address = get_post_meta( $post->ID, '_vortex_blockchain_contract_address', true );
        $blockchain = get_post_meta( $post->ID, '_vortex_blockchain_name', true );
        $artist_wallet = get_post_meta( $post->ID, '_vortex_artist_wallet_address', true );
        
        // Output fields
        ?>
        <p>
            <label for="vortex-blockchain-token-id"><?php _e( 'Token ID:', 'vortex-ai-marketplace' ); ?></label>
            <input type="text" id="vortex-blockchain-token-id" name="vortex_blockchain_token_id" value="<?php echo esc_attr( $token_id ); ?>" class="regular-text">
        </p>
        <p>
            <label for="vortex-blockchain-contract-address"><?php _e( 'Contract Address:', 'vortex-ai-marketplace' ); ?></label>
            <input type="text" id="vortex-blockchain-contract-address" name="vortex_blockchain_contract_address" value="<?php echo esc_attr( $contract_address ); ?>" class="large-text">
        </p>
        <p>
            <label for="vortex-blockchain-name"><?php _e( 'Blockchain:', 'vortex-ai-marketplace' ); ?></label>
            <select id="vortex-blockchain-name" name="vortex_blockchain_name">
                <option value="ethereum" <?php selected( $blockchain, 'ethereum' ); ?>><?php _e( 'Ethereum', 'vortex-ai-marketplace' ); ?></option>
                <option value="polygon" <?php selected( $blockchain, 'polygon' ); ?>><?php _e( 'Polygon', 'vortex-ai-marketplace' ); ?></option>
                <option value="solana" <?php selected( $blockchain, 'solana' ); ?>><?php _e( 'Solana', 'vortex-ai-marketplace' ); ?></option>
                <option value="binance" <?php selected( $blockchain, 'binance' ); ?>><?php _e( 'Binance Smart Chain', 'vortex-ai-marketplace' ); ?></option>
            </select>
        </p>
        <p>
            <label for="vortex-artist-wallet-address"><?php _e( 'Artist Wallet Address:', 'vortex-ai-marketplace' ); ?></label>
            <input type="text" id="vortex-artist-wallet-address" name="vortex_artist_wallet_address" value="<?php echo esc_attr( $artist_wallet ); ?>" class="large-text">
        </p>
        <?php
    }
    
    /**
     * Save the meta box data
     *
     * @since    1.0.0
     * @param    int    $post_id    The post ID.
     */
    public function save_meta_box_data( $post_id ) {
        // Check if our nonces are set and verify them
        if ( ! isset( $_POST['vortex_artwork_details_nonce'] ) || ! wp_verify_nonce( $_POST['vortex_artwork_details_nonce'], 'vortex_artwork_details_nonce' ) ) {
            return;
        }
        
        if ( ! isset( $_POST['vortex_blockchain_details_nonce'] ) || ! wp_verify_nonce( $_POST['vortex_blockchain_details_nonce'], 'vortex_blockchain_details_nonce' ) ) {
            return;
        }
        
        // If this is an autosave, we don't want to do anything
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        // Check the user's permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // Artwork details
        if ( isset( $_POST['vortex_artwork_price'] ) ) {
            update_post_meta( $post_id, '_vortex_artwork_price', sanitize_text_field( $_POST['vortex_artwork_price'] ) );
        }
        
        if ( isset( $_POST['vortex_artwork_edition_size'] ) ) {
            update_post_meta( $post_id, '_vortex_artwork_edition_size', absint( $_POST['vortex_artwork_edition_size'] ) );
        }
        
        if ( isset( $_POST['vortex_artwork_ai_prompt'] ) ) {
            update_post_meta( $post_id, '_vortex_artwork_ai_prompt', sanitize_textarea_field( $_POST['vortex_artwork_ai_prompt'] ) );
        }
        
        // Created with HURAII checkbox
        $created_with_huraii = isset( $_POST['vortex_created_with_huraii'] ) ? '1' : '0';
        update_post_meta( $post_id, '_vortex_created_with_huraii', $created_with_huraii );
        
        // Blockchain details
        if ( isset( $_POST['vortex_blockchain_token_id'] ) ) {
            update_post_meta( $post_id, '_vortex_blockchain_token_id', sanitize_text_field( $_POST['vortex_blockchain_token_id'] ) );
        }
        
        if ( isset( $_POST['vortex_blockchain_contract_address'] ) ) {
            update_post_meta( $post_id, '_vortex_blockchain_contract_address', sanitize_text_field( $_POST['vortex_blockchain_contract_address'] ) );
        }
        
        if ( isset( $_POST['vortex_blockchain_name'] ) ) {
            update_post_meta( $post_id, '_vortex_blockchain_name', sanitize_text_field( $_POST['vortex_blockchain_name'] ) );
        }
        
        if ( isset( $_POST['vortex_artist_wallet_address'] ) ) {
            update_post_meta( $post_id, '_vortex_artist_wallet_address', sanitize_text_field( $_POST['vortex_artist_wallet_address'] ) );
        }
    }
} 