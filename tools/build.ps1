#
# Build script for MaxtDesign Cookie Consent (Windows PowerShell)
# Generates minified CSS and JS files
#
# Usage: .\tools\build.ps1
#

Write-Host "==================================" -ForegroundColor Cyan
Write-Host "MaxtDesign Cookie Consent - Build" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""

# Check if npm is available
if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
    Write-Host "ERROR: npm not found. Install Node.js first." -ForegroundColor Red
    exit 1
}

# Check if node_modules exists
if (-not (Test-Path "node_modules")) {
    Write-Host "Installing dependencies..." -ForegroundColor Yellow
    npm install
    Write-Host ""
}

# Build frontend assets
Write-Host "Building frontend assets..." -ForegroundColor Yellow
npm run build
Write-Host ""

# Build admin assets
Write-Host "Building admin assets..." -ForegroundColor Yellow
npm run build:admin-css
npm run build:admin-js
Write-Host ""

# Calculate sizes
Write-Host "==================================" -ForegroundColor Cyan
Write-Host "Asset Sizes" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""

$cssMin = 0
$jsMin = 0

# Frontend CSS
if ((Test-Path "assets/css/popup.css") -and (Test-Path "assets/css/popup.min.css")) {
    $cssOrig = (Get-Item "assets/css/popup.css").Length
    $cssMin = (Get-Item "assets/css/popup.min.css").Length
    $cssReduction = [math]::Round(($cssOrig - $cssMin) / $cssOrig * 100, 1)
    Write-Host "popup.css:"
    Write-Host "  Source:   $($cssOrig) bytes"
    Write-Host "  Minified: $($cssMin) bytes"
    Write-Host "  Saved:    $($cssReduction)%"
    Write-Host ""
}

# Frontend JS
if ((Test-Path "assets/js/consent-runtime.js") -and (Test-Path "assets/js/consent-runtime.min.js")) {
    $jsOrig = (Get-Item "assets/js/consent-runtime.js").Length
    $jsMin = (Get-Item "assets/js/consent-runtime.min.js").Length
    $jsReduction = [math]::Round(($jsOrig - $jsMin) / $jsOrig * 100, 1)
    Write-Host "consent-runtime.js:"
    Write-Host "  Source:   $($jsOrig) bytes"
    Write-Host "  Minified: $($jsMin) bytes"
    Write-Host "  Saved:    $($jsReduction)%"
    Write-Host ""
}

# Total frontend size
$totalMin = $cssMin + $jsMin
$target = 10240

Write-Host "==================================" -ForegroundColor Cyan
Write-Host "Frontend Total (minified): $($totalMin) bytes"
Write-Host "Target: 10KB (10,240 bytes)"
Write-Host ""

if ($totalMin -le $target) {
    Write-Host "SUCCESS - Under target by $(($target - $totalMin)) bytes" -ForegroundColor Green
}
else {
    Write-Host "Over target by $(($totalMin - $target)) bytes" -ForegroundColor Yellow
}
Write-Host ""

# Run validation
Write-Host "Running size validation..." -ForegroundColor Yellow
npm run validate

Write-Host ""
Write-Host "Build complete!" -ForegroundColor Green
