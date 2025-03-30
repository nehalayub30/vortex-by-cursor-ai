<?php
/**
 * VORTEX Directory Audit
 * 
 * Performs audit of plugin directory structure and ensures all components are properly organized
 *
 * @package   VORTEX_Marketplace
 * @author    VORTEX Development Team
 * @license   GPL-2.0+
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class VORTEX_Directory_Audit {
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * Expected directory structure
     */
    private $expected_structure = array(
        'includes' => array(
            'ai-agents' => array(
                'class-vortex-huraii.php',
                'class-vortex-cloe.php',
                'class-vortex-business-strategist.php',
                'class-vortex-thorius.php'
            ),
            'blockchain' => array(
                'class-vortex-tola-integration.php',
                'class-vortex-blockchain-metrics.php',
                'class-vortex-smart-contracts.php'
            ),
            'commercial' => array(
                'class-vortex-creator-economy.php',
                'class-vortex-predictive-pricing.php'
            ),
            'gamification' => array(
                'class-vortex-gamification.php',
                'class-vortex-achievements.php',
                'class-vortex-rewards.php'
            ),
            'class-vortex-integrations.php',
            'class-vortex-shortcodes.php',
            'class-vortex-orchestrator.php',
            'class-vortex-security.php',
            'class-vortex-ai-initializer.php',
            'class-vortex-directory-audit.php'
        ),
        'assets' => array(
            'css' => array(),
            'js' => array(),
            'images' => array()
        ),
        'templates' => array(
            'dashboard' => array(),
            'blockchain' => array(),
            'marketplace' => array()
        )
    );
    
    /**
     * Audit results
     */
    private $audit_results = array(
        'missing_files' => array(),
        'missing_directories' => array(),
        'status' => 'pending'
    );
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_init', array($this, 'schedule_regular_audit'));
    }
    
    /**
     * Return an instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
    /**
     * Schedule regular directory audit
     */
    public function schedule_regular_audit() {
        if (!wp_next_scheduled('vortex_directory_audit')) {
            wp_schedule_event(time(), 'daily', 'vortex_directory_audit');
        }
        
        add_action('vortex_directory_audit', array($this, 'perform_audit'));
    }
    
    /**
     * Perform directory audit
     */
    public function perform_audit($auto_fix = false) {
        $plugin_dir = plugin_dir_path(dirname(__FILE__));
        $this->audit_results = array(
            'missing_files' => array(),
            'missing_directories' => array(),
            'status' => 'pending'
        );
        
        $this->audit_directory($plugin_dir, $this->expected_structure, '', $auto_fix);
        
        if (empty($this->audit_results['missing_files']) && empty($this->audit_results['missing_directories'])) {
            $this->audit_results['status'] = 'passed';
        } else {
            $this->audit_results['status'] = 'failed';
        }
        
        update_option('vortex_directory_audit_results', $this->audit_results);
        update_option('vortex_last_directory_audit', current_time('mysql'));
        
        return $this->audit_results;
    }
    
    /**
     * Audit directory recursively
     */
    private function audit_directory($base_path, $expected, $relative_path = '', $auto_fix = false) {
        foreach ($expected as $name => $content) {
            $current_path = $relative_path ? $relative_path . '/' . $name : $name;
            $full_path = $base_path . '/' . $current_path;
            
            if (is_array($content)) {
                // Directory
                if (!file_exists($full_path)) {
                    $this->audit_results['missing_directories'][] = $current_path;
                    
                    if ($auto_fix) {
                        wp_mkdir_p($full_path);
                    }
                }
                
                // Recursively check contents
                $this->audit_directory($base_path, $content, $current_path, $auto_fix);
            } else {
                // File
                if (!file_exists($full_path)) {
                    $this->audit_results['missing_files'][] = $current_path;
                    
                    if ($auto_fix && $this->can_create_template_file($current_path)) {
                        $this->create_template_file($full_path, $current_path);
                    }
                }
            }
        }
    }
    
    /**
     * Check if file can be auto-created
     */
    private function can_create_template_file($file_path) {
        $templates = array(
            'class-vortex-shortcodes.php',
            'class-vortex-ai-initializer.php',
            'class-vortex-blockchain-metrics.php',
            'class-vortex-gamification.php',
            'class-vortex-achievements.php',
            'class-vortex-rewards.php',
            'class-vortex-smart-contracts.php'
        );
        
        $file_name = basename($file_path);
        return in_array($file_name, $templates);
    }
    
    /**
     * Create template file based on file name
     */
    private function create_template_file($full_path, $file_path) {
        $file_name = basename($file_path);
        $class_name = $this->file_to_class_name($file_name);
        $template = $this->get_template_content($class_name, $file_path);
        
        if ($template) {
            $dir = dirname($full_path);
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
            
            file_put_contents($full_path, $template);
            return true;
        }
        
        return false;
    }
    
    /**
     * Convert file name to class name
     */
    private function file_to_class_name($file_name) {
        // Convert class-vortex-example.php to VORTEX_Example
        $name = str_replace('.php', '', $file_name);
        $name = str_replace('class-', '', $name);
        $parts = explode('-', $name);
        
        $class_name = '';
        foreach ($parts as $part) {
            $class_name .= strtoupper(substr($part, 0, 1)) . substr($part, 1) . '_';
        }
        
        return rtrim($class_name, '_');
    }
    
    /**
     * Get template content for a specific class
     */
    private function get_template_content($class_name, $file_path) {
        $template = "<?php\n/**\n * " . str_replace('_', ' ', $class_name) . "\n *\n * @package   VORTEX_Marketplace\n * @author    VORTEX Development Team\n * @license   GPL-2.0+\n */\n\n// If this file is called directly, abort.\nif (!defined('WPINC')) {\n    die;\n}\n\nclass " . $class_name . " {\n    /**\n     * Instance of this class.\n     */\n    protected static \$instance = null;\n    \n    /**\n     * Constructor\n     */\n    private function __construct() {\n        // Initialize component\n    }\n    \n    /**\n     * Return an instance of this class.\n     */\n    public static function get_instance() {\n        if (null == self::\$instance) {\n            self::\$instance = new self;\n        }\n        \n        return self::\$instance;\n    }\n}\n";
        
        return $template;
    }
    
    /**
     * Get latest audit results
     */
    public function get_audit_results() {
        $results = get_option('vortex_directory_audit_results', array(
            'missing_files' => array(),
            'missing_directories' => array(),
            'status' => 'pending'
        ));
        
        $last_audit = get_option('vortex_last_directory_audit', '');
        
        return array(
            'results' => $results,
            'last_audit' => $last_audit
        );
    }
} 