# Resolve Rebase Conflicts
$ErrorActionPreference = "Stop"
$repoUrl = "https://github.com/MarianneNems/VORTEX-AI-AGENTS.git"

try {
    # 1. Abort any existing rebase
    Write-Host "Aborting existing rebase..." -ForegroundColor Yellow
    git rebase --abort

    # 2. Reset to origin/main
    Write-Host "Resetting to origin/main..." -ForegroundColor Yellow
    git fetch origin
    git reset --hard origin/main

    # 3. Create new branch
    Write-Host "Creating clean branch..." -ForegroundColor Yellow
    $branchName = "clean-structure-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
    git checkout -b $branchName

    # 4. Add files
    Write-Host "Adding files..." -ForegroundColor Yellow
    git add -A

    # 5. Create clean commit
    Write-Host "Creating commit..." -ForegroundColor Yellow
    git commit -m "Clean structure update"

    # 6. Push new branch
    Write-Host "Pushing new branch..." -ForegroundColor Yellow
    git push -u origin $branchName

    Write-Host "`nNew branch created and pushed!" -ForegroundColor Green
    Write-Host "Branch name: $branchName" -ForegroundColor Cyan
    Write-Host "`nNext steps:" -ForegroundColor Yellow
    Write-Host "1. Go to: $repoUrl" -ForegroundColor Cyan
    Write-Host "2. Create pull request from $branchName to main" -ForegroundColor Cyan
    Write-Host "3. Merge the pull request" -ForegroundColor Cyan

} catch {
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "`nTo fix manually:" -ForegroundColor Yellow
    Write-Host "1. git rebase --abort" -ForegroundColor Cyan
    Write-Host "2. git reset --hard origin/main" -ForegroundColor Cyan
    Write-Host "3. git checkout -b clean-structure" -ForegroundColor Cyan
    Write-Host "4. git add -A" -ForegroundColor Cyan
    Write-Host "5. git commit -m 'Clean structure'" -ForegroundColor Cyan
    Write-Host "6. git push -u origin clean-structure" -ForegroundColor Cyan
} 