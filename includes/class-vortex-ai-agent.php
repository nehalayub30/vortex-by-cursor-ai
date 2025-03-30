/**
 * Get user profile context for AI personalization
 * 
 * @param int $user_id User ID
 * @return string Context string for AI prompt
 */
protected function get_user_context($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return '';
    }
    
    $user_data = get_userdata($user_id);
    $user_role = get_user_meta($user_id, 'vortex_user_role', true);
    $user_categories = get_user_meta($user_id, 'vortex_user_categories', true);
    
    $context = "User is a " . ($user_role === 'artist' ? 'creator/artist' : 'collector/buyer');
    
    if (!empty($user_categories) && is_array($user_categories)) {
        $context .= " with interests in: " . implode(', ', $user_categories);
    }
    
    return $context;
}

/**
 * Enhance prompt with user context
 * 
 * @param string $prompt The original prompt
 * @param int $user_id User ID
 * @return string Enhanced prompt
 */
public function enhance_prompt_with_context($prompt, $user_id = null) {
    $user_context = $this->get_user_context($user_id);
    
    if (empty($user_context)) {
        return $prompt;
    }
    
    return $prompt . "\n\nUser Context: " . $user_context;
}

/**
 * Add business context to user data
 * 
 * @param int $user_id User ID
 * @param string $context_type Type of context
 * @param array $context_data Context data
 * @return bool Success
 */
public function add_user_context($user_id, $context_type, $context_data) {
    // Get existing user context
    $user_context = get_user_meta($user_id, 'vortex_ai_context', true);
    
    if (!is_array($user_context)) {
        $user_context = array();
    }
    
    // Add or update this context type
    $user_context[$context_type] = $context_data;
    
    // Add timestamp
    $user_context['updated_at'] = current_time('timestamp');
    
    // Save updated context
    update_user_meta($user_id, 'vortex_ai_context', $user_context);
    
    // Log context update for learning
    $this->log_context_update($user_id, $context_type, $context_data);
    
    return true;
}

/**
 * Log context updates for AI learning
 */
private function log_context_update($user_id, $context_type, $context_data) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'vortex_ai_learning_log';
    
    $wpdb->insert(
        $table,
        array(
            'user_id' => $user_id,
            'agent_type' => get_class($this),
            'context_type' => $context_type,
            'context_data' => maybe_serialize($context_data),
            'timestamp' => current_time('timestamp')
        ),
        array('%d', '%s', '%s', '%s', '%d')
    );
    
    return true;
} 