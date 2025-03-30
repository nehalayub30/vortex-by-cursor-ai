# Create complete demo with samples
$ErrorActionPreference = "Stop"

Write-Host "Creating complete demo with samples..." -ForegroundColor Yellow

# Create directories
$directories = @(
    "demo",
    "demo/screenshots",
    "demo/css",
    "demo/js",
    "demo/images"
)

foreach ($dir in $directories) {
    New-Item -ItemType Directory -Path $dir -Force | Out-Null
    Write-Host "Created directory: $dir" -ForegroundColor Green
}

# Create demo HTML
$demoHTML = @"
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VORTEX AI AGENTS - Live Demo</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav>
        <div class="logo">VORTEX AI</div>
        <div class="nav-links">
            <a href="#dashboard">Dashboard</a>
            <a href="#features">Features</a>
            <a href="#demo">Demo</a>
        </div>
    </nav>

    <header>
        <h1>VORTEX AI AGENTS Marketplace</h1>
        <p>AI-Powered WordPress Marketplace Solution</p>
    </header>

    <main>
        <section id="dashboard" class="dashboard">
            <h2>AI Dashboard</h2>
            <div class="dashboard-grid">
                <div class="card">
                    <i class="fas fa-robot"></i>
                    <h3>AI Insights</h3>
                    <p>Real-time market analysis</p>
                </div>
                <div class="card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Analytics</h3>
                    <p>Performance metrics</p>
                </div>
                <div class="card">
                    <i class="fas fa-brain"></i>
                    <h3>HURAII System</h3>
                    <p>Intelligent decisions</p>
                </div>
            </div>
        </section>

        <section id="features" class="features">
            <h2>Key Features</h2>
            <div class="feature-grid">
                <div class="feature">
                    <img src="screenshots/ai-recommendations.png" alt="AI Recommendations">
                    <h3>Smart Recommendations</h3>
                </div>
                <div class="feature">
                    <img src="screenshots/market-analysis.png" alt="Market Analysis">
                    <h3>Market Analysis</h3>
                </div>
                <div class="feature">
                    <img src="screenshots/blockchain.png" alt="Blockchain">
                    <h3>Blockchain Integration</h3>
                </div>
            </div>
        </section>

        <section id="demo" class="live-demo">
            <h2>Live Demo</h2>
            <div class="demo-container">
                <div class="demo-nav">
                    <button class="active" data-view="dashboard">Dashboard</button>
                    <button data-view="products">Products</button>
                    <button data-view="analytics">Analytics</button>
                </div>
                <div class="demo-content">
                    <div class="demo-view active" id="demo-dashboard">
                        <!-- Dashboard content -->
                    </div>
                    <div class="demo-view" id="demo-products">
                        <!-- Products content -->
                    </div>
                    <div class="demo-view" id="demo-analytics">
                        <!-- Analytics content -->
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 VORTEX AI AGENTS</p>
    </footer>

    <script src="js/demo.js"></script>
</body>
</html>
"@

# Create CSS
$demoCSS = @"
:root {
    --primary: #2c3e50;
    --secondary: #3498db;
    --accent: #e74c3c;
    --background: #f5f6fa;
    --text: #2d3436;
}

body {
    margin: 0;
    font-family: 'Segoe UI', system-ui, sans-serif;
    background: var(--background);
    color: var(--text);
}

nav {
    background: var(--primary);
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
}

.nav-links a {
    color: white;
    text-decoration: none;
    margin-left: 2rem;
}

header {
    text-align: center;
    padding: 4rem 2rem;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    padding: 2rem;
}

.card {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.card i {
    font-size: 2rem;
    color: var(--secondary);
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    padding: 2rem;
}

.feature img {
    width: 100%;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.live-demo {
    padding: 2rem;
}

.demo-nav {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.demo-nav button {
    padding: 0.5rem 1rem;
    border: none;
    background: var(--primary);
    color: white;
    border-radius: 4px;
    cursor: pointer;
}

.demo-nav button.active {
    background: var(--secondary);
}

.demo-view {
    display: none;
    padding: 2rem;
    background: white;
    border-radius: 8px;
}

.demo-view.active {
    display: block;
}

footer {
    text-align: center;
    padding: 2rem;
    background: var(--primary);
    color: white;
}
"@

# Create JavaScript
$demoJS = @"
document.addEventListener('DOMContentLoaded', function() {
    const demoButtons = document.querySelectorAll('.demo-nav button');
    const demoViews = document.querySelectorAll('.demo-view');

    demoButtons.forEach(button => {
        button.addEventListener('click', () => {
            const view = button.dataset.view;
            
            demoButtons.forEach(btn => btn.classList.remove('active'));
            demoViews.forEach(view => view.classList.remove('active'));
            
            button.classList.add('active');
            document.getElementById(`demo-${view}`).classList.add('active');
        });
    });
});
"@

# Create files
$demoHTML | Out-File -FilePath "demo/index.html" -Force -Encoding UTF8
$demoCSS | Out-File -FilePath "demo/css/style.css" -Force -Encoding UTF8
$demoJS | Out-File -FilePath "demo/js/demo.js" -Force -Encoding UTF8

# Create sample screenshots using System.Drawing
Add-Type -AssemblyName System.Drawing

function Create-SampleImage {
    param (
        [string]$path,
        [string]$text,
        [int]$width = 800,
        [int]$height = 600
    )

    $bmp = New-Object System.Drawing.Bitmap $width,$height
    $graphics = [System.Drawing.Graphics]::FromImage($bmp)
    
    # Background
    $graphics.Clear([System.Drawing.Color]::White)
    
    # Text
    $font = New-Object System.Drawing.Font("Arial", 20)
    $brush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(44, 62, 80))
    $point = New-Object System.Drawing.PointF(($width/2 - 100), ($height/2 - 15))
    $graphics.DrawString($text, $font, $brush, $point)
    
    $bmp.Save($path, [System.Drawing.Imaging.ImageFormat]::Png)
    $graphics.Dispose()
    $bmp.Dispose()
}

# Create sample screenshots
$screenshots = @(
    @{path="demo/screenshots/ai-recommendations.png"; text="AI Recommendations"},
    @{path="demo/screenshots/market-analysis.png"; text="Market Analysis"},
    @{path="demo/screenshots/blockchain.png"; text="Blockchain Integration"}
)

foreach ($screenshot in $screenshots) {
    Create-SampleImage -path $screenshot.path -text $screenshot.text
    Write-Host "Created screenshot: $($screenshot.path)" -ForegroundColor Green
}

Write-Host "`nDemo setup complete!" -ForegroundColor Green
Write-Host "Open demo/index.html in your browser to view the demo" -ForegroundColor Yellow

# Offer to open demo
$response = Read-Host "`nWould you like to open the demo now? (y/n)"
if ($response -eq "y") {
    Start-Process "demo/index.html"
} 