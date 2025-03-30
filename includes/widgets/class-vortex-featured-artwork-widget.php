<?php
/**
 * The Featured Artwork Widget functionality.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/widgets
 */

/**
 * The Featured Artwork Widget functionality.
 *
 * Displays featured artworks in a widget area with customizable display options.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/widgets
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Featured_Artwork_Widget extends WP_Widget {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        parent::__construct(
            'vortex_featured_artwork', // Base ID
            __( 'VORTEX Featured Artwork', 'vortex-ai-marketplace' ), // Name
            array(
                'description' => __( 'Display featured artworks from the marketplace.', 'vortex-ai-marketplace' ),
                'classname'   => 'vortex-featured-artwork-widget',
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
        register_widget( 'Vortex_Featured_Artwork_Widget' );
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
                'vortex-featured-artwork-widget',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/css/vortex-marketplace.css',
                array(),
                VORTEX_VERSION,
                'all'
            );
            
            wp_enqueue_script(
                'vortex-featured-artwork-widget',
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
        $show_price = ! empty( $instance['show_price'] ) ? (bool) $instance['show_price'] : true;
        $show_artist = ! empty( $instance['show_artist'] ) ? (bool) $instance['show_artist'] : true;
        $featured_only = ! empty( $instance['featured_only'] ) ? (bool) $instance['featured_only'] : false;
        $ai_only = ! empty( $instance['ai_only'] ) ? (bool) $instance['ai_only'] : false;
        $category = ! empty( $instance['category'] ) ? $instance['category'] : '';
        $orderby = ! empty( $instance['orderby'] ) ? $instance['orderby'] : 'date';
        
        // Query args
        $args = array(
            'post_type'      => 'vortex_artwork',
            'posts_per_page' => $number,
            'post_status'    => 'publish',
        );
        
        // Featured only
        if ( $featured_only ) {
            $args['meta_query'][] = array(
                'key'     => '_vortex_artwork_featured',
                'value'   => '1',
                'compare' => '=',
            );
        }
        
        // AI-generated only
        if ( $ai_only ) {
            $args['meta_query'][] = array(
                'key'     => '_vortex_created_with_huraii',
                'value'   => '1',
                'compare' => '=',
            );
        }
        
        // Filter by category
        if ( ! empty( $category ) ) {
            $args['tax_query'][] = array(
                'taxonomy' => 'art_category',
                'field'    => 'slug',
                'terms'    => $category,
            );
        }
        
        // Order by
        switch ( $orderby ) {
            case 'price':
                $args['meta_key'] = '_vortex_artwork_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
                
            case 'price-desc':
                $args['meta_key'] = '_vortex_artwork_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
                
            case 'title':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
                
            case 'views':
                $args['meta_key'] = '_vortex_view_count';
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
        
        // Query artworks
        $query = new WP_Query( $args );
        
        if ( $query->have_posts() ) {
            // Widget container
            echo '<div class="vortex-featured-artwork-container columns-' . esc_attr( $columns ) . '">';
            
            while ( $query->have_posts() ) {
                $query->the_post();
                $artwork_id = get_the_ID();
                
                // Get artwork meta
                $price = get_post_meta( $artwork_id, '_vortex_artwork_price', true );
                $on_sale = get_post_meta( $artwork_id, '_vortex_artwork_on_sale', true );
                $sale_price = $on_sale ? get_post_meta( $artwork_id, '_vortex_artwork_sale_price', true ) : '';
                $tola_price = get_post_meta( $artwork_id, '_vortex_tola_price', true );
                $ai_generated = get_post_meta( $artwork_id, '_vortex_created_with_huraii', true );
                
                // Get artist info
                $author_id = get_post_field( 'post_author', $artwork_id );
                $author_name = get_the_author_meta( 'display_name', $author_id );
                
                // Artwork container
                echo '<div class="vortex-featured-artwork">';
                
                // Artwork image
                echo '<div class="vortex-featured-artwork-image">';
                echo '<a href="' . esc_url( get_permalink() ) . '">';
                if ( has_post_thumbnail() ) {
                    the_post_thumbnail( 'medium' );
                } else {
                    echo '<img src="' . esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/images/placeholder.jpg' ) . '" alt="' . esc_attr( get_the_title() ) . '" />';
                }
                echo '</a>';
                
                // AI badge
                if ( $ai_generated ) {
                    echo '<span class="vortex-ai-badge">' . esc_html__( 'AI', 'vortex-ai-marketplace' ) . '</span>';
                }
                
                echo '</div>'; // End image
                
                // Artwork info
                echo '<div class="vortex-featured-artwork-info">';
                
                // Title
                echo '<h3 class="vortex-featured-artwork-title">';
                echo '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
                echo '</h3>';
                
                // Artist
                if ( $show_artist ) {
                    echo '<div class="vortex-featured-artwork-artist">';
                    echo esc_html__( 'By', 'vortex-ai-marketplace' ) . ' ';
                    
                    // Get artist profile if exists
                    $args = array(
                        'post_type'      => 'vortex_artist',
                        'posts_per_page' => 1,
                        'author'         => $author_id,
                        'fields'         => 'ids',
                    );
                    
                    $artist_query = new WP_Query( $args );
                    
                    if ( $artist_query->have_posts() ) {
                        $artist_id = $artist_query->posts[0];
                        echo '<a href="' . esc_url( get_permalink( $artist_id ) ) . '">' . esc_html( $author_name ) . '</a>';
                    } else {
                        echo esc_html( $author_name );
                    }
                    
                    echo '</div>';
                }
                
                // Price
                if ( $show_price ) {
                    echo '<div class="vortex-featured-artwork-price">';
                    
                    if ( ! empty( $price ) ) {
                        // Regular price with optional sale price
                        if ( $on_sale && ! empty( $sale_price ) ) {
                            echo '<del>' . esc_html( apply_filters( 'vortex_artwork_price_display', $price, $artwork_id ) ) . '</del> ';
                            echo '<ins>' . esc_html( apply_filters( 'vortex_artwork_price_display', $sale_price, $artwork_id ) ) . '</ins>';
                        } else {
                            echo esc_html( apply_filters( 'vortex_artwork_price_display', $price, $artwork_id ) );
                        }
                    } elseif ( ! empty( $tola_price ) ) {
                        // TOLA price
                        echo esc_html( number_format( (float) $tola_price, 6 ) . ' TOLA' );
                    } else {
                        // No price set
                        echo esc_html__( 'Price on request', 'vortex-ai-marketplace' );
                    }
                    
                    echo '</div>';
                }
                
                echo '</div>'; // End info
                
                echo '</div>'; // End artwork container
            }
            
            echo '</div>'; // End widget container
            
            // Add "View All" link if enabled
            if ( ! empty( $instance['show_view_all'] ) && $instance['show_view_all'] ) {
                echo '<div class="vortex-view-all-link">';
                echo '<a href="' . esc_url( get_post_type_archive_link( 'vortex_artwork' ) ) . '">' . esc_html__( 'View All Artworks', 'vortex-ai-marketplace' ) . '</a>';
                echo '</div>';
            }
            
            wp_reset_postdata();
        } else {
            // No artworks found
            echo '<p class="vortex-no-artworks">' . esc_html__( 'No artworks found.', 'vortex-ai-marketplace' ) . '</p>';
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
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Featured Artwork', 'vortex-ai-marketplace' );
        $number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 3;
        $columns = ! empty( $instance['columns'] ) ? absint( $instance['columns'] ) : 1;
        $show_price = isset( $instance['show_price'] ) ? (bool) $instance['show_price'] : true;
        $show_artist = isset( $instance['show_artist'] ) ? (bool) $instance['show_artist'] : true;
        $featured_only = isset( $instance['featured_only'] ) ? (bool) $instance['featured_only'] : false;
        $ai_only = isset( $instance['ai_only'] ) ? (bool) $instance['ai_only'] : false;
        $show_view_all = isset( $instance['show_view_all'] ) ? (bool) $instance['show_view_all'] : true;
        $category = ! empty( $instance['category'] ) ? $instance['category'] : '';
        $orderby = ! empty( $instance['orderby'] ) ? $instance['orderby'] : 'date';
        
        // Get categories for dropdown
        $categories = get_terms( array(
            'taxonomy'   => 'art_category',
            'hide_empty' => true,
        ) );
        ?>
        
        <!-- Title -->
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'vortex-ai-marketplace' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        
        <!-- Number of artworks -->
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_html_e( 'Number of artworks to show:', 'vortex-ai-marketplace' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="number" step="1" min="1" max="12" value="<?php echo esc_attr( $number ); ?>" size="3">
        </p>
        
        <!-- Columns -->
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>"><?php esc_html_e( 'Columns:', 'vortex-ai-marketplace' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'columns' ) ); ?>">
                <option value="1" <?php selected( $columns, 1 ); ?>><?php esc_html_e( '1 Column', 'vortex-ai-marketplace' ); ?></option>
                <option value="2" <?php selected( $columns, 2 ); ?>><?php esc_html_e( '2 Columns', 'vortex-ai-marketplace' ); ?></option>
                <option value="3" <?php selected( $columns, 3 ); ?>><?php esc_html_e( '3 Columns', 'vortex-ai-marketplace' ); ?></option>
            </select>
        </p>
        
        <!-- Category filter -->
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"><?php esc_html_e( 'Category:', 'vortex-ai-marketplace' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'category' ) ); ?>">
                <option value="" <?php selected( $category, '' ); ?>><?php esc_html_e( 'All Categories', 'vortex-ai-marketplace' ); ?></option>
                <?php foreach ( $categories as $cat ) : ?>
                    <option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $category, $cat->slug ); ?>>
                        <?php echo esc_html( $cat->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <!-- Order by -->
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>"><?php esc_html_e( 'Order By:', 'vortex-ai-marketplace' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>">
                <option value="date" <?php selected( $orderby, 'date' ); ?>><?php esc_html_e( 'Date (newest first)', 'vortex-ai-marketplace' ); ?></option>
                <option value="price" <?php selected( $orderby, 'price' ); ?>><?php esc_html_e( 'Price (low to high)', 'vortex-ai-marketplace' ); ?></option>
                <option value="price-desc" <?php selected( $orderby, 'price-desc' ); ?>><?php esc_html_e( 'Price (high to low)', 'vortex-ai-marketplace' ); ?></option>
                <option value="title" <?php selected( $orderby, 'title' ); ?>><?php esc_html_e( 'Title', 'vortex-ai-marketplace' ); ?></option>
                <option value="views" <?php selected( $orderby, 'views' ); ?>><?php esc_html_e( 'Popularity (views)', 'vortex-ai-marketplace' ); ?></option>
                <option value="random" <?php selected( $orderby, 'random' ); ?>><?php esc_html_e( 'Random', 'vortex-ai-marketplace' ); ?></option>
            </select>
        </p>
        
        <!-- Display options -->
        <p>
            <input class="checkbox" type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'featured_only' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'featured_only' ) ); ?>" <?php checked( $featured_only ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'featured_only' ) ); ?>"><?php esc_html_e( 'Show only featured artworks', 'vortex-ai-marketplace' ); ?></label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'ai_only' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'ai_only' ) ); ?>" <?php checked( $ai_only ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'ai_only' ) ); ?>"><?php esc_html_e( 'Show only AI-generated artworks', 'vortex-ai-marketplace' ); ?></label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_price' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_price' ) ); ?>" <?php checked( $show_price ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_price' ) ); ?>"><?php esc_html_e( 'Show price', 'vortex-ai-marketplace' ); ?></label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_artist' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_artist' ) ); ?>" <?php checked( $show_artist ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_artist' ) ); ?>"><?php esc_html_e( 'Show artist name', 'vortex-ai-marketplace' ); ?></label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_view_all' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_view_all' ) ); ?>" <?php checked( $show_view_all ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_view_all' ) ); ?>"><?php esc_html_e( 'Show "View All" link', 'vortex-ai-marketplace' ); ?></label>
        </p>
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
        $instance['show_price'] = isset( $new_instance['show_price'] ) ? (bool) $new_instance['show_price'] : false;
        $instance['show_artist'] = isset( $new_instance['show_artist'] ) ? (bool) $new_instance['show_artist'] : false;
        $instance['featured_only'] = isset( $new_instance['featured_only'] ) ? (bool) $new_instance['featured_only'] : false;
        $instance['ai_only'] = isset( $new_instance['ai_only'] ) ? (bool) $new_instance['ai_only'] : false;
        $instance['show_view_all'] = isset( $new_instance['show_view_all'] ) ? (bool) $new_instance['show_view_all'] : false;
        $instance['category'] = ! empty( $new_instance['category'] ) ? sanitize_text_field( $new_instance['category'] ) : '';
        $instance['orderby'] = ! empty( $new_instance['orderby'] ) ? sanitize_text_field( $new_instance['orderby'] ) : 'date';
        
        return $instance;
    }
} 