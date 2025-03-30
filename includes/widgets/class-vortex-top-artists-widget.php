<?php
/**
 * The Top Artists Widget functionality.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/widgets
 */

/**
 * The Top Artists Widget functionality.
 *
 * Displays top-ranked artists in a widget area with customizable display options.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/widgets
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Top_Artists_Widget extends WP_Widget {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        parent::__construct(
            'vortex_top_artists_widget', // Base ID
            __( 'VORTEX Top Artists', 'vortex-ai-marketplace' ), // Name
            array(
                'description' => __( 'Display top-ranked artists from the marketplace.', 'vortex-ai-marketplace' ),
                'classname'   => 'vortex-top-artists-widget',
            )
        );

        // Register widget
        add_action( 'widgets_init', array( $this, 'register_widget' ) );
        
        // Load widget specific scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Register the widget with WordPress.
     *
     * @since    1.0.0
     */
    public function register_widget() {
        register_widget( 'Vortex_Top_Artists_Widget' );
    }

    /**
     * Enqueue widget specific scripts and styles.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Only load if widget is active
        if ( is_active_widget( false, false, $this->id_base, true ) ) {
            wp_enqueue_style(
                'vortex-top-artists-widget',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/css/vortex-rankings.css',
                array(),
                VORTEX_VERSION,
                'all'
            );
            
            wp_enqueue_script(
                'vortex-top-artists-widget',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/js/vortex-live-rankings.js',
                array( 'jquery' ),
                VORTEX_VERSION,
                true
            );
            
            // Localize script with necessary data
            wp_localize_script(
                'vortex-top-artists-widget',
                'vortexRankings',
                array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'vortex_rankings_nonce' ),
                )
            );
        }
    }

    /**
     * Front-end display of widget.
     *
     * @since    1.0.0
     * @param    array    $args        Widget arguments.
     * @param    array    $instance    Saved values from database.
     */
    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }

        // Get widget settings
        $number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;
        $rank_by = ! empty( $instance['rank_by'] ) ? $instance['rank_by'] : 'overall';
        $display_style = ! empty( $instance['display_style'] ) ? $instance['display_style'] : 'list';
        $show_score = ! empty( $instance['show_score'] ) ? (bool) $instance['show_score'] : true;
        $show_avatar = ! empty( $instance['show_avatar'] ) ? (bool) $instance['show_avatar'] : true;
        $show_stats = ! empty( $instance['show_stats'] ) ? (bool) $instance['show_stats'] : false;
        $show_bio = ! empty( $instance['show_bio'] ) ? (bool) $instance['show_bio'] : false;
        $verified_only = ! empty( $instance['verified_only'] ) ? (bool) $instance['verified_only'] : true;
        
        // Get artist rankings
        $rankings = $this->get_artist_rankings( $number, $rank_by, $verified_only );
        
        if ( ! empty( $rankings ) ) {
            // Widget container
            $container_class = 'vortex-top-artists-container display-' . esc_attr( $display_style );
            echo '<div class="' . esc_attr( $container_class ) . '">';
            
            if ( $display_style === 'list' ) {
                $this->render_list_layout( $rankings, $show_avatar, $show_score, $show_stats, $show_bio );
            } elseif ( $display_style === 'grid' ) {
                $this->render_grid_layout( $rankings, $show_avatar, $show_score, $show_stats, $show_bio );
            } elseif ( $display_style === 'compact' ) {
                $this->render_compact_layout( $rankings, $show_avatar, $show_score );
            } elseif ( $display_style === 'leaderboard' ) {
                $this->render_leaderboard_layout( $rankings, $show_avatar, $show_score, $show_stats );
            }
            
            echo '</div>'; // End container
            
            // Last updated timestamp
            if ( ! empty( $instance['show_updated'] ) && $instance['show_updated'] ) {
                $updated_time = get_option( 'vortex_rankings_last_updated' );
                if ( $updated_time ) {
                    echo '<div class="vortex-rankings-updated">';
                    echo esc_html__( 'Rankings last updated:', 'vortex-ai-marketplace' ) . ' ';
                    echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $updated_time ) ) );
                    echo '</div>';
                }
            }
            
            // View all link
            if ( ! empty( $instance['show_view_all'] ) && $instance['show_view_all'] ) {
                echo '<div class="vortex-view-all-link">';
                echo '<a href="' . esc_url( get_post_type_archive_link( 'vortex_artist' ) ) . '">' . esc_html__( 'View All Artists', 'vortex-ai-marketplace' ) . '</a>';
                echo '</div>';
            }
            
        } else {
            echo '<p class="vortex-no-rankings">' . esc_html__( 'No artist rankings available yet.', 'vortex-ai-marketplace' ) . '</p>';
        }
        
        echo $args['after_widget'];
    }

    /**
     * Render artists in list layout.
     *
     * @since    1.0.0
     * @param    array    $rankings     The artist rankings data.
     * @param    bool     $show_avatar  Whether to show artist avatars.
     * @param    bool     $show_score   Whether to show ranking scores.
     * @param    bool     $show_stats   Whether to show artist stats.
     * @param    bool     $show_bio     Whether to show artist bio.
     */
    private function render_list_layout( $rankings, $show_avatar, $show_score, $show_stats, $show_bio ) {
        echo '<ul class="vortex-top-artists-list">';
        
        foreach ( $rankings as $rank => $artist ) {
            echo '<li class="vortex-top-artist-item rank-' . esc_attr( $rank ) . '">';
            
            // Rank number
            echo '<div class="vortex-artist-rank">' . esc_html( $rank ) . '</div>';
            
            // Artist avatar
            if ( $show_avatar && ! empty( $artist['avatar'] ) ) {
                echo '<div class="vortex-artist-avatar">';
                echo '<a href="' . esc_url( $artist['url'] ) . '">';
                echo '<img src="' . esc_url( $artist['avatar'] ) . '" alt="' . esc_attr( $artist['name'] ) . '" />';
                echo '</a>';
                echo '</div>';
            }
            
            // Artist info
            echo '<div class="vortex-artist-info">';
            
            // Artist name and verification
            echo '<h4 class="vortex-artist-name">';
            echo '<a href="' . esc_url( $artist['url'] ) . '">' . esc_html( $artist['name'] );
            if ( $artist['verified'] ) {
                echo ' <span class="vortex-verified-badge" title="' . esc_attr__( 'Verified Artist', 'vortex-ai-marketplace' ) . '">✓</span>';
            }
            echo '</a></h4>';
            
            // Artist score
            if ( $show_score && isset( $artist['score'] ) ) {
                echo '<div class="vortex-artist-score">';
                echo esc_html__( 'Score:', 'vortex-ai-marketplace' ) . ' <span>' . esc_html( number_format( $artist['score'], 1 ) ) . '</span>';
                echo '</div>';
            }
            
            // Artist bio
            if ( $show_bio && ! empty( $artist['bio'] ) ) {
                echo '<div class="vortex-artist-bio">' . wp_kses_post( $artist['bio'] ) . '</div>';
            }
            
            // Artist stats
            if ( $show_stats ) {
                echo '<div class="vortex-artist-stats">';
                
                if ( isset( $artist['artwork_count'] ) ) {
                    echo '<span class="vortex-stat">';
                    echo '<i class="dashicons dashicons-art"></i> ';
                    echo esc_html( $artist['artwork_count'] );
                    echo '</span>';
                }
                
                if ( isset( $artist['sales_count'] ) ) {
                    echo '<span class="vortex-stat">';
                    echo '<i class="dashicons dashicons-cart"></i> ';
                    echo esc_html( $artist['sales_count'] );
                    echo '</span>';
                }
                
                if ( isset( $artist['avg_rating'] ) ) {
                    echo '<span class="vortex-stat">';
                    echo '<i class="dashicons dashicons-star-filled"></i> ';
                    echo esc_html( number_format( $artist['avg_rating'], 1 ) );
                    echo '</span>';
                }
                
                echo '</div>';
            }
            
            echo '</div>'; // End info
            
            echo '</li>';
        }
        
        echo '</ul>';
    }

    /**
     * Render artists in grid layout.
     *
     * @since    1.0.0
     * @param    array    $rankings     The artist rankings data.
     * @param    bool     $show_avatar  Whether to show artist avatars.
     * @param    bool     $show_score   Whether to show ranking scores.
     * @param    bool     $show_stats   Whether to show artist stats.
     * @param    bool     $show_bio     Whether to show artist bio.
     */
    private function render_grid_layout( $rankings, $show_avatar, $show_score, $show_stats, $show_bio ) {
        echo '<div class="vortex-top-artists-grid">';
        
        foreach ( $rankings as $rank => $artist ) {
            echo '<div class="vortex-top-artist-item rank-' . esc_attr( $rank ) . '">';
            
            // Rank badge
            echo '<div class="vortex-artist-rank-badge">' . esc_html( $rank ) . '</div>';
            
            // Artist avatar
            if ( $show_avatar && ! empty( $artist['avatar'] ) ) {
                echo '<div class="vortex-artist-avatar">';
                echo '<a href="' . esc_url( $artist['url'] ) . '">';
                echo '<img src="' . esc_url( $artist['avatar'] ) . '" alt="' . esc_attr( $artist['name'] ) . '" />';
                echo '</a>';
                echo '</div>';
            }
            
            // Artist info
            echo '<div class="vortex-artist-info">';
            
            // Artist name and verification
            echo '<h4 class="vortex-artist-name">';
            echo '<a href="' . esc_url( $artist['url'] ) . '">' . esc_html( $artist['name'] );
            if ( $artist['verified'] ) {
                echo ' <span class="vortex-verified-badge" title="' . esc_attr__( 'Verified Artist', 'vortex-ai-marketplace' ) . '">✓</span>';
            }
            echo '</a></h4>';
            
            // Artist score
            if ( $show_score && isset( $artist['score'] ) ) {
                echo '<div class="vortex-artist-score">';
                echo esc_html__( 'Score:', 'vortex-ai-marketplace' ) . ' <span>' . esc_html( number_format( $artist['score'], 1 ) ) . '</span>';
                echo '</div>';
            }
            
            // Artist bio
            if ( $show_bio && ! empty( $artist['bio'] ) ) {
                echo '<div class="vortex-artist-bio">' . wp_kses_post( $artist['bio'] ) . '</div>';
            }
            
            // Artist stats
            if ( $show_stats ) {
                echo '<div class="vortex-artist-stats">';
                
                if ( isset( $artist['artwork_count'] ) ) {
                    echo '<span class="vortex-stat">';
                    echo '<i class="dashicons dashicons-art"></i> ';
                    echo esc_html( $artist['artwork_count'] );
                    echo '</span>';
                }
                
                if ( isset( $artist['sales_count'] ) ) {
                    echo '<span class="vortex-stat">';
                    echo '<i class="dashicons dashicons-cart"></i> ';
                    echo esc_html( $artist['sales_count'] );
                    echo '</span>';
                }
                
                if ( isset( $artist['avg_rating'] ) ) {
                    echo '<span class="vortex-stat">';
                    echo '<i class="dashicons dashicons-star-filled"></i> ';
                    echo esc_html( number_format( $artist['avg_rating'], 1 ) );
                    echo '</span>';
                }
                
                echo '</div>';
            }
            
            echo '</div>'; // End info
            
            echo '</div>'; // End item
        }
        
        echo '</div>';
    }

    /**
     * Render artists in compact layout.
     *
     * @since    1.0.0
     * @param    array    $rankings     The artist rankings data.
     * @param    bool     $show_avatar  Whether to show artist avatars.
     * @param    bool     $show_score   Whether to show ranking scores.
     */
    private function render_compact_layout( $rankings, $show_avatar, $show_score ) {
        echo '<div class="vortex-top-artists-compact">';
        
        foreach ( $rankings as $rank => $artist ) {
            echo '<div class="vortex-top-artist-item rank-' . esc_attr( $rank ) . '">';
            
            // Rank number
            echo '<div class="vortex-artist-rank">' . esc_html( $rank ) . '</div>';
            
            // Artist avatar
            if ( $show_avatar && ! empty( $artist['avatar'] ) ) {
                echo '<div class="vortex-artist-avatar">';
                echo '<a href="' . esc_url( $artist['url'] ) . '">';
                echo '<img src="' . esc_url( $artist['avatar'] ) . '" alt="' . esc_attr( $artist['name'] ) . '" />';
                echo '</a>';
                echo '</div>';
            }
            
            // Artist name
            echo '<div class="vortex-artist-name">';
            echo '<a href="' . esc_url( $artist['url'] ) . '">' . esc_html( $artist['name'] );
            if ( $artist['verified'] ) {
                echo ' <span class="vortex-verified-badge" title="' . esc_attr__( 'Verified Artist', 'vortex-ai-marketplace' ) . '">✓</span>';
            }
            echo '</a>';
            echo '</div>';
            
            // Artist score
            if ( $show_score && isset( $artist['score'] ) ) {
                echo '<div class="vortex-artist-score">' . esc_html( number_format( $artist['score'], 1 ) ) . '</div>';
            }
            
            echo '</div>'; // End item
        }
        
        echo '</div>';
    }

    /**
     * Render artists in leaderboard layout.
     *
     * @since    1.0.0
     * @param    array    $rankings     The artist rankings data.
     * @param    bool     $show_avatar  Whether to show artist avatars.
     * @param    bool     $show_score   Whether to show ranking scores.
     * @param    bool     $show_stats   Whether to show artist stats.
     */
    private function render_leaderboard_layout( $rankings, $show_avatar, $show_score, $show_stats ) {
        echo '<div class="vortex-top-artists-leaderboard">';
        
        // Table header
        echo '<div class="vortex-leaderboard-header">';
        echo '<div class="vortex-leaderboard-rank">' . esc_html__( 'Rank', 'vortex-ai-marketplace' ) . '</div>';
        echo '<div class="vortex-leaderboard-artist">' . esc_html__( 'Artist', 'vortex-ai-marketplace' ) . '</div>';
        
        if ( $show_stats ) {
            echo '<div class="vortex-leaderboard-artworks">' . esc_html__( 'Artworks', 'vortex-ai-marketplace' ) . '</div>';
            echo '<div class="vortex-leaderboard-sales">' . esc_html__( 'Sales', 'vortex-ai-marketplace' ) . '</div>';
        }
        
        if ( $show_score ) {
            echo '<div class="vortex-leaderboard-score">' . esc_html__( 'Score', 'vortex-ai-marketplace' ) . '</div>';
        }
        
        echo '</div>'; // End header
        
        // Table rows
        foreach ( $rankings as $rank => $artist ) {
            $row_class = 'vortex-leaderboard-row';
            if ( $rank <= 3 ) {
                $row_class .= ' top-rank rank-' . esc_attr( $rank );
            }
            
            echo '<div class="' . esc_attr( $row_class ) . '">';
            
            // Rank
            echo '<div class="vortex-leaderboard-rank">' . esc_html( $rank ) . '</div>';
            
            // Artist
            echo '<div class="vortex-leaderboard-artist">';
            
            if ( $show_avatar && ! empty( $artist['avatar'] ) ) {
                echo '<div class="vortex-artist-avatar">';
                echo '<a href="' . esc_url( $artist['url'] ) . '">';
                echo '<img src="' . esc_url( $artist['avatar'] ) . '" alt="' . esc_attr( $artist['name'] ) . '" />';
                echo '</a>';
                echo '</div>';
            }
            
            echo '<div class="vortex-artist-name">';
            echo '<a href="' . esc_url( $artist['url'] ) . '">' . esc_html( $artist['name'] );
            if ( $artist['verified'] ) {
                echo ' <span class="vortex-verified-badge" title="' . esc_attr__( 'Verified Artist', 'vortex-ai-marketplace' ) . '">✓</span>';
            }
            echo '</a>';
            echo '</div>';
            
            echo '</div>'; // End artist
            
            // Stats
            if ( $show_stats ) {
                echo '<div class="vortex-leaderboard-artworks">' . esc_html( $artist['artwork_count'] ) . '</div>';
                echo '<div class="vortex-leaderboard-sales">' . esc_html( $artist['sales_count'] ) . '</div>';
            }
            
            // Score
            if ( $show_score && isset( $artist['score'] ) ) {
                echo '<div class="vortex-leaderboard-score">' . esc_html( number_format( $artist['score'], 1 ) ) . '</div>';
            }
            
            echo '</div>'; // End row
        }
        
        echo '</div>'; // End leaderboard
    }

    /**
     * Get artist rankings data.
     *
     * @since    1.0.0
     * @param    int       $number         Number of artists to get.
     * @param    string    $rank_by        Ranking criteria.
     * @param    bool      $verified_only  Whether to get only verified artists.
     * @return   array                     Artist rankings data.
     */
    private function get_artist_rankings( $number, $rank_by, $verified_only ) {
        global $wpdb;
        
        $rankings = array();
        $rankings_table = $wpdb->prefix . 'vortex_rankings';
        
        // Check if rankings table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$rankings_table'" ) !== $rankings_table ) {
            // Fallback to meta queries if rankings table doesn't exist
            return $this->get_artists_fallback( $number, $rank_by, $verified_only );
        }
        
        // Query based on ranking criteria
        switch ( $rank_by ) {
            case 'overall':
                // Default ranking from rankings table
                $query = "SELECT r.artist_id, r.score, a.post_title as name 
                         FROM $rankings_table r 
                         JOIN {$wpdb->posts} a ON r.artist_id = a.ID 
                         WHERE a.post_type = 'vortex_artist' AND a.post_status = 'publish'";
                
                if ( $verified_only ) {
                    $query .= " AND EXISTS (
                        SELECT 1 FROM {$wpdb->postmeta} pm 
                        WHERE pm.post_id = a.ID 
                        AND pm.meta_key = '_vortex_artist_verified' 
                        AND pm.meta_value = '1'
                    )";
                }
                
                $query .= " ORDER BY r.score DESC LIMIT %d";
                
                $results = $wpdb->get_results( $wpdb->prepare( $query, $number ), ARRAY_A );
                break;
                
            case 'sales':
                // Ranking by sales amount
                $sales_table = $wpdb->prefix . 'vortex_sales';
                
                // Check if sales table exists
                if ( $wpdb->get_var( "SHOW TABLES LIKE '$sales_table'" ) !== $sales_table ) {
                    return $this->get_artists_fallback( $number, 'overall', $verified_only );
                }
                
                $query = "SELECT a.ID as artist_id, a.post_title as name, 
                         SUM(s.amount) as total_sales 
                         FROM {$wpdb->posts} a 
                         JOIN {$wpdb->postmeta} pm ON a.ID = pm.post_id AND pm.meta_key = '_vortex_artist_user_id' 
                         JOIN {$wpdb->posts} artwork ON artwork.post_author = pm.meta_value AND artwork.post_type = 'vortex_artwork' 
                         JOIN $sales_table s ON s.artwork_id = artwork.ID 
                         WHERE a.post_type = 'vortex_artist' AND a.post_status = 'publish'";
                
                if ( $verified_only ) {
                    $query .= " AND EXISTS (
                        SELECT 1 FROM {$wpdb->postmeta} pm2 
                        WHERE pm2.post_id = a.ID 
                        AND pm2.meta_key = '_vortex_artist_verified' 
                        AND pm2.meta_value = '1'
                    )";
                }
                
                $query .= " GROUP BY a.ID, a.post_title ORDER BY total_sales DESC LIMIT %d";
                
                $results = $wpdb->get_results( $wpdb->prepare( $query, $number ), ARRAY_A );
                break;
                
            case 'popularity':
                // Ranking by view count
                $query = "SELECT a.ID as artist_id, a.post_title as name, 
                         IFNULL(pm_views.meta_value, 0) as view_count 
                         FROM {$wpdb->posts} a 
                         LEFT JOIN {$wpdb->postmeta} pm_views ON a.ID = pm_views.post_id AND pm_views.meta_key = '_vortex_artist_view_count' 
                         WHERE a.post_type = 'vortex_artist' AND a.post_status = 'publish'";
                
                if ( $verified_only ) {
                    $query .= " AND EXISTS (
                        SELECT 1 FROM {$wpdb->postmeta} pm 
                        WHERE pm.post_id = a.ID 
                        AND pm.meta_key = '_vortex_artist_verified' 
                        AND pm.meta_value = '1'
                    )";
                }
                
                $query .= " ORDER BY view_count DESC LIMIT %d";
                
                $results = $wpdb->get_results( $wpdb->prepare( $query, $number ), ARRAY_A );
                break;
                
            case 'artwork_count':
                // Ranking by number of artworks
                $query = "SELECT a.ID as artist_id, a.post_title as name, 
                         COUNT(artwork.ID) as artwork_count 
                         FROM {$wpdb->posts} a 
                         JOIN {$wpdb->postmeta} pm ON a.ID = pm.post_id AND pm.meta_key = '_vortex_artist_user_id' 
                         JOIN {$wpdb->posts} artwork ON artwork.post_author = pm.meta_value 
                            AND artwork.post_type = 'vortex_artwork' 
                            AND artwork.post_status = 'publish' 
                         WHERE a.post_type = 'vortex_artist' AND a.post_status = 'publish'";
                
                if ( $verified_only ) {
                    $query .= " AND EXISTS (
                        SELECT 1 FROM {$wpdb->postmeta} pm2 
                        WHERE pm2.post_id = a.ID 
                        AND pm2.meta_key = '_vortex_artist_verified' 
                        AND pm2.meta_value = '1'
                    )";
                }
                
                $query .= " GROUP BY a.ID, a.post_title ORDER BY artwork_count DESC LIMIT %d";
                
                $results = $wpdb->get_results( $wpdb->prepare( $query, $number ), ARRAY_A );
                break;
                
            default:
                // Default to overall ranking
                return $this->get_artist_rankings( $number, 'overall', $verified_only );
        }
        
        if ( empty( $results ) ) {
            return array();
        }
        
        // Process results
        $rank = 1;
        foreach ( $results as $result ) {
            $artist_id = $result['artist_id'];
            
            // Get artist data
            $avatar = get_the_post_thumbnail_url( $artist_id, 'thumbnail' );
            $verified = (bool) get_post_meta( $artist_id, '_vortex_artist_verified', true );
            $artist_user_id = get_post_meta( $artist_id, '_vortex_artist_user_id', true );
            
            // Get artwork count
            $artwork_count = 0;
            if ( $artist_user_id ) {
                $artwork_count = count_user_posts( $artist_user_id, 'vortex_artwork' );
            }
            
            // Get sales count
            $sales_count = $this->get_artist_sales_count( $artist_user_id );
            
            // Get average rating
            $avg_rating = get_post_meta( $artist_id, '_vortex_artist_avg_rating', true );
            if ( empty( $avg_rating ) ) {
                $avg_rating = 0;
            }
            
            // Get score
            $score = isset( $result['score'] ) ? $result['score'] : 
                   (isset( $result['total_sales'] ) ? $result['total_sales'] : 
                   (isset( $result['view_count'] ) ? $result['view_count'] : 
                   (isset( $result['artwork_count'] ) ? $result['artwork_count'] * 10 : 0)));
            
            // Get bio
            $artist = get_post( $artist_id );
            $bio = $artist ? wp_trim_words( $artist->post_content, 20 ) : '';
            
            $rankings[$rank] = array(
                'id'            => $artist_id,
                'name'          => $result['name'],
                'url'           => get_permalink( $artist_id ),
                'avatar'        => $avatar ? $avatar : plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/images/default-avatar.png',
                'verified'      => $verified,
                'score'         => $score,
                'artwork_count' => $artwork_count,
                'sales_count'   => $sales_count,
                'avg_rating'    => $avg_rating,
                'bio'           => $bio,
            );
            
            $rank++;
        }
        
        return $rankings;
    }

    /**
     * Get artist rankings when rankings table is not available.
     *
     * @since    1.0.0
     * @param    int       $number         Number of artists to get.
     * @param    string    $rank_by        Ranking criteria.
     * @param    bool      $verified_only  Whether to get only verified artists.
     * @return   array                     Artist rankings data.
     */
    private function get_artists_fallback( $number, $rank_by, $verified_only ) {
        // Build query args
        $args = array(
            'post_type'      => 'vortex_artist',
            'posts_per_page' => $number,
            'post_status'    => 'publish',
        );
        
        // Add meta query for verified only
        if ( $verified_only ) {
            $args['meta_query'][] = array(
                'key'     => '_vortex_artist_verified',
                'value'   => '1',
                'compare' => '=',
            );
        }
        
        // Set orderby based on ranking criteria
        switch ( $rank_by ) {
            case 'sales':
                $args['meta_key'] = '_vortex_ranking_sales_score';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
                
            case 'popularity':
                $args['meta_key'] = '_vortex_artist_view_count';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
                
            case 'overall':
            default:
                $args['meta_key'] = '_vortex_ranking_total_score';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
        }
        
        $artists_query = new WP_Query( $args );
        $rankings = array();
        
        if ( $artists_query->have_posts() ) {
            $rank = 1;
            
            while ( $artists_query->have_posts() ) {
                $artists_query->the_post();
                
                $artist_id = get_the_ID();
                $avatar = get_the_post_thumbnail_url( $artist_id, 'thumbnail' );
                $verified = (bool) get_post_meta( $artist_id, '_vortex_artist_verified', true );
                $artist_user_id = get_post_meta( $artist_id, '_vortex_artist_user_id', true );
                
                // Get artwork count
                $artwork_count = 0;
                if ( $artist_user_id ) {
                    $artwork_count = count_user_posts( $artist_user_id, 'vortex_artwork' );
                }
                
                // Get sales count
                $sales_count = $this->get_artist_sales_count( $artist_user_id );
                
                // Get score based on ranking type
                switch ( $rank_by ) {
                    case 'sales':
                        $score = get_post_meta( $artist_id, '_vortex_ranking_sales_score', true );
                        break;
                        
                    case 'popularity':
                        $score = get_post_meta( $artist_id, '_vortex_artist_view_count', true );
                        break;
                        
                    case 'overall':
                    default:
                        $score = get_post_meta( $artist_id, '_vortex_ranking_total_score', true );
                        break;
                }
                
                if ( empty( $score ) ) {
                    $score = 0;
                }
                
                // Get average rating
                $avg_rating = get_post_meta( $artist_id, '_vortex_artist_avg_rating', true );
                if ( empty( $avg_rating ) ) {
                    $avg_rating = 0;
                }
                
                // Get bio
                $bio = wp_trim_words( get_the_content(), 20 );
                
                $rankings[$rank] = array(
                    'id'            => $artist_id,
                    'name'          => get_the_title(),
                    'url'           => get_permalink(),
                    'avatar'        => $avatar ? $avatar : plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/images/default-avatar.png',
                    'verified'      => $verified,
                    'score'         => $score,
                    'artwork_count' => $artwork_count,
                    'sales_count'   => $sales_count,
                    'avg_rating'    => $avg_rating,
                    'bio'           => $bio,
                );
                
                $rank++;
            }
            
            wp_reset_postdata();
        }
        
        return $rankings;
    }

    /**
     * Get sales count for an artist.
     *
     * @since    1.0.0
     * @param    int       $artist_user_id    The artist's user ID.
     * @return   int                         Sales count.
     */
    private function get_artist_sales_count( $artist_user_id ) {
        global $wpdb;
        
        if ( ! $artist_user_id ) {
            return 0;
        }
        
        $sales_table = $wpdb->prefix . 'vortex_sales';
        
        // Check if sales table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$sales_table'" ) !== $sales_table ) {
            return 0;
        }
        
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $sales_table s
             JOIN {$wpdb->posts} p ON s.artwork_id = p.ID
             WHERE p.post_author = %d",
            $artist_user_id
        ) );
        
        return intval( $count );
    }

    /**
     * Back-end widget form.
     *
     * @since    1.0.0
     * @param    array    $instance    Previously saved values from database.
     * @return   void
     */
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Top Artists', 'vortex-ai-marketplace' );
        $number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;
        $rank_by = ! empty( $instance['rank_by'] ) ? $instance['rank_by'] : 'overall';
        $display_style = ! empty( $instance['display_style'] ) ? $instance['display_style'] : 'list';
        $show_score = isset( $instance['show_score'] ) ? (bool) $instance['show_score'] : true;
        $show_avatar = isset( $instance['show_avatar']