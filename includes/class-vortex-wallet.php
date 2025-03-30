/**
 * Check if user has access to LLM API features
 *
 * @param int $user_id User ID
 * @return bool Whether user has access
 */
public function check_llm_api_access($user_id) {
    // Check if user has TOLA tokens
    $has_tokens = $this->get_tola_balance($user_id) > 0;
    
    // Allow other components to modify access (like user agreement check)
    return apply_filters('vortex_check_llm_api_access', $has_tokens, $user_id);
} 