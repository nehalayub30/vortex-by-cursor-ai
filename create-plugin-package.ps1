#!/usr/bin/env pwsh

# Create temporary directory for plugin
$pluginSlug = "vortex-ai-agents"
$version = "1.0.0"
$tempDir = Join-Path $PWD "temp_$pluginSlug"
$zipFile = "$pluginSlug-$version.zip"

# Create temp directory
New-Item -ItemType Directory -Force -Path $tempDir

# Copy required files and directories
$filesToCopy = @(
    "vortex-ai-agents.php",
    "readme.txt",
    "LICENSE",
    "includes",
    "assets",
    "admin",
    "templates",
    "languages"  # If you have translations
)

foreach ($item in $filesToCopy) {
    if (Test-Path $item) {
        Copy-Item -Path $item -Destination $tempDir -Recurse -Force
    }
}

# Install production dependencies
Set-Location $tempDir
& composer install --no-dev --optimize-autoloader
if ($LASTEXITCODE -ne 0) {
    Write-Error "Composer install failed"
    exit 1
}

# Remove development files
$filesToRemove = @(
    ".git*",
    "node_modules",
    "tests",
    "bin",
    ".github",
    "*.ps1",
    "phpunit*",
    "*.md",
    "composer.*",
    "package*.json"
)

foreach ($item in $filesToRemove) {
    Get-ChildItem -Path $tempDir -Include $item -Recurse -Force | Remove-Item -Force -Recurse
}

# Create ZIP file
Compress-Archive -Path "$tempDir/*" -DestinationPath $zipFile -Force

# Clean up
Remove-Item -Path $tempDir -Recurse -Force

Write-Host "Plugin package created: $zipFile" 