# Script to update existing VORTEX-AI-AGENTS repository
$ErrorActionPreference = "Stop"

# Clone existing repository
Write-Host "Cloning existing repository..." -ForegroundColor Yellow
git clone https://github.com/MarianneNems/VORTEX-AI-AGENTS.git
Set-Location VORTEX-AI-AGENTS

# Create new branch for demo
Write-Host "Creating demo branch..." -ForegroundColor Yellow
git checkout -b feature/interactive-demo

# Update README.md with demo information
$readmeUpdate = @"
# VORTEX AI AGENTS

## Interactive Demo
The interactive demo showcases the following features:
- Art Basel inspired marketplace
- TOLA integration with blockchain
- AI-powered networking
- Real-time chat system
- Advanced visualizations

## Directory Structure 