<?php
/**
 * The Artist Widget functionality.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/widgets
 */

/**
 * The Artist Widget functionality.
 *
 * Displays artists in a widget area with customizable display options.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/widgets
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Artist_Widget extends WP_Widget {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        parent::__construct(
            'vortex_artist_widget', // Base ID
            __( 'VORTEX Artists', 'vortex-ai-marketplace' ), // Name
            array(
                'description' => __( 'Display artists from the marketplace.', 'vortex-ai-marketplace' ),
                'classname'   => 'vortex-artist-widget',
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
        register_widget( 'Vortex_Artist_Widget' );
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
                'vortex-artist-widget',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/css/vortex-marketplace.css',
                array(),
                VORTEX_VERSION,
                'all'
            );
            
            wp_enqueue_script(
                'vortex-artist-widget',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/js/vortex-marketplace.js',
                array( 'jquery' ),
                VORTEX_VERSION,
                true
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
        $number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 3;
        $columns = ! empty( $instance['columns'] ) ? absint( $instance['columns'] ) : 1;
        $display_style = ! empty( $instance['display_style'] ) ? $instance['display_style'] : 'grid';
        $show_bio = ! empty( $instance['show_bio'] ) ? (bool) $instance['show_bio'] : true;
        $show_social = ! empty( $instance['show_social'] ) ? (bool) $instance['show_social'] : false;
        $show_artwork_count = ! empty( $instance['show_artwork_count'] ) ? (bool) $instance['show_artwork_count'] : true;
        $verified_only = ! empty( $instance['verified_only'] ) ? (bool) $instance['verified_only'] : false;
        $orderby = ! empty( $instance['orderby'] ) ? $instance['orderby'] : 'name';
        
        // Query args
        $args = array(
            'post_type'      => 'vortex_artist',
            'posts_per_page' => $number,
            'post_status'    => 'publish',
        );
        
        // Verified artists only
        if ( $verified_only ) {
            $args['meta_query'][] = array(
                'key'     => '_vortex_artist_verified',
                'value'   => '1',
                'compare' => '=',
            );
        }
        
        // Order by
        switch ( $orderby ) {
            case 'name':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
                
            case 'popular':
                $args['meta_key'] = '_vortex_artist_view_count';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
                
            case 'artwork_count':
                // We'll sort by artwork count after getting the results
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
                
            case 'ranking':
                // Use the ranking table via a JOIN in a custom query later
                $args['meta_key'] = '_vortex_ranking_total_score';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
                
            case 'random':
                $args['orderby'] = 'rand';
                break;
                
            case 'date':
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
        }
        
        // Query artists
        $query = new WP_Query( $args );
        
        // If ordering by artwork count, we need to sort manually
        if ( $orderby === 'artwork_count' && $query->have_posts() ) {
            $artists_with_counts = array();
            
            foreach ( $query->posts as $artist_post ) {
                $artist_user_id = get_post_meta( $artist_post->ID, '_vortex_artist_user_id', true );
                $artwork_count = 0;
                
                if ( $artist_user_id ) {
                    $artwork_count = count_user_posts( $artist_user_id, 'vortex_artwork' );
                }
                
                $artists_with_counts[$artist_post->ID] = $artwork_count;
            }
            
            // Sort by artwork count
            arsort( $artists_with_counts );
            
            // Reorder posts
            $sorted_posts = array();
            foreach ( array_keys( $artists_with_counts ) as $artist_id ) {
                foreach ( $query->posts as $post ) {
                    if ( $post->ID === $artist_id ) {
                        $sorted_posts[] = $post;
                        break;
                    }
                }
            }
            
            $query->posts = $sorted_posts;
        }
        
        if ( $query->have_posts() ) {
            // Widget container class based on display style
            $container_class = 'vortex-artists-container';
            if ( $display_style === 'grid' ) {
                $container_class .= ' vortex-artists-grid columns-' . esc_attr( $columns );
            } else {
                $container_class .= ' vortex-artists-list';
            }
            
            echo '<div class="' . esc_attr( $container_class ) . '">';
            
            while ( $query->have_posts() ) {
                $query->the_post();
                $artist_id = get_the_ID();
                
                // Get artist meta
                $artist_user_id = get_post_meta( $artist_id, '_vortex_artist_user_id', true );
                $verified = get_post_meta( $artist_id, '_vortex_artist_verified', true );
                $specialties = '';
                $social_media = array();
                
                if ( $artist_user_id ) {
                    $specialties = get_user_meta( $artist_user_id, '_vortex_artist_specialties', true );
                    $social_media = get_user_meta( $artist_user_id, '_vortex_artist_social_media', true );
                    
                    if ( ! is_array( $social_media ) ) {
                        $social_media = array();
                    }
                }
                
                // Get artwork count
                $artwork_count = 0;
                if ( $artist_user_id ) {
                    $artwork_count = count_user_posts( $artist_user_id, 'vortex_artwork' );
                }
                
                // Artist container
                echo '<div class="vortex-artist-item">';
                
                // Artist image
                echo '<div class="vortex-artist-avatar">';
                echo '<a href="' . esc_url( get_permalink() ) . '">';
                if ( has_post_thumbnail() ) {
                    the_post_thumbnail( 'thumbnail' );
                } else {
                    echo '<img src="' . esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/images/default-avatar.png' ) . '" alt="' . esc_attr( get_the_title() ) . '" />';
                }
                echo '</a>';
                echo '</div>'; // End avatar
                
                // Artist info
                echo '<div class="vortex-artist-info">';
                
                // Name and verification badge
                echo '<h3 class="vortex-artist-name">';
                echo '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() );
                if ( $verified ) {
                    echo ' <span class="vortex-verified-badge" title="' . esc_attr__( 'Verified Artist', 'vortex-ai-marketplace' ) . '">✓</span>';
                }
                echo '</a></h3>';
                
                // Specialties
                if ( ! empty( $specialties ) ) {
                    echo '<div class="vortex-artist-specialties">' . esc_html( $specialties ) . '</div>';
                }
                
                // Bio excerpt
                if ( $show_bio ) {
                    $bio = get_the_excerpt();
                    if ( ! empty( $bio ) ) {
                        echo '<div class="vortex-artist-bio">' . wp_kses_post( $bio ) . '</div>';
                    }
                }
                
                // Artwork count
                if ( $show_artwork_count ) {
                    echo '<div class="vortex-artist-artwork-count">';
                    echo esc_html( sprintf( 
                        _n( '%s Artwork', '%s Artworks', $artwork_count, 'vortex-ai-marketplace' ), 
                        number_format_i18n( $artwork_count ) 
                    ) );
                    echo '</div>';
                }
                
                // Social media links
                if ( $show_social && ! empty( $social_media ) ) {
                    echo '<div class="vortex-artist-social">';
                    
                    foreach ( $social_media as $platform => $url ) {
                        if ( ! empty( $url ) ) {
                            // Determine icon class based on platform
                            $icon_class = 'dashicons';
                            switch ( $platform ) {
                                case 'twitter':
                                    $icon_class .= ' dashicons-twitter';
                                    break;
                                case 'instagram':
                                    $icon_class .= ' dashicons-instagram';
                                    break;
                                case 'facebook':
                                    $icon_class .= ' dashicons-facebook';
                                    break;
                                case 'deviantart':
                                    $icon_class .= ' dashicons-art';
                                    break;
                                case 'behance':
                                    $icon_class .= ' dashicons-portfolio';
                                    break;
                                default:
                                    $icon_class .= ' dashicons-admin-links';
                            }
                            
                            echo '<a href="' . esc_url( $url ) . '" target="_blank" class="vortex-social-link" title="' . esc_attr( ucfirst( $platform ) ) . '">';
                            echo '<span class="' . esc_attr( $icon_class ) . '"></span>';
                            echo '</a>';
                        }
                    }
                    
                    echo '</div>';
                }
                
                echo '</div>'; // End info
                
                echo '</div>'; // End artist container
            }
            
            echo '</div>'; // End widget container
            
            // Add "View All" link if enabled
            if ( ! empty( $instance['show_view_all'] ) && $instance['show_view_all'] ) {
                echo '<div class="vortex-view-all-link">';
                echo '<a href="' . esc_url( get_post_type_archive_link( 'vortex_artist' ) ) . '">' . esc_html__( 'View All Artists', 'vortex-ai-marketplace' ) . '</a>';
                echo '</div>';
            }
            
            wp_reset_postdata();
        } else {
            // No artists found
            echo '<p class="vortex-no-artists">' . esc_html__( 'No artists found.', 'vortex-ai-marketplace' ) . '</p>';
        }
        
        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @since    1.0.0
     * @param    array    $instance    Previously saved values from database.
     * @return   void
     */
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Featured Artists', 'vortex-ai-marketplace' );
        $number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 3;
        $columns = ! empty( $instance['columns'] ) ? absint( $instance['columns'] ) : 1;
        $display_style = ! empty( $instance['display_style'] ) ? $instance['display_style'] : 'grid';
        $show_bio = isset( $instance['show_bio'] ) ? (bool) $instance['show_bio'] : true;
        $show_social = isset( $instance['show_social'] ) ? (bool) $instance['show_social'] : false;
        $show_artwork_count = isset( $instance['show_artwork_count'] ) ? (bool) $instance['show_artwork_count'] : true;
        $verified_only = isset( $instance['verified_only'] ) ? (bool) $instance['verified_only'] : false;
        $show_view_all = isset( $instance['show_view_all'] ) ? (bool) $instance['show_view_all'] : true;
        $orderby = ! empty( $instance['orderby'] ) ? $instance['orderby'] : 'name';
        ?>
        
        <!-- Title -->
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'vortex-ai-marketplace' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        
        <!-- Number of artists -->
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_html_e( 'Number of artists to show:', 'vortex-ai-marketplace' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="number" step="1" min="1" max="12" value="<?php echo esc_attr( $number ); ?>" size="3">
        </p>
        
        <!-- Display style -->
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'display_style' ) ); ?>"><?php esc_html_e( 'Display Style:', 'vortex-ai-marketplace' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'display_style' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'display_style' ) ); ?>">
                <option value="grid" <?php selected( $display_style, 'grid' ); ?>><?php esc_html_e( 'Grid', 'vortex-ai-marketplace' ); ?></option>
                <option value="list" <?php selected( $display_style, 'list' ); ?>><?php esc_html_e( 'List', 'vortex-ai-marketplace' ); ?></option>
            </select>
        </p>
        
        <!-- Columns (only relevant for grid style) -->
        <p class="vortex-columns-option" style="<?php echo $display_style === 'list' ? 'display: none;' : ''; ?>">
            <label for="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>"><?php esc_html_e( 'Columns:', 'vortex-ai-marketplace' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'columns' ) ); ?>">
                <option value="1" <?php selected( $columns, 1 ); ?>><?php esc_html_e( '1 Column', 'vortex-ai-marketplace' ); ?></option>
                <option value="2" <?php selected( $columns, 2 ); ?>><?php esc_html_e( '2 Columns', 'vortex-ai-marketplace' ); ?></option>
                <option value="3" <?php selected( $columns, 3 ); ?>><?php esc_html_e( '3 Columns', 'vortex-ai-marketplace' ); ?></option>
            </select>
        </p>
        
        <!-- Order by -->
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>"><?php esc_html_e( 'Order By:', 'vortex-ai-marketplace' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>">
                <option value="name" <?php selected( $orderby, 'name' ); ?>><?php esc_html_e( 'Name', 'vortex-ai-marketplace' ); ?></option>
                <option value="date" <?php selected( $orderby, 'date' ); ?>><?php esc_html_e( 'Registration Date', 'vortex-ai-marketplace' ); ?></option>
                <option value="popular" <?php selected( $orderby, 'popular' ); ?>><?php esc_html_e( 'Popularity (views)', 'vortex-ai-marketplace' ); ?></option>
                <option value="artwork_count" <?php selected( $orderby, 'artwork_count' ); ?>><?php esc_html_e( 'Artwork Count', 'vortex-ai-marketplace' ); ?></option>
                <option value="ranking" <?php selected( $orderby, 'ranking' ); ?>><?php esc_html_e( 'Artist Ranking', 'vortex-ai-marketplace' ); ?></option>
                <option value="random" <?php selected( $orderby, 'random' ); ?>><?php esc_html_e( 'Random', 'vortex-ai-marketplace' ); ?></option>
            </select>
        </p>
        
        <!-- Display options -->
        <p>
            <input class="checkbox" type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'verified_only' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'verified_only' ) ); ?>" <?php checked( $verified_only ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'verified_only' ) ); ?>"><?php esc_html_e( 'Show only verified artists', 'vortex-ai-marketplace' ); ?></label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_bio' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_bio' ) ); ?>" <?php checked( $show_bio ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_bio' ) ); ?>"><?php esc_html_e( 'Show bio excerpt', 'vortex-ai-marketplace' ); ?></label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_social' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_social' ) ); ?>" <?php checked( $show_social ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_social' ) ); ?>"><?php esc_html_e( 'Show social media links', 'vortex-ai-marketplace' ); ?></label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_artwork_count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_artwork_count' ) ); ?>" <?php checked( $show_artwork_count ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_artwork_count' ) ); ?>"><?php esc_html_e( 'Show artwork count', 'vortex-ai-marketplace' ); ?></label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_view_all' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_view_all' ) ); ?>" <?php checked( $show_view_all ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_view_all' ) ); ?>"><?php esc_html_e( 'Show "View All" link', 'vortex-ai-marketplace' ); ?></label>
        </p>
        
        <script>
            jQuery(document).ready(function($) {
                $('#<?php echo esc_attr( $this->get_field_id( 'display_style' ) ); ?>').on('change', function() {
                    if ($(this).val() === 'list') {
                        $('.vortex-columns-option').hide();
                    } else {
                        $('.vortex-columns-option').show();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @since    1.0.0
     * @param    array    $new_instance    Values just sent to be saved.
     * @param    array    $old_instance    Previously saved values from database.
     * @return   array                     Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        
        $instance['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['number'] = ! empty( $new_instance['number'] ) ? absint( $new_instance['number'] ) : 3;
        $instance['columns'] = ! empty( $new_instance['columns'] ) ? absint( $new_instance['columns'] ) : 1;
        $instance['display_style'] = ! empty( $new_instance['display_style'] ) ? sanitize_text_field( $new_instance['display_style'] ) : 'grid';
        $instance['show_bio'] = isset( $new_instance['show_bio'] ) ? (bool) $new_instance['show_bio'] : false;
        $instance['show_social'] = isset( $new_instance['show_social'] ) ? (bool) $new_instance['show_social'] : false;
        $instance['show_artwork_count'] = isset( $new_instance['show_artwork_count'] ) ? (bool) $new_instance['show_artwork_count'] : false;
        $instance['verified_only'] = isset( $new_instance['verified_only'] ) ? (bool) $new_instance['verified_only'] : false;
        $instance['show_view_all'] = isset( $new_instance['show_view_all'] ) ? (bool) $new_instance['show_view_all'] : false;
        $instance['orderby'] = ! empty( $new_instance['orderby'] ) ? sanitize_text_field( $new_instance['orderby'] ) : 'name';
        
        return $instance;
    }

    /**
     * Get top artists for display.
     * 
     * This static method can be used outside the widget for direct access to top artists.
     *
     * @since    1.0.0
     * @param    int      $count           Number of artists to retrieve.
     * @param    bool     $verified_only   Whether to only include verified artists.
     * @param    string   $orderby         How to order the results.
     * @return   array                     Array of artist data.
     */
    public static function get_top_artists( $count = 5, $verified_only = true, $orderby = 'ranking' ) {
        $args = array(
            'post_type'      => 'vortex_artist',
            'posts_per_page' => absint( $count ),
            'post_status'    => 'publish',
        );
        
        // Verified only filter
        if ( $verified_only ) {
            $args['meta_query'][] = array(
                'key'     => '_vortex_artist_verified',
                'value'   => '1',
                'compare' => '=',
            );
        }
        
        // Order by
        switch ( $orderby ) {
            case 'name':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
                
            case 'popular':
                $args['meta_key'] = '_vortex_artist_view_count';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
                
            case 'date':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
                
            case 'random':
                $args['orderby'] = 'rand';
                break;
                
            case 'ranking':
            default:
                $args['meta_key'] = '_vortex_ranking_total_score';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
        }
        
        $artists = array();
        $query = new WP_Query( $args );
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                
                $artist_id = get_the_ID();
                $artist_user_id = get_post_meta( $artist_id, '_vortex_artist_user_id', true );
                $verified = (bool) get_post_meta( $artist_id, '_vortex_artist_verified', true );
                
                // Get artwork count
                $artwork_count = 0;
                if ( $artist_user_id ) {
                    $artwork_count = count_user_posts( $artist_user_id, 'vortex_artwork' );
                }
                
                $artists[] = array(
                    'id'            => $artist_id,
                    'title'         => get_the_title(),
                    'permalink'     => get_permalink(),
                    'thumbnail'     => get_the_post_thumbnail_url( $artist_id, 'thumbnail' ),
                    'excerpt'       => get_the_excerpt(),
                    'verified'      => $verified,
                    'artwork_count' => $artwork_count,
                    'user_id'       => $artist_user_id,
                );
            }
            
            wp_reset_postdata();
        }
        
        return $artists;
    }
} 