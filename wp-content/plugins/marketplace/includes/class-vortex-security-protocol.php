<?php
/**
 * VORTEX Security Protocol
 *
 * Ensures algorithm integrity and prevents sharing of trade secrets
 * Strictly restricts sensitive information to admin users only
 *
 * @package Vortex_Marketplace
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Security_Protocol Class
 */
class VORTEX_Security_Protocol {
    /**
     * The single instance of the class
     * @var VORTEX_Security_Protocol
     */
    private static $instance = null;
    
    /**
     * Restricted algorithm components
     * @var array
     */
    private $restricted_components = array(
        'ai_architecture' => true,
        'learning_algorithms' => true,
        'neural_network_structure' => true,
        'training_methodologies' => true,
        'deep_learning_weights' => true,
        'cross_learning_mechanics' => true,
        'agent_interaction_protocols' => true,
        'source_code_details' => true,
        'proprietary_functions' => true,
        'model_weights' => true,
        'system_architecture' => true
    );
    
    /**
     * Main VORTEX_Security_Protocol Instance
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register hooks for filtering data output
        add_filter('vortex_get_system_info', array($this, 'filter_system_info'), 10, 2);
        add_filter('vortex_get_agent_info', array($this, 'filter_agent_info'), 10, 2);
        add_filter('vortex_get_audit_info', array($this, 'filter_audit_info'), 10, 2);
        
        // REST API security
        add_filter('rest_pre_dispatch', array($this, 'secure_api_endpoints'), 10, 3);
        
        // Add admin-only capabilities
        add_action('admin_init', array($this, 'add_vortex_capabilities'));
        
        // Secure AJAX endpoints
        add_action('wp_ajax_nopriv_vortex_get_algorithm_details', array($this, 'block_unauthorized_ajax'));
        add_action('wp_ajax_nopriv_vortex_get_agent_structure', array($this, 'block_unauthorized_ajax'));
        add_action('wp_ajax_nopriv_vortex_get_learning_details', array($this, 'block_unauthorized_ajax'));
        
        // Secure debug info
        add_filter('debug_information', array($this, 'filter_debug_information'), 999);
        
        // Database access restriction
        add_filter('query', array($this, 'restrict_sensitive_queries'));
        
        // Setup database encryption for sensitive tables
        $this->setup_data_encryption();
        
        // Log security events
        add_action('init', array($this, 'setup_security_monitoring'));
    }
    
    /**
     * Setup data encryption for sensitive information
     */
    private function setup_data_encryption() {
        // This would ideally implement actual encryption of sensitive database tables
        // For this implementation, we'll focus on access control
        
        if (!get_option('vortex_data_encryption_initialized', false)) {
            // Generate a unique encryption key for this installation
            $encryption_key = wp_generate_password(64, true, true);
            update_option('vortex_encryption_key_hash', wp_hash($encryption_key));
            
            // Don't store the actual key in the database for security
            // In a real implementation, this would be securely provided to the admin
            
            update_option('vortex_data_encryption_initialized', true);
        }
    }
    
    /**
     * Setup security monitoring
     */
    public function setup_security_monitoring() {
        // Monitor requests for suspicious activity
        if (!defined('WP_ADMIN') && !current_user_can('manage_options') && $this->is_accessing_sensitive_url()) {
            $this->log_security_event('unauthorized_access_attempt', 'Unauthorized attempt to access sensitive VORTEX information');
        }
    }
    
    /**
     * Check if current request is trying to access sensitive URLs
     */
    private function is_accessing_sensitive_url() {
        $current_url = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        
        $sensitive_patterns = array(
            '/vortex.*algorithm/i',
            '/vortex.*neural/i',
            '/vortex.*learning/i',
            '/vortex.*weights/i',
            '/vortex.*architecture/i',
            '/vortex.*training/i',
            '/vortex.*code/i'
        );
        
        foreach ($sensitive_patterns as $pattern) {
            if (preg_match($pattern, $current_url)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Add Vortex-specific capabilities to admin role
     */
    public function add_vortex_capabilities() {
        $role = get_role('administrator');
        
        if ($role) {
            $role->add_cap('view_vortex_algorithm_details');
            $role->add_cap('edit_vortex_algorithm');
            $role->add_cap('manage_vortex_ai_agents');
            $role->add_cap('view_vortex_system_architecture');
        }
    }
    
    /**
     * Filter system information based on user capabilities
     */
    public function filter_system_info($info, $context = '') {
        if (!current_user_can('manage_options')) {
            // Remove all sensitive algorithm information
            foreach ($this->restricted_components as $component => $restricted) {
                if (isset($info[$component])) {
                    unset($info[$component]);
                }
            }
            
            // Replace details with general information for non-admins
            $info['overview'] = 'VORTEX AI Marketplace - Advanced AI-driven marketplace platform';
            $info['status'] = isset($info['status']) ? $info['status'] : 'Active';
            
            // Remove any methods, class details, etc.
            if (isset($info['methods'])) unset($info['methods']);
            if (isset($info['classes'])) unset($info['classes']);
            if (isset($info['database_structure'])) unset($info['database_structure']);
            if (isset($info['training_data'])) unset($info['training_data']);
            
            // Add disclaimer
            $info['notice'] = 'Detailed VORTEX algorithm information is restricted to administrators only.';
        }
        
        return $info;
    }
    
    /**
     * Filter agent information based on user capabilities
     */
    public function filter_agent_info($info, $agent_name = '') {
        if (!current_user_can('manage_options')) {
            // Remove detailed agent architecture information
            if (isset($info['architecture'])) unset($info['architecture']);
            if (isset($info['learning_parameters'])) unset($info['learning_parameters']);
            if (isset($info['neural_structure'])) unset($info['neural_structure']);
            if (isset($info['model_weights'])) unset($info['model_weights']);
            if (isset($info['training_methods'])) unset($info['training_methods']);
            
            // Provide basic public information only
            $info = array(
                'name' => $agent_name,
                'status' => isset($info['status']) ? $info['status'] : 'Active',
                'description' => $this->get_public_agent_description($agent_name),
                'notice' => 'Detailed agent information is restricted to administrators only.'
            );
        }
        
        return $info;
    }
    
    /**
     * Filter audit information based on user capabilities
     */
    public function filter_audit_info($info, $audit_type = '') {
        if (!current_user_can('manage_options') && $audit_type === 'revolutionary') {
            // Remove detailed implementation metrics
            foreach ($info as $key => $audit) {
                if (isset($audit['implementation_details'])) {
                    unset($info[$key]['implementation_details']);
                }
                
                if (isset($audit['test_methodology'])) {
                    unset($info[$key]['test_methodology']);
                }
                
                if (isset($audit['algorithm_assessment'])) {
                    unset($info[$key]['algorithm_assessment']);
                }
            }
            
            // Add disclaimer
            $info['notice'] = 'Detailed audit methodology information is restricted to administrators only.';
        }
        
        return $info;
    }
    
    /**
     * Get public agent description
     */
    private function get_public_agent_description($agent_name) {
        $descriptions = array(
            'HURAII' => 'Advanced AI image generation system for creating unique digital artwork',
            'CLOE' => 'AI-powered market analysis and advertising optimization system',
            'Business_Strategist' => 'Strategic business intelligence agent for marketplace optimization',
            'Thorius' => 'Blockchain integration agent for secure NFT and token management'
        );
        
        return isset($descriptions[$agent_name]) ? $descriptions[$agent_name] : 'AI assistant for marketplace functionality';
    }
    
    /**
     * Secure API endpoints to prevent extraction of algorithm details
     */
    public function secure_api_endpoints($response, $handler, $request) {
        $route = $request->get_route();
        
        // Check if this is a VORTEX-related API endpoint
        if (strpos($route, '/vortex/') === 0) {
            // Identify sensitive routes that contain algorithm details
            $sensitive_routes = array(
                '/vortex/v1/system/architecture',
                '/vortex/v1/agents/structure',
                '/vortex/v1/learning/parameters',
                '/vortex/v1/training/methods',
                '/vortex/v1/algorithms/details'
            );
            
            // Block sensitive routes for non-admin users
            if (in_array($route, $sensitive_routes) || $this->route_matches_sensitive_pattern($route)) {
                if (!current_user_can('manage_options')) {
                    $this->log_security_event('api_access_denied', 'Unauthorized attempt to access sensitive API: ' . $route);
                    
                    return new WP_Error(
                        'vortex_restricted_access',
                        'Access to VORTEX algorithm details is restricted to administrators only.',
                        array('status' => 403)
                    );
                }
            }
        }
        
        return $response;
    }
    
    /**
     * Check if a route matches sensitive patterns
     */
    private function route_matches_sensitive_pattern($route) {
        $sensitive_patterns = array(
            '/\/algorithm/i',
            '/\/neural/i',
            '/\/deep-learning/i',
            '/\/model/i',
            '/\/weights/i',
            '/\/structure/i',
            '/\/training/i'
        );
        
        foreach ($sensitive_patterns as $pattern) {
            if (preg_match($pattern, $route)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Block unauthorized AJAX requests to algorithm details
     */
    public function block_unauthorized_ajax() {
        $this->log_security_event('ajax_access_denied', 'Unauthorized AJAX request for algorithm details');
        
        wp_send_json_error(array(
            'message' => 'Access to VORTEX algorithm details is restricted to administrators only.'
        ), 403);
        
        exit;
    }
    
    /**
     * Filter WordPress debug information to prevent leaking algorithm details
     */
    public function filter_debug_information($info) {
        // Remove VORTEX details from Site Health and other debug outputs
        if (isset($info['vortex'])) {
            if (!current_user_can('manage_options')) {
                unset($info['vortex']);
            }
        }
        
        // Filter other sections that might contain VORTEX details
        if (isset($info['wp-paths-sizes']['fields'])) {
            foreach ($info['wp-paths-sizes']['fields'] as $key => $field) {
                if (strpos($key, 'vortex') !== false) {
                    unset($info['wp-paths-sizes']['fields'][$key]);
                }
            }
        }
        
        return $info;
    }
    
    /**
     * Restrict sensitive database queries
     */
    public function restrict_sensitive_queries($query) {
        // Only restrict queries from unauthorized users
        if (!current_user_can('manage_options')) {
            // Check if query is trying to access sensitive VORTEX tables
            $sensitive_tables = array(
                'vortex_agent_architecture',
                'vortex_neural_network',
                'vortex_model_weights',
                'vortex_training_data',
                'vortex_learning_parameters'
            );
            
            foreach ($sensitive_tables as $table) {
                if (strpos($query, $table) !== false) {
                    global $wpdb;
                    $table_name = $wpdb->prefix . $table;
                    
                    // Log attempted access
                    $this->log_security_event('db_access_denied', 'Unauthorized query to sensitive table: ' . $table);
                    
                    // Return a modified query that won't reveal sensitive data
                    return "SELECT 'access_denied' AS message FROM {$wpdb->prefix}posts LIMIT 0";
                }
            }
        }
        
        return $query;
    }
    
    /**
     * Log security events
     */
    private function log_security_event($event_type, $message) {
        global $wpdb;
        
        // Log to database
        $wpdb->insert(
            $wpdb->prefix . 'vortex_security_log',
            array(
                'event_type' => $event_type,
                'message' => $message,
                'ip_address' => $this->get_client_ip(),
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );
        
        // For critical events, also notify admin
        $critical_events = array('unauthorized_access_attempt', 'api_access_denied');
        
        if (in_array($event_type, $critical_events)) {
            $this->send_security_alert($event_type, $message);
        }
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }
        
        return $ip;
    }
    
    /**
     * Send security alert to admin
     */
    private function send_security_alert($event_type, $message) {
        $admin_email = get_option('admin_email');
        
        $subject = 'VORTEX Security Alert: ' . ucfirst(str_replace('_', ' ', $event_type));
        
        $body = "A security event has been detected in your VORTEX AI Marketplace:\n\n";
        $body .= "Event Type: " . ucfirst(str_replace('_', ' ', $event_type)) . "\n";
        $body .= "Message: " . $message . "\n";
        $body .= "IP Address: " . $this->get_client_ip() . "\n";
        $body .= "Time: " . current_time('mysql') . "\n\n";
        $body .= "Please check your security logs for more information.";
        
        wp_mail($admin_email, $subject, $body);
    }
    
    /**
     * Create security tables
     */
    public function create_security_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Security log table
        $table_name = $wpdb->prefix . 'vortex_security_log';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            message text NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_id bigint(20) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the Security Protocol
add_action('plugins_loaded', function() {
    $security = VORTEX_Security_Protocol::get_instance();
    $security->create_security_tables();
}, 5); // Very high priority to ensure it loads before other components 