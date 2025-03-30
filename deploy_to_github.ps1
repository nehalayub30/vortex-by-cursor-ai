# Deploy demo to GitHub Pages
$ErrorActionPreference = "Stop"

# We're already in the marketplace directory, no need to change location
Write-Host "Current location: $(Get-Location)" -ForegroundColor Yellow

# Initialize git if not already initialized
if (-not (Test-Path ".git")) {
    Write-Host "Initializing git repository..." -ForegroundColor Yellow
    git init
    git remote add origin https://github.com/MarianneNems/VORTEX-AI-AGENTS.git
}

# Create or switch to gh-pages branch
Write-Host "Setting up gh-pages branch..." -ForegroundColor Yellow
git checkout -b gh-pages

# Ensure demo files are in the correct structure
Write-Host "Organizing demo files..." -ForegroundColor Yellow

# Create root index.html for GitHub Pages
$rootIndexContent = @"
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VORTEX AI AGENTS Demo</title>
    <meta http-equiv="refresh" content="0; url=demo/index.html">
</head>
<body>
    <p>Redirecting to demo...</p>
</body>
</html>
"@

# Create or update root index.html
$rootIndexContent | Out-File -FilePath "index.html" -Force -Encoding UTF8

# Add all files to git
Write-Host "Adding files to repository..." -ForegroundColor Yellow
git add .

# Commit changes
Write-Host "Committing changes..." -ForegroundColor Yellow
git commit -m "Deploy demo to GitHub Pages"

# Push to GitHub
Write-Host "Pushing to GitHub..." -ForegroundColor Yellow
git push origin gh-pages --force

# Output the demo URL
$repoUrl = "https://mariannenems.github.io/VORTEX-AI-AGENTS/"
Write-Host "`nDemo will be live at:" -ForegroundColor Green
Write-Host $repoUrl -ForegroundColor Cyan

# Offer to open demo in browser
$response = Read-Host "`nWould you like to open the repository in your browser? (y/n)"
if ($response -eq "y") {
    Start-Process "https://github.com/MarianneNems/VORTEX-AI-AGENTS"
}

Write-Host "`nNext steps:" -ForegroundColor Yellow
Write-Host "1. Wait a few minutes for GitHub Pages to build" -ForegroundColor Cyan
Write-Host "2. Check repository settings to ensure Pages is enabled" -ForegroundColor Cyan
Write-Host "3. Verify the demo is working correctly" -ForegroundColor Cyan
Write-Host "4. Share the URL with stakeholders" -ForegroundColor Cyan 