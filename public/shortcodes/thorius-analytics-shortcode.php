<?php
/**
 * Thorius Analytics Shortcode
 * 
 * Renders analytics dashboard for Thorius AI usage
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Sanitize attributes
$type = sanitize_text_field($atts['type']);
$period = sanitize_text_field($atts['period']);

// Get analytics data
$analytics = new Vortex_Thorius_Analytics();
$data = $analytics->get_analytics_data($type, $period);

// Check user permissions - restrict to admins and account owners
$can_view = current_user_can('manage_options');
if (!$can_view) {
    echo '<div class="vortex-thorius-restricted">';
    esc_html_e('You do not have permission to view this analytics dashboard.', 'vortex-ai-marketplace');
    echo '</div>';
    return;
}
?>

<div class="vortex-thorius-analytics-dashboard">
    <div class="vortex-thorius-analytics-header">
        <h3><?php esc_html_e('Thorius AI Analytics', 'vortex-ai-marketplace'); ?></h3>
        
        <div class="vortex-thorius-analytics-filters">
            <div class="vortex-thorius-analytics-filter">
                <label for="vortex-analytics-type"><?php esc_html_e('Type:', 'vortex-ai-marketplace'); ?></label>
                <select id="vortex-analytics-type" class="vortex-analytics-select">
                    <option value="overview" <?php selected($type, 'overview'); ?>><?php esc_html_e('Overview', 'vortex-ai-marketplace'); ?></option>
                    <option value="usage" <?php selected($type, 'usage'); ?>><?php esc_html_e('Usage', 'vortex-ai-marketplace'); ?></option>
                    <option value="agents" <?php selected($type, 'agents'); ?>><?php esc_html_e('Agents', 'vortex-ai-marketplace'); ?></option>
                    <option value="user" <?php selected($type, 'user'); ?>><?php esc_html_e('User Activity', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="vortex-thorius-analytics-filter">
                <label for="vortex-analytics-period"><?php esc_html_e('Period:', 'vortex-ai-marketplace'); ?></label>
                <select id="vortex-analytics-period" class="vortex-analytics-select">
                    <option value="7days" <?php selected($period, '7days'); ?>><?php esc_html_e('Last 7 Days', 'vortex-ai-marketplace'); ?></option>
                    <option value="30days" <?php selected($period, '30days'); ?>><?php esc_html_e('Last 30 Days', 'vortex-ai-marketplace'); ?></option>
                    <option value="90days" <?php selected($period, '90days'); ?>><?php esc_html_e('Last 90 Days', 'vortex-ai-marketplace'); ?></option>
                    <option value="1year" <?php selected($period, '1year'); ?>><?php esc_html_e('Last Year', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <button id="vortex-analytics-update" class="vortex-analytics-button"><?php esc_html_e('Update', 'vortex-ai-marketplace'); ?></button>
        </div>
    </div>
    
    <div class="vortex-thorius-analytics-content">
        <!-- Overview Dashboard -->
        <div id="vortex-analytics-overview" class="vortex-analytics-section <?php echo ($type === 'overview') ? 'active' : ''; ?>">
            <div class="vortex-analytics-stats-grid">
                <div class="vortex-analytics-stat-card">
                    <div class="vortex-analytics-stat-title"><?php esc_html_e('Total Interactions', 'vortex-ai-marketplace'); ?></div>
                    <div class="vortex-analytics-stat-value"><?php echo esc_html($data['overview']['total_interactions']); ?></div>
                    <div class="vortex-analytics-stat-trend <?php echo esc_attr($data['overview']['total_trend_class']); ?>">
                        <?php echo esc_html($data['overview']['total_trend']); ?>
                    </div>
                </div>
                
                <div class="vortex-analytics-stat-card">
                    <div class="vortex-analytics-stat-title"><?php esc_html_e('Thorius Usage', 'vortex-ai-marketplace'); ?></div>
                    <div class="vortex-analytics-stat-value"><?php echo esc_html($data['overview']['thorius_usage']); ?></div>
                    <div class="vortex-analytics-stat-trend <?php echo esc_attr($data['overview']['thorius_trend_class']); ?>">
                        <?php echo esc_html($data['overview']['thorius_trend']); ?>
                    </div>
                </div>
                
                <div class="vortex-analytics-stat-card">
                    <div class="vortex-analytics-stat-title"><?php esc_html_e('Agent Requests', 'vortex-ai-marketplace'); ?></div>
                    <div class="vortex-analytics-stat-value"><?php echo esc_html($data['overview']['agent_requests']); ?></div>
                    <div class="vortex-analytics-stat-trend <?php echo esc_attr($data['overview']['agent_trend_class']); ?>">
                        <?php echo esc_html($data['overview']['agent_trend']); ?>
                    </div>
                </div>
                
                <div class="vortex-analytics-stat-card">
                    <div class="vortex-analytics-stat-title"><?php esc_html_e('Unique Users', 'vortex-ai-marketplace'); ?></div>
                    <div class="vortex-analytics-stat-value"><?php echo esc_html($data['overview']['unique_users']); ?></div>
                    <div class="vortex-analytics-stat-trend <?php echo esc_attr($data['overview']['users_trend_class']); ?>">
                        <?php echo esc_html($data['overview']['users_trend']); ?>
                    </div>
                </div>
            </div>
            
            <div class="vortex-analytics-chart-container">
                <canvas id="vortex-analytics-overview-chart"></canvas>
            </div>
        </div>
        
        <!-- Usage Analytics -->
        <div id="vortex-analytics-usage" class="vortex-analytics-section <?php echo ($type === 'usage') ? 'active' : ''; ?>">
            <div class="vortex-analytics-chart-container">
                <canvas id="vortex-analytics-usage-chart"></canvas>
            </div>
            
            <div class="vortex-analytics-table-container">
                <table class="vortex-analytics-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Date', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Interactions', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Users', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Avg. Response Time', 'vortex-ai-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['usage']['daily_data'] as $day): ?>
                        <tr>
                            <td><?php echo esc_html($day['date']); ?></td>
                            <td><?php echo esc_html($day['interactions']); ?></td>
                            <td><?php echo esc_html($day['users']); ?></td>
                            <td><?php echo esc_html($day['avg_response_time']); ?>s</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Agent Analytics -->
        <div id="vortex-analytics-agents" class="vortex-analytics-section <?php echo ($type === 'agents') ? 'active' : ''; ?>">
            <div class="vortex-analytics-chart-container">
                <canvas id="vortex-analytics-agents-chart"></canvas>
            </div>
            
            <div class="vortex-analytics-table-container">
                <table class="vortex-analytics-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Agent', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Requests', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Success Rate', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Avg. Processing Time', 'vortex-ai-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['agents']['data'] as $agent): ?>
                        <tr>
                            <td><?php echo esc_html($agent['name']); ?></td>
                            <td><?php echo esc_html($agent['requests']); ?></td>
                            <td><?php echo esc_html($agent['success_rate']); ?>%</td>
                            <td><?php echo esc_html($agent['avg_processing_time']); ?>s</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- User Activity -->
        <div id="vortex-analytics-user" class="vortex-analytics-section <?php echo ($type === 'user') ? 'active' : ''; ?>">
            <div class="vortex-analytics-chart-container">
                <canvas id="vortex-analytics-user-chart"></canvas>
            </div>
            
            <div class="vortex-analytics-table-container">
                <table class="vortex-analytics-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('User', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Interactions', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Last Active', 'vortex-ai-marketplace'); ?></th>
                            <th><?php esc_html_e('Favorite Agent', 'vortex-ai-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['user']['users'] as $user): ?>
                        <tr>
                            <td><?php echo esc_html($user['name']); ?></td>
                            <td><?php echo esc_html($user['interactions']); ?></td>
                            <td><?php echo esc_html($user['last_active']); ?></td>
                            <td><?php echo esc_html($user['favorite_agent']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle filter changes
    $('#vortex-analytics-update').on('click', function() {
        var type = $('#vortex-analytics-type').val();
        var period = $('#vortex-analytics-period').val();
        
        window.location.href = '<?php echo esc_js(remove_query_arg(array('type', 'period'))); ?>&type=' + type + '&period=' + period;
    });
    
    // Initialize charts with data from PHP
    var analyticsData = <?php echo json_encode($data); ?>;
    
    // Initialize Chart.js instances for each chart
    function initCharts() {
        // Overview chart
        if ($('#vortex-analytics-overview-chart').length) {
            var ctx = document.getElementById('vortex-analytics-overview-chart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: analyticsData.overview.chart.labels,
                    datasets: [
                        {
                            label: '<?php esc_html_e('Interactions', 'vortex-ai-marketplace'); ?>',
                            data: analyticsData.overview.chart.interactions,
                            borderColor: '#5a67d8',
                            backgroundColor: 'rgba(90, 103, 216, 0.1)',
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: '<?php esc_html_e('Thorius AI Activity Overview', 'vortex-ai-marketplace'); ?>'
                        }
                    }
                }
            });
        }
        
        // Initialize other charts similarly...
    }
    
    // Call chart initialization
    initCharts();
});
</script> 