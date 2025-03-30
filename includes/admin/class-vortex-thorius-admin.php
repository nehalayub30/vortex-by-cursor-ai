/**
 * Register plugin admin menu pages
 */
public function register_admin_pages() {
    // Main menu
    add_menu_page(
        __('Thorius AI', 'vortex-ai-marketplace'),
        __('Thorius AI', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex-thorius',
        array($this, 'render_dashboard_page'),
        'dashicons-superhero',
        30
    );
    
    // Submenu pages
    add_submenu_page(
        'vortex-thorius',
        __('Dashboard', 'vortex-ai-marketplace'),
        __('Dashboard', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex-thorius',
        array($this, 'render_dashboard_page')
    );
    
    // Agents Settings submenu
    add_submenu_page(
        'vortex-thorius',
        __('AI Agents', 'vortex-ai-marketplace'),
        __('AI Agents', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex-thorius-agents',
        array($this, 'render_agents_page')
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
    
    // Synthesis Reports submenu
    add_submenu_page(
        'vortex-thorius',
        __('Synthesis Reports', 'vortex-ai-marketplace'),
        __('Synthesis Reports', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex-thorius-synthesis',
        array($this, 'render_synthesis_page')
    );
    
    // Intelligence Dashboard submenu
    add_submenu_page(
        'vortex-thorius',
        __('Intelligence Dashboard', 'vortex-ai-marketplace'),
        __('Intelligence', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex-thorius-intelligence',
        array($this, 'render_intelligence_page')
    );
    
    // Integration Test submenu
    add_submenu_page(
        'vortex-thorius',
        __('Integration Test', 'vortex-ai-marketplace'),
        __('System Test', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex-thorius-test',
        array($this, 'render_integration_test_page')
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
}

/**
 * Render Synthesis Reports page
 */
public function render_synthesis_page() {
    require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'admin/partials/synthesis-report.php';
}

/**
 * Render Intelligence Dashboard page
 */
public function render_intelligence_page() {
    require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'admin/partials/intelligence-dashboard.php';
}

/**
 * Register admin dashboard widgets
 */
public function register_dashboard_widgets() {
    wp_add_dashboard_widget(
        'thorius_ai_overview',
        __('Thorius AI Overview', 'vortex-ai-marketplace'),
        array($this, 'render_overview_widget')
    );
    
    wp_add_dashboard_widget(
        'thorius_ai_insights',
        __('Thorius AI Insights', 'vortex-ai-marketplace'),
        array($this, 'render_insights_widget')
    );
}

/**
 * Render overview widget
 */
public function render_overview_widget() {
    // Get analytics data
    require_once plugin_dir_path(dirname(__FILE__)) . 'class-vortex-thorius-analytics.php';
    $analytics = new Vortex_Thorius_Analytics();
    $stats = $analytics->get_overview_stats();
    
    // Display widget content
    echo '<div class="thorius-widget-overview">';
    
    // Display usage stats
    echo '<div class="stat-container">';
    echo '<div class="stat-item">';
    echo '<span class="stat-value">' . number_format($stats['usage']['total_queries']) . '</span>';
    echo '<span class="stat-label">' . __('AI Queries', 'vortex-ai-marketplace') . '</span>';
    echo '</div>';
    
    echo '<div class="stat-item">';
    echo '<span class="stat-value">' . number_format($stats['usage']['unique_users']) . '</span>';
    echo '<span class="stat-label">' . __('Unique Users', 'vortex-ai-marketplace') . '</span>';
    echo '</div>';
    
    echo '<div class="stat-item">';
    echo '<span class="stat-value">' . number_format($stats['usage']['tokens_used']) . '</span>';
    echo '<span class="stat-label">' . __('Tokens Used', 'vortex-ai-marketplace') . '</span>';
    echo '</div>';
    echo '</div>';
    
    // Display agent distribution
    echo '<div class="agent-distribution">';
    echo '<h4>' . __('Agent Usage', 'vortex-ai-marketplace') . '</h4>';
    
    $agents = $stats['agents'];
    $total = array_sum(array_values($agents));
    
    foreach ($agents as $agent => $count) {
        $percentage = $total > 0 ? round(($count / $total) * 100) : 0;
        $agent_label = ucfirst($agent);
        
        echo '<div class="agent-bar">';
        echo '<div class="agent-label">' . $agent_label . '</div>';
        echo '<div class="agent-progress">';
        echo '<div class="agent-progress-bar" style="width: ' . $percentage . '%;">' . $percentage . '%</div>';
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    
    // Quick actions
    echo '<div class="thorius-quick-actions">';
    echo '<a href="' . admin_url('admin.php?page=vortex-thorius-intelligence') . '" class="button">' . __('Open Intelligence Dashboard', 'vortex-ai-marketplace') . '</a>';
    echo '</div>';
    
    echo '</div>';
}

/**
 * Render insights widget
 */
public function render_insights_widget() {
    // Get synthesis data
    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-vortex-thorius-synthesis-reports.php';
    $synthesis = new Vortex_Thorius_Synthesis_Reports();
    $insights = $synthesis->get_quick_insights();
    
    echo '<div class="thorius-widget-insights">';
    
    if (!empty($insights['trends'])) {
        echo '<div class="insights-section">';
        echo '<h4>' . __('Key Trends', 'vortex-ai-marketplace') . '</h4>';
        echo '<ul class="insights-list">';
        foreach ($insights['trends'] as $trend) {
            echo '<li>' . $trend . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    
    if (!empty($insights['recommendations'])) {
        echo '<div class="insights-section">';
        echo '<h4>' . __('Recommendations', 'vortex-ai-marketplace') . '</h4>';
        echo '<ul class="insights-list">';
        foreach ($insights['recommendations'] as $recommendation) {
            echo '<li>' . $recommendation . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    
    // View full report link
    echo '<div class="thorius-insights-actions">';
    echo '<a href="' . admin_url('admin.php?page=vortex-thorius-synthesis') . '" class="button">' . __('View Full Synthesis Report', 'vortex-ai-marketplace') . '</a>';
    echo '</div>';
    
    echo '</div>';
}

/**
 * Register settings
 */
public function register_settings() {
    // Register settings sections
    add_settings_section(
        'vortex_thorius_general_section',
        __('General Settings', 'vortex-ai-marketplace'),
        array($this, 'render_general_section'),
        'vortex-thorius-settings'
    );
    
    add_settings_section(
        'vortex_thorius_agents_section',
        __('AI Agents Settings', 'vortex-ai-marketplace'),
        array($this, 'render_agents_section'),
        'vortex-thorius-settings'
    );
    
    add_settings_section(
        'vortex_thorius_analytics_section',
        __('Analytics Settings', 'vortex-ai-marketplace'),
        array($this, 'render_analytics_section'),
        'vortex-thorius-settings'
    );
    
    // NEW: Add Intelligence settings section
    add_settings_section(
        'vortex_thorius_intelligence_section',
        __('Intelligence Settings', 'vortex-ai-marketplace'),
        array($this, 'render_intelligence_section'),
        'vortex-thorius-settings'
    );
    
    // Register general settings
    register_setting('vortex-thorius-settings', 'vortex_thorius_enable_concierge');
    register_setting('vortex-thorius-settings', 'vortex_thorius_enable_voice');
    register_setting('vortex-thorius-settings', 'vortex_thorius_default_language');
    
    // Register API settings
    register_setting('vortex-thorius-settings', 'vortex_thorius_openai_key', array(
        'sanitize_callback' => array($this, 'encrypt_api_key')
    ));
    register_setting('vortex-thorius-settings', 'vortex_thorius_stability_key', array(
        'sanitize_callback' => array($this, 'encrypt_api_key')
    ));
    
    // Register agents settings
    register_setting('vortex-thorius-settings', 'vortex_thorius_enable_cloe');
    register_setting('vortex-thorius-settings', 'vortex_thorius_enable_huraii');
    register_setting('vortex-thorius-settings', 'vortex_thorius_enable_strategist');
    
    // Register analytics settings
    register_setting('vortex-thorius-settings', 'vortex_thorius_enable_analytics');
    register_setting('vortex-thorius-settings', 'vortex_thorius_anonymize_ips');
    register_setting('vortex-thorius-settings', 'vortex_thorius_data_retention', array(
        'default' => 90
    ));
    
    // NEW: Register intelligence settings
    register_setting('vortex-thorius-settings', 'vortex_thorius_enable_synthesis');
    register_setting('vortex-thorius-settings', 'vortex_thorius_enable_intelligence');
    register_setting('vortex-thorius-settings', 'vortex_thorius_weekly_email');
    register_setting('vortex-thorius-settings', 'vortex_thorius_weekly_email_recipients');
}

/**
 * Render intelligence settings section
 */
public function render_intelligence_section() {
    echo '<p>' . __('Configure settings for the Thorius Intelligence and Synthesis Reports.', 'vortex-ai-marketplace') . '</p>';
    
    // Enable Synthesis Reports
    echo '<div class="thorius-setting">';
    echo '<label for="vortex_thorius_enable_synthesis">';
    echo '<input type="checkbox" id="vortex_thorius_enable_synthesis" name="vortex_thorius_enable_synthesis" value="1" ' . checked(get_option('vortex_thorius_enable_synthesis'), 1, false) . '>';
    echo __('Enable Synthesis Reports', 'vortex-ai-marketplace');
    echo '</label>';
    echo '<p class="description">' . __('Generate comprehensive behavioral analysis reports.', 'vortex-ai-marketplace') . '</p>';
    echo '</div>';
    
    // Enable Intelligence Dashboard
    echo '<div class="thorius-setting">';
    echo '<label for="vortex_thorius_enable_intelligence">';
    echo '<input type="checkbox" id="vortex_thorius_enable_intelligence" name="vortex_thorius_enable_intelligence" value="1" ' . checked(get_option('vortex_thorius_enable_intelligence'), 1, false) . '>';
    echo __('Enable Intelligence Dashboard', 'vortex-ai-marketplace');
    echo '</label>';
    echo '<p class="description">' . __('Allow administrators to query platform data through natural language.', 'vortex-ai-marketplace') . '</p>';
    echo '</div>';
    
    // Weekly Email Reports
    echo '<div class="thorius-setting">';
    echo '<label for="vortex_thorius_weekly_email">';
    echo '<input type="checkbox" id="vortex_thorius_weekly_email" name="vortex_thorius_weekly_email" value="1" ' . checked(get_option('vortex_thorius_weekly_email'), 1, false) . '>';
    echo __('Send Weekly Email Reports', 'vortex-ai-marketplace');
    echo '</label>';
    echo '<p class="description">' . __('Automatically email weekly synthesis reports to administrators.', 'vortex-ai-marketplace') . '</p>';
    echo '</div>';
    
    // Email Recipients
    echo '<div class="thorius-setting">';
    echo '<label for="vortex_thorius_weekly_email_recipients">' . __('Weekly Report Recipients:', 'vortex-ai-marketplace') . '</label>';
    echo '<input type="text" id="vortex_thorius_weekly_email_recipients" name="vortex_thorius_weekly_email_recipients" class="regular-text" value="' . esc_attr(get_option('vortex_thorius_weekly_email_recipients')) . '">';
    echo '<p class="description">' . __('Comma-separated list of email addresses. Leave blank to send to all administrators.', 'vortex-ai-marketplace') . '</p>';
    echo '</div>';
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
    require_once plugin_dir_path(dirname(__FILE__)) . 'class-vortex-thorius-test.php';
    $test = new Vortex_Thorius_Test();
    $results = $test->run_tests();
    
    wp_send_json_success($results);
    exit;
}

/**
 * Render Integration Test page
 */
public function render_integration_test_page() {
    require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'admin/partials/integration-test.php';
}

/**
 * Enqueue admin styles and scripts
 */
public function enqueue_admin_assets($hook) {
    // Main admin styles
    wp_enqueue_style(
        'vortex-thorius-admin-css',
        plugin_dir_url(dirname(dirname(__FILE__))) . 'admin/css/thorius-admin.css',
        array(),
        VORTEX_THORIUS_VERSION
    );
    
    // Main admin script
    wp_enqueue_script(
        'vortex-thorius-admin-js',
        plugin_dir_url(dirname(dirname(__FILE__))) . 'admin/js/thorius-admin.js',
        array('jquery'),
        VORTEX_THORIUS_VERSION,
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
    
    // Synthesis page specific assets
    if (strpos($hook, 'vortex-thorius-synthesis') !== false) {
        wp_enqueue_style(
            'vortex-thorius-synthesis-css',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'admin/css/thorius-synthesis.css',
            array(),
            VORTEX_THORIUS_VERSION
        );
        
        wp_enqueue_script(
            'vortex-thorius-synthesis-js',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'admin/js/thorius-synthesis.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-datepicker'),
            VORTEX_THORIUS_VERSION,
            true
        );
        
        // Add Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
            array(),
            '3.7.1',
            true
        );
    }
    
    // Intelligence dashboard specific assets
    if (strpos($hook, 'vortex-thorius-intelligence') !== false) {
        wp_enqueue_style(
            'vortex-thorius-intelligence-css',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'admin/css/thorius-intelligence.css',
            array(),
            VORTEX_THORIUS_VERSION
        );
        
        wp_enqueue_script(
            'vortex-thorius-intelligence-js',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'admin/js/thorius-intelligence.js',
            array('jquery', 'chartjs'),
            VORTEX_THORIUS_VERSION,
            true
        );
    }
}

/**
 * Render learning system status dashboard
 */
public function render_learning_dashboard() {
    // Get instance of main plugin
    $thorius = Vortex_Thorius::get_instance();
    
    // Get learning system status
    $status = $thorius->learning_system->get_learning_system_status();
    
    // Render dashboard
    echo '<div class="wrap thorius-learning-dashboard">';
    echo '<h1>' . __('Thorius Deep Learning Status', 'vortex-ai-marketplace') . '</h1>';
    
    // Overview metrics
    echo '<div class="thorius-stats-cards">';
    
    echo '<div class="thorius-stat-card">';
    echo '<div class="thorius-stat-header">' . __('Total Interactions', 'vortex-ai-marketplace') . '</div>';
    echo '<div class="thorius-stat-value">' . number_format($status['total_interactions']) . '</div>';
    echo '</div>';
    
    echo '<div class="thorius-stat-card">';
    echo '<div class="thorius-stat-header">' . __('User Feedback', 'vortex-ai-marketplace') . '</div>';
    echo '<div class="thorius-stat-value">' . number_format($status['total_feedback']) . '</div>';
    echo '</div>';
    
    echo '<div class="thorius-stat-card">';
    echo '<div class="thorius-stat-header">' . __('Neural Adaptations', 'vortex-ai-marketplace') . '</div>';
    echo '<div class="thorius-stat-value">' . number_format($status['adaptations']) . '</div>';
    echo '</div>';
    
    echo '</div>'; // End stat cards
    
    // Agent performance metrics
    echo '<h2>' . __('AI Agent Learning Progress', 'vortex-ai-marketplace') . '</h2>';
    
    echo '<div class="thorius-agent-metrics">';
    foreach ($status['agents'] as $agent => $data) {
        echo '<div class="thorius-agent-card">';
        echo '<div class="thorius-agent-header">' . strtoupper($agent) . '</div>';
        
        echo '<div class="thorius-agent-metrics-grid">';
        
        foreach ($data['metrics'] as $metric => $value) {
            if ($metric === 'improvements') continue;
            
            echo '<div class="thorius-agent-metric">';
            echo '<div class="thorius-metric-name">' . ucfirst(str_replace('_', ' ', $metric)) . '</div>';
            echo '<div class="thorius-metric-value">' . number_format($value, 2) . '</div>';
            
            // Show trend indicator if improvements data available
            if (isset($data['metrics']['improvements'][$metric])) {
                $improvement = $data['metrics']['improvements'][$metric];
                $class = $improvement > 0 ? 'positive' : ($improvement < 0 ? 'negative' : 'neutral');
                echo '<div class="thorius-metric-trend ' . $class . '">' . sprintf('%+.2f', $improvement) . '</div>';
            }
            
            echo '</div>'; // End metric
        }
        
        echo '</div>'; // End metrics grid
        
        // Last adaptation info
        if ($data['last_adaptation']) {
            echo '<div class="thorius-agent-last-adaptation">';
            echo '<strong>' . __('Last Adaptation:', 'vortex-ai-marketplace') . '</strong> ';
            echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($data['last_adaptation']));
            echo '</div>';
        }
        
        // Action buttons
        echo '<div class="thorius-agent-actions">';
        echo '<button class="button thorius-trigger-adaptation" data-agent="' . esc_attr($agent) . '">' . __('Trigger Adaptation', 'vortex-ai-marketplace') . '</button>';
        echo '<button class="button thorius-reset-learning" data-agent="' . esc_attr($agent) . '">' . __('Reset Learning', 'vortex-ai-marketplace') . '</button>';
        echo '</div>';
        
        echo '</div>'; // End agent card
    }
    echo '</div>'; // End agent metrics
    
    // Recent adaptations
    echo '<h2>' . __('Recent Neural Adaptations', 'vortex-ai-marketplace') . '</h2>';
    
    $recent_adaptations = $this->get_recent_adaptations(10);
    
    if (!empty($recent_adaptations)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Agent', 'vortex-ai-marketplace') . '</th>';
        echo '<th>' . __('Type', 'vortex-ai-marketplace') . '</th>';
        echo '<th>' . __('Date', 'vortex-ai-marketplace') . '</th>';
        echo '<th>' . __('Training Data', 'vortex-ai-marketplace') . '</th>';
        echo '<th>' . __('Status', 'vortex-ai-marketplace') . '</th>';
        echo '<th>' . __('Impact', 'vortex-ai-marketplace') . '</th>';
        echo '</tr></thead>';
        
        echo '<tbody>';
        foreach ($recent_adaptations as $adaptation) {
            echo '<tr>';
            echo '<td>' . strtoupper($adaptation['agent']) . '</td>';
            echo '<td>' . ucfirst(str_replace('_', ' ', $adaptation['type'])) . '</td>';
            echo '<td>' . date_i18n(get_option('date_format'), strtotime($adaptation['date'])) . '</td>';
            echo '<td>' . number_format($adaptation['training_size']) . ' examples</td>';
            
            $status_class = $adaptation['status'] === 'completed' ? 'success' : ($adaptation['status'] === 'failed' ? 'error' : 'pending');
            echo '<td><span class="thorius-status-badge ' . $status_class . '">' . ucfirst($adaptation['status']) . '</span></td>';
            
            $impact = isset($adaptation['impact']) ? sprintf('%+.2f%%', $adaptation['impact'] * 100) : 'N/A';
            $impact_class = isset($adaptation['impact']) ? ($adaptation['impact'] > 0 ? 'positive' : ($adaptation['impact'] < 0 ? 'negative' : 'neutral')) : '';
            echo '<td class="' . $impact_class . '">' . $impact . '</td>';
            
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>' . __('No adaptations have been performed yet.', 'vortex-ai-marketplace') . '</p>';
    }
    
    // Global learning settings
    echo '<h2>' . __('Adaptation Settings', 'vortex-ai-marketplace') . '</h2>';
    
    $thresholds = $thorius->learning_system->get_default_adaptation_thresholds();
    
    echo '<form id="thorius-learning-settings-form" method="post" action="options.php">';
    settings_fields('thorius_learning_settings');
    
    echo '<table class="form-table">';
    
    echo '<tr>';
    echo '<th scope="row"><label for="interaction_threshold">' . __('Minimum Interactions', 'vortex-ai-marketplace') . '</label></th>';
    echo '<td><input type="number" id="interaction_threshold" name="thorius_adaptation_thresholds[interaction_count]" value="' . esc_attr($thresholds['interaction_count']) . '" min="10" max="1000" step="10">';
    echo '<p class="description">' . __('Minimum number of interactions before adaptation is considered', 'vortex-ai-marketplace') . '</p></td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<th scope="row"><label for="feedback_quality">' . __('Feedback Quality Threshold', 'vortex-ai-marketplace') . '</label></th>';
    echo '<td><input type="number" id="feedback_quality" name="thorius_adaptation_thresholds[feedback_quality]" value="' . esc_attr($thresholds['feedback_quality']) . '" min="0.5" max="0.95" step="0.05">';
    echo '<p class="description">' . __('Minimum positive feedback ratio to prevent adaptation', 'vortex-ai-marketplace') . '</p></td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<th scope="row"><label for="confidence_improvement">' . __('Confidence Improvement', 'vortex-ai-marketplace') . '</label></th>';
    echo '<td><input type="number" id="confidence_improvement" name="thorius_adaptation_thresholds[confidence_improvement]" value="' . esc_attr($thresholds['confidence_improvement']) . '" min="0.05" max="0.5" step="0.05">';
    echo '<p class="description">' . __('Minimum confidence improvement required for adaptation to be considered successful', 'vortex-ai-marketplace') . '</p></td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<th scope="row"><label for="consistency_threshold">' . __('Consistency Threshold', 'vortex-ai-marketplace') . '</label></th>';
    echo '<td><input type="number" id="consistency_threshold" name="thorius_adaptation_thresholds[consistency_threshold]" value="' . esc_attr($thresholds['consistency_threshold']) . '" min="0.5" max="0.95" step="0.05">';
    echo '<p class="description">' . __('Minimum consistency score required across similar queries', 'vortex-ai-marketplace') . '</p></td>';
    echo '</tr>';
    
    echo '</table>';
    
    submit_button(__('Save Settings', 'vortex-ai-marketplace'));
    echo '</form>';
    
    echo '</div>'; // End wrap
}

/**
 * Constructor
 */
public function __construct() {
    // Add admin menu pages
    add_action('admin_menu', array($this, 'register_admin_pages'));
    
    // Register settings
    add_action('admin_init', array($this, 'register_settings'));
    
    // Add meta boxes
    add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
    
    // Register AJAX handlers
    add_action('wp_ajax_vortex_thorius_generate_report', array($this, 'ajax_generate_report'));
    add_action('wp_ajax_vortex_thorius_admin_query', array($this, 'ajax_process_admin_query'));
    add_action('wp_ajax_vortex_thorius_run_integration_test', array($this, 'ajax_run_integration_test'));
    
    // Add admin notices
    add_action('admin_notices', array($this, 'display_admin_notices'));
    
    // Add dashboard widgets
    add_action('wp_dashboard_setup', array($this, 'register_dashboard_widgets'));
    
    // Enqueue admin assets
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
} 