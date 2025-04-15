<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/frontend
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks for
 * enqueuing the public-facing stylesheet and JavaScript.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/frontend
 */
class Vortex_Public {

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
	 * The API client instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Vortex_API_Client    $api_client    The API client instance.
	 */
	private $api_client;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of the plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->api_client = new Vortex_API_Client();
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, VORTEX_AI_MARKETPLACE_PLUGIN_URL . 'assets/css/style.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, VORTEX_AI_MARKETPLACE_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), $this->version, false );

		// Localize the script with data for AJAX requests
		wp_localize_script(
			$this->plugin_name,
			'vortex_ai_marketplace',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'vortex_ai_marketplace_nonce' ),
			)
		);
	}

	/**
	 * Render a template from the templates directory.
	 *
	 * @since    1.0.0
	 * @param    string    $template_name    The name of the template to render.
	 * @param    array     $args             The variables to pass to the template.
	 * @return   string                       The rendered template.
	 */
	public function render_template( $template_name, $args = array() ) {
		// Extract the variables to a local namespace
		extract( $args );

		// Start output buffering
		ob_start();

		// Include the template
		include VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/frontend/templates/' . $template_name . '.php';

		// Get the buffered content
		$template_content = ob_get_clean();

		return $template_content;
	}

	/**
	 * Get market predictions from the API.
	 *
	 * @since    1.0.0
	 * @param    string    $asset_type    The type of asset to get predictions for.
	 * @param    string    $time_frame    The time frame for predictions.
	 * @return   array|WP_Error           The API response or WP_Error on failure.
	 */
	public function get_market_predictions( $asset_type = 'all', $time_frame = '7days' ) {
		return $this->api_client->get_market_predictions( $asset_type, $time_frame );
	}

	/**
	 * Get asset prediction from the API.
	 *
	 * @since    1.0.0
	 * @param    int       $asset_id      The ID of the asset to get predictions for.
	 * @param    string    $time_frame    The time frame for predictions.
	 * @return   array|WP_Error           The API response or WP_Error on failure.
	 */
	public function get_asset_prediction( $asset_id, $time_frame = '7days' ) {
		return $this->api_client->get_asset_prediction( $asset_id, $time_frame );
	}

	/**
	 * Get market analytics from the API.
	 *
	 * @since    1.0.0
	 * @param    string    $start_date    The start date for analytics.
	 * @param    string    $end_date      The end date for analytics.
	 * @return   array|WP_Error           The API response or WP_Error on failure.
	 */
	public function get_market_analytics( $start_date = '', $end_date = '' ) {
		return $this->api_client->get_market_analytics( $start_date, $end_date );
	}

	/**
	 * Get artist performance from the API.
	 *
	 * @since    1.0.0
	 * @param    int       $artist_id     The ID of the artist.
	 * @param    string    $start_date    The start date for analytics.
	 * @param    string    $end_date      The end date for analytics.
	 * @return   array|WP_Error           The API response or WP_Error on failure.
	 */
	public function get_artist_performance( $artist_id, $start_date = '', $end_date = '' ) {
		return $this->api_client->get_artist_performance( $artist_id, $start_date, $end_date );
	}

	/**
	 * Analyze artwork using the API.
	 *
	 * @since    1.0.0
	 * @param    int       $artwork_id    The ID of the artwork to analyze.
	 * @return   array|WP_Error           The API response or WP_Error on failure.
	 */
	public function analyze_artwork( $artwork_id ) {
		return $this->api_client->analyze_artwork( $artwork_id );
	}
} 