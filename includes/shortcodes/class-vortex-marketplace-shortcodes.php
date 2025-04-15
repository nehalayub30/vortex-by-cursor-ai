<?php
/**
 * Marketplace Shortcodes
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class responsible for registering and handling all marketplace-related shortcodes
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/shortcodes
 */
class VORTEX_Marketplace_Shortcodes {

    /**
     * Initialize the class and register shortcodes
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Register all shortcodes
        add_shortcode('vortex_marketplace', array($this, 'render_marketplace'));
        add_shortcode('vortex_artwork', array($this, 'render_artwork'));
        add_shortcode('vortex_artist_profile', array($this, 'render_artist_profile'));
        add_shortcode('vortex_artwork_grid', array($this, 'render_artwork_grid'));
        add_shortcode('vortex_artwork_slider', array($this, 'render_artwork_slider'));
        add_shortcode('vortex_categories', array($this, 'render_categories'));
        add_shortcode('vortex_marketplace_search', array($this, 'render_search'));
        add_shortcode('vortex_collector_dashboard', array($this, 'render_collector_dashboard'));
        add_shortcode('vortex_artist_dashboard', array($this, 'render_artist_dashboard'));
        add_shortcode('vortex_featured_artists', array($this, 'render_featured_artists'));
        add_shortcode('vortex_trending_artworks', array($this, 'render_trending_artworks'));

        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
    }

    /**
     * Register scripts and styles for the shortcodes
     *
     * @since    1.0.0
     */
    public function register_scripts() {
        // Register marketplace styles
        wp_register_style(
            'vortex-marketplace',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'public/css/marketplace.css',
            array(),
            VORTEX_VERSION
        );

        // Register marketplace scripts
        wp_register_script(
            'vortex-marketplace',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'public/js/marketplace.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );

        // Register slider scripts and styles
        wp_register_style(
            'vortex-slider',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'public/css/slider.css',
            array(),
            VORTEX_VERSION
        );

        wp_register_script(
            'vortex-slider',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'public/js/slider.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );

        // Localize script with ajax url
        wp_localize_script('vortex-marketplace', 'vortex_marketplace', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_marketplace_nonce'),
        ));
    }

    /**
     * Render complete marketplace
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string   HTML output
     */
    public function render_marketplace($atts) {
        // Enqueue required styles and scripts
        wp_enqueue_style('vortex-marketplace');
        wp_enqueue_script('vortex-marketplace');

        // Parse attributes
        $atts = shortcode_atts(array(
            'category' => '',
            'artist' => '',
            'style' => '',
            'ai_engine' => '',
            'columns' => 3,
            'items_per_page' => 12,
            'show_filters' => 'true',
            'show_search' => 'true',
            'show_sorting' => 'true',
            'default_sort' => 'newest', // newest, popular, price_low, price_high
        ), $atts, 'vortex_marketplace');

        // Start output buffer
        ob_start();

        // Include the marketplace template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/marketplace/marketplace.php';

        return ob_get_clean();
    }

    /**
     * Render single artwork display
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string   HTML output
     */
    public function render_artwork($atts) {
        // Enqueue required styles
        wp_enqueue_style('vortex-marketplace');

        // Parse attributes
        $atts = shortcode_atts(array(
            'id' => 0,
            'show_artist' => 'true',
            'show_price' => 'true',
            'show_purchase' => 'true',
            'show_details' => 'true',
        ), $atts, 'vortex_artwork');

        // Start output buffer
        ob_start();

        // Validate ID
        $artwork_id = intval($atts['id']);
        if (empty($artwork_id)) {
            return '<p class="vortex-error">' . esc_html__('Artwork ID is required', 'vortex-ai-marketplace') . '</p>';
        }

        // Get artwork data
        $artwork = $this->get_artwork_data($artwork_id);
        if (!$artwork) {
            return '<p class="vortex-error">' . esc_html__('Artwork not found', 'vortex-ai-marketplace') . '</p>';
        }

        // Include the artwork template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/marketplace/single-artwork.php';

        return ob_get_clean();
    }

    /**
     * Render artist profile
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string   HTML output
     */
    public function render_artist_profile($atts) {
        // Enqueue required styles
        wp_enqueue_style('vortex-marketplace');

        // Parse attributes
        $atts = shortcode_atts(array(
            'id' => 0,
            'username' => '',
            'show_bio' => 'true',
            'show_stats' => 'true',
            'show_artworks' => 'true',
            'artwork_count' => 6,
        ), $atts, 'vortex_artist_profile');

        // Start output buffer
        ob_start();

        // Get artist data
        $artist_id = intval($atts['id']);
        $username = sanitize_text_field($atts['username']);

        if (empty($artist_id) && empty($username)) {
            return '<p class="vortex-error">' . esc_html__('Artist ID or username is required', 'vortex-ai-marketplace') . '</p>';
        }

        // Get artist by ID or username
        $artist = $this->get_artist_data($artist_id, $username);
        if (!$artist) {
            return '<p class="vortex-error">' . esc_html__('Artist not found', 'vortex-ai-marketplace') . '</p>';
        }

        // Include the artist profile template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/marketplace/artist-profile.php';

        return ob_get_clean();
    }

    /**
     * Render artwork grid
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string   HTML output
     */
    public function render_artwork_grid($atts) {
        // Enqueue required styles
        wp_enqueue_style('vortex-marketplace');
        wp_enqueue_script('vortex-marketplace');

        // Parse attributes
        $atts = shortcode_atts(array(
            'category' => '',
            'artist' => '',
            'style' => '',
            'ai_engine' => '',
            'columns' => 3,
            'count' => 6,
            'show_artist' => 'true',
            'show_price' => 'true',
            'show_details' => 'true',
            'orderby' => 'date', // date, price, popularity
            'order' => 'DESC', // ASC, DESC
        ), $atts, 'vortex_artwork_grid');

        // Start output buffer
        ob_start();

        // Get artworks
        $artworks = $this->get_artworks($atts);

        // Include the artwork grid template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/marketplace/artwork-grid.php';

        return ob_get_clean();
    }

    /**
     * Render artwork slider
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string   HTML output
     */
    public function render_artwork_slider($atts) {
        // Enqueue required styles and scripts
        wp_enqueue_style('vortex-marketplace');
        wp_enqueue_style('vortex-slider');
        wp_enqueue_script('vortex-slider');

        // Parse attributes
        $atts = shortcode_atts(array(
            'category' => '',
            'artist' => '',
            'style' => '',
            'ai_engine' => '',
            'count' => 10,
            'show_artist' => 'true',
            'show_price' => 'true',
            'show_details' => 'true',
            'orderby' => 'date', // date, price, popularity
            'order' => 'DESC', // ASC, DESC
            'autoplay' => 'true',
            'speed' => 3000,
        ), $atts, 'vortex_artwork_slider');

        // Start output buffer
        ob_start();

        // Get artworks
        $artworks = $this->get_artworks($atts);

        // Include the artwork slider template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/marketplace/artwork-slider.php';

        return ob_get_clean();
    }

    /**
     * Render categories
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string   HTML output
     */
    public function render_categories($atts) {
        // Enqueue required styles
        wp_enqueue_style('vortex-marketplace');

        // Parse attributes
        $atts = shortcode_atts(array(
            'layout' => 'grid', // grid, list
            'columns' => 4,
            'count' => 8,
            'show_count' => 'true',
            'show_thumbnail' => 'true',
            'parent' => 0,
        ), $atts, 'vortex_categories');

        // Start output buffer
        ob_start();

        // Get categories
        $categories = $this->get_categories($atts);

        // Include the categories template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/marketplace/categories.php';

        return ob_get_clean();
    }

    /**
     * Render marketplace search
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string   HTML output
     */
    public function render_search($atts) {
        // Enqueue required styles and scripts
        wp_enqueue_style('vortex-marketplace');
        wp_enqueue_script('vortex-marketplace');

        // Parse attributes
        $atts = shortcode_atts(array(
            'placeholder' => __('Search the marketplace...', 'vortex-ai-marketplace'),
            'show_filters' => 'true',
            'results_page' => '',
        ), $atts, 'vortex_marketplace_search');

        // Start output buffer
        ob_start();

        // Include the search template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/marketplace/search-form.php';

        return ob_get_clean();
    }

    /**
     * Render collector dashboard
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string   HTML output
     */
    public function render_collector_dashboard($atts) {
        // Enqueue required styles and scripts
        wp_enqueue_style('vortex-marketplace');
        wp_enqueue_script('vortex-marketplace');

        // Parse attributes
        $atts = shortcode_atts(array(
            'default_tab' => 'collection', // collection, favorites, activity, settings
        ), $atts, 'vortex_collector_dashboard');

        // Start output buffer
        ob_start();

        // Check if user is logged in
        if (!is_user_logged_in()) {
            include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/marketplace/login-required.php';
            return ob_get_clean();
        }

        // Include the collector dashboard template
        include plugin_dir_path(dirname(dirname(__FILE__))).  'public/partials/marketplace/collector-dashboard.php';

        return ob_get_clean();
    }

    /**
     * Render artist dashboard
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string   HTML output
     */
    public function render_artist_dashboard($atts) {
        // Enqueue required styles and scripts
        wp_enqueue_style('vortex-marketplace');
        wp_enqueue_script('vortex-marketplace');

        // Parse attributes
        $atts = shortcode_atts(array(
            'default_tab' => 'artworks', // artworks, sales, analytics, settings
        ), $atts, 'vortex_artist_dashboard');

        // Start output buffer
        ob_start();

        // Check if user is logged in and is an artist
        if (!is_user_logged_in()) {
            include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/marketplace/login-required.php';
            return ob_get_clean();
        }

        // Check if user is an artist
        $user_id = get_current_user_id();
        if (!$this->is_user_artist($user_id)) {
            include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/marketplace/artist-required.php';
            return ob_get_clean();
        }

        // Include the artist dashboard template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/marketplace/artist-dashboard.php';

        return ob_get_clean();
    }

    /**
     * Render featured artists
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string   HTML output
     */
    public function render_featured_artists($atts) {
        // Enqueue required styles
        wp_enqueue_style('vortex-marketplace');

        // Parse attributes
        $atts = shortcode_atts(array(
            'count' => 6,
            'columns' => 3,
            'show_bio' => 'true',
            'show_artworks' => 'true',
            'artworks_count' => 3,
        ), $atts, 'vortex_featured_artists');

        // Start output buffer
        ob_start();

        // Get featured artists
        $artists = $this->get_featured_artists($atts);

        // Include the featured artists template
        include plugin_dir_path(dirname(dirname(__FILE__))).  'public/partials/marketplace/featured-artists.php';

        return ob_get_clean();
    }

    /**
     * Render trending artworks
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string   HTML output
     */
    public function render_trending_artworks($atts) {
        // Enqueue required styles
        wp_enqueue_style('vortex-marketplace');

        // Parse attributes
        $atts = shortcode_atts(array(
            'count' => 6,
            'columns' => 3,
            'period' => '7', // days
            'show_artist' => 'true',
            'show_price' => 'true',
            'show_rank' => 'true',
        ), $atts, 'vortex_trending_artworks');

        // Start output buffer
        ob_start();

        // Get trending artworks
        $artworks = $this->get_trending_artworks($atts);

        // Include the trending artworks template
        include plugin_dir_path(dirname(dirname(__FILE__))). 'public/partials/marketplace/trending-artworks.php';

        return ob_get_clean();
    }

    /**
     * Get artwork data
     *
     * @since    1.0.0
     * @param    int       $artwork_id    Artwork ID
     * @return   array|bool               Artwork data or false if not found
     */
    private function get_artwork_data($artwork_id) {
        $artwork = get_post($artwork_id);
        
        if (!$artwork || $artwork->post_type !== 'vortex_artwork') {
            return false;
        }

        // Get artist data
        $artist_id = get_post_meta($artwork_id, '_vortex_artist_id', true);
        $artist = $this->get_artist_data($artist_id);

        // Get artwork metadata
        $price = get_post_meta($artwork_id, '_vortex_price', true);
        $medium = get_post_meta($artwork_id, '_vortex_medium', true);
        $dimensions = get_post_meta($artwork_id, '_vortex_dimensions', true);
        $year = get_post_meta($artwork_id, '_vortex_year', true);
        $ai_engine = get_post_meta($artwork_id, '_vortex_ai_engine', true);
        $token_id = get_post_meta($artwork_id, '_vortex_token_id', true);
        $is_tokenized = !empty($token_id);

        // Get artwork categories
        $categories = get_the_terms($artwork_id, 'artwork_category');
        $styles = get_the_terms($artwork_id, 'artwork_style');

        // Get thumbnail/featured image
        $image_id = get_post_thumbnail_id($artwork_id);
        $image_url = wp_get_attachment_image_url($image_id, 'large');
        $image_full_url = wp_get_attachment_image_url($image_id, 'full');

        // Format artwork data
        return array(
            'id' => $artwork_id,
            'title' => get_the_title($artwork),
            'description' => get_the_content(null, false, $artwork),
            'price' => $price,
            'medium' => $medium,
            'dimensions' => $dimensions,
            'year' => $year,
            'ai_engine' => $ai_engine,
            'token_id' => $token_id,
            'is_tokenized' => $is_tokenized,
            'permalink' => get_permalink($artwork),
            'image' => $image_url,
            'image_full' => $image_full_url,
            'categories' => $categories,
            'styles' => $styles,
            'artist' => $artist,
            'date' => get_the_date('', $artwork),
            'views' => intval(get_post_meta($artwork_id, '_vortex_view_count', true)),
            'likes' => intval(get_post_meta($artwork_id, '_vortex_like_count', true)),
        );
    }

    /**
     * Helper method to get artist data
     *
     * @since    1.0.0
     * @param    int       $artist_id    Artist ID
     * @param    string    $username     Username (optional)
     * @return   array|bool              Artist data or false if not found
     */
    private function get_artist_data($artist_id = 0, $username = '') {
        $user = null;
        
        if (!empty($artist_id)) {
            $user = get_user_by('ID', $artist_id);
        } elseif (!empty($username)) {
            $user = get_user_by('login', $username);
        }
        
        if (!$user || !$this->is_user_artist($user->ID)) {
            return false;
        }
        
        // Get artist metadata
        $bio = get_user_meta($user->ID, 'vortex_artist_bio', true);
        $website = get_user_meta($user->ID, 'vortex_artist_website', true);
        $social = array(
            'twitter' => get_user_meta($user->ID, 'vortex_artist_twitter', true),
            'instagram' => get_user_meta($user->ID, 'vortex_artist_instagram', true),
            'facebook' => get_user_meta($user->ID, 'vortex_artist_facebook', true),
        );
        
        // Get artist avatar
        $avatar_url = get_avatar_url($user->ID, array('size' => 150));
        
        return array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'username' => $user->user_login,
            'bio' => $bio,
            'website' => $website,
            'social' => $social,
            'avatar' => $avatar_url,
            'url' => get_author_posts_url($user->ID),
        );
    }

    /**
     * Check if a user is an artist
     *
     * @since    1.0.0
     * @param    int       $user_id    User ID
     * @return   bool                  Whether user is an artist
     */
    private function is_user_artist($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        return in_array('artist', (array) $user->roles);
    }

    /**
     * Get artworks based on provided parameters
     *
     * @since    1.0.0
     * @param    array     $args    Query arguments
     * @return   array              Array of artwork data
     */
    private function get_artworks($args) {
        $query_args = array(
            'post_type' => 'vortex_artwork',
            'post_status' => 'publish',
            'posts_per_page' => isset($args['count']) ? intval($args['count']) : 6,
            'orderby' => isset($args['orderby']) ? sanitize_text_field($args['orderby']) : 'date',
            'order' => isset($args['order']) ? sanitize_text_field($args['order']) : 'DESC',
        );
        
        // Add taxonomy queries
        $tax_query = array();
        
        if (!empty($args['category'])) {
            $tax_query[] = array(
                'taxonomy' => 'artwork_category',
                'field' => 'slug',
                'terms' => explode(',', $args['category']),
            );
        }
        
        if (!empty($args['style'])) {
            $tax_query[] = array(
                'taxonomy' => 'artwork_style',
                'field' => 'slug',
                'terms' => explode(',', $args['style']),
            );
        }
        
        if (!empty($tax_query)) {
            $query_args['tax_query'] = $tax_query;
        }
        
        // Filter by artist
        if (!empty($args['artist'])) {
            $artist_id = intval($args['artist']);
            if (!$artist_id) {
                $user = get_user_by('login', sanitize_text_field($args['artist']));
                if ($user) {
                    $artist_id = $user->ID;
                }
            }
            
            if ($artist_id) {
                $query_args['meta_query'][] = array(
                    'key' => '_vortex_artist_id',
                    'value' => $artist_id,
                );
            }
        }
        
        // Filter by AI engine
        if (!empty($args['ai_engine'])) {
            $query_args['meta_query'][] = array(
                'key' => '_vortex_ai_engine',
                'value' => $args['ai_engine'],
            );
        }
        
        // Get posts
        $query = new WP_Query($query_args);
        $artworks = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $artworks[] = $this->get_artwork_data(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        return $artworks;
    }

    /**
     * Get categories
     *
     * @since    1.0.0
     * @param    array     $args    Query arguments
     * @return   array              Array of category data
     */
    private function get_categories($args) {
        $query_args = array(
            'taxonomy' => 'artwork_category',
            'hide_empty' => false,
            'number' => isset($args['count']) ? intval($args['count']) : 8,
            'parent' => isset($args['parent']) ? intval($args['parent']) : 0,
        );
        
        $terms = get_terms($query_args);
        $categories = array();
        
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
                $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
                
                $categories[] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'description' => $term->description,
                    'count' => $term->count,
                    'url' => get_term_link($term),
                    'thumbnail' => $thumbnail_url,
                );
            }
        }
        
        return $categories;
    }

    /**
     * Get featured artists
     *
     * @since    1.0.0
     * @param    array     $args    Query arguments
     * @return   array              Array of artist data
     */
    private function get_featured_artists($args) {
        $count = isset($args['count']) ? intval($args['count']) : 6;
        
        // Get users with artist role who are featured
        $query_args = array(
            'role' => 'artist',
            'meta_key' => 'vortex_artist_featured',
            'meta_value' => '1',
            'number' => $count,
            'orderby' => 'display_name',
        );
        
        $user_query = new WP_User_Query($query_args);
        $artists = array();
        
        if (!empty($user_query->results)) {
            foreach ($user_query->results as $user) {
                $artists[] = $this->get_artist_data($user->ID);
            }
        }
        
        return $artists;
    }

    /**
     * Get trending artworks
     *
     * @since    1.0.0
     * @param    array     $args    Query arguments
     * @return   array              Array of artwork data
     */
    private function get_trending_artworks($args) {
        $count = isset($args['count']) ? intval($args['count']) : 6;
        $period = isset($args['period']) ? intval($args['period']) : 7;
        
        // Use meta query to get most viewed artworks in the specified period
        $query_args = array(
            'post_type' => 'vortex_artwork',
            'posts_per_page' => $count,
            'meta_key' => '_vortex_view_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'date_query' => array(
                array(
                    'after' => $period . ' days ago',
                ),
            ),
        );
        
        $query = new WP_Query($query_args);
        $artworks = array();
        
        if ($query->have_posts()) {
            $rank = 1;
            while ($query->have_posts()) {
                $query->the_post();
                $artwork = $this->get_artwork_data(get_the_ID());
                $artwork['rank'] = $rank++;
                $artworks[] = $artwork;
            }
            wp_reset_postdata();
        }
        
        return $artworks;
    }
}

// Initialize the class
new VORTEX_Marketplace_Shortcodes(); 