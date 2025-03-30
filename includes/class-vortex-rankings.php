<?php
/**
 * The rankings algorithms and display functionality.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The rankings algorithms and display functionality.
 *
 * This class handles the calculation, storage, and display of rankings
 * for artists, artworks, and other marketplace elements.
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
     * The DB table name for rankings.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $rankings_table    DB table name for rankings.
     */
    private $rankings_table;

    /**
     * The logger instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      object    $logger    Logger instance.
     */
    private $logger;

    /**
     * Weighting factors for different ranking algorithms.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $weights    Weighting factors for algorithms.
     */
    private $weights;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     * @param    object    $logger            Optional. Logger instance.
     */
    public function __construct( $plugin_name, $version, $logger = null ) {
        global $wpdb;
        
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->logger = $logger;
        
        // Set database table name
        $this->rankings_table = $wpdb->prefix . 'vortex_rankings';
        
        // Initialize default weights
        $this->initialize_weights();
        
        // Register hooks
        $this->register_hooks();
    }

    /**
     * Initialize ranking algorithm weights from settings.
     *
     * @since    1.0.0
     */
    private function initialize_weights() {
        // Default weights if settings not configured
        $default_weights = array(
            'artist' => array(
                'sales_count' => 35,
                'sales_revenue' => 30,
                'artwork_count' => 10,
                'view_count' => 15,
                'avg_rating' => 10,
            ),
            'artwork' => array(
                'sales_count' => 40,
                'revenue' => 20,
                'view_count' => 30,
                'rating' => 10,
            ),
            'category' => array(
                'artwork_count' => 20,
                'sales_count' => 40,
                'revenue' => 25,
                'view_count' => 15,
            ),
        );
        
        // Get weights from options
        $artist_weights = get_option( 'vortex_rankings_artist_weights', $default_weights['artist'] );
        $artwork_weights = get_option( 'vortex_rankings_artwork_weights', $default_weights['artwork'] );
        $category_weights = get_option( 'vortex_rankings_category_weights', $default_weights['category'] );
        
        $this->weights = array(
            'artist' => $artist_weights,
            'artwork' => $artwork_weights,
            'category' => $category_weights,
        );
    }

    /**
     * Register hooks for rankings functionality.
     *
     * @since    1.0.0
     */
    private function register_hooks() {
        // Schedule weekly rankings calculation
        if ( ! wp_next_scheduled( 'vortex_weekly_rankings_update' ) ) {
            wp_schedule_event( time(), 'weekly', 'vortex_weekly_rankings_update' );
        }
        
        // Register hooks for rankings calculation
        add_action( 'vortex_weekly_rankings_update', array( $this, 'calculate_all_rankings' ) );
        add_action( 'vortex_artwork_purchased', array( $this, 'update_rankings_on_purchase' ), 10, 3 );
        add_filter( 'vortex_artist_query_args', array( $this, 'modify_artist_query_for_rankings' ), 10, 2 );
        add_filter( 'vortex_artwork_query_args', array( $this, 'modify_artwork_query_for_rankings' ), 10, 2 );
        
        // Register admin hooks
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'add_rankings_menu' ), 30 );
        
        // Register REST API endpoints
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        
        // Register shortcodes
        add_shortcode( 'vortex_top_artists', array( $this, 'top_artists_shortcode' ) );
        add_shortcode( 'vortex_top_artworks', array( $this, 'top_artworks_shortcode' ) );
        add_shortcode( 'vortex_trending_categories', array( $this, 'trending_categories_shortcode' ) );
        
        // Register widget hooks if Artist Widget exists
        if ( class_exists( 'Vortex_Artist_Widget' ) ) {
            add_filter( 'vortex_artist_widget_query_args', array( $this, 'modify_artist_widget_query' ), 10, 2 );
        }
        
        // Register widget hooks if Artwork Widget exists
        if ( class_exists( 'Vortex_Featured_Artwork_Widget' ) ) {
            add_filter( 'vortex_artwork_widget_query_args', array( $this, 'modify_artwork_widget_query' ), 10, 2 );
        }
    }

    /**
     * Register plugin settings for rankings configuration.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Artist ranking weights
        register_setting( 'vortex_rankings_settings', 'vortex_rankings_artist_weights', array(
            'type' => 'array',
            'sanitize_callback' => array( $this, 'sanitize_ranking_weights' ),
        ));
        
        // Artwork ranking weights
        register_setting( 'vortex_rankings_settings', 'vortex_rankings_artwork_weights', array(
            'type' => 'array',
            'sanitize_callback' => array( $this, 'sanitize_ranking_weights' ),
        ));
        
        // Category ranking weights
        register_setting( 'vortex_rankings_settings', 'vortex_rankings_category_weights', array(
            'type' => 'array',
            'sanitize_callback' => array( $this, 'sanitize_ranking_weights' ),
        ));
        
        // Time window for trending calculations
        register_setting( 'vortex_rankings_settings', 'vortex_rankings_trending_days', array(
            'type' => 'integer',
            'default' => 7,
            'sanitize_callback' => 'absint',
        ));
        
        // Enable/disable ranking display on artist profiles
        register_setting( 'vortex_rankings_settings', 'vortex_rankings_show_on_profiles', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ));
        
        // Enable/disable ranking badges
        register_setting( 'vortex_rankings_settings', 'vortex_rankings_show_badges', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ));
        
        // Top artist threshold
        register_setting( 'vortex_rankings_settings', 'vortex_rankings_top_artist_threshold', array(
            'type' => 'integer',
            'default' => 10,
            'sanitize_callback' => 'absint',
        ));
    }

    /**
     * Add Rankings submenu to the marketplace admin menu.
     *
     * @since    1.0.0
     */
    public function add_rankings_menu() {
        add_submenu_page(
            'vortex_marketplace',
            __( 'Rankings Settings', 'vortex-ai-marketplace' ),
            __( 'Rankings', 'vortex-ai-marketplace' ),
            'manage_options',
            'vortex_rankings_settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register REST API routes for rankings.
     *
     * @since    1.0.0
     */
    public function register_rest_routes() {
        register_rest_route( 'vortex/v1', '/rankings/artists', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'api_get_artist_rankings' ),
            'permission_callback' => '__return_true',
            'args' => array(
                'limit' => array(
                    'type' => 'integer',
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ),
                'type' => array(
                    'type' => 'string',
                    'default' => 'overall',
                    'enum' => array( 'overall', 'trending', 'sales', 'popularity' ),
                ),
                'timeframe' => array(
                    'type' => 'string',
                    'default' => 'all',
                    'enum' => array( 'all', 'weekly', 'monthly', 'yearly' ),
                ),
            ),
        ));
        
        register_rest_route( 'vortex/v1', '/rankings/artworks', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'api_get_artwork_rankings' ),
            'permission_callback' => '__return_true',
            'args' => array(
                'limit' => array(
                    'type' => 'integer',
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ),
                'type' => array(
                    'type' => 'string',
                    'default' => 'overall',
                    'enum' => array( 'overall', 'trending', 'sales', 'popularity' ),
                ),
                'timeframe' => array(
                    'type' => 'string',
                    'default' => 'all',
                    'enum' => array( 'all', 'weekly', 'monthly', 'yearly' ),
                ),
                'category' => array(
                    'type' => 'integer',
                    'default' => 0,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));
        
        register_rest_route( 'vortex/v1', '/rankings/categories', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'api_get_category_rankings' ),
            'permission_callback' => '__return_true',
            'args' => array(
                'limit' => array(
                    'type' => 'integer',
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ),
                'type' => array(
                    'type' => 'string',
                    'default' => 'overall',
                    'enum' => array( 'overall', 'trending', 'sales', 'popularity' ),
                ),
                'timeframe' => array(
                    'type' => 'string',
                    'default' => 'all',
                    'enum' => array( 'all', 'weekly', 'monthly', 'yearly' ),
                ),
            ),
        ));
    }

    /**
     * Calculate rankings for all elements.
     *
     * @since    1.0.0
     */
    public function calculate_all_rankings() {
        $this->log( 'Starting calculation of all rankings', 'info' );
        
        // Calculate artist rankings
        $this->calculate_artist_rankings();
        
        // Calculate artwork rankings
        $this->calculate_artwork_rankings();
        
        // Calculate category rankings
        $this->calculate_category_rankings();
        
        // Calculate trending rankings
        $this->calculate_trending_rankings();
        
        $this->log( 'Completed calculation of all rankings', 'info' );
    }

    /**
     * Calculate artist rankings.
     *
     * @since    1.0.0
     * @param    string    $timeframe    Optional. Time frame for ranking calculation.
     * @return   bool      Success or failure.
     */
    public function calculate_artist_rankings( $timeframe = 'all' ) {
        global $wpdb;
        
        $this->log( 'Calculating artist rankings for timeframe: ' . $timeframe, 'info' );
        
        // Get all published artists
        $artists_query = "
            SELECT ID, post_author, post_title
            FROM {$wpdb->posts}
            WHERE post_type = 'vortex_artist'
            AND post_status = 'publish'
        ";
        
        $artists = $wpdb->get_results( $artists_query );
        
        if ( empty( $artists ) ) {
            $this->log( 'No artists found for ranking calculation', 'warning' );
            return false;
        }
        
        $this->log( sprintf( 'Found %d artists for ranking calculation', count( $artists ) ), 'info' );
        
        // Array to store rankings
        $rankings = array();
        
        // Get ranking weights
        $weights = $this->weights['artist'];
        
        // Calculate ranking for each artist
        foreach ( $artists as $artist ) {
            // Get artist metrics
            $sales_count = $this->get_artist_metric( $artist->ID, 'sales_count', $timeframe );
            $sales_revenue = $this->get_artist_metric( $artist->ID, 'sales_revenue', $timeframe );
            $artwork_count = $this->get_artist_metric( $artist->ID, 'artwork_count', $timeframe );
            $view_count = $this->get_artist_metric( $artist->ID, 'view_count', $timeframe );
            $avg_rating = $this->get_artist_metric( $artist->ID, 'avg_rating', $timeframe );
            
            // Calculate overall score (weighted sum of normalized metrics)
            $overall_score = 0;
            
            // Get maximum values for normalization
            $max_sales_count = $this->get_max_metric( 'artist', 'sales_count', $timeframe );
            $max_sales_revenue = $this->get_max_metric( 'artist', 'sales_revenue', $timeframe );
            $max_artwork_count = $this->get_max_metric( 'artist', 'artwork_count', $timeframe );
            $max_view_count = $this->get_max_metric( 'artist', 'view_count', $timeframe );
            
            // Normalize and apply weights
            $normalized_sales_count = $max_sales_count > 0 ? $sales_count / $max_sales_count : 0;
            $normalized_sales_revenue = $max_sales_revenue > 0 ? $sales_revenue / $max_sales_revenue : 0;
            $normalized_artwork_count = $max_artwork_count > 0 ? $artwork_count / $max_artwork_count : 0;
            $normalized_view_count = $max_view_count > 0 ? $view_count / $max_view_count : 0;
            $normalized_avg_rating = $avg_rating / 5; // Ratings are out of 5
            
            $overall_score += $normalized_sales_count * $weights['sales_count'];
            $overall_score += $normalized_sales_revenue * $weights['sales_revenue'];
            $overall_score += $normalized_artwork_count * $weights['artwork_count'];
            $overall_score += $normalized_view_count * $weights['view_count'];
            $overall_score += $normalized_avg_rating * $weights['avg_rating'];
            
            // Calculate various specialized scores
            $sales_score = ( $normalized_sales_count * 0.6 ) + ( $normalized_sales_revenue * 0.4 );
            $popularity_score = ( $normalized_view_count * 0.7 ) + ( $normalized_avg_rating * 0.3 );
            
            // Store ranking data
            $rankings[] = array(
                'artist_id' => $artist->ID,
                'user_id' => $artist->post_author,
                'overall_score' => $overall_score,
                'sales_score' => $sales_score * 100, // Convert to percentage
                'popularity_score' => $popularity_score * 100, // Convert to percentage
                'timeframe' => $timeframe,
                'metrics' => array(
                    'sales_count' => $sales_count,
                    'sales_revenue' => $sales_revenue,
                    'artwork_count' => $artwork_count,
                    'view_count' => $view_count,
                    'avg_rating' => $avg_rating,
                ),
                'calculated_at' => current_time( 'mysql' ),
            );
        }
        
        // Sort rankings by overall score
        usort( $rankings, function( $a, $b ) {
            return $b['overall_score'] <=> $a['overall_score'];
        });
        
        // Assign ranks
        $rank = 1;
        foreach ( $rankings as &$ranking ) {
            $ranking['rank'] = $rank++;
        }
        
        // Save rankings to database
        $this->save_artist_rankings( $rankings, $timeframe );
        
        // Log completion
        $this->log( sprintf( 'Completed artist rankings calculation for %d artists', count( $artists ) ), 'info' );
        
        return true;
    }

    /**
     * Calculate artwork rankings.
     *
     * @since    1.0.0
     * @param    string    $timeframe    Optional. Time frame for ranking calculation.
     * @param    int       $category_id  Optional. Limit to specific category.
     * @return   bool      Success or failure.
     */
    public function calculate_artwork_rankings( $timeframe = 'all', $category_id = 0 ) {
        global $wpdb;
        
        $this->log( 'Calculating artwork rankings for timeframe: ' . $timeframe, 'info' );
        
        // Build query for artworks
        $artworks_query = "
            SELECT ID, post_author, post_title
            FROM {$wpdb->posts}
            WHERE post_type = 'vortex_artwork'
            AND post_status = 'publish'
        ";
        
        // Add category filter if specified
        if ( $category_id > 0 ) {
            $artworks_query = $wpdb->prepare("
                SELECT p.ID, p.post_author, p.post_title
                FROM {$wpdb->posts} p
                JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE p.post_type = 'vortex_artwork'
                AND p.post_status = 'publish'
                AND tt.taxonomy = 'vortex_artwork_category'
                AND tt.term_id = %d
            ", $category_id);
        }
        
        $artworks = $wpdb->get_results( $artworks_query );
        
        if ( empty( $artworks ) ) {
            $this->log( 'No artworks found for ranking calculation', 'warning' );
            return false;
        }
        
        $this->log( sprintf( 'Found %d artworks for ranking calculation', count( $artworks ) ), 'info' );
        
        // Array to store rankings
        $rankings = array();
        
        // Get ranking weights
        $weights = $this->weights['artwork'];
        
        // Calculate ranking for each artwork
        foreach ( $artworks as $artwork ) {
            // Get artwork metrics
            $sales_count = $this->get_artwork_metric( $artwork->ID, 'sales_count', $timeframe );
            $revenue = $this->get_artwork_metric( $artwork->ID, 'revenue', $timeframe );
            $view_count = $this->get_artwork_metric( $artwork->ID, 'view_count', $timeframe );
            $rating = $this->get_artwork_metric( $artwork->ID, 'rating', $timeframe );
            
            // Calculate overall score (weighted sum of normalized metrics)
            $overall_score = 0;
            
            // Get maximum values for normalization
            $max_sales_count = $this->get_max_metric( 'artwork', 'sales_count', $timeframe );
            $max_revenue = $this->get_max_metric( 'artwork', 'revenue', $timeframe );
            $max_view_count = $this->get_max_metric( 'artwork', 'view_count', $timeframe );
            
            // Normalize and apply weights
            $normalized_sales_count = $max_sales_count > 0 ? $sales_count / $max_sales_count : 0;
            $normalized_revenue = $max_revenue > 0 ? $revenue / $max_revenue : 0;
            $normalized_view_count = $max_view_count > 0 ? $view_count / $max_view_count : 0;
            $normalized_rating = $rating / 5; // Ratings are out of 5
            
            $overall_score += $normalized_sales_count * $weights['sales_count'];
            $overall_score += $normalized_revenue * $weights['revenue'];
            $overall_score += $normalized_view_count * $weights['view_count'];
            $overall_score += $normalized_rating * $weights['rating'];
            
            // Calculate various specialized scores
            $sales_score = ( $normalized_sales_count * 0.7 ) + ( $normalized_revenue * 0.3 );
            $popularity_score = ( $normalized_view_count * 0.6 ) + ( $normalized_rating * 0.4 );
            
            // Store ranking data
            $rankings[] = array(
                'artwork_id' => $artwork->ID,
                'artist_id' => $artwork->post_author,
                'overall_score' => $overall_score,
                'sales_score' => $sales_score * 100, // Convert to percentage
                'popularity_score' => $popularity_score * 100, // Convert to percentage
                'timeframe' => $timeframe,
                'category_id' => $category_id,
                'metrics' => array(
                    'sales_count' => $sales_count,
                    'revenue' => $revenue,
                    'view_count' => $view_count,
                    'rating' => $rating,
                ),
                'calculated_at' => current_time( 'mysql' ),
            );
        }
        
        // Sort rankings by overall score
        usort( $rankings, function( $a, $b ) {
            return $b['overall_score'] <=> $a['overall_score'];
        });
        
        // Assign ranks
        $rank = 1;
        foreach ( $rankings as &$ranking ) {
            $ranking['rank'] = $rank++;
        }
        
        // Save rankings to database
        $this->save_artwork_rankings( $rankings, $timeframe, $category_id );
        
        // Log completion
        $this->log( sprintf( 'Completed artwork rankings calculation for %d artworks', count( $artworks ) ), 'info' );
        
        return true;
    }

    /**
     * Calculate category rankings.
     *
     * @since    1.0.0
     * @param    string    $timeframe    Optional. Time frame for ranking calculation.
     * @return   bool      Success or failure.
     */
    public function calculate_category_rankings( $timeframe = 'all' ) {
        global $wpdb;
        
        $this->log( 'Calculating category rankings for timeframe: ' . $timeframe, 'info' );
        
        // Get all artwork categories
        $categories_query = "
            SELECT t.term_id, t.name, tt.count
            FROM {$wpdb->terms} t
            JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'vortex_artwork_category'
            AND tt.count > 0
        ";
        
        $categories = $wpdb->get_results( $categories_query );
        
        if ( empty( $categories ) ) {
            $this->log( 'No categories found for ranking calculation', 'warning' );
            return false;
        }
        
        $this->log( sprintf( 'Found %d categories for ranking calculation', count( $categories ) ), 'info' );
        
        // Array to store rankings
        $rankings = array();
        
        // Get ranking weights
        $weights = $this->weights['category'];
        
        // Calculate ranking for each category
        foreach ( $categories as $category ) {
            // Get category metrics
            $artwork_count = $category->count;
            $sales_count = $this->get_category_metric( $category->term_id, 'sales_count', $timeframe );
            $revenue = $this->get_category_metric( $category->term_id, 'revenue', $timeframe );
            $view_count = $this->get_category_metric( $category->term_id, 'view_count', $timeframe );
            
            // Calculate overall score (weighted sum of normalized metrics)
            $overall_score = 0;
            
            // Get maximum values for normalization
            $max_artwork_count = max( array_column( $categories, 'count' ) );
            $max_sales_count = $this->get_max_metric( 'category', 'sales_count', $timeframe );
            $max_revenue = $this->get_max_metric( 'category', 'revenue', $timeframe );
            $max_view_count = $this->get_max_metric( 'category', 'view_count', $timeframe );
            
            // Normalize and apply weights
            $normalized_artwork_count = $max_artwork_count > 0 ? $artwork_count / $max_artwork_count : 0;
            $normalized_sales_count = $max_sales_count > 0 ? $sales_count / $max_sales_count : 0;
            $normalized_revenue = $max_revenue > 0 ? $revenue / $max_revenue : 0;
            $normalized_view_count = $max_view_count > 0 ? $view_count / $max_view_count : 0;
            
            $overall_score += $normalized_artwork_count * $weights['artwork_count'];
            $overall_score += $normalized_sales_count * $weights['sales_count'];
            $overall_score += $normalized_revenue * $weights['revenue'];
            $overall_score += $normalized_view_count * $weights['view_count'];
            
            // Calculate various specialized scores
            $sales_score = ( $normalized_sales_count * 0.6 ) + ( $normalized_revenue * 0.4 );
            $popularity_score = ( $normalized_view_count * 0.7 ) + ( $normalized_artwork_count * 0.3 );
            
            // Store ranking data
            $rankings[] = array(
                'category_id' => $category->term_id,
                'category_name' => $category->name,
                'overall_score' => $overall_score,
                'sales_score' => $sales_score * 100, // Convert to percentage
                'popularity_score' => $popularity_score * 100, // Convert to percentage
                'timeframe' => $timeframe,
                'metrics' => array(
                    'artwork_count' => $artwork_count,
                    'sales_count' => $sales_count,
                    'revenue' => $revenue,
                    'view_count' => $view_count,
                ),
                'calculated_at' => current_time( 'mysql' ),
            );
        }
        
        // Sort rankings by overall score
        usort( $rankings, function( $a, $b ) {
            return $b['overall_score'] <=> $a['overall_score'];
        });
        
        // Assign ranks
        $rank = 1;
        foreach ( $rankings as &$ranking ) {
            $ranking['rank'] = $rank++;
        }
        
        // Save rankings to database
        $this->save_category_rankings( $rankings, $timeframe );
        
        // Log completion
        $this->log( sprintf( 'Completed category rankings calculation for %d categories', count( $categories ) ), 'info' );
        
        return true;
    }

    /**
     * Calculate trending rankings based on recent activity.
     *
     * @since    1.0.0
     */
    public function calculate_trending_rankings() {
        // Get trending time window from settings
        $trending_days = get_option( 'vortex_rankings_trending_days', 7 );
        
        // Calculate weekly rankings for artists, artworks, and categories
        $this->calculate_artist_rankings( 'weekly' );
        $this->calculate_artwork_rankings( 'weekly' );
        $this->calculate_category_rankings( 'weekly' );
        
        // Calculate monthly rankings if requested
        if ( $trending_days >= 28 ) {
            $this->calculate_artist_rankings( 'monthly' );
            $this->calculate_artwork_rankings( 'monthly' );
            $this->calculate_category_rankings( 'monthly' );
        }
    }

    /**
     * Update rankings when an artwork is purchased.
     *
     * @since    1.0.0
     * @param    int       $artwork_id      The artwork post ID.
     * @param    float     $price           The sale price.
     * @param    int       $buyer_user_id   The buyer user ID.
     */
    public function update_rankings_on_purchase( $artwork_id, $price, $buyer_user_id ) {
        // Get artwork details
        $artwork = get_post( $artwork_id );
        if ( ! $artwork || 'vortex_artwork' !== $artwork->post_type ) {
            return;
        }
        
        $artist_id = $artwork->post_author;
        
        // Update artwork sales metrics
        update_post_meta( $artwork_id, '_vortex_artwork_sales_count', 
            intval( get_post_meta( $artwork_id, '_vortex_artwork_sales_count', true ) ) + 1 );
        update_post_meta( $artwork_id, '_vortex_artwork_revenue', 
            floatval( get_post_meta( $artwork_id, '_vortex_artwork_revenue', true ) ) + $price );
        
        // Update artist sales metrics
        update_post_meta( $artist_id, '_vortex_artist_sales_count', 
            intval( get_post_meta( $artist_id, '_vortex_artist_sales_count', true ) ) + 1 );
        update_post_meta( $artist_id, '_vortex_artist_sales_revenue', 
            floatval( get_post_meta( $artist_id, '_vortex_artist_sales_revenue', true ) ) + $price );
        
        // Update category sales metrics
        $categories = wp_get_post_terms( $artwork_id, 'vortex_artwork_category', array( 'fields' => 'ids' ) );
        if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
            foreach ( $categories as $category_id ) {
                // We'll use term meta for category metrics
                $sales_count = get_term_meta( $category_id, '_vortex_category_sales_count', true );
                $revenue = get_term_meta( $category_id, '_vortex_category_revenue', true );
                
                update_term_meta( $category_id, '_vortex_category_sales_count', 
                    intval( $sales_count ) + 1 );
                update_term_meta( $category_id, '_vortex_category_revenue', 
                    floatval( $revenue ) + $price );
            }
        }
        
        // Schedule a deferred ranking update
        if ( ! wp_next_scheduled( 'vortex_update_rankings_after_purchase' ) ) {
            wp_schedule_single_event( time() + 3600, 'vortex_update_rankings_after_purchase' );
        }
    }

    /**
     * Save artist rankings to database.
     *
     * @since    1.0.0
     * @param    array     $rankings    Array of ranking data.
     * @param    string    $timeframe   Time frame for rankings.
     */
    private function save_artist_rankings( $rankings, $timeframe ) {
        global $wpdb;
        
        // Delete existing rankings for this timeframe
        $wpdb->delete(
            $this->rankings_table,
            array(
                'type' => 'artist',
                'timeframe' => $timeframe,
            ),
            array( '%s', '%s' )
        );
        
        // Insert new rankings
        foreach ( $rankings as $ranking ) {
            $data = array(
                'type' => 'artist',
                'item_id' => $ranking['artist_id'],
                'related_id' => $ranking['user_id'],
                'rank' => $ranking['rank'],
                'overall_score' => $ranking['overall_score'],
                'sales_score' => $ranking['sales_score'],
                'popularity_score' => $ranking['popularity_score'],
                'timeframe' => $timeframe,
                'category_id' => 0,
                'metrics' => wp_json_encode( $ranking['metrics'] ),
                'calculated_at' => $ranking['calculated_at'],
            );
            
            $wpdb->insert(
                $this->rankings_table,
                $data,
                array( '%s', '%d', '%d', '%d', '%f', '%f', '%f', '%s', '%d', '%s', '%s' )
            );
            
            // Update artist post meta with ranking for easy querying
            update_post_meta( $ranking['artist_id'], '_vortex_artist_rank_' . $timeframe, $ranking['rank'] );
            update_post_meta( $ranking['artist_id'], '_vortex_artist_score_' . $timeframe, $ranking['overall_score'] );
        }
    }

    /**
     * Save artwork rankings to database.
     *
     * @since    1.0.0
     * @param    array     $rankings     Array of ranking data.
     * @param    string    $timeframe    Time frame for rankings.
     * @param    int       $category_id  Category ID for category-specific rankings.
     */
    private function save_artwork_rankings( $rankings, $timeframe, $category_id = 0 ) {
        global $wpdb;
        
        // Delete existing rankings for this timeframe and category
        $wpdb->delete(
            $this->rankings_table,
            array(
                'type' => 'artwork',
                'timeframe' => $timeframe,
                'category_id' => $category_id,
            ),
            array( '%s', '%s', '%d' )
        );
        
        // Insert new rankings
        foreach ( $rankings as $ranking ) {
            $data = array(
                'type' => 'artwork',
                'item_id' => $ranking['artwork_id'],
                'related_id' => $ranking['artist_id'],
                'rank' => $ranking['rank'],
                'overall_score' => $ranking['overall_score'],
                'sales_score' => $ranking['sales_score'],
                'popularity_score' => $ranking['popularity_score'],
                'timeframe' => $timeframe,
                'category_id' => $category_id,
                'metrics' => wp_json_encode( $ranking['metrics'] ),
                'calculated_at' => $ranking['calculated_at'],
            );
            
            $wpdb->insert(
                $this->rankings_table,
                $data,
                array( '%s', '%d', '%d', '%d', '%f', '%f', '%f', '%s', '%d', '%s', '%s' )
            );
            
            // Update artwork post meta with ranking for easy querying
            $meta_key_suffix = $timeframe;
            if ( $category_id > 0 ) {
                $meta_key_suffix .= '_cat_' . $category_id;
            }
            
            update_post_meta( $ranking['artwork_id'], '_vortex_artwork_rank_' . $meta_key_suffix, $ranking['rank'] );
            update_post_meta( $ranking['artwork_id'], '_vortex_artwork_score_' . $meta_key_suffix, $ranking['overall_score'] );
        }
    }

    /**
     * Save category rankings to database.
     *
     * @since    1.0.0
     * @param    array     $rankings    Array of ranking data.
     * @param    string    $timeframe   Time frame for rankings.
     */
    private function save_category_rankings( $rankings, $timeframe ) {
        global $wpdb;
        
        // Delete existing rankings for this timeframe
        $wpdb->delete(
            $this->rankings_table,
            array(
                'type' => 'category',
                'timeframe' => $timeframe,
            ),
            array( '%s', '%s' )
        );
        
        // Insert new rankings
        foreach ( $rankings as $ranking ) {
            $data = array(
                'type' => 'category',
                'item_id' => $ranking['category_id'],
                'related_id' => 0,
                'rank' => $ranking['rank'],
                'overall_score' => $ranking['overall_score'],
                'sales_score' => $ranking['sales_score'],
                'popularity_score' => $ranking['popularity_score'],
                'timeframe' => $timeframe,
                'category_id' => 0,
                'metrics' => wp_json_encode( $ranking['metrics'] ),
                'calculated_at' => $ranking['calculated_at'],
            );
            
            $wpdb->insert(
                $this->rankings_table,
                $data,
                array( '%s', '%d', '%d', '%f', '%f', '%f', '%s', '%d', '%s', '%s' )
            );
            
            // Update category term meta with ranking for easy querying
            update_term_meta( $ranking['category_id'], '_vortex_category_rank_' . $timeframe, $ranking['rank'] );
            update_term_meta( $ranking['category_id'], '_vortex_category_score_' . $timeframe, $ranking['overall_score'] );
        }
    }
} 