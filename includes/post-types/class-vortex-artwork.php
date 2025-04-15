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
     * Change the title placeholder for the artwork post type.
     *
     * @since    1.0.0
     * @param    string    $title    The default title placeholder.
     * @return   string              Modified title placeholder.
     */
    public function change_title_placeholder( $title ) {
        $screen = get_current_screen();
        
        if ( $screen && $screen->post_type === $this->post_type ) {
            return __( 'Enter artwork title', 'vortex-ai-marketplace' );
        }
        
        return $title;
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

        if ( isset( $_POST['vortex_artwork_ai_steps'] ) ) {
            update_post_meta( $post_id, '_vortex_artwork_ai_steps', absint( $_POST['vortex_artwork_ai_steps'] ) );
        }
    }

    /**
     * Register REST API fields for artwork post type.
     *
     * @since    1.0.0
     */
    public function register_rest_fields() {
        register_rest_field(
            $this->post_type,
            'artwork_meta',
            array(
                'get_callback'    => array( $this, 'get_artwork_meta_for_api' ),
                'update_callback' => null,
                'schema'          => null,
            )
        );
        
        register_rest_field(
            $this->post_type,
            'artwork_stats',
            array(
                'get_callback'    => array( $this, 'get_artwork_stats_for_api' ),
                'update_callback' => null,
                'schema'          => null,
            )
        );
        
        register_rest_field(
            $this->post_type,
            'artwork_ai_data',
            array(
                'get_callback'    => array( $this, 'get_artwork_ai_data_for_api' ),
                'update_callback' => null,
                'schema'          => null,
            )
        );
        
        register_rest_field(
            $this->post_type,
            'artwork_blockchain',
            array(
                'get_callback'    => array( $this, 'get_artwork_blockchain_for_api' ),
                'update_callback' => null,
                'schema'          => null,
            )
        );
    }
    
    /**
     * Get artwork meta data for REST API.
     *
     * @since    1.0.0
     * @param    array     $object      The post object.
     * @param    string    $field_name  The field name.
     * @param    array     $request     The request data.
     * @return   array                  The artwork meta data.
     */
    public function get_artwork_meta_for_api( $object, $field_name, $request ) {
        $post_id = $object['id'];
        
        return array(
            'price'             => (float) get_post_meta( $post_id, '_vortex_artwork_price', true ),
            'tola_price'        => (float) get_post_meta( $post_id, '_vortex_tola_price', true ),
            'edition_size'      => (int) get_post_meta( $post_id, '_vortex_artwork_edition_size', true ),
            'dimensions'        => get_post_meta( $post_id, '_vortex_artwork_dimensions', true ),
            'medium'            => get_post_meta( $post_id, '_vortex_artwork_medium', true ),
            'is_featured'       => (bool) get_post_meta( $post_id, '_vortex_artwork_is_featured', true ),
            'is_sold_out'       => (bool) get_post_meta( $post_id, '_vortex_artwork_is_sold_out', true ),
            'is_limited_edition' => (bool) get_post_meta( $post_id, '_vortex_artwork_is_limited_edition', true ),
        );
    }
    
    /**
     * Get artwork stats data for REST API.
     *
     * @since    1.0.0
     * @param    array     $object      The post object.
     * @param    string    $field_name  The field name.
     * @param    array     $request     The request data.
     * @return   array                  The artwork stats data.
     */
    public function get_artwork_stats_for_api( $object, $field_name, $request ) {
        $post_id = $object['id'];
        
        return array(
            'view_count'        => (int) get_post_meta( $post_id, '_vortex_artwork_view_count', true ),
            'like_count'        => (int) get_post_meta( $post_id, '_vortex_artwork_like_count', true ),
            'share_count'       => (int) get_post_meta( $post_id, '_vortex_artwork_share_count', true ),
            'purchase_count'    => (int) get_post_meta( $post_id, '_vortex_artwork_purchase_count', true ),
        );
    }
    
    /**
     * Get artwork AI data for REST API.
     *
     * @since    1.0.0
     * @param    array     $object      The post object.
     * @param    string    $field_name  The field name.
     * @param    array     $request     The request data.
     * @return   array                  The artwork AI data.
     */
    public function get_artwork_ai_data_for_api( $object, $field_name, $request ) {
        $post_id = $object['id'];
        
        return array(
            'created_with_huraii' => (bool) get_post_meta( $post_id, '_vortex_created_with_huraii', true ),
            'ai_prompt'           => get_post_meta( $post_id, '_vortex_artwork_ai_prompt', true ),
            'ai_negative_prompt'  => get_post_meta( $post_id, '_vortex_artwork_ai_negative_prompt', true ),
            'ai_model'            => get_post_meta( $post_id, '_vortex_artwork_ai_model', true ),
            'ai_seed'             => get_post_meta( $post_id, '_vortex_artwork_ai_seed', true ),
            'ai_guidance_scale'   => (float) get_post_meta( $post_id, '_vortex_artwork_ai_guidance_scale', true ),
            'ai_steps'            => (int) get_post_meta( $post_id, '_vortex_artwork_ai_steps', true ),
        );
    }
    
    /**
     * Get artwork blockchain data for REST API.
     *
     * @since    1.0.0
     * @param    array     $object      The post object.
     * @param    string    $field_name  The field name.
     * @param    array     $request     The request data.
     * @return   array                  The artwork blockchain data.
     */
    public function get_artwork_blockchain_for_api( $object, $field_name, $request ) {
        $post_id = $object['id'];
        
        return array(
            'token_id'           => get_post_meta( $post_id, '_vortex_blockchain_token_id', true ),
            'contract_address'   => get_post_meta( $post_id, '_vortex_blockchain_contract_address', true ),
            'blockchain_name'    => get_post_meta( $post_id, '_vortex_blockchain_name', true ),
        );
    }

    /**
     * Customize the updated messages for the artwork post type.
     *
     * @since    1.0.0
     * @param    array    $messages    Default post updated messages.
     * @return   array                 Modified post updated messages.
     */
    public function custom_updated_messages( $messages ) {
        global $post;

        $permalink = get_permalink( $post );
        $view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View artwork', 'vortex-ai-marketplace' ) );
        $preview_url = add_query_arg( 'preview', 'true', $permalink );
        $preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_url ), __( 'Preview artwork', 'vortex-ai-marketplace' ) );

        $messages[$this->post_type] = array(
            0  => '', // Unused. Messages start at index 1.
            1  => __( 'Artwork updated.', 'vortex-ai-marketplace' ) . $view_link,
            2  => __( 'Custom field updated.', 'vortex-ai-marketplace' ),
            3  => __( 'Custom field deleted.', 'vortex-ai-marketplace' ),
            4  => __( 'Artwork updated.', 'vortex-ai-marketplace' ),
            5  => isset( $_GET['revision'] ) ? sprintf( __( 'Artwork restored to revision from %s', 'vortex-ai-marketplace' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6  => __( 'Artwork published.', 'vortex-ai-marketplace' ) . $view_link,
            7  => __( 'Artwork saved.', 'vortex-ai-marketplace' ),
            8  => __( 'Artwork submitted.', 'vortex-ai-marketplace' ) . $preview_link,
            9  => sprintf(
                __( 'Artwork scheduled for: <strong>%1$s</strong>.', 'vortex-ai-marketplace' ),
                date_i18n( __( 'M j, Y @ G:i', 'vortex-ai-marketplace' ), strtotime( $post->post_date ) )
            ) . $preview_link,
            10 => __( 'Artwork draft updated.', 'vortex-ai-marketplace' ) . $preview_link,
        );

        return $messages;
    }

    /**
     * Customize the bulk updated messages for the artwork post type.
     *
     * @since    1.0.0
     * @param    array    $bulk_messages    Array of messages.
     * @param    array    $bulk_counts      Array of item counts.
     * @return   array                      Modified array of messages.
     */
    public function custom_bulk_updated_messages( $bulk_messages, $bulk_counts ) {
        $bulk_messages[$this->post_type] = array(
            'updated'   => _n( '%s artwork updated.', '%s artworks updated.', $bulk_counts['updated'], 'vortex-ai-marketplace' ),
            'locked'    => _n( '%s artwork not updated, somebody is editing it.', '%s artworks not updated, somebody is editing them.', $bulk_counts['locked'], 'vortex-ai-marketplace' ),
            'deleted'   => _n( '%s artwork permanently deleted.', '%s artworks permanently deleted.', $bulk_counts['deleted'], 'vortex-ai-marketplace' ),
            'trashed'   => _n( '%s artwork moved to the Trash.', '%s artworks moved to the Trash.', $bulk_counts['trashed'], 'vortex-ai-marketplace' ),
            'untrashed' => _n( '%s artwork restored from the Trash.', '%s artworks restored from the Trash.', $bulk_counts['untrashed'], 'vortex-ai-marketplace' ),
        );

        return $bulk_messages;
    }

    /**
     * Add custom admin styles for the artwork post type.
     *
     * @since    1.0.0
     */
    public function add_admin_styles() {
        $screen = get_current_screen();
        
        // Only add styles on artwork admin screens
        if ( $screen && ( $screen->post_type === $this->post_type || 
             $screen->taxonomy === 'vortex_artwork_category' || 
             $screen->taxonomy === 'vortex_artwork_tag' ) ) {
            
            ?>
            <style type="text/css">
                /* Artwork meta box styling */
                .vortex-meta-field {
                    margin-bottom: 15px;
                }
                
                .vortex-meta-field label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: bold;
                }
                
                .vortex-meta-field input[type="text"],
                .vortex-meta-field input[type="number"],
                .vortex-meta-field select {
                    width: 100%;
                }
                
                .vortex-meta-field input[type="checkbox"] + label {
                    display: inline;
                    margin-left: 5px;
                }
                
                .vortex-meta-row {
                    display: flex;
                    flex-wrap: wrap;
                    margin: 0 -10px;
                }
                
                .vortex-meta-col {
                    flex: 1;
                    padding: 0 10px;
                    min-width: 100px;
                }
                
                /* Stats styling */
                .vortex-stats-container {
                    margin-bottom: 15px;
                }
                
                .vortex-stat-item {
                    margin-bottom: 8px;
                    display: flex;
                    justify-content: space-between;
                }
                
                .vortex-stat-label {
                    font-weight: bold;
                }
                
                /* Custom column styling */
                .column-artwork_thumbnail {
                    width: 80px;
                }
                
                .column-price, 
                .column-edition_size {
                    width: 100px;
                    text-align: center;
                }
                
                .column-artist {
                    width: 150px;
                }
                
                .column-featured {
                    width: 80px;
                    text-align: center;
                }
                
                .vortex-price-display {
                    font-weight: bold;
                    color: #0073aa;
                }
                
                .vortex-featured-icon {
                    color: #f1c40f;
                }
                
                /* AI details styling */
                .vortex-ai-model-badge {
                    display: inline-block;
                    padding: 3px 8px;
                    background: #e9f7fe;
                    border-radius: 3px;
                    font-size: 12px;
                    margin-top: 5px;
                }
            </style>
            <?php
        }
    }

    /**
     * Modify admin query for the artwork post type.
     * 
     * Customizes the listing of artwork posts in the admin.
     *
     * @since    1.0.0
     * @param    WP_Query    $query    The WP_Query instance.
     * @return   WP_Query               Modified query.
     */
    public function modify_admin_query( $query ) {
        // Only modify in admin and when it's the main query for our post type
        if ( is_admin() && $query->is_main_query() && $query->get('post_type') === $this->post_type ) {
            // Get current orderby value
            $orderby = $query->get('orderby');
            
            // Sort by price if selected
            if ( 'price' === $orderby ) {
                $query->set( 'meta_key', '_vortex_artwork_price' );
                $query->set( 'orderby', 'meta_value_num' );
            }
            
            // Sort by views if selected
            if ( 'views' === $orderby ) {
                $query->set( 'meta_key', '_vortex_artwork_view_count' );
                $query->set( 'orderby', 'meta_value_num' );
            }
            
            // Filter by featured status if in the URL
            if ( isset( $_GET['featured'] ) && $_GET['featured'] === '1' ) {
                $meta_query = $query->get( 'meta_query' ) ? $query->get( 'meta_query' ) : array();
                $meta_query[] = array(
                    'key'     => '_vortex_artwork_is_featured',
                    'value'   => '1',
                    'compare' => '='
                );
                $query->set( 'meta_query', $meta_query );
            }
            
            // Filter by AI-generated status if in the URL
            if ( isset( $_GET['ai_generated'] ) && $_GET['ai_generated'] === '1' ) {
                $meta_query = $query->get( 'meta_query' ) ? $query->get( 'meta_query' ) : array();
                $meta_query[] = array(
                    'key'     => '_vortex_created_with_huraii',
                    'value'   => '1',
                    'compare' => '='
                );
                $query->set( 'meta_query', $meta_query );
            }
        }
        
        return $query;
    }

    /**
     * AJAX handler for getting artworks.
     * 
     * Handles AJAX requests to get artworks with filters.
     *
     * @since    1.0.0
     */
    public function ajax_get_artworks() {
        // Check nonce for security
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vortex_artwork_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'vortex-ai-marketplace' ) ) );
            exit;
        }
        
        // Set up query args
        $args = array(
            'post_type'      => $this->post_type,
            'posts_per_page' => isset( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : 12,
            'paged'          => isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1,
            'post_status'    => 'publish',
        );
        
        // Handle search
        if ( isset( $_POST['search'] ) && ! empty( $_POST['search'] ) ) {
            $args['s'] = sanitize_text_field( $_POST['search'] );
        }
        
        // Handle categories
        if ( isset( $_POST['categories'] ) && ! empty( $_POST['categories'] ) ) {
            $categories = is_array( $_POST['categories'] ) ? array_map( 'intval', $_POST['categories'] ) : array( intval( $_POST['categories'] ) );
            
            $args['tax_query'][] = array(
                'taxonomy' => 'vortex_artwork_category',
                'field'    => 'term_id',
                'terms'    => $categories,
            );
        }
        
        // Handle tags
        if ( isset( $_POST['tags'] ) && ! empty( $_POST['tags'] ) ) {
            $tags = is_array( $_POST['tags'] ) ? array_map( 'intval', $_POST['tags'] ) : array( intval( $_POST['tags'] ) );
            
            $args['tax_query'][] = array(
                'taxonomy' => 'vortex_artwork_tag',
                'field'    => 'term_id',
                'terms'    => $tags,
            );
        }
        
        // Handle sorting
        if ( isset( $_POST['orderby'] ) ) {
            $orderby = sanitize_text_field( $_POST['orderby'] );
            $order = isset( $_POST['order'] ) ? sanitize_text_field( $_POST['order'] ) : 'DESC';
            
            switch ( $orderby ) {
                case 'price_low':
                    $args['meta_key'] = '_vortex_artwork_price';
                    $args['orderby']  = 'meta_value_num';
                    $args['order']    = 'ASC';
                    break;
                
                case 'price_high':
                    $args['meta_key'] = '_vortex_artwork_price';
                    $args['orderby']  = 'meta_value_num';
                    $args['order']    = 'DESC';
                    break;
                
                case 'views':
                    $args['meta_key'] = '_vortex_artwork_view_count';
                    $args['orderby']  = 'meta_value_num';
                    $args['order']    = 'DESC';
                    break;
                
                case 'title':
                    $args['orderby'] = 'title';
                    $args['order']   = $order;
                    break;
                
                case 'date':
                default:
                    $args['orderby'] = 'date';
                    $args['order']   = $order;
                    break;
            }
        }
        
        // Handle featured filter
        if ( isset( $_POST['featured'] ) && $_POST['featured'] === 'true' ) {
            $args['meta_query'][] = array(
                'key'     => '_vortex_artwork_is_featured',
                'value'   => '1',
                'compare' => '=',
            );
        }
        
        // Handle AI-generated filter
        if ( isset( $_POST['ai_generated'] ) && $_POST['ai_generated'] === 'true' ) {
            $args['meta_query'][] = array(
                'key'     => '_vortex_created_with_huraii',
                'value'   => '1',
                'compare' => '=',
            );
        }
        
        // Handle price range filter
        if ( isset( $_POST['min_price'] ) || isset( $_POST['max_price'] ) ) {
            $min_price = isset( $_POST['min_price'] ) ? floatval( $_POST['min_price'] ) : 0;
            $max_price = isset( $_POST['max_price'] ) ? floatval( $_POST['max_price'] ) : 9999999;
            
            $args['meta_query'][] = array(
                'key'     => '_vortex_artwork_price',
                'value'   => array( $min_price, $max_price ),
                'type'    => 'NUMERIC',
                'compare' => 'BETWEEN',
            );
        }
        
        // Get artworks
        $query = new WP_Query( $args );
        $artworks = array();
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $artwork_id = get_the_ID();
                
                // Get artwork data
                $artwork = array(
                    'id'          => $artwork_id,
                    'title'       => get_the_title(),
                    'permalink'   => get_permalink(),
                    'thumbnail'   => get_the_post_thumbnail_url( $artwork_id, 'medium' ),
                    'price'       => get_post_meta( $artwork_id, '_vortex_artwork_price', true ),
                    'tola_price'  => get_post_meta( $artwork_id, '_vortex_tola_price', true ),
                    'artist'      => get_the_author(),
                    'date'        => get_the_date(),
                    'is_featured' => (bool) get_post_meta( $artwork_id, '_vortex_artwork_is_featured', true ),
                    'is_sold_out' => (bool) get_post_meta( $artwork_id, '_vortex_artwork_is_sold_out', true ),
                    'excerpt'     => get_the_excerpt(),
                );
                
                $artworks[] = $artwork;
            }
            
            wp_reset_postdata();
        }
        
        // Send response
        wp_send_json_success( array(
            'artworks'    => $artworks,
            'total_posts' => $query->found_posts,
            'max_pages'   => $query->max_num_pages,
        ) );
    }
    
    /**
     * AJAX handler for getting specific artwork data.
     * 
     * Handles AJAX requests to get detailed data for a specific artwork.
     *
     * @since    1.0.0
     */
    public function ajax_get_artwork_data() {
        // Check nonce for security
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vortex_artwork_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'vortex-ai-marketplace' ) ) );
            exit;
        }
        
        // Check for artwork ID
        if ( ! isset( $_POST['artwork_id'] ) || empty( $_POST['artwork_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Artwork ID is required.', 'vortex-ai-marketplace' ) ) );
            exit;
        }
        
        $artwork_id = intval( $_POST['artwork_id'] );
        
        // Check if artwork exists
        $artwork = get_post( $artwork_id );
        
        if ( ! $artwork || $artwork->post_type !== $this->post_type || $artwork->post_status !== 'publish' ) {
            wp_send_json_error( array( 'message' => __( 'Artwork not found.', 'vortex-ai-marketplace' ) ) );
            exit;
        }
        
        // Get artwork meta
        $price = get_post_meta( $artwork_id, '_vortex_artwork_price', true );
        $tola_price = get_post_meta( $artwork_id, '_vortex_tola_price', true );
        $edition_size = get_post_meta( $artwork_id, '_vortex_artwork_edition_size', true );
        $dimensions = get_post_meta( $artwork_id, '_vortex_artwork_dimensions', true );
        $medium = get_post_meta( $artwork_id, '_vortex_artwork_medium', true );
        $is_featured = (bool) get_post_meta( $artwork_id, '_vortex_artwork_is_featured', true );
        $is_sold_out = (bool) get_post_meta( $artwork_id, '_vortex_artwork_is_sold_out', true );
        $is_limited_edition = (bool) get_post_meta( $artwork_id, '_vortex_artwork_is_limited_edition', true );
        
        // Get AI details
        $created_with_huraii = (bool) get_post_meta( $artwork_id, '_vortex_created_with_huraii', true );
        $ai_prompt = get_post_meta( $artwork_id, '_vortex_artwork_ai_prompt', true );
        $ai_negative_prompt = get_post_meta( $artwork_id, '_vortex_artwork_ai_negative_prompt', true );
        $ai_model = get_post_meta( $artwork_id, '_vortex_artwork_ai_model', true );
        $ai_seed = get_post_meta( $artwork_id, '_vortex_artwork_ai_seed', true );
        $ai_guidance_scale = get_post_meta( $artwork_id, '_vortex_artwork_ai_guidance_scale', true );
        $ai_steps = get_post_meta( $artwork_id, '_vortex_artwork_ai_steps', true );
        
        // Get blockchain details
        $token_id = get_post_meta( $artwork_id, '_vortex_blockchain_token_id', true );
        $contract_address = get_post_meta( $artwork_id, '_vortex_blockchain_contract_address', true );
        $blockchain_name = get_post_meta( $artwork_id, '_vortex_blockchain_name', true );
        $is_minted = (bool) get_post_meta( $artwork_id, '_vortex_is_minted', true );
        
        // Get categories and tags
        $categories = array();
        $category_terms = get_the_terms( $artwork_id, 'vortex_artwork_category' );
        
        if ( $category_terms && ! is_wp_error( $category_terms ) ) {
            foreach ( $category_terms as $term ) {
                $categories[] = array(
                    'id'   => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'link' => get_term_link( $term ),
                );
            }
        }
        
        $tags = array();
        $tag_terms = get_the_terms( $artwork_id, 'vortex_artwork_tag' );
        
        if ( $tag_terms && ! is_wp_error( $tag_terms ) ) {
            foreach ( $tag_terms as $term ) {
                $tags[] = array(
                    'id'   => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'link' => get_term_link( $term ),
                );
            }
        }
        
        // Prepare artwork data
        $artwork_data = array(
            'id'               => $artwork_id,
            'title'            => $artwork->post_title,
            'content'          => apply_filters( 'the_content', $artwork->post_content ),
            'permalink'        => get_permalink( $artwork_id ),
            'thumbnail'        => get_the_post_thumbnail_url( $artwork_id, 'medium' ),
            'full_image'       => get_the_post_thumbnail_url( $artwork_id, 'full' ),
            'price'            => $price,
            'tola_price'       => $tola_price,
            'edition_size'     => $edition_size,
            'dimensions'       => $dimensions,
            'medium'           => $medium,
            'is_featured'      => $is_featured,
            'is_sold_out'      => $is_sold_out,
            'is_limited_edition' => $is_limited_edition,
            'artist'           => array(
                'id'    => $artwork->post_author,
                'name'  => get_the_author_meta( 'display_name', $artwork->post_author ),
                'url'   => get_author_posts_url( $artwork->post_author ),
                'avatar' => get_avatar_url( $artwork->post_author ),
            ),
            'date'             => array(
                'published'     => get_the_date( 'c', $artwork_id ),
                'modified'      => get_the_modified_date( 'c', $artwork_id ),
                'human_readable' => get_the_date( '', $artwork_id ),
            ),
            'ai_details'       => array(
                'created_with_huraii' => $created_with_huraii,
                'ai_prompt'           => $ai_prompt,
                'ai_negative_prompt'  => $ai_negative_prompt,
                'ai_model'            => $ai_model,
                'ai_seed'             => $ai_seed,
                'ai_guidance_scale'   => $ai_guidance_scale,
                'ai_steps'            => $ai_steps,
            ),
            'blockchain'       => array(
                'is_minted'        => $is_minted,
                'token_id'         => $token_id,
                'contract_address' => $contract_address,
                'blockchain_name'  => $blockchain_name,
            ),
            'categories'       => $categories,
            'tags'             => $tags,
        );
        
        // Track artwork view
        $this->track_artwork_view( $artwork_id );
        
        // Send response
        wp_send_json_success( array( 'artwork' => $artwork_data ) );
    }
    
    /**
     * AJAX handler for updating artwork views.
     * 
     * Handles AJAX requests to increment view count for an artwork.
     *
     * @since    1.0.0
     */
    public function ajax_update_artwork_views() {
        // Check for artwork ID
        if ( ! isset( $_POST['artwork_id'] ) || empty( $_POST['artwork_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Artwork ID is required.', 'vortex-ai-marketplace' ) ) );
            exit;
        }
        
        $artwork_id = intval( $_POST['artwork_id'] );
        
        // Check if artwork exists
        $artwork = get_post( $artwork_id );
        
        if ( ! $artwork || $artwork->post_type !== $this->post_type ) {
            wp_send_json_error( array( 'message' => __( 'Artwork not found.', 'vortex-ai-marketplace' ) ) );
            exit;
        }
        
        // Track artwork view
        $this->track_artwork_view( $artwork_id );
        
        // Send response
        wp_send_json_success( array( 'message' => __( 'View count updated.', 'vortex-ai-marketplace' ) ) );
    }
    
    /**
     * Track artwork view.
     * 
     * Increments the view count for an artwork.
     *
     * @since    1.0.0
     * @param    int    $artwork_id    The artwork ID.
     */
    private function track_artwork_view( $artwork_id ) {
        // Get current view count
        $view_count = get_post_meta( $artwork_id, '_vortex_artwork_view_count', true );
        $view_count = empty( $view_count ) ? 0 : intval( $view_count );
        
        // Increment view count
        $view_count++;
        
        // Update view count
        update_post_meta( $artwork_id, '_vortex_artwork_view_count', $view_count );
        
        // Allow for additional tracking
        do_action( 'vortex_artwork_viewed', $artwork_id, $view_count );
    }

    /**
     * Set custom columns for admin list view.
     *
     * @since    1.0.0
     * @param    array    $columns    Default columns.
     * @return   array                Modified columns.
     */
    public function set_custom_columns( $columns ) {
        $new_columns = array();
        
        // Add checkbox column first
        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
        }
        
        // Add thumbnail column
        $new_columns['artwork_thumbnail'] = __( 'Thumbnail', 'vortex-ai-marketplace' );
        
        // Add title column
        if ( isset( $columns['title'] ) ) {
            $new_columns['title'] = $columns['title'];
        }
        
        // Add other custom columns
        $new_columns['artist'] = __( 'Artist', 'vortex-ai-marketplace' );
        $new_columns['price'] = __( 'Price', 'vortex-ai-marketplace' );
        $new_columns['edition_size'] = __( 'Edition Size', 'vortex-ai-marketplace' );
        $new_columns['featured'] = __( 'Featured', 'vortex-ai-marketplace' );
        $new_columns['views'] = __( 'Views', 'vortex-ai-marketplace' );
        $new_columns['ai_generated'] = __( 'AI Generated', 'vortex-ai-marketplace' );
        
        // Add taxonomy columns
        if ( isset( $columns['taxonomy-vortex_artwork_category'] ) ) {
            $new_columns['taxonomy-vortex_artwork_category'] = $columns['taxonomy-vortex_artwork_category'];
        } else {
            $new_columns['taxonomy-vortex_artwork_category'] = __( 'Categories', 'vortex-ai-marketplace' );
        }
        
        if ( isset( $columns['taxonomy-vortex_artwork_tag'] ) ) {
            $new_columns['taxonomy-vortex_artwork_tag'] = $columns['taxonomy-vortex_artwork_tag'];
        } else {
            $new_columns['taxonomy-vortex_artwork_tag'] = __( 'Tags', 'vortex-ai-marketplace' );
        }
        
        // Add date column
        if ( isset( $columns['date'] ) ) {
            $new_columns['date'] = $columns['date'];
        }
        
        return $new_columns;
    }
    
    /**
     * Custom column content for admin list view.
     *
     * @since    1.0.0
     * @param    string    $column     Column name.
     * @param    int       $post_id    Post ID.
     */
    public function custom_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'artwork_thumbnail':
                if ( has_post_thumbnail( $post_id ) ) {
                    echo '<a href="' . esc_url( get_edit_post_link( $post_id ) ) . '">';
                    echo get_the_post_thumbnail( $post_id, array( 50, 50 ) );
                    echo '</a>';
                } else {
                    echo '<div style="width:50px;height:50px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;">';
                    echo '<span class="dashicons dashicons-format-image"></span>';
                    echo '</div>';
                }
                break;
                
            case 'artist':
                $author_id = get_post_field( 'post_author', $post_id );
                $author_name = get_the_author_meta( 'display_name', $author_id );
                echo '<a href="' . esc_url( add_query_arg( array( 'post_type' => $this->post_type, 'author' => $author_id ), admin_url( 'edit.php' ) ) ) . '">' . esc_html( $author_name ) . '</a>';
                break;
                
            case 'price':
                $price = get_post_meta( $post_id, '_vortex_artwork_price', true );
                if ( ! empty( $price ) ) {
                    $currency_symbol = get_option( 'vortex_marketplace_currency_symbol', '$' );
                    echo '<span class="vortex-price-display">' . esc_html( $currency_symbol . number_format( (float) $price, 2 ) ) . '</span>';
                } else {
                    echo '—';
                }
                break;
                
            case 'edition_size':
                $is_limited_edition = get_post_meta( $post_id, '_vortex_artwork_is_limited_edition', true );
                
                if ( $is_limited_edition ) {
                    $edition_size = get_post_meta( $post_id, '_vortex_artwork_edition_size', true );
                    if ( ! empty( $edition_size ) ) {
                        echo esc_html( $edition_size );
                    } else {
                        echo '—';
                    }
                } else {
                    echo '<span title="' . esc_attr__( 'Unlimited edition', 'vortex-ai-marketplace' ) . '">∞</span>';
                }
                break;
                
            case 'featured':
                $is_featured = get_post_meta( $post_id, '_vortex_artwork_is_featured', true );
                
                if ( $is_featured ) {
                    echo '<span class="vortex-featured-icon dashicons dashicons-star-filled" style="color:#f1c40f;" title="' . esc_attr__( 'Featured', 'vortex-ai-marketplace' ) . '"></span>';
                } else {
                    echo '<span class="dashicons dashicons-star-empty" title="' . esc_attr__( 'Not featured', 'vortex-ai-marketplace' ) . '"></span>';
                }
                break;
                
            case 'views':
                $view_count = get_post_meta( $post_id, '_vortex_artwork_view_count', true );
                echo ! empty( $view_count ) ? esc_html( number_format( intval( $view_count ) ) ) : '0';
                break;
                
            case 'ai_generated':
                $created_with_huraii = get_post_meta( $post_id, '_vortex_created_with_huraii', true );
                
                if ( $created_with_huraii ) {
                    $ai_model = get_post_meta( $post_id, '_vortex_artwork_ai_model', true );
                    echo '<span class="dashicons dashicons-yes" style="color:#2ecc71;" title="' . esc_attr__( 'AI Generated', 'vortex-ai-marketplace' ) . '"></span>';
                    
                    if ( ! empty( $ai_model ) ) {
                        echo '<div class="vortex-ai-model-badge">' . esc_html( $ai_model ) . '</div>';
                    }
                } else {
                    echo '<span class="dashicons dashicons-no" title="' . esc_attr__( 'Not AI Generated', 'vortex-ai-marketplace' ) . '"></span>';
                }
                break;
        }
    }
    
    /**
     * Set sortable columns for admin list view.
     *
     * @since    1.0.0
     * @param    array    $columns    Default sortable columns.
     * @return   array                Modified sortable columns.
     */
    public function set_sortable_columns( $columns ) {
        $columns['price'] = 'price';
        $columns['views'] = 'views';
        $columns['featured'] = '_vortex_artwork_is_featured';
        $columns['artist'] = 'author';
        
        return $columns;
    }
} 