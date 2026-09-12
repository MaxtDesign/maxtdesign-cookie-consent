# Release Status — pending / unreleased work

> Project-local tracker. NOT in Claude's global memory. Update at each release.
> Dev-only file; excluded from the distributed package by `tools/prepare-svn.sh`.

## Live on WordPress.org
- **Stable tag: 1.8.0** — SVN r3615713 (2026-07-20), atomic commit (trunk + `tags/1.8.0` in one revision).
- Trunk is the clean 21-file package: the 18 from 1.7.6 + `assets/js/popup.js` + `assets/js/popup.min.js` (1.7.7 popup-JS extraction) + `includes/class-consent-api-bridge.php` (1.8.0 bridge). Diff vs staged confirmed no stale files.
- **1.8.0** = WP Consent API provider (registers `wp_get_consent_type` = `optin`; bridges analytics→`statistics` / ads→`marketing` via `wp_set_consent()`; `functional` always allowed). Bridge toggle defaults **ON** — a complete no-op unless the WP Consent API plugin is active. Also ships the 1.7.7 popup-JS extraction + `mdcc_should_show_popup` filter + the `9c21ad8` doc-link fix. Verified live before push: Test A (bridge active, razorback2 full stack) + Test B (no-op, plugin-test) + Plugin Check (shipping code clean) + PHPStan L8.
- ⏳ wp.org builds the downloadable zip asynchronously after a Stable-tag bump — verify `downloads.wordpress.org/plugin/maxtdesign-cookie-consent.1.8.0.zip` returns 200 (usually within minutes).

## 🚧 1.10.0 — built + verified, awaiting Plugin Check → release (2026-09-12)
Branch `feat/regional-consent-model` at `c3290df` (pushed). **Hotfix driver:** the site is 88% US and deny-by-default was losing GA4/Ads data. Ships three things in one release (main at `b346d3d` = 1.8.0 is unchanged until the merge):
- **Regional consent model** (new `consent_model` setting; default `optin` = no upgrade change). Under `regional`: EEA/UK/CH → opt-in popup (denied); **California → CCPA/CPRA opt-out** (granted by default, "Do Not Sell or Share My Personal Information" notice, no re-prompt; Pacific-TZ detection, over-inclusion accepted); everyone else → **no banner**. GCM `region`-scoped defaults — Google resolves region, server geolocates nothing, pages stay cacheable. Design/decisions: memory `project-regional-consent-model`.
- The never-released **1.9.0 extensibility API** (`docs/v1.9.0-extensibility-scope.md`).
- **Tested up to: 7.1** (merged `chore/wp-7-1-tested-up-to`).
- Verified: build OK, php -l clean, **PHPStan L8 clean**, budget **9.78 / 10 KB (230 B headroom)**, no test regressions, **all three tiers + CA opt-out + EEA accept driven via CDP with timezone overrides, zero console errors**. Staged trunk 21 files / 1.10.0 / Stable tag 1.10.0; WC r3692950 diff = identical file sets (no stale files); verified zip built.
- **Remaining gates:** Plugin Check on the staged trunk (junction `maxtdesign-cookie-consent-svncheck` recreated) → FF `main`, tag `v1.10.0`, push → copy staged into WC, `svn cp trunk tags/1.10.0` → atomic `svn ci` **only on explicit go**.
- Deferred: `.pot` not regenerated (no wp-cli here; translate.wordpress.org extracts from source). readme "Coming in Pro… launching 2025" is stale copy — operator's call.
- After shipping: set **Consent Model = Regional** on maxtoffroad (admin), update this file + `SESSION-HANDOFF.md`, delete the merged branches, remove the `-svncheck` junction (`cmd /c rmdir`).

## Next release checklist (when doing the next SVN push)
1. Bump version everywhere (header `Version`, `MDCC_VERSION`, `package.json`, `readme.txt` Stable tag).
2. Move `CHANGELOG.md` `## [Unreleased]` → the new version + date; add a `readme.txt` changelog + upgrade-notice entry.
3. `npm run build` (+ admin variants), run tests.
4. Commit + tag `vX.Y.Z`, push `main` + tag.
5. `bash tools/prepare-svn.sh X.Y.Z`; verify 21 files + versions.
6. SVN: `svn up`; **diff WC trunk vs staged to catch any stale files**; copy staged in; `svn cp trunk tags/X.Y.Z`; atomic `svn ci`.
7. Verify on server (tags list, Stable tag, clean tag); reply on any open support threads.

## Open follow-ups to confirm before/at next release
- **`#faq` anchor on maxtdesign.com**: a separate session is adding `id="faq"` to the FAQ section. The in-plugin FAQ links (`/plugins/cookie-consent#faq`, in `class-admin-settings.php` lines 459 and 715) only scroll correctly once that anchor is live — confirm it exists.
- ~~Post-1.8.0 zip verification~~ — done 2026-07-20 (HTTP 200, clean 21-file package, no dev files).

## Build/release tooling modernized (2026-07-17) — matches Disable REST / Product Bundles
Dev-only; no runtime/shipped-code change. All excluded from the user zip.
- **`.distignore`** added — defense-in-depth deny-list layered under the allow-list (`bin/build-zip.php` cross-checks the two so they can't drift).
- **`bin/build-zip.php`** — allow-list packager → verified `_build/<slug>-<ver>.zip` (forward-slash Linux-safe entries, staleness guard, PHP-lint, leak-check). Verified: 21-file set identical to `svn-upload/trunk/`. `npm run build:zip`.
- **PHPStan** (level 8) added — `phpstan.neon.dist` + hand-written `stubs/phpstan-stubs.php` (no Composer) + `phpstan-baseline.neon` grandfathering the current 48 findings (0 real bugs). Analyzer `phpstan.phar` is **git-ignored** (see `BUILD.md` to obtain it). `npm run phpstan`.
- `tools/prepare-svn.sh` / the SVN release path unchanged — new tooling layers on top.
- Full how-to in `BUILD.md`; rationale/decisions in memory `project-deferred-work-1-8-0`.

## Post-launch polish (not release-gating)
- **PHPCS still absent** (PHPStan is now in place, above). Add PHPCS as dev-only tooling (WPCS ruleset) when convenient, mirroring the suite plugins — never a runtime dependency; exclude from the shipped zip. Not release-gating.
