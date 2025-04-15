class VORTEX_HURAII_Monitor {
    private $metrics_collector;
    private $status_checker;
    private $alert_system;
    
    public function __construct() {
        $this->metrics_collector = new VORTEX_Metrics_Collector();
        $this->status_checker = new VORTEX_Status_Checker();
        $this->alert_system = new VORTEX_Alert_System();
        
        add_action('init', array($this, 'init_websocket_server'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_monitor_assets'));
    }
    
    public function get_realtime_status() {
        return [
            'system_health' => $this->status_checker->check_system_health(),
            'performance_metrics' => $this->metrics_collector->get_current_metrics(),
            'resource_usage' => $this->get_resource_usage(),
            'active_processes' => $this->get_active_processes(),
            'queue_status' => $this->get_queue_status(),
            'learning_progress' => $this->get_learning_progress()
        ];
    }
    
    private function get_resource_usage() {
        return [
            'cpu_usage' => sys_getloadavg(),
            'memory_usage' => memory_get_usage(true),
            'gpu_usage' => $this->status_checker->get_gpu_usage(),
            'disk_usage' => disk_free_space(ABSPATH)
        ];
    }
} 