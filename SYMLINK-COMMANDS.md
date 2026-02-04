# Quick Symlink Commands Reference

## Create Symlink (PowerShell - Admin Required)

```powershell
New-Item -ItemType SymbolicLink `
  -Path "C:\laragon\www\plugin-test\wp-content\plugins\maxtdesign-cookie-consent" `
  -Target "C:\dev\maxtdesign-cookie-consent"
```

## Create Symlink (CMD - Admin Required)

```cmd
mklink /D "C:\laragon\www\plugin-test\wp-content\plugins\maxtdesign-cookie-consent" "C:\dev\maxtdesign-cookie-consent"
```

## Verify Symlink

```powershell
Get-Item "C:\laragon\www\plugin-test\wp-content\plugins\maxtdesign-cookie-consent" | Select-Object Target
```

## Remove Symlink

```powershell
Remove-Item "C:\laragon\www\plugin-test\wp-content\plugins\maxtdesign-cookie-consent"
```

## Check if Plugin File Exists

```powershell
Test-Path "C:\laragon\www\plugin-test\wp-content\plugins\maxtdesign-cookie-consent\maxtdesign-cookie-consent.php"
```

---

**Remember:** Always run PowerShell as Administrator!

**Right-click PowerShell → "Run as Administrator"**
