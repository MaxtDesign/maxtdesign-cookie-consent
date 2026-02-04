# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

## [Unreleased]
### Added
- Minification build process with SCRIPT_DEBUG support
  - PostCSS + cssnano for CSS minification, Terser for JS
  - Cross-platform build scripts: `tools/build.sh` (Unix/Mac) and `tools/build.ps1` (Windows)
  - Size validation tool: `npm run validate` (<10KB frontend target)
  - WordPress loads `.min` files by default; source files when `SCRIPT_DEBUG = true`
  - BUILD.md documentation for build workflow
- `.svnignore` for WordPress.org distribution (minified assets only)

### Fixed
- Fixed popup re-appearing after decline when "Re-prompt on Decline" is enabled
  - Added visibility check to ensure popup re-appears only after user has closed it
  - Prevents unintended popup display on initial page load with denied consent
### Changed
- Optimized consent-runtime.js: Reduced file size from 6.5KB to 3.9KB (40.7% reduction)
  - Extracted all comments to docs/JAVASCRIPT-API.md for maintainability
  - Removed debug code and optimized variable names (internal only)
  - Maintained 100% functional parity - all consent actions work identically
  - Ready for Phase 3 minification (target: ~2.5KB)
- Renamed plugin display name to "MaxtDesign Cookie Consent - Google Consent Mode v2"
- Updated slug and text domain to `maxtdesign-cookie-consent`
- Updated documentation, licensing, and translation assets to reflect new name
- Refactored all prefixes: `MDLC_/mdlc_/mdlc-` → `MDCC_/mdcc_/mdcc-`
- Updated all option/transient names to `mdcc_*`
- Updated shortcode names to `[mdcc_consent_status]` and `[mdcc_manage_consent]`
- Updated JS global/events/localStorage keys to `window.mdccConsent`, `mdcc:changed`, `mdcc_consent`
- Updated asset handles to `mdcc-*`
- Rewrote readme.txt for SEO with new branding and messaging
- Renamed POT to `maxtdesign-cookie-consent.pot` and updated languages README
- Flattened plugin structure to repository root (removed nested folder)
- Refined .gitignore to include docs in repo; confirmed .svnignore for WP.org
- Readme: Updated Contributors to WordPress.org username `slaacr`

## [1.6.0] - 2025-10-30
### Added
- First public release scaffolding

[Unreleased]: https://example.com/compare/v1.6.0...HEAD
[1.6.0]: https://example.com/releases/v1.6.0
