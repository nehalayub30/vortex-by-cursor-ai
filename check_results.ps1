# Script to check implementation results
$ErrorActionPreference = "Stop"

# Check repository structure
Write-Host "Checking repository structure..." -ForegroundColor Yellow
Get-ChildItem -Recurse -Directory | Select-Object FullName

# Check if demo files exist
Write-Host "`nChecking demo files..." -ForegroundColor Yellow
if (Test-Path "demo/index.html") {
    Write-Host "Demo index.html exists" -ForegroundColor Green
    # Open in default browser
    Start-Process "demo/index.html"
} else {
    Write-Host "Demo index.html not found" -ForegroundColor Red
}

# Check documentation
Write-Host "`nChecking documentation..." -ForegroundColor Yellow
if (Test-Path "docs") {
    Get-ChildItem -Path "docs" -Recurse -File | Select-Object FullName
} else {
    Write-Host "Documentation folder not found" -ForegroundColor Red
}

# Check GitHub workflow files
Write-Host "`nChecking GitHub workflows..." -ForegroundColor Yellow
if (Test-Path ".github/workflows") {
    Get-ChildItem -Path ".github/workflows" -File | Select-Object Name
} else {
    Write-Host "Workflow files not found" -ForegroundColor Red
}

# Check package.json
Write-Host "`nChecking package.json..." -ForegroundColor Yellow
if (Test-Path "package.json") {
    Get-Content "package.json" | Write-Host
} else {
    Write-Host "package.json not found" -ForegroundColor Red
}

# Offer to open repository in browser
$repoUrl = "https://github.com/MarianneNems/VORTEX-AI-AGENTS"
$response = Read-Host "`nWould you like to open the repository in your browser? (y/n)"
if ($response -eq "y") {
    Start-Process $repoUrl
} 