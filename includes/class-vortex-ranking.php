<?php
/**
 * The rankings functionality of the plugin.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The rankings functionality of the plugin.
 *
 * This class handles artist and artwork rankings, including the calculation
 * of ranking scores, storage, and display.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Rankings {

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
     * Database handler instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Rankings_DB    $db    The database handler for rankings.
     */
    private $db;

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
        
        // Initialize rankings database handler
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'database/class-vortex-rankings-db.php';
        $this->db = new Vortex_Rankings_DB();
        
        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Register all rankings related hooks.
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_hooks() {
        // Schedule daily ranking calculation
        add_action( 'wp', array( $this, 'schedule_ranking_calculation' ) );
        add_action( 'vortex_calculate_rankings', array( $this, 'calculate_all_rankings' ) );
        
        // Update rankings when relevant actions occur
        add_action( 'save_post_vortex_artwork', array( $this, 'update_artist_ranking_on_artwork_change' ), 10, 3 );
        add_action( 'vortex_artwork_purchase_completed', array( $this, 'update_rankings_on_purchase' ), 10, 3 );
        
        // Register shortcodes
        add_shortcode( 'vortex_top_artists', array( $this, 'top_artists_shortcode' ) );
        add_shortcode( 'vortex_trending_artworks', array( $this, 'trending_artworks_shortcode' ) );
        
        // Register REST API endpoints
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        
        // Dashboard widget for admin
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_vortex_get_artist_ranking', array( $this, 'ajax_get_artist_ranking' ) );
        add_action( 'wp_ajax_nopriv_vortex_get_artist_ranking', array( $this, 'ajax_get_artist_ranking' ) );
        add_action( 'wp_ajax_vortex_get_trending_artworks', array( $this, 'ajax_get_trending_artworks' ) );
        add_action( 'wp_ajax_nopriv_vortex_get_trending_artworks', array( $this, 'ajax_get_trending_artworks' ) );
    }

    /**
     * Schedule the daily ranking calculation if not already scheduled.
     *
     * @since    1.0.0
     */
    public function schedule_ranking_calculation() {
        if ( ! wp_next_scheduled( 'vortex_calculate_rankings' ) ) {
            wp_schedule_event( time(), 'daily', 'vortex_calculate_rankings' );
        }
    }

    /**
     * Calculate rankings for all artists and artworks.
     *
     * @since    1.0.0
     */
    public function calculate_all_rankings() {
        // Get all artists
        $artists = get_posts( array(
            'post_type' => 'vortex_artist',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ) );
        
        foreach ( $artists as $artist_id ) {
            $this->calculate_artist_ranking( $artist_id );
        }
        
        // Calculate trending artworks
        $this->calculate_trending_artworks();
    }

    /**
     * Calculate the ranking score for a single artist.
     *
     * @since    1.0.0
     * @param    int       $artist_id    The artist post ID.
     * @return   float                  The calculated ranking score.
     */
    public function calculate_artist_ranking( $artist_id ) {
        // Get the artist's user ID
        $artist_user_id = get_post_field( 'post_author', $artist_id );
        
        // Initialize score components
        $sales_score = 0;
        $views_score = 0;
        $engagement_score = 0;
        $artwork_count_score = 0;
        
        // Get artist's artworks
        $artworks = get_posts( array(
            'post_type' => 'vortex_artwork',
            'author' => $artist_user_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
        ) );
        
        $artwork_count = count( $artworks );
        
        // Calculate artwork count component (max 25 points)
        $artwork_count_score = min( $artwork_count * 5, 25 );
        
        // Calculate sales component (max 35 points)
        $total_sales = $this->get_artist_total_sales( $artist_user_id );
        $sales_score = min( $total_sales * 0.5, 35 );
        
        // Calculate views component (max 20 points)
        $total_views = 0;
        foreach ( $artworks as $artwork_id ) {
            $views = get_post_meta( $artwork_id, '_vortex_view_count', true );
            $total_views += intval( $views );
        }
        $views_score = min( $total_views * 0.02, 20 );
        
        // Calculate engagement component (max 20 points)
        $total_engagement = 0;
        foreach ( $artworks as $artwork_id ) {
            // Count comments
            $comments = get_comments( array(
                'post_id' => $artwork_id,
                'count' => true,
            ) );
            
            // Count likes/favorites if available
            $likes = get_post_meta( $artwork_id, '_vortex_like_count', true );
            
            $total_engagement += intval( $comments ) + intval( $likes );
        }
        $engagement_score = min( $total_engagement * 0.5, 20 );
        
        // Calculate final score (0-100)
        $final_score = $sales_score + $views_score + $engagement_score + $artwork_count_score;
        
        // Store the ranking in the database
        $this->db->update_artist_ranking( $artist_id, $final_score );
        
        // Store score components for detailed analysis
        update_post_meta( $artist_id, '_vortex_ranking_sales_score', $sales_score );
        update_post_meta( $artist_id, '_vortex_ranking_views_score', $views_score );
        update_post_meta( $artist_id, '_vortex_ranking_engagement_score', $engagement_score );
        update_post_meta( $artist_id, '_vortex_ranking_artwork_score', $artwork_count_score );
        update_post_meta( $artist_id, '_vortex_ranking_total_score', $final_score );
        update_post_meta( $artist_id, '_vortex_ranking_last_updated', current_time( 'mysql' ) );
        
        return $final_score;
    }

    /**
     * Calculate trending artworks based on recent activity.
     *
     * @since    1.0.0
     */
    public function calculate_trending_artworks() {
        // Get recent artworks (last 30 days)
        $recent_artworks = get_posts( array(
            'post_type' => 'vortex_artwork',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'date_query' => array(
                array(
                    'after' => '30 days ago',
                ),
            ),
        ) );
        
        $trending_scores = array();
        
        foreach ( $recent_artworks as $artwork_id ) {
            // Calculate trending score based on views, sales, and engagement
            $views = intval( get_post_meta( $artwork_id, '_vortex_view_count', true ) );
            
            // Get sales in last 30 days
            $sales = $this->get_artwork_recent_sales( $artwork_id, 30 );
            
            // Get comments in last 30 days
            $comments = get_comments( array(
                'post_id' => $artwork_id,
                'count' => true,
                'date_query' => array(
                    array(
                        'after' => '30 days ago',
                    ),
                ),
            ) );
            
            // Get likes in last 30 days
            $likes = intval( get_post_meta( $artwork_id, '_vortex_recent_likes', true ) );
            
            // Calculate trending score with weights
            $trending_score = ( $views * 0.05 ) + ( $sales * 10 ) + ( $comments * 2 ) + ( $likes * 1 );
            
            // Store score in array
            $trending_scores[ $artwork_id ] = $trending_score;
            
            // Store score in metadata
            update_post_meta( $artwork_id, '_vortex_trending_score', $trending_score );
            update_post_meta( $artwork_id, '_vortex_trending_last_updated', current_time( 'mysql' ) );
        }
        
        // Sort by score (high to low)
        arsort( $trending_scores );
        
        // Store top 50 trending artworks
        $trending_list = array_slice( array_keys( $trending_scores ), 0, 50, true );
        update_option( 'vortex_trending_artworks', $trending_list );
        update_option( 'vortex_trending_last_updated', current_time( 'mysql' ) );
        
        return $trending_list;
    }

    /**
     * Get total sales for an artist.
     *
     * @since    1.0.0
     * @param    int       $artist_user_id    The artist's user ID.
     * @return   float                       Total sales amount.
     */
    private function get_artist_total_sales( $artist_user_id ) {
        global $wpdb;
        
        $total_sales = 0;
        
        // Query the sales table
        $sales = $wpdb->get_results( $wpdb->prepare(
            "SELECT artwork_id, amount, currency 
             FROM {$wpdb->prefix}vortex_sales 
             WHERE artwork_id IN (
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_author = %d AND post_type = 'vortex_artwork'
             )",
            $artist_user_id
        ) );
        
        if ( $sales ) {
            foreach ( $sales as $sale ) {
                // Convert to USD if necessary
                if ( $sale->currency === 'USD' ) {
                    $total_sales += floatval( $sale->amount );
                } elseif ( $sale->currency === 'TOLA' ) {
                    // Get TOLA to USD conversion rate from options
                    $tola_rate = get_option( 'vortex_tola_usd_rate', 1 );
                    $total_sales += floatval( $sale->amount ) * floatval( $tola_rate );
                }
            }
        }
        
        return $total_sales;
    }

    /**
     * Get recent sales for an artwork.
     *
     * @since    1.0.0
     * @param    int       $artwork_id    The artwork post ID.
     * @param    int       $days          Number of days to look back.
     * @return   int                      Number of sales.
     */
    private function get_artwork_recent_sales( $artwork_id, $days = 30 ) {
        global $wpdb;
        
        $date_limit = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
        
        $sales_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$wpdb->prefix}vortex_sales 
             WHERE artwork_id = %d AND sale_date >= %s",
            $artwork_id,
            $date_limit
        ) );
        
        return intval( $sales_count );
    }

    /**
     * Update artist ranking when an artwork is created or updated.
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID.
     * @param    WP_Post   $post       The post object.
     * @param    bool      $update     Whether this is an existing post being updated.
     */
    public function update_artist_ranking_on_artwork_change( $post_id, $post, $update ) {
        // Skip auto-saves and revisions
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }
        
        // Get the artist post ID based on the author
        $artist_posts = get_posts( array(
            'post_type' => 'vortex_artist',
            'author' => $post->post_author,
            'posts_per_page' => 1,
            'fields' => 'ids',
        ) );
        
        if ( ! empty( $artist_posts ) ) {
            // Recalculate ranking for this artist
            $this->calculate_artist_ranking( $artist_posts[0] );
        }
    }

    /**
     * Update rankings when a purchase is completed.
     *
     * @since    1.0.0
     * @param    int       $artwork_id    The artwork post ID.
     * @param    int       $buyer_id      The buyer user ID.
     * @param    array     $purchase_data Purchase transaction data.
     */
    public function update_rankings_on_purchase( $artwork_id, $buyer_id, $purchase_data ) {
        // Get the artist ID
        $artist_id = get_post_field( 'post_author', $artwork_id );
        
        // Get the artist post
        $artist_posts = get_posts( array(
            'post_type' => 'vortex_artist',
            'author' => $artist_id,
            'posts_per_page' => 1,
            'fields' => 'ids',
        ) );
        
        if ( ! empty( $artist_posts ) ) {
            // Recalculate ranking for this artist
            $this->calculate_artist_ranking( $artist_posts[0] );
        }
        
        // Recalculate trending artworks
        $this->calculate_trending_artworks();
    }

    /**
     * Get top ranked artists.
     *
     * @since    1.0.0
     * @param    int       $limit    Maximum number of artists to return.
     * @return   array               Array of artist data with rankings.
     */
    public function get_top_artists( $limit = 10 ) {
        global $wpdb;
        
        $top_artists = $wpdb->get_results( $wpdb->prepare(
            "SELECT r.artist_id, r.score, a.post_title as artist_name 
             FROM {$wpdb->prefix}vortex_rankings r
             JOIN {$wpdb->posts} a ON r.artist_id = a.ID
             WHERE a.post_type = 'vortex_artist' AND a.post_status = 'publish'
             ORDER BY r.score DESC
             LIMIT %d",
            $limit
        ) );
        
        $results = array();
        
        if ( $top_artists ) {
            foreach ( $top_artists as $artist ) {
                // Get thumbnail
                $thumbnail = get_the_post_thumbnail_url( $artist->artist_id, 'thumbnail' );
                
                // Get artwork count
                $artist_user_id = get_post_field( 'post_author', $artist->artist_id );
                $artwork_count = count_user_posts( $artist_user_id, 'vortex_artwork' );
                
                // Build result
                $results[] = array(
                    'id' => $artist->artist_id,
                    'name' => $artist->artist_name,
                    'score' => $artist->score,
                    'thumbnail' => $thumbnail ? $thumbnail : '',
                    'permalink' => get_permalink( $artist->artist_id ),
                    'artwork_count' => $artwork_count,
                );
            }
        }
        
        return $results;
    }

    /**
     * Get trending artworks.
     *
     * @since    1.0.0
     * @param    int       $limit    Maximum number of artworks to return.
     * @return   array               Array of artwork data with trending scores.
     */
    public function get_trending_artworks( $limit = 10 ) {
        $trending_ids = get_option( 'vortex_trending_artworks', array() );
        
        // Limit to requested number
        $trending_ids = array_slice( $trending_ids, 0, $limit );
        
        $results = array();
        
        if ( ! empty( $trending_ids ) ) {
            foreach ( $trending_ids as $artwork_id ) {
                $artwork = get_post( $artwork_id );
                
                if ( $artwork && $artwork->post_status === 'publish' ) {
                    // Get author/artist info
                    $author_id = $artwork->post_author;
                    $author_name = get_the_author_meta( 'display_name', $author_id );
                    
                    // Get artwork details
                    $trending_score = get_post_meta( $artwork_id, '_vortex_trending_score', true );
                    $price = get_post_meta( $artwork_id, '_vortex_artwork_price', true );
                    $tola_price = get_post_meta( $artwork_id, '_vortex_tola_price', true );
                    $thumbnail = get_the_post_thumbnail_url( $artwork_id, 'medium' );
                    
                    // Build result
                    $results[] = array(
                        'id' => $artwork_id,
                        'title' => $artwork->post_title,
                        'artist' => $author_name,
                        'trending_score' => $trending_score,
                        'price' => $price,
                        'tola_price' => $tola_price,
                        'thumbnail' => $thumbnail ? $thumbnail : '',
                        'permalink' => get_permalink( $artwork_id ),
                    );
                }
            }
        }
        
        return $results;
    }

    /**
     * Shortcode callback for displaying top artists.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string             HTML output.
     */
    public function top_artists_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'limit' => 5,
            'show_score' => 'no',
            'layout' => 'grid', // grid or list
        ), $atts, 'vortex_top_artists' );
        
        $top_artists = $this->get_top_artists( intval( $atts['limit'] ) );
        
        ob_start();
        
        if ( ! empty( $top_artists ) ) {
            echo '<div class="vortex-top-artists-container">';
            echo '<h3>' . esc_html__( 'Top Artists', 'vortex-ai-marketplace' ) . '</h3>';
            
            if ( $atts['layout'] === 'grid' ) {
                echo '<div class="vortex-top-artists-grid">';
                foreach ( $top_artists as $artist ) {
                    ?>
                    <div class="vortex-top-artist-item">
                        <a href="<?php echo esc_url( $artist['permalink'] ); ?>">
                            <div class="vortex-top-artist-thumbnail">
                                <?php if ( ! empty( $artist['thumbnail'] ) ) : ?>
                                    <img src="<?php echo esc_url( $artist['thumbnail'] ); ?>" alt="<?php echo esc_attr( $artist['name'] ); ?>">
                                <?php else : ?>
                                    <div class="vortex-thumbnail-placeholder"></div>
                                <?php endif; ?>
                            </div>
                            <h4 class="vortex-top-artist-name"><?php echo esc_html( $artist['name'] ); ?></h4>
                        </a>
                        <?php if ( $atts['show_score'] === 'yes' ) : ?>
                            <div class="vortex-top-artist-score"><?php echo esc_html( sprintf( __( 'Score: %s', 'vortex-ai-marketplace' ), number_format( $artist['score'], 1 ) ) ); ?></div>
                        <?php endif; ?>
                        <div class="vortex-top-artist-artworks"><?php echo esc_html( sprintf( _n( '%s Artwork', '%s Artworks', $artist['artwork_count'], 'vortex-ai-marketplace' ), $artist['artwork_count'] ) ); ?></div>
                    </div>
                    <?php
                }
                echo '</div>';
            } else {
                echo '<ul class="vortex-top-artists-list">';
                foreach ( $top_artists as $artist ) {
                    ?>
                    <li class="vortex-top-artist-item">
                        <?php if ( ! empty( $artist['thumbnail'] ) ) : ?>
                            <div class="vortex-top-artist-thumbnail">
                                <img src="<?php echo esc_url( $artist['thumbnail'] ); ?>" alt="<?php echo esc_attr( $artist['name'] ); ?>">
                            </div>
                        <?php endif; ?>
                        <div class="vortex-top-artist-info">
                            <h4 class="vortex-top-artist-name">
                                <a href="<?php echo esc_url( $artist['permalink'] ); ?>"><?php echo esc_html( $artist['name'] ); ?></a>
                            </h4>
                            <?php if ( $atts['show_score'] === 'yes' ) : ?>
                                <div class="vortex-top-artist-score"><?php echo esc_html( sprintf( __( 'Score: %s', 'vortex-ai-marketplace' ), number_format( $artist['score'], 1 ) ) ); ?></div>
                            <?php endif; ?>
                            <div class="vortex-top-artist-artworks"><?php echo esc_html( sprintf( _n( '%s Artwork', '%s Artworks', $artist['artwork_count'], 'vortex-ai-marketplace' ), $artist['artwork_count'] ) ); ?></div>
                        </div>
                    </li>
                    <?php
                }
                echo '</ul>';
            }
            
            echo '</div>';
        } else {
            echo '<p>' . esc_html__( 'No top artists found.', 'vortex-ai-marketplace' ) . '</p>';
        }
        
        return ob_get_clean();
    }

    /**
     * Shortcode callback for displaying trending artworks.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string             HTML output.
     */
    public function trending_artworks_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'limit' => 6,
            'show_score' => 'no',
            'columns' => 3,
        ), $atts, 'vortex_trending_artworks' );
        
        $trending_artworks = $this->get_trending_artworks( intval( $atts['limit'] ) );
        
        ob_start();
        
        if ( ! empty( $trending_artworks ) ) {
            echo '<div class="vortex-trending-artworks-container">';
            echo '<h3>' . esc_html__( 'Trending Artworks', 'vortex-ai-marketplace' ) . '</h3>';
            
            echo '<div class="vortex-trending-artworks-grid columns-' . esc_attr( $atts['columns'] ) . '">';
            foreach ( $trending_artworks as $artwork ) {
                ?>
                <div class="vortex-trending-artwork-item">
                    <div class="vortex-trending-artwork-thumbnail">
                        <a href="<?php echo esc_url( $artwork['permalink'] ); ?>">
                            <?php if ( ! empty( $artwork['thumbnail'] ) ) : ?>
                                <img src="<?php echo esc_url( $artwork['thumbnail'] ); ?>" alt="<?php echo esc_attr( $artwork['title'] ); ?>">
                            <?php else : ?>
                                <div class="vortex-thumbnail-placeholder"></div>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="vortex-trending-artwork-info">
                        <h4 class="vortex-trending-artwork-title">
                            <a href="<?php echo esc_url( $artwork['permalink'] ); ?>"><?php echo esc_html( $artwork['title'] ); ?></a>
                        </h4>
                        <div class="vortex-trending-artwork-artist"><?php echo esc_html( sprintf( __( 'By %s', 'vortex-ai-marketplace' ), $artwork['artist'] ) ); ?></div>
                        
                        <?php if ( $atts['show_score'] === 'yes' ) : ?>
                            <div class="vortex-trending-artwork-score"><?php echo esc_html( sprintf( __( 'Trending: %s', 'vortex-ai-marketplace' ), number_format( $artwork['trending_score'], 0 ) ) ); ?></div>
                        <?php endif; ?>
                        
                        <div class="vortex-trending-artwork-price">
                            <?php if ( ! empty( $artwork['price'] ) ) : ?>
                                <?php echo esc_html( sprintf( __( 'Price: $%s', 'vortex-ai-marketplace' ), number_format( $artwork['price'], 2 ) ) ); ?>
                            <?php elseif ( ! empty( $artwork['tola_price'] ) ) : ?>
                                <?php echo esc_html( sprintf( __( 'Price: %s TOLA', 'vortex-ai-marketplace' ), number_format( $artwork['tola_price'], 6 ) ) ); ?>
                            <?php else : ?>
                                <?php esc_html_e( 'Price on request', 'vortex-ai-marketplace' ); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
            }
            echo '</div>';
            
            echo '</div>';
        } else {
            echo '<p>' . esc_html__( 'No trending artworks found.', 'vortex-ai-marketplace' ) . '</p>';
        }
        
        return ob_get_clean();
    }

    /**
     * Register REST API routes for rankings.
     *
     * @since    1.0.0
     */
    public function register_rest_routes() {
        register_rest_route( 'vortex/v1', '/rankings/artists', array(
            'methods' => 'GET',
            'callback' => array( $this, 'rest_get_top_artists' ),
            'permission_callback' => '__return_true',
        ) );
        
        register_rest_route( 'vortex/v1', '/rankings/trending', array(
            'methods' => 'GET',
            'callback' => array( $this, 'rest_get_trending_artworks' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST API callback for top artists.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full details about the request.
     * @return   WP_REST_Response                Response object.
     */
    public function rest_get_top_artists( $request ) {
        $limit = $request->get_param( 'limit' ) ? intval( $request->get_param( 'limit' ) ) : 10;
        
        $top_artists = $this->get_top_artists( $limit );
        
        return rest_ensure_response( $top_artists );
    }

    /**
     * REST API callback for trending artworks.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Full details about the request.
     * @return   WP_REST_Response                Response object.
     */
    public function rest_get_trending_artworks( $request ) {
        $limit = $request->get_param( 'limit' ) ? intval( $request->get_param( 'limit' ) ) : 10;
        
        $trending_artworks = $this->get_trending_artworks( $limit );
        
        return rest_ensure_response( $trending_artworks );
    }

    /**
     * Add rankings dashboard widget for admins.
     *
     * @since    1.0.0
     */
    public function add_dashboard_widget() {
        if ( current_user_can( 'administrator' ) ) {
            wp_add_dashboard_widget(
                'vortex_rankings_dashboard_widget',
                __( 'VORTEX Rankings Overview', 'vortex-ai-marketplace' ),
                array( $this, 'render_dashboard_widget' )
            );
        }
    }

    /**
     * Render the rankings dashboard widget.
     *
     * @since    1.0.0
     */
    public function render_dashboard_widget() {
        $top_artists = $this->get_top_artists( 5 );
        $trending_artworks = $this->get_trending_artworks( 5 );
        
        ?>
        <div class="vortex-dashboard-widget">
            <div class="vortex-dashboard-section">
                <h4><?php esc_html_e( 'Top 5 Artists', 'vortex-ai-marketplace' ); ?></h4>
                <?php if ( ! empty( $top_artists ) ) : ?>
                    <ul class="vortex-dashboard-list">
                        <?php foreach ( $top_artists as $artist ) : ?>
                            <li>
                                <a href="<?php echo esc_url( $artist['permalink'] ); ?>"><?php echo esc_html( $artist['name'] ); ?></a>
                                <span class="vortex-dashboard-score"><?php echo esc_html( number_format( $artist['score'], 1 ) ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p><?php esc_html_e( 'No ranked artists yet.', 'vortex-ai-marketplace' ); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="vortex-dashboard-section">
                <h4><?php esc_html_e( 'Trending Artworks', 'vortex-ai-marketplace' ); ?></h4>
                <?php if ( ! empty( $trending_artworks ) ) : ?>
                    <ul class="vortex-dashboard-list">
                        <?php foreach ( $trending_artworks as $artwork ) : ?>
                            <li>
                                <a href="<?php echo esc_url( $artwork['permalink'] ); ?>"><?php echo esc_html( $artwork['title'] ); ?></a>
                                <span class="vortex-dashboard-info"><?php echo esc_html( sprintf( __( 'by %s', 'vortex-ai-marketplace' ), $artwork['artist'] ) ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p><?php esc_html_e( 'No trending artworks yet.', 'vortex-ai-marketplace' ); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="vortex-dashboard-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=vortex-rankings-settings' ) ); ?>" class="button"><?php esc_html_e( 'Rankings Settings', 'vortex-ai-marketplace' ); ?></a>
                <button id="vortex-recalculate-rankings" class="button button-primary"><?php esc_html_e( 'Recalculate Rankings', 'vortex-ai-marketplace' ); ?></button>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#vortex-recalculate-rankings').on('click', function() {
                    $(this).prop('disabled', true).text('<?php esc_html_e( 'Calculating...', 'vortex-ai-marketplace' ); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'vortex_recalculate_rankings',
                            security: '<?php echo wp_create_nonce( 'vortex_recalculate_rankings_nonce' ); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('<?php esc_html_e( 'Rankings successfully recalculated!', 'vortex-ai-marketplace' ); ?>');
                                location.reload();
                            } else {
                                alert('<?php esc_html_e( 'Error recalculating rankings.', 'vortex-ai-marketplace' ); ?>');
                                $('#vortex-recalculate-rankings').prop('disabled', false).text('<?php esc_html_e( 'Recalculate Rankings', 'vortex-ai-marketplace' ); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php esc_html_e( 'Ajax error. Please try again.', 'vortex-ai-marketplace' ); ?>');
                            $('#vortex-recalculate-rankings').prop('disabled', false).text('<?php esc_html_e( 'Recalculate Rankings', 'vortex-ai-marketplace' ); ?>');
                        }
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * AJAX handler for getting artist ranking.
     *
     * @since    1.0.0
     */
    public function ajax_get_artist_ranking() {
        check_ajax_referer( 'vortex_rankings_nonce', 'security' );
        
        $artist_id = isset( $_POST['artist_id'] ) ? intval( $_POST['artist_id'] ) : 0;
        
        if ( $artist_id ) {
            $ranking_data = $this->db->get_artist_ranking( $artist_id );
            
            if ( $ranking_data ) {
                // Add additional data
                $ranking_data['sales_score'] = get_post_meta( $artist_id, '_vortex_ranking_sales_score', true );
                $ranking_data['views_score'] = get_post_meta( $artist_id, '_vortex_ranking_views_score', true );
                $ranking_data['engagement_score'] = get_post_meta( $artist_id, '_vortex_ranking_engagement_score', true );
                $ranking_data['artwork_score'] = get_post_meta( $artist_id, '_vortex_ranking_artwork_score', true );
                $ranking_data['last_updated'] = get_post_meta( $artist_id, '_vortex_ranking_last_updated', true );
                
                wp_send_json_success( $ranking_data );
            } else {
                wp_send_json_error( array( 'message' => __( 'No ranking data found for this artist.', 'vortex-ai-marketplace' ) ) );
            }
        } else {
            wp_send_json_error( array( 'message' =>
            