# Build Process - MaxtDesign Cookie Consent

## Overview

This plugin uses a build process to minify CSS and JavaScript files for optimal performance.

**Production** uses minified `.min.css` and `.min.js` files.  
**Development** uses source `.css` and `.js` files when `SCRIPT_DEBUG` is enabled.

## Requirements

- Node.js 14+ and npm
- Bash (Unix/Mac/WSL) or PowerShell (Windows)

## First-Time Setup

### 1. Install Dependencies

```bash
npm install
```

Installs: PostCSS + cssnano (CSS), Terser (JavaScript).

### 2. Run Initial Build

```bash
# Unix/Mac/WSL
./tools/build.sh

# Windows PowerShell
.\tools\build.ps1

# Or use npm directly
npm run build
npm run build:admin-css
npm run build:admin-js
```

## Development Workflow

### Editing Assets

1. **Edit source files only:**
   - `assets/css/popup.css`
   - `assets/js/consent-runtime.js`
   - `assets/css/admin.css`
   - `assets/js/admin.js`

2. **Enable SCRIPT_DEBUG in wp-config.php:**
   ```php
   define( 'SCRIPT_DEBUG', true );
   ```
   This loads source files instead of minified versions.

3. **Test your changes** with source files.

4. **Build minified files** when ready:
   ```bash
   npm run build
   npm run build:admin-css
   npm run build:admin-js
   ```

5. **Test with minified files** (disable SCRIPT_DEBUG):
   ```php
   define( 'SCRIPT_DEBUG', false );
   ```

6. **Validate size target:**
   ```bash
   npm run validate
   ```

### Watch Mode (Optional)

```bash
npm run watch:css
npm run watch:js
```

## Build Scripts

| Command | Description |
|---------|-------------|
| `npm run build` | Frontend CSS + JS |
| `npm run build:css` | Frontend CSS only |
| `npm run build:js` | Frontend JavaScript only |
| `npm run build:admin-css` | Admin CSS |
| `npm run build:admin-js` | Admin JavaScript |
| `npm run validate` | Size validation (<10KB total) |

## Size Targets

**Frontend Assets:**
- `popup.min.css`: ≤5-6KB
- `consent-runtime.min.js`: ≤3.5-4KB
- **Total: ≤10KB**

**Admin Assets** (not counted toward frontend target):
- `admin.min.css`: ~1KB
- `admin.min.js`: ~0.3KB

## Troubleshooting

### Build fails with "npm: command not found"
Install Node.js from https://nodejs.org/

### Build fails with "Cannot find module"
Run `npm install`

### Size validation fails
Run `npm run validate` to see which files exceed targets. Review CSS/JS optimizations.

## Static Analysis (PHPStan)

Level-8 static analysis, matching the MaxtDesign suite plugins. Runs from a
committed config + hand-written WordPress/Consent-API stubs, with **no Composer
dependency**.

The analyzer binary (`phpstan.phar`, ~23 MB) is **git-ignored** — grab it once:

```bash
# Any of these work; the plugin is pinned to the PHPStan 1.x line.
curl -L -o phpstan.phar https://github.com/phpstan/phpstan/releases/download/1.12.27/phpstan.phar
# …or copy it from a sibling suite plugin that already has it:
cp ../maxtdesign-product-bundles/phpstan.phar ./phpstan.phar
```

Then:

```bash
npm run phpstan          # php -d memory_limit=2G phpstan.phar analyse
```

- Config: `phpstan.neon.dist` (level 8; paths `includes/` + main + `uninstall.php`).
- WP/Consent-API surface: `stubs/phpstan-stubs.php` (add a symbol when the plugin
  starts calling a new WP function).
- Existing findings are grandfathered in `phpstan-baseline.neon` — a clean run
  means **no new** issues. Regenerate after a burn-down with
  `php phpstan.phar analyse --generate-baseline`.

None of these files ship (excluded by `.distignore` + `bin/build-zip.php`).

## Packaging (WordPress.org zip)

```bash
npm run build:zip        # npm run build && php -d extension=zip bin/build-zip.php
```

Produces a verified, WordPress.org-ready zip at `_build/<slug>-<version>.zip`
(git-ignored). The script:

- stages by an **allow-list** (the only files that ship — no dev file can leak),
- writes **forward-slash, slug-rooted** entries (Windows zip tools write
  backslashes that break extraction on the Linux boxes wp.org/SVN run on),
- refuses to package a `.min` older than its source (run `npm run build` first —
  note `npm run build` omits the admin variants; run `build:admin-css` /
  `build:admin-js` if you edited those),
- PHP-lints every shipped file, cross-checks the allow-list against `.distignore`,
  and fails if any dev entry leaked.

Test the built zip on a clean install before uploading.

## Distribution

Two layers keep dev files out of what users install (see memory
`project-distignore-vs-prepare-svn`):

1. **Allow-list (primary):** `bin/build-zip.php` and `tools/prepare-svn.sh` copy
   only the ~21 shippable files. What they don't copy never reaches trunk.
2. **`.distignore` (defense-in-depth):** WordPress.org's own zip-builder reads it
   at the trunk root and strips anything listed, catching a stray `cp -r`.

- **Git:** commits both source and minified assets (plus the tooling above).
- **WordPress.org SVN:** trunk carries only the shipped subset; `tools/prepare-svn.sh`
  stages it and the SVN release copies from there.
