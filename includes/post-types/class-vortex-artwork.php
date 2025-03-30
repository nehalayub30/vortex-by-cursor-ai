<?php
/**
 * The artwork post type functionality.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/post-types
 */

/**
 * The artwork post type functionality.
 *
 * Defines the artwork post type and its features.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/post-types
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Artwork {

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
     * The post type name.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $post_type    The post type name.
     */
    private $post_type;

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
        $this->post_type = 'vortex_artwork';

        // Register hooks
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_' . $this->post_type, array( $this, 'save_meta_box_data' ) );
        add_action( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'set_custom_columns' ) );
        add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'custom_column_content' ), 10, 2 );
        add_filter( 'manage_edit-' . $this->post_type . '_sortable_columns', array( $this, 'set_sortable_columns' ) );
        add_filter( 'enter_title_here', array( $this, 'change_title_placeholder' ) );
        add_filter( 'post_updated_messages', array( $this, 'custom_updated_messages' ) );
        add_filter( 'bulk_post_updated_messages', array( $this, 'custom_bulk_updated_messages' ), 10, 2 );
        add_action( 'admin_head', array( $this, 'add_admin_styles' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
        add_filter( 'pre_get_posts', array( $this, 'modify_admin_query' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_vortex_get_artworks', array( $this, 'ajax_get_artworks' ) );
        add_action( 'wp_ajax_nopriv_vortex_get_artworks', array( $this, 'ajax_get_artworks' ) );
        add_action( 'wp_ajax_vortex_get_artwork_data', array( $this, 'ajax_get_artwork_data' ) );
        add_action( 'wp_ajax_vortex_update_artwork_views', array( $this, 'ajax_update_artwork_views' ) );
        add_action( 'wp_ajax_nopriv_vortex_update_artwork_views', array( $this, 'ajax_update_artwork_views' ) );
    }

    /**
     * Register the custom post type.
     *
     * @since    1.0.0
     */
    public function register_post_type() {
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
            'not_found_in_trash' => __( 'No artworks found in Trash.', 'vortex-ai-marketplace' ),
        );

        $capabilities = array(
            'edit_post'              => 'edit_vortex_artwork',
            'read_post'              => 'read_vortex_artwork',
            'delete_post'            => 'delete_vortex_artwork',
            'edit_posts'             => 'edit_vortex_artworks',
            'edit_others_posts'      => 'edit_others_vortex_artworks',
            'publish_posts'          => 'publish_vortex_artworks',
            'read_private_posts'     => 'read_private_vortex_artworks',
            'delete_posts'           => 'delete_vortex_artworks',
            'delete_private_posts'   => 'delete_private_vortex_artworks',
            'delete_published_posts' => 'delete_published_vortex_artworks',
            'delete_others_posts'    => 'delete_others_vortex_artworks',
            'edit_private_posts'     => 'edit_private_vortex_artworks',
            'edit_published_posts'   => 'edit_published_vortex_artworks',
        );

        $args = array(
            'labels'               => $labels,
            'description'          => __( 'Digital artwork for the VORTEX AI Marketplace', 'vortex-ai-marketplace' ),
            'public'               => true,
            'publicly_queryable'   => true,
            'show_ui'              => true,
            'show_in_menu'         => 'vortex_marketplace',
            'query_var'            => true,
            'rewrite'              => array( 'slug' => 'artwork' ),
            'capability_type'      => array( 'vortex_artwork', 'vortex_artworks' ),
            'map_meta_cap'         => true,
            'capabilities'         => $capabilities,
            'has_archive'          => true,
            'hierarchical'         => false,
            'menu_position'        => null,
            'supports'             => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'revisions' ),
            'menu_icon'            => 'dashicons-art',
            'show_in_rest'         => true,
            'rest_base'            => 'artworks',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'taxonomies'           => array( 'vortex_artwork_category', 'vortex_artwork_tag' ),
        );

        register_post_type( $this->post_type, $args );

        // Register meta fields for REST API
        register_post_meta( $this->post_type, '_vortex_artwork_price', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'number',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_artwork_edition_size', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'integer',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_artwork_ai_prompt', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_created_with_huraii', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_blockchain_token_id', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_blockchain_contract_address', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_blockchain_name', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_tola_price', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'number',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        // Add meta for tracking views
        register_post_meta( $this->post_type, '_vortex_artwork_view_count', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'integer',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));
    }

    /**
     * Add meta boxes for additional artwork information.
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
            'vortex_artwork_ai_details',
            __( 'AI Generation Details', 'vortex-ai-marketplace' ),
            array( $this, 'render_ai_details_meta_box' ),
            $this->post_type,
            'normal',
            'default'
        );

        add_meta_box(
            'vortex_artwork_blockchain',
            __( 'Blockchain Information', 'vortex-ai-marketplace' ),
            array( $this, 'render_blockchain_meta_box' ),
            $this->post_type,
            'side',
            'default'
        );

        add_meta_box(
            'vortex_artwork_stats',
            __( 'Artwork Statistics', 'vortex-ai-marketplace' ),
            array( $this, 'render_stats_meta_box' ),
            $this->post_type,
            'side',
            'default'
        );
    }

    /**
     * Render the artwork details meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_details_meta_box( $post ) {
        // Add a nonce field for security
        wp_nonce_field( 'vortex_artwork_details_nonce', 'vortex_artwork_details_nonce' );

        // Retrieve current values
        $price = get_post_meta( $post->ID, '_vortex_artwork_price', true );
        $edition_size = get_post_meta( $post->ID, '_vortex_artwork_edition_size', true );
        $tola_price = get_post_meta( $post->ID, '_vortex_tola_price', true );
        $dimensions = get_post_meta( $post->ID, '_vortex_artwork_dimensions', true );
        $medium = get_post_meta( $post->ID, '_vortex_artwork_medium', true );
        $is_featured = get_post_meta( $post->ID, '_vortex_artwork_is_featured', true );
        $is_sold_out = get_post_meta( $post->ID, '_vortex_artwork_is_sold_out', true );
        $is_limited_edition = get_post_meta( $post->ID, '_vortex_artwork_is_limited_edition', true );

        // Default currency symbol
        $currency_symbol = get_option( 'vortex_marketplace_currency_symbol', '$' );

        // Output the fields
        ?>
        <div class="vortex-meta-field">
            <label for="vortex_artwork_price">
                <?php echo esc_html__( 'Price', 'vortex-ai-marketplace' ) . ' (' . esc_html( $currency_symbol ) . ')'; ?>:
            </label>
            <input type="number" id="vortex_artwork_price" name="vortex_artwork_price" 
                   value="<?php echo esc_attr( $price ); ?>" step="0.01" min="0">
        </div>

        <div class="vortex-meta-field">
            <label for="vortex_artwork_tola_price">
                <?php esc_html_e( 'TOLA Price', 'vortex-ai-marketplace' ); ?>:
            </label>
            <input type="number" id="vortex_artwork_tola_price" name="vortex_artwork_tola_price" 
                   value="<?php echo esc_attr( $tola_price ); ?>" step="0.01" min="0">
        </div>

        <div class="vortex-meta-field">
            <input type="checkbox" id="vortex_artwork_is_limited_edition" name="vortex_artwork_is_limited_edition" 
                   value="1" <?php checked( $is_limited_edition, '1' ); ?>>
            <label for="vortex_artwork_is_limited_edition">
                <?php esc_html_e( 'Limited Edition', 'vortex-ai-marketplace' ); ?>
            </label>
        </div>

        <div class="vortex-meta-field" id="edition_size_field" style="<?php echo $is_limited_edition ? '' : 'display:none;'; ?>">
            <label for="vortex_artwork_edition_size">
                <?php esc_html_e( 'Edition Size', 'vortex-ai-marketplace' ); ?>:
            </label>
            <input type="number" id="vortex_artwork_edition_size" name="vortex_artwork_edition_size" 
                   value="<?php echo esc_attr( $edition_size ); ?>" min="1">
        </div>

        <div class="vortex-meta-field">
            <label for="vortex_artwork_dimensions">
                <?php esc_html_e( 'Dimensions', 'vortex-ai-marketplace' ); ?>:
            </label>
            <input type="text" id="vortex_artwork_dimensions" name="vortex_artwork_dimensions" 
                   value="<?php echo esc_attr( $dimensions ); ?>" placeholder="e.g. 2000x3000px">
        </div>

        <div class="vortex-meta-field">
            <label for="vortex_artwork_medium">
                <?php esc_html_e( 'Medium', 'vortex-ai-marketplace' ); ?>:
            </label>
            <input type="text" id="vortex_artwork_medium" name="vortex_artwork_medium" 
                   value="<?php echo esc_attr( $medium ); ?>" placeholder="e.g. Digital, AI Generated">
        </div>

        <div class="vortex-meta-field">
            <input type="checkbox" id="vortex_artwork_is_featured" name="vortex_artwork_is_featured" 
                   value="1" <?php checked( $is_featured, '1' ); ?>>
            <label for="vortex_artwork_is_featured">
                <?php esc_html_e( 'Featured Artwork', 'vortex-ai-marketplace' ); ?>
            </label>
        </div>

        <div class="vortex-meta-field">
            <input type="checkbox" id="vortex_artwork_is_sold_out" name="vortex_artwork_is_sold_out" 
                   value="1" <?php checked( $is_sold_out, '1' ); ?>>
            <label for="vortex_artwork_is_sold_out">
                <?php esc_html_e( 'Sold Out', 'vortex-ai-marketplace' ); ?>
            </label>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('#vortex_artwork_is_limited_edition').change(function() {
                    if($(this).is(':checked')) {
                        $('#edition_size_field').show();
                    } else {
                        $('#edition_size_field').hide();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Render the AI details meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_ai_details_meta_box( $post ) {
        // Add a nonce field for security
        wp_nonce_field( 'vortex_artwork_ai_details_nonce', 'vortex_artwork_ai_details_nonce' );

        // Retrieve current values
        $created_with_huraii = get_post_meta( $post->ID, '_vortex_created_with_huraii', true );
        $ai_prompt = get_post_meta( $post->ID, '_vortex_artwork_ai_prompt', true );
        $ai_negative_prompt = get_post_meta( $post->ID, '_vortex_artwork_ai_negative_prompt', true );
        $ai_model = get_post_meta( $post->ID, '_vortex_artwork_ai_model', true );
        $ai_seed = get_post_meta( $post->ID, '_vortex_artwork_ai_seed', true );
        $ai_guidance_scale = get_post_meta( $post->ID, '_vortex_artwork_ai_guidance_scale', true );
        $ai_steps = get_post_meta( $post->ID, '_vortex_artwork_ai_steps', true );

        // Output the fields
        ?>
        <div class="vortex-meta-field">
            <input type="checkbox" id="vortex_created_with_huraii" name="vortex_created_with_huraii" 
                   value="1" <?php checked( $created_with_huraii, '1' ); ?>>
            <label for="vortex_created_with_huraii">
                <?php esc_html_e( 'Created with HURAII', 'vortex-ai-marketplace' ); ?>
            </label>
        </div>

        <div class="vortex-meta-field">
            <label for="vortex_artwork_ai_model">
                <?php esc_html_e( 'AI Model', 'vortex-ai-marketplace' ); ?>:
            </label>
            <select id="vortex_artwork_ai_model" name="vortex_artwork_ai_model">
                <option value=""><?php esc_html_e( 'Select a model', 'vortex-ai-marketplace' ); ?></option>
                <?php
                $models = array(
                    'stable-diffusion-v1-5' => __( 'Stable Diffusion v1.5', 'vortex-ai-marketplace' ),
                    'stable-diffusion-xl' => __( 'Stable Diffusion XL', 'vortex-ai-marketplace' ),
                    'dall-e-3' => __( 'DALL-E 3', 'vortex-ai-marketplace' ),
                    'midjourney-v5' => __( 'Midjourney v5', 'vortex-ai-marketplace' ),
                );

                foreach ( $models as $model_id => $model_name ) :
                    echo '<option value="' . esc_attr( $model_id ) . '" ' . selected( $ai_model, $model_id, false ) . '>' . esc_html( $model_name ) . '</option>';
                endforeach;
                ?>
            </select>
        </div>

        <div class="vortex-meta-field">
            <label for="vortex_artwork_ai_prompt">
                <?php esc_html_e( 'Prompt', 'vortex-ai-marketplace' ); ?>:
            </label>
            <textarea id="vortex_artwork_ai_prompt" name="vortex_artwork_ai_prompt" rows="4" class="large-text"><?php echo esc_textarea( $ai_prompt ); ?></textarea>
        </div>

        <div class="vortex-meta-field">
            <label for="vortex_artwork_ai_negative_prompt">
                <?php esc_html_e( 'Negative Prompt', 'vortex-ai-marketplace' ); ?>:
            </label>
            <textarea id="vortex_artwork_ai_negative_prompt" name="vortex_artwork_ai_negative_prompt" rows="3" class="large-text"><?php echo esc_textarea( $ai_negative_prompt ); ?></textarea>
        </div>

        <div class="vortex-meta-row">
            <div class="vortex-meta-col">
                <label for="vortex_artwork_ai_seed">
                    <?php esc_html_e( 'Seed', 'vortex-ai-marketplace' ); ?>:
                </label>
                <input type="number" id="vortex_artwork_ai_seed" name="vortex_artwork_ai_seed" 
                       value="<?php echo esc_attr( $ai_seed ); ?>">
            </div>

            <div class="vortex-meta-col">
                <label for="vortex_artwork_ai_guidance_scale">
                    <?php esc_html_e( 'Guidance Scale', 'vortex-ai-marketplace' ); ?>:
                </label>
                <input type="number" id="vortex_artwork_ai_guidance_scale" name="vortex_artwork_ai_guidance_scale" 
                       value="<?php echo esc_attr( $ai_guidance_scale ); ?>" step="0.1" min="1" max="20">
            </div>

            <div class="vortex-meta-col">
                <label for="vortex_artwork_ai_steps">
                    <?php esc_html_e( 'Steps', 'vortex-ai-marketplace' ); ?>:
                </label>
                <input type="number" id="vortex_artwork_ai_steps" name="vortex_artwork_ai_steps" 
                       value="<?php echo esc_attr( $ai_steps ); ?>" min="1" max="150">
            </div>
        </div>

        <p class="description">
            <?php esc_html_e( 'Providing accurate AI generation details helps users understand how the artwork was created and may influence search results.', 'vortex-ai-marketplace' ); ?>
        </p>
        <?php
    }

    /**
     * Render the blockchain details meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_blockchain_meta_box( $post ) {
        // Add a nonce field for security
        wp_nonce_field( 'vortex_artwork_blockchain_nonce', 'vortex_artwork_blockchain_nonce' );

        // Retrieve current values
        $token_id = get_post_meta( $post->ID, '_vortex_blockchain_token_id', true );
        $contract_address = get_post_meta( $post->ID, '_vortex_blockchain_contract_address', true );
        $blockchain_name = get_post_meta( $post->ID, '_vortex_blockchain_name', true );
        $is_minted = get_post_meta( $post->ID, '_vortex_is_minted', true );

        // Default blockchain option
        $default_blockchain = get_option( 'vortex_blockchain_network', 'solana' );

        // Output the fields
        ?>
        <div class="vortex-meta-field">
            <input type="checkbox" id="vortex_is_minted" name="vortex_is_minted" 
                   value="1" <?php checked( $is_minted, '1' ); ?>>
            <label for="vortex_is_minted">
                <?php esc_html_e( 'Minted on Blockchain', 'vortex-ai-marketplace' ); ?>
            </label>
        </div>

        <div id="blockchain_details" style="<?php echo $is_minted ? '' : 'display:none;'; ?>">
            <div class="vortex-meta-field">
                <label for="vortex_blockchain_name">
                    <?php esc_html_e( 'Blockchain', 'vortex-ai-marketplace' ); ?>:
                </label>
                <select id="vortex_blockchain_name" name="vortex_blockchain_name">
                    <option value="solana" <?php selected( $blockchain_name, 'solana' ); ?>>Solana</option>
                    <option value="ethereum" <?php selected( $blockchain_name, 'ethereum' ); ?>>Ethereum</option>
                    <option value="polygon" <?php selected( $blockchain_name, 'polygon' ); ?>>Polygon</option>
                    <option value="tezos" <?php selected( $blockchain_name, 'tezos' ); ?>>Tezos</option>
                </select>
            </div>

            <div class="vortex-meta-field">
                <label for="vortex_blockchain_token_id">
                    <?php esc_html_e( 'Token ID', 'vortex-ai-marketplace' ); ?>:
                </label>
                <input type="text" id="vortex_blockchain_token_id" name="vortex_blockchain_token_id" 
                       value="<?php echo esc_attr( $token_id ); ?>">
            </div>

            <div class="vortex-meta-field">
                <label for="vortex_blockchain_contract_address">
                    <?php esc_html_e( 'Contract Address', 'vortex-ai-marketplace' ); ?>:
                </label>
                <input type="text" id="vortex_blockchain_contract_address" name="vortex_blockchain_contract_address" 
                       value="<?php echo esc_attr( $contract_address ); ?>">
            </div>
        </div>

        <p class="description">
            <?php esc_html_e( 'If this artwork is minted as an NFT, provide the blockchain details.', 'vortex-ai-marketplace' ); ?>
        </p>

        <script>
            jQuery(document).ready(function($) {
                $('#vortex_is_minted').change(function() {
                    if($(this).is(':checked')) {
                        $('#blockchain_details').show();
                    } else {
                        $('#blockchain_details').hide();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Render the artwork statistics meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_stats_meta_box( $post ) {
        // Retrieve stats
        $view_count = get_post_meta( $post->ID, '_vortex_artwork_view_count', true );
        $view_count = empty( $view_count ) ? 0 : intval( $view_count );
        
        $like_count = get_post_meta( $post->ID, '_vortex_artwork_like_count', true );
        $like_count = empty( $like_count ) ? 0 : intval( $like_count );
        
        $share_count = get_post_meta( $post->ID, '_vortex_artwork_share_count', true );
        $share_count = empty( $share_count ) ? 0 : intval( $share_count );
        
        $download_count = get_post_meta( $post->ID, '_vortex_artwork_download_count', true );
        $download_count = empty( $download_count ) ? 0 : intval( $download_count );
        
        $sales_count = get_post_meta( $post->ID, '_vortex_artwork_sales_count', true );
        $sales_count = empty( $sales_count ) ? 0 : intval( $sales_count );
        
        // Display stats
        ?>
        <div class="vortex-stats-container">
            <div class="vortex-stat-item">
                <span class="vortex-stat-label"><?php esc_html_e( 'Views', 'vortex-ai-marketplace' ); ?>:</span>
                <span class="vortex-stat-value"><?php echo esc_html( number_format( $view_count ) ); ?></span>
            </div>
            
            <div class="vortex-stat-item">
                <span class="vortex-stat-label"><?php esc_html_e( 'Likes', 'vortex-ai-marketplace' ); ?>:</span>
                <span class="vortex-stat-value"><?php echo esc_html( number_format( $like_count ) ); ?></span>
            </div>
            
            <div class="vortex-stat-item">
                <span class="vortex-stat-label"><?php esc_html_e( 'Shares', 'vortex-ai-marketplace' ); ?>:</span>
                <span class="vortex-stat-value"><?php echo esc_html( number_format( $share_count ) ); ?></span>
            </div>
            
            <div class="vortex-stat-item">
                <span class="vortex-stat-label"><?php esc_html_e( 'Downloads', 'vortex-ai-marketplace' ); ?>:</span>
                <span class="vortex-stat-value"><?php echo esc_html( number_format( $download_count ) ); ?></span>
            </div>
            
            <div class="vortex-stat-item">
                <span class="vortex-stat-label"><?php esc_html_e( 'Sales', 'vortex-ai-marketplace' ); ?>:</span>
                <span class="vortex-stat-value"><?php echo esc_html( number_format( $sales_count ) ); ?></span>
            </div>
        </div>
        
        <p class="description">
            <?php esc_html_e( 'These statistics are automatically updated and cannot be modified directly.', 'vortex-ai-marketplace' ); ?>
        </p>
        <?php
    }

    /**
     * Save the meta box data.
     *
     * @since    1.0.0
     * @param    int    $post_id    The post ID.
     */
    public function save_meta_box_data( $post_id ) {
        // Check if our nonces are set and verify them
        if ( ! isset( $_POST['vortex_artwork_details_nonce'] ) || 
             ! wp_verify_nonce( $_POST['vortex_artwork_details_nonce'], 'vortex_artwork_details_nonce' ) ) {
            return;
        }

        if ( ! isset( $_POST['vortex_artwork_ai_details_nonce'] ) || 
             ! wp_verify_nonce( $_POST['vortex_artwork_ai_details_nonce'], 'vortex_artwork_ai_details_nonce' ) ) {
            return;
        }

        if ( ! isset( $_POST['vortex_artwork_blockchain_nonce'] ) || 
             ! wp_verify_nonce( $_POST['vortex_artwork_blockchain_nonce'], 'vortex_artwork_blockchain_nonce' ) ) {
            return;
        }

        // Check if this is an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check the user's permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Artwork Details
        if ( isset( $_POST['vortex_artwork_price'] ) ) {
            update_post_meta( $post_id, '_vortex_artwork_price', sanitize_text_field( $_POST['vortex_artwork_price'] ) );
        }

        if ( isset( $_POST['vortex_artwork_tola_price'] ) ) {
            update_post_meta( $post_id, '_vortex_tola_price', sanitize_text_field( $_POST['vortex_artwork_tola_price'] ) );
        }

        $is_limited_edition = isset( $_POST['vortex_artwork_is_limited_edition'] ) ? '1' : '0';
        update_post_meta( $post_id, '_vortex_artwork_is_limited_edition', $is_limited_edition );

        if ( isset( $_POST['vortex_artwork_edition_size'] ) ) {
            update_post_meta( $post_id, '_vortex_artwork_edition_size', absint( $_POST['vortex_artwork_edition_size'] ) );
        }

        if ( isset( $_POST['vortex_artwork_dimensions'] ) ) {
            update_post_meta( $post_id, '_vortex_artwork_dimensions', sanitize_text_field( $_POST['vortex_artwork_dimensions'] ) );
        }

        if ( isset( $_POST['vortex_artwork_medium'] ) ) {
            update_post_meta( $post_id, '_vortex_artwork_medium', sanitize_text_field( $_POST['vortex_artwork_medium'] ) );
        }

        $is_featured = isset( $_POST['vortex_artwork_is_featured'] ) ? '1' : '0';
        update_post_meta( $post_id, '_vortex_artwork_is_featured', $is_featured );

        $is_sold_out = isset( $_POST['vortex_artwork_is_sold_out'] ) ? '1' : '0';
        update_post_meta( $post_id, '_vortex_artwork_is_sold_out', $is_sold_out );

        // AI Details
        $created_with_huraii = isset( $_POST['vortex_created_with_huraii'] ) ? '1' : '0';
        update_post_meta( $post_id, '_vortex_created_with_huraii', $created_with_huraii );

        if ( isset( $_POST['vortex_artwork_ai_model'] ) ) {
            update_post_meta( $post_id, '_vortex_artwork_ai_model', sanitize_text_field( $_POST['vortex_artwork_ai_model'] ) );
        }

        if ( isset( $_POST['vortex_artwork_ai_prompt'] ) ) {
            update_post_meta( $post_id, '_vortex_artwork_ai_prompt', sanitize_textarea_field( $_POST['vortex_artwork_ai_prompt'] ) );
        }

        if ( isset( $_POST['vortex_artwork_ai_negative_prompt'] ) ) {
            update_post_meta( $post_id, '_vortex_artwork_ai_negative_prompt', sanitize_textarea_field( $_POST['vortex_artwork_ai_negative_prompt'] ) );
        }

        if ( isset( $_POST['vortex_artwork_ai_seed'] ) ) {
            update_post_meta( $post_id, '_vortex_artwork_ai_seed', sanitize_text_field( $_POST['vortex_artwork_ai_seed'] ) );
        }

        if ( isset( $_POST['vortex_artwork_ai_guidance_scale'] ) ) {
            update_post_meta( $post_id, '_vortex_artwork_ai_guidance_scale', sanitize_text_field( $_POST['vortex_artwork_ai_guidance_scale'] ) );
        }

        if ( isset( $_POST['vortex_artwork_ai_steps'] ) ) ) {
            update_post_meta( $post_id, '_vortex_artwork_ai_steps', absint( $_POST['vortex_artwork_ai_steps'] ) ) );
        }
    }
} 