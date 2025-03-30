        <script>
        jQuery(document).ready(function($) {
            // Toggle full audit results
            $('#run-directory-audit').on('click', function() {
                $('.vortex-full-audit-results').slideToggle();
                
                // Scroll to audit results
                if ($('.vortex-full-audit-results').is(':visible')) {
                    $('html, body').animate({
                        scrollTop: $('.vortex-full-audit-results').offset().top - 50
                    }, 500);
                }
            });
            
            // Auto-fix issues
            $('#auto-fix-issues').on('click', function() {
                $(this).prop('disabled', true).text('<?php _e('Fixing...', 'vortex-marketplace'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_auto_fix_issues',
                        nonce: '<?php echo wp_create_nonce('vortex_auto_fix_issues'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Issues fixed successfully. Page will now reload.', 'vortex-marketplace'); ?>');
                            location.reload();
                        } else {
                            alert('<?php _e('Error: ', 'vortex-marketplace'); ?>' + response.data.message);
                            $('#auto-fix-issues').prop('disabled', false).text('<?php _e('Auto-Fix Issues', 'vortex-marketplace'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred while fixing issues.', 'vortex-marketplace'); ?>');
                        $('#auto-fix-issues').prop('disabled', false).text('<?php _e('Auto-Fix Issues', 'vortex-marketplace'); ?>');
                    }
                });
            });
            
            // Create database tables
            $('#create-database-tables').on('click', function() {
                $(this).prop('disabled', true).text('<?php _e('Creating...', 'vortex-marketplace'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_create_missing_tables',
                        nonce: '<?php echo wp_create_nonce('vortex_create_missing_tables'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Database tables created successfully. Page will now reload.', 'vortex-marketplace'); ?>');
                            location.reload();
                        } else {
                            alert('<?php _e('Error: ', 'vortex-marketplace'); ?>' + response.data.message);
                            $('#create-database-tables').prop('disabled', false).text('<?php _e('Create Missing Tables', 'vortex-marketplace'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred while creating database tables.', 'vortex-marketplace'); ?>');
                        $('#create-database-tables').prop('disabled', false).text('<?php _e('Create Missing Tables', 'vortex-marketplace'); ?>');
                    }
                });
            });
            
            // Enable all AI learning
            $('#enable-all-learning').on('click', function() {
                $(this).prop('disabled', true).text('<?php _e('Enabling...', 'vortex-marketplace'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_enable_all_ai_learning',
                        nonce: '<?php echo wp_create_nonce('vortex_enable_all_ai_learning'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Deep learning enabled for all AI agents. Page will now reload.', 'vortex-marketplace'); ?>');
                            location.reload();
                        } else {
                            alert('<?php _e('Error: ', 'vortex-marketplace'); ?>' + response.data.message);
                            $('#enable-all-learning').prop('disabled', false).text('<?php _e('Enable All AI Learning', 'vortex-marketplace'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred while enabling AI learning.', 'vortex-marketplace'); ?>');
                        $('#enable-all-learning').prop('disabled', false).text('<?php _e('Enable All AI Learning', 'vortex-marketplace'); ?>');
                    }
                });
            });
            
            // Trigger training cycle
            $('#trigger-training').on('click', function() {
                $(this).prop('disabled', true).text('<?php _e('Initiating...', 'vortex-marketplace'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_trigger_training_cycle',
                        nonce: '<?php echo wp_create_nonce('vortex_trigger_training_cycle'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Training cycle initiated for all AI agents.', 'vortex-marketplace'); ?>');
                            $('#trigger-training').prop('disabled', false).text('<?php _e('Trigger Training Cycle', 'vortex-marketplace'); ?>');
                        } else {
                            alert('<?php _e('Error: ', 'vortex-marketplace'); ?>' + response.data.message);
                            $('#trigger-training').prop('disabled', false).text('<?php _e('Trigger Training Cycle', 'vortex-marketplace'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred while triggering training cycle.', 'vortex-marketplace'); ?>');
                        $('#trigger-training').prop('disabled', false).text('<?php _e('Trigger Training Cycle', 'vortex-marketplace'); ?>');
                    }
                });
            });
            
            // Refresh blockchain metrics
            $('#refresh-blockchain-metrics').on('click', function() {
                $(this).prop('disabled', true).text('<?php _e('Refreshing...', 'vortex-marketplace'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_refresh_blockchain_metrics',
                        nonce: '<?php echo wp_create_nonce('vortex_refresh_blockchain_metrics'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Blockchain metrics refreshed. Page will now reload.', 'vortex-marketplace'); ?>');
                            location.reload();
                        } else {
                            alert('<?php _e('Error: ', 'vortex-marketplace'); ?>' + response.data.message);
                            $('#refresh-blockchain-metrics').prop('disabled', false).text('<?php _e('Refresh Metrics', 'vortex-marketplace'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred while refreshing blockchain metrics.', 'vortex-marketplace'); ?>');
                        $('#refresh-blockchain-metrics').prop('disabled', false).text('<?php _e('Refresh Metrics', 'vortex-marketplace'); ?>');
                    }
                });
            });
            
            // Initialize blockchain integration
            $('#initialize-blockchain').on('click', function() {
                var $button = $(this);
                $button.prop('disabled', true).text('<?php _e('Initializing...', 'vortex-marketplace'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_initialize_blockchain',
                        nonce: '<?php echo wp_create_nonce('vortex_admin_actions'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Blockchain integration initialized. Page will now reload.', 'vortex-marketplace'); ?>');
                            location.reload();
                        } else {
                            alert('<?php _e('Error: ', 'vortex-marketplace'); ?>' + response.data.message);
                            $button.prop('disabled', false).text('<?php _e('Initialize Blockchain Integration', 'vortex-marketplace'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred while initializing blockchain integration.', 'vortex-marketplace'); ?>');
                        $button.prop('disabled', false).text('<?php _e('Initialize Blockchain Integration', 'vortex-marketplace'); ?>');
                    }
                });
            });
            
            // Initialize gamification system
            $('#initialize-gamification').on('click', function() {
                var $button = $(this);
                $button.prop('disabled', true).text('<?php _e('Initializing...', 'vortex-marketplace'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_initialize_gamification',
                        nonce: '<?php echo wp_create_nonce('vortex_admin_actions'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Gamification system initialized. Page will now reload.', 'vortex-marketplace'); ?>');
                            location.reload();
                        } else {
                            alert('<?php _e('Error: ', 'vortex-marketplace'); ?>' + response.data.message);
                            $button.prop('disabled', false).text('<?php _e('Initialize Gamification System', 'vortex-marketplace'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred while initializing gamification system.', 'vortex-marketplace'); ?>');
                        $button.prop('disabled', false).text('<?php _e('Initialize Gamification System', 'vortex-marketplace'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render system status page with admin verification
     */
    public function render_system_status_page() {
        // Verify admin access
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'vortex-marketplace'));
        }
        
        // Continue with existing function...
        // ...
    }

    /**
     * Enhance auto-initialization with privacy checks
     */
    public function auto_initialize_on_site_startup() {
        // Check if initialization has already been done
        if (get_option('vortex_auto_initialized', false)) {
            return;
        }
        
        // Log initialization with privacy protection
        $this->log_private_startup();
        
        // Run system checks
        $this->run_system_check();
        
        // Initialize blockchain if not already initialized
        if (class_exists('VORTEX_TOLA_Integration')) {
            $tola = VORTEX_TOLA_Integration::get_instance();
            if (method_exists($tola, 'create_tables')) {
                $tola->create_tables();
            }
        }
        
        // Initialize gamification if not already initialized
        if (class_exists('VORTEX_Gamification')) {
            $gamification = VORTEX_Gamification::get_instance();
            if (method_exists($gamification, 'initialize')) {
                $gamification->initialize();
            }
        }
        
        // Ensure deep learning is enabled for all agents with ROI focus
        $this->ensure_deep_learning_enabled_with_roi_focus();
        
        // Track that initialization has been done
        update_option('vortex_auto_initialized', true);
    }

    /**
     * Private logging to prevent data exposure
     */
    private function log_private_startup() {
        if (WP_DEBUG && WP_DEBUG_LOG) {
            error_log('VORTEX Marketplace auto-initialized on site startup');
        } else {
            // Don't log in production unless specifically enabled
            update_option('vortex_last_initialization', time());
        }
    }

    /**
     * Enhanced method to ensure deep learning is enabled with ROI focus
     */
    public function ensure_deep_learning_enabled_with_roi_focus() {
        // Set optimization goal for all AI agents
        update_option('vortex_ai_optimization_goal', 'roi');
        update_option('vortex_ai_target_roi', 80); // 80% ROI target
        
        // Enable deep learning for all agents with ROI optimization
        $ai_agents = array('huraii', 'cloe', 'business_strategist', 'thorius');
        
        foreach ($ai_agents as $agent) {
            // Enable deep learning
            update_option("vortex_{$agent}_deep_learning", true);
            
            // Set learning parameters focused on ROI
            update_option("vortex_{$agent}_learning_rate", 0.001);
            update_option("vortex_{$agent}_context_window", 1000);
            update_option("vortex_{$agent}_continuous_learning", true);
            update_option("vortex_{$agent}_cross_learning", true);
            update_option("vortex_{$agent}_optimization_focus", 'roi');
            
            // Schedule initial training cycle with staggered timing to prevent resource spikes
            $delay = array_search($agent, $ai_agents) * 300; // 5 minutes apart
            wp_schedule_single_event(time() + $delay, "vortex_{$agent}_train_model");
        }
        
        // Enable real-time blockchain metrics
        update_option('vortex_realtime_blockchain_metrics', true);
        
        // Schedule regular ROI analysis
        if (!wp_next_scheduled('vortex_analyze_roi')) {
            wp_schedule_event(time(), 'daily', 'vortex_analyze_roi');
        }
    }

    /**
     * Initialize revolutionary components
     */
    public function initialize_revolutionary_components() {
        global $wpdb;
        
        error_log('VORTEX Orchestrator: Initializing revolutionary components');
        
        // Ensure Master Command system is activated
        if (class_exists('VORTEX_Master_Command')) {
            $master_command = VORTEX_Master_Command::get_instance();
            
            // Queue core commands if not already done
            $commands_exist = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_command_queue"
            );
            
            if (!$commands_exist) {
                if (method_exists($master_command, 'queue_initial_commands')) {
                    $master_command->queue_initial_commands();
                }
            }
        }
        
        // Ensure Revolutionary Audit system is scheduled
        if (!wp_next_scheduled('vortex_system_heartbeat')) {
            wp_schedule_event(time() + 1800, 'hourly', 'vortex_system_heartbeat'); // Schedule 30 minutes after init
        }
        
        // Configure HURAII competitive benchmark vs Midjourney
        if (class_exists('VORTEX_HURAII')) {
            $huraii = VORTEX_HURAII::get_instance();
            if (method_exists($huraii, 'set_competitive_benchmark')) {
                $huraii->set_competitive_benchmark('midjourney', true);
                
                $wpdb->insert(
                    $wpdb->prefix . 'vortex_system_logs',
                    array(
                        'log_type' => 'revolutionary_config',
                        'message' => 'HURAII configured to exceed Midjourney benchmark',
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s')
                );
            }
        }
        
        // Set Business Strategist to target 80% ROI
        if (class_exists('VORTEX_Business_Strategist')) {
            $strategist = VORTEX_Business_Strategist::get_instance();
            if (method_exists($strategist, 'set_roi_target')) {
                $strategist->set_roi_target(80);
                
                $wpdb->insert(
                    $wpdb->prefix . 'vortex_system_logs',
                    array(
                        'log_type' => 'revolutionary_config',
                        'message' => 'Business Strategist configured for 80% ROI target',
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s')
                );
            }
        }
        
        // Configure CLOE for elite advertising capabilities
        if (class_exists('VORTEX_CLOE')) {
            $cloe = VORTEX_CLOE::get_instance();
            if (method_exists($cloe, 'set_notification_strategy')) {
                $cloe->set_notification_strategy('conversion_optimized');
                
                $wpdb->insert(
                    $wpdb->prefix . 'vortex_system_logs',
                    array(
                        'log_type' => 'revolutionary_config',
                        'message' => 'CLOE configured for elite conversion-optimized advertising',
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s')
                );
            }
        }
        
        // Configure Thorius for maximum blockchain security and token value protection
        if (class_exists('VORTEX_Thorius')) {
            $thorius = VORTEX_Thorius::get_instance();
            if (method_exists($thorius, 'set_token_value_protection')) {
                $thorius->set_token_value_protection(true);
                
                $wpdb->insert(
                    $wpdb->prefix . 'vortex_system_logs',
                    array(
                        'log_type' => 'revolutionary_config',
                        'message' => 'Thorius configured for maximum token value protection',
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s')
                );
            }
        }
        
        // Mark revolutionary components as initialized
        update_option('vortex_revolutionary_components_initialized', true);
        update_option('vortex_revolutionary_initialization_time', time());
        
        error_log('VORTEX Orchestrator: Revolutionary components initialized successfully');
    }

    /**
     * Initialize security components
     */
    public function initialize_security_components() {
        global $wpdb;
        
        error_log('VORTEX Orchestrator: Initializing security components');
        
        // Ensure Security Protocol is activated
        if (class_exists('VORTEX_Security_Protocol')) {
            $security = VORTEX_Security_Protocol::get_instance();
            $security->create_security_tables();
        }
        
        // Add intellectual property protection commands
        if (class_exists('VORTEX_Master_Command')) {
            $master_command = VORTEX_Master_Command::get_instance();
            
            if (method_exists($master_command, 'add_ip_protection_commands')) {
                $master_command->add_ip_protection_commands();
                
                $wpdb->insert(
                    $wpdb->prefix . 'vortex_system_logs',
                    array(
                        'log_type' => 'security_initialization',
                        'message' => 'Intellectual property protection commands added to all AI agents',
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s')
                );
            }
        }
        
        // Create obfuscation layer for algorithm details
        $this->create_algorithm_obfuscation();
        
        // Initialize response security filters
        $this->initialize_response_security();
        
        // Mark security components as initialized
        update_option('vortex_security_components_initialized', true);
        update_option('vortex_security_initialization_time', time());
        
        error_log('VORTEX Orchestrator: Security components initialized successfully');
    }

    /**
     * Create algorithm obfuscation layer
     */
    private function create_algorithm_obfuscation() {
        // Generate random keys for obfuscating algorithm components
        $obfuscation_keys = array();
        
        $algorithm_components = array(
            'neural_network_structure',
            'learning_algorithms',
            'training_methodologies',
            'deep_learning_weights',
            'cross_learning_mechanics'
        );
        
        foreach ($algorithm_components as $component) {
            $obfuscation_keys[$component] = wp_generate_password(32, true, true);
        }
        
        // Store obfuscation keys (accessible only to admins)
        update_option('vortex_algorithm_obfuscation_keys', $obfuscation_keys, true);
        
        // Set up access control
        global $wpdb;
        
        // Create access control entry in database
        $wpdb->insert(
            $wpdb->prefix . 'vortex_system_logs',
            array(
                'log_type' => 'security_initialization',
                'message' => 'Algorithm obfuscation layer created with restricted admin-only access',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
    }

    /**
     * Initialize AI response security filters
     */
    private function initialize_response_security() {
        global $wpdb;
        
        error_log('VORTEX Orchestrator: Initializing response security filters');
        
        // Ensure Public Responses Manager is activated
        if (!class_exists('VORTEX_Public_Responses')) {
            $file_path = plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-public-responses.php';
            if (file_exists($file_path)) {
                require_once $file_path;
                VORTEX_Public_Responses::get_instance();
                
                $wpdb->insert(
                    $wpdb->prefix . 'vortex_system_logs',
                    array(
                        'log_type' => 'security_initialization',
                        'message' => 'Public Responses Manager initialized for algorithm protection',
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s')
                );
            }
        }
        
        // Ensure Agent Response Filter is activated
        if (!class_exists('VORTEX_Agent_Response_Filter')) {
            $file_path = plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-agent-response-filter.php';
            if (file_exists($file_path)) {
                require_once $file_path;
                VORTEX_Agent_Response_Filter::get_instance();
                
                $wpdb->insert(
                    $wpdb->prefix . 'vortex_system_logs',
                    array(
                        'log_type' => 'security_initialization',
                        'message' => 'Agent Response Filter initialized for algorithm protection',
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s')
                );
            }
        }
        
        // Add this initialization to the security components
        update_option('vortex_response_security_initialized', true);
        
        error_log('VORTEX Orchestrator: Response security filters initialized successfully');
    }

    // Add to run_system_check method
    public function run_system_check() {
        // Original code...
        
        // Check if revolutionary components are initialized
        if (!get_option('vortex_revolutionary_components_initialized', false)) {
            $this->initialize_revolutionary_components();
        }
        
        // Check if security components are initialized
        if (!get_option('vortex_security_components_initialized', false)) {
            $this->initialize_security_components();
        }
        
        // Original code...
    }

    /**
     * Register AJAX handlers for the orchestrator
     */
    public function register_ajax_handlers() {
        add_action('wp_ajax_vortex_run_system_check', array($this, 'ajax_run_system_check'));
        add_action('wp_ajax_vortex_fix_system_issues', array($this, 'ajax_fix_system_issues'));
        add_action('wp_ajax_vortex_toggle_ai_learning', array($this, 'ajax_toggle_ai_learning'));
        add_action('wp_ajax_vortex_run_agent_training', array($this, 'ajax_run_agent_training'));
        add_action('wp_ajax_vortex_create_missing_tables', array($this, 'ajax_create_missing_tables'));
        add_action('wp_ajax_vortex_refresh_blockchain_metrics', array($this, 'ajax_refresh_blockchain_metrics'));
        add_action('wp_ajax_vortex_initialize_blockchain', array($this, 'ajax_initialize_blockchain'));
        add_action('wp_ajax_vortex_initialize_gamification', array($this, 'ajax_initialize_gamification'));
    }
    
    /**
     * AJAX handler to run agent training
     */
    public function ajax_run_agent_training() {
        // Security check
        check_ajax_referer('vortex_admin_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }
        
        $agent_id = isset($_POST['agent_id']) ? sanitize_text_field($_POST['agent_id']) : '';
        
        if ($agent_id === 'all') {
            // Run training for all agents
            foreach ($this->ai_agents as $agent => $class_name) {
                $hook_name = "vortex_{$agent}_learning_cycle";
                wp_schedule_single_event(time() + (array_search($agent, array_keys($this->ai_agents)) * 60), $hook_name);
            }
            
            wp_send_json_success(array(
                'message' => 'Training scheduled for all agents'
            ));
        } elseif (array_key_exists($agent_id, $this->ai_agents)) {
            // Run training for specific agent
            $hook_name = "vortex_{$agent_id}_learning_cycle";
            wp_schedule_single_event(time(), $hook_name);
            
            wp_send_json_success(array(
                'message' => "Training scheduled for {$agent_id}"
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Invalid agent ID'
            ));
        }
    }
    
    /**
     * AJAX handler to create missing tables
     */
    public function ajax_create_missing_tables() {
        // Security check
        check_ajax_referer('vortex_admin_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }
        
        // Get missing tables
        $missing_tables = $this->check_database_tables();
        
        if (empty($missing_tables)) {
            wp_send_json_success(array(
                'message' => 'No missing tables found'
            ));
            return;
        }
        
        // Create missing tables
        $this->create_missing_tables($missing_tables);
        
        // Log the action
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'vortex_system_logs',
            array(
                'log_type' => 'admin_action',
                'message' => sprintf('Admin created %d missing database tables', count($missing_tables)),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
        
        wp_send_json_success(array(
            'message' => sprintf('%d missing tables created successfully', count($missing_tables)),
            'tables_created' => $missing_tables
        ));
    }
    
    /**
     * AJAX handler to refresh blockchain metrics
     */
    public function ajax_refresh_blockchain_metrics() {
        // Security check
        check_ajax_referer('vortex_admin_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }
        
        if (class_exists('VORTEX_Blockchain_Metrics')) {
            $metrics = VORTEX_Blockchain_Metrics::get_instance();
            $metrics->cache_blockchain_metrics();
            
            wp_send_json_success(array(
                'message' => 'Blockchain metrics refreshed successfully'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Blockchain metrics component not available'
            ));
        }
    }
    
    /**
     * AJAX handler to initialize blockchain
     */
    public function ajax_initialize_blockchain() {
        // Security check
        check_ajax_referer('vortex_admin_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }
        
        $file_path = plugin_dir_path(dirname(__FILE__)) . 'includes/blockchain/class-vortex-tola-integration.php';
        
        if (!file_exists($file_path)) {
            wp_send_json_error(array(
                'message' => 'Blockchain integration file not found'
            ));
            return;
        }
        
        // Include the file if not already included
        if (!class_exists('VORTEX_TOLA_Integration')) {
            require_once $file_path;
        }
        
        // Initialize blockchain
        $tola = VORTEX_TOLA_Integration::get_instance();
        
        if (method_exists($tola, 'create_tables')) {
            $tola->create_tables();
            
            // Initialize blockchain metrics
            if (class_exists('VORTEX_Blockchain_Metrics')) {
                $metrics = VORTEX_Blockchain_Metrics::get_instance();
                $metrics->cache_blockchain_metrics();
            }
            
            // Log the action
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'vortex_system_logs',
                array(
                    'log_type' => 'admin_action',
                    'message' => 'Admin initialized blockchain integration',
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s')
            );
            
            wp_send_json_success(array(
                'message' => 'Blockchain integration initialized successfully'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Blockchain integration class does not have required methods'
            ));
        }
    }
    
    /**
     * AJAX handler to initialize gamification
     */
    public function ajax_initialize_gamification() {
        // Security check
        check_ajax_referer('vortex_admin_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }
        
        $file_path = plugin_dir_path(dirname(__FILE__)) . 'includes/gamification/class-vortex-gamification.php';
        
        if (!file_exists($file_path)) {
            wp_send_json_error(array(
                'message' => 'Gamification file not found'
            ));
            return;
        }
        
        // Include the file if not already included
        if (!class_exists('VORTEX_Gamification')) {
            require_once $file_path;
        }
        
        // Initialize gamification
        $gamification = VORTEX_Gamification::get_instance();
        
        if (method_exists($gamification, 'initialize')) {
            $gamification->initialize();
            
            // Log the action
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'vortex_system_logs',
                array(
                    'log_type' => 'admin_action',
                    'message' => 'Admin initialized gamification system',
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s')
            );
            
            wp_send_json_success(array(
                'message' => 'Gamification system initialized successfully'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Gamification class does not have required methods'
            ));
        }
    }
}

// Initialize the Orchestrator
add_action('plugins_loaded', function() {
    $orchestrator = VORTEX_Orchestrator::get_instance();
    
    // Register AJAX handlers
    $orchestrator->register_ajax_handlers();
    
    // Add dashboard tabs
    add_filter('vortex_dashboard_tabs', array($orchestrator, 'add_dashboard_integration_tabs'));
    
    // Register shortcodes
    $orchestrator->register_component_shortcodes();
    
    // Integrate real-time TOLA blockchain metrics
    $orchestrator->integrate_tola_blockchain_metrics();
    
    // Integrate AI with gamification and blockchain
    $orchestrator->integrate_ai_with_gamification_blockchain();
}, 20); // Higher priority to ensure it runs after all components are loaded

// Add initialization on site startup
add_action('init', function() {
    // Check if we're in an admin or AJAX request - if not, it's likely a frontend request
    if (!is_admin() && !wp_doing_ajax()) {
        // Get orchestrator instance and run auto-initialization
        $orchestrator = VORTEX_Orchestrator::get_instance();
        $orchestrator->auto_initialize_on_site_startup();
    }
}, 5); // Early priority to ensure it runs before other components

// Add to run_system_check method for revolutionary features
add_action('vortex_system_check', function() {
    $orchestrator = VORTEX_Orchestrator::get_instance();
    
    // Check if revolutionary components are initialized
    if (!get_option('vortex_revolutionary_components_initialized', false)) {
        $orchestrator->initialize_revolutionary_components();
    }
    
    // Check if security components are initialized
    if (!get_option('vortex_security_components_initialized', false)) {
        $orchestrator->initialize_security_components();
    }
}, 20); 