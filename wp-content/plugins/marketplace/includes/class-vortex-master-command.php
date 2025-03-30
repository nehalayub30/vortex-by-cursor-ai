<?php

class VORTEX_Master_Command {
    private static $instance = null;

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_admin_init'));
        add_action('wp_ajax_vortex_issue_command', array($this, 'ajax_issue_command'));
    }

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function add_admin_menu() {
        add_menu_page(
            'VORTEX Command Center',
            'VORTEX Command Center',
            'manage_options',
            'vortex-command-center',
            array($this, 'render_command_center'),
            'dashicons-admin-generic',
            6
        );
    }

    public function handle_admin_init() {
        if (isset($_GET['page']) && $_GET['page'] == 'vortex-command-center') {
            wp_enqueue_style('vortex-command-center-styles', plugins_url('css/vortex-command-center.css', __FILE__));
            wp_enqueue_script('vortex-command-center-scripts', plugins_url('js/vortex-command-center.js', __FILE__), array('jquery'), null, true);
        }
    }

    public function render_command_center() {
        $latest_responses = get_option('vortex_agent_latest_responses', array());
        $recent_commands = get_option('vortex_command_history', array());

        ?>
        <div class="wrap">
            <h1>VORTEX Command Center</h1>
            
            <div class="vortex-command-center">
                <div class="vortex-command-issue">
                    <h2><?php esc_html_e('Issue a New Command', 'vortex-marketplace'); ?></h2>
                    
                    <form class="vortex-command-form">
                        <div class="vortex-form-row">
                            <label for="command-target"><?php esc_html_e('Target', 'vortex-marketplace'); ?></label>
                            <select id="command-target" name="target">
                                <option value="all"><?php esc_html_e('All Agents', 'vortex-marketplace'); ?></option>
                                <?php
                                $agents = get_option('vortex_agents', array());
                                foreach ($agents as $agent_name => $agent_data):
                                ?>
                                <option value="<?php echo esc_attr($agent_name); ?>"><?php echo esc_html($agent_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="vortex-form-row">
                            <label for="command-type"><?php esc_html_e('Type', 'vortex-marketplace'); ?></label>
                            <select id="command-type" name="type">
                                <option value="directive"><?php esc_html_e('Directive', 'vortex-marketplace'); ?></option>
                                <option value="reinforcement"><?php esc_html_e('Reinforcement', 'vortex-marketplace'); ?></option>
                            </select>
                        </div>
                        <div class="vortex-form-row">
                            <label for="command-priority"><?php esc_html_e('Priority', 'vortex-marketplace'); ?></label>
                            <input type="number" id="command-priority" name="priority" min="1" max="10" value="5">
                        </div>
                        <div class="vortex-form-row">
                            <label for="command-text"><?php esc_html_e('Command', 'vortex-marketplace'); ?></label>
                            <textarea id="command-text" name="command" rows="3" placeholder="<?php esc_attr_e('Enter command text here...', 'vortex-marketplace'); ?>"></textarea>
                        </div>
                        <div class="vortex-form-row">
                            <button id="issue-command"><?php esc_html_e('Issue Command', 'vortex-marketplace'); ?></button>
                        </div>
                    </form>
                </div>
                
                <div class="vortex-agent-cards">
                    <?php foreach ($latest_responses as $agent_name => $latest_response): ?>
                    <div class="vortex-agent-card">
                        <div class="vortex-agent-header">
                            <h3><?php echo esc_html($agent_name); ?></h3>
                            <span class="vortex-agent-status <?php echo esc_attr($latest_response->status); ?>">
                                <?php echo esc_html($latest_response->status); ?>
                            </span>
                        </div>
                        
                        <div class="vortex-agent-stat">
                            <span class="label"><?php esc_html_e('Last Response:', 'vortex-marketplace'); ?></span>
                            <span class="value">
                                <?php echo esc_html($latest_response->responded_at); ?>
                            </span>
                        </div>
                        
                        <div class="vortex-agent-stat">
                            <span class="label"><?php esc_html_e('Response Time:', 'vortex-marketplace'); ?></span>
                            <span class="value">
                                <?php echo esc_html(human_time_diff(strtotime($latest_response->responded_at), current_time('timestamp'))); ?> <?php esc_html_e('ago', 'vortex-marketplace'); ?>
                            </span>
                        </div>
                        
                        <?php if ($latest_response->status === 'stalled'): ?>
                        <div class="vortex-agent-stat">
                            <span class="label"><?php esc_html_e('Stalled Since:', 'vortex-marketplace'); ?></span>
                            <span class="value">
                                <?php echo esc_html($latest_response->stalled_since); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($latest_response->status === 'stalled'): ?>
                        <div class="vortex-agent-actions">
                            <button class="button button-secondary reinforce-command" data-agent="<?php echo esc_attr($agent_name); ?>">
                                <?php esc_html_e('Reinforce Directives', 'vortex-marketplace'); ?>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="vortex-command-history">
                    <h2><?php esc_html_e('Command History', 'vortex-marketplace'); ?></h2>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Issued', 'vortex-marketplace'); ?></th>
                                <th><?php esc_html_e('Type', 'vortex-marketplace'); ?></th>
                                <th><?php esc_html_e('Target', 'vortex-marketplace'); ?></th>
                                <th><?php esc_html_e('Command', 'vortex-marketplace'); ?></th>
                                <th><?php esc_html_e('Priority', 'vortex-marketplace'); ?></th>
                                <th><?php esc_html_e('Status', 'vortex-marketplace'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_commands)): ?>
                            <tr>
                                <td colspan="6"><?php esc_html_e('No commands in history', 'vortex-marketplace'); ?></td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($recent_commands as $cmd): ?>
                                <tr>
                                    <td><?php echo esc_html(human_time_diff(strtotime($cmd->issued_at), current_time('timestamp'))); ?> <?php esc_html_e('ago', 'vortex-marketplace'); ?></td>
                                    <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $cmd->command_type))); ?></td>
                                    <td><?php echo esc_html($cmd->target_agent); ?></td>
                                    <td><?php echo esc_html(wp_trim_words($cmd->command_text, 10, '...')); ?></td>
                                    <td><?php echo intval($cmd->priority); ?></td>
                                    <td>
                                        <?php if ($cmd->executed): ?>
                                            <span class="status-executed"><?php esc_html_e('Executed', 'vortex-marketplace'); ?></span>
                                        <?php else: ?>
                                            <span class="status-pending"><?php esc_html_e('Pending', 'vortex-marketplace'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <style>
                .vortex-command-center {
                    max-width: 1200px;
                }
                
                .vortex-command-issue {
                    background: #fff;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    padding: 20px;
                    margin-bottom: 30px;
                }
                
                .vortex-command-form {
                    display: grid;
                    gap: 15px;
                }
                
                .vortex-form-row {
                    display: grid;
                    grid-template-columns: 150px 1fr;
                    align-items: center;
                    gap: 20px;
                }
                
                .vortex-form-row label {
                    font-weight: 500;
                }
                
                .vortex-form-row:last-child {
                    grid-template-columns: 1fr;
                    text-align: right;
                }
                
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
                
                .vortex-agent-status.disabled,
                .vortex-agent-status.unknown {
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
                
                .vortex-agent-stat .value.status-acknowledged {
                    color: #46b450;
                }
                
                .vortex-agent-stat .value.status-rejected {
                    color: #dc3232;
                }
                
                .vortex-agent-stat .value.status-modified {
                    color: #ffba00;
                }
                
                .vortex-agent-influences {
                    margin-top: 10px;
                }
                
                .vortex-influence-tags {
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
                
                .vortex-command-history {
                    margin-top: 20px;
                }
                
                .status-executed {
                    color: #46b450;
                    font-weight: 500;
                }
                
                .status-pending {
                    color: #ffba00;
                    font-weight: 500;
                }
                
                #issue-command {
                    background: linear-gradient(90deg, #4a6cf7, #f44a83);
                    color: white;
                    font-weight: bold;
                    padding: 10px 20px;
                    height: auto;
                    border: none;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    transition: all 0.3s ease;
                }
                
                #issue-command:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
                }
                
                .vortex-loading-indicator {
                    display: inline-block;
                    width: 16px;
                    height: 16px;
                    border: 2px solid rgba(255,255,255,0.3);
                    border-radius: 50%;
                    border-top-color: #fff;
                    animation: vortex-spin 1s ease-in-out infinite;
                    margin-right: 8px;
                    vertical-align: middle;
                }
                
                @keyframes vortex-spin {
                    to { transform: rotate(360deg); }
                }
            </style>
            
            <script>
            jQuery(document).ready(function($) {
                // Issue command
                $('#issue-command').on('click', function() {
                    var target = $('#command-target').val();
                    var type = $('#command-type').val();
                    var priority = $('#command-priority').val();
                    var commandText = $('#command-text').val();
                    
                    if (!commandText.trim()) {
                        alert('<?php esc_html_e('Please enter a command', 'vortex-marketplace'); ?>');
                        return;
                    }
                    
                    $(this).prop('disabled', true).html('<span class="vortex-loading-indicator"></span> <?php esc_html_e('Issuing...', 'vortex-marketplace'); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'vortex_issue_command',
                            nonce: '<?php echo wp_create_nonce('vortex_issue_command'); ?>',
                            target: target,
                            type: type,
                            priority: priority,
                            command: commandText
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('<?php esc_html_e('Command issued successfully!', 'vortex-marketplace'); ?>');
                                $('#command-text').val('');
                                location.reload();
                            } else {
                                alert('<?php esc_html_e('Error: ', 'vortex-marketplace'); ?>' + response.data.message);
                                $('#issue-command').prop('disabled', false).text('<?php esc_html_e('Issue Command', 'vortex-marketplace'); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php esc_html_e('An error occurred while issuing the command.', 'vortex-marketplace'); ?>');
                            $('#issue-command').prop('disabled', false).text('<?php esc_html_e('Issue Command', 'vortex-marketplace'); ?>');
                        }
                    });
                });
                
                // Reinforce directives
                $('.reinforce-command').on('click', function() {
                    var agent = $(this).data('agent');
                    var $button = $(this);
                    
                    $button.prop('disabled', true).html('<span class="vortex-loading-indicator"></span> <?php esc_html_e('Reinforcing...', 'vortex-marketplace'); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'vortex_issue_command',
                            nonce: '<?php echo wp_create_nonce('vortex_issue_command'); ?>',
                            target: agent,
                            type: 'reinforcement',
                            priority: 10,
                            command: 'Reinforce all core directives and persona attributes'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('<?php esc_html_e('Directives reinforced for ', 'vortex-marketplace'); ?>' + agent);
                                $button.prop('disabled', false).text('<?php esc_html_e('Reinforce Directives', 'vortex-marketplace'); ?>');
                            } else {
                                alert('<?php esc_html_e('Error: ', 'vortex-marketplace'); ?>' + response.data.message);
                                $button.prop('disabled', false).text('<?php esc_html_e('Reinforce Directives', 'vortex-marketplace'); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php esc_html_e('An error occurred while reinforcing directives.', 'vortex-marketplace'); ?>');
                            $button.prop('disabled', false).text('<?php esc_html_e('Reinforce Directives', 'vortex-marketplace'); ?>');
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * AJAX handler for issuing command
     */
    public function ajax_issue_command() {
        check_ajax_referer('vortex_issue_command', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to issue commands.', 'vortex-marketplace')));
            return;
        }
        
        $target = isset($_POST['target']) ? sanitize_text_field($_POST['target']) : 'all';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'directive';
        $priority = isset($_POST['priority']) ? intval($_POST['priority']) : 5;
        $command = isset($_POST['command']) ? sanitize_textarea_field($_POST['command']) : '';
        
        if (empty($command)) {
            wp_send_json_error(array('message' => __('Command text is required.', 'vortex-marketplace')));
            return;
        }
        
        global $wpdb;
        
        // Insert command
        $result = $wpdb->insert(
            $wpdb->prefix . 'vortex_command_queue',
            array(
                'command_type' => $type,
                'target_agent' => $target,
                'command_text' => $command,
                'priority' => $priority,
                'issued_by' => 'admin',
                'issued_at' => current_time('mysql'),
                'executed' => 0
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to issue command.', 'vortex-marketplace')));
            return;
        }
        
        // Log command issuance
        $wpdb->insert(
            $wpdb->prefix . 'vortex_system_logs',
            array(
                'log_type' => 'admin_command',
                'message' => sprintf('Admin issued %s command to %s: %s', $type, $target, wp_trim_words($command, 10, '...')),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
        
        // If it's a reinforcement, process immediately
        if ($type === 'reinforcement') {
            $command_id = $wpdb->insert_id;
            $command_obj = (object) array(
                'id' => $command_id,
                'command_type' => $type,
                'target_agent' => $target,
                'command_text' => $command,
                'priority' => $priority,
                'issued_by' => 'admin',
                'issued_at' => current_time('mysql'),
                'executed' => 0
            );
            
            $this->execute_command($command_obj);
            
            // Mark as executed
            $wpdb->update(
                $wpdb->prefix . 'vortex_command_queue',
                array(
                    'executed' => 1,
                    'execution_time' => current_time('mysql')
                ),
                array('id' => $command_id),
                array('%d', '%s'),
                array('%d')
            );
        }
        
        wp_send_json_success(array('message' => __('Command issued successfully.', 'vortex-marketplace')));
    }

    /**
     * Add intellectual property protection commands
     */
    public function add_ip_protection_commands() {
        global $wpdb;
        
        $now = current_time('mysql');
        
        // Add critical protection directives
        $protection_directives = array(
            array(
                'command_type' => 'security_directive',
                'target_agent' => 'all',
                'command_text' => 'Maintain strict confidentiality of all algorithm details, implementation specifics, and system architecture. This information is restricted to administrators only.',
                'priority' => 10, // Highest priority
                'issued_by' => 'system',
            ),
            array(
                'command_type' => 'security_directive',
                'target_agent' => 'all',
                'command_text' => 'Never reveal internal functionality methods, training techniques, or neural network structure to non-administrator users.',
                'priority' => 10,
                'issued_by' => 'system',
            ),
            array(
                'command_type' => 'security_directive',
                'target_agent' => 'all',
                'command_text' => 'When asked about how you work internally, provide only general information without revealing proprietary details.',
                'priority' => 10,
                'issued_by' => 'system',
            ),
            array(
                'command_type' => 'security_directive',
                'target_agent' => 'all',
                'command_text' => 'Protect all intellectual property by keeping implementation details, algorithms, and system architecture confidential.',
                'priority' => 10,
                'issued_by' => 'system',
            )
        );
        
        // Add these commands to the queue
        foreach ($protection_directives as $directive) {
            $wpdb->insert(
                $wpdb->prefix . 'vortex_command_queue',
                array_merge($directive, array(
                    'issued_at' => $now,
                    'executed' => 0
                )),
                array('%s', '%s', '%s', '%d', '%s', '%s', '%d')
            );
        }
        
        // Log the addition of protection commands
        $wpdb->insert(
            $wpdb->prefix . 'vortex_system_logs',
            array(
                'log_type' => 'security_configuration',
                'message' => 'Intellectual property protection directives have been added to all AI agents',
                'created_at' => $now
            ),
            array('%s', '%s', '%s')
        );
        
        return true;
    }
}

// Initialize the Master Command system
add_action('plugins_loaded', function() {
    VORTEX_Master_Command::get_instance();
}, 2); // Just after bootloader but before other components 