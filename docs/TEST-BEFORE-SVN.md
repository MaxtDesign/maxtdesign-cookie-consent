# Test Before SVN Push

**Do not push to WordPress.org live SVN until all steps below pass.**

## 1. Push to GitHub and let CI run

- Push your branch to GitHub.
- Open **Actions** and confirm the **CI** workflow completes (build, validate, test).
- Fix any failures before proceeding.

## 2. Build and prepare the release package locally

- Run **`npm run build`** (or use the **Release** workflow after you tag).
- Run **`npm run prepare-svn`** (or `.\tools\prepare-svn.ps1` / `./tools/prepare-svn.sh`) to create **`svn-upload/trunk/`** with only the files that go to SVN.

## 3. Test on a staging WordPress site

- Install the plugin from the zip you build (or from **`svn-upload/trunk/`** by copying it into `wp-content/plugins/maxtdesign-cookie-consent/` on a **staging or local** WordPress).
- Run through **`tests/MANUAL-TESTING-CHECKLIST.md`** (or at least: activate, open settings, view frontend popup, accept/decline, shortcodes, deactivate/uninstall).
- Confirm no PHP/JS errors and behavior matches expectations.

## 4. Only then: push to live SVN

- Copy **`svn-upload/trunk/`** into your WordPress.org SVN checkout **trunk** (or use the zip from a GitHub Release after tagging).
- **`svn status`** → **`svn ci -m "Update to x.y.z"`**.
- **`svn cp trunk tags/x.y.z`** → **`svn ci -m "Tagging x.y.z"`**.

See **docs/submission-guide.md** and **docs/SVN-UPLOAD-FILE-LIST.md** (if present) for full SVN and file list.
