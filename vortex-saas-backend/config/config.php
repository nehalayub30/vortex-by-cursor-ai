<?php
/**
 * VORTEX SaaS Backend Configuration
 * 
 * Main configuration file for the VORTEX SaaS Backend.
 * This file contains configuration settings for database connections,
 * API keys, and other environment-specific settings.
 *
 * @package VORTEX_SaaS_Backend
 * @subpackage Configuration
 */

// Load environment variables from .env file if available
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// Database configuration
define('VORTEX_DB_HOST', getenv('VORTEX_DB_HOST') ?: 'localhost');
define('VORTEX_DB_NAME', getenv('VORTEX_DB_NAME') ?: 'vortex_saas');
define('VORTEX_DB_USER', getenv('VORTEX_DB_USER') ?: 'vortex_user');
define('VORTEX_DB_PASS', getenv('VORTEX_DB_PASS') ?: 'vortex_password');
define('VORTEX_DB_CHARSET', 'utf8mb4');

// API configuration
define('VORTEX_BASE_URL', getenv('VORTEX_BASE_URL') ?: 'https://api.vortexmarketplace.io');
define('VORTEX_ALLOWED_ORIGINS', getenv('VORTEX_ALLOWED_ORIGINS') ?: '*');
define('VORTEX_API_RATE_LIMIT', getenv('VORTEX_API_RATE_LIMIT') ?: 100); // Requests per minute

// External API keys
define('VORTEX_OPENAI_API_KEY', getenv('VORTEX_OPENAI_API_KEY') ?: '');
define('VORTEX_ANTHROPIC_API_KEY', getenv('VORTEX_ANTHROPIC_API_KEY') ?: '');
define('VORTEX_HUGGINGFACE_API_KEY', getenv('VORTEX_HUGGINGFACE_API_KEY') ?: '');
define('VORTEX_STABILITY_API_KEY', getenv('VORTEX_STABILITY_API_KEY') ?: '');

// Blockchain configuration
define('VORTEX_BLOCKCHAIN_PROVIDER', getenv('VORTEX_BLOCKCHAIN_PROVIDER') ?: 'solana');
define('VORTEX_BLOCKCHAIN_NETWORK', getenv('VORTEX_BLOCKCHAIN_NETWORK') ?: 'devnet');
define('VORTEX_BLOCKCHAIN_RPC_URL', getenv('VORTEX_BLOCKCHAIN_RPC_URL') ?: 'https://api.devnet.solana.com');

// Cache configuration
define('VORTEX_CACHE_ENABLED', getenv('VORTEX_CACHE_ENABLED') ?: true);
define('VORTEX_CACHE_LIFETIME', getenv('VORTEX_CACHE_LIFETIME') ?: 3600); // Seconds

// Logging configuration
define('VORTEX_LOG_LEVEL', getenv('VORTEX_LOG_LEVEL') ?: 'info'); // debug, info, warning, error
define('VORTEX_LOG_DIR', __DIR__ . '/../logs');

// API keys that are allowed to access the API (key => role mapping)
const VORTEX_API_KEYS = [
    // Replace these with real API keys in production or use environment variables
    'vx_test_key_123456' => 'test',
    'vx_development_key_abcdef' => 'development',
    getenv('VORTEX_PRODUCTION_API_KEY') => 'production',
];

// Error reporting
if (getenv('VORTEX_ENVIRONMENT') === 'production') {
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Initialize database connection
function getDbConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $dsn = "mysql:host=" . VORTEX_DB_HOST . ";dbname=" . VORTEX_DB_NAME . ";charset=" . VORTEX_DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $conn = new PDO($dsn, VORTEX_DB_USER, VORTEX_DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error
            error_log('Database connection failed: ' . $e->getMessage());
            
            // In production, you wouldn't want to expose the error details
            if (getenv('VORTEX_ENVIRONMENT') !== 'production') {
                throw new Exception('Database connection failed: ' . $e->getMessage());
            } else {
                throw new Exception('Database connection failed. Please try again later.');
            }
        }
    }
    
    return $conn;
} 