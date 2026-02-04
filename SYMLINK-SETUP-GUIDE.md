# WordPress Development Symlink Setup

This guide helps you create a symbolic link from your plugin development folder to your local WordPress installation.

## What is a Symlink?

A symbolic link (symlink) makes WordPress see your plugin files in the `wp-content/plugins` folder, but the actual files remain in your development folder (`C:\dev\maxtdesign-cookie-consent`). Any changes you make are immediately reflected in WordPress.

## Quick Setup (Recommended)

### Option 1: Automated Script

1. **Right-click PowerShell** and select **"Run as Administrator"**

2. Navigate to plugin folder:
   ```powershell
   cd C:\dev\maxtdesign-cookie-consent
   ```

3. Run the script:
   ```powershell
   .\create-wordpress-symlink.ps1
   ```

4. Follow the prompts

That's it! ✅

---

## Manual Setup

If you prefer to create the symlink manually:

### Step 1: Open PowerShell as Administrator

**Right-click** on PowerShell → **"Run as Administrator"**

### Step 2: Verify Paths Exist

```powershell
# Check source folder exists
Test-Path "C:\dev\maxtdesign-cookie-consent"

# Check WordPress plugins folder exists
Test-Path "C:\laragon\www\plugin-test\wp-content\plugins"
```

Both should return `True`.

### Step 3: Remove Existing Plugin Folder (if exists)

```powershell
# Only run this if the plugin folder already exists
Remove-Item "C:\laragon\www\plugin-test\wp-content\plugins\maxtdesign-cookie-consent" -Recurse -Force
```

### Step 4: Create Symlink

```powershell
New-Item -ItemType SymbolicLink `
  -Path "C:\laragon\www\plugin-test\wp-content\plugins\maxtdesign-cookie-consent" `
  -Target "C:\dev\maxtdesign-cookie-consent"
```

### Step 5: Verify

```powershell
# Check symlink exists
Test-Path "C:\laragon\www\plugin-test\wp-content\plugins\maxtdesign-cookie-consent\maxtdesign-cookie-consent.php"
```

Should return `True` if successful! ✅

---

## Alternative: CMD Method (Legacy)

If PowerShell doesn't work, use Command Prompt:

1. **Right-click Command Prompt** → **"Run as Administrator"**

2. Create symlink:
   ```cmd
   mklink /D "C:\laragon\www\plugin-test\wp-content\plugins\maxtdesign-cookie-consent" "C:\dev\maxtdesign-cookie-consent"
   ```

---

## Troubleshooting

### ❌ "Access Denied" or "You do not have sufficient privilege"

**Solution:** You must run PowerShell/CMD as **Administrator**

1. Press `Win + X`
2. Select **"Windows PowerShell (Admin)"** or **"Command Prompt (Admin)"**

---

### ❌ "Developer Mode is not enabled"

On Windows 10/11, symlinks may require Developer Mode:

**Solution:** Enable Developer Mode

1. Press `Win + I` (Settings)
2. Go to **Update & Security** → **For developers**
3. Turn on **Developer Mode**
4. Restart PowerShell and try again

---

### ❌ Symlink created but WordPress doesn't see the plugin

**Possible causes:**

1. **Wrong path** - Double-check paths in the symlink command
2. **WordPress cache** - Clear WordPress cache or restart PHP

**Verify symlink target:**
```powershell
Get-Item "C:\laragon\www\plugin-test\wp-content\plugins\maxtdesign-cookie-consent" | Select-Object Target
```

Should show: `C:\dev\maxtdesign-cookie-consent`

---

### ❌ "The target already exists"

The plugin folder already exists as a regular folder.

**Solution:** Remove or backup the existing folder first

**Backup approach:**
```powershell
# Rename existing folder
Rename-Item `
  "C:\laragon\www\plugin-test\wp-content\plugins\maxtdesign-cookie-consent" `
  "maxtdesign-cookie-consent-backup"

# Then create symlink (see Step 4 above)
```

---

## Verification

After creating the symlink, verify it works:

### 1. Check Symlink Properties

```powershell
Get-Item "C:\laragon\www\plugin-test\wp-content\plugins\maxtdesign-cookie-consent" | Format-List *
```

Look for:
- `LinkType: SymbolicLink`
- `Target: C:\dev\maxtdesign-cookie-consent`

### 2. Check File Access

```powershell
# Should see your plugin files
Get-ChildItem "C:\laragon\www\plugin-test\wp-content\plugins\maxtdesign-cookie-consent"
```

### 3. Test in WordPress

1. Go to `http://plugin-test.test/wp-admin`
2. Navigate to **Plugins**
3. You should see **"MaxtDesign Cookie Consent - Google Consent Mode v2"**
4. Activate the plugin

### 4. Test Live Changes

1. Edit a file in `C:\dev\maxtdesign-cookie-consent` (e.g., change plugin description)
2. Refresh WordPress Plugins page
3. Changes should appear immediately! ✅

---

## Removing Symlink

If you need to remove the symlink later:

```powershell
# This only removes the link, not your dev files!
Remove-Item "C:\laragon\www\plugin-test\wp-content\plugins\maxtdesign-cookie-consent"
```

Your dev files in `C:\dev\maxtdesign-cookie-consent` remain untouched. ✅

---

## Benefits of This Setup

✅ **Work in one place** - Edit files in `C:\dev\` only  
✅ **Instant updates** - Changes appear immediately in WordPress  
✅ **Version control** - Git tracks your dev folder, not WordPress  
✅ **Clean separation** - WordPress installation stays clean  
✅ **Easy testing** - Test in local WordPress without copying files  

---

## Next Steps After Symlink

1. ✅ Activate plugin in WordPress admin
2. ✅ Start development in `C:\dev\maxtdesign-cookie-consent`
3. ✅ Run Cursor prompts for optimization (`.notes/` folder)
4. ✅ Test changes live in WordPress
5. ✅ Commit to Git when ready

---

## Support

If you encounter issues:

1. Check you're running PowerShell as **Administrator**
2. Verify both paths exist
3. Enable **Developer Mode** in Windows Settings
4. Check antivirus isn't blocking symlink creation
5. Try CMD method as alternative

**Still having trouble?** The automated script (`create-wordpress-symlink.ps1`) provides better error messages and troubleshooting guidance.
