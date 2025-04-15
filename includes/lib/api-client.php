<?php
/**
 * API Client for communicating with the Vortex SaaS Backend
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/lib
 */

/**
 * API Client class.
 *
 * This class handles all the API communication with the Vortex SaaS Backend.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/lib
 */
class Vortex_API_Client {

	/**
	 * The API endpoint URL.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $api_endpoint    The API endpoint URL.
	 */
	private $api_endpoint;

	/**
	 * The API key.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $api_key    The API key for authentication.
	 */
	private $api_key;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->api_key = get_option( 'vortex_api_key' );
		$this->api_endpoint = get_option( 'vortex_api_endpoint', 'https://www.vortexartec.com/api/v1' );
	}

	/**
	 * Make a GET request to the API.
	 *
	 * @since    1.0.0
	 * @param    string    $endpoint    The API endpoint to call.
	 * @param    array     $params      Optional parameters to include in the request.
	 * @return   array|WP_Error         The API response or WP_Error on failure.
	 */
	public function get( $endpoint, $params = array() ) {
		return $this->request( 'GET', $endpoint, $params );
	}

	/**
	 * Make a POST request to the API.
	 *
	 * @since    1.0.0
	 * @param    string    $endpoint    The API endpoint to call.
	 * @param    array     $data        The data to send in the request body.
	 * @return   array|WP_Error         The API response or WP_Error on failure.
	 */
	public function post( $endpoint, $data = array() ) {
		return $this->request( 'POST', $endpoint, $data );
	}

	/**
	 * Make a PUT request to the API.
	 *
	 * @since    1.0.0
	 * @param    string    $endpoint    The API endpoint to call.
	 * @param    array     $data        The data to send in the request body.
	 * @return   array|WP_Error         The API response or WP_Error on failure.
	 */
	public function put( $endpoint, $data = array() ) {
		return $this->request( 'PUT', $endpoint, $data );
	}

	/**
	 * Make a DELETE request to the API.
	 *
	 * @since    1.0.0
	 * @param    string    $endpoint    The API endpoint to call.
	 * @param    array     $params      Optional parameters to include in the request.
	 * @return   array|WP_Error         The API response or WP_Error on failure.
	 */
	public function delete( $endpoint, $params = array() ) {
		return $this->request( 'DELETE', $endpoint, $params );
	}

	/**
	 * Make a request to the API.
	 *
	 * @since    1.0.0
	 * @param    string    $method      The HTTP method to use (GET, POST, PUT, DELETE).
	 * @param    string    $endpoint    The API endpoint to call.
	 * @param    array     $data        The data to include in the request.
	 * @return   array|WP_Error         The API response or WP_Error on failure.
	 */
	private function request( $method, $endpoint, $data = array() ) {
		// Make sure we have an API key.
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'missing_api_key', __( 'API key is missing. Please configure it in the plugin settings.', 'vortex-ai-marketplace' ) );
		}

		// Build the request URL.
		$url = trailingslashit( $this->api_endpoint ) . ltrim( $endpoint, '/' );

		// Set up the request arguments.
		$args = array(
			'method'  => $method,
			'headers' => array(
				'X-API-Key'    => $this->api_key,
				'Content-Type' => 'application/json',
			),
			'timeout' => 30,
		);

		// Add data to the request.
		if ( 'GET' === $method || 'DELETE' === $method ) {
			$url = add_query_arg( $data, $url );
		} else {
			$args['body'] = wp_json_encode( $data );
		}

		// Make the request.
		$response = wp_remote_request( $url, $args );

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Get the response code.
		$response_code = wp_remote_retrieve_response_code( $response );

		// Parse the response body.
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Handle API errors.
		if ( $response_code >= 400 ) {
			$error_message = isset( $body['message'] ) ? $body['message'] : __( 'Unknown API error occurred.', 'vortex-ai-marketplace' );
			return new WP_Error( 'api_error', $error_message, array( 'status' => $response_code ) );
		}

		return $body;
	}

	/**
	 * Get market predictions.
	 *
	 * @since    1.0.0
	 * @param    string    $asset_type    The type of asset to get predictions for.
	 * @param    string    $time_frame    The time frame for predictions.
	 * @return   array|WP_Error           The API response or WP_Error on failure.
	 */
	public function get_market_predictions( $asset_type = 'all', $time_frame = '7days' ) {
		return $this->get( 'market-predictions', array(
			'asset_type' => $asset_type,
			'time_frame' => $time_frame,
		) );
	}

	/**
	 * Get asset prediction.
	 *
	 * @since    1.0.0
	 * @param    int       $asset_id      The ID of the asset to get predictions for.
	 * @param    string    $time_frame    The time frame for predictions.
	 * @return   array|WP_Error           The API response or WP_Error on failure.
	 */
	public function get_asset_prediction( $asset_id, $time_frame = '7days' ) {
		return $this->get( "market-predictions/asset/{$asset_id}", array(
			'time_frame' => $time_frame,
		) );
	}

	/**
	 * Get market analytics.
	 *
	 * @since    1.0.0
	 * @param    string    $start_date    The start date for analytics.
	 * @param    string    $end_date      The end date for analytics.
	 * @return   array|WP_Error           The API response or WP_Error on failure.
	 */
	public function get_market_analytics( $start_date = '', $end_date = '' ) {
		if ( empty( $start_date ) ) {
			$start_date = date( 'Y-m-d', strtotime( '-30 days' ) );
		}

		if ( empty( $end_date ) ) {
			$end_date = date( 'Y-m-d' );
		}

		return $this->post( 'analytics', array(
			'operation'  => 'market_overview',
			'api_key'    => $this->api_key,
			'start_date' => $start_date,
			'end_date'   => $end_date,
		) );
	}

	/**
	 * Get artist performance analytics.
	 *
	 * @since    1.0.0
	 * @param    int       $artist_id     The ID of the artist.
	 * @param    string    $start_date    The start date for analytics.
	 * @param    string    $end_date      The end date for analytics.
	 * @return   array|WP_Error           The API response or WP_Error on failure.
	 */
	public function get_artist_performance( $artist_id, $start_date = '', $end_date = '' ) {
		if ( empty( $start_date ) ) {
			$start_date = date( 'Y-m-d', strtotime( '-30 days' ) );
		}

		if ( empty( $end_date ) ) {
			$end_date = date( 'Y-m-d' );
		}

		return $this->post( 'analytics', array(
			'operation'  => 'artist_performance',
			'api_key'    => $this->api_key,
			'artist_id'  => $artist_id,
			'start_date' => $start_date,
			'end_date'   => $end_date,
		) );
	}

	/**
	 * Get sales metrics.
	 *
	 * @since    1.0.0
	 * @param    string    $start_date    The start date for analytics.
	 * @param    string    $end_date      The end date for analytics.
	 * @param    array     $filters       Additional filters to apply.
	 * @return   array|WP_Error           The API response or WP_Error on failure.
	 */
	public function get_sales_metrics( $start_date = '', $end_date = '', $filters = array() ) {
		if ( empty( $start_date ) ) {
			$start_date = date( 'Y-m-d', strtotime( '-30 days' ) );
		}

		if ( empty( $end_date ) ) {
			$end_date = date( 'Y-m-d' );
		}

		return $this->post( 'analytics', array(
			'operation'  => 'sales_metrics',
			'api_key'    => $this->api_key,
			'start_date' => $start_date,
			'end_date'   => $end_date,
			'filters'    => $filters,
		) );
	}

	/**
	 * Analyze artwork.
	 *
	 * @since    1.0.0
	 * @param    int       $artwork_id    The ID of the artwork to analyze.
	 * @return   array|WP_Error           The API response or WP_Error on failure.
	 */
	public function analyze_artwork( $artwork_id ) {
		return $this->post( 'ai/compute', array(
			'operation'  => 'analyze_artwork',
			'api_key'    => $this->api_key,
			'artwork_id' => $artwork_id,
		) );
	}

	/**
	 * Get business strategy recommendations.
	 *
	 * @since    1.0.0
	 * @param    array     $business_profile    The business profile data.
	 * @return   array|WP_Error                The API response or WP_Error on failure.
	 */
	public function get_business_strategy( $business_profile ) {
		return $this->post( 'ai/compute', array(
			'operation'        => 'get_business_strategy',
			'api_key'          => $this->api_key,
			'business_profile' => $business_profile,
		) );
	}
} 