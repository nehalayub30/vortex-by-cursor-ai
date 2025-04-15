class VORTEX_Backup_Manager {
    private $backup_scheduler;
    private $storage_manager;
    
    public function __construct() {
        $this->backup_scheduler = new VORTEX_Backup_Scheduler();
        $this->storage_manager = new VORTEX_Storage_Manager();
        
        add_action('init', array($this, 'schedule_backups'));
    }
    
    public function create_backup($type = 'full') {
        $backup_data = [
            'models' => $this->backup_models(),
            'settings' => $this->backup_settings(),
            'learning_data' => $this->backup_learning_data(),
            'timestamp' => time()
        ];
        
        return $this->storage_manager->store_backup($backup_data);
    }
} 