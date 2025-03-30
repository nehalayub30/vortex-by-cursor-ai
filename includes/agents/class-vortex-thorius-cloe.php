/**
 * Process query with enhanced deep learning
 */
protected function process_with_deep_learning($query, $context) {
    try {
        // Prepare query with context
        $prepared_query = $this->prepare_query_with_context($query, $context);
        
        // Use API to get completion
        $response = $this->api->get_completion($prepared_query, [
            'model' => 'cloe-advanced',
            'max_tokens' => 1500,
            'temperature' => 0.7,
            'system_prompt' => $this->get_system_prompt()
        ]);
        
        // Format and return response
        return $this->format_response($response);
    } catch (Exception $e) {
        error_log('CLOE Deep Learning Error: ' . $e->getMessage());
        return $this->get_error_response($e);
    }
} 