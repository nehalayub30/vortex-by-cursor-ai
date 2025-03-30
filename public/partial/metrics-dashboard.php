<?php
/**
 * Public Metrics Dashboard Partial
 * 
 * Displays user-friendly marketplace metrics for public users
 * while ensuring AI agent deep learning remains active.
 *
 * @package VORTEX_AI_Marketplace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user_id = get_current_user_id();
$is_artist = false;
$is_collector = false;

if ($current_user_id) {
    $is_artist = user_can($current_user_id, 'vortex_artist');
    $is_collector = user_can($current_user_id, 'vortex_collector');
}

// Initialize AI agents to ensure deep learning during dashboard viewing
do_action('vortex_ai_agent_init', 'CLOE', 'public_metrics_dashboard', array(
    'context' => 'public_analytics_view',
    'user_id' => $current_user_id,
    'session_id' => wp_get_session_token(),
    'learning_enabled' => true
));

// Initialize HURAII with limited scope for public metrics
do_action('vortex_ai_agent_init', 'HURAII', 'public_metrics_dashboard', array(
    'context' => 'public_generation_metrics',
    'user_id' => $current_user_id,
    'session_id' => wp_get_session_token(),
    'learning_enabled' => true
));

// Initialize BusinessStrategist for artist insights if user is an artist
if ($is_artist) {
    do_action('vortex_ai_agent_init', 'BusinessStrategist', 'public_metrics_dashboard', array(
        'context' => 'artist_insights',
        'user_id' => $current_user_id,
        'session_id' => wp_get_session_token(),
        'learning_enabled' => true
    ));
}

// Record this dashboard view for AI learning
do_action('vortex_ai_agent_learn', 'CLOE', 'public_dashboard_view', array(
    'dashboard_type' => 'public_metrics',
    'user_id' => $current_user_id,
    'is_artist' => $is_artist,
    'is_collector' => $is_collector,
    'timestamp' => current_time('mysql'),
));

// Get timeframe from request or use default
$timeframe = isset($_GET['timeframe']) ? sanitize_text_field($_GET['timeframe']) : '30days';

// Get public metrics data based on timeframe and user type
$metrics = apply_filters('vortex_get_public_marketplace_metrics', array(), $timeframe, $current_user_id);

// Allow AI agents to process and enhance metrics data for personalization
$enhanced_metrics = apply_filters('vortex_ai_enhanced_public_metrics', $metrics, $timeframe, $current_user_id);

// Format the date range display based on timeframe
switch ($timeframe) {
    case '7days':
        $date_range = sprintf(
            esc_html__('Last 7 days (%s - %s)', 'vortex-ai-marketplace'),
            date_i18n(get_option('date_format'), strtotime('-7 days')),
            date_i18n(get_option('date_format'))
        );
        break;
    case '30days':
        $date_range = sprintf(
            esc_html__('Last 30 days (%s - %s)', 'vortex-ai-marketplace'),
            date_i18n(get_option('date_format'), strtotime('-30 days')),
            date_i18n(get_option('date_format'))
        );
        break;
    case '90days':
        $date_range = sprintf(
            esc_html__('Last 90 days (%s - %s)', 'vortex-ai-marketplace'),
            date_i18n(get_option('date_format'), strtotime('-90 days')),
            date_i18n(get_option('date_format'))
        );
        break;
    default:
        $date_range = esc_html__('Custom timeframe', 'vortex-ai-marketplace');
}

// Default values for metrics in case they're not provided
$metrics = wp_parse_args($enhanced_metrics, array(
    'marketplace_summary' => array(
        'total_artworks' => 0,
        'total_artists' => 0,
        'featured_artworks' => 0,
        'active_collectors' => 0,
    ),
    'trending_styles' => array(),
    'popular_categories' => array(),
    'recent_sales' => array(),
    'top_artists' => array(),
    'activity_by_day' => array(),
    'popular_formats' => array(),
));

// Get personalized recommendations from CLOE if user is logged in
$personalized_recommendations = array();
if ($current_user_id) {
    $personalized_recommendations = apply_filters('vortex_cloe_get_personalized_recommendations', array(), $current_user_id, 3);
}

// Get artist-specific metrics if user is an artist
$artist_metrics = array();
if ($is_artist) {
    $artist_metrics = apply_filters('vortex_get_artist_metrics', array(), $current_user_id, $timeframe);
    
    // Get business insights from BusinessStrategist
    $business_insights = apply_filters('vortex_get_artist_business_insights', array(), $current_user_id);
}

// Get collector-specific metrics if user is a collector
$collector_metrics = array();
if ($is_collector) {
    $collector_metrics = apply_filters('vortex_get_collector_metrics', array(), $current_user_id, $timeframe);
}

// Generate greeting from CLOE
$greeting = apply_filters('vortex_cloe_get_personalized_greeting', '', $current_user_id);
?>

<div class="vortex-public-metrics-dashboard" data-timeframe="<?php echo esc_attr($timeframe); ?>">
    <?php if (!empty($greeting)) : ?>
    <div class="vortex-cloe-greeting">
        <div class="cloe-avatar"></div>
        <div class="greeting-bubble">
            <?php echo wp_kses_post($greeting); ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="metrics-header">
        <h2><?php esc_html_e('VORTEX AI Marketplace Insights', 'vortex-ai-marketplace'); ?></h2>
        <div class="timeframe-selector">
            <span><?php esc_html_e('Timeframe:', 'vortex-ai-marketplace'); ?></span>
            <select id="vortex-public-timeframe-select">
                <option value="7days" <?php selected($timeframe, '7days'); ?>><?php esc_html_e('Last 7 Days', 'vortex-ai-marketplace'); ?></option>
                <option value="30days" <?php selected($timeframe, '30days'); ?>><?php esc_html_e('Last 30 Days', 'vortex-ai-marketplace'); ?></option>
                <option value="90days" <?php selected($timeframe, '90days'); ?>><?php esc_html_e('Last 90 Days', 'vortex-ai-marketplace'); ?></option>
            </select>
        </div>
        <div class="date-range"><?php echo esc_html($date_range); ?></div>
    </div>
    
    <div class="metrics-loading" style="display: none;">
        <div class="spinner-container">
            <div class="spinner"></div>
            <p><?php esc_html_e('Loading insights...', 'vortex-ai-marketplace'); ?></p>
        </div>
    </div>

    <div class="metrics-content">
        <!-- Marketplace Overview -->
        <div class="metrics-row">
            <div class="metrics-card summary-card">
                <div class="card-header">
                    <h3><?php esc_html_e('Marketplace Overview', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="metrics-grid">
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($metrics['marketplace_summary']['total_artworks'])); ?></div>
                            <div class="metric-label"><?php esc_html_e('Total Artworks', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($metrics['marketplace_summary']['total_artists'])); ?></div>
                            <div class="metric-label"><?php esc_html_e('Active Artists', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($metrics['marketplace_summary']['featured_artworks'])); ?></div>
                            <div class="metric-label"><?php esc_html_e('Featured Artworks', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($metrics['marketplace_summary']['active_collectors'])); ?></div>
                            <div class="metric-label"><?php esc_html_e('Active Collectors', 'vortex-ai-marketplace'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trending and Popular Section -->
        <div class="metrics-row">
            <!-- Trending Styles -->
            <div class="metrics-card">
                <div class="card-header">
                    <h3><?php esc_html_e('Trending Styles', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="trending-styles">
                        <?php if (!empty($metrics['trending_styles'])) : ?>
                            <ol class="trend-list">
                                <?php foreach ($metrics['trending_styles'] as $index => $style) : ?>
                                    <li>
                                        <span class="trend-name"><?php echo esc_html($style['name']); ?></span>
                                        <?php if (!empty($style['growth'])) : ?>
                                            <span class="trend-growth positive">↑ <?php echo esc_html($style['growth']); ?>%</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php else : ?>
                            <p class="no-data"><?php esc_html_e('No trending data available for this timeframe', 'vortex-ai-marketplace'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Popular Categories -->
            <div class="metrics-card">
                <div class="card-header">
                    <h3><?php esc_html_e('Popular Categories', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="popular-categories-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($is_artist) : ?>
        <!-- Artist-specific metrics -->
        <div class="metrics-row">
            <div class="metrics-card artist-performance">
                <div class="card-header">
                    <h3><?php esc_html_e('Your Artist Performance', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="metrics-grid">
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($artist_metrics['total_views'] ?? 0)); ?></div>
                            <div class="metric-label"><?php esc_html_e('Profile Views', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($artist_metrics['total_sales'] ?? 0)); ?></div>
                            <div class="metric-label"><?php esc_html_e('Artwork Sales', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($artist_metrics['total_revenue'] ?? 0, 2)); ?> TOLA</div>
                            <div class="metric-label"><?php esc_html_e('Total Revenue', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($artist_metrics['follower_count'] ?? 0)); ?></div>
                            <div class="metric-label"><?php esc_html_e('Followers', 'vortex-ai-marketplace'); ?></div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="artist-performance-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Business Insights from BusinessStrategist -->
            <div class="metrics-card business-insights">
                <div class="card-header">
                    <h3><?php esc_html_e('Business Insights', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="business-advisor">
                        <div class="advisor-avatar"></div>
                        <div class="advice-content">
                            <?php if (!empty($business_insights['key_insight'])) : ?>
                                <p class="key-insight"><?php echo esc_html($business_insights['key_insight']); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($business_insights['next_milestone'])) : ?>
                                <div class="next-milestone">
                                    <h4><?php esc_html_e('Next Business Milestone', 'vortex-ai-marketplace'); ?></h4>
                                    <div class="milestone-info">
                                        <p><?php echo esc_html($business_insights['next_milestone']['description']); ?></p>
                                        <div class="milestone-progress">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo esc_attr($business_insights['next_milestone']['progress']); ?>%"></div>
                                            </div>
                                            <span class="progress-text"><?php echo esc_html($business_insights['next_milestone']['progress']); ?>%</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($business_insights['weekly_commitment'])) : ?>
                                <div class="weekly-commitment">
                                    <h4><?php esc_html_e('Weekly Artwork Commitment', 'vortex-ai-marketplace'); ?></h4>
                                    <div class="commitment-info">
                                        <p><?php echo esc_html(sprintf(__('You\'ve uploaded %d of 2 artworks this week', 'vortex-ai-marketplace'), $business_insights['weekly_commitment']['current'])); ?></p>
                                        <div class="commitment-progress">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo esc_attr(min(100, $business_insights['weekly_commitment']['current'] / 2 * 100)); ?>%"></div>
                                            </div>
                                            <span class="progress-text"><?php echo esc_html($business_insights['weekly_commitment']['current']); ?>/2</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($is_collector) : ?>
        <!-- Collector-specific metrics -->
        <div class="metrics-row">
            <div class="metrics-card collector-activity">
                <div class="card-header">
                    <h3><?php esc_html_e('Your Collection Activity', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="metrics-grid">
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($collector_metrics['total_collected'] ?? 0)); ?></div>
                            <div class="metric-label"><?php esc_html_e('Collected Artworks', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($collector_metrics['total_spent'] ?? 0, 2)); ?> TOLA</div>
                            <div class="metric-label"><?php esc_html_e('Total Spent', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($collector_metrics['artists_supported'] ?? 0)); ?></div>
                            <div class="metric-label"><?php esc_html_e('Artists Supported', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo esc_html(number_format_i18n($collector_metrics['collection_value'] ?? 0, 2)); ?> TOLA</div>
                            <div class="metric-label"><?php esc_html_e('Collection Value', 'vortex-ai-marketplace'); ?></div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="collector-activity-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Personalized Recommendations from CLOE -->
        <?php if (!empty($personalized_recommendations)) : ?>
        <div class="metrics-row">
            <div class="metrics-card full-width recommendations">
                <div class="card-header">
                    <h3><?php esc_html_e('Recommended For You', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="recommendations-grid">
                        <?php foreach ($personalized_recommendations as $recommendation) : ?>
                            <div class="recommendation-item">
                                <a href="<?php echo esc_url($recommendation['url']); ?>" class="recommendation-link">
                                    <div class="recommendation-image">
                                        <img src="<?php echo esc_url($recommendation['image']); ?>" alt="<?php echo esc_attr($recommendation['title']); ?>">
                                    </div>
                                    <div class="recommendation-info">
                                        <h4><?php echo esc_html($recommendation['title']); ?></h4>
                                        <p class="artist"><?php echo esc_html($recommendation['artist']); ?></p>
                                        <p class="price"><?php echo esc_html(number_format_i18n($recommendation['price'], 2)); ?> TOLA</p>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Marketplace Activity -->
        <div class="metrics-row">
            <div class="metrics-card full-width">
                <div class="card-header">
                    <h3><?php esc_html_e('Marketplace Activity', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="chart-container large">
                        <canvas id="marketplace-activity-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Artists -->
        <div class="metrics-row">
            <div class="metrics-card">
                <div class="card-header">
                    <h3><?php esc_html_e('Top Artists', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="top-artists-list">
                        <?php if (!empty($metrics['top_artists'])) : ?>
                            <ol class="artist-list">
                                <?php foreach ($metrics['top_artists'] as $index => $artist) : ?>
                                    <li>
                                        <?php if (!empty($artist['avatar'])) : ?>
                                            <img src="<?php echo esc_url($artist['avatar']); ?>" alt="<?php echo esc_attr($artist['name']); ?>" class="artist-avatar">
                                        <?php endif; ?>
                                        <div class="artist-info">
                                            <a href="<?php echo esc_url($artist['url']); ?>" class="artist-name"><?php echo esc_html($artist['name']); ?></a>
                                            <span class="artist-stats"><?php echo esc_html(sprintf(__('%d artworks • %d sales', 'vortex-ai-marketplace'), $artist['artwork_count'], $artist['sales_count'])); ?></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php else : ?>
                            <p class="no-data"><?php esc_html_e('No artist data available for this timeframe', 'vortex-ai-marketplace'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Sales -->
            <div class="metrics-card">
                <div class="card-header">
                    <h3><?php esc_html_e('Recent Sales', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="recent-sales-list">
                        <?php if (!empty($metrics['recent_sales'])) : ?>
                            <ul class="sales-list">
                                <?php foreach ($metrics['recent_sales'] as $sale) : ?>
                                    <li>
                                        <?php if (!empty($sale['thumbnail'])) : ?>
                                            <img src="<?php echo esc_url($sale['thumbnail']); ?>" alt="<?php echo esc_attr($sale['title']); ?>" class="sale-thumb">
                                        <?php endif; ?>
                                        <div class="sale-info">
                                            <a href="<?php echo esc_url($sale['url']); ?>" class="artwork-title"><?php echo esc_html($sale['title']); ?></a>
                                            <span class="sale-details"><?php echo esc_html(sprintf(__('by %s • %s TOLA', 'vortex-ai-marketplace'), $sale['artist'], number_format_i18n($sale['price'], 2))); ?></span>
                                            <span class="sale-time"><?php echo esc_html(human_time_diff(strtotime($sale['date']), current_time('timestamp'))); ?> <?php esc_html_e('ago', 'vortex-ai-marketplace'); ?></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            <p class="no-data"><?php esc_html_e('No recent sales data available', 'vortex-ai-marketplace'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Popular Formats -->
        <div class="metrics-row">
            <div class="metrics-card">
                <div class="card-header">
                    <h3><?php esc_html_e('Popular Formats', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="popular-formats-chart"></canvas>
                    </div>
                </div>
            </div>

            <div class="metrics-card cta-card">
                <div class="card-header">
                    <h3><?php esc_html_e('Get Started', 'vortex-ai-marketplace'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="cta-content">
                        <?php if (!$current_user_id) : ?>
                            <p><?php esc_html_e('Join the VORTEX AI Marketplace community to create, collect, and trade digital artwork powered by AI.', 'vortex-ai-marketplace'); ?></p>
                            <div class="cta-buttons">
                                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="button button-primary"><?php esc_html_e('Sign Up Now', 'vortex-ai-marketplace'); ?></a>
                                <a href="<?php echo esc_url(wp_login_url()); ?>" class="button"><?php esc_html_e('Log In', 'vortex-ai-marketplace'); ?></a>
                            </div>
                        <?php elseif ($is_artist) : ?>
                            <p><?php esc_html_e('Ready to create more art? Use HURAII to generate new pieces and build your portfolio.', 'vortex-ai-marketplace'); ?></p>
                            <div class="cta-buttons">
                                <a href="<?php echo esc_url(home_url('/create-artwork/')); ?>" class="button button-primary"><?php esc_html_e('Create New Artwork', 'vortex-ai-marketplace'); ?></a>
                                <a href="<?php echo esc_url(home_url('/artist-dashboard/')); ?>" class="button"><?php esc_html_e('View Your Dashboard', 'vortex-ai-marketplace'); ?></a>
                            </div>
                        <?php elseif ($is_collector) : ?>
                            <p><?php esc_html_e('Discover new artwork that matches your preferences, powered by CLOE's recommendation engine.', 'vortex-ai-marketplace'); ?></p>
                            <div class="cta-buttons">
                                <a href="<?php echo esc_url(home_url('/browse-artwork/')); ?>" class="button button-primary"><?php esc_html_e('Discover Artwork', 'vortex-ai-marketplace'); ?></a>
                                <a href="<?php echo esc_url(home_url('/collector-dashboard/')); ?>" class="button"><?php esc_html_e('View Your Collection', 'vortex-ai-marketplace'); ?></a>
                            </div>
                        <?php else : ?>
                            <p><?php esc_html_e('Choose your role in the VORTEX AI Marketplace to get personalized features and insights.', 'vortex-ai-marketplace'); ?></p>
                            <div class="cta-buttons">
                                <a href="<?php echo esc_url(home_url('/become-artist/')); ?>" class="button button-primary"><?php esc_html_e('Become an Artist', 'vortex-ai-marketplace'); ?></a>
                                <a href="<?php echo esc_url(home_url('/become-collector/')); ?>" class="button"><?php esc_html_e('Become a Collector', 'vortex-ai-marketplace'); ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Track AI interactions for learning
    function trackAIInteraction(agent, action, data) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_track_ai_interaction',
                agent: agent,
                interaction_type: action,
                interaction_data: data,
                nonce: '<?php echo wp_create_nonce('vortex_ai_interaction_nonce'); ?>'
            }
        });
    }
    
    // Initialize charts
    function initCharts() {
        // Popular Categories Chart
        const categoriesData = <?php echo json_encode($metrics['popular_categories'] ?? array()); ?>;
        if (Object.keys(categoriesData).length > 0) {
            const labels = Object.keys(categoriesData);
            const data = Object.values(categoriesData);
            
            const categoriesCtx = document.getElementById('popular-categories-chart').getContext('2d');
            new Chart(categoriesCtx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#4CAF50',
                            '#2196F3',
                            '#9C27B0',
                            '#FF9800',
                            '#F44336',
                            '#00BCD4'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 15,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Popular Formats Chart
        const formatsData = <?php echo json_encode($metrics['popular_formats'] ?? array()); ?>;
        if (Object.keys(formatsData).length > 0) {
            const labels = Object.keys(formatsData);
            const data = Object.values(formatsData);
            
            const formatsCtx = document.getElementById('popular-formats-chart').getContext('2d');
            new Chart(formatsCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '<?php esc_attr_e('Artwork Count', 'vortex-ai-marketplace'); ?>',
                        data: data,
                        backgroundColor: 'rgba(33, 150, 243, 0.7)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
        
        // Marketplace Activity Chart
        const activityData = <?php echo json_encode($metrics['activity_by_day'] ?? array()); ?>;
        if (activityData.length > 0) {
            const activityCtx = document.getElementById('marketplace-activity-chart').getContext('2d');
            new Chart(activityCtx, {
                type: 'line',
                data: {
                    labels: activityData.map(item => item.date),
                    datasets: [{
                        label: '<?php esc_attr_e('New Artworks', 'vortex-ai-marketplace'); ?>',
                        data: activityData.map(item => item.new_artworks),
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: '<?php esc_attr_e('Sales', 'vortex-ai-marketplace'); ?>',
                        data: activityData.map(item => item.sales),
                        borderColor: '#FF9800',
                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
        
        <?php if ($is_artist && !empty($artist_metrics['performance_data'])) : ?>
        // Artist Performance Chart
        const artistData = <?php echo json_encode($artist_metrics['performance_data'] ?? array()); ?>;
        if (artistData.length > 0) {
            const artistCtx = document.getElementById('artist-performance-chart').getContext('2d');
            new Chart(artistCtx, {
                type: 'line',
                data: {
                    labels: artistData.map(item => item.date),
                    datasets: [{
                        label: '<?php esc_attr_e('Views', 'vortex-ai-marketplace'); ?>',
                        data: artistData.map(item => item.views),
                        borderColor: '#2196F3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: '<?php esc_attr_e('Revenue (TOLA)', 'vortex-ai-marketplace'); ?>',
                        data: artistData.map(item => item.revenue),
                        borderColor: '#9C27B0',
                        backgroundColor: 'rgba(156, 39, 176, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: '<?php esc_attr_e('Views', 'vortex-ai-marketplace'); ?>'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: '<?php esc_attr_e('TOLA', 'vortex-ai-marketplace'); ?>'
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>
        
        <?php if ($is_collector && !empty($collector_metrics['activity_data'])) : ?>
        // Collector Activity Chart
        const collectorData = <?php echo json_encode($collector_metrics['activity_data'] ?? array()); ?>;
        if (collectorData.length > 0) {
            const collectorCtx = document.getElementById('collector-activity-chart').getContext('2d');
            new Chart(collectorCtx, {
                type: 'bar',
                data: {
                    labels: collectorData.map(item => item.date),
                    datasets: [{
                        label: '<?php esc_attr_e('Purchases', 'vortex-ai-marketplace'); ?>',
                        data: collectorData.map(item => item.purchases),
                        backgroundColor: 'rgba(76, 175, 80, 0.7)'
                    },
                    {
                        label: '<?php esc_attr_e('Spent (TOLA)', 'vortex-ai-marketplace'); ?>',
                        data: collectorData.map(item => item.spent),
                        backgroundColor: 'rgba(255, 152, 0, 0.7)',
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: '<?php esc_attr_e('TOLA', 'vortex-ai-marketplace'); ?>'
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>
    }
    
    // Initialize charts on page load
    initCharts();
});
</script> 