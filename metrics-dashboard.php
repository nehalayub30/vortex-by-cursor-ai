<?php
/**
 * VORTEX Marketplace Metrics Dashboard
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Analytics
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the metrics dashboard
 *
 * @since 1.0.0
 * @return void
 */
function vortex_render_metrics_dashboard() {
    // Verify user access
    if (!current_user_can('manage_options') && !current_user_can('edit_vortex_artworks')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'vortex-marketplace'));
    }
    
    // Get current user
    $current_user = wp_get_current_user();
    
    // Initialize AI agents for dashboard session
    do_action('vortex_ai_agent_init', 'metrics_dashboard', 
        array('HURAII', 'CLOE', 'BusinessStrategist'), 
        'active', 
        array(
            'user_id' => $current_user->ID,
            'context' => 'analytics_viewing',
            'dashboard_session' => uniqid('dash_')
        )
    );
    
    // Load required classes
    $metrics = VORTEX_Metrics::get_instance();
    $analytics = VORTEX_Analytics::get_instance();
    $rankings = VORTEX_Rankings::get_instance();
    
    // Get summary data
    $summary = $analytics->get_analytics_summary();
    $trending_artworks = $rankings->get_trending_artworks(5);
    $top_artists = $rankings->get_top_artists(5);
    $sales_leaderboard = $rankings->get_sales_leaderboard(5);
    
    // Get period for filtering
    $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '30days';
    $periods = array(
        '7days' => __('Last 7 Days', 'vortex-marketplace'),
        '30days' => __('Last 30 Days', 'vortex-marketplace'),
        '90days' => __('Last 90 Days', 'vortex-marketplace'),
        'alltime' => __('All Time', 'vortex-marketplace')
    );
    
    // Process AI insights from the agents
    $huraii_insights = apply_filters('vortex_huraii_dashboard_insights', array(), $current_user->ID);
    $cloe_insights = apply_filters('vortex_cloe_dashboard_insights', array(), $current_user->ID);
    $business_insights = apply_filters('vortex_business_strategist_dashboard_insights', array(), $current_user->ID);
    
    // Enqueue scripts and styles
    wp_enqueue_style('vortex-metrics-dashboard', VORTEX_PLUGIN_URL . 'css/vortex-metrics-dashboard.css', array(), VORTEX_VERSION);
    wp_enqueue_script('chart-js', VORTEX_PLUGIN_URL . 'assets/js/chart.min.js', array('jquery'), '3.7.0', true);
    wp_enqueue_script('vortex-dashboard-js', VORTEX_PLUGIN_URL . 'assets/js/dashboard.js', array('jquery', 'chart-js'), VORTEX_VERSION, true);
    
    // Localize script data
    wp_localize_script('vortex-dashboard-js', 'vortexDashboardData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vortex_dashboard_nonce'),
        'period' => $period,
        'i18n' => array(
            'viewsLabel' => __('Views', 'vortex-marketplace'),
            'salesLabel' => __('Sales', 'vortex-marketplace'),
            'revenueLabel' => __('Revenue', 'vortex-marketplace'),
            'loadingText' => __('Loading data...', 'vortex-marketplace')
        ),
        'aiAgents' => array(
            'HURAII' => array(
                'active' => true,
                'learning_mode' => 'active',
                'context' => 'metrics_analysis'
            ),
            'CLOE' => array(
                'active' => true,
                'learning_mode' => 'active',
                'context' => 'curation_metrics'
            ),
            'BusinessStrategist' => array(
                'active' => true,
                'learning_mode' => 'active',
                'context' => 'economic_metrics'
            )
        )
    ));
    
    // Track AI learning for this dashboard view
    do_action('vortex_ai_interaction', 'dashboard_view', array(
        'dashboard_type' => 'metrics',
        'period' => $period,
        'sections_displayed' => array('summary', 'trending', 'top_artists', 'sales'),
        'ai_agents' => array(
            'HURAII' => array(
                'status' => 'active',
                'learning_mode' => 'active',
                'context' => 'metrics_analysis'
            ),
            'CLOE' => array(
                'status' => 'active',
                'learning_mode' => 'active',
                'context' => 'curation_metrics'
            ),
            'BusinessStrategist' => array(
                'status' => 'active',
                'learning_mode' => 'active',
                'context' => 'economic_metrics'
            )
        )
    ), $current_user->ID);
    
    // Start dashboard output
    ?>
    <div class="wrap vortex-dashboard">
        <h1><?php _e('VORTEX Marketplace Analytics', 'vortex-marketplace'); ?></h1>
        
        <!-- Period Selection -->
        <div class="vortex-period-selection">
            <form method="get">
                <input type="hidden" name="page" value="vortex-analytics">
                <label for="vortex-period"><?php _e('Time Period:', 'vortex-marketplace'); ?></label>
                <select name="period" id="vortex-period" onchange="this.form.submit()">
                    <?php foreach ($periods as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($period, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        
        <!-- Summary Cards -->
        <div class="vortex-summary-cards">
            <div class="vortex-card">
                <div class="vortex-card-icon sales-icon"></div>
                <div class="vortex-card-content">
                    <h3><?php _e('Total Sales', 'vortex-marketplace'); ?></h3>
                    <p class="vortex-card-value"><?php echo esc_html($summary['metrics']['total_sales']); ?></p>
                </div>
            </div>
            
            <div class="vortex-card">
                <div class="vortex-card-icon revenue-icon"></div>
                <div class="vortex-card-content">
                    <h3><?php _e('Total Revenue', 'vortex-marketplace'); ?></h3>
                    <p class="vortex-card-value"><?php echo esc_html(vortex_format_price($summary['metrics']['total_revenue'])); ?></p>
                </div>
            </div>
            
            <div class="vortex-card">
                <div class="vortex-card-icon views-icon"></div>
                <div class="vortex-card-content">
                    <h3><?php _e('Total Views', 'vortex-marketplace'); ?></h3>
                    <p class="vortex-card-value"><?php echo esc_html(number_format($summary['metrics']['total_views'])); ?></p>
                </div>
            </div>
            
            <div class="vortex-card">
                <div class="vortex-card-icon artists-icon"></div>
                <div class="vortex-card-content">
                    <h3><?php _e('Active Artists', 'vortex-marketplace'); ?></h3>
                    <p class="vortex-card-value"><?php echo esc_html($summary['metrics']['active_artists']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- AI Insights Section -->
        <div class="vortex-ai-insights">
            <h2><?php _e('AI Insights', 'vortex-marketplace'); ?></h2>
            
            <div class="vortex-insights-tabs">
                <button class="vortex-tab-btn active" data-tab="huraii"><?php _e('HURAII', 'vortex-marketplace'); ?></button>
                <button class="vortex-tab-btn" data-tab="cloe"><?php _e('CLOE', 'vortex-marketplace'); ?></button>
                <button class="vortex-tab-btn" data-tab="business"><?php _e('Business Strategist', 'vortex-marketplace'); ?></button>
            </div>
            
            <div class="vortex-tab-content active" id="huraii-tab">
                <?php if (!empty($huraii_insights)) : ?>
                    <div class="vortex-insight-card">
                        <h3><?php echo esc_html($huraii_insights['title'] ?? __('Visual Trends Analysis', 'vortex-marketplace')); ?></h3>
                        <p><?php echo esc_html($huraii_insights['description'] ?? __('HURAII is analyzing visual trends across the marketplace.', 'vortex-marketplace')); ?></p>
                        
                        <?php if (!empty($huraii_insights['key_points'])) : ?>
                            <ul class="vortex-insight-points">
                                <?php foreach ($huraii_insights['key_points'] as $point) : ?>
                                    <li><?php echo esc_html($point); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                    <div class="vortex-insight-card">
                        <p><?php _e('HURAII is analyzing visual data. Check back soon for insights on artwork quality, style trends, and Seed Art component usage patterns.', 'vortex-marketplace'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="vortex-tab-content" id="cloe-tab">
                <?php if (!empty($cloe_insights)) : ?>
                    <div class="vortex-insight-card">
                        <h3><?php echo esc_html($cloe_insights['title'] ?? __('Curation & Discovery Insights', 'vortex-marketplace')); ?></h3>
                        <p><?php echo esc_html($cloe_insights['description'] ?? __('CLOE is analyzing audience preferences and artwork engagement.', 'vortex-marketplace')); ?></p>
                        
                        <?php if (!empty($cloe_insights['key_points'])) : ?>
                            <ul class="vortex-insight-points">
                                <?php foreach ($cloe_insights['key_points'] as $point) : ?>
                                    <li><?php echo esc_html($point); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                    <div class="vortex-insight-card">
                        <p><?php _e('CLOE is analyzing user engagement data. Check back soon for insights on audience preferences, collection recommendations, and emerging artists.', 'vortex-marketplace'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="vortex-tab-content" id="business-tab">
                <?php if (!empty($business_insights)) : ?>
                    <div class="vortex-insight-card">
                        <h3><?php echo esc_html($business_insights['title'] ?? __('Market Analysis', 'vortex-marketplace')); ?></h3>
                        <p><?php echo esc_html($business_insights['description'] ?? __('BusinessStrategist is analyzing market trends and sales data.', 'vortex-marketplace')); ?></p>
                        
                        <?php if (!empty($business_insights['key_points'])) : ?>
                            <ul class="vortex-insight-points">
                                <?php foreach ($business_insights['key_points'] as $point) : ?>
                                    <li><?php echo esc_html($point); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                    <div class="vortex-insight-card">
                        <p><?php _e('BusinessStrategist is analyzing market data. Check back soon for insights on price trends, revenue optimization, and market opportunities.', 'vortex-marketplace'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Dashboard Grid -->
        <div class="vortex-dashboard-grid">
            <!-- Sales & Views Chart -->
            <div class="vortex-dashboard-widget vortex-chart-widget">
                <h2><?php _e('Sales & Views Trends', 'vortex-marketplace'); ?></h2>
                <div class="vortex-chart-container">
                    <canvas id="vortex-sales-views-chart"></canvas>
                </div>
            </div>
            
            <!-- Category Performance -->
            <div class="vortex-dashboard-widget">
                <h2><?php _e('Category Performance', 'vortex-marketplace'); ?></h2>
                <div class="vortex-chart-container">
                    <canvas id="vortex-category-chart"></canvas>
                </div>
            </div>
            
            <!-- Trending Artworks Widget -->
            <div class="vortex-dashboard-widget">
                <h2><?php _e('Trending Artworks', 'vortex-marketplace'); ?></h2>
                <?php 
                // Include trending artworks template
                include(VORTEX_PLUGIN_PATH . 'templates/rankings/trending-artworks.php'); 
                ?>
                <div class="vortex-widget-footer">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-rankings&view=trending')); ?>" class="vortex-view-all">
                        <?php _e('View All', 'vortex-marketplace'); ?> →
                    </a>
                </div>
            </div>
            
            <!-- Top Artists Widget -->
            <div class="vortex-dashboard-widget">
                <h2><?php _e('Top Artists', 'vortex-marketplace'); ?></h2>
                <?php 
                // Include top artists template
                include(VORTEX_PLUGIN_PATH . 'templates/rankings/top-artists.php'); 
                ?>
                <div class="vortex-widget-footer">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-rankings&view=artists')); ?>" class="vortex-view-all">
                        <?php _e('View All', 'vortex-marketplace'); ?> →
                    </a>
                </div>
            </div>
            
            <!-- Sales Leaderboard Widget -->
            <div class="vortex-dashboard-widget">
                <h2><?php _e('Sales Leaderboard', 'vortex-marketplace'); ?></h2>
                <?php 
                // Include sales leaderboard template
                include(VORTEX_PLUGIN_PATH . 'templates/rankings/sales-leaderboard.php'); 
                ?>
                <div class="vortex-widget-footer">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-rankings&view=sales')); ?>" class="vortex-view-all">
                        <?php _e('View All', 'vortex-marketplace'); ?> →
                    </a>
                </div>
            </div>
            
            <!-- Seed Art Components Analysis -->
            <div class="vortex-dashboard-widget vortex-chart-widget">
                <h2><?php _e('Seed Art Components Analysis', 'vortex-marketplace'); ?></h2>
                <div class="vortex-chart-container">
                    <canvas id="vortex-seed-art-chart"></canvas>
                </div>
                <div class="vortex-huraii-insights">
                    <h4><?php _e('HURAII Component Analysis', 'vortex-marketplace'); ?></h4>
                    <div id="vortex-huraii-component-insights">
                        <p class="loading-placeholder"><?php _e('Loading HURAII insights...', 'vortex-marketplace'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
} 