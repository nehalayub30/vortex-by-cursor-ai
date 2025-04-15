<?php
/**
 * Marketplace Plugin Obfuscation Preparation Script
 *
 * This script prepares the critical plugin files for obfuscation.
 * It identifies sensitive files, creates a build directory, and
 * creates a manifest of files to be obfuscated.
 *
 * IMPORTANT: This script does not perform the actual obfuscation.
 * Use a commercial obfuscation tool like IonCube, SourceGuardian, or Zend Guard.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

class VORTEX_Obfuscation_Preparation {
    private $plugin_dir;
    private $build_dir;
    private $sensitive_files = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->plugin_dir = plugin_dir_path(dirname(dirname(__FILE__)));
        $this->build_dir = $this->plugin_dir . 'build/';
        
        // List of sensitive files to obfuscate
        $this->sensitive_files = array(
            // AI Components with sensitive algorithms
            'includes/ai/class-vortex-ai-governance-advisor.php',
            'includes/ai/class-vortex-business-strategist.php',
            
            // DAO Components with financial logic
            'includes/dao/class-vortex-dao-token.php',
            'includes/dao/class-vortex-dao-investment.php',
            
            // Blockchain integration
            'includes/blockchain/class-vortex-tola-integration.php',
            'includes/blockchain/class-vortex-blockchain-manager.php',
            
            // Security components
            'security/class-vortex-command-control.php',
            'security/class-vortex-security.php',
        );
    }
    
    /**
     * Prepare files for obfuscation
     */
    public function prepare() {
        $this->create_build_directory();
        $this->create_obfuscation_manifest();
        $this->prepare_sensitive_files();
        
        echo "Preparation complete. Files are ready for obfuscation.\n";
        echo "Build directory: {$this->build_dir}\n";
        echo "Manifest file: {$this->build_dir}obfuscation-manifest.json\n";
    }
    
    /**
     * Create build directory
     */
    private function create_build_directory() {
        if (!file_exists($this->build_dir)) {
            mkdir($this->build_dir, 0755, true);
        }
        
        // Create subdirectories
        if (!file_exists($this->build_dir . 'to-obfuscate/')) {
            mkdir($this->build_dir . 'to-obfuscate/', 0755, true);
        }
        
        if (!file_exists($this->build_dir . 'obfuscated/')) {
            mkdir($this->build_dir . 'obfuscated/', 0755, true);
        }
    }
    
    /**
     * Create obfuscation manifest
     */
    private function create_obfuscation_manifest() {
        $manifest = array(
            'plugin' => 'VORTEX Marketplace',
            'version' => VORTEX_MARKETPLACE_VERSION,
            'date' => date('Y-m-d H:i:s'),
            'files' => $this->sensitive_files
        );
        
        file_put_contents(
            $this->build_dir . 'obfuscation-manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * Prepare sensitive files
     */
    private function prepare_sensitive_files() {
        foreach ($this->sensitive_files as $file) {
            $source_file = $this->plugin_dir . $file;
            $target_file = $this->build_dir . 'to-obfuscate/' . $file;
            
            // Create target directory if it doesn't exist
            $target_dir = dirname($target_file);
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            
            // Copy the file for obfuscation
            if (file_exists($source_file)) {
                // Read file content
                $content = file_get_contents($source_file);
                
                // Add obfuscation header comment
                $header = "<?php\n";
                $header .= "/**\n";
                $header .= " * VORTEX Marketplace Plugin - Protected File\n";
                $header .= " * This file contains proprietary code and should be obfuscated before distribution.\n";
                $header .= " * \n";
                $header .= " * Original file: {$file}\n";
                $header .= " * Prepared for obfuscation: " . date('Y-m-d H:i:s') . "\n";
                $header .= " */\n\n";
                
                // Remove the original PHP opening tag
                $content = preg_replace('/^<\?php\s+/i', '', $content);
                
                // Combine the header with content
                $processed_content = $header . $content;
                
                // Save the processed file
                file_put_contents($target_file, $processed_content);
                
                echo "Prepared file: {$file}\n";
            } else {
                echo "Warning: Source file not found: {$source_file}\n";
            }
        }
    }
}

// Run preparation when script is executed
function vortex_run_obfuscation_preparation() {
    $preparation = new VORTEX_Obfuscation_Preparation();
    $preparation->prepare();
}

if (isset($_GET['prepare']) && $_GET['prepare'] === 'run') {
    vortex_run_obfuscation_preparation();
} 