            .vortex-agent-cards {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .vortex-agent-card {
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                padding: 20px;
                transition: transform 0.2s ease;
            }
            
            .vortex-agent-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            
            .vortex-agent-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            
            .vortex-agent-header h3 {
                margin: 0;
            }
            
            .vortex-agent-status {
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: bold;
                text-transform: uppercase;
            }
            
            .vortex-agent-status.active {
                background-color: #edfaef;
                color: #46b450;
            }
            
            .vortex-agent-status.disabled {
                background-color: #f9e2e2;
                color: #dc3232;
            }
            
            .vortex-agent-status.stalled {
                background-color: #fcf9e8;
                color: #ffba00;
            }
            
            .vortex-agent-status.initializing {
                background-color: #e8f0f9;
                color: #4a6cf7;
            }
            
            .vortex-agent-status.reactivated {
                background-color: #f0e8f9;
                color: #8c42f4;
            }
            
            .vortex-agent-stat {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #f3f3f3;
            }
            
            .vortex-agent-stat .label {
                font-weight: 500;
            }
            
            .vortex-agent-responsibilities {
                margin-top: 10px;
            }
            
            .vortex-responsibility-tags {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                margin-top: 5px;
            }
            
            .vortex-tag {
                background: #f1f1f1;
                color: #333;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 11px;
            }
            
            .vortex-agent-actions {
                margin-top: 15px;
                text-align: center;
            }
            
            .vortex-system-logs {
                margin-top: 20px;
            }
            
            #ignite-all-agents {
                background: linear-gradient(90deg, #4a6cf7, #f44a83);
                color: white;
                font-weight: bold;
                padding: 10px 20px;
                height: auto;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 1px;
                border: none;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
            }
            
            #ignite-all-agents:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            }
            
            .vortex-loading-indicator {
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 3px solid rgba(255,255,255,0.3);
                border-radius: 50%;
                border-top-color: #fff;
                animation: vortex-spin 1s ease-in-out infinite;
                margin-right: 10px;
                vertical-align: middle;
            }
            
            @keyframes vortex-spin {
                to { transform: rotate(360deg); }
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Ignite all agents
            $('#ignite-all-agents').on('click', function() {
                if (confirm('<?php esc_html_e('Are you sure you want to ignite all AI agents? This will start deep learning for all four AI agents simultaneously.', 'vortex-marketplace'); ?>')) {
                    $(this).prop('disabled', true).html('<span class="vortex-loading-indicator"></span> <?php esc_html_e('Igniting...', 'vortex-marketplace'); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'vortex_ignite_all_agents',
                            nonce: '<?php echo wp_create_nonce('vortex_ignite_all_agents'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('<?php esc_html_e('All AI agents have been successfully ignited! Continuous deep learning is now active for all agents.', 'vortex-marketplace'); ?>');
                                location.reload();
                            } else {
                                alert('<?php esc_html_e('Error: ', 'vortex-marketplace'); ?>' + response.data.message);
                                $('#ignite-all-agents').prop('disabled', false).text('<?php esc_html_e('IGNITE ALL AI AGENTS', 'vortex-marketplace'); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php esc_html_e('An error occurred while igniting the agents.', 'vortex-marketplace'); ?>');
                            $('#ignite-all-agents').prop('disabled', false).text('<?php esc_html_e('IGNITE ALL AI AGENTS', 'vortex-marketplace'); ?>');
                        }
                    });
                }
            });
            
            // Refresh status
            $('#refresh-status').on('click', function() {
                location.reload();
            });
            
            // Force training cycle
            $('.force-training').on('click', function() {
                var agentName = $(this).data('agent');
                var $button = $(this);
                
                $button.prop('disabled', true).html('<span class="vortex-loading-indicator"></span> <?php esc_html_e('Initiating...', 'vortex-marketplace'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_get_agent_status',
                        nonce: '<?php echo wp_create_nonce('vortex_get_agent_status'); ?>',
                        agent: agentName,
                        force_training: true
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php esc_html_e('Training cycle initiated for ', 'vortex-marketplace'); ?>' + agentName);
                            $button.prop('disabled', false).text('<?php esc_html_e('Force Training Cycle', 'vortex-marketplace'); ?>');
                            
                            // Update status
                            var $agentCard = $('.vortex-agent-card[data-agent="' + agentName + '"]');
                            $agentCard.find('.vortex-agent-status')
                                .removeClass('disabled stalled initializing')
                                .addClass('active')
                                .text('Active');
                        } else {
                            alert('<?php esc_html_e('Error: ', 'vortex-marketplace'); ?>' + response.data.message);
                            $button.prop('disabled', false).text('<?php esc_html_e('Force Training Cycle', 'vortex-marketplace'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php esc_html_e('An error occurred while initiating training cycle.', 'vortex-marketplace'); ?>');
                        $button.prop('disabled', false).text('<?php esc_html_e('Force Training Cycle', 'vortex-marketplace'); ?>');
                    }
                });
            });
            
            // Auto-refresh status every 60 seconds
            setInterval(function() {
                $('.vortex-agent-card').each(function() {
                    var agentName = $(this).data('agent');
                    var $card = $(this);
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'vortex_get_agent_status',
                            nonce: '<?php echo wp_create_nonce('vortex_get_agent_status'); ?>',
                            agent: agentName
                        },
                        success: function(response) {
                            if (response.success) {
                                var agent = response.data.agent;
                                
                                // Update status
                                $card.find('.vortex-agent-status')
                                    .removeClass('active disabled stalled initializing reactivated')
                                    .addClass(agent.learning_status)
                                    .text(agent.learning_status.charAt(0).toUpperCase() + agent.learning_status.slice(1));
                                
                                // Update stats
                                $card.find('.vortex-agent-stat:eq(0) .value').text(agent.examples_processed);
                                $card.find('.vortex-agent-stat:eq(1) .value').text(agent.insights_generated);
                                
                                // Update last training
                                if (agent.last_training) {
                                    $card.find('.vortex-agent-stat:eq(2) .value').text(agent.last_training_ago + ' ago');
                                }
                            }
                        }
                    });
                });
            }, 60000);
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for igniting all agents
     */
    public function ajax_ignite_all_agents() {
        check_ajax_referer('vortex_ignite_all_agents', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'vortex-marketplace')));
            return;
        }
        
        // Call the ignite system function
        if (class_exists('VORTEX_Launch_Coordinator')) {
            $coordinator = VORTEX_Launch_Coordinator::get_instance();
            $coordinator->ignite_system();
            
            wp_send_json_success(array('message' => __('All AI agents have been successfully ignited!', 'vortex-marketplace')));
        } else {
            wp_send_json_error(array('message' => __('Launch Coordinator not found.', 'vortex-marketplace')));
        }
    }
    
    /**
     * AJAX handler for getting agent status
     */
    public function ajax_get_agent_status() {
        check_ajax_referer('vortex_get_agent_status', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'vortex-marketplace')));
            return;
        }
        
        $agent_name = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : '';
        $force_training = isset($_POST['force_training']) ? (bool) $_POST['force_training'] : false;
        
        if (empty($agent_name)) {
            wp_send_json_error(array('message' => __('Agent name is required.', 'vortex-marketplace')));
            return;
        }
        
        global $wpdb;
        
        // Get agent status from database
        $agent = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_agent_performance WHERE agent_name = %s",
            $agent_name
        ));
        
        if (!$agent) {
            wp_send_json_error(array('message' => __('Agent not found.', 'vortex-marketplace')));
            return;
        }
        
        // Force training if requested
        if ($force_training) {
            $agent_classes = array(
                'HURAII' => 'VORTEX_HURAII',
                'CLOE' => 'VORTEX_CLOE',
                'Business_Strategist' => 'VORTEX_Business_Strategist',
                'Thorius' => 'VORTEX_Thorius'
            );
            
            $class_name = isset($agent_classes[$agent_name]) ? $agent_classes[$agent_name] : '';
            
            if (!empty($class_name) && class_exists($class_name)) {
                // Schedule training
                wp_schedule_single_event(time() + 30, 'vortex_' . strtolower($agent_name) . '_train_model');
                
                // Update status
                $wpdb->update(
                    $wpdb->prefix . 'vortex_agent_performance',
                    array(
                        'learning_status' => 'active',
                        'updated_at' => current_time('mysql')
                    ),
                    array('agent_name' => $agent_name),
                    array('%s', '%s'),
                    array('%s')
                );
                
                // Log forced training
                $wpdb->insert(
                    $wpdb->prefix . 'vortex_system_logs',
                    array(
                        'log_type' => 'manual_training',
                        'message' => sprintf('Training manually initiated for AI Agent %s by admin', $agent_name),
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s')
                );
                
                // Update agent for response
                $agent->learning_status = 'active';
            }
        }
        
        // Calculate time ago for last training
        $last_training_ago = '';
        if (!empty($agent->last_training)) {
            $last_training_ago = human_time_diff(strtotime($agent->last_training), current_time('timestamp'));
        }
        
        wp_send_json_success(array(
            'agent' => array(
                'name' => $agent->agent_name,
                'examples_processed' => number_format($agent->examples_processed),
                'insights_generated' => number_format($agent->insights_generated),
                'learning_status' => $agent->learning_status,
                'last_training' => $agent->last_training,
                'last_training_ago' => $last_training_ago
            )
        ));
    }
}

// Initialize the admin page
add_action('plugins_loaded', function() {
    new VORTEX_Admin_System_Status();
}); 