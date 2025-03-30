# PowerShell script to generate .mo files from .po files
# Requires gettext tools to be installed

# Check if languages directory exists, create if it doesn't
if (-not (Test-Path "languages")) {
    New-Item -ItemType Directory -Path "languages"
}

# Function to compile .po to .mo
function Compile-PoToMo {
    param (
        [string]$PoFile,
        [string]$OutputDir
    )
    
    # Get the base name of the .po file and change extension to .mo
    $moFile = Join-Path $OutputDir ([System.IO.Path]::ChangeExtension([System.IO.Path]::GetFileName($PoFile), ".mo"))
    
    # Compile the .po file to .mo
    Write-Host "Compiling $PoFile to $moFile"
    msgfmt -o $moFile $PoFile
}

# List of language codes
$languages = @("en_US", "es_ES", "fr_FR", "de_DE", "it_IT", "ja", "zh_CN")

# Process each language
foreach ($lang in $languages) {
    $poFile = "languages/vortex-ai-marketplace-$lang.po"
    if (Test-Path $poFile) {
        Compile-PoToMo -PoFile $poFile -OutputDir "languages"
    } else {
        Write-Host "Warning: $poFile not found"
    }
}

Write-Host "Translation compilation complete!" 