<?php
/**
 * Thorius Analytics Dashboard
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get analytics data
require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/class-vortex-thorius-analytics.php';
$analytics = new Vortex_Thorius_Analytics();

// Set default values
$period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '30days';
$event_type = isset($_GET['event_type']) ? sanitize_text_field($_GET['event_type']) : 'all';

// Get data
$data = $analytics->get_analytics($period, $event_type);
?>

<div class="wrap thorius-analytics-dashboard">
    <h1 class="wp-heading-inline"><?php _e('Thorius Analytics', 'vortex-ai-marketplace'); ?></h1>

    <!-- Filters -->
    <div class="thorius-analytics-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="vortex-thorius-analytics">
            
            <select name="period" id="thorius-period-filter">
                <option value="7days" <?php selected($period, '7days'); ?>><?php _e('Last 7 Days', 'vortex-ai-marketplace'); ?></option>
                <option value="30days" <?php selected($period, '30days'); ?>><?php _e('Last 30 Days', 'vortex-ai-marketplace'); ?></option>
                <option value="90days" <?php selected($period, '90days'); ?>><?php _e('Last 90 Days', 'vortex-ai-marketplace'); ?></option>
                <option value="1year" <?php selected($period, '1year'); ?>><?php _e('Last Year', 'vortex-ai-marketplace'); ?></option>
            </select>
            
            <select name="event_type" id="thorius-event-type-filter">
                <option value="all" <?php selected($event_type, 'all'); ?>><?php _e('All Events', 'vortex-ai-marketplace'); ?></option>
                <?php if (!empty($data['summary']['event_types'])): ?>
                    <?php foreach (array_keys($data['summary']['event_types']) as $type): ?>
                        <option value="<?php echo esc_attr($type); ?>" <?php selected($event_type, $type); ?>>
                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $type))); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            
            <button type="submit" class="button"><?php _e('Apply Filters', 'vortex-ai-marketplace'); ?></button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="thorius-card thorius-summary-card">
        <div class="thorius-card-header">
            <h2><?php _e('Analytics Summary', 'vortex-ai-marketplace'); ?></h2>
        </div>
        <div class="thorius-card-content">
            <div class="thorius-stats-grid">
                <div class="thorius-stat-box">
                    <div class="thorius-stat-value"><?php echo number_format($data['summary']['total_events']); ?></div>
                    <div class="thorius-stat-label"><?php _e('Total Events', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="thorius-stat-box">
                    <div class="thorius-stat-value"><?php echo count($data['summary']['event_types']); ?></div>
                    <div class="thorius-stat-label"><?php _e('Event Types', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="thorius-stat-box">
                    <div class="thorius-stat-value"><?php echo date_i18n(get_option('date_format'), strtotime($data['start_date'])); ?></div>
                    <div class="thorius-stat-label"><?php _e('Start Date', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="thorius-stat-box">
                    <div class="thorius-stat-value"><?php echo date_i18n(get_option('date_format'), strtotime($data['end_date'])); ?></div>
                    <div class="thorius-stat-label"><?php _e('End Date', 'vortex-ai-marketplace'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Types Chart -->
    <div class="thorius-card">
        <div class="thorius-card-header">
            <h2><?php _e('Event Distribution', 'vortex-ai-marketplace'); ?></h2>
        </div>
        <div class="thorius-card-content">
            <div class="thorius-chart-container">
                <canvas id="thorius-event-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Daily Activity Chart -->
    <div class="thorius-card">
        <div class="thorius-card-header">
            <h2><?php _e('Daily Activity', 'vortex-ai-marketplace'); ?></h2>
        </div>
        <div class="thorius-card-content">
            <div class="thorius-chart-container">
                <canvas id="thorius-daily-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Agent Usage -->
    <div class="thorius-card">
        <div class="thorius-card-header">
            <h2><?php _e('Agent Usage', 'vortex-ai-marketplace'); ?></h2>
        </div>
        <div class="thorius-card-content">
            <?php if (!empty($data['summary']['agents'])): ?>
                <div class="thorius-chart-container">
                    <canvas id="thorius-agent-usage-chart"></canvas>
                </div>
            <?php else: ?>
                <p><?php _e('No agent usage data available.', 'vortex-ai-marketplace'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Event List -->
    <div class="thorius-card">
        <div class="thorius-card-header">
            <h2><?php _e('Recent Events', 'vortex-ai-marketplace'); ?></h2>
        </div>
        <div class="thorius-card-content">
            <?php if (!empty($data['results'])): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Time', 'vortex-ai-marketplace'); ?></th>
                            <th><?php _e('Event Type', 'vortex-ai-marketplace'); ?></th>
                            <th><?php _e('User', 'vortex-ai-marketplace'); ?></th>
                            <th><?php _e('Details', 'vortex-ai-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($data['results'], 0, 20) as $event): ?>
                            <tr>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event['created_at'])); ?></td>
                                <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $event['event_type']))); ?></td>
                                <td>
                                    <?php 
                                    if (isset($event['event_data']['user_id']) && $event['event_data']['user_id']) {
                                        $user = get_userdata($event['event_data']['user_id']);
                                        echo $user ? esc_html($user->display_name) : __('Unknown User', 'vortex-ai-marketplace');
                                    } else {
                                        echo __('Guest', 'vortex-ai-marketplace');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($event['event_data'])) {
                                        // Display the most relevant data
                                        if (isset($event['event_data']['agent'])) {
                                            echo '<strong>' . __('Agent:', 'vortex-ai-marketplace') . '</strong> ' . esc_html(strtoupper($event['event_data']['agent'])) . '<br>';
                                        }
                                        
                                        if (isset($event['event_data']['query'])) {
                                            $query = $event['event_data']['query'];
                                            echo '<strong>' . __('Query:', 'vortex-ai-marketplace') . '</strong> ' . (strlen($query) > 50 ? esc_html(substr($query, 0, 50) . '...') : esc_html($query));
                                        }
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No events found.', 'vortex-ai-marketplace'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Check if Chart.js is available
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded');
        return;
    }
    
    // Event Types Chart
    var eventTypesCtx = document.getElementById('thorius-event-chart').getContext('2d');
    var eventTypesData = <?php 
        $labels = array();
        $values = array();
        foreach ($data['summary']['event_types'] as $type => $count) {
            $labels[] = ucfirst(str_replace('_', ' ', $type));
            $values[] = $count;
        }
        echo json_encode(array('labels' => $labels, 'values' => $values));
    ?>;
    
    new Chart(eventTypesCtx, {
        type: 'bar',
        data: {
            labels: eventTypesData.labels,
            datasets: [{
                label: '<?php _e('Event Count', 'vortex-ai-marketplace'); ?>',
                data: eventTypesData.values,
                backgroundColor: 'rgba(54, 162, 235, 0.8)'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Daily Activity Chart
    var dailyActivityCtx = document.getElementById('thorius-daily-chart').getContext('2d');
    var dailyData = <?php 
        $daily_labels = array();
        $daily_values = array();
        foreach ($data['summary']['daily_activity'] as $date => $count) {
            $daily_labels[] = date_i18n(get_option('date_format'), strtotime($date));
            $daily_values[] = $count;
        }
        echo json_encode(array('labels' => $daily_labels, 'values' => $daily_values));
    ?>;
    
    new Chart(dailyActivityCtx, {
        type: 'line',
        data: {
            labels: dailyData.labels,
            datasets: [{
                label: '<?php _e('Daily Events', 'vortex-ai-marketplace'); ?>',
                data: dailyData.values,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Agent Usage Chart
    <?php if (!empty($data['summary']['agents'])): ?>
    var agentUsageCtx = document.getElementById('thorius-agent-usage-chart').getContext('2d');
    var agentData = <?php 
        $agent_labels = array();
        $agent_values = array();
        foreach ($data['summary']['agents'] as $agent => $count) {
            $agent_labels[] = strtoupper($agent);
            $agent_values[] = $count;
        }
        echo json_encode(array('labels' => $agent_labels, 'values' => $agent_values));
    ?>;
    
    new Chart(agentUsageCtx, {
        type: 'doughnut',
        data: {
            labels: agentData.labels,
            datasets: [{
                data: agentData.values,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
        }
    });
    <?php endif; ?>
});
</script> 