<?php
/**
 * VORTEX Market Predictor
 *
 * @package    Vortex
 * @subpackage Vortex/Prediction
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class for predicting market trends based on historical data
 */
class VORTEX_Market_Predictor {
    /**
     * Historical data storage
     *
     * @var array
     */
    private $historical_data;

    /**
     * Confidence level for predictions
     *
     * @var float
     */
    private $confidence_level;

    /**
     * Error margin for predictions
     *
     * @var float
     */
    private $error_margin;

    /**
     * Database table name
     *
     * @var string
     */
    private $table_name;

    /**
     * Initialize the class and set properties
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vortex_market_data';
        $this->historical_data = array();
        $this->confidence_level = 0.85;
        $this->error_margin = 0.05;
        
        // Register AJAX handlers
        add_action( 'wp_ajax_get_market_prediction', array( $this, 'ajax_get_market_prediction' ) );
        add_action( 'wp_ajax_nopriv_get_market_prediction', array( $this, 'ajax_get_market_prediction' ) );
        
        // Register REST API endpoints
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route(
            'vortex/v1',
            '/market-predictions',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_market_predictions' ),
                'permission_callback' => array( $this, 'check_rest_permission' ),
            )
        );
        
        register_rest_route(
            'vortex/v1',
            '/market-predictions/asset/(?P<asset_id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_asset_prediction' ),
                'permission_callback' => array( $this, 'check_rest_permission' ),
                'args'                => array(
                    'asset_id' => array(
                        'validate_callback' => function( $param ) {
                            return is_numeric( $param );
                        }
                    ),
                ),
            )
        );
    }

    /**
     * Check permissions for REST API endpoints
     *
     * @param WP_REST_Request $request Current request
     * @return bool
     */
    public function check_rest_permission( $request ) {
        // Check for API key in header
        $headers = $request->get_headers();
        if ( isset( $headers['x_vortex_api_key'] ) && $this->validate_api_key( $headers['x_vortex_api_key'][0] ) ) {
            return true;
        }
        
        // Otherwise require authentication
        return current_user_can( 'read' );
    }

    /**
     * Validate API key
     *
     * @param string $api_key API key to validate
     * @return bool
     */
    private function validate_api_key( $api_key ) {
        $valid_key = get_option( 'vortex_market_predictor_api_key' );
        return $api_key === $valid_key;
    }

    /**
     * AJAX handler for market predictions
     */
    public function ajax_get_market_prediction() {
        // Verify nonce
        if ( ! check_ajax_referer( 'vortex_market_prediction_nonce', 'security', false ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
            wp_die();
        }
        
        // Get parameters
        $asset_type = isset( $_POST['asset_type'] ) ? sanitize_text_field( $_POST['asset_type'] ) : 'all';
        $time_frame = isset( $_POST['time_frame'] ) ? sanitize_text_field( $_POST['time_frame'] ) : '7days';
        
        // Get prediction
        $prediction = $this->predict_market_trend( $asset_type, $time_frame );
        
        wp_send_json_success( $prediction );
        wp_die();
    }

    /**
     * Get market predictions for REST API
     *
     * @param WP_REST_Request $request Current request
     * @return WP_REST_Response
     */
    public function get_market_predictions( $request ) {
        $asset_type = $request->get_param( 'asset_type' ) ? sanitize_text_field( $request->get_param( 'asset_type' ) ) : 'all';
        $time_frame = $request->get_param( 'time_frame' ) ? sanitize_text_field( $request->get_param( 'time_frame' ) ) : '7days';
        
        $prediction = $this->predict_market_trend( $asset_type, $time_frame );
        
        return rest_ensure_response( $prediction );
    }

    /**
     * Get prediction for a specific asset
     *
     * @param WP_REST_Request $request Current request
     * @return WP_REST_Response
     */
    public function get_asset_prediction( $request ) {
        $asset_id = $request->get_param( 'asset_id' );
        $time_frame = $request->get_param( 'time_frame' ) ? sanitize_text_field( $request->get_param( 'time_frame' ) ) : '7days';
        
        $prediction = $this->predict_asset_performance( $asset_id, $time_frame );
        
        return rest_ensure_response( $prediction );
    }

    /**
     * Predict market trend
     *
     * @param string $asset_type Type of asset
     * @param string $time_frame Time frame for prediction
     * @return array
     */
    public function predict_market_trend( $asset_type = 'all', $time_frame = '7days' ) {
        // Load historical data
        $this->load_historical_data( $asset_type );
        
        // Calculate prediction based on time frame
        $days = $this->convert_time_frame_to_days( $time_frame );
        
        // Apply prediction algorithm
        $trend_data = $this->apply_prediction_algorithm( $days );
        
        return array(
            'asset_type'        => $asset_type,
            'time_frame'        => $time_frame,
            'prediction'        => $trend_data['prediction'],
            'confidence'        => $trend_data['confidence'],
            'supporting_data'   => $trend_data['supporting_data'],
            'last_updated'      => current_time( 'mysql' ),
        );
    }

    /**
     * Predict performance for a specific asset
     *
     * @param int $asset_id Asset ID
     * @param string $time_frame Time frame for prediction
     * @return array
     */
    public function predict_asset_performance( $asset_id, $time_frame = '7days' ) {
        // Load asset specific data
        $this->load_asset_data( $asset_id );
        
        // Calculate prediction based on time frame
        $days = $this->convert_time_frame_to_days( $time_frame );
        
        // Apply asset-specific prediction algorithm
        $performance_data = $this->apply_asset_prediction_algorithm( $asset_id, $days );
        
        return array(
            'asset_id'          => $asset_id,
            'time_frame'        => $time_frame,
            'prediction'        => $performance_data['prediction'],
            'confidence'        => $performance_data['confidence'],
            'price_change'      => $performance_data['price_change'],
            'volume_change'     => $performance_data['volume_change'],
            'last_updated'      => current_time( 'mysql' ),
        );
    }

    /**
     * Load historical market data
     *
     * @param string $asset_type Type of asset
     */
    private function load_historical_data( $asset_type = 'all' ) {
        global $wpdb;
        
        $query = "SELECT * FROM {$this->table_name} WHERE 1=1";
        
        if ( 'all' !== $asset_type ) {
            $query .= $wpdb->prepare( " AND asset_type = %s", $asset_type );
        }
        
        $query .= " ORDER BY date_recorded DESC LIMIT 90"; // Get 90 days of data
        
        $this->historical_data = $wpdb->get_results( $query, ARRAY_A );
        
        // If no data found, fallback to default data
        if ( empty( $this->historical_data ) ) {
            $this->historical_data = $this->get_default_historical_data();
        }
    }

    /**
     * Load data for a specific asset
     *
     * @param int $asset_id Asset ID
     */
    private function load_asset_data( $asset_id ) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE asset_id = %d ORDER BY date_recorded DESC LIMIT 90",
            $asset_id
        );
        
        $this->historical_data = $wpdb->get_results( $query, ARRAY_A );
        
        // If no data found, fallback to default data
        if ( empty( $this->historical_data ) ) {
            $this->historical_data = $this->get_default_asset_data( $asset_id );
        }
    }

    /**
     * Apply prediction algorithm
     *
     * @param int $days Number of days to predict
     * @return array
     */
    private function apply_prediction_algorithm( $days ) {
        // Simple moving average calculation for demonstration
        $prices = array_column( $this->historical_data, 'price' );
        $volumes = array_column( $this->historical_data, 'volume' );
        
        // Calculate trends
        $price_trend = $this->calculate_trend( $prices );
        $volume_trend = $this->calculate_trend( $volumes );
        
        // Determine market sentiment
        $sentiment = $this->determine_market_sentiment();
        
        // Combine factors for final prediction
        $prediction_value = $this->combine_prediction_factors( $price_trend, $volume_trend, $sentiment );
        
        // Calculate confidence level
        $confidence = $this->calculate_confidence_level( $price_trend, $volume_trend, $sentiment );
        
        return array(
            'prediction' => $prediction_value,
            'confidence' => $confidence,
            'supporting_data' => array(
                'price_trend' => $price_trend,
                'volume_trend' => $volume_trend,
                'sentiment' => $sentiment,
            ),
        );
    }

    /**
     * Apply asset prediction algorithm
     *
     * @param int $asset_id Asset ID
     * @param int $days Number of days to predict
     * @return array
     */
    private function apply_asset_prediction_algorithm( $asset_id, $days ) {
        // Get specific asset data
        $prices = array_column( $this->historical_data, 'price' );
        $volumes = array_column( $this->historical_data, 'volume' );
        
        // Calculate more complex metrics for asset
        $volatility = $this->calculate_volatility( $prices );
        $momentum = $this->calculate_momentum( $prices );
        $relative_strength = $this->calculate_relative_strength( $asset_id );
        
        // Project price change
        $price_change = $this->project_price_change( $prices, $volatility, $momentum, $days );
        
        // Project volume change
        $volume_change = $this->project_volume_change( $volumes, $momentum, $days );
        
        // Generate confidence score
        $confidence = $this->calculate_asset_confidence( $volatility, $momentum, $relative_strength );
        
        return array(
            'prediction' => ($price_change > 0) ? 'bullish' : 'bearish',
            'confidence' => $confidence,
            'price_change' => $price_change,
            'volume_change' => $volume_change,
        );
    }

    /**
     * Convert time frame string to number of days
     *
     * @param string $time_frame Time frame string
     * @return int
     */
    private function convert_time_frame_to_days( $time_frame ) {
        switch ( $time_frame ) {
            case '24h':
                return 1;
            case '7days':
                return 7;
            case '30days':
                return 30;
            case '90days':
                return 90;
            case '1year':
                return 365;
            default:
                return 7; // Default to 7 days
        }
    }

    /**
     * Calculate trend from historical data
     *
     * @param array $data Data points
     * @return float
     */
    private function calculate_trend( $data ) {
        if ( count( $data ) < 2 ) {
            return 0;
        }
        
        // Use simple linear regression
        $x = range( 1, count( $data ) );
        $y = array_reverse( $data ); // Most recent first
        
        $x_mean = array_sum( $x ) / count( $x );
        $y_mean = array_sum( $y ) / count( $y );
        
        $numerator = 0;
        $denominator = 0;
        
        foreach ( $x as $i => $x_i ) {
            $numerator += ( $x_i - $x_mean ) * ( $y[ $i ] - $y_mean );
            $denominator += pow( $x_i - $x_mean, 2 );
        }
        
        if ( $denominator == 0 ) {
            return 0;
        }
        
        return $numerator / $denominator;
    }

    /**
     * Determine market sentiment
     *
     * @return float
     */
    private function determine_market_sentiment() {
        // In a real implementation, this would analyze external data sources
        // For demonstration, return a random value between -1 and 1
        return ( mt_rand( -100, 100 ) / 100 );
    }

    /**
     * Combine prediction factors
     *
     * @param float $price_trend Price trend
     * @param float $volume_trend Volume trend
     * @param float $sentiment Market sentiment
     * @return string
     */
    private function combine_prediction_factors( $price_trend, $volume_trend, $sentiment ) {
        // Weight factors
        $price_weight = 0.5;
        $volume_weight = 0.3;
        $sentiment_weight = 0.2;
        
        $combined_score = ($price_trend * $price_weight) + 
                         ($volume_trend * $volume_weight) + 
                         ($sentiment * $sentiment_weight);
        
        // Translate score to prediction
        if ( $combined_score > 0.1 ) {
            return 'bullish';
        } elseif ( $combined_score < -0.1 ) {
            return 'bearish';
        } else {
            return 'neutral';
        }
    }

    /**
     * Calculate confidence level
     *
     * @param float $price_trend Price trend
     * @param float $volume_trend Volume trend
     * @param float $sentiment Market sentiment
     * @return float
     */
    private function calculate_confidence_level( $price_trend, $volume_trend, $sentiment ) {
        // Calculate correlation between factors
        $correlation = abs( $price_trend * $volume_trend * $sentiment );
        
        // Consistency factor (higher if trends align)
        $consistency = 0;
        if ( ($price_trend > 0 && $volume_trend > 0 && $sentiment > 0) || 
             ($price_trend < 0 && $volume_trend < 0 && $sentiment < 0) ) {
            $consistency = 0.2;
        }
        
        // Base confidence plus adjustments
        $confidence = $this->confidence_level + $correlation + $consistency;
        
        // Limit to 0-1 range
        return max( 0, min( 1, $confidence ) );
    }

    /**
     * Calculate volatility
     *
     * @param array $prices Price data
     * @return float
     */
    private function calculate_volatility( $prices ) {
        if ( count( $prices ) < 2 ) {
            return 0;
        }
        
        // Calculate standard deviation of percentage changes
        $changes = array();
        $prev = $prices[0];
        
        for ( $i = 1; $i < count( $prices ); $i++ ) {
            if ( $prev != 0 ) {
                $changes[] = ($prices[$i] - $prev) / $prev;
            }
            $prev = $prices[$i];
        }
        
        $mean = array_sum( $changes ) / count( $changes );
        $variance = 0;
        
        foreach ( $changes as $change ) {
            $variance += pow( $change - $mean, 2 );
        }
        
        $variance /= count( $changes );
        return sqrt( $variance );
    }

    /**
     * Calculate momentum
     *
     * @param array $prices Price data
     * @return float
     */
    private function calculate_momentum( $prices ) {
        if ( count( $prices ) < 14 ) {
            return 0;
        }
        
        // Use rate of change over 14 periods
        $current = $prices[0];
        $past = $prices[13];
        
        if ( $past == 0 ) {
            return 0;
        }
        
        return (($current - $past) / $past) * 100;
    }

    /**
     * Calculate relative strength
     *
     * @param int $asset_id Asset ID
     * @return float
     */
    private function calculate_relative_strength( $asset_id ) {
        // In a real implementation, this would compare the asset's performance
        // to a market index or category average
        // For demonstration, return a value between 0 and 2
        return mt_rand( 0, 200 ) / 100;
    }

    /**
     * Project price change
     *
     * @param array $prices Price data
     * @param float $volatility Volatility
     * @param float $momentum Momentum
     * @param int $days Days to project
     * @return float
     */
    private function project_price_change( $prices, $volatility, $momentum, $days ) {
        if ( empty( $prices ) ) {
            return 0;
        }
        
        $current_price = $prices[0];
        $trend = $this->calculate_trend( $prices );
        
        // Base projection on trend
        $raw_change = $trend * $days;
        
        // Adjust for momentum
        $momentum_factor = $momentum / 100;
        
        // Adjust for volatility (high volatility increases uncertainty)
        $volatility_adjustment = 1 - ($volatility / 2);
        
        return $raw_change * (1 + $momentum_factor) * $volatility_adjustment;
    }

    /**
     * Project volume change
     *
     * @param array $volumes Volume data
     * @param float $momentum Momentum
     * @param int $days Days to project
     * @return float
     */
    private function project_volume_change( $volumes, $momentum, $days ) {
        if ( empty( $volumes ) ) {
            return 0;
        }
        
        $current_volume = $volumes[0];
        $trend = $this->calculate_trend( $volumes );
        
        // Volume tends to follow price momentum
        $momentum_factor = $momentum / 50; // Less impact than on price
        
        return $trend * $days * (1 + $momentum_factor);
    }

    /**
     * Calculate asset confidence
     *
     * @param float $volatility Volatility
     * @param float $momentum Momentum
     * @param float $relative_strength Relative strength
     * @return float
     */
    private function calculate_asset_confidence( $volatility, $momentum, $relative_strength ) {
        // High volatility decreases confidence
        $volatility_factor = 1 - $volatility;
        
        // Strong momentum in either direction increases confidence
        $momentum_factor = abs( $momentum ) / 50;
        
        // Relative strength increases confidence
        $strength_factor = $relative_strength / 2;
        
        // Combine factors
        $confidence = $this->confidence_level * $volatility_factor * (1 + $momentum_factor) * $strength_factor;
        
        // Limit to 0-1 range
        return max( 0, min( 1, $confidence ) );
    }

    /**
     * Get default historical data
     *
     * @return array
     */
    private function get_default_historical_data() {
        $data = array();
        $base_price = 100;
        $base_volume = 1000;
        
        for ( $i = 90; $i >= 0; $i-- ) {
            $date = date( 'Y-m-d', strtotime( "-{$i} days" ) );
            
            // Generate slightly random values for demo
            $price = $base_price * (1 + (sin( $i / 10 ) * 0.1) + (mt_rand( -10, 10 ) / 100));
            $volume = $base_volume * (1 + (cos( $i / 15 ) * 0.2) + (mt_rand( -20, 20 ) / 100));
            
            $data[] = array(
                'date_recorded' => $date,
                'price'         => $price,
                'volume'        => $volume,
                'asset_type'    => 'all',
            );
        }
        
        return $data;
    }

    /**
     * Get default asset data
     *
     * @param int $asset_id Asset ID
     * @return array
     */
    private function get_default_asset_data( $asset_id ) {
        $data = array();
        $base_price = 50 + ($asset_id % 10) * 10; // Different base price for each asset
        $base_volume = 500 + ($asset_id % 5) * 200; // Different base volume for each asset
        
        for ( $i = 90; $i >= 0; $i-- ) {
            $date = date( 'Y-m-d', strtotime( "-{$i} days" ) );
            
            // Generate slightly random values for demo
            $price = $base_price * (1 + (sin( $i / 10 + $asset_id ) * 0.15) + (mt_rand( -15, 15 ) / 100));
            $volume = $base_volume * (1 + (cos( $i / 15 + $asset_id ) * 0.25) + (mt_rand( -25, 25 ) / 100));
            
            $data[] = array(
                'date_recorded' => $date,
                'price'         => $price,
                'volume'        => $volume,
                'asset_id'      => $asset_id,
            );
        }
        
        return $data;
    }

    /**
     * Create database table
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            date_recorded date NOT NULL,
            asset_id bigint(20) DEFAULT NULL,
            asset_type varchar(50) DEFAULT 'all',
            price decimal(15,6) NOT NULL DEFAULT 0,
            volume decimal(15,6) NOT NULL DEFAULT 0,
            market_cap decimal(20,6) DEFAULT NULL,
            transactions int(11) DEFAULT 0,
            unique_buyers int(11) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY asset_id (asset_id),
            KEY date_recorded (date_recorded),
            KEY asset_type (asset_type)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
} 