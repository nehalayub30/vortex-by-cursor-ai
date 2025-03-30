<?php
/**
 * Metrics functionality for the VORTEX AI Marketplace.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_Metrics {

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
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Initialize metrics functionality.
     *
     * @since    1.0.0
     */
    public function initialize_metrics() {
        // Initialize database tables if needed
        $this->create_metrics_tables();

        // Register REST API endpoints for metrics
        add_action('rest_api_init', array($this, 'register_metrics_endpoints'));
        
        // Add tracking for various metrics
        add_action('wp_footer', array($this, 'track_view_metrics'));
        add_action('wp_ajax_vortex_artwork_like', array($this, 'track_like_metrics'));
        add_action('wp_ajax_nopriv_vortex_artwork_like', array($this, 'track_like_metrics'));
        add_action('vortex_artwork_purchase', array($this, 'track_purchase_metrics'), 10, 3);
        
        // Add dashboard widget for admins
        add_action('wp_dashboard_setup', array($this, 'add_metrics_dashboard_widget'));
        
        // Add shortcodes for metrics display
        add_shortcode('vortex_artist_metrics', array($this, 'artist_metrics_shortcode'));
        add_shortcode('vortex_artwork_metrics', array($this, 'artwork_metrics_shortcode'));
        add_shortcode('vortex_marketplace_metrics', array($this, 'marketplace_metrics_shortcode'));
    }

    /**
     * Create database tables for metrics.
     *
     * @since    1.0.0
     */
    private function create_metrics_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $artwork_metrics_table = $wpdb->prefix . 'vortex_artwork_metrics';
        $artist_metrics_table = $wpdb->prefix . 'vortex_artist_metrics';
        $marketplace_metrics_table = $wpdb->prefix . 'vortex_marketplace_metrics';
        
        // Check if tables exist before creating them
        if($wpdb->get_var("SHOW TABLES LIKE '$artwork_metrics_table'") != $artwork_metrics_table) {
            $sql = "CREATE TABLE $artwork_metrics_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                artwork_id mediumint(9) NOT NULL,
                views int(11) NOT NULL DEFAULT 0,
                likes int(11) NOT NULL DEFAULT 0,
                shares int(11) NOT NULL DEFAULT 0,
                purchases int(11) NOT NULL DEFAULT 0,
                revenue float NOT NULL DEFAULT 0,
                last_updated datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY artwork_id (artwork_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        if($wpdb->get_var("SHOW TABLES LIKE '$artist_metrics_table'") != $artist_metrics_table) {
            $sql = "CREATE TABLE $artist_metrics_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                artist_id mediumint(9) NOT NULL,
                total_views int(11) NOT NULL DEFAULT 0,
                total_likes int(11) NOT NULL DEFAULT 0,
                total_shares int(11) NOT NULL DEFAULT 0,
                total_sales int(11) NOT NULL DEFAULT 0,
                total_revenue float NOT NULL DEFAULT 0,
                followers int(11) NOT NULL DEFAULT 0,
                last_updated datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY artist_id (artist_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        if($wpdb->get_var("SHOW TABLES LIKE '$marketplace_metrics_table'") != $marketplace_metrics_table) {
            $sql = "CREATE TABLE $marketplace_metrics_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                date date NOT NULL,
                daily_views int(11) NOT NULL DEFAULT 0,
                daily_sales int(11) NOT NULL DEFAULT 0,
                daily_revenue float NOT NULL DEFAULT 0,
                new_artists int(11) NOT NULL DEFAULT 0,
                new_collectors int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY  (id),
                UNIQUE KEY date (date)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    /**
     * Register REST API endpoints for metrics.
     *
     * @since    1.0.0
     */
    public function register_metrics_endpoints() {
        register_rest_route( 'vortex/v1', '/metrics/artist/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_artist_metrics'),
        ));
        
        register_rest_route( 'vortex/v1', '/metrics/artwork/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_artwork_metrics'),
        ));
        
        register_rest_route( 'vortex/v1', '/metrics/marketplace', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_marketplace_metrics'),
        ));
    }

    /**
     * Track page view metrics.
     *
     * @since    1.0.0
     */
    public function track_view_metrics() {
        global $post;
        
        if (!is_singular(array('vortex-artwork', 'vortex-artist'))) {
            return;
        }
        
        if (is_singular('vortex-artwork')) {
            $this->increment_artwork_metric($post->ID, 'views');
            
            // Also update artist metrics
            $artist_id = get_post_meta($post->ID, 'vortex_artwork_artist', true);
            if ($artist_id) {
                $this->increment_artist_metric($artist_id, 'total_views');
            }
        } else if (is_singular('vortex-artist')) {
            $this->increment_artist_metric($post->ID, 'total_views');
        }
        
        // Update marketplace metrics
        $this->increment_marketplace_daily_metric('daily_views');
    }

    /**
     * Track like metrics (AJAX handler).
     *
     * @since    1.0.0
     */
    public function track_like_metrics() {
        // Check for nonce security
        check_ajax_referer('vortex_like_nonce', 'nonce');
        
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        
        if ($artwork_id > 0) {
            $this->increment_artwork_metric($artwork_id, 'likes');
            
            // Also update artist metrics
            $artist_id = get_post_meta($artwork_id, 'vortex_artwork_artist', true);
            if ($artist_id) {
                $this->increment_artist_metric($artist_id, 'total_likes');
            }
            
            wp_send_json_success('Like recorded');
        } else {
            wp_send_json_error('Invalid artwork ID');
        }
    }

    /**
     * Track purchase metrics.
     *
     * @since    1.0.0
     * @param    int       $artwork_id    The artwork ID.
     * @param    int       $buyer_id      The buyer user ID.
     * @param    float     $price         The purchase price.
     */
    public function track_purchase_metrics($artwork_id, $buyer_id, $price) {
        // Update artwork metrics
        $this->increment_artwork_metric($artwork_id, 'purchases');
        $this->increment_artwork_revenue($artwork_id, $price);
        
        // Update artist metrics
        $artist_id = get_post_meta($artwork_id, 'vortex_artwork_artist', true);
        if ($artist_id) {
            $this->increment_artist_metric($artist_id, 'total_sales');
            $this->increment_artist_revenue($artist_id, $price);
        }
        
        // Update marketplace metrics
        $this->increment_marketplace_daily_metric('daily_sales');
        $this->increment_marketplace_daily_revenue($price);
    }

    /**
     * Increment an artwork metric.
     *
     * @since    1.0.0
     * @param    int       $artwork_id    The artwork ID.
     * @param    string    $metric        The metric to increment.
     * @param    int       $amount        The amount to increment by.
     */
    private function increment_artwork_metric($artwork_id, $metric, $amount = 1) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artwork_metrics';
        
        // Check if metric record exists
        $record_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE artwork_id = %d",
            $artwork_id
        ));
        
        if ($record_id) {
            // Update existing record
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET $metric = $metric + %d, last_updated = CURRENT_TIMESTAMP WHERE artwork_id = %d",
                $amount,
                $artwork_id
            ));
        } else {
            // Insert new record
            $wpdb->insert(
                $table_name,
                array(
                    'artwork_id' => $artwork_id,
                    $metric => $amount,
                )
            );
        }
    }

    /**
     * Increment an artwork's revenue.
     *
     * @since    1.0.0
     * @param    int       $artwork_id    The artwork ID.
     * @param    float     $amount        The amount to add to revenue.
     */
    private function increment_artwork_revenue($artwork_id, $amount) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artwork_metrics';
        
        // Check if metric record exists
        $record_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE artwork_id = %d",
            $artwork_id
        ));
        
        if ($record_id) {
            // Update existing record
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET revenue = revenue + %f, last_updated = CURRENT_TIMESTAMP WHERE artwork_id = %d",
                $amount,
                $artwork_id
            ));
        } else {
            // Insert new record
            $wpdb->insert(
                $table_name,
                array(
                    'artwork_id' => $artwork_id,
                    'revenue' => $amount,
                )
            );
        }
    }

    /**
     * Increment an artist metric.
     *
     * @since    1.0.0
     * @param    int       $artist_id    The artist ID.
     * @param    string    $metric       The metric to increment.
     * @param    int       $amount       The amount to increment by.
     */
    private function increment_artist_metric($artist_id, $metric, $amount = 1) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artist_metrics';
        
        // Check if metric record exists
        $record_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE artist_id = %d",
            $artist_id
        ));
        
        if ($record_id) {
            // Update existing record
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET $metric = $metric + %d, last_updated = CURRENT_TIMESTAMP WHERE artist_id = %d",
                $amount,
                $artist_id
            ));
        } else {
            // Insert new record
            $wpdb->insert(
                $table_name,
                array(
                    'artist_id' => $artist_id,
                    $metric => $amount,
                )
            );
        }
    }

    /**
     * Increment an artist's revenue.
     *
     * @since    1.0.0
     * @param    int       $artist_id    The artist ID.
     * @param    float     $amount       The amount to add to revenue.
     */
    private function increment_artist_revenue($artist_id, $amount) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artist_metrics';
        
        // Check if metric record exists
        $record_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE artist_id = %d",
            $artist_id
        ));
        
        if ($record_id) {
            // Update existing record
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET total_revenue = total_revenue + %f, last_updated = CURRENT_TIMESTAMP WHERE artist_id = %d",
                $amount,
                $artist_id
            ));
        } else {
            // Insert new record
            $wpdb->insert(
                $table_name,
                array(
                    'artist_id' => $artist_id,
                    'total_revenue' => $amount,
                )
            );
        }
    }

    /**
     * Increment a marketplace daily metric.
     *
     * @since    1.0.0
     * @param    string    $metric    The metric to increment.
     * @param    int       $amount    The amount to increment by.
     */
    private function increment_marketplace_daily_metric($metric, $amount = 1) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_marketplace_metrics';
        $today = current_time('Y-m-d');
        
        // Check if today's record exists
        $record_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE date = %s",
            $today
        ));
        
        if ($record_id) {
            // Update existing record
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET $metric = $metric + %d WHERE date = %s",
                $amount,
                $today
            ));
        } else {
            // Insert new record
            $wpdb->insert(
                $table_name,
                array(
                    'date' => $today,
                    $metric => $amount,
                )
            );
        }
    }

    /**
     * Increment marketplace daily revenue.
     *
     * @since    1.0.0
     * @param    float     $amount    The amount to add to revenue.
     */
    private function increment_marketplace_daily_revenue($amount) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_marketplace_metrics';
        $today = current_time('Y-m-d');
        
        // Check if today's record exists
        $record_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE date = %s",
            $today
        ));
        
        if ($record_id) {
            // Update existing record
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET daily_revenue = daily_revenue + %f WHERE date = %s",
                $amount,
                $today
            ));
        } else {
            // Insert new record
            $wpdb->insert(
                $table_name,
                array(
                    'date' => $today,
                    'daily_revenue' => $amount,
                )
            );
        }
    }

    /**
     * Add metrics dashboard widget for admins.
     *
     * @since    1.0.0
     */
    public function add_metrics_dashboard_widget() {
        wp_add_dashboard_widget(
            'vortex_metrics_dashboard',
            __('VORTEX Marketplace Metrics', 'vortex-ai-marketplace'),
            array($this, 'display_metrics_dashboard_widget')
        );
    }

    /**
     * Display the metrics dashboard widget.
     *
     * @since    1.0.0
     */
    public function display_metrics_dashboard_widget() {
        // Get marketplace metrics
        global $wpdb;
        $marketplace_table = $wpdb->prefix . 'vortex_marketplace_metrics';
        
        // Get today's metrics
        $today = current_time('Y-m-d');
        $today_metrics = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $marketplace_table WHERE date = %s",
            $today
        ));
        
        // Get yesterday's metrics
        $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($today)));
        $yesterday_metrics = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $marketplace_table WHERE date = %s",
            $yesterday
        ));
        
        // Format output
        echo '<div class="vortex-metrics-widget">';
        
        // Today's stats
        echo '<h3>' . __('Today\'s Metrics', 'vortex-ai-marketplace') . '</h3>';
        echo '<ul>';
        echo '<li>' . __('Views: ', 'vortex-ai-marketplace') . ($today_metrics ? $today_metrics->daily_views : 0) . '</li>';
        echo '<li>' . __('Sales: ', 'vortex-ai-marketplace') . ($today_metrics ? $today_metrics->daily_sales : 0) . '</li>';
        echo '<li>' . __('Revenue: ', 'vortex-ai-marketplace') . ($today_metrics ? $this->format_revenue($today_metrics->daily_revenue) : $this->format_revenue(0)) . '</li>';
        echo '<li>' . __('New Artists: ', 'vortex-ai-marketplace') . ($today_metrics ? $today_metrics->new_artists : 0) . '</li>';
        echo '<li>' . __('New Collectors: ', 'vortex-ai-marketplace') . ($today_metrics ? $today_metrics->new_collectors : 0) . '</li>';
        echo '</ul>';
        
        // Yesterday's stats
        echo '<h3>' . __('Yesterday\'s Metrics', 'vortex-ai-marketplace') . '</h3>';
        echo '<ul>';
        echo '<li>' . __('Views: ', 'vortex-ai-marketplace') . ($yesterday_metrics ? $yesterday_metrics->daily_views : 0) . '</li>';
        echo '<li>' . __('Sales: ', 'vortex-ai-marketplace') . ($yesterday_metrics ? $yesterday_metrics->daily_sales : 0) . '</li>';
        echo '<li>' . __('Revenue: ', 'vortex-ai-marketplace') . ($yesterday_metrics ? $this->format_revenue($yesterday_metrics->daily_revenue) : $this->format_revenue(0)) . '</li>';
        echo '</ul>';
        
        // Link to detailed metrics
        echo '<p><a href="' . admin_url('admin.php?page=vortex-metrics') . '">' . __('View Detailed Metrics', 'vortex-ai-marketplace') . '</a></p>';
        
        echo '</div>';
    }

    /**
     * Format revenue for display.
     *
     * @since    1.0.0
     * @param    float     $revenue    The revenue amount.
     * @return   string    Formatted revenue.
     */
    private function format_revenue($revenue) {
        return number_format($revenue, 2) . ' TOLA';
    }

    /**
     * Get artist metrics (REST API callback).
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request    Full data about the request.
     * @return   WP_REST_Response            Response data.
     */
    public function get_artist_metrics($request) {
        $artist_id = $request['id'];
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artist_metrics';
        
        $metrics = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE artist_id = %d",
            $artist_id
        ));
        
        if (!$metrics) {
            return new WP_REST_Response(array(
                'error' => 'No metrics found for this artist.'
            ), 404);
        }
        
        return new WP_REST_Response($metrics, 200);
    }

    /**
     * Get artwork metrics (REST API callback).
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request    Full data about the request.
     * @return   WP_REST_Response            Response data.
     */
    public function get_artwork_metrics($request) {
        $artwork_id = $request['id'];
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artwork_metrics';
        
        $metrics = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE artwork_id = %d",
            $artwork_id
        ));
        
        if (!$metrics) {
            return new WP_REST_Response(array(
                'error' => 'No metrics found for this artwork.'
            ), 404);
        }
        
        return new WP_REST_Response($metrics, 200);
    }

    /**
     * Get marketplace metrics (REST API callback).
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request    Full data about the request.
     * @return   WP_REST_Response            Response data.
     */
    public function get_marketplace_metrics($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_marketplace_metrics';
        
        // Get the last 30 days of metrics
        $today = current_time('Y-m-d');
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days', strtotime($today)));
        
        $metrics = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE date BETWEEN %s AND %s ORDER BY date DESC",
            $thirty_days_ago,
            $today
        ));
        
        if (empty($metrics)) {
            return new WP_REST_Response(array(
                'error' => 'No marketplace metrics found.'
            ), 404);
        }
        
        return new WP_REST_Response($metrics, 200);
    }

    /**
     * Shortcode for displaying artist metrics.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    Rendered HTML.
     */
    public function artist_metrics_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'vortex_artist_metrics');
        
        $artist_id = intval($atts['id']);
        
        if ($artist_id <= 0) {
            return '<p>' . __('Invalid artist ID.', 'vortex-ai-marketplace') . '</p>';
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artist_metrics';
        
        $metrics = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE artist_id = %d",
            $artist_id
        ));
        
        if (!$metrics) {
            return '<p>' . __('No metrics available for this artist.', 'vortex-ai-marketplace') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="vortex-artist-metrics">
            <h3><?php _e('Artist Performance', 'vortex-ai-marketplace'); ?></h3>
            <div class="vortex-metrics-grid">
                <div class="vortex-metric-card">
                    <div class="vortex-metric-value"><?php echo number_format($metrics->total_views); ?></div>
                    <div class="vortex-metric-label"><?php _e('Total Views', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="vortex-metric-card">
                    <div class="vortex-metric-value"><?php echo number_format($metrics->total_likes); ?></div>
                    <div class="vortex-metric-label"><?php _e('Total Likes', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="vortex-metric-card">
                    <div class="vortex-metric-value"><?php echo number_format($metrics->total_sales); ?></div>
                    <div class="vortex-metric-label"><?php _e('Artworks Sold', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="vortex-metric-card">
                    <div class="vortex-metric-value"><?php echo $this->format_revenue($metrics->total_revenue); ?></div>
                    <div class="vortex-metric-label"><?php _e('Total Revenue', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="vortex-metric-card">
                    <div class="vortex-metric-value"><?php echo number_format($metrics->followers); ?></div>
                    <div class="vortex-metric-label"><?php _e('Followers', 'vortex-ai-marketplace'); ?></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode for displaying artwork metrics.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    Rendered HTML.
     */
    public function artwork_metrics_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'vortex_artwork_metrics');
        
        $artwork_id = intval($atts['id']);
        
        if ($artwork_id <= 0) {
            return '<p>' . __('Invalid artwork ID.', 'vortex-ai-marketplace') . '</p>';
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artwork_metrics';
        
        $metrics = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE artwork_id = %d",
            $artwork_id
        ));
        
        if (!$metrics) {
            return '<p>' . __('No metrics available for this artwork.', 'vortex-ai-marketplace') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="vortex-artwork-metrics">
            <h3><?php _e('Artwork Performance', 'vortex-ai-marketplace'); ?></h3>
            <div class="vortex-metrics-grid">
                <div class="vortex-metric-card">
                    <div class="vortex-metric-value"><?php echo number_format($metrics->views); ?></div>
                    <div class="vortex-metric-label"><?php _e('Views', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="vortex-metric-card">
                    <div class="vortex-metric-value"><?php echo number_format($metrics->likes); ?></div>
                    <div class="vortex-metric-label"><?php _e('Likes', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="vortex-metric-card">
                    <div class="vortex-metric-value"><?php echo number_format($metrics->shares); ?></div>
                    <div class="vortex-metric-label"><?php _e('Shares', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="vortex-metric-card">
                    <div class="vortex-metric-value"><?php echo number_format($metrics->purchases); ?></div>
                    <div class="vortex-metric-label"><?php _e('Times Purchased', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="vortex-metric-card">
                    <div class="vortex-metric-value"><?php echo $this->format_revenue($metrics->revenue); ?></div>
                    <div class="vortex-metric-label"><?php _e('Total Revenue', 'vortex-ai-marketplace'); ?></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode for displaying marketplace metrics.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    Rendered HTML.
     */
    public function marketplace_metrics_shortcode($atts) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_marketplace_metrics';
        
        // Get the last 7 days of metrics
        $today = current_time('Y-m-d');
        $seven_days_ago = date('Y-m-d', strtotime('-7 days', strtotime($today)));
        
        $metrics = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE date BETWEEN %s AND %s ORDER BY date ASC",
            $seven_days_ago,
            $today
        ));
        
        if (empty($metrics)) {
            return '<p>' . __('No marketplace metrics available.', 'vortex-ai-marketplace') . '</p>';
        }
        
        // Calculate totals for the period
        $total_views = 0;
        $total_sales = 0;
        $total_revenue = 0;
        $total_new_artists = 0;
        $total_new_collectors = 0;
        
        foreach ($metrics as $day) {
            $total_views += $day->daily_views;
            $total_sales += $day->daily_sales;
            $total_revenue += $day->daily_revenue;
            $total_new_artists += $day->new_artists;
            $total_new_collectors += $day->new_collectors;
        }
        
        ob_start();
        ?>
        <div class="vortex-marketplace-metrics">
            <h3><?php _e('Marketplace Performance (Last 7 Days)', 'vortex-ai-marketplace'); ?></h3>
            <div class="vortex-metrics-grid">
                <div class="vortex-metric-card">
                    <div class="vortex-metric-value"><?php echo number_format($total_views); ?></div>
                    <div class="vortex-metric-label"><?php _e('Total Views', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="vortex-metric-card">
                    <div class="vortex-metric-value"><?php echo number_format($total_sales); ?></div>
                    <div class="vortex-metric-label"><?php _e('Total Sales', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="vortex-metric-card">
                    <div class="vortex-metric-value"><?php echo $this->format_revenue($total_revenue); ?></div>
                    <div class="vortex-metric-label"><?php _e('Total Revenue', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="vortex-metric-card">
                    <div class="vortex-metric-value"><?php echo number_format($total_new_artists); ?></div>
                    <div class="vortex-metric-label"><?php _e('New Artists', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="vortex-metric-card">
                    <div class="vortex-metric-value"><?php echo number_format($total_new_collectors); ?></div>
                    <div class="vortex-metric-label"><?php _e('New Collectors', 'vortex-ai-marketplace'); ?></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
} 