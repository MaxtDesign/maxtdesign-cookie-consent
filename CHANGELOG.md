# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

## [Unreleased]
### Added
- Initial plugin scaffolding: main file, includes, assets, languages
- Autoloader, activation/deactivation hooks, default settings
- Minimal documentation and placeholders
- Core Consent Manager with Google Consent Mode v2
- Frontend runtime `assets/js/consent-runtime.js` with localStorage storage
- Frontend enqueue + localization for runtime (zero dependencies)
- Standalone Popup System with Minimal/Modern/Bold styles, positions (top/bottom/center), animations (slide/fade/none), full accessibility, cookie-based shown tracking, and integration with `window.mdlcConsent`
- Comprehensive Admin Settings page under Settings > Lean Consent (Settings API, sanitization, color picker, Elementor integration)
### Changed
- Flattened plugin structure to repository root (removed nested folder)
 - Refined .gitignore to include docs in repo; confirmed .svnignore for WP.org

## [1.6.0] - 2025-10-30
### Added
- First public release scaffolding

[Unreleased]: https://example.com/compare/v1.6.0...HEAD
[1.6.0]: https://example.com/releases/v1.6.0
