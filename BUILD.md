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

## Distribution

- **Git:** Commits both source and minified files.
- **WordPress.org SVN:** Distributes only minified files (see `.svnignore`).
