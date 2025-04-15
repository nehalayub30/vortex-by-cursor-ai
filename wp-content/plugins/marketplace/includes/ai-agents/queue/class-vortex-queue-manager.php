class VORTEX_Queue_Manager {
    private $queue;
    private $processor;
    
    public function __construct() {
        $this->queue = new VORTEX_Priority_Queue();
        $this->processor = new VORTEX_Queue_Processor();
        
        add_action('init', array($this, 'init_queue_processor'));
    }
    
    public function add_to_queue($task, $priority = 'normal') {
        $task_data = [
            'id' => uniqid('task_'),
            'type' => $task['type'],
            'data' => $task['data'],
            'priority' => $priority,
            'timestamp' => time()
        ];
        
        $this->queue->enqueue($task_data);
        $this->maybe_process_queue();
    }
    
    private function maybe_process_queue() {
        if (!$this->processor->is_processing()) {
            $this->processor->start_processing();
        }
    }
} 