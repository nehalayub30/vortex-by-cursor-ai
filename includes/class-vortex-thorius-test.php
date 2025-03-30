<?php
/**
 * Thorius Integration Test
 * 
 * Performs tests to ensure all components work together
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Integration Test
 */
class Vortex_Thorius_Test {
    
    /**
     * Run integration tests
     * 
     * @return array Test results
     */
    public function run_tests() {
        $results = array(
            'success' => true,
            'tests' => array()
        );
        
        // Test API connections
        $results['tests']['api_connections'] = $this->test_api_connections();
        
        // Test agent functionality
        $results['tests']['agent_cloe'] = $this->test_agent('cloe');
        $results['tests']['agent_huraii'] = $this->test_agent('huraii');
        $results['tests']['agent_strategist'] = $this->test_agent('strategist');
        
        // Test database tables
        $results['tests']['database_tables'] = $this->test_database_tables();
        
        // Test admin features
        $results['tests']['admin_features'] = $this->test_admin_features();
        
        // Test shortcodes
        $results['tests']['shortcodes'] = $this->test_shortcodes();
        
        // Test intelligence features
        $results['tests']['intelligence'] = $this->test_intelligence();
        
        // Check for failed tests
        foreach ($results['tests'] as $test) {
            if (!$test['success']) {
                $results['success'] = false;
                break;
            }
        }
        
        return $results;
    }
    
    /**
     * Test API connections
     * 
     * @return array Test result
     */
    private function test_api_connections() {
        $result = array(
            'success' => true,
            'message' => __('All API connections are working correctly.', 'vortex-ai-marketplace'),
            'details' => array()
        );
        
        // Test OpenAI API
        $openai_result = $this->test_openai_connection();
        $result['details']['openai'] = $openai_result;
        
        if (!$openai_result['success']) {
            $result['success'] = false;
            $result['message'] = __('Issues with API connections detected.', 'vortex-ai-marketplace');
        }
        
        // Test Stability API if used
        $stability_result = $this->test_stability_connection();
        $result['details']['stability'] = $stability_result;
        
        if (!$stability_result['success']) {
            $result['success'] = false;
            $result['message'] = __('Issues with API connections detected.', 'vortex-ai-marketplace');
        }
        
        return $result;
    }
    
    /**
     * Test OpenAI API connection
     * 
     * @return array Test result
     */
    private function test_openai_connection() {
        $api_key = get_option('vortex_thorius_openai_key');
        
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => __('OpenAI API key is not configured.', 'vortex-ai-marketplace')
            );
        }
        
        // Decrypt the API key if it's encrypted
        if (function_exists('openssl_decrypt') && defined('VORTEX_THORIUS_ENCRYPTION_KEY')) {
            $api_key = $this->decrypt_api_key($api_key);
        }
        
        try {
            // Initialize API Manager
            require_once plugin_dir_path(__FILE__) . 'api/class-vortex-thorius-api-manager.php';
            $api_manager = new Vortex_Thorius_API_Manager();
            
            // Test connection with a minimal request
            $response = $api_manager->test_openai_connection($api_key);
            
            if (isset($response['error'])) {
                return array(
                    'success' => false,
                    'message' => sprintf(__('OpenAI API Error: %s', 'vortex-ai-marketplace'), $response['error'])
                );
            }
            
            return array(
                'success' => true,
                'message' => __('OpenAI API connection successful.', 'vortex-ai-marketplace')
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => sprintf(__('OpenAI API Error: %s', 'vortex-ai-marketplace'), $e->getMessage())
            );
        }
    }
    
    /**
     * Test Stability API connection
     * 
     * @return array Test result
     */
    private function test_stability_connection() {
        $api_key = get_option('vortex_thorius_stability_key');
        
        if (empty($api_key)) {
            // Stability API is optional, so not having it isn't a failure
            return array(
                'success' => true,
                'message' => __('Stability API key is not configured (optional).', 'vortex-ai-marketplace')
            );
        }
        
        // Decrypt the API key if it's encrypted
        if (function_exists('openssl_decrypt') && defined('VORTEX_THORIUS_ENCRYPTION_KEY')) {
            $api_key = $this->decrypt_api_key($api_key);
        }
        
        try {
            // Initialize API Manager
            require_once plugin_dir_path(__FILE__) . 'api/class-vortex-thorius-api-manager.php';
            $api_manager = new Vortex_Thorius_API_Manager();
            
            // Test connection with a minimal request
            $response = $api_manager->test_stability_connection($api_key);
            
            if (isset($response['error'])) {
                return array(
                    'success' => false,
                    'message' => sprintf(__('Stability API Error: %s', 'vortex-ai-marketplace'), $response['error'])
                );
            }
            
            return array(
                'success' => true,
                'message' => __('Stability API connection successful.', 'vortex-ai-marketplace')
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => sprintf(__('Stability API Error: %s', 'vortex-ai-marketplace'), $e->getMessage())
            );
        }
    }
    
    /**
     * Test agent functionality
     * 
     * @param string $agent Agent to test
     * @return array Test result
     */
    private function test_agent($agent) {
        $result = array(
            'success' => true,
            'message' => sprintf(__('%s agent is functioning correctly.', 'vortex-ai-marketplace'), ucfirst($agent)),
            'details' => array()
        );
        
        // Check if agent is enabled
        $enabled = get_option('vortex_thorius_enable_' . $agent, true);
        if (!$enabled) {
            return array(
                'success' => true,
                'message' => sprintf(__('%s agent is disabled (skipped).', 'vortex-ai-marketplace'), ucfirst($agent))
            );
        }
        
        try {
            // Initialize Deep Learning
            require_once plugin_dir_path(__FILE__) . 'agents/class-vortex-thorius-deep-learning.php';
            $deep_learning = new Vortex_Thorius_Deep_Learning();
            
            // Test with a simple query
            $test_query = 'This is a test query for ' . $agent . '. Please respond with a simple confirmation.';
            
            $method = 'process_with_' . $agent;
            if (!method_exists($deep_learning, $method)) {
                return array(
                    'success' => false,
                    'message' => sprintf(__('Method %s not found.', 'vortex-ai-marketplace'), $method)
                );
            }
            
            $response = $deep_learning->$method($test_query);
            
            if (!isset($response['success']) || !$response['success']) {
                $result['success'] = false;
                $result['message'] = sprintf(__('%s agent is not functioning correctly.', 'vortex-ai-marketplace'), ucfirst($agent));
                $result['details']['error'] = isset($response['message']) ? $response['message'] : __('Unknown error', 'vortex-ai-marketplace');
            } else {
                $result['details']['response'] = substr($response['response'], 0, 100) . '...';
            }
        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = sprintf(__('%s agent error: %s', 'vortex-ai-marketplace'), ucfirst($agent), $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Test database tables
     * 
     * @return array Test result
     */
    private function test_database_tables() {
        global $wpdb;
        
        $result = array(
            'success' => true,
            'message' => __('All required database tables exist.', 'vortex-ai-marketplace'),
            'details' => array()
        );
        
        // List of required tables
        $required_tables = array(
            'vortex_thorius_analytics',
            'vortex_thorius_sessions',
            'vortex_thorius_conversations',
            'vortex_thorius_cache',
            'vortex_thorius_security_log'
        );
        
        foreach ($required_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            
            $result['details'][$table] = array(
                'success' => $table_exists,
                'message' => $table_exists 
                    ? sprintf(__('Table %s exists.', 'vortex-ai-marketplace'), $table_name)
                    : sprintf(__('Table %s does not exist.', 'vortex-ai-marketplace'), $table_name)
            );
            
            if (!$table_exists) {
                $result['success'] = false;
                $result['message'] = __('Some required database tables are missing.', 'vortex-ai-marketplace');
            }
        }
        
        return $result;
    }
    
    /**
     * Test admin features
     * 
     * @return array Test result
     */
    private function test_admin_features() {
        $result = array(
            'success' => true,
            'message' => __('Admin features are available and functional.', 'vortex-ai-marketplace'),
            'details' => array()
        );
        
        // Check if admin class is loaded
        require_once plugin_dir_path(__FILE__) . 'admin/class-vortex-thorius-admin.php';
        $admin_class_exists = class_exists('Vortex_Thorius_Admin');
        
        $result['details']['admin_class'] = array(
            'success' => $admin_class_exists,
            'message' => $admin_class_exists 
                ? __('Admin class is available.', 'vortex-ai-marketplace')
                : __('Admin class is not available.', 'vortex-ai-marketplace')
        );
        
        if (!$admin_class_exists) {
            $result['success'] = false;
            $result['message'] = __('Some admin features are not available.', 'vortex-ai-marketplace');
        }
        
        return $result;
    }
    
    /**
     * Test shortcodes
     * 
     * @return array Test result
     */
    private function test_shortcodes() {
        $result = array(
            'success' => true,
            'message' => __('All shortcodes are registered and functional.', 'vortex-ai-marketplace'),
            'details' => array()
        );
        
        // Check if shortcodes are registered
        $shortcodes = array(
            'thorius_concierge',
            'thorius_chat',
            'thorius_agent'
        );
        
        global $shortcode_tags;
        
        foreach ($shortcodes as $shortcode) {
            $registered = isset($shortcode_tags[$shortcode]);
            
            $result['details'][$shortcode] = array(
                'success' => $registered,
                'message' => $registered 
                    ? sprintf(__('Shortcode [%s] is registered.', 'vortex-ai-marketplace'), $shortcode)
                    : sprintf(__('Shortcode [%s] is not registered.', 'vortex-ai-marketplace'), $shortcode)
            );
            
            if (!$registered) {
                $result['success'] = false;
                $result['message'] = __('Some shortcodes are not registered.', 'vortex-ai-marketplace');
            }
        }
        
        return $result;
    }
    
    /**
     * Test intelligence features
     * 
     * @return array Test result
     */
    private function test_intelligence() {
        $result = array(
            'success' => true,
            'message' => __('Intelligence features are available and functional.', 'vortex-ai-marketplace'),
            'details' => array()
        );
        
        // Test synthesis reports
        require_once plugin_dir_path(__FILE__) . 'admin/class-vortex-thorius-synthesis-reports.php';
        $synthesis_reports = new Vortex_Thorius_Synthesis_Reports();
        
        $synthesis_available = method_exists($synthesis_reports, 'generate_report');
        
        $result['details']['synthesis'] = array(
            'success' => $synthesis_available,
            'message' => $synthesis_available 
                ? __('Synthesis reports functionality is available.', 'vortex-ai-marketplace')
                : __('Synthesis reports functionality is not available.', 'vortex-ai-marketplace')
        );
        
        if (!$synthesis_available) {
            $result['success'] = false;
            $result['message'] = __('Some intelligence features are not available.', 'vortex-ai-marketplace');
        }
        
        // Test admin intelligence
        require_once plugin_dir_path(__FILE__) . 'admin/class-vortex-thorius-admin-intelligence.php';
        $admin_intelligence = new Vortex_Thorius_Admin_Intelligence();
        
        $intelligence_available = method_exists($admin_intelligence, 'process_admin_query');
        
        $result['details']['intelligence'] = array(
            'success' => $intelligence_available,
            'message' => $intelligence_available 
                ? __('Admin intelligence functionality is available.', 'vortex-ai-marketplace')
                : __('Admin intelligence functionality is not available.', 'vortex-ai-marketplace')
        );
        
        if (!$intelligence_available) {
            $result['success'] = false;
            $result['message'] = __('Some intelligence features are not available.', 'vortex-ai-marketplace');
        }
        
        return $result;
    }
    
    /**
     * Decrypt API key
     * 
     * @param string $encrypted_key Encrypted API key
     * @return string Decrypted API key
     */
    private function decrypt_api_key($encrypted_key) {
        if (empty($encrypted_key) || !function_exists('openssl_decrypt') || !defined('VORTEX_THORIUS_ENCRYPTION_KEY')) {
            return $encrypted_key;
        }
        
        $method = 'AES-256-CBC';
        $iv = substr(VORTEX_THORIUS_ENCRYPTION_KEY, 0, 16);
        
        $decrypted = openssl_decrypt(base64_decode($encrypted_key), $method, VORTEX_THORIUS_ENCRYPTION_KEY, 0, $iv);
        
        return $decrypted !== false ? $decrypted : $encrypted_key;
    }
} 