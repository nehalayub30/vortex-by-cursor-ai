<?php
// Include integrations
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-integrations.php';

// Initialize classes
function vortex_initialize() {
    // Initialize plugin
    $vortex = VORTEX_Marketplace::get_instance();
    
    // Initialize commercial integrations
    if (file_exists(VORTEX_PLUGIN_DIR . 'includes/class-vortex-integrations.php')) {
        require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-integrations.php';
        $vortex_integrations = VORTEX_Integrations::get_instance();
    }
}
add_action('plugins_loaded', 'vortex_initialize');

// ... rest of existing code ... 

// Shortcode functions
function vortex_shortcode_functions() {
    $vortex = VORTEX_Marketplace::get_instance();
    
    // Register shortcodes
    add_shortcode('vortex_marketplace', array($vortex, 'marketplace_shortcode'));
    add_shortcode('vortex_artist_profile', array($vortex, 'artist_profile_shortcode'));
    add_shortcode('vortex_artwork_gallery', array($vortex, 'artwork_gallery_shortcode'));
    add_shortcode('vortex_dao_dashboard', array($vortex, 'dao_dashboard_shortcode'));
    add_shortcode('vortex_wallet_connect', array($vortex, 'wallet_connect_shortcode'));
    add_shortcode('vortex_token_stats', array($vortex, 'token_stats_shortcode'));
    add_shortcode('vortex_blockchain_metrics', array($vortex, 'blockchain_metrics_shortcode'));
    add_shortcode('vortex_agent_insights', array($vortex, 'agent_insights_shortcode'));
}
add_action('init', 'vortex_shortcode_functions');

/**
 * Register shortcodes
 */
function vortex_register_shortcodes() {
    // Main marketplace shortcode
    add_shortcode('vortex_marketplace', 'vortex_marketplace_shortcode');
    
    // Artist profile shortcode
    add_shortcode('vortex_artist_profile', 'vortex_artist_profile_shortcode');
    
    // Artwork gallery shortcode
    add_shortcode('vortex_artwork_gallery', 'vortex_artwork_gallery_shortcode');
    
    // DAO dashboard shortcode
    add_shortcode('vortex_dao_dashboard', 'vortex_dao_dashboard_shortcode');
    
    // Wallet connect shortcode
    add_shortcode('vortex_wallet_connect', 'vortex_wallet_connect_shortcode');
    
    // Token stats shortcode
    add_shortcode('vortex_token_stats', 'vortex_token_stats_shortcode');
    
    // Blockchain metrics shortcode
    add_shortcode('vortex_blockchain_metrics', 'vortex_blockchain_metrics_shortcode');
    
    // AI agent insights shortcode
    add_shortcode('vortex_agent_insights', 'vortex_agent_insights_shortcode');
}
add_action('init', 'vortex_register_shortcodes');

/**
 * Blockchain metrics shortcode handler
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function vortex_blockchain_metrics_shortcode($atts) {
    $atts = shortcode_atts(array(
        'metric_type' => 'all',  // Options: all, token_activity, artist_performance, artwork_trends, swaps
        'days' => 30,            // Timeframe in days: 7, 30, 90 
        'show_chart' => 'true'   // Show charts: true, false
    ), $atts);
    
    // Enqueue required assets
    wp_enqueue_style('vortex-blockchain-metrics-style');
    wp_enqueue_script('chart-js');
    wp_enqueue_script('vortex-blockchain-metrics-script');
    
    // Get blockchain metrics instance and render
    $blockchain_metrics = new VORTEX_Blockchain_Metrics();
    
    // Start output buffer
    ob_start();
    
    // Include the metrics template with data
    $metrics = $blockchain_metrics->get_metrics($atts['metric_type'], intval($atts['days']));
    include VORTEX_PLUGIN_DIR . 'public/partials/vortex-blockchain-metrics.php';
    
    // Return the buffered output
    return ob_get_clean();
}

/**
 * AI agent insights shortcode handler
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function vortex_agent_insights_shortcode($atts) {
    $atts = shortcode_atts(array(
        'agent' => 'all',            // Options: all, huraii, cloe, business_strategist, thorius
        'insight_type' => 'latest',  // Options: latest, trending, recommendations, blockchain, alerts
        'limit' => 5                 // Number of insights to display initially
    ), $atts);
    
    // Enqueue required assets
    wp_enqueue_style('vortex-agent-insights-style');
    wp_enqueue_script('vortex-agent-insights-script');
    
    // Get orchestrator instance
    $vortex_orchestrator = VORTEX_Orchestrator::get_instance();
    
    // Get insights
    $insights = $vortex_orchestrator->get_agent_insights_for_display(
        $atts['agent'],
        $atts['insight_type'],
        intval($atts['limit'])
    );
    
    // Start output buffer
    ob_start();
    
    // Include the template
    include VORTEX_PLUGIN_DIR . 'public/partials/vortex-agent-insights.php';
    
    // Return the buffered output
    return ob_get_clean();
}

// ... rest of existing code ... 