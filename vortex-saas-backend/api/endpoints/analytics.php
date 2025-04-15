<?php
/**
 * VORTEX SaaS API - Analytics Endpoint
 * 
 * Handles requests for marketplace analytics, sales data, and performance metrics.
 *
 * @package VORTEX_SaaS_Backend
 * @subpackage API/Endpoints
 */

// Include necessary configuration and libraries
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/marketplace/MarketAnalytics.php';
require_once __DIR__ . '/../../lib/marketplace/ArtistPerformance.php';
require_once __DIR__ . '/../../lib/marketplace/SalesMetrics.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . VORTEX_ALLOWED_ORIGINS);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

// Validate API key
if (!isset($input['api_key']) || !validateApiKey($input['api_key'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit();
}

// Determine the requested operation
$operation = isset($input['operation']) ? $input['operation'] : '';

switch ($operation) {
    case 'market_overview':
        handleMarketOverview($input);
        break;
        
    case 'artist_performance':
        handleArtistPerformance($input);
        break;
        
    case 'sales_metrics':
        handleSalesMetrics($input);
        break;
        
    case 'trend_analysis':
        handleTrendAnalysis($input);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid operation requested']);
        exit();
}

/**
 * Validates the provided API key against stored keys
 *
 * @param string $apiKey The API key to validate
 * @return bool True if valid, false otherwise
 */
function validateApiKey($apiKey) {
    // In production, this would check against a database of valid keys
    // For now, check against the config
    return isset(VORTEX_API_KEYS[$apiKey]);
}

/**
 * Handles market overview requests
 *
 * @param array $input Request input data
 */
function handleMarketOverview($input) {
    try {
        // Create market analytics
        $analytics = new VORTEX_MarketAnalytics();
        
        // Get date range parameters
        $startDate = isset($input['start_date']) ? $input['start_date'] : date('Y-m-d', strtotime('-30 days'));
        $endDate = isset($input['end_date']) ? $input['end_date'] : date('Y-m-d');
        
        // Get market overview
        $results = $analytics->getMarketOverview($startDate, $endDate);
        
        // Log API usage
        logApiUsage('market_overview', $input['api_key'], $input);
        
        // Return results
        echo json_encode([
            'success' => true,
            'data' => $results
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Analytics failed',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handles artist performance requests
 *
 * @param array $input Request input data
 */
function handleArtistPerformance($input) {
    // Validate required parameters
    if (!isset($input['artist_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing artist_id parameter']);
        exit();
    }
    
    try {
        // Create artist performance analyzer
        $artistAnalytics = new VORTEX_ArtistPerformance();
        
        // Get date range parameters
        $startDate = isset($input['start_date']) ? $input['start_date'] : date('Y-m-d', strtotime('-30 days'));
        $endDate = isset($input['end_date']) ? $input['end_date'] : date('Y-m-d');
        
        // Get artist performance data
        $results = $artistAnalytics->getPerformanceMetrics($input['artist_id'], $startDate, $endDate);
        
        // Log API usage
        logApiUsage('artist_performance', $input['api_key'], $input);
        
        // Return results
        echo json_encode([
            'success' => true,
            'data' => $results
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Analytics failed',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handles sales metrics requests
 *
 * @param array $input Request input data
 */
function handleSalesMetrics($input) {
    try {
        // Create sales metrics analyzer
        $salesMetrics = new VORTEX_SalesMetrics();
        
        // Get date range parameters
        $startDate = isset($input['start_date']) ? $input['start_date'] : date('Y-m-d', strtotime('-30 days'));
        $endDate = isset($input['end_date']) ? $input['end_date'] : date('Y-m-d');
        
        // Get optional filters
        $filters = isset($input['filters']) ? $input['filters'] : [];
        
        // Get sales metrics
        $results = $salesMetrics->getSalesData($startDate, $endDate, $filters);
        
        // Log API usage
        logApiUsage('sales_metrics', $input['api_key'], $input);
        
        // Return results
        echo json_encode([
            'success' => true,
            'data' => $results
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Analytics failed',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handles trend analysis requests
 *
 * @param array $input Request input data
 */
function handleTrendAnalysis($input) {
    try {
        // Create market analytics
        $analytics = new VORTEX_MarketAnalytics();
        
        // Get trend metrics
        $metric = isset($input['metric']) ? $input['metric'] : 'sales';
        $period = isset($input['period']) ? $input['period'] : 'monthly';
        $segment = isset($input['segment']) ? $input['segment'] : null;
        
        // Get trend analysis
        $results = $analytics->getTrendAnalysis($metric, $period, $segment);
        
        // Log API usage
        logApiUsage('trend_analysis', $input['api_key'], $input);
        
        // Return results
        echo json_encode([
            'success' => true,
            'data' => $results
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Analytics failed',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Logs API usage for billing and analytics
 *
 * @param string $operation The operation being performed
 * @param string $apiKey The API key used
 * @param array $input The input parameters
 */
function logApiUsage($operation, $apiKey, $input) {
    // In production, this would log to a database or file
    // For now, just log to a file
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'operation' => $operation,
        'api_key' => $apiKey,
        'request_id' => uniqid(),
        'client_ip' => $_SERVER['REMOTE_ADDR'],
        'request_params' => json_encode(array_diff_key($input, ['api_key' => '']))
    ];
    
    // Append to log file
    file_put_contents(
        __DIR__ . '/../../logs/api_usage.log',
        json_encode($logEntry) . PHP_EOL,
        FILE_APPEND
    );
} 