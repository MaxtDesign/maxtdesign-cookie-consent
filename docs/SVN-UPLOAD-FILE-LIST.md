# SVN Upload File List

Files to include when uploading to WordPress.org SVN (`https://plugins.svn.wordpress.org/maxtdesign-cookie-consent`). Use for **trunk** and **tags** (e.g. `tags/1.7.0`). WP.org icons/banners/screenshots go in the repo **assets** directory, not inside trunk.

## Trunk / Tag (plugin package)

**Root:** `maxtdesign-cookie-consent.php`, `readme.txt`, `LICENSE.txt`, `uninstall.php`  
**includes/:** `class-admin-settings.php`, `class-consent-manager.php`, `class-popup-system.php`, `class-shortcodes.php`  
**assets/css/:** `admin.css`, `admin.min.css`, `popup.css`, `popup.min.css`  
**assets/js/:** `admin.js`, `admin.min.js`, `consent-runtime.js`, `consent-runtime.min.js`  
**languages/:** `maxtdesign-cookie-consent.pot`, `README.md`

**Total: 20 files.** Do not upload dev files (e.g. `tools/`, `tests/`, `node_modules/`, `.git/`).

## Prepare for SVN push

1. **Test first:** See **docs/TEST-BEFORE-SVN.md** — run CI, test on staging, then push to live SVN.
2. **Option A (GitHub):** Push a tag `v1.7.0` → Release workflow builds the zip and creates a GitHub Release. Download the zip and use it for WP.org or to update SVN trunk.
3. **Option B (local):** Run **`npm run prepare-svn`** (or `tools/prepare-svn.ps1` / `tools/prepare-svn.sh`). Copy **`svn-upload/trunk/`** into your SVN checkout **trunk**, then commit and tag (e.g. `svn cp trunk tags/1.7.0`).
