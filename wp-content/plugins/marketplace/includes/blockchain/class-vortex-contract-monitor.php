<?php
class VORTEX_Contract_Monitor {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('vortex_monitor_contract_performance', array($this, 'monitor_performance'));
        add_action('vortex_contract_interaction', array($this, 'log_interaction'), 10, 2);
    }
    
    public function monitor_performance() {
        global $wpdb;
        
        // Get recent contract interactions
        $interactions = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vortex_contract_interactions 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        
        $metrics = $this->analyze_interactions($interactions);
        
        // Store metrics
        update_option('vortex_contract_performance_metrics', $metrics);
        
        // Alert if performance issues detected
        if ($metrics['avg_response_time'] > 5000 || $metrics['error_rate'] > 0.05) {
            $this->alert_performance_issues($metrics);
        }
    }
    
    public function log_interaction($contract_address, $data) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_contract_interactions',
            array(
                'contract_address' => $contract_address,
                'method' => $data['method'],
                'response_time' => $data['response_time'],
                'status' => $data['status'],
                'gas_used' => $data['gas_used'],
                'created_at' => current_time('mysql')
            )
        );
    }
} 