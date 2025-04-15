/**
 * Initialize the class and set its properties.
 */
public function __construct() {
    // Process form submissions
    add_action('admin_init', array($this, 'process_agent_config_form'));
    
    // Add admin menu
    add_action('admin_menu', array($this, 'add_admin_menu'));
    
    // Enqueue admin scripts and styles
    add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
}

/**
 * Register the stylesheets for the admin area.
 */
public function enqueue_styles($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'vortex-') !== false) {
        wp_enqueue_style('vortex-admin-style', plugin_dir_url(__FILE__) . 'css/vortex-admin.css', array(), VORTEX_VERSION, 'all');
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css', array(), '5.15.3', 'all');
    }
}

/**
 * Register the JavaScript for the admin area.
 */
public function enqueue_scripts($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'vortex-') !== false) {
        wp_enqueue_script('chart-js', plugin_dir_url(__FILE__) . '../assets/lib/chart.min.js', array('jquery'), '3.7.1', true);
        wp_enqueue_script('vortex-admin-script', plugin_dir_url(__FILE__) . 'js/vortex-admin.js', array('jquery'), VORTEX_VERSION, true);
        
        // Localize the script with our data
        wp_localize_script('vortex-admin-script', 'vortex_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_nonce'),
            'plugin_url' => VORTEX_PLUGIN_URL,
            'version' => VORTEX_VERSION
        ));
    }
}

/**
 * Register the menu pages.
 */
public function add_admin_menu() {
    // Main menu item
    add_menu_page(
        'VORTEX Marketplace',
        'VORTEX',
        'manage_options',
        'vortex-dashboard',
        array($this, 'display_dashboard_page'),
        'dashicons-store',
        25
    );
    
    // Dashboard submenu
    add_submenu_page(
        'vortex-dashboard',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'vortex-dashboard',
        array($this, 'display_dashboard_page')
    );
    
    // AI Agents submenu
    add_submenu_page(
        'vortex-dashboard',
        'AI Agents',
        'AI Agents',
        'manage_options',
        'vortex-agents',
        array($this, 'display_agent_dashboard')
    );
    
    // AI Insights submenu
    add_submenu_page(
        'vortex-dashboard',
        'AI Insights',
        'AI Insights',
        'manage_options',
        'vortex-insights',
        array($this, 'display_insights_page')
    );
    
    // Blockchain Metrics submenu
    add_submenu_page(
        'vortex-dashboard',
        'Blockchain Metrics',
        'Blockchain Metrics',
        'manage_options',
        'vortex-blockchain',
        array($this, 'display_blockchain_page')
    );
    
    // DAO Governance submenu
    add_submenu_page(
        'vortex-dashboard',
        'DAO Governance',
        'DAO Governance',
        'manage_options',
        'vortex-dao',
        array($this, 'display_dao_page')
    );
    
    // Settings submenu
    add_submenu_page(
        'vortex-dashboard',
        'Settings',
        'Settings',
        'manage_options',
        'vortex-settings',
        array($this, 'display_settings_page')
    );
}

/**
 * Display the dashboard page.
 */
public function display_dashboard_page() {
    include_once VORTEX_PLUGIN_DIR . 'admin/partials/vortex-dashboard.php';
}

/**
 * Display the agent dashboard page.
 */
public function display_agent_dashboard() {
    include_once VORTEX_PLUGIN_DIR . 'admin/partials/vortex-agent-dashboard.php';
}

/**
 * Display the insights page.
 */
public function display_insights_page() {
    include_once VORTEX_PLUGIN_DIR . 'admin/partials/vortex-insights.php';
}

/**
 * Display the blockchain metrics page.
 */
public function display_blockchain_page() {
    include_once VORTEX_PLUGIN_DIR . 'admin/partials/vortex-blockchain-page.php';
}

/**
 * Display the DAO governance page.
 */
public function display_dao_page() {
    include_once VORTEX_PLUGIN_DIR . 'admin/partials/vortex-dao-page.php';
}

/**
 * Display the settings page.
 */
public function display_settings_page() {
    include_once VORTEX_PLUGIN_DIR . 'admin/partials/vortex-settings.php';
}

/**
 * Get the icon class for an agent.
 */
public function get_agent_icon($agent) {
    $icons = array(
        'huraii' => 'fas fa-paint-brush',
        'cloe' => 'fas fa-user-friends',
        'business_strategist' => 'fas fa-chart-line',
        'thorius' => 'fas fa-shield-alt'
    );
    
    return isset($icons[$agent]) ? $icons[$agent] : 'fas fa-robot';
}

/**
 * Get the color for a health score.
 */
public function get_health_color($health_score) {
    if ($health_score >= 80) {
        return '#36b37e'; // Green for good health
    } else if ($health_score >= 60) {
        return '#ff9f43'; // Orange for moderate health
    } else {
        return '#ff6b6b'; // Red for poor health
    }
}

/**
 * Process agent configuration form.
 */
public function process_agent_config_form() {
    if (!isset($_POST['vortex_agent_config_nonce']) || !wp_verify_nonce($_POST['vortex_agent_config_nonce'], 'vortex_agent_config')) {
        return;
    }
    
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Update learning frequency
    if (isset($_POST['learning_frequency'])) {
        $learning_frequency = sanitize_text_field($_POST['learning_frequency']);
        update_option('vortex_learning_frequency', $learning_frequency);
    }
    
    // Update learning start time
    if (isset($_POST['learning_start_time'])) {
        $learning_start_time = sanitize_text_field($_POST['learning_start_time']);
        update_option('vortex_learning_start_time', $learning_start_time);
    }
    
    // Update insight retention days
    if (isset($_POST['insight_retention_days'])) {
        $insight_retention_days = intval($_POST['insight_retention_days']);
        update_option('vortex_insight_retention_days', $insight_retention_days);
    }
    
    // Update deep learning day
    if (isset($_POST['deep_learning_day'])) {
        $deep_learning_day = intval($_POST['deep_learning_day']);
        update_option('vortex_deep_learning_day', $deep_learning_day);
    }
    
    // Update public insights setting
    $enable_public_insights = isset($_POST['enable_public_insights']) ? '1' : '0';
    update_option('vortex_enable_public_insights', $enable_public_insights);
    
    // Update agent configuration
    $agent_config = array(
        'huraii' => isset($_POST['agent_config']['huraii']),
        'cloe' => isset($_POST['agent_config']['cloe']),
        'business_strategist' => isset($_POST['agent_config']['business_strategist']),
        'thorius' => isset($_POST['agent_config']['thorius'])
    );
    update_option('vortex_agent_config', $agent_config);
    
    // Update scheduled events based on new configuration
    $orchestrator = VORTEX_Orchestrator::get_instance();
    $orchestrator->update_scheduled_events();
    
    // Set admin notice
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Agent configuration settings saved successfully.', 'vortex'); ?></p>
        </div>
        <?php
    });
} 