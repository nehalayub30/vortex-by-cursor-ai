# Check push status and verify pages
$ErrorActionPreference = "Stop"

Write-Host "Verifying GitHub Pages deployment..." -ForegroundColor Yellow

try {
    # Check if we're on gh-pages branch
    $currentBranch = git rev-parse --abbrev-ref HEAD
    Write-Host "Current branch: $currentBranch" -ForegroundColor Cyan

    # Verify files
    $requiredFiles = @(
        "index.html",
        "style.css",
        "docs/index.html"
    )

    $missingFiles = @()
    foreach ($file in $requiredFiles) {
        if (-not (Test-Path $file)) {
            $missingFiles += $file
        }
    }

    if ($missingFiles.Count -gt 0) {
        Write-Host "`nMissing files:" -ForegroundColor Red
        foreach ($file in $missingFiles) {
            Write-Host "- $file" -ForegroundColor Red
        }
    } 
    else {
        Write-Host "`n✓ All required files present" -ForegroundColor Green
    }

    # Check remote status
    $remoteUrl = git config --get remote.origin.url
    Write-Host "`nRemote URL: $remoteUrl" -ForegroundColor Cyan

    # Check last commit
    $lastCommit = git log -1 --oneline
    Write-Host "Last commit: $lastCommit" -ForegroundColor Cyan

    Write-Host "`nYour site should be available at:" -ForegroundColor Yellow
    Write-Host "https://mariannenems.github.io/VORTEX-AI-AGENTS" -ForegroundColor Green

    Write-Host "`nNext steps:" -ForegroundColor Yellow
    Write-Host "1. Wait a few minutes for GitHub Pages to update" -ForegroundColor Cyan
    Write-Host "2. Visit your site URL to verify changes" -ForegroundColor Cyan
    Write-Host "3. Check GitHub repository settings if site is not updating" -ForegroundColor Cyan

    # Offer to open in browser
    $response = Read-Host "`nWould you like to open the site in your browser? (y/n)"
    if ($response -eq "y") {
        Start-Process "https://mariannenems.github.io/VORTEX-AI-AGENTS"
    }
}
catch {
    Write-Host "Error: $_" -ForegroundColor Red
    Write-Host "Stack Trace: $($_.ScriptStackTrace)" -ForegroundColor Red
    exit 1
}