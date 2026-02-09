# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

## [Unreleased]

## [1.7.1] - 2026-02-09

### Fixed
- **CRITICAL:** Google Consent Mode v2 timing issue where GTM/GA4 scripts could execute before consent state was set
- Added `gtag('consent', 'default', {...})` injection in `<head>` before tracking scripts load
- Ensures GDPR/CCPA compliance by preventing tracking before explicit user consent

### Added
- New admin setting: "Inject Default Consent State" in Advanced Settings section
- Setting enabled by default for proper compliance
- Admin can disable if conflicts occur with custom consent implementations
- Help text warning that disabling may cause tracking before consent

### Technical Changes
- New method `MDCC_Consent_Manager::inject_gcm_default()` injects consent default state
- New admin setting field `gcm_inject_default` (boolean, default: true)
- New admin section "Advanced Settings" for technical configuration options
- Runs on `wp_head` priority 1 to execute before all tracking scripts
- Added `wait_for_update: 500` parameter to allow consent-runtime.js time to load
- Setting checked before injection - respects admin configuration
- Zero changes to existing JavaScript API or shortcode functionality

### Documentation
- Updated README.md with fix explanation and admin control section
- Added FAQ entry "Does this plugin actually block Google Analytics and Ads tracking?"
- Added FAQ entry "How can I verify the plugin is blocking tracking correctly?"
- Updated installation section with GTM setup instructions and troubleshooting
- Updated upgrade notice with recommendation to update for compliance

## [1.7.0] - 2025-02-04
### Added
- `docs/SVN-UPLOAD-FILE-LIST.md`: Complete list of files for WordPress.org SVN upload (trunk/tag + assets, exclusions, and workflow)
- Phase 4 testing and validation suite
  - `tests/performance-validation.php`: Validates asset size targets (≤10KB total, popup CSS ≤6KB, runtime JS ≤4KB)
  - `tests/functional-tests.php`: Automated checks for file structure, minification, PHP/JS syntax, CSS content, build system
  - `tests/MANUAL-TESTING-CHECKLIST.md`: Comprehensive manual test guide (performance, visual, functional, responsive, accessibility)
  - `tests/VISUAL-REGRESSION-GUIDE.md`: Screenshot comparison methodology for 9 style×position combinations
  - npm scripts: `npm run test`, `npm run test:performance`, `npm run test:functional`
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

[Unreleased]: https://github.com/maxtdesign/maxtdesign-cookie-consent/compare/v1.7.1...HEAD
[1.7.1]: https://github.com/maxtdesign/maxtdesign-cookie-consent/compare/v1.7.0...v1.7.1
[1.7.0]: https://github.com/maxtdesign/maxtdesign-cookie-consent/compare/v1.6.0...v1.7.0
[1.6.0]: https://github.com/maxtdesign/maxtdesign-cookie-consent/releases/tag/v1.6.0
