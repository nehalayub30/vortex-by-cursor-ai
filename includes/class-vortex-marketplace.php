<?php
/**
 * The marketplace functionality of the plugin.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The marketplace functionality of the plugin.
 *
 * Defines the plugin name, version, and marketplace-specific functionality
 * including artwork listing, purchasing, and integration with blockchain.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Marketplace {

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
     * Blockchain integration instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Blockchain_Integration    $blockchain    The blockchain integration instance.
     */
    private $blockchain;

    /**
     * TOLA token integration instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Tola    $tola    The TOLA token integration instance.
     */
    private $tola;

    /**
     * The cart contents.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $cart    The cart contents.
     */
    private $cart = array();

    /**
     * The marketplace currency.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $currency    The marketplace currency.
     */
    private $currency;

    /**
     * The marketplace currency symbol.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $currency_symbol    The marketplace currency symbol.
     */
    private $currency_symbol;

    /**
     * The marketplace commission rate.
     *
     * @since    1.0.0
     * @access   private
     * @var      float    $commission_rate    The marketplace commission rate.
     */
    private $commission_rate;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Initialize blockchain integration
        $this->blockchain = new Vortex_Blockchain_Integration( $plugin_name, $version );
        
        // Initialize TOLA token integration
        $this->tola = new Vortex_Tola( $plugin_name, $version );
        
        // Initialize marketplace settings
        $this->initialize_marketplace_settings();

        // Initialize cart
        $this->initialize_cart();
        
        // Initialize marketplace hooks
        $this->init_hooks();
    }

    /**
     * Initialize marketplace settings from options.
     *
     * @since    1.0.0
     */
    private function initialize_marketplace_settings() {
        $this->currency = get_option( 'vortex_marketplace_currency', 'USD' );
        $this->currency_symbol = get_option( 'vortex_marketplace_currency_symbol', '$' );
        $this->commission_rate = floatval( get_option( 'vortex_marketplace_commission', 10 ) );
    }

    /**
     * Initialize the cart from session or create new cart.
     *
     * @since    1.0.0
     */
    private function initialize_cart() {
        // Start session if not already started
        if ( !session_id() ) {
            session_start();
        }

        // Get cart from session or create new cart
        if ( isset( $_SESSION['vortex_cart'] ) ) {
            $this->cart = $_SESSION['vortex_cart'];
        } else {
            $this->cart = array(
                'items' => array(),
                'total' => 0,
                'item_count' => 0,
                'currency' => $this->currency,
                'currency_symbol' => $this->currency_symbol,
            );
            $_SESSION['vortex_cart'] = $this->cart;
        }
    }

    /**
     * Register all marketplace related hooks.
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_hooks() {
        // Register custom post types and taxonomies
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        
        // Register shortcodes
        add_shortcode( 'vortex_marketplace', array( $this, 'marketplace_shortcode' ) );
        add_shortcode( 'vortex_artist_profile', array( $this, 'artist_profile_shortcode' ) );
        
        // AJAX handlers for marketplace interactions
        add_action( 'wp_ajax_vortex_purchase_artwork', array( $this, 'ajax_purchase_artwork' ) );
        add_action( 'wp_ajax_vortex_list_artwork', array( $this, 'ajax_list_artwork' ) );
        add_action( 'wp_ajax_vortex_get_marketplace_data', array( $this, 'ajax_get_marketplace_data' ) );
        add_action( 'wp_ajax_nopriv_vortex_get_marketplace_data', array( $this, 'ajax_get_marketplace_data' ) );
        
        // Filters for artwork display and pricing
        add_filter( 'vortex_artwork_price_display', array( $this, 'format_artwork_price' ), 10, 2 );
        add_filter( 'vortex_artwork_purchase_button', array( $this, 'render_purchase_button' ), 10, 2 );
        
        // Filter for marketplace settings
        add_filter( 'vortex_marketplace_settings', array( $this, 'get_marketplace_settings' ) );
        
        // Add meta boxes for artwork pricing and editions
        add_action( 'add_meta_boxes', array( $this, 'add_artwork_meta_boxes' ) );
        add_action( 'save_post_vortex_artwork', array( $this, 'save_artwork_meta' ) );
    }

    /**
     * Register custom post types for artwork and artists.
     *
     * @since    1.0.0
     */
    public function register_post_types() {
        // Register Artwork post type
        register_post_type( 'vortex_artwork',
            array(
                'labels' => array(
                    'name' => __( 'Artworks', 'vortex-ai-marketplace' ),
                    'singular_name' => __( 'Artwork', 'vortex-ai-marketplace' ),
                    'add_new' => __( 'Add New Artwork', 'vortex-ai-marketplace' ),
                    'add_new_item' => __( 'Add New Artwork', 'vortex-ai-marketplace' ),
                    'edit_item' => __( 'Edit Artwork', 'vortex-ai-marketplace' ),
                    'new_item' => __( 'New Artwork', 'vortex-ai-marketplace' ),
                    'view_item' => __( 'View Artwork', 'vortex-ai-marketplace' ),
                    'search_items' => __( 'Search Artworks', 'vortex-ai-marketplace' ),
                    'not_found' => __( 'No artworks found', 'vortex-ai-marketplace' ),
                    'not_found_in_trash' => __( 'No artworks found in Trash', 'vortex-ai-marketplace' ),
                ),
                'public' => true,
                'has_archive' => true,
                'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments' ),
                'menu_icon' => 'dashicons-art',
                'rewrite' => array( 'slug' => 'artwork' ),
                'show_in_rest' => true,
            )
        );
        
        // Register Artist post type
        register_post_type( 'vortex_artist',
            array(
                'labels' => array(
                    'name' => __( 'Artists', 'vortex-ai-marketplace' ),
                    'singular_name' => __( 'Artist', 'vortex-ai-marketplace' ),
                    'add_new' => __( 'Add New Artist', 'vortex-ai-marketplace' ),
                    'add_new_item' => __( 'Add New Artist', 'vortex-ai-marketplace' ),
                    'edit_item' => __( 'Edit Artist', 'vortex-ai-marketplace' ),
                    'new_item' => __( 'New Artist', 'vortex-ai-marketplace' ),
                    'view_item' => __( 'View Artist', 'vortex-ai-marketplace' ),
                    'search_items' => __( 'Search Artists', 'vortex-ai-marketplace' ),
                    'not_found' => __( 'No artists found', 'vortex-ai-marketplace' ),
                    'not_found_in_trash' => __( 'No artists found in Trash', 'vortex-ai-marketplace' ),
                ),
                'public' => true,
                'has_archive' => true,
                'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
                'menu_icon' => 'dashicons-businessman',
                'rewrite' => array( 'slug' => 'artist' ),
                'show_in_rest' => true,
            )
        );
    }

    /**
     * Register taxonomies for artwork classification.
     *
     * @since    1.0.0
     */
    public function register_taxonomies() {
        // Register Art Style taxonomy
        register_taxonomy(
            'art_style',
            'vortex_artwork',
            array(
                'hierarchical' => true,
                'labels' => array(
                    'name' => __( 'Art Styles', 'vortex-ai-marketplace' ),
                    'singular_name' => __( 'Art Style', 'vortex-ai-marketplace' ),
                    'search_items' => __( 'Search Art Styles', 'vortex-ai-marketplace' ),
                    'all_items' => __( 'All Art Styles', 'vortex-ai-marketplace' ),
                    'parent_item' => __( 'Parent Art Style', 'vortex-ai-marketplace' ),
                    'parent_item_colon' => __( 'Parent Art Style:', 'vortex-ai-marketplace' ),
                    'edit_item' => __( 'Edit Art Style', 'vortex-ai-marketplace' ),
                    'update_item' => __( 'Update Art Style', 'vortex-ai-marketplace' ),
                    'add_new_item' => __( 'Add New Art Style', 'vortex-ai-marketplace' ),
                    'new_item_name' => __( 'New Art Style Name', 'vortex-ai-marketplace' ),
                ),
                'show_ui' => true,
                'show_in_rest' => true,
                'rewrite' => array( 'slug' => 'art-style' ),
            )
        );
        
        // Register Art Category taxonomy
        register_taxonomy(
            'art_category',
            'vortex_artwork',
            array(
                'hierarchical' => true,
                'labels' => array(
                    'name' => __( 'Art Categories', 'vortex-ai-marketplace' ),
                    'singular_name' => __( 'Art Category', 'vortex-ai-marketplace' ),
                    'search_items' => __( 'Search Art Categories', 'vortex-ai-marketplace' ),
                    'all_items' => __( 'All Art Categories', 'vortex-ai-marketplace' ),
                    'parent_item' => __( 'Parent Art Category', 'vortex-ai-marketplace' ),
                    'parent_item_colon' => __( 'Parent Art Category:', 'vortex-ai-marketplace' ),
                    'edit_item' => __( 'Edit Art Category', 'vortex-ai-marketplace' ),
                    'update_item' => __( 'Update Art Category', 'vortex-ai-marketplace' ),
                    'add_new_item' => __( 'Add New Art Category', 'vortex-ai-marketplace' ),
                    'new_item_name' => __( 'New Art Category Name', 'vortex-ai-marketplace' ),
                ),
                'show_ui' => true,
                'show_in_rest' => true,
                'rewrite' => array( 'slug' => 'art-category' ),
            )
        );
        
        // Register AI Engine taxonomy
        register_taxonomy(
            'ai_engine',
            'vortex_artwork',
            array(
                'hierarchical' => false,
                'labels' => array(
                    'name' => __( 'AI Engines', 'vortex-ai-marketplace' ),
                    'singular_name' => __( 'AI Engine', 'vortex-ai-marketplace' ),
                    'search_items' => __( 'Search AI Engines', 'vortex-ai-marketplace' ),
                    'all_items' => __( 'All AI Engines', 'vortex-ai-marketplace' ),
                    'edit_item' => __( 'Edit AI Engine', 'vortex-ai-marketplace' ),
                    'update_item' => __( 'Update AI Engine', 'vortex-ai-marketplace' ),
                    'add_new_item' => __( 'Add New AI Engine', 'vortex-ai-marketplace' ),
                    'new_item_name' => __( 'New AI Engine Name', 'vortex-ai-marketplace' ),
                ),
                'show_ui' => true,
                'show_in_rest' => true,
                'rewrite' => array( 'slug' => 'ai-engine' ),
            )
        );
    }

    /**
     * Add meta boxes for artwork details.
     *
     * @since    1.0.0
     */
    public function add_artwork_meta_boxes() {
        add_meta_box(
            'vortex_artwork_pricing',
            __( 'Artwork Pricing', 'vortex-ai-marketplace' ),
            array( $this, 'render_pricing_meta_box' ),
            'vortex_artwork',
            'side',
            'high'
        );
        
        add_meta_box(
            'vortex_artwork_blockchain',
            __( 'Blockchain Details', 'vortex-ai-marketplace' ),
            array( $this, 'render_blockchain_meta_box' ),
            'vortex_artwork',
            'normal',
            'high'
        );
        
        add_meta_box(
            'vortex_artwork_editions',
            __( 'Edition Information', 'vortex-ai-marketplace' ),
            array( $this, 'render_editions_meta_box' ),
            'vortex_artwork',
            'normal',
            'high'
        );
    }

    /**
     * Render pricing meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_pricing_meta_box( $post ) {
        // Add nonce for security
        wp_nonce_field( 'vortex_artwork_pricing_nonce', 'vortex_artwork_pricing_nonce' );
        
        // Get existing values
        $price = get_post_meta( $post->ID, '_vortex_artwork_price', true );
        $tola_price = get_post_meta( $post->ID, '_vortex_tola_price', true );
        
        // Output fields
        ?>
        <p>
            <label for="vortex_artwork_price"><?php _e( 'Price (USD):', 'vortex-ai-marketplace' ); ?></label>
            <input type="number" id="vortex_artwork_price" name="vortex_artwork_price" step="0.01" value="<?php echo esc_attr( $price ); ?>" style="width: 100%;" />
        </p>
        <p>
            <label for="vortex_tola_price"><?php _e( 'Price (TOLA):', 'vortex-ai-marketplace' ); ?></label>
            <input type="number" id="vortex_tola_price" name="vortex_tola_price" step="0.000001" value="<?php echo esc_attr( $tola_price ); ?>" style="width: 100%;" />
        </p>
        <?php
    }

    /**
     * Render blockchain meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_blockchain_meta_box( $post ) {
        // Add nonce for security
        wp_nonce_field( 'vortex_artwork_blockchain_nonce', 'vortex_artwork_blockchain_nonce' );
        
        // Get existing values
        $token_id = get_post_meta( $post->ID, '_vortex_blockchain_token_id', true );
        $contract_address = get_post_meta( $post->ID, '_vortex_blockchain_contract_address', true );
        $blockchain_name = get_post_meta( $post->ID, '_vortex_blockchain_name', true );
        
        // Default to Solana if not set
        if ( empty( $blockchain_name ) ) {
            $blockchain_name = 'solana';
        }
        
        // Output fields
        ?>
        <p>
            <label for="vortex_blockchain_name"><?php _e( 'Blockchain:', 'vortex-ai-marketplace' ); ?></label>
            <select id="vortex_blockchain_name" name="vortex_blockchain_name" style="width: 100%;">
                <option value="solana" <?php selected( $blockchain_name, 'solana' ); ?>><?php _e( 'Solana', 'vortex-ai-marketplace' ); ?></option>
                <option value="ethereum" <?php selected( $blockchain_name, 'ethereum' ); ?>><?php _e( 'Ethereum', 'vortex-ai-marketplace' ); ?></option>
            </select>
        </p>
        <p>
            <label for="vortex_blockchain_token_id"><?php _e( 'Token ID:', 'vortex-ai-marketplace' ); ?></label>
            <input type="text" id="vortex_blockchain_token_id" name="vortex_blockchain_token_id" value="<?php echo esc_attr( $token_id ); ?>" style="width: 100%;" />
        </p>
        <p>
            <label for="vortex_blockchain_contract_address"><?php _e( 'Contract Address:', 'vortex-ai-marketplace' ); ?></label>
            <input type="text" id="vortex_blockchain_contract_address" name="vortex_blockchain_contract_address" value="<?php echo esc_attr( $contract_address ); ?>" style="width: 100%;" />
        </p>
        <p class="description">
            <?php _e( 'If this artwork is minted on-chain, enter the token details here.', 'vortex-ai-marketplace' ); ?>
        </p>
        <div class="vortex-blockchain-actions">
            <button type="button" class="button" id="vortex-mint-artwork"><?php _e( 'Mint Artwork on Blockchain', 'vortex-ai-marketplace' ); ?></button>
            <span class="spinner" style="float: none;"></span>
        </div>
        <?php
    }

    /**
     * Render editions meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_editions_meta_box( $post ) {
        // Add nonce for security
        wp_nonce_field( 'vortex_artwork_editions_nonce', 'vortex_artwork_editions_nonce' );
        
        // Get existing value
        $edition_size = get_post_meta( $post->ID, '_vortex_artwork_edition_size', true );
        
        // Output field
        ?>
        <p>
            <label for="vortex_artwork_edition_size"><?php _e( 'Edition Size:', 'vortex-ai-marketplace' ); ?></label>
            <input type="number" id="vortex_artwork_edition_size" name="vortex_artwork_edition_size" min="1" value="<?php echo esc_attr( $edition_size ); ?>" />
        </p>
        <p class="description">
            <?php _e( 'Enter 1 for a unique artwork, or a higher number for limited editions.', 'vortex-ai-marketplace' ); ?>
        </p>
        <?php
    }

    /**
     * Save artwork meta when the post is saved.
     *
     * @since    1.0.0
     * @param    int    $post_id    The ID of the post being saved.
     */
    public function save_artwork_meta( $post_id ) {
        // Check if our nonces are set and verify them
        if ( ! isset( $_POST['vortex_artwork_pricing_nonce'] ) || 
             ! wp_verify_nonce( $_POST['vortex_artwork_pricing_nonce'], 'vortex_artwork_pricing_nonce' ) ) {
            return;
        }
        
        if ( ! isset( $_POST['vortex_artwork_blockchain_nonce'] ) || 
             ! wp_verify_nonce( $_POST['vortex_artwork_blockchain_nonce'], 'vortex_artwork_blockchain_nonce' ) ) {
            return;
        }
        
        if ( ! isset( $_POST['vortex_artwork_editions_nonce'] ) || 
             ! wp_verify_nonce( $_POST['vortex_artwork_editions_nonce'], 'vortex_artwork_editions_nonce' ) ) {
            return;
        }
        
        // Check if this is an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        // Check user permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // Save pricing data
        if ( isset( $_POST['vortex_artwork_price'] ) ) {
            update_post_meta( $post_id, '_vortex_artwork_price', sanitize_text_field( $_POST['vortex_artwork_price'] ) );
        }
        
        if ( isset( $_POST['vortex_tola_price'] ) ) {
            update_post_meta( $post_id, '_vortex_tola_price', sanitize_text_field( $_POST['vortex_tola_price'] ) );
        }
        
        // Save blockchain data
        if ( isset( $_POST['vortex_blockchain_token_id'] ) ) {
            update_post_meta( $post_id, '_vortex_blockchain_token_id', sanitize_text_field( $_POST['vortex_blockchain_token_id'] ) );
        }
        
        if ( isset( $_POST['vortex_blockchain_contract_address'] ) ) {
            update_post_meta( $post_id, '_vortex_blockchain_contract_address', sanitize_text_field( $_POST['vortex_blockchain_contract_address'] ) );
        }
        
        if ( isset( $_POST['vortex_blockchain_name'] ) ) {
            update_post_meta( $post_id, '_vortex_blockchain_name', sanitize_text_field( $_POST['vortex_blockchain_name'] ) );
        }
        
        // Save edition data
        if ( isset( $_POST['vortex_artwork_edition_size'] ) ) {
            update_post_meta( $post_id, '_vortex_artwork_edition_size', absint( $_POST['vortex_artwork_edition_size'] ) );
        }
    }

    /**
     * Format the artwork price for display.
     *
     * @since    1.0.0
     * @param    string    $price       The unformatted price.
     * @param    int       $artwork_id  The artwork ID.
     * @return   string                 The formatted price.
     */
    public function format_artwork_price( $price, $artwork_id ) {
        $currency = get_option( 'vortex_marketplace_currency', 'USD' );
        
        if ( $currency === 'USD' ) {
            return '$' . number_format( (float) $price, 2 );
        } else if ( $currency === 'TOLA' ) {
            $tola_price = get_post_meta( $artwork_id, '_vortex_tola_price', true );
            return number_format( (float) $tola_price, 6 ) . ' TOLA';
        }
        
        return $price . ' ' . $currency;
    }

    /**
     * Render the purchase button for an artwork.
     *
     * @since    1.0.0
     * @param    string    $button      The button HTML.
     * @param    int       $artwork_id  The artwork ID.
     * @return   string                 The purchase button HTML.
     */
    public function render_purchase_button( $button, $artwork_id ) {
        $price = get_post_meta( $artwork_id, '_vortex_artwork_price', true );
        $tola_price = get_post_meta( $artwork_id, '_vortex_tola_price', true );
        $edition_size = get_post_meta( $artwork_id, '_vortex_artwork_edition_size', true );
        
        $button_text = __( 'Purchase', 'vortex-ai-marketplace' );
        if ( $edition_size == 1 ) {
            $button_text = __( 'Purchase Original', 'vortex-ai-marketplace' );
        } else {
            $button_text = sprintf( __( 'Purchase Edition (1/%s)', 'vortex-ai-marketplace' ), $edition_size );
        }
        
        $html = '<div class="vortex-purchase-options">';
        
        if ( ! empty( $price ) ) {
            $html .= '<button class="vortex-purchase-button vortex-purchase-fiat" data-artwork="' . esc_attr( $artwork_id ) . '" data-price="' . esc_attr( $price ) . '" data-currency="USD">';
            $html .= $button_text . ' - $' . number_format( (float) $price, 2 );
            $html .= '</button>';
        }
        
        if ( ! empty( $tola_price ) ) {
            $html .= '<button class="vortex-purchase-button vortex-purchase-tola" data-artwork="' . esc_attr( $artwork_id ) . '" data-price="' . esc_attr( $tola_price ) . '" data-currency="TOLA">';
            $html .= $button_text . ' - ' . number_format( (float) $tola_price, 6 ) . ' TOLA';
            $html .= '</button>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Get marketplace settings.
     *
     * @since    1.0.0
     * @param    array    $settings    Existing settings.
     * @return   array                 Modified settings.
     */
    public function get_marketplace_settings( $settings ) {
        $marketplace_settings = array(
            'currency' => get_option( 'vortex_marketplace_currency', 'USD' ),
            'commission' => get_option( 'vortex_marketplace_commission', 10 ),
            'enable_tola' => get_option( 'vortex_enable_tola_payments', 'yes' ),
            'blockchain_network' => get_option( 'vortex_blockchain_network', 'solana' ),
            'solana_network' => get_option( 'vortex_solana_network', 'mainnet' ),
        );
        
        return array_merge( $settings, $marketplace_settings );
    }

    /**
     * Marketplace shortcode to display the marketplace.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            Marketplace HTML.
     */
    public function marketplace_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'category' => '',
            'style' => '',
            'artist' => '',
            'ai_engine' => '',
            'columns' => 3,
            'count' => 12,
        ), $atts, 'vortex_marketplace' );
        
        // Start output buffer
        ob_start();
        
        // Query for artworks
        $args = array(
            'post_type' => 'vortex_artwork',
            'posts_per_page' => $atts['count'],
        );
        
        // Add taxonomy filters
        $tax_query = array();
        
        if ( ! empty( $atts['category'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'art_category',
                'field' => 'slug',
                'terms' => explode( ',', $atts['category'] ),
            );
        }
        
        if ( ! empty( $atts['style'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'art_style',
                'field' => 'slug',
                'terms' => explode( ',', $atts['style'] ),
            );
        }
        
        if ( ! empty( $atts['ai_engine'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'ai_engine',
                'field' => 'slug',
                'terms' => explode( ',', $atts['ai_engine'] ),
            );
        }
        
        if ( ! empty( $tax_query ) ) {
            $args['tax_query'] = $tax_query;
        }
        
        // Artist filter
        if ( ! empty( $atts['artist'] ) ) {
            $artist = get_page_by_path( $atts['artist'], OBJECT, 'vortex_artist' );
            if ( $artist ) {
                $args['author'] = $artist->post_author;
            }
        }
        
        // Run the query
        $artworks = new WP_Query( $args );
        
        if ( $artworks->have_posts() ) {
            ?>
            <div class="vortex-marketplace-grid columns-<?php echo esc_attr( $atts['columns'] ); ?>">
                <?php while ( $artworks->have_posts() ) : $artworks->the_post(); ?>
                    <div class="vortex-artwork-item">
                        <div class="vortex-artwork-image">
                            <a href="<?php the_permalink(); ?>">
                                <?php 
                                if ( has_post_thumbnail() ) {
                                    the_post_thumbnail( 'large' );
                                } else {
                                    echo '<img src="' . esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'public/images/placeholder.jpg' ) . '" alt="' . esc_attr( get_the_title() ) . '" />';
                                }
                                ?>
                            </a>
                        </div>
                        <div class="vortex-artwork-info">
                            <h3 class="vortex-artwork-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <div class="vortex-artwork-artist">
                                <?php 
                                $author_id = get_post_field( 'post_author', get_the_ID() );
                                $author_name = get_the_author_meta( 'display_name', $author_id );
                                echo esc_html( __( 'By', 'vortex-ai-marketplace' ) . ' ' . $author_name );
                                ?>
                            </div>
                            <div class="vortex-artwork-price">
                                <?php 
                                $price = get_post_meta( get_the_ID(), '_vortex_artwork_price', true );
                                $tola_price = get_post_meta( get_the_ID(), '_vortex_tola_price', true );
                                
                                if ( ! empty( $price ) ) {
                                    echo apply_filters( 'vortex_artwork_price_display', $price, get_the_ID() );
                                } elseif ( ! empty( $tola_price ) ) {
                                    echo number_format( (float) $tola_price, 6 ) . ' TOLA';
                                } else {
                                    _e( 'Price on request', 'vortex-ai-marketplace' );
                                }
                                ?>
                            </div>
                            <?php echo apply_filters( 'vortex_artwork_purchase_button', '', get_the_ID() ); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <?php
            wp_reset_postdata();
        } else {
            echo '<p>' . __( 'No artworks found.', 'vortex-ai-marketplace' ) . '</p>';
        }
        
        return ob_get_clean();
    }

    /**
     * Artist profile shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            Artist profile HTML.
     */
    public function artist_profile_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'id' => 0,
            'slug' => '',
        ), $atts, 'vortex_artist_profile' );
        
        // Start output buffer
        ob_start();
        
        // Get artist by ID or slug
        $artist = null;
        if ( ! empty( $atts['id'] ) ) {
            $artist = get_post( $atts['id'] );
        } elseif ( ! empty( $atts['slug'] ) ) {
            $artist = get_page_by_path( $atts['slug'], OBJECT, 'vortex_artist' );
        }
        
        if ( $artist && $artist->post_type === 'vortex_artist' ) {
            ?>
            <div class="vortex-artist-profile">
                <div class="vortex-artist-header">
                    <?php if ( has_post_thumbnail( $artist->ID ) ) : ?>
                        <div class="vortex-artist-avatar">
                            <?php echo get_the_post_thumbnail( $artist->ID, 'thumbnail' ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="vortex-artist-info">
                        <h2 class="vortex-artist-name"><?php echo esc_html( get_the_title( $artist->ID ) ); ?></h2>
                        
                        <?php 
                        $wallet_address = get_post_meta( $artist->ID, '_vortex_artist_wallet_address', true );
                        if ( ! empty( $wallet_address ) ) : 
                            $short_wallet = substr( $wallet_address, 0, 6 ) . '...' . substr( $wallet_address, -4 );
                        ?>
                            <div class="vortex-artist-wallet">
                                <span title="<?php echo esc_attr( $wallet_address ); ?>"><?php echo esc_html( $short_wallet ); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="vortex-artist-bio">
                    <?php echo apply_filters( 'the_content', $artist->post_content ); ?>
                </div>
                
                <h3><?php _e( 'Artworks by this Artist', 'vortex-ai-marketplace' ); ?></h3>
                
                <?php
                // Query for artist's artworks
                $args = array(
                    'post_type' => 'vortex_artwork',
                    'posts_per_page' => 6,
                    'author' => $artist->post_author,
                );
                
                $artworks = new WP_Query( $args );
                
                if ( $artworks->have_posts() ) {
                    echo '<div class="vortex-artist-artworks columns-3">';
                    
                    while ( $artworks->have_posts() ) {
                        $artworks->the_post();
                        ?>
                        <div class="vortex-artwork-item">
                            <div class="vortex-artwork-image">
                                <a href="<?php the_permalink(); ?>">
                                    <?php 
                                    if ( has_post_thumbnail() ) {
                                        the_post_thumbnail( 'medium' );
                                    } else {
                                        echo '<img src="' . esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'public/images/placeholder.jpg' ) . '" alt="' . esc_attr( get_the_title() ) . '" />';
                                    }
                                    ?>
                                </a>
                            </div>
                            <div class="vortex-artwork-info">
                                <h4 class="vortex-artwork-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                <div class="vortex-artwork-price">
                                    <?php 
                                    $price = get_post_meta( get_the_ID(), '_vortex_artwork_price', true );
                                    if ( ! empty( $price ) ) {
                                        echo apply_filters( 'vortex_artwork_price_display', $price, get_the_ID() );
                                    } else {
                                        _e( 'Price on request', 'vortex-ai-marketplace' );
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    echo '</div>';
                } else {
                    echo '<p>' . __( 'No artworks found.', 'vortex-ai-marketplace' ) . '</p>';
                }
                
                echo '</div>';
            } else {
                echo '<p>' . __( 'Artist not found.', 'vortex-ai-marketplace' ) . '</p>';
            }
            
            return ob_get_clean();
        }

    /**
     * AJAX handler for purchasing artwork.
     *
     * @since    1.0.0
     */
    public function ajax_purchase_artwork() {
        // Verify nonce
        if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'vortex_purchase_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'vortex-ai-marketplace' ) ) );
        }
        
        $artwork_id = isset( $_POST['artwork_id'] ) ? intval( $_POST['artwork_id'] ) : 0;
        $currency = isset( $_POST['currency'] ) ? sanitize_text_field( $_POST['currency'] ) : 'USD';
        
        if ( ! $artwork_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid artwork ID.', 'vortex-ai-marketplace' ) ) );
        }
        
        $current_user_id = get_current_user_id();
        if ( ! $current_user_id ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in to purchase artwork.', 'vortex-ai-marketplace' ) ) );
        }
        
        try {
            if ( $currency === 'TOLA' ) {
                // Process TOLA payment
                $tola_price = get_post_meta( $artwork_id, '_vortex_tola_price', true );
                
                $result = $this->tola->process_payment( $current_user_id, $artwork_id, $tola_price );
                if ( ! $result ) {
                    throw new Exception( __( 'TOLA payment processing failed.', 'vortex-ai-marketplace' ) );
                }
            } else {
                // Process regular payment
                $price = get_post_meta( $artwork_id, '_vortex_artwork_price', true );
                
                // Process payment (implementation depends on your payment gateway)
                // For now, we'll just simulate a successful payment
                $payment_successful = true;
                
                if ( ! $payment_successful ) {
                    throw new Exception( __( 'Payment processing failed.', 'vortex-ai-marketplace' ) );
                }
            }
            
            // Record the sale
            $this->record_sale( $artwork_id, $current_user_id, $currency );
            
            // Trigger purchase completed action
            $purchase_data = array(
                'artwork_id' => $artwork_id,
                'buyer_id' => $current_user_id,
                'currency' => $currency,
                'timestamp' => current_time( 'mysql' ),
            );
            do_action( 'vortex_artwork_purchase_completed', $artwork_id, $current_user_id, $purchase_data );
            
            wp_send_json_success( array( 
                'message' => __( 'Purchase successful!', 'vortex-ai-marketplace' ),
                'redirect' => get_permalink( $artwork_id )
            ) );
            
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    /**
     * Record a sale in the database.
     *
     * @since    1.0.0
     * @param    int       $artwork_id    The artwork ID.
     * @param    int       $buyer_id      The buyer's user ID.
     * @param    string    $currency      The currency used.
     * @return   bool                     Whether the sale was recorded successfully.
     */
    private function record_sale( $artwork_id, $buyer_id, $currency = 'USD' ) {
        global $wpdb;
        
        $price = 0;
        if ( $currency === 'TOLA' ) {
            $price = get_post_meta( $artwork_id, '_vortex_tola_price', true );
        } else {
            $price = get_post_meta( $artwork_id, '_vortex_artwork_price', true );
        }
        
        // Insert into sales table
        $result = $wpdb->insert(
            $wpdb->prefix . 'vortex_sales',
            array(
                'artwork_id' => $artwork_id,
                'buyer_id' => $buyer_id,
                'amount' => $price,
                'currency' => $currency,
                'sale_date' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%f', '%s', '%s' )
        );
        
        return $result !== false;
    }

    // Add TOLA-only transaction enforcement
    public function enforce_tola_transactions() {
        // Validate currency
        if ($this->currency !== 'TOLA') {
            throw new Exception(__('Only TOLA tokens are accepted for transactions', 'vortex'));
        }
        
        // Verify TOLA balance before transaction
        // ... existing code ...
    }

    // Add TOLA balance checks
    public function verify_tola_balance($user_id, $amount) {
        $balance = $this->tola->get_user_balance($user_id);
        return $balance >= $amount;
    }
}
