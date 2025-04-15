class VORTEX_Error_Handler {
    private $logger;
    private $notifier;
    
    public function __construct() {
        $this->logger = new VORTEX_Logger();
        $this->notifier = new VORTEX_Error_Notifier();
        
        set_error_handler(array($this, 'handle_error'));
        set_exception_handler(array($this, 'handle_exception'));
    }
    
    public function handle_error($errno, $errstr, $errfile, $errline) {
        $error_data = [
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'timestamp' => time()
        ];
        
        $this->logger->log_error($error_data);
        $this->notifier->notify_if_critical($error_data);
        
        return true;
    }
} 