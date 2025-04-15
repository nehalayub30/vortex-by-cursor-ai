<?php
/**
 * Thorius HURAII Agent Class
 * 
 * Adapter for HURAII AI agent
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/agents
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius HURAII Agent Class
 */
class Vortex_Thorius_HURAII {
    
    /**
     * API Manager instance
     *
     * @var Vortex_Thorius_API_Manager
     */
    private $api_manager;
    
    /**
     * HURAII instance
     * 
     * @var VortexAiAgents\Agents\HURAII
     */
    private $huraii_instance;
    
    /**
     * Constructor
     *
     * @param Vortex_Thorius_API_Manager $api_manager API Manager instance
     */
    public function __construct($api_manager) {
        $this->api_manager = $api_manager;
        
        // Initialize HURAII instance if class exists
        if (class_exists('VortexAiAgents\Agents\HURAII')) {
            $this->huraii_instance = new \VortexAiAgents\Agents\HURAII();
        }
    }
    
    /**
     * Process query
     *
     * @param string $query User query
     * @param array $context Conversation context
     * @return array Response data
     */
    public function process_query($query, $context = array()) {
        // Check if HURAII instance exists
        if (!$this->huraii_instance) {
            return $this->get_error_response('HURAII instance not available');
        }
        
        try {
            // Determine if this is an artwork generation request
            if ($this->is_artwork_request($query)) {
                return $this->process_artwork_request($query, $context);
            }
            
            // Otherwise, handle as a normal query
            return $this->process_normal_query($query, $context);
        } catch (Exception $e) {
            return $this->get_error_response($e->getMessage());
        }
    }
    
    /**
     * Check if query is an artwork generation request
     *
     * @param string $query User query
     * @return bool True if artwork request
     */
    private function is_artwork_request($query) {
        $artwork_keywords = array(
            'generate', 'create', 'make', 'draw', 'paint', 'design', 'produce',
            'artwork', 'image', 'picture', 'illustration', 'painting', 'drawing'
        );
        
        $query_lower = strtolower($query);
        
        foreach ($artwork_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Process artwork generation request
     *
     * @param string $query User query
     * @param array $context Conversation context
     * @return array Response data
     */
    private function process_artwork_request($query, $context) {
        // Extract prompt from query
        $prompt = $this->extract_artwork_prompt($query);
        
        // Extract style from context or query
        $style = $this->extract_artwork_style($query, $context);
        
        // Use HURAII to generate artwork
        $artwork_data = array(
            'prompt' => $prompt,
            'style' => $style,
            'resolution' => '1024x1024',
            'variations' => 1
        );
        
        // Create request object for HURAII
        $request = new \WP_REST_Request('POST', '/vortex-ai/v1/huraii/generate');
        $request->set_param('prompt', $prompt);
        $request->set_param('style', $style);
        $request->set_param('resolution', '1024x1024');
        $request->set_param('variations', 1);
        
        // Generate artwork
        $result = $this->huraii_instance->generate_artwork($request);
        
        // Check for errors
        if (is_wp_error($result)) {
            return $this->get_error_response($result->get_error_message());
        }
        
        return array(
            'success' => true,
            'response' => 'I\'ve created an artwork based on your request.',
            'data' => $result->get_data(),
            'type' => 'artwork'
        );
    }
    
    /**
     * Process normal text query
     *
     * @param string $query User query
     * @param array $context Conversation context
     * @return array Response data
     */
    private function process_normal_query($query, $context) {
        // Use API manager to get completion
        $system_prompt = "You are HURAII (Human-Understanding Rendering Artificial Intelligence Integration), an AI assistant specializing in visual art creation and artistic advice. You can describe art you would create in detail, discuss artistic styles, and provide advice on visual composition.";
        
        $response = $this->api_manager->generate_completion($query, $system_prompt, $context);
        
        return array(
            'success' => true,
            'response' => $response,
            'type' => 'text'
        );
    }
    
    /**
     * Extract artwork prompt from query
     *
     * @param string $query User query
     * @return string Artwork prompt
     */
    private function extract_artwork_prompt($query) {
        // Remove common prefixes
        $prefixes = array(
            'create an image of', 'generate an image of', 'make an image of',
            'draw', 'paint', 'create', 'generate', 'make', 'illustrate',
            'can you create', 'could you generate', 'please make'
        );
        
        $prompt = $query;
        
        foreach ($prefixes as $prefix) {
            if (stripos($query, $prefix) === 0) {
                $prompt = trim(substr($query, strlen($prefix)));
                break;
            }
        }
        
        return $prompt;
    }
    
    /**
     * Extract artwork style from context or query
     *
     * @param string $query User query
     * @param array $context Conversation context
     * @return string Artwork style
     */
    private function extract_artwork_style($query, $context) {
        // Check context for style
        if (isset($context['style'])) {
            return $context['style'];
        }
        
        // Check query for common style keywords
        $query_lower = strtolower($query);
        
        $style_keywords = array(
            'realistic' => 'realistic',
            'real' => 'realistic',
            'photo' => 'realistic',
            'photograph' => 'realistic',
            'abstract' => 'abstract',
            'cartoon' => 'cartoon',
            'anime' => 'anime',
            'watercolor' => 'watercolor',
            'oil painting' => 'oil-painting',
            'sketch' => 'sketch',
            'digital' => 'digital-art',
            'pixel' => 'pixel-art'
        );
        
        foreach ($style_keywords as $keyword => $style) {
            if (strpos($query_lower, $keyword) !== false) {
                return $style;
            }
        }
        
        // Default style
        return 'realistic';
    }
    
    /**
     * Get error response
     *
     * @param string $message Error message
     * @return array Error response data
     */
    private function get_error_response($message) {
        return array(
            'success' => false,
            'response' => 'I\'m sorry, but I couldn\'t generate the artwork at this time.',
            'error' => $message
        );
    }
} 