# Setup demo and screenshots
$ErrorActionPreference = "Stop"

Write-Host "Setting up demo and screenshots..." -ForegroundColor Yellow

# Create demo directory
New-Item -ItemType Directory -Path "demo" -Force | Out-Null
New-Item -ItemType Directory -Path "demo/screenshots" -Force | Out-Null

# Create demo files
$demoFiles = @{
    "demo/index.html" = @"
<!DOCTYPE html>
<html>
<head>
    <title>VORTEX AI AGENTS Demo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>VORTEX AI AGENTS Marketplace</h1>
    <div class="screenshots">
        <img src="screenshots/dashboard.png" alt="AI Dashboard">
        <img src="screenshots/recommendations.png" alt="Product Recommendations">
        <img src="screenshots/analysis.png" alt="Market Analysis">
    </div>
</body>
</html>
"@

    "demo/style.css" = @"
body { font-family: Arial; margin: 20px; }
.screenshots { display: grid; gap: 20px; margin: 20px 0; }
img { max-width: 100%; border: 1px solid #ddd; }
"@
}

# Create files
foreach ($file in $demoFiles.Keys) {
    $demoFiles[$file] | Out-File -FilePath $file -Force -Encoding UTF8
    Write-Host "Created: $file" -ForegroundColor Green
}

Write-Host "`nRequired Screenshots:" -ForegroundColor Yellow
Write-Host "1. dashboard.png - AI Dashboard view" -ForegroundColor Cyan
Write-Host "2. recommendations.png - Product recommendations" -ForegroundColor Cyan
Write-Host "3. analysis.png - Market analysis view" -ForegroundColor Cyan

Write-Host "`nTo view the demo:" -ForegroundColor Yellow
Write-Host "1. Add your screenshots to the 'demo/screenshots' folder" -ForegroundColor Cyan
Write-Host "2. Open demo/index.html in your browser" -ForegroundColor Cyan

# Offer to open demo folder
$response = Read-Host "`nWould you like to open the demo folder? (y/n)"
if ($response -eq "y") {
    explorer.exe "demo"
} 