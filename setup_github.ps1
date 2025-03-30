# Setup GitHub repository and pages
$ErrorActionPreference = "Stop"
$pluginRoot = Get-Location

# Create GitHub specific files
$files = @{
    ".github/workflows/deploy.yml" = @"
name: Deploy to GitHub Pages
on:
  push:
    branches: [ main ]
jobs:
  build-and-deploy:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    - name: Setup Node.js
      uses: actions/setup-node@v2
      with:
        node-version: '16'
    - name: Install dependencies
      run: npm install
    - name: Build demo
      run: npm run build
    - name: Deploy to GitHub Pages
      uses: JamesIves/github-pages-deploy-action@4.1.5
      with:
        branch: gh-pages
        folder: demo
"@

    "demo/index.html" = @"
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VORTEX AI AGENTS Marketplace Demo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>VORTEX AI AGENTS Marketplace</h1>
        <p>AI-powered WordPress Marketplace Solution</p>
    </header>
    
    <main>
        <section class="features">
            <h2>Key Features</h2>
            <div class="feature-grid">
                <div class="feature">
                    <h3>AI Recommendations</h3>
                    <p>Intelligent product suggestions using advanced AI</p>
                </div>
                <div class="feature">
                    <h3>Blockchain Integration</h3>
                    <p>Secure transactions with blockchain technology</p>
                </div>
                <div class="feature">
                    <h3>Market Analysis</h3>
                    <p>Real-time market insights and trends</p>
                </div>
            </div>
        </section>

        <section class="demo">
            <h2>Live Demo</h2>
            <div class="demo-container">
                <!-- Demo content will be added here -->
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 VORTEX AI AGENTS. All rights reserved.</p>
    </footer>
</body>
</html>
"@

    "demo/style.css" = @"
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --accent-color: #e74c3c;
    --text-color: #333;
    --light-bg: #f5f6fa;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    margin: 0;
    padding: 0;
    color: var(--text-color);
}

header {
    background: var(--primary-color);
    color: white;
    text-align: center;
    padding: 4rem 2rem;
}

header h1 {
    margin: 0;
    font-size: 2.5rem;
}

.features {
    padding: 4rem 2rem;
    background: var(--light-bg);
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.feature {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.demo {
    padding: 4rem 2rem;
}

footer {
    background: var(--primary-color);
    color: white;
    text-align: center;
    padding: 1rem;
    position: relative;
    bottom: 0;
    width: 100%;
}
"@
}

# Create directories and files
foreach ($file in $files.Keys) {
    $filePath = Join-Path $pluginRoot $file
    $fileDir = Split-Path $filePath -Parent
    
    if (-not (Test-Path $fileDir)) {
        New-Item -ItemType Directory -Path $fileDir -Force | Out-Null
    }
    
    $files[$file] | Out-File -FilePath $filePath -Force -Encoding UTF8
    Write-Host "Created file: $file" -ForegroundColor Green
}

# Create zip file for GitHub release
$version = "1.0.0"
$zipFileName = "vortex-ai-agents-$version.zip"
Compress-Archive -Path * -DestinationPath $zipFileName -Force

Write-Host "`nNext steps:" -ForegroundColor Yellow
Write-Host "1. Create GitHub repository:" -ForegroundColor Cyan
Write-Host "   https://github.com/new" -ForegroundColor White
Write-Host "`n2. Push to GitHub:" -ForegroundColor Cyan
Write-Host "   git remote add origin https://github.com/MarianneNems/VORTEX-AI-AGENTS.git" -ForegroundColor White
Write-Host "   git branch -M main" -ForegroundColor White
Write-Host "   git push -u origin main" -ForegroundColor White
Write-Host "`n3. Enable GitHub Pages:" -ForegroundColor Cyan
Write-Host "   Go to repository Settings > Pages" -ForegroundColor White
Write-Host "   Select 'gh-pages' branch and '/root' folder" -ForegroundColor White
Write-Host "`n4. Create Release:" -ForegroundColor Cyan
Write-Host "   Go to repository Releases" -ForegroundColor White
Write-Host "   Create new release with tag v$version" -ForegroundColor White
Write-Host "   Upload $zipFileName" -ForegroundColor White

Write-Host "`nDemo will be available at:" -ForegroundColor Green
Write-Host "https://mariannenems.github.io/VORTEX-AI-AGENTS/" -ForegroundColor White 