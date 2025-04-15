<?php
/**
 * VORTEX Integration Registry
 *
 * Manages all integration points between DAO, AI, Marketplace, and Security
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_Integration_Registry {
    
    private static $instance = null;
    private $integrations = array();
    
    /**
     * Get class instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize all integration components
        add_action('plugins_loaded', array($this, 'initialize_integrations'), 20);
        
        // Add dashboard widget to show integration status
        add_action('wp_dashboard_setup', array($this, 'add_integration_dashboard_widget'));
        
        // Add admin menu for integration management
        add_action('admin_menu', array($this, 'add_integration_admin_menu'), 30);
        
        // Register AJAX handlers
        add_action('wp_ajax_vortex_check_integration_status', array($this, 'ajax_check_integration_status'));
        add_action('wp_ajax_vortex_toggle_integration', array($this, 'ajax_toggle_integration'));
    }
    
    /**
     * Initialize integrations
     */
    public function initialize_integrations() {
        // Initialize DAO integrations
        $this->register_integration('dao_ai', array(
            'title' => 'DAO AI Integration',
            'description' => 'Connects DAO governance with AI Orchestrator for insights and security analysis',
            'component' => 'VORTEX_DAO_AI_Integration',
            'class_file' => 'dao/class-vortex-dao-ai-integration.php',
            'enabled' => true
        ));
        
        $this->register_integration('dao_marketplace', array(
            'title' => 'DAO-Marketplace Revenue Bridge',
            'description' => 'Manages revenue sharing between marketplace and DAO treasury',
            'component' => 'VORTEX_Marketplace_Revenue_Bridge',
            'class_file' => 'dao/class-vortex-marketplace-revenue-bridge.php',
            'enabled' => true
        ));
        
        $this->register_integration('dao_security', array(
            'title' => 'DAO Security Bridge',
            'description' => 'Provides security integration between DAO and platform security system',
            'component' => 'VORTEX_DAO_Security_Bridge',
            'class_file' => 'dao/class-vortex-dao-security-bridge.php',
            'enabled' => true
        ));
        
        // Add blockchain metrics integration
        $this->register_integration('blockchain_metrics', array(
            'title' => 'Real-time Blockchain Metrics',
            'description' => 'Provides real-time metrics for TOLA token and artwork transactions',
            'component' => 'VORTEX_Blockchain_Metrics',
            'class_file' => 'blockchain/class-vortex-blockchain-metrics.php',
            'enabled' => true,
            'dependencies' => array('VORTEX_Token_Transfers')
        ));
        
        // Load enabled integrations
        $this->load_enabled_integrations();
    }
    
    /**
     * Register a new integration
     *
     * @param string $id Integration ID
     * @param array  $config Integration configuration
     */
    public function register_integration($id, $config) {
        // Store integration config
        $this->integrations[$id] = wp_parse_args($config, array(
            'title' => '',
            'description' => '',
            'component' => '',
            'class_file' => '',
            'enabled' => false,
            'status' => 'inactive',
            'last_checked' => 0,
            'dependencies' => array()
        ));
        
        // Get saved enabled state
        $saved_enabled = get_option('vortex_integration_' . $id . '_enabled');
        if ($saved_enabled !== false) {
            $this->integrations[$id]['enabled'] = (bool) $saved_enabled;
        }
    }
    
    /**
     * Load all enabled integrations
     */
    private function load_enabled_integrations() {
        foreach ($this->integrations as $id => $config) {
            if ($config['enabled']) {
                $this->load_integration($id);
            }
        }
    }
    
    /**
     * Load a specific integration
     *
     * @param string $id Integration ID
     * @return bool Success or failure
     */
    public function load_integration($id) {
        if (!isset($this->integrations[$id])) {
            return false;
        }
        
        $config = $this->integrations[$id];
        
        // Check if the class file exists
        $file_path = plugin_dir_path(dirname(__FILE__)) . 'includes/' . $config['class_file'];
        if (!file_exists($file_path)) {
            $this->integrations[$id]['status'] = 'error';
            $this->integrations[$id]['error'] = 'Class file not found: ' . $config['class_file'];
            return false;
        }
        
        // Check if dependencies are loaded
        if (!empty($config['dependencies'])) {
            foreach ($config['dependencies'] as $dependency) {
                if (!$this->is_dependency_available($dependency)) {
                    $this->integrations[$id]['status'] = 'error';
                    $this->integrations[$id]['error'] = 'Dependency not available: ' . $dependency;
                    return false;
                }
            }
        }
        
        // Include the class file if it's not already loaded
        if (!class_exists($config['component'])) {
            require_once $file_path;
        }
        
        // Check if the class exists
        if (!class_exists($config['component'])) {
            $this->integrations[$id]['status'] = 'error';
            $this->integrations[$id]['error'] = 'Component class not found: ' . $config['component'];
            return false;
        }
        
        // Check if the class has a get_instance method
        if (!method_exists($config['component'], 'get_instance')) {
            $this->integrations[$id]['status'] = 'error';
            $this->integrations[$id]['error'] = 'Component does not have get_instance method';
            return false;
        }
        
        // Initialize the integration
        try {
            call_user_func(array($config['component'], 'get_instance'));
            $this->integrations[$id]['status'] = 'active';
            $this->integrations[$id]['last_checked'] = time();
            return true;
        } catch (Exception $e) {
            $this->integrations[$id]['status'] = 'error';
            $this->integrations[$id]['error'] = 'Error initializing component: ' . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Check if a dependency is available
     *
     * @param string $dependency Dependency name
     * @return bool Whether dependency is available
     */
    private function is_dependency_available($dependency) {
        // Check if class exists
        if (class_exists($dependency)) {
            return true;
        }
        
        // Check if function exists
        if (function_exists($dependency)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Add dashboard widget for integrations
     */
    public function add_integration_dashboard_widget() {
        // Only add widget for users who can manage options
        if (!current_user_can('manage_options')) {
            return;
        }
        
        wp_add_dashboard_widget(
            'vortex_integrations_status',
            'VORTEX Integrations',
            array($this, 'render_dashboard_widget')
        );
    }
    
    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        // Check all integrations status
        $this->check_all_integrations_status();
        
        echo '<div class="vortex-integration-status-widget">';
        echo '<p>Status of VORTEX DAO integrations:</p>';
        
        echo '<ul class="vortex-integration-list">';
        foreach ($this->integrations as $id => $config) {
            $status_class = 'status-' . $config['status'];
            $status_label = ucfirst($config['status']);
            
            echo '<li class="' . $status_class . '">';
            echo '<span class="integration-title">' . esc_html($config['title']) . '</span>';
            echo '<span class="integration-status">' . esc_html($status_label) . '</span>';
            echo '</li>';
        }
        echo '</ul>';
        
        echo '<p><a href="' . admin_url('admin.php?page=vortex-integrations') . '" class="button">Manage Integrations</a></p>';
        echo '</div>';
        
        // Add inline styles
        echo '<style>
            .vortex-integration-list { margin-top: 10px; }
            .vortex-integration-list li { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #eee; }
            .status-active .integration-status { color: #46b450; }
            .status-inactive .integration-status { color: #999; }
            .status-error .integration-status { color: #dc3232; }
        </style>';
    }
    
    /**
     * Add admin menu for integration management
     */
    public function add_integration_admin_menu() {
        add_submenu_page(
            'vortex-dashboard',
            'Integrations',
            'Integrations',
            'manage_options',
            'vortex-integrations',
            array($this, 'render_integrations_page')
        );
    }
    
    /**
     * Render integrations admin page
     */
    public function render_integrations_page() {
        // Check all integrations status
        $this->check_all_integrations_status();
        
        ?>
        <div class="wrap">
            <h1>VORTEX Integrations</h1>
            
            <p>Manage integrations between different VORTEX components.</p>
            
            <div class="vortex-integration-tables">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Integration</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->integrations as $id => $config): ?>
                        <tr>
                            <td><?php echo esc_html($config['title']); ?></td>
                            <td><?php echo esc_html($config['description']); ?></td>
                            <td class="status-<?php echo esc_attr($config['status']); ?>">
                                <?php echo ucfirst(esc_html($config['status'])); ?>
                                <?php if ($config['status'] === 'error' && isset($config['error'])): ?>
                                    <div class="integration-error"><?php echo esc_html($config['error']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button 
                                    class="button <?php echo $config['enabled'] ? 'button-secondary' : 'button-primary'; ?>"
                                    data-id="<?php echo esc_attr($id); ?>"
                                    data-action="<?php echo $config['enabled'] ? 'disable' : 'enable'; ?>"
                                    onclick="toggleIntegration(this)"
                                >
                                    <?php echo $config['enabled'] ? 'Disable' : 'Enable'; ?>
                                </button>
                                
                                <button 
                                    class="button button-secondary"
                                    data-id="<?php echo esc_attr($id); ?>"
                                    onclick="checkIntegration(this)"
                                >
                                    Check Status
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div id="integration-messages" class="notice" style="display: none;"></div>
        </div>
        
        <script type="text/javascript">
            function toggleIntegration(button) {
                const id = button.dataset.id;
                const action = button.dataset.action;
                const messages = document.getElementById('integration-messages');
                
                // Show loading state
                button.classList.add('updating-message');
                button.disabled = true;
                
                // Make AJAX request
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_toggle_integration',
                        nonce: '<?php echo wp_create_nonce('vortex_integration_nonce'); ?>',
                        integration_id: id,
                        toggle_action: action
                    },
                    success: function(response) {
                        button.classList.remove('updating-message');
                        button.disabled = false;
                        
                        if (response.success) {
                            // Show success message
                            messages.className = 'notice notice-success';
                            messages.innerHTML = '<p>' + response.data.message + '</p>';
                            messages.style.display = 'block';
                            
                            // Update button state
                            button.dataset.action = action === 'enable' ? 'disable' : 'enable';
                            button.textContent = action === 'enable' ? 'Disable' : 'Enable';
                            button.className = 'button ' + (action === 'enable' ? 'button-secondary' : 'button-primary');
                            
                            // Update status cell
                            const statusCell = button.closest('tr').querySelector('td:nth-child(3)');
                            statusCell.className = 'status-' + response.data.status;
                            statusCell.textContent = response.data.status.charAt(0).toUpperCase() + response.data.status.slice(1);
                            
                            // Reload page after a short delay
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            // Show error message
                            messages.className = 'notice notice-error';
                            messages.innerHTML = '<p>' + response.data.message + '</p>';
                            messages.style.display = 'block';
                        }
                        
                        // Hide message after 5 seconds
                        setTimeout(function() {
                            messages.style.display = 'none';
                        }, 5000);
                    },
                    error: function() {
                        button.classList.remove('updating-message');
                        button.disabled = false;
                        
                        // Show error message
                        messages.className = 'notice notice-error';
                        messages.innerHTML = '<p>An error occurred. Please try again.</p>';
                        messages.style.display = 'block';
                        
                        // Hide message after 5 seconds
                        setTimeout(function() {
                            messages.style.display = 'none';
                        }, 5000);
                    }
                });
            }
            
            function checkIntegration(button) {
                const id = button.dataset.id;
                const messages = document.getElementById('integration-messages');
                
                // Show loading state
                button.classList.add('updating-message');
                button.disabled = true;
                
                // Make AJAX request
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_check_integration_status',
                        nonce: '<?php echo wp_create_nonce('vortex_integration_nonce'); ?>',
                        integration_id: id
                    },
                    success: function(response) {
                        button.classList.remove('updating-message');
                        button.disabled = false;
                        
                        if (response.success) {
                            // Show success message
                            messages.className = 'notice notice-success';
                            messages.innerHTML = '<p>' + response.data.message + '</p>';
                            messages.style.display = 'block';
                            
                            // Update status cell
                            const statusCell = button.closest('tr').querySelector('td:nth-child(3)');
                            statusCell.className = 'status-' + response.data.status;
                            statusCell.textContent = response.data.status.charAt(0).toUpperCase() + response.data.status.slice(1);
                            
                            if (response.data.error) {
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'integration-error';
                                errorDiv.textContent = response.data.error;
                                statusCell.appendChild(errorDiv);
                            }
                        } else {
                            // Show error message
                            messages.className = 'notice notice-error';
                            messages.innerHTML = '<p>' + response.data.message + '</p>';
                            messages.style.display = 'block';
                        }
                        
                        // Hide message after 5 seconds
                        setTimeout(function() {
                            messages.style.display = 'none';
                        }, 5000);
                    },
                    error: function() {
                        button.classList.remove('updating-message');
                        button.disabled = false;
                        
                        // Show error message
                        messages.className = 'notice notice-error';
                        messages.innerHTML = '<p>An error occurred. Please try again.</p>';
                        messages.style.display = 'block';
                        
                        // Hide message after 5 seconds
                        setTimeout(function() {
                            messages.style.display = 'none';
                        }, 5000);
                    }
                });
            }
        </script>
        
        <style>
            .status-active { color: #46b450; }
            .status-inactive { color: #999; }
            .status-error { color: #dc3232; }
            .integration-error { color: #dc3232; font-size: 12px; margin-top: 5px; }
            #integration-messages { margin-top: 15px; }
        </style>
        <?php
    }
    
    /**
     * AJAX handler to check integration status
     */
    public function ajax_check_integration_status() {
        check_ajax_referer('vortex_integration_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $integration_id = isset($_POST['integration_id']) ? sanitize_key($_POST['integration_id']) : '';
        
        if (empty($integration_id) || !isset($this->integrations[$integration_id])) {
            wp_send_json_error(array('message' => 'Invalid integration ID'));
            return;
        }
        
        // Check the integration status
        $this->check_integration_status($integration_id);
        
        $integration = $this->integrations[$integration_id];
        
        wp_send_json_success(array(
            'message' => 'Integration status checked',
            'status' => $integration['status'],
            'error' => isset($integration['error']) ? $integration['error'] : null
        ));
    }
    
    /**
     * AJAX handler to toggle integration
     */
    public function ajax_toggle_integration() {
        check_ajax_referer('vortex_integration_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $integration_id = isset($_POST['integration_id']) ? sanitize_key($_POST['integration_id']) : '';
        $toggle_action = isset($_POST['toggle_action']) ? sanitize_key($_POST['toggle_action']) : '';
        
        if (empty($integration_id) || !isset($this->integrations[$integration_id])) {
            wp_send_json_error(array('message' => 'Invalid integration ID'));
            return;
        }
        
        if (!in_array($toggle_action, array('enable', 'disable'))) {
            wp_send_json_error(array('message' => 'Invalid toggle action'));
            return;
        }
        
        $enable = ($toggle_action === 'enable');
        
        // Update integration enabled state
        $this->integrations[$integration_id]['enabled'] = $enable;
        update_option('vortex_integration_' . $integration_id . '_enabled', $enable);
        
        // If enabling, try to load the integration
        if ($enable) {
            $result = $this->load_integration($integration_id);
            
            if (!$result) {
                wp_send_json_error(array(
                    'message' => 'Failed to enable integration: ' . 
                        (isset($this->integrations[$integration_id]['error']) ? 
                         $this->integrations[$integration_id]['error'] : 'Unknown error')
                ));
                return;
            }
        } else {
            // Just update the status if disabling
            $this->integrations[$integration_id]['status'] = 'inactive';
        }
        
        wp_send_json_success(array(
            'message' => 'Integration ' . ($enable ? 'enabled' : 'disabled') . ' successfully',
            'status' => $this->integrations[$integration_id]['status']
        ));
    }
    
    /**
     * Check status of all integrations
     */
    private function check_all_integrations_status() {
        foreach (array_keys($this->integrations) as $id) {
            $this->check_integration_status($id);
        }
    }
    
    /**
     * Check status of a specific integration
     *
     * @param string $id Integration ID
     */
    private function check_integration_status($id) {
        if (!isset($this->integrations[$id])) {
            return;
        }
        
        // If not enabled, status is inactive
        if (!$this->integrations[$id]['enabled']) {
            $this->integrations[$id]['status'] = 'inactive';
            return;
        }
        
        // If it's been less than 5 minutes since last check, skip
        if (isset($this->integrations[$id]['last_checked']) && 
            time() - $this->integrations[$id]['last_checked'] < 300) {
            return;
        }
        
        // Check if the component class is loaded
        $component = $this->integrations[$id]['component'];
        
        if (!class_exists($component)) {
            $this->integrations[$id]['status'] = 'error';
            $this->integrations[$id]['error'] = 'Component class not loaded';
            return;
        }
        
        // Check if the component has an instance
        if (!method_exists($component, 'get_instance')) {
            $this->integrations[$id]['status'] = 'error';
            $this->integrations[$id]['error'] = 'Component does not have get_instance method';
            return;
        }
        
        try {
            $instance = call_user_func(array($component, 'get_instance'));
            $this->integrations[$id]['status'] = 'active';
            $this->integrations[$id]['last_checked'] = time();
        } catch (Exception $e) {
            $this->integrations[$id]['status'] = 'error';
            $this->integrations[$id]['error'] = 'Error getting component instance: ' . $e->getMessage();
        }
    }
    
    /**
     * Get all integrations
     *
     * @return array Array of integrations
     */
    public function get_integrations() {
        return $this->integrations;
    }
    
    /**
     * Get a specific integration
     *
     * @param string $id Integration ID
     * @return array|null Integration config or null if not found
     */
    public function get_integration($id) {
        return isset($this->integrations[$id]) ? $this->integrations[$id] : null;
    }
}

// Initialize Integration Registry
$vortex_integration_registry = VORTEX_Integration_Registry::get_instance(); 