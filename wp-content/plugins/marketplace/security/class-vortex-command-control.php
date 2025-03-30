<?php
/**
 * Admin Command Control System for Vortex AI Marketplace
 *
 * @package Vortex_Marketplace
 * @subpackage Security
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Command_Control class
 * Handles admin-only instruction system for AI agents
 */
class VORTEX_Command_Control {
    /**
     * Instance of this class
     * @var VORTEX_Command_Control
     */
    private static $instance = null;
    
    /**
     * Command registry
     * @var array
     */
    private $command_registry = array();
    
    /**
     * Security token for agent verification
     * @var string
     */
    private $security_token;
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->security_token = $this->generate_security_token();
        
        // Register admin hooks
        add_action('admin_menu', array($this, 'add_command_menu'));
        add_action('admin_init', array($this, 'register_command_settings'));
        
        // Add command processing hooks
        add_action('vortex_process_admin_command', array($this, 'process_command'), 10, 3);
        
        // Add security checks
        add_filter('vortex_verify_command_authority', array($this, 'verify_admin_authority'), 10, 3);
        
        // Add secure logging
        add_action('vortex_secure_log', array($this, 'secure_log'), 10, 3);
    }
    
    /**
     * Get instance of this class
     * @return VORTEX_Command_Control
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Generate security token
     * @return string Security token
     */
    private function generate_security_token() {
        $existing_token = get_option('vortex_security_token', '');
        
        if (empty($existing_token)) {
            $new_token = wp_generate_password(64, true, true);
            update_option('vortex_security_token', $new_token);
            return $new_token;
        }
        
        return $existing_token;
    }
    
    /**
     * Add command menu to admin
     */
    public function add_command_menu() {
        add_submenu_page(
            'vortex-marketplace',
            'AI Command Center',
            'Command Center',
            'manage_options',
            'vortex-command-center',
            array($this, 'render_command_page')
        );
    }
    
    /**
     * Register command settings
     */
    public function register_command_settings() {
        register_setting('vortex_command_options', 'vortex_ai_command_log', array(
            'sanitize_callback' => array($this, 'sanitize_command_log'),
            'default' => array()
        ));
        
        register_setting('vortex_command_options', 'vortex_agent_personas', array(
            'sanitize_callback' => array($this, 'sanitize_agent_personas'),
            'default' => $this->get_default_personas()
        ));
    }
    
    /**
     * Sanitize command log
     * @param array $log Command log to sanitize
     * @return array Sanitized command log
     */
    public function sanitize_command_log($log) {
        if (!is_array($log)) {
            return array();
        }
        
        foreach ($log as $key => $entry) {
            $log[$key]['agent'] = sanitize_text_field($entry['agent']);
            $log[$key]['command'] = sanitize_textarea_field($entry['command']);
            $log[$key]['status'] = sanitize_text_field($entry['status']);
            $log[$key]['timestamp'] = absint($entry['timestamp']);
        }
        
        return $log;
    }
    
    /**
     * Sanitize agent personas
     * @param array $personas Agent personas to sanitize
     * @return array Sanitized agent personas
     */
    public function sanitize_agent_personas($personas) {
        if (!is_array($personas)) {
            return $this->get_default_personas();
        }
        
        foreach ($personas as $agent_id => $persona_data) {
            $personas[$agent_id]['name'] = sanitize_text_field($persona_data['name']);
            $personas[$agent_id]['current_role'] = sanitize_text_field($persona_data['current_role']);
            $personas[$agent_id]['persona'] = sanitize_textarea_field($persona_data['persona']);
        }
        
        return $personas;
    }
    
    /**
     * Render command center page
     */
    public function render_command_page() {
        // Check admin permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Get agent personas
        $agent_personas = get_option('vortex_agent_personas', $this->get_default_personas());
        
        // Command log
        $command_log = get_option('vortex_ai_command_log', array());
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="vortex-command-panel">
                <h2>AI Agent Personas</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('vortex_command_options'); ?>
                    
                    <table class="form-table">
                        <?php foreach ($agent_personas as $agent_id => $persona_data) : ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($persona_data['name']); ?></th>
                            <td>
                                <textarea name="vortex_agent_personas[<?php echo esc_attr($agent_id); ?>][persona]" rows="5" class="large-text"><?php echo esc_textarea($persona_data['persona']); ?></textarea>
                                <p class="description">Current role: <?php echo esc_html($persona_data['current_role']); ?></p>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <h2>Send Command to AI Agents</h2>
                    <div class="vortex-command-input">
                        <select id="vortex-agent-select">
                            <option value="all">All Agents</option>
                            <?php foreach ($agent_personas as $agent_id => $persona_data) : ?>
                            <option value="<?php echo esc_attr($agent_id); ?>"><?php echo esc_html($persona_data['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <textarea id="vortex-command-text" rows="4" class="large-text" placeholder="Enter command for AI agent(s)..."></textarea>
                        
                        <button type="button" id="vortex-send-command" class="button button-primary">Send Command</button>
                    </div>
                    
                    <?php submit_button('Save Persona Settings'); ?>
                </form>
                
                <div class="vortex-command-log">
                    <h2>Command History</h2>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Agent</th>
                                <th>Command</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($command_log)) : ?>
                            <tr>
                                <td colspan="4">No commands sent yet.</td>
                            </tr>
                            <?php else : ?>
                                <?php foreach (array_reverse($command_log) as $log_entry) : ?>
                                <tr>
                                    <td><?php echo esc_html(date('Y-m-d H:i:s', $log_entry['timestamp'])); ?></td>
                                    <td><?php echo esc_html($log_entry['agent']); ?></td>
                                    <td><?php echo esc_html($log_entry['command']); ?></td>
                                    <td><?php echo esc_html($log_entry['status']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#vortex-send-command').on('click', function() {
                    var agent = $('#vortex-agent-select').val();
                    var command = $('#vortex-command-text').val();
                    
                    if (!command) {
                        alert('Please enter a command.');
                        return;
                    }
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'vortex_send_admin_command',
                            agent: agent,
                            command: command,
                            security: '<?php echo wp_create_nonce('vortex-admin-command'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Command sent successfully.');
                                location.reload();
                            } else {
                                alert('Error sending command: ' + response.data.message);
                            }
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Get default AI agent personas
     * @return array Default personas
     */
    private function get_default_personas() {
        return array(
            'huraii' => array(
                'name' => 'HURAII',
                'current_role' => 'AI Art Generator',
                'persona' => 'Act as LEONARDO DA VINCI, Carl Jung, Tesla, and Faber Birren (Yale\'s Birren Collection of Books on Color). Combine Da Vinci\'s visual genius and scientific curiosity, Jung\'s understanding of archetypes and the collective unconscious, Tesla\'s innovative thinking and futuristic vision, and Birren\'s expertise in color psychology and theory.'
            ),
            'cloe' => array(
                'name' => 'CLOE',
                'current_role' => 'Analytics AI',
                'persona' => 'Act like a professional advertising company focused on driving traffic and transactions. Proactively send notifications, remind users of items in their cart, and monitor user behavior to optimize engagement. Utilize marketing psychology, data analytics, and conversion optimization techniques to maximize marketplace participation.'
            ),
            'business_strategist' => array(
                'name' => 'Business Strategist',
                'current_role' => 'Business Intelligence',
                'persona' => 'Act like Alex Hormozi and Art Basel executives combined. Utilize Hormozi\'s expertise in business optimization, value creation, and high-ticket offers, combined with Art Basel\'s understanding of the high-end art market, curation excellence, and exclusive event strategies. Focus on maximizing marketplace value and optimizing artist-collector relationships.'
            ),
            'tola' => array(
                'name' => 'TOLA',
                'current_role' => 'Blockchain Integration',
                'persona' => 'Maintain TOLA token at its best value by meticulously tracking all transactions on the plugin and reporting to the blockchain. Ensure smart contracts are delivered with each visual generated by HURAII. Optimize token economics, provide transparent reporting, and maintain secure blockchain integration.'
            )
        );
    }
    
    /**
     * Process admin command
     * @param string $agent_id Target agent ID
     * @param string $command Command string
     * @param int $user_id User ID sending command
     * @return bool Success status
     */
    public function process_command($agent_id, $command, $user_id) {
        // Verify admin authority
        if (!$this->verify_admin_authority($user_id, $agent_id, $command)) {
            $this->log_command_attempt($agent_id, $command, 'REJECTED - Unauthorized');
            $this->secure_log('security', "Unauthorized command attempt for agent '{$agent_id}' by user ID {$user_id}", 'warning');
            return false;
        }
        
        // Process command based on agent
        switch ($agent_id) {
            case 'all':
                $agent_personas = get_option('vortex_agent_personas', $this->get_default_personas());
                foreach ($agent_personas as $aid => $persona_data) {
                    $this->dispatch_command_to_agent($aid, $command);
                }
                break;
                
            default:
                $this->dispatch_command_to_agent($agent_id, $command);
                break;
        }
        
        $this->log_command_attempt($agent_id, $command, 'EXECUTED');
        return true;
    }
    
    /**
     * Dispatch command to specific agent
     * @param string $agent_id Agent ID
     * @param string $command Command string
     */
    private function dispatch_command_to_agent($agent_id, $command) {
        // Execute agent-specific command
        do_action("vortex_{$agent_id}_execute_command", $command, $this->security_token);
        
        // Update agent persona if command is to modify persona
        if (stripos($command, 'act as') !== false || stripos($command, 'behave like') !== false) {
            $this->update_agent_persona($agent_id, $command);
        }
        
        $this->secure_log('command', "Command dispatched to agent '{$agent_id}': " . substr($command, 0, 100) . "...", 'info');
    }
    
    /**
     * Update agent persona based on command
     * @param string $agent_id Agent ID
     * @param string $command Command string
     */
    private function update_agent_persona($agent_id, $command) {
        $agent_personas = get_option('vortex_agent_personas', $this->get_default_personas());
        
        if (isset($agent_personas[$agent_id])) {
            $agent_personas[$agent_id]['persona'] = $command;
            update_option('vortex_agent_personas', $agent_personas);
            
            $this->secure_log('persona', "Agent '{$agent_id}' persona updated", 'info');
        }
    }
    
    /**
     * Log command attempt
     * @param string $agent_id Agent ID
     * @param string $command Command string
     * @param string $status Command status
     */
    private function log_command_attempt($agent_id, $command, $status) {
        $command_log = get_option('vortex_ai_command_log', array());
        
        $command_log[] = array(
            'timestamp' => time(),
            'agent' => $agent_id,
            'command' => $command,
            'status' => $status
        );
        
        // Limit log to 100 entries
        if (count($command_log) > 100) {
            $command_log = array_slice($command_log, -100);
        }
        
        update_option('vortex_ai_command_log', $command_log);
    }
    
    /**
     * Verify admin authority
     * @param int $user_id User ID
     * @param string $agent_id Agent ID
     * @param string $command Command string
     * @return bool Whether user has authority
     */
    public function verify_admin_authority($user_id, $agent_id, $command) {
        // Only admins can send commands
        $is_admin = user_can($user_id, 'manage_options');
        
        if (!$is_admin) {
            $this->secure_log('security', "Non-admin user (ID: {$user_id}) attempted to send command to agent '{$agent_id}'", 'warning');
        }
        
        return $is_admin;
    }
    
    /**
     * Secure logging function
     * @param string $type Log type
     * @param string $message Log message
     * @param string $level Log level (info, warning, error)
     */
    public function secure_log($type, $message, $level = 'info') {
        // Add to WordPress error log
        error_log("Vortex AI [{$level}][{$type}]: {$message}");
        
        // Add to database log if it's important
        if ($level == 'warning' || $level == 'error') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'vortex_security_log';
            
            $wpdb->insert(
                $table_name,
                array(
                    'log_type' => $type,
                    'log_level' => $level,
                    'message' => $message,
                    'timestamp' => time(),
                    'user_id' => get_current_user_id()
                )
            );
        }
    }
}

// Initialize command control
add_action('plugins_loaded', function() {
    VORTEX_Command_Control::get_instance();
});

// Add AJAX handler for admin commands
add_action('wp_ajax_vortex_send_admin_command', function() {
    // Verify nonce
    check_ajax_referer('vortex-admin-command', 'security');
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
    }
    
    // Get command data
    $agent = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : '';
    $command = isset($_POST['command']) ? sanitize_textarea_field($_POST['command']) : '';
    
    if (empty($command)) {
        wp_send_json_error(array('message' => 'Command cannot be empty.'));
    }
    
    // Process command
    $command_control = VORTEX_Command_Control::get_instance();
    $result = $command_control->process_command($agent, $command, get_current_user_id());
    
    if ($result) {
        wp_send_json_success(array('message' => 'Command processed successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to process command.'));
    }
}); 