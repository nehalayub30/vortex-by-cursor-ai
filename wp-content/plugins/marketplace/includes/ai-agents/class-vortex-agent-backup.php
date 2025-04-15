<?php
class VORTEX_Agent_Backup {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('vortex_daily_backup', array($this, 'perform_daily_backup'));
        add_action('vortex_agent_learning_completed', array($this, 'backup_learning_data'));
    }
    
    public function perform_daily_backup() {
        $backup_dir = WP_CONTENT_DIR . '/vortex-backups/ai-agents/';
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        $agents = ['huraii', 'cole', 'business_strategist', 'thorius'];
        
        foreach ($agents as $agent) {
            $this->backup_agent_data($agent, $backup_dir);
        }
        
        // Cleanup old backups (keep last 30 days)
        $this->cleanup_old_backups($backup_dir);
    }
    
    private function backup_agent_data($agent, $backup_dir) {
        global $wpdb;
        
        // Backup learning data
        $learning_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_agent_learning_data WHERE agent_name = %s",
            $agent
        ));
        
        if ($learning_data) {
            $filename = $backup_dir . $agent . '_' . date('Y-m-d') . '.json';
            file_put_contents($filename, json_encode($learning_data));
        }
    }
} 