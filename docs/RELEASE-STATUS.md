# Release Status — pending / unreleased work

> Project-local tracker. NOT in Claude's global memory. Update at each release.
> Dev-only file; excluded from the distributed package by `tools/prepare-svn.sh`.

## Live on WordPress.org
- **Stable tag: 1.10.0** — SVN **r3692962** (2026-09-12), atomic commit (trunk + `tags/1.10.0` in one revision). Git `main` = `810177e`, tag `v1.10.0`.
- Trunk is the clean 21-file package (same file set as 1.8.0; 11 files changed in content). Diff vs staged confirmed no stale files.
- **1.10.0** = regional consent model (`consent_model` setting; default `optin` = no upgrade change; under `regional`: EEA/UK/CH opt-in popup, **California CCPA opt-out notice**, no banner elsewhere; GCM `region`-scoped defaults, cacheable, no server geolocation) + the 1.9.0 extensibility API + Tested up to 7.1. Design/decisions: memory `project-regional-consent-model`; API scope: `docs/v1.9.0-extensibility-scope.md`.
- Previous: 1.8.0 (r3615713, 2026-07-20) = WP Consent API bridge + 1.7.7 popup-JS extraction. 1.9.0 was never released on its own.

## ✅ 1.10.0 — SHIPPED 2026-09-12 (SVN r3692962) — build record
Branch `feat/regional-consent-model` at `c3290df` (pushed). **Hotfix driver:** the site is 88% US and deny-by-default was losing GA4/Ads data. Ships three things in one release (main at `b346d3d` = 1.8.0 is unchanged until the merge):
- **Regional consent model** (new `consent_model` setting; default `optin` = no upgrade change). Under `regional`: EEA/UK/CH → opt-in popup (denied); **California → CCPA/CPRA opt-out** (granted by default, "Do Not Sell or Share My Personal Information" notice, no re-prompt; Pacific-TZ detection, over-inclusion accepted); everyone else → **no banner**. GCM `region`-scoped defaults — Google resolves region, server geolocates nothing, pages stay cacheable. Design/decisions: memory `project-regional-consent-model`.
- The never-released **1.9.0 extensibility API** (`docs/v1.9.0-extensibility-scope.md`).
- **Tested up to: 7.1** (merged `chore/wp-7-1-tested-up-to`).
- Verified: build OK, php -l clean, **PHPStan L8 clean**, budget **9.78 / 10 KB (230 B headroom)**, no test regressions, **all three tiers + CA opt-out + EEA accept driven via CDP with timezone overrides, zero console errors**. Staged trunk 21 files / 1.10.0 / Stable tag 1.10.0; WC r3692950 diff = identical file sets (no stale files); verified zip built.
- **Gates passed:** Plugin Check on the staged trunk — 135 findings, **all** the `-svncheck` folder-name artifact (`TextDomainMismatch` on every i18n call + the header `textdomain_mismatch`), 0 real (see memory `reference-plugin-check-dev-noise`). FF `main` → `v1.10.0` → pushed → SVN copy + `svn cp` → atomic `svn ci` r3692962 on explicit go.
- Deferred: `.pot` not regenerated (no wp-cli here; translate.wordpress.org extracts from source). readme "Coming in Pro… launching 2025" is stale copy — operator's call.
- **Post-ship checklist:** ☐ **set Consent Model = Regional on maxtoffroad (admin, after it updates to 1.10.0) — this is the switch that stops the data loss**; ☑ 1.10.0 downloads zip verified (HTTP 200, 21 files, Version/Stable tag 1.10.0, no dev leaks, regional code present); ☑ `-svncheck` junction removed from plugin-test (2026-09-12); ☑ merged branches + WIP stash deleted; ☐ refresh `SESSION-HANDOFF.md` (still describes the 1.8.0-era topology); ☐ watch the wp.org support forum for 1.10.0 upgrade reports.

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
