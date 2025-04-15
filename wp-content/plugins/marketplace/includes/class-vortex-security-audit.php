class VORTEX_Security_Audit {
    public function perform_plugin_security_audit() {
        return [
            'file_permissions' => $this->check_file_permissions(),
            'input_validation' => $this->validate_input_handling(),
            'database_security' => $this->check_database_queries(),
            'api_security' => $this->audit_api_endpoints(),
            'smart_contract_security' => $this->audit_smart_contracts()
        ];
    }
    
    private function check_file_permissions() {
        $plugin_dir = VORTEX_PLUGIN_DIR;
        $security_issues = [];
        
        // Check upload directories
        $upload_dir = wp_upload_dir();
        if (is_writable($upload_dir['basedir'] . '/vortex')) {
            $permissions = substr(sprintf('%o', fileperms($upload_dir['basedir'] . '/vortex')), -4);
            if ((int)$permissions > 0755) {
                $security_issues[] = 'Upload directory has excessive permissions: ' . $permissions;
            }
        }
        
        return [
            'status' => empty($security_issues) ? 'secure' : 'issues_found',
            'issues' => $security_issues
        ];
    }
    
    private function validate_input_handling() {
        global $wpdb;
        
        // Check for proper nonce usage in AJAX handlers
        $ajax_files = glob(VORTEX_PLUGIN_DIR . 'includes/**/ajax-*.php');
        $issues = [];
        
        foreach ($ajax_files as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'check_ajax_referer') === false && 
                strpos($content, 'verify_nonce') === false) {
                $issues[] = 'Missing nonce verification in: ' . basename($file);
            }
        }
        
        return [
            'status' => empty($issues) ? 'secure' : 'issues_found',
            'issues' => $issues
        ];
    }
} 