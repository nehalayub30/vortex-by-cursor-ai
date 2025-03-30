# Create/verify essential gh-pages content
$ErrorActionPreference = "Stop"
$pluginRoot = Get-Location

# Essential files structure
$files = @{
    "index.html" = @"
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VORTEX AI AGENTS Marketplace</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>VORTEX AI AGENTS Marketplace</h1>
        <nav>
            <a href="#features">Features</a>
            <a href="#demo">Demo</a>
            <a href="docs/">Documentation</a>
            <a href="https://github.com/MarianneNems/VORTEX-AI-AGENTS">GitHub</a>
        </nav>
    </header>

    <main>
        <section id="features">
            <h2>Key Features</h2>
            <div class="feature-grid">
                <div class="feature">
                    <h3>AI Integration</h3>
                    <p>Advanced AI-powered marketplace functionality</p>
                </div>
                <div class="feature">
                    <h3>HURAII System</h3>
                    <p>Intelligent decision-making system</p>
                </div>
                <div class="feature">
                    <h3>Blockchain Ready</h3>
                    <p>Secure transaction processing</p>
                </div>
            </div>
        </section>

        <section id="demo">
            <h2>Live Demo</h2>
            <div class="demo-container">
                <p>Coming Soon!</p>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Marianne Nems - VORTEX AI AGENTS</p>
    </footer>
</body>
</html>
"@

    "style.css" = @"
:root {
    --primary: #2c3e50;
    --secondary: #3498db;
    --accent: #e74c3c;
    --background: #f5f6fa;
    --text: #333;
}

body {
    margin: 0;
    font-family: 'Segoe UI', system-ui, sans-serif;
    line-height: 1.6;
    background: var(--background);
    color: var(--text);
}

header {
    background: var(--primary);
    color: white;
    padding: 2rem;
    text-align: center;
}

nav {
    margin-top: 1rem;
}

nav a {
    color: white;
    text-decoration: none;
    margin: 0 1rem;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    transition: background 0.3s;
}

nav a:hover {
    background: var(--secondary);
}

main {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.feature {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

footer {
    background: var(--primary);
    color: white;
    text-align: center;
    padding: 1rem;
    margin-top: 4rem;
}
"@

    "docs/index.html" = @"
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VORTEX AI AGENTS - Documentation</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <header>
        <h1>VORTEX AI AGENTS Documentation</h1>
        <nav>
            <a href="../">Home</a>
            <a href="#installation">Installation</a>
            <a href="#api">API</a>
            <a href="#support">Support</a>
        </nav>
    </header>

    <main>
        <section id="installation">
            <h2>Installation</h2>
            <p>Detailed installation instructions coming soon!</p>
        </section>

        <section id="api">
            <h2>API Documentation</h2>
            <p>API documentation coming soon!</p>
        </section>

        <section id="support">
            <h2>Support</h2>
            <p>For support, please visit our <a href="https://github.com/MarianneNems/VORTEX-AI-AGENTS/issues">GitHub Issues</a> page.</p>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Marianne Nems - VORTEX AI AGENTS</p>
    </footer>
</body>
</html>
"@
}

# Create files
foreach ($file in $files.Keys) {
    $filePath = Join-Path $pluginRoot $file
    $fileDir = Split-Path $filePath -Parent
    
    if (-not (Test-Path $fileDir)) {
        New-Item -ItemType Directory -Path $fileDir -Force | Out-Null
        Write-Host "Created directory: $fileDir" -ForegroundColor Green
    }
    
    $files[$file] | Out-File -FilePath $filePath -Force -Encoding UTF8
    Write-Host "Created/Updated file: $file" -ForegroundColor Green
}

# Commit and push changes
git add .
git commit -m "Update gh-pages content"
git push origin gh-pages

Write-Host "`nGitHub Pages content updated!" -ForegroundColor Green
Write-Host "Your site should be available at: https://mariannenems.github.io/VORTEX-AI-AGENTS/" -ForegroundColor Yellow
Write-Host "Please allow a few minutes for changes to propagate." -ForegroundColor Yellow 