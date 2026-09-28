# STATE: maxtdesign-cookie-consent
Updated: 2026-09-28 by session "MaxtDesign - Cookie Consent 2"

## Identity
Product: **MaxtDesign Cookie Consent - Google Consent Mode v2**. Slug
`maxtdesign-cookie-consent`, short code `cc`, registry row present. Prefixes
LOCKED: classes `MDCC_`, options and hooks `mdcc_`, no PHP namespace.
Repo `MaxtDesign/maxtdesign-cookie-consent`. Free wordpress.org plugin,
published under account `slaacr`, which is MaxtDesign.
Versions: wordpress.org serves **1.10.0**. `main` is **1.10.1**. The header on
the working branches is **1.11.0**, unreleased. Floors: WordPress 5.8, PHP 7.4.

## Status
Build phase of `docs/plan-popup-design-system.md`, on branch
`feat/popup-design-system`, stacked on `feat/country-gpc-consent` (`f91fd87`,
the 1.11.0 country and GPC candidate). Phase 0 (baseline) is done. Phases 1 to
5 follow. `main` is `7074066`. Nothing is merged. The exact 1.11.0 candidate
artifacts are live on MaxtOffroad by a separate route (2026-09-25). This work
folds into 1.11.0 and does not bump the version.

## Locked decisions
- 2026-09-28: plan accepted. **D1a**: Decline stays for `optin` visitors in
  Compact. **D2**: for `optout` visitors in Compact the Manage options link
  carries the opt-out wording, no separate opt-out button.
- 2026-09-28: byte caps do not move. Core 10,240 B, full dialog 14,336 B,
  inline loader under 1,024 B.
- 2026-09-12: regional consent model. California is opt-out, Pacific time zone
  over-inclusion accepted, no banner for the rest of the United States.
  `optin` stays the plugin default.

## Next actions
1. [session] Phase 1, packaging fix: `tools/prepare-svn.sh` must copy
   `popup-loader.js`, `popup-loader.min.js` and `templates/`.
   `bin/build-zip.php` allow-list gains `templates/*.php`. Prove by extraction.
2. [session] Phases 2 to 5 of the plan, one commit each.
3. [operator] Decide on flag F1 (custom primary colour is not applied in
   1.11.0). Phase 3 restores it unless told otherwise.
4. [operator] Start LocalWP site `plugin-test` when a real WordPress check is
   wanted. It returned 502 on 2026-09-28.
5. [operator] wordpress.org release of 1.11.0 stays held until the release
   gate passes and the operator gives an explicit go.

## External relationships
- Vendored libs: none. No Composer dependencies.
- wordpress.org SVN, account `slaacr`. Last release 1.10.0, SVN r3692962.
- MaxtOffroad runs the 1.11.0 candidate with its own country endpoint adapter.
  That adapter is not part of this repo.
- Laneparty theme restyles the popup through theme CSS (improvement log entry
  2026-09-26). Plan part B, the template override, serves that need.
- The main checkout is junction-mounted into LocalWP sites `plugin-test` and
  `razorback2`. It stays on `feat/country-gpc-consent`.

## Verification state
All run 2026-09-28 in the worktree, on the phase 0 tree.

| Check | Result |
|---|---|
| `node tests/country-consent.cjs` | 221 of 221 assertions pass |
| `php tests/functional-tests.php` | 30 of 30 pass |
| `node tests/popup-baseline.cjs` | 71 of 71 checks pass, Chrome 152.0.7977.85 |
| Baseline failure test | a 1 px padding change fails 23 of 71, a markup change fails 15 of 29. The check fails when it should |
| `npm run validate` | core 10,101 of 10,240 B. Full dialog 13,797 of 14,336 B. Loader 481 B |
| `npm run phpstan` (level 8) | **1 error**, already in `f91fd87`: `MDCC_Consent_Manager::country_endpoint()` has no return type. See flag F2 |
| `plugin-deliverables.php` | 14 passed, 0 failed |
| Outbound HTTP grep | 0 hits. One `file_get_contents()` reads the bundled loader from disk |

Phase 0 baseline numbers (default settings, LF line endings):

| Item | Bytes |
|---|---|
| Popup markup | 2,447 |
| Popup markup with the privacy policy link | 2,692 |
| Inline primary-colour block | 345 (not counted by any budget check today) |
| Inline loader | 481 |

Not verified: a real WordPress render (LocalWP was down), field Core Web
Vitals, Plugin Check on the 1.11.0 tree.

## History
- `plan-popup-design-system.md` → the accepted plan this build follows.
- `../tests/fixtures/popup-baseline/README.md` → what the phase 0 baseline holds.
- `RELEASE-STATUS.md`, `SESSION-HANDOFF.md` → release tracker and takeover
  brief up to 1.10.0.
- `v1.9.0-extensibility-scope.md` → scope of the 1.9.0 developer API.
- `JAVASCRIPT-API.md` → the `window.mdccConsent` API.
- `C:/maxt/pilots/aimasters-maxtoffroad-operations/COUNTRY-CONSENT-STAGING-20260925.md`
  and `COUNTRY-CONSENT-RELEASE-20260925.md` → the MaxtOffroad staging and
  production records for the 1.11.0 candidate.
- 2026-09-13: this file was created as a seed by the `maxtdesign-provenance`
  session. 2026-09-28: backfilled in phase 0.

## Flags
- **F1. Custom primary colour is not applied in 1.11.0.** Found by the phase 0
  baseline. With `popup_primary_color` set to `#c8102e` the Accept button
  still computes `rgb(0, 115, 170)`. Cause: the inline colour block prints in
  the head, and `popup-loader.js` appends `popup.min.css` after it. Both rules
  have the same specificity, so the stylesheet wins. In 1.10.x the stylesheet
  was enqueued first and the inline block followed it, so the colour applied.
  Shown in the harness, which mirrors that document order. Not yet confirmed
  on a WordPress site. Plan phase 3 moves the colour to a CSS variable, which
  does not depend on order.
- **F2. PHPStan level 8 has 1 error in the 1.11.0 candidate.** A missing
  return type on `country_endpoint()`. A docblock fixes it. Plan phase 5
  requires a clean run.
- **F3. `package.json` script `prepare-svn` calls `tools/prepare-svn.ps1` on
  Windows. That file does not exist.** Only `tools/prepare-svn.sh` does.
  Handled in phase 1.
- **F4. Worktree.** This session works in
  `.claude/worktrees/popup-design-system` inside the main checkout. The path
  is listed in the main checkout's `.git/info/exclude`. LocalWP does not
  serve it.
