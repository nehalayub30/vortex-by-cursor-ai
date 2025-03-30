<?php
/**
 * Thorius Orchestrator Class
 * 
 * Enhanced coordination between AI agents
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Orchestrator Class
 */
class Vortex_Thorius_Orchestrator {
    
    /**
     * Agent registry
     */
    private $agents = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register available agents
        $this->register_default_agents();
        
        // Allow plugins to register additional agents
        do_action('vortex_thorius_register_agents', $this);
    }
    
    /**
     * Register an AI agent
     *
     * @param string $id Agent ID
     * @param array $properties Agent properties
     */
    public function register_agent($id, $properties) {
        $this->agents[$id] = wp_parse_args($properties, array(
            'name' => '',
            'description' => '',
            'capabilities' => array(),
            'priority' => 10,
            'callback' => null,
        ));
    }
    
    /**
     * Register default agents
     */
    private function register_default_agents() {
        $this->register_agent('huraii', array(
            'name' => 'HURAII',
            'description' => 'Advanced AI image generation and transformation',
            'capabilities' => array('image_generation', 'image_transformation', 'style_transfer', 'nft_creation'),
            'priority' => 10,
            'callback' => array($this, 'process_huraii_request'),
        ));
        
        $this->register_agent('cloe', array(
            'name' => 'CLOE',
            'description' => 'Art discovery and curation assistant',
            'capabilities' => array('art_discovery', 'style_analysis', 'art_recommendations', 'artist_discovery'),
            'priority' => 20,
            'callback' => array($this, 'process_cloe_request'),
        ));
        
        $this->register_agent('strategist', array(
            'name' => 'Business Strategist',
            'description' => 'Market insights and trend analysis',
            'capabilities' => array('market_analysis', 'trend_prediction', 'price_optimization', 'audience_analysis'),
            'priority' => 30,
            'callback' => array($this, 'process_strategist_request'),
        ));
    }
    
    /**
     * Route request to appropriate agent
     *
     * @param array $request Request data
     * @return array Response data
     */
    public function route_request($request) {
        if (empty($request['agent']) || empty($request['action'])) {
            return array(
                'success' => false,
                'message' => 'Invalid request: Missing agent or action'
            );
        }
        
        $agent_id = sanitize_text_field($request['agent']);
        
        if (!isset($this->agents[$agent_id])) {
            return array(
                'success' => false,
                'message' => 'Unknown agent: ' . $agent_id
            );
        }
        
        $agent = $this->agents[$agent_id];
        
        if (!is_callable($agent['callback'])) {
            return array(
                'success' => false,
                'message' => 'Agent callback not callable'
            );
        }
        
        // Track agent usage
        $this->track_agent_usage($agent_id, $request['action']);
        
        // Execute agent callback
        return call_user_func($agent['callback'], $request);
    }
    
    /**
     * Process parallel agent requests
     *
     * @param array $requests Array of agent requests
     * @return array Responses
     */
    public function process_parallel_requests($requests) {
        $responses = array();
        
        foreach ($requests as $key => $request) {
            $responses[$key] = $this->route_request($request);
        }
        
        return $responses;
    }
    
    /**
     * Track agent usage
     *
     * @param string $agent_id Agent ID
     * @param string $action Action
     */
    private function track_agent_usage($agent_id, $action) {
        // Get analytics class
        $analytics = new Vortex_Thorius_Analytics();
        
        // Track agent usage
        $analytics->track_analytics('agent_used', array(
            'agent' => $agent_id,
            'action' => $action
        ));
    }
} 