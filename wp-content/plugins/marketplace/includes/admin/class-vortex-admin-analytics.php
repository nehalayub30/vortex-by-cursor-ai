/**
 * Analyze ROI and provide actionable insights
 */
public function analyze_roi() {
    global $wpdb;
    
    // Calculate total investment (infrastructure, token costs, etc.)
    $total_investment = floatval(get_option('vortex_total_investment', 0));
    
    // Calculate total revenue from marketplace transactions
    $total_revenue = $wpdb->get_var("
        SELECT SUM(amount) FROM {$wpdb->prefix}vortex_transactions 
        WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    
    // Calculate ROI
    $roi = $total_investment > 0 ? (($total_revenue - $total_investment) / $total_investment) * 100 : 0;
    
    // Store current ROI
    update_option('vortex_current_roi', $roi);
    
    // Get AI insights for ROI improvement
    $insights = $this->get_ai_insights_for_roi();
    
    // Log ROI and insights
    $this->log_roi_analysis($roi, $insights);
    
    // Alert admin if ROI falls below target
    if ($roi < 80) {
        $this->send_roi_alert_to_admin($roi, $insights);
    }
    
    return array(
        'roi' => $roi,
        'insights' => $insights
    );
}

/**
 * Get AI insights for improving ROI
 */
private function get_ai_insights_for_roi() {
    $insights = array();
    
    // Get CLOE's market insights
    if (class_exists('VORTEX_CLOE')) {
        $cloe = VORTEX_CLOE::get_instance();
        
        $insights['market_gaps'] = $cloe->identify_content_gaps();
        $insights['trending_styles'] = $cloe->get_popular_styles();
        $insights['price_sensitivity'] = $cloe->get_price_sensitivity_data();
    }
    
    // Get Business Strategist's recommendations
    if (class_exists('VORTEX_Business_Strategist')) {
        $strategist = VORTEX_Business_Strategist::get_instance();
        
        $insights['pricing_recommendations'] = $strategist->get_pricing_recommendations();
        $insights['value_ladder'] = $strategist->generate_value_ladder();
        $insights['market_sizing'] = $strategist->get_market_sizing();
    }
    
    return $insights;
}

/**
 * Get public market insights (safe for all users)
 * Removes sensitive financial data
 */
public function get_public_market_insights($period = 'month') {
    $insights = array();
    
    // Get CLOE's market insights (public-safe version)
    if (class_exists('VORTEX_CLOE')) {
        $cloe = VORTEX_CLOE::get_instance();
        
        $insights['trending_styles'] = $cloe->get_popular_styles($period);
        $insights['emerging_themes'] = $cloe->get_emerging_themes($period);
        $insights['popular_keywords'] = $cloe->get_top_performing_keywords($period);
        
        // Do NOT include price sensitivity or content gaps (strategic info)
    }
    
    // Get Business Strategist's recommendations (public-safe version)
    if (class_exists('VORTEX_Business_Strategist')) {
        $strategist = VORTEX_Business_Strategist::get_instance();
        
        $insights['market_trends'] = $strategist->get_public_market_trends($period);
        // Do NOT include pricing recommendations, value ladder, or market sizing
    }
    
    return $insights;
}

/**
 * Check if current request is an admin-only page
 */
private function is_admin_context() {
    return (is_admin() && current_user_can('manage_options')) || (defined('REST_REQUEST') && REST_REQUEST && current_user_can('manage_options'));
} 