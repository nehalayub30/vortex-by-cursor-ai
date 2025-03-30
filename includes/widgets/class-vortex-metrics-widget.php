<?php
/**
 * The Metrics Widget functionality.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/widgets
 */

/**
 * The Metrics Widget functionality.
 *
 * Displays marketplace metrics and statistics in a widget area with customizable display options.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/widgets
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Metrics_Widget extends WP_Widget {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        parent::__construct(
            'vortex_metrics_widget', // Base ID
            __( 'VORTEX Marketplace Metrics', 'vortex-ai-marketplace' ), // Name
            array(
                'description' => __( 'Display marketplace metrics and statistics.', 'vortex-ai-marketplace' ),
                'classname'   => 'vortex-metrics-widget',
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
        register_widget( 'Vortex_Metrics_Widget' );
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
                'vortex-metrics-widget',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/css/vortex-metrics.css',
                array(),
                VORTEX_VERSION,
                'all'
            );
            
            // Load Chart.js only if charts are enabled
            $settings = $this->get_settings();
            foreach ( $settings as $instance ) {
                if ( ! empty( $instance['show_charts'] ) && $instance['show_charts'] ) {
                    wp_enqueue_script(
                        'chart-js',
                        plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/chart.min.js',
                        array(),
                        '3.7.0',
                        true
                    );
                    break;
                }
            }
            
            wp_enqueue_script(
                'vortex-metrics-widget',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/js/vortex-metrics-display.js',
                array( 'jquery' ),
                VORTEX_VERSION,
                true
            );
            
            // Localize the script with necessary data
            wp_localize_script(
                'vortex-metrics-widget',
                'vortexMetrics',
                array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'vortex_metrics_nonce' ),
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
        $metrics_to_show = ! empty( $instance['metrics_to_show'] ) ? $instance['metrics_to_show'] : array( 'total_sales', 'total_artists', 'total_artworks' );
        $time_period = ! empty( $instance['time_period'] ) ? $instance['time_period'] : '30_days';
        $show_charts = ! empty( $instance['show_charts'] ) ? (bool) $instance['show_charts'] : false;
        $chart_type = ! empty( $instance['chart_type'] ) ? $instance['chart_type'] : 'bar';
        $layout = ! empty( $instance['layout'] ) ? $instance['layout'] : 'cards';
        $card_style = ! empty( $instance['card_style'] ) ? $instance['card_style'] : 'standard';
        
        // Get metrics data
        $metrics_data = $this->get_metrics_data( $metrics_to_show, $time_period );
        
        // Define time period label
        $period_labels = array(
            '7_days'  => __( 'Last 7 Days', 'vortex-ai-marketplace' ),
            '30_days' => __( 'Last 30 Days', 'vortex-ai-marketplace' ),
            '90_days' => __( 'Last 3 Months', 'vortex-ai-marketplace' ),
            'year'    => __( 'Last Year', 'vortex-ai-marketplace' ),
            'all'     => __( 'All Time', 'vortex-ai-marketplace' ),
        );
        $period_label = isset( $period_labels[$time_period] ) ? $period_labels[$time_period] : $period_labels['30_days'];
        
        // Widget container
        echo '<div class="vortex-metrics-container layout-' . esc_attr( $layout ) . ' style-' . esc_attr( $card_style ) . '">';
        
        // Period indicator
        echo '<div class="vortex-metrics-period">' . esc_html( $period_label ) . '</div>';
        
        // Metrics display
        if ( $layout === 'cards' ) {
            $this->render_metrics_cards( $metrics_data );
        } else {
            $this->render_metrics_list( $metrics_data );
        }
        
        // Charts display
        if ( $show_charts && ! empty( $metrics_data['chart_data'] ) ) {
            $chart_id = 'vortex-metrics-chart-' . uniqid();
            echo '<div class="vortex-metrics-chart-container">';
            echo '<canvas id="' . esc_attr( $chart_id ) . '" width="300" height="200"></canvas>';
            echo '</div>';
            
            // Add chart initialization script
            $this->render_chart_script( $chart_id, $metrics_data['chart_data'], $chart_type );
        }
        
        // Footer with attribution
        if ( ! empty( $instance['show_powered_by'] ) && $instance['show_powered_by'] ) {
            echo '<div class="vortex-metrics-footer">';
            echo __( 'Powered by', 'vortex-ai-marketplace' ) . ' <a href="https://vortexartec.com" target="_blank">VORTEX</a>';
            echo '</div>';
        }
        
        echo '</div>'; // End widget container
        
        echo $args['after_widget'];
    }

    /**
     * Render metrics in card layout.
     *
     * @since    1.0.0
     * @param    array    $metrics_data    The metrics data to display.
     */
    private function render_metrics_cards( $metrics_data ) {
        echo '<div class="vortex-metrics-cards">';
        
        foreach ( $metrics_data['metrics'] as $metric ) {
            if ( empty( $metric['value'] ) ) {
                continue;
            }
            
            echo '<div class="vortex-metrics-card">';
            
            // Icon if available
            if ( ! empty( $metric['icon'] ) ) {
                echo '<div class="vortex-metrics-icon">';
                echo '<span class="' . esc_attr( $metric['icon'] ) . '"></span>';
                echo '</div>';
            }
            
            // Metric content
            echo '<div class="vortex-metrics-content">';
            echo '<div class="vortex-metrics-value">' . esc_html( $metric['value'] ) . '</div>';
            echo '<div class="vortex-metrics-label">' . esc_html( $metric['label'] ) . '</div>';
            
            // Trend indicator if available
            if ( isset( $metric['trend'] ) ) {
                $trend_class = $metric['trend'] >= 0 ? 'positive' : 'negative';
                $trend_icon = $metric['trend'] >= 0 ? '↑' : '↓';
                $trend_value = abs( $metric['trend'] ) . '%';
                
                echo '<div class="vortex-metrics-trend ' . esc_attr( $trend_class ) . '">';
                echo esc_html( $trend_icon . ' ' . $trend_value );
                echo '</div>';
            }
            
            echo '</div>'; // End content
            
            echo '</div>'; // End card
        }
        
        echo '</div>'; // End cards container
    }

    /**
     * Render metrics in list layout.
     *
     * @since    1.0.0
     * @param    array    $metrics_data    The metrics data to display.
     */
    private function render_metrics_list( $metrics_data ) {
        echo '<ul class="vortex-metrics-list">';
        
        foreach ( $metrics_data['metrics'] as $metric ) {
            if ( empty( $metric['value'] ) ) {
                continue;
            }
            
            echo '<li class="vortex-metrics-item">';
            
            // Icon if available
            if ( ! empty( $metric['icon'] ) ) {
                echo '<span class="vortex-metrics-icon ' . esc_attr( $metric['icon'] ) . '"></span>';
            }
            
            // Label
            echo '<span class="vortex-metrics-label">' . esc_html( $metric['label'] ) . '</span>';
            
            // Value
            echo '<span class="vortex-metrics-value">' . esc_html( $metric['value'] ) . '</span>';
            
            // Trend indicator if available
            if ( isset( $metric['trend'] ) ) {
                $trend_class = $metric['trend'] >= 0 ? 'positive' : 'negative';
                $trend_icon = $metric['trend'] >= 0 ? '↑' : '↓';
                $trend_value = abs( $metric['trend'] ) . '%';
                
                echo '<span class="vortex-metrics-trend ' . esc_attr( $trend_class ) . '">';
                echo esc_html( $trend_icon . ' ' . $trend_value );
                echo '</span>';
            }
            
            echo '</li>';
        }
        
        echo '</ul>';
    }

    /**
     * Render chart initialization script.
     *
     * @since    1.0.0
     * @param    string    $chart_id       The chart canvas ID.
     * @param    array     $chart_data     The chart data.
     * @param    string    $chart_type     The chart type.
     */
    private function render_chart_script( $chart_id, $chart_data, $chart_type ) {
        // Prepare data for JavaScript
        $labels_json = json_encode( $chart_data['labels'] );
        $datasets_json = json_encode( $chart_data['datasets'] );
        
        // Chart configuration
        $chart_config = array(
            'type' => $chart_type,
            'data' => array(
                'labels' => $chart_data['labels'],
                'datasets' => $chart_data['datasets'],
            ),
            'options' => array(
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => array(
                    'legend' => array(
                        'position' => 'bottom',
                        'labels' => array(
                            'padding' => 20,
                        ),
                    ),
                ),
            ),
        );
        
        // Output script
        echo '<script type="text/javascript">';
        echo 'jQuery(document).ready(function($) {';
        echo '  if (typeof Chart !== "undefined") {';
        echo '    var ctx = document.getElementById("' . esc_js( $chart_id ) . '").getContext("2d");';
        echo '    new Chart(ctx, ' . json_encode( $chart_config ) . ');';
        echo '  }';
        echo '});';
        echo '</script>';
    }

    /**
     * Get metrics data based on selected metrics and time period.
     *
     * @since    1.0.0
     * @param    array     $metrics_to_show    The metrics to retrieve.
     * @param    string    $time_period        The time period for the metrics.
     * @return   array                         The metrics data.
     */
    private function get_metrics_data( $metrics_to_show, $time_period ) {
        global $wpdb;
        
        $result = array(
            'metrics' => array(),
            'chart_data' => array(
                'labels' => array(),
                'datasets' => array(),
            ),
        );
        
        // Define date range
        $end_date = current_time( 'mysql' );
        $start_date = '';
        
        switch ( $time_period ) {
            case '7_days':
                $start_date = date( 'Y-m-d H:i:s', strtotime( '-7 days', current_time( 'timestamp' ) ) );
                break;
                
            case '30_days':
                $start_date = date( 'Y-m-d H:i:s', strtotime( '-30 days', current_time( 'timestamp' ) ) );
                break;
                
            case '90_days':
                $start_date = date( 'Y-m-d H:i:s', strtotime( '-90 days', current_time( 'timestamp' ) ) );
                break;
                
            case 'year':
                $start_date = date( 'Y-m-d H:i:s', strtotime( '-1 year', current_time( 'timestamp' ) ) );
                break;
                
            case 'all':
            default:
                $start_date = '1970-01-01 00:00:00'; // Beginning of time (all data)
                break;
        }
        
        // Previous period for trend calculations
        $previous_end_date = $start_date;
        $days_diff = round( ( strtotime( $end_date ) - strtotime( $start_date ) ) / ( 60 * 60 * 24 ) );
        $previous_start_date = date( 'Y-m-d H:i:s', strtotime( '-' . $days_diff . ' days', strtotime( $start_date ) ) );
        
        // Collect requested metrics
        foreach ( $metrics_to_show as $metric ) {
            switch ( $metric ) {
                case 'total_sales':
                    // Total sales amount
                    $current_sales = $this->get_sales_total( $start_date, $end_date );
                    $previous_sales = $this->get_sales_total( $previous_start_date, $previous_end_date );
                    
                    // Calculate trend
                    $trend = 0;
                    if ( $previous_sales > 0 ) {
                        $trend = round( ( ( $current_sales - $previous_sales ) / $previous_sales ) * 100, 1 );
                    }
                    
                    $result['metrics']['total_sales'] = array(
                        'label' => __( 'Total Sales', 'vortex-ai-marketplace' ),
                        'value' => '$' . number_format( $current_sales, 0 ),
                        'trend' => $trend,
                        'icon'  => 'dashicons dashicons-chart-bar',
                    );
                    
                    // Add chart data
                    $sales_by_day = $this->get_sales_by_day( $start_date, $end_date );
                    $result['chart_data']['labels'] = array_keys( $sales_by_day );
                    $result['chart_data']['datasets'][] = array(
                        'label' => __( 'Sales ($)', 'vortex-ai-marketplace' ),
                        'data' => array_values( $sales_by_day ),
                        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 1,
                    );
                    break;
                
                case 'order_count':
                    // Total number of orders
                    $current_orders = $this->get_order_count( $start_date, $end_date );
                    $previous_orders = $this->get_order_count( $previous_start_date, $previous_end_date );
                    
                    // Calculate trend
                    $trend = 0;
                    if ( $previous_orders > 0 ) {
                        $trend = round( ( ( $current_orders - $previous_orders ) / $previous_orders ) * 100, 1 );
                    }
                    
                    $result['metrics']['order_count'] = array(
                        'label' => __( 'Orders', 'vortex-ai-marketplace' ),
                        'value' => number_format( $current_orders ),
                        'trend' => $trend,
                        'icon'  => 'dashicons dashicons-cart',
                    );
                    
                    // Add to chart data if not already added
                    if ( empty( $result['chart_data']['labels'] ) ) {
                        $orders_by_day = $this->get_orders_by_day( $start_date, $end_date );
                        $result['chart_data']['labels'] = array_keys( $orders_by_day );
                        $result['chart_data']['datasets'][] = array(
                            'label' => __( 'Orders', 'vortex-ai-marketplace' ),
                            'data' => array_values( $orders_by_day ),
                            'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                            'borderColor' => 'rgba(75, 192, 192, 1)',
                            'borderWidth' => 1,
                        );
                    }
                    break;
                
                case 'total_artists':
                    // Total number of artists
                    $current_artists = $this->get_artist_count( $start_date, $end_date );
                    $previous_artists = $this->get_artist_count( $previous_start_date, $previous_end_date );
                    
                    // Calculate trend
                    $trend = 0;
                    if ( $previous_artists > 0 ) {
                        $trend = round( ( ( $current_artists - $previous_artists ) / $previous_artists ) * 100, 1 );
                    }
                    
                    $result['metrics']['total_artists'] = array(
                        'label' => __( 'Artists', 'vortex-ai-marketplace' ),
                        'value' => number_format( $current_artists ),
                        'trend' => $trend,
                        'icon'  => 'dashicons dashicons-businessperson',
                    );
                    break;
                
                case 'total_artworks':
                    // Total number of artworks
                    $current_artworks = $this->get_artwork_count( $start_date, $end_date );
                    $previous_artworks = $this->get_artwork_count( $previous_start_date, $previous_end_date );
                    
                    // Calculate trend
                    $trend = 0;
                    if ( $previous_artworks > 0 ) {
                        $trend = round( ( ( $current_artworks - $previous_artworks ) / $previous_artworks ) * 100, 1 );
                    }
                    
                    $result['metrics']['total_artworks'] = array(
                        'label' => __( 'Artworks', 'vortex-ai-marketplace' ),
                        'value' => number_format( $current_artworks ),
                        'trend' => $trend,
                        'icon'  => 'dashicons dashicons-art',
                    );
                    break;
                
                case 'ai_generations':
                    // Total number of AI generations
                    $current_generations = $this->get_ai_generation_count( $start_date, $end_date );
                    $previous_generations = $this->get_ai_generation_count( $previous_start_date, $previous_end_date );
                    
                    // Calculate trend
                    $trend = 0;
                    if ( $previous_generations > 0 ) {
                        $trend = round( ( ( $current_generations - $previous_generations ) / $previous_generations ) * 100, 1 );
                    }
                    
                    $result['metrics']['ai_generations'] = array(
                        'label' => __( 'AI Generations', 'vortex-ai-marketplace' ),
                        'value' => number_format( $current_generations ),
                        'trend' => $trend,
                        'icon'  => 'dashicons dashicons-welcome-view-site',
                    );
                    break;
                
                case 'tola_volume':
                    // Total TOLA transaction volume
                    $current_volume = $this->get_tola_volume( $start_date, $end_date );
                    $previous_volume = $this->get_tola_volume( $previous_start_date, $previous_end_date );
                    
                    // Calculate trend
                    $trend = 0;
                    if ( $previous_volume > 0 ) {
                        $trend = round( ( ( $current_volume - $previous_volume ) / $previous_volume ) * 100, 1 );
                    }
                    
                    $result['metrics']['tola_volume'] = array(
                        'label' => __( 'TOLA Volume', 'vortex-ai-marketplace' ),
                        'value' => number_format( $current_volume, 2 ) . ' TOLA',
                        'trend' => $trend,
                        'icon'  => 'dashicons dashicons-money-alt',
                    );
                    break;
                
                case 'avg_artwork_price':
                    // Average artwork price
                    $current_avg_price = $this->get_avg_artwork_price( $start_date, $end_date );
                    $previous_avg_price = $this->get_avg_artwork_price( $previous_start_date, $previous_end_date );
                    
                    // Calculate trend
                    $trend = 0;
                    if ( $previous_avg_price > 0 ) {
                        $trend = round( ( ( $current_avg_price - $previous_avg_price ) / $previous_avg_price ) * 100, 1 );
                    }
                    
                    $result['metrics']['avg_artwork_price'] = array(
                        'label' => __( 'Avg. Price', 'vortex-ai-marketplace' ),
                        'value' => '$' . number_format( $current_avg_price, 2 ),
                        'trend' => $trend,
                        'icon'  => 'dashicons dashicons-tag',
                    );
                    break;
            }
        }
        
        return $result;
    }

    /**
     * Get total sales amount for the specified period.
     *
     * @since    1.0.0
     * @param    string    $start_date    Period start date.
     * @param    string    $end_date      Period end date.
     * @return   float                    Total sales amount.
     */
    private function get_sales_total( $start_date, $end_date ) {
        global $wpdb;
        
        $sales_table = $wpdb->prefix . 'vortex_sales';
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$sales_table'" ) !== $sales_table ) {
            return 0; // Table doesn't exist
        }
        
        $total = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(amount) FROM $sales_table 
             WHERE currency = 'USD' AND sale_date BETWEEN %s AND %s",
            $start_date,
            $end_date
        ) );
        
        // Add TOLA sales converted to USD
        $tola_rate = get_option( 'vortex_tola_usd_rate', 1 );
        
        $tola_total = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(amount) FROM $sales_table 
             WHERE currency = 'TOLA' AND sale_date BETWEEN %s AND %s",
            $start_date,
            $end_date
        ) );
        
        $total += $tola_total * $tola_rate;
        
        return floatval( $total );
    }

    /**
     * Get sales by day for the specified period.
     *
     * @since    1.0.0
     * @param    string    $start_date    Period start date.
     * @param    string    $end_date      Period end date.
     * @return   array                    Sales by day.
     */
    private function get_sales_by_day( $start_date, $end_date ) {
        global $wpdb;
        
        $sales_table = $wpdb->prefix . 'vortex_sales';
        $sales_by_day = array();
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$sales_table'" ) !== $sales_table ) {
            return $sales_by_day; // Table doesn't exist
        }
        
        // Get USD sales by day
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(sale_date) as sale_day, SUM(amount) as total 
             FROM $sales_table 
             WHERE currency = 'USD' AND sale_date BETWEEN %s AND %s 
             GROUP BY DATE(sale_date) 
             ORDER BY sale_day ASC",
            $start_date,
            $end_date
        ) );
        
        foreach ( $results as $row ) {
            $sales_by_day[$row->sale_day] = floatval( $row->total );
        }
        
        // Add TOLA sales converted to USD
        $tola_rate = get_option( 'vortex_tola_usd_rate', 1 );
        
        $tola_results = $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(sale_date) as sale_day, SUM(amount) as total 
             FROM $sales_table 
             WHERE currency = 'TOLA' AND sale_date BETWEEN %s AND %s 
             GROUP BY DATE(sale_day) 
             ORDER BY sale_day ASC",
            $start_date,
            $end_date
        ) );
        
        foreach ( $tola_results as $row ) {
            $day = $row->sale_day;
            $tola_value = floatval( $row->total ) * $tola_rate;
            
            if ( isset( $sales_by_day[$day] ) ) {
                $sales_by_day[$day] += $tola_value;
            } else {
                $sales_by_day[$day] = $tola_value;
            }
        }
        
        // Fill in missing days with zeros
        $start = new DateTime( $start_date );
        $end = new DateTime( $end_date );
        $interval = new DateInterval( 'P1D' );
        $period = new DatePeriod( $start, $interval, $end );
        
        $complete_sales_by_day = array();
        
        foreach ( $period as $date ) {
            $day = $date->format( 'Y-m-d' );
            $complete_sales_by_day[$day] = isset( $sales_by_day[$day] ) ? $sales_by_day[$day] : 0;
        }
        
        return $complete_sales_by_day;
    }

    /**
     * Get order count for the specified period.
     *
     * @since    1.0.0
     * @param    string    $start_date    Period start date.
     * @param    string    $end_date      Period end date.
     * @return   int                      Order count.
     */
    private function get_order_count( $start_date, $end_date ) {
        global $wpdb;
        
        $sales_table = $wpdb->prefix . 'vortex_sales';
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$sales_table'" ) !== $sales_table ) {
            return 0; // Table doesn't exist
        }
        
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $sales_table 
             WHERE sale_date BETWEEN %s AND %s",
            $start_date,
            $end_date
        ) );
        
        return intval( $count );
    }

    /**
     * Get orders by day for the specified period.
     *
     * @since    1.0.0
     * @param    string    $start_date    Period start date.
     * @param    string    $end_date      Period end date.
     * @return   array                    Orders by day.
     */
    private function get_orders_by_day( $start_date, $end_date ) {
        global $wpdb;
        
        $sales_table = $wpdb->prefix . 'vortex_sales';
        $orders_by_day = array();
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$sales_table'" ) !== $sales_table ) {
            return $orders_by_day; // Table doesn't exist
        }
        
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(sale_date) as sale_day, COUNT(*) as count 
             FROM $sales_table 
             WHERE sale_date BETWEEN %s AND %s 
             GROUP BY DATE(sale_date) 
             ORDER BY sale_day ASC",
            $start_date,
            $end_date
        ) );
        
        foreach ( $results as $row ) {
            $orders_by_day[$row->sale_day] = intval( $row->count );
        }
        
        // Fill in missing days with zeros
        $start = new DateTime( $start_date );
        $end = new DateTime( $end_date );
        $interval = new DateInterval( 'P1D' );
        $period = new DatePeriod( $start, $interval, $end );
        
        $complete_orders_by_day = array();
        
        foreach ( $period as $date ) {
            $day = $date->format( 'Y-m-d' );
            $complete_orders_by_day[$day] = isset( $orders_by_day[$day] ) ? $orders_by_day[$day] : 0;
        }
        
        return $complete_orders_by_day;
    }

    /**
     * Get artist count for the specified period.
     *
     * @since    1.0.0
     * @param    string    $start_date    Period start date.
     * @param    string    $end_date      Period end date.
     * @return   int                      Artist count.
     */
    private function get_artist_count( $start_date, $end_date ) {
        // Get all artist posts created within the period
        $args = array(
            'post_type'      => 'vortex_artist',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'date_query'     => array(
                array(
                    'after'     => $start_date,
                    'before'    => $end_date,
                    'inclusive' => true,
                ),
            ),
        );
        
        $query = new WP_Query( $args );
        return $query->found_posts;
    }

    /**
     * Get artwork count for the specified period.
     *
     * @since    1.0.0
     * @param    string    $start_date    Period start date.
     * @param    string    $end_date      Period end date.
     * @return   int                      Artwork count.
     */
    private function get_artwork_count( $start_date, $end_date ) {
        // Get all artwork posts created within the period
        $args = array(
            'post_type'      => 'vortex_artwork',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'date_query'     => array(
                array(
                    'after'     => $start_date,
                    'before'    => $end_date,
                    'inclusive' => true,
                ),
            ),
        );
        
        $query = new WP_Query( $args );
        return $query->found_posts;
    }

    /**
     * Get AI generation count for the specified period.
     *
     * @since    1.0.0
     * @param    string    $start_date    Period start date.
     * @param    string    $end_date      Period end date.
     * @return   int                      AI generation count.
     */
    private function get_ai_generation_count( $start_date, $end_date ) {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'vortex_events';
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$events_table'" ) !== $events_table ) {
            // Fallback to post meta query
            $args = array(
                'post_type'      => 'vortex_artwork',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'     => '_vortex_created_with_huraii',
                        'value'   => '1',
                        'compare' => '=',
                    ),
                ),
                'date_query'     => array(
                    array(
                        'after'     => $start_date,
                        'before'    => $end_date,
                        'inclusive' => true,
                    ),
                ),
            );
            
            $query = new WP_Query( $args );
            return $query->found_posts;
        }
        
        // Use events table
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $events_table 
             WHERE event_type = 'ai_generation' 
             AND timestamp BETWEEN %s AND %s",
            $start_date,
            $end_date
        ) );
        
        return intval( $count );
    }

    /**
     * Get TOLA transaction volume for the specified period.
     *
     * @since    1.0.0
     * @param    string    $start_date    Period start date.
     * @param    string    $end_date      Period end date.
     * @return   float                    TOLA transaction volume.
     */
    private function get_tola_volume( $start_date, $end_date ) {
        global $wpdb;
        
        $transactions_table = $wpdb->prefix . 'vortex_transactions';
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$transactions_table'" ) !== $transactions_table ) {
            // Fallback to sales table
            $sales_table = $wpdb->prefix . 'vortex_sales';
            
            if ( $wpdb->get_var( "SHOW TABLES LIKE '$sales_table'" ) !== $sales_table ) {
                return 0; // No tables available
            }
            
            $volume = $wpdb->get_var( $wpdb->prepare(
                "SELECT SUM(amount) FROM $sales_table 
                 WHERE currency = 'TOLA' AND sale_date BETWEEN %s AND %s",
                $start_date,
                $end_date
            ) );
            
            return floatval( $volume );
        }
        
        // Use transactions table
        $volume = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(amount) FROM $transactions_table 
             WHERE token_type = 'TOLA' AND created_at BETWEEN %s AND %s",
            $start_date,
            $end_date
        ) );
        
        return floatval( $volume );
    }

    /**
     * Get average artwork price for the specified period.
     *
     * @since    1.0.0
     * @param    string    $start_date    Period start date.
     * @param    string    $end_date      Period end date.
     * @return   float                    Average artwork price
</rewritten_file> 