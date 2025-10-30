# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

## [Unreleased]
### Added
- Complete uninstall cleanup script: deletes `mdlc_settings`, `mdlc_version`, clears `mdlc_cache` transient, and supports multisite cleanup across all sites
- Shortcodes: `[mdlc_consent_status]` and `[mdlc_manage_consent]` with inline CSS/JS, real-time updates via `mdlc:changed`, accessibility, and mobile responsiveness
- Initial plugin scaffolding: main file, includes, assets, languages
- Autoloader, activation/deactivation hooks, default settings
- Minimal documentation and placeholders
- Core Consent Manager with Google Consent Mode v2
- Frontend runtime `assets/js/consent-runtime.js` with localStorage storage
- Frontend enqueue + localization for runtime (zero dependencies)
- Standalone Popup System with Minimal/Modern/Bold styles, positions (top/bottom/center), animations (slide/fade/none), full accessibility, cookie-based shown tracking, and integration with `window.mdlcConsent`
- Comprehensive Admin Settings page under Settings > Lean Consent (Settings API, sanitization, color picker, Elementor integration)
- Internationalization: audited all user-facing strings, ensured consistent text domain `maxtdesign-lean-consent`, localized JavaScript strings (confirm reset, On/Off), added translator guide in `languages/README.md`, and ensured `.pot` template present in `languages/`
- SECURITY: Added SECURITY-AUDIT.md documenting full security/compliance review and results
- Docs: Added security note above sanitize_settings() explaining Settings API nonce/capability flow
### Changed
- Flattened plugin structure to repository root (removed nested folder)
- Refined .gitignore to include docs in repo; confirmed .svnignore for WP.org
- Readme: Updated Contributors to WordPress.org username `slaacr`
### Fixed
- Shortcodes: Center shortcode blocks within viewport when theme containers are absent; changed `.mdlc-status` to block-level flex and constrained width
- Admin: Avoid duplicate "Settings saved." notices by removing custom notice and relying on core
- PHPCS: Escaped checkbox attributes using `checked()` in `includes/class-admin-settings.php`
- PHPCS: Escaped popup title/message on output and removed double-escaping in `includes/class-popup-system.php`
- PHPCS: Replaced `__()` with `esc_html__()` in `wp_die()` checks in `maxtdesign-lean-consent.php`
- Plugin Check: Removed discouraged `load_plugin_textdomain()` for WP.org hosted translations
- Readme: Updated "Tested up to" to 6.8
- Shortcodes: Added version parameter to `wp_register_style()` handle
- Packaging: Removed `site-consent-ORIGINAL.php` from distribution

## [1.6.0] - 2025-10-30
### Added
- First public release scaffolding

[Unreleased]: https://example.com/compare/v1.6.0...HEAD
[1.6.0]: https://example.com/releases/v1.6.0
