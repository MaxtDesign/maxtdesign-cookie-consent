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

These are also recorded under `## [Unreleased]` in `CHANGELOG.md`.

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
