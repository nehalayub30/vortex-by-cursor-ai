<?php
class VORTEX_WebSocket_Handler {
    private static $instance = null;
    private $websocket_server = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize WebSocket server for real-time updates
        add_action('init', array($this, 'initialize_websocket_server'));
        add_action('vortex_blockchain_metrics_updated', array($this, 'broadcast_metrics_update'));
    }
    
    public function initialize_websocket_server() {
        if (!$this->websocket_server) {
            $this->websocket_server = new WebSocketServer([
                'port' => 8080,
                'allowed_origins' => [get_site_url()]
            ]);
            
            $this->websocket_server->on('connection', function($connection) {
                error_log('New WebSocket connection established');
            });
        }
    }
    
    public function broadcast_metrics_update($metrics) {
        if ($this->websocket_server) {
            $this->websocket_server->broadcast(json_encode([
                'type' => 'metrics_update',
                'data' => $metrics
            ]));
        }
    }
} 