<?php
/**
 * VORTEX SaaS API - AI Computation Endpoint
 * 
 * Handles requests for AI-related computations including artwork analysis,
 * market predictions, and business strategy recommendations.
 *
 * @package VORTEX_SaaS_Backend
 * @subpackage API/Endpoints
 */

// Include necessary configuration and libraries
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/ai/ArtworkAnalyzer.php';
require_once __DIR__ . '/../../lib/ai/MarketPredictor.php';
require_once __DIR__ . '/../../lib/ai/BusinessStrategist.php';

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
    case 'analyze_artwork':
        handleArtworkAnalysis($input);
        break;
        
    case 'predict_market_trends':
        handleMarketPrediction($input);
        break;
        
    case 'get_business_strategy':
        handleBusinessStrategy($input);
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
 * Handles artwork analysis requests
 *
 * @param array $input Request input data
 */
function handleArtworkAnalysis($input) {
    // Validate required parameters
    if (!isset($input['artwork_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing artwork_id parameter']);
        exit();
    }
    
    try {
        // Create artwork analyzer
        $analyzer = new VORTEX_ArtworkAnalyzer();
        
        // Get analysis results
        $results = $analyzer->analyzeArtwork($input['artwork_id']);
        
        // Log API usage
        logApiUsage('analyze_artwork', $input['api_key'], $input);
        
        // Return results
        echo json_encode([
            'success' => true,
            'data' => $results
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Analysis failed',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handles market trend prediction requests
 *
 * @param array $input Request input data
 */
function handleMarketPrediction($input) {
    // Validate required parameters
    if (!isset($input['timeframe'])) {
        $input['timeframe'] = 'short'; // Default to short-term predictions
    }
    
    try {
        // Create market predictor
        $predictor = new VORTEX_MarketPredictor();
        
        // Get prediction results
        $results = $predictor->predictTrends($input['timeframe']);
        
        // Log API usage
        logApiUsage('predict_market_trends', $input['api_key'], $input);
        
        // Return results
        echo json_encode([
            'success' => true,
            'data' => $results
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Prediction failed',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handles business strategy recommendation requests
 *
 * @param array $input Request input data
 */
function handleBusinessStrategy($input) {
    // Validate required parameters
    if (!isset($input['business_profile'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing business_profile parameter']);
        exit();
    }
    
    try {
        // Create business strategist
        $strategist = new VORTEX_BusinessStrategist();
        
        // Get strategy recommendations
        $results = $strategist->getRecommendations($input['business_profile']);
        
        // Log API usage
        logApiUsage('get_business_strategy', $input['api_key'], $input);
        
        // Return results
        echo json_encode([
            'success' => true,
            'data' => $results
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Strategy generation failed',
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