# Release Status — pending / unreleased work

> Project-local tracker. NOT in Claude's global memory. Update at each release.
> Dev-only file; excluded from the distributed package by `tools/prepare-svn.sh`.

## Live on WordPress.org
- **Stable tag: 1.7.6** — SVN commit r3569354 (2026-06-11), trunk + `tags/1.7.6`.
- Trunk is the clean 18-file package (the stale `docs/` + `CHANGELOG.md` + root `README.md` that shipped in 1.7.2–1.7.4 were removed in the 1.7.5 push).

## ⚠️ GitHub `main` is AHEAD of the live SVN release
`main` contains changes that are committed to git but **not yet pushed to SVN** (deferred to the next release trip):

| Commit | Change | Ships next release |
|---|---|---|
| `9c21ad8` | `fix(admin)`: in-plugin "Plugin Documentation" link → `https://maxtdesign.com/plugins/cookie-consent/docs` (was the landing page) | ✅ |
| _1.7.7_ | `refactor(popup)`: extract popup inline JS → `assets/js/popup.js`/`popup.min.js` (still inlined, no extra HTTP); add `mdcc_should_show_popup` filter. Code version bumped to 1.7.7. | ✅ |

These are also recorded under `## [Unreleased]` in `CHANGELOG.md`.

## In progress (not yet on `main`)
- **`feat/wp-consent-api-bridge` → 1.8.0**: makes the plugin a WP Consent API provider (registers `wp_get_consent_type` = `optin`, bridges the existing analytics→`statistics` / ads→`marketing` choices to `wp_set_consent()`; `functional` always allowed). Additive, degrades gracefully without the WP Consent API plugin. Supersedes the 1.7.7 checkpoint when it ships (1.8.0 contains everything above).

## Next release checklist (when doing the next SVN push)
1. Bump version everywhere (header `Version`, `MDCC_VERSION`, `package.json`, `readme.txt` Stable tag).
2. Move `CHANGELOG.md` `## [Unreleased]` → the new version + date; add a `readme.txt` changelog + upgrade-notice entry.
3. `npm run build` (+ admin variants), run tests.
4. Commit + tag `vX.Y.Z`, push `main` + tag.
5. `bash tools/prepare-svn.sh X.Y.Z`; verify 18 files + versions.
6. SVN: `svn up`; **diff WC trunk vs staged to catch any stale files**; copy staged in; `svn cp trunk tags/X.Y.Z`; atomic `svn ci`.
7. Verify on server (tags list, Stable tag, clean tag); reply on any open support threads.

## Open follow-ups to confirm before/at next release
- **`#faq` anchor on maxtdesign.com**: a separate session is adding `id="faq"` to the FAQ section. The in-plugin FAQ links (`/plugins/cookie-consent#faq`, in `class-admin-settings.php` lines 459 and 715) only scroll correctly once that anchor is live — confirm it exists.
- Empty this file's "ahead of SVN" table once the pending work has shipped.

## Build/release tooling modernized (2026-07-17) — matches Disable REST / Product Bundles
Dev-only; no runtime/shipped-code change. All excluded from the user zip.
- **`.distignore`** added — defense-in-depth deny-list layered under the allow-list (`bin/build-zip.php` cross-checks the two so they can't drift).
- **`bin/build-zip.php`** — allow-list packager → verified `_build/<slug>-<ver>.zip` (forward-slash Linux-safe entries, staleness guard, PHP-lint, leak-check). Verified: 21-file set identical to `svn-upload/trunk/`. `npm run build:zip`.
- **PHPStan** (level 8) added — `phpstan.neon.dist` + hand-written `stubs/phpstan-stubs.php` (no Composer) + `phpstan-baseline.neon` grandfathering the current 48 findings (0 real bugs). Analyzer `phpstan.phar` is **git-ignored** (see `BUILD.md` to obtain it). `npm run phpstan`.
- `tools/prepare-svn.sh` / the SVN release path unchanged — new tooling layers on top.
- Full how-to in `BUILD.md`; rationale/decisions in memory `project-deferred-work-1-8-0`.

## Post-launch polish (not release-gating)
- **PHPCS still absent** (PHPStan is now in place, above). Add PHPCS as dev-only tooling (WPCS ruleset) when convenient, mirroring the suite plugins — never a runtime dependency; exclude from the shipped zip. Not release-gating.
