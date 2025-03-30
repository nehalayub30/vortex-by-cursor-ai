# Full Project Structure Setup
$ErrorActionPreference = "Stop"
$pluginRoot = Get-Location

Write-Host "Setting up VORTEX AI AGENTS structure at: $pluginRoot" -ForegroundColor Yellow

try {
    # Define complete directory structure
    $directories = @(
        # Core directories
        "admin/css",
        "admin/js",
        "admin/templates",
        "assets/css",
        "assets/images",
        "assets/js",
        "dist",
        "includes/ai",
        "includes/analytics",
        "includes/api",
        "includes/blockchain",
        "includes/core",
        "includes/data",
        "includes/frontend",
        "includes/services",
        "src/Admin",
        "src/AI",
        "src/Blockchain",
        "src/Core",
        "src/Frontend",
        "templates/admin",
        "templates/emails",
        "templates/frontend",
        "tests/unit",
        "tests/integration",
        "vendor"
    )

    # Create directories
    foreach ($dir in $directories) {
        $dirPath = Join-Path $pluginRoot $dir
        if (-not (Test-Path $dirPath)) {
            New-Item -ItemType Directory -Path $dirPath -Force | Out-Null
            Write-Host "Created directory: $dir" -ForegroundColor Green
        }
    }

    # Create core files
    $coreFiles = @{
        "marketplace.php" = @"
<?php
/**
 * Plugin Name: VORTEX AI AGENTS Marketplace
 * Plugin URI: https://github.com/MarianneNems/VORTEX-AI-AGENTS
 * Description: AI-powered marketplace plugin with HURAII system integration
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Marianne Nems (aka Mariana Villard, all rights reserved Feb 26-2025)
 * Author URI: https://vortexartec.com
 * License: GPL v2 or later
 */

defined('ABSPATH') || exit;
"@

        "composer.json" = @"
{
    "name": "mariannenems/vortex-ai-agents",
    "description": "VORTEX AI AGENTS Marketplace",
    "type": "wordpress-plugin",
    "require": {
        "php": ">=7.4"
    },
    "autoload": {
        "psr-4": {
            "VortexAI\\": "src/"
        }
    }
}
"@

        "package.json" = @"
{
    "name": "vortex-ai-agents",
    "version": "1.0.0",
    "scripts": {
        "build": "webpack --mode production",
        "dev": "webpack --mode development --watch"
    }
}
"@

        ".gitignore" = @"
/vendor/
/node_modules/
/dist/
.DS_Store
*.log
"@

        "readme.txt" = @"
=== VORTEX AI AGENTS Marketplace ===
Contributors: mariannenems
Tags: ai, marketplace, vortex, huraii
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

VORTEX AI AGENTS Marketplace with HURAII system integration.
"@
    }

    # Create core files
    foreach ($file in $coreFiles.Keys) {
        $filePath = Join-Path $pluginRoot $file
        $coreFiles[$file] | Out-File -FilePath $filePath -Force -Encoding UTF8
        Write-Host "Created file: $file" -ForegroundColor Green
    }

    # Create asset files
    $assetFiles = @{
        "assets/js/main.js" = "console.log('VORTEX AI AGENTS - Main');"
        "assets/css/style.css" = "/* VORTEX AI AGENTS Styles */"
        "admin/js/admin.js" = "console.log('VORTEX AI AGENTS - Admin');"
        "admin/css/admin.css" = "/* VORTEX AI AGENTS Admin Styles */"
    }

    # Create asset files
    foreach ($file in $assetFiles.Keys) {
        $filePath = Join-Path $pluginRoot $file
        $assetFiles[$file] | Out-File -FilePath $filePath -Force -Encoding UTF8
        Write-Host "Created file: $file" -ForegroundColor Green
    }

    # Add index.php files for security
    Get-ChildItem -Path $pluginRoot -Directory -Recurse | ForEach-Object {
        $indexPath = Join-Path $_.FullName "index.php"
        if (-not (Test-Path $indexPath)) {
            "<?php // Silence is golden" | Out-File -FilePath $indexPath -Force -Encoding UTF8
        }
    }

    Write-Host "`nStructure created successfully!" -ForegroundColor Green
    
    # Verify structure
    Write-Host "`nVerifying structure..." -ForegroundColor Yellow
    $missingItems = @()
    
    foreach ($dir in $directories) {
        $dirPath = Join-Path $pluginRoot $dir
        if (-not (Test-Path $dirPath)) {
            $missingItems += "Directory: $dir"
        }
    }
    
    foreach ($file in $coreFiles.Keys) {
        $filePath = Join-Path $pluginRoot $file
        if (-not (Test-Path $filePath)) {
            $missingItems += "File: $file"
        }
    }
    
    if ($missingItems.Count -gt 0) {
        Write-Host "`nMissing items:" -ForegroundColor Red
        $missingItems | ForEach-Object {
            Write-Host "- $_" -ForegroundColor Red
        }
    } else {
        Write-Host "`nAll items verified successfully!" -ForegroundColor Green
    }

} catch {
    Write-Host "Error: $_" -ForegroundColor Red
    exit 1
} 