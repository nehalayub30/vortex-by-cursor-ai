<?php
/**
 * VORTEX SaaS API - Main Dispatcher
 * 
 * Entry point for all API requests. Routes requests to the appropriate endpoint handler.
 *
 * @package VORTEX_SaaS_Backend
 * @subpackage API
 */

// Include configuration
require_once __DIR__ . '/../config/config.php';

// Define API version and path
define('VORTEX_API_VERSION', '1.0');
define('VORTEX_API_PATH', 'api/v1');

// Parse the request URI
$requestUri = $_SERVER['REQUEST_URI'];
$baseUrl = VORTEX_BASE_URL . '/' . VORTEX_API_PATH . '/';

// Remove the base URL from the request URI
$path = str_replace($baseUrl, '', $requestUri);

// Remove query string if present
if (($pos = strpos($path, '?')) !== false) {
    $path = substr($path, 0, $pos);
}

// Remove trailing slash if present
$path = rtrim($path, '/');

// Route the request to the appropriate endpoint
switch ($path) {
    case 'ai/compute':
        require_once __DIR__ . '/endpoints/ai_compute.php';
        break;
        
    case 'analytics':
        require_once __DIR__ . '/endpoints/analytics.php';
        break;
        
    case 'marketplace':
        require_once __DIR__ . '/endpoints/marketplace.php';
        break;
        
    case 'blockchain':
        require_once __DIR__ . '/endpoints/blockchain.php';
        break;
        
    case 'health':
        // Simple health check endpoint
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'healthy',
            'version' => VORTEX_API_VERSION,
            'timestamp' => time()
        ]);
        break;
        
    default:
        // If no matching endpoint is found, return a 404
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode([
            'error' => 'Endpoint not found',
            'path' => $path
        ]);
}

// Log all API requests
logApiRequest();

/**
 * Logs basic information about each API request
 */
function logApiRequest() {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'path' => $_SERVER['REQUEST_URI'],
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown'
    ];
    
    // Create logs directory if it doesn't exist
    if (!file_exists(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0755, true);
    }
    
    // Append to log file
    file_put_contents(
        __DIR__ . '/../logs/api_requests.log',
        json_encode($logEntry) . PHP_EOL,
        FILE_APPEND
    );
} 