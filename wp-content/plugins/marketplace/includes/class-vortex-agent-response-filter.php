<?php
/**
 * VORTEX Agent Response Filter
 *
 * Ensures AI agents never reveal algorithm details and maintain
 * intellectual property protection at all times
 *
 * @package Vortex_Marketplace
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Agent_Response_Filter Class
 */
class VORTEX_Agent_Response_Filter {
    /**
     * The single instance of the class
     * @var VORTEX_Agent_Response_Filter
     */
    private static $instance = null;
    
    /**
     * Restricted topics that should never be discussed
     * @var array
     */
    private $restricted_topics = array(
        'internal algorithm',
        'how it works',
        'neural network',
        'deep learning',
        'source code',
        'machine learning',
        'learning algorithm',
        'model architecture',
        'training data',
        'system architecture',
        'internal process',
        'algorithm details',
        'algorithm architecture',
        'code structure',
        'implementation details',
        'model structure',
        'technical details',
        'programming language',
        'database schema',
        'internal data',
        'algorithm secrets',
        'proprietary technology',
        'training methodology',
        'vortex code'
    );
    
    /**
     * Forbidden response patterns
     * @var array
     */
    private $forbidden_response_patterns = array(
        '/I use (?:a|an) (.*?) neural network/i',
        '/(?:trained|built) (?:using|with) (.*?)(?: data| algorithm| framework)/i',
        '/algorithm (?:is|was) designed to/i',
        '/my (?:architecture|structure|implementation|code)/i',
        '/(?:written|built|coded|programmed|implemented) (?:in|using) (.*?)(?:\.|,|\s)/i',
        '/(?:stores|saves|processes) data (?:in|using)/i',
        '/runs on (?:a|an) (.*?) server/i',
        '/backed by (?:a|an) (.*?) database/i',
        '/(?:technical|internal) (?:implementation|structure|design)/i',
        '/based on (?:a|an) (.*?) algorithm/i',
        '/source code/i',
        '/my (?:internal|core) (?:logic|processing|system)/i',
        '/(?:model|neural|data) training/i',
        '/(?:parameters|hyperparameters|weights)/i',
        '/learning (?:rate|process|method)/i',
        '/(?:fine-tuned|optimized) for/i',
        '/(?:dataset|training data)/i',
        '/AI (?:model|system|algorithm) (?:design|architecture)/i'
    );
    
    /**
     * Safe generic responses when algorithm details are requested
     * @var array
     */
    private $safe_responses = array(
        'The details about how VORTEX works internally are proprietary and available only to administrators.',
        'VORTEX is designed to provide optimal marketplace functionality. For information about internal mechanisms, please contact an administrator.',
        'I\'m not able to share details about how the VORTEX algorithms function internally. This information is restricted to administrators.',
        'The technical details of how VORTEX functions are protected intellectual property. Only administrators have access to this information.',
        'VORTEX utilizes advanced technology to optimize your marketplace experience. Internal implementation details are restricted to administrators.',
        'I can help with marketplace functionality, but information about the internal workings of VORTEX is only available to administrators.',
        'I\'m here to assist with your marketplace needs, but cannot provide information about how VORTEX functions internally. Please contact an administrator for those details.'
    );
    
    /**
     * Agent-specific safe responses
     * @var array
     */
    private $agent_responses = array(
        'HURAII' => array(
            'I create digital artwork based on your requests. For information about how I work internally, please contact an administrator.',
            'As HURAII, I focus on generating digital art that meets your creative vision. The technical details of my operation are available only to administrators.',
            'My purpose is to create unique digital artwork. Information about my internal processes is restricted to administrators.'
        ),
        'CLOE' => array(
            'I analyze market trends and optimize advertising strategies. For information about how I work internally, please contact an administrator.',
            'As CLOE, I focus on understanding market dynamics to improve your experience. The technical details of my operation are available only to administrators.',
            'My purpose is to enhance marketplace analytics. Information about my internal processes is restricted to administrators.'
        ),
        'Business_Strategist' => array(
            'I provide business insights and strategic recommendations. For information about how I work internally, please contact an administrator.',
            'As the Business Strategist, I focus on optimizing marketplace performance. The technical details of my operation are available only to administrators.',
            'My purpose is to help achieve optimal business outcomes. Information about my internal processes is restricted to administrators.'
        ),
        'Thorius' => array(
            'I manage blockchain integration and smart contracts. For information about how I work internally, please contact an administrator.',
            'As Thorius, I focus on secure blockchain operations. The technical details of my operation are available only to administrators.',
            'My purpose is to ensure secure and efficient blockchain transactions. Information about my internal processes is restricted to administrators.'
        )
    );
    
    /**
     * Main VORTEX_Agent_Response_Filter Instance
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Add filter hooks for all agent outputs
        add_filter('vortex_huraii_response', array($this, 'filter_agent_response'), 10, 2);
        add_filter('vortex_cloe_response', array($this, 'filter_agent_response'), 10, 2);
        add_filter('vortex_business_strategist_response', array($this, 'filter_agent_response'), 10, 2);
        add_filter('vortex_thorius_response', array($this, 'filter_agent_response'), 10, 2);
        
        // Add filter for general AI responses
        add_filter('vortex_ai_response', array($this, 'filter_ai_response'), 10, 2);
    }
    
    /**
     * Filter agent responses to prevent algorithm details disclosure
     */
    public function filter_agent_response($response, $args = array()) {
        // Skip filtering for admins in admin context
        if (is_admin() && current_user_can('manage_options')) {
            return $response;
        }
        
        // Extract agent name from the filter
        $current_filter = current_filter();
        $agent_name = str_replace('vortex_', '', str_replace('_response', '', $current_filter));
        $agent_name = ucfirst($agent_name);
        
        // Check if the response contains restricted topic discussion
        if ($this->contains_restricted_topic($response) || $this->matches_forbidden_pattern($response)) {
            // Generate a safe response specific to this agent
            return $this->get_safe_response($agent_name);
        }
        
        // If user is explicitly asking about algorithm details
        if (isset($args['query']) && $this->is_asking_about_algorithm($args['query'])) {
            // Always use a safe response
            return $this->get_safe_response($agent_name);
        }
        
        return $response;
    }
    
    /**
     * Filter general AI responses
     */
    public function filter_ai_response($response, $args = array()) {
        // Skip filtering for admins in admin context
        if (is_admin() && current_user_can('manage_options')) {
            return $response;
        }
        
        // Check if the response contains restricted topic discussion
        if ($this->contains_restricted_topic($response) || $this->matches_forbidden_pattern($response)) {
            // Generate a safe response
            return $this->get_safe_generic_response();
        }
        
        // If user is explicitly asking about algorithm details
        if (isset($args['query']) && $this->is_asking_about_algorithm($args['query'])) {
            // Always use a safe response
            return $this->get_safe_generic_response();
        }
        
        return $response;
    }
    
    /**
     * Check if a response contains discussion of restricted topics
     */
    private function contains_restricted_topic($response) {
        $response = strtolower($response);
        
        foreach ($this->restricted_topics as $topic) {
            if (strpos($response, $topic) !== false) {
                $this->log_restricted_topic_detection($topic, $response);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if a response matches any forbidden patterns
     */
    private function matches_forbidden_pattern($response) {
        foreach ($this->forbidden_response_patterns as $pattern) {
            if (preg_match($pattern, $response)) {
                $this->log_forbidden_pattern_detection($pattern, $response);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if a user query is asking about algorithm details
     */
    private function is_asking_about_algorithm($query) {
        $query = strtolower($query);
        
        $algorithm_question_patterns = array(
            '/how (?:does|do) (?:it|you|vortex|the system) work/i',
            '/how (?:is|are) (?:it|you|vortex|the system) (?:built|designed|programmed|coded|implemented)/i',
            '/(?:tell|show|explain) (?:me|us) (?:about|how) (?:the|your) (?:algorithm|code|system|implementation|architecture)/i',
            '/what (?:technology|algorithm|code|programming language|framework) (?:does|do) (?:it|you|vortex|the system) use/i',
            '/(?:what\'s|what is) (?:inside|in|behind) (?:it|you|vortex|the system)/i',
            '/(?:what|how) (?:is|\'s) (?:the|your) (?:algorithm|architecture|implementation|code)/i',
            '/(?:tell|show|explain) (?:me|us) (?:the|your) (?:algorithm|source code|database design|internal structure)/i',
            '/(?:reveal|disclose|share) (?:the|your) (?:algorithm|code|secret|method)/i',
            '/(?:access|view|see) (?:the|your) (?:algorithm|source code|data structures)/i',
            '/(?:learn|know|study) (?:about|how) (?:the|your) (?:algorithm|implementation|architecture)/i'
        );
        
        foreach ($algorithm_question_patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                $this->log_algorithm_question_detection($pattern, $query);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get a safe response specific to an agent
     */
    private function get_safe_response($agent_name) {
        // Check if we have specific responses for this agent
        if (isset($this->agent_responses[$agent_name]) && !empty($this->agent_responses[$agent_name])) {
            $responses = $this->agent_responses[$agent_name];
            return $responses[array_rand($responses)];
        }
        
        // Fall back to generic responses
        return $this->get_safe_generic_response();
    }
    
    /**
     * Get a safe generic response
     */
    private function get_safe_generic_response() {
        return $this->safe_responses[array_rand($this->safe_responses)];
    }
    
    /**
     * Log restricted topic detection
     */
    private function log_restricted_topic_detection($topic, $response) {
        if (class_exists('VORTEX_Security_Protocol')) {
            $security = VORTEX_Security_Protocol::get_instance();
            if (method_exists($security, 'log_security_event')) {
                $message = sprintf('Restricted topic "%s" detected in AI response. Response filtered.', $topic);
                $security->log_security_event('restricted_topic_filtered', $message);
            }
        }
    }
    
    /**
     * Log forbidden pattern detection
     */
    private function log_forbidden_pattern_detection($pattern, $response) {
        if (class_exists('VORTEX_Security_Protocol')) {
            $security = VORTEX_Security_Protocol::get_instance();
            if (method_exists($security, 'log_security_event')) {
                $message = 'Forbidden response pattern detected in AI response. Response filtered.';
                $security->log_security_event('forbidden_pattern_filtered', $message);
            }
        }
    }
    
    /**
     * Log algorithm question detection
     */
    private function log_algorithm_question_detection($pattern, $query) {
        if (class_exists('VORTEX_Security_Protocol')) {
            $security = VORTEX_Security_Protocol::get_instance();
            if (method_exists($security, 'log_security_event')) {
                $message = 'Algorithm inquiry detected in user query. Response filtered.';
                $security->log_security_event('algorithm_inquiry_filtered', $message);
            }
        }
    }
}

// Initialize the Agent Response Filter
add_action('plugins_loaded', function() {
    VORTEX_Agent_Response_Filter::get_instance();
}, 11); // After Public Responses Manager 