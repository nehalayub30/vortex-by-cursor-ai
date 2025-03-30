# VORTEX AI Demo - CSS and Style Audit Script
Write-Host "====================================================" -ForegroundColor Cyan
Write-Host "      VORTEX AI Demo CSS and Style Audit           " -ForegroundColor Cyan
Write-Host "====================================================" -ForegroundColor Cyan

$baseDir = "marketplace/demo"
$global:cssErrors = 0
$global:cssWarnings = 0

# 1. Check CSS Files Exist
Write-Host "`n[1] Checking CSS Files..." -ForegroundColor Yellow
$cssFiles = @(
    "$baseDir/assets/css/artbasel-inspired.css"
)

foreach ($cssFile in $cssFiles) {
    if (Test-Path $cssFile) {
        Write-Host "✓ Found CSS file: $cssFile" -ForegroundColor Green
        
        # Check CSS file size to ensure it has content
        $fileSize = (Get-Item $cssFile).Length
        if ($fileSize -lt 1000) {
            Write-Host "! Warning: $cssFile seems small ($fileSize bytes). It may not contain all necessary styles." -ForegroundColor Yellow
            $global:cssWarnings++
        } else {
            Write-Host "✓ CSS file has sufficient content ($fileSize bytes)" -ForegroundColor Green
        }
        
        # Check for crucial CSS selectors
        $cssContent = Get-Content $cssFile -Raw
        $requiredSelectors = @(
            ".gallery-grid", 
            ".artwork-card", 
            ".site-header", 
            ".container", 
            ".hero-section",
            ".site-footer",
            ".button",
            "@media"
        )
        
        foreach ($selector in $requiredSelectors) {
            if ($cssContent -match $selector) {
                Write-Host "✓ CSS includes important selector: $selector" -ForegroundColor Green
            } else {
                Write-Host "✗ Missing important CSS selector: $selector" -ForegroundColor Red
                $global:cssErrors++
            }
        }
    } else {
        Write-Host "✗ CSS file missing: $cssFile" -ForegroundColor Red
        $global:cssErrors++
    }
}

# 2. Check HTML Pages for CSS References
Write-Host "`n[2] Checking HTML Files for CSS References..." -ForegroundColor Yellow
$htmlFiles = @(
    "$baseDir/index.html",
    "$baseDir/pages/marketplace.html",
    "$baseDir/pages/huraii-demo.html",
    "$baseDir/pages/artist-journey.html",
    "$baseDir/pages/collector-experience.html",
    "$baseDir/pages/vortex-registration.html",
    "$baseDir/pages/marianne-bio.html"
)

foreach ($htmlFile in $htmlFiles) {
    if (Test-Path $htmlFile) {
        Write-Host "Checking $htmlFile..." -ForegroundColor Cyan
        $htmlContent = Get-Content $htmlFile -Raw
        
        # Check for CSS reference
        if ($htmlContent -match '<link\s+[^>]*href="[^"]*artbasel-inspired\.css"') {
            Write-Host "✓ CSS properly linked in $htmlFile" -ForegroundColor Green
        } else {
            Write-Host "✗ CSS not properly linked in $htmlFile" -ForegroundColor Red
            $global:cssErrors++
        }
        
        # Check for required HTML structure
        $requiredElements = @(
            '<header\s+[^>]*class="[^"]*site-header[^"]*"',
            '<footer\s+[^>]*class="[^"]*site-footer[^"]*"',
            '<div\s+[^>]*class="[^"]*container[^"]*"'
        )
        
        foreach ($element in $requiredElements) {
            if ($htmlContent -match $element) {
                Write-Host "✓ Found required element pattern: $element" -ForegroundColor Green
            } else {
                Write-Host "! Missing expected HTML structure: $element" -ForegroundColor Yellow
                $global:cssWarnings++
            }
        }
    } else {
        # Just a warning since not all pages might be created yet
        Write-Host "! HTML file not found: $htmlFile" -ForegroundColor Yellow
        $global:cssWarnings++
    }
}

# 3. Check for Gallery Grid Implementation
Write-Host "`n[3] Checking for Gallery Grid Implementation..." -ForegroundColor Yellow
if (Test-Path "$baseDir/index.html") {
    $indexContent = Get-Content "$baseDir/index.html" -Raw
    
    if ($indexContent -match '<div\s+[^>]*class="[^"]*gallery-grid[^"]*"') {
        Write-Host "✓ Gallery grid implemented in index.html" -ForegroundColor Green
        
        # Check for three-column structure
        if ((Select-String -Pattern '<div\s+[^>]*class="[^"]*artwork-card[^"]*"' -Path "$baseDir/index.html").Matches.Count -ge 3) {
            Write-Host "✓ At least 3 artwork cards found in gallery grid" -ForegroundColor Green
        } else {
            Write-Host "! Less than 3 artwork cards found in gallery grid - may not fill the three columns" -ForegroundColor Yellow
            $global:cssWarnings++
        }
    } else {
        Write-Host "✗ Gallery grid not implemented in index.html" -ForegroundColor Red
        $global:cssErrors++
    }
}

# 4. Check for Responsive Design Elements in CSS
Write-Host "`n[4] Checking for Responsive Design in CSS..." -ForegroundColor Yellow
if (Test-Path "$baseDir/assets/css/artbasel-inspired.css") {
    $cssContent = Get-Content "$baseDir/assets/css/artbasel-inspired.css" -Raw
    
    $mediaQueries = @(
        '@media\s*\(\s*max-width:\s*1024px\s*\)',
        '@media\s*\(\s*max-width:\s*768px\s*\)',
        '@media\s*\(\s*max-width:\s*480px\s*\)'
    )
    
    foreach ($query in $mediaQueries) {
        if ($cssContent -match $query) {
            Write-Host "✓ Found responsive media query: $query" -ForegroundColor Green
        } else {
            Write-Host "! Missing recommended responsive media query: $query" -ForegroundColor Yellow
            $global:cssWarnings++
        }
    }
}

# 5. Check for JavaScript Integration
Write-Host "`n[5] Checking for JavaScript Integration..." -ForegroundColor Yellow
if (Test-Path "$baseDir/assets/js/main.js") {
    Write-Host "✓ Found main.js file" -ForegroundColor Green
    
    # Check main.js content
    $jsContent = Get-Content "$baseDir/assets/js/main.js" -Raw
    if ($jsContent.Length -lt 100) {
        Write-Host "! JavaScript file is small, may be missing important functionality" -ForegroundColor Yellow
        $global:cssWarnings++
    }
    
    # Check that JS is referenced in HTML
    if (Test-Path "$baseDir/index.html") {
        $indexContent = Get-Content "$baseDir/index.html" -Raw
        if ($indexContent -match '<script\s+[^>]*src="[^"]*main\.js"') {
            Write-Host "✓ JavaScript properly linked in index.html" -ForegroundColor Green
        } else {
            Write-Host "! JavaScript not linked in index.html" -ForegroundColor Yellow
            $global:cssWarnings++
        }
    }
} else {
    Write-Host "! JavaScript file main.js not found" -ForegroundColor Yellow
    $global:cssWarnings++
}

# 6. Summary
Write-Host "`n====================================================" -ForegroundColor Cyan
Write-Host "             CSS & STYLE AUDIT SUMMARY              " -ForegroundColor Cyan
Write-Host "====================================================" -ForegroundColor Cyan

if ($global:cssErrors -eq 0 -and $global:cssWarnings -eq 0) {
    Write-Host "`n✓ PERFECT! CSS and styling are correctly implemented." -ForegroundColor Green
} elseif ($global:cssErrors -eq 0) {
    Write-Host "`n! Your CSS implementation has $global:cssWarnings warnings but no critical errors." -ForegroundColor Yellow
    Write-Host "  Consider addressing these for optimal design consistency." -ForegroundColor Yellow
} else {
    Write-Host "`n✗ Your CSS implementation has $global:cssErrors critical errors that should be fixed." -ForegroundColor Red
    Write-Host "  Please address the errors marked in red above." -ForegroundColor Red
}

Write-Host "`nErrors: $global:cssErrors | Warnings: $global:cssWarnings" -ForegroundColor Cyan
Write-Host "`n====================================================" -ForegroundColor Cyan

# Common CSS Issues to Check Manually
Write-Host "`n[MANUAL CHECK LIST] Please verify these items manually:" -ForegroundColor Magenta
Write-Host "1. Three-column layout appears consistently across all sections" -ForegroundColor Magenta
Write-Host "2. Fonts are loading properly (no fallbacks being used unexpectedly)" -ForegroundColor Magenta
Write-Host "3. Buttons have proper styling and hover effects" -ForegroundColor Magenta
Write-Host "4. Images are properly sized and not distorted" -ForegroundColor Magenta
Write-Host "5. Spacing between elements is consistent" -ForegroundColor Magenta
Write-Host "6. Test responsiveness by resizing your browser window" -ForegroundColor Magenta
Write-Host "7. Colors match the Art Basel style throughout" -ForegroundColor Magenta
Write-Host "8. Header and footer are consistent across all pages" -ForegroundColor Magenta