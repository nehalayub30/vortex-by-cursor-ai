<?php
/**
 * VORTEX Marketplace Enhanced Metrics Dashboard
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Analytics
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the enhanced metrics dashboard with real-time updates
 *
 * @since 1.1.0
 * @return void
 */
function vortex_render_enhanced_metrics_dashboard() {
    // Verify user access
    if (!current_user_can('manage_options') && !current_user_can('edit_vortex_artworks')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'vortex-marketplace'));
    }
    
    // Get current user
    $current_user = wp_get_current_user();
    
    // Initialize AI agents for dashboard session with advanced learning context
    $session_id = do_action('vortex_ai_agent_init', 'enhanced_metrics_dashboard', 
        array('HURAII', 'CLOE', 'BusinessStrategist'), 
        'active', 
        array(
            'user_id' => $current_user->ID,
            'context' => 'advanced_analytics_viewing',
            'dashboard_session' => uniqid('enhanced_dash_'),
            'real_time' => true,
            'refresh_interval' => 60, // seconds
            'learning_priority' => 'high'
        )
    );
    
    // Load required classes
    $metrics = VORTEX_Metrics::get_instance();
    $analytics = VORTEX_Analytics::get_instance();
    $rankings = VORTEX_Rankings::get_instance();
    
    // Get summary data with AI enhancement
    $summary = $analytics->get_analytics_summary(true);
    $trending_artworks = $rankings->get_trending_artworks(5, 0, true);
    $top_artists = $rankings->get_top_artists(5, 0, '30days', true);
    $sales_leaderboard = $rankings->get_sales_leaderboard(5, 0, '30days', true);
    
    // Get period for filtering
    $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '30days';
    $periods = array(
        '7days' => __('Last 7 Days', 'vortex-marketplace'),
        '30days' => __('Last 30 Days', 'vortex-marketplace'),
        '90days' => __('Last 90 Days', 'vortex-marketplace'),
        'alltime' => __('All Time', 'vortex-marketplace')
    );
    
    // Get AI insights with enhanced context for deeper learning
    $huraii_insights = apply_filters('vortex_huraii_dashboard_insights', array(), array(
        'user_id' => $current_user->ID,
        'period' => $period,
        'session_id' => $session_id,
        'include_predictions' => true,
        'depth' => 'comprehensive'
    ));
    
    $cloe_insights = apply_filters('vortex_cloe_dashboard_insights', array(), array(
        'user_id' => $current_user->ID,
        'period' => $period,
        'session_id' => $session_id,
        'include_audience_segments' => true,
        'depth' => 'comprehensive'
    ));
    
    $business_insights = apply_filters('vortex_business_strategist_dashboard_insights', array(), array(
        'user_id' => $current_user->ID,
        'period' => $period,
        'session_id' => $session_id,
        'include_forecasts' => true,
        'depth' => 'comprehensive'
    ));
    
    // Enqueue enhanced scripts and styles
    wp_enqueue_style('vortex-enhanced-dashboard-css', VORTEX_PLUGIN_URL . 'assets/css/enhanced-dashboard.css', array(), VORTEX_VERSION);
    wp_enqueue_script('chart-js', VORTEX_PLUGIN_URL . 'assets/js/chart.min.js', array('jquery'), '3.7.0', true);
    wp_enqueue_script('vortex-real-time-js', VORTEX_PLUGIN_URL . 'assets/js/real-time-updates.js', array('jquery'), VORTEX_VERSION, true);
    wp_enqueue_script('vortex-enhanced-dashboard-js', VORTEX_PLUGIN_URL . 'assets/js/enhanced-dashboard.js', array('jquery', 'chart-js', 'vortex-real-time-js'), VORTEX_VERSION, true);
    
    // Localize script data with enhanced options
    wp_localize_script('vortex-enhanced-dashboard-js', 'vortexDashboardData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vortex_dashboard_nonce'),
        'period' => $period,
        'sessionId' => $session_id,
        'realTimeUpdates' => true,
        'updateInterval' => 60000, // 60 seconds
        'exportFormats' => array('csv', 'json', 'pdf'),
        'userPreferences' => vortex_get_user_dashboard_preferences($current_user->ID),
        'i18n' => vortex_get_dashboard_i18n_strings()
    ));
    
    // Track AI learning for this enhanced dashboard view with detailed context
    do_action('vortex_ai_interaction', 'enhanced_dashboard_view', array(
        'dashboard_type' => 'metrics',
        'period' => $period,
        'sections_displayed' => array('summary', 'trending', 'top_artists', 'sales', 'ai_insights', 'predictions'),
        'session_id' => $session_id,
        'view_timestamp' => current_time('timestamp'),
        'user_role' => implode(', ', $current_user->roles),
        'is_mobile' => wp_is_mobile()
    ), $current_user->ID);
    
    // Start dashboard output
    ?>
    <div class="wrap vortex-enhanced-dashboard" data-session-id="<?php echo esc_attr($session_id); ?>">
        <h1><?php _e('VORTEX Marketplace Advanced Analytics', 'vortex-marketplace'); ?></h1>
        
        <div class="vortex-dashboard-actions">
            <!-- Period Selection -->
            <div class="vortex-period-selection">
                <form method="get" id="vortex-period-form">
                    <input type="hidden" name="page" value="vortex-enhanced-analytics">
                    <label for="vortex-period"><?php _e('Time Period:', 'vortex-marketplace'); ?></label>
                    <select name="period" id="vortex-period" onchange="this.form.submit()">
                        <?php foreach ($periods as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($period, $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <!-- Export Options -->
            <div class="vortex-export-options">
                <button class="vortex-export-btn" data-format="csv"><?php _e('Export CSV', 'vortex-marketplace'); ?></button>
                <button class="vortex-export-btn" data-format="pdf"><?php _e('Export PDF', 'vortex-marketplace'); ?></button>
                <button class="vortex-export-btn" data-format="json"><?php _e('Export JSON', 'vortex-marketplace'); ?></button>
            </div>
            
            <!-- Real-time Toggle -->
            <div class="vortex-realtime-toggle">
                <label for="vortex-realtime-updates"><?php _e('Real-time Updates:', 'vortex-marketplace'); ?></label>
                <input type="checkbox" id="vortex-realtime-updates" checked>
            </div>
        </div>
        
        <!-- Summary Cards with Real-time Indicators -->
        <div class="vortex-summary-cards">
            <div class="vortex-card" data-metric="sales">
                <div class="vortex-card-icon sales-icon"></div>
                <div class="vortex-card-content">
                    <h3><?php _e('Total Sales', 'vortex-marketplace'); ?></h3>
                    <p class="vortex-card-value"><?php echo esc_html($summary['metrics']['total_sales']); ?></p>
                    <?php if (isset($summary['metrics']['sales_trend'])) : ?>
                        <div class="vortex-trend-indicator <?php echo $summary['metrics']['sales_trend'] > 0 ? 'positive' : 'negative'; ?>">
                            <?php echo esc_html($summary['metrics']['sales_trend']); ?>%
                        </div>
                    <?php endif; ?>
                </div>
                <div class="vortex-realtime-indicator"></div>
            </div>
            
            <div class="vortex-card" data-metric="revenue">
                <div class="vortex-card-icon revenue-icon"></div>
                <div class="vortex-card-content">
                    <h3><?php _e('Total Revenue', 'vortex-marketplace'); ?></h3>
                    <p class="vortex-card-value"><?php echo esc_html(vortex_format_price($summary['metrics']['total_revenue'])); ?></p>
                    <?php if (isset($summary['metrics']['revenue_trend'])) : ?>
                        <div class="vortex-trend-indicator <?php echo $summary['metrics']['revenue_trend'] > 0 ? 'positive' : 'negative'; ?>">
                            <?php echo esc_html($summary['metrics']['revenue_trend']); ?>%
                        </div>
                    <?php endif; ?>
                </div>
                <div class="vortex-realtime-indicator"></div>
            </div>
            
            <div class="vortex-card" data-metric="views">
                <div class="vortex-card-icon views-icon"></div>
                <div class="vortex-card-content">
                    <h3><?php _e('Total Views', 'vortex-marketplace'); ?></h3>
                    <p class="vortex-card-value"><?php echo esc_html($summary['metrics']['total_views']); ?></p>
                    <?php if (isset($summary['metrics']['views_trend'])) : ?>
                        <div class="vortex-trend-indicator <?php echo $summary['metrics']['views_trend'] > 0 ? 'positive' : 'negative'; ?>">
                            <?php echo esc_html($summary['metrics']['views_trend']); ?>%
                        </div>
                    <?php endif; ?>
                </div>
                <div class="vortex-realtime-indicator"></div>
            </div>
        </div>
        
        <!-- Trending Artworks -->
        <div class="vortex-trending-artworks">
            <h2><?php _e('Trending Artworks', 'vortex-marketplace'); ?></h2>
            <div class="vortex-artworks-container">
                <?php foreach ($trending_artworks as $artwork) : ?>
                    <div class="vortex-artwork">
                        <img src="<?php echo esc_url($artwork['image_url']); ?>" alt="<?php echo esc_attr($artwork['title']); ?>">
                        <h3><?php echo esc_html($artwork['title']); ?></h3>
                        <p><?php echo esc_html($artwork['artist']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Top Artists -->
        <div class="vortex-top-artists">
            <h2><?php _e('Top Artists', 'vortex-marketplace'); ?></h2>
            <div class="vortex-artists-container">
                <?php foreach ($top_artists as $artist) : ?>
                    <div class="vortex-artist">
                        <img src="<?php echo esc_url($artist['image_url']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                        <h3><?php echo esc_html($artist['name']); ?></h3>
                        <p><?php echo esc_html($artist['total_sales']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Sales Leaderboard -->
        <div class="vortex-sales-leaderboard">
            <h2><?php _e('Sales Leaderboard', 'vortex-marketplace'); ?></h2>
            <div class="vortex-leaderboard-container">
                <?php foreach ($sales_leaderboard as $leader) : ?>
                    <div class="vortex-leader">
                        <h3><?php echo esc_html($leader['name']); ?></h3>
                        <p><?php echo esc_html($leader['total_sales']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- AI Insights -->
        <div class="vortex-ai-insights">
            <h2><?php _e('AI Insights', 'vortex-marketplace'); ?></h2>
            <div class="vortex-insights-container">
                <?php if (!empty($huraii_insights)) : ?>
                    <div class="vortex-insight">
                        <h3><?php _e('HURAII Insights', 'vortex-marketplace'); ?></h3>
                        <p><?php echo esc_html($huraii_insights['insight']); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($cloe_insights)) : ?>
                    <div class="vortex-insight">
                        <h3><?php _e('CLOE Insights', 'vortex-marketplace'); ?></h3>
                        <p><?php echo esc_html($cloe_insights['insight']); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($business_insights)) : ?>
                    <div class="vortex-insight">
                        <h3><?php _e('Business Strategist Insights', 'vortex-marketplace'); ?></h3>
                        <p><?php echo esc_html($business_insights['insight']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
} 