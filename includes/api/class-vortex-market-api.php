<?php
/**
 * Market API endpoints for the VORTEX AI AGENTS plugin
 *
 * @package VortexAIAgents
 */

namespace VortexAIAgents\API;

use VortexAIAgents\Services\MarketAnalysis;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class Market_API
 */
class Market_API {

    /**
     * Market analysis service instance
     *
     * @var MarketAnalysis
     */
    private $market_analysis;

    /**
     * Constructor
     *
     * @param MarketAnalysis $market_analysis Market analysis service.
     */
    public function __construct(MarketAnalysis $market_analysis) {
        $this->market_analysis = $market_analysis;
    }

    /**
     * Register routes
     */
    public function register_routes() {
        register_rest_route(
            'vortex-ai/v1',
            '/market-data',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'           => array($this, 'get_market_data'),
                'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vortex-ai/v1',
            '/market-trends',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'           => array($this, 'get_market_trends'),
                'permission_callback' => array($this, 'check_permission'),
            )
        );

        register_rest_route(
            'vortex-ai/v1',
            '/artist-insights/(?P<id>[\d]+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'           => array($this, 'get_artist_insights'),
                'permission_callback' => array($this, 'check_permission'),
                'args'               => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                ),
            )
        );
    }

    /**
     * Check if user has permission to access endpoints
     *
     * @return bool
     */
    public function check_permission() {
        return current_user_can('read');
    }

    /**
     * Get market data
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_market_data(WP_REST_Request $request) {
        try {
            $data = $this->market_analysis->get_market_overview();
            return new WP_REST_Response($data, 200);
        } catch (\Exception $e) {
            return new WP_Error(
                'vortex_market_error',
                __('Error fetching market data', 'vortex-ai-agents'),
                array('status' => 500)
            );
        }
    }

    /**
     * Get market trends
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_market_trends(WP_REST_Request $request) {
        try {
            $trends = $this->market_analysis->get_market_trends();
            return new WP_REST_Response($trends, 200);
        } catch (\Exception $e) {
            return new WP_Error(
                'vortex_trends_error',
                __('Error fetching market trends', 'vortex-ai-agents'),
                array('status' => 500)
            );
        }
    }

    /**
     * Get artist insights
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_artist_insights(WP_REST_Request $request) {
        try {
            $artist_id = $request->get_param('id');
            $insights = $this->market_analysis->get_artist_insights($artist_id);
            return new WP_REST_Response($insights, 200);
        } catch (\Exception $e) {
            return new WP_Error(
                'vortex_artist_error',
                __('Error fetching artist insights', 'vortex-ai-agents'),
                array('status' => 500)
            );
        }
    }
} 