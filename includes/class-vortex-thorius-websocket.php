<?php
/**
 * Thorius WebSocket Handler
 * 
 * Enables real-time communication for Thorius
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius WebSocket Handler Class
 */
class Vortex_Thorius_WebSocket {
    
    /**
     * WebSocket server instance
     */
    private $server = null;
    
    /**
     * WebSocket port
     */
    private $port = 8080;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Only initialize server in CLI mode
        if (php_sapi_name() === 'cli') {
            $this->init_server();
        } else {
            // Add client-side code
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        }
    }
    
    /**
     * Initialize WebSocket server
     */
    private function init_server() {
        // Using Ratchet WebSocket library
        // This requires Ratchet to be installed via Composer
        
        // Create socket server
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new ThorusWebSocketHandler()
                )
            ),
            $this->port
        );
        
        $this->server = $server;
        
        // Start the server
        $server->run();
    }
    
    /**
     * Enqueue WebSocket client scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'vortex-thorius-websocket', 
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/thorius-websocket.js',
            array('jquery'),
            VORTEX_AI_MARKETPLACE_VERSION,
            true
        );
        
        // Pass WebSocket settings to JavaScript
        wp_localize_script(
            'vortex-thorius-websocket',
            'vortex_thorius_ws',
            array(
                'enabled' => true,
                'url' => 'ws://' . $_SERVER['HTTP_HOST'] . ':' . $this->port
            )
        );
    }
}

/**
 * WebSocket handler for Thorius
 */
class ThorusWebSocketHandler implements MessageComponentInterface {
    protected $clients;
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }
    
    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection
        $this->clients->attach($conn);
        
        echo "New connection! ({$conn->resourceId})\n";
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n",
            $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
        
        // Decode the message
        $data = json_decode($msg, true);
        
        // Process message
        if ($data && isset($data['action'])) {
            switch ($data['action']) {
                case 'chat':
                    $this->processChat($from, $data);
                    break;
                    
                case 'agent_request':
                    $this->processAgentRequest($from, $data);
                    break;
            }
        }
    }
    
    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it
        $this->clients->detach($conn);
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        
        $conn->close();
    }
    
    private function processChat($from, $data) {
        // Process chat message in real-time
        // This would integrate with the Thorius AI class
        
        // Send response back to client
        $from->send(json_encode([
            'action' => 'chat_response',
            'message' => 'This would be the AI response',
            'timestamp' => time()
        ]));
    }
    
    private function processAgentRequest($from, $data) {
        // Process agent request
        // This would integrate with the Thorius Orchestrator
        
        // Send response back to client
        $from->send(json_encode([
            'action' => 'agent_response',
            'agent' => $data['agent'],
            'data' => ['status' => 'processing']
        ]));
    }
} 