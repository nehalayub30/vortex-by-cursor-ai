<?php
/**
 * Thorius Learning Dashboard
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * The Thorius Learning Dashboard class
 */
class Thorius_Learning_Dashboard {

    /**
     * Initialize the dashboard
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_dashboard_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_dashboard_assets'));
        add_action('wp_ajax_thorius_refresh_data', array($this, 'ajax_refresh_data'));
        add_action('wp_ajax_thorius_save_settings', array($this, 'ajax_save_settings'));
    }

    /**
     * Register the dashboard menu item
     */
    public function add_dashboard_menu() {
        add_submenu_page(
            'vortex-ai-marketplace',
            'Thorius Learning Dashboard',
            'Learning Dashboard',
            'manage_options',
            'thorius-learning-dashboard',
            array($this, 'render_dashboard_page')
        );
    }

    /**
     * Enqueue dashboard styles and scripts
     *
     * @param string $hook The current admin page
     */
    public function enqueue_dashboard_assets($hook) {
        if ('vortex-ai-marketplace_page_thorius-learning-dashboard' !== $hook) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'thorius-learning-dashboard-css',
            plugin_dir_url(__FILE__) . 'css/thorius-learning-dashboard.css',
            array(),
            '1.0.0',
            'all'
        );

        // Enqueue JS
        wp_enqueue_script(
            'thorius-learning-dashboard-js',
            plugin_dir_url(__FILE__) . 'js/thorius-learning-dashboard.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Add localized data for the script
        wp_localize_script(
            'thorius-learning-dashboard-js',
            'thoriusData',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('thorius_dashboard_nonce'),
                'agents' => $this->get_agent_data()
            )
        );
    }

    /**
     * Render the dashboard page
     */
    public function render_dashboard_page() {
        $agents = $this->get_agent_data();
        ?>
        <div class="wrap thorius-dashboard-container">
            <div class="thorius-dashboard-header">
                <h1>Thorius Learning Dashboard</h1>
                <div class="thorius-dashboard-actions">
                    <button id="thorius-refresh-data" class="thorius-refresh-button">
                        <span class="dashicons dashicons-update"></span> Refresh Data
                    </button>
                    <button id="thorius-open-settings" class="thorius-settings-button">
                        <span class="dashicons dashicons-admin-generic"></span> Settings
                    </button>
                </div>
            </div>

            <div id="thorius-settings-panel" class="thorius-settings-panel" style="display: none;">
                <h2>Dashboard Settings</h2>
                <form id="thorius-settings-form" class="thorius-settings-form">
                    <div class="thorius-form-row">
                        <label for="refresh-interval">Refresh Interval (minutes):</label>
                        <input type="number" id="refresh-interval" name="refresh_interval" min="1" max="60" value="15">
                    </div>
                    <div class="thorius-form-row">
                        <label for="data-retention">Data Retention (days):</label>
                        <input type="number" id="data-retention" name="data_retention" min="1" max="365" value="30">
                    </div>
                    <div class="thorius-form-row">
                        <label for="notify-improvements">
                            <input type="checkbox" id="notify-improvements" name="notify_improvements" checked>
                            Notify on significant improvements
                        </label>
                    </div>
                    <div class="thorius-form-actions">
                        <button type="submit" class="button button-primary">Save Settings</button>
                        <button type="button" id="thorius-cancel-settings" class="button">Cancel</button>
                    </div>
                </form>
            </div>

            <div class="thorius-dashboard-layout">
                <!-- Learning Overview -->
                <div class="thorius-dashboard-card thorius-full-width">
                    <div class="thorius-card-header">
                        <h2>Learning Overview</h2>
                    </div>
                    <div class="thorius-card-content">
                        <div class="thorius-stats-grid">
                            <div class="thorius-stat-box">
                                <span class="thorius-stat-value"><?php echo esc_html($this->get_total_adaptations()); ?></span>
                                <span class="thorius-stat-label">Total Adaptations</span>
                            </div>
                            <div class="thorius-stat-box">
                                <span class="thorius-stat-value"><?php echo esc_html($this->get_active_agents()); ?></span>
                                <span class="thorius-stat-label">Active Agents</span>
                            </div>
                            <div class="thorius-stat-box">
                                <span class="thorius-stat-value"><?php echo esc_html($this->get_learning_efficiency()); ?>%</span>
                                <span class="thorius-stat-label">Learning Efficiency</span>
                            </div>
                            <div class="thorius-stat-box">
                                <span class="thorius-stat-value"><?php echo esc_html($this->get_average_accuracy()); ?>%</span>
                                <span class="thorius-stat-label">Average Accuracy</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Learning Trends -->
                <div class="thorius-dashboard-card thorius-half-width">
                    <div class="thorius-card-header">
                        <h2>Learning Trends</h2>
                    </div>
                    <div class="thorius-card-content">
                        <div id="thorius-trends-chart" class="thorius-chart-container"></div>
                    </div>
                </div>

                <!-- Recent Adaptations -->
                <div class="thorius-dashboard-card thorius-half-width">
                    <div class="thorius-card-header">
                        <h2>Recent Adaptations</h2>
                    </div>
                    <div class="thorius-card-content">
                        <table class="thorius-data-table">
                            <thead>
                                <tr>
                                    <th>Agent</th>
                                    <th>Adaptation Type</th>
                                    <th>Date</th>
                                    <th>Impact</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php echo $this->get_recent_adaptations_html(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Agent Performance -->
                <div class="thorius-dashboard-card thorius-full-width">
                    <div class="thorius-card-header">
                        <h2>Agent Performance</h2>
                    </div>
                    <div class="thorius-card-content">
                        <div class="thorius-agent-tabs">
                            <div class="thorius-agent-tab-buttons">
                                <?php foreach ($agents as $index => $agent) : ?>
                                    <button class="thorius-agent-tab-button <?php echo $index === 0 ? 'active' : ''; ?>" 
                                            data-agent-id="<?php echo esc_attr($agent['id']); ?>">
                                        <?php echo esc_html($agent['name']); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div id="thorius-agent-metrics-content" class="thorius-agent-tab-content">
                                <div class="thorius-loading">Loading agent metrics...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get agent data
     *
     * @return array Agent data
     */
    private function get_agent_data() {
        // In a real implementation, this would come from the database
        return array(
            array(
                'id' => 'agent1',
                'name' => 'Customer Service Agent',
            ),
            array(
                'id' => 'agent2',
                'name' => 'Product Recommendation Agent',
            ),
            array(
                'id' => 'agent3',
                'name' => 'Content Creation Agent',
            ),
        );
    }

    /**
     * Get total adaptations
     *
     * @return int Total adaptations
     */
    private function get_total_adaptations() {
        // In a real implementation, this would come from the database
        return 347;
    }

    /**
     * Get active agents
     *
     * @return int Active agents
     */
    private function get_active_agents() {
        // In a real implementation, this would come from the database
        return 3;
    }

    /**
     * Get learning efficiency
     *
     * @return float Learning efficiency
     */
    private function get_learning_efficiency() {
        // In a real implementation, this would come from the database
        return 82.5;
    }

    /**
     * Get average accuracy
     *
     * @return float Average accuracy
     */
    private function get_average_accuracy() {
        // In a real implementation, this would come from the database
        return 91.2;
    }

    /**
     * Get recent adaptations HTML
     *
     * @return string Recent adaptations HTML
     */
    private function get_recent_adaptations_html() {
        // In a real implementation, this would come from the database
        $adaptations = array(
            array(
                'agent' => 'Customer Service Agent',
                'type' => 'Response Optimization',
                'date' => '2023-11-14',
                'impact' => 'High',
            ),
            array(
                'agent' => 'Product Recommendation Agent',
                'type' => 'Algorithm Refinement',
                'date' => '2023-11-13',
                'impact' => 'Medium',
            ),
            array(
                'agent' => 'Content Creation Agent',
                'type' => 'Vocabulary Expansion',
                'date' => '2023-11-12',
                'impact' => 'Low',
            ),
            array(
                'agent' => 'Customer Service Agent',
                'type' => 'Intent Recognition',
                'date' => '2023-11-10',
                'impact' => 'Medium',
            ),
        );

        $html = '';
        foreach ($adaptations as $adaptation) {
            $impact_class = strtolower($adaptation['impact']);
            $html .= '<tr>';
            $html .= '<td>' . esc_html($adaptation['agent']) . '</td>';
            $html .= '<td>' . esc_html($adaptation['type']) . '</td>';
            $html .= '<td>' . esc_html($adaptation['date']) . '</td>';
            $html .= '<td><span class="thorius-impact-badge ' . esc_attr($impact_class) . '">' . esc_html($adaptation['impact']) . '</span></td>';
            $html .= '</tr>';
        }

        return $html;
    }

    /**
     * AJAX handler for refreshing dashboard data
     */
    public function ajax_refresh_data() {
        check_ajax_referer('thorius_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // In a real implementation, this would fetch fresh data from the database
        $response = array(
            'total_adaptations' => $this->get_total_adaptations() + mt_rand(1, 5),
            'active_agents' => $this->get_active_agents(),
            'learning_efficiency' => round($this->get_learning_efficiency() + (mt_rand(-10, 10) / 10), 1),
            'average_accuracy' => round($this->get_average_accuracy() + (mt_rand(-5, 5) / 10), 1),
            'recent_adaptations' => $this->get_recent_adaptations_html(),
        );

        wp_send_json_success($response);
    }

    /**
     * AJAX handler for saving dashboard settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('thorius_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $refresh_interval = isset($_POST['refresh_interval']) ? intval($_POST['refresh_interval']) : 15;
        $data_retention = isset($_POST['data_retention']) ? intval($_POST['data_retention']) : 30;
        $notify_improvements = isset($_POST['notify_improvements']) ? (bool)$_POST['notify_improvements'] : false;

        // In a real implementation, this would save settings to the database
        update_option('thorius_refresh_interval', $refresh_interval);
        update_option('thorius_data_retention', $data_retention);
        update_option('thorius_notify_improvements', $notify_improvements);

        wp_send_json_success(array(
            'message' => 'Settings saved successfully',
        ));
    }
}

// Initialize the dashboard
$thorius_learning_dashboard = new Thorius_Learning_Dashboard(); 