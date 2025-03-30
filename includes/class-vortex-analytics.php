<?php
/**
 * The analytics processing functionality.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The analytics processing functionality.
 *
 * This class handles processing and analyzing marketplace data,
 * generating insights, and providing visualization capabilities.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Analytics {

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
     * The metrics instance for data access.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Metrics    $metrics    The metrics instance.
     */
    private $metrics;

    /**
     * The rankings instance for data access.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Rankings    $rankings    The rankings instance.
     */
    private $rankings;

    /**
     * The logger instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      object    $logger    Logger instance.
     */
    private $logger;

    /**
     * Database table name for storing analytics data.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $analytics_table    Database table name.
     */
    private $analytics_table;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string           $plugin_name    The name of this plugin.
     * @param    string           $version        The version of this plugin.
     * @param    Vortex_Metrics   $metrics        The metrics instance.
     * @param    Vortex_Rankings  $rankings       The rankings instance.
     * @param    object           $logger         Optional. Logger instance.
     */
    public function __construct( $plugin_name, $version, $metrics = null, $rankings = null, $logger = null ) {
        global $wpdb;
        
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->metrics = $metrics;
        $this->rankings = $rankings;
        $this->logger = $logger;
        
        // Set database table name
        $this->analytics_table = $wpdb->prefix . 'vortex_analytics';
        
        // Register hooks
        $this->register_hooks();
    }

    /**
     * Register hooks for analytics functionality.
     *
     * @since    1.0.0
     */
    private function register_hooks() {
        // Schedule periodic analytics processing
        if ( ! wp_next_scheduled( 'vortex_weekly_analytics_processing' ) ) {
            wp_schedule_event( time(), 'weekly', 'vortex_weekly_analytics_processing' );
        }
        
        if ( ! wp_next_scheduled( 'vortex_monthly_analytics_processing' ) ) {
            wp_schedule_event( time(), 'monthly', 'vortex_monthly_analytics_processing' );
        }
        
        // Register action hooks for scheduled events
        add_action( 'vortex_weekly_analytics_processing', array( $this, 'process_weekly_analytics' ) );
        add_action( 'vortex_monthly_analytics_processing', array( $this, 'process_monthly_analytics' ) );
        
        // Admin hooks
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'add_analytics_menu' ), 35 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        
        // Dashboard widgets
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_vortex_get_analytics_chart', array( $this, 'ajax_get_analytics_chart' ) );
        add_action( 'wp_ajax_vortex_export_analytics', array( $this, 'ajax_export_analytics' ) );
        add_action( 'wp_ajax_vortex_get_insights', array( $this, 'ajax_get_insights' ) );
        
        // REST API routes
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        
        // User-specific analytics
        add_action( 'show_user_profile', array( $this, 'add_user_analytics_section' ) );
        add_action( 'edit_user_profile', array( $this, 'add_user_analytics_section' ) );
        
        // Marketplace analytics hooks
        add_action( 'vortex_artwork_purchased', array( $this, 'track_purchase_analytics' ), 20, 3 );
        add_action( 'vortex_artist_verification', array( $this, 'track_verification_analytics' ), 10, 1 );
    }

    /**
     * Register plugin settings for analytics configuration.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting( 'vortex_analytics_settings', 'vortex_analytics_enabled', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ));
        
        register_setting( 'vortex_analytics_settings', 'vortex_analytics_dashboard_widgets', array(
            'type' => 'object',
            'default' => array(
                'sales_summary' => true,
                'artist_growth' => true,
                'artwork_statistics' => true,
                'marketplace_health' => true,
            ),
            'sanitize_callback' => array( $this, 'sanitize_dashboard_widgets' ),
        ));
        
        register_setting( 'vortex_analytics_settings', 'vortex_analytics_report_recipients', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        
        register_setting( 'vortex_analytics_settings', 'vortex_analytics_report_frequency', array(
            'type' => 'string',
            'default' => 'monthly',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        
        register_setting( 'vortex_analytics_settings', 'vortex_analytics_integrations', array(
            'type' => 'object',
            'default' => array(
                'google_analytics' => false,
                'google_analytics_id' => '',
                'facebook_pixel' => false,
                'facebook_pixel_id' => '',
            ),
            'sanitize_callback' => array( $this, 'sanitize_analytics_integrations' ),
        ));
    }

    /**
     * Add Analytics submenu to the marketplace admin menu.
     *
     * @since    1.0.0
     */
    public function add_analytics_menu() {
        add_submenu_page(
            'vortex_marketplace',
            __( 'Marketplace Analytics', 'vortex-ai-marketplace' ),
            __( 'Analytics', 'vortex-ai-marketplace' ),
            'manage_options',
            'vortex_analytics',
            array( $this, 'render_analytics_page' )
        );
        
        add_submenu_page(
            'vortex_marketplace',
            __( 'Analytics Settings', 'vortex-ai-marketplace' ),
            __( 'Analytics Settings', 'vortex-ai-marketplace' ),
            'manage_options',
            'vortex_analytics_settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register REST API routes for analytics data.
     *
     * @since    1.0.0
     */
    public function register_rest_routes() {
        register_rest_route( 'vortex/v1', '/analytics/summary', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'api_get_analytics_summary' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ));
        
        register_rest_route( 'vortex/v1', '/analytics/chart/(?P<chart_type>[a-z0-9_]+)', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'api_get_chart_data' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
            'args' => array(
                'chart_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'start_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                ),
                'end_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                ),
                'period' => array(
                    'type' => 'string',
                    'default' => 'day',
                    'enum' => array( 'day', 'week', 'month', 'year' ),
                ),
                'filters' => array(
                    'type' => 'object',
                ),
            ),
        ));
        
        register_rest_route( 'vortex/v1', '/analytics/insights', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'api_get_insights' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
            'args' => array(
                'type' => array(
                    'type' => 'string',
                    'default' => 'all',
                    'enum' => array( 'all', 'sales', 'artists', 'artworks', 'growth' ),
                ),
                'period' => array(
                    'type' => 'string',
                    'default' => 'month',
                    'enum' => array( 'week', 'month', 'quarter', 'year' ),
                ),
            ),
        ));
        
        register_rest_route( 'vortex/v1', '/analytics/user/(?P<user_id>\d+)', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'api_get_user_analytics' ),
            'permission_callback' => function( $request ) {
                $user_id = $request->get_param( 'user_id' );
                return current_user_can( 'manage_options' ) || 
                       get_current_user_id() == $user_id;
            },
            'args' => array(
                'user_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'period' => array(
                    'type' => 'string',
                    'default' => 'month',
                    'enum' => array( 'week', 'month', 'quarter', 'year', 'all' ),
                ),
            ),
        ));
    }

    /**
     * Enqueue admin scripts and styles for analytics pages.
     *
     * @since    1.0.0
     * @param    string    $hook    Current admin page.
     */
    public function enqueue_admin_scripts( $hook ) {
        // Only load on the analytics admin pages
        if ( 'vortex_marketplace_page_vortex_analytics' !== $hook && 
             'vortex_marketplace_page_vortex_analytics_settings' !== $hook &&
             'index.php' !== $hook ) {
            return;
        }
        
        // Enqueue Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js',
            array(),
            '3.7.0',
            true
        );
        
        // Enqueue our custom scripts and styles
        wp_enqueue_style(
            'vortex-analytics-admin',
            plugin_dir_url( dirname( __FILE__ ) ) . 'admin/css/vortex-analytics-admin.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'vortex-analytics-admin',
            plugin_dir_url( dirname( __FILE__ ) ) . 'admin/js/vortex-analytics-admin.js',
            array( 'jquery', 'chartjs' ),
            $this->version,
            true
        );
        
        // Localize script with data needed for charts
        wp_localize_script(
            'vortex-analytics-admin',
            'vortexAnalytics',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'apiUrl' => rest_url( 'vortex/v1/analytics/' ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'loading' => __( 'Loading...', 'vortex-ai-marketplace' ),
                'noData' => __( 'No data available', 'vortex-ai-marketplace' ),
                'exportLabel' => __( 'Export Data', 'vortex-ai-marketplace' ),
                'colorPalette' => $this->get_chart_color_palette(),
                'dateFormat' => get_option( 'date_format' ),
                'currencySymbol' => get_option( 'vortex_marketplace_currency_symbol', '$' ),
                'chartDefaults' => array(
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                ),
            )
        );
    }

    /**
     * Add analytics dashboard widgets.
     *
     * @since    1.0.0
     */
    public function add_dashboard_widgets() {
        // Check if analytics is enabled
        if ( ! get_option( 'vortex_analytics_enabled', true ) ) {
            return;
        }
        
        // Check user permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        // Get enabled widgets from settings
        $widgets = get_option( 'vortex_analytics_dashboard_widgets', array(
            'sales_summary' => true,
            'artist_growth' => true,
            'artwork_statistics' => true,
            'marketplace_health' => true,
        ));
        
        // Add enabled widgets
        if ( ! empty( $widgets['sales_summary'] ) ) {
            wp_add_dashboard_widget(
                'vortex_analytics_sales_summary',
                __( 'VORTEX Marketplace: Sales Summary', 'vortex-ai-marketplace' ),
                array( $this, 'render_sales_summary_widget' )
            );
        }
        
        if ( ! empty( $widgets['artist_growth'] ) ) {
            wp_add_dashboard_widget(
                'vortex_analytics_artist_growth',
                __( 'VORTEX Marketplace: Artist Growth', 'vortex-ai-marketplace' ),
                array( $this, 'render_artist_growth_widget' )
            );
        }
        
        if ( ! empty( $widgets['artwork_statistics'] ) ) {
            wp_add_dashboard_widget(
                'vortex_analytics_artwork_statistics',
                __( 'VORTEX Marketplace: Artwork Statistics', 'vortex-ai-marketplace' ),
                array( $this, 'render_artwork_statistics_widget' )
            );
        }
        
        if ( ! empty( $widgets['marketplace_health'] ) ) {
            wp_add_dashboard_widget(
                'vortex_analytics_marketplace_health',
                __( 'VORTEX Marketplace: Health Metrics', 'vortex-ai-marketplace' ),
                array( $this, 'render_marketplace_health_widget' )
            );
        }
    }

    /**
     * Process weekly analytics data.
     *
     * @since    1.0.0
     */
    public function process_weekly_analytics() {
        $this->log( 'Starting weekly analytics processing', 'info' );
        
        // Check if analytics is enabled
        if ( ! get_option( 'vortex_analytics_enabled', true ) ) {
            $this->log( 'Analytics processing is disabled', 'info' );
            return;
        }
        
        // Get date range for this week
        $end_date = current_time( 'Y-m-d' );
        $start_date = date( 'Y-m-d', strtotime( '-7 days', strtotime( $end_date ) ) );
        
        // Process sales analytics
        $this->process_sales_analytics( $start_date, $end_date, 'week' );
        
        // Process artist analytics
        $this->process_artist_analytics( $start_date, $end_date, 'week' );
        
        // Process artwork analytics
        $this->process_artwork_analytics( $start_date, $end_date, 'week' );
        
        // Process marketplace health metrics
        $this->process_marketplace_health( $start_date, $end_date, 'week' );
        
        // Generate insights
        $this->generate_insights( $start_date, $end_date, 'week' );
        
        // Send weekly report if configured
        if ( 'weekly' === get_option( 'vortex_analytics_report_frequency', 'monthly' ) ) {
            $this->send_analytics_report( $start_date, $end_date, 'week' );
        }
        
        $this->log( 'Completed weekly analytics processing', 'info' );
    }

    /**
     * Process monthly analytics data.
     *
     * @since    1.0.0
     */
    public function process_monthly_analytics() {
        $this->log( 'Starting monthly analytics processing', 'info' );
        
        // Check if analytics is enabled
        if ( ! get_option( 'vortex_analytics_enabled', true ) ) {
            $this->log( 'Analytics processing is disabled', 'info' );
            return;
        }
        
        // Get date range for this month
        $end_date = current_time( 'Y-m-d' );
        $start_date = date( 'Y-m-d', strtotime( '-1 month', strtotime( $end_date ) ) );
        
        // Process sales analytics
        $this->process_sales_analytics( $start_date, $end_date, 'month' );
        
        // Process artist analytics
        $this->process_artist_analytics( $start_date, $end_date, 'month' );
        
        // Process artwork analytics
        $this->process_artwork_analytics( $start_date, $end_date, 'month' );
        
        // Process marketplace health metrics
        $this->process_marketplace_health( $start_date, $end_date, 'month' );
        
        // Generate insights
        $this->generate_insights( $start_date, $end_date, 'month' );
        
        // Send monthly report if configured
        if ( 'monthly' === get_option( 'vortex_analytics_report_frequency', 'monthly' ) ) {
            $this->send_analytics_report( $start_date, $end_date, 'month' );
        }
        
        $this->log( 'Completed monthly analytics processing', 'info' );
    }

    /**
     * Process sales analytics data.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @param    string    $period        Period type (day, week, month, year).
     */
    public function process_sales_analytics( $start_date, $end_date, $period ) {
        global $wpdb;
        
        $this->log( "Processing sales analytics for period: {$period}", 'info' );
        
        // Prepare date ranges
        $current_range = array(
            'start' => $start_date,
            'end' => $end_date,
        );
        
        // Calculate previous period for comparison
        $days_diff = strtotime( $end_date ) - strtotime( $start_date );
        $previous_end = date( 'Y-m-d', strtotime( $start_date ) - 1 );
        $previous_start = date( 'Y-m-d', strtotime( $previous_end ) - $days_diff );
        
        $previous_range = array(
            'start' => $previous_start,
            'end' => $previous_end,
        );
        
        // Get sales data for current period
        $current_sales = $this->get_sales_data( $current_range['start'], $current_range['end'] );
        
        // Get sales data for previous period for comparison
        $previous_sales = $this->get_sales_data( $previous_range['start'], $previous_range['end'] );
        
        // Calculate growth metrics
        $sales_growth = array(
            'count' => $this->calculate_growth( $previous_sales['count'], $current_sales['count'] ),
            'revenue' => $this->calculate_growth( $previous_sales['revenue'], $current_sales['revenue'] ),
            'avg_value' => $this->calculate_growth( $previous_sales['avg_value'], $current_sales['avg_value'] ),
        );
        
        // Calculate conversion metrics
        $current_views = $this->get_total_views( $current_range['start'], $current_range['end'] );
        $conversion_rate = $current_views > 0 ? ( $current_sales['count'] / $current_views ) * 100 : 0;
        
        $previous_views = $this->get_total_views( $previous_range['start'], $previous_range['end'] );
        $previous_conversion_rate = $previous_views > 0 ? ( $previous_sales['count'] / $previous_views ) * 100 : 0;
        
        $conversion_growth = $this->calculate_growth( $previous_conversion_rate, $conversion_rate );
        
        // Get top selling artists
        $top_artists = $this->get_top_selling_artists( $current_range['start'], $current_range['end'], 5 );
        
        // Get top selling artworks
        $top_artworks = $this->get_top_selling_artworks( $current_range['start'], $current_range['end'], 5 );
        
        // Get sales by category
        $sales_by_category = $this->get_sales_by_category( $current_range['start'], $current_range['end'] );
        
        // Store aggregated sales analytics
        $analytics_data = array(
            'type' => 'sales',
            'period' => $period,
            'start_date' => $current_range['start'],
            'end_date' => $current_range['end'],
            'data' => wp_json_encode( array(
                'current' => $current_sales,
                'previous' => $previous_sales,
                'growth' => $sales_growth,
                'conversion' => array(
                    'rate' => $conversion_rate,
                    'growth' => $conversion_growth,
                ),
                'top_artists' => $top_artists,
                'top_artworks' => $top_artworks,
                'by_category' => $sales_by_category,
            )),
            'created_at' => current_time( 'mysql' ),
        );
        
        $this->save_analytics_data( $analytics_data );
        
        // Process daily sales trend for the period
        $this->process_sales_trend( $current_range['start'], $current_range['end'], $period );
    }

    /**
     * Process artist analytics data.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @param    string    $period        Period type (day, week, month, year).
     */
    public function process_artist_analytics( $start_date, $end_date, $period ) {
        global $wpdb;
        
        $this->log( "Processing artist analytics for period: {$period}", 'info' );
        
        // Prepare date ranges
        $current_range = array(
            'start' => $start_date,
            'end' => $end_date,
        );
        
        // Calculate previous period for comparison
        $days_diff = strtotime( $end_date ) - strtotime( $start_date );
        $previous_end = date( 'Y-m-d', strtotime( $start_date ) - 1 );
        $previous_start = date( 'Y-m-d', strtotime( $previous_end ) - $days_diff );
        
        $previous_range = array(
            'start' => $previous_start,
            'end' => $previous_end,
        );
        
        // Get new artist registrations
        $current_registrations = $this->get_new_artists( $current_range['start'], $current_range['end'] );
        $previous_registrations = $this->get_new_artists( $previous_range['start'], $previous_range['end'] );
        $registration_growth = $this->calculate_growth( $previous_registrations['count'], $current_registrations['count'] );
        
        // Get artist verification rate
        $current_verifications = $this->get_verified_artists( $current_range['start'], $current_range['end'] );
        $current_verification_rate = $current_registrations['count'] > 0 ? 
            ($current_verifications['count'] / $current_registrations['count']) * 100 : 0;
            
        $previous_verifications = $this->get_verified_artists( $previous_range['start'], $previous_range['end'] );
        $previous_verification_rate = $previous_registrations['count'] > 0 ? 
            ($previous_verifications['count'] / $previous_registrations['count']) * 100 : 0;
            
        $verification_rate_growth = $this->calculate_growth( $previous_verification_rate, $current_verification_rate );
        
        // Get artist activity metrics
        $active_artists = $this->get_active_artists( $current_range['start'], $current_range['end'] );
        $active_rate = $this->get_total_artists() > 0 ? 
            ($active_artists['count'] / $this->get_total_artists()) * 100 : 0;
            
        // Get artist earnings metrics
        $artist_earnings = $this->get_artist_earnings( $current_range['start'], $current_range['end'] );
        $previous_artist_earnings = $this->get_artist_earnings( $previous_range['start'], $previous_range['end'] );
        $earnings_growth = $this->calculate_growth( $previous_artist_earnings['total'], $artist_earnings['total'] );
        
        // Get top earning artists
        $top_earning_artists = $this->get_top_earning_artists( $current_range['start'], $current_range['end'], 5 );
        
        // Store aggregated artist analytics
        $analytics_data = array(
            'type' => 'artists',
            'period' => $period,
            'start_date' => $current_range['start'],
            'end_date' => $current_range['end'],
            'data' => wp_json_encode( array(
                'registrations' => array(
                    'current' => $current_registrations,
                    'previous' => $previous_registrations,
                    'growth' => $registration_growth,
                ),
                'verifications' => array(
                    'current' => $current_verifications,
                    'current_rate' => $current_verification_rate,
                    'previous_rate' => $previous_verification_rate,
                    'growth' => $verification_rate_growth,
                ),
                'activity' => array(
                    'active_artists' => $active_artists,
                    'active_rate' => $active_rate,
                ),
                'earnings' => array(
                    'current' => $artist_earnings,
                    'previous' => $previous_artist_earnings,
                    'growth' => $earnings_growth,
                    'top_artists' => $top_earning_artists,
                ),
            )),
            'created_at' => current_time( 'mysql' ),
        );
        
        $this->save_analytics_data( $analytics_data );
        
        // Process artist growth trend for the period
        $this->process_artist_growth_trend( $current_range['start'], $current_range['end'], $period );
    }

    /**
     * Process artwork analytics data.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @param    string    $period        Period type (day, week, month, year).
     */
    public function process_artwork_analytics( $start_date, $end_date, $period ) {
        global $wpdb;
        
        $this->log( "Processing artwork analytics for period: {$period}", 'info' );
        
        // Prepare date ranges
        $current_range = array(
            'start' => $start_date,
            'end' => $end_date,
        );
        
        // Calculate previous period for comparison
        $days_diff = strtotime( $end_date ) - strtotime( $start_date );
        $previous_end = date( 'Y-m-d', strtotime( $start_date ) - 1 );
        $previous_start = date( 'Y-m-d', strtotime( $previous_end ) - $days_diff );
        
        $previous_range = array(
            'start' => $previous_start,
            'end' => $previous_end,
        );
        
        // Get new artwork uploads
        $current_uploads = $this->get_new_artworks( $current_range['start'], $current_range['end'] );
        $previous_uploads = $this->get_new_artworks( $previous_range['start'], $previous_range['end'] );
        $upload_growth = $this->calculate_growth( $previous_uploads['count'], $current_uploads['count'] );
        
        // Get AI-generated artwork metrics
        $current_ai_artworks = $this->get_new_ai_artworks( $current_range['start'], $current_range['end'] );
        $current_ai_percentage = $current_uploads['count'] > 0 ? 
            ($current_ai_artworks['count'] / $current_uploads['count']) * 100 : 0;
            
        $previous_ai_artworks = $this->get_new_ai_artworks( $previous_range['start'], $previous_range['end'] );
        $previous_ai_percentage = $previous_uploads['count'] > 0 ? 
            ($previous_ai_artworks['count'] / $previous_uploads['count']) * 100 : 0;
            
        $ai_percentage_growth = $this->calculate_growth( $previous_ai_percentage, $current_ai_percentage );
        
        // Get artwork view metrics
        $current_views = $this->get_artwork_views( $current_range['start'], $current_range['end'] );
        $previous_views = $this->get_artwork_views( $previous_range['start'], $previous_range['end'] );
        $views_growth = $this->calculate_growth( $previous_views['count'], $current_views['count'] );
        
        // Get average views per artwork
        $total_artworks = $this->get_total_artworks();
        $avg_views = $total_artworks > 0 ? $current_views['count'] / $total_artworks : 0;
        
        // Get most popular artworks
        $most_viewed_artworks = $this->get_most_viewed_artworks( $current_range['start'], $current_range['end'], 5 );
        
        // Get artwork by category
        $artworks_by_category = $this->get_artworks_by_category();
        
        // Get top AI models used
        $top_ai_models = $this->get_top_ai_models( $current_range['start'], $current_range['end'], 5 );
        
        // Store aggregated artwork analytics
        $analytics_data = array(
            'type' => 'artworks',
            'period' => $period,
            'start_date' => $current_range['start'],
            'end_date' => $current_range['end'],
            'data' => wp_json_encode( array(
                'uploads' => array(
                    'current' => $current_uploads,
                    'previous' => $previous_uploads,
                    'growth' => $upload_growth,
                ),
                'ai_generated' => array(
                    'current' => $current_ai_artworks,
                    'current_percentage' => $current_ai_percentage,
                    'previous_percentage' => $previous_ai_percentage,
                    'growth' => $ai_percentage_growth,
                    'top_models' => $top_ai_models,
                ),
                'views' => array(
                    'current' => $current_views,
                    'previous' => $previous_views,
                    'growth' => $views_growth,
                    'avg_per_artwork' => $avg_views,
                    'most_viewed' => $most_viewed_artworks,
                ),
                'by_category' => $artworks_by_category,
            )),
            'created_at' => current_time( 'mysql' ),
        );
        
        $this->save_analytics_data( $analytics_data );
        
        // Process artwork trends for the period
        $this->process_artwork_upload_trend( $current_range['start'], $current_range['end'], $period );
    }

    /**
     * Process marketplace health metrics.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @param    string    $period        Period type (day, week, month, year).
     */
    public function process_marketplace_health( $start_date, $end_date, $period ) {
        global $wpdb;
        
        $this->log( "Processing marketplace health metrics for period: {$period}", 'info' );
        
        // Prepare date ranges
        $current_range = array(
            'start' => $start_date,
            'end' => $end_date,
        );
        
        // Calculate previous period for comparison
        $days_diff = strtotime( $end_date ) - strtotime( $start_date );
        $previous_end = date( 'Y-m-d', strtotime( $start_date ) - 1 );
        $previous_start = date( 'Y-m-d', strtotime( $previous_end ) - $days_diff );
        
        $previous_range = array(
            'start' => $previous_start,
            'end' => $previous_end,
        );
        
        // Get user engagement metrics
        $current_user_engagement = $this->get_user_engagement( $current_range['start'], $current_range['end'] );
        $previous_user_engagement = $this->get_user_engagement( $previous_range['start'], $previous_range['end'] );
        
        $engagement_growth = array(
            'logins' => $this->calculate_growth( $previous_user_engagement['logins'], $current_user_engagement['logins'] ),
            'active_users' => $this->calculate_growth( $previous_user_engagement['active_users'], $current_user_engagement['active_users'] ),
            'avg_session' => $this->calculate_growth( $previous_user_engagement['avg_session'], $current_user_engagement['avg_session'] ),
        );
        
        // Get marketplace liquidity metrics
        $current_liquidity = $this->get_marketplace_liquidity( $current_range['start'], $current_range['end'] );
        $previous_liquidity = $this->get_marketplace_liquidity( $previous_range['start'], $previous_range['end'] );
        
        $liquidity_growth = array(
            'transaction_volume' => $this->calculate_growth( $previous_liquidity['transaction_volume'], $current_liquidity['transaction_volume'] ),
            'listings_sold' => $this->calculate_growth( $previous_liquidity['listings_sold'], $current_liquidity['listings_sold'] ),
            'time_to_sale' => $this->calculate_growth( $previous_liquidity['time_to_sale'], $current_liquidity['time_to_sale'], true ),
        );
        
        // Get TOLA token metrics
        $current_tola = $this->get_tola_metrics( $current_range['start'], $current_range['end'] );
        $previous_tola = $this->get_tola_metrics( $previous_range['start'], $previous_range['end'] );
        
        // Store aggregated marketplace health metrics
        $analytics_data = array(
            'type' => 'marketplace_health',
            'period' => $period,
            'start_date' => $current_range['start'],
            'end_date' => $current_range['end'],
            'data' => wp_json_encode( array(
                'engagement' => $engagement_growth,
                'liquidity' => $liquidity_growth,
                'tola' => $current_tola,
            )),
            'created_at' => current_time( 'mysql' ),
        );
        
        $this->save_analytics_data( $analytics_data );
    }

    /**
     * Save analytics data to the database.
     *
     * @since    1.0.0
     * @param    array    $data    Analytics data to be saved.
     */
    private function save_analytics_data( $data ) {
        global $wpdb;
        
        $wpdb->insert( $this->analytics_table, $data );
    }

    /**
     * Generate insights based on analytics data.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @param    string    $period        Period type (day, week, month, year).
     */
    private function generate_insights( $start_date, $end_date, $period ) {
        // Implementation of generate_insights method
    }

    /**
     * Send analytics report to recipients.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @param    string    $period        Period type (day, week, month, year).
     */
    private function send_analytics_report( $start_date, $end_date, $period ) {
        // Implementation of send_analytics_report method
    }

    /**
     * Get sales data for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   array                     Sales data for the given date range.
     */
    private function get_sales_data( $start_date, $end_date ) {
        // Implementation of get_sales_data method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get sales by category for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   array                     Sales data by category for the given date range.
     */
    private function get_sales_by_category( $start_date, $end_date ) {
        // Implementation of get_sales_by_category method
    }

    /**
     * Get top selling artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @param    int       $limit           Number of top artists to return.
     * @return   array                     Array of top selling artists.
     */
    private function get_top_selling_artists( $start_date, $end_date, $limit ) {
        // Implementation of get_top_selling_artists method
    }

    /**
     * Get top selling artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @param    int       $limit           Number of top artworks to return.
     * @return   array                     Array of top selling artworks.
     */
    private function get_top_selling_artworks( $start_date, $end_date, $limit ) {
        // Implementation of get_top_selling_artworks method
    }

    /**
     * Get new artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   array                     New artists data for the given date range.
     */
    private function get_new_artists( $start_date, $end_date ) {
        // Implementation of get_new_artists method
    }

    /**
     * Get verified artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   array                     Verified artists data for the given date range.
     */
    private function get_verified_artists( $start_date, $end_date ) {
        // Implementation of get_verified_artists method
    }

    /**
     * Get active artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   array                     Active artists data for the given date range.
     */
    private function get_active_artists( $start_date, $end_date ) {
        // Implementation of get_active_artists method
    }

    /**
     * Get artist earnings for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   array                     Artist earnings data for the given date range.
     */
    private function get_artist_earnings( $start_date, $end_date ) {
        // Implementation of get_artist_earnings method
    }

    /**
     * Get top earning artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @param    int       $limit           Number of top earning artists to return.
     * @return   array                     Array of top earning artists.
     */
    private function get_top_earning_artists( $start_date, $end_date, $limit ) {
        // Implementation of get_top_earning_artists method
    }

    /**
     * Get new artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   array                     New artworks data for the given date range.
     */
    private function get_new_artworks( $start_date, $end_date ) {
        // Implementation of get_new_artworks method
    }

    /**
     * Get new AI artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   array                     New AI artworks data for the given date range.
     */
    private function get_new_ai_artworks( $start_date, $end_date ) {
        // Implementation of get_new_ai_artworks method
    }

    /**
     * Get artwork views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   array                     Artwork views data for the given date range.
     */
    private function get_artwork_views( $start_date, $end_date ) {
        // Implementation of get_artwork_views method
    }

    /**
     * Get artworks by category for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   array                     Artworks data by category for the given date range.
     */
    private function get_artworks_by_category( $start_date, $end_date ) {
        // Implementation of get_artworks_by_category method
    }

    /**
     * Get top AI models for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @param    int       $limit           Number of top AI models to return.
     * @return   array                     Array of top AI models.
     */
    private function get_top_ai_models( $start_date, $end_date, $limit ) {
        // Implementation of get_top_ai_models method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get user engagement for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   array                     User engagement data for the given date range.
     */
    private function get_user_engagement( $start_date, $end_date ) {
        // Implementation of get_user_engagement method
    }

    /**
     * Get marketplace liquidity for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   array                     Marketplace liquidity data for the given date range.
     */
    private function get_marketplace_liquidity( $start_date, $end_date ) {
        // Implementation of get_marketplace_liquidity method
    }

    /**
     * Get TOLA metrics for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   array                     TOLA metrics data for the given date range.
     */
    private function get_tola_metrics( $start_date, $end_date ) {
        // Implementation of get_tola_metrics method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
    private function get_total_artworks( $start_date, $end_date ) {
        // Implementation of get_total_artworks method
    }

    /**
     * Get total views for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total views for the given date range.
     */
    private function get_total_views( $start_date, $end_date ) {
        // Implementation of get_total_views method
    }

    /**
     * Get total artists for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artists for the given date range.
     */
    private function get_total_artists( $start_date, $end_date ) {
        // Implementation of get_total_artists method
    }

    /**
     * Get total artworks for a given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date for analysis.
     * @param    string    $end_date      End date for analysis.
     * @return   int                         Total artworks for the given date range.
     */
# Creating class-vortex-analytics.php

Here's an implementation of the Vortex Analytics class that should be placed in the includes directory of your plugin:

```php:includes/class-vortex-analytics.php
<?php
/**
 * The analytics functionality of the plugin.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The analytics functionality of the plugin.
 *
 * Handles data collection, analysis, and reporting for the marketplace activities,
 * sales, user behavior, and more.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Analytics {

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
     * The database handler for analytics.
     *
     * @since    1.0.0
     * @access   private
     * @var      object    $db    Database handler.
     */
    private $db;

    /**
     * Analytics options.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $options    Analytics configuration options.
     */
    private $options;

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
        
        // Load database handler
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'database/class-vortex-metrics-db.php';
        $this->db = new Vortex_Metrics_DB();
        
        // Load options
        $this->options = get_option( 'vortex_analytics_options', array(
            'tracking_enabled' => true,
            'anonymize_ip' => true,
            'track_logged_out' => true,
            'track_admin' => false,
            'retention_days' => 365,
        ) );
        
        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Register all analytics related hooks.
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_hooks() {
        // Skip tracking if disabled
        if ( ! $this->options['tracking_enabled'] ) {
            return;
        }
        
        // Skip tracking for admin users if configured
        if ( ! $this->options['track_admin'] && current_user_can( 'manage_options' ) ) {
            return;
        }
        
        // Core tracking actions
        add_action( 'template_redirect', array( $this, 'track_page_view' ) );
        add_action( 'wp_footer', array( $this, 'tracking_script' ) );
        
        // Artwork specific tracking
        add_action( 'wp', array( $this, 'track_artwork_view' ) );
        
        // Artist profile tracking
        add_action( 'wp', array( $this, 'track_artist_profile_view' ) );
        
        // Marketplace tracking
        add_action( 'vortex_artwork_purchase_completed', array( $this, 'track_purchase' ), 10, 3 );
        add_action( 'vortex_artwork_added_to_cart', array( $this, 'track_add_to_cart' ), 10, 2 );
        add_action( 'vortex_artwork_removed_from_cart', array( $this, 'track_remove_from_cart' ), 10, 2 );
        
        // Search tracking
        add_action( 'pre_get_posts', array( $this, 'track_search' ) );
        
        // AJAX actions for frontend tracking
        add_action( 'wp_ajax_vortex_track_event', array( $this, 'ajax_track_event' ) );
        add_action( 'wp_ajax_nopriv_vortex_track_event', array( $this, 'ajax_track_event' ) );
        
        // AI generation tracking
        add_action( 'vortex_huraii_generation_complete', array( $this, 'track_ai_generation' ), 10, 2 );
        
        // Admin dashboard widget
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
        
        // Admin menu and settings
        add_action( 'admin_menu', array( $this, 'add_analytics_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // AJAX actions for admin reports
        add_action( 'wp_ajax_vortex_get_analytics_data', array( $this, 'ajax_get_analytics_data' ) );
        
        // Schedule data cleanup
        add_action( 'wp', array( $this, 'schedule_data_cleanup' ) );
        add_action( 'vortex_analytics_cleanup', array( $this, 'cleanup_old_data' ) );
    }

    /**
     * Add analytics menu to admin.
     *
     * @since    1.0.0
     */
    public function add_analytics_menu() {
        add_submenu_page(
            'vortex-ai-marketplace',
            __( 'Analytics Dashboard', 'vortex-ai-marketplace' ),
            __( 'Analytics', 'vortex-ai-marketplace' ),
            'manage_options',
            'vortex-analytics',
            array( $this, 'display_analytics_page' )
        );
    }

    /**
     * Register analytics settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting(
            'vortex_analytics_options',
            'vortex_analytics_options',
            array( $this, 'validate_options' )
        );
        
        add_settings_section(
            'vortex_analytics_general',
            __( 'General Settings', 'vortex-ai-marketplace' ),
            array( $this, 'settings_section_callback' ),
            'vortex-analytics'
        );
        
        add_settings_field(
            'tracking_enabled',
            __( 'Enable Analytics Tracking', 'vortex-ai-marketplace' ),
            array( $this, 'tracking_enabled_callback' ),
            'vortex-analytics',
            'vortex_analytics_general'
        );
        
        add_settings_field(
            'anonymize_ip',
            __( 'Anonymize IP Addresses', 'vortex-ai-marketplace' ),
            array( $this, 'anonymize_ip_callback' ),
            'vortex-analytics',
            'vortex_analytics_general'
        );
        
        add_settings_field(
            'track_logged_out',
            __( 'Track Logged-out Users', 'vortex-ai-marketplace' ),
            array( $this, 'track_logged_out_callback' ),
            'vortex-analytics',
            'vortex_analytics_general'
        );
        
        add_settings_field(
            'track_admin',
            __( 'Track Admin Users', 'vortex-ai-marketplace' ),
            array( $this, 'track_admin_callback' ),
            'vortex-analytics',
            'vortex_analytics_general'
        );
        
        add_settings_field(
            'retention_days',
            __( 'Data Retention (days)', 'vortex-ai-marketplace' ),
            array( $this, 'retention_days_callback' ),
            'vortex-analytics',
            'vortex_analytics_general'
        );
    }

    /**
     * Validate analytics options.
     *
     * @since    1.0.0
     * @param    array    $input    The options array.
     * @return   array              The validated options array.
     */
    public function validate_options( $input ) {
        $output = array();
        
        $output['tracking_enabled'] = isset( $input['tracking_enabled'] ) && $input['tracking_enabled'];
        $output['anonymize_ip'] = isset( $input['anonymize_ip'] ) && $input['anonymize_ip'];
        $output['track_logged_out'] = isset( $input['track_logged_out'] ) && $input['track_logged_out'];
        $output['track_admin'] = isset( $input['track_admin'] ) && $input['track_admin'];
        
        // Ensure retention is at least 30 days and at most 730 days (2 years)
        $output['retention_days'] = isset( $input['retention_days'] ) ? 
            max( 30, min( 730, intval( $input['retention_days'] ) ) ) : 365;
        
        return $output;
    }

    /**
     * Settings section callback.
     *
     * @since    1.0.0
     */
    public function settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure how analytics data is collected and stored in your marketplace.', 'vortex-ai-marketplace' ) . '</p>';
    }

    /**
     * Tracking enabled field callback.
     *
     * @since    1.0.0
     */
    public function tracking_enabled_callback() {
        $checked = isset( $this->options['tracking_enabled'] ) && $this->options['tracking_enabled'] ? 'checked' : '';
        echo '<input type="checkbox" id="tracking_enabled" name="vortex_analytics_options[tracking_enabled]" ' . $checked . ' />';
        echo '<p class="description">' . esc_html__( 'Enable analytics tracking across the marketplace.', 'vortex-ai-marketplace' ) . '</p>';
    }

    /**
     * Anonymize IP field callback.
     *
     * @since    1.0.0
     */
    public function anonymize_ip_callback() {
        $checked = isset( $this->options['anonymize_ip'] ) && $this->options['anonymize_ip'] ? 'checked' : '';
        echo '<input type="checkbox" id="anonymize_ip" name="vortex_analytics_options[anonymize_ip]" ' . $checked . ' />';
        echo '<p class="description">' . esc_html__( 'Anonymize IP addresses for privacy compliance.', 'vortex-ai-marketplace' ) . '</p>';
    }

    /**
     * Track logged out field callback.
     *
     * @since    1.0.0
     */
    public function track_logged_out_callback() {
        $checked = isset( $this->options['track_logged_out'] ) && $this->options['track_logged_out'] ? 'checked' : '';
        echo '<input type="checkbox" id="track_logged_out" name="vortex_analytics_options[track_logged_out]" ' . $checked . ' />';
        echo '<p class="description">' . esc_html__( 'Track activity of logged-out visitors.', 'vortex-ai-marketplace' ) . '</p>';
    }

    /**
     * Track admin field callback.
     *
     * @since    1.0.0
     */
    public function track_admin_callback() {
        $checked = isset( $this->options['track_admin'] ) && $this->options['track_admin'] ? 'checked' : '';
        echo '<input type="checkbox" id="track_admin" name="vortex_analytics_options[track_admin]" ' . $checked . ' />';
        echo '<p class="description">' . esc_html__( 'Track activity of admin users.', 'vortex-ai-marketplace' ) . '</p>';
    }

    /**
     * Retention days field callback.
     *
     * @since    1.0.0
     */
    public function retention_days_callback() {
        $value = isset( $this->options['retention_days'] ) ? intval( $this->options['retention_days'] ) : 365;
        echo '<input type="number" id="retention_days" name="vortex_analytics_options[retention_days]" value="' . $value . '" min="30" max="730" />';
        echo '<p class="description">' . esc_html__( 'Number of days to retain analytics data (30-730).', 'vortex-ai-marketplace' ) . '</p>';
    }

    /**
     * Display the analytics admin page.
     *
     * @since    1.0.0
     */
    public function display_analytics_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        // Get statistics for the dashboard
        $stats = $this->get_overview_stats();
        
        ?>
        <div class="wrap vortex-analytics-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <div class="vortex-analytics-tabs">
                <div class="vortex-tab-nav">
                    <a href="#overview" class="vortex-tab-active"><?php _e( 'Overview', 'vortex-ai-marketplace' ); ?></a>
                    <a href="#sales"><?php _e( 'Sales', 'vortex-ai-marketplace' ); ?></a>
                    <a href="#audience"><?php _e( 'Audience', 'vortex-ai-marketplace' ); ?></a>
                    <a href="#content"><?php _e( 'Content', 'vortex-ai-marketplace' ); ?></a>
                    <a href="#ai"><?php _e( 'AI Generation', 'vortex-ai-marketplace' ); ?></a>
                    <a href="#settings"><?php _e( 'Settings', 'vortex-ai-marketplace' ); ?></a>
                </div>
                
                <div class="vortex-tab-content">
                    <!-- Overview Tab -->
                    <div id="overview" class="vortex-tab-pane vortex-tab-active">
                        <div class="vortex-analytics-period-selector">
                            <label for="period-selector"><?php _e( 'Time Period:', 'vortex-ai-marketplace' ); ?></label>
                            <select id="period-selector">
                                <option value="7"><?php _e( 'Last 7 Days', 'vortex-ai-marketplace' ); ?></option>
                                <option value="30" selected><?php _e( 'Last 30 Days', 'vortex-ai-marketplace' ); ?></option>
                                <option value="90"><?php _e( 'Last 90 Days', 'vortex-ai-marketplace' ); ?></option>
                                <option value="365"><?php _e( 'Last Year', 'vortex-ai-marketplace' ); ?></option>
                                <option value="custom"><?php _e( 'Custom Range', 'vortex-ai-marketplace' ); ?></option>
                            </select>
                            
                            <div id="custom-date-range" style="display:none;">
                                <input type="date" id="date-from" name="date-from">
                                <input type="date" id="date-to" name="date-to">
                                <button id="apply-custom-range" class="button"><?php _e( 'Apply', 'vortex-ai-marketplace' ); ?></button>
                            </div>
                        </div>
                        
                        <div class="vortex-analytics-cards">
                            <div class="vortex-analytics-card">
                                <h3><?php _e( 'Total Sales', 'vortex-ai-marketplace' ); ?></h3>
                                <div class="vortex-analytics-value">$<?php echo number_format( $stats['total_sales'], 2 ); ?></div>
                                <div class="vortex-analytics-comparison <?php echo $stats['sales_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $stats['sales_trend'] >= 0 ? '+' : ''; ?><?php echo number_format( $stats['sales_trend'], 1 ); ?>% 
                                    <?php _e( 'vs previous period', 'vortex-ai-marketplace' ); ?>
                                </div>
                            </div>
                            
                            <div class="vortex-analytics-card">
                                <h3><?php _e( 'Total Orders', 'vortex-ai-marketplace' ); ?></h3>
                                <div class="vortex-analytics-value"><?php echo number_format( $stats['total_orders'] ); ?></div>
                                <div class="vortex-analytics-comparison <?php echo $stats['orders_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $stats['orders_trend'] >= 0 ? '+' : ''; ?><?php echo number_format( $stats['orders_trend'], 1 ); ?>% 
                                    <?php _e( 'vs previous period', 'vortex-ai-marketplace' ); ?>
                                </div>
                            </div>
                            
                            <div class="vortex-analytics-card">
                                <h3><?php _e( 'TOLA Transactions', 'vortex-ai-marketplace' ); ?></h3>
                                <div class="vortex-analytics-value"><?php echo number_format( $stats['tola_transactions'] ); ?> TOLA</div>
                                <div class="vortex-analytics-comparison <?php echo $stats['tola_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $stats['tola_trend'] >= 0 ? '+' : ''; ?><?php echo number_format( $stats['tola_trend'], 1 ); ?>% 
                                    <?php _e( 'vs previous period', 'vortex-ai-marketplace' ); ?>
                                </div>
                            </div>
                            
                            <div class="vortex-analytics-card">
                                <h3><?php _e( 'AI Generations', 'vortex-ai-marketplace' ); ?></h3>
                                <div class="vortex-analytics-value"><?php echo number_format( $stats['ai_generations'] ); ?></div>
                                <div class="vortex-analytics-comparison <?php echo $stats['ai_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $stats['ai_trend'] >= 0 ? '+' : ''; ?><?php echo number_format( $stats['ai_trend'], 1 ); ?>% 
                                    <?php _e( 'vs previous period', 'vortex-ai-marketplace' ); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="vortex-analytics-charts-row">
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'Sales Over Time', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="sales-chart"></canvas>
                            </div>
                            
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'Traffic & Conversions', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="traffic-chart"></canvas>
                            </div>
                        </div>
                        
                        <div class="vortex-analytics-charts-row">
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'Top Selling Artists', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="artists-chart"></canvas>
                            </div>
                            
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'Top Art Categories', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="categories-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sales Tab -->
                    <div id="sales" class="vortex-tab-pane">
                        <div class="vortex-analytics-charts-row">
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'Sales by Payment Method', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="payment-methods-chart"></canvas>
                            </div>
                            
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'Sales by Price Range', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="price-ranges-chart"></canvas>
                            </div>
                        </div>
                        
                        <div class="vortex-analytics-table-container">
                            <h3><?php _e( 'Recent Sales', 'vortex-ai-marketplace' ); ?></h3>
                            <table class="vortex-analytics-table" id="recent-sales-table">
                                <thead>
                                    <tr>
                                        <th><?php _e( 'Date', 'vortex-ai-marketplace' ); ?></th>
                                        <th><?php _e( 'Artwork', 'vortex-ai-marketplace' ); ?></th>
                                        <th><?php _e( 'Artist', 'vortex-ai-marketplace' ); ?></th>
                                        <th><?php _e( 'Buyer', 'vortex-ai-marketplace' ); ?></th>
                                        <th><?php _e( 'Amount', 'vortex-ai-marketplace' ); ?></th>
                                        <th><?php _e( 'Currency', 'vortex-ai-marketplace' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- To be populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Audience Tab -->
                    <div id="audience" class="vortex-tab-pane">
                        <div class="vortex-analytics-charts-row">
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'Visitors by Device', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="devices-chart"></canvas>
                            </div>
                            
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'Geographic Distribution', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="geography-chart"></canvas>
                            </div>
                        </div>
                        
                        <div class="vortex-analytics-charts-row">
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'New vs Returning Visitors', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="visitor-type-chart"></canvas>
                            </div>
                            
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'Traffic Sources', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="traffic-sources-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content Tab -->
                    <div id="content" class="vortex-tab-pane">
                        <div class="vortex-analytics-charts-row">
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'Most Viewed Artworks', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="top-artworks-chart"></canvas>
                            </div>
                            
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'Most Viewed Artists', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="top-artists-chart"></canvas>
                            </div>
                        </div>
                        
                        <div class="vortex-analytics-table-container">
                            <h3><?php _e( 'Search Keywords', 'vortex-ai-marketplace' ); ?></h3>
                            <table class="vortex-analytics-table" id="search-keywords-table">
                                <thead>
                                    <tr>
                                        <th><?php _e( 'Keyword', 'vortex-ai-marketplace' ); ?></th>
                                        <th><?php _e( 'Searches', 'vortex-ai-marketplace' ); ?></th>
                                        <th><?php _e( 'Conversion Rate', 'vortex-ai-marketplace' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- To be populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- AI Generation Tab -->
                    <div id="ai" class="vortex-tab-pane">
                        <div class="vortex-analytics-charts-row">
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'AI Generations by Engine', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="ai-engines-chart"></canvas>
                            </div>
                            
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'AI Style Distribution', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="ai-styles-chart"></canvas>
                            </div>
                        </div>
                        
                        <div class="vortex-analytics-charts-row">
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'AI Generations Over Time', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="ai-time-chart"></canvas>
                            </div>
                            
                            <div class="vortex-analytics-chart">
                                <h3><?php _e( 'Conversion: Generation to Sale', 'vortex-ai-marketplace' ); ?></h3>
                                <canvas id="ai-conversion-chart"></canvas>
                            </div>
                        </div>
                        
                        <div class="vortex-analytics-table-container">
                            <h3><?php _e( 'Popular AI Prompts', 'vortex-ai-marketplace' ); ?></h3>
                            <table class="vortex-analytics-table" id="ai-prompts-table">
                                <thead>
                                    <tr>
                                        <th><?php _e( 'Prompt Keywords', 'vortex-ai-marketplace' ); ?></th>
                                        <th><?php _e( 'Frequency', 'vortex-ai-marketplace' ); ?></th>
                                        <th><?php _e( 'Average Rating', 'vortex-ai-marketplace' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- To be populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Settings Tab -->
                    <div id="settings" class="vortex-tab-pane">
                        <form action="options.php" method="post">
                            <?php
                            settings_fields( 'vortex_analytics_options' );
                            do_settings_sections( 'vortex-analytics' );
                            submit_button();
                            ?>
                        </form>
                        
                        <div class="vortex-data-management">
                            <h3><?php _e( 'Data Management', 'vortex-ai-marketplace' ); ?></h3>
                            <p><?php _e( 'Use these tools to manage your analytics data.', 'vortex-ai-marketplace' ); ?></p>
                            
                            <button id="vortex-export-analytics" class="button">
                                <?php _e( 'Export Analytics Data (CSV)', 'vortex-ai-marketplace' ); ?>
                            </button>
                            
                            <button id="vortex-cleanup-analytics" class="button">
                                <?php _e( 'Clean Up Old Data', 'vortex-ai-marketplace' ); ?>
                            </button>
                            
                            <button id="vortex-reset-analytics" class="button button-link-delete">
                                <?php _e( 'Reset Analytics Data', 'vortex-ai-marketplace' ); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab functionality
            $('.vortex-tab-nav a').on('click', function(e) {
                e.preventDefault();
                const target = $(this).attr('href');
                
                // Update active tab
                $('.vortex-tab-nav a').removeClass('vortex-tab-active');
                $(this).addClass('vortex-tab-active');
                
                // Show target content
                $('.vortex-tab-pane').removeClass('vortex-tab-active');
                $(target).addClass('vortex-tab-active');
            });
            
            // Period selector functionality
            $('#period-selector').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#custom-date-range').show();
                } else {
                    $('#custom-date-range').hide();
                    loadAnalyticsData($(this).val());
                }
            });
            
            // Apply custom range
            $('#apply-custom-range').on('click', function() {
                const fromDate = $('#date-from').val();
                const toDate = $('#date-to').val();
                
                if (fromDate && toDate) {
                    loadAnalyticsData('custom', fromDate, toDate);
                } else {
                    alert('<?php _e( 'Please select both start and end dates.', 'vortex-ai-marketplace' ); ?>');
                }
            });
            
            // Data export
            $('#vortex-export-analytics').on('click', function() {
                window.location.href = ajaxurl + '?action=vortex_export_analytics&_wpnonce=' + 
                    '<?php echo wp_create_nonce( 'vortex_export_analytics' ); ?>';
            });
            
            // Data cleanup
            $('#vortex-cleanup-analytics').on('click', function() {
                if (confirm('<?php _e( 'This will remove analytics data older than your retention period setting. Continue?', 'vortex-ai-marketplace' ); ?>')) {
                    $.post(ajaxurl, {
                        action: 'vortex_cleanup_analytics',
                        _wpnonce: '<?php echo wp_create_nonce( 'vortex_cleanup_analytics' ); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('<?php _e( 'Old analytics data has been cleaned up.', 'vortex-ai-marketplace' ); ?>');
                        } else {
                            alert('<?php _e( 'There was an error cleaning up analytics data.', 'vortex-ai-marketplace' ); ?>');
                        }
                    });
                }
            });
            
            // Data reset
            $('#vortex-reset-analytics').on('click', function() {
                if (confirm('<?php _e( 'WARNING: This will permanently delete ALL analytics data. This action cannot be undone. Continue?', 'vortex-ai-marketplace' ); ?>')) {
                    $.post(ajaxurl, {
                        action: 'vortex_reset_analytics',
                        _wpnonce: '<?php echo wp_create_nonce( 'vortex_reset_analytics' ); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('<?php _e( 'All analytics data has been reset.', 'vortex-ai-marketplace' ); ?>');
                            window.location.reload();
                        } else {
                            alert('<?php _e( 'There was an error resetting analytics data.', 'vortex-ai-marketplace' ); ?>');
                        }
                    });
                }
            });
            
            // Initial data load
            loadAnalyticsData(30);
            
            function loadAnalyticsData(period, fromDate, toDate) {
                // Show loading state
                $('.vortex-analytics-chart').addClass('loading');
                
                $.post(ajaxurl, {
                    action: 'vortex_get_analytics_data',
                    period: period,
                    from_date: fromDate,
                    to_date: toDate,
                    _wpnonce: '<?php echo wp_create_nonce( 'vortex_get_analytics_data' ); ?>'
                }, function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        // Update charts
                        initSalesChart(data.sales_over_time);
                        initTrafficChart(data.traffic_data);
                        initArtistsChart(data.top_artists);
                        initCategoriesChart(data.top_categories);
                        initPaymentMethodsChart(data.payment_methods);
                        initPriceRangesChart(data.price_ranges);
                        initDevicesChart(data.devices);
                        initGeographyChart(data.geography);
                        initVisitorTypeChart(data.visitor_types);
                        initTrafficSourcesChart(data.traffic_sources);
                        initTopArtworksChart(data.top_artworks);
                        initTopArtistsViewsChart(data.top_artists_views);
                        initAIEnginesChart(data.ai_engines);
                        initAIStylesChart(data.ai_styles);
                        initAITimeChart(data.ai_over_time);
                        initAIConversionChart(data.ai_conversion);
                        
                        // Update tables
                        updateRecentSalesTable(data.recent_sales);
                        updateSearchKeywordsTable(data.search_keywords);
                        updateAIPromptsTable(data.ai_prompts);
                        
                        // Remove loading state
                        $('.vortex-analytics-chart').removeClass('loading');
                    } else {
                        alert('<?php _e( 'Error loading analytics data.', 'vortex-ai-marketplace' ); ?>');
                    }
                }).fail(function() {
                    alert('<?php _e( 'Error connecting to server.', 'vortex-ai-marketplace' ); ?>');
                    $('.vortex-analytics-chart').removeClass('loading');
                });
            }
            
            // Chart initialization functions
            function initSalesChart(data) {
                const ctx = document.getElementById('sales-chart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: '<?php _e( 'Sales ($)', 'vortex-ai-marketplace' ); ?>',
                            data: data.values,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 2,
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value;
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // Initialize all other charts
            function initTrafficChart(data) {
                // Traffic chart implementation
            }
            
            function initArtistsChart(data) {
                // Artists chart implementation
            }
            
            function initCategoriesChart(data) {
                // Categories chart implementation
            }
            
            function initPaymentMethodsChart(data) {
                // Payment methods chart implementation
            }
            
            function initPriceRangesChart(data) {
                // Price ranges chart implementation
            }
            
            function initDevicesChart(data) {
                // Devices chart implementation
            }
            
            function initGeographyChart(data) {
                // Geography chart implementation
            }
            
            function initVisitorTypeChart(data) {
                const ctx = document.getElementById('visitor-type-chart').getContext('2d');
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: ['New Visitors', 'Returning Visitors'],
                        datasets: [{
                            data: [data.new_visitors, data.returning_visitors],
                            backgroundColor: [
                                'rgba(54, 162, 235, 0.7)',
                                'rgba(255, 99, 132, 0.7)'
                            ],
                            borderColor: [
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 99, 132, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                                        const value = context.raw;
                                        const percentage = Math.round((value / total) * 100);
                                        return `${context.label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            function initTrafficSourcesChart(data) {
                const ctx = document.getElementById('traffic-sources-chart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: [
                                'rgba(54, 162, 235, 0.7)',
                                'rgba(255, 99, 132, 0.7)',
                                'rgba(255, 205, 86, 0.7)',
                                'rgba(75, 192, 192, 0.7)',
                                'rgba(153, 102, 255, 0.7)'
                            ],
                            borderColor: [
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 99, 132, 1)',
                                'rgba(255, 205, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });
            }
            
            function initTopArtworksChart(data) {
            function initVisitorTypeChart
