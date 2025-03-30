<?php
/**
 * Artwork Analytics API endpoints for the VORTEX AI AGENTS plugin
 *
 * @package VortexAIAgents
 */

namespace VortexAIAgents\API;

use VortexAIAgents\Services\Art_Market_Analytics;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class Artwork_Analytics_API
 */
class Artwork_Analytics_API {

    /**
     * Art market analytics service
     *
     * @var Art_Market_Analytics
     */
    private $analytics;

    /**
     * Constructor
     *
     * @param Art_Market_Analytics $analytics Art market analytics service.
     */
    public function __construct(Art_Market_Analytics $analytics) {
        $this->analytics = $analytics;
    }

    /**
     * Register routes
     */
    public function register_routes() {
        register_rest_route(
            'vortex-ai/v1',
            '/artwork-analytics/(?P<id>[\d]+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'           => array($this, 'get_artwork_analytics'),
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

        register_rest_route(
            'vortex-ai/v1',
            '/artwork-analytics/batch',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'           => array($this, 'get_batch_analytics'),
                'permission_callback' => array($this, 'check_permission'),
                'args'               => array(
                    'artwork_ids' => array(
                        'required' => true,
                        'type'     => 'array',
                        'items'    => array(
                            'type' => 'integer',
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            'vortex-ai/v1',
            '/artwork-analytics/category/(?P<category>[\w-]+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'           => array($this, 'get_category_analytics'),
                'permission_callback' => array($this, 'check_permission'),
                'args'               => array(
                    'category' => array(
                        'required' => true,
                        'type'     => 'string',
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
     * Get artwork analytics
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_artwork_analytics(WP_REST_Request $request) {
        try {
            $artwork_id = $request->get_param('id');
            $analytics = $this->analytics->analyze_artwork_potential($artwork_id);

            return new WP_REST_Response($analytics, 200);
        } catch (\Exception $e) {
            return new WP_Error(
                'vortex_analytics_error',
                __('Error analyzing artwork', 'vortex-ai-agents'),
                array('status' => 500)
            );
        }
    }

    /**
     * Get batch analytics
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_batch_analytics(WP_REST_Request $request) {
        try {
            $artwork_ids = $request->get_param('artwork_ids');
            $results = array();

            foreach ($artwork_ids as $artwork_id) {
                $results[$artwork_id] = $this->analytics->analyze_artwork_potential($artwork_id);
            }

            return new WP_REST_Response($results, 200);
        } catch (\Exception $e) {
            return new WP_Error(
                'vortex_batch_analytics_error',
                __('Error analyzing artworks batch', 'vortex-ai-agents'),
                array('status' => 500)
            );
        }
    }

    /**
     * Get category analytics
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_category_analytics(WP_REST_Request $request) {
        try {
            $category = $request->get_param('category');
            $category_data = $this->analytics->get_market_context($category);

            return new WP_REST_Response($category_data, 200);
        } catch (\Exception $e) {
            return new WP_Error(
                'vortex_category_analytics_error',
                __('Error analyzing category', 'vortex-ai-agents'),
                array('status' => 500)
            );
        }
    }
} 