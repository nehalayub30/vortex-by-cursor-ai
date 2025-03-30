    /**
     * Check shortcodes
     */
    private static function check_shortcodes() {
        // List of expected shortcodes
        $expected_shortcodes = array(
            'vortex_artwork_gallery',
            'vortex_artist_profile',
            'vortex_marketplace_stats',
            'vortex_blockchain_stats',
            'vortex_gamification_leaderboard',
            'vortex_user_dashboard',
            'vortex_ai_insights'
        );
        
        $missing_shortcodes = array();
        
        // Check if shortcode class exists
        if (!class_exists('VORTEX_Shortcodes')) {
            error_log('Vortex Audit: VORTEX_Shortcodes class not found');
            return;
        }
        
        // Check shortcode templates
        $templates_dir = plugin_dir_path(__FILE__) . 'templates/shortcodes/';
        foreach ($expected_shortcodes as $shortcode) {
            $template_file = $templates_dir . str_replace('vortex_', '', $shortcode) . '.php';
            if (!file_exists($template_file)) {
                $missing_shortcodes[] = $shortcode;
            }
        }
        
        if (!empty($missing_shortcodes)) {
            error_log('Vortex Audit: Missing shortcode templates: ' . implode(', ', $missing_shortcodes));
        }
    }
    
    /**
     * Check AI agents
     */
    private static function check_ai_agents() {
        // List of expected AI agent classes
        $expected_agents = array(
            'VORTEX_CLOE' => 'class-vortex-cloe.php',
            'VORTEX_HURAII' => 'class-vortex-huraii.php',
            'VORTEX_Business_Strategist' => 'class-vortex-business-strategist.php',
            'VORTEX_Thorius' => 'class-vortex-thorius.php',
            'VORTEX_Support_Chat' => 'class-vortex-support-chat.php'
        );
        
        $missing_agents = array();
        
        // Check if agent classes exist
        foreach ($expected_agents as $class => $file) {
            $file_path = plugin_dir_path(__FILE__) . 'includes/ai/' . $file;
            if (!file_exists($file_path)) {
                $missing_agents[] = $class . ' (' . $file . ')';
            } else {
                // Check if the class is properly defined in the file
                $file_content = file_get_contents($file_path);
                if (strpos($file_content, 'class ' . $class) === false) {
                    $missing_agents[] = $class . ' (class not defined in file)';
                }
            }
        }
        
        if (!empty($missing_agents)) {
            error_log('Vortex Audit: Missing AI agent classes: ' . implode(', ', $missing_agents));
        }
        
        // Check required AI agent methods
        $required_methods = array(
            'enable_deep_learning',
            'set_learning_rate',
            'enable_continuous_learning',
            'set_context_window',
            'enable_cross_learning'
        );
        
        foreach ($expected_agents as $class => $file) {
            $file_path = plugin_dir_path(__FILE__) . 'includes/ai/' . $file;
            if (file_exists($file_path)) {
                $file_content = file_get_contents($file_path);
                $missing_methods = array();
                
                foreach ($required_methods as $method) {
                    if (strpos($file_content, 'function ' . $method) === false) {
                        $missing_methods[] = $method;
                    }
                }
                
                if (!empty($missing_methods)) {
                    error_log('Vortex Audit: ' . $class . ' is missing methods: ' . implode(', ', $missing_methods));
                }
            }
        }
    }
    
    /**
     * Check deep learning
     */
    private static function check_deep_learning() {
        // Check if deep learning class exists
        $dl_class_file = plugin_dir_path(__FILE__) . 'includes/deep-learning/class-vortex-deep-learning.php';
        if (!file_exists($dl_class_file)) {
            error_log('Vortex Audit: Deep Learning class file not found');
            return;
        }
        
        // Required deep learning methods
        $required_methods = array(
            'initialize',
            'train_model',
            'save_model_weights',
            'load_model_weights',
            'predict',
            'collect_training_data',
            'set_learning_rate',
            'get_training_metrics'
        );
        
        $file_content = file_get_contents($dl_class_file);
        $missing_methods = array();
        
        foreach ($required_methods as $method) {
            if (strpos($file_content, 'function ' . $method) === false) {
                $missing_methods[] = $method;
            }
        }
        
        if (!empty($missing_methods)) {
            error_log('Vortex Audit: Deep Learning class is missing methods: ' . implode(', ', $missing_methods));
        }
        
        // Check model directories
        $model_dir = plugin_dir_path(__FILE__) . 'includes/deep-learning/models';
        if (!is_dir($model_dir)) {
            wp_mkdir_p($model_dir);
            error_log('Vortex Audit: Created missing deep learning models directory');
        }
        
        // Check data directories
        $data_dir = plugin_dir_path(__FILE__) . 'includes/deep-learning/data';
        if (!is_dir($data_dir)) {
            wp_mkdir_p($data_dir);
            error_log('Vortex Audit: Created missing deep learning data directory');
        }
    }
    
    /**
     * Check blockchain
     */
    private static function check_blockchain() {
        // Check if blockchain integration class exists
        $blockchain_class_file = plugin_dir_path(__FILE__) . 'includes/blockchain/class-vortex-tola-integration.php';
        if (!file_exists($blockchain_class_file)) {
            error_log('Vortex Audit: TOLA blockchain integration class file not found');
            return;
        }
        
        // Check if blockchain metrics class exists
        $metrics_class_file = plugin_dir_path(__FILE__) . 'includes/blockchain/class-vortex-blockchain-metrics.php';
        if (!file_exists($metrics_class_file)) {
            error_log('Vortex Audit: Blockchain metrics class file not found');
            return;
        }
        
        // Required blockchain metrics methods
        $required_metrics_methods = array(
            'get_metrics',
            'generate_metrics',
            'update_metrics',
            'get_public_blockchain_stats'
        );
        
        $file_content = file_get_contents($metrics_class_file);
        $missing_methods = array();
        
        foreach ($required_metrics_methods as $method) {
            if (strpos($file_content, 'function ' . $method) === false) {
                $missing_methods[] = $method;
            }
        }
        
        if (!empty($missing_methods)) {
            error_log('Vortex Audit: Blockchain metrics class is missing methods: ' . implode(', ', $missing_methods));
        }
        
        // Required blockchain integration methods
        $required_integration_methods = array(
            'create_transaction',
            'tokenize_artwork',
            'create_smart_contract',
            'verify_transaction',
            'get_artwork_blockchain_data',
            'process_transaction_queue'
        );
        
        $file_content = file_get_contents($blockchain_class_file);
        $missing_methods = array();
        
        foreach ($required_integration_methods as $method) {
            if (strpos($file_content, 'function ' . $method) === false) {
                $missing_methods[] = $method;
            }
        }
        
        if (!empty($missing_methods)) {
            error_log('Vortex Audit: TOLA integration class is missing methods: ' . implode(', ', $missing_methods));
        }
    }
    
    /**
     * Check gamification
     */
    private static function check_gamification() {
        // Check if gamification class exists
        $gamification_class_file = plugin_dir_path(__FILE__) . 'includes/gamification/class-vortex-gamification.php';
        if (!file_exists($gamification_class_file)) {
            error_log('Vortex Audit: Gamification class file not found');
            return;
        }
        
        // Required gamification methods
        $required_methods = array(
            'add_points',
            'get_user_level',
            'get_user_badges',
            'award_badge',
            'notify_achievement',
            'get_leaderboard',
            'track_user_action',
            'get_next_level_requirement',
            'get_level_title'
        );
        
        $file_content = file_get_contents($gamification_class_file);
        $missing_methods = array();
        
        foreach ($required_methods as $method) {
            if (strpos($file_content, 'function ' . $method) === false) {
                $missing_methods[] = $method;
            }
        }
        
        if (!empty($missing_methods)) {
            error_log('Vortex Audit: Gamification class is missing methods: ' . implode(', ', $missing_methods));
        }
        
        // Check activity hooks
        $activities = array(
            'artwork_upload',
            'artwork_sold',
            'artwork_viewed',
            'artwork_liked',
            'artwork_commented',
            'artwork_shared',
            'profile_completed',
            'login_streak',
            'first_purchase',
            'blockchain_transaction'
        );
        
        $missing_hooks = array();
        
        foreach ($activities as $activity) {
            if (strpos($file_content, "add_action('vortex_" . $activity . "'") === false &&
                strpos($file_content, "add_action(\"vortex_" . $activity . "\"") === false) {
                $missing_hooks[] = 'vortex_' . $activity;
            }
        }
        
        if (!empty($missing_hooks)) {
            error_log('Vortex Audit: Gamification class is missing hooks for: ' . implode(', ', $missing_hooks));
        }
    }
    
    /**
     * Check database tables
     */
    private static function check_database_tables() {
        global $wpdb;
        
        $required_tables = array(
            $wpdb->prefix . 'vortex_artworks',
            $wpdb->prefix . 'vortex_artwork_views',
            $wpdb->prefix . 'vortex_artwork_likes',
            $wpdb->prefix . 'vortex_user_points',
            $wpdb->prefix . 'vortex_user_badges',
            $wpdb->prefix . 'vortex_user_activities',
            $wpdb->prefix . 'vortex_tola_transactions',
            $wpdb->prefix . 'vortex_token_swaps',
            $wpdb->prefix . 'vortex_smart_contracts',
            $wpdb->prefix . 'vortex_ai_insights',
            $wpdb->prefix . 'vortex_deep_learning_metrics'
        );
        
        $missing_tables = array();
        
        // Get all tables in the database
        $existing_tables = $wpdb->get_col("SHOW TABLES");
        
        foreach ($required_tables as $table) {
            if (!in_array($table, $existing_tables)) {
                $missing_tables[] = $table;
            }
        }
        
        if (!empty($missing_tables)) {
            error_log('Vortex Audit: Missing database tables: ' . implode(', ', $missing_tables));
        }
    }
}

// Add admin page for plugin audit
add_action('admin_menu', function() {
    add_submenu_page(
        'vortex-marketplace',
        'Plugin Audit',
        'Plugin Audit',
        'manage_options',
        'vortex-plugin-audit',
        'vortex_display_audit_page'
    );
});

/**
 * Display audit page
 */
function vortex_display_audit_page() {
    // Run audit when requested
    if (isset($_POST['vortex_run_audit']) && check_admin_referer('vortex_run_audit_nonce')) {
        VORTEX_Plugin_Audit::run_audit();
        $audit_complete = true;
    }
    
    // Get recent log entries
    $log_file = ABSPATH . 'wp-content/debug.log';
    $log_entries = array();
    
    if (file_exists($log_file)) {
        $log_content = file_get_contents($log_file);
        $pattern = '/\[.*?\] Vortex Audit: (.*?)(?=\[|$)/';
        
        if (preg_match_all($pattern, $log_content, $matches)) {
            $log_entries = $matches[1];
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="notice notice-info">
            <p>The audit tool will check if all required components of the Vortex AI Marketplace plugin are properly installed and configured.</p>
        </div>
        
        <form method="post">
            <?php wp_nonce_field('vortex_run_audit_nonce'); ?>
            <p><input type="submit" name="vortex_run_audit" class="button button-primary" value="Run Plugin Audit"></p>
        </form>
        
        <?php if (isset($audit_complete)): ?>
        <div class="notice notice-success">
            <p>Audit completed! Check below for audit results.</p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($log_entries)): ?>
        <div class="card">
            <h2>Audit Results</h2>
            <ul class="vortex-audit-results">
                <?php foreach ($log_entries as $entry): ?>
                <li><?php echo esc_html(trim($entry)); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Plugin Components</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Component</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>CLOE AI Agent</td>
                        <td><?php echo file_exists(plugin_dir_path(__FILE__) . 'includes/ai/class-vortex-cloe.php') ? '<span class="dashicons dashicons-yes" style="color:green;"></span> Installed' : '<span class="dashicons dashicons-no" style="color:red;"></span> Missing'; ?></td>
                    </tr>
                    <tr>
                        <td>HURAII AI Agent</td>
                        <td><?php echo file_exists(plugin_dir_path(__FILE__) . 'includes/ai/class-vortex-huraii.php') ? '<span class="dashicons dashicons-yes" style="color:green;"></span> Installed' : '<span class="dashicons dashicons-no" style="color:red;"></span> Missing'; ?></td>
                    </tr>
                    <tr>
                        <td>Business Strategist AI Agent</td>
                        <td><?php echo file_exists(plugin_dir_path(__FILE__) . 'includes/ai/class-vortex-business-strategist.php') ? '<span class="dashicons dashicons-yes" style="color:green;"></span> Installed' : '<span class="dashicons dashicons-no" style="color:red;"></span> Missing'; ?></td>
                    </tr>
                    <tr>
                        <td>Thorius AI Agent</td>
                        <td><?php echo file_exists(plugin_dir_path(__FILE__) . 'includes/ai/class-vortex-thorius.php') ? '<span class="dashicons dashicons-yes" style="color:green;"></span> Installed' : '<span class="dashicons dashicons-no" style="color:red;"></span> Missing'; ?></td>
                    </tr>
                    <tr>
                        <td>Deep Learning System</td>
                        <td><?php echo file_exists(plugin_dir_path(__FILE__) . 'includes/deep-learning/class-vortex-deep-learning.php') ? '<span class="dashicons dashicons-yes" style="color:green;"></span> Installed' : '<span class="dashicons dashicons-no" style="color:red;"></span> Missing'; ?></td>
                    </tr>
                    <tr>
                        <td>TOLA Blockchain Integration</td>
                        <td><?php echo file_exists(plugin_dir_path(__FILE__) . 'includes/blockchain/class-vortex-tola-integration.php') ? '<span class="dashicons dashicons-yes" style="color:green;"></span> Installed' : '<span class="dashicons dashicons-no" style="color:red;"></span> Missing'; ?></td>
                    </tr>
                    <tr>
                        <td>Blockchain Metrics</td>
                        <td><?php echo file_exists(plugin_dir_path(__FILE__) . 'includes/blockchain/class-vortex-blockchain-metrics.php') ? '<span class="dashicons dashicons-yes" style="color:green;"></span> Installed' : '<span class="dashicons dashicons-no" style="color:red;"></span> Missing'; ?></td>
                    </tr>
                    <tr>
                        <td>Gamification System</td>
                        <td><?php echo file_exists(plugin_dir_path(__FILE__) . 'includes/gamification/class-vortex-gamification.php') ? '<span class="dashicons dashicons-yes" style="color:green;"></span> Installed' : '<span class="dashicons dashicons-no" style="color:red;"></span> Missing'; ?></td>
                    </tr>
                    <tr>
                        <td>Security System</td>
                        <td><?php echo file_exists(plugin_dir_path(__FILE__) . 'security/class-vortex-security.php') ? '<span class="dashicons dashicons-yes" style="color:green;"></span> Installed' : '<span class="dashicons dashicons-no" style="color:red;"></span> Missing'; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
} 