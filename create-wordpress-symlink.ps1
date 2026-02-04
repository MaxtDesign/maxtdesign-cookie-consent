# Create Symlink for WordPress Plugin Development
# 
# This script creates a symbolic link from your dev folder to your local WordPress installation
# so changes are immediately reflected in the WordPress site.
#
# IMPORTANT: Must be run as Administrator!

# Paths
$sourceFolder = "C:\dev\maxtdesign-cookie-consent"
$targetFolder = "C:\laragon\www\plugin-test\wp-content\plugins\maxtdesign-cookie-consent"

Write-Host "================================" -ForegroundColor Cyan
Write-Host "WordPress Plugin Symlink Creator" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Right-click on PowerShell and select 'Run as Administrator'," -ForegroundColor Yellow
    Write-Host "then run this script again." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Press any key to exit..."
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    exit 1
}

Write-Host "✓ Running as Administrator" -ForegroundColor Green
Write-Host ""

# Verify source folder exists
if (-not (Test-Path $sourceFolder)) {
    Write-Host "ERROR: Source folder does not exist!" -ForegroundColor Red
    Write-Host "  Path: $sourceFolder" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Press any key to exit..."
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    exit 1
}

Write-Host "✓ Source folder found: $sourceFolder" -ForegroundColor Green
Write-Host ""

# Verify WordPress plugins directory exists
$pluginsDir = Split-Path $targetFolder -Parent
if (-not (Test-Path $pluginsDir)) {
    Write-Host "ERROR: WordPress plugins directory does not exist!" -ForegroundColor Red
    Write-Host "  Path: $pluginsDir" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Make sure Laragon is set up and WordPress is installed." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Press any key to exit..."
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    exit 1
}

Write-Host "✓ WordPress plugins directory found: $pluginsDir" -ForegroundColor Green
Write-Host ""

# Check if target already exists
if (Test-Path $targetFolder) {
    Write-Host "WARNING: Target path already exists!" -ForegroundColor Yellow
    Write-Host "  Path: $targetFolder" -ForegroundColor Yellow
    Write-Host ""
    
    # Check if it's already a symlink
    $item = Get-Item $targetFolder -Force
    if ($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint) {
        $linkTarget = $item.Target
        Write-Host "  This is already a symlink pointing to:" -ForegroundColor Cyan
        Write-Host "  $linkTarget" -ForegroundColor Cyan
        Write-Host ""
        
        if ($linkTarget -eq $sourceFolder) {
            Write-Host "✓ Symlink already points to the correct location!" -ForegroundColor Green
            Write-Host ""
            Write-Host "No action needed. Symlink is already configured correctly." -ForegroundColor Green
            Write-Host ""
            Write-Host "Press any key to exit..."
            $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
            exit 0
        }
        
        Write-Host "  The symlink points to a different location." -ForegroundColor Yellow
    } else {
        Write-Host "  This is a regular folder (not a symlink)." -ForegroundColor Yellow
    }
    
    Write-Host ""
    Write-Host "Do you want to:" -ForegroundColor Yellow
    Write-Host "  [R] Remove and recreate as symlink" -ForegroundColor White
    Write-Host "  [B] Backup and create symlink" -ForegroundColor White
    Write-Host "  [C] Cancel" -ForegroundColor White
    Write-Host ""
    $choice = Read-Host "Enter your choice (R/B/C)"
    
    switch ($choice.ToUpper()) {
        "R" {
            Write-Host ""
            Write-Host "Removing existing folder/symlink..." -ForegroundColor Yellow
            Remove-Item $targetFolder -Force -Recurse
            Write-Host "✓ Removed" -ForegroundColor Green
        }
        "B" {
            $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
            $backupPath = "$targetFolder-backup-$timestamp"
            Write-Host ""
            Write-Host "Creating backup..." -ForegroundColor Yellow
            Move-Item $targetFolder $backupPath
            Write-Host "✓ Backup created: $backupPath" -ForegroundColor Green
        }
        default {
            Write-Host ""
            Write-Host "Cancelled by user." -ForegroundColor Yellow
            Write-Host ""
            Write-Host "Press any key to exit..."
            $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
            exit 0
        }
    }
}

Write-Host ""
Write-Host "Creating symbolic link..." -ForegroundColor Cyan
Write-Host ""
Write-Host "  From (Link):   $targetFolder" -ForegroundColor White
Write-Host "  To (Target):   $sourceFolder" -ForegroundColor White
Write-Host ""

try {
    # Create the symlink
    # Syntax: New-Item -ItemType SymbolicLink -Path "link" -Target "target"
    $null = New-Item -ItemType SymbolicLink -Path $targetFolder -Target $sourceFolder -Force
    
    Write-Host "✓ Symbolic link created successfully!" -ForegroundColor Green
    Write-Host ""
    
    # Verify it works
    if (Test-Path "$targetFolder\maxtdesign-cookie-consent.php") {
        Write-Host "✓ Verification passed: Plugin files are accessible" -ForegroundColor Green
        Write-Host ""
        Write-Host "SUCCESS!" -ForegroundColor Green
        Write-Host ""
        Write-Host "Your plugin is now linked to WordPress at:" -ForegroundColor Cyan
        Write-Host "  $targetFolder" -ForegroundColor White
        Write-Host ""
        Write-Host "Any changes you make to:" -ForegroundColor Cyan
        Write-Host "  $sourceFolder" -ForegroundColor White
        Write-Host ""
        Write-Host "Will immediately appear in WordPress!" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "Next steps:" -ForegroundColor Yellow
        Write-Host "  1. Open WordPress admin (http://plugin-test.test/wp-admin)" -ForegroundColor White
        Write-Host "  2. Go to Plugins page" -ForegroundColor White
        Write-Host "  3. Activate 'MaxtDesign Cookie Consent'" -ForegroundColor White
        Write-Host ""
    } else {
        Write-Host "WARNING: Symlink created but plugin file not found!" -ForegroundColor Yellow
        Write-Host "  Expected: $targetFolder\maxtdesign-cookie-consent.php" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "The symlink was created, but something may be wrong." -ForegroundColor Yellow
    }
    
} catch {
    Write-Host ""
    Write-Host "ERROR: Failed to create symbolic link!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Error details:" -ForegroundColor Yellow
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Host ""
    Write-Host "Common causes:" -ForegroundColor Yellow
    Write-Host "  - Not running as Administrator" -ForegroundColor White
    Write-Host "  - Developer Mode not enabled in Windows Settings" -ForegroundColor White
    Write-Host "  - Antivirus blocking the operation" -ForegroundColor White
    Write-Host ""
    Write-Host "Press any key to exit..."
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    exit 1
}

Write-Host ""
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
