<?php
/**
 * Thorius CLOE Agent Class
 * 
 * Adapter for CLOE AI agent - Creative Learning Optimized Entity
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/agents
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius CLOE Agent Class
 */
class Vortex_Thorius_CLOE {
    
    /**
     * API Manager instance
     *
     * @var Vortex_Thorius_API_Manager
     */
    private $api_manager;
    
    /**
     * Constructor
     *
     * @param Vortex_Thorius_API_Manager $api_manager API Manager instance
     */
    public function __construct($api_manager) {
        $this->api_manager = $api_manager;
    }
    
    /**
     * Process query with enhanced deep learning
     *
     * @param string $query User query
     * @param array $context Conversation context
     * @return array Response data
     */
    public function process_query($query, $context = array()) {
        try {
            // Prepare query with context
            $prepared_query = $this->prepare_query_with_context($query, $context);
            
            // Use API to get completion
            $system_prompt = $this->get_system_prompt();
            $response = $this->api_manager->generate_completion($prepared_query, $system_prompt, $context);
            
            // Format and return response
            return array(
                'success' => true,
                'response' => $response,
                'type' => 'text'
            );
        } catch (Exception $e) {
            return $this->get_error_response($e->getMessage());
        }
    }
    
    /**
     * Prepare query with context
     *
     * @param string $query User query
     * @param array $context Conversation context
     * @return string Prepared query
     */
    private function prepare_query_with_context($query, $context) {
        $prepared_query = $query;
        
        // Add conversation history if available
        if (!empty($context['conversation_history'])) {
            $prepared_query = "Previous conversation:\n" . $context['conversation_history'] . "\n\nCurrent query: " . $query;
        }
        
        // Add artwork context if available
        if (!empty($context['artwork_context'])) {
            $prepared_query .= "\n\nRelevant artwork context: " . $context['artwork_context'];
        }
        
        return $prepared_query;
    }
    
    /**
     * Get system prompt for CLOE
     *
     * @return string System prompt
     */
    private function get_system_prompt() {
        return "You are CLOE (Creative Learning Optimized Entity), an AI assistant specializing in art, creativity, and digital content creation. You are friendly, helpful, and knowledgeable about art history, techniques, styles, and digital art tools. Your responses should be informative but concise, and you should always aim to inspire creativity.";
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
            'response' => 'I\'m sorry, but I encountered an error processing your request.',
            'error' => $message
        );
    }
} 