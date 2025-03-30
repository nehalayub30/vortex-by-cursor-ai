/**
 * Display the settings page
 */
public function display_settings_page() {
    // Load the settings header with tabs
    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/settings/settings-header.php';
    
    // Get current tab
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
    
    // Load the appropriate tab content
    switch ($current_tab) {
        case 'artwork':
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/settings/artwork-settings.php';
            break;
        case 'artists':
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/settings/artists-settings.php';
            break;
        case 'payments':
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/settings/payments-settings.php';
            break;
        case 'blockchain':
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/settings/blockchain-settings.php';
            break;
        case 'ai':
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/settings/ai-settings.php';
            break;
        case 'general':
        case 'advanced':
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/settings/general-settings.php';
            break;
        default:
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/settings/general-settings.php';
            break;
    }
}

/**
 * Register the Tools page and subpages
 */
public function add_tools_menu() {
    // Add main Tools page
    $parent_slug = 'vortex-tools';
    add_menu_page(
        __('Vortex Tools', 'vortex-ai-marketplace'),
        __('Vortex Tools', 'vortex-ai-marketplace'),
        'manage_options',
        $parent_slug,
        array($this, 'display_tools_page'),
        'dashicons-admin-tools',
        30
    );
    
    // Add Import Settings subpage
    add_submenu_page(
        $parent_slug,
        __('Import Settings', 'vortex-ai-marketplace'),
        __('Import Settings', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex-import-settings',
        array($this, 'display_import_settings_page')
    );
    
    // Add Export Settings subpage
    add_submenu_page(
        $parent_slug,
        __('Export Settings', 'vortex-ai-marketplace'),
        __('Export Settings', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex-export-settings',
        array($this, 'display_export_settings_page')
    );
    
    // Add Maintenance subpage
    add_submenu_page(
        $parent_slug,
        __('Maintenance', 'vortex-ai-marketplace'),
        __('Maintenance', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex-maintenance',
        array($this, 'display_maintenance_page')
    );
    
    // Add Blockchain Tools subpage
    add_submenu_page(
        $parent_slug,
        __('Blockchain Tools', 'vortex-ai-marketplace'),
        __('Blockchain Tools', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex-blockchain-tools',
        array($this, 'display_blockchain_tools_page')
    );
}

/**
 * Display the Tools main page
 */
public function display_tools_page() {
    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/tools/tools-main.php';
}

/**
 * Display the Import Settings page
 */
public function display_import_settings_page() {
    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/tools/import-settings.php';
}

/**
 * Display the Export Settings page
 */
public function display_export_settings_page() {
    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/tools/export-settings.php';
}

/**
 * Display the Maintenance page
 */
public function display_maintenance_page() {
    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/tools/maintenance-settings.php';
}

/**
 * Display the Blockchain Tools page
 */
public function display_blockchain_tools_page() {
    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/tools/blockchain-settings.php';
}

/**
 * Register AJAX handlers for maintenance actions
 */
public function register_maintenance_ajax_handlers() {
    add_action('wp_ajax_vortex_maintenance_ajax', array($this, 'handle_maintenance_ajax'));
}

/**
 * Handle AJAX maintenance actions
 */
public function handle_maintenance_ajax() {
    // Check nonce
    if (!check_ajax_referer('vortex_maintenance_ajax_nonce', 'nonce', false)) {
        wp_send_json_error(array(
            'message' => __('Security verification failed. Please refresh the page and try again.', 'vortex-ai-marketplace')
        ));
    }
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('You do not have sufficient permissions to perform this action.', 'vortex-ai-marketplace')
        ));
    }
    
    // Get the requested action
    $action = isset($_POST['maintenance_action']) ? sanitize_text_field($_POST['maintenance_action']) : '';
    
    // Process the action
    switch ($action) {
        case 'clear_cache':
            $result = vortex_clear_plugin_cache();
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Cache cleared successfully!', 'vortex-ai-marketplace'),
                    'button_text' => __('Clear Again', 'vortex-ai-marketplace'),
                    'health_score' => vortex_calculate_health_score() // Recalculate health score
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Failed to clear cache. Please try again.', 'vortex-ai-marketplace')
                ));
            }
            break;
            
        // Handle other actions similarly...
            
        default:
            wp_send_json_error(array(
                'message' => __('Unknown action requested.', 'vortex-ai-marketplace')
            ));
            break;
    }
}

/**
 * Register download handler for settings export
 */
public function register_download_handler() {
    add_action('admin_post_vortex_download_settings', array($this, 'download_settings'));
}

/**
 * Process settings download
 */
public function download_settings() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'vortex-ai-marketplace'));
    }
    
    // Get the export data from transient
    $export_data = get_transient('vortex_export_data');
    
    if (empty($export_data)) {
        wp_die(__('Export data not found or has expired. Please try exporting again.', 'vortex-ai-marketplace'));
    }
    
    // Set filename
    $filename = 'vortex-marketplace-settings-' . date('Y-m-d') . '.json';
    
    // Set headers for download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($export_data));
    header('Pragma: no-cache');
    
    // Output the data
    echo $export_data;
    
    // Clear the transient
    delete_transient('vortex_export_data');
    
    // End execution
    exit;
}

/**
 * Add the AI Dashboard page
 */
public function add_ai_dashboard_page() {
    add_submenu_page(
        'vortex-settings',
        __('AI Dashboard', 'vortex-ai-marketplace'),
        __('AI Dashboard', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex-ai-dashboard',
        array($this, 'display_ai_dashboard_page')
    );
}

/**
 * Display the AI Dashboard page
 */
public function display_ai_dashboard_page() {
    // Check if we need to run a cleanup
    if (isset($_POST['action']) && $_POST['action'] === 'run_cleanup') {
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'vortex_run_cleanup')) {
            wp_die(__('Security check failed', 'vortex-ai-marketplace'));
        }
        
        // Run cleanup
        $usage_tracker = Vortex_AI_Marketplace::get_instance()->usage_tracker;
        if (method_exists($usage_tracker, 'cleanup_old_logs')) {
            $usage_tracker->cleanup_old_logs();
            
            // Add admin notice
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>';
                esc_html_e('AI usage logs cleanup completed successfully.', 'vortex-ai-marketplace');
                echo '</p></div>';
            });
        }
    }
    
    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ai-dashboard.php';
}

/**
 * Display shortcode documentation
 */
public function display_shortcode_docs() {
    ?>
    <div class="wrap">
        <h1><?php _e('Vortex AI Shortcodes', 'vortex-ai-marketplace'); ?></h1>
        
        <div class="card">
            <h2><?php _e('Business Strategy Shortcodes', 'vortex-ai-marketplace'); ?></h2>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Shortcode', 'vortex-ai-marketplace'); ?></th>
                        <th><?php _e('Description', 'vortex-ai-marketplace'); ?></th>
                        <th><?php _e('Example', 'vortex-ai-marketplace'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[vortex_business_idea_form]</code></td>
                        <td><?php _e('Displays the business idea intake form that generates a personalized business plan.', 'vortex-ai-marketplace'); ?></td>
                        <td><code>[vortex_business_idea_form class="my-custom-class"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[vortex_business_strategy]</code></td>
                        <td><?php _e('Displays the business strategy generator.', 'vortex-ai-marketplace'); ?></td>
                        <td><code>[vortex_business_strategy industry="art" focus="digital"]</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h2><?php _e('AI Creation Shortcodes', 'vortex-ai-marketplace'); ?></h2>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Shortcode', 'vortex-ai-marketplace'); ?></th>
                        <th><?php _e('Description', 'vortex-ai-marketplace'); ?></th>
                        <th><?php _e('Example', 'vortex-ai-marketplace'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[vortex_huraii_artwork]</code></td>
                        <td><?php _e('Displays the HURAII artwork generation form.', 'vortex-ai-marketplace'); ?></td>
                        <td><code>[vortex_huraii_artwork style="abstract"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[vortex_cloe_analysis]</code></td>
                        <td><?php _e('Displays the CLOE market analysis form.', 'vortex-ai-marketplace'); ?></td>
                        <td><code>[vortex_cloe_analysis market="art"]</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
} 