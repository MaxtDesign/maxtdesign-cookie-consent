# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

## [Unreleased]

## [1.9.0] - Unreleased

### Added
- **Extensibility API** — a public, versioned developer API so add-ons and integrators can hook consent cleanly (the foundation for Cookie Consent Pro, and useful for any third-party integration):
  - **JavaScript** (`window.mdccConsent`, `apiVersion: '1'`): `registerService(id, {category, onGrant, onRevoke})` — consent-gated service loading that fires immediately for the current state and on every change, so load order does not matter; `getCategory(name)`; `on('change', cb)` / `off('change', cb)`; `requireConsent(category, cb)`. All existing methods (`current/stored/acceptAll/acceptAnalyticsOnly/declineAll/reset`) are unchanged.
  - **PHP**: `mdcc_gcm_default_state` (filter — set region-specific GCM defaults), `mdcc_consent_categories` (filter — category labels/metadata exposed to the runtime + UI), `mdcc_tracking_services` (filter — declare consent-gated services), `mdcc_admin_settings_sections` (action — register add-on settings sections/fields), `mdcc_popup_before_actions` (action — inject markup into the popup, e.g. per-category toggles).
- **EU dark-pattern warning** on the "re-prompt on decline" setting — surfaces EDPB/CNIL guidance that re-prompting after a decline approaches a dark pattern.

### Changed
- Nothing removed or altered in existing behavior: the extension API is purely additive and backward-compatible. The consent runtime's on-load behavior and the `mdcc:changed` event are byte-compatible with 1.8.x. Frontend footprint stays within the zero-footprint budget (8.85 KB / 10 KB).

## [1.8.0] - 2026-07-13

### Added
- **WP Consent API bridge** — the plugin is now a first-class provider for the WordPress Consent API (the wp.org standard that WooCommerce and others read). When the free WP Consent API plugin is active, each visitor choice is mirrored onto the standard consent categories via `wp_set_consent()` — `analytics` → `statistics`, `ads` → `marketing`, and strictly-necessary `functional` cookies are always allowed — and the plugin declares the site's consent model as `optin` (GDPR) through the `wp_get_consent_type` filter. This lets other consent-aware plugins respect the same decision with `wp_has_consent()`, replacing owner-asserted consent with real, visitor-controlled consent. Purely additive: with the WP Consent API plugin absent, every path is a no-op and the plugin keeps its existing client-only behavior.
- New **"WP Consent API Integration"** admin toggle (Advanced Settings, default on), with live detection of whether the WP Consent API plugin is active.
- Developer filters: `mdcc_consent_type` (override the declared consent model), `mdcc_consent_api_enabled` (force the JS bridge on/off), and `mdcc_consent_runtime_deps` (declare script dependencies for the consent runtime).
- `mdcc_should_show_popup` filter — lets site developers suppress the consent popup on specific pages or conditions (per page, post type, user role, etc.) without disabling it sitewide. Runs only after all built-in gates pass, so it can turn a "would show" into "don't show" but cannot force the popup on in admin, when disabled, or once already shown.

### Changed
- Extracted the popup's inline behavior JavaScript out of a PHP heredoc in `class-popup-system.php` into a dedicated source file (`assets/js/popup.js`, minified to `popup.min.js` via `npm run build:popup-js`). The script is still inlined into the footer — no new HTTP request — so this is a maintainability/build-tooling change with no behavior change. Edit `popup.js`; never edit the generated `.min.js`.
- In-plugin "Plugin Documentation" link now points to the dedicated docs page (`https://maxtdesign.com/plugins/cookie-consent/docs`) instead of the plugin landing page.

### Privacy
- The Privacy Policy generator content now discloses the WP Consent API bridge and its category mapping when the integration applies.

## [1.7.6] - 2026-06-11

### Changed
- `Plugin URI` now points to the plugin's own page (`https://maxtdesign.com/plugins/cookie-consent`) instead of the WordPress.org listing, and `Author URI` points to `https://maxtdesign.com`. Also aligns Plugin URI with the WordPress.org guideline that it be distinct from the directory listing.
- In-plugin "Documentation & Support" links (Plugin Documentation, FAQ) and the Elementor popup-ID help link now point to the official plugin page.
- Translation README support contact now points to the WordPress.org support forum.

### Removed
- Deleted the stale `README.md` release-checklist from the repository (already excluded from the distributed package; removing it prevents any future re-leak).

## [1.7.5] - 2026-06-11

### Fixed
- Consent popup reappeared on every page load even after the visitor had stored a consent choice. The display decision was server-side only (the `mdcc_popup_shown` cookie gate in `should_show_popup()`); the client behavior script showed the popup unconditionally whenever the markup was present. Under full-page caching the cached HTML (markup + inline JS) is served to every visitor regardless of cookies, so the server gate never re-evaluated and the popup showed every time. Added a client-side `shouldShow()` gate in the popup behavior JS that mirrors the server gate, so the popup stays hidden once consent is stored (or the dismissal cookie is present), and re-shows only on decline when "Re-prompt on Decline" is enabled.
- "Re-prompt on Decline" never re-showed the popup from its own Decline All button. `handleConsentAction('decline-all')` dispatched `mdcc:changed` synchronously while the popup was still visible, and the re-prompt listener guarded on `display === 'none'`, so the guard always failed (`closePopup()` only hides ~300ms later via `setTimeout`). The re-prompt is now driven directly from the Decline All path via `scheduleRepromptOnDecline()`, with the once-per-session flag consumed only on a successful re-show, so unrelated decline events (`reset()`, the `[mdcc_manage_consent]` shortcode) no longer burn the one-shot.

### Added
- `window.mdccConsent.stored()` returns the raw stored consent object, or `null` when no choice has been made yet. Unlike `current()`, this distinguishes "no choice yet" from "declined all", which the popup uses to decide whether to show. Documented in `docs/JAVASCRIPT-API.md`.

## [1.7.4] - 2026-05-28

### Added
- WordPress 7.0 "Armstrong" compatibility (Tested up to: 7.0)
- Privacy Policy generator integration via `wp_add_privacy_policy_content()` — describes localStorage usage, the `mdcc_popup_shown` cookie, and the GCM v2 signalling behaviour
- Admin notice when an Elementor popup ID is configured but Elementor is not active (previously the built-in popup would silently suppress, leaving the site with no consent UI)
- GCM v2 default state now also declares `security_storage`, `functionality_storage`, and `personalization_storage` for completeness

### Changed
- `inject_gcm_default()` now uses `wp_print_inline_script_tag()` instead of a raw `<script>` echo. This composes with Content Security Policy nonce plugins (e.g. via `wp_inline_script_attributes`), preventing the consent default from being silently blocked on CSP-hardened sites.
- Removed `wp_strip_all_tags()` wrappers around `wp_add_inline_script()` / `wp_add_inline_style()` calls. The inputs are internally authored and not user-controlled; strip-tags added no security value and risked silently mangling future JS string literals containing `<`.

### Fixed
- `mdccConsent.reset()` now clears the `mdcc_popup_shown` cookie in addition to the localStorage state. Previously, calling reset from the manage-consent shortcode cleared the stored choice but left the popup hidden, so the user could not be re-prompted.
- First-time visitors no longer get a redundant `gtag('consent','update',{denied})` on page load. The runtime now only fires an `update` call when a stored consent choice exists; on first visit it leaves the inline default state (denied + `wait_for_update:500`) in place. This preserves GCM v2's wait window and keeps cookieless-pings behaviour intact in Advanced mode. Tracking remains fully blocked on first load because the inline default already denies all categories.

## [1.7.3] - 2026-02-28

### Added
- Complete translation template (`.pot`) with all 61 translatable strings extracted from source
- Enables community translation via translate.wordpress.org
- Users can now create translations with Loco Translate or Poedit without any extra setup
- Previous `.pot` was an empty stub — all strings were unreachable by translation tools

## [1.7.2] - 2026-02-11

### Fixed
- **Popup race condition** where JavaScript initialized before popup HTML was rendered via wp_footer
- Changed initialization from `DOMContentLoaded` to window `load` event
- Ensures popup HTML exists in DOM before JavaScript attempts interaction
- Resolves inconsistent popup display, particularly noticeable in Chrome browser

### Technical Changes
- Modified `MDCC_Popup_System::get_popup_javascript()` initialization code
- Replaced `DOMContentLoaded` event listener with `window.addEventListener('load', init)`
- Added inline code comment explaining why `load` event is required vs `DOMContentLoaded`
- The `load` event fires after all content (including wp_footer) is rendered
- Zero functional changes to popup behavior, appearance, or consent functionality

### User Impact
- Popup now appears consistently on all pages
- Fixes intermittent "popup not showing" issue reported by users
- Particularly improves reliability in Chrome (faster JavaScript execution)
- No configuration changes required - fix applies automatically

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

[Unreleased]: https://github.com/maxtdesign/maxtdesign-cookie-consent/compare/v1.8.0...HEAD
[1.8.0]: https://github.com/maxtdesign/maxtdesign-cookie-consent/compare/v1.7.6...v1.8.0
[1.7.6]: https://github.com/maxtdesign/maxtdesign-cookie-consent/compare/v1.7.5...v1.7.6
[1.7.5]: https://github.com/maxtdesign/maxtdesign-cookie-consent/compare/v1.7.4...v1.7.5
[1.7.4]: https://github.com/maxtdesign/maxtdesign-cookie-consent/compare/v1.7.3...v1.7.4
[1.7.3]: https://github.com/maxtdesign/maxtdesign-cookie-consent/compare/v1.7.2...v1.7.3
[1.7.2]: https://github.com/maxtdesign/maxtdesign-cookie-consent/compare/v1.7.1...v1.7.2
[1.7.1]: https://github.com/maxtdesign/maxtdesign-cookie-consent/compare/v1.7.0...v1.7.1
[1.7.0]: https://github.com/maxtdesign/maxtdesign-cookie-consent/compare/v1.6.0...v1.7.0
[1.6.0]: https://github.com/maxtdesign/maxtdesign-cookie-consent/releases/tag/v1.6.0
