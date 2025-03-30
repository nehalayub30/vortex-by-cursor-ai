<?php
/**
 * The artwork category taxonomy functionality.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/taxonomies
 */

/**
 * The artwork category taxonomy functionality.
 *
 * Defines and manages the artwork category taxonomy for organizing artworks.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/taxonomies
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Artwork_Category {

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
     * The taxonomy name.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $taxonomy    The taxonomy name.
     */
    private $taxonomy;

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
        $this->taxonomy = 'art_category';
        
        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Register all category taxonomy related hooks.
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_hooks() {
        // Register the taxonomy
        add_action( 'init', array( $this, 'register_taxonomy' ) );
        
        // Add custom fields to taxonomy term form
        add_action( $this->taxonomy . '_add_form_fields', array( $this, 'add_term_fields' ) );
        add_action( $this->taxonomy . '_edit_form_fields', array( $this, 'edit_term_fields' ) );
        
        // Save custom fields from taxonomy term form
        add_action( 'created_' . $this->taxonomy, array( $this, 'save_term_fields' ) );
        add_action( 'edited_' . $this->taxonomy, array( $this, 'save_term_fields' ) );
        
        // Add custom columns to taxonomy term list
        add_filter( 'manage_edit-' . $this->taxonomy . '_columns', array( $this, 'modify_term_columns' ) );
        add_filter( 'manage_' . $this->taxonomy . '_custom_column', array( $this, 'populate_term_columns' ), 10, 3 );
        
        // Register REST API fields
        add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
        
        // Ajax handlers
        add_action( 'wp_ajax_vortex_get_category_artworks', array( $this, 'ajax_get_category_artworks' ) );
        add_action( 'wp_ajax_nopriv_vortex_get_category_artworks', array( $this, 'ajax_get_category_artworks' ) );
    }

    /**
     * Register the artwork category taxonomy.
     *
     * @since    1.0.0
     */
    public function register_taxonomy() {
        $labels = array(
            'name'                       => _x( 'Art Categories', 'Taxonomy General Name', 'vortex-ai-marketplace' ),
            'singular_name'              => _x( 'Art Category', 'Taxonomy Singular Name', 'vortex-ai-marketplace' ),
            'menu_name'                  => __( 'Categories', 'vortex-ai-marketplace' ),
            'all_items'                  => __( 'All Categories', 'vortex-ai-marketplace' ),
            'parent_item'                => __( 'Parent Category', 'vortex-ai-marketplace' ),
            'parent_item_colon'          => __( 'Parent Category:', 'vortex-ai-marketplace' ),
            'new_item_name'              => __( 'New Category Name', 'vortex-ai-marketplace' ),
            'add_new_item'               => __( 'Add New Category', 'vortex-ai-marketplace' ),
            'edit_item'                  => __( 'Edit Category', 'vortex-ai-marketplace' ),
            'update_item'                => __( 'Update Category', 'vortex-ai-marketplace' ),
            'view_item'                  => __( 'View Category', 'vortex-ai-marketplace' ),
            'separate_items_with_commas' => __( 'Separate categories with commas', 'vortex-ai-marketplace' ),
            'add_or_remove_items'        => __( 'Add or remove categories', 'vortex-ai-marketplace' ),
            'choose_from_most_used'      => __( 'Choose from the most used categories', 'vortex-ai-marketplace' ),
            'popular_items'              => __( 'Popular Categories', 'vortex-ai-marketplace' ),
            'search_items'               => __( 'Search Categories', 'vortex-ai-marketplace' ),
            'not_found'                  => __( 'Not Found', 'vortex-ai-marketplace' ),
            'no_terms'                   => __( 'No categories', 'vortex-ai-marketplace' ),
            'items_list'                 => __( 'Categories list', 'vortex-ai-marketplace' ),
            'items_list_navigation'      => __( 'Categories list navigation', 'vortex-ai-marketplace' ),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rest_base'                  => 'art-categories',
            'rewrite'                    => array( 'slug' => 'art-category' ),
        );
        
        register_taxonomy( $this->taxonomy, 'vortex_artwork', $args );
    }

    /**
     * Add custom fields to the new term form.
     *
     * @since    1.0.0
     */
    public function add_term_fields() {
        ?>
        <div class="form-field term-group">
            <label for="vortex_category_icon"><?php _e( 'Category Icon', 'vortex-ai-marketplace' ); ?></label>
            <input type="text" id="vortex_category_icon" name="vortex_category_icon" value="" />
            <p class="description"><?php _e( 'Icon class (e.g., "dashicons dashicons-art" or Font Awesome class).', 'vortex-ai-marketplace' ); ?></p>
        </div>
        
        <div class="form-field term-group">
            <label for="vortex_category_color"><?php _e( 'Category Color', 'vortex-ai-marketplace' ); ?></label>
            <input type="color" id="vortex_category_color" name="vortex_category_color" value="#3498db" />
            <p class="description"><?php _e( 'Choose a color for this category.', 'vortex-ai-marketplace' ); ?></p>
        </div>
        
        <div class="form-field term-group">
            <label for="vortex_category_featured"><?php _e( 'Featured Category', 'vortex-ai-marketplace' ); ?></label>
            <input type="checkbox" id="vortex_category_featured" name="vortex_category_featured" value="1" />
            <p class="description"><?php _e( 'Check to make this a featured category.', 'vortex-ai-marketplace' ); ?></p>
        </div>
        
        <div class="form-field term-group">
            <label for="vortex_category_order"><?php _e( 'Display Order', 'vortex-ai-marketplace' ); ?></label>
            <input type="number" id="vortex_category_order" name="vortex_category_order" value="0" min="0" />
            <p class="description"><?php _e( 'Order in which the category appears (0 = default).', 'vortex-ai-marketplace' ); ?></p>
        </div>
        <?php
    }

    /**
     * Add custom fields to the edit term form.
     *
     * @since    1.0.0
     * @param    WP_Term    $term    The term object.
     */
    public function edit_term_fields( $term ) {
        // Get the existing values
        $icon = get_term_meta( $term->term_id, 'vortex_category_icon', true );
        $color = get_term_meta( $term->term_id, 'vortex_category_color', true );
        $featured = get_term_meta( $term->term_id, 'vortex_category_featured', true );
        $order = get_term_meta( $term->term_id, 'vortex_category_order', true );
        
        if ( empty( $color ) ) {
            $color = '#3498db'; // Default color
        }
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="vortex_category_icon"><?php _e( 'Category Icon', 'vortex-ai-marketplace' ); ?></label>
            </th>
            <td>
                <input type="text" id="vortex_category_icon" name="vortex_category_icon" value="<?php echo esc_attr( $icon ); ?>" />
                <p class="description"><?php _e( 'Icon class (e.g., "dashicons dashicons-art" or Font Awesome class).', 'vortex-ai-marketplace' ); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="vortex_category_color"><?php _e( 'Category Color', 'vortex-ai-marketplace' ); ?></label>
            </th>
            <td>
                <input type="color" id="vortex_category_color" name="vortex_category_color" value="<?php echo esc_attr( $color ); ?>" />
                <p class="description"><?php _e( 'Choose a color for this category.', 'vortex-ai-marketplace' ); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="vortex_category_featured"><?php _e( 'Featured Category', 'vortex-ai-marketplace' ); ?></label>
            </th>
            <td>
                <input type="checkbox" id="vortex_category_featured" name="vortex_category_featured" value="1" <?php checked( $featured, 1 ); ?> />
                <p class="description"><?php _e( 'Check to make this a featured category.', 'vortex-ai-marketplace' ); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="vortex_category_order"><?php _e( 'Display Order', 'vortex-ai-marketplace' ); ?></label>
            </th>
            <td>
                <input type="number" id="vortex_category_order" name="vortex_category_order" value="<?php echo esc_attr( $order ); ?>" min="0" />
                <p class="description"><?php _e( 'Order in which the category appears (0 = default).', 'vortex-ai-marketplace' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save custom fields from the term form.
     *
     * @since    1.0.0
     * @param    int    $term_id    The term ID.
     */
    public function save_term_fields( $term_id ) {
        // Save icon
        if ( isset( $_POST['vortex_category_icon'] ) ) {
            update_term_meta( 
                $term_id, 
                'vortex_category_icon', 
                sanitize_text_field( $_POST['vortex_category_icon'] ) 
            );
        }
        
        // Save color
        if ( isset( $_POST['vortex_category_color'] ) ) {
            update_term_meta( 
                $term_id, 
                'vortex_category_color', 
                sanitize_hex_color( $_POST['vortex_category_color'] ) 
            );
        }
        
        // Save featured status
        $featured = isset( $_POST['vortex_category_featured'] ) ? 1 : 0;
        update_term_meta( $term_id, 'vortex_category_featured', $featured );
        
        // Save order
        if ( isset( $_POST['vortex_category_order'] ) ) {
            update_term_meta( 
                $term_id, 
                'vortex_category_order', 
                intval( $_POST['vortex_category_order'] ) 
            );
        }
    }

    /**
     * Modify the columns in the term list table.
     *
     * @since    1.0.0
     * @param    array    $columns    The default columns.
     * @return   array                Modified columns.
     */
    public function modify_term_columns( $columns ) {
        $new_columns = array();
        
        // Add columns after name column
        foreach ( $columns as $key => $value ) {
            $new_columns[$key] = $value;
            
            if ( $key === 'name' ) {
                $new_columns['icon'] = __( 'Icon', 'vortex-ai-marketplace' );
                $new_columns['color'] = __( 'Color', 'vortex-ai-marketplace' );
                $new_columns['featured'] = __( 'Featured', 'vortex-ai-marketplace' );
                $new_columns['order'] = __( 'Order', 'vortex-ai-marketplace' );
            }
        }
        
        return $new_columns;
    }

    /**
     * Populate the custom columns in the term list table.
     *
     * @since    1.0.0
     * @param    string    $content      The column content.
     * @param    string    $column_name  The column name.
     * @param    int       $term_id      The term ID.
     * @return   string                  The column content.
     */
    public function populate_term_columns( $content, $column_name, $term_id ) {
        switch ( $column_name ) {
            case 'icon':
                $icon = get_term_meta( $term_id, 'vortex_category_icon', true );
                if ( ! empty( $icon ) ) {
                    $content = '<i class="' . esc_attr( $icon ) . '"></i> ' . $icon;
                } else {
                    $content = '—';
                }
                break;
                
            case 'color':
                $color = get_term_meta( $term_id, 'vortex_category_color', true );
                if ( ! empty( $color ) ) {
                    $content = '<span style="display:inline-block; width:20px; height:20px; background-color:' . esc_attr( $color ) . '; vertical-align:middle; border-radius:50%;"></span> ' . $color;
                } else {
                    $content = '—';
                }
                break;
                
            case 'featured':
                $featured = get_term_meta( $term_id, 'vortex_category_featured', true );
                $content = $featured ? '<span class="dashicons dashicons-star-filled" style="color:gold;"></span>' : '—';
                break;
                
            case 'order':
                $order = get_term_meta( $term_id, 'vortex_category_order', true );
                $content = ! empty( $order ) ? $order : '0';
                break;
        }
        
        return $content;
    }

    /**
     * Register REST API fields for category taxonomy.
     *
     * @since    1.0.0
     */
    public function register_rest_fields() {
        register_rest_field(
            $this->taxonomy,
            'category_meta',
            array(
                'get_callback'    => array( $this, 'get_term_meta_for_api' ),
                'update_callback' => null,
                'schema'          => null,
            )
        );
    }

    /**
     * Get term meta data for REST API.
     *
     * @since    1.0.0
     * @param    array     $object      The term object.
     * @param    string    $field_name  The field name.
     * @param    array     $request     The request data.
     * @return   array                  The term meta data.
     */
    public function get_term_meta_for_api( $object, $field_name, $request ) {
        $term_id = $object['id'];
        
        return array(
            'icon'     => get_term_meta( $term_id, 'vortex_category_icon', true ),
            'color'    => get_term_meta( $term_id, 'vortex_category_color', true ),
            'featured' => (bool) get_term_meta( $term_id, 'vortex_category_featured', true ),
            'order'    => (int) get_term_meta( $term_id, 'vortex_category_order', true ),
            'count'    => (int) $object['count'],
        );
    }

    /**
     * AJAX handler for getting artworks by category.
     *
     * @since    1.0.0
     */
    public function ajax_get_category_artworks() {
        // Check for category ID
        $category_id = isset( $_POST['category_id'] ) ? intval( $_POST['category_id'] ) : 0;
        
        if ( ! $category_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid category ID.', 'vortex-ai-marketplace' ) ) );
        }
        
        // Get pagination parameters
        $page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
        $per_page = isset( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : 12;
        
        // Query for artworks in this category
        $args = array(
            'post_type'      => 'vortex_artwork',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'tax_query'      => array(
                array(
                    'taxonomy' => $this->taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $category_id,
                ),
            ),
        );
        
        // Add sorting
        $sort = isset( $_POST['sort'] ) ? sanitize_text_field( $_POST['sort'] ) : 'date';
        
        switch ( $sort ) {
            case 'price_low':
                $args['meta_key'] = '_vortex_artwork_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
                
            case 'price_high':
                $args['meta_key'] = '_vortex_artwork_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
                
            case 'title':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
                
            case 'popular':
                $args['meta_key'] = '_vortex_view_count';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
                
            case 'date':
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
        }
        
        $query = new WP_Query( $args );
        $artworks = array();
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                
                $artwork_id = get_the_ID();
                $price = get_post_meta( $artwork_id, '_vortex_artwork_price', true );
                $tola_price = get_post_meta( $artwork_id, '_vortex_tola_price', true );
                $on_sale = get_post_meta( $artwork_id, '_vortex_artwork_on_sale', true );
                
                // Get final price based on sale status
                $display_price = $price;
                if ( $on_sale ) {
                    $sale_price = get_post_meta( $artwork_id, '_vortex_artwork_sale_price', true );
                    if ( ! empty( $sale_price ) ) {
                        $display_price = $sale_price;
                    }
                }
                
                // Get author info
                $author_id = get_post_field( 'post_author', $artwork_id );
                $author_name = get_the_author_meta( 'display_name', $author_id );
                
                $artworks[] = array(
                    'id'           => $artwork_id,
                    'title'        => get_the_title(),
                    'permalink'    => get_permalink(),
                    'thumbnail'    => get_the_post_thumbnail_url( $artwork_id, 'medium' ),
                    'price'        => $display_price,
                    'regular_price' => $price,
                    'tola_price'   => $tola_price,
                    'on_sale'      => (bool) $on_sale,
                    'author'       => $author_name,
                    'author_id'    => $author_id,
                    'date'         => get_the_date( 'c' ),
                    'excerpt'      => get_the_excerpt(),
                );
            }
            
            wp_reset_postdata();
        }
        
        wp_send_json_success( array(
            'artworks' => $artworks,
            'total'    => $query->found_posts,
            'pages'    => $query->max_num_pages,
        ) );
    }

    /**
     * Get featured categories.
     *
     * @since    1.0.0
     * @param    int       $limit    Maximum number of categories to return.
     * @return   array               Array of featured category terms.
     */
    public function get_featured_categories( $limit = 6 ) {
        $args = array(
            'taxonomy'   => $this->taxonomy,
            'hide_empty' => true,
            'number'     => $limit,
            'meta_query' => array(
                array(
                    'key'     => 'vortex_category_featured',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
            'meta_key'  => 'vortex_category_order',
            'orderby'   => 'meta_value_num',
            'order'     => 'ASC',
        );
        
        return get_terms( $args );
    }

    /**
     * Get all categories ordered by custom order.
     *
     * @since    1.0.0
     * @param    int       $parent    Parent term ID (0 for top level).
     * @return   array                Array of category terms.
     */
    public function get_ordered_categories( $parent = 0 ) {
        $args = array(
            'taxonomy'   => $this->taxonomy,
            'hide_empty' => true,
            'parent'     => $parent,
            'meta_key'   => 'vortex_category_order',
            'orderby'    => 'meta_value_num',
            'order'      => 'ASC',
        );
        
        return get_terms( $args );
    }
} 