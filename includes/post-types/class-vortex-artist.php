<?php
/**
 * The artist post type functionality.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/post-types
 */

/**
 * The artist post type functionality.
 *
 * Defines the artist post type and its features.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/post-types
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Artist {

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
        $this->post_type = 'vortex_artist';

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
        add_action( 'pre_get_posts', array( $this, 'modify_query' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
        add_action( 'admin_head', array( $this, 'add_admin_styles' ) );
        
        // Artist-specific hooks
        add_action( 'wp_ajax_vortex_get_artists', array( $this, 'ajax_get_artists' ) );
        add_action( 'wp_ajax_nopriv_vortex_get_artists', array( $this, 'ajax_get_artists' ) );
        add_action( 'wp_ajax_vortex_get_artist_data', array( $this, 'ajax_get_artist_data' ) );
        add_action( 'wp_ajax_nopriv_vortex_get_artist_data', array( $this, 'ajax_get_artist_data' ) );
        add_action( 'wp_ajax_vortex_verify_artist', array( $this, 'ajax_verify_artist' ) );
        add_action( 'wp_ajax_vortex_feature_artist', array( $this, 'ajax_feature_artist' ) );
        add_action( 'wp_ajax_vortex_update_artist_views', array( $this, 'ajax_update_artist_views' ) );
        add_action( 'wp_ajax_nopriv_vortex_update_artist_views', array( $this, 'ajax_update_artist_views' ) );
        
        // Integration with user profile
        add_action( 'show_user_profile', array( $this, 'add_artist_profile_fields' ) );
        add_action( 'edit_user_profile', array( $this, 'add_artist_profile_fields' ) );
        add_action( 'personal_options_update', array( $this, 'save_artist_profile_fields' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_artist_profile_fields' ) );
        
        // User registration hook
        add_action( 'user_register', array( $this, 'create_artist_profile_on_registration' ) );
    }

    /**
     * Register the custom post type.
     *
     * @since    1.0.0
     */
    public function register_post_type() {
        $labels = array(
            'name'               => _x( 'Artists', 'post type general name', 'vortex-ai-marketplace' ),
            'singular_name'      => _x( 'Artist', 'post type singular name', 'vortex-ai-marketplace' ),
            'menu_name'          => _x( 'Artists', 'admin menu', 'vortex-ai-marketplace' ),
            'name_admin_bar'     => _x( 'Artist', 'add new on admin bar', 'vortex-ai-marketplace' ),
            'add_new'            => _x( 'Add New', 'artist', 'vortex-ai-marketplace' ),
            'add_new_item'       => __( 'Add New Artist', 'vortex-ai-marketplace' ),
            'new_item'           => __( 'New Artist', 'vortex-ai-marketplace' ),
            'edit_item'          => __( 'Edit Artist', 'vortex-ai-marketplace' ),
            'view_item'          => __( 'View Artist', 'vortex-ai-marketplace' ),
            'all_items'          => __( 'All Artists', 'vortex-ai-marketplace' ),
            'search_items'       => __( 'Search Artists', 'vortex-ai-marketplace' ),
            'parent_item_colon'  => __( 'Parent Artists:', 'vortex-ai-marketplace' ),
            'not_found'          => __( 'No artists found.', 'vortex-ai-marketplace' ),
            'not_found_in_trash' => __( 'No artists found in Trash.', 'vortex-ai-marketplace' ),
        );

        $capabilities = array(
            'edit_post'              => 'edit_vortex_artist',
            'read_post'              => 'read_vortex_artist',
            'delete_post'            => 'delete_vortex_artist',
            'edit_posts'             => 'edit_vortex_artists',
            'edit_others_posts'      => 'edit_others_vortex_artists',
            'publish_posts'          => 'publish_vortex_artists',
            'read_private_posts'     => 'read_private_vortex_artists',
            'delete_posts'           => 'delete_vortex_artists',
            'delete_private_posts'   => 'delete_private_vortex_artists',
            'delete_published_posts' => 'delete_published_vortex_artists',
            'delete_others_posts'    => 'delete_others_vortex_artists',
            'edit_private_posts'     => 'edit_private_vortex_artists',
            'edit_published_posts'   => 'edit_published_vortex_artists',
        );

        $args = array(
            'labels'               => $labels,
            'description'          => __( 'Artists for the VORTEX AI Marketplace', 'vortex-ai-marketplace' ),
            'public'               => true,
            'publicly_queryable'   => true,
            'show_ui'              => true,
            'show_in_menu'         => 'vortex_marketplace',
            'query_var'            => true,
            'rewrite'              => array( 'slug' => 'artist' ),
            'capability_type'      => array( 'vortex_artist', 'vortex_artists' ),
            'map_meta_cap'         => true,
            'capabilities'         => $capabilities,
            'has_archive'          => true,
            'hierarchical'         => false,
            'menu_position'        => null,
            'supports'             => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments', 'revisions' ),
            'menu_icon'            => 'dashicons-admin-users',
            'show_in_rest'         => true,
            'rest_base'            => 'artists',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );

        register_post_type( $this->post_type, $args );

        // Register meta fields for REST API
        register_post_meta( $this->post_type, '_vortex_artist_user_id', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'integer',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_artist_verified', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_artist_featured', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_artist_specialties', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_artist_wallet_address', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_artist_commission_rate', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'number',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_artist_social_links', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'object',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        // Add metadata for tracking views and stats
        register_post_meta( $this->post_type, '_vortex_artist_view_count', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'integer',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_artist_artwork_count', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'integer',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_artist_sales_count', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'integer',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_artist_total_revenue', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'number',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        register_post_meta( $this->post_type, '_vortex_artist_avg_rating', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'number',
            'auth_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));
    }

    /**
     * Add meta boxes for additional artist information.
     *
     * @since    1.0.0
     */
    public function add_meta_boxes() {
        add_meta_box(
            'vortex_artist_details',
            __( 'Artist Details', 'vortex-ai-marketplace' ),
            array( $this, 'render_details_meta_box' ),
            $this->post_type,
            'normal',
            'high'
        );

        add_meta_box(
            'vortex_artist_social',
            __( 'Social Media Links', 'vortex-ai-marketplace' ),
            array( $this, 'render_social_meta_box' ),
            $this->post_type,
            'normal',
            'default'
        );

        add_meta_box(
            'vortex_artist_verification',
            __( 'Verification Status', 'vortex-ai-marketplace' ),
            array( $this, 'render_verification_meta_box' ),
            $this->post_type,
            'side',
            'high'
        );

        add_meta_box(
            'vortex_artist_wallet',
            __( 'Wallet Information', 'vortex-ai-marketplace' ),
            array( $this, 'render_wallet_meta_box' ),
            $this->post_type,
            'side',
            'default'
        );

        add_meta_box(
            'vortex_artist_stats',
            __( 'Artist Statistics', 'vortex-ai-marketplace' ),
            array( $this, 'render_stats_meta_box' ),
            $this->post_type,
            'side',
            'default'
        );

        add_meta_box(
            'vortex_artist_artworks',
            __( 'Artist Artworks', 'vortex-ai-marketplace' ),
            array( $this, 'render_artworks_meta_box' ),
            $this->post_type,
            'normal',
            'default'
        );
    }

    /**
     * Render the artist details meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_details_meta_box( $post ) {
        // Add nonce field for security
        wp_nonce_field( 'vortex_artist_details_nonce', 'vortex_artist_details_nonce' );

        // Get current values
        $user_id = get_post_meta( $post->ID, '_vortex_artist_user_id', true );
        $specialties = get_post_meta( $post->ID, '_vortex_artist_specialties', true );
        $commission_rate = get_post_meta( $post->ID, '_vortex_artist_commission_rate', true );
        $featured = get_post_meta( $post->ID, '_vortex_artist_featured', true );

        // Get all users for dropdown
        $users = get_users( array(
            'role__in' => array( 'administrator', 'author', 'contributor', 'subscriber' ),
            'orderby' => 'display_name',
        ) );

        // Output the fields
        ?>
        <div class="vortex-meta-field">
            <label for="vortex_artist_user_id">
                <?php esc_html_e( 'Associated User', 'vortex-ai-marketplace' ); ?>:
            </label>
            <select id="vortex_artist_user_id" name="vortex_artist_user_id">
                <option value=""><?php esc_html_e( 'Select a user', 'vortex-ai-marketplace' ); ?></option>
                <?php foreach ( $users as $user ) : ?>
                    <option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $user_id, $user->ID ); ?>>
                        <?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e( 'Select the WordPress user associated with this artist.', 'vortex-ai-marketplace' ); ?></p>
        </div>

        <div class="vortex-meta-field">
            <label for="vortex_artist_specialties">
                <?php esc_html_e( 'Specialties', 'vortex-ai-marketplace' ); ?>:
            </label>
            <input type="text" id="vortex_artist_specialties" name="vortex_artist_specialties" 
                   value="<?php echo esc_attr( $specialties ); ?>" class="regular-text" 
                   placeholder="<?php esc_attr_e( 'e.g., Digital Art, AI Art, Photography', 'vortex-ai-marketplace' ); ?>">
            <p class="description"><?php esc_html_e( 'Enter the artist\'s specialties, separated by commas.', 'vortex-ai-marketplace' ); ?></p>
        </div>

        <div class="vortex-meta-field">
            <label for="vortex_artist_commission_rate">
                <?php esc_html_e( 'Commission Rate (%)', 'vortex-ai-marketplace' ); ?>:
            </label>
            <input type="number" id="vortex_artist_commission_rate" name="vortex_artist_commission_rate" 
                   value="<?php echo esc_attr( $commission_rate ); ?>" class="small-text" 
                   min="0" max="100" step="0.01">
            <p class="description"><?php esc_html_e( 'Enter the commission rate for this artist (0-100%). Default marketplace commission will be used if left empty.', 'vortex-ai-marketplace' ); ?></p>
        </div>

        <div class="vortex-meta-field">
            <input type="checkbox" id="vortex_artist_featured" name="vortex_artist_featured" 
                   value="1" <?php checked( $featured, '1' ); ?>>
            <label for="vortex_artist_featured">
                <?php esc_html_e( 'Featured Artist', 'vortex-ai-marketplace' ); ?>
            </label>
            <p class="description"><?php esc_html_e( 'Featured artists will be highlighted on the marketplace.', 'vortex-ai-marketplace' ); ?></p>
        </div>
        <?php
    }

    /**
     * Render the social media links meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_social_meta_box( $post ) {
        // Add nonce field for security
        wp_nonce_field( 'vortex_artist_social_nonce', 'vortex_artist_social_nonce' );

        // Get current values
        $social_links = get_post_meta( $post->ID, '_vortex_artist_social_links', true );
        
        // Initialize social links if not set
        if ( empty( $social_links ) || ! is_array( $social_links ) ) {
            $social_links = array(
                'website' => '',
                'twitter' => '',
                'instagram' => '',
                'facebook' => '',
                'linkedin' => '',
                'behance' => '',
                'deviantart' => '',
                'youtube' => '',
            );
        }

        // Output the fields
        ?>
        <div class="vortex-social-fields">
            <div class="vortex-meta-field">
                <label for="vortex_artist_social_website">
                    <?php esc_html_e( 'Website', 'vortex-ai-marketplace' ); ?>:
                </label>
                <input type="url" id="vortex_artist_social_website" name="vortex_artist_social_links[website]" 
                       value="<?php echo esc_url( $social_links['website'] ); ?>" class="regular-text" 
                       placeholder="https://example.com">
            </div>

            <div class="vortex-meta-field">
                <label for="vortex_artist_social_twitter">
                    <?php esc_html_e( 'Twitter', 'vortex-ai-marketplace' ); ?>:
                </label>
                <input type="url" id="vortex_artist_social_twitter" name="vortex_artist_social_links[twitter]" 
                       value="<?php echo esc_url( $social_links['twitter'] ); ?>" class="regular-text" 
                       placeholder="https://twitter.com/username">
            </div>

            <div class="vortex-meta-field">
                <label for="vortex_artist_social_instagram">
                    <?php esc_html_e( 'Instagram', 'vortex-ai-marketplace' ); ?>:
                </label>
                <input type="url" id="vortex_artist_social_instagram" name="vortex_artist_social_links[instagram]" 
                       value="<?php echo esc_url( $social_links['instagram'] ); ?>" class="regular-text" 
                       placeholder="https://instagram.com/username">
            </div>

            <div class="vortex-meta-field">
                <label for="vortex_artist_social_facebook">
                    <?php esc_html_e( 'Facebook', 'vortex-ai-marketplace' ); ?>:
                </label>
                <input type="url" id="vortex_artist_social_facebook" name="vortex_artist_social_links[facebook]" 
                       value="<?php echo esc_url( $social_links['facebook'] ); ?>" class="regular-text" 
                       placeholder="https://facebook.com/username">
            </div>

            <div class="vortex-meta-field">
                <label for="vortex_artist_social_linkedin">
                    <?php esc_html_e( 'LinkedIn', 'vortex-ai-marketplace' ); ?>:
                </label>
                <input type="url" id="vortex_artist_social_linkedin" name="vortex_artist_social_links[linkedin]" 
                       value="<?php echo esc_url( $social_links['linkedin'] ); ?>" class="regular-text" 
                       placeholder="https://linkedin.com/in/username">
            </div>

            <div class="vortex-meta-field">
                <label for="vortex_artist_social_behance">
                    <?php esc_html_e( 'Behance', 'vortex-ai-marketplace' ); ?>:
                </label>
                <input type="url" id="vortex_artist_social_behance" name="vortex_artist_social_links[behance]" 
                       value="<?php echo esc_url( $social_links['behance'] ); ?>" class="regular-text" 
                       placeholder="https://behance.net/username">
            </div>

            <div class="vortex-meta-field">
                <label for="vortex_artist_social_deviantart">
                    <?php esc_html_e( 'DeviantArt', 'vortex-ai-marketplace' ); ?>:
                </label>
                <input type="url" id="vortex_artist_social_deviantart" name="vortex_artist_social_links[deviantart]" 
                       value="<?php echo esc_url( $social_links['deviantart'] ); ?>" class="regular-text" 
                       placeholder="https://username.deviantart.com">
            </div>

            <div class="vortex-meta-field">
                <label for="vortex_artist_social_youtube">
                    <?php esc_html_e( 'YouTube', 'vortex-ai-marketplace' ); ?>:
                </label>
                <input type="url" id="vortex_artist_social_youtube" name="vortex_artist_social_links[youtube]" 
                       value="<?php echo esc_url( $social_links['youtube'] ); ?>" class="regular-text" 
                       placeholder="https://youtube.com/channel/username">
            </div>
        </div>
        <p class="description"><?php esc_html_e( 'Enter the artist\'s social media profiles. Leave blank if not applicable.', 'vortex-ai-marketplace' ); ?></p>
        <?php
    }

    /**
     * Render the verification status meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_verification_meta_box( $post ) {
        // Add nonce field for security
        wp_nonce_field( 'vortex_artist_verification_nonce', 'vortex_artist_verification_nonce' );

        // Get current values
        $verified = get_post_meta( $post->ID, '_vortex_artist_verified', true );
        $verification_date = get_post_meta( $post->ID, '_vortex_artist_verification_date', true );
        $verified_by = get_post_meta( $post->ID, '_vortex_artist_verified_by', true );

        // Output the fields
        ?>
        <div class="vortex-meta-field">
            <input type="checkbox" id="vortex_artist_verified" name="vortex_artist_verified" 
                   value="1" <?php checked( $verified, '1' ); ?>>
            <label for="vortex_artist_verified">
                <?php esc_html_e( 'Verified Artist', 'vortex-ai-marketplace' ); ?>
            </label>
        </div>

        <?php if ( $verified && $verification_date ) : ?>
            <div class="vortex-verification-details">
                <p>
                    <?php 
                    printf(
                        esc_html__( 'Verified on: %s', 'vortex-ai-marketplace' ),
                        esc_html( date_i18n( get_option( 'date_format' ), strtotime( $verification_date ) ) )
                    );
                    ?>
                </p>
                <?php if ( $verified_by ) : 
                    $user = get_user_by( 'id', $verified_by );
                    if ( $user ) : ?>
                        <p>
                            <?php 
                            printf(
                                esc_html__( 'Verified by: %s', 'vortex-ai-marketplace' ),
                                esc_html( $user->display_name )
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <p class="description"><?php esc_html_e( 'Verified artists receive a verification badge on their profile and listings.', 'vortex-ai-marketplace' ); ?></p>
        <?php
    }

    /**
     * Render the wallet information meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_wallet_meta_box( $post ) {
        // Add nonce field for security
        wp_nonce_field( 'vortex_artist_wallet_nonce', 'vortex_artist_wallet_nonce' );

        // Get current values
        $wallet_address = get_post_meta( $post->ID, '_vortex_artist_wallet_address', true );
        $blockchain = get_post_meta( $post->ID, '_vortex_artist_blockchain', true );
        if ( empty( $blockchain ) ) {
            $blockchain = 'solana'; // Default blockchain
        }

        // Output the fields
        ?>
        <div class="vortex-meta-field">
            <label for="vortex_artist_blockchain">
                <?php esc_html_e( 'Blockchain', 'vortex-ai-marketplace' ); ?>:
            </label>
            <select id="vortex_artist_blockchain" name="vortex_artist_blockchain">
                <option value="solana" <?php selected( $blockchain, 'solana' ); ?>><?php esc_html_e( 'Solana', 'vortex-ai-marketplace' ); ?></option>
                <option value="ethereum" <?php selected( $blockchain, 'ethereum' ); ?>><?php esc_html_e( 'Ethereum', 'vortex-ai-marketplace' ); ?></option>
                <option value="polygon" <?php selected( $blockchain, 'polygon' ); ?>><?php esc_html_e( 'Polygon', 'vortex-ai-marketplace' ); ?></option>
            </select>
            <p class="description"><?php esc_html_e( 'Select the blockchain for this artist\'s wallet.', 'vortex-ai-marketplace' ); ?></p>
        </div>

        <div class="vortex-meta-field">
            <label for="vortex_artist_wallet_address">
                <?php esc_html_e( 'Wallet Address', 'vortex-ai-marketplace' ); ?>:
            </label>
            <input type="text" id="vortex_artist_wallet_address" name="vortex_artist_wallet_address" 
                   value="<?php echo esc_attr( $wallet_address ); ?>" class="widefat">
            <p class="description"><?php esc_html_e( 'Enter the artist\'s cryptocurrency wallet address for payments.', 'vortex-ai-marketplace' ); ?></p>
        </div>

        <?php if ( ! empty( $wallet_address ) ) : ?>
            <div class="vortex-wallet-actions">
                <button type="button" class="button vortex-verify-wallet" data-artist-id="<?php echo esc_attr( $post->ID ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'vortex_verify_wallet_nonce' ) ); ?>">
                    <?php esc_html_e( 'Verify Wallet', 'vortex-ai-marketplace' ); ?>
                </button>
            </div>
            <div id="vortex-wallet-verification-results"></div>
        <?php endif; ?>
        <?php
    }

    /**
     * Render the artist statistics meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_stats_meta_box( $post ) {
        // Get current values
        $view_count = get_post_meta( $post->ID, '_vortex_artist_view_count', true );
        $view_count = empty( $view_count ) ? 0 : intval( $view_count );
        
        $artwork_count = get_post_meta( $post->ID, '_vortex_artist_artwork_count', true );
        if ( empty( $artwork_count ) ) {
            // Count artworks dynamically
            $user_id = get_post_meta( $post->ID, '_vortex_artist_user_id', true );
            if ( $user_id ) {
                $artwork_count = count_user_posts( $user_id, 'vortex_artwork' );
                update_post_meta( $post->ID, '_vortex_artist_artwork_count', $artwork_count );
            } else {
                $artwork_count = 0;
            }
        }
        
        $sales_count = get_post_meta( $post->ID, '_vortex_artist_sales_count', true );
        $sales_count = empty( $sales_count ) ? 0 : intval( $sales_count );
        
        $total_revenue = get_post_meta( $post->ID, '_vortex_artist_total_revenue', true );
        $total_revenue = empty( $total_revenue ) ? 0 : floatval( $total_revenue );
        
        $avg_rating = get_post_meta( $post->ID, '_vortex_artist_avg_rating', true );
        $avg_rating = empty( $avg_rating ) ? 0 : floatval( $avg_rating );
        
        // Get marketplace currency symbol
        $currency_symbol = get_option( 'vortex_marketplace_currency_symbol', '$' );
        
        // Display stats
        ?>
        <div class="vortex-stats-container">
            <div class="vortex-stat-item">
                <span class="vortex-stat-label"><?php esc_html_e( 'Profile Views', 'vortex-ai-marketplace' ); ?>:</span>
                <span class="vortex-stat-value"><?php echo esc_html( number_format( $view_count ) ); ?></span>
            </div>
            
            <div class="vortex-stat-item">
                <span class="vortex-stat-label"><?php esc_html_e( 'Artworks', 'vortex-ai-marketplace' ); ?>:</span>
                <span class="vortex-stat-value"><?php echo esc_html( number_format( $artwork_count ) ); ?></span>
            </div>
            
            <div class="vortex-stat-item">
                <span class="vortex-stat-label"><?php esc_html_e( 'Sales', 'vortex-ai-marketplace' ); ?>:</span>
                <span class="vortex-stat-value"><?php echo esc_html( number_format( $sales_count ) ); ?></span>
            </div>
            
            <div class="vortex-stat-item">
                <span class="vortex-stat-label"><?php esc_html_e( 'Total Revenue', 'vortex-ai-marketplace' ); ?>:</span>
                <span class="vortex-stat-value"><?php echo esc_html( $currency_symbol . number_format( $total_revenue, 2 ) ); ?></span>
            </div>
            
            <div class="vortex-stat-item">
                <span class="vortex-stat-label"><?php esc_html_e( 'Average Rating', 'vortex-ai-marketplace' ); ?>:</span>
                <span class="vortex-stat-value">
                    <?php echo esc_html( number_format( $avg_rating, 1 ) ); ?> / 5.0
                    <div class="vortex-star-rating">
                        <?php
                        for ( $i = 1; $i <= 5; $i++ ) {
                            if ( $i <= $avg_rating ) {
                                echo '<span class="dashicons dashicons-star-filled"></span>';
                            } elseif ( $i - 0.5 <= $avg_rating ) {
                                echo '<span class="dashicons dashicons-star-half"></span>';
                            } else {
                                echo '<span class="dashicons dashicons-star-empty"></span>';
                            }
                        }
                        ?>
                    </div>
                </span>
            </div>
        </div>
        <?php
    }
} 