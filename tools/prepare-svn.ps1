# Prepares plugin files for WordPress.org SVN upload (trunk/tag).
# Usage: .\tools\prepare-svn.ps1 [-Version "1.7.0"]

param([string]$Version)

$ErrorActionPreference = "Stop"
$RootDir = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
if (-not $RootDir) { $RootDir = (Get-Location).Path }
Set-Location $RootDir

if (-not $Version) {
    try { $Version = (Get-Content package.json | ConvertFrom-Json).version } catch { $Version = "dev" }
}

$OutDir = "svn-upload\trunk"
Write-Host "MaxtDesign Cookie Consent - Prepare for SVN"; Write-Host "Version: $Version"; Write-Host "Output:  $OutDir\"; Write-Host ""

if (Test-Path node_modules) { npm run build } else { npm install; npm run build }

if (Test-Path $OutDir) { Remove-Item -Recurse -Force $OutDir }
New-Item -ItemType Directory -Force -Path "$OutDir\includes","$OutDir\assets\css","$OutDir\assets\js","$OutDir\languages" | Out-Null

Copy-Item maxtdesign-cookie-consent.php, readme.txt, LICENSE.txt, uninstall.php -Destination $OutDir
Copy-Item includes\class-*.php -Destination "$OutDir\includes"
Copy-Item assets\css\*.css -Destination "$OutDir\assets\css"
Copy-Item assets\js\admin.js, assets\js\admin.min.js, assets\js\consent-runtime.js, assets\js\consent-runtime.min.js -Destination "$OutDir\assets\js"
Copy-Item languages\maxtdesign-cookie-consent.pot, languages\README.md -Destination "$OutDir\languages"

Write-Host "Done. Copy $OutDir to SVN trunk, then commit and tag $Version"
