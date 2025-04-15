<?php
/**
 * The admin-specific functionality of the Thorius AI component.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The admin-specific functionality of the Thorius AI component.
 *
 * Defines the plugin name, version, and hooks for the admin area.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin
 * @author     Marianne Nems <support@vortexartec.com>
 */
class Vortex_Thorius_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name = 'vortex-ai-marketplace', $version = '1.0.0') {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        // AJAX handlers
        add_action('wp_ajax_vortex_thorius_test', array($this, 'ajax_run_integration_test'));
    }

    /**
     * Register the admin menu pages.
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Thorius AI', 'vortex-ai-marketplace'),
            __('Thorius AI', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-thorius',
            array($this, 'render_dashboard_page'),
            'dashicons-admin-generic',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'vortex-thorius',
            __('Dashboard', 'vortex-ai-marketplace'),
            __('Dashboard', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-thorius',
            array($this, 'render_dashboard_page')
        );
        
        // Learning System submenu
        add_submenu_page(
            'vortex-thorius',
            __('Learning System', 'vortex-ai-marketplace'),
            __('Learning System', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-thorius-learning',
            array($this, 'render_learning_dashboard')
        );
        
        // Analytics submenu
        add_submenu_page(
            'vortex-thorius',
            __('Analytics', 'vortex-ai-marketplace'),
            __('Analytics', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-thorius-analytics',
            array($this, 'render_analytics_page')
        );
        
        // Intelligence submenu
        add_submenu_page(
            'vortex-thorius',
            __('Intelligence', 'vortex-ai-marketplace'),
            __('Intelligence', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-thorius-intelligence',
            array($this, 'render_intelligence_page')
        );
        
        // Synthesis submenu
        add_submenu_page(
            'vortex-thorius',
            __('Synthesis Reports', 'vortex-ai-marketplace'),
            __('Synthesis Reports', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-thorius-synthesis',
            array($this, 'render_synthesis_page')
        );
        
        // Agents submenu
        add_submenu_page(
            'vortex-thorius',
            __('Manage Agents', 'vortex-ai-marketplace'),
            __('Manage Agents', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-thorius-agents',
            array($this, 'render_agents_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'vortex-thorius',
            __('Settings', 'vortex-ai-marketplace'),
            __('Settings', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-thorius-settings',
            array($this, 'render_settings_page')
        );
        
        // Test submenu
        add_submenu_page(
            'vortex-thorius',
            __('Integration Test', 'vortex-ai-marketplace'),
            __('Integration Test', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-thorius-test',
            array($this, 'render_integration_test_page')
        );
    }

    /**
     * Enqueue admin styles and scripts
     */
    public function enqueue_admin_assets($hook) {
        // Main admin styles
        wp_enqueue_style(
            'vortex-thorius-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/thorius-admin.css',
            array(),
            $this->version
        );
        
        // Main admin script
        wp_enqueue_script(
            'vortex-thorius-admin-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/thorius-admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Pass data to admin script
        wp_localize_script(
            'vortex-thorius-admin-js',
            'thorius_admin_params',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vortex_thorius_admin_nonce'),
                'i18n' => array(
                    'error' => __('Error', 'vortex-ai-marketplace'),
                    'success' => __('Success', 'vortex-ai-marketplace'),
                    'loading' => __('Loading...', 'vortex-ai-marketplace'),
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'vortex-ai-marketplace')
                )
            )
        );
        
        // Chart.js for dashboard
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
            array(),
            '3.7.1',
            true
        );
    }

    /**
     * Render the main Thorius AI dashboard page
     */
    public function render_dashboard_page() {
        // Get instance of main Thorius class
        $thorius = null;
        if (class_exists('Vortex_Thorius')) {
            $thorius = Vortex_Thorius::get_instance();
        }
        
        // Get analytics data for dashboard
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-analytics.php';
        $analytics = new Vortex_Thorius_Analytics();
        $stats = $analytics->get_dashboard_stats();
        
        // Start output buffering
        ?>
        <div class="wrap thorius-dashboard">
            <h1 class="wp-heading-inline">
                <?php _e('Thorius AI Dashboard', 'vortex-ai-marketplace'); ?>
            </h1>
            
            <div class="thorius-dashboard-header">
                <div class="thorius-header-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-thorius-settings')); ?>" class="button">
                        <span class="dashicons dashicons-admin-generic"></span> <?php _e('Settings', 'vortex-ai-marketplace'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-thorius-test')); ?>" class="button">
                        <span class="dashicons dashicons-hammer"></span> <?php _e('Run System Test', 'vortex-ai-marketplace'); ?>
                    </a>
                </div>
            </div>
            
            <!-- System Status Card -->
            <div class="thorius-card thorius-system-status">
                <div class="thorius-card-header">
                    <h2><?php _e('System Status', 'vortex-ai-marketplace'); ?></h2>
                </div>
                <div class="thorius-card-content">
                    <div class="thorius-status-grid">
                        <?php
                        // API Connectivity
                        $openai_key = get_option('vortex_thorius_openai_key');
                        $openai_status = !empty($openai_key) ? 'active' : 'inactive';
                        
                        $stability_key = get_option('vortex_thorius_stability_key');
                        $stability_status = !empty($stability_key) ? 'active' : 'inactive';
                        
                        // Agent Status
                        $cloe_enabled = get_option('vortex_thorius_enable_cloe', false);
                        $cloe_status = $cloe_enabled ? 'active' : 'inactive';
                        
                        $huraii_enabled = get_option('vortex_thorius_enable_huraii', false);
                        $huraii_status = $huraii_enabled ? 'active' : 'inactive';
                        
                        $strategist_enabled = get_option('vortex_thorius_enable_strategist', false);
                        $strategist_status = $strategist_enabled ? 'active' : 'inactive';
                        
                        // Database Status
                        $db_tables_exist = $this->check_database_tables();
                        $db_status = $db_tables_exist ? 'active' : 'error';
                        ?>
                        
                        <div class="thorius-status-item">
                            <div class="thorius-status-label"><?php _e('OpenAI API', 'vortex-ai-marketplace'); ?></div>
                            <div class="thorius-status-indicator <?php echo esc_attr($openai_status); ?>"></div>
                            <div class="thorius-status-text">
                                <?php echo $openai_status === 'active' ? __('Connected', 'vortex-ai-marketplace') : __('Not Configured', 'vortex-ai-marketplace'); ?>
                            </div>
                        </div>
                        
                        <div class="thorius-status-item">
                            <div class="thorius-status-label"><?php _e('Stability API', 'vortex-ai-marketplace'); ?></div>
                            <div class="thorius-status-indicator <?php echo esc_attr($stability_status); ?>"></div>
                            <div class="thorius-status-text">
                                <?php echo $stability_status === 'active' ? __('Connected', 'vortex-ai-marketplace') : __('Not Configured', 'vortex-ai-marketplace'); ?>
                            </div>
                        </div>
                        
                        <div class="thorius-status-item">
                            <div class="thorius-status-label"><?php _e('CLOE Agent', 'vortex-ai-marketplace'); ?></div>
                            <div class="thorius-status-indicator <?php echo esc_attr($cloe_status); ?>"></div>
                            <div class="thorius-status-text">
                                <?php echo $cloe_status === 'active' ? __('Active', 'vortex-ai-marketplace') : __('Inactive', 'vortex-ai-marketplace'); ?>
                            </div>
                        </div>
                        
                        <div class="thorius-status-item">
                            <div class="thorius-status-label"><?php _e('HURAII Agent', 'vortex-ai-marketplace'); ?></div>
                            <div class="thorius-status-indicator <?php echo esc_attr($huraii_status); ?>"></div>
                            <div class="thorius-status-text">
                                <?php echo $huraii_status === 'active' ? __('Active', 'vortex-ai-marketplace') : __('Inactive', 'vortex-ai-marketplace'); ?>
                            </div>
                        </div>
                        
                        <div class="thorius-status-item">
                            <div class="thorius-status-label"><?php _e('Strategist Agent', 'vortex-ai-marketplace'); ?></div>
                            <div class="thorius-status-indicator <?php echo esc_attr($strategist_status); ?>"></div>
                            <div class="thorius-status-text">
                                <?php echo $strategist_status === 'active' ? __('Active', 'vortex-ai-marketplace') : __('Inactive', 'vortex-ai-marketplace'); ?>
                            </div>
                        </div>
                        
                        <div class="thorius-status-item">
                            <div class="thorius-status-label"><?php _e('Database', 'vortex-ai-marketplace'); ?></div>
                            <div class="thorius-status-indicator <?php echo esc_attr($db_status); ?>"></div>
                            <div class="thorius-status-text">
                                <?php echo $db_status === 'active' ? __('Connected', 'vortex-ai-marketplace') : __('Tables Missing', 'vortex-ai-marketplace'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Usage Stats Card -->
            <div class="thorius-card thorius-usage-stats">
                <div class="thorius-card-header">
                    <h2><?php _e('Usage Statistics', 'vortex-ai-marketplace'); ?></h2>
                    <div class="thorius-card-actions">
                        <select id="thorius-usage-period" class="thorius-period-selector">
                            <option value="7"><?php _e('Last 7 Days', 'vortex-ai-marketplace'); ?></option>
                            <option value="30" selected><?php _e('Last 30 Days', 'vortex-ai-marketplace'); ?></option>
                            <option value="90"><?php _e('Last 90 Days', 'vortex-ai-marketplace'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="thorius-card-content">
                    <div class="thorius-stats-grid">
                        <div class="thorius-stat-box">
                            <div class="thorius-stat-value"><?php echo number_format($stats['total_queries']); ?></div>
                            <div class="thorius-stat-label"><?php _e('Total Queries', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        
                        <div class="thorius-stat-box">
                            <div class="thorius-stat-value"><?php echo number_format($stats['unique_users']); ?></div>
                            <div class="thorius-stat-label"><?php _e('Unique Users', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        
                        <div class="thorius-stat-box">
                            <div class="thorius-stat-value"><?php echo number_format($stats['avg_queries_per_user'], 1); ?></div>
                            <div class="thorius-stat-label"><?php _e('Avg. Queries Per User', 'vortex-ai-marketplace'); ?></div>
                        </div>
                        
                        <div class="thorius-stat-box">
                            <div class="thorius-stat-value"><?php echo number_format($stats['tokens_used']); ?></div>
                            <div class="thorius-stat-label"><?php _e('Total Tokens Used', 'vortex-ai-marketplace'); ?></div>
                        </div>
                    </div>
                    
                    <!-- Agent Usage Chart -->
                    <div class="thorius-chart-container">
                        <h3><?php _e('Agent Usage Distribution', 'vortex-ai-marketplace'); ?></h3>
                        <div class="thorius-agent-chart-wrapper">
                            <canvas id="thorius-agent-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="thorius-recent-activity">
                        <h3><?php _e('Recent Activity', 'vortex-ai-marketplace'); ?></h3>
                        <?php if (!empty($stats['recent_activity'])) : ?>
                            <table class="widefat thorius-activity-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Time', 'vortex-ai-marketplace'); ?></th>
                                        <th><?php _e('User', 'vortex-ai-marketplace'); ?></th>
                                        <th><?php _e('Agent', 'vortex-ai-marketplace'); ?></th>
                                        <th><?php _e('Query', 'vortex-ai-marketplace'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['recent_activity'] as $activity) : ?>
                                        <tr>
                                            <td><?php echo esc_html($activity['time']); ?></td>
                                            <td><?php echo esc_html($activity['user']); ?></td>
                                            <td><?php echo esc_html($activity['agent']); ?></td>
                                            <td><?php echo esc_html($activity['query']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <p><?php _e('No recent activity recorded.', 'vortex-ai-marketplace'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions Card -->
            <div class="thorius-card thorius-quick-actions">
                <div class="thorius-card-header">
                    <h2><?php _e('Quick Actions', 'vortex-ai-marketplace'); ?></h2>
                </div>
                <div class="thorius-card-content">
                    <div class="thorius-action-grid">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-thorius-intelligence')); ?>" class="thorius-action-button">
                            <span class="dashicons dashicons-chart-line"></span>
                            <span class="action-title"><?php _e('Intelligence Dashboard', 'vortex-ai-marketplace'); ?></span>
                            <span class="action-description"><?php _e('Query your data and get insights with natural language', 'vortex-ai-marketplace'); ?></span>
                        </a>
                        
                        <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-thorius-analytics')); ?>" class="thorius-action-button">
                            <span class="dashicons dashicons-chart-bar"></span>
                            <span class="action-title"><?php _e('View Analytics', 'vortex-ai-marketplace'); ?></span>
                            <span class="action-description"><?php _e('Detailed analytics about AI usage and performance', 'vortex-ai-marketplace'); ?></span>
                        </a>
                        
                        <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-thorius-synthesis')); ?>" class="thorius-action-button">
                            <span class="dashicons dashicons-welcome-view-site"></span>
                            <span class="action-title"><?php _e('Synthesis Reports', 'vortex-ai-marketplace'); ?></span>
                            <span class="action-description"><?php _e('AI-generated reports and behavioral insights', 'vortex-ai-marketplace'); ?></span>
                        </a>
                        
                        <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-thorius-agents')); ?>" class="thorius-action-button">
                            <span class="dashicons dashicons-groups"></span>
                            <span class="action-title"><?php _e('Manage AI Agents', 'vortex-ai-marketplace'); ?></span>
                            <span class="action-description"><?php _e('Configure AI agent settings and capabilities', 'vortex-ai-marketplace'); ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize Agent Usage Chart
            if (typeof Chart !== 'undefined' && document.getElementById('thorius-agent-chart')) {
                var ctx = document.getElementById('thorius-agent-chart').getContext('2d');
                
                var agentData = <?php echo json_encode($stats['agent_distribution']); ?>;
                var labels = Object.keys(agentData);
                var values = Object.values(agentData);
                var colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'];
                
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: colors,
                            hoverBorderColor: '#ffffff',
                            hoverBorderWidth: 2
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });
            }
            
            // Period selector change handler
            $('#thorius-usage-period').on('change', function() {
                // This would typically trigger an AJAX call to refresh the stats
                // For now, just reload the page with the period parameter
                var period = $(this).val();
                window.location.href = '<?php echo esc_url(admin_url('admin.php?page=vortex-thorius')); ?>&period=' + period;
            });
        });
        </script>
        <?php
    }

    /**
     * Check if required database tables exist
     *
     * @return bool Whether all required tables exist
     */
    private function check_database_tables() {
        global $wpdb;
        
        $required_tables = array(
            $wpdb->prefix . 'vortex_thorius_sessions',
            $wpdb->prefix . 'vortex_thorius_interaction_history',
            $wpdb->prefix . 'vortex_thorius_user_context'
        );
        
        foreach ($required_tables as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            if (!$table_exists) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Render Intelligence page
     */
    public function render_intelligence_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/intelligence-dashboard.php';
    }

    /**
     * Render Synthesis page
     */
    public function render_synthesis_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/synthesis-reports.php';
    }

    /**
     * Render Learning dashboard
     */
    public function render_learning_dashboard() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/learning-dashboard.php';
    }

    /**
     * Render Analytics page
     */
    public function render_analytics_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/analytics-dashboard.php';
    }

    /**
     * Render Agents page
     */
    public function render_agents_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/agents-management.php';
    }

    /**
     * Render Settings page
     */
    public function render_settings_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/thorius-settings.php';
    }

    /**
     * Render Integration Test page
     */
    public function render_integration_test_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/integration-test.php';
    }

    /**
     * AJAX handler for running integration test
     */
    public function ajax_run_integration_test() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_thorius_test_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Verify user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Run integration tests
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-test.php';
        $test = new Vortex_Thorius_Test();
        $results = $test->run_tests();
        
        wp_send_json_success($results);
        exit;
    }

    /**
     * Display admin notices related to Thorius AI
     */
    public function display_admin_notices() {
        // Check if we're on the Thorius admin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'vortex-thorius') === false) {
            return;
        }

        global $wpdb;
        
        // Check if required tables exist
        $tables_to_check = array(
            'vortex_thorius_sessions',
            'vortex_thorius_interaction_history',
            'vortex_thorius_user_context'
        );
        
        $missing_tables = array();
        foreach ($tables_to_check as $table) {
            $table_name = $wpdb->prefix . $table;
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                $missing_tables[] = $table;
            }
        }
        
        if (!empty($missing_tables)) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . __('Thorius AI: Some required database tables are missing:', 'vortex-ai-marketplace') . ' ';
            echo implode(', ', $missing_tables);
            echo '. ' . sprintf(
                __('<a href="%s">Run database update</a> to fix this issue.', 'vortex-ai-marketplace'),
                admin_url('admin.php?page=vortex-settings&tab=advanced')
            ) . '</p>';
            echo '</div>';
        }
    }
} 