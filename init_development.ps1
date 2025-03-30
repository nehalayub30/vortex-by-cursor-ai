# Initialize Development Environment
$ErrorActionPreference = "Stop"
$pluginRoot = Get-Location

Write-Host "Initializing VORTEX AI AGENTS development environment..." -ForegroundColor Yellow

try {
    # 1. Initialize Git repository if not already initialized
    if (-not (Test-Path ".git")) {
        Write-Host "Initializing Git repository..." -ForegroundColor Cyan
        git init
        git add .
        git commit -m "Initial commit: Basic plugin structure"
    }

    # 2. Install Node.js dependencies
    Write-Host "`nInstalling Node.js dependencies..." -ForegroundColor Cyan
    npm install --save-dev webpack webpack-cli @babel/core @babel/preset-env babel-loader css-loader style-loader

    # 3. Install Composer dependencies
    Write-Host "`nInstalling Composer dependencies..." -ForegroundColor Cyan
    composer install

    # 4. Create initial class structure
    $initialClasses = @{
        "src/Core/Plugin.php" = @"
<?php
namespace VortexAI\Core;

class Plugin {
    private static \$instance = null;

    public static function getInstance() {
        if (null === self::\$instance) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }

    private function __construct() {
        \$this->initHooks();
    }

    private function initHooks() {
        add_action('plugins_loaded', [\$this, 'loadTextdomain']);
        add_action('admin_enqueue_scripts', [\$this, 'adminAssets']);
        add_action('wp_enqueue_scripts', [\$this, 'frontendAssets']);
    }

    public function loadTextdomain() {
        load_plugin_textdomain('vortex-ai-agents', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function adminAssets() {
        wp_enqueue_style('vortex-ai-admin', plugins_url('admin/css/admin.css', dirname(__FILE__)));
        wp_enqueue_script('vortex-ai-admin', plugins_url('admin/js/admin.js', dirname(__FILE__)), ['jquery'], '1.0.0', true);
    }

    public function frontendAssets() {
        wp_enqueue_style('vortex-ai-main', plugins_url('assets/css/style.css', dirname(__FILE__)));
        wp_enqueue_script('vortex-ai-main', plugins_url('assets/js/main.js', dirname(__FILE__)), ['jquery'], '1.0.0', true);
    }
}
"@

        "src/AI/Manager.php" = @"
<?php
namespace VortexAI\AI;

class Manager {
    private static \$instance = null;

    public static function getInstance() {
        if (null === self::\$instance) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }

    private function __construct() {
        // Initialize AI components
    }

    public function initializeAI() {
        // AI initialization logic
    }
}
"@

        "includes/core/functions.php" = @"
<?php
// Core plugin functions

function vortex_ai_init() {
    return VortexAI\Core\Plugin::getInstance();
}

function vortex_ai_manager() {
    return VortexAI\AI\Manager::getInstance();
}
"@
    }

    # Create class files
    foreach ($file in $initialClasses.Keys) {
        $filePath = Join-Path $pluginRoot $file
        $fileDir = Split-Path $filePath -Parent
        
        if (-not (Test-Path $fileDir)) {
            New-Item -ItemType Directory -Path $fileDir -Force | Out-Null
        }
        
        $initialClasses[$file] | Out-File -FilePath $filePath -Force -Encoding UTF8
        Write-Host "Created class file: $file" -ForegroundColor Green
    }

    # 5. Update main plugin file
    $mainPluginUpdate = @"
<?php
/**
 * Plugin Name: VORTEX AI AGENTS Marketplace
 * Plugin URI: https://github.com/MarianneNems/VORTEX-AI-AGENTS
 * Description: AI-powered marketplace plugin with HURAII system integration
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Marianne Nems
 * License: GPL v2 or later
 */

defined('ABSPATH') || exit;

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Core functions
require_once __DIR__ . '/includes/core/functions.php';

// Initialize plugin
add_action('plugins_loaded', 'vortex_ai_init');
"@

    $mainPluginUpdate | Out-File -FilePath (Join-Path $pluginRoot "marketplace.php") -Force -Encoding UTF8
    Write-Host "`nUpdated main plugin file" -ForegroundColor Green

    Write-Host "`nDevelopment environment initialized successfully!" -ForegroundColor Green
    Write-Host "`nNext steps:" -ForegroundColor Yellow
    Write-Host "1. Run 'npm run dev' to start development mode" -ForegroundColor Cyan
    Write-Host "2. Run 'composer dump-autoload' to update autoloader" -ForegroundColor Cyan
    Write-Host "3. Activate the plugin in WordPress" -ForegroundColor Cyan

} catch {
    Write-Host "Error: $_" -ForegroundColor Red
    exit 1
} 