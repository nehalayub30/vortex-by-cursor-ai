<?php
/**
 * The artist management functionality of the plugin.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The artist management functionality of the plugin.
 *
 * Handles artist profiles, registration, verification, and portfolio
 * management for the marketplace.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Artists {

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

    private $ai_manager;
    private $db;
    private $cache_group = 'vortex_artists';
    private $cache_expiry = 3600; // 1 hour

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
        
        // Initialize blockchain integration if class exists
        if ( class_exists( 'Vortex_Blockchain_Integration' ) ) {
            $this->blockchain = new Vortex_Blockchain_Integration( $plugin_name, $version );
        }
        
        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Register all artist related hooks.
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_hooks() {
        // Artist profile management
        add_action( 'init', array( $this, 'register_artist_role' ) );
        add_action( 'init', array( $this, 'add_artist_capabilities' ) );
        
        // Artist registration and profile hooks
        add_action( 'user_register', array( $this, 'create_artist_profile_on_registration' ), 10, 1 );
        add_action( 'profile_update', array( $this, 'sync_artist_profile_with_user' ), 10, 2 );
        add_action( 'delete_user', array( $this, 'handle_artist_deletion' ) );
        
        // Artist verification
        add_action( 'admin_init', array( $this, 'handle_artist_verification_actions' ) );
        
        // Profile fields and customization
        add_action( 'show_user_profile', array( $this, 'add_artist_profile_fields' ) );
        add_action( 'edit_user_profile', array( $this, 'add_artist_profile_fields' ) );
        add_action( 'personal_options_update', array( $this, 'save_artist_profile_fields' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_artist_profile_fields' ) );
        
        // Portfolio management
        add_action( 'wp_ajax_vortex_get_artist_portfolio', array( $this, 'ajax_get_artist_portfolio' ) );
        add_action( 'wp_ajax_nopriv_vortex_get_artist_portfolio', array( $this, 'ajax_get_artist_portfolio' ) );
        add_action( 'wp_ajax_vortex_update_portfolio_order', array( $this, 'ajax_update_portfolio_order' ) );
        
        // Wallet integration
        add_action( 'wp_ajax_vortex_connect_artist_wallet', array( $this, 'ajax_connect_artist_wallet' ) );
        add_action( 'wp_ajax_vortex_disconnect_artist_wallet', array( $this, 'ajax_disconnect_artist_wallet' ) );
        
        // Shortcodes
        add_shortcode( 'vortex_artist_registration', array( $this, 'artist_registration_shortcode' ) );
        add_shortcode( 'vortex_artist_portfolio', array( $this, 'artist_portfolio_shortcode' ) );
        add_shortcode( 'vortex_artist_statistics', array( $this, 'artist_statistics_shortcode' ) );
        
        // Filters for template integration
        add_filter( 'template_include', array( $this, 'artist_template_override' ) );
        
        // Artist dashboard
        add_action( 'wp_ajax_vortex_get_artist_dashboard_data', array( $this, 'ajax_get_artist_dashboard_data' ) );

        // New hooks for AI integration
        add_action('vortex_daily_artist_analysis', array($this, 'analyze_artist_performance'));
        add_action('save_post_vortex_artwork', array($this, 'process_artwork_submission'), 10, 3);
        add_filter('vortex_artist_recommendations', array($this, 'get_ai_recommendations'), 10, 2);
    }

    /**
     * Register the Artist user role.
     *
     * @since    1.0.0
     */
    public function register_artist_role() {
        // Check if the role already exists
        if ( ! get_role( 'vortex_artist' ) ) {
            // Create a new role with subscriber capabilities as a base
            add_role(
                'vortex_artist',
                __( 'VORTEX Artist', 'vortex-ai-marketplace' ),
                array(
                    'read' => true,
                    'upload_files' => true,
                    'publish_posts' => true,
                    'edit_posts' => true,
                    'delete_posts' => true,
                )
            );
        }
    }

    /**
     * Add custom capabilities to the Artist role.
     *
     * @since    1.0.0
     */
    public function add_artist_capabilities() {
        $artist_role = get_role( 'vortex_artist' );
        
        if ( $artist_role ) {
            // Add specific capabilities for VORTEX artists
            $artist_role->add_cap( 'create_vortex_artworks' );
            $artist_role->add_cap( 'edit_vortex_artworks' );
            $artist_role->add_cap( 'sell_vortex_artworks' );
            $artist_role->add_cap( 'use_vortex_huraii' );
            
            // Allow administrators to manage artists and their content
            $admin_role = get_role( 'administrator' );
            if ( $admin_role ) {
                $admin_role->add_cap( 'manage_vortex_artists' );
                $admin_role->add_cap( 'verify_vortex_artists' );
                $admin_role->add_cap( 'edit_vortex_artworks' );
                $admin_role->add_cap( 'delete_vortex_artworks' );
            }
        }
    }

    /**
     * Create an artist profile post when a user registers as an artist.
     *
     * @since    1.0.0
     * @param    int    $user_id    The user ID.
     */
    public function create_artist_profile_on_registration( $user_id ) {
        // Check if this is an artist registration
        $is_artist_registration = isset( $_POST['vortex_register_as_artist'] ) && $_POST['vortex_register_as_artist'];
        
        if ( $is_artist_registration ) {
            // Set user role to artist
            $user = new WP_User( $user_id );
            $user->set_role( 'vortex_artist' );
            
            // Create artist profile post
            $this->create_or_update_artist_profile( $user_id );
        }
    }

    /**
     * Create or update an artist profile post.
     *
     * @since    1.0.0
     * @param    int    $user_id    The user ID.
     * @return   int                The artist profile post ID.
     */
    public function create_or_update_artist_profile( $user_id ) {
        // Get user data
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return 0;
        }
        
        // Check if profile already exists
        $existing_profile = $this->get_artist_profile_by_user_id( $user_id );
        
        // User display name or username for the profile title
        $display_name = $user->display_name ? $user->display_name : $user->user_login;
        
        // Prepare profile data
        $profile_data = array(
            'post_title'    => $display_name,
            'post_content'  => '', // Will be updated later with bio
            'post_status'   => 'publish',
            'post_author'   => $user_id,
            'post_type'     => 'vortex_artist',
        );
        
        if ( $existing_profile ) {
            // Update existing profile
            $profile_data['ID'] = $existing_profile->ID;
            $profile_id = wp_update_post( $profile_data );
        } else {
            // Create new profile
            $profile_id = wp_insert_post( $profile_data );
        }
        
        if ( $profile_id && ! is_wp_error( $profile_id ) ) {
            // Set initial profile metadata
            update_post_meta( $profile_id, '_vortex_artist_user_id', $user_id );
            update_post_meta( $profile_id, '_vortex_artist_verified', false );
            update_post_meta( $profile_id, '_vortex_artist_registration_date', current_time( 'mysql' ) );
            
            // Set initial user metadata
            update_user_meta( $user_id, '_vortex_artist_profile_id', $profile_id );
        }
        
        return $profile_id;
    }

    /**
     * Get artist profile post by user ID.
     *
     * @since    1.0.0
     * @param    int       $user_id    The user ID.
     * @return   WP_Post|null          The artist profile post or null.
     */
    public function get_artist_profile_by_user_id( $user_id ) {
        // Check user meta first (faster)
        $profile_id = get_user_meta( $user_id, '_vortex_artist_profile_id', true );
        
        if ( $profile_id ) {
            $profile = get_post( $profile_id );
            if ( $profile && $profile->post_type === 'vortex_artist' ) {
                return $profile;
            }
        }
        
        // Fallback to query
        $args = array(
            'post_type'      => 'vortex_artist',
            'posts_per_page' => 1,
            'author'         => $user_id,
            'post_status'    => 'publish',
        );
        
        $query = new WP_Query( $args );
        
        if ( $query->have_posts() ) {
            return $query->posts[0];
        }
        
        return null;
    }

    /**
     * Sync artist profile with user data when user profile is updated.
     *
     * @since    1.0.0
     * @param    int       $user_id      The user ID.
     * @param    WP_User   $old_user_data Old user data.
     */
    public function sync_artist_profile_with_user( $user_id, $old_user_data ) {
        // Check if this is an artist
        $user = new WP_User( $user_id );
        if ( ! in_array( 'vortex_artist', (array) $user->roles ) ) {
            return;
        }
        
        // Get artist profile
        $profile = $this->get_artist_profile_by_user_id( $user_id );
        
        if ( $profile ) {
            // Update profile with user data
            $profile_data = array(
                'ID'           => $profile->ID,
                'post_title'   => $user->display_name,
            );
            
            wp_update_post( $profile_data );
            
            // Update artist bio if it was submitted
            if ( isset( $_POST['vortex_artist_bio'] ) ) {
                $bio = wp_kses_post( $_POST['vortex_artist_bio'] );
                wp_update_post( array(
                    'ID'           => $profile->ID,
                    'post_content' => $bio,
                ) );
            }
        } else {
            // Create new profile if it doesn't exist
            $this->create_or_update_artist_profile( $user_id );
        }
    }

    /**
     * Handle artist deletion.
     *
     * @since    1.0.0
     * @param    int    $user_id    The user ID.
     */
    public function handle_artist_deletion( $user_id ) {
        // Get artist profile
        $profile = $this->get_artist_profile_by_user_id( $user_id );
        
        if ( $profile ) {
            // Check marketplace settings for artwork handling
            $artwork_handling = get_option( 'vortex_deleted_artist_artwork_handling', 'preserve' );
            
            if ( $artwork_handling === 'preserve' ) {
                // Change artwork author to admin
                $args = array(
                    'post_type'      => 'vortex_artwork',
                    'posts_per_page' => -1,
                    'author'         => $user_id,
                    'post_status'    => 'any',
                );
                
                $artworks = get_posts( $args );
                
                if ( $artworks ) {
                    // Get an admin user
                    $admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
                    if ( ! empty( $admins ) ) {
                        $admin_id = $admins[0]->ID;
                        
                        foreach ( $artworks as $artwork ) {
                            wp_update_post( array(
                                'ID'          => $artwork->ID,
                                'post_author' => $admin_id,
                            ) );
                            
                            // Add note about original artist
                            update_post_meta( $artwork->ID, '_vortex_original_artist', $user_id );
                            update_post_meta( $artwork->ID, '_vortex_original_artist_name', $profile->post_title );
                        }
                    }
                }
            } elseif ( $artwork_handling === 'delete' ) {
                // Delete all artist's artworks
                $args = array(
                    'post_type'      => 'vortex_artwork',
                    'posts_per_page' => -1,
                    'author'         => $user_id,
                    'post_status'    => 'any',
                    'fields'         => 'ids',
                );
                
                $artwork_ids = get_posts( $args );
                
                foreach ( $artwork_ids as $artwork_id ) {
                    wp_delete_post( $artwork_id, true );
                }
            }
            
            // Delete the artist profile
            wp_delete_post( $profile->ID, true );
        }
    }

    /**
     * Handle artist verification actions in admin.
     *
     * @since    1.0.0
     */
    public function handle_artist_verification_actions() {
        // Check if this is an artist verification action
        if ( ! isset( $_GET['action'] ) || ! isset( $_GET['artist_id'] ) || ! isset( $_GET['_wpnonce'] ) ) {
            return;
        }
        
        // Check permissions
        if ( ! current_user_can( 'verify_vortex_artists' ) ) {
            wp_die( __( 'You do not have permission to verify artists.', 'vortex-ai-marketplace' ) );
        }
        
        $action = sanitize_text_field( $_GET['action'] );
        $artist_id = intval( $_GET['artist_id'] );
        $nonce = sanitize_text_field( $_GET['_wpnonce'] );
        
        // Verify nonce
        if ( ! wp_verify_nonce( $nonce, 'vortex_artist_verification' ) ) {
            wp_die( __( 'Security check failed.', 'vortex-ai-marketplace' ) );
        }
        
        // Process the action
        if ( $action === 'verify_artist' ) {
            // Mark artist as verified
            update_post_meta( $artist_id, '_vortex_artist_verified', true );
            update_post_meta( $artist_id, '_vortex_artist_verification_date', current_time( 'mysql' ) );
            update_post_meta( $artist_id, '_vortex_artist_verified_by', get_current_user_id() );
            
            // Get the artist's user ID
            $user_id = get_post_meta( $artist_id, '_vortex_artist_user_id', true );
            
            if ( $user_id ) {
                // Send verification email
                $this->send_artist_verification_email( $user_id );
            }
            
            // Redirect back to artists list
            wp_redirect( admin_url( 'edit.php?post_type=vortex_artist&verified=1' ) );
            exit;
            
        } elseif ( $action === 'unverify_artist' ) {
            // Remove verification
            update_post_meta( $artist_id, '_vortex_artist_verified', false );
            delete_post_meta( $artist_id, '_vortex_artist_verification_date' );
            
            // Redirect back to artists list
            wp_redirect( admin_url( 'edit.php?post_type=vortex_artist&unverified=1' ) );
            exit;
        }
    }

    /**
     * Send verification email to artist.
     *
     * @since    1.0.0
     * @param    int    $user_id    The user ID.
     * @return   bool               Whether the email was sent.
     */
    private function send_artist_verification_email( $user_id ) {
        $user = get_userdata( $user_id );
        
        if ( ! $user ) {
            return false;
        }
        
        $subject = sprintf( __( 'Congratulations! Your artist profile on %s has been verified', 'vortex-ai-marketplace' ), get_bloginfo( 'name' ) );
        
        $message = sprintf( __( 'Dear %s,', 'vortex-ai-marketplace' ), $user->display_name ) . "\n\n";
        $message .= sprintf( __( 'Your artist profile on %s has been verified by our team. You can now fully access all artist features and start selling your artwork in the marketplace.', 'vortex-ai-marketplace' ), get_bloginfo( 'name' ) ) . "\n\n";
        $message .= __( 'What this means for you:', 'vortex-ai-marketplace' ) . "\n";
        $message .= '- ' . __( 'Your profile will display a verified badge', 'vortex-ai-marketplace' ) . "\n";
        $message .= '- ' . __( 'Your artworks will be eligible for featured placement', 'vortex-ai-marketplace' ) . "\n";
        $message .= '- ' . __( 'You can now receive payments from sales', 'vortex-ai-marketplace' ) . "\n\n";
        $message .= __( 'Visit your artist dashboard to start managing your portfolio:', 'vortex-ai-marketplace' ) . "\n";
        $message .= home_url( '/artist-dashboard/' ) . "\n\n";
        $message .= __( 'Thank you for being part of our creative community!', 'vortex-ai-marketplace' ) . "\n\n";
        $message .= sprintf( __( 'The %s Team', 'vortex-ai-marketplace' ), get_bloginfo( 'name' ) );
        
        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
        
        return wp_mail( $user->user_email, $subject, $message, $headers );
    }

    /**
     * Add artist profile fields to the user profile page.
     *
     * @since    1.0.0
     * @param    WP_User    $user    The user object.
     */
    public function add_artist_profile_fields( $user ) {
        // Check if user is an artist
        if ( ! in_array( 'vortex_artist', (array) $user->roles ) ) {
            return;
        }
        
        // Get artist profile
        $profile = $this->get_artist_profile_by_user_id( $user->ID );
        $profile_id = $profile ? $profile->ID : 0;
        
        // Get artist metadata
        $bio = $profile ? $profile->post_content : '';
        $wallet_address = get_user_meta( $user->ID, '_vortex_artist_wallet_address', true );
        $verified = $profile ? get_post_meta( $profile_id, '_vortex_artist_verified', true ) : false;
        $specialties = get_user_meta( $user->ID, '_vortex_artist_specialties', true );
        $website = get_user_meta( $user->ID, '_vortex_artist_website', true );
        $social_media = get_user_meta( $user->ID, '_vortex_artist_social_media', true );
        
        if ( ! is_array( $social_media ) ) {
            $social_media = array(
                'twitter' => '',
                'instagram' => '',
                'facebook' => '',
                'deviantart' => '',
                'behance' => '',
            );
        }
        
        ?>
        <h2><?php _e( 'VORTEX Artist Profile', 'vortex-ai-marketplace' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th><label for="vortex_artist_bio"><?php _e( 'Artist Bio', 'vortex-ai-marketplace' ); ?></label></th>
                <td>
                    <?php 
                    wp_editor( $bio, 'vortex_artist_bio', array(
                        'textarea_name' => 'vortex_artist_bio',
                        'textarea_rows' => 5,
                        'media_buttons' => false,
                    ) );
                    ?>
                    <p class="description"><?php _e( 'Tell collectors about yourself and your artistic journey.', 'vortex-ai-marketplace' ); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="vortex_artist_specialties"><?php _e( 'Specialties', 'vortex-ai-marketplace' ); ?></label></th>
                <td>
                    <input type="text" name="vortex_artist_specialties" id="vortex_artist_specialties" value="<?php echo esc_attr( $specialties ); ?>" class="regular-text" />
                    <p class="description"><?php _e( 'Comma-separated list of your artistic specialties (e.g., "Digital Art, Illustration, Abstract").', 'vortex-ai-marketplace' ); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="vortex_artist_website"><?php _e( 'Website', 'vortex-ai-marketplace' ); ?></label></th>
                <td>
                    <input type="url" name="vortex_artist_website" id="vortex_artist_website" value="<?php echo esc_url( $website ); ?>" class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th><?php _e( 'Social Media', 'vortex-ai-marketplace' ); ?></th>
                <td>
                    <p>
                        <label for="vortex_artist_twitter">
                            <span class="dashicons dashicons-twitter"></span> <?php _e( 'Twitter:', 'vortex-ai-marketplace' ); ?>
                        </label>
                        <input type="text" name="vortex_artist_social_media[twitter]" id="vortex_artist_twitter" value="<?php echo esc_attr( $social_media['twitter'] ); ?>" class="regular-text" />
                    </p>
                    
                    <p>
                        <label for="vortex_artist_instagram">
                            <span class="dashicons dashicons-instagram"></span> <?php _e( 'Instagram:', 'vortex-ai-marketplace' ); ?>
                        </label>
                        <input type="text" name="vortex_artist_social_media[instagram]" id="vortex_artist_instagram" value="<?php echo esc_attr( $social_media['instagram'] ); ?>" class="regular-text" />
                    </p>
                    
                    <p>
                        <label for="vortex_artist_facebook">
                            <span class="dashicons dashicons-facebook"></span> <?php _e( 'Facebook:', 'vortex-ai-marketplace' ); ?>
                        </label>
                        <input type="text" name="vortex_artist_social_media[facebook]" id="vortex_artist_facebook" value="<?php echo esc_attr( $social_media['facebook'] ); ?>" class="regular-text" />
                    </p>
                    
                    <p>
                        <label for="vortex_artist_deviantart">
                            <span class="dashicons dashicons-art"></span> <?php _e( 'DeviantArt:', 'vortex-ai-marketplace' ); ?>
                        </label>
                        <input type="text" name="vortex_artist_social_media[deviantart]" id="vortex_artist_deviantart" value="<?php echo esc_attr( $social_media['deviantart'] ); ?>" class="regular-text" />
                    </p>
                    
                    <p>
                        <label for="vortex_artist_behance">
                            <span class="dashicons dashicons-portfolio"></span> <?php _e( 'Behance:', 'vortex-ai-marketplace' ); ?>
                        </label>
                        <input type="text" name="vortex_artist_social_media[behance]" id="vortex_artist_behance" value="<?php echo esc_attr( $social_media['behance'] ); ?>" class="regular-text" />
                    </p>
                </td>
            </tr>
            
            <tr>
                <th><label for="vortex_artist_wallet_address"><?php _e( 'Wallet Address', 'vortex-ai-marketplace' ); ?></label></th>
                <td>
                    <input type="text" name="vortex_artist_wallet_address" id="vortex_artist_wallet_address" value="<?php echo esc_attr( $wallet_address ); ?>" class="regular-text" />
                    <p class="description"><?php _e( 'Your Solana wallet address for receiving payments.', 'vortex-ai-marketplace' ); ?></p>
                    
                    <?php if ( $this->blockchain && ! empty( $wallet_address ) ) : ?>
                        <button type="button" class="button" id="vortex-verify-wallet-button"><?php _e( 'Verify Wallet', 'vortex-ai-marketplace' ); ?></button>
                        <span id="vortex-wallet-verification-result"></span>
                    <?php endif; ?>
                </td>
            </tr>
            
            <?php if ( current_user_can( 'verify_vortex_artists' ) && $profile ) : ?>
                <tr>
                    <th><?php _e( 'Verification Status', 'vortex-ai-marketplace' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="vortex_artist_verified" value="1" <?php checked( $verified ); ?> />
                            <?php _e( 'Artist Verified', 'vortex-ai-marketplace' ); ?>
                        </label>
                        
                        <?php if ( $verified ) : ?>
                            <?php 
                            $verification_date = get_post_meta( $profile_id, '_vortex_artist_verification_date', true );
                            $verified_by = get_post_meta( $profile_id, '_vortex_artist_verified_by', true );
                            $verifier = get_userdata( $verified_by );
                            $verifier_name = $verifier ? $verifier->display_name : __( 'Unknown', 'vortex-ai-marketplace' );
                            ?>
                            <p class="description">
                                <?php printf( __( 'Verified on %s by %s', 'vortex-ai-marketplace' ), 
                                    date_i18n( get_option( 'date_format' ), strtotime( $verification_date ) ),
                                    esc_html( $verifier_name )
                                ); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            <?php if ( $this->blockchain && ! empty( $wallet_address ) ) : ?>
            $('#vortex-verify-wallet-button').on('click', function() {
                var $button = $(this);
                var $result = $('#vortex-wallet-verification-result');
                
                $button.prop('disabled', true).text('<?php _e( 'Verifying...', 'vortex-ai-marketplace' ); ?>');
                $result.text('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_verify_artist_wallet',
                        wallet: $('#vortex_artist_wallet_address').val(),
                        user_id: <?php echo $user->ID; ?>,
                        security: '<?php echo wp_create_nonce( 'vortex_verify_wallet_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.text('<?php _e( 'Wallet verified successfully!', 'vortex-ai-marketplace' ); ?>').css('color', 'green');
                        } else {
                            $result.text(response.data.message).css('color', 'red');
                        }
                    },
                    error: function() {
                        $result.text('<?php _e( 'Verification failed. Please try again.', 'vortex-ai-marketplace' ); ?>').css('color', 'red');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('<?php _e( 'Verify Wallet', 'vortex-ai-marketplace' ); ?>');
                    }
                });
            });
            <?php endif; ?>
        });
        </script>
        <?php
    }

    /**
     * Save artist profile fields.
     *
     * @since    1.0.0
     * @param    int    $user_id    The user ID.
     * @return   bool               Whether fields were saved.
     */
    public function save_artist_profile_fields( $user_id ) {
        // Check permissions
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }
        
        // Check if user is an artist
        $user = new WP_User( $user_id );
        if ( ! in_array( 'vortex_artist', (array) $user->roles ) ) {
            return false;
        }
        
        // Get artist profile
        $profile = $this->get_artist_profile_by_user_id( $user_id );
        
        if ( ! $profile ) {
            // Create artist profile if it doesn't exist
            $profile_id = $this->create_or_update_artist_profile( $user_id );
            $profile = get_post( $profile_id );
        }
        
        if ( $profile ) {
            // Update artist bio
            if ( isset( $_POST['vortex_artist_bio'] ) ) {
                wp_update_post( array(
                    'ID'           => $profile->ID,
                    'post_content' => wp_kses_post( $_POST['vortex_artist_bio'] ),
                ) );
            }
            
            // Update verification status if admin
            if ( current_user_can( 'verify_vortex_artists' ) && isset( $_POST['vortex_artist_verified'] ) ) {
                $verified = $_POST['vortex_artist_verified'] ? true : false;
                update_post_meta( $profile->ID, '_vortex_artist_verified', $verified );
                
                if ( $verified && ! get_post_meta( $profile->ID, '_vortex_artist_verification_date', true ) ) {
                    update_post_meta( $profile->ID, '_vortex_artist_verification_date', current_time( 'mysql' ) );
                    update_post_meta( $profile->ID, '_vortex_artist_verified_by', get_current_user_id() );
                }
            }
        }
        
        // Update user meta fields
        if ( isset( $_POST['vortex_artist_wallet_address'] ) ) {
            update_user_meta( $user_id, '_vortex_artist_wallet_address', sanitize_text_field( $_POST['vortex_artist_wallet_address'] ) );
        }
        
        if ( isset( $_POST['vortex_artist_specialties'] ) ) {
            update_user_meta( $user_id, '_vortex_artist_specialties', sanitize_text_field( $_POST['vortex_artist_specialties'] ) );
        }
        
        if ( isset( $_POST['vortex_artist_website'] ) ) {
            update_user_meta( $user_id, '_vortex_artist_website', esc_url_raw( $_POST['vortex_artist_website'] ) );
        }
        
        // Update social media
        if ( isset( $_POST['vortex_artist_social_media'] ) && is_array( $_POST['vortex_artist_social_media'] ) ) {
            $social_media = array();
            foreach ( $_POST['vortex_artist_social_media'] as $platform => $url ) {
                $social_media[$platform] = esc_url_raw( $url );
            }
            update_user_meta( $user_id, '_vortex_artist_social_media', $social_media );
        }
        
        return true;
    }

    /**
     * AJAX handler for getting artist portfolio data.
     *
     * @since    1.0.0
     */
    public function ajax_get_artist_portfolio() {
        // Check for artist ID
        $artist_id = isset( $_POST['artist_id'] ) ? intval( $_POST['artist_id'] ) : 0;
        
        if ( ! $artist_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid artist ID.', 'vortex-ai-marketplace' ) ) );
        }
        
        // Get the artist's user ID
        $user_id = get_post_meta( $artist_id, '_vortex_artist_user_id', true );
        
        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => __( 'Artist user not found.', 'vortex-ai-marketplace' ) ) );
        }
        
        // Query for artist's artworks
        $args = array(
            'post_type'      => 'vortex_artwork',
            'posts_per_page' => -1,
            'author'         => $user_id,
            'post_status'    => 'publish',
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        );
        
        // Filter by category if provided
        if ( isset( $_POST['category'] ) && ! empty( $_POST['category'] ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'art_category',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field( $_POST['category'] ),
                ),
            );
        }
        
        $query = new WP_Query( $args );
        $artworks = array();
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                
                $artwork_id = get_the_ID();
                $price = get_post_meta( $artwork_id, '_vortex_artwork_price', true );
                $tola_price = get_post_meta( $artwork_id, '_vortex_tola_price', true );
                $edition_size = get_post_meta( $artwork_id, '_vortex_artwork_edition_size', true );
                $blockchain_token_id = get_post_meta( $artwork_id, '_vortex_blockchain_token_id', true );
                
                $artworks[] = array(
                    'id'           => $artwork_id,
                    'price'        => $price,
                    'tola_price'   => $tola_price,
                    'edition_size' => $edition_size,
                    'blockchain_token_id' => $blockchain_token_id,
                );
            }
        }
        
        wp_send_json_success( $artworks );
    }

    /**
     * Initialize artists system
     */
    private function init() {
        try {
            $this->ai_manager = VORTEX_AI_Manager::get_instance();
            $this->setup_hooks();
            $this->initialize_artist_tables();
        } catch (Exception $e) {
            $this->log_error('Initialization failed', $e);
        }
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        add_action('init', array($this, 'register_artist_post_type'));
        add_action('vortex_daily_artist_analysis', array($this, 'analyze_artist_performance'));
        add_action('save_post_vortex_artwork', array($this, 'process_artwork_submission'), 10, 3);
        add_filter('vortex_artist_recommendations', array($this, 'get_ai_recommendations'), 10, 2);
    }

    /**
     * Register artist post type
     */
    public function register_artist_post_type() {
        register_post_type('vortex_artist', array(
            'labels' => array(
                'name' => __('Artists', 'vortex'),
                'singular_name' => __('Artist', 'vortex')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'show_in_rest' => true
        ));
    }

    /**
     * Create or update artist profile
     */
    public function create_update_artist($data) {
        try {
            // Validate data
            $this->validate_artist_data($data);

            // Get AI recommendations for artist profile
            $ai_recommendations = $this->ai_manager->get_artist_recommendations($data);

            // Merge AI recommendations with submitted data
            $profile_data = array_merge($data, $ai_recommendations);

            // Sanitize data
            $profile_data = $this->sanitize_artist_data($profile_data);

            // Create or update artist post
            $post_data = array(
                'post_type' => 'vortex_artist',
                'post_title' => $profile_data['name'],
                'post_content' => $profile_data['bio'],
                'post_status' => 'publish'
            );

            if (!empty($profile_data['id'])) {
                $post_data['ID'] = $profile_data['id'];
                $artist_id = wp_update_post($post_data);
            } else {
                $artist_id = wp_insert_post($post_data);
            }

            if (is_wp_error($artist_id)) {
                throw new Exception($artist_id->get_error_message());
            }

            // Update artist meta
            $this->update_artist_meta($artist_id, $profile_data);

            // Track for AI learning
            $this->track_artist_action('profile_update', $artist_id, $profile_data);

            return $artist_id;

        } catch (Exception $e) {
            $this->log_error('Artist profile update failed', $e);
            throw $e;
        }
    }

    /**
     * Process artwork submission
     */
    public function process_artwork_submission($post_id, $post, $update) {
        try {
            // Verify it's not an autosave
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

            // Get artwork data
            $artwork_data = $this->get_artwork_data($post_id);

            // Get AI analysis
            $ai_analysis = $this->ai_manager->analyze_artwork($artwork_data);

            // Update artwork meta with AI insights
            update_post_meta($post_id, '_vortex_ai_analysis', $ai_analysis);

            // Update artist performance metrics
            $this->update_artist_metrics($post->post_author, $ai_analysis);

            // Track for AI learning
            $this->track_artist_action('artwork_submission', $post->post_author, $artwork_data);

        } catch (Exception $e) {
            $this->log_error('Artwork submission processing failed', $e);
        }
    }

    /**
     * Get artist performance metrics
     */
    public function get_artist_metrics($artist_id) {
        try {
            $cache_key = "artist_metrics_{$artist_id}";
            $metrics = wp_cache_get($cache_key, $this->cache_group);

            if (false === $metrics) {
                // Get base metrics
                $metrics = $this->calculate_artist_metrics($artist_id);

                // Get AI-enhanced insights
                $ai_insights = $this->ai_manager->get_artist_insights($artist_id);

                // Merge insights with metrics
                $metrics = array_merge($metrics, $ai_insights);

                wp_cache_set($cache_key, $metrics, $this->cache_group, $this->cache_expiry);
            }

            return $metrics;

        } catch (Exception $e) {
            $this->log_error('Metrics retrieval failed', $e);
            return array();
        }
    }

    /**
     * Analyze artist performance
     */
    public function analyze_artist_performance() {
        try {
            $artists = $this->get_active_artists();

            foreach ($artists as $artist) {
                // Get performance data
                $performance_data = $this->get_artist_metrics($artist->ID);

                // Get AI analysis
                $analysis = $this->ai_manager->analyze_artist_performance($artist->ID, $performance_data);

                // Update recommendations
                $this->update_artist_recommendations($artist->ID, $analysis);

                // Track for AI learning
                $this->track_artist_action('performance_analysis', $artist->ID, $analysis);
            }

        } catch (Exception $e) {
            $this->log_error('Performance analysis failed', $e);
        }
    }

    /**
     * Get AI recommendations for artist
     */
    public function get_ai_recommendations($artist_id, $context = '') {
        try {
            // Get artist data
            $artist_data = $this->get_artist_data($artist_id);

            // Get recommendations from each AI agent
            $huraii_recommendations = $this->ai_manager->get_agent('huraii')
                ->get_artist_recommendations($artist_data);

            $cloe_recommendations = $this->ai_manager->get_agent('cloe')
                ->get_artist_recommendations($artist_data);

            $business_recommendations = $this->ai_manager->get_agent('business_strategist')
                ->get_artist_recommendations($artist_data);

            // Combine recommendations
            return array(
                'style' => $huraii_recommendations,
                'engagement' => $cloe_recommendations,
                'business' => $business_recommendations
            );

        } catch (Exception $e) {
            $this->log_error('Recommendations retrieval failed', $e);
            return array();
        }
    }

    /**
     * Track artist action for AI learning
     */
    private function track_artist_action($action, $artist_id, $data) {
        try {
            $this->ai_manager->track_event('artist_action', array(
                'action' => $action,
                'artist_id' => $artist_id,
                'data' => $data,
                'timestamp' => current_time('timestamp')
            ));
        } catch (Exception $e) {
            $this->log_error('Action tracking failed', $e);
        }
    }

    /**
     * Error logging
     */
    private function log_error($message, $error) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[VORTEX Artists] %s: %s',
                $message,
                $error->getMessage()
            ));
        }
    }

    /**
     * Validation and sanitization
     */
    private function validate_artist_data($data) {
        if (empty($data['name'])) {
            throw new Exception(__('Artist name is required', 'vortex'));
        }

        if (!empty($data['email']) && !is_email($data['email'])) {
            throw new Exception(__('Invalid email address', 'vortex'));
        }

        return true;
    }

    private function sanitize_artist_data($data) {
        return array(
            'name' => sanitize_text_field($data['name']),
            'email' => sanitize_email($data['email']),
            'bio' => wp_kses_post($data['bio']),
            'styles' => array_map('sanitize_text_field', $data['styles']),
            'social' => array_map('esc_url_raw', $data['social'])
        );
    }
}

// Initialize the artists system
new Vortex_Artists(); 